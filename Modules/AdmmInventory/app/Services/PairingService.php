<?php

namespace Modules\AdmmInventory\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\InstallationEvent;
use Modules\AdmmInventory\Models\SimCard;
use Modules\AdmmInventory\Models\Technician;
use Modules\Core\Contracts\AuditLogInterface;

/**
 * Pairs a device with a SIM card and puts the pair in a technician's custody.
 * All rules live here so the pairing screen (and later the installation form)
 * share them. Methods throw \DomainException with a message safe to show users.
 */
class PairingService
{
    /** SIM statuses that can never be paired. */
    public const SIM_BLOCKED = ['lost', 'faulty', 'deactivated', 'suspended', 'inactive', 'active_client'];

    public const MIN_SEARCH_DIGITS = 3;

    public function __construct(private AuditLogInterface $auditLog) {}

    public static function digits(string $term): string
    {
        return preg_replace('/\D+/', '', $term);
    }

    // ─── Eligible hardware ───────────────────────────────────────────────────

    /** Devices sitting in office stock with no SIM. */
    public function availableDevices(): Builder
    {
        return GpsDevice::query()
            ->where('status', 'in_office_stock')
            ->whereNull('sim_card_id');
    }

    /** SIM cards not in any device and not lost / faulty / deactivated / on a client. */
    public function availableSims(): Builder
    {
        return SimCard::query()
            ->whereDoesntHave('gpsDevice')
            ->whereNotIn('status', self::SIM_BLOCKED);
    }

    // ─── Substring search (returns up to $limit + 1 rows so the UI can say "more") ─

    public function searchDevices(string $term, int $limit = 10): Collection
    {
        $d = self::digits($term);
        if (strlen($d) < self::MIN_SEARCH_DIGITS) {
            return collect();
        }

        return $this->availableDevices()
            ->where('imei', 'like', "%{$d}%")
            ->orderBy('imei')
            ->limit($limit + 1)
            ->get();
    }

    public function searchSims(string $term, int $limit = 10): Collection
    {
        $d = self::digits($term);
        if (strlen($d) < self::MIN_SEARCH_DIGITS) {
            return collect();
        }

        // Phones are stored as +263771234567, people type 0771234567: drop one leading 0
        $phone = str_starts_with($d, '0') ? substr($d, 1) : $d;

        return $this->availableSims()
            ->where(function (Builder $q) use ($d, $phone) {
                $q->where('iccid', 'like', "%{$d}%");
                if (strlen($phone) >= 2) {
                    $q->orWhere('msisdn', 'like', "%{$phone}%");
                }
            })
            ->orderBy('iccid')
            ->limit($limit + 1)
            ->get();
    }

    // ─── Pair + assign ───────────────────────────────────────────────────────

    /** @return array{imei:string, iccid:string, technician:string} */
    public function pair(int $deviceId, int $simId, int $technicianId, ?int $userId): array
    {
        return DB::transaction(function () use ($deviceId, $simId, $technicianId, $userId) {
            $device = GpsDevice::whereKey($deviceId)->lockForUpdate()->first();
            $sim    = SimCard::whereKey($simId)->lockForUpdate()->first();
            $tech   = Technician::whereKey($technicianId)->where('is_active', true)->first();

            if (!$device || $device->status !== 'in_office_stock' || $device->sim_card_id) {
                throw new \DomainException('That device is no longer available for pairing.');
            }
            if (!$sim || in_array($sim->status, self::SIM_BLOCKED, true)
                || GpsDevice::where('sim_card_id', $sim->id)->exists()) {
                throw new \DomainException('That SIM card is no longer available for pairing.');
            }
            if (!$tech) {
                throw new \DomainException('That technician is not active.');
            }

            $device->update([
                'sim_card_id'   => $sim->id,
                'technician_id' => $tech->id,
                'technician'    => $tech->name,   // keeps the legacy text column in step
                'status'        => 'with_technician',
            ]);

            $sim->update([
                'status'           => 'active_internal',
                'location_context' => 'internal_stock',
            ]);

            InstallationEvent::create([
                'gps_device_id' => $device->id,
                'sim_card_id'   => $sim->id,
                'event_type'    => 'paired',
                'technician_id' => $tech->id,
                'performed_by'  => $userId,
                'notes'         => "Paired IMEI {$device->imei} with ICCID {$sim->iccid}; assigned to {$tech->name}",
            ]);

            $this->auditLog->record(
                event: 'device.paired',
                module: 'AdmmInventory',
                data: ['imei' => $device->imei, 'iccid' => $sim->iccid, 'technician' => $tech->name],
                entityType: 'GpsDevice',
                entityId: $device->id
            );

            return ['imei' => $device->imei, 'iccid' => $sim->iccid, 'technician' => $tech->name];
        });
    }

    // ─── Undo a pairing that has not been installed yet ──────────────────────

    /** @return array{imei:string, iccid:?string} */
    public function unpair(int $deviceId, ?int $userId): array
    {
        return DB::transaction(function () use ($deviceId, $userId) {
            $device = GpsDevice::whereKey($deviceId)->lockForUpdate()->first();

            if (!$device || $device->status !== 'with_technician') {
                throw new \DomainException('Only a pair that is still with a technician (not installed) can be undone.');
            }

            $sim  = $device->sim_card_id ? SimCard::whereKey($device->sim_card_id)->lockForUpdate()->first() : null;
            $techId = $device->technician_id;

            $device->update([
                'sim_card_id'   => null,
                'technician_id' => null,
                'technician'    => null,
                'status'        => 'in_office_stock',
            ]);

            if ($sim && $sim->status === 'active_internal') {
                $sim->update(['status' => 'unassigned', 'location_context' => 'internal_stock']);
            }

            InstallationEvent::create([
                'gps_device_id' => $device->id,
                'sim_card_id'   => $sim?->id,
                'event_type'    => 'unpaired',
                'technician_id' => $techId,
                'performed_by'  => $userId,
                'notes'         => 'Pairing undone before installation; hardware returned to office stock',
            ]);

            $this->auditLog->record(
                event: 'device.unpaired',
                module: 'AdmmInventory',
                data: ['imei' => $device->imei, 'iccid' => $sim?->iccid],
                entityType: 'GpsDevice',
                entityId: $device->id
            );

            return ['imei' => $device->imei, 'iccid' => $sim?->iccid];
        });
    }
}
