<?php
// Path: resources/views/projects/milestones/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();

$statusMap = [
    'pending' => ['label' => $isAr ? 'قيد الانتظار' : 'Pending', 'color' => '#64748b', 'bg' => '#f1f5f9'],
    'in_progress' => ['label' => $isAr ? 'قيد التنفيذ' : 'In Progress', 'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
    'under_review' => ['label' => $isAr ? 'قيد المراجعة' : 'Under Review', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
    'completed' => ['label' => $isAr ? 'مكتملة ومستلمة' : 'Completed', 'color' => '#059669', 'bg' => '#ecfdf5'],
    'delayed' => ['label' => $isAr ? 'متأخرة عن الموعد' : 'Delayed', 'color' => '#dc2626', 'bg' => '#fef2f2'],
    'cancelled' => ['label' => $isAr ? 'ملغاة' : 'Cancelled', 'color' => '#94a3b8', 'bg' => '#f8fafc'],
];
$st = $statusMap[$milestone->status] ?? $statusMap['pending'];
$pct = (float)($milestone->progress_percent ?? 0);
$mTitle = $isAr ? ($milestone->title_ar ?: $milestone->title_en) : ($milestone->title_en ?: $milestone->title_ar);

$t = [
    'ar' => [
        'print' => 'طباعة كشف المرحلة', 'edit' => 'تعديل', 'back' => 'العودة',
        'code' => 'كود المرحلة', 'project' => 'المشروع المربوط', 'assigned' => 'المهندس / المسؤول', 'status' => 'حالة المهمة',
        'prog' => 'نسبة الإنجاز التنفيذي للمرحلة',
        'sec_time' => 'التواريخ والتكاليف المالية',
        'start' => 'تاريخ البدء', 'due' => 'تاريخ الاستحقاق', 'est' => 'التكلفة التقديرية', 'act' => 'التكلفة الفعلية',
        'desc' => 'الوصف والشروط الفنية'
    ],
    'en' => [
        'print' => 'Print Milestone Card', 'edit' => 'Edit', 'back' => 'Back',
        'code' => 'Milestone Code', 'project' => 'Linked Project', 'assigned' => 'Assignee / Engineer', 'status' => 'Task Status',
        'prog' => 'Execution Progress Level',
        'sec_time' => 'Timeline & Financial Costs',
        'start' => 'Start Date', 'due' => 'Due Date', 'est' => 'Estimated Cost', 'act' => 'Actual Cost',
        'desc' => 'Scope & Technical Specs'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-ms: #8b5cf6; --c-ms-dark: #7c3aed; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .ms-show-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition: 0.2s;}
    .btn-action:hover { background: #f5f3ff; color: var(--c-ms); border-color: #ddd6fe; }
    .btn-print { background: var(--c-ms-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25); transition: 0.2s;}
    .btn-print:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(139, 92, 246, 0.35); }
    
    .card-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; border-top: 6px solid var(--c-ms); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 24px; }
    
    .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
    @media(max-width:768px){ .grid-4 { grid-template-columns: repeat(2, 1fr); } }

    .info-item { background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; border-radius: 12px; }
    .info-item h5 { margin: 0 0 8px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .info-item p { margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--c-text-dark); }

    .progress-bar-big { background: #e2e8f0; border-radius: 10px; height: 16px; width: 100%; overflow: hidden; margin-top: 10px; }
    .progress-fill-big { background: var(--c-ms); height: 100%; border-radius: 10px; transition: width 0.5s ease;}

    @media print {
        .nt-sidebar, .top-header, .header-bar, .table-pagination-nav, header, aside { display: none !important; }
        body { background: #fff !important; }
        .ms-show-wrapper { max-width: 100% !important; padding: 0 !important; }
        .card-box { border: 1px solid #000 !important; box-shadow: none !important; page-break-inside: avoid; }
        .info-item { border: 1px solid #000 !important; background: transparent !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="ms-show-wrapper" dir="<?= $isAr ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/milestones" class="btn-action" title="<?= $t['back'] ?>"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($mTitle) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-ms-dark); font-weight:900; font-family:monospace; font-size:1.1rem;"><?= htmlspecialchars($milestone->milestone_code) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
            <a href="/ERP/projects/milestones/<?= $milestone->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="<?= $t['edit'] ?>"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <div class="card-box">
        <div class="grid-4">
            <div class="info-item">
                <h5><?= $t['code'] ?></h5>
                <p style="font-family:monospace; color:var(--c-ms-dark);"><?= htmlspecialchars($milestone->milestone_code) ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['project'] ?></h5>
                <p style="font-size: 1rem;"><i class="ph-fill ph-buildings" style="color:#94a3b8;"></i> <?= htmlspecialchars($milestone->project_name ?? '---') ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['assigned'] ?></h5>
                <p style="font-size: 1rem;"><i class="ph-fill ph-user" style="color:#94a3b8;"></i> <?= htmlspecialchars($milestone->assigned_to ?: '---') ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['status'] ?></h5>
                <p><span style="padding:4px 12px; border-radius:6px; font-size:0.85rem; font-weight:800; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;"><?= $st['label'] ?></span></p>
            </div>
        </div>

        <div style="margin-top:32px; padding-top:24px; border-top:1px dashed #cbd5e1;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h5 style="margin:0; color:var(--c-text-dark); font-weight:800; font-size:1rem;"><?= $t['prog'] ?></h5>
                <span style="font-family:monospace; font-weight:900; color:var(--c-ms-dark); font-size:1.4rem;"><?= $pct ?>%</span>
            </div>
            <div class="progress-bar-big"><div class="progress-fill-big" style="width:<?= $pct ?>%; background:<?= $pct >= 100 ? '#059669' : 'var(--c-ms)' ?>;"></div></div>
        </div>
    </div>

    <div class="card-box" style="border-top-color:#e2e8f0;">
        <h3 style="margin:0 0 24px 0; font-size:1.2rem; font-weight:800; color:var(--c-text-dark); border-bottom:1px solid #f1f5f9; padding-bottom:14px; display:flex; align-items:center; gap:8px;">
            <i class="ph-bold ph-calendar" style="color:var(--c-ms);"></i> <?= $t['sec_time'] ?>
        </h3>
        <div class="grid-4">
            <div class="info-item" style="background:#fff; border-color:#e2e8f0;">
                <h5><?= $t['start'] ?></h5>
                <p style="font-family:monospace; color:#334155;"><i class="ph-bold ph-calendar-blank"></i> <?= htmlspecialchars($milestone->start_date) ?></p>
            </div>
            <div class="info-item" style="background:#fff; border-color:#e2e8f0;">
                <h5><?= $t['due'] ?></h5>
                <p style="font-family:monospace; color:#0284c7;"><i class="ph-bold ph-calendar-check"></i> <?= htmlspecialchars($milestone->due_date) ?></p>
            </div>
            <div class="info-item" style="background:#fff; border-color:#e2e8f0;">
                <h5><?= $t['est'] ?></h5>
                <p style="font-family:monospace; color:#0f172a;"><?= number_format((float)$milestone->estimated_cost, 2) ?> <span style="font-size:0.7rem; color:#64748b;"><?= $currency ?></span></p>
            </div>
            <div class="info-item" style="background:#fff; border-color:#e2e8f0;">
                <h5><?= $t['act'] ?></h5>
                <p style="font-family:monospace; color:#dc2626;"><?= number_format((float)$milestone->actual_cost, 2) ?> <span style="font-size:0.7rem; color:#64748b;"><?= $currency ?></span></p>
            </div>
        </div>

        <?php if(!empty($milestone->description)): ?>
            <div style="margin-top:24px; padding-top:20px; border-top:1px solid #f1f5f9;">
                <h5 style="margin:0 0 8px 0; font-size:0.85rem; color:var(--c-text-muted); font-weight:800;"><?= $t['desc'] ?></h5>
                <p style="margin:0; font-weight:600; color:#1e293b; line-height:1.7; background:#f8fafc; padding:16px; border-radius:10px; border:1px dashed #cbd5e1;"><?= nl2br(htmlspecialchars($milestone->description)) ?></p>
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