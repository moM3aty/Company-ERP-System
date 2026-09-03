<?php
// Path: resources/views/projects/contracts/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$t = [
    'ar' => [
        'print' => 'طباعة وثيقة العقد', 'edit' => 'تعديل العقد', 'back' => 'العودة',
        'subtitle' => 'إدارة العقود والمتابعة القانونية', 'brand_title' => 'وثيقة عقد رسمي (CONTRACT AGREEMENT)',
        'proj' => 'المشروع التابع له:', 'client' => 'العميل المالك:', 'type' => 'نوع العقد:', 'dates' => 'تواريخ السريان:',
        'val' => 'القيمة الكلية للعقد', 'adv' => 'الدفعة المقدمة', 'ret' => 'نسبة استقطاع الضمان', 'status' => 'حالة العقد',
        'terms' => 'الشروط والأحكام الالتزامية للعقد:', 'notes' => 'ملاحظات إضافية:',
        'sig_1' => 'توقيع واعتماد الطرف الأول', 'sig_2' => 'توقيع واعتماد الطرف الثاني', 'sig_legal' => 'الإدارة القانونية والمالية',
        'st_draft' => 'مسودة', 'st_active' => 'ساري', 'st_renewal' => 'قيد التجديد', 'st_completed' => 'مكتمل', 'st_suspended' => 'موقف مؤقتاً', 'st_terminated' => 'مفسوخ / ملغى',
        'tp_owner' => 'عقد المالك الرئيسي', 'tp_sub' => 'عقد مقاول فرعي', 'tp_cons' => 'عقد استشاري', 'tp_supp' => 'عقد توريد مواد'
    ],
    'en' => [
        'print' => 'Print Contract', 'edit' => 'Edit', 'back' => 'Back',
        'subtitle' => 'Contracts & Legal Follow-up', 'brand_title' => 'OFFICIAL CONTRACT AGREEMENT',
        'proj' => 'Linked Project:', 'client' => 'Client / Owner:', 'type' => 'Contract Type:', 'dates' => 'Validity Dates:',
        'val' => 'Total Contract Value', 'adv' => 'Advance Payment', 'ret' => 'Retention Guarantee %', 'status' => 'Contract Status',
        'terms' => 'Terms & Conditions:', 'notes' => 'Additional Notes:',
        'sig_1' => 'First Party Signature', 'sig_2' => 'Second Party Signature', 'sig_legal' => 'Legal & Finance Dept.',
        'st_draft' => 'Draft', 'st_active' => 'Active', 'st_renewal' => 'Under Renewal', 'st_completed' => 'Completed', 'st_suspended' => 'Suspended', 'st_terminated' => 'Terminated',
        'tp_owner' => 'Main Owner Contract', 'tp_sub' => 'Subcontractor Contract', 'tp_cons' => 'Consultant Contract', 'tp_supp' => 'Supply Contract'
    ]
][$isAr ? 'ar' : 'en'];

