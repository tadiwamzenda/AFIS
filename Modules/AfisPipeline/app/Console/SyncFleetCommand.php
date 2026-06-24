<?php

namespace Modules\AfisPipeline\Console;

use Illuminate\Console\Command;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Jobs\SyncClientFleetJob;

class SyncFleetCommand extends Command
{
    protected $signature   = 'afis:sync-fleet {--client= : Sync a specific client by ID}';
    protected $description = 'Sync fleet data from Navixy for all active clients';

    public function handle(): void
    {
        $clients = Client::active()->get();

        if ($clientId = $this->option('client')) {
            $clients = $clients->where('id', $clientId);
        }

        if ($clients->isEmpty()) {
            $this->warn('No active clients found.');
            return;
        }

        $this->info("Dispatching sync jobs for {$clients->count()} client(s)...");

        foreach ($clients as $client) {
            SyncClientFleetJob::dispatch($client);
            $this->line("  → Queued: {$client->name}");
        }

        $this->info('All sync jobs dispatched.');
    }
}