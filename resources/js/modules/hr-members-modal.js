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
    fetch(`${window.HrMembersModalConfig?.routes?.membersModal || '/hr/people/members-modal'}?type=${type}&id=${id}`)
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
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
    openMembersModal,
    renderMembersModalPage,
    renderPaginationButtons,
    changeMembersModalPerPage,
    scrollModalTableToTop,
    filterMembersModalList,
    clearMembersModalSearch,
    sortMembersModalBy,
    applyCurrentSort,
    resetSortIcons,
});
