<?php
// Path: resources/views/purchasing/returns/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'مرتجعات المشتريات (Debit Notes)', 'desc' => 'إدارة المرتجعات للموردين، معالجة البضائع المعيبة وإصدار إشعارات الخصم.',
        'add_btn' => 'مرتجع جديد', 'col_num' => 'رقم المرتجع / الفاتورة', 'col_sup' => 'المورد / سبب الإرجاع',
        'col_date' => 'تاريخ الإرجاع', 'col_val' => 'قيمة المرتجع', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات', 'empty' => 'لا توجد مرتجعات مشتريات تطابق بحثك.',
        'search_placeholder' => 'ابحث برقم المرتجع، رقم الفاتورة، المورد، أو سبب الإرجاع...', 'search_btn' => 'بحث', 'clear' => 'إلغاء',
        'stat_count' => 'عدد المرتجعات المبحوثة', 'stat_val' => 'إجمالي قيمة المرتجعات',
        'invoice_label' => 'الفاتورة:', 'reason_label' => 'السبب:', 'items_count' => 'البنود:',
        'status_draft' => 'مسودة', 'status_approved' => 'موافق عليه', 'status_completed' => 'تم الإرجاع والخصم', 'status_cancelled' => 'ملغى',
        'confirm_delete' => 'تأكيد الحذف؟'
    ],
    'en' => [
        'title' => 'Purchase Returns (Debit Notes)', 'desc' => 'Manage supplier returns, defective stock returns, and debit notes.',
        'add_btn' => 'New Return', 'col_num' => 'Return / Invoice No.', 'col_sup' => 'Supplier / Return Reason',
        'col_date' => 'Return Date', 'col_val' => 'Return Value', 'col_status' => 'Status', 'col_actions' => 'Actions', 'empty' => 'No purchase returns found.',
        'search_placeholder' => 'Search by return no, invoice no, supplier, or reason...', 'search_btn' => 'Search', 'clear' => 'Clear',
        'stat_count' => 'Searched Returns Count', 'stat_val' => 'Total Return Value',
        'invoice_label' => 'Invoice:', 'reason_label' => 'Reason:', 'items_count' => 'Items:',
        'status_draft' => 'Draft', 'status_approved' => 'Approved', 'status_completed' => 'Returned & Debited', 'status_cancelled' => 'Cancelled',
        'confirm_delete' => 'Confirm delete?'
    ]
][$isRtl ? 'ar' : 'en'];

function getReturnBadge($status, $t) {
    $map = [
        'draft' => ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $t['status_draft']],
        'approved' => ['bg' => '#eff6ff', 'color' => '#2563eb', 'label' => $t['status_approved']],
        'completed' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => $t['status_completed']],
        'cancelled' => ['bg' => '#f1f5f9', 'color' => '#94a3b8', 'label' => $t['status_cancelled']]
    ];
    $s = $map[$status] ?? $map['completed'];
    return "<span style='background:{$s['bg']}; color:{$s['color']}; padding:4px 12px; border-radius:8px; font-weight:800; font-size:0.75rem; border:1px solid currentColor;'>{$s['label']}</span>";
}

$totalReturnSum = 0;
if (!empty($returns)) {
    foreach ($returns as $ret) {
        $totalReturnSum += $ret->total_amount;
    }
}
?>

