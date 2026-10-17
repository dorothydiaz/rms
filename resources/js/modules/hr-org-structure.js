/**
 * ============================================================================
 * HR Org Structure - Interactive Tree, Pan/Zoom & Manager Reassignment Engine
 * ============================================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    initOrgChart();
});

function initOrgChart() {
    const canvasViewport = document.getElementById('orgCanvasViewport') || document.querySelector('.org-canvas-viewport');
    const canvasContent = document.getElementById('orgCanvasContent');
    const zoomLevelEl = document.getElementById('orgZoomLevel');
    const searchInput = document.getElementById('orgSearchInput');
    const deptFilter = document.getElementById('orgDeptFilter');
    const branchFilter = document.getElementById('orgBranchFilter');
    const companyFilter = document.getElementById('orgCompanyFilter');

    if (!canvasViewport || !canvasContent) return;

    // Pan & Zoom State
    let scale = 1;
    let translateX = 0;
    let translateY = 0;
    let isDragging = false;
    let startX = 0;
    let startY = 0;

    // Center & Auto-Fit Initially
    function centerCanvas() {
        const viewportWidth = canvasViewport.clientWidth;
        const treeEl = canvasContent.querySelector('.org-tree') || canvasContent;
        const contentWidth = treeEl.scrollWidth || 900;

        // Auto-scale if hierarchy is wider than viewport
        let fitScale = 1;
        if (contentWidth > (viewportWidth - 60)) {
            fitScale = Math.max(0.45, Math.min(1, (viewportWidth - 60) / contentWidth));
        }
        scale = fitScale;
        translateX = Math.max(20, (viewportWidth - contentWidth * scale) / 2);
        translateY = 40;
        updateTransform();
    }

    function fitToScreen() {
        const viewportWidth = canvasViewport.clientWidth;
        const viewportHeight = canvasViewport.clientHeight;
        const treeEl = canvasContent.querySelector('.org-tree') || canvasContent;
        const contentWidth = treeEl.scrollWidth || 900;
        const contentHeight = treeEl.scrollHeight || 500;

        const scaleX = (viewportWidth - 60) / contentWidth;
        const scaleY = (viewportHeight - 80) / contentHeight;
        scale = Math.max(0.35, Math.min(1, Math.min(scaleX, scaleY)));
        translateX = Math.max(20, (viewportWidth - contentWidth * scale) / 2);
        translateY = 30;
        updateTransform();
    }

    function updateTransform() {
        canvasContent.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
        if (zoomLevelEl) {
            zoomLevelEl.textContent = `${Math.round(scale * 100)}%`;
        }
    }

    // ------------------------------------------------------------------------
    // Mouse Panning Listeners
    // ------------------------------------------------------------------------
    canvasViewport.addEventListener('mousedown', (e) => {
        // Ignore clicks on buttons, inputs, links, or the unassigned drawer
        if (e.target.closest('button') || e.target.closest('input') || e.target.closest('select') || e.target.closest('a') || e.target.closest('.org-unassigned-drawer')) {
            return;
        }
        isDragging = true;
        startX = e.clientX - translateX;
        startY = e.clientY - translateY;
        canvasViewport.classList.add('is-dragging');
    });

    window.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        translateX = e.clientX - startX;
        translateY = e.clientY - startY;
        updateTransform();
    });

    window.addEventListener('mouseup', () => {
        if (isDragging) {
            isDragging = false;
            canvasViewport.classList.remove('is-dragging');
        }
    });

    // ------------------------------------------------------------------------
    // Touchscreen Panning (Mobile / Tablet)
    // ------------------------------------------------------------------------
    canvasViewport.addEventListener('touchstart', (e) => {
        if (e.touches.length === 1) {
            if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input') || e.target.closest('select')) return;
            isDragging = true;
            startX = e.touches[0].clientX - translateX;
            startY = e.touches[0].clientY - translateY;
        }
    }, { passive: true });

    window.addEventListener('touchmove', (e) => {
        if (!isDragging || e.touches.length !== 1) return;
        translateX = e.touches[0].clientX - startX;
        translateY = e.touches[0].clientY - startY;
        updateTransform();
    }, { passive: true });

    window.addEventListener('touchend', () => {
        isDragging = false;
    });

    // ------------------------------------------------------------------------
    // Mouse Wheel Zoom
    // ------------------------------------------------------------------------
    canvasViewport.addEventListener('wheel', (e) => {
        e.preventDefault();
        const zoomDelta = e.deltaY < 0 ? 0.08 : -0.08;
        applyZoom(scale + zoomDelta);
    }, { passive: false });

    function applyZoom(newScale) {
        scale = Math.min(Math.max(0.35, newScale), 1.6);
        updateTransform();
    }

    // Zoom Buttons
    document.getElementById('orgZoomInBtn')?.addEventListener('click', () => applyZoom(scale + 0.15));
    document.getElementById('orgZoomOutBtn')?.addEventListener('click', () => applyZoom(scale - 0.15));
    document.getElementById('orgZoomResetBtn')?.addEventListener('click', () => centerCanvas());
    document.getElementById('orgZoomFitBtn')?.addEventListener('click', () => fitToScreen());

    // ------------------------------------------------------------------------
    // View Mode Toggle (Tree, Grid, Table)
    // ------------------------------------------------------------------------
    const viewButtons = document.querySelectorAll('.org-view-btn');
    const treeView = document.getElementById('orgTreeView');
    const gridView = document.getElementById('orgGridView');
    const tableView = document.getElementById('orgTableView');
    const zoomControls = document.getElementById('orgZoomControls');

    viewButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            viewButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const mode = btn.dataset.view;

            if (mode === 'tree') {
                if (treeView) treeView.style.display = 'block';
                if (gridView) gridView.style.display = 'none';
                if (tableView) tableView.style.display = 'none';
                if (zoomControls) zoomControls.style.display = 'inline-flex';
                setTimeout(centerCanvas, 50);
            } else if (mode === 'grid') {
                if (treeView) treeView.style.display = 'none';
                if (gridView) gridView.style.display = 'grid';
                if (tableView) tableView.style.display = 'none';
                if (zoomControls) zoomControls.style.display = 'none';
            } else if (mode === 'table') {
                if (treeView) treeView.style.display = 'none';
                if (gridView) gridView.style.display = 'none';
                if (tableView) tableView.style.display = 'block';
                if (zoomControls) zoomControls.style.display = 'none';
            }
        });
    });

    // ------------------------------------------------------------------------
    // Unified Multi-Facet Filtering Engine (Dept, Branch, Company, Search)
    // ------------------------------------------------------------------------
    let searchTimeout = null;

    function applyFilters() {
        const deptVal = deptFilter ? deptFilter.value.trim().toLowerCase() : '';
        const branchVal = branchFilter ? branchFilter.value.trim().toLowerCase() : '';
        const companyVal = companyFilter ? companyFilter.value.trim().toLowerCase() : '';
        const searchVal = searchInput ? searchInput.value.trim().toLowerCase() : '';

        const cards = document.querySelectorAll('.org-node-card');
        const rows = document.querySelectorAll('.org-dir-row');
        const gridItems = document.querySelectorAll('.org-grid-staff-item');
        const deptBoxes = document.querySelectorAll('.org-grid-dept-box');

        let firstMatchCard = null;

        // 1. Tree Cards Filtering
        cards.forEach(card => {
            const cardDept = card.dataset.department?.toLowerCase() || '';
            const cardBranch = card.dataset.branch?.toLowerCase() || '';
            const cardCompany = card.dataset.company?.toLowerCase() || '';
            const cardName = card.dataset.name?.toLowerCase() || '';
            const cardPos = card.dataset.position?.toLowerCase() || '';

            const deptMatch = !deptVal || cardDept === deptVal;
            const branchMatch = !branchVal || cardBranch === branchVal;
            const companyMatch = !companyVal || cardCompany.includes(companyVal);
            const searchMatch = !searchVal || cardName.includes(searchVal) || cardPos.includes(searchVal) || cardDept.includes(searchVal) || cardCompany.includes(searchVal);

            const isMatch = deptMatch && branchMatch && companyMatch && searchMatch;

            if (isMatch) {
                card.classList.remove('dimmed');
                if (searchVal) {
                    card.classList.add('highlighted');
                    if (!firstMatchCard) firstMatchCard = card;

                    // Ensure parent nodes are visible if collapsed
                    let parent = card.closest('ul');
                    while (parent && parent.classList.contains('org-sub-tree')) {
                        parent.style.display = 'flex';
                        parent = parent.parentElement.closest('ul');
                    }
                } else {
                    card.classList.remove('highlighted');
                }
            } else {
                card.classList.add('dimmed');
                card.classList.remove('highlighted');
            }
        });

        // Center on matched card in Tree view if search query is active
        if (firstMatchCard && treeView && treeView.style.display !== 'none') {
            const cardRect = firstMatchCard.getBoundingClientRect();
            const viewportRect = canvasViewport.getBoundingClientRect();
            translateX += (viewportRect.left + viewportRect.width / 2) - (cardRect.left + cardRect.width / 2);
            translateY += (viewportRect.top + viewportRect.height / 3) - (cardRect.top + cardRect.height / 2);
            updateTransform();
        }

        // 2. Grid Items Filtering
        gridItems.forEach(item => {
            const itemDept = item.dataset.department?.toLowerCase() || '';
            const itemBranch = item.dataset.branch?.toLowerCase() || '';
            const itemCompany = item.dataset.company?.toLowerCase() || '';
            const itemName = item.dataset.name?.toLowerCase() || '';

            const deptMatch = !deptVal || itemDept === deptVal;
            const branchMatch = !branchVal || itemBranch === branchVal;
            const companyMatch = !companyVal || itemCompany.includes(companyVal);
            const searchMatch = !searchVal || itemName.includes(searchVal) || itemDept.includes(searchVal) || itemCompany.includes(searchVal);

            item.style.display = (deptMatch && branchMatch && companyMatch && searchMatch) ? '' : 'none';
        });

        // Filter Grid Department Containers
        deptBoxes.forEach(box => {
            const boxDept = box.dataset.deptName?.toLowerCase() || '';
            const deptMatch = !deptVal || boxDept === deptVal;
            box.style.display = deptMatch ? '' : 'none';
        });

        // 3. Table Rows Filtering
        rows.forEach(row => {
            const rowDept = row.dataset.department?.toLowerCase() || '';
            const rowBranch = row.dataset.branch?.toLowerCase() || '';
            const rowCompany = row.dataset.company?.toLowerCase() || '';
            const rowName = row.dataset.name?.toLowerCase() || '';

            const deptMatch = !deptVal || rowDept === deptVal;
            const branchMatch = !branchVal || rowBranch === branchVal;
            const companyMatch = !companyVal || rowCompany.includes(companyVal);
            const searchMatch = !searchVal || rowName.includes(searchVal) || rowDept.includes(searchVal) || rowCompany.includes(searchVal);

            row.style.display = (deptMatch && branchMatch && companyMatch && searchMatch) ? '' : 'none';
        });
    }

    // Attach Filter Event Listeners
    deptFilter?.addEventListener('change', applyFilters);
    branchFilter?.addEventListener('change', applyFilters);
    companyFilter?.addEventListener('change', applyFilters);

    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 150);
    });

    // ------------------------------------------------------------------------
    // Collapse / Expand Sub-trees
    // ------------------------------------------------------------------------
    document.querySelectorAll('.org-collapse-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const li = btn.closest('li');
            const subTree = li.querySelector('ul');
            if (subTree) {
                const isHidden = subTree.style.display === 'none';
                subTree.style.display = isHidden ? 'flex' : 'none';
                btn.innerHTML = isHidden
                    ? `<i class="ph ph-minus-circle"></i> <span>${btn.dataset.count}</span>`
                    : `<i class="ph ph-plus-circle"></i> <span>${btn.dataset.count}</span>`;
            }
        });
    });

    // Initial Center on Load
    setTimeout(centerCanvas, 150);
}

/**
 * Open Modal to Reassign Manager
 */
