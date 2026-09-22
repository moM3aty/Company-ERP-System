<?php
// Path: resources/views/hr/departments/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

if (!isset($department) || !$department) {
    header("Location: /ERP/hr/departments");
    exit;
}

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$code        = htmlspecialchars((string)($department->code ?? '---'));
$deptNameAr  = (string)($department->name_ar ?? '');
$deptNameEn  = (string)($department->name_en ?? '');
$deptName    = $isAr ? $deptNameAr : ($deptNameEn ?: $deptNameAr);
$parentName  = htmlspecialchars((string)($isAr ? ($department->parent_name_ar ?? 'إدارة رئيسية مستقلة') : ($department->parent_name_en ?: ($department->parent_name_ar ?? 'Independent Main Division'))));
$managerName = htmlspecialchars((string)($department->manager_name ?: ($isAr ? 'غير عين' : 'Unassigned')));
$description = htmlspecialchars((string)($department->description ?? ''));

$statusMap = [
    'active'   => ['label' => $isAr ? 'نشط ومفعل' : 'Active', 'color' => '#0d9488', 'bg' => '#ccfbf1'],
    'inactive' => ['label' => $isAr ? 'غير نشط' : 'Inactive', 'color' => '#64748b', 'bg' => '#f1f5f9'],
];
$st = $statusMap[$department->status ?? 'active'] ?? $statusMap['active'];

$t = [
    'ar' => [
        'print' => 'طباعة الوثيقة التنظيمية',
        'edit' => 'تعديل',
        'title' => 'بطاقة التعريف بالإدارة والهيكل التنظيمي',
        'sub' => 'إدارة الموارد البشرية - الهيكل الإداري',
        'official_badge' => 'إدارة رسمية',
        'code_lbl' => 'كود الإدارة:',
        'name_ar_lbl' => 'اسم الإدارة (بالعربية):',
        'name_en_lbl' => 'اسم الإدارة (بالإنجليزية):',
        'parent_lbl' => 'القطاع / الإدارة العليا:',
        'manager_lbl' => 'مدير الإدارة المسؤول:',
        'status_lbl' => 'حالة الإدارة:',
        'desc_lbl' => 'توصيف المهام والاختصاصات:',
        'no_desc' => 'لا يوجد توصيف مسجل للإدارة.',
        'sig_manager' => 'مدير الإدارة',
        'sig_hr' => 'مدير الموارد البشرية',
        'sig_sub' => 'التوقيع والاعتماد الرسميين'
    ],
    'en' => [
        'print' => 'Print Org Document',
        'edit' => 'Edit',
        'title' => 'Department Identification & Structure Card',
        'sub' => 'HR Department - Organizational Structure',
        'official_badge' => 'Official Division',
        'code_lbl' => 'Department Code:',
        'name_ar_lbl' => 'Department Name (Arabic):',
        'name_en_lbl' => 'Department Name (English):',
        'parent_lbl' => 'Parent Division / Sector:',
        'manager_lbl' => 'Department Manager:',
        'status_lbl' => 'Status:',
        'desc_lbl' => 'Responsibilities & Scope of Work:',
        'no_desc' => 'No description recorded.',
        'sig_manager' => 'Department Head',
        'sig_hr' => 'HR Director',
        'sig_sub' => 'Official Signature & Stamp'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-dept: #0d9488; --c-dept-dark: #0f766e; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .dept-show-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition: 0.2s; }
    .btn-action:hover { background: #ccfbf1; color: var(--c-dept-dark); border-color: #99f6e4; }
    .btn-print { background: var(--c-dept-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #115e59; }

    .card-box { background: #ffffff; border: 2px solid var(--c-dept); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }

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
        .dept-show-wrapper, .dept-show-wrapper * { visibility: visible !important; }
        .dept-show-wrapper { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; }
        .header-bar { display: none !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; padding: 20px !important; }
        .dataTables_wrapper, .table-pagination-nav { display: none !important; visibility: hidden !important; }
    }
</style>

<div class="dept-show-wrapper" dir="<?= $dir ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/departments" class="btn-action"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($deptName) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-dept-dark); font-weight:800; font-family:monospace;"><?= $code ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="/ERP/hr/departments/<?= (int)$department->id ?>/edit" class="btn-action" style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1;"><i class="ph-bold ph-pencil-simple"></i></a>
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="print-canvas card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-dept); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars((string)$cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-sitemap" style="font-size:3.5rem; color:var(--c-dept);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars((string)$cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= $t['sub'] ?></span>
                </div>
            </div>
            <div style="text-align:<?= $isAr ? 'left' : 'right' ?>;">
                <span style="background:var(--c-dept); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;"><?= $t['official_badge'] ?></span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-dept-dark); font-size:1.1rem;"># <?= $code ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label"><?= $t['name_ar_lbl'] ?></span>
                <span class="info-val"><?= htmlspecialchars((string)$deptNameAr) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['name_en_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace;"><?= htmlspecialchars((string)($deptNameEn ?: '---')) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['parent_lbl'] ?></span>
                <span class="info-val"><?= $parentName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['manager_lbl'] ?></span>
                <span class="info-val"><?= $managerName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['status_lbl'] ?></span>
                <span class="info-val"><span style="padding:2px 10px; border-radius:4px; font-size:0.85rem; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>; font-weight:900;"><?= $st['label'] ?></span></span>
            </div>
        </div>

        <?php if(!empty($description)): ?>
            <div style="margin-top:24px; padding:20px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0;">
                <h5 style="margin:0 0 10px 0; font-size:0.9rem; color:var(--c-text-dark); font-weight:800;"><?= $t['desc_lbl'] ?></h5>
                <p style="margin:0; font-size:0.88rem; color:#334155; line-height:1.7; white-space:pre-line;"><?= $description ?></p>
            </div>
        <?php endif; ?>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6><?= $t['sig_manager'] ?></h6>
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