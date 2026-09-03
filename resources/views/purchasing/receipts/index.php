<?php
// Path: resources/views/purchasing/receipts/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'أذونات استلام البضائع (GRN)', 'desc' => 'إدارة محاضر استلام وفحص البضائع الموردة وتدقيق الكميات المقبولة.',
        'add_btn' => 'إذن استلام جديد', 'col_num' => 'رقم الإذن / أمر الشراء', 'col_sup' => 'المورد / بوليصة الشحن',
        'col_date' => 'تاريخ الاستلام', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات', 'empty' => 'لا توجد أذونات استلام تطابق بحثك.',
        'search_placeholder' => 'ابحث برقم الإذن، رقم البوليصة، المورد أو أمر الشراء...', 'search_btn' => 'بحث', 'clear' => 'إلغاء',
        'po_label' => 'أمر شراء:', 'dn_label' => 'إذن التسليم:', 'received_by' => 'مستلم:',
        'status_draft' => 'مسودة', 'status_inspected' => 'قيد الفحص', 'status_accepted' => 'مقبول واستلم', 'status_rejected' => 'مرفوض',
        'confirm_delete' => 'تأكيد الحذف؟'
    ],
    'en' => [
        'title' => 'Goods Receipts (GRN)', 'desc' => 'Manage warehouse inward gate passes and inspected items.',
        'add_btn' => 'New Goods Receipt', 'col_num' => 'GRN / PO Number', 'col_sup' => 'Supplier / Delivery Note',
        'col_date' => 'Receipt Date', 'col_status' => 'Status', 'col_actions' => 'Actions', 'empty' => 'No goods receipts found.',
        'search_placeholder' => 'Search by GRN no, delivery note, supplier or PO...', 'search_btn' => 'Search', 'clear' => 'Clear',
        'po_label' => 'PO:', 'dn_label' => 'Delivery Note:', 'received_by' => 'Received by:',
        'status_draft' => 'Draft', 'status_inspected' => 'Inspected', 'status_accepted' => 'Accepted', 'status_rejected' => 'Rejected',
        'confirm_delete' => 'Confirm delete?'
    ]
][$isRtl ? 'ar' : 'en'];

function getGrnBadge($status, $t) {
    $map = [
        'draft' => ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $t['status_draft']],
        'inspected' => ['bg' => '#fef3c7', 'color' => '#d97706', 'label' => $t['status_inspected']],
        'accepted' => ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => $t['status_accepted']],
        'rejected' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => $t['status_rejected']]
    ];
    $s = $map[$status] ?? $map['accepted'];
    return "<span style='background:{$s['bg']}; color:{$s['color']}; padding:4px 12px; border-radius:8px; font-weight:800; font-size:0.75rem; border:1px solid currentColor;'>{$s['label']}</span>";
}
?>

<style>
    :root {
        --c-emerald: #059669;
        --c-emerald-dark: #047857;
        --c-emerald-light: #d1fae5;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
    }

    .mod-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .mod-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .mod-title-box { display: flex; align-items: center; gap: 16px; }
    .mod-icon { width: 48px; height: 48px; background: var(--c-emerald-light); color: var(--c-emerald); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.15); }
    .mod-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 800; }
    .mod-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: var(--c-text-muted); font-weight: 500; }
    
    .btn-primary { background: linear-gradient(135deg, var(--c-emerald), var(--c-emerald-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25); transition: 0.2s; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(5, 150, 105, 0.35); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 12px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .search-input { flex: 1; border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 16px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; }
    .search-input:focus { border-color: var(--c-emerald); background: #ffffff; outline: none; box-shadow: 0 0 0 3px var(--c-emerald-light); }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; }
    .btn-clear { background: #f1f5f9; color: var(--c-text-muted); border: 1px solid var(--c-border); padding: 10px 20px; border-radius: 8px; font-weight: 800; text-decoration: none; display: flex; align-items: center; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .mod-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .mod-table th { padding: 16px 24px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .mod-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .mod-table tr:hover td { background: #f8fafc; }
    
    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; transition: 0.2s; }
    .action-btn:hover { background: var(--c-emerald-light); border-color: #a7f3d0; color: var(--c-emerald); }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; transition: 0.2s; }
    .page-link.active { background: var(--c-emerald); color: #ffffff; border-color: var(--c-emerald); }
</style>

<div class="mod-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="mod-header">
        <div class="mod-title-box">
            <div class="mod-icon"><i class="ph-duotone ph-package"></i></div>
            <div>
                <h2 class="mod-title"><?= $t['title'] ?></h2>
                <p class="mod-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/purchasing/receipts/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <form action="/ERP/purchasing/receipts" method="GET" class="search-bar">
        <input type="text" name="search" class="search-input" placeholder="<?= $t['search_placeholder'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['search_btn'] ?></button>
        <?php if(!empty($search)): ?>
            <a href="/ERP/purchasing/receipts" class="btn-clear"><i class="ph-bold ph-x"></i> <?= $t['clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th style="width: 25%;"><?= $t['col_num'] ?></th>
                        <th style="width: 30%;"><?= $t['col_sup'] ?></th>
                        <th style="width: 15%;"><?= $t['col_date'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($receipts)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach ($receipts as $r): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 900; color: var(--c-emerald); font-family: monospace; font-size: 1rem;"><i class="ph-bold ph-hash"></i> <?= htmlspecialchars($r->receipt_number) ?></div>
                                <?php if(!empty($r->po_number)): ?>
                                    <div style="color: var(--c-text-muted); font-size: 0.8rem; margin-top:2px;"><?= $t['po_label'] ?> <span style="font-family:monospace; font-weight:bold; color:#0f172a;"><?= htmlspecialchars($r->po_number) ?></span></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 800; color: var(--c-text-dark);"><i class="ph-fill ph-buildings text-slate-400"></i> <?= htmlspecialchars($r->supplier_name ?? '---') ?></div>
                                <?php if(!empty($r->delivery_note_number)): ?>
                                    <div style="color: var(--c-text-muted); font-size: 0.8rem; margin-top:2px;"><?= $t['dn_label'] ?> <span style="font-family:monospace;"><?= htmlspecialchars($r->delivery_note_number) ?></span></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #334155;"><i class="ph-bold ph-calendar-blank"></i> <?= $r->receipt_date ?></div>
                                <?php if(!empty($r->received_by)): ?>
                                    <div style="color: var(--c-text-muted); font-size: 0.8rem; margin-top:2px;"><i class="ph-fill ph-user"></i> <?= htmlspecialchars($r->received_by) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?= getGrnBadge($r->status, $t) ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="/ERP/purchasing/receipts/<?= $r->id ?>" class="action-btn" title="معاينة وطباعة"><i class="ph-bold ph-printer"></i></a>
                                <a href="/ERP/purchasing/receipts/<?= $r->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                <form action="/ERP/purchasing/receipts/<?= $r->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
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