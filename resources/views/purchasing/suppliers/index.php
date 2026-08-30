<?php
// Path: resources/views/purchasing/suppliers/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'سجل الموردين والشركات', 
        'desc' => 'إدارة قاعدة بيانات الموردين، معلومات التواصل، ومتابعة أوامر الشراء.',
        'add_btn' => 'إضافة مورد جديد', 
        'search' => 'البحث بالاسم، الكود، أو الهاتف...',
        'col_sup' => 'المورد / الكود', 
        'col_contact' => 'التواصل', 
        'col_tax' => 'الرقم الضريبي',
        'col_orders' => 'أوامر الشراء', 
        'col_status' => 'الحالة', 
        'col_actions' => 'إجراءات',
        'empty_title' => 'لا يوجد موردين', 
        'empty_desc' => 'لا توجد نتائج تطابق بحثك أو لم يتم إضافة موردين.',
        'stat_total' => 'إجمالي الموردين',
        'stat_active' => 'موردين نشطين',
        'stat_orders' => 'إجمالي أوامر الشراء',
    ],
    'en' => [
        'title' => 'Suppliers Directory', 
        'desc' => 'Manage supplier database, contact info, and track purchase orders.',
        'add_btn' => 'Add Supplier', 
        'search' => 'Search by name, code, or phone...',
        'col_sup' => 'Supplier / Code', 
        'col_contact' => 'Contact', 
        'col_tax' => 'Tax Number',
        'col_orders' => 'Purchase Orders', 
        'col_status' => 'Status', 
        'col_actions' => 'Actions',
        'empty_title' => 'No Suppliers Found', 
        'empty_desc' => 'No results match your search or no suppliers added yet.',
        'stat_total' => 'Total Suppliers',
        'stat_active' => 'Active Suppliers',
        'stat_orders' => 'Total POs',
    ]
][$isRtl ? 'ar' : 'en'];

$totalSuppliers = count($suppliers ?? []);
$activeSuppliers = 0;
$totalOrders = 0;
if (!empty($suppliers)) {
    foreach ($suppliers as $s) {
        if ($s->is_active) $activeSuppliers++;
        $totalOrders += ($s->orders_count ?? 0);
    }
}
?>

