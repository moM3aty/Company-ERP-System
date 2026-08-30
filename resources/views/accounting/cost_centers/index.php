<?php
// Path: resources/views/accounting/cost_centers/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);
?>

<style>
    :root {
        --c-cc: #7c3aed;
        --c-cc-dark: #6d28d9;
        --c-cc-light: #f5f3ff;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .cc-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .cc-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .cc-title-box { display: flex; align-items: center; gap: 16px; }
    .cc-icon { width: 48px; height: 48px; background: var(--c-cc-light); color: var(--c-cc); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(124, 58, 237, 0.12); }
    .cc-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-cc { background: linear-gradient(135deg, var(--c-cc), var(--c-cc-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-cc-light); color: var(--c-cc); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .cc-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .cc-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .cc-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-cc-light); color: var(--c-cc); border-color: #ddd6fe; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-cc); color: #ffffff; border-color: var(--c-cc); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-block; }
</style>

<div class="cc-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="cc-header">
        <div class="cc-title-box">
            <div class="cc-icon"><i class="ph-duotone ph-target"></i></div>
            <div>
                <h2 class="cc-title">دليل مراكز التكلفة (Cost Centers)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);">ربط وحصر المصروفات والإيرادات بالمشاريع والفروع والأقسام الإدارية.</p>
            </div>
        </div>
        <a href="/ERP/accounting/cost-centers/create" class="btn-cc"><i class="ph-bold ph-plus"></i> إضافة مركز تكلفة</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-buildings"></i></div><div class="kpi-info"><h4>إجمالي المراكز</h4><p><?= number_format($stats->total_centers ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#f5f3ff; color:#7c3aed;"><i class="ph-duotone ph-folder"></i></div><div class="kpi-info"><h4 style="color:#7c3aed;">مراكز رئيسية</h4><p><?= number_format($stats->parent_centers ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-duotone ph-tree-structure"></i></div><div class="kpi-info"><h4 style="color:#2563eb;">مراكز فرعية</h4><p><?= number_format($stats->sub_centers ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;">المراكز النشطة</h4><p><?= number_format($stats->active_centers ?? 0) ?></p></div></div>
    </div>

    <form action="/ERP/accounting/cost-centers" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2;" placeholder="ابحث برقم الكود، الاسم بالعربي، أو الإنجليزي..." value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="status" class="form-control" style="flex:1;">
            <option value="">-- كل الحالات --</option>
            <option value="active" <?= ($statusFilter==='active')?'selected':'' ?>>نشط (Active)</option>
            <option value="inactive" <?= ($statusFilter==='inactive')?'selected':'' ?>>غير نشط (Inactive)</option>
        </select>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> فلترة</button>
    </form>

    <div class="table-card">
        <table class="cc-table">
            <thead>
                <tr>
                    <th style="width: 15%;">رمز الكود</th>
                    <th style="width: 30%;">اسم مركز التكلفة</th>
                    <th style="width: 15%;">الموازنة التقديرية</th>
                    <th style="width: 15%;">النوع (الدرجة)</th>
                    <th style="width: 15%;">المركز الأب</th>
                    <th style="width: 10%; text-align: center;">الحالة</th>
                    <th style="width: 15%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($centers)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد مراكز تكلفة مسجلة.</td></tr>
                <?php else: foreach ($centers as $c): ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-cc-dark); font-size: 1rem;"><?= htmlspecialchars($c->code) ?></td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);">
                                <?= !empty($c->is_parent) ? '<i class="ph-bold ph-folder" style="color:var(--c-cc);"></i>' : '<i class="ph-bold ph-file-text" style="color:#94a3b8;"></i>' ?>
                                <?= htmlspecialchars($c->name_ar) ?>
                            </div>
                        </td>
                        <td style="font-family: monospace; font-weight: 800; color: var(--c-text-dark);"><?= number_format((float)($c->budget_amount ?? 0), 2) ?></td>
                        <td>
                            <span class="badge-status" style="<?= !empty($c->is_parent) ? 'background:#f5f3ff; color:#7c3aed; border:1px solid #ddd6fe;' : 'background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;' ?>">
                                <?= !empty($c->is_parent) ? 'رئيسي (Parent)' : 'فرعي (Sub)' ?>
                            </span>
                        </td>
                        <td style="font-size:0.85rem; color:#64748b; font-weight:700;"><?= htmlspecialchars($c->parent_name ?? '---') ?></td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="<?= !empty($c->is_active) ? 'background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;' : 'background:#fef2f2; color:#dc2626; border:1px solid #fecaca;' ?>">
                                <?= !empty($c->is_active) ? 'نشط' : 'معطل' ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/accounting/cost-centers/<?= $c->id ?>" class="action-btn" title="عرض التفاصيل"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/accounting/cost-centers/<?= $c->id ?>/report" class="action-btn" title="تقرير الربحية والموازنة"><i class="ph-bold ph-chart-line-up"></i></a>
                            <a href="/ERP/accounting/cost-centers/<?= $c->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/accounting/cost-centers/<?= $c->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف مركز التكلفة؟');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>&status=<?= urlencode($statusFilter ?? '') ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>