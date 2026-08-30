<?php
// Path: resources/views/purchasing/landed_costs/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';
?>

<style>
    :root { --c-indigo: #4338ca; }
    .lc-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: #0f172a; color: #ffffff; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none;}

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); border-top: 8px solid var(--c-indigo); }
    .c-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 24px; }
    .c-brand { font-size: 1.8rem; font-weight: 900; color: #0f172a; margin: 0; }
    
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px; }
    .info-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; }
    .info-box h5 { margin: 0 0 10px 0; color: #64748b; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 6px; }
    .info-box p { margin: 4px 0; color: #0f172a; font-weight: 700; font-size: 0.95rem; }

    .table-print { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .table-print th { background: #0f172a; color: #ffffff; padding: 12px; font-size: 0.85rem; text-align: start; border: 1px solid #0f172a; }
    .table-print td { padding: 12px; border: 1px solid #cbd5e1; font-size: 0.95rem; color: #1e293b; font-weight: 600; }

    .totals-area { width: 350px; float: <?= $isAr ? 'left' : 'right' ?>; border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden; }
    .totals-row { display: flex; justify-content: space-between; padding: 12px 16px; background: #f8fafc; color: var(--c-indigo); font-size: 1.2rem; font-weight: 900; }

    @media print {
        @page { size: A4 portrait; margin: 12mm; }
        body { background: #fff !important; }
        .nt-sidebar, header, nav, footer, .top-bar { display: none !important; }
        .lc-wrapper { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .print-canvas { border: none !important; box-shadow: none !important; padding: 0 !important; border-top: none !important; }
        .info-box { border: 1px solid #000 !important; background: transparent !important; }
        .table-print th { background: #e2e8f0 !important; color: #000 !important; -webkit-print-color-adjust: exact !important; }
    }
</style>

<div class="lc-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <a href="/ERP/purchasing/landed-costs" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <button onclick="window.print()" class="btn-act"><i class="ph-bold ph-printer"></i> طباعة سند التكلفة</button>
    </div>

    <div class="print-canvas">
        <div class="c-header">
            <div>
                <h1 class="c-brand">Nour Trust ERP</h1>
                <p style="margin:4px 0 0 0; color:#64748b; font-weight:700;">سند تحميل مصاريف واستيراد (Landed Cost Allocation)</p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <div style="font-family: monospace; font-size: 1.8rem; font-weight: 900; color: var(--c-indigo);"><?= htmlspecialchars($landedCost->reference_number) ?></div>
                <div style="margin-top:4px; font-weight:800; color: #0f172a; text-transform: uppercase;">تاريخ التكلفة: <?= $landedCost->cost_date ?></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5>بيانات أمر الشراء والتوزيع</h5>
                <p><span style="color:#64748b;">رقم أمر الشراء (PO):</span> <strong style="color:var(--c-indigo); font-family:monospace;"><?= htmlspecialchars($landedCost->po_number ?? 'غير محدد') ?></strong></p>
                <p><span style="color:#64748b;">المورد:</span> <?= htmlspecialchars($landedCost->supplier_name ?? 'عام / متعدد') ?></p>
                <p><span style="color:#64748b;">معيار التوزيع:</span> <?= $landedCost->allocation_method == 'by_value' ? 'حسب قيمة الصنف' : 'حسب الكمية' ?></p>
            </div>
            <div class="info-box">
                <h5>الحالة والملاحظات</h5>
                <p><span style="color:#64748b;">حالة القيد:</span> <strong style="color:var(--c-indigo);"><?= strtoupper($landedCost->status) ?></strong></p>
                <p><span style="color:#64748b;">البيان والملاحظات:</span> <?= htmlspecialchars($landedCost->notes ?? 'لا يوجد') ?></p>
            </div>
        </div>

        <table class="table-print">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">نوع المصروف (Cost Type)</th>
                    <th style="width: 45%;">البيان والتفاصيل</th>
                    <th style="width: 15%; text-align: end;">المبلغ</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($items)): foreach($items as $index => $item): ?>
                    <tr>
                        <td style="text-align: center; color: #94a3b8; font-weight: 800;"><?= $index + 1 ?></td>
                        <td style="color: #0f172a; font-weight: 800;"><?= htmlspecialchars($item->cost_type) ?></td>
                        <td style="color: #475569;"><?= htmlspecialchars($item->description ?? '---') ?></td>
                        <td style="text-align: end; font-family: monospace; font-weight: 900; color: var(--c-indigo);"><?= number_format($item->amount, 2) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <div>
            <div class="totals-area">
                <div class="totals-row">
                    <span>إجمالي التكاليف المضافة:</span>
                    <span style="font-family: monospace;"><?= number_format($landedCost->total_amount, 2) ?></span>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>

        <div style="margin-top: 60px; display: flex; justify-content: space-between; text-align: center; font-weight: 800; font-size: 0.9rem; color: #0f172a;">
            <div style="flex: 1;">
                <div>إعداد المحاسب المسؤول</div>
                <div style="border-top: 1px dashed #cbd5e1; width: 60%; margin: 40px auto 0 auto;"></div>
            </div>
            <div style="flex: 1;">
                <div>اعتماد مدير الحسابات / المشتريات</div>
                <div style="border-top: 1px dashed #cbd5e1; width: 60%; margin: 40px auto 0 auto;"></div>
            </div>
        </div>
    </div>
</div>