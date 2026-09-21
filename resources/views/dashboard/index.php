<?php
// Path: resources/views/dashboard/index.php

if (session_status() === PHP_SESSION_NONE) session_start();

$isAr     = $isAr ?? (($_SESSION['locale'] ?? 'ar') === 'ar');
$dir      = $isAr ? 'rtl' : 'ltr';
$currency = $currency ?? 'EGP';

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isAr ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title' => 'مركز القيادة والتحكم التنفيذي',
        'desc' => 'نظرة شاملة وموحدة على الأداء المالي، التشغيلي، المبيعات والمخزون.',
        'active_scope' => 'الفرع النشط:',
        'from' => 'من:',
        'to' => 'إلى:',
        'filter_btn' => 'تحديث',
        
        // Hero Financial KPIs
        'total_sales' => 'إجمالي المبيعات',
        'total_purchases' => 'إجمالي المشتريات',
        'expenses' => 'المصروفات التشغيلية',
        'net_profit' => 'صافي الربح التقديري',
        
        // Liquidity & Balances
        'cash_balance' => 'سيولة الخزائن والبنوك',
        'receivables' => 'ذمم العملاء (مستحقات)',
        'payables' => 'ذمم الموردين (التزامات)',
        'inventory_val' => 'قيمة المخزون المتاح',
        'assets_val' => 'صافي الأصول الثابتة',
        'budgets_val' => 'الموازنات التقديرية',

        // Operational Pulse
        'sec_ops' => 'مؤشرات الأداء القطاعي والعمليات',
        'quotes' => 'عروض أسعار',
        'orders' => 'أوامر بيع',
        'returns' => 'مرتجعات مبيعات',
        'prs' => 'طلبات شراء',
        'projects' => 'مشاريع جارية',
        'proj_val' => 'عقود المشاريع',
        'crm_leads' => 'فرص CRM',
        'employees' => 'الموظفون النشطون',
        'leaves' => 'إجازات معلقة',
        'cheques' => 'شيكات معلقة',
        'petty_cash' => 'متبقي العُهد',

        // Analytics
        'chart_financial' => 'مؤشر الحركة المالية والإنفاق (6 شهور)',
        'chart_cashflow' => 'التدفقات النقدية (المقبوضات vs المدفوعات)',
        'chart_expenses' => 'توزيع المصروفات حسب الحسابات',
        'chart_crm' => 'مسار تحويل الفرص (CRM Pipeline)',

        // Watchlist & Tables
        'overdue_inv' => 'فواتير متأخرة السداد',
        'exp_docs' => 'وثائق تنتهي قريباً',
        'low_stock' => 'أصناف بلغت حد الطلب',
        'top_prod' => 'الأعلى مبيعاً (بالقيمة)',
        'active_proj' => 'المشاريع ونسبة الإنجاز',
        'recent_entries' => 'أحدث قيود اليومية',

        'col_num' => 'الكود/المرجع',
        'col_party' => 'الطرف/العميل',
        'col_amount' => 'المبلغ',
        'col_days' => 'المتبقي',
        'col_progress' => 'الإنجاز',
        'no_alerts' => 'لا توجد تنبيهات حرجة حالياً.',
        'no_data' => 'لا توجد بيانات مسجلة.'
    ],
    'en' => [
        'title' => 'Executive Control Center',
        'desc' => 'Unified real-time visibility over financial, sales, inventory and HR performance.',
        'active_scope' => 'Active Branch:',
        'from' => 'From:',
        'to' => 'To:',
        'filter_btn' => 'Update',
        
        // Hero Financial KPIs
        'total_sales' => 'Total Sales Revenue',
        'total_purchases' => 'Total Purchases',
        'expenses' => 'Operating Expenses',
        'net_profit' => 'Net Profit (Est.)',
        
        // Liquidity & Balances
        'cash_balance' => 'Cash & Bank Liquidity',
        'receivables' => 'Accounts Receivable',
        'payables' => 'Accounts Payable',
        'inventory_val' => 'Inventory Valuation',
        'assets_val' => 'Fixed Assets Net',
        'budgets_val' => 'Approved Budgets',

        // Operational Pulse
        'sec_ops' => 'Operational & Departmental Pulse',
        'quotes' => 'Pending Quotes',
        'orders' => 'Active Sales Orders',
        'returns' => 'Sales Returns',
        'prs' => 'Purchase Requisitions',
        'projects' => 'Active Projects',
        'proj_val' => 'Projects Value',
        'crm_leads' => 'CRM Leads',
        'employees' => 'Active Employees',
        'leaves' => 'Pending Leaves',
        'cheques' => 'Pending Cheques',
        'petty_cash' => 'Petty Cash Balance',

        // Analytics
        'chart_financial' => 'Financial Performance (Last 6 Months)',
        'chart_cashflow' => 'Cash Dynamics (Inflow vs Outflow)',
        'chart_expenses' => 'Expenses Breakdown',
        'chart_crm' => 'CRM Conversion Pipeline',

        // Watchlist & Tables
        'overdue_inv' => 'Overdue Invoices',
        'exp_docs' => 'Expiring HR Documents',
        'low_stock' => 'Low Stock Items',
        'top_prod' => 'Top Products by Revenue',
        'active_proj' => 'Active Projects Progress',
        'recent_entries' => 'Recent Journal Entries',

        'col_num' => 'Ref / Code',
        'col_party' => 'Party / Customer',
        'col_amount' => 'Amount',
        'col_days' => 'Days Left',
        'col_progress' => 'Progress',
        'no_alerts' => 'No active alerts.',
        'no_data' => 'No records found.'
    ]
][$isAr ? 'ar' : 'en'];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
    :root {
        --surface-bg: #f8fafc;
        --card-bg: #ffffff;
        --border-color: #e2e8f0;
        --text-heading: #0f172a;
        --text-body: #334155;
        --text-sub: #64748b;
        
        --brand-primary: #4f46e5;   --brand-primary-light: #e0e7ff;
        --brand-success: #059669;   --brand-success-light: #d1fae5;
        --brand-danger: #e11d48;    --brand-danger-light: #ffe4e6;
        --brand-warning: #d97706;   --brand-warning-light: #fef3c7;
        --brand-info: #0284c7;      --brand-info-light: #e0f2fe;
        --brand-purple: #9333ea;    --brand-purple-light: #f3e8ff;
    }

    body.dark-mode, [data-theme="dark"] {
        --surface-bg: #0b0f19;
        --card-bg: #151e2e;
        --border-color: #27354a;
        --text-heading: #f8fafc;
        --text-body: #cbd5e1;
        --text-sub: #8192a6;
    }

    .main-dash-container { 
        font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; 
        padding-bottom: 60px; 
        background: var(--surface-bg); 
        min-height: 100vh; 
        color: var(--text-body);
        width: 100%;
        box-sizing: border-box;
    }
    
    /* Sticky Executive Header */
    .glass-top-header {
        position: sticky; top: 0; z-index: 40;
        background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(16px);
        border-bottom: 1px solid var(--border-color);
        padding: 16px 28px; margin-bottom: 24px;
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;
    }
    body.dark-mode .glass-top-header { background: rgba(21, 30, 46, 0.9); }

    .header-brand-title { display: flex; align-items: center; gap: 14px; }
    .header-icon-box {
        width: 48px; height: 48px; border-radius: 14px;
        background: linear-gradient(135deg, var(--brand-primary), #3730a3);
        color: #ffffff; display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem; box-shadow: 0 8px 18px rgba(79, 70, 229, 0.25);
    }

    .filter-pill-bar {
        background: var(--card-bg); border: 1px solid var(--border-color);
        padding: 6px 12px; border-radius: 14px; display: flex; align-items: center; gap: 10px;
    }
    .filter-input {
        border: 1px solid var(--border-color); border-radius: 8px;
        padding: 6px 10px; font-size: 0.85rem; font-weight: 700;
        background: var(--surface-bg); color: var(--text-heading); font-family: inherit;
    }
    .btn-apply-filter {
        background: var(--text-heading); color: var(--card-bg);
        border: none; padding: 7px 16px; border-radius: 8px;
        font-weight: 800; font-size: 0.85rem; cursor: pointer; transition: 0.2s;
        display: inline-flex; align-items: center; gap: 6px;
    }

    .branch-pill {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--card-bg); border: 1px solid var(--border-color);
        padding: 6px 16px; border-radius: 20px; font-size: 0.82rem; font-weight: 800;
        margin: 0 28px 24px 28px;
    }

    /* Shortcuts Bar */
    .shortcuts-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 14px; margin: 0 28px 28px 28px;
    }
    .shortcut-card {
        background: var(--card-bg); border: 1px solid var(--border-color);
        border-radius: 14px; padding: 12px 16px; text-decoration: none;
        color: var(--text-heading); display: flex; align-items: center; gap: 12px;
        font-weight: 800; font-size: 0.88rem; transition: all 0.2s ease;
        box-shadow: 0 4px 10px rgba(0,0,0,0.02);
    }
    .shortcut-card:hover { transform: translateY(-2px); border-color: var(--brand-primary); box-shadow: 0 8px 20px rgba(0,0,0,0.06); }
    .shortcut-icon { width: 36px; height: 36px; border-radius: 10px; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }

    /* Bento Grid System - عزل وتحديد العرض بدقة */
    .bento-container { padding: 0 28px; width: 100%; box-sizing: border-box; }
    .bento-row { 
        display: grid !important; 
        grid-template-columns: repeat(12, 1fr) !important; 
        gap: 20px !important; 
        margin-bottom: 24px !important; 
        width: 100% !important; 
        box-sizing: border-box !important;
    }
    
    .bento-card {
        background: var(--card-bg); border: 1px solid var(--border-color);
        border-radius: 20px; padding: 22px; transition: all 0.3s ease;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.03);
        display: flex; flex-direction: column; 
        min-width: 0 !important; /* يمنع انكماش الكروت أو تمددها العشوائي */
        overflow: hidden !important;
        box-sizing: border-box !important;
    }

    /* Hero Financial KPIs */
    .kpi-hero { grid-column: span 3; border-top: 5px solid var(--brand-primary); }
    .kpi-hero.emerald { border-top-color: var(--brand-success); }
    .kpi-hero.rose { border-top-color: var(--brand-danger); }
    .kpi-hero.amber { border-top-color: var(--brand-warning); }
    
    .kpi-hero-title { font-size: 0.8rem; font-weight: 800; color: var(--text-sub); text-transform: uppercase; margin-bottom: 8px; }
    .kpi-hero-val { font-size: 1.7rem; font-weight: 900; font-family: monospace; color: var(--text-heading); display: flex; align-items: baseline; gap: 6px; }

    /* Balance Widgets */
    .kpi-sub { grid-column: span 2; }
    .kpi-sub-val { font-size: 1.25rem; font-weight: 900; font-family: monospace; color: var(--text-heading); margin-top: 4px; }

    /* Operational Grid Nodes */
    .ops-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; }
    .ops-card-node {
        background: var(--surface-bg); border: 1px solid var(--border-color);
        border-radius: 14px; padding: 14px 16px;
    }
    .ops-card-node h6 { margin: 0 0 6px 0; font-size: 0.78rem; font-weight: 800; color: var(--text-sub); }
    .ops-card-node p { margin: 0; font-size: 1.3rem; font-weight: 900; font-family: monospace; color: var(--text-heading); }

    /* Column Spans */
    .span-12 { grid-column: span 12 !important; }
    .span-8  { grid-column: span 8 !important; }
    .span-6  { grid-column: span 6 !important; }
    .span-4  { grid-column: span 4 !important; }

    @media(max-width: 1200px) { 
        .kpi-hero { grid-column: span 6 !important; } 
        .kpi-sub { grid-column: span 4 !important; } 
        .span-8 { grid-column: span 12 !important; } 
    }
    @media(max-width: 768px)  { 
        .kpi-hero, .kpi-sub, .span-4, .span-6 { grid-column: span 12 !important; } 
        .bento-container { padding: 0 14px; }
        .shortcuts-grid, .branch-pill { margin-left: 14px; margin-right: 14px; }
    }

    /* Section Titles */
    .widget-title { font-size: 1.05rem; font-weight: 900; color: var(--text-heading); margin: 0 0 18px 0; display: flex; align-items: center; gap: 10px; }
    .widget-title i { font-size: 1.3rem; }

    /* Clean Non-DataTables Table Styling */
    .dash-pure-table { width: 100% !important; border-collapse: collapse !important; font-size: 0.85rem !important; margin: 0 !important; }
    .dash-pure-table th { padding: 12px 14px !important; background: var(--surface-bg) !important; color: var(--text-sub) !important; font-weight: 800 !important; border-bottom: 2px solid var(--border-color) !important; text-align: start !important; font-size: 0.75rem !important; text-transform: uppercase !important; }
    .dash-pure-table td { padding: 12px 14px !important; border-bottom: 1px solid var(--border-color) !important; color: var(--text-body) !important; font-weight: 700 !important; vertical-align: middle !important; }
    .dash-pure-table tr:last-child td { border-bottom: none !important; }

    .risk-card { border-inline-start: 5px solid var(--brand-danger); }
    .warning-card { border-inline-start: 5px solid var(--brand-warning); }

    .progress-track { background: var(--border-color); height: 6px; border-radius: 3px; overflow: hidden; margin-top: 4px; width: 100%; }
    .progress-fill { background: var(--brand-primary); height: 100%; }

    /* TIGHT DATA TABLES KILLER: إزالة تامة وكاملة لأي عنصر محقون من DataTables */
    .bento-card .dataTables_wrapper,
    .bento-card .dataTables_filter,
    .bento-card .dataTables_length,
    .bento-card .dataTables_info,
    .bento-card .dataTables_paginate {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
        opacity: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        position: absolute !important;
        pointer-events: none !important;
    }
