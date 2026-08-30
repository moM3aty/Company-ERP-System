<?php
// Path: resources/views/accounting/cost_centers/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';
?>

<style>
    :root { --c-cc: #7c3aed; --c-cc-dark: #6d28d9; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .cc-show-wrapper { max-width: 1000px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .print-only-header { display: none; }

    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .info-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; border-top: 4px solid var(--c-cc); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
    @media(max-width:768px){ .grid-4 { grid-template-columns: repeat(2, 1fr); } }
    .info-item h5 { margin: 0 0 6px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .info-item p { margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--c-text-dark); }

    .sub-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .sub-table th { padding: 12px 16px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; border-bottom: 2px solid #cbd5e1; text-align: start; }
    .sub-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .cc-show-wrapper { max-width: 100% !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .info-card { border: 1px solid #000 !important; box-shadow: none !important; }
        .print-only-header { display: flex !important; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--c-cc); padding-bottom: 15px; margin-bottom: 20px; }
    }
</style>

<div class="cc-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <div class="print-only-header">
        <div style="display:flex; align-items:center; gap:15px;">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:50px;">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:2.5rem; color:var(--c-cc);"></i>
            <?php endif; ?>
            <h2 style="margin:0; font-size:1.5rem; color:#000; font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
        </div>
        <div style="text-align:end; font-size:0.85rem; font-weight:bold;">
            بطاقة مركز التكلفة المالي<br>
            تاريخ الطباعة: <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/accounting/cost-centers" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($center->name_ar) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-cc-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($center->code) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="/ERP/accounting/cost-centers/<?= $center->id ?>/report" class="btn-print" style="background:var(--c-cc); text-decoration:none;"><i class="ph-bold ph-chart-line-up"></i> التقرير التحليلي</a>
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة البطاقة</button>
            <a href="/ERP/accounting/cost-centers/<?= $center->id ?>/edit" class="btn-print" style="background:#0f172a; text-decoration:none;"><i class="ph-bold ph-pencil"></i> تعديل</a>
        </div>
    </div>

    <div class="info-card">
        <div class="grid-4">
            <div class="info-item">
                <h5>كود مركز التكلفة</h5>
                <p style="font-family:monospace; color:var(--c-cc-dark);"><?= htmlspecialchars($center->code) ?></p>
            </div>
            <div class="info-item">
                <h5>الدرجة الهيكلية</h5>
                <p><?= !empty($center->is_parent) ? 'مركز رئيسي (Parent)' : 'مركز فرعي (Sub)' ?></p>
            </div>
            <div class="info-item">
                <h5>المركز الأب المباشر</h5>
                <p><?= htmlspecialchars($center->parent_name ?? 'مركز رئيسي رئيسي') ?></p>
            </div>
            <div class="info-item">
                <h5>الموازنة التقديرية</h5>
                <p style="font-family:monospace;"><?= number_format((float)($center->budget_amount ?? 0), 2) ?></p>
            </div>
        </div>
    </div>

    <?php if(!empty($center->is_parent)): ?>
        <div class="info-card">
            <h3 style="margin:0 0 16px 0; font-size:1.1rem; font-weight:800; color:var(--c-text-dark);"><i class="ph-bold ph-tree-structure" style="color:var(--c-cc);"></i> المراكز الفرعية التابعة بهذا المركز</h3>
            <table class="sub-table">
                <thead>
                    <tr>
                        <th>الكود</th>
                        <th>اسم المركز الفرعي</th>
                        <th style="text-align:center;">الحالة</th>
                        <th style="text-align:center;">عرض</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($subCenters)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px; color:#94a3b8;">لا توجد مراكز فرعية مسجلة تحت هذا المركز الرئيسي.</td></tr>
                    <?php else: foreach($subCenters as $sub): ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:var(--c-cc-dark);"><?= htmlspecialchars($sub->code) ?></td>
                            <td style="font-weight:800; color:var(--c-text-dark);"><?= htmlspecialchars($sub->name_ar) ?></td>
                            <td style="text-align:center;">
                                <span style="padding:2px 8px; border-radius:4px; font-size:0.75rem; font-weight:800; <?= $sub->is_active ? 'background:#ecfdf5; color:#059669;' : 'background:#fef2f2; color:#dc2626;' ?>">
                                    <?= $sub->is_active ? 'نشط' : 'معطل' ?>
                                </span>
                            </td>
                            <td style="text-align:center;"><a href="/ERP/accounting/cost-centers/<?= $sub->id ?>" style="color:var(--c-cc);"><i class="ph-bold ph-eye"></i></a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>