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

    // Desktop Sidebar Collapse Toggle
    const toggleBtn = document.querySelector('.sidebar-toggle-btn');
    if (toggleBtn) {
        toggleBtn.onclick = () => {
            const isNowCollapsed = sidebar.classList.toggle('collapsed');
            try {
                localStorage.setItem('rms_sidebar_collapsed', isNowCollapsed ? 'true' : 'false');
            } catch (e) {}
        };
    }

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

    // Breadcrumb Dropdown Navigation Switcher
    const dropdownTriggers = document.querySelectorAll('.breadcrumb-dropdown-btn, .breadcrumb-dropdown-toggle');
    
    dropdownTriggers.forEach(triggerBtn => {
        triggerBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const container = triggerBtn.closest('.breadcrumb-dropdown-container');
            if (!container) return;
            
            const wasOpen = container.classList.contains('open');
            // Close any other open dropdowns first
            document.querySelectorAll('.breadcrumb-dropdown-container.open').forEach(c => {
                if (c !== container) {
                    c.classList.remove('open');
                    const btn = c.querySelector('.breadcrumb-dropdown-btn, .breadcrumb-dropdown-toggle');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                }
            });
            
            // Toggle current
            container.classList.toggle('open', !wasOpen);
            triggerBtn.setAttribute('aria-expanded', String(!wasOpen));
        });
    });

    // Close breadcrumb dropdowns on click outside or Escape
    function closeAllBreadcrumbDropdowns() {
        document.querySelectorAll('.breadcrumb-dropdown-container.open').forEach(c => {
            c.classList.remove('open');
            const btn = c.querySelector('.breadcrumb-dropdown-btn, .breadcrumb-dropdown-toggle');
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
            e.stopPropagation();
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

        // Sidebar navigation link clicked -> sidebar remains opened
        const sidebarLink = e.target.closest('.sidebar a[href]');
        if (sidebarLink) {
            const href = sidebarLink.getAttribute('href');
            if (href && href !== '#' && !href.startsWith('javascript:')) {
                try {
                    localStorage.setItem('rms_sidebar_collapsed', 'false');
                } catch (err) {}
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

