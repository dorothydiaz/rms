@extends('layouts.app')

@section('title', 'Business Settings - Company & Branch Management')

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

<style>
/* -------------------------------------------------------------------------
   HR CORPORATE THEME - BUSINESS SETTINGS
   Palette: #F8FAFC canvas, #F1F5F9 surfaces, #4F46E5 / #0D9488 accents, #0F172A text
   Strict Layout Uniformity: max-w-7xl, p-6, Zero Layout Shift
   ------------------------------------------------------------------------- */
.biz-settings-wrapper {
    width: 100%;
    max-width: 80rem; /* max-w-7xl (1280px) */
    margin-left: auto;
    margin-right: auto;
    padding: 1.5rem; /* p-6 (24px) */
    box-sizing: border-box;
}

/* Page Header Summary Strip */
.biz-header-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.05);
    margin-bottom: 24px;
    min-height: 110px;
    box-sizing: border-box;
}

.biz-header-main {
    display: flex;
    align-items: center;
    gap: 20px;
}

.biz-avatar-logo {
    width: 68px;
    height: 68px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}

.biz-logo-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.biz-logo-placeholder {
    font-size: 32px;
    color: #4f46e5;
}

.biz-title-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 4px;
}

.biz-company-title {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.02em;
}

.biz-badge-code {
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.biz-badge-type {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 6px;
    background: #eef2ff;
    color: #4f46e5;
    border: 1px solid #e0e7ff;
}

.biz-badge-status {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.biz-badge-status.active {
    background: #ecfdf5;
    color: #059669;
}

.biz-badge-status.inactive {
    background: #fef2f2;
    color: #dc2626;
}

.biz-legal-name {
    font-size: 13px;
    color: #475569;
    margin: 0 0 6px 0;
    display: flex;
    align-items: center;
    gap: 5px;
}

.biz-meta-chips {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    font-size: 12px;
    color: #64748b;
}

.biz-meta-chips span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.stat-pill-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 18px;
    text-align: right;
}

.stat-label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 6px;
}

.stat-radar-badges {
    display: flex;
    align-items: center;
    gap: 8px;
}

.radar-chip {
    font-size: 11.5px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.radar-chip.active { background: #dcfce7; color: #16a34a; }
.radar-chip.warning { background: #fef3c7; color: #d97706; }
.radar-chip.danger { background: #fee2e2; color: #dc2626; }

/* -------------------------------------------------------------------------
   NAVIGATION TABS (RIGID STRUCTURAL HEIGHT - ZERO LAYOUT SHIFT)
   ------------------------------------------------------------------------- */
.biz-tabs-bar {
    display: flex;
    align-items: stretch;
    gap: 8px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 6px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.05);
    height: 60px;
    box-sizing: border-box;
}

.biz-tab-btn {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 16px;
    border-radius: 8px;
    text-decoration: none;
    color: #475569;
    transition: all 0.15s ease;
    border: 1px solid transparent;
    height: 100%;
    box-sizing: border-box;
}

.biz-tab-btn i {
    font-size: 20px;
    color: #64748b;
    transition: color 0.15s ease;
    flex-shrink: 0;
}

.tab-label-group {
    display: flex;
    flex-direction: column;
    text-align: left;
    overflow: hidden;
}

.tab-main-text {
    font-size: 12.5px;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tab-sub-text {
    font-size: 10.5px;
    color: #64748b;
    white-space: nowrap;
}

.biz-tab-btn:hover {
    background: #f8fafc;
    color: #0f172a;
}

.biz-tab-btn.active {
    background: #eef2ff;
    border-color: #c7d2fe;
    color: #4f46e5;
}

.biz-tab-btn.active i {
    color: #4f46e5;
}

.biz-tab-btn.active .tab-main-text {
    color: #4f46e5;
}

.tab-alert-badge {
    background: #ef4444;
    color: #ffffff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 9999px;
    margin-left: auto;
}

.tab-count-badge {
    background: #f1f5f9;
    color: #475569;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 9999px;
    margin-left: auto;
}

/* -------------------------------------------------------------------------
   STANDARDIZED CARD SURFACES & 2-COLUMN FORM GRID
   ------------------------------------------------------------------------- */
.biz-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.05);
    padding: 24px;
    margin-bottom: 24px;
    box-sizing: border-box;
}

.biz-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
}

.card-title-group {
    display: flex;
    align-items: center;
    gap: 12px;
}

.card-title-group i {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: #eef2ff;
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.card-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 2px 0;
}

.card-desc {
    font-size: 12px;
    color: #64748b;
    margin: 0;
}

/* Universal 2-Column Form Grid (grid-cols-1 md:grid-cols-2 gap-6) */
.grid-form-2col {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px 24px;
}

.col-span-2 {
    grid-column: span 2 / span 2;
}

@media (max-width: 768px) {
    .grid-form-2col {
        grid-template-columns: 1fr;
    }
    .col-span-2 {
        grid-column: span 1 / span 1;
    }
}

/* Input Fields & Micro-interactions */
.form-group {
    display: flex;
    flex-direction: column;
}

.input-label {
    font-size: 12.5px;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 6px;
}

.req {
    color: #ef4444;
}

.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: 9px 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 13px;
    color: #0f172a;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
    font-family: inherit;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

.form-input.uppercase {
    text-transform: uppercase;
}

.field-hint {
    font-size: 11px;
    color: #64748b;
    margin-top: 4px;
}

/* Upload Dropzones */
.upload-dropzone-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    border: 1.5px dashed #cbd5e1;
    border-radius: 10px;
    padding: 20px;
    background: #f8fafc;
    text-align: center;
}

