<?php
// Path: resources/views/sales/returns/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = isRtl();
$currency = current_currency();
$dir = $isAr ? 'rtl' : 'ltr';

$custName = $isAr ? ($return->customer_name ?? 'عميل عام') : ($return->customer_name ?? 'General Customer');
$companyName = current_company_name();
$branchName  = $_SESSION['active_branch_name'] ?? ($isAr ? 'الفرع الرئيسي' : 'Main Branch');

$statusMap = [
    'draft'     => ['bg' => '#f1f5f9', 'text' => '#475569', 'border' => '#cbd5e1', 'label' => $isAr ? 'مسودة' : 'DRAFT'],
    'approved'  => ['bg' => '#eff6ff', 'text' => '#2563eb', 'border' => '#bfdbfe', 'label' => $isAr ? 'معتمد' : 'APPROVED'],
    'completed' => ['bg' => '#ecfdf5', 'text' => '#059669', 'border' => '#a7f3d0', 'label' => $isAr ? 'مكتمل' : 'COMPLETED'],
    'cancelled' => ['bg' => '#fef2f2', 'text' => '#dc2626', 'border' => '#fecaca', 'label' => $isAr ? 'ملغى' : 'CANCELLED']
];

$st = strtolower($return->status ?? 'draft');
$stUI = $statusMap[$st] ?? $statusMap['draft'];
?>

