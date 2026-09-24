<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConfigController extends Controller
{
    public function businessSettings(): View
    {
        return view('config.business-settings');
    }

    public function accountSettings(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user) {
            $user->loadMissing('branch', 'roles');
        }

        $recentLogins = collect();
        if ($user) {
            try {
                $recentLogins = LoginAttempt::where('identity', $user->username)
                    ->orWhere('identity', $user->email)
                    ->orderBy('attempted_at', 'desc')
                    ->take(5)
                    ->get();
            } catch (\Throwable $e) {
                // If login_attempts table is empty or inaccessible
            }
        }

        return view('config.account-settings', compact('user', 'recentLogins'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'username'  => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email'     => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update([
            'full_name' => $validated['full_name'],
            'username'  => $validated['username'],
            'email'     => $validated['email'],
        ]);

        AuditLogger::log('Update', 'AccountSettings', $user->id, "User {$user->username} updated profile information");

        return back()->with('success', 'Profile information updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The provided current password does not match our records.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AuditLogger::log('Security', 'AccountSettings', $user->id, "User {$user->username} changed account password");

        return back()->with('success', 'Your password has been changed successfully.');
    }
}
