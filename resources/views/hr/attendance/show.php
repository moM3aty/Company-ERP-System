<?php
// Path: resources/views/hr/attendance/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$statusMap = [
    'present' => ['label' => 'حاضر (Present)', 'color' => '#059669', 'bg' => '#ecfdf5'],
    'late' => ['label' => 'متأخر (Late)', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'half_day' => ['label' => 'نصف يوم (Half Day)', 'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
    'absent' => ['label' => 'غائب (Absent)', 'color' => '#dc2626', 'bg' => '#fef2f2'],
    'on_leave' => ['label' => 'إجازة رسمية (On Leave)', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
];
$st = $statusMap[$attendance->status] ?? $statusMap['present'];
?>

<style>
    :root { --c-att: #0284c7; --c-att-dark: #0369a1; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .att-show-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-att-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .card-box { background: #ffffff; border: 2px solid var(--c-att); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }
    
    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 160px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .att-show-wrapper { max-width: 100% !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="att-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/attendance" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;">تقرير سجل بصمة يومي</h2>
                <p style="margin:4px 0 0 0; color:var(--c-att-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($attendance->date) ?></p>
            </div>
        </div>
        <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة السجل</button>
    </div>

    <div class="card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-att); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-fingerprint" style="font-size:3.5rem; color:var(--c-att);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;">إدارة الحضور والانصراف (Time Tracking)</span>
                </div>
            </div>
            <div style="text-align:end;">
                <span style="background:var(--c-att); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;">بصمة موثقة</span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-att-dark); font-size:1.1rem;"># <?= htmlspecialchars($attendance->emp_code) ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label">اسم الموظف:</span>
                <span class="info-val"><?= htmlspecialchars($attendance->employee_name) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">التاريخ واليوم:</span>
                <span class="info-val" style="font-family:monospace;"><?= htmlspecialchars($attendance->date) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">وقت الحضور (الدخول):</span>
                <span class="info-val" style="font-family:monospace; color:#059669;"><?= $attendance->check_in ? date('h:i A', strtotime($attendance->check_in)) : '---' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">وقت الانصراف (الخروج):</span>
                <span class="info-val" style="font-family:monospace; color:#dc2626;"><?= $attendance->check_out ? date('h:i A', strtotime($attendance->check_out)) : '---' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">إجمالي ساعات العمل:</span>
                <span class="info-val" style="font-family:monospace; color:var(--c-text-dark); font-size:1.1rem;"><?= number_format((float)$attendance->work_hours, 2) ?> Hrs</span>
            </div>
            <div class="info-row">
                <span class="info-label">حالة الدوام:</span>
                <span class="info-val"><span style="padding:2px 10px; border-radius:4px; font-size:0.85rem; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;"><?= $st['label'] ?></span></span>
            </div>
        </div>

        <?php if(!empty($attendance->notes)): ?>
            <div style="margin-top:24px; padding:16px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0;">
                <h5 style="margin:0 0 6px 0; font-size:0.85rem; color:var(--c-text-muted); font-weight:800;">ملاحظات السجل:</h5>
                <p style="margin:0; font-size:0.9rem; color:#334155; font-weight:700;"><?= htmlspecialchars($attendance->notes) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>