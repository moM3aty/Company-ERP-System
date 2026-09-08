<?php
// Path: resources/views/accounting/accounts/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'دليل الحسابات (Chart of Accounts)', 'desc' => 'الشجرة المالية المنظمة لجميع الحسابات الرئيسية والفرعية الخاصة بالمنشأة.',
        'add_btn' => 'إضافة حساب جديد', 'search' => 'ابحث برقم الحساب، أو الاسم...',
        'type_all' => '-- كل الأنواع --', 'branch_all' => '-- كل الفروع --', 'btn_search' => 'تصفية وبحث', 'clear' => 'إلغاء',
        'stat_total' => 'إجمالي الحسابات', 'stat_ast' => 'الأصول', 'stat_lia' => 'الالتزامات', 'stat_eqt' => 'حقوق الملكية', 'stat_rev_exp' => 'الإيرادات والمصروفات',
        'col_code' => 'الكود', 'col_name' => 'اسم الحساب والمستوى', 'col_type' => 'طبيعة الحساب', 'col_parent' => 'الحساب الأب', 'col_bal' => 'الرصيد الحالي', 'col_actions' => 'الإجراءات',
        'empty' => 'لا توجد حسابات مالية مسجلة.', 'confirm_del' => 'هل أنت متأكد من حذف الحساب نهائياً؟',
        'type_asset' => 'أصول (Assets)', 'type_liab' => 'التزامات (Liabilities)', 'type_eqt' => 'حقوق ملكية (Equity)', 'type_rev' => 'إيرادات (Revenues)', 'type_exp' => 'مصروفات (Expenses)',
        'general' => 'عام (الشركة)'
    ],
    'en' => [
        'title' => 'Chart of Accounts', 'desc' => 'Structured financial tree for all parent and sub-accounts of the organization.',
        'add_btn' => 'Add Account', 'search' => 'Search by code or name...',
        'type_all' => '-- All Types --', 'branch_all' => '-- All Branches --', 'btn_search' => 'Filter', 'clear' => 'Clear',
        'stat_total' => 'Total Accounts', 'stat_ast' => 'Assets', 'stat_lia' => 'Liabilities', 'stat_eqt' => 'Equity', 'stat_rev_exp' => 'Rev & Exp',
        'col_code' => 'Code', 'col_name' => 'Account Name & Level', 'col_type' => 'Type', 'col_parent' => 'Parent Account', 'col_bal' => 'Current Balance', 'col_actions' => 'Actions',
        'empty' => 'No accounts found.', 'confirm_del' => 'Are you sure you want to delete this account?',
        'type_asset' => 'Assets', 'type_liab' => 'Liabilities', 'type_eqt' => 'Equity', 'type_rev' => 'Revenues', 'type_exp' => 'Expenses',
        'general' => 'General (Company)'
    ]
][$isRtl ? 'ar' : 'en'];

function getAccountTypeBadgeView($type, $t) {
    $map = [
        'asset'     => ['bg' => '#d1fae5', 'color' => '#059669', 'label' => $t['type_asset']],
        'liability' => ['bg' => '#ffe4e6', 'color' => '#e11d48', 'label' => $t['type_liab']],
        'equity'    => ['bg' => '#e0e7ff', 'color' => '#4338ca', 'label' => $t['type_eqt']],
        'revenue'   => ['bg' => '#dbeafe', 'color' => '#0284c7', 'label' => $t['type_rev']],
        'expense'   => ['bg' => '#ffedd5', 'color' => '#ea580c', 'label' => $t['type_exp']]
    ];
    $s = $map[$type] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $type];
    return "<span style='background:{$s['bg']}; color:{$s['color']}; padding:6px 12px; border-radius:8px; font-weight:800; font-size:0.75rem; border:1px solid currentColor;'>{$s['label']}</span>";
}
?>

