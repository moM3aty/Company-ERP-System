<?php
// Path: resources/views/accounting/reports/income_statement.php
if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$cName = function_exists('current_company_name') ? current_company_name() : ($GLOBALS['companyName'] ?? 'NOUR TRUST');
$cLogo = $GLOBALS['companyLogo'] ?? '/assets/img/default-logo.png';

$t = [
    'ar' => [
        'title' => 'قائمة الدخل (Profit & Loss)', 'desc' => 'ملخص الإيرادات والمصروفات وقياس صافي الأرباح بالفترة المالية.',
        'from' => 'من تاريخ', 'to' => 'إلى تاريخ', 'branch' => 'الفرع', 'all_branches' => '-- الكل --',
        'cc' => 'مركز تكلفة', 'all_cc' => '-- كافة مراكز التكلفة --', 'btn_view' => 'تحديث التقرير',
        'print' => 'طباعة التقرير', 'excel' => 'تصدير Excel', 'print_date' => 'تاريخ الاستخراج:',
        'tot_rev' => 'إجمالي الإيرادات', 'tot_exp' => 'إجمالي المصروفات', 'net_inc' => 'صافي الدخل', 'margin' => 'هامش الربح',
        'rev' => 'الإيرادات (Revenues)', 'exp' => 'المصروفات (Expenses)', 'col_code' => 'كود الحساب', 'col_acc' => 'اسم الحساب',
        'col_bal' => 'الرصيد', 'col_pct' => 'النسبة (% إيراد)', 'empty' => 'لا توجد بيانات مسجلة.'
    ],
    'en' => [
        'title' => 'Income Statement (P&L)', 'desc' => 'Summary of revenues, expenses, and net profit for the period.',
        'from' => 'From Date', 'to' => 'To Date', 'branch' => 'Branch', 'all_branches' => '-- All --',
        'cc' => 'Cost Center', 'all_cc' => '-- All Cost Centers --', 'btn_view' => 'Refresh',
        'print' => 'Print Report', 'excel' => 'Export Excel', 'print_date' => 'Generated on:',
        'tot_rev' => 'Total Revenues', 'tot_exp' => 'Total Expenses', 'net_inc' => 'Net Income', 'margin' => 'Profit Margin',
        'rev' => 'Revenues', 'exp' => 'Expenses', 'col_code' => 'Code', 'col_acc' => 'Account Name',
        'col_bal' => 'Balance', 'col_pct' => 'Percentage (%)', 'empty' => 'No records found.'
    ]
][$isRtl ? 'ar' : 'en'];
?>
<style>
    :root { --c-is: #1e40af; --c-is-dark: #0f172a; --c-is-light: #eff6ff; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .is-wrapper { padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; margin: 0 auto; }
    .print-only-header { display: none; }
    .filter-card { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 20px; display: flex; gap: 16px; align-items: flex-end; margin-bottom: 24px; flex-wrap:wrap;}
    .form-control { border: 1px solid var(--c-border); border-radius: 10px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; color: var(--c-text-dark); font-weight: 700; width: 100%; box-sizing: border-box;}
    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:900px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: center; }
    .kpi-card h4 { margin: 0 0 8px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; white-space: nowrap; }
    .kpi-card p { margin: 0; font-size: clamp(1rem, 1.6vw, 1.4rem); font-weight: 900; font-family: monospace; }
    .report-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden;}
    .rep-table { width: 100%; border-collapse: collapse; font-size: 0.92rem; table-layout: fixed; }
    .rep-table th { padding: 14px 20px; background: #f1f5f9; color: var(--c-text-muted); font-weight: 900; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; font-size: 0.78rem; text-align: start; }
    .rep-table td { padding: 13px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; word-wrap: break-word; }
    .rep-section-title td { background: #f1f5f9; font-weight: 900; color: #0f172a; font-size: 1.02rem; padding: 14px 20px; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; }
    .rep-subtotal td { font-weight: 900; color: #0f172a; background: #f8fafc; border-top: 2px solid #cbd5e1 !important; border-bottom: 2px solid #cbd5e1 !important; padding: 14px 20px; }
    .rep-grandtotal td { font-weight: 900; font-size: 1.15rem; background: #0f172a !important; color: #ffffff !important; padding: 18px 20px; border: none !important; }
    .progress-bar-bg { background: #e2e8f0; border-radius: 6px; height: 6px; width: 60px; display: inline-block; overflow: hidden; vertical-align: middle; margin-inline-start: 8px; }
    .progress-bar-fill { height: 100%; border-radius: 6px; }
    @media print {
        @page { size: A4 portrait; margin: 12mm 15mm; }
        body { background: #ffffff !important; color: #000000 !important; font-size: 9.5pt; font-family: 'Cairo', 'Inter', sans-serif !important; }
        .rep-header-actions, .filter-card, button { display: none !important; }
        .is-wrapper { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .print-only-header { display: flex !important; align-items: flex-end; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 20px; }
        .kpi-row { grid-template-columns: repeat(4, 1fr) !important; gap: 10px !important; margin-bottom: 20px !important; }
        .kpi-card { border: 1px solid #000000 !important; box-shadow: none !important; padding: 10px !important; }
        .report-card { border: 1px solid #000000 !important; box-shadow: none !important; page-break-inside: auto; }
        .rep-table { border-collapse: collapse !important; width: 100% !important; }
        .rep-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; }
        .rep-table td { border: 1px solid #cbd5e1 !important; padding: 7px 10px !important; }
        .rep-section-title td, .rep-subtotal td { border: 1px solid #000000 !important; background: #f1f5f9 !important; color: #000000 !important; }
        .rep-grandtotal td { background: #0f172a !important; color: #ffffff !important; border: 1px solid #000000 !important; }
        .progress-bar-bg { display: none !important; }
    }
</style>

<div class="is-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="print-only-header">
        <div style="display:flex; align-items:center; gap:15px;">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:50px;">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:2.5rem; color:#0f172a;"></i>
            <?php endif; ?>
            <div>
                <h2 style="margin:0; font-size:1.4rem; color:#000; font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                <span style="font-size:0.85rem; color:#475569; font-weight:bold;"><?= $t['title'] ?></span>
            </div>
        </div>
        <div style="text-align:end; font-size:0.85rem; font-weight:bold; color:#0f172a;">
            <?= $t['from'] ?>: <span dir="ltr"><?= htmlspecialchars($startDate ?? '') ?></span> <?= $t['to'] ?>: <span dir="ltr"><?= htmlspecialchars($endDate ?? '') ?></span><br>
            <span style="font-size:0.75rem; color:#64748b; font-weight:normal;"><?= $t['print_date'] ?> <?= date('Y-m-d H:i') ?></span>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; border-bottom:1px solid #e2e8f0; padding-bottom:16px;" class="rep-header-actions">
        <div style="display:flex; align-items:center; gap:16px;">
            <div style="width:52px; height:52px; background:#eff6ff; color:var(--c-is); border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.8rem;"><i class="ph-duotone ph-chart-line-up"></i></div>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="button" onclick="exportToExcel('Income_Statement')" style="background:#059669; color:#fff; border:none; padding:11px 20px; border-radius:10px; font-weight:800; cursor:pointer;"><?= $t['excel'] ?></button>
            <button type="button" onclick="window.print()" style="background:#1e40af; color:#fff; border:none; padding:11px 20px; border-radius:10px; font-weight:800; cursor:pointer;"><?= $t['print'] ?></button>
        </div>
    </div>

    <form action="/ERP/accounting/reports/income-statement" method="GET" class="filter-card">
        <div style="flex:1; min-width:130px;"><label style="font-weight:800; font-size:0.85rem; color:#64748b; margin-bottom:6px; display:block;"><?= $t['from'] ?></label><input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate ?? '') ?>" required></div>
        <div style="flex:1; min-width:130px;"><label style="font-weight:800; font-size:0.85rem; color:#64748b; margin-bottom:6px; display:block;"><?= $t['to'] ?></label><input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate ?? '') ?>" required></div>
        <?php if(!empty($branches)): ?>
        <div style="flex:1; min-width:150px;">
            <label style="font-weight:800; font-size:0.85rem; color:#64748b; margin-bottom:6px; display:block;"><?= $t['branch'] ?></label>
            <select name="branch_id" class="form-control">
                <option value="0"><?= $t['all_branches'] ?></option>
                <?php foreach($branches as $b): $bName = $isRtl ? ($b->name_ar ?? '') : ($b->name_en ?: ($b->name_ar ?? '')); ?>
                    <option value="<?= $b->id ?>" <?= ($branchId ?? 0) == $b->id ? 'selected' : '' ?>><?= htmlspecialchars($bName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <?php if(!empty($costCenters)): ?>
        <div style="flex: 1.5; min-width:200px;">
            <label style="font-weight:800; font-size:0.85rem; color:#64748b; margin-bottom:6px; display:block;"><?= $t['cc'] ?></label>
            <select name="cost_center_id" class="form-control">
                <option value=""><?= $t['all_cc'] ?></option>
                <?php foreach($costCenters as $cc): ?>
                    <option value="<?= $cc->id ?>" <?= ($costCenterId ?? 0) == $cc->id ? 'selected' : '' ?>><?= htmlspecialchars((string)($cc->code ?? '')) ?> - <?= htmlspecialchars((string)($cc->name_ar ?? '')) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <button type="submit" style="background:#0f172a; color:#fff; border:none; padding:11px 20px; border-radius:10px; font-weight:800; cursor:pointer; height:42px;"><?= $t['btn_view'] ?></button>
    </form>

    <div class="kpi-row">
        <div class="kpi-card" style="border-bottom:3px solid #16a34a;"><h4 style="color:#16a34a;"><?= $t['tot_rev'] ?></h4><p style="color:#16a34a;"><?= number_format($convert($totalRevenue ?? 0), 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></p></div>
        <div class="kpi-card" style="border-bottom:3px solid #dc2626;"><h4 style="color:#dc2626;"><?= $t['tot_exp'] ?></h4><p style="color:#dc2626;"><?= number_format($convert($totalExpense ?? 0), 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></p></div>
        <div class="kpi-card" style="border-bottom:3px solid <?= ($netIncome ?? 0) >= 0 ? '#2563eb' : '#dc2626' ?>;"><h4 style="color:var(--c-text-muted);"><?= $t['net_inc'] ?></h4><p style="color:<?= ($netIncome ?? 0) >= 0 ? '#2563eb' : '#dc2626' ?>;"><?= number_format($convert($netIncome ?? 0), 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></p></div>
        <div class="kpi-card" style="border-bottom:3px solid <?= ($netMargin ?? 0) >= 0 ? '#0284c7' : '#dc2626' ?>;"><h4 style="color:var(--c-text-muted);"><?= $t['margin'] ?></h4><p style="color:<?= ($netMargin ?? 0) >= 0 ? '#0284c7' : '#dc2626' ?>;"><?= $netMargin ?? 0 ?>%</p></div>
    </div>

    <div class="report-card">
        <table class="rep-table" id="reportTable">
            <thead>
                <tr>
                    <th style="width: 15%;"><?= $t['col_code'] ?></th>
                    <th style="width: 45%;"><?= $t['col_acc'] ?></th>
                    <th style="width: 22%; text-align: center;"><?= $t['col_bal'] ?> (<?= $currency ?>)</th>
                    <th style="width: 18%; text-align: center;"><?= $t['col_pct'] ?></th>
                </tr>
            </thead>
            <tbody>
                <tr class="rep-section-title"><td colspan="4"><i class="ph-bold ph-trend-up" style="color:#16a34a;"></i> 1. <?= $t['rev'] ?></td></tr>
                <?php if(empty($revenues)): ?>
                    <tr class="data-row"><td colspan="4" style="text-align:center; padding:20px; color:#94a3b8;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach($revenues as $rev): 
                    $rName = $isRtl ? ($rev->name_ar ?? '') : ($rev->name_en ?: ($rev->name_ar ?? ''));
                    $rawPct = ($totalRevenue ?? 0) > 0 ? round(((float)($rev->balance ?? 0) / ($totalRevenue ?? 1)) * 100, 1) : 0;
                    $visPct = min(100, max(0, $rawPct));
                ?>
                    <tr class="data-row">
                        <td style="font-family:monospace; font-weight:800; color:#475569;"><?= htmlspecialchars((string)($rev->code ?? '')) ?></td>
                        <td style="font-weight:700; color:var(--c-text-dark);"><?= htmlspecialchars($rName) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#16a34a;"><?= number_format($convert((float)($rev->balance ?? 0)), 2) ?></td>
                        <td style="text-align:center; white-space:nowrap;">
                            <span style="font-family:monospace; font-size:0.8rem; font-weight:bold; color:#64748b;"><?= $rawPct ?>%</span>
                            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:<?= $visPct ?>%; background:#16a34a;"></div></div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                <tr class="rep-subtotal">
                    <td colspan="2" style="text-align:end;"><?= $t['tot_rev'] ?>:</td>
                    <td style="text-align:center; color:#16a34a; font-family:monospace; font-size:1.05rem;"><?= number_format($convert($totalRevenue ?? 0), 2) ?></td>
                    <td style="text-align:center; font-family:monospace; color:#16a34a;">100%</td>
                </tr>

                <tr class="rep-section-title"><td colspan="4"><i class="ph-bold ph-trend-down" style="color:#dc2626;"></i> 2. <?= $t['exp'] ?></td></tr>
                <?php if(empty($expenses)): ?>
                    <tr class="data-row"><td colspan="4" style="text-align:center; padding:20px; color:#94a3b8;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach($expenses as $exp): 
                    $eName = $isRtl ? ($exp->name_ar ?? '') : ($exp->name_en ?: ($exp->name_ar ?? ''));
                    $rawPct = ($totalRevenue ?? 0) > 0 ? round(((float)($exp->balance ?? 0) / ($totalRevenue ?? 1)) * 100, 1) : 0;
                    $visPct = min(100, max(0, $rawPct));
                ?>
                    <tr class="data-row">
                        <td style="font-family:monospace; font-weight:800; color:#475569;"><?= htmlspecialchars((string)($exp->code ?? '')) ?></td>
                        <td style="font-weight:700; color:var(--c-text-dark);"><?= htmlspecialchars($eName) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format($convert((float)($exp->balance ?? 0)), 2) ?></td>
                        <td style="text-align:center; white-space:nowrap;">
                            <span style="font-family:monospace; font-size:0.8rem; font-weight:bold; color:#64748b;"><?= $rawPct ?>%</span>
                            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:<?= $visPct ?>%; background:#dc2626;"></div></div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                <tr class="rep-subtotal">
                    <td colspan="2" style="text-align:end;"><?= $t['tot_exp'] ?>:</td>
                    <td style="text-align:center; color:#dc2626; font-family:monospace; font-size:1.05rem;"><?= number_format($convert($totalExpense ?? 0), 2) ?></td>
                    <td style="text-align:center; font-family:monospace; color:#dc2626;"><?= ($totalRevenue ?? 0) > 0 ? round((($totalExpense??0)/($totalRevenue??1))*100, 1) : 0 ?>%</td>
                </tr>

                <tr class="rep-grandtotal">
                    <td colspan="2" style="text-align:end;"><?= $t['net_inc'] ?>:</td>
                    <td style="text-align:center; font-family:monospace; font-size:1.3rem; color: #ffffff !important;"><?= number_format($convert($netIncome ?? 0), 2) ?></td>
                    <td style="text-align:center; font-family:monospace; color: #ffffff !important;"><?= $netMargin ?? 0 ?>%</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<script>
function exportToExcel(filename) {
    let cloneTable = document.getElementById("reportTable").cloneNode(true);
    cloneTable.querySelectorAll('.progress-bar-bg').forEach(el => el.remove());
    let html = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body dir="rtl">${cloneTable.outerHTML}</body></html>`;
    let blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    let a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = filename + '.xls'; a.click();
}
</script>