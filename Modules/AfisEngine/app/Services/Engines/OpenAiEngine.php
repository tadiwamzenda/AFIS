<?php

namespace Modules\AfisEngine\Services\Engines;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiEngine
{
    private string $apiKey;
    private string $model;
    private int    $maxTokens;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey    = config('afisengine.openai.api_key', '');
        $this->model     = config('afisengine.openai.model', 'gpt-4o');
        $this->maxTokens = config('afisengine.openai.max_tokens', 4096);
        $this->baseUrl   = config('afisengine.openai.base_url', 'https://api.openai.com/v1');
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
            throw new \RuntimeException('OpenAI API key not configured.');
        }

                $startTime = microtime(true);

        $response = Http::connectTimeout(15)
            ->timeout(120)
            ->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ])
            ->post("{$this->baseUrl}/chat/completions", [
                'model'      => $this->model,
                'max_tokens' => $this->maxTokens,
                'messages'   => [
                    [
                        'role'    => 'system',
                        'content' => 'You are an expert fleet intelligence analyst. Provide detailed, accurate, and actionable analysis.',
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        if (!$response->successful()) {
            $error = $response->json('error.message') ?? 'Unknown OpenAI error';
            Log::error('OpenAiEngine: API error', ['status' => $response->status(), 'error' => $error]);
            throw new \RuntimeException("OpenAI API error: {$error}");
        }

        $data = $response->json();
        $text = $data['choices'][0]['message']['content'] ?? '';

        // Same reasoning as ClaudeEngine — a 200 with no usable content
        // must fail loudly, not save an empty 'completed' report.
        if (trim($text) === '') {
            Log::error('OpenAiEngine: successful response but empty/unexpected content structure', ['response_keys' => array_keys($data)]);
            throw new \RuntimeException('OpenAI API returned a successful response with no usable content.');
        }

        return [
            'response'    => $text,
            'tokens'      => $data['usage']['total_tokens'] ?? 0,
            'duration_ms' => $durationMs,
        ];
    }
}