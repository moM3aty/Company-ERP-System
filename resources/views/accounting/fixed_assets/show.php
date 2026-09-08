<?php
// Path: resources/views/accounting/fixed_assets/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$t = [
    'ar' => [
        'back' => 'رجوع للقائمة', 'print' => 'طباعة البطاقة', 'edit' => 'تعديل',
        'subtitle' => 'كارت متابعة الأصل الثابت والإهلاك (Asset Profile)', 'code' => 'كود الأصل', 'branch' => 'الفرع / الموقع',
        'dep_btn' => 'احتساب إهلاك الشهر الحالي', 'modal_title' => 'تأكيد احتساب الإهلاك', 'modal_desc' => 'هل تريد احتساب وتوليد قيد الإهلاك الشهري؟ سيتم تحديث مجمع الإهلاك وإنشاء قيد يومية.',
        'modal_cancel' => 'إلغاء', 'modal_confirm' => 'تأكيد الاحتساب',
        'cost' => 'التكلفة التاريخية', 'dep' => 'مجمع الإهلاك التراكمي', 'book' => 'صافي القيمة الدفترية', 'salvage' => 'القيمة التخريدية',
        'date' => 'تاريخ الشراء / الإهلاك', 'life' => 'العمر الإنتاجي', 'method' => 'طريقة الإهلاك',
        'method_sl' => 'قسط ثابت (Straight Line)', 'years' => 'سنوات',
        'acc_ast' => 'حساب الأصل', 'acc_dep_acc' => 'حساب مجمع الإهلاك', 'acc_dep_exp' => 'حساب مصروف الإهلاك',
        'tx_title' => 'سجل قيود وحركات الإهلاك المسجلة', 'col_date' => 'تاريخ الحركة', 'col_ref' => 'رقم القيد', 'col_desc' => 'البيان', 'col_amt' => 'مبلغ الإهلاك',
        'empty' => 'لم يتم احتساب أو توليد قيود إهلاك لهذا الأصل بعد.', 'null' => 'غير محدد', 'general' => 'عام (الشركة)',
        'print_date' => 'تاريخ الاستخراج:'
    ],
    'en' => [
        'back' => 'Back to List', 'print' => 'Print Profile', 'edit' => 'Edit',
        'subtitle' => 'Asset Profile & Depreciation Tracking', 'code' => 'Asset Code', 'branch' => 'Branch / Site',
        'dep_btn' => 'Post Monthly Depreciation', 'modal_title' => 'Confirm Depreciation', 'modal_desc' => 'Do you want to calculate and post the monthly depreciation? This will update accumulated depreciation and generate a journal entry.',
        'modal_cancel' => 'Cancel', 'modal_confirm' => 'Confirm Post',
        'cost' => 'Historical Cost', 'dep' => 'Accumulated Depr.', 'book' => 'Net Book Value', 'salvage' => 'Salvage Value',
        'date' => 'Purchase Date', 'life' => 'Useful Life', 'method' => 'Depr. Method',
        'method_sl' => 'Straight Line', 'years' => 'Years',
        'acc_ast' => 'Asset Account', 'acc_dep_acc' => 'Acc. Depr. Account', 'acc_dep_exp' => 'Depr. Exp. Account',
        'tx_title' => 'Depreciation Ledger & Entries', 'col_date' => 'Date', 'col_ref' => 'Entry No.', 'col_desc' => 'Description', 'col_amt' => 'Depr. Amount',
        'empty' => 'No depreciation entries generated for this asset yet.', 'null' => 'Not assigned', 'general' => 'General (Company)',
        'print_date' => 'Report Date:'
    ]
][$isRtl ? 'ar' : 'en'];

$aName = $isRtl ? ($asset->name_ar ?: $asset->name_en) : ($asset->name_en ?: $asset->name_ar);
$branchBadge = !empty($asset->branch_name) ? ($isRtl ? $asset->branch_name : ($asset->branch_name_en ?: $asset->branch_name)) : $t['general'];

$accAst = $isRtl ? ($asset->asset_account_name ?? '') : ($asset->asset_account_name_en ?? $asset->asset_account_name ?? '');
$accDep = $isRtl ? ($asset->acc_account_name ?? '') : ($asset->acc_account_name_en ?? $asset->acc_account_name ?? '');
$expAcc = $isRtl ? ($asset->exp_account_name ?? '') : ($asset->exp_account_name_en ?? $asset->exp_account_name ?? '');
?>