window.openReassignManagerModal = function(empId, empName, currentSupervisorId) {
    const modal = document.getElementById('reassignManagerModal');
    if (!modal) return;

    document.getElementById('reassign_emp_id').value = empId;
    document.getElementById('reassign_emp_name').textContent = empName;

    const select = document.getElementById('reassign_supervisor_select');
    if (select) {
        select.value = currentSupervisorId || '';

        // Disable self-selection to prevent immediate loop
        Array.from(select.options).forEach(opt => {
            if (parseInt(opt.value) === parseInt(empId)) {
                opt.disabled = true;
                opt.textContent = `${opt.dataset.name || opt.text} (Self - Invalid)`;
            } else {
                opt.disabled = false;
                opt.textContent = opt.dataset.name || opt.text;
            }
        });
    }

    modal.style.display = 'flex';
};

window.closeReassignManagerModal = function() {
    const modal = document.getElementById('reassignManagerModal');
    if (modal) modal.style.display = 'none';
};

/**
 * Submit Reassign Manager Form via AJAX
 */
window.submitReassignManager = async function(event) {
    event.preventDefault();
    const form = event.target;
    const empId = document.getElementById('reassign_emp_id').value;
    const supervisorId = document.getElementById('reassign_supervisor_select').value;
    const submitBtn = form.querySelector('button[type="submit"]');

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Saving...';
    }

    try {
        const response = await fetch('/people/org-structure/assign-supervisor', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                employee_id: empId,
                supervisor_id: supervisorId || null,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            closeReassignManagerModal();
            if (window.showToast) {
                window.showToast(data.message, 'success');
            } else {
                alert(data.message);
            }
            setTimeout(() => window.location.reload(), 600);
        } else {
            alert(data.message || 'Error updating manager.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Save Assignment';
            }
        }
    } catch (err) {
        alert('Network or server error updating manager: ' + err.message);
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Save Assignment';
        }
    }
};

