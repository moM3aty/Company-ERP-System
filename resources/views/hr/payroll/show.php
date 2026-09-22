<?php
// Path: resources/views/hr/payroll/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert  = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

if (!isset($payroll) || !$payroll) {
    header("Location: /ERP/hr/payroll");
    exit;
}

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$branchName = htmlspecialchars((string)($payroll->branch_name ?? ($isAr ? 'عام' : 'General')));

$statusMap = [
    'draft'     => ['label' => $isAr ? 'مسودة قيد الإعداد' : 'Draft', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'processed' => ['label' => $isAr ? 'مسير معتمد ومحتسب' : 'Processed', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
    'paid'      => ['label' => $isAr ? 'مسير مصروف بالكامل' : 'Paid', 'color' => '#059669', 'bg' => '#ecfdf5'],
];
$st = $statusMap[$payroll->status ?? 'processed'] ?? $statusMap['processed'];

$t = [
    'ar' => [
        'print' => 'طباعة كشف المسير',
        'edit' => 'تعديل',
        'title' => 'مسير رواتب:',
        'sub' => 'كشف مسير الرواتب والمستحقات الشهرية الرسمية',
        'official_badge' => 'مسير معتمد',
        'branch_lbl' => 'الفرع النشط:',
        'col_hash' => 'م',
        'col_emp' => 'اسم الموظف والكود',
        'col_dept' => 'الإدارة',
        'col_basic' => 'الأساسي',
        'col_allow' => 'البدلات',
        'col_net' => 'صافي المستحق (Net)',
        'empty' => 'لا توجد تفاصيل موظفين لهذا المسير.',
        'general' => 'عام',
        'total_label' => 'إجمالي المسير العام:',
        'sig_spec' => 'إعداد أخصائي الأجور',
        'sig_hr' => 'مراجعة مدير الموارد البشرية',
        'sig_fin' => 'اعتماد المدير المالي',
        'sig_sub' => 'التوقيع والاعتماد الرسميين'
    ],
    'en' => [
        'print' => 'Print Payroll Slip',
        'edit' => 'Edit',
        'title' => 'Payroll Run:',
        'sub' => 'Official Monthly Payroll & Entitlements Sheet',
        'official_badge' => 'Approved Payroll',
        'branch_lbl' => 'Active Branch:',
        'col_hash' => '#',
        'col_emp' => 'Employee & Code',
        'col_dept' => 'Department',
        'col_basic' => 'Basic Salary',
        'col_allow' => 'Allowances',
        'col_net' => 'Net Pay',
        'empty' => 'No employee details for this payroll.',
        'general' => 'General',
        'total_label' => 'Total Payroll Run:',
        'sig_spec' => 'Payroll Specialist Prep',
        'sig_hr' => 'HR Director Review',
        'sig_fin' => 'Finance Director Approval',
        'sig_sub' => 'Official Signature & Stamp'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-pay: #be123c; --c-pay-dark: #9f1239; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .pay-show-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition: 0.2s; }
    .btn-action:hover { background: #ffe4e6; color: var(--c-pay-dark); border-color: #fecdd3; }
    .btn-print { background: var(--c-pay-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #881337; }

    .card-box { background: #ffffff; border: 2px solid var(--c-pay); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }

    .pay-details-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 24px; }
    .pay-details-table th { background: #f8fafc; border: 1px solid #cbd5e1; padding: 10px; font-weight: 800; text-align: start; }
    .pay-details-table td { border: 1px solid #e2e8f0; padding: 10px; color: #334155; }

    .signatures-grid { display: flex; justify-content: space-between; text-align: center; margin-top: 48px; }
    .sig-box { flex: 1; padding: 0 10px; }
    .sig-box h6 { margin: 0 0 30px 0; font-size: 0.85rem; color: var(--c-text-dark); font-weight: 800; }
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
        .pay-show-wrapper, .pay-show-wrapper * { visibility: visible !important; }
        .pay-show-wrapper { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; }
        .header-bar { display: none !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; padding: 20px !important; }
        .dataTables_wrapper, .table-pagination-nav { display: none !important; visibility: hidden !important; }
        .pay-details-table th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>

<div class="pay-show-wrapper" dir="<?= $dir ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/payroll" class="btn-action"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= $t['title'] ?> <?= htmlspecialchars((string)($payroll->payroll_code ?? '')) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-pay-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars((string)($payroll->month ?? '')) ?> / <?= (int)($payroll->year ?? date('Y')) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="/ERP/hr/payroll/<?= (int)$payroll->id ?>/edit" class="btn-action" style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1;"><i class="ph-bold ph-pencil-simple"></i></a>
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="print-canvas card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-pay); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars((string)$cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-receipt" style="font-size:3.5rem; color:var(--c-pay);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars((string)$cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= $t['sub'] ?></span>
                </div>
            </div>
            <div style="text-align:<?= $isAr ? 'left' : 'right' ?>;">
                <span style="background:var(--c-pay); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;"><?= $t['official_badge'] ?></span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-pay-dark); font-size:1.1rem;"># <?= htmlspecialchars((string)($payroll->payroll_code ?? '')) ?></div>
            </div>
        </div>

        <div style="margin-bottom: 20px; font-size: 0.95rem; font-weight: 800; color: var(--c-text-dark);">
            <i class="ph-bold ph-storefront" style="color:var(--c-pay);"></i> <?= $t['branch_lbl'] ?> <?= $branchName ?>
        </div>

        <!-- جدول تفاصيل الموظفين والمسير -->
        <table class="pay-details-table">
            <thead>
                <tr>
                    <th style="width: 5%;"><?= $t['col_hash'] ?></th>
                    <th style="width: 25%;"><?= $t['col_emp'] ?></th>
                    <th style="width: 20%;"><?= $t['col_dept'] ?></th>
                    <th style="width: 15%; text-align:end;"><?= $t['col_basic'] ?></th>
                    <th style="width: 15%; text-align:end;"><?= $t['col_allow'] ?></th>
                    <th style="width: 20%; text-align:end;"><?= $t['col_net'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($employeeDetails)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:20px; color:var(--c-text-muted);"><?= $t['empty'] ?></td></tr>
                <?php else: foreach($employeeDetails as $idx => $emp): 
                    $empName = $isAr ? ($emp->name_ar ?? '') : ($emp->name_en ?: ($emp->name_ar ?? ''));
                    $deptName = $isAr ? ($emp->dept_name ?? $t['general']) : ($emp->dept_name_en ?: ($emp->dept_name ?? $t['general']));
                    $empAllowances = (float)($emp->housing ?? 0) + (float)($emp->transport ?? 0);
                    $empNet = (float)($emp->basic_salary ?? 0) + $empAllowances;
                ?>
                    <tr>
                        <td style="font-weight:bold;"><?= $idx + 1 ?></td>
                        <td>
                            <div style="font-weight:bold; color:#0f172a;"><?= htmlspecialchars((string)$empName) ?></div>
                            <div style="font-size:0.75rem; font-family:monospace; color:#64748b;"><?= htmlspecialchars((string)($emp->emp_code ?? '')) ?></div>
                        </td>
                        <td><?= htmlspecialchars((string)$deptName) ?></td>
                        <td style="font-family:monospace; font-weight:bold; text-align:end;"><?= number_format($convert($emp->basic_salary ?? 0), 2) ?></td>
                        <td style="font-family:monospace; color:#0284c7; text-align:end;"><?= number_format($convert($empAllowances), 2) ?></td>
                        <td style="font-family:monospace; font-weight:900; color:#059669; text-align:end;"><?= number_format($convert($empNet), 2) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
                <tr style="background:#fff1f2; font-weight:900;">
                    <td colspan="3" style="text-align:<?= $isAr ? 'left' : 'right' ?>; padding:12px;"><?= $t['total_label'] ?></td>
                    <td style="font-family:monospace; text-align:end;"><?= number_format($convert($payroll->total_basic ?? 0), 2) ?></td>
                    <td style="font-family:monospace; color:#0284c7; text-align:end;"><?= number_format($convert($payroll->total_allowances ?? 0), 2) ?></td>
                    <td style="font-family:monospace; color:#059669; font-size:1.1rem; text-align:end;"><?= number_format($convert($payroll->net_pay ?? 0), 2) ?> <span style="font-size:0.75rem; color:#64748b;"><?= $currency ?></span></td>
                </tr>
            </tfoot>
        </table>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6><?= $t['sig_spec'] ?></h6>
                <div style="font-size:0.75rem; color:#64748b; margin-bottom:30px;"><?= $t['sig_sub'] ?></div>
                <div class="sig-line"></div>
            </div>
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