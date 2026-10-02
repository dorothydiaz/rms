document.addEventListener('DOMContentLoaded', () => {
    // Mobile sidebar toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (!sidebar) return;

    function toggleSidebar() {
        sidebar.classList.toggle('open');
        if (overlay) overlay.classList.toggle('active');
    }

    if (mobileMenuBtn) {
        mobileMenuBtn.onclick = toggleSidebar;
    }
    if (overlay) {
        overlay.onclick = toggleSidebar;
    }

    // Synchronize Rail Active Indicators (Clean up active items when sidebar is collapsed on non-module pages like Account Settings)
    function syncRailActiveState() {
        if (!sidebar) return;
        const isCollapsed = sidebar.classList.contains('collapsed');
        const isSettingsPage = document.body.classList.contains('page-account-settings') || 
                               window.location.pathname.includes('/account-settings');
        const isHomePage = window.location.pathname.endsWith('index.php') || 
                           window.location.pathname.endsWith('/rms/') || 
                           window.location.pathname.endsWith('/rms') ||
                           window.location.pathname === '/' ||
                           (document.querySelector('.brand-logo') && !document.querySelector('.breadcrumb-list'));

        if (isCollapsed) {
            if (isSettingsPage) {
                // On Account Settings, when collapsed, no rail item or submenu should remain active
                document.querySelectorAll('.rail-item').forEach(ri => ri.classList.remove('active'));
                document.querySelectorAll('.submenu').forEach(menu => menu.classList.remove('active'));
            } else if (isHomePage) {
                // On Home page, when collapsed, only Home icon is active
                document.querySelectorAll('.rail-item[data-target]').forEach(ri => ri.classList.remove('active'));
                document.querySelectorAll('.submenu').forEach(menu => menu.classList.remove('active'));
                const homeRail = document.querySelector('.rail-item[data-title="Home"]');
                if (homeRail) homeRail.classList.add('active');
            }
        }
    }

    // Dynamic Sidebar Toggle Icon & Tooltip Synchronizer
    function updateSidebarToggleIcon() {
        const headerToggle = document.querySelector('.header-sidebar-toggle');
        if (!headerToggle) return;
        
        const isCollapsed = sidebar.classList.contains('collapsed');
        const icon = headerToggle.querySelector('i');
        
        if (isCollapsed) {
            // Closed/collapsed state -> show menu icon to expand
            if (icon && !icon.classList.contains('ph-list')) {
                icon.className = 'ph ph-list';
            }
            headerToggle.setAttribute('data-tooltip', 'Expand Sidebar');
            headerToggle.setAttribute('aria-expanded', 'false');
            headerToggle.classList.add('collapsed-state');
            syncRailActiveState();
        } else {
            // Open/expanded state -> show left caret to collapse
            if (icon && !icon.classList.contains('ph-caret-left')) {
                icon.className = 'ph ph-caret-left';
            }
            headerToggle.setAttribute('data-tooltip', 'Collapse Sidebar');
            headerToggle.setAttribute('aria-expanded', 'true');
            headerToggle.classList.remove('collapsed-state');
        }
    }

    // Desktop & Header Sidebar Collapse Toggle
    const toggleBtns = document.querySelectorAll('.sidebar-toggle-btn');
    toggleBtns.forEach(btn => {
        btn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            const isNowCollapsed = sidebar.classList.toggle('collapsed');
            
            // If expanding and there is no active submenu, default to HR Operations
            if (!isNowCollapsed && !document.querySelector('.submenu.active')) {
                const firstSubmenu = document.getElementById('submenu-hr');
                if (firstSubmenu) firstSubmenu.classList.add('active');
                const firstRail = document.querySelector('.rail-item[data-target="submenu-hr"]');
                if (firstRail) firstRail.classList.add('active');
            } else if (isNowCollapsed) {
                syncRailActiveState();
            }

            try {
                localStorage.setItem('rms_sidebar_collapsed', isNowCollapsed ? 'true' : 'false');
            } catch (err) {}
            updateSidebarToggleIcon();
        };
    });

    // Observe sidebar class mutations for reliable state sync across any trigger
    const sidebarObserver = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                updateSidebarToggleIcon();
                break;
            }
        }
    });
    sidebarObserver.observe(sidebar, { attributes: true, attributeFilter: ['class'] });

    // Initial sync on DOM ready
    updateSidebarToggleIcon();

    // Submenu switching
    const railItems = document.querySelectorAll('.rail-item[data-target]');
    const submenus = document.querySelectorAll('.submenu');

    railItems.forEach(item => {
        item.onclick = (e) => {
            e.preventDefault();
            
            // Uncollapse if collapsed
            if (sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
            }
            try {
                localStorage.setItem('rms_sidebar_collapsed', 'false');
            } catch (err) {}

            // Update active state on rail items
            document.querySelectorAll('.rail-item').forEach(ri => ri.classList.remove('active'));
            item.classList.add('active');

            // Switch submenus
            const targetId = item.getAttribute('data-target');
            if (targetId) {
                submenus.forEach(menu => menu.classList.remove('active'));
                const targetMenu = document.getElementById(targetId);
                if (targetMenu) {
                    targetMenu.classList.add('active');
                }
            }
        };
    });

    // Dropdown Toggles (Accordion expansion with persistence across page/tab switching)
    const dropdownGroups = document.querySelectorAll('.nav-item-group');
    
    function getExpandedAccordionIds() {
        try {
            const raw = localStorage.getItem('rms_expanded_accordions');
            const arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function saveExpandedAccordionIds(ids) {
        try {
            localStorage.setItem('rms_expanded_accordions', JSON.stringify(ids));
        } catch (e) {}
    }

    dropdownGroups.forEach(group => {
        const subNav = group.nextElementSibling;
        if (subNav && subNav.classList.contains('sub-nav')) {
            group.onclick = (e) => {
                e.preventDefault();
                const isNowExpanded = subNav.classList.toggle('expanded');
                
                const btnIcon = group.querySelector('.add-btn i');
                if (btnIcon) {
                    if (isNowExpanded) {
                        btnIcon.classList.remove('ph-plus', 'ph-caret-down');
                        btnIcon.classList.add('ph-minus', 'ph-caret-up');
                    } else {
                        btnIcon.classList.remove('ph-minus', 'ph-caret-up');
                        btnIcon.classList.add('ph-plus', 'ph-caret-down');
                    }
                }

                // Persist state to localStorage so non-active groups stay open across tab/page switches
                const groupId = group.getAttribute('data-group-id');
                if (groupId) {
                    let currentIds = getExpandedAccordionIds();
                    if (isNowExpanded) {
                        if (!currentIds.includes(groupId)) {
                            currentIds.push(groupId);
                        }
                    } else {
                        currentIds = currentIds.filter(id => id !== groupId);
                    }
                    saveExpandedAccordionIds(currentIds);
                }
            };
        }
    });

    // Breadcrumb Dropdown Navigation Switcher (Hover & Click Support)
    const dropdownContainers = document.querySelectorAll('.breadcrumb-dropdown-container');
    const dropdownTriggers = document.querySelectorAll('.breadcrumb-btn, .breadcrumb-dropdown-btn, .breadcrumb-dropdown-toggle');
    
    dropdownContainers.forEach(container => {
        let hoverTimer = null;

        // Hover support with grace period so moving mouse into menu is seamless
        container.addEventListener('mouseenter', () => {
            if (hoverTimer) clearTimeout(hoverTimer);
            // Close other open dropdowns
            dropdownContainers.forEach(other => {
                if (other !== container && other.classList.contains('open')) {
                    other.classList.remove('open');
                    const otherBtn = other.querySelector('.breadcrumb-btn, .breadcrumb-dropdown-btn, .breadcrumb-dropdown-toggle');
                    if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
                }
            });
            container.classList.add('open');
            const btn = container.querySelector('.breadcrumb-btn, .breadcrumb-dropdown-btn, .breadcrumb-dropdown-toggle');
            if (btn) btn.setAttribute('aria-expanded', 'true');
        });

        container.addEventListener('mouseleave', () => {
            hoverTimer = setTimeout(() => {
                container.classList.remove('open');
                const btn = container.querySelector('.breadcrumb-btn, .breadcrumb-dropdown-btn, .breadcrumb-dropdown-toggle');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            }, 250); // 250ms grace buffer allows smooth diagonal cursor movement
        });
    });

    dropdownTriggers.forEach(triggerBtn => {
        triggerBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const container = triggerBtn.closest('.breadcrumb-dropdown-container');
            if (!container) return;
            
            // If already open (e.g. from hover), keep it locked open
            const wasOpen = container.classList.contains('open');
            
            // Close any other open dropdowns first
            dropdownContainers.forEach(c => {
                if (c !== container) {
                    c.classList.remove('open');
                    const btn = c.querySelector('.breadcrumb-btn, .breadcrumb-dropdown-btn, .breadcrumb-dropdown-toggle');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                }
            });
            
            if (wasOpen) {
                // If it was already opened, a click locks it open (or user can click outside to close)
                container.classList.add('open');
                triggerBtn.setAttribute('aria-expanded', 'true');
            } else {
                container.classList.add('open');
                triggerBtn.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // Close breadcrumb dropdowns on click outside or Escape
    function closeAllBreadcrumbDropdowns() {
        document.querySelectorAll('.breadcrumb-dropdown-container.open').forEach(c => {
            c.classList.remove('open');
            const btn = c.querySelector('.breadcrumb-btn, .breadcrumb-dropdown-btn, .breadcrumb-dropdown-toggle');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.breadcrumb-dropdown-container')) {
            closeAllBreadcrumbDropdowns();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllBreadcrumbDropdowns();
        }
    });

    // Ensure clicking breadcrumbs never opens or interacts with sidebar
    const breadcrumbNav = document.querySelector('.breadcrumb-nav');
    if (breadcrumbNav) {
        breadcrumbNav.addEventListener('click', (e) => {
            // Do not stop propagation if an anchor link is being clicked
            if (!e.target.closest('a')) {
                e.stopPropagation();
            }
            if (sidebar && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                if (overlay) overlay.classList.remove('active');
            }
        });
    }

    // Deterministic Navigation Handlers:
    // 1) Breadcrumbs navigation -> sidebar must remain closed (collapsed)
    // 2) Sidebar navigation -> sidebar must remain opened (uncollapsed)
    document.addEventListener('click', (e) => {
        // Breadcrumb navigation link clicked -> sidebar remains closed
        const breadcrumbLink = e.target.closest('.breadcrumb-nav a, .breadcrumb-dropdown-menu a');
        if (breadcrumbLink) {
            try {
                localStorage.setItem('rms_sidebar_collapsed', 'true');
            } catch (err) {}
            return;
        }

        // Sidebar navigation link clicked
        const sidebarLink = e.target.closest('.sidebar a[href]');
        if (sidebarLink) {
            const href = sidebarLink.getAttribute('href');
            if (href && href !== '#' && !href.startsWith('javascript:')) {
                // If navigating to Home / index.php or Account Settings, collapse sidebar panel
                if (href.includes('account-settings') || href.endsWith('index.php') || href.endsWith('/rms/') || href.endsWith('/rms') || href === '/') {
                    try {
                        localStorage.setItem('rms_sidebar_collapsed', 'true');
                    } catch (err) {}
                    if (sidebar) sidebar.classList.add('collapsed');
                } else {
                    try {
                        localStorage.setItem('rms_sidebar_collapsed', 'false');
                    } catch (err) {}
                }
            }
        }
    }, true);

    // Sync state on back/forward cache navigation
    window.addEventListener('pageshow', () => {
        try {
            const isHomePage = window.location.pathname.endsWith('index.php') || 
                               window.location.pathname.endsWith('/rms/') || 
                               window.location.pathname.endsWith('/rms') ||
                               window.location.pathname === '/';
            const isSettingsPage = window.location.pathname.includes('/account-settings');
            if (isHomePage || isSettingsPage) {
                if (sidebar) sidebar.classList.add('collapsed');
                try { localStorage.setItem('rms_sidebar_collapsed', 'true'); } catch (err) {}
                updateSidebarToggleIcon();
                return;
            }

            const state = localStorage.getItem('rms_sidebar_collapsed');
            if (sidebar && state !== null) {
                if (state === 'true') {
                    sidebar.classList.add('collapsed');
                } else if (state === 'false') {
                    sidebar.classList.remove('collapsed');
                }
            }
            updateSidebarToggleIcon();
        } catch (err) {}
    });

    // User Profile Pop-up Menu Toggle
    const userProfileToggle = document.getElementById('userProfileToggle');
    const userProfilePopup = document.getElementById('userProfilePopup');

    if (userProfileToggle && userProfilePopup) {
        userProfileToggle.addEventListener('click', (e) => {
            // Prevent event if clicked on logout button form
            if (e.target.closest('#sidebarLogoutForm')) {
                return;
            }
            e.stopPropagation();
            const isOpen = userProfilePopup.classList.toggle('show');
            userProfileToggle.classList.toggle('open', isOpen);
            userProfileToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!userProfilePopup.contains(e.target) && !userProfileToggle.contains(e.target)) {
                userProfilePopup.classList.remove('show');
                userProfileToggle.classList.remove('open');
                userProfileToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && userProfilePopup.classList.contains('show')) {
                userProfilePopup.classList.remove('show');
                userProfileToggle.classList.remove('open');
                userProfileToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Sync current header date badge with user local date
    const headerDateSpan = document.getElementById('currentHeaderDate');
    if (headerDateSpan) {
        try {
            const today = new Date();
            const dateStr = today.toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: '2-digit',
                year: 'numeric'
            });
            headerDateSpan.textContent = dateStr;
        } catch (e) {}
    }

    // ============================================================
    // UNIVERSAL CUSTOM GLASSMORPHIC CALENDAR (85% Transparency)
    // ============================================================
    function initCustomDatePickers() {
        const dateInputs = document.querySelectorAll('input[type="date"]:not(.no-custom-datepicker)');
        
        let calendarPopup = document.getElementById('hrGlobalCalendarPopup');
        if (!calendarPopup) {
            calendarPopup = document.createElement('div');
            calendarPopup.id = 'hrGlobalCalendarPopup';
            calendarPopup.className = 'hr-custom-calendar-popup';
            document.body.appendChild(calendarPopup);
        }

        let activeInput = null;
        let viewDate = new Date();
        let selectedDate = null;

        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        function renderCalendar() {
            const year = viewDate.getFullYear();
            const month = viewDate.getMonth();

            const firstDayIndex = new Date(year, month, 1).getDay(); // 0 = Sun
            const lastDayDate = new Date(year, month + 1, 0).getDate();
            const prevMonthLastDate = new Date(year, month, 0).getDate();

            const today = new Date();
            const isCurrentMonthView = today.getFullYear() === year && today.getMonth() === month;

            let daysHtml = '';

            // Previous month filler days
            for (let i = firstDayIndex - 1; i >= 0; i--) {
                const dayNum = prevMonthLastDate - i;
                daysHtml += `<div class="hr-cal-day-cell other-month" data-action="prev-month-day" data-day="${dayNum}">${dayNum}</div>`;
            }

            // Current month days
            for (let d = 1; d <= lastDayDate; d++) {
                const isToday = isCurrentMonthView && today.getDate() === d;
                const isSelected = selectedDate && 
                                   selectedDate.getFullYear() === year && 
                                   selectedDate.getMonth() === month && 
                                   selectedDate.getDate() === d;

                const classes = ['hr-cal-day-cell', 'current-month'];
                if (isToday) classes.push('today');
                if (isSelected) classes.push('selected');

                daysHtml += `<div class="${classes.join(' ')}" data-day="${d}">${d}</div>`;
            }

            // Next month filler days (fill up to complete weeks)
            const totalRendered = firstDayIndex + lastDayDate;
            const nextMonthDays = (totalRendered % 7 === 0) ? 0 : 7 - (totalRendered % 7);
            for (let n = 1; n <= nextMonthDays; n++) {
                daysHtml += `<div class="hr-cal-day-cell other-month" data-action="next-month-day" data-day="${n}">${n}</div>`;
            }

            calendarPopup.innerHTML = `
                <div class="hr-cal-header">
                    <div class="hr-cal-title-wrap">
                        <span class="hr-cal-month">${monthNames[month]}</span>
                        <span class="hr-cal-year">${year}</span>
                        <i class="ph ph-caret-down" style="font-size: 11px; color: #8b5cf6;"></i>
                    </div>
                    <div class="hr-cal-nav">
                        <button type="button" class="hr-cal-nav-btn prev" title="Previous Month">
                            <i class="ph ph-arrow-up"></i>
                        </button>
                        <button type="button" class="hr-cal-nav-btn next" title="Next Month">
                            <i class="ph ph-arrow-down"></i>
                        </button>
                    </div>
                </div>
                <div class="hr-cal-weekdays">
                    <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                </div>
                <div class="hr-cal-days-grid">
                    ${daysHtml}
                </div>
                <div class="hr-cal-footer">
                    <button type="button" class="hr-cal-action-btn clear-btn">Clear</button>
                    <button type="button" class="hr-cal-action-btn today-btn">Today</button>
                </div>
            `;

            // Month navigation
            calendarPopup.querySelector('.hr-cal-nav-btn.prev').onclick = (e) => {
                e.stopPropagation();
                viewDate.setMonth(viewDate.getMonth() - 1);
                renderCalendar();
            };

            calendarPopup.querySelector('.hr-cal-nav-btn.next').onclick = (e) => {
                e.stopPropagation();
                viewDate.setMonth(viewDate.getMonth() + 1);
                renderCalendar();
            };

            calendarPopup.querySelector('.clear-btn').onclick = (e) => {
                e.stopPropagation();
                if (activeInput) {
                    activeInput.value = '';
                    activeInput.dispatchEvent(new Event('input', { bubbles: true }));
                    activeInput.dispatchEvent(new Event('change', { bubbles: true }));
                    if (typeof activeInput.onchange === 'function') {
                        activeInput.onchange();
                    }
                }
                closeCalendar();
            };

            calendarPopup.querySelector('.today-btn').onclick = (e) => {
                e.stopPropagation();
                const now = new Date();
                selectDateAndClose(now.getFullYear(), now.getMonth(), now.getDate());
            };

            calendarPopup.querySelectorAll('.hr-cal-day-cell').forEach(cell => {
                cell.onclick = (e) => {
                    e.stopPropagation();
                    const day = parseInt(cell.getAttribute('data-day'));
                    const action = cell.getAttribute('data-action');
                    if (action === 'prev-month-day') {
                        viewDate.setMonth(viewDate.getMonth() - 1);
                        selectDateAndClose(viewDate.getFullYear(), viewDate.getMonth(), day);
                    } else if (action === 'next-month-day') {
                        viewDate.setMonth(viewDate.getMonth() + 1);
                        selectDateAndClose(viewDate.getFullYear(), viewDate.getMonth(), day);
                    } else {
                        selectDateAndClose(year, month, day);
                    }
                };
            });
        }

        function selectDateAndClose(y, m, d) {
            if (!activeInput) return;
            const mm = String(m + 1).padStart(2, '0');
            const dd = String(d).padStart(2, '0');
            const valStr = `${y}-${mm}-${dd}`;

            activeInput.value = valStr;
            activeInput.dispatchEvent(new Event('input', { bubbles: true }));
            activeInput.dispatchEvent(new Event('change', { bubbles: true }));
            if (typeof activeInput.onchange === 'function') {
                activeInput.onchange();
            }
            closeCalendar();
        }

        function openCalendar(input) {
            activeInput = input;
            const currentVal = input.value;
            if (currentVal && /^\d{4}-\d{2}-\d{2}$/.test(currentVal)) {
                const parts = currentVal.split('-');
                selectedDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                viewDate = new Date(selectedDate);
            } else {
                selectedDate = null;
                viewDate = new Date();
            }

            renderCalendar();

            // Position popup relative to input
            const rect = input.getBoundingClientRect();
            calendarPopup.classList.add('show');
            calendarPopup.style.position = 'fixed';
            calendarPopup.style.zIndex = '99999999';

            const popupWidth = 290;
            let left = rect.left;
            let top = rect.bottom + 6;

            if (left + popupWidth > window.innerWidth - 10) {
                left = window.innerWidth - popupWidth - 14;
            }
            if (left < 10) left = 10;

            const popupHeight = 310;
            if (top + popupHeight > window.innerHeight && rect.top > popupHeight) {
                top = rect.top - popupHeight - 6;
            }

            calendarPopup.style.left = `${left}px`;
            calendarPopup.style.top = `${top}px`;
        }

        function closeCalendar() {
            calendarPopup.classList.remove('show');
            activeInput = null;
        }

        dateInputs.forEach(input => {
            if (input.dataset.customPickerInit) return;
            input.dataset.customPickerInit = 'true';

            // Convert to text with readonly to prevent native OS calendar popup
            input.type = 'text';
            input.readOnly = true;
            input.style.cursor = 'pointer';

            const wrap = document.createElement('div');
            wrap.className = 'hr-custom-date-wrap';
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(input);

            const calIcon = document.createElement('i');
            calIcon.className = 'ph ph-calendar-blank hr-custom-date-icon';
            wrap.appendChild(calIcon);

            wrap.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (activeInput === input && calendarPopup.classList.contains('show')) {
                    closeCalendar();
                } else {
                    openCalendar(input);
                }
            });
        });

        document.addEventListener('click', (e) => {
            if (!calendarPopup.contains(e.target) && (!activeInput || !activeInput.closest('.hr-custom-date-wrap') || !activeInput.closest('.hr-custom-date-wrap').contains(e.target))) {
                closeCalendar();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeCalendar();
            }
        });
    }

    // ============================================================
    // UNIVERSAL CUSTOM GLASSMORPHIC DROPDOWN (85% Transparency)
    // ============================================================
    function initCustomSelects() {
        const selects = document.querySelectorAll('select.hr-select, select.form-select, .hr-filter-form select, .hr-per-page-select');

        selects.forEach(select => {
            if (select.dataset.customSelectInit) return;
            select.dataset.customSelectInit = 'true';

            // Hide native select from view but keep accessible in form
            select.style.position = 'absolute';
            select.style.opacity = '0';
            select.style.pointerEvents = 'none';
            select.style.width = '0';
            select.style.height = '0';
            select.style.margin = '0';
            select.style.padding = '0';
            select.style.border = 'none';

            // Create custom wrapper
            const wrap = document.createElement('div');
            wrap.className = 'hr-custom-select-wrap';
            select.parentNode.insertBefore(wrap, select);
            wrap.appendChild(select);

            // Trigger button
            const trigger = document.createElement('div');
            trigger.className = 'hr-custom-select-trigger';
            trigger.setAttribute('tabindex', '0');
            trigger.setAttribute('role', 'button');
            trigger.setAttribute('aria-haspopup', 'listbox');

            const triggerText = document.createElement('span');
            triggerText.className = 'hr-select-trigger-text';

            const chevron = document.createElement('i');
            chevron.className = 'ph ph-caret-down hr-select-chevron';

            trigger.appendChild(triggerText);
            trigger.appendChild(chevron);
            wrap.appendChild(trigger);

            // Custom Menu
            const menu = document.createElement('div');
            menu.className = 'hr-custom-select-menu';
            menu.setAttribute('role', 'listbox');
            wrap.appendChild(menu);

            function positionMenu() {
                const rect = trigger.getBoundingClientRect();
                menu.style.position = 'fixed';
                menu.style.left = `${rect.left}px`;
                menu.style.minWidth = `${rect.width}px`;
                menu.style.width = `${rect.width}px`;
                menu.style.maxWidth = `${Math.max(rect.width, 360)}px`;
                menu.style.zIndex = '99999999';

                const spaceBelow = window.innerHeight - rect.bottom;
                const menuHeight = Math.min(menu.scrollHeight || 240, 280);

                if (spaceBelow < menuHeight + 10 && rect.top > menuHeight) {
                    menu.style.top = 'auto';
                    menu.style.bottom = `${window.innerHeight - rect.top + 6}px`;
                } else {
                    menu.style.top = `${rect.bottom + 6}px`;
                    menu.style.bottom = 'auto';
                }
            }

            function closeMenu() {
                wrap.classList.remove('open');
                if (menu.parentNode === document.body) {
                    document.body.removeChild(menu);
                    wrap.appendChild(menu);
                }
            }

            function openMenu() {
                // Close all other open custom selects
                document.querySelectorAll('.hr-custom-select-wrap.open').forEach(w => {
                    if (w !== wrap && typeof w._closeCustomSelect === 'function') {
                        w._closeCustomSelect();
                    }
                });

                wrap.classList.add('open');
                document.body.appendChild(menu);
                positionMenu();
            }

            wrap._closeCustomSelect = closeMenu;

            function updateOptions() {
                menu.innerHTML = '';
                const options = Array.from(select.options);
                let selectedOpt = select.options[select.selectedIndex] || options[0];

                triggerText.textContent = selectedOpt ? selectedOpt.text : 'Select...';

                options.forEach((opt, idx) => {
                    const optEl = document.createElement('div');
                    optEl.className = 'hr-custom-select-option' + (opt.selected ? ' selected' : '');
                    optEl.setAttribute('role', 'option');
                    optEl.setAttribute('data-value', opt.value);

                    const labelSpan = document.createElement('span');
                    labelSpan.textContent = opt.text;
                    optEl.appendChild(labelSpan);

                    if (opt.selected) {
                        const checkIcon = document.createElement('i');
                        checkIcon.className = 'ph ph-check hr-select-check';
                        optEl.appendChild(checkIcon);
                    }

                    optEl.onclick = (e) => {
                        e.stopPropagation();
                        select.selectedIndex = idx;
                        select.value = opt.value;
                        triggerText.textContent = opt.text;

                        menu.querySelectorAll('.hr-custom-select-option').forEach(el => el.classList.remove('selected'));
                        optEl.classList.add('selected');

                        closeMenu();

                        // Fire native change events
                        select.dispatchEvent(new Event('input', { bubbles: true }));
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        if (typeof select.onchange === 'function') {
                            select.onchange();
                        }
                    };

                    menu.appendChild(optEl);
                });
            }

            updateOptions();

            optEl_click_callback = (e, opt, idx) => {
                e.stopPropagation();
                select.selectedIndex = idx;
                select.value = opt.value;
                triggerText.textContent = opt.text;

                menu.querySelectorAll('.hr-custom-select-option').forEach(el => el.classList.remove('selected'));
                const matchedOpt = menu.querySelectorAll('.hr-custom-select-option')[idx];
                if (matchedOpt) matchedOpt.classList.add('selected');

                closeMenu();

                // Fire native change events
                select.dispatchEvent(new Event('input', { bubbles: true }));
                select.dispatchEvent(new Event('change', { bubbles: true }));
                if (typeof select.onchange === 'function') {
                    select.onchange();
                }
            };

            // Toggle open
            trigger.onclick = (e) => {
                e.stopPropagation();
                if (wrap.classList.contains('open')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            };

            // Keyboard navigation
            trigger.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    trigger.click();
                } else if (e.key === 'Escape') {
                    closeMenu();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (!wrap.classList.contains('open')) {
                        openMenu();
                    } else if (select.selectedIndex < select.options.length - 1) {
                        select.selectedIndex++;
                        updateOptions();
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (select.selectedIndex > 0) {
                        select.selectedIndex--;
                        updateOptions();
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            });
        });

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.hr-custom-select-wrap') && !e.target.closest('.hr-custom-select-menu')) {
                document.querySelectorAll('.hr-custom-select-wrap.open').forEach(w => {
                    if (typeof w._closeCustomSelect === 'function') {
                        w._closeCustomSelect();
                    }
                });
            }
        });

        // Close on scroll so floating menu does not detach from trigger
        window.addEventListener('scroll', () => {
            document.querySelectorAll('.hr-custom-select-wrap.open').forEach(w => {
                if (typeof w._closeCustomSelect === 'function') {
                    w._closeCustomSelect();
                }
            });
        }, { passive: true });
    }

    // Initialize custom components
    initCustomDatePickers();
    initCustomSelects();

    window.initCustomDatePickers = initCustomDatePickers;
    window.initCustomSelects = initCustomSelects;

    // ============================================================
    // Universal Right Modal Controls (Click Outside & Escape Key)
    // ============================================================
    document.addEventListener('click', (e) => {
        if (e.target && e.target.classList.contains('hr-modal-overlay') && e.target.classList.contains('open')) {
            e.target.classList.remove('open');
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.hr-modal-overlay.open');
            openModals.forEach(m => m.classList.remove('open'));
        }
    });

    // Universal Modal Helper Functions
    window.openModal = function(id) {
        const m = document.getElementById(id);
        if (m) {
            m.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    };
    window.closeModal = function(id) {
        const m = document.getElementById(id);
        if (m) {
            m.classList.remove('open');
            if (!document.querySelector('.hr-modal-overlay.open')) {
                document.body.style.overflow = '';
            }
        }
    };

    // -------------------------------------------------------------
    // Global SweetAlert2 Confirmation Dialog Helpers & Auto-Interceptor
    // -------------------------------------------------------------
    window.rmsConfirm = function(options = {}) {
        if (typeof Swal === 'undefined') {
            return Promise.resolve(confirm(options.text || options.title || 'Are you sure?'));
        }
        const isDanger = options.isDanger !== false;
        return Swal.fire({
            title: options.title || 'Are you sure?',
            html: options.html || options.text || '',
            icon: options.icon || (isDanger ? 'warning' : 'question'),
            showCancelButton: true,
            confirmButtonColor: options.confirmColor || (isDanger ? '#dc2626' : '#7c3aed'),
            cancelButtonColor: options.cancelColor || '#64748b',
            confirmButtonText: options.confirmText || (isDanger ? 'Yes, proceed' : 'Confirm'),
            cancelButtonText: options.cancelText || 'Cancel',
            reverseButtons: true,
            customClass: {
                popup: 'rms-swal-popup',
                confirmButton: isDanger ? 'swal2-danger' : ''
            }
        }).then(result => result.isConfirmed);
    };

    // Auto-upgrade native HTML forms with onsubmit="return confirm(...)"
    document.querySelectorAll('form[onsubmit*="confirm("]').forEach(function(form) {
        const onsubmitAttr = form.getAttribute('onsubmit') || '';
        const match = onsubmitAttr.match(/confirm\((?:'|")([^'"]+)(?:'|")\)/);
        if (match) {
            const promptMsg = match[1].replace(/\\'/g, "'").replace(/\\"/g, '"');
            form.removeAttribute('onsubmit');
            form.addEventListener('submit', function(e) {
                if (form.dataset.swalApproved === 'true') return;
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Are you sure?',
                        html: promptMsg,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc2626',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Yes, Proceed',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true,
                        customClass: {
                            confirmButton: 'swal2-danger'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.dataset.swalApproved = 'true';
                            form.submit();
                        }
                    });
                } else if (confirm(promptMsg)) {
                    form.dataset.swalApproved = 'true';
                    form.submit();
                }
            });
        }
    });

    // Initialize Universal DataTables-style Table Sorter
    if (typeof window.initRmsTableSorting === 'function') {
        window.initRmsTableSorting();
    }
});

