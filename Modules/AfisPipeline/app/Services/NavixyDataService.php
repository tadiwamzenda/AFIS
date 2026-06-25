<?php

namespace Modules\AfisPipeline\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NavixyDataService
{
    private string $baseUrl;

    public function __construct(private PipelineAuthService $auth)
    {
        $this->baseUrl = rtrim(config('auth-module.navixy_base_url', 'https://api.navixy.com/v2'), '/');
    }

    // ─── Trackers ─────────────────────────────────────────────────────────────

    public function getTrackers(): array
    {
        $response = $this->get('tracker/list');
        return $response['list'] ?? [];
    }

    public function getLastGpsPoints(array $trackerIds): array
{
    if (empty($trackerIds)) return [];

    try {
        // Call one at a time — more reliable across Navixy account types
        $results = [];
        foreach ($trackerIds as $id) {
            try {
                $response = $this->post('tracker/get_last_gps_point', [
                    'tracker_id' => $id,
                ]);
                if (!empty($response['value'])) {
                    $results[] = array_merge($response['value'], ['tracker_id' => $id]);
                }
            } catch (\Throwable $e) {
                // Skip individual tracker failures silently
                \Illuminate\Support\Facades\Log::debug("AfisPipeline: skipped last GPS for tracker {$id}", ['error' => $e->getMessage()]);
            }
        }
        return $results;
    } catch (\Throwable $e) {
        return [];
    }
}

    // ─── Trips ────────────────────────────────────────────────────────────────

  public function getTrips(int $trackerId, Carbon $from, Carbon $to): array
{
    try {
        $hash = $this->auth->getHash();

        $response = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->baseUrl}/track/list", [
                'hash'       => $hash,
                'tracker_id' => $trackerId,
                'from'       => $from->format('Y-m-d H:i:s'),
                'to'         => $to->format('Y-m-d H:i:s'),
                'filter'     => false,
            ]);

        $data = $response->json();

        if (empty($data['success'])) {
            Log::warning("AfisPipeline: track/list failed", ['response' => $data]);
            return [];
        }

        return $data['list'] ?? [];

    } catch (\Throwable $e) {
        Log::warning("AfisPipeline: trips fetch failed for tracker {$trackerId}", [
            'error' => $e->getMessage(),
        ]);
        return [];
    }
}

    // ─── Events ───────────────────────────────────────────────────────────────

    public function getEvents(int $trackerId, Carbon $from, Carbon $to): array
{
    try {
        $hash = $this->auth->getHash();

        $response = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->baseUrl}/event/log/list", [
                'hash'       => $hash,
                'tracker_id' => $trackerId,
                'from'       => $from->format('Y-m-d H:i:s'),
                'to'         => $to->format('Y-m-d H:i:s'),
            ]);

        $data = $response->json();

        if (empty($data['success'])) {
            Log::warning("AfisPipeline: event/log/list failed", ['response' => $data]);
            return [];
        }

        return $data['list'] ?? [];

    } catch (\Throwable $e) {
        Log::warning("AfisPipeline: events fetch failed for tracker {$trackerId}", [
            'error' => $e->getMessage(),
        ]);
        return [];
    }
}

    // ─── HTTP helpers ─────────────────────────────────────────────────────────

    private function get(string $endpoint, array $params = []): array
    {
        $params['hash'] = $this->auth->getHash();

        $response = Http::timeout(30)->get("{$this->baseUrl}/{$endpoint}", $params);

        return $this->handleResponse($response, $endpoint);
    }

    private function post(string $endpoint, array $data = []): array
    {
        $data['hash'] = $this->auth->getHash();

        $response = Http::timeout(30)->asForm()->post("{$this->baseUrl}/{$endpoint}", $data);

        return $this->handleResponse($response, $endpoint);
    }

    private function handleResponse($response, string $endpoint): array
    {
        $data = $response->json();

        if (!empty($data['status']['code']) && $data['status']['code'] === 101) {
            $this->auth->clearHash();
            throw new \RuntimeException("Navixy session expired on [{$endpoint}]");
        }

        if (empty($data['success'])) {
            throw new \RuntimeException("Navixy error on [{$endpoint}]: " . ($data['status']['description'] ?? 'unknown'));
        }

        return $data;
    }
}