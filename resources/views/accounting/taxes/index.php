<?php
// Path: resources/views/accounting/taxes/index.php

try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

    $flashMsg = $_SESSION['flash_msg'] ?? null;
    $flashErr = $_SESSION['flash_err'] ?? null;
    unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

    $t = [
        'ar' => [
            'title' => 'إعدادات الضرائب', 'desc' => 'إدارة نسب وأنواع الضرائب المطبقة في النظام وربطها بالدليل المحاسبي.',
            'add_btn' => 'إضافة ضريبة جديدة', 'stat_total' => 'إجمالي الضرائب', 'stat_vat' => 'القيمة المضافة (VAT)', 'stat_wh' => 'خصم من المنبع',
            'search_ph' => 'ابحث باسم الضريبة...', 'all_branches' => '-- جميع الفروع --', 'all_types' => '-- كل الأنواع --',
            'type_vat' => 'قيمة مضافة (VAT)', 'type_wh' => 'خصم من المنبع (Withholding)', 'type_other' => 'أخرى (Other)',
            'btn_search' => 'بحث', 'col_name' => 'اسم الضريبة', 'col_rate' => 'النسبة (%)', 'col_type' => 'النوع', 'col_acc' => 'الحساب المرتبط', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات',
            'empty' => 'لا توجد ضرائب مسجلة.', 'confirm_del' => 'هل أنت متأكد من حذف هذه الضريبة؟', 'general' => 'عام (المركز الرئيسي)',
            'st_active' => 'نشط', 'st_inactive' => 'معطل'
        ],
        'en' => [
            'title' => 'Tax Settings', 'desc' => 'Manage tax rates and types applied in the system and link to GL accounts.',
            'add_btn' => 'Add New Tax', 'stat_total' => 'Total Taxes', 'stat_vat' => 'VAT Taxes', 'stat_wh' => 'Withholding Taxes',
            'search_ph' => 'Search tax name...', 'all_branches' => '-- All Branches --', 'all_types' => '-- All Types --',
            'type_vat' => 'VAT', 'type_wh' => 'Withholding', 'type_other' => 'Other',
            'btn_search' => 'Search', 'col_name' => 'Tax Name', 'col_rate' => 'Rate (%)', 'col_type' => 'Type', 'col_acc' => 'Linked Account', 'col_status' => 'Status', 'col_actions' => 'Actions',
            'empty' => 'No taxes found.', 'confirm_del' => 'Are you sure you want to delete this tax?', 'general' => 'General (HQ)',
            'st_active' => 'Active', 'st_inactive' => 'Inactive'
        ]
    ][$isRtl ? 'ar' : 'en'];

    function getTaxTypeBadge($type, $t) {
        $map = [
            'vat' => ['bg' => '#e0f2fe', 'color' => '#0284c7', 'label' => $t['type_vat'], 'icon' => 'ph-receipt'],
            'withholding' => ['bg' => '#ffedd5', 'color' => '#ea580c', 'label' => $t['type_wh'], 'icon' => 'ph-scissors'],
            'other' => ['bg' => '#f1f5f9', 'color' => '#475569', 'label' => $t['type_other'], 'icon' => 'ph-dots-three']
        ];
        $s = $map[$type] ?? $map['other'];
        return "<span style='background:{$s['bg']}; color:{$s['color']}; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem; border:1px solid currentColor; display:inline-flex; align-items:center; gap:4px;'><i class='ph-bold {$s['icon']}'></i> {$s['label']}</span>";
    }
?>

