<?php
// Path: resources/views/hr/contracts/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert  = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

if (!isset($contract) || !$contract) {
    header("Location: /ERP/hr/contracts");
    exit;
}

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$contractCode = htmlspecialchars((string)($contract->contract_code ?? '---'));
$empName      = htmlspecialchars((string)($isAr ? ($contract->employee_name ?? '---') : ($contract->employee_name_en ?: ($contract->employee_name ?? '---'))));
$empCode      = htmlspecialchars((string)($contract->emp_code ?? '---'));
$nationalId   = htmlspecialchars((string)($contract->national_id ?: '---'));
$deptName     = htmlspecialchars((string)($isAr ? ($contract->dept_name ?? 'عام') : ($contract->dept_name_en ?: ($contract->dept_name ?? 'General'))));
$desigName    = htmlspecialchars((string)($isAr ? ($contract->desig_name ?? '---') : ($contract->desig_name_en ?: ($contract->desig_name ?? '---'))));
$startDate    = htmlspecialchars((string)($contract->start_date ?? '---'));
$endDate      = htmlspecialchars((string)($contract->end_date ?: ($isAr ? 'عقد غير محدد المدة' : 'Indefinite Contract')));
$notes        = htmlspecialchars((string)($contract->notes ?? ''));

$basic     = (float)($contract->basic_salary ?? 0);
$housing   = (float)($contract->housing_allowance ?? 0);
$transport = (float)($contract->transport_allowance ?? 0);
$total     = $basic + $housing + $transport;

