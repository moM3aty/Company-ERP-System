<?php
// Path: resources/views/hr/leaves/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$statusMap = [
    'pending' => ['label' => 'قيد المراجعة والانتظار', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'approved' => ['label' => 'مقبولة ومصادق عليها', 'color' => '#059669', 'bg' => '#ecfdf5'],
    'rejected' => ['label' => 'طلب مرفوض', 'color' => '#dc2626', 'bg' => '#fef2f2'],
];
$st = $statusMap[$leave->status] ?? $statusMap['pending'];

$typeMap = [
    'annual' => 'إجازة سنوية',
    'sick' => 'إجازة مرضية',
    'unpaid' => 'بدون راتب',
    'maternity' => 'إجازة وضع/أمومة',
    'other' => 'إجازة أخرى'
];
?>

<style>
    :root { --c-leave: #7c3aed; --c-leave-dark: #6d28d9; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .leave-show-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-leave-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .card-box { background: #ffffff; border: 2px solid var(--c-leave); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 160px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    .signatures-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 40px; margin-top: 48px; text-align: center; }
    .sig-box h6 { margin: 0 0 40px 0; font-size: 0.9rem; color: var(--c-text-dark); font-weight: 800; }
    .sig-line { border-bottom: 1px solid #94a3b8; width: 80%; margin: 0 auto; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .leave-show-wrapper { max-width: 100% !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="leave-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/leaves" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;">نموذج طلب إجازة</h2>
                <p style="margin:4px 0 0 0; color:var(--c-leave-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($leave->employee_name) ?></p>
            </div>
        </div>
        <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة النموذج</button>
    </div>

    <div class="card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-leave); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-airplane-takeoff" style="font-size:3.5rem; color:var(--c-leave);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;">إدارة الموارد البشرية - شؤون الموظفين</span>
                </div>
            </div>
            <div style="text-align:end;">
                <span style="background:var(--c-leave); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;">طلب إجازة رسمي</span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-leave-dark); font-size:1.1rem;"># LVE-<?= htmlspecialchars($leave->id) ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label">اسم الموظف صاحب الطلب:</span>
                <span class="info-val"><?= htmlspecialchars($leave->employee_name) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">كود الموظف / الإدارة:</span>
                <span class="info-val" style="font-family:monospace;"><?= htmlspecialchars($leave->emp_code) ?> | <?= htmlspecialchars($leave->dept_name ?: '---') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">نوع الإجازة:</span>
                <span class="info-val"><?= $typeMap[$leave->leave_type] ?? $leave->leave_type ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">تاريخ بدء الإجازة:</span>
                <span class="info-val" style="font-family:monospace; color:#059669;"><?= htmlspecialchars($leave->start_date) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">تاريخ النهاية والمباشرة:</span>
                <span class="info-val" style="font-family:monospace; color:#dc2626;"><?= htmlspecialchars($leave->end_date) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">إجمالي عدد أيام الإجازة:</span>
                <span class="info-val" style="font-family:monospace; color:var(--c-leave-dark); font-size:1.1rem;"><?= (int)$leave->days_count ?> يوم</span>
            </div>
            <div class="info-row">
                <span class="info-label">حالة الطلب:</span>
                <span class="info-val"><span style="padding:2px 10px; border-radius:4px; font-size:0.85rem; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;"><?= $st['label'] ?></span></span>
            </div>
        </div>

        <?php if(!empty($leave->reason)): ?>
            <div style="margin-top:24px; padding:16px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0;">
                <h5 style="margin:0 0 6px 0; font-size:0.85rem; color:var(--c-text-muted); font-weight:800;">سبب الإجازة:</h5>
                <p style="margin:0; font-size:0.9rem; color:#334155; font-weight:700; white-space:pre-line;"><?= htmlspecialchars($leave->reason) ?></p>
            </div>
        <?php endif; ?>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6>توقيع الموظف</h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6>اعتماد مدير الموارد البشرية</h6>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>
</div>