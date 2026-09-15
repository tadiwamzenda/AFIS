<?php

namespace Modules\AfisPipeline\Console;

use Illuminate\Console\Command;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Models\AfisTrackerGroup;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AfisPipeline\Services\NavixyDataService;
use Modules\AfisPipeline\Services\PipelineAuthService;
use Illuminate\Support\Facades\Log;

class SyncTrackerGroupsCommand extends Command
{
    protected $signature   = 'afis:sync-groups';
    protected $description = 'Sync tracker groups from both Navixy instances';

    public function handle(NavixyDataService $navixy, PipelineAuthService $auth): void
    {
        $this->info('Syncing tracker groups from both instances...');

        $totalSynced = 0;

        // ── Step 1: Sync groups from Bantu Track master accounts (instances 1 & 2) ──
        foreach ([1, 2] as $instance) {
            $this->line("  → Instance {$instance} (master account)...");

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
                            'client_id'       => $clientId ?? 21,
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

        // ── Step 2: Sync groups for independent clients (own Navixy accounts) ──
        $this->line("  → Syncing groups for independent clients...");

        $independentClients = Client::where('is_active', true)
            ->whereNotNull('navixy_api_key')
            ->where('id', '!=', 21)
            ->get();

        $independentNavixyIds = [];

        foreach ($independentClients as $client) {
            $this->line("    → {$client->name} (instance {$client->navixy_instance})...");

            try {
                // Set client API key so all getHash() calls use it
                $auth->setClientApiKey($client->navixy_api_key);

                // Get groups for this independent account
                $groups = $navixy->getTrackerGroups($client->navixy_instance);

                foreach ($groups as $group) {
                    // Normalize title for fuzzy matching (strips spaces, uppercases)
                    // Handles "REF HEAD OFFICE" vs "REF HEADOFFICE" migration discrepancies
                    $normalizedNew = strtoupper(preg_replace('/\s+/', '', trim($group['title'])));

                    // Check if group with similar title already exists for this client
                    $existingByTitle = AfisTrackerGroup::where('client_id', $client->id)
                        ->get()
                        ->first(function ($g) use ($normalizedNew) {
                            $normalizedExisting = strtoupper(preg_replace('/\s+/', '', trim($g->title)));
                            return $normalizedExisting === $normalizedNew;
                        });

                    if ($existingByTitle) {
                    // Check if independent account's group ID already exists as a separate entry
                    $alreadyExists = AfisTrackerGroup::where('navixy_group_id', $group['id'])
                        ->where('id', '!=', $existingByTitle->id)
                        ->first();

                    if ($alreadyExists) {
                        // Independent account version already exists — delete the old master account entry
                        // and keep the independent account entry (which has the correct group ID)
                        $existingByTitle->delete();
                        // Update the existing independent entry to ensure client mapping is correct
                        $alreadyExists->update([
                            'client_id'       => $client->id,
                            'navixy_instance' => $client->navixy_instance,
                            'color'           => $group['color'] ?? null,
                        ]);
                        $this->line("      ↻ Cleaned: {$group['title']} (removed old master account entry)");
                    } else {
                        // Safe to update — no duplicate navixy_group_id
                        $existingByTitle->update([
                            'navixy_group_id' => $group['id'],
                            'navixy_instance' => $client->navixy_instance,
                            'title'           => $group['title'],
                            'color'           => $group['color'] ?? null,
                        ]);
                        $this->line("      ↻ Merged: {$group['title']}");
                    }
                    } else {
                        // Genuinely new group
                        AfisTrackerGroup::updateOrCreate(
                            ['navixy_group_id' => $group['id']],
                            [
                                'client_id'       => $client->id,
                                'title'           => $group['title'],
                                'color'           => $group['color'] ?? null,
                                'navixy_instance' => $client->navixy_instance,
                            ]
                        );
                        $this->line("      ✓ New: {$group['title']}");
                    }
                }

                // Also get all trackers from this account for ghost cleanup
                $accountTrackers = $navixy->getAllTrackers($client->navixy_instance);
                foreach ($accountTrackers as $t) {
                    $independentNavixyIds[] = $t['id'];
                }

                $this->line("      ✓ " . count($groups) . " group(s) synced");
                $totalSynced += count($groups);

            } catch (\Throwable $e) {
                $this->warn("      ✗ {$client->name} failed: " . $e->getMessage());
                Log::warning("afis:sync-groups: independent client {$client->name} failed", ['error' => $e->getMessage()]);
            } finally {
                // Always clear override after each client
                $auth->setClientApiKey(null);
            }
        }

        // ── Step 3: Ghost tracker cleanup ─────────────────────────────────────
        $this->line("  → Cleaning up ghost trackers...");

        // Get all tracker IDs from master accounts
        $inst1Ids = collect($navixy->getAllTrackers(1))->pluck('id')->toArray();
        $inst2Ids = collect($navixy->getAllTrackers(2))->pluck('id')->toArray();

        // Combine with independent client tracker IDs
        $allNavixyIds = array_merge($inst1Ids, $inst2Ids, $independentNavixyIds);

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

        // ── Step 4: Ungrouped tracker cleanup ─────────────────────────────────
        // Trackers with navixy_group_id = NULL go to MISCELLANEOUS
        // (created by old discoverTrackers() with no group mapping)
        $ungroupedCount = AfisTracker::whereNull('navixy_group_id')
            ->where('client_id', '!=', 21)
            ->update(['client_id' => 21]);

        if ($ungroupedCount > 0) {
            $this->line("  → Moved {$ungroupedCount} ungrouped tracker(s) to MISCELLANEOUS");
            Log::info("afis:sync-groups: moved {$ungroupedCount} ungrouped trackers to MISCELLANEOUS");
        }

                // ── Step 5: Repair misassigned trackers ───────────────────────────────
        // Finds trackers whose navixy_group_id belongs to a DIFFERENT client
        // than their current client_id. This corrects data corruption from
        // group merges, account migrations, or manual changes.
        // Runs daily (not every 15-min sync) to avoid performance overhead.
        $this->line("  → Repairing misassigned trackers...");

        $repaired = 0;

        // Load all group → client mappings
        $groupClientMap = AfisTrackerGroup::pluck('client_id', 'navixy_group_id');

        // Find trackers where group belongs to different client
        AfisTracker::whereNotNull('navixy_group_id')
            ->where('client_id', '!=', 21)
            ->chunk(200, function ($trackers) use ($groupClientMap, &$repaired) {
                foreach ($trackers as $tracker) {
                    $correctClientId = $groupClientMap[$tracker->navixy_group_id] ?? null;

                    // Skip if group not mapped (unmapped groups → leave as is)
                    if (!$correctClientId) continue;

                    // Skip if already correctly assigned
                    if ($correctClientId === $tracker->client_id) continue;

                    // Skip if correct client is MISCELLANEOUS
                    if ($correctClientId === 21) continue;

                    // Reassign to correct client
                    $tracker->update(['client_id' => $correctClientId]);
                    $repaired++;

                    Log::info("afis:sync-groups: reassigned tracker {$tracker->label} " .
                        "from client {$tracker->client_id} to {$correctClientId} " .
                        "(group {$tracker->navixy_group_id})");
                }
            });

        if ($repaired > 0) {
            $this->line("  → Repaired {$repaired} misassigned tracker(s)");
            Log::info("afis:sync-groups: repaired {$repaired} misassigned trackers");
        } else {
            $this->line("  → No misassigned trackers found");
        }


                // ── Step 6: Orphaned group cleanup ────────────────────────────────────
        // Mirror of Step 5, in the opposite direction: Step 5 fixes a TRACKER
        // whose client_id disagrees with its GROUP. This fixes a GROUP whose
        // client_id disagrees with ALL of its trackers — i.e. every tracker
        // under this group's navixy_group_id has already, correctly, ended
        // up at MISCELLANEOUS (via Step 3's ghost-tracker cleanup), but the
        // group record itself was never updated to match. 
        // Deliberately conservative: only acts when the group HAS trackers
        // and ALL of them contradict the group's client_id — a group with
        // zero trackers (e.g. genuinely new/empty) is left untouched, since
        // absence of trackers isn't evidence of a wrong assignment.
        $this->line('  → Cleaning up orphaned groups...');

        $orphanedGroups = 0;

        AfisTrackerGroup::where('client_id', '!=', 21)
            ->chunk(100, function ($groups) use (&$orphanedGroups) {
                foreach ($groups as $group) {
                    $trackerClientIds = AfisTracker::where('navixy_group_id', $group->navixy_group_id)
                        ->distinct()
                        ->pluck('client_id');

                    if ($trackerClientIds->isEmpty()) continue; // no trackers — no evidence either way, leave alone

                    $allGhost = $trackerClientIds->every(fn($id) => $id === 21);
                    if (!$allGhost) continue; // at least one real tracker still agrees — group is fine

                    $group->update(['client_id' => 21]);
                    $orphanedGroups++;

                    Log::info("afis:sync-groups: moved orphaned group to MISCELLANEOUS", [
                        'group_id'        => $group->id,
                        'navixy_group_id' => $group->navixy_group_id,
                        'title'           => $group->title,
                        'previous_client' => $group->client_id,
                    ]);
                }
            });

        if ($orphanedGroups > 0) {
            $this->line("  → Moved {$orphanedGroups} orphaned group(s) to MISCELLANEOUS");
        } else {
            $this->line('  → No orphaned groups found');
        }

        $this->info("Done. Synced {$totalSynced} groups.");
    }
}