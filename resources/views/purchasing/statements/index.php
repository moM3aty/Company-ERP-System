<?php
// Path: resources/views/purchasing/statements/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'كشوف حسابات الموردين', 
        'desc' => 'متابعة الأرصدة المستحقة، الفواتير، السدادات والشرائح المالية لكل مورد.',
        'search_placeholder' => 'ابحث باسم المورد، الكود، أو رقم الهاتف...', 
        'search_btn' => 'بحث', 
        'clear' => 'إلغاء',
        'stat_total_sups' => 'إجمالي الموردين',
        'stat_total_invoiced' => 'إجمالي المشتريات (الفواتير)',
        'stat_total_balance' => 'إجمالي صافي المستحقات',
        'col_code' => 'الكود',
        'col_sup' => 'اسم المورد', 
        'col_phone' => 'الهاتف',
        'col_invoiced' => 'إجمالي الفواتير',
        'col_paid' => 'المدفوع',
        'col_returned' => 'المرتجع',
        'col_net' => 'صافي الرصيد المستحق', 
        'col_actions' => 'إجراءات', 
        'empty' => 'لا يوجد موردين يطابقون بحثك.',
        'view_statement' => 'كشف الحساب'
    ],
    'en' => [
        'title' => 'Supplier Statements of Account', 
        'desc' => 'Track payables, invoices, payments, and detailed ledgers per supplier.',
        'search_placeholder' => 'Search by supplier name, code, or phone...', 
        'search_btn' => 'Search', 
        'clear' => 'Clear',
        'stat_total_sups' => 'Total Suppliers',
        'stat_total_invoiced' => 'Total Invoiced Amount',
        'stat_total_balance' => 'Total Net Balance Due',
        'col_code' => 'Code',
        'col_sup' => 'Supplier Name', 
        'col_phone' => 'Phone Number',
        'col_invoiced' => 'Total Invoiced',
        'col_paid' => 'Total Paid',
        'col_returned' => 'Total Returned',
        'col_net' => 'Net Due Balance', 
        'col_actions' => 'Actions', 
        'empty' => 'No suppliers found.',
        'view_statement' => 'View Statement'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-navy: #0f172a;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
        --c-rose: #e11d48;
        --c-emerald: #059669;
    }

    .mod-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .mod-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .mod-title-box { display: flex; align-items: center; gap: 16px; }
    .mod-icon { width: 48px; height: 48px; background: #e2e8f0; color: var(--c-navy); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; }
    .mod-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 800; }
    .mod-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: var(--c-text-muted); font-weight: 500; }

    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
    @media (max-width: 768px) { .kpi-row { grid-template-columns: 1fr; } }
    .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; background: #f1f5f9; color: var(--c-navy); }
    .kpi-info h4 { margin: 0 0 4px 0; font-size: 0.78rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .kpi-info p { margin: 0; font-size: 1.35rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 12px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .search-input { flex: 1; border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 16px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; }
    .search-input:focus { border-color: var(--c-navy); background: #ffffff; outline: none; box-shadow: 0 0 0 3px #e2e8f0; }
    .btn-search { background: var(--c-navy); color: #ffffff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; }
    .btn-clear { background: #f1f5f9; color: var(--c-text-muted); border: 1px solid var(--c-border); padding: 10px 20px; border-radius: 8px; font-weight: 800; text-decoration: none; display: flex; align-items: center; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .mod-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .mod-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .mod-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .mod-table tr:hover td { background: #f8fafc; }
    
    .btn-stmt { background: var(--c-navy); color: #ffffff !important; padding: 8px 16px; border-radius: 8px; font-weight: 800; text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
    .btn-stmt:hover { background: #1e293b; transform: translateY(-1px); }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; transition: 0.2s; }
    .page-link.active { background: var(--c-navy); color: #ffffff; border-color: var(--c-navy); }
</style>

<div class="mod-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="mod-header">
        <div class="mod-title-box">
            <div class="mod-icon"><i class="ph-duotone ph-receipt"></i></div>
            <div>
                <h2 class="mod-title"><?= $t['title'] ?></h2>
                <p class="mod-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="ph-duotone ph-buildings"></i></div>
            <div class="kpi-info">
                <h4><?= $t['stat_total_sups'] ?></h4>
                <p><?= count($statements ?? []) ?></p>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-duotone ph-file-text"></i></div>
            <div class="kpi-info">
                <h4 style="color:#2563eb;"><?= $t['stat_total_invoiced'] ?></h4>
                <p><?= number_format($totalInvoicedSum ?? 0, 2) ?> <span style="font-size:0.8rem; color:#64748b;"><?= $currency ?></span></p>
            </div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid var(--c-rose);">
            <div class="kpi-icon" style="background:#ffe4e6; color:var(--c-rose);"><i class="ph-duotone ph-coins"></i></div>
            <div class="kpi-info">
                <h4 style="color:var(--c-rose);"><?= $t['stat_total_balance'] ?></h4>
                <p><?= number_format($totalBalanceSum ?? 0, 2) ?> <span style="font-size:0.8rem; color:#64748b;"><?= $currency ?></span></p>
            </div>
        </div>
    </div>

    <form action="/ERP/purchasing/statements" method="GET" class="search-bar">
        <input type="text" name="search" class="search-input" placeholder="<?= $t['search_placeholder'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['search_btn'] ?></button>
        <?php if(!empty($search)): ?>
            <a href="/ERP/purchasing/statements" class="btn-clear"><i class="ph-bold ph-x"></i> <?= $t['clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th style="width: 12%;"><?= $t['col_code'] ?></th>
                        <th style="width: 23%;"><?= $t['col_sup'] ?></th>
                        <th style="width: 15%;"><?= $t['col_phone'] ?></th>
                        <th style="width: 13%; text-align: end;"><?= $t['col_invoiced'] ?></th>
                        <th style="width: 13%; text-align: end;"><?= $t['col_paid'] ?></th>
                        <th style="width: 12%; text-align: end;"><?= $t['col_returned'] ?></th>
                        <th style="width: 12%; text-align: end;"><?= $t['col_net'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($statements)): ?>
                        <tr><td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach ($statements as $st): ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: 800; color: #db2777;">
                                <?= htmlspecialchars($st->code) ?>
                            </td>
                            <td>
                                <div style="font-weight: 800; color: #0f172a;"><i class="ph-fill ph-buildings text-slate-400"></i> <?= htmlspecialchars($st->supplier_name) ?></div>
                            </td>
                            <td style="font-family: monospace; font-weight: 700; color: #334155;">
                                <?= htmlspecialchars($st->phone ?? '---') ?>
                            </td>
                            <td style="text-align: end; font-family: monospace; font-weight: 800; color: #2563eb;">
                                <?= number_format($st->total_invoiced, 2) ?>
                            </td>
                            <td style="text-align: end; font-family: monospace; font-weight: 800; color: #059669;">
                                <?= number_format($st->total_paid, 2) ?>
                            </td>
                            <td style="text-align: end; font-family: monospace; font-weight: 800; color: #d97706;">
                                <?= number_format($st->total_returned, 2) ?>
                            </td>
                            <td style="text-align: end; font-family: monospace; font-weight: 900; font-size: 1.05rem; color: <?= $st->net_balance > 0 ? '#dc2626' : '#059669' ?>;">
                                <?= number_format($st->net_balance, 2) ?> <?= $currency ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="/ERP/purchasing/statements/<?= $st->id ?>" class="btn-stmt"><i class="ph-bold ph-file-text"></i> <?= $t['view_statement'] ?></a>
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>