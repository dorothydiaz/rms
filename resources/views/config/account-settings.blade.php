@extends('layouts.app')

@section('title', 'Account Settings - Restaurant Management System')

@push('styles')
    @vite(['resources/css/modules/account-settings.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/account-settings.js'])
@endpush

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

<div class="account-settings-grid">
    <!-- 1. Personal Information Card -->
    <div class="hr-card account-card">
        <div class="account-card-header">
            <h3 class="account-card-title">
                <i class="ph ph-user-circle" style="color: #a855f7; font-size: 19px;"></i>
                Personal Profile Information
            </h3>
            <p class="account-card-desc">Update your basic personal and contact details</p>
        </div>

        <form method="POST" action="{{ route('account-settings.profile') }}" class="account-card-form">
            @csrf

            <div class="hr-form-group" style="margin-bottom: 14px;">
                <label class="hr-form-label">Full Name *</label>
                <div style="position: relative;">
                    <input type="text" name="full_name" class="hr-input" required value="{{ old('full_name', $user->full_name ?? $displayName) }}" style="padding-left: 38px;">
                    <i class="ph ph-identification-card" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></i>
                </div>
            </div>

            <div class="hr-form-group" style="margin-bottom: 14px;">
                <label class="hr-form-label">Username *</label>
                <div style="position: relative;">
                    <input type="text" name="username" class="hr-input" required value="{{ old('username', $user->username ?? $displayUsername) }}" style="padding-left: 38px;">
                    <i class="ph ph-at" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></i>
                </div>
                <small style="font-size: 11px; color: #64748b;">Used for authentication and internal logs.</small>
            </div>

            <div class="hr-form-group" style="margin-bottom: 14px;">
                <label class="hr-form-label">Email Address *</label>
                <div style="position: relative;">
                    <input type="email" name="email" class="hr-input" required value="{{ old('email', $user->email ?? $displayEmail) }}" style="padding-left: 38px;">
                    <i class="ph ph-envelope" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></i>
                </div>
            </div>

            <div class="account-role-grid" style="margin-bottom: 16px;">
                <div class="hr-form-group">
                    <label class="hr-form-label">Assigned Role</label>
                    <input type="text" class="hr-input" value="{{ $displayRole }}" readonly disabled title="{{ $displayRole }}" style="background: rgba(241, 245, 249, 0.8); cursor: not-allowed; text-overflow: ellipsis; font-size: 12px; padding: 8px 10px;">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Branch Access</label>
                    <input type="text" class="hr-input" value="{{ $displayBranch }}" readonly disabled title="{{ $displayBranch }}" style="background: rgba(241, 245, 249, 0.8); cursor: not-allowed; text-overflow: ellipsis; font-size: 12px; padding: 8px 10px;">
                </div>
            </div>

            <div class="account-card-actions">
                <button type="submit" class="hr-btn hr-btn-primary">
                    <i class="ph ph-floppy-disk"></i>
                    <span>Save Profile Changes</span>
                </button>
            </div>
        </form>
    </div>

    <!-- 2. Security & Password Card -->
    <div class="hr-card account-card">
        <div class="account-card-header">
            <h3 class="account-card-title">
                <i class="ph ph-lock-key" style="color: #ec4899; font-size: 19px;"></i>
                Security & Password
            </h3>
            <p class="account-card-desc">Ensure your account is protected with a strong password</p>
        </div>

        <form method="POST" action="{{ route('account-settings.password') }}" class="account-card-form">
            @csrf

            <div class="hr-form-group" style="margin-bottom: 14px;">
                <label class="hr-form-label">Current Password *</label>
                <div style="position: relative;">
                    <input type="password" name="current_password" id="current_password" class="hr-input" required placeholder="Enter existing password" style="padding-left: 38px; padding-right: 38px;">
                    <i class="ph ph-key" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></i>
                    <button type="button" onclick="togglePassVisibility('current_password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;">
                        <i class="ph ph-eye"></i>
                    </button>
                </div>
            </div>

            <div class="hr-form-group" style="margin-bottom: 14px;">
                <label class="hr-form-label">New Password *</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="new_password" class="hr-input" required placeholder="Minimum 8 characters" style="padding-left: 38px; padding-right: 38px;">
                    <i class="ph ph-lock-simple" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></i>
                    <button type="button" onclick="togglePassVisibility('new_password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;">
                        <i class="ph ph-eye"></i>
                    </button>
                </div>
                <small style="font-size: 11px; color: #64748b;">Must contain at least 8 characters with letters and numbers.</small>
            </div>

            <div class="hr-form-group" style="margin-bottom: 16px;">
                <label class="hr-form-label">Confirm New Password *</label>
                <div style="position: relative;">
                    <input type="password" name="password_confirmation" id="password_confirmation" class="hr-input" required placeholder="Repeat new password" style="padding-left: 38px; padding-right: 38px;">
                    <i class="ph ph-lock-simple-open" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></i>
                    <button type="button" onclick="togglePassVisibility('password_confirmation', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;">
                        <i class="ph ph-eye"></i>
                    </button>
                </div>
            </div>

            <div class="account-card-actions">
                <button type="submit" class="hr-btn hr-btn-primary">
                    <i class="ph ph-shield-check"></i>
                    <span>Update Password</span>
                </button>
            </div>
        </form>
    </div>

    <!-- 3. Account Activity & Security Overview Card -->
    <div class="hr-card account-card account-card-table">
        <div class="account-card-header" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
            <div>
                <h3 class="account-card-title">
                    <i class="ph ph-shield-check" style="color: #6366f1; font-size: 19px;"></i>
                    Recent Sign-In Activity
                </h3>
                <p class="account-card-desc">Review your recent sign-ins & security status</p>
            </div>
            <span class="hr-badge hr-badge-info" style="font-size: 11px; padding: 4px 10px; white-space: nowrap;">
                <i class="ph ph-shield"></i> Rate-Limit Guard Active
            </span>
        </div>

        @if(isset($recentLogins) && $recentLogins->isNotEmpty())
            <div class="hr-table-wrapper" style="flex: 1; min-height: 220px;">
                <table class="hr-table" style="font-size: 12.5px; width: 100%;">
                    <thead>
                        <tr>
                            <th style="padding: 10px 14px; white-space: nowrap;">Date & Time</th>
                            <th style="padding: 10px 14px; white-space: nowrap;">IP Address</th>
                            <th style="padding: 10px 14px;">Identity</th>
                            <th style="padding: 10px 14px; white-space: nowrap;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentLogins as $login)
                            <tr>
                                <td style="padding: 10px 14px; white-space: nowrap;">
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="ph ph-clock" style="color: #94a3b8;"></i>
                                        <span>{{ $login->attempted_at ? \Carbon\Carbon::parse($login->attempted_at)->format('M d, Y h:i A') : 'N/A' }}</span>
                                    </div>
                                </td>
                                <td style="padding: 10px 14px; white-space: nowrap;">
                                    <code style="font-size: 11.5px; background: rgba(241, 245, 249, 0.9); padding: 3px 7px; border-radius: 5px; border: 1px solid rgba(226, 232, 240, 0.85);">{{ $login->ip_address }}</code>
                                </td>
                                <td style="padding: 10px 14px; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $login->identity }}">
                                    {{ $login->identity }}
                                </td>
                                <td style="padding: 10px 14px; white-space: nowrap;">
                                    @if($login->is_successful)
                                        <span class="hr-badge hr-badge-success" style="font-size: 11px; padding: 3px 8px;">
                                            <i class="ph ph-check"></i> Successful
                                        </span>
                                    @else
                                        <span class="hr-badge hr-badge-danger" style="font-size: 11px; padding: 3px 8px;">
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
            <div class="account-empty-state">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(99, 102, 241, 0.1); color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 12px;">
                    <i class="ph ph-shield-check"></i>
                </div>
                <div style="font-size: 13.5px; font-weight: 600; color: #334155; margin-bottom: 4px;">Active & Protected</div>
                <span style="font-size: 12px; color: #64748b; line-height: 1.5; max-width: 320px;">No previous suspicious attempts recorded. Your session is active and protected by rate-limiting guards.</span>
            </div>
        @endif
    </div>
</div>
@endsection
