<?php
// Path: resources/views/accounting/journals/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = 'EGP';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

function getEntryStatusBadge($status) {
    $map = [
        'draft'     => ['bg' => '#fef3c7', 'color' => '#d97706', 'label' => 'مسودة (Draft)'],
        'posted'    => ['bg' => '#e0e7ff', 'color' => '#4338ca', 'label' => 'مرحّل (Posted)'],
        'cancelled' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => 'ملغى (Cancelled)']
    ];
    $s = $map[$status] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $status];
    return "<span style='background:{$s['bg']}; color:{$s['color']}; padding:4px 12px; border-radius:6px; font-weight:800; font-size:0.8rem; border:1px solid currentColor;'>{$s['label']}</span>";
}
?>

<style>
    :root { --c-je: #4f46e5; --c-je-dark: #3730a3; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .voucher-wrapper { max-width: 1000px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .print-only-header { display: none; }

    .voucher-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .voucher-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; border-top: 4px solid var(--c-je); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; }
    .info-item h5 { margin: 0 0 4px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .info-item p { margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--c-text-dark); }

    .items-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; margin-bottom: 24px; }
    .items-table th { padding: 12px 16px; background: #f1f5f9; color: var(--c-text-muted); font-weight: 800; font-size: 0.78rem; border-bottom: 2px solid #cbd5e1; }
    .items-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .signatures-block { display: none; margin-top: 50px; grid-template-columns: repeat(3, 1fr); text-align: center; font-weight: 800; border-top: 2px dashed #cbd5e1; padding-top: 20px; }

    @media print {
        .nt-sidebar, .top-header, .voucher-header, header, aside { display: none !important; }
        body { background: #fff !important; }
        .voucher-wrapper { max-width: 100% !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .voucher-card { border: 1px solid #000 !important; box-shadow: none !important; }
        
        .print-only-header { display: flex !important; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--c-je); padding-bottom: 15px; margin-bottom: 20px; }
        .print-logo-box img { max-height: 50px; }
        .signatures-block { display: grid !important; }
    }
</style>

<div class="voucher-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <!-- 📄 ترويسة الطباعة -->
    <div class="print-only-header">
        <div class="print-logo-box" style="display:flex; align-items:center; gap:15px;">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:2.5rem; color:var(--c-je);"></i>
            <?php endif; ?>
            <h2 style="margin:0; font-size:1.5rem; color:#000; font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
        </div>
        <div style="text-align:end; font-size:0.85rem; font-weight:bold;">
            سند قيد يومية (Journal Voucher)<br>
            تاريخ الطباعة: <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <!-- رأس الصفحة الطبيعي -->
    <div class="voucher-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/accounting/journal-entries" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;">سند قيد يومية رقم: <span style="color:var(--c-je); font-family:monospace;"><?= htmlspecialchars($entry->entry_number) ?></span></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;">عرض التفاصيل المالية واعتماد القيد.</p>
            </div>
        </div>
        <div>
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة سند القيد</button>
        </div>
    </div>

    <!-- البطاقة الرئيسية -->
    <div class="voucher-card">
        <div class="grid-3">
            <div class="info-item">
                <h5>تاريخ القيد</h5>
                <p style="font-family:monospace; color:var(--c-je-dark);"><?= htmlspecialchars($entry->entry_date) ?></p>
            </div>
            <div class="info-item">
                <h5>رقم المرجع / المستند</h5>
                <p><?= htmlspecialchars($entry->reference_number ?? '---') ?></p>
            </div>
            <div class="info-item">
                <h5>حالة القيد</h5>
                <div><?= getEntryStatusBadge($entry->status) ?></div>
            </div>
        </div>

        <div style="margin-bottom:20px; font-weight:800; color:var(--c-text-dark); font-size:1.05rem;">
            البيان العام: <span style="font-weight:normal; color:#334155;"><?= htmlspecialchars($entry->description) ?></span>
        </div>

        <!-- الجدول التفصيلي -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 15%;">رمز الحساب</th>
                    <th style="width: 30%;">اسم الحساب</th>
                    <th style="width: 25%;">الشرح / البيان</th>
                    <th style="width: 15%; text-align: center;">مدين (+)</th>
                    <th style="width: 15%; text-align: center;">دائن (-)</th>
                </tr>
            </thead>
            <tbody>
                <?php $tDebit = 0; $tCredit = 0; foreach ($items as $item): $tDebit += $item->debit; $tCredit += $item->credit; ?>
                    <tr>
                        <td style="font-family:monospace; font-weight:900; color:var(--c-je-dark);"><?= htmlspecialchars($item->acc_code) ?></td>
                        <td style="font-weight:800; color:var(--c-text-dark);"><?= htmlspecialchars($item->acc_name) ?></td>
                        <td style="color:#64748b; font-weight:600;"><?= htmlspecialchars($item->description ?? '---') ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format((float)$item->debit, 2) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format((float)$item->credit, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc; font-weight:900;">
                    <td colspan="3" style="text-align:start; padding:14px 16px; border-top:2px solid #cbd5e1;">الإجمالي المالي</td>
                    <td style="text-align:center; font-family:monospace; font-size:1.1rem; color:#059669; border-top:2px solid #cbd5e1;"><?= number_format($tDebit, 2) ?></td>
                    <td style="text-align:center; font-family:monospace; font-size:1.1rem; color:#dc2626; border-top:2px solid #cbd5e1;"><?= number_format($tCredit, 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- بلوك التوقيعات للطباعة الرسمية -->
        <div class="signatures-block">
            <div>المحاسب المسؤول<br><br>...........................</div>
            <div>المراجع المالي<br><br>...........................</div>
            <div>اعتماد المدير المالي<br><br>...........................</div>
        </div>
    </div>
</div>