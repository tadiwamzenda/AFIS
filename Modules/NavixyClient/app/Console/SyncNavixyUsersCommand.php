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

                $existing = User::where('navixy_user_id', $navixyUserId)
                    ->where('navixy_instance', $instance)
                    ->first();

                if ($existing) {
                    // Update name, email, active status
                    $existing->update([
                        'name'                     => $name,
                        'email'                    => $email,
                        'navixy_security_group_id' => $securityGroupId,
                        'is_active'                => $isActive,
                    ]);
                    $totalUpdated++;
                } else {
                    // Create new user record
                    User::create([
                        'name'                     => $name,
                        'email'                    => $email,
                        'navixy_user_id'           => $navixyUserId,
                        'navixy_account_id'        => 0,
                        'navixy_instance'          => $instance,
                        'navixy_security_group_id' => $securityGroupId,
                        'role'                     => User::ROLE_CLIENT,
                        'is_active'                => $isActive,
                    ]);
                    $totalCreated++;
                }
            }
        }

        $this->info("Done. Created: {$totalCreated} · Updated: {$totalUpdated}");
        Log::info('navixy:sync-users complete', compact('totalCreated', 'totalUpdated'));
    }
}