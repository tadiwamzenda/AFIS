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

    public function incidentAnalysis(
        AfisTracker $tracker,
        string  $incidentDescription,
        string  $incidentDate,
        ?string $tripReportText   = null,
        ?string $speedReportText  = null,
        ?string $eventsReportText = null,
    ): string
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

        if ($tripLog === '') {
            $tripLog = 'No AFIS GPS trip records found in the 24 hours surrounding this incident.';
        }

        $navixySections = '';
        if ($tripReportText) {
            $navixySections .= "\n### Navixy Trip Report (raw extract)\n" . $this->truncateForPrompt($tripReportText) . "\n";
        }
        if ($speedReportText) {
            $navixySections .= "\n### Navixy Speed Violation Report (raw extract)\n" . $this->truncateForPrompt($speedReportText) . "\n";
        }
        if ($eventsReportText) {
            $navixySections .= "\n### Navixy Events Report (raw extract)\n" . $this->truncateForPrompt($eventsReportText) . "\n";
        }
        if ($navixySections === '') {
            $navixySections = "No Navixy PDF reports were uploaded for this incident. Base the chronology and location strictly on the AFIS GPS trip log and the incident description — do not invent addresses or event names.";
        }

        $chronologyDate = $incidentCarbon->format('d.m.Y');

        return <<<PROMPT
You are a fleet incident analyst for Bantu Track, a GPS tracking company in Zimbabwe.

Produce a SHORT, PRECISE incident analysis report — maximum 2 pages when printed. No padding, no academic language, no restating the same point twice, no filler.

## Vehicle: {$tracker->label}
## Client: {$tracker->client?->name}
## Incident date: {$incidentDate}
## Incident description (as logged by the reporting user): {$incidentDescription}

## AFIS GPS TRIP LOG (±24 hours around incident)
{$tripLog}

## NAVIXY REPORT DATA (authoritative for exact addresses, timestamps and event names — prefer this over the AFIS trip log when building the chronology table)
{$navixySections}

## OUTPUT FORMAT — FOLLOW EXACTLY, OUTPUT NOTHING ELSE

On the very first line, output only:
LOCATION: <the specific street/area name where the incident occurred, taken from the Navixy data if available, otherwise the best available location from the GPS data>

Then produce exactly these 5 sections, in this order, using these exact Markdown headers:

## 1. EXECUTIVE SUMMARY
2-3 sentences maximum. What happened, in plain terms.

## 2. CHRONOLOGY OF EVENTS {$chronologyDate}
A Markdown table with exactly these columns: Time | Event | Details
Only include rows supported by the Navixy data or GPS trip log — do not invent events. Times in 24-hour HH:MM format.

## 3. ANALYSIS AND KEY FINDINGS
Use numbered sub-headings like "### 3.1 [Finding name]", "### 3.2 [Finding name]" etc. Only include findings actually supported by the data — do not pad with generic findings. Usually 2-4 findings is right; never invent one just to fill space.

## 4. CONCLUSION
2-3 sentences maximum stating what the evidence shows.

