<?php

namespace Modules\AdmmDashboard\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Modules\AdmmInventory\Models\Accessory;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\SimCard;
use Modules\AuditLog\Models\AuditLog;

class AdminDashboard extends Component
{
    public function render()
    {
        $thirtyDays = Carbon::now()->addDays(30);

        return view('admmdashboard::livewire.dashboard', [

            // ── Counts ───────────────────────────────────────────────────────
            'totalClients'      => Client::where('is_active', true)->count(),
            'totalSimCards'     => SimCard::count(),
            'totalGpsDevices'   => GpsDevice::count(),
            'totalAccessories'  => Accessory::count(),

            // ── SIM card breakdown ───────────────────────────────────────────
            'simClientAssigned' => SimCard::where('location_context', 'client_assigned')->count(),
            'simInternalStock'  => SimCard::where('location_context', 'internal_stock')->count(),
            'simUnallocated'    => SimCard::where('location_context', 'unallocated')->count(),

            // ── GPS device breakdown ─────────────────────────────────────────
            'devicesInstalled'  => GpsDevice::where('status', 'installed_client')->count(),
            'devicesInStock'    => GpsDevice::where('status', 'in_office_stock')->count(),
            'devicesRepair'     => GpsDevice::where('status', 'under_repair')->count(),

            // ── Alerts ───────────────────────────────────────────────────────
            'simRenewalsDue'    => SimCard::whereNotNull('bundle_renewal_date')
                ->where('bundle_renewal_date', '<=', $thirtyDays)
                ->where('status', '!=', 'deactivated')
                ->with('client')
                ->orderBy('bundle_renewal_date')
                ->limit(10)
                ->get(),

            'warrantyExpiring'  => GpsDevice::whereNotNull('warranty_expiry_date')
                ->where('warranty_expiry_date', '<=', $thirtyDays)
                ->whereNotIn('status', ['decommissioned', 'lost_stolen'])
                ->with('client')
                ->orderBy('warranty_expiry_date')
                ->limit(10)
                ->get(),

            'lostAssets' => [
                'sim_cards'   => SimCard::where('status', 'lost')->count(),
                'gps_devices' => GpsDevice::where('status', 'lost_stolen')->count(),
                'accessories' => Accessory::where('status', 'lost')->count(),
            ],

            // ── Recent activity ──────────────────────────────────────────────
            'recentActivity'    => AuditLog::with('user')
                ->latest('created_at')
                ->limit(15)
                ->get(),
        ]);
    }
}