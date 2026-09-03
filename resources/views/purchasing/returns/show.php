<?php
// Path: resources/views/purchasing/returns/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';
$currency = current_currency();

$t = [
    'ar' => [
        'title' => 'تفاصيل مرتجع المشتريات',
        'print' => 'طباعة إشعار الخصم',
        'edit' => 'تعديل',
        'brand_title' => 'إذن مرتجع مشتريات - إشعار خصم (Debit Note)',
        'return_date' => 'تاريخ الإرجاع',
        'supplier_info' => 'إلى المورد (Supplier)',
        'supplier_code' => 'كود المورد:',
        'phone' => 'الهاتف:',
        'invoice_info' => 'بيانات الفاتورة والسبب',
        'linked_inv' => 'الفاتورة المرتبطة:',
        'reason' => 'سبب الإرجاع:',
        'status' => 'الحالة:',
        'notes' => 'ملاحظات إضافية:',
        'subtotal' => 'إجمالي الأصناف المرجعة:',
        'tax_refund' => 'الضريبة المستردة:',
        'net_debit' => 'صافي قيمة الخصم:',
        'no_inv' => 'بدون فاتورة',
        'unspecified' => 'غير محدد',
        'sig_store' => 'توقيع أمين المخزن المسلّم',
        'sig_purchasing' => 'إعداد إدارة المشتريات',
        'sig_vendor' => 'توقيع واستلام مندوب المورد',
        'col_code' => 'كود الصنف',
        'col_desc' => 'الوصف',
        'col_qty' => 'الكمية',
        'col_price' => 'سعر الوحدة',
        'col_total' => 'الإجمالي',
        'unregistered' => '---'
    ],
    'en' => [
        'title' => 'Purchase Return Details',
        'print' => 'Print Debit Note',
        'edit' => 'Edit',
        'brand_title' => 'Purchase Return - Debit Note Document',
        'return_date' => 'Return Date',
        'supplier_info' => 'To Supplier / Vendor',
        'supplier_code' => 'Vendor Code:',
        'phone' => 'Phone:',
        'invoice_info' => 'Invoice & Reason Info',
        'linked_inv' => 'Linked Invoice:',
        'reason' => 'Return Reason:',
        'status' => 'Status:',
        'notes' => 'Additional Remarks:',
        'subtotal' => 'Returned Items Subtotal:',
        'tax_refund' => 'Refunded VAT:',
        'net_debit' => 'Net Debit Amount:',
        'no_inv' => 'No Invoice',
        'unspecified' => 'Unspecified',
        'sig_store' => 'Issuing Storekeeper Signature',
        'sig_purchasing' => 'Purchasing Department',
        'sig_vendor' => 'Vendor Representative Receipt',
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
    :root { --c-red: #dc2626; }
    .ret-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: #0f172a; color: #ffffff; transition: 0.2s; }
    .btn-act:hover { background: #1e293b; }
    .btn-edit { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s;}
    .back-btn:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca;}

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); border-top: 8px solid var(--c-red); }
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
    .totals-row.grand { background: #f8fafc; color: var(--c-red); font-size: 1.2rem; font-weight: 900; border-bottom: none; }

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
        .totals-area { border: 1px solid #000 !important; }

        .print-signatures { display: flex !important; justify-content: space-between !important; margin-top: 60px !important; page-break-inside: avoid !important; }
        .sig-box { text-align: center !important; flex: 1 !important; font-weight: 800 !important; font-size: 0.85rem !important; color: #000 !important; }
        .sig-line { border-top: 1px dashed #000 !important; width: 60% !important; margin: 40px auto 0 auto !important; }
    }
</style>

<div class="ret-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="/ERP/purchasing/returns" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <h3 style="margin:0; font-weight: 800; color: #0f172a;"><?= $t['title'] ?></h3>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/ERP/purchasing/returns/<?= $returnOrder->id ?>/edit" class="btn-act btn-edit"><i class="ph-bold ph-pencil-simple"></i> <?= $t['edit'] ?></a>
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
                <div style="font-family: monospace; font-size: 1.8rem; font-weight: 900; color: var(--c-red);"><?= htmlspecialchars($returnOrder->return_number) ?></div>
                <div style="margin-top:4px; font-weight:800; color: #0f172a; text-transform: uppercase;"><?= $t['return_date'] ?>: <?= $returnOrder->return_date ?></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5><?= $t['supplier_info'] ?></h5>
                <p style="font-size: 1.1rem; color: var(--c-red);"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars($returnOrder->supplier_name ?? '---') ?></p>
                <p><span style="color:#64748b;"><?= $t['supplier_code'] ?></span> <span style="font-family:monospace;"><?= htmlspecialchars($returnOrder->supplier_code ?? '---') ?></span></p>
                <p><span style="color:#64748b;"><?= $t['phone'] ?></span> <?= htmlspecialchars($returnOrder->supplier_phone ?? '---') ?></p>
            </div>
            <div class="info-box">
                <h5><?= $t['invoice_info'] ?></h5>
                <p><span style="color:#64748b;"><?= $t['linked_inv'] ?></span> <strong style="font-family:monospace;"><?= htmlspecialchars($returnOrder->invoice_number ?? $t['no_inv']) ?></strong></p>
                <p><span style="color:#64748b;"><?= $t['reason'] ?></span> <?= htmlspecialchars($returnOrder->reason ?? $t['unspecified']) ?></p>
                <p><span style="color:#64748b;"><?= $t['status'] ?></span> <strong style="color:var(--c-red);"><?= strtoupper($returnOrder->status) ?></strong></p>
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

        <?php if(!empty($returnOrder->notes)): ?>
            <div style="margin-bottom: 24px; color: #334155; font-size: 0.9rem; font-weight: 600; padding: 10px; border: 1px dashed #cbd5e1; border-radius: 8px;">
                <strong style="color: var(--c-red);"><?= $t['notes'] ?></strong> <br><?= nl2br(htmlspecialchars($returnOrder->notes)) ?>
            </div>
        <?php endif; ?>

        <div class="totals-container">
            <div class="totals-area">
                <div class="totals-row">
                    <span><?= $t['subtotal'] ?></span>
                    <span style="font-family: monospace;"><?= number_format($returnOrder->subtotal, 2) ?></span>
                </div>
                <div class="totals-row">
                    <span><?= $t['tax_refund'] ?></span>
                    <span style="font-family: monospace;">+ <?= number_format($returnOrder->tax_amount, 2) ?></span>
                </div>
                <div class="totals-row grand">
                    <span><?= $t['net_debit'] ?></span>
                    <span style="font-family: monospace;"><?= number_format($returnOrder->total_amount, 2) ?> <?= $currency ?></span>
                </div>
            </div>
        </div>

        <div class="print-signatures">
            <div class="sig-box">
                <div><?= $t['sig_store'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $t['sig_purchasing'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $t['sig_vendor'] ?></div>
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