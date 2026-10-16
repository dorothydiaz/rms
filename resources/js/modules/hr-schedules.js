// -------------------------------------------------------------
// Constants and Client Memory
// -------------------------------------------------------------
const CSRF_TOKEN = window.HrSchedulesConfig?.csrfToken || '';
const QUICK_ASSIGN_URL = window.HrSchedulesConfig?.routes?.quickAssign || '';
const COPY_WEEK_URL = window.HrSchedulesConfig?.routes?.copyWeek || '';
const QUICK_FILL_ROW_URL = window.HrSchedulesConfig?.routes?.quickFillRow || '';
const UPDATE_DEPT_URL = window.HrSchedulesConfig?.routes?.updateDept || '';
const BATCH_SCHEDULE_URL = window.HrSchedulesConfig?.routes?.batchSchedule || '';
let draftSchedules = {}; // Key: "empId_date", Value: { employee_id, date, shift_template_id, is_rest_day, custom_start_time, custom_end_time, notes, is_draft }
let savedOriginalCells = {}; // Key: "empId_date", Value: original innerHTML before draft
let batchSelectedEmpIds = [];
let batchFillScope = 'all'; // 'all' or 'custom'
const ALL_EMPLOYEES = window.HrSchedulesConfig?.allEmployees || [];
const ALL_DEPARTMENTS = window.HrSchedulesConfig?.departmentsJson || [];
const DEPT_COLORS = {
    'management': '#6366f1',
    'front of house': '#3b82f6',
    'back of house': '#10b981',
    'finance & admin': '#f59e0b',
    'finance': '#f59e0b',
    'hr': '#8b5cf6',
    'human resources': '#8b5cf6',
    'it': '#0ea5e9',
    'information technology': '#0ea5e9'
};
const SHIFT_TEMPLATES = window.HrSchedulesConfig?.shiftTemplates || {};

// Default shift templates per category (matching Schedule.html)
const DEFAULT_SHIFT_TEMPLATES = {
    'default': {
        'O': { label: 'OPENING', start: '08:00', end: '17:00' },
        'MD': { label: 'MID DAY', start: '10:00', end: '19:00' },
        'LD': { label: 'LATE DAY', start: '12:00', end: '21:00' },
        'C': { label: 'CLOSING', start: '13:00', end: '22:00' },
        'OFF': { label: 'RESTDAY', start: 'OFF', end: 'OFF' }
    }
};

let shiftTemplatesStore = {};
function initShiftTemplatesStore() {
    try {
        const saved = localStorage.getItem('RMS_SHIFT_TEMPLATES');
        if (saved) {
            shiftTemplatesStore = JSON.parse(saved);
        } else {
            shiftTemplatesStore = JSON.parse(JSON.stringify(DEFAULT_SHIFT_TEMPLATES));
        }
    } catch(e) {
        shiftTemplatesStore = JSON.parse(JSON.stringify(DEFAULT_SHIFT_TEMPLATES));
    }
}
initShiftTemplatesStore();

function getShiftConfig(category, code) {
    const catKey = (category || 'default').toLowerCase();
    const config = shiftTemplatesStore[catKey] || shiftTemplatesStore['default'] || DEFAULT_SHIFT_TEMPLATES['default'];
    return config[code] || { label: code, start: '08:00', end: '17:00' };
}

// -------------------------------------------------------------
// Interactive Department Cycler for Employee Pill
// -------------------------------------------------------------
function cycleEmployeeDepartment(empId, direction, btn) {
    if (btn) {
        btn.disabled = true;
        setTimeout(() => { btn.disabled = false; }, 250);
    }
    const pill = document.getElementById(`empDeptPill_${empId}`);
    if (!pill || !ALL_DEPARTMENTS || ALL_DEPARTMENTS.length === 0) return;

    // Check employee's assigned departments
    const empObj = (typeof ALL_EMPLOYEES !== 'undefined' && Array.isArray(ALL_EMPLOYEES)) 
        ? ALL_EMPLOYEES.find(item => item.id == empId) 
        : null;

    let availableDepts = ALL_DEPARTMENTS;
    if (empObj && Array.isArray(empObj.all_department_ids) && empObj.all_department_ids.length > 0) {
        const allowedIds = empObj.all_department_ids.map(Number);
        const filtered = ALL_DEPARTMENTS.filter(d => allowedIds.includes(Number(d.id)));
        if (filtered.length > 0) {
            availableDepts = filtered;
        }
    }
    if (availableDepts.length <= 1) {
        return; // Only 1 or 0 departments assigned, no cycling
    }

    const currentDept = (pill.getAttribute('data-current-dept') || pill.querySelector('.sched-dept-pill-text')?.textContent || '').trim().toLowerCase();
    let currIdx = availableDepts.findIndex(d => d.name.trim().toLowerCase() === currentDept);
    if (currIdx === -1) currIdx = 0;

    let newIdx = (currIdx + direction + availableDepts.length) % availableDepts.length;
    const nextDept = availableDepts[newIdx];

    // 1. Immediately update UI
    pill.setAttribute('data-current-dept', nextDept.name);
    const textSpan = pill.querySelector('.sched-dept-pill-text');
    if (textSpan) textSpan.textContent = nextDept.name;
    pill.setAttribute('title', `Department: ${nextDept.name} (Click < or > to cycle)`);

    const dot = pill.querySelector('.sched-cat-dot');
    if (dot) {
        dot.style.backgroundColor = DEPT_COLORS[nextDept.name.toLowerCase()] || '#7c3aed';
    }

    // 2. Update Row and Cells attributes
    const row = document.getElementById(`schedRow_${empId}`);
    if (row) {
        row.setAttribute('data-emp-cat', nextDept.name.toLowerCase());
        row.querySelectorAll('.sched-cell-td').forEach(c => c.setAttribute('data-emp-cat', nextDept.name.toLowerCase()));
    }

    // 3. Update cached employee in ALL_EMPLOYEES array
    if (typeof ALL_EMPLOYEES !== 'undefined' && Array.isArray(ALL_EMPLOYEES)) {
        const e = ALL_EMPLOYEES.find(item => item.id == empId);
        if (e) {
            if (!e.department) e.department = {};
            e.department.id = nextDept.id;
            e.department.name = nextDept.name;
            e.department_id = nextDept.id;
        }
    }

    // 4. Auto-update list visibility if category filter or search is active!
    filterMatrixByCategory();
    if (typeof filterMatrixRowsBySearch === 'function') {
        filterMatrixRowsBySearch();
    }

    // 5. Toast feedback
    showSchedToast(`Department updated to ${nextDept.name}`);

    // 6. Persist to database via AJAX
    fetch(UPDATE_DEPT_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            employee_id: empId,
            department_id: nextDept.id,
            department_name: nextDept.name
        })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            console.warn('Server warning updating department:', data);
        }
    })
    .catch(err => {
        console.error('Error updating department:', err);
    });
}

// -------------------------------------------------------------
// Floating Cell Custom Schedule Popover
// -------------------------------------------------------------
let activeCellEmpId = null;
let activeCellDate = null;

function switchScheduleManagerTab(tabKey, btnElement = null) {
    document.querySelectorAll('.sched-mgr-tab-panel').forEach(p => {
        p.classList.remove('active');
        p.style.display = 'none';
    });
    document.querySelectorAll('.sched-mgr-tab-btn').forEach(b => b.classList.remove('active'));

    const panel = document.getElementById(`tabPanel_${tabKey}`);
    if (panel) {
        panel.classList.add('active');
        panel.style.display = 'flex';
    }

    let btn = btnElement || document.getElementById(`tabBtn_${tabKey}`);
    if (btn) btn.classList.add('active');
}

function openScheduleManagerTab(tabKey) {
    if (tabKey === 'fill_grid' && !activeCellEmpId) {
        openBatchFillGridModal();
        return;
    }
    if (tabKey === 'shift_settings' || tabKey === 'category_defaults') {
        openShiftMasterModal();
        return;
    }
    const dd = document.getElementById('cellCustomDropdown');
    const backdrop = document.getElementById('cellCustomBackdrop');
    if (!dd) return;

    if (tabKey === 'add_template') {
        tabKey = 'add_shift';
    }

    switchScheduleManagerTab(tabKey);

    // Connect schedule owner if no active cell
    if (!activeCellEmpId) {
        const firstRow = document.querySelector('.sched-row[data-emp-id]');
        if (firstRow) {
            const fEmpId = firstRow.getAttribute('data-emp-id');
            const fName = firstRow.querySelector('.sched-emp-name-text')?.textContent || firstRow.getAttribute('data-emp-name') || 'Employee';
            const fDept = firstRow.getAttribute('data-emp-cat') || 'Department';
            const fBranch = firstRow.getAttribute('data-emp-branch') || 'Branch';
            const fInitials = firstRow.querySelector('.sched-emp-avatar')?.textContent?.trim() || fName.substring(0, 2).toUpperCase();

            const ownerAvatar = document.getElementById('fillGridOwnerAvatar');
            if (ownerAvatar) ownerAvatar.textContent = fInitials;
            const ownerName = document.getElementById('fillGridOwnerName');
            if (ownerName) ownerName.textContent = fName.trim();
            const ownerDept = document.getElementById('fillGridOwnerDept');
            if (ownerDept) ownerDept.innerHTML = `<span style="text-transform: capitalize;">${fDept}</span> &bull; <span style="text-transform: capitalize;">${fBranch}</span>`;
            const submitName = document.getElementById('fillGridSubmitName');
            if (submitName) submitName.textContent = fName.trim().split(' ')[0];
            const empHidden = document.getElementById('schedEmployeeIdModal');
            if (empHidden) empHidden.value = fEmpId;
        }
        const cText = document.getElementById('cellCustomContextText');
        if (cText) cText.textContent = 'Manage shifts, schedules, and templates';
    }

    dd.style.visibility = 'hidden';
    dd.style.display = 'flex';

    const ddHeight = dd.offsetHeight || 520;
    const ddWidth = dd.offsetWidth || 390;
    const viewportHeight = window.innerHeight;
    const viewportWidth = window.innerWidth;

    const left = Math.max(12, Math.round((viewportWidth - ddWidth) / 2));
    const top = Math.max(12, Math.round((viewportHeight - ddHeight) / 2));

    dd.style.top = `${top}px`;
    dd.style.left = `${left}px`;
    dd.style.visibility = 'visible';

    if (backdrop) backdrop.style.display = 'block';
}

