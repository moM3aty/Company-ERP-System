<?php
// Path: resources/views/accounting/reports/vat_return.php
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

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:900px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-card h4 { margin: 0 0 6px 0; font-size: 0.78rem; color: #64748b; font-weight: 800; }
    .kpi-card p { margin: 0; font-size: 1.4rem; font-weight: 900; font-family: monospace; }

    .table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .rep-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; table-layout: fixed; }
    .rep-table th { padding: 12px 16px; background: #f8fafc; color: #64748b; font-weight: 800; border-bottom: 2px solid #e2e8f0; text-align: start; font-size: 0.78rem; }
    .rep-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; word-wrap: break-word; }

    @media print {
        @page { size: A4 portrait; margin: 10mm 12mm; }
        body { background: #ffffff !important; color: #000000 !important; font-size: 8.5pt !important; font-family: 'Cairo', 'Inter', sans-serif !important; }
        .nt-sidebar, .top-header, .rep-header-actions, .filter-card, button { display: none !important; }
        .rep-wrapper { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .print-only-header { display: flex !important; align-items: center; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 18px; }
        .kpi-row { grid-template-columns: repeat(4, 1fr) !important; gap: 8px !important; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; page-break-inside: auto; }
        .rep-table { border-collapse: collapse !important; width: 100% !important; table-layout: auto !important; }
        .rep-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
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
                <span style="font-size:0.8rem; color:#475569; font-weight:bold;">تقرير الإقرار الضريبي عن القيمة المضافة</span>
            </div>
        </div>
        <div style="text-align:end; font-size:0.8rem; font-weight:bold; color:#0f172a;">
            الفترة من: <span dir="ltr"><?= htmlspecialchars($startDate) ?></span> إلى <span dir="ltr"><?= htmlspecialchars($endDate) ?></span><br>
            تاريخ الطباعة: <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; border-bottom:1px solid #e2e8f0; padding-bottom:16px;" class="rep-header-actions">
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:48px; height:48px; background:var(--c-rep-light); color:var(--c-rep); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.6rem;"><i class="ph-bold ph-receipt"></i></div>
            <div>
                <h2 style="margin:0; font-weight:900;">تقرير الإقرار الضريبي (VAT Return)</h2>
                <p style="margin:4px 0 0 0; color:#64748b; font-size:0.88rem;">تسوية ضريبة المخرجات والمدخلات ومبالغ الخصم والإضافة.</p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="exportExcel('VAT_Return')" style="background:#059669; color:#fff; border:none; padding:11px 20px; border-radius:10px; font-weight:800; cursor:pointer;"><i class="ph-bold ph-file-xls"></i> تصدير Excel</button>
            <button onclick="window.print()" style="background:var(--c-rep); color:#fff; border:none; padding:11px 20px; border-radius:10px; font-weight:800; cursor:pointer;"><i class="ph-bold ph-printer"></i> طباعة الإقرار</button>
        </div>
    </div>

    <form action="/ERP/accounting/reports/vat-return" method="GET" class="filter-card">
        <div style="flex:1;"><label style="font-weight:800; font-size:0.85rem; color:#64748b; margin-bottom:6px; display:block;">من تاريخ</label><input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>"></div>
        <div style="flex:1;"><label style="font-weight:800; font-size:0.85rem; color:#64748b; margin-bottom:6px; display:block;">إلى تاريخ</label><input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>"></div>
        <button type="submit" style="background:#0f172a; color:#fff; border:none; padding:11px 20px; border-radius:10px; font-weight:800; cursor:pointer; height:42px;"><i class="ph-bold ph-funnel"></i> تحديث</button>
    </form>

    <div class="kpi-row">
        <div class="kpi-card" style="border-bottom:3px solid #dc2626;">
            <h4 style="color:#dc2626;">ضريبة مبيعات / مخرجات (Output VAT)</h4>
            <p style="color:#dc2626;" title="<?= number_format($totalOutputVat, 2) ?>"><?= number_format($totalOutputVat, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom:3px solid #059669;">
            <h4 style="color:#059669;">ضريبة مشتريات / مدخلات (Input VAT)</h4>
            <p style="color:#059669;" title="<?= number_format($totalInputVat, 2) ?>"><?= number_format($totalInputVat, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom:3px solid #2563eb;">
            <h4 style="color:#2563eb;">خصم وإضافة (WHT Balance)</h4>
            <p style="color:#2563eb;" title="<?= number_format($totalWht, 2) ?>"><?= number_format($totalWht, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom:3px solid var(--c-rep); background:var(--c-rep-light);">
            <h4 style="color:var(--c-rep-dark);">صافي الضريبة الواجبة السداد</h4>
            <p style="color:var(--c-rep-dark);" title="<?= number_format($netVatPayable, 2) ?>"><?= number_format($netVatPayable, 2) ?> <?= $currency ?></p>
        </div>
    </div>

    <div class="table-card" id="vatTableArea">
        <table class="rep-table">
            <thead>
                <tr>
                    <th style="width:12%;">رقم القيد</th>
                    <th style="width:12%;">التاريخ</th>
                    <th style="width:15%;">كود الضريبة</th>
                    <th style="width:33%;">بيان الإقرار الضريبي</th>
                    <th style="width:14%; text-align:center;">مدخلات / مستردة</th>
                    <th style="width:14%; text-align:center;">مخرجات / مستحقة</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($vatItems)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:35px; color:#94a3b8;">لا توجد حركات ضريبية مسجلة خلال هذه الفترة.</td></tr>
                <?php else: foreach($vatItems as $v): ?>
                    <tr>
                        <td><a href="/ERP/accounting/journal-entries/<?= $v->journal_entry_id ?>" style="font-family:monospace; font-weight:900; color:var(--c-rep); text-decoration:none;">#<?= $v->entry_number ?></a></td>
                        <td style="font-family:monospace; font-weight:700; color:#0f172a;"><?= $v->entry_date ?></td>
                        <td style="font-weight:800; color:var(--c-rep-dark);"><?= htmlspecialchars($v->tax_code) ?> (<?= (float)$v->tax_rate ?>%)</td>
                        <td style="font-weight:700; color:#334155;"><?= htmlspecialchars($v->description ?: $v->entry_desc) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format((float)$v->debit, 2) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format((float)$v->credit, 2) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function exportExcel(filename) {
    let blob = new Blob([document.getElementById('vatTableArea').innerHTML], { type: 'application/vnd.ms-excel' });
    let a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = filename + '.xls'; a.click();
}
</script>