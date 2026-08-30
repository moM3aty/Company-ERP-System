<?php
// Path: resources/views/hr/payroll/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$statusMap = [
    'draft' => ['label' => 'مسودة قيد الإعداد', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'processed' => ['label' => 'مسير معتمد ومحتسب', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
    'paid' => ['label' => 'مسير مصروف بالكامل', 'color' => '#059669', 'bg' => '#ecfdf5'],
];
$st = $statusMap[$payroll->status] ?? $statusMap['processed'];
?>

<style>
    :root { --c-pay: #be123c; --c-pay-dark: #9f1239; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .pay-show-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-pay-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .card-box { background: #ffffff; border: 2px solid var(--c-pay); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }

    .pay-details-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 24px; }
    .pay-details-table th { background: #f8fafc; border: 1px solid #cbd5e1; padding: 10px; font-weight: 800; text-align: start; }
    .pay-details-table td { border: 1px solid #e2e8f0; padding: 10px; color: #334155; }

    .signatures-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 48px; text-align: center; }
    .sig-box h6 { margin: 0 0 40px 0; font-size: 0.85rem; color: var(--c-text-dark); font-weight: 800; }
    .sig-line { border-bottom: 1px solid #94a3b8; width: 80%; margin: 0 auto; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .pay-show-wrapper { max-width: 100% !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="pay-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/payroll" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;">مسير رواتب: <?= htmlspecialchars($payroll->payroll_code) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-pay-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($payroll->month) ?> / <?= $payroll->year ?></p>
            </div>
        </div>
        <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة كشف المسير</button>
    </div>

    <div class="card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-pay); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-receipt" style="font-size:3.5rem; color:var(--c-pay);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;">كشف مسير الرواتب والمستحقات الشهرية الرسمية</span>
                </div>
            </div>
            <div style="text-align:end;">
                <span style="background:var(--c-pay); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;">مسير معتمد</span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-pay-dark); font-size:1.1rem;"># <?= htmlspecialchars($payroll->payroll_code) ?></div>
            </div>
        </div>

        <!-- جدول تفاصيل الموظفين والمسير -->
        <table class="pay-details-table">
            <thead>
                <tr>
                    <th style="width: 5%;">م</th>
                    <th style="width: 25%;">اسم الموظف والكود</th>
                    <th style="width: 20%;">الإدارة</th>
                    <th style="width: 15%;">الأساسي</th>
                    <th style="width: 15%;">البدلات</th>
                    <th style="width: 20%;">صافي المستحق (Net)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($employeeDetails)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:20px;">لا توجد تفاصيل موظفين لهذا المسير.</td></tr>
                <?php else: foreach($employeeDetails as $idx => $emp): 
                    $empAllowances = (float)$emp->housing + (float)$emp->transport;
                    $empNet = (float)$emp->basic_salary + $empAllowances;
                ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td>
                            <div style="font-weight:bold; color:#0f172a;"><?= htmlspecialchars($emp->name_ar) ?></div>
                            <div style="font-size:0.75rem; font-family:monospace; color:#64748b;"><?= htmlspecialchars($emp->emp_code) ?></div>
                        </td>
                        <td><?= htmlspecialchars($emp->dept_name ?: 'عام') ?></td>
                        <td style="font-family:monospace; font-weight:bold;"><?= number_format((float)$emp->basic_salary, 2) ?></td>
                        <td style="font-family:monospace; color:#0284c7;"><?= number_format($empAllowances, 2) ?></td>
                        <td style="font-family:monospace; font-weight:900; color:#059669;"><?= number_format($empNet, 2) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
                <tr style="background:#fff1f2; font-weight:900;">
                    <td colspan="3" style="text-align:end; padding:12px;">إجمالي المسير العام:</td>
                    <td style="font-family:monospace;"><?= number_format((float)$payroll->total_basic, 2) ?></td>
                    <td style="font-family:monospace; color:#0284c7;"><?= number_format((float)$payroll->total_allowances, 2) ?></td>
                    <td style="font-family:monospace; color:#059669; font-size:1.1rem;"><?= number_format((float)$payroll->net_pay, 2) ?> SAR/EGP</td>
                </tr>
            </tfoot>
        </table>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6>إعداد أخصائي الأجور</h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6>مراجعة مدير الموارد البشرية</h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6>اعتماد المدير المالي</h6>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>
</div>