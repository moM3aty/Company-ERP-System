<?php
// Path: resources/views/purchasing/invoices/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'فواتير المشتريات (Bills)', 'desc' => 'إدارة فواتير الموردين، الاستحقاقات المالية ومتابعة المدفوعات.',
        'add_btn' => 'فاتورة جديدة', 'total_invoices' => 'إجمالي الفواتير', 'invoices_value' => 'قيمة الفواتير', 'unpaid_dues' => 'مستحقات غير مدفوعة',
        'search_placeholder' => 'ابحث برقم الفاتورة، فاتورة المورد، أو المورد...', 'search_btn' => 'بحث', 'clear_btn' => 'إلغاء',
        'col_num' => 'رقم الفاتورة', 'col_sup' => 'المورد', 'col_dates' => 'الإصدار / الاستحقاق', 
        'col_val' => 'المبلغ / المتبقي', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات', 
        'empty' => 'لا توجد فواتير مشتريات تطابق بحثك.',
        'po_label' => 'أمر شراء:', 'sup_inv_label' => 'فاتورة المورد:',
        'issue_date' => 'إصدار:', 'due_date' => 'استحقاق:', 'balance' => 'متبقي:',
        'status_draft' => 'مسودة', 'status_unpaid' => 'غير مدفوعة', 'status_partially_paid' => 'مدفوعة جزئياً', 'status_paid' => 'مدفوعة بالكامل', 'status_cancelled' => 'ملغاة'
    ],
    'en' => [
        'title' => 'Purchase Invoices (Bills)', 'desc' => 'Manage supplier bills, accounts payable, and track due payments.',
        'add_btn' => 'New Invoice', 'total_invoices' => 'Total Invoices', 'invoices_value' => 'Invoices Value', 'unpaid_dues' => 'Unpaid Dues',
        'search_placeholder' => 'Search by invoice no, vendor bill, or supplier...', 'search_btn' => 'Search', 'clear_btn' => 'Clear',
        'col_num' => 'Invoice No', 'col_sup' => 'Supplier', 'col_dates' => 'Issue / Due', 
        'col_val' => 'Total / Balance', 'col_status' => 'Status', 'col_actions' => 'Actions', 
        'empty' => 'No purchase invoices found.',
        'po_label' => 'PO:', 'sup_inv_label' => 'Vendor Bill:',
        'issue_date' => 'Issued:', 'due_date' => 'Due:', 'balance' => 'Balance:',
        'status_draft' => 'Draft', 'status_unpaid' => 'Unpaid', 'status_partially_paid' => 'Partially Paid', 'status_paid' => 'Fully Paid', 'status_cancelled' => 'Cancelled'
    ]
][$isRtl ? 'ar' : 'en'];

function getInvoiceBadge($status, $t) {
    $map = [
        'draft' => ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $t['status_draft']],
        'unpaid' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => $t['status_unpaid']],
        'partially_paid' => ['bg' => '#fef3c7', 'color' => '#d97706', 'label' => $t['status_partially_paid']],
        'paid' => ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => $t['status_paid']],
        'cancelled' => ['bg' => '#f1f5f9', 'color' => '#94a3b8', 'label' => $t['status_cancelled']]
    ];
    $s = $map[$status] ?? $map['unpaid'];
    return "<span style='background:{$s['bg']}; color:{$s['color']}; padding:4px 12px; border-radius:8px; font-weight:800; font-size:0.75rem; border:1px solid currentColor;'>{$s['label']}</span>";
}

$totalUnpaid = 0; $totalAmountSum = 0;
if (!empty($invoices)) {
    foreach ($invoices as $inv) {
        $totalAmountSum += convert_amount($inv->total_amount);
        if ($inv->status !== 'paid' && $inv->status !== 'cancelled') {
            $totalUnpaid += convert_amount($inv->total_amount - $inv->paid_amount);
        }
    }
}
?>

