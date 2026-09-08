<?php
// Path: resources/views/accounting/dashboard/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

// حماية المتغيرات (Safe Fallback)
$currency = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');

$kpis = $kpis ?? [
    'revenue' => 0, 'expenses' => 0, 'net_profit' => 0, 'net_margin' => 0,
    'drafts' => 0, 'total_assets' => 0, 'total_liabilities' => 0, 'total_equity' => 0,
    'cash_balance' => 0, 'working_capital' => 0, 'pending_recons' => 0
];

$charts = $charts ?? [
    'labels' => [], 'revenue' => [], 'expense' => [], 'profit_trend' => [],
    'cash_inflow' => [], 'cash_outflow' => [], 'expense_categories' => ['labels'=>[], 'data'=>[]],
    'tax_breakdown' => ['output_vat'=>0, 'input_vat'=>0, 'wht'=>0]
];

$recentEntries = $recentEntries ?? [];
$dbErrors = $dbErrors ?? [];

$t = [
    'ar' => [
        'title' => 'المركز المالي الشامل',
        'desc' => 'رؤية استراتيجية للربحية، الأصول، والتدفقات النقدية والضريبية.',
        'revenue' => 'إجمالي الإيرادات', 'expenses' => 'إجمالي المصروفات',
        'profit' => 'صافي ربح الفترة', 'cash' => 'السيولة والنقدية بالبنوك',
        'working_cap' => 'رأس المال العامل', 'margin' => 'هامش الربح',
        'c1' => '1. حركة الإيرادات والمصروفات (6 شهور)', 'c2' => '2. الهيكل المالي للأصول والخصوم',
        'c3' => '3. التدفق النقدي (مقبوضات/مدفوعات)', 'c4' => '4. توزيع بنود المصروفات',
        'c5' => '5. اتجاه نمو صافي الربح الشهري', 'c6' => '6. الموقف الضريبي المجمع',
        'recent_entries' => 'أحدث القيود المسجلة بالدفاتر', 'quick_hub' => 'التقارير والموديولات المحاسبية',
        'new_entry' => 'قيد جديد', 'export_excel' => 'تصدير لـ Excel', 'print' => 'طباعة التقرير',
        'no_entries' => 'لا توجد قيود مسجلة مؤخراً.',
        'drafts' => 'قيود مسودة:', 'pending_recons' => 'تسويات معلقة:', 'review' => 'مراجعة ←',
        'st_draft' => 'مسودة', 'st_posted' => 'مرحّل',
        'lbl_assets' => 'الأصول', 'lbl_liabilities' => 'الالتزامات', 'lbl_equity' => 'حقوق الملكية',
        'lbl_rev' => 'الإيرادات', 'lbl_exp' => 'المصروفات', 'lbl_inflow' => 'مقبوضات', 'lbl_outflow' => 'مدفوعات',
        'lbl_vat_out' => 'ضريبة مبيعات', 'lbl_vat_in' => 'ضريبة مشتريات', 'lbl_wht' => 'خصم وإضافة'
    ],
    'en' => [
        'title' => 'Financial Command Center',
        'desc' => 'Strategic view of profitability, assets, cash flows, and taxes.',
        'revenue' => 'Total Revenue', 'expenses' => 'Total Expenses',
        'profit' => 'Net Profit', 'cash' => 'Cash Balance in Banks',
        'working_cap' => 'Working Capital', 'margin' => 'Profit Margin',
        'c1' => '1. Revenue vs Expense Trend', 'c2' => '2. Capital Structure',
        'c3' => '3. Cash Inflow vs Outflow', 'c4' => '4. Expense Categories',
        'c5' => '5. Monthly Net Profit Growth', 'c6' => '6. Tax Exposure Breakdown',
        'recent_entries' => 'Recent Journal Entries', 'quick_hub' => 'Accounting Launchpad',
        'new_entry' => 'New Entry', 'export_excel' => 'Export Excel', 'print' => 'Print Report',
        'no_entries' => 'No recent entries found.',
        'drafts' => 'Draft Entries:', 'pending_recons' => 'Pending Recons:', 'review' => 'Review ←',
        'st_draft' => 'Draft', 'st_posted' => 'Posted',
        'lbl_assets' => 'Assets', 'lbl_liabilities' => 'Liabilities', 'lbl_equity' => 'Equity',
        'lbl_rev' => 'Revenues', 'lbl_exp' => 'Expenses', 'lbl_inflow' => 'Inflow', 'lbl_outflow' => 'Outflow',
        'lbl_vat_out' => 'Output VAT', 'lbl_vat_in' => 'Input VAT', 'lbl_wht' => 'WHT'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --dash-indigo: #4f46e5; --dash-indigo-dark: #3730a3; --dash-indigo-light: #e0e7ff;
        --dash-emerald: #10b981; --dash-emerald-bg: #ecfdf5;
        --dash-rose: #f43f5e; --dash-rose-bg: #fff1f2;
        --dash-amber: #d97706; --dash-amber-bg: #fef3c7;
        --dash-sky: #0284c7; --dash-sky-bg: #e0f2fe;
        --dash-violet: #8b5cf6; --dash-violet-bg: #f5f3ff;
        --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b;
    }

    .dash-wrapper { padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }

    .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; flex-wrap: wrap; gap: 16px; }
    .dash-title-box { display: flex; align-items: center; gap: 16px; }
    .dash-icon { width: 54px; height: 52px; background: var(--dash-indigo-light); color: var(--dash-indigo); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.9rem; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15); flex-shrink: 0; }
    .dash-title { margin: 0; color: var(--c-text-dark); font-size: 1.7rem; font-weight: 900; }
    
    .btn-actions-group { display: flex; gap: 10px; }
    .btn-excel { background: #059669; color: #ffffff !important; padding: 12px 20px; border-radius: 12px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.18); font-size: 0.88rem; }
    .btn-excel:hover { background: #047857; transform: translateY(-2px); }
    .btn-print { background: #0f172a; color: #ffffff !important; padding: 12px 20px; border-radius: 12px; font-weight: 800; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; font-size: 0.88rem;}
    .btn-print:hover { background: #1e293b; transform: translateY(-2px); }
    .btn-create-entry { background: linear-gradient(135deg, var(--dash-indigo), var(--dash-indigo-dark)); color: #ffffff !important; padding: 12px 22px; border-radius: 12px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25); font-size: 0.88rem;}
    .btn-create-entry:hover { transform: translateY(-2px); }

    .kpi-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1200px){ .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
    @media(max-width:640px){ .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
    
    .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; transition: all 0.2s; }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
    .kpi-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: currentColor; }
    .kpi-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .kpi-icon-box { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
    .kpi-label { font-size: 0.75rem; font-weight: 800; color: var(--c-text-muted); text-transform: uppercase;}
    .kpi-val { font-size: clamp(1.1rem, 1.4vw, 1.45rem); font-weight: 900; font-family: monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0; }

    .charts-row-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
    @media(max-width:1100px){ .charts-row-3 { grid-template-columns: repeat(2, 1fr); } }
    @media(max-width:768px){ .charts-row-3 { grid-template-columns: 1fr; } }

    .chart-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; flex-direction: column;}
    .card-title { margin: 0 0 16px 0; font-size: 0.95rem; font-weight: 900; color: var(--c-text-dark); display: flex; align-items: center; gap: 8px;}

    .bento-bottom { display: grid; grid-template-columns: 7fr 5fr; gap: 24px; margin-bottom: 28px; }
    @media(max-width:1024px){ .bento-bottom { grid-template-columns: 1fr; } }

    .hub-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .hub-item { display: flex; align-items: center; gap: 10px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-decoration: none; color: var(--c-text-dark); font-weight: 800; font-size: 0.85rem; transition: 0.2s; }
    .hub-item:hover { background: #ffffff; border-color: var(--dash-indigo); transform: translateX(<?= $isRtl ? '-3px' : '3px' ?>); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    .hub-icon { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }

    .dash-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .dash-table th { padding: 12px 14px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; border-bottom: 2px solid #e2e8f0; text-align: start; font-size: 0.75rem; text-transform: uppercase;}
    .dash-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    @media print {
        .nt-sidebar, header, nav, .dash-header .btn-actions-group, .hub-item { display: none !important; }
        body { background: #fff !important; }
        .dash-wrapper { max-width: 100% !important; padding:0 !important; margin: 0 !important;}
        .kpi-card, .chart-card { border: 1px solid #000 !important; box-shadow: none !important; break-inside: avoid; page-break-inside: avoid; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="dash-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <!-- Header Bar -->
    <div class="dash-header">
        <div class="dash-title-box">
            <div class="dash-icon"><i class="ph-duotone ph-chart-line-up"></i></div>
            <div>
                <h2 class="dash-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem; font-weight:600;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div class="btn-actions-group">
            <button type="button" onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
            <button type="button" onclick="exportDashboardExcel()" class="btn-excel"><i class="ph-bold ph-file-xls"></i> <?= $t['export_excel'] ?></button>
            <a href="/ERP/accounting/journal-entries/create" class="btn-create-entry"><i class="ph-bold ph-plus-circle"></i> <?= $t['new_entry'] ?></a>
        </div>
    </div>

    <!-- Alert for Database Issues if any -->
    <?php if (!empty($dbErrors)): ?>
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: bold; font-family: monospace;">
            <i class="ph-bold ph-warning-circle"></i> تنبيه: توجد جداول غير محدثة أو مفقودة في قاعدة البيانات مما قد يؤثر على بعض الأرقام:<br>
            <?php foreach($dbErrors as $err): ?>
                <div style="font-size: 0.8rem; margin-top: 4px;">- <?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- KPI Grid (6 Indicator Cards) -->
    <div class="kpi-grid">
        <div class="kpi-card" style="color: var(--dash-emerald);">
            <div class="kpi-top">
                <span class="kpi-label"><?= $t['revenue'] ?></span>
                <div class="kpi-icon-box" style="background: var(--dash-emerald-bg); color: var(--dash-emerald);"><i class="ph-duotone ph-trend-up"></i></div>
            </div>
            <p class="kpi-val" style="color: var(--dash-emerald);" title="<?= number_format($kpis['revenue'], 2) ?>"><?= number_format($kpis['revenue'], 2) ?> <span style="font-size:0.7rem; font-weight:normal;"><?= $currency ?></span></p>
        </div>

        <div class="kpi-card" style="color: var(--dash-rose);">
            <div class="kpi-top">
                <span class="kpi-label"><?= $t['expenses'] ?></span>
                <div class="kpi-icon-box" style="background: var(--dash-rose-bg); color: var(--dash-rose);"><i class="ph-duotone ph-trend-down"></i></div>
            </div>
            <p class="kpi-val" style="color: var(--dash-rose);" title="<?= number_format($kpis['expenses'], 2) ?>"><?= number_format($kpis['expenses'], 2) ?> <span style="font-size:0.7rem; font-weight:normal;"><?= $currency ?></span></p>
        </div>

        <div class="kpi-card" style="color: <?= $kpis['net_profit'] >= 0 ? 'var(--dash-indigo)' : 'var(--dash-rose)' ?>;">
            <div class="kpi-top">
                <span class="kpi-label"><?= $t['profit'] ?></span>
                <div class="kpi-icon-box" style="background: var(--dash-indigo-light); color: var(--dash-indigo);"><i class="ph-duotone ph-scales"></i></div>
            </div>
            <p class="kpi-val" style="color: <?= $kpis['net_profit'] >= 0 ? 'var(--dash-indigo)' : 'var(--dash-rose)' ?>;" title="<?= number_format($kpis['net_profit'], 2) ?>"><?= number_format($kpis['net_profit'], 2) ?> <span style="font-size:0.7rem; font-weight:normal;"><?= $currency ?></span></p>
        </div>

        <div class="kpi-card" style="color: var(--dash-sky);">
            <div class="kpi-top">
                <span class="kpi-label"><?= $t['cash'] ?></span>
                <div class="kpi-icon-box" style="background: var(--dash-sky-bg); color: var(--dash-sky);"><i class="ph-duotone ph-bank"></i></div>
            </div>
            <p class="kpi-val" style="color: var(--dash-sky);" title="<?= number_format($kpis['cash_balance'], 2) ?>"><?= number_format($kpis['cash_balance'], 2) ?> <span style="font-size:0.7rem; font-weight:normal;"><?= $currency ?></span></p>
        </div>

        <div class="kpi-card" style="color: var(--dash-amber);">
            <div class="kpi-top">
                <span class="kpi-label"><?= $t['working_cap'] ?></span>
                <div class="kpi-icon-box" style="background: var(--dash-amber-bg); color: var(--dash-amber);"><i class="ph-duotone ph-coins"></i></div>
            </div>
            <p class="kpi-val" style="color: var(--dash-amber);" title="<?= number_format($kpis['working_capital'], 2) ?>"><?= number_format($kpis['working_capital'], 2) ?> <span style="font-size:0.7rem; font-weight:normal;"><?= $currency ?></span></p>
        </div>

        <div class="kpi-card" style="color: var(--dash-violet);">
            <div class="kpi-top">
                <span class="kpi-label"><?= $t['margin'] ?></span>
                <div class="kpi-icon-box" style="background: var(--dash-violet-bg); color: var(--dash-violet);"><i class="ph-duotone ph-percent"></i></div>
            </div>
            <p class="kpi-val" style="color: var(--dash-violet);"><?= $kpis['net_margin'] ?>%</p>
        </div>
    </div>

    <!-- CHARTS ROW 1: 3 CHARTS SIDE-BY-SIDE -->
    <div class="charts-row-3">
        <div class="chart-card">
            <div class="card-title">
                <span><i class="ph-bold ph-chart-bar" style="color: var(--dash-indigo);"></i> <?= $t['c1'] ?></span>
            </div>
            <div style="flex:1; position: relative;">
                <canvas id="chartTrend"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="card-title">
                <span><i class="ph-bold ph-chart-pie" style="color: var(--dash-sky);"></i> <?= $t['c2'] ?></span>
            </div>
            <div style="flex:1; position: relative;">
                <canvas id="chartStruct"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="card-title">
                <span><i class="ph-bold ph-arrows-left-right" style="color: var(--dash-emerald);"></i> <?= $t['c3'] ?></span>
            </div>
            <div style="flex:1; position: relative;">
                <canvas id="chartCash"></canvas>
            </div>
        </div>
    </div>

    <!-- CHARTS ROW 2: 3 CHARTS SIDE-BY-SIDE -->
    <div class="charts-row-3">
        <div class="chart-card">
            <div class="card-title">
                <span><i class="ph-bold ph-pie-chart" style="color: var(--dash-rose);"></i> <?= $t['c4'] ?></span>
            </div>
            <div style="flex:1; position: relative;">
                <canvas id="chartExp"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="card-title">
                <span><i class="ph-bold ph-trend-up" style="color: var(--dash-indigo);"></i> <?= $t['c5'] ?></span>
            </div>
            <div style="flex:1; position: relative;">
                <canvas id="chartProfit"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="card-title">
                <span><i class="ph-bold ph-receipt" style="color: var(--dash-amber);"></i> <?= $t['c6'] ?></span>
            </div>
            <div style="flex:1; position: relative;">
                <canvas id="chartTax"></canvas>
            </div>
        </div>
    </div>

    <!-- Bento Bottom Section: Launchpad Hub & Activity -->
    <div class="bento-bottom">
        <!-- Quick Reports Launchpad Hub -->
        <div class="chart-card" style="background:#f8fafc;">
            <div class="card-title">
                <span><i class="ph-bold ph-rocket-launch" style="color: var(--dash-indigo);"></i> <?= $t['quick_hub'] ?></span>
            </div>
            
            <div class="hub-grid">
                <a href="/ERP/accounting/reports/income-statement" class="hub-item">
                    <div class="hub-icon" style="background:#e0e7ff; color:#4f46e5;"><i class="ph-bold ph-chart-line-up"></i></div>
                    <div><?= $isRtl ? 'قائمة الدخل (P&L)' : 'Income Statement' ?></div>
                </a>

                <a href="/ERP/accounting/reports/balance-sheet" class="hub-item">
                    <div class="hub-icon" style="background:#f5f3ff; color:#7c3aed;"><i class="ph-bold ph-scales"></i></div>
                    <div><?= $isRtl ? 'الميزانية العمومية' : 'Balance Sheet' ?></div>
                </a>

                <a href="/ERP/accounting/reports/trial-balance" class="hub-item">
                    <div class="hub-icon" style="background:#e0f2fe; color:#0284c7;"><i class="ph-bold ph-list-numbers"></i></div>
                    <div><?= $isRtl ? 'ميزان المراجعة' : 'Trial Balance' ?></div>
                </a>

                <a href="/ERP/accounting/reports/ledger" class="hub-item">
                    <div class="hub-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-bold ph-book-open"></i></div>
                    <div><?= $isRtl ? 'دفتر الأستاذ العام' : 'General Ledger' ?></div>
                </a>

                <a href="/ERP/accounting/reports/vat-return" class="hub-item">
                    <div class="hub-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-bold ph-receipt"></i></div>
                    <div><?= $isRtl ? 'الإقرار الضريبي' : 'VAT Return' ?></div>
                </a>

                <a href="/ERP/accounting/reports/cash-flow" class="hub-item">
                    <div class="hub-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-bold ph-arrows-left-right"></i></div>
                    <div><?= $isRtl ? 'التدفقات النقدية' : 'Cash Flows' ?></div>
                </a>

                <a href="/ERP/accounting/bank-reconciliation" class="hub-item">
                    <div class="hub-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-bold ph-bank"></i></div>
                    <div><?= $isRtl ? 'التسوية البنكية' : 'Bank Reconciliation' ?></div>
                </a>

                <a href="/ERP/accounting/chart-of-accounts" class="hub-item">
                    <div class="hub-icon" style="background:#ffffff; color:#475569; border:1px solid #cbd5e1;"><i class="ph-bold ph-tree-structure"></i></div>
                    <div><?= $isRtl ? 'دليل الحسابات' : 'Chart of Accounts' ?></div>
                </a>
            </div>
        </div>

        <!-- Recent Entries Table -->
        <div class="chart-card" style="display:flex; flex-direction:column; justify-content:space-between;">
            <div>
                <div class="card-title">
                    <span><i class="ph-bold ph-clock-counter-clockwise" style="color: var(--dash-amber);"></i> <?= $t['recent_entries'] ?></span>
                    <a href="/ERP/accounting/journal-entries" style="font-size:0.8rem; color:var(--dash-indigo); text-decoration:none; font-weight:800;"><?= $isRtl ? 'عرض الكل ←' : 'View All →' ?></a>
                </div>
                <table class="dash-table" id="recentEntriesTable">
                    <thead>
                        <tr>
                            <th><?= $isRtl ? 'القيد' : 'Entry' ?></th>
                            <th><?= $isRtl ? 'التاريخ' : 'Date' ?></th>
                            <th style="text-align:end;"><?= $isRtl ? 'المبلغ' : 'Amount' ?></th>
                            <th style="text-align:center;"><?= $isRtl ? 'الحالة' : 'Status' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($recentEntries)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:20px; color:#94a3b8;"><?= $t['no_entries'] ?></td></tr>
                        <?php else: foreach($recentEntries as $entry): ?>
                            <tr>
                                <td><a href="/ERP/accounting/journal-entries/<?= $entry->id ?>" style="font-family:monospace; font-weight:900; color:var(--dash-indigo); text-decoration:none;">#<?= htmlspecialchars($entry->entry_number) ?></a></td>
                                <td style="font-family:monospace; font-size:0.8rem; font-weight:700; color:#64748b;"><?= htmlspecialchars($entry->entry_date) ?></td>
                                <td style="font-family:monospace; font-weight:900; color:#0f172a; text-align:end;"><?= number_format((float)$entry->total_amount, 2) ?></td>
                                <td style="text-align:center;">
                                    <?php if($entry->status === 'posted'): ?>
                                        <span style="background:#ecfdf5; color:#059669; padding:4px 8px; border-radius:6px; font-weight:800; font-size:0.72rem;"><?= $t['st_posted'] ?></span>
                                    <?php else: ?>
                                        <span style="background:#fef3c7; color:#d97706; padding:4px 8px; border-radius:6px; font-weight:800; font-size:0.72rem;"><?= $t['st_draft'] ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px; padding:12px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; display:flex; justify-content:space-between; align-items:center; font-size:0.82rem; font-weight:800;">
                <span style="color:#64748b;"><i class="ph-bold ph-file-dashed" style="color:var(--dash-amber);"></i> <?= $t['drafts'] ?> <strong style="color:#0f172a; font-family:monospace; font-size:0.95rem;"><?= $kpis['drafts'] ?></strong> | <?= $t['pending_recons'] ?> <strong style="color:#0f172a; font-family:monospace; font-size:0.95rem;"><?= $kpis['pending_recons'] ?></strong></span>
                <a href="/ERP/accounting/journal-entries?status=draft" style="color:var(--dash-indigo); text-decoration:none;"><?= $t['review'] ?></a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    Chart.defaults.font.family = "'Cairo', 'Inter', sans-serif";
    const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top', labels: { boxWidth: 12, font: {family: "'Cairo', sans-serif", weight: 'bold'} } } } };

    // 1. Chart 1: Revenue vs Expense Bar Chart
    new Chart(document.getElementById('chartTrend').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($charts['labels']) ?>,
            datasets: [
                { label: '<?= $t['lbl_rev'] ?>', data: <?= json_encode($charts['revenue']) ?>, backgroundColor: '#10b981', borderRadius: 6 },
                { label: '<?= $t['lbl_exp'] ?>', data: <?= json_encode($charts['expense']) ?>, backgroundColor: '#f43f5e', borderRadius: 6 }
            ]
        },
        options: { ...chartOptions, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
    });

    // 2. Chart 2: Capital Structure Doughnut
    new Chart(document.getElementById('chartStruct').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['<?= $t['lbl_assets'] ?>', '<?= $t['lbl_liabilities'] ?>', '<?= $t['lbl_equity'] ?>'],
            datasets: [{
                data: [<?= $kpis['total_assets'] ?>, <?= $kpis['total_liabilities'] ?>, <?= $kpis['total_equity'] ?>],
                backgroundColor: ['#10b981', '#f43f5e', '#4f46e5'],
                borderWidth: 2, borderColor: '#ffffff'
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: {family: "'Cairo', sans-serif", weight: 'bold'} } } } }
    });

    // 3. Chart 3: Cash Flow Dynamics Line Chart
    new Chart(document.getElementById('chartCash').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode($charts['labels']) ?>,
            datasets: [
                { label: '<?= $t['lbl_inflow'] ?>', data: <?= json_encode($charts['cash_inflow']) ?>, borderColor: '#059669', backgroundColor: 'rgba(5, 150, 105, 0.08)', fill: true, tension: 0.3, pointRadius: 4 },
                { label: '<?= $t['lbl_outflow'] ?>', data: <?= json_encode($charts['cash_outflow']) ?>, borderColor: '#dc2626', backgroundColor: 'rgba(220, 38, 38, 0.08)', fill: true, tension: 0.3, pointRadius: 4 }
            ]
        },
        options: { ...chartOptions, scales: { x: { grid: { display: false } } } }
    });

    // 4. Chart 4: Expense Categories
    const expLabels = <?= json_encode($charts['expense_categories']['labels']) ?>;
    const expData = <?= json_encode($charts['expense_categories']['data']) ?>;
    new Chart(document.getElementById('chartExp').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: expLabels.length > 0 ? expLabels : ['---'],
            datasets: [{
                data: expData.length > 0 ? expData : [1],
                backgroundColor: ['#f43f5e', '#d97706', '#0284c7', '#8b5cf6', '#64748b'],
                borderWidth: 2, borderColor: '#ffffff'
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '50%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10, family: "'Cairo', sans-serif" } } } } }
    });

    // 5. Chart 5: Monthly Net Profit Line
    new Chart(document.getElementById('chartProfit').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode($charts['labels']) ?>,
            datasets: [{
                label: '<?= $t['profit'] ?>',
                data: <?= json_encode($charts['profit_trend']) ?>,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.12)',
                fill: true,
                tension: 0.4,
                pointRadius: 5
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } } } }
    });

    // 6. Chart 6: Tax Exposure Breakdown
    const taxData = <?= json_encode($charts['tax_breakdown']) ?>;
    new Chart(document.getElementById('chartTax').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['<?= $t['lbl_vat_out'] ?>', '<?= $t['lbl_vat_in'] ?>', '<?= $t['lbl_wht'] ?>'],
            datasets: [{
                label: 'Total',
                data: [taxData.output_vat, taxData.input_vat, taxData.wht],
                backgroundColor: ['#f43f5e', '#10b981', '#0284c7'],
                borderRadius: 6
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } } } }
    });
});

