<?php
// Path: resources/views/accounting/reports/trial_balance.php
if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };
$currency = function_exists('current_currency') ? current_currency() : 'EGP';

$cName = current_company_name();
$cLogo = $_SESSION['company_logo'] ?? '/assets/img/default-logo.png';

$t = [
    'ar' => [
        'title' => 'ميزان المراجعة (Trial Balance)', 'desc' => 'التحقق التلقائي من انضباط وتوازن الدفاتر المحاسبية.',
        'branch' => 'الفرع', 'all_branches' => '-- جميع الفروع --', 'from' => 'من تاريخ', 'to' => 'إلى تاريخ', 'btn_view' => 'تحديث',
        'print' => 'طباعة A4', 'excel' => 'تصدير Excel',
        'col_code' => 'الكود', 'col_acc' => 'اسم الحساب المحاسبي', 'op_bal' => 'أرصدة بداية الفترة',
        'mov_bal' => 'حركات الفترة (المجاميع)', 'end_bal' => 'أرصدة نهاية الفترة', 'dr' => 'مدين', 'cr' => 'دائن', 'tot' => 'الإجمالي العام:'
    ],
    'en' => [
        'title' => 'Trial Balance', 'desc' => 'Check arithmetic accuracy and GL account balances.',
        'branch' => 'Branch', 'all_branches' => '-- All Branches --', 'from' => 'From Date', 'to' => 'To Date', 'btn_view' => 'Refresh',
        'print' => 'Print A4', 'excel' => 'Export Excel',
        'col_code' => 'Code', 'col_acc' => 'Account Name', 'op_bal' => 'Opening Balances',
        'mov_bal' => 'Period Movements', 'end_bal' => 'Ending Balances', 'dr' => 'Debit', 'cr' => 'Credit', 'tot' => 'Grand Total:'
    ]
][$isRtl ? 'ar' : 'en'];
?>
<style>
    :root { --c-rep: #7c3aed; --c-rep-dark: #5b21b6; --c-border: #cbd5e1; }
    .rep-wrapper { margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .print-only-header { display: none; }
    .filter-card { background: #fff; border: 1px solid var(--c-border); border-radius: 16px; padding: 18px; display: flex; gap: 16px; align-items: flex-end; margin-bottom: 24px; flex-wrap:wrap;}
    .form-control { border: 1px solid var(--c-border); border-radius: 10px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; font-weight: 700; width: 100%; box-sizing: border-box; }
    .table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden;}
    .rep-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; table-layout: fixed; }
    .rep-table th { padding: 11px 10px; background: #f8fafc; color: #64748b; font-weight: 800; border: 1px solid #cbd5e1; text-align: center; }
    .rep-table td { padding: 10px 12px; border: 1px solid #e2e8f0; vertical-align: middle; word-wrap: break-word; }
    .tot-row td { background: #0f172a !important; color: #ffffff !important; font-weight: 900; font-size: 0.92rem; padding: 12px; }
    @media print {
        @page { size: A4 portrait; margin: 10mm 12mm; }
        body { background: #ffffff !important; color: #000000 !important; font-size: 8pt !important; }
        .rep-header-actions, .filter-card, button { display: none !important; }
        .rep-wrapper { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .print-only-header { display: flex !important; align-items: center; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 18px; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; }
        .rep-table { border-collapse: collapse !important; width: 100% !important; }
        .rep-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; }
        .rep-table td { border: 1px solid #cbd5e1 !important; padding: 5px 6px !important; }
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
            الفترة من: <span dir="ltr"><?= htmlspecialchars($startDate ?? '') ?></span> إلى <span dir="ltr"><?= htmlspecialchars($endDate ?? '') ?></span><br>
            تاريخ الطباعة: <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; border-bottom:1px solid #e2e8f0; padding-bottom:16px;" class="rep-header-actions">
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:48px; height:48px; background:#f5f3ff; color:var(--c-rep); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.6rem;"><i class="ph-bold ph-list-numbers"></i></div>
            <div>
                <h2 style="margin:0; font-weight:900;"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:#64748b; font-size:0.88rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="exportExcel('Trial_Balance')" style="background:#059669; color:#fff; border:none; padding:11px 20px; border-radius:10px; font-weight:800; cursor:pointer;"><?= $t['excel'] ?></button>
            <button onclick="window.print()" style="background:var(--c-rep); color:#fff; border:none; padding:11px 20px; border-radius:10px; font-weight:800; cursor:pointer;"><?= $t['print'] ?></button>
        </div>
    </div>

    <form action="/ERP/accounting/reports/trial-balance" method="GET" class="filter-card">
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

    <div class="table-card" id="tblArea">
        <table class="rep-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width:10%;"><?= $t['col_code'] ?></th>
                    <th rowspan="2" style="width:26%;"><?= $t['col_acc'] ?></th>
                    <th colspan="2"><?= $t['op_bal'] ?> (<?= $currency ?>)</th>
                    <th colspan="2"><?= $t['mov_bal'] ?></th>
                    <th colspan="2"><?= $t['end_bal'] ?></th>
                </tr>
                <tr>
                    <th style="width:11%;"><?= $t['dr'] ?></th><th style="width:11%;"><?= $t['cr'] ?></th>
                    <th style="width:11%;"><?= $t['dr'] ?></th><th style="width:11%;"><?= $t['cr'] ?></th>
                    <th style="width:11%;"><?= $t['dr'] ?></th><th style="width:11%;"><?= $t['cr'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($tbData)): ?>
                    <tr><td colspan="8" style="text-align:center; padding:35px; color:#94a3b8;">لا توجد بيانات بالميزان.</td></tr>
                <?php else: 
                    $tOpD=0; $tOpC=0; $tPrD=0; $tPrC=0; $tEnD=0; $tEnC=0;
                    foreach($tbData as $r): 
                    $aName = $isRtl ? ($r->name_ar ?? '') : ($r->name_en ?: ($r->name_ar ?? ''));
                    $opDr = (float)($r->op_dr ?? 0); $opCr = (float)($r->op_cr ?? 0);
                    $dr = (float)($r->dr ?? 0); $cr = (float)($r->cr ?? 0);
                    $clDr = (float)($r->close_dr ?? 0); $clCr = (float)($r->close_cr ?? 0);
                    
                    $tOpD+=$opDr; $tOpC+=$opCr; $tPrD+=$dr; $tPrC+=$cr; $tEnD+=$clDr; $tEnC+=$clCr;
                ?>
                    <tr>
                        <td style="font-family:monospace; font-weight:900; color:var(--c-rep-dark); text-align:center;"><?= htmlspecialchars((string)($r->code ?? '')) ?></td>
                        <td style="font-weight:700; color:#0f172a;"><?= htmlspecialchars($aName) ?></td>
                        <td style="text-align:center; font-family:monospace; color:#059669;"><?= $opDr > 0 ? number_format($convert($opDr), 2) : '-' ?></td>
                        <td style="text-align:center; font-family:monospace; color:#dc2626;"><?= $opCr > 0 ? number_format($convert($opCr), 2) : '-' ?></td>
                        <td style="text-align:center; font-family:monospace; color:#059669;"><?= $dr > 0 ? number_format($convert($dr), 2) : '-' ?></td>
                        <td style="text-align:center; font-family:monospace; color:#dc2626;"><?= $cr > 0 ? number_format($convert($cr), 2) : '-' ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669; background:#f0fdf4;"><?= $clDr > 0 ? number_format($convert($clDr), 2) : '-' ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626; background:#fef2f2;"><?= $clCr > 0 ? number_format($convert($clCr), 2) : '-' ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                <tr class="tot-row">
                    <td colspan="2" style="text-align:end;"><?= $t['tot'] ?></td>
                    <td style="text-align:center; font-family:monospace;"><?= number_format($convert($tOpD??0), 2) ?></td>
                    <td style="text-align:center; font-family:monospace;"><?= number_format($convert($tOpC??0), 2) ?></td>
                    <td style="text-align:center; font-family:monospace;"><?= number_format($convert($tPrD??0), 2) ?></td>
                    <td style="text-align:center; font-family:monospace;"><?= number_format($convert($tPrC??0), 2) ?></td>
                    <td style="text-align:center; font-family:monospace; color:#4ade80;"><?= number_format($convert($tEnD??0), 2) ?></td>
                    <td style="text-align:center; font-family:monospace; color:#f87171;"><?= number_format($convert($tEnC??0), 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<script>
function exportExcel(filename) {
    let blob = new Blob([document.getElementById('tblArea').innerHTML], { type: 'application/vnd.ms-excel' });
    let a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = filename + '.xls'; a.click();
}
</script>