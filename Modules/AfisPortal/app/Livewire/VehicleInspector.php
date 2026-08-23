<?php

namespace Modules\AfisPortal\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Modules\AfisEngine\Jobs\GenerateAiReportJob;
use Modules\AfisEngine\Models\AfisAiReport;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisTrip;
use Modules\AfisPipeline\Models\AfisEvent;

class VehicleInspector extends Component
{
    public int    $trackerId;
    public int    $days       = 30;
    public string $activeTab  = 'overview';
    public string $message    = '';
    public bool   $generating = false;

    public function mount(int $trackerId): void
    {
        $this->trackerId = $trackerId;
    }

    public function generateBehaviourReport(): void
    {
        $this->generating = true;

        $tracker = AfisTracker::findOrFail($this->trackerId);

        GenerateAiReportJob::dispatch(
            reportType: 'vehicle_behaviour',
            clientId:   $tracker->client_id,
            trackerId:  $this->trackerId,
            options:    ['days' => $this->days, 'use_cache' => false],
        );

        $this->message    = "Vehicle behaviour report is being generated for {$tracker->label}. Refresh in 30-60 seconds.";
        $this->generating = false;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $tracker  = AfisTracker::with('client')->findOrFail($this->trackerId);
        $from     = Carbon::now()->subDays($this->days);

        // ── Stats computed from the FULL period, not the 50-row display cap
        // below — the previous version computed every stat from the same
        // limited collection used to render the trips/events tables, so any
        // vehicle with more than 50 trips/events in the window silently
        // undercounted total_trips, total_distance_km, total_hours, etc.
        $tripsQuery = AfisTrip::where('tracker_id', $this->trackerId)->where('start_time', '>=', $from);

        $trips  = (clone $tripsQuery)->orderByDesc('start_time')->limit(50)->get();

        $events = \Modules\AfisPipeline\Models\AfisDeviceAlert::where('tracker_id', $this->trackerId)
            ->where('occurred_at', '>=', $from)
            ->orderByDesc('occurred_at')
            ->limit(50)
            ->get();

        $eventsCount = \Modules\AfisPipeline\Models\AfisDeviceAlert::where('tracker_id', $this->trackerId)
            ->where('occurred_at', '>=', $from)
            ->count();

        $stats = [
            'total_trips'       => (clone $tripsQuery)->count(),
            'total_distance_km' => round((clone $tripsQuery)->sum('distance_km'), 1),
            'avg_speed_kmh'     => round((clone $tripsQuery)->avg('avg_speed_kmh'), 1),
            // >=195 km/h is a known GPS/sensor error, excluded fleet-wide.
            'max_speed_kmh'     => round((clone $tripsQuery)->where('max_speed_kmh', '<', 195)->max('max_speed_kmh'), 1),
            'total_hours'       => round((clone $tripsQuery)->sum('duration_minutes') / 60, 1),
            'total_events'      => $eventsCount,
            'event_types'       => \Modules\AfisPipeline\Models\AfisDeviceAlert::where('tracker_id', $this->trackerId)
                ->where('occurred_at', '>=', $from)
                ->selectRaw('event_type, COUNT(*) as total')
                ->groupBy('event_type')
                ->pluck('total', 'event_type'),
        ];

        // Include failed reports too — same reasoning as ReportViewer.
        $reports = AfisAiReport::where('tracker_id', $this->trackerId)
            ->whereIn('status', ['completed', 'failed'])
            ->latest()
            ->get();

        return view('afisportal::livewire.vehicle-inspector', compact(
            'tracker', 'trips', 'events', 'stats', 'reports'
        ));
    }
}