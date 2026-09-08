<?php
// Path: resources/views/inventory/dashboard/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isset($_SESSION['locale']) && $_SESSION['locale'] === 'en' ? false : true;

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

if (!isset($kpis)) {
    $kpis = ['stock_value' => 0, 'total_products' => 0, 'active_warehouses' => 0, 'low_stock_alerts' => 0, 'total_movements' => 0, 'pending_transfers' => 0];
}
if (!isset($chartData)) {
    $chartData = ['movements_in' => 0, 'movements_out' => 0, 'ops_breakdown' => ['transfers' => 0, 'deliveries' => 0, 'returns' => 0, 'adjustments' => 0]];
}
if (!isset($recentMovements)) $recentMovements = [];
if (!isset($recentDeliveries)) $recentDeliveries = [];

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title' => 'لوحة التحكم المخزنية الشاملة',
        'desc' => 'مراقبة تحركات البضائع، المستودعات، وقيمة المخزون لحظياً.',
        'export' => 'تصدير لـ Excel',
        'active_scope' => 'الفرع النشط حالياً:',
        'card_ledger' => 'كارت الصنف', 'card_transfers' => 'التحويلات', 'card_deliveries' => 'أذون التسليم',
        'card_returns' => 'المرتجعات', 'card_adjustments' => 'التسويات',
        'kpi_value' => 'قيمة المنتجات التقديرية', 'kpi_products' => 'إجمالي الأصناف المسجلة',
        'kpi_movements' => 'إجمالي الحركات المسجلة', 'kpi_pending' => 'تحويلات قيد الانتظار',
        'chart_flow' => 'حجم التدفق المخزني (الوارد vs المنصرف)', 'chart_flow_in' => 'إجمالي الوارد (+)', 'chart_flow_out' => 'إجمالي المنصرف (-)',
        'chart_ops' => 'توزيع أذون العمليات المخزنية',
        'ops_transfers' => 'تحويلات', 'ops_deliveries' => 'تسليم', 'ops_returns' => 'مرتجعات', 'ops_adj' => 'تسويات',
        'tbl_moves_title' => 'أحدث الحركات المخزنية', 'view_all' => 'عرض الكل ➔',
        'col_branch' => 'الفرع', 'col_item' => 'الصنف', 'col_wh' => 'المستودع', 'col_type' => 'نوع الحركة', 'col_qty' => 'الكمية',
        'in' => 'وارد (+)', 'out' => 'منصرف (-)', 'empty_moves' => 'لا توجد حركات مسجلة للفرع المحدد مؤخراً.',
        'tbl_del_title' => 'أحدث أذون التسليم (مبيعات)',
        'col_del_no' => 'رقم الإذن', 'col_cust' => 'العميل', 'col_date' => 'التاريخ', 'col_status' => 'الحالة',
        'empty_del' => 'لا توجد أذون تسليم حديثة للفرع المحدد.',
        'status_draft' => 'مسودة', 'status_delivered' => 'تم التسليم'
    ],
    'en' => [
        'title' => 'Inventory Dashboard',
        'desc' => 'Monitor goods movements, warehouses, and real-time inventory value.',
        'export' => 'Export to Excel',
        'active_scope' => 'Active Branch Scope:',
        'card_ledger' => 'Stock Ledger', 'card_transfers' => 'Transfers', 'card_deliveries' => 'Delivery Notes',
        'card_returns' => 'Returns', 'card_adjustments' => 'Adjustments',
        'kpi_value' => 'Estimated Products Value', 'kpi_products' => 'Total Registered Products',
        'kpi_movements' => 'Total Stock Movements', 'kpi_pending' => 'Pending Transfers',
        'chart_flow' => 'Stock Flow Volume (IN vs OUT)', 'chart_flow_in' => 'Total Inflow (+)', 'chart_flow_out' => 'Total Outflow (-)',
        'chart_ops' => 'Inventory Operations Distribution',
        'ops_transfers' => 'Transfers', 'ops_deliveries' => 'Deliveries', 'ops_returns' => 'Returns', 'ops_adj' => 'Adjustments',
        'tbl_moves_title' => 'Recent Stock Movements', 'view_all' => 'View All ➔',
        'col_branch' => 'Branch', 'col_item' => 'Product', 'col_wh' => 'Warehouse', 'col_type' => 'Movement Type', 'col_qty' => 'Quantity',
        'in' => 'IN (+)', 'out' => 'OUT (-)', 'empty_moves' => 'No recent movements recorded for this branch.',
        'tbl_del_title' => 'Recent Delivery Notes',
        'col_del_no' => 'Note No.', 'col_cust' => 'Customer', 'col_date' => 'Date', 'col_status' => 'Status',
        'empty_del' => 'No recent delivery notes for this branch.',
        'status_draft' => 'Draft', 'status_delivered' => 'Delivered'
    ]
][$isRtl ? 'ar' : 'en'];
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
    
    .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--c-border); padding-bottom: 18px; }
    .dash-title-box { display: flex; align-items: center; gap: 16px; }
    .dash-icon { width: 52px; height: 52px; background: var(--c-brand-light); color: var(--c-brand); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 2rem; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15); }
    .dash-title { margin: 0; color: var(--c-slate-dark); font-size: 1.8rem; font-weight: 800; }
    
    .btn-excel { background: #16a34a; color: #ffffff !important; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2); }
    .btn-excel:hover { background: #15803d; transform: translateY(-2px); }

    .branch-scope-badge {
        display: inline-flex; align-items: center; gap: 8px;
        background: #f8fafc; border: 1px solid var(--c-border);
        padding: 6px 14px; border-radius: 20px; font-size: 0.8rem;
        font-weight: 800; color: var(--c-slate-dark); margin-bottom: 24px;
    }

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
                <h2 class="dash-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-slate-muted); font-weight:600;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <button onclick="exportToExcel()" class="btn-excel"><i class="ph-bold ph-file-xls"></i> <?= $t['export'] ?></button>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-brand);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-brand); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <div class="hub-grid">
        <a href="/ERP/inventory/stock/ledger" class="hub-card">
            <div class="hub-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-duotone ph-list-dashes"></i></div>
            <span><?= $t['card_ledger'] ?></span>
        </a>
        <a href="/ERP/inventory/stock/transfers" class="hub-card">
            <div class="hub-icon" style="background:#fff7ed; color:#ea580c;"><i class="ph-duotone ph-truck"></i></div>
            <span><?= $t['card_transfers'] ?></span>
        </a>
        <a href="/ERP/inventory/delivery-notes" class="hub-card">
            <div class="hub-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-package"></i></div>
            <span><?= $t['card_deliveries'] ?></span>
        </a>
        <a href="/ERP/inventory/returns" class="hub-card">
            <div class="hub-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-arrow-u-down-left"></i></div>
            <span><?= $t['card_returns'] ?></span>
        </a>
        <a href="/ERP/inventory/adjustments" class="hub-card">
            <div class="hub-icon" style="background:#f3e8ff; color:#9333ea;"><i class="ph-duotone ph-scales"></i></div>
            <span><?= $t['card_adjustments'] ?></span>
        </a>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card" style="--c-brand: #2563eb;">
            <div class="kpi-header">
                <span class="kpi-label"><?= $t['kpi_value'] ?></span>
                <i class="ph-duotone ph-currency-circle-dollar" style="font-size:1.8rem; color:#2563eb;"></i>
            </div>
            <div class="kpi-value"><?= number_format($convert($kpis['stock_value'] ?? 0), 2) ?> <span style="font-size:0.8rem; color:var(--c-slate-muted);"><?= htmlspecialchars($currency) ?></span></div>
        </div>

        <div class="kpi-card" style="--c-brand: #059669;">
            <div class="kpi-header">
                <span class="kpi-label"><?= $t['kpi_products'] ?></span>
                <i class="ph-duotone ph-barcode" style="font-size:1.8rem; color:#059669;"></i>
            </div>
            <div class="kpi-value"><?= number_format((int)$kpis['total_products']) ?></div>
        </div>

        <div class="kpi-card" style="--c-brand: #9333ea;">
            <div class="kpi-header">
                <span class="kpi-label"><?= $t['kpi_movements'] ?></span>
                <i class="ph-duotone ph-arrows-down-up" style="font-size:1.8rem; color:#9333ea;"></i>
            </div>
            <div class="kpi-value"><?= number_format((int)$kpis['total_movements']) ?></div>
        </div>

        <div class="kpi-card" style="--c-brand: #ea580c;">
            <div class="kpi-header">
                <span class="kpi-label"><?= $t['kpi_pending'] ?></span>
                <i class="ph-duotone ph-clock" style="font-size:1.8rem; color:#ea580c;"></i>
            </div>
            <div class="kpi-value" style="color:#ea580c;"><?= number_format((int)$kpis['pending_transfers']) ?></div>
        </div>
    </div>

    <div class="grid-split">
        <div class="panel">
            <div class="panel-title">
                <span><i class="ph-duotone ph-arrows-down-up" style="color:#2563eb;"></i> <?= $t['chart_flow'] ?></span>
            </div>
            <div style="height: 260px; position: relative;">
                <canvas id="flowChart"></canvas>
            </div>
        </div>

        <div class="panel">
            <div class="panel-title">
                <span><i class="ph-duotone ph-chart-pie-slice" style="color:#9333ea;"></i> <?= $t['chart_ops'] ?></span>
            </div>
            <div style="height: 260px; position: relative;">
                <canvas id="opsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid-split">
        <div class="panel">
            <div class="panel-title">
                <span><i class="ph-duotone ph-clock-counter-clockwise" style="color:#059669;"></i> <?= $t['tbl_moves_title'] ?></span>
                <a href="/ERP/inventory/stock/ledger" style="font-size:0.8rem; color:#2563eb; text-decoration:none;"><?= $t['view_all'] ?></a>
            </div>
            <table class="dash-table" id="exportTableMovements">
                <thead>
                    <tr>
                        <th><?= $t['col_branch'] ?></th>
                        <th><?= $t['col_item'] ?></th>
                        <th><?= $t['col_wh'] ?></th>
                        <th><?= $t['col_type'] ?></th>
                        <th style="text-align:center;"><?= $t['col_qty'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($recentMovements)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:20px; color:#94a3b8;"><?= $t['empty_moves'] ?></td></tr>
                    <?php else: foreach($recentMovements as $m): ?>
                        <tr>
                            <td><span style="background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:6px; font-size:0.75rem; font-weight:800; border:1px solid #e2e8f0;"><?= htmlspecialchars($m->branch_name ?? 'المركز الرئيسي') ?></span></td>
                            <td>
                                <div><?= htmlspecialchars($m->product_name ?? '---') ?></div>
                                <div style="font-family:monospace; color:#2563eb; font-size:0.75rem;"><?= htmlspecialchars($m->product_code ?? '---') ?></div>
                            </td>
                            <td><?= htmlspecialchars($m->warehouse_name ?? '---') ?></td>
                            <td>
                                <?php if (($m->movement_type ?? '') === 'in'): ?>
                                    <span class="badge" style="background:#f0fdf4; color:#16a34a;"><?= $t['in'] ?></span>
                                <?php else: ?>
                                    <span class="badge" style="background:#fef2f2; color:#dc2626;"><?= $t['out'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#0f172a;">
                                <?= number_format((float)($m->quantity ?? 0), 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <div class="panel-title">
                <span><i class="ph-duotone ph-truck" style="color:#ea580c;"></i> <?= $t['tbl_del_title'] ?></span>
                <a href="/ERP/inventory/delivery-notes" style="font-size:0.8rem; color:#2563eb; text-decoration:none;"><?= $t['view_all'] ?></a>
            </div>
            <table class="dash-table">
                <thead>
                    <tr>
                        <th><?= $t['col_branch'] ?></th>
                        <th><?= $t['col_del_no'] ?></th>
                        <th><?= $t['col_cust'] ?></th>
                        <th><?= $t['col_date'] ?></th>
                        <th style="text-align:center;"><?= $t['col_status'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($recentDeliveries)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:20px; color:#94a3b8;"><?= $t['empty_del'] ?></td></tr>
                    <?php else: foreach($recentDeliveries as $dn): ?>
                        <tr>
                            <td><span style="background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:6px; font-size:0.75rem; font-weight:800; border:1px solid #e2e8f0;"><?= htmlspecialchars($dn->branch_name ?? 'المركز الرئيسي') ?></span></td>
                            <td style="font-family:monospace; font-weight:800; color:#2563eb;"><?= htmlspecialchars($dn->delivery_number ?? '---') ?></td>
                            <td><?= htmlspecialchars($dn->customer_name ?? '---') ?></td>
                            <td><?= htmlspecialchars($dn->delivery_date ?? '---') ?></td>
                            <td style="text-align:center;">
                                <span class="badge" style="background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;">
                                    <?= ($dn->status ?? 'draft') === 'delivered' ? $t['status_delivered'] : $t['status_draft'] ?>
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

    var flowInText = "<?= $t['chart_flow_in'] ?>";
    var flowOutText = "<?= $t['chart_flow_out'] ?>";
    var opsTransfers = "<?= $t['ops_transfers'] ?>";
    var opsDel = "<?= $t['ops_deliveries'] ?>";
    var opsRet = "<?= $t['ops_returns'] ?>";
    var opsAdj = "<?= $t['ops_adj'] ?>";

    var ctxFlow = document.getElementById('flowChart').getContext('2d');
    new Chart(ctxFlow, {
        type: 'bar',
        data: {
            labels: [flowInText, flowOutText],
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

    var ctxOps = document.getElementById('opsChart').getContext('2d');
    new Chart(ctxOps, {
        type: 'doughnut',
        data: {
            labels: [opsTransfers, opsDel, opsRet, opsAdj],
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
    var table = document.getElementById("exportTableMovements");
    var rows = [];
    
    rows.push(["<?= $t['tbl_moves_title'] ?>"]);
    rows.push([]);
    rows.push(["<?= $t['col_branch'] ?>", "<?= $t['col_item'] ?>", "<?= $t['col_wh'] ?>", "<?= $t['col_type'] ?>", "<?= $t['col_qty'] ?>"]);

    for (var i = 0; i < table.rows.length; i++) {
        var row = table.rows[i];
        if(row.parentElement.tagName === 'THEAD') continue;
        
        var cols = [];
        for (var j = 0; j < row.cells.length; j++) {
            cols.push('"' + row.cells[j].innerText.replace(/\n/g, ' ') + '"');
        }
        if(cols.length > 0) rows.push(cols);
    }

    var csvContent = "\uFEFF" + rows.map(function(e) { return e.join(","); }).join("\n");
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", "Inventory_Dashboard_<?= date('Y-m-d') ?>.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>