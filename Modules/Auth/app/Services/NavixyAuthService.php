<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class NavixyAuthService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('auth-module.navixy_base_url', 'https://api.navixy.com/v2'), '/');
    }

    /**
     * Authenticate with Navixy and return hash, user_id, account_id.
     *
     * @throws RuntimeException on failure
     */
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
            Log::error('Navixy auth HTTP error', ['message' => $e->getMessage()]);
            throw new RuntimeException('Unable to reach the Navixy service. Please try again.');
        }

        $data = $response->json();

        if (empty($data['success'])) {
            $code = $data['status']['code'] ?? 'unknown';
            Log::warning('Navixy auth failed', ['code' => $code, 'email' => $email]);
            throw new RuntimeException('Invalid credentials. Please check your Navixy email and password.');
        }

        return [
            'hash'       => $data['hash'],
            'user_id'    => $data['user_id']    ?? null,
            'account_id' => $data['account_id'] ?? null,
        ];
    }
}