<?php

namespace Modules\AfisIntelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Modules\AdmmInventory\Models\Client;
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
                return $client;
            });

        return view('afisintelligence::admin.index', compact('clients'));
    }

    public function dashboard(int $clientId)
    {
        $client = Client::findOrFail($clientId);
        return view('afisintelligence::admin.dashboard', compact('client'));
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

        if (!$user->navixy_security_group_id || !$user->navixy_instance) {
            abort(403, 'Your account is not linked to a client fleet. Contact Bantu Track support.');
        }

        // Check primary client security_group_id
        $client = Client::where('navixy_security_group_id', $user->navixy_security_group_id)
            ->where('navixy_instance', $user->navixy_instance)
            ->first();

        if ($client) return $client;

        // Check extended security groups table (for ZETDC multi-group etc)
        $extended = \Modules\AdmmInventory\Models\ClientSecurityGroup::where('navixy_security_group_id', $user->navixy_security_group_id)
            ->where('navixy_instance', $user->navixy_instance)
            ->first();

        if ($extended) return $extended->client;

        abort(403, 'Your account is not linked to a client fleet. Contact Bantu Track support.');
    }
}