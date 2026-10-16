/**
 * Roles & Permissions Management Module
 */

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

// Role permission mapping for instant client-side default calculation
const rolePermissionsMap = window.HrRolesConfig?.rolePermissionsMap || {};

let currentEditingEmployee = null;

// Tab switcher between Roles and Employee Permissions
function switchSubTab(tabName) {
    const rolesSection = document.getElementById('viewRolesSection');
    const employeesSection = document.getElementById('viewEmployeesSection');
    const tabBtnRoles = document.getElementById('tabBtnRoles');
    const tabBtnEmployees = document.getElementById('tabBtnEmployees');

    if (tabName === 'employees') {
        rolesSection.style.display = 'none';
        employeesSection.style.display = 'block';
        tabBtnRoles.classList.remove('active');
        tabBtnEmployees.classList.add('active');
    } else {
        rolesSection.style.display = 'flex';
        employeesSection.style.display = 'none';
        tabBtnEmployees.classList.remove('active');
        tabBtnRoles.classList.add('active');
    }

    // Update URL query parameter without full reload
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url);
}

// Select All / Deselect All within a specific Role card
function toggleRoleCardAll(formId, checked) {
    const form = document.getElementById(formId);
    if (!form) return;
    const checkboxes = form.querySelectorAll('input[name="permissions[]"]');
    checkboxes.forEach(cb => { cb.checked = checked; });
}

// Select All / Deselect All within Add Role Modal
function toggleNewRolePerms(checked) {
    const cbs = document.querySelectorAll('#addRoleModal .new-role-perm-chk');
    cbs.forEach(cb => { cb.checked = checked; });
}

// Auto-generate slug from role name
function autoGenerateSlug(name) {
    const slugInput = document.getElementById('newRoleSlug');
    if (!slugInput) return;
    const slug = name.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-');
    slugInput.value = slug;
}


// Failsafe button handler that extracts employee data cleanly
function openEmployeePermissionsFromBtn(btn) {
    try {
        const emp = {
            id: btn.getAttribute('data-emp-id'),
            full_name: btn.getAttribute('data-emp-name'),
            employee_id: btn.getAttribute('data-emp-code'),
            position: btn.getAttribute('data-emp-position') || 'Staff',
            branch: btn.getAttribute('data-emp-branch') || 'Universal Access',
            role_name: btn.getAttribute('data-emp-role-name') || 'Staff',
            role_slug: btn.getAttribute('data-emp-role-slug') || 'staff',
            initials: btn.getAttribute('data-emp-initials') || 'EM',
            photo_url: btn.getAttribute('data-emp-photo') || null,
            has_user: btn.getAttribute('data-emp-has-user') === '1',
            username: btn.getAttribute('data-emp-username') || '',
            direct_permission_ids: JSON.parse(btn.getAttribute('data-emp-perms') || '[]'),
        };
        openEmployeePermissionsModal(emp);
    } catch (e) {
        console.error('Failed to parse employee data:', e);
    }
}

// Open Right Drawer Modal for Employee Permissions
function openEmployeePermissionsModal(emp) {
    currentEditingEmployee = emp;

    // Set form action
    const form = document.getElementById('employeePermissionsForm');
    form.action = `/hr/admin/roles/employees/${emp.id}/permissions`;

    // Populate header info
    document.getElementById('modalEmpName').textContent = emp.full_name;
    document.getElementById('modalEmpId').textContent = emp.employee_id;
    document.getElementById('modalEmpPosition').textContent = emp.position || 'Staff';
    document.getElementById('modalEmpBranch').textContent = emp.branch || 'Universal';

    // Avatar
    const avatarWrap = document.getElementById('modalEmpAvatarWrap');
    if (emp.photo_url) {
        avatarWrap.innerHTML = `<img src="${emp.photo_url}" alt="${emp.full_name}" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 1.5px solid #cbd5e1;">`;
    } else {
        avatarWrap.innerHTML = `<div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #9333ea); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px;">${emp.initials}</div>`;
    }

    // Role & user badges
    const roleBadge = document.getElementById('modalEmpRoleBadge');
    roleBadge.textContent = emp.role_name;

    const userBadge = document.getElementById('modalEmpUserBadge');
    if (emp.has_user) {
        userBadge.innerHTML = `<i class="ph ph-check" style="color: #10b981;"></i> @${emp.username}`;
    } else {
        userBadge.innerHTML = `<span style="color: #94a3b8; font-style: italic;">No Login Account</span>`;
    }

    // Determine default role permissions for highlighting
    const defaultRolePerms = rolePermissionsMap[emp.role_slug] || [];

    // Reset search
    document.getElementById('modalPermissionSearch').value = '';
    filterModalPermissions('');

    // Update checkboxes and role pills
    const allCheckboxes = document.querySelectorAll('#editEmployeePermissionsModal .emp-perm-chk');
    const assignedIds = Array.isArray(emp.direct_permission_ids) ? emp.direct_permission_ids.map(Number) : [];

    allCheckboxes.forEach(cb => {
        const permId = Number(cb.getAttribute('data-perm-id'));
        cb.checked = assignedIds.includes(permId);

        // Role default pill
        const pill = document.querySelector(`.role-default-pill[data-perm-id="${permId}"]`);
        if (pill) {
            if (defaultRolePerms.includes(permId)) {
                pill.style.display = 'inline-flex';
                pill.title = `Included in default ${emp.role_name} role`;
            } else {
                pill.style.display = 'none';
            }
        }
    });

    updateModalCounts();
    openModal('editEmployeePermissionsModal');
}

