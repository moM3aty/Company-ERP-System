<?php
// Path: resources/views/treasury/cheques/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title' => 'إدارة الشيكات والأوراق المالية (Cheques)', 'desc' => 'حصر ومتابعة حركة الشيكات الواردة والصادرة وتواريخ الاستحقاق والتحصيل.',
        'add_btn' => 'تسجيل شيك جديد', 'col_num' => 'رقم الشيك', 'col_type' => 'النوع',
        'col_bank' => 'البنك المسحوب عليه', 'col_payee' => 'المستفيد / الساحب', 'col_due' => 'تاريخ الاستحقاق',
        'col_amount' => 'المبلغ', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات',
        'empty' => 'لا توجد شيكات أو أوراق مالية مسجلة.', 'search_ph' => 'ابحث برقم الشيك، اسم البنك، الساحب/المستفيد...',
        'btn_search' => 'فلترة', 'btn_clear' => 'إلغاء', 'active_scope' => 'الفرع النشط:',
        'kpi_total' => 'إجمالي الشيكات', 'kpi_pending' => 'شيكات معلقة (برسم التحصيل)', 
        'kpi_collected' => 'شيكات محصلة ومقبولة', 'kpi_bounced' => 'شيكات مرتجعة ومرفوضة',
        'all_types' => '-- كل الأنواع --', 'all_statuses' => '-- كل الحالات --',
        'type_received' => 'وارد (استلام)', 'type_issued' => 'صادر (دفع)',
        'received_badge' => 'شيك وارد', 'issued_badge' => 'شيك صادر',
        'status_pending' => 'برسم التحصيل / معلق', 'status_collected' => 'محصل / مقبول', 
        'status_bounced' => 'مرتد / مرفوض', 'status_cancelled' => 'ملغى',
        'print_list' => 'طباعة القائمة', 'confirm_delete' => 'هل أنت متأكد من حذف سجل الشيك؟'
    ],
    'en' => [
        'title' => 'Cheques Management', 'desc' => 'Track incoming and outgoing cheques, due dates, and clearing status.',
        'add_btn' => 'Register New Cheque', 'col_num' => 'Cheque No.', 'col_type' => 'Type',
        'col_bank' => 'Bank Name', 'col_payee' => 'Payee / Payer', 'col_due' => 'Due Date',
        'col_amount' => 'Amount', 'col_status' => 'Status', 'col_actions' => 'Actions',
        'empty' => 'No cheques recorded.', 'search_ph' => 'Search by cheque no, bank, payee...',
        'btn_search' => 'Filter', 'btn_clear' => 'Clear', 'active_scope' => 'Active Branch:',
        'kpi_total' => 'Total Cheques', 'kpi_pending' => 'Pending (Under Collection)', 
        'kpi_collected' => 'Collected & Cleared', 'kpi_bounced' => 'Bounced & Rejected',
        'all_types' => '-- All Types --', 'all_statuses' => '-- All Statuses --',
        'type_received' => 'Received (Inward)', 'type_issued' => 'Issued (Outward)',
        'received_badge' => 'Received', 'issued_badge' => 'Issued',
        'status_pending' => 'Pending', 'status_collected' => 'Collected', 
        'status_bounced' => 'Bounced', 'status_cancelled' => 'Cancelled',
        'print_list' => 'Print List', 'confirm_delete' => 'Are you sure you want to delete this cheque record?'
    ]
][$isRtl ? 'ar' : 'en'];

