<?php
// Path: resources/views/projects/milestones/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'المراحل والمهام التنفيذية', 'desc' => 'متابعة وتقسيم مراحل المشاريع وتعيين المهام الهندسية ونسب الإنجاز.',
        'add_btn' => 'إضافة مرحلة / مهمة', 'col_code' => 'الكود', 'col_name' => 'المهمة والمشروع',
        'col_assigned' => 'المسؤول والتنفيذ', 'col_dates' => 'الاستحقاق / البداية', 'col_prog' => 'الإنجاز',
        'col_priority' => 'الأولوية', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات', 'empty' => 'لا توجد مهام تطابق بحثك.',
        'search' => 'ابحث بكود المرحلة، عنوان المهمة، المسؤول، أو المشروع...', 'btn_search' => 'بحث', 'clear' => 'إلغاء',
        'stat_total' => 'إجمالي المهام', 'stat_prog' => 'مهام جارية', 'stat_comp' => 'مكتملة', 'stat_delay' => 'متأخرة',
        'st_pending' => 'قيد الانتظار', 'st_in_progress' => 'قيد التنفيذ', 'st_under_review' => 'مراجعة واستلام', 'st_completed' => 'مكتملة', 'st_delayed' => 'متأخرة', 'st_cancelled' => 'ملغاة',
        'pr_low' => 'منخفضة', 'pr_medium' => 'متوسطة', 'pr_high' => 'عالية', 'pr_urgent' => 'عاجلة جداً',
        'start' => 'بدء:', 'due' => 'استحقاق:', 'assigned' => 'مُسند إلى:', 'est_cost' => 'ميزانية المهمة:', 'confirm_del' => 'تأكيد الحذف؟'
    ],
    'en' => [
        'title' => 'Milestones & Tasks', 'desc' => 'Track project phases, assign engineering tasks, and monitor progress.',
        'add_btn' => 'Add Milestone / Task', 'col_code' => 'Code', 'col_name' => 'Task & Project',
        'col_assigned' => 'Assignee & Budget', 'col_dates' => 'Due / Start Date', 'col_prog' => 'Progress',
        'col_priority' => 'Priority', 'col_status' => 'Status', 'col_actions' => 'Actions', 'empty' => 'No milestones found.',
        'search' => 'Search by code, title, assignee, or project...', 'btn_search' => 'Search', 'clear' => 'Clear',
        'stat_total' => 'Total Tasks', 'stat_prog' => 'In Progress', 'stat_comp' => 'Completed', 'stat_delay' => 'Delayed',
        'st_pending' => 'Pending', 'st_in_progress' => 'In Progress', 'st_under_review' => 'Under Review', 'st_completed' => 'Completed', 'st_delayed' => 'Delayed', 'st_cancelled' => 'Cancelled',
        'pr_low' => 'Low', 'pr_medium' => 'Medium', 'pr_high' => 'High', 'pr_urgent' => 'Urgent',
        'start' => 'Start:', 'due' => 'Due:', 'assigned' => 'Assigned To:', 'est_cost' => 'Task Budget:', 'confirm_del' => 'Confirm delete?'
    ]
][$isRtl ? 'ar' : 'en'];

