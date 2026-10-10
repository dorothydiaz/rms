@extends('layouts.app')

@section('title', 'System Settings - HR Operations')

@section('content')
<x-hr-tabs parent="system-administration" />

<div style="max-width: 800px;">
    <div class="hr-card">
        <form method="POST" action="{{ route('hr.admin.settings.update') }}">
            @csrf
            <div style="border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding-bottom: 16px; margin-bottom: 20px;">
                <h3 style="font-size: 16px; font-weight: 700; color: #fff; margin: 0 0 6px 0;">
                    <i class="ph ph-buildings"></i> Company Entity Information
                </h3>
                <p style="font-size: 12px; color: #94a3b8; margin: 0;">These details appear on payslips, employment certificates, and tax filings.</p>
            </div>

            <div class="hr-form-grid">
                <div class="hr-form-group">
                    <label class="hr-form-label">Registered Company Name *</label>
                    <input type="text" name="name" class="hr-input" required value="{{ old('name', $company->name ?? 'Gourmet Bistro Inc.') }}">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Company Code / Short Name *</label>
                    <input type="text" name="code" class="hr-input" required value="{{ old('code', $company->code ?? 'GBI') }}">
                </div>
            </div>

            <div class="hr-form-grid">
                <div class="hr-form-group">
                    <label class="hr-form-label">BIR Tax Identification Number (TIN)</label>
                    <input type="text" name="tin" class="hr-input" placeholder="e.g. 123-456-789-000" value="{{ old('tin', $company->tin ?? '') }}">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Official HR Email</label>
                    <input type="email" name="email" class="hr-input" placeholder="e.g. hr@gourmetbistro.ph" value="{{ old('email', $company->email ?? '') }}">
                </div>
            </div>

            <div class="hr-form-group">
                <label class="hr-form-label">Contact Phone / Landline</label>
                <input type="tel" name="phone" class="hr-input" placeholder="e.g. +63 (2) 8888-1234 or 0917-xxx-xxxx" pattern="[+]?[\d\s\-()]{7,25}" value="{{ old('phone', $company->phone ?? '') }}">
            </div>

            <div class="hr-form-group">
                <label class="hr-form-label">Headquarters / Commissary Address</label>
                <textarea name="address" class="hr-textarea" rows="3" placeholder="Full business registration address...">{{ old('address', $company->address ?? '') }}</textarea>
            </div>

            <div style="margin-top: 24px; display: flex; justify-content: flex-end;">
                <button type="submit" class="hr-btn hr-btn-primary">
                    <i class="ph ph-floppy-disk"></i>
                    <span>Save System Settings</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
