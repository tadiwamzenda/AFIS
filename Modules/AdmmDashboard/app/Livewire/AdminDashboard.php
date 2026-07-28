<?php

namespace Modules\AdmmDashboard\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Modules\AdmmInventory\Models\AssetRecord;
use Modules\AdmmInventory\Models\Client;
use Modules\AuditLog\Models\AuditLog;

class AdminDashboard extends Component
{
    public function render()
    {
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
        $totalTrackers  = \Modules\AfisPipeline\Models\AfisTracker::where('client_id', '!=', 21)->count();
        $activeClients  = \Modules\AfisPipeline\Models\AfisTracker::where('client_id', '!=', 21)
            ->distinct('client_id')->count('client_id');

        // ── Recent installations (last 10 added to asset register) ───────────
        $recentInstallations = AssetRecord::whereNotNull('installation_date')
            ->orderByDesc('installation_date')
            ->limit(5)
            ->get();

        // ── Recent activity (audit log) ───────────────────────────────────────
        $recentActivity = AuditLog::with('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('admmdashboard::livewire.dashboard', compact(
            'totalClients',
            'totalDevices', 'devicesWithClient', 'devicesInStock', 'devicesLost',
            'totalSims', 'simsWithClient', 'simsInStock', 'simsLost',
            'totalTrackers',
            'recentInstallations',
            'recentActivity',
        ));
    }
}