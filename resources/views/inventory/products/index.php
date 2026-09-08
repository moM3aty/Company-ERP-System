<?php
// Path: resources/views/inventory/products/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title' => 'دليل الأصناف والمنتجات', 'desc' => 'إدارة قاعدة بيانات المخزون، الباركود، التسعير وحالة الأصناف.',
        'add_btn' => 'إضافة صنف جديد', 'col_item' => 'الكود والصنف', 'col_cat' => 'الفئة / الوحدة',
        'col_price' => 'سعر الشراء / البيع', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات', 'empty' => 'لا توجد أصناف تطابق بحثك.',
        'search_ph' => 'ابحث بكود الصنف، الباركود، الاسم...', 'btn_search' => 'بحث', 'btn_clear' => 'إلغاء',
        'kpi_total' => 'إجمالي الأصناف بالدليل', 'kpi_active' => 'أصناف نشطة', 'kpi_inactive' => 'أصناف متوقفة',
        'active_scope' => 'الفرع النشط:', 'buy' => 'شراء:', 'sell' => 'بيع:', 'unit' => 'الوحدة:', 'no_cat' => 'بدون فئة',
        'status_active' => 'نشط', 'status_inactive' => 'موقوف', 'confirm_delete' => 'هل أنت متأكد من حذف هذا الصنف؟'
    ],
    'en' => [
        'title' => 'Products Directory', 'desc' => 'Manage inventory database, barcodes, pricing, and product status.',
        'add_btn' => 'New Product', 'col_item' => 'Item Code & Name', 'col_cat' => 'Category / UOM',
        'col_price' => 'Cost / Selling Price', 'col_status' => 'Status', 'col_actions' => 'Actions', 'empty' => 'No products found.',
        'search_ph' => 'Search by code, barcode, name...', 'btn_search' => 'Search', 'btn_clear' => 'Clear',
        'kpi_total' => 'Total Products', 'kpi_active' => 'Active Products', 'kpi_inactive' => 'Inactive Products',
        'active_scope' => 'Active Branch:', 'buy' => 'Buy:', 'sell' => 'Sell:', 'unit' => 'Unit:', 'no_cat' => 'No Category',
        'status_active' => 'Active', 'status_inactive' => 'Inactive', 'confirm_delete' => 'Are you sure you want to delete this product?'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { --c-amber: #f59e0b; --c-amber-dark: #d97706; --c-amber-light: #fef3c7; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #475569; }
    .mod-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .mod-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .mod-title-box { display: flex; align-items: center; gap: 16px; }
    .mod-icon { width: 48px; height: 48px; background: var(--c-amber-light); color: var(--c-amber); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.15); }
    .mod-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 800; }
    .mod-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: var(--c-text-muted); font-weight: 500; }
    
    .btn-primary { background: linear-gradient(135deg, var(--c-amber), var(--c-amber-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25); transition: 0.2s; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(245, 158, 11, 0.35); }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 24px; }

    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: var(--c-amber-light); color: var(--c-amber); }
    .kpi-info h4 { margin: 0 0 4px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .kpi-info p { margin: 0; font-size: 1.4rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 12px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .search-input { flex: 1; border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 16px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; }
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

    .status-active { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 4px 12px; border-radius: 8px; font-weight: 800; font-size: 0.75rem; }
    .status-inactive { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 4px 12px; border-radius: 8px; font-weight: 800; font-size: 0.75rem; }
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
        <a href="/ERP/inventory/products/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
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
            <div class="kpi-icon"><i class="ph-duotone ph-stack"></i></div>
            <div class="kpi-info"><h4><?= $t['kpi_total'] ?></h4><p><?= number_format($stats->total ?? 0) ?></p></div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #059669;">
            <div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div>
            <div class="kpi-info"><h4 style="color:#059669;"><?= $t['kpi_active'] ?></h4><p><?= number_format($stats->active ?? 0) ?></p></div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #dc2626;">
            <div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-minus-circle"></i></div>
            <div class="kpi-info"><h4 style="color:#dc2626;"><?= $t['kpi_inactive'] ?></h4><p><?= number_format($stats->inactive ?? 0) ?></p></div>
        </div>
    </div>

    <form action="/ERP/inventory/products" method="GET" class="search-bar">
        <input type="text" name="search" class="search-input" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search)): ?>
            <a href="/ERP/inventory/products" class="btn-clear"><i class="ph-bold ph-x"></i> <?= $t['btn_clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="mod-table">
            <thead>
                <tr>
                    <th style="width: 30%;"><?= $t['col_item'] ?></th>
                    <th style="width: 20%;"><?= $t['col_cat'] ?></th>
                    <th style="width: 20%; text-align: end;"><?= $t['col_price'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($isRtl ? $p->name_ar : ($p->name_en ?: $p->name_ar)) ?></div>
                            <div style="font-weight: 900; color: var(--c-amber); font-family: monospace; font-size: 0.85rem; margin-top: 4px;">
                                <i class="ph-bold ph-barcode"></i> <?= htmlspecialchars($p->item_code) ?> 
                                <?= !empty($p->barcode) ? " | {$p->barcode}" : '' ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #334155;"><i class="ph-fill ph-folder text-slate-400"></i> <?= htmlspecialchars($p->category_name ?? $t['no_cat']) ?></div>
                            <div style="color: var(--c-text-muted); font-size: 0.8rem; margin-top:2px;"><?= $t['unit'] ?> <strong><?= htmlspecialchars($p->unit) ?></strong></div>
                        </td>
                        <td style="text-align: end;">
                            <div style="font-family: monospace; font-weight: 600; color: #64748b; font-size: 0.85rem;"><?= $t['buy'] ?> <?= number_format($convert($p->purchase_price), 2) ?> <?= $currency ?></div>
                            <div style="font-family: monospace; font-weight: 900; color: #0f172a; font-size: 1.05rem; margin-top:2px;"><?= $t['sell'] ?> <?= number_format($convert($p->selling_price), 2) ?> <?= $currency ?></div>
                        </td>
                        <td style="text-align: center;">
                            <?php if($p->is_active): ?> <span class="status-active"><?= $t['status_active'] ?></span>
                            <?php else: ?> <span class="status-inactive"><?= $t['status_inactive'] ?></span> <?php endif; ?>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/inventory/products/<?= $p->id ?>" class="action-btn" title="عرض التفاصيل"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/inventory/products/<?= $p->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/inventory/products/<?= $p->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
                                <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>