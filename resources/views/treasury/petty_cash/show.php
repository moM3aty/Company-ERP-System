<?php
// Path: resources/views/treasury/petty_cash/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$pct = (float)$pettyCash->amount > 0 ? min(100, round(((float)$pettyCash->spent_amount / (float)$pettyCash->amount) * 100)) : 0;
?>

<style>
    :root { --c-pc: #d97706; --c-pc-dark: #b45309; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .pc-show-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-pc-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    /* Voucher Official Style */
    .voucher-card { background: #ffffff; border: 2px solid var(--c-pc); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }
    .voucher-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px double var(--c-pc); padding-bottom: 20px; margin-bottom: 24px; }
    
    .voucher-title-badge { background: var(--c-pc); color: #fff; padding: 6px 20px; border-radius: 8px; font-size: 1.1rem; font-weight: 900; letter-spacing: 1px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 150px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    .kpi-boxes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin: 24px 0; }
    .kpi-mini { background: #fef3c7; border: 1px solid #fde68a; padding: 14px; border-radius: 12px; text-align: center; }
    .kpi-mini h6 { margin: 0 0 4px 0; font-size: 0.75rem; color: #b45309; font-weight: 800; }
    .kpi-mini p { margin: 0; font-size: 1.4rem; font-weight: 900; font-family: monospace; }

    .settle-form-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

    .signatures-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 48px; text-align: center; }
    .sig-box h6 { margin: 0 0 40px 0; font-size: 0.85rem; color: var(--c-text-muted); font-weight: 800; }
    .sig-line { border-bottom: 1px solid #94a3b8; width: 80%; margin: 0 auto; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, .settle-form-card, header, aside { display: none !important; }
        body { background: #fff !important; }
        .pc-show-wrapper { max-width: 100% !important; }
        .voucher-card { border: 2px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="pc-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/petty-cash" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= __('بطاقة عُهدة:', 'Custody Card:') ?> <?= htmlspecialchars($pettyCash->employee_name) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-pc-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($pettyCash->code) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= __('طباعة إذن العُهدة', 'Print Voucher') ?></button>
            <?php if (has_permission('treasury_petty_cash_edit')): ?>
                <a href="/ERP/treasury/petty-cash/<?= $pettyCash->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="<?= __('تعديل', 'Edit') ?>"><i class="ph-bold ph-pencil"></i></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="voucher-card">
        <div class="voucher-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-buildings" style="font-size:3rem; color:var(--c-pc);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= __('إدارة الخزانة والمالية', 'Treasury & Finance Department') ?></span>
                </div>
            </div>
            <div style="text-align:<?= $isRtl ? 'left' : 'right' ?>;">
                <div class="voucher-title-badge"><?= __('إذن تسليم عُهدة (PETTY CASH)', 'PETTY CASH VOUCHER') ?></div>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-pc-dark); font-size:1.1rem;"># <?= htmlspecialchars($pettyCash->code) ?></div>
            </div>
        </div>

        <div class="kpi-boxes">
            <div class="kpi-mini">
                <h6><?= __('إجمالي ميزانية العُهدة', 'Total Budget') ?></h6>
                <p style="color:var(--c-pc-dark);"><?= number_format((float)$pettyCash->amount, 2) ?></p>
            </div>
            <div class="kpi-mini" style="background:#fef2f2; border-color:#fecdd3;">
                <h6 style="color:#dc2626;"><?= __('المنصرف والمصفى', 'Settled & Spent') ?></h6>
                <p style="color:#dc2626;"><?= number_format((float)$pettyCash->spent_amount, 2) ?></p>
            </div>
            <div class="kpi-mini" style="background:#f0fdf4; border-color:#a7f3d0;">
                <h6 style="color:#059669;"><?= __('المتبقي بالعهدة', 'Remaining Balance') ?></h6>
                <p style="color:#059669;"><?= number_format((float)$pettyCash->remaining_amount, 2) ?></p>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label"><?= __('الموظف المسؤول عن العُهدة:', 'Responsible Employee:') ?></span>
                <span class="info-val"><?= htmlspecialchars($pettyCash->employee_name) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= __('الحساب المصدر:', 'Source Account:') ?></span>
                <span class="info-val"><?= htmlspecialchars($pettyCash->account_name ?? __('الخزينة العامة', 'Main Safe')) ?> (<?= htmlspecialchars($pettyCash->account_code ?? '---') ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= __('تاريخ الاستلام:', 'Issue Date:') ?></span>
                <span class="info-val" style="font-family:monospace;"><?= htmlspecialchars($pettyCash->issue_date) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= __('البيان والغرض:', 'Description/Purpose:') ?></span>
                <span class="info-val"><?= htmlspecialchars($pettyCash->description ?: __('عُهدة نثريات ومصروفات نقدية', 'Petty cash for miscellaneous expenses')) ?></span>
            </div>
        </div>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6><?= __('المستلم / الموظف المسؤول', 'Received By') ?></h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= __('أمين الخزينة المسلم', 'Issued By (Cashier)') ?></h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= __('اعتماد الحسابات والمالية', 'Approved By (Finance)') ?></h6>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>

    <?php if((float)$pettyCash->remaining_amount > 0 && has_permission('treasury_petty_cash_settle')): ?>
        <div class="settle-form-card">
            <h3 style="margin:0 0 16px 0; font-size:1.1rem; font-weight:800; color:var(--c-text-dark); display:flex; align-items:center; gap:8px;">
                <i class="ph-bold ph-receipt" style="color:var(--c-pc);"></i> <?= __('تسجيل تصفية جديدة وإثبات منصرفات من العُهدة', 'Record New Settlement') ?>
            </h3>
            <form action="/ERP/treasury/petty-cash/<?= $pettyCash->id ?>/settle" method="POST" style="display:flex; gap:16px; align-items:flex-end; flex-wrap: wrap;">
                <div style="flex:1; min-width:200px;">
                    <label style="display:block; font-size:0.85rem; font-weight:800; color:#475569; margin-bottom:6px;"><?= __('مبلغ التصفية والمنصرف الجديد', 'New Settlement Amount') ?></label>
                    <input type="number" step="0.01" max="<?= (float)$pettyCash->remaining_amount ?>" name="settle_amount" class="form-control" style="width:100%; padding:10px 14px; border:1px solid #cbd5e1; border-radius:8px; font-family:monospace; font-weight:bold; font-size:1rem;" placeholder="0.00" required>
                </div>
                <button type="submit" style="background:var(--c-pc-dark); color:#fff; border:none; padding:12px 24px; border-radius:8px; font-weight:800; cursor:pointer;"><i class="ph-bold ph-check"></i> <?= __('خصم وتثبيت المنصرف', 'Deduct & Confirm Settlement') ?></button>
            </form>
        </div>
    <?php endif; ?>
</div>