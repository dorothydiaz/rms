@extends('layouts.app')

@section('title', 'Employee Profile Required')

@section('content')
<div class="card" style="max-width: 600px; margin: 40px auto; text-align: center; padding: 40px 30px;">
    <div style="font-size: 48px; color: #f59e0b; margin-bottom: 20px;">
        <i class="ph ph-identification-card"></i>
    </div>
    <h2 style="margin-bottom: 12px; font-weight: 700; color: #1e293b;">Employee Profile Setup Required</h2>
    <p style="color: #64748b; margin-bottom: 24px; line-height: 1.6;">
        Your user account (<strong>{{ $user->username }}</strong>) is not currently connected to an active employee profile. 
        Please contact your HR Administrator or Company Manager to assign your Employee ID.
    </p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
        <i class="ph ph-arrow-left"></i> Return to Main Operations
    </a>
</div>
@endsection
