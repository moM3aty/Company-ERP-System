<?php
// Path: resources/views/accounting/fixed_assets/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);
?>

<style>
    :root {
        --c-fa: #0d9488;
        --c-fa-dark: #0f766e;
        --c-fa-light: #ccfbf1;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .fa-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .fa-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .fa-title-box { display: flex; align-items: center; gap: 16px; }
    .fa-icon { width: 48px; height: 48px; background: var(--c-fa-light); color: var(--c-fa); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(13, 148, 136, 0.15); }
    .fa-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-fa { background: linear-gradient(135deg, var(--c-fa), var(--c-fa-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-fa-light); color: var(--c-fa); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .fa-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .fa-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .fa-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-fa-light); color: var(--c-fa); border-color: #99f6e4; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-fa); color: #ffffff; border-color: var(--c-fa); }
</style>

<div class="fa-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="fa-header">
        <div class="fa-title-box">
            <div class="fa-icon"><i class="ph-duotone ph-armchair"></i></div>
            <div>
                <h2 class="fa-title">إدارة الأصول الثابتة والإهلاكات (Fixed Assets)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);">سجل حصر الأصول الثابتة، متابعة القيم الدفترية، وحساب قيود الإهلاك الآلية.</p>
            </div>
        </div>
        <a href="/ERP/accounting/fixed-assets/create" class="btn-fa"><i class="ph-bold ph-plus"></i> إضافة أصل جديد</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-buildings"></i></div><div class="kpi-info"><h4>عدد الأصول</h4><p><?= number_format($stats->total_assets ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#f0fdf4; color:#16a34a;"><i class="ph-duotone ph-currency-circle-dollar"></i></div><div class="kpi-info"><h4 style="color:#16a34a;">التكلفة التاريخية</h4><p><?= number_format((float)($stats->total_cost ?? 0), 2) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fff7ed; color:#c2410c;"><i class="ph-duotone ph-chart-line-down"></i></div><div class="kpi-info"><h4 style="color:#c2410c;">مجمع الإهلاك التراكمي</h4><p><?= number_format((float)($stats->total_depreciation ?? 0), 2) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ccfbf1; color:#0d9488;"><i class="ph-duotone ph-vault"></i></div><div class="kpi-info"><h4 style="color:#0d9488;">صافي القيمة الدفترية</h4><p><?= number_format((float)($stats->total_book_value ?? 0), 2) ?></p></div></div>
    </div>

    <form action="/ERP/accounting/fixed-assets" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2;" placeholder="ابحث بكود الأصل، الاسم، أو التصنيف..." value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="category" class="form-control" style="flex:1;">
            <option value="">-- جميع التصنيفات --</option>
            <option value="buildings" <?= ($categoryFilter==='buildings')?'selected':'' ?>>مباني وعقارات</option>
            <option value="vehicles" <?= ($categoryFilter==='vehicles')?'selected':'' ?>>سيارات ووسائل نقل</option>
            <option value="equipment" <?= ($categoryFilter==='equipment')?'selected':'' ?>>آلات ومعدات</option>
            <option value="furniture" <?= ($categoryFilter==='furniture')?'selected':'' ?>>أثاث وتجهيزات مكتبية</option>
            <option value="it_hardware" <?= ($categoryFilter==='it_hardware')?'selected':'' ?>>أجهزة إلكترونية وحاسب</option>
        </select>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> بحث</button>
    </form>

    <div class="table-card">
        <table class="fa-table">
            <thead>
                <tr>
                    <th style="width: 12%;">الكود</th>
                    <th style="width: 25%;">اسم الأصل</th>
                    <th style="width: 12%;">تاريخ الشراء</th>
                    <th style="width: 13%;">تكلفة الشراء</th>
                    <th style="width: 13%;">مجمع الإهلاك</th>
                    <th style="width: 13%;">القيمة الدفترية</th>
                    <th style="width: 12%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assets)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد أصول مسجلة تطابق خيارات البحث.</td></tr>
                <?php else: foreach ($assets as $a): ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-fa-dark); font-size: 0.95rem;"><?= htmlspecialchars($a->code) ?></td>
                        <td>
                            <a href="/ERP/accounting/fixed-assets/<?= $a->id ?>" style="font-weight: 800; color: var(--c-text-dark); text-decoration:none;">
                                <?= htmlspecialchars($a->name_ar) ?>
                            </a>
                        </td>
                        <td style="font-family: monospace; font-weight: 700; color: #64748b;"><?= htmlspecialchars($a->purchase_date) ?></td>
                        <td style="font-family: monospace; font-weight: 900; color: #0f172a;"><?= number_format((float)$a->purchase_cost, 2) ?></td>
                        <td style="font-family: monospace; font-weight: 900; color: #dc2626;"><?= number_format((float)$a->accumulated_depreciation, 2) ?></td>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-fa-dark);"><?= number_format((float)$a->book_value, 2) ?></td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/accounting/fixed-assets/<?= $a->id ?>" class="action-btn" title="عرض كارت الأصل والإهلاك"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/accounting/fixed-assets/<?= $a->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/accounting/fixed-assets/<?= $a->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف الأصل؟');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>&category=<?= urlencode($categoryFilter ?? '') ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>