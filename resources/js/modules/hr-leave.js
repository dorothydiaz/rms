/**
 * Leave & Absence Management Module
 */

export function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open', 'active');
}

export function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open', 'active');
}

export function openReviewModal(reqId, empName, leaveType, days) {
    const elEmp = document.getElementById('reviewLeaveEmp');
    const elDetails = document.getElementById('reviewLeaveDetails');
    const elForm = document.getElementById('reviewLeaveForm');
    if (elEmp) elEmp.innerText = empName;
    if (elDetails) elDetails.innerText = leaveType + ' - ' + days + ' day(s)';
    if (elForm) elForm.action = '/hr/leave/requests/' + reqId + '/review';
    openModal('reviewLeaveModal');
}

export function openAdjustModal(empId, typeId, empName, typeName, beg, earned, used, encashed) {
    const year = window.HrLeaveCreditsConfig?.year || '';
    const elEmpId = document.getElementById('adjEmpId');
    const elTypeId = document.getElementById('adjTypeId');
    const elEmpName = document.getElementById('adjEmpName');
    const elTypeName = document.getElementById('adjTypeName');
    const elBeg = document.getElementById('adjBeg');
    const elEarned = document.getElementById('adjEarned');
    const elUsed = document.getElementById('adjUsed');
    const elEncashed = document.getElementById('adjEncashed');

    if (elEmpId) elEmpId.value = empId;
    if (elTypeId) elTypeId.value = typeId;
    if (elEmpName) elEmpName.innerText = empName;
    if (elTypeName) elTypeName.innerText = typeName + (year ? ' (Year ' + year + ')' : '');
    if (elBeg) elBeg.value = beg;
    if (elEarned) elEarned.value = earned;
    if (elUsed) elUsed.value = used;
    if (elEncashed) elEncashed.value = encashed;
    openModal('adjustModal');
}

Object.assign(window, {
    openModal,
    closeModal,
    openReviewModal,
    openAdjustModal,
});
