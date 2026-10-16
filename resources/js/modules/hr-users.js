/**
 * User Accounts Administration Module
 */

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// =========================================================================
// Client-Side Pagination — Users
// =========================================================================
let _usersPage = 1, _usersRows = [];
function usersGetPerPage() { return parseInt(document.getElementById('usersPerPageSelect')?.value || '10', 10); }
function usersChangePerPage() { _usersPage = 1; renderUsersPage(); }
function usersGoToPage(p) { _usersPage = p; renderUsersPage(); const w = document.getElementById('usersTableWrapper'); if(w) w.scrollTop = 0; }

function renderUsersPage() {
    const pp = usersGetPerPage(), total = _usersRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pp));
    if (_usersPage > totalPages) _usersPage = totalPages;
    const start = (_usersPage - 1) * pp, end = Math.min(start + pp, total);

    document.querySelectorAll('#usersTableBody tr.user-row').forEach(r => r.style.display = 'none');
    _usersRows.forEach((r, i) => { r.style.display = (i >= start && i < end) ? '' : 'none'; });


    const bar = document.getElementById('usersPaginationBar');
    if (bar) bar.style.display = total > 0 ? 'flex' : 'none';
    const info = document.getElementById('usersPaginationInfo');
    if (info) info.innerHTML = `Showing <strong>${total === 0 ? 0 : start+1}</strong> to <strong>${end}</strong> of <strong>${total}</strong> users`;
    const nav = document.getElementById('usersPageNav');
    if (!nav) return;
    let h = _usersPage === 1
        ? `<span class="hr-page-btn disabled"><i class="ph ph-caret-left"></i><span>Prev</span></span>`
        : `<span class="hr-page-btn" onclick="usersGoToPage(${_usersPage-1})" style="cursor:pointer"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    h += `<div class="hr-page-numbers">`;
    let ps = Math.max(1, _usersPage-2), pe = Math.min(totalPages, _usersPage+2);
    if (ps > 1) { h += `<span class="hr-page-num" onclick="usersGoToPage(1)" style="cursor:pointer">1</span>`; if (ps > 2) h += `<span class="hr-page-num dots">…</span>`; }
    for (let p = ps; p <= pe; p++) h += p === _usersPage ? `<span class="hr-page-num active">${p}</span>` : `<span class="hr-page-num" onclick="usersGoToPage(${p})" style="cursor:pointer">${p}</span>`;
    if (pe < totalPages) { if (pe < totalPages-1) h += `<span class="hr-page-num dots">…</span>`; h += `<span class="hr-page-num" onclick="usersGoToPage(${totalPages})" style="cursor:pointer">${totalPages}</span>`; }
    h += `</div>`;
    h += _usersPage >= totalPages
        ? `<span class="hr-page-btn disabled"><span>Next</span><i class="ph ph-caret-right"></i></span>`
        : `<span class="hr-page-btn" onclick="usersGoToPage(${_usersPage+1})" style="cursor:pointer"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    nav.innerHTML = h;
}

window.refreshUsersPage = function() {
    _usersRows = Array.from(document.querySelectorAll('#usersTableBody tr.user-row'));
    renderUsersPage();
};

document.addEventListener('DOMContentLoaded', () => {
    window.refreshUsersPage();
});
document.addEventListener('rmsTableSorted', () => {
    window.refreshUsersPage();
});

function editUser(user) {
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_branch_id').value = user.branch_id || '';
    document.getElementById('edit_status').value = user.status;
    document.getElementById('edit_employee_id').value = user.employee ? user.employee.id : '';
    if (user.roles && user.roles.length > 0) {
        document.getElementById('edit_role_id').value = user.roles[0].id;
    }
    document.getElementById('editUserForm').action = "/hr/admin/users/" + user.id;
    openModal('editUserModal');
}

function resetPassword(userId, username) {
    document.getElementById('reset_username_label').innerText = '@' + username;
    document.getElementById('resetPasswordForm').action = "/hr/admin/users/" + userId + "/reset-password";
    openModal('resetPasswordModal');
}

Object.assign(window, {
    openModal,
    closeModal,
    usersGetPerPage,
    usersChangePerPage,
    usersGoToPage,
    renderUsersPage,
    refreshUsersPage,
    editUser,
    resetPassword,
});
