<?php
// Path: resources/views/accounting/accounts/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$t = [
    'ar' => [
        'back' => 'رجوع للدليل', 'print' => 'طباعة كشف الحساب', 'edit' => 'تعديل',
        'subtitle' => 'كشف حساب مالي تفصيلي (Account Ledger)', 'code' => 'رقم الحساب', 'type' => 'طبيعة وتصنيف الحساب',
        'parent' => 'الحساب الرئيسي', 'level' => 'المستوى الدفتري', 'branch' => 'الفرع / الموقع', 'op_bal' => 'الرصيد الافتتاحي',
        'dr' => 'إجمالي المدين (+) للمدة', 'cr' => 'إجمالي الدائن (-) للمدة', 'cur_bal' => 'الرصيد الحالي الصافي',
        'tx_title' => 'الحركات التفصيلية المعتمدة (دفتر الأستاذ للحساب)',
        'col_date' => 'التاريخ', 'col_ref' => 'رقم القيد', 'col_desc' => 'البيان والتفاصيل',
        'col_dr' => 'مدين (+)', 'col_cr' => 'دائن (-)', 'empty' => 'لم يتم تسجيل أي حركات مالية مرحلة على هذا الحساب.',
        'root' => 'حساب جذر (مستقل)', 'general' => 'عام (الشركة)',
        'type_asset' => 'أصول (Asset)', 'type_liab' => 'التزامات (Liability)', 'type_eqt' => 'حقوق ملكية (Equity)', 'type_rev' => 'إيرادات (Revenue)', 'type_exp' => 'مصروفات (Expense)',
        'print_date' => 'تاريخ الاستخراج:', 'print_acc' => 'اسم الحساب:'
    ],
    'en' => [
        'back' => 'Back to COA', 'print' => 'Print Ledger', 'edit' => 'Edit',
        'subtitle' => 'Detailed Account Ledger Statement', 'code' => 'Account Code', 'type' => 'Account Nature',
        'parent' => 'Parent Account', 'level' => 'Ledger Level', 'branch' => 'Branch / Site', 'op_bal' => 'Opening Balance',
        'dr' => 'Total Debit (+) Period', 'cr' => 'Total Credit (-) Period', 'cur_bal' => 'Net Current Balance',
        'tx_title' => 'Posted Transactions (Account Ledger)',
        'col_date' => 'Date', 'col_ref' => 'Entry No.', 'col_desc' => 'Description & Details',
        'col_dr' => 'Debit (+)', 'col_cr' => 'Credit (-)', 'empty' => 'No posted financial transactions recorded yet.',
        'root' => 'Independent Root Account', 'general' => 'General (Company Level)',
        'type_asset' => 'Assets', 'type_liab' => 'Liabilities', 'type_eqt' => 'Equity', 'type_rev' => 'Revenues', 'type_exp' => 'Expenses',
        'print_date' => 'Report Date:', 'print_acc' => 'Account Name:'
    ]
][$isRtl ? 'ar' : 'en'];

function getAccountTypeBadgePrint($type, $t) {
    $map = [
        'asset'     => ['bg' => '#d1fae5', 'color' => '#059669', 'label' => $t['type_asset']],
        'liability' => ['bg' => '#ffe4e6', 'color' => '#e11d48', 'label' => $t['type_liab']],
        'equity'    => ['bg' => '#e0e7ff', 'color' => '#4338ca', 'label' => $t['type_eqt']],
        'revenue'   => ['bg' => '#dbeafe', 'color' => '#0284c7', 'label' => $t['type_rev']],
        'expense'   => ['bg' => '#ffedd5', 'color' => '#ea580c', 'label' => $t['type_exp']]
    ];
    $s = $map[$type] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $type];
    return "<span class='badge-print' style='background:{$s['bg']}; color:{$s['color']}; padding:4px 12px; border-radius:6px; font-weight:900; font-size:0.85rem; border:1px solid currentColor;'>{$s['label']}</span>";
}

$aName = $isRtl ? ($account->name_ar ?: $account->name_en) : ($account->name_en ?: $account->name_ar);
$pName = $isRtl ? ($account->parent_name ?: ($account->parent_code ?? $t['root'])) : (($account->parent_code ?? $t['root']));
$branchBadge = !empty($account->branch_name) ? ($isRtl ? $account->branch_name : ($account->branch_name_en ?: $account->branch_name)) : $t['general'];
?>

