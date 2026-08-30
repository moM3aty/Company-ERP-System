<?php
// Path: resources/views/sales/invoices/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = isRtl();
$currency = current_currency();
$dir = $isAr ? 'rtl' : 'ltr';

$custName = $isAr ? ($customer->name_ar ?? $customer->name_en) : ($customer->name_en ?? $customer->name_ar);
if (empty(trim((string)$custName))) $custName = $isAr ? 'عميل نقدي' : 'Cash Customer';

$companyName = current_company_name();
$branchName  = $_SESSION['active_branch_name'] ?? ($isAr ? 'الفرع الرئيسي' : 'Main Branch');

$grandTotal = (float)($invoice->total_amount ?? 0);
$paidAmount = (float)($invoice->paid_amount ?? 0);
$dueAmount = max(0, $grandTotal - $paidAmount);

$statusMap = [
    'draft' => ['bg' => '#f1f5f9', 'text' => '#475569', 'border' => '#cbd5e1', 'label' => $isAr ? 'مسودة' : 'DRAFT'],
    'unpaid' => ['bg' => '#fef2f2', 'text' => '#dc2626', 'border' => '#fecaca', 'label' => $isAr ? 'غير مدفوعة' : 'UNPAID'],
    'partially_paid' => ['bg' => '#fffbeb', 'text' => '#d97706', 'border' => '#fde68a', 'label' => $isAr ? 'مدفوعة جزئياً' : 'PARTIALLY PAID'],
    'paid' => ['bg' => '#ecfdf5', 'text' => '#059669', 'border' => '#a7f3d0', 'label' => $isAr ? 'مدفوعة بالكامل' : 'PAID'],
    'cancelled' => ['bg' => '#f8fafc', 'text' => '#94a3b8', 'border' => '#e2e8f0', 'label' => $isAr ? 'ملغاة' : 'CANCELLED']
];

$st = strtolower($invoice->status ?? 'draft');
$stUI = $statusMap[$st] ?? $statusMap['draft'];
?>

