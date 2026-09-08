<?php
// Path: resources/views/accounting/journals/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$t = [
    'ar' => [
        'back' => 'رجوع للقائمة', 'print' => 'طباعة السند', 'edit' => 'تعديل السند',
        'subtitle' => 'سند قيد يومية عام (Journal Voucher)', 'voucher_no' => 'رقم القيد:',
        'date' => 'تاريخ القيد', 'ref' => 'رقم المرجع', 'status' => 'حالة القيد', 'branch' => 'الفرع/المركز',
        'desc' => 'البيان العام:', 'col_code' => 'رقم الحساب', 'col_acc' => 'اسم الحساب', 'col_cc' => 'مركز التكلفة', 'col_desc' => 'الشرح / البيان', 'col_dr' => 'مدين (+)', 'col_cr' => 'دائن (-)',
        'tot' => 'الإجمالي المالي', 'sig_1' => 'المحاسب المسؤول', 'sig_2' => 'المراجع المالي', 'sig_3' => 'اعتماد المدير المالي',
        'st_draft' => 'مسودة', 'st_posted' => 'مرحّل', 'general' => 'عام (مستوى الشركة)'
    ],
    'en' => [
        'back' => 'Back to List', 'print' => 'Print Voucher', 'edit' => 'Edit',
        'subtitle' => 'General Journal Voucher', 'voucher_no' => 'Voucher No:',
        'date' => 'Entry Date', 'ref' => 'Ref No.', 'status' => 'Status', 'branch' => 'Branch/Location',
        'desc' => 'General Description:', 'col_code' => 'Account Code', 'col_acc' => 'Account Name', 'col_cc' => 'Cost Center', 'col_desc' => 'Description / Details', 'col_dr' => 'Debit (+)', 'col_cr' => 'Credit (-)',
        'tot' => 'Total Amount', 'sig_1' => 'Accountant', 'sig_2' => 'Auditor', 'sig_3' => 'Finance Manager Approval',
        'st_draft' => 'Draft', 'st_posted' => 'Posted', 'general' => 'General (Company Level)'
    ]
][$isRtl ? 'ar' : 'en'];

function getEntryStatusBadgePrint($status, $t) {
    if ($status === 'posted') return "<span class='badge-print' style='background:#ecfdf5; color:#059669; padding:4px 12px; border-radius:6px; font-weight:900; font-size:0.85rem; border:1px solid #059669;'>{$t['st_posted']}</span>";
    return "<span class='badge-print' style='background:#fef3c7; color:#d97706; padding:4px 12px; border-radius:6px; font-weight:900; font-size:0.85rem; border:1px solid #d97706;'>{$t['st_draft']}</span>";
}

$branchBadge = !empty($entry->branch_name) ? ($isRtl ? $entry->branch_name : ($entry->branch_name_en ?: $entry->branch_name)) : $t['general'];
?>