// Update modal count badges
function updateModalCounts() {
    const allCheckboxes = document.querySelectorAll('#editEmployeePermissionsModal .emp-perm-chk');
    let totalChecked = 0;

    allCheckboxes.forEach(cb => {
        if (cb.checked) totalChecked++;
    });

    document.getElementById('modalGrantedCount').textContent = totalChecked;

    // Update per-module badges
    document.querySelectorAll('.modal-module-card').forEach(card => {
        const cbs = card.querySelectorAll('.emp-perm-chk');
        const checkedInModule = card.querySelectorAll('.emp-perm-chk:checked').length;
        const badge = card.querySelector('.module-checked-badge');
        if (badge) {
            badge.textContent = `${checkedInModule}/${cbs.length}`;
            if (checkedInModule > 0) {
                badge.style.color = '#7c3aed';
                badge.style.background = 'rgba(124, 58, 237, 0.1)';
            } else {
                badge.style.color = '#64748b';
                badge.style.background = '#f8fafc';
            }
        }
    });
}

function checkAllModalPermissions(check) {
    const visibleCheckboxes = document.querySelectorAll('#editEmployeePermissionsModal .modal-perm-item:not([style*="display: none"]) .emp-perm-chk');
    visibleCheckboxes.forEach(cb => { cb.checked = check; });
    updateModalCounts();
}

function applyRoleDefaultsToModal() {
    if (!currentEditingEmployee) return;
    const defaultRolePerms = rolePermissionsMap[currentEditingEmployee.role_slug] || [];
    const allCheckboxes = document.querySelectorAll('#editEmployeePermissionsModal .emp-perm-chk');

    allCheckboxes.forEach(cb => {
        const permId = Number(cb.getAttribute('data-perm-id'));
        cb.checked = defaultRolePerms.includes(permId);
    });

    updateModalCounts();
}

