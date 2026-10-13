@extends('layouts.app')

@section('title', 'Companies & Branches - Business Settings')

@section('content')
<div class="biz-settings-wrapper" id="companiesBranchesApp">

<style>
/* Modular Table Panel - Consistent Row Heights & Pill Ergonomics */
#companiesDirectoryTable {
    table-layout: auto;
    border-collapse: collapse;
    width: 100%;
}
#companiesDirectoryTable tbody tr {
    height: 58px;
    max-height: 58px;
    transition: background 0.15s;
    box-sizing: border-box;
}
#companiesDirectoryTable tbody td {
    height: 58px;
    max-height: 58px;
    padding: 6px 12px;
    vertical-align: middle;
    box-sizing: border-box;
}
.branch-pills-wrap {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-wrap: wrap;
    max-height: 46px;
    overflow: hidden;
    line-height: 1.2;
}
.branch-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #e2e8f0;
    max-width: 125px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    box-sizing: border-box;
}
.branch-pill-more {
    display: inline-flex;
    align-items: center;
    font-size: 10.5px;
    font-weight: 700;
    color: #2563eb;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    padding: 2px 6px;
    border-radius: 6px;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s;
}
.branch-pill-more:hover {
    background: #dbeafe;
    border-color: #93c5fd;
}
</style>

    <!-- Top View Switcher & Module Header -->
    <div class="biz-top-bar" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);">
                <i class="ph ph-buildings"></i>
            </div>
            <div>
                <h1 style="font-size: 1.35rem; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.2;">Companies &amp; Branches</h1>
                <p style="font-size: 0.8rem; color: #64748b; margin: 2px 0 0;">Enterprise Multi-Entity Directory, Branch Management &amp; Legal Tax Registrations</p>
            </div>
        </div>

        <!-- Mode Switcher Pills: [Directory] / [Company Details] -->
        <div class="view-mode-pill-group" style="display: inline-flex; background: #f1f5f9; padding: 3px; border-radius: 10px; border: 1px solid #e2e8f0;">
            <button type="button" class="view-mode-pill {{ $currentView === 'directory' ? 'active' : '' }}" onclick="switchViewMode('directory')" style="border: none; background: {{ $currentView === 'directory' ? '#ffffff' : 'transparent' }}; color: {{ $currentView === 'directory' ? '#0f172a' : '#64748b' }}; font-weight: 600; font-size: 12.5px; padding: 6px 14px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: {{ $currentView === 'directory' ? '0 1px 3px rgba(0,0,0,0.08)' : 'none' }}; transition: all 0.2s;">
                <i class="ph ph-table"></i>
                <span>Companies Directory</span>
            </button>
            <button type="button" class="view-mode-pill {{ $currentView === 'details' ? 'active' : '' }}" onclick="switchViewMode('details')" style="border: none; background: {{ $currentView === 'details' ? '#ffffff' : 'transparent' }}; color: {{ $currentView === 'details' ? '#0f172a' : '#64748b' }}; font-weight: 600; font-size: 12.5px; padding: 6px 14px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: {{ $currentView === 'details' ? '0 1px 3px rgba(0,0,0,0.08)' : 'none' }}; transition: all 0.2s;">
                <i class="ph ph-identification-card"></i>
                <span>Company Details {{ $selectedCompany ? '(' . ($selectedCompany->company_trade_name ?: $selectedCompany->company_code) . ')' : '' }}</span>
            </button>
        </div>
    </div>

    <!-- ====================================================================
         VIEW 1: COMPANIES & BRANCHES MODULAR DIRECTORY (Modular Table RFQ Pattern)
         ==================================================================== -->
    <div id="sectionViewDirectory" style="display: {{ $currentView === 'directory' ? 'block' : 'none' }};">

        <!-- KPI Radar Strip (4-Pillar Metric Cluster) -->
        <div class="biz-kpi-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 20px;">
            <div class="biz-kpi-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04); display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(59, 130, 246, 0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="ph ph-buildings"></i>
                </div>
                <div>
                    <span style="font-size: 0.72rem; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.04em;">Total Companies</span>
                    <div style="font-size: 1.45rem; font-weight: 700; color: #0f172a;">{{ $metrics['total_companies'] }}</div>
                    <span style="font-size: 0.7rem; color: #10b981; font-weight: 600;"><i class="ph ph-check-circle"></i> {{ $metrics['active_companies'] }} Active Entities</span>
                </div>
            </div>

            <div class="biz-kpi-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04); display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); color: #059669; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="ph ph-storefront"></i>
                </div>
                <div>
                    <span style="font-size: 0.72rem; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.04em;">Connected Branches</span>
                    <div style="font-size: 1.45rem; font-weight: 700; color: #0f172a;">{{ $metrics['total_branches'] }}</div>
                    <span style="font-size: 0.7rem; color: #059669; font-weight: 600;"><i class="ph ph-check-circle"></i> {{ $metrics['active_branches'] }} Operational Sites</span>
                </div>
            </div>

            <div class="biz-kpi-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04); display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(139, 92, 246, 0.1); color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="ph ph-receipt"></i>
                </div>
                <div>
                    <span style="font-size: 0.72rem; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.04em;">VAT Registered</span>
                    <div style="font-size: 1.45rem; font-weight: 700; color: #0f172a;">{{ $metrics['vat_registered'] }}</div>
                    <span style="font-size: 0.7rem; color: #64748b; font-weight: 500;">BIR Compliant Entities</span>
                </div>
            </div>

            <div class="biz-kpi-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04); display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(245, 158, 11, 0.1); color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="ph ph-tree-structure"></i>
                </div>
                <div>
                    <span style="font-size: 0.72rem; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.04em;">Enterprise Groups</span>
                    <div style="font-size: 1.45rem; font-weight: 700; color: #0f172a;">{{ $metrics['holding_groups'] }}</div>
                    <span style="font-size: 0.7rem; color: #64748b; font-weight: 500;">Parent / Holding Networks</span>
                </div>
            </div>
        </div>

        <!-- Modular Table Master Panel -->
        <div class="modular-table-panel" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 3px 12px rgba(15, 23, 42, 0.05); overflow: hidden;">
            
            <!-- Modular Table Toolbar (RFQ Style) -->
            <div class="modular-toolbar" style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <!-- Left: Live Search & Active Filter Chips -->
                <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 260px; max-width: 520px;">
                    <div style="position: relative; width: 100%;">
                        <i class="ph ph-magnifying-glass" style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px;"></i>
                        <input type="text" id="companiesDirectorySearch" oninput="handleCompaniesDirectorySearch(this.value)" placeholder="Search company code, name, branch, TIN, city, contact..." style="width: 100%; height: 34px; padding: 6px 12px 6px 32px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; outline: none; background: #f8fafc; transition: border-color 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.background='#ffffff';" onblur="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                    </div>
                    <div id="companiesActiveFilterChips" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;"></div>
                </div>

                <!-- Right: Column Customizer Dropdown Trigger & Create Actions -->
                <div style="display: flex; align-items: center; gap: 8px; position: relative;">
                    <!-- Column Customizer Dropdown Trigger -->
                    <div style="position: relative;">
                        <button type="button" class="btn-table-control" id="btnCompaniesColFilter" onclick="toggleCompaniesTableSettingsDropdown(event)" style="height: 34px; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff; color: #334155; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                            <i class="ph ph-columns" style="font-size: 14px; color: #2563eb;"></i>
                            <span>Columns</span>
                            <i class="ph ph-caret-down" style="font-size: 10px; color: #64748b;"></i>
                        </button>
                    </div>

                    <button type="button" class="btn-table-action" onclick="openCreateBranchModal({{ $selectedCompany ? $selectedCompany->company_id : 'null' }})" style="height: 34px; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc; color: #1e293b; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                        <i class="ph ph-plus" style="font-size: 13px;"></i>
                        <span>Add Branch</span>
                    </button>

                    <button type="button" class="btn-table-primary" onclick="openCreateCompanyModal()" style="height: 34px; padding: 6px 14px; border: none; border-radius: 8px; background: #2563eb; color: #ffffff; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25); transition: all 0.2s;">
                        <i class="ph ph-plus-circle" style="font-size: 15px;"></i>
                        <span>New Company</span>
                    </button>
                </div>
            </div>

            <!-- Floating Settings Dropdown Menu (Exact RFQ Pattern) -->
            <div id="companiesTableSettingsDropdown" class="modular-settings-dropdown" style="display: none; position: absolute; right: 20px; z-index: 999; width: 330px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15); padding: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 700; color: #0f172a;">
                        <i class="ph ph-gear-six" style="color: #2563eb;"></i>
                        <span>Table Columns &amp; Sequence</span>
                    </div>
                    <span id="companiesColActiveCounter" style="font-size: 11px; background: #eff6ff; color: #1d4ed8; padding: 2px 7px; border-radius: 999px; font-weight: 600;">8 of 12 visible</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-size: 11px;">
                    <button type="button" onclick="resetDirectoryColumnDefaults()" style="border: none; background: none; color: #2563eb; font-weight: 600; cursor: pointer; padding: 0;">Reset Default</button>
                    <span style="color: #cbd5e1;">&bull;</span>
                    <button type="button" onclick="showAllDirectoryColumns()" style="border: none; background: none; color: #2563eb; font-weight: 600; cursor: pointer; padding: 0;">Select All</button>
                    <span style="color: #cbd5e1;">&bull;</span>
                    <button type="button" onclick="resetDirectoryColumnWidths()" style="border: none; background: none; color: #2563eb; font-weight: 600; cursor: pointer; padding: 0;">Reset Widths</button>
                </div>
                <input type="text" id="directoryColSearchInput" placeholder="Filter columns..." oninput="handleDirectoryColSearch(this.value)" style="width: 100%; height: 28px; padding: 4px 8px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 11.5px; margin-bottom: 8px; outline: none;">
                
                <div id="directoryColCheckboxesList" style="max-height: 250px; overflow-y: auto; display: flex; flex-direction: column; gap: 4px;">
                    <!-- Populated dynamically by JS with draggable column order -->
                </div>
            </div>

            <!-- Responsive Table Container -->
            <div class="modular-table-responsive" style="overflow-x: auto; width: 100%; position: relative;">
                <table class="modular-table" id="companiesDirectoryTable" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 12.5px;">
                    <thead id="companiesDirectoryThead" style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; user-select: none;">
                        <tr id="companiesDirectoryTheadRow">
                            <!-- Populated dynamically with stacked headers, resizers & drag-and-drop -->
                        </tr>
                    </thead>
                    <tbody id="companiesDirectoryTbody">
                        <!-- Populated dynamically by renderCompaniesDirectory() -->
                    </tbody>
                </table>
            </div>

            <!-- Client-Side Pagination Bar -->
            <div id="companiesDirectoryPaginationBar" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; border-top: 1px solid #f1f5f9; background: #f8fafc; flex-wrap: wrap; gap: 10px;">
                <div id="companiesPaginationInfo" style="font-size: 12px; color: #64748b;">
                    Showing <strong>1</strong> to <strong>10</strong> of <strong>{{ $companies->count() }}</strong> companies
                </div>
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b;">
                        <span>Show</span>
                        <select id="companiesPageSizeSelect" onchange="changeDirectoryPageSize(this.value)" style="padding: 3px 6px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 11.5px;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span>rows</span>
                    </div>
                    <div id="companiesPaginationControls" style="display: flex; align-items: center; gap: 4px;">
                        <!-- Rendered by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====================================================================
         VIEW 2: REDESIGNED COMPANY DETAILS PAGE (/modern-ux-designer)
         (Goodfarms Reference Redesign: Header banner + General Info & Branches tabs)
         ==================================================================== -->
    <div id="sectionViewDetails" style="display: {{ $currentView === 'details' ? 'block' : 'none' }};">
        @if($selectedCompany)
            <div class="company-details-container" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.06); padding: 24px; position: relative;">
                
                <!-- Back Link & Action Bar -->
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
                    <button type="button" onclick="switchViewMode('directory')" style="border: none; background: #f1f5f9; color: #334155; font-size: 12.5px; font-weight: 600; padding: 6px 14px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background 0.2s;">
                        <i class="ph ph-arrow-left"></i>
                        <span>Back to Companies Directory</span>
                    </button>

                    <div style="display: flex; align-items: center; gap: 10px;">
                        <!-- Delete Company CTA -->
                        <button type="button" onclick="confirmDeleteCompany({{ $selectedCompany->company_id }}, '{{ addslashes($selectedCompany->company_legal_name) }}')" style="border: 1px solid #fecaca; background: #fff5f5; color: #dc2626; font-size: 12px; font-weight: 600; padding: 7px 14px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s;" title="Delete this company and all associated records">
                            <i class="ph ph-trash"></i>
                            <span>Delete Company</span>
                        </button>

                        <!-- Context Switcher CTA -->
                        <form action="{{ route('context.switch') }}" method="POST" style="margin: 0;">
                            @csrf
                            <input type="hidden" name="company_id" value="{{ $selectedCompany->company_id }}">
                            <input type="hidden" name="is_consolidated" value="0">
                            <button type="submit" class="btn-action-context" style="border: 1px solid #cbd5e1; background: #ffffff; color: #1e293b; font-size: 12px; font-weight: 600; padding: 7px 14px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
                                <i class="ph ph-arrows-clockwise" style="color: #2563eb;"></i>
                                <span>Switch Workspace Context</span>
                            </button>
                        </form>

                        <!-- Save Profile Form Submit -->
                        <button type="submit" form="companyDetailsUpdateForm" style="border: none; background: #2563eb; color: #ffffff; font-size: 12.5px; font-weight: 600; padding: 7px 18px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25); transition: background 0.2s;">
                            <i class="ph ph-floppy-disk"></i>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </div>

                <!-- Form Wrapping Updates -->
                <form id="companyDetailsUpdateForm" action="{{ route('config.companies-branches.update', $selectedCompany->company_id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Hero Identity Header Card (Redesign of Reference Screenshot) -->
                    <div class="company-hero-banner" style="display: flex; align-items: flex-start; gap: 20px; padding: 20px; background: linear-gradient(135deg, rgba(248, 250, 252, 0.8), rgba(241, 245, 249, 0.4)); border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 24px;">
                        
                        <!-- Company Logo Square Dropzone -->
                        <div class="company-logo-dropzone" style="position: relative; width: 88px; height: 88px; border-radius: 12px; border: 2px dashed #cbd5e1; background: #ffffff; display: flex; align-items: center; justify-content: center; cursor: pointer; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); flex-shrink: 0;" onclick="document.getElementById('companyLogoFileInput').click()" title="Click to upload/change company logo">
                            @if($selectedCompany->company_logo)
                                <img id="companyLogoPreviewImg" src="{{ asset('storage/' . $selectedCompany->company_logo) }}" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                            @else
                                <div id="companyLogoPlaceholder" style="display: flex; flex-direction: column; align-items: center; color: #94a3b8; font-size: 11px; text-align: center; padding: 4px;">
                                    <i class="ph ph-camera" style="font-size: 26px; color: #64748b; margin-bottom: 2px;"></i>
                                    <span style="font-weight: 600;">Your logo</span>
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
                            <span style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em; display: block; margin-bottom: 4px;">Company Name</span>
                            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                <input type="text" name="company_legal_name" value="{{ old('company_legal_name', $selectedCompany->company_legal_name) }}" required placeholder="Legal Entity Name (e.g. GOODFARMS TRADING CORPORATION)" style="font-size: clamp(1.3rem, 2vw, 1.7rem); font-weight: 800; color: #0f172a; border: 1px solid transparent; background: transparent; padding: 2px 6px; border-radius: 8px; width: 100%; max-width: 650px; outline: none; transition: all 0.2s;" onfocus="this.style.background='#ffffff'; this.style.borderColor='#cbd5e1';" onblur="this.style.background='transparent'; this.style.borderColor='transparent';">
                            </div>

                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 8px;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">Trade Name:</span>
                                    <input type="text" name="company_trade_name" value="{{ old('company_trade_name', $selectedCompany->company_trade_name) }}" placeholder="Trade / Brand Name" style="font-size: 12px; font-weight: 600; color: #1e293b; padding: 3px 8px; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff;">
                                </div>

                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">Code:</span>
                                    <input type="text" name="company_code" value="{{ old('company_code', $selectedCompany->company_code) }}" required style="font-size: 12px; font-weight: 700; color: #1e293b; padding: 3px 8px; border: 1px solid #cbd5e1; border-radius: 6px; background: #ffffff; width: 85px; text-transform: uppercase;">
                                </div>

                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; background: {{ $selectedCompany->company_is_active ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)' }}; color: {{ $selectedCompany->company_is_active ? '#059669' : '#dc2626' }};">
                                    <i class="ph {{ $selectedCompany->company_is_active ? 'ph-check-circle' : 'ph-x-circle' }}"></i>
                                    {{ $selectedCompany->company_is_active ? 'Active Entity' : 'Inactive' }}
                                </span>

                                <!-- Brand Color Indicator Dot -->
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="width: 14px; height: 14px; border-radius: 50%; background-color: {{ $selectedCompany->company_brand_color ?: '#ef4444' }}; display: inline-block; box-shadow: 0 0 0 2px #ffffff, 0 0 0 3px {{ $selectedCompany->company_brand_color ?: '#ef4444' }};" id="headerBrandColorDot"></span>
                                    <span style="font-size: 11.5px; color: #64748b;">Brand Color</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Functional Tabs (Matching Reference Image: General Information & Branches) -->
                    <div class="company-tabs-nav" style="display: flex; align-items: center; gap: 8px; border-bottom: 2px solid #e2e8f0; margin-bottom: 24px;">
                        <button type="button" class="company-tab-btn active" id="tabBtnGeneral" onclick="switchCompanySubTab('general')" style="border: none; background: transparent; padding: 10px 18px; font-size: 13.5px; font-weight: 700; color: #2563eb; cursor: pointer; border-bottom: 3px solid #2563eb; margin-bottom: -2px; display: flex; align-items: center; gap: 8px;">
                            <i class="ph ph-buildings"></i>
                            <span>General Information</span>
                        </button>
                        <button type="button" class="company-tab-btn" id="tabBtnBranches" onclick="switchCompanySubTab('branches')" style="border: none; background: transparent; padding: 10px 18px; font-size: 13.5px; font-weight: 600; color: #64748b; cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -2px; display: flex; align-items: center; gap: 8px;">
                            <i class="ph ph-storefront"></i>
                            <span>Branches</span>
                            <span style="background: #e2e8f0; color: #334155; font-size: 11px; padding: 2px 7px; border-radius: 999px; font-weight: 700;">{{ $selectedCompany->branches->count() }}</span>
                        </button>
                        <button type="button" class="company-tab-btn" id="tabBtnVault" onclick="switchCompanySubTab('vault')" style="border: none; background: transparent; padding: 10px 18px; font-size: 13.5px; font-weight: 600; color: #64748b; cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -2px; display: flex; align-items: center; gap: 8px;">
                            <i class="ph ph-file-lock"></i>
                            <span>Certificate &amp; Legal Hub</span>
                            @if($vaultStats['expiring_soon'] > 0 || $vaultStats['expired'] > 0)
                                <span style="background: #fee2e2; color: #dc2626; font-size: 10.5px; padding: 2px 6px; border-radius: 999px; font-weight: 700;">{{ $vaultStats['expiring_soon'] + $vaultStats['expired'] }}</span>
                            @endif
                        </button>
                        <button type="button" class="company-tab-btn" id="tabBtnHierarchy" onclick="switchCompanySubTab('hierarchy')" style="border: none; background: transparent; padding: 10px 18px; font-size: 13.5px; font-weight: 600; color: #64748b; cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -2px; display: flex; align-items: center; gap: 8px;">
                            <i class="ph ph-tree-structure"></i>
                            <span>Hierarchy &amp; Relationships</span>
                        </button>
                    </div>

                    <!-- ==========================================================
                         SUB-TAB 1: GENERAL INFORMATION (2-Column Industrial Grid)
                         (Matches fields in Reference Image: Address, TIN, Branch Code, RDO, VAT, Currency, External Code | Phone, Email, Website, Email Domain, Color)
                         ========================================================== -->
                    <div id="companyTabPaneGeneral" class="company-subtab-pane" style="display: block;">
                        <div class="general-info-2col" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 28px;">
                            
                            <!-- Left Column: Legal, Tax & Physical Address -->
                            <div class="col-section" style="display: flex; flex-direction: column; gap: 16px;">
                                
                                <!-- Address Block -->
                                <div class="form-row-block" style="display: flex; flex-direction: column; gap: 6px;">
                                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 6px;">
                                        <span>Address</span>
                                    </label>
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <input type="text" name="company_address_line_1" value="{{ old('company_address_line_1', $selectedCompany->company_address_line_1) }}" placeholder="Street / Purok (e.g. Purok-5)" style="height: 34px; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                                        <input type="text" name="company_address_line_2" value="{{ old('company_address_line_2', $selectedCompany->company_address_line_2) }}" placeholder="Barangay / Camp (e.g. Camp 1)" style="height: 34px; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                                        <div style="display: grid; grid-template-columns: 2fr 2fr 1fr; gap: 6px;">
                                            <input type="text" name="company_city" value="{{ old('company_city', $selectedCompany->company_city) }}" placeholder="City / Municipality (e.g. Maramag)" style="height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                                            <input type="text" name="company_state_province_code" value="{{ old('company_state_province_code', $selectedCompany->company_state_province_code) }}" placeholder="Province (e.g. Bukidnon (PH))" style="height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                                            <input type="text" name="company_postal_code" value="{{ old('company_postal_code', $selectedCompany->company_postal_code) }}" placeholder="ZIP (8712)" style="height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                                        </div>
                                        <input type="text" name="company_country_code" value="{{ old('company_country_code', $selectedCompany->company_country_code ?: 'PH') }}" placeholder="Country (e.g. Philippines)" style="height: 34px; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                                    </div>
                                </div>

                                <!-- TIN (Taxpayer Identification Number) with Tooltip -->
                                <div class="form-row" style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; min-width: 140px; display: flex; align-items: center; gap: 4px;">
                                        <span>TIN</span>
                                        <span title="Taxpayer Identification Number (9 digits + 3 to 5 digit branch suffix)" style="color: #64748b; cursor: help; font-size: 13px;"><i class="ph ph-question"></i></span>
                                    </label>
                                    <div style="flex: 1; position: relative;">
                                        <input type="text" name="company_tax_id" value="{{ old('company_tax_id', $selectedCompany->company_tax_id) }}" placeholder="412-277-750-00000" style="width: 100%; height: 34px; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; font-family: monospace;">
                                    </div>
                                </div>

                                <!-- Company Branch Code -->
                                <div class="form-row" style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; min-width: 140px;">
                                        <span>Company Branch Code</span>
                                    </label>
                                    <input type="text" name="company_branch_code" value="{{ old('company_branch_code', $selectedCompany->company_branch_code ?: '00000') }}" placeholder="00000" style="flex: 1; height: 34px; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; font-family: monospace;">
                                </div>

                                <!-- RDO with Tooltip -->
                                <div class="form-row" style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; min-width: 140px; display: flex; align-items: center; gap: 4px;">
                                        <span>RDO</span>
                                        <span title="Revenue District Office code (e.g. 098 or 047)" style="color: #64748b; cursor: help; font-size: 13px;"><i class="ph ph-question"></i></span>
                                    </label>
                                    <input type="text" name="company_rdo_code" value="{{ old('company_rdo_code', $selectedCompany->company_rdo_code) }}" placeholder="e.g. 098" style="flex: 1; height: 34px; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                                </div>

                                <!-- VAT Registered Toggle Checkbox -->
                                <div class="form-row" style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; min-width: 140px; display: flex; align-items: center; gap: 4px;">
                                        <span>VAT Registered</span>
                                        <span title="Check if entity is registered for Value Added Tax with the BIR" style="color: #64748b; cursor: help; font-size: 13px;"><i class="ph ph-question"></i></span>
                                    </label>
                                    <div style="flex: 1; display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" name="company_is_vat_registered" id="vatCheckbox" value="1" {{ ($selectedCompany->company_is_vat_registered ?? ($selectedCompany->company_tax_type === 'VAT Registered')) ? 'checked' : '' }} style="width: 18px; height: 18px; cursor: pointer; accent-color: #2563eb;">
                                        <label for="vatCheckbox" style="font-size: 12px; color: #475569; cursor: pointer;">Yes, entity is VAT Registered</label>
                                    </div>
                                </div>

                                <!-- Currency -->
                                <div class="form-row" style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; min-width: 140px;">
                                        <span>Currency</span>
                                    </label>
                                    <select name="company_base_currency_code" style="flex: 1; height: 34px; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; background: #ffffff;">
                                        @foreach($currencies as $curr)
                                            <option value="{{ $curr }}" {{ $selectedCompany->company_base_currency_code === $curr ? 'selected' : '' }}>
                                                {{ $curr }} - {{ $curr === 'PHP' ? 'Philippine Peso (PHP)' : ($curr === 'USD' ? 'US Dollar (USD)' : $curr) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- External Code with Tooltip -->
                                <div class="form-row" style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; min-width: 140px; display: flex; align-items: center; gap: 4px;">
                                        <span>External Code</span>
                                        <span title="Code used in work entry exports and external HR/payroll integrations" style="color: #64748b; cursor: help; font-size: 13px;"><i class="ph ph-question"></i></span>
                                    </label>
                                    <input type="text" name="company_external_code" value="{{ old('company_external_code', $selectedCompany->company_external_code) }}" placeholder="Code used in work entry exports" style="flex: 1; height: 34px; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                                </div>
                            </div>

                            <!-- Right Column: Phone, Email, Website, Email Domain & Brand Color -->
                            <div class="col-section" style="display: flex; flex-direction: column; gap: 16px;">
                                
                                <!-- Phone -->
                                <div class="form-row" style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; min-width: 140px;">
                                        <span>Phone</span>
                                    </label>
                                    <div style="flex: 1; position: relative;">
                                        <i class="ph ph-phone" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                                        <input type="text" name="company_phone_number" value="{{ old('company_phone_number', $selectedCompany->company_phone_number) }}" placeholder="+63 2 8000 0000" style="width: 100%; height: 34px; padding: 6px 12px 6px 32px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="form-row" style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; min-width: 140px;">
                                        <span>Email</span>
                                    </label>
                                    <div style="flex: 1; position: relative;">
                                        <i class="ph ph-envelope" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                                        <input type="email" name="company_email_address" value="{{ old('company_email_address', $selectedCompany->company_email_address) }}" placeholder="info@goodfarms.com" style="width: 100%; height: 34px; padding: 6px 12px 6px 32px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                                    </div>
                                </div>

                                <!-- Website -->
                                <div class="form-row" style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; min-width: 140px;">
                                        <span>Website</span>
                                    </label>
                                    <div style="flex: 1; position: relative;">
                                        <i class="ph ph-globe" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                                        <input type="text" name="company_website_url" value="{{ old('company_website_url', $selectedCompany->company_website_url) }}" placeholder="e.g. https://www.odoo.com" style="width: 100%; height: 34px; padding: 6px 12px 6px 32px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                                    </div>
                                </div>

                                <!-- Email Domain -->
                                <div class="form-row" style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; min-width: 140px;">
                                        <span>Email Domain</span>
                                    </label>
                                    <div style="flex: 1; position: relative;">
                                        <i class="ph ph-at" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                                        <input type="text" name="company_email_domain" value="{{ old('company_email_domain', $selectedCompany->company_email_domain) }}" placeholder="goodfarms.odoo.com" style="width: 100%; height: 34px; padding: 6px 12px 6px 32px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                                    </div>
                                </div>

                                <!-- Color (Brand Dot Swatch & Color Picker) -->
                                <div class="form-row" style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; min-width: 140px;">
                                        <span>Color</span>
                                    </label>
                                    <div style="flex: 1; display: flex; align-items: center; gap: 10px;">
                                        <div style="position: relative; display: flex; align-items: center;">
                                            <input type="color" name="company_brand_color" id="companyBrandColorPicker" value="{{ old('company_brand_color', $selectedCompany->company_brand_color ?: '#ef4444') }}" onchange="handleBrandColorChange(this.value)" style="width: 34px; height: 34px; border: none; border-radius: 50%; cursor: pointer; padding: 0; background: transparent;">
                                        </div>
                                        <span id="brandColorHexLabel" style="font-size: 12px; font-family: monospace; font-weight: 600; color: #334155;">{{ $selectedCompany->company_brand_color ?: '#ef4444' }}</span>

                                        <!-- Quick Color Preset Swatches -->
                                        <div style="display: flex; align-items: center; gap: 6px; margin-left: auto;">
                                            @foreach(['#ef4444', '#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#0f172a'] as $presetColor)
                                                <button type="button" onclick="selectBrandColorPreset('{{ $presetColor }}')" style="width: 18px; height: 18px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.15); background-color: {{ $presetColor }}; cursor: pointer; padding: 0; transition: transform 0.15s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="{{ $presetColor }}"></button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- Timezone & Fiscal Year Info -->
                                <div class="form-row" style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; min-width: 140px;">
                                        <span>Timezone</span>
                                    </label>
                                    <select name="company_timezone" style="flex: 1; height: 34px; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; background: #ffffff;">
                                        @foreach($timezones as $tz)
                                            <option value="{{ $tz }}" {{ $selectedCompany->company_timezone === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-row" style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; min-width: 140px;">
                                        <span>Contact Person</span>
                                    </label>
                                    <input type="text" name="company_contact_person" value="{{ old('company_contact_person', $selectedCompany->company_contact_person) }}" placeholder="Designated Administrator" style="flex: 1; height: 34px; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==========================================================
                         SUB-TAB 2: BRANCHES (Operational Outlets under this Company)
                         ========================================================== -->
                    <div id="companyTabPaneBranches" class="company-subtab-pane" style="display: none;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                            <div>
                                <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0;">Branches of {{ $selectedCompany->company_trade_name ?: $selectedCompany->company_legal_name }}</h3>
                                <p style="font-size: 0.8rem; color: #64748b; margin: 2px 0 0;">Manage retail stores, commissaries, warehouses, and takeout stations linked to this legal entity.</p>
                            </div>
                            <button type="button" onclick="openCreateBranchModal({{ $selectedCompany->company_id }})" style="border: none; background: #2563eb; color: #ffffff; font-size: 12.5px; font-weight: 600; padding: 7px 16px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);">
                                <i class="ph ph-plus-circle" style="font-size: 15px;"></i>
                                <span>Add New Branch</span>
                            </button>
                        </div>

                        <!-- Branches Grid Cards -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px;">
                            @forelse($selectedCompany->branches as $branch)
                                <div class="branch-item-card" style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between; transition: all 0.2s;">
                                    <div>
                                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                                            <div>
                                                <h4 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0;">{{ $branch->branch_name }}</h4>
                                                <span style="font-size: 11px; font-family: monospace; color: #2563eb; font-weight: 700;">{{ $branch->branch_code }}</span>
                                            </div>
                                            <span style="font-size: 11px; padding: 2px 8px; border-radius: 999px; font-weight: 600; background: {{ $branch->branch_is_active ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)' }}; color: {{ $branch->branch_is_active ? '#059669' : '#dc2626' }};">
                                                {{ $branch->branch_is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>

                                        <div style="display: flex; flex-direction: column; gap: 6px; font-size: 12px; color: #475569; margin-bottom: 14px;">
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <i class="ph ph-tag" style="color: #64748b;"></i>
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
                                            <button type="button" onclick="editBranch({{ $branch->toJson() }})" style="border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 11.5px; font-weight: 600; padding: 4px 10px; border-radius: 6px; cursor: pointer;">
                                                <i class="ph ph-pencil-simple"></i> Edit Branch
                                            </button>

                                            <button type="button" onclick="confirmDeleteBranch({{ $branch->branch_id }}, '{{ addslashes($branch->branch_name) }}')" style="border: 1px solid #fecaca; background: #fff5f5; color: #dc2626; font-size: 11.5px; font-weight: 600; padding: 4px 8px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;" title="Delete Branch">
                                                <i class="ph ph-trash"></i> Delete
                                            </button>
                                        </div>

                                        <form action="{{ route('config.business-settings.branches.toggle', $branch->branch_id) }}" method="POST" style="margin: 0;">
                                            @csrf
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
                                    <button type="button" onclick="openCreateBranchModal({{ $selectedCompany->company_id }})" style="border: none; background: #2563eb; color: #ffffff; font-size: 12px; font-weight: 600; padding: 6px 14px; border-radius: 8px; cursor: pointer;">
                                        <i class="ph ph-plus"></i> Add First Branch
                                    </button>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- ==========================================================
                         SUB-TAB 3: CERTIFICATE & LEGAL HUB (Document Vault)
                         ========================================================== -->
                    <div id="companyTabPaneVault" class="company-subtab-pane" style="display: none;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                            <div>
                                <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0;">Compliance &amp; Document Vault</h3>
                                <p style="font-size: 0.8rem; color: #64748b; margin: 2px 0 0;">COR, Mayor's Permit, Fire Clearances, and Statutory Certificates for {{ $selectedCompany->company_legal_name }}</p>
                            </div>
                            <button type="button" onclick="openDocumentModal()" style="border: none; background: #2563eb; color: #ffffff; font-size: 12.5px; font-weight: 600; padding: 7px 16px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
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
                        <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 10px;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: left;">
                                <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                    <tr>
                                        <th style="padding: 10px 14px;">Document Name</th>
                                        <th style="padding: 10px 14px;">Category</th>
                                        <th style="padding: 10px 14px;">Authority</th>
                                        <th style="padding: 10px 14px;">Expiration</th>
                                        <th style="padding: 10px 14px;">Status</th>
                                        <th style="padding: 10px 14px; text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($selectedCompany->legalDocuments as $doc)
                                        <tr style="border-bottom: 1px solid #f1f5f9;">
                                            <td style="padding: 10px 14px; font-weight: 600; color: #0f172a;">{{ $doc->document_name }}</td>
                                            <td style="padding: 10px 14px; color: #475569;">{{ $doc->document_category }}</td>
                                            <td style="padding: 10px 14px; color: #475569;">{{ $doc->issuing_authority ?: 'N/A' }}</td>
                                            <td style="padding: 10px 14px; color: #475569;">{{ $doc->expiration_date ? \Carbon\Carbon::parse($doc->expiration_date)->format('M d, Y') : 'Perpetual' }}</td>
                                            <td style="padding: 10px 14px;">
                                                <span style="font-size: 11px; padding: 2px 7px; border-radius: 999px; font-weight: 600; background: {{ $doc->expiration_status === 'Active' ? '#ecfdf5' : ($doc->expiration_status === 'Expiring Soon' ? '#fef3c7' : '#fee2e2') }}; color: {{ $doc->expiration_status === 'Active' ? '#059669' : ($doc->expiration_status === 'Expiring Soon' ? '#d97706' : '#dc2626') }};">
                                                    {{ $doc->expiration_status }}
                                                </span>
                                            </td>
                                            <td style="padding: 10px 14px; text-align: right;">
                                                @if($doc->file_path)
                                                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" style="color: #2563eb; text-decoration: none; margin-right: 8px;"><i class="ph ph-arrow-square-out"></i> View</a>
                                                @endif
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

                    <!-- ==========================================================
                         SUB-TAB 4: HIERARCHY & RELATIONSHIPS
                         ========================================================== -->
                    <div id="companyTabPaneHierarchy" class="company-subtab-pane" style="display: none;">
                        <div style="margin-bottom: 16px;">
                            <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0;">Enterprise Structure &amp; Master Data Sharing</h3>
                            <p style="font-size: 0.8rem; color: #64748b; margin: 2px 0 0;">Define corporate holding relationships and cross-subsidiary catalog sharing rules.</p>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
                            <div style="display: flex; flex-direction: column; gap: 14px;">
                                <div>
                                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 4px;">Entity Classification</label>
                                    <select name="company_type" style="width: 100%; height: 34px; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; background: #ffffff;">
                                        <option value="Company" {{ $selectedCompany->company_type === 'Company' ? 'selected' : '' }}>Company (Standard Operating Entity)</option>
                                        <option value="Holding Enterprise" {{ $selectedCompany->company_type === 'Holding Enterprise' ? 'selected' : '' }}>Holding Enterprise (Parent Group)</option>
                                        <option value="Subsidiary" {{ $selectedCompany->company_type === 'Subsidiary' ? 'selected' : '' }}>Subsidiary Operating Brand</option>
                                        <option value="Agency" {{ $selectedCompany->company_type === 'Agency' ? 'selected' : '' }}>Agency / Third-Party Partner</option>
                                    </select>
                                </div>

                                <div>
                                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 4px;">Parent Holding Company</label>
                                    <select name="parent_company_id" style="width: 100%; height: 34px; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; background: #ffffff;">
                                        <option value="">None (Top-Level Independent Entity)</option>
                                        @foreach($companies as $c)
                                            @if($c->company_id !== $selectedCompany->company_id)
                                                <option value="{{ $c->company_id }}" {{ $selectedCompany->parent_company_id === $c->company_id ? 'selected' : '' }}>{{ $c->company_legal_name }} ({{ $c->company_code }})</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        @else
            <div style="padding: 40px; text-align: center; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0;">
                <i class="ph ph-buildings" style="font-size: 40px; color: #94a3b8;"></i>
                <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 10px;">No Company Selected</h3>
                <p style="font-size: 13px; color: #64748b;">Please select a company from the directory to view its details.</p>
                <button type="button" onclick="switchViewMode('directory')" style="margin-top: 10px; border: none; background: #2563eb; color: #ffffff; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600;">Back to Directory</button>
            </div>
        @endif
    </div>

    <!-- ====================================================================
         MODALS: CREATE COMPANY & CREATE/EDIT BRANCH
         ==================================================================== -->
    
    <!-- 1. Modal: New Company -->
    <div id="modalCreateCompany" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #ffffff; border-radius: 14px; width: 100%; max-width: 580px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 700; color: #0f172a;">
                    <i class="ph ph-buildings" style="color: #2563eb; font-size: 20px;"></i>
                    <span>Register New Company Entity</span>
                </div>
                <button type="button" onclick="closeCreateCompanyModal()" style="border: none; background: transparent; color: #64748b; font-size: 18px; cursor: pointer;"><i class="ph ph-x"></i></button>
            </div>
            <form action="{{ route('config.companies-branches.store') }}" method="POST" enctype="multipart/form-data" style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                @csrf
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Company Code *</label>
                        <input type="text" name="company_code" required placeholder="e.g. GF-01" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; text-transform: uppercase;">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Legal Registered Name *</label>
                        <input type="text" name="company_legal_name" required placeholder="e.g. Goodfarms Trading Corporation" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Trade / Brand Name</label>
                        <input type="text" name="company_trade_name" placeholder="e.g. Goodfarms" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Entity Classification</label>
                        <select name="company_type" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; background: #ffffff;">
                            <option value="Company">Company (Standard)</option>
                            <option value="Holding Enterprise">Holding Enterprise</option>
                            <option value="Subsidiary">Subsidiary</option>
                            <option value="Agency">Agency</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">TIN</label>
                        <input type="text" name="company_tax_id" placeholder="412-277-750-00000" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Code</label>
                        <input type="text" name="company_branch_code" value="00000" placeholder="00000" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Base Currency</label>
                        <select name="company_base_currency_code" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; background: #ffffff;">
                            <option value="PHP">PHP - Philippine Peso</option>
                            <option value="USD">USD - US Dollar</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Brand Accent Color</label>
                        <input type="color" name="company_brand_color" value="#ef4444" style="width: 100%; height: 34px; padding: 2px; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer; background: #ffffff;">
                    </div>
                </div>

                <div style="padding-top: 10px; display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                    <button type="button" onclick="closeCreateCompanyModal()" style="border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 12.5px; font-weight: 600; padding: 7px 16px; border-radius: 8px; cursor: pointer;">Cancel</button>
                    <button type="submit" style="border: none; background: #2563eb; color: #ffffff; font-size: 12.5px; font-weight: 600; padding: 7px 20px; border-radius: 8px; cursor: pointer;">Create Company</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Modal: New Branch -->
    <div id="modalCreateBranch" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #ffffff; border-radius: 14px; width: 100%; max-width: 580px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 700; color: #0f172a;">
                    <i class="ph ph-storefront" style="color: #059669; font-size: 20px;"></i>
                    <span>Add New Operational Branch</span>
                </div>
                <button type="button" onclick="closeCreateBranchModal()" style="border: none; background: transparent; color: #64748b; font-size: 18px; cursor: pointer;"><i class="ph ph-x"></i></button>
            </div>
            <form action="{{ route('config.business-settings.branches.store') }}" method="POST" style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                @csrf
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Belongs to Company *</label>
                    <select name="company_id" id="branchModalCompanySelect" required style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; background: #ffffff;">
                        @foreach($companies as $c)
                            <option value="{{ $c->company_id }}" {{ $selectedCompany && $selectedCompany->company_id === $c->company_id ? 'selected' : '' }}>{{ $c->company_legal_name }} ({{ $c->company_code }})</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Code *</label>
                        <input type="text" name="branch_code" required placeholder="e.g. BR-01" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; text-transform: uppercase;">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Name *</label>
                        <input type="text" name="branch_name" required placeholder="e.g. Main Commissary / SM Mall Store" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Type</label>
                        <select name="branch_type" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; background: #ffffff;">
                            @foreach($branchTypes as $bt)
                                <option value="{{ $bt }}">{{ $bt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Tax Code / Suffix</label>
                        <input type="text" name="branch_tax_code" placeholder="e.g. 00001" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">City</label>
                        <input type="text" name="branch_city" placeholder="e.g. Taguig City" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Operating Hours</label>
                        <input type="text" name="operating_hours" placeholder="10:00 AM - 10:00 PM" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                    </div>
                </div>

                <div style="padding-top: 10px; display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                    <button type="button" onclick="closeCreateBranchModal()" style="border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 12.5px; font-weight: 600; padding: 7px 16px; border-radius: 8px; cursor: pointer;">Cancel</button>
                    <button type="submit" style="border: none; background: #059669; color: #ffffff; font-size: 12.5px; font-weight: 600; padding: 7px 20px; border-radius: 8px; cursor: pointer;">Add Branch</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Modal: Edit Branch -->
    <div id="modalEditBranch" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #ffffff; border-radius: 14px; width: 100%; max-width: 580px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 700; color: #0f172a;">
                    <i class="ph ph-pencil-simple" style="color: #2563eb; font-size: 20px;"></i>
                    <span>Edit Operational Branch</span>
                </div>
                <button type="button" onclick="closeEditBranchModal()" style="border: none; background: transparent; color: #64748b; font-size: 18px; cursor: pointer;"><i class="ph ph-x"></i></button>
            </div>
            <form id="editBranchForm" action="" method="POST" style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                @csrf
                @method('PUT')
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Code *</label>
                        <input type="text" name="branch_code" id="editBranchCode" required style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; text-transform: uppercase;">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Name *</label>
                        <input type="text" name="branch_name" id="editBranchName" required style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Type</label>
                        <select name="branch_type" id="editBranchType" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px; background: #ffffff;">
                            @foreach($branchTypes as $bt)
                                <option value="{{ $bt }}">{{ $bt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Branch Tax Code</label>
                        <input type="text" name="branch_tax_code" id="editBranchTaxCode" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">City</label>
                        <input type="text" name="branch_city" id="editBranchCity" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Operating Hours</label>
                        <input type="text" name="operating_hours" id="editBranchHours" style="width: 100%; height: 34px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                    </div>
                </div>

                <div style="padding-top: 10px; display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                    <button type="button" id="btnDeleteFromEditBranch" onclick="handleDeleteFromEditBranchModal()" style="border: 1px solid #fecaca; background: #fff5f5; color: #dc2626; font-size: 12px; font-weight: 600; padding: 7px 14px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="ph ph-trash"></i> Delete Branch
                    </button>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" onclick="closeEditBranchModal()" style="border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 12.5px; font-weight: 600; padding: 7px 16px; border-radius: 8px; cursor: pointer;">Cancel</button>
                        <button type="submit" style="border: none; background: #2563eb; color: #ffffff; font-size: 12.5px; font-weight: 600; padding: 7px 20px; border-radius: 8px; cursor: pointer;">Update Branch</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. Modal: Confirm Delete Company -->
    <div id="modalConfirmDeleteCompany" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
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

    <!-- 5. Modal: Confirm Delete Branch -->
    <div id="modalConfirmDeleteBranch" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
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

<!-- Raw Payload Data for Instant Client-Side Processing -->
<script>
    window.COMPANIES_DATA = @json($companies);
    window.BRANCHES_DATA = @json($allBranches);
    window.SELECTED_COMPANY_ID = {{ $selectedCompany ? $selectedCompany->company_id : 'null' }};
</script>

<!-- Modular Table Architecture Scripts (Patterned after Request for Quotations) -->
<script>
/**
 * 12-Column Granular Modular Table Definitions
 */
const DIRECTORY_COLUMNS = [
    {
        id: 'identity',
        label: 'Company Entity',
        subLabel: 'Code & Visual Identity',
        topPurpose: 'Unique identifier, entity code and brand logo',
        default: true,
        lockVisible: true,
        align: 'left',
        width: '180px',
        minWidth: '150px'
    },
    {
        id: 'legal_name',
        label: 'Legal & Trade Name',
        subLabel: 'Corporate Registered Title',
        topPurpose: 'Statutory registered business name and public trade name',
        default: true,
        lockVisible: false,
        align: 'left',
        width: '240px',
        minWidth: '180px'
    },
    {
        id: 'entity_type',
        label: 'Classification',
        subLabel: 'Corporate Structure',
        topPurpose: 'Holding group, subsidiary, or standalone company type',
        default: true,
        lockVisible: false,
        align: 'center',
        width: '130px',
        minWidth: '110px'
    },
    {
        id: 'branch_pills',
        label: 'Attached Branches',
        subLabel: 'Operational Outlets & Pills',
        topPurpose: 'Connected physical stores, kiosks, and central commissaries',
        default: true,
        lockVisible: false,
        align: 'left',
        width: '280px',
        minWidth: '220px'
    },
    {
        id: 'tax_tin',
        label: 'TIN & BIR Suffix',
        subLabel: 'Tax Registration & RDO',
        topPurpose: 'Bureau of Internal Revenue Tax Identification Number and Branch Code',
        default: true,
        lockVisible: false,
        align: 'left',
        width: '180px',
        minWidth: '150px'
    },
    {
        id: 'base_currency',
        label: 'Currency',
        subLabel: 'Base Accounting Unit',
        topPurpose: 'Standard currency and fiscal timezone',
        default: false,
        lockVisible: false,
        align: 'center',
        width: '110px',
        minWidth: '90px'
    },
    {
        id: 'contact',
        label: 'Contact Info',
        subLabel: 'Phone & Administrator',
        topPurpose: 'Official phone number and primary administrator',
        default: true,
        lockVisible: false,
        align: 'left',
        width: '160px',
        minWidth: '140px'
    },
    {
        id: 'web_domain',
        label: 'Digital Presence',
        subLabel: 'Domain & Website URL',
        topPurpose: 'Corporate email domain and external portal URL',
        default: false,
        lockVisible: false,
        align: 'left',
        width: '170px',
        minWidth: '140px'
    },
    {
        id: 'location',
        label: 'Headquarters',
        subLabel: 'City & Province',
        topPurpose: 'Principal office location and city',
        default: false,
        lockVisible: false,
        align: 'left',
        width: '170px',
        minWidth: '130px'
    },
    {
        id: 'brand_color',
        label: 'Brand Color',
        subLabel: 'Theme Identity',
        topPurpose: 'Primary brand accent color',
        default: false,
        lockVisible: false,
        align: 'center',
        width: '110px',
        minWidth: '90px'
    },
    {
        id: 'status',
        label: 'Operational Status',
        subLabel: 'Active Flag',
        topPurpose: 'System active status',
        default: true,
        lockVisible: false,
        align: 'center',
        width: '110px',
        minWidth: '95px'
    },
    {
        id: 'actions',
        label: 'Action',
        subLabel: 'Console Controls',
        topPurpose: 'View Company Details, context switch, and status controls',
        default: true,
        lockVisible: true,
        align: 'center',
        width: '140px',
        minWidth: '120px'
    }
];

// State & Persistent Storage Keys
const STORAGE_PREFS_KEY = 'rms_companies_table_prefs_v1';
let activeDirectoryColIds = [];
let directoryColOrder = [];
let directoryColWidths = {};
let directorySearchTerm = '';
let directoryColSearchTerm = '';
let currentDirectoryPage = 1;
let directoryPageSize = 10;

/**
 * Initialize Modular Table on Page Load
 */
document.addEventListener('DOMContentLoaded', function() {
    loadSavedDirectoryPreferences();
    renderDirectoryTableHeader();
    renderCompaniesDirectory();

    // Close settings dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('companiesTableSettingsDropdown');
        const trigger = document.getElementById('btnCompaniesColFilter');
        if (dropdown && !dropdown.contains(e.target) && !trigger.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});

/**
 * Load Preferences from LocalStorage
 */
function loadSavedDirectoryPreferences() {
    try {
        const raw = localStorage.getItem(STORAGE_PREFS_KEY);
        if (raw) {
            const parsed = JSON.parse(raw);
            activeDirectoryColIds = Array.isArray(parsed.activeIds) ? parsed.activeIds : null;
            directoryColOrder = Array.isArray(parsed.order) ? parsed.order : null;
            directoryColWidths = (parsed.widths && typeof parsed.widths === 'object') ? parsed.widths : {};
        }
    } catch(e) {
        console.warn('Could not parse table preferences:', e);
    }

    if (!directoryColOrder || directoryColOrder.length === 0) {
        directoryColOrder = DIRECTORY_COLUMNS.map(c => c.id);
    } else {
        // Ensure all registered columns are present
        DIRECTORY_COLUMNS.forEach(c => {
            if (!directoryColOrder.includes(c.id)) directoryColOrder.push(c.id);
        });
    }

    if (!activeDirectoryColIds || activeDirectoryColIds.length === 0) {
        activeDirectoryColIds = DIRECTORY_COLUMNS.filter(c => c.default).map(c => c.id);
    }

    // Ensure locked columns are strictly visible
    DIRECTORY_COLUMNS.filter(c => c.lockVisible).forEach(c => {
        if (!activeDirectoryColIds.includes(c.id)) activeDirectoryColIds.push(c.id);
    });
}

/**
 * Save Preferences to LocalStorage
 */
function saveDirectoryPreferences() {
    try {
        localStorage.setItem(STORAGE_PREFS_KEY, JSON.stringify({
            activeIds: activeDirectoryColIds,
            order: directoryColOrder,
            widths: directoryColWidths
        }));
    } catch(e) {}
}

/**
 * Render Modular Table Thead
 */
function renderDirectoryTableHeader() {
    const theadRow = document.getElementById('companiesDirectoryTheadRow');
    if (!theadRow) return;

    const orderedVisibleCols = directoryColOrder
        .filter(id => activeDirectoryColIds.includes(id))
        .map(id => DIRECTORY_COLUMNS.find(c => c.id === id))
        .filter(Boolean);

    let html = '';
    orderedVisibleCols.forEach(col => {
        const w = directoryColWidths[col.id] || col.width;
        const align = col.align === 'center' ? 'text-align: center;' : (col.align === 'right' ? 'text-align: right;' : 'text-align: left;');
        
        if (col.id === 'actions') {
            html += `
                <th style="width: ${w}; min-width: ${col.minWidth}; ${align} padding: 10px 12px; font-weight: 700; color: #475569; position: relative;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <span>Action</span>
                        <button type="button" onclick="toggleCompaniesTableSettingsDropdown(event)" style="border: none; background: none; color: #64748b; cursor: pointer; padding: 2px;" title="Customize Columns">
                            <i class="ph ph-gear"></i>
                        </button>
                    </div>
                </th>
            `;
        } else {
            html += `
                <th draggable="true" ondragstart="handleHeaderDragStart(event, '${col.id}')" ondragover="handleHeaderDragOver(event, '${col.id}')" ondrop="handleHeaderDrop(event, '${col.id}')"
                    style="width: ${w}; min-width: ${col.minWidth}; ${align} padding: 10px 12px; font-weight: 700; color: #475569; position: relative; cursor: grab;"
                    title="${col.topPurpose}">
                    <div style="display: flex; flex-direction: column; gap: 2px;">
                        <span style="font-size: 12px; color: #1e293b; font-weight: 700;">${col.label}</span>
                        ${col.subLabel ? `<span style="font-size: 10px; color: #94a3b8; font-weight: 500;">${col.subLabel}</span>` : ''}
                    </div>
                    <div class="col-resizer" onmousedown="initColResize(event, '${col.id}')" style="position: absolute; right: 0; top: 0; bottom: 0; width: 5px; cursor: col-resize; user-select: none;"></div>
                </th>
            `;
        }
    });

    theadRow.innerHTML = html;
    renderDirectoryColDropdownChecklist();
}

/**
 * Render Column Visibility Checkbox List in Settings Dropdown
 */
function renderDirectoryColDropdownChecklist() {
    const listEl = document.getElementById('directoryColCheckboxesList');
    if (!listEl) return;

    const query = (directoryColSearchTerm || '').toLowerCase().trim();
    const orderedCols = directoryColOrder.map(id => DIRECTORY_COLUMNS.find(c => c.id === id)).filter(Boolean);

    listEl.innerHTML = orderedCols
        .filter(col => {
            if (!query) return true;
            return col.label.toLowerCase().includes(query) || (col.subLabel && col.subLabel.toLowerCase().includes(query));
        })
        .map(col => {
            const isChecked = activeDirectoryColIds.includes(col.id);
            const isDisabled = col.lockVisible ? 'disabled' : '';

            return `
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 4px 6px; border-radius: 6px; background: #f8fafc; font-size: 12px;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; flex: 1; user-select: none;">
                        <input type="checkbox" ${isChecked ? 'checked' : ''} ${isDisabled} onchange="toggleDirectoryColVisibility('${col.id}', this.checked)" style="accent-color: #2563eb;">
                        <span style="color: #1e293b; font-weight: 600;">${col.label}</span>
                    </label>
                    ${col.lockVisible ? '<span style="font-size: 10px; color: #94a3b8; font-weight: 600;">(Locked)</span>' : ''}
                </div>
            `;
        }).join('');

    const counter = document.getElementById('companiesColActiveCounter');
    if (counter) {
        counter.textContent = `${activeDirectoryColIds.length} of ${DIRECTORY_COLUMNS.length} visible`;
    }
}

/**
 * Toggle Column Visibility
 */
function toggleDirectoryColVisibility(colId, isVisible) {
    if (isVisible) {
        if (!activeDirectoryColIds.includes(colId)) activeDirectoryColIds.push(colId);
    } else {
        activeDirectoryColIds = activeDirectoryColIds.filter(id => id !== colId);
    }
    saveDirectoryPreferences();
    renderDirectoryTableHeader();
    renderCompaniesDirectory();
}

function showAllDirectoryColumns() {
    activeDirectoryColIds = DIRECTORY_COLUMNS.map(c => c.id);
    saveDirectoryPreferences();
    renderDirectoryTableHeader();
    renderCompaniesDirectory();
}

function resetDirectoryColumnDefaults() {
    activeDirectoryColIds = DIRECTORY_COLUMNS.filter(c => c.default).map(c => c.id);
    directoryColOrder = DIRECTORY_COLUMNS.map(c => c.id);
    directoryColWidths = {};
    saveDirectoryPreferences();
    renderDirectoryTableHeader();
    renderCompaniesDirectory();
}

function resetDirectoryColumnWidths() {
    directoryColWidths = {};
    saveDirectoryPreferences();
    renderDirectoryTableHeader();
}

function handleDirectoryColSearch(val) {
    directoryColSearchTerm = val;
    renderDirectoryColDropdownChecklist();
}

function toggleCompaniesTableSettingsDropdown(e) {
    if (e) e.stopPropagation();
    const dropdown = document.getElementById('companiesTableSettingsDropdown');
    if (!dropdown) return;
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

/**
 * Render Table Body with Dynamic Companies and Interactive Branch Pills
 */
function renderCompaniesDirectory() {
    const tbody = document.getElementById('companiesDirectoryTbody');
    if (!tbody) return;

    let items = Array.isArray(window.COMPANIES_DATA) ? [...window.COMPANIES_DATA] : [];

    // Filter by live search
    if (directorySearchTerm) {
        const q = directorySearchTerm.toLowerCase();
        items = items.filter(c => {
            const branchesStr = (c.branches || []).map(b => b.branch_name + ' ' + b.branch_code).join(' ').toLowerCase();
            return (c.company_code && c.company_code.toLowerCase().includes(q)) ||
                   (c.company_legal_name && c.company_legal_name.toLowerCase().includes(q)) ||
                   (c.company_trade_name && c.company_trade_name.toLowerCase().includes(q)) ||
                   (c.company_tax_id && c.company_tax_id.toLowerCase().includes(q)) ||
                   (c.company_city && c.company_city.toLowerCase().includes(q)) ||
                   (c.company_contact_person && c.company_contact_person.toLowerCase().includes(q)) ||
                   branchesStr.includes(q);
        });
    }

    // Pagination slicing
    const totalCount = items.length;
    const startIdx = (currentDirectoryPage - 1) * directoryPageSize;
    const endIdx = startIdx + directoryPageSize;
    const pageItems = items.slice(startIdx, endIdx);

    const orderedVisibleCols = directoryColOrder
        .filter(id => activeDirectoryColIds.includes(id))
        .map(id => DIRECTORY_COLUMNS.find(c => c.id === id))
        .filter(Boolean);

    if (pageItems.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${orderedVisibleCols.length}" style="padding: 36px; text-align: center; color: #94a3b8;">
                    <i class="ph ph-magnifying-glass" style="font-size: 28px; margin-bottom: 6px;"></i>
                    <p style="margin: 0; font-size: 13px;">No companies matching "${escapeHtml(directorySearchTerm)}"</p>
                </td>
            </tr>
        `;
        renderPaginationBar(0, 0, 0);
        return;
    }

    let rowsHtml = '';
    pageItems.forEach(company => {
        rowsHtml += `<tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">`;

        orderedVisibleCols.forEach(col => {
            rowsHtml += `<td style="padding: 10px 12px; vertical-align: middle;">`;

            switch(col.id) {
                case 'identity':
                    const logoSrc = company.company_logo ? `/storage/${company.company_logo}` : null;
                    rowsHtml += `
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                ${logoSrc ? `<img src="${logoSrc}" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">` : `<i class="ph ph-buildings" style="color: #94a3b8; font-size: 18px;"></i>`}
                            </div>
                            <div>
                                <span style="font-size: 12px; font-weight: 700; color: #2563eb; font-family: monospace;">${escapeHtml(company.company_code)}</span>
                                <div style="display: flex; align-items: center; gap: 4px; margin-top: 1px;">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: ${company.company_is_active ? '#10b981' : '#ef4444'};"></span>
                                    <span style="font-size: 10.5px; color: #64748b;">${company.company_is_active ? 'Active' : 'Inactive'}</span>
                                </div>
                            </div>
                        </div>
                    `;
                    break;

                case 'legal_name':
                    rowsHtml += `
                        <div style="max-height: 44px; overflow: hidden; line-height: 1.3;">
                            <a href="javascript:void(0)" onclick="selectCompanyForDetails(${company.company_id})" style="font-size: 12.5px; font-weight: 700; color: #0f172a; text-decoration: none; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px;" title="${escapeHtml(company.company_legal_name)}" onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='#0f172a'">
                                ${escapeHtml(company.company_legal_name)}
                            </a>
                            ${company.company_trade_name ? `<div style="font-size: 11px; color: #64748b; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px;" title="DBA: ${escapeHtml(company.company_trade_name)}">DBA: ${escapeHtml(company.company_trade_name)}</div>` : ''}
                        </div>
                    `;
                    break;

                case 'entity_type':
                    const badgeBg = company.company_type === 'Holding Enterprise' ? '#f5f3ff' : '#eff6ff';
                    const badgeColor = company.company_type === 'Holding Enterprise' ? '#7c3aed' : '#2563eb';
                    rowsHtml += `
                        <div style="text-align: center;">
                            <span style="font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 999px; background: ${badgeBg}; color: ${badgeColor};">
                                ${escapeHtml(company.company_type || 'Company')}
                            </span>
                        </div>
                    `;
                    break;

                case 'branch_pills':
                    const branches = company.branches || [];
                    if (branches.length === 0) {
                        rowsHtml += `<span style="font-size: 11px; color: #94a3b8; font-style: italic;">No branches linked</span>`;
                    } else {
                        const maxPills = 2;
                        const visiblePills = branches.slice(0, maxPills);
                        const remainder = branches.length - maxPills;

                        rowsHtml += `<div class="branch-pills-wrap">`;
                        visiblePills.forEach(b => {
                            rowsHtml += `
                                <span class="branch-pill" title="${escapeHtml(b.branch_name)} (${b.branch_code}) - ${b.branch_type}">
                                    <span style="width: 5px; height: 5px; border-radius: 50%; background: ${b.branch_is_active ? '#10b981' : '#ef4444'}; flex-shrink: 0;"></span>
                                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(b.branch_name)}</span>
                                </span>
                            `;
                        });
                        if (remainder > 0) {
                            const extraNames = escapeHtml(branches.slice(maxPills).map(b => b.branch_name).join(', '));
                            rowsHtml += `
                                <span class="branch-pill-more" onclick="event.stopPropagation(); selectCompanyForDetails(${company.company_id})" title="${extraNames}">
                                    (+${remainder})
                                </span>
                            `;
                        }
                        rowsHtml += `</div>`;
                    }
                    break;

                case 'tax_tin':
                    const tinDisplay = company.company_tax_id ? (company.company_branch_code ? `${company.company_tax_id}-${company.company_branch_code}` : company.company_tax_id) : 'Not Registered';
                    const isVat = company.company_is_vat_registered || company.company_tax_type === 'VAT Registered';
                    rowsHtml += `
                        <div>
                            <span style="font-size: 11.5px; font-family: monospace; font-weight: 600; color: #1e293b;">${escapeHtml(tinDisplay)}</span>
                            <div style="display: flex; align-items: center; gap: 6px; margin-top: 1px;">
                                ${company.company_rdo_code ? `<span style="font-size: 10.5px; color: #64748b;">RDO: ${escapeHtml(company.company_rdo_code)}</span>` : ''}
                                <span style="font-size: 10px; font-weight: 600; padding: 1px 5px; border-radius: 4px; background: ${isVat ? '#ecfdf5' : '#f1f5f9'}; color: ${isVat ? '#059669' : '#64748b'};">
                                    ${isVat ? 'VAT' : 'Non-VAT'}
                                </span>
                            </div>
                        </div>
                    `;
                    break;

                case 'base_currency':
                    rowsHtml += `
                        <div style="text-align: center;">
                            <span style="font-size: 11.5px; font-weight: 700; color: #0f172a;">${escapeHtml(company.company_base_currency_code || 'PHP')}</span>
                        </div>
                    `;
                    break;

                case 'contact':
                    rowsHtml += `
                        <div style="font-size: 11.5px;">
                            ${company.company_phone_number ? `<div style="color: #1e293b;"><i class="ph ph-phone" style="color: #64748b;"></i> ${escapeHtml(company.company_phone_number)}</div>` : ''}
                            ${company.company_email_address ? `<div style="color: #64748b;"><i class="ph ph-envelope" style="color: #64748b;"></i> ${escapeHtml(company.company_email_address)}</div>` : ''}
                        </div>
                    `;
                    break;

                case 'web_domain':
                    rowsHtml += `
                        <div style="font-size: 11.5px;">
                            ${company.company_email_domain ? `<span style="color: #2563eb; font-weight: 600;">@${escapeHtml(company.company_email_domain)}</span>` : ''}
                            ${company.company_website_url ? `<div style="color: #64748b;"><a href="${escapeHtml(company.company_website_url)}" target="_blank" style="color: #64748b; text-decoration: none;"><i class="ph ph-globe"></i> Website</a></div>` : ''}
                        </div>
                    `;
                    break;

                case 'location':
                    const loc = [company.company_city, company.company_state_province_code, company.company_country_code].filter(Boolean).join(', ');
                    rowsHtml += `<span style="font-size: 11.5px; color: #475569;">${escapeHtml(loc || 'Philippines')}</span>`;
                    break;

                case 'brand_color':
                    const colorHex = company.company_brand_color || '#ef4444';
                    rowsHtml += `
                        <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                            <span style="width: 14px; height: 14px; border-radius: 50%; background-color: ${colorHex}; display: inline-block;"></span>
                            <span style="font-size: 11px; font-family: monospace; color: #64748b;">${colorHex}</span>
                        </div>
                    `;
                    break;

                case 'status':
                    rowsHtml += `
                        <div style="text-align: center;">
                            <span style="font-size: 11px; padding: 2px 8px; border-radius: 999px; font-weight: 600; background: ${company.company_is_active ? '#ecfdf5' : '#fee2e2'}; color: ${company.company_is_active ? '#059669' : '#dc2626'};">
                                ${company.company_is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                    `;
                    break;

                case 'actions':
                    rowsHtml += `
                        <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                            <button type="button" onclick="selectCompanyForDetails(${company.company_id})" style="border: 1px solid #bfdbfe; background: #eff6ff; color: #2563eb; font-size: 11.5px; font-weight: 600; padding: 4px 9px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.15s;" title="View & Edit Company Details">
                                <i class="ph ph-arrow-right"></i> Details
                            </button>
                            <button type="button" onclick="confirmDeleteCompany(${company.company_id}, '${escapeHtml(company.company_legal_name).replace(/'/g, "\\'")}')" style="border: 1px solid #fecaca; background: #fef2f2; color: #dc2626; font-size: 11.5px; font-weight: 600; padding: 4px 8px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.15s;" title="Delete Company">
                                <i class="ph ph-trash"></i>
                            </button>
                        </div>
                    `;
                    break;
            }

            rowsHtml += `</td>`;
        });

        rowsHtml += `</tr>`;
    });

    tbody.innerHTML = rowsHtml;
    renderPaginationBar(startIdx + 1, Math.min(endIdx, totalCount), totalCount);
}

/**
 * Render Pagination Controls
 */
function renderPaginationBar(start, end, total) {
    const info = document.getElementById('companiesPaginationInfo');
    if (info) {
        info.innerHTML = `Showing <strong>${start}</strong> to <strong>${end}</strong> of <strong>${total}</strong> companies`;
    }

    const controls = document.getElementById('companiesPaginationControls');
    if (!controls) return;

    const totalPages = Math.ceil(total / directoryPageSize) || 1;
    let btns = `
        <button type="button" onclick="changeDirectoryPage(${currentDirectoryPage - 1})" ${currentDirectoryPage <= 1 ? 'disabled' : ''} style="border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 11px; padding: 3px 8px; border-radius: 6px; cursor: pointer;">
            Prev
        </button>
    `;

    for (let p = 1; p <= totalPages; p++) {
        if (totalPages > 7 && Math.abs(p - currentDirectoryPage) > 2 && p !== 1 && p !== totalPages) {
            continue;
        }
        btns += `
            <button type="button" onclick="changeDirectoryPage(${p})" style="border: 1px solid ${p === currentDirectoryPage ? '#2563eb' : '#cbd5e1'}; background: ${p === currentDirectoryPage ? '#2563eb' : '#ffffff'}; color: ${p === currentDirectoryPage ? '#ffffff' : '#334155'}; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px; cursor: pointer;">
                ${p}
            </button>
        `;
    }

    btns += `
        <button type="button" onclick="changeDirectoryPage(${currentDirectoryPage + 1})" ${currentDirectoryPage >= totalPages ? 'disabled' : ''} style="border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 11px; padding: 3px 8px; border-radius: 6px; cursor: pointer;">
            Next
        </button>
    `;

    controls.innerHTML = btns;
}

function changeDirectoryPage(page) {
    const totalPages = Math.ceil((window.COMPANIES_DATA || []).length / directoryPageSize) || 1;
    if (page < 1 || page > totalPages) return;
    currentDirectoryPage = page;
    renderCompaniesDirectory();
}

function changeDirectoryPageSize(size) {
    directoryPageSize = parseInt(size, 10) || 10;
    currentDirectoryPage = 1;
    renderCompaniesDirectory();
}

function handleCompaniesDirectorySearch(val) {
    directorySearchTerm = val;
    currentDirectoryPage = 1;
    renderCompaniesDirectory();
}

/**
 * View Mode Switching: Directory vs Company Details
 */
function switchViewMode(mode) {
    const dirSec = document.getElementById('sectionViewDirectory');
    const detSec = document.getElementById('sectionViewDetails');
    const pills = document.querySelectorAll('.view-mode-pill');

    if (mode === 'details') {
        if (dirSec) dirSec.style.display = 'none';
        if (detSec) detSec.style.display = 'block';
        if (pills[0]) { pills[0].style.background = 'transparent'; pills[0].style.color = '#64748b'; pills[0].style.boxShadow = 'none'; }
        if (pills[1]) { pills[1].style.background = '#ffffff'; pills[1].style.color = '#0f172a'; pills[1].style.boxShadow = '0 1px 3px rgba(0,0,0,0.08)'; }
        window.history.replaceState(null, '', `?view=details${window.SELECTED_COMPANY_ID ? '&company_id=' + window.SELECTED_COMPANY_ID : ''}`);
    } else {
        if (dirSec) dirSec.style.display = 'block';
        if (detSec) detSec.style.display = 'none';
        if (pills[0]) { pills[0].style.background = '#ffffff'; pills[0].style.color = '#0f172a'; pills[0].style.boxShadow = '0 1px 3px rgba(0,0,0,0.08)'; }
        if (pills[1]) { pills[1].style.background = 'transparent'; pills[1].style.color = '#64748b'; pills[1].style.boxShadow = 'none'; }
        window.history.replaceState(null, '', '?view=directory');
    }
}

function selectCompanyForDetails(companyId) {
    window.location.href = `{{ route('config.companies-branches.index') }}?view=details&company_id=${companyId}`;
}

/**
 * Company Sub-tab Switching (General Info, Branches, Vault, Hierarchy)
 */
function switchCompanySubTab(tab) {
    const panes = {
        'general': document.getElementById('companyTabPaneGeneral'),
        'branches': document.getElementById('companyTabPaneBranches'),
        'vault': document.getElementById('companyTabPaneVault'),
        'hierarchy': document.getElementById('companyTabPaneHierarchy')
    };

    const btns = {
        'general': document.getElementById('tabBtnGeneral'),
        'branches': document.getElementById('tabBtnBranches'),
        'vault': document.getElementById('tabBtnVault'),
        'hierarchy': document.getElementById('tabBtnHierarchy')
    };

    Object.keys(panes).forEach(k => {
        if (panes[k]) panes[k].style.display = (k === tab ? 'block' : 'none');
        if (btns[k]) {
            if (k === tab) {
                btns[k].style.color = '#2563eb';
                btns[k].style.borderBottomColor = '#2563eb';
            } else {
                btns[k].style.color = '#64748b';
                btns[k].style.borderBottomColor = 'transparent';
            }
        }
    });
}

/**
 * Logo Preview Handling
 */
function handleLogoFileSelect(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let img = document.getElementById('companyLogoPreviewImg');
            const placeholder = document.getElementById('companyLogoPlaceholder');
            if (placeholder) placeholder.style.display = 'none';
            if (!img) {
                img = document.createElement('img');
                img.id = 'companyLogoPreviewImg';
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'contain';
                input.parentElement.appendChild(img);
            }
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * Brand Color Swatch Picker Handling
 */
function handleBrandColorChange(color) {
    const hexLabel = document.getElementById('brandColorHexLabel');
    if (hexLabel) hexLabel.textContent = color;
    const dot = document.getElementById('headerBrandColorDot');
    if (dot) {
        dot.style.backgroundColor = color;
        dot.style.boxShadow = `0 0 0 2px #ffffff, 0 0 0 3px ${color}`;
    }
}

function selectBrandColorPreset(color) {
    const picker = document.getElementById('companyBrandColorPicker');
    if (picker) {
        picker.value = color;
        handleBrandColorChange(color);
    }
}

/**
 * Modal Open/Close Controls
 */
function openCreateCompanyModal() {
    const m = document.getElementById('modalCreateCompany');
    if (m) m.style.display = 'flex';
}
function closeCreateCompanyModal() {
    const m = document.getElementById('modalCreateCompany');
    if (m) m.style.display = 'none';
}

function openCreateBranchModal(companyId) {
    const m = document.getElementById('modalCreateBranch');
    const select = document.getElementById('branchModalCompanySelect');
    if (select && companyId) select.value = companyId;
    if (m) m.style.display = 'flex';
}
function closeCreateBranchModal() {
    const m = document.getElementById('modalCreateBranch');
    if (m) m.style.display = 'none';
}

let currentEditingBranch = null;

function editBranch(branch) {
    currentEditingBranch = branch;
    const m = document.getElementById('modalEditBranch');
    const form = document.getElementById('editBranchForm');
    if (!m || !form) return;

    form.action = `/config/business-settings/branches/${branch.branch_id}`;
    document.getElementById('editBranchCode').value = branch.branch_code || '';
    document.getElementById('editBranchName').value = branch.branch_name || '';
    document.getElementById('editBranchType').value = branch.branch_type || 'Store / Dine-In';
    document.getElementById('editBranchTaxCode').value = branch.branch_tax_code || '';
    document.getElementById('editBranchCity').value = branch.branch_city || '';
    document.getElementById('editBranchHours').value = branch.operating_hours || '';

    m.style.display = 'flex';
}

function closeEditBranchModal() {
    currentEditingBranch = null;
    const m = document.getElementById('modalEditBranch');
    if (m) m.style.display = 'none';
}

function handleDeleteFromEditBranchModal() {
    if (!currentEditingBranch) return;
    const bId = currentEditingBranch.branch_id;
    const bName = currentEditingBranch.branch_name;
    closeEditBranchModal();
    confirmDeleteBranch(bId, bName);
}

function confirmDeleteCompany(companyId, companyName) {
    const modal = document.getElementById('modalConfirmDeleteCompany');
    const form = document.getElementById('deleteCompanyForm');
    const nameEl = document.getElementById('deleteCompanyTargetName');
    if (!modal || !form) return;

    form.action = `/config/companies-branches/${companyId}`;
    if (nameEl) nameEl.textContent = companyName || 'this company';
    modal.style.display = 'flex';
}

function closeConfirmDeleteCompanyModal() {
    const modal = document.getElementById('modalConfirmDeleteCompany');
    if (modal) modal.style.display = 'none';
}

function confirmDeleteBranch(branchId, branchName) {
    const modal = document.getElementById('modalConfirmDeleteBranch');
    const form = document.getElementById('deleteBranchForm');
    const nameEl = document.getElementById('deleteBranchTargetName');
    if (!modal || !form) return;

    form.action = `/config/companies-branches/branches/${branchId}`;
    if (nameEl) nameEl.textContent = branchName || 'this branch';
    modal.style.display = 'flex';
}

function closeConfirmDeleteBranchModal() {
    const modal = document.getElementById('modalConfirmDeleteBranch');
    if (modal) modal.style.display = 'none';
}

/**
 * Drag and drop column sequence handlers
 */
let draggedColId = null;
function handleHeaderDragStart(e, id) {
    draggedColId = id;
    e.dataTransfer.effectAllowed = 'move';
}
function handleHeaderDragOver(e, id) {
    e.preventDefault();
}
function handleHeaderDrop(e, targetId) {
    e.preventDefault();
    if (!draggedColId || draggedColId === targetId) return;

    const fromIdx = directoryColOrder.indexOf(draggedColId);
    const toIdx = directoryColOrder.indexOf(targetId);
    if (fromIdx !== -1 && toIdx !== -1) {
        directoryColOrder.splice(fromIdx, 1);
        directoryColOrder.splice(toIdx, 0, draggedColId);
        saveDirectoryPreferences();
        renderDirectoryTableHeader();
        renderCompaniesDirectory();
    }
    draggedColId = null;
}

/**
 * Column Resizing handler
 */
function initColResize(e, colId) {
    e.stopPropagation();
    const th = e.target.parentElement;
    const startX = e.clientX;
    const startWidth = th.offsetWidth;

    function onMouseMove(ev) {
        const newWidth = Math.max(80, startWidth + (ev.clientX - startX));
        directoryColWidths[colId] = newWidth + 'px';
        th.style.width = newWidth + 'px';
    }

    function onMouseUp() {
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
        saveDirectoryPreferences();
    }

    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
</script>
@endsection
