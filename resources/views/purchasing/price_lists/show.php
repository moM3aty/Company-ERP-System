<?php
// Path: resources/views/purchasing/price_lists/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';
?>

<style>
    .pl-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .back-btn { width: 38px; height: 38px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: #e0e7ff; color: #4f46e5; border-color: #c7d2fe; }
    
    .btn-act { padding: 9px 18px; border-radius: 8px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    .btn-dark { background: #0f172a; color: #ffffff; }
    .btn-dark:hover { background: #1e293b; }

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); }
    .c-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 24px; }
    .c-brand { font-size: 1.6rem; font-weight: 900; color: #0f172a; margin: 0; }
    
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 28px; border: 1px solid #f1f5f9; }
    .info-box h5 { margin: 0 0 6px 0; color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
    .info-box p { margin: 0; color: #0f172a; font-weight: 800; font-size: 1rem; }

    .table-print { width: 100%; border-collapse: collapse; }
    .table-print th { background: #0f172a; color: #ffffff; padding: 10px 12px; font-size: 0.8rem; text-align: start; }
    .table-print td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; color: #1e293b; font-weight: 600; }

    .print-signatures { display: none; }

    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        body { background: #fff !important; }
        .nt-sidebar, header, nav, footer, .top-bar { display: none !important; }
        .pl-wrapper { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .print-canvas { border: none !important; box-shadow: none !important; padding: 0 !important; }
        .info-grid { border: 1px solid #000 !important; background: transparent !important; }
        .table-print th { background: #e2e8f0 !important; color: #000 !important; -webkit-print-color-adjust: exact !important; }
        .print-signatures { display: flex !important; justify-content: space-between !important; margin-top: 50px !important; }
        .sig-box { text-align: center !important; flex: 1 !important; font-weight: 800 !important; }
        .sig-line { border-top: 1px dashed #000 !important; width: 60% !important; margin: 30px auto 0 auto !important; }
    }
</style>

<div class="pl-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="/ERP/purchasing/price-lists" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <h3 style="margin:0; font-weight: 800; color: #0f172a;">تفاصيل قائمة الأسعار</h3>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/ERP/purchasing/price-lists/<?= $priceList->id ?>/edit" class="btn-act" style="background:#e0e7ff; color:#4f46e5; border:1px solid #c7d2fe;"><i class="ph-bold ph-pencil-simple"></i> تعديل</a>
            <button onclick="window.print()" class="btn-act btn-dark"><i class="ph-bold ph-printer"></i> طباعة معتمدة</button>
        </div>
    </div>

    <div class="print-canvas">
        <div class="c-header">
            <div>
                <h1 class="c-brand">Nour Trust ERP</h1>
                <p style="margin:4px 0 0 0; color:#64748b; font-weight:600;">قائمة أسعار الموردين المعتمدة (Supplier Price List)</p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <div style="font-family: monospace; font-size: 1.4rem; font-weight: 900; color: #4f46e5;"><?= htmlspecialchars($priceList->list_number) ?></div>
                <div style="margin-top:4px; font-weight:800; color:#059669; text-transform: uppercase;"><?= strtoupper($priceList->status) ?></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5>العنوان / الموضوع</h5>
                <p><?= htmlspecialchars($priceList->title) ?></p>
            </div>
            <div class="info-box">
                <h5>المورد</h5>
                <p><i class="ph-fill ph-buildings text-slate-400"></i> <?= htmlspecialchars($priceList->supplier_name ?? '---') ?></p>
            </div>
            <div class="info-box">
                <h5>فترة سريان الأسعار</h5>
                <p style="color: #059669; font-size:0.9rem;">من: <?= $priceList->valid_from ?> <span style="color:#dc2626; margin:0 8px;">إلى: <?= $priceList->valid_to ?></span></p>
            </div>
            <div class="info-box">
                <h5>العملة المعتمدة</h5>
                <p style="font-family: monospace; font-size:1.1rem; color:#4f46e5;"><?= htmlspecialchars($priceList->currency) ?></p>
            </div>
        </div>

        <h4 style="margin: 0 0 16px 0; color: #0f172a; font-weight: 800; font-size: 1.1rem;">أصناف القائمة (Products & Pricing):</h4>
        
        <table class="table-print">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 45%;">كود واسم الصنف</th>
                    <th style="width: 15%; text-align: center;">MOQ</th>
                    <th style="width: 15%; text-align: center;">خصم %</th>
                    <th style="width: 20%; text-align: end;">سعر الوحدة</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($items)): foreach($items as $index => $item): ?>
                    <tr>
                        <td style="text-align: center; color: #94a3b8;"><?= $index + 1 ?></td>
                        <td>
                            <div style="color: #0f172a; font-weight: 800;"><?= htmlspecialchars($item->product_name ?? 'غير متوفر') ?></div>
                            <div style="color: #64748b; font-family: monospace; font-size: 0.8rem;"><?= htmlspecialchars($item->product_code ?? '') ?></div>
                        </td>
                        <td style="text-align: center; font-family: monospace; color:#475569;"><?= number_format($item->min_order_qty, 2) ?></td>
                        <td style="text-align: center; font-family: monospace; color:#d97706;"><?= number_format($item->discount_percent, 2) ?>%</td>
                        <td style="text-align: end; font-family: monospace; font-weight: 900; color:#4f46e5; font-size: 1.05rem;"><?= number_format($item->unit_price, 2) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 20px;">لا توجد أصناف في هذه القائمة.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if(!empty($priceList->notes)): ?>
            <div style="margin-top: 24px; padding: 16px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
                <strong style="color: #4f46e5; font-size: 0.85rem;">ملاحظات وشروط القائمة:</strong><br>
                <span style="color: #334155; font-weight: 600; font-size: 0.9rem;"><?= nl2br(htmlspecialchars($priceList->notes)) ?></span>
            </div>
        <?php endif; ?>

        <div class="print-signatures">
            <div class="sig-box">
                <div>إعداد المشتريات</div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div>اعتماد مدير المشتريات</div>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>
</div>