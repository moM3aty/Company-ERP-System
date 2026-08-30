<?php
// Path: resources/views/hr/contracts/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$statusMap = [
    'active' => ['label' => 'عقد ساري المفعول', 'color' => '#059669', 'bg' => '#ecfdf5'],
    'expired' => ['label' => 'عقد منتهي الصلاحية', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'terminated' => ['label' => 'عقد مفسوخ', 'color' => '#dc2626', 'bg' => '#fef2f2'],
];
$st = $statusMap[$contract->status] ?? $statusMap['active'];

$basic = (float)$contract->basic_salary;
$housing = (float)$contract->housing_allowance;
$transport = (float)$contract->transport_allowance;
$total = $basic + $housing + $transport;
?>

<style>
    :root { --c-hcont: #d97706; --c-hcont-dark: #b45309; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .hcont-show-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-hcont-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .card-box { background: #ffffff; border: 2px solid var(--c-hcont); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 160px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    .salary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-top: 24px; }
    .sal-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-weight: 700; color: #475569; }
    .sal-total { display: flex; justify-content: space-between; font-weight: 900; color: var(--c-text-dark); font-size: 1.2rem; border-top: 2px dashed #cbd5e1; padding-top: 12px; margin-top: 12px; }

    .signatures-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 40px; margin-top: 48px; text-align: center; }
    .sig-box h6 { margin: 0 0 40px 0; font-size: 0.9rem; color: var(--c-text-dark); font-weight: 800; }
    .sig-line { border-bottom: 1px solid #94a3b8; width: 80%; margin: 0 auto; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .hcont-show-wrapper { max-width: 100% !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="hcont-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/contracts" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;">وثيقة العقد: <?= htmlspecialchars($contract->contract_code) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-hcont-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($contract->employee_name) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة وثيقة العقد</button>
            <a href="/ERP/hr/contracts/<?= $contract->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="تعديل"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <div class="card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-hcont); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-handshake" style="font-size:3.5rem; color:var(--c-hcont);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;">إدارة الموارد البشرية - قسم شؤون الموظفين</span>
                </div>
            </div>
            <div style="text-align:end;">
                <span style="background:var(--c-hcont); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;">عقد عمل رسمي (Employment Contract)</span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-hcont-dark); font-size:1.1rem;"># <?= htmlspecialchars($contract->contract_code) ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label">اسم الطرف الثاني (الموظف):</span>
                <span class="info-val"><?= htmlspecialchars($contract->employee_name) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">كود الموظف / رقم الهوية:</span>
                <span class="info-val" style="font-family:monospace;"><?= htmlspecialchars($contract->emp_code) ?> | <?= htmlspecialchars($contract->national_id ?: '---') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">المسمى / الإدارة:</span>
                <span class="info-val"><?= htmlspecialchars($contract->desig_name ?: '---') ?> - <?= htmlspecialchars($contract->dept_name ?: '---') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">تاريخ البداية (سريان العقد):</span>
                <span class="info-val" style="font-family:monospace; color:#059669;"><?= htmlspecialchars($contract->start_date) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">تاريخ نهاية العقد:</span>
                <span class="info-val" style="font-family:monospace; color:#dc2626;"><?= htmlspecialchars($contract->end_date ?: 'عقد غير محدد المدة') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">حالة العقد:</span>
                <span class="info-val"><span style="padding:2px 10px; border-radius:4px; font-size:0.85rem; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;"><?= $st['label'] ?></span></span>
            </div>
        </div>

        <div class="salary-box">
            <h5 style="margin:0 0 16px 0; font-size:1.1rem; color:var(--c-text-dark); font-weight:900;"><i class="ph-bold ph-coins" style="color:var(--c-hcont);"></i> البدلات والرواتب (Financial Package)</h5>
            <div class="sal-row">
                <span>الراتب الأساسي (Basic Salary):</span>
                <span style="font-family:monospace; color:#059669;"><?= number_format($basic, 2) ?></span>
            </div>
            <div class="sal-row">
                <span>بدل السكن (Housing Allowance):</span>
                <span style="font-family:monospace; color:#0284c7;"><?= number_format($housing, 2) ?></span>
            </div>
            <div class="sal-row">
                <span>بدل النقل والمواصلات (Transport):</span>
                <span style="font-family:monospace; color:#d97706;"><?= number_format($transport, 2) ?></span>
            </div>
            <div class="sal-total">
                <span>إجمالي الراتب (Total Gross Package):</span>
                <span style="font-family:monospace;"><?= number_format($total, 2) ?> SAR/EGP</span>
            </div>
        </div>

        <?php if(!empty($contract->notes)): ?>
            <div style="margin-top:24px; padding:16px; border-top:1px solid #f1f5f9;">
                <h5 style="margin:0 0 6px 0; font-size:0.85rem; color:var(--c-text-muted); font-weight:800;">شروط وملاحظات إضافية:</h5>
                <p style="margin:0; font-size:0.9rem; color:#334155; font-weight:700; white-space:pre-line;"><?= htmlspecialchars($contract->notes) ?></p>
            </div>
        <?php endif; ?>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6>الطرف الأول (الشركة/الإدارة)</h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6>الطرف الثاني (الموظف)</h6>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>
</div>