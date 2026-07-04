<?php

namespace Modules\AfisEngine\Services\Prompts;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisTrip;
use Modules\AfisPipeline\Models\AfisEvent;
use Modules\AdmmInventory\Models\Client;

class PromptBuilder
{
    // Zimbabwe public holidays
    private array $zimbabweHolidays2026 = [
        '2026-01-01', // New Year's Day
        '2026-02-21', // Robert Gabriel Mugabe National Youth Day
        '2026-04-03', // Good Friday
        '2026-04-05', // Easter Saturday
        '2026-04-06', // Easter Sunday
        '2026-04-07', // Easter Monday
        '2026-04-18', // Independence Day
        '2026-05-01', // Workers Day
        '2026-05-25', // Africa Day
        '2026-08-11', // Heroes Day
        '2026-08-12', // Defence Forces Day
        '2026-12-22', // Unity Day
        '2026-12-25', // Christmas Day
        '2026-12-26', // Boxing Day
    ];

    private int $speedLimitKmh = 120; // Zimbabwe open road speed limit

    // ─── Fleet Intelligence Report ────────────────────────────────────────────

    public function fleetIntelligence(Client $client, int $days = 30): string
    {
        $from     = Carbon::now()->subDays($days)->startOfDay();
        $to       = Carbon::now()->endOfDay();
        $trackers = AfisTracker::where('client_id', $client->id)->get();

        // ── Performance metrics per vehicle ──────────────────────────────────
        $vehicleData = [];
        $totalMileage        = 0;
        $totalWeekendKm      = 0;
        $totalAfterHoursKm   = 0;
        $speedingVehicles    = [];

        foreach ($trackers as $tracker) {
            $trips = AfisTrip::where('tracker_id', $tracker->id)
                ->whereBetween('start_time', [$from, $to])
                ->get();

            $vehicleMileage      = round($trips->sum('distance_km'), 2);
            $vehicleWeekendKm    = 0;
            $vehicleAfterHrsKm   = 0;
            $maxSpeed            = 0;
            $speedingCount       = 0;
            $afterHoursBreakdown = array_fill(18, 12, 0); // 18:00 to 05:59

            foreach ($trips as $trip) {
                $startTime = Carbon::parse($trip->start_time);
                $distKm    = $trip->distance_km;

                // Weekend / holiday check
                if ($this->isWeekendOrHoliday($startTime)) {
                    $vehicleWeekendKm += $distKm;
                }

                // After hours check (before 06:00 or after 18:00)
                $hour = (int) $startTime->format('H');
                if ($hour >= 18 || $hour < 6) {
                    $vehicleAfterHrsKm += $distKm;

                    // Track which hour slot
                    if ($hour >= 18) {
                        $slot = $hour; // 18-23
                    } else {
                        $slot = $hour; // 0-5 → store as 24-29 internally
                    }
                }

                // Speeding
                if ($trip->max_speed_kmh > $this->speedLimitKmh) {
                    $speedingCount++;
                    if ($trip->max_speed_kmh > $maxSpeed) {
                        $maxSpeed = $trip->max_speed_kmh;
                    }
                }
            }

            $totalMileage      += $vehicleMileage;
            $totalWeekendKm    += $vehicleWeekendKm;
            $totalAfterHoursKm += $vehicleAfterHrsKm;

            if ($speedingCount > 0) {
                $speedingVehicles[] = [
                    'label'    => $tracker->label,
                    'top_speed'=> $maxSpeed,
                    'count'    => $speedingCount,
                ];
            }

            if ($vehicleMileage > 0 || $vehicleWeekendKm > 0 || $vehicleAfterHrsKm > 0) {
                $vehicleData[] = [
                    'label'      => $tracker->label,
                    'mileage'    => $vehicleMileage,
                    'weekend_km' => round($vehicleWeekendKm, 2),
                    'after_hrs'  => round($vehicleAfterHrsKm, 2),
                    'max_speed'  => $maxSpeed,
                    'trips'      => $trips->count(),
                ];
            }
        }

        // ── Format tables for the prompt ─────────────────────────────────────
        $performanceTable = $this->formatPerformanceTable($client->name, $trackers->count(), $totalMileage, $totalWeekendKm, $totalAfterHoursKm);
        $vehicleTable     = $this->formatVehicleTable($vehicleData);
        $speedingTable    = $this->formatSpeedingTable($speedingVehicles);
        $weekendVehicles  = $this->formatWeekendVehicles($vehicleData);
        $afterHrsVehicles = $this->formatAfterHoursVehicles($vehicleData);

        return <<<PROMPT
You are a fleet intelligence analyst for Bantu Track, a GPS tracking company in Zimbabwe.

Your role is to analyse the GPS tracking data below and produce a professional fleet intelligence report in the exact style of the sample Bantu Track Vehicle Tracking Report.

## Report Details
- Client: {$client->name}
- Analysis period: {$from->format('d M Y')} to {$to->format('d M Y')} ({$days} days)
- Report generated: {$this->today()}

## DATA TO ANALYSE

### PERFORMANCE METRICS
{$performanceTable}

### VEHICLE BREAKDOWN
{$vehicleTable}

### SPEEDING INCIDENTS (vehicles exceeding {$this->speedLimitKmh} km/h)
{$speedingTable}

### WEEKEND & HOLIDAY DRIVING
{$weekendVehicles}

### AFTER HOURS DRIVING (18:00 - 06:00)
{$afterHrsVehicles}

## REPORT STRUCTURE REQUIRED

Produce a structured report with these exact sections:

### 1. PERFORMANCE SUMMARY
Provide a concise paragraph summarising the fleet's overall performance for the period. Include total fleet size, total mileage, weekend/holiday mileage as a percentage of total, and after hours mileage as a percentage of total. Note any immediately concerning patterns.

### 2. FLEET PERFORMANCE ASSESSMENT
Rate the overall fleet performance as: EXCELLENT / GOOD / NEEDS IMPROVEMENT / POOR — with justification based on the data.

### 3. SPEEDING ANALYSIS
Analyse the speeding data. List each speeding vehicle with their top speed, frequency, and risk level. Comment on patterns — are certain vehicles repeat offenders? What are the safety implications on Zimbabwean roads where the open road limit is {$this->speedLimitKmh} km/h?

### 4. WEEKEND & HOLIDAY USAGE ANALYSIS
Analyse vehicles that drove on weekends and public holidays. Is the mileage justified for the type of organisation? Which vehicles show the highest weekend usage? Are there concerns about unauthorised personal use?

### 5. AFTER HOURS DRIVING ANALYSIS
Identify vehicles operating after 18:00. Which hour slots show the most activity? Are there vehicles operating in the early hours (midnight to 05:00) which would be most concerning? What are the safety and policy implications?

### 6. TOP RISK VEHICLES
List the top 5 highest-risk vehicles based on the combination of speeding, weekend usage, and after hours driving. For each vehicle provide a brief risk profile and recommended action.

### 7. OPERATIONAL RECOMMENDATIONS
Provide 5-8 specific, actionable recommendations for the fleet manager based on this data. Be direct and practical — what should they do this week?

### 8. COMPLIANCE SUMMARY
| Compliance Area | Status | Detail |
Rate each area as PASS / WARNING / FAIL with brief notes:
- Speed compliance
- Working hours compliance
- Weekend/holiday usage policy
- Overall fleet discipline

Keep the tone professional and direct. Use tables where appropriate. Base all analysis strictly on the data provided above — do not invent figures not present in the data.
PROMPT;
    }

