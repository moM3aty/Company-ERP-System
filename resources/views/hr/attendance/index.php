<?php
// Path: resources/views/hr/attendance/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title' => 'سجل الحضور والانصراف (Time & Attendance)',
        'desc' => 'متابعة وتوثيق حركات البصمة، الدوام، التأخيرات والغياب.',
        'add_btn' => 'تسجيل حركة بصمة جماعية',
        'col_emp' => 'اسم الموظف والكود',
        'col_date' => 'تاريخ السجل',
        'col_times' => 'الدخول / الخروج',
        'col_hours' => 'ساعات العمل',
        'col_status' => 'الحالة',
        'col_actions' => 'إجراء',
        'empty' => 'لا توجد سجلات حضور مسجلة لليوم المحدد.',
        'search_ph' => 'ابحث بكود الموظف، الاسم...',
        'btn_search' => 'فلترة وبحث',
        'btn_clear' => 'إلغاء',
        'active_scope' => 'الفرع النشط:',
        'kpi_stats_date' => 'إحصائيات ليوم:',
        'kpi_present' => 'حضور وملتزم',
        'kpi_late' => 'تأخير (مُسجل)',
        'kpi_absent' => 'حالات غياب',
        'kpi_leave' => 'في إجازة',
        'all_statuses' => '-- كل الحالات --',
        'status_present' => 'حاضر',
        'status_late' => 'متأخر',
        'status_half' => 'نصف يوم',
        'status_absent' => 'غائب',
        'status_leave' => 'في إجازة',
        'hours_lbl' => 'ساعة',
        'confirm_delete' => 'هل أنت متأكد من حذف هذا السجل؟'
    ],
    'en' => [
        'title' => 'Time & Attendance',
        'desc' => 'Track daily check-ins, check-outs, delays, and absences.',
        'add_btn' => 'Bulk Attendance Entry',
        'col_emp' => 'Employee & Code',
        'col_date' => 'Record Date',
        'col_times' => 'In / Out Times',
        'col_hours' => 'Work Hours',
        'col_status' => 'Status',
        'col_actions' => 'Actions',
        'empty' => 'No attendance records found for the selected day.',
        'search_ph' => 'Search by code or name...',
        'btn_search' => 'Filter & Search',
        'btn_clear' => 'Clear',
        'active_scope' => 'Active Branch:',
        'kpi_stats_date' => 'Statistics for:',
        'kpi_present' => 'Present on Time',
        'kpi_late' => 'Late Arrivals',
        'kpi_absent' => 'Absences',
        'kpi_leave' => 'On Leave',
        'all_statuses' => '-- All Statuses --',
        'status_present' => 'Present',
        'status_late' => 'Late',
        'status_half' => 'Half Day',
        'status_absent' => 'Absent',
        'status_leave' => 'On Leave',
        'hours_lbl' => 'Hrs',
        'confirm_delete' => 'Are you sure you want to delete this record?'
    ]
][$isRtl ? 'ar' : 'en'];

