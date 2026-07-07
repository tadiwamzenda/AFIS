<?php

namespace Modules\AfisPortal\Services;

use Carbon\Carbon;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisTrackerGroup;
use Modules\AfisPipeline\Models\AfisTrip;
use Modules\AfisPipeline\Models\AfisMileageDaily;
use Modules\AfisPipeline\Models\AfisFuelEvent;
use Modules\AfisPipeline\Models\AfisDeviceAlert;

class ReportDataService
{
    // Zimbabwe public holidays 2024-2026
    private array $zimbabweHolidays = [
        '2024-01-01', '2024-02-21', '2024-03-29', '2024-04-01',
        '2024-04-18', '2024-05-01', '2024-05-25', '2024-08-12',
        '2024-08-13', '2024-12-22', '2024-12-25', '2024-12-26',
        '2025-01-01', '2025-02-21', '2025-04-18', '2025-04-19',
        '2025-04-20', '2025-04-21', '2025-04-18', '2025-05-01',
        '2025-05-25', '2025-08-11', '2025-08-12', '2025-12-22',
        '2025-12-25', '2025-12-26',
        '2026-01-01', '2026-02-21', '2026-04-03', '2026-04-05',
        '2026-04-06', '2026-04-07', '2026-04-18', '2026-05-01',
        '2026-05-25', '2026-08-11', '2026-08-12', '2026-12-22',
        '2026-12-25', '2026-12-26',
    ];

    private int $speedLimit = 120;

