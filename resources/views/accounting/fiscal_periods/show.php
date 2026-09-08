<?php
// Path: resources/views/accounting/fiscal_periods/show.php

try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
    $currency = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');

    $flashMsg = $_SESSION['flash_msg'] ?? null;
    $flashErr = $_SESSION['flash_err'] ?? null;
    unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

    global $companyName, $companyLogo;
    $cName = $companyName ?? 'NOUR TRUST';
    $cLogo = $companyLogo ?? '/assets/img/default-logo.png';

    $t = [
        'ar' => [
            'back' => 'رجوع للقائمة', 'print' => 'طباعة الملخص', 'edit' => 'تعديل', 'close_p' => 'إغلاق الفترة',
            'subtitle' => 'ملخص الفترة المالية (Fiscal Period Summary)', 'p_name' => 'اسم الفترة:', 'date_range' => 'النطاق الزمني:', 'branch' => 'الفرع:',
            'status' => 'حالة الفترة:', 'st_open' => 'مفتوحة (تستقبل قيود)', 'st_closed' => 'مغلقة ومقفلة بالكامل',
            'stat_tx' => 'إجمالي القيود المُرحلة', 'stat_vol' => 'حجم الحركات المالي (إجمالي المبالغ)',
            'tx_title' => 'عينة من أحدث القيود المسجلة خلال هذه الفترة',
            'col_date' => 'تاريخ القيد', 'col_ref' => 'رقم القيد', 'col_desc' => 'البيان', 'col_amt' => 'المبلغ الإجمالي',
            'empty' => 'لا توجد قيود مسجلة في هذه الفترة بعد.', 'general' => 'عام (المركز الرئيسي)',
            'print_date' => 'تاريخ الطباعة:', 'confirm_close' => 'هل أنت متأكد من إغلاق الفترة؟ لن يتم قبول أي قيود جديدة.'
        ],
        'en' => [
            'back' => 'Back to List', 'print' => 'Print Summary', 'edit' => 'Edit', 'close_p' => 'Close Period',
            'subtitle' => 'Fiscal Period Summary', 'p_name' => 'Period Name:', 'date_range' => 'Date Range:', 'branch' => 'Branch:',
            'status' => 'Status:', 'st_open' => 'Open (Accepting Entries)', 'st_closed' => 'Closed (Locked)',
            'stat_tx' => 'Total Posted Entries', 'stat_vol' => 'Financial Volume (Total Amount)',
            'tx_title' => 'Sample of Latest Entries within this Period',
            'col_date' => 'Entry Date', 'col_ref' => 'Entry No.', 'col_desc' => 'Description', 'col_amt' => 'Total Amount',
            'empty' => 'No entries posted in this period yet.', 'general' => 'General (HQ)',
            'print_date' => 'Print Date:', 'confirm_close' => 'Are you sure you want to close this period? No new entries will be accepted.'
        ]
    ][$isRtl ? 'ar' : 'en'];

    $pName = $isRtl ? ($period->name_ar ?? '') : ($period->name_en ?: ($period->name_ar ?? ''));
    $branchName = !empty($period->branch_name) ? ($isRtl ? $period->branch_name : ($period->branch_name_en ?: $period->branch_name)) : $t['general'];
    
    $isOpen = ($period->status ?? '') === 'open';
?>

