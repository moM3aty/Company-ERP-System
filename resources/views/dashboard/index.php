<?php
// Path: resources/views/dashboard/index.php

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$currentLocale = $locale ?? $_SESSION['locale'] ?? 'ar';
$isRtl = isRtl();
$currency = current_currency();

$applied_filters = $applied_filters ?? [];
$kpis = $kpis ?? [];
$charts = $charts ?? [];
$alerts = $alerts ?? [];
$tables = $tables ?? [];
$shortcuts = $shortcuts ?? [];

$t = [
    'ar' => [
        'dashboard_title' => 'لوحة القيادة الإستراتيجية',
        'date_filters' => 'الفترة', 'to' => 'إلى', 'apply_filters' => 'تحديث',
        'financial_overview' => 'المؤشرات المالية الرئيسية',
        'operations_overview' => 'المشاريع والعمليات والـ CRM',
        'hr_treasury_overview' => 'الموارد البشرية والخزينة والأصول',
        'total_sales' => 'المبيعات', 'total_purchases' => 'المشتريات',
        'expenses' => 'المصروفات', 'net_profit' => 'صافي الربح',
        'cash_balance' => 'النقدية والبنوك', 'receivables' => 'ذمم العملاء',
        'payables' => 'التزامات الموردين', 'inventory_value' => 'المخزون',
        'fixed_assets' => 'الأصول الثابتة', 'active_projects' => 'المشاريع الجارية',
        'project_val' => 'عقود المشاريع', 'crm_leads' => 'الفرص (Leads)',
        'crm_converted' => 'نسبة التحويل', 'sales_returns' => 'المرتجعات',
        'active_staff' => 'الموظفين', 'pending_leaves' => 'إجازات معلقة',
        'monthly_payroll' => 'مسير الرواتب', 'pending_cheques' => 'شيكات للتحصيل',
        'petty_cash' => 'متبقي العُهد', 'sales_trend' => 'مؤشر المبيعات (6 أشهر)',
        'purch_exp_trend' => 'المشتريات والمصروفات', 'cash_flow' => 'التدفقات النقدية',
        'expense_breakdown' => 'تحليل المصروفات', 'leads_breakdown' => 'مسار العملاء (CRM)',
        'alerts_risks' => 'تنبيهات ومخاطر حادة', 'overdue_invoices' => 'فواتير متأخرة',
        'expiring_docs' => 'وثائق تنتهي قريباً', 'low_stock' => 'أصناف بلغت حد الطلب',
        'top_products' => 'الأكثر مبيعاً', 'active_projects_tbl' => 'أداء المشاريع',
        'recent_trx' => 'أحدث القيود', 'days' => 'يوم', 'progress' => 'الإنجاز',
        'no_data' => 'لا توجد بيانات', 'cash_in' => 'مقبوضات', 'cash_out' => 'مدفوعات',
        'new' => 'جديد', 'contacted' => 'تم التواصل', 'converted' => 'ناجح'
    ],
    'en' => [
        'dashboard_title' => 'Executive Dashboard',
        'date_filters' => 'Period', 'to' => 'To', 'apply_filters' => 'Update',
        'financial_overview' => 'Financial Key Metrics',
        'operations_overview' => 'Operations, Projects & CRM',
        'hr_treasury_overview' => 'HR, Treasury & Assets',
        'total_sales' => 'Sales Revenue', 'total_purchases' => 'Purchases',
        'expenses' => 'Expenses', 'net_profit' => 'Net Profit',
        'cash_balance' => 'Cash & Banks', 'receivables' => 'Receivables',
        'payables' => 'Payables', 'inventory_value' => 'Inventory',
        'fixed_assets' => 'Fixed Assets', 'active_projects' => 'Active Projects',
        'project_val' => 'Contracts Value', 'crm_leads' => 'Total Leads',
        'crm_converted' => 'Conversion Rate', 'sales_returns' => 'Sales Returns',
        'active_staff' => 'Active Staff', 'pending_leaves' => 'Pending Leaves',
        'monthly_payroll' => 'Net Payroll', 'pending_cheques' => 'Pending Cheques',
        'petty_cash' => 'Petty Cash', 'sales_trend' => 'Sales Trend (6M)',
        'purch_exp_trend' => 'Purchases vs Expenses', 'cash_flow' => 'Cash Flow (In/Out)',
        'expense_breakdown' => 'Expense Breakdown', 'leads_breakdown' => 'CRM Pipeline',
        'alerts_risks' => 'Critical Risk Alerts', 'overdue_invoices' => 'Overdue Invoices',
        'expiring_docs' => 'Expiring Documents', 'low_stock' => 'Low Stock Items',
        'top_products' => 'Top Products', 'active_projects_tbl' => 'Project Progress',
        'recent_trx' => 'Recent Entries', 'days' => 'Days', 'progress' => 'Progress',
        'no_data' => 'No Data', 'cash_in' => 'Cash In', 'cash_out' => 'Cash Out',
        'new' => 'New', 'contacted' => 'Contacted', 'converted' => 'Converted'
    ]
][$currentLocale];

