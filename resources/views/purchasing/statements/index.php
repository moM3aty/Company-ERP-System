<?php
// Path: resources/views/purchasing/statements/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'كشف حساب الموردين (Supplier Statements)', 'desc' => 'متابعة الأرصدة التراكمية، إجمالي المشتريات، والالتزامات المالية لكل مورد.',
        'col_sup' => 'المورد / الكود', 'col_invoiced' => 'إجمالي المشتريات (له)', 'col_paid' => 'المدفوع والمرتجع (عليه)',
        'col_balance' => 'الرصيد المتبقي (الصافي)', 'col_actions' => 'إجراءات', 'empty' => 'لا توجد بيانات حسابات تطابق بحثك.'
    ],
    'en' => [
        'title' => 'Supplier Statements', 'desc' => 'Track running balances, total purchases, and outstanding payables per vendor.',
        'col_sup' => 'Supplier / Code', 'col_invoiced' => 'Total Invoiced (Credit)', 'col_paid' => 'Paid / Returned (Debit)',
        'col_balance' => 'Net Balance Due', 'col_actions' => 'Actions', 'empty' => 'No supplier statement records found.'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-sky: #0284c7;
        --c-sky-dark: #0369a1;
        --c-sky-light: #e0f2fe;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
    }

    .mod-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .mod-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .mod-title-box { display: flex; align-items: center; gap: 16px; }
    .mod-icon { width: 48px; height: 48px; background: var(--c-sky-light); color: var(--c-sky); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(2, 132, 199, 0.15); }
    .mod-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 800; }
    .mod-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: var(--c-text-muted); font-weight: 500; }

    .kpi-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: var(--c-sky-light); color: var(--c-sky); }
    .kpi-info h4 { margin: 0 0 4px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .kpi-info p { margin: 0; font-size: 1.4rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 12px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .search-input { flex: 1; border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 16px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; }
    .search-input:focus { border-color: var(--c-sky); background: #ffffff; outline: none; box-shadow: 0 0 0 3px var(--c-sky-light); }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; }
    .btn-clear { background: #f1f5f9; color: var(--c-text-muted); border: 1px solid var(--c-border); padding: 10px 20px; border-radius: 8px; font-weight: 800; text-decoration: none; display: flex; align-items: center; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .mod-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .mod-table th { padding: 16px 24px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .mod-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .mod-table tr:hover td { background: #f8fafc; }
    
    .action-btn { padding: 8px 16px; border-radius: 8px; border: 1px solid #bae6fd; background: var(--c-sky-light); color: var(--c-sky); display: inline-flex; align-items: center; gap: 6px; cursor: pointer; text-decoration: none; font-weight: 800; font-size: 0.85rem; transition: 0.2s; }
    .action-btn:hover { background: var(--c-sky); color: #ffffff; border-color: var(--c-sky); }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; transition: 0.2s; }
    .page-link:hover { background: #f1f5f9; color: var(--c-text-dark); }
    .page-link.active { background: var(--c-sky); color: #ffffff; border-color: var(--c-sky); }
</style>

<div class="mod-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="mod-header">
        <div class="mod-title-box">
            <div class="mod-icon"><i class="ph-duotone ph-book-open"></i></div>
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
            <div class="kpi-icon"><i class="ph-duotone ph-shopping-cart"></i></div>
            <div class="kpi-info"><h4>إجمالي مشتريات الموردين (الصفحة)</h4><p><?= number_format($totalInvoicedSum, 2) ?></p></div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid var(--c-sky);">
            <div class="kpi-icon"><i class="ph-duotone ph-scales"></i></div>
            <div class="kpi-info"><h4 style="color:var(--c-sky);">إجمالي الرصيد المتبقي للموردين</h4><p><?= number_format($totalBalanceSum, 2) ?></p></div>
        </div>
    </div>

    <!-- Search Form -->
    <form action="/ERP/purchasing/statements" method="GET" class="search-bar">
        <input type="text" name="search" class="search-input" placeholder="ابحث باسم المورد، الكود، أو رقم الهاتف..." value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> بحث</button>
        <?php if(!empty($search)): ?>
            <a href="/ERP/purchasing/statements" class="btn-clear"><i class="ph-bold ph-x"></i> إلغاء</a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="mod-table">
            <thead>
                <tr>
                    <th style="width: 25%;"><?= $t['col_sup'] ?></th>
                    <th style="width: 20%; text-align: end;"><?= $t['col_invoiced'] ?></th>
                    <th style="width: 20%; text-align: end;"><?= $t['col_paid'] ?></th>
                    <th style="width: 20%; text-align: end;"><?= $t['col_balance'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($statements)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($statements as $st): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark); font-size: 1rem;"><i class="ph-fill ph-buildings text-slate-400"></i> <?= htmlspecialchars($st->supplier_name) ?></div>
                            <div style="color: var(--c-sky); font-family: monospace; font-size: 0.85rem; font-weight:bold; margin-top:2px;"><?= htmlspecialchars($st->code) ?></div>
                        </td>
                        <td style="text-align: end; font-family: monospace; font-weight: 800; color: #0f172a; font-size: 1rem;">
                            <?= number_format($st->total_invoiced, 2) ?>
                        </td>
                        <td style="text-align: end; font-family: monospace; font-weight: 800; color: #059669; font-size: 1rem;">
                            <?= number_format($st->total_paid + $st->total_returned, 2) ?>
                        </td>
                        <td style="text-align: end; font-family: monospace; font-weight: 900; font-size: 1.05rem; color: <?= $st->net_balance > 0 ? '#dc2626' : '#059669' ?>;">
                            <?= number_format($st->net_balance, 2) ?>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/purchasing/statements/<?= $st->id ?>" class="action-btn" title="عرض كشف الحساب"><i class="ph-bold ph-file-text"></i> كشف الحساب</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
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