<?php

namespace Modules\AfisPipeline\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Services\PipelineSyncService;

class SyncClientFleetJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    // Confirmed via two independent real production runs (cold-start:
    // 10m22s, steady-state: 9m49s) — ZETDC (899 trackers) genuinely takes
    // ~10 min per sync, consistently. Almost certainly cumulative
    // rate-limited Navixy API call time across 899 individual trackers,
    // not a one-off. 1200s gives real margin above the observed worst case.
    public int $timeout = 1200;

    public function __construct(
        public Client $client,
        public ?int   $instanceOverride = null
    ) {}

    /**
     * Prevents a second sync job for the SAME client+instance from being
     * queued/processed while one is already pending or running — the
     * production-path equivalent of the command-level Cache::lock() added
     * to SyncFleetCommand. Keyed by instance too, so ZESA's two jobs (one
     * per instance) stay independently unique rather than colliding with
     * each other.
     */
    public function uniqueId(): string
    {
        return $this->client->id . ':' . ($this->instanceOverride ?? 'primary');
    }

    /**
     * Matches the 15-min sync-fleet interval — if this client's job is
     * still uniquely locked by the time the next scheduled cycle tries to
     * dispatch another one, that means the previous run hasn't finished
     * yet, so skipping the duplicate is correct.
     */
    public int $uniqueFor = 900;

    public function handle(PipelineSyncService $service): void
    {
        $service->syncClient($this->client, $this->instanceOverride);
    }
}