<!-- In-Page Staff Members Modal Window -->
<style>
/* Modern Scrollbar for Table */
#membersModalTableWrapper::-webkit-scrollbar {
    width: 7px;
    height: 7px;
}
#membersModalTableWrapper::-webkit-scrollbar-track {
    background: #f8fafc;
}
#membersModalTableWrapper::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
#membersModalTableWrapper::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Modal Table Row Transitions */
#membersModalTableBody tr {
    transition: background-color 0.15s ease, transform 0.15s ease;
}
#membersModalTableBody tr:hover {
    background-color: #faf8ff !important;
}

/* Action Button */
.hr-modal-view-profile-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    height: 32px;
    font-size: 12px;
    font-weight: 600;
    color: #6d28d9;
    background: #f5f3ff;
    border: 1px solid #ddd6fe;
    border-radius: 8px;
    text-decoration: none;
    white-space: nowrap;
    box-shadow: 0 1px 2px rgba(109, 40, 217, 0.05);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.hr-modal-view-profile-btn:hover {
    background: #7c3aed;
    color: #ffffff;
    border-color: #7c3aed;
    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);
    transform: translateY(-1px);
}
.hr-modal-view-profile-btn i {
    font-size: 13px;
    transition: transform 0.2s;
}
.hr-modal-view-profile-btn:hover i {
    transform: translate(1px, -1px);
}

/* Table Header Sorting */
.modal-sort-th {
    cursor: pointer;
    user-select: none;
    transition: background 0.15s, color 0.15s;
}
.modal-sort-th:hover {
    background: #f1f5f9 !important;
    color: #7c3aed !important;
}
.modal-sort-th .sort-icon {
    display: inline-flex;
    align-items: center;
    margin-left: 6px;
    font-size: 12px;
    color: #94a3b8;
}
.modal-sort-th.sorted-asc,
.modal-sort-th.sorted-desc {
    color: #7c3aed !important;
    background: #f5f3ff !important;
}
.modal-sort-th.sorted-asc .sort-icon,
.modal-sort-th.sorted-desc .sort-icon {
    color: #7c3aed;
}

/* Modal Pagination Styles */
.modal-page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    min-width: 32px;
    height: 32px;
    padding: 0 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    transition: all 0.18s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.modal-page-btn:hover:not(:disabled) {
    background: #ffffff;
    color: #7c3aed;
    border-color: #a855f7;
    box-shadow: 0 2px 6px rgba(124, 58, 237, 0.12);
    transform: translateY(-1px);
}
.modal-page-btn.active {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
    color: #ffffff !important;
    border-color: #7c3aed !important;
    box-shadow: 0 4px 10px rgba(124, 58, 237, 0.3) !important;
    transform: translateY(-1px);
}
.modal-page-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #94a3b8;
    box-shadow: none;
    transform: none;
}

/* Fixed Docked Layout for Staff Members Roster Modal */
#membersRosterModal .hr-modal {
    max-width: min(1040px, 96vw) !important;
    width: 100% !important;
    height: 100vh !important;
    max-height: 100vh !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
}

#membersRosterModal .hr-modal-body,
#membersRosterModal #membersModalBody {
    padding: 0 !important;
    margin: 0 !important;
    gap: 0 !important;
    flex: 1 1 0% !important;
    min-height: 0 !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
    background: #ffffff !important;
}

#membersRosterModal #membersModalContent {
    flex: 1 1 0% !important;
    min-height: 0 !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
}

#membersRosterModal #membersModalTableWrapper {
    flex: 1 1 0% !important;
    min-height: 0 !important;
    max-height: none !important;
    overflow-y: auto !important;
    overflow-x: auto !important;
    position: relative !important;
}

#membersRosterModal #membersModalPaginationBar {
    flex-shrink: 0 !important;
    position: relative !important;
    z-index: 20 !important;
    background: #ffffff !important;
    border-top: 1px solid #e2e8f0 !important;
    box-shadow: 0 -2px 10px rgba(15, 23, 42, 0.04) !important;
    padding: 10px 26px !important;
    margin: 0 !important;
}

