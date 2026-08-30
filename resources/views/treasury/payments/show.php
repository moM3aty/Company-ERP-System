<?php
// Path: resources/views/treasury/payments/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$methodsMap = [
    'cash' => __('نقداً (Cash)', 'Cash'),
    'bank_transfer' => __('تحويل بنكي صادر', 'Outgoing Bank Transfer'),
    'cheque' => __('شيك صادر', 'Issued Cheque'),
    'pos' => __('بطاقة أجهزة الصراف / مدى', 'POS / Cards'),
];
?>

<style>
    :root { --c-pay: #e11d48; --c-pay-dark: #be123c; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .payment-show-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-pay-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    /* Voucher Official Style */
    .voucher-card { background: #ffffff; border: 2px solid var(--c-pay); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; }
    .voucher-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px double var(--c-pay); padding-bottom: 20px; margin-bottom: 24px; }
    
    .voucher-title-badge { background: var(--c-pay); color: #fff; padding: 6px 20px; border-radius: 8px; font-size: 1.2rem; font-weight: 900; letter-spacing: 1px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 140px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    .amount-box { background: #fff1f2; border: 2px solid #e11d48; border-radius: 12px; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; margin: 24px 0; }
    .amount-val { font-size: 2rem; font-weight: 900; color: #be123c; font-family: monospace; }

    .signatures-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 48px; text-align: center; }
    .sig-box h6 { margin: 0 0 40px 0; font-size: 0.85rem; color: var(--c-text-muted); font-weight: 800; }
    .sig-line { border-bottom: 1px solid #94a3b8; width: 80%; margin: 0 auto; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .payment-show-wrapper { max-width: 100% !important; }
        .voucher-card { border: 2px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="payment-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/payments" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= __('سند صرف رقم:', 'Payment Voucher No:') ?> <?= htmlspecialchars($payment->voucher_number) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;"><?= __('تاريخ الإصدار:', 'Issue Date:') ?> <?= htmlspecialchars($payment->payment_date) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= __('طباعة السند الرسمية', 'Print Official Voucher') ?></button>
            <?php if (has_permission('treasury_payments_edit')): ?>
                <a href="/ERP/treasury/payments/<?= $payment->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="<?= __('تعديل السند', 'Edit Voucher') ?>"><i class="ph-bold ph-pencil"></i></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="voucher-card">
        <div class="voucher-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-buildings" style="font-size:3rem; color:var(--c-pay);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= __('إدارة الخزانة والمالية', 'Treasury & Finance Department') ?></span>
                </div>
            </div>
            <div style="text-align:<?= $isRtl ? 'left' : 'right' ?>;">
                <div class="voucher-title-badge"><?= __('سند صرف (PAYMENT VOUCHER)', 'PAYMENT VOUCHER') ?></div>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-pay-dark); font-size:1.1rem;"># <?= htmlspecialchars($payment->voucher_number) ?></div>
            </div>
        </div>

        <div class="amount-box">
            <div>
                <span style="font-size:0.8rem; font-weight:800; color:#9f1239; text-transform:uppercase;"><?= __('المبلغ المصروف / Amount', 'Payment Amount') ?></span>
                <div style="font-size:0.9rem; font-weight:700; color:#881337; margin-top:2px;"><?= __('فقط وقدره مفصلاً بالكامل', 'Only the amount detailed above') ?></div>
            </div>
            <div class="amount-val"><?= number_format((float)$payment->amount, 2) ?> <span style="font-size:1rem;"><?= __('SAR/EGP', 'CUR') ?></span></div>
        </div>

        <div style="margin-top:28px;">
            <div class="info-row">
                <span class="info-label"><?= __('اصرفوا إلى السيد/السادة:', 'Pay To:') ?></span>
                <span class="info-val"><?= htmlspecialchars($payment->payee_name ?: ($payment->supplier_name ?? __('عام', 'General'))) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= __('خصماً من حساب:', 'From Account/Safe:') ?></span>
                <span class="info-val"><?= htmlspecialchars($payment->account_name ?? __('الخزينة العامة', 'Main Safe')) ?> (<?= htmlspecialchars($payment->account_code ?? '---') ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= __('طريقة الصرف:', 'Payment Method:') ?></span>
                <span class="info-val"><?= $methodsMap[$payment->payment_method] ?? __('نقداً', 'Cash') ?> <?= $payment->reference_no ? "— (".__('مرجع/شيك:', 'Ref/Cheque:')." {$payment->reference_no})" : '' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= __('وذلك عن / البيان:', 'Description/Reason:') ?></span>
                <span class="info-val"><?= htmlspecialchars($payment->description ?: __('صرف مستحقات مالية وإصدار سند', 'Payment of financial dues and issuing a voucher')) ?></span>
            </div>
        </div>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6><?= __('المستلم / المستفيد', 'Received By') ?></h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= __('أمين الصندوق / الخزينة', 'Cashier / Teller') ?></h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= __('يعتمد / المدير المالي', 'Approved By (Finance Mgr)') ?></h6>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>
</div>