<style>
    :root { 
        --brand-primary: #4f46e5; --brand-primary-dark: #3730a3; --brand-primary-light: #e0e7ff; 
        --surface: #ffffff; --surface-hover: #f8fafc; --bg-body: #f1f5f9;
        --text-main: #0f172a; --text-muted: #64748b; --border-color: #e2e8f0;
        --radius-lg: 24px; --radius-md: 16px;
        --shadow-soft: 0 10px 30px -10px rgba(15, 23, 42, 0.08);
        --shadow-hover: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
    }
    .voucher-wrapper { max-width: 1150px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: var(--bg-body);}
    
    .print-only-header { display: none; }

    .header-bar { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); padding: 24px 30px; margin-bottom: 30px; border-bottom: 1px solid rgba(226,232,240,0.8); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; position: sticky; top:0; z-index:10;}
    .btn-action { width: 48px; height: 48px; border-radius: 14px; background: var(--surface); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--text-muted); font-size: 1.3rem; transition: 0.3s; box-shadow: var(--shadow-soft);}
    .btn-action:hover { background: var(--brand-primary-light); color: var(--brand-primary); border-color: #a5b4fc; transform: translateX(<?= $isRtl ? '4px' : '-4px' ?>);}
    .btn-print { background: var(--text-main); color: #ffffff; border: none; padding: 14px 26px; border-radius: 12px; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; font-size: 0.95rem; box-shadow: var(--shadow-soft);}
    .btn-print:hover { background: #000000; transform: translateY(-2px); box-shadow: var(--shadow-hover);}

    .voucher-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 36px; border-top: 5px solid var(--brand-primary); margin: 0 30px 30px 30px; box-shadow: var(--shadow-soft); transition: 0.4s;}
    .voucher-card:hover { box-shadow: var(--shadow-hover); transform: translateY(-3px);}
    
    .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 24px; background: var(--surface-hover); padding: 20px; border-radius: 16px; border: 1px solid #f1f5f9; }
    @media (max-width: 900px) { .grid-4 { grid-template-columns: repeat(2, 1fr); } }
    .info-item h5 { margin: 0 0 6px 0; font-size: 0.78rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase; }
    .info-item p { margin: 0; font-size: 1.15rem; font-weight: 900; color: var(--text-main); font-family:monospace;}

    .items-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; text-align: start; margin-bottom: 30px;}
    .items-table th { padding: 16px 20px; background: var(--surface-hover); color: var(--text-muted); font-weight: 900; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; }
    .items-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; font-weight:700;}
    .items-table tr:hover td { background: var(--surface-hover); }
    
    .tfoot-row td { background: var(--surface-hover); font-weight: 900; font-size: 1.15rem; border-top: 3px solid #cbd5e1 !important; color: var(--text-main); }

    .signatures-block { display: none; margin-top: 60px; grid-template-columns: repeat(3, 1fr); text-align: center; font-weight: 900; font-size: 1.1rem; border-top: 2px dashed #000; padding-top: 30px; color: #000; }

    /* ========================================================
       BULLETPROOF PRINT STYLES - تنسيقات الطباعة الخارقة (A4)
       ======================================================== */
    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        
        body * { visibility: hidden !important; }
        .voucher-wrapper, .voucher-wrapper * { visibility: visible !important; }
        
        .voucher-wrapper {
            position: absolute !important;
            left: 0 !important; top: 0 !important;
            width: 100% !important; max-width: 100% !important;
            margin: 0 !important; padding: 0 !important;
            background-color: #ffffff !important;
        }

        .header-bar, .btn-action, .btn-print, .table-pagination-nav { display: none !important; }
        
        .print-only-header { 
            display: flex !important; align-items: center !important; justify-content: space-between !important; 
            border-bottom: 3px solid #000 !important; padding-bottom: 12px !important; margin-bottom: 20px !important; 
        }
        .print-logo-box { display: flex !important; align-items: center !important; gap: 15px !important; }
        .print-logo-box img { max-height: 55px !important; }
        .print-logo-box h2 { margin: 0 !important; font-size: 1.8rem !important; color: #000 !important; font-weight: 900 !important; }
        .print-meta { text-align: end !important; font-size: 1rem !important; font-weight: bold !important; color: #000 !important; }
        
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        
        .voucher-card { 
            border: none !important; box-shadow: none !important; border-top: none !important;
            margin: 0 !important; padding: 0 !important;
        }
        
        .grid-4 { 
            display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 10px !important; 
            background: transparent !important; border: 1px solid #000 !important; padding: 15px !important; border-radius: 6px !important;
            margin-bottom: 20px !important;
        }
        .info-item h5 { color: #000 !important; font-size: 9pt !important; }
        .info-item p { color: #000 !important; font-size: 11pt !important; }
        
        .items-table { border-collapse: collapse !important; width: 100% !important; border: 2px solid #000 !important;}
        .items-table th { 
            background: #e2e8f0 !important; color: #000 !important; 
            border-bottom: 2px solid #000 !important; border-left: 1px solid #000 !important; border-right: 1px solid #000 !important;
            font-weight: bold !important; font-size: 10pt !important; padding: 10px !important;
        }
        .items-table td { 
            border: 1px solid #000 !important; color: #000 !important; 
            padding: 10px !important; font-size: 10pt !important;
        }
        .tfoot-row td { background: #e2e8f0 !important; border-top: 2px solid #000 !important; }

        .signatures-block { display: grid !important; page-break-inside: avoid; }
        
        a { text-decoration: none !important; color: #000 !important; }
        .badge-print { border: 1px solid #000 !important; background: transparent !important; color: #000 !important;}
    }
</style>

<div class="voucher-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <!-- 📄 ترويسة الطباعة -->
    <div class="print-only-header">
        <div class="print-logo-box">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:3rem; color:#000;"></i>
            <?php endif; ?>
            <div>
                <h2><?= htmlspecialchars($cName) ?></h2>
                <span style="font-size:1.1rem; color:#475569; font-weight:900;"><?= $t['subtitle'] ?></span>
            </div>
        </div>
        <div class="print-meta">
            <?= $t['voucher_no'] ?> <?= htmlspecialchars($entry->entry_number) ?><br>
            Date: <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <!-- رأس الصفحة الطبيعي -->
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:24px;">
            <a href="/ERP/accounting/journal-entries" class="btn-action" title="<?= $t['back'] ?>"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.8rem; color:var(--text-main); font-weight:900;"><?= $t['subtitle'] ?></h2>
                <p style="margin:6px 0 0 0; color:var(--brand-primary-dark); font-weight:900; font-family:monospace; font-size:1.15rem;">#<?= htmlspecialchars($entry->entry_number) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:12px;">
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
            <?php if(($entry->status ?? '') === 'draft'): ?>
                <a href="/ERP/accounting/journal-entries/<?= $entry->id ?>/edit" class="btn-print" style="background:linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)); text-decoration:none; border:none;"><i class="ph-bold ph-pencil"></i> <?= $t['edit'] ?></a>
            <?php endif; ?>
        </div>
    </div>

    <!-- البطاقة الرئيسية -->
    <div class="voucher-card">
        <div class="grid-4">
            <div class="info-item">
                <h5><?= $t['date'] ?></h5>
                <p style="font-family:monospace; color:var(--brand-primary-dark); font-size:1.4rem;"><?= htmlspecialchars($entry->entry_date) ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['ref'] ?></h5>
                <p><?= htmlspecialchars($entry->reference_number ?: '---') ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['branch'] ?></h5>
                <p style="color:#0ea5e9; font-weight:900;"><i class="ph-bold ph-git-branch"></i> <?= htmlspecialchars($branchBadge) ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['status'] ?></h5>
                <div style="margin-top:8px;"><?= getEntryStatusBadgePrint($entry->status, $t) ?></div>
            </div>
        </div>

        <div style="margin-bottom:30px; font-weight:900; color:var(--text-main); font-size:1.15rem; background:#fff; padding:15px 20px; border-radius:12px; border:1px dashed #cbd5e1;">
            <?= $t['desc'] ?> <span style="font-weight:700; color:#334155; margin-inline-start:8px;"><?= htmlspecialchars($entry->description) ?></span>
        </div>

        <!-- الجدول التفصيلي -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 15%;"><?= $t['col_code'] ?></th>
                    <th style="width: 25%;"><?= $t['col_acc'] ?></th>
                    <th style="width: 15%;"><?= $t['col_cc'] ?></th>
                    <th style="width: 21%;"><?= $t['col_desc'] ?></th>
                    <th style="width: 12%; text-align: center;"><?= $t['col_dr'] ?></th>
                    <th style="width: 12%; text-align: center;"><?= $t['col_cr'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $tDebit = 0; $tCredit = 0; foreach ($items as $item): $tDebit += $item->debit; $tCredit += $item->credit; 
                    $aName = $isRtl ? ($item->acc_name_ar ?? $item->acc_name) : ($item->acc_name_en ?: ($item->acc_name_ar ?? $item->acc_name));
                    $ccName = $item->cc_name ?: '---';
                ?>
                    <tr>
                        <td style="font-family:monospace; font-weight:900; color:var(--brand-primary-dark); font-size:1.05rem;"><?= htmlspecialchars($item->acc_code) ?></td>
                        <td style="font-weight:800; color:var(--text-main); font-size:1rem;"><?= htmlspecialchars($aName) ?></td>
                        <td style="font-weight:700; color:#475569; font-size:0.9rem;"><?= htmlspecialchars($ccName) ?></td>
                        <td style="color:#64748b; font-weight:700;"><?= htmlspecialchars($item->description ?: '---') ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669; font-size:1.15rem;"><?= number_format((float)$item->debit, 2) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626; font-size:1.15rem;"><?= number_format((float)$item->credit, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="tfoot-row">
                    <td colspan="4" style="text-align:end; padding:18px 20px;"><?= $t['tot'] ?></td>
                    <td style="text-align:center; font-family:monospace; color:#059669; font-size:1.25rem;"><?= number_format($tDebit, 2) ?></td>
                    <td style="text-align:center; font-family:monospace; color:#dc2626; font-size:1.25rem;"><?= number_format($tCredit, 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- بلوك التوقيعات للطباعة الرسمية -->
        <div class="signatures-block">
            <div><?= $t['sig_1'] ?><br><br><br>...........................</div>
            <div><?= $t['sig_2'] ?><br><br><br>...........................</div>
            <div><?= $t['sig_3'] ?><br><br><br>...........................</div>
        </div>
    </div>
</div>

<script>
function purgeControlsForPrint() {
    const selectors = ['.table-pagination-nav', '.dataTables_info', '.dataTables_paginate', '.pagination'];
    selectors.forEach(s => {
        document.querySelectorAll(s).forEach(el => {
            if(el) el.style.display = 'none';
        });
    });
}
function safePrint() {
    purgeControlsForPrint();
    window.print();
}
window.addEventListener("beforeprint", purgeControlsForPrint);
</script>