#membersRosterModal .hr-modal-footer {
    flex-shrink: 0 !important;
    margin-top: 0 !important;
    background: #f8fafc !important;
    border-top: 1px solid #e2e8f0 !important;
    padding: 12px 26px !important;
    position: relative !important;
    z-index: 20 !important;
}
</style>

<div id="membersRosterModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 1020px; width: 96%; max-height: 88vh; display: flex; flex-direction: column; border-radius: 18px; overflow: hidden; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.3); background: #ffffff; border: 1px solid rgba(226, 232, 240, 0.8);">
        
        <!-- Modal Header -->
        <div class="hr-modal-header" style="padding: 18px 26px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #fbf8ff 0%, #f3e8ff 100%); flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.28);">
                    <i class="ph ph-users-three" id="membersModalIcon"></i>
                </div>
                <div>
                    <h3 class="hr-modal-title" id="membersModalTitle" style="margin: 0; font-size: 18px; color: #1e1b4b; font-weight: 800; letter-spacing: -0.01em;">
                        Staff Members
                    </h3>
                    <div style="font-size: 13px; color: #6b21a8; font-weight: 600; margin-top: 2px; display: flex; align-items: center; gap: 6px;" id="membersModalSubtitle">
                        Loading roster...
                    </div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <span id="membersModalCountBadge" class="hr-badge hr-badge-purple" style="font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 20px; box-shadow: 0 2px 4px rgba(124, 58, 237, 0.15);">
                    ...
                </span>
                <button type="button" class="icon-btn" onclick="closeModal('membersRosterModal')" style="cursor: pointer; border: 1px solid #e2e8f0; background: #ffffff; width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #64748b; box-shadow: 0 1px 3px rgba(0,0,0,0.06); transition: all 0.15s ease;" onmouseover="this.style.color='#0f172a'; this.style.borderColor='#cbd5e1';" onmouseout="this.style.color='#64748b'; this.style.borderColor='#e2e8f0';" title="Close Window (Esc)">
                    <i class="ph ph-x"></i>
                </button>
            </div>
        </div>

        <!-- Search Bar & Live Counter Bar -->
        <div style="padding: 14px 26px; background: #ffffff; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; flex-shrink: 0;">
            <div style="position: relative; flex: 1; max-width: 440px;">
                <i class="ph ph-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; pointer-events: none;"></i>
                <input type="text" id="membersModalSearch" class="hr-input" placeholder="Search members by name, ID, position, branch..." oninput="filterMembersModalList()" style="width: 100%; box-sizing: border-box; padding-left: 40px; padding-right: 34px; height: 38px; font-size: 13px; border-radius: 10px; border: 1px solid #cbd5e1; background: #f8fafc; transition: all 0.2s;" onfocus="this.style.background='#ffffff'; this.style.borderColor='#a855f7'; this.style.boxShadow='0 0 0 3px rgba(168, 85, 247, 0.12)';" onblur="if(!this.value) this.style.background='#f8fafc'; this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                <button type="button" id="membersModalSearchClear" onclick="clearMembersModalSearch()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: none; color: #94a3b8; cursor: pointer; display: none; padding: 2px; font-size: 16px;" title="Clear search">
                    <i class="ph ph-x-circle"></i>
                </button>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="hr-badge hr-badge-neutral" style="font-size: 12px; font-weight: 600; padding: 5px 12px; border-radius: 20px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;" id="membersModalFilteredCount">
                    Showing all members
                </span>
            </div>
        </div>

        <!-- Body / Roster Table -->
        <div class="hr-modal-body" style="padding: 0; display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden; background: #ffffff;" id="membersModalBody">
            <div id="membersModalLoading" style="text-align: center; padding: 60px 20px; color: #7c3aed;">
                <i class="ph ph-spinner animate-spin" style="font-size: 40px;"></i>
                <div style="margin-top: 14px; font-size: 14px; font-weight: 600; color: #4b5563;">Loading members roster...</div>
            </div>

            <div id="membersModalContent" style="display: none; display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;">
                <!-- Actual Scrollable Table with Fixed Sticky Header -->
                <div id="membersModalTableWrapper" style="flex: 1; min-height: 0; max-height: 420px; overflow-y: auto; overflow-x: auto; position: relative;">
                    <table class="hr-table" style="margin: 0; width: 100%; min-width: 880px; border-collapse: separate; border-spacing: 0;">
                        <thead>
                            <tr style="font-size: 11.5px; text-transform: uppercase; color: #475569; letter-spacing: 0.05em;">
                                <th class="modal-sort-th" onclick="sortMembersModalBy('name')" style="position: sticky; top: 0; z-index: 10; background: #f8fafc; padding: 13px 20px; border-bottom: 2px solid #e2e8f0; box-shadow: inset 0 -1px 0 #e2e8f0; width: 27%;">
                                    <span>Employee</span>
                                    <span class="sort-icon"><i class="ph ph-arrows-down-up" id="sortIcon_name"></i></span>
                                </th>
                                <th class="modal-sort-th" onclick="sortMembersModalBy('position')" style="position: sticky; top: 0; z-index: 10; background: #f8fafc; padding: 13px 20px; border-bottom: 2px solid #e2e8f0; box-shadow: inset 0 -1px 0 #e2e8f0; width: 25%;">
                                    <span>Position / Roles</span>
                                    <span class="sort-icon"><i class="ph ph-arrows-down-up" id="sortIcon_position"></i></span>
                                </th>
                                <th class="modal-sort-th" onclick="sortMembersModalBy('department')" style="position: sticky; top: 0; z-index: 10; background: #f8fafc; padding: 13px 20px; border-bottom: 2px solid #e2e8f0; box-shadow: inset 0 -1px 0 #e2e8f0; width: 23%;">
                                    <span>Department & Branch</span>
                                    <span class="sort-icon"><i class="ph ph-arrows-down-up" id="sortIcon_department"></i></span>
                                </th>
                                <th class="modal-sort-th" onclick="sortMembersModalBy('status')" style="position: sticky; top: 0; z-index: 10; background: #f8fafc; padding: 13px 20px; border-bottom: 2px solid #e2e8f0; box-shadow: inset 0 -1px 0 #e2e8f0; width: 12%;">
                                    <span>Status</span>
                                    <span class="sort-icon"><i class="ph ph-arrows-down-up" id="sortIcon_status"></i></span>
                                </th>
                                <th style="position: sticky; top: 0; z-index: 10; background: #f8fafc; padding: 13px 20px; border-bottom: 2px solid #e2e8f0; box-shadow: inset 0 -1px 0 #e2e8f0; text-align: right; width: 13%; min-width: 140px;">
                                    <span>Action</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="membersModalTableBody">
                        </tbody>
                    </table>

                    <div id="membersModalEmpty" style="display: none; text-align: center; padding: 60px 20px; color: #94a3b8;">
                        <div style="width: 56px; height: 56px; border-radius: 50%; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 12px;">
                            <i class="ph ph-users"></i>
                        </div>
                        <div style="font-size: 15px; font-weight: 700; color: #475569;">No staff members found</div>
                        <div style="font-size: 12.5px; color: #94a3b8; margin-top: 4px;">There are no employees currently matching this criteria.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cohesive Table Pagination Bar - Fixed directly above Modal Footer -->
        <div id="membersModalPaginationBar" style="padding: 10px 26px; background: #ffffff; border-top: 1px solid #e2e8f0; display: none; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; flex-shrink: 0; position: relative; z-index: 20; box-shadow: 0 -2px 10px rgba(15, 23, 42, 0.04);">
            <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                <div class="hr-per-page-wrap" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; font-weight: 500;">
                    <span class="hr-per-page-label">Show</span>
                    <select id="membersModalPerPage" class="hr-per-page-select" onchange="changeMembersModalPerPage(this.value)">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="hr-per-page-label">per page</span>
                </div>
                <div class="hr-pagination-info" id="membersModalPaginationInfo" style="font-size: 12.5px; color: #64748b; font-weight: 500;">
                    Showing <strong>0 - 0</strong> of <strong>0</strong> members
                </div>
            </div>
            <div class="hr-pagination-nav" id="membersModalPaginationNav" style="display: inline-flex; align-items: center; gap: 6px;">
                <!-- Dynamic page buttons -->
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="hr-modal-footer" style="padding: 12px 26px; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: #f8fafc; flex-shrink: 0;">
            <div style="font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                <i class="ph ph-info" style="color: #7c3aed; font-size: 15px;"></i>
                <span>Click <strong>"View Profile"</strong> or an employee's name to inspect their full 201 file.</span>
            </div>
            <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('membersRosterModal')" style="padding: 0 20px; height: 34px; font-weight: 600; border-radius: 8px;">
                <span>Close</span>
            </button>
        </div>
    </div>
