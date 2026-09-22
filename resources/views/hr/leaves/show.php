<?php
// Path: resources/views/hr/leaves/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

if (!isset($leave) || !$leave) {
    header("Location: /ERP/hr/leaves");
    exit;
}

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$empName   = htmlspecialchars((string)($isAr ? ($leave->employee_name ?? '---') : ($leave->employee_name_en ?: ($leave->employee_name ?? '---'))));
$empCode   = htmlspecialchars((string)($leave->emp_code ?? '---'));
$deptName  = htmlspecialchars((string)($isAr ? ($leave->dept_name ?? 'عام') : ($leave->dept_name_en ?: ($leave->dept_name ?? 'General'))));
$startDate = htmlspecialchars((string)($leave->start_date ?? '---'));
$endDate   = htmlspecialchars((string)($leave->end_date ?? '---'));
$daysCount = (int)($leave->days_count ?? 0);
$reason    = htmlspecialchars((string)($leave->reason ?? ''));
$branchName = htmlspecialchars((string)($leave->branch_name ?? ($isAr ? 'عام' : 'General')));

$statusMap = [
    'pending'  => ['label' => $isAr ? 'قيد المراجعة والانتظار' : 'Pending Review', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'approved' => ['label' => $isAr ? 'مقبولة ومصادق عليها' : 'Approved', 'color' => '#059669', 'bg' => '#ecfdf5'],
    'rejected' => ['label' => $isAr ? 'طلب مرفوض' : 'Rejected', 'color' => '#dc2626', 'bg' => '#fef2f2'],
];
$st = $statusMap[$leave->status ?? 'pending'] ?? $statusMap['pending'];

$typeMap = [
    'annual'    => $isAr ? 'إجازة سنوية' : 'Annual Leave',
    'sick'      => $isAr ? 'إجازة مرضية' : 'Sick Leave',
    'unpaid'    => $isAr ? 'بدون راتب' : 'Unpaid Leave',
    'maternity' => $isAr ? 'إجازة وضع/أمومة' : 'Maternity Leave',
    'other'     => $isAr ? 'إجازة أخرى' : 'Other Leave'
];

$t = [
    'ar' => [
        'print' => 'طباعة النموذج',
        'title' => 'نموذج طلب إجازة رسمية',
        'sub' => 'إدارة الموارد البشرية - شؤون الموظفين',
        'official_badge' => 'طلب إجازة رسمي',
        'emp_lbl' => 'اسم الموظف صاحب الطلب:',
        'code_lbl' => 'كود الموظف / الإدارة:',
        'branch_lbl' => 'الفرع النشط:',
        'type_lbl' => 'نوع الإجازة:',
        'start_lbl' => 'تاريخ بدء الإجازة:',
        'end_lbl' => 'تاريخ النهاية والمباشرة:',
        'days_lbl' => 'إجمالي عدد أيام الإجازة:',
        'status_lbl' => 'حالة الطلب:',
        'reason_lbl' => 'سبب الإجازة والتفاصيل:',
        'days_unit' => 'يوم',
        'sig_emp' => 'توقيع الموظف',
        'sig_hr' => 'اعتماد مدير الموارد البشرية',
        'sig_sub' => 'التوقيع والاعتماد الرسميين'
    ],
    'en' => [
        'print' => 'Print Leave Form',
        'title' => 'Official Leave Request Form',
        'sub' => 'HR Department - Personnel Division',
        'official_badge' => 'Official Request',
        'emp_lbl' => 'Applicant Employee Name:',
        'code_lbl' => 'Employee Code / Dept:',
        'branch_lbl' => 'Active Branch:',
        'type_lbl' => 'Leave Type:',
        'start_lbl' => 'Start Date:',
        'end_lbl' => 'End & Resumption Date:',
        'days_lbl' => 'Total Leave Days:',
        'status_lbl' => 'Request Status:',
        'reason_lbl' => 'Leave Reason & Details:',
        'days_unit' => 'Days',
        'sig_emp' => 'Employee Signature',
        'sig_hr' => 'HR Director Approval',
        'sig_sub' => 'Official Signature & Stamp'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-leave: #7c3aed; --c-leave-dark: #6d28d9; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .leave-show-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition: 0.2s; }
    .btn-action:hover { background: #f3e8ff; color: var(--c-leave-dark); border-color: #ddd6fe; }
    .btn-print { background: var(--c-leave-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #5b21b6; }

    .card-box { background: #ffffff; border: 2px solid var(--c-leave); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }

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
        .leave-show-wrapper, .leave-show-wrapper * { visibility: visible !important; }
        .leave-show-wrapper { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; }
        .header-bar { display: none !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; padding: 20px !important; }
        .dataTables_wrapper, .table-pagination-nav { display: none !important; visibility: hidden !important; }
    }
</style>

<div class="leave-show-wrapper" dir="<?= $dir ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/leaves" class="btn-action"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-leave-dark); font-weight:800; font-family:monospace;"><?= $empName ?></p>
            </div>
        </div>
        <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
    </div>

    <div class="print-canvas card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-leave); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars((string)$cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-airplane-takeoff" style="font-size:3.5rem; color:var(--c-leave);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars((string)$cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= $t['sub'] ?></span>
                </div>
            </div>
            <div style="text-align:<?= $isAr ? 'left' : 'right' ?>;">
                <span style="background:var(--c-leave); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;"><?= $t['official_badge'] ?></span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-leave-dark); font-size:1.1rem;"># LVE-<?= htmlspecialchars((string)$leave->id) ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label"><?= $t['emp_lbl'] ?></span>
                <span class="info-val"><?= $empName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['code_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace;"><?= $empCode ?> | <?= $deptName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['branch_lbl'] ?></span>
                <span class="info-val"><?= $branchName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['type_lbl'] ?></span>
                <span class="info-val"><?= $typeMap[$leave->leave_type] ?? $leave->leave_type ?></span>
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
                <span class="info-label"><?= $t['days_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace; color:var(--c-leave-dark); font-size:1.1rem;"><?= $daysCount ?> <?= $t['days_unit'] ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['status_lbl'] ?></span>
                <span class="info-val"><span style="padding:2px 10px; border-radius:4px; font-size:0.85rem; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>; font-weight:900;"><?= $st['label'] ?></span></span>
            </div>
        </div>

        <?php if(!empty($reason)): ?>
            <div style="margin-top:24px; padding:16px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0;">
                <h5 style="margin:0 0 6px 0; font-size:0.85rem; color:var(--c-text-muted); font-weight:800;"><?= $t['reason_lbl'] ?></h5>
                <p style="margin:0; font-size:0.9rem; color:#334155; font-weight:700; white-space:pre-line;"><?= $reason ?></p>
            </div>
        <?php endif; ?>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6><?= $t['sig_emp'] ?></h6>
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