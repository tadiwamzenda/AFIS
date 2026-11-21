<?php

namespace Modules\AdmmInventory\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AdmmInventory\Models\AssetRecord;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\InstallationEvent;
use Modules\AdmmInventory\Models\SimCard;
use Modules\AdmmInventory\Models\Technician;
use Modules\Core\Contracts\AuditLogInterface;

/**
 * Installation lifecycle: install (new or repair/swap), uncouple, edit details,
 * change installer. Every change writes an installation event and an audit
 * entry, and keeps the flat Asset Register in step (matched on IMEI).
 * Business-rule failures throw \DomainException with a message safe to show.
 */
class InstallationService
{
    public function __construct(private AuditLogInterface $auditLog) {}

    // ─── Registration helpers ────────────────────────────────────────────────

    /** Display form: upper case, single spaces. */
    public static function normalizeReg(string $reg): string
    {
        return trim(preg_replace('/\s+/', ' ', strtoupper($reg)));
    }

    /** Comparison key: ignores case, spaces, hyphens, dots, slashes and underscores. */
    public static function regKey(string $reg): string
    {
        return str_replace([' ', '-', '.', '/', '_'], '', strtoupper($reg));
    }

    /** Installed devices already on this registration. */
    public function findConflicts(string $reg, ?int $excludeDeviceId = null): Collection
    {
        $key = self::regKey($reg);
        if ($key === '') {
            return collect();
        }

        return GpsDevice::with(['client', 'simCard'])
            ->where('status', 'installed')
            ->whereRaw("UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(vehicle_registration,' ',''),'-',''),'.',''),'/',''),'_','')) = ?", [$key])
            ->when($excludeDeviceId, fn ($q) => $q->where('id', '!=', $excludeDeviceId))
            ->get();
    }

    // ─── Smart suggestions ───────────────────────────────────────────────────

    /** Most common vehicle make/model for a client, from devices and the Asset Register. */
    public function makeSuggestions(int $clientId, int $limit = 5): Collection
    {
        $client = Client::find($clientId);
        if (!$client) {
            return collect();
        }

        $counts  = [];
        $display = [];
        $add = function ($make, $n) use (&$counts, &$display) {
            $make = trim(preg_replace('/\s+/', ' ', (string) $make));
            if ($make === '') {
                return;
            }
            $k = strtoupper($make);
            $counts[$k]  = ($counts[$k] ?? 0) + (int) $n;
            $display[$k] ??= $make;
        };

        GpsDevice::where('client_id', $clientId)
            ->whereNotNull('vehicle_make')->where('vehicle_make', '!=', '')
            ->selectRaw('vehicle_make, COUNT(*) as n')->groupBy('vehicle_make')->get()
            ->each(fn ($r) => $add($r->vehicle_make, $r->n));

        $names = array_filter([mb_strtolower($client->name), mb_strtolower((string) $client->navixy_group_prefix)]);
        if ($names) {
            AssetRecord::whereIn(DB::raw('LOWER(client)'), $names)
                ->whereNotNull('vehicle_make')->where('vehicle_make', '!=', '')
                ->selectRaw('vehicle_make, COUNT(*) as n')->groupBy('vehicle_make')->get()
                ->each(fn ($r) => $add($r->vehicle_make, $r->n));
        }

        arsort($counts);

        return collect(array_slice(array_keys($counts), 0, $limit))->map(fn ($k) => $display[$k])->values();
    }

    // ─── Install ─────────────────────────────────────────────────────────────

