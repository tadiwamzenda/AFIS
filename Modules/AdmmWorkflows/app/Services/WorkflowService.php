<?php

namespace Modules\AdmmWorkflows\Services;

use Illuminate\Support\Facades\DB;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\SimCard;
use Modules\Core\Contracts\AuditLogInterface;

class WorkflowService
{
    public function __construct(protected AuditLogInterface $auditLog) {}

    // ─── SIM Swap ─────────────────────────────────────────────────────────────

    public function simSwap(
        GpsDevice $device,
        SimCard   $newSim,
        string    $reason,
        int       $userId
    ): void {
        DB::transaction(function () use ($device, $newSim, $reason, $userId) {
            $oldSim = $device->simCard;

            // Detach old SIM → back to internal stock
            if ($oldSim && $oldSim->id !== $newSim->id) {
                $oldSim->update([
                    'status'           => 'unassigned',
                    'location_context' => 'internal_stock',
                    'client_id'        => null,
                    'navixy_tracker_id'=> null,
                ]);
            }

            // Attach new SIM
            $newSim->update([
                'status'            => $device->location_context === 'client_assigned'
                    ? 'active_client' : 'active_internal',
                'location_context'  => $device->location_context,
                'client_id'         => $device->client_id,
                'navixy_tracker_id' => $device->navixy_tracker_id,
            ]);

            // Update device
            $device->update(['sim_card_id' => $newSim->id]);

            $this->auditLog->record(
                event: 'sim.swapped',
                module: 'AdmmWorkflows',
                data: [
                    'device_serial'   => $device->serial_number,
                    'old_sim_msisdn'  => $oldSim?->msisdn ?? 'none',
                    'new_sim_msisdn'  => $newSim->msisdn,
                    'reason'          => $reason,
                ],
                userId: $userId,
                entityType: 'GpsDevice',
                entityId: $device->id,
            );
        });
    }

    // ─── Device Install ───────────────────────────────────────────────────────

    public function deviceInstall(
        GpsDevice $device,
        Client    $client,
        string    $vehicleReg,
        ?SimCard  $sim,
        string    $reason,
        int       $userId
    ): void {
        DB::transaction(function () use ($device, $client, $vehicleReg, $sim, $reason, $userId) {
            $device->update([
                'status'               => 'installed_client',
                'location_context'     => 'client_assigned',
                'client_id'            => $client->id,
                'vehicle_registration' => $vehicleReg,
                'installed_at'         => now(),
                'sim_card_id'          => $sim?->id,
            ]);

            if ($sim) {
                $sim->update([
                    'status'            => 'active_client',
                    'location_context'  => 'client_assigned',
                    'client_id'         => $client->id,
                    'navixy_tracker_id' => $device->navixy_tracker_id,
                ]);
            }

            $this->auditLog->record(
                event: 'device.installed',
                module: 'AdmmWorkflows',
                data: [
                    'device_serial'   => $device->serial_number,
                    'client'          => $client->name,
                    'vehicle'         => $vehicleReg,
                    'sim_msisdn'      => $sim?->msisdn ?? 'none',
                    'reason'          => $reason,
                ],
                userId: $userId,
                entityType: 'GpsDevice',
                entityId: $device->id,
            );
        });
    }

    // ─── Device Remove ────────────────────────────────────────────────────────

    public function deviceRemove(
        GpsDevice $device,
        string    $reason,
        bool      $detachSim,
        int       $userId
    ): void {
        DB::transaction(function () use ($device, $reason, $detachSim, $userId) {
            $sim      = $device->simCard;
            $oldClient = $device->client?->name ?? 'unknown';
            $oldVehicle = $device->vehicle_registration ?? 'unknown';

            $device->update([
                'status'               => 'in_office_stock',
                'location_context'     => 'internal_stock',
                'client_id'            => null,
                'vehicle_registration' => null,
                'installed_at'         => null,
            ]);

            if ($sim) {
                if ($detachSim) {
                    $sim->update([
                        'status'            => 'unassigned',
                        'location_context'  => 'internal_stock',
                        'client_id'         => null,
                        'navixy_tracker_id' => null,
                    ]);
                    $device->update(['sim_card_id' => null]);
                } else {
                    $sim->update([
                        'status'           => 'active_internal',
                        'location_context' => 'internal_stock',
                        'client_id'        => null,
                    ]);
                }
            }

            $this->auditLog->record(
                event: 'device.removed',
                module: 'AdmmWorkflows',
                data: [
                    'device_serial' => $device->serial_number,
                    'old_client'    => $oldClient,
                    'old_vehicle'   => $oldVehicle,
                    'sim_detached'  => $detachSim,
                    'reason'        => $reason,
                ],
                userId: $userId,
                entityType: 'GpsDevice',
                entityId: $device->id,
            );
        });
    }
}