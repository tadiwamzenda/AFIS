<?php

namespace Modules\AfisIncidents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisIncidents\Models\AfisIncident;

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
    
    public function adminDownload(AfisIncident $incident)
    {
        abort_unless($incident->report_path, 404);
        abort_unless(\Illuminate\Support\Facades\Storage::disk('local')->exists($incident->report_path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->download(
            $incident->report_path,
            "Incident_Report_{$incident->vehicle_label}_{$incident->id}.docx"
        );
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