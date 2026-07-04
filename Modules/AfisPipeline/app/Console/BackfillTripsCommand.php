<?php

namespace Modules\AfisPipeline\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisTrip;
use Modules\AfisPipeline\Models\AfisMileageDaily;
use Modules\AfisPipeline\Services\NavixyDataService;

class BackfillTripsCommand extends Command
{
    protected $signature = 'afis:backfill
        {--client= : Client ID to backfill (leave empty for all)}
        {--days=30 : Number of days to backfill}
        {--from= : Start date (Y-m-d)}
        {--to= : End date (Y-m-d)}';

    protected $description = 'Backfill historical trip and mileage data from Navixy';

    public function handle(NavixyDataService $navixy): void
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
            $instance = $client->navixy_instance ?? 1;
            $trackers = AfisTracker::where('client_id', $client->id)->get();

            if ($trackers->isEmpty()) {
                $this->warn("  → {$client->name}: no trackers found, skipping");
                continue;
            }

            $this->line("  → {$client->name}: {$trackers->count()} trackers...");

            $tripCount    = 0;
            $mileageCount = 0;
            $trackerIds   = $trackers->pluck('navixy_tracker_id')->toArray();

            // ── Backfill trips ────────────────────────────────────────────
            foreach ($trackers as $tracker) {
                $trips = $navixy->getTrips($tracker->navixy_tracker_id, $from, $to, $instance);

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
                            'max_speed_kmh'    => min($trip['max_speed'] ?? 0, 200),
                            'duration_minutes' => $durationMinutes,
                        ]
                    );
                    $tripCount++;
                }

                // Small delay to avoid Navixy rate limits
                usleep(200000); // 0.2 seconds
            }

            // ── Backfill daily mileage ────────────────────────────────────
            // Process in 30-day chunks to stay within Navixy limits
            $chunkStart = $from->copy();
            while ($chunkStart->lt($to)) {
                $chunkEnd = $chunkStart->copy()->addDays(30)->min($to);

                $mileageData = $navixy->getDailyMileage(
                    $trackerIds, $chunkStart, $chunkEnd, $instance
                );

                foreach ($trackers as $tracker) {
                    $trackerMileage = $mileageData[$tracker->navixy_tracker_id] ?? [];
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

            $this->info("     ✓ {$tripCount} trips, {$mileageCount} daily mileage records");
        }

        $this->info('Backfill complete.');
    }
}