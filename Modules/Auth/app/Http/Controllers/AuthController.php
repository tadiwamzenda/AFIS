<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Services\NavixyAuthService;

class AuthController extends Controller
{
    public function __construct(
        protected NavixyAuthService $navixyAuth
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

        // Step 1 — verify credentials against Navixy
        try {
            $navixy = $this->navixyAuth->authenticate($request->email, $request->password);
        } catch (\RuntimeException $e) {
            return back()
                ->withErrors(['email' => $e->getMessage()])
                ->withInput(['email' => $request->email]);
        }

        // Step 2 — match to a local user record
        $user = User::where('navixy_user_id', $navixy['user_id'])->first();

        if (!$user) {
            return back()
                ->withErrors(['email' => 'Your Navixy account is not registered in this system. Contact Bantu Track support.'])
                ->withInput(['email' => $request->email]);
        }

        if (!$user->is_active) {
            return back()
                ->withErrors(['email' => 'Your account is deactivated. Contact Bantu Track support.'])
                ->withInput(['email' => $request->email]);
        }

        // Step 3 — log in and store Navixy session data
        Auth::login($user, $request->boolean('remember'));

        session([
            'navixy_hash'         => $navixy['hash'],
            'navixy_user_id'      => $navixy['user_id'],
            'navixy_account_id'   => $navixy['account_id'],
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
            'hash_acquired_at', 'navixy_email', 'navixy_password_enc',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function redirectByRole(User $user)
    {
        return $user->isBtStaff()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('client.dashboard');
    }
}