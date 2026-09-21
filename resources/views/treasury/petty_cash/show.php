<?php
// Path: resources/views/treasury/petty_cash/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

if (!isset($pettyCash) || !$pettyCash) {
    header("Location: /ERP/treasury/petty-cash");
    exit;
}

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$code          = htmlspecialchars((string)($pettyCash->code ?? '---'));
$employeeName  = htmlspecialchars((string)($pettyCash->employee_name ?? '---'));
$accountName   = htmlspecialchars((string)($pettyCash->account_name ?? ($isAr ? 'الخزينة العامة' : 'Main Safe')));
$accountCode   = htmlspecialchars((string)($pettyCash->account_code ?? '---'));
$issueDate     = htmlspecialchars((string)($pettyCash->issue_date ?? date('Y-m-d')));
$description   = htmlspecialchars((string)($pettyCash->description ?? ''));
$amount        = (float)($pettyCash->amount ?? 0);
$spentAmount   = (float)($pettyCash->spent_amount ?? 0);
$remainingAmt  = (float)($pettyCash->remaining_amount ?? 0);

$t = [
    'ar' => [
        'print' => 'طباعة إذن العُهدة',
        'title' => 'إذن تسليم عُهدة (PETTY CASH VOUCHER)',
        'sub' => 'إدارة الخزانة والمالية',
        'card_emp' => 'بطاقة عُهدة:',
        'tot_budget' => 'إجمالي ميزانية العُهدة',
        'tot_spent' => 'المنصرف والمصفى',
        'tot_remain' => 'المتبقي بالعهدة',
        'emp_label' => 'الموظف المسؤول عن العُهدة:',
        'acc_label' => 'الحساب المصدر:',
        'date_label' => 'تاريخ الاستلام:',
        'desc_label' => 'البيان والغرض:',
        'sig_emp' => 'المستلم / الموظف المسؤول',
        'sig_cashier' => 'أمين الخزينة المسلم',
        'sig_manager' => 'اعتماد الحسابات والمالية',
        'sig_sub' => 'الاسم والتوقيع والختم',
        'settle_title' => 'تسجيل تصفية جديدة وإثبات منصرفات من العُهدة',
        'settle_lbl' => 'مبلغ التصفية والمنصرف الجديد',
        'btn_settle' => 'خصم وتثبيت المنصرف'
    ],
    'en' => [
        'print' => 'Print Voucher',
        'title' => 'PETTY CASH VOUCHER',
        'sub' => 'Treasury & Finance Department',
        'card_emp' => 'Custody Card:',
        'tot_budget' => 'Total Budget',
        'tot_spent' => 'Settled & Spent',
        'tot_remain' => 'Remaining Balance',
        'emp_label' => 'Responsible Employee:',
        'acc_label' => 'Source Account:',
        'date_label' => 'Issue Date:',
        'desc_label' => 'Description/Purpose:',
        'sig_emp' => 'Received By',
        'sig_cashier' => 'Issued By (Cashier)',
        'sig_manager' => 'Approved By (Finance)',
        'sig_sub' => 'Name, Signature & Stamp',
        'settle_title' => 'Record New Settlement',
        'settle_lbl' => 'New Settlement Amount',
        'btn_settle' => 'Deduct & Confirm Settlement'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-pc: #d97706; 
        --c-pc-dark: #b45309; 
        --c-border: #cbd5e1; 
        --c-text-dark: #0f172a; 
        --c-text-muted: #64748b; 
    }
    .pc-show-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition: 0.2s; }
    .btn-action:hover { background: #fef3c7; color: var(--c-pc-dark); border-color: #fde68a; }
    .btn-print { background: var(--c-pc-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #78350f; }

    .voucher-card { background: #ffffff; border: 2px solid var(--c-pc); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }
    .voucher-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px double var(--c-pc); padding-bottom: 20px; margin-bottom: 24px; }
    
    .voucher-title-badge { background: var(--c-pc); color: #fff; padding: 6px 20px; border-radius: 8px; font-size: 1.1rem; font-weight: 900; letter-spacing: 1px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 150px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    .kpi-boxes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin: 24px 0; }
    .kpi-mini { background: #fef3c7; border: 1px solid #fde68a; padding: 14px; border-radius: 12px; text-align: center; }
    .kpi-mini h6 { margin: 0 0 4px 0; font-size: 0.75rem; color: #b45309; font-weight: 800; }
    .kpi-mini p { margin: 0; font-size: 1.4rem; font-weight: 900; font-family: monospace; }

    .settle-form-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

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
        .pc-show-wrapper, .pc-show-wrapper * { visibility: visible !important; }
        .pc-show-wrapper { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; }
        .header-bar, .settle-form-card { display: none !important; }
        .voucher-card { border: 2px solid #000 !important; box-shadow: none !important; padding: 20px !important; }
        .kpi-mini { border-color: #000 !important; background: transparent !important; }
        .voucher-title-badge { background: #000 !important; color: #fff !important; -webkit-print-color-adjust: exact !important; }
        .dataTables_wrapper, .table-pagination-nav { display: none !important; visibility: hidden !important; }
    }
</style>

<div class="pc-show-wrapper" dir="<?= $dir ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/petty-cash" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= $t['card_emp'] ?> <?= $employeeName ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-pc-dark); font-weight:800; font-family:monospace;"><?= $code ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="/ERP/treasury/petty-cash/<?= (int)$pettyCash->id ?>/edit" class="btn-action" style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1;"><i class="ph-bold ph-pencil-simple"></i></a>
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="print-canvas voucher-card">
        <div class="voucher-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-buildings" style="font-size:3rem; color:var(--c-pc);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= $t['sub'] ?></span>
                </div>
            </div>
            <div style="text-align:<?= $isAr ? 'left' : 'right' ?>;">
                <div class="voucher-title-badge"><?= $t['title'] ?></div>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-pc-dark); font-size:1.1rem;"># <?= $code ?></div>
            </div>
        </div>

        <div class="kpi-boxes">
            <div class="kpi-mini">
                <h6><?= $t['tot_budget'] ?></h6>
                <p style="color:var(--c-pc-dark);"><?= number_format($convert($amount), 2) ?> <span style="font-size:0.8rem; color:#64748b;"><?= $currency ?></span></p>
            </div>
            <div class="kpi-mini" style="background:#fef2f2; border-color:#fecdd3;">
                <h6 style="color:#dc2626;"><?= $t['tot_spent'] ?></h6>
                <p style="color:#dc2626;"><?= number_format($convert($spentAmount), 2) ?> <span style="font-size:0.8rem; color:#64748b;"><?= $currency ?></span></p>
            </div>
            <div class="kpi-mini" style="background:#f0fdf4; border-color:#a7f3d0;">
                <h6 style="color:#059669;"><?= $t['tot_remain'] ?></h6>
                <p style="color:#059669;"><?= number_format($convert($remainingAmt), 2) ?> <span style="font-size:0.8rem; color:#64748b;"><?= $currency ?></span></p>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label"><?= $t['emp_label'] ?></span>
                <span class="info-val"><?= $employeeName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['acc_label'] ?></span>
                <span class="info-val"><?= $accountName ?> (<?= $accountCode ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['date_label'] ?></span>
                <span class="info-val" style="font-family:monospace;"><?= $issueDate ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['desc_label'] ?></span>
                <span class="info-val"><?= !empty($description) ? $description : '---' ?></span>
            </div>
        </div>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6><?= $t['sig_emp'] ?></h6>
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

    <?php if($remainingAmt > 0): ?>
        <div class="settle-form-card">
            <h3 style="margin:0 0 16px 0; font-size:1.1rem; font-weight:800; color:var(--c-text-dark); display:flex; align-items:center; gap:8px;">
                <i class="ph-bold ph-receipt" style="color:var(--c-pc);"></i> <?= $t['settle_title'] ?>
            </h3>
            <form action="/ERP/treasury/petty-cash/<?= (int)$pettyCash->id ?>/settle" method="POST" style="display:flex; gap:16px; align-items:flex-end; flex-wrap: wrap;">
                <div style="flex:1; min-width:200px;">
                    <label style="display:block; font-size:0.85rem; font-weight:800; color:#475569; margin-bottom:6px;"><?= $t['settle_lbl'] ?></label>
                    <input type="number" step="0.01" max="<?= $remainingAmt ?>" name="settle_amount" class="form-control" style="width:100%; padding:10px 14px; border:1px solid #cbd5e1; border-radius:8px; font-family:monospace; font-weight:bold; font-size:1rem;" placeholder="0.00" required>
                </div>
                <button type="submit" style="background:var(--c-pc-dark); color:#fff; border:none; padding:12px 24px; border-radius:8px; font-weight:800; cursor:pointer;"><i class="ph-bold ph-check"></i> <?= $t['btn_settle'] ?></button>
            </form>
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