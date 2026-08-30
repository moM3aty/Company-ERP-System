<?php
// Path: resources/views/projects/costs/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$categoryMap = [
    'materials' => ['label' => 'مواد وتوريدات', 'color' => '#ea580c', 'bg' => '#ffedd5', 'icon' => 'ph-package'],
    'labor' => ['label' => 'عمالة وأجور', 'color' => '#2563eb', 'bg' => '#eff6ff', 'icon' => 'ph-users-three'],
    'equipment' => ['label' => 'معدات وآليات', 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-truck'],
    'subcontractor' => ['label' => 'مقاولين فرعيين', 'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'icon' => 'ph-handshake'],
    'overhead' => ['label' => 'مصروفات إدارية', 'color' => '#0284c7', 'bg' => '#e0f2fe', 'icon' => 'ph-receipt'],
    'other' => ['label' => 'مصروفات أخرى', 'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'ph-dots-three-circle'],
];

$statusMap = [
    'paid' => ['label' => 'مسدد بالكامل', 'color' => '#059669', 'bg' => '#ecfdf5'],
    'partially_paid' => ['label' => 'مسدد جزئياً', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'unpaid' => ['label' => 'غير مسدد', 'color' => '#dc2626', 'bg' => '#fef2f2'],
];
?>

<style>
    :root {
        --c-pcost: #ea580c;
        --c-pcost-dark: #c2410c;
        --c-pcost-light: #ffedd5;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .pcost-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .pcost-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .pcost-title-box { display: flex; align-items: center; gap: 16px; }
    .pcost-icon { width: 48px; height: 48px; background: var(--c-pcost-light); color: var(--c-pcost); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(234, 88, 12, 0.15); }
    .pcost-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-pcost { background: linear-gradient(135deg, var(--c-pcost), var(--c-pcost-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-pcost-light); color: var(--c-pcost); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .pcost-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .pcost-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .pcost-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-pcost-light); color: var(--c-pcost); border-color: #ffedd5; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-pcost); color: #ffffff; border-color: var(--c-pcost); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
</style>

<div class="pcost-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="pcost-header">
        <div class="pcost-title-box">
            <div class="pcost-icon"><i class="ph-duotone ph-currency-dollar"></i></div>
            <div>
                <h2 class="pcost-title">تكاليف ومصروفات الموقع (Project Site Expenses)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);">تسجيل وحصر مصاريف المواد، العمالة، المعدات والمقاولين الفرعيين بالمواقع.</p>
            </div>
        </div>
        <a href="/ERP/projects/costs/create" class="btn-pcost"><i class="ph-bold ph-plus"></i> تسجيل مصروف موقع جديد</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-receipt"></i></div><div class="kpi-info"><h4>إجمالي السندات</h4><p><?= number_format($stats->total_vouchers ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ffedd5; color:#ea580c;"><i class="ph-duotone ph-coins"></i></div><div class="kpi-info"><h4 style="color:#ea580c;">إجمالي المصروفات</h4><p><?= number_format((float)($stats->total_amount ?? 0), 2) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-duotone ph-package"></i></div><div class="kpi-info"><h4 style="color:#2563eb;">تكلفة المواد والتوريد</h4><p><?= number_format((float)($stats->materials_cost ?? 0), 2) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-truck"></i></div><div class="kpi-info"><h4 style="color:#d97706;">معدات ومقاولين فرعيين</h4><p><?= number_format((float)($stats->equipment_cost ?? 0), 2) ?></p></div></div>
    </div>

    <form action="/ERP/projects/costs" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="ابحث برقم السند، البيان، المرجع، اسم المورد..." value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="project_id" class="form-control" style="flex:1; min-width:160px;">
            <option value="">-- كل المشاريع --</option>
            <?php foreach($projects as $proj): ?>
                <option value="<?= $proj->id ?>" <?= ($projectId == $proj->id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($proj->name_ar) ?> (<?= htmlspecialchars($proj->code) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <select name="cost_category" class="form-control" style="flex:1; min-width:140px;">
            <option value="">-- تبويب التكلفة --</option>
            <option value="materials" <?= ($categoryFilter==='materials')?'selected':'' ?>>مواد وتوريدات</option>
            <option value="labor" <?= ($categoryFilter==='labor')?'selected':'' ?>>عمالة وأجور</option>
            <option value="equipment" <?= ($categoryFilter==='equipment')?'selected':'' ?>>معدات وآليات</option>
            <option value="subcontractor" <?= ($categoryFilter==='subcontractor')?'selected':'' ?>>مقاولين فرعيين</option>
            <option value="overhead" <?= ($categoryFilter==='overhead')?'selected':'' ?>>مصروفات إدارية</option>
            <option value="other" <?= ($categoryFilter==='other')?'selected':'' ?>>أخرى</option>
        </select>

        <select name="payment_status" class="form-control" style="flex:1; min-width:130px;">
            <option value="">-- حالة السداد --</option>
            <option value="paid" <?= ($statusFilter==='paid')?'selected':'' ?>>مسدد بالكامل</option>
            <option value="partially_paid" <?= ($statusFilter==='partially_paid')?'selected':'' ?>>مسدد جزئياً</option>
            <option value="unpaid" <?= ($statusFilter==='unpaid')?'selected':'' ?>>غير مسدد</option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> فلترة</button>
    </form>

    <div class="table-card">
        <table class="pcost-table">
            <thead>
                <tr>
                    <th style="width: 13%;">رقم السند</th>
                    <th style="width: 22%;">المشروع والتبويب</th>
                    <th style="width: 14%;">التاريخ / المرجع</th>
                    <th style="width: 18%;">المورد / الحساب</th>
                    <th style="width: 13%;">المبلغ المصروف</th>
                    <th style="width: 10%; text-align: center;">السداد</th>
                    <th style="width: 10%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($costs)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد مصروفات موقع مسجلة بمواصفات البحث.</td></tr>
                <?php else: foreach ($costs as $c): 
                    $cat = $categoryMap[$c->cost_category] ?? $categoryMap['materials'];
                    $st = $statusMap[$c->payment_status] ?? $statusMap['paid'];
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-pcost-dark); font-size: 0.95rem;">
                            <a href="/ERP/projects/costs/<?= $c->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($c->voucher_number) ?></a>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($c->project_name ?? 'مشروع غير محدد') ?></div>
                            <span class="badge-status" style="background:<?= $cat['bg'] ?>; color:<?= $cat['color'] ?>; margin-top:4px;">
                                <i class="ph-bold <?= $cat['icon'] ?>"></i> <?= $cat['label'] ?>
                            </span>
                        </td>
                        <td style="font-family: monospace; font-size: 0.85rem; font-weight: 700; color: #334155;">
                            <div><?= htmlspecialchars($c->cost_date) ?></div>
                            <div style="font-size:0.75rem; color:#64748b;">Ref: <?= htmlspecialchars($c->reference_no ?: '---') ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($c->supplier_name ?: ($c->account_name ?? 'صندوق مباشر')) ?></div>
                            <div style="font-size: 0.75rem; color: var(--c-text-muted);"><?= htmlspecialchars($c->description ?? '') ?></div>
                        </td>
                        <td style="font-family: monospace; font-weight: 900; color: #dc2626; font-size: 0.98rem;">
                            <?= number_format((float)$c->amount, 2) ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/projects/costs/<?= $c->id ?>" class="action-btn" title="عرض وطباعة السند"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/projects/costs/<?= $c->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/projects/costs/<?= $c->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا المصروف؟ سيعاد احتساب تكلفة المشروع آلياً.');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&project_id=<?= urlencode($projectId) ?>&cost_category=<?= urlencode($categoryFilter) ?>&payment_status=<?= urlencode($statusFilter) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>