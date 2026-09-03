<?php
// Path: resources/views/projects/contracts/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'عقود المشاريع والمقاولات', 'desc' => 'إدارة وتتبع العقود الرسمية للمشاريع، الاستشاريين والمقاولين الفرعيين وشروط الضمان.',
        'add_btn' => 'تسجيل عقد جديد', 'col_num' => 'رقم العقد', 'col_name' => 'عنوان العقد والمشروع',
        'col_type' => 'نوع العقد', 'col_dates' => 'تاريخ البداية / النهاية', 'col_val' => 'قيمة العقد الإجمالية',
        'col_status' => 'الحالة', 'col_actions' => 'إجراءات', 'empty' => 'لا توجد عقود مشاريع مسجلة بمواصفات البحث.',
        'search' => 'ابحث برقم العقد، عنوان العقد، اسم المشروع، أو المالك...', 'btn_search' => 'بحث', 'clear' => 'إلغاء',
        'stat_total' => 'إجمالي العقود', 'stat_val' => 'القيمة الكلية للعقود', 'stat_act' => 'عقود سارية', 'stat_ret' => 'متوسط الاستقطاع',
        'st_draft' => 'مسودة', 'st_active' => 'ساري', 'st_under_renewal' => 'قيد التجديد', 'st_completed' => 'مكتمل', 'st_suspended' => 'موقف مؤقتاً', 'st_terminated' => 'مفسوخ / ملغى',
        'tp_owner' => 'عقد المالك الرئيسي', 'tp_sub' => 'عقد مقاول فرعي', 'tp_cons' => 'عقد استشاري', 'tp_supp' => 'عقد توريد مواد',
        'start' => 'بدء:', 'end' => 'تسليم:', 'confirm_del' => 'هل أنت متأكد من حذف هذا العقد؟'
    ],
    'en' => [
        'title' => 'Project Contracts', 'desc' => 'Manage official project contracts, subcontracts, and warranty terms.',
        'add_btn' => 'Add Contract', 'col_num' => 'Contract No.', 'col_name' => 'Contract & Project',
        'col_type' => 'Contract Type', 'col_dates' => 'Start / End Date', 'col_val' => 'Total Contract Value',
        'col_status' => 'Status', 'col_actions' => 'Actions', 'empty' => 'No contracts found.',
        'search' => 'Search by contract no, title, project, or client...', 'btn_search' => 'Search', 'clear' => 'Clear',
        'stat_total' => 'Total Contracts', 'stat_val' => 'Total Contracts Value', 'stat_act' => 'Active Contracts', 'stat_ret' => 'Avg Retention',
        'st_draft' => 'Draft', 'st_active' => 'Active', 'st_under_renewal' => 'Under Renewal', 'st_completed' => 'Completed', 'st_suspended' => 'Suspended', 'st_terminated' => 'Terminated',
        'tp_owner' => 'Main Owner Contract', 'tp_sub' => 'Subcontractor', 'tp_cons' => 'Consultant Contract', 'tp_supp' => 'Supply Contract',
        'start' => 'Start:', 'end' => 'End:', 'confirm_del' => 'Confirm delete?'
    ]
][$isRtl ? 'ar' : 'en'];

