<?php
// Path: resources/views/hr/payroll/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert  = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title' => 'مسيرات ومعالجة الرواتب (Payroll)',
        'desc' => 'احتساب المسيرات الشهرية، البدلات والخصوم وصرف الرواتب.',
        'add_btn' => 'توليد مسير رواتب جديد',
        'col_code' => 'كود المسير',
        'col_month' => 'الشهر والسنة',
        'col_salaries' => 'الأساسي / البدلات',
        'col_deductions' => 'الخصومات',
        'col_net' => 'صافي المسير (Net)',
        'col_status' => 'الحالة',
        'col_actions' => 'إجراءات',
        'empty' => 'لا توجد مسيرات رواتب مسجلة بمواصفات البحث.',
        'search_ph' => 'ابحث بكود المسير أو الشهر...',
        'btn_search' => 'فلترة',
        'btn_clear' => 'إلغاء',
        'active_scope' => 'الفرع النشط:',
        'kpi_total' => 'إجمالي المسيرات',
        'kpi_net' => 'إجمالي المدفوعات Net',
        'kpi_draft' => 'مسودات قيد الإعداد',
        'kpi_paid' => 'مسيرات مصروفة',
        'all_years' => '-- كل السنوات --',
        'all_statuses' => '-- كل الحالات --',
        'status_draft' => 'مسودة',
        'status_processed' => 'محتسب ومعتمد',
        'status_paid' => 'صُرف بالكامل',
        'basic_lbl' => 'أساسي:',
        'allow_lbl' => 'بدلات:',
        'print_list' => 'طباعة القائمة',
        'confirm_delete' => 'هل أنت متأكد من حذف هذا المسير؟'
    ],
    'en' => [
        'title' => 'Payroll & Salary Processing',
        'desc' => 'Generate monthly payrolls, allowances, deductions, and issue payments.',
        'add_btn' => 'Generate New Payroll',
        'col_code' => 'Payroll Code',
        'col_month' => 'Month & Year',
        'col_salaries' => 'Basic / Allowances',
        'col_deductions' => 'Deductions',
        'col_net' => 'Net Pay',
        'col_status' => 'Status',
        'col_actions' => 'Actions',
        'empty' => 'No payroll records found.',
        'search_ph' => 'Search by payroll code or month...',
        'btn_search' => 'Filter',
        'btn_clear' => 'Clear',
        'active_scope' => 'Active Branch:',
        'kpi_total' => 'Total Payroll Runs',
        'kpi_net' => 'Total Net Paid',
        'kpi_draft' => 'Draft Payrolls',
        'kpi_paid' => 'Paid Payrolls',
        'all_years' => '-- All Years --',
        'all_statuses' => '-- All Statuses --',
        'status_draft' => 'Draft',
        'status_processed' => 'Processed & Approved',
        'status_paid' => 'Fully Paid',
        'basic_lbl' => 'Basic:',
        'allow_lbl' => 'Allowances:',
        'print_list' => 'Print List',
        'confirm_delete' => 'Are you sure you want to delete this payroll run?'
    ]
][$isRtl ? 'ar' : 'en'];

