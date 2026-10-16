/**
 * build_hr_payroll_remaining_assets.cjs
 * Modularizes the remaining 6 HR Payroll views:
 * 1. holidays.blade.php
 * 2. payslip-detail.blade.php
 * 3. payslips.blade.php
 * 4. register.blade.php
 * 5. statutory-rules.blade.php
 * 6. periods.blade.php
 */

const fs = require('fs');
const path = require('path');

// ==========================================
// 1. holidays.blade.php
// ==========================================
console.log('Processing holidays.blade.php...');
const holidaysBladePath = 'resources/views/hr/payroll/holidays.blade.php';
const holidaysRaw = fs.readFileSync(holidaysBladePath, 'utf8');
const holidaysLines = holidaysRaw.split('\n');

// Script is lines 712 to 860 (0-indexed: 711 to 859)
// HTML content is lines 6 to 711 (0-indexed: 5 to 710)
const holidaysHtmlLines = holidaysLines.slice(5, 711);

const holidaysCss = `/* Enterprise Philippine Payroll - Holidays Management */
.holiday-row {
    transition: background-color 0.15s ease;
}

.holiday-row:hover {
    background-color: rgba(241, 245, 249, 0.6);
}

.holiday-drawer {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
`;

const holidaysJs = `/**
 * Enterprise Philippine Payroll - Holidays Management Module
 */

export function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}

export function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

// Filter Holidays client-side without page reload
export function applyHolidayFilters() {
    const q = (document.getElementById('holidaySearchInput')?.value || '').toLowerCase().trim();
    const type = document.getElementById('filterHolidayType')?.value;
    const scope = document.getElementById('filterScope')?.value;
    const branch = document.getElementById('filterBranch')?.value;
    const status = document.getElementById('filterStatus')?.value;

    const rows = document.querySelectorAll('.holiday-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        const hType = row.getAttribute('data-type');
        const hScope = row.getAttribute('data-scope');
        const hStatus = row.getAttribute('data-status');
        const branches = JSON.parse(row.getAttribute('data-branches') || '[]');

        let match = true;

        if (q && !name.includes(q)) match = false;
        if (type && hType !== type) match = false;
        if (scope && hScope !== scope) match = false;
        if (status && hStatus !== status) match = false;
        if (branch && branches.length > 0 && !branches.includes(branch) && !branches.includes(parseInt(branch))) {
            match = false;
        }

        row.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    });

    const badge = document.getElementById('tableHolidayCountBadge');
    if (badge) badge.innerText = \`\${visibleCount} holidays\`;

    const emptyRow = document.getElementById('emptyHolidayRow');
    if (emptyRow) {
        emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
    }
}

export function resetHolidayFilters() {
    const searchInput = document.getElementById('holidaySearchInput');
    const typeInput = document.getElementById('filterHolidayType');
    const scopeInput = document.getElementById('filterScope');
    const branchInput = document.getElementById('filterBranch');
    const statusInput = document.getElementById('filterStatus');

    if (searchInput) searchInput.value = '';
    if (typeInput) typeInput.value = '';
    if (scopeInput) scopeInput.value = '';
    if (branchInput) branchInput.value = '';
    if (statusInput) statusInput.value = '';
    applyHolidayFilters();
}

// Open Detail Drawer
export function viewHolidayDetails(id) {
    const row = document.querySelector(\`.holiday-row[data-id="\${id}"]\`);
    if (!row) return;

    const data = JSON.parse(row.getAttribute('data-json'));

    const dtlName = document.getElementById('dtlName');
    const dtlDate = document.getElementById('dtlDate');
    const dtlTypeBadge = document.getElementById('dtlTypeBadge');
    const dtlScope = document.getElementById('dtlScope');
    const dtlStatus = document.getElementById('dtlStatus');
    const dtlReference = document.getElementById('dtlReference');
    const dtlTreatment = document.getElementById('dtlTreatment');
    const dtlLocations = document.getElementById('dtlLocations');

    if (dtlName) dtlName.innerText = data.name;
    if (dtlDate) dtlDate.innerText = data.date ? new Date(data.date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric', weekday: 'long' }) : '';
    if (dtlTypeBadge) dtlTypeBadge.innerText = data.holiday_type;
    if (dtlScope) dtlScope.innerText = data.scope;
    if (dtlStatus) dtlStatus.innerText = data.is_active ? 'Active' : 'Inactive';
    if (dtlReference) dtlReference.innerText = data.official_reference || 'DOLE Statutory Labor Code';
    if (dtlTreatment) dtlTreatment.innerText = data.payroll_treatment || 'Statutory DOLE Rules Apply';
    if (dtlLocations) {
        dtlLocations.innerText = (data.scope === 'Nationwide' || !data.applicable_branches || data.applicable_branches.length === 0) 
            ? 'All Branches Nationwide' 
            : \`\${data.applicable_branches.length} Selected Branches\`;
    }

    // Calculation rules display based on type
    const dtlRuleUnworked = document.getElementById('dtlRuleUnworked');
    const dtlRuleWorked = document.getElementById('dtlRuleWorked');
    const dtlRuleRestDay = document.getElementById('dtlRuleRestDay');
    const dtlRuleOt = document.getElementById('dtlRuleOt');

    if (data.holiday_type === 'Regular Holiday') {
        if (dtlRuleUnworked) dtlRuleUnworked.innerText = '100%';
        if (dtlRuleWorked) dtlRuleWorked.innerText = '200%';
        if (dtlRuleRestDay) dtlRuleRestDay.innerText = '+30% (260%)';
        if (dtlRuleOt) dtlRuleOt.innerText = '+30% of day rate';
    } else if (data.holiday_type === 'Special Non-Working') {
        if (dtlRuleUnworked) dtlRuleUnworked.innerText = '0% (No work no pay)';
        if (dtlRuleWorked) dtlRuleWorked.innerText = '130%';
        if (dtlRuleRestDay) dtlRuleRestDay.innerText = '+50% (150%)';
        if (dtlRuleOt) dtlRuleOt.innerText = '+30% of day rate';
    } else if (data.holiday_type === 'Special Working') {
        if (dtlRuleUnworked) dtlRuleUnworked.innerText = '100%';
        if (dtlRuleWorked) dtlRuleWorked.innerText = '100%';
        if (dtlRuleRestDay) dtlRuleRestDay.innerText = '130%';
        if (dtlRuleOt) dtlRuleOt.innerText = '125%';
    } else {
        if (dtlRuleUnworked) dtlRuleUnworked.innerText = 'Per Company Policy';
        if (dtlRuleWorked) dtlRuleWorked.innerText = '130%';
        if (dtlRuleRestDay) dtlRuleRestDay.innerText = '150%';
        if (dtlRuleOt) dtlRuleOt.innerText = '+30%';
    }

    openModal('holidayDetailDrawer');
}

// Edit Holiday
export function editHoliday(id) {
    const row = document.querySelector(\`.holiday-row[data-id="\${id}"]\`);
    if (!row) return;

    const data = JSON.parse(row.getAttribute('data-json'));
    const form = document.getElementById('editHolidayForm');
    const baseUrl = window.HrHolidaysConfig?.baseUrl || '/hr/payroll/holidays';
    if (form) form.action = \`\${baseUrl}/\${id}\`;

    const nameInput = document.getElementById('editHolidayName');
    const yearInput = document.getElementById('editHolidayYear');
    const dateInput = document.getElementById('editHolidayDate');
    const typeInput = document.getElementById('editHolidayType');
    const scopeInput = document.getElementById('editHolidayScope');
    const refInput = document.getElementById('editHolidayRef');
    const descInput = document.getElementById('editHolidayDesc');
    const activeInput = document.getElementById('editIsActive');

    if (nameInput) nameInput.value = data.name;
    if (yearInput) yearInput.value = data.year;
    if (dateInput) dateInput.value = data.date ? data.date.substring(0, 10) : '';
    if (typeInput) typeInput.value = data.holiday_type;
    if (scopeInput) scopeInput.value = data.scope;
    if (refInput) refInput.value = data.official_reference || '';

    // Set Payroll Treatment select dropdown
    const treatmentSelect = document.getElementById('editPayrollTreatment');
    if (treatmentSelect) {
        let found = false;
        for (let opt of treatmentSelect.options) {
            if (opt.value === data.payroll_treatment) {
                treatmentSelect.value = data.payroll_treatment;
                found = true;
                break;
            }
        }
        if (!found && data.payroll_treatment) {
            const customOpt = new Option(data.payroll_treatment, data.payroll_treatment, true, true);
            treatmentSelect.add(customOpt);
        }
    }

    if (descInput) descInput.value = data.description || '';
    if (activeInput) activeInput.checked = !!data.is_active;

    openModal('editHolidayModal');
}

export function onHolidayTypeChange(type, prefix) {
    const treatmentSelect = document.getElementById(\`\${prefix}PayrollTreatment\`);
    if (!treatmentSelect) return;

    if (type === 'Regular Holiday') {
        treatmentSelect.value = '100% unworked / 200% worked (+30% rest day)';
    } else if (type === 'Special Non-Working') {
        treatmentSelect.value = 'No work no pay / 130% worked (+50% rest day)';
    } else if (type === 'Special Working') {
        treatmentSelect.value = '100% standard rate (no premium)';
    } else {
        treatmentSelect.value = '130% worked rate';
    }
}

// Global window exposure
Object.assign(window, {
    openModal,
    closeModal,
    applyHolidayFilters,
    resetHolidayFilters,
    viewHolidayDetails,
    editHoliday,
    onHolidayTypeChange,
});

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('holidaySearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', applyHolidayFilters);
    }
});
`;

