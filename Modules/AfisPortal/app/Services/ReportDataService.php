<?php

namespace Modules\AfisPortal\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisTrackerGroup;
use Modules\AfisPipeline\Models\AfisTrip;
use Modules\AfisPipeline\Models\AfisFuelEvent;
use Modules\AfisPipeline\Models\AfisDeviceAlert;
use Modules\AfisPipeline\Models\AfisFuelDaily;

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
        Client $client,
        Carbon $from,
        Carbon $to,
        array  $selectedGroups = []
    ): array {
        ini_set('memory_limit', '2048M');
        set_time_limit(0);

        // ── Get trackers ──────────────────────────────────────────────────────
        $trackerQuery = AfisTracker::where('client_id', $client->id);
        if (!empty($selectedGroups)) {
            $trackerQuery->whereIn('navixy_group_id', $selectedGroups);
        }
        $trackers   = $trackerQuery->orderBy('label')->get();
        $trackerIds = $trackers->pluck('id')->toArray();

        if (empty($trackerIds)) {
            return ['error' => 'No trackers found for this selection.'];
        }

        // ── Weekend dates ─────────────────────────────────────────────────────
        $weekendDates = [];
        $current      = $from->copy();
        while ($current->lte($to)) {
            if ($this->isWeekendOrHoliday($current)) {
                $weekendDates[] = $current->toDateString();
            }
            $current->addDay();
        }

        // ── Bulk SQL aggregates (NO full trip load) ───────────────────────────

        // 1. Total mileage per tracker
        $mileageByTracker = DB::table('afis_trips')
            ->whereIn('tracker_id', $trackerIds)
            ->whereBetween('start_time', [$from, $to])
            ->selectRaw('tracker_id, SUM(distance_km) as total_mileage')
            ->groupBy('tracker_id')
            ->pluck('total_mileage', 'tracker_id');

        // 2. Total mileage per fleet
        $totalMileage = $mileageByTracker->sum();

        // 3. Groups lookup
        $groupsByNavixyId = AfisTrackerGroup::whereIn('navixy_group_id',
            $trackers->pluck('navixy_group_id')->filter()->unique()->toArray()
        )->pluck('title', 'navixy_group_id');

        // 4. Weekend km per tracker (SQL)
        $weekendKmByTracker = collect();
        if (!empty($weekendDates)) {
            $weekendKmByTracker = DB::table('afis_trips')
                ->whereIn('tracker_id', $trackerIds)
                ->whereIn(DB::raw('DATE(start_time)'), $weekendDates)
                ->selectRaw('tracker_id, SUM(distance_km) as total_km')
                ->groupBy('tracker_id')
                ->pluck('total_km', 'tracker_id');
        }

        // 5. After hours km per tracker (SQL)
        $afterHrsKmByTracker = DB::table('afis_trips')
            ->whereIn('tracker_id', $trackerIds)
            ->whereBetween('start_time', [$from, $to])
            ->where(function ($q) {
                $q->whereRaw('HOUR(start_time) >= 18')
                  ->orWhereRaw('HOUR(start_time) < 6');
            })
            ->selectRaw('tracker_id, SUM(distance_km) as total_km')
            ->groupBy('tracker_id')
            ->pluck('total_km', 'tracker_id');

        // 6. Max speed and speeding trips per tracker (SQL)
        // Speeds > 195 km/h are treated as GPS errors and excluded entirely.
        // MySQL's MAX() ignores NULLs, so a tracker whose every reading is
        // >195 gets max_speed = NULL here — it falls out of every
        // speeding-related list downstream since speeding_trips also lands at 0.
        $speedStats = DB::table('afis_trips')
            ->whereIn('tracker_id', $trackerIds)
            ->whereBetween('start_time', [$from, $to])
            ->selectRaw('
                tracker_id,
                MAX(CASE WHEN max_speed_kmh BETWEEN ? AND 195 THEN max_speed_kmh ELSE NULL END) as max_speed,
                SUM(CASE WHEN max_speed_kmh BETWEEN ? AND 195 THEN 1 ELSE 0 END) as speeding_trips
            ', [$this->speedLimit, $this->speedLimit])
            ->groupBy('tracker_id')
            ->get()
            ->keyBy('tracker_id');

        // 7. Hour breakdown for after-hours (SQL)
        $hourBreakdownRaw = DB::table('afis_trips')
            ->whereIn('tracker_id', $trackerIds)
            ->whereBetween('start_time', [$from, $to])
            ->where(function ($q) {
                $q->whereRaw('HOUR(start_time) >= 18')
                  ->orWhereRaw('HOUR(start_time) < 6');
            })
            ->selectRaw('tracker_id, HOUR(start_time) as hour_slot, SUM(distance_km) as total_km')
            ->groupBy('tracker_id', 'hour_slot')
            ->get()
            ->groupBy('tracker_id');

        // 8. Weekend per-date breakdown (SQL)
        $weekendTripData = collect();
        if (!empty($weekendDates)) {
            $weekendTripData = DB::table('afis_trips')
                ->whereIn('tracker_id', $trackerIds)
                ->whereIn(DB::raw('DATE(start_time)'), $weekendDates)
                ->selectRaw('tracker_id, DATE(start_time) as trip_date, SUM(distance_km) as total_km')
                ->groupBy('tracker_id', 'trip_date')
                ->get()
                ->groupBy('tracker_id')
                ->map(fn($trips) => $trips->pluck('total_km', 'trip_date'));
        }

        // 9. Trip count per tracker (SQL)
        $tripCountByTracker = DB::table('afis_trips')
            ->whereIn('tracker_id', $trackerIds)
            ->whereBetween('start_time', [$from, $to])
            ->selectRaw('tracker_id, COUNT(*) as total')
            ->groupBy('tracker_id')
            ->pluck('total', 'tracker_id');

        // 9b. Trip-based total mileage per tracker — same source (afis_trips) as
        // weekend_km, used specifically for the weekend "% of total" calculation
        // so numerator and denominator never come from two disagreeing pipelines.
        // (mileage_daily, used for $v['mileage'] elsewhere, can be sparse/lagging
        // for some trackers, which was producing percentages over 10,000%.)
        $tripMileageByTracker = DB::table('afis_trips')
            ->whereIn('tracker_id', $trackerIds)
            ->whereBetween('start_time', [$from, $to])
            ->selectRaw('tracker_id, SUM(distance_km) as total_km')
            ->groupBy('tracker_id')
            ->pluck('total_km', 'tracker_id');

        // 10. Fuel events (bulk)
        $fuelEventsByTracker = AfisFuelEvent::whereIn('tracker_id', $trackerIds)
            ->whereBetween('occurred_at', [$from, $to])
            ->get()
            ->groupBy('tracker_id');

        // 11. Fuel daily (bulk)
        $fuelByTracker = AfisFuelDaily::whereIn('tracker_id', $trackerIds)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->where(function ($q) {
                $q->where('refuel_count', '>', 0)
                  ->orWhere('has_drain', true)
                  ->orWhere('consumed_litres', '>', 0);
            })
            ->orderBy('date')
            ->get()
            ->groupBy('tracker_id');

        // ── Per-vehicle data (no DB queries inside loop) ──────────────────────
        $vehicles = [];

        foreach ($trackers as $tracker) {
            $vMileage  = (float) ($mileageByTracker[$tracker->id] ?? 0);
            $vWeekend  = (float) ($weekendKmByTracker[$tracker->id] ?? 0);
            $vAfterHrs = (float) ($afterHrsKmByTracker[$tracker->id] ?? 0);
            $stats     = $speedStats->get($tracker->id);
            $vMaxSpeed = (float) ($stats?->max_speed ?? 0);
            $vSpeeding = (int)   ($stats?->speeding_trips ?? 0);

            // Hour breakdown
            $hourBreakdown = [];
            for ($h = 18; $h <= 23; $h++) $hourBreakdown["{$h}:00-{$h}:59"] = 0;
            for ($h = 0; $h <= 5; $h++)   $hourBreakdown["{$h}:00-{$h}:59"] = 0;

            $trackerHours = $hourBreakdownRaw->get($tracker->id, collect());
            foreach ($trackerHours as $row) {
                $h   = (int) $row->hour_slot;
                $key = "{$h}:00-{$h}:59";
                if (isset($hourBreakdown[$key])) {
                    $hourBreakdown[$key] = round((float) $row->total_km, 0);
                }
            }

            // Weekend per-date
            $trackerWeekendTrips = $weekendTripData->get($tracker->id, collect());
            $dateTotals          = [];
            foreach ($weekendDates as $date) {
                $dateTotals[$date] = (int) round((float) $trackerWeekendTrips->get($date, 0));
            }

            // Fuel events — refuelling figures sourced from AfisFuelDaily
            // (same trusted source as fuel_flat / Standard Report's Fuel
            // Summary table), NOT AfisFuelEvent, which was found to overcount
            // refuels by ~3-9x for at least one vehicle (confirmed against
            // both the Standard Report and the client's own Navixy figures).
            // Drain figures still come from AfisFuelEvent below and likely
            // carry the same bug — not yet fixed, pending verification.
            $trackerFuelDaily = $fuelByTracker->get($tracker->id, collect());
            $fuelingDays      = $trackerFuelDaily->filter(fn($f) => $f->refuel_count > 0);

            $totalFuelingCount = (int) $fuelingDays->sum('refuel_count');
            $totalFueling      = round((float) $fuelingDays->sum('volume_litres'), 2);

            $trackerFuelEvents = $fuelEventsByTracker->get($tracker->id, collect());
            $drainEvents       = $trackerFuelEvents->where('event_type', 'drain');
            $totalDrain        = round($drainEvents->sum('volume_litres'), 2);

            $consumption  = $vMileage > 0 && $totalFueling > 0
                ? round(($totalFueling / $vMileage) * 100, 4)
                : null;

            $vehicles[] = [
                'id'             => $tracker->id,
                'label'          => $tracker->label,
                'group'          => $groupsByNavixyId[$tracker->navixy_group_id] ?? $client->name,
                'group_id'       => $tracker->navixy_group_id,
                'mileage'        => round($vMileage, 2),
                'weekend_km'     => round($vWeekend, 2),
                'after_hrs_km'   => round($vAfterHrs, 2),
                'max_speed'      => round($vMaxSpeed, 0),
                'speeding_trips' => $vSpeeding,
                'hour_breakdown' => $hourBreakdown,
                'fueling_count'  => $totalFuelingCount,
                'fueling_litres' => $totalFueling,
                'drain_count'    => $drainEvents->count(),
                'drain_litres'   => $totalDrain,
                'consumption'    => $consumption,
                'trips'          => (int) ($tripCountByTracker[$tracker->id] ?? 0),
                'trip_mileage'   => round($vMileage, 2), // same source now
                'weekend_dates'  => $dateTotals,
                'weekend_total'  => array_sum($dateTotals),
                'fuel_events'    => ($fuelByTracker->get($tracker->id) ?? collect())
                    ->map(fn($f) => [
                        'date'     => $f->date->format('d.m.Y'),
                        'type'     => $f->has_drain && $f->refuel_count === 0 ? 'drain' : 'fueling',
                        'mileage'  => $f->mileage_km,
                        'refuels'  => $f->refuel_count,
                        'volume'   => $f->volume_litres,
                        'consumed' => $f->consumed_litres,
                        'rate'     => $f->consumption_km_per_litre,
                        'address'  => null,
                    ])
                    ->toArray(),
            ];
        }

        // ── Fleet totals from vehicle data ────────────────────────────────────
        $weekendKm  = collect($vehicles)->sum('weekend_km');
        $afterHrsKm = collect($vehicles)->sum('after_hrs_km');

        // ── Speeding summary ──────────────────────────────────────────────────
        $speedingSummary = collect($vehicles)
            ->filter(fn($v) => $v['speeding_trips'] > 0)
            ->sortByDesc('max_speed')
            ->values()
            ->toArray();

        // ── Speeding detail (built after vehicles array is complete) ──────────
        $speedingDetail = collect($vehicles)
            ->filter(fn($v) => $v['speeding_trips'] > 0)
            ->map(function ($v) use ($from, $to) {
                $worstTrip = AfisTrip::where('tracker_id', $v['id'])
                    ->whereBetween('start_time', [$from, $to])
                    ->whereBetween('max_speed_kmh', [$this->speedLimit, 195]) // exclude GPS errors
                    ->orderByDesc('max_speed_kmh')
                    ->first();

                $alert = AfisDeviceAlert::where('tracker_id', $v['id'])
                    ->whereIn('event_type', ['speedup'])
                    ->whereBetween('occurred_at', [$from, $to])
                    ->whereNotNull('address')
                    ->orderByDesc('occurred_at')
                    ->first();

                 return [
                    'label'     => $v['label'],
                    'group'     => $v['group'],
                    'top_speed' => $v['max_speed'],
                    // No fallback guess — if no speedup alert exists (e.g. speed alert rules aren't configured in Navixy for 
                    //this clientor this is a historical period predating when they were), the address genuinely doesn't exist. 
                    'address'   => $alert?->address ?? 'Location unavailable, refer to system Speed Violation report',
                    'time'      => $worstTrip
                        ? Carbon::parse($worstTrip->start_time)->format('Y-m-d')
                        : '—',
                    'frequency' => $v['speeding_trips'],
                ];
            })
            ->sortByDesc('top_speed')
            ->values()
            ->toArray();

        // ── Weekend summary ───────────────────────────────────────────────────
        $weekendSummary = collect($vehicles)
            ->filter(fn($v) => $v['weekend_km'] >= 5)
            ->sortByDesc('weekend_km')
            ->values()
            ->toArray();

        // ── After hours summary ───────────────────────────────────────────────
        $afterHrsSummary = collect($vehicles)
            ->filter(fn($v) => $v['after_hrs_km'] > 0)
            ->sortByDesc('after_hrs_km')
            ->values()
            ->toArray();

        // ── Fuel summary ──────────────────────────────────────────────────────
        $fuelSummary = collect($vehicles)
            ->filter(fn($v) => $v['fueling_count'] > 0 || $v['drain_count'] > 0)
            ->sortByDesc('fueling_litres')
            ->values()
            ->toArray();

        // ── Sub-group breakdown ───────────────────────────────────────────────
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

        // ── Flat fuel list ────────────────────────────────────────────────────
        // Use pre-loaded tracker/group data — no N+1
        $trackerLabelMap = $trackers->pluck('label', 'id');
        $trackerGroupMap = $trackers->pluck('navixy_group_id', 'id');

        $fuelFlat = AfisFuelDaily::whereIn('tracker_id', $trackerIds)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->where(function ($q) {
                $q->where('refuel_count', '>', 0)
                  ->orWhere('has_drain', true)
                  ->orWhere('consumed_litres', '>', 0);
            })
            ->orderBy('date')
            ->orderBy('tracker_id')
            ->get()
            ->map(function ($f) use ($trackerLabelMap, $trackerGroupMap, $groupsByNavixyId, $client) {
                $groupId = $trackerGroupMap[$f->tracker_id] ?? null;
                return [
                    'label'    => $f->vehicle_label ?? ($trackerLabelMap[$f->tracker_id] ?? '—'),
                    'group'    => $groupId ? ($groupsByNavixyId[$groupId] ?? $client->name) : $client->name,
                    'date'     => $f->date->format('d.m.Y'),
                    'type'     => $f->has_drain && $f->refuel_count === 0 ? 'drain' : 'fueling',
                    'mileage'  => $f->mileage_km,
                    'refuels'  => $f->refuel_count,
                    'volume'   => $f->volume_litres,
                    'consumed' => $f->consumed_litres,
                    'rate'     => $f->consumption_km_per_litre,
                    'address'  => null,
                ];
            })
            ->toArray();

                // ── Previous-period trend comparison ──────────────────────────────────
        $periodDays   = $from->diffInDays($to) + 1;
        $previousFrom = $from->copy()->subDays($periodDays)->startOfDay();
        $previousTo   = $from->copy()->subDay()->endOfDay();

        $previousMileage = (float) DB::table('afis_trips')
            ->whereIn('tracker_id', $trackerIds)
            ->whereBetween('start_time', [$previousFrom, $previousTo])
            ->sum('distance_km');

        $mileageTrendPct = $previousMileage > 0
            ? round((($totalMileage - $previousMileage) / $previousMileage) * 100, 1)
            : 0;

        // ── Offline count — same MAX(id)-subquery pattern as FleetStateDashboard ──
        $offlineCount = DB::table('afis_device_alerts')
            ->whereIn('tracker_id', $trackerIds)
            ->whereIn('event_type', ['online', 'offline'])
            ->whereIn('id', function ($sub) use ($trackerIds) {
                $sub->selectRaw('MAX(id)')
                    ->from('afis_device_alerts')
                    ->whereIn('tracker_id', $trackerIds)
                    ->whereIn('event_type', ['online', 'offline'])
                    ->groupBy('tracker_id');
            })
            ->where('event_type', 'offline')
            ->count();

        // ── Critical alerts from afis_notifications ─────────────────────────────
        // Defensive: a 'severity' column on afis_notifications hasn't been
        // confirmed in this codebase — degrades to 0 instead of throwing if absent.
        $criticalAlerts = 0;
        if (Schema::hasTable('afis_notifications') && Schema::hasColumn('afis_notifications', 'severity')) {
            $criticalAlerts = DB::table('afis_notifications')
                ->whereIn(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.tracker_id'))"), $trackerIds)
                ->where('created_at', '>=', $from)
                ->whereIn('severity', ['critical', 'severe'])
                ->count();
        }

        return [
            'client'            => $client,
            'from'              => $from,
            'to'                => $to,
            'fleet_size'        => $trackers->count(),
            'total_mileage'     => round($totalMileage, 2),
            'weekend_km'        => round($weekendKm, 2),
            'after_hrs_km'      => round($afterHrsKm, 2),
            'vehicles'          => $vehicles,
            'speeding'          => $speedingSummary,
            'speeding_detail'   => $speedingDetail,
            'weekend'           => $weekendSummary,
            'after_hours'       => $afterHrsSummary,
            'fuel'              => $fuelSummary,
            'fuel_flat'         => $fuelFlat,
            'groups'            => $groupBreakdown,
            'weekend_dates'     => $weekendDates,
            'speed_limit'       => $this->speedLimit,
            'generated_at'      => Carbon::now(),
            'previous_mileage'  => round($previousMileage, 2),
            'mileage_trend_pct' => $mileageTrendPct,
            'offline_count'     => $offlineCount,
            'critical_alerts'   => $criticalAlerts,
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
