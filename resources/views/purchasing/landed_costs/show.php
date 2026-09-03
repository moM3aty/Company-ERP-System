<?php
// Path: resources/views/purchasing/landed_costs/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';
$currency = current_currency();

$t = [
    'ar' => [
        'title' => 'تفاصيل بيان التكاليف الإضافية',
        'print' => 'طباعة سند التكلفة',
        'edit' => 'تعديل',
        'brand_title' => 'سند تحميل مصاريف واستيراد (Landed Cost Allocation)',
        'cost_date' => 'تاريخ التكلفة',
        'shipment_info' => 'بيانات أمر الشراء والتوزيع',
        'po_number' => 'رقم أمر الشراء (PO):',
        'supplier' => 'المورد:',
        'alloc_method' => 'معيار التوزيع:',
        'alloc_value' => 'حسب قيمة الصنف',
        'alloc_qty' => 'حسب الكمية',
        'status_info' => 'الحالة والملاحظات',
        'status' => 'حالة القيد:',
        'notes' => 'البيان والملاحظات:',
        'unspecified' => 'غير محدد',
        'general_vendor' => 'عام / متعدد',
        'no_notes' => 'لا يوجد',
        'col_type' => 'نوع المصروف (Cost Type)',
        'col_desc' => 'البيان والتفاصيل',
        'col_amount' => 'المبلغ',
        'total_costs' => 'إجمالي التكاليف المضافة:',
        'sig_acc' => 'إعداد المحاسب المسؤول',
        'sig_mgr' => 'اعتماد مدير الحسابات / المشتريات'
    ],
    'en' => [
        'title' => 'Landed Cost Entry Details',
        'print' => 'Print Allocation Voucher',
        'edit' => 'Edit',
        'brand_title' => 'Landed Cost & Freight Allocation Sheet',
        'cost_date' => 'Cost Date',
        'shipment_info' => 'PO & Allocation Details',
        'po_number' => 'PO Number:',
        'supplier' => 'Supplier:',
        'alloc_method' => 'Allocation Method:',
        'alloc_value' => 'By Item Value',
        'alloc_qty' => 'By Item Quantity',
        'status_info' => 'Status & Notes',
        'status' => 'Entry Status:',
        'notes' => 'Notes & Remarks:',
        'unspecified' => 'Unspecified',
        'general_vendor' => 'General / Multiple',
        'no_notes' => 'None',
        'col_type' => 'Expense / Cost Type',
        'col_desc' => 'Description & Details',
        'col_amount' => 'Amount',
        'total_costs' => 'Total Additional Costs:',
        'sig_acc' => 'Accountant Preparation',
        'sig_mgr' => 'Finance / Purchasing Approval'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-indigo: #4338ca; }
    .lc-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: #0f172a; color: #ffffff; transition: 0.2s; }
    .btn-act:hover { background: #1e293b; }
    .btn-edit { background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s;}
    .back-btn:hover { background: #e0e7ff; color: #4338ca; border-color: #c7d2fe;}

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

    .totals-container { display: flex; justify-content: <?= $isAr ? 'flex-start' : 'flex-end' ?>; margin-top: 24px; }
    .totals-area { width: 350px; border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden; page-break-inside: avoid; }
    .totals-row { display: flex; justify-content: space-between; padding: 12px 16px; background: #f8fafc; color: var(--c-indigo); font-size: 1.2rem; font-weight: 900; }

    .print-signatures { display: none; }

    /* ========================================= */
    /* إعدادات الطباعة الشاملة والحجب الإجباري */
    /* ========================================= */
    @media print {
        @page { size: A4 portrait; margin: 15mm; }
        
        /* 1. حجب جميع عناصر الصفحة خارج كارت التقييم */
        body * {
            visibility: hidden !important;
        }
        
        /* 2. إظهار ورقة السند فقط */
        .print-canvas, .print-canvas * {
            visibility: visible !important;
        }
        
        .print-canvas {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
        }

        /* 3. الإخفاء الجذري لكل مكونات البحث، الترقيم، و DataTables */
        .table-pagination-nav,
        .table-pagination-nav *,
        .print-canvas .table-pagination-nav,
        .dataTables_info, .dataTables_paginate, .pagination {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            height: 0 !important;
            width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* 4. إخفاء عمود الترقيم (#) من الجدول */
        .table-print th:first-child, .table-print td:first-child { 
            display: none !important; 
        }

        .info-box { border: 1px solid #000 !important; background: transparent !important; page-break-inside: avoid !important; }
        .table-print th { background: #e2e8f0 !important; color: #000 !important; border: 1px solid #000 !important; -webkit-print-color-adjust: exact !important; }
        .table-print td { border: 1px solid #000 !important; color: #000 !important; }
        .totals-area { border: 1px solid #000 !important; }

        .print-signatures { display: flex !important; justify-content: space-between !important; margin-top: 60px !important; page-break-inside: avoid !important; }
        .sig-box { text-align: center !important; flex: 1 !important; font-weight: 800 !important; font-size: 0.85rem !important; color: #000 !important; }
        .sig-line { border-top: 1px dashed #000 !important; width: 60% !important; margin: 40px auto 0 auto !important; }
    }
</style>

<div class="lc-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="/ERP/purchasing/landed-costs" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <h3 style="margin:0; font-weight: 800; color: #0f172a;"><?= $t['title'] ?></h3>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/ERP/purchasing/landed-costs/<?= $landedCost->id ?>/edit" class="btn-act btn-edit"><i class="ph-bold ph-pencil-simple"></i> <?= $t['edit'] ?></a>
            <button onclick="safePrint()" class="btn-act"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="print-canvas">
        <div class="c-header">
            <div>
                <h1 class="c-brand">Nour Trust ERP</h1>
                <p style="margin:4px 0 0 0; color:#64748b; font-weight:700;"><?= $t['brand_title'] ?></p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <div style="font-family: monospace; font-size: 1.8rem; font-weight: 900; color: var(--c-indigo);"><?= htmlspecialchars($landedCost->reference_number) ?></div>
                <div style="margin-top:4px; font-weight:800; color: #0f172a; text-transform: uppercase;"><?= $t['cost_date'] ?>: <?= $landedCost->cost_date ?></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5><?= $t['shipment_info'] ?></h5>
                <p><span style="color:#64748b;"><?= $t['po_number'] ?></span> <strong style="color:var(--c-indigo); font-family:monospace;"><?= htmlspecialchars($landedCost->po_number ?? $t['unspecified']) ?></strong></p>
                <p><span style="color:#64748b;"><?= $t['supplier'] ?></span> <?= htmlspecialchars($landedCost->supplier_name ?? $t['general_vendor']) ?></p>
                <p><span style="color:#64748b;"><?= $t['alloc_method'] ?></span> <?= $landedCost->allocation_method == 'by_value' ? $t['alloc_value'] : $t['alloc_qty'] ?></p>
            </div>
            <div class="info-box">
                <h5><?= $t['status_info'] ?></h5>
                <p><span style="color:#64748b;"><?= $t['status'] ?></span> <strong style="color:var(--c-indigo);"><?= strtoupper($landedCost->status) ?></strong></p>
                <p><span style="color:#64748b;"><?= $t['notes'] ?></span> <?= htmlspecialchars($landedCost->notes ?? $t['no_notes']) ?></p>
            </div>
        </div>

        <table class="table-print">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;"><?= $t['col_type'] ?></th>
                    <th style="width: 45%;"><?= $t['col_desc'] ?></th>
                    <th style="width: 15%; text-align: end;"><?= $t['col_amount'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($items)): foreach($items as $index => $item): ?>
                    <tr>
                        <td style="text-align: center; color: #94a3b8; font-weight: 800;"><?= $index + 1 ?></td>
                        <td style="color: #0f172a; font-weight: 800;"><?= htmlspecialchars($item->cost_type) ?></td>
                        <td style="color: #475569;"><?= htmlspecialchars($item->description ?? '---') ?></td>
                        <td style="text-align: end; font-family: monospace; font-weight: 900; color: var(--c-indigo);"><?= number_format($item->amount, 2) ?> <?= $currency ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <div class="totals-container">
            <div class="totals-area">
                <div class="totals-row">
                    <span><?= $t['total_costs'] ?></span>
                    <span style="font-family: monospace;"><?= number_format($landedCost->total_amount, 2) ?> <?= $currency ?></span>
                </div>
            </div>
        </div>

        <div class="print-signatures">
            <div class="sig-box">
                <div><?= $t['sig_acc'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $t['sig_mgr'] ?></div>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>
</div>

<script>
function purgeControls() {
    const selectors = ['.table-pagination-nav', '.dataTables_info', '.dataTables_paginate', '.pagination'];
    selectors.forEach(s => {
        document.querySelectorAll(s).forEach(el => el.remove());
    });
}

function safePrint() {
    purgeControls();
    window.print();
}

document.addEventListener("DOMContentLoaded", purgeControls);
window.addEventListener("beforeprint", purgeControls);
</script>