    /**
     * @param array $in device_id, client_id, installed_at, vehicle_registration, fleet_number,
     *                  vehicle_make, installation_location, type (new|repair_swap), installer_id,
     *                  replaced_device_id, replaced_device_outcome, replaced_sim_outcome,
     *                  override (bool), override_reason
     * @return array{imei:string, registration:string, client:string}
     */
    public function install(array $in, ?int $userId, bool $isAdmin): array
    {
        return DB::transaction(function () use ($in, $userId, $isAdmin) {
            $type = $in['type'] ?? 'new';
            if (!in_array($type, ['new', 'repair_swap'], true)) {
                throw new \DomainException('Unknown installation type.');
            }

            $device = GpsDevice::whereKey((int) ($in['device_id'] ?? 0))->lockForUpdate()->first();
            if (!$device || $device->status !== 'with_technician') {
                throw new \DomainException('That unit is not with a technician. Pair and assign it first.');
            }
            $sim = $device->sim_card_id ? SimCard::whereKey($device->sim_card_id)->lockForUpdate()->first() : null;
            if (!$sim) {
                throw new \DomainException('That device has no SIM card. Pair it first.');
            }

            $client = Client::find((int) ($in['client_id'] ?? 0));
            if (!$client) {
                throw new \DomainException('Choose a client.');
            }

            $reg = self::normalizeReg((string) ($in['vehicle_registration'] ?? ''));
            if (self::regKey($reg) === '') {
                throw new \DomainException('Enter the vehicle registration number.');
            }

            $installer = !empty($in['installer_id'])
                ? Technician::active()->find((int) $in['installer_id'])
                : $device->assignedTechnician;
            if (!empty($in['installer_id']) && !$installer) {
                throw new \DomainException('That installer is not an active technician.');
            }

            $conflicts = $this->findConflicts($reg, $device->id);
            $notes     = [];

            if ($type === 'new') {
                if ($conflicts->isNotEmpty()) {
                    if (empty($in['override'])) {
                        throw new \DomainException("{$reg} already has an active device ({$conflicts->pluck('imei')->join(', ')}). Log it as a Repair/Swap, or override with a reason.");
                    }
                    if (!$isAdmin) {
                        throw new \DomainException('Only an administrator can override the duplicate-registration check.');
                    }
                    $reason = trim((string) ($in['override_reason'] ?? ''));
                    if (mb_strlen($reason) < 5) {
                        throw new \DomainException('Give a reason (at least 5 characters) for the override.');
                    }
                    $notes[] = "OVERRIDE of duplicate check (existing: {$conflicts->pluck('imei')->join(', ')}). Reason: {$reason}";
                }
            } else {
                $replacedId = (int) ($in['replaced_device_id'] ?? 0);

                if ($conflicts->isNotEmpty() && !$replacedId) {
                    throw new \DomainException('Choose which existing device is being replaced.');
                }

                if ($replacedId) {
                    $old = GpsDevice::whereKey($replacedId)->lockForUpdate()->first();
                    if (!$old || $old->status !== 'installed'
                        || self::regKey((string) $old->vehicle_registration) !== self::regKey($reg)) {
                        throw new \DomainException('The device being replaced is not installed on this registration.');
                    }
                    $this->doUncouple(
                        $old,
                        ($in['replaced_device_outcome'] ?? 'faulty'),
                        ($in['replaced_sim_outcome'] ?? 'stock'),
                        "Replaced by IMEI {$device->imei}",
                        $userId
                    );
                    $notes[] = "Replaced IMEI {$old->imei}";
                } else {
                    $notes[] = 'No previous device was recorded on this vehicle';
                }
            }

            $device->update([
                'status'                => 'installed',
                'client_id'             => $client->id,
                'vehicle_registration'  => $reg,
                'fleet_number'          => $this->nullable($in['fleet_number'] ?? null),
                'vehicle_make'          => $this->nullable($in['vehicle_make'] ?? null),
                'installation_location' => $this->nullable($in['installation_location'] ?? null),
                'installed_at'          => $in['installed_at'] ?? now()->toDateString(),
                'technician_id'         => $installer?->id,
                'technician'            => $installer?->name,
            ]);

            $sim->update([
                'status'           => 'active_client',
                'location_context' => 'client_assigned',
                'client_id'        => $client->id,
            ]);

            $this->event($device, $sim, $type === 'new' ? 'installed' : 'repair_swap', $userId,
                implode('. ', array_merge(["Installed on {$reg} for {$client->name}"], $notes)));

            $this->assetInstall($device->fresh(), $sim->fresh(), $client, $installer);

            $this->audit('device.installed', $device, ['registration' => $reg, 'client' => $client->name, 'type' => $type]);

            return ['imei' => $device->imei, 'registration' => $reg, 'client' => $client->name];
        });
    }

    // ─── Uncouple ────────────────────────────────────────────────────────────

    /** Release the vehicle. Device and SIM each go to 'faulty' or 'stock'. */
    public function uncouple(int $deviceId, string $deviceOutcome, string $simOutcome, string $reason, ?int $userId): array
    {
        return DB::transaction(function () use ($deviceId, $deviceOutcome, $simOutcome, $reason, $userId) {
            $device = GpsDevice::whereKey($deviceId)->lockForUpdate()->first();
            if (!$device || $device->status !== 'installed') {
                throw new \DomainException('Only an installed device can be uncoupled.');
            }
            if (mb_strlen(trim($reason)) < 3) {
                throw new \DomainException('Give a reason for the uncoupling.');
            }

            $reg = $device->vehicle_registration;
            $this->doUncouple($device, $deviceOutcome, $simOutcome, $reason, $userId);

            return ['imei' => $device->imei, 'registration' => $reg];
        });
    }

