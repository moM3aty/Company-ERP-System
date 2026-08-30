<?php
// Path: resources/views/purchasing/suppliers/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

$supName = $isAr ? ($supplier->name_ar ?: $supplier->name_en) : ($supplier->name_en ?: $supplier->name_ar);
$initial = mb_substr($supName, 0, 1, 'UTF-8');
?>

<style>
    .sup-show-wrapper { max-width: 1000px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    /* Screen Header Profile */
    .profile-header { background: #ffffff; border-radius: 24px; padding: 32px; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; border: 1px solid #f1f5f9; flex-wrap: wrap; gap: 24px; }
    .profile-info { display: flex; align-items: center; gap: 24px; }
    .avatar { width: 85px; height: 85px; border-radius: 24px; background: linear-gradient(135deg, #db2777, #be185d); color: white; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800; box-shadow: 0 10px 20px -5px rgba(219, 39, 119, 0.4); text-transform: uppercase; }
    .sup-name { margin: 0 0 10px 0; font-size: 1.8rem; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; }
    .sup-code { background: #fdf2f8; color: #db2777; padding: 6px 16px; border-radius: 99px; font-weight: 800; font-family: monospace; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #fbcfe8; }

    /* Action Buttons */
    .actions-group { display: flex; gap: 12px; }
    .btn { padding: 12px 24px; border-radius: 12px; font-weight: 800; font-size: 0.95rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; border: none; cursor: pointer; }
    .btn-light { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .btn-light:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; }
    .btn-primary { background: linear-gradient(135deg, #db2777, #be185d); color: white; box-shadow: 0 6px 16px rgba(219, 39, 119, 0.3); }

    /* Stats Grid */
    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
    @media(max-width: 900px) { .stats-grid { grid-template-columns: 1fr; } }
    .stat-card { background: #ffffff; border-radius: 18px; padding: 24px; border: 1px solid #f1f5f9; display: flex; align-items: center; gap: 16px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
    .stat-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; flex-shrink: 0; }
    .icon-pink { background: #fdf2f8; color: #db2777; }
    .stat-details h5 { margin: 0 0 6px 0; color: #64748b; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; }
    .stat-details .value { margin: 0; color: #0f172a; font-size: 1.6rem; font-weight: 900; font-family: monospace; }

    /* Details Grid */
    .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    @media(max-width: 768px) { .details-grid { grid-template-columns: 1fr; } }
    .details-card { background: #ffffff; border-radius: 20px; padding: 32px; border: 1px solid #f1f5f9; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
    .details-card h4 { margin: 0 0 24px 0; color: #0f172a; font-size: 1.2rem; font-weight: 800; display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #f8fafc; padding-bottom: 16px; }

    .info-list { list-style: none; padding: 0; margin: 0; }
    .info-list li { display: flex; justify-content: space-between; align-items: flex-start; padding: 14px 0; border-bottom: 1px dashed #e2e8f0; }
    .info-list li:last-child { border-bottom: none; padding-bottom: 0; }
    .info-label { color: #64748b; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; }
    .info-value { color: #1e293b; font-weight: 800; font-size: 0.95rem; text-align: end; }

    /* Hidden Sections for Screen */
    .print-official-header, .print-signatures { display: none; }

    /* ========================================= */
    /* Master A4 Print CSS (إعدادات الطباعة الرسمية) */
    /* ========================================= */
    @media print {
        @page { size: A4 portrait; margin: 12mm 15mm; }
        
        body { background: #ffffff !important; color: #000000 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; font-size: 9.5pt !important; }
        
        .nt-sidebar, header, nav, footer, .actions-group, .no-print, .avatar, .stat-icon { display: none !important; }
        .sup-show-wrapper { max-width: 100% !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }

        /* Print Header Title */
        .print-official-header { display: flex !important; justify-content: space-between !important; align-items: flex-start !important; border-bottom: 2px solid #0f172a !important; padding-bottom: 12px !important; margin-bottom: 20px !important; }
        .brand-title { margin: 0; font-size: 1.4rem; font-weight: 900; color: #0f172a; }
        .brand-sub { margin: 2px 0 0 0; color: #475569; font-size: 0.8rem; font-weight: 700; }

        .profile-header { border: 1px solid #0f172a !important; box-shadow: none !important; border-radius: 8px !important; margin-bottom: 16px !important; padding: 14px 18px !important; background: #ffffff !important; }
        .sup-name { font-size: 1.3rem !important; margin-bottom: 4px !important; color: #000000 !important; }
        .sup-code { border: 1px solid #0f172a !important; background: #ffffff !important; color: #000000 !important; font-size: 8.5pt !important; padding: 2px 8px !important; }

        /* Stats in Print */
        .stats-grid { display: table !important; width: 100% !important; table-layout: fixed !important; border-collapse: collapse !important; margin-bottom: 16px !important; page-break-inside: avoid !important; }
        .stat-card { display: table-cell !important; width: 33.33% !important; border: 1px solid #0f172a !important; padding: 10px !important; background: #ffffff !important; box-shadow: none !important; border-radius: 0 !important; vertical-align: middle !important; }
        .stat-details h5 { font-size: 7.5pt !important; color: #333333 !important; font-weight: 800 !important; }
        .stat-details .value { font-size: 11pt !important; font-weight: 900 !important; color: #000000 !important; }

        /* Details in Print */
        .details-grid { display: flex !important; justify-content: space-between !important; gap: 12px !important; page-break-inside: avoid !important; }
        .details-card { width: 48% !important; border: 1px solid #0f172a !important; border-radius: 8px !important; padding: 16px !important; box-shadow: none !important; background: #ffffff !important; }
        .details-card h4 { font-size: 9.5pt !important; margin-bottom: 12px !important; padding-bottom: 6px !important; border-bottom: 1px solid #0f172a !important; color: #000 !important; }
        .info-list li { padding: 8px 0 !important; border-bottom: 1px dashed #0f172a !important; font-size: 8.5pt !important; }
        .info-label { color: #333333 !important; }
        .info-value { color: #000000 !important; }

        /* Signatures */
        .print-signatures { display: flex !important; justify-content: space-between !important; margin-top: 40px !important; padding-top: 15px !important; border-top: 2px dashed #0f172a !important; page-break-inside: avoid !important; }
        .sig-box { text-align: center !important; flex: 1 !important; font-size: 8.5pt !important; font-weight: 800 !important; color: #000 !important; }
        .sig-line { border-top: 1px dashed #0f172a !important; width: 70% !important; margin: 30px auto 0 auto !important; }
    }
</style>

<div class="sup-show-wrapper" dir="<?= $dir ?>">

    <!-- Printable Official Header -->
    <div class="print-official-header">
        <div>
            <h1 class="brand-title">Nour Trust ERP</h1>
            <p class="brand-sub"><?= $isAr ? 'بطاقة البيانات الأساسية والمصادقة للمورد' : 'Supplier Audit Profile Sheet' ?></p>
        </div>
        <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
            <div style="font-weight: 800; font-size: 0.85rem; color: #0f172a;"><?= $isAr ? 'تاريخ التصدير:' : 'Print Date:' ?> <?= date('Y-m-d') ?></div>
            <div style="font-size: 0.75rem; color: #475569; font-weight: 700; margin-top: 2px;"><?= $isAr ? 'حالة التقرير: موثق' : 'Status: Verified' ?></div>
        </div>
    </div>
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-info">
            <div class="avatar"><?= htmlspecialchars($initial) ?></div>
            <div>
                <h1 class="sup-name"><?= htmlspecialchars($supName) ?></h1>
                <div class="sup-code"><i class="ph-bold ph-hash"></i> <?= htmlspecialchars($supplier->code) ?></div>
            </div>
        </div>
        <div class="actions-group">
            <button type="button" onclick="window.print()" class="btn btn-light"><i class="ph-bold ph-printer"></i> <?= $isAr ? 'طباعة الملف' : 'Print Profile' ?></button>
            <a href="/ERP/purchasing/suppliers/<?= $supplier->id ?>/edit" class="btn btn-primary"><i class="ph-bold ph-pencil-simple"></i> <?= $isAr ? 'تعديل' : 'Edit' ?></a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-pink"><i class="ph-duotone ph-shopping-bag"></i></div>
            <div class="stat-details">
                <h5><?= $isAr ? 'أوامر الشراء (POs)' : 'Purchase Orders' ?></h5>
                <p class="value"><?= number_format($stats->orders_count ?? 0) ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-pink"><i class="ph-duotone ph-shield-check"></i></div>
            <div class="stat-details">
                <h5><?= $isAr ? 'الحد الائتماني للمورد' : 'Credit Limit' ?></h5>
                <p class="value"><?= number_format($supplier->credit_limit, 2) ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-pink"><i class="ph-duotone ph-toggle-left"></i></div>
            <div class="stat-details">
                <h5><?= $isAr ? 'حالة الحساب' : 'Account Status' ?></h5>
                <p class="value" style="font-size: 1.2rem; color: <?= $supplier->is_active ? '#059669' : '#dc2626' ?>;">
                    <?= $supplier->is_active ? ($isAr ? 'نشط (Active)' : 'Active') : ($isAr ? 'موقوف (Inactive)' : 'Inactive') ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Detailed Info -->
    <div class="details-grid">
        <div class="details-card">
            <h4><i class="ph-duotone ph-identification-card text-pink-500" style="font-size: 1.5rem;"></i> <?= $isAr ? 'معلومات التواصل' : 'Contact Information' ?></h4>
            <ul class="info-list">
                <li>
                    <span class="info-label"><i class="ph-bold ph-phone text-slate-400"></i> <?= $isAr ? 'رقم الهاتف' : 'Phone' ?></span>
                    <span class="info-value" dir="ltr" style="font-family: monospace;"><?= htmlspecialchars($supplier->phone ?? '---') ?></span>
                </li>
                <li>
                    <span class="info-label"><i class="ph-bold ph-envelope text-slate-400"></i> <?= $isAr ? 'البريد الإلكتروني' : 'Email' ?></span>
                    <span class="info-value"><?= htmlspecialchars($supplier->email ?? '---') ?></span>
                </li>
                <li>
                    <span class="info-label"><i class="ph-bold ph-map-pin text-slate-400"></i> <?= $isAr ? 'العنوان' : 'Address' ?></span>
                    <span class="info-value"><?= htmlspecialchars($supplier->address ?? '---') ?></span>
                </li>
            </ul>
        </div>

        <div class="details-card">
            <h4><i class="ph-duotone ph-article text-pink-500" style="font-size: 1.5rem;"></i> <?= $isAr ? 'البيانات المالية والضريبية' : 'Financial & Tax Info' ?></h4>
            <ul class="info-list">
                <li>
                    <span class="info-label"><i class="ph-bold ph-file-text text-slate-400"></i> <?= $isAr ? 'الرقم الضريبي' : 'Tax Number' ?></span>
                    <span class="info-value" style="font-family: monospace; font-size: 1.05rem;"><?= htmlspecialchars($supplier->tax_number ?? '---') ?></span>
                </li>
                <li>
                    <span class="info-label"><i class="ph-bold ph-info text-slate-400"></i> <?= $isAr ? 'ملاحظات' : 'Notes' ?></span>
                    <span class="info-value"><?= htmlspecialchars($supplier->notes ?? '---') ?></span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Official Signatures Section (Visible on Print Only) -->
    <div class="print-signatures">
        <div class="sig-box">
            <div><?= $isAr ? 'إعداد مسؤول المشتريات' : 'Prepared By' ?></div>
            <div class="sig-line"></div>
        </div>
        <div class="sig-box">
            <div><?= $isAr ? 'اعتماد مدير المشتريات' : 'Purchasing Manager' ?></div>
            <div class="sig-line"></div>
        </div>
        <div class="sig-box">
            <div><?= $isAr ? 'توقيع وختم المورد' : 'Supplier Stamp' ?></div>
            <div class="sig-line"></div>
        </div>
    </div>

</div>