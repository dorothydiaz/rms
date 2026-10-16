@extends('layouts.app')

@section('title', 'Employee Documents - People Administration')

@section('content')
<div class="hr-page-header" style="margin-bottom: 20px;">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-folder-notch-open"></i>
            Employee Documents Repository
        </h1>
        <p class="hr-page-subtitle">Health certificates, food handler permits, contracts, statutory IDs, and clearances</p>
    </div>
    <div class="hr-page-actions">
        <button class="hr-btn hr-btn-primary" onclick="openModal('uploadDocModal')">
            <i class="ph ph-upload-simple"></i>
            <span>Upload Document</span>
        </button>
    </div>
</div>

<!-- Real-Time Filter & Search Bar (No Enter Key Required) -->
<div class="hr-filter-bar" style="margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: nowrap;">
    <!-- Filters & Search Controls Group -->
    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; flex: 1;">
        <!-- Search Input -->
        <div style="position: relative; width: 220px; flex-shrink: 0;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; pointer-events: none;"></i>
            <input type="text" id="docSearchInput" class="hr-input" placeholder="Search title, file, branch..." oninput="filterDocumentsTable()" style="width: 100%; box-sizing: border-box; padding-left: 28px; height: 31px; font-size: 12px;">
        </div>

        <!-- Document Type Filter -->
        <select id="docTypeFilter" class="hr-select" style="height: 31px; max-width: 180px; font-size: 12px;" onchange="filterDocumentsTable()">
            <option value="">-- All Types --</option>
            <option value="Health Permit">Health Permit / Sanitary</option>
            <option value="Food Handler Certificate">Food Handler Certificate</option>
            <option value="Employment Contract">Employment Contract</option>
            <option value="Government ID">Government ID</option>
            <option value="NBI Clearance">NBI / Police Clearance</option>
            <option value="Other">Other Document</option>
        </select>

        <!-- Branch Filter -->
        <select id="docBranchFilter" class="hr-select" style="height: 31px; max-width: 160px; font-size: 12px;" onchange="filterDocumentsTable()">
            <option value="">-- All Branches --</option>
            @foreach($branches as $b)
                <option value="{{ $b->name }}">{{ $b->name }}</option>
            @endforeach
        </select>

        <!-- Expiry Status Filter -->
        <select id="docExpiryFilter" class="hr-select" style="height: 31px; max-width: 155px; font-size: 12px;" onchange="filterDocumentsTable()">
            <option value="">-- Expiry Status --</option>
            <option value="valid">Active / Valid</option>
            <option value="expiring">Expiring Soon (30d)</option>
            <option value="expired">Expired</option>
            <option value="none">No Expiry Date</option>
        </select>

        <!-- Sort By Options in Table Toolbar -->
        <select id="docSortSelect" class="hr-select" style="height: 31px; max-width: 175px; font-size: 12px;" onchange="applyDocSortFromSelect(this.value)">
            <option value="">Sort By: Default</option>
            <option value="title_asc">Title (A &rarr; Z)</option>
            <option value="title_desc">Title (Z &rarr; A)</option>
            <option value="type_asc">Type (A &rarr; Z)</option>
            <option value="employee_asc">Employee (A &rarr; Z)</option>
            <option value="employee_desc">Employee (Z &rarr; A)</option>
            <option value="branch_asc">Branch (A &rarr; Z)</option>
            <option value="size_asc">File Size (Smallest)</option>
            <option value="size_desc">File Size (Largest)</option>
            <option value="expiry_asc">Expiry (Earliest)</option>
            <option value="expiry_desc">Expiry (Latest)</option>
        </select>

        <!-- Reset Filter Button -->
        <button type="button" class="hr-btn hr-btn-secondary" onclick="resetDocumentFilters()" title="Reset all filters" style="height: 38px; padding: 0 14px;">
            <i class="ph ph-arrows-counter-clockwise"></i>
            <span>Reset</span>
        </button>
    </div>

    <!-- Live Counter Badge -->
    <div style="flex-shrink: 0;">
        <span class="hr-badge hr-badge-neutral" style="font-size: 12px; font-weight: 600; padding: 7px 12px; border-radius: 8px; background: #f1f5f9; color: #475569; display: inline-flex; align-items: center; gap: 4px;">
            Showing <strong id="docVisibleCount" style="color: #0f172a;">{{ $documents->count() }}</strong> of {{ $documents->count() }} documents
        </span>
    </div>
</div>