    private function doUncouple(GpsDevice $d, string $deviceOutcome, string $simOutcome, string $reason, ?int $userId): void
    {
        foreach ([$deviceOutcome, $simOutcome] as $o) {
            if (!in_array($o, ['faulty', 'stock'], true)) {
                throw new \DomainException('Choose Faulty or Back to stock for the device and the SIM.');
            }
        }

        $sim = $d->sim_card_id ? SimCard::whereKey($d->sim_card_id)->lockForUpdate()->first() : null;

        $before = [
            'registration' => $d->vehicle_registration,
            'fleet'        => $d->fleet_number,
            'make'         => $d->vehicle_make,
            'location'     => $d->installation_location,
            'client_id'    => $d->client_id,
            'technician_id'=> $d->technician_id,
        ];

        $d->update([
            'status'                => $deviceOutcome === 'faulty' ? 'faulty' : 'in_office_stock',
            'sim_card_id'           => null,
            'client_id'             => null,
            'vehicle_registration'  => null,
            'fleet_number'          => null,
            'vehicle_make'          => null,
            'installation_location' => null,
            'installed_at'          => null,
            'technician_id'         => null,
            'technician'            => null,
        ]);

        if ($sim) {
            $sim->update([
                'status'           => $simOutcome === 'faulty' ? 'faulty' : 'unassigned',
                'location_context' => 'internal_stock',
                'client_id'        => null,
            ]);
        }

        $label = fn ($o) => $o === 'faulty' ? 'faulty' : 'back to stock';
        $note  = "Removed from {$before['registration']}: device {$label($deviceOutcome)}"
               . ($sim ? ", SIM {$label($simOutcome)}" : '')
               . ". Reason: {$reason}";

        InstallationEvent::create([
            'gps_device_id'         => $d->id,
            'sim_card_id'           => $sim?->id,
            'event_type'            => 'uncoupled',
            'client_id'             => $before['client_id'],
            'vehicle_registration'  => $before['registration'],
            'fleet_number'          => $before['fleet'],
            'vehicle_make'          => $before['make'],
            'installation_location' => $before['location'],
            'technician_id'         => $before['technician_id'],
            'performed_by'          => $userId,
            'notes'                 => $note,
        ]);

        $this->assetUncouple($d, $deviceOutcome, $before['registration'], $reason);
        $this->audit('device.uncoupled', $d, ['registration' => $before['registration'], 'device' => $deviceOutcome, 'sim' => $simOutcome, 'reason' => $reason]);
    }

    // ─── Edit details / change installer ─────────────────────────────────────

    /** @return string[] human-readable list of what changed */
    public function updateDetails(int $deviceId, array $f, ?int $userId): array
    {
        return DB::transaction(function () use ($deviceId, $f, $userId) {
            $d = GpsDevice::whereKey($deviceId)->lockForUpdate()->first();
            if (!$d || $d->status !== 'installed') {
                throw new \DomainException('Only an installed device can be edited here.');
            }

            $client = Client::find((int) ($f['client_id'] ?? 0));
            if (!$client) {
                throw new \DomainException('Choose a client.');
            }
            $reg = self::normalizeReg((string) ($f['vehicle_registration'] ?? ''));
            if (self::regKey($reg) === '') {
                throw new \DomainException('Enter the vehicle registration number.');
            }

            if (self::regKey($reg) !== self::regKey((string) $d->vehicle_registration)) {
                $conflicts = $this->findConflicts($reg, $d->id);
                if ($conflicts->isNotEmpty()) {
                    throw new \DomainException("{$reg} already has an active device ({$conflicts->pluck('imei')->join(', ')}). Use Log Installation → Repair/Swap to replace it.");
                }
            }

            $new = [
                'installed_at'          => $f['installed_at'] ?? null,
                'client_id'             => $client->id,
                'vehicle_registration'  => $reg,
                'fleet_number'          => $this->nullable($f['fleet_number'] ?? null),
                'vehicle_make'          => $this->nullable($f['vehicle_make'] ?? null),
                'installation_location' => $this->nullable($f['installation_location'] ?? null),
            ];

            $labels = [
                'installed_at' => 'date', 'client_id' => 'client', 'vehicle_registration' => 'registration',
                'fleet_number' => 'fleet no', 'vehicle_make' => 'make/model', 'installation_location' => 'location',
            ];

            $changes = [];
            foreach ($new as $field => $value) {
                $old = $field === 'installed_at' ? $d->installed_at?->format('Y-m-d') : $d->{$field};
                if ((string) $old !== (string) $value) {
                    $changes[] = "{$labels[$field]}: " . ($old ?: '—') . ' → ' . ($value ?: '—');
                }
            }
            if (!$changes) {
                throw new \DomainException('No changes to save.');
            }

            $d->update($new);
            if ($d->sim_card_id) {
                SimCard::whereKey($d->sim_card_id)->update(['client_id' => $client->id]);
            }

            $sim = $d->simCard;
            $this->event($d, $sim, 'edited', $userId, 'Edited: ' . implode('; ', $changes));

            AssetRecord::where('gps_device_imei', $d->imei)->update([
                'installation_date' => $d->installed_at,
                'client'            => $client->name,
                'vehicle_reg_no'    => $d->vehicle_registration,
                'vehicle_fleet_no'  => $d->fleet_number,
                'vehicle_make'      => $d->vehicle_make,
            ]);

            $this->audit('device.edited', $d, ['changes' => $changes]);

            return $changes;
        });
    }

