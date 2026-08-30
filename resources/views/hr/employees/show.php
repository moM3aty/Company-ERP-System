<?php
// Path: resources/views/hr/employees/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$statusMap = [
    'active' => ['label' => 'على رأس العمل', 'color' => '#2563eb', 'bg' => '#dbeafe'],
    'on_leave' => ['label' => 'في إجازة رسمية', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'resigned' => ['label' => 'مستقيل', 'color' => '#64748b', 'bg' => '#f1f5f9'],
    'terminated' => ['label' => 'منهي خدماته', 'color' => '#dc2626', 'bg' => '#fef2f2'],
];
$st = $statusMap[$employee->status] ?? $statusMap['active'];
?>

<style>
    :root { --c-emp: #2563eb; --c-emp-dark: #1d4ed8; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .emp-show-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-emp-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .card-box { background: #ffffff; border: 2px solid var(--c-emp); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 160px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .emp-show-wrapper { max-width: 100% !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="emp-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/employees" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($employee->name_ar) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-emp-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($employee->emp_code) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة البطاقة التعريفية</button>
            <a href="/ERP/hr/employees/<?= $employee->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="تعديل"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <div class="card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-emp); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-user-circle" style="font-size:3.5rem; color:var(--c-emp);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;">بطاقة موظف رسمي وسجل وظيفي</span>
                </div>
            </div>
            <div style="text-align:end;">
                <span style="background:var(--c-emp); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;">كادر رسمي</span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-emp-dark); font-size:1.1rem;"># <?= htmlspecialchars($employee->emp_code) ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label">الاسم بالكامل (بالعربية):</span>
                <span class="info-val"><?= htmlspecialchars($employee->name_ar) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">الاسم بالكامل (بالإنجليزية):</span>
                <span class="info-val" style="font-family:monospace;"><?= htmlspecialchars($employee->name_en ?: '---') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">الإدارة والمسمى الوظيفي:</span>
                <span class="info-val"><?= htmlspecialchars($employee->department_name_ar ?: 'غير محدد') ?> - <?= htmlspecialchars($employee->designation_title_ar ?: 'بدون مسمى') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">رقم الهوية الوطنية / الإقامة:</span>
                <span class="info-val" style="font-family:monospace;"><?= htmlspecialchars($employee->national_id ?: '---') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">الهاتف والبريد الإلكتروني:</span>
                <span class="info-val" style="font-family:monospace;"><?= htmlspecialchars($employee->phone ?: '---') ?> | <?= htmlspecialchars($employee->email ?: '---') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">تاريخ المباشرة:</span>
                <span class="info-val" style="font-family:monospace;"><?= htmlspecialchars($employee->joining_date) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">الراتب الأساسي الشهري:</span>
                <span class="info-val" style="font-family:monospace; color:#059669; font-size:1.1rem;"><?= number_format((float)$employee->basic_salary, 2) ?> SAR/EGP</span>
            </div>
            <div class="info-row">
                <span class="info-label">حالة الموظف:</span>
                <span class="info-val"><span style="padding:2px 10px; border-radius:4px; font-size:0.85rem; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;"><?= $st['label'] ?></span></span>
            </div>
        </div>

        <?php if(!empty($employee->address)): ?>
            <div style="margin-top:24px; padding:16px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0;">
                <h5 style="margin:0 0 6px 0; font-size:0.85rem; color:var(--c-text-muted); font-weight:800;">العنوان الإقامي:</h5>
                <p style="margin:0; font-size:0.9rem; color:#334155; font-weight:700;"><?= htmlspecialchars($employee->address) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>