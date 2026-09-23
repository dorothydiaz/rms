document.addEventListener('DOMContentLoaded', () => {
    // Mobile sidebar toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    function toggleSidebar() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    }

    mobileMenuBtn.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', toggleSidebar);

    // Desktop Sidebar Collapse Toggle
    const toggleBtn = document.querySelector('.sidebar-toggle-btn');
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
    });

    // Submenu switching
    const railItems = document.querySelectorAll('.rail-item[data-target]');
    const submenus = document.querySelectorAll('.submenu');

    railItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Uncollapse if collapsed
            if (sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
            }

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
        });
    });

    // Dropdown Toggles (e.g., Posts)
    const dropdownGroups = document.querySelectorAll('.nav-item-group');
    dropdownGroups.forEach(group => {
        const subNav = group.nextElementSibling;
        if (subNav && subNav.classList.contains('sub-nav')) {
            group.addEventListener('click', (e) => {
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
            });
        }
    });
});
