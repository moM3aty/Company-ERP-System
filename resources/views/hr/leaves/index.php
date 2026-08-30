<?php
// Path: resources/views/hr/leaves/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$statusMap = [
    'pending' => ['label' => 'قيد المراجعة', 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-clock-countdown'],
    'approved' => ['label' => 'مقبولة ومُعتمدة', 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
    'rejected' => ['label' => 'مرفوضة', 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-x-circle'],
];

$typeMap = [
    'annual' => 'إجازة سنوية',
    'sick' => 'إجازة مرضية',
    'unpaid' => 'بدون راتب',
    'maternity' => 'إجازة وضع/أمومة',
    'other' => 'إجازة أخرى'
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
    .leave-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .leave-title-box { display: flex; align-items: center; gap: 16px; }
    .leave-icon { width: 48px; height: 48px; background: var(--c-leave-light); color: var(--c-leave); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(124, 58, 237, 0.15); }
    .leave-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-leave { background: linear-gradient(135deg, var(--c-leave), var(--c-leave-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25); }

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
</style>

<div class="leave-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="leave-header">
        <div class="leave-title-box">
            <div class="leave-icon"><i class="ph-duotone ph-airplane-takeoff"></i></div>
            <div>
                <h2 class="leave-title">طلبات ومستحقات الإجازات (Leaves & Requests)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);">إدارة وتتبع طلبات إجازات الموظفين واعتمادها.</p>
            </div>
        </div>
        <a href="/ERP/hr/leaves/create" class="btn-leave"><i class="ph-bold ph-plus"></i> تقديم طلب إجازة جديد</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-files"></i></div><div class="kpi-info"><h4>إجمالي الطلبات</h4><p><?= number_format($stats->total_leaves ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-clock-countdown"></i></div><div class="kpi-info"><h4 style="color:#d97706;">قيد المراجعة</h4><p><?= number_format($stats->pending_leaves ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;">طلبات مقبولة</h4><p><?= number_format($stats->approved_leaves ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-x-circle"></i></div><div class="kpi-info"><h4 style="color:#dc2626;">طلبات مرفوضة</h4><p><?= number_format($stats->rejected_leaves ?? 0) ?></p></div></div>
    </div>

    <form action="/ERP/hr/leaves" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="ابحث بكود الموظف، أو اسمه..." value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="leave_type" class="form-control" style="flex:1; min-width:140px;">
            <option value="">-- كل أنواع الإجازات --</option>
            <option value="annual" <?= ($typeFilter==='annual')?'selected':'' ?>>سنوية</option>
            <option value="sick" <?= ($typeFilter==='sick')?'selected':'' ?>>مرضية</option>
            <option value="unpaid" <?= ($typeFilter==='unpaid')?'selected':'' ?>>بدون راتب</option>
            <option value="maternity" <?= ($typeFilter==='maternity')?'selected':'' ?>>وضع/أمومة</option>
            <option value="other" <?= ($typeFilter==='other')?'selected':'' ?>>أخرى</option>
        </select>

        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value="">-- كل الحالات --</option>
            <option value="pending" <?= ($statusFilter==='pending')?'selected':'' ?>>قيد المراجعة</option>
            <option value="approved" <?= ($statusFilter==='approved')?'selected':'' ?>>مقبولة</option>
            <option value="rejected" <?= ($statusFilter==='rejected')?'selected':'' ?>>مرفوضة</option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> فلترة</button>
    </form>

    <div class="table-card">
        <table class="leave-table">
            <thead>
                <tr>
                    <th style="width: 25%;">اسم الموظف والإدارة</th>
                    <th style="width: 15%;">نوع الإجازة</th>
                    <th style="width: 20%;">فترة الإجازة</th>
                    <th style="width: 12%; text-align: center;">عدد الأيام</th>
                    <th style="width: 12%; text-align: center;">الحالة</th>
                    <th style="width: 16%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leaves)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد طلبات إجازة مسجلة.</td></tr>
                <?php else: foreach ($leaves as $l): 
                    $st = $statusMap[$l->status] ?? $statusMap['pending'];
                ?>
                    <tr>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($l->employee_name ?: 'مجهول') ?></div>
                            <div style="font-size: 0.78rem; color: var(--c-leave-dark); font-family:monospace; font-weight:bold;"><?= htmlspecialchars($l->emp_code) ?> - <?= htmlspecialchars($l->dept_name ?: 'عام') ?></div>
                        </td>
                        <td style="font-weight: 800; color: #475569;">
                            <span style="background:#f1f5f9; padding:4px 8px; border-radius:6px; font-size:0.8rem;"><?= $typeMap[$l->leave_type] ?? $l->leave_type ?></span>
                        </td>
                        <td style="font-family: monospace; font-size: 0.85rem; font-weight: 700;">
                            <div><span style="color:#64748b;">من:</span> <?= htmlspecialchars($l->start_date) ?></div>
                            <div><span style="color:#64748b;">إلى:</span> <?= htmlspecialchars($l->end_date) ?></div>
                        </td>
                        <td style="text-align: center; font-family: monospace; font-weight: 900; color: var(--c-leave-dark); font-size: 1.05rem;">
                            <?= (int)$l->days_count ?> يوم
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/hr/leaves/<?= $l->id ?>" class="action-btn" title="عرض التفاصيل"><i class="ph-bold ph-eye"></i></a>
                            
                            <?php if($l->status === 'pending'): ?>
                                <form action="/ERP/hr/leaves/<?= $l->id ?>/approve" method="POST" style="display:inline;" onsubmit="return confirm('هل تريد قبول واقرار طلب الإجازة؟');">
                                    <button type="submit" class="action-btn approve" title="قبول"><i class="ph-bold ph-check"></i></button>
                                </form>
                                <form action="/ERP/hr/leaves/<?= $l->id ?>/reject" method="POST" style="display:inline;" onsubmit="return confirm('هل تريد رفض طلب الإجازة؟');">
                                    <button type="submit" class="action-btn reject" title="رفض"><i class="ph-bold ph-x"></i></button>
                                </form>
                            <?php endif; ?>

                            <form action="/ERP/hr/leaves/<?= $l->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا الطلب؟');">
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