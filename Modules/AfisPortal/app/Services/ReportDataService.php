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
    // 2024
    '2024-01-01', // New Year's Day
    '2024-02-21', // Robert Mugabe National Youth Day
    '2024-03-29', // Good Friday
    '2024-04-01', // Easter Monday
    '2024-04-18', // Independence Day
    '2024-05-01', // Workers' Day
    '2024-05-25', // Africa Day
    '2024-08-12', // Heroes' Day (2nd Monday)
    '2024-08-13', // Defence Forces Day
    '2024-12-22', // National Unity Day
    '2024-12-25', // Christmas Day
    '2024-12-26', // Boxing Day

    // 2025
    '2025-01-01', // New Year's Day
    '2025-02-21', // Robert Mugabe National Youth Day
    '2025-04-18', // Good Friday
    '2025-04-21', // Easter Monday
    '2025-04-18', // Independence Day (falls on Friday)
    '2025-05-01', // Workers' Day
    '2025-05-25', // Africa Day (Sunday - observed Monday 26th)
    '2025-05-26', // Africa Day (observed)
    '2025-08-11', // Heroes' Day (2nd Monday)
    '2025-08-12', // Defence Forces Day
    '2025-12-22', // National Unity Day
    '2025-12-25', // Christmas Day
    '2025-12-26', // Boxing Day

    // 2026
    '2026-01-01', // New Year's Day
    '2026-02-21', // Robert Mugabe National Youth Day (Saturday)
    '2026-02-23', // Robert Mugabe National Youth Day (observed - Monday)
    '2026-04-03', // Good Friday
    '2026-04-06', // Easter Monday
    '2026-04-18', // Independence Day (Saturday)
    '2026-04-20', // Independence Day (observed - Monday)
    '2026-05-01', // Workers' Day
    '2026-05-25', // Africa Day (Monday)
    '2026-08-10', // Heroes' Day (2nd Monday)
    '2026-08-11', // Defence Forces Day
    '2026-12-22', // National Unity Day
    '2026-12-25', // Christmas Day
    '2026-12-26', // Boxing Day (Saturday)
    '2026-12-28', // Boxing Day (observed - Monday)

    // 2027
    '2027-01-01', // New Year's Day
    '2027-02-21', // Robert Mugabe National Youth Day (Sunday)
    '2027-02-22', // Robert Mugabe National Youth Day (observed - Monday)
    '2027-03-26', // Good Friday
    '2027-03-29', // Easter Monday
    '2027-04-18', // Independence Day (Sunday)
    '2027-04-19', // Independence Day (observed - Monday)
    '2027-05-01', // Workers' Day (Saturday)
    '2027-05-03', // Workers' Day (observed - Monday)
    '2027-05-25', // Africa Day
    '2027-08-09', // Heroes' Day (2nd Monday)
    '2027-08-10', // Defence Forces Day
    '2027-12-22', // National Unity Day
    '2027-12-25', // Christmas Day (Saturday)
    '2027-12-27', // Christmas Day (observed - Monday)
    '2027-12-26', // Boxing Day (Sunday)
    '2027-12-28', // Boxing Day (observed - Monday)

    // 2028
    '2028-01-01', // New Year's Day (Saturday)
    '2028-01-03', // New Year's Day (observed - Monday)
    '2028-02-21', // Robert Mugabe National Youth Day
    '2028-04-14', // Good Friday
    '2028-04-17', // Easter Monday
    '2028-04-18', // Independence Day
    '2028-05-01', // Workers' Day (Monday)
    '2028-05-25', // Africa Day
    '2028-08-14', // Heroes' Day (2nd Monday)
    '2028-08-15', // Defence Forces Day
    '2028-12-22', // National Unity Day
    '2028-12-25', // Christmas Day (Monday)
    '2028-12-26', // Boxing Day

    // 2029
    '2029-01-01', // New Year's Day (Monday)
    '2029-02-21', // Robert Mugabe National Youth Day
    '2029-03-30', // Good Friday
    '2029-04-02', // Easter Monday
    '2029-04-18', // Independence Day
    '2029-05-01', // Workers' Day
    '2029-05-25', // Africa Day
    '2029-08-13', // Heroes' Day (2nd Monday)
    '2029-08-14', // Defence Forces Day
    '2029-12-22', // National Unity Day (Saturday)
    '2029-12-24', // National Unity Day (observed - Monday)
    '2029-12-25', // Christmas Day
    '2029-12-26', // Boxing Day

    // 2030
    '2030-01-01', // New Year's Day
    '2030-02-21', // Robert Mugabe National Youth Day
    '2030-04-19', // Good Friday
    '2030-04-22', // Easter Monday
    '2030-04-18', // Independence Day
    '2030-05-01', // Workers' Day
    '2030-05-25', // Africa Day (Saturday)
    '2030-05-27', // Africa Day (observed - Monday)
    '2030-08-12', // Heroes' Day (2nd Monday)
    '2030-08-13', // Defence Forces Day
    '2030-12-22', // National Unity Day (Sunday)
    '2030-12-23', // National Unity Day (observed - Monday)
    '2030-12-25', // Christmas Day
    '2030-12-26', // Boxing Day
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

        // ── Speeding detail: ONE ROW PER VEHICLE PER DAY it actually
        // exceeded the limit — not just the single worst day across the
        // whole period, which was silently hiding every other qualifying
        // day. Frequency = count of qualifying TRIPS that day (confirmed
        // correct methodology — AFIS counts trips, Navixy counts individual
        // GPS pings, different metrics by design, not a bug).
        $dailySpeeding = DB::table('afis_trips')
            ->whereIn('tracker_id', $trackerIds)
            ->whereBetween('start_time', [$from, $to])
            ->where('max_speed_kmh', '>=', $this->speedLimit)
            ->where('max_speed_kmh', '<', 195) // GPS-error exclusion, same boundary as the fleet-wide rule
            ->selectRaw('tracker_id, DATE(start_time) as day, MAX(max_speed_kmh) as day_max_speed, COUNT(*) as day_frequency')
            ->groupBy('tracker_id', DB::raw('DATE(start_time)'))
            ->get();

        $vehiclesById = collect($vehicles)->keyBy('id');

        $speedingDetail = $dailySpeeding->map(function ($row) use ($vehiclesById) {
                $v = $vehiclesById->get($row->tracker_id);
                if (!$v) return null;

                // Worst trip for THIS specific day (not the whole period).
                $worstTripThatDay = AfisTrip::where('tracker_id', $row->tracker_id)
                    ->whereDate('start_time', $row->day)
                    ->where('max_speed_kmh', '>=', $this->speedLimit)
                    ->where('max_speed_kmh', '<', 195)
                    ->orderByDesc('max_speed_kmh')
                    ->first();

                // Match the alert to THIS day, closest in time to that
                // day's worst trip — same principle as before, scoped per-day.
                $alert = null;
                if ($worstTripThatDay) {
                    $tripStart = Carbon::parse($worstTripThatDay->start_time);
                    $alert = AfisDeviceAlert::where('tracker_id', $row->tracker_id)
                        ->where('event_type', 'speedup')
                        ->whereDate('occurred_at', $row->day)
                        ->whereNotNull('address')
                        ->get()
                        ->sortBy(fn($a) => abs(Carbon::parse($a->occurred_at)->diffInSeconds($tripStart)))
                        ->first();
                }

                return [
                    'label'     => $v['label'],
                    'group'     => $v['group'],
                    'top_speed' => round((float) $row->day_max_speed, 0),
                    // No fallback guess — if no speedup alert exists (e.g. speed alert rules
                    // aren't configured in Navixy for this client), the address genuinely
                    // doesn't exist.
                    'address'   => $alert?->address ?? 'Location unavailable, refer to system Speed Violation report',
                    'time'      => $row->day,
                    'frequency' => (int) $row->day_frequency,
                ];
            })
            ->filter()
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