// =========================================================================
// Universal DataTables-Style Table Sorter for All RMS Tables
// =========================================================================
function initRmsTableSorting() {
    const tableSelectors = [
        'table.hr-table',
        'table.datatable',
        'table.table-sortable',
        'table.vendor-table',
        'table.rfq-table',
        'table.po-table',
        'table.stk-table',
        'table.bom-table',
        'table.inv-master-table',
        'table.hr-recent-table',
        'table.rms-table'
    ].join(', ');

    const tables = document.querySelectorAll(tableSelectors);

    tables.forEach(table => {
        // Skip tables explicitly marked as non-sortable or matrix grid schedules
        if (table.classList.contains('sched-matrix-table') || 
            table.classList.contains('no-sort') || 
            table.getAttribute('data-no-sort') === 'true' ||
            table.closest('.sched-matrix-wrapper') ||
            table.closest('.sched-matrix-container')) {
            return;
        }

        // Skip tables with their own custom sorting implementation
        if (table.getAttribute('data-custom-sort') === 'true' ||
            table.id === 'employeesDirectoryTable' ||
            table.id === 'companiesTable' ||
            table.id === 'documentsTable') {
            return;
        }

        const thead = table.querySelector('thead');
        const tbody = table.querySelector('tbody');
        if (!thead || !tbody) return;

        // Target last row of headers
        const headerRow = thead.querySelector('tr:last-child');
        if (!headerRow) return;

        const ths = Array.from(headerRow.querySelectorAll('th'));
        
        ths.forEach((th, colIndex) => {
            // Check if column is an action column or explicitly non-sortable
            const rawText = th.textContent.trim().toUpperCase();
            const actionLabels = ['ACTIONS', 'ACTION', 'WORKFLOW ACTIONS', 'MANUAL PUNCH', 'MANUAL', 'ADJUST', 'SELECT', 'ALL', '#', '', 'OPERATIONS', 'PREVIEW'];
            const isActionCol = th.classList.contains('no-sort') || 
                                th.classList.contains('non-sortable') ||
                                th.getAttribute('data-sortable') === 'false' ||
                                th.hasAttribute('onclick') ||
                                actionLabels.includes(rawText) ||
                                th.querySelector('input[type="checkbox"]');

            if (isActionCol) return;

            // Mark as sortable if not already
            if (!th.classList.contains('sortable')) {
                th.classList.add('sortable');
                th.setAttribute('title', `Click to sort by ${th.textContent.trim()} (Ascending / Descending)`);

                // If not already wrapped with sort-icon
                if (!th.querySelector('.sort-icon')) {
                    const currentHtml = th.innerHTML.trim();
                    th.innerHTML = `
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; width: 100%;">
                            <span>${currentHtml}</span>
                            <span style="display: inline-flex; align-items: center; flex-shrink: 0;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    `;
                }
            }

            // Avoid attaching duplicate click listeners
            if (th.dataset.rmsSortBound === 'true') return;
            th.dataset.rmsSortBound = 'true';

            th.addEventListener('click', (e) => {
                // Don't trigger if clicked on an input, button, select, or link inside th
                if (e.target.closest('input, button, select, a, label')) return;

                // Cycle sort state: none -> asc -> desc -> none
                let currentDir = th.dataset.sortDir || 'none';
                let nextDir = 'asc';
                if (currentDir === 'asc') nextDir = 'desc';
                else if (currentDir === 'desc') nextDir = 'none';

                // Reset all other headers in this table
                ths.forEach(otherTh => {
                    otherTh.classList.remove('sorted-asc', 'sorted-desc');
                    otherTh.dataset.sortDir = 'none';
                    const icon = otherTh.querySelector('.sort-icon i');
                    if (icon) icon.className = 'ph ph-arrows-down-up';
                });

                if (nextDir !== 'none') {
                    th.classList.add(nextDir === 'asc' ? 'sorted-asc' : 'sorted-desc');
                    th.dataset.sortDir = nextDir;
                    const icon = th.querySelector('.sort-icon i');
                    if (icon) {
                        icon.className = nextDir === 'asc' ? 'ph ph-caret-up' : 'ph ph-caret-down';
                    }
                }

                // Perform sorting on tbody rows
                sortHtmlTable(table, tbody, colIndex, nextDir);
            });
        });
    });
}

