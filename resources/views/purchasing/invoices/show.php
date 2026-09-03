<?php
// Path: resources/views/purchasing/invoices/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';
$currency = current_currency();

$t = [
    'ar' => [
        'title' => 'تفاصيل فاتورة المشتريات',
        'print' => 'طباعة الفاتورة',
        'edit' => 'تعديل',
        'brand_title' => 'فاتورة مشتريات (Purchase Invoice / Bill)',
        'issue_date' => 'تاريخ الإصدار',
        'supplier_info' => 'المورد (Vendor)',
        'sup_bill_no' => 'رقم فاتورة المورد',
        'tax_no' => 'الرقم الضريبي للمورد',
        'due_info' => 'بيانات الاستحقاق والأمر',
        'linked_po' => 'أمر الشراء المرتبط',
        'due_date' => 'تاريخ الاستحقاق',
        'payment_status' => 'حالة السداد',
        'notes' => 'ملاحظات الفاتورة',
        'subtotal' => 'الإجمالي الفرعي',
        'discount' => 'الخصم',
        'tax' => 'الضريبة',
        'net_total' => 'الصافي المطلوب',
        'paid' => 'المبلغ المدفوع',
        'finance_prep' => 'إعداد الإدارة المالية',
        'finance_mgr' => 'اعتماد المدير المالي',
        'col_code' => 'كود الصنف',
        'col_desc' => 'الوصف',
        'col_qty' => 'الكمية',
        'col_price' => 'سعر الوحدة',
        'col_total' => 'الإجمالي',
        'no_po' => 'بدون أمر',
        'unregistered' => 'غير مسجل'
    ],
    'en' => [
        'title' => 'Purchase Invoice Details',
        'print' => 'Print Invoice',
        'edit' => 'Edit',
        'brand_title' => 'Purchase Invoice / Bill',
        'issue_date' => 'Issue Date',
        'supplier_info' => 'Vendor / Supplier',
        'sup_bill_no' => 'Supplier Bill No.',
        'tax_no' => 'Supplier Tax No.',
        'due_info' => 'Due & Order Details',
        'linked_po' => 'Linked PO',
        'due_date' => 'Due Date',
        'payment_status' => 'Payment Status',
        'notes' => 'Invoice Notes',
        'subtotal' => 'Subtotal',
        'discount' => 'Discount',
        'tax' => 'Tax',
        'net_total' => 'Net Total Due',
        'paid' => 'Amount Paid',
        'finance_prep' => 'Finance Dept. Preparation',
        'finance_mgr' => 'Financial Manager Approval',
        'col_code' => 'Item Code',
        'col_desc' => 'Description',
        'col_qty' => 'Qty',
        'col_price' => 'Unit Price',
        'col_total' => 'Total',
        'no_po' => 'No PO',
        'unregistered' => 'Unregistered'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-rose: #e11d48; }
    .inv-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: #0f172a; color: #ffffff; transition: 0.2s;}
    .btn-act:hover { background: #1e293b; }
    .btn-edit { background: #ffe4e6; color: #e11d48; border: 1px solid #fecdd3; }
    .btn-edit:hover { background: #e11d48; color: #ffffff; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s;}
    .back-btn:hover { background: #ffe4e6; color: #e11d48; border-color: #fecdd3;}

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); border-top: 8px solid var(--c-rose); }
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
    .totals-row.grand { background: #f8fafc; color: var(--c-rose); font-size: 1.2rem; font-weight: 900; border-bottom: none; }

    /* ========================================= */
    /* إعدادات الطباعة الشاملة والحجب الإجباري */
    /* ========================================= */
    @media print {
        @page { size: A4 portrait; margin: 15mm; }
        
        /* 1. حجب جميع عناصر الصفحة خارج كارت الفاتورة */
        body * {
            visibility: hidden !important;
        }
        
        /* 2. إظهار ورقة الفاتورة فقط */
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

        /* 3. الإخفاء الجذري لشريط الترقيم المستهدف (table-pagination-nav) وعناصر DataTables */
        .table-pagination-nav,
        .table-pagination-nav *,
        .print-canvas .table-pagination-nav,
        .print-canvas .table-pagination-nav *,
        .print-canvas .dataTables_info,
        .print-canvas .dataTables_paginate,
        .print-canvas .dataTables_length,
        .print-canvas .dataTables_filter,
        .print-canvas .dt-info,
        .print-canvas .dt-paging,
        .print-canvas .dt-search,
        .print-canvas .dt-length,
        .print-canvas .dt-container > .row:last-child,
        .print-canvas .dt-container > .row:first-child,
        .print-canvas div.dt-layout-row,
        .print-canvas .pagination,
        .print-canvas .dataTables_wrapper > div:not(.table-responsive),
        .print-canvas [class*="dataTables_"],
        .print-canvas [class*="dt-"] {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            height: 0 !important;
            width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            position: absolute !important;
            top: -9999px !important;
        }

        /* 4. إخفاء عمود الترقيم (#) من الجدول */
        .table-print th:first-child, .table-print td:first-child { 
            display: none !important; 
        }

        /* 5. تنسيقات الجدول المطبوع */
        .table-print { border: 1px solid #000 !important; page-break-inside: auto !important; margin-bottom: 10px !important;}
        .table-print th { background: #e2e8f0 !important; color: #000 !important; border: 1px solid #000 !important; -webkit-print-color-adjust: exact !important; }
        .table-print td { border: 1px solid #000 !important; color: #000 !important; }
        
        .totals-area { border: 1px solid #000 !important; }
        .totals-row { border-bottom: 1px solid #000 !important; color: #000 !important; }
        .totals-row.grand { border-bottom: none !important; color: #000 !important; background: #e2e8f0 !important; -webkit-print-color-adjust: exact !important;}
        .info-box { border: 1px solid #000 !important; background: transparent !important; page-break-inside: avoid !important; }
    }
</style>

<div class="inv-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="/ERP/purchasing/invoices" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <h3 style="margin:0; font-weight: 800; color: #0f172a;"><?= $t['title'] ?></h3>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/ERP/purchasing/invoices/<?= $invoice->id ?>/edit" class="btn-act btn-edit"><i class="ph-bold ph-pencil-simple"></i> <?= $t['edit'] ?></a>
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
                <div style="font-family: monospace; font-size: 1.8rem; font-weight: 900; color: var(--c-rose);"><?= htmlspecialchars($invoice->invoice_number) ?></div>
                <div style="margin-top:4px; font-weight:800; color: #0f172a; text-transform: uppercase;"><?= $t['issue_date'] ?>: <?= $invoice->invoice_date ?></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5><?= $t['supplier_info'] ?></h5>
                <p style="font-size: 1.1rem; color: var(--c-rose);"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars($invoice->supplier_name ?? '---') ?></p>
                <p><span style="color:#64748b;"><?= $t['sup_bill_no'] ?>:</span> <span style="font-family:monospace;"><?= htmlspecialchars($invoice->supplier_invoice_number ?? '---') ?></span></p>
                <p><span style="color:#64748b;"><?= $t['tax_no'] ?>:</span> <span style="font-family:monospace;"><?= htmlspecialchars($invoice->supplier_tax ?? '---') ?></span></p>
            </div>
            <div class="info-box">
                <h5><?= $t['due_info'] ?></h5>
                <p><span style="color:#64748b;"><?= $t['linked_po'] ?>:</span> <strong style="font-family:monospace;"><?= htmlspecialchars($invoice->po_number ?? $t['no_po']) ?></strong></p>
                <p><span style="color:#64748b;"><?= $t['due_date'] ?>:</span> <span style="color:#dc2626; font-weight:800;"><?= $invoice->due_date ?></span></p>
                <p><span style="color:#64748b;"><?= $t['payment_status'] ?>:</span> <strong style="color:var(--c-rose);"><?= strtoupper($invoice->status) ?></strong></p>
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
                        <td style="text-align: center; font-family: monospace;"><?= number_format(convert_amount($item->unit_price), 2) ?></td>
                        <td style="text-align: end; font-family: monospace; font-weight: 900; color: #0f172a;"><?= number_format(convert_amount($item->total_price), 2) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <?php if(!empty($invoice->notes)): ?>
            <div style="margin-bottom: 24px; color: #334155; font-size: 0.9rem; font-weight: 600; padding: 10px; border: 1px dashed #cbd5e1; border-radius: 8px;">
                <strong style="color: var(--c-rose);"><?= $t['notes'] ?>:</strong> <br><?= nl2br(htmlspecialchars($invoice->notes)) ?>
            </div>
        <?php endif; ?>

        <div class="totals-container">
            <div class="totals-area">
                <div class="totals-row">
                    <span><?= $t['subtotal'] ?>:</span>
                    <span style="font-family: monospace;"><?= number_format(convert_amount($invoice->subtotal), 2) ?></span>
                </div>
                <div class="totals-row" style="color:#ea580c;">
                    <span><?= $t['discount'] ?>:</span>
                    <span style="font-family: monospace;">- <?= number_format(convert_amount($invoice->discount_amount), 2) ?></span>
                </div>
                <div class="totals-row">
                    <span><?= $t['tax'] ?>:</span>
                    <span style="font-family: monospace;">+ <?= number_format(convert_amount($invoice->tax_amount), 2) ?></span>
                </div>
                <div class="totals-row grand">
                    <span><?= $t['net_total'] ?>:</span>
                    <span style="font-family: monospace;"><?= number_format(convert_amount($invoice->total_amount), 2) ?> <span style="font-size: 0.8rem; color:#64748b;"><?= $currency ?></span></span>
                </div>
                <div class="totals-row" style="background:#fff; color:#059669; border-top:1px solid #cbd5e1;">
                    <span><?= $t['paid'] ?>:</span>
                    <span style="font-family: monospace;"><?= number_format(convert_amount($invoice->paid_amount), 2) ?> <span style="font-size: 0.8rem; color:#64748b;"><?= $currency ?></span></span>
                </div>
            </div>
        </div>

        <div style="margin-top: 60px; display: flex; justify-content: space-between; text-align: center; font-weight: 800; font-size: 0.9rem; color: #0f172a; page-break-inside: avoid;">
            <div style="flex: 1;">
                <div><?= $t['finance_prep'] ?></div>
                <div style="border-top: 1px dashed #cbd5e1; width: 60%; margin: 40px auto 0 auto;"></div>
            </div>
            <div style="flex: 1;">
                <div><?= $t['finance_mgr'] ?></div>
                <div style="border-top: 1px dashed #cbd5e1; width: 60%; margin: 40px auto 0 auto;"></div>
            </div>
        </div>
    </div>
</div>

<script>
// دالة مسح وتصفية شريط الترقيم المخصص والـ DataTables نهائياً من الـ DOM
function purgeDataTablesControls() {
    const selectors = [
        '.table-pagination-nav',
        '.dataTables_info', '.dataTables_paginate', '.dataTables_length', '.dataTables_filter',
        '.dt-info', '.dt-paging', '.dt-search', '.dt-length', '.dt-layout-row', '.pagination'
    ];
    selectors.forEach(selector => {
        document.querySelectorAll(selector).forEach(el => el.remove());
    });
}

function safePrint() {
    purgeDataTablesControls();
    window.print();
}

document.addEventListener("DOMContentLoaded", purgeDataTablesControls);
window.addEventListener("beforeprint", purgeDataTablesControls);
</script>