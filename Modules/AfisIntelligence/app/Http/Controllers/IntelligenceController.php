<?php

namespace Modules\AfisIntelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisEngine\Models\AfisAiReport;
use Modules\AfisPipeline\Models\AfisTracker;

class IntelligenceController extends Controller
{
    // ─── Admin ───────────────────────────────────────────────────────────────

    public function index()
    {
        $clients = Client::active()
            ->orderBy('name')
            ->get()
            ->map(function ($client) {
                $client->tracker_count = AfisTracker::where('client_id', $client->id)->count();
                $client->last_intelligence_report = AfisAiReport::where('client_id', $client->id)
                    ->whereIn('report_type', ['fleet_intelligence', 'predictive_intelligence'])
                    ->completed()
                    ->latest()
                    ->first();
                return $client;
            });

        return view('afisintelligence::admin.index', compact('clients'));
    }

    public function dashboard(int $clientId)
    {
        $client = Client::findOrFail($clientId);
        return view('afisintelligence::admin.dashboard', compact('client'));
    }

    public function archive(int $clientId)
    {
        $client = Client::findOrFail($clientId);
        return view('afisintelligence::admin.archive', compact('client'));
    }

    // ─── Client portal ────────────────────────────────────────────────────────

    public function clientDashboard()
    {
        $client = $this->resolveClientFromAuth();
        return view('afisintelligence::client.dashboard', compact('client'));
    }

    public function clientArchive()
    {
        $client = $this->resolveClientFromAuth();
        return view('afisintelligence::client.archive', compact('client'));
    }

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