// تصدير Dashboard كاملاً لـ Excel
function exportDashboardExcel() {
    let summaryData = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="utf-8">
            <style>
                table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
                th { background-color: #4f46e5; color: #ffffff; padding: 10px; font-weight: bold; border: 1px solid #000; text-align: center;}
                td { padding: 8px; border: 1px solid #cbd5e1; text-align: center; }
                .title { font-size: 16pt; font-weight: bold; color: #0f172a; margin-bottom: 20px;}
            </style>
        </head>
        <body dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
            <div class="title"><?= $t['title'] ?> - ERP Financial Dashboard</div>
            <p><strong>تاريخ التصدير:</strong> ${new Date().toLocaleString()}</p>
            <br>
            <table>
                <thead>
                    <tr>
                        <th>المؤشر المالي (Metric)</th>
                        <th>القيمة (<?= $currency ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><?= $t['revenue'] ?></td><td><?= number_format($kpis['revenue'], 2, '.', '') ?></td></tr>
                    <tr><td><?= $t['expenses'] ?></td><td><?= number_format($kpis['expenses'], 2, '.', '') ?></td></tr>
                    <tr><td><?= $t['profit'] ?></td><td><?= number_format($kpis['net_profit'], 2, '.', '') ?></td></tr>
                    <tr><td><?= $t['margin'] ?></td><td><?= $kpis['net_margin'] ?>%</td></tr>
                    <tr><td><?= $t['cash'] ?></td><td><?= number_format($kpis['cash_balance'], 2, '.', '') ?></td></tr>
                    <tr><td><?= $t['working_cap'] ?></td><td><?= number_format($kpis['working_capital'], 2, '.', '') ?></td></tr>
                    <tr><td><?= $t['lbl_assets'] ?></td><td><?= number_format($kpis['total_assets'], 2, '.', '') ?></td></tr>
                    <tr><td><?= $t['lbl_liabilities'] ?></td><td><?= number_format($kpis['total_liabilities'], 2, '.', '') ?></td></tr>
                    <tr><td><?= $t['lbl_equity'] ?></td><td><?= number_format($kpis['total_equity'], 2, '.', '') ?></td></tr>
                </tbody>
            </table>
            <br>
            <h3><?= $t['recent_entries'] ?></h3>
            ${document.getElementById('recentEntriesTable').outerHTML}
        </body>
        </html>
    `;

    let blob = new Blob([summaryData], { type: 'application/vnd.ms-excel' });
    let link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = 'Financial_Dashboard_Executive_' + new Date().toISOString().slice(0, 10) + '.xls';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>