$leadsTotal = (int)($kpis['total_leads_count'] ?? 0);
$leadsConverted = (int)($kpis['converted_leads_count'] ?? 0);
$conversionRate = $leadsTotal > 0 ? round(($leadsConverted / $leadsTotal) * 100) : 0;
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<style>
    .erp-dashboard { font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: #f8fafc; padding: 16px; color: #0f172a; min-height: 100vh; }

    /* Compact Header Bar */
    .dash-header-bar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; }
    .dash-title { margin: 0; font-size: 1.35rem; font-weight: 900; display: flex; align-items: center; gap: 8px; color: #0f172a; }

    .filter-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 8px; }
    .form-control { padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 0.8rem; color: #0f172a; background: #f8fafc; }
    .btn-submit-filter { background: #0284c7; color: #fff; border: none; padding: 5px 12px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 0.8rem; font-family: inherit; }

    /* Compact Quick Actions */
    .quick-actions-wrap { display: flex; gap: 8px; overflow-x: auto; margin-bottom: 16px; padding-bottom: 2px; }
    .action-card { flex: 1; min-width: 120px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px; display: flex; align-items: center; gap: 8px; text-decoration: none; color: #1e293b; font-weight: 800; font-size: 0.78rem; transition: 0.15s; }
    .action-card:hover { border-color: #0284c7; background: #f0f9ff; }
    .action-icon { width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }

    /* HERO FINANCIAL KPIS */
    .hero-kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 12px; }
    @media (max-width: 1024px) { .hero-kpi-grid { grid-template-columns: repeat(2, 1fr); } }

    .hero-card { border-radius: 12px; padding: 12px 16px; color: #ffffff; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; }
    .hero-card-sales { background: linear-gradient(135deg, #059669, #10b981); }
    .hero-card-purchases { background: linear-gradient(135deg, #d97706, #f59e0b); }
    .hero-card-expenses { background: linear-gradient(135deg, #dc2626, #ef4444); }
    .hero-card-profit { background: linear-gradient(135deg, #0284c7, #38bdf8); }

    .hero-title { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; opacity: 0.95; }
    .hero-amount { font-size: 1.45rem; font-weight: 900; font-family: monospace; margin-top: 4px; }
    .hero-curr { font-size: 0.75rem; font-weight: 700; opacity: 0.85; }

    /* COMPACT SUB-KPIS GRID (FLAT DESIGN, NO NESTED PANELS) */
    .sub-kpi-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-bottom: 16px; }
    @media (max-width: 1024px) { .sub-kpi-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 640px) { .sub-kpi-grid { grid-template-columns: repeat(2, 1fr); } }

    .sub-kpi-item { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 8px; background: #ffffff; border: 1px solid #e2e8f0; }
    .sub-kpi-icon { width: 30px; height: 30px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
    .sub-kpi-label { font-size: 0.7rem; font-weight: 700; color: #64748b; }
    .sub-kpi-val { font-size: 0.98rem; font-weight: 900; font-family: monospace; color: #0f172a; }

    /* CHARTS LAYOUT */
    .charts-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px; }
    .charts-grid-3 { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 12px; margin-bottom: 16px; }
    @media (max-width: 1024px) { .charts-grid-2, .charts-grid-3 { grid-template-columns: 1fr; } }

    .chart-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; }
    .chart-header { font-size: 0.82rem; font-weight: 800; color: #0f172a; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
    .chart-wrapper { position: relative; height: 210px; width: 100%; }

    /* ALERTS & TABLES */
    .alerts-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px; }
    @media (max-width: 1024px) { .alerts-grid { grid-template-columns: 1fr; } }
    .alert-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
    .alert-card-header { padding: 10px 14px; border-bottom: 1px solid #e2e8f0; font-weight: 800; font-size: 0.8rem; display: flex; align-items: center; gap: 6px; }
    .alert-item { padding: 8px 14px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-size: 0.78rem; }
    .alert-item:last-child { border-bottom: none; }

    .tables-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    @media (max-width: 1024px) { .tables-grid { grid-template-columns: 1fr; } }
    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
    .table-header { padding: 10px 14px; border-bottom: 1px solid #e2e8f0; font-weight: 800; font-size: 0.8rem; display: flex; align-items: center; gap: 6px; background: #f8fafc; }
    .clean-table { width: 100%; border-collapse: collapse; text-align: start; font-size: 0.78rem; }
    .clean-table th { padding: 8px 12px; background: #ffffff; color: #64748b; font-weight: 700; border-bottom: 1px solid #e2e8f0; font-size: 0.68rem; }
    .clean-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; font-weight: 600; }

    .empty-box { padding: 20px 12px; text-align: center; color: #94a3b8; font-weight: 600; font-size: 0.78rem; }
</style>

<div class="erp-dashboard" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">

    <!-- Top Header Bar -->
    <div class="dash-header-bar">
        <h2 class="dash-title">
            <i class="ph-duotone ph-chart-pie-slice text-sky-600" style="font-size: 1.6rem;"></i>
            <?= $t['dashboard_title'] ?>
        </h2>

        <form action="/ERP/dashboard" method="GET" class="filter-card">
            <span style="font-weight: 800; font-size: 0.78rem; color: #0284c7;"><?= $t['date_filters'] ?>:</span>
            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($applied_filters['start_date'] ?? date('Y-m-01')) ?>">
            <span style="font-weight: 700; color: #64748b; font-size: 0.75rem;"><?= $t['to'] ?></span>
            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($applied_filters['end_date'] ?? date('Y-m-t')) ?>">
            <button type="submit" class="btn-submit-filter"><i class="ph-bold ph-arrows-clockwise"></i> <?= $t['apply_filters'] ?></button>
        </form>
    </div>

    <!-- Quick Action Buttons -->
    <div class="quick-actions-wrap">
        <?php foreach ($shortcuts as $action): ?>
            <a href="<?= $action['link'] ?>" class="action-card">
                <div class="action-icon" style="color: <?= $action['color'] ?>; background: <?= $action['color'] ?>15;"><i class="ph-bold <?= $action['icon'] ?>"></i></div>
                <span><?= $isRtl ? $action['title_ar'] : $action['title_en'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- 1. Hero Financial KPIs -->
    <div class="hero-kpi-grid">
        <div class="hero-card hero-card-sales">
            <div class="hero-title"><?= $t['total_sales'] ?></div>
            <div class="hero-amount"><?= number_format($kpis['total_sales'] ?? 0, 2) ?> <span class="hero-curr"><?= htmlspecialchars($currency) ?></span></div>
        </div>

        <div class="hero-card hero-card-purchases">
            <div class="hero-title"><?= $t['total_purchases'] ?></div>
            <div class="hero-amount"><?= number_format($kpis['total_purchases'] ?? 0, 2) ?> <span class="hero-curr"><?= htmlspecialchars($currency) ?></span></div>
        </div>

        <div class="hero-card hero-card-expenses">
            <div class="hero-title"><?= $t['expenses'] ?></div>
            <div class="hero-amount"><?= number_format($kpis['expenses'] ?? 0, 2) ?> <span class="hero-curr"><?= htmlspecialchars($currency) ?></span></div>
        </div>

        <div class="hero-card hero-card-profit">
            <div class="hero-title"><?= $t['net_profit'] ?></div>
            <div class="hero-amount"><?= number_format($kpis['net_profit'] ?? 0, 2) ?> <span class="hero-curr"><?= htmlspecialchars($currency) ?></span></div>
        </div>
    </div>

    <!-- 2. Financial & Asset Sub-KPIs Strip -->
    <div class="sub-kpi-grid">
        <div class="sub-kpi-item">
            <div class="sub-kpi-icon" style="background:#e0f2fe; color:#0284c7;"><i class="ph-bold ph-bank"></i></div>
            <div>
                <div class="sub-kpi-label"><?= $t['cash_balance'] ?></div>
                <div class="sub-kpi-val"><?= number_format($kpis['cash_balance'] ?? 0, 2) ?></div>
            </div>
        </div>

        <div class="sub-kpi-item">
            <div class="sub-kpi-icon" style="background:#ecfdf5; color:#10b981;"><i class="ph-bold ph-arrow-down-left"></i></div>
            <div>
                <div class="sub-kpi-label"><?= $t['receivables'] ?></div>
                <div class="sub-kpi-val" style="color:#10b981;"><?= number_format($kpis['receivables'] ?? 0, 2) ?></div>
            </div>
        </div>

        <div class="sub-kpi-item">
            <div class="sub-kpi-icon" style="background:#fef2f2; color:#ef4444;"><i class="ph-bold ph-arrow-up-right"></i></div>
            <div>
                <div class="sub-kpi-label"><?= $t['payables'] ?></div>
                <div class="sub-kpi-val" style="color:#ef4444;"><?= number_format(abs($kpis['payables'] ?? 0), 2) ?></div>
            </div>
        </div>

        <div class="sub-kpi-item">
            <div class="sub-kpi-icon" style="background:#f3e8ff; color:#9333ea;"><i class="ph-bold ph-package"></i></div>
            <div>
                <div class="sub-kpi-label"><?= $t['inventory_value'] ?></div>
                <div class="sub-kpi-val"><?= number_format($kpis['inventory_value'] ?? 0, 2) ?></div>
            </div>
        </div>

        <div class="sub-kpi-item">
            <div class="sub-kpi-icon" style="background:#fffbeb; color:#d97706;"><i class="ph-bold ph-buildings"></i></div>
            <div>
                <div class="sub-kpi-label"><?= $t['fixed_assets'] ?></div>
                <div class="sub-kpi-val"><?= number_format($kpis['fixed_assets_val'] ?? 0, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- 3. Primary Charts Moved UP (Sales & Cash Flow) -->
    <div class="charts-grid-2">
        <div class="chart-card">
            <div class="chart-header">
                <span><i class="ph-bold ph-chart-line-up text-emerald-500"></i> <?= $t['sales_trend'] ?></span> 
                <span style="color: #64748b; font-size: 0.72rem; font-family: monospace;"><?= htmlspecialchars($currency) ?></span>
            </div>
            <div class="chart-wrapper"><canvas id="salesChart"></canvas></div>
        </div>

        <div class="chart-card">
            <div class="chart-header">
                <span><i class="ph-bold ph-arrows-down-up text-sky-500"></i> <?= $t['cash_flow'] ?></span> 
                <span style="color: #64748b; font-size: 0.72rem; font-family: monospace;"><?= htmlspecialchars($currency) ?></span>
            </div>
            <div class="chart-wrapper"><canvas id="cashFlowChart"></canvas></div>
        </div>
    </div>

    <!-- 4. Operations, Projects, CRM & HR Sub-KPIs Strip -->
    <div class="sub-kpi-grid">
        <div class="sub-kpi-item">
            <div class="sub-kpi-icon" style="background:#e0f2fe; color:#0284c7;"><i class="ph-bold ph-list-checks"></i></div>
            <div>
                <div class="sub-kpi-label"><?= $t['active_projects'] ?></div>
                <div class="sub-kpi-val" style="color:#0284c7;"><?= number_format($kpis['active_projects_count'] ?? 0) ?></div>
            </div>
        </div>

        <div class="sub-kpi-item">
            <div class="sub-kpi-icon" style="background:#e0e7ff; color:#4338ca;"><i class="ph-bold ph-users-three"></i></div>
            <div>
                <div class="sub-kpi-label"><?= $t['crm_leads'] ?></div>
                <div class="sub-kpi-val"><?= number_format($leadsTotal) ?> (<?= $conversionRate ?>%)</div>
            </div>
        </div>

        <div class="sub-kpi-item">
            <div class="sub-kpi-icon" style="background:#e0e7ff; color:#4338ca;"><i class="ph-bold ph-users"></i></div>
            <div>
                <div class="sub-kpi-label"><?= $t['active_staff'] ?></div>
                <div class="sub-kpi-val"><?= number_format($kpis['active_employees'] ?? 0) ?></div>
            </div>
        </div>

        <div class="sub-kpi-item">
            <div class="sub-kpi-icon" style="background:#f1f5f9; color:#334155;"><i class="ph-bold ph-money"></i></div>
            <div>
                <div class="sub-kpi-label"><?= $t['monthly_payroll'] ?></div>
                <div class="sub-kpi-val"><?= number_format($kpis['payroll_monthly_net'] ?? 0, 2) ?></div>
            </div>
        </div>

        <div class="sub-kpi-item">
            <div class="sub-kpi-icon" style="background:#ecfdf5; color:#10b981;"><i class="ph-bold ph-piggy-bank"></i></div>
            <div>
                <div class="sub-kpi-label"><?= $t['petty_cash'] ?></div>
                <div class="sub-kpi-val" style="color:#10b981;"><?= number_format($kpis['petty_cash_remaining'] ?? 0, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- 5. Secondary Charts (Purchases, Expense Breakdown, CRM Pipeline) -->
    <div class="charts-grid-3">
        <div class="chart-card">
            <div class="chart-header">
                <span><i class="ph-bold ph-chart-bar text-amber-500"></i> <?= $t['purch_exp_trend'] ?></span> 
                <span style="color: #64748b; font-size: 0.72rem; font-family: monospace;"><?= htmlspecialchars($currency) ?></span>
            </div>
            <div class="chart-wrapper"><canvas id="purchChart"></canvas></div>
        </div>

        <div class="chart-card">
            <div class="chart-header">
                <span><i class="ph-bold ph-chart-pie-slice text-rose-500"></i> <?= $t['expense_breakdown'] ?></span>
            </div>
            <div class="chart-wrapper"><canvas id="expenseChart"></canvas></div>
        </div>

        <div class="chart-card">
            <div class="chart-header">
                <span><i class="ph-bold ph-funnel text-indigo-500"></i> <?= $t['leads_breakdown'] ?></span>
            </div>
            <div class="chart-wrapper"><canvas id="leadsChart"></canvas></div>
        </div>
    </div>

    <!-- 6. Critical Risk Alerts -->
    <div class="alerts-grid">
        <div class="alert-card">
            <div class="alert-card-header" style="color: #dc2626; background: #fef2f2;">
                <i class="ph-bold ph-clock-counter-clockwise"></i> <?= $t['overdue_invoices'] ?>
            </div>
            <?php if (empty($alerts['overdue_invoices'])): ?>
                <div class="empty-box"><?= $t['no_data'] ?></div>
            <?php else: foreach ($alerts['overdue_invoices'] as $inv): ?>
                <div class="alert-item">
                    <div>
                        <div style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($inv['invoice_number'] ?? '') ?></div>
                        <div style="font-size: 0.72rem; color: #64748b;"><?= htmlspecialchars($isRtl ? ($inv['name_ar'] ?? '') : ($inv['name_en'] ?? '')) ?></div>
                    </div>
                    <div style="text-align: end;">
                        <div style="font-weight: 900; font-family: monospace; color: #dc2626;"><?= number_format($inv['remaining'] ?? 0, 2) ?></div>
                        <div style="font-size: 0.68rem; color: #94a3b8; font-weight: 700;"><?= $inv['delay_days'] ?? 0 ?> <?= $t['days'] ?></div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <div class="alert-card">
            <div class="alert-card-header" style="color: #d97706; background: #fffbeb;">
                <i class="ph-bold ph-file-warning"></i> <?= $t['expiring_docs'] ?>
            </div>
            <?php if (empty($alerts['expiring_docs'])): ?>
                <div class="empty-box"><?= $t['no_data'] ?></div>
            <?php else: foreach ($alerts['expiring_docs'] as $doc): ?>
                <div class="alert-item">
                    <div>
                        <div style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($doc['title_ar'] ?? 'Document') ?></div>
                        <div style="font-size: 0.72rem; color: #64748b;"><?= htmlspecialchars($isRtl ? ($doc['name_ar'] ?? '') : ($doc['name_en'] ?? '')) ?></div>
                    </div>
                    <div style="text-align: end;">
                        <div style="font-weight: 800; font-family: monospace; color: #d97706;"><?= htmlspecialchars($doc['expiry_date']) ?></div>
                        <div style="font-size: 0.68rem; color: #94a3b8; font-weight: 700;"><?= $doc['days_left'] ?? 0 ?> <?= $t['days'] ?></div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <div class="alert-card">
            <div class="alert-card-header" style="color: #4338ca; background: #e0e7ff;">
                <i class="ph-bold ph-package"></i> <?= $t['low_stock'] ?>
            </div>
            <?php if (empty($alerts['low_stock_products'])): ?>
                <div class="empty-box"><?= $t['no_data'] ?></div>
            <?php else: foreach ($alerts['low_stock_products'] as $p): ?>
                <div class="alert-item">
                    <div>
                        <div style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($isRtl ? ($p['name_ar'] ?? '') : ($p['name_en'] ?? '')) ?></div>
                        <div style="font-size: 0.72rem; font-family: monospace; color: #4338ca;"><?= htmlspecialchars($p['sku']) ?></div>
                    </div>
                    <div style="text-align: end;">
                        <span style="background: #fee2e2; color: #dc2626; padding: 2px 6px; border-radius: 4px; font-weight: 800; font-size: 0.7rem;">Limit: <?= number_format($p['reorder_level'], 1) ?></span>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- 7. Operational Tables -->
    <div class="tables-grid">
        <div class="table-card">
            <div class="table-header" style="color: #059669;">
                <i class="ph-bold ph-trophy"></i> <?= $t['top_products'] ?>
            </div>
            <div style="overflow-x: auto;">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: end;">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($tables['top_products'])): ?>
                            <tr><td colspan="3" class="empty-box"><?= $t['no_data'] ?></td></tr>
                        <?php else: foreach($tables['top_products'] as $prod): ?>
                            <tr>
                                <td style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($isRtl ? ($prod['name_ar'] ?? '') : ($prod['name_en'] ?? '')) ?></td>
                                <td style="text-align: center;"><span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: 800; font-family: monospace;"><?= number_format($prod['qty'] ?? 0) ?></span></td>
                                <td style="text-align: end; font-weight: 900; font-family: monospace; color: #059669;"><?= number_format($prod['revenue'] ?? 0, 2) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header" style="color: #0284c7;">
                <i class="ph-bold ph-list-checks"></i> <?= $t['active_projects_tbl'] ?>
            </div>
            <div style="overflow-x: auto;">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th style="text-align: center;"><?= $t['progress'] ?></th>
                            <th style="text-align: end;">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($tables['active_projects'])): ?>
                            <tr><td colspan="3" class="empty-box"><?= $t['no_data'] ?></td></tr>
                        <?php else: foreach($tables['active_projects'] as $prj): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($isRtl ? ($prj['name_ar'] ?? '') : ($prj['name_en'] ?? '')) ?></div>
                                    <div style="font-size: 0.7rem; font-family: monospace; color: #0284c7;"><?= htmlspecialchars($prj['code']) ?></div>
                                </td>
                                <td style="text-align: center;">
                                    <span style="background: #e0f2fe; color: #0284c7; padding: 2px 6px; border-radius: 99px; font-weight: 800; font-size: 0.7rem; font-family: monospace;"><?= number_format($prj['progress_percent'], 0) ?>%</span>
                                </td>
                                <td style="text-align: end; font-weight: 900; font-family: monospace; color: #0f172a;"><?= number_format($prj['contract_value'], 2) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header" style="color: #4338ca;">
                <i class="ph-bold ph-book-bookmark"></i> <?= $t['recent_trx'] ?>
            </div>
            <div style="overflow-x: auto;">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th>Entry #</th>
                            <th>Details</th>
                            <th style="text-align: end;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($tables['recent_transactions'])): ?>
                            <tr><td colspan="3" class="empty-box"><?= $t['no_data'] ?></td></tr>
                        <?php else: foreach($tables['recent_transactions'] as $trx): ?>
                            <tr>
                                <td style="font-weight: 800; font-family: monospace; color: #4338ca;"><?= htmlspecialchars($trx['entry_number']) ?></td>
                                <td style="font-size: 0.78rem; color: #475569; max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($trx['details']) ?></td>
                                <td style="text-align: end; font-weight: 900; font-family: monospace; color: #0f172a;"><?= number_format($trx['amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Chart === 'undefined') return;

    let chartData = {};
    try {
        chartData = <?= json_encode($charts ?? [], JSON_UNESCAPED_UNICODE) ?> || {};
    } catch(e) { chartData = {}; }

    if (Array.isArray(chartData) || typeof chartData !== 'object' || chartData === null) chartData = {};

    const labels = (Array.isArray(chartData.labels) && chartData.labels.length > 0) ? chartData.labels : ['M1', 'M2', 'M3', 'M4', 'M5', 'M6'];
    const salesData = (Array.isArray(chartData.sales) && chartData.sales.length > 0) ? chartData.sales : [0,0,0,0,0,0];
    const purchasesData = (Array.isArray(chartData.purchases) && chartData.purchases.length > 0) ? chartData.purchases : [0,0,0,0,0,0];
    const expensesData = (Array.isArray(chartData.expenses) && chartData.expenses.length > 0) ? chartData.expenses : [0,0,0,0,0,0];
    const cashIn = (Array.isArray(chartData.cash_in) && chartData.cash_in.length > 0) ? chartData.cash_in : [0,0,0,0,0,0];
    const cashOut = (Array.isArray(chartData.cash_out) && chartData.cash_out.length > 0) ? chartData.cash_out : [0,0,0,0,0,0];

    Chart.defaults.font.family = "<?= $isRtl ? 'Cairo, sans-serif' : 'Inter, sans-serif' ?>";
    Chart.defaults.color = '#64748b';
    Chart.defaults.plugins.tooltip.padding = 8;
    Chart.defaults.plugins.tooltip.cornerRadius = 6;

    function createGradient(ctx, colorStart, colorEnd) {
        const gradient = ctx.createLinearGradient(0, 0, 0, 200);
        gradient.addColorStop(0, colorStart);
        gradient.addColorStop(1, colorEnd);
        return gradient;
    }

    const yScales = { y: { beginAtZero: true, suggestedMax: 100, grid: { borderDash: [4, 4] } }, x: { grid: { display: false } } };

    function processDoughnut(dataObj, defaultColors) {
        if (!dataObj || typeof dataObj !== 'object' || Array.isArray(dataObj)) dataObj = {};
        const keys = Object.keys(dataObj);
        const vals = Object.values(dataObj).map(v => Number(v) || 0);
        const sum = vals.reduce((a, b) => a + b, 0);

        if (sum === 0 || keys.length === 0) {
            return { labels: ['<?= $t["no_data"] ?>'], data: [1], colors: ['#cbd5e1'], tooltipDisable: true };
        }
        return { labels: keys, data: vals, colors: defaultColors, tooltipDisable: false };
    }

    // Sales Chart
    try {
        const ctxSales = document.getElementById('salesChart');
        if (ctxSales) {
            new Chart(ctxSales.getContext('2d'), {
                type: 'line',
                data: { labels: labels, datasets: [{ label: 'Sales', data: salesData, borderColor: '#10b981', backgroundColor: createGradient(ctxSales.getContext('2d'), 'rgba(16, 185, 129, 0.25)', 'rgba(16, 185, 129, 0)'), fill: true, tension: 0.4, borderWidth: 3, pointRadius: 3 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: yScales }
            });
        }
    } catch(e) {}

    // Cash Flow Chart
    try {
        const ctxCash = document.getElementById('cashFlowChart');
        if (ctxCash) {
            new Chart(ctxCash.getContext('2d'), {
                type: 'bar',
                data: { labels: labels, datasets: [{ label: '<?= $t["cash_in"] ?>', data: cashIn, backgroundColor: '#0ea5e9', borderRadius: 4 }, { label: '<?= $t["cash_out"] ?>', data: cashOut, backgroundColor: '#ef4444', borderRadius: 4 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: yScales }
            });
        }
    } catch(e) {}

    // Purchases Chart
    try {
        const ctxPurch = document.getElementById('purchChart');
        if (ctxPurch) {
            new Chart(ctxPurch.getContext('2d'), {
                type: 'bar',
                data: { labels: labels, datasets: [{ type: 'bar', label: 'Purchases', data: purchasesData, backgroundColor: '#f59e0b', borderRadius: 4 }, { type: 'line', label: 'Expenses', data: expensesData, borderColor: '#ef4444', borderWidth: 2, borderDash: [5, 5], backgroundColor: 'transparent', tension: 0.4, pointRadius: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: yScales }
            });
        }
    } catch(e) {}

    // Expense Breakdown
    try {
        const ctxExp = document.getElementById('expenseChart');
        if (ctxExp) {
            const d = processDoughnut(chartData.expense_categories, ['#ef4444', '#f59e0b', '#0ea5e9', '#8b5cf6', '#10b981', '#64748b']);
            new Chart(ctxExp.getContext('2d'), {
                type: 'doughnut',
                data: { labels: d.labels, datasets: [{ data: d.data, backgroundColor: d.colors, borderWidth: 2, borderColor: '#ffffff' }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 8 } }, tooltip: { enabled: !d.tooltipDisable } } }
            });
        }
    } catch(e) {}

    // CRM Leads Chart
    try {
        const ctxLeads = document.getElementById('leadsChart');
        if (ctxLeads) {
            const d = processDoughnut(chartData.leads_status, ['#0284c7', '#f59e0b', '#10b981']);
            if (!d.tooltipDisable) { d.labels = ['<?= $t["new"] ?>', '<?= $t["contacted"] ?>', '<?= $t["converted"] ?>']; }
            new Chart(ctxLeads.getContext('2d'), {
                type: 'doughnut',
                data: { labels: d.labels, datasets: [{ data: d.data, backgroundColor: d.colors, borderWidth: 2, borderColor: '#ffffff' }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 8 } }, tooltip: { enabled: !d.tooltipDisable } } }
            });
        }
    } catch(e) {}
});
</script>