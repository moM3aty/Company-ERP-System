<?php
// Path: resources/views/accounting/budgets/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');

global $companyName, $companyLogo;
$cNameGlob = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$t = [
    'ar' => [
        'back' => 'رجوع للقائمة', 'print' => 'طباعة التقرير', 'edit' => 'تعديل',
        'subtitle' => 'تقرير أداء الموازنة (Budget Performance)', 'year' => 'السنة المالية:', 'branch' => 'الفرع:',
        'alloc' => 'المبلغ المعتمد', 'spent' => 'المصروف الفعلي التراكمي', 'remain' => 'الرصيد المتبقي', 'usage' => 'نسبة الاستهلاك',
        'link_title' => 'التتبع المحاسبي للموازنة', 'acc' => 'الحساب المرتبط:', 'cc' => 'مركز التكلفة:',
        'tx_title' => 'القيود والحركات الفعلية المخصومة من الموازنة',
        'col_date' => 'تاريخ القيد', 'col_ref' => 'رقم القيد', 'col_desc' => 'البيان', 'col_amt' => 'المبلغ المخصوم',
        'empty' => 'لم يتم إثبات أو ترحيل قيود مصروفات على هذه الموازنة بعد.', 'null' => 'غير محدد (غير مربوط)', 'general' => 'عام (كل الفروع)',
        'print_date' => 'تاريخ الاستخراج:', 'b_name' => 'اسم الموازنة:'
    ],
    'en' => [
        'back' => 'Back to List', 'print' => 'Print Report', 'edit' => 'Edit',
        'subtitle' => 'Budget Performance Report', 'year' => 'Fiscal Year:', 'branch' => 'Branch:',
        'alloc' => 'Allocated Amount', 'spent' => 'Actual Spent', 'remain' => 'Remaining Balance', 'usage' => 'Usage %',
        'link_title' => 'Accounting Linkage', 'acc' => 'Linked Account:', 'cc' => 'Linked Cost Center:',
        'tx_title' => 'Actual Posted Transactions Deducted from Budget',
        'col_date' => 'Date', 'col_ref' => 'Entry No.', 'col_desc' => 'Description', 'col_amt' => 'Deducted Amt',
        'empty' => 'No actual posted expenses recorded for this budget yet.', 'null' => 'Not linked', 'general' => 'General (All Branches)',
        'print_date' => 'Report Date:', 'b_name' => 'Budget Name:'
    ]
][$isRtl ? 'ar' : 'en'];

$bName = $isRtl ? ($budget->name_ar ?: $budget->name_en) : ($budget->name_en ?: $budget->name_ar);
$branchBadge = !empty($budget->branch_name) ? ($isRtl ? $budget->branch_name : ($budget->branch_name_en ?: $budget->branch_name)) : $t['general'];
$accName = $budget->account_id ? ($isRtl ? ($budget->acc_name) : ($budget->acc_code)) : $t['null'];
$ccName  = $budget->cost_center_id ? ($isRtl ? ($budget->cc_name) : ($budget->cc_code)) : $t['null'];

// تحديد لون الـ Progress Bar حسب الاستهلاك
$barColor = '#10b981'; // Green
if ($percent >= 75) $barColor = '#f59e0b'; // Amber
if ($percent >= 90) $barColor = '#ef4444'; // Red
?>

