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

            <a href="{{ route('config.business-settings') }}" class="user-dropdown-item">
                <i class="ph ph-sliders"></i>
                <div class="item-text">
                    <span class="item-title">Business Settings</span>
                    <span class="item-sub">Company profiles, legal hub, branches</span>
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

<style>
/* Header User Profile Styles */
.header-user-profile {
    position: relative;
    display: inline-block;
    z-index: 100;
}

.user-profile-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 10px 4px 4px;
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.user-profile-btn:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.user-avatar-pill {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.03em;
}

.user-name-col {
    display: flex;
    flex-direction: column;
    text-align: left;
    line-height: 1.15;
}

.user-fullname {
    font-size: 12px;
    font-weight: 700;
    color: #1e293b;
    white-space: nowrap;
    max-width: 110px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role-badge {
    font-size: 9px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
}

.user-chevron {
    font-size: 12px;
    color: #94a3b8;
    transition: transform 0.2s;
}

.header-user-profile.is-open .user-chevron {
    transform: rotate(180deg);
}

/* User Dropdown Panel */
.user-dropdown-panel {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 290px;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 16px;
    box-shadow: 0 12px 36px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(0, 0, 0, 0.04);
    display: none;
    flex-direction: column;
    overflow: hidden;
    animation: userDropdownIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 1000;
}

.header-user-profile.is-open .user-dropdown-panel {
    display: flex;
}

@keyframes userDropdownIn {
    from {
        opacity: 0;
        transform: translateY(-8px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.user-dropdown-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}

.user-profile-avatar-large {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 700;
    flex-shrink: 0;
}

.user-profile-meta {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.profile-meta-name {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-meta-email {
    font-size: 11px;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-role-row {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 4px;
}

.role-pill {
    font-size: 9px;
    font-weight: 700;
    padding: 1px 6px;
    background: #e2e8f0;
    color: #334155;
    border-radius: 4px;
    text-transform: uppercase;
}

.branch-pill {
    font-size: 9px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 3px;
}

.user-dropdown-nav {
    padding: 8px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.user-dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    border-radius: 10px;
    text-decoration: none;
    color: #334155;
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.user-dropdown-item:hover {
    background: #f1f5f9;
}

.user-dropdown-item i {
    font-size: 18px;
    color: #64748b;
}

.user-dropdown-item:hover i {
    color: #4f46e5;
}

.item-text {
    display: flex;
    flex-direction: column;
}

.item-title {
    font-size: 12px;
    font-weight: 600;
    color: #1e293b;
}

.item-sub {
    font-size: 10px;
    color: #94a3b8;
}

.user-dropdown-footer {
    padding: 8px 12px 12px 12px;
    border-top: 1px solid #f1f5f9;
    background: #ffffff;
}

.btn-user-logout {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px;
    border-radius: 8px;
    border: 1px solid #fee2e2;
    background: #fff5f5;
    color: #dc2626;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-user-logout:hover {
    background: #fee2e2;
    border-color: #fca5a5;
}

/* Modal styles */
.modal-backdrop-custom {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
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
