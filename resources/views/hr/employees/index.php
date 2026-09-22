<?php
// Path: resources/views/hr/employees/index.php

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
        'title' => 'دليل الموظفين وشؤون العاملين (Employees Directory)',
        'desc' => 'السجل الشامل لبيانات الموظفين، الوظائف، والرواتب الأساسية.',
        'add_btn' => 'إضافة موظف جديد',
        'col_code' => 'الكود',
        'col_name' => 'اسم الموظف',
        'col_dept' => 'الإدارة والمسمى الوظيفي',
        'col_join' => 'تاريخ المباشرة والتوعية',
        'col_salary' => 'الراتب الأساسي',
        'col_status' => 'الحالة',
        'col_actions' => 'إجراءات',
        'empty' => 'لا يوجد موظفين مسجلين بالمواصفات المحددة.',
        'search_ph' => 'ابحث بكود الموظف، الاسم، الهوية، الهاتف، البريد...',
        'btn_search' => 'فلترة',
        'btn_clear' => 'إلغاء',
        'active_scope' => 'الفرع النشط:',
        'kpi_total' => 'إجمالي كادر الموظفين',
        'kpi_active' => 'موظفين على رأس العمل',
        'kpi_leave' => 'موظفين في إجازة',
        'kpi_probation' => 'فترة التجربة',
        'all_depts' => '-- كل الإدارات --',
        'all_statuses' => '-- كل الحالات --',
        'status_active' => 'على رأس العمل',
        'status_on_leave' => 'في إجازة',
        'status_resigned' => 'مستقيل',
        'status_terminated' => 'منهي خدماته',
        'type_full_time' => 'دوام كامل',
        'type_part_time' => 'دوام جزئي',
        'type_contract' => 'عقد محدد',
        'type_probation' => 'تحت التجربة',
        'unassigned_dept' => 'غير محدد',
        'unassigned_title' => 'بدون مسمى',
        'no_phone' => 'لا يوجد هاتف',
        'print_list' => 'طباعة القائمة',
        'confirm_delete' => 'هل أنت متأكد من حذف هذا الموظف؟'
    ],
    'en' => [
        'title' => 'Employees Directory',
        'desc' => 'Comprehensive master record of employees, job positions, and base salaries.',
        'add_btn' => 'Add New Employee',
        'col_code' => 'Code',
        'col_name' => 'Employee Name',
        'col_dept' => 'Department & Position',
        'col_join' => 'Joining Date & Type',
        'col_salary' => 'Base Salary',
        'col_status' => 'Status',
        'col_actions' => 'Actions',
        'empty' => 'No employee records found.',
        'search_ph' => 'Search by code, name, ID, phone, email...',
        'btn_search' => 'Filter',
        'btn_clear' => 'Clear',
        'active_scope' => 'Active Branch:',
        'kpi_total' => 'Total Workforce',
        'kpi_active' => 'Active Staff',
        'kpi_leave' => 'On Leave Staff',
        'kpi_probation' => 'Probation Period',
        'all_depts' => '-- All Departments --',
        'all_statuses' => '-- All Statuses --',
        'status_active' => 'Active',
        'status_on_leave' => 'On Leave',
        'status_resigned' => 'Resigned',
        'status_terminated' => 'Terminated',
        'type_full_time' => 'Full-Time',
        'type_part_time' => 'Part-Time',
        'type_contract' => 'Contract',
        'type_probation' => 'Probation',
        'unassigned_dept' => 'Unassigned',
        'unassigned_title' => 'No Title',
        'no_phone' => 'No Phone',
        'print_list' => 'Print List',
        'confirm_delete' => 'Are you sure you want to delete this employee?'
    ]
][$isRtl ? 'ar' : 'en'];

