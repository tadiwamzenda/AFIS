<?php

namespace Modules\AfisPipeline\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisEvent;
use Modules\AfisPipeline\Models\AfisSyncLog;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisTrip;

class PipelineSyncService
{
    public function __construct(private NavixyDataService $navixy) {}

    public function syncClient(Client $client): AfisSyncLog
    {
        $log = AfisSyncLog::create([
            'client_id'  => $client->id,
            'status'     => 'running',
            'started_at' => now(),
        ]);

        try {
            $instance = $client->navixy_instance ?? 1;

            // Use trackers already discovered via client login
            $knownTrackers = AfisTracker::where('client_id', $client->id)->get();

            if ($knownTrackers->isEmpty()) {
                $log->update([
                    'status'        => 'completed',
                    'completed_at'  => now(),
                    'error_message' => 'No trackers found. Client must log in first to discover their fleet.',
                ]);
                return $log;
            }

            $from         = now()->subHours(24);
            $to           = now();
            $tripsSynced  = 0;
            $eventsSynced = 0;

            foreach ($knownTrackers as $tracker) {
                // Sync trips
                $trips = $this->navixy->getTrips($tracker->navixy_tracker_id, $from, $to, $instance);
                foreach ($trips as $trip) {
                    if (($trip['type'] ?? '') === 'single_report') continue;
                    if (empty($trip['end_date'])) continue;

                    $startTime       = Carbon::parse($trip['start_date']);
                    $endTime         = Carbon::parse($trip['end_date']);
                    $durationMinutes = (int) $startTime->diffInMinutes($endTime);

                    AfisTrip::firstOrCreate(
                        [
                            'tracker_id'        => $tracker->id,
                            'navixy_tracker_id' => $tracker->navixy_tracker_id,
                            'start_time'        => $startTime,
                        ],
                        [
                            'client_id'        => $client->id,
                            'end_time'         => $endTime,
                            'distance_km'      => $trip['length'] ?? 0,
                            'avg_speed_kmh'    => $trip['avg_speed'] ?? 0,
                            'max_speed_kmh'    => $trip['max_speed'] ?? 0,
                            'duration_minutes' => $durationMinutes,
                        ]
                    );
                    $tripsSynced++;
                }

                // Sync events
                $events = $this->navixy->getEvents($tracker->navixy_tracker_id, $from, $to, $instance);
                foreach ($events as $event) {
                    AfisEvent::firstOrCreate(
                        [
                            'tracker_id'        => $tracker->id,
                            'navixy_tracker_id' => $tracker->navixy_tracker_id,
                            'event_type'        => $event['event_id'] ?? 'unknown',
                            'occurred_at'       => Carbon::parse($event['time'] ?? now()),
                        ],
                        [
                            'client_id'  => $client->id,
                            'lat'        => $event['location']['lat'] ?? null,
                            'lng'        => $event['location']['lng'] ?? null,
                            'extra_data' => $event,
                        ]
                    );
                    $eventsSynced++;
                }
            }

            $log->update([
                'status'          => 'completed',
                'trackers_synced' => $knownTrackers->count(),
                'trips_synced'    => $tripsSynced,
                'events_synced'   => $eventsSynced,
                'completed_at'    => now(),
            ]);

        } catch (\Throwable $e) {
            Log::error("AfisPipeline: sync failed for {$client->name}", ['error' => $e->getMessage()]);
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);
        }

        return $log;
    }
}