<style>
    :root { 
        --brand-primary: #059669; --brand-primary-dark: #047857; --brand-primary-light: #d1fae5; 
        --surface: #ffffff; --surface-hover: #f8fafc; --bg-body: #f1f5f9;
        --text-main: #0f172a; --text-muted: #64748b; --border-color: #e2e8f0;
        --radius-lg: 24px; --radius-md: 16px;
        --shadow-soft: 0 10px 30px -10px rgba(15, 23, 42, 0.08);
        --shadow-hover: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
    }
    .acc-show-wrapper { max-width: 1200px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: var(--bg-body);}
    
    .print-only-header { display: none; }

    .header-bar { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); padding: 24px 30px; margin-bottom: 30px; border-bottom: 1px solid rgba(226,232,240,0.8); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; position: sticky; top:0; z-index:10;}
    .btn-action { width: 48px; height: 48px; border-radius: 14px; background: var(--surface); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--text-muted); font-size: 1.3rem; transition: 0.3s; box-shadow: var(--shadow-soft);}
    .btn-action:hover { background: var(--brand-primary-light); color: var(--brand-primary); border-color: #6ee7b7; transform: translateX(<?= $isRtl ? '4px' : '-4px' ?>);}
    .btn-print { background: var(--text-main); color: #ffffff; border: none; padding: 14px 26px; border-radius: 12px; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; font-size: 0.95rem; box-shadow: var(--shadow-soft);}
    .btn-print:hover { background: #000000; transform: translateY(-2px); box-shadow: var(--shadow-hover);}

    .info-box { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 32px; border-top: 5px solid var(--brand-primary); margin: 0 30px 30px 30px; box-shadow: var(--shadow-soft); transition: 0.4s;}
    .info-box:hover { box-shadow: var(--shadow-hover); transform: translateY(-3px);}
    .info-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 24px; }
    @media (max-width: 1100px) { .info-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 768px) { .info-grid { grid-template-columns: repeat(2, 1fr); } }
    .info-item { background: var(--surface-hover); padding: 20px; border-radius: 16px; border: 1px solid #f1f5f9; }
    .info-item h5 { margin: 0 0 8px 0; font-size: 0.8rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase; }
    .info-item p { margin: 0; font-size: 1.25rem; font-weight: 900; color: var(--text-main); font-family:monospace;}

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 0 30px 30px 30px; }
    @media (max-width: 900px) { .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; box-shadow: var(--shadow-soft); transition: 0.3s;}
    .kpi-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); }
    .kpi-card h4 { margin: 0 0 10px 0; font-size: 0.85rem; color: var(--text-muted); font-weight: 800; }
    .kpi-card p { margin: 0; font-size: 1.8rem; font-weight: 900; font-family: monospace; color: var(--text-main); letter-spacing:-0.5px;}

    .table-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; margin: 0 30px 30px 30px; box-shadow: var(--shadow-soft); }
    .ledger-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; text-align: start; }
    .ledger-table th { padding: 18px 24px; background: var(--surface-hover); color: var(--text-muted); font-weight: 900; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; }
    .ledger-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; font-weight:700;}
    .ledger-table tr:hover td { background: var(--surface-hover); }

    /* ========================================================
       BULLETPROOF PRINT STYLES - تنسيقات الطباعة الخارقة (A4)
       ======================================================== */
    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        
        /* 1. إخفاء كل شيء في الصفحة باستثناء الحاوية الرئيسية الخاصة بنا */
        body * { visibility: hidden !important; }
        .acc-show-wrapper, .acc-show-wrapper * { visibility: visible !important; }
        
        /* 2. إعادة ضبط موقع الحاوية لتكون في أعلى وأقصى اليمين/اليسار للصفحة المطبوعة */
        .acc-show-wrapper {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background-color: #ffffff !important;
        }

        /* 3. إخفاء أزرار التحكم والقوائم داخل الحاوية */
        .header-bar, .btn-action, .btn-print, .table-pagination-nav { 
            display: none !important; 
            height: 0 !important; 
            overflow: hidden !important;
        }
        
        /* 4. إظهار الترويسة المخصصة للطباعة */
        .print-only-header { 
            display: flex !important; 
            align-items: center !important; 
            justify-content: space-between !important; 
            border-bottom: 3px solid #000 !important; 
            padding-bottom: 12px !important; 
            margin-bottom: 20px !important; 
        }
        .print-logo-box { display: flex !important; align-items: center !important; gap: 15px !important; }
        .print-logo-box img { max-height: 50px !important; }
        .print-logo-box h2 { margin: 0 !important; font-size: 1.6rem !important; color: #000 !important; font-weight: 900 !important; }
        .print-meta { text-align: end !important; font-size: 0.9rem !important; font-weight: bold !important; color: #000 !important; }
        
        /* 5. إجبار المتصفح على طباعة الألوان الخلفية */
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        
        /* 6. تحويل البطاقات إلى إطارات صلبة للطباعة */
        .info-box { 
            border: 2px solid #000 !important; 
            box-shadow: none !important; 
            border-radius: 6px !important; 
            margin: 0 0 20px 0 !important; 
            padding: 15px !important;
            page-break-inside: avoid;
        }
        .info-grid { 
            display: grid !important; 
            grid-template-columns: repeat(5, 1fr) !important; /* إجبار 5 أعمدة */
            gap: 10px !important; 
        }
        .info-item { 
            background: transparent !important; 
            border: 1px solid #000 !important; 
            padding: 10px !important; 
            border-radius: 4px !important;
        }
        
        .kpi-row { 
            display: grid !important; 
            grid-template-columns: repeat(4, 1fr) !important; /* إجبار 4 أعمدة */
            gap: 10px !important; 
            margin: 0 0 20px 0 !important; 
        }
        .kpi-card { 
            border: 2px solid #000 !important; 
            box-shadow: none !important; 
            padding: 12px !important; 
            border-radius: 6px !important; 
            page-break-inside: avoid;
        }
        .info-item h5, .kpi-card h4 { color: #000 !important; font-size: 9pt !important; }
        .info-item p, .kpi-card p { color: #000 !important; font-size: 11pt !important; }
        
        /* 7. تظبيط جدول الحركات */
        .table-card { 
            border: 2px solid #000 !important; 
            box-shadow: none !important; 
            margin: 0 !important; 
            border-radius: 6px !important;
            page-break-inside: auto;
        }
        .table-title-print { 
            background: #e2e8f0 !important; 
            color: #000 !important; 
            border-bottom: 2px solid #000 !important;
        }
        .ledger-table { border-collapse: collapse !important; width: 100% !important; }
        .ledger-table th { 
            background: #f1f5f9 !important; 
            color: #000 !important; 
            border-bottom: 2px solid #000 !important; 
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
            font-weight: bold !important; 
            font-size: 9pt !important;
            padding: 8px !important;
        }
        .ledger-table td { 
            border: 1px solid #000 !important; 
            color: #000 !important; 
            padding: 8px !important;
            font-size: 9pt !important;
        }
        
        a { text-decoration: none !important; color: #000 !important; }
        .badge-print { border: 1px solid #000 !important; }
    }
</style>

<div class="acc-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
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
            <?= $t['print_acc'] ?> <?= htmlspecialchars($aName) ?> (<?= htmlspecialchars($account->code) ?>)<br>
            <?= $t['print_date'] ?> <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <!-- رأس الصفحة الطبيعي -->
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:24px;">
            <a href="/ERP/accounting/chart-of-accounts" class="btn-action" title="<?= $t['back'] ?>"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.8rem; color:var(--text-main); font-weight:900;"><?= htmlspecialchars($aName) ?></h2>
                <p style="margin:6px 0 0 0; color:var(--brand-primary-dark); font-weight:900; font-family:monospace; font-size:1.15rem;"><?= htmlspecialchars($account->code ?? '---') ?></p>
            </div>
        </div>
        <div style="display:flex; gap:12px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
            <a href="/ERP/accounting/chart-of-accounts/<?= $account->id ?>/edit" class="btn-print" style="background:linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)); text-decoration:none; border:none;"><i class="ph-bold ph-pencil"></i> <?= $t['edit'] ?></a>
        </div>
    </div>

    <!-- بطاقة المعلومات الأساسية -->
    <div class="info-box">
        <div class="info-grid">
            <div class="info-item">
                <h5><?= $t['code'] ?></h5>
                <p style="font-family:monospace; color:var(--brand-primary-dark); font-size:1.4rem;"><?= htmlspecialchars($account->code ?? '---') ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['type'] ?></h5>
                <div style="margin-top:8px;"><?= getAccountTypeBadgePrint($account->type ?? '', $t) ?></div>
            </div>
            <div class="info-item">
                <h5><?= $t['parent'] ?></h5>
                <p style="font-family:inherit; font-size:1.1rem; color:#475569;"><?= htmlspecialchars($pName) ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['branch'] ?></h5>
                <p style="font-family:inherit; font-size:1.1rem; color:#0ea5e9; font-weight:900;"><i class="ph-bold ph-git-branch"></i> <?= htmlspecialchars($branchBadge) ?></p>
            </div>
            <div class="info-item">
                <h5><?= $t['level'] ?></h5>
                <p style="font-family:monospace; font-size:1.4rem; color:var(--text-main);">Level <?= (int)($account->account_level ?? 1) ?></p>
            </div>
        </div>
    </div>

    <!-- ملخص الأرصدة (KPIs) -->
    <div class="kpi-row">
        <div class="kpi-card" style="border-bottom: 4px solid #64748b;">
            <h4><?= $t['op_bal'] ?></h4>
            <p style="color:#0f172a;"><?= number_format((float)($account->opening_balance ?? 0), 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 4px solid #10b981;">
            <h4 style="color:#059669;"><?= $t['dr'] ?></h4>
            <p style="color:#10b981;"><?= number_format((float)($totals->total_debit ?? 0), 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 4px solid #dc2626;">
            <h4 style="color:#dc2626;"><?= $t['cr'] ?></h4>
            <p style="color:#f43f5e;"><?= number_format((float)($totals->total_credit ?? 0), 2) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 4px solid #4338ca; background:#e0e7ff;">
            <h4 style="color:#4338ca;"><?= $t['cur_bal'] ?></h4>
            <p style="color:#4338ca;"><?= number_format((float)($account->current_balance ?? 0), 2) ?> <span style="font-size:0.9rem; font-weight:bold; color:#6366f1;"><?= $currency ?></span></p>
        </div>
    </div>

    <!-- جدول القيود (Ledger) -->
    <div class="table-card">
        <div class="table-title-print" style="padding:22px 30px; background:var(--surface-hover); border-bottom:1px solid #e2e8f0; font-weight:900; color:var(--text-main); display:flex; justify-content:space-between; align-items:center; font-size:1.1rem;">
            <span><i class="ph-bold ph-list-dashes" style="color:var(--brand-primary); font-size:1.4rem; vertical-align:middle; margin-inline-end:8px;"></i> <?= $t['tx_title'] ?></span>
            <span style="font-size:0.85rem; color:#fff; background:#10b981; padding:6px 14px; border-radius:8px; font-weight:900; letter-spacing:1px;" class="badge-print">POSTED ✓</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="ledger-table">
                <thead>
                    <tr>
                        <th style="width: 14%;"><?= $t['col_date'] ?></th>
                        <th style="width: 16%;"><?= $t['col_ref'] ?></th>
                        <th style="width: 40%;"><?= $t['col_desc'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_dr'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_cr'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movements)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 60px; color: var(--text-muted); font-weight: 900; font-size:1.1rem; border-bottom:none;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach ($movements as $m): ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:800; color:#475569; font-size:1.05rem;"><?= htmlspecialchars($m->entry_date ?? '') ?></td>
                            <td>
                                <a href="/ERP/accounting/journal-entries/<?= $m->journal_entry_id ?? '' ?>" style="font-family:monospace; font-weight:900; color:var(--brand-primary-dark); text-decoration:none; font-size:1.05rem;">
                                    #<?= htmlspecialchars($m->entry_number ?? '') ?>
                                </a>
                            </td>
                            <td style="font-weight:800; color:var(--text-main); font-size:1rem;"><?= htmlspecialchars($m->description ?: ($m->entry_desc ?? '---')) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#10b981; font-size:1.15rem;"><?= number_format((float)($m->debit ?? 0), 2) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#f43f5e; font-size:1.15rem;"><?= number_format((float)($m->credit ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>