.dropzone-label {
    font-size: 12.5px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 12px;
}

.dropzone-preview-area {
    width: 100px;
    height: 100px;
    border-radius: 8px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    overflow: hidden;
}

.preview-logo-img,
.preview-favicon-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.preview-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    color: #94a3b8;
    font-size: 11px;
}

.preview-placeholder i {
    font-size: 24px;
}

.file-input-hidden {
    display: none;
}

.btn-upload-file {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-upload-file:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
    color: #0f172a;
}

.upload-hint {
    font-size: 10.5px;
    color: #64748b;
    margin-top: 8px;
}

/* Buttons */
.btn-primary-save {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #4f46e5;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s ease;
    box-shadow: 0 1px 2px rgba(79, 70, 229, 0.2);
}

.btn-primary-save:hover {
    background: #4338ca;
}

.btn-primary-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: #4f46e5;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s ease;
}

.btn-primary-action:hover {
    background: #4338ca;
}

.biz-card-footer {
    display: flex;
    justify-content: flex-end;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid #f1f5f9;
}

/* Rules Toggles */
.rules-toggle-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.toggle-row-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}

.toggle-info {
    max-width: 80%;
}

.toggle-title {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 4px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}

.toggle-desc {
    font-size: 12px;
    color: #64748b;
    margin: 0;
}

/* Switch Toggle */
.switch-toggle {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}

.switch-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #cbd5e1;
    transition: .2s;
    border-radius: 24px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .2s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #4f46e5;
}

input:checked + .slider:before {
    transform: translateX(20px);
}

/* Tables */
.table-responsive {
    overflow-x: auto;
}

.biz-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
}

.biz-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    padding: 10px 14px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    white-space: nowrap;
}

.biz-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    color: #0f172a;
    vertical-align: middle;
}

.biz-table tr:hover td {
    background: #f8fafc;
}

.branch-cell-title {
    display: flex;
    flex-direction: column;
}

.br-name {
    font-weight: 700;
    color: #0f172a;
}

.br-code-tag {
    font-size: 10px;
    color: #64748b;
}

.branch-type-pill {
    font-size: 10px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 4px;
    background: #ccfbf1;
    color: #0f766e;
    display: inline-block;
}

