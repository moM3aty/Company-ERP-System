<?php
// Path: resources/views/accounting/reports/balance_sheet.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = 'EGP';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';
?>

<style>
    :root { 
        --c-rep: #7c3aed; 
        --c-rep-dark: #5b21b6; 
        --c-rep-light: #f5f3ff; 
        --c-border: #cbd5e1; 
        --c-text-dark: #0f172a; 
        --c-text-muted: #64748b; 
    }
    
    .rep-wrapper { margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .print-only-header { display: none; }

    .rep-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-group { display: flex; gap: 10px; }
    .btn-main { background: linear-gradient(135deg, var(--c-rep), var(--c-rep-dark)); color: #fff !important; border: none; padding: 11px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-size: 0.88rem; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.15); }
    .btn-excel { background: #059669; color: #fff !important; border: none; padding: 11px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.88rem; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.15); }

    .filter-card { background: #fff; border: 1px solid var(--c-border); border-radius: 16px; padding: 18px; display: flex; gap: 16px; align-items: flex-end; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .form-control { border: 1px solid var(--c-border); border-radius: 10px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; font-weight: 700; width: 100%; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--c-rep); background: #ffffff; }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:900px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-card h4 { margin: 0 0 6px 0; font-size: 0.78rem; color: #64748b; font-weight: 800; white-space: nowrap; }
    .kpi-card p { margin: 0; font-size: clamp(1.1rem, 1.5vw, 1.4rem); font-weight: 900; font-family: monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media(max-width:850px){ .grid-2 { grid-template-columns: 1fr; } }
    .card-box { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    
    .table-rep { width: 100%; border-collapse: collapse; font-size: 0.9rem; table-layout: fixed; }
    .table-rep th { padding: 12px 16px; background: #f8fafc; color: #64748b; font-weight: 800; border-bottom: 2px solid #e2e8f0; text-align: start; font-size: 0.78rem; }
    .table-rep td { padding: 11px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; word-wrap: break-word; }
    
    .sec-header td { background: #f1f5f9; font-weight: 900; color: #0f172a; font-size: 0.92rem; }
    .subtotal-row td { background: #f8fafc; font-weight: 800; color: #0f172a; border-top: 1px dashed #cbd5e1; }
    .total-row td { background: var(--c-rep-light); font-weight: 900; color: var(--c-rep-dark); font-size: 1.05rem; }

    /* ========================================================= */
    /* محرك إخفاء النوافذ العلوية والجانبية المعتمد للطباعة A4  */
    /* ========================================================= */
    @media print {
        @page { size: A4 portrait; margin: 8mm 10mm; }
        
        html, body { 
            background: #ffffff !important; 
            margin: 0 !important; 
            padding: 0 !important; 
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important;
            font-size: 8.5pt !important;
            font-family: 'Cairo', 'Inter', sans-serif !important;
        }

        /* إخفاء كلي لهيكل النظام الخارجي (Navbar, Header, Sidebar) */
        body * {
            visibility: hidden !important;
        }

        /* إظهار حاوية التقرير فقط ومحتوياتها */
        .rep-wrapper, .rep-wrapper * {
            visibility: visible !important;
        }

        .rep-wrapper {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* إخفاء عناصر التحكم التفاعلية */
        .rep-header, .filter-card, .btn-group, button, form {
            display: none !important;
        }

        /* إظهار الترويسة المخصصة للطباعة */
        .print-only-header {
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        .kpi-row {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 8px !important;
            margin-bottom: 16px !important;
        }

        .kpi-card {
            border: 1px solid #000000 !important;
            box-shadow: none !important;
            padding: 8px !important;
            page-break-inside: avoid;
        }
        .kpi-card p { font-size: 1rem !important; }

        .grid-2 {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 12px !important;
        }

        .card-box {
            border: 1px solid #000000 !important;
            box-shadow: none !important;
            page-break-inside: avoid;
        }

        .table-rep {
            border-collapse: collapse !important;
            width: 100% !important;
            table-layout: auto !important;
        }

        .table-rep th {
            background: #f1f5f9 !important;
            color: #000000 !important;
            border: 1px solid #000000 !important;
            font-weight: bold !important;
        }

        .table-rep td {
            border: 1px solid #cbd5e1 !important;
            padding: 5px 7px !important;
        }

        .sec-header td, .subtotal-row td {
            background: #f1f5f9 !important;
            color: #000000 !important;
            border: 1px solid #000000 !important;
            font-weight: bold !important;
        }

        .total-row td {
            background: var(--c-rep-light) !important;
            color: #000000 !important;
            border: 2px solid #000000 !important;
            font-weight: bold !important;
        }
    }
</style>

<div class="rep-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
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
                <span style="font-size:0.8rem; color:#475569; font-weight:bold;">تقرير الميزانية العمومية والمركز المالي</span>
            </div>
        </div>
        <div style="text-align:end; font-size:0.8rem; font-weight:bold; color:#0f172a;">
            موقوفة حتى تاريخ: <span dir="ltr"><?= htmlspecialchars($asOfDate) ?></span><br>
            تاريخ الطباعة: <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <!-- شريط التحكم والعرض الشاشي -->
    <div class="rep-header">
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:48px; height:48px; background:var(--c-rep-light); color:var(--c-rep); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.6rem;"><i class="ph-bold ph-scales"></i></div>
            <div>
                <h2 style="margin:0; font-weight:900;">الميزانية العمومية (Balance Sheet)</h2>
                <p style="margin:4px 0 0 0; color:#64748b; font-size:0.88rem;">عرض مبوب للأصول والالتزامات وحقوق الملكية ورأس المال العامل.</p>
            </div>
        </div>
        <div class="btn-group">
            <button type="button" onclick="exportExcel('Balance_Sheet_<?= $asOfDate ?>')" class="btn-excel"><i class="ph-bold ph-file-xls"></i> تصدير Excel</button>
            <button type="button" onclick="window.print()" class="btn-main"><i class="ph-bold ph-printer"></i> طباعة A4</button>
        </div>
    </div>

    <!-- تصفية التقرير -->
    <form action="/ERP/accounting/reports/balance-sheet" method="GET" class="filter-card">
        <div style="flex:1;">
            <label style="font-weight:800; font-size:0.85rem; color:#64748b; margin-bottom:6px; display:block;">موقوفة حتى تاريخ (As of Date)</label>
            <input type="date" name="as_of_date" class="form-control" value="<?= htmlspecialchars($asOfDate) ?>" required>
        </div>
        <button type="submit" class="btn-main" style="height:42px;"><i class="ph-bold ph-funnel"></i> عرض الميزانية</button>
    </form>

    <!-- مؤشرات الأداء المالي -->
    <div class="kpi-row">
        <div class="kpi-card" style="border-bottom: 3px solid #059669;">
            <h4 style="color:#059669;">إجمالي الأصول (Total Assets)</h4>
            <p style="color:#059669;" title="<?= number_format($grandTotalAssets, 2) ?>"><?= number_format($grandTotalAssets, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #dc2626;">
            <h4 style="color:#dc2626;">إجمالي الالتزامات (Total Liabilities)</h4>
            <p style="color:#dc2626;" title="<?= number_format($totCurrLiab + $totNonCurrLiab, 2) ?>"><?= number_format($totCurrLiab + $totNonCurrLiab, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #2563eb;">
            <h4 style="color:#2563eb;">حقوق الملكية والأرباح المرحّلة</h4>
            <p style="color:#2563eb;" title="<?= number_format($totEquity + $currentNetProfit, 2) ?>"><?= number_format($totEquity + $currentNetProfit, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid <?= $workingCapital >= 0 ? 'var(--c-rep)' : '#dc2626' ?>;">
            <h4 style="color:var(--c-rep-dark);">رأس المال العامل (Working Capital)</h4>
            <p style="color:<?= $workingCapital >= 0 ? 'var(--c-rep-dark)' : '#dc2626' ?>;" title="<?= number_format($workingCapital, 2) ?>"><?= number_format($workingCapital, 2) ?></p>
        </div>
    </div>

    <!-- جدول الميزانية المزدوج -->
    <div class="grid-2" id="balanceSheetContent">
        
        <!-- جانب الأصول -->
        <div class="card-box">
            <div style="padding:14px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:900; color:#059669; font-size:1.02rem;">
                <i class="ph-bold ph-trend-up"></i> أولاً: الأصول (Assets)
            </div>
            <table class="table-rep">
                <thead>
                    <tr>
                        <th style="width:25%;">الكود</th>
                        <th style="width:45%;">اسم الحساب</th>
                        <th style="width:30%; text-align:center;">الرصيد (<?= $currency ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="sec-header"><td colspan="3">1. الأصول المتداولة (Current Assets)</td></tr>
                    <?php if(empty($currAssets)): ?>
                        <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:12px;">لا توجد أصول متداولة.</td></tr>
                    <?php else: foreach($currAssets as $a): ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:#475569;"><?= $a->code ?></td>
                            <td style="font-weight:700;"><?= htmlspecialchars($a->name_ar) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format($a->balance, 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    <tr class="subtotal-row">
                        <td colspan="2" style="text-align:end;">مجموع الأصول المتداولة:</td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format($totCurrAssets, 2) ?></td>
                    </tr>

                    <tr class="sec-header"><td colspan="3">2. الأصول غير المتداولة (Non-Current Assets)</td></tr>
                    <?php if(empty($nonCurrAssets)): ?>
                        <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:12px;">لا توجد أصول غير متداولة.</td></tr>
                    <?php else: foreach($nonCurrAssets as $a): ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:#475569;"><?= $a->code ?></td>
                            <td style="font-weight:700;"><?= htmlspecialchars($a->name_ar) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format($a->balance, 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    <tr class="subtotal-row">
                        <td colspan="2" style="text-align:end;">مجموع الأصول غير المتداولة:</td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format($totNonCurrAssets, 2) ?></td>
                    </tr>

                    <tr class="total-row">
                        <td colspan="2" style="text-align:end;">إجمالي الأصول (Total Assets):</td>
                        <td style="text-align:center; font-family:monospace; font-size:1.15rem; color:#059669;"><?= number_format($grandTotalAssets, 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- جانب الخصوم وحقوق الملكية -->
        <div class="card-box">
            <div style="padding:14px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:900; color:#dc2626; font-size:1.02rem;">
                <i class="ph-bold ph-trend-down"></i> ثانياً: الخصوم وحقوق الملكية
            </div>
            <table class="table-rep">
                <thead>
                    <tr>
                        <th style="width:25%;">الكود</th>
                        <th style="width:45%;">اسم الحساب</th>
                        <th style="width:30%; text-align:center;">الرصيد (<?= $currency ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="sec-header"><td colspan="3">1. الالتزامات المتداولة (Current Liabilities)</td></tr>
                    <?php if(empty($currLiabilities)): ?>
                        <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:12px;">لا توجد التزامات متداولة.</td></tr>
                    <?php else: foreach($currLiabilities as $l): ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:#475569;"><?= $l->code ?></td>
                            <td style="font-weight:700;"><?= htmlspecialchars($l->name_ar) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format($l->balance, 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    <tr class="subtotal-row">
                        <td colspan="2" style="text-align:end;">مجموع الالتزامات المتداولة:</td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format($totCurrLiab, 2) ?></td>
                    </tr>

                    <tr class="sec-header"><td colspan="3">2. الالتزامات غير المتداولة (Long-Term Liabilities)</td></tr>
                    <?php if(empty($nonCurrLiabilities)): ?>
                        <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:12px;">لا توجد التزامات غير متداولة.</td></tr>
                    <?php else: foreach($nonCurrLiabilities as $l): ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:#475569;"><?= $l->code ?></td>
                            <td style="font-weight:700;"><?= htmlspecialchars($l->name_ar) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format($l->balance, 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>

                    <tr class="sec-header"><td colspan="3">3. حقوق الملكية والأرباح (Owners' Equity)</td></tr>
                    <?php foreach($equity as $e): ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:#475569;"><?= $e->code ?></td>
                            <td style="font-weight:700;"><?= htmlspecialchars($e->name_ar) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#2563eb;"><?= number_format($e->balance, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background:#fef3c7; font-weight:800; color:#b45309;">
                        <td colspan="2">أرباح / خسائر العام الحالية (Current Net Income):</td>
                        <td style="text-align:center; font-family:monospace; font-weight:900;"><?= number_format($currentNetProfit, 2) ?></td>
                    </tr>

                    <tr class="total-row">
                        <td colspan="2" style="text-align:end;">إجمالي الخصوم وحقوق الملكية:</td>
                        <td style="text-align:center; font-family:monospace; font-size:1.15rem; color:#dc2626;"><?= number_format($grandTotalLiabAndEquity, 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function exportExcel(filename) {
    let content = document.getElementById('balanceSheetContent').innerHTML;
    let html = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="utf-8">
            <style>
                table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
                th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: start; }
                .sec-header td { background-color: #f1f5f9; font-weight: bold; }
                .total-row td { background-color: #f5f3ff; font-weight: bold; }
            </style>
        </head>
        <body dir="rtl">
            <h2>الميزانية العمومية والمركز المالي - ${filename}</h2>
            ${content}
        </body>
        </html>
    `;
    let blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    let link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = filename + '.xls';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>