const newHolidaysBlade = `@extends('layouts.app')

@section('title', 'Holidays Management - Enterprise Philippine Payroll')

@push('blade-data')
<script>
    window.HrHolidaysConfig = Object.freeze({
        baseUrl: @json(url('hr/payroll/holidays'))
    });
</script>
@endpush

@push('styles')
    @vite(['resources/css/modules/hr-holidays.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-holidays.js'])
@endpush

@section('content')
` + holidaysHtmlLines.join('\n') + `
@endsection
`;

fs.writeFileSync('resources/css/modules/hr-holidays.css', holidaysCss, 'utf8');
fs.writeFileSync('resources/js/modules/hr-holidays.js', holidaysJs, 'utf8');
fs.writeFileSync(holidaysBladePath, newHolidaysBlade, 'utf8');
console.log('Saved holidays.blade.php, hr-holidays.css, hr-holidays.js');


// ==========================================
// 2. payslip-detail.blade.php
// ==========================================
console.log('Processing payslip-detail.blade.php...');
const payslipDetailPath = 'resources/views/hr/payroll/payslip-detail.blade.php';
const payslipDetailRaw = fs.readFileSync(payslipDetailPath, 'utf8');
const payslipDetailLines = payslipDetailRaw.split('\n');

// Style is lines 7 to 437 in file (0-indexed: 6 to 437)
const payslipDetailCss = payslipDetailLines.slice(6, 437).join('\n').trim() + '\n';
// HTML content is lines 442 to 803 (0-indexed: 441 to 803)
const payslipDetailHtml = payslipDetailLines.slice(441, 803).join('\n');

