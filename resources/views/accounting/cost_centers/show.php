<?php
// Path: resources/views/accounting/cost_centers/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$t = [
    'ar' => [
        'back' => 'رجوع للقائمة', 'print' => 'طباعة البطاقة', 'edit' => 'تعديل', 'report' => 'التقرير التحليلي',
        'subtitle' => 'بطاقة مركز تكلفة (Cost Center Profile)', 'code' => 'كود المركز', 'type' => 'الدرجة الهيكلية',
        'parent' => 'المركز الأب', 'budget' => 'الموازنة التقديرية', 'branch' => 'الفرع / الموقع',
        'sub_title' => 'المراكز الفرعية التابعة', 'col_code' => 'الكود', 'col_name' => 'اسم المركز الفرعي', 'col_status' => 'الحالة', 'col_view' => 'عرض',
        'empty' => 'لا توجد مراكز فرعية مسجلة تحت هذا المركز.',
        'root' => 'مركز رئيسي (جذر)', 'general' => 'عام (الشركة)',
        'type_parent' => 'رئيسي (Parent)', 'type_sub' => 'فرعي (Sub)',
        'st_active' => 'نشط', 'st_inactive' => 'معطل', 'print_date' => 'تاريخ الاستخراج:'
    ],
    'en' => [
        'back' => 'Back to List', 'print' => 'Print Profile', 'edit' => 'Edit', 'report' => 'Analytics Report',
        'subtitle' => 'Cost Center Profile', 'code' => 'Center Code', 'type' => 'Structural Level',
        'parent' => 'Parent Center', 'budget' => 'Allocated Budget', 'branch' => 'Branch / Site',
        'sub_title' => 'Dependent Sub-Centers', 'col_code' => 'Code', 'col_name' => 'Sub-Center Name', 'col_status' => 'Status', 'col_view' => 'View',
        'empty' => 'No sub-centers registered under this center.',
        'root' => 'Root Center', 'general' => 'General (Company Level)',
        'type_parent' => 'Parent', 'type_sub' => 'Sub-Center',
        'st_active' => 'Active', 'st_inactive' => 'Inactive', 'print_date' => 'Report Date:'
    ]
][$isRtl ? 'ar' : 'en'];

$cNameVal = $isRtl ? ($center->name_ar ?: $center->name_en) : ($center->name_en ?: $center->name_ar);
$pNameVal = $isRtl ? ($center->parent_name ?: ($center->parent_code ?? $t['root'])) : (($center->parent_code ?? $t['root']));
$branchBadge = !empty($center->branch_name) ? ($isRtl ? $center->branch_name : ($center->branch_name_en ?: $center->branch_name)) : $t['general'];
?>

