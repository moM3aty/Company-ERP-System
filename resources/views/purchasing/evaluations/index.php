<?php
// Path: resources/views/purchasing/evaluations/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'تقييمات وأداء الموردين', 'desc' => 'قياس جودة التوريد، دقة المواعيد، التنافسية وتصنيف الموردين المستمر.',
        'add_btn' => 'إجراء تقييم جديد', 'col_num' => 'رقم التقييم', 'col_sup' => 'المورد',
        'col_date' => 'تاريخ التقييم / الفترة', 'col_score' => 'النتيجة الإجمالية',
        'col_grade' => 'التصنيف', 'col_actions' => 'إجراءات', 'empty' => 'لا توجد تقييمات مسجلة تطابق بحثك.',
        'search' => 'ابحث برقم التقييم، اسم المورد، أو المقيّم...',
        'search_btn' => 'بحث', 'clear' => 'إلغاء',
        'stat_total' => 'إجمالي التقييمات المبحوثة',
        'stat_avg' => 'متوسط الأداء العام',
        'stat_excellent' => 'الموردين الممتازين (Grade A)',
        'general' => 'عام / غير محدد',
        'confirm_delete' => 'تأكيد الحذف؟'
    ],
    'en' => [
        'title' => 'Supplier Performance Evaluations', 'desc' => 'Measure delivery accuracy, quality, pricing, and supplier scorecard.',
        'add_btn' => 'New Evaluation', 'col_num' => 'Evaluation No.', 'col_sup' => 'Supplier',
        'col_date' => 'Date / Period', 'col_score' => 'Overall Score',
        'col_grade' => 'Grade', 'col_actions' => 'Actions', 'empty' => 'No evaluations recorded matching your search.',
        'search' => 'Search by evaluation no, vendor name, or evaluator...',
        'search_btn' => 'Search', 'clear' => 'Clear',
        'stat_total' => 'Total Searched Evaluations',
        'stat_avg' => 'Overall Average Score',
        'stat_excellent' => 'Excellent Vendors (Grade A)',
        'general' => 'General / Unspecified',
        'confirm_delete' => 'Confirm delete?'
    ]
][$isRtl ? 'ar' : 'en'];

function getGradeBadge($grade) {
    $map = [
        'A+' => 'background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;',
        'A'  => 'background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0;',
        'B'  => 'background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;',
        'C'  => 'background:#fffbeb; color:#d97706; border:1px solid #fde68a;',
        'D'  => 'background:#fef2f2; color:#dc2626; border:1px solid #fecaca;'
    ];
    $style = $map[$grade] ?? $map['B'];
    return "<span style='padding: 4px 14px; border-radius: 99px; font-weight: 900; font-family: monospace; font-size: 0.85rem; {$style}'>{$grade}</span>";
}
?>

