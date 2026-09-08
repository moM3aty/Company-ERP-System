<?php
// Path: resources/views/accounting/reports/ledger.php
if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };
$currency = function_exists('current_currency') ? current_currency() : 'EGP';

$cName = current_company_name();
$cLogo = $_SESSION['company_logo'] ?? '/assets/img/default-logo.png';

$t = [
    'ar' => [
        'title' => 'دفتر الأستاذ العام (General Ledger)', 'desc' => 'عرض تفصيلي بحركات وقيد رصيد الحساب المالي المختار.',
        'acc' => 'اختر الحساب المحاسبي', 'branch' => 'الفرع', 'all_branches' => '-- جميع الفروع --',
        'from' => 'من تاريخ', 'to' => 'إلى تاريخ', 'search' => 'بحث ببيان القيد', 'btn_view' => 'عرض التقرير',
        'print' => 'طباعة A4', 'excel' => 'تصدير Excel', 'op_bal' => 'رصيد بداية الفترة المنقول:',
        'col_date' => 'التاريخ', 'col_ref' => 'رقم القيد', 'col_desc' => 'البيان / شرح الحركة',
        'col_dr' => 'مدين (+)', 'col_cr' => 'دائن (-)', 'col_bal' => 'الرصيد التراكمي', 'empty' => 'يرجى اختيار حساب مالي لعرض دفتر الأستاذ الخاص به.'
    ],
    'en' => [
        'title' => 'General Ledger', 'desc' => 'Detailed view of journal movements and running balances for selected accounts.',
        'acc' => 'Select Account', 'branch' => 'Branch', 'all_branches' => '-- All Branches --',
        'from' => 'From Date', 'to' => 'To Date', 'search' => 'Search Description', 'btn_view' => 'Generate',
        'print' => 'Print A4', 'excel' => 'Export Excel', 'op_bal' => 'Opening Balance:',
        'col_date' => 'Date', 'col_ref' => 'Ref No.', 'col_desc' => 'Description / Details',
        'col_dr' => 'Debit (+)', 'col_cr' => 'Credit (-)', 'col_bal' => 'Running Balance', 'empty' => 'Please select an account to view its ledger.'
    ]
][$isRtl ? 'ar' : 'en'];
?>
<style>
    :root { --c-rep: #7c3aed; --c-rep-dark: #5b21b6; --c-border: #cbd5e1; }
    .rep-wrapper { margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .print-only-header { display: none; }
    .filter-card { background: #fff; border: 1px solid var(--c-border); border-radius: 16px; padding: 20px; display: flex; gap: 16px; align-items: flex-end; margin-bottom: 24px; flex-wrap:wrap;}
    .form-control { border: 1px solid var(--c-border); border-radius: 10px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; font-weight: 700; width: 100%; box-sizing: border-box; }
    .table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .rep-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; table-layout: fixed; }
    .rep-table th { padding: 14px 16px; background: #f8fafc; color: #64748b; font-weight: 800; border-bottom: 2px solid #e2e8f0; text-align: start; }
    .rep-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; word-wrap: break-word; }
    @media print {
        @page { size: A4 portrait; margin: 10mm 12mm; }
        body { background: #ffffff !important; color: #000000 !important; font-size: 8.5pt !important; }
        .rep-header-actions, .filter-card, button { display: none !important; }
        .rep-wrapper { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .print-only-header { display: flex !important; align-items: center; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 18px; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; }
        .rep-table { border-collapse: collapse !important; width: 100% !important; }
        .rep-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; }
        .rep-table td { border: 1px solid #cbd5e1 !important; padding: 6px 8px !important; }
    }
</style>

<div class="rep-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
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
            <?= $t['acc'] ?>: <?= !empty($selectedAccount) ? htmlspecialchars($selectedAccount->code . ' - ' . ($isRtl ? ($selectedAccount->name_ar ?? '') : ($selectedAccount->name_en ?: ($selectedAccount->name_ar ?? '')))) : 'الكل' ?><br>
            الفترة: <span dir="ltr"><?= htmlspecialchars($startDate ?? '') ?></span> إلى <span dir="ltr"><?= htmlspecialchars($endDate ?? '') ?></span>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; border-bottom:1px solid #e2e8f0; padding-bottom:16px;" class="rep-header-actions">
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:48px; height:48px; background:#f5f3ff; color:var(--c-rep); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.6rem;"><i class="ph-bold ph-book-open"></i></div>
            <div>
                <h2 style="margin:0; font-weight:900;"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:#64748b; font-size:0.88rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="exportExcel('General_Ledger')" style="background:#059669; color:#fff; border:none; padding:11px 20px; border-radius:10px; font-weight:800; cursor:pointer;"><?= $t['excel'] ?></button>
            <button onclick="window.print()" style="background:var(--c-rep); color:#fff; border:none; padding:11px 20px; border-radius:10px; font-weight:800; cursor:pointer;"><?= $t['print'] ?></button>
        </div>
    </div>

    <form action="/ERP/accounting/reports/ledger" method="GET" class="filter-card">
        <div style="flex:2; min-width:200px;">
            <label style="font-weight:800; font-size:0.85rem; color:#64748b; margin-bottom:6px; display:block;"><?= $t['acc'] ?> <span style="color:red">*</span></label>
            <select name="account_id" class="form-control" required>
                <option value="">-- <?= $t['acc'] ?> --</option>
                <?php foreach($accounts ?? [] as $acc): $aName = $isRtl ? ($acc->name_ar ?? '') : ($acc->name_en ?: ($acc->name_ar ?? '')); ?>
                    <option value="<?= $acc->id ?>" <?= ($accountId ?? 0) == $acc->id ? 'selected' : '' ?>><?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($aName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
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
        <div style="flex:1; min-width:130px;"><label style="font-weight:800; font-size:0.85rem; color:#64748b; margin-bottom:6px; display:block;"><?= $t['from'] ?></label><input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate ?? '') ?>"></div>
        <div style="flex:1; min-width:130px;"><label style="font-weight:800; font-size:0.85rem; color:#64748b; margin-bottom:6px; display:block;"><?= $t['to'] ?></label><input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate ?? '') ?>"></div>
        <button type="submit" style="background:#0f172a; color:#fff; border:none; padding:11px 20px; border-radius:10px; font-weight:800; cursor:pointer; height:42px;"><?= $t['btn_view'] ?></button>
    </form>

    <div class="table-card" id="ledgerTableArea">
        <table class="rep-table">
            <thead>
                <tr>
                    <th style="width:12%;"><?= $t['col_date'] ?></th>
                    <th style="width:14%;"><?= $t['col_ref'] ?></th>
                    <th style="width:38%;"><?= $t['col_desc'] ?></th>
                    <th style="width:12%; text-align:center;"><?= $t['col_dr'] ?></th>
                    <th style="width:12%; text-align:center;"><?= $t['col_cr'] ?></th>
                    <th style="width:12%; text-align:center;"><?= $t['col_bal'] ?> (<?= $currency ?>)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($accountId)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:40px; color:#94a3b8; font-weight:700;"><?= $t['empty'] ?></td></tr>
                <?php else: ?>
                    <tr style="background:#fef3c7; font-weight:800;"><td colspan="5"><?= $t['op_bal'] ?></td><td style="text-align:center; font-family:monospace; font-size:1rem; color:#b45309;"><?= number_format($convert($openingBalance ?? 0), 2) ?></td></tr>
                    <?php 
                    $runBal = $openingBalance ?? 0;
                    foreach($transactions ?? [] as $it): 
                        $dr = (float)($it->debit ?? 0);
                        $cr = (float)($it->credit ?? 0);
                        $runBal += ($dr - $cr);
                    ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:700; color:#0f172a;"><?= htmlspecialchars((string)($it->date ?? '')) ?></td>
                            <td><a href="/ERP/accounting/journal-entries/<?= $it->journal_entry_id ?? 0 ?>" style="font-family:monospace; font-weight:900; color:var(--c-rep); text-decoration:none;">#<?= htmlspecialchars((string)($it->entry_number ?? '')) ?></a></td>
                            <td style="font-weight:700; color:#334155;"><?= htmlspecialchars((string)($it->line_desc ?? '')) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format($convert($dr), 2) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format($convert($cr), 2) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#0f172a; background:#f8fafc;"><?= number_format($convert($runBal), 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background:#f8fafc; font-weight:900;"><td colspan="3" style="text-align:end;">إجمالي حركات الفترة:</td><td style="text-align:center; font-family:monospace; color:#059669; font-size:1rem;"><?= number_format($convert($totalDebit ?? 0), 2) ?></td><td style="text-align:center; font-family:monospace; color:#dc2626; font-size:1rem;"><?= number_format($convert($totalCredit ?? 0), 2) ?></td><td style="text-align:center; font-family:monospace; font-size:1.1rem; color:var(--c-rep-dark);"><?= number_format($convert($runBal), 2) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function exportExcel(filename) {
    let blob = new Blob([document.getElementById('ledgerTableArea').innerHTML], { type: 'application/vnd.ms-excel' });
    let a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = filename + '.xls'; a.click();
}
</script>