<style>
    .ret-show-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .ret-top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .ret-top-bar .title-group { display: flex; align-items: center; gap: 12px; }
    .back-btn { width: 38px; height: 38px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .back-btn:hover { background: #f8fafc; color: #0f172a; }
    
    .action-btns { display: flex; gap: 10px; }
    .btn-act { padding: 9px 18px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; font-family: inherit; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none; transition: 0.2s; }
    .btn-act-light { background: #ffffff; border: 1px solid #cbd5e1; color: #334155; }
    .btn-act-light:hover { background: #f8fafc; border-color: #94a3b8; }
    .btn-act-danger { background: #dc2626; color: #ffffff; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25); }
    .btn-act-danger:hover { background: #b91c1c; }

    .ret-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); overflow: hidden; padding: 40px; }
    
    .canvas-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 28px; margin-bottom: 28px; }
    .brand-title { margin: 0; font-size: 1.6rem; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; }
    .brand-sub { margin: 4px 0 0 0; color: #64748b; font-size: 0.85rem; font-weight: 600; }
    
    .doc-meta { text-align: <?= $isAr ? 'left' : 'right' ?>; }
    .doc-type-tag { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #dc2626; letter-spacing: 1px; margin-bottom: 4px; display: block; }
    .doc-number { font-family: monospace; font-size: 1.6rem; font-weight: 900; color: #0f172a; margin: 0; }
    .status-badge { padding: 4px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 800; display: inline-block; margin-top: 8px; border: 1px solid <?= $stUI['border'] ?>; background: <?= $stUI['bg'] ?>; color: <?= $stUI['text'] ?>; }

    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px; background: #f8fafc; padding: 20px 24px; border-radius: 12px; border: 1px solid #f1f5f9; }
    .info-box h5 { margin: 0 0 10px 0; color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-box .name { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
    .info-box p { margin: 3px 0; color: #475569; font-size: 0.88rem; font-weight: 500; }

    .table-responsive-wrapper { overflow-x: auto; margin-bottom: 28px; }
    .ret-table { width: 100%; border-collapse: collapse; }
    .ret-table th { background: #0f172a; color: #ffffff; padding: 12px 16px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: start; }
    .ret-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.92rem; vertical-align: middle; }
    
    .summary-section { display: flex; justify-content: space-between; align-items: flex-start; gap: 32px; margin-top: 20px; }
    .notes-card { flex-grow: 1; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; font-size: 0.88rem; color: #475569; }
    .notes-card strong { color: #0f172a; display: block; margin-bottom: 4px; }
    
    .totals-box { width: 300px; flex-shrink: 0; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; }
    .tot-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.88rem; color: #64748b; font-weight: 600; }
    .tot-row.grand { border-top: 2px solid #0f172a; padding-top: 10px; margin-top: 6px; font-size: 1.2rem; font-weight: 900; color: #dc2626; }
    .tot-row .val { font-family: monospace; font-weight: 800; color: #0f172a; }

    .print-only-header { display: none; }
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
        
        .ret-show-wrapper {
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

        .ret-top-bar, .no-print, .canvas-header { display: none !important; }
        
        /* إخفاء عناصر DataTables */
        .dataTables_filter, .dataTables_length, .dataTables_info, .dataTables_paginate,
        .dt-search, .dt-paging, .dt-info, .dt-length, .dt-buttons, .pagination,
        [id*="filter"], [id*="search"], [class*="paginate"], [class*="pagination"],
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

        .ret-canvas { 
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

        .ret-table, .dataTable-table { 
            border: 1px solid #000 !important; 
            margin-bottom: 20px !important; 
            width: 100% !important;
        }
        .ret-table th, .dataTable-table th { 
            background: #f0f0f0 !important; 
            color: #000 !important; 
            font-size: 9.5pt !important; 
            padding: 8px !important; 
            border: 1px solid #000 !important;
        }
        .ret-table td, .dataTable-table td { 
            border: 1px solid #000 !important; 
            font-size: 9.5pt !important; 
            padding: 8px !important; 
            color: #000 !important;
        }
        .ret-table tr, .dataTable-table tr { page-break-inside: avoid !important; }

        .summary-section { 
            page-break-inside: avoid !important; 
            margin-top: 15px !important;
            display: flex !important;
            justify-content: space-between !important;
        }
        .notes-card {
            border: 1px solid #000 !important;
            background: #fff !important;
            width: 55% !important;
            padding: 10px !important;
            color: #000 !important;
        }
        .totals-box { 
            border: 1px solid #000 !important; 
            background: #fff !important; 
            padding: 10px !important;
            width: 40% !important;
            border-radius: 0 !important;
        }
        .tot-row { color: #000 !important; font-size: 9.5pt !important; }
        .tot-row.grand { border-top: 2px solid #000 !important; color: #000 !important; font-size: 11pt !important;}

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

<div class="ret-show-wrapper" dir="<?= $dir ?>">
    
    <!-- Top Action Bar -->
    <div class="ret-top-bar no-print">
        <div class="title-group">
            <a href="/ERP/sales/returns" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h3 style="margin:0; font-size: 1.3rem; font-weight: 800; color: #0f172a;"><?= $isAr ? 'تفاصيل إشعار مرتجع المبيعات' : 'Credit Note Details' ?></h3>
            </div>
        </div>
        <div class="action-btns">
            <button type="button" onclick="window.print()" class="btn-act btn-act-light"><i class="ph-bold ph-printer"></i> <?= $isAr ? 'طباعة الإشعار' : 'Print Credit Note' ?></button>
            <a href="/ERP/sales/returns/<?= $return->id ?>/edit" class="btn-act btn-act-danger"><i class="ph-bold ph-pencil-simple"></i> <?= $isAr ? 'تعديل' : 'Edit' ?></a>
        </div>
    </div>

    <!-- Paper Canvas -->
    <div class="ret-canvas">
        
        <!-- Header for Web -->
        <div class="canvas-header">
            <div>
                <h1 class="brand-title"><?= htmlspecialchars($companyName) ?></h1>
                <p class="brand-sub"><?= $isAr ? 'إشعار مالي دائن - مرتجع مبيعات' : 'Sales Return Credit Note' ?></p>
            </div>
            <div class="doc-meta">
                <span class="doc-type-tag"><?= $isAr ? 'إشعار دائن ضريبي' : 'CREDIT NOTE' ?></span>
                <h2 class="doc-number"><?= htmlspecialchars($return->return_number) ?></h2>
                <span class="status-badge"><?= $stUI['label'] ?></span>
            </div>
        </div>

        <!-- Header for Print -->
        <div class="print-only-header">
            <div>
                <h1><?= $isAr ? 'إشعار دائن (مرتجع)' : 'CREDIT NOTE' ?></h1>
                <p><?= $isAr ? 'رقم الوثيقة:' : 'Document #:' ?> <?= htmlspecialchars($return->return_number) ?></p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <h2><?= htmlspecialchars($companyName) ?></h2>
                <p><?= htmlspecialchars($branchName) ?></p>
                <p><?= htmlspecialchars($return->return_date) ?></p>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-box">
                <h5><?= $isAr ? 'الشركة المصدرة للإشعار' : 'Issued From' ?></h5>
                <div class="name"><?= htmlspecialchars($companyName) ?></div>
                <p><strong><?= $isAr ? 'الفرع' : 'Branch' ?>:</strong> <?= htmlspecialchars($branchName) ?></p>
                <p><strong><?= $isAr ? 'الرقم الضريبي' : 'Tax No' ?>:</strong> 300000000000003</p>
            </div>
            <div class="info-box">
                <h5><?= $isAr ? 'إشعار لصالح العميل' : 'Credit To' ?></h5>
                <div class="name"><?= htmlspecialchars($custName) ?></div>
                <p><strong><?= $isAr ? 'الرقم الضريبي' : 'Tax No' ?>:</strong> <?= htmlspecialchars($return->customer_tax ?? '---') ?></p>
                <p><strong><?= $isAr ? 'الهاتف' : 'Phone' ?>:</strong> <span dir="ltr"><?= htmlspecialchars($return->customer_phone ?? '---') ?></span></p>
                <p><strong><?= $isAr ? 'تاريخ الإشعار' : 'Return Date' ?>:</strong> <?= htmlspecialchars($return->return_date) ?></p>
                <p><strong><?= $isAr ? 'الفاتورة المرتبطة' : 'Linked Invoice' ?>:</strong> <span style="color:#2563eb; font-family:monospace; font-weight:bold;"><?= htmlspecialchars($return->invoice_number ?? ($isAr ? 'مرتجع عام' : 'General Return')) ?></span></p>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-responsive-wrapper">
            <table class="ret-table no-datatable" id="returnsItemsTable">
                <thead>
                    <tr>
                        <th style="width: 50%;"><?= $isAr ? 'الصنف المرتجع / الوصف' : 'Returned Item' ?></th>
                        <th style="width: 15%; text-align: center;"><?= $isAr ? 'الكمية' : 'Qty' ?></th>
                        <th style="width: 17%; text-align: end;"><?= $isAr ? 'سعر الوحدة' : 'Unit Price' ?> (<?= htmlspecialchars($currency) ?>)</th>
                        <th style="width: 18%; text-align: end;"><?= $isAr ? 'الإجمالي' : 'Total' ?> (<?= htmlspecialchars($currency) ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($lines)): foreach($lines as $line): ?>
                        <tr>
                            <td><div style="font-weight: 800; color: #0f172a; font-size: 0.95rem;"><?= htmlspecialchars($line->description) ?></div></td>
                            <td style="text-align: center; font-weight: 800; font-family: monospace; color: #dc2626;"><?= number_format($line->quantity, 2) ?></td>
                            <td style="text-align: end; font-weight: 700; font-family: monospace; color: #64748b;"><?= number_format(convert_amount($line->unit_price ?? 0), 2) ?></td>
                            <td style="text-align: end; font-weight: 900; font-family: monospace; color: #0f172a;"><?= number_format(convert_amount($line->total ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" style="text-align: center; padding: 24px; color: #94a3b8;"><?= $isAr ? 'لا توجد بنود مرتجعة.' : 'No returned items found.' ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Summary -->
        <div class="summary-section">
            <div class="notes-card">
                <strong><?= $isAr ? 'سبب مرتجع المبيعات / بيان الإشعار:' : 'Return Reason / Statement:' ?></strong>
                <?= !empty($return->reason) ? nl2br(htmlspecialchars($return->reason)) : ($isAr ? 'تم قيد هذا المبلغ لحساب العميل كإشعار دائن عن بضاعة مرتجعة.' : 'Credit note issued for returned products.') ?>
            </div>
            
            <div class="totals-box">
                <div class="tot-row">
                    <span><?= $isAr ? 'المجموع الفرعي' : 'Subtotal' ?></span>
                    <span class="val"><?= number_format(convert_amount($return->subtotal ?? 0), 2) ?> <?= htmlspecialchars($currency) ?></span>
                </div>
                <div class="tot-row">
                    <span><?= $isAr ? 'ضريبة القيمة المضافة (15%)' : 'VAT (15%)' ?></span>
                    <span class="val"><?= number_format(convert_amount($return->tax_amount ?? 0), 2) ?> <?= htmlspecialchars($currency) ?></span>
                </div>
                <div class="tot-row grand">
                    <span><?= $isAr ? 'إجمالي الإشعار الدائن' : 'Credit Note Total' ?></span>
                    <span class="val" style="color:#dc2626;"><?= number_format(convert_amount($return->total_amount ?? 0), 2) ?> <?= htmlspecialchars($currency) ?></span>
                </div>
            </div>
        </div>

        <!-- Signatures (Visible on A4 Print) -->
        <div class="print-signatures">
            <div class="sig-box">
                <div><?= $isAr ? 'مسؤول ارتجاع المخزون' : 'Store Keeper' ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $isAr ? 'المراجع المالي' : 'Auditor' ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $isAr ? 'استلام العميل للإشعار' : 'Customer Credit Acknowledgment' ?></div>
                <div class="sig-line"></div>
            </div>
        </div>

    </div>
</div>