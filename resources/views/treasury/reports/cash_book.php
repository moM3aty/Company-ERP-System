<?php
// Path: resources/views/treasury/reports/cash_book.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isRtl ? 'rtl' : 'ltr';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$treasuryAccounts = $treasuryAccounts ?? [];
$movements        = $movements ?? [];
$selectedAccount  = $selectedAccount ?? null;
$openingBalance   = (float)($openingBalance ?? 0.00);
$totalIn          = (float)($totalIn ?? 0.00);
$totalOut         = (float)($totalOut ?? 0.00);
$closingBalance   = (float)($closingBalance ?? 0.00);
$totalPages       = (int)($totalPages ?? 1);
$currentPage      = (int)($currentPage ?? 1);
$startDate        = $startDate ?? date('Y-m-01');
$endDate          = $endDate ?? date('Y-m-t');
$search           = $search ?? '';
$accountId        = $accountId ?? null;

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title' => 'دفتر الصندوق والخزينة (Cash Book Report)',
        'desc' => 'كشف وحركة السيولة والوارد والصادر بالرصيد الافتتاحي والتراكمي.',
        'brand_sub' => 'إدارة الخزانة والمالية',
        'print' => 'طباعة كشف الصندوق', 'active_scope' => 'الفرع النشط:',
        'no_accs' => '-- لا توجد خزائن أو حسابات مسجلة --',
        'from_date' => 'من تاريخ', 'to_date' => 'إلى تاريخ',
        'search_ph' => 'ابحث بالمرجع أو البيان...', 'btn_view' => 'عرض التقرير',
        'kpi_open' => 'الرصيد الافتتاحي', 'kpi_in' => 'إجمالي المقبوضات (وارد)',
        'kpi_out' => 'إجمالي المدفوعات (صادر)', 'kpi_close' => 'الرصيد النهائي الحالي',
        'col_date' => 'التاريخ', 'col_ref' => 'المرجع / الرقم', 'col_type' => 'نوع الحركة',
        'col_party' => 'الجهة / التفاصيل', 'col_desc' => 'البيان',
        'col_in' => 'وارد (+)', 'col_out' => 'صادر (-)',
        'empty' => 'لا توجد حركات نقدية مسجلة بالنطاق المحدد.',
        'type_receipt' => 'قبض', 'type_payment' => 'صرف',
        'type_trf_in' => 'تحويل وارد', 'type_trf_out' => 'تحويل صادر',
        'general' => 'عام', 'internal_trf' => 'تحويل داخلي',
        'sig_cashier' => 'أمين الصندوق / الخزينة',
        'sig_auditor' => 'المراجع المالي',
        'sig_manager' => 'المدير المالي / الاعتماد',
        'sig_sub' => 'الاسم والتوقيع والختم'
    ],
    'en' => [
        'title' => 'Cash Book Report',
        'desc' => 'Detailed cash inflow/outflow ledger with opening and closing balances.',
        'brand_sub' => 'Treasury & Finance Department',
        'print' => 'Print Cash Book', 'active_scope' => 'Active Branch:',
        'no_accs' => '-- No accounts available --',
        'from_date' => 'From Date', 'to_date' => 'To Date',
        'search_ph' => 'Search reference or description...', 'btn_view' => 'View Report',
        'kpi_open' => 'Opening Balance', 'kpi_in' => 'Total Inflow',
        'kpi_out' => 'Total Outflow', 'kpi_close' => 'Closing Balance',
        'col_date' => 'Date', 'col_ref' => 'Reference/No', 'col_type' => 'Tx Type',
        'col_party' => 'Party/Details', 'col_desc' => 'Description',
        'col_in' => 'In (+)', 'col_out' => 'Out (-)',
        'empty' => 'No cash movements found in the specified range.',
        'type_receipt' => 'Receipt', 'type_payment' => 'Payment',
        'type_trf_in' => 'Transfer In', 'type_trf_out' => 'Transfer Out',
        'general' => 'General', 'internal_trf' => 'Internal Transfer',
        'sig_cashier' => 'Cashier / Teller',
        'sig_auditor' => 'Auditor',
        'sig_manager' => 'Finance Manager',
        'sig_sub' => 'Name, Signature & Stamp'
    ]
][$isRtl ? 'ar' : 'en'];

