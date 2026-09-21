<?php
// Path: resources/views/treasury/petty_cash/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title' => 'إدارة العُهد المالية والنثريات (Petty Cash)', 'desc' => 'متابعة وتسليم وتصفية العُهد المؤقتة والمستديمة المسلمة للموظفين.',
        'add_btn' => 'تسليم عُهدة جديدة', 'col_code' => 'كود العُهدة', 'col_date' => 'تاريخ التسليم',
        'col_emp' => 'الموظف المستلم', 'col_acc' => 'الحساب المصدر', 'col_budget' => 'ميزانية العُهدة',
        'col_spent' => 'المنصرف / المتبقي', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات',
        'empty' => 'لا توجد عُهد مالية مسجلة بمواصفات البحث.', 'search_ph' => 'ابحث برقم العهدة، اسم الموظف، البيان...',
        'btn_search' => 'فلترة', 'btn_clear' => 'إلغاء', 'kpi_total_custodies' => 'إجمالي العُهد',
        'kpi_total_amount' => 'إجمالي قيم العُهد', 'kpi_spent' => 'المصروف والمنصرف',
        'kpi_remaining' => 'الرصيد المتبقي بعهدة الموظفين', 'active_scope' => 'الفرع النشط:',
        'all_statuses' => '-- كل الحالات --', 'print_list' => 'طباعة القائمة',
        'status_active' => 'قيد الاستخدام (نشطة)', 'status_part' => 'مصفاة جزئياً',
        'status_closed' => 'مصفاة بالكامل (مغلقة)', 'spent_lbl' => 'منصرف:', 'remain_lbl' => 'متبقي:',
        'general' => 'الخزينة العامة', 'confirm_delete' => 'هل أنت متأكد من حذف سجل هذه العُهدة؟'
    ],
    'en' => [
        'title' => 'Petty Cash Management', 'desc' => 'Track, issue, and settle temporary/permanent petty cash funds.',
        'add_btn' => 'Issue New Custody', 'col_code' => 'Code', 'col_date' => 'Issue Date',
        'col_emp' => 'Employee', 'col_acc' => 'Account', 'col_budget' => 'Amount',
        'col_spent' => 'Spent/Remaining', 'col_status' => 'Status', 'col_actions' => 'Actions',
        'empty' => 'No petty cash records found.', 'search_ph' => 'Search by code, employee, description...',
        'btn_search' => 'Filter', 'btn_clear' => 'Clear', 'kpi_total_custodies' => 'Total Custodies',
        'kpi_total_amount' => 'Total Amounts', 'kpi_spent' => 'Total Settled',
        'kpi_remaining' => 'Remaining Balance', 'active_scope' => 'Active Branch:',
        'all_statuses' => '-- All Statuses --', 'print_list' => 'Print List',
        'status_active' => 'Active', 'status_part' => 'Partially Settled',
        'status_closed' => 'Closed/Settled', 'spent_lbl' => 'Spent:', 'remain_lbl' => 'Remain:',
        'general' => 'Main Safe', 'confirm_delete' => 'Are you sure you want to delete this petty cash?'
    ]
][$isRtl ? 'ar' : 'en'];

