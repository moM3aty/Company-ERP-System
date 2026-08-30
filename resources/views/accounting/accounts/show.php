<?php
// Path: resources/views/accounting/accounts/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = 'EGP';

// بيانات الشركة للترويسة في الطباعة
global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

function getAccountTypeBadge($type) {
    $map = [
        'asset'     => ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => 'أصول (Assets)'],
        'liability' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => 'التزامات (Liabilities)'],
        'equity'    => ['bg' => '#eff6ff', 'color' => '#2563eb', 'label' => 'حقوق ملكية (Equity)'],
        'revenue'   => ['bg' => '#f0fdf4', 'color' => '#16a34a', 'label' => 'إيرادات (Revenues)'],
        'expense'   => ['bg' => '#fff7ed', 'color' => '#c2410c', 'label' => 'مصروفات (Expenses)']
    ];
    $s = $map[$type] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $type];
    return "<span class='acc-badge' style='background:{$s['bg']}; color:{$s['color']}; padding:4px 12px; border-radius:6px; font-weight:800; font-size:0.8rem; border:1px solid currentColor;'>{$s['label']}</span>";
}
?>

<style>
    :root { --c-acc: #059669; --c-acc-dark: #047857; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .card-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .print-only-header { display: none; } /* مخفي في الشاشة العادية */

    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .header-actions { display: flex; gap: 10px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); transition: 0.2s; font-size:1.2rem; }
    .btn-action:hover { border-color: var(--c-acc); color: var(--c-acc); }
    .btn-print { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #000000; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

    .info-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; border-top: 4px solid var(--c-acc); margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
    @media (max-width: 768px) { .info-grid { grid-template-columns: repeat(2, 1fr); } }
    .info-item h5 { margin: 0 0 6px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .info-item p { margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--c-text-dark); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media (max-width: 768px) { .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; }
    .kpi-card h4 { margin: 0 0 8px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-card p { margin: 0; font-size: 1.6rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .ledger-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .ledger-table th { padding: 14px 18px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; font-size: 0.75rem; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
    .ledger-table td { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    /* ========================================================
       PRINT STYLES - تنسيقات الطباعة الخرافية 
       ======================================================== */
    @media print {
        /* إخفاء القوائم والأزرار */
        .nt-sidebar, .top-header, .header-actions, header, aside, .btn-action { display: none !important; }
        
        /* تمدد الصفحة وتبييض الخلفية */
        body { background-color: #ffffff !important; margin: 0 !important; padding: 0 !important; }
        .card-wrapper { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        
        /* إجبار المتصفح على طباعة الألوان الخلفية (Background Colors) */
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        
        /* إزالة الظلال والحواف الدائرية للطباعة النظيفة */
        .info-box, .kpi-card, .table-card { border: 1px solid #000 !important; box-shadow: none !important; border-radius: 4px !important; margin-bottom: 15px !important; break-inside: avoid; }
        .card-header { border-bottom: none !important; margin-bottom: 0 !important; }
        
        /* إظهار الترويسة الخاصة بالطباعة */
        .print-only-header { display: flex !important; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--c-acc); padding-bottom: 15px; margin-bottom: 20px; }
        .print-logo-box { display: flex; align-items: center; gap: 15px; }
        .print-logo-box img { max-height: 50px; }
        .print-logo-box h2 { margin: 0; font-size: 1.5rem; color: #000; font-weight: 900; }
        .print-meta { text-align: end; font-size: 0.8rem; font-weight: bold; color: #555; }
        
        /* ضبط البادجات */
        .acc-badge { border: 1px solid #000 !important; }
    }
</style>

<div class="card-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <!-- 📄 ترويسة الطباعة (تظهر فقط عند طباعة الورقة) -->
    <div class="print-only-header">
        <div class="print-logo-box">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:2.5rem; color:var(--c-acc);"></i>
            <?php endif; ?>
            <h2><?= htmlspecialchars($cName) ?></h2>
        </div>
        <div class="print-meta">
            تقرير كشف حساب مالي<br>
            تاريخ الطباعة: <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <!-- رأس الصفحة الطبيعي -->
    <div class="card-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/accounting/chart-of-accounts" class="btn-action"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($account->name_ar ?? '---') ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-acc-dark); font-weight:800; font-family:monospace; font-size:1.1rem;"><?= htmlspecialchars($account->code ?? '---') ?></p>
            </div>
        </div>
        <div class="header-actions">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة الكشف</button>
            <a href="/ERP/accounting/chart-of-accounts/<?= $account->id ?>/edit" class="btn-print" style="background:var(--c-acc); text-decoration:none;"><i class="ph-bold ph-pencil"></i> تعديل</a>
        </div>
    </div>

    <!-- بطاقة المعلومات -->
    <div class="info-box">
        <div class="info-grid">
            <div class="info-item">
                <h5>رقم الحساب (الكود)</h5>
                <p style="font-family:monospace; color:var(--c-acc-dark);"><?= htmlspecialchars($account->code ?? '---') ?></p>
            </div>
            <div class="info-item">
                <h5>طبيعة الحساب</h5>
                <div><?= getAccountTypeBadge($account->type ?? '') ?></div>
            </div>
            <div class="info-item">
                <h5>الحساب الرئيسي الأب</h5>
                <p><?= htmlspecialchars($account->parent_name ?? 'حساب رئيسي مستقل (جذر)') ?></p>
            </div>
            <div class="info-item">
                <h5>المستوى في الدليل</h5>
                <p>المستوى <?= (int)($account->account_level ?? 1) ?></p>
            </div>
        </div>
    </div>

    <!-- ملخص الأرصدة (KPIs) -->
    <div class="kpi-row">
        <div class="kpi-card">
            <h4>الرصيد الافتتاحي</h4>
            <p><?= number_format((float)($account->opening_balance ?? 0), 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #059669;">
            <h4 style="color:#059669;">إجمالي المدين (+) للمدة</h4>
            <p style="color:#059669;"><?= number_format((float)($totals->total_debit ?? 0), 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #dc2626;">
            <h4 style="color:#dc2626;">إجمالي الدائن (-) للمدة</h4>
            <p style="color:#dc2626;"><?= number_format((float)($totals->total_credit ?? 0), 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #2563eb; background:#eff6ff;">
            <h4 style="color:#2563eb;">الرصيد الحالي الصافي</h4>
            <p style="color:#2563eb;"><?= number_format((float)($account->current_balance ?? 0), 2) ?> <span style="font-size:0.8rem; font-weight:normal;"><?= $currency ?></span></p>
        </div>
    </div>

    <!-- جدول القيود -->
    <div class="table-card">
        <div style="padding:16px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:800; color:var(--c-text-dark); display:flex; justify-content:space-between; align-items:center;">
            <span><i class="ph-bold ph-list-dashes" style="color:var(--c-acc);"></i> الحركات التفصيلية (كشف الحساب)</span>
            <span style="font-size:0.8rem; color:var(--c-text-muted); background:#e2e8f0; padding:2px 8px; border-radius:4px;">مرحّل فقط</span>
        </div>
        <table class="ledger-table">
            <thead>
                <tr>
                    <th style="width: 15%;">تاريخ القيد</th>
                    <th style="width: 15%;">رقم المرجع</th>
                    <th style="width: 35%;">البيان / الوصف</th>
                    <th style="width: 15%; text-align: center;">مدين (+)</th>
                    <th style="width: 15%; text-align: center;">دائن (-)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لم يتم تسجيل أي حركات مالية مرحلة على هذا الحساب حتى الآن.</td></tr>
                <?php else: foreach ($movements as $m): ?>
                    <tr>
                        <td style="font-family:monospace; font-weight:700;"><?= htmlspecialchars($m->entry_date ?? '') ?></td>
                        <td>
                            <a href="/ERP/accounting/journal-entries/<?= $m->journal_entry_id ?? '' ?>" style="font-family:monospace; font-weight:900; color:var(--c-acc); text-decoration:none;">
                                #<?= htmlspecialchars($m->entry_number ?? '') ?>
                            </a>
                        </td>
                        <td style="font-weight:700; color:var(--c-text-dark);"><?= htmlspecialchars($m->entry_desc ?? $m->description ?? '---') ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format((float)($m->debit ?? 0), 2) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format((float)($m->credit ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>