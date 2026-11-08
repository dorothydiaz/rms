@php
    $authUser = auth()->user();
    $initials = 'U';
    if ($authUser && $authUser->name) {
        $parts = explode(' ', trim($authUser->name));
        $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
    }
@endphp

<div class="header-user-profile" id="headerUserProfile">
    <!-- Trigger Button -->
    <button type="button" class="user-profile-btn" id="headerUserBtn" aria-haspopup="true" aria-expanded="false" title="User account and preferences">
        <div class="user-avatar-pill">
            <span class="avatar-text">{{ $initials }}</span>
        </div>
        <div class="user-name-col">
            <span class="user-fullname">{{ $authUser?->name ?? 'Guest User' }}</span>
            <span class="user-role-badge">{{ $authUser?->role ?? 'User' }}</span>
        </div>
        <i class="ph ph-caret-down user-chevron"></i>
    </button>

    <!-- User Profile Dropdown Menu -->
    <div class="user-dropdown-panel" id="headerUserDropdown">
        <div class="user-dropdown-header">
            <div class="user-profile-avatar-large">
                <span>{{ $initials }}</span>
            </div>
            <div class="user-profile-meta">
                <span class="profile-meta-name">{{ $authUser?->name ?? 'Guest' }}</span>
                <span class="profile-meta-email">{{ $authUser?->email ?? '' }}</span>
                <div class="profile-role-row">
                    <span class="role-pill">{{ $authUser?->role ?? 'Staff' }}</span>
                    @if($authUser?->branch)
                        <span class="branch-pill"><i class="ph ph-map-pin"></i> {{ $authUser->branch->branch_name }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="user-dropdown-nav">
            <a href="{{ route('account-settings') }}" class="user-dropdown-item">
                <i class="ph ph-user-circle"></i>
                <div class="item-text">
                    <span class="item-title">My Profile & Security</span>
                    <span class="item-sub">Personal info, passwords, login logs</span>
                </div>
            </a>

            <button type="button" class="user-dropdown-item" onclick="RMSUserProfile.openDefaultLandingModal()">
                <i class="ph ph-compass"></i>
                <div class="item-text">
                    <span class="item-title">Default Landing Location</span>
                    <span class="item-sub">Choose company & branch for automatic login</span>
                </div>
            </button>

            <a href="{{ route('config.companies-branches.index') }}" class="user-dropdown-item">
                <i class="ph ph-buildings"></i>
                <div class="item-text">
                    <span class="item-title">Companies & Branches</span>
                    <span class="item-sub">Company & agency profiles, legal hub, branches</span>
                </div>
            </a>
        </div>

        <div class="user-dropdown-footer">
            <form action="{{ route('logout') }}" method="POST" class="logout-form">
                @csrf
                <button type="submit" class="btn-user-logout">
                    <i class="ph ph-sign-out"></i>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Default Landing Location Modal -->
    <div class="modal-backdrop-custom" id="defaultLandingModal" style="display: none;">
        <div class="modal-card-custom">
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
                            @foreach($navCompaniesTree as $comp)
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
                                $selectedCompId = $authUser?->default_company_id ?: ($navCompaniesTree->first()?->company_id);
                                $targetComp = $navCompaniesTree->firstWhere('company_id', $selectedCompId) ?: $navCompaniesTree->first();
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
</div>

<script>
window.RMSUserProfile = {
    companiesTree: @json($navCompaniesTree),

    init() {
        const profile = document.getElementById('headerUserProfile');
        const trigger = document.getElementById('headerUserBtn');

        if (!profile || !trigger) return;

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            profile.classList.toggle('is-open');
        });

        document.addEventListener('click', (e) => {
            if (!profile.contains(e.target)) {
                profile.classList.remove('is-open');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                profile.classList.remove('is-open');
                this.closeDefaultLandingModal();
            }
        });
    },

    openDefaultLandingModal() {
        const modal = document.getElementById('defaultLandingModal');
        if (modal) modal.style.display = 'flex';
        const profile = document.getElementById('headerUserProfile');
        if (profile) profile.classList.remove('is-open');
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

document.addEventListener('DOMContentLoaded', () => {
    RMSUserProfile.init();
});
</script>
