<?php

namespace Modules\AdmmInventory\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\InstallationEvent;
use Modules\AdmmInventory\Models\SimCard;
use Modules\Core\Contracts\AuditLogInterface;

/**
 * Read queries and status changes behind the Stock Take screens.
 * Every change writes an installation event and an audit entry, and
 * throws \DomainException with a message that is safe to show users.
 */
class StockTakeService
{
    public const STALE_DAYS  = 30;
    public const LIST_LIMIT  = 200;

    public function __construct(
        private PairingService $pairing,
        private AuditLogInterface $auditLog,
    ) {}

    // ═══ Queries ═════════════════════════════════════════════════════════════

    /**
     * Devices (with their SIMs) in technicians' hands that are not installed yet,
     * grouped by technician.
     */
    public function technicianStock(?int $technicianId = null): Collection
    {
        $devices = GpsDevice::with(['simCard', 'assignedTechnician'])
            ->where('status', 'with_technician')
            ->when($technicianId, fn ($q) => $q->where('technician_id', $technicianId))
            ->orderBy('imei')
            ->get();

        $pairedAt = InstallationEvent::where('event_type', 'paired')
            ->whereIn('gps_device_id', $devices->pluck('id'))
            ->orderBy('created_at')
            ->get()
            ->groupBy('gps_device_id')
            ->map(fn ($events) => $events->last()->created_at);

        return $devices
            ->groupBy(fn ($d) => $d->technician_id ?? 0)
            ->map(function ($group) use ($pairedAt) {
                $first = $group->first();

                return [
                    'technician_id' => $first->technician_id,
                    'name'          => $first->assignedTechnician?->name
                                        ?? ($first->technician ?: 'Unknown technician'),
                    'units'         => $group->map(fn ($d) => [
                        'device'    => $d,
                        'sim'       => $d->simCard,
                        'paired_at' => $pairedAt[$d->id] ?? null,
                    ])->values(),
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /** Active SIM cards that are not in any device. */
    public function unpairedSims(string $search = '', bool $onlyStale = false): Builder
    {
        $q = $this->pairing->availableSims();

        if ($search = trim($search)) {
            $t = addcslashes($search, '%_\\');
            $d = preg_replace('/\D+/', '', $search);
            $p = str_starts_with($d, '0') ? substr($d, 1) : $d;

            $q->where(function (Builder $w) use ($t, $p) {
                $w->where('iccid', 'like', "%{$t}%")->orWhere('msisdn', 'like', "%{$t}%");
                if (strlen($p) >= 3) {
                    $w->orWhere('msisdn', 'like', "%{$p}%");
                }
            });
        }

        return $this->applyStale($q, $onlyStale);
    }

    /** Devices in office stock with no SIM and nobody holding them. */
    public function availableDevices(string $search = '', bool $onlyStale = false): Builder
    {
        $q = $this->pairing->availableDevices();

        if ($search = trim($search)) {
            $t = addcslashes($search, '%_\\');
            $q->where(function (Builder $w) use ($t) {
                $w->where('imei', 'like', "%{$t}%")
                  ->orWhere('device_type', 'like', "%{$t}%")
                  ->orWhere('model', 'like', "%{$t}%");
            });
        }

        return $this->applyStale($q, $onlyStale);
    }

    public function faultyDevices(): Collection
    {
        return GpsDevice::with('simCard')->where('status', 'faulty')->orderByDesc('updated_at')->limit(self::LIST_LIMIT)->get();
    }

    public function faultySims(): Collection
    {
        return SimCard::where('status', 'faulty')->orderByDesc('updated_at')->limit(self::LIST_LIMIT)->get();
    }

    /** Never-verified first, then oldest verification first, for a stock take. */
    private function applyStale(Builder $q, bool $onlyStale): Builder
    {
        if ($onlyStale) {
            $q->where(fn ($w) => $w->whereNull('last_verified_at')
                ->orWhere('last_verified_at', '<', now()->subDays(self::STALE_DAYS)));
        }

        return $q->orderByRaw('last_verified_at IS NULL DESC')->orderBy('last_verified_at');
    }

    public function staleCount(Builder $base): int
    {
        return (clone $base)->where(fn ($w) => $w->whereNull('last_verified_at')
            ->orWhere('last_verified_at', '<', now()->subDays(self::STALE_DAYS)))->count();
    }

    // ═══ Stock-take actions ══════════════════════════════════════════════════

    public function verifyDevice(int $id, ?int $userId): void
    {
        DB::transaction(function () use ($id, $userId) {
            $d = $this->lockDevice($id);
            if (!in_array($d->status, ['in_office_stock', 'with_technician'], true)) {
                throw new \DomainException('Only devices in stock or with a technician can be verified.');
            }
            $d->update(['last_verified_at' => now(), 'last_verified_by' => $userId]);
            $this->record($d, null, 'verified', $userId, "Device {$d->imei} verified in stock take");
        });
    }

    public function verifySim(int $id, ?int $userId): void
    {
        DB::transaction(function () use ($id, $userId) {
            $s = $this->lockSim($id);
            $this->assertSimFree($s);
            $s->update(['last_verified_at' => now(), 'last_verified_by' => $userId]);
            $this->record(null, $s, 'verified', $userId, "SIM {$s->iccid} verified in stock take");
        });
    }

    public function flagSimLost(int $id, ?int $userId): void
    {
        $this->changeSim($id, $userId, 'lost', 'flagged_lost', 'flagged as lost');
    }

    public function flagSimFaulty(int $id, ?int $userId): void
    {
        $this->changeSim($id, $userId, 'faulty', 'marked_faulty', 'marked faulty');
    }

    public function flagDeviceFaulty(int $id, ?int $userId): void
    {
        $this->changeFreeDevice($id, $userId, 'faulty', 'marked_faulty', 'marked faulty');
    }

    public function flagDeviceLost(int $id, ?int $userId): void
    {
        $this->changeFreeDevice($id, $userId, 'lost_stolen', 'flagged_lost', 'flagged as lost / stolen');
    }

    // ═══ Faulty section actions ══════════════════════════════════════════════

    /** Repaired or cleared: back to office stock, unlinked from any SIM. */
    public function returnDeviceToStock(int $id, ?int $userId): void
    {
        DB::transaction(function () use ($id, $userId) {
            $d = $this->lockDevice($id);
            if ($d->status !== 'faulty') {
                throw new \DomainException('Only a faulty device can be returned to stock.');
            }

            $sim = $d->sim_card_id ? SimCard::whereKey($d->sim_card_id)->lockForUpdate()->first() : null;

            $d->update([
                'status'        => 'in_office_stock',
                'sim_card_id'   => null,
                'technician_id' => null,
                'technician'    => null,
            ]);

            if ($sim && $sim->status === 'active_internal') {
                $sim->update(['status' => 'unassigned']);
            }

            $this->record($d, $sim, 'returned_to_stock', $userId, "Faulty device {$d->imei} returned to office stock");
        });
    }

    public function returnSimToStock(int $id, ?int $userId): void
    {
        DB::transaction(function () use ($id, $userId) {
            $s = $this->lockSim($id);
            if ($s->status !== 'faulty') {
                throw new \DomainException('Only a faulty SIM card can be returned to stock.');
            }
            $s->update(['status' => 'unassigned', 'location_context' => 'internal_stock']);
            $this->record(null, $s, 'returned_to_stock', $userId, "Faulty SIM {$s->iccid} returned to stock");
        });
    }

    public function writeOffDevice(int $id, ?int $userId): void
    {
        DB::transaction(function () use ($id, $userId) {
            $d = $this->lockDevice($id);
            if ($d->status !== 'faulty') {
                throw new \DomainException('Only a faulty device can be written off.');
            }
            $d->update(['status' => 'decommissioned']);
            $this->record($d, null, 'written_off', $userId, "Faulty device {$d->imei} written off (decommissioned)");
        });
    }

    public function writeOffSim(int $id, ?int $userId): void
    {
        DB::transaction(function () use ($id, $userId) {
            $s = $this->lockSim($id);
            if ($s->status !== 'faulty') {
                throw new \DomainException('Only a faulty SIM card can be written off.');
            }
            $s->update(['status' => 'deactivated']);
            $this->record(null, $s, 'written_off', $userId, "Faulty SIM {$s->iccid} written off (deactivated)");
        });
    }

    // ═══ Internals ═══════════════════════════════════════════════════════════

    private function changeSim(int $id, ?int $userId, string $status, string $eventType, string $verb): void
    {
        DB::transaction(function () use ($id, $userId, $status, $eventType, $verb) {
            $s = $this->lockSim($id);
            $this->assertSimFree($s);
            $s->update(['status' => $status]);
            $this->record(null, $s, $eventType, $userId, "SIM {$s->iccid} {$verb}");
        });
    }

    private function changeFreeDevice(int $id, ?int $userId, string $status, string $eventType, string $verb): void
    {
        DB::transaction(function () use ($id, $userId, $status, $eventType, $verb) {
            $d = $this->lockDevice($id);
            if ($d->status !== 'in_office_stock' || $d->sim_card_id) {
                throw new \DomainException('Only an unpaired device in office stock can be changed here.');
            }
            $d->update(['status' => $status]);
            $this->record($d, null, $eventType, $userId, "Device {$d->imei} {$verb}");
        });
    }

    private function assertSimFree(SimCard $s): void
    {
        if (GpsDevice::where('sim_card_id', $s->id)->exists()) {
            throw new \DomainException('That SIM card is inside a device. Uncouple it first.');
        }
        if (in_array($s->status, PairingService::SIM_BLOCKED, true)) {
            throw new \DomainException('That SIM card is not in an active state.');
        }
    }

    private function lockDevice(int $id): GpsDevice
    {
        return GpsDevice::whereKey($id)->lockForUpdate()->first()
            ?? throw new \DomainException('Device not found.');
    }

    private function lockSim(int $id): SimCard
    {
        return SimCard::whereKey($id)->lockForUpdate()->first()
            ?? throw new \DomainException('SIM card not found.');
    }

    private function record(?GpsDevice $d, ?SimCard $s, string $eventType, ?int $userId, string $note): void
    {
        InstallationEvent::create([
            'gps_device_id' => $d?->id,
            'sim_card_id'   => $s?->id,
            'event_type'    => $eventType,
            'technician_id' => $d?->technician_id,
            'performed_by'  => $userId,
            'notes'         => $note,
        ]);

        $this->auditLog->record(
            event: 'stock.' . $eventType,
            module: 'AdmmInventory',
            data: ['note' => $note],
            entityType: $d ? 'GpsDevice' : 'SimCard',
            entityId: $d?->id ?? $s->id
        );
    }
}
