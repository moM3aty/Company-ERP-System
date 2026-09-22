<?php
// Path: resources/views/hr/contracts/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert  = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title' => 'عقود الموظفين (Employment Contracts)',
        'desc' => 'إدارة وتتبع عقود العمل وتفاصيل الرواتب والبدلات.',
        'add_btn' => 'إبرام عقد جديد',
        'col_code' => 'رقم العقد',
        'col_emp' => 'اسم الموظف',
        'col_salary' => 'الراتب والبدلات',
        'col_dates' => 'تاريخ العقد',
        'col_status' => 'الحالة',
        'col_actions' => 'إجراءات',
        'empty' => 'لا توجد عقود مسجلة بمواصفات البحث.',
        'search_ph' => 'ابحث برقم العقد، اسم الموظف، كود الموظف...',
        'btn_search' => 'فلترة',
        'btn_clear' => 'إلغاء',
        'active_scope' => 'الفرع النشط:',
        'kpi_total' => 'إجمالي العقود',
        'kpi_active' => 'عقود سارية',
        'kpi_expired' => 'عقود منتهية',
        'kpi_terminated' => 'عقود مفسوخة',
        'all_statuses' => '-- كل الحالات --',
        'status_active' => 'عقد ساري',
        'status_expired' => 'عقد منتهي',
        'status_terminated' => 'عقد مفسوخ',
        'start_lbl' => 'بدء:',
        'end_lbl' => 'انتهاء:',
        'unspecified' => 'غير محدد',
        'unknown_emp' => 'مجهول',
        'basic_lbl' => 'أساسي:',
        'total_lbl' => 'الإجمالي:',
        'print_list' => 'طباعة القائمة',
        'confirm_delete' => 'هل أنت متأكد من حذف هذا العقد؟'
    ],
    'en' => [
        'title' => 'Employment Contracts',
        'desc' => 'Manage employment contracts, salary packages, and terms.',
        'add_btn' => 'New Contract',
        'col_code' => 'Contract Code',
        'col_emp' => 'Employee Name',
        'col_salary' => 'Salary & Allowances',
        'col_dates' => 'Contract Dates',
        'col_status' => 'Status',
        'col_actions' => 'Actions',
        'empty' => 'No contract records found.',
        'search_ph' => 'Search by contract code, employee name, or ID...',
        'btn_search' => 'Filter',
        'btn_clear' => 'Clear',
        'active_scope' => 'Active Branch:',
        'kpi_total' => 'Total Contracts',
        'kpi_active' => 'Active Contracts',
        'kpi_expired' => 'Expired Contracts',
        'kpi_terminated' => 'Terminated Contracts',
        'all_statuses' => '-- All Statuses --',
        'status_active' => 'Active',
        'status_expired' => 'Expired',
        'status_terminated' => 'Terminated',
        'start_lbl' => 'Start:',
        'end_lbl' => 'End:',
        'unspecified' => 'Indefinite',
        'unknown_emp' => 'Unknown',
        'basic_lbl' => 'Basic:',
        'total_lbl' => 'Total:',
        'print_list' => 'Print List',
        'confirm_delete' => 'Are you sure you want to delete this contract?'
    ]
][$isRtl ? 'ar' : 'en'];

