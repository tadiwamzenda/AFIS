<?php

namespace Modules\Core\Contracts;

interface AiEngineInterface
{
    public function analyze(string $prompt, array $context = []): string;
    public function analyzeAsync(string $jobClass, array $payload = []): void;
    public function isAvailable(): bool;
    public function getProviderName(): string;
}