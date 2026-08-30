<?php
// Path: resources/views/sales/statements/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isRtl();
$currency = current_currency();

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'كشوف حسابات العملاء', 
        'desc' => 'متابعة الأرصدة التراكمية، المديونيات الحالية والتحصيلات لكل عميل.',
        'search' => 'البحث بالاسم، الكود، أو الهاتف...',
        'col_cust' => 'العميل / الكود', 
        'col_invoiced' => 'إجمالي المبيعات',
        'col_paid' => 'إجمالي المقبوضات', 
        'col_returned' => 'إجمالي المرتجعات',
        'col_balance' => 'الرصيد المستحق الحالي', 
        'col_actions' => 'إجراءات',
        'empty_title' => 'لا توجد حسابات عملاء', 
        'empty_desc' => 'لم يتم تسجيل أي حركات مالية للعملاء حتى الآن.',
        'total_receivables' => 'إجمالي المديونيات المستحقة',
        'total_collected' => 'إجمالي المقبوضات والتحصيلات',
        'active_accounts' => 'عدد الحسابات الفعالة',
        'view_stmt' => 'عرض كشف الحساب'
    ],
    'en' => [
        'title' => 'Customer Statements', 
        'desc' => 'Track running balances, current receivables, and payments per customer.',
        'search' => 'Search by name, code, or phone...',
        'col_cust' => 'Customer / Code', 
        'col_invoiced' => 'Total Invoiced',
        'col_paid' => 'Total Received', 
        'col_returned' => 'Total Returns',
        'col_balance' => 'Net Balance Due', 
        'col_actions' => 'Actions',
        'empty_title' => 'No Customer Statements', 
        'empty_desc' => 'No financial transactions recorded for customers yet.',
        'total_receivables' => 'Total Outstanding Receivables',
        'total_collected' => 'Total Collected Payments',
        'active_accounts' => 'Active Customer Accounts',
        'view_stmt' => 'Statement'
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
    
    .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
    @media (max-width: 768px) { .summary-grid { grid-template-columns: 1fr; } }
    .sum-card { background: #ffffff; border-radius: 16px; padding: 20px 24px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .sum-label { font-size: 0.8rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 6px; }
    .sum-val { font-size: 1.8rem; font-weight: 900; font-family: monospace; color: #0f172a; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; margin-top: 20px;}
    .crm-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .crm-table th { padding: 16px 24px; background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem; border-bottom: 2px solid #e2e8f0; }
    .crm-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; transition: background 0.2s; }
    .crm-table tr:hover td { background: #f8fafc; }
    
    .action-btn { padding: 6px 14px; border-radius: 8px; border: 1px solid #c7d2fe; background: #e0e7ff; color: #4338ca; font-weight: 800; text-decoration: none; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
    .action-btn:hover { background: #4338ca; color: #ffffff; }
    
    .empty-state { text-align: center; padding: 80px 20px; }
    .empty-icon { width: 90px; height: 90px; background: #e0e7ff; color: #4338ca; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 3rem; margin-bottom: 20px; }
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
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-check-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-warning-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <!-- Summary KPI Header Cards -->
    <div class="summary-grid">
        <div class="sum-card" style="border-top: 4px solid #dc2626;">
            <div class="sum-label"><?= $t['total_receivables'] ?> (<?= htmlspecialchars($currency) ?>)</div>
            <div class="sum-val" style="color: #dc2626;"><?= number_format(convert_amount($summary->total_receivables ?? 0), 2) ?></div>
        </div>
        <div class="sum-card" style="border-top: 4px solid #059669;">
            <div class="sum-label"><?= $t['total_collected'] ?> (<?= htmlspecialchars($currency) ?>)</div>
            <div class="sum-val" style="color: #059669;"><?= number_format(convert_amount($summary->total_collected ?? 0), 2) ?></div>
        </div>
        <div class="sum-card" style="border-top: 4px solid #4338ca;">
            <div class="sum-label"><?= $t['active_accounts'] ?></div>
            <div class="sum-val" style="color: #4338ca;"><?= number_format($summary->active_accounts ?? 0) ?></div>
        </div>
    </div>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th style="width: 25%;"><?= $t['col_cust'] ?></th>
                        <th style="width: 18%; text-align: end;"><?= $t['col_invoiced'] ?> (<?= htmlspecialchars($currency) ?>)</th>
                        <th style="width: 18%; text-align: end;"><?= $t['col_paid'] ?> (<?= htmlspecialchars($currency) ?>)</th>
                        <th style="width: 15%; text-align: end;"><?= $t['col_returned'] ?> (<?= htmlspecialchars($currency) ?>)</th>
                        <th style="width: 14%; text-align: end;"><?= $t['col_balance'] ?> (<?= htmlspecialchars($currency) ?>)</th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="ph-duotone ph-files"></i></div>
                                    <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 1.4rem; font-weight: 800;"><?= $t['empty_title'] ?></h3>
                                    <p style="color: #64748b; margin: 0; font-size: 1rem; font-weight: 500;"><?= $t['empty_desc'] ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $c): 
                            $name = $isRtl ? ($c->name_ar ?: $c->name_en) : ($c->name_en ?: $c->name_ar);
                            $bal = convert_amount($c->current_balance ?? 0);
                            $balColor = $bal > 0 ? '#dc2626' : ($bal < 0 ? '#059669' : '#475569');
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800; color: #0f172a; font-size: 0.95rem;"><?= htmlspecialchars($name) ?></div>
                                    <div style="color: #4338ca; font-family: monospace; font-size: 0.85rem; font-weight: 800; margin-top: 2px;"><?= htmlspecialchars($c->code) ?></div>
                                </td>
                                <td style="text-align: end; font-weight: 700; font-family: monospace; color: #334155;"><?= number_format(convert_amount($c->total_invoiced ?? 0), 2) ?></td>
                                <td style="text-align: end; font-weight: 700; font-family: monospace; color: #059669;"><?= number_format(convert_amount($c->total_paid ?? 0), 2) ?></td>
                                <td style="text-align: end; font-weight: 700; font-family: monospace; color: #d97706;"><?= number_format(convert_amount($c->total_returned ?? 0), 2) ?></td>
                                <td style="text-align: end; font-weight: 900; font-family: monospace; font-size: 1.05rem; color: <?= $balColor ?>;">
                                    <?= number_format($bal, 2) ?>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <a href="/ERP/sales/statements/<?= $c->id ?>" class="action-btn"><i class="ph-bold ph-receipt"></i> <?= $t['view_stmt'] ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>