<?php
// Path: resources/views/sales/receipts/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = isRtl();
$currency = current_currency();
$dir = $isAr ? 'rtl' : 'ltr';

$custName = $isAr ? ($receipt->customer_name ?? 'عميل عام') : ($receipt->customer_name ?? 'General Customer');
$companyName = current_company_name();
$branchName  = $_SESSION['active_branch_name'] ?? ($isAr ? 'الفرع الرئيسي' : 'Main Branch');

$methodMap = [
    'cash'          => ['bg' => '#ecfdf5', 'text' => '#059669', 'border' => '#a7f3d0', 'label' => $isAr ? 'نقداً (كاش)' : 'Cash'],
    'bank_transfer' => ['bg' => '#eff6ff', 'text' => '#2563eb', 'border' => '#bfdbfe', 'label' => $isAr ? 'تحويل بنكي' : 'Bank Transfer'],
    'cheque'        => ['bg' => '#f3e8ff', 'text' => '#9333ea', 'border' => '#d8b4fe', 'label' => $isAr ? 'شيك بنكي' : 'Cheque']
];

$methodKey = strtolower($receipt->payment_method ?? 'cash');
$mUI = $methodMap[$methodKey] ?? $methodMap['cash'];
?>

<style>
    .rct-show-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .rct-top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .rct-top-bar .title-group { display: flex; align-items: center; gap: 12px; }
    .back-btn { width: 38px; height: 38px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .back-btn:hover { background: #f8fafc; color: #0f172a; }
    
    .action-btns { display: flex; gap: 10px; }
    .btn-act { padding: 9px 18px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; font-family: inherit; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none; transition: 0.2s; }
    .btn-act-light { background: #ffffff; border: 1px solid #cbd5e1; color: #334155; }
    .btn-act-light:hover { background: #f8fafc; border-color: #94a3b8; }
    .btn-act-dark { background: #0f172a; color: #ffffff; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25); }
    .btn-act-dark:hover { background: #1e293b; }

    .rct-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); overflow: hidden; padding: 40px; }
    
    .canvas-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 24px; }
    .brand-title { margin: 0; font-size: 1.6rem; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; }
    .brand-sub { margin: 4px 0 0 0; color: #64748b; font-size: 0.85rem; font-weight: 600; }
    
    .doc-meta { text-align: <?= $isAr ? 'left' : 'right' ?>; }
    .doc-type-tag { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #059669; letter-spacing: 1px; margin-bottom: 4px; display: block; }
    .doc-number { font-family: monospace; font-size: 1.6rem; font-weight: 900; color: #0f172a; margin: 0; }
    .method-badge { padding: 4px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 800; display: inline-block; margin-top: 8px; border: 1px solid <?= $mUI['border'] ?>; background: <?= $mUI['bg'] ?>; color: <?= $mUI['text'] ?>; }

    .hero-amount-card { background: linear-gradient(135deg, #059669, #047857); color: #ffffff; border-radius: 14px; padding: 20px 28px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; box-shadow: 0 8px 20px -2px rgba(5, 150, 105, 0.25); }
    .hero-amount-label { font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; }
    .hero-amount-sub { font-size: 0.8rem; opacity: 0.8; margin-top: 2px; }
    .hero-amount-val { font-size: 2.2rem; font-weight: 900; font-family: monospace; letter-spacing: -0.5px; }

    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; background: #f8fafc; padding: 20px 24px; border-radius: 12px; border: 1px solid #f1f5f9; }
    .info-box h5 { margin: 0 0 10px 0; color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px; }
    .info-box .name { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
    .info-box p { margin: 4px 0; color: #475569; font-size: 0.88rem; font-weight: 600; display: flex; align-items: center; gap: 8px; }

    .notes-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; font-size: 0.92rem; color: #334155; line-height: 1.6; }
    .notes-card strong { color: #0f172a; font-weight: 800; display: block; margin-bottom: 6px; font-size: 0.85rem; text-transform: uppercase; }

    .print-only-header { display: none; }
    .print-signatures { display: none; }

    /* ========================================= */
    /* Master A4 Print CSS (إلغاء أدوات DataTables والـ Scrollbar) */
    /* ========================================= */
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
        
        .rct-show-wrapper {
            padding: 15mm !important;
            max-width: 100% !important; 
            width: 100% !important; 
            margin: 0 !important; 
            box-sizing: border-box !important;
        }

        .nt-navbar, .nt-sidebar, .nav-search-box, header, nav, footer, aside, .sidebar-overlay, .mobile-sidebar-toggle, .rct-top-bar, .no-print, .canvas-header {
            display: none !important;
            height: 0 !important;
            opacity: 0 !important;
            visibility: hidden !important;
        }

        .dataTables_filter, .dataTables_length, .dataTables_info, .dataTables_paginate,
        .dt-search, .dt-paging, .dt-info, .dt-length, .dt-buttons, .pagination,
        [id*="filter"], [id*="search"], [class*="paginate"], [class*="pagination"] {
            display: none !important;
            height: 0 !important;
            opacity: 0 !important;
            visibility: hidden !important;
        }

        .rct-canvas { 
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

        .hero-amount-card { 
            background: #f8fafc !important; 
            color: #0f172a !important; 
            border: 2px solid #0f172a !important; 
            box-shadow: none !important; 
            padding: 15px !important;
            margin-bottom: 20px !important;
        }
        .hero-amount-val { color: #0f172a !important; font-size: 20pt !important; }

        .info-grid { 
            display: flex !important; 
            justify-content: space-between !important; 
            background: #ffffff !important; 
            border: 1px solid #0f172a !important; 
            padding: 12px 15px !important; 
            border-radius: 0 !important;
            margin-bottom: 20px !important;
            page-break-inside: avoid !important; 
        }
        .info-box { width: 48% !important; }

        .notes-card {
            border: 1px solid #0f172a !important;
            background: #ffffff !important;
            border-radius: 0 !important;
            padding: 12px !important;
        }

        .print-signatures { 
            display: flex !important; 
            justify-content: space-between !important; 
            margin-top: 50px !important; 
            padding-top: 15px !important; 
            border-top: 2px dashed #0f172a !important; 
            page-break-inside: avoid !important; 
        }
        .sig-box { text-align: center !important; flex: 1 !important; font-size: 10pt !important; font-weight: bold !important; color: #000 !important; }
        .sig-line { border-top: 1px dashed #0f172a !important; width: 70% !important; margin: 35px auto 0 auto !important; }
    }
</style>

<div class="rct-show-wrapper" dir="<?= $dir ?>">
    
    <!-- Top Action Bar -->
    <div class="rct-top-bar no-print">
        <div class="title-group">
            <a href="/ERP/sales/receipts" class="back-btn" title="<?= $isAr ? 'رجوع' : 'Back' ?>"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h3 style="margin:0; font-size: 1.3rem; font-weight: 800; color: #0f172a;"><?= $isAr ? 'تفاصيل سند القبض' : 'Receipt Details' ?></h3>
            </div>
        </div>
        <div class="action-btns">
            <button type="button" onclick="window.print()" class="btn-act btn-act-dark"><i class="ph-bold ph-printer"></i> <?= $isAr ? 'طباعة السند' : 'Print Voucher' ?></button>
            <form action="/ERP/sales/receipts/<?= $receipt->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $isAr ? 'هل أنت متأكد من حذف سند القبض هذا؟' : 'Are you sure you want to delete this receipt?' ?>');">
                <button type="submit" class="btn-act btn-act-light" style="color:#dc2626;"><i class="ph-bold ph-trash"></i> <?= $isAr ? 'حذف' : 'Delete' ?></button>
            </form>
        </div>
    </div>

    <!-- Paper Canvas Voucher -->
    <div class="rct-canvas">
        
        <!-- Header for Web -->
        <div class="canvas-header">
            <div>
                <h1 class="brand-title"><?= htmlspecialchars($companyName) ?></h1>
                <p class="brand-sub"><?= $isAr ? 'نظام المقبوضات والسندات المالية' : 'Sales Receipts & Treasury Management' ?></p>
            </div>
            <div class="doc-meta">
                <span class="doc-type-tag"><?= $isAr ? 'سند قبض مالي رسمي' : 'OFFICIAL SALES RECEIPT' ?></span>
                <h2 class="doc-number"><?= htmlspecialchars($receipt->receipt_number) ?></h2>
                <span class="method-badge"><?= $mUI['label'] ?></span>
            </div>
        </div>

        <!-- Header for Print -->
        <div class="print-only-header">
            <div>
                <h1><?= $isAr ? 'سند قبض مالي' : 'SALES RECEIPT' ?></h1>
                <p><?= $isAr ? 'رقم الوثيقة:' : 'Document #:' ?> <?= htmlspecialchars($receipt->receipt_number) ?></p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <h2><?= htmlspecialchars($companyName) ?></h2>
                <p><?= htmlspecialchars($branchName) ?></p>
                <p><?= htmlspecialchars($receipt->receipt_date) ?></p>
            </div>
        </div>

        <!-- Hero Amount Display Banner -->
        <div class="hero-amount-card">
            <div>
                <div class="hero-amount-label"><?= $isAr ? 'إجمالي المبلغ المقبوض' : 'Total Amount Collected' ?></div>
                <div class="hero-amount-sub"><?= $isAr ? 'تم قيد المبلغ لحساب المبيعات والخزينة' : 'Credited to Sales & Treasury' ?></div>
            </div>
            <div class="hero-amount-val"><?= number_format($receipt->amount ?? 0, 2) ?> <span style="font-size: 1rem; font-weight: 700;"><?= htmlspecialchars($currency) ?></span></div>
        </div>

        <!-- Info Grid Panels -->
        <div class="info-grid">
            <div class="info-box">
                <h5><i class="ph-bold ph-user-circle text-emerald-600 no-print"></i> <?= $isAr ? 'استلمنا من السيد / العميل' : 'Received From' ?></h5>
                <div class="name"><?= htmlspecialchars($custName) ?></div>
                <p><i class="ph-bold ph-file-text text-slate-400 no-print"></i> <?= $isAr ? 'الرقم الضريبي:' : 'Tax No:' ?> <span style="font-family: monospace; font-weight: 800; color: #0f172a;"><?= htmlspecialchars($receipt->customer_tax ?? '---') ?></span></p>
                <p><i class="ph-bold ph-phone text-slate-400 no-print"></i> <?= $isAr ? 'الهاتف:' : 'Phone:' ?> <span dir="ltr"><?= htmlspecialchars($receipt->customer_phone ?? '---') ?></span></p>
                <p><i class="ph-bold ph-map-pin text-slate-400 no-print"></i> <?= $isAr ? 'العنوان:' : 'Address:' ?> <?= htmlspecialchars($receipt->customer_address ?? '---') ?></p>
            </div>

            <div class="info-box">
                <h5><i class="ph-bold ph-receipt text-emerald-600 no-print"></i> <?= $isAr ? 'بيانات التخصيص والسداد' : 'Allocation & Meta' ?></h5>
                <p><i class="ph-bold ph-buildings text-slate-400 no-print"></i> <?= $isAr ? 'الفرع:' : 'Branch:' ?> <strong><?= htmlspecialchars($branchName) ?></strong></p>
                <p><i class="ph-bold ph-calendar text-slate-400 no-print"></i> <?= $isAr ? 'تاريخ التحصيل:' : 'Receipt Date:' ?> <strong><?= htmlspecialchars($receipt->receipt_date) ?></strong></p>
                <p><i class="ph-bold ph-link text-slate-400 no-print"></i> <?= $isAr ? 'الفاتورة المربوطة:' : 'Invoice Ref:' ?> <strong style="font-family: monospace; color: #2563eb;"><?= htmlspecialchars($receipt->invoice_number ?? ($isAr ? 'سند عام (غير مربوط)' : 'General Receipt')) ?></strong></p>
                <p><i class="ph-bold ph-hash text-slate-400 no-print"></i> <?= $isAr ? 'رقم المرجع / الشيك:' : 'Ref No / Cheque:' ?> <strong style="font-family: monospace; color: #0f172a;"><?= htmlspecialchars($receipt->reference_no ?? '---') ?></strong></p>
            </div>
        </div>

        <!-- Statement & Notes Card -->
        <div class="notes-card">
            <strong><?= $isAr ? 'وذلك عن (البيان / السبب):' : 'Payment Description / Reason:' ?></strong>
            <?= !empty($receipt->notes) ? nl2br(htmlspecialchars($receipt->notes)) : ($isAr ? 'دفعة مقبوضة لحساب المبيعات والفواتير الضريبية.' : 'Payment collected for sales invoices and accounts receivable.') ?>
        </div>

        <!-- Print Signatures Section -->
        <div class="print-signatures">
            <div class="sig-box">
                <div><?= $isAr ? 'أمين الخزينة / المستلم' : 'Cashier Signature' ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $isAr ? 'إدارة الحسابات' : 'Accountant Approval' ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $isAr ? 'توقيع المسدد (العميل)' : 'Payer Signature' ?></div>
                <div class="sig-line"></div>
            </div>
        </div>

    </div>
</div>