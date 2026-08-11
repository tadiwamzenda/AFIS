<?php

namespace Modules\AfisPipeline\Console;

use Illuminate\Console\Command;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Jobs\SyncClientFleetJob;
use Modules\AfisPipeline\Services\PipelineSyncService;

class SyncFleetCommand extends Command
{
    protected $signature   = 'afis:sync-fleet {--client= : Sync a specific client by ID} {--queue : Force queue dispatch even on local}';
    protected $description = 'Sync fleet data from Navixy for all active clients';

    public function handle(PipelineSyncService $syncService): void
    {
        $clients = Client::active()->get();

        if ($clientId = $this->option('client')) {
            $clients = $clients->where('id', $clientId);
        }

        if ($clients->isEmpty()) {
            $this->warn('No active clients found.');
            return;
        }

        // Use queue on production or when --queue flag is passed
        $useQueue = $this->option('queue') || app()->environment('production');

        if ($useQueue) {
            $this->info("Dispatching sync jobs for {$clients->count()} client(s)...");
            foreach ($clients as $client) {
                SyncClientFleetJob::dispatch($client);
                $this->line("  → Queued: {$client->name}");

                // Queue secondary instance if configured
                if ($client->navixy_instance_secondary) {
                    SyncClientFleetJob::dispatch($client, $client->navixy_instance_secondary);
                    $this->line("  → Queued: {$client->name} (instance {$client->navixy_instance_secondary})");
                }
            }
            $this->info('All sync jobs dispatched.');
        } else {
            $this->info("Syncing {$clients->count()} client(s) synchronously...");
            foreach ($clients as $client) {
                $this->line("  → Syncing: {$client->name} (instance {$client->navixy_instance})");
                $syncService->syncClient($client);
                $this->line("    ✓ Done");

                // Sync secondary instance if configured
                if ($client->navixy_instance_secondary) {
                    $this->line("  → Syncing: {$client->name} (instance {$client->navixy_instance_secondary})");
                    $syncService->syncClient($client, $client->navixy_instance_secondary);
                    $this->line("    ✓ Done");
                }
            }
            $this->info('All clients synced.');
        }
    }
}