$statusMap = [
    'present'  => ['label' => $t['status_present'], 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
    'late'     => ['label' => $t['status_late'], 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-clock'],
    'half_day' => ['label' => $t['status_half'], 'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'icon' => 'ph-circle-half'],
    'absent'   => ['label' => $t['status_absent'], 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-x-circle'],
    'on_leave' => ['label' => $t['status_leave'], 'color' => '#0284c7', 'bg' => '#e0f2fe', 'icon' => 'ph-airplane-takeoff'],
];
?>

<style>
    :root {
        --c-att: #0284c7;
        --c-att-dark: #0369a1;
        --c-att-light: #e0f2fe;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .att-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .att-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .att-title-box { display: flex; align-items: center; gap: 16px; }
    .att-icon { width: 48px; height: 48px; background: var(--c-att-light); color: var(--c-att); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(2, 132, 199, 0.15); }
    .att-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-att { background: linear-gradient(135deg, var(--c-att), var(--c-att-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25); }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 24px; }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-att-light); color: var(--c-att); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .att-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .att-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .att-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-att-light); color: var(--c-att); border-color: #bae6fd; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-att); color: #ffffff; border-color: var(--c-att); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
    .branch-badge { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; border: 1px solid #cbd5e1; display: inline-block; margin-top: 4px;}

    .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { display: none !important; }
</style>

<div class="att-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="att-header">
        <div class="att-title-box">
            <div class="att-icon"><i class="ph-duotone ph-calendar-check"></i></div>
            <div>
                <h2 class="att-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/hr/attendance/log" class="btn-att"><i class="ph-bold ph-fingerprint"></i> <?= $t['add_btn'] ?></a>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-att);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-att); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <h3 style="margin:0 0 12px 0; font-size:1rem; color:var(--c-text-dark); font-weight:800;"><?= $t['kpi_stats_date'] ?> <?= htmlspecialchars($dateFilter ?: date('Y-m-d')) ?></h3>
    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= $t['kpi_present'] ?></h4><p><?= number_format($stats->present_today ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-clock"></i></div><div class="kpi-info"><h4 style="color:#d97706;"><?= $t['kpi_late'] ?></h4><p><?= number_format($stats->late_today ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-user-minus"></i></div><div class="kpi-info"><h4 style="color:#dc2626;"><?= $t['kpi_absent'] ?></h4><p><?= number_format($stats->absent_today ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#e0f2fe; color:#0284c7;"><i class="ph-duotone ph-airplane-takeoff"></i></div><div class="kpi-info"><h4 style="color:#0284c7;"><?= $t['kpi_leave'] ?></h4><p><?= number_format($stats->on_leave_today ?? 0) ?></p></div></div>
    </div>

    <form action="/ERP/hr/attendance" method="GET" class="search-bar">
        <input type="date" name="date" class="form-control" style="flex:1; min-width:140px;" value="<?= htmlspecialchars($dateFilter) ?>">
        
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value=""><?= $t['all_statuses'] ?></option>
            <option value="present" <?= ($statusFilter==='present')?'selected':'' ?>><?= $t['status_present'] ?></option>
            <option value="late" <?= ($statusFilter==='late')?'selected':'' ?>><?= $t['status_late'] ?></option>
            <option value="half_day" <?= ($statusFilter==='half_day')?'selected':'' ?>><?= $t['status_half'] ?></option>
            <option value="absent" <?= ($statusFilter==='absent')?'selected':'' ?>><?= $t['status_absent'] ?></option>
            <option value="on_leave" <?= ($statusFilter==='on_leave')?'selected':'' ?>><?= $t['status_leave'] ?></option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($statusFilter)): ?>
            <a href="/ERP/hr/attendance" class="btn-search" style="background:#f1f5f9; color:#475569; text-decoration:none;"><i class="ph-bold ph-x"></i> <?= $t['btn_clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="att-table">
            <thead>
                <tr>
                    <th style="width: 25%;"><?= $t['col_emp'] ?></th>
                    <th style="width: 15%;"><?= $t['col_date'] ?></th>
                    <th style="width: 20%; text-align: center;"><?= $t['col_times'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_hours'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($attendances)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($attendances as $a): 
                    $st = $statusMap[$a->status] ?? $statusMap['present'];
                    $empName = $isRtl ? ($a->employee_name ?? 'مجهول') : ($a->employee_name_en ?: ($a->employee_name ?? 'Unknown'));
                ?>
                    <tr>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars((string)$empName) ?></div>
                            <div style="font-size: 0.78rem; color: var(--c-att-dark); font-family:monospace; font-weight:bold;"><?= htmlspecialchars((string)$a->emp_code) ?></div>
                            <?php if(!empty($a->branch_name)): ?>
                                <div class="branch-badge"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars((string)$a->branch_name) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-family: monospace; font-weight: 700; color: #334155;">
                            <?= htmlspecialchars((string)$a->date) ?>
                        </td>
                        <td style="text-align: center; font-family: monospace;">
                            <div style="color: #059669; font-weight:bold;"><i class="ph-bold ph-sign-in"></i> <?= $a->check_in ? date('h:i A', strtotime($a->check_in)) : '--:--' ?></div>
                            <div style="color: #dc2626; font-weight:bold; margin-top:2px;"><i class="ph-bold ph-sign-out"></i> <?= $a->check_out ? date('h:i A', strtotime($a->check_out)) : '--:--' ?></div>
                        </td>
                        <td style="text-align: center; font-family: monospace; font-weight: 900; color: var(--c-att-dark); font-size: 1.05rem;">
                            <?= number_format((float)$a->work_hours, 2) ?>
                            <span style="font-size:0.7rem; color:#64748b;"><?= $t['hours_lbl'] ?></span>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/hr/attendance/<?= $a->id ?>" class="action-btn" title="عرض التفاصيل"><i class="ph-bold ph-eye"></i></a>
                            <form action="/ERP/hr/attendance/<?= $a->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
                                <button type="submit" class="action-btn delete" title="حذف السجل"><i class="ph-bold ph-trash"></i></button>
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&date=<?= urlencode($dateFilter) ?>&status=<?= urlencode($statusFilter) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
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