</style>

<div class="main-dash-container" dir="<?= $dir ?>">
    
    <!-- Executive Sticky Header -->
    <div class="glass-top-header">
        <div class="header-brand-title">
            <div class="header-icon-box"><i class="ph-duotone ph-squares-four"></i></div>
            <div>
                <h1 style="margin:0; font-size:1.6rem; color:var(--text-heading);"><?= $t['title'] ?></h1>
                <p style="margin:3px 0 0 0; color:var(--text-sub); font-size:0.88rem; font-weight:600;"><?= $t['desc'] ?></p>
            </div>
        </div>

        <form action="/ERP/dashboard" method="GET" class="filter-pill-bar">
            <i class="ph-bold ph-calendar" style="color:var(--brand-primary);"></i>
            <span style="font-size:0.8rem; font-weight:800;"><?= $t['from'] ?></span>
            <input type="date" name="start_date" class="filter-input" value="<?= htmlspecialchars($applied_filters['start_date']) ?>">
            <span style="font-size:0.8rem; font-weight:800;"><?= $t['to'] ?></span>
            <input type="date" name="end_date" class="filter-input" value="<?= htmlspecialchars($applied_filters['end_date']) ?>">
            <button type="submit" class="btn-apply-filter"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['filter_btn'] ?></button>
        </form>
    </div>

    <!-- Active Branch Scope Indicator -->
    <div class="branch-pill">
        <i class="ph-bold ph-storefront" style="color:var(--brand-primary);"></i>
        <span><?= $t['active_scope'] ?></span>
        <span style="color:var(--brand-primary); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
    </div>

    <!-- Action Shortcuts Bar -->
    <div class="shortcuts-grid">
        <?php foreach($shortcuts as $sc): ?>
            <a href="<?= $sc['link'] ?>" class="shortcut-card">
                <div class="shortcut-icon" style="background: <?= $sc['color'] ?>;"><i class="ph-bold <?= $sc['icon'] ?>"></i></div>
                <span><?= $isAr ? $sc['title_ar'] : $sc['title_en'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="bento-container">
        
        <!-- Hero Financial KPIs -->
        <div class="bento-row">
            <div class="bento-card kpi-hero emerald">
                <div class="kpi-hero-title"><?= $t['total_sales'] ?></div>
                <div class="kpi-hero-val" style="color: var(--brand-success);"><?= number_format($kpis['total_sales'], 2) ?> <span style="font-size:0.8rem; color:var(--text-sub);"><?= $currency ?></span></div>
            </div>
            <div class="bento-card kpi-hero amber">
                <div class="kpi-hero-title"><?= $t['total_purchases'] ?></div>
                <div class="kpi-hero-val" style="color: var(--brand-warning);"><?= number_format($kpis['total_purchases'], 2) ?> <span style="font-size:0.8rem; color:var(--text-sub);"><?= $currency ?></span></div>
            </div>
            <div class="bento-card kpi-hero rose">
                <div class="kpi-hero-title"><?= $t['expenses'] ?></div>
                <div class="kpi-hero-val" style="color: var(--brand-danger);"><?= number_format($kpis['expenses'], 2) ?> <span style="font-size:0.8rem; color:var(--text-sub);"><?= $currency ?></span></div>
            </div>
            <div class="bento-card kpi-hero" style="border-top-color: var(--brand-primary); background: var(--brand-primary-light);">
                <div class="kpi-hero-title" style="color: var(--brand-primary);"><?= $t['net_profit'] ?></div>
                <div class="kpi-hero-val" style="color: var(--brand-primary);"><?= number_format($kpis['net_profit'], 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></div>
            </div>
        </div>

        <!-- Liquidity & Balances Overview -->
        <div class="bento-row">
            <div class="bento-card kpi-sub">
                <div class="kpi-hero-title"><?= $t['cash_balance'] ?></div>
                <div class="kpi-sub-val" style="color:var(--brand-info);"><?= number_format($kpis['cash_balance'], 2) ?></div>
            </div>
            <div class="bento-card kpi-sub">
                <div class="kpi-hero-title"><?= $t['receivables'] ?></div>
                <div class="kpi-sub-val" style="color:var(--brand-success);"><?= number_format($kpis['receivables'], 2) ?></div>
            </div>
            <div class="bento-card kpi-sub">
                <div class="kpi-hero-title"><?= $t['payables'] ?></div>
                <div class="kpi-sub-val" style="color:var(--brand-danger);"><?= number_format($kpis['payables'], 2) ?></div>
            </div>
            <div class="bento-card kpi-sub">
                <div class="kpi-hero-title"><?= $t['inventory_val'] ?></div>
                <div class="kpi-sub-val" style="color:var(--brand-purple);"><?= number_format($kpis['inventory_value'], 2) ?></div>
            </div>
            <div class="bento-card kpi-sub">
                <div class="kpi-hero-title"><?= $t['assets_val'] ?></div>
                <div class="kpi-sub-val" style="color:var(--brand-primary);"><?= number_format($kpis['fixed_assets_val'], 2) ?></div>
            </div>
            <div class="bento-card kpi-sub">
                <div class="kpi-hero-title"><?= $t['budgets_val'] ?></div>
                <div class="kpi-sub-val"><?= number_format($kpis['allocated_budgets'], 2) ?></div>
            </div>
        </div>

        <!-- Departmental & Operations Pulse -->
        <div class="bento-row">
            <div class="bento-card span-12">
                <div class="widget-title"><i class="ph-duotone ph-pulse" style="color:var(--brand-primary);"></i> <?= $t['sec_ops'] ?></div>
                <div class="ops-grid">
                    <div class="ops-card-node">
                        <h6><?= $t['quotes'] ?></h6>
                        <p><?= number_format($kpis['quotations_count']) ?></p>
                    </div>
                    <div class="ops-card-node">
                        <h6><?= $t['orders'] ?></h6>
                        <p><?= number_format($kpis['sales_orders_count']) ?></p>
                    </div>
                    <div class="ops-card-node">
                        <h6><?= $t['returns'] ?></h6>
                        <p style="color:var(--brand-danger);"><?= number_format($kpis['sales_returns_total'], 2) ?></p>
                    </div>
                    <div class="ops-card-node">
                        <h6><?= $t['prs'] ?></h6>
                        <p style="color:var(--brand-warning);"><?= number_format($kpis['purchase_requests_count']) ?></p>
                    </div>
                    <div class="ops-card-node">
                        <h6><?= $t['projects'] ?></h6>
                        <p style="color:var(--brand-primary);"><?= number_format($kpis['active_projects_count']) ?></p>
                    </div>
                    <div class="ops-card-node">
                        <h6><?= $t['crm_leads'] ?></h6>
                        <p style="color:var(--brand-success);"><?= number_format($kpis['converted_leads_count']) ?> / <?= number_format($kpis['total_leads_count']) ?></p>
                    </div>
                    <div class="ops-card-node">
                        <h6><?= $t['employees'] ?></h6>
                        <p><?= number_format($kpis['active_employees']) ?></p>
                    </div>
                    <div class="ops-card-node">
                        <h6><?= $t['leaves'] ?></h6>
                        <p style="color:var(--brand-purple);"><?= number_format($kpis['pending_leaves']) ?></p>
                    </div>
                    <div class="ops-card-node">
                        <h6><?= $t['cheques'] ?></h6>
                        <p style="color:var(--brand-warning);"><?= number_format($kpis['pending_cheques_val'], 2) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="bento-row">
            <div class="bento-card span-8">
                <div class="widget-title"><i class="ph-duotone ph-chart-line-up" style="color:var(--brand-primary);"></i> <?= $t['chart_financial'] ?></div>
                <div style="height: 260px; position: relative; width: 100%;">
                    <canvas id="financialChart"></canvas>
                </div>
            </div>

            <div class="bento-card span-4">
                <div class="widget-title"><i class="ph-duotone ph-chart-pie-slice" style="color:var(--brand-danger);"></i> <?= $t['chart_expenses'] ?></div>
                <div style="height: 230px; position: relative; width: 100%; display:flex; justify-content:center;">
                    <canvas id="expensesPieChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="bento-row">
            <div class="bento-card span-8">
                <div class="widget-title"><i class="ph-duotone ph-arrows-left-right" style="color:var(--brand-success);"></i> <?= $t['chart_cashflow'] ?></div>
                <div style="height: 240px; position: relative; width: 100%;">
                    <canvas id="cashflowChart"></canvas>
                </div>
            </div>

            <div class="bento-card span-4">
                <div class="widget-title"><i class="ph-duotone ph-funnel" style="color:var(--brand-purple);"></i> <?= $t['chart_crm'] ?></div>
                <div style="height: 220px; position: relative; width: 100%;">
                    <canvas id="crmPipelineChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Risk Watch & Alerts -->
        <div class="bento-row">
            <div class="bento-card span-4 risk-card">
                <div class="widget-title" style="color:var(--brand-danger);"><i class="ph-bold ph-warning-circle"></i> <?= $t['overdue_inv'] ?></div>
                <div style="overflow-x:auto; width:100%;">
                    <table class="clean-dash-table dash-pure-table">
                        <thead>
                            <tr>
                                <th><?= $t['col_num'] ?></th>
                                <th><?= $t['col_party'] ?></th>
                                <th style="text-align:end;"><?= $t['col_amount'] ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($alerts['overdue_invoices'])): ?>
                                <tr><td colspan="3" style="text-align:center; color:var(--text-sub); padding:20px;"><?= $t['no_alerts'] ?></td></tr>
                            <?php else: foreach($alerts['overdue_invoices'] as $inv): ?>
                                <tr>
                                    <td style="font-family:monospace; font-weight:900; color:var(--brand-danger);"><?= htmlspecialchars($inv['invoice_number']) ?></td>
                                    <td><?= htmlspecialchars($isAr ? $inv['name_ar'] : ($inv['name_en'] ?: $inv['name_ar'])) ?></td>
                                    <td style="font-family:monospace; font-weight:900; text-align:end;"><?= number_format($inv['remaining'], 2) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bento-card span-4 warning-card">
                <div class="widget-title" style="color:var(--brand-warning);"><i class="ph-bold ph-clock-afternoon"></i> <?= $t['exp_docs'] ?></div>
                <div style="overflow-x:auto; width:100%;">
                    <table class="clean-dash-table dash-pure-table">
                        <thead>
                            <tr>
                                <th><?= $t['col_party'] ?></th>
                                <th>الوثيقة</th>
                                <th style="text-align:center;"><?= $t['col_days'] ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($alerts['expiring_docs'])): ?>
                                <tr><td colspan="3" style="text-align:center; color:var(--text-sub); padding:20px;"><?= $t['no_alerts'] ?></td></tr>
                            <?php else: foreach($alerts['expiring_docs'] as $doc): ?>
                                <tr>
                                    <td><?= htmlspecialchars($isAr ? $doc['name_ar'] : ($doc['name_en'] ?: $doc['name_ar'])) ?></td>
                                    <td><?= htmlspecialchars($doc['title_ar']) ?></td>
                                    <td style="font-family:monospace; font-weight:900; color:var(--brand-warning); text-align:center;"><?= (int)$doc['days_left'] ?> يوم</td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bento-card span-4 warning-card" style="border-inline-start-color: var(--brand-purple);">
                <div class="widget-title" style="color:var(--brand-purple);"><i class="ph-bold ph-package"></i> <?= $t['low_stock'] ?></div>
                <div style="overflow-x:auto; width:100%;">
                    <table class="clean-dash-table dash-pure-table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>الصنف</th>
                                <th style="text-align:center;">حد الإعادة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($alerts['low_stock_products'])): ?>
                                <tr><td colspan="3" style="text-align:center; color:var(--text-sub); padding:20px;"><?= $t['no_alerts'] ?></td></tr>
                            <?php else: foreach($alerts['low_stock_products'] as $prod): ?>
                                <tr>
                                    <td style="font-family:monospace; font-weight:900;"><?= htmlspecialchars($prod['sku']) ?></td>
                                    <td><?= htmlspecialchars($isAr ? $prod['name_ar'] : ($prod['name_en'] ?: $prod['name_ar'])) ?></td>
                                    <td style="font-family:monospace; font-weight:900; color:var(--brand-purple); text-align:center;"><?= number_format($prod['reorder_level']) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Operational Tables Row -->
        <div class="bento-row">
            <div class="bento-card span-4">
                <div class="widget-title"><i class="ph-bold ph-crown" style="color:var(--brand-warning);"></i> <?= $t['top_prod'] ?></div>
                <div style="overflow-x:auto; width:100%;">
                    <table class="clean-dash-table dash-pure-table">
                        <thead>
                            <tr>
                                <th>الصنف</th>
                                <th style="text-align:center;">الكمية</th>
                                <th style="text-align:end;"><?= $t['col_amount'] ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($tables['top_products'])): ?>
                                <tr><td colspan="3" style="text-align:center; color:var(--text-sub); padding:20px;"><?= $t['no_data'] ?></td></tr>
                            <?php else: foreach($tables['top_products'] as $tp): ?>
                                <tr>
                                    <td><?= htmlspecialchars($isAr ? ($tp['name_ar'] ?? 'صنف') : ($tp['name_en'] ?? $tp['name_ar'] ?? 'Item')) ?></td>
                                    <td style="font-family:monospace; text-align:center;"><?= number_format($tp['qty']) ?></td>
                                    <td style="font-family:monospace; font-weight:900; color:var(--brand-success); text-align:end;"><?= number_format($tp['revenue'], 2) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bento-card span-4">
                <div class="widget-title"><i class="ph-bold ph-kanban" style="color:var(--brand-primary);"></i> <?= $t['active_proj'] ?></div>
                <div style="overflow-x:auto; width:100%;">
                    <table class="clean-dash-table dash-pure-table">
                        <thead>
                            <tr>
                                <th>المشروع</th>
                                <th style="text-align:end;">الميزانية</th>
                                <th style="text-align:center;"><?= $t['col_progress'] ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($tables['active_projects'])): ?>
                                <tr><td colspan="3" style="text-align:center; color:var(--text-sub); padding:20px;"><?= $t['no_data'] ?></td></tr>
                            <?php else: foreach($tables['active_projects'] as $prj): $pct = (float)$prj['progress_percent']; ?>
                                <tr>
                                    <td>
                                        <div><?= htmlspecialchars($isAr ? $prj['name_ar'] : ($prj['name_en'] ?: $prj['name_ar'])) ?></div>
                                        <div style="font-family:monospace; font-size:0.7rem; color:var(--text-sub);"><?= htmlspecialchars($prj['code']) ?></div>
                                    </td>
                                    <td style="font-family:monospace; font-weight:800; text-align:end;"><?= number_format($prj['contract_value'], 2) ?></td>
                                    <td style="width:30%; text-align:center;">
                                        <div style="font-size:0.75rem; font-family:monospace; font-weight:900; text-align:end;"><?= $pct ?>%</div>
                                        <div class="progress-track"><div class="progress-fill" style="width:<?= $pct ?>%;"></div></div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bento-card span-4">
                <div class="widget-title"><i class="ph-bold ph-list-dashes" style="color:var(--brand-info);"></i> <?= $t['recent_entries'] ?></div>
                <div style="overflow-x:auto; width:100%;">
                    <table class="clean-dash-table dash-pure-table">
                        <thead>
                            <tr>
                                <th>رقم القيد</th>
                                <th>البيان</th>
                                <th style="text-align:end;">المبلغ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($tables['recent_transactions'])): ?>
                                <tr><td colspan="3" style="text-align:center; color:var(--text-sub); padding:20px;"><?= $t['no_data'] ?></td></tr>
                            <?php else: foreach($tables['recent_transactions'] as $trx): ?>
                                <tr>
                                    <td style="font-family:monospace; font-weight:900; color:var(--brand-info);">#<?= htmlspecialchars($trx['entry_number']) ?></td>
                                    <td style="font-size:0.8rem;"><?= htmlspecialchars($trx['details'] ?: 'قيد محاسبي') ?></td>
                                    <td style="font-family:monospace; font-weight:900; text-align:end;"><?= number_format($trx['amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    Chart.defaults.font.family = "<?= $isAr ? 'Cairo, sans-serif' : 'Inter, sans-serif' ?>";
    Chart.defaults.color = '#64748b';

    // Purge unwanted DataTables injected wrappers completely
    function purgeDataTables() {
        document.querySelectorAll('.bento-card .dataTables_wrapper, .bento-card .dataTables_filter, .bento-card .dataTables_length, .bento-card .dataTables_info, .bento-card .dataTables_paginate').forEach(el => el.remove());
    }
    purgeDataTables();
    setTimeout(purgeDataTables, 300);

    // Chart 1: Financial Performance
    new Chart(document.getElementById('financialChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode($charts['labels']) ?>,
            datasets: [
                {
                    label: '<?= $t['total_sales'] ?>',
                    data: <?= json_encode($charts['sales']) ?>,
                    borderColor: '#059669', backgroundColor: 'rgba(5, 150, 105, 0.08)',
                    borderWidth: 3, fill: true, tension: 0.3
                },
                {
                    label: '<?= $t['total_purchases'] ?>',
                    data: <?= json_encode($charts['purchases']) ?>,
                    borderColor: '#d97706', backgroundColor: 'transparent',
                    borderWidth: 2, borderDash: [5, 5], tension: 0.3
                },
                {
                    label: '<?= $t['expenses'] ?>',
                    data: <?= json_encode($charts['expenses']) ?>,
                    borderColor: '#e11d48', backgroundColor: 'transparent',
                    borderWidth: 2, tension: 0.3
                }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
    });

    // Chart 2: Expenses Breakdown
    new Chart(document.getElementById('expensesPieChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($charts['expense_categories'])) ?>,
            datasets: [{
                data: <?= json_encode(array_values($charts['expense_categories'])) ?>,
                backgroundColor: ['#e11d48', '#2563eb', '#9333ea', '#d97706', '#059669'],
                borderWidth: 2
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // Chart 3: Cashflow Inflow vs Outflow
    new Chart(document.getElementById('cashflowChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($charts['labels']) ?>,
            datasets: [
                {
                    label: 'السيولة الواردة (Inflow)',
                    data: <?= json_encode($charts['cash_in']) ?>,
                    backgroundColor: '#2563eb', borderRadius: 6
                },
                {
                    label: 'السيولة الصادرة (Outflow)',
                    data: <?= json_encode($charts['cash_out']) ?>,
                    backgroundColor: '#e11d48', borderRadius: 6
                }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
    });

    // Chart 4: CRM Pipeline
    new Chart(document.getElementById('crmPipelineChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['جديد (New)', 'تم التواصل', 'محولة لعملاء'],
            datasets: [{
                label: 'عدد الفرص',
                data: [
                    <?= (int)($charts['leads_status']['new'] ?? 0) ?>,
                    <?= (int)($charts['leads_status']['contacted'] ?? 0) ?>,
                    <?= (int)($charts['leads_status']['converted'] ?? 0) ?>
                ],
                backgroundColor: ['#4f46e5', '#d97706', '#059669'],
                borderRadius: 8
            }]
        },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
});
</script>