</div>

<script>
let _currentModalMembers = [];
let _filteredModalMembers = [];
let _currentModalPage = 1;
let _currentModalPerPage = 10; // Default minimum of 10
let _currentSortCol = null;
let _currentSortDir = 'asc';

function openMembersModal(type, id, entityName) {
    const modal = document.getElementById('membersRosterModal');
    if (!modal) return;

    // Reset UI State
    document.getElementById('membersModalTitle').innerText = 'Staff Members';
    document.getElementById('membersModalSubtitle').innerText = 'Loading ' + (entityName || '') + '...';
    document.getElementById('membersModalCountBadge').innerText = '...';
    document.getElementById('membersModalLoading').style.display = 'block';
    document.getElementById('membersModalContent').style.display = 'none';
    const paginationBar = document.getElementById('membersModalPaginationBar');
    if (paginationBar) paginationBar.style.display = 'none';
    document.getElementById('membersModalSearch').value = '';
    const clearBtn = document.getElementById('membersModalSearchClear');
    if (clearBtn) clearBtn.style.display = 'none';
    
    // Reset sorting
    _currentSortCol = null;
    _currentSortDir = 'asc';
    resetSortIcons();

    // Reset pagination to 10 per page, page 1
    _currentModalPage = 1;
    _currentModalPerPage = 10;
    const perPageSelect = document.getElementById('membersModalPerPage');
    if (perPageSelect) perPageSelect.value = '10';
    
    // Choose entity icon
    const iconMap = {
        'department': 'ph-tree-structure',
        'position': 'ph-identification-badge',
        'branch': 'ph-map-pin',
        'company': 'ph-buildings',
        'agency': 'ph-briefcase'
    };
    const iconEl = document.getElementById('membersModalIcon');
    if (iconEl) iconEl.className = 'ph ' + (iconMap[type] || 'ph-users-three');

    if (typeof openModal === 'function') {
        openModal('membersRosterModal');
    } else {
        modal.classList.add('open');
    }

    // Fetch members via AJAX
    fetch(`{{ route('hr.people.members-modal') }}?type=${type}&id=${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('membersModalLoading').style.display = 'none';
            document.getElementById('membersModalContent').style.display = 'flex';

            if (!data.success) {
                alert('Could not load members.');
                return;
            }

            document.getElementById('membersModalTitle').innerText = data.title;
            document.getElementById('membersModalSubtitle').innerHTML = `
                <i class="ph ph-check-circle" style="color: #7c3aed;"></i>
                <span>${data.count} assigned staff member${data.count === 1 ? '' : 's'}</span>
            `;
            document.getElementById('membersModalCountBadge').innerText = `${data.count} staff`;

            _currentModalMembers = data.members || [];
            _filteredModalMembers = [..._currentModalMembers];
            _currentModalPage = 1;
            renderMembersModalPage();
        })
        .catch(err => {
            console.error('Error fetching members:', err);
            document.getElementById('membersModalLoading').innerHTML = `
                <div style="color: #ef4444; padding: 30px;">
                    <i class="ph ph-warning-circle" style="font-size: 32px;"></i>
                    <div style="margin-top: 8px; font-weight: 600;">Failed to load members roster.</div>
                </div>
            `;
        });
}

function renderMembersModalPage() {
    const tbody = document.getElementById('membersModalTableBody');
    const empty = document.getElementById('membersModalEmpty');
    const filteredCountEl = document.getElementById('membersModalFilteredCount');
    const paginationInfo = document.getElementById('membersModalPaginationInfo');
    const paginationNav = document.getElementById('membersModalPaginationNav');
    const paginationBar = document.getElementById('membersModalPaginationBar');
    if (!tbody) return;

    tbody.innerHTML = '';
    const total = _filteredModalMembers.length;

    if (total === 0) {
        tbody.style.display = 'none';
        if (empty) empty.style.display = 'block';
        if (filteredCountEl) filteredCountEl.innerText = '0 members found';
        if (paginationInfo) paginationInfo.innerHTML = 'Showing <strong>0</strong> of <strong>0</strong> members';
        if (paginationNav) paginationNav.innerHTML = '';
        if (paginationBar) paginationBar.style.display = 'none';
        return;
    }

    tbody.style.display = '';
    if (empty) empty.style.display = 'none';
    if (paginationBar) paginationBar.style.display = 'flex';

    const totalPages = Math.ceil(total / _currentModalPerPage) || 1;
    if (_currentModalPage > totalPages) _currentModalPage = totalPages;
    if (_currentModalPage < 1) _currentModalPage = 1;

    const startIdx = (_currentModalPage - 1) * _currentModalPerPage;
    const endIdx = Math.min(startIdx + _currentModalPerPage, total);
    const pageItems = _filteredModalMembers.slice(startIdx, endIdx);

    // Update Counts & Labels
    if (filteredCountEl) {
        filteredCountEl.innerText = _filteredModalMembers.length === _currentModalMembers.length
            ? `Showing all ${_filteredModalMembers.length} members`
            : `Filtered: ${_filteredModalMembers.length} of ${_currentModalMembers.length} members`;
    }
    if (paginationInfo) {
        paginationInfo.innerHTML = `Showing <strong>${startIdx + 1} - ${endIdx}</strong> of <strong>${total}</strong> members`;
    }

    // Render Rows
    pageItems.forEach((m, idx) => {
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid #f1f5f9';

        // Avatar
        const avatarHtml = m.profile_photo_url
            ? `<img src="${m.profile_photo_url}" alt="${m.full_name}" style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 2px solid #e2e8f0; flex-shrink: 0;">`
            : `<div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #8b5cf6 0%, #d946ef 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; flex-shrink: 0; box-shadow: 0 3px 8px rgba(139, 92, 246, 0.25);">${m.initials}</div>`;

        // Positions badges
        let posHtml = `<div style="font-weight: 700; color: #1e293b; font-size: 13.5px;">${m.position}</div>`;
        if (m.positions && m.positions.length > 1) {
            posHtml += `<div style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px;">`;
            m.positions.forEach(p => {
                if (p.is_primary) {
                    posHtml += `<span class="hr-badge hr-badge-purple" style="font-size: 10.5px; padding: 2px 7px; border-radius: 6px;">${p.name} (Primary)</span>`;
                } else {
                    posHtml += `<span class="hr-badge hr-badge-neutral" style="font-size: 10.5px; padding: 2px 7px; background: #f1f5f9; color: #475569; border-radius: 6px;">${p.name}</span>`;
                }
            });
            posHtml += `</div>`;
        }

        // Status badge
        let statusBadge = '<span class="hr-badge hr-badge-success" style="font-size: 11.5px; padding: 3px 10px; border-radius: 20px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;"><i class="ph ph-check-circle" style="font-size: 12px;"></i>Active</span>';
        if (m.employment_status === 'Probationary') {
            statusBadge = '<span class="hr-badge hr-badge-warning" style="font-size: 11.5px; padding: 3px 10px; border-radius: 20px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;"><i class="ph ph-clock" style="font-size: 12px;"></i>Probationary</span>';
        } else if (m.employment_status === 'On Leave') {
            statusBadge = '<span class="hr-badge hr-badge-info" style="font-size: 11.5px; padding: 3px 10px; border-radius: 20px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;"><i class="ph ph-calendar" style="font-size: 12px;"></i>On Leave</span>';
        } else if (['Resigned', 'Terminated', 'Retired', 'Suspended'].includes(m.employment_status)) {
            statusBadge = `<span class="hr-badge hr-badge-danger" style="font-size: 11.5px; padding: 3px 10px; border-radius: 20px; font-weight: 700;">${m.employment_status}</span>`;
        }

        const employmentTypeSubtext = (m.employment_type && m.employment_type !== m.employment_status)
            ? `<div style="font-size: 11.5px; color: #64748b; margin-top: 3px; font-weight: 500;">${m.employment_type}</div>`
            : '';

        tr.innerHTML = `
            <td style="padding: 12px 20px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <a href="${m.profile_url}" target="_blank" rel="noopener noreferrer" style="text-decoration: none; flex-shrink: 0;" title="View ${m.full_name}'s Profile">
                        ${avatarHtml}
                    </a>
                    <div style="min-width: 0;">
                        <a href="${m.profile_url}" target="_blank" rel="noopener noreferrer" style="font-weight: 700; color: #0f172a; font-size: 14px; text-decoration: none; display: block; line-height: 1.3;" onmouseover="this.style.color='#7c3aed'" onmouseout="this.style.color='#0f172a'" title="View ${m.full_name}'s Profile">
                            ${m.full_name}
                        </a>
                        <span style="display: inline-block; padding: 1px 7px; border-radius: 5px; background: #f3f0ff; color: #7c3aed; font-family: monospace; font-size: 11.5px; font-weight: 600; margin-top: 3px;">${m.employee_id}</span>
                    </div>
                </div>
            </td>
            <td style="padding: 12px 20px;">
                ${posHtml}
            </td>
            <td style="padding: 12px 20px;">
                <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #334155;">
                    <i class="ph ph-tree-structure" style="color: #8b5cf6; font-size: 14px;"></i>
                    <span>${m.department}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; margin-top: 3px;">
                    <i class="ph ph-map-pin" style="color: #94a3b8; font-size: 13px;"></i>
                    <span>${m.branch}</span>
                </div>
            </td>
            <td style="padding: 12px 20px;">
                ${statusBadge}
                ${employmentTypeSubtext}
            </td>
            <td style="padding: 12px 20px; text-align: right; width: 140px; min-width: 140px; white-space: nowrap;">
                <a href="${m.profile_url}" target="_blank" rel="noopener noreferrer" class="hr-modal-view-profile-btn" title="View ${m.full_name}'s Profile in new window">
                    <span>View Profile</span>
                    <i class="ph ph-arrow-square-out"></i>
                </a>
            </td>
        `;
        tbody.appendChild(tr);
    });

    // Render Pagination Controls
    renderPaginationButtons(totalPages);
}

