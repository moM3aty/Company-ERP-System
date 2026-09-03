<?php
// Path: resources/views/partials/sidebar.php

use Core\Auth\AuthManager;

global $app;
if (class_exists('\Core\Auth\AuthManager')) {
    if (AuthManager::user() === null && $app) {
        AuthManager::init($app->get(PDO::class));
    }
}

$checkAccess = function($module, $resource, $action) {
    if (class_exists('\Core\Auth\AuthManager')) {
        return AuthManager::hasAccess($module, $resource, $action);
    }
    return true; 
};

$canViewAdmin      = $checkAccess('Administration', 'Users', 'view');
$canViewSales      = $checkAccess('Sales', 'Customers', 'view');
$canViewPurchasing = $checkAccess('Purchasing', 'Suppliers', 'view');
$canViewProjects   = $checkAccess('Projects', 'Projects', 'view');
$canViewInventory  = $checkAccess('Inventory', 'Products', 'view');
$canViewAccounting = $checkAccess('Accounting', 'Journal_Entries', 'view');

$isSuperAdmin    = isset(AuthManager::user()['is_system']) && AuthManager::user()['is_system'] ? true : false;
$canViewPricing  = $canViewSales || $isSuperAdmin;
$canViewTreasury = $canViewAccounting || $isSuperAdmin;
$canViewHR       = $isSuperAdmin || $checkAccess('HR', 'Employees', 'view'); 

$currentLocale = $_SESSION['locale'] ?? 'ar';
$isRtl = $currentLocale === 'ar';
$baseUrl = '/ERP';

$rawUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$cleanUri = '/' . trim(preg_replace('#^' . preg_quote($baseUrl, '#') . '#', '', $rawUri), '/');

$isActiveHub = function($modules) use ($cleanUri) {
    if (!is_array($modules)) $modules = [$modules];
    foreach ($modules as $module) {
        $modulePath = '/' . trim($module, '/');
        if ($cleanUri === $modulePath || str_starts_with($cleanUri, $modulePath . '/')) return true;
    }
    return false;
};

$isActiveLink = function($route) use ($cleanUri) {
    $routePath = '/' . trim($route, '/');
    return ($cleanUri === $routePath || str_starts_with($cleanUri, $routePath . '/'));
};

$db = $app ? $app->get(PDO::class) : null;
$companyLogo = '/assets/img/default-logo.png'; 
$companyName = 'NOUR TRUST';

if ($db) {
    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM sys_settings WHERE setting_key IN ('company_name', 'company_logo')");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['setting_key'] === 'company_name' && !empty($row['setting_value'])) $companyName = $row['setting_value'];
            if ($row['setting_key'] === 'company_logo' && !empty($row['setting_value'])) $companyLogo = $row['setting_value'];
        }
    } catch (Exception $e) {}
}

