<?php
// Path: resources/views/treasury/petty_cash/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$statusMap = [
    'active' => ['label' => __('قيد الاستخدام (نشطة)', 'Active'), 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-clock'],
    'partially_settled' => ['label' => __('مصفاة جزئياً', 'Partially Settled'), 'color' => '#2563eb', 'bg' => '#eff6ff', 'icon' => 'ph-chart-pie-slice'],
    'closed' => ['label' => __('مصفاة بالكامل (مغلقة)', 'Closed/Settled'), 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
];
?>

<style>
    :root {
        --c-pc: #d97706;
        --c-pc-dark: #b45309;
        --c-pc-light: #fef3c7;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .pc-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .pc-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .pc-title-box { display: flex; align-items: center; gap: 16px; }
    .pc-icon { width: 48px; height: 48px; background: var(--c-pc-light); color: var(--c-pc); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(217, 119, 6, 0.12); }
    .pc-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-pc { background: linear-gradient(135deg, var(--c-pc), var(--c-pc-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.2); }
    .btn-pc.disabled { opacity: 0.5; pointer-events: none; cursor: not-allowed; }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-pc-light); color: var(--c-pc); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .pc-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .pc-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .pc-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-pc-light); color: var(--c-pc); border-color: #fde68a; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-pc); color: #ffffff; border-color: var(--c-pc); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
    .branch-badge { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; border: 1px solid #cbd5e1; display: inline-block; margin-top: 4px;}
    
    .progress-line { background: #e2e8f0; height: 6px; border-radius: 3px; overflow: hidden; margin-top: 4px; width: 100px; }
    .progress-fill { background: var(--c-pc); height: 100%; }
</style>

<div class="pc-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="pc-header">
        <div class="pc-title-box">
            <div class="pc-icon"><i class="ph-duotone ph-briefcase"></i></div>
            <div>
                <h2 class="pc-title"><?= __('إدارة العُهد المالية والنثريات (Petty Cash)', 'Petty Cash Management') ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= __('متابعة وتسليم وتصفية العُهد المؤقتة والمستديمة المسلمة للموظفين.', 'Track, issue, and settle temporary/permanent petty cash funds.') ?></p>
            </div>
        </div>
        
        <?php if (has_permission('treasury_petty_cash_create')): ?>
            <a href="/ERP/treasury/petty-cash/create" class="btn-pc"><i class="ph-bold ph-plus"></i> <?= __('تسليم عُهدة جديدة', 'Issue New Custody') ?></a>
        <?php else: ?>
            <a href="javascript:void(0)" class="btn-pc disabled" title="<?= __('ليس لديك صلاحية', 'No Permission') ?>"><i class="ph-bold ph-plus"></i> <?= __('تسليم عُهدة جديدة', 'Issue New Custody') ?></a>
        <?php endif; ?>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-folder-open"></i></div><div class="kpi-info"><h4><?= __('إجمالي العُهد', 'Total Custodies') ?></h4><p><?= number_format($stats->total_custodies ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-coins"></i></div><div class="kpi-info"><h4 style="color:#d97706;"><?= __('إجمالي قيم العُهد', 'Total Amounts') ?></h4><p><?= number_format((float)($stats->total_amount ?? 0), 2) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-receipt"></i></div><div class="kpi-info"><h4 style="color:#dc2626;"><?= __('المصروف والمنصرف', 'Total Settled') ?></h4><p><?= number_format((float)($stats->total_spent ?? 0), 2) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-wallet"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= __('الرصيد المتبقي بعهدة الموظفين', 'Remaining Balance') ?></h4><p><?= number_format((float)($stats->total_remaining ?? 0), 2) ?></p></div></div>
    </div>

    <form action="/ERP/treasury/petty-cash" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="<?= __('ابحث برقم العهدة، اسم الموظف، البيان...', 'Search by code, employee, description...') ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="status" class="form-control" style="flex:1; min-width:150px;">
            <option value=""><?= __('-- كل الحالات --', '-- All Statuses --') ?></option>
            <option value="active" <?= ($statusFilter==='active')?'selected':'' ?>><?= __('نشطة (قيد الاستخدام)', 'Active') ?></option>
            <option value="partially_settled" <?= ($statusFilter==='partially_settled')?'selected':'' ?>><?= __('مصفاة جزئياً', 'Partially Settled') ?></option>
            <option value="closed" <?= ($statusFilter==='closed')?'selected':'' ?>><?= __('مغلقة (مصفاة بالكامل)', 'Closed') ?></option>
        </select>
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($fromDate ?? '') ?>">
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($toDate ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= __('فلترة', 'Filter') ?></button>
    </form>

    <div class="table-card">
        <table class="pc-table">
            <thead>
                <tr>
                    <th style="width: 12%;"><?= __('كود العُهدة', 'Code') ?></th>
                    <th style="width: 12%;"><?= __('تاريخ التسليم', 'Issue Date') ?></th>
                    <th style="width: 20%;"><?= __('الموظف المستلم', 'Employee') ?></th>
                    <th style="width: 18%;"><?= __('الحساب المصدر', 'Account') ?></th>
                    <th style="width: 14%;"><?= __('ميزانية العُهدة', 'Amount') ?></th>
                    <th style="width: 12%;"><?= __('المنصرف / المتبقي', 'Spent/Remaining') ?></th>
                    <th style="width: 12%; text-align: center;"><?= __('الحالة', 'Status') ?></th>
                    <th style="width: 10%; text-align: center;"><?= __('إجراءات', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pettyCashList)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= __('لا توجد عُهد مالية مسجلة بمواصفات البحث.', 'No petty cash records found.') ?></td></tr>
                <?php else: foreach ($pettyCashList as $pc): 
                    $st = $statusMap[$pc->status] ?? $statusMap['active'];
                    $pct = (float)$pc->amount > 0 ? min(100, round(((float)$pc->spent_amount / (float)$pc->amount) * 100)) : 0;
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-pc-dark); font-size: 0.95rem;">
                            <?php if (has_permission('treasury_petty_cash_view')): ?>
                                <a href="/ERP/treasury/petty-cash/<?= $pc->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($pc->code) ?></a>
                            <?php else: ?>
                                <?= htmlspecialchars($pc->code) ?>
                            <?php endif; ?>
                        </td>
                        <td style="font-family: monospace; font-weight: 700;"><?= htmlspecialchars($pc->issue_date) ?></td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($pc->employee_name) ?></div>
                            <div style="font-size: 0.75rem; color: var(--c-text-muted);"><?= htmlspecialchars($pc->description ?? '') ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #475569;"><?= htmlspecialchars($pc->account_name ?? __('الخزينة العامة', 'Main Safe')) ?></div>
                            <?php if (is_hq() && !empty($pc->branch_name)): ?>
                                <div class="branch-badge"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars($pc->branch_name) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-text-dark); font-size: 0.95rem;"><?= number_format((float)$pc->amount, 2) ?></td>
                        <td>
                            <div style="font-size:0.8rem; font-weight:800; color:#dc2626;"><?= __('منصرف:', 'Spent:') ?> <?= number_format((float)$pc->spent_amount, 2) ?></div>
                            <div style="font-size:0.75rem; font-weight:800; color:#059669;"><?= __('متبقي:', 'Remain:') ?> <?= number_format((float)$pc->remaining_amount, 2) ?></div>
                            <div class="progress-line"><div class="progress-fill" style="width:<?= $pct ?>%; background:<?= $pct > 80 ? '#dc2626' : 'var(--c-pc)' ?>;"></div></div>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <?php if (has_permission('treasury_petty_cash_view')): ?>
                                <a href="/ERP/treasury/petty-cash/<?= $pc->id ?>" class="action-btn" title="<?= __('عرض وسجل التصفية', 'View & Settlements') ?>"><i class="ph-bold ph-eye"></i></a>
                            <?php endif; ?>

                            <?php if (has_permission('treasury_petty_cash_edit')): ?>
                                <a href="/ERP/treasury/petty-cash/<?= $pc->id ?>/edit" class="action-btn" title="<?= __('تعديل', 'Edit') ?>"><i class="ph-bold ph-pencil-simple"></i></a>
                            <?php endif; ?>

                            <?php if (has_permission('treasury_petty_cash_delete')): ?>
                                <form action="/ERP/treasury/petty-cash/<?= $pc->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= __('هل أنت متأكد من حذف سجل هذه العُهدة؟', 'Are you sure you want to delete this petty cash?') ?>');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($fromDate) ?>&end_date=<?= urlencode($toDate) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>