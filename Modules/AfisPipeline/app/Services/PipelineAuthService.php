<?php

namespace Modules\AfisPipeline\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PipelineAuthService
{
    private string $baseUrl;
    private string $email;
    private string $password;
    private string $cacheKey = 'afis_pipeline_navixy_hash';

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('auth-module.navixy_base_url', 'https://api.navixy.com/v2'), '/');
        $this->email    = config('afispipeline.navixy_email', '');
        $this->password = config('afispipeline.navixy_password', '');
    }

    public function getHash(): string
    {
        // Return cached hash if still valid (cache for 18 minutes)
        if (Cache::has($this->cacheKey)) {
            return Cache::get($this->cacheKey);
        }

        return $this->refreshHash();
    }

    public function refreshHash(): string
    {
        if (empty($this->email) || empty($this->password)) {
            throw new \RuntimeException('Pipeline Navixy credentials not configured. Set NAVIXY_PIPELINE_EMAIL and NAVIXY_PIPELINE_PASSWORD in .env');
        }

        $response = Http::timeout(15)
            ->asForm()
            ->post("{$this->baseUrl}/user/auth", [
                'login'    => $this->email,
                'password' => $this->password,
            ]);

        $data = $response->json();

        if (empty($data['success'])) {
            throw new \RuntimeException('Pipeline Navixy auth failed: ' . ($data['status']['description'] ?? 'unknown error'));
        }

        $hash = $data['hash'];

        // Cache for 18 minutes (Navixy expires at ~20 min)
        Cache::put($this->cacheKey, $hash, now()->addMinutes(18));

        Log::info('AfisPipeline: Navixy hash refreshed');

        return $hash;
    }

    public function clearHash(): void
    {
        Cache::forget($this->cacheKey);
    }
}