$t = [
    'ar' => [
        'system_tag' => 'نظام إدارة المؤسسات', 'op_systems' => 'الأنظمة التشغيلية', 'admin_fin_systems' => 'الأنظمة الإدارية والمالية',
        'dashboard' => 'لوحة القيادة', 'workspace' => 'مساحة العمل', 'approvals' => 'موافقاتي',
        'crm_sales' => 'المبيعات والعملاء', 'crm_dashboard' => 'شاشة المبيعات', 'leads' => 'العملاء المحتملين', 'customers' => 'سجل العملاء', 'quotations' => 'عروض الأسعار', 'sales_orders' => 'أوامر البيع', 'delivery_notes' => 'أذونات الصرف', 'sales_invoices' => 'فواتير المبيعات', 'sales_receipts' => 'سندات القبض', 'sales_returns' => 'مرتجعات المبيعات', 'price_lists' => 'قوائم الأسعار', 'sales_reps' => 'المناديب', 'customer_statements' => 'كشوف الحسابات', 'sales_contracts' => 'العقود',
        'purchasing' => 'المشتريات والموردين', 'pur_dashboard' => 'شاشة المشتريات', 'suppliers' => 'سجل الموردين', 'supplier_evaluations' => 'تقييم الموردين', 'supplier_price_lists' => 'قوائم الأسعار', 'pur_contracts' => 'العقود', 'pr' => 'طلبات الشراء', 'rfq' => 'عروض الأسعار', 'po' => 'أوامر الشراء', 'grn' => 'استلام البضاعة', 'landed_costs' => 'التكاليف الجمركية', 'pur_invoices' => 'فواتير المشتريات', 'pur_returns' => 'مرتجعات الموردين', 'supplier_statements' => 'كشوف الموردين',
        'projects' => 'المشاريع والمقاولات', 'projects_dashboard' => 'لوحة المشاريع', 'projects_list' => 'سجل المشاريع', 'milestones' => 'المراحل والمهام', 'project_invoices' => 'المستخلصات والفواتير', 'project_costs' => 'تكاليف ومصروفات الموقع', 'project_contracts' => 'عقود المشاريع',
        'inventory' => 'المخازن والمستودعات', 'inv_dashboard' => 'لوحة المخازن', 'products' => 'دليل الأصناف', 'categories' => 'فئات الأصناف', 'warehouses' => 'المستودعات', 'stock_transfers' => 'التحويلات المخزنية', 'stock_returns' => 'مرتجعات المخزون', 'stock_adjustments' => 'تسويات وجرد المخزون', 'stock_ledger' => 'حركات المخزون',
        'accounting' => 'الحسابات والمالية', 'acc_dashboard' => 'الشاشة المالية', 'chart_accounts' => 'دليل الحسابات', 'journal_entries' => 'قيود اليومية', 'cost_centers' => 'مراكز التكلفة', 'fixed_assets' => 'إدارة الأصول الثابتة والإهلاكات', 'budgets' => 'الموازنات التقديرية والانحرافات', 'bank_reconciliation' => 'التسويات والمطابقات البنكية', 'fiscal_periods' => 'الفترات المالية', 'taxes' => 'الضرائب', 'financial_reports' => 'القوائم والتقارير المالية', 'income_statement' => 'قائمة الدخل (P&L)', 'balance_sheet' => 'الميزانية العمومية', 'cash_flow' => 'التدفقات النقدية', 'vat_return' => 'الإقرار الضريبي', 'general_ledger' => 'دفتر الأستاذ', 'trial_balance' => 'ميزان المراجعة',
        'treasury' => 'الخزانة والبنوك', 'treasury_dashboard' => 'لوحة الخزانة', 'bank_accounts' => 'الحسابات البنكية', 'receipt_vouchers' => 'سندات القبض', 'payment_vouchers' => 'سندات الصرف', 'internal_transfers' => 'التحويلات الداخلية', 'petty_cash' => 'العهد المالية', 'cheques' => 'حركة الشيكات', 'cash_book' => 'دفتر الصندوق',
        'hr' => 'الموارد البشرية والرواتب', 'hr_dashboard' => 'شاشة الـ HR', 'departments' => 'الهيكل التنظيمي', 'designations' => 'الدرجات الوظيفية', 'employees' => 'دليل الموظفين', 'emp_contracts' => 'عقود الموظفين', 'attendance' => 'الحضور والانصراف', 'shifts' => 'الورديات', 'leaves' => 'الإجازات', 'payroll' => 'مسيرات الرواتب', 'salary_components' => 'مكونات الراتب', 'appraisals' => 'التقييمات', 'recruitment' => 'التوظيف', 'hr_documents' => 'وثائق الموظفين',
        'admin' => 'إدارة النظام والإعدادات', 'companies' => 'دليل الشركات', 'branches' => 'الفروع والمراكز', 'roles' => 'الأدوار والصلاحيات', 'users' => 'حسابات المستخدمين', 'settings' => 'الإعدادات العامة', 'audit_logs' => 'سجل حركات النظام', 'backups' => 'النسخ الاحتياطي والصيانة',
    ],
    'en' => [
        'system_tag' => 'Enterprise System', 'op_systems' => 'Operational Systems', 'admin_fin_systems' => 'Admin & Financial Systems',
        'dashboard' => 'Main Dashboard', 'workspace' => 'My Workspace', 'approvals' => 'My Approvals',
        'crm_sales' => 'Sales & CRM', 'crm_dashboard' => 'Sales Dashboard', 'leads' => 'Leads', 'customers' => 'Customers', 'quotations' => 'Quotations', 'sales_orders' => 'Sales Orders', 'delivery_notes' => 'Delivery Notes', 'sales_invoices' => 'Sales Invoices', 'sales_receipts' => 'Sales Receipts', 'sales_returns' => 'Sales Returns', 'price_lists' => 'Price Lists', 'sales_reps' => 'Sales Reps', 'customer_statements' => 'Customer Statements', 'sales_contracts' => 'Sales Contracts',
        'purchasing' => 'Purchasing', 'pur_dashboard' => 'Purchasing Dashboard', 'suppliers' => 'Suppliers', 'supplier_evaluations' => 'Supplier Evaluations', 'supplier_price_lists' => 'Supplier Catalogs', 'pur_contracts' => 'Purchase Contracts', 'pr' => 'Purchase Reqs', 'rfq' => 'Requests for Quote', 'po' => 'Purchase Orders', 'grn' => 'Goods Receipts', 'landed_costs' => 'Landed Costs', 'pur_invoices' => 'Purchase Invoices', 'pur_returns' => 'Purchase Returns', 'supplier_statements' => 'Supplier Statements',
        'projects' => 'Projects & Contracting', 'projects_dashboard' => 'Projects Dashboard', 'projects_list' => 'Projects Directory', 'milestones' => 'Milestones & Tasks', 'project_invoices' => 'Progress Invoices', 'project_costs' => 'Project Costing', 'project_contracts' => 'Project Contracts',
        'inventory' => 'Inventory', 'inv_dashboard' => 'Inventory Dashboard', 'products' => 'Products', 'categories' => 'Categories', 'warehouses' => 'Warehouses', 'stock_transfers' => 'Stock Transfers', 'stock_returns' => 'Stock Returns', 'stock_adjustments' => 'Stock Adjustments & Count', 'stock_ledger' => 'Stock Ledger',
        'accounting' => 'Accounting', 'acc_dashboard' => 'Acc. Dashboard', 'chart_accounts' => 'Chart of Accounts', 'journal_entries' => 'Journal Entries', 'cost_centers' => 'Cost Centers', 'fixed_assets' => 'Fixed Assets & Depreciation', 'budgets' => 'Budgets & Variances', 'bank_reconciliation' => 'Bank Reconciliation', 'fiscal_periods' => 'Fiscal Periods', 'taxes' => 'Taxes', 'financial_reports' => 'Financial Reports & Statements', 'income_statement' => 'Income Statement (P&L)', 'balance_sheet' => 'Balance Sheet', 'cash_flow' => 'Cash Flow Statement', 'vat_return' => 'VAT Return', 'general_ledger' => 'General Ledger', 'trial_balance' => 'Trial Balance',
        'treasury' => 'Treasury & Banking', 'treasury_dashboard' => 'Treasury Dashboard', 'bank_accounts' => 'Bank Accounts', 'receipt_vouchers' => 'Receipt Vouchers', 'payment_vouchers' => 'Payment Vouchers', 'internal_transfers' => 'Internal Transfers', 'petty_cash' => 'Petty Cash', 'cheques' => 'Cheque Management', 'cash_book' => 'Cash Book Report',
        'hr' => 'HR & Payroll', 'hr_dashboard' => 'HR Dashboard', 'departments' => 'Departments', 'designations' => 'Designations', 'employees' => 'Employees Directory', 'emp_contracts' => 'Employment Contracts', 'attendance' => 'Attendance Logs', 'shifts' => 'Shifts & Schedules', 'leaves' => 'Leave Requests', 'payroll' => 'Payroll', 'salary_components' => 'Salary Structure', 'appraisals' => 'Performance Appraisals', 'recruitment' => 'Recruitment', 'hr_documents' => 'Employee Documents',
        'admin' => 'Administration & Settings', 'companies' => 'Companies', 'branches' => 'Branches', 'roles' => 'Roles & Permissions', 'users' => 'Users Directory', 'settings' => 'System Settings', 'audit_logs' => 'Audit & Security Logs', 'backups' => 'Backups & Maintenance',
    ]
][$currentLocale];

?>

<!-- Overlay Mobile Backdrop -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebarMobile()"></div>

