<?php

namespace Modules\AfisEngine\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\AfisEngine\Models\AfisAiReport;
use Modules\AfisEngine\Models\AfisAiReportCache;
use Modules\AfisEngine\Services\Engines\ClaudeEngine;
use Modules\AfisEngine\Services\Engines\OpenAiEngine;
use Modules\Core\Contracts\AiEngineInterface;

class AfisEngineService implements AiEngineInterface
{
    public function __construct(
        private ClaudeEngine $claude,
        private OpenAiEngine $openai,
    ) {}

    // ─── AiEngineInterface ────────────────────────────────────────────────────

    public function analyze(string $prompt, array $context = []): string
    {
        $result = $this->runWithFallback($prompt);
        return $result['response'];
    }

    public function analyzeAsync(string $jobClass, array $payload = []): void
    {
        dispatch(new $jobClass($payload));
    }

    public function isAvailable(): bool
    {
        return $this->claude->isAvailable() || $this->openai->isAvailable();
    }

    public function getProviderName(): string
    {
        if ($this->claude->isAvailable()) return 'claude';
        if ($this->openai->isAvailable()) return 'openai';
        return 'none';
    }

    // ─── Core analysis with caching ──────────────────────────────────────────

    public function generateReport(
        string $reportType,
        string $prompt,
        int    $clientId,
        ?int   $trackerId  = null,
        bool   $useCache   = true
    ): AfisAiReport {

        // Check cache first
        if ($useCache) {
            $cached = $this->getFromCache($reportType, $clientId, $trackerId);
            if ($cached) {
                Log::info('AfisEngine: serving from cache', ['report_type' => $reportType]);
                return $this->reportFromCache($cached, $clientId, $trackerId, $reportType);
            }
        }

        // Create pending report record
        $report = AfisAiReport::create([
            'client_id'    => $clientId,
            'tracker_id'   => $trackerId,
            'report_type'  => $reportType,
            'prompt_used'  => $prompt,
            'response'     => '',
            'status'       => 'pending',
            'generated_by' => Auth::id(),
        ]);

        try {
            $result = $this->runWithFallback($prompt);

            $report->update([
                'response'    => $result['response'],
                'engine_used' => $result['engine'],
                'tokens_used' => $result['tokens'],
                'duration_ms' => $result['duration_ms'],
                'status'      => 'completed',
            ]);

            // Store in cache
            $this->storeInCache($reportType, $clientId, $trackerId, $result['response'], $result['engine']);

        } catch (\Throwable $e) {
            $report->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('AfisEngine: report generation failed', [
                'report_type' => $reportType,
                'error'       => $e->getMessage(),
            ]);
            throw $e;
        }

        return $report->fresh();
    }

    // ─── Primary/fallback logic ───────────────────────────────────────────────

    private function runWithFallback(string $prompt): array
    {
        $primary  = config('afisengine.primary_engine', 'claude');
        $fallback = config('afisengine.fallback_engine', 'openai');

        // Try primary engine
        try {
            $engine = $primary === 'claude' ? $this->claude : $this->openai;

            if (!$engine->isAvailable()) {
                throw new \RuntimeException("{$primary} engine not available");
            }

            $result           = $engine->analyze($prompt);
            $result['engine'] = $primary;
            Log::info("AfisEngine: used {$primary}");
            return $result;

        } catch (\Throwable $e) {
            Log::warning("AfisEngine: primary engine ({$primary}) failed, trying fallback", [
                'error' => $e->getMessage(),
            ]);
        }

        // Try fallback engine
        try {
            $engine = $fallback === 'openai' ? $this->openai : $this->claude;

            if (!$engine->isAvailable()) {
                throw new \RuntimeException("{$fallback} fallback engine not available");
            }

            $result           = $engine->analyze($prompt);
            $result['engine'] = $fallback;
            Log::info("AfisEngine: used fallback {$fallback}");
            return $result;

        } catch (\Throwable $e) {
            Log::error('AfisEngine: both engines failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('All AI engines unavailable: ' . $e->getMessage());
        }
    }

    // ─── Cache helpers ────────────────────────────────────────────────────────

    private function getCacheKey(string $reportType, int $clientId, ?int $trackerId): string
    {
        return md5("{$reportType}:{$clientId}:{$trackerId}:" . now()->format('Y-m-d-H'));
    }

    private function getFromCache(string $reportType, int $clientId, ?int $trackerId): ?AfisAiReportCache
    {
        return AfisAiReportCache::where('cache_key', $this->getCacheKey($reportType, $clientId, $trackerId))
            ->where('expires_at', '>', now())
            ->first();
    }

    private function storeInCache(
        string $reportType,
        int    $clientId,
        ?int   $trackerId,
        string $response,
        string $engine
    ): void {
        $cacheHours = config('afisengine.cache_hours', 6);

        AfisAiReportCache::updateOrCreate(
            ['cache_key' => $this->getCacheKey($reportType, $clientId, $trackerId)],
            [
                'client_id'    => $clientId,
                'tracker_id'   => $trackerId,
                'report_type'  => $reportType,
                'response'     => $response,
                'engine_used'  => $engine,
                'generated_at' => now(),
                'expires_at'   => now()->addHours($cacheHours),
            ]
        );
    }

    private function reportFromCache(
        AfisAiReportCache $cached,
        int               $clientId,
        ?int              $trackerId,
        string            $reportType
    ): AfisAiReport {
        return new AfisAiReport([
            'client_id'    => $clientId,
            'tracker_id'   => $trackerId,
            'report_type'  => $reportType,
            'engine_used'  => $cached->engine_used . ' (cached)',
            'prompt_used'  => 'served from cache',
            'response'     => $cached->response,
            'tokens_used'  => 0,
            'status'       => 'completed',
            'created_at'   => $cached->generated_at,
        ]);
    }
}