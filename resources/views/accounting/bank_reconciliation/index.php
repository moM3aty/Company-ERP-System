<?php
// Path: resources/views/accounting/bank_reconciliation/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

function getRecStatusBadge($status) {
    $map = [
        'draft'      => ['bg' => '#fef3c7', 'color' => '#d97706', 'label' => 'قيد المطابقة'],
        'reconciled' => ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => 'تسوية معتمدة']
    ];
    $s = $map[$status] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $status];
    return "<span style='background:{$s['bg']}; color:{$s['color']}; padding:6px 14px; border-radius:8px; font-weight:800; font-size:0.78rem; border:1px solid currentColor; display:inline-block; white-space:nowrap;'>{$s['label']}</span>";
}
?>

<style>
    :root {
        --c-br: #059669;
        --c-br-dark: #047857;
        --c-br-light: #ecfdf5;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
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

    .search-bar { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px; display: flex; gap: 12px; align-items: center; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
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
                <h2 class="br-title">التسويات ومطابقة الحسابات البنكية (Bank Reconciliation)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;">مطابقة كشف حساب البنك مع حركة الدفاتر المالية وإظهار الفروقات.</p>
            </div>
        </div>
        <a href="/ERP/accounting/bank-reconciliation/create" class="btn-br"><i class="ph-bold ph-plus"></i> مذكرة تسوية جديدة</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0; display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-check-circle" style="font-size:1.2rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca; display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-warning-circle" style="font-size:1.2rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-info">
                <h4>إجمالي المذكرات</h4>
                <p><?= number_format($stats->total_recs ?? 0) ?></p>
            </div>
            <div class="kpi-icon" style="background:#f1f5f9; color:#475569;"><i class="ph-duotone ph-files"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4 style="color:#059669;">تسويات معتمدة</h4>
                <p style="color:#059669;"><?= number_format($stats->reconciled_count ?? 0) ?></p>
            </div>
            <div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4 style="color:#d97706;">قيد المطابقة</h4>
                <p style="color:#d97706;"><?= number_format($stats->draft_count ?? 0) ?></p>
            </div>
            <div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-clock"></i></div>
        </div>
    </div>

    <form action="/ERP/accounting/bank-reconciliation" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2;" placeholder="ابحث برقم التسوية، أو الملاحظات..." value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="account_id" class="form-control" style="flex:1;">
            <option value="">-- جميع الحسابات البنكية --</option>
            <?php foreach($bankAccounts as $acc): ?>
                <option value="<?= $acc->id ?>" <?= ($accountId == $acc->id)?'selected':'' ?>><?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="form-control" style="flex:1;">
            <option value="">-- جميع الحالات --</option>
            <option value="draft" <?= ($statusFilter==='draft')?'selected':'' ?>>قيد المطابقة (Draft)</option>
            <option value="reconciled" <?= ($statusFilter==='reconciled')?'selected':'' ?>>معتمدة (Reconciled)</option>
        </select>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> بحث</button>
    </form>

    <div class="table-card">
        <table class="br-table">
            <thead>
                <tr>
                    <th style="width: 14%;">رقم التسوية</th>
                    <th style="width: 25%;">الحساب البنكي</th>
                    <th style="width: 13%; text-align: center;">تاريخ الكشف</th>
                    <th style="width: 13%; text-align: center;">رصيد الكشف</th>
                    <th style="width: 13%; text-align: center;">رصيد الدفاتر</th>
                    <th style="width: 10%; text-align: center;">الحالة</th>
                    <th style="width: 12%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reconciliations)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد مذكرات تسوية بنكية مسجلة.</td></tr>
                <?php else: foreach ($reconciliations as $r): ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-br-dark); font-size: 0.95rem;">
                            <?= htmlspecialchars($r->reconciliation_number) ?>
                        </td>
                        <td style="font-weight: 800; color: var(--c-text-dark);">
                            <?= htmlspecialchars($r->acc_code) ?> - <?= htmlspecialchars($r->acc_name) ?>
                        </td>
                        <td style="text-align: center; font-family: monospace; font-weight: 800; color: #0f172a;"><?= htmlspecialchars($r->statement_date) ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 900; color: #2563eb;"><?= number_format((float)$r->statement_balance, 2) ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 900; color: #0f172a;"><?= number_format((float)$r->book_balance, 2) ?></td>
                        <td style="text-align: center;"><?= getRecStatusBadge($r->status) ?></td>
                        <td style="text-align: center; white-space: nowrap;">
                            <div style="display: flex; justify-content: center; gap: 6px;">
                                <a href="/ERP/accounting/bank-reconciliation/<?= $r->id ?>" class="action-btn" title="ورشة العمل والمطابقة"><i class="ph-bold ph-sliders-horizontal"></i></a>
                                <?php if($r->status === 'draft'): ?>
                                    <form action="/ERP/accounting/bank-reconciliation/<?= $r->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف مذكرة التسوية؟');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>&account_id=<?= urlencode($accountId ?? '') ?>&status=<?= urlencode($statusFilter ?? '') ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>