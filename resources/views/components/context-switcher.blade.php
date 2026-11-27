@php
    $authUser = auth()->user();
    $userName = $authUser?->name ?? 'Guest User';
    $userRole = $authUser?->role ?? 'Staff';
    $userPhoto = $authUser?->employee?->photo ?? null;

    $nameParts = preg_split('/\s+/', trim((string) $userName)) ?: [];
    $userInitials = strtoupper(
        substr($nameParts[0] ?? 'U', 0, 1) .
        (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : '')
    );

    $checkedBranchIds = array_values(array_unique(array_map('intval', $checkedBranchIds ?? [])));
    $isConsolidatedView = $isConsolidatedView ?? false;
    $currentCompany = $currentCompany ?? null;
    $currentBranch = $currentBranch ?? null;
    $navCompaniesTree = $navCompaniesTree ?? collect();

    $accessibleBranches = collect();
    $companyCount = 0;

    foreach ($navCompaniesTree as $comp) {
        $compBranches = ($comp->branches ?? collect())->filter(function ($br) use ($authUser) {
            if (!$authUser) {
                return true;
            }
            return $authUser->isSuperAdmin() || $authUser->canAccessBranch((int) $br->branch_id);
        });

        if ($compBranches->isNotEmpty()) {
            $companyCount++;
            foreach ($compBranches as $br) {
                $accessibleBranches->push((object) [
                    'branch_id' => (int) $br->branch_id,
                    'company_id' => (int) $comp->company_id,
                    'company_name' => $comp->company_trade_name ?: $comp->company_legal_name,
                    'branch_name' => $br->branch_name,
                    'branch_code' => $br->branch_code,
                    'branch_type' => $br->branch_type,
                    'branch_city' => $br->branch_city,
                    'branch_address_line_1' => $br->branch_address_line_1,
                ]);
            }
        }
    }

    if ($accessibleBranches->isEmpty() && $currentBranch) {
        $accessibleBranches->push((object) [
            'branch_id' => (int) $currentBranch->branch_id,
            'company_id' => (int) ($currentCompany?->company_id ?? $currentBranch->company_id),
            'company_name' => $currentCompany?->company_trade_name ?: $currentCompany?->company_legal_name ?: '',
            'branch_name' => $currentBranch->branch_name,
            'branch_code' => $currentBranch->branch_code,
            'branch_type' => $currentBranch->branch_type,
            'branch_city' => $currentBranch->branch_city,
            'branch_address_line_1' => $currentBranch->branch_address_line_1,
        ]);
        $companyCount = 1;
    }

    $accessibleBranchIds = $accessibleBranches->pluck('branch_id')->all();
    $activeBranchIds = array_values(array_intersect($checkedBranchIds, $accessibleBranchIds));
    if (empty($activeBranchIds) && $currentBranch && in_array((int) $currentBranch->branch_id, $accessibleBranchIds, true)) {
        $activeBranchIds = [(int) $currentBranch->branch_id];
    } elseif (empty($activeBranchIds) && $accessibleBranches->isNotEmpty()) {
        $activeBranchIds = [$accessibleBranches->first()->branch_id];
    }

    $primaryBranchId = $currentBranch ? (int) $currentBranch->branch_id : ($activeBranchIds[0] ?? null);

    $makeBranchMonogram = static function (?string $name, ?string $code): string {
        $cleanCode = strtoupper(preg_replace('/^(BR-|BRANCH-)/i', '', trim((string) $code)));
        $alphaCode = preg_replace('/[^A-Z]/', '', $cleanCode);
        if (strlen($alphaCode) >= 2 && strlen($alphaCode) <= 3) {
            return substr($alphaCode, 0, 2);
        }

        $words = array_values(array_filter(preg_split('/\s+/', preg_replace('/[^a-zA-Z0-9\s]/', ' ', (string) $name)) ?: []));
        if (count($words) >= 2) {
            $first = strtoupper($words[0]);
            $second = strtoupper($words[1]);
            if ($second === 'BRANCH' && $first !== 'MAIN' && strlen($first) >= 2) {
                return substr($first, 0, 2);
            }
            return substr($first, 0, 1) . substr($second, 0, 1);
        }

        if (!empty($words)) {
            return strtoupper(substr($words[0], 0, 2));
        }

        return 'BR';
    };

    $selectedBranches = $accessibleBranches->filter(fn ($b) => in_array($b->branch_id, $activeBranchIds, true))->values();
    $selectedCount = $selectedBranches->count();
    $totalBranches = $accessibleBranches->count();

    if ($isConsolidatedView && $companyCount >= 1) {
        $branchSummaryLabel = 'All Branches';
    } elseif ($selectedCount === 1) {
        $branchSummaryLabel = $selectedBranches->first()->branch_name;
    } elseif ($selectedCount > 1) {
        $primaryObj = $selectedBranches->firstWhere('branch_id', $primaryBranchId) ?? $selectedBranches->first();
        $branchSummaryLabel = $primaryObj->branch_name . ' +' . ($selectedCount - 1);
    } else {
        $branchSummaryLabel = 'Select Branch';
    }

    // Order stacked trigger chips with active branches first (up to 3 visible chips + optional +N)
    $orderedForStack = $accessibleBranches->sortByDesc(fn ($b) => in_array($b->branch_id, $activeBranchIds, true) ? 1 : 0)->values();
    $stackChips = $orderedForStack->take(3);
    $stackOverflowCount = max(0, $orderedForStack->count() - 3);

    $unreadCount = (int) ($headerUnreadNotificationsCount ?? 0);
    $notifications = $headerNotifications ?? collect();
    $notifExpandedByDefault = $unreadCount > 0;
