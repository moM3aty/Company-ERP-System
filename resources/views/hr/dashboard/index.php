<?php
// Path: resources/views/hr/dashboard/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$kpis = $kpis ?? [
    'total_employees' => 0, 'present_today' => 0, 'on_leave_today' => 0,
    'latest_payroll_net' => 0, 'contracts_expiring' => 0, 'docs_expiring' => 0,
    'active_applicants' => 0, 'avg_appraisal' => 0
];

$charts = $charts ?? [
    'dept_labels' => [], 'dept_data' => [],
    'att_status_labels' => ['حاضر', 'متأخر', 'غائب', 'إجازة'], 'att_status_data' => [0,0,0,0],
    'recruitment_labels' => ['جديد', 'مقابلة', 'عرض', 'توظيف', 'مرفوض'], 'recruitment_data' => [0,0,0,0,0],
    'appraisal_labels' => ['ممتاز', 'جيد جداً', 'جيد', 'مقبول', 'تحسين'], 'appraisal_data' => [0,0,0,0,0],
    'contract_status_labels' => ['ساري', 'منتهي', 'مفسوخ'], 'contract_status_data' => [0,0,0],
    'leave_type_labels' => ['سنوية', 'مرضية', 'بدون راتب', 'وضع', 'أخرى'], 'leave_type_data' => [0,0,0,0,0]
];

