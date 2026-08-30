<?php
// Path: resources/views/hr/salary_components/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$typeMap = [
    'allowance' => ['label' => 'بدل / إضافي', 'color' => '#059669', 'bg' => '#ecfdf5'],
    'deduction' => ['label' => 'خصم / استقطاع', 'color' => '#dc2626', 'bg' => '#fef2f2'],
];
$tp = $typeMap[$component->type] ?? $typeMap['allowance'];
?>

<style>
    :root { --c-comp: #4338ca; --c-comp-dark: #312e81; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .comp-show-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-comp-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .card-box { background: #ffffff; border: 2px solid var(--c-comp); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 160px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .comp-show-wrapper { max-width: 100% !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="comp-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/salary-components" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($component->name_ar) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-comp-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($component->employee_name) ?></p>
            </div>
        </div>
        <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة الوثيقة</button>
    </div>

    <div class="card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-comp); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-sliders-horizontal" style="font-size:3.5rem; color:var(--c-comp);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;">إدارة الموارد البشرية - شؤون الأجور والبدلات</span>
                </div>
            </div>
            <div style="text-align:end;">
                <span style="background:var(--c-comp); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;">عنصر راتب معتمد</span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-comp-dark); font-size:1.1rem;"># CMP-<?= htmlspecialchars($component->id) ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label">الموظف المستفيد:</span>
                <span class="info-val"><?= htmlspecialchars($component->employee_name) ?> (<?= htmlspecialchars($component->emp_code) ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label">الإدارة التابع لها:</span>
                <span class="info-val"><?= htmlspecialchars($component->dept_name ?: 'عام') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">اسم البدل / المفرد:</span>
                <span class="info-val"><?= htmlspecialchars($component->name_ar) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">نوع المفرد:</span>
                <span class="info-val"><span style="padding:2px 10px; border-radius:4px; font-size:0.85rem; background:<?= $tp['bg'] ?>; color:<?= $tp['color'] ?>;"><?= $tp['label'] ?></span></span>
            </div>
            <div class="info-row">
                <span class="info-label">المبلغ المقدر:</span>
                <span class="info-val" style="font-family:monospace; color:<?= $component->type==='allowance'?'#059669':'#dc2626' ?>; font-size:1.1rem;"><?= number_format((float)$component->amount, 2) ?> SAR/EGP</span>
            </div>
            <div class="info-row">
                <span class="info-label">طبيعة العنصر:</span>
                <span class="info-val"><?= $component->is_fixed ? 'عنصر ثابت يدرج شهرياً تلقائياً' : 'عنصر متغير / مؤقت' ?></span>
            </div>
        </div>
    </div>
</div>