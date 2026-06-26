<?php

namespace Modules\AfisIntelligence\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisEngine\Jobs\GenerateAiReportJob;
use Modules\AfisEngine\Models\AfisAiReport;
use Modules\AfisPipeline\Models\AfisEvent;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisTrip;

class IntelligenceDashboard extends Component
{
    public int    $clientId;
    public string $message    = '';
    public bool   $generating = false;

    public function mount(int $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function generatePredictive(): void
    {
        $this->generating = true;
        $this->message    = '';

        GenerateAiReportJob::dispatch(
            reportType: 'predictive_intelligence',
            clientId:   $this->clientId,
            options:    ['days' => 90, 'use_cache' => false]
        );

        $this->message    = 'Predictive intelligence report queued. Available in 30-60 seconds.';
        $this->generating = false;
    }

    public function generateFleetIntelligence(): void
    {
        $this->generating = true;

        GenerateAiReportJob::dispatch(
            reportType: 'fleet_intelligence',
            clientId:   $this->clientId,
            options:    ['days' => 30, 'use_cache' => false]
        );

        $this->message    = 'Fleet intelligence report queued. Available in 30-60 seconds.';
        $this->generating = false;
    }

    public function render()
    {
        $client   = Client::findOrFail($this->clientId);
        $trackers = AfisTracker::where('client_id', $this->clientId)->get();
        $from90   = Carbon::now()->subDays(90);
        $from30   = Carbon::now()->subDays(30);

        // Fleet risk metrics
        $fleetMetrics = [
            'total_vehicles'    => $trackers->count(),
            'active_vehicles'   => $trackers->where('is_active', true)->count(),
            'total_trips_30d'   => AfisTrip::whereIn('tracker_id', $trackers->pluck('id'))
                ->where('start_time', '>=', $from30)->count(),
            'total_km_30d'      => round(AfisTrip::whereIn('tracker_id', $trackers->pluck('id'))
                ->where('start_time', '>=', $from30)->sum('distance_km'), 1),
            'max_speed_30d'     => round(AfisTrip::whereIn('tracker_id', $trackers->pluck('id'))
                ->where('start_time', '>=', $from30)->max('max_speed_kmh'), 1),
            'total_events_30d'  => AfisEvent::whereIn('tracker_id', $trackers->pluck('id'))
                ->where('occurred_at', '>=', $from30)->count(),
            'total_trips_90d'   => AfisTrip::whereIn('tracker_id', $trackers->pluck('id'))
                ->where('start_time', '>=', $from90)->count(),
            'total_km_90d'      => round(AfisTrip::whereIn('tracker_id', $trackers->pluck('id'))
                ->where('start_time', '>=', $from90)->sum('distance_km'), 1),
        ];

        // Per-vehicle risk indicators
        $vehicleRiskData = $trackers->map(function ($tracker) use ($from30) {
            $trips  = AfisTrip::where('tracker_id', $tracker->id)->where('start_time', '>=', $from30)->get();
            $events = AfisEvent::where('tracker_id', $tracker->id)->where('occurred_at', '>=', $from30)->count();

            $maxSpeed   = $trips->max('max_speed_kmh') ?? 0;
            $tripCount  = $trips->count();
            $totalKm    = round($trips->sum('distance_km'), 1);

            // Simple risk score calculation
            $riskScore = 0;
            if ($maxSpeed > 120) $riskScore += 3;
            elseif ($maxSpeed > 100) $riskScore += 2;
            elseif ($maxSpeed > 80) $riskScore += 1;
            if ($events > 10) $riskScore += 2;
            elseif ($events > 5) $riskScore += 1;
            if ($tripCount > 50) $riskScore += 1;

            return [
                'id'          => $tracker->id,
                'label'       => $tracker->label,
                'is_active'   => $tracker->is_active,
                'trip_count'  => $tripCount,
                'total_km'    => $totalKm,
                'max_speed'   => $maxSpeed,
                'event_count' => $events,
                'risk_score'  => min($riskScore, 10),
                'risk_level'  => $riskScore >= 5 ? 'high' : ($riskScore >= 3 ? 'medium' : 'low'),
            ];
        })->sortByDesc('risk_score')->values();

        // Recent intelligence reports
        $recentReports = AfisAiReport::where('client_id', $this->clientId)
            ->whereIn('report_type', ['fleet_intelligence', 'predictive_intelligence'])
            ->completed()
            ->latest()
            ->limit(5)
            ->get();

        return view('afisintelligence::livewire.intelligence-dashboard', compact(
            'client', 'fleetMetrics', 'vehicleRiskData', 'recentReports'
        ));
    }
}