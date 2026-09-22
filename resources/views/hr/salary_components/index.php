<?php
// Path: resources/views/hr/salary_components/index.php

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
        'title' => 'مفردات الراتب والبدلات (Salary Components)',
        'desc' => 'إدارة عناصر وهيكل الراتب من بدلات إضافية واستقطاعات مسجلة.',
        'add_btn' => 'إضافة بدل / استقطاع',
        'col_name' => 'اسم المفرد / البدل',
        'col_emp' => 'الموظف المعني',
        'col_type' => 'النوع',
        'col_amount' => 'المبلغ (المقدار)',
        'col_fixed' => 'الخاصية',
        'col_actions' => 'إجراءات',
        'empty' => 'لا توجد مفردات رواتب مسجلة.',
        'search_ph' => 'ابحث باسم البدل، الموظف، أو الكود...',
        'btn_search' => 'فلترة',
        'btn_clear' => 'إلغاء',
        'active_scope' => 'الفرع النشط:',
        'kpi_total' => 'إجمالي المفردات',
        'kpi_allowances' => 'إجمالي البدلات',
        'kpi_deductions' => 'إجمالي الخصومات',
        'kpi_fixed' => 'عناصر ثنائية/ثابتة',
        'all_types' => '-- كل الأنواع --',
        'type_allowance' => 'بدلات وإضافات',
        'type_deduction' => 'استقطاعات وخصومات',
        'fixed_yes' => 'بدل ثابت',
        'fixed_no' => 'متغير',
        'general_emp' => 'عام',
        'print_list' => 'طباعة القائمة',
        'confirm_delete' => 'هل أنت متأكد من حذف هذا العنصر؟'
    ],
    'en' => [
        'title' => 'Salary Components & Allowances',
        'desc' => 'Manage salary structures, recurring allowances, and deductions.',
        'add_btn' => 'Add Component / Allowance',
        'col_name' => 'Component Name',
        'col_emp' => 'Employee',
        'col_type' => 'Type',
        'col_amount' => 'Amount',
        'col_fixed' => 'Property',
        'col_actions' => 'Actions',
        'empty' => 'No salary components recorded.',
        'search_ph' => 'Search by component name, employee, or code...',
        'btn_search' => 'Filter',
        'btn_clear' => 'Clear',
        'active_scope' => 'Active Branch:',
        'kpi_total' => 'Total Components',
        'kpi_allowances' => 'Total Allowances',
        'kpi_deductions' => 'Total Deductions',
        'kpi_fixed' => 'Fixed Components',
        'all_types' => '-- All Types --',
        'type_allowance' => 'Allowances & Earnings',
        'type_deduction' => 'Deductions & Cuts',
        'fixed_yes' => 'Fixed Monthly',
        'fixed_no' => 'Variable',
        'general_emp' => 'General',
        'print_list' => 'Print List',
        'confirm_delete' => 'Are you sure you want to delete this component?'
    ]
][$isRtl ? 'ar' : 'en'];

