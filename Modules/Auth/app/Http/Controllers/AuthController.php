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
            // Not in local DB yet — try finding in master account sub-users
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
                    'client_id'                => $subUserData['_client_id'] ?? null,
                    'role'                     => User::ROLE_CLIENT,
                    'is_active'                => true,
                ]);
            }

            if (!$user) {
                // Last resort — check if this is an independent client master account
                // These users authenticate directly against their own Navixy account
                // and won't appear in any sub-user list
                $independentClient = $this->findIndependentClientByEmail($request->email);

                if ($independentClient) {
                // Check if user already exists by email (may have been created before)
                $user = User::where('email', $request->email)->first();

                if (!$user) {
                    $user = User::create([
                        'name'             => $request->email,
                        'email'            => $request->email,
                        // Master account users don't have a navixy_user_id — use null
                        // to avoid unique constraint violation (multiple masters = multiple nulls)
                        'navixy_user_id'   => $navixy['user_id'] ?: null,
                        'navixy_account_id'=> $navixy['account_id'] ?: null,
                        'navixy_instance'  => $independentClient->navixy_instance,
                        'client_id'        => $independentClient->id,
                        'role'             => User::ROLE_CLIENT,
                        'is_active'        => true,
                    ]);
                } else {
                    // Update existing user with client_id if missing
                    if (!$user->client_id) {
                        $user->update(['client_id' => $independentClient->id]);
                    }
                }
            }
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

        // Use client directly if found via independent account search
// Otherwise find by security_group_id (master account sub-users)
$client = $subUserData['_client']
    ?? \Modules\AdmmInventory\Models\Client::where('navixy_security_group_id', $subUserData['security_group_id'])
        ->where('navixy_instance', $instance)
        ->first();

$name = trim(($subUserData['first_name'] ?? '') . ' ' . ($subUserData['last_name'] ?? '')) ?: $email;

$user = User::create([
    'name'                     => $name,
    'email'                    => $email,
    'navixy_user_id'           => $navixyUserId,
    'navixy_account_id'        => $navixyData['account_id'] ?? 0,
    'navixy_instance'          => $instance,
    'navixy_security_group_id' => $subUserData['security_group_id'] ?? null,
    'role'                     => User::ROLE_CLIENT,
    'is_active'                => (bool) ($subUserData['activated'] ?? true),
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
        // ── Step 1: Search Bantu Track master account sub-users (instances 1 & 2) ──
        foreach ([1, 2] as $instance) {
            try {
                $subUsers = $this->pipelineAuth->getSubUsers($instance);

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

        // ── Step 2: Search independent client accounts ────────────────────────────
        // These clients have their own Navixy master accounts — not sub-users of Bantu Track
        $independentClients = \Modules\AdmmInventory\Models\Client::where('is_active', true)
            ->whereNotNull('navixy_api_key')
            ->where('id', '!=', 21)
            ->get();

        foreach ($independentClients as $client) {
            try {
                // Set API key to authenticate as this client's account
                $this->pipelineAuth->setClientApiKey($client->navixy_api_key);

                $subUsers = $this->pipelineAuth->getSubUsers($client->navixy_instance);

                $found = $navixyUserId > 0
                    ? collect($subUsers)->firstWhere('id', $navixyUserId)
                    : collect($subUsers)->firstWhere('login', $email);

                if ($found) {
                    // Attach client_id so we can map this user to the correct fleet
                    $found['_client_id'] = $client->id;
                    $found['_client']    = $client;
                    return [$client->navixy_instance, $found];
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("AuthController: could not fetch sub-users for {$client->name}", [
                    'error' => $e->getMessage(),
                ]);
            } finally {
                $this->pipelineAuth->setClientApiKey(null);
            }
        }

        return null;
    }

        /**
     * Check if the email belongs to an independent client's Navixy account
     * by fetching user info using their stored API key.
     */
    protected function findIndependentClientByEmail(string $email): ?\Modules\AdmmInventory\Models\Client
    {
        $independentClients = \Modules\AdmmInventory\Models\Client::where('is_active', true)
            ->whereNotNull('navixy_api_key')
            ->where('id', '!=', 21)
            ->get();

        foreach ($independentClients as $client) {
            try {
                $this->pipelineAuth->setClientApiKey($client->navixy_api_key);

                // Check if this email exists as a user in this account
                $subUsers = $this->pipelineAuth->getSubUsers($client->navixy_instance);
                $found = collect($subUsers)->firstWhere('login', $email);

                if ($found) {
                    $this->pipelineAuth->setClientApiKey(null);
                    return $client;
                }

                // Also check if the master account email matches
                // (the client admin logging in with their own master account)
                $response = \Illuminate\Support\Facades\Http::timeout(15)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post(config('auth-module.navixy_base_url') . '/user/get_info', [
                        'hash' => $client->navixy_api_key,
                    ]);
                $info = $response->json();
                if (($info['user_info']['login'] ?? '') === $email) {
                    $this->pipelineAuth->setClientApiKey(null);
                    return $client;
                }

            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("AuthController: findIndependentClientByEmail failed for {$client->name}", [
                    'error' => $e->getMessage(),
                ]);
            } finally {
                $this->pipelineAuth->setClientApiKey(null);
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