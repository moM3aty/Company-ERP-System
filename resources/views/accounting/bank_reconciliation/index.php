<?php
// Path: resources/views/accounting/bank_reconciliation/index.php

try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
    $currency = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');

    $flashMsg = $_SESSION['flash_msg'] ?? null;
    $flashErr = $_SESSION['flash_err'] ?? null;
    unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

    // مصفوفة الترجمة للواجهة
    $t = [
        'ar' => [
            'title' => 'التسويات ومطابقة الحسابات البنكية', 'desc' => 'مطابقة كشف حساب البنك مع حركة الدفاتر المالية وإظهار الفروقات.',
            'add_btn' => 'مذكرة تسوية جديدة', 'stat_total' => 'إجمالي المذكرات', 'stat_comp' => 'تسويات معتمدة', 'stat_draft' => 'قيد المطابقة',
            'search_ph' => 'ابحث برقم التسوية أو الملاحظات...', 'all_branches' => '-- جميع الفروع --', 'all_accounts' => '-- جميع الحسابات البنكية --',
            'all_statuses' => '-- جميع الحالات --', 'st_draft' => 'قيد المطابقة (Draft)', 'st_comp' => 'معتمدة (Reconciled)',
            'btn_search' => 'بحث', 'col_num' => 'رقم التسوية', 'col_acc' => 'الحساب البنكي', 'col_date' => 'تاريخ الكشف',
            'col_stmt' => 'رصيد الكشف', 'col_book' => 'رصيد الدفاتر', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات',
            'empty' => 'لا توجد مذكرات تسوية بنكية مسجلة.', 'confirm_del' => 'هل أنت متأكد من حذف مذكرة التسوية؟',
            'general' => 'عام (المركز الرئيسي)', 'badge_draft' => 'قيد المطابقة', 'badge_comp' => 'تسوية معتمدة'
        ],
        'en' => [
            'title' => 'Bank Reconciliations', 'desc' => 'Match bank statements with general ledger and review differences.',
            'add_btn' => 'New Reconciliation', 'stat_total' => 'Total Reconciliations', 'stat_comp' => 'Completed', 'stat_draft' => 'Pending Match',
            'search_ph' => 'Search by recon number or notes...', 'all_branches' => '-- All Branches --', 'all_accounts' => '-- All Bank Accounts --',
            'all_statuses' => '-- All Statuses --', 'st_draft' => 'Draft (Pending)', 'st_comp' => 'Reconciled (Completed)',
            'btn_search' => 'Search', 'col_num' => 'Recon No.', 'col_acc' => 'Bank Account', 'col_date' => 'Statement Date',
            'col_stmt' => 'Stmt. Balance', 'col_book' => 'Book Balance', 'col_status' => 'Status', 'col_actions' => 'Actions',
            'empty' => 'No bank reconciliations found.', 'confirm_del' => 'Are you sure you want to delete this reconciliation?',
            'general' => 'General (HQ)', 'badge_draft' => 'Pending Draft', 'badge_comp' => 'Completed'
        ]
    ][$isRtl ? 'ar' : 'en'];

    function getRecStatusBadge($status, $t) {
        $map = [
            'draft'      => ['bg' => '#fef3c7', 'color' => '#d97706', 'label' => $t['badge_draft']],
            'reconciled' => ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => $t['badge_comp']]
        ];
        $s = $map[$status] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $status];
        return "<span style='background:{$s['bg']}; color:{$s['color']}; padding:6px 14px; border-radius:8px; font-weight:800; font-size:0.78rem; border:1px solid currentColor; display:inline-block; white-space:nowrap;'>{$s['label']}</span>";
    }
?>

