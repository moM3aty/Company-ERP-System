<?php
// Path: resources/views/sales/representatives/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isRtl();
$currency = current_currency();

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'مناديب المبيعات والعمولات', 
        'desc' => 'إدارة فريق المبيعات، عمولات التحصيل، والمستهدفات الشهرية (Targets).',
        'add_btn' => 'إضافة مندوب جديد', 
        'search' => 'البحث بالاسم، الكود، أو الهاتف...',
        'col_rep' => 'المندوب / الكود', 
        'col_contact' => 'التواصل', 
        'col_commission' => 'نسبة العمولة',
        'col_target' => 'المستهدف الشهري', 
        'col_status' => 'الحالة', 
        'col_actions' => 'إجراءات',
        'empty_title' => 'لا يوجد مناديب مبيعات', 
        'empty_desc' => 'ابدأ بإضافة أول مندوب مبيعات لتتبع أدائه وعمولاته.',
    ],
    'en' => [
        'title' => 'Sales Representatives', 
        'desc' => 'Manage sales team, commission rates, and monthly targets.',
        'add_btn' => 'Add Sales Rep', 
        'search' => 'Search by name, code, or phone...',
        'col_rep' => 'Sales Rep / Code', 
        'col_contact' => 'Contact', 
        'col_commission' => 'Commission Rate',
        'col_target' => 'Monthly Target', 
        'col_status' => 'Status', 
        'col_actions' => 'Actions',
        'empty_title' => 'No Sales Reps Found', 
        'empty_desc' => 'Start by adding your first sales representative.',
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    .crm-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .crm-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
    .crm-title-box { display: flex; align-items: center; gap: 14px; }
    .crm-title-icon { width: 48px; height: 48px; background: #e0f2fe; color: #0284c7; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(2, 132, 199, 0.1); }
    .crm-title { margin: 0; color: #0f172a; font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px; }
    .crm-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: #64748b; font-weight: 500; }
    
    .btn-primary { background: linear-gradient(135deg, #0284c7, #0369a1); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; text-decoration: none; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25); transition: all 0.2s ease; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35); }
    
    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; margin-top: 20px;}
    .crm-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .crm-table th { padding: 16px 24px; background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem; border-bottom: 2px solid #e2e8f0; }
    .crm-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; transition: background 0.2s; }
    .crm-table tr:hover td { background: #f8fafc; }
    
    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; font-size: 1.1rem; margin: 0 2px; }
    .action-btn:hover { background: #e0f2fe; border-color: #bae6fd; color: #0284c7; }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
    
    .empty-state { text-align: center; padding: 80px 20px; }
    .empty-icon { width: 90px; height: 90px; background: #e0f2fe; color: #0284c7; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 3rem; margin-bottom: 20px; }
</style>

<div class="crm-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="crm-header">
        <div class="crm-title-box">
            <div class="crm-title-icon"><i class="ph-duotone ph-user-gear"></i></div>
            <div>
                <h2 class="crm-title"><?= $t['title'] ?></h2>
                <p class="crm-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/sales/representatives/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-check-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-warning-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th style="width: 25%;"><?= $t['col_rep'] ?></th>
                        <th style="width: 25%;"><?= $t['col_contact'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_commission'] ?></th>
                        <th style="width: 15%; text-align: end;"><?= $t['col_target'] ?> (<?= htmlspecialchars($currency) ?>)</th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($representatives)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="ph-duotone ph-users-three"></i></div>
                                    <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 1.4rem; font-weight: 800;"><?= $t['empty_title'] ?></h3>
                                    <p style="color: #64748b; margin: 0; font-size: 1rem; font-weight: 500;"><?= $t['empty_desc'] ?></p>
                                    <br>
                                    <a href="/ERP/sales/representatives/create" class="btn-primary" style="margin-top: 16px;"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($representatives as $rep): 
                            $name = $isRtl ? ($rep->name_ar ?: $rep->name_en) : ($rep->name_en ?: $rep->name_ar);
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800; color: #0f172a; font-size: 0.95rem;"><?= htmlspecialchars($name) ?></div>
                                    <div style="color: #0284c7; font-family: monospace; font-size: 0.85rem; font-weight: 800; margin-top: 2px;"><?= htmlspecialchars($rep->code) ?></div>
                                </td>
                                <td>
                                    <?php if($rep->phone): ?><div style="font-weight: 600; color: #334155;"><i class="ph-fill ph-phone text-muted"></i> <?= htmlspecialchars($rep->phone) ?></div><?php endif; ?>
                                    <?php if($rep->email): ?><div style="font-size: 0.85rem; color: #64748b; margin-top:2px;"><i class="ph-fill ph-envelope text-muted"></i> <?= htmlspecialchars($rep->email) ?></div><?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <span style="background: #e0f2fe; color: #0284c7; padding: 4px 12px; border-radius: 99px; font-weight: 800; font-family: monospace; font-size: 0.85rem; border: 1px solid #bae6fd;">
                                        <?= number_format($rep->commission_rate, 1) ?> %
                                    </span>
                                </td>
                                <td style="text-align: end; font-weight: 900; font-family: monospace; color: #0f172a; font-size: 1rem;">
                                    <?= number_format($rep->target_amount ?? 0, 2) ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if($rep->is_active): ?>
                                        <span style="background:#ecfdf5; color:#059669; padding:4px 12px; border-radius:99px; font-size:0.7rem; font-weight:800; border:1px solid #a7f3d0;">ACTIVE</span>
                                    <?php else: ?>
                                        <span style="background:#f1f5f9; color:#475569; padding:4px 12px; border-radius:99px; font-size:0.7rem; font-weight:800; border:1px solid #cbd5e1;">INACTIVE</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <a href="/ERP/sales/representatives/<?= $rep->id ?>" class="action-btn" title="<?= $isRtl ? 'عرض التفاصيل' : 'View Details' ?>"><i class="ph-bold ph-eye"></i></a>
                                    <a href="/ERP/sales/representatives/<?= $rep->id ?>/edit" class="action-btn" title="<?= $isRtl ? 'تعديل' : 'Edit' ?>"><i class="ph-bold ph-pencil-simple"></i></a>
                                    <form action="/ERP/sales/representatives/<?= $rep->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $isRtl ? 'تأكيد حذف المندوب؟' : 'Confirm Delete?' ?>');">
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