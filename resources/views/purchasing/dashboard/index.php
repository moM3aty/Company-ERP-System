<?php
// Path: resources/views/purchasing/dashboard/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency(); // جلب رمز العملة النشطة

$t = [
    'ar' => [
        'title' => 'لوحة القيادة والتحليل الإداري للمشتريات',
        'desc' => 'مراقبة الموردين، إنفاق الشحنات، متابعة الاعتمادات، وتدفق البضائع الواردة.',
        'spend' => 'إنفاق الشهر الحالي', 'pos' => 'أوامر شراء جارية (PO)',
        'prs' => 'طلبات بانتظار الاعتماد (PR)', 'suppliers' => 'الموردين النشطين',
        'receipts' => 'استلامات قيد الفحص (GRN)', 'unpaid' => 'مستحقات غير مدفوعة',
        'chart_title' => 'مؤشر الإنفاق الشهري (آخر 6 شهور)', 'status_chart' => 'تحليل حالة أوامر الشراء',
        'top_sup' => 'أعلى 5 موردين تعاملاً (بالقيمة)', 'top_prod' => 'أكثر 5 أصناف تم شراؤها',
        'recent_po' => 'أحدث أوامر الشراء الصادرة', 'recent_inv' => 'أحدث فواتير الموردين المستحقة',
        'exp_po' => 'تصدير أوامر الشراء', 'exp_inv' => 'تصدير الفواتير', 'exp_sup' => 'تصدير الموردين',
        'col_po' => 'رقم الأمر', 'col_sup' => 'المورد', 'col_total' => 'الإجمالي', 'col_status' => 'الحالة',
        'col_inv' => 'رقم الفاتورة', 'col_bal' => 'المبلغ المتبقي', 'col_due' => 'الاستحقاق', 'no_data' => 'لا توجد بيانات'
    ],
    'en' => [
        'title' => 'Purchasing Control & Analytics Dashboard',
        'desc' => 'Monitor vendors, spend trends, pending approvals, and inbound logistics.',
        'spend' => 'Monthly Spend', 'pos' => 'Active POs',
        'prs' => 'Pending Requisitions', 'suppliers' => 'Active Vendors',
        'receipts' => 'Pending Receipts', 'unpaid' => 'Outstanding Payables',
        'chart_title' => 'Monthly Spend Trend (6 Months)', 'status_chart' => 'PO Status Analysis',
        'top_sup' => 'Top 5 Suppliers (by Value)', 'top_prod' => 'Top 5 Purchased Products',
        'recent_po' => 'Recent Purchase Orders', 'recent_inv' => 'Recent Due Invoices',
        'exp_po' => 'Export POs', 'exp_inv' => 'Export Invoices', 'exp_sup' => 'Export Suppliers',
        'col_po' => 'PO Number', 'col_sup' => 'Supplier', 'col_total' => 'Total', 'col_status' => 'Status',
        'col_inv' => 'Invoice No', 'col_bal' => 'Balance Due', 'col_due' => 'Due Date', 'no_data' => 'No data available'
    ]
][$isRtl ? 'ar' : 'en'];

