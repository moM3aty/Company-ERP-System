<?php
// Path: resources/views/projects/costs/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$categoryMap = [
    'materials' => 'مواد وتوريدات إشائية (Materials)',
    'labor' => 'عمالة وأجور ومستحقات (Labor)',
    'equipment' => 'إيجار وصيانة معدات (Equipment)',
    'subcontractor' => 'مستحقات مقاولين فرعيين (Subcontractor)',
    'overhead' => 'مصروفات إدارية وموقع (Overhead)',
    'other' => 'نفقات ومصروفات أخرى (Other)',
];

$statusMap = [
    'paid' => ['label' => 'مسدد بالكامل', 'color' => '#059669', 'bg' => '#ecfdf5'],
    'partially_paid' => ['label' => 'مسدد جزئياً', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'unpaid' => ['label' => 'غير مسدد', 'color' => '#dc2626', 'bg' => '#fef2f2'],
];
$st = $statusMap[$cost->payment_status] ?? $statusMap['paid'];
?>

<style>
    :root { --c-pcost: #ea580c; --c-pcost-dark: #c2410c; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .pcost-show-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-pcost-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .voucher-card { background: #ffffff; border: 2px solid var(--c-pcost); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }
    .voucher-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px double var(--c-pcost); padding-bottom: 20px; margin-bottom: 24px; }
    
    .voucher-title-badge { background: var(--c-pcost); color: #fff; padding: 6px 20px; border-radius: 8px; font-size: 1.1rem; font-weight: 900; letter-spacing: 1px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 160px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    .amount-box { background: #ffedd5; border: 2px solid #ea580c; border-radius: 12px; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; margin: 24px 0; }
    .amount-val { font-size: 2rem; font-weight: 900; color: #c2410c; font-family: monospace; }

    .signatures-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 44px; text-align: center; }
    .sig-box h6 { margin: 0 0 40px 0; font-size: 0.85rem; color: var(--c-text-muted); font-weight: 800; }
    .sig-line { border-bottom: 1px solid #94a3b8; width: 80%; margin: 0 auto; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .pcost-show-wrapper { max-width: 100% !important; }
        .voucher-card { border: 2px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="pcost-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/costs" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;">سند مصروف رقم: <?= htmlspecialchars($cost->voucher_number) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;">تاريخ الصرف: <?= htmlspecialchars($cost->cost_date) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة السند الرسمية</button>
            <a href="/ERP/projects/costs/<?= $cost->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="تعديل"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <div class="voucher-card">
        <div class="voucher-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-buildings" style="font-size:3rem; color:var(--c-pcost);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;">إدارة تكاليف ومصروفات المواقع الإنشائية</span>
                </div>
            </div>
            <div style="text-align:end;">
                <div class="voucher-title-badge">سند مصروفات موقع (SITE EXPENSE VOUCHER)</div>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-pcost-dark); font-size:1.1rem;"># <?= htmlspecialchars($cost->voucher_number) ?></div>
            </div>
        </div>

        <div class="amount-box">
            <div>
                <span style="font-size:0.8rem; font-weight:800; color:#c2410c; text-transform:uppercase;">المبلغ المصروف / Amount</span>
                <div style="font-size:0.9rem; font-weight:700; color:#9a3412; margin-top:2px;">حالة السداد: <span style="color:<?= $st['color'] ?>; font-weight:900;"><?= $st['label'] ?></span></div>
            </div>
            <div class="amount-val"><?= number_format((float)$cost->amount, 2) ?> <span style="font-size:1rem;">SAR/EGP</span></div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label">المشروع / الموقع:</span>
                <span class="info-val"><?= htmlspecialchars($cost->project_name ?? 'غير محدد') ?> (<?= htmlspecialchars($cost->project_code ?? '') ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label">تبويب التكلفة:</span>
                <span class="info-val"><?= $categoryMap[$cost->cost_category] ?? 'مصروفات موقع' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">المورد / الجهة المستلمة:</span>
                <span class="info-val"><?= htmlspecialchars($cost->supplier_name ?: ($cost->account_name ?? 'صندوق موقع مباشر')) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">الرقم المرجعي / الفاتورة:</span>
                <span class="info-val" style="font-family:monospace;"><?= htmlspecialchars($cost->reference_no ?: '---') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">البيان والتفاصيل:</span>
                <span class="info-val"><?= htmlspecialchars($cost->description ?: 'صرف واستحقاق نفقات موقع المشروع') ?></span>
            </div>
        </div>

        <?php if(!empty($cost->notes)): ?>
            <div style="margin-top:20px; padding-top:16px; border-top:1px solid #f1f5f9;">
                <p style="margin:0; font-size:0.85rem; color:#64748b;"><b>ملاحظات إضافية:</b> <?= htmlspecialchars($cost->notes) ?></p>
            </div>
        <?php endif; ?>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6>مهندس الموقع / المستلم</h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6>المحاسب المسؤول</h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6>اعتماد مدير المشروع</h6>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>
</div>