function translateTxType($type, $t) {
    $map = [
        'قبض'         => $t['type_receipt'],
        'صرف'         => $t['type_payment'],
        'تحويل وارد'  => $t['type_trf_in'],
        'تحويل صادر'  => $t['type_trf_out'],
    ];
    return $map[$type] ?? $type;
}
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
    .cb-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .cb-title-box { display: flex; align-items: center; gap: 16px; }
    .cb-icon { width: 48px; height: 48px; background: var(--c-cb-light); color: var(--c-cb); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(29, 78, 216, 0.12); }
    .cb-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-print { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #1e293b; }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 24px; }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-cb-light); color: var(--c-cb); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; box-sizing: border-box; }
    .btn-search { background: var(--c-cb); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .cb-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .cb-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .cb-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-cb); color: #ffffff; border-color: var(--c-cb); }

    .badge-type { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-block; }

    /* Print Specific Elements */
    .print-only { display: none; }
    .signatures-grid { display: none; margin-top: 40px; justify-content: space-between; text-align: center; }
    .sig-box { flex: 1; padding: 0 10px; }
    .sig-box h6 { margin: 0 0 30px 0; font-size: 0.85rem; color: #000; font-weight: 800; }
    .sig-line { border-bottom: 1px dashed #000; width: 80%; margin: 0 auto; }

    /* إخفاء عناصر التحكم المحقونة تلقائياً بواسطة DataTables */
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate,
    .table-pagination-nav {
        display: none !important;
    }

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        
        body { background: #fff !important; color: #000 !important; }
        
        /* إخفاء القوائم وأدوات التحكم */
        .nt-sidebar, .top-header, .cb-header, .search-bar, .pagination, header, aside, .branch-scope-badge { 
            display: none !important; 
        }
        
        /* إظهار العناصر الخاصة بالطباعة */
        .print-only, .signatures-grid { display: flex !important; }
        
        /* إعادة ضبط المحتوى ليأخذ مساحة الشاشة بالكامل دون استخدام position: absolute المسببة للمشاكل */
        .cb-wrapper { max-width: 100% !important; margin: 0 !important; padding: 0 !important; background: #fff !important; }
        
        /* تنسيق كروت المؤشرات في الطباعة لتكون صف واحد مرتب */
        .kpi-row { display: flex !important; flex-wrap: nowrap !important; gap: 10px !important; margin-bottom: 20px !important; }
        .kpi-card { flex: 1 !important; border: 1px solid #000 !important; box-shadow: none !important; padding: 10px !important; break-inside: avoid; border-radius: 6px !important;}
        .kpi-icon { display: none !important; } /* إخفاء الأيقونة لتوفير المساحة */
        .kpi-info h4 { color: #000 !important; font-size: 0.8rem !important; margin-bottom: 4px !important;}
        .kpi-info p { color: #000 !important; font-size: 1.1rem !important; }

        /* تنسيق الجدول للطباعة */
        .table-card { border: none !important; box-shadow: none !important; overflow: visible !important; border-radius: 0 !important;}
        .cb-table { border-collapse: collapse !important; width: 100% !important; border: 2px solid #000 !important; }
        .cb-table thead { display: table-header-group !important; } /* لتكرار الهيدر في الصفحات الجديدة */
        .cb-table tfoot { display: table-row-group !important; }
        .cb-table tr { page-break-inside: avoid !important; }
        .cb-table th { background: #e2e8f0 !important; color: #000 !important; border: 1px solid #000 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; font-size: 0.85rem !important; padding: 8px !important; }
        .cb-table td { border: 1px solid #000 !important; color: #000 !important; padding: 6px 8px !important; font-size: 0.85rem !important; }
        .badge-type { border: 1px solid #000 !important; background: transparent !important; color: #000 !important; padding: 2px 6px !important; }
        
        /* إخفاء إضافي للـ DataTables */
        .dataTables_wrapper, .table-pagination-nav { display: none !important; visibility: hidden !important; }
        
        /* ضمان دقة الألوان */
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="cb-wrapper" dir="<?= $dir ?>">
    
    <!-- 📄 ترويسة الطباعة (تظهر فقط عند الطباعة) -->
    <div class="print-only" style="border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px; align-items: flex-start; justify-content: space-between;">
        <div style="display:flex; align-items:center; gap:15px;">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
            <?php endif; ?>
            <div>
                <h2 style="margin:0; font-size:1.4rem; font-weight:900; color:#000;"><?= htmlspecialchars($cName) ?></h2>
                <span style="font-size:0.9rem; font-weight:700; color:#000;"><?= $t['brand_sub'] ?></span>
            </div>
        </div>
        <div style="text-align:<?= $isRtl ? 'left' : 'right' ?>;">
            <h2 style="margin:0; font-size:1.4rem; font-weight:900; color:#000; text-transform:uppercase;"><?= $t['title'] ?></h2>
            <p style="margin:4px 0 0 0; font-size:0.95rem; font-weight:bold; color:#000;">
                <?= htmlspecialchars($selectedAccount->name_ar ?? ($isRtl ? 'كل الخزائن' : 'All Accounts')) ?> 
                <?= isset($selectedAccount->code) ? "({$selectedAccount->code})" : '' ?>
            </p>
            <p style="margin:4px 0 0 0; font-size:0.85rem; color:#000;">
                <?= $t['from_date'] ?>: <?= htmlspecialchars($startDate) ?> | <?= $t['to_date'] ?>: <?= htmlspecialchars($endDate) ?>
            </p>
        </div>
    </div>

    <!-- رأس الصفحة المعتاد (للشاشة) -->
    <div class="cb-header">
        <div class="cb-title-box">
            <div class="cb-icon"><i class="ph-duotone ph-book-open-text"></i></div>
            <div>
                <h2 class="cb-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
        <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-cb);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-cb); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <form action="/ERP/treasury/reports/cash-book" method="GET" class="search-bar">
        <select name="account_id" class="form-control" style="flex:2; min-width:220px;" required>
            <?php if (empty($treasuryAccounts)): ?>
                <option value=""><?= $t['no_accs'] ?></option>
            <?php else: foreach($treasuryAccounts as $acc): 
                $accName = $isRtl ? ($acc->name_ar ?? '') : ($acc->name_en ?: ($acc->name_ar ?? ''));
            ?>
                <option value="<?= $acc->id ?>" <?= ($selectedAccount && $selectedAccount->id == $acc->id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$acc->code) ?> - <?= htmlspecialchars((string)$accName) ?> 
                    <?= !empty($acc->branch_name) ? " [{$acc->branch_name}]" : '' ?>
                </option>
            <?php endforeach; endif; ?>
        </select>
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>" title="<?= $t['from_date'] ?>">
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>" title="<?= $t['to_date'] ?>">
        <input type="text" name="search" class="form-control" style="flex:1; min-width:180px;" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_view'] ?></button>
    </form>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="ph-duotone ph-vault"></i></div>
            <div class="kpi-info">
                <h4><?= $t['kpi_open'] ?></h4>
                <p style="color:var(--c-text-dark);"><?= number_format($convert($openingBalance), 2) ?> <span style="font-size:0.75rem; color:#64748b;" class="no-print"><?= $currency ?></span></p>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-arrow-down-left"></i></div>
            <div class="kpi-info">
                <h4 style="color:#059669;"><?= $t['kpi_in'] ?></h4>
                <p style="color:#059669;"><?= number_format($convert($totalIn), 2) ?> <span style="font-size:0.75rem; color:#64748b;" class="no-print"><?= $currency ?></span></p>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-arrow-up-right"></i></div>
            <div class="kpi-info">
                <h4 style="color:#dc2626;"><?= $t['kpi_out'] ?></h4>
                <p style="color:#dc2626;"><?= number_format($convert($totalOut), 2) ?> <span style="font-size:0.75rem; color:#64748b;" class="no-print"><?= $currency ?></span></p>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#eff6ff; color:#1d4ed8;"><i class="ph-duotone ph-scales"></i></div>
            <div class="kpi-info">
                <h4 style="color:#1d4ed8;"><?= $t['kpi_close'] ?></h4>
                <p style="color:#1d4ed8;"><?= number_format($convert($closingBalance), 2) ?> <span style="font-size:0.75rem; color:#64748b;" class="no-print"><?= $currency ?></span></p>
            </div>
        </div>
    </div>

    <div class="table-card">
        <table class="cb-table">
            <thead>
                <tr>
                    <th style="width: 12%;"><?= $t['col_date'] ?></th>
                    <th style="width: 14%;"><?= $t['col_ref'] ?></th>
                    <th style="width: 12%;"><?= $t['col_type'] ?></th>
                    <th style="width: 20%;"><?= $t['col_party'] ?></th>
                    <th style="width: 22%;"><?= $t['col_desc'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_in'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_out'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($movements as $m): 
                    $inAmt = (float)($m->in_amount ?? 0);
                    $outAmt = (float)($m->out_amount ?? 0);
                    $isIn = $inAmt > 0;
                    $partyNameStr = $m->party === 'عام' ? $t['general'] : ($m->party === 'تحويل داخلي' ? $t['internal_trf'] : $m->party);
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 700;"><?= htmlspecialchars((string)($m->tx_date ?? '---')) ?></td>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-cb-dark);"><?= htmlspecialchars((string)($m->ref_no ?? '---')) ?></td>
                        <td>
                            <span class="badge-type" style="<?= $isIn ? 'background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;' : 'background:#fef2f2; color:#dc2626; border:1px solid #fecaca;' ?>">
                                <?= translateTxType($m->tx_type ?? '', $t) ?>
                            </span>
                        </td>
                        <td style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars((string)$partyNameStr) ?></td>
                        <td style="font-size: 0.82rem; color: var(--c-text-muted);"><?= htmlspecialchars((string)($m->description ?: '---')) ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 900; color: #059669; font-size: 0.95rem;">
                            <?= $isIn ? number_format($convert($inAmt), 2) : '-' ?>
                        </td>
                        <td style="text-align: center; font-family: monospace; font-weight: 900; color: #dc2626; font-size: 0.95rem;">
                            <?= !$isIn ? number_format($convert($outAmt), 2) : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- التوقيعات الخاصة بالطباعة الرسمية -->
    <div class="signatures-grid">
        <div class="sig-box">
            <h6><?= $t['sig_cashier'] ?></h6>
            <div style="font-size:0.75rem; color:#64748b; margin-bottom:30px;"><?= $t['sig_sub'] ?></div>
            <div class="sig-line"></div>
        </div>
        <div class="sig-box">
            <h6><?= $t['sig_auditor'] ?></h6>
            <div style="font-size:0.75rem; color:#64748b; margin-bottom:30px;"><?= $t['sig_sub'] ?></div>
            <div class="sig-line"></div>
        </div>
        <div class="sig-box">
            <h6><?= $t['sig_manager'] ?></h6>
            <div style="font-size:0.75rem; color:#64748b; margin-bottom:30px;"><?= $t['sig_sub'] ?></div>
            <div class="sig-line"></div>
        </div>
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

<script>
function purgeControls() {
    const selectors = [
        '.table-pagination-nav', 
        '.dataTables_info', 
        '.dataTables_paginate', 
        '.pagination',
        '.dataTables_filter',
        '.dataTables_length'
    ];
    selectors.forEach(s => {
        document.querySelectorAll(s).forEach(el => el.remove());
    });
}

function safePrint() {
    purgeControls();
    window.print();
}

document.addEventListener("DOMContentLoaded", purgeControls);
window.addEventListener("beforeprint", purgeControls);
</script>