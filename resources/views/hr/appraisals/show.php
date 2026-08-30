<?php
// Path: resources/views/hr/appraisals/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$statusMap = [
    'draft' => ['label' => 'مسودة قيد التحرير', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'submitted' => ['label' => 'مرفوع للمراجعة', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
    'approved' => ['label' => 'مُعتمد رسمياً', 'color' => '#059669', 'bg' => '#ecfdf5'],
];
$st = $statusMap[$appraisal->status] ?? $statusMap['draft'];
?>

<style>
    :root { --c-appr: #ea580c; --c-appr-dark: #c2410c; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .appr-show-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-appr-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .card-box { background: #ffffff; border: 2px solid var(--c-appr); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 160px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    .score-banner { background: #ffedd5; border: 1px solid #fed7aa; border-radius: 12px; padding: 20px; margin-top: 24px; text-align: center; }

    .signatures-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 40px; margin-top: 48px; text-align: center; }
    .sig-box h6 { margin: 0 0 40px 0; font-size: 0.9rem; color: var(--c-text-dark); font-weight: 800; }
    .sig-line { border-bottom: 1px solid #94a3b8; width: 80%; margin: 0 auto; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .appr-show-wrapper { max-width: 100% !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="appr-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/appraisals" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;">تقرير تقييم الأداء الوظيفي</h2>
                <p style="margin:4px 0 0 0; color:var(--c-appr-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($appraisal->employee_name) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة كشف التقييم</button>
            <a href="/ERP/hr/appraisals/<?= $appraisal->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="تعديل"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <div class="card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-appr); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-chart-line-up" style="font-size:3.5rem; color:var(--c-appr);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;">إدارة الموارد البشرية - نموذج تقييم الأداء</span>
                </div>
            </div>
            <div style="text-align:end;">
                <span style="background:var(--c-appr); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;">كشف أداء رسمي</span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-appr-dark); font-size:1.1rem;"># <?= htmlspecialchars($appraisal->appraisal_code) ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label">اسم الموظف الخاضع للتقييم:</span>
                <span class="info-val"><?= htmlspecialchars($appraisal->employee_name) ?> (<?= htmlspecialchars($appraisal->emp_code) ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label">الإدارة / المسمى الوظيفي:</span>
                <span class="info-val"><?= htmlspecialchars($appraisal->dept_name ?: 'عام') ?> - <?= htmlspecialchars($appraisal->desig_name ?: '---') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">فترة التقييم:</span>
                <span class="info-val" style="font-family:monospace;"><?= htmlspecialchars($appraisal->appraisal_period) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">تاريخ التقييم:</span>
                <span class="info-val" style="font-family:monospace;"><?= htmlspecialchars($appraisal->appraisal_date) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">المُقَيِّم المسؤول:</span>
                <span class="info-val"><?= htmlspecialchars($appraisal->evaluator_name ?: 'غير محدد') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">حالة الاعتماد:</span>
                <span class="info-val"><span style="padding:2px 10px; border-radius:4px; font-size:0.85rem; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;"><?= $st['label'] ?></span></span>
            </div>
        </div>

        <div class="score-banner">
            <h4 style="margin:0 0 6px 0; color:var(--c-appr-dark); font-size:1rem;">النتيجة العامة للتقييم والتقدير</h4>
            <div style="font-size:2.2rem; font-weight:900; font-family:monospace; color:#059669;"><?= number_format((float)$appraisal->score, 1) ?> %</div>
            <div style="font-size:1.1rem; font-weight:800; color:#0284c7; margin-top:4px;">التقدير: <?= htmlspecialchars($appraisal->rating_grade) ?></div>
        </div>

        <?php if(!empty($appraisal->remarks)): ?>
            <div style="margin-top:24px; padding:16px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0;">
                <h5 style="margin:0 0 6px 0; font-size:0.85rem; color:var(--c-text-muted); font-weight:800;">التوصيات والملاحظات الإدارية:</h5>
                <p style="margin:0; font-size:0.9rem; color:#334155; font-weight:700; white-space:pre-line;"><?= htmlspecialchars($appraisal->remarks) ?></p>
            </div>
        <?php endif; ?>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6>توقيع المُقَيِّم / رئيس القسم</h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6>توقيع واعتماد مدير الموارد البشرية</h6>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>
</div>