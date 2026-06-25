<?php

namespace Modules\AfisPortal\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisEngine\Jobs\GenerateAiReportJob;
use Modules\AfisEngine\Models\AfisAiReport;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisTrip;
use Modules\AfisPipeline\Models\AfisEvent;

class ClientFleetDashboard extends Component
{
    public int    $clientId;
    public string $message   = '';
    public bool   $generating = false;

    public function mount(int $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function generateFleetReport(): void
    {
        $this->generating = true;
        $this->message    = '';

        GenerateAiReportJob::dispatch(
            reportType: 'fleet_intelligence',
            clientId:   $this->clientId,
        );

        $this->message    = 'Fleet intelligence report is being generated. Refresh in 30-60 seconds to see it.';
        $this->generating = false;
    }

    public function generatePredictiveReport(): void
    {
        $this->generating = true;

        GenerateAiReportJob::dispatch(
            reportType: 'predictive_intelligence',
            clientId:   $this->clientId,
            options:    ['days' => 90],
        );

        $this->message    = 'Predictive intelligence report is being generated. Refresh in 30-60 seconds.';
        $this->generating = false;
    }

    public function render()
    {
        $client   = Client::findOrFail($this->clientId);
        $trackers = AfisTracker::where('client_id', $this->clientId)
            ->orderBy('label')
            ->get()
            ->map(function ($tracker) {
                $last30Days = Carbon::now()->subDays(30);
                $tracker->trip_count    = AfisTrip::where('tracker_id', $tracker->id)->where('start_time', '>=', $last30Days)->count();
                $tracker->event_count   = AfisEvent::where('tracker_id', $tracker->id)->where('occurred_at', '>=', $last30Days)->count();
                $tracker->max_speed     = AfisTrip::where('tracker_id', $tracker->id)->where('start_time', '>=', $last30Days)->max('max_speed_kmh');
                $tracker->total_km      = round(AfisTrip::where('tracker_id', $tracker->id)->where('start_time', '>=', $last30Days)->sum('distance_km'), 1);
                $tracker->last_report   = AfisAiReport::where('tracker_id', $tracker->id)->completed()->latest()->first();
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