window.initRmsTableSorting = initRmsTableSorting;

// Automatically run if script loads after DOM is ready
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    setTimeout(initRmsTableSorting, 1);
}

function sortHtmlTable(table, tbody, colIndex, direction) {
    const allRows = Array.from(tbody.querySelectorAll('tr'));
    
    // Separate data rows from special empty/no-results rows
    const dataRows = [];
    const pinnedRows = [];

    allRows.forEach((row, idx) => {
        // Tag original index if not already
        if (typeof row._origSortIndex === 'undefined') {
            row._origSortIndex = idx;
        }

        const isPinned = row.classList.contains('no-results-row') ||
                         row.classList.contains('empty-row') ||
                         row.id === 'noEmpResultsRow' ||
                         row.querySelector('td[colspan]');

        if (isPinned) {
            pinnedRows.push(row);
        } else {
            dataRows.push(row);
        }
    });

    if (direction === 'none') {
        // Reset to original DOM order
        dataRows.sort((a, b) => a._origSortIndex - b._origSortIndex);
    } else {
        dataRows.sort((rowA, rowB) => {
            const cellA = rowA.children[colIndex];
            const cellB = rowB.children[colIndex];

            if (!cellA && !cellB) return 0;
            if (!cellA) return 1;
            if (!cellB) return -1;

            const valA = getCellSortValue(cellA);
            const valB = getCellSortValue(cellB);

            // Compare parsed values
            let cmp = 0;
            if (valA.type === 'number' && valB.type === 'number') {
                cmp = valA.value - valB.value;
            } else if (valA.type === 'date' && valB.type === 'date') {
                cmp = valA.value - valB.value;
            } else {
                cmp = String(valA.value).localeCompare(String(valB.value), undefined, { 
                    numeric: true, 
                    sensitivity: 'base' 
                });
            }

            return direction === 'asc' ? cmp : -cmp;
        });
    }

    // Re-append in sorted order
    dataRows.forEach(row => tbody.appendChild(row));
    pinnedRows.forEach(row => tbody.appendChild(row));

    // Dispatch custom event for views to listen to
    table.dispatchEvent(new CustomEvent('rmsTableSorted', {
        bubbles: true,
        detail: { table, tbody, colIndex, direction }
    }));

    // If table has custom filter / pagination callback, trigger it
    if (typeof window.filterTimekeepingRows === 'function' && table.querySelector('.timekeeping-row')) {
        window.filterTimekeepingRows();
    }
    if (typeof window.filterDeptsTable === 'function' && table.querySelector('.dept-row')) {
        window.filterDeptsTable();
    }
    if (typeof window.refreshBranchPage === 'function' && table.querySelector('.branch-row')) {
        window.refreshBranchPage();
    }
    if (typeof window.refreshPosPage === 'function' && table.querySelector('.pos-row')) {
        window.refreshPosPage();
    }
    if (typeof window.refreshUsersPage === 'function' && table.querySelector('.user-row')) {
        window.refreshUsersPage();
    }
    if (typeof window.refreshLogsPage === 'function' && table.querySelector('.log-row')) {
        window.refreshLogsPage();
    }
}

