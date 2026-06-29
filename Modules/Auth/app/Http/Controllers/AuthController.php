<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\AfisPipeline\Services\PipelineAuthService;
use Modules\Auth\Services\NavixyAuthService;

class AuthController extends Controller
{
    public function __construct(
        protected NavixyAuthService  $navixyAuth,
        protected PipelineAuthService $pipelineAuth,
    ) {}

    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }
        return view('auth::login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:4'],
        ]);

        // ── Step 1: Authenticate against Navixy (same URL for both instances) ──
        
        try {
            $navixy = $this->navixyAuth->authenticate($request->email, $request->password);
        } catch (\RuntimeException $e) {
            return back()
                ->withErrors(['email' => 'Invalid credentials. Please check your Navixy email and password.'])
                ->withInput(['email' => $request->email]);
        }
        

// Step 2: Find or auto-create local user
$userId = $navixy['user_id'] ?? null;

if ($userId) {
    // Sub-user with user_id returned — resolve or auto-create
    $user = $this->resolveOrCreateUser((int) $userId, $request->email, $navixy);
} else {
    // Navixy didn't return user_id (master accounts and some sub-users)
    // Fall back to email lookup — works for both BT staff and clients
    $user = User::where('email', $request->email)->first();

    if (!$user) {
        // Not in local DB yet — try auto-creating from sub-user sync data
        $instanceData = $this->findUserInInstances(0, $request->email);

        if ($instanceData) {
            [$instance, $subUserData] = $instanceData;
            $name = trim(($subUserData['first_name'] ?? '') . ' ' . ($subUserData['last_name'] ?? '')) ?: $request->email;

            $user = User::create([
                'name'                     => $name,
                'email'                    => $request->email,
                'navixy_user_id'           => $subUserData['id'] ?? 0,
                'navixy_account_id'        => 0,
                'navixy_instance'          => $instance,
                'navixy_security_group_id' => $subUserData['security_group_id'] ?? null,
                'role'                     => User::ROLE_CLIENT,
                'is_active'                => true,
            ]);
        }

        if (!$user) {
            return back()
                ->withErrors(['email' => 'Your account is not registered. Contact Bantu Track support.'])
                ->withInput(['email' => $request->email]);
        }
    }
}

        // ── Step 3: Create session ────────────────────────────────────────────
        Auth::login($user, $request->boolean('remember'));

        session([
            'navixy_hash'         => $navixy['hash'],
            'navixy_user_id'      => $navixy['user_id'],
            'navixy_account_id'   => $navixy['account_id'],
            'navixy_instance'     => $user->navixy_instance,
            'hash_acquired_at'    => now()->timestamp,
            'navixy_email'        => $request->email,
            'navixy_password_enc' => encrypt($request->password),
        ]);

        $request->session()->regenerate();

        return $this->redirectByRole($user);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->forget([
            'navixy_hash', 'navixy_user_id', 'navixy_account_id',
            'navixy_instance', 'hash_acquired_at',
            'navixy_email', 'navixy_password_enc',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // ─── Resolve or auto-create local user ───────────────────────────────────

    protected function resolveOrCreateUser(int $navixyUserId, string $email, array $navixyData): ?User
    {
        // 1 — Check if user already exists in either instance
        $user = User::where('navixy_user_id', $navixyUserId)->first();
        if ($user) {
            return $user;
        }

        // 2 — Unknown user — determine which instance they belong to
        $instanceData = $this->findUserInInstances($navixyUserId);

        if (!$instanceData) {
            // User authenticated with Navixy but is not a sub-user of either master account
            // This means they are a master account user themselves — check if BT staff
            Log::warning('AuthController: user not found in any instance sub-user list', [
                'navixy_user_id' => $navixyUserId,
                'email'          => $email,
            ]);
            return null;
        }

        // 3 — Auto-create local user record
        [$instance, $subUserData] = $instanceData;

        // Find matching client by security_group_id + instance
        $client = \Modules\AdmmInventory\Models\Client::where('navixy_security_group_id', $subUserData['security_group_id'])
            ->where('navixy_instance', $instance)
            ->first();

        $name = trim(($subUserData['first_name'] ?? '') . ' ' . ($subUserData['last_name'] ?? '')) ?: $email;

        $user = User::create([
            'name'                    => $name,
            'email'                   => $email,
            'navixy_user_id'          => $navixyUserId,
            'navixy_account_id'       => $navixyData['account_id'] ?? 0,
            'navixy_instance'         => $instance,
            'navixy_security_group_id'=> $subUserData['security_group_id'] ?? null,
            'role'                    => User::ROLE_CLIENT,
            'is_active'               => (bool) ($subUserData['activated'] ?? true),
        ]);

        Log::info('AuthController: auto-created local user', [
            'user_id'  => $user->id,
            'email'    => $email,
            'instance' => $instance,
            'client'   => $client?->name ?? 'unknown',
        ]);

        return $user;
    }

    /**
     * Search both instance sub-user lists for the given navixy_user_id.
     * Returns [instance_number, sub_user_data] or null if not found.
     */
protected function findUserInInstances(int $navixyUserId, string $email = ''): ?array
{
    foreach ([1, 2] as $instance) {
        try {
            $subUsers = $this->pipelineAuth->getSubUsers($instance);

            // Search by user_id if provided, otherwise by email
            $found = $navixyUserId > 0
                ? collect($subUsers)->firstWhere('id', $navixyUserId)
                : collect($subUsers)->firstWhere('login', $email);

            if ($found) {
                return [$instance, $found];
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("AuthController: could not fetch sub-users for instance {$instance}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    return null;
}

    protected function redirectByRole(User $user)
    {
        return $user->isBtStaff()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('client.dashboard');
    }
}