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
        $client = $this->resolveClient();
        return view('afisincidents::client.log-incident', compact('client'));
    }

    public function clientArchive()
    {
        $client = $this->resolveClient();
        return view('afisincidents::client.archive', compact('client'));
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