    // ─── Vehicle Behaviour Profile ────────────────────────────────────────────

    public function vehicleBehaviourProfile(AfisTracker $tracker, int $days = 30): string
    {
        $from  = Carbon::now()->subDays($days)->startOfDay();
        $to    = Carbon::now()->endOfDay();
        $trips = AfisTrip::where('tracker_id', $tracker->id)->whereBetween('start_time', [$from, $to])->get();

        $totalKm       = round($trips->sum('distance_km'), 2);
        $totalTrips    = $trips->count();
        $avgSpeed      = round($trips->avg('avg_speed_kmh'), 1);
        $maxSpeed      = round($trips->max('max_speed_kmh'), 1);
        $totalHours    = round($trips->sum('duration_minutes') / 60, 1);
        $weekendKm     = round($trips->filter(fn($t) => $this->isWeekendOrHoliday(Carbon::parse($t->start_time)))->sum('distance_km'), 2);
        $afterHoursKm  = round($trips->filter(fn($t) => $this->isAfterHours(Carbon::parse($t->start_time)))->sum('distance_km'), 2);
        $speedingTrips = $trips->filter(fn($t) => $t->max_speed_kmh > $this->speedLimitKmh)->count();

        $tripList = $trips->take(30)->map(fn($t) => sprintf(
            "  %s | %s min | %.1f km | Avg %.0f km/h | Max %.0f km/h%s%s",
            Carbon::parse($t->start_time)->format('d M H:i'),
            $t->duration_minutes,
            $t->distance_km,
            $t->avg_speed_kmh,
            $t->max_speed_kmh,
            $t->max_speed_kmh > $this->speedLimitKmh ? ' ⚠ SPEEDING' : '',
            $this->isAfterHours(Carbon::parse($t->start_time)) ? ' 🌙 AFTER HOURS' : ''
        ))->implode("\n");

        return <<<PROMPT
You are a fleet intelligence analyst for Bantu Track, a GPS tracking company in Zimbabwe.

Analyse the GPS trip data below for a single vehicle and produce a professional vehicle behaviour report.

## Vehicle: {$tracker->label}
## Client: {$tracker->client?->name}
## Period: {$from->format('d M Y')} to {$to->format('d M Y')} ({$days} days)

## VEHICLE DATA SUMMARY
- Total trips: {$totalTrips}
- Total distance: {$totalKm} km
- Total hours driven: {$totalHours} h
- Average speed: {$avgSpeed} km/h
- Maximum speed recorded: {$maxSpeed} km/h
- Speed limit (Zimbabwe open road): {$this->speedLimitKmh} km/h
- Speeding trips (>{$this->speedLimitKmh} km/h): {$speedingTrips} of {$totalTrips}
- Weekend/holiday driving: {$weekendKm} km
- After hours driving (18:00-06:00): {$afterHoursKm} km

## TRIP LOG (most recent 30 trips)
{$tripList}

## REPORT SECTIONS REQUIRED

### 1. VEHICLE SUMMARY
Brief overview of this vehicle's activity and key findings.

### 2. DRIVING BEHAVIOUR RATING
Rate as: EXCELLENT / GOOD / NEEDS IMPROVEMENT / POOR — with justification.

### 3. SPEED COMPLIANCE
Detail any speeding incidents. What is the highest speed recorded? On what type of road/context (if determinable)? What is the risk level?

### 4. WORKING HOURS COMPLIANCE
Comment on weekend/holiday usage and after hours driving. Is the level acceptable for this type of vehicle/organisation?

### 5. TRIP PATTERNS
What patterns do you observe? Regular routes? Unusual trip times? Long idle periods between trips?

### 6. RISK ASSESSMENT
Overall risk score: X/10 — with justification based strictly on the data.

### 7. RECOMMENDED ACTIONS
3-5 specific actions for the fleet manager regarding this vehicle.

Base all analysis strictly on the data provided. Do not invent figures.
PROMPT;
    }