$statusMap = [
    'draft'     => ['label' => $t['status_draft'], 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-pencil-line'],
    'processed' => ['label' => $t['status_processed'], 'color' => '#0284c7', 'bg' => '#e0f2fe', 'icon' => 'ph-gear'],
    'paid'      => ['label' => $t['status_paid'], 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
];
?>

<style>
    :root {
        --c-pay: #be123c;
        --c-pay-dark: #9f1239;
        --c-pay-light: #ffe4e6;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .pay-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .pay-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .pay-title-box { display: flex; align-items: center; gap: 16px; }
    .pay-icon { width: 48px; height: 48px; background: var(--c-pay-light); color: var(--c-pay); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(190, 18, 60, 0.15); }
    .pay-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-pay { background: linear-gradient(135deg, var(--c-pay), var(--c-pay-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(190, 18, 60, 0.25); }
    .btn-print { background: #0f172a; color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #1e293b; }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 24px; }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-pay-light); color: var(--c-pay); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .pay-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .pay-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .pay-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-pay-light); color: var(--c-pay); border-color: #fecdd3; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-pay); color: #ffffff; border-color: var(--c-pay); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
    .branch-badge { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; border: 1px solid #cbd5e1; display: inline-block; margin-top: 4px;}

    /* إخفاء DataTables Injection */
    .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { display: none !important; }

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body { background: #ffffff !important; color: #000000 !important; }
        .nt-sidebar, header, nav, footer { display: none !important; }
        .search-bar, .pagination, .btn-pay, .btn-print, .action-btn { display: none !important; }
        .pay-table th:last-child, .pay-table td:last-child { display: none !important; }
        .pay-wrapper { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; border-radius: 0 !important; }
        .pay-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .pay-table td { border: 1px solid #cbd5e1 !important; color: #000000 !important; }
        .kpi-card { border: 1px solid #000 !important; box-shadow: none !important; break-inside: avoid; }
        .badge-status { border: 1px solid #000 !important; background: transparent !important; color: #000 !important; }
    }
</style>

<div class="pay-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="pay-header">
        <div class="pay-title-box">
            <div class="pay-icon"><i class="ph-duotone ph-coins"></i></div>
            <div>
                <h2 class="pay-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print_list'] ?></button>
            <a href="/ERP/hr/payroll/create" class="btn-pay"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-pay);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-pay); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-receipt"></i></div><div class="kpi-info"><h4><?= $t['kpi_total'] ?></h4><p><?= number_format((int)($stats->total_runs ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-currency-dollar"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= $t['kpi_net'] ?></h4><p><?= number_format($convert($stats->total_net_paid ?? 0), 2) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-pencil-line"></i></div><div class="kpi-info"><h4 style="color:#d97706;"><?= $t['kpi_draft'] ?></h4><p><?= number_format((int)($stats->draft_runs ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#e0f2fe; color:#0284c7;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#0284c7;"><?= $t['kpi_paid'] ?></h4><p><?= number_format((int)($stats->paid_runs ?? 0)) ?></p></div></div>
    </div>

    <form action="/ERP/hr/payroll" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="year" class="form-control" style="flex:1; min-width:120px;">
            <option value=""><?= $t['all_years'] ?></option>
            <?php for($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                <option value="<?= $y ?>" <?= ($yearFilter == $y)?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>

        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value=""><?= $t['all_statuses'] ?></option>
            <option value="draft" <?= ($statusFilter==='draft')?'selected':'' ?>><?= $t['status_draft'] ?></option>
            <option value="processed" <?= ($statusFilter==='processed')?'selected':'' ?>><?= $t['status_processed'] ?></option>
            <option value="paid" <?= ($statusFilter==='paid')?'selected':'' ?>><?= $t['status_paid'] ?></option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($yearFilter) || !empty($statusFilter)): ?>
            <a href="/ERP/hr/payroll" class="btn-search" style="background:#f1f5f9; color:#475569; text-decoration:none;"><i class="ph-bold ph-x"></i> <?= $t['btn_clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="pay-table">
            <thead>
                <tr>
                    <th style="width: 15%;"><?= $t['col_code'] ?></th>
                    <th style="width: 15%;"><?= $t['col_month'] ?></th>
                    <th style="width: 18%;"><?= $t['col_salaries'] ?></th>
                    <th style="width: 18%;"><?= $t['col_deductions'] ?></th>
                    <th style="width: 18%;"><?= $t['col_net'] ?></th>
                    <th style="width: 8%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 8%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payrolls)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($payrolls as $p): 
                    $st = $statusMap[$p->status] ?? $statusMap['processed'];
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-pay-dark); font-size: 0.95rem;">
                            <a href="/ERP/hr/payroll/<?= $p->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars((string)$p->payroll_code) ?></a>
                            <?php if(!empty($p->branch_name)): ?>
                                <br><div class="branch-badge"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars((string)$p->branch_name) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 800; color: var(--c-text-dark);">
                            <?= htmlspecialchars((string)$p->month) ?> / <?= $p->year ?>
                        </td>
                        <td style="font-family: monospace; font-weight: 700;">
                            <div><span style="color:#64748b;"><?= $t['basic_lbl'] ?></span> <?= number_format($convert($p->total_basic ?? 0), 2) ?></div>
                            <div style="color:#0284c7;"><span style="color:#64748b;"><?= $t['allow_lbl'] ?></span> <?= number_format($convert($p->total_allowances ?? 0), 2) ?></div>
                        </td>
                        <td style="font-family: monospace; font-weight: 800; color: #dc2626;">
                            <?= number_format($convert($p->total_deductions ?? 0), 2) ?>
                        </td>
                        <td style="font-family: monospace; font-weight: 900; color: #059669; font-size: 1.05rem;">
                            <?= number_format($convert($p->net_pay ?? 0), 2) ?> <span style="font-size:0.75rem; color:#64748b;"><?= $currency ?></span>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/hr/payroll/<?= $p->id ?>" class="action-btn" title="عرض المسير والطباعة"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/hr/payroll/<?= $p->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/hr/payroll/<?= $p->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
                                <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&year=<?= urlencode($yearFilter) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.table-card .dataTables_filter, .table-card .dataTables_length, .table-card .dataTables_info, .table-card .dataTables_paginate').forEach(el => el.remove());
});
</script>