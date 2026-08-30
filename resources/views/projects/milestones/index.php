<?php
// Path: resources/views/projects/milestones/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$statusMap = [
    'pending' => ['label' => 'قيد الانتظار', 'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'ph-clock'],
    'in_progress' => ['label' => 'قيد التنفيذ', 'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'icon' => 'ph-gear-six'],
    'under_review' => ['label' => 'قيد المراجعة', 'color' => '#0284c7', 'bg' => '#e0f2fe', 'icon' => 'ph-eye'],
    'completed' => ['label' => 'مكتملة بنجاح', 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
    'delayed' => ['label' => 'متأخرة عن الموعد', 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-warning-circle'],
    'cancelled' => ['label' => 'ملغاة', 'color' => '#94a3b8', 'bg' => '#f8fafc', 'icon' => 'ph-x-circle'],
];

$priorityMap = [
    'low' => ['label' => 'منخفضة', 'color' => '#64748b', 'bg' => '#f1f5f9'],
    'medium' => ['label' => 'متوسطة', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
    'high' => ['label' => 'عالية', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'urgent' => ['label' => 'طائفة / عاجلة جداً', 'color' => '#dc2626', 'bg' => '#fef2f2'],
];
?>

<style>
    :root {
        --c-ms: #8b5cf6;
        --c-ms-dark: #7c3aed;
        --c-ms-light: #f5f3ff;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .ms-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .ms-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .ms-title-box { display: flex; align-items: center; gap: 16px; }
    .ms-icon { width: 48px; height: 48px; background: var(--c-ms-light); color: var(--c-ms); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(139, 92, 246, 0.15); }
    .ms-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-ms { background: linear-gradient(135deg, var(--c-ms), var(--c-ms-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-ms-light); color: var(--c-ms); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .ms-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .ms-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .ms-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-ms-light); color: var(--c-ms); border-color: #ddd6fe; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-ms); color: #ffffff; border-color: var(--c-ms); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
    
    .progress-bar-wrap { background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden; margin-top: 4px; width: 110px; }
    .progress-bar-fill { background: var(--c-ms); height: 100%; border-radius: 4px; }
</style>

<div class="ms-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="ms-header">
        <div class="ms-title-box">
            <div class="ms-icon"><i class="ph-duotone ph-flag-banner"></i></div>
            <div>
                <h2 class="ms-title">المراحل والمهام التنفيذية (Milestones & Tasks)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);">متابعة وتقسيم مراحل المشاريع وتعيين المهام الهندسية ونسب الإنجاز.</p>
            </div>
        </div>
        <a href="/ERP/projects/milestones/create" class="btn-ms"><i class="ph-bold ph-plus"></i> إضافة مرحلة / مهمة جديدة</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-list-checks"></i></div><div class="kpi-info"><h4>إجمالي المراكز والمهام</h4><p><?= number_format($stats->total_milestones ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#f5f3ff; color:#8b5cf6;"><i class="ph-duotone ph-gear-six"></i></div><div class="kpi-info"><h4 style="color:#8b5cf6;">مهام قيد التنفيذ</h4><p><?= number_format($stats->in_progress_count ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;">مهام مكتملة</h4><p><?= number_format($stats->completed_count ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-warning-circle"></i></div><div class="kpi-info"><h4 style="color:#dc2626;">مهام متأخرة</h4><p><?= number_format($stats->delayed_count ?? 0) ?></p></div></div>
    </div>

    <form action="/ERP/projects/milestones" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="ابحث بكود المرحلة، عنوان المهمة، المسند إليه، أو المشروع..." value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="project_id" class="form-control" style="flex:1; min-width:160px;">
            <option value="">-- كل المشاريع --</option>
            <?php foreach($projects as $proj): ?>
                <option value="<?= $proj->id ?>" <?= ($projectId == $proj->id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($proj->name_ar) ?> (<?= htmlspecialchars($proj->code) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value="">-- كل الحالات --</option>
            <option value="pending" <?= ($statusFilter==='pending')?'selected':'' ?>>قيد الانتظار</option>
            <option value="in_progress" <?= ($statusFilter==='in_progress')?'selected':'' ?>>قيد التنفيذ</option>
            <option value="under_review" <?= ($statusFilter==='under_review')?'selected':'' ?>>قيد المراجعة</option>
            <option value="completed" <?= ($statusFilter==='completed')?'selected':'' ?>>مكتملة</option>
            <option value="delayed" <?= ($statusFilter==='delayed')?'selected':'' ?>>متأخرة</option>
        </select>

        <select name="priority" class="form-control" style="flex:1; min-width:120px;">
            <option value="">-- الأولوية --</option>
            <option value="low" <?= ($priorityFilter==='low')?'selected':'' ?>>منخفضة</option>
            <option value="medium" <?= ($priorityFilter==='medium')?'selected':'' ?>>متوسطة</option>
            <option value="high" <?= ($priorityFilter==='high')?'selected':'' ?>>عالية</option>
            <option value="urgent" <?= ($priorityFilter==='urgent')?'selected':'' ?>>عاجلة جداً</option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> فلترة</button>
    </form>

    <div class="table-card">
        <table class="ms-table">
            <thead>
                <tr>
                    <th style="width: 12%;">كود المرحلة</th>
                    <th style="width: 25%;">اسم المهمة والمشروع</th>
                    <th style="width: 15%;">المسؤول والتنفيذ</th>
                    <th style="width: 14%;">الاستحقاق / البداية</th>
                    <th style="width: 12%;">الإنجاز</th>
                    <th style="width: 11%; text-align: center;">الأولوية</th>
                    <th style="width: 11%; text-align: center;">الحالة</th>
                    <th style="width: 10%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($milestones)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد مراحل أو مهام مسجلة بمواصفات البحث.</td></tr>
                <?php else: foreach ($milestones as $m): 
                    $st = $statusMap[$m->status] ?? $statusMap['pending'];
                    $pr = $priorityMap[$m->priority] ?? $priorityMap['medium'];
                    $pct = (float)($m->progress_percent ?? 0);
                    $isOverdue = (strtotime($m->due_date) < time() && !in_array($m->status, ['completed', 'cancelled']));
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-ms-dark); font-size: 0.95rem;">
                            <a href="/ERP/projects/milestones/<?= $m->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($m->milestone_code) ?></a>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($m->title_ar) ?></div>
                            <div style="font-size: 0.78rem; color: var(--c-text-muted);"><i class="ph-bold ph-buildings"></i> <?= htmlspecialchars($m->project_name ?? 'مشروع غير محدد') ?></div>
                        </td>
                        <td style="font-weight: 700; color: #334155;">
                            <div><i class="ph-bold ph-user"></i> <?= htmlspecialchars($m->assigned_to ?: 'غير مسند') ?></div>
                            <div style="font-size:0.75rem; color:#64748b; font-family:monospace;">تقديري: <?= number_format((float)$m->estimated_cost, 2) ?></div>
                        </td>
                        <td style="font-family: monospace; font-size: 0.82rem;">
                            <div><b>بدء:</b> <?= htmlspecialchars($m->start_date) ?></div>
                            <div style="color:<?= $isOverdue ? '#dc2626' : '#64748b' ?>; font-weight:<?= $isOverdue ? 'bold' : 'normal' ?>;">
                                <b>استحقاق:</b> <?= htmlspecialchars($m->due_date) ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-family:monospace; font-weight:900; color:var(--c-ms-dark); font-size:0.85rem;"><?= $pct ?>%</div>
                            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $pct ?>%; background:<?= $pct >= 100 ? '#059669' : 'var(--c-ms)' ?>;"></div></div>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $pr['bg'] ?>; color:<?= $pr['color'] ?>;">
                                <?= $pr['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $isOverdue ? '#fef2f2' : $st['bg'] ?>; color:<?= $isOverdue ? '#dc2626' : $st['color'] ?>;">
                                <i class="ph-bold <?= $isOverdue ? 'ph-warning-circle' : $st['icon'] ?>"></i> <?= $isOverdue ? 'متأخرة' : $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/projects/milestones/<?= $m->id ?>" class="action-btn" title="عرض التفاصيل"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/projects/milestones/<?= $m->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/projects/milestones/<?= $m->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذه المرحلة/المهمة؟');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&project_id=<?= urlencode($projectId) ?>&status=<?= urlencode($statusFilter) ?>&priority=<?= urlencode($priorityFilter) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>