const payslipDetailJs = `/**
 * Enterprise Philippine Payroll - Payslip Detail Module
 */

export function openPayslipModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.style.display = 'flex';
}

export function closePayslipModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.style.display = 'none';
}

Object.assign(window, {
    openPayslipModal,
    closePayslipModal,
});
`;

const newPayslipDetailBlade = `@extends('layouts.app')

@section('title', 'Payslip - ' . ($record->employee?->full_name ?? 'Employee'))

@push('styles')
    @vite(['resources/css/modules/hr-payslip-detail.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-payslip-detail.js'])
@endpush

@section('content')
` + payslipDetailHtml + `
@endsection
`;

fs.writeFileSync('resources/css/modules/hr-payslip-detail.css', payslipDetailCss, 'utf8');
fs.writeFileSync('resources/js/modules/hr-payslip-detail.js', payslipDetailJs, 'utf8');
fs.writeFileSync(payslipDetailPath, newPayslipDetailBlade, 'utf8');
console.log('Saved payslip-detail.blade.php, hr-payslip-detail.css, hr-payslip-detail.js');


// ==========================================
// 3. payslips.blade.php
// ==========================================
console.log('Processing payslips.blade.php...');
const payslipsPath = 'resources/views/hr/payroll/payslips.blade.php';
const payslipsRaw = fs.readFileSync(payslipsPath, 'utf8');
const payslipsLines = payslipsRaw.split('\n');

