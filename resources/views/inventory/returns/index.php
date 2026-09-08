<?php
// Path: resources/views/inventory/returns/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title' => 'مرتجعات المخزون (Stock Returns)', 'desc' => 'إدارة مرتجعات المبيعات من العملاء ومرتجعات المشتريات للموردين وتأثيرها على المخزن.',
        'add_btn' => 'إذن مرتجع جديد', 'col_num' => 'رقم المرتجع', 'col_type' => 'نوع المرتجع',
        'col_party' => 'الجهة (عميل / مورد)', 'col_wh' => 'المستودع', 'col_date' => 'التاريخ',
        'col_status' => 'الحالة', 'col_actions' => 'إجراءات', 'empty' => 'لا توجد مرتجعات مخزنية تطابق بحثك.',
        'search_ph' => 'ابحث برقم المرتجع، اسم الطرف، أو المستودع...', 'btn_search' => 'بحث', 'btn_clear' => 'إلغاء',
        'kpi_total' => 'إجمالي المرتجعات', 'kpi_sales' => 'مرتجعات مبيعات', 'kpi_purchase' => 'مرتجعات مشتريات',
        'active_scope' => 'الفرع النشط:', 'print_list' => 'طباعة القائمة',
        'confirm_delete' => 'هل أنت متأكد من حذف إذن المرتجع؟'
    ],
    'en' => [
        'title' => 'Stock Returns', 'desc' => 'Manage sales returns from customers and purchase returns to suppliers.',
        'add_btn' => 'New Return Note', 'col_num' => 'Return No.', 'col_type' => 'Return Type',
        'col_party' => 'Party (Customer / Supplier)', 'col_wh' => 'Warehouse', 'col_date' => 'Date',
        'col_status' => 'Status', 'col_actions' => 'Actions', 'empty' => 'No stock returns found.',
        'search_ph' => 'Search by return no, party name, or warehouse...', 'btn_search' => 'Search', 'btn_clear' => 'Clear',
        'kpi_total' => 'Total Returns', 'kpi_sales' => 'Sales Returns', 'kpi_purchase' => 'Purchase Returns',
        'active_scope' => 'Active Branch:', 'print_list' => 'Print List',
        'confirm_delete' => 'Are you sure you want to delete this return note?'
    ]
][$isRtl ? 'ar' : 'en'];

function getReturnStatusBadge($status, $isRtl) {
    $map = [
        'draft'    => ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $isRtl ? 'مسودة' : 'Draft'],
        'approved' => ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => $isRtl ? 'معتمد ومرحل' : 'Approved'],
        'cancelled' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => $isRtl ? 'ملغى' : 'Cancelled']
    ];
    $s = $map[$status] ?? $map['draft'];
    return "<span class='status-badge' style='background:{$s['bg']}; color:{$s['color']}; padding:4px 12px; border-radius:8px; font-weight:800; font-size:0.75rem; border:1px solid currentColor;'>{$s['label']}</span>";
}

function getReturnTypeBadge($type, $isRtl) {
    if ($type === 'sales_return') {
        $lbl = $isRtl ? 'مرتجع مبيعات' : 'Sales Return';
        return "<span class='type-badge' style='background:#eff6ff; color:#2563eb; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem;'><i class='ph-bold ph-arrow-u-down-left'></i> {$lbl}</span>";
    }
    $lbl = $isRtl ? 'مرتجع مشتريات' : 'Purchase Return';
    return "<span class='type-badge' style='background:#fff7ed; color:#ea580c; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem;'><i class='ph-bold ph-arrow-u-up-right'></i> {$lbl}</span>";
}
?>