$statusMap = [
    'active'     => ['label' => $isAr ? 'عقد ساري المفعول' : 'Active Contract', 'color' => '#059669', 'bg' => '#ecfdf5'],
    'expired'    => ['label' => $isAr ? 'عقد منتهي الصلاحية' : 'Expired Contract', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'terminated' => ['label' => $isAr ? 'عقد مفسوخ' : 'Terminated Contract', 'color' => '#dc2626', 'bg' => '#fef2f2'],
];
$st = $statusMap[$contract->status ?? 'active'] ?? $statusMap['active'];

$t = [
    'ar' => [
        'print' => 'طباعة وثيقة العقد',
        'edit' => 'تعديل',
        'title' => 'عقد عمل رسمي (Employment Contract)',
        'sub' => 'إدارة الموارد البشرية - قسم شؤون الموظفين',
        'emp_lbl' => 'اسم الطرف الثاني (الموظف):',
        'emp_code_lbl' => 'كود الموظف / رقم الهوية:',
        'desig_lbl' => 'المسمى / الإدارة:',
        'start_lbl' => 'تاريخ البداية (سريان العقد):',
        'end_lbl' => 'تاريخ نهاية العقد:',
        'status_lbl' => 'حالة العقد:',
        'pkg_title' => 'البدلات والرواتب (Financial Package)',
        'basic_lbl' => 'الراتب الأساسي (Basic Salary):',
        'housing_lbl' => 'بدل السكن (Housing Allowance):',
        'transport_lbl' => 'بدل النقل والمواصلات (Transport):',
        'total_lbl' => 'إجمالي الراتب (Total Gross Package):',
        'notes_lbl' => 'شروط وملاحظات إضافية:',
        'sig_party1' => 'الطرف الأول (الشركة/الإدارة)',
        'sig_party2' => 'الطرف الثاني (الموظف)',
        'sig_sub' => 'التوقيع والاعتماد الرسميين'
    ],
    'en' => [
        'print' => 'Print Contract Document',
        'edit' => 'Edit',
        'title' => 'Employment Contract Agreement',
        'sub' => 'HR Department - Personnel File',
        'emp_lbl' => 'Second Party (Employee Name):',
        'emp_code_lbl' => 'Employee Code / ID:',
        'desig_lbl' => 'Position & Department:',
        'start_lbl' => 'Contract Effective Date:',
        'end_lbl' => 'Contract Expiry Date:',
        'status_lbl' => 'Contract Status:',
        'pkg_title' => 'Salaries & Allowances Package',
        'basic_lbl' => 'Basic Salary:',
        'housing_lbl' => 'Housing Allowance:',
        'transport_lbl' => 'Transport Allowance:',
        'total_lbl' => 'Total Gross Monthly Package:',
        'notes_lbl' => 'Additional Terms & Conditions:',
        'sig_party1' => 'First Party (Employer)',
        'sig_party2' => 'Second Party (Employee)',
        'sig_sub' => 'Official Signature & Stamp'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-hcont: #d97706; --c-hcont-dark: #b45309; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .hcont-show-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition: 0.2s; }
    .btn-action:hover { background: #fef3c7; color: var(--c-hcont-dark); border-color: #fde68a; }
    .btn-print { background: var(--c-hcont-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #78350f; }

    .card-box { background: #ffffff; border: 2px solid var(--c-hcont); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 180px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    .salary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-top: 24px; }
    .sal-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-weight: 700; color: #475569; }
    .sal-total { display: flex; justify-content: space-between; font-weight: 900; color: var(--c-text-dark); font-size: 1.2rem; border-top: 2px dashed #cbd5e1; padding-top: 12px; margin-top: 12px; }

    .signatures-grid { display: flex; justify-content: space-between; text-align: center; margin-top: 48px; }
    .sig-box { flex: 1; padding: 0 10px; }
    .sig-box h6 { margin: 0 0 30px 0; font-size: 0.85rem; color: var(--c-text-muted); font-weight: 800; }
    .sig-line { border-bottom: 1px dashed #cbd5e1; width: 80%; margin: 0 auto; }

    /* Purge injected DataTables elements */
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
        .hcont-show-wrapper, .hcont-show-wrapper * { visibility: visible !important; }
        .hcont-show-wrapper { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; }
        .header-bar { display: none !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; padding: 20px !important; }
        .dataTables_wrapper, .table-pagination-nav { display: none !important; visibility: hidden !important; }
    }
</style>

<div class="hcont-show-wrapper" dir="<?= $dir ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/contracts" class="btn-action"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= $contractCode ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-hcont-dark); font-weight:800; font-family:monospace;"><?= $empName ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="/ERP/hr/contracts/<?= (int)$contract->id ?>/edit" class="btn-action" style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1;"><i class="ph-bold ph-pencil-simple"></i></a>
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="print-canvas card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-hcont); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars((string)$cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-handshake" style="font-size:3.5rem; color:var(--c-hcont);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars((string)$cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= $t['sub'] ?></span>
                </div>
            </div>
            <div style="text-align:<?= $isAr ? 'left' : 'right' ?>;">
                <span style="background:var(--c-hcont); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;"><?= $t['title'] ?></span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-hcont-dark); font-size:1.1rem;"># <?= $contractCode ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label"><?= $t['emp_lbl'] ?></span>
                <span class="info-val"><?= $empName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['emp_code_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace;"><?= $empCode ?> | <?= $nationalId ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['desig_lbl'] ?></span>
                <span class="info-val"><?= $desigName ?> - <?= $deptName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['start_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace; color:#059669;"><?= $startDate ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['end_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace; color:#dc2626;"><?= $endDate ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['status_lbl'] ?></span>
                <span class="info-val"><span style="padding:2px 10px; border-radius:4px; font-size:0.85rem; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>; font-weight:900;"><?= $st['label'] ?></span></span>
            </div>
        </div>

        <div class="salary-box">
            <h5 style="margin:0 0 16px 0; font-size:1.1rem; color:var(--c-text-dark); font-weight:900;"><i class="ph-bold ph-coins" style="color:var(--c-hcont);"></i> <?= $t['pkg_title'] ?></h5>
            <div class="sal-row">
                <span><?= $t['basic_lbl'] ?></span>
                <span style="font-family:monospace; color:#059669;"><?= number_format($convert($basic), 2) ?> <?= $currency ?></span>
            </div>
            <div class="sal-row">
                <span><?= $t['housing_lbl'] ?></span>
                <span style="font-family:monospace; color:#0284c7;"><?= number_format($convert($housing), 2) ?> <?= $currency ?></span>
            </div>
            <div class="sal-row">
                <span><?= $t['transport_lbl'] ?></span>
                <span style="font-family:monospace; color:#d97706;"><?= number_format($convert($transport), 2) ?> <?= $currency ?></span>
            </div>
            <div class="sal-total">
                <span><?= $t['total_lbl'] ?></span>
                <span style="font-family:monospace;"><?= number_format($convert($total), 2) ?> <?= $currency ?></span>
            </div>
        </div>

        <?php if(!empty($notes)): ?>
            <div style="margin-top:24px; padding:16px; border-top:1px solid #f1f5f9;">
                <h5 style="margin:0 0 6px 0; font-size:0.85rem; color:var(--c-text-muted); font-weight:800;"><?= $t['notes_lbl'] ?></h5>
                <p style="margin:0; font-size:0.9rem; color:#334155; font-weight:700; white-space:pre-line;"><?= $notes ?></p>
            </div>
        <?php endif; ?>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6><?= $t['sig_party1'] ?></h6>
                <div style="font-size:0.75rem; color:#64748b; margin-bottom:30px;"><?= $t['sig_sub'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= $t['sig_party2'] ?></h6>
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