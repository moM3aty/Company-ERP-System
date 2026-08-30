<?php
// Path: resources/views/projects/list/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$statusMap = [
    'planning' => ['label' => 'تخطيط وتجهيز', 'color' => '#6366f1', 'bg' => '#e0e7ff', 'icon' => 'ph-compass'],
    'in_progress' => ['label' => 'قيد التنفيذ', 'color' => '#0284c7', 'bg' => '#e0f2fe', 'icon' => 'ph-gear-six'],
    'on_hold' => ['label' => 'موقف مؤقتاً', 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-pause-circle'],
    'completed' => ['label' => 'مكتمل بنجاح', 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
    'cancelled' => ['label' => 'ملغى', 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-x-circle'],
];
?>

<style>
    :root {
        --c-prj: #0284c7;
        --c-prj-dark: #0369a1;
        --c-prj-light: #e0f2fe;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .prj-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .prj-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .prj-title-box { display: flex; align-items: center; gap: 16px; }
    .prj-icon { width: 48px; height: 48px; background: var(--c-prj-light); color: var(--c-prj); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(2, 132, 199, 0.12); }
    .prj-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-prj { background: linear-gradient(135deg, var(--c-prj), var(--c-prj-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.2); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-prj-light); color: var(--c-prj); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .prj-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .prj-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .prj-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-prj-light); color: var(--c-prj); border-color: #bae6fd; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-prj); color: #ffffff; border-color: var(--c-prj); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
    
    .progress-bar-wrap { background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden; margin-top: 4px; width: 110px; }
    .progress-bar-fill { background: var(--c-prj); height: 100%; border-radius: 4px; }
</style>

<div class="prj-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="prj-header">
        <div class="prj-title-box">
            <div class="prj-icon"><i class="ph-duotone ph-buildings"></i></div>
            <div>
                <h2 class="prj-title">سجل المشاريع والمقاولات (Projects Directory)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);">إدارة وتتبع عقود وتكاليف ونسب إنجاز كافة المشاريع والمواقع.</p>
            </div>
        </div>
        <a href="/ERP/projects/list/create" class="btn-prj"><i class="ph-bold ph-plus"></i> إضافة مشروع جديد</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-kanban"></i></div><div class="kpi-info"><h4>إجمالي المشاريع</h4><p><?= number_format($stats->total_projects ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#e0f2fe; color:#0284c7;"><i class="ph-duotone ph-currency-circle-dollar"></i></div><div class="kpi-info"><h4 style="color:#0284c7;">إجمالي قيمة العقود</h4><p><?= number_format((float)($stats->total_value ?? 0), 2) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-duotone ph-gear-six"></i></div><div class="kpi-info"><h4 style="color:#2563eb;">مشاريع قيد التنفيذ</h4><p><?= number_format($stats->in_progress_count ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;">المشاريع المكتملة</h4><p><?= number_format($stats->completed_count ?? 0) ?></p></div></div>
    </div>

    <form action="/ERP/projects/list" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="ابحث بكود المشروع، اسمه بالعربي، أو اسم العميل..." value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="status" class="form-control" style="flex:1; min-width:150px;">
            <option value="">-- كل الحالات --</option>
            <option value="planning" <?= ($statusFilter==='planning')?'selected':'' ?>>تخطيط وتجهيز</option>
            <option value="in_progress" <?= ($statusFilter==='in_progress')?'selected':'' ?>>قيد التنفيذ</option>
            <option value="on_hold" <?= ($statusFilter==='on_hold')?'selected':'' ?>>موقف مؤقتاً</option>
            <option value="completed" <?= ($statusFilter==='completed')?'selected':'' ?>>مكتمل</option>
            <option value="cancelled" <?= ($statusFilter==='cancelled')?'selected':'' ?>>ملغى</option>
        </select>
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($fromDate ?? '') ?>" title="تاريخ البداية من">
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($toDate ?? '') ?>" title="تاريخ البداية إلى">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> فلترة</button>
    </form>

    <div class="table-card">
        <table class="prj-table">
            <thead>
                <tr>
                    <th style="width: 12%;">كود المشروع</th>
                    <th style="width: 25%;">اسم المشروع والعميل</th>
                    <th style="width: 15%;">قيمة العقد</th>
                    <th style="width: 15%;">تاريخ البداية / النهاية</th>
                    <th style="width: 13%;">نسبة الإنجاز</th>
                    <th style="width: 10%; text-align: center;">الحالة</th>
                    <th style="width: 10%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد مشاريع مسجلة بمواصفات البحث.</td></tr>
                <?php else: foreach ($projects as $p): 
                    $st = $statusMap[$p->status] ?? $statusMap['in_progress'];
                    $pct = (float)($p->progress_percent ?? 0);
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-prj-dark); font-size: 0.95rem;">
                            <a href="/ERP/projects/list/<?= $p->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($p->code) ?></a>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($p->name_ar) ?></div>
                            <div style="font-size: 0.78rem; color: var(--c-text-muted);"><i class="ph-bold ph-user"></i> العميل: <?= htmlspecialchars($p->customer_name ?? 'عميل غير محدد') ?></div>
                        </td>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-text-dark); font-size: 0.95rem;">
                            <?= number_format((float)$p->contract_value, 2) ?>
                        </td>
                        <td style="font-family: monospace; font-size: 0.82rem;">
                            <div><b>بدء:</b> <?= htmlspecialchars($p->start_date) ?></div>
                            <div style="color:#64748b;"><b>تسليم:</b> <?= htmlspecialchars($p->end_date ?: 'غير محدد') ?></div>
                        </td>
                        <td>
                            <div style="font-family:monospace; font-weight:900; color:var(--c-prj-dark); font-size:0.85rem;"><?= $pct ?>%</div>
                            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $pct ?>%; background:<?= $pct >= 100 ? '#059669' : 'var(--c-prj)' ?>;"></div></div>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/projects/list/<?= $p->id ?>" class="action-btn" title="عرض التفاصيل"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/projects/list/<?= $p->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/projects/list/<?= $p->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا المشروع؟');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($fromDate) ?>&end_date=<?= urlencode($toDate) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>