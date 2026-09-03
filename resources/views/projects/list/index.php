<?php
// Path: resources/views/projects/list/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'سجل المشاريع والمقاولات', 'desc' => 'إدارة وتتبع عقود وتكاليف ونسب إنجاز كافة المشاريع والمواقع.',
        'add_btn' => 'إضافة مشروع جديد', 'col_code' => 'الكود', 'col_name' => 'المشروع والعميل',
        'col_val' => 'قيمة العقد', 'col_dates' => 'تاريخ البداية / النهاية', 'col_prog' => 'الإنجاز',
        'col_status' => 'الحالة', 'col_actions' => 'إجراءات', 'empty' => 'لا توجد مشاريع تطابق بحثك.',
        'search' => 'ابحث بكود المشروع أو اسمه...', 'btn_search' => 'بحث', 'clear' => 'إلغاء',
        'stat_total' => 'إجمالي المشاريع', 'stat_val' => 'إجمالي العقود', 'stat_prog' => 'مشاريع جارية', 'stat_comp' => 'مكتملة',
        'st_planning' => 'تخطيط', 'st_in_progress' => 'قيد التنفيذ', 'st_on_hold' => 'موقف', 'st_completed' => 'مكتمل', 'st_cancelled' => 'ملغى',
        'start' => 'بدء:', 'end' => 'انتهاء:', 'customer' => 'العميل:', 'confirm_del' => 'تأكيد الحذف؟'
    ],
    'en' => [
        'title' => 'Projects Directory', 'desc' => 'Manage and track contracts, costs, and progress of all projects.',
        'add_btn' => 'New Project', 'col_code' => 'Code', 'col_name' => 'Project & Client',
        'col_val' => 'Contract Value', 'col_dates' => 'Start / End Date', 'col_prog' => 'Progress',
        'col_status' => 'Status', 'col_actions' => 'Actions', 'empty' => 'No projects found.',
        'search' => 'Search by code or name...', 'btn_search' => 'Search', 'clear' => 'Clear',
        'stat_total' => 'Total Projects', 'stat_val' => 'Total Contracts', 'stat_prog' => 'In Progress', 'stat_comp' => 'Completed',
        'st_planning' => 'Planning', 'st_in_progress' => 'In Progress', 'st_on_hold' => 'On Hold', 'st_completed' => 'Completed', 'st_cancelled' => 'Cancelled',
        'start' => 'Start:', 'end' => 'End:', 'customer' => 'Client:', 'confirm_del' => 'Confirm delete?'
    ]
][$isRtl ? 'ar' : 'en'];

