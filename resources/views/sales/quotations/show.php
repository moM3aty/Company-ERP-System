<?php
// Path: resources/views/sales/quotations/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = isRtl();
$currency = current_currency();
$dir = $isAr ? 'rtl' : 'ltr';

$companyName = current_company_name();
$branchName  = $_SESSION['active_branch_name'] ?? ($isAr ? 'الفرع الرئيسي' : 'Main Branch');

if (!isset($customer) || !$customer) {
    $customer = (object)[
        'name_ar' => '',
        'name_en' => '',
        'tax_number' => '---',
        'phone' => '---',
        'email' => '---'
    ];
}

$custName = $isAr ? ($customer->name_ar ?? $customer->name_en) : ($customer->name_en ?? $customer->name_ar);
if (empty(trim((string)$custName))) $custName = $isAr ? 'عميل عام' : 'General Customer';

$statusMap = [
    'draft' => ['bg' => '#f1f5f9', 'text' => '#475569', 'border' => '#cbd5e1', 'label' => $isAr ? 'مسودة' : 'DRAFT'],
    'sent' => ['bg' => '#eff6ff', 'text' => '#2563eb', 'border' => '#bfdbfe', 'label' => $isAr ? 'تم الإرسال' : 'SENT'],
    'accepted' => ['bg' => '#ecfdf5', 'text' => '#059669', 'border' => '#a7f3d0', 'label' => $isAr ? 'مقبول' : 'ACCEPTED'],
    'rejected' => ['bg' => '#fef2f2', 'text' => '#dc2626', 'border' => '#fecaca', 'label' => $isAr ? 'مرفوض' : 'REJECTED']
];

$statusKey = isset($quotation->status) ? strtolower($quotation->status) : 'draft';
$stUI = $statusMap[$statusKey] ?? $statusMap['draft'];
$quoteNumber = $quotation->quote_number ?? '---';
$issueDate = $quotation->issue_date ?? date('Y-m-d');
$expiryDate = $quotation->expiry_date ?? '---';
?>

