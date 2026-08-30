<?php
// Path: resources/views/purchasing/contracts/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';
?>

<style>
    :root {
        --c-primary: #7c3aed;
        --c-primary-light: #ede9fe;
    }

    .cnt-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-primary-light); color: var(--c-primary); border-color: #c4b5fd; }
    
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; }
    .btn-dark { background: #0f172a; color: #ffffff; }
    .btn-dark:hover { background: #1e293b; }

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); border-top: 6px solid var(--c-primary); }
    .c-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 24px; }
    .c-brand { font-size: 1.6rem; font-weight: 900; color: #0f172a; margin: 0; }
    
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 28px; border: 1px solid #f1f5f9; }
    .info-box h5 { margin: 0 0 6px 0; color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
    .info-box p { margin: 0; color: #0f172a; font-weight: 800; font-size: 1rem; }

    .terms-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; color: #334155; line-height: 1.8; font-size: 0.95rem; font-weight: 600; }
    
    .print-signatures { display: none; }

    @media print {
        @page { size: A4 portrait; margin: 12mm; }
        body { background: #fff !important; }
        .nt-sidebar, header, nav, footer, .top-bar { display: none !important; }
        .cnt-wrapper { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .print-canvas { border: 1px solid #cbd5e1 !important; box-shadow: none !important; padding: 20px !important; border-top: 4px solid #000 !important; }
        .info-grid { border: 1px solid #cbd5e1 !important; background: transparent !important; page-break-inside: avoid !important; }
        .terms-box { border: 1px solid #cbd5e1 !important; page-break-inside: auto !important; }
        .print-signatures { display: flex !important; justify-content: space-between !important; margin-top: 60px !important; }
        .sig-box { text-align: center !important; flex: 1 !important; font-weight: 800 !important; font-size: 0.9rem !important; }
        .sig-line { border-top: 1px dashed #000 !important; width: 60% !important; margin: 35px auto 0 auto !important; }
    }
</style>

<div class="cnt-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="/ERP/purchasing/contracts" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <h3 style="margin:0; font-weight: 800; color: #0f172a;">تفاصيل عقد المشتريات</h3>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/ERP/purchasing/contracts/<?= $contract->id ?>/edit" class="btn-act" style="background:var(--c-primary-light); color:var(--c-primary); border:1px solid #c4b5fd;"><i class="ph-bold ph-pencil-simple"></i> تعديل العقد</a>
            <button onclick="window.print()" class="btn-act btn-dark"><i class="ph-bold ph-printer"></i> طباعة الوثيقة</button>
        </div>
    </div>

    <div class="print-canvas">
        <div class="c-header">
            <div>
                <h1 class="c-brand">Nour Trust ERP</h1>
                <p style="margin:4px 0 0 0; color:#64748b; font-weight:700;">وثيقة اتفاقية توريد رسمية (Purchase Contract)</p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <div style="font-family: monospace; font-size: 1.5rem; font-weight: 900; color: var(--c-primary);"><?= htmlspecialchars($contract->contract_number) ?></div>
                <div style="margin-top:4px; font-weight:800; color:#059669; text-transform: uppercase;"><?= $contract->status ?></div>
            </div>
        </div>

        <div style="margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px dashed #f1f5f9;">
            <h5 style="margin:0 0 6px 0; color:var(--c-primary); font-size:0.85rem; font-weight:800; text-transform:uppercase;">موضوع الاتفاقية / العنوان</h5>
            <h3 style="margin:0; color:#0f172a; font-size:1.4rem; font-weight:900;"><?= htmlspecialchars($contract->title) ?></h3>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5>الطرف الأول (الشركة)</h5>
                <p>مؤسسة نور الثقة للحلول الرقمية</p>
                <div style="color:#64748b; font-size:0.85rem; font-weight:600; margin-top:4px;">الرقم الضريبي: 300000000000003</div>
            </div>
            <div class="info-box">
                <h5>الطرف الثاني (المورد)</h5>
                <p><?= htmlspecialchars($contract->supplier_name ?? '---') ?></p>
                <div style="color:#64748b; font-size:0.85rem; font-weight:600; margin-top:4px; font-family:monospace;">الرقم الضريبي: <?= htmlspecialchars($contract->supplier_tax ?? '---') ?></div>
            </div>
        </div>

        <div class="info-grid" style="background:#ffffff; border-color:#e2e8f0;">
            <div class="info-box">
                <h5>مدة سريان العقد</h5>
                <p style="color:#059669; font-size:0.95rem;">بداية: <?= $contract->start_date ?></p>
                <p style="color:#dc2626; font-size:0.95rem;">نهاية: <?= $contract->end_date ?></p>
            </div>
            <div class="info-box">
                <h5>القيمة الإجمالية التقديرية</h5>
                <p style="font-family: monospace; font-size:1.4rem; color:var(--c-primary);"><?= number_format($contract->total_value, 2) ?> <span style="font-size:0.9rem; color:#64748b;">EGP</span></p>
            </div>
        </div>

        <h4 style="margin: 0 0 12px 0; color: #0f172a; font-weight: 900; font-size: 1.1rem;">الشروط والأحكام المتفق عليها:</h4>
        <div class="terms-box">
            <?= !empty($contract->terms_conditions) ? nl2br(htmlspecialchars($contract->terms_conditions)) : 'تخضع هذه الاتفاقية للشروط والأحكام العامة للتوريد المعتمدة بين الطرفين.' ?>
        </div>

        <div class="print-signatures">
            <div class="sig-box">
                <div>الطرف الأول (مدير المشتريات)</div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div>الطرف الثاني (المورد / المفوض)</div>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>
</div>