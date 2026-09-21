<?php
// Path: resources/views/treasury/transfers/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

if (!isset($transfer) || !$transfer) {
    header("Location: /ERP/treasury/transfers");
    exit;
}

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$transferNumber = htmlspecialchars((string)($transfer->transfer_number ?? '---'));
$transferDate   = htmlspecialchars((string)($transfer->transfer_date ?? date('Y-m-d')));
$fromAccount     = htmlspecialchars((string)($transfer->from_account_name ?? ($isAr ? 'غير محدد' : 'Unspecified')));
$fromCode        = htmlspecialchars((string)($transfer->from_account_code ?? '---'));
$toAccount       = htmlspecialchars((string)($transfer->to_account_name ?? ($isAr ? 'غير محدد' : 'Unspecified')));
$toCode          = htmlspecialchars((string)($transfer->to_account_code ?? '---'));
$referenceNo     = htmlspecialchars((string)($transfer->reference_no ?? ''));
$description     = htmlspecialchars((string)($transfer->description ?? ''));
$amount          = (float)($transfer->amount ?? 0);

$t = [
    'ar' => [
        'print' => 'طباعة إذن التحويل',
        'title' => 'إذن تحويل داخلي (INTERNAL TRANSFER)',
        'sub' => 'إدارة الخزانة والمالية',
        'amt_label' => 'المبلغ المحول / Transferred Amount',
        'amt_sub' => 'فقط وقدره مفصلاً بالكامل',
        'from_label' => 'خصماً من (المصدر):',
        'to_label' => 'إضافة إلى (الوجهة):',
        'ref_label' => 'الرقم المرجعي / الإيصال:',
        'desc_label' => 'البيان وسبب التحويل:',
        'sig_src' => 'أمناء الخزينة / الحساب المصدر',
        'sig_dst' => 'أمناء الخزينة / الحساب المستلم',
        'sig_manager' => 'اعتماد إدارة الخزانة',
        'sig_sub' => 'الاسم، التوقيع والختم'
    ],
    'en' => [
        'print' => 'Print Transfer Voucher',
        'title' => 'INTERNAL TRANSFER VOUCHER',
        'sub' => 'Treasury & Finance Department',
        'amt_label' => 'Transferred Amount',
        'amt_sub' => 'Only the amount detailed above',
        'from_label' => 'From (Source):',
        'to_label' => 'To (Destination):',
        'ref_label' => 'Reference No:',
        'desc_label' => 'Description/Reason:',
        'sig_src' => 'Source Cashier',
        'sig_dst' => 'Destination Cashier',
        'sig_manager' => 'Treasury Manager',
        'sig_sub' => 'Name, Signature & Stamp'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-trf: #4f46e5; 
        --c-trf-dark: #4338ca; 
        --c-border: #cbd5e1; 
        --c-text-dark: #0f172a; 
        --c-text-muted: #64748b; 
    }
    .transfer-show-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition: 0.2s; }
    .btn-action:hover { background: #e0e7ff; color: var(--c-trf-dark); border-color: #c7d2fe; }
    .btn-print { background: var(--c-trf-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #3730a3; }

    .voucher-card { background: #ffffff; border: 2px solid var(--c-trf); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; }
    .voucher-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px double var(--c-trf); padding-bottom: 20px; margin-bottom: 24px; }
    
    .voucher-title-badge { background: var(--c-trf); color: #fff; padding: 6px 20px; border-radius: 8px; font-size: 1.1rem; font-weight: 900; letter-spacing: 1px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 140px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    .amount-box { background: #e0e7ff; border: 2px solid #4f46e5; border-radius: 12px; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; margin: 24px 0; }
    .amount-val { font-size: 2rem; font-weight: 900; color: #3730a3; font-family: monospace; }

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
        .transfer-show-wrapper, .transfer-show-wrapper * { visibility: visible !important; }
        .transfer-show-wrapper { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; }
        .header-bar { display: none !important; }
        .voucher-card { border: 2px solid #000 !important; box-shadow: none !important; padding: 20px !important; }
        .amount-box { border-color: #000 !important; background: transparent !important; }
        .amount-val { color: #000 !important; }
        .voucher-title-badge { background: #000 !important; color: #fff !important; -webkit-print-color-adjust: exact !important; }
        .dataTables_wrapper, .table-pagination-nav { display: none !important; visibility: hidden !important; }
    }
</style>

<div class="transfer-show-wrapper" dir="<?= $dir ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/transfers" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= $transferNumber ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;"><?= $transferDate ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="/ERP/treasury/transfers/<?= (int)$transfer->id ?>/edit" class="btn-action" style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1;"><i class="ph-bold ph-pencil-simple"></i></a>
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="print-canvas voucher-card">
        <div class="voucher-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-buildings" style="font-size:3rem; color:var(--c-trf);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= $t['sub'] ?></span>
                </div>
            </div>
            <div style="text-align:<?= $isAr ? 'left' : 'right' ?>;">
                <div class="voucher-title-badge"><?= $t['title'] ?></div>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-trf-dark); font-size:1.1rem;"># <?= $transferNumber ?></div>
            </div>
        </div>

        <div class="amount-box">
            <div>
                <span style="font-size:0.8rem; font-weight:800; color:#3730a3; text-transform:uppercase;"><?= $t['amt_label'] ?></span>
                <div style="font-size:0.9rem; font-weight:700; color:#312e81; margin-top:2px;"><?= $t['amt_sub'] ?></div>
            </div>
            <div class="amount-val"><?= number_format($convert($amount), 2) ?> <span style="font-size:1rem; color:#64748b;"><?= $currency ?></span></div>
        </div>

        <div style="margin-top:28px;">
            <div class="info-row">
                <span class="info-label"><?= $t['from_label'] ?></span>
                <span class="info-val"><?= $fromAccount ?> (<?= $fromCode ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['to_label'] ?></span>
                <span class="info-val"><?= $toAccount ?> (<?= $toCode ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['ref_label'] ?></span>
                <span class="info-val"><?= !empty($referenceNo) ? $referenceNo : '---' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['desc_label'] ?></span>
                <span class="info-val"><?= !empty($description) ? $description : '---' ?></span>
            </div>
        </div>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6><?= $t['sig_src'] ?></h6>
                <div style="font-size:0.75rem; color:#64748b; margin-bottom:30px;"><?= $t['sig_sub'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= $t['sig_dst'] ?></h6>
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