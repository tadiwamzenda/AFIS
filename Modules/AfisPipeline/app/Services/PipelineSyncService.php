<?php

namespace Modules\AfisPipeline\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\GpsDevice;
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
            // Get all Navixy trackers
            $navixyTrackers = $this->navixy->getTrackers();

            // Get navixy_tracker_ids assigned to this client
            $clientTrackerIds = GpsDevice::where('client_id', $client->id)
                ->whereNotNull('navixy_tracker_id')
                ->pluck('navixy_tracker_id')
                ->toArray();

            // Filter trackers to this client's devices
            $clientTrackers = collect($navixyTrackers)
                ->filter(fn($t) => in_array($t['id'], $clientTrackerIds));

            if ($clientTrackers->isEmpty()) {
                $log->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                    'error_message' => 'No trackers found for this client.',
                ]);
                return $log;
            }

            // Get last GPS points
            $lastPoints = collect($this->navixy->getLastGpsPoints($clientTrackers->pluck('id')->toArray()))
                ->keyBy('tracker_id');

            $trackersSynced = 0;
            $tripsSynced    = 0;
            $eventsSynced   = 0;

            $from = now()->subHours(168);
            $to   = now();

            foreach ($clientTrackers as $navixyTracker) {
                $lastPoint = $lastPoints->get($navixyTracker['id']);

                // Upsert tracker record
                $tracker = AfisTracker::updateOrCreate(
                    ['navixy_tracker_id' => $navixyTracker['id']],
                    [
                        'client_id'            => $client->id,
                        'label'                => $navixyTracker['label'] ?? 'Unknown',
                        'model_name'           => $navixyTracker['source']['model'] ?? null,
                        'is_active'            => ($navixyTracker['status']['identification'] ?? '') !== 'blocked',
                        'last_active_at'       => isset($navixyTracker['last_connection'])
                            ? Carbon::parse($navixyTracker['last_connection']) : null,
                        'last_lat'             => $lastPoint['gps']['lat'] ?? null,
                        'last_lng'             => $lastPoint['gps']['lng'] ?? null,
                        'last_synced_at'       => now(),
                    ]
                );
                $trackersSynced++;

                // Sync trips
                
                $trips = $this->navixy->getTrips($navixyTracker['id'], $from, $to);

                foreach ($trips as $trip) {
                    // Skip single GPS point reports — not real trips
                    if (($trip['type'] ?? '') === 'single_report') {
                        continue;
                    }

                    // Skip trips with no end date
                    if (empty($trip['end_date'])) {
                        continue;
                    }

                    $startTime = Carbon::parse($trip['start_date']);
                    $endTime   = Carbon::parse($trip['end_date']);
                    $durationMinutes = (int) $startTime->diffInMinutes($endTime);

                    AfisTrip::updateOrCreate(
                        [
                            'tracker_id'        => $tracker->id,
                            'navixy_tracker_id' => $navixyTracker['id'],
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
                $events = $this->navixy->getEvents($navixyTracker['id'], $from, $to);
                foreach ($events as $event) {
                    AfisEvent::firstOrCreate(
                        [
                            'tracker_id'        => $tracker->id,
                            'navixy_tracker_id' => $navixyTracker['id'],
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
                'trackers_synced' => $trackersSynced,
                'trips_synced'    => $tripsSynced,
                'events_synced'   => $eventsSynced,
                'completed_at'    => now(),
            ]);

        } catch (\Throwable $e) {
            Log::error("AfisPipeline: sync failed for client {$client->name}", ['error' => $e->getMessage()]);
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);
        }

        return $log;
    }
}