    public function changeInstaller(int $deviceId, int $technicianId, ?int $userId): void
    {
        DB::transaction(function () use ($deviceId, $technicianId, $userId) {
            $d    = GpsDevice::whereKey($deviceId)->lockForUpdate()->first();
            $tech = Technician::active()->find($technicianId);

            if (!$d || $d->status !== 'installed') {
                throw new \DomainException('Only an installed device can have its installer changed.');
            }
            if (!$tech) {
                throw new \DomainException('That technician is not active.');
            }
            if ($d->technician_id === $tech->id) {
                throw new \DomainException('That technician is already the recorded installer.');
            }

            $from = $d->assignedTechnician?->name ?? ($d->technician ?: 'nobody');
            $d->update(['technician_id' => $tech->id, 'technician' => $tech->name]);

            $this->event($d, $d->simCard, 'installer_changed', $userId, "Installer changed from {$from} to {$tech->name}");
            AssetRecord::where('gps_device_imei', $d->imei)->update(['technician' => $tech->name]);
            $this->audit('device.installer_changed', $d, ['from' => $from, 'to' => $tech->name]);
        });
    }

    // ─── Asset Register bridge ───────────────────────────────────────────────

    private function assetInstall(GpsDevice $d, SimCard $sim, Client $client, ?Technician $installer): void
    {
        AssetRecord::updateOrCreate(['gps_device_imei' => $d->imei], [
            'installation_date'  => $d->installed_at,
            'client'             => $client->name,
            'vehicle_reg_no'     => $d->vehicle_registration,
            'vehicle_fleet_no'   => $d->fleet_number,
            'vehicle_make'       => $d->vehicle_make,
            'gps_device_type'    => $d->device_type,
            'gps_device_state'   => 'ACTIVE',
            'sim_card_serial_no' => $sim->iccid,
            'sim_card_phone_no'  => $sim->msisdn,
            'sim_card_isp'       => $this->ispFor($sim->network_provider),
            'location'           => 'CLIENT',
            'technician'         => $installer?->name,
        ]);
    }

    private function assetUncouple(GpsDevice $d, string $deviceOutcome, ?string $reg, string $reason): void
    {
        $line = now()->timezone('Africa/Harare')->format('d/m/Y') . ": removed from {$reg} ({$reason})";

        AssetRecord::where('gps_device_imei', $d->imei)->get()->each(function (AssetRecord $r) use ($deviceOutcome, $line) {
            $r->update([
                'gps_device_state' => $deviceOutcome === 'faulty' ? 'MALFUNCTION' : 'ACTIVE',
                'location'         => 'STOCK',
                'client'           => null,
                'vehicle_reg_no'   => null,
                'vehicle_fleet_no' => null,
                'vehicle_make'     => null,
                'comment'          => trim(($r->comment ? $r->comment . "\n" : '') . $line),
            ]);
        });
    }

    private function ispFor(?string $provider): ?string
    {
        return match (strtoupper((string) $provider)) {
            'ECONET' => 'ECONET',
            'NETONE' => 'NETONE',
            'GLOBAL' => 'GLOBAL',
            default  => null,   // e.g. Telecel is not in the register's list
        };
    }

    // ─── Internals ───────────────────────────────────────────────────────────

    private function nullable(?string $v): ?string
    {
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }

    private function event(GpsDevice $d, ?SimCard $sim, string $type, ?int $userId, string $note): void
    {
        InstallationEvent::create([
            'gps_device_id'         => $d->id,
            'sim_card_id'           => $sim?->id,
            'event_type'            => $type,
            'client_id'             => $d->client_id,
            'vehicle_registration'  => $d->vehicle_registration,
            'fleet_number'          => $d->fleet_number,
            'vehicle_make'          => $d->vehicle_make,
            'installation_location' => $d->installation_location,
            'technician_id'         => $d->technician_id,
            'performed_by'          => $userId,
            'notes'                 => $note,
        ]);
    }

    private function audit(string $event, GpsDevice $d, array $data): void
    {
        $this->auditLog->record(
            event: $event,
            module: 'AdmmInventory',
            data: ['imei' => $d->imei] + $data,
            entityType: 'GpsDevice',
            entityId: $d->id
        );
    }
}
