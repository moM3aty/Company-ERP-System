<?php
// Path: resources/views/sales/contracts/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isRtl();
$currency = current_currency();

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'عقود المبيعات والاشتراكات', 'desc' => 'إدارة العقود السنوية، اتفاقيات المبيعات، ومتابعة فترات السداد والتجديد.',
        'add_btn' => 'إبرام عقد جديد', 'search' => 'البحث برقم العقد، العميل أو عنوان الاتفاقية...',
        'col_num' => 'رقم العقد', 'col_title' => 'عنوان العقد / العميل', 'col_dates' => 'سريان العقد',
        'col_freq' => 'دورية الفوترة', 'col_value' => 'القيمة الإجمالية', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات',
        'empty_title' => 'لا توجد عقود مبيعات', 'empty_desc' => 'لم تقم بإبرام أي عقود أو اشتراكات للعملاء حتى الآن.',
    ],
    'en' => [
        'title' => 'Sales Contracts', 'desc' => 'Manage annual agreements, service contracts, and subscription renewals.',
        'add_btn' => 'New Contract', 'search' => 'Search by contract no, customer, or title...',
        'col_num' => 'Contract No.', 'col_title' => 'Contract / Customer', 'col_dates' => 'Validity Period',
        'col_freq' => 'Billing Cycle', 'col_value' => 'Total Value', 'col_status' => 'Status', 'col_actions' => 'Actions',
        'empty_title' => 'No Contracts Found', 'empty_desc' => 'Start by creating your first sales contract.',
    ]
][$isRtl ? 'ar' : 'en'];

$freqMap = [
    'one_time'      => $isRtl ? 'دفعة واحدة' : 'One Time',
    'monthly'       => $isRtl ? 'شهرياً' : 'Monthly',
    'quarterly'     => $isRtl ? 'ربع سنوي' : 'Quarterly',
    'semi_annually' => $isRtl ? 'نصف سنوي' : 'Semi-Annually',
    'annually'      => $isRtl ? 'سنوياً' : 'Annually'
];
?>

<style>
    .crm-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .crm-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
    .crm-title-box { display: flex; align-items: center; gap: 14px; }
    .crm-title-icon { width: 48px; height: 48px; background: #f0fdf4; color: #16a34a; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(22, 163, 74, 0.1); }
    .crm-title { margin: 0; color: #0f172a; font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px; }
    .crm-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: #64748b; font-weight: 500; }
    
    .btn-primary { background: linear-gradient(135deg, #16a34a, #15803d); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; text-decoration: none; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25); transition: all 0.2s ease; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(22, 163, 74, 0.35); }
    
    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; margin-top: 20px;}
    .crm-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .crm-table th { padding: 16px 24px; background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem; border-bottom: 2px solid #e2e8f0; }
    .crm-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; transition: background 0.2s; }
    .crm-table tr:hover td { background: #f8fafc; }
    
    .badge { padding: 4px 12px; border-radius: 999px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; display: inline-flex; align-items: center; justify-content: center;}
    .bg-draft { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
    .bg-active { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .bg-expired { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
    .bg-terminated { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; font-size: 1.1rem; margin: 0 2px; }
    .action-btn:hover { background: #f0fdf4; border-color: #bbf7d0; color: #16a34a; }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
    
    .empty-state { text-align: center; padding: 80px 20px; }
    .empty-icon { width: 90px; height: 90px; background: #f0fdf4; color: #16a34a; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 3rem; margin-bottom: 20px; }
</style>

<div class="crm-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="crm-header">
        <div class="crm-title-box">
            <div class="crm-title-icon"><i class="ph-duotone ph-file-text"></i></div>
            <div>
                <h2 class="crm-title"><?= $t['title'] ?></h2>
                <p class="crm-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/sales/contracts/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-check-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-warning-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th style="width: 15%;"><?= $t['col_num'] ?></th>
                        <th style="width: 25%;"><?= $t['col_title'] ?></th>
                        <th style="width: 20%;"><?= $t['col_dates'] ?></th>
                        <th style="width: 12%; text-align: center;"><?= $t['col_freq'] ?></th>
                        <th style="width: 15%; text-align: end;"><?= $t['col_value'] ?> (<?= htmlspecialchars($currency) ?>)</th>
                        <th style="width: 8%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contracts)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="ph-duotone ph-file-text"></i></div>
                                    <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 1.4rem; font-weight: 800;"><?= $t['empty_title'] ?></h3>
                                    <p style="color: #64748b; margin: 0; font-size: 1rem; font-weight: 500;"><?= $t['empty_desc'] ?></p>
                                    <br>
                                    <a href="/ERP/sales/contracts/create" class="btn-primary" style="margin-top: 16px;"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contracts as $c): ?>
                            <tr>
                                <td style="font-weight: 800; font-family: monospace; color: #16a34a; font-size: 0.95rem;"><?= htmlspecialchars($c->contract_number) ?></td>
                                <td>
                                    <div style="font-weight: 800; color: #0f172a; font-size: 0.95rem;"><?= htmlspecialchars($c->title) ?></div>
                                    <div style="font-size: 0.82rem; color: #64748b; margin-top:2px;"><i class="ph-fill ph-user-circle"></i> <?= htmlspecialchars($c->customer_name ?? ($isRtl ? 'عميل عام' : 'General Customer')) ?></div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #334155; font-size: 0.85rem;"><i class="ph-bold ph-calendar text-muted"></i> <?= htmlspecialchars($c->start_date) ?></div>
                                    <div style="font-weight: 600; color: #dc2626; font-size: 0.82rem; margin-top:2px;"><i class="ph-bold ph-calendar-x text-muted"></i> <?= htmlspecialchars($c->end_date) ?></div>
                                </td>
                                <td style="text-align: center;">
                                    <span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; border: 1px solid #e2e8f0;">
                                        <?= $freqMap[$c->billing_frequency] ?? $c->billing_frequency ?>
                                    </span>
                                </td>
                                <td style="text-align: end; font-weight: 900; font-family: monospace; color: #0f172a; font-size: 1.05rem;">
                                    <?= number_format($c->total_value, 2) ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge bg-<?= strtolower($c->status) ?>"><?= strtoupper(htmlspecialchars($c->status)) ?></span>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <a href="/ERP/sales/contracts/<?= $c->id ?>" class="action-btn" title="عرض العقد والطباعة"><i class="ph-bold ph-eye"></i></a>
                                    <a href="/ERP/sales/contracts/<?= $c->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                    <form action="/ERP/sales/contracts/<?= $c->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $isRtl ? 'تأكيد حذف العقد؟' : 'Are you sure?' ?>');">
                                        <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
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