// In-modal search filter
function filterModalPermissions(query) {
    const q = (query || '').toLowerCase().trim();
    const items = document.querySelectorAll('#editEmployeePermissionsModal .modal-perm-item');

    items.forEach(item => {
        const name = item.getAttribute('data-perm-name') || '';
        if (!q || name.includes(q)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });

    // Hide empty module cards
    document.querySelectorAll('.modal-module-card').forEach(card => {
        const visibleItems = card.querySelectorAll('.modal-perm-item:not([style*="display: none"])');
        card.style.display = visibleItems.length === 0 ? 'none' : 'block';
    });
}

// =========================================================================
// Employee Permissions Table Real-Time Filter
// =========================================================================
function filterEmployeeTable() {
    const searchVal = (document.getElementById('employeeSearchInput')?.value || '').toLowerCase().trim();
    const branchVal = (document.getElementById('branchFilterSelect')?.value || '').trim();
    const permVal = (document.getElementById('permStatusFilter')?.value || '').trim();

    const rows = document.querySelectorAll('#employeePermissionsTable tbody tr.emp-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const empId = row.getAttribute('data-emp-id') || '';
        const pos = row.getAttribute('data-position') || '';
        const branch = row.getAttribute('data-branch') || '';
        const role = row.getAttribute('data-role') || '';
        const hasCustom = row.getAttribute('data-has-custom') === 'true';
        const hasUser = row.getAttribute('data-has-user') === 'true';

        const matchesSearch = !searchVal || 
            name.includes(searchVal) || 
            empId.includes(searchVal) || 
            pos.includes(searchVal) || 
            role.includes(searchVal);

        const matchesBranch = !branchVal || branch === branchVal;

        let matchesPerm = true;
        if (permVal === 'custom') matchesPerm = hasCustom;
        else if (permVal === 'defaults') matchesPerm = !hasCustom;
        else if (permVal === 'has_user') matchesPerm = hasUser;

        if (matchesSearch && matchesBranch && matchesPerm) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const countElem = document.getElementById('displayedEmpCount');
    if (countElem) countElem.textContent = visibleCount;
}

function resetEmployeeFilters() {
    const s = document.getElementById('employeeSearchInput');
    if (s) s.value = '';
    const b = document.getElementById('branchFilterSelect');
    if (b) b.value = '';
    const p = document.getElementById('permStatusFilter');
    if (p) p.value = '';
    const sel = document.getElementById('rolesSortSelect');
    if (sel) sel.value = '';

    document.querySelectorAll('#employeePermissionsTable thead th.sortable').forEach(th => {
        th.classList.remove('sorted-asc', 'sorted-desc');
        const icon = th.querySelector('.sort-icon i');
        if (icon) icon.className = 'ph ph-arrows-down-up';
    });
    currentEmpSortCol = -1;

    filterEmployeeTable();
}

// =========================================================================
// Employee Table Column Sorting (Via Table Header Click or Toolbar Dropdown)
// =========================================================================
let currentEmpSortCol = -1;
let currentEmpSortDir = 'asc';

function applyRolesSortFromSelect(val) {
    if (!val) {
        document.querySelectorAll('#employeePermissionsTable thead th.sortable').forEach(th => {
            th.classList.remove('sorted-asc', 'sorted-desc');
            const icon = th.querySelector('.sort-icon i');
            if (icon) icon.className = 'ph ph-arrows-down-up';
        });
        currentEmpSortCol = -1;
        return;
    }

    const sortMap = {
        'name_asc': [0, 'text', 'asc'],
        'name_desc': [0, 'text', 'desc'],
        'pos_asc': [1, 'text', 'asc'],
        'branch_asc': [2, 'text', 'asc'],
        'role_asc': [3, 'text', 'asc'],
        'perms_desc': [4, 'number', 'desc'],
        'perms_asc': [4, 'number', 'asc'],
    };

    if (sortMap[val]) {
        sortEmployeesTable(sortMap[val][0], sortMap[val][1], sortMap[val][2]);
    }
}

function sortEmployeesTable(colIndex, dataType, forceDir = null) {
    const tableBody = document.querySelector('#employeePermissionsTable tbody');
    const rows = Array.from(tableBody.querySelectorAll('tr.emp-row'));
    const headers = document.querySelectorAll('#employeePermissionsTable thead th.sortable');

    if (forceDir) {
        currentEmpSortDir = forceDir;
        currentEmpSortCol = colIndex;
    } else {
        if (currentEmpSortCol === colIndex) {
            currentEmpSortDir = currentEmpSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            currentEmpSortCol = colIndex;
            currentEmpSortDir = 'asc';
        }
    }

    headers.forEach((th, idx) => {
        th.classList.remove('sorted-asc', 'sorted-desc');
        const icon = th.querySelector('.sort-icon i');
        if (icon) icon.className = 'ph ph-arrows-down-up';

        if (idx === colIndex) {
            th.classList.add(currentEmpSortDir === 'asc' ? 'sorted-asc' : 'sorted-desc');
            if (icon) icon.className = currentEmpSortDir === 'asc' ? 'ph ph-caret-up' : 'ph ph-caret-down';
        } else {
            th.classList.remove('sorted-asc', 'sorted-desc');
            if (icon) icon.className = 'ph ph-arrows-down-up';
        }
    });

    const sortSelect = document.getElementById('rolesSortSelect');
    if (sortSelect) {
        const keyMap = {
            '0_asc': 'name_asc', '0_desc': 'name_desc',
            '1_asc': 'pos_asc',
            '2_asc': 'branch_asc',
            '3_asc': 'role_asc',
            '4_desc': 'perms_desc', '4_asc': 'perms_asc'
        };
        sortSelect.value = keyMap[colIndex + '_' + currentEmpSortDir] || '';
    }

    rows.sort((a, b) => {
        let valA, valB;
        switch (colIndex) {
            case 0:
                valA = a.getAttribute('data-name') || '';
                valB = b.getAttribute('data-name') || '';
                break;
            case 1:
                valA = a.getAttribute('data-position') || '';
                valB = b.getAttribute('data-position') || '';
                break;
            case 2:
                valA = a.getAttribute('data-branch') || '';
                valB = b.getAttribute('data-branch') || '';
                break;
            case 3:
                valA = a.getAttribute('data-role') || '';
                valB = b.getAttribute('data-role') || '';
                break;
            case 4:
                valA = parseInt(a.getAttribute('data-permissions-count') || '0', 10);
                valB = parseInt(b.getAttribute('data-permissions-count') || '0', 10);
                return currentEmpSortDir === 'asc' ? valA - valB : valB - valA;
            default:
                valA = a.children[colIndex].textContent.trim();
                valB = b.children[colIndex].textContent.trim();
        }
        const cmp = String(valA).localeCompare(String(valB), undefined, { numeric: true, sensitivity: 'base' });
        return currentEmpSortDir === 'asc' ? cmp : -cmp;
    });

    rows.forEach(r => tableBody.appendChild(r));
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    // Check URL parameter for default tab
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tab') === 'employees') {
        switchSubTab('employees');
    }
});

// Global window exposure
Object.assign(window, {
    openModal,
    closeModal,
    switchSubTab,
    toggleRoleCardAll,
    toggleNewRolePerms,
    autoGenerateSlug,
    filterEmployeeTable,
    resetEmployeeFilters,
    openEmployeePermissionsFromBtn,
    openEmployeePermissionsModal,
    updateModalCounts,
    checkAllModalPermissions,
    applyRoleDefaultsToModal,
    filterModalPermissions,
    applyRolesSortFromSelect,
    sortEmployeesTable,
});
