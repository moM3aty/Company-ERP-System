<?php
// Path: resources/views/accounting/dashboard/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = 'EGP';

$t = [
    'ar' => [
        'title' => 'مركز القيادة والتحليل المالي الشامل',
        'desc' => 'رؤية استراتيجية مجمعة للربحية، الأصول، التدفقات النقدية، والموقف الضريبي.',
        'revenue' => 'إجمالي الإيرادات',
        'expenses' => 'إجمالي المصروفات',
        'profit' => 'صافي ربح الفترة',
        'cash' => 'السيولة والنقدية',
        'working_cap' => 'رأس المال العامل',
        'margin' => 'هامش الربح',
        'c1' => '1. حركة الإيرادات والمصروفات (6 شهور)',
        'c2' => '2. الهيكل المالي للأصول والخصوم',
        'c3' => '3. التدفق النقدي (المقبوضات والمدفوعات)',
        'c4' => '4. توزيع أكبر 5 بنود للمصروفات',
        'c5' => '5. اتجاه نمو صافي الربح الشهري',
        'c6' => '6. الموقف والتحليل الضريبي المجمع',
        'recent_entries' => 'أحدث القيود اليومية المسجلة',
        'quick_hub' => 'مركز التقارير والموديولات المحاسبية',
        'new_entry' => 'إضافة قيد جديد',
        'export_excel' => 'تصدير Dashboard لـ Excel'
    ],
    'en' => [
        'title' => 'Executive Financial Command Center',
        'desc' => 'Comprehensive financial intelligence, liquidity analytics, and executive reports.',
        'revenue' => 'Total Revenue',
        'expenses' => 'Total Expenses',
        'profit' => 'Net Profit',
        'cash' => 'Cash Balance',
        'working_cap' => 'Working Capital',
        'margin' => 'Profit Margin',
        'c1' => '1. Revenue vs Expense Trend',
        'c2' => '2. Capital Structure (Assets/Liab)',
        'c3' => '3. Cash Inflow vs Outflow',
        'c4' => '4. Top 5 Expense Categories',
        'c5' => '5. Monthly Net Profit Growth',
        'c6' => '6. Tax Exposure Breakdown',
        'recent_entries' => 'Recent Journal Entries',
        'quick_hub' => 'Executive Accounting Launchpad',
        'new_entry' => 'New Entry',
        'export_excel' => 'Export Dashboard to Excel'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --dash-indigo: #4f46e5;
        --dash-indigo-dark: #3730a3;
        --dash-indigo-light: #e0e7ff;
        --dash-emerald: #10b981;
        --dash-emerald-bg: #ecfdf5;
        --dash-rose: #f43f5e;
        --dash-rose-bg: #fff1f2;
        --dash-amber: #d97706;
        --dash-amber-bg: #fef3c7;
        --dash-sky: #0284c7;
        --dash-sky-bg: #e0f2fe;
        --dash-violet: #8b5cf6;
        --dash-violet-bg: #f5f3ff;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .dash-wrapper { padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>;  margin: 0 auto; }

    .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; border-bottom: 1px solid #e2e8f0; padding-bottom: 18px; flex-wrap: wrap; gap: 16px; }
    .dash-title-box { display: flex; align-items: center; gap: 16px; }
    .dash-icon { width: 54px; height: 52px; background: var(--dash-indigo-light); color: var(--dash-indigo); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.9rem; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15); flex-shrink: 0; }
    .dash-title { margin: 0; color: var(--c-text-dark); font-size: 1.7rem; font-weight: 900; }
    
    .btn-actions-group { display: flex; gap: 10px; }
    .btn-excel { background: #059669; color: #ffffff !important; padding: 12px 20px; border-radius: 12px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.18); border: none; cursor: pointer; font-size: 0.88rem; }
    .btn-create-entry { background: linear-gradient(135deg, var(--dash-indigo), var(--dash-indigo-dark)); color: #ffffff !important; padding: 12px 22px; border-radius: 12px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25); font-size: 0.88rem; }

    /* KPI Grid - 6 Items Bar */
    .kpi-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; margin-bottom: 28px; }
    @media(max-width:1200px){ .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
    @media(max-width:640px){ .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
    
    .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 18px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; transition: all 0.2s; }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
    .kpi-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: currentColor; }
    .kpi-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .kpi-icon-box { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
    .kpi-label { font-size: 0.78rem; font-weight: 800; color: var(--c-text-muted); }
    .kpi-val { font-size: clamp(1rem, 1.3vw, 1.35rem); font-weight: 900; font-family: monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0; }

    /* 3 Charts Per Row Grid */
    .charts-row-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
    @media(max-width:1100px){ .charts-row-3 { grid-template-columns: repeat(2, 1fr); } }
    @media(max-width:768px){ .charts-row-3 { grid-template-columns: 1fr; } }

    .chart-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .card-title { margin: 0 0 16px 0; font-size: 0.95rem; font-weight: 900; color: var(--c-text-dark); display: flex; align-items: center; justify-content: space-between; }

    /* Bento Bottom Layout */
    .bento-bottom { display: grid; grid-template-columns: 7fr 5fr; gap: 24px; margin-bottom: 28px; }
    @media(max-width:1024px){ .bento-bottom { grid-template-columns: 1fr; } }

    .hub-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .hub-item { display: flex; align-items: center; gap: 10px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-decoration: none; color: var(--c-text-dark); font-weight: 800; font-size: 0.85rem; transition: all 0.2s; }
    .hub-item:hover { background: #ffffff; border-color: var(--dash-indigo); transform: translateX(<?= $isRtl ? '-3px' : '3px' ?>); box-shadow: 0 4px 10px rgba(79, 70, 229, 0.08); }
    .hub-icon { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }

    .dash-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .dash-table th { padding: 10px 14px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; border-bottom: 2px solid #e2e8f0; text-align: start; font-size: 0.75rem; }
    .dash-table td { padding: 11px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
</style>

<div class="dash-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <!-- Header Bar -->
    <div class="dash-header">
        <div class="dash-title-box">
            <div class="dash-icon"><i class="ph-duotone ph-chart-pie-slice"></i></div>
            <div>
                <h2 class="dash-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem; font-weight:600;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div class="btn-actions-group">
            <button type="button" onclick="exportDashboardExcel()" class="btn-excel">
                <i class="ph-bold ph-file-xls"></i> <?= $t['export_excel'] ?>
            </button>
            <a href="/ERP/accounting/journal-entries/create" class="btn-create-entry">
                <i class="ph-bold ph-plus-circle"></i> <?= $t['new_entry'] ?>
            </a>
        </div>
    </div>

    <!-- KPI Row (6 Indicator Cards) -->
    <div class="kpi-grid">
        <div class="kpi-card" style="color: var(--dash-emerald);">
            <div class="kpi-top">
                <span class="kpi-label"><?= $t['revenue'] ?></span>
                <div class="kpi-icon-box" style="background: var(--dash-emerald-bg); color: var(--dash-emerald);"><i class="ph-duotone ph-trend-up"></i></div>
            </div>
            <p class="kpi-val" style="color: var(--dash-emerald);" title="<?= number_format($kpis['revenue'], 2) ?>"><?= number_format($kpis['revenue'], 2) ?></p>
        </div>

        <div class="kpi-card" style="color: var(--dash-rose);">
            <div class="kpi-top">
                <span class="kpi-label"><?= $t['expenses'] ?></span>
                <div class="kpi-icon-box" style="background: var(--dash-rose-bg); color: var(--dash-rose);"><i class="ph-duotone ph-trend-down"></i></div>
            </div>
            <p class="kpi-val" style="color: var(--dash-rose);" title="<?= number_format($kpis['expenses'], 2) ?>"><?= number_format($kpis['expenses'], 2) ?></p>
        </div>

        <div class="kpi-card" style="color: <?= $kpis['net_profit'] >= 0 ? 'var(--dash-indigo)' : 'var(--dash-rose)' ?>;">
            <div class="kpi-top">
                <span class="kpi-label"><?= $t['profit'] ?></span>
                <div class="kpi-icon-box" style="background: var(--dash-indigo-light); color: var(--dash-indigo);"><i class="ph-duotone ph-scales"></i></div>
            </div>
            <p class="kpi-val" style="color: <?= $kpis['net_profit'] >= 0 ? 'var(--dash-indigo)' : 'var(--dash-rose)' ?>;" title="<?= number_format($kpis['net_profit'], 2) ?>"><?= number_format($kpis['net_profit'], 2) ?></p>
        </div>

        <div class="kpi-card" style="color: var(--dash-sky);">
            <div class="kpi-top">
                <span class="kpi-label"><?= $t['cash'] ?></span>
                <div class="kpi-icon-box" style="background: var(--dash-sky-bg); color: var(--dash-sky);"><i class="ph-duotone ph-bank"></i></div>
            </div>
            <p class="kpi-val" style="color: var(--dash-sky);" title="<?= number_format($kpis['cash_balance'], 2) ?>"><?= number_format($kpis['cash_balance'], 2) ?></p>
        </div>

        <div class="kpi-card" style="color: var(--dash-amber);">
            <div class="kpi-top">
                <span class="kpi-label"><?= $t['working_cap'] ?></span>
                <div class="kpi-icon-box" style="background: var(--dash-amber-bg); color: var(--dash-amber);"><i class="ph-duotone ph-coins"></i></div>
            </div>
            <p class="kpi-val" style="color: var(--dash-amber);" title="<?= number_format($kpis['working_capital'], 2) ?>"><?= number_format($kpis['working_capital'], 2) ?></p>
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
        <!-- Chart 1: Revenue vs Expense Bar Chart -->
        <div class="chart-card">
            <div class="card-title">
                <span><i class="ph-bold ph-chart-bar" style="color: var(--dash-indigo);"></i> <?= $t['c1'] ?></span>
            </div>
            <div style="height: 230px; position: relative;">
                <canvas id="chartTrend"></canvas>
            </div>
        </div>

        <!-- Chart 2: Doughnut Capital Structure -->
        <div class="chart-card">
            <div class="card-title">
                <span><i class="ph-bold ph-chart-pie" style="color: var(--dash-sky);"></i> <?= $t['c2'] ?></span>
            </div>
            <div style="height: 230px; position: relative;">
                <canvas id="chartStruct"></canvas>
            </div>
        </div>

        <!-- Chart 3: Cash Flow Dynamics -->
        <div class="chart-card">
            <div class="card-title">
                <span><i class="ph-bold ph-arrows-left-right" style="color: var(--dash-emerald);"></i> <?= $t['c3'] ?></span>
            </div>
            <div style="height: 230px; position: relative;">
                <canvas id="chartCash"></canvas>
            </div>
        </div>
    </div>

    <!-- CHARTS ROW 2: 3 CHARTS SIDE-BY-SIDE -->
    <div class="charts-row-3">
        <!-- Chart 4: Expense Categories Doughnut -->
        <div class="chart-card">
            <div class="card-title">
                <span><i class="ph-bold ph-pie-chart" style="color: var(--dash-rose);"></i> <?= $t['c4'] ?></span>
            </div>
            <div style="height: 230px; position: relative;">
                <canvas id="chartExp"></canvas>
            </div>
        </div>

        <!-- Chart 5: Monthly Net Profit Line Chart -->
        <div class="chart-card">
            <div class="card-title">
                <span><i class="ph-bold ph-trend-up" style="color: var(--dash-indigo);"></i> <?= $t['c5'] ?></span>
            </div>
            <div style="height: 230px; position: relative;">
                <canvas id="chartProfit"></canvas>
            </div>
        </div>

        <!-- Chart 6: Tax Breakdown Bar Chart -->
        <div class="chart-card">
            <div class="card-title">
                <span><i class="ph-bold ph-receipt" style="color: var(--dash-amber);"></i> <?= $t['c6'] ?></span>
            </div>
            <div style="height: 230px; position: relative;">
                <canvas id="chartTax"></canvas>
            </div>
        </div>
    </div>

    <!-- Bento Bottom Section: Launchpad Hub & Activity -->
    <div class="bento-bottom">
        <!-- Quick Reports Launchpad Hub -->
        <div class="chart-card">
            <div class="card-title">
                <span><i class="ph-bold ph-rocket-launch" style="color: var(--dash-indigo);"></i> <?= $t['quick_hub'] ?></span>
            </div>
            
            <div class="hub-grid">
                <a href="/ERP/accounting/reports/income-statement" class="hub-item">
                    <div class="hub-icon" style="background:#e0e7ff; color:#4f46e5;"><i class="ph-bold ph-chart-line-up"></i></div>
                    <div>قائمة الدخل (P&L)</div>
                </a>

                <a href="/ERP/accounting/reports/balance-sheet" class="hub-item">
                    <div class="hub-icon" style="background:#f5f3ff; color:#7c3aed;"><i class="ph-bold ph-scales"></i></div>
                    <div>الميزانية العمومية</div>
                </a>

                <a href="/ERP/accounting/reports/trial-balance" class="hub-item">
                    <div class="hub-icon" style="background:#e0f2fe; color:#0284c7;"><i class="ph-bold ph-list-numbers"></i></div>
                    <div>ميزان المراجعة</div>
                </a>

                <a href="/ERP/accounting/reports/ledger" class="hub-item">
                    <div class="hub-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-bold ph-book-open"></i></div>
                    <div>دفتر الأستاذ العام</div>
                </a>

                <a href="/ERP/accounting/reports/vat-return" class="hub-item">
                    <div class="hub-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-bold ph-receipt"></i></div>
                    <div>الإقرار الضريبي</div>
                </a>

                <a href="/ERP/accounting/reports/cash-flow" class="hub-item">
                    <div class="hub-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-bold ph-arrows-left-right"></i></div>
                    <div>التدفقات النقدية</div>
                </a>

                <a href="/ERP/accounting/bank-reconciliation" class="hub-item">
                    <div class="hub-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-bold ph-bank"></i></div>
                    <div>التسوية البنكية</div>
                </a>

                <a href="/ERP/accounting/fiscal-periods" class="hub-item">
                    <div class="hub-icon" style="background:#fef3c7; color:#b45309;"><i class="ph-bold ph-calendar-check"></i></div>
                    <div>السنوات المالية</div>
                </a>

                <a href="/ERP/accounting/taxes" class="hub-item">
                    <div class="hub-icon" style="background:#e0f2fe; color:#0369a1;"><i class="ph-bold ph-percent"></i></div>
                    <div>إعدادات الضرائب</div>
                </a>

                <a href="/ERP/accounting/chart-of-accounts" class="hub-item">
                    <div class="hub-icon" style="background:#f1f5f9; color:#475569;"><i class="ph-bold ph-tree-structure"></i></div>
                    <div>دليل الحسابات</div>
                </a>
            </div>
        </div>

        <!-- Recent Entries Table -->
        <div class="chart-card" style="display:flex; flex-direction:column; justify-content:space-between;">
            <div>
                <div class="card-title">
                    <span><i class="ph-bold ph-clock-counter-clockwise" style="color: var(--dash-amber);"></i> <?= $t['recent_entries'] ?></span>
                    <a href="/ERP/accounting/journal-entries" style="font-size:0.8rem; color:var(--dash-indigo); text-decoration:none; font-weight:800;">عرض الكل ←</a>
                </div>

                <table class="dash-table" id="recentEntriesTable">
                    <thead>
                        <tr>
                            <th>القيد</th>
                            <th>التاريخ</th>
                            <th>المبلغ</th>
                            <th style="text-align:center;">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($recentEntries)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:20px; color:#94a3b8;">لا توجد قيود مسجلة مؤخراً.</td></tr>
                        <?php else: foreach($recentEntries as $entry): ?>
                            <tr>
                                <td><a href="/ERP/accounting/journal-entries/<?= $entry->id ?>" style="font-family:monospace; font-weight:900; color:var(--dash-indigo); text-decoration:none;">#<?= htmlspecialchars($entry->entry_number) ?></a></td>
                                <td style="font-family:monospace; font-size:0.8rem; font-weight:700; color:#64748b;"><?= htmlspecialchars($entry->entry_date) ?></td>
                                <td style="font-family:monospace; font-weight:900; color:#0f172a;"><?= number_format((float)$entry->total_amount, 2) ?></td>
                                <td style="text-align:center;">
                                    <?php if($entry->status === 'posted'): ?>
                                        <span style="background:#ecfdf5; color:#059669; padding:3px 8px; border-radius:6px; font-weight:800; font-size:0.72rem;">مرحّل</span>
                                    <?php else: ?>
                                        <span style="background:#fef3c7; color:#d97706; padding:3px 8px; border-radius:6px; font-weight:800; font-size:0.72rem;">مسودة</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px; padding:12px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; display:flex; justify-content:space-between; align-items:center; font-size:0.82rem; font-weight:800;">
                <span style="color:#64748b;"><i class="ph-bold ph-file-dashed" style="color:var(--dash-amber);"></i> قيود مسودة: <strong style="color:#0f172a; font-family:monospace; font-size:0.95rem;"><?= $kpis['drafts'] ?></strong> | تسويات معلقة: <strong style="color:#0f172a; font-family:monospace; font-size:0.95rem;"><?= $kpis['pending_recons'] ?></strong></span>
                <a href="/ERP/accounting/journal-entries?status=draft" style="color:var(--dash-indigo); text-decoration:none;">مراجعة ←</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    Chart.defaults.font.family = "'Cairo', 'Inter', sans-serif";

    // 1. Chart 1: Revenue vs Expense Bar Chart
    new Chart(document.getElementById('chartTrend').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($charts['labels']) ?>,
            datasets: [
                { label: 'الإيرادات', data: <?= json_encode($charts['revenue']) ?>, backgroundColor: '#10b981', borderRadius: 6 },
                { label: 'المصروفات', data: <?= json_encode($charts['expense']) ?>, backgroundColor: '#f43f5e', borderRadius: 6 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top', labels: { boxWidth: 12 } } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
    });

    // 2. Chart 2: Capital Structure Doughnut
    new Chart(document.getElementById('chartStruct').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['الأصول', 'الالتزامات', 'حقوق الملكية'],
            datasets: [{
                data: [<?= $kpis['total_assets'] ?>, <?= $kpis['total_liabilities'] ?>, <?= $kpis['total_equity'] ?>],
                backgroundColor: ['#10b981', '#f43f5e', '#4f46e5'],
                borderWidth: 2, borderColor: '#ffffff'
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
    });

    // 3. Chart 3: Cash Flow Dynamics Line Chart
    new Chart(document.getElementById('chartCash').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode($charts['labels']) ?>,
            datasets: [
                { label: 'مقبوضات', data: <?= json_encode($charts['cash_inflow']) ?>, borderColor: '#059669', backgroundColor: 'rgba(5, 150, 105, 0.08)', fill: true, tension: 0.3 },
                { label: 'مدفوعات', data: <?= json_encode($charts['cash_outflow']) ?>, borderColor: '#dc2626', backgroundColor: 'rgba(220, 38, 38, 0.08)', fill: true, tension: 0.3 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top', labels: { boxWidth: 12 } } }, scales: { x: { grid: { display: false } } } }
    });

    // 4. Chart 4: Expense Categories
    const expLabels = <?= json_encode($charts['expense_categories']['labels']) ?>;
    const expData = <?= json_encode($charts['expense_categories']['data']) ?>;
    new Chart(document.getElementById('chartExp').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: expLabels.length > 0 ? expLabels : ['لا توجد مصروفات'],
            datasets: [{
                data: expData.length > 0 ? expData : [1],
                backgroundColor: ['#f43f5e', '#d97706', '#0284c7', '#8b5cf6', '#64748b'],
                borderWidth: 2, borderColor: '#ffffff'
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
    });

    // 5. Chart 5: Monthly Net Profit Line
    new Chart(document.getElementById('chartProfit').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode($charts['labels']) ?>,
            datasets: [{
                label: 'صافي الربح',
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
            labels: ['ضريبة مبيعات (مخرجات)', 'ضريبة مشتريات (مدخلات)', 'خصم وإضافة (WHT)'],
            datasets: [{
                label: 'المبلغ الإجمالي',
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
                th { background-color: #4f46e5; color: #ffffff; padding: 10px; font-weight: bold; border: 1px solid #000; }
                td { padding: 8px; border: 1px solid #cbd5e1; text-align: center; }
                .title { font-size: 16pt; font-weight: bold; color: #0f172a; }
            </style>
        </head>
        <body dir="rtl">
            <div class="title">ملخص المؤشرات والمركز المالي - ERP Financial Dashboard</div>
            <p>تاريخ التصدير: ${new Date().toLocaleString()}</p>
            <br>
            <table>
                <thead>
                    <tr>
                        <th>المؤشر المالي</th>
                        <th>القيمة (EGP)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>إجمالي الإيرادات</td><td><?= number_format($kpis['revenue'], 2) ?></td></tr>
                    <tr><td>إجمالي المصروفات</td><td><?= number_format($kpis['expenses'], 2) ?></td></tr>
                    <tr><td>صافي ربح الفترة</td><td><?= number_format($kpis['net_profit'], 2) ?></td></tr>
                    <tr><td>هامش الربح (%)</td><td><?= $kpis['net_margin'] ?>%</td></tr>
                    <tr><td>السيولة والنقدية بالبنوك</td><td><?= number_format($kpis['cash_balance'], 2) ?></td></tr>
                    <tr><td>رأس المال العامل</td><td><?= number_format($kpis['working_capital'], 2) ?></td></tr>
                    <tr><td>إجمالي الأصول</td><td><?= number_format($kpis['total_assets'], 2) ?></td></tr>
                    <tr><td>إجمالي الالتزامات</td><td><?= number_format($kpis['total_liabilities'], 2) ?></td></tr>
                    <tr><td>حقوق الملكية</td><td><?= number_format($kpis['total_equity'], 2) ?></td></tr>
                    <tr><td>قيود مسودة بانتظار الاعتماد</td><td><?= $kpis['drafts'] ?></td></tr>
                </tbody>
            </table>
            <br>
            <h3>أحدث القيود المسجلة</h3>
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