<style>
    :root { 
        --brand-primary: #059669; --brand-primary-dark: #047857; --brand-primary-light: #d1fae5; 
        --surface: #ffffff; --surface-hover: #f8fafc; --bg-body: #f1f5f9;
        --text-main: #0f172a; --text-muted: #64748b; --border-color: #e2e8f0;
        --radius-lg: 24px; --radius-md: 16px;
        --shadow-soft: 0 10px 30px -10px rgba(15, 23, 42, 0.08);
        --shadow-hover: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
    }
    .acc-wrapper { padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: var(--bg-body);}
    
    .glass-header { position: sticky; top: 0; z-index: 50; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); padding: 20px 30px; margin-bottom: 30px; border-bottom: 1px solid rgba(226, 232, 240, 0.6); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
    .header-icon { width: 56px; height: 56px; background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)); color: white; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3); }
    .header-text h1 { margin: 0; font-size: 1.8rem; font-weight: 900; color: var(--text-main); }
    .header-text p { margin: 4px 0 0 0; font-size: 0.95rem; font-weight: 600; color: var(--text-muted); }
    
    .btn-primary { background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)); color: white !important; padding: 12px 24px; border-radius: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 8px 20px rgba(5, 150, 105, 0.25); transition: 0.3s; border: none;}
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(5, 150, 105, 0.35); }

    .kpi-row { padding: 0 30px; display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 30px; }
    @media(max-width:1100px){ .kpi-row { grid-template-columns: repeat(3, 1fr); } }
    @media(max-width:768px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    
    .kpi-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 20px; display: flex; align-items: center; gap: 14px; box-shadow: var(--shadow-soft); transition: 0.4s;}
    .kpi-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-hover); }
    .kpi-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: var(--brand-primary-light); color: var(--brand-primary); }
    .kpi-info h4 { margin: 0 0 4px 0; font-size: 0.8rem; color: var(--text-muted); font-weight: 800; text-transform:uppercase;}
    .kpi-info p { margin: 0; font-size: 1.6rem; font-weight: 900; font-family: monospace; color: var(--text-main); }

    .search-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px; margin: 0 30px 30px 30px; display: flex; gap: 12px; box-shadow: var(--shadow-soft); align-items: center;}
    .form-control { border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 16px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; color: var(--text-main); transition: 0.3s;}
    .form-control:focus { outline: none; border-color: var(--brand-primary); background: #fff; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15); }
    .btn-search { background: var(--text-main); color: #fff; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; transition: 0.3s;}
    .btn-search:hover { background: #000; box-shadow: var(--shadow-soft);}

    .table-container { margin: 0 30px; background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-soft); }
    .modern-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: start; }
    .modern-table th { padding: 18px 20px; background: var(--surface-hover); color: var(--text-muted); font-weight: 900; text-transform: uppercase; border-bottom: 2px solid var(--border-color); font-size: 0.8rem; }
    .modern-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .modern-table tr:hover td { background: var(--surface-hover); }

    .action-btn { width: 38px; height: 38px; border-radius: 10px; border: 1px solid var(--border-color); background: #fff; color: var(--text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 3px; transition: 0.3s; }
    .action-btn:hover { background: var(--brand-primary-light); color: var(--brand-primary); border-color: #6ee7b7; }
    .action-btn.delete:hover { background: #ffe4e6; color: #e11d48; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 30px; }
    .page-link { width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid var(--border-color); background: #ffffff; color: var(--text-muted); text-decoration: none; font-weight: 900; transition:0.3s;}
    .page-link.active { background: var(--brand-primary); color: #ffffff; border-color: var(--brand-primary); box-shadow: 0 4px 10px rgba(5,150,105,0.2);}
    .page-link:hover:not(.active) { background: var(--surface-hover); }
</style>

<div class="acc-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="glass-header">
        <div style="display:flex; align-items:center; gap:18px;">
            <div class="header-icon"><i class="ph-duotone ph-tree-structure"></i></div>
            <div class="header-text">
                <h1><?= $t['title'] ?></h1>
                <p><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/accounting/chart-of-accounts/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <!-- Alert for Database Issues if any -->
    <?php if (!empty($dbErrors)): ?>
        <div style="margin: 0 30px 24px 30px; background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 16px; border-radius: 14px; font-weight: bold; font-family: monospace; display:flex; align-items:center; gap:10px; box-shadow:var(--shadow-soft);">
            <i class="ph-bold ph-warning-circle" style="font-size:1.6rem;"></i>
            <div>
                <strong>تنبيه بقاعدة البيانات:</strong> الجداول تفتقر لتحديثات دعم الفروع، يتم استخدام الوضع العام (الآمن) مؤقتاً.
                <?php foreach($dbErrors as $err): ?>
                    <div style="font-size: 0.8rem; margin-top: 4px;">- <?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if($flashMsg): ?><div style="margin: 0 30px 24px 30px; background: #ecfdf5; color: #059669; padding: 16px 20px; border-radius: 14px; font-weight: 800; border: 1px solid #a7f3d0; display:flex; align-items:center; gap:10px;"><i class="ph-fill ph-check-circle" style="font-size:1.4rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="margin: 0 30px 24px 30px; background: #fff1f2; color: #e11d48; padding: 16px 20px; border-radius: 14px; font-weight: 800; border: 1px solid #fecdd3; display:flex; align-items:center; gap:10px;"><i class="ph-fill ph-warning-circle" style="font-size:1.4rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-circles-four"></i></div><div class="kpi-info"><h4><?= $t['stat_total'] ?></h4><p><?= number_format($stats->total_accounts ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-vault"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= $t['stat_ast'] ?></h4><p><?= number_format($stats->assets_count ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fff1f2; color:#e11d48;"><i class="ph-duotone ph-receipt"></i></div><div class="kpi-info"><h4 style="color:#e11d48;"><?= $t['stat_lia'] ?></h4><p><?= number_format($stats->liabilities_count ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#e0e7ff; color:#4338ca;"><i class="ph-duotone ph-scales"></i></div><div class="kpi-info"><h4 style="color:#4338ca;"><?= $t['stat_eqt'] ?></h4><p><?= number_format($stats->equity_count ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ffedd5; color:#ea580c;"><i class="ph-duotone ph-trend-up"></i></div><div class="kpi-info"><h4 style="color:#ea580c;"><?= $t['stat_rev_exp'] ?></h4><p><?= number_format(($stats->revenue_count ?? 0) + ($stats->expense_count ?? 0)) ?></p></div></div>
    </div>

    <form action="/ERP/accounting/chart-of-accounts" method="GET" class="search-card">
        <input type="text" name="search" class="form-control" style="flex:2;" placeholder="<?= $t['search'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        
        <?php if(!empty($branches)): ?>
        <select name="branch_id" class="form-control" style="flex:1;">
            <option value=""><?= $t['branch_all'] ?></option>
            <?php foreach($branches as $b): 
                $bName = $isRtl ? $b->name_ar : ($b->name_en ?: $b->name_ar);
            ?>
                <option value="<?= $b->id ?>" <?= ($branchFilter == $b->id) ? 'selected' : '' ?>><?= htmlspecialchars($bName) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <select name="type" class="form-control" style="flex:1;">
            <option value=""><?= $t['type_all'] ?></option>
            <option value="asset" <?= ($typeFilter==='asset')?'selected':'' ?>><?= $t['type_asset'] ?></option>
            <option value="liability" <?= ($typeFilter==='liability')?'selected':'' ?>><?= $t['type_liab'] ?></option>
            <option value="equity" <?= ($typeFilter==='equity')?'selected':'' ?>><?= $t['type_eqt'] ?></option>
            <option value="revenue" <?= ($typeFilter==='revenue')?'selected':'' ?>><?= $t['type_rev'] ?></option>
            <option value="expense" <?= ($typeFilter==='expense')?'selected':'' ?>><?= $t['type_exp'] ?></option>
        </select>
        
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($typeFilter) || !empty($branchFilter)): ?>
            <a href="/ERP/accounting/chart-of-accounts" class="btn-search" style="background:var(--surface-hover); color:var(--text-muted); text-decoration:none;"><i class="ph-bold ph-x"></i> <?= $t['clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-container">
        <div style="overflow-x:auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 15%;"><?= $t['col_code'] ?></th>
                        <th style="width: 30%;"><?= $t['col_name'] ?></th>
                        <th style="width: 15%;"><?= $t['col_type'] ?></th>
                        <th style="width: 15%;"><?= $t['col_parent'] ?></th>
                        <th style="width: 12%; text-align: center;"><?= $t['col_bal'] ?></th>
                        <th style="width: 13%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($accounts)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 50px; color: var(--text-muted); font-weight: 800; font-size:1.1rem;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach ($accounts as $a): 
                        $lvl = max(1, (int)($a->account_level ?? 1));
                        $indent = ($lvl - 1) * 20;
                        $aName = $isRtl ? ($a->name_ar ?: $a->name_en) : ($a->name_en ?: $a->name_ar);
                        $pName = $isRtl ? ($a->parent_name ?: ($a->parent_code ?? '---')) : (($a->parent_code ?? '---'));
                        $branchBadge = !empty($a->branch_name) ? ($isRtl ? $a->branch_name : ($a->branch_name_en ?: $a->branch_name)) : $t['general'];
                    ?>
                        <tr>
                            <td><div style="font-weight: 900; color: var(--text-main); font-family: monospace; font-size: 1.05rem; padding-inline-start:<?= $indent ?>px;"><?= htmlspecialchars($a->code ?? '---') ?></div></td>
                            <td>
                                <div style="font-weight: 800; color: var(--text-main); padding-inline-start: <?= $indent ?>px; display:flex; align-items:center; gap:8px;">
                                    <?= !empty($a->is_parent) ? '<i class="ph-fill ph-folder" style="color:var(--brand-primary); font-size:1.3rem;"></i>' : '<i class="ph-fill ph-file-text" style="color:var(--text-muted); font-size:1.3rem;"></i>' ?>
                                    <?= htmlspecialchars($aName) ?>
                                </div>
                                <?php if(!empty($branches)): ?>
                                <div style="padding-inline-start: <?= $indent + 25 ?>px; margin-top:4px;">
                                    <span style="background:var(--surface-hover); border:1px solid var(--border-color); color:var(--text-muted); padding:2px 6px; border-radius:4px; font-size:0.7rem; font-weight:bold;">
                                        <i class="ph-bold ph-git-branch"></i> <?= htmlspecialchars($branchBadge) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td><?= getAccountTypeBadgeView($a->type ?? '', $t) ?></td>
                            <td><span style="font-size:0.85rem; color:var(--text-muted); font-weight:800;"><?= htmlspecialchars($pName) ?></span></td>
                            <td style="text-align: center; font-family: monospace; font-weight: 900; color: var(--text-main); font-size:1.05rem;">
                                <?= number_format((float)($a->current_balance ?? 0), 2) ?> <span style="font-size:0.75rem; color:var(--text-muted);"><?= $currency ?></span>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="/ERP/accounting/chart-of-accounts/<?= $a->id ?>" class="action-btn" title="View Ledger"><i class="ph-bold ph-book-open"></i></a>
                                <a href="/ERP/accounting/chart-of-accounts/<?= $a->id ?>/edit" class="action-btn" title="Edit"><i class="ph-bold ph-pencil-simple"></i></a>
                                <form action="/ERP/accounting/chart-of-accounts/<?= $a->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_del'] ?>');">
                                    <button type="submit" class="action-btn delete" title="Delete"><i class="ph-bold ph-trash"></i></button>
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>&type=<?= urlencode($typeFilter ?? '') ?>&branch_id=<?= urlencode($branchFilter ?? '') ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>