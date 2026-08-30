<?php
// Path: resources/views/sales/contracts/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = isRtl();
$currency = current_currency();
$dir = $isAr ? 'rtl' : 'ltr';

$custName = $isAr ? ($contract->customer_name ?? 'عميل عام') : ($contract->customer_name ?? 'General Customer');
$branchName = $_SESSION['active_branch_name'] ?? ($isAr ? 'الفرع الرئيسي' : 'Main Branch');

$statusMap = [
    'draft'      => ['bg' => '#f1f5f9', 'text' => '#475569', 'border' => '#cbd5e1', 'label' => $isAr ? 'مسودة' : 'DRAFT'],
    'active'     => ['bg' => '#ecfdf5', 'text' => '#059669', 'border' => '#a7f3d0', 'label' => $isAr ? 'عقد ساري' : 'ACTIVE'],
    'expired'    => ['bg' => '#fef3c7', 'text' => '#d97706', 'border' => '#fde68a', 'label' => $isAr ? 'عقد منتهي' : 'EXPIRED'],
    'terminated' => ['bg' => '#fef2f2', 'text' => '#dc2626', 'border' => '#fecaca', 'label' => $isAr ? 'مفسوخ' : 'TERMINATED']
];

$st = strtolower($contract->status ?? 'draft');
$stUI = $statusMap[$st] ?? $statusMap['draft'];

$freqMap = [
    'one_time'      => $isAr ? 'دفعة واحدة' : 'One Time',
    'monthly'       => $isAr ? 'شهرياً' : 'Monthly',
    'quarterly'     => $isAr ? 'ربع سنوي' : 'Quarterly',
    'semi_annually' => $isAr ? 'نصف سنوي' : 'Semi-Annually',
    'annually'      => $isAr ? 'سنوياً' : 'Annually'
];
?>

