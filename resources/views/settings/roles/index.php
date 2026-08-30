<?php
// Path: resources/views/settings/roles/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'مصفوفة الأدوار والصلاحيات', 'desc' => 'إدارة المسميات الوظيفية وتحديد ما يمكن لكل دور الوصول إليه في النظام.',
        'add_btn' => 'إنشاء دور جديد', 'col_name' => 'اسم الدور الوظيفي', 'col_users' => 'المستخدمين المرتبطين',
        'col_type' => 'نوع الدور', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات',
        'empty' => 'لا توجد أدوار وصلاحيات مسجلة حالياً.', 'users_unit' => 'مستخدم'
    ],
    'en' => [
        'title' => 'Roles & Access Matrix', 'desc' => 'Manage job roles and define system-wide permissions.',
        'add_btn' => 'Create New Role', 'col_name' => 'Role Name', 'col_users' => 'Assigned Users',
        'col_type' => 'Role Type', 'col_status' => 'Status', 'col_actions' => 'Actions',
        'empty' => 'No roles found in system.', 'users_unit' => 'Users'
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
    
    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
    .crm-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .crm-table th { padding: 16px 24px; background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem; border-bottom: 2px solid #e2e8f0; }
    .crm-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; transition: background 0.2s; }
    .crm-table tr:hover td { background: #f8fafc; }
    
    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: #4338ca; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; font-size: 1.1rem; margin: 0 2px; }
    .action-btn:hover { background: #e0e7ff; border-color: #c7d2fe; color: #3730a3; }
    .action-btn.delete { color: #dc2626; }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
    
    .badge { padding: 4px 12px; border-radius: 999px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; display: inline-flex; align-items: center; justify-content: center; }
</style>

<div class="crm-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="crm-header">
        <div class="crm-title-box">
            <div class="crm-title-icon"><i class="ph-duotone ph-shield-check"></i></div>
            <div>
                <h2 class="crm-title"><?= $t['title'] ?></h2>
                <p class="crm-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/settings/roles/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-check-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-warning-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th style="width: 40%;"><?= $t['col_name'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_users'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_type'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($roles)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 60px; color: #94a3b8;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach ($roles as $r): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 800; color: #0f172a; font-size: 1.05rem;"><?= htmlspecialchars($r->name) ?></div>
                                <div style="font-size: 0.8rem; color: #64748b; margin-top:2px;"><?= htmlspecialchars($r->description ?? '---') ?></div>
                            </td>
                            <td style="text-align: center;">
                                <span style="background: #e0e7ff; color: #4338ca; padding: 4px 12px; border-radius: 99px; font-weight: 800; font-size: 0.8rem;">
                                    <?= number_format($r->users_count) ?> <?= $t['users_unit'] ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php if($r->is_system): ?>
                                    <span style="color: #dc2626; font-weight: 800; font-size: 0.75rem; border: 1px solid #fca5a5; background: #fef2f2; padding: 3px 8px; border-radius: 6px;"><i class="ph-fill ph-lock-key"></i> SYSTEM</span>
                                <?php else: ?>
                                    <span style="color: #64748b; font-weight: 700; font-size: 0.75rem; background: #f1f5f9; padding: 3px 8px; border-radius: 6px;">CUSTOM</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if($r->is_active): ?>
                                    <span class="badge" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;">ACTIVE</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;">INACTIVE</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="/ERP/settings/roles/<?= $r->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                <?php if(!$r->is_system): ?>
                                <form action="/ERP/settings/roles/<?= $r->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $isRtl ? 'تأكيد حذف هذا الدور بالكامل؟' : 'Confirm deleting this role?' ?>');">
                                    <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>