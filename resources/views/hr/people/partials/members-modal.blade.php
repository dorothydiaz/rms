@push('styles')
    @vite('resources/css/modules/hr-members-modal.css')
@endpush

@push('blade-data')
<script>
    window.HrMembersModalConfig = Object.freeze({
        routes: {
            membersModal: @json(route('hr.people.members-modal')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-members-modal.js')
@endpush

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
