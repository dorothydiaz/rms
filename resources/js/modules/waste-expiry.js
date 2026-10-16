// =========================================================================
// TAB SWITCHING CONTROLLER
// =========================================================================
function switchTab(tabId) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.wst-tab-panel').forEach(panel => panel.classList.remove('active'));

    const targetPanel = document.getElementById(tabId);
    if (targetPanel) {
        targetPanel.classList.add('active');
    }

    if (tabId === 'tab-records') document.getElementById('btnTabRecords')?.classList.add('active');
    else if (tabId === 'tab-log-waste') document.getElementById('btnTabLogWaste')?.classList.add('active');
    else if (tabId === 'tab-expiry-watchlist') document.getElementById('btnTabExpiryWatchlist')?.classList.add('active');
}

// =========================================================================
// FORM CONTROLS & DYNAMIC PREVIEWS
// =========================================================================
let currentSelectedProduct = null;

function onSelectItem(productId) {
    const sel = document.getElementById('inputInventoryItem');
    const opt = sel.options[sel.selectedIndex];

    if (!productId || !opt) {
        currentSelectedProduct = null;
        document.getElementById('itemPreviewBox').style.display = 'none';
        calculateLossValuation();
        return;
    }

    currentSelectedProduct = {
        id: productId,
        sku: opt.dataset.sku,
        name: opt.dataset.name,
        category: opt.dataset.category,
        uom: opt.dataset.uom,
        stock: parseFloat(opt.dataset.stock) || 0,
        cost: parseFloat(opt.dataset.cost) || 0,
        location: opt.dataset.location || 'Main Storage'
    };

    document.getElementById('prevStock').textContent = `${currentSelectedProduct.stock.toFixed(2)} ${currentSelectedProduct.uom}`;
    document.getElementById('prevCost').textContent = `₱${currentSelectedProduct.cost.toFixed(2)} / ${currentSelectedProduct.uom}`;
    document.getElementById('prevLocation').textContent = currentSelectedProduct.location;
    document.getElementById('itemPreviewBox').style.display = 'flex';

    calculateLossValuation();
}

function calculateLossValuation() {
    const qty = parseFloat(document.getElementById('inputQuantity').value) || 0;
    const cost = currentSelectedProduct ? currentSelectedProduct.cost : 0;
    const total = qty * cost;
    document.getElementById('inputValuation').value = `₱${total.toFixed(2)}`;
}

function onWasteTypeChange() {
    const type = document.getElementById('inputWasteType').value;
    const reasonSel = document.getElementById('inputReasonCode');

    if (type === 'EXPIRED') {
        reasonSel.value = 'Expired on Shelf';
    } else if (type === 'SPOILAGE') {
        reasonSel.value = 'Mold / Spoilage';
    } else if (type === 'STORAGE_FAILURE') {
        reasonSel.value = 'Cold Chain Temperature Breach';
    } else if (type === 'DAMAGED') {
        reasonSel.value = 'Physical Handling Damage';
    } else if (type === 'DEFECTIVE') {
        reasonSel.value = 'Packaging Defect';
    } else if (type === 'PREP_FALLOUT') {
        reasonSel.value = 'Preparation Fallout';
    }
}

