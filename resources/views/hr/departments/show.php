<?php
// Path: resources/views/hr/departments/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$statusMap = [
    'active' => ['label' => 'نشط ومفعل', 'color' => '#0d9488', 'bg' => '#ccfbf1'],
    'inactive' => ['label' => 'غير نشط', 'color' => '#64748b', 'bg' => '#f1f5f9'],
];
$st = $statusMap[$department->status] ?? $statusMap['active'];
?>

<style>
    :root { --c-dept: #0d9488; --c-dept-dark: #0f766e; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .dept-show-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-dept-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .card-box { background: #ffffff; border: 2px solid var(--c-dept); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    @media(max-width:768px){ .grid-2 { grid-template-columns: 1fr; } }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 160px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .dept-show-wrapper { max-width: 100% !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="dept-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/departments" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($department->name_ar) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-dept-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($department->code) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة الوثيقة التنظيمية</button>
            <a href="/ERP/hr/departments/<?= $department->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="تعديل"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <div class="card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-dept); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-sitemap" style="font-size:3rem; color:var(--c-dept);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;">بطاقة التعريف بالإدارة والهيكل التنظيمي</span>
                </div>
            </div>
            <div style="text-align:end;">
                <span style="background:var(--c-dept); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;">إدارة رسمية</span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-dept-dark); font-size:1.1rem;"># <?= htmlspecialchars($department->code) ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label">اسم الإدارة (بالعربية):</span>
                <span class="info-val"><?= htmlspecialchars($department->name_ar) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">اسم الإدارة (بالإنجليزية):</span>
                <span class="info-val" style="font-family:monospace;"><?= htmlspecialchars($department->name_en ?: '---') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">القطاع / الإدارة العليا:</span>
                <span class="info-val"><?= htmlspecialchars($department->parent_name_ar ?: 'إدارة رئيسية مستقلة') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">مدير الإدارة المسؤول:</span>
                <span class="info-val"><?= htmlspecialchars($department->manager_name ?: 'غير عين') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">حالة الإدارة:</span>
                <span class="info-val"><span style="padding:2px 10px; border-radius:4px; font-size:0.85rem; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;"><?= $st['label'] ?></span></span>
            </div>
        </div>

        <?php if(!empty($department->description)): ?>
            <div style="margin-top:24px; padding:20px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0;">
                <h5 style="margin:0 0 10px 0; font-size:0.9rem; color:var(--c-text-dark); font-weight:800;">توصيف المهام والاختصاصات:</h5>
                <p style="margin:0; font-size:0.88rem; color:#334155; line-height:1.7; white-space:pre-line;"><?= htmlspecialchars($department->description) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>