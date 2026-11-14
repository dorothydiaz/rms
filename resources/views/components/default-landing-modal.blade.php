@props(['navCompaniesTree' => null])
@php
    $authUser = auth()->user();
    $tree = $navCompaniesTree ?? \Illuminate\Support\Facades\Cache::remember('global_nav_companies_tree', 3600, function () {
        return \App\Models\Company::active()->with(['branches' => fn($q) => $q->active()->orderBy('branch_name')])->orderBy('company_legal_name')->get();
    });
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
