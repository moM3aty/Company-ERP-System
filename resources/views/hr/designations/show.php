<?php
// Path: resources/views/hr/designations/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

if (!isset($designation) || !$designation) {
    header("Location: /ERP/hr/designations");
    exit;
}

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$code        = htmlspecialchars((string)($designation->code ?? '---'));
$titleAr     = (string)($designation->title_ar ?? '');
$titleEn     = (string)($designation->title_en ?? '');
$titleName   = $isAr ? $titleAr : ($titleEn ?: $titleAr);
$deptName    = htmlspecialchars((string)($isAr ? ($designation->department_name_ar ?? 'عمومي / كل الإدارات') : ($designation->department_name_en ?: ($designation->department_name_ar ?? 'General / All Departments'))));
$payGrade    = htmlspecialchars((string)($designation->pay_grade ?: ($isAr ? 'عام' : 'General')));
$description = htmlspecialchars((string)($designation->description ?? ''));

$statusMap = [
    'active'   => ['label' => $isAr ? 'نشط ومفعل' : 'Active', 'color' => '#c026d3', 'bg' => '#fae8ff'],
    'inactive' => ['label' => $isAr ? 'غير نشط' : 'Inactive', 'color' => '#64748b', 'bg' => '#f1f5f9'],
];
$st = $statusMap[$designation->status ?? 'active'] ?? $statusMap['active'];

$t = [
    'ar' => [
        'print' => 'طباعة الوصف الوظيفي',
        'edit' => 'تعديل',
        'title' => 'بطاقة وتوصيف المسمى الوظيفي',
        'sub' => 'إدارة الموارد البشرية - السلم الوظيفي',
        'official_badge' => 'مسمى معتمد',
        'code_lbl' => 'كود المسمى:',
        'title_ar_lbl' => 'المسمى الوظيفي (بالعربية):',
        'title_en_lbl' => 'المسمى الوظيفي (بالإنجليزية):',
        'dept_lbl' => 'الإدارة التابع لها:',
        'grade_lbl' => 'الدرجة المالية (Pay Grade):',
        'status_lbl' => 'حالة المسمى:',
        'desc_lbl' => 'الوصف الوظيفي والمسؤوليات:',
        'sig_hr' => 'مدير الموارد البشرية',
        'sig_manager' => 'اعتماد رئيس القسم',
        'sig_sub' => 'التوقيع والاعتماد الرسميين'
    ],
    'en' => [
        'print' => 'Print Job Description',
        'edit' => 'Edit',
        'title' => 'Job Designation & Description Card',
        'sub' => 'HR Department - Job Hierarchy',
        'official_badge' => 'Approved Title',
        'code_lbl' => 'Designation Code:',
        'title_ar_lbl' => 'Job Title (Arabic):',
        'title_en_lbl' => 'Job Title (English):',
        'dept_lbl' => 'Department:',
        'grade_lbl' => 'Pay Grade:',
        'status_lbl' => 'Status:',
        'desc_lbl' => 'Job Description & Responsibilities:',
        'sig_hr' => 'HR Director',
        'sig_manager' => 'Department Head Approval',
        'sig_sub' => 'Official Signature & Stamp'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-desig: #c026d3; --c-desig-dark: #a21caf; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .desig-show-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition: 0.2s; }
    .btn-action:hover { background: #fae8ff; color: var(--c-desig-dark); border-color: #f5d0fe; }
    .btn-print { background: var(--c-desig-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #86198f; }

    .card-box { background: #ffffff; border: 2px solid var(--c-desig); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }

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
        .desig-show-wrapper, .desig-show-wrapper * { visibility: visible !important; }
        .desig-show-wrapper { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; }
        .header-bar { display: none !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; padding: 20px !important; }
        .dataTables_wrapper, .table-pagination-nav { display: none !important; visibility: hidden !important; }
    }
</style>

<div class="desig-show-wrapper" dir="<?= $dir ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/designations" class="btn-action"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($titleName) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-desig-dark); font-weight:800; font-family:monospace;"><?= $code ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="/ERP/hr/designations/<?= (int)$designation->id ?>/edit" class="btn-action" style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1;"><i class="ph-bold ph-pencil-simple"></i></a>
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="print-canvas card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-desig); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars((string)$cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-identification-card" style="font-size:3.5rem; color:var(--c-desig);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars((string)$cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= $t['sub'] ?></span>
                </div>
            </div>
            <div style="text-align:<?= $isAr ? 'left' : 'right' ?>;">
                <span style="background:var(--c-desig); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;"><?= $t['official_badge'] ?></span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-desig-dark); font-size:1.1rem;"># <?= $code ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label"><?= $t['title_ar_lbl'] ?></span>
                <span class="info-val"><?= htmlspecialchars((string)$titleAr) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['title_en_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace;"><?= htmlspecialchars((string)($titleEn ?: '---')) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['dept_lbl'] ?></span>
                <span class="info-val"><?= $deptName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['grade_lbl'] ?></span>
                <span class="info-val" style="font-family:monospace; color:#0284c7;"><?= $payGrade ?></span>
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