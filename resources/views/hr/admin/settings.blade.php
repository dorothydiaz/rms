@extends('layouts.app')

@section('title', 'System Settings - HR Operations')

@section('content')
<x-hr-tabs parent="system-administration" />

<div style="max-width: 800px;">
    <div class="hr-card">
        <form method="POST" action="{{ route('hr.admin.settings.update') }}">
            @csrf
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 22px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #f3e8ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                        <i class="ph ph-buildings"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 3px 0;">
                            Company Entity Information
                        </h3>
                        <p style="font-size: 12.5px; color: #64748b; margin: 0;">These statutory details appear on payslips, employment certificates, and tax filings.</p>
                    </div>
                </div>
                <span class="hr-badge hr-badge-purple" style="font-size: 11.5px; padding: 4px 10px; white-space: nowrap;">
                    <i class="ph ph-shield-check"></i> System Configuration
                </span>
            </div>

            <!-- Row 1: Company Identity -->
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

            <!-- Row 2: Official Contact Information -->
            <div class="hr-form-grid">
                <div class="hr-form-group">
                    <label class="hr-form-label">Official HR Email</label>
                    <input type="email" name="email" class="hr-input" placeholder="e.g. hr@gourmetbistro.ph" value="{{ old('email', $company->email ?? '') }}">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Contact Phone / Landline</label>
                    <input type="tel" name="phone" class="hr-input" placeholder="e.g. +63 (2) 8888-1234 or 0917-xxx-xxxx" pattern="[+]?[\d\s\-()]{7,25}" value="{{ old('phone', $company->phone ?? '') }}">
                </div>
            </div>

            <!-- Row 3: Tax Identification & Compliance Hint -->
            <div class="hr-form-grid">
                <div class="hr-form-group">
                    <label class="hr-form-label">BIR Tax Identification Number (TIN)</label>
                    <input type="text" name="tin" class="hr-input" placeholder="e.g. 123-456-789-000" value="{{ old('tin', $company->tin ?? '') }}">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Statutory Remittance Notice</label>
                    <div style="display: flex; align-items: center; gap: 8px; min-height: 40px; padding: 0 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12px; color: #64748b; box-sizing: border-box;">
                        <i class="ph ph-info" style="color: #7c3aed; font-size: 16px; flex-shrink: 0;"></i>
                        <span>Reflected on BIR 2316, SSS, PhilHealth, & Pag-IBIG reports.</span>
                    </div>
                </div>
            </div>

            <!-- Row 4: Physical Address -->
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
