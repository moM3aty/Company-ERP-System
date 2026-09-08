<?php
// Path: resources/views/accounting/reports/balance_sheet.php

if (session_status() === PHP_SESSION_NONE) session_start();

$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

$cName = function_exists('current_company_name') ? current_company_name() : ($_SESSION['company_name'] ?? 'NOUR TRUST');
$cLogo = $_SESSION['company_logo'] ?? '/assets/img/default-logo.png';

// تعيين قيم افتراضية متينة لتفادي أي خطأ متصفح
$asOfDate = $asOfDate ?? date('Y-m-d');
$branchId = $branchId ?? 0;
$branches = $branches ?? [];

$totCurrAssets = $totCurrAssets ?? 0.0;
$totNonCurrAssets = $totNonCurrAssets ?? 0.0;
$totCurrLiab = $totCurrLiab ?? 0.0;
$totNonCurrLiab = $totNonCurrLiab ?? 0.0;
$totEquity = $totEquity ?? 0.0;
$currentNetProfit = $currentNetProfit ?? 0.0;
$grandTotalAssets = $grandTotalAssets ?? 0.0;
$grandTotalLiabAndEquity = $grandTotalLiabAndEquity ?? 0.0;
$workingCapital = $workingCapital ?? 0.0;

$currAssets = $currAssets ?? [];
$nonCurrAssets = $nonCurrAssets ?? [];
$currLiabilities = $currLiabilities ?? [];
$nonCurrLiabilities = $nonCurrLiabilities ?? [];
$equity = $equity ?? [];

