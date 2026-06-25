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
            'trackers' => AfisTracker::count(),
            'trips'    => AfisTrip::count(),
            'events'   => AfisEvent::count(),
            'reports'  => AfisAiReport::completed()->count(),
        ];

        return view('afisportal::livewire.fleet-overview', compact('clients', 'totals'));
    }
}