$statusMap = [
    'planning' => ['label' => $t['st_planning'], 'color' => '#6366f1', 'bg' => '#e0e7ff', 'icon' => 'ph-compass'],
    'in_progress' => ['label' => $t['st_in_progress'], 'color' => '#0284c7', 'bg' => '#e0f2fe', 'icon' => 'ph-gear-six'],
    'on_hold' => ['label' => $t['st_on_hold'], 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-pause-circle'],
    'completed' => ['label' => $t['st_completed'], 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
    'cancelled' => ['label' => $t['st_cancelled'], 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-x-circle'],
];
?>

<style>
    :root { --c-prj: #0284c7; --c-prj-dark: #0369a1; --c-prj-light: #e0f2fe; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .prj-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .prj-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .prj-title-box { display: flex; align-items: center; gap: 16px; }
    .prj-icon { width: 48px; height: 48px; background: var(--c-prj-light); color: var(--c-prj); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(2, 132, 199, 0.12); }
    .prj-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-prj { background: linear-gradient(135deg, var(--c-prj), var(--c-prj-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.2); transition: 0.2s;}
    .btn-prj:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(2, 132, 199, 0.3); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-prj-light); color: var(--c-prj); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase;}
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .form-control:focus { outline: none; border-color: var(--c-prj); }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }
    .btn-clear { background: #f1f5f9; color: var(--c-text-muted); border: 1px solid var(--c-border); padding: 10px 20px; border-radius: 8px; font-weight: 800; text-decoration: none; display: flex; align-items: center; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .prj-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .prj-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .prj-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .prj-table tr:hover td { background: #f8fafc; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; transition: 0.2s; }
    .action-btn:hover { background: var(--c-prj-light); color: var(--c-prj); border-color: #bae6fd; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; transition: 0.2s;}
    .page-link.active { background: var(--c-prj); color: #ffffff; border-color: var(--c-prj); }
    
    .progress-bar-wrap { background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden; margin-top: 4px; width: 110px; }
    .progress-bar-fill { background: var(--c-prj); height: 100%; border-radius: 4px; }
</style>

<div class="prj-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="prj-header">
        <div class="prj-title-box">
            <div class="prj-icon"><i class="ph-duotone ph-buildings"></i></div>
            <div>
                <h2 class="prj-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/projects/list/create" class="btn-prj"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-kanban"></i></div><div class="kpi-info"><h4><?= $t['stat_total'] ?></h4><p><?= number_format($stats->total_projects ?? 0) ?></p></div></div>
        <div class="kpi-card" style="border-bottom: 3px solid var(--c-prj);"><div class="kpi-icon" style="background:#e0f2fe; color:#0284c7;"><i class="ph-duotone ph-currency-circle-dollar"></i></div><div class="kpi-info"><h4 style="color:#0284c7;"><?= $t['stat_val'] ?></h4><p><?= number_format((float)($stats->total_value ?? 0), 2) ?> <span style="font-size:0.7rem; color:#64748b;"><?= $currency ?></span></p></div></div>
        <div class="kpi-card" style="border-bottom: 3px solid #2563eb;"><div class="kpi-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-duotone ph-gear-six"></i></div><div class="kpi-info"><h4 style="color:#2563eb;"><?= $t['stat_prog'] ?></h4><p><?= number_format($stats->in_progress_count ?? 0) ?></p></div></div>
        <div class="kpi-card" style="border-bottom: 3px solid #059669;"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= $t['stat_comp'] ?></h4><p><?= number_format($stats->completed_count ?? 0) ?></p></div></div>
    </div>

    <form action="/ERP/projects/list" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="<?= $t['search'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="status" class="form-control" style="flex:1; min-width:150px;">
            <option value="">-- <?= $t['col_status'] ?> --</option>
            <?php foreach($statusMap as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($statusFilter===$k)?'selected':'' ?>><?= $v['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($statusFilter)): ?>
            <a href="/ERP/projects/list" class="btn-clear"><i class="ph-bold ph-x"></i> <?= $t['clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="prj-table">
                <thead>
                    <tr>
                        <th style="width: 12%;"><?= $t['col_code'] ?></th>
                        <th style="width: 25%;"><?= $t['col_name'] ?></th>
                        <th style="width: 15%;"><?= $t['col_val'] ?></th>
                        <th style="width: 15%;"><?= $t['col_dates'] ?></th>
                        <th style="width: 13%;"><?= $t['col_prog'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projects)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach ($projects as $p): 
                        $st = $statusMap[$p->status] ?? $statusMap['in_progress'];
                        $pct = (float)($p->progress_percent ?? 0);
                        $pName = $isRtl ? ($p->name_ar ?: $p->name_en) : ($p->name_en ?: $p->name_ar);
                    ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: 900; color: var(--c-prj-dark); font-size: 0.95rem;">
                                <a href="/ERP/projects/list/<?= $p->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($p->code) ?></a>
                            </td>
                            <td>
                                <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($pName) ?></div>
                                <div style="font-size: 0.78rem; color: var(--c-text-muted); margin-top:2px;"><i class="ph-bold ph-user" style="color:#94a3b8;"></i> <?= $t['customer'] ?> <?= htmlspecialchars($p->customer_name ?? '---') ?></div>
                            </td>
                            <td style="font-family: monospace; font-weight: 900; color: var(--c-text-dark); font-size: 1.05rem;">
                                <?= number_format((float)$p->contract_value, 2) ?> <span style="font-size:0.75rem; color:#64748b;"><?= $currency ?></span>
                            </td>
                            <td style="font-family: monospace; font-size: 0.85rem;">
                                <div style="font-weight:600;"><b style="color:#475569;"><?= $t['start'] ?></b> <?= htmlspecialchars($p->start_date) ?></div>
                                <div style="color:#dc2626; font-weight:600; margin-top:2px;"><b><?= $t['end'] ?></b> <?= htmlspecialchars($p->end_date ?: '---') ?></div>
                            </td>
                            <td>
                                <div style="font-family:monospace; font-weight:900; color:var(--c-prj-dark); font-size:0.85rem;"><?= $pct ?>%</div>
                                <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $pct ?>%; background:<?= $pct >= 100 ? '#059669' : 'var(--c-prj)' ?>;"></div></div>
                            </td>
                            <td style="text-align: center;">
                                <span style="padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                    <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                                </span>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="/ERP/projects/list/<?= $p->id ?>" class="action-btn" title="عرض التفاصيل"><i class="ph-bold ph-eye"></i></a>
                                <a href="/ERP/projects/list/<?= $p->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                <form action="/ERP/projects/list/<?= $p->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_del'] ?>');">
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
        <div class="pagination">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>