$statusMap = [
    'draft' => ['label' => $t['st_draft'], 'color' => '#64748b', 'bg' => '#f1f5f9'],
    'active' => ['label' => $t['st_active'], 'color' => '#be123c', 'bg' => '#fff1f2'],
    'under_renewal' => ['label' => $t['st_renewal'], 'color' => '#d97706', 'bg' => '#fef3c7'],
    'completed' => ['label' => $t['st_completed'], 'color' => '#059669', 'bg' => '#ecfdf5'],
    'suspended' => ['label' => $t['st_suspended'], 'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
    'terminated' => ['label' => $t['st_terminated'], 'color' => '#dc2626', 'bg' => '#fef2f2'],
];

$typeMap = [
    'owner_contract' => $t['tp_owner'],
    'subcontractor_contract' => $t['tp_sub'],
    'consultant_contract' => $t['tp_cons'],
    'supply_contract' => $t['tp_supp'],
];

$st = $statusMap[$contract->status] ?? $statusMap['active'];
$cTitle = $isAr ? ($contract->title_ar ?: $contract->title_en) : ($contract->title_en ?: $contract->title_ar);
?>

<style>
    :root { --c-pcontract: #be123c; --c-pcontract-dark: #9f1239; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .pcontract-show-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition:0.2s;}
    .btn-action:hover { background: #fff1f2; color: var(--c-pcontract); border-color: #fecdd3; }
    .btn-print { background: var(--c-pcontract-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(190, 18, 60, 0.25); transition:0.2s;}
    .btn-print:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(190, 18, 60, 0.35); }

    .voucher-card { background: #ffffff; border: 2px solid var(--c-pcontract); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }
    .voucher-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px double var(--c-pcontract); padding-bottom: 20px; margin-bottom: 24px; }
    .voucher-title-badge { background: var(--c-pcontract); color: #fff; padding: 6px 20px; border-radius: 8px; font-size: 1.1rem; font-weight: 900; letter-spacing: 1px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 170px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin: 24px 0; }
    @media(max-width:768px){ .grid-4 { grid-template-columns: repeat(2, 1fr); } }

    .card-kpi { background: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 16px; text-align: center; }
    .card-kpi h6 { margin: 0 0 8px 0; font-size: 0.75rem; color: #9f1239; font-weight: 800; }
    .card-kpi p { margin: 0; font-size: 1.25rem; font-weight: 900; font-family: monospace; color: var(--c-pcontract-dark); }

    .signatures-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 48px; text-align: center; }
    .sig-box h6 { margin: 0 0 40px 0; font-size: 0.85rem; color: var(--c-text-muted); font-weight: 800; }
    .sig-line { border-bottom: 1px solid #94a3b8; width: 80%; margin: 0 auto; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, .table-pagination-nav, header, aside { display: none !important; }
        body { background: #fff !important; }
        .pcontract-show-wrapper { max-width: 100% !important; padding: 0 !important;}
        .voucher-card { border: 2px solid #000 !important; box-shadow: none !important; page-break-inside: avoid; }
        .card-kpi { border: 1px solid #000 !important; background: transparent !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="pcontract-show-wrapper" dir="<?= $isAr ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/contracts" class="btn-action" title="<?= $t['back'] ?>"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cTitle) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-pcontract-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($contract->contract_number) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
            <a href="/ERP/projects/contracts/<?= $contract->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="<?= $t['edit'] ?>"><i class="ph-bold ph-pencil"></i></a>
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
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= $t['subtitle'] ?></span>
                </div>
            </div>
            <div style="text-align:<?= $isAr ? 'left' : 'right' ?>;">
                <div class="voucher-title-badge"><?= $t['brand_title'] ?></div>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-pcontract-dark); font-size:1.1rem;"># <?= htmlspecialchars($contract->contract_number) ?></div>
            </div>
        </div>

        <div class="grid-4">
            <div class="card-kpi">
                <h6><?= $t['val'] ?></h6>
                <p><?= number_format((float)$contract->contract_value, 2) ?> <span style="font-size:0.7rem;"><?= $currency ?></span></p>
            </div>
            <div class="card-kpi" style="background:#f0fdf4; border-color:#a7f3d0;">
                <h6 style="color:#047857;"><?= $t['adv'] ?></h6>
                <p style="color:#059669;"><?= number_format((float)$contract->advance_payment_amount, 2) ?> <span style="font-size:0.7rem;"><?= $currency ?></span></p>
            </div>
            <div class="card-kpi" style="background:#fef3c7; border-color:#fde68a;">
                <h6 style="color:#b45309;"><?= $t['ret'] ?></h6>
                <p style="color:#d97706;"><?= number_format((float)$contract->retention_percent, 1) ?>%</p>
            </div>
            <div class="card-kpi" style="background:<?= $st['bg'] ?>; border-color:<?= $st['color'] ?>;">
                <h6 style="color:<?= $st['color'] ?>;"><?= $t['status'] ?></h6>
                <p style="font-size:0.95rem; color:<?= $st['color'] ?>; margin-top:8px;"><?= $st['label'] ?></p>
            </div>
        </div>

        <div style="margin-top:30px;">
            <div class="info-row">
                <span class="info-label"><?= $t['proj'] ?></span>
                <span class="info-val"><?= htmlspecialchars($contract->project_name ?? '---') ?> (<?= htmlspecialchars($contract->project_code ?? '') ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['client'] ?></span>
                <span class="info-val"><?= htmlspecialchars($contract->customer_name ?? '---') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['type'] ?></span>
                <span class="info-val"><?= $typeMap[$contract->contract_type] ?? '---' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['dates'] ?></span>
                <span class="info-val" style="font-family:monospace;">
                    Sign: <?= htmlspecialchars($contract->sign_date ?: '---') ?> | 
                    Start: <?= htmlspecialchars($contract->start_date) ?> | 
                    End: <?= htmlspecialchars($contract->end_date ?: '---') ?>
                </span>
            </div>
        </div>

        <?php if(!empty($contract->terms_and_conditions)): ?>
            <div style="margin-top:24px; padding:20px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0;">
                <h5 style="margin:0 0 10px 0; font-size:0.9rem; color:var(--c-text-dark); font-weight:800;"><?= $t['terms'] ?></h5>
                <p style="margin:0; font-size:0.88rem; color:#334155; line-height:1.7; white-space:pre-line;"><?= htmlspecialchars($contract->terms_and_conditions) ?></p>
            </div>
        <?php endif; ?>

        <?php if(!empty($contract->notes)): ?>
            <div style="margin-top:16px; padding-top:12px; border-top:1px solid #f1f5f9;">
                <p style="margin:0; font-size:0.85rem; color:#64748b;"><b><?= $t['notes'] ?></b> <?= htmlspecialchars($contract->notes) ?></p>
            </div>
        <?php endif; ?>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6><?= $t['sig_1'] ?></h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= $t['sig_2'] ?></h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= $t['sig_legal'] ?></h6>
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