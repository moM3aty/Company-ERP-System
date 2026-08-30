<?php
// Path: resources/views/sales/receipts/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isRtl();
$currency = current_currency();

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'سندات قبض المبيعات', 'desc' => 'إدارة وتحصيل المبالغ النقدية والتحويلات المسجلة للعملاء.',
        'add_btn' => 'إصدار سند قبض', 'search' => 'البحث برقم السند، العميل أو الفاتورة...',
        'col_rct' => 'رقم السند', 'col_customer' => 'العميل', 'col_date' => 'التاريخ',
        'col_method' => 'طريقة الدفع', 'col_inv' => 'الفاتورة المربوطة', 'col_amount' => 'المبلغ المقبوض', 'col_actions' => 'إجراءات',
        'empty_title' => 'لا توجد سندات قبض', 'empty_desc' => 'لم تقم بإصدار أي سندات قبض حتى الآن.',
    ],
    'en' => [
        'title' => 'Sales Receipts', 'desc' => 'Manage cash receipts and bank collections from customers.',
        'add_btn' => 'Issue Sales Receipt', 'search' => 'Search by receipt no, customer or invoice...',
        'col_rct' => 'Receipt No.', 'col_customer' => 'Customer', 'col_date' => 'Date',
        'col_method' => 'Method', 'col_inv' => 'Linked Invoice', 'col_amount' => 'Amount', 'col_actions' => 'Actions',
        'empty_title' => 'No Receipts Found', 'empty_desc' => 'You haven\'t issued any sales receipts yet.',
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    .crm-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .crm-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
    .crm-title-box { display: flex; align-items: center; gap: 14px; }
    .crm-title-icon { width: 48px; height: 48px; background: #ecfdf5; color: #10b981; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.1); }
    .crm-title { margin: 0; color: #0f172a; font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px; }
    .crm-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: #64748b; font-weight: 500; }
    
    .btn-primary { background: linear-gradient(135deg, #10b981, #059669); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; text-decoration: none; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); transition: all 0.2s ease; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35); }
    
    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; margin-top: 20px;}
    .crm-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .crm-table th { padding: 16px 24px; background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem; border-bottom: 2px solid #e2e8f0; }
    .crm-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; transition: background 0.2s; }
    .crm-table tr:hover td { background: #f8fafc; }
    
    .badge-method { padding: 4px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; border: 1px solid #a7f3d0; background: #ecfdf5; color: #059669; }
    
    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; font-size: 1.1rem; margin: 0 2px; }
    .action-btn:hover { background: #ecfdf5; border-color: #a7f3d0; color: #059669; }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
    
    .empty-state { text-align: center; padding: 80px 20px; }
    .empty-icon { width: 90px; height: 90px; background: #ecfdf5; color: #10b981; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 3rem; margin-bottom: 20px; }
</style>

<div class="crm-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="crm-header">
        <div class="crm-title-box">
            <div class="crm-title-icon"><i class="ph-duotone ph-money"></i></div>
            <div>
                <h2 class="crm-title"><?= $t['title'] ?></h2>
                <p class="crm-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/sales/receipts/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-check-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-warning-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th style="width: 15%;"><?= $t['col_rct'] ?></th>
                        <th style="width: 25%;"><?= $t['col_customer'] ?></th>
                        <th style="width: 15%;"><?= $t['col_date'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_method'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_inv'] ?></th>
                        <th style="width: 15%; text-align: end;"><?= $t['col_amount'] ?> (<?= htmlspecialchars($currency) ?>)</th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($receipts)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="ph-duotone ph-receipt"></i></div>
                                    <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 1.4rem; font-weight: 800;"><?= $t['empty_title'] ?></h3>
                                    <p style="color: #64748b; margin: 0; font-size: 1rem; font-weight: 500;"><?= $t['empty_desc'] ?></p>
                                    <br>
                                    <a href="/ERP/sales/receipts/create" class="btn-primary" style="margin-top: 16px;"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($receipts as $r): ?>
                            <tr>
                                <td style="font-weight: 800; font-family: monospace; color: #059669; font-size: 0.95rem;"><?= htmlspecialchars($r->receipt_number) ?></td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a; font-size: 0.95rem;"><i class="ph-fill ph-user-circle text-muted"></i> <?= htmlspecialchars($r->customer_name ?? ($isRtl ? 'عميل عام' : 'General Customer')) ?></div>
                                </td>
                                <td><i class="ph-fill ph-calendar-blank text-muted"></i> <?= htmlspecialchars($r->receipt_date) ?></td>
                                <td style="text-align: center;">
                                    <span class="badge-method"><?= strtoupper(htmlspecialchars($r->payment_method)) ?></span>
                                </td>
                                <td style="text-align: center; font-family: monospace; font-weight: 700; color: #2563eb;">
                                    <?= htmlspecialchars($r->invoice_number ?? '---') ?>
                                </td>
                                <td style="text-align: end; font-weight: 900; font-family: monospace; color: #10b981; font-size: 1.1rem;">
                                    <?= number_format(convert_amount($r->amount ?? 0), 2) ?>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <a href="/ERP/sales/receipts/<?= $r->id ?>" class="action-btn" title="<?= $isRtl ? 'عرض' : 'View' ?>"><i class="ph-bold ph-eye"></i></a>
                                    <form action="/ERP/sales/receipts/<?= $r->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $isRtl ? 'تأكيد حذف سند القبض؟' : 'Confirm Delete?' ?>');">
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