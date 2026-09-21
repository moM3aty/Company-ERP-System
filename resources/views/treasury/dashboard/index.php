<?php
// Path: resources/views/treasury/dashboard/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

// إعداد العملات والتحويل بشكل آمن
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

$activeBranchId = (int)($_SESSION['branch_id'] ?? 0);
$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');
$isHq = ($activeBranchId === 0);

// التحقق الآمن من الصلاحيات
$checkPerm = function($perm) {
    return !function_exists('has_permission') || has_permission($perm);
};

// مصفوفة الترجمة الشاملة
$t = [
    'ar' => [
        'title' => 'لوحة قيادة الخزانة والبنوك', 'desc' => 'مراقبة السيولة النقدية، السندات، والرسوم البيانية التحليلية.',
        'hq_badge_title' => 'بيانات مجمعة لجميع الفروع', 'hq' => 'المركز الرئيسي',
        'export' => 'تصدير شيت إكسيل', 'no_export_perm' => 'ليس لديك صلاحية التصدير',
        'active_scope' => 'الفرع النشط حالياً:',
        'cash_on_hand' => 'نقدية بالصناديق', 'bank_balances' => 'أرصدة البنوك',
        'today_inflow' => 'مقبوضات اليوم', 'today_outflow' => 'مدفوعات اليوم',
        'pending_cheques' => 'شيكات معلقة', 'active_custody' => 'عُهد نشطة',
        'chart_flow' => '1. حركية التدفقات النقدية اليومية (آخر 7 أيام)',
        'chart_accs' => '2. وزن وأرصدة الخزائن والبنوك',
        'chart_methods' => '3. توزيع المقبوضات حسب طريقة التحصيل',
        'chart_monthly' => '4. المقارنة الشهرية بين المقبوضات والمدفوعات',
        'quick_hub' => 'اختصارات أقسام الخزانة',
        'hub_acc' => 'الحسابات والبنوك', 'hub_rec' => 'سندات القبض',
        'hub_pay' => 'سندات الصرف', 'hub_trf' => 'تحويلات داخلية',
        'hub_pc' => 'إدارة العُهد', 'hub_chq' => 'حركة الشيكات',
        'hub_cashbook' => 'عرض تقرير دفتر الصندوق الكامل',
        'recent_title' => 'أحدث حركات الصرف والقبض المسجلة',
        'col_code' => 'رقم السند', 'col_date' => 'التاريخ', 'col_branch' => 'الفرع',
        'col_type' => 'النوع', 'col_party' => 'الطرف المستلم / الدافع', 'col_amount' => 'المبلغ',
        'empty_tx' => 'لا توجد حركات نقدية مسجلة مؤخراً.',
        'inflow' => 'الوارد (المقبوضات)', 'outflow' => 'الصادر (المدفوعات)',
        'general' => 'عام', 'total_amt' => 'إجمالي المبلغ',
        'meth_cash' => 'نقدي', 'meth_bank' => 'تحويل بنكي', 'meth_chq' => 'شيك', 'meth_pos' => 'شبكة / POS',
        'monthly_inflow' => 'مقبوضات شهرية', 'monthly_outflow' => 'مدفوعات شهرية'
    ],
    'en' => [
        'title' => 'Treasury & Banking Dashboard', 'desc' => 'Real-time liquidity, voucher flow, and analytical charts.',
        'hq_badge_title' => 'Consolidated Data for All Branches', 'hq' => 'Headquarters',
        'export' => 'Export Excel', 'no_export_perm' => 'No export permission',
        'active_scope' => 'Active Branch:',
        'cash_on_hand' => 'Cash on Hand', 'bank_balances' => 'Bank Balances',
        'today_inflow' => 'Today Inflow', 'today_outflow' => 'Today Outflow',
        'pending_cheques' => 'Pending Cheques', 'active_custody' => 'Active Custodies',
        'chart_flow' => '1. Daily Cash Flow Trend (7 Days)',
        'chart_accs' => '2. Accounts Weight & Balances',
        'chart_methods' => '3. Payment Methods Breakdown',
        'chart_monthly' => '4. Monthly Flow Comparison (6 Months)',
        'quick_hub' => 'Treasury Quick Hub',
        'hub_acc' => 'Bank Accounts', 'hub_rec' => 'Receipt Vouchers',
        'hub_pay' => 'Payment Vouchers', 'hub_trf' => 'Internal Transfers',
        'hub_pc' => 'Petty Cash', 'hub_chq' => 'Cheques Hub',
        'hub_cashbook' => 'Open Full Cash Book Report',
        'recent_title' => 'Recent Cash Movements',
        'col_code' => 'Voucher #', 'col_date' => 'Date', 'col_branch' => 'Branch',
        'col_type' => 'Type', 'col_party' => 'Party', 'col_amount' => 'Amount',
        'empty_tx' => 'No recent transactions found.',
        'inflow' => 'Inflow (Receipts)', 'outflow' => 'Outflow (Payments)',
        'general' => 'General', 'total_amt' => 'Total Amount',
        'meth_cash' => 'Cash', 'meth_bank' => 'Bank Transfer', 'meth_chq' => 'Cheque', 'meth_pos' => 'POS/Cards',
        'monthly_inflow' => 'Monthly Inflow', 'monthly_outflow' => 'Monthly Outflow'
    ]
][$isRtl ? 'ar' : 'en'];