<style>
    :root {
        --c-red: #dc2626;
        --c-red-dark: #b91c1c;
        --c-red-light: #fef2f2;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
    }

    .mod-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .mod-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .mod-title-box { display: flex; align-items: center; gap: 16px; }
    .mod-icon { width: 48px; height: 48px; background: var(--c-red-light); color: var(--c-red); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(220, 38, 38, 0.15); }
    .mod-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 800; }
    .mod-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: var(--c-text-muted); font-weight: 500; }
    
    .btn-primary { background: linear-gradient(135deg, var(--c-red), var(--c-red-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25); transition: 0.2s; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(220, 38, 38, 0.35); }

    .kpi-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: var(--c-red-light); color: var(--c-red); }
    .kpi-info h4 { margin: 0 0 4px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .kpi-info p { margin: 0; font-size: 1.4rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 12px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .search-input { flex: 1; border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 16px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; }
    .search-input:focus { border-color: var(--c-red); background: #ffffff; outline: none; box-shadow: 0 0 0 3px var(--c-red-light); }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; }
    .btn-clear { background: #f1f5f9; color: var(--c-text-muted); border: 1px solid var(--c-border); padding: 10px 20px; border-radius: 8px; font-weight: 800; text-decoration: none; display: flex; align-items: center; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .mod-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .mod-table th { padding: 16px 24px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .mod-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .mod-table tr:hover td { background: #f8fafc; }
    
    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; transition: 0.2s; }
    .action-btn:hover { background: var(--c-red-light); border-color: #fca5a5; color: var(--c-red); }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; transition: 0.2s; }
    .page-link.active { background: var(--c-red); color: #ffffff; border-color: var(--c-red); }
</style>

<div class="mod-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="mod-header">
        <div class="mod-title-box">
            <div class="mod-icon"><i class="ph-duotone ph-arrow-u-up-left"></i></div>
            <div>
                <h2 class="mod-title"><?= $t['title'] ?></h2>
                <p class="mod-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/purchasing/returns/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="ph-duotone ph-files"></i></div>
            <div class="kpi-info"><h4><?= $t['stat_count'] ?></h4><p><?= count($returns ?? []) ?></p></div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid var(--c-red);">
            <div class="kpi-icon"><i class="ph-duotone ph-money"></i></div>
            <div class="kpi-info"><h4 style="color:var(--c-red);"><?= $t['stat_val'] ?></h4><p><?= number_format($totalReturnSum, 2) ?> <?= $currency ?></p></div>
        </div>
    </div>

    <form action="/ERP/purchasing/returns" method="GET" class="search-bar">
        <input type="text" name="search" class="search-input" placeholder="<?= $t['search_placeholder'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['search_btn'] ?></button>
        <?php if(!empty($search)): ?>
            <a href="/ERP/purchasing/returns" class="btn-clear"><i class="ph-bold ph-x"></i> <?= $t['clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th style="width: 20%;"><?= $t['col_num'] ?></th>
                        <th style="width: 25%;"><?= $t['col_sup'] ?></th>
                        <th style="width: 15%;"><?= $t['col_date'] ?></th>
                        <th style="width: 15%; text-align: end;"><?= $t['col_val'] ?></th>
                        <th style="width: 12%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 13%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($returns)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach ($returns as $ret): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 900; color: var(--c-red); font-family: monospace; font-size: 1rem;"><i class="ph-bold ph-hash"></i> <?= htmlspecialchars($ret->return_number) ?></div>
                                <?php if(!empty($ret->invoice_number)): ?>
                                    <div style="color: var(--c-text-muted); font-size: 0.8rem; margin-top:2px;"><?= $t['invoice_label'] ?> <span style="font-family:monospace; font-weight:bold; color:#0f172a;"><?= htmlspecialchars($ret->invoice_number) ?></span></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 800; color: var(--c-text-dark);"><i class="ph-fill ph-buildings text-slate-400"></i> <?= htmlspecialchars($ret->supplier_name ?? '---') ?></div>
                                <?php if(!empty($ret->reason)): ?>
                                    <div style="color: var(--c-text-muted); font-size: 0.8rem; margin-top:2px;"><?= $t['reason_label'] ?> <span><?= htmlspecialchars($ret->reason) ?></span></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #334155;"><i class="ph-bold ph-calendar-blank"></i> <?= $ret->return_date ?></div>
                                <div style="color: var(--c-text-muted); font-size: 0.8rem; margin-top:2px;"><?= $t['items_count'] ?> <?= $ret->items_count ?></div>
                            </td>
                            <td style="text-align: end; font-family: monospace; font-weight: 900; color: var(--c-red); font-size: 1.05rem;">
                                <?= number_format($ret->total_amount, 2) ?> <?= $currency ?>
                            </td>
                            <td style="text-align: center;">
                                <?= getReturnBadge($ret->status, $t) ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="/ERP/purchasing/returns/<?= $ret->id ?>" class="action-btn" title="معاينة وطباعة"><i class="ph-bold ph-printer"></i></a>
                                <a href="/ERP/purchasing/returns/<?= $ret->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                <form action="/ERP/purchasing/returns/<?= $ret->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
                                    <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
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