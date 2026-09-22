<?php
// Path: resources/views/hr/salary_components/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert  = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

if (!isset($component) || !$component) {
    header("Location: /ERP/hr/salary-components");
    exit;
}

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$empName   = htmlspecialchars((string)($isAr ? ($component->employee_name ?? 'عام') : ($component->employee_name_en ?: ($component->employee_name ?? 'General'))));
$empCode   = htmlspecialchars((string)($component->emp_code ?? '---'));
$deptName  = htmlspecialchars((string)($isAr ? ($component->dept_name ?? 'عام') : ($component->dept_name_en ?: ($component->dept_name ?? 'General'))));
$compName  = htmlspecialchars((string)($component->name_ar ?? '---'));
$amount    = (float)($component->amount ?? 0);
$isFixed   = (bool)($component->is_fixed ?? true);
$branchName = htmlspecialchars((string)($component->branch_name ?? ($isAr ? 'عام' : 'General')));

$typeMap = [
    'allowance' => ['label' => $isAr ? 'بدل / إضافي' : 'Allowance / Addition', 'color' => '#059669', 'bg' => '#ecfdf5'],
    'deduction' => ['label' => $isAr ? 'خصم / استقطاع' : 'Deduction / Cut', 'color' => '#dc2626', 'bg' => '#fef2f2'],
];
$tp = $typeMap[$component->type ?? 'allowance'] ?? $typeMap['allowance'];

$t = [
    'ar' => [
        'print' => 'طباعة الوثيقة',
        'edit' => 'تعديل',
        'title' => 'بطاقة تعريف بدل / مفرد راتب',
        'sub' => 'إدارة الموارد البشرية - شؤون الأجور والبدلات',
        'official_badge' => 'عنصر راتب معتمد',
        'emp_lbl' => 'الموظف المستفيد:',
        'dept_lbl' => 'الإدارة التابع لها:',
        'branch_lbl' => 'الفرع النشط:',
        'comp_lbl' => 'اسم البدل / المفرد:',
        'type_lbl' => 'نوع المفرد:',
        'amount_lbl' => 'المبلغ المقدر:',
        'fixed_lbl' => 'طبيعة العنصر:',
        'fixed_yes' => 'عنصر ثابت يدرج شهرياً تلقائياً',
        'fixed_no' => 'عنصر متغير / مؤقت',
        'sig_hr' => 'مدير الموارد البشرية',
        'sig_fin' => 'المدير المالي',
        'sig_sub' => 'التوقيع والاعتماد الرسميين'
    ],
    'en' => [
        'print' => 'Print Component Voucher',
        'edit' => 'Edit',
        'title' => 'Salary Component Card',
        'sub' => 'HR Department - Payroll & Allowances Division',
        'official_badge' => 'Approved Element',
        'emp_lbl' => 'Beneficiary Employee:',
        'dept_lbl' => 'Department:',
        'branch_lbl' => 'Active Branch:',
        'comp_lbl' => 'Component Name:',
        'type_lbl' => 'Type:',
        'amount_lbl' => 'Estimated Amount:',
        'fixed_lbl' => 'Property:',
        'fixed_yes' => 'Fixed Monthly Element (Auto-applied)',
        'fixed_no' => 'Variable / Temporary Element',
        'sig_hr' => 'HR Director',
        'sig_fin' => 'Finance Director',
        'sig_sub' => 'Official Signature & Stamp'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-comp: #4338ca; --c-comp-dark: #312e81; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .comp-show-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition: 0.2s; }
    .btn-action:hover { background: #e0e7ff; color: var(--c-comp-dark); border-color: #c7d2fe; }
    .btn-print { background: var(--c-comp-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #1e1b4b; }

    .card-box { background: #ffffff; border: 2px solid var(--c-comp); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 180px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

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
        .comp-show-wrapper, .comp-show-wrapper * { visibility: visible !important; }
        .comp-show-wrapper { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; }
        .header-bar { display: none !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; padding: 20px !important; }
        .dataTables_wrapper, .table-pagination-nav { display: none !important; visibility: hidden !important; }
    }
</style>

<div class="comp-show-wrapper" dir="<?= $dir ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/salary-components" class="btn-action"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= $compName ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-comp-dark); font-weight:800; font-family:monospace;"><?= $empName ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="/ERP/hr/salary-components/<?= (int)$component->id ?>/edit" class="btn-action" style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1;"><i class="ph-bold ph-pencil-simple"></i></a>
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="print-canvas card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-comp); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars((string)$cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-sliders-horizontal" style="font-size:3.5rem; color:var(--c-comp);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars((string)$cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= $t['sub'] ?></span>
                </div>
            </div>
            <div style="text-align:<?= $isAr ? 'left' : 'right' ?>;">
                <span style="background:var(--c-comp); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;"><?= $t['official_badge'] ?></span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-comp-dark); font-size:1.1rem;"># CMP-<?= (int)$component->id ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label"><?= $t['emp_lbl'] ?></span>
                <span class="info-val"><?= $empName ?> (<?= $empCode ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['dept_lbl'] ?></span>
                <span class="info-val"><?= $deptName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['branch_lbl'] ?></span>
                <span class="info-val"><?= $branchName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['comp_lbl'] ?></span>
                <span class="info-val"><?= $compName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['type_lbl'] ?></span>
                <span class="info-val"><span style="padding:2px 10px; border-radius:4px; font-size:0.85rem; background:<?= $tp['bg'] ?>; color:<?= $tp['color'] ?>; font-weight:900;"><?= $tp['label'] ?></span></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['amount_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace; color:<?= ($component->type ?? '')==='allowance'?'#059669':'#dc2626' ?>; font-size:1.1rem;"><?= number_format($convert($amount), 2) ?> <?= $currency ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['fixed_lbl'] ?></span>
                <span class="info-val"><?= $isFixed ? $t['fixed_yes'] : $t['fixed_no'] ?></span>
            </div>
        </div>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6><?= $t['sig_hr'] ?></h6>
                <div style="font-size:0.75rem; color:#64748b; margin-bottom:30px;"><?= $t['sig_sub'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= $t['sig_fin'] ?></h6>
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