<?php
// Path: resources/views/treasury/payments/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

if (!isset($payment) || !$payment) {
    header("Location: /ERP/treasury/payments");
    exit;
}

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$voucherNumber = htmlspecialchars((string)($payment->voucher_number ?? '---'));
$paymentDate   = htmlspecialchars((string)($payment->payment_date ?? date('Y-m-d')));
$payeeName     = htmlspecialchars((string)($payment->payee_name ?: ($payment->supplier_name ?? ($isAr ? 'عام' : 'General'))));
$accountName   = htmlspecialchars((string)($payment->account_name ?? ($isAr ? 'الخزينة العامة' : 'Main Safe')));
$accountCode   = htmlspecialchars((string)($payment->account_code ?? '---'));
$referenceNo   = htmlspecialchars((string)($payment->reference_no ?? ''));
$description   = htmlspecialchars((string)($payment->description ?? ''));
$amount        = (float)($payment->amount ?? 0);
$methodKey     = (string)($payment->payment_method ?? 'cash');

$methodsMap = [
    'cash'          => $isAr ? 'نقداً (Cash)' : 'Cash',
    'bank_transfer' => $isAr ? 'تحويل بنكي صادر' : 'Outgoing Bank Transfer',
    'cheque'        => $isAr ? 'شيك صادر' : 'Issued Cheque',
    'pos'           => $isAr ? 'بطاقة أجهزة الصراف / مدى' : 'POS / Cards',
];

$t = [
    'ar' => [
        'print' => 'طباعة السند الرسمية',
        'title' => 'سند صرف (PAYMENT VOUCHER)',
        'sub' => 'إدارة الخزانة والمالية',
        'amt_label' => 'المبلغ المصروف / Amount',
        'amt_sub' => 'فقط وقدره مفصلاً بالكامل',
        'payee_label' => 'اصرفوا إلى السيد/السادة:',
        'acc_label' => 'خصماً من حساب:',
        'method_label' => 'طريقة الصرف:',
        'desc_label' => 'وذلك عن / البيان:',
        'ref_label' => 'مرجع/شيك:',
        'sig_payee' => 'المستلم / المستفيد',
        'sig_cashier' => 'أمين الصندوق / الخزينة',
        'sig_manager' => 'يعتمد / المدير المالي',
        'sig_sub' => 'الاسم، التوقيع والختم'
    ],
    'en' => [
        'print' => 'Print Official Voucher',
        'title' => 'PAYMENT VOUCHER',
        'sub' => 'Treasury & Finance Department',
        'amt_label' => 'Payment Amount',
        'amt_sub' => 'Only the amount detailed above',
        'payee_label' => 'Pay To:',
        'acc_label' => 'From Account/Safe:',
        'method_label' => 'Payment Method:',
        'desc_label' => 'Description/Reason:',
        'ref_label' => 'Ref/Cheque:',
        'sig_payee' => 'Received By',
        'sig_cashier' => 'Cashier / Teller',
        'sig_manager' => 'Approved By (Finance Mgr)',
        'sig_sub' => 'Name, Signature & Stamp'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-pay: #e11d48; 
        --c-pay-dark: #be123c; 
        --c-border: #cbd5e1; 
        --c-text-dark: #0f172a; 
        --c-text-muted: #64748b; 
    }
    .payment-show-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition: 0.2s; }
    .btn-action:hover { background: #fff1f2; color: var(--c-pay-dark); border-color: #fecdd3; }
    .btn-print { background: var(--c-pay-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #9f1239; }

    .voucher-card { background: #ffffff; border: 2px solid var(--c-pay); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; }
    .voucher-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px double var(--c-pay); padding-bottom: 20px; margin-bottom: 24px; }
    
    .voucher-title-badge { background: var(--c-pay); color: #fff; padding: 6px 20px; border-radius: 8px; font-size: 1.1rem; font-weight: 900; letter-spacing: 1px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 140px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    .amount-box { background: #fff1f2; border: 2px solid #e11d48; border-radius: 12px; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; margin: 24px 0; }
    .amount-val { font-size: 2rem; font-weight: 900; color: #be123c; font-family: monospace; }

    .signatures-grid { display: flex; justify-content: space-between; text-align: center; margin-top: 48px; }
    .sig-box { flex: 1; padding: 0 10px; }
    .sig-box h6 { margin: 0 0 30px 0; font-size: 0.85rem; color: var(--c-text-muted); font-weight: 800; }
    .sig-line { border-bottom: 1px dashed #cbd5e1; width: 80%; margin: 0 auto; }

    /* إخفاء عناصر التحكم المحقونة تلقائياً */
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
        .payment-show-wrapper, .payment-show-wrapper * { visibility: visible !important; }
        .payment-show-wrapper { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; }
        .header-bar { display: none !important; }
        .voucher-card { border: 2px solid #000 !important; box-shadow: none !important; padding: 20px !important; }
        .amount-box { border-color: #000 !important; background: transparent !important; }
        .amount-val { color: #000 !important; }
        .voucher-title-badge { background: #000 !important; color: #fff !important; -webkit-print-color-adjust: exact !important; }
        .dataTables_wrapper, .table-pagination-nav { display: none !important; visibility: hidden !important; }
    }
</style>

<div class="payment-show-wrapper" dir="<?= $dir ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/payments" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= $voucherNumber ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;"><?= $paymentDate ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="/ERP/treasury/payments/<?= (int)$payment->id ?>/edit" class="btn-action" style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1;"><i class="ph-bold ph-pencil-simple"></i></a>
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="print-canvas voucher-card">
        <div class="voucher-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-buildings" style="font-size:3rem; color:var(--c-pay);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= $t['sub'] ?></span>
                </div>
            </div>
            <div style="text-align:<?= $isAr ? 'left' : 'right' ?>;">
                <div class="voucher-title-badge"><?= $t['title'] ?></div>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-pay-dark); font-size:1.1rem;"># <?= $voucherNumber ?></div>
            </div>
        </div>

        <div class="amount-box">
            <div>
                <span style="font-size:0.8rem; font-weight:800; color:#9f1239; text-transform:uppercase;"><?= $t['amt_label'] ?></span>
                <div style="font-size:0.9rem; font-weight:700; color:#881337; margin-top:2px;"><?= $t['amt_sub'] ?></div>
            </div>
            <div class="amount-val"><?= number_format($convert($amount), 2) ?> <span style="font-size:1rem; color:#64748b;"><?= $currency ?></span></div>
        </div>

        <div style="margin-top:28px;">
            <div class="info-row">
                <span class="info-label"><?= $t['payee_label'] ?></span>
                <span class="info-val"><?= $payeeName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['acc_label'] ?></span>
                <span class="info-val"><?= $accountName ?> (<?= $accountCode ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['method_label'] ?></span>
                <span class="info-val">
                    <?= $methodsMap[$methodKey] ?? $methodsMap['cash'] ?> 
                    <?= !empty($referenceNo) ? " — ({$t['ref_label']} {$referenceNo})" : '' ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['desc_label'] ?></span>
                <span class="info-val"><?= !empty($description) ? $description : '---' ?></span>
            </div>
        </div>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6><?= $t['sig_payee'] ?></h6>
                <div style="font-size:0.75rem; color:#64748b; margin-bottom:30px;"><?= $t['sig_sub'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= $t['sig_cashier'] ?></h6>
                <div style="font-size:0.75rem; color:#64748b; margin-bottom:30px;"><?= $t['sig_sub'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= $t['sig_manager'] ?></h6>
                <div style="font-size:0.75rem; color:#64748b; margin-bottom:30px;"><?= $t['sig_sub'] ?></div>
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