    // ─── Predictive Intelligence ──────────────────────────────────────────────

    public function predictiveIntelligence(Client $client, int $days = 90): string
    {
        $from     = Carbon::now()->subDays($days)->startOfDay();
        $to       = Carbon::now()->endOfDay();
        $trackers = AfisTracker::where('client_id', $client->id)->get();

        // Month-by-month breakdown
        $monthlyData = [];
        $months = collect(CarbonPeriod::create($from, '1 month', $to));

        foreach ($months as $monthStart) {
            $monthEnd = $monthStart->copy()->endOfMonth();
            $trips    = AfisTrip::whereIn('tracker_id', $trackers->pluck('id'))
                ->whereBetween('start_time', [$monthStart, $monthEnd])
                ->get();

            $monthlyData[] = [
                'month'      => $monthStart->format('M Y'),
                'trips'      => $trips->count(),
                'km'         => round($trips->sum('distance_km'), 1),
                'max_speed'  => round($trips->max('max_speed_kmh'), 0),
                'speeding'   => $trips->filter(fn($t) => $t->max_speed_kmh > $this->speedLimitKmh)->count(),
                'weekend_km' => round($trips->filter(fn($t) => $this->isWeekendOrHoliday(Carbon::parse($t->start_time)))->sum('distance_km'), 1),
                'after_hrs'  => round($trips->filter(fn($t) => $this->isAfterHours(Carbon::parse($t->start_time)))->sum('distance_km'), 1),
            ];
        }

        $trendTable = "Month | Trips | Total KM | Max Speed | Speeding Trips | Weekend KM | After Hours KM\n";
        $trendTable .= str_repeat('-', 90) . "\n";
        foreach ($monthlyData as $m) {
            $trendTable .= sprintf(
                "%s | %d | %.1f | %.0f km/h | %d | %.1f | %.1f\n",
                $m['month'], $m['trips'], $m['km'], $m['max_speed'], $m['speeding'], $m['weekend_km'], $m['after_hrs']
            );
        }

        return <<<PROMPT
You are a predictive fleet intelligence analyst for Bantu Track, a GPS tracking company in Zimbabwe.

Using the 90-day historical GPS tracking data below, produce a predictive intelligence report for the next 30 and 60 days.

## Client: {$client->name}
## Historical period: {$from->format('d M Y')} to {$to->format('d M Y')} (90 days)
## Vehicles tracked: {$trackers->count()}

## MONTHLY TREND DATA
{$trendTable}

## REPORT SECTIONS REQUIRED

### 1. TREND SUMMARY
What are the key trends visible across the 3-month period? Is performance improving, declining, or stable?

### 2. 30-DAY FORECAST
Based on the trend data, what should the fleet manager expect in the next 30 days? What risks are likely to materialise?

### 3. 60-DAY FORECAST
Medium-term predictions. What trajectory is the fleet on? What will happen if current trends continue?

### 4. RISK TRAJECTORY
Is the fleet's risk profile trending UP (worsening), DOWN (improving), or FLAT (stable)? Justify with specific data points from the trend.

### 5. EARLY WARNING INDICATORS
What specific warning signs are visible in the data that suggest problems ahead? List each with its supporting data.

### 6. RECOMMENDED INTERVENTIONS
What specific interventions should the fleet manager make NOW to prevent predicted problems? Rank by urgency.

### 7. KEY PERFORMANCE INDICATORS TO MONITOR
What 5 KPIs should the fleet manager track closely over the next 60 days, and what thresholds should trigger escalation?

Base predictions strictly on the trend data provided. Distinguish clearly between what the data shows and what is projected.
PROMPT;
    }