$statusMap = [
    'active'     => ['label' => $t['status_active'], 'color' => '#2563eb', 'bg' => '#dbeafe', 'icon' => 'ph-check-circle'],
    'on_leave'   => ['label' => $t['status_on_leave'], 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-airplane-takeoff'],
    'resigned'   => ['label' => $t['status_resigned'], 'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'ph-user-minus'],
    'terminated' => ['label' => $t['status_terminated'], 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-x-circle'],
];

$typeMap = [
    'full_time' => $t['type_full_time'],
    'part_time' => $t['type_part_time'],
    'contract'  => $t['type_contract'],
    'probation' => $t['type_probation']
];
?>

<style>
    :root {
        --c-emp: #2563eb;
        --c-emp-dark: #1d4ed8;
        --c-emp-light: #dbeafe;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .emp-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .emp-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .emp-title-box { display: flex; align-items: center; gap: 16px; }
    .emp-icon { width: 48px; height: 48px; background: var(--c-emp-light); color: var(--c-emp); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.15); }
    .emp-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-emp { background: linear-gradient(135deg, var(--c-emp), var(--c-emp-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); }
    .btn-print { background: #0f172a; color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #1e293b; }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 24px; }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-emp-light); color: var(--c-emp); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .emp-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .emp-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .emp-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-emp-light); color: var(--c-emp); border-color: #bfdbfe; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-emp); color: #ffffff; border-color: var(--c-emp); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
    .branch-badge { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; border: 1px solid #cbd5e1; display: inline-block; margin-top: 4px;}

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body { background: #ffffff !important; color: #000000 !important; }
        .nt-sidebar, header, nav, footer { display: none !important; }
        .search-bar, .pagination, .btn-emp, .btn-print, .action-btn { display: none !important; }
        .emp-table th:last-child, .emp-table td:last-child { display: none !important; }
        .emp-wrapper { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; border-radius: 0 !important; }
        .emp-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .emp-table td { border: 1px solid #cbd5e1 !important; color: #000000 !important; }
        .kpi-card { border: 1px solid #000 !important; box-shadow: none !important; break-inside: avoid; }
        .badge-status { border: 1px solid #000 !important; background: transparent !important; color: #000 !important; }
    }
</style>

<div class="emp-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="emp-header">
        <div class="emp-title-box">
            <div class="emp-icon"><i class="ph-duotone ph-users-three"></i></div>
            <div>
                <h2 class="emp-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print_list'] ?></button>
            <a href="/ERP/hr/employees/create" class="btn-emp"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-emp);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-emp); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-users"></i></div><div class="kpi-info"><h4><?= $t['kpi_total'] ?></h4><p><?= number_format((int)($stats->total_emps ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#dbeafe; color:#2563eb;"><i class="ph-duotone ph-user-check"></i></div><div class="kpi-info"><h4 style="color:#2563eb;"><?= $t['kpi_active'] ?></h4><p><?= number_format((int)($stats->active_emps ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-airplane-takeoff"></i></div><div class="kpi-info"><h4 style="color:#d97706;"><?= $t['kpi_leave'] ?></h4><p><?= number_format((int)($stats->on_leave_emps ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fae8ff; color:#c026d3;"><i class="ph-duotone ph-clock"></i></div><div class="kpi-info"><h4 style="color:#c026d3;"><?= $t['kpi_probation'] ?></h4><p><?= number_format((int)($stats->probation_emps ?? 0)) ?></p></div></div>
    </div>

    <form action="/ERP/hr/employees" method="GET" class="search-bar">
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
            <option value="on_leave" <?= ($statusFilter==='on_leave')?'selected':'' ?>><?= $t['status_on_leave'] ?></option>
            <option value="resigned" <?= ($statusFilter==='resigned')?'selected':'' ?>><?= $t['status_resigned'] ?></option>
            <option value="terminated" <?= ($statusFilter==='terminated')?'selected':'' ?>><?= $t['status_terminated'] ?></option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($deptFilter) || !empty($statusFilter)): ?>
            <a href="/ERP/hr/employees" class="btn-search" style="background:#f1f5f9; color:#475569; text-decoration:none;"><i class="ph-bold ph-x"></i> <?= $t['btn_clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="emp-table">
            <thead>
                <tr>
                    <th style="width: 10%;"><?= $t['col_code'] ?></th>
                    <th style="width: 24%;"><?= $t['col_name'] ?></th>
                    <th style="width: 20%;"><?= $t['col_dept'] ?></th>
                    <th style="width: 16%;"><?= $t['col_join'] ?></th>
                    <th style="width: 14%;"><?= $t['col_salary'] ?></th>
                    <th style="width: 8%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 8%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($employees as $e): 
                    $st = $statusMap[$e->status] ?? $statusMap['active'];
                    $empName   = $isRtl ? ($e->name_ar ?? '') : ($e->name_en ?: ($e->name_ar ?? ''));
                    $deptName  = $isRtl ? ($e->department_name_ar ?? $t['unassigned_dept']) : ($e->department_name_en ?: ($e->department_name_ar ?? $t['unassigned_dept']));
                    $desigTitle = $isRtl ? ($e->designation_title_ar ?? $t['unassigned_title']) : ($e->designation_title_en ?: ($e->designation_title_ar ?? $t['unassigned_title']));
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-emp-dark); font-size: 0.95rem;">
                            <a href="/ERP/hr/employees/<?= $e->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars((string)$e->emp_code) ?></a>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars((string)$empName) ?></div>
                            <div style="font-size: 0.78rem; color: var(--c-text-muted);"><i class="ph-bold ph-phone"></i> <?= htmlspecialchars((string)($e->phone ?: $t['no_phone'])) ?></div>
                            <?php if(!empty($e->branch_name)): ?>
                                <div class="branch-badge"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars((string)$e->branch_name) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><i class="ph-bold ph-sitemap" style="color:var(--c-emp);"></i> <?= htmlspecialchars((string)$deptName) ?></div>
                            <div style="font-size: 0.78rem; color: var(--c-text-muted);"><?= htmlspecialchars((string)$desigTitle) ?></div>
                        </td>
                        <td style="font-family: monospace; font-size: 0.85rem; font-weight: 700;">
                            <div><?= htmlspecialchars((string)$e->joining_date) ?></div>
                            <div style="font-size: 0.75rem; color: #0284c7;"><?= $typeMap[$e->employment_type] ?? $t['type_full_time'] ?></div>
                        </td>
                        <td style="font-family: monospace; font-weight: 900; color: #059669; font-size: 0.98rem;">
                            <?= number_format($convert($e->basic_salary ?? 0), 2) ?> <span style="font-size:0.75rem; color:#64748b;"><?= $currency ?></span>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/hr/employees/<?= $e->id ?>" class="action-btn" title="عرض البطاقة"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/hr/employees/<?= $e->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/hr/employees/<?= $e->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
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