<?php
// Path: resources/views/treasury/transfers/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);
?>

<style>
    :root {
        --c-trf: #4f46e5;
        --c-trf-dark: #4338ca;
        --c-trf-light: #e0e7ff;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .trf-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .trf-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .trf-title-box { display: flex; align-items: center; gap: 16px; }
    .trf-icon { width: 48px; height: 48px; background: var(--c-trf-light); color: var(--c-trf); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.12); }
    .trf-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-trf { background: linear-gradient(135deg, var(--c-trf), var(--c-trf-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2); }
    .btn-trf.disabled { opacity: 0.5; pointer-events: none; cursor: not-allowed; }

    .kpi-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:768px){ .kpi-row { grid-template-columns: 1fr; } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; display: flex; align-items: center; gap: 16px; }
    .kpi-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: var(--c-trf-light); color: var(--c-trf); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.3rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .trf-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .trf-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .trf-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-trf-light); color: var(--c-trf); border-color: #c7d2fe; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-trf); color: #ffffff; border-color: var(--c-trf); }

    .transfer-flow { display: flex; align-items: center; gap: 8px; font-weight: 800; flex-wrap:wrap; }
    .acc-tag { background: #f1f5f9; color: #334155; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; border: 1px solid #e2e8f0; display:inline-flex; align-items:center; gap:4px; }
    .branch-badge { background: #f8fafc; color: #64748b; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; border: 1px dashed #cbd5e1; }
</style>

<div class="trf-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="trf-header">
        <div class="trf-title-box">
            <div class="trf-icon"><i class="ph-duotone ph-arrows-left-right"></i></div>
            <div>
                <h2 class="trf-title"><?= __('التحويلات النقدية الداخلية (Internal Transfers)', 'Internal Transfers') ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= __('نقل وتداول السيولة النقدية بين الصناديق الرئيسية والحسابات البنكية والعكس.', 'Manage liquidity transfers between main safes and bank accounts.') ?></p>
            </div>
        </div>
        <?php if (has_permission('treasury_transfers_create')): ?>
            <a href="/ERP/treasury/transfers/create" class="btn-trf"><i class="ph-bold ph-plus"></i> <?= __('إجراء تحويل نقدي جديد', 'Create New Transfer') ?></a>
        <?php else: ?>
            <a href="javascript:void(0)" class="btn-trf disabled" title="<?= __('ليس لديك صلاحية', 'No Permission') ?>"><i class="ph-bold ph-plus"></i> <?= __('إجراء تحويل نقدي جديد', 'Create New Transfer') ?></a>
        <?php endif; ?>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="ph-duotone ph-swap"></i></div>
            <div class="kpi-info">
                <h4><?= __('إجمالي حركات التحويل الداخلي', 'Total Transfers') ?></h4>
                <p><?= number_format($stats->total_transfers ?? 0) ?></p>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#e0e7ff; color:#4f46e5;"><i class="ph-duotone ph-currency-circle-dollar"></i></div>
            <div class="kpi-info">
                <h4 style="color:#4f46e5;"><?= __('إجمالي المبالغ المحولة', 'Total Transferred Amount') ?></h4>
                <p><?= number_format((float)($stats->total_amount ?? 0), 2) ?></p>
            </div>
        </div>
    </div>

    <form action="/ERP/treasury/transfers" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:220px;" placeholder="<?= __('ابحث برقم التحويل، اسم الخزينة المصدر أو الواردة، البيان...', 'Search transfer no, account, description...') ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($fromDate ?? '') ?>">
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($toDate ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= __('فلترة', 'Filter') ?></button>
    </form>

    <div class="table-card">
        <table class="trf-table">
            <thead>
                <tr>
                    <th style="width: 14%;"><?= __('رقم التحويل', 'Transfer No') ?></th>
                    <th style="width: 12%;"><?= __('التاريخ', 'Date') ?></th>
                    <th style="width: 35%;"><?= __('مسار التحويل (من ← إلى)', 'Flow (From → To)') ?></th>
                    <th style="width: 15%;"><?= __('الرقم المرجعي', 'Reference') ?></th>
                    <th style="width: 14%;"><?= __('المبلغ المحول', 'Amount') ?></th>
                    <th style="width: 10%; text-align: center;"><?= __('إجراءات', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transfers)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= __('لا توجد حركات تحويل نقدية مسجلة.', 'No transfer records found.') ?></td></tr>
                <?php else: foreach ($transfers as $t): ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-trf-dark); font-size: 0.95rem;">
                            <?php if (has_permission('treasury_transfers_view')): ?>
                                <a href="/ERP/treasury/transfers/<?= $t->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($t->transfer_number) ?></a>
                            <?php else: ?>
                                <?= htmlspecialchars($t->transfer_number) ?>
                            <?php endif; ?>
                        </td>
                        <td style="font-family: monospace; font-weight: 700;"><?= htmlspecialchars($t->transfer_date) ?></td>
                        <td>
                            <div class="transfer-flow">
                                <span class="acc-tag" style="background:#fef2f2; color:#dc2626; border-color:#fecaca;">
                                    <i class="ph-bold ph-minus"></i> 
                                    <span>
                                        <?= htmlspecialchars($t->from_account_name ?? __('غير محدد', 'Unspecified')) ?>
                                        <?php if(is_hq() && !empty($t->from_branch_name)): ?> <span class="branch-badge"><?= htmlspecialchars($t->from_branch_name) ?></span> <?php endif; ?>
                                    </span>
                                </span>
                                <i class="ph-bold <?= $isRtl ? 'ph-arrow-left' : 'ph-arrow-right' ?>" style="color:var(--c-trf);"></i>
                                <span class="acc-tag" style="background:#ecfdf5; color:#059669; border-color:#a7f3d0;">
                                    <i class="ph-bold ph-plus"></i> 
                                    <span>
                                        <?= htmlspecialchars($t->to_account_name ?? __('غير محدد', 'Unspecified')) ?>
                                        <?php if(is_hq() && !empty($t->to_branch_name)): ?> <span class="branch-badge"><?= htmlspecialchars($t->to_branch_name) ?></span> <?php endif; ?>
                                    </span>
                                </span>
                            </div>
                            <?php if(!empty($t->description)): ?>
                                <div style="font-size:0.75rem; color:var(--c-text-muted); margin-top:4px;"><?= htmlspecialchars($t->description) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-family: monospace; font-weight: 700; color: #475569;"><?= htmlspecialchars($t->reference_no ?: '---') ?></td>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-trf-dark); font-size: 1rem;"><?= number_format((float)$t->amount, 2) ?></td>
                        <td style="text-align: center; white-space: nowrap;">
                            <?php if (has_permission('treasury_transfers_view')): ?>
                                <a href="/ERP/treasury/transfers/<?= $t->id ?>" class="action-btn" title="<?= __('عرض وسند الطباعة', 'View & Print') ?>"><i class="ph-bold ph-printer"></i></a>
                            <?php endif; ?>

                            <?php if (has_permission('treasury_transfers_edit')): ?>
                                <a href="/ERP/treasury/transfers/<?= $t->id ?>/edit" class="action-btn" title="<?= __('تعديل', 'Edit') ?>"><i class="ph-bold ph-pencil-simple"></i></a>
                            <?php endif; ?>

                            <?php if (has_permission('treasury_transfers_delete')): ?>
                                <form action="/ERP/treasury/transfers/<?= $t->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= __('هل أنت متأكد من حذف أمر التحويل؟', 'Are you sure you want to delete this transfer?') ?>');">
                                    <button type="submit" class="action-btn delete" title="<?= __('حذف', 'Delete') ?>"><i class="ph-bold ph-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($fromDate) ?>&end_date=<?= urlencode($toDate) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>