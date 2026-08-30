<?php
// Path: resources/views/accounting/fiscal_periods/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = 'EGP';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';
?>

<style>
    :root { 
        --c-fp: #d97706; 
        --c-fp-dark: #b45309; 
        --c-fp-light: #fef3c7;
        --c-border: #cbd5e1; 
        --c-text-dark: #0f172a; 
        --c-text-muted: #64748b; 
    }
    
    .fp-show-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .print-only-header { display: none; }

    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { height: 42px; padding: 0 16px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 0.9rem; font-weight: 800; cursor: pointer; gap: 6px; }
    .btn-close-year { background: linear-gradient(135deg, #dc2626, #991b1b); color: #fff; border: none; padding: 11px 22px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:900px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-card h4 { margin: 0 0 6px 0; font-size: 0.78rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-card p { margin: 0; font-size: 1.45rem; font-weight: 900; font-family: monospace; }

    .table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; margin-bottom: 24px; }
    .sub-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    .sub-table th { padding: 14px 16px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; border-bottom: 2px solid #e2e8f0; text-align: start; font-size: 0.75rem; }
    .sub-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 9999; }
    .modal-card { background: #ffffff; border-radius: 20px; width: 100%; max-width: 460px; padding: 32px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
    .modal-icon-circle { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; background: #fef2f2; color: #dc2626; }
    .modal-actions-row { display: flex; gap: 12px; margin-top: 24px; justify-content: center; }
    .btn-modal-cancel { flex: 1; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; font-weight: 800; cursor: pointer; }
    .btn-modal-confirm { flex: 1; padding: 12px; border-radius: 10px; border: none; background: #dc2626; color: #ffffff; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }

    /* ========================================================= */
    /* قواعد تنسيق الطباعة الرسمية (A4 Print Engine Fixes)      */
    /* ========================================================= */
    @media print {
        @page { size: A4 portrait; margin: 12mm 15mm; }
        body { background: #ffffff !important; color: #000000 !important; font-size: 10pt; font-family: 'Cairo', 'Inter', sans-serif !important; }
        .nt-sidebar, .top-header, .header-bar, header, aside, .modal-overlay, button, form button { display: none !important; }
        
        .fp-show-wrapper { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        
        .print-only-header { display: flex !important; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--c-fp); padding-bottom: 12px; margin-bottom: 20px; }
        
        .kpi-row { grid-template-columns: repeat(4, 1fr) !important; gap: 10px !important; margin-bottom: 20px !important; }
        .kpi-card { border: 1px solid #000000 !important; box-shadow: none !important; padding: 12px !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; page-break-inside: avoid; }
        .sub-table { border-collapse: collapse !important; width: 100% !important; }
        .sub-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .sub-table td { border: 1px solid #cbd5e1 !important; padding: 8px 10px !important; }
    }
</style>

<div class="fp-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <!-- الترويسة المخصصة للطباعة -->
    <div class="print-only-header">
        <div style="display:flex; align-items:center; gap:15px;">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:50px;">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:2.5rem; color:var(--c-fp);"></i>
            <?php endif; ?>
            <div>
                <h2 style="margin:0; font-size:1.4rem; color:#000; font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                <span style="font-size:0.85rem; color:#475569; font-weight:bold;">تقرير الوضع المالي للفترة والميزانية</span>
            </div>
        </div>
        <div style="text-align:end; font-size:0.85rem; font-weight:bold; color:#0f172a;">
            تقرير إغلاق السنة والشهور المالية<br>
            تاريخ الطباعة: <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/accounting/fiscal-periods" class="btn-action" style="width:44px; padding:0;"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($period->period_name) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-fp-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($period->start_date) ?> إلى <?= htmlspecialchars($period->end_date) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <?php if($period->status === 'open'): ?>
                <button type="button" class="btn-close-year" onclick="openYearCloseModal()">
                    <i class="ph-bold ph-lock-key"></i> إغلاق وتوليد قيد الإقفال السنوي
                </button>
            <?php else: ?>
                <?php if(!empty($period->closing_journal_id)): ?>
                    <a href="/ERP/accounting/journal-entries/<?= $period->closing_journal_id ?>" class="btn-action" style="background:#f0fdf4; color:#16a34a; border-color:#a7f3d0;">
                        <i class="ph-bold ph-receipt"></i> عرض قيد الإغلاق الآلي
                    </a>
                <?php endif; ?>
            <?php endif; ?>
            <button onclick="window.print()" class="btn-action" title="طباعة"><i class="ph-bold ph-printer"></i> طباعة التقرير</button>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card" style="border-bottom: 3px solid var(--c-fp);">
            <h4 style="color:var(--c-fp-dark);">حالة السنة المالية</h4>
            <p style="color:var(--c-fp-dark);"><?= $period->status === 'open' ? 'مفتوحة (Active)' : 'مغلقة (Closed)' ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #2563eb;">
            <h4 style="color:#2563eb;">قيود مرحلة بدفاتر السنة</h4>
            <p style="color:#2563eb;"><?= number_format($postedCount) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid <?= $draftCount > 0 ? '#dc2626' : '#16a34a' ?>; background: <?= $draftCount > 0 ? '#fef2f2' : '#f0fdf4' ?>;">
            <h4 style="color:<?= $draftCount > 0 ? '#dc2626' : '#16a34a' ?>;">مسودات قيود (تمنع الإغلاق)</h4>
            <p style="color:<?= $draftCount > 0 ? '#dc2626' : '#16a34a' ?>;"><?= number_format($draftCount) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid <?= $netProfit >= 0 ? '#16a34a' : '#dc2626' ?>; background: <?= $netProfit >= 0 ? '#f0fdf4' : '#fef2f2' ?>;">
            <h4 style="color:<?= $netProfit >= 0 ? '#16a34a' : '#dc2626' ?>;">صافي نتيجة العام التقديرية</h4>
            <p style="color:<?= $netProfit >= 0 ? '#16a34a' : '#dc2626' ?>;"><?= number_format($netProfit, 2) ?> <span style="font-size:0.8rem; font-weight:normal;"><?= $currency ?></span></p>
        </div>
    </div>

    <div class="table-card">
        <div style="padding:16px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:800; color:var(--c-text-dark); display:flex; justify-content:space-between; align-items:center;">
            <span><i class="ph-bold ph-calendar-blank" style="color:var(--c-fp);"></i> الفترات والشهور المالية الفرعية (التحكم بالقفل الشهري)</span>
        </div>
        
        <table class="sub-table">
            <thead>
                <tr>
                    <th style="width: 8%;">الشهر</th>
                    <th style="width: 32%;">اسم الفترة الشهرية</th>
                    <th style="width: 25%;">النطاق الزمني</th>
                    <th style="width: 15%; text-align: center;">الحالة</th>
                    <th style="width: 20%; text-align: center;">التحكم بالقفل</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($subPeriods)): ?>
                    <tr><td colspan="5" style="text-align:center; padding:35px; color:#94a3b8; font-weight:700;">لم يتم توليد شهور فرعية لهذه السنة.</td></tr>
                <?php else: foreach($subPeriods as $sub): ?>
                    <tr>
                        <td style="font-family:monospace; font-weight:900; color:var(--c-fp-dark);">#<?= $sub->period_number ?></td>
                        <td style="font-weight:800; color:var(--c-text-dark);"><?= htmlspecialchars($sub->name_ar) ?></td>
                        <td style="font-family:monospace; font-size:0.85rem; color:#64748b;">
                            <?= htmlspecialchars($sub->start_date) ?> إلى <?= htmlspecialchars($sub->end_date) ?>
                        </td>
                        <td style="text-align:center;">
                            <?php if($sub->status === 'open'): ?>
                                <span style="background:#f0fdf4; color:#16a34a; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem;">مفتوح</span>
                            <?php elseif($sub->status === 'soft_lock'): ?>
                                <span style="background:#fef3c7; color:#d97706; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem;">مقفل مؤقتاً</span>
                            <?php else: ?>
                                <span style="background:#fef2f2; color:#dc2626; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem;">مغلق نهائياً</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;">
                            <?php if($period->status === 'open' && $sub->status !== 'closed'): ?>
                                <form action="/ERP/accounting/fiscal-periods/sub-period/<?= $sub->id ?>/toggle" method="POST" style="display:inline;">
                                    <button type="submit" class="btn-action" style="font-size:0.8rem; padding:0 12px; height:32px;">
                                        <?= $sub->status === 'open' ? '<i class="ph-bold ph-lock"></i> قفل مؤقت' : '<i class="ph-bold ph-lock-open"></i> إعادة فتح' ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color:#94a3b8; font-size:0.8rem;">مغلق مع السنة</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="yearCloseModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-icon-circle">
            <i class="ph-bold ph-lock-key" style="font-size:2.2rem;"></i>
        </div>
        <h3 style="margin: 0 0 8px 0; font-size: 1.3rem; font-weight: 900; color: var(--c-text-dark);">تأكيد الإغلاق وقيد الإقفال السنوي</h3>
        <p style="margin: 0; color: var(--c-text-muted); font-size: 0.9rem; line-height: 1.5; font-weight: 600;">
            سيقوم النظام بتجميع وتصفير كافة حسابات الإيرادات والمصروفات، وإنشاء قيد إغلاق سنوي آلي يُرحّل صافي نتيجة العام (<?= number_format($netProfit, 2) ?> EGP) إلى حساب الأرباح المرحلة.
        </p>
        
        <div class="modal-actions-row">
            <button type="button" onclick="closeYearCloseModal()" class="btn-modal-cancel">تراجع وإلغاء</button>
            <form action="/ERP/accounting/fiscal-periods/<?= $period->id ?>/close-year" method="POST" style="flex: 1;">
                <button type="submit" class="btn-modal-confirm" style="width: 100%;">
                    <i class="ph-bold ph-check"></i> توليد القيد والإغلاق
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openYearCloseModal() { document.getElementById('yearCloseModal').style.display = 'flex'; }
function closeYearCloseModal() { document.getElementById('yearCloseModal').style.display = 'none'; }
</script>