function openCellCustomDropdown(empId, date) {
    const dd = document.getElementById('cellCustomDropdown');
    const backdrop = document.getElementById('cellCustomBackdrop');
    // If clicking the same active cell that is already open, toggle it closed
    if (dd && dd.style.display !== 'none' && activeCellEmpId === empId && activeCellDate === date) {
        closeCellCustomDropdown();
        return;
    }

    activeCellEmpId = empId;
    activeCellDate = date;

    const cell = document.getElementById(`cell_${empId}_${date}`);
    if (!cell || !dd) return;

    // Default to first tab: shift_custom
    switchScheduleManagerTab('shift_custom');

    // Context text with employee name and date
    const row = document.getElementById(`schedRow_${empId}`);
    const empName = row ? (row.querySelector('.sched-emp-name-text')?.textContent || row.getAttribute('data-emp-name') || 'Employee') : 'Employee';
    const empDept = row ? (row.getAttribute('data-emp-cat') || 'Department') : 'Department';
    const empBranch = row ? (row.getAttribute('data-emp-branch') || 'Branch') : 'Branch';
    const initials = row ? (row.querySelector('.sched-emp-avatar')?.textContent?.trim() || empName.substring(0, 2).toUpperCase()) : 'EM';

    const cText = document.getElementById('cellCustomContextText');
    if (cText) {
        cText.innerHTML = `<span style="color:#7c3aed;font-weight:700;"><i class="ph ph-user"></i> ${empName.trim()}</span> &bull; <span>${date}</span>`;
    }

    // Connect schedule owner to Fill Grid tab (No option for All!)
    const ownerAvatar = document.getElementById('fillGridOwnerAvatar');
    if (ownerAvatar) ownerAvatar.textContent = initials;
    const ownerName = document.getElementById('fillGridOwnerName');
    if (ownerName) ownerName.textContent = empName.trim();
    const ownerDept = document.getElementById('fillGridOwnerDept');
    if (ownerDept) ownerDept.innerHTML = `<span style="text-transform: capitalize;">${empDept}</span> &bull; <span style="text-transform: capitalize;">${empBranch}</span>`;
    const submitName = document.getElementById('fillGridSubmitName');
    if (submitName) submitName.textContent = empName.trim().split(' ')[0];

    const empHidden = document.getElementById('schedEmployeeIdModal');
    if (empHidden) empHidden.value = empId;

    const startDateInput = document.getElementById('schedStartDateModal');
    if (startDateInput) {
        startDateInput.value = date;
        const endDateInput = document.getElementById('schedEndDateModal');
        if (endDateInput && (!endDateInput.value || endDateInput.value < date)) {
            endDateInput.value = date;
        }
    }

    // Show invisibly to measure real height and width
    dd.style.visibility = 'hidden';
    dd.style.display = 'flex';

    const rect = cell.getBoundingClientRect();
    const ddHeight = dd.offsetHeight || 520;
    const ddWidth = dd.offsetWidth || 390;
    const viewportHeight = window.innerHeight;
    const viewportWidth = window.innerWidth;

    // Horizontal side placement: Open to the right of the cell by default, or flip to the left if near screen edge
    const spaceRight = viewportWidth - rect.right;
    const spaceLeft = rect.left;

    let left;
    if (spaceRight >= ddWidth + 10 || spaceRight >= spaceLeft) {
        // Sufficient room on right side of the cell
        left = rect.right + 10;
    } else {
        // Not enough room on the right -> flip to the left side of the cell
        left = rect.left - ddWidth - 10;
    }
    // Prevent clipping past viewport edges
    left = Math.max(12, Math.min(left, viewportWidth - ddWidth - 12));

    // Vertical placement: Align with cell top, clamped so window never overflows top/bottom of screen
    let top = rect.top - 6;
    if (top + ddHeight > viewportHeight - 12) {
        top = Math.max(12, viewportHeight - ddHeight - 12);
    }
    if (top < 12) {
        top = 12;
    }

    dd.style.top = `${Math.round(top)}px`;
    dd.style.left = `${Math.round(left)}px`;
    dd.style.visibility = 'visible';

    if (backdrop) backdrop.style.display = 'block';
}

function closeCellCustomDropdown() {
    const dd = document.getElementById('cellCustomDropdown');
    const backdrop = document.getElementById('cellCustomBackdrop');
    if (dd) {
        dd.style.display = 'none';
        dd.style.visibility = 'hidden';
    }
    if (backdrop) {
        backdrop.style.display = 'none';
    }
    activeCellEmpId = null;
    activeCellDate = null;
}

function syncDateRangeModal() {
    var start = document.getElementById('schedStartDateModal')?.value;
    var end = document.getElementById('schedEndDateModal')?.value;
    if (start && (!end || end < start)) {
        document.getElementById('schedEndDateModal').value = start;
    }
}

function presetDateRangeModal(type) {
    var now = new Date();
    var formatDate = function(d) {
        var year = d.getFullYear();
        var month = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    };
    var start = new Date();
    var end = new Date();
    if (type === 'today') {
    } else if (type === 'this_week') {
        var dayOfWeek = now.getDay();
        var diffToMon = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
        start.setDate(now.getDate() + diffToMon);
        end.setDate(start.getDate() + 6);
    } else if (type === 'next_week') {
        var dayOfWeek = now.getDay();
        var diffToMon = dayOfWeek === 0 ? 1 : 8 - dayOfWeek;
        start.setDate(now.getDate() + diffToMon);
        end.setDate(start.getDate() + 6);
    } else if (type === 'month') {
        start = new Date(now.getFullYear(), now.getMonth(), 1);
        end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    }
    var sInput = document.getElementById('schedStartDateModal');
    var eInput = document.getElementById('schedEndDateModal');
    if (sInput) sInput.value = formatDate(start);
    if (eInput) eInput.value = formatDate(end);
}

function setRestDaysPresetModal(days) {
    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(function(d) {
        var cb = document.getElementById('rest_day_modal_' + d);
        if (cb) cb.checked = days.includes(d);
    });
}

function clearRestDaysPresetModal() {
    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(function(d) {
        var cb = document.getElementById('rest_day_modal_' + d);
        if (cb) cb.checked = false;
    });
}

function filterRegisteredShifts(query) {
    const q = (query || '').toLowerCase().trim();
    document.querySelectorAll('.sched-dd-shift-item').forEach(btn => {
        const name = (btn.getAttribute('data-shift-name') || '').toLowerCase();
        const code = (btn.getAttribute('data-shift-code') || '').toLowerCase();
        if (!q || name.includes(q) || code.includes(q)) {
            btn.style.display = 'flex';
        } else {
            btn.style.display = 'none';
        }
    });
}

function updateShiftPreviewModal() {
    const start = document.getElementById('newShiftStartModal')?.value || '06:00';
    const end = document.getElementById('newShiftEndModal')?.value || '15:00';
    const code = start.replace(':', '');
    const to12h = (t) => {
        let [h, m] = t.split(':').map(Number);
        let ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return m === 0 ? `${h}${ampm}` : `${h}:${m < 10 ? '0' : ''}${m}${ampm}`;
    };
    const prev = document.getElementById('shiftFormatPreviewModal');
    if (prev) {
        prev.textContent = `${code} = ${to12h(start)} - ${to12h(end)}`;
    }
}

function openEditShiftTemplateModal(id, code, start, end, isOvernight, breakMins, color) {
    const modal = document.getElementById('editShiftTemplateModal');
    if (!modal) return;

    document.getElementById('editShiftId').value = id;
    document.getElementById('editShiftStartModal').value = start;
    document.getElementById('editShiftEndModal').value = end;
    document.getElementById('editIsOvernightModal').checked = !!isOvernight;
    document.getElementById('editShiftBreakModal').value = breakMins || 60;
    document.getElementById('editShiftColorModal').value = color || '#7c3aed';
    
    const subTitle = document.getElementById('editShiftModalSubtitle');
    if (subTitle) {
        subTitle.textContent = `Editing shift template code: ${code}`;
    }

    const form = document.getElementById('editShiftTemplateForm');
    if (form) {
        form.action = `/hr/attendance/shifts/${id}`;
    }

    updateEditShiftPreviewModal();
    openModal('editShiftTemplateModal');
}

function updateEditShiftPreviewModal() {
    const start = document.getElementById('editShiftStartModal')?.value || '06:00';
    const end = document.getElementById('editShiftEndModal')?.value || '15:00';
    const code = start.replace(':', '');
    const to12h = (t) => {
        let [h, m] = t.split(':').map(Number);
        let ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return m === 0 ? `${h}${ampm}` : `${h}:${m < 10 ? '0' : ''}${m}${ampm}`;
    };
    const prev = document.getElementById('editShiftFormatPreviewModal');
    if (prev) {
        prev.textContent = `${code} = ${to12h(start)} - ${to12h(end)}`;
    }
}