<style>
    :root {
        --c-tx: #0d9488; --c-tx-dark: #0f766e; --c-tx-light: #ccfbf1;
        --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b;
    }
    .tx-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>;}
    .tx-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0; }
    .tx-title-box { display: flex; align-items: center; gap: 16px; }
    .tx-icon { width: 50px; height: 50px; background: var(--c-tx-light); color: var(--c-tx); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.15); flex-shrink: 0; }
    .tx-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-tx { background: linear-gradient(135deg, var(--c-tx), var(--c-tx-dark)); color: #ffffff !important; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25); transition: 0.3s;}
    .btn-tx:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(13, 148, 136, 0.35); }

    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:900px){ .kpi-row { grid-template-columns: 1fr; } }
    .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-info h4 { margin: 0 0 6px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.6rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }
    .kpi-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }

    .search-bar { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px; display: flex; gap: 12px; align-items: center; margin-bottom: 24px; flex-wrap:wrap; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .form-control { border: 1px solid var(--c-border); border-radius: 10px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; color: var(--c-text-dark); font-weight: 600; outline: none; transition: 0.3s;}
    .form-control:focus { border-color: var(--c-tx); background:#fff; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 11px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; transition: 0.3s;}
    .btn-search:hover { background: #000; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .tx-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .tx-table th { padding: 16px 20px; background: #f1f5f9; color: var(--c-text-muted); font-weight: 900; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.78rem; text-align: start; }
    .tx-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; text-align: start; }
    .tx-table tr:hover td { background: #f8fafc; }

    .action-btn { width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; transition: all 0.2s; margin: 0 2px;}
    .action-btn:hover { background: var(--c-tx-light); color: var(--c-tx); border-color: #5eead4; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; transition:0.3s;}
    .page-link.active { background: var(--c-tx); color: #ffffff; border-color: var(--c-tx); }
    .page-link:hover:not(.active) { background: #f1f5f9; }
</style>

<div class="tx-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="tx-header">
        <div class="tx-title-box">
            <div class="tx-icon"><i class="ph-duotone ph-receipt"></i></div>
            <div>
                <h2 class="tx-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/accounting/taxes/create" class="btn-tx"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ccfbf1; color: #0f766e; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #99f6e4; display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-check-circle" style="font-size:1.2rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca; display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-warning-circle" style="font-size:1.2rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-info">
                <h4><?= $t['stat_total'] ?></h4>
                <p><?= number_format((float)($stats->total ?? 0)) ?></p>
            </div>
            <div class="kpi-icon" style="background:#f1f5f9; color:#475569;"><i class="ph-duotone ph-files"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4 style="color:#0284c7;"><?= $t['stat_vat'] ?></h4>
                <p style="color:#0284c7;"><?= number_format((float)($stats->vat_count ?? 0)) ?></p>
            </div>
            <div class="kpi-icon" style="background:#e0f2fe; color:#0284c7;"><i class="ph-duotone ph-receipt"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4 style="color:#ea580c;"><?= $t['stat_wh'] ?></h4>
                <p style="color:#ea580c;"><?= number_format((float)($stats->withholding_count ?? 0)) ?></p>
            </div>
            <div class="kpi-icon" style="background:#ffedd5; color:#ea580c;"><i class="ph-duotone ph-scissors"></i></div>
        </div>
    </div>

    <form action="/ERP/accounting/taxes" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2;" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars((string)($search ?? '')) ?>">
        
        <?php if(!empty($branches)): ?>
        <select name="branch_id" class="form-control" style="flex:1;">
            <option value=""><?= $t['all_branches'] ?></option>
            <?php foreach($branches as $b): 
                $bName = $isRtl ? ($b->name_ar ?? '') : ($b->name_en ?: ($b->name_ar ?? ''));
            ?>
                <option value="<?= $b->id ?? 0 ?>" <?= ((string)($branchFilter ?? '') == (string)($b->id ?? 0))?'selected':'' ?>><?= htmlspecialchars($bName) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <select name="tax_type" class="form-control" style="flex:1;">
            <option value=""><?= $t['all_types'] ?></option>
            <option value="vat" <?= ((string)($typeFilter ?? '')==='vat')?'selected':'' ?>><?= $t['type_vat'] ?></option>
            <option value="withholding" <?= ((string)($typeFilter ?? '')==='withholding')?'selected':'' ?>><?= $t['type_wh'] ?></option>
            <option value="other" <?= ((string)($typeFilter ?? '')==='other')?'selected':'' ?>><?= $t['type_other'] ?></option>
        </select>
        
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
    </form>

    <div class="table-card">
        <div style="overflow-x:auto;">
            <table class="tx-table">
                <thead>
                    <tr>
                        <th style="width: 25%;"><?= $t['col_name'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_rate'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_type'] ?></th>
                        <th style="width: 20%;"><?= $t['col_acc'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($taxes)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach ($taxes as $tx): 
                        $txName = $isRtl ? ($tx->name_ar ?? '') : ($tx->name_en ?: ($tx->name_ar ?? ''));
                        $branchName = !empty($tx->branch_name) ? ($isRtl ? $tx->branch_name : ($tx->branch_name_en ?: $tx->branch_name)) : $t['general'];
                        $accName = $isRtl ? ($tx->acc_name ?? '') : ($tx->acc_name_en ?: ($tx->acc_name ?? ''));
                    ?>
                        <tr>
                            <td>
                                <div style="font-weight: 900; color: var(--c-tx-dark); font-size: 1.05rem;">
                                    <?= htmlspecialchars($txName) ?>
                                </div>
                                <?php if(!empty($branches)): ?>
                                    <div style="font-size:0.75rem; color:var(--c-text-muted); font-weight:bold; margin-top:4px;"><i class="ph-bold ph-git-branch"></i> <?= htmlspecialchars($branchName) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; font-family: monospace; font-weight: 900; color: #ea580c; font-size:1.1rem;"><?= number_format((float)($tx->rate ?? 0), 2) ?>%</td>
                            <td style="text-align: center;"><?= getTaxTypeBadge((string)($tx->tax_type ?? 'vat'), $t) ?></td>
                            <td>
                                <div style="font-size:0.85rem; font-weight:800; color:#0f172a;"><span style="font-family:monospace; color:var(--c-tx);"><?= htmlspecialchars((string)($tx->acc_code ?? '')) ?></span> - <?= htmlspecialchars($accName) ?></div>
                            </td>
                            <td style="text-align: center;">
                                <?php if(!empty($tx->is_active)): ?>
                                    <span style="background:#ecfdf5; color:#059669; padding:4px 10px; border-radius:6px; font-size:0.75rem; font-weight:800; border:1px solid #a7f3d0;"><?= $t['st_active'] ?></span>
                                <?php else: ?>
                                    <span style="background:#fef2f2; color:#dc2626; padding:4px 10px; border-radius:6px; font-size:0.75rem; font-weight:800; border:1px solid #fecaca;"><?= $t['st_inactive'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="/ERP/accounting/taxes/<?= $tx->id ?? 0 ?>" class="action-btn" title="التقرير"><i class="ph-bold ph-chart-line-up"></i></a>
                                <a href="/ERP/accounting/taxes/<?= $tx->id ?? 0 ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                <form action="/ERP/accounting/taxes/<?= $tx->id ?? 0 ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_del'] ?>');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode((string)($search ?? '')) ?>&tax_type=<?= urlencode((string)($typeFilter ?? '')) ?>&branch_id=<?= urlencode((string)($branchFilter ?? '')) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php 
} catch (Throwable $e) {
    echo "<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 View Error (index.php)</h3>" . htmlspecialchars($e->getMessage()) . "<br>Line: " . $e->getLine() . "</div>";
}
?>