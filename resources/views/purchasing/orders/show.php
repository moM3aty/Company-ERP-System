<?php
// Path: resources/views/purchasing/orders/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';
$currency = current_currency();

$t = [
    'ar' => [
        'title' => 'تفاصيل أمر الشراء (PO)',
        'print' => 'طباعة الوثيقة',
        'edit' => 'تعديل',
        'brand_title' => 'أمر شراء رسمي (Purchase Order)',
        'order_date' => 'تاريخ الأمر',
        'vendor_info' => 'إلى المورد (Vendor)',
        'tax_no' => 'الرقم الضريبي:',
        'phone' => 'الهاتف:',
        'ship_info' => 'شحن إلى (Ship To)',
        'company_wh' => 'مخازن المؤسسة',
        'delivery_date' => 'تاريخ التوريد المتوقع:',
        'company_tax' => 'الرقم الضريبي للشركة:',
        'notes_terms' => 'ملاحظات وشروط الدفع:',
        'subtotal' => 'الإجمالي الفرعي (Subtotal):',
        'discount' => 'الخصم (Discount):',
        'tax' => 'الضريبة (Tax):',
        'total' => 'الصافي (Total):',
        'sig_prep' => 'إعداد المشتريات',
        'sig_mgr' => 'مدير المشتريات (توقيع وختم)',
        'col_code' => 'كود الصنف',
        'col_desc' => 'الوصف',
        'col_qty' => 'الكمية',
        'col_price' => 'سعر الوحدة',
        'col_total' => 'الإجمالي',
        'unregistered' => '---'
    ],
    'en' => [
        'title' => 'Purchase Order Details (PO)',
        'print' => 'Print Document',
        'edit' => 'Edit',
        'brand_title' => 'Official Purchase Order (PO)',
        'order_date' => 'Order Date',
        'vendor_info' => 'To Vendor / Supplier',
        'tax_no' => 'VAT No.:',
        'phone' => 'Phone:',
        'ship_info' => 'Ship To',
        'company_wh' => 'Company Warehouses',
        'delivery_date' => 'Expected Delivery Date:',
        'company_tax' => 'Company VAT No.:',
        'notes_terms' => 'Notes & Payment Terms:',
        'subtotal' => 'Subtotal:',
        'discount' => 'Discount:',
        'tax' => 'Tax:',
        'total' => 'Total:',
        'sig_prep' => 'Purchasing Officer',
        'sig_mgr' => 'Purchasing Manager Approval',
        'col_code' => 'Item Code',
        'col_desc' => 'Description',
        'col_qty' => 'Qty',
        'col_price' => 'Unit Price',
        'col_total' => 'Total',
        'unregistered' => 'N/A'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-blue: #2563eb; --c-blue-light: #dbeafe; }
    .po-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; background: #0f172a; color: #ffffff; }
    .btn-act:hover { background: #1e293b; }
    .btn-edit { background: var(--c-blue-light); color: var(--c-blue); border: 1px solid #bfdbfe; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none;}

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); border-top: 8px solid var(--c-blue); }
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
    .totals-row { display: flex; justify-content: space-between; padding: 10px 16px; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 700; }
    .totals-row.grand { background: #f8fafc; color: var(--c-blue); font-size: 1.2rem; font-weight: 900; border-bottom: none; }

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
        
        /* 2. إظهار ورقة الأمر فقط */
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
        .sig-box { text-align: center !important; flex: 1 !important; font-weight: 800 !important; font-size: 0.9rem !important; color: #000 !important; }
        .sig-line { border-top: 1px dashed #000 !important; width: 60% !important; margin: 40px auto 0 auto !important; }
    }
</style>

<div class="po-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="/ERP/purchasing/orders" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <h3 style="margin:0; font-weight: 800; color: #0f172a;"><?= $t['title'] ?></h3>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/ERP/purchasing/orders/<?= $order->id ?>/edit" class="btn-act btn-edit"><i class="ph-bold ph-pencil-simple"></i> <?= $t['edit'] ?></a>
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
                <div style="font-family: monospace; font-size: 1.8rem; font-weight: 900; color: var(--c-blue);"><?= htmlspecialchars($order->po_number) ?></div>
                <div style="margin-top:4px; font-weight:800; color: #0f172a; text-transform: uppercase;"><?= $t['order_date'] ?>: <?= $order->order_date ?></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5><?= $t['vendor_info'] ?></h5>
                <p style="font-size: 1.1rem; color: var(--c-blue);"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars($order->supplier_name ?? '---') ?></p>
                <p><span style="color:#64748b;"><?= $t['tax_no'] ?></span> <?= htmlspecialchars($order->supplier_tax ?? '---') ?></p>
                <p><span style="color:#64748b;"><?= $t['phone'] ?></span> <span style="font-family: monospace;"><?= htmlspecialchars($order->supplier_phone ?? '---') ?></span></p>
            </div>
            <div class="info-box">
                <h5><?= $t['ship_info'] ?></h5>
                <p><?= $t['company_wh'] ?></p>
                <p><span style="color:#64748b;"><?= $t['delivery_date'] ?></span> <span style="color:#dc2626; font-weight:800;"><?= $order->delivery_date ?></span></p>
                <p><span style="color:#64748b;"><?= $t['company_tax'] ?></span> 300000000000003</p>
            </div>
        </div>

        <table class="table-print">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 15%;"><?= $t['col_code'] ?></th>
                    <th style="width: 40%;"><?= $t['col_desc'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_qty'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_price'] ?></th>
                    <th style="width: 15%; text-align: end;"><?= $t['col_total'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($items)): foreach($items as $index => $item): ?>
                    <tr>
                        <td style="text-align: center; color: #94a3b8; font-weight: 800;"><?= $index + 1 ?></td>
                        <td style="font-family: monospace; color: #475569;"><?= htmlspecialchars($item->product_code ?? $t['unregistered']) ?></td>
                        <td><?= htmlspecialchars($item->description) ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 800;"><?= number_format($item->quantity, 2) ?></td>
                        <td style="text-align: center; font-family: monospace;"><?= number_format($item->unit_price, 2) ?></td>
                        <td style="text-align: end; font-family: monospace; font-weight: 900; color: #0f172a;"><?= number_format($item->total_price, 2) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <?php if(!empty($order->notes)): ?>
            <div style="margin-bottom: 24px; color: #334155; font-size: 0.9rem; font-weight: 600; padding: 10px; border: 1px dashed #cbd5e1; border-radius: 8px;">
                <strong style="color: var(--c-blue);"><?= $t['notes_terms'] ?></strong> <br><?= nl2br(htmlspecialchars($order->notes)) ?>
            </div>
        <?php endif; ?>

        <div class="totals-container">
            <div class="totals-area">
                <div class="totals-row">
                    <span><?= $t['subtotal'] ?></span>
                    <span style="font-family: monospace;"><?= number_format($order->subtotal, 2) ?></span>
                </div>
                <div class="totals-row" style="color:#ea580c;">
                    <span><?= $t['discount'] ?></span>
                    <span style="font-family: monospace;">- <?= number_format($order->discount_amount, 2) ?></span>
                </div>
                <div class="totals-row">
                    <span><?= $t['tax'] ?></span>
                    <span style="font-family: monospace;">+ <?= number_format($order->tax_amount, 2) ?></span>
                </div>
                <div class="totals-row grand">
                    <span><?= $t['total'] ?></span>
                    <span style="font-family: monospace;"><?= number_format($order->total_amount, 2) ?> <?= $currency ?></span>
                </div>
            </div>
        </div>

        <div class="print-signatures">
            <div class="sig-box">
                <div><?= $t['sig_prep'] ?></div>
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