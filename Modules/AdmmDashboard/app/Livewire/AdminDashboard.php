<?php

namespace Modules\AdmmDashboard\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Modules\AdmmInventory\Models\AssetRecord;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\StockSnapshot;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AuditLog\Models\AuditLog;
use Modules\Notifications\Models\AfisNotification;

class AdminDashboard extends Component
{
    public function render()
    {
        $now = Carbon::now('Africa/Harare');

        // ── Clients ───────────────────────────────────────────────────────────
        $totalClients  = Client::where('is_active', true)
            ->where('id', '!=', 21) // exclude MISCELLANEOUS
            ->count();

        // ── Devices from Asset Register ───────────────────────────────────────
        $totalDevices       = AssetRecord::count();
        $devicesWithClient  = AssetRecord::where('location', 'CLIENT')->count();
        $devicesInStock     = AssetRecord::where('location', 'STOCK')->count();
        $devicesLost        = AssetRecord::where('location', 'LOST')->count();

        // ── SIM cards from Asset Register ─────────────────────────────────────
        $simQuery           = AssetRecord::whereNotNull('sim_card_phone_no')
                                ->where('sim_card_phone_no', '!=', '');
        $totalSims          = (clone $simQuery)->count();
        $simsWithClient     = (clone $simQuery)->where('location', 'CLIENT')->count();
        $simsInStock        = (clone $simQuery)->where('location', 'STOCK')->count();
        $simsLost           = (clone $simQuery)->where('location', 'LOST')->count();

        // ── Fleet summary ─────────────────────────────────────────────────────
        $totalTrackers  = AfisTracker::where('client_id', '!=', 21)->count();

        // ── Recent installations (last 5 added to asset register) ────────────
        $recentInstallations = AssetRecord::whereNotNull('installation_date')
            ->orderByDesc('installation_date')
            ->limit(5)
            ->get();

        // ── Recent activity (audit log) ────────────────────────────────────────
        $recentActivity = AuditLog::with('user')
            ->latest()
            ->limit(10)
            ->get();

        // ── Trend deltas (today vs. yesterday's snapshot) ─────────────────────
        $yesterday = $this->snapshotTotalsForDate($now->copy()->subDay());

        $clientsDelta = $this->percentDelta($totalClients, $yesterday['clients']);
        $devicesDelta = $this->percentDelta($devicesWithClient, $yesterday['devices_with_client']);
        $simsDelta    = $this->percentDelta($simsWithClient, $yesterday['sims_with_client']);
        $lostDelta    = ($devicesLost + $simsLost) - $yesterday['lost']; // raw count change, not %

        // ── 14-day device trend (line chart) ───────────────────────────────────
        $deviceTrend = StockSnapshot::where('snapshot_date', '>=', $now->copy()->subDays(13)->startOfDay())
            ->where('parent_client', '!=', 'MISCELLANEOUS')
            ->selectRaw('snapshot_date, SUM(devices_total) as total')
            ->groupBy('snapshot_date')
            ->orderBy('snapshot_date')
            ->get()
            ->map(fn ($row) => [
                'label' => Carbon::parse($row->snapshot_date, 'Africa/Harare')->format('D'),
                'value' => (int) $row->total,
            ])
            ->values();

        // ── 14-day offline vehicle trend (bar chart) ───────────────────────────
        $offlineTrend = AfisNotification::whereIn('type', [
                'vehicle.immediately_offline',
                'vehicle.extended_offline',
                'vehicle.critically_offline',
            ])
            ->where('created_at', '>=', $now->copy()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(DISTINCT data->>"$.tracker_id") as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'label' => Carbon::parse($row->day, 'Africa/Harare')->format('D'),
                'value' => (int) $row->count,
            ])
            ->values();

        return view('admmdashboard::livewire.dashboard', compact(
            'totalClients',
            'totalDevices', 'devicesWithClient', 'devicesInStock', 'devicesLost',
            'totalSims', 'simsWithClient', 'simsInStock', 'simsLost',
            'totalTrackers',
            'recentInstallations',
            'recentActivity',
            'clientsDelta', 'devicesDelta', 'simsDelta', 'lostDelta',
            'deviceTrend', 'offlineTrend',
        ));
    }

    /**
     * Sum snapshot totals for a given date (excludes MISCELLANEOUS parent_client).
     * Returns zeros if no snapshot exists for that date yet (e.g. early history).
     */
    private function snapshotTotalsForDate(Carbon $date): array
    {
        $row = StockSnapshot::whereDate('snapshot_date', $date->toDateString())
            ->where('parent_client', '!=', 'MISCELLANEOUS')
            ->selectRaw('
                COUNT(DISTINCT parent_client) as clients,
                SUM(devices_with_client) as devices_with_client,
                SUM(sims_with_client) as sims_with_client,
                SUM(devices_lost) + SUM(sims_lost) as lost
            ')
            ->first();

        return [
            'clients'             => (int) ($row->clients ?? 0),
            'devices_with_client' => (int) ($row->devices_with_client ?? 0),
            'sims_with_client'    => (int) ($row->sims_with_client ?? 0),
            'lost'                => (int) ($row->lost ?? 0),
        ];
    }

    private function percentDelta(int $current, int $previous): ?float
    {
        if ($previous <= 0) {
            return null; // not enough snapshot history yet — blade shows "—"
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}