<?php
// Path: resources/views/hr/dashboard/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$kpis = $kpis ?? [
    'total_employees' => 0, 'present_today' => 0, 'on_leave_today' => 0, 'absent_today' => 0,
    'latest_payroll_net' => 0, 'contracts_expiring' => 0, 'docs_expiring' => 0,
    'active_applicants' => 0, 'avg_appraisal' => 0
];

$t = [
    'ar' => [
        'title' => 'اللوحة التنفيذية للموارد البشرية',
        'desc' => 'مراقبة شاملة لكادر العمل، الحضور، المسيرات والالتزامات الإدارية.',
        'active_scope' => 'الفرع النشط:', 'export' => 'تصدير التقرير الشامل (Excel)',
        'tot_emp' => 'إجمالي الكادر النشط', 'pres_today' => 'حضور اليوم',
        'leave_today' => 'إجازات اليوم', 'pay_net' => 'إجمالي آخر مسير رواتب',
        'c_dept' => 'توزيع الأقسام الإدارية', 'c_att' => 'حالات الحضور والغياب اليوم',
        'c_rec' => 'مراحل التوظيف والمتقدمين', 'c_appr' => 'درجات تقييم الأداء',
        'c_cont' => 'حالات عقود الموظفين', 'c_leave' => 'تصنيف طلبات الإجازات',
        'hub' => 'الوصول السريع للموديولات', 'alerts' => 'تنبيهات الاستحقاق والانتهاء العاجلة (30 يوماً)',
        'no_alerts' => 'لا توجد تنبيهات انتهاء خلال الـ 30 يوماً القادمة.',
        'col_type' => 'النوع', 'col_code' => 'كود/رقم', 'col_emp' => 'الموظف', 'col_exp' => 'تاريخ الانتهاء',
        'mod_emp' => 'الموظفين', 'mod_dept' => 'الإدارات', 'mod_desig' => 'المسميات', 'mod_cont' => 'العقود',
        'mod_att' => 'الحضور', 'mod_shift' => 'الورديات', 'mod_leave' => 'الإجازات', 'mod_pay' => 'الرواتب',
        'mod_comp' => 'المفردات', 'mod_appr' => 'التقييمات', 'mod_rec' => 'التوظيف', 'mod_doc' => 'الوثائق'
    ],
    'en' => [
        'title' => 'HR Executive Dashboard',
        'desc' => 'Comprehensive tracking of workforce, attendance, payroll, and compliance.',
        'active_scope' => 'Active Branch:', 'export' => 'Export Full Report (Excel)',
        'tot_emp' => 'Active Workforce', 'pres_today' => 'Present Today',
        'leave_today' => 'On Leave Today', 'pay_net' => 'Latest Payroll Net',
        'c_dept' => 'Departments Distribution', 'c_att' => 'Today\'s Attendance Status',
        'c_rec' => 'Recruitment Pipeline', 'c_appr' => 'Performance Appraisals',
        'c_cont' => 'Contract Statuses', 'c_leave' => 'Leave Requests Breakdown',
        'hub' => 'Quick Modules Hub', 'alerts' => 'Urgent Expiry Alerts (Next 30 Days)',
        'no_alerts' => 'No expiring items in the next 30 days.',
        'col_type' => 'Type', 'col_code' => 'Code/No.', 'col_emp' => 'Employee', 'col_exp' => 'Expiry Date',
        'mod_emp' => 'Employees', 'mod_dept' => 'Departments', 'mod_desig' => 'Designations', 'mod_cont' => 'Contracts',
        'mod_att' => 'Attendance', 'mod_shift' => 'Shifts', 'mod_leave' => 'Leaves', 'mod_pay' => 'Payroll',
        'mod_comp' => 'Components', 'mod_appr' => 'Appraisals', 'mod_rec' => 'Recruitment', 'mod_doc' => 'Documents'
    ]
][$isRtl ? 'ar' : 'en'];