function deleteShiftTemplate(id, name) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete Shift Template?',
            html: `Are you sure you want to delete shift template <strong>"${escapeHtml(name)}"</strong>?<br><br><span style="font-size: 12.5px; color: #64748b;">Existing schedules will keep their times, but this template preset will be removed.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="ph ph-trash"></i> Yes, Delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                confirmButton: 'swal2-danger'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('deleteShiftTemplateForm');
                if (form) {
                    form.action = `/hr/attendance/shifts/${id}`;
                    form.submit();
                }
            }
        });
    } else {
        if (!confirm(`Are you sure you want to delete shift template "${name}"?\n\nExisting schedules will keep their times, but this template preset will be removed.`)) {
            return;
        }
        const form = document.getElementById('deleteShiftTemplateForm');
        if (form) {
            form.action = `/hr/attendance/shifts/${id}`;
            form.submit();
        }
    }
}

function deleteShiftTemplateFromModal() {
    const id = document.getElementById('editShiftId')?.value;
    const name = document.getElementById('editShiftFormatPreviewModal')?.textContent || 'this shift';
    if (!id) return;
    closeModal('editShiftTemplateModal');
    deleteShiftTemplate(id, name);
}

document.addEventListener('click', function(e) {
    const dd = document.getElementById('cellCustomDropdown');
    if (dd && dd.style.display !== 'none') {
        if (!e.target.closest('#cellCustomDropdown') && !e.target.closest('.sched-pill-custom') && !e.target.closest('.sched-shift-card') && !e.target.closest('.sched-top-actions') && !e.target.closest('#editShiftTemplateModal')) {
            closeCellCustomDropdown();
        }
    }
});

window.addEventListener('resize', function() {
    closeCellCustomDropdown();
});

document.addEventListener('scroll', function(e) {
    const dd = document.getElementById('cellCustomDropdown');
    if (dd && dd.style.display !== 'none' && !e.target.closest('#cellCustomDropdown')) {
        closeCellCustomDropdown();
    }
}, true);

function applyCustomTimeToActiveCell() {
    if (!activeCellEmpId || !activeCellDate) return;
    const start = document.getElementById('customCellStart').value || '08:00';
    const end = document.getElementById('customCellEnd').value || '17:00';
    const label = document.getElementById('customCellLabel').value.trim() || 'CUSTOM';

    const empId = activeCellEmpId;
    const date = activeCellDate;
    closeCellCustomDropdown();

    const cell = document.getElementById(`cell_${empId}_${date}`);
    const prevHTML = cell.innerHTML;

    // Optimistic UI
    renderAssignedCardHTML(cell, empId, date, 'CUSTOM', label, `${start} - ${end}`, 'sched-card-custom');

    fetch(QUICK_ASSIGN_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            employee_id: empId,
            date: date,
            custom_start_time: start,
            custom_end_time: end,
            notes: label,
            is_rest_day: false
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showSchedToast(`Applied ${label} (${start} - ${end})`);
        } else {
            cell.innerHTML = prevHTML;
            showSchedToast(data.message || 'Failed to apply custom shift', false);
        }
    })
    .catch(err => {
        cell.innerHTML = prevHTML;
        showSchedToast('Error saving custom shift', false);
    });
}

function applyTemplateToActiveCell(templateId, code, name, start, end, color) {
    if (!activeCellEmpId || !activeCellDate) return;
    const empId = activeCellEmpId;
    const date = activeCellDate;
    closeCellCustomDropdown();

    const cell = document.getElementById(`cell_${empId}_${date}`);
    const prevHTML = cell.innerHTML;

    renderAssignedCardHTML(cell, empId, date, code || 'TMPL', name, `${start} - ${end}`, 'sched-card-custom', color);

    fetch(QUICK_ASSIGN_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            employee_id: empId,
            date: date,
            shift_template_id: templateId,
            is_rest_day: false
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showSchedToast(`Assigned ${code || name} on ${date}`);
        } else {
            cell.innerHTML = prevHTML;
            showSchedToast(data.message, false);
        }
    })
    .catch(() => {
        cell.innerHTML = prevHTML;
        showSchedToast('Network error saving shift', false);
    });
}

// -------------------------------------------------------------
// Direct 1-Click Preset Plotting (O, MD, LD, C, OFF)
// -------------------------------------------------------------
function directPlotPreset(empId, date, code) {
    const cell = document.getElementById(`cell_${empId}_${date}`);
    if (!cell) return;

    const prevHTML = cell.innerHTML;
    const empCat = cell.getAttribute('data-emp-cat') || 'default';
    const cfg = getShiftConfig(empCat, code);

    const isOff = code === 'OFF';
    const sLabel = isOff ? 'RESTDAY' : (cfg.label || code);
    const sTime = isOff ? 'OFF DUTY' : `${cfg.start} - ${cfg.end}`;
    const themeClass = isOff ? 'sched-card-rest' : `sched-theme-${code.toLowerCase()}`;

    // Render Optimistic Card
    renderAssignedCardHTML(cell, empId, date, code, sLabel, sTime, themeClass);

    fetch(QUICK_ASSIGN_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            employee_id: empId,
            date: date,
            shift_code: code,
            custom_start_time: isOff ? null : cfg.start,
            custom_end_time: isOff ? null : cfg.end,
            notes: sLabel,
            is_rest_day: isOff
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showSchedToast(`Assigned ${sLabel} (${sTime})`);
        } else {
            cell.innerHTML = prevHTML;
            showSchedToast(data.message || 'Failed to plot shift', false);
        }
    })
    .catch(err => {
        cell.innerHTML = prevHTML;
        showSchedToast('Network error while plotting shift', false);
    });
}

function clearCellShift(empId, date) {
    const draftKey = `${empId}_${date}`;
    const cell = document.getElementById(`cell_${empId}_${date}`);
    if (!cell) return;

    if (typeof draftSchedules !== 'undefined' && draftSchedules[draftKey]) {
        delete draftSchedules[draftKey];
        if (typeof savedOriginalCells !== 'undefined' && savedOriginalCells[draftKey]) {
            cell.innerHTML = savedOriginalCells[draftKey];
            delete savedOriginalCells[draftKey];
        } else {
            renderEmptyCellHTML(cell, empId, date);
        }
        updateSaveButtonState();
        showSchedToast('Draft shift removed');
        return;
    }

    const prevHTML = cell.innerHTML;
    renderEmptyCellHTML(cell, empId, date);

    fetch(QUICK_ASSIGN_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            employee_id: empId,
            date: date,
            clear: true
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showSchedToast('Shift cleared successfully');
        } else {
            cell.innerHTML = prevHTML;
            showSchedToast(data.message, false);
        }
    })
    .catch(() => {
        cell.innerHTML = prevHTML;
        showSchedToast('Network error clearing shift', false);
    });
}

// -------------------------------------------------------------
// Card & Empty Cell HTML Renderers
// -------------------------------------------------------------
function classifyShift(rawCode, rawLabel, timeStr) {
    const code = (rawCode || '').trim().toUpperCase();
    const label = (rawLabel || '').trim();
    const upperLabel = label.toUpperCase();

    // Extract start time "HH:MM"
    let startTime = '';
    if (timeStr && timeStr.includes('-')) {
        startTime = timeStr.split('-')[0].trim().substring(0, 5);
    } else if (timeStr && timeStr.includes(':')) {
        startTime = timeStr.trim().substring(0, 5);
    }

    // 1. Rest Day
    if (code === 'OFF' || upperLabel === 'RESTDAY' || upperLabel.includes('REST') || upperLabel.includes('OFF')) {
        return { code: 'OFF', label: 'RESTDAY', theme: 'sched-card-rest', isCustom: false };
    }

    // 2. Explicit Preset Codes & Labels (Exact matching takes highest priority)
    if (code === 'MD' || code.includes('MID') || upperLabel.includes('MID')) {
        return { code: 'MD', label: label || 'MID DAY', theme: 'sched-theme-md', isCustom: false };
    }
    if (code === 'LD' || code.includes('LATE') || upperLabel.includes('LATE')) {
        return { code: 'LD', label: label || 'LATE DAY', theme: 'sched-theme-ld', isCustom: false };
    }
    if (code === 'C' || code.includes('CLOS') || upperLabel.includes('CLOS')) {
        return { code: 'C', label: label || 'CLOSING', theme: 'sched-theme-c', isCustom: false };
    }
    if (code === 'O' || code.includes('OPEN') || upperLabel.includes('OPEN')) {
        return { code: 'O', label: label || 'OPENING', theme: 'sched-theme-o', isCustom: false };
    }

    // 3. Military / Numeric 4-digit codes
    if (['0600', '0700', '0800', '0900'].includes(code)) {
        return { code: 'O', label: 'OPENING', theme: 'sched-theme-o', isCustom: false };
    }
    if (['1000', '1100'].includes(code)) {
        return { code: 'MD', label: 'MID DAY', theme: 'sched-theme-md', isCustom: false };
    }
    if (['1200'].includes(code)) {
        return { code: 'LD', label: 'LATE DAY', theme: 'sched-theme-ld', isCustom: false };
    }
    if (['1300', '1400', '1500', '1600', '1700', '1800', '1900', '2000', '2100', '2200', '2300'].includes(code)) {
        return { code: 'C', label: 'CLOSING', theme: 'sched-theme-c', isCustom: false };
    }

    // 4. Fallback by start time ONLY if code and label are empty/unspecified
    if (!code && !label && startTime) {
        if (startTime < '10:00') {
            return { code: 'O', label: 'OPENING', theme: 'sched-theme-o', isCustom: false };
        }
        if (startTime < '12:00') {
            return { code: 'MD', label: 'MID DAY', theme: 'sched-theme-md', isCustom: false };
        }
        if (startTime < '13:00') {
            return { code: 'LD', label: 'LATE DAY', theme: 'sched-theme-ld', isCustom: false };
        }
        return { code: 'C', label: 'CLOSING', theme: 'sched-theme-c', isCustom: false };
    }

    // 5. Custom Shift
    return { code: code || 'CUSTOM', label: label || 'CUSTOM', theme: 'sched-card-custom', isCustom: true };
}

function renderAssignedCardHTML(cell, empId, date, rawCode, rawLabel, time, themeClass = null, customColor = null) {
    const classified = classifyShift(rawCode, rawLabel, time);
    const code = classified.code;
    const label = classified.label;
    const finalTheme = classified.theme;
    const displayTime = (classified.code === 'OFF') ? 'OFF DUTY' : time;

    let codeStyle = '';
    let nameStyle = '';
    if (classified.isCustom && customColor) {
        codeStyle = `style="background: ${customColor}; color: #ffffff;"`;
        nameStyle = `style="color: ${customColor};"`;
    }

    let detailBoxHTML = '';
    if (classified.code === 'OFF') {
        detailBoxHTML = `
            <div class="sched-card-detail-box">
                <div class="sched-detail-row">
                    <span class="sched-detail-dot dot-no-inout">●</span>
                    <span class="sched-detail-punch">Rest Day</span>
                    <span class="sched-detail-badge badge-off-duty">OFF DUTY</span>
                </div>
                <div class="sched-detail-row sched-detail-hours">
                    <span>Hours Worked:</span>
                    <strong>0.00 hrs</strong>
                </div>
            </div>
        `;
    } else {
        detailBoxHTML = `
            <div class="sched-card-detail-box">
                <div class="sched-detail-row">
                    <span class="sched-detail-dot dot-planned">●</span>
                    <span class="sched-detail-punch">Planned</span>
                    <span class="sched-detail-badge badge-planned">SCHEDULED</span>
                </div>
                <div class="sched-detail-row sched-detail-hours">
                    <span>Target Hours:</span>
                    <strong>8.00 hrs</strong>
                </div>
            </div>
        `;
    }

    cell.innerHTML = `
        <div class="sched-shift-card ${finalTheme}" onclick="openCellCustomDropdown(${empId}, '${date}')">
            <div class="sched-card-top">
                <div class="sched-badge-wrap">
                    <span class="sched-badge-code" ${codeStyle}>${escapeHtml(code)}</span>
                    <span class="sched-badge-name" ${nameStyle}>${escapeHtml(label)}</span>
                </div>
                <button type="button" class="sched-card-clear" onclick="event.stopPropagation(); clearCellShift(${empId}, '${date}')" title="Clear Shift">
                    &times;
                </button>
            </div>
            <div class="sched-card-time">
                <i class="ph ph-clock"></i>
                <span>${escapeHtml(displayTime)}</span>
            </div>
            ${detailBoxHTML}
        </div>
    `;
}

function renderEmptyCellHTML(cell, empId, date) {
    cell.innerHTML = `
        <div class="sched-empty-plotter">
            <div class="sched-preset-grid">
                <div class="sched-btn-row">
                    <button type="button" class="sched-mini-pill sched-pill-o" onclick="directPlotPreset(${empId}, '${date}', 'O')" title="OPENING Shift">O</button>
                    <button type="button" class="sched-mini-pill sched-pill-md" onclick="directPlotPreset(${empId}, '${date}', 'MD')" title="MID DAY Shift">MD</button>
                    <button type="button" class="sched-mini-pill sched-pill-ld" onclick="directPlotPreset(${empId}, '${date}', 'LD')" title="LATE DAY Shift">LD</button>
                </div>
                <div class="sched-btn-row">
                    <button type="button" class="sched-mini-pill sched-pill-c" onclick="directPlotPreset(${empId}, '${date}', 'C')" title="CLOSING Shift">C</button>
                    <button type="button" class="sched-mini-pill sched-pill-restday" onclick="directPlotPreset(${empId}, '${date}', 'OFF')" title="RESTDAY (Off Duty)">RESTDAY</button>
                </div>
                <div class="sched-btn-row">
                    <button type="button" class="sched-mini-pill sched-pill-custom" onclick="openCellCustomDropdown(${empId}, '${date}')" title="Select other shift or enter custom time">
                        Custom <i class="ph ph-caret-down" style="font-size: 8px;"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

// -------------------------------------------------------------
// Shift Master Settings Modal Logic
// -------------------------------------------------------------
let activeModalCat = 'default';

function openShiftMasterModal(preferredDept = null) {
    let targetTab = null;
    const tabs = document.querySelectorAll('#shiftModalCatTabs .sched-cat-tab-btn');
    if (preferredDept) {
        for (const tab of tabs) {
            if (tab.textContent.trim().toLowerCase() === preferredDept.trim().toLowerCase()) {
                targetTab = tab;
                break;
            }
        }
    }
    if (!targetTab && tabs.length > 0) {
        targetTab = tabs[0];
    }
    if (targetTab) {
        tabs.forEach(b => b.classList.remove('active'));
        targetTab.classList.add('active');
        activeModalCat = targetTab.textContent.trim().toLowerCase();
        document.getElementById('modalActiveCategoryLabel').textContent = targetTab.textContent.trim();
    }
    loadShiftCategoryIntoModal(activeModalCat);
    openModal('shiftMasterSettingsModal');
}

