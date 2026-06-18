<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Services\NavixyAuthService;

class NavixyHashRefreshMiddleware
{
    public function __construct(
        protected NavixyAuthService $navixyAuth
    ) {}

    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $acquiredAt = session('hash_acquired_at');

        // Proactive refresh: re-auth if hash is older than 20 minutes
        if ($acquiredAt && (now()->timestamp - $acquiredAt) > 1200) {
            $this->attemptRefresh($request);
        }

        return $next($request);
    }

    protected function attemptRefresh(Request $request): void
    {
        $email   = session('navixy_email');
        $encPass = session('navixy_password_enc');

        if (!$email || !$encPass) {
            $this->forceLogout($request);
            return;
        }

        try {
            $data = $this->navixyAuth->authenticate($email, decrypt($encPass));

            session([
                'navixy_hash'      => $data['hash'],
                'hash_acquired_at' => now()->timestamp,
            ]);

            Log::info('Navixy hash refreshed proactively', ['user_id' => Auth::id()]);

        } catch (\Throwable $e) {
            Log::warning('Navixy hash refresh failed — logging out', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ]);
            $this->forceLogout($request);
        }
    }

    protected function forceLogout(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}