<aside class="nt-sidebar" id="ntSidebar">
    <!-- Branding Header -->
    <div class="sidebar-branding">
        <div class="logo-box">
            <?php if($companyLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($companyLogo) ?>" alt="Logo">
            <?php else: ?>
                <i class="ph-bold ph-buildings"></i>
            <?php endif; ?>
        </div>
        <div class="branding-info">
            <span class="company-name"><?= htmlspecialchars($companyName) ?></span>
            <span class="system-tag"><?= $t['system_tag'] ?></span>
        </div>
        <button class="mobile-close-btn" onclick="closeSidebarMobile()">
            <i class="ph-bold ph-x"></i>
        </button>
    </div>

    <!-- Navigation Area -->
    <nav class="sidebar-nav">
        <style>
            a { text-decoration: none; }

            .nt-sidebar {
                width: 280px;
                background-color: #0b1120;
                color: #f8fafc;
                display: flex;
                flex-direction: column;
                height: 100vh;
                height: 100dvh;
                position: sticky;
                top: 0;
                user-select: none;
                box-shadow: 4px 0 24px rgba(0,0,0,0.2);
                z-index: 1050;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            [dir="rtl"] .nt-sidebar { box-shadow: -4px 0 24px rgba(0,0,0,0.2); }

            .sidebar-branding {
                height: 76px;
                display: flex;
                align-items: center;
                padding: 0 20px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
                background-color: rgba(11, 17, 32, 0.95);
                backdrop-filter: blur(10px);
                z-index: 10;
                gap: 14px;
            }

            .logo-box {
                width: 40px; height: 40px;
                background: #ffffff; border-radius: 12px;
                display: flex; align-items: center; justify-content: center;
                overflow: hidden; border: 2px solid #3b82f6; flex-shrink: 0;
                box-shadow: 0 0 14px rgba(59, 130, 246, 0.3);
            }
            .logo-box img { max-width: 100%; max-height: 100%; border-radius: 8px;}
            .logo-box i { color: #2563eb; font-size: 1.4rem; }

            .branding-info { display: flex; flex-direction: column; overflow: hidden; flex: 1; transition: opacity 0.2s ease; }
            .company-name { font-weight: 800; font-size: 1.05rem; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; letter-spacing: 0.3px;}
            .system-tag { font-size: 0.65rem; color: #94a3b8; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; margin-top: 2px;}

            .mobile-close-btn {
                display: none;
                background: transparent;
                border: none;
                color: #94a3b8;
                font-size: 1.4rem;
                cursor: pointer;
                padding: 4px;
                border-radius: 8px;
            }
            .mobile-close-btn:hover { color: #ffffff; background: rgba(255,255,255,0.1); }

            .sidebar-nav {
                flex: 1;
                padding: 16px 14px;
                overflow-y: auto;
                overflow-x: hidden;
            }
            .sidebar-nav::-webkit-scrollbar { width: 5px; }
            .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }
            .sidebar-nav::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.25); }

            .nav-hub {
                display: flex; align-items: center; justify-content: space-between;
                padding: 12px 14px; border-radius: 10px;
                color: #cbd5e1; font-size: 0.9rem; font-weight: 700;
                transition: all 0.2s ease;
                margin-top: 6px; cursor: pointer;
                background: transparent; border: 1px solid transparent;
            }
            .nav-hub:hover { background: rgba(255, 255, 255, 0.05); color: #ffffff; }
            
            .nav-hub.active {
                background: rgba(59, 130, 246, 0.12);
                color: #ffffff;
            }
            [dir="rtl"] .nav-hub.active { border-right: 3px solid #3b82f6; }
            [dir="ltr"] .nav-hub.active { border-left: 3px solid #3b82f6; }

            .nav-hub .hub-icon { font-size: 1.3rem; transition: 0.3s; flex-shrink: 0; }
            .nav-hub.active .hub-icon { color: #60a5fa !important; transform: scale(1.1); }

            .nav-hub .hub-title { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

            .nav-hub .ph-caret-right { font-size: 0.75rem; color: #64748b; transition: transform 0.3s ease; flex-shrink: 0; }
            .nav-hub.active .ph-caret-right { color: #94a3b8; }
            
            [dir="rtl"] .ph-caret-right { transform: rotate(180deg); }
            [dir="rtl"] .nav-hub.expanded .ph-caret-right { transform: rotate(-90deg); color: #60a5fa; }
            [dir="ltr"] .nav-hub.expanded .ph-caret-right { transform: rotate(90deg); color: #60a5fa; }

            .sub-menu-wrapper {
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .sub-menu-list {
                display: flex; flex-direction: column; gap: 4px;
                padding: 6px 0 10px 0; margin-top: 4px;
            }
            [dir="rtl"] .sub-menu-list { padding-right: 14px; border-right: 1px solid rgba(255, 255, 255, 0.08); margin-right: 20px; }
            [dir="ltr"] .sub-menu-list { padding-left: 14px; border-left: 1px solid rgba(255, 255, 255, 0.08); margin-left: 20px; }

            .nav-link {
                display: flex; align-items: center; gap: 10px;
                padding: 8px 12px; border-radius: 8px;
                color: #94a3b8; font-size: 0.85rem; font-weight: 600;
                transition: all 0.2s ease; position: relative; white-space: nowrap;
            }
            .nav-link:hover { color: #ffffff; background: rgba(255, 255, 255, 0.04); }
            [dir="rtl"] .nav-link:hover { transform: translateX(-4px); }
            [dir="ltr"] .nav-link:hover { transform: translateX(4px); }

            .nav-link.active {
                color: #ffffff !important;
                background: rgba(59, 130, 246, 0.2) !important;
                font-weight: 800;
            }
            
            .nav-link::before {
                content: ''; position: absolute; width: 6px; height: 6px; border-radius: 50%;
                background: transparent; transition: 0.3s; top: 50%; transform: translateY(-50%);
            }
            .nav-link:hover::before { background: #475569; }
            .nav-link.active::before { background: #60a5fa; box-shadow: 0 0 8px rgba(96, 165, 250, 0.8); }
            
            [dir="rtl"] .nav-link::before { right: -18px; }
            [dir="ltr"] .nav-link::before { left: -18px; }

            .section-divider {
                margin: 24px 14px 8px 14px; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 12px;
                font-size: 0.68rem; color: #64748b; text-transform: uppercase; font-weight: 800; letter-spacing: 1px;
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            }

            .nt-sidebar.collapsed,
            body.sidebar-collapsed .nt-sidebar {
                width: 80px !important;
            }

            .nt-sidebar.collapsed .branding-info,
            body.sidebar-collapsed .nt-sidebar .branding-info,
            .nt-sidebar.collapsed .nav-hub .hub-title,
            body.sidebar-collapsed .nt-sidebar .nav-hub .hub-title,
            .nt-sidebar.collapsed .nav-hub .ph-caret-right,
            body.sidebar-collapsed .nt-sidebar .nav-hub .ph-caret-right,
            .nt-sidebar.collapsed .sub-menu-wrapper,
            body.sidebar-collapsed .nt-sidebar .sub-menu-wrapper,
            .nt-sidebar.collapsed .nav-link span,
            body.sidebar-collapsed .nt-sidebar .nav-link span {
                display: none !important;
            }

            .nt-sidebar.collapsed .sidebar-branding,
            body.sidebar-collapsed .nt-sidebar .sidebar-branding {
                padding: 0;
                justify-content: center;
            }

            .nt-sidebar.collapsed .nav-hub,
            body.sidebar-collapsed .nt-sidebar .nav-hub {
                justify-content: center;
                padding: 12px 0;
            }

            .nt-sidebar.collapsed .nav-hub > div,
            body.sidebar-collapsed .nt-sidebar .nav-hub > div {
                justify-content: center;
                gap: 0 !important;
            }

            .nt-sidebar.collapsed .nav-link,
            body.sidebar-collapsed .nt-sidebar .nav-link {
                justify-content: center;
                padding: 10px 0;
            }

            .nt-sidebar.collapsed .section-divider,
            body.sidebar-collapsed .nt-sidebar .section-divider {
                font-size: 0;
                height: 1px;
                padding: 0;
                margin: 16px 12px;
                background: rgba(255, 255, 255, 0.08);
                border: none;
            }

            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(15, 23, 42, 0.65);
                backdrop-filter: blur(4px);
                z-index: 1040;
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            @media (max-width: 1024px) {
                .mobile-close-btn { display: flex; align-items: center; justify-content: center; }

                .nt-sidebar {
                    position: fixed;
                    top: 0;
                    bottom: 0;
                    box-shadow: 0 0 35px rgba(0,0,0,0.5);
                }

                [dir="rtl"] .nt-sidebar {
                    right: 0;
                    transform: translateX(100%);
                }

                [dir="ltr"] .nt-sidebar {
                    left: 0;
                    transform: translateX(-100%);
                }

                .nt-sidebar.mobile-open {
                    transform: translateX(0) !important;
                }

                .sidebar-overlay {
                    display: block;
                    pointer-events: none;
                }

                .sidebar-overlay.mobile-open {
                    opacity: 1;
                    pointer-events: auto;
                }
            }
        </style>

        <!-- الرئيسية -->
        <a href="<?= $baseUrl ?>/dashboard" class="nav-hub <?= $isActiveLink('/dashboard') ? 'active' : '' ?>" style="background: <?= $isActiveLink('/dashboard') ? 'linear-gradient(135deg, #2563eb, #1d4ed8)' : 'rgba(255,255,255,0.03)' ?>; color: white; border-radius: 12px; margin-bottom: 16px; border: none;" title="<?= $t['dashboard'] ?>">
            <div style="display: flex; align-items: center; gap: 14px;">
                <i class="ph-duotone ph-squares-four hub-icon"></i> 
                <span class="hub-title"><?= $t['dashboard'] ?></span>
            </div>
        </a>
        
        <div style="margin-bottom: 12px;">
            <a href="<?= $baseUrl ?>/workspace/approvals" class="nav-link <?= $isActiveLink('/workspace/approvals') ? 'active' : '' ?>" title="<?= $t['approvals'] ?>">
                <i class="ph-duotone ph-check-square-offset" style="font-size: 1.1rem; color: #a855f7;"></i>
                <span><?= $t['approvals'] ?></span>
            </a>
        </div>

        <div class="section-divider"><?= $t['op_systems'] ?></div>

        <!-- المبيعات والعملاء -->
        <?php if($canViewSales || $isSuperAdmin): $salesActive = $isActiveHub(['/sales', '/crm']); ?>
            <div class="nav-hub <?= $salesActive ? 'active expanded' : '' ?>" onclick="toggleAccordion(this)" title="<?= $t['crm_sales'] ?>">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <i class="ph-duotone ph-presentation-chart hub-icon" style="color: #38bdf8;"></i> 
                    <span class="hub-title"><?= $t['crm_sales'] ?></span>
                </div>
                <i class="ph-bold ph-caret-right"></i>
            </div>
            <div class="sub-menu-wrapper" style="<?= $salesActive ? 'max-height: 1000px;' : '' ?>">
                <div class="sub-menu-list">
                    <a href="<?= $baseUrl ?>/sales/dashboard" class="nav-link <?= $isActiveLink('/sales/dashboard') ? 'active' : '' ?>" style="color: #38bdf8;"><span><?= $t['crm_dashboard'] ?></span></a>
                    <?php if($checkAccess('Sales', 'Customers', 'view')): ?>
                        <a href="<?= $baseUrl ?>/crm/leads" class="nav-link <?= $isActiveLink('/crm/leads') ? 'active' : '' ?>"><span><?= $t['leads'] ?></span></a>
                        <a href="<?= $baseUrl ?>/sales/customers" class="nav-link <?= $isActiveLink('/sales/customers') ? 'active' : '' ?>"><span><?= $t['customers'] ?></span></a>
                        <a href="<?= $baseUrl ?>/sales/quotations" class="nav-link <?= $isActiveLink('/sales/quotations') ? 'active' : '' ?>"><span><?= $t['quotations'] ?></span></a>
                    <?php endif; ?>
                    <?php if($checkAccess('Sales', 'Orders', 'view')): ?>
                        <a href="<?= $baseUrl ?>/sales/orders" class="nav-link <?= $isActiveLink('/sales/orders') ? 'active' : '' ?>"><span><?= $t['sales_orders'] ?></span></a>
                        <a href="<?= $baseUrl ?>/sales/invoices" class="nav-link <?= $isActiveLink('/sales/invoices') ? 'active' : '' ?>"><span><?= $t['sales_invoices'] ?></span></a>
                        <a href="<?= $baseUrl ?>/sales/receipts" class="nav-link <?= $isActiveLink('/sales/receipts') ? 'active' : '' ?>"><span><?= $t['sales_receipts'] ?></span></a>
                        <a href="<?= $baseUrl ?>/sales/returns" class="nav-link <?= $isActiveLink('/sales/returns') ? 'active' : '' ?>"><span><?= $t['sales_returns'] ?></span></a>
                        <a href="<?= $baseUrl ?>/sales/price-lists" class="nav-link <?= $isActiveLink('/sales/price-lists') ? 'active' : '' ?>"><span><?= $t['price_lists'] ?></span></a>
                        <a href="<?= $baseUrl ?>/sales/representatives" class="nav-link <?= $isActiveLink('/sales/representatives') ? 'active' : '' ?>"><span><?= $t['sales_reps'] ?></span></a>
                        <a href="<?= $baseUrl ?>/sales/statements" class="nav-link <?= $isActiveLink('/sales/statements') ? 'active' : '' ?>"><span><?= $t['customer_statements'] ?></span></a>
                        <a href="<?= $baseUrl ?>/sales/contracts" class="nav-link <?= $isActiveLink('/sales/contracts') ? 'active' : '' ?>"><span><?= $t['sales_contracts'] ?></span></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- المشتريات والموردين -->
        <?php if($canViewPurchasing || $isSuperAdmin): $purActive = $isActiveHub('/purchasing'); ?>
            <div class="nav-hub <?= $purActive ? 'active expanded' : '' ?>" onclick="toggleAccordion(this)" title="<?= $t['purchasing'] ?>">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <i class="ph-duotone ph-shopping-bag hub-icon" style="color: #f472b6;"></i> 
                    <span class="hub-title"><?= $t['purchasing'] ?></span>
                </div>
                <i class="ph-bold ph-caret-right"></i>
            </div>
            <div class="sub-menu-wrapper" style="<?= $purActive ? 'max-height: 1000px;' : '' ?>">
                <div class="sub-menu-list">
                    <a href="<?= $baseUrl ?>/purchasing/dashboard" class="nav-link <?= $isActiveLink('/purchasing/dashboard') ? 'active' : '' ?>" style="color: #f472b6;"><span><?= $t['pur_dashboard'] ?></span></a>
                    <a href="<?= $baseUrl ?>/purchasing/suppliers" class="nav-link <?= $isActiveLink('/purchasing/suppliers') ? 'active' : '' ?>"><span><?= $t['suppliers'] ?></span></a>
                    <a href="<?= $baseUrl ?>/purchasing/supplier-evaluations" class="nav-link <?= $isActiveLink('/purchasing/supplier-evaluations') ? 'active' : '' ?>"><span><?= $t['supplier_evaluations'] ?></span></a>
                    <a href="<?= $baseUrl ?>/purchasing/price-lists" class="nav-link <?= $isActiveLink('/purchasing/price-lists') ? 'active' : '' ?>"><span><?= $t['supplier_price_lists'] ?></span></a>
                    <a href="<?= $baseUrl ?>/purchasing/contracts" class="nav-link <?= $isActiveLink('/purchasing/contracts') ? 'active' : '' ?>"><span><?= $t['pur_contracts'] ?></span></a>
                    <a href="<?= $baseUrl ?>/purchasing/requisitions" class="nav-link <?= $isActiveLink('/purchasing/requisitions') ? 'active' : '' ?>"><span><?= $t['pr'] ?></span></a>
                    <a href="<?= $baseUrl ?>/purchasing/rfq" class="nav-link <?= $isActiveLink('/purchasing/rfq') ? 'active' : '' ?>"><span><?= $t['rfq'] ?></span></a>
                    <a href="<?= $baseUrl ?>/purchasing/orders" class="nav-link <?= $isActiveLink('/purchasing/orders') ? 'active' : '' ?>"><span><?= $t['po'] ?></span></a>
                    <a href="<?= $baseUrl ?>/purchasing/receipts" class="nav-link <?= $isActiveLink('/purchasing/receipts') ? 'active' : '' ?>"><span><?= $t['grn'] ?></span></a>
                    <a href="<?= $baseUrl ?>/purchasing/landed-costs" class="nav-link <?= $isActiveLink('/purchasing/landed-costs') ? 'active' : '' ?>"><span><?= $t['landed_costs'] ?></span></a>
                    <a href="<?= $baseUrl ?>/purchasing/invoices" class="nav-link <?= $isActiveLink('/purchasing/invoices') ? 'active' : '' ?>"><span><?= $t['pur_invoices'] ?></span></a>
                    <a href="<?= $baseUrl ?>/purchasing/returns" class="nav-link <?= $isActiveLink('/purchasing/returns') ? 'active' : '' ?>"><span><?= $t['pur_returns'] ?></span></a>
                    <a href="<?= $baseUrl ?>/purchasing/statements" class="nav-link <?= $isActiveLink('/purchasing/statements') ? 'active' : '' ?>"><span><?= $t['supplier_statements'] ?></span></a>
                </div>
            </div>
        <?php endif; ?>

        <!-- المشاريع والمقاولات -->
        <?php if($canViewProjects || $isSuperAdmin): $prjActive = $isActiveHub('/projects'); ?>
            <div class="nav-hub <?= $prjActive ? 'active expanded' : '' ?>" onclick="toggleAccordion(this)" title="<?= $t['projects'] ?>">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <i class="ph-duotone ph-kanban hub-icon" style="color: #f97316;"></i> 
                    <span class="hub-title"><?= $t['projects'] ?></span>
                </div>
                <i class="ph-bold ph-caret-right"></i>
            </div>
            <div class="sub-menu-wrapper" style="<?= $prjActive ? 'max-height: 1000px;' : '' ?>">
                <div class="sub-menu-list">
                    <a href="<?= $baseUrl ?>/projects/dashboard" class="nav-link <?= $isActiveLink('/projects/dashboard') ? 'active' : '' ?>" style="color: #f97316;"><span><?= $t['projects_dashboard'] ?></span></a>
                    <a href="<?= $baseUrl ?>/projects/list" class="nav-link <?= $isActiveLink('/projects/list') ? 'active' : '' ?>"><span><?= $t['projects_list'] ?></span></a>
                    <a href="<?= $baseUrl ?>/projects/milestones" class="nav-link <?= $isActiveLink('/projects/milestones') ? 'active' : '' ?>"><span><?= $t['milestones'] ?></span></a>
                    <a href="<?= $baseUrl ?>/projects/invoices" class="nav-link <?= $isActiveLink('/projects/invoices') ? 'active' : '' ?>"><span><?= $t['project_invoices'] ?></span></a>
                    <a href="<?= $baseUrl ?>/projects/costs" class="nav-link <?= $isActiveLink('/projects/costs') ? 'active' : '' ?>"><span><?= $t['project_costs'] ?></span></a>
                    <a href="<?= $baseUrl ?>/projects/contracts" class="nav-link <?= $isActiveLink('/projects/contracts') ? 'active' : '' ?>"><span><?= $t['project_contracts'] ?></span></a>
                </div>
            </div>
        <?php endif; ?>

        <!-- المخازن والمستودعات -->
        <?php if($canViewInventory || $isSuperAdmin): $invActive = $isActiveHub('/inventory'); ?>
            <div class="nav-hub <?= $invActive ? 'active expanded' : '' ?>" onclick="toggleAccordion(this)" title="<?= $t['inventory'] ?>">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <i class="ph-duotone ph-warehouse hub-icon" style="color: #fbbf24;"></i> 
                    <span class="hub-title"><?= $t['inventory'] ?></span>
                </div>
                <i class="ph-bold ph-caret-right"></i>
            </div>
            <div class="sub-menu-wrapper" style="<?= $invActive ? 'max-height: 1000px;' : '' ?>">
                <div class="sub-menu-list">
                    <a href="<?= $baseUrl ?>/inventory/dashboard" class="nav-link <?= $isActiveLink('/inventory/dashboard') ? 'active' : '' ?>" style="color: #fbbf24;"><span><?= $t['inv_dashboard'] ?></span></a>
                    <a href="<?= $baseUrl ?>/inventory/products" class="nav-link <?= $isActiveLink('/inventory/products') ? 'active' : '' ?>"><span><?= $t['products'] ?></span></a>
                    <a href="<?= $baseUrl ?>/inventory/categories" class="nav-link <?= $isActiveLink('/inventory/categories') ? 'active' : '' ?>"><span><?= $t['categories'] ?></span></a>
                    <a href="<?= $baseUrl ?>/inventory/warehouses" class="nav-link <?= $isActiveLink('/inventory/warehouses') ? 'active' : '' ?>"><span><?= $t['warehouses'] ?></span></a>
                    <a href="<?= $baseUrl ?>/inventory/stock/transfers" class="nav-link <?= $isActiveLink('/inventory/stock/transfers') ? 'active' : '' ?>"><span><?= $t['stock_transfers'] ?></span></a>
                    <a href="<?= $baseUrl ?>/inventory/delivery-notes" class="nav-link <?= $isActiveLink('/inventory/delivery-notes') ? 'active' : '' ?>"><span><?= $t['delivery_notes'] ?></span></a>
                    <a href="<?= $baseUrl ?>/inventory/returns" class="nav-link <?= $isActiveLink('/inventory/returns') ? 'active' : '' ?>"><span><?= $t['stock_returns'] ?></span></a>
                    <a href="<?= $baseUrl ?>/inventory/adjustments" class="nav-link <?= $isActiveLink('/inventory/adjustments') ? 'active' : '' ?>"><span><?= $t['stock_adjustments'] ?></span></a>
                    <a href="<?= $baseUrl ?>/inventory/stock/ledger" class="nav-link <?= $isActiveLink('/inventory/stock/ledger') ? 'active' : '' ?>"><span><?= $t['stock_ledger'] ?></span></a>
                </div>
            </div>
        <?php endif; ?>

        <div class="section-divider"><?= $t['admin_fin_systems'] ?></div>

        <!-- الحسابات والمالية -->
        <?php if($canViewAccounting || $isSuperAdmin): $accActive = $isActiveHub('/accounting'); ?>
            <div class="nav-hub <?= $accActive ? 'active expanded' : '' ?>" onclick="toggleAccordion(this)" title="<?= $t['accounting'] ?>">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <i class="ph-duotone ph-chart-line-up hub-icon" style="color: #34d399;"></i> 
                    <span class="hub-title"><?= $t['accounting'] ?></span>
                </div>
                <i class="ph-bold ph-caret-right"></i>
            </div>
            <div class="sub-menu-wrapper" style="<?= $accActive ? 'max-height: 1000px;' : '' ?>">
                <div class="sub-menu-list">
                    <a href="<?= $baseUrl ?>/accounting/dashboard" class="nav-link <?= $isActiveLink('/accounting/dashboard') ? 'active' : '' ?>" style="color: #34d399;"><span><?= $t['acc_dashboard'] ?></span></a>
                    <a href="<?= $baseUrl ?>/accounting/chart-of-accounts" class="nav-link <?= $isActiveLink('/accounting/chart-of-accounts') ? 'active' : '' ?>"><span><?= $t['chart_accounts'] ?></span></a>
                    <a href="<?= $baseUrl ?>/accounting/journal-entries" class="nav-link <?= $isActiveLink('/accounting/journal-entries') ? 'active' : '' ?>"><span><?= $t['journal_entries'] ?></span></a>
                    <a href="<?= $baseUrl ?>/accounting/cost-centers" class="nav-link <?= $isActiveLink('/accounting/cost-centers') ? 'active' : '' ?>"><span><?= $t['cost_centers'] ?></span></a>
                    <a href="<?= $baseUrl ?>/accounting/fixed-assets" class="nav-link <?= $isActiveLink('/accounting/fixed-assets') ? 'active' : '' ?>"><span><?= $t['fixed_assets'] ?></span></a>
                    <a href="<?= $baseUrl ?>/accounting/budgets" class="nav-link <?= $isActiveLink('/accounting/budgets') ? 'active' : '' ?>"><span><?= $t['budgets'] ?></span></a>
                    <a href="<?= $baseUrl ?>/accounting/bank-reconciliation" class="nav-link <?= $isActiveLink('/accounting/bank-reconciliation') ? 'active' : '' ?>"><span><?= $t['bank_reconciliation'] ?></span></a>
                    <a href="<?= $baseUrl ?>/accounting/fiscal-periods" class="nav-link <?= $isActiveLink('/accounting/fiscal-periods') ? 'active' : '' ?>"><span><?= $t['fiscal_periods'] ?></span></a>
                    <a href="<?= $baseUrl ?>/accounting/taxes" class="nav-link <?= $isActiveLink('/accounting/taxes') ? 'active' : '' ?>"><span><?= $t['taxes'] ?></span></a>
                    
                    <div style="font-size: 0.7rem; color: #64748b; font-weight: 800; margin: 10px 0 4px 0;"><?= $t['financial_reports'] ?></div>
                    <a href="<?= $baseUrl ?>/accounting/reports/income-statement" class="nav-link <?= $isActiveLink('/accounting/reports/income-statement') ? 'active' : '' ?>"><span><?= $t['income_statement'] ?></span></a>
                    <a href="<?= $baseUrl ?>/accounting/reports/balance-sheet" class="nav-link <?= $isActiveLink('/accounting/reports/balance-sheet') ? 'active' : '' ?>"><span><?= $t['balance_sheet'] ?></span></a>
                    <a href="<?= $baseUrl ?>/accounting/reports/cash-flow" class="nav-link <?= $isActiveLink('/accounting/reports/cash-flow') ? 'active' : '' ?>"><span><?= $t['cash_flow'] ?></span></a>
                    <a href="<?= $baseUrl ?>/accounting/reports/vat-return" class="nav-link <?= $isActiveLink('/accounting/reports/vat-return') ? 'active' : '' ?>"><span><?= $t['vat_return'] ?></span></a>
                    <a href="<?= $baseUrl ?>/accounting/reports/ledger" class="nav-link <?= $isActiveLink('/accounting/reports/ledger') ? 'active' : '' ?>"><span><?= $t['general_ledger'] ?></span></a>
                    <a href="<?= $baseUrl ?>/accounting/reports/trial-balance" class="nav-link <?= $isActiveLink('/accounting/reports/trial-balance') ? 'active' : '' ?>"><span><?= $t['trial_balance'] ?></span></a>
                </div>
            </div>
        <?php endif; ?>

        <!-- الخزانة والبنوك -->
        <?php if($canViewTreasury): $treActive = $isActiveHub('/treasury'); ?>
            <div class="nav-hub <?= $treActive ? 'active expanded' : '' ?>" onclick="toggleAccordion(this)" title="<?= $t['treasury'] ?>">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <i class="ph-duotone ph-vault hub-icon" style="color: #a78bfa;"></i> 
                    <span class="hub-title"><?= $t['treasury'] ?></span>
                </div>
                <i class="ph-bold ph-caret-right"></i>
            </div>
            <div class="sub-menu-wrapper" style="<?= $treActive ? 'max-height: 1000px;' : '' ?>">
                <div class="sub-menu-list">
                    <a href="<?= $baseUrl ?>/treasury/dashboard" class="nav-link <?= $isActiveLink('/treasury/dashboard') ? 'active' : '' ?>" style="color: #a78bfa;"><span><?= $t['treasury_dashboard'] ?></span></a>
                    <a href="<?= $baseUrl ?>/treasury/accounts" class="nav-link <?= $isActiveLink('/treasury/accounts') ? 'active' : '' ?>"><span><?= $t['bank_accounts'] ?></span></a>
                    <a href="<?= $baseUrl ?>/treasury/receipts" class="nav-link <?= $isActiveLink('/treasury/receipts') ? 'active' : '' ?>"><span><?= $t['receipt_vouchers'] ?></span></a>
                    <a href="<?= $baseUrl ?>/treasury/payments" class="nav-link <?= $isActiveLink('/treasury/payments') ? 'active' : '' ?>"><span><?= $t['payment_vouchers'] ?></span></a>
                    <a href="<?= $baseUrl ?>/treasury/transfers" class="nav-link <?= $isActiveLink('/treasury/transfers') ? 'active' : '' ?>"><span><?= $t['internal_transfers'] ?></span></a>
                    <a href="<?= $baseUrl ?>/treasury/petty-cash" class="nav-link <?= $isActiveLink('/treasury/petty-cash') ? 'active' : '' ?>"><span><?= $t['petty_cash'] ?></span></a>
                    <a href="<?= $baseUrl ?>/treasury/cheques" class="nav-link <?= $isActiveLink('/treasury/cheques') ? 'active' : '' ?>"><span><?= $t['cheques'] ?></span></a>
                    <a href="<?= $baseUrl ?>/treasury/reports/cash-book" class="nav-link <?= $isActiveLink('/treasury/reports/cash-book') ? 'active' : '' ?>"><span><?= $t['cash_book'] ?></span></a>
                </div>
            </div>
        <?php endif; ?>

        <!-- الموارد البشرية والرواتب -->
        <?php if($canViewHR): $hrActive = $isActiveHub('/hr'); ?>
            <div class="nav-hub <?= $hrActive ? 'active expanded' : '' ?>" onclick="toggleAccordion(this)" title="<?= $t['hr'] ?>">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <i class="ph-duotone ph-users-three hub-icon" style="color: #818cf8;"></i> 
                    <span class="hub-title"><?= $t['hr'] ?></span>
                </div>
                <i class="ph-bold ph-caret-right"></i>
            </div>
            <div class="sub-menu-wrapper" style="<?= $hrActive ? 'max-height: 1000px;' : '' ?>">
                <div class="sub-menu-list">
                    <a href="<?= $baseUrl ?>/hr/dashboard" class="nav-link <?= $isActiveLink('/hr/dashboard') ? 'active' : '' ?>" style="color: #818cf8;"><span><?= $t['hr_dashboard'] ?></span></a>
                    <a href="<?= $baseUrl ?>/hr/departments" class="nav-link <?= $isActiveLink('/hr/departments') ? 'active' : '' ?>"><span><?= $t['departments'] ?></span></a>
                    <a href="<?= $baseUrl ?>/hr/designations" class="nav-link <?= $isActiveLink('/hr/designations') ? 'active' : '' ?>"><span><?= $t['designations'] ?></span></a>
                    <a href="<?= $baseUrl ?>/hr/employees" class="nav-link <?= $isActiveLink('/hr/employees') ? 'active' : '' ?>"><span><?= $t['employees'] ?></span></a>
                    <a href="<?= $baseUrl ?>/hr/contracts" class="nav-link <?= $isActiveLink('/hr/contracts') ? 'active' : '' ?>"><span><?= $t['emp_contracts'] ?></span></a>
                    <a href="<?= $baseUrl ?>/hr/attendance" class="nav-link <?= $isActiveLink('/hr/attendance') ? 'active' : '' ?>"><span><?= $t['attendance'] ?></span></a>
                    <a href="<?= $baseUrl ?>/hr/shifts" class="nav-link <?= $isActiveLink('/hr/shifts') ? 'active' : '' ?>"><span><?= $t['shifts'] ?></span></a>
                    <a href="<?= $baseUrl ?>/hr/leaves" class="nav-link <?= $isActiveLink('/hr/leaves') ? 'active' : '' ?>"><span><?= $t['leaves'] ?></span></a>
                    <a href="<?= $baseUrl ?>/hr/payroll" class="nav-link <?= $isActiveLink('/hr/payroll') ? 'active' : '' ?>"><span><?= $t['payroll'] ?></span></a>
                    <a href="<?= $baseUrl ?>/hr/salary-components" class="nav-link <?= $isActiveLink('/hr/salary-components') ? 'active' : '' ?>"><span><?= $t['salary_components'] ?></span></a>
                    <a href="<?= $baseUrl ?>/hr/appraisals" class="nav-link <?= $isActiveLink('/hr/appraisals') ? 'active' : '' ?>"><span><?= $t['appraisals'] ?></span></a>
                    <a href="<?= $baseUrl ?>/hr/recruitment" class="nav-link <?= $isActiveLink('/hr/recruitment') ? 'active' : '' ?>"><span><?= $t['recruitment'] ?></span></a>
                    <a href="<?= $baseUrl ?>/hr/documents" class="nav-link <?= $isActiveLink('/hr/documents') ? 'active' : '' ?>"><span><?= $t['hr_documents'] ?></span></a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- إدارة النظام والإعدادات -->
        <?php if($canViewAdmin || $isSuperAdmin): $adminActive = $isActiveHub(['/admin', '/settings']); ?>
            <div style="margin-top: 18px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.06);">
                <div class="nav-hub <?= $adminActive ? 'active expanded' : '' ?>" onclick="toggleAccordion(this)" title="<?= $t['admin'] ?>">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <i class="ph-duotone ph-gear hub-icon" style="color: #cbd5e1;"></i> 
                        <span class="hub-title"><?= $t['admin'] ?></span>
                    </div>
                    <i class="ph-bold ph-caret-right"></i>
                </div>
                <div class="sub-menu-wrapper" style="<?= $adminActive ? 'max-height: 1000px;' : '' ?>">
                    <div class="sub-menu-list">
                        <a href="<?= $baseUrl ?>/admin/companies" class="nav-link <?= $isActiveLink('/admin/companies') ? 'active' : '' ?>"><span><?= $t['companies'] ?></span></a>
                        <a href="<?= $baseUrl ?>/admin/branches" class="nav-link <?= $isActiveLink('/admin/branches') ? 'active' : '' ?>"><span><?= $t['branches'] ?></span></a>
                        <a href="<?= $baseUrl ?>/settings/roles" class="nav-link <?= $isActiveLink('/settings/roles') ? 'active' : '' ?>"><span><?= $t['roles'] ?></span></a>
                        <a href="<?= $baseUrl ?>/settings/users" class="nav-link <?= $isActiveLink('/settings/users') ? 'active' : '' ?>"><span><?= $t['users'] ?></span></a>
                        <a href="<?= $baseUrl ?>/admin/settings" class="nav-link <?= $isActiveLink('/admin/settings') ? 'active' : '' ?>"><span><?= $t['settings'] ?></span></a>
                        <a href="<?= $baseUrl ?>/admin/logs" class="nav-link <?= $isActiveLink('/admin/logs') ? 'active' : '' ?>"><span><?= $t['audit_logs'] ?></span></a>
                        <a href="<?= $baseUrl ?>/admin/backups" class="nav-link <?= $isActiveLink('/admin/backups') ? 'active' : '' ?>"><span><?= $t['backups'] ?></span></a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </nav>
</aside>

<script>
function toggleAccordion(element) {
    const sidebar = document.getElementById('ntSidebar');
    const isCollapsed = sidebar && (sidebar.classList.contains('collapsed') || document.body.classList.contains('sidebar-collapsed'));

    if (isCollapsed) {
        if (sidebar) sidebar.classList.remove('collapsed');
        document.body.classList.remove('sidebar-collapsed');
    }

    const targetWrapper = element.nextElementSibling;
    if (!targetWrapper || !targetWrapper.classList.contains('sub-menu-wrapper')) return;

    const isExpanded = element.classList.contains('expanded');

    document.querySelectorAll('.sub-menu-wrapper').forEach(wrapper => {
        if (wrapper !== targetWrapper) {
            wrapper.style.maxHeight = null;
            if (wrapper.previousElementSibling) {
                wrapper.previousElementSibling.classList.remove('expanded');
            }
        }
    });

    if (isExpanded && !isCollapsed) {
        targetWrapper.style.maxHeight = null;
        element.classList.remove('expanded');
    } else {
        const innerContent = targetWrapper.querySelector('.sub-menu-list');
        const scrollHeight = innerContent ? innerContent.scrollHeight + 20 : 1000;
        targetWrapper.style.maxHeight = scrollHeight + "px";
        element.classList.add('expanded');
    }
}

function openSidebarMobile() {
    const sidebar = document.getElementById('ntSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.add('mobile-open');
    if (overlay) overlay.classList.add('mobile-open');
}

function closeSidebarMobile() {
    const sidebar = document.getElementById('ntSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.remove('mobile-open');
    if (overlay) overlay.classList.remove('mobile-open');
}

document.addEventListener("DOMContentLoaded", function() {
    const activeLink = document.querySelector('.nav-link.active');
    if (activeLink) {
        const wrapper = activeLink.closest('.sub-menu-wrapper');
        if (wrapper) {
            const innerContent = wrapper.querySelector('.sub-menu-list');
            const scrollHeight = innerContent ? innerContent.scrollHeight + 20 : 1000;
            wrapper.style.maxHeight = scrollHeight + "px";
            if (wrapper.previousElementSibling) {
                wrapper.previousElementSibling.classList.add('expanded');
            }
        }
        setTimeout(() => {
            activeLink.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 300);
    }

    const mobileToggleBtn = document.querySelector('.mobile-sidebar-toggle, .menu-toggle-btn, [data-toggle="sidebar"]');
    if (mobileToggleBtn) {
        mobileToggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const sidebar = document.getElementById('ntSidebar');
            if (sidebar) {
                if (window.innerWidth <= 1024) {
                    if (sidebar.classList.contains('mobile-open')) {
                        closeSidebarMobile();
                    } else {
                        openSidebarMobile();
                    }
                } else {
                    sidebar.classList.toggle('collapsed');
                    document.body.classList.toggle('sidebar-collapsed');
                }
            }
        });
    }
});
</script>