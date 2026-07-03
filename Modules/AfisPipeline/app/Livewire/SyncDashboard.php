<?php

namespace Modules\AfisPipeline\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Artisan;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Jobs\SyncClientFleetJob;
use Modules\AfisPipeline\Models\AfisDeviceAlert;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisTrackerGroup;
use Modules\AfisPipeline\Models\AfisSyncLog;
use Modules\AfisPipeline\Models\AfisTrip;

class SyncDashboard extends Component
{
    use WithPagination;

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

    public function syncGroups(): void
    {
        Artisan::call('afis:sync-groups');
        $this->message = 'Tracker groups synced from both Navixy instances.';
    }

    public function render()
    {
        $clients = Client::active()->orderBy('name')->get()->map(function ($client) {
            $client->tracker_count = AfisTracker::where('client_id', $client->id)->count();
            return $client;
        });

        return view('afispipeline::livewire.sync-dashboard', [
            'clients'        => $clients,
            'recentLogs'     => AfisSyncLog::with('client')->latest('created_at')->paginate(50),
            'trackerCount'   => AfisTracker::count(),
            'totalTrips'     => AfisTrip::count(),
            'totalEvents'    => AfisDeviceAlert::count(),
            'trackerGroups'  => AfisTrackerGroup::with('client')->orderBy('title')->get(),
            'unmappedGroups' => AfisTrackerGroup::whereNull('client_id')->count(),
        ]);
    }
}