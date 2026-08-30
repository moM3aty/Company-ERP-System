<?php
// Path: resources/views/purchasing/price_lists/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);

$t = [
    'ar' => [
        'title' => 'قوائم أسعار الموردين (Catalogs)', 'desc' => 'إدارة تسعير الموردين، الكتالوجات، والخصومات وفترات صلاحيتها.',
        'add_btn' => 'إضافة قائمة أسعار', 'col_num' => 'رقم القائمة / العنوان', 'col_sup' => 'المورد',
        'col_dates' => 'الصلاحية (من - إلى)', 'col_items' => 'الأصناف',
        'col_status' => 'الحالة', 'col_actions' => 'إجراءات', 'empty' => 'لا توجد قوائم أسعار تطابق بحثك.'
    ],
    'en' => [
        'title' => 'Supplier Price Lists', 'desc' => 'Manage supplier catalogs, pricing, discounts and validities.',
        'add_btn' => 'New Price List', 'col_num' => 'List No. / Title', 'col_sup' => 'Supplier',
        'col_dates' => 'Validity (From - To)', 'col_items' => 'Items',
        'col_status' => 'Status', 'col_actions' => 'Actions', 'empty' => 'No price lists found matching your search.'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-primary: #4f46e5;
        --c-primary-dark: #3730a3;
        --c-primary-light: #e0e7ff;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .mod-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .mod-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .mod-title-box { display: flex; align-items: center; gap: 16px; }
    .mod-icon { width: 48px; height: 48px; background: var(--c-primary-light); color: var(--c-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.15); }
    .mod-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 800; }
    .mod-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: var(--c-text-muted); font-weight: 500; }
    
    .btn-primary { background: linear-gradient(135deg, var(--c-primary), var(--c-primary-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25); transition: 0.2s; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35); }

    /* Search Bar */
    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 12px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px; }
    .search-input { flex: 1; border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 16px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; }
    .search-input:focus { border-color: var(--c-primary); background: #ffffff; outline: none; box-shadow: 0 0 0 3px var(--c-primary-light); }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; }
    .btn-clear { background: #f1f5f9; color: var(--c-text-muted); border: 1px solid var(--c-border); padding: 10px 20px; border-radius: 8px; font-weight: 800; text-decoration: none; display: flex; align-items: center; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; }
    .mod-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .mod-table th { padding: 16px 24px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
    .mod-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .mod-table tr:hover td { background: #f8fafc; }
    
    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; transition: 0.2s; }
    .action-btn:hover { background: var(--c-primary-light); border-color: #a5b4fc; color: var(--c-primary); }
    
    .st-active { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 4px 12px; border-radius: 8px; font-weight: 800; font-size: 0.75rem; }
    .st-expired { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 4px 12px; border-radius: 8px; font-weight: 800; font-size: 0.75rem; }
    .st-draft { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 4px 12px; border-radius: 8px; font-weight: 800; font-size: 0.75rem; }

    /* Pagination */
    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; transition: 0.2s; }
    .page-link:hover { background: #f1f5f9; color: var(--c-text-dark); border-color: #cbd5e1; }
    .page-link.active { background: var(--c-primary); color: #ffffff; border-color: var(--c-primary); }
</style>

<div class="mod-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="mod-header">
        <div class="mod-title-box">
            <div class="mod-icon"><i class="ph-duotone ph-tag"></i></div>
            <div>
                <h2 class="mod-title"><?= $t['title'] ?></h2>
                <p class="mod-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/purchasing/price-lists/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>

    <!-- Search Form -->
    <form action="/ERP/purchasing/price-lists" method="GET" class="search-bar">
        <input type="text" name="search" class="search-input" placeholder="ابحث برقم القائمة، العنوان، أو اسم المورد..." value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> بحث</button>
        <?php if(!empty($search)): ?>
            <a href="/ERP/purchasing/price-lists" class="btn-clear"><i class="ph-bold ph-x"></i> إلغاء</a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="mod-table">
            <thead>
                <tr>
                    <th style="width: 25%;"><?= $t['col_num'] ?></th>
                    <th style="width: 20%;"><?= $t['col_sup'] ?></th>
                    <th style="width: 20%;"><?= $t['col_dates'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_items'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lists)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($lists as $l): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($l->title) ?></div>
                            <div style="color: var(--c-primary); font-family: monospace; font-weight: 800; font-size: 0.85rem; margin-top:2px;"><?= htmlspecialchars($l->list_number) ?></div>
                        </td>
                        <td style="font-weight: 700; color: #475569;"><i class="ph-fill ph-buildings text-slate-400"></i> <?= htmlspecialchars($l->supplier_name ?? '---') ?></td>
                        <td>
                            <div style="font-weight: 600; color: #059669; font-size: 0.8rem;"><i class="ph-bold ph-play-circle"></i> <?= $l->valid_from ?></div>
                            <div style="font-weight: 600; color: #dc2626; font-size: 0.8rem; margin-top:2px;"><i class="ph-bold ph-stop-circle"></i> <?= $l->valid_to ?></div>
                        </td>
                        <td style="text-align: center;">
                            <span style="background: var(--c-primary-light); color: var(--c-primary); padding: 4px 12px; border-radius: 8px; font-weight: 800; border: 1px solid #c7d2fe;"><?= $l->items_count ?></span>
                        </td>
                        <td style="text-align: center;">
                            <span class="st-<?= strtolower($l->status) ?>"><?= strtoupper($l->status) ?></span>
                        </td>
                        <td style="text-align: center;">
                            <a href="/ERP/purchasing/price-lists/<?= $l->id ?>" class="action-btn" title="عرض"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/purchasing/price-lists/<?= $l->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/purchasing/price-lists/<?= $l->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('تأكيد الحذف؟');"><button type="submit" class="action-btn delete"><i class="ph-bold ph-trash"></i></button></form>
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