<?php
// Path: resources/views/accounting/cost_centers/report.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = 'EGP';

$budget = (float)($center->budget_amount ?? 0);
$budgetUsed = $totalExpenses;
$budgetPercent = $budget > 0 ? min(100, round(($budgetUsed / $budget) * 100, 1)) : 0;
?>

<style>
    :root { --c-cc: #7c3aed; --c-cc-dark: #6d28d9; --c-cc-light: #f5f3ff; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .report-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .report-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-back { width: 42px; height: 42px; border-radius: 10px; background: #fff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); }
    .btn-print { background: var(--c-text-dark); color: #fff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:768px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; }
    .kpi-card h4 { margin: 0 0 6px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-card p { margin: 0; font-size: 1.5rem; font-weight: 900; font-family: monospace; }

    .progress-bar-bg { background: #e2e8f0; border-radius: 8px; height: 12px; width: 100%; overflow: hidden; margin-top: 8px; }
    .progress-bar-fill { background: var(--c-cc); height: 100%; border-radius: 8px; }

    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 24px; }
    @media(max-width:768px){ .grid-2 { grid-template-columns: 1fr; } }

    .table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .report-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    .report-table th { padding: 12px 16px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; border-bottom: 2px solid #e2e8f0; text-align: start; font-size: 0.75rem; }
    .report-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    @media print {
        .nt-sidebar, .top-header, .report-header, header, aside { display: none !important; }
        body { background: #fff !important; }
        .report-wrapper { max-width: 100% !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="report-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="report-header">
        <div style="display:flex; align-items:center; gap:12px;">
            <a href="/ERP/accounting/cost-centers/<?= $center->id ?>" class="btn-back"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;">تقرير الأداء المالي: <?= htmlspecialchars($center->name_ar) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-cc-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($center->code) ?></p>
            </div>
        </div>
        <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة التقرير التحليلي</button>
    </div>

    <!-- KPIs -->
    <div class="kpi-row">
        <div class="kpi-card" style="border-bottom: 3px solid #16a34a;">
            <h4 style="color:#16a34a;">إجمالي الإيرادات المحققة</h4>
            <p style="color:#16a34a;"><?= number_format($totalRevenues, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #dc2626;">
            <h4 style="color:#dc2626;">إجمالي المصروفات التراكمية</h4>
            <p style="color:#dc2626;"><?= number_format($totalExpenses, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid <?= $netProfit >= 0 ? '#2563eb' : '#dc2626' ?>; background: <?= $netProfit >= 0 ? '#eff6ff' : '#fef2f2' ?>;">
            <h4 style="color:<?= $netProfit >= 0 ? '#2563eb' : '#dc2626' ?>;">صافي أرباح / خسائر المركز</h4>
            <p style="color:<?= $netProfit >= 0 ? '#2563eb' : '#dc2626' ?>;"><?= number_format($netProfit, 2) ?> <span style="font-size:0.8rem; font-weight:normal;"><?= $currency ?></span></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid var(--c-cc);">
            <h4 style="color:var(--c-cc);">استهلاك الموازنة التقديرية</h4>
            <p style="color:var(--c-cc);"><?= $budgetPercent ?>%</p>
            <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?= $budgetPercent ?>%; background: <?= $budgetPercent > 90 ? '#dc2626' : 'var(--c-cc)' ?>;"></div></div>
        </div>
    </div>

    <div class="grid-2">
        <div class="table-card">
            <div style="padding:14px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:800; color:var(--c-text-dark);">
                <i class="ph-bold ph-chart-pie-slice" style="color:var(--c-cc);"></i> تفكيك التكاليف والإيرادات بالحسابات
            </div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>الكود</th>
                        <th>الحساب</th>
                        <th>النوع</th>
                        <th style="text-align:center;">الصافي</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($accountBreakdown)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:30px; color:#94a3b8;">لا توجد حركات مسجلة.</td></tr>
                    <?php else: foreach($accountBreakdown as $ab): $net = $ab->type === 'revenue' ? ($ab->total_credit - $ab->total_debit) : ($ab->total_debit - $ab->total_credit); ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:var(--c-cc-dark);"><?= htmlspecialchars($ab->code) ?></td>
                            <td style="font-weight:700; color:var(--c-text-dark);"><?= htmlspecialchars($ab->name_ar) ?></td>
                            <td><span style="font-size:0.75rem; font-weight:800; padding:2px 6px; border-radius:4px; <?= $ab->type==='revenue'?'background:#f0fdf4; color:#16a34a;':'background:#fff7ed; color:#c2410c;' ?>"><?= $ab->type==='revenue'?'إيراد':'مصروف' ?></span></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; <?= $ab->type==='revenue'?'color:#16a34a;':'color:#dc2626;' ?>"><?= number_format((float)$net, 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-card">
            <div style="padding:14px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:800; color:var(--c-text-dark);">
                <i class="ph-bold ph-list-dashes" style="color:var(--c-cc);"></i> أحدث القيود المؤثرة على المركز
            </div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>القيد</th>
                        <th>التاريخ</th>
                        <th>الحساب</th>
                        <th style="text-align:center;">المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($transactions)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:30px; color:#94a3b8;">لا توجد قيود مرحلة اقتطعت من هذا المركز.</td></tr>
                    <?php else: foreach($transactions as $tx): $val = $tx->debit > 0 ? $tx->debit : $tx->credit; ?>
                        <tr>
                            <td><a href="/ERP/accounting/journal-entries/<?= $tx->journal_entry_id ?>" style="font-family:monospace; font-weight:900; color:var(--c-cc); text-decoration:none;">#<?= htmlspecialchars($tx->entry_number) ?></a></td>
                            <td style="font-family:monospace; font-size:0.8rem;"><?= htmlspecialchars($tx->entry_date) ?></td>
                            <td style="font-weight:700; color:var(--c-text-dark);"><?= htmlspecialchars($tx->account_name) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:var(--c-text-dark);"><?= number_format((float)$val, 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>