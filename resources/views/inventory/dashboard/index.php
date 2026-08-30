<?php
// Path: resources/views/inventory/dashboard/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = 'SAR';

$kpis = $kpis ?? [
    'stock_value' => 0, 'total_products' => 0, 'active_warehouses' => 0,
    'low_stock_alerts' => 0, 'total_movements' => 0, 'pending_transfers' => 0
];
$chartData = $chartData ?? [
    'movements_in' => 0, 'movements_out' => 0,
    'ops_breakdown' => ['transfers' => 0, 'deliveries' => 0, 'returns' => 0, 'adjustments' => 0]
];
$recentMovements = $recentMovements ?? [];
$recentDeliveries = $recentDeliveries ?? [];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<style>
    :root {
        --c-brand: #2563eb;
        --c-brand-dark: #1d4ed8;
        --c-brand-light: #eff6ff;
        --c-slate-dark: #0f172a;
        --c-slate-muted: #64748b;
        --c-border: #e2e8f0;
        --c-card-bg: #ffffff;
    }

    .dash-wrapper { padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; border-bottom: 1px solid var(--c-border); padding-bottom: 18px; }
    .dash-title-box { display: flex; align-items: center; gap: 16px; }
    .dash-icon { width: 52px; height: 52px; background: var(--c-brand-light); color: var(--c-brand); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 2rem; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15); }
    .dash-title { margin: 0; color: var(--c-slate-dark); font-size: 1.8rem; font-weight: 800; }
    
    .btn-excel { background: #16a34a; color: #ffffff !important; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2); }
    .btn-excel:hover { background: #15803d; transform: translateY(-2px); }

    .hub-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 32px; }
    .hub-card { background: var(--c-card-bg); border: 1px solid var(--c-border); border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 14px; text-decoration: none; color: var(--c-slate-dark); font-weight: 800; font-size: 0.9rem; transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .hub-card:hover { border-color: var(--c-brand); transform: translateY(-3px); box-shadow: 0 8px 16px rgba(37, 99, 235, 0.1); background: var(--c-brand-light); }
    .hub-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }

    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px; }
    .kpi-card { background: var(--c-card-bg); border: 1px solid var(--c-border); border-radius: 16px; padding: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .kpi-card::before { content:''; position: absolute; top:0; right:0; left:0; height: 4px; background: var(--c-brand); }
    .kpi-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .kpi-label { font-size: 0.8rem; font-weight: 800; color: var(--c-slate-muted); text-transform: uppercase; }
    .kpi-value { font-size: 1.8rem; font-weight: 900; color: var(--c-slate-dark); font-family: monospace; }

    .grid-split { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px; }
    @media (max-width: 1024px) { .grid-split { grid-template-columns: 1fr; } }

    .panel { background: var(--c-card-bg); border: 1px solid var(--c-border); border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .panel-title { font-size: 1.1rem; font-weight: 800; color: var(--c-slate-dark); margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; }

    .dash-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    .dash-table th { padding: 12px 14px; background: #f8fafc; color: var(--c-slate-muted); font-weight: 800; font-size: 0.75rem; text-transform: uppercase; text-align: start; border-bottom: 1px solid var(--c-border); }
    .dash-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; font-weight: 600; vertical-align: middle; }
    .dash-table tr:hover td { background: #f8fafc; }

    .badge { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-block; }
</style>

<div class="dash-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="dash-header">
        <div class="dash-title-box">
            <div class="dash-icon"><i class="ph-duotone ph-chart-line-up"></i></div>
            <div>
                <h2 class="dash-title">لوحة التحكم المخزنية الشاملة</h2>
                <p style="margin:4px 0 0 0; color:var(--c-slate-muted); font-weight:600;">مراقبة تحركات البضائع، المستودعات، وقيمة المخزون لحظياً.</p>
            </div>
        </div>
        <button onclick="exportToExcel()" class="btn-excel"><i class="ph-bold ph-file-xls"></i> تصدير لـ Excel</button>
    </div>

    <div class="hub-grid">
        <a href="/ERP/inventory/stock/ledger" class="hub-card">
            <div class="hub-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-duotone ph-list-dashes"></i></div>
            <span>كارت الصنف</span>
        </a>
        <a href="/ERP/inventory/stock/transfers" class="hub-card">
            <div class="hub-icon" style="background:#fff7ed; color:#ea580c;"><i class="ph-duotone ph-truck"></i></div>
            <span>التحويلات</span>
        </a>
        <a href="/ERP/inventory/delivery-notes" class="hub-card">
            <div class="hub-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-package"></i></div>
            <span>أذون التسليم</span>
        </a>
        <a href="/ERP/inventory/returns" class="hub-card">
            <div class="hub-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-arrow-u-down-left"></i></div>
            <span>المرتجعات</span>
        </a>
        <a href="/ERP/inventory/adjustments" class="hub-card">
            <div class="hub-icon" style="background:#f3e8ff; color:#9333ea;"><i class="ph-duotone ph-scales"></i></div>
            <span>التسويات</span>
        </a>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card" style="--c-brand: #2563eb;">
            <div class="kpi-header">
                <span class="kpi-label">قيمة المخزون التقديرية</span>
                <i class="ph-duotone ph-currency-circle-dollar" style="font-size:1.8rem; color:#2563eb;"></i>
            </div>
            <div class="kpi-value"><?= number_format($kpis['stock_value'] ?? 0, 2) ?> <span style="font-size:0.8rem; color:var(--c-slate-muted);"><?= $currency ?></span></div>
        </div>

        <div class="kpi-card" style="--c-brand: #059669;">
            <div class="kpi-header">
                <span class="kpi-label">إجمالي الأصناف المسجلة</span>
                <i class="ph-duotone ph-barcode" style="font-size:1.8rem; color:#059669;"></i>
            </div>
            <div class="kpi-value"><?= number_format($kpis['total_products'] ?? 0) ?></div>
        </div>

        <div class="kpi-card" style="--c-brand: #9333ea;">
            <div class="kpi-header">
                <span class="kpi-label">إجمالي الحركات المسجلة</span>
                <i class="ph-duotone ph-arrows-down-up" style="font-size:1.8rem; color:#9333ea;"></i>
            </div>
            <div class="kpi-value"><?= number_format($kpis['total_movements'] ?? 0) ?></div>
        </div>

        <div class="kpi-card" style="--c-brand: #ea580c;">
            <div class="kpi-header">
                <span class="kpi-label">تحويلات قيد الانتظار</span>
                <i class="ph-duotone ph-clock" style="font-size:1.8rem; color:#ea580c;"></i>
            </div>
            <div class="kpi-value" style="color:#ea580c;"><?= number_format($kpis['pending_transfers'] ?? 0) ?></div>
        </div>
    </div>

    <div class="grid-split">
        <div class="panel">
            <div class="panel-title">
                <span><i class="ph-duotone ph-arrows-down-up" style="color:#2563eb;"></i> حجم التدفق المخزني (الوارد vs المنصرف)</span>
            </div>
            <div style="height: 260px; position: relative;">
                <canvas id="flowChart"></canvas>
            </div>
        </div>

        <div class="panel">
            <div class="panel-title">
                <span><i class="ph-duotone ph-chart-pie-slice" style="color:#9333ea;"></i> توزيع أذون العمليات المخزنية</span>
            </div>
            <div style="height: 260px; position: relative;">
                <canvas id="opsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid-split">
        <div class="panel">
            <div class="panel-title">
                <span><i class="ph-duotone ph-clock-counter-clockwise" style="color:#059669;"></i> أحدث الحركات المخزنية</span>
                <a href="/ERP/inventory/stock/ledger" style="font-size:0.8rem; color:#2563eb; text-decoration:none;">عرض الكل ➔</a>
            </div>
            <table class="dash-table" id="exportTableMovements">
                <thead>
                    <tr>
                        <th>الصنف</th>
                        <th>المستودع</th>
                        <th>نوع الحركة</th>
                        <th style="text-align:center;">الكمية</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($recentMovements)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px; color:#94a3b8;">لا توجد حركات مسجلة مؤخراً</td></tr>
                    <?php else: foreach($recentMovements as $m): ?>
                        <tr>
                            <td>
                                <div><?= htmlspecialchars($m->product_name ?? '---') ?></div>
                                <div style="font-family:monospace; color:#2563eb; font-size:0.75rem;"><?= htmlspecialchars($m->product_code ?? '---') ?></div>
                            </td>
                            <td><?= htmlspecialchars($m->warehouse_name ?? '---') ?></td>
                            <td>
                                <span class="badge" style="background:<?= ($m->movement_type ?? '') === 'in' ? '#f0fdf4; color:#16a34a;' : '#fef2f2; color:#dc2626;' ?>">
                                    <?= ($m->movement_type ?? '') === 'in' ? 'وارد (+)' : 'منصرف (-)' ?>
                                </span>
                            </td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#0f172a;">
                                <?= number_format($m->quantity ?? 0, 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <div class="panel-title">
                <span><i class="ph-duotone ph-truck" style="color:#ea580c;"></i> أحدث أذون التسليم (مبيعات)</span>
                <a href="/ERP/inventory/delivery-notes" style="font-size:0.8rem; color:#2563eb; text-decoration:none;">عرض الكل ➔</a>
            </div>
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>رقم الإذن</th>
                        <th>العميل</th>
                        <th>التاريخ</th>
                        <th style="text-align:center;">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($recentDeliveries)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px; color:#94a3b8;">لا توجد أذون تسليم حديثة</td></tr>
                    <?php else: foreach($recentDeliveries as $dn): ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:#2563eb;"><?= htmlspecialchars($dn->delivery_number ?? '---') ?></td>
                            <td><?= htmlspecialchars($dn->customer_name ?? '---') ?></td>
                            <td><?= htmlspecialchars($dn->delivery_date ?? '---') ?></td>
                            <td style="text-align:center;">
                                <span class="badge" style="background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;">
                                    <?= htmlspecialchars($dn->status ?? 'draft') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    Chart.defaults.font.family = "'Cairo', 'Inter', sans-serif";

    // Chart 1
    const ctxFlow = document.getElementById('flowChart').getContext('2d');
    new Chart(ctxFlow, {
        type: 'bar',
        data: {
            labels: ['إجمالي الوارد (+)', 'إجمالي المنصرف (-)'],
            datasets: [{
                data: [<?= (float)($chartData['movements_in'] ?? 0) ?>, <?= (float)($chartData['movements_out'] ?? 0) ?>],
                backgroundColor: ['#16a34a', '#dc2626'],
                borderRadius: 8,
                barThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Chart 2
    const ctxOps = document.getElementById('opsChart').getContext('2d');
    new Chart(ctxOps, {
        type: 'doughnut',
        data: {
            labels: ['تحويلات', 'أذون تسليم', 'مرتجعات', 'تسويات'],
            datasets: [{
                data: [
                    <?= (int)($chartData['ops_breakdown']['transfers'] ?? 0) ?>,
                    <?= (int)($chartData['ops_breakdown']['deliveries'] ?? 0) ?>,
                    <?= (int)($chartData['ops_breakdown']['returns'] ?? 0) ?>,
                    <?= (int)($chartData['ops_breakdown']['adjustments'] ?? 0) ?>
                ],
                backgroundColor: ['#ea580c', '#059669', '#dc2626', '#9333ea'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: { legend: { position: 'bottom' } }
        }
    });
});

function exportToExcel() {
    let table = document.getElementById("exportTableMovements");
    let rows = [];
    
    rows.push(["تقرير حركات المخزون - Nour Trust ERP"]);
    rows.push([]);
    rows.push(["الصنف", "المستودع", "نوع الحركة", "الكمية"]);

    for (let row of table.rows) {
        if(row.parentElement.tagName === 'THEAD') continue;
        let cols = Array.from(row.cells).map(cell => '"' + cell.innerText.replace(/\n/g, ' ') + '"');
        if(cols.length > 0) rows.push(cols);
    }

    let csvContent = "\uFEFF" + rows.map(e => e.join(",")).join("\n");
    let blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    let url = URL.createObjectURL(blob);
    let link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", "Inventory_Dashboard_Report_" + new Date().toISOString().slice(0,10) + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>