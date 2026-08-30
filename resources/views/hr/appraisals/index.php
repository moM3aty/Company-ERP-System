<?php
// Path: resources/views/hr/appraisals/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$statusMap = [
    'draft' => ['label' => 'مسودة', 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-pencil-line'],
    'submitted' => ['label' => 'مقدم للمراجعة', 'color' => '#0284c7', 'bg' => '#e0f2fe', 'icon' => 'ph-paper-plane-tilt'],
    'approved' => ['label' => 'مُعتمد رسمياً', 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
];
?>

<style>
    :root {
        --c-appr: #ea580c;
        --c-appr-dark: #c2410c;
        --c-appr-light: #ffedd5;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .appr-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .appr-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .appr-title-box { display: flex; align-items: center; gap: 16px; }
    .appr-icon { width: 48px; height: 48px; background: var(--c-appr-light); color: var(--c-appr); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(234, 88, 12, 0.15); }
    .appr-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-appr { background: linear-gradient(135deg, var(--c-appr), var(--c-appr-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-appr-light); color: var(--c-appr); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .appr-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .appr-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .appr-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-appr-light); color: var(--c-appr); border-color: #fed7aa; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-appr); color: #ffffff; border-color: var(--c-appr); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
</style>

<div class="appr-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="appr-header">
        <div class="appr-title-box">
            <div class="appr-icon"><i class="ph-duotone ph-chart-line-up"></i></div>
            <div>
                <h2 class="appr-title">تقييم الأداء الوظيفي (Performance Appraisals)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);">إدارة التقييمات السنوية، تقارير الأداء، والدرجات التقديرية.</p>
            </div>
        </div>
        <a href="/ERP/hr/appraisals/create" class="btn-appr"><i class="ph-bold ph-plus"></i> إجراء تقييم جديد</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-clipboard-text"></i></div><div class="kpi-info"><h4>إجمالي التقييمات</h4><p><?= number_format($stats->total_appraisals ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ffedd5; color:#ea580c;"><i class="ph-duotone ph-star"></i></div><div class="kpi-info"><h4 style="color:#ea580c;">متوسط التقييم العام</h4><p><?= number_format((float)($stats->avg_score ?? 0), 1) ?> %</p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;">تقييمات معتمدة</h4><p><?= number_format($stats->approved_count ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-pencil-line"></i></div><div class="kpi-info"><h4 style="color:#d97706;">مسودات قيد الإعداد</h4><p><?= number_format($stats->draft_count ?? 0) ?></p></div></div>
    </div>

    <form action="/ERP/hr/appraisals" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="ابحث بكود التقييم، اسم الموظف، أو المُقَيِّم..." value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value="">-- كل الحالات --</option>
            <option value="draft" <?= ($statusFilter==='draft')?'selected':'' ?>>مسودة</option>
            <option value="submitted" <?= ($statusFilter==='submitted')?'selected':'' ?>>مقدم للمراجعة</option>
            <option value="approved" <?= ($statusFilter==='approved')?'selected':'' ?>>معتمد</option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> فلترة</button>
    </form>

    <div class="table-card">
        <table class="appr-table">
            <thead>
                <tr>
                    <th style="width: 12%;">كود التقييم</th>
                    <th style="width: 25%;">اسم الموظف والإدارة</th>
                    <th style="width: 18%;">الفترة والتاريخ</th>
                    <th style="width: 15%;">النتيجة / التقدير</th>
                    <th style="width: 12%;">المُقَيِّم المسؤول</th>
                    <th style="width: 8%; text-align: center;">الحالة</th>
                    <th style="width: 10%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appraisals)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد تقييمات أداء مسجلة.</td></tr>
                <?php else: foreach ($appraisals as $ap): 
                    $st = $statusMap[$ap->status] ?? $statusMap['draft'];
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-appr-dark); font-size: 0.95rem;">
                            <a href="/ERP/hr/appraisals/<?= $ap->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($ap->appraisal_code) ?></a>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($ap->employee_name ?: 'مجهول') ?></div>
                            <div style="font-size: 0.75rem; color: #64748b; font-family: monospace;"><?= htmlspecialchars($ap->emp_code) ?> - <?= htmlspecialchars($ap->dept_name ?: 'عام') ?></div>
                        </td>
                        <td style="font-family: monospace; font-size:0.85rem; font-weight:700;">
                            <div><?= htmlspecialchars($ap->appraisal_period) ?></div>
                            <div style="font-size:0.75rem; color:#64748b;"><?= htmlspecialchars($ap->appraisal_date) ?></div>
                        </td>
                        <td>
                            <div style="font-family: monospace; font-weight: 900; color: #059669; font-size: 1.05rem;"><?= number_format((float)$ap->score, 1) ?> %</div>
                            <span style="font-size:0.75rem; font-weight:800; color:#0284c7;"><?= htmlspecialchars($ap->rating_grade) ?></span>
                        </td>
                        <td style="font-weight: 700; color: #475569;">
                            <?= htmlspecialchars($ap->evaluator_name ?: 'غير محدد') ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/hr/appraisals/<?= $ap->id ?>" class="action-btn" title="عرض التقرير"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/hr/appraisals/<?= $ap->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/hr/appraisals/<?= $ap->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا التقييم؟');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>