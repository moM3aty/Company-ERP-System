<?php
// Path: resources/views/accounting/journals/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'قيود اليومية العامة (Journal Entries)', 'desc' => 'السجل المحاسبي المركزي لجميع الحركات والعمليات المالية.',
        'add_btn' => 'إنشاء قيد جديد', 'search' => 'ابحث برقم القيد، البيان، المرجع...',
        'status_all' => '-- جميع الحالات --', 'branch_all' => '-- كل الفروع --', 'btn_search' => 'تصفية وبحث', 'clear' => 'إلغاء',
        'stat_total' => 'إجمالي القيود', 'stat_draft' => 'المسودات (Drafts)', 'stat_posted' => 'المرحلة (Posted)', 'stat_val' => 'إجمالي المبالغ',
        'col_no' => 'رقم القيد', 'col_date' => 'التاريخ', 'col_branch' => 'الفرع', 'col_desc' => 'البيان / الوصف', 'col_ref' => 'رقم المرجع', 'col_amt' => 'إجمالي القيد', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات',
        'empty' => 'لا توجد قيود يومية مسجلة.',
        'st_draft' => 'مسودة (Draft)', 'st_posted' => 'مرحّل (Posted)', 'st_cancelled' => 'ملغى', 'general' => 'عام'
    ],
    'en' => [
        'title' => 'Journal Entries', 'desc' => 'Central accounting ledger for all financial movements and operations.',
        'add_btn' => 'Create Entry', 'search' => 'Search by entry no, description, ref...',
        'status_all' => '-- All Statuses --', 'branch_all' => '-- All Branches --', 'btn_search' => 'Filter', 'clear' => 'Clear',
        'stat_total' => 'Total Entries', 'stat_draft' => 'Drafts', 'stat_posted' => 'Posted', 'stat_val' => 'Total Amount',
        'col_no' => 'Entry No.', 'col_date' => 'Date', 'col_branch' => 'Branch', 'col_desc' => 'Description', 'col_ref' => 'Ref No.', 'col_amt' => 'Total', 'col_status' => 'Status', 'col_actions' => 'Actions',
        'empty' => 'No journal entries found.',
        'st_draft' => 'Draft', 'st_posted' => 'Posted', 'st_cancelled' => 'Cancelled', 'general' => 'General'
    ]
][$isRtl ? 'ar' : 'en'];

function getEntryStatusBadgeView($status, $t) {
    $map = [
        'draft'  => ['bg' => '#fef3c7', 'color' => '#d97706', 'label' => $t['st_draft'], 'icon' => 'ph-pencil-line'],
        'posted' => ['bg' => '#e0e7ff', 'color' => '#4338ca', 'label' => $t['st_posted'], 'icon' => 'ph-check-circle']
    ];
    $s = $map[$status] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $status, 'icon' => 'ph-file'];
    return "<span style='background:{$s['bg']}; color:{$s['color']}; padding:6px 12px; border-radius:8px; font-weight:800; font-size:0.75rem; border:1px solid currentColor; display:inline-flex; align-items:center; gap:4px;'><i class='ph-fill {$s['icon']}'></i> {$s['label']}</span>";
}
?>