// Style is lines 7 to 186 in file (0-indexed: 6 to 186)
const payslipsCss = payslipsLines.slice(6, 186).join('\n').trim() + '\n';
// HTML content is lines 191 to 313 (0-indexed: 190 to 313)
const payslipsHtml = payslipsLines.slice(190, 313).join('\n');

const payslipsJs = `/**
 * Enterprise Philippine Payroll - Payslips List Module
 */

document.addEventListener('DOMContentLoaded', () => {
    // Toolbar search and filters ready
});
`;

const newPayslipsBlade = `@extends('layouts.app')

@section('title', 'Employee Payslips - Philippine Payroll Engine')

@push('styles')
    @vite(['resources/css/modules/hr-payslips.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-payslips.js'])
@endpush

@section('content')
` + payslipsHtml + `
@endsection
`;

fs.writeFileSync('resources/css/modules/hr-payslips.css', payslipsCss, 'utf8');
fs.writeFileSync('resources/js/modules/hr-payslips.js', payslipsJs, 'utf8');
fs.writeFileSync(payslipsPath, newPayslipsBlade, 'utf8');
console.log('Saved payslips.blade.php, hr-payslips.css, hr-payslips.js');


// ==========================================
// 4. register.blade.php
// ==========================================
console.log('Processing register.blade.php...');
const registerPath = 'resources/views/hr/payroll/register.blade.php';
const registerRaw = fs.readFileSync(registerPath, 'utf8');
const registerLines = registerRaw.split('\n');

// Style is lines 7 to 104 in file (0-indexed: 6 to 104)
const registerCss = registerLines.slice(6, 104).join('\n').trim() + '\n';
// HTML content is lines 109 to 289 (0-indexed: 108 to 289)
const registerHtml = registerLines.slice(108, 289).join('\n');

const registerJs = `/**
 * Enterprise Philippine Payroll - Payroll Register Module
 */

export function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}

export function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

export function openAdjModal(recId, name) {
    const adjRec = document.getElementById('adjRecordId');
    const adjStaff = document.getElementById('adjStaffName');
    if (adjRec) adjRec.value = recId;
    if (adjStaff) adjStaff.innerText = name;
    openModal('payrollAdjModal');
}

Object.assign(window, {
    openModal,
    closeModal,
    openAdjModal,
});
`;

const newRegisterBlade = `@extends('layouts.app')

@section('title', 'Payroll Register - Philippine Payroll Engine')

@push('styles')
    @vite(['resources/css/modules/hr-register.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-register.js'])
@endpush

@section('content')
` + registerHtml + `
@endsection
`;

fs.writeFileSync('resources/css/modules/hr-register.css', registerCss, 'utf8');
fs.writeFileSync('resources/js/modules/hr-register.js', registerJs, 'utf8');
fs.writeFileSync(registerPath, newRegisterBlade, 'utf8');
console.log('Saved register.blade.php, hr-register.css, hr-register.js');


// ==========================================
// 5. statutory-rules.blade.php
// ==========================================
console.log('Processing statutory-rules.blade.php...');
const statutoryPath = 'resources/views/hr/payroll/statutory-rules.blade.php';
const statutoryRaw = fs.readFileSync(statutoryPath, 'utf8');
const statutoryLines = statutoryRaw.split('\n');

