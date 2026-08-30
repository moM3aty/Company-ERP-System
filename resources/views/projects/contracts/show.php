<?php
// Path: resources/views/projects/contracts/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$statusMap = [
    'draft' => ['label' => 'مسودة', 'color' => '#64748b', 'bg' => '#f1f5f9'],
    'active' => ['label' => 'ساري وساري التنفيذ', 'color' => '#be123c', 'bg' => '#fff1f2'],
    'under_renewal' => ['label' => 'قيد التجديد', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'completed' => ['label' => 'مكتمل ومغلق بالكامل', 'color' => '#059669', 'bg' => '#ecfdf5'],
    'suspended' => ['label' => 'موقف مؤقتاً', 'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
    'terminated' => ['label' => 'مفسوخ / ملغى', 'color' => '#dc2626', 'bg' => '#fef2f2'],
];

$typeMap = [
    'owner_contract' => 'عقد المالك الرئيسي (Main Owner Agreement)',
    'subcontractor_contract' => 'عقد مقاول فرعي (Subcontractor Agreement)',
    'consultant_contract' => 'عقد الخدمات الاستشارية (Consultant Agreement)',
    'supply_contract' => 'عقد توريدات مواد (Supply Agreement)',
];

$st = $statusMap[$contract->status] ?? $statusMap['active'];
?>

<style>
    :root { --c-pcontract: #be123c; --c-pcontract-dark: #9f1239; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .pcontract-show-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-pcontract-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .voucher-card { background: #ffffff; border: 2px solid var(--c-pcontract); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }
    .voucher-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px double var(--c-pcontract); padding-bottom: 20px; margin-bottom: 24px; }
    
    .voucher-title-badge { background: var(--c-pcontract); color: #fff; padding: 6px 20px; border-radius: 8px; font-size: 1.1rem; font-weight: 900; letter-spacing: 1px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 170px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin: 24px 0; }
    @media(max-width:768px){ .grid-4 { grid-template-columns: repeat(2, 1fr); } }

    .card-kpi { background: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 16px; text-align: center; }
    .card-kpi h6 { margin: 0 0 4px 0; font-size: 0.75rem; color: #9f1239; font-weight: 800; }
    .card-kpi p { margin: 0; font-size: 1.3rem; font-weight: 900; font-family: monospace; color: var(--c-pcontract-dark); }

    .signatures-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 48px; text-align: center; }
    .sig-box h6 { margin: 0 0 40px 0; font-size: 0.85rem; color: var(--c-text-muted); font-weight: 800; }
    .sig-line { border-bottom: 1px solid #94a3b8; width: 80%; margin: 0 auto; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .pcontract-show-wrapper { max-width: 100% !important; }
        .voucher-card { border: 2px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="pcontract-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/contracts" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($contract->title_ar) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-pcontract-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($contract->contract_number) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة وثيقة العقد</button>
            <a href="/ERP/projects/contracts/<?= $contract->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="تعديل العقد"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <div class="voucher-card">
        <div class="voucher-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-buildings" style="font-size:3rem; color:var(--c-pcontract);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;">إدارة العقود والمتابعة القانونية</span>
                </div>
            </div>
            <div style="text-align:end;">
                <div class="voucher-title-badge">وثيقة عقد رسمي (CONTRACT AGREEMENT)</div>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-pcontract-dark); font-size:1.1rem;"># <?= htmlspecialchars($contract->contract_number) ?></div>
            </div>
        </div>

        <div class="grid-4">
            <div class="card-kpi">
                <h6>القيمة الكلية للعقد</h6>
                <p><?= number_format((float)$contract->contract_value, 2) ?></p>
            </div>
            <div class="card-kpi" style="background:#f0fdf4; border-color:#a7f3d0;">
                <h6 style="color:#047857;">الدفعة المقدمة</h6>
                <p style="color:#059669;"><?= number_format((float)$contract->advance_payment_amount, 2) ?></p>
            </div>
            <div class="card-kpi" style="background:#fef3c7; border-color:#fde68a;">
                <h6 style="color:#b45309;">نسبة استقطاع الضمان</h6>
                <p style="color:#d97706;"><?= number_format((float)$contract->retention_percent, 1) ?>%</p>
            </div>
            <div class="card-kpi" style="background:#f1f5f9; border-color:#cbd5e1;">
                <h6 style="color:#475569;">حالة العقد</h6>
                <p style="font-size:1rem; color:<?= $st['color'] ?>;"><?= $st['label'] ?></p>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label">المشروع التابع له:</span>
                <span class="info-val"><?= htmlspecialchars($contract->project_name ?? 'غير محدد') ?> (<?= htmlspecialchars($contract->project_code ?? '') ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label">الطرف الثاني / العميل:</span>
                <span class="info-val"><?= htmlspecialchars($contract->customer_name ?? 'غير محدد') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">نوع العقد التنفيذي:</span>
                <span class="info-val"><?= $typeMap[$contract->contract_type] ?? 'عقد مشروع' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">تواريخ العقد والسريان:</span>
                <span class="info-val" style="font-family:monospace;">توقيع: <?= htmlspecialchars($contract->sign_date ?: 'غير محدد') ?> | بدء: <?= htmlspecialchars($contract->start_date) ?> | انتهاء: <?= htmlspecialchars($contract->end_date ?: 'غير محدد') ?></span>
            </div>
        </div>

        <?php if(!empty($contract->terms_and_conditions)): ?>
            <div style="margin-top:24px; padding:20px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0;">
                <h5 style="margin:0 0 10px 0; font-size:0.9rem; color:var(--c-text-dark); font-weight:800;">الشروط والأحكام الالتزامية للعقد:</h5>
                <p style="margin:0; font-size:0.88rem; color:#334155; line-height:1.7; white-space:pre-line;"><?= htmlspecialchars($contract->terms_and_conditions) ?></p>
            </div>
        <?php endif; ?>

        <?php if(!empty($contract->notes)): ?>
            <div style="margin-top:16px; padding-top:12px; border-top:1px solid #f1f5f9;">
                <p style="margin:0; font-size:0.85rem; color:#64748b;"><b>ملاحظات إضافية:</b> <?= htmlspecialchars($contract->notes) ?></p>
            </div>
        <?php endif; ?>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6>توقيع واعتماد الطرف الأول</h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6>توقيع واعتماد الطرف الثاني</h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6>الإدارة القانونية والمالية</h6>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>
</div>