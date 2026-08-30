<?php
// Path: resources/views/purchasing/dashboard/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = 'EGP';

// تعريف مصفوفة النصوص للغتين العربية والإنجليزية
$t = [
    'ar' => [
        'title' => 'لوحة القيادة والتحليل الإداري للمشتريات',
        'desc' => 'مراقبة الموردين، إنفاق الشحنات، متابعة الاعتمادات، وتدفق البضائع الواردة.',
        'spend' => 'إنفاق الشهر الحالي',
        'pos' => 'أوامر شراء جارية (PO)',
        'prs' => 'طلبات بانتظار الاعتماد (PR)',
        'suppliers' => 'الموردين النشطين',
        'receipts' => 'استلامات قيد الفحص (GRN)',
        'unpaid' => 'مستحقات غير مدفوعة',
        'chart_title' => 'مؤشر الإنفاق الشهري (آخر 6 شهور)',
        'recent_po' => 'أحدث أوامر الشراء الصادرة'
    ],
    'en' => [
        'title' => 'Purchasing Control & Analytics Dashboard',
        'desc' => 'Monitor vendors, spend trends, pending approvals, and inbound logistics.',
        'spend' => 'Monthly Spend',
        'pos' => 'Active POs',
        'prs' => 'Pending Requisitions',
        'suppliers' => 'Active Vendors',
        'receipts' => 'Pending Receipts',
        'unpaid' => 'Outstanding Payables',
        'chart_title' => 'Monthly Spend Trend (6 Months)',
        'recent_po' => 'Recent Purchase Orders'
    ]
][$isRtl ? 'ar' : 'en'];

// الحماية من الـ Null في أرقام الـ KPIs
$monthly_spend    = $kpis['monthly_spend'] ?? 0;
$open_pos         = $kpis['open_pos'] ?? 0;
$pending_prs      = $kpis['pending_prs'] ?? 0;
$suppliers_count  = $kpis['suppliers_count'] ?? 0;
$pending_receipts = $kpis['pending_receipts'] ?? 0;
$unpaid_invoices  = $kpis['unpaid_invoices'] ?? 0;

// الحماية من الـ Null في بيانات الـ Charts لضمان عمل الجافاسكريبت دائماً
$c_spend_labels = !empty($charts['spend_labels']) ? $charts['spend_labels'] : ['لا توجد بيانات'];
$c_spend_data   = !empty($charts['spend_data']) ? $charts['spend_data'] : [0];

$c_status_labels = !empty($charts['status_labels']) ? $charts['status_labels'] : ['لا توجد بيانات'];
$c_status_data   = !empty($charts['status_data']) ? $charts['status_data'] : [1];

$c_sup_labels = !empty($charts['top_sup_labels']) ? $charts['top_sup_labels'] : ['لا توجد بيانات'];
$c_sup_data   = !empty($charts['top_sup_data']) ? $charts['top_sup_data'] : [0];

$c_prod_labels = !empty($charts['top_prod_labels']) ? $charts['top_prod_labels'] : ['لا توجد بيانات'];
$c_prod_data   = !empty($charts['top_prod_data']) ? $charts['top_prod_data'] : [0];

