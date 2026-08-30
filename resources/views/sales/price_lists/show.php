<?php
// Path: resources/views/sales/price_lists/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = isRtl();
$currency = current_currency();
$dir = $isAr ? 'rtl' : 'ltr';

$listName = $isAr ? ($priceList->name_ar ?: $priceList->name_en) : ($priceList->name_en ?: $priceList->name_ar);

$companyName = current_company_name();
$branchName  = $_SESSION['active_branch_name'] ?? ($isAr ? 'الفرع الرئيسي' : 'Main Branch');
?>

<style>
    .pl-show-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .pl-top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .pl-top-bar .title-group { display: flex; align-items: center; gap: 12px; }
    .back-btn { width: 38px; height: 38px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .back-btn:hover { background: #f8fafc; color: #0f172a; }
    
    .action-btns { display: flex; gap: 10px; }
    .btn-act { padding: 9px 18px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; font-family: inherit; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none; transition: 0.2s; }
    .btn-act-light { background: #ffffff; border: 1px solid #cbd5e1; color: #334155; }
    .btn-act-light:hover { background: #f8fafc; border-color: #94a3b8; }
    .btn-act-indigo { background: #4338ca; color: #ffffff; box-shadow: 0 4px 12px rgba(67, 56, 202, 0.25); }
    .btn-act-indigo:hover { background: #3730a3; }

    .pl-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); overflow: hidden; padding: 40px; }
    
    .canvas-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 28px; margin-bottom: 28px; }
    .brand-title { margin: 0; font-size: 1.6rem; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; }
    .brand-sub { margin: 4px 0 0 0; color: #64748b; font-size: 0.85rem; font-weight: 600; }
    
    .doc-meta { text-align: <?= $isAr ? 'left' : 'right' ?>; }
    .doc-type-tag { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #4338ca; letter-spacing: 1px; margin-bottom: 4px; display: block; }
    .doc-number { font-family: monospace; font-size: 1.6rem; font-weight: 900; color: #0f172a; margin: 0; }

    .print-only-header { display: none; }

    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px; background: #f8fafc; padding: 20px 24px; border-radius: 12px; border: 1px solid #f1f5f9; }
    .info-box h5 { margin: 0 0 10px 0; color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-box .name { font-size: 1.2rem; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
    .info-box p { margin: 3px 0; color: #475569; font-size: 0.88rem; font-weight: 500; }

    .table-responsive-wrapper { overflow-x: auto; margin-bottom: 28px; }
    .pl-table { width: 100%; border-collapse: collapse; }
    .pl-table th { background: #0f172a; color: #ffffff; padding: 12px 16px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: start; }
    .pl-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.92rem; vertical-align: middle; }

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
        
        .pl-show-wrapper {
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

        .pl-top-bar, .no-print, .canvas-header { display: none !important; }
        
        /* Hiding JS Datatables elements completely */
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

        .pl-canvas { 
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

        .pl-table, .dataTable-table { 
            border: 1px solid #000 !important; 
            margin-bottom: 20px !important; 
            width: 100% !important;
        }
        .pl-table th, .dataTable-table th { 
            background: #f0f0f0 !important; 
            color: #000 !important; 
            font-size: 9.5pt !important; 
            padding: 8px !important; 
            border: 1px solid #000 !important;
        }
        .pl-table td, .dataTable-table td { 
            border: 1px solid #000 !important; 
            font-size: 9.5pt !important; 
            padding: 8px !important; 
            color: #000 !important;
        }
        .pl-table tr, .dataTable-table tr { page-break-inside: avoid !important; }

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

<div class="pl-show-wrapper" dir="<?= $dir ?>">
    
    <!-- Top Action Bar -->
    <div class="pl-top-bar no-print">
        <div class="title-group">
            <a href="/ERP/sales/price-lists" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h3 style="margin:0; font-size: 1.3rem; font-weight: 800; color: #0f172a;"><?= $isAr ? 'تفاصيل قائمة الأسعار' : 'Price List Details' ?></h3>
            </div>
        </div>
        <div class="action-btns">
            <button type="button" onclick="window.print()" class="btn-act btn-act-light"><i class="ph-bold ph-printer"></i> <?= $isAr ? 'طباعة القائمة' : 'Print List' ?></button>
            <a href="/ERP/sales/price-lists/<?= $priceList->id ?>/edit" class="btn-act btn-act-indigo"><i class="ph-bold ph-pencil-simple"></i> <?= $isAr ? 'تعديل' : 'Edit' ?></a>
        </div>
    </div>

    <!-- Paper Canvas -->
    <div class="pl-canvas">
        
        <!-- Header for Web -->
        <div class="canvas-header">
            <div>
                <h1 class="brand-title"><?= htmlspecialchars($companyName) ?></h1>
                <p class="brand-sub">نظام التسعير المالي وسياسات المبيعات</p>
            </div>
            <div class="doc-meta">
                <span class="doc-type-tag"><?= $isAr ? 'قائمة أسعار رسمية' : 'PRICING TIER' ?></span>
                <h2 class="doc-number"><?= htmlspecialchars($priceList->code) ?></h2>
            </div>
        </div>

        <!-- Header for Print -->
        <div class="print-only-header">
            <div>
                <h1><?= $isAr ? 'قائمة أسعار' : 'PRICE LIST' ?></h1>
                <p><?= $isAr ? 'رقم الوثيقة:' : 'Document #:' ?> <?= htmlspecialchars($priceList->code) ?></p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <h2><?= htmlspecialchars($companyName) ?></h2>
                <p><?= htmlspecialchars($branchName) ?></p>
                <p><?= date('Y-m-d') ?></p>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-box">
                <h5><?= $isAr ? 'اسم شريحة التسعير' : 'Price List Title' ?></h5>
                <div class="name"><?= htmlspecialchars($listName) ?></div>
                <p><i class="ph-bold ph-coins text-slate-400 no-print"></i> العملة (Currency): <strong><?= htmlspecialchars($currency) ?></strong></p>
            </div>
            <div class="info-box">
                <h5><?= $isAr ? 'الملاحظات والحالة' : 'Status & Meta' ?></h5>
                <p><i class="ph-bold ph-toggle-left text-slate-400 no-print"></i> الحالة (Status): 
                    <?php if($priceList->is_active): ?>
                        <span style="background:#ecfdf5; color:#059669; padding:2px 8px; border-radius:4px; font-weight:800; font-size:0.8rem;" class="no-print-bg">نشطة (Active)</span>
                    <?php else: ?>
                        <span style="background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:4px; font-weight:800; font-size:0.8rem;" class="no-print-bg">معطلة (Inactive)</span>
                    <?php endif; ?>
                </p>
                <p><i class="ph-bold ph-info text-slate-400 no-print"></i> البيان (Notes): <?= htmlspecialchars($priceList->notes ?? '---') ?></p>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-responsive-wrapper">
            <!-- Adding no-datatable class to avoid JS auto-formatting if applicable -->
            <table class="pl-table no-datatable" id="priceListItemsTable">
                <thead>
                    <tr>
                        <th style="width: 15%;"><?= $isAr ? 'كود الصنف' : 'SKU' ?></th>
                        <th style="width: 45%;"><?= $isAr ? 'اسم المنتج / الوصف' : 'Product Name' ?></th>
                        <th style="width: 15%; text-align: center;"><?= $isAr ? 'الحد الأدنى للكمية' : 'Min Qty' ?></th>
                        <th style="width: 15%; text-align: end;"><?= $isAr ? 'السعر المحدد' : 'Tier Price' ?> (<?= htmlspecialchars($currency) ?>)</th>
                        <th style="width: 10%; text-align: center;"><?= $isAr ? 'الخصم' : 'Discount' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($lines)): foreach($lines as $line): ?>
                        <tr>
                            <td style="font-weight: 800; font-family: monospace; color: #4338ca;"><?= htmlspecialchars($line->sku ?? 'N/A') ?></td>
                            <td><div style="font-weight: 800; color: #0f172a; font-size: 0.95rem;"><?= htmlspecialchars($line->product_name) ?></div></td>
                            <td style="text-align: center; font-weight: 800; font-family: monospace; color: #64748b;"><?= number_format($line->min_quantity, 2) ?></td>
                            <td style="text-align: end; font-weight: 900; font-family: monospace; color: #0f172a; font-size: 1.05rem;"><?= number_format($line->price, 2) ?></td>
                            <td style="text-align: center; font-weight: 800; color: #059669; font-family: monospace;"><?= number_format($line->discount_percentage, 1) ?>%</td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 24px; color: #94a3b8;"><?= $isAr ? 'لا توجد أصناف مسجلة بهذه القائمة.' : 'No items added.' ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Signatures (Visible on A4 Print) -->
        <div class="print-signatures">
            <div class="sig-box">
                <div><?= $isAr ? 'إعداد مسؤل المبيعات' : 'Sales Spec' ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $isAr ? 'اعتماد مدير التسعير' : 'Pricing Manager' ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $isAr ? 'المدير التنفيذي' : 'Executive Approval' ?></div>
                <div class="sig-line"></div>
            </div>
        </div>

    </div>
</div>