function getBadge($status) {
    $colors = ['draft' => '#64748b', 'sent' => '#2563eb', 'completed' => '#059669', 'paid' => '#059669', 'partially_received' => '#ea580c', 'unpaid' => '#dc2626', 'cancelled' => '#dc2626'];
    $c = $colors[$status] ?? '#64748b';
    return "<span style='color:{$c}; font-weight:800; font-size:0.75rem; border:1px solid {$c}; padding:4px 10px; border-radius:8px; background: {$c}15;'>".strtoupper($status)."</span>";
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<style>
    :root { --d-bg: #f8fafc; --d-surface: #ffffff; --d-border: #e2e8f0; --d-text: #0f172a; --d-muted: #64748b; --c-blue: #2563eb; --c-blue-bg: #eff6ff; --c-rose: #e11d48; --c-rose-bg: #ffe4e6; --c-emerald: #059669; --c-emerald-bg: #d1fae5; --c-orange: #ea580c; --c-orange-bg: #ffedd5; --c-indigo: #4338ca; --c-indigo-bg: #e0e7ff; }
    .dashboard-wrapper { padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: var(--d-bg); min-height: 100vh;}
    .top-action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .page-title { margin: 0; color: var(--d-text); font-size: 1.6rem; font-weight: 900; display: flex; align-items: center; gap: 12px; }
    .page-title-icon { background: linear-gradient(135deg, var(--c-blue), #1d4ed8); color: #fff; padding: 8px 12px; border-radius: 12px; display: flex; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }
    .export-hub { display: flex; gap: 10px; flex-wrap: wrap; }
    .export-btn { background: var(--d-surface); color: var(--d-text); border: 1px solid var(--d-border); padding: 8px 16px; border-radius: 8px; font-weight: 800; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .export-btn:hover { background: #f1f5f9; border-color: #cbd5e1; transform: translateY(-2px); }
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 24px; }
    .charts-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 24px; margin-bottom: 24px; }
    .tables-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 24px; margin-bottom: 24px; }
    .p-card { background: var(--d-surface); border: 1px solid rgba(226, 232, 240, 0.8); border-radius: 16px; padding: 20px; box-shadow: 0 4px 10px -2px rgba(0,0,0,0.02); transition: 0.3s; }
    .card-title { margin: 0 0 20px 0; font-size: 1.05rem; font-weight: 900; color: var(--d-text); display: flex; align-items: center; gap: 10px; }
    .kpi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
    .kpi-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
    .kpi-title { font-size: 0.8rem; font-weight: 800; color: var(--d-muted); text-transform: uppercase; }
    .kpi-value { font-size: 1.6rem; font-weight: 900; color: var(--d-text); font-family: monospace; display: flex; align-items: baseline; gap: 6px; }
    .chart-container { position: relative; width: 100%; height: 280px; }
    .col-8 { grid-column: span 8; } .col-6 { grid-column: span 6; } .col-4 { grid-column: span 4; }
    @media(max-width: 1024px) { .col-8, .col-6, .col-4 { grid-column: span 12; } }
    .mod-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .mod-table th { padding: 12px 16px; background: #f8fafc; color: var(--d-muted); font-weight: 800; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid var(--d-border); text-align: start; }
    .mod-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; font-weight: 600; }
</style>

<div class="dashboard-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="top-action-bar">
        <h2 class="page-title"><div class="page-title-icon"><i class="ph-duotone ph-chart-pie-slice"></i></div> <?= $t['title'] ?></h2>
        <div class="export-hub">
            <a href="/ERP/purchasing/dashboard/export?type=pos" class="export-btn"><i class="ph-duotone ph-file-csv text-green-600"></i> <?= $t['exp_po'] ?></a>
            <a href="/ERP/purchasing/dashboard/export?type=invoices" class="export-btn"><i class="ph-duotone ph-file-csv text-green-600"></i> <?= $t['exp_inv'] ?></a>
            <a href="/ERP/purchasing/dashboard/export?type=suppliers" class="export-btn"><i class="ph-duotone ph-file-csv text-green-600"></i> <?= $t['exp_sup'] ?></a>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="p-card" style="border-bottom: 3px solid var(--c-rose);"><div class="kpi-header"><div class="kpi-title"><?= $t['spend'] ?></div><div class="kpi-icon" style="background: var(--c-rose-bg); color: var(--c-rose);"><i class="ph-duotone ph-currency-circle-dollar"></i></div></div><div class="kpi-value" style="color: var(--c-rose);"><?= number_format($kpis['monthly_spend']) ?> <span style="font-size: 0.75rem; color: var(--d-muted);"><?= $currency ?></span></div></div>
        <div class="p-card" style="border-bottom: 3px solid var(--c-blue);"><div class="kpi-header"><div class="kpi-title"><?= $t['pos'] ?></div><div class="kpi-icon" style="background: var(--c-blue-bg); color: var(--c-blue);"><i class="ph-duotone ph-file-text"></i></div></div><div class="kpi-value"><?= number_format($kpis['open_pos']) ?></div></div>
        <div class="p-card" style="border-bottom: 3px solid var(--c-emerald);"><div class="kpi-header"><div class="kpi-title"><?= $t['suppliers'] ?></div><div class="kpi-icon" style="background: var(--c-emerald-bg); color: var(--c-emerald);"><i class="ph-duotone ph-buildings"></i></div></div><div class="kpi-value"><?= number_format($kpis['suppliers_count']) ?></div></div>
        <div class="p-card" style="border-bottom: 3px solid var(--c-orange);"><div class="kpi-header"><div class="kpi-title"><?= $t['receipts'] ?></div><div class="kpi-icon" style="background: var(--c-orange-bg); color: var(--c-orange);"><i class="ph-duotone ph-package"></i></div></div><div class="kpi-value"><?= number_format($kpis['pending_receipts']) ?></div></div>
        <div class="p-card" style="border-bottom: 3px solid var(--c-indigo);"><div class="kpi-header"><div class="kpi-title"><?= $t['unpaid'] ?></div><div class="kpi-icon" style="background: var(--c-indigo-bg); color: var(--c-indigo);"><i class="ph-duotone ph-receipt"></i></div></div><div class="kpi-value" style="color: var(--c-indigo);"><?= number_format($kpis['unpaid_invoices']) ?> <span style="font-size: 0.75rem; color: var(--d-muted);"><?= $currency ?></span></div></div>
    </div>

    <div class="charts-grid">
        <div class="p-card col-8"><h3 class="card-title"><i class="ph-duotone ph-trend-up text-rose-500"></i> <?= $t['chart_title'] ?></h3><div class="chart-container"><canvas id="spendChart"></canvas></div></div>
        <div class="p-card col-4"><h3 class="card-title"><i class="ph-duotone ph-chart-donut text-blue-500"></i> <?= $t['status_chart'] ?></h3><div class="chart-container"><canvas id="statusChart"></canvas></div></div>
        <div class="p-card col-6"><h3 class="card-title"><i class="ph-duotone ph-crown text-orange-500"></i> <?= $t['top_sup'] ?></h3><div class="chart-container"><canvas id="supplierChart"></canvas></div></div>
        <div class="p-card col-6"><h3 class="card-title"><i class="ph-duotone ph-stack text-emerald-500"></i> <?= $t['top_prod'] ?></h3><div class="chart-container"><canvas id="productChart"></canvas></div></div>
    </div>

    <div class="tables-grid">
        <div class="p-card col-6">
            <h3 class="card-title"><i class="ph-duotone ph-clock text-blue-500"></i> <?= $t['recent_po'] ?></h3>
            <div style="overflow-x:auto;">
                <table class="mod-table">
                    <thead><tr><th><?= $t['col_po'] ?></th><th><?= $t['col_sup'] ?></th><th style="text-align:end;"><?= $t['col_total'] ?></th><th style="text-align:center;"><?= $t['col_status'] ?></th></tr></thead>
                    <tbody>
                        <?php if(empty($recentOrders)): ?><tr><td colspan="4" style="text-align:center; padding:20px; color:var(--d-muted);"><?= $t['no_data'] ?></td></tr>
                        <?php else: foreach ($recentOrders as $po): ?>
                            <tr>
                                <td style="font-family:monospace; font-weight:800; color:var(--c-blue);"><a href="/ERP/purchasing/orders/<?= $po->id ?>" style="text-decoration:none; color:inherit;"><?= $po->po_number ?></a></td>
                                <td><?= mb_substr($po->supplier_name ?? '---', 0, 20) ?></td>
                                <td style="text-align:end; font-family:monospace; font-weight:800;"><?= number_format($po->total_amount, 2) ?></td>
                                <td style="text-align:center;"><?= getBadge($po->status) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="p-card col-6">
            <h3 class="card-title"><i class="ph-duotone ph-warning-circle text-rose-500"></i> <?= $t['recent_inv'] ?></h3>
            <div style="overflow-x:auto;">
                <table class="mod-table">
                    <thead><tr><th><?= $t['col_inv'] ?></th><th><?= $t['col_sup'] ?></th><th style="text-align:end;"><?= $t['col_bal'] ?></th><th style="text-align:center;"><?= $t['col_due'] ?></th></tr></thead>
                    <tbody>
                        <?php if(empty($recentInvoices)): ?><tr><td colspan="4" style="text-align:center; padding:20px; color:var(--d-muted);"><?= $t['no_data'] ?></td></tr>
                        <?php else: foreach ($recentInvoices as $inv): ?>
                            <tr>
                                <td style="font-family:monospace; font-weight:800; color:var(--c-rose);"><a href="/ERP/purchasing/invoices/<?= $inv->id ?>" style="text-decoration:none; color:inherit;"><?= $inv->invoice_number ?></a></td>
                                <td><?= mb_substr($inv->supplier_name ?? '---', 0, 20) ?></td>
                                <td style="text-align:end; font-family:monospace; font-weight:800; color:var(--c-rose);"><?= number_format($inv->balance, 2) ?></td>
                                <td style="text-align:center; font-weight:700; color:var(--d-muted);"><?= $inv->due_date ?></td>
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
    if(typeof Chart === 'undefined') return;
    Chart.defaults.font.family = "<?= $isRtl ? 'Cairo, sans-serif' : 'Inter, sans-serif' ?>";
    Chart.defaults.color = '#64748b';
    const cTT = { backgroundColor: 'rgba(15, 23, 42, 0.9)', titleFont: { size: 13 }, bodyFont: { size: 13, weight: 'bold', family: "monospace" }, padding: 10, cornerRadius: 6, displayColors: false };

    // 1. Spend Chart
    try {
        const spendCtx = document.getElementById('spendChart').getContext('2d');
        let spendGradient = spendCtx.createLinearGradient(0, 0, 0, 300);
        spendGradient.addColorStop(0, 'rgba(225, 29, 72, 0.3)'); spendGradient.addColorStop(1, 'rgba(225, 29, 72, 0.0)');
        new Chart(spendCtx, { type: 'line', data: { labels: <?= json_encode($charts['spend_labels'] ?? [], JSON_UNESCAPED_UNICODE) ?>, datasets: [{ label: '<?= $currency ?>', data: <?= json_encode($charts['spend_data'] ?? []) ?>, borderColor: '#e11d48', backgroundColor: spendGradient, borderWidth: 3, fill: true, tension: 0.4, pointBackgroundColor: '#ffffff', pointBorderColor: '#e11d48', pointBorderWidth: 2, pointRadius: 4 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: cTT }, scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9', borderDash: [4, 4] }, border: { display: false } }, x: { grid: { display: false }, border: { display: false } } } } });
    } catch(e){}

    // 2. Status Chart
    try {
        const sdRaw = <?= json_encode($charts['status_data'] ?? []) ?>; const isSE = sdRaw.length === 0;
        new Chart(document.getElementById('statusChart').getContext('2d'), { type: 'doughnut', data: { labels: isSE ? ['<?= $t["no_data"] ?>'] : <?= json_encode($charts['status_labels'] ?? [], JSON_UNESCAPED_UNICODE) ?>, datasets: [{ data: isSE ? [1] : sdRaw, backgroundColor: isSE ? ['#e2e8f0'] : ['#2563eb', '#059669', '#ea580c', '#64748b', '#dc2626', '#8b5cf6'], borderWidth: 2, borderColor: '#ffffff' }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15 } }, tooltip: isSE ? { enabled: false } : cTT } } });
    } catch(e){}

    // 3. Top Suppliers
    try {
        new Chart(document.getElementById('supplierChart').getContext('2d'), { type: 'bar', data: { labels: <?= json_encode($charts['top_sup_labels'] ?? [], JSON_UNESCAPED_UNICODE) ?>, datasets: [{ label: '<?= $currency ?>', data: <?= json_encode($charts['top_sup_data'] ?? []) ?>, backgroundColor: '#f59e0b', borderRadius: 6, barPercentage: 0.6 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: cTT }, scales: { y: { beginAtZero: true, grid: { borderDash: [4, 4] } }, x: { grid: { display: false } } } } });
    } catch(e){}

    // 4. Top Products
    try {
        new Chart(document.getElementById('productChart').getContext('2d'), { type: 'bar', data: { labels: <?= json_encode($charts['top_prod_labels'] ?? [], JSON_UNESCAPED_UNICODE) ?>, datasets: [{ label: '<?= $currency ?>', data: <?= json_encode($charts['top_prod_data'] ?? []) ?>, backgroundColor: '#059669', borderRadius: 6, barPercentage: 0.6 }] }, options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: cTT }, scales: { x: { beginAtZero: true, grid: { borderDash: [4, 4] } }, y: { grid: { display: false } } } } });
    } catch(e){}
});
</script>