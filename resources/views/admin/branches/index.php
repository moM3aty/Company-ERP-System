<?php
// Path: resources/views/admin/branches/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);
?>

<style>
    /* 
      Royal Violet Theme 
      اللون الأساسي: Violet/Purple 
    */
    :root {
        --br-primary: #6d28d9;
        --br-dark: #5b21b6;
        --br-light: #f5f3ff;
        --br-border: #ede9fe;
        --br-text: #1e293b;
        --br-muted: #64748b;
    }

    .br-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .br-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; flex-wrap: wrap; gap: 16px; }
    .br-title-box { display: flex; align-items: center; gap: 16px; }
    .br-icon { width: 50px; height: 50px; background: var(--br-light); color: var(--br-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(109, 40, 217, 0.15); border: 1px solid var(--br-border); }
    .br-title { margin: 0; color: var(--br-text); font-size: 1.6rem; font-weight: 900; }
    
    .btn-create { background: linear-gradient(135deg, var(--br-primary), var(--br-dark)); color: #ffffff !important; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(109, 40, 217, 0.25); transition: 0.2s; }
    .btn-create:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(109, 40, 217, 0.35); }

    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width: 768px){ .stats-grid { grid-template-columns: 1fr; } }
    .stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border-left: 4px solid var(--br-primary); }
    .stat-val { font-size: 1.6rem; font-weight: 900; color: var(--br-text); font-family: monospace; }
    .stat-lbl { font-size: 0.85rem; font-weight: 700; color: var(--br-muted); }

    .search-bar { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; align-items: center; }
    .form-control { border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; outline: none; }
    .form-control:focus { border-color: var(--br-primary); box-shadow: 0 0 0 3px var(--br-light); }
    .btn-search { background: var(--br-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 8px;}

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .br-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .br-table th { padding: 16px 20px; background: #f8fafc; color: var(--br-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; white-space: nowrap; }
    .br-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: var(--br-text); vertical-align: middle; }
    .br-table tr:hover td { background: var(--br-light); }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--br-primary); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; transition: 0.2s;}
    .action-btn:hover { background: var(--br-light); border-color: var(--br-border); }
    .action-btn.delete { color: #dc2626; }
    .action-btn.delete:hover { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }

    .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 24px; }
    .page-link { padding: 8px 14px; border: 1px solid #cbd5e1; background: #fff; color: var(--br-text); border-radius: 8px; text-decoration: none; font-weight: 700; transition: 0.2s; }
    .page-link:hover { background: var(--br-light); border-color: var(--br-primary); color: var(--br-primary); }
    .page-link.active { background: var(--br-primary); color: #fff; border-color: var(--br-primary); pointer-events: none; }
</style>

<div class="br-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="br-header">
        <div class="br-title-box">
            <div class="br-icon"><i class="ph-duotone ph-tree-structure"></i></div>
            <div>
                <h2 class="br-title">الفروع والمراكز (Branches Directory)</h2>
                <p style="margin:4px 0 0 0; color:var(--br-muted);">إدارة الفروع وربطها بالشركات وتحديد حالتها.</p>
            </div>
        </div>
        <a href="/ERP/admin/branches/create" class="btn-create"><i class="ph-bold ph-plus"></i> إضافة فرع جديد</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <!-- KPIs -->
    <div class="stats-grid">
        <div class="stat-card">
            <div style="background:var(--br-light); color:var(--br-primary); width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="ph-fill ph-tree-structure"></i></div>
            <div><div class="stat-val"><?= $stats->total ?? 0 ?></div><div class="stat-lbl">إجمالي الفروع</div></div>
        </div>
        <div class="stat-card" style="border-left-color: #10b981;">
            <div style="background:#d1fae5; color:#10b981; width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="ph-fill ph-check-circle"></i></div>
            <div><div class="stat-val"><?= $stats->active ?? 0 ?></div><div class="stat-lbl">فروع نشطة</div></div>
        </div>
        <div class="stat-card" style="border-left-color: #ef4444;">
            <div style="background:#fee2e2; color:#ef4444; width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="ph-fill ph-x-circle"></i></div>
            <div><div class="stat-val"><?= $stats->inactive ?? 0 ?></div><div class="stat-lbl">فروع معطلة</div></div>
        </div>
    </div>

    <!-- Search & Filter -->
    <form action="/ERP/admin/branches" method="GET" class="search-bar">
        <div style="flex: 2; min-width: 250px;">
            <input type="text" name="search" class="form-control" style="width: 100%;" placeholder="بحث بكود الفرع، الاسم، المدينة أو الشركة التابع لها..." value="<?= htmlspecialchars($search ?? '') ?>">
        </div>
        <div style="flex: 1; min-width: 150px;">
            <select name="status" class="form-control" style="width: 100%;">
                <option value="">-- كل الحالات --</option>
                <option value="1" <?= ($statusFilter === '1') ? 'selected' : '' ?>>فروع نشطة</option>
                <option value="0" <?= ($statusFilter === '0') ? 'selected' : '' ?>>فروع معطلة</option>
            </select>
        </div>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> تصفية</button>
        <?php if(!empty($search) || $statusFilter !== ''): ?>
            <a href="/ERP/admin/branches" style="color:var(--br-muted); font-weight:800; font-size:0.85rem; text-decoration:none; margin-right:10px;">إعادة ضبط</a>
        <?php endif; ?>
    </form>

    <!-- Data Table -->
    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="br-table">
                <thead>
                    <tr>
                        <th style="width: 12%;">كود الفرع</th>
                        <th style="width: 25%;">اسم الفرع</th>
                        <th style="width: 25%;">الشركة (الكيان القانوني)</th>
                        <th style="width: 13%;">المدينة</th>
                        <th style="width: 10%; text-align: center;">الحالة</th>
                        <th style="width: 15%; text-align: center;">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($branches)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--br-muted); font-weight: 700;">لا توجد فروع مطابقة لعملية البحث.</td></tr>
                    <?php else: foreach ($branches as $b): ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: 900; color: var(--br-primary); font-size:0.95rem;"><?= htmlspecialchars($b->code) ?></td>
                            <td style="font-weight: 800; color: var(--br-text); font-size:0.95rem;"><?= htmlspecialchars($b->name_ar) ?></td>
                            <td style="font-weight: 700; color: var(--br-muted);"><i class="ph-fill ph-buildings" style="color:#cbd5e1;"></i> <?= htmlspecialchars($b->company_name ?: 'عام') ?></td>
                            <td style="font-weight: 600; color: var(--br-text);"><?= htmlspecialchars($b->city ?: '---') ?></td>
                            <td style="text-align: center;">
                                <?php if($b->is_active): ?>
                                    <span class="badge-status" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> نشط</span>
                                <?php else: ?>
                                    <span class="badge-status" style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca;"><i class="ph-fill ph-x-circle"></i> معطل</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="/ERP/admin/branches/<?= $b->id ?>" class="action-btn" title="عرض التفاصيل"><i class="ph-bold ph-eye"></i></a>
                                <a href="/ERP/admin/branches/<?= $b->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                <form action="/ERP/admin/branches/<?= $b->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('تأكيد حذف هذا الفرع بشكل نهائي؟');">
                                    <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination (15 items per page) -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" 
                   class="page-link <?= ($i === $currentPage) ? 'active' : '' ?>">
                   <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

</div>