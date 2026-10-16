window.openModal = function(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('open'); }
};

window.closeModal = function(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('open'); }
};

// Open New Manual Entry Modal
function openEncodeModal() {
    document.getElementById('modalTitle').innerText = 'Encode Manual Time Entry';
    document.getElementById('encEmployeeId').value = '';
    const today = new Date().toISOString().slice(0, 10);
    const encDate = document.getElementById('encDate');
    if (encDate) encDate.value = today;
    clearAllPunches();
    document.getElementById('encStatus').value = 'Auto';
    document.getElementById('encNotes').value = '';
    hideLookupAlert();
    openModal('encodeModal');
}

function getActivePunchDate() {
    const ids = ['encTimeIn', 'encTimeOut', 'encBreakOut', 'encBreakIn', 'encCoffeeOut', 'encCoffeeIn'];
    for (const id of ids) {
        const val = document.getElementById(id)?.value;
        if (val) {
            if (val.includes('T')) {
                return val.split('T')[0];
            }
            if (val.includes('-') && val.length >= 10) {
                return val.substring(0, 10);
            }
        }
    }
    const encDate = document.getElementById('encDate')?.value;
    if (encDate) {
        return encDate;
    }
    return new Date().toISOString().slice(0, 10);
}

function syncDateFromPunches() {
    const d = getActivePunchDate();
    const encDate = document.getElementById('encDate');
    if (encDate && d) {
        encDate.value = d;
    }
}

