<?php

namespace Modules\AfisPipeline\Console;

use Illuminate\Console\Command;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisTrackerGroup;
use Modules\AfisPipeline\Services\NavixyDataService;
use Illuminate\Support\Facades\Log;
use Modules\AfisPipeline\Models\AfisTracker;

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

                    $existing = AfisTrackerGroup::where('navixy_group_id', $group['id'])->first();

if ($existing) {
    $existing->update([
        'navixy_instance' => $instance,
        'title'           => $group['title'],
        'color'           => $group['color'] ?? null,
        // Only update client_id if we found a match OR if it was never set
        'client_id'       => $clientId ?? $existing->client_id ?? 21,
    ]);
} else {
    AfisTrackerGroup::create([
        'navixy_group_id' => $group['id'],
        'navixy_instance' => $instance,
        'title'           => $group['title'],
        'color'           => $group['color'] ?? null,
        'client_id'       => $clientId ?? 21, // New unmapped groups go to MISCELLANEOUS
    ]);
}

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

        // ── Clean up ghost trackers (removed from Navixy but still in AFIS) ──
$this->line("  → Cleaning up ghost trackers...");

$inst1Ids = collect($navixy->getAllTrackers(1))->pluck('id')->toArray();
$inst2Ids = collect($navixy->getAllTrackers(2))->pluck('id')->toArray();
$allNavixyIds = array_merge($inst1Ids, $inst2Ids);

        if (!empty($allNavixyIds)) {
            $ghostCount = AfisTracker::whereNotIn('navixy_tracker_id', $allNavixyIds)
                ->where('client_id', '!=', 21)
                ->update(['client_id' => 21]);
            
            if ($ghostCount > 0) {
                $this->line("  → Moved {$ghostCount} ghost tracker(s) to MISCELLANEOUS");
                Log::info("afis:sync-groups: moved {$ghostCount} ghost trackers to MISCELLANEOUS");
            } else {
                $this->line("  → No ghost trackers found");
            }
        }
        $this->info("Done. Synced {$totalSynced} groups.");
    }
}