// HTML content is lines 6 to 137 (0-indexed: 5 to 137)
const statutoryHtml = statutoryLines.slice(5, 137).join('\n');

const statutoryCss = `/* Enterprise Philippine Payroll - Statutory Rules Module */
.hr-table th, .hr-table td {
    vertical-align: middle;
}
`;

const statutoryJs = `/**
 * Enterprise Philippine Payroll - Statutory Rules Module
 */

export function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}

export function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

export function openRuleModal(id, name, rate, min, max, ee, er, fixed, eff, active) {
    const form = document.getElementById('ruleForm');
    if (form) form.action = '/hr/payroll/statutory-rules/' + id;
    
    const ruleName = document.getElementById('ruleName');
    const ruleRate = document.getElementById('ruleRate');
    const ruleMin = document.getElementById('ruleMin');
    const ruleMax = document.getElementById('ruleMax');
    const ruleEe = document.getElementById('ruleEe');
    const ruleEr = document.getElementById('ruleEr');
    const ruleFixed = document.getElementById('ruleFixed');
    const ruleEff = document.getElementById('ruleEff');
    const ruleActive = document.getElementById('ruleActive');

    if (ruleName) ruleName.value = name;
    if (ruleRate) ruleRate.value = rate;
    if (ruleMin) ruleMin.value = min;
    if (ruleMax) ruleMax.value = max;
    if (ruleEe) ruleEe.value = ee;
    if (ruleEr) ruleEr.value = er;
    if (ruleFixed) ruleFixed.value = fixed;
    if (ruleEff) ruleEff.value = eff;
    if (ruleActive) ruleActive.checked = active;

    openModal('ruleModal');
}

Object.assign(window, {
    openModal,
    closeModal,
    openRuleModal,
});
`;

const newStatutoryBlade = `@extends('layouts.app')

@section('title', 'Philippine Statutory Rules - Payroll')

@push('styles')
    @vite(['resources/css/modules/hr-statutory-rules.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-statutory-rules.js'])
@endpush

@section('content')
` + statutoryHtml + `
@endsection
`;

fs.writeFileSync('resources/css/modules/hr-statutory-rules.css', statutoryCss, 'utf8');
fs.writeFileSync('resources/js/modules/hr-statutory-rules.js', statutoryJs, 'utf8');
fs.writeFileSync(statutoryPath, newStatutoryBlade, 'utf8');
console.log('Saved statutory-rules.blade.php, hr-statutory-rules.css, hr-statutory-rules.js');


// ==========================================
// 6. periods.blade.php
// ==========================================
console.log('Processing periods.blade.php...');
const periodsPath = 'resources/views/hr/payroll/periods.blade.php';
const periodsRaw = fs.readFileSync(periodsPath, 'utf8');
const periodsLines = periodsRaw.split('\n');

// HTML content is lines 6 to 132 (0-indexed: 5 to 132)
const periodsHtml = periodsLines.slice(5, 132).join('\n');

const periodsCss = `/* Enterprise Philippine Payroll - Payroll Periods Module */
.hr-table th, .hr-table td {
    vertical-align: middle;
}
`;

const periodsJs = `/**
 * Enterprise Philippine Payroll - Payroll Periods Module
 */

export function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}

export function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

Object.assign(window, {
    openModal,
    closeModal,
});
`;

const newPeriodsBlade = `@extends('layouts.app')

@section('title', 'Payroll Periods - Philippine Payroll Engine')

@push('styles')
    @vite(['resources/css/modules/hr-periods.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-periods.js'])
@endpush

@section('content')
` + periodsHtml + `
@endsection
`;

fs.writeFileSync('resources/css/modules/hr-periods.css', periodsCss, 'utf8');
fs.writeFileSync('resources/js/modules/hr-periods.js', periodsJs, 'utf8');
fs.writeFileSync(periodsPath, newPeriodsBlade, 'utf8');
console.log('Saved periods.blade.php, hr-periods.css, hr-periods.js');

console.log('ALL 6 PAYROLL MODULES EXTRACTED SUCCESSFULLY!');
