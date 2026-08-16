<?php

namespace Modules\Notifications\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Models\AfisFuelDaily;
use Modules\AfisPipeline\Models\AfisSyncLog;
use Modules\Notifications\Models\AfisNotification;
use Modules\Notifications\Services\NotificationService;

class CheckAlertsCommand extends Command
{
    protected $signature   = 'afis:check-alerts';
    protected $description = 'Check AFIS system for alert conditions and fire notifications';

    /**
     * Datetime-cast model attributes get tagged with config('app.timezone')
     * (UTC) by Eloquent on read, regardless of what timezone the value was
     * actually written in. Carbon::parse($castAttribute, 'Africa/Harare')
     * silently IGNORES the timezone argument when given an already-cast
     * DateTimeInterface — it only applies to raw strings. Bypasses the
     * cast and parses the raw stored string directly, so the timezone
     * argument actually takes effect.
     */
    private function harareTime($model, string $attribute): ?Carbon
    {
        $raw = $model->getRawOriginal($attribute);
        return $raw ? Carbon::parse($raw, 'Africa/Harare') : null;
    }

    public function handle(NotificationService $notifications): void
    {
        $this->info('Checking AFIS alerts...');
        $fired = 0;
        $now = Carbon::now('Africa/Harare');

    // ── 1. Vehicle offline alerts + incident tracking ─────────────────────
    $this->line('  → Checking vehicle offline status...');

    $allTrackers     = AfisTracker::where('client_id', '!=', 21)->get();
    $offlineTrackers = $allTrackers->where('online_status', 'offline');
    $onlineTrackers  = $allTrackers->where('online_status', 'online');

    $this->line("    → Found {$offlineTrackers->count()} offline trackers");

    $clientIds = $offlineTrackers->pluck('client_id')->unique()->toArray();
    $clients   = Client::whereIn('id', $clientIds)->get()->keyBy('id');
    $today     = $now->toDateString();

    // Warning/Severe still fire once per day each — unchanged behaviour,
    // just now sourced from the incident's accurate went_offline_at.
    $alreadyFiredTiered = AfisNotification::whereIn('type', [
            'vehicle.immediately_offline',
            'vehicle.extended_offline',
        ])
        ->whereDate('created_at', $today)
        ->get()
        ->map(fn($n) => $n->data['tracker_id'] ?? null)
        ->filter()
        ->flip()
        ->toArray();

    foreach ($offlineTrackers as $tracker) {
        $incident = \Modules\AfisPipeline\Models\AfisOfflineIncident::where('tracker_id', $tracker->id)
            ->open()
            ->first();

        // Stale-incident check: if the tracker's own status has changed
        // MORE RECENTLY than this incident's recorded start, a full
        // online→offline cycle happened that we never observed (both
        // transitions fell between check-alerts runs — the tracker was
        // online again and offline again before this run ever caught it
        // "online" to close the original incident). The incident is stale
        // regardless of current status; close it using the tracker's own
        // timestamp and let a fresh one open below.
        if ($incident) {
            $statusChangedAt = $this->harareTime($tracker, 'online_status_changed_at');
            $incidentStart   = $this->harareTime($incident, 'went_offline_at');

            if ($statusChangedAt && $incidentStart && $statusChangedAt->gt($incidentStart)) {
                $incident->update(['came_online_at' => $statusChangedAt]);

                if ($incident->notification_id) {
                    AfisNotification::where('id', $incident->notification_id)
                        ->update(['status' => 'attended', 'status_changed_at' => $now]);
                }

                $incident = null; // force a fresh incident to open below
            }
        }

        if (!$incident) {
            $wentOfflineAt = $tracker->online_status_changed_at;

            if (!$wentOfflineAt) {
                // No precise transition timestamp (tracker was already
                // offline before this feature existed). Best available
                // historical proxy: the last recorded 'offline' alert
                // event — imperfect for determining CURRENT status
                // (confirmed 40% unreliable earlier today), but for a
                // genuinely past event its own timestamp is a reasonable
                // approximation, far better than "just now" for a vehicle
                // that's actually been offline for weeks.
                $lastOfflineAlert = \Modules\AfisPipeline\Models\AfisDeviceAlert::where('tracker_id', $tracker->id)
                    ->where('event_type', 'offline')
                    ->orderByDesc('occurred_at')
                    ->first();

                $wentOfflineAt = $lastOfflineAlert
                    ? Carbon::parse($lastOfflineAlert->occurred_at, 'Africa/Harare')
                    : $now; // last resort — genuinely no historical data exists
            }

            $incident = \Modules\AfisPipeline\Models\AfisOfflineIncident::create([
                'tracker_id'      => $tracker->id,
                'client_id'       => $tracker->client_id,
                'went_offline_at' => $wentOfflineAt,
            ]);
        } else {
            // Self-heal: an existing open incident may have been created
            // before this historical-fallback logic existed (stamped
            // 'now()' instead of the real offline time). If a real offline
            // alert on record is EARLIER than what this incident currently
            // shows, correct it. Safe to run on every check-alerts cycle —
            // this only ever moves the timestamp earlier/more accurate,
            // never later, so there's no risk of drifting a correct value.
            $lastOfflineAlert = \Modules\AfisPipeline\Models\AfisDeviceAlert::where('tracker_id', $tracker->id)
                ->where('event_type', 'offline')
                ->orderByDesc('occurred_at')
                ->first();

            if ($lastOfflineAlert) {
                $alertTime = Carbon::parse($lastOfflineAlert->occurred_at, 'Africa/Harare');
                if ($alertTime->lt($incident->went_offline_at)) {
                    $incident->update(['went_offline_at' => $alertTime]);
                }
            }
        }

        $client       = $clients->get($tracker->client_id);
        $offlineSince = Carbon::parse($incident->went_offline_at, 'Africa/Harare');
        $offlineHours = (int) abs($now->diffInHours($offlineSince));
        $offlineDays  = (int) abs($now->diffInDays($offlineSince));
        $duration     = $offlineDays >= 1
            ? "{$offlineDays}d " . ($offlineHours % 24) . "h"
            : "{$offlineHours}h";

        // Warning / Severe — same fire-once-per-day rule and labels as
        // before, just accurate duration now.
        if (!isset($alreadyFiredTiered[$tracker->id])) {
            $severity = $offlineHours >= 24 ? 'severe' : 'warning';
            $type     = $offlineHours >= 24 ? 'vehicle.extended_offline' : 'vehicle.immediately_offline';

            $notifications->record(
                type:     $type,
                title:    "Vehicle offline — {$tracker->label}",
                message:  "{$tracker->label} ({$client?->name}) offline for {$duration}.",
                module:   'AfisPipeline',
                severity: $severity,
                data:     [
                    'tracker_id'    => $tracker->id,
                    'tracker_label' => $tracker->label,
                    'client_id'     => $tracker->client_id,
                    'client'        => $client?->name,
                    'offline_since' => $offlineSince->toDateTimeString(),
                    'offline_hours' => $offlineHours,
                    'offline_days'  => $offlineDays,
                ],
            );
            $fired++;
        }

        // Critical — fires ONCE per incident (not daily), pending/attended
        // lifecycle, and ONLY once a human has marked the incident
        // "Functional". Time passing alone (7 days, 30 days, however long)
        // never fires this on its own — it requires review first.
        if ($offlineDays >= 7 && $incident->comment === 'Functional' && !$incident->notification_id) {
            $criticalNotification = $notifications->record(
                type:     'vehicle.critically_offline',
                title:    "Vehicle offline — {$tracker->label}",
                message:  "{$tracker->label} ({$client?->name}) offline for {$duration}. Marked Functional — requires attention.",
                module:   'AfisPipeline',
                severity: 'critical',
                data:     [
                    'tracker_id'    => $tracker->id,
                    'tracker_label' => $tracker->label,
                    'client_id'     => $tracker->client_id,
                    'client'        => $client?->name,
                    'offline_since' => $offlineSince->toDateTimeString(),
                    'offline_hours' => $offlineHours,
                    'offline_days'  => $offlineDays,
                    'incident_id'   => $incident->id,
                ],
            );

            $criticalNotification->update(['status' => 'pending', 'status_changed_at' => $now]);
            $incident->update(['notification_id' => $criticalNotification->id]);
            $fired++;
            $this->line("    ✓ critical (pending): {$tracker->label} — offline {$duration}, marked Functional");
        }
    }

    // ── 1b. Close incidents for trackers back online; mark any linked
    // pending Critical notification as attended. This is what makes the
    // notification lifecycle and the States dashboard synchronous — both
    // read the same incident row, closed by the same event.
    $closedCount = 0;
    foreach ($onlineTrackers as $tracker) {
        $incident = \Modules\AfisPipeline\Models\AfisOfflineIncident::where('tracker_id', $tracker->id)
            ->open()
            ->first();

        if (!$incident) continue;

        $incident->update(['came_online_at' => $tracker->online_status_changed_at ?? $now]);

        if ($incident->notification_id) {
            AfisNotification::where('id', $incident->notification_id)
                ->update(['status' => 'attended', 'status_changed_at' => $now]);
        }

        $closedCount++;
    }

    if ($closedCount > 0) {
        $this->line("    → Closed {$closedCount} offline incident(s) — vehicle(s) back online");
    }

    $this->line("    → Fired {$fired} offline-related notifications this run");

        // ── 2. Fuel drain alerts ──────────────────────────────────────────────
        $this->line('  → Checking fuel drain events...');

        $drains = AfisFuelDaily::where('has_drain', true)
            ->where('date', '>=', $now->copy()->subDay()->toDateString())
            ->where('date', '<=', $now->toDateString())
            ->get();

        foreach ($drains as $drain) {
            $tracker = AfisTracker::find($drain->tracker_id);
            $client  = Client::find($drain->client_id);

            $recentlySent = AfisNotification::where('type', 'vehicle.fuel_drain')
                ->where('data->tracker_id', $drain->tracker_id)
                ->where('data->date', $drain->date->toDateString())
                ->exists();

            if ($recentlySent) continue;

            $notifications->record(
                type:     'vehicle.fuel_drain',
                title:    "Fuel drain detected — {$drain->vehicle_label}",
                message:  "Suspected fuel drain on {$drain->vehicle_label} ({$client?->name}) on {$drain->date->format('d M Y')}. Drain volume: {$drain->drain_litres}L.",
                module:   'AfisPipeline',
                severity: 'critical',
                data:     [
                    'tracker_id'    => $drain->tracker_id,
                    'vehicle_label' => $drain->vehicle_label,
                    'client'        => $client?->name,
                    'date'          => $drain->date->toDateString(),
                    'drain_litres'  => $drain->drain_litres,
                ],
            );
            $fired++;
            $this->line("    ✓ critical: Fuel drain on {$drain->vehicle_label} — {$drain->drain_litres}L");
        }

        // ── 3. Pipeline sync failures ─────────────────────────────────────────
        $this->line('  → Checking pipeline sync failures...');

        $failedSyncs = DB::table('afis_sync_logs')
            ->where('status', 'failed')
            ->where('created_at', '>=', $now->copy()->subHour())
            ->get();

        foreach ($failedSyncs as $log) {
            $client = Client::find($log->client_id);

            $recentlySent = AfisNotification::where('type', 'pipeline.sync_failed')
                ->where('data->sync_log_id', $log->id)
                ->exists();

            if ($recentlySent) continue;

            $notifications->record(
                type:     'pipeline.sync_failed',
                title:    "Pipeline sync failed — {$client?->name}",
                message:  "Navixy data sync failed for {$client?->name}. Error: " . ($log->error_message ?? 'Unknown error'),
                module:   'AfisPipeline',
                severity: 'critical',
                data:     [
                    'sync_log_id' => $log->id,
                    'client'      => $client?->name,
                    'client_id'   => $log->client_id,
                    'error'       => $log->error_message ?? 'Unknown error',
                ],
            );
            $fired++;
            $this->line("    ✓ critical: Sync failed for {$client?->name}");
        }

        

        // ── 4. New trackers discovered ────────────────────────────────────────
        $this->line('  → Checking new trackers...');

        $newTrackers = AfisTracker::where('created_at', '>=', $now->copy()->subDay())
            ->where('client_id', '!=', 21)
            ->get();

        if ($newTrackers->count() > 0) {
            $recentlySent = AfisNotification::where('type', 'system.new_trackers')
                ->where('created_at', '>=', $now->copy()->subDay())
                ->exists();

            if (!$recentlySent) {
                $clientNames = $newTrackers->map(fn($t) => Client::find($t->client_id)?->name)
                    ->filter()->unique()->implode(', ');

                $notifications->record(
                    type:     'system.new_trackers',
                    title:    "{$newTrackers->count()} new tracker(s) discovered",
                    message:  "{$newTrackers->count()} new GPS trackers were added to the system for: {$clientNames}.",
                    module:   'AfisPipeline',
                    severity: 'info',
                    data:     [
                        'count'   => $newTrackers->count(),
                        'clients' => $clientNames,
                    ],
                );
                $fired++;
                $this->line("    ✓ info: {$newTrackers->count()} new trackers discovered");
            }
        }

        $this->info("Done. Fired {$fired} notifications.");
    }
}