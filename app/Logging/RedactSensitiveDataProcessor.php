<?php

namespace App\Logging;

use Monolog\LogRecord;

/**
 * Belt-and-suspenders redaction — a live example (a Navixy session hash
 * logged in plaintext via NavixyAuthService) was found and fixed at its
 * source. This scrubs the same class of value from ANY log record's
 * context, at any nesting depth, app-wide — a safety net for the same
 * mistake happening again unnoticed elsewhere, not a replacement for
 * fixing call sites directly.
 */
class RedactSensitiveDataProcessor
{
    private const REDACTED_KEYS = ['hash', 'password', 'api_key', 'navixy_api_key', 'secret', 'token'];

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(context: $this->scrub($record->context));
    }

    private function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->scrub($value);
            } elseif (in_array(strtolower((string) $key), self::REDACTED_KEYS, true)) {
                $data[$key] = '[REDACTED]';
            }
        }
        return $data;
    }
}