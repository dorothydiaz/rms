<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    protected int $maxAttempts = 5;
    protected int $lockoutMinutes = 15;

    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'identity' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identity = trim($request->input('identity'));
        $password = $request->input('password');
        $remember = $request->boolean('remember_me');
        $ip = $request->ip();

        // 1. Check Brute-Force lockout
        $cutoffTime = Carbon::now()->subMinutes($this->lockoutMinutes);
        $recentAttempts = LoginAttempt::where(function ($q) use ($ip, $identity) {
                $q->where('ip_address', $ip)
                  ->orWhere('identity', $identity);
            })
            ->where('attempted_at', '>=', $cutoffTime)
            ->orderBy('attempted_at', 'desc')
            ->limit(10)
            ->get();

        $consecutiveFailures = 0;
        foreach ($recentAttempts as $att) {
            if (!$att->is_successful) {
                $consecutiveFailures++;
            } else {
                break;
            }
        }

        if ($consecutiveFailures >= $this->maxAttempts) {
            $oldestFailed = $recentAttempts->last();
            $retrySeconds = $oldestFailed 
                ? max(60, ($this->lockoutMinutes * 60) - Carbon::now()->diffInSeconds($oldestFailed->attempted_at))
                : 300;
            $retryMinutes = max(1, ceil($retrySeconds / 60));

            return back()->withInput($request->only('identity'))
                ->with('error', "Too many failed attempts. Access locked for {$retryMinutes} minute(s) to protect your account.")
                ->with('locked', true);
        }

        // 2. Resolve identity to user (by username or email)
        $user = User::where('username', $identity)
            ->orWhere('email', $identity)
            ->first();

        if (!$user) {
            $this->recordAttempt($ip, $identity, false);
            return back()->withInput($request->only('identity'))
                ->with('error', 'Invalid username/email or password.');
        }

        if (!$user->isActive()) {
            $this->recordAttempt($ip, $identity, false);
            return back()->withInput($request->only('identity'))
                ->with('error', 'This account is currently inactive. Please contact your system administrator.');
        }

        // 3. Attempt Authentication
        $credentials = [
            'id' => $user->id,
            'password' => $password,
        ];

        if (Auth::attempt($credentials, $remember)) {
            $this->recordAttempt($ip, $identity, true);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        $this->recordAttempt($ip, $identity, false);
        return back()->withInput($request->only('identity'))
            ->with('error', 'Invalid username/email or password.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login', ['logged_out' => 1]);
    }

    protected function recordAttempt(string $ip, string $identity, bool $isSuccessful): void
    {
        try {
            LoginAttempt::create([
                'ip_address' => $ip,
                'identity' => $identity,
                'is_successful' => $isSuccessful,
                'attempted_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            // Fail silently so auth flow is not disrupted by logging errors
        }
    }
}