// ترجمة أسماء الحالات داخل الـ JS
$attLabelsJson = json_encode($isRtl ? ['حاضر/متأخر', 'غائب', 'إجازة'] : ['Present/Late', 'Absent', 'On Leave']);
$recLabelsJson = json_encode($isRtl ? ['جديد', 'مقابلة', 'عرض', 'توظيف', 'مرفوض'] : ['Applied', 'Interview', 'Offered', 'Hired', 'Rejected']);
$appLabelsJson = json_encode($isRtl ? ['ممتاز', 'جيد جداً', 'جيد', 'مقبول', 'تحسين'] : ['Excellent', 'Very Good', 'Good', 'Acceptable', 'Needs Imp']);
$contLabelsJson = json_encode($isRtl ? ['ساري', 'منتهي', 'مفسوخ'] : ['Active', 'Expired', 'Terminated']);
$levLabelsJson = json_encode($isRtl ? ['سنوية', 'مرضية', 'بدون راتب', 'وضع', 'أخرى'] : ['Annual', 'Sick', 'Unpaid', 'Maternity', 'Other']);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<style>
    :root {
        --surface-bg: #f8fafc;
        --card-bg: #ffffff;
        --border-color: #e2e8f0;
        --text-heading: #0f172a;
        --text-body: #334155;
        --text-sub: #64748b;

        --hr-primary: #0284c7;  --hr-primary-bg: #e0f2fe;
        --hr-success: #10b981;  --hr-success-bg: #d1fae5;
        --hr-warning: #f59e0b;  --hr-warning-bg: #fef3c7;
        --hr-danger: #ef4444;   --hr-danger-bg: #fee2e2;
        --hr-purple: #8b5cf6;   --hr-purple-bg: #ede9fe;
        --hr-pink: #ec4899;     --hr-pink-bg: #fce7f3;
    }

    body.dark-mode, [data-theme="dark"] {
        --surface-bg: #0b0f19;
        --card-bg: #151e2e;
        --border-color: #27354a;
        --text-heading: #f8fafc;
        --text-body: #cbd5e1;
        --text-sub: #8192a6;
    }

    .hr-dash-wrapper { font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; padding-bottom: 60px; background: var(--surface-bg); min-height: 100vh; color: var(--text-body); }
    
    .glass-header {
        position: sticky; top: 0; z-index: 40;
        background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px);
        border-bottom: 1px solid var(--border-color);
        padding: 16px 28px; margin-bottom: 24px;
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;
    }
    body.dark-mode .glass-header { background: rgba(21, 30, 46, 0.85); }

    .header-title-box { display: flex; align-items: center; gap: 14px; }
    .header-icon { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, var(--hr-primary), #0369a1); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; box-shadow: 0 8px 18px rgba(2, 132, 199, 0.25); }

    .btn-export { background: #059669; color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; transition: 0.2s; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2); }
    .btn-export:hover { transform: translateY(-2px); background: #047857; }

    .branch-pill { display: inline-flex; align-items: center; gap: 8px; background: var(--card-bg); border: 1px solid var(--border-color); padding: 6px 16px; border-radius: 20px; font-size: 0.82rem; font-weight: 800; color: var(--text-heading); margin: 0 28px 24px 28px; }

    .bento-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px; margin: 0 28px 24px 28px; }
    .bento-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 20px; padding: 22px; transition: 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.02); display: flex; flex-direction: column; min-width: 0 !important; overflow: hidden !important; }
    .bento-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); border-color: var(--hr-primary); }

    .col-3 { grid-column: span 3; } .col-4 { grid-column: span 4; } .col-8 { grid-column: span 8; }
    @media(max-width: 1100px) { .col-3, .col-4 { grid-column: span 6; } .col-8 { grid-column: span 12; } }
    @media(max-width: 768px)  { .col-3, .col-4, .col-8 { grid-column: span 12; } .bento-grid { margin: 0 14px 24px 14px; gap: 14px;} }

    .kpi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
    .kpi-title { font-size: 0.8rem; font-weight: 800; color: var(--text-sub); text-transform: uppercase; }
    .kpi-icon-sm { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
    .kpi-val { font-size: 1.6rem; font-weight: 900; font-family: monospace; color: var(--text-heading); }

    .chart-title { margin: 0 0 16px 0; font-size: 1rem; font-weight: 900; color: var(--text-heading); display: flex; align-items: center; gap: 8px; }

    .hub-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; }
    .hub-item { background: var(--surface-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; text-decoration: none; color: var(--text-heading); font-weight: 800; font-size: 0.85rem; display: flex; align-items: center; gap: 10px; transition: 0.2s; }
    .hub-item:hover { background: var(--hr-primary-bg); border-color: var(--hr-primary); transform: translateY(-2px); }
    .hub-item i { font-size: 1.3rem; }

    .clean-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .clean-table th { padding: 10px 12px; background: var(--surface-bg); color: var(--text-sub); font-weight: 800; border-bottom: 2px solid var(--border-color); text-align: start; }
    .clean-table td { padding: 10px 12px; border-bottom: 1px solid var(--border-color); color: var(--text-heading); font-weight: 700; vertical-align: middle; }

    /* Purge DataTables inside Bento */
    .bento-card .dataTables_wrapper, .bento-card .dataTables_filter, .bento-card .dataTables_length, .bento-card .dataTables_info, .bento-card .dataTables_paginate { display: none !important; width: 0 !important; height: 0 !important; opacity: 0 !important; position: absolute !important; pointer-events: none !important; margin: 0 !important; padding: 0 !important; }
</style>

<div class="hr-dash-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <div class="glass-header">
        <div class="header-title-box">
            <div class="header-icon"><i class="ph-duotone ph-users-three"></i></div>
            <div>
                <h1 style="margin:0; font-size:1.6rem; font-weight:900; color:var(--text-heading);"><?= $t['title'] ?></h1>
                <p style="margin:3px 0 0 0; color:var(--text-sub); font-size:0.9rem; font-weight:600;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <button onclick="exportHRDashboardToExcel()" class="btn-export">
            <i class="ph-bold ph-file-xls"></i> <?= $t['export'] ?>
        </button>
    </div>

    <div class="branch-pill">
        <i class="ph-bold ph-storefront" style="color:var(--hr-primary);"></i>
        <span><?= $t['active_scope'] ?></span>
        <span style="color:var(--hr-primary); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
    </div>

    <!-- KPIs Row -->
    <div class="bento-grid">
        <div class="bento-card col-3" style="border-top: 4px solid var(--hr-primary);">
            <div class="kpi-header">
                <div class="kpi-title"><?= $t['tot_emp'] ?></div>
                <div class="kpi-icon-sm" style="background:var(--hr-primary-bg); color:var(--hr-primary);"><i class="ph-duotone ph-users-three"></i></div>
            </div>
            <div class="kpi-val" style="color: var(--hr-primary);"><?= number_format($kpis['total_employees']) ?></div>
        </div>
        <div class="bento-card col-3" style="border-top: 4px solid var(--hr-success);">
            <div class="kpi-header">
                <div class="kpi-title"><?= $t['pres_today'] ?></div>
                <div class="kpi-icon-sm" style="background:var(--hr-success-bg); color:var(--hr-success);"><i class="ph-duotone ph-check-circle"></i></div>
            </div>
            <div class="kpi-val" style="color: var(--hr-success);"><?= number_format($kpis['present_today']) ?></div>
        </div>
        <div class="bento-card col-3" style="border-top: 4px solid var(--hr-warning);">
            <div class="kpi-header">
                <div class="kpi-title"><?= $t['leave_today'] ?></div>
                <div class="kpi-icon-sm" style="background:var(--hr-warning-bg); color:var(--hr-warning);"><i class="ph-duotone ph-airplane-takeoff"></i></div>
            </div>
            <div class="kpi-val" style="color: var(--hr-warning);"><?= number_format($kpis['on_leave_today']) ?></div>
        </div>
        <div class="bento-card col-3" style="border-top: 4px solid var(--hr-purple); background: var(--hr-purple-bg);">
            <div class="kpi-header">
                <div class="kpi-title" style="color:var(--hr-purple);"><?= $t['pay_net'] ?></div>
                <div class="kpi-icon-sm" style="background:#fff; color:var(--hr-purple);"><i class="ph-duotone ph-coins"></i></div>
            </div>
            <div class="kpi-val" style="color: var(--hr-purple);"><?= number_format($convert($kpis['latest_payroll_net']), 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="bento-grid">
        <div class="bento-card col-4">
            <h3 class="chart-title"><i class="ph-duotone ph-chart-pie-slice" style="color:var(--hr-primary);"></i> <?= $t['c_dept'] ?></h3>
            <div style="height: 220px; position: relative;">
                <canvas id="deptChart"></canvas>
            </div>
        </div>
        <div class="bento-card col-4">
            <h3 class="chart-title"><i class="ph-duotone ph-chart-donut" style="color:var(--hr-success);"></i> <?= $t['c_att'] ?></h3>
            <div style="height: 220px; position: relative;">
                <canvas id="attChart"></canvas>
            </div>
        </div>
        <div class="bento-card col-4">
            <h3 class="chart-title"><i class="ph-duotone ph-user-plus" style="color:var(--hr-pink);"></i> <?= $t['c_rec'] ?></h3>
            <div style="height: 220px; position: relative;">
                <canvas id="recChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="bento-grid">
        <div class="bento-card col-4">
            <h3 class="chart-title"><i class="ph-duotone ph-star" style="color:var(--hr-warning);"></i> <?= $t['c_appr'] ?></h3>
            <div style="height: 220px; position: relative;">
                <canvas id="apprChart"></canvas>
            </div>
        </div>
        <div class="bento-card col-4">
            <h3 class="chart-title"><i class="ph-duotone ph-file-text" style="color:var(--hr-danger);"></i> <?= $t['c_cont'] ?></h3>
            <div style="height: 220px; position: relative;">
                <canvas id="contractChart"></canvas>
            </div>
        </div>
        <div class="bento-card col-4">
            <h3 class="chart-title"><i class="ph-duotone ph-airplane" style="color:var(--hr-purple);"></i> <?= $t['c_leave'] ?></h3>
            <div style="height: 220px; position: relative;">
                <canvas id="leaveChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Bottom Hub & Alerts -->
    <div class="bento-grid">
        <div class="bento-card col-4" style="background: var(--surface-bg);">
            <h3 class="chart-title"><i class="ph-fill ph-grid-four" style="color:var(--text-sub);"></i> <?= $t['hub'] ?></h3>
            <div class="hub-grid">
                <a href="/ERP/hr/employees" class="hub-item"><i class="ph-duotone ph-users" style="color:var(--hr-primary);"></i> <?= $t['mod_emp'] ?></a>
                <a href="/ERP/hr/departments" class="hub-item"><i class="ph-duotone ph-buildings" style="color:var(--hr-success);"></i> <?= $t['mod_dept'] ?></a>
                <a href="/ERP/hr/designations" class="hub-item"><i class="ph-duotone ph-identification-badge" style="color:var(--hr-warning);"></i> <?= $t['mod_desig'] ?></a>
                <a href="/ERP/hr/contracts" class="hub-item"><i class="ph-duotone ph-file-text" style="color:var(--hr-danger);"></i> <?= $t['mod_cont'] ?></a>
                <a href="/ERP/hr/attendance" class="hub-item"><i class="ph-duotone ph-clock" style="color:#0ea5e9;"></i> <?= $t['mod_att'] ?></a>
                <a href="/ERP/hr/shifts" class="hub-item"><i class="ph-duotone ph-calendar" style="color:#6366f1;"></i> <?= $t['mod_shift'] ?></a>
                <a href="/ERP/hr/leaves" class="hub-item"><i class="ph-duotone ph-airplane-takeoff" style="color:var(--hr-purple);"></i> <?= $t['mod_leave'] ?></a>
                <a href="/ERP/hr/payroll" class="hub-item"><i class="ph-duotone ph-coins" style="color:#be123c;"></i> <?= $t['mod_pay'] ?></a>
                <a href="/ERP/hr/salary-components" class="hub-item"><i class="ph-duotone ph-sliders-horizontal" style="color:#4338ca;"></i> <?= $t['mod_comp'] ?></a>
                <a href="/ERP/hr/appraisals" class="hub-item"><i class="ph-duotone ph-chart-line-up" style="color:#ea580c;"></i> <?= $t['mod_appr'] ?></a>
                <a href="/ERP/hr/recruitment" class="hub-item"><i class="ph-duotone ph-user-plus" style="color:var(--hr-pink);"></i> <?= $t['mod_rec'] ?></a>
                <a href="/ERP/hr/documents" class="hub-item"><i class="ph-duotone ph-folder-user" style="color:#0f766e;"></i> <?= $t['mod_doc'] ?></a>
            </div>
        </div>

        <div class="bento-card col-8" style="border-inline-start: 4px solid var(--hr-danger);">
            <h3 class="chart-title"><i class="ph-duotone ph-warning-circle" style="color:var(--hr-danger);"></i> <?= $t['alerts'] ?></h3>
            <div style="overflow-x: auto; width:100%;">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th><?= $t['col_type'] ?></th>
                            <th><?= $t['col_code'] ?></th>
                            <th><?= $t['col_emp'] ?></th>
                            <th style="text-align:center;"><?= $t['col_exp'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expiringAlerts)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:20px; color:var(--text-sub);"><?= $t['no_alerts'] ?></td></tr>
                        <?php else: foreach ($expiringAlerts as $alert): 
                            $typeStr = $isRtl ? ($alert->type_ar ?? '') : ($alert->type_en ?? '');
                            $empStr  = $isRtl ? ($alert->emp_name ?? '') : ($alert->emp_name_en ?: ($alert->emp_name ?? ''));
                        ?>
                            <tr>
                                <td><span style="background:var(--hr-danger-bg); color:var(--hr-danger); padding:2px 8px; border-radius:4px; font-size:0.75rem;"><?= htmlspecialchars($typeStr) ?></span></td>
                                <td style="font-family:monospace; font-weight:900;"><?= htmlspecialchars($alert->code) ?></td>
                                <td><?= htmlspecialchars($empStr) ?></td>
                                <td style="font-family:monospace; font-weight:900; color:var(--hr-danger); text-align:center;"><?= htmlspecialchars($alert->exp_date) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Chart === 'undefined') return;

    Chart.defaults.font.family = "<?= $isRtl ? 'Cairo, sans-serif' : 'Inter, sans-serif' ?>";
    Chart.defaults.color = '#64748b';

    // Purge unwanted DataTables elements inside bento cards
    function purgeDataTables() {
        document.querySelectorAll('.bento-card .dataTables_wrapper, .bento-card .dataTables_filter, .bento-card .dataTables_length, .bento-card .dataTables_info, .bento-card .dataTables_paginate').forEach(el => el.remove());
    }
    purgeDataTables(); setTimeout(purgeDataTables, 300);

    // Chart 1: Departments
    try {
        const deptLabels = <?= json_encode(array_values($charts['dept_labels'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
        const deptData = <?= json_encode(array_values($charts['dept_data'] ?? [])) ?>;
        new Chart(document.getElementById('deptChart'), {
            type: 'doughnut',
            data: {
                labels: deptLabels.length ? deptLabels : ['<?= $t['no_data'] ?>'],
                datasets: [{
                    data: deptData.length ? deptData : [1],
                    backgroundColor: deptData.length ? ['#0284c7', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#db2777', '#0f766e'] : ['#cbd5e1'],
                    borderWidth: 2, borderColor: '#ffffff'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
        });
    } catch(e) {}

    // Chart 2: Attendance
    try {
        new Chart(document.getElementById('attChart'), {
            type: 'polarArea',
            data: {
                labels: <?= $attLabelsJson ?>,
                datasets: [{
                    data: <?= json_encode(array_values($charts['att_status_data'] ?? [])) ?>,
                    backgroundColor: ['rgba(16, 185, 129, 0.8)', 'rgba(239, 68, 68, 0.8)', 'rgba(124, 58, 237, 0.8)']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
        });
    } catch(e) {}

    // Chart 3: Recruitment
    try {
        new Chart(document.getElementById('recChart'), {
            type: 'bar',
            data: {
                labels: <?= $recLabelsJson ?>,
                datasets: [{
                    label: 'Count',
                    data: <?= json_encode(array_values($charts['recruitment_data'] ?? [])) ?>,
                    backgroundColor: ['#0284c7', '#f59e0b', '#8b5cf6', '#10b981', '#ef4444'],
                    borderRadius: 4
                }]
            },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, grid:{display:false} }, y: { grid:{display:false} } } }
        });
    } catch(e) {}

    // Chart 4: Appraisals
    try {
        new Chart(document.getElementById('apprChart'), {
            type: 'bar',
            data: {
                labels: <?= $appLabelsJson ?>,
                datasets: [{
                    label: 'Count',
                    data: <?= json_encode(array_values($charts['appraisal_data'] ?? [])) ?>,
                    backgroundColor: ['#10b981', '#0284c7', '#f59e0b', '#8b5cf6', '#ef4444'],
                    borderRadius: 6,
                    barPercentage: 0.5
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid:{borderDash:[5,5]} }, x: { grid:{display:false} } } }
        });
    } catch(e) {}

    // Chart 5: Contracts
    try {
        new Chart(document.getElementById('contractChart'), {
            type: 'pie',
            data: {
                labels: <?= $contLabelsJson ?>,
                datasets: [{
                    data: <?= json_encode(array_values($charts['contract_status_data'] ?? [])) ?>,
                    backgroundColor: ['#10b981', '#ef4444', '#64748b'],
                    borderWidth: 2, borderColor: '#ffffff'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
        });
    } catch(e) {}

    // Chart 6: Leaves
    try {
        new Chart(document.getElementById('leaveChart'), {
            type: 'doughnut',
            data: {
                labels: <?= $levLabelsJson ?>,
                datasets: [{
                    data: <?= json_encode(array_values($charts['leave_type_data'] ?? [])) ?>,
                    backgroundColor: ['#7c3aed', '#0284c7', '#f59e0b', '#db2777', '#64748b'],
                    borderWidth: 2, borderColor: '#ffffff'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
        });
    } catch(e) {}
});

// تصدير إكسيل מתקדם
function exportHRDashboardToExcel() {
    if (typeof XLSX === 'undefined') return;

    const wb = XLSX.utils.book_new();

    // 1. المؤشرات
    const summaryData = [
        ["مؤشر الموارد البشرية (KPI)", "القيمة / العدد"],
        ["إجمالي الكادر النشط", <?= (int)($kpis['total_employees'] ?? 0) ?>],
        ["حضور اليوم", <?= (int)($kpis['present_today'] ?? 0) ?>],
        ["إجازات اليوم", <?= (int)($kpis['on_leave_today'] ?? 0) ?>],
        ["صافي الرواتب (المسير الأخير)", <?= (float)($convert($kpis['latest_payroll_net'] ?? 0)) ?>],
        ["متوسط تقييم الأداء", <?= (float)($kpis['avg_appraisal'] ?? 0) ?>]
    ];
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(summaryData), "المؤشرات الرئيسية");

    const fileName = "HR_Executive_Dashboard_" + new Date().toISOString().slice(0, 10) + ".xlsx";
    XLSX.writeFile(wb, fileName);
}
</script>