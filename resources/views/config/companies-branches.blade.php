@extends('layouts.app')

@section('title', 'Companies & Branches - Enterprise Configuration Hub')

@push('blade-data')
<script>
    window.COMPANIES_DATA = @json($companies);
    window.BRANCHES_DATA = @json($allBranches);
    window.SELECTED_COMPANY_ID = {{ $selectedCompany ? $selectedCompany->company_id : 'null' }};
    window.CompaniesBranchesConfig = Object.freeze({
        indexUrl: @json(route('config.companies-branches.index'))
    });
</script>
@endpush

@push('styles')
    @vite(['resources/css/modules/companies-branches.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/companies-branches.js'])
@endpush

@section('content')
@php
    $activeSubTab = $activeSubTab ?? request('tab', 'general');
    if ($activeSubTab === 'legal') {
        $activeSubTab = 'vault';
    }
    $fiscalMonths = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
@endphp

<div class="cb-hub-wrapper biz-settings-wrapper" id="companiesBranchesApp">

    <!-- Standard ERP Page Title & Tab Strip (Matches HR & System Administration Tabs) -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row">
            <div>
                <h1 class="hr-parent-title">
                    <i class="ph ph-buildings"></i>
                    <span>Companies &amp; Branches</span>
                </h1>
                <p class="hr-parent-subtitle">Manage registered corporate entities, staffing agencies, branch networks, and legal compliance</p>
            </div>
            <div class="hr-page-actions">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="openCreateBranchModal({{ $selectedCompany ? $selectedCompany->company_id : 'null' }})">
                    <i class="ph ph-storefront"></i>
                    <span>Add Branch</span>
                </button>
                <button type="button" class="hr-btn hr-btn-primary" onclick="openCreateCompanyModal()">
                    <i class="ph ph-plus-circle"></i>
                    <span>New Company / Agency</span>
                </button>
            </div>
        </div>

        <!-- Tab Navigation Strip -->
        <div class="hr-tabs-wrapper">
            <button type="button" class="hr-tab-item view-mode-pill {{ $currentView === 'directory' ? 'active' : '' }}" onclick="switchViewMode('directory')">
                <i class="ph ph-buildings"></i>
                <span>Companies Directory</span>
            </button>
            <button type="button" class="hr-tab-item view-mode-pill {{ $currentView === 'details' ? 'active' : '' }}" onclick="switchViewMode('details')">
                <i class="ph ph-identification-card"></i>
                <span>Company Details {{ $selectedCompany ? '(' . ($selectedCompany->company_trade_name ?: $selectedCompany->company_code) . ')' : '' }}</span>
            </button>
        </div>
    </div>

    <!-- ====================================================================
         VIEW 1: COMPANIES, AGENCIES & BRANCHES MODULAR DIRECTORY
         ==================================================================== -->
    <div id="sectionViewDirectory" class="cb-view-pane" style="display: {{ $currentView === 'directory' ? 'flex' : 'none' }};">

        <!-- 5 Summary Metric Cards (Matches HR Stat Cards) -->
        <div class="hr-company-summary-grid cb-kpi-grid">
            <div class="hr-stat-card hr-stat-card-purple">
                <div class="hr-stat-icon-wrap">
                    <i class="ph ph-buildings"></i>
                </div>
                <div class="hr-stat-content">
                    <span class="hr-stat-label">Total Companies</span>
                    <div class="hr-stat-value">{{ $metrics['total_companies'] }}</div>
                    <span class="hr-stat-sub">{{ $metrics['active_companies'] }} Active Entities</span>
                </div>
            </div>

            <div class="hr-stat-card hr-stat-card-green">
                <div class="hr-stat-icon-wrap">
                    <i class="ph ph-storefront"></i>
                </div>
                <div class="hr-stat-content">
                    <span class="hr-stat-label">Connected Branches</span>
                    <div class="hr-stat-value">{{ $metrics['total_branches'] }}</div>
                    <span class="hr-stat-sub">{{ $metrics['active_branches'] }} Operational Sites</span>
                </div>
            </div>

            <div class="hr-stat-card hr-stat-card-pink">
                <div class="hr-stat-icon-wrap">
                    <i class="ph ph-handshake"></i>
                </div>
                <div class="hr-stat-content">
                    <span class="hr-stat-label">Staffing Agencies</span>
                    <div class="hr-stat-value">{{ $metrics['agencies_count'] ?? 0 }}</div>
                    <span class="hr-stat-sub">{{ $metrics['assigned_employees'] ?? 0 }} Deployed Staff</span>
                </div>
            </div>

            <div class="hr-stat-card hr-stat-card-blue">
                <div class="hr-stat-icon-wrap">
                    <i class="ph ph-receipt"></i>
                </div>
                <div class="hr-stat-content">
                    <span class="hr-stat-label">VAT Registered</span>
                    <div class="hr-stat-value">{{ $metrics['vat_registered'] }}</div>
                    <span class="hr-stat-sub">BIR Compliant Entities</span>
                </div>
            </div>

            <div class="hr-stat-card hr-stat-card-amber">
                <div class="hr-stat-icon-wrap">
                    <i class="ph ph-tree-structure"></i>
                </div>
                <div class="hr-stat-content">
                    <span class="hr-stat-label">Enterprise Groups</span>
                    <div class="hr-stat-value">{{ $metrics['holding_groups'] }}</div>
                    <span class="hr-stat-sub">Parent / Holding Networks</span>
                </div>
            </div>
        </div>

        <!-- Real-Time Filter & Search Bar (Matches HR Filter Bar) -->
        <div class="hr-filter-bar modular-toolbar" style="position: relative;">
            <!-- Left: Live Search & Entity Type Filter Pills -->
            <div style="display: flex; align-items: center; gap: 8px; flex: 1; min-width: 280px; flex-wrap: wrap;">
                <div style="position: relative; width: 280px; max-width: 100%; flex-shrink: 0;">
                    <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px; pointer-events: none;"></i>
                    <input type="text" id="companiesDirectorySearch" oninput="handleCompaniesDirectorySearch(this.value)" placeholder="Search code, legal name, branch, TIN, city..." class="hr-input" style="width: 100%; box-sizing: border-box; padding-left: 32px; height: 31px; font-size: 12px;">
                </div>

                <!-- Quick Classification Filter Pills -->
                <div class="cb-type-filter-pills" role="tablist" aria-label="Filter by Entity Classification">
                    <button type="button" class="cb-type-pill active" data-type="all" onclick="filterCompaniesByType('all')">All ({{ $metrics['total_companies'] }})</button>
                    <button type="button" class="cb-type-pill" data-type="Company" onclick="filterCompaniesByType('Company')">Companies</button>
                    <button type="button" class="cb-type-pill" data-type="Group" onclick="filterCompaniesByType('Group')">Holding / Sub</button>
                    <button type="button" class="cb-type-pill" data-type="Agency" onclick="filterCompaniesByType('Agency')">Agencies ({{ $metrics['agencies_count'] ?? 0 }})</button>
                </div>

                <div id="companiesActiveFilterChips" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;"></div>
            </div>

            <!-- Right: Column Customizer Dropdown Trigger -->
            <div style="display: flex; align-items: center; gap: 8px; position: relative; flex-shrink: 0;">
                <button type="button" class="hr-btn hr-btn-secondary" id="btnCompaniesColFilter" onclick="toggleCompaniesTableSettingsDropdown(event)" style="height: 31px; font-size: 12px; padding: 0 12px;">
                    <i class="ph ph-columns" style="color: #7c3aed;"></i>
                    <span>Columns</span>
                    <i class="ph ph-caret-down" style="font-size: 10px; color: #64748b;"></i>
                </button>

                <!-- Floating Settings Dropdown Menu -->
                <div id="companiesTableSettingsDropdown" class="modular-settings-dropdown" style="display: none; position: absolute; right: 0; top: calc(100% + 6px); z-index: 999; width: 330px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 12px 32px rgba(15, 23, 42, 0.15); padding: 14px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <div style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 700; color: #0f172a;">
                            <i class="ph ph-gear-six" style="color: #7c3aed;"></i>
                            <span>Table Columns &amp; Sequence</span>
                        </div>
                        <span id="companiesColActiveCounter" style="font-size: 11px; background: #f5f3ff; color: #7c3aed; padding: 2px 8px; border-radius: 999px; font-weight: 700;">9 of 13 visible</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-size: 11px;">
                        <button type="button" onclick="resetDirectoryColumnDefaults()" style="border: none; background: none; color: #7c3aed; font-weight: 600; cursor: pointer; padding: 0;">Reset Default</button>
                        <span style="color: #cbd5e1;">&bull;</span>
                        <button type="button" onclick="showAllDirectoryColumns()" style="border: none; background: none; color: #7c3aed; font-weight: 600; cursor: pointer; padding: 0;">Select All</button>
                        <span style="color: #cbd5e1;">&bull;</span>
                        <button type="button" onclick="resetDirectoryColumnWidths()" style="border: none; background: none; color: #7c3aed; font-weight: 600; cursor: pointer; padding: 0;">Reset Widths</button>
                    </div>
                    <input type="text" id="directoryColSearchInput" placeholder="Filter columns..." oninput="handleDirectoryColSearch(this.value)" class="hr-input" style="height: 30px; font-size: 11.5px; margin-bottom: 8px; width: 100%; box-sizing: border-box;">
                    
                    <div id="directoryColCheckboxesList" style="max-height: 250px; overflow-y: auto; display: flex; flex-direction: column; gap: 4px;">
                        <!-- Populated dynamically by JS with draggable column order -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Full-Viewport Table Card with Sticky Headers & Pinned Bottom Footer -->
        <div class="hr-table-card hr-table-card-full modular-table-panel">
            <!-- Responsive Table Container -->
            <div class="hr-table-wrapper modular-table-responsive" id="companiesTableWrapper">
                <table class="hr-table modular-table" id="companiesDirectoryTable">
                    <thead id="companiesDirectoryThead">
                        <tr id="companiesDirectoryTheadRow">
                            <!-- Populated dynamically with stacked headers, resizers & drag-and-drop -->
                        </tr>
                    </thead>
                    <tbody id="companiesDirectoryTbody">
                        <!-- Server-rendered initial fallback for instant paint & SEO/Testing, hydrated by renderCompaniesDirectory() -->
                        @foreach($companies as $comp)
                            <tr>
                                <td>{{ $comp->company_code }} - {{ $comp->company_legal_name }}</td>
                                <td>
                                    <div class="branch-pills-wrap">
                                        @foreach($comp->branches as $b)
                                            <span class="branch-pill">{{ $b->branch_code }} - {{ $b->branch_name }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <button type="button" onclick="confirmDeleteCompany({{ $comp->company_id }}, '{{ addslashes($comp->company_legal_name) }}')">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Glassmorphic Table Footer & Pagination (Pinned at Bottom) -->
            <div class="hr-table-footer" id="companiesDirectoryPaginationBar">
                <div class="hr-pagination-container">
                    <div class="hr-pagination-left">
                        <div class="hr-pagination-info" id="companiesPaginationInfo">
                            Showing <strong>1</strong> to <strong>{{ min(10, $companies->count()) }}</strong> of <strong>{{ $companies->count() }}</strong> companies
                        </div>
                        <div class="hr-per-page-wrap">
                            <span class="hr-per-page-label">Show</span>
                            <select id="companiesPageSizeSelect" class="hr-per-page-select" onchange="changeDirectoryPageSize(this.value)">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                            <span class="hr-per-page-label">rows</span>
                        </div>
                    </div>
                    <div class="hr-pagination-nav" id="companiesPaginationControls">
                        <!-- Rendered by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====================================================================
         VIEW 2: UNIFIED ERP COMPANY, AGENCY & BRANCH DETAILS WORKSPACE
         ==================================================================== -->
    <div id="sectionViewDetails" class="cb-view-pane" style="display: {{ $currentView === 'details' ? 'flex' : 'none' }};">
        @if($selectedCompany)
            <div class="company-details-container">
                
                <!-- Top Action Bar -->
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
                    <button type="button" class="cb-btn-secondary" onclick="switchViewMode('directory')">
                        <i class="ph ph-arrow-left"></i>
                        <span>Back to Companies Directory</span>
                    </button>

                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <!-- Toggle Company Active Status -->
                        <form action="{{ route('config.companies-branches.toggle', $selectedCompany->company_id) }}" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="cb-btn-secondary" style="border-color: {{ $selectedCompany->company_is_active ? '#fde68a' : '#a7f3d0' }}; background: {{ $selectedCompany->company_is_active ? '#fffbeb' : '#ecfdf5' }}; color: {{ $selectedCompany->company_is_active ? '#b45309' : '#047857' }};">
                                <i class="ph {{ $selectedCompany->company_is_active ? 'ph-pause-circle' : 'ph-play-circle' }}"></i>
                                <span>{{ $selectedCompany->company_is_active ? 'Deactivate Entity' : 'Activate Entity' }}</span>
                            </button>
                        </form>

                        <!-- Delete Company CTA -->
                        <button type="button" onclick="confirmDeleteCompany({{ $selectedCompany->company_id }}, '{{ addslashes($selectedCompany->company_legal_name) }}')" style="height: 34px; border: 1px solid #fecaca; background: #fff5f5; color: #dc2626; font-size: 12px; font-weight: 600; padding: 6px 13px; border-radius: 9px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;" title="Delete this company and all associated records">
                            <i class="ph ph-trash"></i>
                            <span>Delete Company</span>
                        </button>

                        <!-- Context Switcher CTA -->
                        <form action="{{ route('context.switch') }}" method="POST" style="margin: 0;">
                            @csrf
                            <input type="hidden" name="company_id" value="{{ $selectedCompany->company_id }}">
                            <input type="hidden" name="is_consolidated" value="0">
                            <button type="submit" class="cb-btn-secondary">
                                <i class="ph ph-arrows-clockwise" style="color: #7c3aed;"></i>
                                <span>Switch Workspace Context</span>
                            </button>
                        </form>

                        <!-- Save Profile Form Submit -->
                        <button type="submit" form="companyDetailsUpdateForm" class="cb-btn-primary" style="padding: 6px 18px;">
                            <i class="ph ph-floppy-disk"></i>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </div>

                <!-- Master Company Profile & Hierarchy Form -->
                <form id="companyDetailsUpdateForm" action="{{ route('config.companies-branches.update', $selectedCompany->company_id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="hierarchy_section_present" value="1">

                    <!-- Hero Identity Header Card -->
                    <div class="company-hero-banner">
                        <!-- Company Logo Square Dropzone -->
                        <div class="company-logo-dropzone" onclick="document.getElementById('companyLogoFileInput').click()" title="Click to upload/change company logo">
                            @if($selectedCompany->company_logo)
                                <img id="companyLogoPreviewImg" src="{{ asset('storage/' . $selectedCompany->company_logo) }}" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                            @else
                                <div id="companyLogoPlaceholder" style="display: flex; flex-direction: column; align-items: center; color: #7c3aed; font-size: 11px; text-align: center; padding: 4px;">
                                    <i class="ph ph-camera" style="font-size: 26px; margin-bottom: 2px;"></i>
                                    <span style="font-weight: 700;">Entity Logo</span>
                                </div>
                            @endif
                            <div class="logo-hover-mask" style="position: absolute; inset: 0; background: rgba(15, 23, 42, 0.65); color: #ffffff; display: none; align-items: center; justify-content: center; flex-direction: column; font-size: 10px; font-weight: 600;">
                                <i class="ph ph-camera-plus" style="font-size: 20px; margin-bottom: 2px;"></i>
                                <span>Change</span>
                            </div>
                        </div>
                        <input type="file" name="logo" id="companyLogoFileInput" accept="image/*" style="display: none;" onchange="handleLogoFileSelect(this)">

                        <!-- Company Names & Badges -->
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <span style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #7c3aed; letter-spacing: 0.05em;">General Business Profile &bull; {{ $selectedCompany->company_type ?: 'Company' }}</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                <input type="text" name="company_legal_name" value="{{ old('company_legal_name', $selectedCompany->company_legal_name) }}" required placeholder="Legal Registered Entity Name" style="font-size: clamp(1.25rem, 2vw, 1.65rem); font-weight: 800; color: #0f172a; border: 1px solid transparent; background: rgba(255,255,255,0.6); padding: 3px 8px; border-radius: 8px; width: 100%; max-width: 660px; outline: none; transition: all 0.2s;" onfocus="this.style.background='#ffffff'; this.style.borderColor='#a855f7';" onblur="this.style.background='rgba(255,255,255,0.6)'; this.style.borderColor='transparent';">
                            </div>

                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">Trade Name:</span>
                                    <input type="text" name="company_trade_name" value="{{ old('company_trade_name', $selectedCompany->company_trade_name) }}" placeholder="DBA / Brand Name" style="font-size: 12px; font-weight: 600; color: #1e293b; padding: 3px 9px; border: 1px solid #cbd5e1; border-radius: 7px; background: #ffffff;">
                                </div>

                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">Code:</span>
                                    <input type="text" name="company_code" value="{{ old('company_code', $selectedCompany->company_code) }}" required style="font-size: 12px; font-weight: 700; color: #7c3aed; padding: 3px 9px; border: 1px solid #cbd5e1; border-radius: 7px; background: #ffffff; width: 95px; text-transform: uppercase; font-family: monospace;">
                                </div>

                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; background: {{ $selectedCompany->company_is_active ? 'rgba(16, 185, 129, 0.12)' : 'rgba(239, 68, 68, 0.12)' }}; color: {{ $selectedCompany->company_is_active ? '#059669' : '#dc2626' }};">
                                    <i class="ph {{ $selectedCompany->company_is_active ? 'ph-check-circle' : 'ph-x-circle' }}"></i>
                                    {{ $selectedCompany->company_is_active ? 'Active Entity' : 'Inactive' }}
                                </span>

                                <button type="button" class="cb-staff-pill-btn" onclick="openMembersModal('company', {{ $selectedCompany->company_id }}, '{{ addslashes($selectedCompany->company_legal_name) }}')">
                                    <i class="ph ph-users-three"></i>
                                    <span>{{ $selectedCompany->employees_count ?? 0 }} Assigned Staff</span>
                                </button>

                                <!-- Brand Color Indicator Dot -->
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="width: 14px; height: 14px; border-radius: 50%; background-color: {{ $selectedCompany->company_brand_color ?: '#ef4444' }}; display: inline-block; box-shadow: 0 0 0 2px #ffffff, 0 0 0 3px {{ $selectedCompany->company_brand_color ?: '#ef4444' }};" id="headerBrandColorDot"></span>
                                    <span style="font-size: 11.5px; color: #64748b;">Brand Color</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Functional Sub-Tabs -->
                    <div class="company-tabs-nav">
                        <button type="button" class="company-tab-btn {{ $activeSubTab === 'general' ? 'active' : '' }}" id="tabBtnGeneral" onclick="switchCompanySubTab('general')" title="General Business Profile">
                            <i class="ph ph-buildings"></i>
                            <span>General Information</span>
                        </button>
                        <button type="button" class="company-tab-btn {{ $activeSubTab === 'branches' ? 'active' : '' }}" id="tabBtnBranches" onclick="switchCompanySubTab('branches')" title="Branches & Sites Hub">
                            <i class="ph ph-storefront"></i>
                            <span>Branches &amp; Sites Hub</span>
                            <span style="background: #f5f3ff; color: #7c3aed; font-size: 11px; padding: 2px 7px; border-radius: 999px; font-weight: 700;">{{ $selectedCompany->branches->count() }}</span>
                        </button>
                        <button type="button" class="company-tab-btn {{ $activeSubTab === 'vault' ? 'active' : '' }}" id="tabBtnVault" onclick="switchCompanySubTab('vault')" title="Certificate & Legal Hub">
                            <i class="ph ph-file-lock"></i>
                            <span>Certificate &amp; Legal Hub</span>
                            @if($vaultStats['expiring_soon'] > 0 || $vaultStats['expired'] > 0)
                                <span style="background: #fee2e2; color: #dc2626; font-size: 10.5px; padding: 2px 6px; border-radius: 999px; font-weight: 700;">{{ $vaultStats['expiring_soon'] + $vaultStats['expired'] }}</span>
                            @endif
                        </button>
                        <button type="button" class="company-tab-btn {{ $activeSubTab === 'hierarchy' ? 'active' : '' }}" id="tabBtnHierarchy" onclick="switchCompanySubTab('hierarchy')" title="Hierarchy & Relationships">
                            <i class="ph ph-tree-structure"></i>
                            <span>Hierarchy &amp; Relationships</span>
                        </button>
                    </div>

                    <!-- ==========================================================
                         SUB-TAB 1: GENERAL INFORMATION (4-Card ERP Settings Grid)
                         ========================================================== -->
                    <div id="companyTabPaneGeneral" class="company-subtab-pane" style="display: {{ $activeSubTab === 'general' ? 'block' : 'none' }};">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
                            
                            <!-- Card 1: Registered Address & Principal Location -->
                            <div class="cb-section-card">
                                <div class="cb-section-card-header">
                                    <div class="cb-section-card-icon"><i class="ph ph-map-pin-line"></i></div>
                                    <div>
                                        <h3 class="cb-section-card-title">Principal Office &amp; Registered Address</h3>
                                    </div>
                                </div>

                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Address Line 1 (Street / Purok / Building)</label>
                                        <input type="text" name="company_address_line_1" value="{{ old('company_address_line_1', $selectedCompany->company_address_line_1) }}" placeholder="e.g. Purok-5" class="cb-input">
                                    </div>
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Address Line 2 (Barangay / District / Floor)</label>
                                        <input type="text" name="company_address_line_2" value="{{ old('company_address_line_2', $selectedCompany->company_address_line_2) }}" placeholder="e.g. Camp 1" class="cb-input">
                                    </div>
                                    <div style="display: grid; grid-template-columns: 2fr 2fr 1fr; gap: 8px;">
                                        <div>
                                            <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">City / Municipality</label>
                                            <input type="text" name="company_city" value="{{ old('company_city', $selectedCompany->company_city) }}" placeholder="Maramag" class="cb-input">
                                        </div>
                                        <div>
                                            <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Province / State</label>
                                            <input type="text" name="company_state_province_code" value="{{ old('company_state_province_code', $selectedCompany->company_state_province_code) }}" placeholder="Bukidnon (PH)" class="cb-input">
                                        </div>
                                        <div>
                                            <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Postal / ZIP</label>
                                            <input type="text" name="company_postal_code" value="{{ old('company_postal_code', $selectedCompany->company_postal_code) }}" placeholder="8712" class="cb-input">
                                        </div>
                                    </div>
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Country Code (ISO-2)</label>
                                        <input type="text" name="company_country_code" value="{{ old('company_country_code', $selectedCompany->company_country_code ?: 'PH') }}" maxlength="2" placeholder="PH" class="cb-input">
                                    </div>
                                </div>
                            </div>

                            <!-- Card 2: Statutory Tax & BIR Registration -->
                            <div class="cb-section-card">
                                <div class="cb-section-card-header">
                                    <div class="cb-section-card-icon"><i class="ph ph-receipt"></i></div>
                                    <div>
                                        <h3 class="cb-section-card-title">Statutory Tax &amp; BIR Registration</h3>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px;">
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">TIN (Taxpayer Identification No.)</label>
                                        <input type="text" name="company_tax_id" value="{{ old('company_tax_id', $selectedCompany->company_tax_id) }}" placeholder="412-277-750-00000" class="cb-input" style="font-family: monospace;">
                                    </div>
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">BIR Branch Code</label>
                                        <input type="text" name="company_branch_code" value="{{ old('company_branch_code', $selectedCompany->company_branch_code ?: '00000') }}" placeholder="00000" class="cb-input" style="font-family: monospace;">
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">RDO Code</label>
                                        <input type="text" name="company_rdo_code" value="{{ old('company_rdo_code', $selectedCompany->company_rdo_code) }}" placeholder="e.g. 098" class="cb-input">
                                    </div>
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">SEC / DTI Reg. No.</label>
                                        <input type="text" name="company_registration_no" value="{{ old('company_registration_no', $selectedCompany->company_registration_no) }}" placeholder="e.g. CS202600192" class="cb-input">
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; align-items: end;">
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Tax Classification</label>
                                        <select name="company_tax_type" class="cb-select">
                                            @foreach($taxTypes as $tt)
                                                <option value="{{ $tt }}" {{ $selectedCompany->company_tax_type === $tt ? 'selected' : '' }}>{{ $tt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 8px; height: 35px; padding: 0 10px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                                        <input type="checkbox" name="company_is_vat_registered" id="vatCheckbox" value="1" {{ ($selectedCompany->company_is_vat_registered ?? ($selectedCompany->company_tax_type === 'VAT Registered')) ? 'checked' : '' }} style="width: 16px; height: 16px; cursor: pointer; accent-color: #7c3aed;">
                                        <label for="vatCheckbox" style="font-size: 12px; font-weight: 600; color: #334155; cursor: pointer;">VAT Registered Entity</label>
                                    </div>
                                </div>

                                <div>
                                    <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">BIR PTU / POS Permit Number</label>
                                    <input type="text" name="bir_permit_number" value="{{ old('bir_permit_number', $selectedCompany->bir_permit_number) }}" placeholder="e.g. FP012026-047-0019283-00000" class="cb-input">
                                </div>
                            </div>

                            <!-- Card 3: Accounting, Fiscal & System Integration -->
                            <div class="cb-section-card">
                                <div class="cb-section-card-header">
                                    <div class="cb-section-card-icon"><i class="ph ph-coins"></i></div>
                                    <div>
                                        <h3 class="cb-section-card-title">Accounting, Fiscal Calendar &amp; Branding</h3>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Base Accounting Currency</label>
                                        <select name="company_base_currency_code" class="cb-select">
                                            @foreach($currencies as $curr)
                                                <option value="{{ $curr }}" {{ $selectedCompany->company_base_currency_code === $curr ? 'selected' : '' }}>
                                                    {{ $curr }} - {{ $curr === 'PHP' ? 'Philippine Peso (PHP)' : ($curr === 'USD' ? 'US Dollar (USD)' : $curr) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Fiscal Year Start Month</label>
                                        <select name="fiscal_year_start_month" class="cb-select">
                                            @foreach($fiscalMonths as $mNum => $mName)
                                                <option value="{{ $mNum }}" {{ ((int)($selectedCompany->fiscal_year_start_month ?: 1)) === $mNum ? 'selected' : '' }}>{{ $mName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">System Timezone</label>
                                        <select name="company_timezone" class="cb-select">
                                            @foreach($timezones as $tz)
                                                <option value="{{ $tz }}" {{ $selectedCompany->company_timezone === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">External Payroll / ERP Code</label>
                                        <input type="text" name="company_external_code" value="{{ old('company_external_code', $selectedCompany->company_external_code) }}" placeholder="Code used in work entry exports" class="cb-input">
                                    </div>
                                </div>

                                <div>
                                    <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">Brand Accent Color</label>
                                    <div style="display: flex; align-items: center; gap: 10px; background: #ffffff; padding: 6px 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                                        <input type="color" name="company_brand_color" id="companyBrandColorPicker" value="{{ old('company_brand_color', $selectedCompany->company_brand_color ?: '#ef4444') }}" onchange="handleBrandColorChange(this.value)" style="width: 30px; height: 28px; border: none; border-radius: 6px; cursor: pointer; padding: 0; background: transparent;">
                                        <span id="brandColorHexLabel" style="font-size: 12px; font-family: monospace; font-weight: 700; color: #334155;">{{ $selectedCompany->company_brand_color ?: '#ef4444' }}</span>
                                        <div style="display: flex; align-items: center; gap: 6px; margin-left: auto;">
                                            @foreach(['#ef4444', '#7c3aed', '#ec4899', '#10b981', '#3b82f6', '#f59e0b'] as $presetColor)
                                                <button type="button" onclick="selectBrandColorPreset('{{ $presetColor }}')" style="width: 18px; height: 18px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.15); background-color: {{ $presetColor }}; cursor: pointer; padding: 0;" title="{{ $presetColor }}"></button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 4: Contact, Digital Presence & Receipt Compliance -->
                            <div class="cb-section-card">
                                <div class="cb-section-card-header">
                                    <div class="cb-section-card-icon"><i class="ph ph-address-book"></i></div>
                                    <div>
                                        <h3 class="cb-section-card-title">Contact, Digital Presence &amp; Receipt Footers</h3>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Designated Contact Person</label>
                                        <input type="text" name="company_contact_person" value="{{ old('company_contact_person', $selectedCompany->company_contact_person) }}" placeholder="Administrator / Account Exec" class="cb-input">
                                    </div>
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Official Phone</label>
                                        <input type="text" name="company_phone_number" value="{{ old('company_phone_number', $selectedCompany->company_phone_number) }}" placeholder="+63 2 8000 0000" class="cb-input">
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Corporate Email</label>
                                        <input type="email" name="company_email_address" value="{{ old('company_email_address', $selectedCompany->company_email_address) }}" placeholder="info@company.com" class="cb-input">
                                    </div>
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Email Domain</label>
                                        <input type="text" name="company_email_domain" value="{{ old('company_email_domain', $selectedCompany->company_email_domain) }}" placeholder="goodfarms.odoo.com" class="cb-input">
                                    </div>
                                </div>

                                <div>
                                    <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Website URL</label>
                                    <input type="text" name="company_website_url" value="{{ old('company_website_url', $selectedCompany->company_website_url) }}" placeholder="https://www.company.com" class="cb-input">
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Invoice / Receipt Header Text</label>
                                        <textarea name="invoice_header_text" rows="2" class="cb-textarea" placeholder="TIN & VAT Header lines on POS/Invoices">{{ old('invoice_header_text', $selectedCompany->invoice_header_text) }}</textarea>
                                    </div>
                                    <div>
                                        <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Invoice / Receipt Footer Text</label>
                                        <textarea name="invoice_footer_text" rows="2" class="cb-textarea" placeholder="THIS INVOICE/RECEIPT SHALL BE VALID FOR FIVE (5) YEARS">{{ old('invoice_footer_text', $selectedCompany->invoice_footer_text) }}</textarea>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ==========================================================
                         SUB-TAB 4: HIERARCHY & RELATIONSHIPS (Inside Master Form)
                         ========================================================== -->
                    <div id="companyTabPaneHierarchy" class="company-subtab-pane" style="display: {{ $activeSubTab === 'hierarchy' ? 'block' : 'none' }};">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 20px;">
                            
                            <!-- Left Card: Corporate Structure & Holding Hierarchy -->
                            <div class="cb-section-card">
                                <div class="cb-section-card-header">
                                    <div class="cb-section-card-icon"><i class="ph ph-tree-structure"></i></div>
                                    <div>
                                        <h3 class="cb-section-card-title">Entity Level &amp; Consolidation Hierarchy</h3>
                                    </div>
                                </div>

                                <div>
                                    <label style="font-size: 12px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 4px;">Entity Classification</label>
                                    <select name="company_type" class="cb-select">
                                        <option value="Company" {{ $selectedCompany->company_type === 'Company' ? 'selected' : '' }}>Company (Standard Operating Entity)</option>
                                        <option value="Holding Enterprise" {{ $selectedCompany->company_type === 'Holding Enterprise' ? 'selected' : '' }}>Holding Enterprise (Parent Group)</option>
                                        <option value="Subsidiary" {{ $selectedCompany->company_type === 'Subsidiary' ? 'selected' : '' }}>Subsidiary Operating Brand</option>
                                        <option value="Agency" {{ $selectedCompany->company_type === 'Agency' ? 'selected' : '' }}>Agency / External Staffing Partner</option>
                                    </select>
                                </div>

                                <div>
                                    <label style="font-size: 12px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 4px;">Parent Holding Company</label>
                                    <select name="parent_company_id" class="cb-select">
                                        <option value="">None (Top-Level Independent Entity)</option>
                                        @foreach($companies as $c)
                                            @if($c->company_id !== $selectedCompany->company_id)
                                                <option value="{{ $c->company_id }}" {{ $selectedCompany->parent_company_id === $c->company_id ? 'selected' : '' }}>{{ $c->company_legal_name }} ({{ $c->company_code }})</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label style="font-size: 12px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 4px;">Internal Notes &amp; Engagement Scope</label>
                                    <textarea name="company_notes" rows="3" class="cb-textarea" placeholder="Notes on subsidiary scope, SLA terms for staffing agencies, or inter-company billing">{{ old('company_notes', $selectedCompany->company_notes) }}</textarea>
                                </div>
                            </div>

                            <!-- Right Card: Shared vs. Independent Master Data Rules Matrix -->
                            <div class="cb-section-card">
                                <div class="cb-section-card-header">
                                    <div class="cb-section-card-icon"><i class="ph ph-git-merge"></i></div>
                                    <div>
                                        <h3 class="cb-section-card-title">Shared vs. Independent Master Data Matrix</h3>
                                    </div>
                                </div>

                                <div class="cb-sharing-rule-card">
                                    <div>
                                        <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Shared Chart of Accounts (COA)</div>
                                        <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">Use unified GL account codes across parent and subsidiary entities for consolidated financial statements.</div>
                                    </div>
                                    <input type="checkbox" name="share_chart_of_accounts" value="1" {{ ($selectedCompany->share_chart_of_accounts ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: #7c3aed; cursor: pointer; flex-shrink: 0;">
                                </div>

                                <div class="cb-sharing-rule-card">
                                    <div>
                                        <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Shared Customer &amp; Vendor Directory</div>
                                        <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">Allow accredited suppliers, RFQs, and B2B customers to transact across all connected branches and subsidiaries.</div>
                                    </div>
                                    <input type="checkbox" name="share_customer_vendor_directory" value="1" {{ ($selectedCompany->share_customer_vendor_directory ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: #7c3aed; cursor: pointer; flex-shrink: 0;">
                                </div>

                                <div class="cb-sharing-rule-card">
                                    <div>
                                        <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Shared Product Catalog, Recipes &amp; UOM</div>
                                        <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">Synchronize Item Master SKUs, standard UOM conversions, and central commissary BOMs across entities.</div>
                                    </div>
                                    <input type="checkbox" name="share_product_catalog" value="1" {{ ($selectedCompany->share_product_catalog ?? true) ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: #7c3aed; cursor: pointer; flex-shrink: 0;">
                                </div>
                            </div>

                        </div>
                    </div>
                </form>

                <!-- ==========================================================
                     SUB-TAB 2: BRANCHES & SITES HUB (Outside Master Form to avoid nested forms)
                     ========================================================== -->
                <div id="companyTabPaneBranches" class="company-subtab-pane" style="display: {{ $activeSubTab === 'branches' ? 'block' : 'none' }};">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0;">Branches of {{ $selectedCompany->company_trade_name ?: $selectedCompany->company_legal_name }}</h3>
                            <p style="font-size: 0.8rem; color: #64748b; margin: 2px 0 0;">Manage retail stores, commissaries, warehouses, and head office sites linked to this legal entity.</p>
                        </div>
                        <button type="button" class="cb-btn-primary" onclick="openCreateBranchModal({{ $selectedCompany->company_id }})">
                            <i class="ph ph-plus-circle" style="font-size: 15px;"></i>
                            <span>Add New Branch</span>
                        </button>
                    </div>

                    <!-- Branches Grid Cards -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px;">
                        @forelse($selectedCompany->branches as $branch)
                            <div class="branch-item-card">
                                <div>
                                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 10px;">
                                        <div>
                                            <h4 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0;">{{ $branch->branch_name }}</h4>
                                            <span style="font-size: 11px; font-family: monospace; color: #7c3aed; font-weight: 700;">{{ $branch->branch_code }}</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <button type="button" class="cb-staff-pill-btn" onclick="openMembersModal('branch', {{ $branch->branch_id }}, '{{ addslashes($branch->branch_name) }}')" title="View Branch Staff">
                                                <i class="ph ph-users"></i>
                                                <span>{{ $branch->employees_count ?? 0 }}</span>
                                            </button>
                                            <span style="font-size: 11px; padding: 2px 8px; border-radius: 999px; font-weight: 600; background: {{ $branch->branch_is_active ? 'rgba(16, 185, 129, 0.12)' : 'rgba(239, 68, 68, 0.12)' }}; color: {{ $branch->branch_is_active ? '#059669' : '#dc2626' }};">
                                                {{ $branch->branch_is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div style="display: flex; flex-direction: column; gap: 6px; font-size: 12px; color: #475569; margin-bottom: 14px;">
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <i class="ph ph-tag" style="color: #7c3aed;"></i>
                                            <span>{{ $branch->branch_type }}</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <i class="ph ph-clock" style="color: #64748b;"></i>
                                            <span>{{ $branch->operating_hours ?: '10:00 AM - 10:00 PM' }}</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <i class="ph ph-map-pin" style="color: #64748b;"></i>
                                            <span>{{ $branch->full_address ?: 'No address specified' }}</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <i class="ph ph-receipt" style="color: #64748b;"></i>
                                            <span>Tax Code: <strong>{{ $branch->branch_tax_code ?: 'N/A' }}</strong></span>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 10px;">
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <button type="button" onclick="editBranch({{ $branch->toJson() }})" class="cb-btn-secondary" style="height: 28px; padding: 3px 10px; font-size: 11.5px;">
                                            <i class="ph ph-pencil-simple"></i> Edit Branch
                                        </button>

                                        <button type="button" onclick="confirmDeleteBranch({{ $branch->branch_id }}, '{{ addslashes($branch->branch_name) }}')" style="border: 1px solid #fecaca; background: #fff5f5; color: #dc2626; font-size: 11.5px; font-weight: 600; padding: 4px 8px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;" title="Delete Branch">
                                            <i class="ph ph-trash"></i> Delete
                                        </button>
                                    </div>

                                    <form action="{{ route('config.business-settings.branches.toggle', $branch->branch_id) }}" method="POST" style="margin: 0;">
                                        @csrf
                                        <input type="hidden" name="from_companies_branches" value="1">
                                        <button type="submit" style="border: none; background: {{ $branch->branch_is_active ? '#fef2f2' : '#ecfdf5' }}; color: {{ $branch->branch_is_active ? '#dc2626' : '#059669' }}; font-size: 11.5px; font-weight: 600; padding: 4px 10px; border-radius: 6px; cursor: pointer;">
                                            {{ $branch->branch_is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div style="grid-column: 1 / -1; padding: 30px; text-align: center; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;">
                                <i class="ph ph-storefront" style="font-size: 32px; color: #94a3b8; margin-bottom: 6px;"></i>
                                <h4 style="font-size: 13px; font-weight: 700; color: #334155; margin: 0;">No Branches Connected Yet</h4>
                                <p style="font-size: 12px; color: #64748b; margin: 4px 0 12px;">Create retail outlets, central commissaries, or warehouses attached to this company.</p>
                                <button type="button" class="cb-btn-primary" onclick="openCreateBranchModal({{ $selectedCompany->company_id }})">
                                    <i class="ph ph-plus"></i> Add First Branch
                                </button>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- ==========================================================
                     SUB-TAB 3: CERTIFICATE & LEGAL HUB (Document Vault)
                     ========================================================== -->
                <div id="companyTabPaneVault" class="company-subtab-pane" style="display: {{ $activeSubTab === 'vault' ? 'block' : 'none' }};">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0;">Compliance &amp; Document Vault</h3>
                            <p style="font-size: 0.8rem; color: #64748b; margin: 2px 0 0;">COR (BIR Form 2303), Mayor's Permit, Fire Clearances, and Statutory Certificates for {{ $selectedCompany->company_legal_name }}</p>
                        </div>
                        <button type="button" class="cb-btn-primary" onclick="openDocumentModal()">
                            <i class="ph ph-upload-simple"></i>
                            <span>Upload Legal Document</span>
                        </button>
                    </div>

                    <!-- Vault Radar Badges -->
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap;">
                        <span style="font-size: 12px; font-weight: 600; color: #059669; background: #ecfdf5; padding: 4px 10px; border-radius: 999px;">
                            <i class="ph ph-shield-check"></i> {{ $vaultStats['active'] }} Active Documents
                        </span>
                        @if($vaultStats['expiring_soon'] > 0)
                            <span style="font-size: 12px; font-weight: 600; color: #d97706; background: #fef3c7; padding: 4px 10px; border-radius: 999px;">
                                <i class="ph ph-warning"></i> {{ $vaultStats['expiring_soon'] }} Expiring Soon (&le;30 Days)
                            </span>
                        @endif
                        @if($vaultStats['expired'] > 0)
                            <span style="font-size: 12px; font-weight: 600; color: #dc2626; background: #fee2e2; padding: 4px 10px; border-radius: 999px;">
                                <i class="ph ph-x-circle"></i> {{ $vaultStats['expired'] }} Expired
                            </span>
                        @endif
                    </div>

                    <!-- Documents List Table -->
                    <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: left;">
                            <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <tr>
                                    <th style="padding: 10px 14px;">Document Name &amp; No.</th>
                                    <th style="padding: 10px 14px;">Category</th>
                                    <th style="padding: 10px 14px;">Issuing Authority</th>
                                    <th style="padding: 10px 14px;">Expiration</th>
                                    <th style="padding: 10px 14px;">Status</th>
                                    <th style="padding: 10px 14px; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($selectedCompany->legalDocuments as $doc)
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 10px 14px;">
                                            <div style="font-weight: 700; color: #0f172a;">{{ $doc->document_name }}</div>
                                            @if($doc->document_number)
                                                <div style="font-size: 11px; color: #64748b; font-family: monospace;">No: {{ $doc->document_number }}</div>
                                            @endif
                                        </td>
                                        <td style="padding: 10px 14px; color: #475569;">{{ $doc->document_category }}</td>
                                        <td style="padding: 10px 14px; color: #475569;">{{ $doc->issuing_authority ?: 'N/A' }}</td>
                                        <td style="padding: 10px 14px; color: #475569;">{{ $doc->expiration_date ? \Carbon\Carbon::parse($doc->expiration_date)->format('M d, Y') : 'Perpetual' }}</td>
                                        <td style="padding: 10px 14px;">
                                            <span style="font-size: 11px; padding: 2px 8px; border-radius: 999px; font-weight: 600; background: {{ $doc->expiration_status === 'Active' ? '#ecfdf5' : ($doc->expiration_status === 'Expiring Soon' ? '#fef3c7' : '#fee2e2') }}; color: {{ $doc->expiration_status === 'Active' ? '#059669' : ($doc->expiration_status === 'Expiring Soon' ? '#d97706' : '#dc2626') }};">
                                                {{ $doc->expiration_status }}
                                            </span>
                                        </td>
                                        <td style="padding: 10px 14px; text-align: right;">
                                            <div style="display: inline-flex; align-items: center; gap: 8px;">
                                                @if($doc->file_path)
                                                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" style="color: #7c3aed; font-weight: 600; text-decoration: none;"><i class="ph ph-arrow-square-out"></i> View</a>
                                                @endif
                                                <form action="{{ route('config.business-settings.documents.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Delete document {{ addslashes($doc->document_name) }}?');" style="display: inline; margin: 0;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="from_companies_branches" value="1">
                                                    <button type="submit" style="border: none; background: transparent; color: #dc2626; cursor: pointer; font-size: 13px;" title="Delete Document">
                                                        <i class="ph ph-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="padding: 24px; text-align: center; color: #94a3b8;">
                                            No compliance documents stored in the vault yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        @else
            <div style="padding: 40px; text-align: center; background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0;">
                <i class="ph ph-buildings" style="font-size: 40px; color: #94a3b8;"></i>
                <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 10px;">No Company Selected</h3>
                <p style="font-size: 13px; color: #64748b;">Please select a company from the directory to view its details.</p>
                <button type="button" class="cb-btn-primary" onclick="switchViewMode('directory')" style="margin-top: 10px;">Back to Directory</button>
            </div>
        @endif
    </div>

    <!-- ====================================================================
         MODALS: CREATE COMPANY/AGENCY, BRANCHES & DOCUMENT VAULT
         ==================================================================== -->
    
    <!-- 1. Modal: New Company / Agency -->
    <div id="modalCreateCompany" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: none; z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 600px; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.22); overflow: hidden;">
            <div style="padding: 16px 22px; border-bottom: 1px solid #f1f5f9; background: linear-gradient(135deg, #fdf4ff 0%, #f5f3ff 100%); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px; font-size: 15px; font-weight: 800; color: #0f172a;">
                    <i class="ph ph-buildings" style="color: #7c3aed; font-size: 20px;"></i>
                    <span>Register New Company or Staffing Agency</span>
                </div>
                <button type="button" onclick="closeCreateCompanyModal()" style="border: none; background: transparent; color: #64748b; font-size: 18px; cursor: pointer;"><i class="ph ph-x"></i></button>
            </div>
            <form action="{{ route('config.companies-branches.store') }}" method="POST" enctype="multipart/form-data" style="padding: 20px 22px; display: flex; flex-direction: column; gap: 14px;">
                @csrf
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Entity Code *</label>
                        <input type="text" name="company_code" required placeholder="e.g. GF-01" class="cb-input" style="text-transform: uppercase;">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Legal Registered Name *</label>
                        <input type="text" name="company_legal_name" required placeholder="e.g. Goodfarms Trading Corporation" class="cb-input">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Trade / Brand Name</label>
                        <input type="text" name="company_trade_name" placeholder="e.g. Goodfarms" class="cb-input">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Entity Classification</label>
                        <select name="company_type" class="cb-select">
                            <option value="Company">Company (Standard Operating Entity)</option>
                            <option value="Holding Enterprise">Holding Enterprise (Parent Group)</option>
                            <option value="Subsidiary">Subsidiary Operating Brand</option>
                            <option value="Agency">Agency (External Staffing Partner)</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">TIN</label>
                        <input type="text" name="company_tax_id" placeholder="412-277-750-00000" class="cb-input">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">BIR Branch Code</label>
                        <input type="text" name="company_branch_code" value="00000" placeholder="00000" class="cb-input">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Base Currency</label>
                        <select name="company_base_currency_code" class="cb-select">
                            <option value="PHP">PHP - Philippine Peso</option>
                            <option value="USD">USD - US Dollar</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Brand Accent Color</label>
                        <input type="color" name="company_brand_color" value="#7c3aed" class="cb-input" style="padding: 2px; cursor: pointer;">
                    </div>
                </div>

                <div style="padding-top: 10px; display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="cb-btn-secondary" onclick="closeCreateCompanyModal()">Cancel</button>
                    <button type="submit" class="cb-btn-primary">Create Entity</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Modal: New Branch -->
    <div id="modalCreateBranch" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: none; z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 580px; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.22); overflow: hidden;">
            <div style="padding: 16px 22px; border-bottom: 1px solid #f1f5f9; background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 800; color: #0f172a;">
                    <i class="ph ph-storefront" style="color: #059669; font-size: 20px;"></i>
                    <span>Add New Operational Branch</span>
                </div>
                <button type="button" onclick="closeCreateBranchModal()" style="border: none; background: transparent; color: #64748b; font-size: 18px; cursor: pointer;"><i class="ph ph-x"></i></button>
            </div>
            <form action="{{ route('config.business-settings.branches.store') }}" method="POST" style="padding: 20px 22px; display: flex; flex-direction: column; gap: 14px;">
                @csrf
                <input type="hidden" name="from_companies_branches" value="1">
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Belongs to Company *</label>
                    <select name="company_id" id="branchModalCompanySelect" required class="cb-select">
                        @foreach($companies as $c)
                            <option value="{{ $c->company_id }}" {{ $selectedCompany && $selectedCompany->company_id === $c->company_id ? 'selected' : '' }}>{{ $c->company_legal_name }} ({{ $c->company_code }})</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Code *</label>
                        <input type="text" name="branch_code" required placeholder="e.g. BR-01" class="cb-input" style="text-transform: uppercase;">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Name *</label>
                        <input type="text" name="branch_name" required placeholder="e.g. Main Commissary / SM Mall Store" class="cb-input">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Type</label>
                        <select name="branch_type" class="cb-select">
                            @foreach($branchTypes as $bt)
                                <option value="{{ $bt }}">{{ $bt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Tax Code / Suffix</label>
                        <input type="text" name="branch_tax_code" placeholder="e.g. 00001" class="cb-input">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">City</label>
                        <input type="text" name="branch_city" placeholder="e.g. Taguig City" class="cb-input">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Operating Hours</label>
                        <input type="text" name="operating_hours" placeholder="10:00 AM - 10:00 PM" class="cb-input">
                    </div>
                </div>

                <div style="padding-top: 10px; display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="cb-btn-secondary" onclick="closeCreateBranchModal()">Cancel</button>
                    <button type="submit" class="cb-btn-primary">Add Branch</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Modal: Edit Branch -->
    <div id="modalEditBranch" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: none; z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 580px; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.22); overflow: hidden;">
            <div style="padding: 16px 22px; border-bottom: 1px solid #f1f5f9; background: linear-gradient(135deg, #fdf4ff 0%, #f5f3ff 100%); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 800; color: #0f172a;">
                    <i class="ph ph-pencil-simple" style="color: #7c3aed; font-size: 20px;"></i>
                    <span>Edit Operational Branch</span>
                </div>
                <button type="button" onclick="closeEditBranchModal()" style="border: none; background: transparent; color: #64748b; font-size: 18px; cursor: pointer;"><i class="ph ph-x"></i></button>
            </div>
            <form id="editBranchForm" action="" method="POST" style="padding: 20px 22px; display: flex; flex-direction: column; gap: 14px;">
                @csrf
                @method('PUT')
                <input type="hidden" name="from_companies_branches" value="1">
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Code *</label>
                        <input type="text" name="branch_code" id="editBranchCode" required class="cb-input" style="text-transform: uppercase;">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Name *</label>
                        <input type="text" name="branch_name" id="editBranchName" required class="cb-input">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Type</label>
                        <select name="branch_type" id="editBranchType" class="cb-select">
                            @foreach($branchTypes as $bt)
                                <option value="{{ $bt }}">{{ $bt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Tax Code</label>
                        <input type="text" name="branch_tax_code" id="editBranchTaxCode" class="cb-input">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">City</label>
                        <input type="text" name="branch_city" id="editBranchCity" class="cb-input">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Operating Hours</label>
                        <input type="text" name="operating_hours" id="editBranchHours" class="cb-input">
                    </div>
                </div>

                <div style="padding-top: 10px; display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                    <button type="button" id="btnDeleteFromEditBranch" onclick="handleDeleteFromEditBranchModal()" style="border: 1px solid #fecaca; background: #fff5f5; color: #dc2626; font-size: 12px; font-weight: 600; padding: 7px 14px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="ph ph-trash"></i> Delete Branch
                    </button>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" class="cb-btn-secondary" onclick="closeEditBranchModal()">Cancel</button>
                        <button type="submit" class="cb-btn-primary">Update Branch</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. Modal: Upload Legal Document (Document Vault) -->
    <div id="modalUploadDocument" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: none; z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 580px; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.22); overflow: hidden;">
            <div style="padding: 16px 22px; border-bottom: 1px solid #f1f5f9; background: linear-gradient(135deg, #fdf4ff 0%, #f5f3ff 100%); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 800; color: #0f172a;">
                    <i class="ph ph-file-lock" style="color: #7c3aed; font-size: 20px;"></i>
                    <span>Upload Compliance &amp; Legal Document</span>
                </div>
                <button type="button" onclick="closeDocumentModal()" style="border: none; background: transparent; color: #64748b; font-size: 18px; cursor: pointer;"><i class="ph ph-x"></i></button>
            </div>
            <form action="{{ route('config.business-settings.documents.store') }}" method="POST" enctype="multipart/form-data" style="padding: 20px 22px; display: flex; flex-direction: column; gap: 12px;">
                @csrf
                <input type="hidden" name="from_companies_branches" value="1">
                <input type="hidden" name="company_id" value="{{ $selectedCompany ? $selectedCompany->company_id : '' }}">

                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Compliance Category *</label>
                    <select name="document_category" required class="cb-select">
                        @foreach($docCategories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Document Title *</label>
                        <input type="text" name="document_name" required placeholder="e.g. BIR Form 2303 - 2026" class="cb-input">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Permit / Cert No.</label>
                        <input type="text" name="document_number" placeholder="e.g. COR-2026-01" class="cb-input">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Issuing Authority</label>
                        <input type="text" name="issuing_authority" placeholder="e.g. BIR / SEC / LGU" class="cb-input">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Issue Date</label>
                        <input type="date" name="issue_date" class="cb-input">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Expiration Date</label>
                        <input type="date" name="expiration_date" class="cb-input">
                    </div>
                </div>

                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Document File (PDF, PNG, JPG up to 10MB) *</label>
                    <input type="file" name="document_file" required accept=".pdf,image/*" class="cb-input" style="padding: 4px 8px;">
                </div>

                <div style="padding-top: 8px; display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="cb-btn-secondary" onclick="closeDocumentModal()">Cancel</button>
                    <button type="submit" class="cb-btn-primary">Upload to Vault</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 5. Modal: Confirm Delete Company -->
    <div id="modalConfirmDeleteCompany" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: none; z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #ffffff; border-radius: 14px; width: 100%; max-width: 480px; box-shadow: 0 20px 40px rgba(0,0,0,0.25); overflow: hidden; border: 1px solid #fee2e2;">
            <div style="padding: 16px 20px; border-bottom: 1px solid #fee2e2; background: #fff5f5; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px; font-size: 15px; font-weight: 700; color: #991b1b;">
                    <i class="ph ph-warning-octagon" style="color: #dc2626; font-size: 22px;"></i>
                    <span>Delete Company Entity</span>
                </div>
                <button type="button" onclick="closeConfirmDeleteCompanyModal()" style="border: none; background: transparent; color: #991b1b; font-size: 18px; cursor: pointer;"><i class="ph ph-x"></i></button>
            </div>
            <form id="deleteCompanyForm" action="" method="POST" style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                @csrf
                @method('DELETE')
                <p style="margin: 0; font-size: 13px; color: #334155; line-height: 1.5;">
                    Are you sure you want to permanently delete <strong id="deleteCompanyTargetName" style="color: #0f172a;"></strong>?
                </p>
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 10px 12px; font-size: 11.5px; color: #991b1b; line-height: 1.4;">
                    <i class="ph ph-warning" style="margin-right: 4px;"></i>
                    This action cannot be undone. All linked branches, compliance documents, logo files, and employee access pivots will be permanently deleted.
                </div>
                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; margin-top: 6px;">
                    <button type="button" onclick="closeConfirmDeleteCompanyModal()" style="border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 12.5px; font-weight: 600; padding: 7px 16px; border-radius: 8px; cursor: pointer;">Cancel</button>
                    <button type="submit" style="border: none; background: #dc2626; color: #ffffff; font-size: 12.5px; font-weight: 600; padding: 7px 18px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="ph ph-trash"></i> Delete Company
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 6. Modal: Confirm Delete Branch -->
    <div id="modalConfirmDeleteBranch" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: none; z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #ffffff; border-radius: 14px; width: 100%; max-width: 460px; box-shadow: 0 20px 40px rgba(0,0,0,0.25); overflow: hidden; border: 1px solid #fee2e2;">
            <div style="padding: 16px 20px; border-bottom: 1px solid #fee2e2; background: #fff5f5; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px; font-size: 15px; font-weight: 700; color: #991b1b;">
                    <i class="ph ph-trash" style="color: #dc2626; font-size: 20px;"></i>
                    <span>Delete Operational Branch</span>
                </div>
                <button type="button" onclick="closeConfirmDeleteBranchModal()" style="border: none; background: transparent; color: #991b1b; font-size: 18px; cursor: pointer;"><i class="ph ph-x"></i></button>
            </div>
            <form id="deleteBranchForm" action="" method="POST" style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                @csrf
                @method('DELETE')
                <p style="margin: 0; font-size: 13px; color: #334155; line-height: 1.5;">
                    Are you sure you want to delete branch <strong id="deleteBranchTargetName" style="color: #0f172a;"></strong>?
                </p>
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 10px 12px; font-size: 11.5px; color: #991b1b; line-height: 1.4;">
                    <i class="ph ph-warning" style="margin-right: 4px;"></i>
                    This branch record and related user assignments will be permanently removed.
                </div>
                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; margin-top: 6px;">
                    <button type="button" onclick="closeConfirmDeleteBranchModal()" style="border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 12.5px; font-weight: 600; padding: 7px 16px; border-radius: 8px; cursor: pointer;">Cancel</button>
                    <button type="submit" style="border: none; background: #dc2626; color: #ffffff; font-size: 12.5px; font-weight: 600; padding: 7px 18px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="ph ph-trash"></i> Delete Branch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('hr.people.partials.members-modal')
@endsection
