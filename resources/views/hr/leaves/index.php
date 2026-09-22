<?php
// Path: resources/views/hr/leaves/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title' => 'طلبات ومستحقات الإجازات (Leaves & Requests)',
        'desc' => 'إدارة وتتبع طلبات إجازات الموظفين واعتمادها.',
        'add_btn' => 'تقديم طلب إجازة جديد',
        'col_emp' => 'اسم الموظف والإدارة',
        'col_type' => 'نوع الإجازة',
        'col_period' => 'فترة الإجازة',
        'col_days' => 'عدد الأيام',
        'col_status' => 'الحالة',
        'col_actions' => 'إجراءات',
        'empty' => 'لا توجد طلبات إجازة مسجلة.',
        'search_ph' => 'ابحث بكود الموظف، أو اسمه...',
        'btn_search' => 'فلترة',
        'btn_clear' => 'إلغاء',
        'active_scope' => 'الفرع النشط:',
        'kpi_total' => 'إجمالي الطلبات',
        'kpi_pending' => 'قيد المراجعة',
        'kpi_approved' => 'طلبات مقبولة',
        'kpi_rejected' => 'طلبات مرفوضة',
        'all_types' => '-- كل أنواع الإجازات --',
        'all_statuses' => '-- كل الحالات --',
        'status_pending' => 'قيد المراجعة',
        'status_approved' => 'مقبولة ومُعتمدة',
        'status_rejected' => 'مرفوضة',
        'type_annual' => 'إجازة سنوية',
        'type_sick' => 'إجازة مرضية',
        'type_unpaid' => 'بدون راتب',
        'type_maternity' => 'إجازة وضع/أمومة',
        'type_other' => 'إجازة أخرى',
        'from_lbl' => 'من:',
        'to_lbl' => 'إلى:',
        'days_unit' => 'يوم',
        'unknown_emp' => 'مجهول',
        'general_dept' => 'عام',
        'print_list' => 'طباعة القائمة',
        'confirm_approve' => 'هل تريد قبول وإقرار طلب الإجازة؟',
        'confirm_reject' => 'هل تريد رفض طلب الإجازة؟',
        'confirm_delete' => 'هل أنت متأكد من حذف هذا الطلب؟'
    ],
    'en' => [
        'title' => 'Leaves & Requests',
        'desc' => 'Manage, track, and approve employee leave requests.',
        'add_btn' => 'New Leave Request',
        'col_emp' => 'Employee & Department',
        'col_type' => 'Leave Type',
        'col_period' => 'Leave Period',
        'col_days' => 'Days Count',
        'col_status' => 'Status',
        'col_actions' => 'Actions',
        'empty' => 'No leave requests recorded.',
        'search_ph' => 'Search by code or employee name...',
        'btn_search' => 'Filter',
        'btn_clear' => 'Clear',
        'active_scope' => 'Active Branch:',
        'kpi_total' => 'Total Requests',
        'kpi_pending' => 'Pending Approval',
        'kpi_approved' => 'Approved Requests',
        'kpi_rejected' => 'Rejected Requests',
        'all_types' => '-- All Leave Types --',
        'all_statuses' => '-- All Statuses --',
        'status_pending' => 'Pending Review',
        'status_approved' => 'Approved',
        'status_rejected' => 'Rejected',
        'type_annual' => 'Annual Leave',
        'type_sick' => 'Sick Leave',
        'type_unpaid' => 'Unpaid Leave',
        'type_maternity' => 'Maternity Leave',
        'type_other' => 'Other Leave',
        'from_lbl' => 'From:',
        'to_lbl' => 'To:',
        'days_unit' => 'Days',
        'unknown_emp' => 'Unknown',
        'general_dept' => 'General',
        'print_list' => 'Print List',
        'confirm_approve' => 'Are you sure you want to approve this leave request?',
        'confirm_reject' => 'Are you sure you want to reject this leave request?',
        'confirm_delete' => 'Are you sure you want to delete this request?'
    ]
][$isRtl ? 'ar' : 'en'];

