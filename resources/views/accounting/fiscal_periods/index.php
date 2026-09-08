<?php
// Path: resources/views/accounting/fiscal_periods/index.php

try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

    $flashMsg = $_SESSION['flash_msg'] ?? null;
    $flashErr = $_SESSION['flash_err'] ?? null;
    unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

    $t = [
        'ar' => [
            'title' => 'الفترات المالية', 'desc' => 'إدارة الفترات المحاسبية (فتح وإغلاق) لضبط حركة القيود والتقارير.',
            'add_btn' => 'إضافة فترة مالية', 'stat_total' => 'إجمالي الفترات', 'stat_open' => 'فترات مفتوحة', 'stat_closed' => 'فترات مغلقة',
            'search_ph' => 'ابحث باسم الفترة...', 'all_branches' => '-- جميع الفروع --', 'all_statuses' => '-- جميع الحالات --',
            'st_open' => 'مفتوحة (Open)', 'st_closed' => 'مغلقة (Closed)', 'btn_search' => 'بحث',
            'col_name' => 'اسم الفترة', 'col_start' => 'تاريخ البداية', 'col_end' => 'تاريخ النهاية', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات',
            'empty' => 'لا توجد فترات مالية مسجلة.', 'confirm_del' => 'هل أنت متأكد من حذف هذه الفترة؟', 'general' => 'عام (المركز الرئيسي)'
        ],
        'en' => [
            'title' => 'Fiscal Periods', 'desc' => 'Manage accounting periods (open/close) to control journal entries.',
            'add_btn' => 'Add Fiscal Period', 'stat_total' => 'Total Periods', 'stat_open' => 'Open Periods', 'stat_closed' => 'Closed Periods',
            'search_ph' => 'Search by name...', 'all_branches' => '-- All Branches --', 'all_statuses' => '-- All Statuses --',
            'st_open' => 'Open', 'st_closed' => 'Closed', 'btn_search' => 'Search',
            'col_name' => 'Period Name', 'col_start' => 'Start Date', 'col_end' => 'End Date', 'col_status' => 'Status', 'col_actions' => 'Actions',
            'empty' => 'No fiscal periods found.', 'confirm_del' => 'Are you sure you want to delete this period?', 'general' => 'General (HQ)'
        ]
    ][$isRtl ? 'ar' : 'en'];

    function getPeriodStatusBadge($status, $t) {
        $map = [
            'open'   => ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => $t['st_open']],
            'closed' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => $t['st_closed']]
        ];
        $s = $map[$status] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $status];
        return "<span style='background:{$s['bg']}; color:{$s['color']}; padding:6px 14px; border-radius:8px; font-weight:800; font-size:0.78rem; border:1px solid currentColor; display:inline-block; white-space:nowrap;'>{$s['label']}</span>";
    }
?>

