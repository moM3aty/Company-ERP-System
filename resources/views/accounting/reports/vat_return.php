<?php
// Path: resources/views/accounting/reports/vat_return.php

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

$totalOutputVat = $totalOutputVat ?? 0.0;
$totalInputVat = $totalInputVat ?? 0.0;
$totalWht = $totalWht ?? 0.0;
$netVatPayable = $netVatPayable ?? 0.0;
$vatItems = $vatItems ?? [];

$t = [
    'ar' => [
        'title' => 'الإقرار الضريبي (VAT Return)',
        'desc' => 'تسوية ضريبة المخرجات والمدخلات ومبالغ الخصم والإضافة لتحديد الصافي المستحق.',
        'from' => 'من تاريخ', 'to' => 'إلى تاريخ', 'branch' => 'الفرع', 'all_branches' => '-- جميع الفروع --', 'btn_view' => 'تحديث التقرير',
        'print' => 'طباعة الإقرار A4', 'excel' => 'تصدير Excel', 'print_date' => 'تاريخ الاستخراج:',
        'kpi_out' => 'ضريبة مبيعات / مخرجات (Output VAT)',
        'kpi_in' => 'ضريبة مشتريات / مدخلات (Input VAT)',
        'kpi_wht' => 'خصم وإضافة (WHT Balance)',
        'kpi_net' => 'صافي الضريبة الواجبة السداد',
        'col_ref' => 'رقم القيد', 'col_date' => 'التاريخ', 'col_tax_code' => 'كود الضريبة',
        'col_desc' => 'البيان', 'col_in' => 'مدخلات / مستردة (+)', 'col_out' => 'مخرجات / مستحقة (-)',
        'empty' => 'لا توجد حركات ضريبية مسجلة خلال هذه الفترة.'
    ],
    'en' => [
        'title' => 'VAT Return Report',
        'desc' => 'Settlement of Input and Output VAT along with Withholding Tax.',
        'from' => 'From Date', 'to' => 'To Date', 'branch' => 'Branch', 'all_branches' => '-- All Branches --', 'btn_view' => 'Update Report',
        'print' => 'Print A4 Form', 'excel' => 'Export Excel', 'print_date' => 'Generated on:',
        'kpi_out' => 'Output VAT (Sales)',
        'kpi_in' => 'Input VAT (Purchases)',
        'kpi_wht' => 'Withholding Tax (WHT)',
        'kpi_net' => 'Net VAT Payable',
        'col_ref' => 'Entry No.', 'col_date' => 'Date', 'col_tax_code' => 'Tax Code',
        'col_desc' => 'Description', 'col_in' => 'Input VAT (+)', 'col_out' => 'Output VAT (-)',
        'empty' => 'No tax transactions recorded during this period.'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { --c-vat: #0284c7; --c-vat-dark: #0369a1; --c-border: #cbd5e1; --c-text: #0f172a; --c-muted: #64748b; }
    .vat-wrapper { margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; color: var(--c-text); }
    .print-only-header { display: none; }
    .vat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0; }
    .btn-action { padding: 11px 20px; border-radius: 10px; font-weight: 800; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.88rem; transition: all 0.2s; }
    .btn-print { background: var(--c-vat); color: #fff; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.2); }
    .btn-excel { background: #059669; color: #fff; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2); }
    .filter-card { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 20px; display: flex; gap: 16px; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; }
    .form-control { border: 1px solid var(--c-border); border-radius: 10px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; font-weight: 700; width: 100%; box-sizing: border-box; }
    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:900px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px; border-top: 4px solid var(--c-border); }
    .table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; margin-bottom: 24px; }
    .rep-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; table-layout: fixed; }
    .rep-table th { padding: 12px 16px; background: #f8fafc; color: var(--c-muted); font-weight: 800; border-bottom: 2px solid #e2e8f0; text-align: start; }
    .rep-table td { padding: 11px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; word-wrap: break-word; }
    @media print {
        @page { size: A4 portrait; margin: 10mm 12mm; }
        body { background: #ffffff !important; color: #000000 !important; font-size: 8.5pt !important; }
        .vat-header, .filter-card, button { display: none !important; }
        .print-only-header { display: flex !important; align-items: center; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 18px; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; }
        .rep-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; }
        .rep-table td { border: 1px solid #cbd5e1 !important; padding: 6px 8px !important; }
    }
</style>

<div class="vat-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">

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

    <div class="vat-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <div style="width:52px; height:52px; background:#e0f2fe; color:var(--c-vat); border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.8rem;"><i class="ph-bold ph-receipt"></i></div>
            <div>
                <h2 style="margin:0; font-weight:900; font-size:1.5rem;"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.88rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="button" onclick="exportExcel('VAT_Return')" class="btn-action btn-excel"><i class="ph-bold ph-file-xls"></i> <?= $t['excel'] ?></button>
            <button type="button" onclick="window.print()" class="btn-action btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <form action="/ERP/accounting/reports/vat-return" method="GET" class="filter-card">
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
        <div class="kpi-card" style="border-top-color:#dc2626;">
            <h4 style="color:#dc2626; font-size:0.78rem; font-weight:800; margin:0 0 6px 0;"><?= $t['kpi_out'] ?></h4>
            <p style="color:#dc2626; margin:0; font-size:1.35rem; font-weight:900; font-family:monospace;"><?= number_format($convert($totalOutputVat), 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></p>
        </div>
        <div class="kpi-card" style="border-top-color:#059669;">
            <h4 style="color:#059669; font-size:0.78rem; font-weight:800; margin:0 0 6px 0;"><?= $t['kpi_in'] ?></h4>
            <p style="color:#059669; margin:0; font-size:1.35rem; font-weight:900; font-family:monospace;"><?= number_format($convert($totalInputVat), 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></p>
        </div>
        <div class="kpi-card" style="border-top-color:#2563eb;">
            <h4 style="color:#2563eb; font-size:0.78rem; font-weight:800; margin:0 0 6px 0;"><?= $t['kpi_wht'] ?></h4>
            <p style="color:#2563eb; margin:0; font-size:1.35rem; font-weight:900; font-family:monospace;"><?= number_format($convert($totalWht), 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></p>
        </div>
        <div class="kpi-card" style="border-top-color:#4f46e5; background:#e0e7ff;">
            <h4 style="color:#3730a3; font-size:0.78rem; font-weight:800; margin:0 0 6px 0;"><?= $t['kpi_net'] ?></h4>
            <p style="color:#3730a3; margin:0; font-size:1.35rem; font-weight:900; font-family:monospace;"><?= number_format($convert($netVatPayable), 2) ?> <span style="font-size:0.8rem;"><?= $currency ?></span></p>
        </div>
    </div>

    <div class="table-card" id="vatTableArea">
        <table class="rep-table">
            <thead>
                <tr>
                    <th style="width:12%;"><?= $t['col_ref'] ?></th>
                    <th style="width:12%;"><?= $t['col_date'] ?></th>
                    <th style="width:15%;"><?= $t['col_tax_code'] ?></th>
                    <th style="width:33%;"><?= $t['col_desc'] ?></th>
                    <th style="width:14%; text-align:center;"><?= $t['col_in'] ?></th>
                    <th style="width:14%; text-align:center;"><?= $t['col_out'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($vatItems)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:35px; color:#94a3b8;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach($vatItems as $v): ?>
                    <tr>
                        <td><a href="/ERP/accounting/journal-entries/<?= $v->journal_entry_id ?? 0 ?>" style="font-family:monospace; font-weight:900; color:var(--c-vat-dark); text-decoration:none;">#<?= htmlspecialchars((string)($v->entry_number ?? '')) ?></a></td>
                        <td style="font-family:monospace; font-weight:700; color:#0f172a;"><?= htmlspecialchars((string)($v->entry_date ?? '')) ?></td>
                        <td style="font-weight:800; color:var(--c-vat-dark);"><?= htmlspecialchars((string)($v->tax_code ?? '')) ?> (<?= (float)($v->tax_rate ?? 0) ?>%)</td>
                        <td style="font-weight:700; color:#334155;"><?= htmlspecialchars((string)($v->line_desc ?? '')) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format($convert($v->debit ?? 0), 2) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format($convert($v->credit ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function exportExcel(filename) {
    let html = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body dir="<?= $isRtl ? 'rtl' : 'ltr' ?>"><h2>${filename}</h2>${document.getElementById('vatTableArea').innerHTML}</body></html>`;
    let blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    let a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = filename + '.xls'; a.click();
}
</script>