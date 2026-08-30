<?php
//resources/views/sales/dashboard/index.php
if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isRtl();
$currency = current_currency();

$t = [
    'ar' => [
        'title'            => 'مركز التحليلات وقيادة المبيعات',
        'desc'             => 'رؤية شاملة ومؤشرات أداء تنفيذية حية للقطاع المالي والعملاء والمناديب.',
        'export_excel'     => 'تصدير التقرير المالي التفصيلي (Excel)',
        'total_invoiced'   => 'إجمالي المبيعات الصادرة',
        'total_collected'  => 'إجمالي المقبوضات النقدية',
        'unpaid_balance'   => 'مستحقات ديون العملاء (A/R)',
        'collection_rate'  => 'نسبة التحصيل الفعلي',
        'total_returns'    => 'مرتجعات المبيعات',
        'open_quotes'      => 'عروض أسعار جارية',
        'active_contracts' => 'العقود المفعلة',
        'active_customers' => 'سجل العملاء النشطين',
        'sales_vs_collected' => 'مقارنة المبيعات الصادرة بالتحصيل الفعلي (آخر 6 أشهر)',
        'invoice_status'   => 'توزيع حالات الفواتير',
        'rep_performance'  => 'أعلى 5 مناديب تحقيقاً للإيراد',
        'quote_pipeline'   => 'حالات ومراحل عروض الأسعار',
        'recent_invoices'  => 'أحدث الفواتير المسجلة',
        'top_products'     => 'الأصناف الأكثر مبيعاً وإيراداً',
        'top_reps'         => 'ترتيب أداء المناديب',
        'doc_no'           => 'رقم الفاتورة',
        'customer'         => 'العميل',
        'amount'           => 'المبلغ',
        'status'           => 'الحالة',
        'date'             => 'التاريخ',
        'view_all'         => 'عرض الكل',
        'no_data'          => 'لا توجد بيانات مسجلة حالياً.'
    ],
    'en' => [
        'title'            => 'Sales Analytics & Executive Control',
        'desc'             => 'Live operational insight and analytics for revenue, collections, and reps.',
        'export_excel'     => 'Export Detailed Financial Excel',
        'total_invoiced'   => 'Total Invoiced Revenue',
        'total_collected'  => 'Total Cash Collected',
        'unpaid_balance'   => 'Accounts Receivable',
        'collection_rate'  => 'Collection Rate %',
        'total_returns'    => 'Sales Credit Returns',
        'open_quotes'      => 'Active Quotations',
        'active_contracts' => 'Active Contracts',
        'active_customers' => 'Active Customers',
        'sales_vs_collected' => 'Invoiced vs Collected Revenue (6 Months)',
        'invoice_status'   => 'Invoices Status Ratio',
        'rep_performance'  => 'Top Sales Reps Revenue',
        'quote_pipeline'   => 'Quotations Pipeline Stage',
        'recent_invoices'  => 'Recent Invoices',
        'top_products'     => 'Top Selling Products',
        'top_reps'         => 'Sales Reps Leaderboard',
        'doc_no'           => 'Invoice No.',
        'customer'         => 'Customer',
        'amount'           => 'Amount',
        'status'           => 'Status',
        'date'             => 'Date',
        'view_all'         => 'View All',
        'no_data'          => 'No recorded data found.'
    ]
][$isRtl ? 'ar' : 'en'];

