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
        $client = $this->resolveClient();
        return view('afisintelligence::client.dashboard', compact('client'));
    }

    public function clientArchive()
    {
        $client = $this->resolveClient();
        return view('afisintelligence::client.archive', compact('client'));
    }

    private function resolveClient(): Client
    {
        /** @var User $user */
        $user   = Auth::user();
        $client = Client::where('navixy_account_id', $user->navixy_account_id)->first();

        if (!$client) {
            abort(403, 'Your account is not linked to a client. Contact Bantu Track support.');
        }

        return $client;
    }
}