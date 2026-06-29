<?php

namespace Modules\AfisPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisTracker;

class PortalController extends Controller
{
    // ─── Admin routes ─────────────────────────────────────────────────────────

    public function fleetOverview()
    {
        return view('afisportal::admin.fleet-overview');
    }

    public function clientFleet(int $clientId)
    {
        $client = Client::findOrFail($clientId);
        return view('afisportal::admin.client-fleet', compact('client'));
    }

    public function vehicleInspector(int $trackerId)
    {
        $tracker = AfisTracker::with('client')->findOrFail($trackerId);
        return view('afisportal::admin.vehicle-inspector', compact('tracker'));
    }

    public function reports(int $clientId)
    {
        $client = Client::findOrFail($clientId);
        return view('afisportal::admin.reports', compact('client'));
    }

    // ─── Client portal routes ─────────────────────────────────────────────────

    public function clientDashboard()
    {
        $client = $this->resolveClientFromAuth();
        return view('afisportal::client.dashboard', compact('client'));
    }

    public function clientVehicleInspector(int $trackerId)
    {
        $client  = $this->resolveClientFromAuth();
        $tracker = AfisTracker::where('id', $trackerId)
            ->where('client_id', $client->id) // enforce ownership
            ->with('client')
            ->firstOrFail();

        return view('afisportal::client.vehicle-inspector', compact('tracker'));
    }

    public function clientReports()
    {
        $client = $this->resolveClientFromAuth();
        return view('afisportal::client.reports', compact('client'));
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    private function resolveClientFromAuth(): Client
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Link via security_group_id + instance — most precise
        if ($user->navixy_security_group_id && $user->navixy_instance) {
            $client = Client::where('navixy_security_group_id', $user->navixy_security_group_id)
                ->where('navixy_instance', $user->navixy_instance)
                ->first();

            if ($client) return $client;
        }

        abort(403, 'Your account is not linked to a client fleet. Contact Bantu Track support.');
    }
}