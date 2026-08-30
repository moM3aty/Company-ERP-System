<?php
// Path: resources/views/treasury/accounts/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';
?>

<style>
    :root { --c-acc: #0891b2; --c-acc-dark: #0e7490; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .acc-show-wrapper { max-width: 1050px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; }
    .btn-print { background: var(--c-acc-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .card-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; border-top: 4px solid var(--c-acc); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px){ .grid-3 { grid-template-columns: 1fr; } }
    .info-item h5 { margin: 0 0 6px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .info-item p { margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--c-text-dark); }

    .report-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    .report-table th { padding: 12px 16px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; border-bottom: 2px solid #cbd5e1; text-align: start; font-size: 0.75rem; }
    .report-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        body { background: #fff !important; }
        .acc-show-wrapper { max-width: 100% !important; }
        .card-box { border: 1px solid #000 !important; box-shadow: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="acc-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/accounts" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($account->name_ar) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-acc-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($account->code) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة كشف الحساب</button>
            <a href="/ERP/treasury/accounts/<?= $account->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="تعديل الحساب"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <div class="card-box">
        <div class="grid-3">
            <div class="info-item">
                <h5>كود الحساب المالي</h5>
                <p style="font-family:monospace; color:var(--c-acc-dark);"><?= htmlspecialchars($account->code) ?></p>
            </div>
            <div class="info-item">
                <h5>اسم الحساب (عربي / إنجليزي)</h5>
                <p><?= htmlspecialchars($account->name_ar) ?> <?= !empty($account->name_en) ? "({$account->name_en})" : '' ?></p>
            </div>
            <div class="info-item">
                <h5>الرصيد الحالي المتوفر</h5>
                <p style="font-family:monospace; color:#059669; font-size:1.4rem;"><?= number_format((float)($account->current_balance ?? 0), 2) ?></p>
            </div>
        </div>
    </div>

    <div class="card-box" style="padding:0; overflow:hidden;">
        <div style="padding:16px 24px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:800; color:var(--c-text-dark);">
            <i class="ph-bold ph-list-dashes" style="color:var(--c-acc);"></i> دفتر الحركات الأخيرة المؤثرة على الحساب
        </div>
        <table class="report-table">
            <thead>
                <tr>
                    <th>القيد</th>
                    <th>التاريخ</th>
                    <th>البيان والتفاصيل</th>
                    <th style="text-align:center;">مدين (+)</th>
                    <th style="text-align:center;">دائن (-)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($transactions)): ?>
                    <tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">لا توجد حركات مالية مرحلة مسجلة على هذا الحساب حتى الآن.</td></tr>
                <?php else: foreach($transactions as $tx): ?>
                    <tr>
                        <td><a href="/ERP/accounting/journal-entries/<?= $tx->journal_entry_id ?>" style="font-family:monospace; font-weight:900; color:var(--c-acc-dark); text-decoration:none;">#<?= htmlspecialchars($tx->entry_number) ?></a></td>
                        <td style="font-family:monospace; font-size:0.85rem;"><?= htmlspecialchars($tx->entry_date) ?></td>
                        <td style="font-weight:700; color:var(--c-text-dark);"><?= htmlspecialchars($tx->entry_desc ?: 'حركة مالية') ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= (float)$tx->debit > 0 ? number_format((float)$tx->debit, 2) : '-' ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= (float)$tx->credit > 0 ? number_format((float)$tx->credit, 2) : '-' ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>