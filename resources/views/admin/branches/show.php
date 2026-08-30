<?php
// Path: resources/views/admin/branches/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
?>

<style>
    :root { 
        --br-primary: #6d28d9;
        --br-dark: #5b21b6;
        --br-light: #f5f3ff;
        --br-border: #ede9fe;
        --br-text: #1e293b;
        --br-muted: #64748b;
    }

    .show-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .show-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0; }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--br-muted); font-size: 1.2rem; transition: 0.2s;}
    .back-btn:hover { background: var(--br-light); color: var(--br-primary); border-color: var(--br-primary); }
    
    .btn-edit { background: linear-gradient(135deg, var(--br-primary), var(--br-dark)); color: white !important; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 10px rgba(109, 40, 217, 0.25); transition: 0.2s; }
    .btn-edit:hover { transform: translateY(-2px); box-shadow: 0 6px 14px rgba(109, 40, 217, 0.35); }

    .info-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .info-card::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--br-primary); }
    [dir="ltr"] .info-card::before { right: auto; left: 0; }

    .section-title { font-size: 1.15rem; font-weight: 800; color: var(--br-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    
    .data-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    @media(max-width:640px) { .data-grid { grid-template-columns: 1fr; } }
    
    .data-item { background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 6px; }
    .data-lbl { font-size: 0.85rem; color: var(--br-muted); font-weight: 800; text-transform: uppercase; }
    .data-val { font-size: 1.05rem; color: var(--br-text); font-weight: 900; }
    .val-mono { font-family: monospace; font-size: 1.1rem; color: var(--br-primary); }

    .badge-status { padding: 6px 12px; border-radius: 8px; font-weight: 800; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px; width: fit-content;}
</style>

<div class="show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="show-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/admin/branches" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--br-text); font-weight:900;">ملف الفرع التعريفي</h2>
                <p style="margin:4px 0 0 0; color:var(--br-muted); font-size:0.9rem;">عرض تفصيلي لبيانات الفرع وارتباطه القانوني والمكاني.</p>
            </div>
        </div>
        <a href="/ERP/admin/branches/<?= $branch->id ?>/edit" class="btn-edit"><i class="ph-bold ph-pencil-simple"></i> تعديل الفرع</a>
    </div>

    <div class="info-card">
        <h3 class="section-title"><i class="ph-duotone ph-tree-structure" style="color:var(--br-primary);"></i> بطاقة بيانات المركز / الفرع</h3>
        
        <div class="data-grid">
            <div class="data-item">
                <span class="data-lbl">اسم الفرع / المركز</span>
                <span class="data-val"><?= htmlspecialchars($branch->name_ar) ?></span>
            </div>
            <div class="data-item">
                <span class="data-lbl">كود الفرع الداخلي</span>
                <span class="data-val val-mono"><?= htmlspecialchars($branch->code) ?></span>
            </div>

            <div class="data-item">
                <span class="data-lbl">المدينة الجغرافية</span>
                <span class="data-val"><i class="ph-duotone ph-map-pin" style="color:var(--br-muted);"></i> <?= htmlspecialchars($branch->city ?: 'غير محدد') ?></span>
            </div>
            <div class="data-item">
                <span class="data-lbl">الشركة الأم (الكيان المالك)</span>
                <span class="data-val" style="color:#0284c7;"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars($branch->company_name ?: 'عام') ?></span>
            </div>

            <div class="data-item" style="grid-column: span 2;">
                <span class="data-lbl">الرقم الضريبي التابع له (موروث من الشركة)</span>
                <span class="data-val val-mono" style="color:var(--br-text); background:#e2e8f0; padding:4px 8px; border-radius:6px; width:fit-content;"><?= htmlspecialchars($branch->tax_number ?: 'غير مسجل للشركة') ?></span>
            </div>

            <div class="data-item" style="grid-column: span 2; flex-direction:row; align-items:center; justify-content:space-between;">
                <span class="data-lbl" style="margin:0;">الحالة التشغيلية للفرع:</span>
                <?php if($branch->is_active): ?>
                    <span class="badge-status" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> فرع نشط (Active)</span>
                <?php else: ?>
                    <span class="badge-status" style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca;"><i class="ph-fill ph-x-circle"></i> فرع معطل (Inactive)</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>