function renderPaginationButtons(totalPages) {
    const nav = document.getElementById('membersModalPaginationNav');
    if (!nav) return;
    nav.innerHTML = '';

    // Always render Prev Button
    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'modal-page-btn';
    prevBtn.innerHTML = '<i class="ph ph-caret-left"></i>';
    prevBtn.disabled = (_currentModalPage === 1);
    prevBtn.title = 'Previous Page';
    prevBtn.onclick = () => {
        if (_currentModalPage > 1) {
            _currentModalPage--;
            renderMembersModalPage();
            scrollModalTableToTop();
        }
    };
    nav.appendChild(prevBtn);

    // Page Number Buttons
    for (let p = 1; p <= totalPages; p++) {
        if (totalPages > 7) {
            if (p !== 1 && p !== totalPages && Math.abs(p - _currentModalPage) > 1) {
                if (p === 2 || p === totalPages - 1) {
                    const dots = document.createElement('span');
                    dots.style.cssText = 'padding: 0 4px; color: #94a3b8; font-size: 12px;';
                    dots.innerText = '...';
                    nav.appendChild(dots);
                }
                continue;
            }
        }

        const pageBtn = document.createElement('button');
        pageBtn.type = 'button';
        pageBtn.className = 'modal-page-btn' + (p === _currentModalPage ? ' active' : '');
        pageBtn.innerText = p;
        pageBtn.onclick = () => {
            _currentModalPage = p;
            renderMembersModalPage();
            scrollModalTableToTop();
        };
        nav.appendChild(pageBtn);
    }

    // Always render Next Button
    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'modal-page-btn';
    nextBtn.innerHTML = '<i class="ph ph-caret-right"></i>';
    nextBtn.disabled = (_currentModalPage === totalPages);
    nextBtn.title = 'Next Page';
    nextBtn.onclick = () => {
        if (_currentModalPage < totalPages) {
            _currentModalPage++;
            renderMembersModalPage();
            scrollModalTableToTop();
        }
    };
    nav.appendChild(nextBtn);
}

