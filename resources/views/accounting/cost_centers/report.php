<?php
// Path: resources/views/accounting/cost_centers/report.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$t = [
    'ar' => [
        'back' => 'العودة للبطاقة', 'print' => 'طباعة التقرير', 'subtitle' => 'تقرير الأداء المالي والموازنة',
        'rev' => 'إجمالي الإيرادات المحققة', 'exp' => 'إجمالي المصروفات التراكمية', 'profit' => 'صافي أرباح / خسائر المركز', 'budget_use' => 'استهلاك الموازنة التقديرية',
        'breakdown' => 'تفكيك التكاليف والإيرادات بالحسابات', 'col_code' => 'الكود', 'col_acc' => 'الحساب', 'col_type' => 'النوع', 'col_net' => 'الصافي',
        'recent' => 'أحدث القيود المؤثرة على المركز', 'col_entry' => 'القيد', 'col_date' => 'التاريخ', 'col_amt' => 'المبلغ',
        'empty_acc' => 'لا توجد حركات مسجلة.', 'empty_tx' => 'لا توجد قيود مرحلة على هذا المركز.',
        'type_rev' => 'إيراد', 'type_exp' => 'مصروف', 'print_date' => 'تاريخ الاستخراج:'
    ],
    'en' => [
        'back' => 'Back to Profile', 'print' => 'Print Report', 'subtitle' => 'Financial Performance & Budget Report',
        'rev' => 'Total Revenues Realized', 'exp' => 'Total Accumulated Expenses', 'profit' => 'Net Profit / Loss', 'budget_use' => 'Budget Consumption',
        'breakdown' => 'Revenues & Expenses Breakdown', 'col_code' => 'Code', 'col_acc' => 'Account', 'col_type' => 'Type', 'col_net' => 'Net',
        'recent' => 'Recent Journal Entries', 'col_entry' => 'Entry', 'col_date' => 'Date', 'col_amt' => 'Amount',
        'empty_acc' => 'No recorded movements.', 'empty_tx' => 'No posted entries for this center.',
        'type_rev' => 'Revenue', 'type_exp' => 'Expense', 'print_date' => 'Report Date:'
    ]
][$isRtl ? 'ar' : 'en'];

$budget = (float)($center->budget_amount ?? 0);
$budgetUsed = $totalExpenses; // باعتبار الموازنة تخصص للمصروفات أساساً
$budgetPercent = $budget > 0 ? min(100, round(($budgetUsed / $budget) * 100, 1)) : 0;
$cNameVal = $isRtl ? ($center->name_ar ?: $center->name_en) : ($center->name_en ?: $center->name_ar);
?>