$statusMap = [
    'active'     => ['label' => $t['status_active'], 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
    'expired'    => ['label' => $t['status_expired'], 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-clock-countdown'],
    'terminated' => ['label' => $t['status_terminated'], 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-x-circle'],
];
?>

<style>
    :root {
        --c-hcont: #d97706;
        --c-hcont-dark: #b45309;
        --c-hcont-light: #fef3c7;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .hcont-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .hcont-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .hcont-title-box { display: flex; align-items: center; gap: 16px; }
    .hcont-icon { width: 48px; height: 48px; background: var(--c-hcont-light); color: var(--c-hcont); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(217, 119, 6, 0.15); }
    .hcont-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-hcont { background: linear-gradient(135deg, var(--c-hcont), var(--c-hcont-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25); }
    .btn-print { background: #0f172a; color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #1e293b; }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 24px; }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-hcont-light); color: var(--c-hcont); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .hcont-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .hcont-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .hcont-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-hcont-light); color: var(--c-hcont); border-color: #fde68a; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-hcont); color: #ffffff; border-color: var(--c-hcont); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
    .branch-badge { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; border: 1px solid #cbd5e1; display: inline-block; margin-top: 4px;}

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body { background: #ffffff !important; color: #000000 !important; }
        .nt-sidebar, header, nav, footer { display: none !important; }
        .search-bar, .pagination, .btn-hcont, .btn-print, .action-btn { display: none !important; }
        .hcont-table th:last-child, .hcont-table td:last-child { display: none !important; }
        .hcont-wrapper { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; border-radius: 0 !important; }
        .hcont-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .hcont-table td { border: 1px solid #cbd5e1 !important; color: #000000 !important; }
        .kpi-card { border: 1px solid #000 !important; box-shadow: none !important; break-inside: avoid; }
        .badge-status { border: 1px solid #000 !important; background: transparent !important; color: #000 !important; }
    }
</style>

<div class="hcont-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="hcont-header">
        <div class="hcont-title-box">
            <div class="hcont-icon"><i class="ph-duotone ph-file-signature"></i></div>
            <div>
                <h2 class="hcont-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print_list'] ?></button>
            <a href="/ERP/hr/contracts/create" class="btn-hcont"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-hcont);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-hcont); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-files"></i></div><div class="kpi-info"><h4><?= $t['kpi_total'] ?></h4><p><?= number_format((int)($stats->total_contracts ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= $t['kpi_active'] ?></h4><p><?= number_format((int)($stats->active_contracts ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-clock-countdown"></i></div><div class="kpi-info"><h4 style="color:#d97706;"><?= $t['kpi_expired'] ?></h4><p><?= number_format((int)($stats->expired_contracts ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-x-circle"></i></div><div class="kpi-info"><h4 style="color:#dc2626;"><?= $t['kpi_terminated'] ?></h4><p><?= number_format((int)($stats->terminated_contracts ?? 0)) ?></p></div></div>
    </div>

    <form action="/ERP/hr/contracts" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:220px;" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value=""><?= $t['all_statuses'] ?></option>
            <option value="active" <?= ($statusFilter==='active')?'selected':'' ?>><?= $t['status_active'] ?></option>
            <option value="expired" <?= ($statusFilter==='expired')?'selected':'' ?>><?= $t['status_expired'] ?></option>
            <option value="terminated" <?= ($statusFilter==='terminated')?'selected':'' ?>><?= $t['status_terminated'] ?></option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($statusFilter)): ?>
            <a href="/ERP/hr/contracts" class="btn-search" style="background:#f1f5f9; color:#475569; text-decoration:none;"><i class="ph-bold ph-x"></i> <?= $t['btn_clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="hcont-table">
            <thead>
                <tr>
                    <th style="width: 12%;"><?= $t['col_code'] ?></th>
                    <th style="width: 25%;"><?= $t['col_emp'] ?></th>
                    <th style="width: 20%;"><?= $t['col_salary'] ?></th>
                    <th style="width: 18%;"><?= $t['col_dates'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contracts)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($contracts as $c): 
                    $st = $statusMap[$c->status] ?? $statusMap['active'];
                    $empName  = $isRtl ? ($c->employee_name ?? $t['unknown_emp']) : ($c->employee_name_en ?: ($c->employee_name ?? $t['unknown_emp']));
                    $totalSal = (float)($c->basic_salary ?? 0) + (float)($c->housing_allowance ?? 0) + (float)($c->transport_allowance ?? 0);
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-hcont-dark); font-size: 0.95rem;">
                            <a href="/ERP/hr/contracts/<?= $c->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars((string)$c->contract_code) ?></a>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars((string)$empName) ?></div>
                            <div style="font-size: 0.78rem; color: var(--c-text-muted); font-family:monospace;"><?= htmlspecialchars((string)$c->emp_code) ?></div>
                            <?php if(!empty($c->branch_name)): ?>
                                <div class="branch-badge"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars((string)$c->branch_name) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-family: monospace; font-weight: 900; color: #059669;"><?= $t['total_lbl'] ?> <?= number_format($convert($totalSal), 2) ?> <span style="font-size:0.75rem; color:#64748b;"><?= $currency ?></span></div>
                            <div style="font-size: 0.75rem; color: #64748b;">(<?= $t['basic_lbl'] ?> <?= number_format($convert($c->basic_salary ?? 0), 2) ?>)</div>
                        </td>
                        <td style="font-family: monospace; font-size: 0.85rem; font-weight: 700;">
                            <div><span style="color:#64748b;"><?= $t['start_lbl'] ?></span> <?= htmlspecialchars((string)$c->start_date) ?></div>
                            <div><span style="color:#64748b;"><?= $t['end_lbl'] ?></span> <?= htmlspecialchars((string)($c->end_date ?: $t['unspecified'])) ?></div>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/hr/contracts/<?= $c->id ?>" class="action-btn" title="عرض العقد"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/hr/contracts/<?= $c->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/hr/contracts/<?= $c->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>