<?php
// Path: resources/views/treasury/receipts/index.php

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
        'title' => 'سندات القبض (Receipt Vouchers)', 'desc' => 'تسجيل وإدارة كافة المقبوضات النقدية والتحويلات الواردة للصناديق والبنوك.',
        'add_btn' => 'إنشاء سند قبض جديد', 'col_num' => 'رقم السند', 'col_date' => 'التاريخ',
        'col_account' => 'الصندوق / البنك المستلم', 'col_payer' => 'استلمنا من (العميل/الحساب)', 'col_method' => 'طريقة الدفع',
        'col_amount' => 'المبلغ', 'col_actions' => 'إجراءات', 'empty' => 'لا توجد سندات قبض مسجلة بالمواصفات المحددة.',
        'search_ph' => 'ابحث برقم السند، اسم المسلم، البيان، المرجع...', 'btn_search' => 'فلترة', 'btn_clear' => 'إلغاء',
        'kpi_total_vouchers' => 'إجمالي السندات', 'kpi_total_amount' => 'إجمالي المقبوضات',
        'kpi_cash' => 'الوارد النقدي', 'kpi_bank' => 'تحويلات وبنوك', 'active_scope' => 'الفرع النشط:',
        'all_methods' => '-- كل طرق الدفع --', 'print_list' => 'طباعة القائمة',
        'cash' => 'نقدي (Cash)', 'bank_transfer' => 'تحويل بنكي', 'cheque' => 'شيك', 'pos' => 'شبكة / مدى',
        'general' => 'عام', 'confirm_delete' => 'هل أنت متأكد من حذف هذا السند؟'
    ],
    'en' => [
        'title' => 'Receipt Vouchers', 'desc' => 'Manage all cash inflows, incoming transfers, and collections.',
        'add_btn' => 'New Receipt Voucher', 'col_num' => 'Voucher No.', 'col_date' => 'Date',
        'col_account' => 'Account / Safe', 'col_payer' => 'Received From', 'col_method' => 'Method',
        'col_amount' => 'Amount', 'col_actions' => 'Actions', 'empty' => 'No receipt vouchers found.',
        'search_ph' => 'Search by voucher no, payer, description...', 'btn_search' => 'Filter', 'btn_clear' => 'Clear',
        'kpi_total_vouchers' => 'Total Vouchers', 'kpi_total_amount' => 'Total Receipts',
        'kpi_cash' => 'Cash Inflow', 'kpi_bank' => 'Bank Transfers', 'active_scope' => 'Active Branch:',
        'all_methods' => '-- All Methods --', 'print_list' => 'Print List',
        'cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque', 'pos' => 'POS / Cards',
        'general' => 'General', 'confirm_delete' => 'Are you sure you want to delete this voucher?'
    ]
][$isRtl ? 'ar' : 'en'];

