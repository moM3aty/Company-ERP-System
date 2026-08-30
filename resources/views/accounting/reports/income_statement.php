<?php
// Path: resources/views/accounting/reports/income_statement.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = 'EGP';

$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_err']);

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';
?>

<style>
    :root {
        --c-is: #1e40af; 
        --c-is-dark: #0f172a; 
        --c-is-light: #eff6ff;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .is-wrapper { padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; margin: 0 auto; }
    
    .print-only-header { display: none; }

    .is-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .is-title-box { display: flex; align-items: center; gap: 16px; }
    .is-icon { width: 52px; height: 52px; background: var(--c-is-light); color: var(--c-is); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 12px rgba(30, 64, 175, 0.12); flex-shrink: 0; }
    .is-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    
    .btn-group-actions { display: flex; gap: 10px; }
    .btn-action-main { background: linear-gradient(135deg, #1e40af, #0f172a); color: #ffffff !important; border: none; padding: 11px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15); font-size: 0.88rem; }
    .btn-action-excel { background: #059669; color: #ffffff !important; border: none; padding: 11px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.15); font-size: 0.88rem; }

    /* Filter Form */
    .filter-card { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 20px; display: flex; gap: 16px; align-items: flex-end; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .form-group { display: flex; flex-direction: column; gap: 6px; flex: 1; }
    .form-label { font-size: 0.85rem; font-weight: 800; color: var(--c-text-muted); }
    .form-control { border: 1px solid var(--c-border); border-radius: 10px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; color: var(--c-text-dark); font-weight: 700; outline: none; transition: all 0.2s; box-sizing: border-box; width: 100%; }
    .form-control:focus { border-color: var(--c-is); background: #ffffff; }
    .btn-filter { background: var(--c-text-dark); color: #ffffff; border: none; padding: 11px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }

    /* KPIs with dynamic number safety */
    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:900px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: center; }
    .kpi-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: currentColor; }
    .kpi-card h4 { margin: 0 0 8px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; white-space: nowrap; }
    .kpi-card p { margin: 0; font-size: clamp(1rem, 1.6vw, 1.4rem); font-weight: 900; font-family: monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* Report Table Structure */
    .report-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 24px; }
    .report-toolbar { padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .report-search { padding: 9px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; width: 280px; outline: none; font-family: inherit; font-weight: 600; }
    
    .rep-table { width: 100%; border-collapse: collapse; font-size: 0.92rem; table-layout: fixed; }
    .rep-table th { padding: 14px 20px; background: #f1f5f9; color: var(--c-text-muted); font-weight: 900; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; font-size: 0.78rem; text-align: start; }
    .rep-table td { padding: 13px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; word-wrap: break-word; }
    
    .rep-section-title td { background: #f1f5f9; font-weight: 900; color: #0f172a; font-size: 1.02rem; padding: 14px 20px; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; }
    
    .rep-subtotal td { font-weight: 900; color: #0f172a; background: #f8fafc; border-top: 2px solid #cbd5e1 !important; border-bottom: 2px solid #cbd5e1 !important; padding: 14px 20px; }
    
    /* Grand Total Crisp Contrast Fix */
    .rep-grandtotal td { font-weight: 900; font-size: 1.15rem; background: #0f172a !important; color: #ffffff !important; padding: 18px 20px; border: none !important; }

    .progress-bar-bg { background: #e2e8f0; border-radius: 6px; height: 6px; width: 60px; display: inline-block; overflow: hidden; vertical-align: middle; margin-inline-start: 8px; }
    .progress-bar-fill { height: 100%; border-radius: 6px; }

    /* ========================================================= */
    /* قواعد الطباعة الأكاديمية (A4 Print Engine)                 */
    /* ========================================================= */
    @media print {
        @page { size: A4 portrait; margin: 12mm 15mm; }
        body { background: #ffffff !important; color: #000000 !important; font-size: 9.5pt; font-family: 'Cairo', 'Inter', sans-serif !important; }
        .nt-sidebar, .top-header, .is-header, header, aside, .filter-card, .report-toolbar { display: none !important; }
        
        .is-wrapper { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        
        .print-only-header { display: flex !important; align-items: flex-end; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 20px; }
        
        .kpi-row { grid-template-columns: repeat(4, 1fr) !important; gap: 10px !important; margin-bottom: 20px !important; }
        .kpi-card { border: 1px solid #000000 !important; box-shadow: none !important; padding: 10px !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .kpi-card::before { display: none !important; }
        .kpi-card p { font-size: 1.1rem !important; }

        .report-card { border: 1px solid #000000 !important; box-shadow: none !important; page-break-inside: auto; }
        .rep-table { border-collapse: collapse !important; width: 100% !important; table-layout: auto !important; }
        .rep-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .rep-table td { border: 1px solid #cbd5e1 !important; padding: 7px 10px !important; }
        .rep-section-title td, .rep-subtotal td { border: 1px solid #000000 !important; background: #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; color: #000000 !important; }
        .rep-grandtotal td { background: #0f172a !important; color: #ffffff !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        .progress-bar-bg { display: none !important; }
    }
</style>

<div class="is-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <!-- هيدر الطباعة الرسمية A4 -->
    <div class="print-only-header">
        <div style="display:flex; align-items:center; gap:15px;">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:50px;">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:2.5rem; color:#0f172a;"></i>
            <?php endif; ?>
            <div>
                <h2 style="margin:0; font-size:1.4rem; color:#000; font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                <span style="font-size:0.85rem; color:#475569; font-weight:bold;">تقرير قائمة الدخل والأداء المالي</span>
            </div>
        </div>
        <div style="text-align:end; font-size:0.85rem; font-weight:bold; color:#0f172a;">
            <span style="font-size:1.2rem; font-weight:900; display:block; color:#0f172a;">قائمة الدخل (Income Statement)</span>
            عن الفترة من: <span dir="ltr"><?= htmlspecialchars($startDate) ?></span> إلى <span dir="ltr"><?= htmlspecialchars($endDate) ?></span><br>
            <span style="font-size:0.75rem; color:#64748b; font-weight:normal;">تاريخ الاستخراج: <?= date('Y-m-d H:i') ?></span>
        </div>
    </div>

    <!-- شريط العرض العادي -->
    <div class="is-header">
        <div class="is-title-box">
            <div class="is-icon"><i class="ph-duotone ph-chart-line-up"></i></div>
            <div>
                <h2 class="is-title">قائمة الدخل (Profit & Loss)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;">ملخص الإيرادات والمصروفات وقياس صافي الأرباح بالفترة المالية.</p>
            </div>
        </div>
        <div class="btn-group-actions">
            <button type="button" onclick="exportToExcel('Income_Statement_<?= $startDate ?>_to_<?= $endDate ?>')" class="btn-action-excel">
                <i class="ph-bold ph-file-xls"></i> تصدير شيت Excel
            </button>
            <button type="button" onclick="window.print()" class="btn-action-main">
                <i class="ph-bold ph-printer"></i> طباعة التقرير
            </button>
        </div>
    </div>

    <?php if($flashErr): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;">
            <i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?>
        </div>
    <?php endif; ?>

    <!-- لوحة فلاتر التقرير -->
    <form action="/ERP/accounting/reports/income-statement" method="GET" class="filter-card">
        <div class="form-group">
            <label class="form-label">من تاريخ (Start Date)</label>
            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">إلى تاريخ (End Date)</label>
            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>" required>
        </div>
        <div class="form-group" style="flex: 1.5;">
            <label class="form-label">تصفية بمركز تكلفة (اختياري)</label>
            <select name="cost_center_id" class="form-control">
                <option value="">-- كافة مراكز التكلفة إجمالاً --</option>
                <?php foreach($costCenters as $cc): ?>
                    <option value="<?= $cc->id ?>" <?= ($costCenterId == $cc->id) ? 'selected' : '' ?>><?= htmlspecialchars($cc->code) ?> - <?= htmlspecialchars($cc->name_ar) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-filter"><i class="ph-bold ph-funnel"></i> تحديث التقرير</button>
    </form>

    <!-- مؤشرات الأداء المالي (KPIs) -->
    <div class="kpi-row">
        <div class="kpi-card" style="color:#16a34a;">
            <h4 style="color:#16a34a;">إجمالي الإيرادات (Total Revenue)</h4>
            <p title="<?= number_format($totalRevenue, 2) ?>"><?= number_format($totalRevenue, 2) ?></p>
        </div>
        <div class="kpi-card" style="color:#dc2626;">
            <h4 style="color:#dc2626;">إجمالي المصروفات (Total Expenses)</h4>
            <p title="<?= number_format($totalExpense, 2) ?>"><?= number_format($totalExpense, 2) ?></p>
        </div>
        <div class="kpi-card" style="color:<?= $netIncome >= 0 ? '#2563eb' : '#dc2626' ?>;">
            <h4 style="color:var(--c-text-muted);">صافي الدخل (Net Income)</h4>
            <p title="<?= number_format($netIncome, 2) ?>"><?= number_format($netIncome, 2) ?></p>
        </div>
        <div class="kpi-card" style="color:<?= $netMargin >= 0 ? '#0284c7' : '#dc2626' ?>;">
            <h4 style="color:var(--c-text-muted);">هامش الربح (Profit Margin)</h4>
            <p title="<?= $netMargin ?>%"><?= $netMargin ?>%</p>
        </div>
    </div>

    <!-- التقرير الهيكلي (Structured Report) -->
    <div class="report-card">
        <div class="report-toolbar">
            <span style="font-weight:900; color:#0f172a; font-size:1rem;"><i class="ph-bold ph-tree-structure" style="color:var(--c-is);"></i> جدول قائمة الدخل المالي</span>
            <input type="text" id="searchInput" class="report-search" placeholder="بحث بالحساب أو الكود..." onkeyup="filterReport()">
        </div>
        
        <table class="rep-table" id="reportTable">
            <thead>
                <tr>
                    <th style="width: 15%;">كود الحساب</th>
                    <th style="width: 45%;">اسم الحساب (Account Name)</th>
                    <th style="width: 22%; text-align: center;">الرصيد (<?= $currency ?>)</th>
                    <th style="width: 18%; text-align: center;">التحليل الرأسي (% إيراد)</th>
                </tr>
            </thead>
            <tbody>
                
                <!-- قسم الإيرادات -->
                <tr class="rep-section-title">
                    <td colspan="4"><i class="ph-bold ph-trend-up" style="color:#16a34a;"></i> أولاً: الإيرادات (Revenues)</td>
                </tr>
                
                <?php if(empty($revenues)): ?>
                    <tr class="data-row"><td colspan="4" style="text-align:center; padding:20px; color:#94a3b8;">لا توجد إيرادات محققة في هذه الفترة.</td></tr>
                <?php else: foreach($revenues as $rev): 
                    $rawPct = $totalRevenue > 0 ? round(($rev->balance / $totalRevenue) * 100, 1) : 0;
                    $visPct = min(100, max(0, $rawPct));
                ?>
                    <tr class="data-row">
                        <td style="font-family:monospace; font-weight:800; color:#475569;"><?= htmlspecialchars($rev->code) ?></td>
                        <td style="font-weight:700; color:var(--c-text-dark);"><?= htmlspecialchars($rev->name_ar) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#16a34a;"><?= number_format($rev->balance, 2) ?></td>
                        <td style="text-align:center; white-space:nowrap;">
                            <span style="font-family:monospace; font-size:0.8rem; font-weight:bold; color:#64748b;"><?= $rawPct ?>%</span>
                            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:<?= $visPct ?>%; background:#16a34a;"></div></div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                
                <tr class="rep-subtotal">
                    <td colspan="2" style="text-align:end;">إجمالي الإيرادات (Total Revenues):</td>
                    <td style="text-align:center; color:#16a34a; font-family:monospace; font-size:1.05rem;"><?= number_format($totalRevenue, 2) ?></td>
                    <td style="text-align:center; font-family:monospace; color:#16a34a;">100%</td>
                </tr>

                <!-- مسافة فاصلة -->
                <tr><td colspan="4" style="height:16px; background:#ffffff; border:none;"></td></tr>

                <!-- قسم المصروفات -->
                <tr class="rep-section-title">
                    <td colspan="4"><i class="ph-bold ph-trend-down" style="color:#dc2626;"></i> ثانياً: المصروفات (Expenses)</td>
                </tr>
                
                <?php if(empty($expenses)): ?>
                    <tr class="data-row"><td colspan="4" style="text-align:center; padding:20px; color:#94a3b8;">لا توجد مصروفات مسجلة في هذه الفترة.</td></tr>
                <?php else: foreach($expenses as $exp): 
                    $rawPct = $totalRevenue > 0 ? round(($exp->balance / $totalRevenue) * 100, 1) : 0;
                    $visPct = min(100, max(0, $rawPct));
                ?>
                    <tr class="data-row">
                        <td style="font-family:monospace; font-weight:800; color:#475569;"><?= htmlspecialchars($exp->code) ?></td>
                        <td style="font-weight:700; color:var(--c-text-dark);"><?= htmlspecialchars($exp->name_ar) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format($exp->balance, 2) ?></td>
                        <td style="text-align:center; white-space:nowrap;">
                            <span style="font-family:monospace; font-size:0.8rem; font-weight:bold; color:#64748b;"><?= $rawPct ?>%</span>
                            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width:<?= $visPct ?>%; background:#dc2626;"></div></div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                
                <tr class="rep-subtotal">
                    <td colspan="2" style="text-align:end;">إجمالي المصروفات (Total Expenses):</td>
                    <td style="text-align:center; color:#dc2626; font-family:monospace; font-size:1.05rem;"><?= number_format($totalExpense, 2) ?></td>
                    <td style="text-align:center; font-family:monospace; color:#dc2626;"><?= $totalRevenue > 0 ? round(($totalExpense/$totalRevenue)*100, 1) : 0 ?>%</td>
                </tr>

                <!-- النتيجة النهائية (صافي الدخل مع تباين واضح جداً) -->
                <tr class="rep-grandtotal">
                    <td colspan="2" style="text-align:end;">صافي الدخل للفترة (Net Income):</td>
                    <td style="text-align:center; font-family:monospace; font-size:1.3rem; color: #ffffff !important;"><?= number_format($netIncome, 2) ?></td>
                    <td style="text-align:center; font-family:monospace; color: #ffffff !important;"><?= $netMargin ?>%</td>
                </tr>

            </tbody>
        </table>
    </div>
</div>

<script>
// بحث تفاعلي داخل التقرير
function filterReport() {
    let input = document.getElementById("searchInput");
    let filter = input.value.toLowerCase();
    let table = document.getElementById("reportTable");
    let tr = table.getElementsByClassName("data-row");

    for (let i = 0; i < tr.length; i++) {
        let codeTd = tr[i].getElementsByTagName("td")[0];
        let nameTd = tr[i].getElementsByTagName("td")[1];
        if (codeTd || nameTd) {
            let txtValueCode = codeTd.textContent || codeTd.innerText;
            let txtValueName = nameTd.textContent || nameTd.innerText;
            if (txtValueCode.toLowerCase().indexOf(filter) > -1 || txtValueName.toLowerCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }       
    }
}

// تصدير جدول قائمة الدخل لشيت Excel
function exportToExcel(filename = '') {
    let table = document.getElementById("reportTable");
    let cloneTable = table.cloneNode(true);
    
    // إزالة أشرطة التقدم من الجدول المصدر
    let progressBars = cloneTable.querySelectorAll('.progress-bar-bg');
    progressBars.forEach(el => el.remove());

    let html = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="utf-8">
            <style>
                table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
                th { background-color: #f1f5f9; color: #000; border: 1px solid #000; font-weight: bold; }
                td { border: 1px solid #cbd5e1; padding: 8px; }
                .rep-section-title td { background-color: #e2e8f0; font-weight: bold; }
                .rep-subtotal td { background-color: #f8fafc; font-weight: bold; border-top: 2px solid #000; }
                .rep-grandtotal td { background-color: #0f172a; color: #ffffff; font-weight: bold; }
            </style>
        </head>
        <body dir="rtl">
            <h2>قائمة الدخل - ${filename}</h2>
            ${cloneTable.outerHTML}
        </body>
        </html>
    `;

    let blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    let link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = (filename || 'Income_Statement') + '.xls';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>