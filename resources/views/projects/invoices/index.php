<?php
// Path: resources/views/projects/invoices/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$statusMap = [
    'draft' => ['label' => 'مسودة', 'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'ph-pencil-line'],
    'submitted' => ['label' => 'مقدم للاعتماد', 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-paper-plane-tilt'],
    'approved' => ['label' => 'معتمد للصرف', 'color' => '#0284c7', 'bg' => '#e0f2fe', 'icon' => 'ph-check-circle'],
    'partially_paid' => ['label' => 'مدفوع جزئياً', 'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'icon' => 'ph-chart-pie-slice'],
    'paid' => ['label' => 'مسدد بالكامل', 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-money'],
    'rejected' => ['label' => 'مرفوض', 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-x-circle'],
];

$typeMap = [
    'advance_payment' => ['label' => 'دفعة مقدمة', 'bg' => '#eff6ff', 'color' => '#2563eb'],
    'progress_claim' => ['label' => 'مستخلص جاري', 'bg' => '#ecfdf5', 'color' => '#059669'],
    'final_claim' => ['label' => 'مستخلص ختامي', 'bg' => '#fdf4ff', 'color' => '#c026d3'],
    'retention_release' => ['label' => 'الإفراج عن المحتجزات', 'bg' => '#fef3c7', 'color' => '#d97706'],
];
?>

<style>
    :root {
        --c-pi: #059669;
        --c-pi-dark: #047857;
        --c-pi-light: #ecfdf5;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .pi-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .pi-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .pi-title-box { display: flex; align-items: center; gap: 16px; }
    .pi-icon { width: 48px; height: 48px; background: var(--c-pi-light); color: var(--c-pi); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.15); }
    .pi-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-pi { background: linear-gradient(135deg, var(--c-pi), var(--c-pi-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-pi-light); color: var(--c-pi); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .pi-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .pi-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .pi-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-pi-light); color: var(--c-pi); border-color: #a7f3d0; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-pi); color: #ffffff; border-color: var(--c-pi); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
</style>

<div class="pi-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="pi-header">
        <div class="pi-title-box">
            <div class="pi-icon"><i class="ph-duotone ph-file-text"></i></div>
            <div>
                <h2 class="pi-title">المستخلصات والفواتير للمشاريع (Progress Claims & Invoices)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);">إصدار ومتابعة مستخلصات الإنجاز الجارية والختامية والدفعات المقدمة.</p>
            </div>
        </div>
        <a href="/ERP/projects/invoices/create" class="btn-pi"><i class="ph-bold ph-plus"></i> إنشاء مستخلص / فاتورة جديدة</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-files"></i></div><div class="kpi-info"><h4>إجمالي المستخلصات</h4><p><?= number_format($stats->total_invoices ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-currency-circle-dollar"></i></div><div class="kpi-info"><h4 style="color:#059669;">صافي القيمة الكلية</h4><p><?= number_format((float)($stats->total_net_amount ?? 0), 2) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#eff6ff; color:#2563eb;"><i class="ph-duotone ph-check-square"></i></div><div class="kpi-info"><h4 style="color:#2563eb;">المحصل والمسدد</h4><p><?= number_format((float)($stats->total_paid ?? 0), 2) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-hourglass-high"></i></div><div class="kpi-info"><h4 style="color:#dc2626;">المتبقي غير المحصل</h4><p><?= number_format((float)($stats->total_remaining ?? 0), 2) ?></p></div></div>
    </div>

    <form action="/ERP/projects/invoices" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="ابحث برقم المستخلص، المشروع، العميل، الوصف..." value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="project_id" class="form-control" style="flex:1; min-width:160px;">
            <option value="">-- كل المشاريع --</option>
            <?php foreach($projects as $proj): ?>
                <option value="<?= $proj->id ?>" <?= ($projectId == $proj->id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($proj->name_ar) ?> (<?= htmlspecialchars($proj->code) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <select name="invoice_type" class="form-control" style="flex:1; min-width:140px;">
            <option value="">-- نوع المستخلص --</option>
            <option value="advance_payment" <?= ($typeFilter==='advance_payment')?'selected':'' ?>>دفعة مقدمة</option>
            <option value="progress_claim" <?= ($typeFilter==='progress_claim')?'selected':'' ?>>مستخلص جاري</option>
            <option value="final_claim" <?= ($typeFilter==='final_claim')?'selected':'' ?>>مستخلص ختامي</option>
            <option value="retention_release" <?= ($typeFilter==='retention_release')?'selected':'' ?>>إفراج عن محتجزات</option>
        </select>

        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value="">-- كل الحالات --</option>
            <option value="draft" <?= ($statusFilter==='draft')?'selected':'' ?>>مسودة</option>
            <option value="submitted" <?= ($statusFilter==='submitted')?'selected':'' ?>>مقدم للاعتماد</option>
            <option value="approved" <?= ($statusFilter==='approved')?'selected':'' ?>>معتمد للصرف</option>
            <option value="partially_paid" <?= ($statusFilter==='partially_paid')?'selected':'' ?>>مدفوع جزئياً</option>
            <option value="paid" <?= ($statusFilter==='paid')?'selected':'' ?>>مسدد بالكامل</option>
            <option value="rejected" <?= ($statusFilter==='rejected')?'selected':'' ?>>مرفوض</option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> فلترة</button>
    </form>

    <div class="table-card">
        <table class="pi-table">
            <thead>
                <tr>
                    <th style="width: 13%;">رقم المستخلص</th>
                    <th style="width: 22%;">المشروع والعميل</th>
                    <th style="width: 12%;">النوع</th>
                    <th style="width: 12%;">تاريخ الاصدار</th>
                    <th style="width: 14%;">الصافي المستحق</th>
                    <th style="width: 14%;">المسدد / المتبقي</th>
                    <th style="width: 11%; text-align: center;">الحالة</th>
                    <th style="width: 12%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد مستخلصات أو فواتير مسجلة بمواصفات البحث.</td></tr>
                <?php else: foreach ($invoices as $inv): 
                    $st = $statusMap[$inv->status] ?? $statusMap['draft'];
                    $tp = $typeMap[$inv->invoice_type] ?? $typeMap['progress_claim'];
                    $net = (float)$inv->net_amount;
                    $paid = (float)$inv->paid_amount;
                    $rem = max(0, $net - $paid);
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-pi-dark); font-size: 0.95rem;">
                            <a href="/ERP/projects/invoices/<?= $inv->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($inv->invoice_number) ?></a>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($inv->project_name ?? 'مشروع غير محدد') ?></div>
                            <div style="font-size: 0.78rem; color: var(--c-text-muted);"><i class="ph-bold ph-user"></i> <?= htmlspecialchars($inv->customer_name ?? 'عميل غير محدد') ?></div>
                        </td>
                        <td>
                            <span class="badge-status" style="background:<?= $tp['bg'] ?>; color:<?= $tp['color'] ?>;">
                                <?= $tp['label'] ?>
                            </span>
                        </td>
                        <td style="font-family: monospace; font-size: 0.85rem; font-weight: 700; color: #334155;">
                            <?= htmlspecialchars($inv->invoice_date) ?>
                        </td>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-pi-dark); font-size: 0.95rem;">
                            <?= number_format($net, 2) ?>
                        </td>
                        <td>
                            <div style="font-size:0.8rem; font-weight:800; color:#059669;">مسدد: <?= number_format($paid, 2) ?></div>
                            <div style="font-size:0.75rem; font-weight:800; color:<?= $rem > 0 ? '#dc2626' : '#64748b' ?>;">متبقي: <?= number_format($rem, 2) ?></div>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/projects/invoices/<?= $inv->id ?>" class="action-btn" title="عرض وطباعة المستخلص"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/projects/invoices/<?= $inv->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/projects/invoices/<?= $inv->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخلص؟');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&project_id=<?= urlencode($projectId) ?>&invoice_type=<?= urlencode($typeFilter) ?>&status=<?= urlencode($statusFilter) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>