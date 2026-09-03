<?php
// Path: resources/views/projects/list/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();

$statusMap = [
    'planning' => ['label' => $isAr ? 'تخطيط وتجهيز' : 'Planning', 'color' => '#6366f1', 'bg' => '#e0e7ff'],
    'in_progress' => ['label' => $isAr ? 'قيد التنفيذ' : 'In Progress', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
    'on_hold' => ['label' => $isAr ? 'موقف مؤقتاً' : 'On Hold', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'completed' => ['label' => $isAr ? 'مكتمل بنجاح' : 'Completed', 'color' => '#059669', 'bg' => '#ecfdf5'],
    'cancelled' => ['label' => $isAr ? 'ملغى' : 'Cancelled', 'color' => '#dc2626', 'bg' => '#fef2f2'],
];
$st = $statusMap[$project->status] ?? $statusMap['in_progress'];
$pct = (float)($project->progress_percent ?? 0);
$pName = $isAr ? ($project->name_ar ?: $project->name_en) : ($project->name_en ?: $project->name_ar);

$t = [
    'ar' => [
        'print' => 'طباعة بطاقة المشروع', 'edit' => 'تعديل', 'back' => 'العودة',
        'code' => 'كود المشروع', 'client' => 'العميل المالك', 'val' => 'قيمة العقد المبرم', 'status' => 'حالة المشروع',
        'prog' => 'مستوى ونسبة الإنجاز الميداني',
        'sec_time' => 'الجدول الزمني والنطاق المالي',
        'start' => 'تاريخ البداية', 'end' => 'تاريخ التسليم المتوقع', 'budget' => 'الميزانية المخصصة', 'spent' => 'المصروفات الفعلية',
        'desc' => 'الوصف ونطاق العمل'
    ],
    'en' => [
        'print' => 'Print Project Card', 'edit' => 'Edit', 'back' => 'Back',
        'code' => 'Project Code', 'client' => 'Client / Owner', 'val' => 'Contract Value', 'status' => 'Project Status',
        'prog' => 'Field Progress Completion',
        'sec_time' => 'Timeline & Financial Scope',
        'start' => 'Start Date', 'end' => 'Expected End Date', 'budget' => 'Allocated Budget', 'spent' => 'Actual Spent Costs',
        'desc' => 'Scope & Description'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-prj: #0284c7; --c-prj-dark: #0369a1; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .prj-show-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition: 0.2s;}
    .btn-action:hover { background: #e0f2fe; color: var(--c-prj); border-color: #bae6fd; }
    .btn-print { background: var(--c-prj-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25); transition: 0.2s;}
    .btn-print:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35); }
    
    .card-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; border-top: 6px solid var(--c-prj); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 24px; }
    
    .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
    @media(max-width:768px){ .grid-4 { grid-template-columns: repeat(2, 1fr); } }

    .info-item { background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; border-radius: 12px; }
    .info-item h5 { margin: 0 0 8px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .info-item p { margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--c-text-dark); }

    .progress-bar-big { background: #e2e8f0; border-radius: 10px; height: 16px; width: 100%; overflow: hidden; margin-top: 10px; }
    .progress-fill-big { background: var(--c-prj); height: 100%; border-radius: 10px; transition: width 0.5s ease;}

    @media print {
        .nt-sidebar, .top-header, .header-bar, .table-pagination-nav, header, aside { display: none !important; }
        body { background: #fff !important; }
        .prj-show-wrapper { max-width: 100% !important; padding: 0 !important; }
        .card-box { border: 1px solid #000 !important; box-shadow: none !important; page-break-inside: avoid; }
        .info-item { border: 1px solid #000 !important; background: transparent !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="prj-show-wrapper" dir="<?= $isAr ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/list" class="btn-action" title="<?= $t['back'] ?>"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.8rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($pName) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-prj-dark); font-weight:900; font-family:monospace; font-size:1.1rem;"><?= htmlspecialchars($project->code) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
            <a href="/ERP/projects/list/<?= $project->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="<?= $t['edit'] ?>"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <div class="card-box">
        <div class="grid-4">
            <div class="info-item">
                <h5><?= $t['code'] ?></h5>
                <p style="font-family:monospace; color:var(--c-prj-dark);"><?= htmlspecialchars($project->code) ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['client'] ?></h5>
                <p style="font-size: 1rem;"><i class="ph-fill ph-user" style="color:#94a3b8;"></i> <?= htmlspecialchars($project->customer_name ?? '---') ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['val'] ?></h5>
                <p style="font-family:monospace; color:#059669; font-size:1.35rem;"><?= number_format((float)$project->contract_value, 2) ?> <span style="font-size:0.8rem; color:#64748b;"><?= $currency ?></span></p>
            </div>
            <div class="info-item">
                <h5><?= $t['status'] ?></h5>
                <p><span style="padding:4px 12px; border-radius:6px; font-size:0.85rem; font-weight:800; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;"><?= $st['label'] ?></span></p>
            </div>
        </div>

        <div style="margin-top:32px; padding-top:24px; border-top:1px dashed #cbd5e1;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h5 style="margin:0; color:var(--c-text-dark); font-weight:800; font-size:1rem;"><?= $t['prog'] ?></h5>
                <span style="font-family:monospace; font-weight:900; color:var(--c-prj-dark); font-size:1.4rem;"><?= $pct ?>%</span>
            </div>
            <div class="progress-bar-big"><div class="progress-fill-big" style="width:<?= $pct ?>%; background:<?= $pct >= 100 ? '#059669' : 'var(--c-prj)' ?>;"></div></div>
        </div>
    </div>

    <div class="card-box" style="border-top-color:#e2e8f0;">
        <h3 style="margin:0 0 24px 0; font-size:1.2rem; font-weight:800; color:var(--c-text-dark); border-bottom:1px solid #f1f5f9; padding-bottom:14px; display:flex; align-items:center; gap:8px;">
            <i class="ph-bold ph-calendar" style="color:var(--c-prj);"></i> <?= $t['sec_time'] ?>
        </h3>
        <div class="grid-4">
            <div class="info-item" style="background:#fff; border-color:#e2e8f0;">
                <h5><?= $t['start'] ?></h5>
                <p style="font-family:monospace; color:#334155;"><i class="ph-bold ph-calendar-blank"></i> <?= htmlspecialchars($project->start_date) ?></p>
            </div>
            <div class="info-item" style="background:#fff; border-color:#e2e8f0;">
                <h5><?= $t['end'] ?></h5>
                <p style="font-family:monospace; color:#dc2626;"><i class="ph-bold ph-calendar-check"></i> <?= htmlspecialchars($project->end_date ?: '---') ?></p>
            </div>
            <div class="info-item" style="background:#fff; border-color:#e2e8f0;">
                <h5><?= $t['budget'] ?></h5>
                <p style="font-family:monospace; color:#0f172a;"><?= number_format((float)$project->estimated_budget, 2) ?> <span style="font-size:0.7rem; color:#64748b;"><?= $currency ?></span></p>
            </div>
            <div class="info-item" style="background:#fff; border-color:#e2e8f0;">
                <h5><?= $t['spent'] ?></h5>
                <p style="font-family:monospace; color:#ea580c;"><?= number_format((float)$project->spent_amount, 2) ?> <span style="font-size:0.7rem; color:#64748b;"><?= $currency ?></span></p>
            </div>
        </div>

        <?php if(!empty($project->description)): ?>
            <div style="margin-top:24px; padding-top:20px; border-top:1px solid #f1f5f9;">
                <h5 style="margin:0 0 8px 0; font-size:0.85rem; color:var(--c-text-muted); font-weight:800;"><?= $t['desc'] ?></h5>
                <p style="margin:0; font-weight:600; color:#1e293b; line-height:1.7; background:#f8fafc; padding:16px; border-radius:10px; border:1px dashed #cbd5e1;"><?= nl2br(htmlspecialchars($project->description)) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function purgeControls() {
    const selectors = ['.table-pagination-nav', '.dataTables_info', '.dataTables_paginate', '.pagination'];
    selectors.forEach(s => {
        document.querySelectorAll(s).forEach(el => el.remove());
    });
}

function safePrint() {
    purgeControls();
    window.print();
}

document.addEventListener("DOMContentLoaded", purgeControls);
window.addEventListener("beforeprint", purgeControls);
</script>