    // ─── Incident Analysis ────────────────────────────────────────────────────

    public function incidentAnalysis(AfisTracker $tracker, string $incidentDescription, string $incidentDate): string
    {
        $incidentCarbon = Carbon::parse($incidentDate);
        $dayBefore      = $incidentCarbon->copy()->subDay();
        $dayAfter       = $incidentCarbon->copy()->addDay();

        $tripsAround  = AfisTrip::where('tracker_id', $tracker->id)
            ->whereBetween('start_time', [$dayBefore, $dayAfter])
            ->orderBy('start_time')
            ->get();

        $tripLog = $tripsAround->map(fn($t) => sprintf(
            "  %s | %s min | %.1f km | Avg %.0f km/h | Max %.0f km/h%s",
            Carbon::parse($t->start_time)->format('d M H:i'),
            $t->duration_minutes,
            $t->distance_km,
            $t->avg_speed_kmh,
            $t->max_speed_kmh,
            $t->max_speed_kmh > $this->speedLimitKmh ? ' ⚠ SPEEDING' : ''
        ))->implode("\n");

        $tripsOnDay = $tripsAround->filter(fn($t) =>
            Carbon::parse($t->start_time)->toDateString() === $incidentCarbon->toDateString()
        );

        $maxSpeedOnDay = round($tripsOnDay->max('max_speed_kmh'), 0);
        $totalKmOnDay  = round($tripsOnDay->sum('distance_km'), 1);
        $tripsOnDayCount = $tripsOnDay->count();

        return <<<PROMPT
You are a fleet incident analyst for Bantu Track, a GPS tracking company in Zimbabwe.

Analyse the GPS tracking data surrounding this incident and produce a structured incident analysis report.

## Vehicle: {$tracker->label}
## Client: {$tracker->client?->name}
## Incident date: {$incidentDate}
## Incident description: {$incidentDescription}

## GPS DATA AROUND INCIDENT (±24 hours)

### Vehicle activity on incident day
- Trips: {$tripsOnDayCount}
- Total distance: {$totalKmOnDay} km
- Maximum speed recorded: {$maxSpeedOnDay} km/h

### Full trip log (48 hours around incident)
{$tripLog}

## REPORT SECTIONS REQUIRED

### 1. INCIDENT SUMMARY
Concise description of the incident based on the description provided and GPS context.

### 2. VEHICLE ACTIVITY TIMELINE
Reconstruct what the vehicle was doing in the 24 hours before the incident. What trips were made? What speeds were recorded?

### 3. GPS EVIDENCE ANALYSIS
What does the GPS data tell us about this incident? Is there evidence of speeding, unusual hours of operation, or abnormal driving patterns on the incident day?

### 4. CONTRIBUTING FACTORS
Based on the GPS data, what factors may have contributed to this incident? Be specific to what the data shows.

### 5. SEVERITY ASSESSMENT
Rate severity as: MINOR / MODERATE / SERIOUS / CRITICAL — with justification.

### 6. DATA GAPS
What information is NOT available in the GPS data that would be needed for a full investigation?

### 7. RECOMMENDATIONS
4-5 specific actions arising from this incident. Include both immediate actions and longer-term preventive measures.

### 8. CONCLUSION
Brief conclusion and recommended follow-up.

Base all analysis strictly on the GPS data provided. Clearly distinguish between what the data confirms and what is inferred.
PROMPT;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function isWeekendOrHoliday(Carbon $date): bool
    {
        if ($date->isWeekend()) return true;
        return in_array($date->toDateString(), $this->zimbabweHolidays2026);
    }

    private function isAfterHours(Carbon $date): bool
    {
        $hour = (int) $date->format('H');
        return $hour >= 18 || $hour < 6;
    }

    private function today(): string
    {
        return Carbon::now()->format('d M Y');
    }

    private function formatPerformanceTable(string $clientName, int $fleetSize, float $totalKm, float $weekendKm, float $afterHrsKm): string
    {
        return <<<TABLE
| ASPECT | {$clientName} |
|--------|---------------|
| Fleet Size (vehicles) | {$fleetSize} |
| Total Mileage (km) | {$totalKm} |
| Weekends and Holidays (km) | {$weekendKm} |
| After Hours (km) | {$afterHrsKm} |
TABLE;
    }

    private function formatVehicleTable(array $vehicleData): string
    {
        if (empty($vehicleData)) return "No vehicle activity recorded in this period.";

        $table = "| Vehicle | Trips | Total KM | Max Speed | Weekend KM | After Hours KM |\n";
        $table .= "|---------|-------|----------|-----------|------------|----------------|\n";

        foreach (array_slice($vehicleData, 0, 50) as $v) {
            $speedFlag = $v['max_speed'] > $this->speedLimitKmh ? ' ⚠' : '';
            $table .= sprintf(
                "| %s | %d | %.1f | %.0f km/h%s | %.1f | %.1f |\n",
                $v['label'], $v['trips'], $v['mileage'], $v['max_speed'], $speedFlag, $v['weekend_km'], $v['after_hrs']
            );
        }

        if (count($vehicleData) > 50) {
            $table .= sprintf("\n... and %d more vehicles\n", count($vehicleData) - 50);
        }

        return $table;
    }

    private function formatSpeedingTable(array $speedingVehicles): string
    {
        if (empty($speedingVehicles)) {
            return "No speeding incidents recorded (all vehicles below {$this->speedLimitKmh} km/h).";
        }

        usort($speedingVehicles, fn($a, $b) => $b['top_speed'] <=> $a['top_speed']);

        $table = "| Vehicle | Top Speed | Speeding Trips |\n";
        $table .= "|---------|-----------|----------------|\n";

        foreach ($speedingVehicles as $v) {
            $table .= sprintf("| %s | %.0f km/h | %d |\n", $v['label'], $v['top_speed'], $v['count']);
        }

        return $table;
    }

    private function formatWeekendVehicles(array $vehicleData): string
    {
        $weekendVehicles = array_filter($vehicleData, fn($v) => $v['weekend_km'] > 0);

        if (empty($weekendVehicles)) {
            return "No weekend or holiday driving recorded.";
        }

        usort($weekendVehicles, fn($a, $b) => $b['weekend_km'] <=> $a['weekend_km']);

        $table = "| Vehicle | Weekend/Holiday KM |\n";
        $table .= "|---------|-------------------|\n";

        foreach (array_slice($weekendVehicles, 0, 30) as $v) {
            $table .= sprintf("| %s | %.1f |\n", $v['label'], $v['weekend_km']);
        }

        return $table;
    }

    private function formatAfterHoursVehicles(array $vehicleData): string
    {
        $afterHrsVehicles = array_filter($vehicleData, fn($v) => $v['after_hrs'] > 0);

        if (empty($afterHrsVehicles)) {
            return "No after hours driving recorded.";
        }

        usort($afterHrsVehicles, fn($a, $b) => $b['after_hrs'] <=> $a['after_hrs']);

        $table = "| Vehicle | After Hours KM |\n";
        $table .= "|---------|---------------|\n";

        foreach (array_slice($afterHrsVehicles, 0, 30) as $v) {
            $table .= sprintf("| %s | %.1f |\n", $v['label'], $v['after_hrs']);
        }

        return $table;
    }
    
    
    public function fleetIntelligenceFromData(array $data): string
    {
        $client     = $data['client'];
        $from       = $data['from']->format('d M Y');
        $to         = $data['to']->format('d M Y');
        $fleetSize  = $data['fleet_size'];
        $totalKm    = number_format($data['total_mileage'], 2);
        $weekendKm  = number_format($data['weekend_km'], 2);
        $afterHrsKm = number_format($data['after_hrs_km'], 2);
        $speedLimit = $data['speed_limit'];

        $weekendPct  = $data['total_mileage'] > 0
            ? number_format(($data['weekend_km'] / $data['total_mileage']) * 100, 1)
            : '0';
        $afterHrsPct = $data['total_mileage'] > 0
            ? number_format(($data['after_hrs_km'] / $data['total_mileage']) * 100, 1)
            : '0';

        $speedingCount  = count($data['speeding']);
        $speedingList   = collect($data['speeding'])->take(10)->map(fn($v) =>
            "  - {$v['label']}: top speed {$v['max_speed']} km/h, {$v['speeding_trips']} speeding trips"
        )->implode("\n");

        $weekendList = collect($data['weekend'])->take(10)->map(fn($v) =>
            "  - {$v['label']}: {$v['weekend_km']} km ({$v['group']})"
        )->implode("\n");

        $afterHrsList = collect($data['after_hours'])->take(10)->map(fn($v) =>
            "  - {$v['label']}: {$v['after_hrs_km']} km ({$v['group']})"
        )->implode("\n");

        $fuelList = !empty($data['fuel'])
            ? collect($data['fuel'])->take(10)->map(fn($v) =>
                "  - {$v['label']}: {$v['fueling_count']} refuels ({$v['fueling_litres']}L)" .
                ($v['drain_count'] > 0 ? ", {$v['drain_count']} DRAINS ({$v['drain_litres']}L)" : '')
            )->implode("\n")
            : "  No fuel sensor data available for this period.";

        $groupList = collect($data['groups'])->map(fn($g) =>
            "  - {$g['name']}: {$g['count']} vehicles, {$g['mileage']} km, {$g['speeding']} speeding trips"
        )->implode("\n");

        return <<<PROMPT
    You are a professional fleet intelligence analyst for Bantu Track, a GPS tracking company in Zimbabwe.

    Analyse the following fleet data and produce a concise, professional AI intelligence report. Keep it focused and actionable — maximum 2 pages when printed.

    ## CLIENT: {$client->name}
    ## PERIOD: {$from} to {$to}

    ## KEY DATA

    **Fleet Overview:**
    - Total vehicles: {$fleetSize}
    - Total mileage: {$totalKm} km
    - Weekend/holiday driving: {$weekendKm} km ({$weekendPct}% of total)
    - After hours driving (18:00-06:00): {$afterHrsKm} km ({$afterHrsPct}% of total)
    - Vehicles exceeding speed limit ({$speedLimit} km/h): {$speedingCount}

    **Speeding vehicles (top 10):**
    {$speedingList}

    **Weekend/holiday driving (top 10):**
    {$weekendList}

    **After hours driving (top 10):**
    {$afterHrsList}

    **Fuel events:**
    {$fuelList}

    **Sub-group breakdown:**
    {$groupList}

    ## REQUIRED SECTIONS

    ### 1. EXECUTIVE SUMMARY
    2-3 paragraphs. What does this data tell us about how this fleet was managed this period? Be direct.

    ### 2. KEY RISKS IDENTIFIED
    List the 3-5 most significant risks in order of severity. Each risk: what it is, which vehicles, what it means for the organisation.

    ### 3. COMPLIANCE SCORECARD
    | Area | Score | Notes |
    Rate each: ✅ PASS | ⚠ WARNING | ❌ FAIL
    - Speed policy compliance
    - Working hours policy
    - Weekend/holiday usage
    - Fuel management (if applicable)
    - Overall fleet discipline

    ### 4. PRIORITY ACTIONS
    Exactly 5 actions the fleet manager should take THIS WEEK. Number them. Be specific — name vehicles where relevant.

    ### 5. FLEET RISK RATING
    Overall rating: LOW / MEDIUM / HIGH / CRITICAL — one sentence justification.

    Keep the tone professional and direct. No filler. Base everything on the data above.
    PROMPT;
        }

}