function changeMembersModalPerPage(val) {
    _currentModalPerPage = parseInt(val, 10) || 10;
    _currentModalPage = 1;
    renderMembersModalPage();
    scrollModalTableToTop();
}

function scrollModalTableToTop() {
    const wrapper = document.getElementById('membersModalTableWrapper');
    if (wrapper) wrapper.scrollTop = 0;
}

function filterMembersModalList() {
    const searchInput = document.getElementById('membersModalSearch');
    const clearBtn = document.getElementById('membersModalSearchClear');
    const q = (searchInput?.value || '').toLowerCase().trim();

    if (clearBtn) {
        clearBtn.style.display = q ? 'block' : 'none';
    }

    if (!q) {
        _filteredModalMembers = [..._currentModalMembers];
    } else {
        _filteredModalMembers = _currentModalMembers.filter(m => {
            return (m.full_name || '').toLowerCase().includes(q) ||
                   (m.employee_id || '').toLowerCase().includes(q) ||
                   (m.position || '').toLowerCase().includes(q) ||
                   (m.department || '').toLowerCase().includes(q) ||
                   (m.branch || '').toLowerCase().includes(q) ||
                   (m.employment_status || '').toLowerCase().includes(q) ||
                   (m.company || '').toLowerCase().includes(q);
        });
    }

    // Preserve sorting if active
    if (_currentSortCol) {
        applyCurrentSort();
    }

    _currentModalPage = 1;
    renderMembersModalPage();
    scrollModalTableToTop();
}