<style>
    :root {
        --c-br: #059669; --c-br-dark: #047857; --c-br-light: #ecfdf5;
        --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b;
    }
    .br-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .br-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .br-title-box { display: flex; align-items: center; gap: 16px; }
    .br-icon { width: 50px; height: 50px; background: var(--c-br-light); color: var(--c-br); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.12); flex-shrink: 0; }
    .br-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-br { background: linear-gradient(135deg, var(--c-br), var(--c-br-dark)); color: #ffffff !important; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2); white-space: nowrap; }

    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:900px){ .kpi-row { grid-template-columns: 1fr; } }
    .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-info h4 { margin: 0 0 6px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.4rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }
    .kpi-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }

    .search-bar { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px; display: flex; gap: 12px; align-items: center; margin-bottom: 24px; flex-wrap:wrap; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .form-control { border: 1px solid var(--c-border); border-radius: 10px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; color: var(--c-text-dark); font-weight: 600; outline: none; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 11px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .br-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .br-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.78rem; text-align: start; }
    .br-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; text-align: start; }

    .action-btn { width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; transition: all 0.2s; }
    .action-btn:hover { background: var(--c-br-light); color: var(--c-br); border-color: #a7f3d0; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-br); color: #ffffff; border-color: var(--c-br); }
</style>

<div class="br-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="br-header">
        <div class="br-title-box">
            <div class="br-icon"><i class="ph-duotone ph-bank"></i></div>
            <div>
                <h2 class="br-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/accounting/bank-reconciliation/create" class="btn-br"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0; display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-check-circle" style="font-size:1.2rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca; display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-warning-circle" style="font-size:1.2rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-info">
                <h4><?= $t['stat_total'] ?></h4>
                <p><?= number_format((float)($stats->total_recs ?? 0)) ?></p>
            </div>
            <div class="kpi-icon" style="background:#f1f5f9; color:#475569;"><i class="ph-duotone ph-files"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4 style="color:#059669;"><?= $t['stat_comp'] ?></h4>
                <p style="color:#059669;"><?= number_format((float)($stats->reconciled_count ?? 0)) ?></p>
            </div>
            <div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4 style="color:#d97706;"><?= $t['stat_draft'] ?></h4>
                <p style="color:#d97706;"><?= number_format((float)($stats->draft_count ?? 0)) ?></p>
            </div>
            <div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-clock"></i></div>
        </div>
    </div>

    <form action="/ERP/accounting/bank-reconciliation" method="GET" class="search-bar">
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

        <select name="account_id" class="form-control" style="flex:1;">
            <option value=""><?= $t['all_accounts'] ?></option>
            <?php if(!empty($bankAccounts)): foreach($bankAccounts as $acc): 
                $aName = $isRtl ? ($acc->name_ar ?? '') : ($acc->name_en ?: ($acc->name_ar ?? ''));
            ?>
                <option value="<?= $acc->id ?? 0 ?>" <?= ((string)($accountId ?? '') == (string)($acc->id ?? 0))?'selected':'' ?>><?= htmlspecialchars($acc->code ?? '') ?> - <?= htmlspecialchars($aName) ?></option>
            <?php endforeach; endif; ?>
        </select>
        
        <select name="status" class="form-control" style="flex:1;">
            <option value=""><?= $t['all_statuses'] ?></option>
            <option value="draft" <?= ((string)($statusFilter ?? '')==='draft')?'selected':'' ?>><?= $t['st_draft'] ?></option>
            <option value="reconciled" <?= ((string)($statusFilter ?? '')==='reconciled')?'selected':'' ?>><?= $t['st_comp'] ?></option>
        </select>
        
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
    </form>

    <div class="table-card">
        <table class="br-table">
            <thead>
                <tr>
                    <th style="width: 14%;"><?= $t['col_num'] ?></th>
                    <th style="width: 25%;"><?= $t['col_acc'] ?></th>
                    <th style="width: 13%; text-align: center;"><?= $t['col_date'] ?></th>
                    <th style="width: 13%; text-align: center;"><?= $t['col_stmt'] ?></th>
                    <th style="width: 13%; text-align: center;"><?= $t['col_book'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 12%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reconciliations)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($reconciliations as $r): 
                    $aName = $isRtl ? ($r->acc_name ?? '') : ($r->acc_name_en ?: ($r->acc_name ?? ''));
                    $branchName = !empty($r->branch_name) ? ($isRtl ? $r->branch_name : ($r->branch_name_en ?: $r->branch_name)) : null;
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-br-dark); font-size: 0.95rem;">
                            <?= htmlspecialchars((string)($r->reconciliation_number ?? '')) ?>
                        </td>
                        <td style="font-weight: 800; color: var(--c-text-dark);">
                            <?= htmlspecialchars((string)($r->acc_code ?? '')) ?> - <?= htmlspecialchars($aName) ?>
                            <?php if($branchName): ?>
                                <div style="font-size:0.75rem; color:var(--c-text-muted); font-weight:bold; margin-top:2px;"><i class="ph-bold ph-git-branch"></i> <?= htmlspecialchars($branchName) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; font-family: monospace; font-weight: 800; color: #0f172a;"><?= htmlspecialchars((string)($r->statement_date ?? '')) ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 900; color: #2563eb;"><?= number_format((float)($r->statement_balance ?? 0), 2) ?> <?= $currency ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 900; color: #0f172a;"><?= number_format((float)($r->book_balance ?? 0), 2) ?> <?= $currency ?></td>
                        <td style="text-align: center;"><?= getRecStatusBadge((string)($r->status ?? ''), $t) ?></td>
                        <td style="text-align: center; white-space: nowrap;">
                            <div style="display: flex; justify-content: center; gap: 6px;">
                                <a href="/ERP/accounting/bank-reconciliation/<?= $r->id ?? 0 ?>" class="action-btn" title="ورشة العمل والمطابقة"><i class="ph-bold ph-sliders-horizontal"></i></a>
                                <?php if(($r->status ?? '') === 'draft'): ?>
                                    <form action="/ERP/accounting/bank-reconciliation/<?= $r->id ?? 0 ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_del'] ?>');">
                                        <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
                                    </form>
                                <?php endif; ?>
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
                <a href="?page=<?= $i ?>&search=<?= urlencode((string)($search ?? '')) ?>&account_id=<?= urlencode((string)($accountId ?? '')) ?>&status=<?= urlencode((string)($statusFilter ?? '')) ?>&branch_id=<?= urlencode((string)($branchFilter ?? '')) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
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