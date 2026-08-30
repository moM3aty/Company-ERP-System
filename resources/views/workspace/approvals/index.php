<?php
// Path: resources/views/workspace/approvals/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);
?>

<style>
    /* 
      Amethyst Purple Theme (موافقاتي)
    */
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
    
    .ap-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid var(--ap-border); padding-bottom: 16px; flex-wrap: wrap; gap: 16px; }
    .ap-title-box { display: flex; align-items: center; gap: 16px; }
    .ap-icon { width: 52px; height: 50px; background: var(--ap-light); color: var(--ap-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 12px rgba(168, 85, 247, 0.15); border: 1px solid var(--ap-border); }
    .ap-title { margin: 0; color: var(--ap-text); font-size: 1.6rem; font-weight: 900; }

    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width: 768px){ .stats-grid { grid-template-columns: 1fr; } }
    .stat-card { background: var(--ap-card-bg, #fff); border: 1px solid var(--ap-border, #e2e8f0); border-radius: 14px; padding: 18px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border-left: 4px solid var(--ap-primary); }
    .stat-val { font-size: 1.6rem; font-weight: 900; color: var(--ap-text); font-family: monospace; }
    .stat-lbl { font-size: 0.85rem; font-weight: 700; color: var(--ap-muted); }

    .search-bar { background: var(--ap-card-bg, #fff); border: 1px solid var(--ap-border, #e2e8f0); border-radius: 12px; padding: 16px; display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; align-items: center; }
    .form-control { border: 1px solid var(--ap-border, #cbd5e1); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: var(--ap-card-bg, #f8fafc); color: var(--ap-text); outline: none; }
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
</style>

<div class="ap-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="ap-header">
        <div class="ap-title-box">
            <div class="ap-icon"><i class="ph-duotone ph-check-square-offset"></i></div>
            <div>
                <h2 class="ap-title"><?= __('مساحة الموافقات (My Approvals)', 'My Approvals') ?></h2>
                <p style="margin:4px 0 0 0; color:var(--ap-muted);"><?= __('المركز الموحد لمراجعة واعتماد الطلبات من كافة أقسام النظام.', 'Unified center for reviewing and approving requests across all modules.') ?></p>
            </div>
        </div>
    </div>

    <?php if($flashMsg): ?>
        <script>document.addEventListener('DOMContentLoaded', () => { if(typeof ERPAlerts !== 'undefined') ERPAlerts.toastSuccess("<?= htmlspecialchars($flashMsg) ?>"); });</script>
    <?php endif; ?>
    <?php if($flashErr): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div style="background:var(--ap-light); color:var(--ap-primary); width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="ph-fill ph-stack"></i></div>
            <div><div class="stat-val"><?= number_format($stats->total ?? 0) ?></div><div class="stat-lbl"><?= __('إجمالي المعلق', 'Total Pending') ?></div></div>
        </div>
        <div class="stat-card" style="border-left-color: #0284c7;">
            <div style="background:rgba(2, 132, 199, 0.12); color:#0284c7; width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="ph-fill ph-users"></i></div>
            <div><div class="stat-val"><?= number_format($stats->hr ?? 0) ?></div><div class="stat-lbl"><?= __('موافقات الموارد البشرية', 'HR Approvals') ?></div></div>
        </div>
        <div class="stat-card" style="border-left-color: #10b981;">
            <div style="background:rgba(16, 185, 129, 0.12); color:#10b981; width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="ph-fill ph-coins"></i></div>
            <div><div class="stat-val"><?= number_format($stats->financial ?? 0) ?></div><div class="stat-lbl"><?= __('موافقات مالية وشرائية', 'Finance & Purchasing') ?></div></div>
        </div>
    </div>

    <form action="/ERP/workspace/approvals" method="GET" class="search-bar">
        <div style="flex: 2; min-width: 250px;">
            <input type="text" name="search" class="form-control" style="width: 100%;" placeholder="<?= __('ابحث في تفاصيل الطلبات...', 'Search request details...') ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        </div>
        <div style="flex: 1; min-width: 150px;">
            <select name="type" class="form-control" style="width: 100%;">
                <option value=""><?= __('-- جميع الأقسام --', '-- All Modules --') ?></option>
                <option value="hr" <?= ($typeFilter === 'hr') ? 'selected' : '' ?>><?= __('الموارد البشرية', 'Human Resources') ?></option>
                <option value="financial" <?= ($typeFilter === 'financial') ? 'selected' : '' ?>><?= __('المالية والمشتريات', 'Finance & Purchasing') ?></option>
            </select>
        </div>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= __('تصفية', 'Filter') ?></button>
    </form>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="ap-table">
                <thead>
                    <tr>
                        <th style="width: 8%;"><?= __('رقم الإشارة', 'Ref ID') ?></th>
                        <th style="width: 15%;"><?= __('نوع الطلب', 'Request Type') ?></th>
                        <th style="width: 25%;"><?= __('تفاصيل وملخص الطلب', 'Summary & Details') ?></th>
                        <th style="width: 15%;"><?= __('تاريخ التقديم', 'Submitted On') ?></th>
                        <th style="width: 12%; text-align: center;"><?= __('القسم', 'Module') ?></th>
                        <th style="width: 25%; text-align: center;"><?= __('إجراءات الاعتماد', 'Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($approvals)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 60px; color: var(--ap-muted);">
                                <i class="ph-duotone ph-check-circle" style="font-size: 3rem; color: #10b981; margin-bottom:10px; display:block;"></i>
                                <strong style="font-size:1.1rem; display:block;"><?= __('ممتاز! صندوقك فارغ.', 'Great! Your inbox is empty.') ?></strong>
                                <?= __('لا توجد أي طلبات معلقة تتطلب موافقتك حالياً.', 'There are no pending requests requiring your approval right now.') ?>
                            </td>
                        </tr>
                    <?php else: foreach ($approvals as $item): ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: 900; color: var(--ap-primary);">#<?= $item->id ?></td>
                            <td style="font-weight: 800; color: var(--ap-text);">
                                <?= htmlspecialchars($item->type_name) ?>
                                <?php if(is_hq() && !empty($item->branch_name)): ?>
                                    <br><span class="branch-badge"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars($item->branch_name) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: 600; color: var(--ap-muted);"><?= htmlspecialchars($item->details ?? '---') ?></td>
                            <td style="font-family: monospace; font-weight: 600; font-size: 0.85rem; color: var(--ap-muted);"><?= date('Y-m-d H:i', strtotime($item->created_at)) ?></td>
                            <td style="text-align: center;">
                                <?php if($item->category === 'hr'): ?>
                                    <span style="background:rgba(2, 132, 199, 0.12); color:#0284c7; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem;">HR</span>
                                <?php else: ?>
                                    <span style="background:rgba(16, 185, 129, 0.12); color:#10b981; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem;">Finance</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap; gap: 8px; display: flex; justify-content: center;">
                                <?php if (has_permission('workspace_approvals_process')): ?>
                                    <form action="/ERP/workspace/approvals/process" method="POST" style="margin:0;">
                                        <input type="hidden" name="request_id" value="<?= $item->id ?>">
                                        <input type="hidden" name="source_module" value="<?= $item->source_module ?>">
                                        <input type="hidden" name="action_type" value="approve">
                                        <button type="submit" class="btn-approve" onclick="return confirm('<?= __('تأكيد اعتماد الطلب والموافقة عليه؟', 'Confirm approval of this request?') ?>')"><i class="ph-bold ph-check"></i> <?= __('اعتماد', 'Approve') ?></button>
                                    </form>

                                    <form action="/ERP/workspace/approvals/process" method="POST" style="margin:0;">
                                        <input type="hidden" name="request_id" value="<?= $item->id ?>">
                                        <input type="hidden" name="source_module" value="<?= $item->source_module ?>">
                                        <input type="hidden" name="action_type" value="reject">
                                        <button type="submit" class="btn-reject" onclick="return confirm('<?= __('تأكيد رفض الطلب؟', 'Confirm rejection of this request?') ?>')"><i class="ph-bold ph-x"></i> <?= __('رفض', 'Reject') ?></button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size:0.8rem; color:#94a3b8;"><i class="ph-bold ph-lock-key"></i> <?= __('لا تملك صلاحية', 'No Permission') ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
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