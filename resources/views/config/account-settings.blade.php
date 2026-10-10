@extends('layouts.app')

@section('title', 'Account Settings - Restaurant Management System')

@section('content')
@php
    $displayName = $user ? ($user->full_name ?? $user->username) : 'Dorothy Diaz';
    $displayRole = $user ? $user->role : 'Manager';
    $displayEmail = $user ? $user->email : 'dorothy@rms.local';
    $displayUsername = $user ? $user->username : 'dorothy';
    $displayBranch = ($user && $user->branch) ? $user->branch->name : 'All Branches (Universal)';

    // Initials calculation
    $nameParts = explode(' ', trim($displayName));
    $initials = '';
    foreach (array_slice($nameParts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    if (empty($initials)) {
        $initials = 'U';
    }
@endphp

<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-user-gear"></i>
            Account Settings
        </h1>
        <p class="hr-page-subtitle">Manage your personal profile, credentials, and account security preferences</p>
    </div>
</div>

@if ($errors->any())
    <div class="login-alert danger" style="margin-bottom: 20px;">
        <i class="ph ph-warning-circle"></i>
        <div>
            <strong style="display:block; margin-bottom: 4px;">Please correct the errors below:</strong>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<!-- Profile Summary Hero Card -->
<div class="hr-card" style="margin-bottom: 24px; padding: 24px 28px;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, rgba(236, 72, 153, 0.2), rgba(168, 85, 247, 0.25)); border: 2px solid rgba(168, 85, 247, 0.35); color: #a855f7; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 800; font-family: var(--font-heading); box-shadow: 0 4px 18px rgba(168, 85, 247, 0.15);">
                {{ $initials }}
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 4px;">
                    <h2 style="font-size: 20px; font-weight: 700; margin: 0; background: linear-gradient(135deg, #ec4899, #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        {{ $displayName }}
                    </h2>
                    <span class="hr-badge hr-badge-purple">
                        <i class="ph ph-shield-check"></i> {{ $displayRole }}
                    </span>
                    <span class="hr-badge hr-badge-success">
                        <i class="ph ph-check-circle"></i> Active
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap; font-size: 13px; color: #64748b;">
                    <span><i class="ph ph-at" style="vertical-align: middle;"></i> {{ $displayUsername }}</span>
                    <span><i class="ph ph-envelope-simple" style="vertical-align: middle;"></i> {{ $displayEmail }}</span>
                    <span><i class="ph ph-storefront" style="vertical-align: middle;"></i> {{ $displayBranch }}</span>
                    @if($user && $user->created_at)
                        <span><i class="ph ph-calendar" style="vertical-align: middle;"></i> Member since {{ $user->created_at->format('M Y') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 24px;">
    <!-- 1. Personal Information Card -->
    <div class="hr-card" style="margin-bottom: 0;">
        <div style="border-bottom: 1px solid rgba(168, 85, 247, 0.12); padding-bottom: 14px; margin-bottom: 18px;">
            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-user-circle" style="color: #a855f7; font-size: 20px;"></i>
                Personal Profile Information
            </h3>
            <p style="font-size: 12.5px; color: #64748b; margin: 0;">Update your basic personal and contact details</p>
        </div>

        <form method="POST" action="{{ route('account-settings.profile') }}">
            @csrf

            <div class="hr-form-group" style="margin-bottom: 16px;">
                <label class="hr-form-label">Full Name *</label>
                <div style="position: relative;">
                    <input type="text" name="full_name" class="hr-input" required value="{{ old('full_name', $user->full_name ?? $displayName) }}" style="padding-left: 38px;">
                    <i class="ph ph-identification-card" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></i>
                </div>
            </div>

            <div class="hr-form-group" style="margin-bottom: 16px;">
                <label class="hr-form-label">Username *</label>
                <div style="position: relative;">
                    <input type="text" name="username" class="hr-input" required value="{{ old('username', $user->username ?? $displayUsername) }}" style="padding-left: 38px;">
                    <i class="ph ph-at" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></i>
                </div>
                <small style="font-size: 11px; color: #64748b;">Used for authentication and internal logs.</small>
            </div>

            <div class="hr-form-group" style="margin-bottom: 16px;">
                <label class="hr-form-label">Email Address *</label>
                <div style="position: relative;">
                    <input type="email" name="email" class="hr-input" required value="{{ old('email', $user->email ?? $displayEmail) }}" style="padding-left: 38px;">
                    <i class="ph ph-envelope" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></i>
                </div>
            </div>

            <div class="hr-form-grid" style="margin-bottom: 20px;">
                <div class="hr-form-group">
                    <label class="hr-form-label">Assigned Role</label>
                    <input type="text" class="hr-input" value="{{ $displayRole }}" readonly disabled style="background: rgba(241, 245, 249, 0.8); cursor: not-allowed;">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Branch Access</label>
                    <input type="text" class="hr-input" value="{{ $displayBranch }}" readonly disabled style="background: rgba(241, 245, 249, 0.8); cursor: not-allowed;">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; pt-2;">
                <button type="submit" class="hr-btn hr-btn-primary">
                    <i class="ph ph-floppy-disk"></i>
                    <span>Save Profile Changes</span>
                </button>
            </div>
        </form>
    </div>

    <!-- 2. Security & Password Card -->
    <div class="hr-card" style="margin-bottom: 0;">
        <div style="border-bottom: 1px solid rgba(168, 85, 247, 0.12); padding-bottom: 14px; margin-bottom: 18px;">
            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-lock-key" style="color: #ec4899; font-size: 20px;"></i>
                Security & Password
            </h3>
            <p style="font-size: 12.5px; color: #64748b; margin: 0;">Ensure your account is protected with a strong password</p>
        </div>

        <form method="POST" action="{{ route('account-settings.password') }}">
            @csrf

            <div class="hr-form-group" style="margin-bottom: 16px;">
                <label class="hr-form-label">Current Password *</label>
                <div style="position: relative;">
                    <input type="password" name="current_password" id="current_password" class="hr-input" required placeholder="Enter existing password" style="padding-left: 38px; padding-right: 38px;">
                    <i class="ph ph-key" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></i>
                    <button type="button" onclick="togglePassVisibility('current_password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;">
                        <i class="ph ph-eye"></i>
                    </button>
                </div>
            </div>

            <div class="hr-form-group" style="margin-bottom: 16px;">
                <label class="hr-form-label">New Password *</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="new_password" class="hr-input" required placeholder="Minimum 8 characters" style="padding-left: 38px; padding-right: 38px;">
                    <i class="ph ph-lock-simple" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></i>
                    <button type="button" onclick="togglePassVisibility('new_password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;">
                        <i class="ph ph-eye"></i>
                    </button>
                </div>
                <small style="font-size: 11px; color: #64748b;">Must contain at least 8 characters with a mix of letters and numbers.</small>
            </div>

            <div class="hr-form-group" style="margin-bottom: 20px;">
                <label class="hr-form-label">Confirm New Password *</label>
                <div style="position: relative;">
                    <input type="password" name="password_confirmation" id="password_confirmation" class="hr-input" required placeholder="Repeat new password" style="padding-left: 38px; padding-right: 38px;">
                    <i class="ph ph-lock-simple-open" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></i>
                    <button type="button" onclick="togglePassVisibility('password_confirmation', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;">
                        <i class="ph ph-eye"></i>
                    </button>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="hr-btn hr-btn-primary">
                    <i class="ph ph-shield-check"></i>
                    <span>Update Password</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Account Activity & Security Overview -->
<div class="hr-card" style="margin-top: 24px;">
    <div style="border-bottom: 1px solid rgba(168, 85, 247, 0.12); padding-bottom: 14px; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div>
            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-shield-warning" style="color: #6366f1; font-size: 20px;"></i>
                Account Security & Recent Sign-In Activity
            </h3>
            <p style="font-size: 12.5px; color: #64748b; margin: 0;">Review your recent sign-ins and safety measures</p>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <span class="hr-badge hr-badge-info">
                <i class="ph ph-shield"></i> Rate-Limit Guard Active
            </span>
        </div>
    </div>

    @if(isset($recentLogins) && $recentLogins->isNotEmpty())
        <div class="hr-table-wrapper">
            <table class="hr-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>IP Address</th>
                        <th>Identity Used</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLogins as $login)
                        <tr>
                            <td>
                                <i class="ph ph-clock" style="color: #94a3b8; margin-right: 4px;"></i>
                                {{ $login->attempted_at ? \Carbon\Carbon::parse($login->attempted_at)->format('M d, Y h:i A') : 'N/A' }}
                            </td>
                            <td>
                                <code>{{ $login->ip_address }}</code>
                            </td>
                            <td>{{ $login->identity }}</td>
                            <td>
                                @if($login->is_successful)
                                    <span class="hr-badge hr-badge-success">
                                        <i class="ph ph-check"></i> Successful
                                    </span>
                                @else
                                    <span class="hr-badge hr-badge-danger">
                                        <i class="ph ph-x"></i> Failed Attempt
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div style="display: flex; align-items: center; gap: 14px; padding: 14px 18px; background: rgba(241, 245, 249, 0.7); border-radius: 12px; border: 1px solid rgba(226, 232, 240, 0.8);">
            <i class="ph ph-info" style="font-size: 22px; color: #64748b;"></i>
            <span style="font-size: 13px; color: #475569;">No previous suspicious login attempts recorded. Your session is active and secure.</span>
        </div>
    @endif
</div>

@push('styles')
<script>
function togglePassVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('ph-eye');
            icon.classList.add('ph-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('ph-eye-slash');
            icon.classList.add('ph-eye');
        }
    }
}
</script>
@endpush
@endsection