<style>
    :root { 
        --brand-primary: #4f46e5; --brand-primary-dark: #3730a3; --brand-primary-light: #e0e7ff; 
        --surface: #ffffff; --surface-hover: #f8fafc; --bg-body: #f1f5f9;
        --text-main: #0f172a; --text-muted: #64748b; --border-color: #e2e8f0;
        --radius-lg: 24px; --radius-md: 16px;
        --shadow-soft: 0 10px 30px -10px rgba(15, 23, 42, 0.08);
        --shadow-hover: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
    }
    .je-wrapper { padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: var(--bg-body);}
    
    .glass-header { position: sticky; top: 0; z-index: 50; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); padding: 20px 30px; margin-bottom: 30px; border-bottom: 1px solid rgba(226, 232, 240, 0.6); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
    .header-icon { width: 56px; height: 56px; background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)); color: white; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3); }
    .header-text h1 { margin: 0; font-size: 1.8rem; font-weight: 900; color: var(--text-main); }
    .header-text p { margin: 4px 0 0 0; font-size: 0.95rem; font-weight: 600; color: var(--text-muted); }
    
    .btn-primary { background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)); color: white !important; padding: 12px 24px; border-radius: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 8px 20px rgba(79, 70, 229, 0.25); transition: 0.3s; border: none;}
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(79, 70, 229, 0.35); }

    .kpi-row { padding: 0 30px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
    @media(max-width:1100px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    
    .kpi-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 20px; display: flex; align-items: center; gap: 14px; box-shadow: var(--shadow-soft); transition: 0.4s;}
    .kpi-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-hover); }
    .kpi-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: var(--brand-primary-light); color: var(--brand-primary); }
    .kpi-info h4 { margin: 0 0 4px 0; font-size: 0.8rem; color: var(--text-muted); font-weight: 800; text-transform:uppercase;}
    .kpi-info p { margin: 0; font-size: 1.6rem; font-weight: 900; font-family: monospace; color: var(--text-main); }

    .search-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px; margin: 0 30px 30px 30px; display: flex; gap: 12px; box-shadow: var(--shadow-soft); align-items: center;}
    .form-control { border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 16px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; color: var(--text-main); transition: 0.3s;}
    .form-control:focus { outline: none; border-color: var(--brand-primary); background: #fff; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); }
    .btn-search { background: var(--text-main); color: #fff; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; transition: 0.3s;}
    .btn-search:hover { background: #000; box-shadow: var(--shadow-soft);}

    .table-container { margin: 0 30px; background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-soft); }
    .modern-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: start; }
    .modern-table th { padding: 18px 20px; background: var(--surface-hover); color: var(--text-muted); font-weight: 900; text-transform: uppercase; border-bottom: 2px solid var(--border-color); font-size: 0.8rem; }
    .modern-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .modern-table tr:hover td { background: var(--surface-hover); }

    .action-btn { width: 38px; height: 38px; border-radius: 10px; border: 1px solid var(--border-color); background: #fff; color: var(--text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 3px; transition: 0.3s; }
    .action-btn:hover { background: var(--brand-primary-light); color: var(--brand-primary); border-color: #a5b4fc; }
    .action-btn.post:hover { background: #d1fae5; color: #059669; border-color: #6ee7b7; }
    .action-btn.delete:hover { background: #ffe4e6; color: #e11d48; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 30px; }
    .page-link { width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid var(--border-color); background: #ffffff; color: var(--text-muted); text-decoration: none; font-weight: 900; transition:0.3s;}
    .page-link.active { background: var(--brand-primary); color: #ffffff; border-color: var(--brand-primary); box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);}
    .page-link:hover:not(.active) { background: var(--surface-hover); }

    /* Custom Confirm Modal */
    .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); display: none; align-items: center; justify-content: center; z-index: 9999; }
    .modal-card { background: #ffffff; border-radius: 24px; width: 100%; max-width: 440px; padding: 36px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: modalIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    @keyframes modalIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .modal-icon-circle { width: 72px; height: 72px; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; }
    .modal-actions-row { display: flex; gap: 16px; margin-top: 30px; justify-content: center; }
    .btn-modal-cancel { flex: 1; padding: 14px; border-radius: 12px; border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; font-weight: 900; cursor: pointer; transition:0.2s;}
    .btn-modal-cancel:hover { background: #e2e8f0; }
    .btn-modal-confirm { flex: 1; padding: 14px; border-radius: 12px; border: none; color: #ffffff; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition:0.2s;}
</style>

<div class="je-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="glass-header">
        <div style="display:flex; align-items:center; gap:18px;">
            <div class="header-icon"><i class="ph-duotone ph-notebook"></i></div>
            <div class="header-text">
                <h1><?= $t['title'] ?></h1>
                <p><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/accounting/journal-entries/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if (!empty($dbErrors)): ?>
        <div style="margin: 0 30px 24px 30px; background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 16px; border-radius: 14px; font-weight: bold; font-family: monospace; display:flex; align-items:center; gap:10px; box-shadow:var(--shadow-soft);">
            <i class="ph-bold ph-warning-circle" style="font-size:1.6rem;"></i>
            <div>
                <strong>تنبيه بقاعدة البيانات:</strong> הגداول تفتقر لتحديثات دعم الفروع، يتم استخدام الوضع العام (الآمن) مؤقتاً.
            </div>
        </div>
    <?php endif; ?>

    <?php if($flashMsg): ?><div style="margin: 0 30px 24px 30px; background: #ecfdf5; color: #059669; padding: 16px 20px; border-radius: 14px; font-weight: 800; border: 1px solid #a7f3d0; display:flex; align-items:center; gap:10px;"><i class="ph-fill ph-check-circle" style="font-size:1.4rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="margin: 0 30px 24px 30px; background: #fff1f2; color: #e11d48; padding: 16px 20px; border-radius: 14px; font-weight: 800; border: 1px solid #fecdd3; display:flex; align-items:center; gap:10px;"><i class="ph-fill ph-warning-circle" style="font-size:1.4rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-files"></i></div><div class="kpi-info"><h4><?= $t['stat_total'] ?></h4><p><?= number_format($stats->total_entries ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-pencil-line"></i></div><div class="kpi-info"><h4 style="color:#d97706;"><?= $t['stat_draft'] ?></h4><p style="color:#d97706;"><?= number_format($stats->drafts ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:var(--brand-primary-light); color:var(--brand-primary);"><i class="ph-duotone ph-check-square-offset"></i></div><div class="kpi-info"><h4 style="color:var(--brand-primary);"><?= $t['stat_posted'] ?></h4><p style="color:var(--brand-primary);"><?= number_format($stats->posted ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#d1fae5; color:#059669;"><i class="ph-duotone ph-currency-circle-dollar"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= $t['stat_val'] ?></h4><p style="color:#059669;"><?= number_format((float)($stats->total_value ?? 0), 2) ?></p></div></div>
    </div>

    <form action="/ERP/accounting/journal-entries" method="GET" class="search-card">
        <input type="text" name="search" class="form-control" style="flex:2;" placeholder="<?= $t['search'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        
        <?php if(!empty($branches)): ?>
        <select name="branch_id" class="form-control" style="flex:1;">
            <option value=""><?= $t['branch_all'] ?></option>
            <?php foreach($branches as $b): 
                $bName = $isRtl ? $b->name_ar : ($b->name_en ?: $b->name_ar);
            ?>
                <option value="<?= $b->id ?>" <?= ($branchFilter == $b->id) ? 'selected' : '' ?>><?= htmlspecialchars($bName) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <select name="status" class="form-control" style="flex:1;">
            <option value=""><?= $t['status_all'] ?></option>
            <option value="draft" <?= ($statusFilter==='draft')?'selected':'' ?>><?= $t['st_draft'] ?></option>
            <option value="posted" <?= ($statusFilter==='posted')?'selected':'' ?>><?= $t['st_posted'] ?></option>
        </select>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($statusFilter) || !empty($branchFilter)): ?>
            <a href="/ERP/accounting/journal-entries" class="btn-search" style="background:var(--surface-hover); color:var(--text-muted); text-decoration:none;"><i class="ph-bold ph-x"></i> <?= $t['clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-container">
        <div style="overflow-x:auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 14%;"><?= $t['col_no'] ?></th>
                        <th style="width: 12%;"><?= $t['col_date'] ?></th>
                        <th style="width: 32%;"><?= $t['col_desc'] ?></th>
                        <th style="width: 12%;"><?= $t['col_ref'] ?></th>
                        <th style="width: 12%; text-align: center;"><?= $t['col_amt'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 13%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entries)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 50px; color: var(--text-muted); font-weight: 800; font-size:1.1rem;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach ($entries as $e): 
                        $branchBadge = !empty($e->branch_name) ? ($isRtl ? $e->branch_name : ($e->branch_name_en ?: $e->branch_name)) : $t['general'];
                    ?>
                        <tr>
                            <td>
                                <a href="/ERP/accounting/journal-entries/<?= $e->id ?>" style="font-weight: 900; color: var(--brand-primary); font-family: monospace; font-size: 1.05rem; text-decoration:none;">
                                    <?= htmlspecialchars($e->entry_number ?? '') ?>
                                </a>
                                <?php if(!empty($branches)): ?>
                                <div style="margin-top:4px;">
                                    <span style="background:var(--surface-hover); border:1px solid var(--border-color); color:var(--text-muted); padding:2px 6px; border-radius:4px; font-size:0.7rem; font-weight:bold;">
                                        <i class="ph-bold ph-git-branch"></i> <?= htmlspecialchars($branchBadge) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td style="font-family: monospace; font-weight: 800; color: var(--text-main);"><?= htmlspecialchars($e->entry_date ?? '') ?></td>
                            <td style="font-weight: 800; color: var(--text-main);"><?= htmlspecialchars($e->description ?? '---') ?></td>
                            <td><span style="font-size:0.85rem; color:var(--text-muted); font-family:monospace; font-weight:bold;"><?= htmlspecialchars($e->reference_number ?? '---') ?></span></td>
                            <td style="text-align: center; font-family: monospace; font-weight: 900; color: var(--text-main); font-size:1.05rem;">
                                <?= number_format((float)($e->total_amount ?? 0), 2) ?> <span style="font-size:0.75rem; color:var(--text-muted);"><?= $currency ?></span>
                            </td>
                            <td style="text-align: center;"><?= getEntryStatusBadgeView($e->status ?? '', $t) ?></td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="/ERP/accounting/journal-entries/<?= $e->id ?>" class="action-btn" title="View Voucher"><i class="ph-bold ph-eye"></i></a>
                                
                                <?php if(($e->status ?? '') === 'draft'): ?>
                                    <a href="/ERP/accounting/journal-entries/<?= $e->id ?>/edit" class="action-btn" title="Edit"><i class="ph-bold ph-pencil-simple"></i></a>
                                    
                                    <button type="button" class="action-btn post" title="Post Entry" onclick="triggerPostModal('/ERP/accounting/journal-entries/<?= $e->id ?>/post')">
                                        <i class="ph-bold ph-check-square-offset"></i>
                                    </button>
                                    
                                    <button type="button" class="action-btn delete" title="Delete" onclick="triggerDeleteModal('/ERP/accounting/journal-entries/<?= $e->id ?>/delete')">
                                        <i class="ph-bold ph-trash"></i>
                                    </button>
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
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>&status=<?= urlencode($statusFilter ?? '') ?>&branch_id=<?= urlencode($branchFilter ?? '') ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Dynamic Action Modal -->
<div id="actionModal" class="modal-overlay">
    <div class="modal-card">
        <div id="modalIcon" class="modal-icon-circle"></div>
        <h3 id="modalTitle" style="margin: 0 0 8px 0; font-size: 1.4rem; font-weight: 900; color: var(--text-main);"></h3>
        <p id="modalMsg" style="margin: 0; color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; font-weight: 700;"></p>
        
        <div class="modal-actions-row">
            <button type="button" onclick="closeActionModal()" class="btn-modal-cancel"><?= $isRtl ? 'تراجع وإلغاء' : 'Cancel' ?></button>
            <form id="actionForm" method="POST" style="flex: 1;">
                <button type="submit" id="modalBtnSubmit" class="btn-modal-confirm" style="width: 100%;"></button>
            </form>
        </div>
    </div>
</div>

<script>
function triggerPostModal(actionUrl) {
    document.getElementById('actionForm').action = actionUrl;
    document.getElementById('modalTitle').innerText = '<?= $isRtl ? 'تأكيد ترحيل القيد' : 'Confirm Posting' ?>';
    document.getElementById('modalMsg').innerText = '<?= $isRtl ? 'هل أنت متأكد من ترحيل هذا القيد للدفاتر؟ لن يمكنك تعديله بعد الترحيل.' : 'Are you sure you want to post this entry? It cannot be edited later.' ?>';
    
    let icon = document.getElementById('modalIcon');
    icon.innerHTML = '<i class="ph-bold ph-check-circle" style="font-size:2.5rem; color:var(--brand-primary);"></i>';
    icon.style.background = 'var(--brand-primary-light)';
    
    let btn = document.getElementById('modalBtnSubmit');
    btn.innerHTML = '<i class="ph-bold ph-check"></i> <?= $isRtl ? 'تأكيد الترحيل' : 'Confirm Post' ?>';
    btn.style.background = 'var(--brand-primary)';
    
    document.getElementById('actionModal').style.display = 'flex';
}

function triggerDeleteModal(actionUrl) {
    document.getElementById('actionForm').action = actionUrl;
    document.getElementById('modalTitle').innerText = '<?= $isRtl ? 'تحذير الحذف!' : 'Delete Warning!' ?>';
    document.getElementById('modalMsg').innerText = '<?= $isRtl ? 'هل أنت متأكد من حذف هذه المسودة تماماً؟ لا يمكن التراجع.' : 'Are you sure you want to delete this draft completely? Cannot be undone.' ?>';
    
    let icon = document.getElementById('modalIcon');
    icon.innerHTML = '<i class="ph-bold ph-warning-circle" style="font-size:2.5rem; color:#dc2626;"></i>';
    icon.style.background = '#fef2f2';
    
    let btn = document.getElementById('modalBtnSubmit');
    btn.innerHTML = '<i class="ph-bold ph-trash"></i> <?= $isRtl ? 'تأكيد الحذف' : 'Confirm Delete' ?>';
    btn.style.background = '#dc2626';
    
    document.getElementById('actionModal').style.display = 'flex';
}

function closeActionModal() {
    document.getElementById('actionModal').style.display = 'none';
}
</script>