<?php
// Path: resources/views/accounting/cost_centers/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'مراكز التكلفة (Cost Centers)', 'desc' => 'تتبع وحصر الإيرادات والمصروفات حسب المشاريع، الفروع أو الأقسام الإدارية.',
        'add_btn' => 'إضافة مركز تكلفة', 'search' => 'ابحث برقم الكود، أو الاسم...',
        'status_all' => '-- كل الحالات --', 'branch_all' => '-- كل الفروع --', 'btn_search' => 'تصفية', 'clear' => 'إلغاء',
        'stat_total' => 'إجمالي المراكز', 'stat_parent' => 'مراكز رئيسية', 'stat_sub' => 'مراكز فرعية', 'stat_active' => 'المراكز النشطة',
        'col_code' => 'الكود', 'col_name' => 'اسم مركز التكلفة', 'col_budget' => 'الموازنة التقديرية', 'col_type' => 'النوع', 'col_parent' => 'المركز الأب', 'col_status' => 'الحالة', 'col_actions' => 'الإجراءات',
        'empty' => 'لا توجد مراكز تكلفة مسجلة.', 'confirm_del' => 'هل أنت متأكد من حذف مركز التكلفة؟',
        'general' => 'عام (كل الفروع)', 'st_active' => 'نشط', 'st_inactive' => 'معطل', 'type_parent' => 'رئيسي (Parent)', 'type_sub' => 'فرعي (Sub)'
    ],
    'en' => [
        'title' => 'Cost Centers', 'desc' => 'Track revenues and expenses by projects, branches, or departments.',
        'add_btn' => 'Add Cost Center', 'search' => 'Search by code or name...',
        'status_all' => '-- All Statuses --', 'branch_all' => '-- All Branches --', 'btn_search' => 'Filter', 'clear' => 'Clear',
        'stat_total' => 'Total Centers', 'stat_parent' => 'Parent Centers', 'stat_sub' => 'Sub Centers', 'stat_active' => 'Active Centers',
        'col_code' => 'Code', 'col_name' => 'Cost Center Name', 'col_budget' => 'Budget', 'col_type' => 'Type', 'col_parent' => 'Parent', 'col_status' => 'Status', 'col_actions' => 'Actions',
        'empty' => 'No cost centers found.', 'confirm_del' => 'Are you sure you want to delete this cost center?',
        'general' => 'General (All Branches)', 'st_active' => 'Active', 'st_inactive' => 'Inactive', 'type_parent' => 'Parent', 'type_sub' => 'Sub'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --brand-primary: #7c3aed; --brand-primary-dark: #6d28d9; --brand-primary-light: #f5f3ff; 
        --surface: #ffffff; --surface-hover: #f8fafc; --bg-body: #f1f5f9;
        --text-main: #0f172a; --text-muted: #64748b; --border-color: #e2e8f0;
        --radius-lg: 24px; --radius-md: 16px;
        --shadow-soft: 0 10px 30px -10px rgba(15, 23, 42, 0.08);
        --shadow-hover: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
    }
    .cc-wrapper { padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: var(--bg-body);}
    
    .glass-header { position: sticky; top: 0; z-index: 50; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); padding: 20px 30px; margin-bottom: 30px; border-bottom: 1px solid rgba(226, 232, 240, 0.6); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
    .header-icon { width: 56px; height: 56px; background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)); color: white; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 8px 20px rgba(124, 58, 237, 0.3); }
    .header-text h1 { margin: 0; font-size: 1.8rem; font-weight: 900; color: var(--text-main); }
    .header-text p { margin: 4px 0 0 0; font-size: 0.95rem; font-weight: 600; color: var(--text-muted); }
    
    .btn-primary { background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)); color: white !important; padding: 12px 24px; border-radius: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 8px 20px rgba(124, 58, 237, 0.25); transition: 0.3s; border: none;}
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(124, 58, 237, 0.35); }

    .kpi-row { padding: 0 30px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
    @media(max-width:1100px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    
    .kpi-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 20px; display: flex; align-items: center; gap: 14px; box-shadow: var(--shadow-soft); transition: 0.4s;}
    .kpi-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-hover); }
    .kpi-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: var(--brand-primary-light); color: var(--brand-primary); }
    .kpi-info h4 { margin: 0 0 4px 0; font-size: 0.8rem; color: var(--text-muted); font-weight: 800; text-transform:uppercase;}
    .kpi-info p { margin: 0; font-size: 1.6rem; font-weight: 900; font-family: monospace; color: var(--text-main); }

    .search-card { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px; margin: 0 30px 30px 30px; display: flex; gap: 12px; box-shadow: var(--shadow-soft); align-items: center;}
    .form-control { border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 16px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; color: var(--text-main); transition: 0.3s;}
    .form-control:focus { outline: none; border-color: var(--brand-primary); background: #fff; box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15); }
    .btn-search { background: var(--text-main); color: #fff; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; transition: 0.3s;}
    .btn-search:hover { background: #000; box-shadow: var(--shadow-soft);}

    .table-container { margin: 0 30px; background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-soft); }
    .modern-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: start; }
    .modern-table th { padding: 18px 20px; background: var(--surface-hover); color: var(--text-muted); font-weight: 900; text-transform: uppercase; border-bottom: 2px solid var(--border-color); font-size: 0.8rem; }
    .modern-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .modern-table tr:hover td { background: var(--surface-hover); }

    .action-btn { width: 38px; height: 38px; border-radius: 10px; border: 1px solid var(--border-color); background: #fff; color: var(--text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 3px; transition: 0.3s; }
    .action-btn:hover { background: var(--brand-primary-light); color: var(--brand-primary); border-color: #ddd6fe; }
    .action-btn.delete:hover { background: #ffe4e6; color: #e11d48; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 30px; }
    .page-link { width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid var(--border-color); background: #ffffff; color: var(--text-muted); text-decoration: none; font-weight: 900; transition:0.3s;}
    .page-link.active { background: var(--brand-primary); color: #ffffff; border-color: var(--brand-primary); box-shadow: 0 4px 10px rgba(124, 58, 237, 0.2);}
    .page-link:hover:not(.active) { background: var(--surface-hover); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-block; }
</style>

<div class="cc-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="glass-header">
        <div style="display:flex; align-items:center; gap:18px;">
            <div class="header-icon"><i class="ph-duotone ph-target"></i></div>
            <div class="header-text">
                <h1><?= $t['title'] ?></h1>
                <p><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/accounting/cost-centers/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if (!empty($dbErrors)): ?>
        <div style="margin: 0 30px 24px 30px; background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 16px; border-radius: 14px; font-weight: bold; font-family: monospace; display:flex; align-items:center; gap:10px; box-shadow:var(--shadow-soft);">
            <i class="ph-bold ph-warning-circle" style="font-size:1.6rem;"></i>
            <div>
                <strong>تنبيه بقاعدة البيانات:</strong> الجداول تفتقر لتحديثات دعم الفروع، يتم استخدام الوضع العام (الآمن) مؤقتاً.
                <?php foreach($dbErrors as $err): ?>
                    <div style="font-size: 0.8rem; margin-top: 4px;">- <?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if($flashMsg): ?><div style="margin: 0 30px 24px 30px; background: #ecfdf5; color: #059669; padding: 16px 20px; border-radius: 14px; font-weight: 800; border: 1px solid #a7f3d0; display:flex; align-items:center; gap:10px;"><i class="ph-fill ph-check-circle" style="font-size:1.4rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="margin: 0 30px 24px 30px; background: #fff1f2; color: #e11d48; padding: 16px 20px; border-radius: 14px; font-weight: 800; border: 1px solid #fecdd3; display:flex; align-items:center; gap:10px;"><i class="ph-fill ph-warning-circle" style="font-size:1.4rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-buildings"></i></div><div class="kpi-info"><h4><?= $t['stat_total'] ?></h4><p><?= number_format($stats->total_centers ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#f5f3ff; color:#7c3aed;"><i class="ph-duotone ph-folder"></i></div><div class="kpi-info"><h4 style="color:#7c3aed;"><?= $t['stat_parent'] ?></h4><p><?= number_format($stats->parent_centers ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-duotone ph-tree-structure"></i></div><div class="kpi-info"><h4 style="color:#2563eb;"><?= $t['stat_sub'] ?></h4><p><?= number_format($stats->sub_centers ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;"><?= $t['stat_active'] ?></h4><p><?= number_format($stats->active_centers ?? 0) ?></p></div></div>
    </div>

    <form action="/ERP/accounting/cost-centers" method="GET" class="search-card">
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
            <option value="active" <?= ($statusFilter==='active')?'selected':'' ?>><?= $t['st_active'] ?></option>
            <option value="inactive" <?= ($statusFilter==='inactive')?'selected':'' ?>><?= $t['st_inactive'] ?></option>
        </select>
        
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
        <?php if(!empty($search) || !empty($statusFilter) || !empty($branchFilter)): ?>
            <a href="/ERP/accounting/cost-centers" class="btn-search" style="background:var(--surface-hover); color:var(--text-muted); text-decoration:none;"><i class="ph-bold ph-x"></i> <?= $t['clear'] ?></a>
        <?php endif; ?>
    </form>

    <div class="table-container">
        <div style="overflow-x:auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 12%;"><?= $t['col_code'] ?></th>
                        <th style="width: 25%;"><?= $t['col_name'] ?></th>
                        <th style="width: 15%;"><?= $t['col_budget'] ?></th>
                        <th style="width: 13%;"><?= $t['col_type'] ?></th>
                        <th style="width: 15%;"><?= $t['col_parent'] ?></th>
                        <th style="width: 8%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 12%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($centers)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 50px; color: var(--text-muted); font-weight: 800; font-size:1.1rem;"><?= $t['empty'] ?></td></tr>
                    <?php else: foreach ($centers as $c): 
                        $cName = $isRtl ? ($c->name_ar ?: $c->name_en) : ($c->name_en ?: $c->name_ar);
                        $pName = $isRtl ? ($c->parent_name ?: ($c->parent_code ?? '---')) : (($c->parent_code ?? '---'));
                        
                        // تم تعريف المتغير هنا لمنع الخطأ
                        $branchBadge = !empty($c->branch_name) ? ($isRtl ? $c->branch_name : ($c->branch_name_en ?: $c->branch_name)) : $t['general'];
                    ?>
                        <tr>
                            <td><div style="font-family: monospace; font-weight: 900; color: var(--brand-primary-dark); font-size: 1.05rem;"><?= htmlspecialchars($c->code) ?></div></td>
                            <td>
                                <div style="font-weight: 800; color: var(--text-main); display:flex; align-items:center; gap:8px;">
                                    <?= !empty($c->is_parent) ? '<i class="ph-fill ph-folder" style="color:var(--brand-primary); font-size:1.3rem;"></i>' : '<i class="ph-fill ph-file-text" style="color:var(--text-muted); font-size:1.3rem;"></i>' ?>
                                    <?= htmlspecialchars($cName) ?>
                                </div>
                                <?php if(!empty($branches)): ?>
                                <div style="margin-top:4px;">
                                    <span style="background:var(--surface-hover); border:1px solid var(--border-color); color:var(--text-muted); padding:2px 6px; border-radius:4px; font-size:0.7rem; font-weight:bold;">
                                        <i class="ph-bold ph-git-branch"></i> <?= htmlspecialchars($branchBadge) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td style="font-family: monospace; font-weight: 800; color: var(--text-main); font-size:1.05rem;">
                                <?= number_format((float)($c->budget_amount ?? 0), 2) ?> <span style="font-size:0.75rem; color:var(--text-muted);"><?= $currency ?></span>
                            </td>
                            <td>
                                <span class="badge-status" style="<?= !empty($c->is_parent) ? 'background:#f5f3ff; color:#7c3aed; border:1px solid #ddd6fe;' : 'background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;' ?>">
                                    <?= !empty($c->is_parent) ? $t['type_parent'] : $t['type_sub'] ?>
                                </span>
                            </td>
                            <td><span style="font-size:0.85rem; color:var(--text-muted); font-weight:800;"><?= htmlspecialchars($pName) ?></span></td>
                            <td style="text-align: center;">
                                <span class="badge-status" style="<?= !empty($c->is_active) ? 'background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;' : 'background:#fef2f2; color:#dc2626; border:1px solid #fecaca;' ?>">
                                    <?= !empty($c->is_active) ? $t['st_active'] : $t['st_inactive'] ?>
                                </span>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="/ERP/accounting/cost-centers/<?= $c->id ?>" class="action-btn" title="View Profile"><i class="ph-bold ph-eye"></i></a>
                                <a href="/ERP/accounting/cost-centers/<?= $c->id ?>/report" class="action-btn" title="Report"><i class="ph-bold ph-chart-line-up"></i></a>
                                <a href="/ERP/accounting/cost-centers/<?= $c->id ?>/edit" class="action-btn" title="Edit"><i class="ph-bold ph-pencil-simple"></i></a>
                                <form action="/ERP/accounting/cost-centers/<?= $c->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_del'] ?>');">
                                    <button type="submit" class="action-btn delete" title="Delete"><i class="ph-bold ph-trash"></i></button>
                                </form>
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