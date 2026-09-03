<?php
// Path: resources/views/purchasing/receipts/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';
$currency = current_currency();

$t = [
    'ar' => [
        'title' => 'تفاصيل إذن استلام بضائع',
        'print' => 'طباعة إذن الاستلام',
        'edit' => 'تعديل',
        'brand_title' => 'محضر فحص وإذن استلام بضائع (Goods Receipt Note)',
        'receipt_date' => 'تاريخ الاستلام',
        'supplier_info' => 'بيانات المورد والشحنة',
        'supplier_label' => 'المورد:',
        'delivery_note' => 'رقم إذن التسليم / البوليصة:',
        'linked_po' => 'رقم أمر الشراء المرتبط:',
        'inspection_info' => 'بيانات الفحص والمخزن',
        'received_by' => 'مستلم الشحنة بالمخزن:',
        'status' => 'حالة الفحص:',
        'notes' => 'ملاحظات:',
        'direct_po' => 'مباشر',
        'no_notes' => 'لا يوجد',
        'default_receiver' => 'أمين المخزن',
        'col_code' => 'كود الصنف',
        'col_desc' => 'الوصف',
        'col_rec' => 'الكمية المستلمة',
        'col_acc' => 'الكمية المقبولة',
        'col_rej' => 'الكمية المرفوضة',
        'sig_store' => 'توقيع أمين المخزن المستلم',
        'sig_agent' => 'توقيع مسلّم الشحنة / المورد',
        'sig_committee' => 'اعتماد لجنة الفحص والجودة',
        'unregistered' => 'غير مسجل'
    ],
    'en' => [
        'title' => 'Goods Receipt Details (GRN)',
        'print' => 'Print GRN',
        'edit' => 'Edit',
        'brand_title' => 'Goods Receipt Note & Inspection Sheet',
        'receipt_date' => 'Receipt Date',
        'supplier_info' => 'Supplier & Shipment Info',
        'supplier_label' => 'Supplier:',
        'delivery_note' => 'Delivery Note / Waybill No:',
        'linked_po' => 'Linked PO Number:',
        'inspection_info' => 'Inspection & Store Info',
        'received_by' => 'Store Receiver Name:',
        'status' => 'Inspection Status:',
        'notes' => 'Notes:',
        'direct_po' => 'Direct',
        'no_notes' => 'None',
        'default_receiver' => 'Storekeeper',
        'col_code' => 'Item Code',
        'col_desc' => 'Description',
        'col_rec' => 'Qty Received',
        'col_acc' => 'Qty Accepted',
        'col_rej' => 'Qty Rejected',
        'sig_store' => 'Storekeeper Signature',
        'sig_agent' => 'Delivery Agent Signature',
        'sig_committee' => 'Quality Committee Approval',
        'unregistered' => 'N/A'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-emerald: #059669; }
    .grn-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: #0f172a; color: #ffffff; transition: 0.2s;}
    .btn-act:hover { background: #1e293b; }
    .btn-edit { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s;}
    .back-btn:hover { background: #d1fae5; color: #059669; border-color: #a7f3d0;}

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); border-top: 8px solid var(--c-emerald); }
    .c-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 24px; }
    .c-brand { font-size: 1.8rem; font-weight: 900; color: #0f172a; margin: 0; }
    
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px; }
    .info-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; }
    .info-box h5 { margin: 0 0 10px 0; color: #64748b; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 6px; }
    .info-box p { margin: 4px 0; color: #0f172a; font-weight: 700; font-size: 0.95rem; }

    .table-print { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .table-print th { background: #0f172a; color: #ffffff; padding: 12px; font-size: 0.85rem; text-align: start; border: 1px solid #0f172a; }
    .table-print td { padding: 12px; border: 1px solid #cbd5e1; font-size: 0.95rem; color: #1e293b; font-weight: 600; }

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
        
        /* 2. إظهار ورقة الإذن فقط */
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

        .print-signatures { display: flex !important; justify-content: space-between !important; margin-top: 60px !important; page-break-inside: avoid !important; }
        .sig-box { text-align: center !important; flex: 1 !important; font-weight: 800 !important; font-size: 0.85rem !important; color: #000 !important; }
        .sig-line { border-top: 1px dashed #000 !important; width: 60% !important; margin: 40px auto 0 auto !important; }
    }
</style>

<div class="grn-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="/ERP/purchasing/receipts" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <h3 style="margin:0; font-weight: 800; color: #0f172a;"><?= $t['title'] ?></h3>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/ERP/purchasing/receipts/<?= $receipt->id ?>/edit" class="btn-act btn-edit"><i class="ph-bold ph-pencil-simple"></i> <?= $t['edit'] ?></a>
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
                <div style="font-family: monospace; font-size: 1.8rem; font-weight: 900; color: var(--c-emerald);"><?= htmlspecialchars($receipt->receipt_number) ?></div>
                <div style="margin-top:4px; font-weight:800; color: #0f172a; text-transform: uppercase;"><?= $t['receipt_date'] ?>: <?= $receipt->receipt_date ?></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5><?= $t['supplier_info'] ?></h5>
                <p style="font-size: 1.1rem; color: var(--c-emerald);"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars($receipt->supplier_name ?? '---') ?></p>
                <p><span style="color:#64748b;"><?= $t['delivery_note'] ?></span> <?= htmlspecialchars($receipt->delivery_note_number ?? '---') ?></p>
                <p><span style="color:#64748b;"><?= $t['linked_po'] ?></span> <?= htmlspecialchars($receipt->po_number ?? $t['direct_po']) ?></p>
            </div>
            <div class="info-box">
                <h5><?= $t['inspection_info'] ?></h5>
                <p><span style="color:#64748b;"><?= $t['received_by'] ?></span> <?= htmlspecialchars($receipt->received_by ?? $t['default_receiver']) ?></p>
                <p><span style="color:#64748b;"><?= $t['status'] ?></span> <strong style="color:var(--c-emerald);"><?= strtoupper($receipt->status) ?></strong></p>
                <p><span style="color:#64748b;"><?= $t['notes'] ?></span> <?= htmlspecialchars($receipt->notes ?? $t['no_notes']) ?></p>
            </div>
        </div>

        <table class="table-print">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 15%;"><?= $t['col_code'] ?></th>
                    <th style="width: 35%;"><?= $t['col_desc'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_rec'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_acc'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_rej'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($items)): foreach($items as $index => $item): ?>
                    <tr>
                        <td style="text-align: center; color: #94a3b8; font-weight: 800;"><?= $index + 1 ?></td>
                        <td style="font-family: monospace; color: #475569;"><?= htmlspecialchars($item->product_code ?? $t['unregistered']) ?></td>
                        <td><?= htmlspecialchars($item->description) ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 800;"><?= number_format($item->quantity_received, 2) ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 800; color: #059669;"><?= number_format($item->quantity_accepted, 2) ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 800; color: #dc2626;"><?= number_format($item->quantity_rejected, 2) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <div class="print-signatures">
            <div class="sig-box">
                <div><?= $t['sig_store'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $t['sig_agent'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $t['sig_committee'] ?></div>
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