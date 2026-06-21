<?php

namespace Modules\AdmmReports\Services;

use Illuminate\Support\Collection;
use Modules\AdmmInventory\Models\Accessory;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\SimCard;
use Modules\AuditLog\Models\AuditLog;

class ReportDataService
{
    public function simCardInventory(): Collection
    {
        return SimCard::with('client')
            ->orderBy('network_provider')
            ->orderBy('msisdn')
            ->get();
    }

    public function simCardsByProvider(): Collection
    {
        return SimCard::with('client')
            ->orderBy('network_provider')
            ->orderBy('status')
            ->get()
            ->groupBy('network_provider');
    }

    public function simCardsByBatch(): Collection
    {
        return SimCard::with('client')
            ->orderBy('batch_code')
            ->orderBy('msisdn')
            ->get()
            ->groupBy(fn($s) => $s->batch_code ?? 'No Batch');
    }

    public function simCardsByStatus(): Collection
    {
        return SimCard::with('client')
            ->orderBy('status')
            ->orderBy('msisdn')
            ->get()
            ->groupBy('status');
    }

    public function gpsDeviceInventory(): Collection
    {
        return GpsDevice::with(['client', 'simCard'])
            ->orderBy('status')
            ->orderBy('serial_number')
            ->get();
    }

    public function gpsDevicesByClient(): Collection
    {
        return GpsDevice::with(['client', 'simCard'])
            ->where('location_context', 'client_assigned')
            ->orderBy('serial_number')
            ->get()
            ->groupBy(fn($d) => $d->client?->name ?? 'Unassigned');
    }

    public function devicesInStock(): Collection
    {
        return GpsDevice::with('simCard')
            ->where('location_context', 'internal_stock')
            ->orderBy('status')
            ->orderBy('serial_number')
            ->get();
    }

    public function accessoryInventory(): Collection
    {
        return Accessory::with(['accessoryType', 'client', 'gpsDevice'])
            ->orderBy('status')
            ->get();
    }

    public function accessoriesByType(): Collection
    {
        return Accessory::with(['accessoryType', 'client', 'gpsDevice'])
            ->orderBy('status')
            ->get()
            ->groupBy(fn($a) => $a->accessoryType?->name ?? 'Unknown');
    }

    public function assignmentChain(): Collection
    {
        return Client::with([
            'gpsDevices.simCard',
            'gpsDevices.accessories.accessoryType',
        ])
        ->active()
        ->orderBy('name')
        ->get();
    }

    public function internalStockSummary(): array
    {
        return [
            'sim_cards'   => SimCard::where('location_context', 'internal_stock')->get(),
            'gps_devices' => GpsDevice::with('simCard')->where('location_context', 'internal_stock')->get(),
            'accessories' => Accessory::with('accessoryType')->where('location_context', 'internal_stock')->get(),
        ];
    }

    public function auditTrail(array $filters = []): Collection
    {
        return AuditLog::with('user')
            ->when(!empty($filters['module']),       fn($q) => $q->where('module', $filters['module']))
            ->when(!empty($filters['event']),        fn($q) => $q->where('event',  $filters['event']))
            ->when(!empty($filters['date_from']),    fn($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']),      fn($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->latest('created_at')
            ->limit(500)
            ->get();
    }
}