<style>
    :root { 
        --brand-primary: #7c3aed; --brand-primary-dark: #6d28d9; --brand-primary-light: #f5f3ff; 
        --surface: #ffffff; --surface-hover: #f8fafc; --bg-body: #f1f5f9;
        --text-main: #0f172a; --text-muted: #64748b; --border-color: #e2e8f0;
        --radius-lg: 24px; --radius-md: 16px;
        --shadow-soft: 0 10px 30px -10px rgba(15, 23, 42, 0.08);
        --shadow-hover: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
    }
    .cc-show-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: var(--bg-body);}
    
    .print-only-header { display: none; }

    .header-bar { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); padding: 24px 30px; margin-bottom: 30px; border-bottom: 1px solid rgba(226,232,240,0.8); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; position: sticky; top:0; z-index:10;}
    .btn-action { width: 48px; height: 48px; border-radius: 14px; background: var(--surface); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--text-muted); font-size: 1.3rem; transition: 0.3s; box-shadow: var(--shadow-soft);}
    .btn-action:hover { background: var(--brand-primary-light); color: var(--brand-primary); border-color: #ddd6fe; transform: translateX(<?= $isRtl ? '4px' : '-4px' ?>);}
    .btn-print { background: var(--text-main); color: #ffffff; border: none; padding: 14px 26px; border-radius: 12px; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; font-size: 0.95rem; box-shadow: var(--shadow-soft);}
    .btn-print:hover { background: #000000; transform: translateY(-2px); box-shadow: var(--shadow-hover);}

    .info-box { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 32px; border-top: 5px solid var(--brand-primary); margin: 0 30px 30px 30px; box-shadow: var(--shadow-soft); transition: 0.4s;}
    .info-box:hover { box-shadow: var(--shadow-hover); transform: translateY(-3px);}
    .info-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 24px; }
    @media (max-width: 1100px) { .info-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 768px) { .info-grid { grid-template-columns: repeat(2, 1fr); } }
    .info-item { background: var(--surface-hover); padding: 20px; border-radius: 16px; border: 1px solid #f1f5f9; }
    .info-item h5 { margin: 0 0 8px 0; font-size: 0.8rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase; }
    .info-item p { margin: 0; font-size: 1.25rem; font-weight: 900; color: var(--text-main); font-family:monospace;}

    .table-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; margin: 0 30px 30px 30px; box-shadow: var(--shadow-soft); }
    .sub-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; text-align: start; }
    .sub-table th { padding: 18px 24px; background: var(--surface-hover); color: var(--text-muted); font-weight: 900; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; }
    .sub-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; font-weight:700;}
    .sub-table tr:hover td { background: var(--surface-hover); }

    /* ========================================================
       BULLETPROOF PRINT STYLES - تنسيقات الطباعة الخارقة (A4)
       ======================================================== */
    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        
        body * { visibility: hidden !important; }
        .cc-show-wrapper, .cc-show-wrapper * { visibility: visible !important; }
        
        .cc-show-wrapper {
            position: absolute !important;
            left: 0 !important; top: 0 !important;
            width: 100% !important; max-width: 100% !important;
            margin: 0 !important; padding: 0 !important;
            background-color: #ffffff !important;
        }

        .header-bar, .btn-action, .btn-print, .table-pagination-nav { display: none !important; }
        
        .print-only-header { 
            display: flex !important; align-items: center !important; justify-content: space-between !important; 
            border-bottom: 3px solid #000 !important; padding-bottom: 12px !important; margin-bottom: 20px !important; 
        }
        .print-logo-box { display: flex !important; align-items: center !important; gap: 15px !important; }
        .print-logo-box img { max-height: 50px !important; }
        .print-logo-box h2 { margin: 0 !important; font-size: 1.6rem !important; color: #000 !important; font-weight: 900 !important; }
        .print-meta { text-align: end !important; font-size: 0.9rem !important; font-weight: bold !important; color: #000 !important; }
        
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        
        .info-box { 
            border: 2px solid #000 !important; box-shadow: none !important; border-radius: 6px !important; 
            margin: 0 0 20px 0 !important; padding: 15px !important; page-break-inside: avoid;
        }
        .info-grid { display: grid !important; grid-template-columns: repeat(5, 1fr) !important; gap: 10px !important; }
        .info-item { background: transparent !important; border: 1px solid #000 !important; padding: 10px !important; border-radius: 4px !important;}
        
        .table-card { border: 2px solid #000 !important; box-shadow: none !important; margin: 0 !important; border-radius: 6px !important; page-break-inside: auto;}
        .table-title-print { background: #e2e8f0 !important; color: #000 !important; border-bottom: 2px solid #000 !important;}
        .sub-table { border-collapse: collapse !important; width: 100% !important; }
        .sub-table th { background: #f1f5f9 !important; color: #000 !important; border-bottom: 2px solid #000 !important; border-left: 1px solid #000 !important; border-right: 1px solid #000 !important; font-weight: bold !important; font-size: 9pt !important; padding: 8px !important;}
        .sub-table td { border: 1px solid #000 !important; color: #000 !important; padding: 8px !important; font-size: 9pt !important;}
        
        a { text-decoration: none !important; color: #000 !important; }
    }
</style>

<div class="cc-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <!-- 📄 ترويسة الطباعة (تظهر فقط عند الطباعة) -->
    <div class="print-only-header">
        <div class="print-logo-box">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:2.8rem; color:#000;"></i>
            <?php endif; ?>
            <div>
                <h2><?= htmlspecialchars($cName) ?></h2>
                <span style="font-size:1.05rem; color:#475569; font-weight:900;"><?= $t['subtitle'] ?></span>
            </div>
        </div>
        <div class="print-meta">
            <?= htmlspecialchars($cNameVal) ?> (<?= htmlspecialchars($center->code) ?>)<br>
            <?= $t['print_date'] ?> <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <!-- رأس الصفحة الطبيعي -->
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:24px;">
            <a href="/ERP/accounting/cost-centers" class="btn-action" title="<?= $t['back'] ?>"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.8rem; color:var(--text-main); font-weight:900;"><?= htmlspecialchars($cNameVal) ?></h2>
                <p style="margin:6px 0 0 0; color:var(--brand-primary-dark); font-weight:900; font-family:monospace; font-size:1.15rem;"><?= htmlspecialchars($center->code) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:12px;">
            <a href="/ERP/accounting/cost-centers/<?= $center->id ?>/report" class="btn-print" style="background:var(--brand-primary); text-decoration:none;"><i class="ph-bold ph-chart-line-up"></i> <?= $t['report'] ?></a>
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
            <a href="/ERP/accounting/cost-centers/<?= $center->id ?>/edit" class="btn-print" style="background:#0f172a; text-decoration:none;"><i class="ph-bold ph-pencil"></i> <?= $t['edit'] ?></a>
        </div>
    </div>

    <!-- بطاقة المعلومات -->
    <div class="info-box">
        <div class="info-grid">
            <div class="info-item">
                <h5><?= $t['code'] ?></h5>
                <p style="font-family:monospace; color:var(--brand-primary-dark); font-size:1.4rem;"><?= htmlspecialchars($center->code) ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['type'] ?></h5>
                <p style="color:var(--brand-primary);"><?= !empty($center->is_parent) ? $t['type_parent'] : $t['type_sub'] ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['parent'] ?></h5>
                <p style="color:#475569;"><?= htmlspecialchars($pNameVal) ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['branch'] ?></h5>
                <p style="color:#0ea5e9;"><i class="ph-bold ph-git-branch"></i> <?= htmlspecialchars($branchBadge) ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['budget'] ?></h5>
                <p style="font-family:monospace; font-size:1.4rem; color:var(--text-main);"><?= number_format((float)($center->budget_amount ?? 0), 2) ?></p>
            </div>
        </div>
    </div>

    <?php if(!empty($center->is_parent)): ?>
        <!-- جدول المراكز الفرعية -->
        <div class="table-card">
            <div class="table-title-print" style="padding:22px 30px; background:var(--surface-hover); border-bottom:1px solid #e2e8f0; font-weight:900; color:var(--text-main); font-size:1.1rem;">
                <i class="ph-bold ph-tree-structure" style="color:var(--brand-primary); font-size:1.4rem; vertical-align:middle; margin-inline-end:8px;"></i> <?= $t['sub_title'] ?>
            </div>
            <div style="overflow-x:auto;">
                <table class="sub-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;"><?= $t['col_code'] ?></th>
                            <th style="width: 50%;"><?= $t['col_name'] ?></th>
                            <th style="width: 15%; text-align:center;"><?= $t['col_status'] ?></th>
                            <th style="width: 15%; text-align:center;"><?= $t['col_view'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($subCenters)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:50px; color:#94a3b8; font-weight:800; font-size:1rem; border-bottom:none;"><?= $t['empty'] ?></td></tr>
                        <?php else: foreach($subCenters as $sub): 
                            $sName = $isRtl ? ($sub->name_ar ?: $sub->name_en) : ($sub->name_en ?: $sub->name_ar);
                        ?>
                            <tr>
                                <td style="font-family:monospace; font-weight:900; color:var(--brand-primary-dark); font-size:1.05rem;"><?= htmlspecialchars($sub->code) ?></td>
                                <td style="font-weight:800; color:var(--text-main); font-size:1rem;"><?= htmlspecialchars($sName) ?></td>
                                <td style="text-align:center;">
                                    <?php if($sub->is_active): ?>
                                        <span style="background:#d1fae5; color:#059669; padding:4px 10px; border-radius:6px; font-weight:900; font-size:0.75rem; border:1px solid #059669;"><?= $t['st_active'] ?></span>
                                    <?php else: ?>
                                        <span style="background:#ffe4e6; color:#e11d48; padding:4px 10px; border-radius:6px; font-weight:900; font-size:0.75rem; border:1px solid #e11d48;"><?= $t['st_inactive'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center;">
                                    <a href="/ERP/accounting/cost-centers/<?= $sub->id ?>" style="color:var(--brand-primary); font-size:1.3rem;"><i class="ph-bold ph-eye"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function purgeControlsForPrint() {
    const selectors = ['.table-pagination-nav', '.dataTables_info', '.dataTables_paginate', '.pagination'];
    selectors.forEach(s => {
        document.querySelectorAll(s).forEach(el => {
            if(el) el.style.display = 'none';
        });
    });
}
function safePrint() {
    purgeControlsForPrint();
    window.print();
}
window.addEventListener("beforeprint", purgeControlsForPrint);
</script>