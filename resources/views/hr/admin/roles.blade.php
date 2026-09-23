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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid rgba(168, 85, 247, 0.14); padding-bottom: 16px;">
                <div>
                    <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 10px;">
                        <span class="hr-badge {{ $role->slug === 'super-admin' ? 'hr-badge-purple' : ($role->slug === 'hr-admin' ? 'hr-badge-blue' : 'hr-badge-warning') }}" style="font-size: 13px;">
                            {{ $role->name }}
                        </span>
                        <span style="font-size: 13px; color: #64748b; font-weight: 400;">({{ $role->description }})</span>
                    </h3>
                </div>
                <div>
                    <span style="font-size: 12px; color: #64748b;">
                        Total Assigned: <strong>{{ $role->permissions->count() }}</strong> permissions
                    </span>
                </div>
            </div>

            <form method="POST" action="{{ route('hr.admin.roles.permissions', $role->id) }}">
                @csrf
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                    @foreach($permissions as $moduleName => $perms)
                        <div style="background: rgba(255, 255, 255, 0.65); border: 1px solid rgba(168, 85, 247, 0.18); border-radius: 12px; padding: 16px; box-shadow: 0 4px 14px rgba(168, 85, 247, 0.04); backdrop-filter: blur(12px);">
                            <h4 style="font-size: 13px; font-weight: 700; color: #9333ea; margin: 0 0 12px 0; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                                <i class="ph ph-folder-open"></i> {{ $moduleName }}
                            </h4>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                @php
                                    $assignedIds = $role->permissions->pluck('id')->toArray();
                                @endphp
                                @foreach($perms as $p)
                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; cursor: pointer; font-weight: 500;">
                                        <input type="checkbox" name="permissions[]" value="{{ $p->id }}" {{ in_array($p->id, $assignedIds) ? 'checked' : '' }} style="accent-color: #a855f7; width: 15px; height: 15px; cursor: pointer;">
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
