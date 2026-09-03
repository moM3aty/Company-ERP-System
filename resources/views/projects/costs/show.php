<?php
// Path: resources/views/projects/costs/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';
$currency = current_currency();

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$categoryMap = [
    'materials' => $isAr ? 'مواد وتوريدات إنشائية (Materials)' : 'Materials & Supplies',
    'labor' => $isAr ? 'عمالة وأجور ومستحقات (Labor)' : 'Labor & Wages',
    'equipment' => $isAr ? 'إيجار وصيانة معدات (Equipment)' : 'Equipment & Machinery',
    'subcontractor' => $isAr ? 'مستحقات مقاولين فرعيين (Subcontractor)' : 'Subcontractor Dues',
    'overhead' => $isAr ? 'مصروفات إدارية وموقع (Overhead)' : 'Site Overhead',
    'other' => $isAr ? 'نفقات ومصروفات أخرى (Other)' : 'Other Expenses',
];

$statusMap = [
    'paid' => ['label' => $isAr ? 'مسدد بالكامل' : 'Paid', 'color' => '#059669', 'bg' => '#ecfdf5'],
    'partially_paid' => ['label' => $isAr ? 'مسدد جزئياً' : 'Partially Paid', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'unpaid' => ['label' => $isAr ? 'غير مسدد' : 'Unpaid', 'color' => '#dc2626', 'bg' => '#fef2f2'],
];
$st = $statusMap[$cost->payment_status] ?? $statusMap['paid'];

$t = [
    'ar' => [
        'print' => 'طباعة السند الرسمي', 'edit' => 'تعديل', 'back' => 'العودة',
        'subtitle' => 'إدارة تكاليف ومصروفات المواقع الإنشائية',
        'brand_title' => 'سند مصروفات موقع (SITE EXPENSE VOUCHER)',
        'voucher' => 'سند مصروف رقم:', 'date' => 'تاريخ الصرف:',
        'amt_label' => 'المبلغ المصروف / Amount', 'st_label' => 'حالة السداد:',
        'proj' => 'المشروع / الموقع:', 'cat' => 'تبويب التكلفة:', 'sup' => 'المورد / الجهة المستلمة:', 'ref' => 'الرقم المرجعي / الفاتورة:', 'desc' => 'البيان والتفاصيل:',
        'notes' => 'ملاحظات إضافية:',
        'sig_eng' => 'مهندس الموقع / المستلم', 'sig_acc' => 'المحاسب المسؤول', 'sig_mgr' => 'اعتماد مدير المشروع',
        'direct' => 'صندوق موقع مباشر', 'default_desc' => 'صرف واستحقاق نفقات موقع المشروع'
    ],
    'en' => [
        'print' => 'Print Voucher', 'edit' => 'Edit', 'back' => 'Back',
        'subtitle' => 'Construction Site Cost Management',
        'brand_title' => 'SITE EXPENSE VOUCHER',
        'voucher' => 'Voucher No.:', 'date' => 'Cost Date:',
        'amt_label' => 'Expense Amount', 'st_label' => 'Payment Status:',
        'proj' => 'Project / Site:', 'cat' => 'Cost Category:', 'sup' => 'Supplier / Payee:', 'ref' => 'Ref No. / Receipt:', 'desc' => 'Description / Details:',
        'notes' => 'Additional Notes:',
        'sig_eng' => 'Site Engineer / Receiver', 'sig_acc' => 'Accountant', 'sig_mgr' => 'Project Manager Approval',
        'direct' => 'Direct Petty Cash', 'default_desc' => 'Site expense allocation and payment'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-pcost: #ea580c; --c-pcost-dark: #c2410c; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .pcost-show-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition:0.2s;}
    .btn-action:hover { background: #ffedd5; color: var(--c-pcost); border-color: #fdba74; }
    .btn-print { background: var(--c-pcost-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25); transition:0.2s;}
    .btn-print:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(234, 88, 12, 0.35); }

    .voucher-card { background: #ffffff; border: 2px solid var(--c-pcost); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }
    .voucher-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px double var(--c-pcost); padding-bottom: 20px; margin-bottom: 24px; }
    
    .voucher-title-badge { background: var(--c-pcost); color: #fff; padding: 6px 20px; border-radius: 8px; font-size: 1.1rem; font-weight: 900; letter-spacing: 1px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 170px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    .amount-box { background: #ffedd5; border: 2px solid #ea580c; border-radius: 12px; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; margin: 24px 0; }
    .amount-val { font-size: 2rem; font-weight: 900; color: #c2410c; font-family: monospace; }

    .signatures-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 44px; text-align: center; }
    .sig-box h6 { margin: 0 0 40px 0; font-size: 0.85rem; color: var(--c-text-muted); font-weight: 800; }
    .sig-line { border-bottom: 1px solid #94a3b8; width: 80%; margin: 0 auto; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .pcost-show-wrapper { max-width: 100% !important; padding: 0 !important; }
        .voucher-card { border: 2px solid #000 !important; box-shadow: none !important; page-break-inside: avoid; }
        .amount-box { border: 2px solid #000 !important; background: transparent !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="pcost-show-wrapper" dir="<?= $dir ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/costs" class="btn-action" title="<?= $t['back'] ?>"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= $t['voucher'] ?> <?= htmlspecialchars($cost->voucher_number) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;"><?= $t['date'] ?> <?= htmlspecialchars($cost->cost_date) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
            <a href="/ERP/projects/costs/<?= $cost->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="<?= $t['edit'] ?>"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <div class="voucher-card">
        <div class="voucher-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-buildings" style="font-size:3rem; color:var(--c-pcost);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= $t['subtitle'] ?></span>
                </div>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <div class="voucher-title-badge"><?= $t['brand_title'] ?></div>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-pcost-dark); font-size:1.1rem;"># <?= htmlspecialchars($cost->voucher_number) ?></div>
            </div>
        </div>

        <div class="amount-box">
            <div>
                <span style="font-size:0.85rem; font-weight:800; color:#c2410c; text-transform:uppercase;"><?= $t['amt_label'] ?></span>
                <div style="font-size:0.9rem; font-weight:700; color:#9a3412; margin-top:2px;"><?= $t['st_label'] ?> <span style="color:<?= $st['color'] ?>; font-weight:900;"><?= $st['label'] ?></span></div>
            </div>
            <div class="amount-val"><?= number_format((float)$cost->amount, 2) ?> <span style="font-size:1rem; color:#c2410c;"><?= $currency ?></span></div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label"><?= $t['proj'] ?></span>
                <span class="info-val"><?= htmlspecialchars($cost->project_name ?? '---') ?> (<?= htmlspecialchars($cost->project_code ?? '') ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['cat'] ?></span>
                <span class="info-val"><?= $categoryMap[$cost->cost_category] ?? '---' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['sup'] ?></span>
                <span class="info-val"><?= htmlspecialchars($cost->supplier_name ?: ($cost->account_name ?? $t['direct'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['ref'] ?></span>
                <span class="info-val" style="font-family:monospace;"><?= htmlspecialchars($cost->reference_no ?: '---') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['desc'] ?></span>
                <span class="info-val"><?= htmlspecialchars($cost->description ?: $t['default_desc']) ?></span>
            </div>
        </div>

        <?php if(!empty($cost->notes)): ?>
            <div style="margin-top:20px; padding-top:16px; border-top:1px solid #f1f5f9;">
                <p style="margin:0; font-size:0.85rem; color:#64748b;"><b><?= $t['notes'] ?></b> <?= htmlspecialchars($cost->notes) ?></p>
            </div>
        <?php endif; ?>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6><?= $t['sig_eng'] ?></h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= $t['sig_acc'] ?></h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= $t['sig_mgr'] ?></h6>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>
</div>

<script>
function purgeControls() {
    const selectors = ['.table-pagination-nav', '.dataTables_info', '.dataTables_paginate', '.pagination'];
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