<style>
    :root { 
        --c-fp: #6366f1; --c-fp-dark: #4338ca; --c-fp-light: #e0e7ff;
        --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; 
    }
    
    .fp-show-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: #f1f5f9;}
    .print-only-header { display: none; }

    .header-bar { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); padding: 24px 30px; margin-bottom: 30px; border-bottom: 1px solid rgba(226,232,240,0.8); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; position: sticky; top:0; z-index:10;}
    .btn-action { height: 42px; padding: 0 16px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 0.9rem; font-weight: 800; cursor: pointer; gap: 6px; transition:0.3s;}
    .btn-action:hover { background: var(--c-fp-light); color: var(--c-fp); border-color: #a5b4fc; }
    
    .btn-print { background: var(--c-text-dark); color: #ffffff; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; font-size: 0.95rem; }
    .btn-close-p { background: #dc2626; color: #fff; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.95rem; text-decoration:none; }

    .info-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px 32px; border-left: 5px solid var(--c-fp); margin: 0 30px 30px 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
    @media(max-width:900px){ .info-grid { grid-template-columns: repeat(2, 1fr); } }
    .info-item h5 { margin: 0 0 6px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .info-item p { margin: 0; font-size: 1.1rem; font-weight: 900; color: var(--c-text-dark); font-family:inherit;}

    .kpi-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 0 30px 30px 30px; }
    .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; display:flex; justify-content:space-between; align-items:center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
    .kpi-card h4 { margin: 0 0 8px 0; font-size: 0.9rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-card p { margin: 0; font-size: 2rem; font-weight: 900; font-family: monospace; color: var(--c-fp-dark); }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; margin: 0 30px 30px 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .tx-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; text-align: start; }
    .tx-table th { padding: 18px 24px; background: #f8fafc; color: var(--c-text-muted); font-weight: 900; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
    .tx-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; font-weight:700;}

    /* A4 Print Layout */
    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        body * { visibility: hidden !important; }
        .fp-show-wrapper, .fp-show-wrapper * { visibility: visible !important; }
        .fp-show-wrapper { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; background-color: #ffffff !important;}
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

<div class="fp-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
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
            <?= $t['p_name'] ?> <?= htmlspecialchars((string)$pName) ?><br>
            <?= $t['print_date'] ?> <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:24px;">
            <a href="/ERP/accounting/fiscal-periods" class="btn-action" title="<?= $t['back'] ?>"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.8rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars((string)$pName) ?></h2>
                <div style="display:flex; gap:12px; margin-top:6px;">
                    <span style="color:var(--c-fp-dark); font-weight:900; font-family:monospace; font-size:1.05rem;"><i class="ph-bold ph-calendar-blank"></i> <?= htmlspecialchars((string)($period->start_date ?? '')) ?> ➔ <?= htmlspecialchars((string)($period->end_date ?? '')) ?></span>
                </div>
            </div>
        </div>
        <div style="display:flex; gap:12px;">
            <?php if($isOpen): ?>
                <a href="/ERP/accounting/fiscal-periods/<?= $period->id ?? 0 ?>/edit" class="btn-action" style="background:var(--c-fp); color:#fff; border-color:var(--c-fp);"><i class="ph-bold ph-pencil"></i> <?= $t['edit'] ?></a>
                <a href="/ERP/accounting/fiscal-periods/<?= $period->id ?? 0 ?>/close" class="btn-close-p" onclick="return confirm('<?= $t['confirm_close'] ?>');"><i class="ph-bold ph-lock-key"></i> <?= $t['close_p'] ?></a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="margin: 0 30px 20px 30px; background: #ecfdf5; color: #059669; padding: 14px 18px; border-radius: 12px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="margin: 0 30px 20px 30px; background: #fef2f2; color: #dc2626; padding: 14px 18px; border-radius: 12px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="info-card">
        <div class="info-grid">
            <div class="info-item">
                <h5><?= $t['p_name'] ?></h5>
                <p style="color:var(--c-fp-dark);"><i class="ph-bold ph-bookmark"></i> <?= htmlspecialchars($pName) ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['date_range'] ?></h5>
                <p style="font-family:monospace; font-size:1rem;"><?= htmlspecialchars((string)($period->start_date ?? '')) ?> <br> <?= htmlspecialchars((string)($period->end_date ?? '')) ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['branch'] ?></h5>
                <p style="color:#0ea5e9;"><i class="ph-bold ph-git-branch"></i> <?= htmlspecialchars($branchName) ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['status'] ?></h5>
                <?php if(!$isOpen): ?>
                    <p style="color:#dc2626;"><i class="ph-fill ph-lock-key"></i> <?= $t['st_closed'] ?></p>
                <?php else: ?>
                    <p style="color:#059669;"><i class="ph-fill ph-lock-open"></i> <?= $t['st_open'] ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php if(!empty($period->notes)): ?>
            <div style="margin-top:20px; padding-top:20px; border-top:1px dashed var(--c-border);">
                <h5 style="margin: 0 0 6px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase;">ملاحظات:</h5>
                <p style="margin:0; font-weight:700; color:#334155;"><?= nl2br(htmlspecialchars((string)$period->notes)) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="kpi-row">
        <div class="kpi-card" style="border-bottom: 5px solid #0f172a;">
            <div>
                <h4><?= $t['stat_tx'] ?></h4>
                <p><?= number_format((float)($stats->total_entries ?? 0)) ?></p>
            </div>
            <i class="ph-duotone ph-list-numbers" style="font-size:3rem; color:#e2e8f0;"></i>
        </div>
        <div class="kpi-card" style="border-bottom: 5px solid var(--c-fp);">
            <div>
                <h4 style="color:var(--c-fp-dark);"><?= $t['stat_vol'] ?></h4>
                <p style="color:var(--c-fp-dark);"><?= number_format((float)($stats->total_volume ?? 0), 2) ?> <span style="font-size:1rem;"><?= $currency ?></span></p>
            </div>
            <i class="ph-duotone ph-coins" style="font-size:3rem; color:var(--c-fp-light);"></i>
        </div>
    </div>

    <div class="table-card">
        <div class="table-title-print" style="padding:22px 30px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:900; color:var(--c-text-dark); font-size:1.1rem; display:flex; justify-content:space-between;">
            <span><i class="ph-bold ph-files" style="color:var(--c-fp); font-size:1.4rem; vertical-align:middle; margin-inline-end:8px;"></i> <?= $t['tx_title'] ?></span>
            <span style="font-size:0.85rem; color:#fff; background:#10b981; padding:4px 12px; border-radius:6px; font-weight:900;">POSTED ✓</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="tx-table">
                <thead>
                    <tr>
                        <th style="width: 15%;"><?= $t['col_date'] ?></th>
                        <th style="width: 20%;"><?= $t['col_ref'] ?></th>
                        <th style="width: 45%;"><?= $t['col_desc'] ?></th>
                        <th style="width: 20%; text-align:center;"><?= $t['col_amt'] ?> (<?= $currency ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($latestEntries)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:60px; color:#94a3b8; font-weight:800; font-size:1.1rem; border-bottom:none;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach($latestEntries as $je): ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:#475569; font-size:1.05rem;"><?= htmlspecialchars((string)($je->entry_date ?? '')) ?></td>
                            <td><a href="/ERP/accounting/journal-entries/<?= htmlspecialchars((string)($je->id ?? '')) ?>" style="font-family:monospace; font-weight:900; color:var(--c-fp-dark); text-decoration:none; font-size:1.05rem;">#<?= htmlspecialchars((string)($je->entry_number ?? '')) ?></a></td>
                            <td style="font-weight:800; color:var(--c-text-dark);"><?= htmlspecialchars((string)($je->description ?? '')) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#0f172a; font-size:1.15rem;"><?= number_format((float)($je->total_amount ?? 0), 2) ?></td>
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