<?php
// Path: resources/views/sales/customers/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isRtl();
$currency = current_currency();

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'سجل العملاء', 'desc' => 'إدارة بيانات العملاء، الحدود الائتمانية، ومعلومات التواصل.',
        'add_btn' => 'إضافة عميل جديد', 'search' => 'البحث بالاسم، الكود، أو الهاتف...',
        'col_name' => 'اسم العميل / الكود', 'col_contact' => 'التواصل', 'col_financial' => 'الرقم الضريبي / الائتمان',
        'col_status' => 'الحالة', 'col_actions' => 'إجراءات',
        'empty_title' => 'لا يوجد عملاء', 'empty_desc' => 'لم يتم إضافة أي عملاء حتى الآن.'
    ],
    'en' => [
        'title' => 'Customers', 'desc' => 'Manage customer records, credit limits, and contact info.',
        'add_btn' => 'Add Customer', 'search' => 'Search by name, code, or phone...',
        'col_name' => 'Customer / Code', 'col_contact' => 'Contact', 'col_financial' => 'Tax No. / Credit Limit',
        'col_status' => 'Status', 'col_actions' => 'Actions',
        'empty_title' => 'No Customers', 'empty_desc' => 'No customers added yet.'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    .crm-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .crm-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
    .crm-title-box { display: flex; align-items: center; gap: 14px; }
    .crm-title-icon { width: 48px; height: 48px; background: #eff6ff; color: #2563eb; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.1); }
    .crm-title { margin: 0; color: #0f172a; font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px; }
    
    .btn-primary { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; text-decoration: none; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); transition: 0.2s; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35); }
    
    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
    .crm-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .crm-table th { padding: 16px 24px; background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; letter-spacing: 0.5px; }
    .crm-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .crm-table tr:hover td { background: #f8fafc; }
    
    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; transition: 0.2s; }
    .action-btn:hover { background: #eff6ff; border-color: #bfdbfe; color: #2563eb; }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
</style>

<div class="crm-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="crm-header">
        <div class="crm-title-box">
            <div class="crm-title-icon"><i class="ph-duotone ph-users-three"></i></div>
            <div>
                <h2 class="crm-title"><?= $t['title'] ?></h2>
                <p style="margin: 4px 0 0 0; font-size: 0.9rem; color: #64748b; font-weight: 500;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/sales/customers/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th style="width: 30%;"><?= $t['col_name'] ?></th>
                        <th style="width: 25%;"><?= $t['col_contact'] ?></th>
                        <th style="width: 25%;"><?= $t['col_financial'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 60px; color: #94a3b8;"><h3 style="margin:0 0 8px 0; color:#0f172a;"><?= $t['empty_title'] ?></h3><p style="margin:0;"><?= $t['empty_desc'] ?></p></td></tr>
                    <?php else: foreach ($customers as $c): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 800; color: #0f172a; font-size: 1rem;"><?= htmlspecialchars($isRtl ? ($c->name_ar ?: $c->name_en) : ($c->name_en ?: $c->name_ar)) ?></div>
                                <div style="color: #2563eb; font-family: monospace; font-size: 0.85rem; font-weight: 800; margin-top: 4px;"><?= htmlspecialchars($c->code) ?></div>
                            </td>
                            <td>
                                <?php if($c->phone): ?><div style="font-weight: 600; color: #334155;"><i class="ph-fill ph-phone text-muted"></i> <?= htmlspecialchars($c->phone) ?></div><?php endif; ?>
                                <?php if($c->email): ?><div style="font-size: 0.85rem; color: #64748b; margin-top:2px;"><i class="ph-fill ph-envelope text-muted"></i> <?= htmlspecialchars($c->email) ?></div><?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #475569;">VAT: <?= htmlspecialchars($c->tax_number ?? '---') ?></div>
                                <div style="font-size: 0.85rem; color: #10b981; font-weight: 800; margin-top: 4px; font-family: monospace;">Limit: <?= number_format($c->credit_limit_converted ?? 0, 2) ?> <?= htmlspecialchars($currency) ?></div>
                            </td>
                            <td style="text-align: center;">
                                <?php if($c->is_active): ?>
                                    <span style="background:#ecfdf5; color:#059669; padding:4px 12px; border-radius:99px; font-size:0.7rem; font-weight:800; border:1px solid #a7f3d0;">ACTIVE</span>
                                <?php else: ?>
                                    <span style="background:#f1f5f9; color:#475569; padding:4px 12px; border-radius:99px; font-size:0.7rem; font-weight:800; border:1px solid #cbd5e1;">INACTIVE</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="/ERP/sales/customers/<?= $c->id ?>" class="action-btn" title="<?= $isRtl ? 'عرض' : 'View' ?>"><i class="ph-bold ph-eye"></i></a>
                                <a href="/ERP/sales/customers/<?= $c->id ?>/edit" class="action-btn" title="<?= $isRtl ? 'تعديل' : 'Edit' ?>"><i class="ph-bold ph-pencil-simple"></i></a>
                                <form action="/ERP/sales/customers/<?= $c->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $isRtl ? 'تأكيد الحذف؟' : 'Confirm delete?' ?>');">
                                    <button type="submit" class="action-btn delete" title="<?= $isRtl ? 'حذف' : 'Delete' ?>"><i class="ph-bold ph-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>