function switchShiftModalCategory(catName, btn) {
    document.querySelectorAll('.sched-cat-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeModalCat = catName.toLowerCase();
    document.getElementById('modalActiveCategoryLabel').textContent = catName;
    loadShiftCategoryIntoModal(activeModalCat);
}

function loadShiftCategoryIntoModal(catKey) {
    const config = shiftTemplatesStore[catKey] || shiftTemplatesStore['default'] || DEFAULT_SHIFT_TEMPLATES['default'];
    ['O', 'MD', 'LD', 'C'].forEach(code => {
        const item = config[code] || DEFAULT_SHIFT_TEMPLATES['default'][code];
        if (item) {
            const startEl = document.getElementById(`shiftTime_${code}_start`);
            const endEl = document.getElementById(`shiftTime_${code}_end`);
            const lblEl = document.getElementById(`lblShift_${code}`);
            if (startEl) startEl.value = item.start;
            if (endEl) endEl.value = item.end;
            if (lblEl) lblEl.textContent = item.label;
        }
    });
}

function saveShiftMasterSettings() {
    if (!shiftTemplatesStore[activeModalCat]) {
        shiftTemplatesStore[activeModalCat] = JSON.parse(JSON.stringify(DEFAULT_SHIFT_TEMPLATES['default']));
    }

    ['O', 'MD', 'LD', 'C'].forEach(code => {
        const startVal = document.getElementById(`shiftTime_${code}_start`)?.value || '08:00';
        const endVal = document.getElementById(`shiftTime_${code}_end`)?.value || '17:00';
        shiftTemplatesStore[activeModalCat][code] = {
            label: code === 'O' ? 'OPENING' : (code === 'MD' ? 'MID DAY' : (code === 'LD' ? 'LATE DAY' : 'CLOSING')),
            start: startVal,
            end: endVal
        };
    });

    try {
        localStorage.setItem('RMS_SHIFT_TEMPLATES', JSON.stringify(shiftTemplatesStore));
    } catch(e) {
        console.error(e);
    }

    closeModal('shiftMasterSettingsModal');
    showSchedToast('Shift Master Settings saved successfully!');
}

function resetShiftCategoryDefaults() {
    shiftTemplatesStore[activeModalCat] = JSON.parse(JSON.stringify(DEFAULT_SHIFT_TEMPLATES['default']));
    loadShiftCategoryIntoModal(activeModalCat);
    showSchedToast(`Reset ${activeModalCat} to default shift times`);
}

// -------------------------------------------------------------
// Multi-Filter "Add Employee Row" Referenced Filtering Method
// -------------------------------------------------------------
let selectedEmpIdsForAddition = [];

function toggleEmpDropdown() {
    const p = document.getElementById('empDropdownPanel');
    const chevron = document.getElementById('empDropdownChevron');
    if (!p) return;
    if (p.style.display === 'none') {
        p.style.display = 'flex';
        chevron.style.transform = 'rotate(180deg)';
        renderDropdownEmployeeList();
    } else {
        closeEmpDropdown();
    }
}

function closeEmpDropdown() {
    const p = document.getElementById('empDropdownPanel');
    const chevron = document.getElementById('empDropdownChevron');
    if (p) p.style.display = 'none';
    if (chevron) chevron.style.transform = 'rotate(0deg)';
}

document.addEventListener('click', function(e) {
    const wrap = document.getElementById('addEmpDropdownWrap');
    if (wrap && !wrap.contains(e.target)) {
        closeEmpDropdown();
    }
});

function isEmpAlreadyInGrid(empId) {
    return document.getElementById(`schedRow_${empId}`) !== null;
}

function renderDropdownEmployeeList() {
    const listEl = document.getElementById('dropdownEmpList');
    if (!listEl) return;

    const q = (document.getElementById('modalEmpSearch')?.value || '').toLowerCase().trim();
    const statusVal = (document.getElementById('modalStatusFilter')?.value || '').toLowerCase().trim();
    const catVal = (document.getElementById('modalCategoryFilter')?.value || '').toLowerCase().trim();

    const filtered = ALL_EMPLOYEES.filter(emp => {
        const name = (emp.full_name || `${emp.first_name} ${emp.last_name}`).toLowerCase();
        const code = (emp.employee_id || '').toLowerCase();
        const branch = (emp.branch?.name || '').toLowerCase();
        const pos = (emp.position?.name || '').toLowerCase();
        const dept = (emp.department?.name || '').toLowerCase();
        const status = (emp.employment_status || '').toLowerCase();

        const matchQ = !q || name.includes(q) || code.includes(q) || branch.includes(q) || pos.includes(q) || dept.includes(q);
        const matchStatus = !statusVal || status === statusVal;
        const matchCat = !catVal || dept.includes(catVal) || branch.includes(catVal);

        return matchQ && matchStatus && matchCat;
    });

    document.getElementById('dropdownEmpMatchText').textContent = `${filtered.length} employee(s) matching filter`;

    if (filtered.length === 0) {
        listEl.innerHTML = `
            <div style="padding: 24px; text-align: center; color: #94a3b8; font-size: 12px;">
                <i class="ph ph-magnifying-glass" style="font-size: 22px; display: block; margin-bottom: 4px;"></i>
                No employees matching filter criteria
            </div>
        `;
        return;
    }

    let html = '';
    filtered.slice(0, 40).forEach(emp => {
        const inGrid = isEmpAlreadyInGrid(emp.id);
        const isSelected = selectedEmpIdsForAddition.includes(emp.id);
        const name = emp.full_name || `${emp.first_name} ${emp.last_name}`;
        const initials = emp.initials || name.substring(0, 2).toUpperCase();
        const branch = emp.branch?.name || 'Main';
        const pos = emp.position?.name || 'Staff';

        html += `
            <div class="sched-emp-row-item ${inGrid ? 'already-added' : ''} ${isSelected ? 'selected' : ''}" 
                 onclick="toggleEmpAdditionSelection(${emp.id})">
                <div style="display: flex; align-items: center; gap: 8px; min-width: 0; flex: 1;">
                    <input type="checkbox" ${inGrid ? 'checked disabled' : (isSelected ? 'checked' : '')} 
                           onclick="event.stopPropagation(); toggleEmpAdditionSelection(${emp.id})" 
                           style="cursor: pointer;">
                    <div class="sched-emp-avatar" style="width: 28px; height: 28px; font-size: 10px;">${initials}</div>
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-weight: 700; font-size: 12px; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            ${escapeHtml(name)} <small style="color: #94a3b8; font-family: monospace;">(${escapeHtml(emp.employee_id || '')})</small>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 6px; align-items: center; flex-shrink: 0;">
                    <span style="font-size: 10px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; border-radius: 4px; padding: 1px 5px; display: flex; align-items: center; gap: 3px;">
                        <i class="ph ph-map-pin"></i> ${escapeHtml(branch)}
                    </span>
                    <span style="font-size: 10px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 4px; padding: 1px 5px;">
                        ${escapeHtml(pos)}
                    </span>

                    ${inGrid ? `
                        <span style="font-size: 9.5px; font-weight: 800; background: #dcfce7; color: #15803d; border-radius: 4px; padding: 2px 6px;">
                            <i class="ph ph-check"></i> In Grid
                        </span>
                    ` : `
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="font-size: 10px; padding: 2px 8px;" onclick="event.stopPropagation(); addSingleEmpToGrid(${emp.id})">
                            + Add
                        </button>
                    `}
                </div>
            </div>
        `;
    });

    listEl.innerHTML = html;
    updateAdditionFooterCounts();
}

function filterDropdownEmployees() {
    renderDropdownEmployeeList();
}

function clearModalEmpSearch() {
    const inp = document.getElementById('modalEmpSearch');
    if (inp) inp.value = '';
    renderDropdownEmployeeList();
}

function resetDropdownFilters() {
    const inp = document.getElementById('modalEmpSearch');
    if (inp) inp.value = '';
    const st = document.getElementById('modalStatusFilter');
    if (st) st.value = '';
    const ct = document.getElementById('modalCategoryFilter');
    if (ct) ct.value = '';
    renderDropdownEmployeeList();
}

function toggleEmpAdditionSelection(empId) {
    if (isEmpAlreadyInGrid(empId)) return;
    const idx = selectedEmpIdsForAddition.indexOf(empId);
    if (idx >= 0) {
        selectedEmpIdsForAddition.splice(idx, 1);
    } else {
        selectedEmpIdsForAddition.push(empId);
    }
    renderDropdownEmployeeList();
}

function toggleSelectAllFilteredEmps() {
    const unaddedFiltered = ALL_EMPLOYEES.filter(emp => !isEmpAlreadyInGrid(emp.id));
    const allSelected = unaddedFiltered.every(e => selectedEmpIdsForAddition.includes(e.id));

    if (allSelected) {
        selectedEmpIdsForAddition = [];
    } else {
        unaddedFiltered.forEach(e => {
            if (!selectedEmpIdsForAddition.includes(e.id)) {
                selectedEmpIdsForAddition.push(e.id);
            }
        });
    }
    renderDropdownEmployeeList();
}

function updateAdditionFooterCounts() {
    const count = selectedEmpIdsForAddition.length;
    const countBadge = document.getElementById('selectedEmpBadge');
    if (countBadge) {
        countBadge.style.display = count > 0 ? 'inline-block' : 'none';
        countBadge.textContent = count;
    }
    const footerCount = document.getElementById('footerSelectedCount');
    if (footerCount) footerCount.textContent = count;
    const addCountText = document.getElementById('selectedAdditionCount');
    if (addCountText) addCountText.textContent = `${count} selected for addition`;
    const btn = document.getElementById('btnAddSelectedEmps');
    if (btn) btn.disabled = count === 0;
}

function addSingleEmpToGrid(empId) {
    const emp = ALL_EMPLOYEES.find(e => e.id == empId);
    if (!emp || isEmpAlreadyInGrid(empId)) return;

    appendEmployeeRowToTable(emp);
    renderDropdownEmployeeList();
    showSchedToast(`Added ${emp.full_name || emp.first_name} to grid`);
}

function addSelectedEmployeesToGrid() {
    if (selectedEmpIdsForAddition.length === 0) return;
    let added = 0;
    selectedEmpIdsForAddition.forEach(id => {
        const emp = ALL_EMPLOYEES.find(e => e.id == id);
        if (emp && !isEmpAlreadyInGrid(id)) {
            appendEmployeeRowToTable(emp);
            added++;
        }
    });

    selectedEmpIdsForAddition = [];
    updateAdditionFooterCounts();
    closeEmpDropdown();
    showSchedToast(`Added ${added} employee(s) to grid`);
}

function appendEmployeeRowToTable(emp) {
    const tbody = document.getElementById('schedMatrixTbody');
    const emptyRow = document.getElementById('schedEmptyRow');
    if (emptyRow) emptyRow.style.display = 'none';

    const name = emp.full_name || `${emp.first_name} ${emp.last_name}`;
    const initials = emp.initials || name.substring(0, 2).toUpperCase();
    const branch = emp.branch?.name || 'Main';
    const pos = emp.position?.name || 'Staff';
    const cat = emp.department?.name || 'Front of House';
    const currentWeekVal = document.getElementById('schedWeekInput')?.value || (window.HrSchedulesConfig?.weekStart || '');

    const tr = document.createElement('tr');
    tr.className = 'sched-row';
    tr.id = `schedRow_${emp.id}`;
    tr.setAttribute('data-emp-id', emp.id);
    tr.setAttribute('data-emp-name', name.toLowerCase());
    tr.setAttribute('data-emp-branch', branch.toLowerCase());
    tr.setAttribute('data-emp-pos', pos.toLowerCase());
    tr.setAttribute('data-emp-cat', cat.toLowerCase());

    const dates = window.HrSchedulesConfig?.dates || [];
    let cellsHTML = '';

    dates.forEach(d => {
        cellsHTML += `
            <td class="sched-cell-td" id="cell_${emp.id}_${d}" data-emp-id="${emp.id}" data-date="${d}" data-emp-cat="${cat.toLowerCase()}">
                <div class="sched-empty-plotter">
                    <div class="sched-preset-grid">
                        <div class="sched-btn-row">
                            <button type="button" class="sched-mini-pill sched-pill-o" onclick="directPlotPreset(${emp.id}, '${d}', 'O')" title="OPENING Shift">O</button>
                            <button type="button" class="sched-mini-pill sched-pill-md" onclick="directPlotPreset(${emp.id}, '${d}', 'MD')" title="MID DAY Shift">MD</button>
                            <button type="button" class="sched-mini-pill sched-pill-ld" onclick="directPlotPreset(${emp.id}, '${d}', 'LD')" title="LATE DAY Shift">LD</button>
                        </div>
                        <div class="sched-btn-row">
                            <button type="button" class="sched-mini-pill sched-pill-c" onclick="directPlotPreset(${emp.id}, '${d}', 'C')" title="CLOSING Shift">C</button>
                            <button type="button" class="sched-mini-pill sched-pill-restday" onclick="directPlotPreset(${emp.id}, '${d}', 'OFF')" title="RESTDAY (Off Duty)">RESTDAY</button>
                        </div>
                        <div class="sched-btn-row">
                            <button type="button" class="sched-mini-pill sched-pill-custom" onclick="openCellCustomDropdown(${emp.id}, '${d}')" title="Select other shift or enter custom time">
                                Custom <i class="ph ph-caret-down" style="font-size: 8px;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </td>
        `;
    });

    tr.innerHTML = `
        <td class="sched-sticky-col sched-sticky-td">
            <div style="display: flex; align-items: flex-start; gap: 8px;">
                <div class="sched-emp-avatar">${initials}</div>
                <div style="min-width: 0; flex: 1;">
                    <div style="font-weight: 600; color: #0f172a; font-size: 13.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.25;">
                        ${escapeHtml(name)}
                    </div>
                    <div style="margin: 3px 0;">
                        <div class="sched-dept-pill-wrapper">
                            ${(() => {
                                const hasMulti = !!(emp.has_multiple_departments || (Array.isArray(emp.all_department_ids) && emp.all_department_ids.length > 1));
                                if (hasMulti) {
                                    return `
                                        <button type="button" 
                                                class="sched-dept-nav-btn prev" 
                                                onclick="cycleEmployeeDepartment(${emp.id}, -1, this)" 
                                                title="Previous department (click to cycle)"
                                                aria-label="Previous department">
                                            <i class="ph ph-caret-left"></i>
                                        </button>
                                        <span class="sched-cat-pill" 
                                              id="empDeptPill_${emp.id}" 
                                              data-current-dept="${escapeHtml(cat)}" 
                                              data-emp-id="${emp.id}" 
                                              onclick="cycleEmployeeDepartment(${emp.id}, 1, this)" 
                                              style="cursor: pointer;"
                                              title="Department: ${escapeHtml(cat)} (Click &lt; or &gt; to cycle)">
                                            <span class="sched-cat-dot" style="background-color: ${DEPT_COLORS[cat.toLowerCase()] || '#7c3aed'};"></span>
                                            <span class="truncate sched-dept-pill-text">${escapeHtml(cat)}</span>
                                        </span>
                                        <button type="button" 
                                                class="sched-dept-nav-btn next" 
                                                onclick="cycleEmployeeDepartment(${emp.id}, 1, this)" 
                                                title="Next department (click to cycle)"
                                                aria-label="Next department">
                                            <i class="ph ph-caret-right"></i>
                                        </button>
                                    `;
                                } else {
                                    return `
                                        <span class="sched-cat-pill" 
                                              id="empDeptPill_${emp.id}" 
                                              data-current-dept="${escapeHtml(cat)}" 
                                              data-emp-id="${emp.id}" 
                                              title="Department: ${escapeHtml(cat)}">
                                            <span class="sched-cat-dot" style="background-color: ${DEPT_COLORS[cat.toLowerCase()] || '#7c3aed'};"></span>
                                            <span class="truncate sched-dept-pill-text">${escapeHtml(cat)}</span>
                                        </span>
                                    `;
                                }
                            })()}
                        </div>
                    </div>
                    <div style="font-size: 11.5px; color: #64748b; display: flex; align-items: center; gap: 4px;">
                        <i class="ph ph-map-pin" style="color: #7c3aed; font-size: 11.5px;"></i>
                        <span class="truncate" style="font-weight: 600; color: #475569;">${escapeHtml(branch)}</span>
                    </div>
                </div>
                <div class="sched-row-action-menu" style="position: relative;">
                    <button type="button" class="sched-row-btn" onclick="toggleRowMenu(${emp.id}, this)" title="Quick fill week">
                        <i class="ph ph-lightning"></i>
                    </button>
                    <div class="sched-row-dropdown" id="rowMenu_${emp.id}" style="display: none;">
                        <div style="padding: 6px 10px; font-size: 10px; font-weight: 600; color: #94a3b8; text-transform: uppercase; border-bottom: 1px solid #f1f5f9;">
                            Quick Fill: ${escapeHtml(emp.first_name || name)}
                        </div>
                        <button type="button" class="sched-row-dd-item" onclick="quickFillRowPreset(${emp.id}, 'O', ['Sun'])">
                            <i class="ph ph-sun" style="color: #10b981;"></i>
                            <span>Mon–Sat Opening (Sun Off)</span>
                        </button>
                        <button type="button" class="sched-row-dd-item" onclick="quickFillRowPreset(${emp.id}, 'MD', ['Sun'])">
                            <i class="ph ph-clock" style="color: #3b82f6;"></i>
                            <span>Mon–Sat Mid Day (Sun Off)</span>
                        </button>
                        <button type="button" class="sched-row-dd-item" onclick="quickFillRowPreset(${emp.id}, 'C', ['Sun'])">
                            <i class="ph ph-moon" style="color: #9333ea;"></i>
                            <span>Mon–Sat Closing (Sun Off)</span>
                        </button>
                        <div style="border-top: 1px solid #f1f5f9; margin: 4px 0;"></div>
                        <button type="button" class="sched-row-dd-item text-danger" onclick="quickFillRowPreset(${emp.id}, 'OFF', ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'])">
                            <i class="ph ph-coffee"></i>
                            <span>Mark Entire Week as Rest Days</span>
                        </button>
                        <button type="button" class="sched-row-dd-item text-danger" onclick="clearEmployeeWeek(${emp.id})">
                            <i class="ph ph-trash"></i>
                            <span>Clear All Shifts This Week</span>
                        </button>
                    </div>
                </div>
            </div>
        </td>
        <td class="sched-col-week" style="text-align: center; vertical-align: middle; color: #64748b; font-size: 12px; font-weight: 700; font-family: monospace;">
            ${escapeHtml(currentWeekVal)}
        </td>
        ${cellsHTML}
        <td class="sched-action-td" style="text-align: center; vertical-align: middle;">
            <button type="button" class="sched-btn-row-del" onclick="removeEmployeeRowFromGrid(${emp.id})" title="Remove row from grid">
                <i class="ph ph-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    updateVisibleRosterCount();
}

function removeEmployeeRowFromGrid(empId) {
    const row = document.getElementById(`schedRow_${empId}`);
    if (row) {
        row.remove();
        updateVisibleRosterCount();
        showSchedToast('Employee removed from grid view');
    }
}

function clearRosterGrid() {
    const doClear = () => {
        const tbody = document.getElementById('schedMatrixTbody');
        if (tbody) {
            tbody.innerHTML = `
                <tr id="schedEmptyRow">
                    <td colspan="9" style="text-align: center; color: #94a3b8; padding: 48px;">
                        <div style="font-size: 28px; margin-bottom: 8px;"><i class="ph ph-users"></i></div>
                        <div style="font-weight: 700; color: #475569; font-size: 14px;">Grid is cleared</div>
                        <div style="font-size: 12px; color: #94a3b8;">Click "+ Add Employee Row" above to begin scheduling.</div>
                    </td>
                </tr>
            `;
            updateVisibleRosterCount();
            showSchedToast('Grid cleared for current view');
        }
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Clear Schedule Grid?',
            text: 'Clear all employee rows from the current schedule view? (Saved schedules in database are safely preserved)',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Clear Grid',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                confirmButton: 'swal2-danger'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                doClear();
            }
        });
    } else {
        if (!confirm('Clear all employee rows from the current schedule view? (Saved schedules in database are preserved)')) return;
        doClear();
    }
}

// =========================================================================
// Client-Side Pagination & Matrix Filtering for Schedules
// =========================================================================
let _schedCurrentPage = 1;
let _schedFilteredRows = [];

function schedGetPerPage() {
    const val = document.getElementById('schedPerPageSelect')?.value || '15';
    return val === 'all' ? 999999 : parseInt(val, 10);
}

function schedChangePerPage() {
    _schedCurrentPage = 1;
    renderSchedPage();
}

function schedGoToPage(p) {
    _schedCurrentPage = p;
    renderSchedPage();
    const w = document.getElementById('schedMatrixTable')?.closest('.hr-table-wrapper');
    if (w) w.scrollTop = 0;
}

function renderSchedPage() {
    const perPage    = schedGetPerPage();
    const total      = _schedFilteredRows.length;
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    if (_schedCurrentPage > totalPages) _schedCurrentPage = totalPages;

    const start = (_schedCurrentPage - 1) * perPage;
    const end   = Math.min(start + perPage, total);

    // Hide all schedule rows
    const allRows = document.querySelectorAll('.sched-row');
    allRows.forEach(r => { r.style.display = 'none'; });

    // Show only the slice for this page
    _schedFilteredRows.forEach((r, idx) => {
        r.style.display = (idx >= start && idx < end) ? '' : 'none';
    });

    // Empty state row
    const emptyRow = document.getElementById('schedEmptyRow');
    if (emptyRow) {
        emptyRow.style.display = (total === 0) ? '' : 'none';
    }

    // Update roster count badge
    const badge = document.getElementById('empCountBadge');
    if (badge) badge.textContent = `${total} Staff`;

    // Pagination bar visibility
    const bar = document.getElementById('schedPaginationBar');
    if (bar) bar.style.display = (total > 0 || allRows.length > 0) ? 'flex' : 'none';

    // Info text
    const info = document.getElementById('schedPaginationInfo');
    if (info) {
        const from = total === 0 ? 0 : start + 1;
        info.innerHTML = `Showing <strong>${from}</strong> to <strong>${end}</strong> of <strong>${total}</strong> staff`;
    }

    // Page navigation
    const nav = document.getElementById('schedPageNav');
    if (!nav) return;

    let html = '';
    if (_schedCurrentPage === 1 || totalPages <= 1) {
        html += `<span class="hr-page-btn disabled" aria-disabled="true"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    } else {
        html += `<span class="hr-page-btn" onclick="schedGoToPage(${_schedCurrentPage - 1})" style="cursor:pointer;"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    }

    html += `<div class="hr-page-numbers">`;
    const window_size = 2;
    let pageStart = Math.max(1, _schedCurrentPage - window_size);
    let pageEnd   = Math.min(totalPages, _schedCurrentPage + window_size);

    if (pageStart > 1) {
        html += `<span class="hr-page-num" onclick="schedGoToPage(1)" style="cursor:pointer;">1</span>`;
        if (pageStart > 2) html += `<span class="hr-page-num dots">…</span>`;
    }

    for (let p = pageStart; p <= pageEnd; p++) {
        html += (p === _schedCurrentPage)
            ? `<span class="hr-page-num active" aria-current="page">${p}</span>`
            : `<span class="hr-page-num" onclick="schedGoToPage(${p})" style="cursor:pointer;">${p}</span>`;
    }

    if (pageEnd < totalPages) {
        if (pageEnd < totalPages - 1) html += `<span class="hr-page-num dots">…</span>`;
        html += `<span class="hr-page-num" onclick="schedGoToPage(${totalPages})" style="cursor:pointer;">${totalPages}</span>`;
    }
    html += `</div>`;

    if (_schedCurrentPage >= totalPages) {
        html += `<span class="hr-page-btn disabled" aria-disabled="true"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    } else {
        html += `<span class="hr-page-btn" onclick="schedGoToPage(${_schedCurrentPage + 1})" style="cursor:pointer;"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    }

    nav.innerHTML = html;
}

function filterMatrixRows(resetPage = true) {
    const q = (document.getElementById('liveEmployeeSearch')?.value || '').toLowerCase().trim();
    const clearBtn = document.getElementById('clearSearchBtn');
    if (clearBtn) clearBtn.style.display = q ? 'inline-flex' : 'none';

    const checkedCats = Array.from(document.querySelectorAll('.sched-cat-cb:checked')).map(cb => cb.value.toLowerCase());
    const allRows = Array.from(document.querySelectorAll('.sched-row'));

    _schedFilteredRows = allRows.filter(r => {
        const name   = (r.getAttribute('data-emp-name')   || '').toLowerCase();
        const branch = (r.getAttribute('data-emp-branch') || '').toLowerCase();
        const pos    = (r.getAttribute('data-emp-pos')    || '').toLowerCase();
        const cat    = (r.getAttribute('data-emp-cat')    || '').toLowerCase();

        const matchCat = checkedCats.length === 0 || checkedCats.includes(cat);
        const matchQ   = !q || _fuzzyMatch(name, q) || _fuzzyMatch(branch, q) || _fuzzyMatch(pos, q);

        return matchCat && matchQ;
    });

    if (resetPage) _schedCurrentPage = 1;
    renderSchedPage();
}

function filterMatrixByCategory() {
    filterMatrixRows(true);
}

/**
 * Levenshtein distance for fuzzy employee search.
 * Returns the edit-distance between two strings.
 */
function _levenshtein(a, b) {
    const m = a.length, n = b.length;
    const dp = Array.from({ length: m + 1 }, (_, i) =>
        Array.from({ length: n + 1 }, (_, j) => (i === 0 ? j : j === 0 ? i : 0))
    );
    for (let i = 1; i <= m; i++) {
        for (let j = 1; j <= n; j++) {
            dp[i][j] = a[i-1] === b[j-1]
                ? dp[i-1][j-1]
                : 1 + Math.min(dp[i-1][j], dp[i][j-1], dp[i-1][j-1]);
        }
    }
    return dp[m][n];
}

/**
 * Fuzzy match: returns true if query loosely matches any token in the haystack.
 * Allows exact substring OR Levenshtein distance ≤ floor(token.length/4)+1.
 */
function _fuzzyMatch(haystack, query) {
    if (!query) return true;
    if (haystack.includes(query)) return true;
    const tokens = haystack.split(/[\s,._\-\/]+/).filter(Boolean);
    const qLen = query.length;
    for (const token of tokens) {
        if (token.length < 2) continue;
        const threshold = Math.floor(Math.min(token.length, qLen) / 4) + 1;
        if (_levenshtein(token, query) <= threshold) return true;
    }
    return false;
}

function filterMatrixRowsBySearch() {
    filterMatrixRows(true);
}

function clearLiveSearch() {
    const inp = document.getElementById('liveEmployeeSearch');
    if (inp) inp.value = '';
    filterMatrixRows(true);
}

function updateVisibleRosterCount() {
    filterMatrixRows(false);
}

// -------------------------------------------------------------
// Toast Helper
// -------------------------------------------------------------
let toastTimer = null;
function showSchedToast(msg, isSuccess = true) {
    const toast = document.getElementById('schedToast');
    const toastMsg = document.getElementById('schedToastMsg');
    const toastIcon = document.getElementById('schedToastIcon');
    if (!toast) return;

    toastMsg.textContent = msg;
    if (isSuccess) {
        toast.style.background = '#0f172a';
        toastIcon.innerHTML = '<i class="ph ph-check-circle" style="color: #10b981; font-size: 18px;"></i>';
    } else {
        toast.style.background = '#ef4444';
        toastIcon.innerHTML = '<i class="ph ph-warning-circle" style="color: #ffffff; font-size: 18px;"></i>';
    }

    toast.style.display = 'flex';
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';

    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(8px)';
        setTimeout(() => { toast.style.display = 'none'; }, 200);
    }, 2800);
}

// -------------------------------------------------------------
// Row Lightning Quick Fill Logic
// -------------------------------------------------------------
function toggleRowMenu(empId, btnElement = null) {
    const dd = document.getElementById(`rowMenu_${empId}`);
    if (!dd) return;

    const isAlreadyOpen = dd.classList.contains('open') && dd.style.display !== 'none';

    // Close all open row dropdowns first
    document.querySelectorAll('.sched-row-dropdown').forEach(d => {
        d.classList.remove('open');
        d.style.display = 'none';
        d.style.visibility = 'hidden';
    });

    if (isAlreadyOpen) {
        return;
    }

    let btn = btnElement;
    if (!btn) {
        const row = document.getElementById(`schedRow_${empId}`);
        btn = row ? row.querySelector('.sched-row-btn') : dd.parentElement?.querySelector('.sched-row-btn');
    }
    if (!btn) return;

    // Attach to document.body so position: fixed coordinates are always 100% relative to viewport
    // and never affected by containing blocks, sticky columns, or table scroll
    if (dd.parentElement !== document.body) {
        document.body.appendChild(dd);
    }

    // Show invisibly to measure real dimensions
    dd.style.visibility = 'hidden';
    dd.style.display = 'block';
    dd.classList.add('open');

    const rect = btn.getBoundingClientRect();
    const ddWidth = dd.offsetWidth || 230;
    const ddHeight = dd.offsetHeight || 220;
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    // Horizontal placement: Open directly BESIDE the button (to the right of the button)
    const spaceRight = viewportWidth - rect.right;
    const spaceLeft = rect.left;

    let left;
    if (spaceRight >= ddWidth + 12 || spaceRight >= spaceLeft) {
        // Sufficient room on the right side of the button
        left = rect.right + 8;
    } else {
        // Not enough room on the right -> flip to the left side of the button
        left = rect.left - ddWidth - 8;
    }
    // Prevent clipping past viewport edges
    left = Math.max(12, Math.min(left, viewportWidth - ddWidth - 12));

    // Vertical placement: Align with the top of the button, clamped within viewport
    let top = rect.top - 6;
    if (top + ddHeight > viewportHeight - 12) {
        top = Math.max(12, viewportHeight - ddHeight - 12);
    }
    if (top < 12) {
        top = 12;
    }

    dd.style.top = `${Math.round(top)}px`;
    dd.style.left = `${Math.round(left)}px`;
    dd.style.visibility = 'visible';
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.sched-row-action-menu') && !e.target.closest('.sched-row-btn') && !e.target.closest('.sched-row-dropdown')) {
        document.querySelectorAll('.sched-row-dropdown').forEach(d => {
            d.classList.remove('open');
            d.style.display = 'none';
            d.style.visibility = 'hidden';
        });
    }
});

document.addEventListener('scroll', function(e) {
    if (!e.target.closest('.sched-row-dropdown')) {
        document.querySelectorAll('.sched-row-dropdown.open').forEach(d => {
            d.classList.remove('open');
            d.style.display = 'none';
            d.style.visibility = 'hidden';
        });
    }
}, true);

window.addEventListener('resize', function() {
    document.querySelectorAll('.sched-row-dropdown.open').forEach(d => {
        d.classList.remove('open');
        d.style.display = 'none';
        d.style.visibility = 'hidden';
    });
});

function quickFillRowPreset(empId, shiftCode, restDays) {
    const weekStart = document.getElementById('schedWeekInput').value;
    document.querySelectorAll('.sched-row-dropdown').forEach(d => {
        d.classList.remove('open');
        d.style.display = 'none';
    });

    const dates = window.HrSchedulesConfig?.dates || [];
    const rowEl = document.getElementById(`schedRow_${empId}`);
    const empCat = rowEl ? (rowEl.getAttribute('data-emp-cat') || 'default') : 'default';
    const cfg = getShiftConfig(empCat, shiftCode);

    const promises = dates.map(d => {
        const cDate = new Date(d);
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const dayName = dayNames[cDate.getDay()];
        const isRest = restDays.includes(dayName) || shiftCode === 'OFF';

        return fetch(QUICK_ASSIGN_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                employee_id: empId,
                date: d,
                shift_code: isRest ? 'OFF' : shiftCode,
                custom_start_time: isRest ? null : cfg.start,
                custom_end_time: isRest ? null : cfg.end,
                notes: isRest ? 'RESTDAY' : (cfg.label || shiftCode),
                is_rest_day: isRest
            })
        });
    });

    Promise.all(promises).then(() => {
        showSchedToast(`Filled week schedule`);
        setTimeout(() => { window.location.reload(); }, 600);
    });
}

function clearEmployeeWeek(empId) {
    const doClearWeek = () => {
        document.querySelectorAll('.sched-row-dropdown').forEach(d => {
            d.classList.remove('open');
            d.style.display = 'none';
        });

        const dates = window.HrSchedulesConfig?.dates || [];
        const promises = dates.map(d => {
            return fetch(QUICK_ASSIGN_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ employee_id: empId, date: d, clear: true })
            });
        });

        Promise.all(promises).then(() => {
            showSchedToast('Cleared employee week');
            setTimeout(() => { window.location.reload(); }, 600);
        });
    };

    const row = document.getElementById(`schedRow_${empId}`);
    const empName = row?.getAttribute('data-emp-name') || 'this employee';

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Clear Week Schedule?',
            html: `Clear all shifts for <strong>${escapeHtml(empName)}</strong> this week?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Clear Week',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                confirmButton: 'swal2-danger'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                doClearWeek();
            }
        });
    } else {
        if (!confirm('Clear all shifts for this employee this week?')) return;
        doClearWeek();
    }
}

