<?php
// Path: resources/views/hr/designations/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title' => 'الدرجات والمسميات الوظيفية (Designations & Job Titles)',
        'desc' => 'إدارة دليل المسميات الوظيفية، السلم الوظيفي، والارتباط بالإدارات.',
        'add_btn' => 'إضافة مسمى وظيفي جديد',
        'col_code' => 'الكود',
        'col_title' => 'المسمى الوظيفي',
        'col_dept' => 'الإدارة التابع لها',
        'col_grade' => 'الدرجة المالية (Pay Grade)',
        'col_status' => 'الحالة',
        'col_actions' => 'إجراءات',
        'empty' => 'لا توجد مسميات وظيفية مسجلة.',
        'search_ph' => 'ابحث بكود المسمى، العنوان بالعربي/الإنجليزي، أو الدرجة المالية...',
        'btn_search' => 'فلترة',
        'btn_clear' => 'إلغاء',
        'active_scope' => 'الفرع النشط:',
        'kpi_total' => 'إجمالي المسميات',
        'kpi_active' => 'مسميات مفعلة',
        'kpi_depts' => 'إدارات مرتبطة',
        'kpi_grades' => 'الدرجات المالية',
        'all_depts' => '-- كل الإدارات --',
        'all_statuses' => '-- كل الحالات --',
        'status_active' => 'نشط ومفعل',
        'status_inactive' => 'غير نشط',
        'general_dept' => 'عمومي / غير محدد',
        'general_grade' => 'عام',
        'print_list' => 'طباعة القائمة',
        'confirm_delete' => 'هل أنت متأكد من حذف هذا المسمى الوظيفي؟'
    ],
    'en' => [
        'title' => 'Designations & Job Titles',
        'desc' => 'Manage job title directory, pay grades, and department assignments.',
        'add_btn' => 'New Designation',
        'col_code' => 'Code',
        'col_title' => 'Job Title',
        'col_dept' => 'Department',
        'col_grade' => 'Pay Grade',
        'col_status' => 'Status',
        'col_actions' => 'Actions',
        'empty' => 'No designations found.',
        'search_ph' => 'Search by code, title, or pay grade...',
        'btn_search' => 'Filter',
        'btn_clear' => 'Clear',
        'active_scope' => 'Active Branch:',
        'kpi_total' => 'Total Designations',
        'kpi_active' => 'Active Designations',
        'kpi_depts' => 'Linked Departments',
        'kpi_grades' => 'Pay Grades Count',
        'all_depts' => '-- All Departments --',
        'all_statuses' => '-- All Statuses --',
        'status_active' => 'Active',
        'status_inactive' => 'Inactive',
        'general_dept' => 'General / Unassigned',
        'general_grade' => 'General',
        'print_list' => 'Print List',
        'confirm_delete' => 'Are you sure you want to delete this designation?'
    ]
][$isRtl ? 'ar' : 'en'];

