<?php
// Path: resources/views/accounting/taxes/show.php

try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
    $currency = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');

    global $companyName, $companyLogo;
    $cName = $companyName ?? 'NOUR TRUST';
    $cLogo = $companyLogo ?? '/assets/img/default-logo.png';

    $t = [
        'ar' => [
            'back' => 'رجوع للقائمة', 'print' => 'طباعة التقرير', 'edit' => 'تعديل',
            'subtitle' => 'تقرير وإحصائيات الضريبة (Tax Report)', 'tx_name' => 'اسم الضريبة:', 'rate' => 'النسبة المئوية:', 'branch' => 'الفرع:',
            'status' => 'الحالة:', 'st_active' => 'نشط', 'st_inactive' => 'معطل', 'type' => 'نوع الضريبة:',
            'acc' => 'الحساب المحاسبي:',
            'stat_dr' => 'إجمالي الاستقطاعات (مدين)', 'stat_cr' => 'إجمالي الاستحقاقات (دائن)',
            'tx_title' => 'أحدث القيود المسجلة على الحساب الضريبي',
            'col_date' => 'تاريخ القيد', 'col_ref' => 'رقم القيد', 'col_desc' => 'البيان', 'col_dr' => 'مدين (+)', 'col_cr' => 'دائن (-)',
            'empty' => 'لا توجد قيود مسجلة على حساب هذه الضريبة.', 'general' => 'عام (المركز الرئيسي)',
            'print_date' => 'تاريخ الطباعة:'
        ],
        'en' => [
            'back' => 'Back to List', 'print' => 'Print Report', 'edit' => 'Edit',
            'subtitle' => 'Tax Report & Statistics', 'tx_name' => 'Tax Name:', 'rate' => 'Tax Rate:', 'branch' => 'Branch:',
            'status' => 'Status:', 'st_active' => 'Active', 'st_inactive' => 'Inactive', 'type' => 'Tax Type:',
            'acc' => 'GL Account:',
            'stat_dr' => 'Total Deductions (Dr)', 'stat_cr' => 'Total Accruals (Cr)',
            'tx_title' => 'Latest Entries Posted to Tax Account',
            'col_date' => 'Entry Date', 'col_ref' => 'Entry No.', 'col_desc' => 'Description', 'col_dr' => 'Debit (+)', 'col_cr' => 'Credit (-)',
            'empty' => 'No journal entries posted for this tax account.', 'general' => 'General (HQ)',
            'print_date' => 'Print Date:'
        ]
    ][$isRtl ? 'ar' : 'en'];

    $txName = $isRtl ? ($tax->name_ar ?? '') : ($tax->name_en ?: ($tax->name_ar ?? ''));
    $branchName = !empty($tax->branch_name) ? ($isRtl ? $tax->branch_name : ($tax->branch_name_en ?: $tax->branch_name)) : $t['general'];
    $accName = $isRtl ? ($tax->acc_name ?? '') : ($tax->acc_name_en ?: ($tax->acc_name ?? ''));
    $isActive = !empty($tax->is_active);

    function getTaxTypeLabel($type, $isRtl) {
        $map = [
            'vat' => $isRtl ? 'قيمة مضافة (VAT)' : 'VAT',
            'withholding' => $isRtl ? 'خصم من المنبع' : 'Withholding',
            'other' => $isRtl ? 'أخرى' : 'Other'
        ];
        return $map[$type] ?? $map['other'];
    }
?>