function getCellSortValue(cell) {
    // 1. Check explicit data attributes
    const attrVal = cell.getAttribute('data-sort-value') || 
                    cell.getAttribute('data-sort') || 
                    cell.getAttribute('data-value') || 
                    cell.getAttribute('data-date') || 
                    cell.getAttribute('data-timestamp') || 
                    cell.getAttribute('data-id');
    if (attrVal !== null && attrVal !== '') {
        const num = Number(attrVal);
        if (!isNaN(num)) return { type: 'number', value: num };
        const d = Date.parse(attrVal);
        if (!isNaN(d)) return { type: 'date', value: d };
        return { type: 'text', value: attrVal.trim().toLowerCase() };
    }

    // 2. Check form inputs or selects inside cell
    const input = cell.querySelector('input, select');
    if (input && (input.type === 'text' || input.type === 'number' || input.tagName === 'SELECT')) {
        const v = (input.value || '').trim();
        const n = Number(v);
        if (!isNaN(n) && v !== '') return { type: 'number', value: n };
        return { type: 'text', value: v.toLowerCase() };
    }

    // 3. Extract plain text content
    let rawText = cell.innerText.trim();
    if (!rawText) return { type: 'text', value: '' };

    // Check for negative currency / accounting format: "(₱ 500.00)" or "(120.50)"
    let isNegative = false;
    if (/^\(.*\)$/.test(rawText)) {
        isNegative = true;
        rawText = rawText.replace(/^\(|\)$/g, '').trim();
    }

    // Check for currency or numeric with unit: e.g. "₱ 12,500.00", "8.5 hrs", "10%", "$45.00", "15 day(s)"
    const cleanedNumStr = rawText.replace(/[₱$,%\s]/g, '')
                                 .replace(/hrs?|days?|mins?|day\(s\)|hours?|staff|items?|pcs?|employees/gi, '')
                                 .trim();
    if (cleanedNumStr !== '' && !isNaN(Number(cleanedNumStr)) && /^[\d.-]+$/.test(cleanedNumStr)) {
        let val = parseFloat(cleanedNumStr);
        if (isNegative) val = -val;
        return { type: 'number', value: val };
    }

    // Check for date ranges or standard date formats: "2026-09-30", "Sep 30, 2026", "Sep 25 - Sep 30, 2026", "09/30/2026"
    const dateMatch = rawText.match(/\b([a-zA-Z]{3,9}\s+\d{1,2}(?:,\s+\d{4})?|\d{4}-\d{2}-\d{2}|\d{1,2}\/\d{1,2}\/\d{4})\b/);
    if (dateMatch) {
        const parsedDate = Date.parse(dateMatch[1]);
        if (!isNaN(parsedDate)) {
            return { type: 'date', value: parsedDate };
        }
    }

    // Check for time: e.g. "08:00 - 17:00", "10:30", "14:15:00", "08:00 AM"
    const timeMatch = rawText.match(/^(\d{1,2}):(\d{2})(?::\d{2})?(?:\s*(AM|PM))?/i);
    if (timeMatch && !rawText.includes('/')) {
        let hrs = parseInt(timeMatch[1], 10);
        const mins = parseInt(timeMatch[2], 10);
        const meridiem = timeMatch[3] ? timeMatch[3].toUpperCase() : null;
        if (meridiem === 'PM' && hrs < 12) hrs += 12;
        if (meridiem === 'AM' && hrs === 12) hrs = 0;
        return { type: 'number', value: hrs * 60 + mins };
    }

    return { type: 'text', value: rawText.toLowerCase() };
}

