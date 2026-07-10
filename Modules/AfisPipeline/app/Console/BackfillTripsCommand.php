<?php

namespace Modules\AfisPipeline\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisDeviceAlert;
use Modules\AfisPipeline\Models\AfisFuelDaily;
use Modules\AfisPipeline\Models\AfisFuelEvent;
use Modules\AfisPipeline\Models\AfisMileageDaily;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisTrip;
use Modules\AfisPipeline\Services\FuelDataParser;
use Modules\AfisPipeline\Services\NavixyDataService;

class BackfillTripsCommand extends Command
{
    protected $signature = 'afis:backfill
        {--client= : Client ID to backfill (leave empty for all)}
        {--days=30 : Number of days to backfill}
        {--from= : Start date (Y-m-d)}
        {--to= : End date (Y-m-d)}';

    protected $description = 'Backfill historical trip, mileage, alerts and fuel data from Navixy';

    public function handle(NavixyDataService $navixy, FuelDataParser $fuelParser): void
    {
        $from = $this->option('from')
            ? Carbon::parse($this->option('from'))->startOfDay()
            : Carbon::now()->subDays((int) $this->option('days'))->startOfDay();

        $to = $this->option('to')
            ? Carbon::parse($this->option('to'))->endOfDay()
            : Carbon::now()->endOfDay();

        $this->info("Backfilling from {$from->format('d M Y')} to {$to->format('d M Y')}");

        $clients = $this->option('client')
            ? Client::where('id', $this->option('client'))->get()
            : Client::active()->get();

        foreach ($clients as $client) {
            $instance   = $client->navixy_instance ?? 1;
            $trackers   = AfisTracker::where('client_id', $client->id)->get();

            if ($trackers->isEmpty()) {
                $this->warn("  → {$client->name}: no trackers found, skipping");
                continue;
            }

            $this->line("  → {$client->name}: {$trackers->count()} trackers...");

            $trackerIds   = $trackers->pluck('navixy_tracker_id')->toArray();
            $tripCount    = 0;
            $mileageCount = 0;
            $alertCount   = 0;
            $fuelCount    = 0;

            // ── 1. Trips ──────────────────────────────────────────────────────
            foreach ($trackers as $tracker) {
            $tripChunkStart = $from->copy();
            while ($tripChunkStart->lt($to)) {
                $tripChunkEnd = $tripChunkStart->copy()->addDays(30)->min($to);

                $trips = $navixy->getTrips($tracker->navixy_tracker_id, $tripChunkStart, $tripChunkEnd, $instance);

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
                    $tripCount++;
                }

                     usleep(200000); // 0.2s delay per tracker
                      $tripChunkStart = $tripChunkEnd->copy()->addDay();
        }

       
    }


            // ── 2. Daily mileage (batch in 30-day chunks) ─────────────────────
            $chunkStart = $from->copy();
            while ($chunkStart->lt($to)) {
                $chunkEnd = $chunkStart->copy()->addDays(7)->min($to);

                $mileageData = [];
                foreach (array_chunk($trackerIds, 50) as $chunk) {
                    $chunkData   = $navixy->getDailyMileage($chunk, $chunkStart, $chunkEnd, $instance);
                    $mileageData = $mileageData + $chunkData;
                    usleep(500000);
                }

                foreach ($trackers as $tracker) {
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
                        $mileageCount++;
                    }
                }

                $chunkStart = $chunkEnd->copy()->addDay();
            }

            // ── 3. Alerts (speeding, fuel, geofence, crash etc) ──────────────
            $chunkStart = $from->copy();
            $trackerMap = $trackers->keyBy('navixy_tracker_id');

            while ($chunkStart->lt($to)) {
                $chunkEnd = $chunkStart->copy()->addDays(7)->min($to);
                $alerts   = $navixy->getAlerts($trackerIds, $chunkStart, $chunkEnd, $instance);

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
                    $alertCount++;

                    // Store fuel level snapshots for reference
                    if (in_array($alert['event'] ?? '', ['fueling', 'drain'])) {
                        $extra = $alert['extra'] ?? [];
                        AfisFuelEvent::firstOrCreate(
                            [
                                'tracker_id'        => $tracker->id,
                                'navixy_tracker_id' => $trackerId,
                                'occurred_at'       => Carbon::parse($alert['time']),
                                'event_type'        => $alert['event'],
                            ],
                            [
                                'client_id'      => $client->id,
                                'volume_litres'  => isset($extra['sensor_calculated_value'])
                                    ? (float) $extra['sensor_calculated_value'] : null,
                                'initial_volume' => null,
                                'final_volume'   => null,
                                'lat'            => $alert['location']['lat'] ?? null,
                                'lng'            => $alert['location']['lng'] ?? null,
                                'address'        => $alert['address'] ?? null,
                            ]
                        );
                    }
                }

                $chunkStart = $chunkEnd->copy()->addDay();
            }

            // ── 4. Fuel daily data via Navixy plugin 95 ───────────────────────
            // Only for clients/trackers that have fuel sensor events
            $fuelTrackerIds = AfisFuelEvent::whereIn('tracker_id', $trackers->pluck('id'))
                ->distinct()
                ->pluck('navixy_tracker_id')
                ->toArray();

            if (!empty($fuelTrackerIds)) {
                $this->line("     → Syncing fuel data for {$client->name} (" . count($fuelTrackerIds) . " fuel trackers)...");

                // Process in 7-day chunks
                $fuelChunkStart = $from->copy();
                while ($fuelChunkStart->lt($to)) {
                    $fuelChunkEnd = $fuelChunkStart->copy()->addDays(7)->min($to);

                    $synced = $fuelParser->syncFuelData(
                        $client,
                        $fuelTrackerIds,
                        $fuelChunkStart,
                        $fuelChunkEnd,
                        $instance,
                        $navixy
                    );
                    $fuelCount += $synced;

                    $this->line("       → {$fuelChunkStart->format('d M')} to {$fuelChunkEnd->format('d M')}: {$synced} records");

                    $fuelChunkStart = $fuelChunkEnd->copy()->addDay();
                }
            }

            $this->info("     ✓ {$tripCount} trips · {$mileageCount} mileage records · {$alertCount} alerts · {$fuelCount} fuel daily records");
        }

        $this->info('Backfill complete.');
    }
    
}