function getBadge($status) {
    $colors = [
        'draft' => '#64748b', 'sent' => '#2563eb', 'completed' => '#059669', 'paid' => '#059669',
        'partially_received' => '#ea580c', 'unpaid' => '#dc2626', 'cancelled' => '#dc2626'
    ];
    $c = $colors[$status] ?? '#64748b';
    return "<span style='color:{$c}; font-weight:800; font-size:0.75rem; border:1px solid {$c}; padding:4px 10px; border-radius:8px; background: {$c}15;'>".strtoupper($status)."</span>";
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --d-bg: #f8fafc;
        --d-surface: #ffffff;
        --d-border: #e2e8f0;
        --d-text: #0f172a;
        --d-muted: #64748b;
        
        --c-blue: #2563eb;    --c-blue-bg: #eff6ff;
        --c-teal: #0d9488;    --c-teal-bg: #ccfbf1;
        --c-rose: #e11d48;    --c-rose-bg: #ffe4e6;
        --c-emerald: #059669; --c-emerald-bg: #d1fae5;
        --c-orange: #ea580c;  --c-orange-bg: #ffedd5;
        --c-indigo: #4338ca;  --c-indigo-bg: #e0e7ff;
    }
    
    .dashboard-wrapper { padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: var(--d-bg); min-height: 100vh;}

    .top-action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .page-title { margin: 0; color: var(--d-text); font-size: 1.8rem; font-weight: 900; display: flex; align-items: center; gap: 12px; }
    .page-title-icon { background: linear-gradient(135deg, var(--c-blue), #1d4ed8); color: #fff; padding: 10px; border-radius: 12px; display: flex; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }

    .export-hub { display: flex; gap: 10px; flex-wrap: wrap; }
    .export-btn { background: var(--d-surface); color: var(--d-text); border: 1px solid var(--d-border); padding: 10px 18px; border-radius: 10px; font-weight: 800; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .export-btn:hover { background: #f1f5f9; border-color: #cbd5e1; transform: translateY(-2px); }
    .export-btn i { color: #16a34a; font-size: 1.2rem; }

    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 24px; }
    .charts-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 24px; margin-bottom: 24px; }
    .tables-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 24px; margin-bottom: 24px; }

    .p-card { background: var(--d-surface); border: 1px solid rgba(226, 232, 240, 0.8); border-radius: 20px; padding: 24px; box-shadow: 0 4px 20px -2px rgba(0,0,0,0.03); transition: 0.3s; position: relative; }
    .p-card:hover { box-shadow: 0 12px 30px -5px rgba(0,0,0,0.06); transform: translateY(-2px); border-color: #cbd5e1; }
    .card-title { margin: 0 0 20px 0; font-size: 1.1rem; font-weight: 900; color: var(--d-text); display: flex; align-items: center; gap: 10px; }

    .kpi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
    .kpi-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; }
    .kpi-title { font-size: 0.85rem; font-weight: 800; color: var(--d-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .kpi-value { font-size: 1.8rem; font-weight: 900; color: var(--d-text); font-family: monospace; display: flex; align-items: baseline; gap: 6px; }

    .chart-container { position: relative; width: 100%; height: 320px; }
    
    .col-8 { grid-column: span 8; }
    .col-6 { grid-column: span 6; }
    .col-4 { grid-column: span 4; }
    @media(max-width: 1200px) { .col-8, .col-6, .col-4 { grid-column: span 12; } }

    .mod-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .mod-table th { padding: 14px 16px; background: #f8fafc; color: var(--d-muted); font-weight: 800; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid var(--d-border); text-align: start; }
    .mod-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; font-weight: 600; }
    .mod-table tr:hover td { background: #f8fafc; }
</style>

<div class="dashboard-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <div class="top-action-bar">
        <h2 class="page-title">
            <div class="page-title-icon"><i class="ph-duotone ph-chart-pie-slice"></i></div>
            <?= $t['title'] ?>
        </h2>
        <div class="export-hub">
            <a href="/ERP/purchasing/dashboard/export?type=pos" class="export-btn"><i class="ph-duotone ph-microsoft-excel-logo"></i> تصدير أوامر الشراء</a>
            <a href="/ERP/purchasing/dashboard/export?type=invoices" class="export-btn"><i class="ph-duotone ph-microsoft-excel-logo"></i> تصدير الفواتير</a>
            <a href="/ERP/purchasing/dashboard/export?type=suppliers" class="export-btn"><i class="ph-duotone ph-microsoft-excel-logo"></i> تصدير الموردين</a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="kpi-grid">
        <div class="p-card" style="border-bottom: 4px solid var(--c-rose);">
            <div class="kpi-header">
                <div class="kpi-title"><?= $t['spend'] ?></div>
                <div class="kpi-icon" style="background: var(--c-rose-bg); color: var(--c-rose);"><i class="ph-duotone ph-currency-circle-dollar"></i></div>
            </div>
            <div class="kpi-value" style="color: var(--c-rose);"><?= number_format($monthly_spend) ?> <span style="font-size: 0.8rem; color: var(--d-muted);"><?= $currency ?></span></div>
        </div>

        <div class="p-card" style="border-bottom: 4px solid var(--c-blue);">
            <div class="kpi-header">
                <div class="kpi-title"><?= $t['pos'] ?></div>
                <div class="kpi-icon" style="background: var(--c-blue-bg); color: var(--c-blue);"><i class="ph-duotone ph-file-text"></i></div>
            </div>
            <div class="kpi-value"><?= number_format($open_pos) ?> <span style="font-size: 0.8rem; color: var(--d-muted);">أمر</span></div>
        </div>

        <div class="p-card" style="border-bottom: 4px solid var(--c-emerald);">
            <div class="kpi-header">
                <div class="kpi-title"><?= $t['suppliers'] ?></div>
                <div class="kpi-icon" style="background: var(--c-emerald-bg); color: var(--c-emerald);"><i class="ph-duotone ph-buildings"></i></div>
            </div>
            <div class="kpi-value"><?= number_format($suppliers_count) ?> <span style="font-size: 0.8rem; color: var(--d-muted);">مورد</span></div>
        </div>

        <div class="p-card" style="border-bottom: 4px solid var(--c-orange);">
            <div class="kpi-header">
                <div class="kpi-title"><?= $t['receipts'] ?></div>
                <div class="kpi-icon" style="background: var(--c-orange-bg); color: var(--c-orange);"><i class="ph-duotone ph-package"></i></div>
            </div>
            <div class="kpi-value"><?= number_format($pending_receipts) ?> <span style="font-size: 0.8rem; color: var(--d-muted);">إذن</span></div>
        </div>

        <div class="p-card" style="border-bottom: 4px solid var(--c-indigo);">
            <div class="kpi-header">
                <div class="kpi-title"><?= $t['unpaid'] ?></div>
                <div class="kpi-icon" style="background: var(--c-indigo-bg); color: var(--c-indigo);"><i class="ph-duotone ph-receipt"></i></div>
            </div>
            <div class="kpi-value" style="color: var(--c-indigo);"><?= number_format($unpaid_invoices) ?> <span style="font-size: 0.8rem; color: var(--d-muted);"><?= $currency ?></span></div>
        </div>
    </div>

    <!-- Charts Area -->
    <div class="charts-grid">
        <div class="p-card col-8">
            <h3 class="card-title"><i class="ph-duotone ph-trend-up" style="color: var(--c-rose); font-size:1.4rem;"></i> <?= $t['chart_title'] ?></h3>
            <div class="chart-container"><canvas id="spendChart"></canvas></div>
        </div>

        <div class="p-card col-4">
            <h3 class="card-title"><i class="ph-duotone ph-chart-donut" style="color: var(--c-blue); font-size:1.4rem;"></i> تحليل حالة أوامر الشراء</h3>
            <div class="chart-container"><canvas id="statusChart"></canvas></div>
        </div>

        <div class="p-card col-6">
            <h3 class="card-title"><i class="ph-duotone ph-crown" style="color: var(--c-orange); font-size:1.4rem;"></i> أعلى 5 موردين تعاملاً (بالقيمة)</h3>
            <div class="chart-container"><canvas id="supplierChart"></canvas></div>
        </div>

        <div class="p-card col-6">
            <h3 class="card-title"><i class="ph-duotone ph-stack" style="color: var(--c-emerald); font-size:1.4rem;"></i> أكثر 5 أصناف تم شراؤها</h3>
            <div class="chart-container"><canvas id="productChart"></canvas></div>
        </div>
    </div>

    <!-- Data Tables -->
    <div class="tables-grid">
        <div class="p-card col-6">
            <h3 class="card-title"><i class="ph-duotone ph-clock" style="color: var(--c-blue); font-size:1.4rem;"></i> <?= $t['recent_po'] ?></h3>
            <div style="overflow-x:auto;">
                <table class="mod-table">
                    <thead><tr><th>رقم الأمر</th><th>المورد</th><th style="text-align:end;">الإجمالي</th><th style="text-align:center;">الحالة</th></tr></thead>
                    <tbody>
                        <?php if(empty($recentOrders)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:30px; color:var(--d-muted);">لا توجد أوامر حديثة</td></tr>
                        <?php else: foreach ($recentOrders as $po): ?>
                            <tr>
                                <td style="font-family:monospace; font-weight:900; color:var(--c-blue);"><a href="/ERP/purchasing/orders/<?= $po->id ?>" style="text-decoration:none; color:inherit;"><?= $po->po_number ?></a></td>
                                <td><?= mb_substr($po->supplier_name ?? '---', 0, 20) ?></td>
                                <td style="text-align:end; font-family:monospace; font-weight:800; font-size:1rem; color:var(--d-text);"><?= number_format($po->total_amount, 2) ?></td>
                                <td style="text-align:center;"><?= getBadge($po->status) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="p-card col-6">
            <h3 class="card-title"><i class="ph-duotone ph-warning-circle" style="color: var(--c-rose); font-size:1.4rem;"></i> أحدث فواتير الموردين المستحقة</h3>
            <div style="overflow-x:auto;">
                <table class="mod-table">
                    <thead><tr><th>رقم الفاتورة</th><th>المورد</th><th style="text-align:end;">المبلغ المتبقي</th><th style="text-align:center;">الاستحقاق</th></tr></thead>
                    <tbody>
                        <?php if(empty($recentInvoices)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:30px; color:var(--d-muted);">لا توجد فواتير حديثة</td></tr>
                        <?php else: foreach ($recentInvoices as $inv): ?>
                            <tr>
                                <td style="font-family:monospace; font-weight:900; color:var(--c-rose);"><a href="/ERP/purchasing/invoices/<?= $inv->id ?>" style="text-decoration:none; color:inherit;"><?= $inv->invoice_number ?></a></td>
                                <td><?= mb_substr($inv->supplier_name ?? '---', 0, 20) ?></td>
                                <td style="text-align:end; font-family:monospace; font-weight:900; font-size:1.05rem; color:var(--c-rose);"><?= number_format($inv->balance, 2) ?></td>
                                <td style="text-align:center; font-weight:800; color:var(--d-muted);"><?= $inv->due_date ?></td>
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
    Chart.defaults.font.family = "'Inter', 'Cairo', sans-serif";
    Chart.defaults.color = '#64748b';
    
    const commonTooltip = {
        backgroundColor: 'rgba(15, 23, 42, 0.9)',
        titleFont: { size: 14, family: "'Inter', 'Cairo', sans-serif" },
        bodyFont: { size: 14, weight: 'bold', family: "monospace" },
        padding: 12,
        cornerRadius: 8,
        displayColors: false
    };

    // 1. Spend Trend Line Chart
    const spendCtx = document.getElementById('spendChart').getContext('2d');
    let spendGradient = spendCtx.createLinearGradient(0, 0, 0, 320);
    spendGradient.addColorStop(0, 'rgba(225, 29, 72, 0.4)');
    spendGradient.addColorStop(1, 'rgba(225, 29, 72, 0.0)');

    new Chart(spendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($c_spend_labels) ?>,
            datasets: [{
                label: 'الإنفاق (<?= $currency ?>)',
                data: <?= json_encode($c_spend_data) ?>,
                borderColor: '#e11d48',
                backgroundColor: spendGradient,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#e11d48',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: commonTooltip },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9', borderDash: [5, 5] }, border: { display: false } },
                x: { grid: { display: false }, border: { display: false } }
            },
            interaction: { intersect: false, mode: 'index' }
        }
    });

    // 2. PO Status Doughnut Chart
    const statusDataRaw = <?= json_encode($c_status_data) ?>;
    const isStatusEmpty = statusDataRaw.every(item => item === 0) || statusDataRaw.length === 0;

    new Chart(document.getElementById('statusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: isStatusEmpty ? ['لا توجد أوامر'] : <?= json_encode($c_status_labels) ?>,
            datasets: [{
                data: isStatusEmpty ? [1] : statusDataRaw,
                backgroundColor: isStatusEmpty ? ['#e2e8f0'] : ['#2563eb', '#059669', '#ea580c', '#64748b', '#dc2626', '#8b5cf6'],
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { weight: 'bold' } } },
                tooltip: isStatusEmpty ? { enabled: false } : commonTooltip
            }
        }
    });

    // 3. Top Suppliers Bar Chart
    new Chart(document.getElementById('supplierChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($c_sup_labels) ?>,
            datasets: [{
                label: 'قيمة التعامل (<?= $currency ?>)',
                data: <?= json_encode($c_sup_data) ?>,
                backgroundColor: '#f59e0b',
                borderRadius: 8,
                borderSkipped: false,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: commonTooltip },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9', borderDash: [5, 5] }, border: { display: false } },
                x: { grid: { display: false }, border: { display: false }, ticks: { font: { weight: 'bold' } } }
            }
        }
    });

    // 4. Top Products Horizontal Bar Chart
    new Chart(document.getElementById('productChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($c_prod_labels) ?>,
            datasets: [{
                label: 'قيمة الشراء (<?= $currency ?>)',
                data: <?= json_encode($c_prod_data) ?>,
                backgroundColor: '#059669',
                borderRadius: 8,
                borderSkipped: false,
                barPercentage: 0.6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: commonTooltip },
            scales: {
                x: { beginAtZero: true, grid: { color: '#f1f5f9', borderDash: [5, 5] }, border: { display: false } },
                y: { grid: { display: false }, border: { display: false }, ticks: { font: { weight: 'bold' } } }
            }
        }
    });

});
</script>