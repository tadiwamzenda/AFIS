<?php

namespace Modules\AfisPortal\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisDeviceAlert;
use Modules\AfisPipeline\Models\AfisTrackerGroup;

class FleetStateDashboard extends Component
{
    public int    $clientId;
    public string $filter = 'all';
    public string $search = '';

    public function mount(int $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    private function buildVehicleStates(): array
    {
        // ── Get all trackers for client in one query ──────────────────────────
        $trackers = AfisTracker::where('client_id', $this->clientId)
            ->when($this->search, fn($q) => $q->where('label', 'like', "%{$this->search}%"))
            ->orderBy('label')
            ->get();

        if ($trackers->isEmpty()) return [];

        $trackerIds    = $trackers->pluck('id')->toArray();
        $trackerDbIds  = $trackers->keyBy('id');

        // ── Get last online/offline event per tracker in ONE query ────────────
        $lastEvents = AfisDeviceAlert::whereIn('tracker_id', $trackerIds)
            ->whereIn('event_type', ['online', 'offline'])
            ->select('tracker_id', 'event_type', 'occurred_at')
            ->orderBy('occurred_at', 'desc')
            ->get()
            ->groupBy('tracker_id')
            ->map(fn($events) => $events->first()); // latest event per tracker

        // ── Get all tracker groups in ONE query ───────────────────────────────
        $groupIds   = $trackers->pluck('navixy_group_id')->filter()->unique()->toArray();
        $groups     = AfisTrackerGroup::whereIn('navixy_group_id', $groupIds)
            ->pluck('title', 'navixy_group_id');

        $now      = Carbon::now();
        $vehicles = [];

        foreach ($trackers as $tracker) {
            $lastEvent = $lastEvents->get($tracker->id);
            $status    = 'offline';
            $since     = null;
            $duration  = '—';

            if ($lastEvent) {
                $status   = $lastEvent->event_type === 'online' ? 'online' : 'offline';
                $since    = Carbon::parse($lastEvent->occurred_at);
                $seconds  = abs($now->diffInSeconds($since));
                $duration = $this->formatDuration($seconds);
            } elseif ($tracker->last_synced_at) {
                $since    = Carbon::parse($tracker->last_synced_at);
                $seconds  = abs($now->diffInSeconds($since));
                $duration = $this->formatDuration($seconds);
            }

            $vehicles[] = [
                'id'       => $tracker->id,
                'label'    => $tracker->label,
                'group'    => $groups[$tracker->navixy_group_id] ?? '—',
                'status'   => $status,
                'since'    => $since?->format('d M Y H:i'),
                'duration' => $duration,
                'seconds'  => isset($seconds) ? $seconds : 0,
            ];
        }

        // Apply filter
        if ($this->filter !== 'all') {
            $vehicles = array_values(array_filter($vehicles, fn($v) => $v['status'] === $this->filter));
        }

        // Sort: offline first, then longest duration first
        usort($vehicles, function($a, $b) {
            if ($a['status'] !== $b['status']) {
                return $a['status'] === 'offline' ? -1 : 1;
            }
            return $b['seconds'] - $a['seconds'];
        });

        return $vehicles;
    }

    private function formatDuration(int $seconds): string
    {
        $seconds = abs($seconds);
        if ($seconds < 60)    return "{$seconds}s";
        if ($seconds < 3600)  return floor($seconds / 60) . 'm';
        if ($seconds < 86400) return floor($seconds / 3600) . 'h ' . (int)floor(($seconds % 3600) / 60) . 'm';
        $days  = (int)floor($seconds / 86400);
        $hours = (int)floor(($seconds % 86400) / 3600);
        return "{$days}d {$hours}h";
    }

    public function render()
    {
        // Get all states for summary counts (no filter)
        $allTrackers  = AfisTracker::where('client_id', $this->clientId)->get();
        $allIds       = $allTrackers->pluck('id')->toArray();
        $totalCount   = count($allIds);

        $lastEvents = AfisDeviceAlert::whereIn('tracker_id', $allIds)
            ->whereIn('event_type', ['online', 'offline'])
            ->select('tracker_id', 'event_type', 'occurred_at')
            ->orderBy('occurred_at', 'desc')
            ->get()
            ->groupBy('tracker_id')
            ->map(fn($e) => $e->first());

        $onlineCount  = 0;
        $offlineCount = 0;
        foreach ($allTrackers as $t) {
            $e = $lastEvents->get($t->id);
            $status = $e ? ($e->event_type === 'online' ? 'online' : 'offline') : 'offline';
            if ($status === 'online') $onlineCount++;
            else $offlineCount++;
        }

        $vehicles = $this->buildVehicleStates();

        return view('afisportal::livewire.fleet-state-dashboard.index', compact(
            'vehicles', 'totalCount', 'onlineCount', 'offlineCount'
        ));
    }
}