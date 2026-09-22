<?php
// Path: resources/views/hr/departments/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title' => 'الهيكل التنظيمي والإدارات (Departments & Org Structure)',
        'desc' => 'إدارة وتنسيق الإدارات الرئيسية والفرعية ومدراء الأقسام.',
        'add_btn' => 'إضافة إدارة / قسم جديد',
        'col_code' => 'الكود',
        'col_name' => 'اسم الإدارة / القطاع',
        'col_parent' => 'الإدارة التابعة لها',
        'col_manager' => 'مدير الإدارة',
        'col_status' => 'الحالة',
        'col_actions' => 'إجراءات',
        'empty' => 'لا توجد إدارات أو أقسام مسجلة.',
        'search_ph' => 'ابحث بكود الإدارة، الاسم بالعربي/الإنجليزي، أو اسم المدير...',
        'btn_search' => 'فلترة',
        'btn_clear' => 'إلغاء',
        'active_scope' => 'الفرع النشط:',
        'kpi_total' => 'إجمالي الإدارات والقطاعات',
        'kpi_active' => 'إدارات مفعلة',
        'kpi_parent' => 'قطاعات رئيسية',
        'kpi_sub' => 'أقسام فرعية',
        'all_statuses' => '-- كل الحالات --',
        'status_active' => 'نشط ومفعل',
        'status_inactive' => 'غير نشط',
        'parent_root' => 'إدارة رئيسية مستقلة',
        'unassigned' => 'غير عين',
        'print_list' => 'طباعة القائمة',
        'confirm_delete' => 'هل أنت متأكد من حذف هذه الإدارة؟'
    ],
    'en' => [
        'title' => 'Departments & Org Structure',
        'desc' => 'Manage main divisions, sub-departments, and department heads.',
        'add_btn' => 'New Department / Division',
        'col_code' => 'Code',
        'col_name' => 'Department Name',
        'col_parent' => 'Parent Division',
        'col_manager' => 'Department Manager',
        'col_status' => 'Status',
        'col_actions' => 'Actions',
        'empty' => 'No departments found.',
        'search_ph' => 'Search by code, name, or manager...',
        'btn_search' => 'Filter',
        'btn_clear' => 'Clear',
        'active_scope' => 'Active Branch:',
        'kpi_total' => 'Total Departments',
        'kpi_active' => 'Active Departments',
        'kpi_parent' => 'Main Divisions',
        'kpi_sub' => 'Sub-Departments',
        'all_statuses' => '-- All Statuses --',
        'status_active' => 'Active',
        'status_inactive' => 'Inactive',
        'parent_root' => 'Independent Main Division',
        'unassigned' => 'Unassigned',
        'print_list' => 'Print List',
        'confirm_delete' => 'Are you sure you want to delete this department?'
    ]
][$isRtl ? 'ar' : 'en'];

