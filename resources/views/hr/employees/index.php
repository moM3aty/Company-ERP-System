<?php
// Path: resources/views/hr/employees/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$statusMap = [
    'active' => ['label' => 'نشط بالمؤسسة', 'color' => '#2563eb', 'bg' => '#dbeafe', 'icon' => 'ph-check-circle'],
    'on_leave' => ['label' => 'في إجازة', 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-airplane-takeoff'],
    'resigned' => ['label' => 'مستقيل', 'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'ph-user-minus'],
    'terminated' => ['label' => 'منهي خدماته', 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-x-circle'],
];

$typeMap = [
    'full_time' => 'دوام كامل',
    'part_time' => 'دوام جزئي',
    'contract' => 'عقد محدد',
    'probation' => 'تحت التجربة'
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
    .emp-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .emp-title-box { display: flex; align-items: center; gap: 16px; }
    .emp-icon { width: 48px; height: 48px; background: var(--c-emp-light); color: var(--c-emp); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.15); }
    .emp-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-emp { background: linear-gradient(135deg, var(--c-emp), var(--c-emp-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); }

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
</style>

<div class="emp-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="emp-header">
        <div class="emp-title-box">
            <div class="emp-icon"><i class="ph-duotone ph-users-three"></i></div>
            <div>
                <h2 class="emp-title">دليل الموظفين وشؤون العاملين (Employees Directory)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);">السجل الشامل لبيانات الموظفين، الوظائف، والرواتب الأساسية.</p>
            </div>
        </div>
        <a href="/ERP/hr/employees/create" class="btn-emp"><i class="ph-bold ph-plus"></i> إضافة موظف جديد</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-users"></i></div><div class="kpi-info"><h4>إجمالي كادر الموظفين</h4><p><?= number_format($stats->total_emps ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#dbeafe; color:#2563eb;"><i class="ph-duotone ph-user-check"></i></div><div class="kpi-info"><h4 style="color:#2563eb;">موظفين على رأس العمل</h4><p><?= number_format($stats->active_emps ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-airplane-takeoff"></i></div><div class="kpi-info"><h4 style="color:#d97706;">موظفين في إجازة</h4><p><?= number_format($stats->on_leave_emps ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fae8ff; color:#c026d3;"><i class="ph-duotone ph-clock"></i></div><div class="kpi-info"><h4 style="color:#c026d3;">فترة التجربة</h4><p><?= number_format($stats->probation_emps ?? 0) ?></p></div></div>
    </div>

    <form action="/ERP/hr/employees" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:220px;" placeholder="ابحث بكود الموظف، الاسم، الهوية، الهاتف، البريد..." value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="department_id" class="form-control" style="flex:1; min-width:160px;">
            <option value="">-- كل الإدارات --</option>
            <?php foreach($departments as $dept): ?>
                <option value="<?= $dept->id ?>" <?= ($deptFilter == $dept->id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dept->name_ar) ?> (<?= htmlspecialchars($dept->code) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value="">-- كل الحالات --</option>
            <option value="active" <?= ($statusFilter==='active')?'selected':'' ?>>على رأس العمل</option>
            <option value="on_leave" <?= ($statusFilter==='on_leave')?'selected':'' ?>>في إجازة</option>
            <option value="resigned" <?= ($statusFilter==='resigned')?'selected':'' ?>>مستقيل</option>
            <option value="terminated" <?= ($statusFilter==='terminated')?'selected':'' ?>>منهي خدماته</option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> فلترة</button>
    </form>

    <div class="table-card">
        <table class="emp-table">
            <thead>
                <tr>
                    <th style="width: 10%;">الكود</th>
                    <th style="width: 24%;">اسم الموظف</th>
                    <th style="width: 20%;">الإدارة والمسمى الوظيفي</th>
                    <th style="width: 16%;">تاريخ المباشرة والتوعية</th>
                    <th style="width: 14%;">الراتب الأساسي</th>
                    <th style="width: 8%; text-align: center;">الحالة</th>
                    <th style="width: 8%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا يوجد موظفين مسجلين.</td></tr>
                <?php else: foreach ($employees as $e): 
                    $st = $statusMap[$e->status] ?? $statusMap['active'];
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-emp-dark); font-size: 0.95rem;">
                            <a href="/ERP/hr/employees/<?= $e->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($e->emp_code) ?></a>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($e->name_ar) ?></div>
                            <div style="font-size: 0.78rem; color: var(--c-text-muted);"><i class="ph-bold ph-phone"></i> <?= htmlspecialchars($e->phone ?: 'لا يوجد هاتف') ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><i class="ph-bold ph-sitemap" style="color:var(--c-emp);"></i> <?= htmlspecialchars($e->department_name_ar ?: 'غير محدد') ?></div>
                            <div style="font-size: 0.78rem; color: var(--c-text-muted);"><?= htmlspecialchars($e->designation_title_ar ?: 'بدون مسمى') ?></div>
                        </td>
                        <td style="font-family: monospace; font-size: 0.85rem; font-weight: 700;">
                            <div><?= htmlspecialchars($e->joining_date) ?></div>
                            <div style="font-size: 0.75rem; color: #0284c7;"><?= $typeMap[$e->employment_type] ?? 'دوام كامل' ?></div>
                        </td>
                        <td style="font-family: monospace; font-weight: 900; color: #059669; font-size: 0.98rem;">
                            <?= number_format((float)$e->basic_salary, 2) ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/hr/employees/<?= $e->id ?>" class="action-btn" title="عرض البطاقة"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/hr/employees/<?= $e->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/hr/employees/<?= $e->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا الموظف؟');">
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