// -------------------------------------------------------------
// Copy Previous Week Schedules Handler
// -------------------------------------------------------------
function triggerCopyPreviousWeek() {
    const doCopy = () => {
        const currentWeekStart = document.getElementById('schedWeekInput').value;

        fetch(COPY_WEEK_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ current_week_start: currentWeekStart })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Copied Successfully!',
                        text: data.message || 'Schedules copied from previous week.',
                        icon: 'success',
                        confirmButtonColor: '#7c3aed',
                        timer: 1800
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    showSchedToast(data.message);
                    setTimeout(() => { window.location.reload(); }, 800);
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Notice',
                        text: data.message || 'No schedules found in previous week to copy.',
                        icon: 'info',
                        confirmButtonColor: '#7c3aed'
                    });
                } else {
                    showSchedToast(data.message || 'No schedules found to copy', false);
                }
            }
        })
        .catch(() => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: 'Error copying previous week schedules',
                    icon: 'error',
                    confirmButtonColor: '#7c3aed'
                });
            } else {
                showSchedToast('Error copying previous week schedules', false);
            }
        });
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Copy Previous Week?',
            text: 'Copy all shift schedules from the previous week into the current week?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#7c3aed',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="ph ph-copy"></i> Yes, Copy Schedules',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                doCopy();
            }
        });
    } else {
        if (!confirm('Copy all shift schedules from the previous week into the current week?')) return;
        doCopy();
    }
}

