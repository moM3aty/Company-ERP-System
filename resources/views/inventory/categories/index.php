<?php
// Path: resources/views/inventory/categories/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'فئات ومجموعات الأصناف', 'desc' => 'تبويب وتصنيف المنتجات لسهولة الإدارة، الفلترة وإصدار التقارير.',
        'add_btn' => 'إضافة فئة جديدة', 'col_name' => 'اسم الفئة (عربي / إنجليزي)', 'col_desc' => 'الوصف التفصيلي',
        'col_count' => 'الأصناف المرتبطة', 'col_actions' => 'إجراءات', 'empty' => 'لا توجد فئات أصناف تطابق بحثك.'
    ],
    'en' => [
        'title' => 'Product Categories', 'desc' => 'Group and classify items for easier management, filtering, and reporting.',
        'add_btn' => 'New Category', 'col_name' => 'Category Name (AR / EN)', 'col_desc' => 'Description',
        'col_count' => 'Linked Products', 'col_actions' => 'Actions', 'empty' => 'No categories found.'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-amber: #f59e0b;
        --c-amber-dark: #d97706;
        --c-amber-light: #fef3c7;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
    }

    .mod-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .mod-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .mod-title-box { display: flex; align-items: center; gap: 16px; }
    .mod-icon { width: 48px; height: 48px; background: var(--c-amber-light); color: var(--c-amber); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.15); }
    .mod-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 800; }
    .mod-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: var(--c-text-muted); font-weight: 500; }
    
    .btn-primary { background: linear-gradient(135deg, var(--c-amber), var(--c-amber-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25); transition: 0.2s; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(245, 158, 11, 0.35); }

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
</style>

<div class="mod-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="mod-header">
        <div class="mod-title-box">
            <div class="mod-icon"><i class="ph-duotone ph-folders"></i></div>
            <div>
                <h2 class="mod-title"><?= $t['title'] ?></h2>
                <p class="mod-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/inventory/categories/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <!-- Search Form -->
    <form action="/ERP/inventory/categories" method="GET" class="search-bar">
        <input type="text" name="search" class="search-input" placeholder="ابحث باسم الفئة بالعربية، الإنجليزية، أو الوصف..." value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> بحث</button>
        <?php if(!empty($search)): ?>
            <a href="/ERP/inventory/categories" class="btn-clear"><i class="ph-bold ph-x"></i> إلغاء</a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="mod-table">
            <thead>
                <tr>
                    <th style="width: 30%;"><?= $t['col_name'] ?></th>
                    <th style="width: 40%;"><?= $t['col_desc'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_count'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($categories as $c): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark); font-size: 1rem;"><i class="ph-fill ph-folder text-amber-500"></i> <?= htmlspecialchars($c->name_ar) ?></div>
                            <?php if(!empty($c->name_en)): ?>
                                <div style="font-weight: 700; color: var(--c-text-muted); font-family: sans-serif; font-size: 0.85rem; margin-top: 4px;" dir="ltr"><?= htmlspecialchars($c->name_en) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #475569; font-size: 0.85rem; line-height: 1.5;"><?= htmlspecialchars($c->description ?? '---') ?></div>
                        </td>
                        <td style="text-align: center;">
                            <span style="background: #f1f5f9; color: var(--c-amber-dark); padding: 4px 14px; border-radius: 8px; font-weight: 900; font-family: monospace; border: 1px solid #e2e8f0; font-size: 0.95rem;">
                                <?= $c->products_count ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/inventory/categories/<?= $c->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/inventory/categories/<?= $c->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('تأكيد حذف هذه الفئة؟ لا يمكن حذفها إذا كانت مرتبطة بأصناف.');">
                                <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
                            </form>
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