Do not include a "RECOMMENDATIONS" section — that is a fixed, standard section appended automatically by the system, not generated by you. Do not include a title, do not include "Prepared By" or "Reviewed By" lines, do not include a date header block — those are also added separately by the system. Output only the LOCATION line followed by the 4 sections above, nothing before or after.
PROMPT;
    }

    private function truncateForPrompt(string $text, int $maxChars = 6000): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }
        return mb_substr($text, 0, $maxChars) . "\n...[truncated]";
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
        $totalKm    = $data['total_mileage'];
        $speedLimit = $data['speed_limit'];

        // ── Precompute every score deterministically in PHP — the AI narrates
        // these numbers, it does not calculate them. ─────────────────────────
        $scoredVehicles = $this->scoreVehicles($data['vehicles'], $speedLimit);

        $activeVehicles = collect($scoredVehicles)->filter(fn($v) => $v['trips'] > 0);
        $activeCount    = $activeVehicles->count();
        $inMotionCount  = $activeCount; // period report: "active" and "in motion" both mean had trips this period
        $utilizationPct = $fleetSize > 0 ? round(($activeCount / $fleetSize) * 100, 1) : 0;

        $offlineCount   = $data['offline_count'] ?? 0;
        $criticalAlerts = ($data['critical_alerts'] ?? 0)
            + count($data['speeding'])
            + $offlineCount
            + collect($data['vehicles'])->sum('drain_count');

        $topPerformerOverall = $activeVehicles
            ->filter(fn($v) => $v['speeding_trips'] === 0)
            ->sortByDesc('mileage')
            ->first();

        $fleetSafetyScore = $activeCount > 0 ? (int) round($activeVehicles->avg('safety_score')) : null;
        $topSafetyVehicle = $activeVehicles->sortByDesc('safety_score')->first();

        $coachingPriorities = collect($scoredVehicles)
            ->filter(fn($v) => $v['speeding_trips'] > 0 || $v['after_hrs_km'] > 0)
            ->sortBy('safety_score')
            ->take(8)
            ->values();

        $topPerformers = collect($scoredVehicles)
            ->filter(fn($v) => $v['speeding_trips'] === 0 && $v['after_hrs_km'] == 0 && $v['mileage'] > 0)
            ->sortByDesc('mileage')
            ->take(5)
            ->values();

        $highRiskVehicles = collect($scoredVehicles)
            ->filter(fn($v) => $v['risk_score'] >= 30)
            ->sortByDesc('risk_score')
            ->take(8)
            ->values();

        $preIncidentWarnings = collect($scoredVehicles)
            ->filter(function ($v) {
                if ($v['max_speed'] <= 150) return false;
                foreach ($v['hour_breakdown'] as $slot => $km) {
                    $hour = (int) explode(':', $slot)[0];
                    if ($hour >= 0 && $hour <= 5 && $km > 0) return true;
                }
                return false;
            })
            ->values();

        $fuelVehicles = collect($data['vehicles'])->filter(fn($v) => $v['fueling_litres'] > 0 && $v['mileage'] > 0);
        $fuelEconomy  = $fuelVehicles->isNotEmpty()
            ? round(($fuelVehicles->sum('fueling_litres') / $fuelVehicles->sum('mileage')) * 100, 2)
            : null;

        $avgMileage    = $activeCount > 0 ? $activeVehicles->avg('mileage') : 0;
        $underUtilised = collect($data['vehicles'])
            ->filter(fn($v) => $v['trips'] > 0 && $avgMileage > 0 && $v['mileage'] < ($avgMileage * 0.4))
            ->sortBy('mileage')
            ->values();

        $trendPct   = $data['mileage_trend_pct'] ?? 0;
        $trendLabel = $trendPct > 0 ? "up {$trendPct}%" : ($trendPct < 0 ? 'down ' . abs($trendPct) . '%' : 'flat');

        // ── Format everything for the prompt ──────────────────────────────────
        $coachingList = $coachingPriorities->map(fn($v) =>
            "  - {$v['label']} ({$v['safety_score']}/100) — {$v['speeding_trips']} speeding events, {$v['after_hrs_km']} after-hours km"
        )->implode("\n") ?: '  None — no vehicles currently need coaching.';

        $topPerformersList = $topPerformers->map(fn($v) =>
            "  - {$v['label']} ({$v['safety_score']}/100) — {$v['trips']} trips, {$v['mileage']} km, zero speeding/after-hours events"
        )->implode("\n") ?: '  None met the criteria this period.';

        $highRiskList = $highRiskVehicles->map(fn($v) =>
            "  - {$v['label']} — risk {$v['risk_score']}/100. Max speed {$v['max_speed']} km/h, {$v['speeding_trips']} speeding trips, {$v['after_hrs_km']} after-hours km."
        )->implode("\n") ?: '  None — no vehicles scored above the risk threshold.';

        $warningsList = $preIncidentWarnings->map(fn($v) =>
            "  - AFIS Warning: {$v['label']} — {$v['max_speed']} km/h recorded with driving activity between midnight and 06:00."
        )->implode("\n") ?: '  None detected this period.';

        $sortedGroups = collect($data['groups'])->sortByDesc('count')->values();
        $groupList    = $sortedGroups->take(15)->map(fn($g) =>
            "  - {$g['name']}: {$g['count']} vehicles"
        )->implode("\n");
        if ($sortedGroups->count() > 15) {
            $groupList .= "\n  ... and " . ($sortedGroups->count() - 15) . ' more groups ('
                . $sortedGroups->slice(15)->sum('count') . ' additional vehicles across smaller sub-groups)';
        }

        $underUtilisedList = $underUtilised->take(15)->map(fn($v) =>
            "  - {$v['label']} — {$v['mileage']} km (fleet active average: " . round($avgMileage, 1) . ' km)'
        )->implode("\n") ?: '  None identified — utilisation is broadly even across the active fleet.';
        if ($underUtilised->count() > 15) {
            $underUtilisedList .= "\n  ... and " . ($underUtilised->count() - 15) . ' more under-utilised vehicles';
        }
        $drainList = collect($data['vehicles'])->filter(fn($v) => $v['drain_count'] > 0)->map(fn($v) =>
            "  - {$v['label']}: {$v['drain_count']} drain event(s), {$v['drain_litres']}L"
        )->implode("\n") ?: '  None detected this period.';

        $fuelEconomyLine = $fuelEconomy !== null
            ? "{$fuelEconomy} L/100km (fleet average, from vehicles with fuel sensor data)"
            : 'Not available — no fuel sensor data recorded for this period.';

        $fleetSafetyLine = $fleetSafetyScore !== null
            ? "{$fleetSafetyScore}/100"
            : 'Not available — no active vehicles this period.';

        return <<<PROMPT