// -------------------------------------------------------------
// Backward Compatibility Modals
// -------------------------------------------------------------
function openModal(id) { 
    var modal = document.getElementById(id);
    if (modal) modal.classList.add('open'); 
}
function closeModal(id) { 
    var modal = document.getElementById(id);
    if (modal) modal.classList.remove('open'); 
}
function openAssignModal() {
    clearRestDaysPreset();
    document.getElementById('isRestDayAll').checked = false;
    toggleRestDayAll(document.getElementById('isRestDayAll'));
    openModal('assignScheduleModal');
}
function syncDateRange() {
    var start = document.getElementById('schedStartDate').value;
    var end = document.getElementById('schedEndDate').value;
    if (!end || end < start) document.getElementById('schedEndDate').value = start;
}
function presetDateRange(type) {
    var now = new Date();
    var formatDate = function(d) {
        var year = d.getFullYear();
        var month = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    };
    var start = new Date();
    var end = new Date();
    if (type === 'today') {
    } else if (type === 'this_week') {
        var dayOfWeek = now.getDay();
        var diffToMon = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
        start.setDate(now.getDate() + diffToMon);
        end.setDate(start.getDate() + 6);
    } else if (type === 'next_week') {
        var dayOfWeek = now.getDay();
        var diffToMon = dayOfWeek === 0 ? 1 : 8 - dayOfWeek;
        start.setDate(now.getDate() + diffToMon);
        end.setDate(start.getDate() + 6);
    } else if (type === 'month') {
        start = new Date(now.getFullYear(), now.getMonth(), 1);
        end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    }
    document.getElementById('schedStartDate').value = formatDate(start);
    document.getElementById('schedEndDate').value = formatDate(end);
}
function setRestDaysPreset(days) {
    var all = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    all.forEach(function(d) {
        var cb = document.getElementById('rest_day_' + d);
        if (cb) cb.checked = days.includes(d);
    });
}
function clearRestDaysPreset() { setRestDaysPreset([]); }
function toggleRestDayAll(cb) {
    var shiftGroup = document.getElementById('shiftSelectionGroup');
    var shiftSelect = document.getElementById('schedShiftTemplateId');
    if (cb.checked) {
        shiftSelect.disabled = true;
        shiftGroup.style.opacity = '0.5';
    } else {
        shiftSelect.disabled = false;
        shiftGroup.style.opacity = '1';
    }
}
function formatTimeToAMPM(timeStr) {
    if (!timeStr) return '';
    var parts = timeStr.split(':');
    var h = parseInt(parts[0], 10);
    var m = parts[1] || '00';
    var ampm = h >= 12 ? 'PM' : 'AM';
    var h12 = h % 12;
    if (h12 === 0) h12 = 12;
    return h12 + (m !== '00' ? ':' + m : '') + ampm;
}
function updateShiftPreview() {
    var start = document.getElementById('newShiftStart').value || '06:00';
    var end = document.getElementById('newShiftEnd').value || '15:00';
    var code = start.replace(':', '');
    var startFmt = formatTimeToAMPM(start);
    var endFmt = formatTimeToAMPM(end);
    document.getElementById('shiftFormatPreview').textContent = code + ' = ' + startFmt + ' - ' + endFmt;
    var overnightCheckbox = document.getElementById('isOvernight');
    if (start > end) overnightCheckbox.checked = true;
}
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

