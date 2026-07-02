<?php

namespace Modules\AfisPortal\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisEngine\Jobs\GenerateAiReportJob;
use Modules\AfisEngine\Models\AfisAiReport;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisTrip;
use Modules\AfisPipeline\Models\AfisEvent;
use Modules\AfisPipeline\Models\AfisSyncLog;

class ClientFleetDashboard extends Component
{
    public int    $clientId;
    public string $message    = '';
    public bool   $generating = false;
    public bool   $syncing    = false;

    public function mount(int $clientId): void
    {
        $this->clientId = $clientId;
        $this->discoverTrackers();
    }

    /**
     * On first visit (or after 15 min), use the client's own Navixy session hash
     * to discover their fleet automatically — no manual linking needed.
     */
    private function discoverTrackers(): void
    {
        $hash = session('navixy_hash');
        if (!$hash) return;

        // Only re-discover if no trackers exist or last sync > 15 min ago
        $trackerCount = AfisTracker::where('client_id', $this->clientId)->count();
        $lastSync     = AfisSyncLog::where('client_id', $this->clientId)
            ->where('status', 'completed')
            ->latest('created_at')
            ->first();

        $needsSync = $trackerCount === 0 ||
            !$lastSync ||
            $lastSync->created_at->diffInMinutes(now()) > 15;

        if (!$needsSync) return;

        try {
            $baseUrl  = rtrim(config('auth-module.navixy_base_url', 'https://api.us.navixy.com/v2'), '/');
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$baseUrl}/tracker/list", ['hash' => $hash]);

            $data     = $response->json();
            $trackers = $data['list'] ?? [];

            if (empty($trackers)) return;

            foreach ($trackers as $t) {
                $imei = $t['source']['device_id'] ?? null;

                $tracker = AfisTracker::updateOrCreate(
                    ['navixy_tracker_id' => $t['id']],
                    [
                        'client_id'      => $this->clientId,
                        'label'          => $t['label'] ?? 'Unknown',
                        'model_name'     => $t['source']['model'] ?? null,
                        'imei'           => $imei,
                        'is_active'      => !($t['source']['blocked'] ?? false),
                        'last_synced_at' => now(),
                    ]
                );

                // Auto-link to ADMM GPS device by IMEI — shows in device listing
                if ($imei) {
                    \Modules\AdmmInventory\Models\GpsDevice::where('imei', $imei)
                        ->where('status', '!=', 'decommissioned')
                        ->update(['status' => 'installed']);
                }
            }

            AfisSyncLog::create([
                'client_id'       => $this->clientId,
                'status'          => 'completed',
                'trackers_synced' => count($trackers),
                'trips_synced'    => 0,
                'events_synced'   => 0,
                'started_at'      => now(),
                'completed_at'    => now(),
            ]);

        } catch (\Throwable $e) {
            Log::warning('ClientFleetDashboard: tracker discovery failed', ['error' => $e->getMessage()]);
        }
    }


    public function render()
    {
        $client   = Client::findOrFail($this->clientId);
        $trackers = AfisTracker::where('client_id', $this->clientId)
            ->orderBy('label')
            ->get()
            ->map(function ($tracker) {
                $last30Days = Carbon::now()->subDays(30);
                $tracker->trip_count  = AfisTrip::where('tracker_id', $tracker->id)->where('start_time', '>=', $last30Days)->count();
                $tracker->event_count = AfisEvent::where('tracker_id', $tracker->id)->where('occurred_at', '>=', $last30Days)->count();
                $tracker->max_speed   = AfisTrip::where('tracker_id', $tracker->id)->where('start_time', '>=', $last30Days)->max('max_speed_kmh');
                $tracker->total_km    = round(AfisTrip::where('tracker_id', $tracker->id)->where('start_time', '>=', $last30Days)->sum('distance_km'), 1);
                $tracker->last_report = AfisAiReport::where('tracker_id', $tracker->id)->completed()->latest()->first();
                return $tracker;
            });

        $recentReports = AfisAiReport::where('client_id', $this->clientId)
            ->completed()
            ->latest()
            ->limit(5)
            ->get();

        $stats = [
            'total_vehicles'  => $trackers->count(),
            'active_vehicles' => $trackers->where('is_active', true)->count(),
            'total_trips'     => $trackers->sum('trip_count'),
            'total_km'        => $trackers->sum('total_km'),
            'total_events'    => $trackers->sum('event_count'),
            'max_speed'       => $trackers->max('max_speed'),
        ];

        return view('afisportal::livewire.client-fleet-dashboard', compact(
            'client', 'trackers', 'recentReports', 'stats'
        ));
    }
}