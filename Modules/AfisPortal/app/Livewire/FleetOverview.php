<?php

namespace Modules\AfisPortal\Livewire;

use Livewire\Component;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisTrip;
use Modules\AfisPipeline\Models\AfisEvent;
use Modules\AfisEngine\Models\AfisAiReport;

class FleetOverview extends Component
{
    public string $search = '';

    public function render()
    {
        $clients = Client::active()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->withCount(['gpsDevices'])
            ->orderBy('name')
            ->get()
            ->map(function ($client) {
                $trackers = AfisTracker::where('client_id', $client->id)->get();
                $client->tracker_count  = $trackers->count();
                $client->active_trackers = $trackers->where('is_active', true)->count();
                $client->last_report    = AfisAiReport::where('client_id', $client->id)
                    ->completed()
                    ->latest()
                    ->first();
                return $client;
            });

        $totals = [
            'clients'  => Client::active()->count(),
            // client_id = 21 is MISCELLANEOUS (ghost/unmapped trackers) — excluded
            // fleet-wide, same convention used in every report in this codebase.
            'trackers' => AfisTracker::where('client_id', '!=', 21)->count(),
            'trips'    => AfisTrip::where('client_id', '!=', 21)->count(),
            // AfisEvent is an empty/unused table (confirmed: 0 rows). The real
            // event data lives in AfisDeviceAlert, same table every report and
            // FleetStateDashboard already use.
            'events'   => \Modules\AfisPipeline\Models\AfisDeviceAlert::count(),
            'reports'  => AfisAiReport::completed()->count(),
        ];
        return view('afisportal::livewire.fleet-overview', compact('clients', 'totals'));
    }
}