$statusMap = [
    'active' => ['label' => $t['status_active'], 'color' => '#c026d3', 'bg' => '#fae8ff', 'icon' => 'ph-check-circle'],
    'inactive' => ['label' => $t['status_inactive'], 'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'ph-minus-circle'],
];
?>

<style>
    :root {
        --c-desig: #c026d3;
        --c-desig-dark: #a21caf;
        --c-desig-light: #fae8ff;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .desig-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .desig-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .desig-title-box { display: flex; align-items: center; gap: 16px; }
    .desig-icon { width: 48px; height: 48px; background: var(--c-desig-light); color: var(--c-desig); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(192, 38, 211, 0.15); }
    .desig-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-desig { background: linear-gradient(135deg, var(--c-desig), var(--c-desig-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(192, 38, 211, 0.25); }
    .btn-print { background: #0f172a; color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #1e293b; }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 24px; }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-desig-light); color: var(--c-desig); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .desig-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .desig-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .desig-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-desig-light); color: var(--c-desig); border-color: #f5d0fe; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-desig); color: #ffffff; border-color: var(--c-desig); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
    .branch-badge { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; border: 1px solid #cbd5e1; display: inline-block; margin-top: 4px;}

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body { background: #ffffff !important; color: #000000 !important; }
        .nt-sidebar, header, nav, footer { display: none !important; }
        .search-bar, .pagination, .btn-desig, .btn-print, .action-btn { display: none !important; }
        .desig-table th:last-child, .desig-table td:last-child { display: none !important; }
        .desig-wrapper { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; border-radius: 0 !important; }
        .desig-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .desig-table td { border: 1px solid #cbd5e1 !important; color: #000000 !important; }
        .kpi-card { border: 1px solid #000 !important; box-shadow: none !important; break-inside: avoid; }
        .badge-status { border: 1px solid #000 !important; background: transparent !important; color: #000 !important; }
    }
</style>

<div class="desig-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="desig-header">
        <div class="desig-title-box">
            <div class="desig-icon"><i class="ph-duotone ph-identification-card"></i></div>
            <div>
                <h2 class="desig-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print_list'] ?></button>
            <a href="/ERP/hr/designations/create" class="btn-desig"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-desig);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-desig); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-briefcase"></i></div><div class="kpi-info"><h4><?= $t['kpi_total'] ?></h4><p><?= number_format((int)($stats->total_designations ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fae8ff; color:#c026d3;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#c026d3;"><?= $t['kpi_active'] ?></h4><p><?= number_format((int)($stats->active_designations ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#e0f2fe; color:#0284c7;"><i class="ph-duotone ph-sitemap"></i></div><div class="kpi-info"><h4 style="color:#0284c7;"><?= $t['kpi_depts'] ?></h4><p><?= number_format((int)($stats->linked_depts ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-chart-polar"></i></div><div class="kpi-info"><h4 style="color:#d97706;"><?= $t['kpi_grades'] ?></h4><p><?= number_format((int)($stats->grades_count ?? 0)) ?></p></div></div>
    </div>

    <form action="/ERP/hr/designations" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:220px;" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="department_id" class="form-control" style="flex:1; min-width:160px;">
            <option value=""><?= $t['all_depts'] ?></option>
            <?php foreach($departments as $dept): 
                $deptName = $isRtl ? ($dept->name_ar ?? '') : ($dept->name_en ?: ($dept->name_ar ?? ''));
            ?>
                <option value="<?= $dept->id ?>" <?= ($deptFilter == $dept->id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$deptName) ?> (<?= htmlspecialchars((string)$dept->code) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value=""><?= $t['all_statuses'] ?></option>
            <option value="active" <?= ($statusFilter==='active')?'selected':'' ?>><?= $t['status_active'] ?></option>
            <option value="inactive" <?= ($statusFilter==='inactive')?'selected':'' ?>><?= $t['status_inactive'] ?></option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($deptFilter) || !empty($statusFilter)): ?>
            <a href="/ERP/hr/designations" class="btn-search" style="background:#f1f5f9; color:#475569; text-decoration:none;"><i class="ph-bold ph-x"></i> <?= $t['btn_clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="desig-table">
            <thead>
                <tr>
                    <th style="width: 12%;"><?= $t['col_code'] ?></th>
                    <th style="width: 28%;"><?= $t['col_title'] ?></th>
                    <th style="width: 22%;"><?= $t['col_dept'] ?></th>
                    <th style="width: 18%;"><?= $t['col_grade'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($designations)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($designations as $dg): 
                    $st = $statusMap[$dg->status] ?? $statusMap['active'];
                    $desigTitle = $isRtl ? ($dg->title_ar ?? '') : ($dg->title_en ?: ($dg->title_ar ?? ''));
                    $deptName   = $isRtl ? ($dg->department_name_ar ?? $t['general_dept']) : ($dg->department_name_en ?: ($dg->department_name_ar ?? $t['general_dept']));
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-desig-dark); font-size: 0.95rem;">
                            <a href="/ERP/hr/designations/<?= $dg->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars((string)$dg->code) ?></a>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars((string)$desigTitle) ?></div>
                            <?php if(!empty($dg->title_en) && $isRtl): ?>
                                <div style="font-size: 0.78rem; color: var(--c-text-muted); font-family: monospace;"><?= htmlspecialchars((string)$dg->title_en) ?></div>
                            <?php endif; ?>
                            <?php if(!empty($dg->branch_name)): ?>
                                <div class="branch-badge"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars((string)$dg->branch_name) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 700; color: #475569;">
                            <i class="ph-bold ph-sitemap" style="color:var(--c-desig);"></i> <?= htmlspecialchars((string)$deptName) ?>
                        </td>
                        <td style="font-family: monospace; font-weight: 800; color: #0284c7;">
                            <?= htmlspecialchars((string)($dg->pay_grade ?: $t['general_grade'])) ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/hr/designations/<?= $dg->id ?>" class="action-btn" title="عرض التفاصيل"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/hr/designations/<?= $dg->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/hr/designations/<?= $dg->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&department_id=<?= urlencode($deptFilter) ?>&status=<?= urlencode($statusFilter) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>