/**
 * NOUR TRUST ERP - Global Application Script
 */

// التحكم في إخفاء وتصغير القائمة الجانبية (Sidebar)
window.toggleSidebar = function(e) {
    if (e) e.preventDefault();
    const sidebar = document.querySelector('.nt-sidebar');
    const mainContent = document.querySelector('.erp-main-container');

    if (sidebar) sidebar.classList.toggle('collapsed');
    if (mainContent) mainContent.classList.toggle('sidebar-collapsed');
};

// التحكم في القوائم الفرعية (Accordion)
window.toggleMenu = function(menuId, element) {
    const sidebar = document.querySelector('.nt-sidebar');
    
    // إذا كانت القائمة مصغرة، نكبرها أولاً قبل الفتح
    if (sidebar && sidebar.classList.contains('collapsed')) {
        sidebar.classList.remove('collapsed');
        const mainContent = document.querySelector('.erp-main-container');
        if (mainContent) mainContent.classList.remove('sidebar-collapsed');
        
        setTimeout(() => { window.toggleMenu(menuId, element); }, 300);
        return;
    }

    // إغلاق كل القوائم المفتوحة الأخرى
    document.querySelectorAll('.sub-menu').forEach(menu => {
        if(menu.id !== menuId) {
            menu.classList.remove('open');
            if(menu.previousElementSibling) menu.previousElementSibling.classList.remove('active');
        }
    });

    // فتح أو إغلاق القائمة المطلوبة
    const menu = document.getElementById(menuId);
    if(menu) {
        const isActive = menu.classList.contains('open');
        if (isActive) {
            menu.classList.remove('open');
            if(element) element.classList.remove('active');
        } else {
            menu.classList.add('open');
            if(element) element.classList.add('active');
        }
    }
};

// التحكم في قائمة البروفايل (Logout)
window.toggleProfileDropdown = function() {
    const dropdown = document.getElementById('profileDropdown') || document.getElementById('profileDropdownMenu');
    if (!dropdown) return;
    dropdown.style.display = dropdown.style.display === 'none' || dropdown.style.display === '' ? 'block' : 'none';
};

// إغلاق القوائم عند النقر في أي مكان فارغ بالشاشة
document.addEventListener('click', function(event) {
    const container = document.getElementById('profileDropdownContainer');
    const dropdown = document.getElementById('profileDropdown') || document.getElementById('profileDropdownMenu');
    if (container && dropdown && !container.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});