<style>
    :root { --c-rose: #e11d48; --c-rose-dark: #be123c; --c-rose-light: #ffe4e6; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #475569; }
    .mod-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .mod-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .mod-title-box { display: flex; align-items: center; gap: 16px; }
    .mod-icon { width: 48px; height: 48px; background: var(--c-rose-light); color: var(--c-rose); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(225, 29, 72, 0.15); }
    .mod-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 800; }
    .mod-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: var(--c-text-muted); font-weight: 500; }
    .btn-primary { background: linear-gradient(135deg, var(--c-rose), var(--c-rose-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25); transition: 0.2s; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(225, 29, 72, 0.35); }
    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: var(--c-rose-light); color: var(--c-rose); }
    .kpi-info h4 { margin: 0 0 4px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .kpi-info p { margin: 0; font-size: 1.4rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }
    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 12px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px; }
    .search-input { flex: 1; border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 16px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; }
    .btn-clear { background: #f1f5f9; color: var(--c-text-muted); border: 1px solid var(--c-border); padding: 10px 20px; border-radius: 8px; font-weight: 800; text-decoration: none; display: flex; align-items: center; }
    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .mod-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .mod-table th { padding: 16px 24px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .mod-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; transition: 0.2s; }
    .action-btn:hover { background: var(--c-rose-light); border-color: #fecdd3; color: var(--c-rose); }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
    .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; transition: 0.2s; }
    .page-link.active { background: var(--c-rose); color: #ffffff; border-color: var(--c-rose); }
</style>

<div class="mod-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="mod-header">
        <div class="mod-title-box">
            <div class="mod-icon"><i class="ph-duotone ph-receipt"></i></div>
            <div>
                <h2 class="mod-title"><?= $t['title'] ?></h2>
                <p class="mod-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/purchasing/invoices/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="ph-duotone ph-files"></i></div>
            <div class="kpi-info"><h4><?= $t['total_invoices'] ?></h4><p><?= count($invoices ?? []) ?></p></div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid var(--c-rose);">
            <div class="kpi-icon"><i class="ph-duotone ph-coins"></i></div>
            <div class="kpi-info"><h4 style="color:var(--c-rose);"><?= $t['invoices_value'] ?></h4><p><?= number_format($totalAmountSum, 2) ?> <span style="font-size: 0.8rem; color:#64748b;"><?= $currency ?></span></p></div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #dc2626;">
            <div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-warning"></i></div>
            <div class="kpi-info"><h4 style="color:#dc2626;"><?= $t['unpaid_dues'] ?></h4><p><?= number_format($totalUnpaid, 2) ?> <span style="font-size: 0.8rem; color:#64748b;"><?= $currency ?></span></p></div>
        </div>
    </div>

    <form action="/ERP/purchasing/invoices" method="GET" class="search-bar">
        <input type="text" name="search" class="search-input" placeholder="<?= $t['search_placeholder'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['search_btn'] ?></button>
        <?php if(!empty($search)): ?>
            <a href="/ERP/purchasing/invoices" class="btn-clear"><i class="ph-bold ph-x"></i> <?= $t['clear_btn'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th style="width: 20%;"><?= $t['col_num'] ?></th>
                        <th style="width: 25%;"><?= $t['col_sup'] ?></th>
                        <th style="width: 20%;"><?= $t['col_dates'] ?></th>
                        <th style="width: 15%; text-align: end;"><?= $t['col_val'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach ($invoices as $inv): 
                        $due = convert_amount($inv->total_amount - $inv->paid_amount);
                        $total = convert_amount($inv->total_amount);
                    ?>
                        <tr>
                            <td>
                                <div style="font-weight: 900; color: var(--c-rose); font-family: monospace; font-size: 1rem;"><i class="ph-bold ph-hash"></i> <?= htmlspecialchars($inv->invoice_number) ?></div>
                                <?php if(!empty($inv->po_number)): ?>
                                    <div style="color: var(--c-text-muted); font-size: 0.8rem; margin-top:2px;"><?= $t['po_label'] ?> <span style="font-family:monospace; font-weight:bold; color:#0f172a;"><?= htmlspecialchars($inv->po_number) ?></span></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 800; color: var(--c-text-dark);"><i class="ph-fill ph-buildings text-slate-400"></i> <?= htmlspecialchars($inv->supplier_name ?? '---') ?></div>
                                <?php if(!empty($inv->supplier_invoice_number)): ?>
                                    <div style="color: var(--c-text-muted); font-size: 0.8rem; margin-top:2px;"><?= $t['sup_inv_label'] ?> <span style="font-family:monospace;"><?= htmlspecialchars($inv->supplier_invoice_number) ?></span></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #475569; font-size: 0.85rem;"><i class="ph-bold ph-calendar-plus"></i> <?= $t['issue_date'] ?> <?= $inv->invoice_date ?></div>
                                <div style="font-weight: 600; color: #dc2626; font-size: 0.85rem; margin-top:2px;"><i class="ph-bold ph-calendar-check"></i> <?= $t['due_date'] ?> <?= $inv->due_date ?></div>
                            </td>
                            <td style="text-align: end;">
                                <div style="font-family: monospace; font-weight: 900; color: var(--c-text-dark); font-size: 1.05rem;"><?= number_format($total, 2) ?></div>
                                <?php if($due > 0 && $inv->status !== 'paid'): ?>
                                    <div style="font-family: monospace; font-size: 0.8rem; color: #dc2626; font-weight: bold; margin-top:2px;"><?= $t['balance'] ?> <?= number_format($due, 2) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?= getInvoiceBadge($inv->status, $t) ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="/ERP/purchasing/invoices/<?= $inv->id ?>" class="action-btn" title="معاينة"><i class="ph-bold ph-printer"></i></a>
                                <a href="/ERP/purchasing/invoices/<?= $inv->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                <form action="/ERP/purchasing/invoices/<?= $inv->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $isRtl ? "تأكيد الحذف؟" : "Confirm delete?" ?>');">
                                    <button type="submit" class="action-btn delete"><i class="ph-bold ph-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

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