<style>
    :root {
        --c-primary: #db2777;
        --c-primary-light: #fdf2f8;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .mod-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .mod-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .mod-title-box { display: flex; align-items: center; gap: 16px; }
    .mod-icon { width: 48px; height: 48px; background: var(--c-primary-light); color: var(--c-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(219, 39, 119, 0.1); }
    .mod-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 800; }
    .mod-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: var(--c-text-muted); font-weight: 500; }
    
    .btn-primary { background: linear-gradient(135deg, #db2777, #be185d); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(219, 39, 119, 0.25); transition: 0.2s; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(219, 39, 119, 0.35); }

    .bento-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
    @media (max-width: 768px) { .bento-grid { grid-template-columns: 1fr; } }
    .bento-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px 24px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .bento-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; background: var(--c-primary-light); color: var(--c-primary); }
    .bento-title { margin: 0 0 4px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .bento-val { margin: 0; font-size: 1.5rem; font-weight: 900; color: var(--c-text-dark); font-family: monospace; }

    .search-bar { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .search-input { flex: 1; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 16px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; transition: 0.2s; }
    .search-input:focus { border-color: var(--c-primary); background: #ffffff; outline: none; box-shadow: 0 0 0 3px #fce7f3; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-clear { background: #f1f5f9; color: var(--c-text-muted); border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 8px; font-weight: 800; text-decoration: none; display: flex; align-items: center; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; }
    .mod-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .mod-table th { padding: 16px 24px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid #e2e8f0; }
    .mod-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .mod-table tr:hover td { background: #f8fafc; }
    
    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; font-size: 1.1rem; margin: 0 2px; transition: 0.2s; }
    .action-btn:hover { background: var(--c-primary-light); border-color: #fbcfe8; color: var(--c-primary); }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; transition: 0.2s; }
    .page-link.active { background: var(--c-primary); color: #ffffff; border-color: var(--c-primary); box-shadow: 0 2px 6px rgba(219, 39, 119, 0.4); }
</style>

<div class="mod-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="mod-header">
        <div class="mod-title-box">
            <div class="mod-icon"><i class="ph-duotone ph-star"></i></div>
            <div>
                <h2 class="mod-title"><?= $t['title'] ?></h2>
                <p class="mod-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/purchasing/supplier-evaluations/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="bento-grid">
        <div class="bento-card">
            <div class="bento-icon"><i class="ph-bold ph-files"></i></div>
            <div>
                <p class="bento-title"><?= $t['stat_total'] ?></p>
                <p class="bento-val"><?= number_format($stats->total_evals ?? 0) ?></p>
            </div>
        </div>
        <div class="bento-card">
            <div class="bento-icon" style="background:#ecfdf5; color:#10b981;"><i class="ph-bold ph-chart-line-up"></i></div>
            <div>
                <p class="bento-title"><?= $t['stat_avg'] ?></p>
                <p class="bento-val" style="color:#059669;"><?= $stats->avg_score ?? 0 ?> %</p>
            </div>
        </div>
        <div class="bento-card">
            <div class="bento-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-bold ph-trophy"></i></div>
            <div>
                <p class="bento-title"><?= $t['stat_excellent'] ?></p>
                <p class="bento-val" style="color:#d97706;"><?= number_format($stats->excellent_suppliers ?? 0) ?></p>
            </div>
        </div>
    </div>

    <form action="/ERP/purchasing/supplier-evaluations" method="GET" class="search-bar">
        <input type="text" name="search" class="search-input" placeholder="<?= $t['search'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['search_btn'] ?></button>
        <?php if(!empty($search)): ?>
            <a href="/ERP/purchasing/supplier-evaluations" class="btn-clear"><i class="ph-bold ph-x"></i> <?= $t['clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th style="width: 15%;"><?= $t['col_num'] ?></th>
                        <th style="width: 25%;"><?= $t['col_sup'] ?></th>
                        <th style="width: 20%;"><?= $t['col_date'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_score'] ?></th>
                        <th style="width: 12%; text-align: center;"><?= $t['col_grade'] ?></th>
                        <th style="width: 13%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($evaluations)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 60px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach ($evaluations as $e): ?>
                        <tr>
                            <td style="font-weight: 800; font-family: monospace; color: var(--c-primary);"><?= htmlspecialchars($e->eval_number) ?></td>
                            <td>
                                <div style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($e->supplier_name ?? $t['general']) ?></div>
                                <div style="color: #64748b; font-family: monospace; font-size: 0.8rem;"><?= htmlspecialchars($e->supplier_code ?? '') ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #334155;"><?= htmlspecialchars($e->evaluation_date) ?></div>
                                <div style="color: #64748b; font-size: 0.8rem;"><?= htmlspecialchars($e->period_covered ?? '---') ?></div>
                            </td>
                            <td style="text-align: center;">
                                <div style="font-weight: 900; font-family: monospace; font-size: 1.1rem; color: #0f172a;"><?= number_format($e->overall_score, 1) ?> %</div>
                            </td>
                            <td style="text-align: center;"><?= getGradeBadge($e->grade) ?></td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="/ERP/purchasing/supplier-evaluations/<?= $e->id ?>" class="action-btn" title="عرض التقرير"><i class="ph-bold ph-eye"></i></a>
                                <a href="/ERP/purchasing/supplier-evaluations/<?= $e->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                <form action="/ERP/purchasing/supplier-evaluations/<?= $e->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
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
        <div class="pagination">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>