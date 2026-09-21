<?php
// Path: resources/views/treasury/accounts/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

if (!isset($account) || !$account) {
    header("Location: /ERP/treasury/accounts");
    exit;
}

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$accId        = (int)($account->id ?? 0);
$accCode      = htmlspecialchars((string)($account->code ?? '---'));
$accNameAr    = (string)($account->name_ar ?? '');
$accNameEn    = (string)($account->name_en ?? '');
$accName      = $isAr ? $accNameAr : ($accNameEn ?: $accNameAr);
$accBalance   = (float)($account->current_balance ?? 0);
$transactions = $transactions ?? [];

$isBank = (mb_strpos($accNameAr, 'بنك') !== false || mb_strpos($accCode, '1112') !== false);

$t = [
    'ar' => [
        'title' => 'كشف حساب خزينة / بنك',
        'brand_sub' => 'إدارة الخزانة والمالية (Treasury Department)',
        'print' => 'طباعة كشف الحساب',
        'edit' => 'تعديل الحساب',
        'code_label' => 'كود الحساب المالي',
        'name_label' => 'اسم الحساب / الخزينة',
        'bal_label' => 'الرصيد الحالي المتوفر',
        'acc_type' => 'نوع الحساب',
        'bank_acc' => 'حساب بنكي',
        'cash_safe' => 'خزينة نقدية',
        'card_title' => 'دفتر الحركات الأخيرة المؤثرة على الحساب',
        'col_je' => 'رقم القيد',
        'col_date' => 'التاريخ',
        'col_desc' => 'البيان والتفاصيل',
        'col_dr' => 'مدين (+)',
        'col_cr' => 'دائن (-)',
        'empty' => 'لا توجد حركات مالية مرحلة مسجلة على هذا الحساب حتى الآن.',
        'tot_dr' => 'إجمالي المدين (+):',
        'tot_cr' => 'إجمالي الدائن (-):',
        'sig_accountant' => 'المحاسب المختص',
        'sig_auditor' => 'المراجع المالي',
        'sig_manager' => 'المدير المالي / الاعتماد',
        'sig_sub' => 'الاسم والتوقيع'
    ],
    'en' => [
        'title' => 'Treasury / Bank Statement',
        'brand_sub' => 'Treasury & Finance Department',
        'print' => 'Print Statement',
        'edit' => 'Edit Account',
        'code_label' => 'Financial Account Code',
        'name_label' => 'Account / Safe Name',
        'bal_label' => 'Current Available Balance',
        'acc_type' => 'Account Type',
        'bank_acc' => 'Bank Account',
        'cash_safe' => 'Cash Safe',
        'card_title' => 'Recent Ledger Movements',
        'col_je' => 'Entry #',
        'col_date' => 'Date',
        'col_desc' => 'Description & Details',
        'col_dr' => 'Debit (+)',
        'col_cr' => 'Credit (-)',
        'empty' => 'No posted financial transactions recorded for this account.',
        'tot_dr' => 'Total Debit (+):',
        'tot_cr' => 'Total Credit (-):',
        'sig_accountant' => 'Accountant',
        'sig_auditor' => 'Financial Auditor',
        'sig_manager' => 'Finance Manager / Approval',
        'sig_sub' => 'Name & Signature'
    ]
][$isAr ? 'ar' : 'en'];
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

    .acc-show-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: #0f172a; color: #ffffff; transition: 0.2s; }
    .btn-act:hover { background: #1e293b; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-acc-light); color: var(--c-acc-dark); border-color: #a5f3fc; }

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); border-top: 8px solid var(--c-acc); }
    .c-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 24px; }

    .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 28px; }
    .info-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; }
    .info-card h5 { margin: 0 0 8px 0; color: var(--c-text-muted); font-size: 0.8rem; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 6px; }
    .info-card p { margin: 4px 0 0 0; color: var(--c-text-dark); font-weight: 800; font-size: 1.05rem; }

    .table-print { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .table-print th { background: #0f172a; color: #ffffff; padding: 12px 16px; font-size: 0.85rem; text-align: start; border: 1px solid #0f172a; }
    .table-print td { padding: 12px 16px; border: 1px solid #cbd5e1; font-size: 0.95rem; color: #1e293b; font-weight: 600; vertical-align: middle; }

    .signatures-grid { margin-top: 50px; display: flex; justify-content: space-between; text-align: center; font-weight: 800; font-size: 0.9rem; color: #0f172a; }
    .sig-box { flex: 1; padding: 0 10px; }
    .sig-line { border-top: 1px dashed #cbd5e1; width: 80%; margin: 40px auto 0 auto; }

    /* إخفاء إجباري لأدوات البحث والترقيم التلقائية المحقونة عبر DataTables */
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate,
    .table-pagination-nav, .pagination {
        display: none !important;
    }

    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        body * { visibility: hidden !important; }
        .print-canvas, .print-canvas * { visibility: visible !important; }
        .print-canvas { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; border: none !important; box-shadow: none !important; padding: 0 !important; }
        
        .info-card { border: 1px solid #000 !important; background: transparent !important; }
        .table-print th { background: #e2e8f0 !important; color: #000 !important; border-color: #000 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .table-print td { border-color: #000 !important; }
        
        .dataTables_wrapper, .table-pagination-nav { display: none !important; visibility: hidden !important; }
    }
</style>

<div class="acc-show-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <a href="/ERP/treasury/accounts" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <div style="display:flex; gap:10px;">
            <a href="/ERP/treasury/accounts/<?= $accId ?>/edit" class="btn-act" style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1;"><i class="ph-bold ph-pencil-simple"></i> <?= $t['edit'] ?></a>
            <button onclick="safePrint()" class="btn-act"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="print-canvas">
        <div class="c-header">
            <div>
                <h1 style="margin:0; font-size:1.8rem; font-weight:900; color:#0f172a; display:flex; align-items:center; gap:10px;">
                    <i class="ph-fill <?= $isBank ? 'ph-bank' : 'ph-wallet' ?>" style="color:var(--c-acc);"></i>
                    <?= htmlspecialchars($accName) ?>
                </h1>
                <p style="margin:4px 0 0 0; color:#64748b; font-weight:700;"><?= $t['brand_sub'] ?></p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <div style="font-family: monospace; font-size: 1.8rem; font-weight: 900; color: var(--c-acc);"><i class="ph-bold ph-hash"></i> <?= $accCode ?></div>
                <div style="margin-top:6px; font-weight:800; color: #059669; text-transform: uppercase;">
                    <?= $isBank ? $t['bank_acc'] : $t['cash_safe'] ?>
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <h5><?= $t['code_label'] ?></h5>
                <p style="font-family:monospace; color:var(--c-acc-dark);"><?= $accCode ?></p>
            </div>
            <div class="info-card">
                <h5><?= $t['name_label'] ?></h5>
                <p><?= htmlspecialchars($accNameAr) ?> <?= !empty($accNameEn) ? "({$accNameEn})" : '' ?></p>
            </div>
            <div class="info-card" style="background:#f0fdf4; border-color:#a7f3d0;">
                <h5 style="color:#047857;"><?= $t['bal_label'] ?></h5>
                <p style="font-family:monospace; color:#059669; font-size:1.3rem;">
                    <?= number_format($convert($accBalance), 2) ?> <span style="font-size:0.85rem; color:#64748b;"><?= $currency ?></span>
                </p>
            </div>
        </div>

        <div style="margin-bottom: 12px; font-weight: 800; color: var(--c-text-dark); font-size: 1rem; display: flex; align-items: center; gap: 8px;">
            <i class="ph-bold ph-list-dashes" style="color:var(--c-acc);"></i> <?= $t['card_title'] ?>
        </div>

        <table class="table-print">
            <thead>
                <tr>
                    <th style="width: 15%;"><?= $t['col_je'] ?></th>
                    <th style="width: 15%;"><?= $t['col_date'] ?></th>
                    <th style="width: 44%;"><?= $t['col_desc'] ?></th>
                    <th style="width: 13%; text-align: center;"><?= $t['col_dr'] ?></th>
                    <th style="width: 13%; text-align: center;"><?= $t['col_cr'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sumDr = 0; 
                $sumCr = 0; 
                if(!empty($transactions)): 
                    foreach($transactions as $tx): 
                        $dr = (float)($tx->debit ?? 0);
                        $cr = (float)($tx->credit ?? 0);
                        $sumDr += $dr;
                        $sumCr += $cr;
                ?>
                    <tr>
                        <td style="font-family:monospace; font-weight:900;">
                            <a href="/ERP/accounting/journal-entries/<?= $tx->journal_entry_id ?>" style="color:var(--c-acc-dark); text-decoration:none;">
                                #<?= htmlspecialchars((string)($tx->entry_number ?? '---')) ?>
                            </a>
                        </td>
                        <td style="font-family:monospace; font-size:0.85rem;"><?= htmlspecialchars((string)($tx->entry_date ?? '---')) ?></td>
                        <td><?= htmlspecialchars((string)($tx->entry_desc ?: 'حركة مالية')) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;">
                            <?= $dr > 0 ? number_format($convert($dr), 2) : '-' ?>
                        </td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;">
                            <?= $cr > 0 ? number_format($convert($cr), 2) : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:30px; color:#94a3b8; font-weight:700;"><?= $t['empty'] ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <?php if(!empty($transactions)): ?>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: end; font-weight: 900; background: #f1f5f9;">مجموع حركات الكشف:</td>
                    <td style="text-align: center; font-weight: 900; font-family: monospace; font-size: 1.05rem; color: #059669; background: #f1f5f9;"><?= number_format($convert($sumDr), 2) ?></td>
                    <td style="text-align: center; font-weight: 900; font-family: monospace; font-size: 1.05rem; color: #dc2626; background: #f1f5f9;"><?= number_format($convert($sumCr), 2) ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>

        <div class="signatures-grid">
            <div class="sig-box">
                <div><?= $t['sig_accountant'] ?></div>
                <div style="color: #64748b; font-size: 0.75rem; margin-top: 4px;"><?= $t['sig_sub'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $t['sig_auditor'] ?></div>
                <div style="color: #64748b; font-size: 0.75rem; margin-top: 4px;"><?= $t['sig_sub'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $t['sig_manager'] ?></div>
                <div style="color: #64748b; font-size: 0.75rem; margin-top: 4px;"><?= $t['sig_sub'] ?></div>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>
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