<style>
    .cnt-show-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .cnt-top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .cnt-top-bar .title-group { display: flex; align-items: center; gap: 12px; }
    .back-btn { width: 38px; height: 38px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .back-btn:hover { background: #f8fafc; color: #0f172a; }
    
    .action-btns { display: flex; gap: 10px; }
    .btn-act { padding: 9px 18px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; font-family: inherit; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none; transition: 0.2s; }
    .btn-act-light { background: #ffffff; border: 1px solid #cbd5e1; color: #334155; }
    .btn-act-light:hover { background: #f8fafc; border-color: #94a3b8; }
    .btn-act-green { background: #16a34a; color: #ffffff; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25); }
    .btn-act-green:hover { background: #15803d; }

    .cnt-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); overflow: hidden; padding: 40px; }
    
    .canvas-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 28px; margin-bottom: 28px; }
    .brand-title { margin: 0; font-size: 1.6rem; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; }
    .brand-sub { margin: 4px 0 0 0; color: #64748b; font-size: 0.85rem; font-weight: 600; }
    
    .doc-meta { text-align: <?= $isAr ? 'left' : 'right' ?>; }
    .doc-type-tag { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #16a34a; letter-spacing: 1px; margin-bottom: 4px; display: block; }
    .doc-number { font-family: monospace; font-size: 1.6rem; font-weight: 900; color: #0f172a; margin: 0; }
    .status-badge { padding: 4px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 800; display: inline-block; margin-top: 8px; border: 1px solid <?= $stUI['border'] ?>; background: <?= $stUI['bg'] ?>; color: <?= $stUI['text'] ?>; }

    .contract-banner { background: #f8fafc; border-radius: 12px; padding: 20px 24px; border: 1px solid #e2e8f0; margin-bottom: 28px; }
    .contract-banner h3 { margin: 0 0 6px 0; color: #0f172a; font-size: 1.3rem; font-weight: 800; }

    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px; background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #f1f5f9; }
    .info-box h5 { margin: 0 0 10px 0; color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-box .name { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
    .info-box p { margin: 4px 0; color: #475569; font-size: 0.88rem; font-weight: 500; }

    .terms-box { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 32px; font-size: 0.95rem; color: #334155; line-height: 1.8; }
    .terms-box h4 { margin: 0 0 12px 0; font-size: 1rem; font-weight: 800; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; }

    .print-signatures { display: none; }

    @media print {
        @page { size: A4 portrait; margin: 12mm 15mm; }
        body { background: #fff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .nt-sidebar, header, nav, footer, .cnt-top-bar { display: none !important; }
        .cnt-show-wrapper { max-width: 100% !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .cnt-canvas { border: none !important; box-shadow: none !important; padding: 0 !important; }
        .print-signatures { display: flex !important; justify-content: space-between !important; margin-top: 60px !important; padding-top: 20px !important; border-top: 2px dashed #cbd5e1 !important; page-break-inside: avoid !important; }
        .sig-box { text-align: center !important; flex: 1 !important; }
        .sig-line { border-top: 1px dashed #0f172a !important; width: 70% !important; margin: 40px auto 0 auto !important; }
    }
</style>

<div class="cnt-show-wrapper" dir="<?= $dir ?>">
    
    <div class="cnt-top-bar">
        <div class="title-group">
            <a href="/ERP/sales/contracts" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h3 style="margin:0; font-size: 1.3rem; font-weight: 800; color: #0f172a;"><?= $isAr ? 'تفاصيل العقد والاتفاقية' : 'Contract Details' ?></h3>
            </div>
        </div>
        <div class="action-btns">
            <button type="button" onclick="window.print()" class="btn-act btn-act-light"><i class="ph-bold ph-printer"></i> <?= $isAr ? 'طباعة العقد الرسمـي' : 'Print Agreement' ?></button>
            <a href="/ERP/sales/contracts/<?= $contract->id ?>/edit" class="btn-act btn-act-green"><i class="ph-bold ph-pencil-simple"></i> <?= $isAr ? 'تعديل' : 'Edit' ?></a>
        </div>
    </div>

    <div class="cnt-canvas">
        
        <div class="canvas-header">
            <div>
                <h1 class="brand-title"><?= current_company_name() ?></h1>
                <p class="brand-sub"><?= $isAr ? 'عقد اتفاقية وشروط مبيعات رسمية' : 'Official Sales Agreement & Contract' ?></p>
            </div>
            <div class="doc-meta">
                <span class="doc-type-tag"><?= $isAr ? 'وثيقة عقد رسمية' : 'SALES CONTRACT' ?></span>
                <h2 class="doc-number"><?= htmlspecialchars($contract->contract_number) ?></h2>
                <span class="status-badge"><?= $stUI['label'] ?></span>
            </div>
        </div>

        <div class="contract-banner">
            <div style="font-size: 0.8rem; font-weight: 800; color: #16a34a; text-transform: uppercase; margin-bottom: 4px;"><?= $isAr ? 'موضوع العقد / العنوان' : 'Contract Subject' ?></div>
            <h3><?= htmlspecialchars($contract->title) ?></h3>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5><?= $isAr ? 'الطرف الأول (الشركة)' : 'First Party (Company)' ?></h5>
                <div class="name"><?= current_company_name() ?></div>
                <p><i class="ph-bold ph-map-pin"></i> <?= $isAr ? 'الفرع:' : 'Branch:' ?> <?= htmlspecialchars($branchName) ?></p>
                <p><i class="ph-bold ph-file-text"></i> <?= $isAr ? 'الرقم الضريبي:' : 'Tax No:' ?> 300000000000003</p>
            </div>
            <div class="info-box">
                <h5><?= $isAr ? 'الطرف الثاني (العميل)' : 'Second Party (Customer)' ?></h5>
                <div class="name"><?= htmlspecialchars($custName) ?></div>
                <p><i class="ph-bold ph-file-text"></i> <?= $isAr ? 'الرقم الضريبي:' : 'Tax No:' ?> <?= htmlspecialchars($contract->customer_tax ?? '---') ?></p>
                <p><i class="ph-bold ph-phone"></i> <span dir="ltr"><?= htmlspecialchars($contract->customer_phone ?? '---') ?></span></p>
                <p><i class="ph-bold ph-map-pin"></i> <?= htmlspecialchars($contract->customer_address ?? '---') ?></p>
            </div>
        </div>

        <div class="info-grid" style="background: #f8fafc; border: 1px solid #e2e8f0;">
            <div class="info-box">
                <h5><?= $isAr ? 'مدة السريان والمدة' : 'Validity & Duration' ?></h5>
                <p><i class="ph-bold ph-calendar"></i> <?= $isAr ? 'بداية العقد:' : 'Start Date:' ?> <strong><?= htmlspecialchars($contract->start_date) ?></strong></p>
                <p><i class="ph-bold ph-calendar-x"></i> <?= $isAr ? 'نهاية العقد:' : 'End Date:' ?> <strong><?= htmlspecialchars($contract->end_date) ?></strong></p>
            </div>
            <div class="info-box">
                <h5><?= $isAr ? 'المالية ودورية الفوترة' : 'Financials & Billing' ?></h5>
                <p><i class="ph-bold ph-coins"></i> <?= $isAr ? 'القيمة الإجمالية:' : 'Total Value:' ?> <strong style="font-family: monospace; font-size: 1.1rem; color: #16a34a;"><?= number_format($contract->total_value, 2) ?> <?= htmlspecialchars($currency) ?></strong></p>
                <p><i class="ph-bold ph-clock"></i> <?= $isAr ? 'دورية الفوترة:' : 'Billing Freq:' ?> <strong><?= $freqMap[$contract->billing_frequency] ?? $contract->billing_frequency ?></strong></p>
            </div>
        </div>

        <div class="terms-box">
            <h4><?= $isAr ? 'الشروط والأحكام والالتزامات المتبادلة:' : 'Terms & Conditions:' ?></h4>
            <?= !empty($contract->terms_conditions) ? nl2br(htmlspecialchars($contract->terms_conditions)) : ($isAr ? 'يسري هذا العقد فور توقيعه من الطرفين مع الالتزام بالتحصيلات المقررة في المواعيد المحددة.' : 'This agreement is binding upon both parties upon signature.') ?>
        </div>

        <div class="print-signatures">
            <div class="sig-box">
                <div style="font-weight: 800; color: #0f172a;"><?= $isAr ? 'توقيع الطرف الأول (الشركة)' : 'First Party Signature' ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div style="font-weight: 800; color: #0f172a;"><?= $isAr ? 'توقيع الطرف الثاني (العميل)' : 'Second Party Signature' ?></div>
                <div class="sig-line"></div>
            </div>
        </div>

    </div>
</div>