// =========================================================================
// SUBMIT LOG WASTE RECORD
// =========================================================================
async function submitWasteRecord(e) {
    e.preventDefault();

    if (!currentSelectedProduct) {
        Swal.fire({
            icon: 'warning',
            title: 'Please Select Item',
            text: 'You must select an item from the Item Masterlist.',
            confirmButtonColor: '#9333ea'
        });
        return;
    }

    const qty = parseFloat(document.getElementById('inputQuantity').value) || 0;
    if (qty <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Quantity',
            text: 'Write-off quantity must be greater than zero.',
            confirmButtonColor: '#9333ea'
        });
        return;
    }

    // Shortage check warning
    let forceOverride = false;
    if (qty > currentSelectedProduct.stock) {
        const confirmResult = await Swal.fire({
            icon: 'warning',
            title: 'Stock Warning',
            html: `Physical stock on hand for <strong>${currentSelectedProduct.name}</strong> is <strong>${currentSelectedProduct.stock.toFixed(2)} ${currentSelectedProduct.uom}</strong>.<br><br>You requested write-off of <strong>${qty.toFixed(2)} ${currentSelectedProduct.uom}</strong>. Do you want to proceed with administrative override?`,
            showCancelButton: true,
            confirmButtonText: 'Yes, Force Write-Off',
            cancelButtonText: 'Cancel & Re-check',
            confirmButtonColor: '#9333ea',
            cancelButtonColor: '#64748b'
        });

        if (!confirmResult.isConfirmed) {
            return;
        }
        forceOverride = true;
    }

    const payload = {
        waste_type: document.getElementById('inputWasteType').value,
        storage_location: currentSelectedProduct.location,
        disposal_method: document.getElementById('inputDisposalMethod').value,
        reported_by: document.getElementById('inputReportedBy').value,
        waste_date: document.getElementById('inputWasteDate').value,
        notes: document.getElementById('inputNotes').value,
        force_override: forceOverride,
        items: [{
            inventory_item_id: currentSelectedProduct.id,
            quantity: qty,
            reason_code: document.getElementById('inputReasonCode').value,
            batch_lot_no: document.getElementById('inputBatchLot').value || null,
            expiry_date: document.getElementById('inputExpiryDate').value || null,
            action_taken: document.getElementById('inputDisposalMethod').value,
            notes: document.getElementById('inputNotes').value || null,
        }]
    };

    try {
        Swal.fire({
            title: 'Logging Waste...',
            text: 'Updating Stock Ledger and deducting on-hand stock...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const res = await fetch(window.WasteExpiryConfig?.routes?.createWasteRecord || '/inventory/api/create-waste-record', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Waste Record Created',
                text: data.message,
                confirmButtonColor: '#9333ea'
            });
            window.location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to record waste entry.',
                confirmButtonColor: '#9333ea'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Server Error',
            text: err.message,
            confirmButtonColor: '#9333ea'
        });
    }
}

// =========================================================================
// QUICK WRITE-OFF FOR EXPIRY WATCHLIST LOT
// =========================================================================
function quickWriteOffLot(lot) {
    switchTab('tab-log-waste');

    // Attempt to pre-select item
    const sel = document.getElementById('inputInventoryItem');
    for (let i = 0; i < sel.options.length; i++) {
        if (sel.options[i].dataset.sku === lot.sku) {
            sel.selectedIndex = i;
            onSelectItem(sel.options[i].value);
            break;
        }
    }

    document.getElementById('inputWasteType').value = 'EXPIRED';
    document.getElementById('inputReasonCode').value = 'Expired on Shelf';
    document.getElementById('inputBatchLot').value = lot.batch_lot_no || '';
    if (lot.expiry_date) {
        document.getElementById('inputExpiryDate').value = lot.expiry_date.split('T')[0];
    }
    if (lot.est_quantity) {
        document.getElementById('inputQuantity').value = parseFloat(lot.est_quantity);
        calculateLossValuation();
    }
}

// =========================================================================
// DETAIL MODAL & AUDIT
// =========================================================================
let activeViewingRecord = null;

