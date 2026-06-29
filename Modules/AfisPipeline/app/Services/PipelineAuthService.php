<?php

namespace Modules\AfisPipeline\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PipelineAuthService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('auth-module.navixy_base_url', 'https://api.us.navixy.com/v2'), '/');
    }

    /**
     * Get a valid hash for the given instance (1 or 2).
     */
    public function getHash(int $instance = 1): string
    {
        $cacheKey = "afis_pipeline_navixy_hash_instance_{$instance}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        return $this->refreshHash($instance);
    }

    /**
     * Re-authenticate and cache a fresh hash for the given instance.
     */
    public function refreshHash(int $instance = 1): string
    {
        $email    = config("afispipeline.instance{$instance}.email", '');
        $password = config("afispipeline.instance{$instance}.password", '');

        if (empty($email) || empty($password)) {
            throw new \RuntimeException(
                "Pipeline credentials not configured for Instance {$instance}. " .
                "Set NAVIXY_INSTANCE{$instance}_EMAIL and NAVIXY_INSTANCE{$instance}_PASSWORD in .env"
            );
        }

        $response = Http::timeout(15)
            ->asForm()
            ->post("{$this->baseUrl}/user/auth", [
                'login'    => $email,
                'password' => $password,
            ]);

        $data = $response->json();

        if (empty($data['success'])) {
            throw new \RuntimeException(
                "Pipeline Navixy auth failed for Instance {$instance}: " .
                ($data['status']['description'] ?? 'unknown error')
            );
        }

        $hash = $data['hash'];
        Cache::put("afis_pipeline_navixy_hash_instance_{$instance}", $hash, now()->addMinutes(18));

        Log::info("AfisPipeline: hash refreshed for instance {$instance}");

        return $hash;
    }

    /**
     * Clear the cached hash for a specific instance.
     */
    public function clearHash(int $instance = 1): void
    {
        Cache::forget("afis_pipeline_navixy_hash_instance_{$instance}");
    }

    /**
     * List all sub-users under the master account for a given instance.
     * Used for auto-creating local user records.
     */
    public function getSubUsers(int $instance = 1): array
    {
        $hash = $this->getHash($instance);

        $response = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->baseUrl}/subuser/list", ['hash' => $hash]);

        $data = $response->json();

        if (empty($data['success'])) {
            // Session may have expired — retry once
            if (!empty($data['status']['code']) && $data['status']['code'] === 101) {
                $this->clearHash($instance);
                $hash = $this->getHash($instance);
                $response = Http::timeout(30)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post("{$this->baseUrl}/subuser/list", ['hash' => $hash]);
                $data = $response->json();
            }

            if (empty($data['success'])) {
                Log::warning("AfisPipeline: subuser/list failed for instance {$instance}", ['data' => $data]);
                return [];
            }
        }

        return $data['list'] ?? [];
    }
}