$typeMap = [
    'allowance' => ['label' => $t['type_allowance'], 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-plus-circle'],
    'deductions' => ['label' => $t['type_deduction'], 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-minus-circle'],
    'deduction' => ['label' => $t['type_deduction'], 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-minus-circle'],
];
?>

<style>
    :root {
        --c-comp: #4338ca;
        --c-comp-dark: #312e81;
        --c-comp-light: #e0e7ff;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .comp-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .comp-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .comp-title-box { display: flex; align-items: center; gap: 16px; }
    .comp-icon { width: 48px; height: 48px; background: var(--c-comp-light); color: var(--c-comp); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(67, 56, 202, 0.15); }
    .comp-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-comp { background: linear-gradient(135deg, var(--c-comp), var(--c-comp-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(67, 56, 202, 0.25); }
    .btn-print { background: #0f172a; color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #1e293b; }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 24px; }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-comp-light); color: var(--c-comp); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .comp-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .comp-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .comp-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-comp-light); color: var(--c-comp); border-color: #c7d2fe; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-comp); color: #ffffff; border-color: var(--c-comp); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
    .branch-badge { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; border: 1px solid #cbd5e1; display: inline-block; margin-top: 4px;}

    /* إخفاء DataTables Injection */
    .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { display: none !important; }

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body { background: #ffffff !important; color: #000000 !important; }
        .nt-sidebar, header, nav, footer { display: none !important; }
        .search-bar, .pagination, .btn-comp, .btn-print, .action-btn { display: none !important; }
        .comp-table th:last-child, .comp-table td:last-child { display: none !important; }
        .comp-wrapper { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; border-radius: 0 !important; }
        .comp-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .comp-table td { border: 1px solid #cbd5e1 !important; color: #000000 !important; }
        .kpi-card { border: 1px solid #000 !important; box-shadow: none !important; break-inside: avoid; }
        .badge-status { border: 1px solid #000 !important; background: transparent !important; color: #000 !important; }
    }
</style>

<div class="comp-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="comp-header">
        <div class="comp-title-box">
            <div class="comp-icon"><i class="ph-duotone ph-sliders-horizontal"></i></div>
            <div>
                <h2 class="comp-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print_list'] ?></button>
            <a href="/ERP/hr/salary-components/create" class="btn-comp"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-comp);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-comp); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-list-checks"></i></div><div class="kpi-info"><h4><?= $t['kpi_total'] ?></h4><p><?= number_format((int)($stats->total_items ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-plus-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= $t['kpi_allowances'] ?></h4><p><?= number_format($convert($stats->total_allowances ?? 0), 2) ?> <span style="font-size:0.75rem; color:#64748b;"><?= $currency ?></span></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-minus-circle"></i></div><div class="kpi-info"><h4 style="color:#dc2626;"><?= $t['kpi_deductions'] ?></h4><p><?= number_format($convert($stats->total_deductions ?? 0), 2) ?> <span style="font-size:0.75rem; color:#64748b;"><?= $currency ?></span></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#e0e7ff; color:#4338ca;"><i class="ph-duotone ph-push-pin"></i></div><div class="kpi-info"><h4 style="color:#4338ca;"><?= $t['kpi_fixed'] ?></h4><p><?= number_format((int)($stats->fixed_count ?? 0)) ?></p></div></div>
    </div>

    <form action="/ERP/hr/salary-components" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="type" class="form-control" style="flex:1; min-width:140px;">
            <option value=""><?= $t['all_types'] ?></option>
            <option value="allowance" <?= ($typeFilter==='allowance')?'selected':'' ?>><?= $t['type_allowance'] ?></option>
            <option value="deduction" <?= ($typeFilter==='deduction')?'selected':'' ?>><?= $t['type_deduction'] ?></option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($typeFilter)): ?>
            <a href="/ERP/hr/salary-components" class="btn-search" style="background:#f1f5f9; color:#475569; text-decoration:none;"><i class="ph-bold ph-x"></i> <?= $t['btn_clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="comp-table">
            <thead>
                <tr>
                    <th style="width: 25%;"><?= $t['col_name'] ?></th>
                    <th style="width: 25%;"><?= $t['col_emp'] ?></th>
                    <th style="width: 15%;"><?= $t['col_type'] ?></th>
                    <th style="width: 15%;"><?= $t['col_amount'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_fixed'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($components)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($components as $c): 
                    $tp = $typeMap[$c->type] ?? $typeMap['allowance'];
                    $empName = $isRtl ? ($c->employee_name ?? $t['general_emp']) : ($c->employee_name_en ?: ($c->employee_name ?? $t['general_emp']));
                ?>
                    <tr>
                        <td style="font-weight: 800; color: var(--c-text-dark);">
                            <a href="/ERP/hr/salary-components/<?= $c->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars((string)$c->name_ar) ?></a>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: #334155;"><?= htmlspecialchars((string)$empName) ?></div>
                            <div style="font-size: 0.75rem; color: #64748b; font-family: monospace;"><?= htmlspecialchars((string)$c->emp_code) ?></div>
                            <?php if(!empty($c->branch_name)): ?>
                                <div class="branch-badge"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars((string)$c->branch_name) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge-status" style="background:<?= $tp['bg'] ?>; color:<?= $tp['color'] ?>;">
                                <i class="ph-bold <?= $tp['icon'] ?>"></i> <?= $tp['label'] ?>
                            </span>
                        </td>
                        <td style="font-family: monospace; font-weight: 900; color: <?= $c->type==='allowance'?'#059669':'#dc2626' ?>; font-size:1rem;">
                            <?= number_format($convert($c->amount ?? 0), 2) ?> <span style="font-size:0.75rem; color:#64748b;"><?= $currency ?></span>
                        </td>
                        <td style="text-align: center;">
                            <span style="font-size:0.75rem; font-weight:800; padding:2px 8px; border-radius:4px; background:#f1f5f9; color:#475569;">
                                <?= $c->is_fixed ? $t['fixed_yes'] : $t['fixed_no'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/hr/salary-components/<?= $c->id ?>" class="action-btn" title="عرض التفاصيل"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/hr/salary-components/<?= $c->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/hr/salary-components/<?= $c->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($typeFilter) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.table-card .dataTables_filter, .table-card .dataTables_length, .table-card .dataTables_info, .table-card .dataTables_paginate').forEach(el => el.remove());
});
</script>