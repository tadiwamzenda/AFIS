<?php

namespace Modules\NavixyClient\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\NavixyClientInterface;
use Modules\NavixyClient\Exceptions\NavixyException;
use Modules\NavixyClient\Exceptions\NavixySessionExpiredException;

class NavixyClientService implements NavixyClientInterface
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('auth-module.navixy_base_url', 'https://api.navixy.com/v2'),
            '/'
        );
    }

    // ─── Interface implementation ────────────────────────────────────────────

    public function authenticate(string $email, string $password): array
    {
        try {
            $response = Http::timeout(15)
                ->asForm()
                ->post("{$this->baseUrl}/user/auth", [
                    'login'    => $email,
                    'password' => $password,
                ]);
        } catch (\Throwable $e) {
            throw new NavixyException('Navixy unreachable: ' . $e->getMessage());
        }

        $data = $response->json();

        if (empty($data['success'])) {
            throw new NavixyException('Navixy auth failed: ' . ($data['status']['description'] ?? 'unknown'));
        }

        return [
            'hash'       => $data['hash'],
            'user_id'    => $data['user_id']    ?? null,
            'account_id' => $data['account_id'] ?? null,
        ];
    }

    public function get(string $endpoint, array $params = []): array
    {
        $params['hash'] = $this->getHash();

        return $this->request(fn() =>
            Http::timeout(30)->get("{$this->baseUrl}/{$endpoint}", $params),
            $endpoint
        );
    }

    public function post(string $endpoint, array $data = []): array
    {
        $data['hash'] = $this->getHash();

        return $this->request(fn() =>
            Http::timeout(30)->asForm()->post("{$this->baseUrl}/{$endpoint}", $data),
            $endpoint
        );
    }

    public function refreshHash(): bool
    {
        $email   = session('navixy_email');
        $encPass = session('navixy_password_enc');

        if (!$email || !$encPass) {
            return false;
        }

        try {
            $data = $this->authenticate($email, decrypt($encPass));

            session([
                'navixy_hash'      => $data['hash'],
                'hash_acquired_at' => now()->timestamp,
            ]);

            Log::info('NavixyClient: hash refreshed reactively');
            return true;

        } catch (\Throwable $e) {
            Log::warning('NavixyClient: reactive hash refresh failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getHash(): ?string
    {
        return session('navixy_hash');
    }

    // ─── Internal helpers ────────────────────────────────────────────────────

    /**
     * Execute an HTTP request callable with one automatic retry on session expiry (error 101).
     */
    protected function request(callable $httpCall, string $endpoint): array
    {
        try {
            $response = $httpCall();
        } catch (\Throwable $e) {
            throw new NavixyException("Navixy HTTP error on [{$endpoint}]: " . $e->getMessage());
        }

        if (!$response->successful()) {
            throw new NavixyException("Navixy HTTP {$response->status()} on [{$endpoint}]");
        }

        $data = $response->json();

        // Reactive hash refresh on error code 101 (session expired)
        if (!empty($data['status']['code']) && $data['status']['code'] === 101) {
            Log::info('NavixyClient: session expired (101), attempting reactive refresh');

            if ($this->refreshHash()) {
                // Retry the original call once with the new hash
                return $this->request($httpCall, $endpoint);
            }

            throw new NavixySessionExpiredException('Navixy session expired and refresh failed.');
        }

        if (empty($data['success'])) {
            $description = $data['status']['description'] ?? 'Unknown error';
            $code        = $data['status']['code']        ?? 0;
            Log::warning("NavixyClient: API error on [{$endpoint}]", compact('code', 'description'));
            throw new NavixyException("Navixy error on [{$endpoint}]: {$description} (code {$code})");
        }

        return $data;
    }
}