$statusMap = [
    'draft' => ['label' => $t['st_draft'], 'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'ph-pencil-line'],
    'active' => ['label' => $t['st_active'], 'color' => '#be123c', 'bg' => '#fff1f2', 'icon' => 'ph-check-circle'],
    'under_renewal' => ['label' => $t['st_under_renewal'], 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-arrows-clockwise'],
    'completed' => ['label' => $t['st_completed'], 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-lock-key'],
    'suspended' => ['label' => $t['st_suspended'], 'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'icon' => 'ph-pause-circle'],
    'terminated' => ['label' => $t['st_terminated'], 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-x-circle'],
];

$typeMap = [
    'owner_contract' => ['label' => $t['tp_owner'], 'bg' => '#fff1f2', 'color' => '#be123c'],
    'subcontractor_contract' => ['label' => $t['tp_sub'], 'bg' => '#f5f3ff', 'color' => '#8b5cf6'],
    'consultant_contract' => ['label' => $t['tp_cons'], 'bg' => '#e0f2fe', 'color' => '#0284c7'],
    'supply_contract' => ['label' => $t['tp_supp'], 'bg' => '#ffedd5', 'color' => '#ea580c'],
];
?>

<style>
    :root { --c-pcontract: #be123c; --c-pcontract-dark: #9f1239; --c-pcontract-light: #fff1f2; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .pcontract-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .pcontract-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .pcontract-title-box { display: flex; align-items: center; gap: 16px; }
    .pcontract-icon { width: 48px; height: 48px; background: var(--c-pcontract-light); color: var(--c-pcontract); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(190, 18, 60, 0.15); }
    .pcontract-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-pcontract { background: linear-gradient(135deg, var(--c-pcontract), var(--c-pcontract-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(190, 18, 60, 0.25); transition: 0.2s;}
    .btn-pcontract:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(190, 18, 60, 0.35); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);}
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-pcontract-light); color: var(--c-pcontract); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase;}
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);}
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .form-control:focus { outline: none; border-color: var(--c-pcontract); }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .pcontract-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .pcontract-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .pcontract-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; transition: 0.2s;}
    .action-btn:hover { background: var(--c-pcontract-light); color: var(--c-pcontract); border-color: #fecdd3; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
</style>

<div class="pcontract-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="pcontract-header">
        <div class="pcontract-title-box">
            <div class="pcontract-icon"><i class="ph-duotone ph-file-search"></i></div>
            <div>
                <h2 class="pcontract-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/projects/contracts/create" class="btn-pcontract"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-files"></i></div><div class="kpi-info"><h4><?= $t['stat_total'] ?></h4><p><?= number_format($stats->total_contracts ?? 0) ?></p></div></div>
        <div class="kpi-card" style="border-bottom: 3px solid var(--c-pcontract);"><div class="kpi-icon" style="background:var(--c-pcontract-light); color:var(--c-pcontract);"><i class="ph-duotone ph-currency-circle-dollar"></i></div><div class="kpi-info"><h4 style="color:var(--c-pcontract);"><?= $t['stat_val'] ?></h4><p><?= number_format((float)($stats->total_value ?? 0), 2) ?> <span style="font-size:0.7rem; color:#64748b;"><?= $currency ?></span></p></div></div>
        <div class="kpi-card" style="border-bottom: 3px solid #059669;"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= $t['stat_act'] ?></h4><p><?= number_format($stats->active_count ?? 0) ?></p></div></div>
        <div class="kpi-card" style="border-bottom: 3px solid #2563eb;"><div class="kpi-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-duotone ph-percent"></i></div><div class="kpi-info"><h4 style="color:#2563eb;"><?= $t['stat_ret'] ?></h4><p><?= number_format((float)($stats->avg_retention ?? 0), 1) ?>%</p></div></div>
    </div>

    <form action="/ERP/projects/contracts" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="<?= $t['search'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="project_id" class="form-control" style="flex:1; min-width:160px;">
            <option value="">-- كل المشاريع --</option>
            <?php foreach($projects as $proj): ?>
                <option value="<?= $proj->id ?>" <?= ($projectId == $proj->id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($proj->name_ar) ?> (<?= htmlspecialchars($proj->code) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <select name="contract_type" class="form-control" style="flex:1; min-width:140px;">
            <option value="">-- <?= $t['col_type'] ?> --</option>
            <?php foreach($typeMap as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($typeFilter===$k)?'selected':'' ?>><?= $v['label'] ?></option>
            <?php endforeach; ?>
        </select>

        <select name="status" class="form-control" style="flex:1; min-width:130px;">
            <option value="">-- <?= $t['col_status'] ?> --</option>
            <?php foreach($statusMap as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($statusFilter===$k)?'selected':'' ?>><?= $v['label'] ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($projectId) || !empty($typeFilter) || !empty($statusFilter)): ?>
            <a href="/ERP/projects/contracts" class="btn-clear"><i class="ph-bold ph-x"></i> <?= $t['clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="pcontract-table">
                <thead>
                    <tr>
                        <th style="width: 13%;"><?= $t['col_num'] ?></th>
                        <th style="width: 25%;"><?= $t['col_name'] ?></th>
                        <th style="width: 14%;"><?= $t['col_type'] ?></th>
                        <th style="width: 14%;"><?= $t['col_dates'] ?></th>
                        <th style="width: 14%;"><?= $t['col_val'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contracts)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach ($contracts as $c): 
                        $st = $statusMap[$c->status] ?? $statusMap['active'];
                        $tp = $typeMap[$c->contract_type] ?? $typeMap['owner_contract'];
                        $cTitle = $isRtl ? ($c->title_ar ?: $c->title_en) : ($c->title_en ?: $c->title_ar);
                    ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: 900; color: var(--c-pcontract-dark); font-size: 0.95rem;">
                                <a href="/ERP/projects/contracts/<?= $c->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($c->contract_number) ?></a>
                            </td>
                            <td>
                                <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($cTitle) ?></div>
                                <div style="font-size: 0.78rem; color: var(--c-text-muted); margin-top:2px;"><i class="ph-bold ph-buildings"></i> <?= htmlspecialchars($c->project_name ?? '---') ?></div>
                            </td>
                            <td>
                                <span class="badge-status" style="background:<?= $tp['bg'] ?>; color:<?= $tp['color'] ?>;">
                                    <?= $tp['label'] ?>
                                </span>
                            </td>
                            <td style="font-family: monospace; font-size: 0.85rem; font-weight: 700; color: #334155;">
                                <div><b style="color:#475569;"><?= $t['start'] ?></b> <?= htmlspecialchars($c->start_date) ?></div>
                                <div style="color:#dc2626; margin-top:2px;"><b><?= $t['end'] ?></b> <?= htmlspecialchars($c->end_date ?: '---') ?></div>
                            </td>
                            <td style="font-family: monospace; font-weight: 900; color: var(--c-pcontract-dark); font-size: 1rem;">
                                <?= number_format((float)$c->contract_value, 2) ?> <span style="font-size:0.75rem; color:#64748b;"><?= $currency ?></span>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                    <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                                </span>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="/ERP/projects/contracts/<?= $c->id ?>" class="action-btn" title="عرض العقد بالكامل والطباعة"><i class="ph-bold ph-eye"></i></a>
                                <a href="/ERP/projects/contracts/<?= $c->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                <form action="/ERP/projects/contracts/<?= $c->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_del'] ?>');">
                                    <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&project_id=<?= urlencode($projectId) ?>&contract_type=<?= urlencode($typeFilter) ?>&status=<?= urlencode($statusFilter) ?>" style="width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: <?= $i == ($currentPage ?? 1) ? 'var(--c-pcontract)' : '#fff' ?>; color: <?= $i == ($currentPage ?? 1) ? '#fff' : 'var(--c-text-muted)' ?>; font-weight: 800; text-decoration:none;"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>