<?php
// Path: resources/views/inventory/adjustments/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

function getAdjustmentStatusBadge($status) {
    $map = [
        'draft'    => ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => 'مسودة'],
        'approved' => ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => 'معتمد ومرحل'],
        'cancelled' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => 'ملغى']
    ];
    $s = $map[$status] ?? $map['draft'];
    return "<span style='background:{$s['bg']}; color:{$s['color']}; padding:4px 12px; border-radius:8px; font-weight:800; font-size:0.75rem; border:1px solid currentColor;'>{$s['label']}</span>";
}

function getAdjustmentTypeBadge($type) {
    if ($type === 'addition') {
        return "<span style='background:#ecfdf5; color:#059669; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem;'><i class='ph-bold ph-plus-circle'></i> إضافة رصيد (تسوية فائض)</span>";
    }
    return "<span style='background:#fef2f2; color:#dc2626; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem;'><i class='ph-bold ph-minus-circle'></i> خصم رصيد (تسوية عجز / تلف)</span>";
}
?>

<style>
    :root {
        --c-amber: #f59e0b; --c-amber-dark: #d97706; --c-amber-light: #fef3c7;
        --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #475569;
    }
    .mod-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .mod-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .mod-title-box { display: flex; align-items: center; gap: 16px; }
    .mod-icon { width: 48px; height: 48px; background: var(--c-amber-light); color: var(--c-amber); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
    .mod-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 800; }
    .btn-primary { background: linear-gradient(135deg, var(--c-amber), var(--c-amber-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; }
    .kpi-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: var(--c-amber-light); color: var(--c-amber); }
    .kpi-info h4 { margin: 0 0 4px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .kpi-info p { margin: 0; font-size: 1.4rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }
    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 12px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px; }
    .search-input { flex: 1; border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 16px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; }
    .btn-clear { background: #f1f5f9; color: var(--c-text-muted); border: 1px solid var(--c-border); padding: 10px 20px; border-radius: 8px; font-weight: 800; text-decoration: none; display: flex; align-items: center; }
    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .mod-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .mod-table th { padding: 16px 24px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .mod-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-amber-light); border-color: #fcd34d; color: var(--c-amber); }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-amber); color: #ffffff; border-color: var(--c-amber); }
</style>

<div class="mod-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="mod-header">
        <div class="mod-title-box">
            <div class="mod-icon"><i class="ph-duotone ph-scales"></i></div>
            <div>
                <h2 class="mod-title">التسويات المخزنية (Stock Adjustments)</h2>
                <p class="mod-desc">معالجة وتوثيق نتائج الجرد الدوري، تسوية العجز، الزيادة والأصناف التالفة.</p>
            </div>
        </div>
        <a href="/ERP/inventory/adjustments/create" class="btn-primary"><i class="ph-bold ph-plus"></i> إذن تسوية جديد</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="ph-duotone ph-files"></i></div>
            <div class="kpi-info"><h4>إجمالي التسويات</h4><p><?= number_format($stats->total ?? 0) ?></p></div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #059669;">
            <div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-plus-circle"></i></div>
            <div class="kpi-info"><h4 style="color:#059669;">تسويات إضافة (فائض)</h4><p><?= number_format($stats->addition_count ?? 0) ?></p></div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #dc2626;">
            <div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-minus-circle"></i></div>
            <div class="kpi-info"><h4 style="color:#dc2626;">تسويات خصم (عجز / تلف)</h4><p><?= number_format($stats->subtraction_count ?? 0) ?></p></div>
        </div>
    </div>

    <form action="/ERP/inventory/adjustments" method="GET" class="search-bar">
        <input type="text" name="search" class="search-input" placeholder="ابحث برقم التسوية، السبب، أو المستودع..." value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> بحث</button>
        <?php if(!empty($search)): ?>
            <a href="/ERP/inventory/adjustments" class="btn-clear"><i class="ph-bold ph-x"></i> إلغاء</a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="mod-table">
            <thead>
                <tr>
                    <th style="width: 15%;">رقم التسوية</th>
                    <th style="width: 20%;">نوع التسوية</th>
                    <th style="width: 20%;">المستودع</th>
                    <th style="width: 20%;">السبب / البيان</th>
                    <th style="width: 10%;">التاريخ</th>
                    <th style="width: 8%; text-align: center;">الحالة</th>
                    <th style="width: 7%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($adjustments)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد تسويات مخزنية تطابق بحثك.</td></tr>
                <?php else: foreach ($adjustments as $a): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 900; color: var(--c-amber); font-family: monospace; font-size: 1.05rem;"><i class="ph-bold ph-hash"></i> <?= htmlspecialchars($a->adjustment_number) ?></div>
                            <div style="color: var(--c-text-muted); font-size: 0.8rem; margin-top:2px;">أصناف: <strong><?= $a->items_count ?></strong></div>
                        </td>
                        <td><?= getAdjustmentTypeBadge($a->adjustment_type) ?></td>
                        <td>
                            <div style="font-weight: 700; color: #334155;"><i class="ph-fill ph-warehouse text-slate-400"></i> <?= htmlspecialchars($a->warehouse_name) ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #475569; font-size:0.85rem;"><?= htmlspecialchars($a->reason ?? '---') ?></div>
                        </td>
                        <td><div style="font-weight: 700; color: #334155;"><i class="ph-bold ph-calendar-blank"></i> <?= $a->adjustment_date ?></div></td>
                        <td style="text-align: center;"><?= getAdjustmentStatusBadge($a->status) ?></td>
                        
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/inventory/adjustments/<?= $a->id ?>" class="action-btn" title="معاينة وطباعة"><i class="ph-bold ph-printer"></i></a>
                            
                            <?php if(trim($a->status) !== 'approved'): ?>
                                <a href="/ERP/inventory/adjustments/<?= $a->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                <form action="/ERP/inventory/adjustments/<?= $a->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف أمر التسوية؟');">
                                    <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
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