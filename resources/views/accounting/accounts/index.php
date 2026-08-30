<?php
// Path: resources/views/accounting/accounts/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

function getAccountTypeBadge($type) {
    $map = [
        'asset'     => ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => 'أصول (Assets)'],
        'liability' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => 'التزامات (Liabilities)'],
        'equity'    => ['bg' => '#eff6ff', 'color' => '#2563eb', 'label' => 'حقوق ملكية (Equity)'],
        'revenue'   => ['bg' => '#f0fdf4', 'color' => '#16a34a', 'label' => 'إيرادات (Revenues)'],
        'expense'   => ['bg' => '#fff7ed', 'color' => '#c2410c', 'label' => 'مصروفات (Expenses)']
    ];
    $s = $map[$type] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $type];
    return "<span style='background:{$s['bg']}; color:{$s['color']}; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem; border:1px solid currentColor;'>{$s['label']}</span>";
}
?>

<style>
    :root {
        --c-acc: #059669;
        --c-acc-dark: #047857;
        --c-acc-light: #dcfce7;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .acc-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .acc-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .acc-title-box { display: flex; align-items: center; gap: 16px; }
    .acc-icon { width: 48px; height: 48px; background: var(--c-acc-light); color: var(--c-acc); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.12); }
    .acc-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 800; }
    .btn-acc { background: linear-gradient(135deg, var(--c-acc), var(--c-acc-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }

    .kpi-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-acc-light); color: var(--c-acc); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .acc-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .acc-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .acc-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-acc-light); color: var(--c-acc); border-color: #a7f3d0; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-acc); color: #ffffff; border-color: var(--c-acc); }
</style>

<div class="acc-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="acc-header">
        <div class="acc-title-box">
            <div class="acc-icon"><i class="ph-duotone ph-tree-structure"></i></div>
            <div>
                <h2 class="acc-title">دليل الحسابات (Chart of Accounts)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);">الشجرة المالية المنظمة لجميع الحسابات الرئيسية والفرعية.</p>
            </div>
        </div>
        <a href="/ERP/accounting/chart-of-accounts/create" class="btn-acc"><i class="ph-bold ph-plus"></i> إضافة حساب جديد</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-circles-four"></i></div><div class="kpi-info"><h4>إجمالي الحسابات</h4><p><?= number_format($stats->total_accounts ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-vault"></i></div><div class="kpi-info"><h4 style="color:#059669;">الأصول</h4><p><?= number_format($stats->assets_count ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-receipt"></i></div><div class="kpi-info"><h4 style="color:#dc2626;">الالتزامات</h4><p><?= number_format($stats->liabilities_count ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-duotone ph-scales"></i></div><div class="kpi-info"><h4 style="color:#2563eb;">حقوق الملكية</h4><p><?= number_format($stats->equity_count ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fff7ed; color:#c2410c;"><i class="ph-duotone ph-trend-up"></i></div><div class="kpi-info"><h4 style="color:#c2410c;">الإيرادات/المصروفات</h4><p><?= number_format(($stats->revenue_count ?? 0) + ($stats->expense_count ?? 0)) ?></p></div></div>
    </div>

    <form action="/ERP/accounting/chart-of-accounts" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2;" placeholder="ابحث برقم الحساب، الاسم بالعربي، أو الإنجليزي..." value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="type" class="form-control" style="flex:1;">
            <option value="">-- كل الأنواع --</option>
            <option value="asset" <?= ($typeFilter==='asset')?'selected':'' ?>>أصول (Assets)</option>
            <option value="liability" <?= ($typeFilter==='liability')?'selected':'' ?>>التزامات (Liabilities)</option>
            <option value="equity" <?= ($typeFilter==='equity')?'selected':'' ?>>حقوق ملكية (Equity)</option>
            <option value="revenue" <?= ($typeFilter==='revenue')?'selected':'' ?>>إيرادات (Revenues)</option>
            <option value="expense" <?= ($typeFilter==='expense')?'selected':'' ?>>مصروفات (Expenses)</option>
        </select>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> فلترة</button>
    </form>

    <div class="table-card">
      <table class="acc-table">
    <thead>
        <tr>
            <th style="width: 15%;">رمز الحساب</th>
            <th style="width: 30%;">اسم الحساب</th>
            <th style="width: 18%;">النوع</th>
            <th style="width: 15%;">الحساب الأب</th>
            <th style="width: 12%; text-align: center;">الرصيد الحالي</th>
            <th style="width: 10%; text-align: center;">إجراءات</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($accounts)): ?>
            <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد حسابات مالية تطابق بحثك.</td></tr>
        <?php else: foreach ($accounts as $a): ?>
            <tr>
                <td><div style="font-weight: 900; color: var(--c-acc-dark); font-family: monospace; font-size: 1rem;"><?= htmlspecialchars($a->code ?? '---') ?></div></td>
                <td>
                    <div style="font-weight: 800; color: var(--c-text-dark); padding-inline-start: <?= (max(1, (int)($a->account_level ?? 1)) - 1) * 16 ?>px;">
                        <?= !empty($a->is_parent) ? '<i class="ph-bold ph-folder" style="color:var(--c-acc);"></i>' : '<i class="ph-bold ph-file-text" style="color:#94a3b8;"></i>' ?>
                        <?= htmlspecialchars($a->name_ar ?? '') ?>
                    </div>
                </td>
                <td><?= getAccountTypeBadge($a->type ?? '') ?></td>
                <td><span style="font-size:0.85rem; color:#64748b; font-weight:700;"><?= htmlspecialchars($a->parent_name ?? '---') ?></span></td>
                <td style="text-align: center; font-family: monospace; font-weight: 900; color: #0f172a;"><?= number_format((float)($a->current_balance ?? 0), 2) ?></td>
                <td style="text-align: center; white-space: nowrap;">
    <!-- زر العرض / كارت الحساب -->
    <a href="/ERP/accounting/chart-of-accounts/<?= $a->id ?>" class="action-btn" title="عرض كارت الحساب"><i class="ph-bold ph-eye"></i></a>
    
    <!-- زر التعديل -->
    <a href="/ERP/accounting/chart-of-accounts/<?= $a->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
    
    <!-- زر الحذف -->
    <form action="/ERP/accounting/chart-of-accounts/<?= $a->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف الحساب؟');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>&type=<?= urlencode($typeFilter ?? '') ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>