@endphp

<div class="entity-context-switcher" id="entityContextSwitcher">
    <!-- Topbar Pill Trigger -->
    <button
        type="button"
        class="ecs-trigger-pill"
        id="entitySwitcherBtn"
        aria-haspopup="true"
        aria-expanded="false"
        title="Switch active branches, view notifications & account settings"
    >
        <div class="ecs-avatar-wrap">
            <div class="ecs-avatar">
                @if($userPhoto)
                    <img src="{{ asset('storage/' . $userPhoto) }}" alt="{{ $userName }}" class="ecs-avatar-img">
                @else
                    <i class="ph-fill ph-user ecs-avatar-silhouette" aria-hidden="true"></i>
                @endif
            </div>
            <span class="ecs-online-dot" aria-hidden="true"></span>
            <span
                class="ecs-notif-pill"
                id="ecsAvatarNotifPill"
                style="{{ $unreadCount > 0 ? '' : 'display: none;' }}"
                aria-label="{{ $unreadCount }} unread notifications"
            >{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        </div>

        <div class="ecs-user-meta">
            <span class="ecs-user-name">{{ $userName }}</span>
            <span class="ecs-user-sub" id="ecsTriggerBranchSummary">{{ $userRole }} &middot; {{ $branchSummaryLabel }}</span>
        </div>

        @if($stackChips->isNotEmpty())
            <span class="ecs-trigger-divider" aria-hidden="true"></span>

            <div class="ecs-branch-stack" id="ecsTriggerBranchStack" aria-hidden="true">
                @foreach($stackChips as $idx => $chipBranch)
                    @php
                        $isChipActive = in_array($chipBranch->branch_id, $activeBranchIds, true);
                        $mono = $makeBranchMonogram($chipBranch->branch_name, $chipBranch->branch_code);
                    @endphp
                    <span
                        class="ecs-stack-chip {{ $isChipActive ? 'is-active' : '' }}"
                        style="z-index: {{ 10 - $idx }};"
                        title="{{ $chipBranch->branch_name }}"
                    >{{ $mono }}</span>
                @endforeach
                @if($stackOverflowCount > 0)
                    <span class="ecs-stack-chip ecs-stack-overflow" style="z-index: 6;">+{{ $stackOverflowCount }}</span>
                @endif
            </div>
        @endif

        <span class="ecs-chevron-circle" aria-hidden="true">
            <i class="ph-bold ph-caret-down entity-chevron"></i>
        </span>
    </button>

    <!-- Context & Settings Popover -->
    <div class="entity-switcher-dropdown ecs-popover" id="entitySwitcherDropdown" role="dialog" aria-label="Branch selector, notifications, and account settings">
        <!-- Profile Identity Header -->
        <div class="ecs-pop-header">
            <div class="ecs-avatar ecs-avatar-lg">
                @if($userPhoto)
                    <img src="{{ asset('storage/' . $userPhoto) }}" alt="{{ $userName }}" class="ecs-avatar-img">
                @else
                    <i class="ph-fill ph-user ecs-avatar-silhouette" aria-hidden="true"></i>
                @endif
            </div>
            <div class="ecs-pop-identity">
                <div class="ecs-pop-name">{{ $userName }}</div>
                <div class="ecs-pop-status">
                    <span class="ecs-status-dot" aria-hidden="true"></span>
                    <span>{{ $userRole }} &middot; Online</span>
                </div>
            </div>
        </div>

        <!-- Multi-Branch Active Selector -->
        <div class="ecs-section">
            <div class="ecs-section-head">
                <span class="ecs-section-label">ACTIVE BRANCHES</span>
                <div class="ecs-section-meta">
                    <span class="ecs-branch-count" id="ecsBranchCountLabel">{{ $selectedCount }} of {{ $totalBranches }} active</span>
                    @if($totalBranches > 1)
                        <button
                            type="button"
                            class="ecs-text-action"
                            id="ecsToggleAllBtn"
                            onclick="RMSContextSwitcher.toggleSelectAll()"
                        >{{ $selectedCount === $totalBranches ? 'Only primary' : 'Select all' }}</button>
                    @endif
                </div>
            </div>

            @if($totalBranches > 5)
                <div class="ecs-search-wrap">
                    <i class="ph ph-magnifying-glass"></i>
                    <input
                        type="text"
                        id="entitySearchInput"
                        placeholder="Search branch or city..."
                        autocomplete="off"
                    >
                    <button type="button" class="ecs-search-clear" id="entitySearchClear" style="display: none;" aria-label="Clear search">&times;</button>
                </div>
            @endif

            <div class="ecs-branch-list" id="ecsBranchList">
                @forelse($accessibleBranches as $br)
                    @php
                        $isSelected = in_array($br->branch_id, $activeBranchIds, true);
                        $isPrimary = $primaryBranchId === $br->branch_id && $selectedCount > 1;
                        $mono = $makeBranchMonogram($br->branch_name, $br->branch_code);
                        $subLoc = $br->branch_city ?: ($br->branch_address_line_1 ?: ($br->branch_type ?: 'Branch'));
                        if ($companyCount > 1 && !empty($br->company_name)) {
                            $subLoc = $br->company_name . ' · ' . $subLoc;
                        }
                    @endphp
                    <button
                        type="button"
                        class="ecs-branch-item {{ $isSelected ? 'is-selected' : '' }}"
                        data-branch-id="{{ $br->branch_id }}"
                        data-company-id="{{ $br->company_id }}"
                        data-branch-name="{{ $br->branch_name }}"
                        data-search-text="{{ strtolower($br->branch_name . ' ' . $br->branch_code . ' ' . $subLoc) }}"
                        aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                        onclick="RMSContextSwitcher.toggleBranch(this)"
                    >
                        <span class="ecs-branch-mono">{{ $mono }}</span>
                        <span class="ecs-branch-info">
                            <span class="ecs-branch-title-row">
                                <span class="ecs-branch-name">{{ $br->branch_name }}</span>
                                @if($isPrimary)
                                    <span class="ecs-primary-tag">Primary</span>
                                @endif
                            </span>
                            <span class="ecs-branch-loc">{{ $subLoc }}</span>
                        </span>
                        <span class="ecs-branch-check" aria-hidden="true">
                            <i class="ph-bold ph-check"></i>
                        </span>
                    </button>
                @empty
                    <div class="ecs-empty-branches">
                        <i class="ph ph-warning-circle"></i>
                        <span>No active branches assigned.</span>
                    </div>
                @endforelse
            </div>

            <!-- Inline Hint / Apply Changes Bar -->
            <div class="ecs-branch-hint" id="ecsBranchHint" style="display: none;"></div>
            <div class="ecs-apply-bar" id="ecsApplyBar" style="display: none;">
                <button type="button" class="ecs-btn-reset" id="ecsResetBranchesBtn" onclick="RMSContextSwitcher.resetBranchSelection()">Reset</button>
                <button type="button" class="ecs-btn-apply" id="ecsApplyBranchesBtn" onclick="RMSContextSwitcher.applyBranchSelection()">
                    Apply (<span id="ecsApplyCount">{{ $selectedCount }}</span> active)
                </button>
            </div>
        </div>

        <!-- Notifications Section -->
        <div class="ecs-section ecs-notif-section">
            <div class="ecs-notif-head">
                <button
                    type="button"
                    class="ecs-notif-toggle"
                    id="ecsNotifToggleBtn"
                    aria-expanded="{{ $notifExpandedByDefault ? 'true' : 'false' }}"
                    aria-controls="ecsNotifPanel"
                    onclick="RMSContextSwitcher.toggleNotifications()"
                >
                    <span class="ecs-notif-title-group">
                        <i class="ph ph-bell"></i>
                        <span>Notifications</span>
                        <span
                            class="ecs-notif-section-badge"
                            id="ecsNotifSectionBadge"
                            style="{{ $unreadCount > 0 ? '' : 'display: none;' }}"
                        >{{ $unreadCount }}</span>
                    </span>
                    <i class="ph-bold ph-caret-down ecs-notif-caret"></i>
                </button>
                <button
                    type="button"
                    class="ecs-text-action"
                    id="ecsMarkAllReadBtn"
                    style="{{ $unreadCount > 0 ? '' : 'display: none;' }}"
                    onclick="RMSContextSwitcher.markAllNotificationsRead()"
                >Mark all read</button>
            </div>

            <div class="ecs-notif-panel {{ $notifExpandedByDefault ? 'is-expanded' : '' }}" id="ecsNotifPanel">
                @if($notifications->isNotEmpty())
                    <div class="ecs-notif-list" id="ecsNotifList">
                        @foreach($notifications as $notif)
                            <button
                                type="button"
                                class="ecs-notif-item {{ $notif->is_read ? '' : 'is-unread' }}"
                                data-notif-id="{{ $notif->id }}"
                                onclick="RMSContextSwitcher.markNotificationRead({{ $notif->id }}, {{ json_encode($notif->link) }}, this)"
                            >
                                <span class="ecs-notif-dot" aria-hidden="true"></span>
                                <span class="ecs-notif-content">
                                    <span class="ecs-notif-title">{{ $notif->title }}</span>
                                    <span class="ecs-notif-msg">{{ \Illuminate\Support\Str::limit($notif->message, 75) }}</span>
                                </span>
                                <span class="ecs-notif-time">{{ $notif->created_at?->diffForHumans(null, true) }}</span>
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="ecs-notif-empty">
                        <i class="ph ph-check-circle"></i>
                        <span>You're all caught up</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Footer Settings Links -->
        <div class="ecs-pop-footer">
            <a href="{{ route('account-settings') }}" class="ecs-footer-link">
                <i class="ph ph-user"></i>
                <span>Profile settings</span>
            </a>
            <a href="{{ route('config.companies-branches.index') }}" class="ecs-footer-link">
                <i class="ph ph-house-line"></i>
                <span>Manage branches</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="ecs-logout-form">
                @csrf
                <button type="submit" class="ecs-footer-link is-danger">
                    <i class="ph ph-sign-out"></i>
                    <span>Sign out</span>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
window.RMSContextSwitcher = {
    initialBranchIds: @json($activeBranchIds),
    selectedBranchIds: new Set(@json($activeBranchIds)),
    primaryBranchId: @json($primaryBranchId),
    totalBranches: {{ $totalBranches }},
    unreadCount: {{ $unreadCount }},
    isSubmitting: false,

    init() {
        const switcher = document.getElementById('entityContextSwitcher');
        const trigger = document.getElementById('entitySwitcherBtn');
        const dropdown = document.getElementById('entitySwitcherDropdown');
        const searchInput = document.getElementById('entitySearchInput');
        const clearBtn = document.getElementById('entitySearchClear');

        if (!switcher || !trigger || !dropdown) return;

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const willOpen = !switcher.classList.contains('is-open');
            switcher.classList.toggle('is-open', willOpen);
            trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            if (willOpen && searchInput) {
                setTimeout(() => searchInput.focus(), 40);
            }
        });

        document.addEventListener('click', (e) => {
            if (!switcher.contains(e.target)) {
                switcher.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && switcher.classList.contains('is-open')) {
                switcher.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
                trigger.focus();
            }
        });

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase().trim();
                if (clearBtn) clearBtn.style.display = query ? 'inline-flex' : 'none';
                this.filterBranches(query);
            });

            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    searchInput.value = '';
                    clearBtn.style.display = 'none';
                    this.filterBranches('');
                    searchInput.focus();
                });
            }
        }
    },

    filterBranches(query) {
        document.querySelectorAll('#ecsBranchList .ecs-branch-item').forEach((item) => {
            const text = item.getAttribute('data-search-text') || '';
            item.style.display = !query || text.includes(query) ? 'flex' : 'none';
        });
    },

    toggleBranch(el) {
        if (this.isSubmitting) return;
        const branchId = parseInt(el.getAttribute('data-branch-id'), 10);
        if (!branchId) return;

        if (this.selectedBranchIds.has(branchId)) {
            if (this.selectedBranchIds.size <= 1) {
                this.showBranchHint('At least 1 branch must remain active.');
                el.classList.remove('is-shaking');
                void el.offsetWidth;
                el.classList.add('is-shaking');
                return;
            }
            this.selectedBranchIds.delete(branchId);
        } else {
            this.selectedBranchIds.add(branchId);
        }

        this.clearBranchHint();
        this.syncBranchSelectionUI();
    },

    toggleSelectAll() {
        if (this.isSubmitting) return;
        const allItems = Array.from(document.querySelectorAll('#ecsBranchList .ecs-branch-item'));
        if (allItems.length <= 1) return;

        if (this.selectedBranchIds.size === allItems.length) {
            const keepId = this.primaryBranchId && this.selectedBranchIds.has(this.primaryBranchId)
                ? this.primaryBranchId
                : parseInt(allItems[0].getAttribute('data-branch-id'), 10);
            this.selectedBranchIds = new Set([keepId]);
        } else {
            this.selectedBranchIds = new Set(allItems.map((el) => parseInt(el.getAttribute('data-branch-id'), 10)));
        }

        this.clearBranchHint();
        this.syncBranchSelectionUI();
    },

    resetBranchSelection() {
        if (this.isSubmitting) return;
        this.selectedBranchIds = new Set(this.initialBranchIds);
        this.clearBranchHint();
        this.syncBranchSelectionUI();
    },

    hasSelectionChanged() {
        if (this.selectedBranchIds.size !== this.initialBranchIds.length) {
            return true;
        }
        return this.initialBranchIds.some((id) => !this.selectedBranchIds.has(id));
    },

    syncBranchSelectionUI() {
        document.querySelectorAll('#ecsBranchList .ecs-branch-item').forEach((item) => {
            const id = parseInt(item.getAttribute('data-branch-id'), 10);
            const active = this.selectedBranchIds.has(id);
            item.classList.toggle('is-selected', active);
            item.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        const count = this.selectedBranchIds.size;
        const countLabel = document.getElementById('ecsBranchCountLabel');
        if (countLabel) {
            countLabel.textContent = `${count} of ${this.totalBranches} active`;
        }

        const toggleAllBtn = document.getElementById('ecsToggleAllBtn');
        if (toggleAllBtn) {
            toggleAllBtn.textContent = count === this.totalBranches ? 'Only primary' : 'Select all';
        }

        const applyCount = document.getElementById('ecsApplyCount');
        if (applyCount) {
            applyCount.textContent = String(count);
        }

        const applyBar = document.getElementById('ecsApplyBar');
        if (applyBar) {
            applyBar.style.display = this.hasSelectionChanged() ? 'flex' : 'none';
        }
    },

    showBranchHint(msg) {
        const hint = document.getElementById('ecsBranchHint');
        if (!hint) return;
        hint.textContent = msg;
        hint.style.display = 'block';
    },

    clearBranchHint() {
        const hint = document.getElementById('ecsBranchHint');
        if (!hint) return;
        hint.textContent = '';
        hint.style.display = 'none';
    },

    async applyBranchSelection() {
        if (this.isSubmitting || this.selectedBranchIds.size === 0) return;

        const applyBtn = document.getElementById('ecsApplyBranchesBtn');
        const resetBtn = document.getElementById('ecsResetBranchesBtn');
        this.isSubmitting = true;
        if (applyBtn) {
            applyBtn.disabled = true;
            applyBtn.textContent = 'Applying...';
        }
        if (resetBtn) resetBtn.disabled = true;

        const branchIds = Array.from(this.selectedBranchIds);
        const companySet = new Set();
        let targetActiveCompanyId = null;
        let targetActiveBranchId = this.primaryBranchId && this.selectedBranchIds.has(this.primaryBranchId)
            ? this.primaryBranchId
            : branchIds[0];

        document.querySelectorAll('#ecsBranchList .ecs-branch-item').forEach((item) => {
            const id = parseInt(item.getAttribute('data-branch-id'), 10);
            const compId = parseInt(item.getAttribute('data-company-id'), 10);
            if (this.selectedBranchIds.has(id) && compId) {
                companySet.add(compId);
                if (id === targetActiveBranchId) {
                    targetActiveCompanyId = compId;
                }
            }
        });

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch("{{ route('context.scope') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    company_ids: Array.from(companySet),
                    branch_ids: branchIds,
                    active_company_id: targetActiveCompanyId,
                    active_branch_id: targetActiveBranchId,
                }),
            });

            if (res.ok) {
                window.location.reload();
                return;
            }
            this.showBranchHint('Could not update active branches. Please try again.');
        } catch (err) {
            console.error('Failed to update active branches:', err);
            this.showBranchHint('Network error while updating branches.');
        } finally {
            this.isSubmitting = false;
            if (applyBtn) {
                applyBtn.disabled = false;
                applyBtn.innerHTML = `Apply (<span id="ecsApplyCount">${this.selectedBranchIds.size}</span> active)`;
            }
            if (resetBtn) resetBtn.disabled = false;
        }
    },

    toggleNotifications() {
        const toggleBtn = document.getElementById('ecsNotifToggleBtn');
        const panel = document.getElementById('ecsNotifPanel');
        if (!toggleBtn || !panel) return;
        const willExpand = !panel.classList.contains('is-expanded');
        panel.classList.toggle('is-expanded', willExpand);
        toggleBtn.setAttribute('aria-expanded', willExpand ? 'true' : 'false');
    },

    syncUnreadBadges(count) {
        this.unreadCount = Math.max(0, parseInt(count, 10) || 0);
        const avatarPill = document.getElementById('ecsAvatarNotifPill');
        const sectionBadge = document.getElementById('ecsNotifSectionBadge');
        const markAllBtn = document.getElementById('ecsMarkAllReadBtn');

        if (avatarPill) {
            if (this.unreadCount > 0) {
                avatarPill.textContent = this.unreadCount > 99 ? '99+' : String(this.unreadCount);
                avatarPill.style.display = 'inline-flex';
            } else {
                avatarPill.style.display = 'none';
            }
        }

        if (sectionBadge) {
            if (this.unreadCount > 0) {
                sectionBadge.textContent = String(this.unreadCount);
                sectionBadge.style.display = 'inline-flex';
            } else {
                sectionBadge.style.display = 'none';
            }
        }

        if (markAllBtn) {
            markAllBtn.style.display = this.unreadCount > 0 ? 'inline-flex' : 'none';
        }
    },

    async markNotificationRead(id, link = null, el = null) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`{{ url('/notifications') }}/${id}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (res.ok) {
                const data = await res.json();
                if (el) el.classList.remove('is-unread');
                this.syncUnreadBadges(data.unread_count);
                if (link) {
                    window.location.href = link;
                }
            }
        } catch (err) {
            console.error('Failed to mark notification read:', err);
        }
    },

    async markAllNotificationsRead() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch("{{ route('notifications.read-all') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (res.ok) {
                document.querySelectorAll('#ecsNotifList .ecs-notif-item.is-unread').forEach((item) => {
                    item.classList.remove('is-unread');
                });
                this.syncUnreadBadges(0);
            }
        } catch (err) {
            console.error('Failed to mark all notifications read:', err);
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    RMSContextSwitcher.init();
});
</script>
