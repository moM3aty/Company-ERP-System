<?php
// Path: resources/views/sales/statements/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = isRtl();
$currency = current_currency();
$dir = $isAr ? 'rtl' : 'ltr';

$companyName = current_company_name();
$branchName  = $_SESSION['active_branch_name'] ?? ($isAr ? 'الفرع الرئيسي' : 'Main Branch');

$custName = $isAr ? ($customer->name_ar ?: $customer->name_en) : ($customer->name_en ?: $customer->name_ar);

// مصفوفة الترجمة الشاملة (بدون أي نصوص Hardcoded)
$t = [
    'ar' => [
        'title' => 'كشف حساب تفصيلي',
        'print_btn' => 'طباعة كشف الحساب',
        'back' => 'رجوع',
        'start_date' => 'تاريخ البداية',
        'end_date' => 'تاريخ النهاية',
        'filter_btn' => 'تصفية للفترة',
        'reset_btn' => 'إعادة ضبط',
        'erp_sub' => 'كشف حساب عميل وتأكيد أرصدة المبيعات',
        'doc_type' => 'كشف حساب مالـي',
        'export_date' => 'تاريخ التصدير:',
        'print_date' => 'تاريخ الطباعة:',
        'customer_details' => 'مُوجه إلى (بيانات العميل)',
        'tax_no' => 'الرقم الضريبي:',
        'phone' => 'رقم الهاتف:',
        'address' => 'العنوان:',
        'period_terms' => 'فترة الكشف والائتمان',
        'period_label' => 'الفترة:',
        'period_start' => 'البداية',
        'period_now' => 'الآن',
        'to_word' => 'إلى',
        'branch_curr' => 'الفرع والعملة:',
        'credit_limit' => 'الحد الائتماني:',
        'opening_balance' => 'الرصيد السابق (الافتتاحي)',
        'total_billed' => 'إجمالي المبيعات (مدين)',
        'total_paid' => 'إجمالي المسدد والمرتجع (دائن)',
        'ending_balance' => 'الرصيد المستحق النهائي',
        'col_date' => 'التاريخ',
        'col_type' => 'نوع الحركة',
        'col_doc' => 'رقم المستند',
        'col_notes' => 'البيان / الملاحظات',
        'col_debit' => 'مدين (+)',
        'col_credit' => 'دائن (-)',
        'col_balance' => 'الرصيد',
        'no_transactions' => 'لا توجد حركات مالية مسجلة في هذه الفترة.',
        'sig_accountant' => 'إعداد المحاسب',
        'sig_manager' => 'المدير المالي',
        'sig_customer' => 'مصادقة وإعتماد العميل',
        'type_invoice' => 'فاتورة مبيعات',
        'type_receipt' => 'سند قبض مالي',
        'type_return' => 'مرتجع مبيعات'
    ],
    'en' => [
        'title' => 'Statement of Account',
        'print_btn' => 'Print Statement',
        'back' => 'Back',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'filter_btn' => 'Filter',
        'reset_btn' => 'Reset',
        'erp_sub' => 'Customer Account Statement & Ledger',
        'doc_type' => 'STATEMENT OF ACCOUNT',
        'export_date' => 'Export Date:',
        'print_date' => 'Print Date:',
        'customer_details' => 'Customer Details',
        'tax_no' => 'Tax No:',
        'phone' => 'Phone:',
        'address' => 'Address:',
        'period_terms' => 'Statement Period & Terms',
        'period_label' => 'Period:',
        'period_start' => 'Start',
        'period_now' => 'Now',
        'to_word' => 'To',
        'branch_curr' => 'Branch & Currency:',
        'credit_limit' => 'Credit Limit:',
        'opening_balance' => 'Opening Balance',
        'total_billed' => 'Total Billed (+)',
        'total_paid' => 'Total Paid/Credits (-)',
        'ending_balance' => 'Ending Balance',
        'col_date' => 'Date',
        'col_type' => 'Type',
        'col_doc' => 'Doc No.',
        'col_notes' => 'Notes / Description',
        'col_debit' => 'Debit (+)',
        'col_credit' => 'Credit (-)',
        'col_balance' => 'Balance',
        'no_transactions' => 'No transactions found for this period.',
        'sig_accountant' => 'Prepared By',
        'sig_manager' => 'Finance Manager',
        'sig_customer' => 'Customer Acceptance',
        'type_invoice' => 'Sales Invoice',
        'type_receipt' => 'Payment Receipt',
        'type_return' => 'Sales Return'
    ]
][$isAr ? 'ar' : 'en'];

