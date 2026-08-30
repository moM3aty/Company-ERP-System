<?php
// Path: resources/views/accounting/fixed_assets/show.php

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
    :root { --c-fa: #0d9488; --c-fa-dark: #0f766e; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .fa-show-wrapper { max-width: 1050px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .print-only-header { display: none; }

    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-depreciate { background: linear-gradient(135deg, var(--c-fa), var(--c-fa-dark)); color: #fff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; }
    .kpi-card h4 { margin: 0 0 6px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-card p { margin: 0; font-size: 1.5rem; font-weight: 900; font-family: monospace; }

    .info-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; border-top: 4px solid var(--c-fa); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .info-item h5 { margin: 0 0 6px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .info-item p { margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--c-text-dark); }

    .table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .dep-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    .dep-table th { padding: 12px 16px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; border-bottom: 2px solid #e2e8f0; text-align: start; font-size: 0.75rem; }
    .dep-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; }

    /* Modal Styles */
    .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 9999; }
    .modal-card { background: #ffffff; border-radius: 20px; width: 100%; max-width: 420px; padding: 32px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); animation: modalIn 0.2s ease-out; }
    @keyframes modalIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .modal-icon-circle { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; background: #ccfbf1; }
    .modal-actions-row { display: flex; gap: 12px; margin-top: 24px; justify-content: center; }
    .btn-modal-cancel { flex: 1; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; font-weight: 800; cursor: pointer; }
    .btn-modal-confirm { flex: 1; padding: 12px; border-radius: 10px; border: none; background: var(--c-fa); color: #ffffff; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside, .modal-overlay { display: none !important; }
        body { background: #fff !important; }
        .fa-show-wrapper { max-width: 100% !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .print-only-header { display: flex !important; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--c-fa); padding-bottom: 15px; margin-bottom: 20px; }
    }
</style>

<div class="fa-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <div class="print-only-header">
        <div style="display:flex; align-items:center; gap:15px;">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:50px;">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:2.5rem; color:var(--c-fa);"></i>
            <?php endif; ?>
            <h2 style="margin:0; font-size:1.5rem; color:#000; font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
        </div>
        <div style="text-align:end; font-size:0.85rem; font-weight:bold;">
            كارت متابعة أصل ثابت والإهلاك<br>
            تاريخ الطباعة: <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/accounting/fixed-assets" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($asset->name_ar) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-fa-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($asset->code) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <?php if(($asset->status ?? '') === 'active'): ?>
                <button type="button" class="btn-depreciate" onclick="openDepreciateModal()">
                    <i class="ph-bold ph-calculator"></i> احتساب الإهلاك الشهري وتوليد القيد
                </button>
            <?php endif; ?>
            <button onclick="window.print()" class="btn-action" title="طباعة"><i class="ph-bold ph-printer"></i></button>
            <a href="/ERP/accounting/fixed-assets/<?= $asset->id ?>/edit" class="btn-action" title="تعديل"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card" style="border-bottom: 3px solid #0f172a;">
            <h4>التكلفة التاريخية</h4>
            <p><?= number_format((float)$asset->purchase_cost, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #dc2626;">
            <h4 style="color:#dc2626;">مجمع الإهلاك التراكمي</h4>
            <p style="color:#dc2626;"><?= number_format((float)$asset->accumulated_depreciation, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid var(--c-fa); background:#ccfbf1;">
            <h4 style="color:var(--c-fa-dark);">صافي القيمة الدفترية الحالية</h4>
            <p style="color:var(--c-fa-dark);"><?= number_format((float)$asset->book_value, 2) ?> <span style="font-size:0.8rem; font-weight:normal;"><?= $currency ?></span></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #d97706;">
            <h4 style="color:#d97706;">القيمة التخريدية المستهدفة</h4>
            <p style="color:#d97706;"><?= number_format((float)$asset->salvage_value, 2) ?></p>
        </div>
    </div>

    <div class="info-card">
        <div class="grid-3">
            <div class="info-item">
                <h5>تاريخ الشراء / بدء الإهلاك</h5>
                <p style="font-family:monospace;"><?= htmlspecialchars($asset->purchase_date) ?></p>
            </div>
            <div class="info-item">
                <h5>العمر الإنتاجي</h5>
                <p><?= (int)$asset->useful_life_years ?> سنوات</p>
            </div>
            <div class="info-item">
                <h5>طريقة الإهلاك</h5>
                <p>قسط ثابت (Straight Line)</p>
            </div>
            <div class="info-item" style="margin-top:15px;">
                <h5>حساب الأصل</h5>
                <p><?= htmlspecialchars($asset->asset_account_name ?? 'غير محدد') ?></p>
            </div>
            <div class="info-item" style="margin-top:15px;">
                <h5>حساب مجمع الإهلاك</h5>
                <p><?= htmlspecialchars($asset->acc_account_name ?? 'غير محدد') ?></p>
            </div>
            <div class="info-item" style="margin-top:15px;">
                <h5>حساب مصروف الإهلاك</h5>
                <p><?= htmlspecialchars($asset->exp_account_name ?? 'غير محدد') ?></p>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div style="padding:14px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:800; color:var(--c-text-dark);">
            <i class="ph-bold ph-history" style="color:var(--c-fa);"></i> سجل قيود وحركات الإهلاك المسجلة
        </div>
        <table class="dep-table">
            <thead>
                <tr>
                    <th>تاريخ الحركة</th>
                    <th>رقم قيد اليومية</th>
                    <th>البيان</th>
                    <th style="text-align:center;">مبلغ الإهلاك</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($depreciations)): ?>
                    <tr><td colspan="4" style="text-align:center; padding:30px; color:#94a3b8;">لم يتم احتساب أو توليد قيود إهلاك لهذا الأصل بعد.</td></tr>
                <?php else: foreach ($depreciations as $d): ?>
                    <tr>
                        <td style="font-family:monospace; font-weight:700;"><?= htmlspecialchars($d->depreciation_date) ?></td>
                        <td><a href="/ERP/accounting/journal-entries/<?= $d->journal_entry_id ?>" style="font-family:monospace; font-weight:900; color:var(--c-fa); text-decoration:none;">#<?= htmlspecialchars($d->entry_number) ?></a></td>
                        <td style="font-weight:700; color:var(--c-text-dark);"><?= htmlspecialchars($d->notes ?? 'إهلاك آلي') ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format((float)$d->amount, 2) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal احتساب الإهلاك المخصص -->
<div id="depreciateModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-icon-circle">
            <i class="ph-bold ph-calculator" style="font-size:2rem; color:var(--c-fa);"></i>
        </div>
        <h3 style="margin: 0 0 8px 0; font-size: 1.3rem; font-weight: 900; color: var(--c-text-dark);">تأكيد احتساب الإهلاك</h3>
        <p style="margin: 0; color: var(--c-text-muted); font-size: 0.9rem; line-height: 1.5; font-weight: 600;">
            هل تريد احتساب وتوليد قيد الإهلاك الشهري لهذا الأصل؟ سيتم تحديث مجمع الإهلاك وإنشاء قيد يومية مرحّل تلقائياً.
        </p>
        
        <div class="modal-actions-row">
            <button type="button" onclick="closeDepreciateModal()" class="btn-modal-cancel">تراجع وإلغاء</button>
            <form action="/ERP/accounting/fixed-assets/<?= $asset->id ?>/depreciate" method="POST" style="flex: 1;">
                <button type="submit" class="btn-modal-confirm" style="width: 100%;">
                    <i class="ph-bold ph-check"></i> تأكيد الاحتساب
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openDepreciateModal() {
    document.getElementById('depreciateModal').style.display = 'flex';
}
function closeDepreciateModal() {
    document.getElementById('depreciateModal').style.display = 'none';
}
</script>