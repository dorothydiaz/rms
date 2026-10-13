@php
    $authUser = auth()->user();
    $tree = $navCompaniesTree ?? \App\Models\Company::active()->with(['branches' => fn($q) => $q->active()->orderBy('branch_name')])->orderBy('company_legal_name')->get();
@endphp

<!-- Default Landing Location Modal -->
<div class="modal-backdrop-custom" id="defaultLandingModal" style="display: none;" onclick="if(event.target === this) RMSUserProfile.closeDefaultLandingModal()">
    <div class="modal-card-custom" onclick="event.stopPropagation()">
        <div class="modal-card-header">
            <div class="modal-title-row">
                <i class="ph ph-compass modal-icon"></i>
                <div>
                    <h3 class="modal-title">Default Landing Location</h3>
                    <p class="modal-subtitle">Select which Company and Branch will open automatically upon system login.</p>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="RMSUserProfile.closeDefaultLandingModal()">&times;</button>
        </div>
        <form action="{{ route('context.default-landing') }}" method="POST" id="defaultLandingForm">
            @csrf
            <div class="modal-card-body">
                <div class="form-group-custom">
                    <label for="defaultCompanySelect">Default Company</label>
                    <select name="default_company_id" id="defaultCompanySelect" class="form-control-custom" required onchange="RMSUserProfile.updateModalBranches(this.value)">
                        @foreach($tree as $comp)
                            <option value="{{ $comp->company_id }}" {{ ($authUser?->default_company_id == $comp->company_id) ? 'selected' : '' }}>
                                {{ $comp->company_trade_name ?: $comp->company_legal_name }} ({{ $comp->company_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group-custom">
                    <label for="defaultBranchSelect">Default Branch</label>
                    <select name="default_branch_id" id="defaultBranchSelect" class="form-control-custom" required>
                        @php
                            $selectedCompId = $authUser?->default_company_id ?: ($tree->first()?->company_id);
                            $targetComp = $tree->firstWhere('company_id', $selectedCompId) ?: $tree->first();
                        @endphp
                        @if($targetComp)
                            @foreach($targetComp->branches as $br)
                                <option value="{{ $br->branch_id }}" {{ ($authUser?->default_branch_id == $br->branch_id) ? 'selected' : '' }}>
                                    {{ $br->branch_name }} ({{ $br->branch_code }}) - {{ $br->branch_type }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="modal-card-footer">
                <button type="button" class="btn-cancel-custom" onclick="RMSUserProfile.closeDefaultLandingModal()">Cancel</button>
                <button type="submit" class="btn-save-custom">Save Landing Preference</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Default Landing Modal Styles */
.modal-backdrop-custom {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    animation: modalFadeIn 0.2s ease;
}

.modal-card-custom {
    width: 440px;
    max-width: 95vw;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    animation: modalScaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes modalScaleIn {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.modal-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 16px 20px;
    background: #faf5ff;
    border-bottom: 1px solid #f3e8ff;
}

.modal-title-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.modal-icon {
    font-size: 24px;
    color: #7c3aed;
    margin-top: 2px;
}

.modal-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e1b4b;
    margin: 0;
}

.modal-subtitle {
    font-size: 11px;
    color: #64748b;
    margin: 3px 0 0 0;
}

.modal-close-btn {
    background: none;
    border: none;
    font-size: 20px;
    color: #94a3b8;
    cursor: pointer;
    line-height: 1;
}

.modal-card-body {
    padding: 20px;
}

.form-group-custom {
    margin-bottom: 16px;
}

.form-group-custom label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}

.form-control-custom {
    width: 100%;
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    font-size: 13px;
    outline: none;
    background: #ffffff;
    transition: border-color 0.2s;
}

.form-control-custom:focus {
    border-color: #7c3aed;
    box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.15);
}

.modal-card-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.btn-cancel-custom {
    padding: 7px 14px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
}

.btn-save-custom {
    padding: 7px 16px;
    border-radius: 8px;
    border: none;
    background: linear-gradient(135deg, #7c3aed, #9333ea);
    color: #ffffff;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(124, 58, 237, 0.3);
}
</style>

<script>
window.RMSUserProfile = {
    companiesTree: @json($tree),

    openDefaultLandingModal() {
        const modal = document.getElementById('defaultLandingModal');
        if (modal) modal.style.display = 'flex';
        const popup = document.getElementById('userProfilePopup');
        if (popup) popup.classList.remove('show');
        const toggle = document.getElementById('userProfileToggle');
        if (toggle) {
            toggle.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
        }
    },

    closeDefaultLandingModal() {
        const modal = document.getElementById('defaultLandingModal');
        if (modal) modal.style.display = 'none';
    },

    updateModalBranches(companyId) {
        const branchSelect = document.getElementById('defaultBranchSelect');
        if (!branchSelect) return;
        branchSelect.innerHTML = '';

        const comp = this.companiesTree.find(c => c.company_id == companyId);
        if (comp && comp.branches && comp.branches.length > 0) {
            comp.branches.forEach(br => {
                const opt = document.createElement('option');
                opt.value = br.branch_id;
                opt.textContent = `${br.branch_name} (${br.branch_code}) - ${br.branch_type}`;
                branchSelect.appendChild(opt);
            });
        } else {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'No branches under this company';
            branchSelect.appendChild(opt);
        }
    }
};

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (window.RMSUserProfile) {
            RMSUserProfile.closeDefaultLandingModal();
        }
    }
});
</script>
