<?php

namespace Modules\AfisPipeline\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisSyncLog;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisTrip;
use Modules\AfisPipeline\Models\AfisTrackerGroup;
use Modules\AfisPipeline\Models\AfisMileageDaily;
use Modules\AfisPipeline\Models\AfisDeviceAlert;
use Modules\AfisPipeline\Models\AfisFuelEvent;
use Modules\AfisPipeline\Models\AfisFuelDaily;
use Modules\AfisPipeline\Services\FuelDataParser;

class PipelineSyncService
{
    public function __construct(
        private NavixyDataService $navixy,
        private FuelDataParser $fuelParser,) {}

    public function syncClient(Client $client): AfisSyncLog
    {
        $log = AfisSyncLog::create([
            'client_id'  => $client->id,
            'status'     => 'running',
            'started_at' => now(),
        ]);

        try {
            $instance = $client->navixy_instance ?? 1;

            // ── Step 1: Discover trackers via master account ──────────────────
            // Get client's group IDs from afis_tracker_groups
            $clientGroupIds = AfisTrackerGroup::where('client_id', $client->id)
                ->pluck('navixy_group_id')
                ->toArray();

            if (!empty($clientGroupIds)) {
                // Pull ALL trackers from Navixy and filter by client's groups
                $allNavixyTrackers = $this->navixy->getAllTrackers($instance);

                foreach ($allNavixyTrackers as $t) {
                    if (!in_array($t['group_id'] ?? null, $clientGroupIds)) continue;

                    $imei = $t['source']['device_id'] ?? null;

                    AfisTracker::updateOrCreate(
                        ['navixy_tracker_id' => $t['id']],
                        [
                            'client_id'       => $client->id,
                            'navixy_group_id' => \Modules\AfisPipeline\Models\AfisTrackerGroup::where('navixy_group_id', $t['group_id'] ?? 0)->exists()
                            ? ($t['group_id'] ?? null)
                            : null,
                            'label'           => $t['label'] ?? 'Unknown',
                            'model_name'      => $t['source']['model'] ?? null,
                            'imei'            => $imei,
                            'is_active'       => true,
                            'online_status'   => ($t['status']['identification'] ?? '') === 'active' ? 'online' : 'offline',
                            'last_synced_at'  => now(),
                        ]
                    );
                }
            }

            // ── Step 2: Get all known trackers for this client ────────────────
            $knownTrackers = AfisTracker::where('client_id', $client->id)->get();

            if ($knownTrackers->isEmpty()) {
                $log->update([
                    'status'        => 'completed',
                    'completed_at'  => now(),
                    'error_message' => 'No trackers found. Set navixy_group_prefix on client and run afis:sync-groups first.',
                ]);
                return $log;
            }

            $from        = now()->subHours(24);
            $to          = now();
            $trackerIds  = $knownTrackers->pluck('navixy_tracker_id')->toArray();
            $tripsSynced = 0;
            $alertsSynced = 0;

            // ── Step 3: Sync trips per tracker ────────────────────────────────
            foreach ($knownTrackers as $tracker) {
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
                            'avg_speed_kmh'    => min($trip['avg_speed'] ?? 0, 200),
                            'max_speed_kmh'    => min($trip['max_speed'] ?? 0, 200),
                            'duration_minutes' => $durationMinutes,
                        ]
                    );
                    $tripsSynced++;
                }
            }


            // ── Step 4: Sync daily mileage (batch) ────────────────────────────
            $mileageData = [];
            foreach (array_chunk($trackerIds, 50) as $chunk) {
                $chunkData   = $this->navixy->getDailyMileage($chunk, $from, $to, $instance);
                $mileageData = $mileageData + $chunkData;
                usleep(200000);
            }
            foreach ($knownTrackers as $tracker) {
                $trackerMileage = $mileageData[(string) $tracker->navixy_tracker_id] ?? [];
                foreach ($trackerMileage as $date => $data) {
                    AfisMileageDaily::updateOrCreate(
                        ['tracker_id' => $tracker->id, 'date' => $date],
                        [
                            'client_id'         => $client->id,
                            'navixy_tracker_id' => $tracker->navixy_tracker_id,
                            'mileage_km'        => $data['mileage'] ?? 0,
                        ]
                    );
                }
            }

            // ── Step 5: Sync alerts ───────────────────────────────────────────
            $alerts     = $this->navixy->getAlerts($trackerIds, $from, $to, $instance);
            $trackerMap = $knownTrackers->keyBy('navixy_tracker_id');

            foreach ($alerts as $alert) {
                $trackerId = $alert['tracker_id'] ?? null;
                $tracker   = $trackerMap->get($trackerId);
                if (!$tracker || empty($alert['id'])) continue;

                AfisDeviceAlert::updateOrCreate(
                    ['navixy_alert_id' => $alert['id']],
                    [
                        'tracker_id'        => $tracker->id,
                        'client_id'         => $client->id,
                        'navixy_tracker_id' => $trackerId,
                        'event_type'        => $alert['event'] ?? 'unknown',
                        'message'           => $alert['message'] ?? '',
                        'is_read'           => $alert['is_read'] ?? false,
                        'occurred_at'       => Carbon::parse($alert['time']),
                        'lat'               => $alert['location']['lat'] ?? null,
                        'lng'               => $alert['location']['lng'] ?? null,
                        'address'           => $alert['address'] ?? null,
                        'extra_data'        => $alert['extra'] ?? null,
                    ]
                );
                $alertsSynced++;
                // Extract fuel events separately
                if (in_array($alert['event'] ?? '', ['fueling', 'drain'])) {
                $extra = $alert['extra_data'] ?? $alert['extra'] ?? [];
                \Modules\AfisPipeline\Models\AfisFuelEvent::firstOrCreate(
                    [
                        'tracker_id'        => $tracker->id,
                        'navixy_tracker_id' => $trackerId,
                        'occurred_at'       => Carbon::parse($alert['time'] ?? $alert['occurred_at']),
                        'event_type'        => $alert['event'] ?? $alert['event_type'],
                    ],
                    [
                        'client_id'      => $client->id,
                        'volume_litres'  => isset($extra['sensor_calculated_value'])
                            ? (float) $extra['sensor_calculated_value'] : null,
                        'initial_volume' => null,
                        'final_volume'   => null,
                        'lat'            => $alert['location']['lat'] ?? $alert['lat'] ?? null,
                        'lng'            => $alert['location']['lng'] ?? $alert['lng'] ?? null,
                        'address'        => $alert['address'] ?? null,
                    ]
                );
            }
            }

            // ── Step 6: Fuel data (once per day per client) ───────────────────
            $today = now()->toDateString();
            $fuelAlreadySynced = AfisFuelDaily::where('client_id', $client->id)
                ->where('date', $today)
                ->exists();

            if (!$fuelAlreadySynced) {
                // Only sync fuel for trackers that have fuel sensors (those with fuel events)
                $fuelTrackerIds = \Modules\AfisPipeline\Models\AfisFuelEvent::whereIn('tracker_id', $knownTrackers->pluck('id'))
                    ->distinct()
                    ->pluck('navixy_tracker_id')
                    ->toArray();

                if (!empty($fuelTrackerIds)) {
                    $fuelSynced = $this->fuelParser->syncFuelData(
                        $client,
                        $fuelTrackerIds,
                        now()->startOfDay(),
                        now()->endOfDay(),
                        $instance,
                        $this->navixy
                    );
                    Log::info("PipelineSyncService: fuel synced {$fuelSynced} records for {$client->name}");
                }
            }

            $log->update([
                'status'          => 'completed',
                'trackers_synced' => $knownTrackers->count(),
                'trips_synced'    => $tripsSynced,
                'events_synced'   => $alertsSynced,
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