<style>
    :root {
        --bg-main: #f8fafc;
        --surface: #ffffff;
        --border: #e2e8f0;
        --text-dark: #0f172a;
        --text-muted: #64748b;
        --primary: #db2777; /* Pink-600 for Purchasing */
        --primary-light: #fce7f3;
        --success: #10b981;
        --danger: #ef4444;
        --radius-lg: 16px;
        --radius-md: 12px;
        --shadow-sm: 0 1px 3px rgba(15,23,42,0.02), 0 4px 10px rgba(15,23,42,0.04);
        --shadow-hover: 0 10px 25px -5px rgba(219, 39, 119, 0.15);
    }

    .premium-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; padding-bottom: 20px; border-bottom: 1px solid var(--border); }
    .header-content { display: flex; align-items: center; gap: 16px; }
    .header-icon { width: 56px; height: 56px; background: linear-gradient(135deg, var(--primary), #be185d); color: white; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: var(--shadow-hover); }
    .page-title { margin: 0 0 4px 0; color: var(--text-dark); font-size: 1.6rem; font-weight: 900; letter-spacing: -0.5px; }
    .page-desc { margin: 0; font-size: 0.95rem; color: var(--text-muted); font-weight: 500; }
    
    .btn-add { background: linear-gradient(135deg, var(--primary), #be185d); color: white !important; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; text-decoration: none; box-shadow: 0 4px 12px rgba(219, 39, 119, 0.25); transition: all 0.2s ease; }
    .btn-add:hover { transform: translateY(-2px); box-shadow: var(--shadow-hover); }

    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
    @media (max-width: 768px) { .stats-row { grid-template-columns: 1fr; } }
    .stat-mini-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px; display: flex; align-items: center; gap: 16px; box-shadow: var(--shadow-sm); transition: 0.2s; }
    .stat-mini-card:hover { transform: translateY(-2px); border-color: #fbcfe8; }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; background: var(--primary-light); color: var(--primary); }
    .stat-info h4 { margin: 0 0 4px 0; font-size: 0.8rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase; }
    .stat-info p { margin: 0; font-size: 1.4rem; font-weight: 900; color: var(--text-dark); font-family: monospace; }

    /* Search Bar */
    .search-bar { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 12px; display: flex; gap: 10px; margin-bottom: 24px; box-shadow: var(--shadow-sm); }
    .search-input { flex: 1; border: 1px solid var(--border); border-radius: 8px; padding: 10px 16px; font-family: inherit; font-size: 0.95rem; background: var(--bg-main); transition: 0.2s; }
    .search-input:focus { border-color: var(--primary); background: #ffffff; outline: none; box-shadow: 0 0 0 3px var(--primary-light); }
    .btn-search { background: var(--text-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-search:hover { background: #1e293b; }
    .btn-clear { background: var(--bg-main); color: var(--text-muted); border: 1px solid var(--border); padding: 10px 20px; border-radius: 8px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-clear:hover { background: #e2e8f0; color: var(--text-dark); }

    .alert { padding: 16px 20px; border-radius: var(--radius-md); margin-bottom: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; border: 1px solid transparent; }
    .alert-success { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
    .alert-danger { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .table-container { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden; }
    .modern-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: start; }
    .modern-table th { padding: 16px 24px; background: #f8fafc; color: var(--text-muted); font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem; border-bottom: 2px solid var(--border); }
    .modern-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; transition: 0.2s; }
    .modern-table tbody tr:hover td { background: #f8fafc; }
    .modern-table tbody tr:last-child td { border-bottom: none; }

    .sup-code-tag { background: var(--primary-light); color: var(--primary); padding: 4px 10px; border-radius: 6px; font-weight: 800; font-family: monospace; font-size: 0.8rem; display: inline-block; margin-top: 4px; border: 1px solid #fbcfe8; }
    
    .status-badge { padding: 4px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 800; letter-spacing: 0.5px; }
    .status-active { background: #ecfdf5; color: var(--success); border: 1px solid #a7f3d0; }
    .status-inactive { background: #fef2f2; color: var(--danger); border: 1px solid #fecaca; }

    .action-btn { width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface); color: var(--text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; font-size: 1.1rem; margin: 0 3px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
    .action-btn:hover { background: var(--primary-light); border-color: #fbcfe8; color: var(--primary); transform: translateY(-2px); }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: var(--danger); }
    
    .empty-state { text-align: center; padding: 60px 20px; }
    .empty-icon { width: 80px; height: 80px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2.5rem; margin-bottom: 20px; }

    /* Pagination */
    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; padding: 16px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--border); background: var(--surface); color: var(--text-muted); text-decoration: none; font-weight: 800; transition: 0.2s; }
    .page-link:hover { background: #f1f5f9; color: var(--text-dark); border-color: #cbd5e1; }
    .page-link.active { background: var(--primary); color: #ffffff; border-color: var(--primary); box-shadow: 0 2px 6px rgba(219, 39, 119, 0.4); }
</style>

<div class="premium-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <!-- 1. Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-icon"><i class="ph-duotone ph-buildings"></i></div>
            <div>
                <h2 class="page-title"><?= $t['title'] ?></h2>
                <p class="page-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/purchasing/suppliers/create" class="btn-add"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?>
        <div class="alert alert-success"><i class="ph-fill ph-check-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashMsg) ?></div>
    <?php endif; ?>
    <?php if($flashErr): ?>
        <div class="alert alert-danger"><i class="ph-fill ph-warning-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashErr) ?></div>
    <?php endif; ?>

    <!-- 2. Mini Stats Row -->
    <div class="stats-row">
        <div class="stat-mini-card">
            <div class="stat-icon"><i class="ph-bold ph-users-three"></i></div>
            <div class="stat-info">
                <h4><?= $t['stat_total'] ?></h4>
                <p><?= number_format($totalSuppliers) ?></p>
            </div>
        </div>
        <div class="stat-mini-card">
            <div class="stat-icon" style="background: #ecfdf5; color: #10b981;"><i class="ph-bold ph-check-circle"></i></div>
            <div class="stat-info">
                <h4><?= $t['stat_active'] ?></h4>
                <p><?= number_format($activeSuppliers) ?></p>
            </div>
        </div>
        <div class="stat-mini-card">
            <div class="stat-icon" style="background: #fdf4ff; color: #c084fc;"><i class="ph-bold ph-shopping-bag"></i></div>
            <div class="stat-info">
                <h4><?= $t['stat_orders'] ?></h4>
                <p><?= number_format($totalOrders) ?></p>
            </div>
        </div>
    </div>

    <!-- 3. Search Form -->
    <form action="/ERP/purchasing/suppliers" method="GET" class="search-bar">
        <input type="text" name="search" class="search-input" placeholder="<?= $t['search'] ?>" value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> بحث</button>
        <?php if(!empty($search)): ?>
            <a href="/ERP/purchasing/suppliers" class="btn-clear"><i class="ph-bold ph-x"></i> إلغاء</a>
        <?php endif; ?>
    </form>

    <!-- 4. Main Table Container -->
    <div class="table-container">
        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 25%;"><?= $t['col_sup'] ?></th>
                        <th style="width: 20%;"><?= $t['col_contact'] ?></th>
                        <th style="width: 15%;"><?= $t['col_tax'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_orders'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($suppliers)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="ph-duotone ph-buildings"></i></div>
                                    <h3 style="margin: 0 0 8px 0; color: #0f172a; font-weight: 800; font-size: 1.3rem;"><?= $t['empty_title'] ?></h3>
                                    <p style="color: #64748b; margin: 0; font-weight: 500;"><?= $t['empty_desc'] ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $sup): 
                            $name = $isRtl ? ($sup->name_ar ?: $sup->name_en) : ($sup->name_en ?: $sup->name_ar);
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800; color: #0f172a; font-size: 1.05rem;"><?= htmlspecialchars($name) ?></div>
                                    <div class="sup-code-tag"><i class="ph-bold ph-hash"></i> <?= htmlspecialchars($sup->code) ?></div>
                                </td>
                                <td>
                                    <?php if($sup->phone): ?>
                                        <div style="font-weight: 700; color: #334155; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                                            <i class="ph-fill ph-phone" style="color:#94a3b8;"></i> <span dir="ltr"><?= htmlspecialchars($sup->phone) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if($sup->email): ?>
                                        <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center; gap: 6px;">
                                            <i class="ph-fill ph-envelope" style="color:#94a3b8;"></i> <?= htmlspecialchars($sup->email) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-family: monospace; font-weight: 800; color: #475569; font-size: 0.95rem;">
                                    <?= htmlspecialchars($sup->tax_number ?? '---') ?>
                                </td>
                                <td style="text-align: center;">
                                    <span style="background: #f1f5f9; color: #334155; padding: 4px 14px; border-radius: 8px; font-weight: 800; font-family: monospace; border: 1px solid #e2e8f0; font-size: 0.9rem;">
                                        <?= $sup->orders_count ?? 0 ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if($sup->is_active): ?>
                                        <span class="status-badge status-active">نشط</span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">موقوف</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <a href="/ERP/purchasing/suppliers/<?= $sup->id ?>" class="action-btn" title="ملف المورد"><i class="ph-bold ph-eye"></i></a>
                                    <a href="/ERP/purchasing/suppliers/<?= $sup->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                    <form action="/ERP/purchasing/suppliers/<?= $sup->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('تأكيد الحذف نهائياً؟');">
                                        <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5. Pagination UI -->
    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>