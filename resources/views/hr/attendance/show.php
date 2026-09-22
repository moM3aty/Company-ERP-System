<?php
// Path: resources/views/hr/attendance/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

if (!isset($attendance) || !$attendance) {
    header("Location: /ERP/hr/attendance");
    exit;
}

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$empName = htmlspecialchars((string)($isAr ? ($attendance->employee_name ?? 'مجهول') : ($attendance->employee_name_en ?: ($attendance->employee_name ?? 'Unknown'))));
$empCode = htmlspecialchars((string)($attendance->emp_code ?? '---'));
$dateRec = htmlspecialchars((string)($attendance->date ?? '---'));
$branchName = htmlspecialchars((string)($attendance->branch_name ?? ($isAr ? 'عام' : 'General')));

$t = [
    'ar' => [
        'print' => 'طباعة السجل',
        'title' => 'تقرير سجل بصمة يومي',
        'sub' => 'إدارة الموارد البشرية - إدارة الحضور والانصراف',
        'official_badge' => 'بصمة موثقة',
        'emp_lbl' => 'اسم الموظف:',
        'branch_lbl' => 'الفرع:',
        'date_lbl' => 'التاريخ واليوم:',
        'in_lbl' => 'وقت الحضور (الدخول):',
        'out_lbl' => 'وقت الانصراف (الخروج):',
        'hours_lbl' => 'إجمالي ساعات العمل:',
        'status_lbl' => 'حالة الدوام:',
        'notes_lbl' => 'ملاحظات السجل:',
        'hours_unit' => 'ساعة',
        'no_time' => '---',
        'status_present' => 'حاضر',
        'status_late' => 'متأخر',
        'status_half' => 'نصف يوم',
        'status_absent' => 'غائب',
        'status_leave' => 'إجازة رسمية'
    ],
    'en' => [
        'print' => 'Print Record',
        'title' => 'Daily Attendance Record',
        'sub' => 'HR Department - Time & Attendance',
        'official_badge' => 'Verified Record',
        'emp_lbl' => 'Employee Name:',
        'branch_lbl' => 'Branch:',
        'date_lbl' => 'Record Date:',
        'in_lbl' => 'Check-In Time:',
        'out_lbl' => 'Check-Out Time:',
        'hours_lbl' => 'Total Work Hours:',
        'status_lbl' => 'Attendance Status:',
        'notes_lbl' => 'Record Notes:',
        'hours_unit' => 'Hrs',
        'no_time' => '---',
        'status_present' => 'Present',
        'status_late' => 'Late',
        'status_half' => 'Half Day',
        'status_absent' => 'Absent',
        'status_leave' => 'On Leave'
    ]
][$isAr ? 'ar' : 'en'];

$statusMap = [
    'present'  => ['label' => $t['status_present'], 'color' => '#059669', 'bg' => '#ecfdf5'],
    'late'     => ['label' => $t['status_late'], 'color' => '#d97706', 'bg' => '#fef3c7'],
    'half_day' => ['label' => $t['status_half'], 'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
    'absent'   => ['label' => $t['status_absent'], 'color' => '#dc2626', 'bg' => '#fef2f2'],
    'on_leave' => ['label' => $t['status_leave'], 'color' => '#0284c7', 'bg' => '#e0f2fe'],
];
$st = $statusMap[$attendance->status] ?? $statusMap['present'];
?>

<style>
    :root { --c-att: #0284c7; --c-att-dark: #0369a1; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .att-show-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition:0.2s;}
    .btn-action:hover { background: #e0f2fe; color: var(--c-att-dark); border-color: #bae6fd; }
    .btn-print { background: var(--c-att-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition:0.2s;}
    .btn-print:hover { background: #0c4a6e; }

    .card-box { background: #ffffff; border: 2px solid var(--c-att); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }
    
    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 160px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .att-show-wrapper { max-width: 100% !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; padding: 20px !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .dataTables_wrapper { display: none !important; }
    }
</style>

<div class="att-show-wrapper" dir="<?= $dir ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/attendance" class="btn-action"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-att-dark); font-weight:800; font-family:monospace;"><?= $dateRec ?></p>
            </div>
        </div>
        <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
    </div>

    <div class="print-canvas card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-att); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars((string)$cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-fingerprint" style="font-size:3.5rem; color:var(--c-att);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars((string)$cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= $t['sub'] ?></span>
                </div>
            </div>
            <div style="text-align:<?= $isAr ? 'left' : 'right' ?>;">
                <span style="background:var(--c-att); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;"><?= $t['official_badge'] ?></span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-att-dark); font-size:1.1rem;"># <?= $empCode ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label"><?= $t['emp_lbl'] ?></span>
                <span class="info-val"><?= $empName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['branch_lbl'] ?></span>
                <span class="info-val"><?= $branchName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['date_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace;"><?= $dateRec ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['in_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace; color:#059669;"><?= $attendance->check_in ? date('h:i A', strtotime($attendance->check_in)) : $t['no_time'] ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['out_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace; color:#dc2626;"><?= $attendance->check_out ? date('h:i A', strtotime($attendance->check_out)) : $t['no_time'] ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['hours_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace; color:var(--c-text-dark); font-size:1.1rem;"><?= number_format((float)$attendance->work_hours, 2) ?> <?= $t['hours_unit'] ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['status_lbl'] ?></span>
                <span class="info-val"><span style="padding:2px 10px; border-radius:4px; font-size:0.85rem; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>; font-weight:bold;"><?= $st['label'] ?></span></span>
            </div>
        </div>

        <?php if(!empty($attendance->notes)): ?>
            <div style="margin-top:24px; padding:16px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0;">
                <h5 style="margin:0 0 6px 0; font-size:0.85rem; color:var(--c-text-muted); font-weight:800;"><?= $t['notes_lbl'] ?></h5>
                <p style="margin:0; font-size:0.9rem; color:#334155; font-weight:700; white-space:pre-line;"><?= htmlspecialchars((string)$attendance->notes) ?></p>
            </div>
        <?php endif; ?>
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
        '.dataTables_length',
        '.dataTables_wrapper'
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