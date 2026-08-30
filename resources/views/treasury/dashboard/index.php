<?php
// Path: resources/views/treasury/dashboard/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$kpis = $kpis ?? ['cash_balance' => 0, 'bank_balance' => 0, 'today_inflow' => 0, 'today_outflow' => 0, 'pending_cheques' => 0, 'active_custody' => 0];
$charts = $charts ?? [
    'labels' => [], 'inflow' => [], 'outflow' => [],
    'accounts_labels' => [], 'accounts_balances' => [],
    'methods_labels' => [__('نقدي', 'Cash'), __('تحويل بنكي', 'Bank Transfer'), __('شيك', 'Cheque'), __('شبكة / POS', 'POS / Card')], 'methods_values' => [0,0,0,0],
    'monthly_labels' => [], 'monthly_inflow' => [], 'monthly_outflow' => []
];
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

    /* Bento Grid System */
    .bento-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px; margin-bottom: 24px; }
    
    .bento-card {
        background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 22px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: all 0.3s ease; display: flex; flex-direction: column;
    }
    .bento-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }

    .kpi-card { grid-column: span 2; }
    @media(max-width: 1280px) { .kpi-card { grid-column: span 4; } }
    @media(max-width: 768px) { .kpi-card { grid-column: span 6; } }
    @media(max-width: 480px) { .kpi-card { grid-column: span 12; } }

    .kpi-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 12px; }
    .kpi-label { font-size: 0.78rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 6px; }
    .kpi-value { font-size: 1.45rem; font-weight: 900; color: #0f172a; font-family: monospace; display: flex; align-items: baseline; gap: 4px; }

    .col-8 { grid-column: span 8; }
    .col-6 { grid-column: span 6; }
    .col-4 { grid-column: span 4; }
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

    /* Table Styling */
    .dash-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .dash-table th { padding: 12px 14px; background: #f8fafc; color: #64748b; font-weight: 800; border-bottom: 2px solid #e2e8f0; text-align: start; }
    .dash-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    
    .hq-badge { background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: bold; border: 1px solid #cbd5e1; display: inline-flex; align-items: center; gap: 4px; margin-right: 12px;}
</style>

<div class="treasury-dash" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <!-- Top Bar -->
    <div class="dash-header">
        <div class="dash-title-group">
            <div class="dash-header-icon"><i class="ph-duotone ph-vault"></i></div>
            <div>
                <div style="display: flex; align-items: center;">
                    <h2 style="margin:0; font-size:1.8rem; font-weight:900; color:#0f172a;"><?= __('لوحة قيادة الخزانة والبنوك', 'Treasury & Banking Executive Dashboard') ?></h2>
                    <?php if (is_hq()): ?>
                        <span class="hq-badge" title="<?= __('بيانات مجمعة لجميع الفروع', 'Consolidated Data for All Branches') ?>"><i class="ph-fill ph-buildings"></i> <?= __('المركز الرئيسي', 'Headquarters') ?></span>
                    <?php endif; ?>
                </div>
                <p style="margin:3px 0 0 0; color:#64748b; font-size:0.9rem; font-weight:600;"><?= __('مراقبة السيولة النقدية، السندات، والرسوم البيانية التحليلية.', 'Real-time liquidity, voucher flow, and bank balance oversight.') ?></p>
            </div>
        </div>
        <div>
            <?php if (has_permission('treasury_dashboard_export')): ?>
                <a href="/ERP/treasury/dashboard?export=excel" class="btn-export-excel">
                    <i class="ph-bold ph-file-xls" style="font-size:1.2rem;"></i>
                    <?= __('تصدير شيت إكسيل', 'Export Excel') ?>
                </a>
            <?php else: ?>
                <a href="javascript:void(0)" class="btn-export-excel disabled" title="<?= __('ليس لديك صلاحية التصدير', 'No export permission') ?>">
                    <i class="ph-bold ph-file-xls" style="font-size:1.2rem;"></i>
                    <?= __('تصدير شيت إكسيل', 'Export Excel') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 6 Executive KPIs -->
    <div class="bento-grid">
        <div class="bento-card kpi-card">
            <div class="kpi-icon" style="background: var(--dash-teal-bg); color: var(--dash-teal);"><i class="ph-duotone ph-wallet"></i></div>
            <div class="kpi-label"><?= __('نقدية بالصناديق', 'Cash on Hand') ?></div>
            <div class="kpi-value" style="color: var(--dash-teal);"><?= number_format($kpis['cash_balance'], 2) ?></div>
        </div>

        <div class="bento-card kpi-card">
            <div class="kpi-icon" style="background: var(--dash-blue-bg); color: var(--dash-blue);"><i class="ph-duotone ph-bank"></i></div>
            <div class="kpi-label"><?= __('أرصدة البنوك', 'Bank Balances') ?></div>
            <div class="kpi-value" style="color: var(--dash-blue);"><?= number_format($kpis['bank_balance'], 2) ?></div>
        </div>

        <div class="bento-card kpi-card">
            <div class="kpi-icon" style="background: var(--dash-purple-bg); color: var(--dash-purple);"><i class="ph-duotone ph-arrow-down-left"></i></div>
            <div class="kpi-label"><?= __('مقبوضات اليوم', 'Today Inflow') ?></div>
            <div class="kpi-value" style="color: var(--dash-purple);">+<?= number_format($kpis['today_inflow'], 2) ?></div>
        </div>

        <div class="bento-card kpi-card">
            <div class="kpi-icon" style="background: var(--dash-rose-bg); color: var(--dash-rose);"><i class="ph-duotone ph-arrow-up-right"></i></div>
            <div class="kpi-label"><?= __('مدفوعات اليوم', 'Today Outflow') ?></div>
            <div class="kpi-value" style="color: var(--dash-rose);">-<?= number_format($kpis['today_outflow'], 2) ?></div>
        </div>

        <div class="bento-card kpi-card">
            <div class="kpi-icon" style="background: var(--dash-fuchsia-bg); color: var(--dash-fuchsia);"><i class="ph-duotone ph-checks"></i></div>
            <div class="kpi-label"><?= __('شيكات معلقة', 'Pending Cheques') ?></div>
            <div class="kpi-value" style="color: var(--dash-fuchsia);"><?= number_format($kpis['pending_cheques'], 2) ?></div>
        </div>

        <div class="bento-card kpi-card">
            <div class="kpi-icon" style="background: var(--dash-amber-bg); color: var(--dash-amber);"><i class="ph-duotone ph-briefcase"></i></div>
            <div class="kpi-label"><?= __('عُهد نشطة', 'Active Custodies') ?></div>
            <div class="kpi-value" style="color: var(--dash-amber);"><?= number_format($kpis['active_custody'], 2) ?></div>
        </div>
    </div>

    <!-- Charts Row 1: Line Chart & Doughnut Chart -->
    <div class="bento-grid">
        <!-- Chart 1: Line Area Flow -->
        <div class="bento-card col-8">
            <h3 style="margin:0 0 18px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-duotone ph-chart-line-up" style="color:var(--dash-purple); font-size:1.3rem;"></i>
                <?= __('1. حركية التدفقات النقدية اليومية (آخر 7 أيام)', '1. Daily Cash Flow Trend') ?>
            </h3>
            <div style="height: 250px; position: relative; width: 100%;">
                <canvas id="cashFlowChart"></canvas>
            </div>
        </div>

        <!-- Chart 2: Doughnut Accounts Distribution -->
        <div class="bento-card col-4">
            <h3 style="margin:0 0 18px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-duotone ph-chart-pie-slice" style="color:var(--dash-blue); font-size:1.3rem;"></i>
                <?= __('2. وزن وأرصدة الخزائن والبنوك', '2. Accounts Weight') ?>
            </h3>
            <div style="height: 220px; position: relative; width: 100%; display:flex; justify-content:center;">
                <canvas id="accountsPieChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2: Payment Methods & 6-Month Comparison -->
    <div class="bento-grid">
        <!-- Chart 3: Bar Chart Payment Methods -->
        <div class="bento-card col-4">
            <h3 style="margin:0 0 18px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-duotone ph-credit-card" style="color:var(--dash-teal); font-size:1.3rem;"></i>
                <?= __('3. توزيع المقبوضات حسب طريقة التحصيل', '3. Payment Methods Breakdown') ?>
            </h3>
            <div style="height: 240px; position: relative; width: 100%;">
                <canvas id="paymentMethodsChart"></canvas>
            </div>
        </div>

        <!-- Chart 4: Grouped Bar Chart Monthly Trend -->
        <div class="bento-card col-8">
            <h3 style="margin:0 0 18px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-duotone ph-chart-bar" style="color:var(--dash-rose); font-size:1.3rem;"></i>
                <?= __('4. المقارنة الشهرية بين المقبوضات والمدفوعات (آخر 6 أشهر)', '4. Monthly Flow Comparison (6 Months)') ?>
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
                <?= __('اختصارات أقسام الخزانة', 'Quick Treasury Hub') ?>
            </h3>

            <div class="hub-grid">
                <?php if(has_permission('treasury_accounts_view')): ?>
                <a href="/ERP/treasury/accounts" class="action-card-node">
                    <i class="ph-duotone ph-vault" style="background:var(--dash-blue-bg); color:var(--dash-blue);"></i>
                    <span><?= __('الحسابات والبنوك', 'Bank Accounts') ?></span>
                </a>
                <?php endif; ?>

                <?php if(has_permission('treasury_receipts_view')): ?>
                <a href="/ERP/treasury/receipts" class="action-card-node">
                    <i class="ph-duotone ph-receipt" style="background:var(--dash-teal-bg); color:var(--dash-teal);"></i>
                    <span><?= __('سندات القبض', 'Receipt Vouchers') ?></span>
                </a>
                <?php endif; ?>

                <?php if(has_permission('treasury_payments_view')): ?>
                <a href="/ERP/treasury/payments" class="action-card-node">
                    <i class="ph-duotone ph-arrow-up-right" style="background:var(--dash-rose-bg); color:var(--dash-rose);"></i>
                    <span><?= __('سندات الصرف', 'Payment Vouchers') ?></span>
                </a>
                <?php endif; ?>

                <?php if(has_permission('treasury_transfers_view')): ?>
                <a href="/ERP/treasury/transfers" class="action-card-node">
                    <i class="ph-duotone ph-arrows-left-right" style="background:var(--dash-purple-bg); color:var(--dash-purple);"></i>
                    <span><?= __('تحويلات داخلية', 'Internal Transfers') ?></span>
                </a>
                <?php endif; ?>

                <?php if(has_permission('treasury_petty_cash_view')): ?>
                <a href="/ERP/treasury/petty-cash" class="action-card-node">
                    <i class="ph-duotone ph-briefcase" style="background:var(--dash-amber-bg); color:var(--dash-amber);"></i>
                    <span><?= __('إدارة العُهد', 'Petty Cash') ?></span>
                </a>
                <?php endif; ?>

                <?php if(has_permission('treasury_cheques_view')): ?>
                <a href="/ERP/treasury/cheques" class="action-card-node">
                    <i class="ph-duotone ph-checks" style="background:var(--dash-fuchsia-bg); color:var(--dash-fuchsia);"></i>
                    <span><?= __('حركة الشيكات', 'Cheques Hub') ?></span>
                </a>
                <?php endif; ?>
            </div>

            <?php if(has_permission('treasury_reports_view')): ?>
            <a href="/ERP/treasury/reports/cash-book" class="action-card-node" style="margin-top:12px; background:#ffffff; justify-content:center; border-color:#cbd5e1;">
                <i class="ph-bold ph-book-open-text" style="background:#eff6ff; color:#1d4ed8;"></i>
                <span><?= __('عرض تقرير دفتر الصندوق الكامل', 'Open Full Cash Book Report') ?></span>
            </a>
            <?php endif; ?>
        </div>

        <div class="bento-card col-8">
            <h3 style="margin:0 0 16px 0; font-size:1.1rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="ph-duotone ph-list-bullets" style="color:var(--dash-teal); font-size:1.3rem;"></i>
                <?= __('أحدث حركات الصرف والقبض المسجلة', 'Recent Cash Movements') ?>
            </h3>

            <table class="dash-table">
                <thead>
                    <tr>
                        <th><?= __('الكود', 'Voucher #') ?></th>
                        <th><?= __('التاريخ', 'Date') ?></th>
                        <th><?= __('الفرع', 'Branch') ?></th> <!-- تمت الإضافة للمركز الرئيسي -->
                        <th><?= __('النوع', 'Type') ?></th>
                        <th><?= __('الطرف المستلم / الدافع', 'Party') ?></th>
                        <th style="text-align:center;"><?= __('المبلغ', 'Amount') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentTransactions)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:#94a3b8; font-weight:700;"><?= __('لا توجد حركات نقدية مسجلة مؤخراً.', 'No recent transactions found.') ?></td></tr>
                    <?php else: foreach ($recentTransactions as $tx): $isIn = $tx->direction === 'in'; ?>
                        <tr>
                            <td style="font-family:monospace; font-weight:900; color:#0f172a;"><?= htmlspecialchars($tx->code) ?></td>
                            <td style="font-family:monospace; font-size:0.8rem;"><?= htmlspecialchars($tx->tx_date) ?></td>
                            <td style="font-size:0.8rem; color:#64748b; font-weight:bold;"><?= is_hq() ? htmlspecialchars($tx->branch_name ?? __('رئيسي', 'HQ')) : '---' ?></td>
                            <td>
                                <span style="font-size:0.75rem; font-weight:800; padding:2px 8px; border-radius:6px; <?= $isIn ? 'background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;' : 'background:#fff1f2; color:#e11d48; border:1px solid #fecdd3;' ?>">
                                    <?= htmlspecialchars($tx->type) ?>
                                </span>
                            </td>
                            <td style="font-weight:700; color:#334155;"><?= htmlspecialchars($tx->party ?: __('عام', 'General')) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; font-size:0.95rem; color:<?= $isIn ? '#059669' : '#e11d48' ?>;">
                                <?= $isIn ? '+' : '-' ?><?= number_format((float)$tx->amount, 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    Chart.defaults.font.family = "'Cairo', 'Inter', sans-serif";

    // Chart 1: Line Area Flow (7 Days)
    new Chart(document.getElementById('cashFlowChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode($charts['labels']) ?>,
            datasets: [
                {
                    label: '<?= __('الوارد (المقبوضات)', 'Inflow') ?>',
                    data: <?= json_encode($charts['inflow']) ?>,
                    borderColor: '#9333ea', backgroundColor: 'rgba(147, 51, 234, 0.08)',
                    borderWidth: 3, fill: true, tension: 0.4
                },
                {
                    label: '<?= __('الصادر (المدفوعات)', 'Outflow') ?>',
                    data: <?= json_encode($charts['outflow']) ?>,
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
                data: <?= json_encode($charts['accounts_balances']) ?>,
                backgroundColor: ['#2563eb', '#0d9488', '#9333ea', '#d97706', '#c026d3'],
                borderWidth: 2
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // Chart 3: Horizontal Bar Chart - Payment Methods
    new Chart(document.getElementById('paymentMethodsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($charts['methods_labels']) ?>,
            datasets: [{
                label: '<?= __('إجمالي المبلغ', 'Total Amount') ?>',
                data: <?= json_encode($charts['methods_values']) ?>,
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
                    label: '<?= __('مقبوضات شهرية', 'Monthly Inflow') ?>',
                    data: <?= json_encode($charts['monthly_inflow']) ?>,
                    backgroundColor: '#0d9488', borderRadius: 6
                },
                {
                    label: '<?= __('مدفوعات شهرية', 'Monthly Outflow') ?>',
                    data: <?= json_encode($charts['monthly_outflow']) ?>,
                    backgroundColor: '#e11d48', borderRadius: 6
                }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
    });
});
</script>