.status-pill {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

.status-pill.status-active,
.status-pill.radar-active {
    background: #dcfce7;
    color: #15803d;
}

.status-pill.status-danger,
.status-pill.radar-expired {
    background: #fee2e2;
    color: #b91c1c;
}

.status-pill.radar-warning {
    background: #fef3c7;
    color: #b45309;
}

.table-actions-inline {
    display: flex;
    align-items: center;
    gap: 6px;
    justify-content: flex-end;
}

.btn-icon-action {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none;
    font-size: 13px;
}

.btn-icon-action:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.btn-icon-action.danger:hover {
    background: #fee2e2;
    color: #b91c1c;
    border-color: #fca5a5;
}

.btn-icon-action.warning:hover {
    background: #fef3c7;
    color: #b45309;
}

.btn-icon-action.success:hover {
    background: #dcfce7;
    color: #15803d;
}

.empty-vault-state {
    text-align: center;
    padding: 40px 20px;
    color: #64748b;
}

.empty-icon-circle {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #eef2ff;
    color: #4f46e5;
    font-size: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px auto;
}

.empty-vault-state h4 {
    color: #0f172a;
    margin: 0 0 6px 0;
}

.empty-vault-state p {
    font-size: 12px;
    max-width: 460px;
    margin: 0 auto;
}

/* Modals */
.modal-backdrop-custom {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-card-custom {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.1), 0 8px 10px -6px rgba(15, 23, 42, 0.05);
    border: 1px solid #e2e8f0;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-lg {
    width: 660px;
    max-width: 95vw;
}

.modal-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.modal-title-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.modal-icon {
    font-size: 24px;
    color: #4f46e5;
}

.modal-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.modal-subtitle {
    font-size: 11.5px;
    color: #64748b;
    margin: 2px 0 0 0;
}

.modal-close-btn {
    background: transparent;
    border: none;
    font-size: 20px;
    color: #94a3b8;
    cursor: pointer;
}

.modal-card-body {
    padding: 24px;
}

.modal-card-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 24px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

.btn-cancel-custom {
    padding: 8px 16px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
}

.btn-save-custom {
    padding: 8px 16px;
    background: #4f46e5;
    border: none;
    border-radius: 6px;
    font-size: 12.5px;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
}
</style>

<script>
function previewImage(input, previewId, placeholderId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let preview = document.getElementById(previewId);
            const placeholder = document.getElementById(placeholderId);
            if (!preview) {
                preview = document.createElement('img');
                preview.id = previewId;
                preview.className = previewId === 'logoPreview' ? 'preview-logo-img' : 'preview-favicon-img';
                input.closest('.upload-dropzone-box').querySelector('.dropzone-preview-area').appendChild(preview);
            }
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function openUploadDocModal() {
    const modal = document.getElementById('uploadDocModal');
    if (modal) modal.style.display = 'flex';
}

function closeUploadDocModal() {
    const modal = document.getElementById('uploadDocModal');
    if (modal) modal.style.display = 'none';
}

function openAddBranchModal() {
    const modal = document.getElementById('addBranchModal');
    if (modal) modal.style.display = 'flex';
}

function closeAddBranchModal() {
    const modal = document.getElementById('addBranchModal');
    if (modal) modal.style.display = 'none';
}

function openEditBranchModal(branch) {
    const modal = document.getElementById('editBranchModal');
    const form = document.getElementById('editBranchForm');
    if (!modal || !form) return;

    form.action = `/config/business-settings/branches/${branch.branch_id}`;
    document.getElementById('editBrName').value = branch.branch_name || '';
    document.getElementById('editBrCode').value = branch.branch_code || '';
    document.getElementById('editBrType').value = branch.branch_type || 'Store / Dine-In';
    document.getElementById('editBrTaxCode').value = branch.branch_tax_code || '';
    document.getElementById('editBrHours').value = branch.operating_hours || '';
    document.getElementById('editBrContact').value = branch.branch_contact_person || '';
    document.getElementById('editBrPhone').value = branch.branch_phone_number || '';
    document.getElementById('editBrAddress').value = branch.branch_address_line_1 || '';
    document.getElementById('editBrCity').value = branch.branch_city || '';

    modal.style.display = 'flex';
}

function closeEditBranchModal() {
    const modal = document.getElementById('editBranchModal');
    if (modal) modal.style.display = 'none';
}
</script>
@endsection
