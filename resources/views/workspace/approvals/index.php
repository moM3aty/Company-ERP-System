<?php
// Path: resources/views/workspace/approvals/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$activeBranchId = (int)($_SESSION['branch_id'] ?? 0);
$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');
$isHq = ($activeBranchId === 0);

$checkPerm = function($perm) {
    return !function_exists('has_permission') || has_permission($perm);
};

$approvals = $pagedApprovals ?? $approvals ?? [];

$t = [
    'ar' => [
        'title' => 'مساحة الموافقات (My Approvals)',
        'desc' => 'المركز الموحد لمراجعة واعتماد الطلبات من كافة أقسام النظام.',
        'active_scope' => 'الفرع النشط:',
        'stat_total' => 'إجمالي المعلق',
        'stat_hr' => 'موافقات الموارد البشرية',
        'stat_fin' => 'موافقات مالية وشرائية',
        'search_ph' => 'ابحث في تفاصيل الطلبات...',
        'all_types' => '-- جميع الأقسام --',
        'type_hr' => 'الموارد البشرية',
        'type_fin' => 'المالية والمشتريات',
        'btn_filter' => 'تصفية',
        'col_ref' => 'رقم الإشارة',
        'col_type' => 'نوع الطلب',
        'col_details' => 'تفاصيل وملخص الطلب',
        'col_amount' => 'القيمة المالية',
        'col_date' => 'تاريخ التقديم',
        'col_mod' => 'القسم',
        'col_actions' => 'إجراءات الاعتماد',
        'empty_title' => 'ممتاز! صندوقك فارغ.',
        'empty_desc' => 'لا توجد أي طلبات معلقة تتطلب موافقتك حالياً.',
        'btn_approve' => 'اعتماد',
        'btn_reject' => 'رفض',
        'cancel' => 'تراجع',
        'confirm_approve' => 'تأكيد اعتماد الطلب والموافقة عليه؟',
        'confirm_reject' => 'تأكيد رفض الطلب؟',
        'no_perm' => 'لا تملك صلاحية'
    ],
    'en' => [
        'title' => 'My Approvals Workspace',
        'desc' => 'Unified center for reviewing and approving requests across all system modules.',
        'active_scope' => 'Active Branch:',
        'stat_total' => 'Total Pending',
        'stat_hr' => 'HR Approvals',
        'stat_fin' => 'Finance & Purchasing',
        'search_ph' => 'Search request details...',
        'all_types' => '-- All Modules --',
        'type_hr' => 'Human Resources',
        'type_fin' => 'Finance & Purchasing',
        'btn_filter' => 'Filter',
        'col_ref' => 'Ref ID',
        'col_type' => 'Request Type',
        'col_details' => 'Summary & Details',
        'col_amount' => 'Financial Amount',
        'col_date' => 'Submitted On',
        'col_mod' => 'Module',
        'col_actions' => 'Approval Actions',
        'empty_title' => 'Great! Your inbox is empty.',
        'empty_desc' => 'There are no pending requests requiring your approval right now.',
        'btn_approve' => 'Approve',
        'btn_reject' => 'Reject',
        'cancel' => 'Cancel',
        'confirm_approve' => 'Confirm approval of this request?',
        'confirm_reject' => 'Confirm rejection of this request?',
        'no_perm' => 'No Permission'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<!-- تضمين مكتبة SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root {
        --ap-primary: #a855f7;
        --ap-dark: #9333ea;
        --ap-light: #faf5ff;
        --ap-border: #e9d5ff;
        --ap-text: #1e293b;
        --ap-muted: #64748b;
    }

    body.dark-mode, [data-theme="dark"] {
        --ap-primary: #c084fc;
        --ap-dark: #a855f7;
        --ap-light: rgba(168, 85, 247, 0.12);
        --ap-border: #581c87;
        --ap-card-bg: #1e293b;
        --ap-text: #f8fafc;
        --ap-muted: #94a3b8;
    }

    .ap-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .ap-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; border-bottom: 1px solid var(--ap-border); padding-bottom: 16px; flex-wrap: wrap; gap: 16px; }
    .ap-title-box { display: flex; align-items: center; gap: 16px; }
    .ap-icon { width: 52px; height: 50px; background: var(--ap-light); color: var(--ap-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 12px rgba(168, 85, 247, 0.15); border: 1px solid var(--ap-border); }
    .ap-title { margin: 0; color: var(--ap-text); font-size: 1.6rem; font-weight: 900; }

    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid #cbd5e1; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--ap-text); margin-bottom: 24px; }

    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width: 768px){ .stats-grid { grid-template-columns: 1fr; } }
    .stat-card { background: var(--ap-card-bg, #fff); border: 1px solid var(--ap-border, #e2e8f0); border-radius: 14px; padding: 18px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border-left: 4px solid var(--ap-primary); }
    [dir="ltr"] .stat-card { border-left: 1px solid var(--ap-border, #e2e8f0); border-right: 4px solid var(--ap-primary); }
    .stat-val { font-size: 1.6rem; font-weight: 900; color: var(--ap-text); font-family: monospace; }
    .stat-lbl { font-size: 0.85rem; font-weight: 700; color: var(--ap-muted); }

    .search-bar { background: var(--ap-card-bg, #fff); border: 1px solid var(--ap-border, #e2e8f0); border-radius: 12px; padding: 16px; display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; align-items: center; }
    .form-control { border: 1px solid var(--ap-border, #cbd5e1); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: var(--ap-card-bg, #f8fafc); color: var(--ap-text); outline: none; box-sizing: border-box; }
    .form-control:focus { border-color: var(--ap-primary); box-shadow: 0 0 0 3px var(--ap-light); }
    .btn-search { background: var(--ap-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 8px;}

    .table-card { background: var(--ap-card-bg, #fff); border: 1px solid var(--ap-border, #e2e8f0); border-radius: 14px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .ap-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .ap-table th { padding: 16px 20px; background: var(--ap-light); color: var(--ap-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid var(--ap-border, #e2e8f0); font-size: 0.75rem; white-space: nowrap; }
    .ap-table td { padding: 14px 20px; border-bottom: 1px solid var(--ap-border, #e2e8f0); color: var(--ap-text); vertical-align: middle; }
    
    .btn-approve { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 6px 14px; border-radius: 6px; font-weight: 800; cursor: pointer; transition: 0.2s; font-size: 0.8rem; display:inline-flex; align-items:center; gap:4px; }
    .btn-approve:hover { background: #10b981; color: white; }
    
    .btn-reject { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 6px 14px; border-radius: 6px; font-weight: 800; cursor: pointer; transition: 0.2s; font-size: 0.8rem; display:inline-flex; align-items:center; gap:4px; }
    .btn-reject:hover { background: #ef4444; color: white; }

    .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 24px; }
    .page-link { padding: 8px 14px; border: 1px solid var(--ap-border, #cbd5e1); background: var(--ap-card-bg, #fff); color: var(--ap-text); border-radius: 8px; text-decoration: none; font-weight: 700; transition: 0.2s; }
    .page-link:hover { background: var(--ap-light); border-color: var(--ap-primary); color: var(--ap-primary); }
    .page-link.active { background: var(--ap-primary); color: #fff; border-color: var(--ap-primary); pointer-events: none; }

    .branch-badge { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; border: 1px solid #cbd5e1; display: inline-block; margin-top: 4px;}

    .table-card .dataTables_wrapper .dataTables_filter,
    .table-card .dataTables_wrapper .dataTables_length,
    .table-card .dataTables_wrapper .dataTables_info,
    .table-card .dataTables_wrapper .dataTables_paginate {
        display: none !important;
    }
</style>

<div class="ap-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="ap-header">
        <div class="ap-title-box">
            <div class="ap-icon"><i class="ph-duotone ph-check-square-offset"></i></div>
            <div>
                <h2 class="ap-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--ap-muted);"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--ap-primary);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--ap-primary); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if($flashMsg): ?>
        <div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div>
    <?php endif; ?>
    <?php if($flashErr): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div style="background:var(--ap-light); color:var(--ap-primary); width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="ph-fill ph-stack"></i></div>
            <div><div class="stat-val"><?= number_format($stats->total ?? 0) ?></div><div class="stat-lbl"><?= $t['stat_total'] ?></div></div>
        </div>
        <div class="stat-card" style="border-left-color: #0284c7;">
            <div style="background:rgba(2, 132, 199, 0.12); color:#0284c7; width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="ph-fill ph-users"></i></div>
            <div><div class="stat-val"><?= number_format($stats->hr ?? 0) ?></div><div class="stat-lbl"><?= $t['stat_hr'] ?></div></div>
        </div>
        <div class="stat-card" style="border-left-color: #10b981;">
            <div style="background:rgba(16, 185, 129, 0.12); color:#10b981; width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="ph-fill ph-coins"></i></div>
            <div><div class="stat-val"><?= number_format($stats->financial ?? 0) ?></div><div class="stat-lbl"><?= $t['stat_fin'] ?></div></div>
        </div>
    </div>

    <form action="/ERP/workspace/approvals" method="GET" class="search-bar">
        <div style="flex: 2; min-width: 250px;">
            <input type="text" name="search" class="form-control" style="width: 100%;" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        </div>
        <div style="flex: 1; min-width: 150px;">
            <select name="type" class="form-control" style="width: 100%;">
                <option value=""><?= $t['all_types'] ?></option>
                <option value="hr" <?= ($typeFilter === 'hr') ? 'selected' : '' ?>><?= $t['type_hr'] ?></option>
                <option value="financial" <?= ($typeFilter === 'financial') ? 'selected' : '' ?>><?= $t['type_fin'] ?></option>
            </select>
        </div>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_filter'] ?></button>
    </form>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="ap-table">
                <thead>
                    <tr>
                        <th style="width: 8%;"><?= $t['col_ref'] ?></th>
                        <th style="width: 15%;"><?= $t['col_type'] ?></th>
                        <th style="width: 25%;"><?= $t['col_details'] ?></th>
                        <th style="width: 12%; text-align:center;"><?= $t['col_amount'] ?></th>
                        <th style="width: 13%;"><?= $t['col_date'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_mod'] ?></th>
                        <th style="width: 17%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($approvals)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 60px; color: var(--ap-muted);">
                                <i class="ph-duotone ph-check-circle" style="font-size: 3rem; color: #10b981; margin-bottom:10px; display:block;"></i>
                                <strong style="font-size:1.1rem; display:block;"><?= $t['empty_title'] ?></strong>
                                <?= $t['empty_desc'] ?>
                            </td>
                        </tr>
                    <?php else: foreach ($approvals as $item): 
                        $typeName = $isRtl ? ($item->type_name_ar ?? $item->type_name ?? '') : ($item->type_name_en ?? $item->type_name ?? '');
                        $amt = (float)($item->amount ?? 0);
                    ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: 900; color: var(--ap-primary);">#<?= $item->id ?></td>
                            <td style="font-weight: 800; color: var(--ap-text);">
                                <?= htmlspecialchars((string)$typeName) ?>
                                <?php if($isHq && !empty($item->branch_name)): ?>
                                    <br><span class="branch-badge"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars((string)$item->branch_name) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: 600; color: var(--ap-muted);"><?= htmlspecialchars((string)($item->details ?? '---')) ?></td>
                            <td style="text-align: center; font-family: monospace; font-weight: 900; color: <?= $amt > 0 ? '#10b981' : '#64748b' ?>;">
                                <?= $amt > 0 ? number_format($convert($amt), 2) . " <span style='font-size:0.75rem;'>{$currency}</span>" : '---' ?>
                            </td>
                            <td style="font-family: monospace; font-weight: 600; font-size: 0.85rem; color: var(--ap-muted);"><?= date('Y-m-d H:i', strtotime($item->created_at ?? 'now')) ?></td>
                            <td style="text-align: center;">
                                <?php if(($item->category ?? '') === 'hr'): ?>
                                    <span style="background:rgba(2, 132, 199, 0.12); color:#0284c7; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem;">HR</span>
                                <?php else: ?>
                                    <span style="background:rgba(16, 185, 129, 0.12); color:#10b981; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem;">Finance</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap; gap: 8px; display: flex; justify-content: center;">
                                <?php if ($checkPerm('workspace_approvals_process')): ?>
                                    <form action="/ERP/workspace/approvals/process" method="POST" style="margin:0;">
                                        <input type="hidden" name="request_id" value="<?= $item->id ?>">
                                        <input type="hidden" name="source_module" value="<?= $item->source_module ?>">
                                        <input type="hidden" name="action_type" value="approve">
                                        <button type="button" class="btn-approve" onclick="handleApprovalAction(event, true)"><i class="ph-bold ph-check"></i> <?= $t['btn_approve'] ?></button>
                                    </form>

                                    <form action="/ERP/workspace/approvals/process" method="POST" style="margin:0;">
                                        <input type="hidden" name="request_id" value="<?= $item->id ?>">
                                        <input type="hidden" name="source_module" value="<?= $item->source_module ?>">
                                        <input type="hidden" name="action_type" value="reject">
                                        <button type="button" class="btn-reject" onclick="handleApprovalAction(event, false)"><i class="ph-bold ph-x"></i> <?= $t['btn_reject'] ?></button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size:0.8rem; color:#94a3b8;"><i class="ph-bold ph-lock-key"></i> <?= $t['no_perm'] ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($typeFilter) ?>" 
                   class="page-link <?= ($i === $currentPage) ? 'active' : '' ?>">
                   <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

</div>

<script>
function handleApprovalAction(event, isApprove) {
    event.preventDefault();
    const form = event.target.closest('form');
    
    const title = isApprove ? "<?= $t['confirm_approve'] ?>" : "<?= $t['confirm_reject'] ?>";
    const confirmButtonText = isApprove ? "<?= $t['btn_approve'] ?>" : "<?= $t['btn_reject'] ?>";
    const confirmButtonColor = isApprove ? "#059669" : "#dc2626";
    const iconType = isApprove ? "question" : "warning";

    Swal.fire({
        title: title,
        icon: iconType,
        showCancelButton: true,
        confirmButtonColor: confirmButtonColor,
        cancelButtonColor: "#64748b",
        confirmButtonText: confirmButtonText,
        cancelButtonText: "<?= $t['cancel'] ?>",
        reverseButtons: <?= $isRtl ? 'true' : 'false' ?>,
        customClass: {
            popup: 'swal2-border-radius'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}

document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.table-card .dataTables_filter, .table-card .dataTables_length, .table-card .dataTables_info, .table-card .dataTables_paginate').forEach(el => el.remove());
});
</script>