$expiringAlerts = $expiringAlerts ?? [];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<style>
    :root {
        --hr-primary: #0f172a;
        --hr-accent: #0284c7;
        --hr-success: #10b981;
        --hr-warning: #f59e0b;
        --hr-danger: #ef4444;
        --hr-purple: #8b5cf6;
        --hr-pink: #db2777;
        --hr-card-bg: #ffffff;
        --hr-border: #e2e8f0;
    }

    .hr-dash-wrapper { padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .dash-title-box h2 { margin: 0; font-size: 1.7rem; font-weight: 900; color: var(--hr-primary); display: flex; align-items: center; gap: 12px; }
    .dash-title-box p { margin: 4px 0 0 0; color: #64748b; font-size: 0.9rem; font-weight: 600; }

    .btn-export-excel { background: #059669; color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-family: inherit; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2); }

    .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width: 1200px){ .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
    @media(max-width: 640px){ .kpi-grid { grid-template-columns: 1fr; } }

    .kpi-card { background: var(--hr-card-bg); border: 1px solid var(--hr-border); border-radius: 16px; padding: 18px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
    .kpi-info h4 { margin: 0 0 4px 0; font-size: 0.78rem; color: #64748b; font-weight: 800; text-transform: uppercase; }
    .kpi-info .val { font-size: 1.7rem; font-weight: 900; font-family: monospace; color: var(--hr-primary); }
    .kpi-icon-box { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

    .charts-row-3col { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-bottom: 24px; }
    @media(max-width: 1200px){ .charts-row-3col { grid-template-columns: repeat(2, 1fr); } }
    @media(max-width: 768px){ .charts-row-3col { grid-template-columns: 1fr; } }

    .chart-card { background: var(--hr-card-bg); border: 1px solid var(--hr-border); border-radius: 18px; padding: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
    .card-title { margin: 0 0 14px 0; font-size: 0.95rem; font-weight: 800; color: var(--hr-primary); display: flex; align-items: center; gap: 8px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 10px; }

    .grid-bottom { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
    @media(max-width: 1024px){ .grid-bottom { grid-template-columns: 1fr; } }

    .hub-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    @media(max-width: 640px){ .hub-grid { grid-template-columns: repeat(2, 1fr); } }
    .hub-item { display: flex; align-items: center; gap: 8px; padding: 10px; background: #f8fafc; border: 1px solid var(--hr-border); border-radius: 10px; text-decoration: none; color: #1e293b; font-weight: 800; font-size: 0.8rem; transition: all 0.2s ease; }
    .hub-item:hover { background: #ffffff; border-color: var(--hr-accent); transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    .hub-item i { font-size: 1.2rem; padding: 6px; border-radius: 6px; background: #fff; }

    .alert-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    .alert-table th { text-align: start; padding: 8px 10px; background: #f8fafc; color: #64748b; border-bottom: 2px solid #e2e8f0; }
    .alert-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; font-weight: 700; }
</style>

<div class="hr-dash-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <div class="dash-header">
        <div class="dash-title-box">
            <h2><i class="ph-duotone ph-chart-line-up" style="color: var(--hr-accent);"></i> اللوحة التنفيذية للموارد البشرية (HR Executive Dashboard)</h2>
            <p>مراقبة شاملة لكادر العمل، الحضور، المسيرات والالتزامات الإدارية.</p>
        </div>
        <button onclick="exportHRDashboardToExcel()" class="btn-export-excel">
            <i class="ph-bold ph-file-xls"></i> تصدير التقرير الشامل (Excel)
        </button>
    </div>

    <!-- KPIs Row -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-info">
                <h4>إجمالي الكادر النشط</h4>
                <div class="val"><?= number_format($kpis['total_employees']) ?></div>
            </div>
            <div class="kpi-icon-box" style="background:#e0f2fe; color:#0284c7;"><i class="ph-duotone ph-users-three"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4>حضور اليوم المكتمل</h4>
                <div class="val" style="color:#10b981;"><?= number_format($kpis['present_today']) ?></div>
            </div>
            <div class="kpi-icon-box" style="background:#d1fae5; color:#10b981;"><i class="ph-duotone ph-clock"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4>إجازات نشطة اليوم</h4>
                <div class="val" style="color:#f59e0b;"><?= number_format($kpis['on_leave_today']) ?></div>
            </div>
            <div class="kpi-icon-box" style="background:#fef3c7; color:#f59e0b;"><i class="ph-duotone ph-airplane-takeoff"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4>إجمالي آخر مسير رواتب</h4>
                <div class="val" style="color:#8b5cf6; font-size:1.3rem;"><?= number_format($kpis['latest_payroll_net'], 2) ?></div>
            </div>
            <div class="kpi-icon-box" style="background:#ede9fe; color:#8b5cf6;"><i class="ph-duotone ph-coins"></i></div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="charts-row-3col">
        <div class="chart-card">
            <h3 class="card-title"><i class="ph-duotone ph-chart-pie-slice" style="color:var(--hr-accent);"></i> توزيع الأقسام الإدارية</h3>
            <div style="height: 200px; position: relative;">
                <canvas id="deptChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h3 class="card-title"><i class="ph-duotone ph-chart-donut" style="color:var(--hr-success);"></i> حالات الحضور والغياب اليوم</h3>
            <div style="height: 200px; position: relative;">
                <canvas id="attChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h3 class="card-title"><i class="ph-duotone ph-user-plus" style="color:var(--hr-pink);"></i> مراحل التوظيف والمتقدمين</h3>
            <div style="height: 200px; position: relative;">
                <canvas id="recChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="charts-row-3col">
        <div class="chart-card">
            <h3 class="card-title"><i class="ph-duotone ph-star" style="color:#ea580c;"></i> درجات تقييم الأداء</h3>
            <div style="height: 200px; position: relative;">
                <canvas id="apprChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h3 class="card-title"><i class="ph-duotone ph-file-text" style="color:#dc2626;"></i> حالات عقود الموظفين</h3>
            <div style="height: 200px; position: relative;">
                <canvas id="contractChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h3 class="card-title"><i class="ph-duotone ph-airplane" style="color:#7c3aed;"></i> تصنيف طلبات الإجازات</h3>
            <div style="height: 200px; position: relative;">
                <canvas id="leaveChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Bottom Section -->
    <div class="grid-bottom">
        <div class="chart-card">
            <h3 class="card-title"><i class="ph-duotone ph-grid-four" style="color:var(--hr-purple);"></i> الوصول السريع للموديولات</h3>
            <div class="hub-grid">
                <a href="/ERP/hr/employees" class="hub-item"><i class="ph-duotone ph-users" style="color:#0284c7;"></i> الموظفين</a>
                <a href="/ERP/hr/departments" class="hub-item"><i class="ph-duotone ph-buildings" style="color:#059669;"></i> الإدارات</a>
                <a href="/ERP/hr/designations" class="hub-item"><i class="ph-duotone ph-identification-badge" style="color:#d97706;"></i> المسميات</a>
                <a href="/ERP/hr/contracts" class="hub-item"><i class="ph-duotone ph-file-text" style="color:#dc2626;"></i> العقود</a>
                <a href="/ERP/hr/attendance" class="hub-item"><i class="ph-duotone ph-clock" style="color:#0ea5e9;"></i> الحضور</a>
                <a href="/ERP/hr/shifts" class="hub-item"><i class="ph-duotone ph-calendar" style="color:#6366f1;"></i> الورديات</a>
                <a href="/ERP/hr/leaves" class="hub-item"><i class="ph-duotone ph-airplane-takeoff" style="color:#7c3aed;"></i> الإجازات</a>
                <a href="/ERP/hr/payroll" class="hub-item"><i class="ph-duotone ph-coins" style="color:#be123c;"></i> الرواتب</a>
                <a href="/ERP/hr/salary-components" class="hub-item"><i class="ph-duotone ph-sliders-horizontal" style="color:#4338ca;"></i> المفردات</a>
                <a href="/ERP/hr/appraisals" class="hub-item"><i class="ph-duotone ph-chart-line-up" style="color:#ea580c;"></i> التقييمات</a>
                <a href="/ERP/hr/recruitment" class="hub-item"><i class="ph-duotone ph-user-plus" style="color:#db2777;"></i> التوظيف</a>
                <a href="/ERP/hr/documents" class="hub-item"><i class="ph-duotone ph-folder-user" style="color:#0f766e;"></i> الوثائق</a>
            </div>
        </div>

        <div class="chart-card">
            <h3 class="card-title"><i class="ph-duotone ph-warning" style="color:var(--hr-danger);"></i> تنبيهات الاستحقاق والانتهاء العاجلة (30 يوماً)</h3>
            <div style="overflow-x: auto;">
                <table class="alert-table" id="alertsTable">
                    <thead>
                        <tr>
                            <th>النوع</th>
                            <th>كود/رقم</th>
                            <th>الموظف</th>
                            <th>تاريخ الانتهاء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expiringAlerts)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:20px; color:#94a3b8;">لا توجد تنبيهات انتهاء خلال الـ 30 يوماً القادمة.</td></tr>
                        <?php else: ?>
                            <?php foreach ($expiringAlerts as $alert): ?>
                                <tr>
                                    <td><span style="background:#fee2e2; color:#dc2626; padding:2px 8px; border-radius:4px; font-size:0.75rem;"><?= htmlspecialchars($alert->type) ?></span></td>
                                    <td style="font-family:monospace;"><?= htmlspecialchars($alert->code) ?></td>
                                    <td><?= htmlspecialchars($alert->emp_name) ?></td>
                                    <td style="font-family:monospace; color:#dc2626;"><?= htmlspecialchars($alert->exp_date) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Chart === 'undefined') return;

    Chart.defaults.font.family = "'Cairo', 'Inter', sans-serif";

    // Chart 1: Departments
    try {
        const deptLabels = <?= json_encode(array_values($charts['dept_labels'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
        const deptData = <?= json_encode(array_values($charts['dept_data'] ?? [])) ?>;
        
        new Chart(document.getElementById('deptChart'), {
            type: 'doughnut',
            data: {
                labels: deptLabels.length ? deptLabels : ['لا يوجد بيانات'],
                datasets: [{
                    data: deptData.length ? deptData : [1],
                    backgroundColor: deptData.length ? ['#0284c7', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#db2777', '#0f766e'] : ['#cbd5e1'],
                    borderWidth: 2, borderColor: '#ffffff'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
        });
    } catch(e) {}

    // Chart 2: Attendance
    try {
        new Chart(document.getElementById('attChart'), {
            type: 'polarArea',
            data: {
                labels: <?= json_encode(array_values($charts['att_status_labels'] ?? []), JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($charts['att_status_data'] ?? [])) ?>,
                    backgroundColor: ['rgba(16, 185, 129, 0.8)', 'rgba(245, 158, 11, 0.8)', 'rgba(239, 68, 68, 0.8)', 'rgba(124, 58, 237, 0.8)']
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
                labels: <?= json_encode(array_values($charts['recruitment_labels'] ?? []), JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    label: 'عدد المتقدمين',
                    data: <?= json_encode(array_values($charts['recruitment_data'] ?? [])) ?>,
                    backgroundColor: ['#0284c7', '#f59e0b', '#8b5cf6', '#10b981', '#ef4444'],
                    borderRadius: 4
                }]
            },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    } catch(e) {}

    // Chart 4: Appraisals
    try {
        new Chart(document.getElementById('apprChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_values($charts['appraisal_labels'] ?? []), JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    label: 'عدد الموظفين',
                    data: <?= json_encode(array_values($charts['appraisal_data'] ?? [])) ?>,
                    backgroundColor: ['#10b981', '#0284c7', '#f59e0b', '#8b5cf6', '#ef4444'],
                    borderRadius: 4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    } catch(e) {}

    // Chart 5: Contracts
    try {
        new Chart(document.getElementById('contractChart'), {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_values($charts['contract_status_labels'] ?? []), JSON_UNESCAPED_UNICODE) ?>,
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
                labels: <?= json_encode(array_values($charts['leave_type_labels'] ?? []), JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($charts['leave_type_data'] ?? [])) ?>,
                    backgroundColor: ['#7c3aed', '#0284c7', '#f59e0b', '#db2777', '#64748b'],
                    borderWidth: 2, borderColor: '#ffffff'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
        });
    } catch(e) {}
});

// تصدير إكسيل متكامل متعدد الأوراق
function exportHRDashboardToExcel() {
    if (typeof XLSX === 'undefined') return;

    const wb = XLSX.utils.book_new();

    // 1. المؤشرات التنفيذية (KPIs)
    const summaryData = [
        ["مؤشر الموارد البشرية (KPI)", "القيمة / العدد"],
        ["إجمالي الكادر النشط", <?= (int)($kpis['total_employees'] ?? 0) ?>],
        ["حضور اليوم المكتمل", <?= (int)($kpis['present_today'] ?? 0) ?>],
        ["غياب اليوم", <?= (int)($kpis['absent_today'] ?? 0) ?>],
        ["إجازات نشطة اليوم", <?= (int)($kpis['on_leave_today'] ?? 0) ?>],
        ["إجمالي آخر مسير رواتب (صافي)", <?= (float)($kpis['latest_payroll_net'] ?? 0) ?>],
        ["عقود ينتهي سريانها (30 يوماً)", <?= (int)($kpis['contracts_expiring'] ?? 0) ?>],
        ["وثائق موشكة على الانتهاء (30 يوماً)", <?= (int)($kpis['docs_expiring'] ?? 0) ?>],
        ["طلبات توظيف قيد المراجعة", <?= (int)($kpis['active_applicants'] ?? 0) ?>],
        ["متوسط تقييم الأداء العام %", <?= (float)($kpis['avg_appraisal'] ?? 0) ?>]
    ];
    const wsSummary = XLSX.utils.aoa_to_sheet(summaryData);
    XLSX.utils.book_append_sheet(wb, wsSummary, "المؤشرات التنفيذية");

    // 2. توزيع الموظفين حسب الأقسام
    const deptLabels = <?= json_encode(array_values($charts['dept_labels'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
    const deptData = <?= json_encode(array_values($charts['dept_data'] ?? [])) ?>;
    const deptSheetData = [["الإدارة / القسم", "عدد الموظفين النشطين"]];
    deptLabels.forEach((label, idx) => {
        deptSheetData.push([label, deptData[idx] || 0]);
    });
    const wsDept = XLSX.utils.aoa_to_sheet(deptSheetData);
    XLSX.utils.book_append_sheet(wb, wsDept, "توزيع الأقسام");

    // 3. ملخص الحضور والغياب اليومي
    const attLabels = <?= json_encode(array_values($charts['att_status_labels'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
    const attData = <?= json_encode(array_values($charts['att_status_data'] ?? [])) ?>;
    const attSheetData = [["حالة الحضور اليوم", "العدد"]];
    attLabels.forEach((label, idx) => {
        attSheetData.push([label, attData[idx] || 0]);
    });
    const wsAtt = XLSX.utils.aoa_to_sheet(attSheetData);
    XLSX.utils.book_append_sheet(wb, wsAtt, "الحضور والغياب");

    // 4. مراحل التوظيف والمتقدمين
    const recLabels = <?= json_encode(array_values($charts['recruitment_labels'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
    const recData = <?= json_encode(array_values($charts['recruitment_data'] ?? [])) ?>;
    const recSheetData = [["مرحلة التوظيف", "عدد المتقدمين"]];
    recLabels.forEach((label, idx) => {
        recSheetData.push([label, recData[idx] || 0]);
    });
    const wsRec = XLSX.utils.aoa_to_sheet(recSheetData);
    XLSX.utils.book_append_sheet(wb, wsRec, "مراحل التوظيف");

    // 5. تقييمات الأداء
    const apprLabels = <?= json_encode(array_values($charts['appraisal_labels'] ?? []), JSON_UNESCAPED_UNICODE) ?>;
    const apprData = <?= json_encode(array_values($charts['appraisal_data'] ?? [])) ?>;
    const apprSheetData = [["درجة التقييم", "عدد الموظفين"]];
    apprLabels.forEach((label, idx) => {
        apprSheetData.push([label, apprData[idx] || 0]);
    });
    const wsAppr = XLSX.utils.aoa_to_sheet(apprSheetData);
    XLSX.utils.book_append_sheet(wb, wsAppr, "تقييمات الأداء");

    // 6. التنبيهات العاجلة والاستحقاقات
    const alertsDataRaw = <?= json_encode($expiringAlerts ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const alertsSheetData = [["نوع التنبيه", "كود / رقم المستند", "اسم الموظف", "تاريخ الانتهاء"]];
    if (alertsDataRaw && alertsDataRaw.length > 0) {
        alertsDataRaw.forEach(item => {
            alertsSheetData.push([item.type || '', item.code || '', item.emp_name || '', item.exp_date || '']);
        });
    } else {
        alertsSheetData.push(["لا توجد تنبيهات عاجلة خلال الـ 30 يوماً القادمة", "", "", ""]);
    }
    const wsAlerts = XLSX.utils.aoa_to_sheet(alertsSheetData);
    XLSX.utils.book_append_sheet(wb, wsAlerts, "التنبيهات العاجلة");

    // حفظ وحذف الملف
    const fileName = "HR_Full_Executive_Report_" + new Date().toISOString().slice(0, 10) + ".xlsx";
    XLSX.writeFile(wb, fileName);
}
</script>