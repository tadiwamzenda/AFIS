<?php

namespace Modules\AfisEngine\Services\Prompts;

use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisTrip;
use Modules\AfisPipeline\Models\AfisEvent;
use Carbon\Carbon;

class PromptBuilder
{
    // ─── Vehicle behaviour profile ────────────────────────────────────────────

    public function vehicleBehaviourProfile(AfisTracker $tracker, int $days = 30): string
    {
        $from   = Carbon::now()->subDays($days);
        $trips  = AfisTrip::where('tracker_id', $tracker->id)->where('start_time', '>=', $from)->get();
        $events = AfisEvent::where('tracker_id', $tracker->id)->where('occurred_at', '>=', $from)->get();

        $tripStats = [
            'total_trips'        => $trips->count(),
            'total_distance_km'  => round($trips->sum('distance_km'), 1),
            'avg_speed_kmh'      => round($trips->avg('avg_speed_kmh'), 1),
            'max_speed_kmh'      => round($trips->max('max_speed_kmh'), 1),
            'avg_duration_min'   => round($trips->avg('duration_minutes'), 1),
            'total_hours_driven' => round($trips->sum('duration_minutes') / 60, 1),
        ];

        $eventBreakdown = $events->groupBy('event_type')->map->count();

        return <<<PROMPT
You are an expert fleet intelligence analyst for Bantu Track, a GPS tracking company in Zimbabwe.

Analyse the following vehicle data and produce a comprehensive behaviour profile report.

## Vehicle Information
- Tracker ID: {$tracker->navixy_tracker_id}
- Label: {$tracker->label}
- Client: {$tracker->client?->name}
- Analysis period: Last {$days} days ({$from->format('d M Y')} to {$this->today()})

## Trip Statistics
- Total trips: {$tripStats['total_trips']}
- Total distance: {$tripStats['total_distance_km']} km
- Average speed: {$tripStats['avg_speed_kmh']} km/h
- Maximum speed recorded: {$tripStats['max_speed_kmh']} km/h
- Average trip duration: {$tripStats['avg_duration_min']} minutes
- Total hours driven: {$tripStats['total_hours_driven']} hours

## Events Summary
{$this->formatEventBreakdown($eventBreakdown)}

## Required Output Structure
Provide a detailed report with these exact sections:

### 1. Executive Summary
Brief overview of vehicle behaviour and key findings (2-3 paragraphs).

### 2. Driving Behaviour Assessment
Analysis of speed patterns, trip durations, and overall driving style. Rate as: Excellent / Good / Needs Improvement / Poor.

### 3. Risk Indicators
List specific risk behaviours identified with severity (High/Medium/Low).

### 4. Usage Patterns
Analysis of when and how the vehicle is used (time of day, frequency, distance patterns).

### 5. Efficiency Analysis
Fuel efficiency implications, idle time concerns, route optimisation opportunities.

### 6. Recommendations
Specific, actionable recommendations for the fleet manager (minimum 3).

### 7. Risk Score
Overall risk score from 1-10 (1 = very low risk, 10 = critical risk). Justify the score.
PROMPT;
    }

    // ─── Fleet-wide intelligence ──────────────────────────────────────────────

    public function fleetIntelligence(Client $client, int $days = 30): string
    {
        $from     = Carbon::now()->subDays($days);
        $trackers = AfisTracker::where('client_id', $client->id)->with('trips', 'events')->get();

        $fleetStats = [
            'total_vehicles'     => $trackers->count(),
            'active_vehicles'    => $trackers->where('is_active', true)->count(),
            'total_trips'        => $trackers->sum(fn($t) => $t->trips->where('start_time', '>=', $from)->count()),
            'total_distance_km'  => round($trackers->sum(fn($t) => $t->trips->where('start_time', '>=', $from)->sum('distance_km')), 1),
            'max_speed_recorded' => round($trackers->max(fn($t) => $t->trips->where('start_time', '>=', $from)->max('max_speed_kmh')), 1),
            'total_events'       => $trackers->sum(fn($t) => $t->events->where('occurred_at', '>=', $from)->count()),
        ];

        return <<<PROMPT
You are an expert fleet intelligence analyst for Bantu Track, a GPS tracking company in Zimbabwe.

Produce a comprehensive fleet-wide intelligence report for the following client.

## Client Information
- Client: {$client->name}
- Analysis period: Last {$days} days ({$from->format('d M Y')} to {$this->today()})

## Fleet Overview
- Total vehicles tracked: {$fleetStats['total_vehicles']}
- Active vehicles: {$fleetStats['active_vehicles']}
- Total trips recorded: {$fleetStats['total_trips']}
- Total fleet distance: {$fleetStats['total_distance_km']} km
- Maximum speed recorded across fleet: {$fleetStats['max_speed_recorded']} km/h
- Total events recorded: {$fleetStats['total_events']}

## Required Output Structure

### 1. Fleet Executive Summary
High-level overview of fleet performance and key findings.

### 2. Fleet Risk Assessment
Overall fleet risk rating and breakdown of risk factors.

### 3. Top Risk Vehicles
Identify vehicles requiring immediate attention and why.

### 4. Fleet Performance Trends
Analysis of fleet-wide patterns over the period.

### 5. Operational Insights
Key observations about fleet utilisation and efficiency.

### 6. Compliance Summary
Assessment of policy compliance (speed limits, operating hours, etc.).

### 7. Strategic Recommendations
Fleet management recommendations (minimum 5 actionable items).

### 8. Fleet Risk Score
Overall fleet risk score 1-10 with justification.
PROMPT;
    }

