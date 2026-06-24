<?php

namespace Modules\AfisEngine\Services\Engines;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeEngine
{
    private string $apiKey;
    private string $model;
    private int    $maxTokens;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey    = config('afisengine.claude.api_key', '');
        $this->model     = config('afisengine.claude.model', 'claude-sonnet-4-6');
        $this->maxTokens = config('afisengine.claude.max_tokens', 4096);
        $this->baseUrl   = config('afisengine.claude.base_url', 'https://api.anthropic.com/v1');
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey) && $this->apiKey !== 'placeholder';
    }

    /**
     * @return array{response: string, tokens: int, duration_ms: int}
     */
    public function analyze(string $prompt): array
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('Claude API key not configured.');
        }

        $startTime = microtime(true);

        $response = Http::timeout(120)
            ->withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->post("{$this->baseUrl}/messages", [
                'model'      => $this->model,
                'max_tokens' => $this->maxTokens,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        if (!$response->successful()) {
            $error = $response->json('error.message') ?? 'Unknown Claude API error';
            Log::error('ClaudeEngine: API error', ['status' => $response->status(), 'error' => $error]);
            throw new \RuntimeException("Claude API error: {$error}");
        }

        $data = $response->json();

        return [
            'response'    => $data['content'][0]['text'] ?? '',
            'tokens'      => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
            'duration_ms' => $durationMs,
        ];
    }
}