$statusMap = [
    'active' => ['label' => $t['status_active'], 'color' => '#0d9488', 'bg' => '#ccfbf1', 'icon' => 'ph-check-circle'],
    'inactive' => ['label' => $t['status_inactive'], 'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'ph-minus-circle'],
];
?>

<style>
    :root {
        --c-dept: #0d9488;
        --c-dept-dark: #0f766e;
        --c-dept-light: #ccfbf1;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .dept-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .dept-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .dept-title-box { display: flex; align-items: center; gap: 16px; }
    .dept-icon { width: 48px; height: 48px; background: var(--c-dept-light); color: var(--c-dept); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(13, 148, 136, 0.15); }
    .dept-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-dept { background: linear-gradient(135deg, var(--c-dept), var(--c-dept-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25); }
    .btn-print { background: #0f172a; color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #1e293b; }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 24px; }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-dept-light); color: var(--c-dept); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .dept-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .dept-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .dept-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-dept-light); color: var(--c-dept); border-color: #99f6e4; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-dept); color: #ffffff; border-color: var(--c-dept); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
    .branch-badge { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; border: 1px solid #cbd5e1; display: inline-block; margin-top: 4px;}

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body { background: #ffffff !important; color: #000000 !important; }
        .nt-sidebar, header, nav, footer { display: none !important; }
        .search-bar, .pagination, .btn-dept, .btn-print, .action-btn { display: none !important; }
        .dept-table th:last-child, .dept-table td:last-child { display: none !important; }
        .dept-wrapper { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; border-radius: 0 !important; }
        .dept-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .dept-table td { border: 1px solid #cbd5e1 !important; color: #000000 !important; }
        .kpi-card { border: 1px solid #000 !important; box-shadow: none !important; break-inside: avoid; }
        .badge-status { border: 1px solid #000 !important; background: transparent !important; color: #000 !important; }
    }
</style>

<div class="dept-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="dept-header">
        <div class="dept-title-box">
            <div class="dept-icon"><i class="ph-duotone ph-sitemap"></i></div>
            <div>
                <h2 class="dept-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print_list'] ?></button>
            <a href="/ERP/hr/departments/create" class="btn-dept"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-dept);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-dept); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-buildings"></i></div><div class="kpi-info"><h4><?= $t['kpi_total'] ?></h4><p><?= number_format((int)($stats->total_depts ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ccfbf1; color:#0d9488;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#0d9488;"><?= $t['kpi_active'] ?></h4><p><?= number_format((int)($stats->active_depts ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#e0f2fe; color:#0284c7;"><i class="ph-duotone ph-tree-structure"></i></div><div class="kpi-info"><h4 style="color:#0284c7;"><?= $t['kpi_parent'] ?></h4><p><?= number_format((int)($stats->parent_depts ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#f5f3ff; color:#8b5cf6;"><i class="ph-duotone ph-diagram"></i></div><div class="kpi-info"><h4 style="color:#8b5cf6;"><?= $t['kpi_sub'] ?></h4><p><?= number_format((int)($stats->sub_depts ?? 0)) ?></p></div></div>
    </div>

    <form action="/ERP/hr/departments" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:220px;" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value=""><?= $t['all_statuses'] ?></option>
            <option value="active" <?= ($statusFilter==='active')?'selected':'' ?>><?= $t['status_active'] ?></option>
            <option value="inactive" <?= ($statusFilter==='inactive')?'selected':'' ?>><?= $t['status_inactive'] ?></option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($statusFilter)): ?>
            <a href="/ERP/hr/departments" class="btn-search" style="background:#f1f5f9; color:#475569; text-decoration:none;"><i class="ph-bold ph-x"></i> <?= $t['btn_clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="dept-table">
            <thead>
                <tr>
                    <th style="width: 12%;"><?= $t['col_code'] ?></th>
                    <th style="width: 28%;"><?= $t['col_name'] ?></th>
                    <th style="width: 20%;"><?= $t['col_parent'] ?></th>
                    <th style="width: 20%;"><?= $t['col_manager'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($departments)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($departments as $d): 
                    $st = $statusMap[$d->status] ?? $statusMap['active'];
                    $deptName = $isRtl ? ($d->name_ar ?? '') : ($d->name_en ?: ($d->name_ar ?? ''));
                    $parentName = $isRtl ? ($d->parent_name_ar ?? $t['parent_root']) : ($d->parent_name_en ?: ($d->parent_name_ar ?? $t['parent_root']));
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-dept-dark); font-size: 0.95rem;">
                            <a href="/ERP/hr/departments/<?= $d->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars((string)$d->code) ?></a>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars((string)$deptName) ?></div>
                            <?php if(!empty($d->name_en) && $isRtl): ?>
                                <div style="font-size: 0.78rem; color: var(--c-text-muted); font-family: monospace;"><?= htmlspecialchars((string)$d->name_en) ?></div>
                            <?php endif; ?>
                            <?php if(!empty($d->branch_name)): ?>
                                <div class="branch-badge"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars((string)$d->branch_name) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 700; color: #475569;">
                            <?= htmlspecialchars((string)$parentName) ?>
                        </td>
                        <td style="font-weight: 700; color: #334155;">
                            <i class="ph-bold ph-user-tier" style="color:var(--c-dept);"></i> <?= htmlspecialchars((string)($d->manager_name ?: $t['unassigned'])) ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/hr/departments/<?= $d->id ?>" class="action-btn" title="عرض التفاصيل"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/hr/departments/<?= $d->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/hr/departments/<?= $d->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
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