    // ─── Incident analysis ────────────────────────────────────────────────────

    public function incidentAnalysis(
        AfisTracker $tracker,
        string      $incidentDescription,
        string      $incidentDate
    ): string {
        $incidentCarbon = Carbon::parse($incidentDate);
        $dayBefore      = $incidentCarbon->copy()->subDay();
        $dayAfter       = $incidentCarbon->copy()->addDay();

        $tripsAround  = AfisTrip::where('tracker_id', $tracker->id)
            ->whereBetween('start_time', [$dayBefore, $dayAfter])
            ->get();

        $eventsAround = AfisEvent::where('tracker_id', $tracker->id)
            ->whereBetween('occurred_at', [$dayBefore, $dayAfter])
            ->get();

        $tripSummary = $tripsAround->map(fn($t) => [
            'time'          => $t->start_time->format('d M Y H:i'),
            'distance_km'   => $t->distance_km,
            'max_speed_kmh' => $t->max_speed_kmh,
            'duration_min'  => $t->duration_minutes,
        ])->toJson(JSON_PRETTY_PRINT);

        $eventSummary = $eventsAround->map(fn($e) => [
            'time'  => $e->occurred_at->format('d M Y H:i'),
            'type'  => $e->event_type,
        ])->toJson(JSON_PRETTY_PRINT);

        return <<<PROMPT
You are an expert fleet incident analyst for Bantu Track, a GPS tracking company in Zimbabwe.

Analyse the following incident and produce a detailed structured incident report.

## Incident Details
- Vehicle: {$tracker->label}
- Tracker ID: {$tracker->navixy_tracker_id}
- Client: {$tracker->client?->name}
- Incident date: {$incidentDate}
- Description: {$incidentDescription}

## Vehicle Activity Around Incident (±24 hours)

### Trips
{$tripSummary}

### Events
{$eventSummary}

## Required Report Structure (10 sections)

### 1. Incident Summary
Concise description of the incident based on available data.

### 2. Timeline Reconstruction
Step-by-step timeline of events before, during, and after the incident.

### 3. Vehicle Behaviour Analysis
Analysis of driving patterns immediately before the incident.

### 4. Contributing Factors
Identified factors that may have contributed to the incident.

### 5. Data Evidence
Key data points from GPS tracking that are relevant to the incident.

### 6. Severity Assessment
Severity rating: Minor / Moderate / Serious / Critical — with justification.

### 7. Liability Considerations
Observations relevant to liability assessment (without legal conclusions).

### 8. Prevention Analysis
Could this incident have been prevented? What indicators were present?

### 9. Recommendations
Specific actions to prevent similar incidents (minimum 4).

### 10. Conclusion
Summary conclusion and suggested follow-up actions.
PROMPT;
    }

    // ─── Predictive intelligence ──────────────────────────────────────────────

    public function predictiveIntelligence(Client $client, int $days = 90): string
    {
        $from     = Carbon::now()->subDays($days);
        $trackers = AfisTracker::where('client_id', $client->id)->get();

        $totalTrips   = AfisTrip::whereIn('tracker_id', $trackers->pluck('id'))->where('start_time', '>=', $from)->count();
        $totalEvents  = AfisEvent::whereIn('tracker_id', $trackers->pluck('id'))->where('occurred_at', '>=', $from)->count();
        $avgMaxSpeed  = round(AfisTrip::whereIn('tracker_id', $trackers->pluck('id'))->where('start_time', '>=', $from)->avg('max_speed_kmh'), 1);

        return <<<PROMPT
You are an expert predictive fleet analyst for Bantu Track, a GPS tracking company in Zimbabwe.

Generate a predictive intelligence report for the following fleet over the next 30, 60, and 90 days.

## Client: {$client->name}
## Historical data period: Last {$days} days ({$from->format('d M Y')} to {$this->today()})

## Historical Fleet Data
- Total vehicles: {$trackers->count()}
- Total trips recorded: {$totalTrips}
- Total events recorded: {$totalEvents}
- Average maximum speed: {$avgMaxSpeed} km/h

## Required Report Structure

### 1. Predictive Summary
Overview of predicted fleet risk trajectory.

### 2. 30-Day Risk Forecast
Predicted risk level and key concerns for the next 30 days.

### 3. 60-Day Risk Forecast
Medium-term predictions and trends.

### 4. 90-Day Risk Forecast
Long-term risk trajectory and strategic considerations.

### 5. Vehicles at Risk
Vehicles predicted to develop issues based on current trends.

### 6. Accident Probability Assessment
Statistical assessment of accident probability based on driving patterns.

### 7. Deterioration Early Warning
Early warning indicators and which vehicles are showing them.

### 8. Intervention Priority List
Ranked list of interventions needed, most urgent first.

### 9. Cost Impact Projection
Projected cost impact if current trends continue vs if interventions are made.

### 10. Strategic Recommendations
Long-term fleet management strategy recommendations (minimum 5).
PROMPT;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function today(): string
    {
        return Carbon::now()->format('d M Y');
    }

    private function formatEventBreakdown($eventBreakdown): string
    {
        if ($eventBreakdown->isEmpty()) {
            return '- No events recorded in this period';
        }

        return $eventBreakdown->map(fn($count, $type) => "- {$type}: {$count}")->implode("\n");
    }
}