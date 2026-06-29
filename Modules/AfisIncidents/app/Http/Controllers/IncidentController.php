<?php

namespace Modules\AfisIncidents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Modules\AdmmInventory\Models\Client;

class IncidentController extends Controller
{
    // ─── Admin routes ─────────────────────────────────────────────────────────

    public function adminLog(int $clientId)
    {
        $client = Client::findOrFail($clientId);
        return view('afisincidents::admin.log-incident', compact('client'));
    }

    public function adminArchive(int $clientId)
    {
        $client = Client::findOrFail($clientId);
        return view('afisincidents::admin.archive', compact('client'));
    }

    public function adminIndex()
    {
        $clients = \Modules\AdmmInventory\Models\Client::active()->orderBy('name')->get();
        return view('afisincidents::admin.index', compact('clients'));
    }

    // ─── Client portal routes ─────────────────────────────────────────────────

    public function clientLog()
    {
        $client = $this->resolveClientFromAuth();
        return view('afisincidents::client.log-incident', compact('client'));
    }

    public function clientArchive()
    {
        $client = $this->resolveClientFromAuth();
        return view('afisincidents::client.archive', compact('client'));
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