function getReceiptMethodBadge($method, $t) {
    $map = [
        'cash'          => ['label' => $t['cash'], 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-money'],
        'bank_transfer' => ['label' => $t['bank_transfer'], 'color' => '#2563eb', 'bg' => '#eff6ff', 'icon' => 'ph-bank'],
        'cheque'        => ['label' => $t['cheque'], 'color' => '#d97706', 'bg' => '#fffbeb', 'icon' => 'ph-receipt'],
        'pos'           => ['label' => $t['pos'], 'color' => '#7c3aed', 'bg' => '#f5f3ff', 'icon' => 'ph-credit-card'],
    ];
    $m = $map[$method] ?? $map['cash'];
    return "<span class='badge-method' style='background:{$m['bg']}; color:{$m['color']}; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem; display:inline-flex; align-items:center; gap:4px; border:1px solid currentColor;'><i class='ph-bold {$m['icon']}'></i> {$m['label']}</span>";
}
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
    .rec-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .rec-title-box { display: flex; align-items: center; gap: 16px; }
    .rec-icon { width: 48px; height: 48px; background: var(--c-rec-light); color: var(--c-rec); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(13, 148, 136, 0.12); }
    .rec-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-rec { background: linear-gradient(135deg, var(--c-rec), var(--c-rec-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2); }
    .btn-print { background: #0f172a; color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #1e293b; }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 24px; }

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

    .branch-badge { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; border: 1px solid #cbd5e1; display: inline-block; margin-top: 4px;}

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body { background: #ffffff !important; color: #000000 !important; }
        .nt-sidebar, header, nav, footer { display: none !important; }
        .search-bar, .pagination, .btn-rec, .btn-print, .action-btn { display: none !important; }
        .rec-table th:last-child, .rec-table td:last-child { display: none !important; }
        .rec-wrapper { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; border-radius: 0 !important; }
        .rec-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .rec-table td { border: 1px solid #cbd5e1 !important; color: #000000 !important; }
        .kpi-card { border: 1px solid #000 !important; box-shadow: none !important; break-inside: avoid; }
        .badge-method { border: 1px solid #000 !important; background: transparent !important; color: #000 !important; }
    }
</style>

<div class="rec-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <div class="rec-header">
        <div class="rec-title-box">
            <div class="rec-icon"><i class="ph-duotone ph-receipt"></i></div>
            <div>
                <h2 class="rec-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print_list'] ?></button>
            <a href="/ERP/treasury/receipts/create" class="btn-rec"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-rec);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-rec); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-files"></i></div><div class="kpi-info"><h4><?= $t['kpi_total_vouchers'] ?></h4><p><?= number_format((int)($stats->total_vouchers ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#f0fdf4; color:#0d9488;"><i class="ph-duotone ph-currency-circle-dollar"></i></div><div class="kpi-info"><h4 style="color:#0d9488;"><?= $t['kpi_total_amount'] ?></h4><p><?= number_format($convert($stats->total_amount ?? 0), 2) ?> <span style="font-size:0.75rem;"><?= $currency ?></span></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-money"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= $t['kpi_cash'] ?></h4><p><?= number_format($convert($stats->total_cash ?? 0), 2) ?> <span style="font-size:0.75rem;"><?= $currency ?></span></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-duotone ph-bank"></i></div><div class="kpi-info"><h4 style="color:#2563eb;"><?= $t['kpi_bank'] ?></h4><p><?= number_format($convert($stats->total_bank ?? 0), 2) ?> <span style="font-size:0.75rem;"><?= $currency ?></span></p></div></div>
    </div>

    <form action="/ERP/treasury/receipts" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="payment_method" class="form-control" style="flex:1; min-width:150px;">
            <option value=""><?= $t['all_methods'] ?></option>
            <option value="cash" <?= ($methodFilter==='cash')?'selected':'' ?>><?= $t['cash'] ?></option>
            <option value="bank_transfer" <?= ($methodFilter==='bank_transfer')?'selected':'' ?>><?= $t['bank_transfer'] ?></option>
            <option value="cheque" <?= ($methodFilter==='cheque')?'selected':'' ?>><?= $t['cheque'] ?></option>
            <option value="pos" <?= ($methodFilter==='pos')?'selected':'' ?>><?= $t['pos'] ?></option>
        </select>
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($fromDate ?? '') ?>">
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($toDate ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($methodFilter) || !empty($fromDate) || !empty($toDate)): ?>
            <a href="/ERP/treasury/receipts" class="btn-search" style="background:#f1f5f9; color:#475569; text-decoration:none;"><i class="ph-bold ph-x"></i> <?= $t['btn_clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="rec-table">
            <thead>
                <tr>
                    <th style="width: 12%;"><?= $t['col_num'] ?></th>
                    <th style="width: 12%;"><?= $t['col_date'] ?></th>
                    <th style="width: 20%;"><?= $t['col_account'] ?></th>
                    <th style="width: 20%;"><?= $t['col_payer'] ?></th>
                    <th style="width: 12%;"><?= $t['col_method'] ?></th>
                    <th style="width: 12%;"><?= $t['col_amount'] ?></th>
                    <th style="width: 12%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($receipts)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($receipts as $r): ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-rec-dark); font-size: 0.95rem;">
                            <a href="/ERP/treasury/receipts/<?= $r->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars((string)$r->voucher_number) ?></a>
                        </td>
                        <td style="font-family: monospace; font-weight: 700;"><?= htmlspecialchars((string)$r->receipt_date) ?></td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars((string)($r->account_name ?? '---')) ?></div>
                            <?php if(!empty($r->branch_name)): ?>
                                <div class="branch-badge"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars((string)$r->branch_name) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars((string)($r->payer_name ?: ($r->customer_name ?? $t['general']))) ?></div>
                            <div style="font-size: 0.75rem; color: var(--c-text-muted);"><?= htmlspecialchars((string)($r->description ?? '')) ?></div>
                        </td>
                        <td>
                            <?= getReceiptMethodBadge($r->payment_method ?? 'cash', $t) ?>
                        </td>
                        <td style="font-family: monospace; font-weight: 900; color: #059669; font-size: 1rem;">
                            <?= number_format($convert($r->amount ?? 0), 2) ?> <span style="font-size:0.75rem; color:#64748b;"><?= $currency ?></span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/treasury/receipts/<?= $r->id ?>" class="action-btn" title="عرض وسند الطباعة"><i class="ph-bold ph-printer"></i></a>
                            <a href="/ERP/treasury/receipts/<?= $r->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/treasury/receipts/<?= $r->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&payment_method=<?= urlencode($methodFilter) ?>&start_date=<?= urlencode($fromDate) ?>&end_date=<?= urlencode($toDate) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>