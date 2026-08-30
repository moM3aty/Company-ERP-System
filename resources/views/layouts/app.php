<?php
// Path: resources/views/layouts/app.php

if (session_status() === PHP_SESSION_NONE) session_start();

$currentLocale = $_SESSION['locale'] ?? 'ar';
$isAr = $currentLocale === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

$currentCompanyId = (int)($_SESSION['company_id'] ?? 1);
$currentCompanyName = current_company_name();
$currentCurrency = current_currency();

// جلب قائمة الشركات المتاحة للتبديل السريع
global $app;
$allCompanies = [];
if ($app && $app->has(\PDO::class)) {
    try {
        $db = $app->get(\PDO::class);
        $stmt = $db->query("SELECT id, name_ar, name_en, currency FROM sys_companies WHERE is_active = 1 ORDER BY id ASC");
        $allCompanies = $stmt->fetchAll(\PDO::FETCH_OBJ);
    } catch (\Throwable $e) {
        $allCompanies = [];
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $isAr ? 'ar' : 'en' ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Nour Trust ERP') ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- CSS Variables & Base Setup -->
    <style>
        :root {
            --spacing-xs: 4px; --spacing-sm: 8px; --spacing-md: 12px; --spacing-lg: 16px; --spacing-xl: 24px; --spacing-2xl: 32px;
            --color-background: #f0f4f8; --color-surface: #ffffff;
            --color-primary-50: #eff6ff; --color-primary-100: #dbeafe; --color-primary-200: #bfdbfe; --color-primary-500: #3b82f6; --color-primary-600: #2563eb; --color-primary-900: #0f172a;
            --color-accent: #f59e0b;
            --color-success: #059669; --color-success-bg: #ecfdf5; --color-success-border: #a7f3d0;
            --color-danger: #dc2626; --color-danger-bg: #fef2f2; --color-danger-border: #fecaca;
            --color-warning: #d97706; --color-warning-bg: #fffbeb; --color-warning-border: #fde68a;
            --color-info: #0284c7; --color-info-bg: #f0f9ff;
            --color-text-main: #0f172a; --color-text-secondary: #475569; --color-text-muted: #94a3b8; --color-text-inverse: #ffffff;
            --color-border: #e2e8f0; --color-border-hover: #cbd5e1;
            --radius-sm: 10px; --radius-md: 14px; --radius-lg: 20px;
            --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.06); --shadow-md: 0 4px 6px -1px rgba(15, 23, 42, 0.06);
            --font-en: 'Inter', system-ui, sans-serif; --font-ar: 'Cairo', system-ui, sans-serif;
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: <?= $isAr ? 'var(--font-ar)' : 'var(--font-en)' ?>;
            background-color: var(--color-background); 
            color: var(--color-text-main); 
            font-size: 14px; 
            line-height: 1.6; 
            overflow: hidden; 
        }

        .erp-wrapper { display: flex; width: 100vw; height: 100vh; overflow: hidden; }
        
        /* Sidebar Styles (Base) */
        .nt-sidebar {
            width: var(--sidebar-width);
            background-color: var(--color-primary-900);
            color: var(--color-text-inverse);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            z-index: 50;
        }
        
        /* Collapsed Sidebar overrides */
        .nt-sidebar.collapsed { width: var(--sidebar-collapsed-width) !important; min-width: var(--sidebar-collapsed-width) !important; }
        .nt-sidebar.collapsed .sidebar-text, .nt-sidebar.collapsed .ph-caret-right { display: none !important; }
        .nt-sidebar.collapsed .nav-hub { justify-content: center; padding: 12px 0; }
        .nt-sidebar.collapsed .sub-menu { display: none !important; }
        .nt-sidebar.collapsed .nav-link { justify-content: center; padding: 12px 0; }
        .nt-sidebar.collapsed .nav-link::before { display: none; }

        .erp-main-container { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            min-width: 0; 
            height: 100vh; 
            overflow-y: auto; 
        }

        /* Utility Classes used by Dashboard and other views */
        .erp-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 9px 20px; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; border: 1px solid transparent; transition: 0.2s; text-decoration: none; font-family: inherit; font-size: 0.85rem; }
        .erp-btn-primary { background: linear-gradient(135deg, var(--color-primary-600), var(--color-primary-700)); color: white; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25); }
        .erp-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35); }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
  
        @media print {
            header, nav, aside,
            .navbar, .main-header, .top-header, .top-nav, .app-header,
            .layout-header, .header-wrapper, .nt-sidebar, .sidebar {
                display: none !important;
                height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="erp-wrapper">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="erp-main-container">
            <?php include __DIR__ . '/../partials/navbar.php'; ?>
            <!-- Dashboard Content Container -->
            <main style="margin:20px;">
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <!-- Global JS Engine -->
    <script>
        // 1. Sidebar Toggle Logic
        window.toggleSidebar = function(e) {
            if (e) e.preventDefault();
            const sidebar = document.querySelector('.nt-sidebar');
            if (sidebar) sidebar.classList.toggle('collapsed');
        };

        // 2. Accordion Menu Logic
        window.toggleMenu = function(menuId, element) {
            if(!element) return;
            const sidebar = document.querySelector('.nt-sidebar');
            
            if (sidebar && sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
                setTimeout(() => { window.toggleMenu(menuId, element); }, 300);
                return;
            }

            document.querySelectorAll('.sub-menu').forEach(menu => {
                if(menu.id !== menuId) {
                    menu.classList.remove('open');
                    if (menu.previousElementSibling) menu.previousElementSibling.classList.remove('active');
                }
            });

            const menu = document.getElementById(menuId);
            if (menu) {
                const isActive = menu.classList.contains('open');
                if (isActive) {
                    menu.classList.remove('open');
                    element.classList.remove('active');
                } else {
                    menu.classList.add('open');
                    element.classList.add('active');
                }
            }
        };

        // 3. Profile Dropdown Logic
        window.toggleProfileDropdown = function() {
            const dropdown = document.getElementById('profileDropdownMenu');
            if (dropdown) dropdown.style.display = dropdown.style.display === 'none' || dropdown.style.display === '' ? 'block' : 'none';
        };

        document.addEventListener('click', function(event) {
            const container = document.getElementById('profileDropdownContainer');
            const dropdown = document.getElementById('profileDropdownMenu');
            if (container && dropdown && !container.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });
    </script>

    <!-- SweetAlert2 Engine -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .swal2-container.swal2-backdrop-show {
            background: rgba(15, 23, 42, 0.6) !important;
            backdrop-filter: blur(8px) !important;
        }

        .ultra-alert-popup {
            border-radius: 24px !important;
            padding: 2.5rem 2rem !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            background: #ffffff !important;
            font-family: inherit !important;
        }

        .ultra-alert-icon {
            width: 80px;
            height: 80px;
            background: #fef2f2;
            color: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            margin: 0 auto 1.5rem auto;
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            animation: pulse-danger 2s infinite;
        }
        @keyframes pulse-danger {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        .ultra-alert-title { font-size: 1.6rem; font-weight: 900; color: #0f172a; margin-bottom: 0.5rem; }
        .ultra-alert-text { font-size: 1.05rem; color: #64748b; font-weight: 500; margin-bottom: 2rem; line-height: 1.6; }

        .ultra-alert-actions { display: flex; gap: 16px; justify-content: center; margin-top: 10px; width: 100%; }
        
        .btn-ultra-confirm {
            background: linear-gradient(135deg, #ef4444, #dc2626) !important;
            color: white !important;
            border: none !important;
            padding: 14px 28px !important;
            border-radius: 14px !important;
            font-size: 1.05rem !important;
            font-weight: 800 !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            box-shadow: 0 8px 16px -4px rgba(239, 68, 68, 0.4) !important;
            transition: all 0.3s ease !important;
            cursor: pointer;
        }
        .btn-ultra-confirm:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 12px 20px -4px rgba(239, 68, 68, 0.5) !important;
        }

        .btn-ultra-cancel {
            background: #f1f5f9 !important;
            color: #475569 !important;
            border: none !important;
            padding: 14px 28px !important;
            border-radius: 14px !important;
            font-size: 1.05rem !important;
            font-weight: 800 !important;
            transition: all 0.3s ease !important;
            cursor: pointer;
        }
        .btn-ultra-cancel:hover {
            background: #e2e8f0 !important;
            color: #0f172a !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('form').forEach(form => {
                const onsubmitAttr = form.getAttribute('onsubmit');
                
                if (onsubmitAttr && onsubmitAttr.includes('confirm')) {
                    const match = onsubmitAttr.match(/confirm\(['"](.*?)['"]\)/);
                    const msg = match ? match[1] : 'هل أنت متأكد من إتمام هذا الإجراء؟ لا يمكن التراجع بعد ذلك.';
                    
                    form.removeAttribute('onsubmit');
                    
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        Swal.fire({
                            html: `
                                <div class="ultra-alert-icon">
                                    <i class="ph-duotone ph-warning-octagon"></i>
                                </div>
                                <div class="ultra-alert-title">${<?= json_encode($isAr ? 'تحذير أمني!' : 'Security Warning!') ?>}</div>
                                <div class="ultra-alert-text">${msg}</div>
                            `,
                            showConfirmButton: true,
                            showCancelButton: true,
                            confirmButtonText: '<i class="ph-bold ph-trash"></i> ' + <?= json_encode($isAr ? 'تأكيد الحذف' : 'Confirm Delete') ?>,
                            cancelButtonText: <?= json_encode($isAr ? 'تراجع وإلغاء' : 'Cancel') ?>,
                            buttonsStyling: false,
                            customClass: {
                                popup: 'ultra-alert-popup',
                                actions: 'ultra-alert-actions',
                                confirmButton: 'btn-ultra-confirm',
                                cancelButton: 'btn-ultra-cancel'
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    });
                }
            });
        });
    </script>

    <style>
        .crm-toolbar-auto {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .table-pagination-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 700;
            flex-wrap: wrap;
            gap: 12px;
        }
        .page-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #334155;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }
        .page-btn:hover:not(:disabled) {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
        }
        .page-btn.active {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
        }
        .page-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
    </style>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        initERPTablePagination(15);
    });

    function initERPTablePagination(pageSize = 15) {
        const table = document.querySelector('.crm-table') || document.querySelector('table');
        if (!table) return;

        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const allRows = Array.from(tbody.querySelectorAll('tr')).filter(tr => !tr.querySelector('.empty-state') && tr.querySelectorAll('td').length > 1);
        if (allRows.length === 0) return;

        const tableCard = table.closest('.table-card') || table.parentNode;
        const isRtl = document.documentElement.dir === 'rtl' || document.body.dir === 'rtl' || !!document.querySelector('[dir="rtl"]');

        let searchInput = document.querySelector('.search-input');
        if (!searchInput) {
            const autoToolbar = document.createElement('div');
            autoToolbar.className = 'crm-toolbar-auto';
            
            const placeholderText = isRtl ? 'البحث السريع والتصفية المباشرة...' : 'Quick search & live filter...';
            const paddingSide = isRtl ? 'right' : 'left';
            const iconPos = isRtl ? 'right: 14px;' : 'left: 14px;';

            autoToolbar.innerHTML = `
                <div style="flex-grow: 1; position: relative;">
                    <i class="ph-bold ph-magnifying-glass" style="position: absolute; top: 50%; transform: translateY(-50%); ${iconPos} color: #94a3b8; font-size: 1.2rem;"></i>
                    <input type="text" class="search-input" placeholder="${placeholderText}" style="width: 100%; padding: 10px 14px; padding-${paddingSide}: 40px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 0.9rem; outline: none; background: #f8fafc; color: #0f172a; transition: 0.2s;">
                </div>
            `;
            tableCard.parentNode.insertBefore(autoToolbar, tableCard);
            searchInput = autoToolbar.querySelector('.search-input');

            searchInput.addEventListener('focus', () => { searchInput.style.background = '#ffffff'; searchInput.style.borderColor = '#3b82f6'; });
            searchInput.addEventListener('blur', () => { searchInput.style.background = '#f8fafc'; searchInput.style.borderColor = '#e2e8f0'; });
        }

        let filteredRows = [...allRows];
        let currentPage = 1;

        let navDiv = document.createElement('div');
        navDiv.className = 'table-pagination-nav';
        tableCard.appendChild(navDiv);

        function render() {
            const totalRows = filteredRows.length;
            const totalPages = Math.ceil(totalRows / pageSize) || 1;
            if (currentPage > totalPages) currentPage = totalPages;

            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;

            allRows.forEach(row => row.style.display = 'none');
            filteredRows.slice(start, end).forEach(row => row.style.display = '');

            const fromCount = totalRows === 0 ? 0 : start + 1;
            const toCount = Math.min(end, totalRows);
            
            let infoText = isRtl 
                ? `عرض ${fromCount} - ${toCount} من أصل ${totalRows}` 
                : `Showing ${fromCount} to ${toCount} of ${totalRows} entries`;

            let btnsHtml = `<div style="display:flex; gap:6px; align-items:center;">`;
            btnsHtml += `<button type="button" class="page-btn prev-btn" ${currentPage === 1 ? 'disabled' : ''}><i class="ph-bold ${isRtl ? 'ph-caret-right' : 'ph-caret-left'}"></i></button>`;

            for (let i = 1; i <= totalPages; i++) {
                if (totalPages > 7 && Math.abs(i - currentPage) > 2 && i !== 1 && i !== totalPages) {
                    if (i === 2 || i === totalPages - 1) btnsHtml += `<span style="padding:0 4px; color:#94a3b8;">...</span>`;
                    continue;
                }
                btnsHtml += `<button type="button" class="page-btn num-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
            }

            btnsHtml += `<button type="button" class="page-btn next-btn" ${currentPage === totalPages ? 'disabled' : ''}><i class="ph-bold ${isRtl ? 'ph-caret-left' : 'ph-caret-right'}"></i></button>`;
            btnsHtml += `</div>`;

            navDiv.innerHTML = `<div>${infoText}</div>${btnsHtml}`;

            navDiv.querySelectorAll('.num-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    currentPage = parseInt(btn.dataset.page);
                    render();
                });
            });

            const prevBtn = navDiv.querySelector('.prev-btn');
            if (prevBtn) prevBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; render(); } });

            const nextBtn = navDiv.querySelector('.next-btn');
            if (nextBtn) nextBtn.addEventListener('click', () => { if (currentPage < totalPages) { currentPage++; render(); } });
        }

        searchInput.addEventListener('input', function (e) {
            const query = e.target.value.toLowerCase().trim();
            filteredRows = allRows.filter(row => row.innerText.toLowerCase().includes(query));
            currentPage = 1;
            render();
        });

        render();
    }
    </script>
</body>
</html>