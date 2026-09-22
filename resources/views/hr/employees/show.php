<?php
// Path: resources/views/hr/employees/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert  = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

if (!isset($employee) || !$employee) {
    header("Location: /ERP/hr/employees");
    exit;
}

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$empCode     = htmlspecialchars((string)($employee->emp_code ?? '---'));
$empNameAr   = (string)($employee->name_ar ?? '');
$empNameEn   = (string)($employee->name_en ?? '');
$empName     = $isAr ? $empNameAr : ($empNameEn ?: $empNameAr);
$deptName    = htmlspecialchars((string)($isAr ? ($employee->department_name_ar ?? 'غير محدد') : ($employee->department_name_en ?: ($employee->department_name_ar ?? 'Unassigned'))));
$desigTitle  = htmlspecialchars((string)($isAr ? ($employee->designation_title_ar ?? 'بدون مسمى') : ($employee->designation_title_en ?: ($employee->designation_title_ar ?? 'No Title'))));
$nationalId  = htmlspecialchars((string)($employee->national_id ?: '---'));
$passportNo  = htmlspecialchars((string)($employee->passport_no ?: '---'));
$phone       = htmlspecialchars((string)($employee->phone ?: '---'));
$email       = htmlspecialchars((string)($employee->email ?: '---'));
$joiningDate = htmlspecialchars((string)($employee->joining_date ?? '---'));
$address     = htmlspecialchars((string)($employee->address ?? ''));
$basicSalary = (float)($employee->basic_salary ?? 0);

$statusMap = [
    'active'     => ['label' => $isAr ? 'على رأس العمل' : 'Active', 'color' => '#2563eb', 'bg' => '#dbeafe'],
    'on_leave'   => ['label' => $isAr ? 'في إجازة رسمية' : 'On Leave', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'resigned'   => ['label' => $isAr ? 'مستقيل' : 'Resigned', 'color' => '#64748b', 'bg' => '#f1f5f9'],
    'terminated' => ['label' => $isAr ? 'منهي خدماته' : 'Terminated', 'color' => '#dc2626', 'bg' => '#fef2f2'],
];
$st = $statusMap[$employee->status ?? 'active'] ?? $statusMap['active'];

$t = [
    'ar' => [
        'print' => 'طباعة البطاقة التعريفية',
        'edit' => 'تعديل',
        'title' => 'بطاقة موظف رسمي وسجل وظيفي',
        'sub' => 'إدارة الموارد البشرية - شؤون الموظفين',
        'official_badge' => 'كادر رسمي',
        'name_ar_lbl' => 'الاسم بالكامل (بالعربية):',
        'name_en_lbl' => 'الاسم بالكامل (بالإنجليزية):',
        'dept_lbl' => 'الإدارة والمسمى الوظيفي:',
        'id_lbl' => 'رقم الهوية الوطنية / الإقامة:',
        'passport_lbl' => 'رقم جواز السفر:',
        'contact_lbl' => 'الهاتف والبريد الإلكتروني:',
        'join_lbl' => 'تاريخ المباشرة:',
        'salary_lbl' => 'الراتب الأساسي الشهري:',
        'status_lbl' => 'حالة الموظف:',
        'address_lbl' => 'العنوان الإقامي:',
        'sig_hr' => 'مدير الموارد البشرية',
        'sig_employee' => 'توقيع الموظف',
        'sig_sub' => 'التوقيع والاعتماد الرسميين'
    ],
    'en' => [
        'print' => 'Print ID Card',
        'edit' => 'Edit',
        'title' => 'Official Employee ID & Master Record',
        'sub' => 'HR Department - Employee Personnel File',
        'official_badge' => 'Official Staff',
        'name_ar_lbl' => 'Full Name (Arabic):',
        'name_en_lbl' => 'Full Name (English):',
        'dept_lbl' => 'Department & Position:',
        'id_lbl' => 'National ID / Iqama Number:',
        'passport_lbl' => 'Passport Number:',
        'contact_lbl' => 'Phone & Email:',
        'join_lbl' => 'Joining Date:',
        'salary_lbl' => 'Base Monthly Salary:',
        'status_lbl' => 'Employee Status:',
        'address_lbl' => 'Residential Address:',
        'sig_hr' => 'HR Director',
        'sig_employee' => 'Employee Signature',
        'sig_sub' => 'Official Signature & Stamp'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-emp: #2563eb; --c-emp-dark: #1d4ed8; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .emp-show-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition: 0.2s; }
    .btn-action:hover { background: #dbeafe; color: var(--c-emp-dark); border-color: #bfdbfe; }
    .btn-print { background: var(--c-emp-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #1e40af; }

    .card-box { background: #ffffff; border: 2px solid var(--c-emp); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 160px; }
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
        .emp-show-wrapper, .emp-show-wrapper * { visibility: visible !important; }
        .emp-show-wrapper { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; }
        .header-bar { display: none !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; padding: 20px !important; }
        .dataTables_wrapper, .table-pagination-nav { display: none !important; visibility: hidden !important; }
    }
</style>

<div class="emp-show-wrapper" dir="<?= $dir ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/employees" class="btn-action"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($empName) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-emp-dark); font-weight:800; font-family:monospace;"><?= $empCode ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="/ERP/hr/employees/<?= (int)$employee->id ?>/edit" class="btn-action" style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1;"><i class="ph-bold ph-pencil-simple"></i></a>
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="print-canvas card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-emp); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars((string)$cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-user-circle" style="font-size:3.5rem; color:var(--c-emp);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars((string)$cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= $t['sub'] ?></span>
                </div>
            </div>
            <div style="text-align:<?= $isAr ? 'left' : 'right' ?>;">
                <span style="background:var(--c-emp); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;"><?= $t['official_badge'] ?></span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-emp-dark); font-size:1.1rem;"># <?= $empCode ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label"><?= $t['name_ar_lbl'] ?></span>
                <span class="info-val"><?= htmlspecialchars((string)$empNameAr) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['name_en_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace;"><?= htmlspecialchars((string)($empNameEn ?: '---')) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['dept_lbl'] ?></span>
                <span class="info-val"><?= $deptName ?> - <?= $desigTitle ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['id_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace;"><?= $nationalId ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['passport_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace;"><?= $passportNo ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['contact_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace;"><?= $phone ?> | <?= $email ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['join_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace;"><?= $joiningDate ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['salary_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace; color:#059669; font-size:1.1rem;"><?= number_format($convert($basicSalary), 2) ?> <?= $currency ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['status_lbl'] ?></span>
                <span class="info-val"><span style="padding:2px 10px; border-radius:4px; font-size:0.85rem; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>; font-weight:900;"><?= $st['label'] ?></span></span>
            </div>
        </div>

        <?php if(!empty($address)): ?>
            <div style="margin-top:24px; padding:16px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0;">
                <h5 style="margin:0 0 6px 0; font-size:0.85rem; color:var(--c-text-muted); font-weight:800;"><?= $t['address_lbl'] ?></h5>
                <p style="margin:0; font-size:0.9rem; color:#334155; font-weight:700;"><?= $address ?></p>
            </div>
        <?php endif; ?>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6><?= $t['sig_employee'] ?></h6>
                <div style="font-size:0.75rem; color:#64748b; margin-bottom:30px;"><?= $t['sig_sub'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= $t['sig_hr'] ?></h6>
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