<?php
// Path: resources/views/sales/returns/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isRtl();
$currency = current_currency();

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'مرتجعات المبيعات (الإشعارات الدائنة)', 'desc' => 'إدارة مرتجعات البضائع الصادرة وإصدار الإشعارات الدائنة للعملاء.',
        'add_btn' => 'إصدار إشعار مرتجع', 'search' => 'البحث برقم المرتجع، العميل أو الفاتورة...',
        'col_ret' => 'رقم المرتجع', 'col_customer' => 'العميل', 'col_date' => 'تاريخ الإشعار',
        'col_inv' => 'الفاتورة المربوطة', 'col_amount' => 'إجمالي المرتجع', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات',
        'empty_title' => 'لا توجد مرتجعات مبيعات', 'empty_desc' => 'لم تقم بتسجيل أي إشعارات مرتجع مبيعات حتى الآن.',
    ],
    'en' => [
        'title' => 'Sales Returns (Credit Notes)', 'desc' => 'Manage returned goods and issue customer credit notes.',
        'add_btn' => 'Create Credit Note', 'search' => 'Search by return no, customer or invoice...',
        'col_ret' => 'Credit Note No.', 'col_customer' => 'Customer', 'col_date' => 'Date',
        'col_inv' => 'Invoice Ref', 'col_amount' => 'Total Amount', 'col_status' => 'Status', 'col_actions' => 'Actions',
        'empty_title' => 'No Sales Returns Found', 'empty_desc' => 'You haven\'t issued any credit notes yet.',
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    .crm-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .crm-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
    .crm-title-box { display: flex; align-items: center; gap: 14px; }
    .crm-title-icon { width: 48px; height: 48px; background: #fef2f2; color: #dc2626; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(220, 38, 38, 0.1); }
    .crm-title { margin: 0; color: #0f172a; font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px; }
    .crm-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: #64748b; font-weight: 500; }
    
    .btn-primary { background: linear-gradient(135deg, #dc2626, #b91c1c); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; text-decoration: none; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25); transition: all 0.2s ease; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(220, 38, 38, 0.35); }
    
    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; margin-top: 20px;}
    .crm-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .crm-table th { padding: 16px 24px; background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem; border-bottom: 2px solid #e2e8f0; }
    .crm-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; transition: background 0.2s; }
    .crm-table tr:hover td { background: #f8fafc; }
    
    .badge { padding: 4px 12px; border-radius: 999px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; display: inline-flex; align-items: center; justify-content: center;}
    .bg-draft { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
    .bg-approved { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    .bg-completed { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .bg-cancelled { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; font-size: 1.1rem; margin: 0 2px; }
    .action-btn:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
    
    .empty-state { text-align: center; padding: 80px 20px; }
    .empty-icon { width: 90px; height: 90px; background: #fef2f2; color: #dc2626; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 3rem; margin-bottom: 20px; }
</style>

<div class="crm-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="crm-header">
        <div class="crm-title-box">
            <div class="crm-title-icon"><i class="ph-duotone ph-arrow-u-up-left"></i></div>
            <div>
                <h2 class="crm-title"><?= $t['title'] ?></h2>
                <p class="crm-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/sales/returns/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-check-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-warning-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th style="width: 15%;"><?= $t['col_ret'] ?></th>
                        <th style="width: 30%;"><?= $t['col_customer'] ?></th>
                        <th style="width: 15%;"><?= $t['col_date'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_inv'] ?></th>
                        <th style="width: 15%; text-align: end;"><?= $t['col_amount'] ?> (<?= htmlspecialchars($currency) ?>)</th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($returns)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="ph-duotone ph-arrow-u-up-left"></i></div>
                                    <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 1.4rem; font-weight: 800;"><?= $t['empty_title'] ?></h3>
                                    <p style="color: #64748b; margin: 0; font-size: 1rem; font-weight: 500;"><?= $t['empty_desc'] ?></p>
                                    <br>
                                    <a href="/ERP/sales/returns/create" class="btn-primary" style="margin-top: 16px;"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($returns as $r): ?>
                            <tr>
                                <td style="font-weight: 800; font-family: monospace; color: #dc2626; font-size: 0.95rem;"><?= htmlspecialchars($r->return_number) ?></td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a; font-size: 0.95rem;"><i class="ph-fill ph-user-circle text-muted"></i> <?= htmlspecialchars($r->customer_name ?? ($isRtl ? 'عميل عام' : 'General Customer')) ?></div>
                                </td>
                                <td><i class="ph-fill ph-calendar-blank text-muted"></i> <?= htmlspecialchars($r->return_date) ?></td>
                                <td style="text-align: center; font-family: monospace; font-weight: 700; color: #2563eb;">
                                    <?= htmlspecialchars($r->invoice_number ?? '---') ?>
                                </td>
                                <td style="text-align: end; font-weight: 900; font-family: monospace; color: #dc2626; font-size: 1.05rem;">
                                    <?= number_format(convert_amount($r->total_amount ?? 0), 2) ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge bg-<?= strtolower($r->status) ?>"><?= strtoupper(htmlspecialchars($r->status)) ?></span>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <a href="/ERP/sales/returns/<?= $r->id ?>" class="action-btn" title="<?= $isRtl ? 'عرض الإشعار والطباعة' : 'View' ?>"><i class="ph-bold ph-eye"></i></a>
                                    <a href="/ERP/sales/returns/<?= $r->id ?>/edit" class="action-btn" title="<?= $isRtl ? 'تعديل' : 'Edit' ?>"><i class="ph-bold ph-pencil-simple"></i></a>
                                    <form action="/ERP/sales/returns/<?= $r->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $isRtl ? 'تأكيد حذف مرتجع المبيعات؟' : 'Confirm delete?' ?>');">
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