// =========================================================================
// Global 2-Digit Year Auto-Correction for Date & Datetime Inputs
// Maps 2-digit year entries (e.g. '01' to '10' -> 2001 to 2010, 00-99 -> 2000-2099)
// Automatically fixes browser native year interpretation ('0001' -> '2001')
// Supports typing formats like 10/21/01 or MMDDYY + Tab
// =========================================================================
(function() {
    const inputBuffers = new WeakMap();

    function autoCorrect2DigitYear(input) {
        if (!input) return;
        const val = input.value;
        if (!val) return;

        // 1. Browser native ISO date / datetime starting with 00XX-
        // e.g. 0001-10-21 or 0001-10-01T01:00 or 0010-05-15
        const isoMatch = val.match(/^00([0-9]{2})-(.*)$/);
        if (isoMatch) {
            const yy = parseInt(isoMatch[1], 10);
            if (yy >= 0 && yy <= 99) {
                const fullYear = 2000 + yy; // 01-10 -> 2001-2010
                input.value = `${fullYear}-${isoMatch[2]}`;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                return;
            }
        }

        // 2. Text input formats like 10/21/01 -> 10/21/2001
        if (input.type === 'text' || !input.type) {
            const textMatch = val.match(/^(\d{1,2})([\/\-\.])(\d{1,2})\2(\d{2})$/);
            if (textMatch) {
                const m = textMatch[1].padStart(2, '0');
                const sep = textMatch[2];
                const d = textMatch[3].padStart(2, '0');
                const yy = parseInt(textMatch[4], 10);
                if (yy >= 0 && yy <= 99) {
                    const fullYear = 2000 + yy;
                    input.value = `${m}${sep}${d}${sep}${fullYear}`;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        }
    }

    // Capture standard browser events
    ['input', 'change', 'keyup', 'blur'].forEach(evt => {
        document.addEventListener(evt, function(e) {
            const target = e.target;
            if (target && (target.type === 'date' || target.type === 'datetime-local' || (target.type === 'text' && target.name && target.name.includes('date')))) {
                autoCorrect2DigitYear(target);
                setTimeout(() => autoCorrect2DigitYear(target), 0);
            }
        }, true);
    });

    // Track keystrokes for Tab completion
    document.addEventListener('keydown', function(e) {
        const target = e.target;
        if (!target || (target.type !== 'date' && target.type !== 'datetime-local')) return;

        let buf = inputBuffers.get(target);
        if (!buf) {
            buf = { raw: '', digits: '' };
            inputBuffers.set(target, buf);
        }

        if ((e.key >= '0' && e.key <= '9') || e.key === '/' || e.key === '-' || e.key === '.') {
            buf.raw += e.key;
            if (e.key >= '0' && e.key <= '9') {
                buf.digits += e.key;
            }
            return;
        }

        if (e.key === 'Backspace' || e.key === 'Delete') {
            buf.raw = '';
            buf.digits = '';
            return;
        }

        if (e.key === 'Tab' || e.key === 'Enter') {
            autoCorrect2DigitYear(target);
            setTimeout(() => autoCorrect2DigitYear(target), 0);
            setTimeout(() => autoCorrect2DigitYear(target), 50);

            // Buffer parsing: slash/dash/dot format (e.g. 10/21/01)
            const slashMatch = buf.raw.match(/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{1,4})(?:[T\s](\d{1,2})(?::(\d{1,2}))?)?$/);
            if (slashMatch) {
                const m = parseInt(slashMatch[1], 10);
                const d = parseInt(slashMatch[2], 10);
                let y = parseInt(slashMatch[3], 10);
                if (m >= 1 && m <= 12 && d >= 1 && d <= 31) {
                    if (y >= 0 && y <= 99) {
                        y = 2000 + y; // 01-10 -> 2001-2010
                    }
                    const datePart = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                    if (target.type === 'date') {
                        target.value = datePart;
                    } else if (target.type === 'datetime-local') {
                        let timePart = '00:00';
                        if (slashMatch[4]) {
                            const hh = String(parseInt(slashMatch[4], 10)).padStart(2, '0');
                            const mm = slashMatch[5] ? String(parseInt(slashMatch[5], 10)).padStart(2, '0') : '00';
                            timePart = `${hh}:${mm}`;
                        } else if (target.value && target.value.includes('T')) {
                            timePart = target.value.split('T')[1].substring(0, 5);
                        }
                        target.value = `${datePart}T${timePart}`;
                    }
                    target.dispatchEvent(new Event('input', { bubbles: true }));
                    target.dispatchEvent(new Event('change', { bubbles: true }));
                }
            } else if (buf.digits.length === 6) {
                // 6 digits: MMDDYY (e.g. 102101 -> 2001-10-21)
                const m = parseInt(buf.digits.substring(0, 2), 10);
                const d = parseInt(buf.digits.substring(2, 4), 10);
                let y = parseInt(buf.digits.substring(4, 6), 10);
                if (m >= 1 && m <= 12 && d >= 1 && d <= 31) {
                    if (y >= 0 && y <= 99) y = 2000 + y;
                    const datePart = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                    if (target.type === 'date') {
                        target.value = datePart;
                    } else if (target.type === 'datetime-local') {
                        let timePart = '00:00';
                        if (target.value && target.value.includes('T')) {
                            timePart = target.value.split('T')[1].substring(0, 5);
                        }
                        target.value = `${datePart}T${timePart}`;
                    }
                    target.dispatchEvent(new Event('input', { bubbles: true }));
                    target.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            buf.raw = '';
            buf.digits = '';
        }
    }, true);
})();

/* ==========================================================================
   Universal HR Operations Form Validator (Emails, Phones, Required Fields)
   ========================================================================== */
(function() {
    'use strict';

    const EMAIL_REGEX = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/;
    const PHONE_ALLOWED_CHARS = /^[0-9+\s\-()]*$/;

    function isPhoneField(el) {
        if (!el || el.tagName !== 'INPUT') return false;
        const type = (el.type || '').toLowerCase();
        const name = (el.name || '').toLowerCase();
        const id = (el.id || '').toLowerCase();
        return type === 'tel' || 
               name.includes('phone') || 
               name.includes('mobile') || 
               name.includes('contact_no') ||
               name.includes('contact_number') ||
               name.includes('contact_num') ||
               name.includes('telephone') ||
               id.includes('phone') || 
               id.includes('mobile') ||
               id.includes('telephone');
    }

    function isEmailField(el) {
        if (!el || el.tagName !== 'INPUT') return false;
        const type = (el.type || '').toLowerCase();
        const name = (el.name || '').toLowerCase();
        const id = (el.id || '').toLowerCase();
        return type === 'email' || name.includes('email') || id.includes('email');
    }

    function validatePhone(val) {
        if (!val) return { valid: true };
        const clean = val.trim();
        if (!PHONE_ALLOWED_CHARS.test(clean)) {
            return { valid: false, message: 'Phone number can only contain digits, +, -, ( ), and spaces.' };
        }
        const digitCount = (clean.match(/\d/g) || []).length;
        if (digitCount < 7) {
            return { valid: false, message: 'Phone number must have at least 7 digits (e.g. 0917-123-4567 or (02) 8888-1234).' };
        }
        if (digitCount > 15) {
            return { valid: false, message: 'Phone number cannot exceed 15 digits.' };
        }
        return { valid: true };
    }

    function validateEmail(val) {
        if (!val) return { valid: true };
        const clean = val.trim();
        if (!EMAIL_REGEX.test(clean)) {
            return { valid: false, message: 'Please enter a valid email address (e.g. name@domain.com).' };
        }
        return { valid: true };
    }

    function showFieldError(input, message) {
        clearFieldError(input);
        input.classList.add('hr-input-invalid');
        const err = document.createElement('div');
        err.className = 'hr-field-error-msg';
        err.innerHTML = `<i class="ph ph-warning-circle" style="font-size: 13px; flex-shrink: 0;"></i> <span>${message}</span>`;
        input.parentNode.appendChild(err);
    }

    function clearFieldError(input) {
        input.classList.remove('hr-input-invalid');
        const existing = input.parentNode ? input.parentNode.querySelector('.hr-field-error-msg') : null;
        if (existing) existing.remove();
    }

    // Real-time listener for all phone and email fields
    document.addEventListener('input', (e) => {
        const target = e.target;
        if (isPhoneField(target) || isEmailField(target)) {
            clearFieldError(target);
        }
    });

    document.addEventListener('blur', (e) => {
        const target = e.target;
        if (isEmailField(target) && target.value.trim() !== '') {
            const res = validateEmail(target.value);
            if (!res.valid) showFieldError(target, res.message);
        } else if (isPhoneField(target) && target.value.trim() !== '') {
            const res = validatePhone(target.value);
            if (!res.valid) showFieldError(target, res.message);
        }
    }, true);

    // Global form submit interceptor for HR Operations forms
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!form || form.tagName !== 'FORM') return;

        // Skip search filter forms or delete confirmation forms that only submit CSRF
        const isDeleteOrGet = (form.method || '').toUpperCase() === 'GET' || form.querySelector('input[name="_method"][value="DELETE"]');
        if (isDeleteOrGet && !form.querySelector('input[type="tel"], input[type="email"], input[name*="email"], input[name*="phone"], input[name*="mobile"], input[name*="contact"]')) {
            return;
        }

        let firstInvalid = null;
        let errorMessages = [];

        const allInputs = form.querySelectorAll('input');
        allInputs.forEach(input => {
            const val = input.value ? input.value.trim() : '';

            // Email check
            if (isEmailField(input)) {
                if (input.required && !val) {
                    showFieldError(input, 'Email address is required.');
                    if (!firstInvalid) firstInvalid = input;
                    errorMessages.push('Email is required');
                } else if (val) {
                    const res = validateEmail(val);
                    if (!res.valid) {
                        showFieldError(input, res.message);
                        if (!firstInvalid) firstInvalid = input;
                        errorMessages.push(res.message);
                    }
                }
            }

            // Phone check
            if (isPhoneField(input)) {
                if (input.required && !val) {
                    showFieldError(input, 'Phone number is required.');
                    if (!firstInvalid) firstInvalid = input;
                    errorMessages.push('Phone number is required');
                } else if (val) {
                    const res = validatePhone(val);
                    if (!res.valid) {
                        showFieldError(input, res.message);
                        if (!firstInvalid) firstInvalid = input;
                        errorMessages.push(res.message);
                    }
                }
            }
        });

        // If errors found, prevent submit and highlight first field
        if (firstInvalid) {
            e.preventDefault();
            e.stopPropagation();
            firstInvalid.focus();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Form Validation Error',
                    text: errorMessages[0] || 'Please correct the highlighted errors before submitting.',
                    confirmButtonColor: '#7c3aed',
                    confirmButtonText: 'Review Form',
                    timer: 4000,
                    timerProgressBar: true
                });
            }
            return false;
        }
    }, true);
})();