/**
 * Open Modal to Set Department Head
 */
window.openSetDeptHeadModal = function(deptId, deptName, currentHeadId) {
    const modal = document.getElementById('setDeptHeadModal');
    if (!modal) return;

    document.getElementById('dept_head_dept_id').value = deptId;
    document.getElementById('dept_head_dept_name').textContent = deptName;

    const select = document.getElementById('dept_head_select');
    if (select) {
        select.value = currentHeadId || '';
    }

    modal.style.display = 'flex';
};

window.closeSetDeptHeadModal = function() {
    const modal = document.getElementById('setDeptHeadModal');
    if (modal) modal.style.display = 'none';
};

/**
 * Submit Department Head Assignment via AJAX
 */
window.submitSetDeptHead = async function(event) {
    event.preventDefault();
    const deptId = document.getElementById('dept_head_dept_id').value;
    const headEmployeeId = document.getElementById('dept_head_select').value;
    const submitBtn = event.target.querySelector('button[type="submit"]');

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Saving...';
    }

    try {
        const response = await fetch(`/people/departments/${deptId}/assign-head`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                head_employee_id: headEmployeeId || null,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            closeSetDeptHeadModal();
            if (window.showToast) {
                window.showToast(data.message, 'success');
            } else {
                alert(data.message);
            }
            setTimeout(() => window.location.reload(), 600);
        } else {
            alert(data.message || 'Error updating department head.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Save Department Head';
            }
        }
    } catch (err) {
        alert('Error: ' + err.message);
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Save Department Head';
        }
    }
};
