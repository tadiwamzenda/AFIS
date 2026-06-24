<?php

namespace Modules\AfisPipeline\Livewire;

use Livewire\Component;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Jobs\SyncClientFleetJob;
use Modules\AfisPipeline\Models\AfisSyncLog;
use Modules\AfisPipeline\Models\AfisTracker;

class SyncDashboard extends Component
{
    public string $message = '';

    public function syncAll(): void
    {
        $clients = Client::active()->get();

        if ($clients->isEmpty()) {
            $this->message = 'No active clients found.';
            return;
        }

        foreach ($clients as $client) {
            SyncClientFleetJob::dispatch($client);
        }

        $this->message = "Sync jobs dispatched for {$clients->count()} client(s). Results will appear below shortly.";
    }

    public function syncClient(int $clientId): void
    {
        $client = Client::findOrFail($clientId);
        SyncClientFleetJob::dispatch($client);
        $this->message = "Sync job dispatched for {$client->name}.";
    }

    public function render()
    {
        return view('afispipeline::livewire.sync-dashboard', [
            'clients'      => Client::active()->withCount(['gpsDevices'])->orderBy('name')->get(),
            'recentLogs'   => AfisSyncLog::with('client')->latest('created_at')->limit(20)->get(),
            'trackerCount' => AfisTracker::count(),
            'totalTrips'   => \Modules\AfisPipeline\Models\AfisTrip::count(),
            'totalEvents'  => \Modules\AfisPipeline\Models\AfisEvent::count(),
        ]);
    }
}