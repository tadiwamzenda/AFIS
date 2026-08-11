<?php

namespace Modules\AfisPipeline\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Services\PipelineSyncService;

class SyncClientFleetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        public Client $client,
        public ?int   $instanceOverride = null
    ) {}

    public function handle(PipelineSyncService $service): void
    {
        $service->syncClient($this->client, $this->instanceOverride);
    }
}