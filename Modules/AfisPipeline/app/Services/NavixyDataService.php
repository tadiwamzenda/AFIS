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
        $this->baseUrl = rtrim(config('auth-module.navixy_base_url', 'https://api.us.navixy.com/v2'), '/');
    }

    public function getTrackers(int $instance = 1): array
    {
        $response = $this->post('tracker/list', [], $instance);
        return $response['list'] ?? [];
    }

    public function getLastGpsPoints(array $trackerIds, int $instance = 1): array
    {
        if (empty($trackerIds)) return [];

        $results = [];
        foreach ($trackerIds as $id) {
            try {
                $hash     = $this->auth->getHash($instance);
                $response = Http::timeout(30)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post("{$this->baseUrl}/tracker/get_last_gps_point", [
                        'hash'       => $hash,
                        'tracker_id' => $id,
                    ]);
                $data = $response->json();
                if (!empty($data['value'])) {
                    $results[] = array_merge($data['value'], ['tracker_id' => $id]);
                }
            } catch (\Throwable $e) {
                Log::debug("AfisPipeline: skipped last GPS for tracker {$id}", ['error' => $e->getMessage()]);
            }
        }
        return $results;
    }

    public function getTrips(int $trackerId, Carbon $from, Carbon $to, int $instance = 1): array
    {
        try {
            $hash     = $this->auth->getHash($instance);
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
            Log::warning("AfisPipeline: trips fetch failed for tracker {$trackerId}", ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function getEvents(int $trackerId, Carbon $from, Carbon $to, int $instance = 1): array
    {
        try {
            $hash     = $this->auth->getHash($instance);
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
            Log::warning("AfisPipeline: events fetch failed for tracker {$trackerId}", ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ─── Internal helpers ─────────────────────────────────────────────────────

    private function post(string $endpoint, array $data = [], int $instance = 1): array
    {
        $data['hash'] = $this->auth->getHash($instance);

        $response = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->baseUrl}/{$endpoint}", $data);

        $result = $response->json();

        if (!empty($result['status']['code']) && $result['status']['code'] === 101) {
            $this->auth->clearHash($instance);
            $data['hash'] = $this->auth->getHash($instance);
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/{$endpoint}", $data);
            $result = $response->json();
        }

        return $result;
    }
}