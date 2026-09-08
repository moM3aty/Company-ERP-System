<?php
// Path: resources/views/accounting/reports/cash_flow.php

if (session_status() === PHP_SESSION_NONE) session_start();

$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

$cName = function_exists('current_company_name') ? current_company_name() : ($_SESSION['company_name'] ?? 'NOUR TRUST');
$cLogo = $_SESSION['company_logo'] ?? '/assets/img/default-logo.png';

$startDate = $startDate ?? date('Y-01-01');
$endDate = $endDate ?? date('Y-12-31');
$branchId = $branchId ?? 0;
$branches = $branches ?? [];

$operatingItems = $operatingItems ?? [];
$investingItems = $investingItems ?? [];
$financingItems = $financingItems ?? [];
$netOperating = $netOperating ?? 0.0;
$netInvesting = $netInvesting ?? 0.0;
$netFinancing = $netFinancing ?? 0.0;
$netCashChange = $netCashChange ?? 0.0;

$t = [
    'ar' => [
        'title' => 'قائمة التدفقات النقدية (Cash Flow)',
        'desc' => 'تصنيف وإظهار حركة السيولة النقدية عبر الأنشطة التشغيلية، الاستثمارية، والتمويلية.',
        'from' => 'من تاريخ', 'to' => 'إلى تاريخ', 'branch' => 'الفرع', 'all_branches' => '-- جميع الفروع --', 'btn_view' => 'تحديث التقرير',
        'print' => 'طباعة A4', 'excel' => 'تصدير Excel', 'print_date' => 'تاريخ الطباعة:',
        'op' => 'الأنشطة التشغيلية (Operating)', 'inv' => 'الأنشطة الاستثمارية (Investing)', 'fin' => 'أنشطة التمويل (Financing)',
        'net_change' => 'صافي التغير النقدي بالفترة:',
        'col_acc' => 'الحساب النقدي', 'col_date' => 'التاريخ', 'col_ref' => 'رقم القيد',
        'col_desc' => 'البيان / الشرح', 'col_in' => 'تدفق داخل (+)', 'col_out' => 'تدفق خارج (-)',
        'empty' => 'لا توجد حركات مسجلة.', 'net' => 'صافي التدفق:'
    ],
    'en' => [
        'title' => 'Cash Flow Statement',
        'desc' => 'Statement of cash flows categorized into Operating, Investing, and Financing activities.',
        'from' => 'From Date', 'to' => 'To Date', 'branch' => 'Branch', 'all_branches' => '-- All Branches --', 'btn_view' => 'Generate',
        'print' => 'Print A4', 'excel' => 'Export Excel', 'print_date' => 'Generated Date:',
        'op' => 'Operating Activities', 'inv' => 'Investing Activities', 'fin' => 'Financing Activities',
        'net_change' => 'Net Cash Change for Period:',
        'col_acc' => 'Cash Account', 'col_date' => 'Date', 'col_ref' => 'Entry No.',
        'col_desc' => 'Description / Details', 'col_in' => 'Inflow (+)', 'col_out' => 'Outflow (-)',
        'empty' => 'No records found.', 'net' => 'Net Flow:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { --c-cf: #0284c7; --c-cf-dark: #0369a1; --c-border: #cbd5e1; --c-text: #0f172a; --c-muted: #64748b; }
    .cf-wrapper { margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; color: var(--c-text); }
    .print-only-header { display: none; }
    .cf-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0; }
    .btn-action { padding: 11px 20px; border-radius: 10px; font-weight: 800; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.88rem; transition: all 0.2s; }
    .btn-print { background: var(--c-cf); color: #fff; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.2); }
    .btn-excel { background: #059669; color: #fff; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2); }
    .filter-card { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 20px; display: flex; gap: 16px; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; }
    .form-control { border: 1px solid var(--c-border); border-radius: 10px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; font-weight: 700; width: 100%; box-sizing: border-box; }
    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:900px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px; border-bottom: 4px solid var(--c-border); }
    .table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; margin-bottom: 24px; }
    .rep-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; table-layout: fixed; }
    .rep-table th { padding: 12px 16px; background: #f8fafc; color: var(--c-muted); font-weight: 800; border-bottom: 2px solid #e2e8f0; text-align: start; }
    .rep-table td { padding: 11px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; word-wrap: break-word; }
    .sec-hdr { background: #f1f5f9; font-weight: 900; color: #0f172a; font-size: 0.95rem; }
    .tot-row { background: #f8fafc; font-weight: 900; color: #0f172a; border-top: 2px solid #cbd5e1; }
    @media print {
        @page { size: A4 portrait; margin: 10mm 12mm; }
        body { background: #ffffff !important; color: #000000 !important; font-size: 8.5pt !important; }
        .cf-header, .filter-card, button { display: none !important; }
        .print-only-header { display: flex !important; align-items: center; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 18px; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; }
        .rep-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; }
        .rep-table td { border: 1px solid #cbd5e1 !important; padding: 6px 8px !important; }
    }
</style>

<div class="cf-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">

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
            <?= $t['from'] ?>: <span dir="ltr"><?= htmlspecialchars($startDate) ?></span> <?= $t['to'] ?>: <span dir="ltr"><?= htmlspecialchars($endDate) ?></span><br>
            <?= $t['print_date'] ?> <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <div class="cf-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <div style="width:52px; height:52px; background:#e0f2fe; color:var(--c-cf); border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.8rem;"><i class="ph-bold ph-arrows-left-right"></i></div>
            <div>
                <h2 style="margin:0; font-weight:900; font-size:1.5rem;"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.88rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="button" onclick="exportExcel('Cash_Flow')" class="btn-action btn-excel"><i class="ph-bold ph-file-xls"></i> <?= $t['excel'] ?></button>
            <button type="button" onclick="window.print()" class="btn-action btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <form action="/ERP/accounting/reports/cash-flow" method="GET" class="filter-card">
        <div style="flex:1; min-width: 150px;">
            <label style="font-weight:800; font-size:0.85rem; color:#64748b; margin-bottom:6px; display:block;"><?= $t['from'] ?></label>
            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>" required>
        </div>
        <div style="flex:1; min-width: 150px;">
            <label style="font-weight:800; font-size:0.85rem; color:#64748b; margin-bottom:6px; display:block;"><?= $t['to'] ?></label>
            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>" required>
        </div>
        <?php if(!empty($branches)): ?>
        <div style="flex:1; min-width: 150px;">
            <label style="font-weight:800; font-size:0.85rem; color:#64748b; margin-bottom:6px; display:block;"><?= $t['branch'] ?></label>
            <select name="branch_id" class="form-control">
                <option value="0"><?= $t['all_branches'] ?></option>
                <?php foreach($branches as $b): $bName = $isRtl ? ($b->name_ar ?? '') : ($b->name_en ?: ($b->name_ar ?? '')); ?>
                    <option value="<?= $b->id ?>" <?= $branchId == $b->id ? 'selected' : '' ?>><?= htmlspecialchars($bName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn-action btn-print" style="height:42px;"><i class="ph-bold ph-funnel"></i> <?= $t['btn_view'] ?></button>
    </form>

    <div class="kpi-row">
        <div class="kpi-card" style="border-bottom-color:#059669;">
            <h4 style="color:#059669; font-size:0.78rem; font-weight:800; margin:0 0 6px 0;"><?= $t['op'] ?></h4>
            <p style="color:#059669; margin:0; font-size:1.35rem; font-weight:900; font-family:monospace;"><?= number_format($convert($netOperating), 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom-color:#2563eb;">
            <h4 style="color:#2563eb; font-size:0.78rem; font-weight:800; margin:0 0 6px 0;"><?= $t['inv'] ?></h4>
            <p style="color:#2563eb; margin:0; font-size:1.35rem; font-weight:900; font-family:monospace;"><?= number_format($convert($netInvesting), 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom-color:#b45309;">
            <h4 style="color:#b45309; font-size:0.78rem; font-weight:800; margin:0 0 6px 0;"><?= $t['fin'] ?></h4>
            <p style="color:#b45309; margin:0; font-size:1.35rem; font-weight:900; font-family:monospace;"><?= number_format($convert($netFinancing), 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom-color:var(--c-cf); background:#f0f9ff;">
            <h4 style="color:var(--c-cf-dark); font-size:0.78rem; font-weight:800; margin:0 0 6px 0;"><?= $t['net_change'] ?></h4>
            <p style="color:var(--c-cf-dark); margin:0; font-size:1.35rem; font-weight:900; font-family:monospace;"><?= number_format($convert($netCashChange), 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></p>
        </div>
    </div>

    <div class="table-card" id="cfArea">
        <table class="rep-table">
            <thead>
                <tr>
                    <th style="width:18%;"><?= $t['col_acc'] ?></th>
                    <th style="width:12%;"><?= $t['col_date'] ?></th>
                    <th style="width:12%;"><?= $t['col_ref'] ?></th>
                    <th style="width:34%;"><?= $t['col_desc'] ?></th>
                    <th style="width:12%; text-align:center;"><?= $t['col_in'] ?></th>
                    <th style="width:12%; text-align:center;"><?= $t['col_out'] ?></th>
                </tr>
            </thead>
            <tbody>
                <tr class="sec-hdr"><td colspan="6"><i class="ph-bold ph-gear" style="color:#059669;"></i> 1. <?= $t['op'] ?></td></tr>
                <?php if(empty($operatingItems)): ?><tr><td colspan="6" style="text-align:center; padding:15px; color:#94a3b8;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach($operatingItems as $c): ?>
                    <tr>
                        <td style="font-weight:800; color:var(--c-cf-dark);"><?= htmlspecialchars((string)($c->acc_name ?? '')) ?></td>
                        <td style="font-family:monospace; font-weight:700;"><?= htmlspecialchars((string)($c->entry_date ?? '')) ?></td>
                        <td><a href="/ERP/accounting/journal-entries/<?= $c->journal_entry_id ?? 0 ?>" style="font-family:monospace; font-weight:900; color:var(--c-cf); text-decoration:none;">#<?= htmlspecialchars((string)($c->entry_number ?? '')) ?></a></td>
                        <td style="font-weight:700; color:#334155;"><?= htmlspecialchars((string)($c->line_desc ?? '')) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format($convert($c->debit ?? 0), 2) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format($convert($c->credit ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                <tr class="tot-row"><td colspan="4" style="text-align:end;"><?= $t['net'] ?></td><td colspan="2" style="text-align:center; font-family:monospace; color:#059669; font-size:1.05rem;"><?= number_format($convert($netOperating), 2) ?></td></tr>

                <tr class="sec-hdr"><td colspan="6"><i class="ph-bold ph-buildings" style="color:#2563eb;"></i> 2. <?= $t['inv'] ?></td></tr>
                <?php if(empty($investingItems)): ?><tr><td colspan="6" style="text-align:center; padding:15px; color:#94a3b8;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach($investingItems as $c): ?>
                    <tr>
                        <td style="font-weight:800; color:var(--c-cf-dark);"><?= htmlspecialchars((string)($c->acc_name ?? '')) ?></td>
                        <td style="font-family:monospace; font-weight:700;"><?= htmlspecialchars((string)($c->entry_date ?? '')) ?></td>
                        <td><a href="/ERP/accounting/journal-entries/<?= $c->journal_entry_id ?? 0 ?>" style="font-family:monospace; font-weight:900; color:var(--c-cf); text-decoration:none;">#<?= htmlspecialchars((string)($c->entry_number ?? '')) ?></a></td>
                        <td style="font-weight:700; color:#334155;"><?= htmlspecialchars((string)($c->line_desc ?? '')) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format($convert($c->debit ?? 0), 2) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format($convert($c->credit ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                <tr class="tot-row"><td colspan="4" style="text-align:end;"><?= $t['net'] ?></td><td colspan="2" style="text-align:center; font-family:monospace; color:#2563eb; font-size:1.05rem;"><?= number_format($convert($netInvesting), 2) ?></td></tr>

                <tr class="sec-hdr"><td colspan="6"><i class="ph-bold ph-bank" style="color:#b45309;"></i> 3. <?= $t['fin'] ?></td></tr>
                <?php if(empty($financingItems)): ?><tr><td colspan="6" style="text-align:center; padding:15px; color:#94a3b8;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach($financingItems as $c): ?>
                    <tr>
                        <td style="font-weight:800; color:var(--c-cf-dark);"><?= htmlspecialchars((string)($c->acc_name ?? '')) ?></td>
                        <td style="font-family:monospace; font-weight:700;"><?= htmlspecialchars((string)($c->entry_date ?? '')) ?></td>
                        <td><a href="/ERP/accounting/journal-entries/<?= $c->journal_entry_id ?? 0 ?>" style="font-family:monospace; font-weight:900; color:var(--c-cf); text-decoration:none;">#<?= htmlspecialchars((string)($c->entry_number ?? '')) ?></a></td>
                        <td style="font-weight:700; color:#334155;"><?= htmlspecialchars((string)($c->line_desc ?? '')) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format($convert($c->debit ?? 0), 2) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format($convert($c->credit ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                <tr class="tot-row"><td colspan="4" style="text-align:end;"><?= $t['net'] ?></td><td colspan="2" style="text-align:center; font-family:monospace; color:#b45309; font-size:1.05rem;"><?= number_format($convert($netFinancing), 2) ?></td></tr>

                <tr style="background:#0f172a; color:#fff; font-weight:900;">
                    <td colspan="4" style="text-align:end; font-size:1.05rem; padding:16px;"><?= $t['net_change'] ?></td>
                    <td colspan="2" style="text-align:center; font-family:monospace; font-size:1.3rem; color:#4ade80 !important;"><?= number_format($convert($netCashChange), 2) ?> <?= $currency ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
function exportExcel(filename) {
    let html = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body dir="<?= $isRtl ? 'rtl' : 'ltr' ?>"><h2>${filename}</h2>${document.getElementById('cfArea').innerHTML}</body></html>`;
    let blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    let a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = filename + '.xls'; a.click();
}
</script>