<?php
// Path: resources/views/hr/shifts/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

if (!isset($shift) || !$shift) {
    header("Location: /ERP/hr/shifts");
    exit;
}

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$shiftName = htmlspecialchars((string)($shift->name_ar ?? '---'));
$shiftCode = htmlspecialchars((string)($shift->code ?? '---'));
$startTime = date('h:i A', strtotime($shift->start_time ?? '00:00:00'));
$endTime   = date('h:i A', strtotime($shift->end_time ?? '00:00:00'));
$graceMins = (int)($shift->grace_period_mins ?? 0);

$statusMap = [
    'active'   => ['label' => $isAr ? 'وردية مفعلة' : 'Active Shift', 'color' => '#16a34a', 'bg' => '#dcfce7'],
    'inactive' => ['label' => $isAr ? 'وردية متوقفة' : 'Inactive Shift', 'color' => '#64748b', 'bg' => '#f1f5f9'],
];
$st = $statusMap[$shift->status ?? 'active'] ?? $statusMap['active'];

$t = [
    'ar' => [
        'print' => 'طباعة الوردية',
        'edit' => 'تعديل',
        'title' => 'بطاقة واعتماد مواعيد الوردية',
        'sub' => 'إدارة الموارد البشرية - شؤون الموظفين والتوقيت',
        'official_badge' => 'وقت معتمد',
        'name_lbl' => 'اسم الوردية:',
        'start_lbl' => 'وقت بداية الدوام:',
        'end_lbl' => 'وقت نهاية الدوام:',
        'grace_lbl' => 'فترة السماح (Grace Period):',
        'status_lbl' => 'حالة التفعيل:',
        'mins_unit' => 'دقيقة'
    ],
    'en' => [
        'print' => 'Print Shift Schedule',
        'edit' => 'Edit',
        'title' => 'Shift Schedule & Timing Card',
        'sub' => 'HR Department - Time & Attendance',
        'official_badge' => 'Approved Timing',
        'name_lbl' => 'Shift Name:',
        'start_lbl' => 'Shift Start Time:',
        'end_lbl' => 'Shift End Time:',
        'grace_lbl' => 'Grace Period (Lateness):',
        'status_lbl' => 'Status:',
        'mins_unit' => 'Mins'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-shift: #16a34a; --c-shift-dark: #15803d; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .shift-show-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition: 0.2s; }
    .btn-action:hover { background: #dcfce7; color: var(--c-shift-dark); border-color: #bbf7d0; }
    .btn-print { background: var(--c-shift-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #14532d; }

    .card-box { background: #ffffff; border: 2px solid var(--c-shift); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }
    
    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 160px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

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
        .shift-show-wrapper, .shift-show-wrapper * { visibility: visible !important; }
        .shift-show-wrapper { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; }
        .header-bar { display: none !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; padding: 20px !important; }
        .dataTables_wrapper, .table-pagination-nav { display: none !important; visibility: hidden !important; }
    }
</style>

<div class="shift-show-wrapper" dir="<?= $dir ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/shifts" class="btn-action"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= $shiftName ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-shift-dark); font-weight:800; font-family:monospace;"><?= $shiftCode ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="/ERP/hr/shifts/<?= (int)$shift->id ?>/edit" class="btn-action" style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1;"><i class="ph-bold ph-pencil-simple"></i></a>
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="print-canvas card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-shift); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars((string)$cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-clock-user" style="font-size:3.5rem; color:var(--c-shift);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars((string)$cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= $t['sub'] ?></span>
                </div>
            </div>
            <div style="text-align:<?= $isAr ? 'left' : 'right' ?>;">
                <span style="background:var(--c-shift); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;"><?= $t['official_badge'] ?></span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-shift-dark); font-size:1.1rem;"># <?= $shiftCode ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label"><?= $t['name_lbl'] ?></span>
                <span class="info-val"><?= $shiftName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['start_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace; color:#059669; font-size:1.1rem;"><?= $startTime ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['end_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace; color:#dc2626; font-size:1.1rem;"><?= $endTime ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['grace_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace; color:#d97706;"><?= $graceMins ?> <?= $t['mins_unit'] ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['status_lbl'] ?></span>
                <span class="info-val"><span style="padding:2px 10px; border-radius:4px; font-size:0.85rem; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>; font-weight:900;"><?= $st['label'] ?></span></span>
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