$statusMap = [
    'pending'  => ['label' => $t['status_pending'], 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-clock-countdown'],
    'approved' => ['label' => $t['status_approved'], 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
    'rejected' => ['label' => $t['status_rejected'], 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-x-circle'],
];

$typeMap = [
    'annual'    => $t['type_annual'],
    'sick'      => $t['type_sick'],
    'unpaid'    => $t['type_unpaid'],
    'maternity' => $t['type_maternity'],
    'other'     => $t['type_other']
];
?>

<style>
    :root {
        --c-leave: #7c3aed;
        --c-leave-dark: #6d28d9;
        --c-leave-light: #f3e8ff;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .leave-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .leave-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .leave-title-box { display: flex; align-items: center; gap: 16px; }
    .leave-icon { width: 48px; height: 48px; background: var(--c-leave-light); color: var(--c-leave); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(124, 58, 237, 0.15); }
    .leave-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-leave { background: linear-gradient(135deg, var(--c-leave), var(--c-leave-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25); }
    .btn-print { background: #0f172a; color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #1e293b; }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 24px; }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-leave-light); color: var(--c-leave); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .leave-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .leave-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .leave-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-leave-light); color: var(--c-leave); border-color: #ddd6fe; }
    .action-btn.approve:hover { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
    .action-btn.reject:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-leave); color: #ffffff; border-color: var(--c-leave); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
    .branch-badge { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; border: 1px solid #cbd5e1; display: inline-block; margin-top: 4px;}

    /* إخفاء DataTables Injection */
    .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { display: none !important; }

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body { background: #ffffff !important; color: #000000 !important; }
        .nt-sidebar, header, nav, footer { display: none !important; }
        .search-bar, .pagination, .btn-leave, .btn-print, .action-btn { display: none !important; }
        .leave-table th:last-child, .leave-table td:last-child { display: none !important; }
        .leave-wrapper { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; border-radius: 0 !important; }
        .leave-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .leave-table td { border: 1px solid #cbd5e1 !important; color: #000000 !important; }
        .kpi-card { border: 1px solid #000 !important; box-shadow: none !important; break-inside: avoid; }
        .badge-status { border: 1px solid #000 !important; background: transparent !important; color: #000 !important; }
    }
</style>

<div class="leave-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="leave-header">
        <div class="leave-title-box">
            <div class="leave-icon"><i class="ph-duotone ph-airplane-takeoff"></i></div>
            <div>
                <h2 class="leave-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print_list'] ?></button>
            <a href="/ERP/hr/leaves/create" class="btn-leave"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-leave);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-leave); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-files"></i></div><div class="kpi-info"><h4><?= $t['kpi_total'] ?></h4><p><?= number_format((int)($stats->total_leaves ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-clock-countdown"></i></div><div class="kpi-info"><h4 style="color:#d97706;"><?= $t['kpi_pending'] ?></h4><p><?= number_format((int)($stats->pending_leaves ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= $t['kpi_approved'] ?></h4><p><?= number_format((int)($stats->approved_leaves ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-x-circle"></i></div><div class="kpi-info"><h4 style="color:#dc2626;"><?= $t['kpi_rejected'] ?></h4><p><?= number_format((int)($stats->rejected_leaves ?? 0)) ?></p></div></div>
    </div>

    <form action="/ERP/hr/leaves" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="leave_type" class="form-control" style="flex:1; min-width:140px;">
            <option value=""><?= $t['all_types'] ?></option>
            <option value="annual" <?= ($typeFilter==='annual')?'selected':'' ?>><?= $t['type_annual'] ?></option>
            <option value="sick" <?= ($typeFilter==='sick')?'selected':'' ?>><?= $t['type_sick'] ?></option>
            <option value="unpaid" <?= ($typeFilter==='unpaid')?'selected':'' ?>><?= $t['type_unpaid'] ?></option>
            <option value="maternity" <?= ($typeFilter==='maternity')?'selected':'' ?>><?= $t['type_maternity'] ?></option>
            <option value="other" <?= ($typeFilter==='other')?'selected':'' ?>><?= $t['type_other'] ?></option>
        </select>

        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value=""><?= $t['all_statuses'] ?></option>
            <option value="pending" <?= ($statusFilter==='pending')?'selected':'' ?>><?= $t['status_pending'] ?></option>
            <option value="approved" <?= ($statusFilter==='approved')?'selected':'' ?>><?= $t['status_approved'] ?></option>
            <option value="rejected" <?= ($statusFilter==='rejected')?'selected':'' ?>><?= $t['status_rejected'] ?></option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($typeFilter) || !empty($statusFilter)): ?>
            <a href="/ERP/hr/leaves" class="btn-search" style="background:#f1f5f9; color:#475569; text-decoration:none;"><i class="ph-bold ph-x"></i> <?= $t['btn_clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="leave-table">
            <thead>
                <tr>
                    <th style="width: 25%;"><?= $t['col_emp'] ?></th>
                    <th style="width: 15%;"><?= $t['col_type'] ?></th>
                    <th style="width: 20%;"><?= $t['col_period'] ?></th>
                    <th style="width: 12%; text-align: center;"><?= $t['col_days'] ?></th>
                    <th style="width: 12%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 16%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leaves)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($leaves as $l): 
                    $st = $statusMap[$l->status] ?? $statusMap['pending'];
                    $empName  = $isRtl ? ($l->employee_name ?? $t['unknown_emp']) : ($l->employee_name_en ?: ($l->employee_name ?? $t['unknown_emp']));
                    $deptName = $isRtl ? ($l->dept_name ?? $t['general_dept']) : ($l->dept_name_en ?: ($l->dept_name ?? $t['general_dept']));
                ?>
                    <tr>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars((string)$empName) ?></div>
                            <div style="font-size: 0.78rem; color: var(--c-leave-dark); font-family:monospace; font-weight:bold;"><?= htmlspecialchars((string)$l->emp_code) ?> - <?= htmlspecialchars((string)$deptName) ?></div>
                            <?php if(!empty($l->branch_name)): ?>
                                <div class="branch-badge"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars((string)$l->branch_name) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 800; color: #475569;">
                            <span style="background:#f1f5f9; padding:4px 8px; border-radius:6px; font-size:0.8rem;"><?= $typeMap[$l->leave_type] ?? $l->leave_type ?></span>
                        </td>
                        <td style="font-family: monospace; font-size: 0.85rem; font-weight: 700;">
                            <div><span style="color:#64748b;"><?= $t['from_lbl'] ?></span> <?= htmlspecialchars((string)$l->start_date) ?></div>
                            <div><span style="color:#64748b;"><?= $t['to_lbl'] ?></span> <?= htmlspecialchars((string)$l->end_date) ?></div>
                        </td>
                        <td style="text-align: center; font-family: monospace; font-weight: 900; color: var(--c-leave-dark); font-size: 1.05rem;">
                            <?= (int)$l->days_count ?> <?= $t['days_unit'] ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/hr/leaves/<?= $l->id ?>" class="action-btn" title="عرض التفاصيل"><i class="ph-bold ph-eye"></i></a>
                            
                            <?php if($l->status === 'pending'): ?>
                                <form action="/ERP/hr/leaves/<?= $l->id ?>/approve" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_approve'] ?>');">
                                    <button type="submit" class="action-btn approve" title="قبول"><i class="ph-bold ph-check"></i></button>
                                </form>
                                <form action="/ERP/hr/leaves/<?= $l->id ?>/reject" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_reject'] ?>');">
                                    <button type="submit" class="action-btn reject" title="رفض"><i class="ph-bold ph-x"></i></button>
                                </form>
                            <?php endif; ?>

                            <form action="/ERP/hr/leaves/<?= $l->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
                                <button type="submit" class="action-btn reject" title="حذف"><i class="ph-bold ph-trash"></i></button>
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&leave_type=<?= urlencode($typeFilter) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
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