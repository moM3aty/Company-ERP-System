<?php
// Path: resources/views/sales/orders/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = isRtl();
$currency = current_currency();
$dir = $isAr ? 'rtl' : 'ltr';

$companyName = current_company_name();
$branchName  = $_SESSION['active_branch_name'] ?? ($isAr ? 'الفرع الرئيسي' : 'Main Branch');

$t = [
    'ar' => [
        'title' => 'تفاصيل أمر البيع',
        'order_info' => 'معلومات الطلب',
        'order_no' => 'رقم الأمر:',
        'order_date' => 'تاريخ الطلب:',
        'exp_date' => 'التسليم المتوقع:',
        'status' => 'الحالة:',
        'customer_info' => 'بيانات العميل',
        'customer_name' => 'اسم العميل:',
        'phone' => 'رقم الهاتف:',
        'email' => 'البريد الإلكتروني:',
        'lines_title' => 'المنتجات المطلوبة',
        'col_prod' => 'المنتج / الوصف',
        'col_qty' => 'الكمية',
        'col_price' => 'سعر الوحدة',
        'col_total' => 'الإجمالي',
        'subtotal' => 'المجموع الفرعي',
        'tax' => 'ضريبة القيمة المضافة (15%)',
        'grand_total' => 'الإجمالي النهائي',
        'notes' => 'ملاحظات إضافية:',
        'back' => 'العودة',
        'edit' => 'تعديل',
        'print' => 'طباعة الأمر'
    ],
    'en' => [
        'title' => 'Sales Order Details',
        'order_info' => 'Order Information',
        'order_no' => 'Order No:',
        'order_date' => 'Order Date:',
        'exp_date' => 'Expected Delivery:',
        'status' => 'Status:',
        'customer_info' => 'Customer Details',
        'customer_name' => 'Customer Name:',
        'phone' => 'Phone:',
        'email' => 'Email:',
        'lines_title' => 'Ordered Items',
        'col_prod' => 'Product / Description',
        'col_qty' => 'Quantity',
        'col_price' => 'Unit Price',
        'col_total' => 'Total',
        'subtotal' => 'Subtotal',
        'tax' => 'VAT (15%)',
        'grand_total' => 'Grand Total',
        'notes' => 'Additional Notes:',
        'back' => 'Back',
        'edit' => 'Edit',
        'print' => 'Print Order'
    ]
][$isAr ? 'ar' : 'en'];

$statusColors = [
    'draft' => ['bg' => '#f1f5f9', 'text' => '#475569'],
    'confirmed' => ['bg' => '#e0f2fe', 'text' => '#0284c7'],
    'processing' => ['bg' => '#fef3c7', 'text' => '#d97706'],
    'shipped' => ['bg' => '#f3e8ff', 'text' => '#9333ea'],
    'delivered' => ['bg' => '#ecfdf5', 'text' => '#059669'],
    'cancelled' => ['bg' => '#fef2f2', 'text' => '#dc2626']
];
$currStatus = strtolower($order->status ?? 'draft');
$statusColor = $statusColors[$currStatus] ?? $statusColors['draft'];
?>

