<?php
// Path: resources/views/hr/shifts/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$statusMap = [
    'active' => ['label' => 'وردية مفعلة', 'color' => '#16a34a', 'bg' => '#dcfce7'],
    'inactive' => ['label' => 'وردية متوقفة', 'color' => '#64748b', 'bg' => '#f1f5f9'],
];
$st = $statusMap[$shift->status] ?? $statusMap['active'];
?>

<style>
    :root { --c-shift: #16a34a; --c-shift-dark: #15803d; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .shift-show-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-shift-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .card-box { background: #ffffff; border: 2px solid var(--c-shift); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }
    
    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 160px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .shift-show-wrapper { max-width: 100% !important; }
        .card-box { border: 2px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="shift-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/shifts" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($shift->name_ar) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-shift-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($shift->code) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة الوردية</button>
            <a href="/ERP/hr/shifts/<?= $shift->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="تعديل"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <div class="card-box">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px double var(--c-shift); padding-bottom:20px; margin-bottom:24px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-clock-user" style="font-size:3.5rem; color:var(--c-shift);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;">بطاقة واعتماد مواعيد الوردية</span>
                </div>
            </div>
            <div style="text-align:end;">
                <span style="background:var(--c-shift); color:#fff; padding:6px 16px; border-radius:6px; font-weight:900; font-size:0.9rem;">وقت معتمد</span>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-shift-dark); font-size:1.1rem;"># <?= htmlspecialchars($shift->code) ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label">اسم الوردية:</span>
                <span class="info-val"><?= htmlspecialchars($shift->name_ar) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">وقت بداية الدوام:</span>
                <span class="info-val" style="font-family:monospace; color:#059669;"><?= date('h:i A', strtotime($shift->start_time)) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">وقت نهاية الدوام:</span>
                <span class="info-val" style="font-family:monospace; color:#dc2626;"><?= date('h:i A', strtotime($shift->end_time)) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">فترة السماح (Grace Period):</span>
                <span class="info-val" style="font-family:monospace; color:#d97706;"><?= $shift->grace_period_mins ?> دقيقة</span>
            </div>
            <div class="info-row">
                <span class="info-label">حالة التفعيل:</span>
                <span class="info-val"><span style="padding:2px 10px; border-radius:4px; font-size:0.85rem; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;"><?= $st['label'] ?></span></span>
            </div>
        </div>
    </div>
</div>