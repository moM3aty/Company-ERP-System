<?php
// Path: resources/views/treasury/reports/cash_book.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$treasuryAccounts = $treasuryAccounts ?? [];
$movements = $movements ?? [];
$selectedAccount = $selectedAccount ?? null;
$openingBalance = $openingBalance ?? 0.00;
$totalIn = $totalIn ?? 0.00;
$totalOut = $totalOut ?? 0.00;
$closingBalance = $closingBalance ?? 0.00;
$totalPages = $totalPages ?? 1;
$currentPage = $currentPage ?? 1;
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-t');
$search = $search ?? '';
$accountId = $accountId ?? null;
?>

<style>
    :root {
        --c-cb: #1d4ed8;
        --c-cb-dark: #1e40af;
        --c-cb-light: #eff6ff;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .cb-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .cb-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .cb-title-box { display: flex; align-items: center; gap: 16px; }
    .cb-icon { width: 48px; height: 48px; background: var(--c-cb-light); color: var(--c-cb); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(29, 78, 216, 0.12); }
    .cb-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-print { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-cb-light); color: var(--c-cb); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-cb); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .cb-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .cb-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .cb-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-cb); color: #ffffff; border-color: var(--c-cb); }

    .badge-type { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-block; }

    @media print {
        .nt-sidebar, .top-header, .cb-header, .search-bar, .pagination, header, aside { display: none !important; }
        body { background: #fff !important; }
        .cb-wrapper { max-width: 100% !important; }
        .table-card, .kpi-card { border: 1px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="cb-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="cb-header">
        <div class="cb-title-box">
            <div class="cb-icon"><i class="ph-duotone ph-book-open-text"></i></div>
            <div>
                <h2 class="cb-title"><?= __('دفتر الصندوق والخزينة (Cash Book Report)', 'Cash Book Report') ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= __('كشف وحركة السيولة والوارد والصادر بالرصيد الافتتاحي والتراكمي.', 'Detailed cash inflow/outflow ledger with opening and closing balances.') ?></p>
            </div>
        </div>
        <?php if(has_permission('treasury_reports_export')): ?>
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= __('طباعة كشف الصندوق', 'Print Cash Book') ?></button>
        <?php endif; ?>
    </div>

    <form action="/ERP/treasury/reports/cash-book" method="GET" class="search-bar">
        <select name="account_id" class="form-control" style="flex:2; min-width:220px;" required>
            <?php if (empty($treasuryAccounts)): ?>
                <option value=""><?= __('-- لا توجد خزائن أو حسابات مسجلة --', '-- No accounts available --') ?></option>
            <?php else: foreach($treasuryAccounts as $acc): ?>
                <option value="<?= $acc->id ?>" <?= ($selectedAccount && $selectedAccount->id == $acc->id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?> 
                    <?= is_hq() && !empty($acc->branch_name) ? " [{$acc->branch_name}]" : '' ?>
                </option>
            <?php endforeach; endif; ?>
        </select>
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>" title="<?= __('من تاريخ', 'From Date') ?>">
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>" title="<?= __('إلى تاريخ', 'To Date') ?>">
        <input type="text" name="search" class="form-control" style="flex:1; min-width:180px;" placeholder="<?= __('ابحث بالمرجع أو البيان...', 'Search reference or description...') ?>" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= __('عرض التقرير', 'View Report') ?></button>
    </form>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="ph-duotone ph-vault"></i></div>
            <div class="kpi-info">
                <h4><?= __('الرصيد الافتتاحي', 'Opening Balance') ?></h4>
                <p style="color:var(--c-text-dark);"><?= number_format($openingBalance, 2) ?></p>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-arrow-down-left"></i></div>
            <div class="kpi-info">
                <h4 style="color:#059669;"><?= __('إجمالي المقبوضات (وارد)', 'Total Inflow') ?></h4>
                <p style="color:#059669;"><?= number_format($totalIn, 2) ?></p>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-arrow-up-right"></i></div>
            <div class="kpi-info">
                <h4 style="color:#dc2626;"><?= __('إجمالي المدفوعات (صادر)', 'Total Outflow') ?></h4>
                <p style="color:#dc2626;"><?= number_format($totalOut, 2) ?></p>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#eff6ff; color:#1d4ed8;"><i class="ph-duotone ph-scales"></i></div>
            <div class="kpi-info">
                <h4 style="color:#1d4ed8;"><?= __('الرصيد النهائي الحالي', 'Closing Balance') ?></h4>
                <p style="color:#1d4ed8;"><?= number_format($closingBalance, 2) ?></p>
            </div>
        </div>
    </div>

    <div class="table-card">
        <table class="cb-table">
            <thead>
                <tr>
                    <th style="width: 12%;"><?= __('التاريخ', 'Date') ?></th>
                    <th style="width: 14%;"><?= __('المرجع / الرقم', 'Reference/No') ?></th>
                    <th style="width: 12%;"><?= __('نوع الحركة', 'Tx Type') ?></th>
                    <th style="width: 20%;"><?= __('الجهة / التفاصيل', 'Party/Details') ?></th>
                    <th style="width: 22%;"><?= __('البيان', 'Description') ?></th>
                    <th style="width: 10%; text-align: center;"><?= __('وارد (+)', 'In (+)') ?></th>
                    <th style="width: 10%; text-align: center;"><?= __('صادر (-)', 'Out (-)') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= __('لا توجد حركات نقدية مسجلة بالنطاق المحدد.', 'No cash movements found in the specified range.') ?></td></tr>
                <?php else: foreach ($movements as $m): 
                    $isIn = (float)$m->in_amount > 0;
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 700;"><?= htmlspecialchars($m->tx_date) ?></td>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-cb-dark);"><?= htmlspecialchars($m->ref_no) ?></td>
                        <td>
                            <span class="badge-type" style="<?= $isIn ? 'background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;' : 'background:#fef2f2; color:#dc2626; border:1px solid #fecaca;' ?>">
                                <?= htmlspecialchars($m->tx_type) ?>
                            </span>
                        </td>
                        <td style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($m->party) ?></td>
                        <td style="font-size: 0.82rem; color: var(--c-text-muted);"><?= htmlspecialchars($m->description ?: '---') ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 900; color: #059669; font-size: 0.95rem;">
                            <?= $isIn ? number_format((float)$m->in_amount, 2) : '-' ?>
                        </td>
                        <td style="text-align: center; font-family: monospace; font-weight: 900; color: #dc2626; font-size: 0.95rem;">
                            <?= !$isIn ? number_format((float)$m->out_amount, 2) : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&account_id=<?= $accountId ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&search=<?= urlencode($search) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>