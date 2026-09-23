@extends('layouts.app')

@section('title', 'Roles & Permissions - Administration')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-shield-check"></i>
            Roles & Permission Matrix
        </h1>
        <p class="hr-page-subtitle">Granular access control matrix for Super Admin, HR/Admin, and Restaurant Managers</p>
    </div>
</div>

<div style="display: flex; flex-direction: column; gap: 24px;">
    @foreach($roles as $role)
        <div class="hr-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding-bottom: 16px;">
                <div>
                    <h3 style="font-size: 18px; font-weight: 700; color: #fff; margin: 0; display: flex; align-items: center; gap: 10px;">
                        <span class="hr-badge {{ $role->slug === 'super-admin' ? 'hr-badge-purple' : ($role->slug === 'hr-admin' ? 'hr-badge-blue' : 'hr-badge-warning') }}" style="font-size: 13px;">
                            {{ $role->name }}
                        </span>
                        <span style="font-size: 13px; color: #94a3b8; font-weight: 400;">({{ $role->description }})</span>
                    </h3>
                </div>
                <div>
                    <span style="font-size: 12px; color: #94a3b8;">
                        Total Assigned: <strong>{{ $role->permissions->count() }}</strong> permissions
                    </span>
                </div>
            </div>

            <form method="POST" action="{{ route('hr.admin.roles.permissions', $role->id) }}">
                @csrf
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                    @foreach($permissions as $moduleName => $perms)
                        <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 8px; padding: 14px;">
                            <h4 style="font-size: 13px; font-weight: 700; color: #38bdf8; margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 0.5px;">
                                <i class="ph ph-folder-open"></i> {{ $moduleName }}
                            </h4>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                @php
                                    $assignedIds = $role->permissions->pluck('id')->toArray();
                                @endphp
                                @foreach($perms as $p)
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #cbd5e1; cursor: pointer;">
                                        <input type="checkbox" name="permissions[]" value="{{ $p->id }}" {{ in_array($p->id, $assignedIds) ? 'checked' : '' }} style="accent-color: #3b82f6;">
                                        <span>{{ $p->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="hr-btn hr-btn-primary">
                        <i class="ph ph-floppy-disk"></i>
                        <span>Save Permissions for {{ $role->name }}</span>
                    </button>
                </div>
            </form>
        </div>
    @endforeach
</div>
@endsection
