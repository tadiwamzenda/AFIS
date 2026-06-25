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

        $trips  = AfisTrip::where('tracker_id', $this->trackerId)
            ->where('start_time', '>=', $from)
            ->orderByDesc('start_time')
            ->limit(50)
            ->get();

        $events = AfisEvent::where('tracker_id', $this->trackerId)
            ->where('occurred_at', '>=', $from)
            ->orderByDesc('occurred_at')
            ->limit(50)
            ->get();

        $stats = [
            'total_trips'       => $trips->count(),
            'total_distance_km' => round($trips->sum('distance_km'), 1),
            'avg_speed_kmh'     => round($trips->avg('avg_speed_kmh'), 1),
            'max_speed_kmh'     => round($trips->max('max_speed_kmh'), 1),
            'total_hours'       => round($trips->sum('duration_minutes') / 60, 1),
            'total_events'      => $events->count(),
            'event_types'       => $events->groupBy('event_type')->map->count(),
        ];

        $reports = AfisAiReport::where('tracker_id', $this->trackerId)
            ->completed()
            ->latest()
            ->limit(10)
            ->get();

        return view('afisportal::livewire.vehicle-inspector', compact(
            'tracker', 'trips', 'events', 'stats', 'reports'
        ));
    }
}