<style>
    :root { 
        --brand-primary: #0d9488; --brand-primary-dark: #0f766e; --brand-primary-light: #ccfbf1; 
        --surface: #ffffff; --surface-hover: #f8fafc; --bg-body: #f1f5f9;
        --text-main: #0f172a; --text-muted: #64748b; --border-color: #e2e8f0;
        --radius-lg: 24px; --radius-md: 16px;
        --shadow-soft: 0 10px 30px -10px rgba(15, 23, 42, 0.08);
        --shadow-hover: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
    }
    .fa-show-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: var(--bg-body);}
    
    .print-only-header { display: none; }

    .header-bar { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); padding: 24px 30px; margin-bottom: 30px; border-bottom: 1px solid rgba(226,232,240,0.8); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; position: sticky; top:0; z-index:10;}
    .btn-action { width: 48px; height: 48px; border-radius: 14px; background: var(--surface); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--text-muted); font-size: 1.3rem; transition: 0.3s; box-shadow: var(--shadow-soft);}
    .btn-action:hover { background: var(--brand-primary-light); color: var(--brand-primary); border-color: #5eead4; transform: translateX(<?= $isRtl ? '4px' : '-4px' ?>);}
    .btn-print { background: var(--text-main); color: #ffffff; border: none; padding: 14px 26px; border-radius: 12px; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; font-size: 0.95rem; box-shadow: var(--shadow-soft);}
    .btn-print:hover { background: #000000; transform: translateY(-2px); box-shadow: var(--shadow-hover);}
    .btn-depreciate { background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)); color: #fff; border: none; padding: 14px 26px; border-radius: 12px; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 8px 20px rgba(13, 148, 136, 0.25); transition: 0.3s; font-size: 0.95rem;}
    .btn-depreciate:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(13, 148, 136, 0.35); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 0 30px 30px 30px; }
    @media (max-width: 900px) { .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; box-shadow: var(--shadow-soft); transition: 0.3s;}
    .kpi-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); }
    .kpi-card h4 { margin: 0 0 10px 0; font-size: 0.85rem; color: var(--text-muted); font-weight: 800; }
    .kpi-card p { margin: 0; font-size: 1.8rem; font-weight: 900; font-family: monospace; color: var(--text-main); letter-spacing:-0.5px;}

    .info-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 32px; border-top: 5px solid var(--brand-primary); margin: 0 30px 30px 30px; box-shadow: var(--shadow-soft); transition: 0.4s;}
    .info-card:hover { box-shadow: var(--shadow-hover); transform: translateY(-3px);}
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
    @media (max-width: 900px) { .grid-3 { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .grid-3 { grid-template-columns: 1fr; } }
    .info-item { background: var(--surface-hover); padding: 20px; border-radius: 16px; border: 1px solid #f1f5f9; }
    .info-item h5 { margin: 0 0 8px 0; font-size: 0.8rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase; }
    .info-item p { margin: 0; font-size: 1.15rem; font-weight: 900; color: var(--text-main); font-family:inherit;}

    .table-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; margin: 0 30px 30px 30px; box-shadow: var(--shadow-soft); }
    .dep-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; text-align: start; }
    .dep-table th { padding: 18px 24px; background: var(--surface-hover); color: var(--text-muted); font-weight: 900; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; }
    .dep-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; font-weight:700;}
    .dep-table tr:hover td { background: var(--surface-hover); }

    /* Modal Styles */
    .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); display: none; align-items: center; justify-content: center; z-index: 9999; }
    .modal-card { background: #ffffff; border-radius: 24px; width: 100%; max-width: 440px; padding: 36px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: modalIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    @keyframes modalIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .modal-icon-circle { width: 72px; height: 72px; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; background: var(--brand-primary-light); color: var(--brand-primary); }
    .modal-actions-row { display: flex; gap: 16px; margin-top: 30px; justify-content: center; }
    .btn-modal-cancel { flex: 1; padding: 14px; border-radius: 12px; border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; font-weight: 900; cursor: pointer; transition:0.2s;}
    .btn-modal-cancel:hover { background: #e2e8f0; }
    .btn-modal-confirm { flex: 1; padding: 14px; border-radius: 12px; border: none; background: var(--brand-primary); color: #ffffff; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition:0.2s;}

    /* ========================================================
       BULLETPROOF PRINT STYLES - تنسيقات الطباعة الخارقة (A4)
       ======================================================== */
    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        body * { visibility: hidden !important; }
        .fa-show-wrapper, .fa-show-wrapper * { visibility: visible !important; }
        
        .fa-show-wrapper { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; margin: 0 !important; padding: 0 !important; background-color: #ffffff !important;}
        .header-bar, .btn-action, .btn-print, .btn-depreciate, .modal-overlay { display: none !important; }
        
        .print-only-header { display: flex !important; align-items: flex-end !important; justify-content: space-between !important; border-bottom: 3px solid #000 !important; padding-bottom: 12px !important; margin-bottom: 20px !important; }
        .print-logo-box { display: flex !important; align-items: center !important; gap: 15px !important; }
        .print-logo-box img { max-height: 50px !important; }
        .print-logo-box h2 { margin: 0 !important; font-size: 1.6rem !important; color: #000 !important; font-weight: 900 !important; }
        .print-meta { text-align: end !important; font-size: 0.9rem !important; font-weight: bold !important; color: #000 !important; }
        
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        
        .kpi-row { display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 10px !important; margin: 0 0 20px 0 !important; }
        .kpi-card { border: 2px solid #000 !important; box-shadow: none !important; padding: 12px !important; border-radius: 6px !important; page-break-inside: avoid; background: transparent !important;}
        .kpi-card h4 { color: #000 !important; font-size: 9pt !important; }
        .kpi-card p { color: #000 !important; font-size: 12pt !important; }
        
        .info-card { border: 2px solid #000 !important; box-shadow: none !important; margin: 0 0 20px 0 !important; border-radius: 6px !important; page-break-inside: avoid; padding: 15px !important;}
        .grid-3 { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 10px !important; }
        .info-item { background: transparent !important; border: 1px solid #000 !important; padding: 10px !important; border-radius: 4px !important;}
        .info-item h5 { color: #000 !important; font-size: 8pt !important; }
        .info-item p { color: #000 !important; font-size: 10pt !important; }

        .table-card { border: 2px solid #000 !important; box-shadow: none !important; margin: 0 !important; border-radius: 6px !important; page-break-inside: auto;}
        .table-title-print { background: #e2e8f0 !important; color: #000 !important; border-bottom: 2px solid #000 !important;}
        .dep-table { border-collapse: collapse !important; width: 100% !important; }
        .dep-table th { background: #f1f5f9 !important; color: #000 !important; border-bottom: 2px solid #000 !important; border-left: 1px solid #000 !important; border-right: 1px solid #000 !important; font-weight: bold !important; font-size: 9pt !important; padding: 8px !important;}
        .dep-table td { border: 1px solid #000 !important; color: #000 !important; padding: 8px !important; font-size: 9pt !important;}
        
        a { text-decoration: none !important; color: #000 !important; }
    }
</style>

<div class="fa-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <!-- 📄 ترويسة الطباعة (تظهر فقط عند الطباعة) -->
    <div class="print-only-header">
        <div class="print-logo-box">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:2.8rem; color:#000;"></i>
            <?php endif; ?>
            <div>
                <h2><?= htmlspecialchars($cName) ?></h2>
                <span style="font-size:1.05rem; color:#475569; font-weight:900;"><?= $t['subtitle'] ?></span>
            </div>
        </div>
        <div class="print-meta">
            <?= htmlspecialchars($aName) ?> (<?= htmlspecialchars($asset->code) ?>)<br>
            <?= $t['print_date'] ?> <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <!-- رأس الصفحة الطبيعي -->
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:24px;">
            <a href="/ERP/accounting/fixed-assets" class="btn-action" title="<?= $t['back'] ?>"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.8rem; color:var(--text-main); font-weight:900;"><?= htmlspecialchars($aName) ?></h2>
                <p style="margin:6px 0 0 0; color:var(--brand-primary-dark); font-weight:900; font-family:monospace; font-size:1.15rem;"><?= htmlspecialchars($asset->code) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:12px;">
            <?php if(($asset->status ?? '') === 'active'): ?>
                <button type="button" class="btn-depreciate" onclick="openDepreciateModal()">
                    <i class="ph-bold ph-calculator"></i> <?= $t['dep_btn'] ?>
                </button>
            <?php endif; ?>
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
            <a href="/ERP/accounting/fixed-assets/<?= $asset->id ?>/edit" class="btn-print" style="background:#0f172a; text-decoration:none;"><i class="ph-bold ph-pencil"></i> <?= $t['edit'] ?></a>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="margin: 0 30px 24px 30px; background: #ecfdf5; color: #059669; padding: 16px 20px; border-radius: 14px; font-weight: 800; border: 1px solid #a7f3d0; display:flex; align-items:center; gap:10px;"><i class="ph-fill ph-check-circle" style="font-size:1.4rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="margin: 0 30px 24px 30px; background: #fff1f2; color: #e11d48; padding: 16px 20px; border-radius: 14px; font-weight: 800; border: 1px solid #fecdd3; display:flex; align-items:center; gap:10px;"><i class="ph-fill ph-warning-circle" style="font-size:1.4rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card" style="border-bottom: 5px solid #0f172a;">
            <h4><?= $t['cost'] ?></h4>
            <p><?= number_format((float)$asset->purchase_cost, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 5px solid #dc2626;">
            <h4 style="color:#dc2626;"><?= $t['dep'] ?></h4>
            <p style="color:#dc2626;"><?= number_format((float)$asset->accumulated_depreciation, 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 5px solid var(--brand-primary); background:var(--brand-primary-light);">
            <h4 style="color:var(--brand-primary-dark);"><?= $t['book'] ?></h4>
            <p style="color:var(--brand-primary-dark);"><?= number_format((float)$asset->book_value, 2) ?> <span style="font-size:1rem; font-weight:800; color:var(--brand-primary);"><?= $currency ?></span></p>
        </div>
        <div class="kpi-card" style="border-bottom: 5px solid #d97706;">
            <h4 style="color:#d97706;"><?= $t['salvage'] ?></h4>
            <p style="color:#d97706;"><?= number_format((float)$asset->salvage_value, 2) ?></p>
        </div>
    </div>

    <div class="info-card">
        <div class="grid-3">
            <div class="info-item">
                <h5><?= $t['date'] ?></h5>
                <p style="font-family:monospace;"><?= htmlspecialchars($asset->purchase_date) ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['life'] ?></h5>
                <p><?= (int)$asset->useful_life_years ?> <?= $t['years'] ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['method'] ?></h5>
                <p><?= $t['method_sl'] ?></p>
            </div>
            
            <div class="info-item" style="margin-top:15px;">
                <h5><?= $t['acc_ast'] ?></h5>
                <p><?= htmlspecialchars($accAst ?: $t['null']) ?></p>
            </div>
            <div class="info-item" style="margin-top:15px;">
                <h5><?= $t['acc_dep_acc'] ?></h5>
                <p><?= htmlspecialchars($accDep ?: $t['null']) ?></p>
            </div>
            <div class="info-item" style="margin-top:15px;">
                <h5><?= $t['acc_dep_exp'] ?></h5>
                <p><?= htmlspecialchars($expAcc ?: $t['null']) ?></p>
            </div>
            <div class="info-item" style="margin-top:15px; grid-column: span 3;">
                <h5><?= $t['branch'] ?></h5>
                <p style="color:#0ea5e9; font-weight:900;"><i class="ph-bold ph-git-branch"></i> <?= htmlspecialchars($branchBadge) ?></p>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-title-print" style="padding:22px 30px; background:var(--surface-hover); border-bottom:1px solid #e2e8f0; font-weight:900; color:var(--text-main); font-size:1.1rem;">
            <i class="ph-bold ph-history" style="color:var(--brand-primary); font-size:1.4rem; vertical-align:middle; margin-inline-end:8px;"></i> <?= $t['tx_title'] ?>
        </div>
        <div style="overflow-x:auto;">
            <table class="dep-table">
                <thead>
                    <tr>
                        <th style="width: 20%;"><?= $t['col_date'] ?></th>
                        <th style="width: 20%;"><?= $t['col_ref'] ?></th>
                        <th style="width: 40%;"><?= $t['col_desc'] ?></th>
                        <th style="width: 20%; text-align:center;"><?= $t['col_amt'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($depreciations)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:50px; color:#94a3b8; font-weight:800; font-size:1rem; border:none;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach ($depreciations as $d): ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:#475569; font-size:1.05rem;"><?= htmlspecialchars($d->depreciation_date) ?></td>
                            <td><a href="/ERP/accounting/journal-entries/<?= $d->journal_entry_id ?>" style="font-family:monospace; font-weight:900; color:var(--brand-primary-dark); text-decoration:none; font-size:1.05rem;">#<?= htmlspecialchars($d->entry_number) ?></a></td>
                            <td style="font-weight:800; color:var(--text-main);"><?= htmlspecialchars($d->notes ?? '---') ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626; font-size:1.15rem;"><?= number_format((float)$d->amount, 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal احتساب الإهلاك -->
<div id="depreciateModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-icon-circle"><i class="ph-bold ph-calculator" style="font-size:2.5rem;"></i></div>
        <h3 style="margin: 0 0 8px 0; font-size: 1.4rem; font-weight: 900; color: var(--text-main);"><?= $t['modal_title'] ?></h3>
        <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; font-weight: 700;"><?= $t['modal_desc'] ?></p>
        
        <div class="modal-actions-row">
            <button type="button" onclick="closeDepreciateModal()" class="btn-modal-cancel"><?= $t['modal_cancel'] ?></button>
            <form action="/ERP/accounting/fixed-assets/<?= $asset->id ?>/depreciate" method="POST" style="flex: 1;">
                <button type="submit" class="btn-modal-confirm" style="width: 100%;">
                    <i class="ph-bold ph-check"></i> <?= $t['modal_confirm'] ?>
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

function purgeControlsForPrint() {
    const selectors = ['.table-pagination-nav', '.dataTables_info', '.dataTables_paginate', '.pagination'];
    selectors.forEach(s => {
        document.querySelectorAll(s).forEach(el => {
            if(el) el.style.display = 'none';
        });
    });
}
function safePrint() {
    purgeControlsForPrint();
    window.print();
}
window.addEventListener("beforeprint", purgeControlsForPrint);
</script>