// قاموس الترجمة الموحد
$t = [
    'ar' => [
        'title' => 'الميزانية العمومية والمركز المالي',
        'desc' => 'عرض محاسبي مبوب ومتوازن للأصول، الالتزامات، وحقوق الملكية وفق المعايير المالية.',
        'as_of' => 'موقوفة حتى تاريخ',
        'branch' => 'الفرع / القطاع',
        'all_branches' => '-- كافة الفروع (الشركة بالكامل) --',
        'btn_view' => 'تحديث الميزانية',
        'print' => 'طباعة التقرير A4',
        'excel' => 'تصدير شيت Excel',
        'kpi_assets' => 'إجمالي الأصول (Total Assets)',
        'kpi_liab' => 'إجمالي الالتزامات (Total Liabilities)',
        'kpi_equity' => 'حقوق الملكية والأرباح',
        'kpi_working_cap' => 'رأس المال العامل (Working Capital)',
        'sec_assets' => 'أولاً: الأصول (Assets)',
        'curr_assets' => '1. الأصول المتداولة (Current Assets)',
        'non_curr_assets' => '2. الأصول غير المتداولة (Non-Current Assets)',
        'sec_liab_equity' => 'ثانياً: الخصوم وحقوق الملكية (Liabilities & Equity)',
        'curr_liab' => '1. الالتزامات المتداولة (Current Liabilities)',
        'non_curr_lia' => '2. الالتزامات غير المتداولة (Long-Term Liabilities)',
        'equity_sec' => '3. حقوق الملكية (Owners Equity)',
        'cur_profit' => 'أرباح / خسائر العام الحالية (Net Income):',
        'tot_assets' => 'إجمالي الأصول:',
        'tot_liab_eq' => 'إجمالي الخصوم وحقوق الملكية:',
        'col_code' => 'الكود',
        'col_name' => 'اسم الحساب المحاسبي',
        'col_bal' => 'الرصيد',
        'subtotal' => 'المجموع الفرعي:',
        'empty' => 'لا توجد حسابات مسجلة في هذا البند.',
        'print_date' => 'تاريخ الاستخراج:'
    ],
    'en' => [
        'title' => 'Balance Sheet & Financial Position',
        'desc' => 'Categorized overview of company Assets, Liabilities, and Equity.',
        'as_of' => 'As of Date',
        'branch' => 'Branch / Division',
        'all_branches' => '-- All Branches (Company-wide) --',
        'btn_view' => 'Update Report',
        'print' => 'Print A4 Report',
        'excel' => 'Export to Excel',
        'kpi_assets' => 'Total Assets',
        'kpi_liab' => 'Total Liabilities',
        'kpi_equity' => 'Equity & Retained Earnings',
        'kpi_working_cap' => 'Working Capital',
        'sec_assets' => 'I. Assets',
        'curr_assets' => '1. Current Assets',
        'non_curr_assets' => '2. Non-Current Assets',
        'sec_liab_equity' => 'II. Liabilities & Owners Equity',
        'curr_liab' => '1. Current Liabilities',
        'non_curr_lia' => '2. Long-Term Liabilities',
        'equity_sec' => '3. Owners Equity',
        'cur_profit' => 'Current Year Net Income / Loss:',
        'tot_assets' => 'Total Assets:',
        'tot_liab_eq' => 'Total Liabilities & Equity:',
        'col_code' => 'Code',
        'col_name' => 'Account Name',
        'col_bal' => 'Balance',
        'subtotal' => 'Subtotal:',
        'empty' => 'No accounts registered under this section.',
        'print_date' => 'Generated Date:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-bs: #4f46e5;
        --c-bs-dark: #3730a3;
        --c-bs-light: #eeef2;
        --c-border: #cbd5e1;
        --c-text: #0f172a;
        --c-muted: #64748b;
    }

    .bs-wrapper {
        margin: 0 auto;
        padding-bottom: 50px;
        font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>;
        color: var(--c-text);
    }

    .print-only-header { display: none; }

    /* Header Bar */
    .bs-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e2e8f0;
    }
    .bs-title-box { display: flex; align-items: center; gap: 16px; }
    .bs-icon {
        width: 52px; height: 52px;
        background: #e0e7ff; color: var(--c-bs);
        border-radius: 14px; display: flex;
        align-items: center; justify-content: center;
        font-size: 1.8rem; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
    }

    .btn-action {
        padding: 11px 20px; border-radius: 10px; font-weight: 800;
        border: none; cursor: pointer; display: inline-flex; align-items: center;
        gap: 8px; font-size: 0.88rem; transition: all 0.2s;
    }
    .btn-print { background: var(--c-bs); color: #fff; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2); }
    .btn-print:hover { background: var(--c-bs-dark); }
    .btn-excel { background: #059669; color: #fff; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2); }
    .btn-excel:hover { background: #047857; }

    /* Filter Panel */
    .filter-card {
        background: #ffffff; border: 1px solid var(--c-border);
        border-radius: 16px; padding: 20px; display: flex;
        gap: 16px; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .form-group { flex: 1; min-width: 180px; }
    .form-label { font-size: 0.85rem; font-weight: 800; color: var(--c-muted); margin-bottom: 6px; display: block; }
    .form-control {
        border: 1px solid var(--c-border); border-radius: 10px;
        padding: 10px 14px; font-family: inherit; font-size: 0.9rem;
        background: #f8fafc; font-weight: 700; width: 100%; box-sizing: border-box;
    }
    .form-control:focus { outline: none; border-color: var(--c-bs); background: #ffffff; }

    /* KPIs */
    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:900px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card {
        background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px;
        padding: 18px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        border-top: 4px solid var(--c-border);
    }
    .kpi-card h4 { margin: 0 0 6px 0; font-size: 0.78rem; color: var(--c-muted); font-weight: 800; }
    .kpi-card p { margin: 0; font-size: clamp(1.1rem, 1.5vw, 1.4rem); font-weight: 900; font-family: monospace; }

    /* Two-Column Statement Grid */
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media(max-width:900px){ .grid-2 { grid-template-columns: 1fr; } }
    
    .card-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .card-box-header {
        padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
        font-weight: 900; font-size: 1.05rem; display: flex; align-items: center; gap: 10px;
    }

    .table-rep { width: 100%; border-collapse: collapse; font-size: 0.88rem; table-layout: fixed; }
    .table-rep th { padding: 12px 16px; background: #f1f5f9; color: var(--c-muted); font-weight: 800; border-bottom: 2px solid #cbd5e1; text-align: start; font-size: 0.78rem; }
    .table-rep td { padding: 11px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; word-wrap: break-word; }

    .sec-header td { background: #f8fafc; font-weight: 900; color: #0f172a; font-size: 0.9rem; border-top: 1px solid #e2e8f0; }
    .subtotal-row td { background: #fafafa; font-weight: 800; color: #0f172a; border-top: 1px dashed #cbd5e1; }
    .total-row td { background: #1e293b; color: #ffffff !important; font-weight: 900; font-size: 1.05rem; }

    /* Print A4 Styles */
    @media print {
        @page { size: A4 portrait; margin: 10mm 12mm; }
        body { background: #ffffff !important; color: #000000 !important; font-size: 8.5pt !important; }
        .bs-header, .filter-card, button, form { display: none !important; }
        .bs-wrapper { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .print-only-header { display: flex !important; align-items: center; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 18px; }
        .kpi-row { display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 8px !important; margin-bottom: 16px !important; }
        .kpi-card { border: 1px solid #000000 !important; box-shadow: none !important; padding: 8px !important; }
        .grid-2 { display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 12px !important; }
        .card-box { border: 1px solid #000000 !important; box-shadow: none !important; }
        .table-rep th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; }
        .table-rep td { border: 1px solid #cbd5e1 !important; padding: 5px 7px !important; }
        .total-row td { background: #0f172a !important; color: #ffffff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>

<div class="bs-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">

    <!-- الترويسة المخصصة للطباعة -->
    <div class="print-only-header">
        <div style="display:flex; align-items:center; gap:12px;">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:45px;">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:2.2rem; color:#0f172a;"></i>
            <?php endif; ?>
            <div>
                <h2 style="margin:0; font-size:1.3rem; color:#000; font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                <span style="font-size:0.8rem; color:#475569; font-weight:bold;"><?= $t['title'] ?></span>
            </div>
        </div>
        <div style="text-align:end; font-size:0.8rem; font-weight:bold; color:#0f172a;">
            <?= $t['as_of'] ?>: <span dir="ltr"><?= htmlspecialchars($asOfDate) ?></span><br>
            <?= $t['print_date'] ?> <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <!-- شريط العنوان والتحكم -->
    <div class="bs-header">
        <div class="bs-title-box">
            <div class="bs-icon"><i class="ph-bold ph-scales"></i></div>
            <div>
                <h2 style="margin:0; font-weight:900; font-size:1.5rem;"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.88rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="button" onclick="exportExcel('Balance_Sheet_<?= $asOfDate ?>')" class="btn-action btn-excel"><i class="ph-bold ph-file-xls"></i> <?= $t['excel'] ?></button>
            <button type="button" onclick="window.print()" class="btn-action btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <!-- فلتر الميزانية -->
    <form action="/ERP/accounting/reports/balance-sheet" method="GET" class="filter-card">
        <div class="form-group">
            <label class="form-label"><?= $t['as_of'] ?></label>
            <input type="date" name="as_of_date" class="form-control" value="<?= htmlspecialchars($asOfDate) ?>" required>
        </div>

        <?php if(!empty($branches)): ?>
        <div class="form-group">
            <label class="form-label"><?= $t['branch'] ?></label>
            <select name="branch_id" class="form-control">
                <option value="0"><?= $t['all_branches'] ?></option>
                <?php foreach($branches as $b): 
                    $bName = $isRtl ? ($b->name_ar ?? '') : ($b->name_en ?: ($b->name_ar ?? ''));
                ?>
                    <option value="<?= $b->id ?>" <?= $branchId == $b->id ? 'selected' : '' ?>><?= htmlspecialchars($bName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn-action btn-print" style="height:42px;"><i class="ph-bold ph-funnel"></i> <?= $t['btn_view'] ?></button>
    </form>

    <!-- بطاقات KPIs الرقمية -->
    <div class="kpi-row">
        <div class="kpi-card" style="border-top-color: #059669;">
            <h4><?= $t['kpi_assets'] ?></h4>
            <p style="color:#059669;"><?= number_format($convert($grandTotalAssets), 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></p>
        </div>
        <div class="kpi-card" style="border-top-color: #dc2626;">
            <h4><?= $t['kpi_liab'] ?></h4>
            <p style="color:#dc2626;"><?= number_format($convert($totCurrLiab + $totNonCurrLiab), 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></p>
        </div>
        <div class="kpi-card" style="border-top-color: #2563eb;">
            <h4><?= $t['kpi_equity'] ?></h4>
            <p style="color:#2563eb;"><?= number_format($convert($totEquity + $currentNetProfit), 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></p>
        </div>
        <div class="kpi-card" style="border-top-color: <?= $workingCapital >= 0 ? '#4f46e5' : '#dc2626' ?>;">
            <h4><?= $t['kpi_working_cap'] ?></h4>
            <p style="color:<?= $workingCapital >= 0 ? '#4f46e5' : '#dc2626' ?>;"><?= number_format($convert($workingCapital), 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></p>
        </div>
    </div>

    <!-- شبكة القائمة المزدوجة (الأصول | الخصوم) -->
    <div class="grid-2" id="balanceSheetContent">
        
        <!-- جانب الأصول -->
        <div class="card-box">
            <div class="card-box-header" style="color:#059669;">
                <i class="ph-bold ph-trend-up"></i> <?= $t['sec_assets'] ?>
            </div>
            <table class="table-rep">
                <thead>
                    <tr>
                        <th style="width:25%;"><?= $t['col_code'] ?></th>
                        <th style="width:45%;"><?= $t['col_name'] ?></th>
                        <th style="width:30%; text-align:center;"><?= $t['col_bal'] ?> (<?= $currency ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="sec-header"><td colspan="3"><?= $t['curr_assets'] ?></td></tr>
                    <?php if(empty($currAssets)): ?>
                        <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:12px;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach($currAssets as $a): 
                        $aName = $isRtl ? ($a->name_ar ?? '') : ($a->name_en ?: ($a->name_ar ?? ''));
                    ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:#475569;"><?= htmlspecialchars((string)($a->code ?? '')) ?></td>
                            <td style="font-weight:700;"><?= htmlspecialchars($aName) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format($convert($a->balance ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    <tr class="subtotal-row">
                        <td colspan="2" style="text-align:end;"><?= $t['subtotal'] ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format($convert($totCurrAssets), 2) ?></td>
                    </tr>

                    <tr class="sec-header"><td colspan="3"><?= $t['non_curr_assets'] ?></td></tr>
                    <?php if(empty($nonCurrAssets)): ?>
                        <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:12px;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach($nonCurrAssets as $a): 
                        $aName = $isRtl ? ($a->name_ar ?? '') : ($a->name_en ?: ($a->name_ar ?? ''));
                    ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:#475569;"><?= htmlspecialchars((string)($a->code ?? '')) ?></td>
                            <td style="font-weight:700;"><?= htmlspecialchars($aName) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format($convert($a->balance ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    <tr class="subtotal-row">
                        <td colspan="2" style="text-align:end;"><?= $t['subtotal'] ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format($convert($totNonCurrAssets), 2) ?></td>
                    </tr>

                    <tr class="total-row">
                        <td colspan="2" style="text-align:end;"><?= $t['tot_assets'] ?></td>
                        <td style="text-align:center; font-family:monospace; font-size:1.1rem; color:#4ade80 !important;"><?= number_format($convert($grandTotalAssets), 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- جانب الخصوم وحقوق الملكية -->
        <div class="card-box">
            <div class="card-box-header" style="color:#dc2626;">
                <i class="ph-bold ph-trend-down"></i> <?= $t['sec_liab_equity'] ?>
            </div>
            <table class="table-rep">
                <thead>
                    <tr>
                        <th style="width:25%;"><?= $t['col_code'] ?></th>
                        <th style="width:45%;"><?= $t['col_name'] ?></th>
                        <th style="width:30%; text-align:center;"><?= $t['col_bal'] ?> (<?= $currency ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="sec-header"><td colspan="3"><?= $t['curr_liab'] ?></td></tr>
                    <?php if(empty($currLiabilities)): ?>
                        <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:12px;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach($currLiabilities as $l): 
                        $lName = $isRtl ? ($l->name_ar ?? '') : ($l->name_en ?: ($l->name_ar ?? ''));
                    ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:#475569;"><?= htmlspecialchars((string)($l->code ?? '')) ?></td>
                            <td style="font-weight:700;"><?= htmlspecialchars($lName) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format($convert($l->balance ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    <tr class="subtotal-row">
                        <td colspan="2" style="text-align:end;"><?= $t['subtotal'] ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format($convert($totCurrLiab), 2) ?></td>
                    </tr>

                    <tr class="sec-header"><td colspan="3"><?= $t['non_curr_lia'] ?></td></tr>
                    <?php if(empty($nonCurrLiabilities)): ?>
                        <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:12px;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach($nonCurrLiabilities as $l): 
                        $lName = $isRtl ? ($l->name_ar ?? '') : ($l->name_en ?: ($l->name_ar ?? ''));
                    ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:#475569;"><?= htmlspecialchars((string)($l->code ?? '')) ?></td>
                            <td style="font-weight:700;"><?= htmlspecialchars($lName) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format($convert($l->balance ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>

                    <tr class="sec-header"><td colspan="3"><?= $t['equity_sec'] ?></td></tr>
                    <?php if(!empty($equity)): foreach($equity as $e): 
                        $eName = $isRtl ? ($e->name_ar ?? '') : ($e->name_en ?: ($e->name_ar ?? ''));
                    ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:#475569;"><?= htmlspecialchars((string)($e->code ?? '')) ?></td>
                            <td style="font-weight:700;"><?= htmlspecialchars($eName) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#2563eb;"><?= number_format($convert($e->balance ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    <tr style="background:#fef3c7; font-weight:800; color:#b45309;">
                        <td colspan="2"><?= $t['cur_profit'] ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900;"><?= number_format($convert($currentNetProfit), 2) ?></td>
                    </tr>

                    <tr class="total-row">
                        <td colspan="2" style="text-align:end;"><?= $t['tot_liab_eq'] ?></td>
                        <td style="text-align:center; font-family:monospace; font-size:1.1rem; color:#f87171 !important;"><?= number_format($convert($grandTotalLiabAndEquity), 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function exportExcel(filename) {
    let html = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body dir="<?= $isRtl ? 'rtl' : 'ltr' ?>"><h2>${filename}</h2>${document.getElementById('balanceSheetContent').innerHTML}</body></html>`;
    let blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    let a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = filename + '.xls'; a.click();
}
</script>