// -------------------------------------------------------------
// Batch Fill Grid & Draft Schedules Engine
// -------------------------------------------------------------
function openDefaultShiftModal(empId = null, empName = '', shiftId = null) {
    if (empId) {
        const empSel = document.getElementById('defaultShiftEmpSelect');
        if (empSel) empSel.value = empId;
        const tmplSel = document.getElementById('defaultShiftTmplSelect');
        if (tmplSel) tmplSel.value = shiftId || '';
    }
    openModal('defaultShiftModal');
}

function openBatchFillGridModal() {
    setBatchFillScope('all');

    const dates = window.HrSchedulesConfig?.dates || [];
    if (dates && dates.length > 0) {
        const sInput = document.getElementById('batchSchedStartDate');
        const eInput = document.getElementById('batchSchedEndDate');
        if (sInput) sInput.value = dates[0];
        if (eInput) eInput.value = dates[dates.length - 1];
    }

    renderBatchEmpChecklist();
    openModal('batchFillGridModal');
}

function setBatchFillScope(scope) {
    batchFillScope = scope;
    const btnAll = document.getElementById('btnBatchScopeAll');
    const btnCustom = document.getElementById('btnBatchScopeCustom');
    const customWrap = document.getElementById('batchCustomEmpWrap');
    const noticeAll = document.getElementById('batchAllEmpNotice');
    const badge = document.getElementById('batchEmpSelectedSummaryBadge');
    const allEmpCountText = document.getElementById('batchAllEmpCountText');

    const gridRows = document.querySelectorAll('.sched-row[data-emp-id]');
    const allIds = Array.from(gridRows).map(r => parseInt(r.getAttribute('data-emp-id'))).filter(Boolean);

    if (allEmpCountText) {
        allEmpCountText.textContent = `all ${allIds.length} employees`;
    }

    if (scope === 'all') {
        if (btnAll) btnAll.classList.add('active');
        if (btnCustom) btnCustom.classList.remove('active');
        if (customWrap) customWrap.style.display = 'none';
        if (noticeAll) noticeAll.style.display = 'flex';
        
        batchSelectedEmpIds = [...allIds];
        if (badge) {
            badge.textContent = `All in Grid (${batchSelectedEmpIds.length})`;
            badge.style.background = '#ede9fe';
            badge.style.color = '#6d28d9';
        }
    } else {
        if (btnAll) btnAll.classList.remove('active');
        if (btnCustom) btnCustom.classList.add('active');
        if (customWrap) customWrap.style.display = 'flex';
        if (noticeAll) noticeAll.style.display = 'none';

        if (batchSelectedEmpIds.length === 0) {
            batchSelectedEmpIds = [...allIds];
        }

        if (badge) {
            badge.textContent = `${batchSelectedEmpIds.length} Selected`;
            badge.style.background = '#f1f5f9';
            badge.style.color = '#334155';
        }
        renderBatchEmpChecklist();
    }

    const applyText = document.getElementById('batchApplyBtnText');
    if (applyText) {
        applyText.textContent = `Apply to Grid (Draft)`;
    }
}

function renderBatchEmpChecklist() {
    const listEl = document.getElementById('batchEmpChecklist');
    if (!listEl) return;

    const q = (document.getElementById('batchEmpSearchInput')?.value || '').toLowerCase().trim();
    const activeDeptBtn = document.querySelector('#batchDeptFilterBar .batch-dept-pill.active');
    const activeDept = activeDeptBtn ? activeDeptBtn.textContent.trim().toLowerCase() : 'all';

    const gridRows = document.querySelectorAll('.sched-row[data-emp-id]');
    const gridEmps = [];
    gridRows.forEach(r => {
        const id = parseInt(r.getAttribute('data-emp-id'));
        const name = r.querySelector('.sched-emp-name-text')?.textContent || r.getAttribute('data-emp-name') || 'Employee';
        const dept = r.getAttribute('data-emp-cat') || 'General';
        const branch = r.getAttribute('data-emp-branch') || 'Branch';
        const initials = r.querySelector('.sched-emp-avatar')?.textContent?.trim() || name.substring(0, 2).toUpperCase();
        gridEmps.push({ id, name, dept, branch, initials });
    });

    const filtered = gridEmps.filter(emp => {
        const matchQ = !q || emp.name.toLowerCase().includes(q) || String(emp.id).includes(q) || emp.dept.toLowerCase().includes(q);
        const matchDept = (activeDept === 'all') || emp.dept.toLowerCase().includes(activeDept);
        return matchQ && matchDept;
    });

    if (filtered.length === 0) {
        listEl.innerHTML = `<div style="padding: 14px; text-align: center; color: #94a3b8; font-size: 11px;">No employees match filter</div>`;
        return;
    }

    let html = '';
    filtered.forEach(emp => {
        const isChecked = batchSelectedEmpIds.includes(emp.id);
        html += `
            <label style="display: flex; align-items: center; justify-content: space-between; padding: 6px 8px; border-radius: 6px; background: ${isChecked ? '#f5f3ff' : '#f8fafc'}; border: 1px solid ${isChecked ? '#c4b5fd' : '#e2e8f0'}; cursor: pointer; user-select: none;">
                <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                    <input type="checkbox" value="${emp.id}" ${isChecked ? 'checked' : ''} onchange="toggleBatchEmpSelection(${emp.id}, this.checked)" style="cursor: pointer;">
                    <div class="sched-emp-avatar" style="width: 22px; height: 22px; font-size: 9px;">${escapeHtml(emp.initials)}</div>
                    <div style="min-width: 0;">
                        <div style="font-size: 11.5px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(emp.name)}</div>
                    </div>
                </div>
                <span style="font-size: 9.5px; font-weight: 700; color: #64748b; background: #e2e8f0; padding: 1px 6px; border-radius: 4px; text-transform: uppercase;">${escapeHtml(emp.dept)}</span>
            </label>
        `;
    });
    listEl.innerHTML = html;
}

function toggleBatchEmpSelection(empId, isChecked) {
    if (isChecked) {
        if (!batchSelectedEmpIds.includes(empId)) batchSelectedEmpIds.push(empId);
    } else {
        batchSelectedEmpIds = batchSelectedEmpIds.filter(id => id !== empId);
    }
    updateBatchEmpSelectionBadge();
}

function updateBatchEmpSelectionBadge() {
    const badge = document.getElementById('batchEmpSelectedSummaryBadge');
    if (badge) {
        badge.textContent = `${batchSelectedEmpIds.length} Selected`;
    }
    const applyText = document.getElementById('batchApplyBtnText');
    if (applyText) {
        applyText.textContent = `Apply to Grid (Draft)`;
    }
}

function batchSelectAllEmps(selectAll) {
    const gridRows = document.querySelectorAll('.sched-row[data-emp-id]');
    if (selectAll) {
        batchSelectedEmpIds = Array.from(gridRows).map(r => parseInt(r.getAttribute('data-emp-id'))).filter(Boolean);
    } else {
        batchSelectedEmpIds = [];
    }
    renderBatchEmpChecklist();
    updateBatchEmpSelectionBadge();
}

function filterBatchEmpsByDept(dept, btn) {
    document.querySelectorAll('#batchDeptFilterBar .batch-dept-pill').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    renderBatchEmpChecklist();
}

function filterBatchModalEmployees() {
    renderBatchEmpChecklist();
}

function presetBatchDateRange(type) {
    var now = new Date();
    var formatDate = function(d) {
        var year = d.getFullYear();
        var month = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    };
    var start = new Date();
    var end = new Date();
    if (type === 'today') {
    } else if (type === 'this_week') {
        var dayOfWeek = now.getDay();
        var diffToMon = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
        start.setDate(now.getDate() + diffToMon);
        end.setDate(start.getDate() + 6);
    } else if (type === 'next_week') {
        var dayOfWeek = now.getDay();
        var diffToMon = dayOfWeek === 0 ? 1 : 8 - dayOfWeek;
        start.setDate(now.getDate() + diffToMon);
        end.setDate(start.getDate() + 6);
    }
    var sInput = document.getElementById('batchSchedStartDate');
    var eInput = document.getElementById('batchSchedEndDate');
    if (sInput) sInput.value = formatDate(start);
    if (eInput) eInput.value = formatDate(end);
}

function syncBatchDateRange() {
    var start = document.getElementById('batchSchedStartDate')?.value;
    var end = document.getElementById('batchSchedEndDate')?.value;
    if (start && (!end || end < start)) {
        document.getElementById('batchSchedEndDate').value = start;
    }
}

function setBatchRestDaysPreset(days) {
    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(function(d) {
        var cb = document.getElementById('batch_rest_day_' + d);
        if (cb) cb.checked = days.includes(d);
    });
}

function clearBatchRestDaysPreset() {
    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(function(d) {
        var cb = document.getElementById('batch_rest_day_' + d);
        if (cb) cb.checked = false;
    });
}

function applyBatchFillGridDraft() {
    let targetEmpIds = [];
    if (batchFillScope === 'all') {
        const gridRows = document.querySelectorAll('.sched-row[data-emp-id]');
        targetEmpIds = Array.from(gridRows).map(r => parseInt(r.getAttribute('data-emp-id'))).filter(Boolean);
    } else {
        targetEmpIds = [...batchSelectedEmpIds];
    }

    if (targetEmpIds.length === 0) {
        showSchedToast('Please select at least one employee to fill', false);
        return;
    }

    const startDate = document.getElementById('batchSchedStartDate')?.value;
    const endDate = document.getElementById('batchSchedEndDate')?.value;
    if (!startDate || !endDate) {
        showSchedToast('Please select valid start and end dates', false);
        return;
    }

    const templateSelect = document.getElementById('batchSchedShiftTemplateId');
    const selectedOpt = templateSelect?.selectedOptions[0];
    const val = templateSelect?.value;
    if (!val) {
        showSchedToast('Please select a shift format or rest day', false);
        return;
    }

    const isAllRest = val === 'OFF';
    const tmplCode = isAllRest ? 'OFF' : (selectedOpt?.getAttribute('data-code') || 'CUSTOM');
    const tmplName = isAllRest ? 'RESTDAY' : (selectedOpt?.getAttribute('data-name') || selectedOpt?.text || 'Shift');
    const tmplStart = selectedOpt?.getAttribute('data-start') || '';
    const tmplEnd = selectedOpt?.getAttribute('data-end') || '';
    const tmplColor = selectedOpt?.getAttribute('data-color') || '#7c3aed';
    const tmplId = isAllRest ? null : (parseInt(val) || null);

    const restDays = [];
    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(d => {
        if (document.getElementById('batch_rest_day_' + d)?.checked) {
            restDays.push(d);
        }
    });

    const gridDates = window.HrSchedulesConfig?.dates || [];
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    let plottedCount = 0;

    targetEmpIds.forEach(empId => {
        gridDates.forEach(d => {
            if (d >= startDate && d <= endDate) {
                const dateObj = new Date(d + 'T00:00:00');
                const dayName = dayNames[dateObj.getDay()];
                const isRest = isAllRest || restDays.includes(dayName);

                const draftKey = `${empId}_${d}`;
                const cell = document.getElementById(`cell_${empId}_${d}`);
                if (cell) {
                    if (!savedOriginalCells[draftKey]) {
                        savedOriginalCells[draftKey] = cell.innerHTML;
                    }

                    const code = isRest ? 'OFF' : tmplCode;
                    const label = isRest ? 'RESTDAY' : tmplName;
                    const time = isRest ? 'OFF DUTY' : (tmplStart && tmplEnd ? `${tmplStart} - ${tmplEnd}` : '08:00 - 17:00');
                    const themeClass = isRest ? 'sched-card-rest' : `sched-theme-${code.toLowerCase()}`;

                    draftSchedules[draftKey] = {
                        employee_id: empId,
                        date: d,
                        shift_template_id: isRest ? null : tmplId,
                        is_rest_day: isRest,
                        custom_start_time: isRest ? null : tmplStart,
                        custom_end_time: isRest ? null : tmplEnd,
                        notes: isRest ? 'RESTDAY' : tmplName,
                        is_draft: true
                    };

                    renderAssignedCardHTML(cell, empId, d, code, label, time, themeClass, tmplColor);

                    const card = cell.querySelector('.sched-shift-card');
                    if (card) {
                        card.classList.add('is-draft');
                        const badgeWrap = card.querySelector('.sched-badge-wrap');
                        if (badgeWrap && !badgeWrap.querySelector('.sched-draft-pill')) {
                            const draftPill = document.createElement('span');
                            draftPill.className = 'sched-draft-pill';
                            draftPill.innerHTML = '<i class="ph ph-pencil-simple" style="font-size: 8px;"></i> Draft';
                            badgeWrap.appendChild(draftPill);
                        }
                        const detailBadge = card.querySelector('.sched-detail-badge');
                        if (detailBadge) {
                            detailBadge.className = 'sched-detail-badge badge-draft';
                            detailBadge.textContent = 'DRAFT';
                            detailBadge.style.cssText = 'background: #fef3c7; color: #b45309; border: 1px solid #fcd34d; font-weight: 600;';
                        }
                    }
                    plottedCount++;
                }
            }
        });
    });

    closeModal('batchFillGridModal');
    updateSaveButtonState();
    showSchedToast(`Plotted ${plottedCount} shifts as draft across ${targetEmpIds.length} employee(s). Click "Save" to save.`);
}

