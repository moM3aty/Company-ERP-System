<?php
// Path: resources/views/inventory/products/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';
?>

<style>
    :root { --c-amber: #f59e0b; }
    .prod-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: #0f172a; color: #ffffff; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none;}

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); border-top: 8px solid var(--c-amber); }
    .c-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 24px; }
    
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px; }
    .info-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; }
    .info-box h5 { margin: 0 0 10px 0; color: #64748b; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 6px; }
    .info-box p { margin: 4px 0; color: #0f172a; font-weight: 700; font-size: 0.95rem; }

    .price-badge { background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; text-align: center; }
    .price-badge h4 { margin: 0 0 6px 0; font-size: 0.85rem; color: #64748b; }
    .price-badge .val { font-size: 1.6rem; font-weight: 900; font-family: monospace; color: #0f172a; }

    @media print {
        @page { size: A4 portrait; margin: 12mm; }
        body { background: #fff !important; }
        .nt-sidebar, header, nav, footer, .top-bar { display: none !important; }
        .prod-wrapper { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .print-canvas { border: none !important; box-shadow: none !important; padding: 0 !important; border-top: none !important; }
        .info-box { border: 1px solid #000 !important; background: transparent !important; }
    }
</style>

<div class="prod-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <a href="/ERP/inventory/products" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <div style="display:flex; gap:10px;">
            <a href="/ERP/inventory/products/<?= $product->id ?>/edit" class="btn-act" style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1;"><i class="ph-bold ph-pencil-simple"></i> تعديل</a>
            <button onclick="window.print()" class="btn-act"><i class="ph-bold ph-printer"></i> طباعة البطاقة</button>
        </div>
    </div>

    <div class="print-canvas">
        <div class="c-header">
            <div>
                <h1 style="margin:0; font-size:1.8rem; font-weight:900; color:#0f172a;"><i class="ph-fill ph-package" style="color:var(--c-amber);"></i> <?= htmlspecialchars($product->name_ar) ?></h1>
                <?php if(!empty($product->name_en)): ?><p style="margin:4px 0 0 0; color:#64748b; font-weight:700; font-family:sans-serif;" dir="ltr"><?= htmlspecialchars($product->name_en) ?></p><?php endif; ?>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <div style="font-family: monospace; font-size: 1.8rem; font-weight: 900; color: var(--c-amber);"><i class="ph-bold ph-barcode"></i> <?= htmlspecialchars($product->item_code) ?></div>
                <div style="margin-top:4px; font-weight:800; color: <?= $product->is_active ? '#059669' : '#dc2626' ?>; text-transform: uppercase;">
                    <?= $product->is_active ? 'صنف نشط Active' : 'صنف موقوف Inactive' ?>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px;">
            <div class="price-badge">
                <h4>سعر الشراء / التكلفة (Cost)</h4>
                <div class="val"><?= number_format($product->purchase_price, 2) ?></div>
            </div>
            <div class="price-badge" style="background: #eff6ff; border-color: #bfdbfe;">
                <h4>سعر البيع (Selling Price)</h4>
                <div class="val" style="color: #2563eb;"><?= number_format($product->selling_price, 2) ?></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5>البيانات المخزنية</h5>
                <p><span style="color:#64748b;">الفئة / القسم:</span> <?= htmlspecialchars($product->category_name ?? 'بدون فئة') ?></p>
                <p><span style="color:#64748b;">وحدة القياس:</span> <strong style="color:var(--c-amber);"><?= htmlspecialchars($product->unit) ?></strong></p>
                <p><span style="color:#64748b;">رقم الباركود العالمي:</span> <span style="font-family:monospace;"><?= htmlspecialchars($product->barcode ?? '---') ?></span></p>
                <p><span style="color:#64748b;">حد إعادة الطلب للتنبيه:</span> <strong style="font-family:monospace; color:#dc2626;"><?= $product->reorder_level ?></strong></p>
            </div>
            <div class="info-box">
                <h5>وصف الصنف</h5>
                <p style="font-weight:600; line-height:1.6; color:#334155; font-size:0.9rem;">
                    <?= nl2br(htmlspecialchars($product->description ?? 'لا يوجد وصف مسجل لهذا الصنف.')) ?>
                </p>
                <div style="margin-top: 20px; padding-top: 10px; border-top: 1px dashed #cbd5e1; font-size: 0.8rem; color: #94a3b8;">
                    تم التسجيل في: <span style="font-family:monospace;"><?= $product->created_at ?></span>
                </div>
            </div>
        </div>
    </div>
</div>