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
    public string $search     = '';

    private function discoverTrackers(): void
{
    // Tracker discovery is handled exclusively by PipelineSyncService (afis:sync-fleet)
    // which uses group-based client mapping to correctly assign client_id.
    // This method previously called tracker/list and overwrote client_id on ALL
    // instance trackers — causing cross-client contamination. Now removed.
    return;
}


    public function render()
    {
        $client   = Client::findOrFail($this->clientId);
        $trackers = AfisTracker::where('client_id', $this->clientId)
            ->when($this->search, fn($q) => $q->where('label', 'like', "%{$this->search}%"))
            ->orderBy('label')
            ->get()
            ->map(function ($tracker) {
                $last30Days = Carbon::now()->subDays(30);
                $tracker->trip_count  = AfisTrip::where('tracker_id', $tracker->id)->where('start_time', '>=', $last30Days)->count();
                $tracker->event_count = \Modules\AfisPipeline\Models\AfisDeviceAlert::where('tracker_id', $tracker->id)->where('occurred_at', '>=', $last30Days)->count();
                // >=195 km/h is a known GPS/sensor error, excluded fleet-wide —
                // same rule already applied in every report this session.
                $tracker->max_speed   = AfisTrip::where('tracker_id', $tracker->id)->where('start_time', '>=', $last30Days)->where('max_speed_kmh', '<', 195)->max('max_speed_kmh');
                $tracker->total_km    = round(AfisTrip::where('tracker_id', $tracker->id)->where('start_time', '>=', $last30Days)->sum('distance_km'), 1);
                $tracker->last_report = AfisAiReport::where('tracker_id', $tracker->id)->completed()->latest()->first();

                // ── Risk score (0-10) — ported from AfisIntelligence's
                // IntelligenceDashboard, with the events component switched
                // from AfisEvent (confirmed empty, 0 rows fleet-wide) to
                // AfisDeviceAlert (the real, populated table). Reuses
                // max_speed/event_count/trip_count already computed above —
                // same 30-day window, no duplicate queries.
                $riskScore = 0;

                if ($tracker->max_speed > 120) $riskScore += 3;
                elseif ($tracker->max_speed > 100) $riskScore += 2;
                elseif ($tracker->max_speed > 80) $riskScore += 1;

                if ($tracker->event_count > 10) $riskScore += 2;
                elseif ($tracker->event_count > 5) $riskScore += 1;

                if ($tracker->trip_count > 50) $riskScore += 1;

                $tracker->risk_score = min($riskScore, 10);
                $tracker->risk_level = $tracker->risk_score >= 5 ? 'high' : ($tracker->risk_score >= 3 ? 'medium' : 'low');

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