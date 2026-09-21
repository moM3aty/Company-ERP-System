<?php
// Path: resources/views/treasury/accounts/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title' => 'إدارة الخزائن والحسابات البنكية', 'desc' => 'تعريف متابعة أرصدة الصناديق، العهد، والحسابات المصرفية بالكامل.',
        'add_btn' => 'إضافة حساب / خزينة جديد', 'col_code' => 'كود الحساب', 'col_name' => 'اسم الحساب / الخزينة',
        'col_type' => 'نوع الحساب', 'col_bal' => 'الرصيد الحالي', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات',
        'empty' => 'لا توجد خزائن أو حسابات مسجلة.', 'search_ph' => 'ابحث برقم الكود، اسم الخزينة، اسم البنك...',
        'btn_search' => 'فلترة', 'btn_clear' => 'إلغاء', 'kpi_count' => 'إجمالي الحسابات والخزائن',
        'kpi_total' => 'الرصيد الإجمالي التراكمي', 'kpi_bank' => 'أرصدة البنوك', 'kpi_cash' => 'أرصدة الخزائن والنقدية',
        'active_scope' => 'الفرع النشط:', 'bank_acc' => 'حساب بنكي', 'cash_safe' => 'خزينة نقدية',
        'status_active' => 'نشط', 'status_inactive' => 'معطل', 'confirm_delete' => 'هل أنت متأكد من حذف الحساب؟'
    ],
    'en' => [
        'title' => 'Treasury & Bank Accounts', 'desc' => 'Manage cash safes, petty cash, and bank account balances.',
        'add_btn' => 'New Account / Safe', 'col_code' => 'Account Code', 'col_name' => 'Account / Safe Name',
        'col_type' => 'Account Type', 'col_bal' => 'Current Balance', 'col_status' => 'Status', 'col_actions' => 'Actions',
        'empty' => 'No treasury or bank accounts found.', 'search_ph' => 'Search by code, safe name, bank name...',
        'btn_search' => 'Filter', 'btn_clear' => 'Clear', 'kpi_count' => 'Total Accounts & Safes',
        'kpi_total' => 'Total Accumulated Balance', 'kpi_bank' => 'Bank Balances', 'kpi_cash' => 'Cash & Safe Balances',
        'active_scope' => 'Active Branch:', 'bank_acc' => 'Bank Account', 'cash_safe' => 'Cash Safe',
        'status_active' => 'Active', 'status_inactive' => 'Inactive', 'confirm_delete' => 'Are you sure you want to delete this account?'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-acc: #0891b2;
        --c-acc-dark: #0e7490;
        --c-acc-light: #ecfeff;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .acc-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .acc-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .acc-title-box { display: flex; align-items: center; gap: 16px; }
    .acc-icon { width: 48px; height: 48px; background: var(--c-acc-light); color: var(--c-acc); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(8, 145, 178, 0.12); }
    .acc-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-acc { background: linear-gradient(135deg, var(--c-acc), var(--c-acc-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(8, 145, 178, 0.2); }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 24px; }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-acc-light); color: var(--c-acc); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .acc-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .acc-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .acc-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-acc-light); color: var(--c-acc); border-color: #a5f3fc; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-acc); color: #ffffff; border-color: var(--c-acc); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-block; }
</style>

<div class="acc-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="acc-header">
        <div class="acc-title-box">
            <div class="acc-icon"><i class="ph-duotone ph-vault"></i></div>
            <div>
                <h2 class="acc-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/treasury/accounts/create" class="btn-acc"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-acc);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-acc); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-bank"></i></div><div class="kpi-info"><h4><?= $t['kpi_count'] ?></h4><p><?= number_format($stats->total_accounts ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfeff; color:#0891b2;"><i class="ph-duotone ph-coins"></i></div><div class="kpi-info"><h4 style="color:#0891b2;"><?= $t['kpi_total'] ?></h4><p><?= number_format($convert($stats->total_balance ?? 0), 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-duotone ph-credit-card"></i></div><div class="kpi-info"><h4 style="color:#2563eb;"><?= $t['kpi_bank'] ?></h4><p><?= number_format($convert($stats->bank_balance ?? 0), 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-money"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= $t['kpi_cash'] ?></h4><p><?= number_format($convert($stats->cash_balance ?? 0), 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></p></div></div>
    </div>

    <form action="/ERP/treasury/accounts" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:220px;" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="status" class="form-control" style="flex:1; min-width:150px;">
            <option value=""><?= $isRtl ? '-- كل الحالات --' : '-- All Statuses --' ?></option>
            <option value="active" <?= ($statusFilter==='active')?'selected':'' ?>><?= $t['status_active'] ?></option>
            <option value="inactive" <?= ($statusFilter==='inactive')?'selected':'' ?>><?= $t['status_inactive'] ?></option>
        </select>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($statusFilter)): ?>
            <a href="/ERP/treasury/accounts" class="btn-search" style="background:#f1f5f9; color:#475569; text-decoration:none;"><i class="ph-bold ph-x"></i> <?= $t['btn_clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="acc-table">
            <thead>
                <tr>
                    <th style="width: 15%;"><?= $t['col_code'] ?></th>
                    <th style="width: 35%;"><?= $t['col_name'] ?></th>
                    <th style="width: 15%;"><?= $t['col_type'] ?></th>
                    <th style="width: 15%;"><?= $t['col_bal'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($accounts)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($accounts as $a): 
                    $isBank = (mb_strpos($a->name_ar, 'بنك') !== false || mb_strpos($a->code, '1112') !== false);
                    $accName = $isRtl ? ($a->name_ar ?? '') : ($a->name_en ?: ($a->name_ar ?? ''));
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-acc-dark); font-size: 1rem;"><?= htmlspecialchars($a->code) ?></td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);">
                                <i class="ph-bold <?= $isBank ? 'ph-bank' : 'ph-wallet' ?>" style="color:<?= $isBank ? '#2563eb' : '#0891b2' ?>;"></i>
                                <?= htmlspecialchars($accName) ?>
                            </div>
                            <?php if($isRtl && !empty($a->name_en)): ?>
                                <div style="font-size:0.75rem; color:var(--c-text-muted); margin-top:2px; font-family:sans-serif;" dir="ltr"><?= htmlspecialchars($a->name_en) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge-status" style="<?= $isBank ? 'background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;' : 'background:#ecfeff; color:#0891b2; border:1px solid #a5f3fc;' ?>">
                                <?= $isBank ? $t['bank_acc'] : $t['cash_safe'] ?>
                            </span>
                        </td>
                        <td style="font-family: monospace; font-weight: 900; color: <?= (float)$a->current_balance >= 0 ? '#059669' : '#dc2626' ?>; font-size: 1rem;">
                            <?= number_format($convert($a->current_balance ?? 0), 2) ?> <span style="font-size:0.75rem; color:#64748b;"><?= $currency ?></span>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="<?= !empty($a->is_active) ? 'background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;' : 'background:#fef2f2; color:#dc2626; border:1px solid #fecaca;' ?>">
                                <?= !empty($a->is_active) ? $t['status_active'] : $t['status_inactive'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/treasury/accounts/<?= $a->id ?>" class="action-btn" title="عرض التفاصيل ودفتر الحساب"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/treasury/accounts/<?= $a->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/treasury/accounts/<?= $a->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
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