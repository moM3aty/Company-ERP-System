<?php
// Path: resources/views/projects/dashboard/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$kpis = $kpis ?? [
    'total_projects' => 0, 'active_projects' => 0, 'total_contract_val' => 0,
    'total_spent' => 0, 'total_claims' => 0, 'total_paid_claims' => 0, 'active_milestones' => 0
];
$charts = $charts ?? [
    'budget_proj_names' => [], 'budget_values' => [], 'spent_values' => [],
    'status_labels' => ['التخطيط', 'قيد التنفيذ', 'متوقف', 'مكتمل', 'ملغى'], 'status_counts' => [0,0,0,0,0],
    'monthly_labels' => [], 'monthly_claims' => [], 'monthly_costs' => [],
    'cost_cat_labels' => ['مواد', 'عمالة', 'معدات', 'مقاولين', 'إدارية'], 'cost_cat_values' => [0,0,0,0,0]
];
$recentProjects = $recentProjects ?? [];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --dash-indigo: #4f46e5; --dash-indigo-bg: #eef2ff;
        --dash-cyan: #0284c7; --dash-cyan-bg: #e0f2fe;
        --dash-emerald: #059669; --dash-emerald-bg: #ecfdf5;
        --dash-orange: #ea580c; --dash-orange-bg: #ffedd5;
        --dash-rose: #be123c; --dash-rose-bg: #fff1f2;
        --dash-violet: #8b5cf6; --dash-violet-bg: #f5f3ff;
    }

    .projects-dash { font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; padding-bottom: 50px; }
    .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; flex-wrap: wrap; gap: 16px; }
    .dash-title-group { display: flex; align-items: center; gap: 14px; }
    .dash-header-icon { width: 52px; height: 52px; background: var(--dash-indigo-bg); color: var(--dash-indigo); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 6px 15px rgba(79, 70, 229, 0.15); }

    .btn-export-excel { background: #059669; color: #ffffff !important; padding: 10px 20px; border-radius: 12px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2); transition: all 0.2s ease; }
    .btn-export-excel:hover { background: #047857; transform: translateY(-2px); }

    /* Bento Grid System */
    .bento-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px; margin-bottom: 24px; }
    
    .bento-card {
        background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 22px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: all 0.3s ease; display: flex; flex-direction: column;
        text-decoration: none; color: inherit;
    }
    .bento-card.kpi-link:hover { transform: translateY(-4px); box-shadow: 0 12px 25px rgba(0,0,0,0.08); border-color: var(--dash-indigo); }

    .kpi-card { grid-column: span 2; cursor: pointer; }
    @media(max-width: 1280px) { .kpi-card { grid-column: span 4; } }
    @media(max-width: 768px) { .kpi-card { grid-column: span 6; } }
    @media(max-width: 480px) { .kpi-card { grid-column: span 12; } }

    .kpi-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 12px; flex-shrink: 0; }
    .kpi-label { font-size: 0.78rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 6px; }
    .kpi-value { font-size: 1.35rem; font-weight: 900; color: #0f172a; font-family: monospace; display: flex; align-items: baseline; gap: 4px; }

    .col-8 { grid-column: span 8; }
    .col-4 { grid-column: span 4; }
    @media(max-width: 1024px) { .col-8, .col-4 { grid-column: span 12; } }

    .hub-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .action-card-node {
        display: flex; align-items: center; gap: 12px; padding: 14px; background: #f8fafc;
        border: 1px solid #e2e8f0; border-radius: 14px; text-decoration: none; color: #0f172a;
        font-weight: 800; font-size: 0.88rem; transition: all 0.2s ease;
    }
    .action-card-node:hover { background: #ffffff; border-color: var(--dash-indigo); transform: translateX(<?= $isRtl ? '-4px' : '4px' ?>); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .action-card-node i { font-size: 1.3rem; width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

    .dash-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .dash-table th { padding: 12px 14px; background: #f8fafc; color: #64748b; font-weight: 800; border-bottom: 2px solid #e2e8f0; text-align: start; }
    .dash-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
</style>

<div class="projects-dash" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <div class="dash-header">
        <div class="dash-title-group">
            <div class="dash-header-icon"><i class="ph-duotone ph-kanban"></i></div>
            <div>
                <h2 style="margin:0; font-size:1.8rem; font-weight:900; color:#0f172a;"><?= $isRtl ? 'لوحة قيادة المشاريع والمقاولات' : 'Projects Dashboard' ?></h2>
                <p style="margin:3px 0 0 0; color:#64748b; font-size:0.9rem; font-weight:600;"><?= $isRtl ? 'متابعة شاملة للميزانيات والمستخلصات، مصروفات الموقع، والعقود.' : 'Executive overview of project budgets, claims, site costs, and contracts.' ?></p>
            </div>
        </div>
        <div>
            <a href="/ERP/projects/dashboard?export=excel" class="btn-export-excel">
                <i class="ph-bold ph-file-xls" style="font-size:1.2rem;"></i>
                <?= $isRtl ? 'تصدير شيت إكسيل' : 'Export Excel' ?>
            </a>
        </div>
    </div>

    <!-- 6 Clickable Executive KPIs -->
    <div class="bento-grid">
        <a href="/ERP/projects/list" class="bento-card kpi-card kpi-link">
            <div class="kpi-icon" style="background: var(--dash-cyan-bg); color: var(--dash-cyan);"><i class="ph-duotone ph-buildings"></i></div>
            <div class="kpi-label"><?= $isRtl ? 'المشاريع النشطة' : 'Active Projects' ?></div>
            <div class="kpi-value" style="color: var(--dash-cyan);"><?= number_format($kpis['active_projects']) ?> <span style="font-size:0.8rem; color:#64748b;">/ <?= $kpis['total_projects'] ?></span></div>
        </a>

        <!-- تصحيح الأيقونة إلى ph-scroll المعتمدة وتوسيطها -->
        <a href="/ERP/projects/contracts" class="bento-card kpi-card kpi-link" title="اضغط للذهاب لصفحة العقود">
            <div class="kpi-icon" style="background: var(--dash-rose-bg); color: var(--dash-rose);"><i class="ph-duotone ph-scroll"></i></div>
            <div class="kpi-label"><?= $isRtl ? 'إجمالي قيمة العقود' : 'Total Contracts' ?></div>
            <div class="kpi-value" style="color: var(--dash-rose);"><?= number_format($kpis['total_contract_val'], 2) ?></div>
        </a>

        <a href="/ERP/projects/costs" class="bento-card kpi-card kpi-link">
            <div class="kpi-icon" style="background: var(--dash-orange-bg); color: var(--dash-orange);"><i class="ph-duotone ph-currency-dollar"></i></div>
            <div class="kpi-label"><?= $isRtl ? 'المصروفات الفعلية' : 'Total Spent' ?></div>
            <div class="kpi-value" style="color: var(--dash-orange);"><?= number_format($kpis['total_spent'], 2) ?></div>
        </a>

        <a href="/ERP/projects/invoices" class="bento-card kpi-card kpi-link">
            <div class="kpi-icon" style="background: var(--dash-emerald-bg); color: var(--dash-emerald);"><i class="ph-duotone ph-file-text"></i></div>
            <div class="kpi-label"><?= $isRtl ? 'مطالبات المستخلصات' : 'Total Claims' ?></div>
            <div class="kpi-value" style="color: var(--dash-emerald);"><?= number_format($kpis['total_claims'], 2) ?></div>
        </a>

        <a href="/ERP/projects/invoices?status=paid" class="bento-card kpi-card kpi-link">
            <div class="kpi-icon" style="background: var(--dash-indigo-bg); color: var(--dash-indigo);"><i class="ph-duotone ph-check-circle"></i></div>
            <div class="kpi-label"><?= $isRtl ? 'المحصلات والتحصيلات' : 'Paid Claims' ?></div>
            <div class="kpi-value" style="color: var(--dash-indigo);"><?= number_format($kpis['total_paid_claims'], 2) ?></div>
        </a>

        <a href="/ERP/projects/milestones" class="bento-card kpi-card kpi-link">
            <div class="kpi-icon" style="background: var(--dash-violet-bg); color: var(--dash-violet);"><i class="ph-duotone ph-flag-banner"></i></div>
            <div class="kpi-label"><?= $isRtl ? 'مهام جارية التنفيذ' : 'Active Milestones' ?></div>
            <div class="kpi-value" style="color: var(--dash-violet);"><?= number_format($kpis['active_milestones']) ?></div>
        </a>
    </div>

    <!-- Charts Row 1 -->
    <div class="bento-grid">
        <div class="bento-card col-8">
            <h3 style="margin:0 0 18px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-duotone ph-chart-bar" style="color:var(--dash-indigo); font-size:1.3rem;"></i>
                <?= $isRtl ? '1. مقارنة الميزانية التقديرية بالمصروفات الفعلية' : '1. Budget vs Spent' ?>
            </h3>
            <div style="height: 250px; position: relative; width: 100%;">
                <canvas id="budgetVsSpentChart"></canvas>
            </div>
        </div>

        <div class="bento-card col-4">
            <h3 style="margin:0 0 18px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-duotone ph-chart-pie-slice" style="color:var(--dash-cyan); font-size:1.3rem;"></i>
                <?= $isRtl ? '2. توزيع المشاريع حسب الحالة' : '2. Project Status' ?>
            </h3>
            <div style="height: 220px; position: relative; width: 100%; display:flex; justify-content:center;">
                <canvas id="statusPieChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="bento-grid">
        <div class="bento-card col-8">
            <h3 style="margin:0 0 18px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-duotone ph-chart-line-up" style="color:var(--dash-emerald); font-size:1.3rem;"></i>
                <?= $isRtl ? '3. حركة المستخلصات مقابل مصروفات المواقع (آخر 6 أشهر)' : '3. Claims vs Costs Trend' ?>
            </h3>
            <div style="height: 240px; position: relative; width: 100%;">
                <canvas id="monthlyClaimsCostsChart"></canvas>
            </div>
        </div>

        <div class="bento-card col-4">
            <h3 style="margin:0 0 18px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-duotone ph-coins" style="color:var(--dash-orange); font-size:1.3rem;"></i>
                <?= $isRtl ? '4. توزيع التكاليف حسب تبويب المصروف' : '4. Cost Categories' ?>
            </h3>
            <div style="height: 240px; position: relative; width: 100%;">
                <canvas id="costCategoriesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Module Hub & Recent Activity Table -->
    <div class="bento-grid">
        <div class="bento-card col-4" style="background:#f8fafc;">
            <h3 style="margin:0 0 16px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-fill ph-grid-four" style="color:#64748b;"></i>
                <?= $isRtl ? 'اختصارات أقسام المشاريع' : 'Quick Projects Hub' ?>
            </h3>

            <div class="hub-grid">
                <a href="/ERP/projects/list" class="action-card-node">
                    <i class="ph-duotone ph-buildings" style="background:var(--dash-cyan-bg); color:var(--dash-cyan);"></i>
                    <span><?= $isRtl ? 'سجل المشاريع' : 'Projects Directory' ?></span>
                </a>

                <a href="/ERP/projects/milestones" class="action-card-node">
                    <i class="ph-duotone ph-flag-banner" style="background:var(--dash-violet-bg); color:var(--dash-violet);"></i>
                    <span><?= $isRtl ? 'المراحل والمهام' : 'Milestones' ?></span>
                </a>

                <a href="/ERP/projects/invoices" class="action-card-node">
                    <i class="ph-duotone ph-file-text" style="background:var(--dash-emerald-bg); color:var(--dash-emerald);"></i>
                    <span><?= $isRtl ? 'المستخلصات' : 'Claims' ?></span>
                </a>

                <a href="/ERP/projects/costs" class="action-card-node">
                    <i class="ph-duotone ph-currency-dollar" style="background:var(--dash-orange-bg); color:var(--dash-orange);"></i>
                    <span><?= $isRtl ? 'مصروفات الموقع' : 'Site Expenses' ?></span>
                </a>

                <!-- تصحيح أيقونة الاختصار أيضاً -->
                <a href="/ERP/projects/contracts" class="action-card-node" style="grid-column: span 2;">
                    <i class="ph-duotone ph-scroll" style="background:var(--dash-rose-bg); color:var(--dash-rose);"></i>
                    <span><?= $isRtl ? 'عقود المشاريع والمقاولين' : 'Contracts Directory' ?></span>
                </a>
            </div>
        </div>

        <div class="bento-card col-8">
            <h3 style="margin:0 0 16px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-duotone ph-list-bullets" style="color:var(--dash-indigo); font-size:1.3rem;"></i>
                <?= $isRtl ? 'أحدث المشاريع المسجلة ونسبة الإنجاز' : 'Recent Projects' ?>
            </h3>

            <table class="dash-table">
                <thead>
                    <tr>
                        <th><?= $isRtl ? 'الكود' : 'Code' ?></th>
                        <th><?= $isRtl ? 'اسم المشروع' : 'Project Name' ?></th>
                        <th><?= $isRtl ? 'قيمة العقد' : 'Contract Value' ?></th>
                        <th style="width:25%;"><?= $isRtl ? 'نسبة الإنجاز' : 'Completion %' ?></th>
                        <th style="text-align:center;"><?= $isRtl ? 'الحالة' : 'Status' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentProjects)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8; font-weight:700;"><?= $isRtl ? 'لا توجد مشاريع مسجلة مؤخراً.' : 'No recent projects found.' ?></td></tr>
                    <?php else: foreach ($recentProjects as $p): $pct = (float)$p->progress_percent; ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:900; color:var(--dash-indigo);"><?= htmlspecialchars($p->code) ?></td>
                            <td style="font-weight:800; color:#0f172a;"><?= htmlspecialchars($p->name_ar) ?></td>
                            <td style="font-family:monospace; font-weight:800; color:#334155;"><?= number_format((float)$p->contract_value, 2) ?></td>
                            <td>
                                <div style="display:flex; justify-content:space-between; font-size:0.75rem; font-family:monospace; font-weight:bold; margin-bottom:2px;">
                                    <span><?= $pct ?>%</span>
                                </div>
                                <div style="background:#e2e8f0; height:6px; border-radius:3px; overflow:hidden;">
                                    <div style="background:<?= $pct >= 100 ? '#059669' : 'var(--dash-indigo)' ?>; width:<?= $pct ?>%; height:100%;"></div>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <span style="font-size:0.75rem; font-weight:800; padding:2px 8px; border-radius:6px; background:#e0f2fe; color:#0284c7;">
                                    <?= htmlspecialchars($p->status) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    Chart.defaults.font.family = "'Cairo', 'Inter', sans-serif";

    new Chart(document.getElementById('budgetVsSpentChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($charts['budget_proj_names']) ?>,
            datasets: [
                { label: '<?= $isRtl ? "الميزانية" : "Budget" ?>', data: <?= json_encode($charts['budget_values']) ?>, backgroundColor: '#4f46e5', borderRadius: 6 },
                { label: '<?= $isRtl ? "المنصرف" : "Spent" ?>', data: <?= json_encode($charts['spent_values']) ?>, backgroundColor: '#ea580c', borderRadius: 6 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
    });

    new Chart(document.getElementById('statusPieChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($charts['status_labels']) ?>,
            datasets: [{ data: <?= json_encode($charts['status_counts']) ?>, backgroundColor: ['#0284c7', '#4f46e5', '#d97706', '#059669', '#dc2626'], borderWidth: 2 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('monthlyClaimsCostsChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode($charts['monthly_labels']) ?>,
            datasets: [
                { label: '<?= $isRtl ? "المستخلصات" : "Claims" ?>', data: <?= json_encode($charts['monthly_claims']) ?>, borderColor: '#059669', backgroundColor: 'rgba(5, 150, 105, 0.08)', borderWidth: 3, fill: true, tension: 0.4 },
                { label: '<?= $isRtl ? "المصروفات" : "Costs" ?>', data: <?= json_encode($charts['monthly_costs']) ?>, borderColor: '#ea580c', backgroundColor: 'rgba(234, 88, 12, 0.08)', borderWidth: 3, fill: true, tension: 0.4 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
    });

    new Chart(document.getElementById('costCategoriesChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($charts['cost_cat_labels']) ?>,
            datasets: [{ label: '<?= $isRtl ? "إجمالي" : "Total" ?>', data: <?= json_encode($charts['cost_cat_values']) ?>, backgroundColor: ['#2563eb', '#8b5cf6', '#d97706', '#ea580c', '#0284c7'], borderRadius: 8 }]
        },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
});
</script>