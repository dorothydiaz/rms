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

    // Dropdown Toggles (Accordion expansion)
    const dropdownGroups = document.querySelectorAll('.nav-item-group');
    dropdownGroups.forEach(group => {
        const subNav = group.nextElementSibling;
        if (subNav && subNav.classList.contains('sub-nav')) {
            group.onclick = (e) => {
                e.preventDefault();
                subNav.classList.toggle('expanded');
                
                const btnIcon = group.querySelector('.add-btn i');
                if (btnIcon) {
                    if (subNav.classList.contains('expanded')) {
                        btnIcon.classList.replace('ph-plus', 'ph-minus');
                        btnIcon.classList.replace('ph-caret-down', 'ph-caret-up');
                    } else {
                        btnIcon.classList.replace('ph-minus', 'ph-plus');
                        btnIcon.classList.replace('ph-caret-up', 'ph-caret-down');
                    }
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

            const popupWidth = 280;
            let left = rect.left + window.scrollX;
            let top = rect.bottom + window.scrollY + 6;

            if (left + popupWidth > window.innerWidth - 10) {
                left = window.innerWidth - popupWidth - 14;
            }
            if (left < 10) left = 10;

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

                        wrap.classList.remove('open');

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

            // Toggle open
            trigger.onclick = (e) => {
                e.stopPropagation();
                const isOpening = !wrap.classList.contains('open');

                // Close all other open custom selects and reset their parent z-index
                document.querySelectorAll('.hr-custom-select-wrap.open').forEach(w => {
                    w.classList.remove('open');
                    w.style.zIndex = '';
                    const p = w.closest('.hr-filter-bar, .hr-table-header, .hr-table-card, .hr-pagination-container');
                    if (p) p.style.zIndex = '';
                });

                if (isOpening) {
                    wrap.classList.add('open');
                    wrap.style.zIndex = '99999';
                    const parentBar = wrap.closest('.hr-filter-bar, .hr-table-header, .hr-table-card, .hr-pagination-container');
                    if (parentBar) {
                        parentBar.style.zIndex = '1000';
                        parentBar.style.position = 'relative';
                    }

                    // Auto-flip upward if too close to bottom edge of screen
                    const rect = trigger.getBoundingClientRect();
                    const spaceBelow = window.innerHeight - rect.bottom;
                    if (spaceBelow < 260 && rect.top > 260) {
                        menu.style.top = 'auto';
                        menu.style.bottom = 'calc(100% + 6px)';
                    } else {
                        menu.style.top = 'calc(100% + 6px)';
                        menu.style.bottom = 'auto';
                    }
                } else {
                    wrap.classList.remove('open');
                    wrap.style.zIndex = '';
                    const parentBar = wrap.closest('.hr-filter-bar, .hr-table-header, .hr-table-card, .hr-pagination-container');
                    if (parentBar) {
                        parentBar.style.zIndex = '';
                    }
                }
            };

            // Keyboard navigation
            trigger.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    trigger.click();
                } else if (e.key === 'Escape') {
                    wrap.classList.remove('open');
                    wrap.style.zIndex = '';
                    const parentBar = wrap.closest('.hr-filter-bar, .hr-table-header, .hr-table-card, .hr-pagination-container');
                    if (parentBar) parentBar.style.zIndex = '';
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (!wrap.classList.contains('open')) {
                        trigger.click();
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
            document.querySelectorAll('.hr-custom-select-wrap.open').forEach(w => {
                if (!w.contains(e.target)) {
                    w.classList.remove('open');
                    w.style.zIndex = '';
                    const parentBar = w.closest('.hr-filter-bar, .hr-table-header, .hr-table-card, .hr-pagination-container');
                    if (parentBar) {
                        parentBar.style.zIndex = '';
                    }
                }
            });
        });
    }

    // Initialize custom components
    initCustomDatePickers();
    initCustomSelects();

    window.initCustomDatePickers = initCustomDatePickers;
    window.initCustomSelects = initCustomSelects;
});