$kpis = $kpis ?? [];
$charts = $charts ?? [
    'labels' => [], 'sales_trend' => [], 'collected_trend' => [], 
    'status_distribution' => [0,0,0,0], 'rep_names' => [], 'rep_sales' => [], 'quotes_pipeline' => [0,0,0,0]
];
$recentInvoices = $recentInvoices ?? [];
$topProducts    = $topProducts ?? [];
$topReps        = $topReps ?? [];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<style>
    .dash-container { padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .dash-header-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 24px 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 28px; box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.03); }
    .dash-title-box { display: flex; align-items: center; gap: 16px; }
    .dash-icon-hero { width: 52px; height: 52px; border-radius: 14px; background: linear-gradient(135deg, #0f172a, #1e293b); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 8px 18px -4px rgba(15, 23, 42, 0.25); }
    .btn-export-excel { background: linear-gradient(135deg, #059669, #047857); color: #ffffff !important; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 10px; text-decoration: none; box-shadow: 0 6px 18px rgba(5, 150, 105, 0.25); transition: all 0.2s ease; cursor: pointer; }
    .btn-export-excel:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(5, 150, 105, 0.35); }
    .quick-modules-bar { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 28px; scrollbar-width: thin; }
    .nav-item-btn { background: #ffffff; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 10px; color: #334155; text-decoration: none; font-weight: 700; font-size: 0.85rem; white-space: nowrap; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
    .nav-item-btn:hover { background: #f8fafc; border-color: #0f172a; color: #0f172a; }
    .kpi-executive-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
    @media (max-width: 1024px) { .kpi-executive-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 640px) { .kpi-executive-grid { grid-template-columns: 1fr; } }
    .kpi-exec-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 22px 24px; box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.03); display: flex; flex-direction: column; justify-content: space-between; transition: 0.2s; }
    .kpi-exec-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -4px rgba(15, 23, 42, 0.08); }
    .kpi-top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .kpi-tag { font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
    .kpi-bubble-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
    .kpi-num-val { font-size: 1.8rem; font-weight: 900; font-family: monospace; color: #0f172a; letter-spacing: -0.5px; }
    .kpi-footer-note { font-size: 0.8rem; color: #64748b; font-weight: 600; margin-top: 10px; display: flex; align-items: center; gap: 6px; }
    .secondary-metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
    @media (max-width: 900px) { .secondary-metrics-grid { grid-template-columns: repeat(2, 1fr); } }
    .sm-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; }
    .sm-title { font-size: 0.78rem; font-weight: 700; color: #475569; }
    .sm-val { font-size: 1.1rem; font-weight: 900; font-family: monospace; color: #0f172a; }
    .charts-grid-4 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 28px; }
    @media (max-width: 1024px) { .charts-grid-4 { grid-template-columns: 1fr; } }
    .chart-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 24px; box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.03); }
    .panel-header-line { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; }
    .panel-header-title { font-size: 1.05rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px; }
    .tables-split-grid { display: grid; grid-template-columns: 3fr 2fr; gap: 24px; }
    @media (max-width: 1024px) { .tables-split-grid { grid-template-columns: 1fr; } }
    .dash-mini-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    .dash-mini-table th { text-align: start; padding: 12px 14px; color: #64748b; font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .dash-mini-table td { padding: 14px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .dash-mini-table tr:hover td { background: #f8fafc; }
    .badge-st { padding: 4px 10px; border-radius: 99px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
    .st-unpaid { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .st-partially { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
    .st-paid { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .st-draft { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
</style>

<div class="dash-container" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">

    <div class="dash-header-card">
        <div class="dash-title-box">
            <div class="dash-icon-hero"><i class="ph-duotone ph-presentation-chart"></i></div>
            <div>
                <h2 style="margin: 0; color: #0f172a; font-size: 1.7rem; font-weight: 900; letter-spacing: -0.5px;"><?= $t['title'] ?></h2>
                <p style="color: #64748b; margin: 4px 0 0 0; font-size: 0.9rem; font-weight: 500;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div>
            <a href="/ERP/sales/dashboard?export=excel" class="btn-export-excel"><i class="ph-bold ph-file-xls" style="font-size:1.3rem;"></i> <?= $t['export_excel'] ?></a>
        </div>
    </div>

    <div class="quick-modules-bar">
        <a href="/ERP/crm/leads" class="nav-item-btn"><i class="ph-bold ph-magnet text-sky-600"></i> <?= $isRtl ? 'الفرص البيعية' : 'Leads' ?></a>
        <a href="/ERP/sales/customers" class="nav-item-btn"><i class="ph-bold ph-users-three text-blue-600"></i> <?= $isRtl ? 'سجل العملاء' : 'Customers' ?></a>
        <a href="/ERP/sales/quotations" class="nav-item-btn"><i class="ph-bold ph-file-text text-purple-600"></i> <?= $isRtl ? 'عروض الأسعار' : 'Quotations' ?></a>
        <a href="/ERP/sales/orders" class="nav-item-btn"><i class="ph-bold ph-shopping-cart text-emerald-600"></i> <?= $isRtl ? 'أوامر البيع' : 'Sales Orders' ?></a>
        <a href="/ERP/inventory/delivery-notes" class="nav-item-btn"><i class="ph-bold ph-truck text-amber-600"></i> <?= $isRtl ? 'أذونات الصرف' : 'Delivery Notes' ?></a>
        <a href="/ERP/sales/invoices" class="nav-item-btn"><i class="ph-bold ph-receipt text-indigo-600"></i> <?= $isRtl ? 'فواتير المبيعات' : 'Invoices' ?></a>
        <a href="/ERP/sales/receipts" class="nav-item-btn"><i class="ph-bold ph-money text-emerald-600"></i> <?= $isRtl ? 'سندات القبض' : 'Receipts' ?></a>
        <a href="/ERP/sales/returns" class="nav-item-btn"><i class="ph-bold ph-arrow-u-up-left text-rose-600"></i> <?= $isRtl ? 'مرتجعات المبيعات' : 'Returns' ?></a>
        <a href="/ERP/sales/price-lists" class="nav-item-btn"><i class="ph-bold ph-tag text-indigo-600"></i> <?= $isRtl ? 'قوائم الأسعار' : 'Price Lists' ?></a>
        <a href="/ERP/sales/representatives" class="nav-item-btn"><i class="ph-bold ph-user-gear text-sky-600"></i> <?= $isRtl ? 'مناديب المبيعات' : 'Sales Reps' ?></a>
        <a href="/ERP/sales/statements" class="nav-item-btn"><i class="ph-bold ph-files text-purple-600"></i> <?= $isRtl ? 'كشوف الحسابات' : 'Statements' ?></a>
        <a href="/ERP/sales/contracts" class="nav-item-btn"><i class="ph-bold ph-article text-green-600"></i> <?= $isRtl ? 'عقود المبيعات' : 'Contracts' ?></a>
    </div>

    <!-- Executive Primary KPIs -->
    <div class="kpi-executive-grid">
        <div class="kpi-exec-card">
            <div class="kpi-top-row">
                <span class="kpi-tag"><?= $t['total_invoiced'] ?></span>
                <div class="kpi-bubble-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-duotone ph-receipt"></i></div>
            </div>
            <div class="kpi-num-val"><?= number_format($kpis['total_invoiced'] ?? 0, 2) ?> <span style="font-size:0.8rem; font-weight:700; color:#64748b;"><?= htmlspecialchars($currency) ?></span></div>
            <div class="kpi-footer-note"><i class="ph-bold ph-shield-check text-blue-600"></i> <?= $isRtl ? 'قيمة المبيعات المعتمدة' : 'Confirmed Invoiced Value' ?></div>
        </div>

        <div class="kpi-exec-card">
            <div class="kpi-top-row">
                <span class="kpi-tag"><?= $t['total_collected'] ?></span>
                <div class="kpi-bubble-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-coins"></i></div>
            </div>
            <div class="kpi-num-val" style="color:#059669;"><?= number_format($kpis['total_collected'] ?? 0, 2) ?> <span style="font-size:0.8rem; font-weight:700; color:#64748b;"><?= htmlspecialchars($currency) ?></span></div>
            <div class="kpi-footer-note" style="color:#059669;"><i class="ph-bold ph-check-circle"></i> <?= $isRtl ? 'التحصيل النقدي والبنكي الفعلي' : 'Actual Collected Cash/Bank' ?></div>
        </div>

        <div class="kpi-exec-card">
            <div class="kpi-top-row">
                <span class="kpi-tag"><?= $t['unpaid_balance'] ?></span>
                <div class="kpi-bubble-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-warning-circle"></i></div>
            </div>
            <div class="kpi-num-val" style="color:#dc2626;"><?= number_format($kpis['unpaid_balance'] ?? 0, 2) ?> <span style="font-size:0.8rem; font-weight:700; color:#64748b;"><?= htmlspecialchars($currency) ?></span></div>
            <div class="kpi-footer-note" style="color:#dc2626;"><i class="ph-bold ph-clock"></i> <?= $isRtl ? 'ذمم العملاء المتبقية (A/R)' : 'Pending Customer Receivables' ?></div>
        </div>

        <div class="kpi-exec-card">
            <div class="kpi-top-row">
                <span class="kpi-tag"><?= $t['collection_rate'] ?></span>
                <div class="kpi-bubble-icon" style="background:#fdf4ff; color:#c084fc;"><i class="ph-duotone ph-percent"></i></div>
            </div>
            <div class="kpi-num-val" style="color:#9333ea;"><?= $kpis['collection_rate'] ?? 0 ?>%</div>
            <div class="kpi-footer-note"><i class="ph-bold ph-chart-line-up text-purple-600"></i> <?= $isRtl ? 'نسبة المبالغ المسددة للإجمالي' : 'Collection Percentage' ?></div>
        </div>
    </div>

    <!-- Secondary Metrics Strip -->
    <div class="secondary-metrics-grid">
        <div class="sm-card">
            <span class="sm-title"><?= $t['total_returns'] ?></span>
            <span class="sm-val" style="color:#d97706;"><?= number_format($kpis['total_returns'] ?? 0, 2) ?> <?= htmlspecialchars($currency) ?></span>
        </div>
        <div class="sm-card">
            <span class="sm-title"><?= $t['open_quotes'] ?></span>
            <span class="sm-val"><?= number_format($kpis['open_quotes_val'] ?? 0, 2) ?> <?= htmlspecialchars($currency) ?></span>
        </div>
        <div class="sm-card">
            <span class="sm-title"><?= $t['active_contracts'] ?></span>
            <span class="sm-val" style="color:#2563eb;"><?= number_format($kpis['active_contracts'] ?? 0) ?> <?= $isRtl ? 'عقود' : 'Contracts' ?></span>
        </div>
        <div class="sm-card">
            <span class="sm-title"><?= $t['active_customers'] ?></span>
            <span class="sm-val" style="color:#059669;"><?= number_format($kpis['active_customers'] ?? 0) ?> <?= $isRtl ? 'عميل' : 'Customers' ?></span>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid-4">
        <div class="chart-panel-card">
            <div class="panel-header-line">
                <div class="panel-header-title"><i class="ph-duotone ph-trend-up text-blue-600"></i> <?= $t['sales_vs_collected'] ?></div>
            </div>
            <div style="min-height: 260px; position: relative;"><canvas id="salesVSCollectedChart"></canvas></div>
        </div>

        <div class="chart-panel-card">
            <div class="panel-header-line">
                <div class="panel-header-title"><i class="ph-duotone ph-chart-donut text-purple-600"></i> <?= $t['invoice_status'] ?></div>
            </div>
            <div style="min-height: 260px; position: relative; display: flex; align-items: center; justify-content: center;"><canvas id="statusChart"></canvas></div>
        </div>

        <div class="chart-panel-card">
            <div class="panel-header-line">
                <div class="panel-header-title"><i class="ph-duotone ph-user-gear text-sky-600"></i> <?= $t['rep_performance'] ?></div>
            </div>
            <div style="min-height: 260px; position: relative;"><canvas id="repsChart"></canvas></div>
        </div>

        <div class="chart-panel-card">
            <div class="panel-header-line">
                <div class="panel-header-title"><i class="ph-duotone ph-funnel text-emerald-600"></i> <?= $t['quote_pipeline'] ?></div>
            </div>
            <div style="min-height: 260px; position: relative;"><canvas id="quotePipelineChart"></canvas></div>
        </div>
    </div>

    <!-- Tables Split -->
    <div class="tables-split-grid">
        <div class="chart-panel-card">
            <div class="panel-header-line">
                <div class="panel-header-title"><i class="ph-duotone ph-receipt text-indigo-600"></i> <?= $t['recent_invoices'] ?></div>
                <a href="/ERP/sales/invoices" style="font-size: 0.85rem; font-weight: 800; color: #2563eb; text-decoration: none;"><?= $t['view_all'] ?> <i class="ph-bold <?= $isRtl ? 'ph-arrow-left' : 'ph-arrow-right' ?>"></i></a>
            </div>
            <div style="overflow-x: auto;">
                <table class="dash-mini-table">
                    <thead>
                        <tr>
                            <th><?= $t['doc_no'] ?></th>
                            <th><?= $t['customer'] ?></th>
                            <th><?= $t['date'] ?></th>
                            <th style="text-align: end;"><?= $t['amount'] ?> (<?= htmlspecialchars($currency) ?>)</th>
                            <th style="text-align: center;"><?= $t['status'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentInvoices)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 30px; color: #94a3b8;"><?= $t['no_data'] ?></td></tr>
                        <?php else: foreach($recentInvoices as $inv): ?>
                            <tr>
                                <td style="font-weight: 800; color: #2563eb; font-family: monospace;">
                                    <a href="/ERP/sales/invoices/<?= $inv->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($inv->invoice_number) ?></a>
                                </td>
                                <td style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($inv->customer_name ?? '---') ?></td>
                                <td><?= htmlspecialchars($inv->issue_date) ?></td>
                                <td style="text-align: end; font-weight: 900; font-family: monospace; color: #0f172a;"><?= number_format($inv->total_amount, 2) ?></td>
                                <td style="text-align: center;">
                                    <?php 
                                        $st = strtolower($inv->status);
                                        $stClass = 'st-draft';
                                        if ($st === 'paid') $stClass = 'st-paid';
                                        if ($st === 'partially_paid') $stClass = 'st-partially';
                                        if ($st === 'unpaid') $stClass = 'st-unpaid';
                                    ?>
                                    <span class="badge-st <?= $stClass ?>"><?= strtoupper($st) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 24px;">
            <div class="chart-panel-card">
                <div class="panel-header-line">
                    <div class="panel-header-title"><i class="ph-duotone ph-trophy text-amber-500"></i> <?= $t['top_products'] ?></div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="dash-mini-table">
                        <thead>
                            <tr>
                                <th><?= $isRtl ? 'الصنف' : 'Item' ?></th>
                                <th style="text-align: center;"><?= $isRtl ? 'الكمية' : 'Qty' ?></th>
                                <th style="text-align: end;"><?= $isRtl ? 'الإيراد' : 'Revenue' ?> (<?= htmlspecialchars($currency) ?>)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topProducts)): ?>
                                <tr><td colspan="3" style="text-align: center; padding: 20px; color: #94a3b8;"><?= $t['no_data'] ?></td></tr>
                            <?php else: foreach($topProducts as $p): ?>
                                <tr>
                                    <td style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($p->description) ?></td>
                                    <td style="text-align: center; font-weight: 800; font-family: monospace; color: #2563eb;"><?= number_format($p->total_qty, 2) ?></td>
                                    <td style="text-align: end; font-weight: 900; font-family: monospace; color: #059669;"><?= number_format($p->total_revenue, 2) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="chart-panel-card">
                <div class="panel-header-line">
                    <div class="panel-header-title"><i class="ph-duotone ph-user-gear text-sky-600"></i> <?= $t['top_reps'] ?></div>
                    <a href="/ERP/sales/representatives" style="font-size: 0.82rem; font-weight: 800; color: #0284c7; text-decoration: none;"><?= $t['view_all'] ?></a>
                </div>
                <div style="overflow-x: auto;">
                    <table class="dash-mini-table">
                        <thead>
                            <tr>
                                <th><?= $isRtl ? 'المندوب' : 'Sales Rep' ?></th>
                                <th style="text-align: center;"><?= $isRtl ? 'العمولة' : 'Commission' ?></th>
                                <th style="text-align: end;"><?= $isRtl ? 'المبيعات' : 'Sales' ?> (<?= htmlspecialchars($currency) ?>)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topReps)): ?>
                                <tr><td colspan="3" style="text-align: center; padding: 20px; color: #94a3b8;"><?= $t['no_data'] ?></td></tr>
                            <?php else: foreach($topReps as $rep): ?>
                                <tr>
                                    <td style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($rep->rep_name) ?></td>
                                    <td style="text-align: center; font-weight: 800; font-family: monospace; color: #0284c7;"><?= number_format($rep->commission_rate, 1) ?>%</td>
                                    <td style="text-align: end; font-weight: 900; font-family: monospace; color: #059669;"><?= number_format($rep->total_sales, 2) ?></td>
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
    const chartLabels    = <?= json_encode($charts['labels']) ?>;
    const salesData      = <?= json_encode($charts['sales_trend']) ?>;
    const collectedData  = <?= json_encode($charts['collected_trend']) ?>;
    const statusData     = <?= json_encode($charts['status_distribution']) ?>;
    const repNames       = <?= json_encode($charts['rep_names']) ?>;
    const repSales       = <?= json_encode($charts['rep_sales']) ?>;
    const qPipeline      = <?= json_encode($charts['quotes_pipeline']) ?>;
    const isRtl          = <?= json_encode($isRtl) ?>;

    Chart.defaults.font.family = isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif";

    const ctxTrend = document.getElementById('salesVSCollectedChart');
    if (ctxTrend) {
        const ctx = ctxTrend.getContext('2d');
        let grad1 = ctx.createLinearGradient(0, 0, 0, 220);
        grad1.addColorStop(0, 'rgba(37, 99, 235, 0.25)');
        grad1.addColorStop(1, 'rgba(37, 99, 235, 0)');

        let grad2 = ctx.createLinearGradient(0, 0, 0, 220);
        grad2.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
        grad2.addColorStop(1, 'rgba(16, 185, 129, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: isRtl ? 'إجمالي الفواتير الصادرة' : 'Total Invoiced Sales',
                        data: salesData,
                        borderColor: '#2563eb',
                        borderWidth: 3,
                        backgroundColor: grad1,
                        fill: true,
                        tension: 0.35
                    },
                    {
                        label: isRtl ? 'المقبوضات والتحصيل النقدى' : 'Total Collections',
                        data: collectedData,
                        borderColor: '#10b981',
                        borderWidth: 3,
                        backgroundColor: grad2,
                        fill: true,
                        tension: 0.35
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { usePointStyle: true, font: { weight: 'bold' } } } },
                scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f5f9' }, beginAtZero: true } }
            }
        });
    }

    const ctxStatus = document.getElementById('statusChart');
    if (ctxStatus) {
        new Chart(ctxStatus.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: isRtl ? ['غير مدفوع', 'مدفوع جزئياً', 'مدفوع بالكامل', 'مسودة'] : ['Unpaid', 'Partially Paid', 'Paid', 'Draft'],
                datasets: [{
                    data: statusData,
                    backgroundColor: ['#f43f5e', '#f59e0b', '#10b981', '#94a3b8'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12, font: { weight: 'bold' } } } }
            }
        });
    }

    const ctxReps = document.getElementById('repsChart');
    if (ctxReps) {
        new Chart(ctxReps.getContext('2d'), {
            type: 'bar',
            data: {
                labels: repNames.length > 0 ? repNames : [isRtl ? 'لا توجد بيانات' : 'No Data'],
                datasets: [{
                    label: isRtl ? 'إجمالي المبيعات' : 'Total Sales',
                    data: repSales.length > 0 ? repSales : [0],
                    backgroundColor: '#0284c7',
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { grid: { color: '#f1f5f9' }, beginAtZero: true }, y: { grid: { display: false } } }
            }
        });
    }

    const ctxPipeline = document.getElementById('quotePipelineChart');
    if (ctxPipeline) {
        new Chart(ctxPipeline.getContext('2d'), {
            type: 'bar',
            data: {
                labels: isRtl ? ['مسودة (Draft)', 'مرسلة (Sent)', 'مقبولة (Won)', 'مرفوضة (Lost)'] : ['Draft', 'Sent', 'Accepted', 'Rejected'],
                datasets: [{
                    label: isRtl ? 'عدد العروض' : 'Quotes Count',
                    data: qPipeline,
                    backgroundColor: ['#94a3b8', '#3b82f6', '#10b981', '#ef4444'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f5f9' }, beginAtZero: true } }
            }
        });
    }
});
</script>