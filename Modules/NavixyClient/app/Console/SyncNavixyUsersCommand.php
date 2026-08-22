<?php

namespace Modules\NavixyClient\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisPipeline\Services\PipelineAuthService;
use App\Models\User;

class SyncNavixyUsersCommand extends Command
{
    protected $signature   = 'navixy:sync-users';
    protected $description = 'Sync sub-users from both Navixy instances into local user records';

    public function handle(PipelineAuthService $pipelineAuth): void
    {
        $this->info('Syncing Navixy sub-users from both instances...');

        $totalCreated = 0;
        $totalUpdated = 0;

        // ── Step 1: Sync users from Bantu Track master accounts (instances 1 & 2) ──
        foreach ([1, 2] as $instance) {
            $this->line("  → Fetching sub-users from Instance {$instance}...");

            try {
                $subUsers = $pipelineAuth->getSubUsers($instance);
            } catch (\Throwable $e) {
                $this->warn("  ✗ Instance {$instance} failed: " . $e->getMessage());
                continue;
            }

            $this->line("  → Found " . count($subUsers) . " sub-users");

            foreach ($subUsers as $subUser) {
                $navixyUserId    = $subUser['id'];
                $securityGroupId = $subUser['security_group_id'] ?? null;
                $email           = $subUser['login'] ?? null;
                $name            = trim(($subUser['first_name'] ?? '') . ' ' . ($subUser['last_name'] ?? '')) ?: $email;
                $isActive        = (bool) ($subUser['activated'] ?? true);

                if (!$email) continue;

                // Find client by security group ID
                $client = null;
                if ($securityGroupId) {
                    $client = Client::where('navixy_security_group_id', $securityGroupId)
                        ->where('navixy_instance', $instance)
                        ->first();
                }

                $existing = User::where('navixy_user_id', $navixyUserId)
                    ->where('navixy_instance', $instance)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'name'                     => $name,
                        'email'                    => $email,
                        'navixy_security_group_id' => $securityGroupId,
                        'is_active'                => $isActive,
                        'client_id'                => $client?->id ?? $existing->client_id,
                    ]);
                    $totalUpdated++;
                } else {
                    User::create([
                        'name'                     => $name,
                        'email'                    => $email,
                        'navixy_user_id'           => $navixyUserId,
                        'navixy_account_id'        => 0,
                        'navixy_instance'          => $instance,
                        'navixy_security_group_id' => $securityGroupId,
                        'role'                     => User::ROLE_CLIENT,
                        'is_active'                => $isActive,
                        'client_id'                => $client?->id,
                    ]);
                    $totalCreated++;
                }
            }
        }

        // ── Step 2: Sync users from independent client accounts ───────────────
        $this->line("  → Syncing users from independent client accounts...");

        $independentClients = Client::where('is_active', true)
            ->whereNotNull('navixy_api_key')
            ->where('id', '!=', 21)
            ->get();

        foreach ($independentClients as $client) {
            $this->line("    → {$client->name}...");

            try {
                // Set client API key so getSubUsers() authenticates as this client
                $pipelineAuth->setClientApiKey($client->navixy_api_key);

                $subUsers = $pipelineAuth->getSubUsers($client->navixy_instance);

                foreach ($subUsers as $subUser) {
                    $navixyUserId    = $subUser['id'];
                    $securityGroupId = $subUser['security_group_id'] ?? null;
                    $email           = $subUser['login'] ?? null;
                    $name            = trim(($subUser['first_name'] ?? '') . ' ' . ($subUser['last_name'] ?? '')) ?: $email;
                    $isActive        = (bool) ($subUser['activated'] ?? true);

                    if (!$email) continue;

                    $existing = User::where('navixy_user_id', $navixyUserId)->first();

                    if ($existing) {
                        $existing->update([
                            'name'                     => $name,
                            'email'                    => $email,
                            'navixy_security_group_id' => $securityGroupId,
                            'is_active'                => $isActive,
                            'client_id'                => $client->id,
                        ]);
                        $totalUpdated++;
                    } else {
                        User::create([
                            'name'                     => $name,
                            'email'                    => $email,
                            'navixy_user_id'           => $navixyUserId,
                            'navixy_account_id'        => $client->navixy_account_id ?? 0,
                            'navixy_instance'          => $client->navixy_instance,
                            'navixy_security_group_id' => $securityGroupId,
                            'role'                     => User::ROLE_CLIENT,
                            'is_active'                => $isActive,
                            'client_id'                => $client->id,
                        ]);
                        $totalCreated++;
                    }
                }

                $this->line("      ✓ " . count($subUsers) . " user(s) synced");

            } catch (\Throwable $e) {
                $this->warn("      ✗ {$client->name} failed: " . $e->getMessage());
                Log::warning("navixy:sync-users: independent client {$client->name} failed", ['error' => $e->getMessage()]);
            } finally {
                $pipelineAuth->setClientApiKey(null);
            }
        }

        $this->info("Done. Created: {$totalCreated} · Updated: {$totalUpdated}");
        Log::info('navixy:sync-users complete', compact('totalCreated', 'totalUpdated'));
    }
}