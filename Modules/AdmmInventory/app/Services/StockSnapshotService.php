<?php

namespace Modules\AdmmInventory\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\AdmmInventory\Models\AssetRecord;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\StockSnapshot;

class StockSnapshotService
{
    /**
     * Take a snapshot of current stock levels and store it.
     */
    public function takeSnapshot(Carbon $date): void
    {
        // Get all parent clients with their prefixes
        $clients = Client::whereNotNull('navixy_group_prefix')->get();

        // Build parent client mapping
        $parentMap = [];
        foreach ($clients as $client) {
            $parentMap[] = [
                'name'   => $client->name,
                'prefix' => strtoupper(trim($client->navixy_group_prefix)),
            ];
        }

        // Group Asset Register records by parent client
        $allRecords = AssetRecord::all();

        $grouped = [];
        foreach ($allRecords as $record) {
            $parent = $this->resolveParentClient($record->client, $parentMap);
            if (!isset($grouped[$parent])) {
                $grouped[$parent] = [];
            }
            $grouped[$parent][] = $record;
        }

        // Store snapshot per parent client
        foreach ($grouped as $parentClient => $records) {
            $records = collect($records);

            $devicesTotal       = $records->count();
            $devicesWithClient  = $records->where('location', 'CLIENT')->count();
            $devicesInStock     = $records->where('location', 'STOCK')->count();
            $devicesLost        = $records->where('location', 'LOST')->count();

            // SIM cards = rows with a phone number
            $simsAll            = $records->whereNotNull('sim_card_phone_no')->filter(fn($r) => !empty(trim($r->sim_card_phone_no ?? '')));
            $simsTotal          = $simsAll->count();
            $simsWithClient     = $simsAll->where('location', 'CLIENT')->count();
            $simsInStock        = $simsAll->where('location', 'STOCK')->count();
            $simsLost           = $simsAll->where('location', 'LOST')->count();

            StockSnapshot::updateOrCreate(
                [
                    'snapshot_date' => $date->toDateString(),
                    'parent_client' => $parentClient,
                ],
                [
                    'devices_total'       => $devicesTotal,
                    'devices_with_client' => $devicesWithClient,
                    'devices_in_stock'    => $devicesInStock,
                    'devices_lost'        => $devicesLost,
                    'sims_total'          => $simsTotal,
                    'sims_with_client'    => $simsWithClient,
                    'sims_in_stock'       => $simsInStock,
                    'sims_lost'           => $simsLost,
                ]
            );
        }
    }

    public function resolveParentClient(?string $clientName, array $parentMap): string
    {
        if (empty($clientName)) return 'UNASSIGNED';

        $upper = strtoupper(trim($clientName));

        foreach ($parentMap as $map) {
            if (empty($map['prefix'])) continue;
            if ($upper === $map['prefix']) return $map['name'];
            if (str_starts_with($upper, $map['prefix'] . ' ')) return $map['name'];
        }

        return $clientName; // Return as-is if no match
    }

    /**
     * Get live counts directly from Asset Register (no snapshot needed).
     */
    public function getLiveCounts(): array
    {
        $clients   = Client::whereNotNull('navixy_group_prefix')->get();
        $parentMap = [];
        foreach ($clients as $client) {
            $parentMap[] = [
                'name'   => $client->name,
                'prefix' => strtoupper(trim($client->navixy_group_prefix)),
            ];
        }

        $allRecords = AssetRecord::all();
        $grouped    = [];

        foreach ($allRecords as $record) {
            $parent = $this->resolveParentClient($record->client, $parentMap);
            if (!isset($grouped[$parent])) {
                $grouped[$parent] = collect();
            }
            $grouped[$parent]->push($record);
        }

        $result = [];
        foreach ($grouped as $parent => $records) {
            $sims = $records->filter(fn($r) => !empty(trim($r->sim_card_phone_no ?? '')));
            $result[] = [
                'client'              => $parent,
                'devices_total'       => $records->count(),
                'devices_with_client' => $records->where('location', 'CLIENT')->count(),
                'devices_in_stock'    => $records->where('location', 'STOCK')->count(),
                'devices_lost'        => $records->where('location', 'LOST')->count(),
                'sims_total'          => $sims->count(),
                'sims_with_client'    => $sims->where('location', 'CLIENT')->count(),
                'sims_in_stock'       => $sims->where('location', 'STOCK')->count(),
                'sims_lost'           => $sims->where('location', 'LOST')->count(),
            ];
        }

        // Sort by client name, put UNASSIGNED last
        usort($result, fn($a, $b) => $a['client'] === 'UNASSIGNED' ? 1 : strcmp($a['client'], $b['client']));

        return $result;
    }
}