<?php

namespace Modules\AfisPipeline\Console;

use Illuminate\Console\Command;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisTrackerGroup;
use Modules\AfisPipeline\Services\NavixyDataService;
use Illuminate\Support\Facades\Log;

class SyncTrackerGroupsCommand extends Command
{
    protected $signature   = 'afis:sync-groups';
    protected $description = 'Sync tracker groups from both Navixy instances';

    public function handle(NavixyDataService $navixy): void
    {
        $this->info('Syncing tracker groups from both instances...');

        $totalSynced = 0;

        foreach ([1, 2] as $instance) {
            $this->line("  → Instance {$instance}...");

            try {
                $groups = $navixy->getTrackerGroups($instance);
                $this->line("  → Found " . count($groups) . " groups");

                $clients = Client::whereNotNull('navixy_group_prefix')
                    ->where('navixy_instance', $instance)
                    ->get();

                foreach ($groups as $group) {
                    $clientId   = null;
                    $groupTitle = strtoupper(trim($group['title']));

                    foreach ($clients as $client) {
                        $prefix = strtoupper(trim($client->navixy_group_prefix ?? ''));
                        if (empty($prefix)) continue;

                        // Exact match: e.g. "ROAD ANGELS" prefix = "ROAD ANGELS"
                        if ($groupTitle === $prefix) {
                            $clientId = $client->id;
                            break;
                        }

                        // Prefix match: e.g. "ATZ" matches "ATZ CASHEL", "ATZ MUTARE"
                        if (str_starts_with($groupTitle, $prefix . ' ')) {
                            $clientId = $client->id;
                            break;
                        }
                    }

                    AfisTrackerGroup::updateOrCreate(
                        ['navixy_group_id' => $group['id']],
                        [
                            'navixy_instance' => $instance,
                            'title'           => $group['title'],
                            'color'           => $group['color'] ?? null,
                            'client_id'       => $clientId,
                        ]
                    );

                    if ($clientId) {
                        $this->line("  ✓ Mapped: {$group['title']} → client #{$clientId}");
                    }

                    $totalSynced++;
                }
            } catch (\Throwable $e) {
                $this->warn("  ✗ Instance {$instance} failed: " . $e->getMessage());
                Log::warning("afis:sync-groups: instance {$instance} failed", ['error' => $e->getMessage()]);
            }
        }

        $this->info("Done. Synced {$totalSynced} groups.");
    }
}