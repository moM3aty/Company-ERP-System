<?php
// Path: resources/views/admin/companies/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
?>

<style>
    :root { 
        --tc-primary: #0d9488;
        --tc-dark: #0f766e;
        --tc-light: #f0fdfa;
        --tc-border: #ccfbf1;
        --tc-text: #1e293b;
        --tc-muted: #64748b;
    }

    .show-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .show-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0; }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--tc-muted); font-size: 1.2rem; transition: 0.2s;}
    .back-btn:hover { background: var(--tc-light); color: var(--tc-primary); border-color: var(--tc-primary); }
    
    .btn-edit { background: var(--tc-primary); color: white !important; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 10px rgba(13, 148, 136, 0.25); transition: 0.2s; }
    .btn-edit:hover { background: var(--tc-dark); transform: translateY(-2px); }

    .info-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .section-title { font-size: 1.15rem; font-weight: 800; color: var(--tc-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    
    .data-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    @media(max-width:640px) { .data-grid { grid-template-columns: 1fr; } }
    
    .data-item { background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 6px; }
    .data-lbl { font-size: 0.85rem; color: var(--tc-muted); font-weight: 800; text-transform: uppercase; }
    .data-val { font-size: 1rem; color: var(--tc-text); font-weight: 900; }
    .val-mono { font-family: monospace; font-size: 1.1rem; color: var(--tc-primary); }

    .badge-status { padding: 6px 12px; border-radius: 8px; font-weight: 800; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px; width: fit-content;}

    .branches-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: start; }
    .branches-table th { padding: 14px 16px; background: #f8fafc; color: var(--tc-muted); font-weight: 800; border-bottom: 2px solid #e2e8f0; }
    .branches-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: var(--tc-text); font-weight: 600;}
</style>

<div class="show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="show-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/admin/companies" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--tc-text); font-weight:900;">ملف الشركة التنفيذي</h2>
                <p style="margin:4px 0 0 0; color:var(--tc-muted); font-size:0.9rem;">عرض تفصيلي للبيانات الرئيسية والفروع التابعة.</p>
            </div>
        </div>
        <a href="/ERP/admin/companies/<?= $company->id ?>/edit" class="btn-edit"><i class="ph-bold ph-pencil-simple"></i> تعديل البيانات</a>
    </div>

    <!-- بطاقة البيانات الرئيسية -->
    <div class="info-card">
        <h3 class="section-title"><i class="ph-duotone ph-buildings" style="color:var(--tc-primary);"></i> البيانات الرسمية والقانونية</h3>
        
        <div class="data-grid">
            <div class="data-item">
                <span class="data-lbl">الاسم التجاري للشركة</span>
                <span class="data-val"><?= htmlspecialchars($company->name_ar) ?></span>
            </div>
            <div class="data-item">
                <span class="data-lbl">كود التمييز الداخلي</span>
                <span class="data-val val-mono"><?= htmlspecialchars($company->code) ?></span>
            </div>
            
            <div class="data-item">
                <span class="data-lbl">الرقم الضريبي (VAT Number)</span>
                <span class="data-val val-mono" style="color:var(--tc-text);"><?= htmlspecialchars($company->tax_number ?: 'غير مسجل') ?></span>
            </div>
            <div class="data-item">
                <span class="data-lbl">رقم السجل التجاري (CR Number)</span>
                <span class="data-val val-mono" style="color:var(--tc-text);"><?= htmlspecialchars($company->cr_number ?: 'غير مسجل') ?></span>
            </div>

            <div class="data-item" style="grid-column: span 2; flex-direction:row; align-items:center; justify-content:space-between;">
                <span class="data-lbl" style="margin:0;">الحالة التشغيلية للشركة بالمقرات:</span>
                <?php if($company->is_active): ?>
                    <span class="badge-status" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> شركة نشطة (Active)</span>
                <?php else: ?>
                    <span class="badge-status" style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca;"><i class="ph-fill ph-x-circle"></i> شركة معطلة (Inactive)</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- بطاقة الفروع التابعة (لإعطاء قيمة إضافية للشاشة) -->
    <div class="info-card">
        <h3 class="section-title"><i class="ph-duotone ph-tree-structure" style="color:var(--tc-primary);"></i> الفروع التابعة للشركة (<?= count($branches) ?> فروع)</h3>
        
        <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius:10px;">
            <table class="branches-table">
                <thead>
                    <tr>
                        <th style="width:20%;">كود الفرع</th>
                        <th style="width:40%;">اسم الفرع</th>
                        <th style="width:20%;">المدينة</th>
                        <th style="width:20%; text-align:center;">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($branches)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:30px; color:var(--tc-muted);">لا توجد أي فروع مسجلة لهذه الشركة حالياً.</td></tr>
                    <?php else: foreach($branches as $b): ?>
                        <tr>
                            <td style="font-family:monospace; color:var(--tc-primary);"><?= htmlspecialchars($b->code) ?></td>
                            <td style="font-weight:800;"><?= htmlspecialchars($b->name_ar) ?></td>
                            <td><i class="ph-duotone ph-map-pin" style="color:var(--tc-muted);"></i> <?= htmlspecialchars($b->city ?: '---') ?></td>
                            <td style="text-align:center;">
                                <?php if($b->is_active): ?>
                                    <span style="color:#059669; font-weight:bold; font-size:0.8rem; background:#ecfdf5; padding:2px 8px; border-radius:4px;">نشط</span>
                                <?php else: ?>
                                    <span style="color:#dc2626; font-weight:bold; font-size:0.8rem; background:#fef2f2; padding:2px 8px; border-radius:4px;">معطل</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>