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

    public function handle(NotificationService $notifications): void
    {
        $this->info('Checking AFIS alerts...');
        $fired = 0;
        $now = Carbon::now('Africa/Harare');

    // ── 1. Vehicle offline alerts (from real-time online_status) ─────────
    $this->line('  → Checking vehicle offline status...');

    $offlineTrackers = AfisTracker::where('client_id', '!=', 21)
        ->where('online_status', 'offline')
        ->get();

    $this->line("    → Found {$offlineTrackers->count()} offline trackers");

    // Load all clients at once
    $clientIds = $offlineTrackers->pluck('client_id')->unique()->toArray();
    $clients   = Client::whereIn('id', $clientIds)->get()->keyBy('id');

    // Load today's already-fired notifications
    $today = $now->toDateString();
    $alreadyFired = AfisNotification::whereIn('type', [
            'vehicle.immediately_offline',
            'vehicle.extended_offline',
            'vehicle.critically_offline'
        ])
        ->whereDate('created_at', $today)
        ->get()
        ->map(fn($n) => $n->data['tracker_id'] ?? null)
        ->filter()
        ->flip()
        ->toArray();

    // Get last offline event time per tracker for duration calculation
    $lastOfflineEvents = \Illuminate\Support\Facades\DB::table('afis_device_alerts as a')
        ->join(\Illuminate\Support\Facades\DB::raw(
            '(SELECT tracker_id, MAX(occurred_at) as max_time
            FROM afis_device_alerts
            WHERE event_type = "offline"
            GROUP BY tracker_id) as b'
        ), function($join) {
            $join->on('a.tracker_id', '=', 'b.tracker_id')
                ->on('a.occurred_at', '=', 'b.max_time');
        })
        ->whereIn('a.tracker_id', $offlineTrackers->pluck('id')->toArray())
        ->select('a.tracker_id', 'a.occurred_at')
        ->get()
        ->keyBy('tracker_id');

    foreach ($offlineTrackers as $tracker) {
        if (isset($alreadyFired[$tracker->id])) continue;

        $client = $clients->get($tracker->client_id);

        // Calculate duration from last offline event
        $lastEvent    = $lastOfflineEvents->get($tracker->id);
        $offlineSince = $lastEvent
            ? Carbon::parse($lastEvent->occurred_at, 'Africa/Harare')
            : Carbon::now('Africa/Harare')->subHour(); // fallback

        $offlineHours = (int) abs($now->diffInHours($offlineSince));
        $offlineDays  = (int) abs($now->diffInDays($offlineSince));

        // Determine severity
        if ($offlineDays >= 7) {
            $severity = 'critical';
            $type     = 'vehicle.critically_offline';
        } elseif ($offlineHours >= 24) {
            $severity = 'severe';
            $type     = 'vehicle.extended_offline';
        } else {
            $severity = 'warning';
            $type     = 'vehicle.immediately_offline';
        }

        $duration = $offlineDays >= 1
            ? "{$offlineDays}d " . ($offlineHours % 24) . "h"
            : "{$offlineHours}h";

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

    $this->line("    → Fired {$fired} offline notifications");

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