<style>
    :root {
        --c-amber: #f59e0b; --c-amber-dark: #d97706; --c-amber-light: #fef3c7;
        --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #475569;
    }
    .mod-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .mod-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .mod-title-box { display: flex; align-items: center; gap: 16px; }
    .mod-icon { width: 48px; height: 48px; background: var(--c-amber-light); color: var(--c-amber); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
    .mod-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 800; }
    .mod-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: var(--c-text-muted); font-weight: 500; }
    
    .btn-primary { background: linear-gradient(135deg, var(--c-amber), var(--c-amber-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25); transition: 0.2s; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(245, 158, 11, 0.35); }
    .btn-print { background: #0f172a; color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #1e293b; }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 24px; }

    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: var(--c-amber-light); color: var(--c-amber); }
    .kpi-info h4 { margin: 0 0 4px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .kpi-info p { margin: 0; font-size: 1.4rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 12px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .search-input { flex: 1; border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 16px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; }
    .search-input:focus { border-color: var(--c-amber); background: #ffffff; outline: none; box-shadow: 0 0 0 3px var(--c-amber-light); }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; }
    .btn-clear { background: #f1f5f9; color: var(--c-text-muted); border: 1px solid var(--c-border); padding: 10px 20px; border-radius: 8px; font-weight: 800; text-decoration: none; display: flex; align-items: center; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .mod-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .mod-table th { padding: 16px 24px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .mod-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .mod-table tr:hover td { background: #f8fafc; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; transition: 0.2s; }
    .action-btn:hover { background: var(--c-amber-light); border-color: #fcd34d; color: var(--c-amber); }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; transition: 0.2s; }
    .page-link:hover { background: #f1f5f9; color: var(--c-text-dark); }
    .page-link.active { background: var(--c-amber); color: #ffffff; border-color: var(--c-amber); }

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body { background: #ffffff !important; color: #000000 !important; }
        .nt-sidebar, header, nav, footer { display: none !important; }
        .search-bar, .pagination, .btn-primary, .btn-print, .action-btn { display: none !important; }
        .mod-table th:last-child, .mod-table td:last-child { display: none !important; }
        .mod-wrapper { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; border-radius: 0 !important; }
        .mod-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .mod-table td { border: 1px solid #cbd5e1 !important; color: #000000 !important; }
        .kpi-card { border: 1px solid #000 !important; box-shadow: none !important; break-inside: avoid; }
        .status-badge, .type-badge { border: 1px solid #000 !important; background: transparent !important; color: #000 !important; }
    }
</style>

<div class="mod-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="mod-header">
        <div class="mod-title-box">
            <div class="mod-icon"><i class="ph-duotone ph-arrow-u-down-left"></i></div>
            <div>
                <h2 class="mod-title"><?= $t['title'] ?></h2>
                <p class="mod-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print_list'] ?></button>
            <a href="/ERP/inventory/returns/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-amber);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-amber); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="ph-duotone ph-files"></i></div>
            <div class="kpi-info"><h4><?= $t['kpi_total'] ?></h4><p><?= number_format($stats->total ?? 0) ?></p></div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #2563eb;">
            <div class="kpi-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-duotone ph-arrow-u-down-left"></i></div>
            <div class="kpi-info"><h4 style="color:#2563eb;"><?= $t['kpi_sales'] ?></h4><p><?= number_format($stats->sales_ret ?? 0) ?></p></div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #ea580c;">
            <div class="kpi-icon" style="background:#fff7ed; color:#ea580c;"><i class="ph-duotone ph-arrow-u-up-right"></i></div>
            <div class="kpi-info"><h4 style="color:#ea580c;"><?= $t['kpi_purchase'] ?></h4><p><?= number_format($stats->purchase_ret ?? 0) ?></p></div>
        </div>
    </div>

    <form action="/ERP/inventory/returns" method="GET" class="search-bar">
        <input type="text" name="search" class="search-input" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search)): ?>
            <a href="/ERP/inventory/returns" class="btn-clear"><i class="ph-bold ph-x"></i> <?= $t['btn_clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="mod-table">
            <thead>
                <tr>
                    <th style="width: 15%;"><?= $t['col_num'] ?></th>
                    <th style="width: 15%;"><?= $t['col_type'] ?></th>
                    <th style="width: 25%;"><?= $t['col_party'] ?></th>
                    <th style="width: 20%;"><?= $t['col_wh'] ?></th>
                    <th style="width: 10%;"><?= $t['col_date'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($returns)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($returns as $r): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 900; color: var(--c-amber); font-family: monospace; font-size: 1.05rem;"><i class="ph-bold ph-hash"></i> <?= htmlspecialchars((string)$r->return_number) ?></div>
                            <div style="color: var(--c-text-muted); font-size: 0.8rem; margin-top:2px;">أصناف: <strong><?= (int)($r->items_count ?? 0) ?></strong></div>
                        </td>
                        <td><?= getReturnTypeBadge($r->return_type ?? 'sales_return', $isRtl) ?></td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars((string)$r->party_name) ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #334155;"><i class="ph-fill ph-warehouse text-slate-400"></i> <?= htmlspecialchars((string)($r->warehouse_name ?? '---')) ?></div>
                        </td>
                        <td><div style="font-weight: 700; color: #334155;"><i class="ph-bold ph-calendar-blank"></i> <?= htmlspecialchars((string)$r->return_date) ?></div></td>
                        <td style="text-align: center;"><?= getReturnStatusBadge($r->status ?? 'draft', $isRtl) ?></td>
                        
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/inventory/returns/<?= $r->id ?>" class="action-btn" title="معاينة وطباعة"><i class="ph-bold ph-printer"></i></a>
                            
                            <?php if(trim((string)$r->status) !== 'approved'): ?>
                                <a href="/ERP/inventory/returns/<?= $r->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                <form action="/ERP/inventory/returns/<?= $r->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
                                    <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>