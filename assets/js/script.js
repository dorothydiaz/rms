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
                // If navigating to Home / index.php, Home has no sub-menu so collapse sidebar
                if (href.endsWith('index.php') || href.endsWith('/rms/') || href.endsWith('/rms') || href === '/') {
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
            const state = localStorage.getItem('rms_sidebar_collapsed');
            if (sidebar && state !== null) {
                if (state === 'true') {
                    sidebar.classList.add('collapsed');
                } else if (state === 'false') {
                    sidebar.classList.remove('collapsed');
                }
            }
        } catch (err) {}
    });
});