// ترجمة أنواع الحركات
$txTypeMap = [
    'invoice' => ['label' => $t['type_invoice'], 'color' => '#0f172a', 'bg' => '#f1f5f9'],
    'receipt' => ['label' => $t['type_receipt'], 'color' => '#059669', 'bg' => '#ecfdf5'],
    'return'  => ['label' => $t['type_return'],  'color' => '#dc2626', 'bg' => '#fef2f2']
];
?>

<style>
    .stmt-show-wrapper { max-width: 1000px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .stmt-top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 16px; }
    .back-btn { width: 38px; height: 38px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .back-btn:hover { background: #f8fafc; color: #0f172a; }
    
    .btn-act { padding: 9px 18px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; font-family: inherit; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none; transition: 0.2s; }
    .btn-act-dark { background: #0f172a; color: #ffffff; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25); }
    .btn-act-dark:hover { background: #1e293b; }

    /* Date Filter Bar */
    .filter-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .filter-form { display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap; }
    .input-label { font-size: 0.8rem; font-weight: 700; color: #475569; margin-bottom: 4px; display: block; }
    .form-control { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.88rem; outline: none; background: #f8fafc; }

    /* Canvas Statement Paper */
    .stmt-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05); overflow: hidden; padding: 40px; }
    
    .canvas-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 24px; }
    .brand-title { margin: 0; font-size: 1.6rem; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; }
    .brand-sub { margin: 4px 0 0 0; color: #64748b; font-size: 0.85rem; font-weight: 600; }
    
    .doc-meta { text-align: <?= $isAr ? 'left' : 'right' ?>; }
    .doc-type-tag { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #4f46e5; letter-spacing: 1px; margin-bottom: 4px; display: block; }
    .doc-number { font-family: monospace; font-size: 1.5rem; font-weight: 900; color: #0f172a; margin: 0; }

    /* Customer & Summary Grid */
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; background: #f8fafc; padding: 20px 24px; border-radius: 12px; border: 1px solid #f1f5f9; }
    .info-box h5 { margin: 0 0 10px 0; color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-box .name { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
    .info-box p { margin: 4px 0; color: #475569; font-size: 0.88rem; font-weight: 600; }

    /* Summary Bar */
    .stmt-summary-bar { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; background: #0f172a; color: #ffffff; padding: 18px 20px; border-radius: 12px; margin-bottom: 28px; }
    .s-box { text-align: center; }
    .s-title { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; opacity: 0.8; margin-bottom: 4px; }
    .s-value { font-size: 1.25rem; font-weight: 900; font-family: monospace; }

    /* Ledger Table */
    .table-responsive-wrapper { overflow-x: auto; margin-bottom: 28px; }
    .stmt-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    .stmt-table th { background: #f8fafc; color: #475569; padding: 12px 14px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; text-align: start; }
    .stmt-table td { padding: 14px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .print-signatures { display: none; }

    /* ========================================= */
    /* Master A4 Print CSS (إعدادات طباعة مسطرة) */
    /* ========================================= */
    @media print {
        @page { size: A4 portrait; margin: 12mm 15mm; }
        body { background: #ffffff !important; color: #000000 !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .nt-sidebar, header, nav, footer, .stmt-top-bar, .filter-card, .no-print, .dataTables_filter, .dataTables_length, .dataTables_info, .dataTables_paginate, .dt-search, .dt-paging, .dt-info, .dt-length, .dt-buttons, .pagination, [id*="filter"], [id*="search"], [class*="paginate"], [class*="pagination"] { display: none !important; height: 0 !important; opacity: 0 !important; visibility: hidden !important; }
        
        .stmt-show-wrapper { max-width: 100% !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .stmt-canvas { border: none !important; box-shadow: none !important; padding: 0 !important; }
        .stmt-summary-bar { background: #ffffff !important; color: #0f172a !important; border: 2px solid #0f172a !important; }
        .s-value { color: #0f172a !important; }

        .stmt-table th { background: #0f172a !important; color: #ffffff !important; }
        .stmt-table td { border-bottom: 1px solid #cbd5e1 !important; }
        .table-responsive-wrapper, .dataTable-wrapper, .dataTable-container { overflow: visible !important; max-height: none !important; height: auto !important; border: none !important; margin: 0 !important; padding: 0 !important; }

        .print-signatures { display: flex !important; justify-content: space-between !important; margin-top: 50px !important; padding-top: 20px !important; border-top: 2px dashed #0f172a !important; page-break-inside: avoid !important; }
        .sig-box { text-align: center !important; flex: 1 !important; }
        .sig-line { border-top: 1px dashed #0f172a !important; width: 70% !important; margin: 35px auto 0 auto !important; }
    }
</style>

<div class="stmt-show-wrapper" dir="<?= $dir ?>">
    
    <!-- Top Action Bar -->
    <div class="stmt-top-bar no-print">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="/ERP/sales/statements" class="back-btn" title="<?= $t['back'] ?>"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <h3 style="margin:0; font-size: 1.3rem; font-weight: 800; color: #0f172a;"><?= $t['title'] ?></h3>
        </div>
        <div>
            <button type="button" onclick="window.print()" class="btn-act btn-act-dark"><i class="ph-bold ph-printer"></i> <?= $t['print_btn'] ?></button>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="filter-card no-print">
        <form action="/ERP/sales/statements/<?= $customer->id ?>" method="GET" class="filter-form">
            <div>
                <label class="input-label"><?= $t['start_date'] ?></label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
            </div>
            <div>
                <label class="input-label"><?= $t['end_date'] ?></label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
            </div>
            <button type="submit" class="btn-act btn-act-dark" style="padding: 8px 18px;"><i class="ph-bold ph-funnel"></i> <?= $t['filter_btn'] ?></button>
            <?php if(!empty($_GET['start_date']) || !empty($_GET['end_date'])): ?>
                <a href="/ERP/sales/statements/<?= $customer->id ?>" style="color: #dc2626; font-weight: 700; font-size: 0.85rem; text-decoration: none;"><?= $t['reset_btn'] ?></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Paper Canvas -->
    <div class="stmt-canvas">
        
        <!-- Header for Web -->
        <div class="canvas-header">
            <div>
                <h1 class="brand-title"><?= htmlspecialchars($companyName) ?></h1>
                <p class="brand-sub"><?= $t['erp_sub'] ?></p>
            </div>
            <div class="doc-meta">
                <span class="doc-type-tag"><?= $t['doc_type'] ?></span>
                <h2 class="doc-number"><?= htmlspecialchars($customer->code) ?></h2>
                <div style="font-size: 0.8rem; color: #64748b; font-weight: 700; margin-top: 4px;"><?= $t['export_date'] ?> <?= date('Y-m-d') ?></div>
            </div>
        </div>

        <!-- Header for Print -->
        <div class="print-only-header">
            <div>
                <h1><?= $t['doc_type'] ?></h1>
                <p><?= $t['print_date'] ?> <?= date('Y-m-d H:i') ?></p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <h2><?= htmlspecialchars($companyName) ?></h2>
                <p><?= htmlspecialchars($branchName) ?></p>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-box">
                <h5><?= $t['customer_details'] ?></h5>
                <div class="name"><?= htmlspecialchars($custName) ?></div>
                <p><i class="ph-bold ph-file-text text-slate-400 no-print"></i> <?= $t['tax_no'] ?> <span style="font-family: monospace;"><?= htmlspecialchars($customer->tax_number ?? '---') ?></span></p>
                <p><i class="ph-bold ph-phone text-slate-400 no-print"></i> <?= $t['phone'] ?> <span dir="ltr"><?= htmlspecialchars($customer->phone ?? '---') ?></span></p>
                <p><i class="ph-bold ph-map-pin text-slate-400 no-print"></i> <?= $t['address'] ?> <?= htmlspecialchars($customer->address ?? '---') ?></p>
            </div>

            <div class="info-box">
                <h5><?= $t['period_terms'] ?></h5>
                <p><i class="ph-bold ph-calendar text-slate-400 no-print"></i> <?= $t['period_label'] ?> <strong><?= !empty($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : $t['period_start'] ?></strong> <?= $t['to_word'] ?> <strong><?= !empty($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : $t['period_now'] ?></strong></p>
                <p><i class="ph-bold ph-buildings text-slate-400 no-print"></i> <?= $t['branch_curr'] ?> <strong><?= htmlspecialchars($branchName) ?> (<?= htmlspecialchars($currency) ?>)</strong></p>
                <p><i class="ph-bold ph-shield-check text-slate-400 no-print"></i> <?= $t['credit_limit'] ?> <strong style="font-family: monospace; color:#059669;"><?= number_format(convert_amount($customer->credit_limit ?? 0), 2) ?> <?= htmlspecialchars($currency) ?></strong></p>
            </div>
        </div>

        <!-- Summary Bar -->
        <div class="stmt-summary-bar">
            <div class="s-box">
                <div class="s-title"><?= $t['opening_balance'] ?></div>
                <div class="s-value"><?= number_format(convert_amount($openingBalance ?? 0), 2) ?></div>
            </div>
            <div class="s-box">
                <div class="s-title"><?= $t['total_billed'] ?></div>
                <div class="s-value" style="color: #60a5fa;"><?= number_format(convert_amount($totalDebit ?? 0), 2) ?></div>
            </div>
            <div class="s-box">
                <div class="s-title"><?= $t['total_paid'] ?></div>
                <div class="s-value" style="color: #34d399;"><?= number_format(convert_amount($totalCredit ?? 0), 2) ?></div>
            </div>
            <div class="s-box">
                <div class="s-title"><?= $t['ending_balance'] ?></div>
                <div class="s-value" style="color: <?= ($closingBalance ?? 0) > 0 ? '#f87171' : '#34d399' ?>;"><?= number_format(convert_amount($closingBalance ?? 0), 2) ?></div>
            </div>
        </div>

        <!-- Ledger Table -->
        <div class="table-responsive-wrapper">
            <table class="stmt-table no-datatable" id="statementTable">
                <thead>
                    <tr>
                        <th style="width: 12%;"><?= $t['col_date'] ?></th>
                        <th style="width: 18%;"><?= $t['col_type'] ?></th>
                        <th style="width: 15%;"><?= $t['col_doc'] ?></th>
                        <th style="width: 25%;"><?= $t['col_notes'] ?></th>
                        <th style="width: 10%; text-align: end;"><?= $t['col_debit'] ?></th>
                        <th style="width: 10%; text-align: end;"><?= $t['col_credit'] ?></th>
                        <th style="width: 10%; text-align: end;"><?= $t['col_balance'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening Balance Row -->
                    <tr style="background: #f8fafc; font-weight: 800;">
                        <td><?= !empty($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : '---' ?></td>
                        <td colspan="3"><?= $t['opening_balance'] ?></td>
                        <td style="text-align: end;">-</td>
                        <td style="text-align: end;">-</td>
                        <td style="text-align: end; font-family: monospace;"><?= number_format(convert_amount($openingBalance ?? 0), 2) ?></td>
                    </tr>

                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 24px; color: #94a3b8;"><?= $t['no_transactions'] ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx): 
                            $tMeta = $txTypeMap[$tx->tx_type] ?? $txTypeMap['invoice'];
                            $deb = convert_amount($tx->debit ?? 0);
                            $cred = convert_amount($tx->credit ?? 0);
                            $bal = convert_amount($tx->running_balance ?? 0);
                        ?>
                            <tr>
                                <td style="font-weight: 600; color: #475569;"><?= htmlspecialchars($tx->tx_date) ?></td>
                                <td>
                                    <span style="background: <?= $tMeta['bg'] ?>; color: <?= $tMeta['color'] ?>; padding: 3px 8px; border-radius: 4px; font-weight: 800; font-size: 0.75rem;">
                                        <?= $tMeta['label'] ?>
                                    </span>
                                </td>
                                <td style="font-weight: 800; font-family: monospace; color: #0f172a;"><?= htmlspecialchars($tx->doc_number) ?></td>
                                <td style="color: #64748b; font-size: 0.85rem;"><?= htmlspecialchars($tx->notes ?? '---') ?></td>
                                <td style="text-align: end; font-weight: 800; font-family: monospace; color: #0f172a;"><?= $deb > 0 ? number_format($deb, 2) : '-' ?></td>
                                <td style="text-align: end; font-weight: 800; font-family: monospace; color: #059669;"><?= $cred > 0 ? number_format($cred, 2) : '-' ?></td>
                                <td style="text-align: end; font-weight: 900; font-family: monospace; color: <?= $bal > 0 ? '#dc2626' : '#059669' ?>;">
                                    <?= number_format($bal, 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Print Signatures Section -->
        <div class="print-signatures">
            <div class="sig-box">
                <div style="font-weight: 800; color: #0f172a;"><?= $t['sig_accountant'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div style="font-weight: 800; color: #0f172a;"><?= $t['sig_manager'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div style="font-weight: 800; color: #0f172a;"><?= $t['sig_customer'] ?></div>
                <div class="sig-line"></div>
            </div>
        </div>

    </div>
</div>