function getChequeStatusBadge($status, $t) {
    $map = [
        'pending'   => ['label' => $t['status_pending'], 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-clock'],
        'collected' => ['label' => $t['status_collected'], 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
        'bounced'   => ['label' => $t['status_bounced'], 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-warning-circle'],
        'cancelled' => ['label' => $t['status_cancelled'], 'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'ph-x-circle'],
    ];
    $s = $map[$status] ?? $map['pending'];
    return "<span class='badge-status' style='background:{$s['bg']}; color:{$s['color']}; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem; display:inline-flex; align-items:center; gap:4px; border:1px solid currentColor;'><i class='ph-bold {$s['icon']}'></i> {$s['label']}</span>";
}
?>

<style>
    :root {
        --c-chq: #c026d3;
        --c-chq-dark: #a21caf;
        --c-chq-light: #fdf4ff;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .chq-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .chq-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .chq-title-box { display: flex; align-items: center; gap: 16px; }
    .chq-icon { width: 48px; height: 48px; background: var(--c-chq-light); color: var(--c-chq); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(192, 38, 211, 0.12); }
    .chq-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-chq { background: linear-gradient(135deg, var(--c-chq), var(--c-chq-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(192, 38, 211, 0.2); }
    .btn-print { background: #0f172a; color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-print:hover { background: #1e293b; }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 24px; }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-chq-light); color: var(--c-chq); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .chq-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .chq-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .chq-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-chq-light); color: var(--c-chq); border-color: #f5d0fe; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-chq); color: #ffffff; border-color: var(--c-chq); }

    .branch-badge { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; border: 1px solid #cbd5e1; display: inline-block; margin-top: 4px;}

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body { background: #ffffff !important; color: #000000 !important; }
        .nt-sidebar, header, nav, footer { display: none !important; }
        .search-bar, .pagination, .btn-chq, .btn-print, .action-btn { display: none !important; }
        .chq-table th:last-child, .chq-table td:last-child { display: none !important; }
        .chq-wrapper { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; border-radius: 0 !important; }
        .chq-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .chq-table td { border: 1px solid #cbd5e1 !important; color: #000000 !important; }
        .kpi-card { border: 1px solid #000 !important; box-shadow: none !important; break-inside: avoid; }
        .badge-status { border: 1px solid #000 !important; background: transparent !important; color: #000 !important; }
    }
</style>

<div class="chq-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="chq-header">
        <div class="chq-title-box">
            <div class="chq-icon"><i class="ph-duotone ph-checks"></i></div>
            <div>
                <h2 class="chq-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print_list'] ?></button>
            <a href="/ERP/treasury/cheques/create" class="btn-chq"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-chq);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-chq); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-files"></i></div><div class="kpi-info"><h4><?= $t['kpi_total'] ?></h4><p><?= number_format((int)($stats->total_cheques ?? 0)) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-clock"></i></div><div class="kpi-info"><h4 style="color:#d97706;"><?= $t['kpi_pending'] ?></h4><p><?= number_format($convert($stats->pending_amount ?? 0), 2) ?> <span style="font-size:0.75rem;"><?= $currency ?></span></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= $t['kpi_collected'] ?></h4><p><?= number_format($convert($stats->collected_amount ?? 0), 2) ?> <span style="font-size:0.75rem;"><?= $currency ?></span></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-warning-circle"></i></div><div class="kpi-info"><h4 style="color:#dc2626;"><?= $t['kpi_bounced'] ?></h4><p><?= number_format($convert($stats->bounced_amount ?? 0), 2) ?> <span style="font-size:0.75rem;"><?= $currency ?></span></p></div></div>
    </div>

    <form action="/ERP/treasury/cheques" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="type" class="form-control" style="flex:1; min-width:140px;">
            <option value=""><?= $t['all_types'] ?></option>
            <option value="received" <?= ($typeFilter==='received')?'selected':'' ?>><?= $t['type_received'] ?></option>
            <option value="issued" <?= ($typeFilter==='issued')?'selected':'' ?>><?= $t['type_issued'] ?></option>
        </select>
        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value=""><?= $t['all_statuses'] ?></option>
            <option value="pending" <?= ($statusFilter==='pending')?'selected':'' ?>><?= $t['status_pending'] ?></option>
            <option value="collected" <?= ($statusFilter==='collected')?'selected':'' ?>><?= $t['status_collected'] ?></option>
            <option value="bounced" <?= ($statusFilter==='bounced')?'selected':'' ?>><?= $t['status_bounced'] ?></option>
            <option value="cancelled" <?= ($statusFilter==='cancelled')?'selected':'' ?>><?= $t['status_cancelled'] ?></option>
        </select>
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($fromDate ?? '') ?>" title="<?= $t['col_due'] ?> (من)">
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($toDate ?? '') ?>" title="<?= $t['col_due'] ?> (إلى)">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($typeFilter) || !empty($statusFilter) || !empty($fromDate) || !empty($toDate)): ?>
            <a href="/ERP/treasury/cheques" class="btn-search" style="background:#f1f5f9; color:#475569; text-decoration:none;"><i class="ph-bold ph-x"></i> <?= $t['btn_clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="chq-table">
            <thead>
                <tr>
                    <th style="width: 14%;"><?= $t['col_num'] ?></th>
                    <th style="width: 10%;"><?= $t['col_type'] ?></th>
                    <th style="width: 16%;"><?= $t['col_bank'] ?></th>
                    <th style="width: 20%;"><?= $t['col_payee'] ?></th>
                    <th style="width: 12%;"><?= $t['col_due'] ?></th>
                    <th style="width: 14%;"><?= $t['col_amount'] ?></th>
                    <th style="width: 12%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cheques)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($cheques as $c): 
                    $isReceived = ($c->type ?? 'received') === 'received';
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-chq-dark); font-size: 0.95rem;">
                            <a href="/ERP/treasury/cheques/<?= $c->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars((string)$c->cheque_number) ?></a>
                        </td>
                        <td>
                            <span class="badge-status" style="<?= $isReceived ? 'background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;' : 'background:#fef2f2; color:#dc2626; border:1px solid #fecaca;' ?>">
                                <?= $isReceived ? $t['received_badge'] : $t['issued_badge'] ?>
                            </span>
                        </td>
                        <td style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars((string)$c->bank_name) ?></td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars((string)$c->payee_payer_name) ?></div>
                            <div style="font-size: 0.75rem; color: var(--c-text-muted);"><?= htmlspecialchars((string)($c->account_name ?? '')) ?></div>
                            <?php if(!empty($c->branch_name)): ?>
                                <div class="branch-badge"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars((string)$c->branch_name) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-family: monospace; font-weight: 800; color: <?= strtotime($c->due_date) < time() && $c->status === 'pending' ? '#dc2626' : '#0f172a' ?>;">
                            <?= htmlspecialchars((string)$c->due_date) ?>
                        </td>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-chq-dark); font-size: 1rem;">
                            <?= number_format($convert($c->amount ?? 0), 2) ?> <span style="font-size:0.75rem; color:#64748b;"><?= $currency ?></span>
                        </td>
                        <td style="text-align: center;">
                            <?= getChequeStatusBadge($c->status ?? 'pending', $t) ?>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/treasury/cheques/<?= $c->id ?>" class="action-btn" title="عرض وسند الشيك"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/treasury/cheques/<?= $c->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/treasury/cheques/<?= $c->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($typeFilter) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($fromDate) ?>&end_date=<?= urlencode($toDate) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>