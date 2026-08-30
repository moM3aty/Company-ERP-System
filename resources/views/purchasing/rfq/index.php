<?php
// Path: resources/views/purchasing/rfq/index.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'عروض الأسعار (RFQ)', 'desc' => 'إدارة طلبات تسعير الموردين ومقارنة العروض وتواريخ استلامها.',
        'add_btn' => 'طلب تسعير جديد', 'col_num' => 'رقم الطلب / العنوان', 'col_dates' => 'تاريخ الطلب / الموعد النهائي',
        'col_counts' => 'الموردين / الأصناف', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات', 'empty' => 'لا توجد طلبات تسعير تطابق بحثك.'
    ],
    'en' => [
        'title' => 'Requests for Quotation (RFQ)', 'desc' => 'Manage supplier quote requests, compare bids and deadlines.',
        'add_btn' => 'New RFQ', 'col_num' => 'RFQ No. / Title', 'col_dates' => 'Request Date / Deadline',
        'col_counts' => 'Suppliers / Items', 'col_status' => 'Status', 'col_actions' => 'Actions', 'empty' => 'No RFQs found matching your search.'
    ]
][$isRtl ? 'ar' : 'en'];

function getRfqBadge($status) {
    $map = [
        'draft' => ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => 'مسودة'],
        'published' => ['bg' => '#ffedd5', 'color' => '#ea580c', 'label' => 'بانتظار العروض (Published)'],
        'closed' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => 'مغلق (Closed)'],
        'awarded' => ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => 'تم الترسية (Awarded)']
    ];
    $s = $map[$status] ?? $map['draft'];
    return "<span style='background:{$s['bg']}; color:{$s['color']}; padding:4px 12px; border-radius:8px; font-weight:800; font-size:0.75rem; border:1px solid currentColor;'>{$s['label']}</span>";
}

$activeCount = 0; $closedCount = 0;
if (!empty($rfqs)) {
    foreach ($rfqs as $r) {
        if ($r->status === 'published') $activeCount++;
        if ($r->status === 'closed' || $r->status === 'awarded') $closedCount++;
    }
}
?>

<style>
    :root {
        --c-orange: #ea580c;       /* Orange / Amber */
        --c-orange-dark: #c2410c;
        --c-orange-light: #ffedd5;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
    }

    .mod-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .mod-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .mod-title-box { display: flex; align-items: center; gap: 16px; }
    .mod-icon { width: 48px; height: 48px; background: var(--c-orange-light); color: var(--c-orange); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(234, 88, 12, 0.15); }
    .mod-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 800; }
    .mod-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: var(--c-text-muted); font-weight: 500; }
    
    .btn-primary { background: linear-gradient(135deg, var(--c-orange), var(--c-orange-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25); transition: 0.2s; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(234, 88, 12, 0.35); }

    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: var(--c-orange-light); color: var(--c-orange); }
    .kpi-info h4 { margin: 0 0 4px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .kpi-info p { margin: 0; font-size: 1.4rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    /* Search Bar */
    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 12px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px; }
    .search-input { flex: 1; border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 16px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; }
    .search-input:focus { border-color: var(--c-orange); background: #ffffff; outline: none; box-shadow: 0 0 0 3px var(--c-orange-light); }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; }
    .btn-clear { background: #f1f5f9; color: var(--c-text-muted); border: 1px solid var(--c-border); padding: 10px 20px; border-radius: 8px; font-weight: 800; text-decoration: none; display: flex; align-items: center; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .mod-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .mod-table th { padding: 16px 24px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .mod-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .mod-table tr:hover td { background: #f8fafc; }
    
    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; transition: 0.2s; }
    .action-btn:hover { background: var(--c-orange-light); border-color: #fdba74; color: var(--c-orange); }

    /* Pagination */
    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; transition: 0.2s; }
    .page-link:hover { background: #f1f5f9; color: var(--c-text-dark); border-color: #cbd5e1; }
    .page-link.active { background: var(--c-orange); color: #ffffff; border-color: var(--c-orange); }
</style>

<div class="mod-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="mod-header">
        <div class="mod-title-box">
            <div class="mod-icon"><i class="ph-duotone ph-megaphone"></i></div>
            <div>
                <h2 class="mod-title"><?= $t['title'] ?></h2>
                <p class="mod-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/purchasing/rfq/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="ph-duotone ph-files"></i></div>
            <div class="kpi-info"><h4>إجمالي الطلبات</h4><p><?= count($rfqs ?? []) ?></p></div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid var(--c-orange);">
            <div class="kpi-icon" style="background:var(--c-orange-light); color:var(--c-orange);"><i class="ph-duotone ph-clock"></i></div>
            <div class="kpi-info"><h4 style="color:var(--c-orange);">بانتظار العروض</h4><p><?= $activeCount ?></p></div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #dc2626;">
            <div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-check-circle"></i></div>
            <div class="kpi-info"><h4 style="color:#dc2626;">طلبات منتهية (Closed)</h4><p><?= $closedCount ?></p></div>
        </div>
    </div>

    <!-- Search Form -->
    <form action="/ERP/purchasing/rfq" method="GET" class="search-bar">
        <input type="text" name="search" class="search-input" placeholder="ابحث برقم الطلب، أو الموضوع..." value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> بحث</button>
        <?php if(!empty($search)): ?>
            <a href="/ERP/purchasing/rfq" class="btn-clear"><i class="ph-bold ph-x"></i> إلغاء</a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="mod-table">
            <thead>
                <tr>
                    <th style="width: 25%;"><?= $t['col_num'] ?></th>
                    <th style="width: 20%;"><?= $t['col_dates'] ?></th>
                    <th style="width: 20%; text-align: center;"><?= $t['col_counts'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 20%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rfqs)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($rfqs as $r): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($r->title) ?></div>
                            <div style="font-weight: 900; color: var(--c-orange); font-family: monospace; font-size: 0.85rem; margin-top: 2px;"><i class="ph-bold ph-hash"></i> <?= htmlspecialchars($r->rfq_number) ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #059669; font-size: 0.85rem;"><i class="ph-bold ph-calendar-blank"></i> بدء: <?= $r->request_date ?></div>
                            <div style="font-weight: 600; color: #dc2626; font-size: 0.85rem; margin-top:2px;"><i class="ph-bold ph-warning-circle"></i> إغلاق: <?= $r->deadline_date ?></div>
                        </td>
                        <td style="text-align: center;">
                            <span style="display:inline-block; background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:6px; font-weight:800; font-size:0.8rem; margin-bottom:4px;"><i class="ph-fill ph-buildings"></i> <?= $r->suppliers_count ?> موردين</span><br>
                            <span style="display:inline-block; background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:6px; font-weight:800; font-size:0.8rem;"><i class="ph-fill ph-package"></i> <?= $r->items_count ?> أصناف</span>
                        </td>
                        <td style="text-align: center;">
                            <?= getRfqBadge($r->status) ?>
                        </td>
                        <td style="text-align: center;">
                            <a href="/ERP/purchasing/rfq/<?= $r->id ?>" class="action-btn" title="معاينة الوثيقة"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/purchasing/rfq/<?= $r->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/purchasing/rfq/<?= $r->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('تأكيد الحذف؟');"><button type="submit" class="action-btn delete"><i class="ph-bold ph-trash"></i></button></form>
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>