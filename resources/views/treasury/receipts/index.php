<?php
// Path: resources/views/treasury/receipts/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$methodsMap = [
    'cash' => ['label' => __('نقدي (Cash)', 'Cash'), 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-money'],
    'bank_transfer' => ['label' => __('تحويل بنكي', 'Bank Transfer'), 'color' => '#2563eb', 'bg' => '#eff6ff', 'icon' => 'ph-bank'],
    'cheque' => ['label' => __('شيك', 'Cheque'), 'color' => '#d97706', 'bg' => '#fffbeb', 'icon' => 'ph-receipt'],
    'pos' => ['label' => __('شبكة / مدى', 'POS / Card'), 'color' => '#7c3aed', 'bg' => '#f5f3ff', 'icon' => 'ph-credit-card'],
];
?>
<style>
    :root {
        --c-rec: #0d9488;
        --c-rec-dark: #0f766e;
        --c-rec-light: #f0fdf4;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .rec-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .rec-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .rec-title-box { display: flex; align-items: center; gap: 16px; }
    .rec-icon { width: 48px; height: 48px; background: var(--c-rec-light); color: var(--c-rec); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(13, 148, 136, 0.12); }
    .rec-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-rec { background: linear-gradient(135deg, var(--c-rec), var(--c-rec-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2); }
    .btn-rec.disabled { opacity: 0.5; pointer-events: none; cursor: not-allowed; }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-rec-light); color: var(--c-rec); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .rec-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .rec-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .rec-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-rec-light); color: var(--c-rec); border-color: #99f6e4; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-rec); color: #ffffff; border-color: var(--c-rec); }

    .badge-method { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
    .branch-badge { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; border: 1px solid #cbd5e1; display: inline-block; margin-top: 4px;}
</style>

<div class="rec-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <div class="rec-header">
        <div class="rec-title-box">
            <div class="rec-icon"><i class="ph-duotone ph-receipt"></i></div>
            <div>
                <h2 class="rec-title"><?= __('سندات القبض (Receipt Vouchers)', 'Receipt Vouchers') ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= __('تسجيل وإدارة كافة المقبوضات النقدية والتحويلات الواردة للصناديق والبنوك.', 'Manage all cash inflows, incoming transfers, and collections.') ?></p>
            </div>
        </div>
        
        <?php if (has_permission('treasury_receipts_create')): ?>
            <a href="/ERP/treasury/receipts/create" class="btn-rec"><i class="ph-bold ph-plus"></i> <?= __('إنشاء سند قبض جديد', 'Create Receipt Voucher') ?></a>
        <?php else: ?>
            <a href="javascript:void(0)" class="btn-rec disabled" title="<?= __('ليس لديك صلاحية', 'No Permission') ?>"><i class="ph-bold ph-plus"></i> <?= __('إنشاء سند قبض جديد', 'Create Receipt Voucher') ?></a>
        <?php endif; ?>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-files"></i></div><div class="kpi-info"><h4><?= __('إجمالي السندات', 'Total Vouchers') ?></h4><p><?= number_format($stats->total_vouchers ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#f0fdf4; color:#0d9488;"><i class="ph-duotone ph-currency-circle-dollar"></i></div><div class="kpi-info"><h4 style="color:#0d9488;"><?= __('إجمالي المقبوضات', 'Total Receipts') ?></h4><p><?= number_format((float)($stats->total_amount ?? 0), 2) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-money"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= __('الوارد النقدي', 'Cash Inflow') ?></h4><p><?= number_format((float)($stats->total_cash ?? 0), 2) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-duotone ph-bank"></i></div><div class="kpi-info"><h4 style="color:#2563eb;"><?= __('تحويلات وبنوك', 'Bank Transfers') ?></h4><p><?= number_format((float)($stats->total_bank ?? 0), 2) ?></p></div></div>
    </div>

    <form action="/ERP/treasury/receipts" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="<?= __('ابحث برقم السند، اسم المسلم، البيان، المرجع...', 'Search by voucher no, payer, description...') ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="payment_method" class="form-control" style="flex:1; min-width:150px;">
            <option value=""><?= __('-- كل طرق الدفع --', '-- All Methods --') ?></option>
            <option value="cash" <?= ($methodFilter==='cash')?'selected':'' ?>><?= __('نقدي (Cash)', 'Cash') ?></option>
            <option value="bank_transfer" <?= ($methodFilter==='bank_transfer')?'selected':'' ?>><?= __('تحويل بنكي', 'Bank Transfer') ?></option>
            <option value="cheque" <?= ($methodFilter==='cheque')?'selected':'' ?>><?= __('شيك', 'Cheque') ?></option>
            <option value="pos" <?= ($methodFilter==='pos')?'selected':'' ?>><?= __('شبكة / مدى', 'POS / Card') ?></option>
        </select>
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($fromDate ?? '') ?>">
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($toDate ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= __('فلترة', 'Filter') ?></button>
    </form>

    <div class="table-card">
        <table class="rec-table">
            <thead>
                <tr>
                    <th style="width: 12%;"><?= __('رقم السند', 'Voucher No') ?></th>
                    <th style="width: 12%;"><?= __('التاريخ', 'Date') ?></th>
                    <th style="width: 20%;"><?= __('الصندوق / البنك المستلم', 'Account / Safe') ?></th>
                    <th style="width: 20%;"><?= __('استلمنا من (العميل/الحساب)', 'Received From') ?></th>
                    <th style="width: 12%;"><?= __('طريقة الدفع', 'Method') ?></th>
                    <th style="width: 12%;"><?= __('المبلغ', 'Amount') ?></th>
                    <th style="width: 12%; text-align: center;"><?= __('إجراءات', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($receipts)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= __('لا توجد سندات قبض مسجلة بالمواصفات المحددة.', 'No receipt vouchers found.') ?></td></tr>
                <?php else: foreach ($receipts as $r): 
                    $m = $methodsMap[$r->payment_method] ?? $methodsMap['cash'];
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-rec-dark); font-size: 0.95rem;">
                            <?php if (has_permission('treasury_receipts_view')): ?>
                                <a href="/ERP/treasury/receipts/<?= $r->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($r->voucher_number) ?></a>
                            <?php else: ?>
                                <?= htmlspecialchars($r->voucher_number) ?>
                            <?php endif; ?>
                        </td>
                        <td style="font-family: monospace; font-weight: 700;"><?= htmlspecialchars($r->receipt_date) ?></td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($r->account_name ?? __('الخزينة العامة', 'Main Safe')) ?></div>
                            <?php if (is_hq() && !empty($r->branch_name)): ?>
                                <div class="branch-badge"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars($r->branch_name) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($r->payer_name ?: ($r->customer_name ?? __('عام', 'General'))) ?></div>
                            <div style="font-size: 0.75rem; color: var(--c-text-muted);"><?= htmlspecialchars($r->description ?? '') ?></div>
                        </td>
                        <td>
                            <span class="badge-method" style="background:<?= $m['bg'] ?>; color:<?= $m['color'] ?>;">
                                <i class="ph-bold <?= $m['icon'] ?>"></i> <?= $m['label'] ?>
                            </span>
                        </td>
                        <td style="font-family: monospace; font-weight: 900; color: #059669; font-size: 1rem;"><?= number_format((float)$r->amount, 2) ?></td>
                        <td style="text-align: center; white-space: nowrap;">
                            
                            <?php if (has_permission('treasury_receipts_view')): ?>
                                <a href="/ERP/treasury/receipts/<?= $r->id ?>" class="action-btn" title="<?= __('عرض وسند الطباعة', 'View & Print') ?>"><i class="ph-bold ph-printer"></i></a>
                            <?php endif; ?>

                            <?php if (has_permission('treasury_receipts_edit')): ?>
                                <a href="/ERP/treasury/receipts/<?= $r->id ?>/edit" class="action-btn" title="<?= __('تعديل', 'Edit') ?>"><i class="ph-bold ph-pencil-simple"></i></a>
                            <?php endif; ?>

                            <?php if (has_permission('treasury_receipts_delete')): ?>
                                <form action="/ERP/treasury/receipts/<?= $r->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= __('هل أنت متأكد من حذف هذا السند؟', 'Are you sure you want to delete this voucher?') ?>');">
                                    <button type="submit" class="action-btn delete" title="<?= __('حذف', 'Delete') ?>"><i class="ph-bold ph-trash"></i></button>
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&payment_method=<?= urlencode($methodFilter) ?>&start_date=<?= urlencode($fromDate) ?>&end_date=<?= urlencode($toDate) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>