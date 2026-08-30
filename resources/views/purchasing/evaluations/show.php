<?php
// Path: resources/views/purchasing/evaluations/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

$supName = $isAr ? ($evaluation->supplier_name ?? 'عام') : ($evaluation->supplier_name ?? 'General Supplier');
?>

<style>
    .eval-show-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .back-btn { width: 38px; height: 38px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; }
    
    .btn-act { padding: 9px 18px; border-radius: 8px; font-weight: 800; font-size: 0.9rem; font-family: inherit; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none; transition: 0.2s; }
    .btn-act-dark { background: #0f172a; color: #ffffff; }

    /* Canvas Certificate */
    .eval-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); padding: 40px; }
    
    .canvas-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 24px; }
    .brand-title { margin: 0; font-size: 1.6rem; font-weight: 900; color: #0f172a; }
    .brand-sub { margin: 4px 0 0 0; color: #64748b; font-size: 0.85rem; font-weight: 600; }
    
    .grade-badge-huge { background: #0f172a; color: #34d399; padding: 12px 24px; border-radius: 12px; font-family: monospace; font-size: 2rem; font-weight: 900; text-align: center; display: inline-block; }

    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #f1f5f9; margin-bottom: 28px; }
    .info-box h5 { margin: 0 0 6px 0; color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
    .info-box p { margin: 0; color: #0f172a; font-weight: 800; font-size: 1rem; }

    .score-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px dashed #cbd5e1; }
    .score-progress-bar { width: 50%; height: 10px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
    .score-progress-fill { height: 100%; background: linear-gradient(90deg, #db2777, #be185d); }

    .print-signatures { display: none; }

    @media print {
        @page { size: A4 portrait; margin: 12mm 15mm; }
        body { background: #fff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .nt-sidebar, header, nav, footer, .top-bar { display: none !important; }
        .eval-show-wrapper { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .eval-canvas { border: none !important; box-shadow: none !important; padding: 0 !important; }
        .print-signatures { display: flex !important; justify-content: space-between !important; margin-top: 50px !important; padding-top: 15px !important; border-top: 2px dashed #0f172a !important; page-break-inside: avoid !important; }
        .sig-box { text-align: center !important; flex: 1 !important; font-weight: 800 !important; }
        .sig-line { border-top: 1px dashed #0f172a !important; width: 70% !important; margin: 35px auto 0 auto !important; }
    }
</style>

<div class="eval-show-wrapper" dir="<?= $dir ?>">
    
    <div class="top-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="/ERP/purchasing/supplier-evaluations" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <h3 style="margin:0; font-size: 1.3rem; font-weight: 800; color: #0f172a;"><?= $isAr ? 'تقرير تقييم المورد' : 'Supplier Evaluation Report' ?></h3>
        </div>
        <button type="button" onclick="window.print()" class="btn-act btn-act-dark"><i class="ph-bold ph-printer"></i> <?= $isAr ? 'طباعة التقرير A4' : 'Print Report' ?></button>
    </div>

    <div class="eval-canvas">
        
        <div class="canvas-header">
            <div>
                <h1 class="brand-title">Nour Trust ERP</h1>
                <p class="brand-sub">تقرير تقييم أداء ومودة الموردين المعتمد</p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <span class="grade-badge-huge"><?= htmlspecialchars($evaluation->grade) ?></span>
                <div style="font-weight: 800; color: #db2777; font-family: monospace; font-size: 1rem; margin-top: 6px;"><?= htmlspecialchars($evaluation->eval_number) ?></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5>المورد محل التقييم</h5>
                <p><?= htmlspecialchars($supName) ?> (<?= htmlspecialchars($evaluation->supplier_code ?? '---') ?>)</p>
            </div>
            <div class="info-box">
                <h5>تاريخ التقييم / الفتره</h5>
                <p><?= htmlspecialchars($evaluation->evaluation_date) ?> (<?= htmlspecialchars($evaluation->period_covered ?? 'عام') ?>)</p>
            </div>
            <div class="info-box">
                <h5>اسم المقيم</h5>
                <p><?= htmlspecialchars($evaluation->evaluator_name ?? 'إدارة المشتريات') ?></p>
            </div>
            <div class="info-box">
                <h5>النسبة الإجمالية المستحقة</h5>
                <p style="color: #059669; font-family: monospace; font-size: 1.2rem;"><?= number_format($evaluation->overall_score, 2) ?> %</p>
            </div>
        </div>

        <h4 style="margin: 0 0 16px 0; color: #0f172a; font-size: 1.1rem; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">تفاصيل نتائج معايير التقييم:</h4>

        <div class="score-row">
            <div><strong>1. الالتزام بالمواعيد:</strong> <span style="font-family: monospace; font-weight: 800; color: #db2777;"><?= $evaluation->delivery_score ?>%</span></div>
            <div class="score-progress-bar"><div class="score-progress-fill" style="width: <?= $evaluation->delivery_score ?>%;"></div></div>
        </div>

        <div class="score-row">
            <div><strong>2. جودة المواصفات:</strong> <span style="font-family: monospace; font-weight: 800; color: #db2777;"><?= $evaluation->quality_score ?>%</span></div>
            <div class="score-progress-bar"><div class="score-progress-fill" style="width: <?= $evaluation->quality_score ?>%;"></div></div>
        </div>

        <div class="score-row">
            <div><strong>3. تنافسية الأسعار:</strong> <span style="font-family: monospace; font-weight: 800; color: #db2777;"><?= $evaluation->price_score ?>%</span></div>
            <div class="score-progress-bar"><div class="score-progress-fill" style="width: <?= $evaluation->price_score ?>%;"></div></div>
        </div>

        <div class="score-row">
            <div><strong>4. خدمة ما بعد التوريد:</strong> <span style="font-family: monospace; font-weight: 800; color: #db2777;"><?= $evaluation->service_score ?>%</span></div>
            <div class="score-progress-bar"><div class="score-progress-fill" style="width: <?= $evaluation->service_score ?>%;"></div></div>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-top: 28px;">
            <h5 style="margin: 0 0 8px 0; color: #64748b; font-size: 0.8rem; font-weight: 800; text-transform: uppercase;">التوصية النهائية والقرار:</h5>
            <div style="color: #0f172a; font-weight: 700; line-height: 1.6;"><?= nl2br(htmlspecialchars($evaluation->recommendation ?? 'استمرار التعامل وفق الشروط المعتمدة.')) ?></div>
        </div>

        <div class="print-signatures">
            <div class="sig-box">
                <div>مسؤول التقييم</div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div>مدير المشتريات</div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div>مدير توكيد الجودة</div>
                <div class="sig-line"></div>
            </div>
        </div>

    </div>
</div>