<!-- Table Card with Sticky Headers and Vertical Scrollbar -->
<div class="hr-table-card">
    <div class="hr-table-wrapper" id="documentsTableWrapper" style="max-height: 560px; overflow-y: auto; overflow-x: auto;">
        <table class="hr-table" id="documentsTable">
            <thead>
                <tr>
                    <th class="sortable" onclick="sortDocumentsTable(0, 'text')" title="Click to sort by Title (A-Z / Z-A)">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Document Title</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortDocumentsTable(1, 'text')" title="Click to sort by Document Type (A-Z / Z-A)">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Document Type</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortDocumentsTable(2, 'text')" title="Click to sort by Employee Name (A-Z / Z-A)">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Employee</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th class="sortable" style="min-width: 175px;" onclick="sortDocumentsTable(3, 'text')" title="Click to sort by Branch (A-Z / Z-A)">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Branch</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortDocumentsTable(4, 'number')" title="Click to sort by File Size">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>File Size</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortDocumentsTable(5, 'date')" title="Click to sort by Expiry Date">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Expiry Date</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th style="text-align: right; width: 140px;">Action</th>
                </tr>
            </thead>
            <tbody id="documentsTableBody">
                @forelse($documents as $doc)
                    @php
                        $ext = strtolower(pathinfo($doc->file_path ?? '', PATHINFO_EXTENSION));
                        $iconClass = 'ph-file-text';
                        $iconColor = '#64748b';
                        if ($ext === 'pdf') {
                            $iconClass = 'ph-file-pdf';
                            $iconColor = '#ef4444';
                        } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                            $iconClass = 'ph-file-image';
                            $iconColor = '#10b981';
                        } elseif (in_array($ext, ['doc', 'docx'])) {
                            $iconClass = 'ph-file-doc';
                            $iconColor = '#2563eb';
                        }

                        $isExpired = false;
                        $isExpiring = false;
                        $expiryStatus = 'none';
                        $expiryDateVal = 0;

                        if ($doc->expiry_date) {
                            $expDate = \Carbon\Carbon::parse($doc->expiry_date);
                            $expiryDateVal = $expDate->timestamp;
                            if ($expDate->isPast()) {
                                $isExpired = true;
                                $expiryStatus = 'expired';
                            } elseif ($expDate->diffInDays(now()) <= 30) {
                                $isExpiring = true;
                                $expiryStatus = 'expiring';
                            } else {
                                $expiryStatus = 'valid';
                            }
                        }

                        $fileSizeKb = ($doc->file_size ?? 0) / 1024;
                        $empName = $doc->employee?->full_name ?? 'Unassigned';
                        $branchName = $doc->employee?->branch?->name ?? 'Universal Access';
                    @endphp
                    <tr class="doc-row" 
                        data-title="{{ strtolower($doc->title . ' ' . $doc->file_name) }}"
                        data-type="{{ $doc->document_type }}"
                        data-employee="{{ strtolower($empName . ' ' . ($doc->employee?->employee_id ?? '')) }}"
                        data-branch="{{ $branchName }}"
                        data-size="{{ $fileSizeKb }}"
                        data-expiry-date="{{ $expiryDateVal }}"
                        data-expiry-status="{{ $expiryStatus }}">
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 36px; height: 36px; border-radius: 9px; background: rgba(124, 58, 237, 0.08); border: 1px solid rgba(124, 58, 237, 0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="ph {{ $iconClass }}" style="font-size: 20px; color: {{ $iconColor }};"></i>
                                </div>
                                <div>
                                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" style="font-weight: 700; color: #0f172a; text-decoration: none;" onmouseover="this.style.color='#7c3aed'" onmouseout="this.style.color='#0f172a'">
                                        {{ $doc->title }}
                                    </a>
                                    <div style="font-size: 11.5px; color: #64748b; margin-top: 1px;">
                                        {{ $doc->file_name }}
                                        @if($doc->notes)
                                            &bull; <span style="font-style: italic;">{{ Str::limit($doc->notes, 30) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="hr-badge hr-badge-neutral">{{ $doc->document_type }}</span>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #0f172a;">{{ $empName }}</div>
                            @if($doc->employee)
                                <div style="font-size: 11px; color: #64748b; font-family: monospace;">{{ $doc->employee->employee_id }}</div>
                            @endif
                        </td>
                        <td style="white-space: nowrap; min-width: 175px;">
                            <span class="hr-badge hr-badge-neutral">
                                <i class="ph ph-storefront"></i> {{ $branchName }}
                            </span>
                        </td>
                        <td style="font-family: monospace; font-size: 12.5px; color: #334155;">
                            {{ number_format($fileSizeKb, 1) }} KB
                        </td>
                        <td>
                            @if($doc->expiry_date)
                                @if($isExpired)
                                    <span class="hr-badge hr-badge-danger" title="Expired on {{ \Carbon\Carbon::parse($doc->expiry_date)->format('M d, Y') }}">
                                        <i class="ph ph-warning-circle"></i> Expired ({{ \Carbon\Carbon::parse($doc->expiry_date)->format('M d, Y') }})
                                    </span>
                                @elseif($isExpiring)
                                    <span class="hr-badge hr-badge-warning" title="Expiring soon">
                                        <i class="ph ph-clock"></i> Expires: {{ \Carbon\Carbon::parse($doc->expiry_date)->format('M d, Y') }}
                                    </span>
                                @else
                                    <span style="font-size: 12.5px; font-weight: 600; color: #0f172a;">
                                        {{ \Carbon\Carbon::parse($doc->expiry_date)->format('M d, Y') }}
                                    </span>
                                @endif
                            @else
                                <span style="color: #94a3b8; font-size: 12px;">No Expiry Date</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="hr-btn hr-btn-secondary hr-btn-sm" title="View Document in New Tab">
                                    <i class="ph ph-eye"></i>
                                </a>
                                <a href="{{ route('hr.people.documents.download', $doc->id) }}" class="hr-btn hr-btn-secondary hr-btn-sm" title="Download Document">
                                    <i class="ph ph-download-simple"></i>
                                </a>
                                <form method="POST" action="{{ route('hr.people.documents.destroy', $doc->id) }}" onsubmit="return confirm('Delete this document: {{ addslashes($doc->title) }}?');" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="hr-btn hr-btn-danger hr-btn-sm" title="Delete Document">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr id="emptyInitialRow">
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 36px;">
                            <i class="ph ph-folder-open" style="font-size: 32px; display: block; margin-bottom: 8px; color: #cbd5e1;"></i>
                            No documents uploaded yet. Click "Upload Document" to file health cards, IDs, or contracts.
                        </td>
                    </tr>
                @endforelse
                <tr id="noResultsRow" style="display: none;">
                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 36px;">
                        <i class="ph ph-magnifying-glass" style="font-size: 32px; display: block; margin-bottom: 8px; color: #cbd5e1;"></i>
                        No documents match your filter criteria.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($documents->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid #e2e8f0; background: #ffffff;">
            {{ $documents->links() }}
        </div>
    @endif
</div>

<!-- Modal: Upload Document (Slide-over Right Drawer with Pure White Background) -->
<div id="uploadDocModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 600px; background: #ffffff !important; border-left: 2px solid #cbd5e1; box-shadow: -10px 0 35px rgba(0, 0, 0, 0.15);">
        <div class="hr-modal-header" style="background: #ffffff !important; border-bottom: 1.5px solid #e2e8f0; padding: 18px 24px;">
            <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 38px; height: 38px; border-radius: 9px; background: rgba(124, 58, 237, 0.12); color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        <i class="ph ph-upload-simple"></i>
                    </div>
                    <div>
                        <span class="hr-modal-title" style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0;">Upload Employee Document</span>
                        <div style="font-size: 12px; color: #64748b;">File clearances, health cards, contracts, or statutory papers</div>
                    </div>
                </div>
                <button type="button" class="icon-btn" onclick="closeModal('uploadDocModal')" style="font-size: 18px;"><i class="ph ph-x"></i></button>
            </div>
        </div>
        <form method="POST" action="{{ route('hr.people.documents.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="hr-modal-body" style="padding: 20px 24px; background: #ffffff !important;">
                <div class="hr-form-group" style="margin-bottom: 16px;">
                    <label class="hr-form-label" style="font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px; display: block;">Employee *</label>
                    <select name="employee_id" class="hr-select" required>
                        <option value="">-- Select Employee --</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->employee_id }}) - {{ $e->branch?->name ?? 'Universal' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="hr-form-group" style="margin-bottom: 16px;">
                    <label class="hr-form-label" style="font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px; display: block;">Document Type *</label>
                    <select name="document_type" class="hr-select" required>
                        <option value="Health Permit">Health Permit / Sanitary Card</option>
                        <option value="Food Handler Certificate">Food Handler Certificate</option>
                        <option value="Employment Contract">Employment Contract</option>
                        <option value="Government ID">Government ID (SSS/TIN/PhilHealth/Passport)</option>
                        <option value="NBI Clearance">NBI / Police Clearance</option>
                        <option value="Other">Other Document</option>
                    </select>
                </div>

                <div class="hr-form-group" style="margin-bottom: 16px;">
                    <label class="hr-form-label" style="font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px; display: block;">Document Title *</label>
                    <input type="text" name="title" class="hr-input" required placeholder="e.g. City Health Permit 2026 or SSS ID Copy">
                </div>

                <div class="hr-form-group" style="margin-bottom: 16px;">
                    <label class="hr-form-label" style="font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px; display: block;">Attach File * (Max 10MB - PDF, PNG, JPG, DOC)</label>
                    <input type="file" name="file" class="hr-input" required accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.webp">
                </div>

                <div class="hr-form-group" style="margin-bottom: 16px;">
                    <label class="hr-form-label" style="font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px; display: block;">Expiry Date <span style="color: #64748b; font-weight: 400;">(Optional - for permits/clearances)</span></label>
                    <input type="date" name="expiry_date" class="hr-input">
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label" style="font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px; display: block;">Notes / Remarks</label>
                    <textarea name="notes" class="hr-input" rows="2" placeholder="e.g. Verified by HR; renewal due next year"></textarea>
                </div>
            </div>
            <div class="hr-modal-footer" style="background: #ffffff !important; border-top: 1.5px solid #e2e8f0; padding: 16px 24px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('uploadDocModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">
                    <i class="ph ph-check-circle"></i> Upload Document
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Universal Modal Open & Close functions to guarantee working under all environments
window.openModal = function(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
};

window.closeModal = function(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.remove('open');
        if (!document.querySelector('.hr-modal-overlay.open')) {
            document.body.style.overflow = '';
        }
    }
};

// =========================================================================
// Real-Time Table Filter (No Enter Key Required)
// =========================================================================
function filterDocumentsTable() {
    const searchVal = document.getElementById('docSearchInput').value.toLowerCase().trim();
    const typeVal = document.getElementById('docTypeFilter').value.trim();
    const branchVal = document.getElementById('docBranchFilter').value.trim();
    const expiryVal = document.getElementById('docExpiryFilter').value.trim();

    const rows = document.querySelectorAll('#documentsTableBody tr.doc-row');
    const noResultsRow = document.getElementById('noResultsRow');
    let visibleCount = 0;

    rows.forEach(row => {
        const title = row.getAttribute('data-title') || '';
        const docType = row.getAttribute('data-type') || '';
        const emp = row.getAttribute('data-employee') || '';
        const branch = row.getAttribute('data-branch') || '';
        const expStatus = row.getAttribute('data-expiry-status') || '';

        const matchesSearch = !searchVal || 
            title.includes(searchVal) || 
            emp.includes(searchVal) || 
            branch.toLowerCase().includes(searchVal) || 
            docType.toLowerCase().includes(searchVal);

        const matchesType = !typeVal || docType === typeVal;
        const matchesBranch = !branchVal || branch === branchVal;
        const matchesExpiry = !expiryVal || expStatus === expiryVal;

        if (matchesSearch && matchesType && matchesBranch && matchesExpiry) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const countElem = document.getElementById('docVisibleCount');
    if (countElem) countElem.textContent = visibleCount;

    if (noResultsRow) {
        if (visibleCount === 0 && rows.length > 0) {
            noResultsRow.style.display = '';
        } else {
            noResultsRow.style.display = 'none';
        }
    }
}

function resetDocumentFilters() {
    const s = document.getElementById('docSearchInput');
    if (s) s.value = '';
    const t = document.getElementById('docTypeFilter');
    if (t) t.value = '';
    const b = document.getElementById('docBranchFilter');
    if (b) b.value = '';
    const e = document.getElementById('docExpiryFilter');
    if (e) e.value = '';
    const sel = document.getElementById('docSortSelect');
    if (sel) sel.value = '';

    // Clear sort classes from headers
    document.querySelectorAll('#documentsTable thead th.sortable').forEach(th => {
        th.classList.remove('sorted-asc', 'sorted-desc');
        const icon = th.querySelector('.sort-icon i');
        if (icon) icon.className = 'ph ph-arrows-down-up';
    });
    currentSortColumn = -1;

    filterDocumentsTable();
}

// =========================================================================
// Real-Time Column Sorting (Via Table Header Click or Toolbar Dropdown)
// =========================================================================
let currentSortColumn = -1;
let currentSortDirection = 'asc';

function applyDocSortFromSelect(val) {
    if (!val) {
        // Reset sort
        document.querySelectorAll('#documentsTable thead th.sortable').forEach(th => {
            th.classList.remove('sorted-asc', 'sorted-desc');
            const icon = th.querySelector('.sort-icon i');
            if (icon) icon.className = 'ph ph-arrows-down-up';
        });
        currentSortColumn = -1;
        return;
    }

    const sortMap = {
        'title_asc': [0, 'text', 'asc'],
        'title_desc': [0, 'text', 'desc'],
        'type_asc': [1, 'text', 'asc'],
        'employee_asc': [2, 'text', 'asc'],
        'employee_desc': [2, 'text', 'desc'],
        'branch_asc': [3, 'text', 'asc'],
        'size_asc': [4, 'number', 'asc'],
        'size_desc': [4, 'number', 'desc'],
        'expiry_asc': [5, 'date', 'asc'],
        'expiry_desc': [5, 'date', 'desc'],
    };

    if (sortMap[val]) {
        sortDocumentsTable(sortMap[val][0], sortMap[val][1], sortMap[val][2]);
    }
}

function sortDocumentsTable(colIndex, dataType, forceDir = null) {
    const tableBody = document.getElementById('documentsTableBody');
    const rows = Array.from(tableBody.querySelectorAll('tr.doc-row'));
    const headers = document.querySelectorAll('#documentsTable thead th.sortable');

    if (forceDir) {
        currentSortDirection = forceDir;
        currentSortColumn = colIndex;
    } else {
        if (currentSortColumn === colIndex) {
            currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            currentSortColumn = colIndex;
            currentSortDirection = 'asc';
        }
    }

    // Update Header Sort Icons & Badges
    headers.forEach((th, idx) => {
        th.classList.remove('sorted-asc', 'sorted-desc');
        const icon = th.querySelector('.sort-icon i');
        if (icon) icon.className = 'ph ph-arrows-down-up';

        if (idx === colIndex) {
            th.classList.add(currentSortDirection === 'asc' ? 'sorted-asc' : 'sorted-desc');
            if (icon) icon.className = currentSortDirection === 'asc' ? 'ph ph-caret-up' : 'ph ph-caret-down';
        }
    });

    // Synchronize Toolbar Sort Dropdown
    const sortSelect = document.getElementById('docSortSelect');
    if (sortSelect) {
        const keyMap = {
            '0_asc': 'title_asc', '0_desc': 'title_desc',
            '1_asc': 'type_asc', '1_desc': 'type_asc',
            '2_asc': 'employee_asc', '2_desc': 'employee_desc',
            '3_asc': 'branch_asc', '3_desc': 'branch_asc',
            '4_asc': 'size_asc', '4_desc': 'size_desc',
            '5_asc': 'expiry_asc', '5_desc': 'expiry_desc',
        };
        sortSelect.value = keyMap[colIndex + '_' + currentSortDirection] || '';
    }

    rows.sort((a, b) => {
        let valA, valB;

        switch (colIndex) {
            case 0: // Title
                valA = a.getAttribute('data-title') || '';
                valB = b.getAttribute('data-title') || '';
                break;
            case 1: // Document Type
                valA = a.getAttribute('data-type') || '';
                valB = b.getAttribute('data-type') || '';
                break;
            case 2: // Employee
                valA = a.getAttribute('data-employee') || '';
                valB = b.getAttribute('data-employee') || '';
                break;
            case 3: // Branch
                valA = a.getAttribute('data-branch') || '';
                valB = b.getAttribute('data-branch') || '';
                break;
            case 4: // File Size
                valA = parseFloat(a.getAttribute('data-size') || 0);
                valB = parseFloat(b.getAttribute('data-size') || 0);
                return currentSortDirection === 'asc' ? valA - valB : valB - valA;
            case 5: // Expiry Date
                valA = parseInt(a.getAttribute('data-expiry-date') || 0, 10);
                valB = parseInt(b.getAttribute('data-expiry-date') || 0, 10);
                // Non-expiring documents placed at bottom
                if (valA === 0) return 1;
                if (valB === 0) return -1;
                return currentSortDirection === 'asc' ? valA - valB : valB - valA;
            default:
                valA = a.children[colIndex].textContent.trim();
                valB = b.children[colIndex].textContent.trim();
        }

        const cmp = String(valA).localeCompare(String(valB), undefined, { numeric: true, sensitivity: 'base' });
        return currentSortDirection === 'asc' ? cmp : -cmp;
    });

    // Re-append sorted rows to tbody
    rows.forEach(row => tableBody.appendChild(row));

    // Keep noResultsRow at bottom
    const noResultsRow = document.getElementById('noResultsRow');
    if (noResultsRow) tableBody.appendChild(noResultsRow);
}
</script>
@endsection
