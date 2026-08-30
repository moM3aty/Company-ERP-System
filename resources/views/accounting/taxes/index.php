<?php
// Path: resources/views/accounting/taxes/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

function getTaxTypeLabel($type) {
    $map = [
        'vat' => 'قيمة مضافة (VAT)',
        'wht' => 'خصم وإضافة (WHT)',
        'sales' => 'ضريبة مبيعات',
        'other' => 'أخرى'
    ];
    return $map[$type] ?? $type;
}
?>

<style>
    :root {
        --c-tax: #0284c7; /* Ocean Blue */
        --c-tax-dark: #0369a1;
        --c-tax-light: #e0f2fe;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .tax-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .tax-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .tax-title-box { display: flex; align-items: center; gap: 16px; }
    .tax-icon { width: 50px; height: 50px; background: var(--c-tax-light); color: var(--c-tax); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.15); flex-shrink: 0; }
    .tax-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-tax { background: linear-gradient(135deg, var(--c-tax), var(--c-tax-dark)); color: #ffffff !important; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.2); white-space: nowrap; }

    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:900px){ .kpi-row { grid-template-columns: 1fr; } }
    .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-info h4 { margin: 0 0 6px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.4rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }
    .kpi-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }

    .search-bar { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px; display: flex; gap: 12px; align-items: center; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .form-control { border: 1px solid var(--c-border); border-radius: 10px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; color: var(--c-text-dark); font-weight: 600; outline: none; transition: all 0.2s; }
    .form-control:focus { border-color: var(--c-tax); background: #ffffff; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1); }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 11px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .tax-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .tax-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.78rem; text-align: start; }
    .tax-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; text-align: start; }

    .action-btn { width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; transition: all 0.2s; }
    .action-btn:hover { background: var(--c-tax-light); color: var(--c-tax); border-color: #bae6fd; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-tax); color: #ffffff; border-color: var(--c-tax); }
</style>

<div class="tax-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="tax-header">
        <div class="tax-title-box">
            <div class="tax-icon"><i class="ph-duotone ph-receipt"></i></div>
            <div>
                <h2 class="tax-title">الضرائب والرسوم (Taxes & Duties)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;">إعداد النسب الضريبية وربطها بالدليل المحاسبي لتقديم الإقرارات.</p>
            </div>
        </div>
        <a href="/ERP/accounting/taxes/create" class="btn-tax"><i class="ph-bold ph-plus"></i> إضافة ضريبة جديدة</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0; display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-check-circle" style="font-size:1.2rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca; display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-warning-circle" style="font-size:1.2rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-info">
                <h4>إجمالي الأكواد الضريبية</h4>
                <p><?= number_format($stats->total_taxes ?? 0) ?></p>
            </div>
            <div class="kpi-icon" style="background:#f1f5f9; color:#475569;"><i class="ph-duotone ph-files"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4 style="color:#16a34a;">ضرائب نشطة</h4>
                <p style="color:#16a34a;"><?= number_format($stats->active_taxes ?? 0) ?></p>
            </div>
            <div class="kpi-icon" style="background:#f0fdf4; color:#16a34a;"><i class="ph-duotone ph-check-circle"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4 style="color:var(--c-tax-dark);">ضرائب القيمة المضافة (VAT)</h4>
                <p style="color:var(--c-tax-dark);"><?= number_format($stats->vat_taxes ?? 0) ?></p>
            </div>
            <div class="kpi-icon" style="background:var(--c-tax-light); color:var(--c-tax);"><i class="ph-duotone ph-percent"></i></div>
        </div>
    </div>

    <form action="/ERP/accounting/taxes" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2;" placeholder="ابحث بكود الضريبة أو الاسم..." value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="type" class="form-control" style="flex:1;">
            <option value="">-- جميع الأنواع --</option>
            <option value="vat" <?= ($typeFilter==='vat')?'selected':'' ?>>قيمة مضافة (VAT)</option>
            <option value="wht" <?= ($typeFilter==='wht')?'selected':'' ?>>خصم وإضافة (WHT)</option>
            <option value="sales" <?= ($typeFilter==='sales')?'selected':'' ?>>مبيعات</option>
            <option value="other" <?= ($typeFilter==='other')?'selected':'' ?>>أخرى</option>
        </select>
        <select name="status" class="form-control" style="flex:1;">
            <option value="">-- جميع الحالات --</option>
            <option value="active" <?= ($statusFilter==='active')?'selected':'' ?>>نشط</option>
            <option value="inactive" <?= ($statusFilter==='inactive')?'selected':'' ?>>معطل</option>
        </select>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> بحث</button>
    </form>

    <div class="table-card">
        <table class="tax-table">
            <thead>
                <tr>
                    <th style="width: 12%;">الكود</th>
                    <th style="width: 25%;">اسم الضريبة</th>
                    <th style="width: 13%; text-align: center;">النسبة (%)</th>
                    <th style="width: 15%;">النوع</th>
                    <th style="width: 15%;">الحساب المرتبط</th>
                    <th style="width: 10%; text-align: center;">الحالة</th>
                    <th style="width: 10%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($taxes)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد ضرائب مسجلة.</td></tr>
                <?php else: foreach ($taxes as $t): ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-tax-dark); font-size: 1rem;">
                            <?= htmlspecialchars($t->code) ?>
                        </td>
                        <td>
                            <a href="/ERP/accounting/taxes/<?= $t->id ?>" style="font-weight: 800; color: var(--c-text-dark); text-decoration:none;">
                                <?= htmlspecialchars($t->name_ar) ?>
                            </a>
                        </td>
                        <td style="text-align: center; font-family: monospace; font-weight: 900; font-size:1.1rem; color: var(--c-tax);"><?= (float)$t->tax_rate ?>%</td>
                        <td style="font-size: 0.85rem; font-weight: bold; color: #64748b;"><?= getTaxTypeLabel($t->tax_type) ?></td>
                        <td style="font-size: 0.8rem; font-weight: bold; color: #475569;"><?= htmlspecialchars($t->acc_code) ?> - <?= htmlspecialchars($t->acc_name) ?></td>
                        <td style="text-align: center;">
                            <?php if($t->is_active): ?>
                                <span style="background:#ecfdf5; color:#059669; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem;">نشط</span>
                            <?php else: ?>
                                <span style="background:#fef2f2; color:#dc2626; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem;">معطل</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <div style="display: flex; justify-content: center; gap: 6px;">
                                <a href="/ERP/accounting/taxes/<?= $t->id ?>" class="action-btn" title="تقرير حساب الضريبة"><i class="ph-bold ph-chart-line-up"></i></a>
                                <a href="/ERP/accounting/taxes/<?= $t->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                <form action="/ERP/accounting/taxes/<?= $t->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذه الضريبة؟');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>&status=<?= urlencode($statusFilter ?? '') ?>&type=<?= urlencode($typeFilter ?? '') ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>