function applySingleEmpFillGridDraft() {
    const empId = document.getElementById('schedEmployeeIdModal')?.value || activeCellEmpId;
    if (!empId) {
        showSchedToast('No employee selected', false);
        return;
    }

    const startDate = document.getElementById('schedStartDateModal')?.value;
    const endDate = document.getElementById('schedEndDateModal')?.value;
    if (!startDate || !endDate) {
        showSchedToast('Please select valid start and end dates', false);
        return;
    }

    const templateSelect = document.getElementById('schedShiftTemplateIdModal');
    const selectedOpt = templateSelect?.selectedOptions[0];
    const val = templateSelect?.value;
    if (!val) {
        showSchedToast('Please select a shift format', false);
        return;
    }

    const tmplCode = selectedOpt?.getAttribute('data-code') || (selectedOpt ? selectedOpt.text.split('=')[0].trim() : 'CUSTOM');
    const tmplName = selectedOpt?.getAttribute('data-name') || selectedOpt?.text || 'Shift';
    const tmplStart = selectedOpt?.getAttribute('data-start') || '';
    const tmplEnd = selectedOpt?.getAttribute('data-end') || '';
    const tmplColor = selectedOpt?.getAttribute('data-color') || '#7c3aed';
    const tmplId = parseInt(val) || null;

    const restDays = [];
    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(d => {
        if (document.getElementById('rest_day_modal_' + d)?.checked) {
            restDays.push(d);
        }
    });

    const gridDates = window.HrSchedulesConfig?.dates || [];
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    let plottedCount = 0;

    gridDates.forEach(d => {
        if (d >= startDate && d <= endDate) {
            const dateObj = new Date(d + 'T00:00:00');
            const dayName = dayNames[dateObj.getDay()];
            const isRest = restDays.includes(dayName);

            const draftKey = `${empId}_${d}`;
            const cell = document.getElementById(`cell_${empId}_${d}`);
            if (cell) {
                if (!savedOriginalCells[draftKey]) {
                    savedOriginalCells[draftKey] = cell.innerHTML;
                }

                const code = isRest ? 'OFF' : tmplCode;
                const label = isRest ? 'RESTDAY' : tmplName;
                const time = isRest ? 'OFF DUTY' : (tmplStart && tmplEnd ? `${tmplStart} - ${tmplEnd}` : '08:00 - 17:00');
                const themeClass = isRest ? 'sched-card-rest' : `sched-theme-${code.toLowerCase()}`;

                draftSchedules[draftKey] = {
                    employee_id: parseInt(empId),
                    date: d,
                    shift_template_id: isRest ? null : tmplId,
                    is_rest_day: isRest,
                    custom_start_time: isRest ? null : tmplStart,
                    custom_end_time: isRest ? null : tmplEnd,
                    notes: isRest ? 'RESTDAY' : tmplName,
                    is_draft: true
                };

                renderAssignedCardHTML(cell, empId, d, code, label, time, themeClass, tmplColor);

                const card = cell.querySelector('.sched-shift-card');
                if (card) {
                    card.classList.add('is-draft');
                    const badgeWrap = card.querySelector('.sched-badge-wrap');
                    if (badgeWrap && !badgeWrap.querySelector('.sched-draft-pill')) {
                        const draftPill = document.createElement('span');
                        draftPill.className = 'sched-draft-pill';
                        draftPill.innerHTML = '<i class="ph ph-pencil-simple" style="font-size: 8px;"></i> Draft';
                        badgeWrap.appendChild(draftPill);
                    }
                    const detailBadge = card.querySelector('.sched-detail-badge');
                    if (detailBadge) {
                        detailBadge.className = 'sched-detail-badge badge-draft';
                        detailBadge.textContent = 'DRAFT';
                        detailBadge.style.cssText = 'background: #fef3c7; color: #b45309; border: 1px solid #fcd34d; font-weight: 600;';
                    }
                }
                plottedCount++;
            }
        }
    });

    closeCellCustomDropdown();
    updateSaveButtonState();
    showSchedToast(`Plotted ${plottedCount} draft shifts for employee. Click "Save" to save.`);
}

function updateSaveButtonState() {
    const count = Object.keys(draftSchedules).length;
    const saveBtn = document.getElementById('btnSaveGrid');
    const badge = document.getElementById('saveBtnBadge');
    const label = document.getElementById('saveBtnLabel');
    if (!saveBtn) return;

    if (count > 0) {
        saveBtn.classList.add('has-drafts');
        if (badge) {
            badge.style.display = 'inline-flex';
            badge.textContent = count;
        }
        if (label) {
            label.textContent = `Save (${count})`;
        }
    } else {
        saveBtn.classList.remove('has-drafts');
        if (badge) {
            badge.style.display = 'none';
            badge.textContent = '0';
        }
        if (label) {
            label.textContent = 'Save';
        }
    }
}

function saveDraftSchedules() {
    const draftKeys = Object.keys(draftSchedules);
    if (draftKeys.length === 0) {
        showSchedToast('All shift changes are saved and active!');
        return;
    }

    const saveBtn = document.getElementById('btnSaveGrid');
    const saveLabel = document.getElementById('saveBtnLabel');
    
    if (saveBtn) {
        saveBtn.disabled = true;
    }
    if (saveLabel) {
        saveLabel.textContent = 'Saving...';
    }

    const payload = {
        schedules: Object.values(draftSchedules).map(item => ({
            employee_id: item.employee_id,
            date: item.date,
            shift_template_id: item.shift_template_id || null,
            is_rest_day: !!item.is_rest_day,
            custom_start_time: item.custom_start_time || null,
            custom_end_time: item.custom_end_time || null,
            notes: item.notes || null,
            clear: !!item.clear
        }))
    };

    fetch(BATCH_SCHEDULE_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            draftKeys.forEach(key => {
                const parts = key.split('_');
                const empId = parts[0];
                const date = parts[1];
                const cell = document.getElementById(`cell_${empId}_${date}`);
                if (cell) {
                    const card = cell.querySelector('.sched-shift-card');
                    if (card) {
                        card.classList.remove('is-draft');
                        const draftPill = card.querySelector('.sched-draft-pill');
                        if (draftPill) draftPill.remove();
                        const draftBadge = card.querySelector('.badge-draft');
                        if (draftBadge) {
                            draftBadge.className = 'sched-detail-badge badge-planned';
                            draftBadge.textContent = 'SCHEDULED';
                            draftBadge.removeAttribute('style');
                        }
                    }
                }
            });

            const count = data.saved_count || draftKeys.length;
            draftSchedules = {};
            savedOriginalCells = {};
            updateSaveButtonState();
            showSchedToast(`Successfully saved ${count} schedule entries!`);
        } else {
            showSchedToast(data.message || 'Failed to save schedules', false);
            updateSaveButtonState();
        }
    })
    .catch(err => {
        console.error(err);
        showSchedToast('Network error while saving schedules', false);
        updateSaveButtonState();
    })
    .finally(() => {
        if (saveBtn) {
            saveBtn.disabled = false;
        }
        if (saveLabel && Object.keys(draftSchedules).length === 0) {
            saveLabel.textContent = 'Save';
        }
    });
}

window.addEventListener('beforeunload', function(e) {
    if (typeof draftSchedules !== 'undefined' && Object.keys(draftSchedules).length > 0) {
        e.preventDefault();
        e.returnValue = '';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    filterMatrixRows(true);
});
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
    initShiftTemplatesStore,
    getShiftConfig,
    cycleEmployeeDepartment,
    switchScheduleManagerTab,
    openScheduleManagerTab,
    openCellCustomDropdown,
    closeCellCustomDropdown,
    syncDateRangeModal,
    presetDateRangeModal,
    setRestDaysPresetModal,
    clearRestDaysPresetModal,
    filterRegisteredShifts,
    updateShiftPreviewModal,
    openEditShiftTemplateModal,
    updateEditShiftPreviewModal,
    deleteShiftTemplate,
    deleteShiftTemplateFromModal,
    applyCustomTimeToActiveCell,
    applyTemplateToActiveCell,
    directPlotPreset,
    clearCellShift,
    classifyShift,
    renderAssignedCardHTML,
    renderEmptyCellHTML,
    openShiftMasterModal,
    switchShiftModalCategory,
    loadShiftCategoryIntoModal,
    saveShiftMasterSettings,
    resetShiftCategoryDefaults,
    toggleEmpDropdown,
    closeEmpDropdown,
    isEmpAlreadyInGrid,
    renderDropdownEmployeeList,
    filterDropdownEmployees,
    clearModalEmpSearch,
    resetDropdownFilters,
    toggleEmpAdditionSelection,
    toggleSelectAllFilteredEmps,
    updateAdditionFooterCounts,
    addSingleEmpToGrid,
    addSelectedEmployeesToGrid,
    appendEmployeeRowToTable,
    removeEmployeeRowFromGrid,
    clearRosterGrid,
    schedGetPerPage,
    schedChangePerPage,
    schedGoToPage,
    renderSchedPage,
    filterMatrixRows,
    filterMatrixByCategory,
    _levenshtein,
    _fuzzyMatch,
    filterMatrixRowsBySearch,
    clearLiveSearch,
    updateVisibleRosterCount,
    showSchedToast,
    toggleRowMenu,
    quickFillRowPreset,
    clearEmployeeWeek,
    triggerCopyPreviousWeek,
    openModal,
    closeModal,
    openAssignModal,
    syncDateRange,
    presetDateRange,
    setRestDaysPreset,
    clearRestDaysPreset,
    toggleRestDayAll,
    formatTimeToAMPM,
    updateShiftPreview,
    escapeHtml,
    openDefaultShiftModal,
    openBatchFillGridModal,
    setBatchFillScope,
    renderBatchEmpChecklist,
    toggleBatchEmpSelection,
    updateBatchEmpSelectionBadge,
    batchSelectAllEmps,
    filterBatchEmpsByDept,
    filterBatchModalEmployees,
    presetBatchDateRange,
    syncBatchDateRange,
    setBatchRestDaysPreset,
    clearBatchRestDaysPreset,
    applyBatchFillGridDraft,
    applySingleEmpFillGridDraft,
    updateSaveButtonState,
    saveDraftSchedules,
});