    public function buildReportData(
        Client  $client,
        Carbon  $from,
        Carbon  $to,
        ?int    $groupId = null
        
    ): array {
        // Get trackers — filtered by group if specified
        $trackerQuery = AfisTracker::where('client_id', $client->id);

        if ($groupId) {
            $trackerQuery->where('navixy_group_id', $groupId);
        }

        $trackers   = $trackerQuery->orderBy('label')->get();
        $trackerIds = $trackers->pluck('id')->toArray();

        if (empty($trackerIds)) {
            return ['error' => 'No trackers found for this selection.'];
        }

        // ── Performance metrics ──────────────────────────────────────────────
        $totalMileage    = AfisMileageDaily::whereIn('tracker_id', $trackerIds)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->sum('mileage_km');

        $allTrips = AfisTrip::whereIn('tracker_id', $trackerIds)
            ->whereBetween('start_time', [$from, $to])
            ->get();

        $weekendKm   = 0;
        $afterHrsKm  = 0;
        $speedingTrips = [];

        foreach ($allTrips as $trip) {
            $start = Carbon::parse($trip->start_time);
            $km    = $trip->distance_km;

            if ($this->isWeekendOrHoliday($start)) $weekendKm += $km;
            if ($this->isAfterHours($start)) $afterHrsKm += $km;
            if ($trip->max_speed_kmh > $this->speedLimit) {
                $speedingTrips[] = $trip;
            }
        }

        // ── Per-vehicle data ─────────────────────────────────────────────────
        $vehicles = [];
        foreach ($trackers as $tracker) {
            $vTrips = $allTrips->where('tracker_id', $tracker->id);

            $vMileage   = AfisMileageDaily::where('tracker_id', $tracker->id)
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->sum('mileage_km');

            $vWeekend   = 0;
            $vAfterHrs  = 0;
            $vMaxSpeed  = 0;
            $vSpeeding  = 0;


            // After hours breakdown by hour slot
            $hourBreakdown = [];
            for ($h = 18; $h <= 23; $h++) $hourBreakdown["{$h}:00-{$h}:59"] = 0;
            for ($h = 0; $h <= 5; $h++)  $hourBreakdown["{$h}:00-{$h}:59"] = 0;

            foreach ($vTrips as $trip) {
                $start = Carbon::parse($trip->start_time);
                $km    = $trip->distance_km;

                if ($this->isWeekendOrHoliday($start)) $vWeekend += $km;
                if ($this->isAfterHours($start)) {
                    $vAfterHrs += $km;
                    $hour = (int) $start->format('H');
                    $key  = "{$hour}:00-{$hour}:59";
                    if (isset($hourBreakdown[$key])) $hourBreakdown[$key] += $km;
                }
                if ($trip->max_speed_kmh > $vMaxSpeed) $vMaxSpeed = $trip->max_speed_kmh;
                if ($trip->max_speed_kmh > $this->speedLimit) $vSpeeding++;
            }

            // ── Speeding detail from alerts ───────────────────────────────────
            $speedingDetail = collect($vehicles)
            ->filter(fn($v) => $v['speeding_trips'] > 0)
            ->map(function ($v) use ($trackerIds, $from, $to) {
                // Get the worst speeding trip for address
                $worstTrip = AfisTrip::where('tracker_id', $v['id'])
                    ->whereBetween('start_time', [$from, $to])
                    ->where('max_speed_kmh', '>', $this->speedLimit)
                    ->orderByDesc('max_speed_kmh')
                    ->first();

                // Try to get address from speedup alerts
                $alert = \Modules\AfisPipeline\Models\AfisDeviceAlert::where('tracker_id', $v['id'])
                    ->where('event_type', 'speedup')
                    ->whereBetween('occurred_at', [$from, $to])
                    ->whereNotNull('address')
                    ->orderByDesc('occurred_at')
                    ->first();

                // Also try outzone alerts which often have location context
                if (!$alert) {
                    $alert = \Modules\AfisPipeline\Models\AfisDeviceAlert::where('tracker_id', $v['id'])
                        ->whereBetween('occurred_at', [$from, $to])
                        ->whereNotNull('address')
                        ->whereRaw("address != ''"  )
                        ->first();
                }

                return [
                    'label'     => $v['label'],
                    'group'     => $v['group'],
                    'top_speed' => $v['max_speed'],
                    'address'   => $alert?->address ?? ($worstTrip ? 'Speed recorded on ' . Carbon::parse($worstTrip->start_time)->format('d M Y') : '—'),
                    'time'      => $worstTrip ? Carbon::parse($worstTrip->start_time)->format('Y-m-d') : '—',
                    'frequency' => $v['speeding_trips'],
                ];
            })
            ->sortByDesc('top_speed')
            ->values()
            ->toArray();

            // Fuel events for this tracker
            $fuelingEvents = AfisFuelEvent::where('tracker_id', $tracker->id)
                ->where('event_type', 'fueling')
                ->whereBetween('occurred_at', [$from, $to])
                ->get();

            $drainEvents = AfisFuelEvent::where('tracker_id', $tracker->id)
                ->where('event_type', 'drain')
                ->whereBetween('occurred_at', [$from, $to])
                ->get();

            $totalFueling = round($fuelingEvents->sum('volume_litres'), 2);
            $totalDrain   = round($drainEvents->sum('volume_litres'), 2);
            $consumption  = $vMileage > 0 && $totalFueling > 0
                ? round(($totalFueling / $vMileage) * 100, 4)
                : null;

            // Get group name
            $group = AfisTrackerGroup::where('navixy_group_id', $tracker->navixy_group_id)->first();

            $vehicles[] = [
                'id'             => $tracker->id,
                'label'          => $tracker->label,
                'group'          => $group?->title ?? $client->name,
                'group_id'       => $tracker->navixy_group_id,
                'mileage'        => round($vMileage, 2),
                'weekend_km'     => round($vWeekend, 2),
                'after_hrs_km'   => round($vAfterHrs, 2),
                'max_speed'      => round($vMaxSpeed, 0),
                'speeding_trips' => $vSpeeding,
                'hour_breakdown' => $hourBreakdown,
                'fueling_count'  => $fuelingEvents->count(),
                'fueling_litres' => $totalFueling,
                'drain_count'    => $drainEvents->count(),
                'drain_litres'   => $totalDrain,
                'consumption'    => $consumption,
                'trips'          => $vTrips->count(),
                'fuel_events' => (function() use ($tracker, $from, $to) {
                // All fuel readings for this tracker in period (sorted by time)
                $allReadings = AfisFuelEvent::where('tracker_id', $tracker->id)
                    ->whereBetween('occurred_at', [$from, $to])
                    ->orderBy('occurred_at')
                    ->get();

                if ($allReadings->isEmpty()) return [];

                $result      = [];
                $prevReading = null;
                $refuelStart = null;
                $dayData     = [];

                // Group readings by date
                $byDate = $allReadings->groupBy(
                    fn($r) => Carbon::parse($r->occurred_at)->toDateString()
                );

                foreach ($byDate as $date => $readings) {
                    $dayMileage  = AfisMileageDaily::where('tracker_id', $tracker->id)
                        ->where('date', $date)->value('mileage_km') ?? 0;

                    $levels      = $readings->pluck('volume_litres')->map(fn($v) => (float)$v)->values();
                    $startLevel  = $levels->first();
                    $endLevel    = $levels->last();

                    // Detect refuels — significant upward jumps (>10L)
                    $refuelCount  = 0;
                    $refuelVolume = 0;
                    for ($i = 1; $i < $levels->count(); $i++) {
                        $jump = $levels[$i] - $levels[$i - 1];
                        if ($jump > 10) {
                            $refuelCount++;
                            $refuelVolume += $jump;
                        }
                    }

                    // Detect drains — significant downward jumps (>15L not explained by consumption)
                    $drainCount  = 0;
                    $drainVolume = 0;
                    for ($i = 1; $i < $levels->count(); $i++) {
                        $drop = $levels[$i - 1] - $levels[$i];
                        if ($drop > 15) {
                            $drainCount++;
                            $drainVolume += $drop;
                        }
                    }

                    // Consumed = start level - end level + any refuels added
                    $consumed = round(max(0, $startLevel - $endLevel + $refuelVolume), 2);
                    $rate     = ($dayMileage > 0 && $consumed > 0)
                        ? round($dayMileage / $consumed, 4) : null;

                    // Only add row if there was meaningful activity
                    if ($dayMileage > 0 || $refuelCount > 0 || $drainCount > 0) {
                        if ($refuelCount > 0) {
                            $result[] = [
                                'date'     => Carbon::parse($date)->format('d.m.Y'),
                                'type'     => 'fueling',
                                'mileage'  => round($dayMileage, 2),
                                'refuels'  => $refuelCount,
                                'volume'   => round($refuelVolume, 2),
                                'consumed' => $consumed,
                                'rate'     => $rate,
                                'address'  => $readings->first()?->address,
                            ];
                        } elseif ($dayMileage > 0) {
                            // No refuel but vehicle was driven — show consumption only
                            $result[] = [
                                'date'     => Carbon::parse($date)->format('d.m.Y'),
                                'type'     => 'normal',
                                'mileage'  => round($dayMileage, 2),
                                'refuels'  => 0,
                                'volume'   => null,
                                'consumed' => $consumed > 0 ? $consumed : null,
                                'rate'     => $rate,
                                'address'  => null,
                            ];
                        }

                        if ($drainCount > 0) {
                            $result[] = [
                                'date'     => Carbon::parse($date)->format('d.m.Y'),
                                'type'     => 'drain',
                                'mileage'  => round($dayMileage, 2),
                                'refuels'  => 0,
                                'volume'   => round($drainVolume, 2),
                                'consumed' => null,
                                'rate'     => null,
                                'address'  => $readings->last()?->address,
                            ];
                        }
                    }
                }

                return $result;
            })(),
            ];
        }
        
        // ── Weekend dates breakdown ───────────────────────────────────────
        $weekendDates = [];
        $current = $from->copy();
        while ($current->lte($to)) {
            if ($this->isWeekendOrHoliday($current)) {
                $weekendDates[] = $current->toDateString();
            }
            $current->addDay();
        }

        // Per-vehicle km on each weekend date
        $weekendByDate = [];
        foreach ($vehicles as &$vehicle) {
            $tracker   = $trackers->firstWhere('id', $vehicle['id']);
            $dateTotals = [];
            foreach ($weekendDates as $date) {
                $km = AfisTrip::where('tracker_id', $vehicle['id'])
                    ->whereDate('start_time', $date)
                    ->sum('distance_km');
                $dateTotals[$date] = round($km, 0);
            }
            $vehicle['weekend_dates'] = $dateTotals;
            $vehicle['weekend_total'] = array_sum($dateTotals);
        }
        unset($vehicle);

        // ── Speeding summary ─────────────────────────────────────────────────
        $speedingSummary = collect($vehicles)
            ->filter(fn($v) => $v['speeding_trips'] > 0)
            ->sortByDesc('max_speed')
            ->values()
            ->toArray();

        // ── Weekend summary ──────────────────────────────────────────────────
        $weekendSummary = collect($vehicles)
            ->filter(fn($v) => $v['weekend_km'] > 0)
            ->sortByDesc('weekend_km')
            ->values()
            ->toArray();

        // ── After hours summary ──────────────────────────────────────────────
        $afterHrsSummary = collect($vehicles)
            ->filter(fn($v) => $v['after_hrs_km'] > 0)
            ->sortByDesc('after_hrs_km')
            ->values()
            ->toArray();

        // ── Fuel summary ─────────────────────────────────────────────────────
        $fuelSummary = collect($vehicles)
            ->filter(fn($v) => $v['fueling_count'] > 0 || $v['drain_count'] > 0)
            ->sortByDesc('fueling_litres')
            ->values()
            ->toArray();

        // ── Sub-group breakdown ──────────────────────────────────────────────
        $groupBreakdown = collect($vehicles)
            ->groupBy('group')
            ->map(fn($gv) => [
                'name'       => $gv->first()['group'],
                'count'      => $gv->count(),
                'mileage'    => round($gv->sum('mileage'), 2),
                'weekend_km' => round($gv->sum('weekend_km'), 2),
                'after_hrs'  => round($gv->sum('after_hrs_km'), 2),
                'speeding'   => $gv->sum('speeding_trips'),
            ])
            ->sortBy('name')
            ->values()
            ->toArray();

            // ── Flat fuel list sorted by date for report ─────────────────────
        $fuelFlat = collect($vehicles)
            ->flatMap(fn($v) => collect($v['fuel_events'])->map(fn($fe) => array_merge($fe, ['label' => $v['label']])))
            ->sortBy('date')
            ->values()
            ->toArray();

        return [
            'client'         => $client,
            'from'           => $from,
            'to'             => $to,
            'fleet_size'     => $trackers->count(),
            'total_mileage'  => round($totalMileage, 2),
            'weekend_km'     => round($weekendKm, 2),
            'after_hrs_km'   => round($afterHrsKm, 2),
            'vehicles'       => $vehicles,
            'weekend_dates' => $weekendDates,
            'speeding_detail' => $speedingDetail,
            'speeding'       => $speedingSummary,
            'weekend'        => $weekendSummary,
            'after_hours'    => $afterHrsSummary,
            'fuel'           => $fuelSummary,
            'fuel_flat'      => $fuelFlat,
            'groups'         => $groupBreakdown,
            'speed_limit'    => $this->speedLimit,
            'generated_at'   => Carbon::now(),
        ];
    }

    private function isWeekendOrHoliday(Carbon $date): bool
    {
        if ($date->isWeekend()) return true;
        return in_array($date->toDateString(), $this->zimbabweHolidays);
    }

    private function isAfterHours(Carbon $date): bool
    {
        $hour = (int) $date->format('H');
        return $hour >= 18 || $hour < 6;
    }
}