<style>
    .show-wrapper { max-width: 1000px; margin: 0 auto; padding-bottom: 40px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;}
    .title-box { display: flex; align-items: center; gap: 16px; }
    .page-title { margin: 0; font-size: 1.8rem; font-weight: 800; color: #0f172a; }
    
    .btn { padding: 10px 20px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; border: none; cursor: pointer; }
    .btn-light { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .btn-light:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; }
    .btn-primary { background: linear-gradient(135deg, #10b981, #059669); color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35); }

    .doc-card { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); overflow: hidden; }
    
    .print-header { display: none; }

    .doc-header { padding: 32px; border-bottom: 1px solid #f1f5f9; display: grid; grid-template-columns: 1fr 1fr; gap: 32px; background: #f8fafc; }
    @media(max-width: 640px) { .doc-header { grid-template-columns: 1fr; } }
    
    .info-block h4 { margin: 0 0 16px 0; color: #0f172a; font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: 8px; }
    .info-row { display: flex; margin-bottom: 10px; font-size: 0.95rem; }
    .info-label { width: 140px; color: #64748b; font-weight: 600; flex-shrink: 0; }
    .info-value { color: #0f172a; font-weight: 700; }
    
    .badge { padding: 4px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; background: <?= $statusColor['bg'] ?>; color: <?= $statusColor['text'] ?>; border: 1px solid <?= $statusColor['text'] ?>33; }

    .doc-body { padding: 32px; }
    
    .table-responsive-wrapper { overflow-x: auto; margin-bottom: 32px; }
    .items-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
    .items-table th { background: #f1f5f9; padding: 14px 16px; text-align: start; color: #475569; font-weight: 800; border-bottom: 2px solid #cbd5e1; }
    .items-table td { padding: 16px; border-bottom: 1px solid #e2e8f0; color: #334155; vertical-align: top; }
    .items-table tr:last-child td { border-bottom: none; }

    .totals-wrapper { display: flex; justify-content: flex-end; }
    .totals-box { width: 100%; max-width: 350px; background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; }
    .totals-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-weight: 600; color: #475569; font-size: 0.95rem; }
    .totals-row.grand { border-top: 2px solid #cbd5e1; padding-top: 12px; margin-bottom: 0; font-size: 1.25rem; font-weight: 900; color: #0f172a; }

    .notes-box { margin-top: 32px; padding: 16px; background: #fffbeb; border-radius: 8px; border: 1px solid #fde68a; color: #92400e; font-size: 0.9rem; font-weight: 500; }
    .print-signatures { display: none; }

    /* ==========================================
       إعدادات الطباعة الاحترافية الصارمة للقضاء على DataTables
       ========================================== */
    @media print {
        @page { size: A4 portrait; margin: 0; }
        
        html, body { 
            width: 100% !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important; 
            color: #000000 !important; 
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important; 
            font-size: 10pt !important;
            overflow: visible !important; 
        }
        
        .show-wrapper {
            padding: 15mm !important;
            max-width: 100% !important; 
            width: 100% !important; 
            margin: 0 !important; 
            box-sizing: border-box !important;
        }

        /* إخفاء القوائم والأشرطة والمودال */
        .nt-navbar, .nt-sidebar, .nav-search-box, header, nav, footer, aside, .sidebar-overlay, .mobile-sidebar-toggle {
            display: none !important;
            height: 0 !important;
            opacity: 0 !important;
            visibility: hidden !important;
        }

        .header-actions, .no-print { display: none !important; }
        
        /* 
           الكود السحري لإخفاء إضافات الجداول التفاعلية (DataTables & Grids)
           التي تولد الـ Search والـ Pagination
        */
        .dataTables_filter, .dataTables_length, .dataTables_info, .dataTables_paginate,
        .dt-search, .dt-paging, .dt-info, .dt-length, .dt-buttons,
        .table-filter, .grid-filter, .grid-search, .grid-pagination, .pagination,
        [id*="filter"], [id*="search"], [class*="paginate"], [class*="pagination"],
        input[type="search"], [placeholder*="search"], [placeholder*="Search"],
        .dataTable-top, .dataTable-bottom, .dataTable-info, .dataTable-dropdown {
            display: none !important;
            height: 0 !important;
            opacity: 0 !important;
            visibility: hidden !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* فك أي Scroll حول الجدول */
        .table-responsive-wrapper, .dataTable-wrapper, .dataTable-container {
            overflow: visible !important;
            max-height: none !important;
            height: auto !important;
            border: none !important;
        }

        .main-content, .doc-card { 
            max-width: 100% !important; 
            width: 100% !important; 
            margin: 0 !important; 
            padding: 0 !important; 
            box-shadow: none !important; 
            border: none !important; 
            border-radius: 0 !important;
            overflow: visible !important;
            height: auto !important;
        }
        
        .print-header { 
            display: flex !important; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 2px solid #0f172a; 
            padding-bottom: 15px; 
            margin-bottom: 20px; 
        }
        .print-header h1 { font-size: 20pt !important; margin:0 !important; color:#000 !important; font-weight:900 !important;}
        .print-header h2 { font-size: 14pt !important; margin:0 !important; color:#000 !important;}
        .print-header p { font-size: 10pt !important; margin:4px 0 0 0 !important; color:#000 !important;}
        
        .doc-header { background: #ffffff !important; border: 1px solid #cbd5e1 !important; border-radius: 8px !important; padding: 15px !important; margin-bottom: 20px !important;}
        .info-block h4 { font-size: 11pt !important; color: #000 !important; border-bottom: 1px solid #cbd5e1 !important; padding-bottom: 5px !important; margin-bottom: 10px !important; }
        .info-row { font-size: 9.5pt !important; margin-bottom: 5px !important;}
        .info-label { color: #000 !important; }
        .info-value { color: #000 !important; }

        .doc-body { padding: 0 !important; }
        
        .items-table, .dataTable-table { border: 1px solid #000 !important; margin-bottom: 20px !important; width: 100% !important;}
        .items-table th, .dataTable-table th { background: #f0f0f0 !important; color: #000 !important; border: 1px solid #000 !important; padding: 8px !important; font-size: 9.5pt !important;}
        .items-table td, .dataTable-table td { border: 1px solid #000 !important; color: #000 !important; padding: 8px !important; font-size: 9.5pt !important;}
        .items-table tr, .dataTable-table tr { page-break-inside: avoid !important; }

        .totals-wrapper { justify-content: flex-end !important; display: flex !important; }
        .totals-box { border: 1px solid #000 !important; background: #fff !important; width: 250px !important; padding: 10px !important; border-radius: 0 !important; margin-top: 10px !important;}
        .totals-row { font-size: 9.5pt !important; color: #000 !important; margin-bottom: 5px !important;}
        .totals-row.grand { border-top: 2px solid #000 !important; color: #000 !important; font-size: 11pt !important;}

        .notes-box { background: #fff !important; border: 1px solid #000 !important; color: #000 !important; padding: 10px !important; margin-top: 20px !important;}

        .print-signatures { 
            display: flex !important; 
            justify-content: space-between !important; 
            margin-top: 40px !important; 
            padding-top: 15px !important; 
            border-top: 2px dashed #000 !important; 
            page-break-inside: avoid !important; 
        }
        .sig-box { text-align: center !important; flex: 1 !important; font-size: 10pt !important; font-weight: bold !important; color: #000 !important; }
        .sig-line { border-top: 1px dashed #000 !important; width: 60% !important; margin: 30px auto 0 auto !important; }
    }
</style>

<div class="show-wrapper" dir="<?= $dir ?>">
    
    <div class="header-actions">
        <div class="title-box">
            <a href="/ERP/sales/orders" class="btn btn-light" style="padding: 10px; border-radius: 10px;"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>" style="font-size: 1.2rem;"></i></a>
            <h2 class="page-title"><?= $t['title'] ?></h2>
        </div>
        <div style="display: flex; gap: 12px;">
            <button onclick="window.print()" class="btn btn-light"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
            <a href="/ERP/sales/orders/<?= $order->id ?>/edit" class="btn btn-primary"><i class="ph-bold ph-pencil-simple"></i> <?= $t['edit'] ?></a>
        </div>
    </div>

    <div class="doc-card">
        
        <!-- ترويسة الطباعة -->
        <div class="print-header">
            <div>
                <h1><?= $isAr ? 'أمر بيع' : 'SALES ORDER' ?></h1>
                <p><?= $isAr ? 'رقم الوثيقة:' : 'Document #:' ?> <?= htmlspecialchars($order->order_no) ?></p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <h2><?= htmlspecialchars($companyName) ?></h2>
                <p><?= htmlspecialchars($branchName) ?></p>
                <p><?= htmlspecialchars($order->order_date) ?></p>
            </div>
        </div>

        <!-- Header Info -->
        <div class="doc-header">
            <div class="info-block">
                <h4><i class="ph-duotone ph-receipt text-emerald-600"></i> <?= $t['order_info'] ?></h4>
                <div class="info-row"><div class="info-label"><?= $t['order_no'] ?></div> <div class="info-value" style="color: #059669; font-family: monospace; font-size: 1.1rem;"><?= htmlspecialchars($order->order_no) ?></div></div>
                <div class="info-row"><div class="info-label"><?= $t['order_date'] ?></div> <div class="info-value"><?= htmlspecialchars($order->order_date) ?></div></div>
                <div class="info-row"><div class="info-label"><?= $t['exp_date'] ?></div> <div class="info-value"><?= htmlspecialchars($order->expected_date ?? 'N/A') ?></div></div>
                <div class="info-row"><div class="info-label"><?= $t['status'] ?></div> <div class="info-value"><span class="badge"><?= htmlspecialchars($order->status) ?></span></div></div>
            </div>
            
            <div class="info-block">
                <h4><i class="ph-duotone ph-user text-emerald-600"></i> <?= $t['customer_info'] ?></h4>
                <?php $custName = $isAr ? ($customer->name_ar ?? $customer->name_en) : ($customer->name_en ?? $customer->name_ar); ?>
                <div class="info-row"><div class="info-label"><?= $t['customer_name'] ?></div> <div class="info-value"><?= htmlspecialchars($custName ?? 'Unknown') ?></div></div>
                <div class="info-row"><div class="info-label"><?= $t['phone'] ?></div> <div class="info-value" dir="ltr" style="text-align: <?= $isAr ? 'right' : 'left' ?>;"><?= htmlspecialchars($customer->phone ?? 'N/A') ?></div></div>
                <div class="info-row"><div class="info-label"><?= $t['email'] ?></div> <div class="info-value"><?= htmlspecialchars($customer->email ?? 'N/A') ?></div></div>
            </div>
        </div>

        <!-- Lines -->
        <div class="doc-body">
            
            <div class="table-responsive-wrapper">
                <!-- أضفت class="no-datatable" لمنع مكتبات الـ JS من تهيئته -->
                <table class="items-table no-datatable" id="orderLinesTable">
                    <thead>
                        <tr>
                            <th style="width: 50%;"><?= $t['col_prod'] ?></th>
                            <th style="width: 15%; text-align: center;"><?= $t['col_qty'] ?></th>
                            <th style="width: 15%; text-align: end;"><?= $t['col_price'] ?> (<?= htmlspecialchars($currency) ?>)</th>
                            <th style="width: 20%; text-align: end;"><?= $t['col_total'] ?> (<?= htmlspecialchars($currency) ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($lines)): foreach($lines as $line): ?>
                            <tr>
                                <td><div style="font-weight: 700;"><?= htmlspecialchars($line->description) ?></div></td>
                                <td style="text-align: center; font-weight: 600;"><?= number_format($line->quantity, 2) ?></td>
                                <td style="text-align: end; font-family: monospace; font-size: 1.05rem;"><?= number_format(convert_amount($line->unit_price), 2) ?></td>
                                <td style="text-align: end; font-family: monospace; font-size: 1.05rem; font-weight: 800; color: #0f172a;"><?= number_format(convert_amount($line->total), 2) ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4" style="text-align: center; color: #94a3b8;">No items found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="totals-wrapper">
                <div class="totals-box">
                    <div class="totals-row"><span><?= $t['subtotal'] ?></span> <span><?= number_format(convert_amount($order->subtotal ?? 0), 2) ?></span></div>
                    <div class="totals-row"><span><?= $t['tax'] ?></span> <span><?= number_format(convert_amount($order->tax_total ?? 0), 2) ?></span></div>
                    <div class="totals-row grand"><span><?= $t['grand_total'] ?></span> <span><?= number_format(convert_amount($order->grand_total ?? 0), 2) ?> <?= htmlspecialchars($currency) ?></span></div>
                </div>
            </div>

            <!-- Notes -->
            <?php if(!empty($order->notes)): ?>
                <div class="notes-box">
                    <strong><?= $t['notes'] ?></strong><br>
                    <?= nl2br(htmlspecialchars($order->notes)) ?>
                </div>
            <?php endif; ?>

            <!-- Signatures -->
            <div class="print-signatures">
                <div class="sig-box">
                    <div><?= $isAr ? 'إعداد المشرف' : 'Prepared By' ?></div>
                    <div class="sig-line"></div>
                </div>
                <div class="sig-box">
                    <div><?= $isAr ? 'إعتماد المبيعات' : 'Sales Approval' ?></div>
                    <div class="sig-line"></div>
                </div>
                <div class="sig-box">
                    <div><?= $isAr ? 'توقيع العميل' : 'Customer Signature' ?></div>
                    <div class="sig-line"></div>
                </div>
            </div>

        </div>
    </div>
</div>