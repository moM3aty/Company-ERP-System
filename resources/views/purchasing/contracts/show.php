<?php
// Path: resources/views/purchasing/contracts/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';
$currency = current_currency();

$t = [
    'ar' => [
        'title' => 'تفاصيل عقد المشتريات',
        'edit' => 'تعديل العقد',
        'print' => 'طباعة الوثيقة',
        'brand_title' => 'وثيقة اتفاقية توريد رسمية (Purchase Contract)',
        'subject' => 'موضوع الاتفاقية / العنوان',
        'first_party' => 'الطرف الأول (الشركة)',
        'company_name' => 'مؤسسة نور الثقة للحلول الرقمية',
        'company_tax' => 'الرقم الضريبي: 300000000000003',
        'second_party' => 'الطرف الثاني (المورد)',
        'tax_no' => 'الرقم الضريبي:',
        'validity' => 'مدة سريان العقد',
        'start_date' => 'بداية:',
        'end_date' => 'نهاية:',
        'total_value' => 'القيمة الإجمالية التقديرية',
        'terms_title' => 'الشروط والأحكام المتفق عليها:',
        'default_terms' => 'تخضع هذه الاتفاقية للشروط والأحكام العامة للتوريد المعتمدة بين الطرفين.',
        'sig_party1' => 'الطرف الأول (مدير المشتريات)',
        'sig_party2' => 'الطرف الثاني (المورد / المفوض)'
    ],
    'en' => [
        'title' => 'Purchase Contract Details',
        'edit' => 'Edit Contract',
        'print' => 'Print Contract',
        'brand_title' => 'Official Purchase Agreement Document',
        'subject' => 'Agreement Subject / Title',
        'first_party' => 'First Party (Company)',
        'company_name' => 'Nour Trust IT & Web Solutions',
        'company_tax' => 'VAT No.: 300000000000003',
        'second_party' => 'Second Party (Supplier)',
        'tax_no' => 'VAT No.:',
        'validity' => 'Contract Validity Period',
        'start_date' => 'Start:',
        'end_date' => 'End:',
        'total_value' => 'Estimated Total Value',
        'terms_title' => 'Agreed Terms & Conditions:',
        'default_terms' => 'This agreement is subject to the general supply terms approved by both parties.',
        'sig_party1' => 'First Party (Purchasing Manager)',
        'sig_party2' => 'Second Party (Supplier / Authorized Representative)'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-primary: #7c3aed;
        --c-primary-light: #ede9fe;
    }

    .cnt-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-primary-light); color: var(--c-primary); border-color: #c4b5fd; }
    
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; }
    .btn-dark { background: #0f172a; color: #ffffff; }
    .btn-dark:hover { background: #1e293b; }

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); border-top: 6px solid var(--c-primary); }
    .c-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 24px; }
    .c-brand { font-size: 1.6rem; font-weight: 900; color: #0f172a; margin: 0; }
    
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 28px; border: 1px solid #f1f5f9; }
    .info-box h5 { margin: 0 0 6px 0; color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
    .info-box p { margin: 0; color: #0f172a; font-weight: 800; font-size: 1rem; }

    .terms-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; color: #334155; line-height: 1.8; font-size: 0.95rem; font-weight: 600; }
    
    .print-signatures { display: none; }

    /* ========================================= */
    /* إعدادات الطباعة الشاملة والحجب الإجباري */
    /* ========================================= */
    @media print {
        @page { size: A4 portrait; margin: 15mm; }
        
        /* 1. حجب جميع عناصر الصفحة خارج كارت العقد */
        body * {
            visibility: hidden !important;
        }
        
        /* 2. إظهار ورقة العقد فقط */
        .print-canvas, .print-canvas * {
            visibility: visible !important;
        }
        
        .print-canvas {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
        }

        /* 3. الإخفاء الجذري لشريط الترقيم المستهدف (table-pagination-nav) وعناصر DataTables */
        .table-pagination-nav,
        .table-pagination-nav *,
        .print-canvas .table-pagination-nav,
        .dataTables_info, .dataTables_paginate, .pagination {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            height: 0 !important;
            width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .info-grid { border: 1px solid #0f172a !important; background: transparent !important; page-break-inside: avoid !important; }
        .terms-box { border: 1px solid #0f172a !important; page-break-inside: auto !important; }
        
        .print-signatures { display: flex !important; justify-content: space-between !important; margin-top: 60px !important; page-break-inside: avoid !important; }
        .sig-box { text-align: center !important; flex: 1 !important; font-weight: 800 !important; font-size: 0.9rem !important; color: #000 !important; }
        .sig-line { border-top: 1px dashed #0f172a !important; width: 60% !important; margin: 35px auto 0 auto !important; }
    }
</style>

<div class="cnt-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="/ERP/purchasing/contracts" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <h3 style="margin:0; font-weight: 800; color: #0f172a;"><?= $t['title'] ?></h3>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/ERP/purchasing/contracts/<?= $contract->id ?>/edit" class="btn-act" style="background:var(--c-primary-light); color:var(--c-primary); border:1px solid #c4b5fd;"><i class="ph-bold ph-pencil-simple"></i> <?= $t['edit'] ?></a>
            <button onclick="safePrint()" class="btn-act btn-dark"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="print-canvas">
        <div class="c-header">
            <div>
                <h1 class="c-brand">Nour Trust ERP</h1>
                <p style="margin:4px 0 0 0; color:#64748b; font-weight:700;"><?= $t['brand_title'] ?></p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <div style="font-family: monospace; font-size: 1.5rem; font-weight: 900; color: var(--c-primary);"><?= htmlspecialchars($contract->contract_number) ?></div>
                <div style="margin-top:4px; font-weight:800; color:#059669; text-transform: uppercase;"><?= strtoupper($contract->status) ?></div>
            </div>
        </div>

        <div style="margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px dashed #f1f5f9;">
            <h5 style="margin:0 0 6px 0; color:var(--c-primary); font-size:0.85rem; font-weight:800; text-transform:uppercase;"><?= $t['subject'] ?></h5>
            <h3 style="margin:0; color:#0f172a; font-size:1.4rem; font-weight:900;"><?= htmlspecialchars($contract->title) ?></h3>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5><?= $t['first_party'] ?></h5>
                <p><?= $t['company_name'] ?></p>
                <div style="color:#64748b; font-size:0.85rem; font-weight:600; margin-top:4px;"><?= $t['company_tax'] ?></div>
            </div>
            <div class="info-box">
                <h5><?= $t['second_party'] ?></h5>
                <p><?= htmlspecialchars($contract->supplier_name ?? '---') ?></p>
                <div style="color:#64748b; font-size:0.85rem; font-weight:600; margin-top:4px; font-family:monospace;"><?= $t['tax_no'] ?> <?= htmlspecialchars($contract->supplier_tax ?? '---') ?></div>
            </div>
        </div>

        <div class="info-grid" style="background:#ffffff; border-color:#e2e8f0;">
            <div class="info-box">
                <h5><?= $t['validity'] ?></h5>
                <p style="color:#059669; font-size:0.95rem;"><?= $t['start_date'] ?> <?= $contract->start_date ?></p>
                <p style="color:#dc2626; font-size:0.95rem;"><?= $t['end_date'] ?> <?= $contract->end_date ?></p>
            </div>
            <div class="info-box">
                <h5><?= $t['total_value'] ?></h5>
                <p style="font-family: monospace; font-size:1.4rem; color:var(--c-primary);"><?= number_format($contract->total_value, 2) ?> <span style="font-size:0.9rem; color:#64748b;"><?= $currency ?></span></p>
            </div>
        </div>

        <h4 style="margin: 0 0 12px 0; color: #0f172a; font-weight: 900; font-size: 1.1rem;"><?= $t['terms_title'] ?></h4>
        <div class="terms-box">
            <?= !empty($contract->terms_conditions) ? nl2br(htmlspecialchars($contract->terms_conditions)) : $t['default_terms'] ?>
        </div>

        <div class="print-signatures">
            <div class="sig-box">
                <div><?= $t['sig_party1'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $t['sig_party2'] ?></div>
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