$statusMap = [
    'pending' => ['label' => $t['st_pending'], 'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'ph-clock'],
    'in_progress' => ['label' => $t['st_in_progress'], 'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'icon' => 'ph-gear-six'],
    'under_review' => ['label' => $t['st_under_review'], 'color' => '#0284c7', 'bg' => '#e0f2fe', 'icon' => 'ph-eye'],
    'completed' => ['label' => $t['st_completed'], 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
    'delayed' => ['label' => $t['st_delayed'], 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-warning-circle'],
    'cancelled' => ['label' => $t['st_cancelled'], 'color' => '#94a3b8', 'bg' => '#f8fafc', 'icon' => 'ph-x-circle'],
];

$priorityMap = [
    'low' => ['label' => $t['pr_low'], 'color' => '#64748b', 'bg' => '#f1f5f9'],
    'medium' => ['label' => $t['pr_medium'], 'color' => '#0284c7', 'bg' => '#e0f2fe'],
    'high' => ['label' => $t['pr_high'], 'color' => '#d97706', 'bg' => '#fef3c7'],
    'urgent' => ['label' => $t['pr_urgent'], 'color' => '#dc2626', 'bg' => '#fef2f2'],
];
?>

<style>
    :root { --c-ms: #8b5cf6; --c-ms-dark: #7c3aed; --c-ms-light: #f5f3ff; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .ms-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .ms-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .ms-title-box { display: flex; align-items: center; gap: 16px; }
    .ms-icon { width: 48px; height: 48px; background: var(--c-ms-light); color: var(--c-ms); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(139, 92, 246, 0.15); }
    .ms-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-ms { background: linear-gradient(135deg, var(--c-ms), var(--c-ms-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25); transition: 0.2s;}
    .btn-ms:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(139, 92, 246, 0.35); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);}
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-ms-light); color: var(--c-ms); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase;}
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);}
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .form-control:focus { outline: none; border-color: var(--c-ms); }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }
    .btn-clear { background: #f1f5f9; color: var(--c-text-muted); border: 1px solid var(--c-border); padding: 10px 20px; border-radius: 8px; font-weight: 800; text-decoration: none; display: flex; align-items: center; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
    .ms-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .ms-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .ms-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .ms-table tr:hover td { background: #f8fafc; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; transition: 0.2s;}
    .action-btn:hover { background: var(--c-ms-light); color: var(--c-ms); border-color: #ddd6fe; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .progress-bar-wrap { background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden; margin-top: 4px; width: 110px; }
    .progress-bar-fill { background: var(--c-ms); height: 100%; border-radius: 4px; }
</style>

<div class="ms-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="ms-header">
        <div class="ms-title-box">
            <div class="ms-icon"><i class="ph-duotone ph-flag-banner"></i></div>
            <div>
                <h2 class="ms-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/projects/milestones/create" class="btn-ms"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-list-checks"></i></div><div class="kpi-info"><h4><?= $t['stat_total'] ?></h4><p><?= number_format($stats->total_milestones ?? 0) ?></p></div></div>
        <div class="kpi-card" style="border-bottom: 3px solid var(--c-ms);"><div class="kpi-icon" style="background:var(--c-ms-light); color:var(--c-ms);"><i class="ph-duotone ph-gear-six"></i></div><div class="kpi-info"><h4 style="color:var(--c-ms);"><?= $t['stat_prog'] ?></h4><p><?= number_format($stats->in_progress_count ?? 0) ?></p></div></div>
        <div class="kpi-card" style="border-bottom: 3px solid #059669;"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= $t['stat_comp'] ?></h4><p><?= number_format($stats->completed_count ?? 0) ?></p></div></div>
        <div class="kpi-card" style="border-bottom: 3px solid #dc2626;"><div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-warning-circle"></i></div><div class="kpi-info"><h4 style="color:#dc2626;"><?= $t['stat_delay'] ?></h4><p><?= number_format($stats->delayed_count ?? 0) ?></p></div></div>
    </div>

    <form action="/ERP/projects/milestones" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="<?= $t['search'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="project_id" class="form-control" style="flex:1; min-width:160px;">
            <option value="">-- كل المشاريع --</option>
            <?php foreach($projects as $proj): ?>
                <option value="<?= $proj->id ?>" <?= ($projectId == $proj->id) ? 'selected' : '' ?>><?= htmlspecialchars($proj->name_ar) ?> (<?= htmlspecialchars($proj->code) ?>)</option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value="">-- <?= $t['col_status'] ?> --</option>
            <?php foreach($statusMap as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($statusFilter===$k)?'selected':'' ?>><?= $v['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <select name="priority" class="form-control" style="flex:1; min-width:120px;">
            <option value="">-- <?= $t['col_priority'] ?> --</option>
            <?php foreach($priorityMap as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($priorityFilter===$k)?'selected':'' ?>><?= $v['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($projectId) || !empty($statusFilter) || !empty($priorityFilter)): ?>
            <a href="/ERP/projects/milestones" class="btn-clear"><i class="ph-bold ph-x"></i> <?= $t['clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="ms-table">
                <thead>
                    <tr>
                        <th style="width: 12%;"><?= $t['col_code'] ?></th>
                        <th style="width: 25%;"><?= $t['col_name'] ?></th>
                        <th style="width: 15%;"><?= $t['col_assigned'] ?></th>
                        <th style="width: 14%;"><?= $t['col_dates'] ?></th>
                        <th style="width: 12%;"><?= $t['col_prog'] ?></th>
                        <th style="width: 11%; text-align: center;"><?= $t['col_priority'] ?></th>
                        <th style="width: 11%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($milestones)): ?>
                        <tr><td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach ($milestones as $m): 
                        $st = $statusMap[$m->status] ?? $statusMap['pending'];
                        $pr = $priorityMap[$m->priority] ?? $priorityMap['medium'];
                        $pct = (float)($m->progress_percent ?? 0);
                        $isOverdue = (strtotime($m->due_date) < time() && !in_array($m->status, ['completed', 'cancelled']));
                        $mTitle = $isRtl ? ($m->title_ar ?: $m->title_en) : ($m->title_en ?: $m->title_ar);
                    ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: 900; color: var(--c-ms-dark); font-size: 0.95rem;">
                                <a href="/ERP/projects/milestones/<?= $m->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($m->milestone_code) ?></a>
                            </td>
                            <td>
                                <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($mTitle) ?></div>
                                <div style="font-size: 0.78rem; color: var(--c-text-muted); margin-top:2px;"><i class="ph-bold ph-buildings" style="color:#94a3b8;"></i> <?= htmlspecialchars($m->project_name ?? '---') ?></div>
                            </td>
                            <td style="font-weight: 700; color: #334155;">
                                <div><i class="ph-bold ph-user" style="color:#94a3b8;"></i> <?= htmlspecialchars($m->assigned_to ?: '---') ?></div>
                                <div style="font-size:0.75rem; color:#64748b; font-family:monospace; margin-top:2px;">
                                    <?= number_format((float)$m->estimated_cost, 2) ?> <?= $currency ?>
                                </div>
                            </td>
                            <td style="font-family: monospace; font-size: 0.82rem;">
                                <div><b><?= $t['start'] ?></b> <?= htmlspecialchars($m->start_date) ?></div>
                                <div style="color:<?= $isOverdue ? '#dc2626' : '#64748b' ?>; font-weight:<?= $isOverdue ? 'bold' : 'normal' ?>; margin-top:2px;">
                                    <b><?= $t['due'] ?></b> <?= htmlspecialchars($m->due_date) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-family:monospace; font-weight:900; color:var(--c-ms-dark); font-size:0.85rem;"><?= $pct ?>%</div>
                                <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $pct ?>%; background:<?= $pct >= 100 ? '#059669' : 'var(--c-ms)' ?>;"></div></div>
                            </td>
                            <td style="text-align: center;">
                                <span style="padding: 2px 8px; border-radius: 6px; font-weight: 800; font-size: 0.7rem; background:<?= $pr['bg'] ?>; color:<?= $pr['color'] ?>;">
                                    <?= $pr['label'] ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <span style="padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; background:<?= $isOverdue ? '#fef2f2' : $st['bg'] ?>; color:<?= $isOverdue ? '#dc2626' : $st['color'] ?>;">
                                    <i class="ph-bold <?= $isOverdue ? 'ph-warning-circle' : $st['icon'] ?>"></i> <?= $isOverdue ? $t['st_delayed'] : $st['label'] ?>
                                </span>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="/ERP/projects/milestones/<?= $m->id ?>" class="action-btn" title="عرض التفاصيل"><i class="ph-bold ph-eye"></i></a>
                                <a href="/ERP/projects/milestones/<?= $m->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                <form action="/ERP/projects/milestones/<?= $m->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_del'] ?>');">
                                    <button type="submit" class="action-btn delete"><i class="ph-bold ph-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div style="display:flex; justify-content:center; gap:8px; margin-top:24px;">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&project_id=<?= urlencode($projectId) ?>&status=<?= urlencode($statusFilter) ?>&priority=<?= urlencode($priorityFilter) ?>" style="width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: <?= $i == ($currentPage ?? 1) ? 'var(--c-ms)' : '#fff' ?>; color: <?= $i == ($currentPage ?? 1) ? '#fff' : 'var(--c-text-muted)' ?>; font-weight: 800; text-decoration:none;"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>