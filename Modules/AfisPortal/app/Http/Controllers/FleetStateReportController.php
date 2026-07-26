<?php

namespace Modules\AfisPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisDeviceAlert;
use Modules\AfisPipeline\Models\AfisTrackerGroup;

class FleetStateReportController extends Controller
{
    public function offlineReport(Request $request, int $clientId)
    {
        $client = Client::findOrFail($clientId);
        $from   = Carbon::parse($request->from ?? now()->subDays(30))->startOfDay();
        $to     = Carbon::parse($request->to   ?? now())->endOfDay();

        $trackers   = AfisTracker::where('client_id', $clientId)->orderBy('label')->get();
        $incidents  = [];

        foreach ($trackers as $tracker) {
            // Get all online/offline events in range ordered by time
            $events = AfisDeviceAlert::where('tracker_id', $tracker->id)
                ->whereIn('event_type', ['online', 'offline'])
                ->whereBetween('occurred_at', [$from, $to])
                ->orderBy('occurred_at')
                ->get();

            if ($events->isEmpty()) continue;

            $group = AfisTrackerGroup::where('navixy_group_id', $tracker->navixy_group_id)->first();

            // Pair offline→online events
            $offlineStart = null;
            foreach ($events as $event) {
                if ($event->event_type === 'offline' && !$offlineStart) {
                    $offlineStart = Carbon::parse($event->occurred_at);
                } elseif ($event->event_type === 'online' && $offlineStart) {
                    $onlineAt  = Carbon::parse($event->occurred_at);
                    $duration  = $offlineStart->diffInSeconds($onlineAt);
                    $incidents[] = [
                        'label'        => $tracker->label,
                        'group'        => $group?->title ?? '—',
                        'went_offline' => $offlineStart->format('d M Y H:i'),
                        'came_online'  => $onlineAt->format('d M Y H:i'),
                        'duration_sec' => $duration,
                        'duration'     => $this->formatDuration($duration),
                        'severity'     => $this->severity($duration),
                    ];
                    $offlineStart = null;
                }
            }

            // Still offline at end of period
            if ($offlineStart) {
                $duration    = $offlineStart->diffInSeconds($to);
                $incidents[] = [
                    'label'        => $tracker->label,
                    'group'        => $group?->title ?? '—',
                    'went_offline' => $offlineStart->format('d M Y H:i'),
                    'came_online'  => 'Still offline',
                    'duration_sec' => $duration,
                    'duration'     => $this->formatDuration($duration),
                    'severity'     => $this->severity($duration),
                ];
            }
        }

        // Sort by duration descending (longest offline first)
        usort($incidents, fn($a, $b) => $b['duration_sec'] - $a['duration_sec']);

        $totalIncidents   = count($incidents);
        $stillOffline     = count(array_filter($incidents, fn($i) => $i['came_online'] === 'Still offline'));
        $avgDuration      = $totalIncidents > 0
            ? $this->formatDuration((int)(array_sum(array_column($incidents, 'duration_sec')) / $totalIncidents))
            : '—';
        $longestOffline   = $totalIncidents > 0
            ? $this->formatDuration(max(array_column($incidents, 'duration_sec')))
            : '—';

        return view('afisportal::reports.offline-report', compact(
            'client', 'from', 'to', 'incidents',
            'totalIncidents', 'stillOffline', 'avgDuration', 'longestOffline'
        ));
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60)    return "{$seconds}s";
        if ($seconds < 3600)  return floor($seconds / 60) . 'm';
        if ($seconds < 86400) return floor($seconds / 3600) . 'h ' . floor(($seconds % 3600) / 60) . 'm';
        $days  = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        return "{$days}d {$hours}h";
    }

    private function severity(int $seconds): string
    {
        if ($seconds < 3600)   return 'low';      // < 1 hour
        if ($seconds < 86400)  return 'medium';   // 1–24 hours
        return 'high';                              // > 24 hours
    }
}