<style>
    :root {
        --c-fp: #6366f1; --c-fp-dark: #4338ca; --c-fp-light: #e0e7ff;
        --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b;
    }
    .fp-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .fp-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .fp-title-box { display: flex; align-items: center; gap: 16px; }
    .fp-icon { width: 50px; height: 50px; background: var(--c-fp-light); color: var(--c-fp); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.12); flex-shrink: 0; }
    .fp-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-fp { background: linear-gradient(135deg, var(--c-fp), var(--c-fp-dark)); color: #ffffff !important; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2); white-space: nowrap; }

    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:900px){ .kpi-row { grid-template-columns: 1fr; } }
    .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-info h4 { margin: 0 0 6px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.6rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }
    .kpi-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }

    .search-bar { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px; display: flex; gap: 12px; align-items: center; margin-bottom: 24px; flex-wrap:wrap; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .form-control { border: 1px solid var(--c-border); border-radius: 10px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; color: var(--c-text-dark); font-weight: 600; outline: none; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 11px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .fp-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .fp-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.78rem; text-align: start; }
    .fp-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; text-align: start; }

    .action-btn { width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; transition: all 0.2s; }
    .action-btn:hover { background: var(--c-fp-light); color: var(--c-fp); border-color: #a5b4fc; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-fp); color: #ffffff; border-color: var(--c-fp); }
</style>

<div class="fp-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="fp-header">
        <div class="fp-title-box">
            <div class="fp-icon"><i class="ph-duotone ph-calendar-check"></i></div>
            <div>
                <h2 class="fp-title"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/accounting/fiscal-periods/create" class="btn-fp"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #e0e7ff; color: #4338ca; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #c7d2fe; display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-check-circle" style="font-size:1.2rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca; display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-warning-circle" style="font-size:1.2rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-info">
                <h4><?= $t['stat_total'] ?></h4>
                <p><?= number_format((float)($stats->total ?? 0)) ?></p>
            </div>
            <div class="kpi-icon" style="background:#f1f5f9; color:#475569;"><i class="ph-duotone ph-files"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4 style="color:#059669;"><?= $t['stat_open'] ?></h4>
                <p style="color:#059669;"><?= number_format((float)($stats->open ?? 0)) ?></p>
            </div>
            <div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-lock-open"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4 style="color:#dc2626;"><?= $t['stat_closed'] ?></h4>
                <p style="color:#dc2626;"><?= number_format((float)($stats->closed ?? 0)) ?></p>
            </div>
            <div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-lock-key"></i></div>
        </div>
    </div>

    <form action="/ERP/accounting/fiscal-periods" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2;" placeholder="<?= $t['search_ph'] ?>" value="<?= htmlspecialchars((string)($search ?? '')) ?>">
        
        <?php if(!empty($branches)): ?>
        <select name="branch_id" class="form-control" style="flex:1;">
            <option value=""><?= $t['all_branches'] ?></option>
            <?php foreach($branches as $b): 
                $bName = $isRtl ? ($b->name_ar ?? '') : ($b->name_en ?: ($b->name_ar ?? ''));
            ?>
                <option value="<?= $b->id ?? 0 ?>" <?= ((string)($branchFilter ?? '') == (string)($b->id ?? 0))?'selected':'' ?>><?= htmlspecialchars($bName) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <select name="status" class="form-control" style="flex:1;">
            <option value=""><?= $t['all_statuses'] ?></option>
            <option value="open" <?= ((string)($statusFilter ?? '')==='open')?'selected':'' ?>><?= $t['st_open'] ?></option>
            <option value="closed" <?= ((string)($statusFilter ?? '')==='closed')?'selected':'' ?>><?= $t['st_closed'] ?></option>
        </select>
        
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> <?= $t['btn_search'] ?></button>
    </form>

    <div class="table-card">
        <table class="fp-table">
            <thead>
                <tr>
                    <th style="width: 25%;"><?= $t['col_name'] ?></th>
                    <th style="width: 20%; text-align: center;"><?= $t['col_start'] ?></th>
                    <th style="width: 20%; text-align: center;"><?= $t['col_end'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 20%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($periods)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($periods as $p): 
                    $pName = $isRtl ? ($p->name_ar ?? '') : (($p->name_en ?? '') !== '' ? $p->name_en : ($p->name_ar ?? ''));
                    $branchName = !empty($p->branch_name) ? ($isRtl ? $p->branch_name : ($p->branch_name_en ?: $p->branch_name)) : $t['general'];
                ?>
                    <tr>
                        <td>
                            <div style="font-weight: 900; color: var(--c-fp-dark); font-size: 1.05rem;">
                                <?= htmlspecialchars($pName) ?>
                            </div>
                            <?php if(!empty($branches)): ?>
                                <div style="font-size:0.75rem; color:var(--c-text-muted); font-weight:bold; margin-top:4px;"><i class="ph-bold ph-git-branch"></i> <?= htmlspecialchars($branchName) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; font-family: monospace; font-weight: 800; color: #0f172a;"><?= htmlspecialchars((string)($p->start_date ?? '')) ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 800; color: #0f172a;"><?= htmlspecialchars((string)($p->end_date ?? '')) ?></td>
                        <td style="text-align: center;"><?= getPeriodStatusBadge((string)($p->status ?? ''), $t) ?></td>
                        <td style="text-align: center; white-space: nowrap;">
                            <div style="display: flex; justify-content: center; gap: 6px;">
                                <a href="/ERP/accounting/fiscal-periods/<?= $p->id ?? 0 ?>" class="action-btn" title="تقرير الفترة"><i class="ph-bold ph-chart-line-up"></i></a>
                                <?php if(($p->status ?? '') === 'open'): ?>
                                    <a href="/ERP/accounting/fiscal-periods/<?= $p->id ?? 0 ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                    <form action="/ERP/accounting/fiscal-periods/<?= $p->id ?? 0 ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_del'] ?>');">
                                        <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode((string)($search ?? '')) ?>&status=<?= urlencode((string)($statusFilter ?? '')) ?>&branch_id=<?= urlencode((string)($branchFilter ?? '')) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php 
} catch (Throwable $e) {
    echo "<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 View Error (index.php)</h3>" . htmlspecialchars($e->getMessage()) . "<br>Line: " . $e->getLine() . "</div>";
}
?>