function clearMembersModalSearch() {
    const searchInput = document.getElementById('membersModalSearch');
    if (searchInput) {
        searchInput.value = '';
    }
    filterMembersModalList();
}

/* Sorting Logic */
function sortMembersModalBy(col) {
    if (_currentSortCol === col) {
        _currentSortDir = (_currentSortDir === 'asc') ? 'desc' : 'asc';
    } else {
        _currentSortCol = col;
        _currentSortDir = 'asc';
    }

    applyCurrentSort();
    renderMembersModalPage();
}

function applyCurrentSort() {
    if (!_currentSortCol) return;

    resetSortIcons();
    const th = document.querySelector(`.modal-sort-th[onclick*="'${_currentSortCol}'"]`);
    const icon = document.getElementById(`sortIcon_${_currentSortCol}`);

    if (th && icon) {
        th.classList.add(_currentSortDir === 'asc' ? 'sorted-asc' : 'sorted-desc');
        icon.className = _currentSortDir === 'asc' ? 'ph ph-caret-up' : 'ph ph-caret-down';
    }

    _filteredModalMembers.sort((a, b) => {
        let valA = '';
        let valB = '';

        if (_currentSortCol === 'name') {
            valA = (a.full_name || '').toLowerCase();
            valB = (b.full_name || '').toLowerCase();
        } else if (_currentSortCol === 'position') {
            valA = (a.position || '').toLowerCase();
            valB = (b.position || '').toLowerCase();
        } else if (_currentSortCol === 'department') {
            valA = (a.department || '').toLowerCase();
            valB = (b.department || '').toLowerCase();
        } else if (_currentSortCol === 'status') {
            valA = (a.employment_status || '').toLowerCase();
            valB = (b.employment_status || '').toLowerCase();
        }

        if (valA < valB) return _currentSortDir === 'asc' ? -1 : 1;
        if (valA > valB) return _currentSortDir === 'asc' ? 1 : -1;
        return 0;
    });
}

function resetSortIcons() {
    ['name', 'position', 'department', 'status'].forEach(col => {
        const th = document.querySelector(`.modal-sort-th[onclick*="'${col}'"]`);
        const icon = document.getElementById(`sortIcon_${col}`);
        if (th) {
            th.classList.remove('sorted-asc', 'sorted-desc');
        }
        if (icon) {
            icon.className = 'ph ph-arrows-down-up';
        }
    });
}
</script>
