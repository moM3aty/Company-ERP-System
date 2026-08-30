<?php
// Path: resources/views/accounting/reports/trial_balance.php
if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = 'EGP';
global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';
?>

<style>
    :root { --c-rep: #7c3aed; --c-rep-dark: #5b21b6; --c-rep-light: #f5f3ff; --c-border: #cbd5e1; }
    .rep-wrapper {  margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .print-only-header { display: none; }
    
    .filter-card { background: #fff; border: 1px solid var(--c-border); border-radius: 16px; padding: 18px; display: flex; gap: 16px; align-items: flex-end; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .form-control { border: 1px solid var(--c-border); border-radius: 10px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; font-weight: 700; width: 100%; box-sizing: border-box; }
    
    .table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .rep-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; table-layout: fixed; }
    .rep-table th { padding: 11px 10px; background: #f8fafc; color: #64748b; font-weight: 800; border: 1px solid #cbd5e1; text-align: center; font-size: 0.75rem; }
    .rep-table td { padding: 10px 12px; border: 1px solid #e2e8f0; vertical-align: middle; word-wrap: break-word; }
    .tot-row td { background: #0f172a !important; color: #ffffff !important; font-weight: 900; font-size: 0.92rem; padding: 12px; }

    @media print {
        @page { size: A4 portrait; margin: 10mm 12mm; }
        body { background: #ffffff !important; color: #000000 !important; font-size: 8pt !important; font-family: 'Cairo', 'Inter', sans-serif !important; }
        .nt-sidebar, .top-header, .rep-header-actions, .filter-card, button { display: none !important; }
        .rep-wrapper { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .print-only-header { display: flex !important; align-items: center; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 18px; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; page-break-inside: auto; }
        .rep-table { border-collapse: collapse !important; width: 100% !important; table-layout: auto !important; }
        .rep-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
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
                <span style="font-size:0.8rem; color:#475569; font-weight:bold;">تقرير ميزان المراجعة بالمجاميع والأرصدة</span>
            </div>
        </div>
        <div style="text-align:end; font-size:0.8rem; font-weight:bold; color:#0f172a;">
            عن الفترة من: <span dir="ltr"><?= htmlspecialchars($startDate) ?></span> إلى <span dir="ltr"><?= htmlspecialchars($endDate) ?></span><br>
            تاريخ الاستخراج: <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; border-bottom:1px solid #e2e8f0; padding-bottom:16px;" class="rep-header-actions">
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:48px; height:48px; background:var(--c-rep-light); color:var(--c-rep); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.6rem;"><i class="ph-bold ph-list-numbers"></i></div>
            <div>
                <h2 style="margin:0; font-weight:900;">ميزان المراجعة (Trial Balance)</h2>
                <p style="margin:4px 0 0 0; color:#64748b; font-size:0.88rem;">التحقق التلقائي من انضباط وتوازن الدفاتر المحاسبية.</p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="exportExcel('Trial_Balance')" style="background:#059669; color:#fff; border:none; padding:11px 20px; border-radius:10px; font-weight:800; cursor:pointer;"><i class="ph-bold ph-file-xls"></i> تصدير Excel</button>
            <button onclick="window.print()" style="background:var(--c-rep); color:#fff; border:none; padding:11px 20px; border-radius:10px; font-weight:800; cursor:pointer;"><i class="ph-bold ph-printer"></i> طباعة A4</button>
        </div>
    </div>

    <!-- مؤشر توازن الميزان -->
    <div style="padding:14px 20px; border-radius:12px; margin-bottom:20px; font-weight:800; display:flex; align-items:center; gap:10px; background: <?= $isBalanced ? '#ecfdf5' : '#fef2f2' ?>; color: <?= $isBalanced ? '#059669' : '#dc2626' ?>; border: 1px solid <?= $isBalanced ? '#a7f3d0' : '#fecaca' ?>;">
        <i class="ph-bold <?= $isBalanced ? 'ph-check-circle' : 'ph-warning-circle' ?>" style="font-size:1.4rem;"></i>
        <span>حالة الميزان المحاسبي: <?= $isBalanced ? 'متزن 100% (إجمالي المدين يساوي إجمالي الدائن)' : 'غير متزن! يرجى مراجعة القيود غير المرحّلة.' ?></span>
    </div>

    <form action="/ERP/accounting/reports/trial-balance" method="GET" class="filter-card">
        <div style="flex:1;"><label style="font-weight:800; font-size:0.85rem; color:#64748b; margin-bottom:6px; display:block;">من تاريخ</label><input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>"></div>
        <div style="flex:1;"><label style="font-weight:800; font-size:0.85rem; color:#64748b; margin-bottom:6px; display:block;">إلى تاريخ</label><input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>"></div>
        <div style="flex:1.5;"><label style="font-weight:800; font-size:0.85rem; color:#64748b; margin-bottom:6px; display:block;">بحث باسم الحساب أو الكود</label><input type="text" name="search" class="form-control" placeholder="ابحث..." value="<?= htmlspecialchars($search) ?>"></div>
        <button type="submit" style="background:#0f172a; color:#fff; border:none; padding:11px 20px; border-radius:10px; font-weight:800; cursor:pointer; height:42px;"><i class="ph-bold ph-funnel"></i> تحديث</button>
    </form>

    <div class="table-card" id="tblArea">
        <table class="rep-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width:10%;">الكود</th>
                    <th rowspan="2" style="width:26%;">اسم الحساب المحاسبي</th>
                    <th colspan="2">أرصدة بداية الفترة</th>
                    <th colspan="2">حركات الفترة (المجاميع)</th>
                    <th colspan="2">أرصدة نهاية الفترة</th>
                </tr>
                <tr>
                    <th style="width:11%;">مدين</th><th style="width:11%;">دائن</th>
                    <th style="width:11%;">مدين</th><th style="width:11%;">دائن</th>
                    <th style="width:11%;">مدين</th><th style="width:11%;">دائن</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($rows)): ?>
                    <tr><td colspan="8" style="text-align:center; padding:35px; color:#94a3b8;">لا توجد حركات أو أرصدة خلال هذه الفترة.</td></tr>
                <?php else: foreach($rows as $r): ?>
                    <tr>
                        <td style="font-family:monospace; font-weight:900; color:var(--c-rep-dark); text-align:center;"><?= $r->code ?></td>
                        <td style="font-weight:700; color:#0f172a;"><?= htmlspecialchars($r->name_ar) ?></td>
                        <td style="text-align:center; font-family:monospace; color:#059669;"><?= $r->op_debit > 0 ? number_format($r->op_debit, 2) : '-' ?></td>
                        <td style="text-align:center; font-family:monospace; color:#dc2626;"><?= $r->op_credit > 0 ? number_format($r->op_credit, 2) : '-' ?></td>
                        <td style="text-align:center; font-family:monospace; color:#059669;"><?= $r->period_debit > 0 ? number_format($r->period_debit, 2) : '-' ?></td>
                        <td style="text-align:center; font-family:monospace; color:#dc2626;"><?= $r->period_credit > 0 ? number_format($r->period_credit, 2) : '-' ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669; background:#f0fdf4;"><?= $r->end_debit > 0 ? number_format($r->end_debit, 2) : '-' ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626; background:#fef2f2;"><?= $r->end_credit > 0 ? number_format($r->end_credit, 2) : '-' ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                <tr class="tot-row">
                    <td colspan="2" style="text-align:end;">الإجمالي العام (Total Balance):</td>
                    <td style="text-align:center; font-family:monospace;"><?= number_format($totOpDeb, 2) ?></td>
                    <td style="text-align:center; font-family:monospace;"><?= number_format($totOpCrd, 2) ?></td>
                    <td style="text-align:center; font-family:monospace;"><?= number_format($totPerDeb, 2) ?></td>
                    <td style="text-align:center; font-family:monospace;"><?= number_format($totPerCrd, 2) ?></td>
                    <td style="text-align:center; font-family:monospace; color:#4ade80;"><?= number_format($totEndDeb, 2) ?></td>
                    <td style="text-align:center; font-family:monospace; color:#f87171;"><?= number_format($totEndCrd, 2) ?></td>
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