function formatLocalDateTime(d) {
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    const hh = String(d.getHours()).padStart(2, '0');
    const min = String(d.getMinutes()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}T${hh}:${min}`;
}

function toDateTimeInputValue(dateStr, timeStr) {
    if (!timeStr) return '';
    if (timeStr.includes('T')) return timeStr.substring(0, 16);
    if (timeStr.includes(' ') && timeStr.length >= 16) {
        const parts = timeStr.split(' ');
        return `${parts[0]}T${parts[1].substring(0, 5)}`;
    }
    const baseDate = dateStr || getActivePunchDate();
    const cleanTime = timeStr.substring(0, 5);
    return `${baseDate}T${cleanTime}`;
}

// Edit existing manual entry
function editManualEntry(recordId, empId, empName, dateStr, punches) {
    document.getElementById('modalTitle').innerText = 'Edit Manual Time Entry - ' + empName;
    document.getElementById('encEmployeeId').value = empId;
    const encDate = document.getElementById('encDate');
    if (encDate) encDate.value = dateStr;

    document.getElementById('encTimeIn').value = toDateTimeInputValue(dateStr, punches.time_in);
    document.getElementById('encBreakOut').value = toDateTimeInputValue(dateStr, punches.break_out);
    document.getElementById('encBreakIn').value = toDateTimeInputValue(dateStr, punches.break_in);
    document.getElementById('encCoffeeOut').value = toDateTimeInputValue(dateStr, punches.coffee_break_out);
    document.getElementById('encCoffeeIn').value = toDateTimeInputValue(dateStr, punches.coffee_break_in);
    document.getElementById('encTimeOut').value = toDateTimeInputValue(dateStr, punches.time_out);

    document.getElementById('encStatus').value = punches.status || 'Auto';
    document.getElementById('encNotes').value = punches.notes || '';

    showLookupAlert('Editing existing attendance record for ' + empName + ' on ' + dateStr, 'info');
    openModal('encodeModal');
}

// Set time to current clock (Date & Time format)
function setNowPunch(inputId) {
    const now = new Date();
    const input = document.getElementById(inputId);
    if (input) {
        input.value = formatLocalDateTime(now);
        syncDateFromPunches();
        lookupPunchesForDate();
    }
}

// Preset standard 8-hr day (Date & Time format)
function applyStandardShiftPreset() {
    const baseDate = getActivePunchDate();
    document.getElementById('encTimeIn').value = `${baseDate}T08:00`;
    document.getElementById('encBreakOut').value = `${baseDate}T12:00`;
    document.getElementById('encBreakIn').value = `${baseDate}T13:00`;
    document.getElementById('encCoffeeOut').value = '';
    document.getElementById('encCoffeeIn').value = '';
    document.getElementById('encTimeOut').value = `${baseDate}T17:00`;
    syncDateFromPunches();
    lookupPunchesForDate();
}

function clearAllPunches() {
    document.getElementById('encTimeIn').value = '';
    document.getElementById('encBreakOut').value = '';
    document.getElementById('encBreakIn').value = '';
    document.getElementById('encCoffeeOut').value = '';
    document.getElementById('encCoffeeIn').value = '';
    document.getElementById('encTimeOut').value = '';
    if (window.resetAllPunchBuffers) window.resetAllPunchBuffers();
}

function showLookupAlert(text, type) {
    const el = document.getElementById('lookupAlert');
    if (!el) return;
    el.style.display = 'block';
    el.innerText = text;
    if (type === 'found') {
        el.style.background = 'rgba(59, 130, 246, 0.12)';
        el.style.color = '#1d4ed8';
        el.style.border = '1px solid rgba(59, 130, 246, 0.3)';
    } else {
        el.style.background = 'rgba(16, 185, 129, 0.12)';
        el.style.color = '#047857';
        el.style.border = '1px solid rgba(16, 185, 129, 0.3)';
    }
}

function hideLookupAlert() {
    const el = document.getElementById('lookupAlert');
    if (el) el.style.display = 'none';
}

// Dynamic lookup when employee or punch date changes in modal
async function lookupPunchesForDate() {
    const empId = document.getElementById('encEmployeeId').value;
    const dateVal = getActivePunchDate();

    if (!empId || !dateVal) {
        hideLookupAlert();
        return;
    }

    try {
        const res = await fetch(`${window.HrCorrectionsConfig?.routes?.lookup || ''}?employee_id=${empId}&date=${dateVal}`);
        const data = await res.json();

        if (data.exists) {
            if (!document.getElementById('encTimeIn').value && data.time_in) {
                document.getElementById('encTimeIn').value = toDateTimeInputValue(dateVal, data.time_in);
            }
            if (!document.getElementById('encBreakOut').value && data.break_out) {
                document.getElementById('encBreakOut').value = toDateTimeInputValue(dateVal, data.break_out);
            }
            if (!document.getElementById('encBreakIn').value && data.break_in) {
                document.getElementById('encBreakIn').value = toDateTimeInputValue(dateVal, data.break_in);
            }
            if (!document.getElementById('encCoffeeOut').value && data.coffee_break_out) {
                document.getElementById('encCoffeeOut').value = toDateTimeInputValue(dateVal, data.coffee_break_out);
            }
            if (!document.getElementById('encCoffeeIn').value && data.coffee_break_in) {
                document.getElementById('encCoffeeIn').value = toDateTimeInputValue(dateVal, data.coffee_break_in);
            }
            if (!document.getElementById('encTimeOut').value && data.time_out) {
                document.getElementById('encTimeOut').value = toDateTimeInputValue(dateVal, data.time_out);
            }
            if (data.status && document.getElementById('encStatus').value === 'Auto') {
                document.getElementById('encStatus').value = data.status;
            }
            if (data.notes && !document.getElementById('encNotes').value) {
                document.getElementById('encNotes').value = data.notes;
            }

            showLookupAlert(`Found existing record for ${dateVal} (${data.total_hours} hrs, Status: ${data.status}). Punches mapped.`, 'found');
        } else {
            showLookupAlert(`No previous attendance record on ${dateVal}. You are encoding a new time entry.`, 'new');
        }
    } catch (e) {
        console.warn('Lookup failed:', e);
    }
}

// Real-Time Table Filtering
function filterManualTable(dateTriggered = false) {
    const searchVal = (document.getElementById('manualSearchInput')?.value || '').toLowerCase().trim();
    const dateVal = (document.getElementById('manualDateFilter')?.value || '').trim();
    const branchVal = (document.getElementById('manualBranchFilter')?.value || '').trim();
    const statusVal = (document.getElementById('manualStatusFilter')?.value || '').trim();

    const rows = document.querySelectorAll('.manual-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const search = row.getAttribute('data-search') || '';
        const rDate = row.getAttribute('data-date') || '';
        const rBranch = row.getAttribute('data-branch') || '';
        const rStatus = row.getAttribute('data-status') || '';

        const matchesSearch = !searchVal || search.includes(searchVal);
        const matchesDate = !dateVal || rDate === dateVal;
        const matchesBranch = !branchVal || rBranch === branchVal;
        const matchesStatus = !statusVal || rStatus === statusVal;

        if (matchesSearch && matchesDate && matchesBranch && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const countBadge = document.getElementById('visibleCount');
    if (countBadge) countBadge.innerText = visibleCount;

    const emptyRow = document.getElementById('emptyTableRow');
    if (emptyRow) {
        emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
    }
}

function applyServerDateFilter(val) {
    if (val) {
        window.location.href = `${window.HrCorrectionsConfig?.routes?.corrections || ''}?date=${val}`;
    } else {
        window.location.href = `${window.HrCorrectionsConfig?.routes?.corrections || ''}`;
    }
}

function resetManualFilters() {
    const s = document.getElementById('manualSearchInput'); if (s) s.value = '';
    const b = document.getElementById('manualBranchFilter'); if (b) b.value = '';
    const st = document.getElementById('manualStatusFilter'); if (st) st.value = '';
    filterManualTable();
}

// Clean Military Time Input Conversion on Datetime-Local Pickers
// Accepts typed military shorthand (e.g. "18" -> converts to 18:00 / 06:00 PM normal time, "1830" -> 18:30 / 06:30 PM normal time)
// and handles seamless Tab navigation between punch fields without getting stuck in native sub-segments.
(function() {
    const punchInputIds = ['encTimeIn', 'encBreakOut', 'encBreakIn', 'encCoffeeOut', 'encCoffeeIn', 'encTimeOut'];
    const timeBuffers = {};

    window.resetAllPunchBuffers = function() {
        punchInputIds.forEach(id => {
            timeBuffers[id] = '';
        });
    };

    function parseTimeShorthand(raw) {
        if (!raw) return null;
        const str = raw.trim();
        if (!str) return null;

        // 1. With colon: "18:30", "18:", "8:30", "8:"
        if (str.includes(':')) {
            const parts = str.split(':');
            const h = parseInt(parts[0], 10);
            if (!isNaN(h) && h >= 0 && h <= 23) {
                let m = 0;
                if (parts[1] && parts[1].length > 0) {
                    const parsedM = parseInt(parts[1].padEnd(2, '0').slice(0, 2), 10);
                    if (!isNaN(parsedM) && parsedM >= 0 && parsedM <= 59) {
                        m = parsedM;
                    }
                }
                return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
            }
            return null;
        }

        // 2. Pure digits shorthand: "18" -> 18:00, "1830" -> 18:30, "8" -> 08:00, "830" -> 08:30
        const digits = str.replace(/\D/g, '');
        if (!digits) return null;

        let hour = null;
        let minute = 0;

        if (digits.length === 1 || digits.length === 2) {
            hour = parseInt(digits, 10);
            minute = 0;
        } else if (digits.length === 3) {
            hour = parseInt(digits.slice(0, 1), 10);
            minute = parseInt(digits.slice(1, 3), 10);
        } else if (digits.length >= 4) {
            hour = parseInt(digits.slice(0, 2), 10);
            minute = parseInt(digits.slice(2, 4), 10);
        }

        if (hour !== null && !isNaN(hour) && hour >= 0 && hour <= 23 && !isNaN(minute) && minute >= 0 && minute <= 59) {
            return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
        }

        return null;
    }

    function applyTimeToInput(input, formattedTime) {
        if (!input || !formattedTime) return false;

        let datePart = '';
        if (input.value && input.value.includes('T')) {
            datePart = input.value.split('T')[0];
        } else {
            datePart = getActivePunchDate();
        }

        input.value = `${datePart}T${formattedTime}`;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        syncDateFromPunches();
        return true;
    }

    function advanceToNext(currentIdx) {
        if (currentIdx < punchInputIds.length - 1) {
            const next = document.getElementById(punchInputIds[currentIdx + 1]);
            if (next) {
                next.focus();
                timeBuffers[punchInputIds[currentIdx + 1]] = '';
            }
        } else {
            const statusSelect = document.getElementById('encStatus');
            if (statusSelect) statusSelect.focus();
        }
    }

    function initPunchInputs() {
        punchInputIds.forEach((id, idx) => {
            const input = document.getElementById(id);
            if (!input) return;

            timeBuffers[id] = '';

            input.addEventListener('focus', function() {
                timeBuffers[id] = '';
            });

            input.addEventListener('keydown', function(e) {
                if ((e.key >= '0' && e.key <= '9') || e.key === ':') {
                    timeBuffers[id] = (timeBuffers[id] || '') + e.key;
                    if (timeBuffers[id].length > 5) {
                        timeBuffers[id] = timeBuffers[id].slice(-5);
                    }
                    return;
                }

                if (e.key === 'Backspace') {
                    timeBuffers[id] = (timeBuffers[id] || '').slice(0, -1);
                    return;
                }

                if (e.key === 'Delete' || e.key === 'Escape') {
                    timeBuffers[id] = '';
                    return;
                }

                // Shift+Tab: move to previous punch input
                if (e.key === 'Tab' && e.shiftKey) {
                    e.preventDefault();
                    if (idx > 0) {
                        const prev = document.getElementById(punchInputIds[idx - 1]);
                        if (prev) prev.focus();
                    } else {
                        const emp = document.getElementById('encEmployeeId');
                        if (emp) emp.focus();
                    }
                    return;
                }

                // Tab or Enter: apply military shorthand if typed, and advance to next input!
                if ((e.key === 'Tab' && !e.shiftKey) || e.key === 'Enter') {
                    const raw = timeBuffers[id];
                    timeBuffers[id] = '';
                    const parsedTime = parseTimeShorthand(raw);

                    if (parsedTime) {
                        e.preventDefault();
                        applyTimeToInput(this, parsedTime);
                        if (id === 'encTimeIn') {
                            lookupPunchesForDate();
                        }
                        advanceToNext(idx);
                        return;
                    }

                    // If user pressed Tab (even without shorthand), advance directly to next punch input
                    if (e.key === 'Tab') {
                        e.preventDefault();
                        advanceToNext(idx);
                    }
                }
            });

            input.addEventListener('blur', function() {
                const raw = timeBuffers[id];
                timeBuffers[id] = '';
                const parsedTime = parseTimeShorthand(raw);
                if (parsedTime) {
                    applyTimeToInput(this, parsedTime);
                    if (id === 'encTimeIn') {
                        lookupPunchesForDate();
                    }
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPunchInputs);
    } else {
        initPunchInputs();
    }
})();
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
    openModal,
    closeModal,
    openEncodeModal,
    getActivePunchDate,
    syncDateFromPunches,
    formatLocalDateTime,
    toDateTimeInputValue,
    editManualEntry,
    setNowPunch,
    applyStandardShiftPreset,
    clearAllPunches,
    showLookupAlert,
    hideLookupAlert,
    lookupPunchesForDate,
    filterManualTable,
    applyServerDateFilter,
    resetManualFilters,
    parseTimeShorthand,
    applyTimeToInput,
    advanceToNext,
    initPunchInputs,
});