<style>
    :root { 
        --brand-primary: #7c3aed; --brand-primary-dark: #6d28d9; --brand-primary-light: #f5f3ff; 
        --surface: #ffffff; --surface-hover: #f8fafc; --bg-body: #f1f5f9;
        --text-main: #0f172a; --text-muted: #64748b; --border-color: #e2e8f0;
        --radius-lg: 24px; --radius-md: 16px;
        --shadow-soft: 0 10px 30px -10px rgba(15, 23, 42, 0.08);
        --shadow-hover: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
    }
    .report-wrapper { max-width: 1200px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: var(--bg-body);}
    
    .print-only-header { display: none; }

    .header-bar { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); padding: 24px 30px; margin-bottom: 30px; border-bottom: 1px solid rgba(226,232,240,0.8); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; position: sticky; top:0; z-index:10;}
    .btn-action { width: 48px; height: 48px; border-radius: 14px; background: var(--surface); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--text-muted); font-size: 1.3rem; transition: 0.3s; box-shadow: var(--shadow-soft);}
    .btn-action:hover { background: var(--brand-primary-light); color: var(--brand-primary); border-color: #ddd6fe; transform: translateX(<?= $isRtl ? '4px' : '-4px' ?>);}
    .btn-print { background: var(--text-main); color: #ffffff; border: none; padding: 14px 26px; border-radius: 12px; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; font-size: 0.95rem; box-shadow: var(--shadow-soft);}
    .btn-print:hover { background: #000000; transform: translateY(-2px); box-shadow: var(--shadow-hover);}

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 0 30px 30px 30px; }
    @media (max-width: 900px) { .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; box-shadow: var(--shadow-soft); transition: 0.3s;}
    .kpi-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); }
    .kpi-card h4 { margin: 0 0 10px 0; font-size: 0.85rem; color: var(--text-muted); font-weight: 800; }
    .kpi-card p { margin: 0; font-size: 1.8rem; font-weight: 900; font-family: monospace; color: var(--text-main); letter-spacing:-0.5px;}

    .progress-bar-bg { background: #e2e8f0; border-radius: 8px; height: 12px; width: 100%; overflow: hidden; margin-top: 12px; }
    .progress-bar-fill { background: var(--brand-primary); height: 100%; border-radius: 8px; transition: width 0.5s ease-in-out;}

    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin: 0 30px 30px 30px; }
    @media(max-width:900px){ .grid-2 { grid-template-columns: 1fr; } }

    .table-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-soft); display: flex; flex-direction: column;}
    .report-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; text-align: start; }
    .report-table th { padding: 18px 24px; background: var(--surface-hover); color: var(--text-muted); font-weight: 900; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; }
    .report-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; font-weight:700;}
    .report-table tr:hover td { background: var(--surface-hover); }

    /* ========================================================
       BULLETPROOF PRINT STYLES - تنسيقات الطباعة الخارقة (A4)
       ======================================================== */
    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        body * { visibility: hidden !important; }
        .report-wrapper, .report-wrapper * { visibility: visible !important; }
        
        .report-wrapper {
            position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; background-color: #ffffff !important;
        }

        .header-bar, .btn-action, .btn-print { display: none !important; }
        
        .print-only-header { 
            display: flex !important; align-items: center !important; justify-content: space-between !important; 
            border-bottom: 3px solid #000 !important; padding-bottom: 12px !important; margin-bottom: 20px !important; 
        }
        .print-logo-box { display: flex !important; align-items: center !important; gap: 15px !important; }
        .print-logo-box img { max-height: 50px !important; }
        .print-logo-box h2 { margin: 0 !important; font-size: 1.6rem !important; color: #000 !important; font-weight: 900 !important; }
        .print-meta { text-align: end !important; font-size: 0.9rem !important; font-weight: bold !important; color: #000 !important; }
        
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        
        .kpi-row { display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 10px !important; margin: 0 0 20px 0 !important; }
        .kpi-card { border: 2px solid #000 !important; box-shadow: none !important; padding: 12px !important; border-radius: 6px !important; page-break-inside: avoid; background: transparent !important;}
        .kpi-card h4 { color: #000 !important; font-size: 9pt !important; }
        .kpi-card p { color: #000 !important; font-size: 12pt !important; }
        
        .grid-2 { display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 10px !important; margin: 0 !important; }

        .table-card { border: 2px solid #000 !important; box-shadow: none !important; margin: 0 !important; border-radius: 6px !important; page-break-inside: auto;}
        .table-title-print { background: #e2e8f0 !important; color: #000 !important; border-bottom: 2px solid #000 !important;}
        .report-table { border-collapse: collapse !important; width: 100% !important; }
        .report-table th { background: #f1f5f9 !important; color: #000 !important; border-bottom: 2px solid #000 !important; border-left: 1px solid #000 !important; border-right: 1px solid #000 !important; font-weight: bold !important; font-size: 9pt !important; padding: 8px !important;}
        .report-table td { border: 1px solid #000 !important; color: #000 !important; padding: 8px !important; font-size: 9pt !important;}
        
        .progress-bar-bg { border: 1px solid #000 !important; background: transparent !important;}
        .progress-bar-fill { background: #000 !important;}
    }
</style>

<div class="report-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <!-- 📄 ترويسة الطباعة -->
    <div class="print-only-header">
        <div class="print-logo-box">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:2.8rem; color:#000;"></i>
            <?php endif; ?>
            <div>
                <h2><?= htmlspecialchars($cName) ?></h2>
                <span style="font-size:1.05rem; color:#475569; font-weight:900;"><?= $t['subtitle'] ?></span>
            </div>
        </div>
        <div class="print-meta">
            <?= htmlspecialchars($cNameVal) ?> (<?= htmlspecialchars($center->code) ?>)<br>
            <?= $t['print_date'] ?> <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:24px;">
            <a href="/ERP/accounting/cost-centers/<?= $center->id ?>" class="btn-action" title="<?= $t['back'] ?>"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.8rem; color:var(--text-main); font-weight:900;"><?= $t['subtitle'] ?>: <?= htmlspecialchars($cNameVal) ?></h2>
                <p style="margin:6px 0 0 0; color:var(--brand-primary-dark); font-weight:900; font-family:monospace; font-size:1.15rem;"><?= htmlspecialchars($center->code) ?></p>
            </div>
        </div>
        <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
    </div>

    <!-- KPIs -->
    <div class="kpi-row">
        <div class="kpi-card" style="border-bottom: 5px solid #10b981;">
            <h4 style="color:#059669;"><?= $t['rev'] ?></h4>
            <p style="color:#10b981;"><?= number_format($totalRevenues, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 5px solid #f43f5e;">
            <h4 style="color:#dc2626;"><?= $t['exp'] ?></h4>
            <p style="color:#f43f5e;"><?= number_format($totalExpenses, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 5px solid <?= $netProfit >= 0 ? '#4338ca' : '#f43f5e' ?>; background: <?= $netProfit >= 0 ? '#e0e7ff' : '#ffe4e6' ?>;">
            <h4 style="color:<?= $netProfit >= 0 ? '#4338ca' : '#dc2626' ?>;"><?= $t['profit'] ?></h4>
            <p style="color:<?= $netProfit >= 0 ? '#4338ca' : '#f43f5e' ?>;"><?= number_format($netProfit, 2) ?> <span style="font-size:1rem; font-weight:800;"><?= $currency ?></span></p>
        </div>
        <div class="kpi-card" style="border-bottom: 5px solid var(--brand-primary);">
            <h4 style="color:var(--brand-primary-dark);"><?= $t['budget_use'] ?></h4>
            <p style="color:var(--brand-primary-dark);"><?= $budgetPercent ?>%</p>
            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?= $budgetPercent ?>%; background: <?= $budgetPercent > 90 ? '#f43f5e' : 'var(--brand-primary)' ?>;"></div></div>
        </div>
    </div>

    <div class="grid-2">
        <div class="table-card">
            <div class="table-title-print" style="padding:22px 30px; background:var(--surface-hover); border-bottom:1px solid #e2e8f0; font-weight:900; color:var(--text-main); font-size:1.1rem;">
                <i class="ph-bold ph-chart-pie-slice" style="color:var(--brand-primary); font-size:1.4rem; vertical-align:middle; margin-inline-end:8px;"></i> <?= $t['breakdown'] ?>
            </div>
            <div style="flex:1; overflow-y:auto;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;"><?= $t['col_code'] ?></th>
                            <th style="width: 40%;"><?= $t['col_acc'] ?></th>
                            <th style="width: 15%;"><?= $t['col_type'] ?></th>
                            <th style="width: 25%; text-align:center;"><?= $t['col_net'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($accountBreakdown)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:50px; color:#94a3b8;"><?= $t['empty_acc'] ?></td></tr>
                        <?php else: foreach($accountBreakdown as $ab): 
                            $net = $ab->type === 'revenue' ? ($ab->total_credit - $ab->total_debit) : ($ab->total_debit - $ab->total_credit); 
                            $aName = $isRtl ? ($ab->name_ar ?: $ab->name_en) : ($ab->name_en ?: $ab->name_ar);
                        ?>
                            <tr>
                                <td style="font-family:monospace; font-weight:900; color:var(--brand-primary-dark); font-size:1.05rem;"><?= htmlspecialchars($ab->code) ?></td>
                                <td style="font-weight:800; color:var(--text-main); font-size:1rem;"><?= htmlspecialchars($aName) ?></td>
                                <td>
                                    <?php if($ab->type==='revenue'): ?>
                                        <span style="background:#d1fae5; color:#059669; padding:4px 10px; border-radius:6px; font-weight:900; font-size:0.75rem; border:1px solid #059669;"><?= $t['type_rev'] ?></span>
                                    <?php else: ?>
                                        <span style="background:#ffedd5; color:#ea580c; padding:4px 10px; border-radius:6px; font-weight:900; font-size:0.75rem; border:1px solid #ea580c;"><?= $t['type_exp'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center; font-family:monospace; font-weight:900; font-size:1.1rem; <?= $ab->type==='revenue'?'color:#10b981;':'color:#f43f5e;' ?>">
                                    <?= number_format((float)$net, 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card">
            <div class="table-title-print" style="padding:22px 30px; background:var(--surface-hover); border-bottom:1px solid #e2e8f0; font-weight:900; color:var(--text-main); font-size:1.1rem;">
                <i class="ph-bold ph-list-dashes" style="color:var(--brand-primary); font-size:1.4rem; vertical-align:middle; margin-inline-end:8px;"></i> <?= $t['recent'] ?>
            </div>
            <div style="flex:1; overflow-y:auto;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;"><?= $t['col_entry'] ?></th>
                            <th style="width: 20%;"><?= $t['col_date'] ?></th>
                            <th style="width: 35%;"><?= $t['col_acc'] ?></th>
                            <th style="width: 20%; text-align:center;"><?= $t['col_amt'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($transactions)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:50px; color:#94a3b8;"><?= $t['empty_tx'] ?></td></tr>
                        <?php else: foreach($transactions as $tx): 
                            $val = $tx->debit > 0 ? $tx->debit : $tx->credit; 
                            $aName = $isRtl ? ($tx->account_name ?: $tx->account_name_en) : ($tx->account_name_en ?: $tx->account_name);
                        ?>
                            <tr>
                                <td><a href="/ERP/accounting/journal-entries/<?= $tx->journal_entry_id ?>" style="font-family:monospace; font-weight:900; color:var(--brand-primary-dark); text-decoration:none; font-size:1.05rem;">#<?= htmlspecialchars($tx->entry_number) ?></a></td>
                                <td style="font-family:monospace; font-weight:800; color:#64748b;"><?= htmlspecialchars($tx->entry_date) ?></td>
                                <td style="font-weight:800; color:var(--text-main); font-size:0.95rem;"><?= htmlspecialchars($aName) ?></td>
                                <td style="text-align:center; font-family:monospace; font-weight:900; color:var(--text-main); font-size:1.1rem;"><?= number_format((float)$val, 2) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>