You are a professional fleet intelligence analyst for Bantu Track, a GPS tracking company in Zimbabwe.

Produce the AI Fleet Intelligence Report using EXACTLY the 5-section structure below. Every number under "COMPUTED DATA" is authoritative and already correctly calculated — use it exactly as given. Do not recompute anything, do not invent numbers not present here. Your job is to narrate and interpret this data professionally, not to do arithmetic.

## CLIENT: {$client->name}
## PERIOD: {$from} to {$to}

## COMPUTED DATA

**Fleet overview:**
- Fleet size: {$fleetSize}
- Total distance this period: {$totalKm} km (trend vs previous period: {$trendLabel})
- Active vehicles (had trips): {$activeCount} / {$fleetSize}
- Vehicles in motion this period: {$inMotionCount}
- Fleet utilization: {$utilizationPct}%
- Offline units: {$offlineCount}
- Critical alerts (speeding + offline + fuel drains): {$criticalAlerts}
- Top performer (most mileage, zero speeding): {$this->describeVehicle($topPerformerOverall)}

**Geographic / group breakdown:**
{$groupList}

**Fleet-wide safety score:** {$fleetSafetyLine}
**Top safety score vehicle:** {$this->describeVehicle($topSafetyVehicle, 'safety_score')}

**Coaching priorities (speeding and/or after-hours vehicles, worst safety score first):**
{$coachingList}

**Top performers (high mileage, zero speeding, zero after-hours):**
{$topPerformersList}

**Fuel economy:**
{$fuelEconomyLine}

**Under-utilised vehicles (mileage below 40% of active fleet average):**
{$underUtilisedList}

**Fuel drain events:**
{$drainList}

**High-risk vehicles (sorted by risk score):**
{$highRiskList}

**Pre-incident warnings (speed >150 km/h combined with midnight-06:00 activity):**
{$warningsList}

## REQUIRED REPORT STRUCTURE — FOLLOW EXACTLY

### SECTION 1 — DAILY FLEET SUMMARY
- One line: {$client->name} ({$fleetSize}) vehicle fleet
- One line: {$totalKm} km / {$activeCount} active / {$inMotionCount} in motion
- Fleet health: one sentence assessment (EXCELLENT / GOOD / NEEDS ATTENTION / POOR) with brief justification
- Operational Highlights: 4 bullet points covering utilization %, active vs idling split, the named top performer, and the offline unit count
- AI Observation: 1-2 sentences on the trend vs previous period and any notable pattern
- Geographic Breakdown: list each group with its vehicle count, from the data above

