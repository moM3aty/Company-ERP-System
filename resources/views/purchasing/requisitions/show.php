<?php
// Path: resources/views/purchasing/requisitions/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

function getPrStatusLabel($status, $isAr) {
    $map = [
        'draft' => ['color' => '#64748b', 'label' => $isAr ? 'مسودة' : 'Draft'],
        'pending' => ['color' => '#d97706', 'label' => $isAr ? 'قيد الاعتماد' : 'Pending'],
        'approved' => ['color' => '#0d9488', 'label' => $isAr ? 'معتمد' : 'Approved'],
        'rejected' => ['color' => '#dc2626', 'label' => $isAr ? 'مرفوض' : 'Rejected'],
        'completed' => ['color' => '#2563eb', 'label' => $isAr ? 'تم الشراء' : 'Completed']
    ];
    return $map[$status] ?? $map['draft'];
}

$statusData = getPrStatusLabel($requestData->status, $isAr);
?>

<style>
    :root {
        --c-teal: #0d9488;
        --c-teal-light: #ccfbf1;
    }

    .pr-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-teal-light); color: var(--c-teal); border-color: #99f6e4; }
    
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; }
    .btn-dark { background: #0f172a; color: #ffffff; }
    .btn-dark:hover { background: #1e293b; }

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); border-top: 6px solid var(--c-teal); }
    .c-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 24px; }
    .c-brand { font-size: 1.6rem; font-weight: 900; color: #0f172a; margin: 0; }
    
    .info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 28px; border: 1px solid #f1f5f9; }
    @media(max-width: 768px) { .info-grid { grid-template-columns: 1fr 1fr; } }
    .info-box h5 { margin: 0 0 6px 0; color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
    .info-box p { margin: 0; color: #0f172a; font-weight: 800; font-size: 0.95rem; }

    .table-print { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .table-print th { background: #0f172a; color: #ffffff; padding: 12px; font-size: 0.8rem; text-align: start; }
    .table-print td { padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; color: #1e293b; font-weight: 600; }

    .total-box { display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 16px 24px; border-radius: 12px; border: 1px dashed #cbd5e1; }
    .total-box h4 { margin: 0; color: #475569; font-size: 1.1rem; font-weight: 800; }
    .total-box .val { font-size: 1.6rem; font-weight: 900; color: var(--c-teal); font-family: monospace; }

    .print-signatures { display: none; }

    @media print {
        @page { size: A4 portrait; margin: 12mm; }
        body { background: #fff !important; }
        .nt-sidebar, header, nav, footer, .top-bar { display: none !important; }
        .pr-wrapper { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .print-canvas { border: 1px solid #cbd5e1 !important; box-shadow: none !important; padding: 20px !important; border-top: 4px solid #000 !important; }
        .info-grid { border: 1px solid #cbd5e1 !important; background: transparent !important; page-break-inside: avoid !important; }
        .table-print th { background: #e2e8f0 !important; color: #000 !important; -webkit-print-color-adjust: exact !important; border-bottom: 2px solid #000 !important; }
        .table-print td { border-bottom: 1px solid #cbd5e1 !important; }
        .total-box { border: 1px solid #000 !important; background: transparent !important; }
        .print-signatures { display: flex !important; justify-content: space-between !important; gap: 20px !important; margin-top: 60px !important; page-break-inside: avoid !important; }
        .sig-box { text-align: center !important; flex: 1 !important; font-weight: 800 !important; font-size: 0.85rem !important; color: #000 !important; }
        .sig-line { border-top: 1px dashed #000 !important; width: 80% !important; margin: 35px auto 0 auto !important; }
    }
</style>

<div class="pr-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="/ERP/purchasing/requisitions" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <h3 style="margin:0; font-weight: 800; color: #0f172a;">تفاصيل طلب الشراء (PR)</h3>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-act btn-dark"><i class="ph-bold ph-printer"></i> طباعة الوثيقة</button>
        </div>
    </div>

    <div class="print-canvas">
        <div class="c-header">
            <div>
                <h1 class="c-brand">Nour Trust ERP</h1>
                <p style="margin:4px 0 0 0; color:#64748b; font-weight:700;">طلب شراء داخلي (Purchase Requisition)</p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <div style="font-family: monospace; font-size: 1.5rem; font-weight: 900; color: var(--c-teal);"><?= htmlspecialchars($requestData->pr_number) ?></div>
                <div style="margin-top:4px; font-weight:800; color: <?= $statusData['color'] ?>; text-transform: uppercase;"><?= $statusData['label'] ?></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5>الإدارة الطالبة</h5>
                <p><?= htmlspecialchars($requestData->department ?? '---') ?></p>
            </div>
            <div class="info-box">
                <h5>مقدم الطلب</h5>
                <p><?= htmlspecialchars($requestData->requested_by ?? '---') ?></p>
            </div>
            <div class="info-box">
                <h5>تاريخ الطلب</h5>
                <p style="font-family: monospace;"><?= $requestData->request_date ?></p>
            </div>
            <div class="info-box">
                <h5>تاريخ الاحتياج</h5>
                <p style="color: #dc2626; font-family: monospace;"><?= $requestData->required_date ?></p>
            </div>
        </div>

        <h4 style="margin: 0 0 12px 0; color: #0f172a; font-weight: 900; font-size: 1.1rem;">بيان بالأصناف والمواصفات المطلوبة:</h4>
        
        <table class="table-print">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 20%;">كود الصنف</th>
                    <th style="width: 40%;">الوصف الفني والمواصفات</th>
                    <th style="width: 15%; text-align: center;">الكمية المطلوبة</th>
                    <th style="width: 20%; text-align: end;">السعر التقديري</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($items)): foreach($items as $index => $item): ?>
                    <tr>
                        <td style="text-align: center; color: #94a3b8; font-weight: 800;"><?= $index + 1 ?></td>
                        <td style="font-family: monospace; color: #475569;"><?= htmlspecialchars($item->product_code ?? 'عام / يدوي') ?></td>
                        <td style="color: #0f172a; font-weight: 700;"><?= htmlspecialchars($item->description) ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 800; color: #0f172a;"><?= number_format($item->quantity, 2) ?></td>
                        <td style="text-align: end; font-family: monospace; font-weight: 900; color: var(--c-teal);"><?= number_format($item->estimated_price, 2) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 20px;">لا توجد أصناف في هذا الطلب.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if(!empty($requestData->notes)): ?>
            <div style="margin-bottom: 24px; color: #334155; font-size: 0.9rem; font-weight: 600;">
                <strong style="color: #475569;">مبرر الشراء / ملاحظات:</strong> <?= nl2br(htmlspecialchars($requestData->notes)) ?>
            </div>
        <?php endif; ?>

        <div class="total-box">
            <h4>إجمالي التكلفة التقديرية للطلب:</h4>
            <div class="val"><?= number_format($requestData->total_estimated_value, 2) ?> <span style="font-size:0.9rem; color:#64748b;">EGP</span></div>
        </div>

        <div class="print-signatures">
            <div class="sig-box">
                <div>مقدم الطلب</div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div>مدير الإدارة الطالبة</div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div>إدارة المشتريات</div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div>الاعتماد المالي / الإدارة العليا</div>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>
</div>