// تجهيز أسماء طرق الدفع للرسم البياني حسب اللغة
$methodsLabelsJson = json_encode([$t['meth_cash'], $t['meth_bank'], $t['meth_chq'], $t['meth_pos']]);

$kpis = $kpis ?? ['cash_balance' => 0, 'bank_balance' => 0, 'today_inflow' => 0, 'today_outflow' => 0, 'pending_cheques' => 0, 'active_custody' => 0];
$charts = $charts ?? ['labels' => [], 'inflow' => [], 'outflow' => [], 'accounts_labels' => [], 'accounts_balances' => [], 'methods_values' => [0,0,0,0], 'monthly_labels' => [], 'monthly_inflow' => [], 'monthly_outflow' => []];
$recentTransactions = $recentTransactions ?? [];
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --dash-purple: #9333ea; --dash-purple-bg: #f3e8ff;
        --dash-teal: #0d9488; --dash-teal-bg: #ccfbf1;
        --dash-blue: #2563eb; --dash-blue-bg: #dbeafe;
        --dash-rose: #e11d48; --dash-rose-bg: #ffe4e6;
        --dash-amber: #d97706; --dash-amber-bg: #fef3c7;
        --dash-fuchsia: #c026d3; --dash-fuchsia-bg: #fae8ff;
    }

    .treasury-dash { font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; padding-bottom: 50px; }

    .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; flex-wrap: wrap; gap: 16px; }
    .dash-title-group { display: flex; align-items: center; gap: 14px; }
    .dash-header-icon { width: 52px; height: 52px; background: var(--dash-purple-bg); color: var(--dash-purple); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 6px 15px rgba(147, 51, 234, 0.12); }

    .btn-export-excel { background: #059669; color: #ffffff !important; padding: 10px 20px; border-radius: 12px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2); transition: all 0.2s ease; }
    .btn-export-excel:hover { background: #047857; transform: translateY(-2px); }
    .btn-export-excel.disabled { opacity: 0.5; pointer-events: none; cursor: not-allowed; }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid #cbd5e1; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: #0f172a; margin-bottom: 24px; }

    /* Bento Grid System - حماية من انكماش العرض */
    .bento-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px; margin-bottom: 24px; width: 100%; }
    
    .bento-card {
        background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 22px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: all 0.3s ease; display: flex; flex-direction: column;
        min-width: 0 !important; /* حماية الجريد من الانكماش والتداخل */
    }
    .bento-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }

    .kpi-card { grid-column: span 2; min-width: 0 !important; }
    @media(max-width: 1280px) { .kpi-card { grid-column: span 4; } }
    @media(max-width: 768px) { .kpi-card { grid-column: span 6; } }
    @media(max-width: 480px) { .kpi-card { grid-column: span 12; } }

    .kpi-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 12px; }
    .kpi-label { font-size: 0.78rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 6px; }
    .kpi-value { font-size: 1.45rem; font-weight: 900; color: #0f172a; font-family: monospace; display: flex; align-items: baseline; gap: 4px; }

    .col-8 { grid-column: span 8; min-width: 0 !important; }
    .col-6 { grid-column: span 6; min-width: 0 !important; }
    .col-4 { grid-column: span 4; min-width: 0 !important; }
    @media(max-width: 1024px) { .col-8, .col-6, .col-4 { grid-column: span 12; } }

    /* Hub Actions */
    .hub-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .action-card-node {
        display: flex; align-items: center; gap: 12px; padding: 14px; background: #f8fafc;
        border: 1px solid #e2e8f0; border-radius: 14px; text-decoration: none; color: #0f172a;
        font-weight: 800; font-size: 0.88rem; transition: all 0.2s ease;
    }
    .action-card-node:hover { background: #ffffff; border-color: var(--dash-purple); transform: translateX(<?= $isRtl ? '-4px' : '4px' ?>); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .action-card-node i { font-size: 1.3rem; padding: 8px; border-radius: 10px; }

    /* Table Styling & DataTables Disabler */
    .dash-table { width: 100% !important; border-collapse: collapse; font-size: 0.85rem; }
    .dash-table th { padding: 12px 14px; background: #f8fafc; color: #64748b; font-weight: 800; border-bottom: 2px solid #e2e8f0; text-align: start; }
    .dash-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    
    .hq-badge { background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: bold; border: 1px solid #cbd5e1; display: inline-flex; align-items: center; gap: 4px; margin-right: 12px;}

    /* إخفاء وتمرير أي عناصر محقونة من DataTables على الكروت */
    .bento-card .dataTables_wrapper { width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .bento-card .dataTables_filter, 
    .bento-card .dataTables_length, 
    .bento-card .dataTables_info, 
    .bento-card .dataTables_paginate { display: none !important; }
</style>

<div class="treasury-dash" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <!-- Top Bar -->
    <div class="dash-header">
        <div class="dash-title-group">
            <div class="dash-header-icon"><i class="ph-duotone ph-vault"></i></div>
            <div>
                <div style="display: flex; align-items: center;">
                    <h2 style="margin:0; font-size:1.8rem; font-weight:900; color:#0f172a;"><?= $t['title'] ?></h2>
                    <?php if ($isHq): ?>
                        <span class="hq-badge" title="<?= $t['hq_badge_title'] ?>"><i class="ph-fill ph-buildings"></i> <?= $t['hq'] ?></span>
                    <?php endif; ?>
                </div>
                <p style="margin:3px 0 0 0; color:#64748b; font-size:0.9rem; font-weight:600;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div>
            <?php if ($checkPerm('treasury_dashboard_export')): ?>
                <a href="/ERP/treasury/dashboard?export=excel" class="btn-export-excel">
                    <i class="ph-bold ph-file-xls" style="font-size:1.2rem;"></i>
                    <?= $t['export'] ?>
                </a>
            <?php else: ?>
                <a href="javascript:void(0)" class="btn-export-excel disabled" title="<?= $t['no_export_perm'] ?>">
                    <i class="ph-bold ph-file-xls" style="font-size:1.2rem;"></i>
                    <?= $t['export'] ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--dash-purple);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--dash-purple); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <!-- 6 Executive KPIs -->
    <div class="bento-grid">
        <div class="bento-card kpi-card">
            <div class="kpi-icon" style="background: var(--dash-teal-bg); color: var(--dash-teal);"><i class="ph-duotone ph-wallet"></i></div>
            <div class="kpi-label"><?= $t['cash_on_hand'] ?></div>
            <div class="kpi-value" style="color: var(--dash-teal);"><?= number_format($convert($kpis['cash_balance']), 2) ?> <span style="font-size:0.75rem; color:#64748b;"><?= $currency ?></span></div>
        </div>

        <div class="bento-card kpi-card">
            <div class="kpi-icon" style="background: var(--dash-blue-bg); color: var(--dash-blue);"><i class="ph-duotone ph-bank"></i></div>
            <div class="kpi-label"><?= $t['bank_balances'] ?></div>
            <div class="kpi-value" style="color: var(--dash-blue);"><?= number_format($convert($kpis['bank_balance']), 2) ?> <span style="font-size:0.75rem; color:#64748b;"><?= $currency ?></span></div>
        </div>

        <div class="bento-card kpi-card">
            <div class="kpi-icon" style="background: var(--dash-purple-bg); color: var(--dash-purple);"><i class="ph-duotone ph-arrow-down-left"></i></div>
            <div class="kpi-label"><?= $t['today_inflow'] ?></div>
            <div class="kpi-value" style="color: var(--dash-purple);">+<?= number_format($convert($kpis['today_inflow']), 2) ?></div>
        </div>

        <div class="bento-card kpi-card">
            <div class="kpi-icon" style="background: var(--dash-rose-bg); color: var(--dash-rose);"><i class="ph-duotone ph-arrow-up-right"></i></div>
            <div class="kpi-label"><?= $t['today_outflow'] ?></div>
            <div class="kpi-value" style="color: var(--dash-rose);">-<?= number_format($convert($kpis['today_outflow']), 2) ?></div>
        </div>

        <div class="bento-card kpi-card">
            <div class="kpi-icon" style="background: var(--dash-fuchsia-bg); color: var(--dash-fuchsia);"><i class="ph-duotone ph-checks"></i></div>
            <div class="kpi-label"><?= $t['pending_cheques'] ?></div>
            <div class="kpi-value" style="color: var(--dash-fuchsia);"><?= number_format($convert($kpis['pending_cheques']), 2) ?></div>
        </div>

        <div class="bento-card kpi-card">
            <div class="kpi-icon" style="background: var(--dash-amber-bg); color: var(--dash-amber);"><i class="ph-duotone ph-briefcase"></i></div>
            <div class="kpi-label"><?= $t['active_custody'] ?></div>
            <div class="kpi-value" style="color: var(--dash-amber);"><?= number_format($convert($kpis['active_custody']), 2) ?></div>
        </div>
    </div>

    <!-- Charts Row 1: Line Chart & Doughnut Chart -->
    <div class="bento-grid">
        <div class="bento-card col-8">
            <h3 style="margin:0 0 18px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-duotone ph-chart-line-up" style="color:var(--dash-purple); font-size:1.3rem;"></i>
                <?= $t['chart_flow'] ?>
            </h3>
            <div style="height: 250px; position: relative; width: 100%;">
                <canvas id="cashFlowChart"></canvas>
            </div>
        </div>

        <div class="bento-card col-4">
            <h3 style="margin:0 0 18px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-duotone ph-chart-pie-slice" style="color:var(--dash-blue); font-size:1.3rem;"></i>
                <?= $t['chart_accs'] ?>
            </h3>
            <div style="height: 220px; position: relative; width: 100%; display:flex; justify-content:center;">
                <canvas id="accountsPieChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2: Payment Methods & 6-Month Comparison -->
    <div class="bento-grid">
        <div class="bento-card col-4">
            <h3 style="margin:0 0 18px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-duotone ph-credit-card" style="color:var(--dash-teal); font-size:1.3rem;"></i>
                <?= $t['chart_methods'] ?>
            </h3>
            <div style="height: 240px; position: relative; width: 100%;">
                <canvas id="paymentMethodsChart"></canvas>
            </div>
        </div>

        <div class="bento-card col-8">
            <h3 style="margin:0 0 18px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-duotone ph-chart-bar" style="color:var(--dash-rose); font-size:1.3rem;"></i>
                <?= $t['chart_monthly'] ?>
            </h3>
            <div style="height: 240px; position: relative; width: 100%;">
                <canvas id="monthlyFlowChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Module Hub & Recent Activity Table -->
    <div class="bento-grid">
        <div class="bento-card col-4" style="background:#f8fafc;">
            <h3 style="margin:0 0 16px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-fill ph-grid-four" style="color:#64748b;"></i>
                <?= $t['quick_hub'] ?>
            </h3>

            <div class="hub-grid">
                <?php if($checkPerm('treasury_accounts_view')): ?>
                <a href="/ERP/treasury/accounts" class="action-card-node">
                    <i class="ph-duotone ph-vault" style="background:var(--dash-blue-bg); color:var(--dash-blue);"></i>
                    <span><?= $t['hub_acc'] ?></span>
                </a>
                <?php endif; ?>

                <?php if($checkPerm('treasury_receipts_view')): ?>
                <a href="/ERP/treasury/receipts" class="action-card-node">
                    <i class="ph-duotone ph-receipt" style="background:var(--dash-teal-bg); color:var(--dash-teal);"></i>
                    <span><?= $t['hub_rec'] ?></span>
                </a>
                <?php endif; ?>

                <?php if($checkPerm('treasury_payments_view')): ?>
                <a href="/ERP/treasury/payments" class="action-card-node">
                    <i class="ph-duotone ph-arrow-up-right" style="background:var(--dash-rose-bg); color:var(--dash-rose);"></i>
                    <span><?= $t['hub_pay'] ?></span>
                </a>
                <?php endif; ?>

                <?php if($checkPerm('treasury_transfers_view')): ?>
                <a href="/ERP/treasury/transfers" class="action-card-node">
                    <i class="ph-duotone ph-arrows-left-right" style="background:var(--dash-purple-bg); color:var(--dash-purple);"></i>
                    <span><?= $t['hub_trf'] ?></span>
                </a>
                <?php endif; ?>

                <?php if($checkPerm('treasury_petty_cash_view')): ?>
                <a href="/ERP/treasury/petty-cash" class="action-card-node">
                    <i class="ph-duotone ph-briefcase" style="background:var(--dash-amber-bg); color:var(--dash-amber);"></i>
                    <span><?= $t['hub_pc'] ?></span>
                </a>
                <?php endif; ?>

                <?php if($checkPerm('treasury_cheques_view')): ?>
                <a href="/ERP/treasury/cheques" class="action-card-node">
                    <i class="ph-duotone ph-checks" style="background:var(--dash-fuchsia-bg); color:var(--dash-fuchsia);"></i>
                    <span><?= $t['hub_chq'] ?></span>
                </a>
                <?php endif; ?>
            </div>

            <?php if($checkPerm('treasury_reports_view')): ?>
            <a href="/ERP/treasury/reports/cash-book" class="action-card-node" style="margin-top:12px; background:#ffffff; justify-content:center; border-color:#cbd5e1;">
                <i class="ph-bold ph-book-open-text" style="background:#eff6ff; color:#1d4ed8;"></i>
                <span><?= $t['hub_cashbook'] ?></span>
            </a>
            <?php endif; ?>
        </div>

        <div class="bento-card col-8">
            <h3 style="margin:0 0 16px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-duotone ph-list-bullets" style="color:var(--dash-teal); font-size:1.3rem;"></i>
                <?= $t['recent_title'] ?>
            </h3>

            <div style="width: 100%; overflow-x: auto;">
                <table class="dash-table no-datatable" style="width: 100% !important;">
                    <thead>
                        <tr>
                            <th><?= $t['col_code'] ?></th>
                            <th><?= $t['col_date'] ?></th>
                            <th><?= $t['col_branch'] ?></th>
                            <th><?= $t['col_type'] ?></th>
                            <th><?= $t['col_party'] ?></th>
                            <th style="text-align:center;"><?= $t['col_amount'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTransactions)): ?>
                            <tr><td colspan="6" style="text-align:center; padding:30px; color:#94a3b8; font-weight:700;"><?= $t['empty_tx'] ?></td></tr>
                        <?php else: foreach ($recentTransactions as $tx): $isIn = $tx->direction === 'in'; ?>
                            <tr>
                                <td style="font-family:monospace; font-weight:900; color:#0f172a;"><?= htmlspecialchars($tx->code) ?></td>
                                <td style="font-family:monospace; font-size:0.8rem;"><?= htmlspecialchars($tx->tx_date) ?></td>
                                <td style="font-size:0.8rem; color:#64748b; font-weight:bold;"><?= $isHq ? htmlspecialchars($tx->branch_name ?: $t['hq']) : '---' ?></td>
                                <td>
                                    <span style="font-size:0.75rem; font-weight:800; padding:2px 8px; border-radius:6px; <?= $isIn ? 'background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;' : 'background:#fff1f2; color:#e11d48; border:1px solid #fecdd3;' ?>">
                                        <?= $isIn ? $t['inflow'] : $t['outflow'] ?>
                                    </span>
                                </td>
                                <td style="font-weight:700; color:#334155;"><?= htmlspecialchars($tx->party ?: $t['general']) ?></td>
                                <td style="text-align:center; font-family:monospace; font-weight:900; font-size:0.95rem; color:<?= $isIn ? '#059669' : '#e11d48' ?>;">
                                    <?= $isIn ? '+' : '-' ?><?= number_format($convert($tx->amount), 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    Chart.defaults.font.family = "'Cairo', 'Inter', sans-serif";

    // إزالة أي عناصر تحكم مضافة تلقائياً من DataTables على الكروت
    document.querySelectorAll('.bento-card .dataTables_filter, .bento-card .dataTables_length, .bento-card .dataTables_info, .bento-card .dataTables_paginate').forEach(el => el.remove());

    // Chart 1: Line Area Flow (7 Days)
    new Chart(document.getElementById('cashFlowChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode($charts['labels']) ?>,
            datasets: [
                {
                    label: '<?= $t['inflow'] ?>',
                    data: <?= json_encode(array_map($convert, $charts['inflow'])) ?>,
                    borderColor: '#9333ea', backgroundColor: 'rgba(147, 51, 234, 0.08)',
                    borderWidth: 3, fill: true, tension: 0.4
                },
                {
                    label: '<?= $t['outflow'] ?>',
                    data: <?= json_encode(array_map($convert, $charts['outflow'])) ?>,
                    borderColor: '#e11d48', backgroundColor: 'rgba(225, 29, 72, 0.08)',
                    borderWidth: 3, fill: true, tension: 0.4
                }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
    });

    // Chart 2: Doughnut Accounts Distribution
    new Chart(document.getElementById('accountsPieChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($charts['accounts_labels']) ?>,
            datasets: [{
                data: <?= json_encode(array_map($convert, $charts['accounts_balances'])) ?>,
                backgroundColor: ['#2563eb', '#0d9488', '#9333ea', '#d97706', '#c026d3', '#64748b'],
                borderWidth: 2
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // Chart 3: Horizontal Bar Chart - Payment Methods
    new Chart(document.getElementById('paymentMethodsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= $methodsLabelsJson ?>,
            datasets: [{
                label: '<?= $t['total_amt'] ?>',
                data: <?= json_encode(array_map($convert, $charts['methods_values'])) ?>,
                backgroundColor: ['#059669', '#2563eb', '#d97706', '#7c3aed'],
                borderRadius: 8
            }]
        },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    // Chart 4: Grouped Bar Chart - 6 Months Monthly Flow
    new Chart(document.getElementById('monthlyFlowChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($charts['monthly_labels']) ?>,
            datasets: [
                {
                    label: '<?= $t['monthly_inflow'] ?>',
                    data: <?= json_encode(array_map($convert, $charts['monthly_inflow'])) ?>,
                    backgroundColor: '#0d9488', borderRadius: 6
                },
                {
                    label: '<?= $t['monthly_outflow'] ?>',
                    data: <?= json_encode(array_map($convert, $charts['monthly_outflow'])) ?>,
                    backgroundColor: '#e11d48', borderRadius: 6
                }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
    });
});
</script>