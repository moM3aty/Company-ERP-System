<?php
// Path: resources/views/projects/list/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$statusMap = [
    'planning' => ['label' => 'تخطيط وتجهيز', 'color' => '#6366f1', 'bg' => '#e0e7ff'],
    'in_progress' => ['label' => 'قيد التنفيذ', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
    'on_hold' => ['label' => 'موقف مؤقتاً', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'completed' => ['label' => 'مكتمل بنجاح', 'color' => '#059669', 'bg' => '#ecfdf5'],
    'cancelled' => ['label' => 'ملغى', 'color' => '#dc2626', 'bg' => '#fef2f2'],
];
$st = $statusMap[$project->status] ?? $statusMap['in_progress'];
$pct = (float)($project->progress_percent ?? 0);
?>

<style>
    :root { --c-prj: #0284c7; --c-prj-dark: #0369a1; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .prj-show-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-prj-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .card-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; border-top: 4px solid var(--c-prj); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 24px; }
    
    .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    @media(max-width:768px){ .grid-4 { grid-template-columns: repeat(2, 1fr); } }

    .info-item h5 { margin: 0 0 6px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .info-item p { margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--c-text-dark); }

    .progress-bar-big { background: #e2e8f0; border-radius: 10px; height: 16px; width: 100%; overflow: hidden; margin-top: 8px; }
    .progress-fill-big { background: var(--c-prj); height: 100%; border-radius: 10px; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .prj-show-wrapper { max-width: 100% !important; }
        .card-box { border: 1px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="prj-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/list" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($project->name_ar) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-prj-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($project->code) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة بطاقة المشروع</button>
            <a href="/ERP/projects/list/<?= $project->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="تعديل"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <div class="card-box">
        <div class="grid-4">
            <div class="info-item">
                <h5>كود المشروع</h5>
                <p style="font-family:monospace; color:var(--c-prj-dark);"><?= htmlspecialchars($project->code) ?></p>
            </div>
            <div class="info-item">
                <h5>العميل المالك</h5>
                <p><?= htmlspecialchars($project->customer_name ?? 'غير محدد') ?></p>
            </div>
            <div class="info-item">
                <h5>قيمة العقد المبرم</h5>
                <p style="font-family:monospace; color:#059669; font-size:1.3rem;"><?= number_format((float)$project->contract_value, 2) ?></p>
            </div>
            <div class="info-item">
                <h5>حالة المشروع</h5>
                <p><span style="padding:4px 12px; border-radius:6px; font-size:0.8rem; font-weight:800; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;"><?= $st['label'] ?></span></p>
            </div>
        </div>

        <div style="margin-top:24px; padding-top:20px; border-top:1px dashed #e2e8f0;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h5 style="margin:0; color:var(--c-text-muted); font-weight:800;">مستوى ونسبة الإنجاز الميداني</h5>
                <span style="font-family:monospace; font-weight:900; color:var(--c-prj-dark); font-size:1.2rem;"><?= $pct ?>%</span>
            </div>
            <div class="progress-bar-big"><div class="progress-fill-big" style="width:<?= $pct ?>%; background:<?= $pct >= 100 ? '#059669' : 'var(--c-prj)' ?>;"></div></div>
        </div>
    </div>

    <div class="card-box">
        <h3 style="margin:0 0 20px 0; font-size:1.1rem; font-weight:800; color:var(--c-text-dark); border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
            <i class="ph-bold ph-calendar" style="color:var(--c-prj);"></i> الجدول الزمني والنطاق
        </h3>
        <div class="grid-4">
            <div class="info-item">
                <h5>تاريخ البداية</h5>
                <p style="font-family:monospace;"><?= htmlspecialchars($project->start_date) ?></p>
            </div>
            <div class="info-item">
                <h5>تاريخ التسليم المتوقع</h5>
                <p style="font-family:monospace;"><?= htmlspecialchars($project->end_date ?: 'غير محدد') ?></p>
            </div>
            <div class="info-item">
                <h5>الميزانية المخصصة</h5>
                <p style="font-family:monospace;"><?= number_format((float)$project->estimated_budget, 2) ?></p>
            </div>
            <div class="info-item">
                <h5>المصروفات الفعلية</h5>
                <p style="font-family:monospace; color:#dc2626;"><?= number_format((float)$project->spent_amount, 2) ?></p>
            </div>
        </div>

        <?php if(!empty($project->description)): ?>
            <div style="margin-top:20px; padding-top:16px; border-top:1px solid #f1f5f9;">
                <h5 style="margin:0 0 6px 0; font-size:0.8rem; color:var(--c-text-muted); font-weight:800;">الوصف ونطاق العمل</h5>
                <p style="margin:0; font-weight:600; color:#334155; line-height:1.6;"><?= htmlspecialchars($project->description) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>