function viewWasteDetail(rec) {
    activeViewingRecord = rec;
    document.getElementById('modalWasteNumber').textContent = rec.waste_number;
    document.getElementById('modalWasteType').textContent = rec.waste_type;
    document.getElementById('modalWasteDate').textContent = rec.waste_date;
    document.getElementById('modalReportedBy').textContent = rec.reported_by;
    document.getElementById('modalDisposalMethod').textContent = rec.disposal_method;
    document.getElementById('modalNotes').textContent = rec.notes || 'No specific notes recorded.';

    const tbody = document.getElementById('modalItemsTbody');
    tbody.innerHTML = '';

    if (rec.items && rec.items.length > 0) {
        rec.items.forEach(it => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9;">
                    <strong>${it.item_name}</strong>
                    <div style="font-size: 11px; color: var(--wst-text-muted);">SKU: ${it.sku} &bull; Reason: ${it.reason_code}</div>
                </td>
                <td style="padding: 8px 12px; text-align: center; border-bottom: 1px solid #f1f5f9;">
                    ${parseFloat(it.quantity).toFixed(2)} ${it.uom}
                </td>
                <td style="padding: 8px 12px; text-align: right; border-bottom: 1px solid #f1f5f9;">
                    ₱${parseFloat(it.unit_cost).toFixed(2)}
                </td>
                <td style="padding: 8px 12px; text-align: right; font-weight: 700; color: var(--wst-danger); border-bottom: 1px solid #f1f5f9;">
                    -₱${parseFloat(it.total_cost).toFixed(2)}
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    const cancelBtn = document.getElementById('btnCancelRecord');
    if (rec.status === 'CANCELLED') {
        cancelBtn.style.display = 'none';
    } else {
        cancelBtn.style.display = 'inline-flex';
    }

    document.getElementById('wasteDetailModal').classList.add('open');
}

function closeWasteModal() {
    document.getElementById('wasteDetailModal').classList.remove('open');
    activeViewingRecord = null;
}

async function cancelWasteRecord() {
    if (!activeViewingRecord) return;

    const confirm = await Swal.fire({
        icon: 'warning',
        title: 'Cancel & Reverse Write-Off?',
        text: `Are you sure you want to cancel ${activeViewingRecord.waste_number}? This will restore the written-off quantities back into physical on-hand stock and log a reversing transaction in Stock Ledger.`,
        showCancelButton: true,
        confirmButtonText: 'Yes, Cancel Write-Off',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b'
    });

    if (!confirm.isConfirmed) return;

    try {
        const res = await fetch(window.WasteExpiryConfig?.routes?.updateWasteStatus || '/inventory/api/update-waste-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                waste_record_id: activeViewingRecord.id,
                status: 'CANCELLED',
                notes: 'Cancelled by user audit reversal'
            })
        });

        const data = await res.json();
        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Record Cancelled',
                text: data.message,
                confirmButtonColor: '#9333ea'
            });
            window.location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#9333ea'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err.message,
            confirmButtonColor: '#9333ea'
        });
    }
}

// =========================================================================
// TABLE SEARCH & FILTERS
// =========================================================================
function filterWasteTable() {
    const q = document.getElementById('wstSearchInput').value.toLowerCase().trim();
    const type = document.getElementById('wstTypeFilter').value;
    const status = document.getElementById('wstStatusFilter').value;

    const rows = document.querySelectorAll('#wasteLogsTbody tr[data-search]');
    rows.forEach(r => {
        const matchesQ = !q || r.dataset.search.includes(q);
        const matchesType = (type === 'ALL') || (r.dataset.type === type);
        const matchesStatus = (status === 'ALL') || (r.dataset.status === status);

        if (matchesQ && matchesType && matchesStatus) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

function resetWasteFilters() {
    document.getElementById('wstSearchInput').value = '';
    document.getElementById('wstTypeFilter').value = 'ALL';
    document.getElementById('wstStatusFilter').value = 'ALL';
    filterWasteTable();
}

function filterWatchlistTable() {
    const q = document.getElementById('watchlistSearchInput').value.toLowerCase().trim();
    const risk = document.getElementById('watchlistRiskFilter').value;

    const rows = document.querySelectorAll('#expiryWatchlistTbody tr[data-search]');
    rows.forEach(r => {
        const matchesQ = !q || r.dataset.search.includes(q);
        const matchesRisk = (risk === 'ALL') || (r.dataset.risk === risk);

        if (matchesQ && matchesRisk) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

// ==========================================================================
// WINDOW EXPORTS FOR FULL BACKWARD-COMPATIBILITY & INLINE HANDLERS
// ==========================================================================
Object.assign(window, {
  switchTab,
  onSelectItem,
  calculateLossValuation,
  onWasteTypeChange,
  submitWasteRecord,
  quickWriteOffLot,
  viewWasteDetail,
  closeWasteModal,
  cancelWasteRecord,
  filterWasteTable,
  resetWasteFilters,
  filterWatchlistTable
});
