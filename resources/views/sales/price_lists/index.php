<?php
// Path: resources/views/sales/price_lists/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isRtl();
$currency = current_currency();

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'قوائم الأسعار والتسعير', 
        'desc' => 'إدارة شرائح الأسعار، خصومات الكميات والسياسات البيعية.',
        'add_btn' => 'إنشاء قائمة أسعار', 
        'search' => 'البحث بالاسم أو الكود...',
        'col_code' => 'الكود', 
        'col_name' => 'اسم القائمة', 
        'col_currency' => 'العملة الحالية',
        'col_items' => 'عدد الأصناف', 
        'col_status' => 'الحالة', 
        'col_actions' => 'إجراءات',
        'empty_title' => 'لا توجد قوائم أسعار', 
        'empty_desc' => 'ابدأ بإنشاء أول قائمة أسعار مخصصة للعملاء.',
    ],
    'en' => [
        'title' => 'Price Lists', 
        'desc' => 'Manage customer pricing tiers, volume discounts, and policies.',
        'add_btn' => 'Create Price List', 
        'search' => 'Search by name or code...',
        'col_code' => 'Code', 
        'col_name' => 'Price List Name', 
        'col_currency' => 'Active Currency',
        'col_items' => 'Items Count', 
        'col_status' => 'Status', 
        'col_actions' => 'Actions',
        'empty_title' => 'No Price Lists Found', 
        'empty_desc' => 'Start by creating your first pricing tier.',
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    .crm-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .crm-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
    .crm-title-box { display: flex; align-items: center; gap: 14px; }
    .crm-title-icon { width: 48px; height: 48px; background: #e0e7ff; color: #4338ca; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(67, 56, 202, 0.1); }
    .crm-title { margin: 0; color: #0f172a; font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px; }
    .crm-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: #64748b; font-weight: 500; }
    
    .btn-primary { background: linear-gradient(135deg, #4338ca, #3730a3); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; text-decoration: none; box-shadow: 0 4px 12px rgba(67, 56, 202, 0.25); transition: all 0.2s ease; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(67, 56, 202, 0.35); }
    
    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; margin-top: 20px;}
    .crm-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .crm-table th { padding: 16px 24px; background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem; border-bottom: 2px solid #e2e8f0; }
    .crm-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; transition: background 0.2s; }
    .crm-table tr:hover td { background: #f8fafc; }
    
    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; font-size: 1.1rem; margin: 0 2px; }
    .action-btn:hover { background: #e0e7ff; border-color: #c7d2fe; color: #4338ca; }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
    
    .empty-state { text-align: center; padding: 80px 20px; }
    .empty-icon { width: 90px; height: 90px; background: #e0e7ff; color: #4338ca; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 3rem; margin-bottom: 20px; }
</style>

<div class="crm-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="crm-header">
        <div class="crm-title-box">
            <div class="crm-title-icon"><i class="ph-duotone ph-tag"></i></div>
            <div>
                <h2 class="crm-title"><?= $t['title'] ?></h2>
                <p class="crm-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/sales/price-lists/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-check-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-warning-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th style="width: 15%;"><?= $t['col_code'] ?></th>
                        <th style="width: 35%;"><?= $t['col_name'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_currency'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_items'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($priceLists)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="ph-duotone ph-tag"></i></div>
                                    <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 1.4rem; font-weight: 800;"><?= $t['empty_title'] ?></h3>
                                    <p style="color: #64748b; margin: 0; font-size: 1rem; font-weight: 500;"><?= $t['empty_desc'] ?></p>
                                    <br>
                                    <a href="/ERP/sales/price-lists/create" class="btn-primary" style="margin-top: 16px;"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($priceLists as $pl): 
                            $name = $isRtl ? ($pl->name_ar ?: $pl->name_en) : ($pl->name_en ?: $pl->name_ar);
                        ?>
                            <tr>
                                <td style="font-weight: 800; font-family: monospace; color: #4338ca; font-size: 0.95rem;"><?= htmlspecialchars($pl->code) ?></td>
                                <td>
                                    <div style="font-weight: 800; color: #0f172a; font-size: 0.95rem;"><?= htmlspecialchars($name) ?></div>
                                    <?php if($pl->notes): ?><div style="font-size: 0.8rem; color: #64748b; margin-top:2px;"><?= htmlspecialchars($pl->notes) ?></div><?php endif; ?>
                                </td>
                                <td style="text-align: center; font-weight: 800; font-family: monospace; color: #4338ca;">
                                    <?= htmlspecialchars($currency) ?>
                                </td>
                                <td style="text-align: center;">
                                    <span style="background: #e0e7ff; color: #4338ca; padding: 3px 10px; border-radius: 99px; font-weight: 800; font-size: 0.8rem;"><?= number_format($pl->items_count) ?> <?= $isRtl ? 'أصناف' : 'Items' ?></span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if($pl->is_active): ?>
                                        <span style="background:#ecfdf5; color:#059669; padding:4px 12px; border-radius:99px; font-size:0.7rem; font-weight:800; border:1px solid #a7f3d0;">ACTIVE</span>
                                    <?php else: ?>
                                        <span style="background:#f1f5f9; color:#475569; padding:4px 12px; border-radius:99px; font-size:0.7rem; font-weight:800; border:1px solid #cbd5e1;">INACTIVE</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <a href="/ERP/sales/price-lists/<?= $pl->id ?>" class="action-btn" title="<?= $isRtl ? 'عرض التفاصيل' : 'View' ?>"><i class="ph-bold ph-eye"></i></a>
                                    <a href="/ERP/sales/price-lists/<?= $pl->id ?>/edit" class="action-btn" title="<?= $isRtl ? 'تعديل' : 'Edit' ?>"><i class="ph-bold ph-pencil-simple"></i></a>
                                    <form action="/ERP/sales/price-lists/<?= $pl->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $isRtl ? 'تأكيد حذف قائمة الأسعار؟' : 'Confirm Delete?' ?>');">
                                        <button type="submit" class="action-btn delete" title="<?= $isRtl ? 'حذف' : 'Delete' ?>"><i class="ph-bold ph-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>