<style>
    .quote-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .quote-top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .quote-top-bar .title-group { display: flex; align-items: center; gap: 12px; }
    .back-btn { width: 38px; height: 38px; border-radius: 10px; background: #fff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .back-btn:hover { background: #f8fafc; color: #0f172a; }
    
    .action-btns { display: flex; gap: 10px; }
    .btn-act { padding: 9px 18px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; font-family: inherit; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none; transition: 0.2s; }
    .btn-act-light { background: #ffffff; border: 1px solid #cbd5e1; color: #334155; }
    .btn-act-light:hover { background: #f8fafc; border-color: #94a3b8; }
    .btn-act-primary { background: #9333ea; color: #ffffff; box-shadow: 0 4px 12px rgba(147, 51, 234, 0.25); }
    .btn-act-primary:hover { background: #7e22ce; }

    .quote-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); overflow: hidden; padding: 40px; }
    
    .canvas-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 28px; margin-bottom: 28px; }
    .brand-title { margin: 0; font-size: 1.6rem; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; }
    .brand-sub { margin: 4px 0 0 0; color: #64748b; font-size: 0.85rem; font-weight: 600; }
    
    .doc-meta { text-align: <?= $isAr ? 'left' : 'right' ?>; }
    .doc-type-tag { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #9333ea; letter-spacing: 1px; margin-bottom: 4px; display: block; }
    .doc-number { font-family: monospace; font-size: 1.6rem; font-weight: 900; color: #0f172a; margin: 0; }
    .status-badge { padding: 4px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 800; display: inline-block; margin-top: 8px; border: 1px solid <?= $stUI['border'] ?>; background: <?= $stUI['bg'] ?>; color: <?= $stUI['text'] ?>; }

    .print-only-header { display: none; }

    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px; background: #f8fafc; padding: 20px 24px; border-radius: 12px; border: 1px solid #f1f5f9; }
    .info-box h5 { margin: 0 0 10px 0; color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-box .name { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
    .info-box p { margin: 3px 0; color: #475569; font-size: 0.88rem; font-weight: 500; }

    .table-responsive-wrapper { overflow-x: auto; margin-bottom: 28px; }
    .quote-table { width: 100%; border-collapse: collapse; }
    .quote-table th { background: #0f172a; color: #ffffff; padding: 12px 16px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: start; }
    .quote-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.92rem; vertical-align: middle; }
    
    .summary-section { display: flex; justify-content: space-between; align-items: flex-start; gap: 32px; margin-top: 20px; }
    .notes-card { flex-grow: 1; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; font-size: 0.88rem; color: #475569; }
    .notes-card strong { color: #0f172a; display: block; margin-bottom: 4px; }
    
    .totals-box { width: 320px; flex-shrink: 0; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; }
    .tot-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.88rem; color: #64748b; font-weight: 600; }
    .tot-row.grand { border-top: 2px solid #0f172a; padding-top: 10px; margin-top: 6px; font-size: 1.2rem; font-weight: 900; color: #9333ea; }
    .tot-row .val { font-family: monospace; font-weight: 800; color: #0f172a; }

    .print-signatures { display: none; }

    /* ==========================================
       إعدادات الطباعة الاحترافية الصارمة 
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
        
        .quote-wrapper {
            padding: 15mm !important;
            max-width: 100% !important; 
            width: 100% !important; 
            margin: 0 !important; 
            box-sizing: border-box !important;
        }

        .nt-navbar, .nt-sidebar, .nav-search-box, header, nav, footer, aside, .sidebar-overlay, .mobile-sidebar-toggle {
            display: none !important;
            height: 0 !important;
            opacity: 0 !important;
            visibility: hidden !important;
        }

        .quote-top-bar, .no-print, .canvas-header { display: none !important; }
        
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

        .table-responsive-wrapper, .dataTable-wrapper, .dataTable-container {
            overflow: visible !important;
            max-height: none !important;
            height: auto !important;
            border: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .quote-canvas { 
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
        
        .print-only-header { 
            display: flex !important; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 2px solid #0f172a; 
            padding-bottom: 15px; 
            margin-bottom: 20px; 
        }
        .print-only-header h1 { font-size: 20pt !important; margin:0 !important; color:#000 !important; font-weight:900 !important;}
        .print-only-header h2 { font-size: 14pt !important; margin:0 !important; color:#000 !important;}
        .print-only-header p { font-size: 10pt !important; margin:4px 0 0 0 !important; color:#000 !important;}
        
        .info-grid { 
            display: flex !important; 
            justify-content: space-between !important; 
            background: #fff !important; 
            border: 1px solid #000 !important; 
            padding: 12px 15px !important; 
            margin-bottom: 20px !important;
            border-radius: 0 !important;
            page-break-inside: avoid !important;
        }
        .info-box { width: 48% !important; }
        .info-box h5 { color: #000 !important; font-size: 10pt !important; border-bottom: 1px solid #ccc !important; padding-bottom: 4px !important; margin-bottom: 8px !important; }
        .info-box p { color: #000 !important; font-size: 9.5pt !important; margin: 3px 0 !important; }
        .info-box .name { font-size: 12pt !important; font-weight: bold !important; color: #000 !important; }

        .quote-table, .dataTable-table { 
            border: 1px solid #000 !important; 
            margin-bottom: 20px !important; 
            width: 100% !important;
        }
        .quote-table th, .dataTable-table th { 
            background: #f0f0f0 !important; 
            color: #000 !important; 
            font-size: 9.5pt !important; 
            padding: 8px !important; 
            border: 1px solid #000 !important;
        }
        .quote-table td, .dataTable-table td { 
            border: 1px solid #000 !important; 
            font-size: 9.5pt !important; 
            padding: 8px !important; 
            color: #000 !important;
        }
        .quote-table tr, .dataTable-table tr { page-break-inside: avoid !important; }

        .totals-wrapper { justify-content: flex-end !important; display: flex !important; }
        .totals-box { border: 1px solid #000 !important; background: #fff !important; width: 250px !important; padding: 10px !important; border-radius: 0 !important; margin-top: 10px !important;}
        .totals-row { font-size: 9.5pt !important; color: #000 !important; margin-bottom: 5px !important;}
        .totals-row.grand { border-top: 2px solid #000 !important; color: #000 !important; font-size: 11pt !important;}

        .notes-card { background: #fff !important; border: 1px solid #000 !important; color: #000 !important; padding: 10px !important; margin-top: 20px !important;}

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

<div class="quote-wrapper" dir="<?= $dir ?>">
    
    <!-- Top Action Bar -->
    <div class="quote-top-bar no-print">
        <div class="title-group">
            <a href="/ERP/sales/quotations" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h3 style="margin:0; font-size: 1.3rem; font-weight: 800; color: #0f172a;"><?= $isAr ? 'عرض سعر' : 'Quotation Details' ?></h3>
            </div>
        </div>
        <div class="action-btns">
            <button type="button" onclick="window.print()" class="btn-act btn-act-light"><i class="ph-bold ph-printer"></i> <?= $isAr ? 'طباعة' : 'Print' ?></button>
            <?php if(isset($quotation->id)): ?>
                <a href="/ERP/sales/quotations/<?= $quotation->id ?>/edit" class="btn-act btn-act-primary"><i class="ph-bold ph-pencil-simple"></i> <?= $isAr ? 'تعديل' : 'Edit' ?></a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Paper Canvas -->
    <div class="quote-canvas">
        
        <!-- Header for Web -->
        <div class="canvas-header">
            <div>
                <h1 class="brand-title"><?= htmlspecialchars($companyName) ?></h1>
                <p class="brand-sub"><?= $isAr ? 'نظام إدارة المبيعات وعروض الأسعار' : 'Sales and Quotations Management' ?></p>
            </div>
            <div class="doc-meta">
                <span class="doc-type-tag"><?= $isAr ? 'عرض سعر رسمـي' : 'OFFICIAL QUOTATION' ?></span>
                <h2 class="doc-number"><?= htmlspecialchars($quoteNumber) ?></h2>
                <span class="status-badge"><?= $stUI['label'] ?></span>
            </div>
        </div>

        <!-- Header for Print -->
        <div class="print-only-header">
            <div>
                <h1><?= $isAr ? 'عرض سعر' : 'QUOTATION' ?></h1>
                <p><?= $isAr ? 'رقم الوثيقة:' : 'Document #:' ?> <?= htmlspecialchars($quoteNumber) ?></p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <h2><?= htmlspecialchars($companyName) ?></h2>
                <p><?= htmlspecialchars($branchName) ?></p>
                <p><?= htmlspecialchars($issueDate) ?></p>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-box">
                <h5><?= $isAr ? 'مُصدر العرض (الشركة)' : 'Quoted From' ?></h5>
                <div class="name"><?= htmlspecialchars($companyName) ?></div>
                <p><strong><?= $isAr ? 'الفرع' : 'Branch' ?>:</strong> <?= htmlspecialchars($branchName) ?></p>
                <p><strong><?= $isAr ? 'الرقم الضريبي' : 'Tax No' ?>:</strong> 300000000000003</p>
            </div>
            <div class="info-box">
                <h5><?= $isAr ? 'مُوجه إلى (العميل)' : 'Quoted To' ?></h5>
                <div class="name"><?= htmlspecialchars($custName) ?></div>
                <p><strong><?= $isAr ? 'الرقم الضريبي' : 'Tax No' ?>:</strong> <?= htmlspecialchars($customer->tax_number ?? '---') ?></p>
                <p><strong><?= $isAr ? 'الهاتف' : 'Phone' ?>:</strong> <span dir="ltr"><?= htmlspecialchars($customer->phone ?? '---') ?></span></p>
                <p><strong><?= $isAr ? 'تاريخ الإصدار' : 'Issue Date' ?>:</strong> <?= htmlspecialchars($issueDate) ?></p>
                <p><strong><?= $isAr ? 'تاريخ الصلاحية' : 'Expiry Date' ?>:</strong> <span style="color:#9333ea; font-weight:bold;"><?= htmlspecialchars($expiryDate) ?></span></p>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-responsive-wrapper">
            <table class="quote-table no-datatable" id="quoteItemsTable">
                <thead>
                    <tr>
                        <th style="width: 45%;"><?= $isAr ? 'الصنف / الوصف' : 'Item Description' ?></th>
                        <th style="width: 15%; text-align: center;"><?= $isAr ? 'الكمية' : 'Qty' ?></th>
                        <th style="width: 20%; text-align: end;"><?= $isAr ? 'سعر الوحدة' : 'Unit Price' ?> (<?= htmlspecialchars($currency) ?>)</th>
                        <th style="width: 20%; text-align: end;"><?= $isAr ? 'الإجمالي' : 'Total' ?> (<?= htmlspecialchars($currency) ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($lines)): foreach($lines as $line): ?>
                        <tr>
                            <td><div style="font-weight: 800; color: #0f172a; font-size: 0.95rem;"><?= htmlspecialchars($line->description) ?></div></td>
                            <td style="text-align: center; font-weight: 800; font-family: monospace; color: #9333ea;"><?= number_format($line->quantity ?? 0, 2) ?></td>
                            <td style="text-align: end; font-weight: 700; font-family: monospace; color: #64748b;"><?= number_format(convert_amount($line->unit_price ?? 0), 2) ?></td>
                            <td style="text-align: end; font-weight: 900; font-family: monospace; color: #0f172a;"><?= number_format(convert_amount($line->total ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" style="text-align: center; padding: 24px; color: #94a3b8;"><?= $isAr ? 'لا توجد عناصر مسجلة.' : 'No items found.' ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Summary -->
        <div class="summary-section">
            <div class="notes-card">
                <strong><?= $isAr ? 'الشروط والأحكام:' : 'Terms & Notes:' ?></strong>
                <?= !empty($quotation->notes) ? nl2br(htmlspecialchars($quotation->notes)) : ($isAr ? 'عرض السعر هذا ساري حتى تاريخ الصلاحية الموضح أعلاه.' : 'This quotation is valid until the expiry date mentioned above.') ?>
            </div>
            
            <div class="totals-box">
                <div class="tot-row">
                    <span><?= $isAr ? 'المجموع الفرعي' : 'Subtotal' ?></span>
                    <span class="val"><?= number_format(convert_amount($quotation->subtotal ?? 0), 2) ?> <?= htmlspecialchars($currency) ?></span>
                </div>
                <div class="tot-row">
                    <span><?= $isAr ? 'ضريبة القيمة المضافة (15%)' : 'VAT (15%)' ?></span>
                    <span class="val"><?= number_format(convert_amount($quotation->tax_amount ?? 0), 2) ?> <?= htmlspecialchars($currency) ?></span>
                </div>
                <div class="tot-row grand">
                    <span><?= $isAr ? 'الإجمالي النهائي' : 'Grand Total' ?></span>
                    <span class="val" style="color: #9333ea;"><?= number_format(convert_amount($quotation->total_amount ?? 0), 2) ?> <?= htmlspecialchars($currency) ?></span>
                </div>
            </div>
        </div>

        <!-- التوقيعات عند الطباعة -->
        <div class="print-signatures">
            <div class="sig-box">
                <div><?= $isAr ? 'مسؤول المبيعات' : 'Sales Rep' ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $isAr ? 'مدير المبيعات' : 'Sales Manager' ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $isAr ? 'موافقة العميل' : 'Customer Acceptance' ?></div>
                <div class="sig-line"></div>
            </div>
        </div>

    </div>
</div>