<style>
    :root { 
        --c-tx: #0d9488; --c-tx-dark: #0f766e; --c-tx-light: #ccfbf1;
        --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; 
    }
    
    .tx-show-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>;}
    .print-only-header { display: none; }

    .header-bar { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); padding: 24px 30px; margin-bottom: 30px; border-bottom: 1px solid rgba(226,232,240,0.8); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; position: sticky; top:0; z-index:10;}
    .btn-action { height: 42px; padding: 0 16px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 0.9rem; font-weight: 800; cursor: pointer; gap: 6px; transition:0.3s;}
    .btn-action:hover { background: var(--c-tx-light); color: var(--c-tx); border-color: #5eead4; }
    
    .btn-print { background: var(--c-text-dark); color: #ffffff; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; font-size: 0.95rem; }

    .info-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px 32px; border-left: 5px solid var(--c-tx); margin: 0 30px 30px 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
    @media(max-width:900px){ .info-grid { grid-template-columns: repeat(2, 1fr); } }
    .info-item h5 { margin: 0 0 6px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .info-item p { margin: 0; font-size: 1.1rem; font-weight: 900; color: var(--c-text-dark); font-family:inherit;}

    .kpi-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 0 30px 30px 30px; }
    .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; display:flex; justify-content:space-between; align-items:center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
    .kpi-card h4 { margin: 0 0 8px 0; font-size: 0.9rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-card p { margin: 0; font-size: 2rem; font-weight: 900; font-family: monospace; color: var(--c-tx-dark); }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; margin: 0 30px 30px 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .tx-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; text-align: start; }
    .tx-table th { padding: 18px 24px; background: #f8fafc; color: var(--c-text-muted); font-weight: 900; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
    .tx-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; font-weight:700;}

    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        body * { visibility: hidden !important; }
        .tx-show-wrapper, .tx-show-wrapper * { visibility: visible !important; }
        .tx-show-wrapper { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; background-color: #ffffff !important;}
        .header-bar { display: none !important; }
        .print-only-header { display: flex !important; align-items: flex-end !important; justify-content: space-between !important; border-bottom: 3px solid #000 !important; padding-bottom: 12px !important; margin-bottom: 20px !important; }
        .print-logo-box { display: flex !important; align-items: center !important; gap: 15px !important; }
        .print-logo-box img { max-height: 50px !important; }
        .print-logo-box h2 { margin: 0 !important; font-size: 1.6rem !important; color: #000 !important; font-weight: 900 !important; }
        .print-meta { text-align: end !important; font-size: 0.9rem !important; font-weight: bold !important; color: #000 !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .info-card { border: 2px solid #000 !important; box-shadow: none !important; margin: 0 0 20px 0 !important; border-radius: 6px !important; border-left: 2px solid #000 !important; padding: 15px !important;}
        .info-item h5 { color: #000 !important; font-size: 8pt !important; }
        .info-item p { color: #000 !important; font-size: 10pt !important; }
        .kpi-row { margin: 0 0 20px 0 !important; gap:10px !important;}
        .kpi-card { border: 2px solid #000 !important; box-shadow: none !important; padding: 12px !important; border-radius: 6px !important; }
        .kpi-card p { color: #000 !important; font-size: 16pt !important;}
        .table-card { border: 2px solid #000 !important; box-shadow: none !important; margin: 0 !important; border-radius: 6px !important; page-break-inside: auto;}
        .table-title-print { background: #e2e8f0 !important; color: #000 !important; border-bottom: 2px solid #000 !important;}
        .tx-table { border-collapse: collapse !important; width: 100% !important; }
        .tx-table th { background: #f1f5f9 !important; color: #000 !important; border-bottom: 2px solid #000 !important; font-size: 9pt !important; padding: 8px !important;}
        .tx-table td { border: 1px solid #cbd5e1 !important; color: #000 !important; padding: 8px !important; font-size: 9pt !important;}
    }
</style>

<div class="tx-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <div class="print-only-header">
        <div class="print-logo-box">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars((string)$cLogo) ?>" alt="Logo">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:2.8rem; color:#000;"></i>
            <?php endif; ?>
            <div>
                <h2><?= htmlspecialchars((string)$cName) ?></h2>
                <span style="font-size:1.05rem; color:#475569; font-weight:900;"><?= $t['subtitle'] ?></span>
            </div>
        </div>
        <div class="print-meta">
            <?= $t['tx_name'] ?> <?= htmlspecialchars((string)$txName) ?><br>
            <?= $t['print_date'] ?> <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:24px;">
            <a href="/ERP/accounting/taxes" class="btn-action" title="<?= $t['back'] ?>"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.8rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars((string)$txName) ?></h2>
                <div style="display:flex; gap:12px; margin-top:6px;">
                    <span style="color:var(--c-tx-dark); font-weight:900; font-family:monospace; font-size:1.05rem;"><i class="ph-bold ph-percent"></i> <?= number_format((float)($tax->rate ?? 0), 2) ?>%</span>
                </div>
            </div>
        </div>
        <div style="display:flex; gap:12px;">
            <a href="/ERP/accounting/taxes/<?= $tax->id ?? 0 ?>/edit" class="btn-action" style="background:var(--c-tx); color:#fff; border-color:var(--c-tx);"><i class="ph-bold ph-pencil"></i> <?= $t['edit'] ?></a>
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="info-card">
        <div class="info-grid">
            <div class="info-item">
                <h5><?= $t['type'] ?></h5>
                <p style="color:var(--c-tx-dark);"><i class="ph-bold ph-receipt"></i> <?= getTaxTypeLabel((string)($tax->tax_type ?? 'vat'), $isRtl) ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['rate'] ?></h5>
                <p style="font-family:monospace; font-size:1.2rem; color:#ea580c;"><?= number_format((float)($tax->rate ?? 0), 2) ?>%</p>
            </div>
            <div class="info-item">
                <h5><?= $t['acc'] ?></h5>
                <p style="font-family:monospace; font-size:0.95rem; color:#0f172a;"><?= htmlspecialchars((string)($tax->acc_code ?? '')) ?> <br> <span style="font-family:inherit; font-size:0.85rem; color:var(--c-text-muted);"><?= htmlspecialchars($accName) ?></span></p>
            </div>
            <div class="info-item">
                <h5><?= $t['status'] ?></h5>
                <?php if($isActive): ?>
                    <p style="color:#059669;"><i class="ph-fill ph-check-circle"></i> <?= $t['st_active'] ?></p>
                <?php else: ?>
                    <p style="color:#dc2626;"><i class="ph-fill ph-x-circle"></i> <?= $t['st_inactive'] ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php if(!empty($tax->notes)): ?>
            <div style="margin-top:20px; padding-top:20px; border-top:1px dashed var(--c-border);">
                <h5 style="margin: 0 0 6px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase;">ملاحظات:</h5>
                <p style="margin:0; font-weight:700; color:#334155;"><?= nl2br(htmlspecialchars((string)$tax->notes)) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="kpi-row">
        <div class="kpi-card" style="border-bottom: 5px solid #059669;">
            <div>
                <h4 style="color:#059669;"><?= $t['stat_dr'] ?></h4>
                <p style="color:#059669;"><?= number_format((float)($totalDebit ?? 0), 2) ?> <span style="font-size:1rem;"><?= $currency ?></span></p>
            </div>
            <i class="ph-duotone ph-arrow-down-left" style="font-size:3rem; color:#ecfdf5;"></i>
        </div>
        <div class="kpi-card" style="border-bottom: 5px solid #dc2626;">
            <div>
                <h4 style="color:#dc2626;"><?= $t['stat_cr'] ?></h4>
                <p style="color:#dc2626;"><?= number_format((float)($totalCredit ?? 0), 2) ?> <span style="font-size:1rem;"><?= $currency ?></span></p>
            </div>
            <i class="ph-duotone ph-arrow-up-right" style="font-size:3rem; color:#fef2f2;"></i>
        </div>
    </div>

    <div class="table-card">
        <div class="table-title-print" style="padding:22px 30px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:900; color:var(--c-text-dark); font-size:1.1rem; display:flex; justify-content:space-between;">
            <span><i class="ph-bold ph-list-numbers" style="color:var(--c-tx); font-size:1.4rem; vertical-align:middle; margin-inline-end:8px;"></i> <?= $t['tx_title'] ?></span>
            <span style="font-size:0.85rem; color:#fff; background:#10b981; padding:4px 12px; border-radius:6px; font-weight:900;">POSTED ✓</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="tx-table">
                <thead>
                    <tr>
                        <th style="width: 15%;"><?= $t['col_date'] ?></th>
                        <th style="width: 20%;"><?= $t['col_ref'] ?></th>
                        <th style="width: 35%;"><?= $t['col_desc'] ?></th>
                        <th style="width: 15%; text-align:center;"><?= $t['col_dr'] ?></th>
                        <th style="width: 15%; text-align:center;"><?= $t['col_cr'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($transactions)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:60px; color:#94a3b8; font-weight:800; font-size:1.1rem; border-bottom:none;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach($transactions as $je): ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:#475569; font-size:1.05rem;"><?= htmlspecialchars((string)($je->entry_date ?? '')) ?></td>
                            <td><a href="/ERP/accounting/journal-entries/<?= htmlspecialchars((string)($je->journal_entry_id ?? '')) ?>" style="font-family:monospace; font-weight:900; color:var(--c-tx-dark); text-decoration:none; font-size:1.05rem;">#<?= htmlspecialchars((string)($je->entry_number ?? '')) ?></a></td>
                            <td style="font-weight:800; color:var(--c-text-dark);"><?= htmlspecialchars((string)($je->description ?: ($je->entry_desc ?? ''))) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669; font-size:1.15rem;"><?= number_format((float)($je->debit ?? 0), 2) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626; font-size:1.15rem;"><?= number_format((float)($je->credit ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
} catch (Throwable $e) {
    echo "<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 View Error (show.php)</h3>" . htmlspecialchars($e->getMessage()) . "<br>Line: " . $e->getLine() . "</div>";
}
?>