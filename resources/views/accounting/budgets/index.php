<?php
// Path: resources/views/accounting/budgets/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

function getBudgetStatusBadge($status) {
    $map = [
        'draft'    => ['bg' => '#fef3c7', 'color' => '#d97706', 'label' => 'مسودة'],
        'approved' => ['bg' => '#ffe4e6', 'color' => '#e11d48', 'label' => 'معتمدة'],
        'closed'   => ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => 'مغلقة']
    ];
    $s = $map[$status] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $status];
    return "<span style='background:{$s['bg']}; color:{$s['color']}; padding:6px 14px; border-radius:8px; font-weight:800; font-size:0.78rem; border:1px solid currentColor; display:inline-block; white-space:nowrap;'>{$s['label']}</span>";
}
?>

<style>
    :root {
        --c-bg: #e11d48;
        --c-bg-dark: #be123c;
        --c-bg-light: #ffe4e6;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .bg-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .bg-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .bg-title-box { display: flex; align-items: center; gap: 16px; }
    .bg-icon { width: 50px; height: 50px; background: var(--c-bg-light); color: var(--c-bg); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 12px rgba(225, 29, 72, 0.12); flex-shrink: 0; }
    .bg-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-bg { background: linear-gradient(135deg, var(--c-bg), var(--c-bg-dark)); color: #ffffff !important; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(225, 29, 72, 0.2); white-space: nowrap; }

    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:900px){ .kpi-row { grid-template-columns: 1fr; } }
    .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-info h4 { margin: 0 0 6px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.4rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }
    .kpi-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }

    .search-bar { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px; display: flex; gap: 12px; align-items: center; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .form-control { border: 1px solid var(--c-border); border-radius: 10px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; color: var(--c-text-dark); font-weight: 600; outline: none; transition: all 0.2s; }
    .form-control:focus { border-color: var(--c-bg); background: #ffffff; box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.1); }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 11px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .bg-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .bg-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.78rem; text-align: start; }
    .bg-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; text-align: start; }

    .action-btn { width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; transition: all 0.2s; }
    .action-btn:hover { background: var(--c-bg-light); color: var(--c-bg); border-color: #fecdd3; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-bg); color: #ffffff; border-color: var(--c-bg); }
</style>

<div class="bg-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="bg-header">
        <div class="bg-title-box">
            <div class="bg-icon"><i class="ph-duotone ph-chart-bar"></i></div>
            <div>
                <h2 class="bg-title">الموازنات التقديرية (Financial Budgets)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;">التخطيط المالي، تخصيص الاعتمادات الموازنية، ومتابعة الانحرافات.</p>
            </div>
        </div>
        <a href="/ERP/accounting/budgets/create" class="btn-bg"><i class="ph-bold ph-plus"></i> إنشاء موازنة جديدة</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0; display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-check-circle" style="font-size:1.2rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca; display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-warning-circle" style="font-size:1.2rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-info">
                <h4>إجمالي الموازنات</h4>
                <p><?= number_format($stats->total_budgets ?? 0) ?></p>
            </div>
            <div class="kpi-icon" style="background:#f1f5f9; color:#475569;"><i class="ph-duotone ph-files"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4 style="color:#e11d48;">الموازنات المعتمدة</h4>
                <p style="color:#e11d48;"><?= number_format($stats->approved_budgets ?? 0) ?></p>
            </div>
            <div class="kpi-icon" style="background:#ffe4e6; color:#e11d48;"><i class="ph-duotone ph-check-circle"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4 style="color:#16a34a;">إجمالي المبالغ المخصصة</h4>
                <p style="color:#16a34a;"><?= number_format((float)($stats->total_allocated_amount ?? 0), 2) ?></p>
            </div>
            <div class="kpi-icon" style="background:#f0fdf4; color:#16a34a;"><i class="ph-duotone ph-currency-circle-dollar"></i></div>
        </div>
    </div>

    <form action="/ERP/accounting/budgets" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2;" placeholder="ابحث برقم الكود، أو اسم الموازنة..." value="<?= htmlspecialchars($search ?? '') ?>">
        <input type="number" name="year" class="form-control" style="flex:1;" placeholder="السنة المالية (مثال: 2026)" value="<?= htmlspecialchars($yearFilter ?? '') ?>">
        <select name="status" class="form-control" style="flex:1;">
            <option value="">-- جميع الحالات --</option>
            <option value="draft" <?= ($statusFilter==='draft')?'selected':'' ?>>مسودة (Draft)</option>
            <option value="approved" <?= ($statusFilter==='approved')?'selected':'' ?>>معتمدة (Approved)</option>
            <option value="closed" <?= ($statusFilter==='closed')?'selected':'' ?>>مغلقة (Closed)</option>
        </select>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> بحث</button>
    </form>

    <div class="table-card">
        <table class="bg-table">
            <thead>
                <tr>
                    <th style="width: 16%;">الكود</th>
                    <th style="width: 26%;">عنوان الموازنة</th>
                    <th style="width: 10%; text-align: center;">السنة المالية</th>
                    <th style="width: 20%; text-align: center;">الفترة الزمنية</th>
                    <th style="width: 14%; text-align: center;">إجمالي الاعتماد</th>
                    <th style="width: 10%; text-align: center;">الحالة</th>
                    <th style="width: 14%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($budgets)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد موازنات تقديرية مسجلة.</td></tr>
                <?php else: foreach ($budgets as $b): ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-bg-dark); font-size: 0.9rem; white-space: nowrap;">
                            <?= htmlspecialchars($b->code) ?>
                        </td>
                        <td>
                            <a href="/ERP/accounting/budgets/<?= $b->id ?>" style="font-weight: 800; color: var(--c-text-dark); text-decoration:none;">
                                <?= htmlspecialchars($b->name_ar) ?>
                            </a>
                        </td>
                        <td style="text-align: center; font-family: monospace; font-weight: 800; color: #0f172a;"><?= (int)$b->fiscal_year ?></td>
                        <td style="text-align: center; font-size: 0.85rem; font-family: monospace; color: #64748b; white-space: nowrap;">
                            <span dir="ltr" style="display:inline-block; unicode-bidi: isolate;"><?= htmlspecialchars($b->start_date) ?></span>
                            <span style="margin: 0 4px; font-weight: bold; color: #cbd5e1;">إلى</span>
                            <span dir="ltr" style="display:inline-block; unicode-bidi: isolate;"><?= htmlspecialchars($b->end_date) ?></span>
                        </td>
                        <td style="text-align: center; font-family: monospace; font-weight: 900; color: var(--c-bg-dark);"><?= number_format((float)$b->total_allocated, 2) ?></td>
                        <td style="text-align: center;"><?= getBudgetStatusBadge($b->status) ?></td>
                        <td style="text-align: center; white-space: nowrap;">
                            <div style="display: flex; justify-content: center; gap: 6px;">
                                <a href="/ERP/accounting/budgets/<?= $b->id ?>" class="action-btn" title="تقرير تحليل الانحرافات"><i class="ph-bold ph-chart-line-up"></i></a>
                                <a href="/ERP/accounting/budgets/<?= $b->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                <form action="/ERP/accounting/budgets/<?= $b->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف الموازنة التقديرية؟');">
                                    <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>&year=<?= urlencode($yearFilter ?? '') ?>&status=<?= urlencode($statusFilter ?? '') ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>