<style>
    /* ========================================= */
    /* Web Layout CSS */
    /* ========================================= */
    .inv-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .inv-top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .inv-top-bar .title-group { display: flex; align-items: center; gap: 12px; }
    .back-btn { width: 38px; height: 38px; border-radius: 10px; background: #fff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .back-btn:hover { background: #f8fafc; color: #0f172a; }
    
    .action-btns { display: flex; gap: 10px; }
    .btn-act { padding: 9px 18px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; font-family: inherit; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none; transition: 0.2s; }
    .btn-act-light { background: #ffffff; border: 1px solid #cbd5e1; color: #334155; }
    .btn-act-light:hover { background: #f8fafc; border-color: #94a3b8; }
    .btn-act-success { background: #10b981; color: #ffffff; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); }
    .btn-act-success:hover { background: #059669; }

    .inv-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); overflow: hidden; padding: 40px; }
    
    .web-only-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 28px; margin-bottom: 28px; }
    .brand-title { margin: 0; font-size: 1.6rem; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; }
    .brand-sub { margin: 4px 0 0 0; color: #64748b; font-size: 0.85rem; font-weight: 600; }
    .doc-meta { text-align: <?= $isAr ? 'left' : 'right' ?>; }
    .doc-type-tag { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #4f46e5; letter-spacing: 1px; margin-bottom: 4px; display: block; }
    .doc-number { font-family: monospace; font-size: 1.6rem; font-weight: 900; color: #0f172a; margin: 0; }
    .status-badge { padding: 4px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 800; display: inline-block; margin-top: 8px; border: 1px solid <?= $stUI['border'] ?>; background: <?= $stUI['bg'] ?>; color: <?= $stUI['text'] ?>; }

    .print-only-header { display: none; }

    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px; background: #f8fafc; padding: 20px 24px; border-radius: 12px; border: 1px solid #f1f5f9; }
    .info-box h5 { margin: 0 0 10px 0; color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-box .name { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
    .info-box p { margin: 3px 0; color: #475569; font-size: 0.88rem; font-weight: 500; }

    .inv-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
    .inv-table th { background: #0f172a; color: #ffffff; padding: 12px 16px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: start; }
    .inv-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.92rem; vertical-align: middle; }
    
    .summary-section { display: flex; justify-content: space-between; align-items: flex-start; gap: 32px; margin-top: 20px; }
    .notes-card { flex-grow: 1; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; font-size: 0.88rem; color: #475569; }
    
    .totals-box { width: 320px; flex-shrink: 0; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; }
    .tot-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.88rem; color: #64748b; font-weight: 600; }
    .tot-row .val { font-family: monospace; font-weight: 800; color: #0f172a; }
    .tot-row.grand { border-top: 2px solid #cbd5e1; padding-top: 10px; margin-top: 6px; font-size: 1.1rem; font-weight: 900; color: #0f172a; }
    .tot-row.paid { color: #059669; font-weight: 700; }
    .tot-row.due { border-top: 2px solid #0f172a; padding-top: 10px; margin-top: 6px; font-size: 1.25rem; font-weight: 900; color: #dc2626; }

    .payments-history { margin-top: 40px; padding-top: 24px; border-top: 2px dashed #e2e8f0; }
    .payments-history h4 { margin: 0 0 16px 0; font-size: 1.05rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
    .pay-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    .pay-table th { background: #f1f5f9; padding: 10px 14px; text-align: start; color: #475569; font-weight: 700; }
    .pay-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }

    .print-signatures { display: none; }

    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 999999; align-items: center; justify-content: center; }
    .modal-card { background: #ffffff; width: 90%; max-width: 450px; border-radius: 16px; padding: 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }

    /* ========================================= */
    /* Master A4 Print CSS (إجبار المتصفح لمنع السكرول والبحث) */
    /* ========================================= */
    @media print {
        @page { size: A4 portrait; margin: 10mm 15mm; }
        
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
            overflow: visible !important; /* إخفاء Scrollbar المزعج */
        }
        
        /* إخفاء شريط البحث وكل القوائم والأشرطة الجانبية بالقوة */
        .nt-navbar, .nt-sidebar, .nav-search-box, header, nav, footer, aside, .sidebar-overlay, .mobile-sidebar-toggle {
            display: none !important;
            height: 0 !important;
            opacity: 0 !important;
        }

        /* إخفاء عناصر التحكم الخاصة بالشاشة الحالية */
        .inv-top-bar, .modal-overlay, .no-print, .web-only-header { 
            display: none !important; 
        }
        
        .main-content, .inv-wrapper, .inv-canvas { 
            max-width: 100% !important; 
            width: 100% !important; 
            margin: 0 !important; 
            padding: 0 !important;
            border: none !important; 
            box-shadow: none !important; 
            border-radius: 0 !important; 
            background: transparent !important;
            overflow: visible !important; /* السماح بالتمدد للطباعة لمنع السكرول */
            height: auto !important;
        }

        /* ترويسة الطباعة الرسمية */
        .print-only-header { 
            display: flex !important; 
            justify-content: space-between !important; 
            align-items: center !important; 
            border-bottom: 2px solid #000 !important; 
            padding-bottom: 10px !important; 
            margin-bottom: 20px !important; 
        }
        .print-only-header .left-col h1 { font-size: 18pt !important; margin: 0 0 5px 0 !important; font-weight: 900 !important; color: #000 !important; }
        .print-only-header .left-col p { font-size: 10pt !important; margin: 0 !important; color: #000 !important; }
        .print-only-header .right-col { text-align: <?= $isAr ? 'left' : 'right' ?> !important; }
        .print-only-header .right-col h2 { font-size: 16pt !important; margin: 0 0 5px 0 !important; color: #000 !important; text-transform: uppercase !important; }
        .print-only-header .right-col p { font-size: 11pt !important; margin: 0 !important; font-weight: bold !important; font-family: monospace !important; }

        /* بيانات العميل والشركة */
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

        /* جدول الأصناف */
        .inv-table { 
            margin-bottom: 20px !important; 
            border-collapse: collapse !important;
            border: 1px solid #000 !important;
            width: 100% !important;
        }
        .inv-table th { 
            background: #f0f0f0 !important; 
            color: #000 !important; 
            font-size: 9.5pt !important; 
            padding: 8px !important; 
            border: 1px solid #000 !important;
        }
        .inv-table td { 
            border: 1px solid #000 !important; 
            font-size: 9.5pt !important; 
            padding: 8px !important; 
            color: #000 !important;
        }
        .inv-table tr { page-break-inside: avoid !important; }

        /* المجاميع والملاحظات */
        .summary-section { 
            page-break-inside: avoid !important; 
            margin-top: 15px !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: stretch !important;
        }
        .notes-card {
            border: 1px solid #000 !important;
            background: #fff !important;
            width: 55% !important;
            padding: 12px !important;
            color: #000 !important;
            font-size: 9pt !important;
        }
        .totals-box { 
            border: 2px solid #000 !important; 
            background: #fff !important; 
            padding: 12px !important;
            width: 40% !important;
            border-radius: 0 !important;
        }
        .tot-row { color: #000 !important; font-size: 9.5pt !important; padding: 4px 0 !important; }
        .tot-row .val { color: #000 !important; }
        .tot-row.grand, .tot-row.due { border-top: 2px solid #000 !important; color: #000 !important; font-size: 11pt !important;}
        .tot-row.due .val { color: #000 !important; }

        /* سجل الدفعات */
        .payments-history { 
            page-break-inside: avoid !important; 
            margin-top: 25px !important; 
            padding-top: 10px !important; 
            border-top: 2px dashed #000 !important; 
        }
        .payments-history h4 { color: #000 !important; font-size: 11pt !important; margin-bottom: 10px !important;}
        .pay-table th, .pay-table td { border: 1px solid #ccc !important; color: #000 !important; padding: 6px !important; font-size: 9pt !important;}

        /* التوقيعات */
        .print-signatures { 
            display: flex !important; 
            justify-content: space-between !important; 
            margin-top: 40px !important; 
            padding-top: 10px !important; 
            page-break-inside: avoid !important; 
        }
        .sig-box { 
            text-align: center !important; 
            flex: 1 !important; 
            color: #000 !important;
            font-size: 10pt !important;
            font-weight: bold !important;
        }
        .sig-line { 
            border-top: 1px dashed #000 !important; 
            width: 65% !important; 
            margin: 30px auto 0 auto !important; 
        }
    }
</style>

<div class="inv-wrapper" dir="<?= $dir ?>">
    
    <?php if(isset($_SESSION['flash_msg'])): ?>
        <div class="no-print" style="background:#ecfdf5; color:#059669; padding:14px 20px; border-radius:12px; margin-bottom:20px; font-weight:700; border:1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= $_SESSION['flash_msg']; unset($_SESSION['flash_msg']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['flash_err'])): ?>
        <div class="no-print" style="background:#fef2f2; color:#dc2626; padding:14px 20px; border-radius:12px; margin-bottom:20px; font-weight:700; border:1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <!-- Top Action Bar (Web Only) -->
    <div class="inv-top-bar no-print">
        <div class="title-group">
            <a href="/ERP/sales/invoices" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h3 style="margin:0; font-size: 1.3rem; font-weight: 800; color: #0f172a;"><?= $isAr ? 'عرض فاتورة مبيعات' : 'Invoice Details' ?></h3>
            </div>
        </div>
        <div class="action-btns">
            <button type="button" onclick="window.print()" class="btn-act btn-act-light"><i class="ph-bold ph-printer"></i> <?= $isAr ? 'طباعة' : 'Print' ?></button>
            <a href="/ERP/sales/invoices/<?= $invoice->id ?>/edit" class="btn-act btn-act-light"><i class="ph-bold ph-pencil-simple"></i> <?= $isAr ? 'تعديل' : 'Edit' ?></a>
            <?php if($dueAmount > 0): ?>
                <button type="button" onclick="openPayModal()" class="btn-act btn-act-success"><i class="ph-bold ph-money"></i> <?= $isAr ? 'تسجيل دفعة' : 'Register Payment' ?></button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Paper Canvas -->
    <div class="inv-canvas">
        
        <!-- Header for Web -->
        <div class="canvas-header web-only-header">
            <div>
                <h1 class="brand-title"><?= htmlspecialchars($companyName) ?></h1>
                <p class="brand-sub"><?= $isAr ? 'نظام إدارة المبيعات والفواتير الضريبية' : 'Sales and Tax Invoices System' ?></p>
            </div>
            <div class="doc-meta">
                <span class="doc-type-tag"><?= $isAr ? 'فاتورة مبيعات ضريبية' : 'TAX INVOICE' ?></span>
                <h2 class="doc-number"><?= htmlspecialchars($invoice->invoice_number) ?></h2>
                <span class="status-badge"><?= $stUI['label'] ?></span>
            </div>
        </div>

        <!-- Header for Print -->
        <div class="print-only-header">
            <div class="left-col">
                <h1><?= htmlspecialchars($companyName) ?></h1>
                <p><?= htmlspecialchars($branchName) ?></p>
                <p><?= $isAr ? 'الرقم الضريبي:' : 'Tax No:' ?> 300000000000003</p>
            </div>
            <div class="right-col">
                <h2><?= $isAr ? 'فاتورة ضريبية' : 'TAX INVOICE' ?></h2>
                <p><?= htmlspecialchars($invoice->invoice_number) ?></p>
                <p style="font-size: 10pt; margin-top: 3px; font-family: <?= $isAr ? "'Cairo'" : "'Inter'" ?>; font-weight: normal;"><?= $stUI['label'] ?></p>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-box">
                <h5><?= $isAr ? 'مُصدر الفاتورة (الشركة)' : 'Billed From' ?></h5>
                <div class="name"><?= htmlspecialchars($companyName) ?></div>
                <p><strong><?= $isAr ? 'الفرع' : 'Branch' ?>:</strong> <?= htmlspecialchars($branchName) ?></p>
                <p><strong><?= $isAr ? 'الرقم الضريبي' : 'Tax No' ?>:</strong> 300000000000003</p>
            </div>
            <div class="info-box">
                <h5><?= $isAr ? 'مُوجهة إلى (العميل)' : 'Billed To' ?></h5>
                <div class="name"><?= htmlspecialchars($custName) ?></div>
                <p><strong><?= $isAr ? 'الرقم الضريبي' : 'Tax No' ?>:</strong> <?= htmlspecialchars($customer->tax_number ?? '---') ?></p>
                <p><strong><?= $isAr ? 'الهاتف' : 'Phone' ?>:</strong> <span dir="ltr"><?= htmlspecialchars($customer->phone ?? '---') ?></span></p>
                <p><strong><?= $isAr ? 'تاريخ الإصدار' : 'Issue Date' ?>:</strong> <?= htmlspecialchars($invoice->issue_date) ?></p>
                <p><strong style="color:var(--print-red, #dc2626);"><?= $isAr ? 'تاريخ الاستحقاق' : 'Due Date' ?>:</strong> <?= htmlspecialchars($invoice->due_date) ?></p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="inv-table">
            <thead>
                <tr>
                    <th style="width: 50%;"><?= $isAr ? 'الصنف / الوصف' : 'Item Description' ?></th>
                    <th style="width: 15%; text-align: center;"><?= $isAr ? 'الكمية' : 'Qty' ?></th>
                    <th style="width: 17%; text-align: end;"><?= $isAr ? 'سعر الوحدة' : 'Unit Price' ?> (<?= htmlspecialchars($currency) ?>)</th>
                    <th style="width: 18%; text-align: end;"><?= $isAr ? 'الإجمالي' : 'Total' ?> (<?= htmlspecialchars($currency) ?>)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($lines)): foreach($lines as $line): ?>
                    <tr>
                        <td><div style="font-weight: 800; font-size: 0.95rem;"><?= htmlspecialchars($line->description) ?></div></td>
                        <td style="text-align: center; font-weight: 800; font-family: monospace; color: #4f46e5;"><?= number_format($line->quantity, 2) ?></td>
                        <td style="text-align: end; font-weight: 700; font-family: monospace; color: #64748b;"><?= number_format($line->unit_price, 2) ?></td>
                        <td style="text-align: end; font-weight: 900; font-family: monospace;"><?= number_format($line->total, 2) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" style="text-align: center; padding: 24px; color: #94a3b8;"><?= $isAr ? 'لا توجد عناصر مسجلة.' : 'No items found.' ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Summary -->
        <div class="summary-section">
            <div class="notes-card">
                <strong><?= $isAr ? 'الشروط والملاحظات:' : 'Notes & Terms:' ?></strong><br>
                <?= !empty($invoice->notes) ? nl2br(htmlspecialchars($invoice->notes)) : ($isAr ? 'شكراً لتعاملكم معنا. الدفع مستحق خلال المدة المحددة أعلاه.' : 'Thank you for your business. Payment is due within the specified period.') ?>
            </div>
            
            <div class="totals-box">
                <div class="tot-row">
                    <span><?= $isAr ? 'المجموع الفرعي' : 'Subtotal' ?></span>
                    <span class="val"><?= number_format($invoice->subtotal ?? 0, 2) ?> <?= htmlspecialchars($currency) ?></span>
                </div>
                <div class="tot-row">
                    <span><?= $isAr ? 'الضريبة (15%)' : 'Tax (15%)' ?></span>
                    <span class="val"><?= number_format($invoice->tax_amount ?? 0, 2) ?> <?= htmlspecialchars($currency) ?></span>
                </div>
                <div class="tot-row grand">
                    <span><?= $isAr ? 'الإجمالي الكلي' : 'Grand Total' ?></span>
                    <span class="val"><?= number_format($grandTotal, 2) ?> <?= htmlspecialchars($currency) ?></span>
                </div>
                <div class="tot-row paid">
                    <span><?= $isAr ? 'المبلغ المدفوع' : 'Paid Amount' ?></span>
                    <span class="val" style="color: #059669;"><?= number_format($paidAmount, 2) ?> <?= htmlspecialchars($currency) ?></span>
                </div>
                <div class="tot-row due">
                    <span><?= $isAr ? 'المبلغ المتبقي' : 'Due Balance' ?></span>
                    <span class="val" style="color: #dc2626;"><?= number_format($dueAmount, 2) ?> <?= htmlspecialchars($currency) ?></span>
                </div>
            </div>
        </div>

        <!-- سجل التحصيلات والدفعات -->
        <div class="payments-history">
            <h4><i class="ph-bold ph-receipt text-emerald-600 no-print"></i> <?= $isAr ? 'سجل التحصيلات والدفعات المسجلة' : 'Payment Ledger History' ?></h4>
            <?php if(empty($payments)): ?>
                <p style="color: #94a3b8; font-size: 0.88rem; margin: 0;"><?= $isAr ? 'لم يتم تسجيل أي دفعات لهذه الفاتورة حتى الآن.' : 'No payments recorded yet.' ?></p>
            <?php else: ?>
                <table class="pay-table">
                    <thead>
                        <tr>
                            <th><?= $isAr ? 'التاريخ' : 'Date' ?></th>
                            <th><?= $isAr ? 'طريقة الدفع' : 'Method' ?></th>
                            <th><?= $isAr ? 'الملاحظات / المرجع' : 'Notes' ?></th>
                            <th style="text-align: end;"><?= $isAr ? 'المبلغ المحصل' : 'Amount' ?> (<?= htmlspecialchars($currency) ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payments as $p): ?>
                            <tr>
                                <td style="font-weight: 700;"><?= htmlspecialchars($p->payment_date) ?></td>
                                <td><span style="background: #ecfdf5; color: #059669; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 0.8rem;" class="no-print-bg"><?= strtoupper(htmlspecialchars($p->payment_method)) ?></span></td>
                                <td style="color: #64748b;"><?= htmlspecialchars($p->notes ?? '---') ?></td>
                                <td style="text-align: end; font-weight: 900; font-family: monospace; color: #059669; font-size: 0.95rem;"><?= number_format($p->amount, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- قسم التوقيعات عند الطباعة الرسمية -->
        <div class="print-signatures">
            <div class="sig-box">
                <div><?= $isAr ? 'إعداد المحاسب' : 'Prepared By' ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $isAr ? 'المدير المالي' : 'Financial Manager' ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $isAr ? 'توقيع المستلم' : 'Recipient Signature' ?></div>
                <div class="sig-line"></div>
            </div>
        </div>

    </div>
</div>

<!-- Modal تسجيل الدفعة -->
<div class="modal-overlay no-print" id="payModal" onclick="if(event.target === this) closePayModal()">
    <div class="modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
            <h3 style="margin: 0; font-weight: 800; color: #0f172a; font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
                <i class="ph-bold ph-money text-emerald-600" style="font-size: 1.4rem;"></i> <?= $isAr ? 'تسجيل دفعة مالية' : 'Register Payment' ?>
            </h3>
            <button type="button" onclick="closePayModal()" style="background: none; border: none; font-size: 1.2rem; color: #64748b; cursor: pointer;"><i class="ph-bold ph-x"></i></button>
        </div>
        
        <form action="/ERP/sales/invoices/<?= $invoice->id ?>/pay" method="POST">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="font-weight: 700; font-size: 0.8rem; color: #64748b; display: block; margin-bottom: 4px;"><?= $isAr ? 'المتبقي حالياً' : 'Current Due' ?></label>
                    <div style="background: #fef2f2; color: #dc2626; padding: 8px 12px; border-radius: 8px; font-weight: 900; font-family: monospace; font-size: 1rem; border: 1px solid #fecaca;">
                        <?= number_format($dueAmount, 2) ?> <span style="font-size: 0.8rem;"><?= htmlspecialchars($currency) ?></span>
                    </div>
                </div>
                <div>
                    <label style="font-weight: 700; font-size: 0.8rem; color: #64748b; display: block; margin-bottom: 4px;"><?= $isAr ? 'المتبقي بعد الدفعة' : 'Remaining After' ?></label>
                    <div id="afterPayCalc" style="background: #ecfdf5; color: #059669; padding: 8px 12px; border-radius: 8px; font-weight: 900; font-family: monospace; font-size: 1rem; border: 1px solid #a7f3d0;">
                        <?= number_format($dueAmount, 2) ?> <span style="font-size: 0.8rem;"><?= htmlspecialchars($currency) ?></span>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-weight: 700; font-size: 0.85rem; color: #334155; display: block; margin-bottom: 6px;"><?= $isAr ? 'المبلغ المستلم الآن' : 'Amount to Pay Now' ?> (<?= htmlspecialchars($currency) ?>) <span style="color:red">*</span></label>
                <input type="number" step="0.01" max="<?= $dueAmount ?>" id="payInput" name="amount" class="form-control" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 800; font-family: monospace; font-size: 1.1rem; color: #10b981;" placeholder="0.00" oninput="updateDueCalc(this.value)" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="font-weight: 700; font-size: 0.85rem; color: #334155; display: block; margin-bottom: 6px;"><?= $isAr ? 'طريقة الدفع' : 'Method' ?></label>
                    <select name="payment_method" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.88rem;">
                        <option value="cash"><?= $isAr ? 'نقداً (كاش)' : 'Cash' ?></option>
                        <option value="bank_transfer"><?= $isAr ? 'تحويل بنكي' : 'Bank' ?></option>
                        <option value="cheque"><?= $isAr ? 'شيك' : 'Cheque' ?></option>
                    </select>
                </div>
                <div>
                    <label style="font-weight: 700; font-size: 0.85rem; color: #334155; display: block; margin-bottom: 6px;"><?= $isAr ? 'تاريخ التحصيل' : 'Date' ?></label>
                    <input type="date" name="payment_date" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.88rem;" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="font-weight: 700; font-size: 0.85rem; color: #334155; display: block; margin-bottom: 6px;"><?= $isAr ? 'رقم الإيصال / الملاحظات' : 'Notes' ?></label>
                <input type="text" name="payment_notes" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.88rem;" placeholder="<?= $isAr ? 'رقم الإيصال أو المرجع...' : 'Ref No...' ?>">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                <button type="button" onclick="closePayModal()" style="padding: 9px 18px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #475569; font-weight: 700; cursor: pointer;"><?= $isAr ? 'إلغاء' : 'Cancel' ?></button>
                <button type="submit" class="btn-act btn-act-success" style="padding: 9px 20px; border:none; color:white;"><i class="ph-bold ph-check"></i> <?= $isAr ? 'حفظ وتأكيد' : 'Save' ?></button>
            </div>
        </form>
    </div>
</div>

<script>
    const currentDue = <?= (float)$dueAmount ?>;
    const currencyLabel = " <?= htmlspecialchars($currency) ?>";

    function openPayModal() {
        var modal = document.getElementById('payModal');
        if (modal) { modal.style.display = 'flex'; }
    }
    function closePayModal() {
        var modal = document.getElementById('payModal');
        if (modal) { modal.style.display = 'none'; }
    }

    function updateDueCalc(val) {
        let paid = parseFloat(val) || 0;
        let rem = Math.max(0, currentDue - paid);
        document.getElementById('afterPayCalc').innerHTML = rem.toFixed(2) + '<span style="font-size:0.8rem;">' + currencyLabel + '</span>';
    }
</script>