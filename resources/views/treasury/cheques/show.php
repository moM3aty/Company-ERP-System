<?php
// Path: resources/views/treasury/cheques/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$statusMap = [
    'pending' => ['label' => 'برسم التحصيل / معلق', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'collected' => ['label' => 'محصل / مقبول', 'color' => '#059669', 'bg' => '#ecfdf5'],
    'bounced' => ['label' => 'مرتد / مرفوض', 'color' => '#dc2626', 'bg' => '#fef2f2'],
    'cancelled' => ['label' => 'ملغى', 'color' => '#64748b', 'bg' => '#f1f5f9'],
];
$st = $statusMap[$cheque->status] ?? $statusMap['pending'];
?>

<style>
    :root { --c-chq: #c026d3; --c-chq-dark: #a21caf; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .cheque-show-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-chq-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    /* Voucher Official Style */
    .voucher-card { background: #ffffff; border: 2px solid var(--c-chq); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }
    .voucher-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px double var(--c-chq); padding-bottom: 20px; margin-bottom: 24px; }
    
    .voucher-title-badge { background: var(--c-chq); color: #fff; padding: 6px 20px; border-radius: 8px; font-size: 1.1rem; font-weight: 900; letter-spacing: 1px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 150px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    .amount-box { background: #fdf4ff; border: 2px solid #c026d3; border-radius: 12px; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; margin: 24px 0; }
    .amount-val { font-size: 2rem; font-weight: 900; color: #a21caf; font-family: monospace; }

    .status-action-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

    .signatures-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 48px; text-align: center; }
    .sig-box h6 { margin: 0 0 40px 0; font-size: 0.85rem; color: var(--c-text-muted); font-weight: 800; }
    .sig-line { border-bottom: 1px solid #94a3b8; width: 80%; margin: 0 auto; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, .status-action-card, header, aside { display: none !important; }
        body { background: #fff !important; }
        .cheque-show-wrapper { max-width: 100% !important; }
        .voucher-card { border: 2px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="cheque-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/cheques" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;">شيك رقم: <?= htmlspecialchars($cheque->cheque_number) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;">تاريخ الاستحقاق: <?= htmlspecialchars($cheque->due_date) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة كشف الشيك</button>
            <a href="/ERP/treasury/cheques/<?= $cheque->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="تعديل"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <div class="voucher-card">
        <div class="voucher-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-buildings" style="font-size:3rem; color:var(--c-chq);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;">إدارة الخزانة والمالية</span>
                </div>
            </div>
            <div style="text-align:end;">
                <div class="voucher-title-badge"><?= $cheque->type === 'received' ? 'شيك وارد (RECEIVED CHEQUE)' : 'شيك صادر (ISSUED CHEQUE)' ?></div>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-chq-dark); font-size:1.1rem;"># <?= htmlspecialchars($cheque->cheque_number) ?></div>
            </div>
        </div>

        <div class="amount-box">
            <div>
                <span style="font-size:0.8rem; font-weight:800; color:#a21caf; text-transform:uppercase;">مبلغ وقيمة الشيك / Cheque Amount</span>
                <div style="font-size:0.9rem; font-weight:700; color:#86198f; margin-top:2px;">حالة الورقة: <span style="color:<?= $st['color'] ?>; font-weight:900;"><?= $st['label'] ?></span></div>
            </div>
            <div class="amount-val"><?= number_format((float)$cheque->amount, 2) ?> <span style="font-size:1rem;">SAR/EGP</span></div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label">البنك المسحوب عليه:</span>
                <span class="info-val"><?= htmlspecialchars($cheque->bank_name) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">اسم المستفيد / الساحب:</span>
                <span class="info-val"><?= htmlspecialchars($cheque->payee_payer_name) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">الحساب / الخزينة المربوطة:</span>
                <span class="info-val"><?= htmlspecialchars($cheque->account_name ?? 'عام') ?> (<?= htmlspecialchars($cheque->account_code ?? '---') ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label">تاريخ التحرير والاستحقاق:</span>
                <span class="info-val">تحرير: <?= htmlspecialchars($cheque->issue_date) ?> | استحقاق: <?= htmlspecialchars($cheque->due_date) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">ملاحظات تفصيلية:</span>
                <span class="info-val"><?= htmlspecialchars($cheque->notes ?: 'لا توجد ملاحظات إضافية') ?></span>
            </div>
        </div>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6>المستلم / الساحب</h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6>أمين صندوق الشيكات</h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6>اعتماد الحسابات</h6>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>

    <div class="status-action-card">
        <h3 style="margin:0 0 16px 0; font-size:1.1rem; font-weight:800; color:var(--c-text-dark); display:flex; align-items:center; gap:8px;">
            <i class="ph-bold ph-sliders-horizontal" style="color:var(--c-chq);"></i> تحديث وتغيير حالة الشيك ورسم التحصيل
        </h3>
        <form action="/ERP/treasury/cheques/<?= $cheque->id ?>/status" method="POST" style="display:flex; gap:16px; align-items:flex-end;">
            <div style="flex:1;">
                <label style="display:block; font-size:0.85rem; font-weight:800; color:#475569; margin-bottom:6px;">اختر الحالة الجديدة للشيك</label>
                <select name="status" class="form-control" style="width:100%; padding:10px 14px; border:1px solid #cbd5e1; border-radius:8px; font-weight:bold;">
                    <option value="pending" <?= $cheque->status === 'pending' ? 'selected' : '' ?>>برسم التحصيل / معلق (Pending)</option>
                    <option value="collected" <?= $cheque->status === 'collected' ? 'selected' : '' ?>>محصل بالمصرف / مقبول (Collected)</option>
                    <option value="bounced" <?= $cheque->status === 'bounced' ? 'selected' : '' ?>>مرتد / مرفوض بدون رصيد (Bounced)</option>
                    <option value="cancelled" <?= $cheque->status === 'cancelled' ? 'selected' : '' ?>>ملغى (Cancelled)</option>
                </select>
            </div>
            <button type="submit" style="background:var(--c-chq-dark); color:#fff; border:none; padding:12px 24px; border-radius:8px; font-weight:800; cursor:pointer;"><i class="ph-bold ph-check"></i> تحديث الحالة الآن</button>
        </form>
    </div>
</div>