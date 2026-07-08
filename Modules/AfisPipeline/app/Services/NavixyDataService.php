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

    // ─── Navixy plugin 95 — fuel report ──────────────────────────────────────

    public function generateNavixyFuelReport(
        array  $trackerIds,
        Carbon $from,
        Carbon $to,
        int    $instance = 1
    ): ?int {
        try {
            $hash     = $this->auth->getHash($instance);
            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/report/tracker/generate", [
                    'hash'        => $hash,
                    'title'       => 'AFIS Fuel ' . $from->format('Y-m-d'),
                    'trackers'    => $trackerIds,
                    'from'        => $from->format('Y-m-d H:i:s'),
                    'to'          => $to->format('Y-m-d H:i:s'),
                    'time_filter' => [
                        'from'     => '00:00:00',
                        'to'       => '23:59:59',
                        'weekdays' => [1, 2, 3, 4, 5, 6, 7],
                    ],
                    'plugin' => [
                        'hide_empty_tabs' => true,
                        'plugin_id'       => 95,
                    ],
                ]);

            $data = $response->json();
            if (empty($data['success'])) {
                Log::warning('NavixyDataService: fuel report generate failed', ['data' => $data]);
                return null;
            }
            return $data['id'];
        } catch (\Throwable $e) {
            Log::warning('NavixyDataService: generateNavixyFuelReport error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function downloadNavixyFuelReport(int $reportId, int $instance = 1): ?string
    {
        try {
            $hash     = $this->auth->getHash($instance);
            $response = Http::timeout(120)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/report/tracker/download", [
                    'hash'      => $hash,
                    'report_id' => $reportId,
                    'format'    => 'xlsx',
                ]);

            if (!$response->successful()) return null;
            return $response->body();
        } catch (\Throwable $e) {
            Log::warning('NavixyDataService: downloadNavixyFuelReport error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ─── Tracker groups ───────────────────────────────────────────────────────

    public function getTrackerGroups(int $instance = 1): array
    {
        $data = $this->post('tracker/group/list', [], $instance);
        return $data['list'] ?? [];
    }

    // ─── Daily mileage ────────────────────────────────────────────────────────

    public function getDailyMileage(array $trackerIds, Carbon $from, Carbon $to, int $instance = 1): array
    {
        if (empty($trackerIds)) return [];

        try {
            $hash     = $this->auth->getHash($instance);
            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/tracker/stats/mileage/read", [
                    'hash'     => $hash,
                    'trackers' => $trackerIds,
                    'from'     => $from->format('Y-m-d H:i:s'),
                    'to'       => $to->format('Y-m-d H:i:s'),
                ]);

            $data = $response->json();
            if (empty($data['success'])) {
                Log::warning("NavixyDataService: mileage read failed", ['response' => $data]);
                return [];
            }
            return $data['result'] ?? [];
        } catch (\Throwable $e) {
            Log::warning("NavixyDataService: getDailyMileage failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ─── Alerts / notification history ───────────────────────────────────────

    public function getAlerts(array $trackerIds, Carbon $from, Carbon $to, int $instance = 1): array
    {
        if (empty($trackerIds)) return [];

        try {
            $hash     = $this->auth->getHash($instance);
            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/history/tracker/list", [
                    'hash'     => $hash,
                    'trackers' => $trackerIds,
                    'from'     => $from->format('Y-m-d H:i:s'),
                    'to'       => $to->format('Y-m-d H:i:s'),
                ]);

            $data = $response->json();
            if (empty($data['success'])) {
                Log::warning("NavixyDataService: alerts list failed", ['response' => $data]);
                return [];
            }
            return $data['list'] ?? [];
        } catch (\Throwable $e) {
            Log::warning("NavixyDataService: getAlerts failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ─── All trackers (online AND offline) ───────────────────────────────────

    public function getAllTrackers(int $instance = 1): array
    {
        // tracker/list returns all trackers regardless of online status
        $data = $this->post('tracker/list', [], $instance);
        return $data['list'] ?? [];
    }

    // ─── (Engine Hours)) ───────────────────────────────────

    public function getEngineHours(array $trackerIds, Carbon $from, Carbon $to, int $instance = 1): array
    {
        if (empty($trackerIds)) return [];

        try {
            $results = [];
            foreach ($trackerIds as $id) {
                $hash     = $this->auth->getHash($instance);
                $response = Http::timeout(30)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post("{$this->baseUrl}/tracker/stats/engine_hours/read", [
                        'hash'       => $hash,
                        'tracker_id' => $id,
                        'from'       => $from->format('Y-m-d H:i:s'),
                        'to'         => $to->format('Y-m-d H:i:s'),
                    ]);
                $data = $response->json();
                if (!empty($data['success'])) {
                    $results[$id] = $data['value'] ?? 0;
                }
                usleep(100000); // 0.1s delay to avoid rate limits
            }
            return $results;
        } catch (\Throwable $e) {
            Log::warning("NavixyDataService: getEngineHours failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function getFuelSensorReadings(
        int    $trackerId,
        int    $sensorId,
        Carbon $from,
        Carbon $to,
        int    $instance = 1
    ): array {
        try {
            $hash     = $this->auth->getHash($instance);
            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/tracker/sensor/data/read", [
                    'hash'       => $hash,
                    'tracker_id' => $trackerId,
                    'sensor_id'  => $sensorId,
                    'from'       => $from->format('Y-m-d H:i:s'),
                    'to'         => $to->format('Y-m-d H:i:s'),
                ]);
            $data = $response->json();
            if (empty($data['success'])) return [];
            return $data['list'] ?? [];
        } catch (\Throwable $e) {
            Log::warning("NavixyDataService: getFuelSensorReadings failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function getTrackerSensors(int $trackerId, int $instance = 1): array
    {
        try {
            $hash     = $this->auth->getHash($instance);
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/tracker/sensor/list", [
                    'hash'       => $hash,
                    'tracker_id' => $trackerId,
                ]);
            $data = $response->json();
            return collect($data['list'] ?? [])
                ->filter(fn($s) => $s['sensor_type'] === 'fuel')
                ->values()
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
    
   
}