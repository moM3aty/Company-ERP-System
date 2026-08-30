<?php
// Path: resources/views/accounting/budgets/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = 'EGP';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';
?>

<style>
    :root { --c-bg: #e11d48; --c-bg-dark: #be123c; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .bg-show-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .print-only-header { display: none; }

    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; }
    .kpi-card h4 { margin: 0 0 6px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-card p { margin: 0; font-size: 1.5rem; font-weight: 900; font-family: monospace; }

    .table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .var-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    .var-table th { padding: 12px 16px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; border-bottom: 2px solid #e2e8f0; text-align: start; font-size: 0.75rem; }
    .var-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .progress-bg { background: #e2e8f0; border-radius: 6px; height: 8px; width: 80px; display: inline-block; overflow: hidden; vertical-align: middle; margin-inline-start: 6px; }
    .progress-fill { height: 100%; border-radius: 6px; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .bg-show-wrapper { max-width: 100% !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .print-only-header { display: flex !important; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--c-bg); padding-bottom: 15px; margin-bottom: 20px; }
    }
</style>

<div class="bg-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <div class="print-only-header">
        <div style="display:flex; align-items:center; gap:15px;">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:50px;">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:2.5rem; color:var(--c-bg);"></i>
            <?php endif; ?>
            <h2 style="margin:0; font-size:1.5rem; color:#000; font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
        </div>
        <div style="text-align:end; font-size:0.85rem; font-weight:bold;">
            تقرير تحليل انحراف الموازنة التقديرية<br>
            تاريخ الطباعة: <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/accounting/budgets" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($budget->name_ar) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-bg-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($budget->code) ?> | السنة المالية: <?= (int)$budget->fiscal_year ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة تقرير الانحرافات</button>
            <a href="/ERP/accounting/budgets/<?= $budget->id ?>/edit" class="btn-action" title="تعديل"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <?php 
        $totalActual = 0;
        foreach($analysisItems as $ai) { $totalActual += $ai->actual_amount; }
        $totalAllocated = (float)$budget->total_allocated;
        $totalVariance = $totalAllocated - $totalActual;
        $totalPercent = $totalAllocated > 0 ? round(($totalActual / $totalAllocated) * 100, 1) : 0;
    ?>

    <div class="kpi-row">
        <div class="kpi-card" style="border-bottom: 3px solid var(--c-bg);">
            <h4 style="color:var(--c-bg-dark);">إجمالي الاعتماد المخطط</h4>
            <p style="color:var(--c-bg-dark);"><?= number_format($totalAllocated, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #2563eb;">
            <h4 style="color:#2563eb;">إجمالي الفعلي المنصرف</h4>
            <p style="color:#2563eb;"><?= number_format($totalActual, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid <?= $totalVariance >= 0 ? '#16a34a' : '#dc2626' ?>; background: <?= $totalVariance >= 0 ? '#f0fdf4' : '#fef2f2' ?>;">
            <h4 style="color:<?= $totalVariance >= 0 ? '#16a34a' : '#dc2626' ?>;">صافي الانحراف الموازني (Variance)</h4>
            <p style="color:<?= $totalVariance >= 0 ? '#16a34a' : '#dc2626' ?>;"><?= number_format($totalVariance, 2) ?> <span style="font-size:0.8rem; font-weight:normal;"><?= $currency ?></span></p>
        </div>
    </div>

    <div class="table-card">
        <div style="padding:14px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:800; color:var(--c-text-dark); display:flex; justify-content:space-between; align-items:center;">
            <span><i class="ph-bold ph-chart-line-up" style="color:var(--c-bg);"></i> تحليل الانحرافات التفصيلي بالحسابات (Budget vs Actual)</span>
            <span style="font-size:0.8rem; font-weight:bold; color:var(--c-bg-dark);">إجمالي استهلاك الموازنة: <?= $totalPercent ?>%</span>
        </div>
        <table class="var-table">
            <thead>
                <tr>
                    <th style="width: 12%;">كود الحساب</th>
                    <th style="width: 28%;">اسم الحساب المالي</th>
                    <th style="width: 15%; text-align: center;">المخطط (Budget)</th>
                    <th style="width: 15%; text-align: center;">الفعلي (Actual)</th>
                    <th style="width: 15%; text-align: center;">الانحراف (Variance)</th>
                    <th style="width: 15%; text-align: center;">نسبة الاستهلاك</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($analysisItems)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:30px; color:#94a3b8;">لا توجد بنود مخصصة لهذه الموازنة.</td></tr>
                <?php else: foreach ($analysisItems as $item): 
                    $alloc = (float)$item->allocated_amount;
                    $act = (float)$item->actual_amount;
                    $var = $alloc - $act;
                    $pct = $alloc > 0 ? min(100, round(($act / $alloc) * 100, 1)) : 0;
                    $isOver = $act > $alloc;
                ?>
                    <tr>
                        <td style="font-family:monospace; font-weight:800; color:var(--c-bg-dark);"><?= htmlspecialchars($item->acc_code) ?></td>
                        <td style="font-weight:800; color:var(--c-text-dark);"><?= htmlspecialchars($item->acc_name) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#0f172a;"><?= number_format($alloc, 2) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#2563eb;"><?= number_format($act, 2) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:<?= $var >= 0 ? '#16a34a' : '#dc2626' ?>;">
                            <?= number_format($var, 2) ?>
                        </td>
                        <td style="text-align:center;">
                            <span style="font-family:monospace; font-weight:800; <?= $isOver ? 'color:#dc2626;' : 'color:#475569;' ?>"><?= $pct ?>%</span>
                            <div class="progress-bg"><div class="progress-fill" style="width:<?= $pct ?>%; background:<?= $isOver ? '#dc2626' : 'var(--c-bg)' ?>;"></div></div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>