### SECTION 2 — DRIVER BEHAVIOUR ANALYSIS
- Fleet-wide safety score: state exactly as given above
- Vehicles needing coaching: state the count of vehicles in the Coaching Priorities list above
- Top score: name the vehicle given above as the top safety score vehicle
- Coaching Priorities: list each vehicle from the data above, each ending "Recommend coaching."
- Top Performers: list each vehicle from the data above
- Behavioural Pattern: 1-2 sentences inferring when/where harsh driving events cluster, based on the after-hours and speeding data given

### SECTION 3 — FLEET EFFICIENCY REPORT
- Fleet utilization: state exactly as given above
- Avg fuel economy: state exactly as given above (or note it's unavailable if so)
- Total idle estimate: one sentence inference based on the gap between active and total fleet size — do not invent a specific idle-hours figure not derivable from the data above
- Efficiency Findings: list the under-utilised vehicles given above as redeployment candidates; list the fuel drain events given above
- Efficiency Opportunity: 1-2 sentence recommendation based on the patterns above

### SECTION 4 — RISK ASSESSMENT
- Fleet composite risk: LOW / MEDIUM / HIGH / CRITICAL, based on the High-Risk Vehicles count and severity given above
- High-risk units: state the count from the High-Risk Vehicles list above
- Pre-incident warnings: state the count from the Pre-Incident Warnings list above
- High-Risk Vehicles: list each with its risk score and a one-line reason drawn from the data, ending "Review recommended."
- Pre-Incident Warnings: list each exactly as given above. Do not expand, define, or rename the "AFIS" acronym anywhere in this report — if referenced, it refers only to Bantu Track's Fleet Intelligence System, nothing else. Do not invent an alternate meaning.
- Predictive Insight: 1-2 sentences on where risk is concentrated and where intervention would have the most leverage

### SECTION 5 — OPERATIONAL RECOMMENDATIONS
Produce exactly 4 numbered, prioritised actions ranked by impact-to-effort:
1. Reconnect the offline units (name the count from the data above) — connectivity or operational issue
2. Review the critical alerts (name the count from the data above) — speeding, fuel drains, after-hours combined
3. Schedule coaching for the vehicles below the safety threshold (name the count from Coaching Priorities above)
4. One additional recommendation you infer directly from the specific patterns in the data above — must reference actual vehicles or groups, not generic advice

End the report with a single line: "FLEET HEALTH VERDICT: " followed by EXCELLENT / GOOD / NEEDS ATTENTION / POOR and a one-sentence justification.

Keep every section concise — bullet points preferred over paragraphs. If a data section above says "None" or "Not available", say so plainly rather than inventing content. Do not repeat the raw data tables — narrate and interpret them.
PROMPT;
    }

    private function scoreVehicles(array $vehicles, int $speedLimit): array
    {
        return array_map(function ($v) use ($speedLimit) {
            $mileage = max($v['mileage'], 0.01); // avoid div-by-zero for parked vehicles

            $safetyScore = 100
                - ($v['speeding_trips'] * 10)
                - (($v['after_hrs_km'] / $mileage) * 20);
            $v['safety_score'] = (int) max(0, min(100, round($safetyScore)));

            $riskScore = (($v['max_speed'] / $speedLimit) * 40)
                + (($v['after_hrs_km'] / $mileage) * 30)
                + ($v['speeding_trips'] * 30);
            $v['risk_score'] = (int) max(0, min(100, round($riskScore)));

            return $v;
        }, $vehicles);
    }

    private function describeVehicle(?array $v, string $scoreKey = null): string
    {
        if (!$v) return 'None identified this period.';

        if ($scoreKey && isset($v[$scoreKey])) {
            return "{$v['label']} ({$v[$scoreKey]}/100)";
        }

        return "{$v['label']} — {$v['mileage']} km, zero speeding events";
    }

}