function getPettyCashBadge($status, $t) {
    $map = [
        'active'            => ['label' => $t['status_active'], 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-clock'],
        'partially_settled' => ['label' => $t['status_part'], 'color' => '#2563eb', 'bg' => '#eff6ff', 'icon' => 'ph-chart-pie-slice'],
        'closed'            => ['label' => $t['status_closed'], 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
    ];
    $s = $map[$status] ?? $map['active'];
    return "<span class='badge-status' style='background:{$s['bg']}; color:{$s['color']}; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem; display:inline-flex; align-items:center; gap:4px; border:1px solid currentColor;'><i class='ph-bold {$s['icon']}'></i> {$s['label']}</span>";
}
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
    .pc-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .pc-title-box { display: flex; align-items: center; gap: 16px; }
    .pc-icon { width: 48px; height: 48px; background: var(--c-pc-light); color: var(--c-pc); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(217, 119, 6, 0.12); }
    .pc-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-pc { background: linear-gradient(135deg, var(--c-pc), var(--c-pc-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.2); }
    .btn-print { background: #0f172a; color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #1e293b; }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 24px; }

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

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; transition: 0.2s; }
    .action-btn:hover { background: var(--c-pc-light); color: var(--c-pc); border-color: #fde68a; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-pc); color: #ffffff; border-color: var(--c-pc); }

    .branch-badge { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; border: 1px solid #cbd5e1; display: inline-block; margin-top: 4px;}
    
    .progress-line { background: #e2e8f0; height: 6px; border-radius: 3px; overflow: hidden; margin-top: 4px; width: 100px; }
    .progress-fill { background: var(--c-pc); height: 100%; }

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body { background: #ffffff !important; color: #000000 !important; }
        .nt-sidebar, header, nav, footer { display: none !important; }
        .search-bar, .pagination, .btn-pc, .btn-print, .action-btn { display: none !important; }
        .pc-table th:last-child, .pc-table td:last-child { display: none !important; }
        .pc-wrapper { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; border-radius: 0 !important; }
        .pc-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .pc-table td { border: 1px solid #cbd5e1 !important; color: #000000 !important; }
        .kpi-card { border: 1px solid #000 !important; box-shadow: none !important; break-inside: avoid; }
        .badge-status { border: 1px solid #000 !important; background: transparent !important; color: #000 !important; }
    }
</style>

<div class="pc-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <div class="pc-header">
        <div class="pc-title-box">
            <div class="pc-icon"><i class="ph-duotone ph-briefcase"></i></div>
            <div>
                <h2 class="pc-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print_list'] ?></button>
            <a href="/ERP/treasury/petty-cash/create" class="btn-pc"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-pc);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-pc); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-folder-open"></i></div><div class="kpi-info"><h4><?= $t['kpi_total_custodies'] ?></h4><p><?= number_format((int)($stats->total_custodies ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-coins"></i></div><div class="kpi-info"><h4 style="color:#d97706;"><?= $t['kpi_total_amount'] ?></h4><p><?= number_format($convert($stats->total_amount ?? 0), 2) ?> <span style="font-size:0.75rem;"><?= $currency ?></span></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-receipt"></i></div><div class="kpi-info"><h4 style="color:#dc2626;"><?= $t['kpi_spent'] ?></h4><p><?= number_format($convert($stats->total_spent ?? 0), 2) ?> <span style="font-size:0.75rem;"><?= $currency ?></span></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-wallet"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= $t['kpi_remaining'] ?></h4><p><?= number_format($convert($stats->total_remaining ?? 0), 2) ?> <span style="font-size:0.75rem;"><?= $currency ?></span></p></div></div>
    </div>

    <form action="/ERP/treasury/petty-cash" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="status" class="form-control" style="flex:1; min-width:150px;">
            <option value=""><?= $t['all_statuses'] ?></option>
            <option value="active" <?= ($statusFilter==='active')?'selected':'' ?>><?= $t['status_active'] ?></option>
            <option value="partially_settled" <?= ($statusFilter==='partially_settled')?'selected':'' ?>><?= $t['status_part'] ?></option>
            <option value="closed" <?= ($statusFilter==='closed')?'selected':'' ?>><?= $t['status_closed'] ?></option>
        </select>
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($fromDate ?? '') ?>">
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($toDate ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($statusFilter) || !empty($fromDate) || !empty($toDate)): ?>
            <a href="/ERP/treasury/petty-cash" class="btn-search" style="background:#f1f5f9; color:#475569; text-decoration:none;"><i class="ph-bold ph-x"></i> <?= $t['btn_clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="pc-table">
            <thead>
                <tr>
                    <th style="width: 12%;"><?= $t['col_code'] ?></th>
                    <th style="width: 12%;"><?= $t['col_date'] ?></th>
                    <th style="width: 20%;"><?= $t['col_emp'] ?></th>
                    <th style="width: 18%;"><?= $t['col_acc'] ?></th>
                    <th style="width: 14%;"><?= $t['col_budget'] ?></th>
                    <th style="width: 12%;"><?= $t['col_spent'] ?></th>
                    <th style="width: 12%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pettyCashList)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($pettyCashList as $pc): 
                    $amt = (float)($pc->amount ?? 0);
                    $sp  = (float)($pc->spent_amount ?? 0);
                    $rem = (float)($pc->remaining_amount ?? 0);
                    $pct = $amt > 0 ? min(100, round(($sp / $amt) * 100)) : 0;
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-pc-dark); font-size: 0.95rem;">
                            <a href="/ERP/treasury/petty-cash/<?= $pc->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars((string)$pc->code) ?></a>
                        </td>
                        <td style="font-family: monospace; font-weight: 700;"><?= htmlspecialchars((string)$pc->issue_date) ?></td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars((string)$pc->employee_name) ?></div>
                            <div style="font-size: 0.75rem; color: var(--c-text-muted);"><?= htmlspecialchars((string)($pc->description ?? '')) ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #475569;"><?= htmlspecialchars((string)($pc->account_name ?? $t['general'])) ?></div>
                            <?php if(!empty($pc->branch_name)): ?>
                                <div class="branch-badge"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars((string)$pc->branch_name) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-text-dark); font-size: 0.95rem;">
                            <?= number_format($convert($amt), 2) ?> <span style="font-size:0.75rem; color:#64748b;"><?= $currency ?></span>
                        </td>
                        <td>
                            <div style="font-size:0.8rem; font-weight:800; color:#dc2626;"><?= $t['spent_lbl'] ?> <?= number_format($convert($sp), 2) ?></div>
                            <div style="font-size:0.75rem; font-weight:800; color:#059669;"><?= $t['remain_lbl'] ?> <?= number_format($convert($rem), 2) ?></div>
                            <div class="progress-line"><div class="progress-fill" style="width:<?= $pct ?>%; background:<?= $pct > 80 ? '#dc2626' : 'var(--c-pc)' ?>;"></div></div>
                        </td>
                        <td style="text-align: center;">
                            <?= getPettyCashBadge($pc->status ?? 'active', $t) ?>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/treasury/petty-cash/<?= $pc->id ?>" class="action-btn" title="عرض وسجل التصفية"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/treasury/petty-cash/<?= $pc->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/treasury/petty-cash/<?= $pc->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
                                <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
                            </form>
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