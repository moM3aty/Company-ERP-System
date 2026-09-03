<?php
// Path: resources/views/purchasing/statements/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';
$currency = current_currency();

$supName = $isAr ? ($supplier->name_ar ?: $supplier->name_en) : ($supplier->name_en ?: $supplier->name_ar);

$t = [
    'ar' => [
        'title' => 'كشف حساب مورد تفصيلي',
        'print' => 'طباعة كشف الحساب A4',
        'filter' => 'تصفية الفترة الزمنية',
        'brand_title' => 'كشف حساب مورد معتمد (Statement of Account)',
        'supplier_info' => 'بيانات المورد الأساسية',
        'sup_code' => 'كود المورد:',
        'phone' => 'رقم الهاتف:',
        'email' => 'البريد الإلكتروني:',
        'tax_number' => 'الرقم الضريبي:',
        'period' => 'الفترة المالية والكشف',
        'from_date' => 'من تاريخ:',
        'to_date' => 'إلى تاريخ:',
        'col_date' => 'التاريخ',
        'col_type' => 'نوع الحركة / البيان',
        'col_ref' => 'رقم المستند',
        'col_credit' => 'دائن (+) مستحق',
        'col_debit' => 'مدين (-) مدفوع/مرتجع',
        'col_balance' => 'الرصيد التراكمي',
        'no_tx' => 'لا توجد حركات مالية مسجلة خلال هذه الفترة المحدد.',
        'total_credit' => 'إجمالي الاستحقاقات (دائن):',
        'total_debit' => 'إجمالي السدادات والمرتجعات (مدين):',
        'final_balance' => 'صافي الرصيد النهائي المتبقي للمورد:',
        'sig_acc' => 'إعداد المحاسب المسؤول',
        'sig_auditor' => 'تدقيق المراجعة الداخلية',
        'sig_vendor' => 'المصادقة والتوقيع من المورد'
    ],
    'en' => [
        'title' => 'Supplier Statement of Account',
        'print' => 'Print A4 Statement',
        'filter' => 'Filter Date Range',
        'brand_title' => 'Official Supplier Statement of Account',
        'supplier_info' => 'Supplier Profile Info',
        'sup_code' => 'Supplier Code:',
        'phone' => 'Phone Number:',
        'email' => 'Email Address:',
        'tax_number' => 'VAT / Tax Number:',
        'period' => 'Statement Period & Balance',
        'from_date' => 'From Date:',
        'to_date' => 'To Date:',
        'col_date' => 'Date',
        'col_type' => 'Transaction Type',
        'col_ref' => 'Ref / Doc No.',
        'col_credit' => 'Credit (+) Payable',
        'col_debit' => 'Debit (-) Paid/Returned',
        'col_balance' => 'Running Balance',
        'no_tx' => 'No financial transactions recorded in this period.',
        'total_credit' => 'Total Purchases / Invoiced (Credit):',
        'total_debit' => 'Total Paid & Returned (Debit):',
        'final_balance' => 'Final Net Ending Balance Due:',
        'sig_acc' => 'Prepared by Accountant',
        'sig_auditor' => 'Internal Auditor Approval',
        'sig_vendor' => 'Supplier Confirmation & Stamp'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-navy: #0f172a; }
    .stmt-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: #0f172a; color: #ffffff; transition: 0.2s;}
    .btn-act:hover { background: #1e293b; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s;}

    .filter-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .filter-form { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
    .filter-input { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.9rem; }

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); border-top: 8px solid var(--c-navy); }
    .c-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 24px; }
    .c-brand { font-size: 1.8rem; font-weight: 900; color: #0f172a; margin: 0; }
    
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
    .info-box { background: #f8fafc; padding: 18px 20px; border-radius: 12px; border: 1px solid #e2e8f0; }
    .info-box h5 { margin: 0 0 10px 0; color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 6px; }
    .info-box p { margin: 4px 0; color: #0f172a; font-weight: 700; font-size: 0.92rem; }

    .table-stmt { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .table-stmt th { background: #0f172a; color: #ffffff; padding: 12px 10px; font-size: 0.8rem; text-align: start; border: 1px solid #0f172a; }
    .table-stmt td { padding: 10px; border: 1px solid #cbd5e1; font-size: 0.9rem; color: #1e293b; font-weight: 600; }

    .totals-summary { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 20px; margin-top: 24px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; text-align: center; }
    .total-item h5 { margin: 0 0 6px 0; color: #64748b; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; }
    .total-item p { margin: 0; font-family: monospace; font-size: 1.25rem; font-weight: 900; }

    .print-signatures { display: none; }

    /* ========================================= */
    /* إعدادات الطباعة الشاملة والحجب الإجباري */
    /* ========================================= */
    @media print {
        @page { size: A4 portrait; margin: 15mm; }
        
        /* 1. حجب جميع عناصر الصفحة خارج كارت الطباعة */
        body * {
            visibility: hidden !important;
        }
        
        /* 2. إظهار ورقة كشف الحساب فقط */
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

        /* 3. الإخفاء الجذري لكل مكونات البحث، الترقيم، و DataTables والفلتر */
        .table-pagination-nav,
        .table-pagination-nav *,
        .filter-card,
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

        .info-box { border: 1px solid #000 !important; background: transparent !important; page-break-inside: avoid !important; }
        .table-stmt th { background: #e2e8f0 !important; color: #000 !important; border: 1px solid #000 !important; -webkit-print-color-adjust: exact !important; }
        .table-stmt td { border: 1px solid #000 !important; color: #000 !important; }
        .totals-summary { border: 1px solid #000 !important; background: transparent !important; }

        .print-signatures { display: flex !important; justify-content: space-between !important; margin-top: 60px !important; page-break-inside: avoid !important; }
        .sig-box { text-align: center !important; flex: 1 !important; font-weight: 800 !important; font-size: 0.85rem !important; color: #000 !important; }
        .sig-line { border-top: 1px dashed #000 !important; width: 60% !important; margin: 40px auto 0 auto !important; }
    }
</style>

<div class="stmt-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="/ERP/purchasing/statements" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <h3 style="margin:0; font-weight: 800; color: #0f172a;"><?= $t['title'] ?></h3>
        </div>
        <button onclick="safePrint()" class="btn-act"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
    </div>

    <!-- فلتر التاريخ -->
    <div class="filter-card">
        <form action="/ERP/purchasing/statements/<?= $supplier->id ?>" method="GET" class="filter-form">
            <strong style="color:#0f172a; font-size:0.9rem;"><i class="ph-bold ph-funnel"></i> <?= $t['filter'] ?>:</strong>
            <label style="font-size:0.85rem; font-weight:700; color:#475569;"><?= $t['from_date'] ?></label>
            <input type="date" name="from_date" class="filter-input" value="<?= htmlspecialchars($fromDate) ?>">
            <label style="font-size:0.85rem; font-weight:700; color:#475569;"><?= $t['to_date'] ?></label>
            <input type="date" name="to_date" class="filter-input" value="<?= htmlspecialchars($toDate) ?>">
            <button type="submit" class="btn-act" style="padding: 8px 16px; font-size: 0.85rem; background:#1e293b;"><?= $isAr ? 'تطبيق' : 'Apply' ?></button>
        </form>
    </div>

    <div class="print-canvas">
        <div class="c-header">
            <div>
                <h1 class="c-brand">Nour Trust ERP</h1>
                <p style="margin:4px 0 0 0; color:#64748b; font-weight:700;"><?= $t['brand_title'] ?></p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <div style="font-family: monospace; font-size: 1.4rem; font-weight: 900; color: var(--c-navy);"><?= htmlspecialchars($supplier->code) ?></div>
                <div style="margin-top:4px; font-weight:800; color: #0f172a; font-size:0.85rem;"><?= date('Y-m-d') ?></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5><?= $t['supplier_info'] ?></h5>
                <p style="font-size: 1.1rem; color: #0f172a; margin-bottom:6px;"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars($supName) ?></p>
                <p><span style="color:#64748b;"><?= $t['sup_code'] ?></span> <span style="font-family:monospace;"><?= htmlspecialchars($supplier->code) ?></span></p>
                <p><span style="color:#64748b;"><?= $t['phone'] ?></span> <span style="font-family:monospace;"><?= htmlspecialchars($supplier->phone ?? '---') ?></span></p>
                <p><span style="color:#64748b;"><?= $t['email'] ?></span> <?= htmlspecialchars($supplier->email ?? '---') ?></p>
                <p><span style="color:#64748b;"><?= $t['tax_number'] ?></span> <span style="font-family:monospace;"><?= htmlspecialchars($supplier->tax_number ?? '---') ?></span></p>
            </div>
            <div class="info-box">
                <h5><?= $t['period'] ?></h5>
                <p><span style="color:#64748b;"><?= $t['from_date'] ?></span> <span style="font-family:monospace;"><?= $fromDate ?></span></p>
                <p><span style="color:#64748b;"><?= $t['to_date'] ?></span> <span style="font-family:monospace;"><?= $toDate ?></span></p>
            </div>
        </div>

        <table class="table-stmt">
            <thead>
                <tr>
                    <th style="width: 12%;"><?= $t['col_date'] ?></th>
                    <th style="width: 25%;"><?= $t['col_type'] ?></th>
                    <th style="width: 18%;"><?= $t['col_ref'] ?></th>
                    <th style="width: 15%; text-align: end;"><?= $t['col_credit'] ?></th>
                    <th style="width: 15%; text-align: end;"><?= $t['col_debit'] ?></th>
                    <th style="width: 15%; text-align: end;"><?= $t['col_balance'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($ledger)): foreach($ledger as $tx): ?>
                    <tr>
                        <td style="font-family: monospace; color: #475569;"><?= $tx->tx_date ?></td>
                        <td>
                            <strong style="color: <?= $tx->tx_type === 'invoice' ? '#2563eb' : '#dc2626' ?>;"><?= htmlspecialchars($tx->tx_title) ?></strong>
                            <?php if(!empty($tx->notes)): ?><div style="font-size:0.75rem; color:#64748b; margin-top:2px;"><?= htmlspecialchars($tx->notes) ?></div><?php endif; ?>
                        </td>
                        <td style="font-family: monospace; font-weight:800; color:#0f172a;"><?= htmlspecialchars($tx->ref_no) ?></td>
                        <td style="text-align: end; font-family: monospace; color:#059669; font-weight:800;"><?= $tx->credit > 0 ? number_format($tx->credit, 2) : '---' ?></td>
                        <td style="text-align: end; font-family: monospace; color:#2563eb; font-weight:800;"><?= $tx->debit > 0 ? number_format($tx->debit, 2) : '---' ?></td>
                        <td style="text-align: end; font-family: monospace; font-weight: 900; color:#0f172a; background:#f8fafc;"><?= number_format($tx->running_balance, 2) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" style="text-align:center; padding: 20px; color:#94a3b8;"><?= $t['no_tx'] ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- إحصائيات ملخصة للكشف -->
        <div class="totals-summary">
            <div class="total-item">
                <h5><?= $t['total_credit'] ?></h5>
                <p style="color:#059669;"><?= number_format($totalCredit ?? 0, 2) ?> <span style="font-size:0.8rem; color:#64748b;"><?= $currency ?></span></p>
            </div>
            <div class="total-item">
                <h5><?= $t['total_debit'] ?></h5>
                <p style="color:#2563eb;"><?= number_format($totalDebit ?? 0, 2) ?> <span style="font-size:0.8rem; color:#64748b;"><?= $currency ?></span></p>
            </div>
            <div class="total-item" style="border-inline-start: 1px dashed #cbd5e1;">
                <h5><?= $t['final_balance'] ?></h5>
                <p style="color: <?= ($runningBalance ?? 0) > 0 ? '#dc2626' : '#059669' ?>; font-size: 1.4rem;"><?= number_format($runningBalance ?? 0, 2) ?> <span style="font-size:0.8rem; color:#64748b;"><?= $currency ?></span></p>
            </div>
        </div>

        <div class="print-signatures">
            <div class="sig-box">
                <div><?= $t['sig_acc'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $t['sig_auditor'] ?></div>
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
    const selectors = ['.table-pagination-nav', '.dataTables_info', '.dataTables_paginate', '.pagination', '.filter-card'];
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