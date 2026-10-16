@extends('layouts.app')

@section('title', 'Business Settings - Company & Branch Management')

@push('styles')
    @vite(['resources/css/modules/business-settings.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/business-settings.js'])
@endpush

@section('content')
<div class="biz-settings-wrapper">
    <!-- Page Header & Summary Strip (Standardized Surface & Metrics) -->
    <div class="biz-header-card">
        <div class="biz-header-main">
            <div class="biz-avatar-logo">
                @if($company && $company->company_logo)
                    <img src="{{ asset('storage/' . $company->company_logo) }}" alt="Logo" class="biz-logo-img">
                @else
                    <div class="biz-logo-placeholder">
                        <i class="ph ph-buildings"></i>
                    </div>
                @endif
            </div>
            <div class="biz-header-info">
                <div class="biz-title-row">
                    <h1 class="biz-company-title">{{ $company->company_trade_name ?: $company->company_legal_name }}</h1>
                    <span class="biz-badge-code">{{ $company->company_code }}</span>
                    <span class="biz-badge-type">{{ $company->company_type }}</span>
                    @if($company->company_is_active)
                        <span class="biz-badge-status active"><i class="ph ph-check-circle"></i> Active Entity</span>
                    @else
                        <span class="biz-badge-status inactive"><i class="ph ph-x-circle"></i> Inactive</span>
                    @endif
                </div>
                <p class="biz-legal-name"><i class="ph ph-certificate"></i> Registered Legal Entity: <strong>{{ $company->company_legal_name }}</strong></p>
                <div class="biz-meta-chips">
                    <span><i class="ph ph-identification-badge"></i> TIN: <strong>{{ $company->company_tax_id ?: 'Not Registered' }}</strong></span>
                    <span><i class="ph ph-map-pin"></i> {{ $company->company_city ?: 'Philippines' }}</span>
                    <span><i class="ph ph-currency-circle-dollar"></i> Base Currency: <strong>{{ $company->company_base_currency_code }}</strong></span>
                    <span><i class="ph ph-storefront"></i> <strong>{{ $branches->count() }}</strong> Connected Branches</span>
                </div>
            </div>
        </div>

        <div class="biz-header-stats">
            <div class="stat-pill-box">
                <span class="stat-label">Document Vault Radar</span>
                <div class="stat-radar-badges">
                    <span class="radar-chip active" title="Active Compliant Documents">
                        <i class="ph ph-shield-check"></i> {{ $vaultStats['active'] }} Active
                    </span>
                    @if($vaultStats['expiring_soon'] > 0)
                        <span class="radar-chip warning" title="Documents Expiring in <=30 Days">
                            <i class="ph ph-warning"></i> {{ $vaultStats['expiring_soon'] }} Expiring Soon
                        </span>
                    @endif
                    @if($vaultStats['expired'] > 0)
                        <span class="radar-chip danger" title="Expired Licenses / Permits">
                            <i class="ph ph-x-octagon"></i> {{ $vaultStats['expired'] }} Expired
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @php
        $activeTab = request()->get('tab', 'general');
    @endphp

    <!-- Enterprise Navigation Shortcut to New Modular Directory -->
    <div style="background: linear-gradient(135deg, #eff6ff, #f8fafc); border: 1px solid #bfdbfe; border-radius: 12px; padding: 12px 18px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="ph ph-buildings" style="font-size: 24px; color: #2563eb;"></i>
            <div>
                <strong style="color: #1e3a8a; font-size: 13px;">Multi-Entity Companies &amp; Branches Directory</strong>
                <p style="margin: 0; font-size: 11.5px; color: #475569;">Access the new Modular Table console with reorderable columns, branch pills, and company profiles.</p>
            </div>
        </div>
        <a href="{{ route('config.companies-branches.index') }}" style="background: #2563eb; color: #ffffff; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(37,99,235,0.2);">
            <span>Open Companies &amp; Branches</span>
            <i class="ph ph-arrow-right"></i>
        </a>
    </div>

    <!-- Universal Functional Navigation Tabs (Zero Layout Shift) -->
    <div class="biz-tabs-bar">
        <a href="{{ route('config.business-settings', ['tab' => 'general']) }}" class="biz-tab-btn {{ $activeTab === 'general' ? 'active' : '' }}">
            <i class="ph ph-storefront"></i>
            <div class="tab-label-group">
                <span class="tab-main-text">Business & Branch Details</span>
                <span class="tab-sub-text">General Business Profile</span>
            </div>
        </a>
        <a href="{{ route('config.business-settings', ['tab' => 'legal']) }}" class="biz-tab-btn {{ $activeTab === 'legal' ? 'active' : '' }}">
            <i class="ph ph-files"></i>
            <div class="tab-label-group">
                <span class="tab-main-text">COR & Compliance Hub</span>
                <span class="tab-sub-text">Certificate & Legal Hub</span>
            </div>
            @if($vaultStats['expiring_soon'] > 0 || $vaultStats['expired'] > 0)
                <span class="tab-alert-badge">{{ $vaultStats['expiring_soon'] + $vaultStats['expired'] }}</span>
            @endif
        </a>
        <a href="{{ route('config.business-settings', ['tab' => 'hierarchy']) }}" class="biz-tab-btn {{ $activeTab === 'hierarchy' ? 'active' : '' }}">
            <i class="ph ph-tree-structure"></i>
            <div class="tab-label-group">
                <span class="tab-main-text">Organizational Hierarchy & Relationships</span>
                <span class="tab-sub-text">Structure & Sharing Rules</span>
            </div>
        </a>
        <a href="{{ route('config.business-settings', ['tab' => 'branches']) }}" class="biz-tab-btn {{ $activeTab === 'branches' ? 'active' : '' }}">
            <i class="ph ph-map-pin-line"></i>
            <div class="tab-label-group">
                <span class="tab-main-text">Branches & Sites Hub</span>
                <span class="tab-sub-text">Operational Outlets & Stores</span>
            </div>
            <span class="tab-count-badge">{{ $branches->count() }}</span>
        </a>
    </div>

    <!-- TAB 1: BUSINESS & BRANCH DETAILS (General Business Profile) -->
    @if($activeTab === 'general')
        <form action="{{ route('config.business-settings.general') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="biz-tab-content">
                <!-- Branding & Visual Assets Card -->
                <div class="biz-card">
                    <div class="biz-card-header">
                        <div class="card-title-group">
                            <i class="ph ph-image"></i>
                            <div>
                                <h3 class="card-title">Brand Identity & Visual Assets</h3>
                                <p class="card-desc">Brand logos and web app icons used across invoices, reports, and top navigation.</p>
                            </div>
                        </div>
                    </div>
                    <div class="biz-card-body">
                        <div class="grid-form-2col">
                            <div class="upload-dropzone-box">
                                <label class="dropzone-label">Primary Brand Logo</label>
                                <div class="dropzone-preview-area">
                                    @if($company->company_logo)
                                        <img src="{{ asset('storage/' . $company->company_logo) }}" alt="Logo" class="preview-logo-img" id="logoPreview">
                                    @else
                                        <div class="preview-placeholder" id="logoPreviewPlaceholder">
                                            <i class="ph ph-image"></i>
                                            <span>No Logo Uploaded</span>
                                        </div>
                                    @endif
                                </div>
                                <input type="file" name="logo" id="logoInput" class="file-input-hidden" accept="image/*" onchange="previewImage(this, 'logoPreview', 'logoPreviewPlaceholder')">
                                <label for="logoInput" class="btn-upload-file">
                                    <i class="ph ph-upload-simple"></i> Choose New Logo
                                </label>
                                <span class="upload-hint">Recommended: PNG / SVG with transparent background, max 2MB</span>
                            </div>

                            <div class="upload-dropzone-box">
                                <label class="dropzone-label">System Favicon / Tab Icon</label>
                                <div class="dropzone-preview-area">
                                    @if($company->company_favicon)
                                        <img src="{{ asset('storage/' . $company->company_favicon) }}" alt="Favicon" class="preview-favicon-img" id="faviconPreview">
                                    @else
                                        <div class="preview-placeholder" id="faviconPreviewPlaceholder">
                                            <i class="ph ph-browser"></i>
                                            <span>No Favicon Uploaded</span>
                                        </div>
                                    @endif
                                </div>
                                <input type="file" name="favicon" id="faviconInput" class="file-input-hidden" accept=".ico,.png,.svg" onchange="previewImage(this, 'faviconPreview', 'faviconPreviewPlaceholder')">
                                <label for="faviconInput" class="btn-upload-file">
                                    <i class="ph ph-upload-simple"></i> Choose Favicon
                                </label>
                                <span class="upload-hint">Recommended: Square 32x32 ICO or PNG</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Basic Organization Details Card (2-Column Form Grid) -->
                <div class="biz-card">
                    <div class="biz-card-header">
                        <div class="card-title-group">
                            <i class="ph ph-buildings"></i>
                            <div>
                                <h3 class="card-title">Corporate & Trade Identification</h3>
                                <p class="card-desc">Legal name as registered with government authorities alongside operational trade names.</p>
                            </div>
                        </div>
                    </div>
                    <div class="biz-card-body">
                        <div class="grid-form-2col">
                            <div class="form-group">
                                <label class="input-label" for="compLegalName">Legal Entity Name <span class="req">*</span></label>
                                <input type="text" name="company_legal_name" id="compLegalName" class="form-input" value="{{ old('company_legal_name', $company->company_legal_name) }}" required placeholder="e.g. Bistro Hospitality Group Inc.">
                                <span class="field-hint">Registered legal name appearing on statutory and tax documents</span>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="compTradeName">Trade Name / DBA (Doing Business As)</label>
                                <input type="text" name="company_trade_name" id="compTradeName" class="form-input" value="{{ old('company_trade_name', $company->company_trade_name) }}" placeholder="e.g. Rustic Bistro">
                                <span class="field-hint">Customer-facing brand name displayed across POS and receipts</span>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="compCode">System Display Code <span class="req">*</span></label>
                                <input type="text" name="company_code" id="compCode" class="form-input uppercase" value="{{ old('company_code', $company->company_code) }}" required placeholder="e.g. BGH-01">
                                <span class="field-hint">Unique 3-10 character uppercase code for transaction numbering</span>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="compCountry">Country Code <span class="req">*</span></label>
                                <input type="text" name="company_country_code" id="compCountry" class="form-input uppercase" value="{{ old('company_country_code', $company->company_country_code ?: 'PH') }}" required maxlength="2">
                                <span class="field-hint">ISO 2-character country code (e.g. PH)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Localization & Regional Parameters (2-Column Form Grid) -->
                <div class="biz-card">
                    <div class="biz-card-header">
                        <div class="card-title-group">
                            <i class="ph ph-globe-hemisphere-east"></i>
                            <div>
                                <h3 class="card-title">Localization & Base System Settings</h3>
                                <p class="card-desc">Currency, timezone, and fiscal calendar configurations.</p>
                            </div>
                        </div>
                    </div>
                    <div class="biz-card-body">
                        <div class="grid-form-2col">
                            <div class="form-group">
                                <label class="input-label" for="compCurrency">Default Base Currency <span class="req">*</span></label>
                                <select name="company_base_currency_code" id="compCurrency" class="form-select" required>
                                    @foreach($currencies as $curr)
                                        <option value="{{ $curr }}" {{ $company->company_base_currency_code === $curr ? 'selected' : '' }}>
                                            {{ $curr }} - {{ $curr === 'PHP' ? 'Philippine Peso' : ($curr === 'USD' ? 'US Dollar' : $curr) }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="field-hint">Primary reporting currency for accounting & pricing</span>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="compTimezone">Operational Timezone <span class="req">*</span></label>
                                <select name="company_timezone" id="compTimezone" class="form-select" required>
                                    @foreach($timezones as $tz)
                                        <option value="{{ $tz }}" {{ $company->company_timezone === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                                    @endforeach
                                </select>
                                <span class="field-hint">System timezone used for POS shift clocks & timestamps</span>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="compFiscal">Fiscal Year Start Month <span class="req">*</span></label>
                                <select name="fiscal_year_start_month" id="compFiscal" class="form-select" required>
                                    @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ $company->fiscal_year_start_month == $m ? 'selected' : '' }}>
                                            Month {{ $m }} ({{ date('F', mktime(0, 0, 0, $m, 10)) }})
                                        </option>
                                    @endfor
                                </select>
                                <span class="field-hint">Opening period for annual financial balance closes</span>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="compContactPerson">Official Representative / Contact Person</label>
                                <input type="text" name="company_contact_person" id="compContactPerson" class="form-input" value="{{ old('company_contact_person', $company->company_contact_person) }}" placeholder="e.g. Dorothy Diaz (Managing Director)">
                                <span class="field-hint">Primary corporate contact for administrative inquiries</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Corporate Contact & Physical Addresses (2-Column Form Grid) -->
                <div class="biz-card">
                    <div class="biz-card-header">
                        <div class="card-title-group">
                            <i class="ph ph-address-book"></i>
                            <div>
                                <h3 class="card-title">Corporate Contact & Head Office Coordinates</h3>
                                <p class="card-desc">Official contact channels and principal place of business.</p>
                            </div>
                        </div>
                    </div>
                    <div class="biz-card-body">
                        <div class="grid-form-2col">
                            <div class="form-group">
                                <label class="input-label" for="compEmail">Corporate Email Address</label>
                                <input type="email" name="company_email_address" id="compEmail" class="form-input" value="{{ old('company_email_address', $company->company_email_address) }}" placeholder="admin@rusticrestaurant.ph">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="compPhone">Support Hotline / Telephone</label>
                                <input type="text" name="company_phone_number" id="compPhone" class="form-input" value="{{ old('company_phone_number', $company->company_phone_number) }}" placeholder="+63 2 8123 4567">
                            </div>

                            <div class="form-group col-span-2">
                                <label class="input-label" for="compAddr1">Physical Head Office Address (Line 1)</label>
                                <input type="text" name="company_address_line_1" id="compAddr1" class="form-input" value="{{ old('company_address_line_1', $company->company_address_line_1) }}" placeholder="Ground Floor, Greenbelt 5, Ayala Center">
                            </div>

                            <div class="form-group col-span-2">
                                <label class="input-label" for="compAddr2">Address Line 2 (Unit / Suite / Building)</label>
                                <input type="text" name="company_address_line_2" id="compAddr2" class="form-input" value="{{ old('company_address_line_2', $company->company_address_line_2) }}" placeholder="Suite 401, Corporate Tower">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="compCity">City / Municipality</label>
                                <input type="text" name="company_city" id="compCity" class="form-input" value="{{ old('company_city', $company->company_city) }}" placeholder="Makati City">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="compState">State / Province / Region</label>
                                <input type="text" name="company_state_province_code" id="compState" class="form-input" value="{{ old('company_state_province_code', $company->company_state_province_code) }}" placeholder="Metro Manila">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="compPostal">Postal / ZIP Code</label>
                                <input type="text" name="company_postal_code" id="compPostal" class="form-input" value="{{ old('company_postal_code', $company->company_postal_code) }}" placeholder="1228">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="compWeb">Website URL</label>
                                <input type="url" name="company_website_url" id="compWeb" class="form-input" value="{{ old('company_website_url', $company->company_website_url) }}" placeholder="https://rusticbistro.ph">
                            </div>
                        </div>
                    </div>
                    <div class="biz-card-footer">
                        <button type="submit" class="btn-primary-save">
                            <i class="ph ph-floppy-disk"></i>
                            <span>Save Business Profile</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @endif

    <!-- TAB 2: CERTIFICATE OF REGISTRATION (COR) & COMPLIANCE HUB -->
    @if($activeTab === 'legal')
        <div class="biz-tab-content">
            <!-- Tax Identification & Regulatory Status (2-Column Form Grid) -->
            <form action="{{ route('config.business-settings.legal') }}" method="POST">
                @csrf
                <div class="biz-card">
                    <div class="biz-card-header">
                        <div class="card-title-group">
                            <i class="ph ph-stamp"></i>
                            <div>
                                <h3 class="card-title">Tax Identification & Regulatory Status</h3>
                                <p class="card-desc">Official BIR / Government registrations required for legal entity tax compliance.</p>
                            </div>
                        </div>
                    </div>
                    <div class="biz-card-body">
                        <div class="grid-form-2col">
                            <div class="form-group">
                                <label class="input-label" for="taxId">Tax Identification Number (TIN) <span class="req">*</span></label>
                                <input type="text" name="company_tax_id" id="taxId" class="form-input" value="{{ old('company_tax_id', $company->company_tax_id) }}" required placeholder="123-456-789-000">
                                <span class="field-hint">Registered 9 to 12 digit TIN for official tax returns</span>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="taxType">Tax Classification / Status <span class="req">*</span></label>
                                <select name="company_tax_type" id="taxType" class="form-select" required>
                                    @foreach($taxTypes as $tt)
                                        <option value="{{ $tt }}" {{ $company->company_tax_type === $tt ? 'selected' : '' }}>{{ $tt }}</option>
                                    @endforeach
                                </select>
                                <span class="field-hint">VAT / Non-VAT / PEZA Exempt classification</span>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="rdoCode">RDO / Revenue District Office Code</label>
                                <input type="text" name="company_rdo_code" id="rdoCode" class="form-input" value="{{ old('company_rdo_code', $company->company_rdo_code) }}" placeholder="e.g. 047 (East Makati)">
                                <span class="field-hint">BIR revenue jurisdiction code</span>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="regNo">SEC / DTI Registration Number</label>
                                <input type="text" name="company_registration_no" id="regNo" class="form-input" value="{{ old('company_registration_no', $company->company_registration_no) }}" placeholder="SEC Reg No. CS202209182">
                                <span class="field-hint">Articles of Incorporation or DTI business certificate</span>
                            </div>

                            <div class="form-group col-span-2">
                                <label class="input-label" for="birPermit">BIR Permit to Use (PTU) / CAS Number</label>
                                <input type="text" name="bir_permit_number" id="birPermit" class="form-input" value="{{ old('bir_permit_number', $company->bir_permit_number) }}" placeholder="PTU-CAS-2024-001239">
                                <span class="field-hint">Computerized Accounting System authorization code</span>
                            </div>

                            <div class="form-group col-span-2">
                                <label class="input-label" for="invHeader">Official Invoice / Receipt Header Disclosures</label>
                                <textarea name="invoice_header_text" id="invHeader" rows="3" class="form-textarea" placeholder="BISTRO HOSPITALITY GROUP INC.&#10;TIN: 123-456-789-000 VAT Registered&#10;Line of Business: Restaurant Operations">{{ old('invoice_header_text', $company->invoice_header_text) }}</textarea>
                                <span class="field-hint">Mandatory text printed at the top of customer POS invoices</span>
                            </div>

                            <div class="form-group col-span-2">
                                <label class="input-label" for="invFooter">Official Invoice / Receipt Footer Disclosures</label>
                                <textarea name="invoice_footer_text" id="invFooter" rows="3" class="form-textarea" placeholder="THIS DOCUMENT IS NOT VALID FOR CLAIM OF INPUT TAX&#10;Thank you for dining with us! Come again soon.">{{ old('invoice_footer_text', $company->invoice_footer_text) }}</textarea>
                                <span class="field-hint">Mandatory disclaimer and BIR authorization printed at bottom</span>
                            </div>
                        </div>
                    </div>
                    <div class="biz-card-footer">
                        <button type="submit" class="btn-primary-save">
                            <i class="ph ph-floppy-disk"></i>
                            <span>Save Regulatory & Print Defaults</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Attached Certificate Vault & Expiration Radar -->
            <div class="biz-card">
                <div class="biz-card-header">
                    <div class="card-title-group">
                        <i class="ph ph-vault"></i>
                        <div>
                            <h3 class="card-title">Certificate & Legal Document Vault</h3>
                            <p class="card-desc">Attached certificates, business permits, and compliance filings with real-time expiration monitoring.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-primary-action" onclick="openUploadDocModal()">
                        <i class="ph ph-upload-simple"></i>
                        <span>Upload Legal Document</span>
                    </button>
                </div>
                <div class="biz-card-body">
                    @if($legalDocuments->isEmpty())
                        <div class="empty-vault-state">
                            <div class="empty-icon-circle">
                                <i class="ph ph-file-dashed"></i>
                            </div>
                            <h4>No Legal Documents Uploaded Yet</h4>
                            <p>Upload your Certificate of Registration (BIR Form 2303), Mayor's Permit, and Sanitary Clearances to enable expiration radar monitoring.</p>
                            <button type="button" class="btn-primary-action mt-3" onclick="openUploadDocModal()">
                                <i class="ph ph-plus"></i> Upload First Document
                            </button>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="biz-table">
                                <thead>
                                    <tr>
                                        <th>Document Category</th>
                                        <th>Document Title & Number</th>
                                        <th>Issuing Authority</th>
                                        <th>Issue Date</th>
                                        <th>Expiration Date</th>
                                        <th>Compliance Radar</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($legalDocuments as $doc)
                                        <tr>
                                            <td>
                                                <div class="doc-category-cell">
                                                    <i class="ph ph-file-text doc-type-icon"></i>
                                                    <span class="category-name">{{ $doc->document_category }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="doc-name-cell">
                                                    <span class="doc-name-text">{{ $doc->document_name }}</span>
                                                    @if($doc->document_number)
                                                        <span class="doc-num-tag">#{{ $doc->document_number }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>{{ $doc->issuing_authority ?: '—' }}</td>
                                            <td>{{ $doc->issue_date ? $doc->issue_date->format('M d, Y') : '—' }}</td>
                                            <td>
                                                @if($doc->expiration_date)
                                                    <strong>{{ $doc->expiration_date->format('M d, Y') }}</strong>
                                                    @if($doc->days_until_expiration !== null)
                                                        <span class="expiry-relative-text">
                                                            @if($doc->days_until_expiration < 0)
                                                                ({{ abs($doc->days_until_expiration) }} days ago)
                                                            @else
                                                                (in {{ $doc->days_until_expiration }} days)
                                                            @endif
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">Permanent / No Expiry</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="status-pill {{ $doc->status_badge_class }}">
                                                    @if($doc->expiration_status === 'Active')
                                                        <i class="ph ph-check-circle"></i> Active
                                                    @elseif($doc->expiration_status === 'Expiring Soon')
                                                        <i class="ph ph-warning-circle"></i> Expiring Soon
                                                    @elseif($doc->expiration_status === 'Expired')
                                                        <i class="ph ph-x-circle"></i> Expired
                                                    @else
                                                        {{ $doc->expiration_status }}
                                                    @endif
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <div class="table-actions-inline">
                                                    <a href="{{ $doc->file_url }}" target="_blank" class="btn-icon-action" title="View Document File">
                                                        <i class="ph ph-eye"></i>
                                                    </a>
                                                    <form action="{{ route('config.business-settings.documents.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Delete document {{ $doc->document_name }}?');" style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-icon-action danger" title="Delete Document">
                                                            <i class="ph ph-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Upload Document Modal -->
        <div class="modal-backdrop-custom" id="uploadDocModal" style="display: none;">
            <div class="modal-card-custom modal-lg">
                <div class="modal-card-header">
                    <div class="modal-title-row">
                        <i class="ph ph-vault modal-icon"></i>
                        <div>
                            <h3 class="modal-title">Upload Legal Compliance Document</h3>
                            <p class="modal-subtitle">Securely attach registration documents with automatic expiration radar tracking.</p>
                        </div>
                    </div>
                    <button type="button" class="modal-close-btn" onclick="closeUploadDocModal()">&times;</button>
                </div>
                <form action="{{ route('config.business-settings.documents.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-card-body">
                        <div class="grid-form-2col">
                            <div class="form-group">
                                <label class="input-label" for="docCategorySelect">Document Category <span class="req">*</span></label>
                                <select name="document_category" id="docCategorySelect" class="form-select" required>
                                    @foreach($docCategories as $cat)
                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="docNameInput">Document Title / Description <span class="req">*</span></label>
                                <input type="text" name="document_name" id="docNameInput" class="form-input" required placeholder="e.g. 2026 Makati Business Permit Renewal">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="docNumInput">Permit / License / Certificate Number</label>
                                <input type="text" name="document_number" id="docNumInput" class="form-input" placeholder="e.g. BP-2026-88129">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="docAuthInput">Issuing Government Authority</label>
                                <input type="text" name="issuing_authority" id="docAuthInput" class="form-input" placeholder="e.g. City Government of Makati / BFP">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="docIssueDate">Issue Date</label>
                                <input type="date" name="issue_date" id="docIssueDate" class="form-input">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="docExpiryDate">Expiration / Renewal Due Date</label>
                                <input type="date" name="expiration_date" id="docExpiryDate" class="form-input">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="docReminderDays">Alert Days Before Expiry</label>
                                <input type="number" name="renewal_reminder_days" id="docReminderDays" class="form-input" value="30" min="1" max="180">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="docFileInput">Attachment File (PDF, PNG, JPG) <span class="req">*</span></label>
                                <input type="file" name="document_file" id="docFileInput" class="form-input" required accept=".pdf,image/*">
                            </div>

                            <div class="form-group col-span-2">
                                <label class="input-label" for="docNotes">Compliance Notes / Remarks</label>
                                <textarea name="notes" id="docNotes" rows="2" class="form-textarea" placeholder="Additional conditions, requirements or annotations..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-card-footer">
                        <button type="button" class="btn-cancel-custom" onclick="closeUploadDocModal()">Cancel</button>
                        <button type="submit" class="btn-primary-save">
                            <i class="ph ph-upload"></i> Upload & Encrypt
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- TAB 3: ORGANIZATIONAL HIERARCHY & RELATIONSHIPS -->
    @if($activeTab === 'hierarchy')
        <form action="{{ route('config.business-settings.hierarchy') }}" method="POST">
            @csrf
            <div class="biz-tab-content">
                <!-- Entity Hierarchy Selector (2-Column Form Grid) -->
                <div class="biz-card">
                    <div class="biz-card-header">
                        <div class="card-title-group">
                            <i class="ph ph-tree-structure"></i>
                            <div>
                                <h3 class="card-title">Entity Level & Consolidation Hierarchy</h3>
                                <p class="card-desc">Define whether this entity is a parent holding group, distinct legal company, or operational subsidiary.</p>
                            </div>
                        </div>
                    </div>
                    <div class="biz-card-body">
                        <div class="grid-form-2col">
                            <div class="form-group">
                                <label class="input-label" for="compTypeSelect">Entity Architecture Role <span class="req">*</span></label>
                                <select name="company_type" id="compTypeSelect" class="form-select" required>
                                    <option value="Company" {{ $company->company_type === 'Company' ? 'selected' : '' }}>
                                        Legal Company (Level 2: Distinct legal books, TIN, charts of accounts)
                                    </option>
                                    <option value="Holding Enterprise" {{ $company->company_type === 'Holding Enterprise' ? 'selected' : '' }}>
                                        Holding Enterprise / Group (Level 1: Root consolidation entity)
                                    </option>
                                    <option value="Subsidiary" {{ $company->company_type === 'Subsidiary' ? 'selected' : '' }}>
                                        Operating Subsidiary (Level 2: Child legal entity)
                                    </option>
                                    <option value="Agency" {{ $company->company_type === 'Agency' ? 'selected' : '' }}>
                                        Manpower / Third-Party Agency Partner
                                    </option>
                                </select>
                                <span class="field-hint">Defines legal books vs. operational entity classification</span>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="parentCompSelect">Parent Holding Enterprise Association</label>
                                <select name="parent_company_id" id="parentCompSelect" class="form-select">
                                    <option value="">— None (Top-Level Holding Root) —</option>
                                    @foreach($allCompanies as $c)
                                        @if($c->company_id !== $company->company_id)
                                            <option value="{{ $c->company_id }}" {{ $company->parent_company_id == $c->company_id ? 'selected' : '' }}>
                                                {{ $c->company_trade_name ?: $c->company_legal_name }} ({{ $c->company_code }})
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                <span class="field-hint">Link this entity to a parent holding group for enterprise rollups</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shared vs Independent Subsystems Toggles -->
                <div class="biz-card">
                    <div class="biz-card-header">
                        <div class="card-title-group">
                            <i class="ph ph-share-network"></i>
                            <div>
                                <h3 class="card-title">Shared vs. Independent Rules Matrix</h3>
                                <p class="card-desc">Control cross-entity inheritance for financials, vendor directories, and product catalogs.</p>
                            </div>
                        </div>
                    </div>
                    <div class="biz-card-body">
                        <div class="rules-toggle-list">
                            <!-- Toggle 1: Chart of Accounts -->
                            <div class="toggle-row-item">
                                <div class="toggle-info">
                                    <h4 class="toggle-title"><i class="ph ph-receipt"></i> Chart of Accounts (COA) Inheritance</h4>
                                    <p class="toggle-desc">When enabled, child branches and subsidiaries inherit the master chart of accounts from the parent legal company.</p>
                                </div>
                                <label class="switch-toggle">
                                    <input type="checkbox" name="share_chart_of_accounts" value="1" {{ $company->share_chart_of_accounts ? 'checked' : '' }}>
                                    <span class="slider round"></span>
                                </label>
                            </div>

                            <!-- Toggle 2: Customer / Vendor Directory -->
                            <div class="toggle-row-item">
                                <div class="toggle-info">
                                    <h4 class="toggle-title"><i class="ph ph-users-three"></i> Customer & Vendor Directory Sharing</h4>
                                    <p class="toggle-desc">Share vendors, suppliers, and customer master records globally across all branches and related companies.</p>
                                </div>
                                <label class="switch-toggle">
                                    <input type="checkbox" name="share_customer_vendor_directory" value="1" {{ $company->share_customer_vendor_directory ? 'checked' : '' }}>
                                    <span class="slider round"></span>
                                </label>
                            </div>

                            <!-- Toggle 3: Product Catalog & Price Lists -->
                            <div class="toggle-row-item">
                                <div class="toggle-info">
                                    <h4 class="toggle-title"><i class="ph ph-fork-knife"></i> Universal Menu Catalog & Recipe BOMs</h4>
                                    <p class="toggle-desc">Enable universal item master catalogs across all stores while allowing branch-specific local stock and selling price overrides.</p>
                                </div>
                                <label class="switch-toggle">
                                    <input type="checkbox" name="share_product_catalog" value="1" {{ $company->share_product_catalog ? 'checked' : '' }}>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="biz-card-footer">
                        <button type="submit" class="btn-primary-save">
                            <i class="ph ph-floppy-disk"></i>
                            <span>Save Hierarchy Rules</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @endif

    <!-- TAB 4: BRANCHES & SITES HUB -->
    @if($activeTab === 'branches')
        <div class="biz-tab-content">
            <div class="biz-card">
                <div class="biz-card-header">
                    <div class="card-title-group">
                        <i class="ph ph-storefront"></i>
                        <div>
                            <h3 class="card-title">Operational Branches & Sites Hub ({{ $branches->count() }})</h3>
                            <p class="card-desc">Retail outlets, dining rooms, cloud kitchens, and central commissary warehouses linked to {{ $company->company_trade_name ?: $company->company_legal_name }}.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-primary-action" onclick="openAddBranchModal()">
                        <i class="ph ph-plus-circle"></i>
                        <span>Add New Branch</span>
                    </button>
                </div>
                <div class="biz-card-body">
                    <div class="table-responsive">
                        <table class="biz-table">
                            <thead>
                                <tr>
                                    <th>Branch Name & Code</th>
                                    <th>Sub-TIN / Branch Code</th>
                                    <th>Operating Site Type</th>
                                    <th>Operating Hours</th>
                                    <th>Location / City</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($branches as $br)
                                    <tr>
                                        <td>
                                            <div class="branch-cell-title">
                                                <span class="br-name">{{ $br->branch_name }}</span>
                                                <span class="br-code-tag">{{ $br->branch_code }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <strong>{{ $br->branch_tax_code ?: '00000' }}</strong>
                                        </td>
                                        <td>
                                            <span class="branch-type-pill {{ strtolower(str_replace(' ', '-', $br->branch_type)) }}">
                                                {{ $br->branch_type }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="hours-text"><i class="ph ph-clock"></i> {{ $br->operating_hours ?: '10:00 AM - 10:00 PM' }}</span>
                                        </td>
                                        <td>{{ $br->branch_city ?: $br->branch_address_line_1 ?: 'Metro Manila' }}</td>
                                        <td>
                                            <div class="contact-mini-col">
                                                <span>{{ $br->branch_phone_number ?: '—' }}</span>
                                                <small class="text-muted">{{ $br->branch_contact_person ?: '' }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-pill {{ $br->branch_is_active ? 'status-active' : 'status-danger' }}">
                                                {{ $br->branch_is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <div class="table-actions-inline">
                                                <button type="button" class="btn-icon-action" title="Edit Branch Details" onclick="openEditBranchModal({{ json_encode($br) }})">
                                                    <i class="ph ph-pencil-simple"></i>
                                                </button>
                                                <form action="{{ route('config.business-settings.branches.toggle', $br->branch_id) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="btn-icon-action {{ $br->branch_is_active ? 'warning' : 'success' }}" title="{{ $br->branch_is_active ? 'Deactivate Branch' : 'Activate Branch' }}">
                                                        <i class="ph {{ $br->branch_is_active ? 'ph-power' : 'ph-play' }}"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            No branches registered under this company yet. Click "Add New Branch" above to create one.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Branch Modal -->
        <div class="modal-backdrop-custom" id="addBranchModal" style="display: none;">
            <div class="modal-card-custom modal-lg">
                <div class="modal-card-header">
                    <div class="modal-title-row">
                        <i class="ph ph-storefront modal-icon"></i>
                        <div>
                            <h3 class="modal-title">Register Operational Branch / Site</h3>
                            <p class="modal-subtitle">Add a retail store, dining outlet, or commissary to {{ $company->company_trade_name ?: $company->company_legal_name }}.</p>
                        </div>
                    </div>
                    <button type="button" class="modal-close-btn" onclick="closeAddBranchModal()">&times;</button>
                </div>
                <form action="{{ route('config.business-settings.branches.store') }}" method="POST">
                    @csrf
                    <div class="modal-card-body">
                        <div class="grid-form-2col">
                            <div class="form-group">
                                <label class="input-label" for="newBrName">Branch Name <span class="req">*</span></label>
                                <input type="text" name="branch_name" id="newBrName" class="form-input" required placeholder="e.g. Alabang Town Center Branch">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="newBrCode">Branch Code <span class="req">*</span></label>
                                <input type="text" name="branch_code" id="newBrCode" class="form-input uppercase" required placeholder="e.g. ATC-04">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="newBrType">Site Type <span class="req">*</span></label>
                                <select name="branch_type" id="newBrType" class="form-select" required>
                                    @foreach($branchTypes as $bt)
                                        <option value="{{ $bt }}">{{ $bt }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="newBrTaxCode">Branch Sub-TIN / Tax Code</label>
                                <input type="text" name="branch_tax_code" id="newBrTaxCode" class="form-input" placeholder="e.g. 00004">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="newBrHours">Operating Hours</label>
                                <input type="text" name="operating_hours" id="newBrHours" class="form-input" value="10:00 AM - 10:00 PM">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="newBrContact">Branch Manager / Contact Person</label>
                                <input type="text" name="branch_contact_person" id="newBrContact" class="form-input" placeholder="e.g. James Perez">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="newBrPhone">Contact Phone Number</label>
                                <input type="text" name="branch_phone_number" id="newBrPhone" class="form-input" placeholder="+63 2 8999 0000">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="newBrCity">City / Municipality</label>
                                <input type="text" name="branch_city" id="newBrCity" class="form-input" placeholder="Muntinlupa City">
                            </div>

                            <div class="form-group col-span-2">
                                <label class="input-label" for="newBrAddress">Physical Street Address</label>
                                <input type="text" name="branch_address_line_1" id="newBrAddress" class="form-input" placeholder="Commercial Center, Alabang Zapote Rd">
                            </div>
                        </div>
                    </div>
                    <div class="modal-card-footer">
                        <button type="button" class="btn-cancel-custom" onclick="closeAddBranchModal()">Cancel</button>
                        <button type="submit" class="btn-primary-save">
                            <i class="ph ph-plus-circle"></i> Create Branch
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Branch Modal -->
        <div class="modal-backdrop-custom" id="editBranchModal" style="display: none;">
            <div class="modal-card-custom modal-lg">
                <div class="modal-card-header">
                    <div class="modal-title-row">
                        <i class="ph ph-pencil-simple modal-icon"></i>
                        <div>
                            <h3 class="modal-title">Edit Branch Details</h3>
                            <p class="modal-subtitle">Update parameters for this operational unit.</p>
                        </div>
                    </div>
                    <button type="button" class="modal-close-btn" onclick="closeEditBranchModal()">&times;</button>
                </div>
                <form id="editBranchForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-card-body">
                        <div class="grid-form-2col">
                            <div class="form-group">
                                <label class="input-label" for="editBrName">Branch Name <span class="req">*</span></label>
                                <input type="text" name="branch_name" id="editBrName" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="editBrCode">Branch Code <span class="req">*</span></label>
                                <input type="text" name="branch_code" id="editBrCode" class="form-input uppercase" required>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="editBrType">Site Type <span class="req">*</span></label>
                                <select name="branch_type" id="editBrType" class="form-select" required>
                                    @foreach($branchTypes as $bt)
                                        <option value="{{ $bt }}">{{ $bt }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="editBrTaxCode">Branch Sub-TIN / Tax Code</label>
                                <input type="text" name="branch_tax_code" id="editBrTaxCode" class="form-input">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="editBrHours">Operating Hours</label>
                                <input type="text" name="operating_hours" id="editBrHours" class="form-input">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="editBrContact">Branch Manager / Contact Person</label>
                                <input type="text" name="branch_contact_person" id="editBrContact" class="form-input">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="editBrPhone">Contact Phone Number</label>
                                <input type="text" name="branch_phone_number" id="editBrPhone" class="form-input">
                            </div>

                            <div class="form-group">
                                <label class="input-label" for="editBrCity">City / Municipality</label>
                                <input type="text" name="branch_city" id="editBrCity" class="form-input">
                            </div>

                            <div class="form-group col-span-2">
                                <label class="input-label" for="editBrAddress">Physical Street Address</label>
                                <input type="text" name="branch_address_line_1" id="editBrAddress" class="form-input">
                            </div>
                        </div>
                    </div>
                    <div class="modal-card-footer">
                        <button type="button" class="btn-cancel-custom" onclick="closeEditBranchModal()">Cancel</button>
                        <button type="submit" class="btn-primary-save">
                            <i class="ph ph-check"></i> Update Branch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