<style>
    :root { 
        --brand-primary: #d97706; --brand-primary-dark: #b45309; --brand-primary-light: #fef3c7; 
        --surface: #ffffff; --surface-hover: #f8fafc; --bg-body: #f1f5f9;
        --text-main: #0f172a; --text-muted: #64748b; --border-color: #e2e8f0;
        --radius-lg: 24px; --radius-md: 16px;
        --shadow-soft: 0 10px 30px -10px rgba(15, 23, 42, 0.08);
        --shadow-hover: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
    }
    .bg-show-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: var(--bg-body);}
    
    .print-only-header { display: none; }

    .header-bar { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); padding: 24px 30px; margin-bottom: 30px; border-bottom: 1px solid rgba(226,232,240,0.8); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; position: sticky; top:0; z-index:10;}
    .btn-action { width: 48px; height: 48px; border-radius: 14px; background: var(--surface); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--text-muted); font-size: 1.3rem; transition: 0.3s; box-shadow: var(--shadow-soft);}
    .btn-action:hover { background: var(--brand-primary-light); color: var(--brand-primary); border-color: #fcd34d; transform: translateX(<?= $isRtl ? '4px' : '-4px' ?>);}
    .btn-print { background: var(--text-main); color: #ffffff; border: none; padding: 14px 26px; border-radius: 12px; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; font-size: 0.95rem; box-shadow: var(--shadow-soft);}
    .btn-print:hover { background: #000000; transform: translateY(-2px); box-shadow: var(--shadow-hover);}

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 0 30px 30px 30px; }
    @media (max-width: 900px) { .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; box-shadow: var(--shadow-soft); transition: 0.3s;}
    .kpi-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); }
    .kpi-card h4 { margin: 0 0 10px 0; font-size: 0.85rem; color: var(--text-muted); font-weight: 800; }
    .kpi-card p { margin: 0; font-size: 1.8rem; font-weight: 900; font-family: monospace; color: var(--text-main); letter-spacing:-0.5px;}

    .progress-bar-bg { background: #e2e8f0; border-radius: 8px; height: 12px; width: 100%; overflow: hidden; margin-top: 12px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);}
    .progress-bar-fill { height: 100%; border-radius: 8px; transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);}

    .info-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 24px 32px; border-left: 5px solid var(--brand-primary); margin: 0 30px 30px 30px; box-shadow: var(--shadow-soft); display:flex; align-items:center; gap:30px;}
    @media (max-width: 768px) { .info-card { flex-direction: column; align-items: flex-start; gap: 15px; } }
    .info-item h5 { margin: 0 0 6px 0; font-size: 0.8rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase; }
    .info-item p { margin: 0; font-size: 1.15rem; font-weight: 900; color: var(--text-main); font-family:inherit;}

    .table-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; margin: 0 30px 30px 30px; box-shadow: var(--shadow-soft); }
    .tx-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; text-align: start; }
    .tx-table th { padding: 18px 24px; background: var(--surface-hover); color: var(--text-muted); font-weight: 900; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; }
    .tx-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; font-weight:700;}
    .tx-table tr:hover td { background: var(--surface-hover); }

    /* ========================================================
       BULLETPROOF PRINT STYLES - A4 Budget Report
       ======================================================== */
    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        body * { visibility: hidden !important; }
        .bg-show-wrapper, .bg-show-wrapper * { visibility: visible !important; }
        
        .bg-show-wrapper { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; background-color: #ffffff !important;}
        .header-bar, .btn-action, .btn-print, .table-pagination-nav { display: none !important; }
        
        .print-only-header { display: flex !important; align-items: flex-end !important; justify-content: space-between !important; border-bottom: 3px solid #000 !important; padding-bottom: 12px !important; margin-bottom: 20px !important; }
        .print-logo-box { display: flex !important; align-items: center !important; gap: 15px !important; }
        .print-logo-box img { max-height: 50px !important; }
        .print-logo-box h2 { margin: 0 !important; font-size: 1.6rem !important; color: #000 !important; font-weight: 900 !important; }
        .print-meta { text-align: end !important; font-size: 0.9rem !important; font-weight: bold !important; color: #000 !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        
        .kpi-row { display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 10px !important; margin: 0 0 20px 0 !important; }
        .kpi-card { border: 2px solid #000 !important; box-shadow: none !important; padding: 12px !important; border-radius: 6px !important; page-break-inside: avoid; background: transparent !important;}
        .kpi-card h4 { color: #000 !important; font-size: 9pt !important; }
        .kpi-card p { color: #000 !important; font-size: 12pt !important; }
        .progress-bar-bg { border: 1px solid #000 !important; background: transparent !important;}
        .progress-bar-fill { background: #000 !important; }
        
        .info-card { border: 2px solid #000 !important; box-shadow: none !important; margin: 0 0 20px 0 !important; border-radius: 6px !important; page-break-inside: avoid; padding: 15px !important; border-left: 2px solid #000 !important;}
        
        .table-card { border: 2px solid #000 !important; box-shadow: none !important; margin: 0 !important; border-radius: 6px !important; page-break-inside: auto;}
        .table-title-print { background: #e2e8f0 !important; color: #000 !important; border-bottom: 2px solid #000 !important;}
        .tx-table { border-collapse: collapse !important; width: 100% !important; }
        .tx-table th { background: #f1f5f9 !important; color: #000 !important; border-bottom: 2px solid #000 !important; border-left: 1px solid #000 !important; border-right: 1px solid #000 !important; font-weight: bold !important; font-size: 9pt !important; padding: 8px !important;}
        .tx-table td { border: 1px solid #000 !important; color: #000 !important; padding: 8px !important; font-size: 9pt !important;}
        
        a { text-decoration: none !important; color: #000 !important; }
    }
</style>

<div class="bg-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <!-- 📄 ترويسة الطباعة -->
    <div class="print-only-header">
        <div class="print-logo-box">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:2.8rem; color:#000;"></i>
            <?php endif; ?>
            <div>
                <h2><?= htmlspecialchars($cNameGlob) ?></h2>
                <span style="font-size:1.05rem; color:#475569; font-weight:900;"><?= $t['subtitle'] ?></span>
            </div>
        </div>
        <div class="print-meta">
            <?= $t['b_name'] ?> <?= htmlspecialchars($bName) ?><br>
            <?= $t['print_date'] ?> <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <!-- رأس الصفحة الطبيعي -->
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:24px;">
            <a href="/ERP/accounting/budgets" class="btn-action" title="<?= $t['back'] ?>"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.8rem; color:var(--text-main); font-weight:900;"><?= htmlspecialchars($bName) ?></h2>
                <div style="display:flex; gap:12px; margin-top:6px;">
                    <span style="color:var(--brand-primary-dark); font-weight:900; font-family:monospace; font-size:1.15rem;"><i class="ph-bold ph-calendar-blank"></i> <?= htmlspecialchars($budget->fiscal_year) ?></span>
                    <span style="color:#0ea5e9; font-weight:900; font-family:monospace; font-size:1.15rem;"><i class="ph-bold ph-git-branch"></i> <?= htmlspecialchars($branchBadge) ?></span>
                </div>
            </div>
        </div>
        <div style="display:flex; gap:12px;">
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
            <a href="/ERP/accounting/budgets/<?= $budget->id ?>/edit" class="btn-print" style="background:linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)); text-decoration:none; border:none;"><i class="ph-bold ph-pencil"></i> <?= $t['edit'] ?></a>
        </div>
    </div>

    <!-- ملخص الأرصدة والـ Progress Bar -->
    <div class="kpi-row">
        <div class="kpi-card" style="border-bottom: 5px solid #0f172a;">
            <h4><?= $t['alloc'] ?></h4>
            <p><?= number_format((float)$budget->total_amount, 2) ?> <span style="font-size:1rem;"><?= $currency ?></span></p>
        </div>
        <div class="kpi-card" style="border-bottom: 5px solid #dc2626;">
            <h4 style="color:#dc2626;"><?= $t['spent'] ?></h4>
            <p style="color:#dc2626;"><?= number_format((float)$actualSpent, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 5px solid #059669; background:#ecfdf5;">
            <h4 style="color:#059669;"><?= $t['remain'] ?></h4>
            <p style="color:#059669;"><?= number_format((float)$remaining, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 5px solid <?= $barColor ?>;">
            <h4 style="color:<?= $barColor ?>;"><?= $t['usage'] ?></h4>
            <p style="color:<?= $barColor ?>;"><?= $percent ?>%</p>
            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?= $percent ?>%; background: <?= $barColor ?>;"></div></div>
        </div>
    </div>

    <!-- بطاقة الربط المحاسبي -->
    <div class="info-card">
        <div><i class="ph-duotone ph-link" style="font-size:2.5rem; color:var(--brand-primary);"></i></div>
        <div style="flex:1;">
            <div style="font-size:1.15rem; font-weight:900; color:var(--text-main); margin-bottom:10px;"><?= $t['link_title'] ?></div>
            <div style="display:flex; flex-wrap:wrap; gap:30px;">
                <div>
                    <span style="color:var(--text-muted); font-size:0.85rem; font-weight:800; display:block;"><?= $t['acc'] ?></span>
                    <span style="font-weight:900; color:#059669; font-size:1.05rem;"><i class="ph-bold ph-tree-structure"></i> <?= htmlspecialchars($accName) ?></span>
                </div>
                <div>
                    <span style="color:var(--text-muted); font-size:0.85rem; font-weight:800; display:block;"><?= $t['cc'] ?></span>
                    <span style="font-weight:900; color:#7c3aed; font-size:1.05rem;"><i class="ph-bold ph-target"></i> <?= htmlspecialchars($ccName) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول القيود الفعلية المخصومة -->
    <div class="table-card">
        <div class="table-title-print" style="padding:22px 30px; background:var(--surface-hover); border-bottom:1px solid #e2e8f0; font-weight:900; color:var(--text-main); font-size:1.1rem; display:flex; justify-content:space-between;">
            <span><i class="ph-bold ph-list-dashes" style="color:var(--brand-primary); font-size:1.4rem; vertical-align:middle; margin-inline-end:8px;"></i> <?= $t['tx_title'] ?></span>
            <span style="font-size:0.85rem; color:#fff; background:#10b981; padding:4px 12px; border-radius:6px; font-weight:900;">POSTED ✓</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="tx-table">
                <thead>
                    <tr>
                        <th style="width: 15%;"><?= $t['col_date'] ?></th>
                        <th style="width: 20%;"><?= $t['col_ref'] ?></th>
                        <th style="width: 45%;"><?= $t['col_desc'] ?></th>
                        <th style="width: 20%; text-align:center;"><?= $t['col_amt'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:60px; color:#94a3b8; font-weight:800; font-size:1.1rem; border-bottom:none;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach ($transactions as $tx): 
                        $val = $tx->debit - $tx->credit; // Assuming it's an expense account where debit is positive
                    ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:#475569; font-size:1.05rem;"><?= htmlspecialchars($tx->entry_date) ?></td>
                            <td><a href="/ERP/accounting/journal-entries/<?= $tx->journal_entry_id ?>" style="font-family:monospace; font-weight:900; color:var(--brand-primary-dark); text-decoration:none; font-size:1.05rem;">#<?= htmlspecialchars($tx->entry_number) ?></a></td>
                            <td style="font-weight:800; color:var(--text-main);"><?= htmlspecialchars($tx->description ?: $tx->entry_desc) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626; font-size:1.15rem;"><?= number_format((float)$val, 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
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