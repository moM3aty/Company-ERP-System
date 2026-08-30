<?php
// Path: resources/views/treasury/cheques/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$statusMap = [
    'pending' => ['label' => 'برسم التحصيل / معلق', 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-clock'],
    'collected' => ['label' => 'محصل / مقبول', 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
    'bounced' => ['label' => 'مرتد / مرفوض', 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-warning-circle'],
    'cancelled' => ['label' => 'ملغى', 'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'ph-x-circle'],
];
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
    .chq-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .chq-title-box { display: flex; align-items: center; gap: 16px; }
    .chq-icon { width: 48px; height: 48px; background: var(--c-chq-light); color: var(--c-chq); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(192, 38, 211, 0.12); }
    .chq-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-chq { background: linear-gradient(135deg, var(--c-chq), var(--c-chq-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(192, 38, 211, 0.2); }

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

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
</style>

<div class="chq-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="chq-header">
        <div class="chq-title-box">
            <div class="chq-icon"><i class="ph-duotone ph-checks"></i></div>
            <div>
                <h2 class="chq-title">إدارة الشيكات والأوراق المالية (Cheques)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);">حصر ومتابعة حركة الشيكات الواردة والصادرة وتواريخ الاستحقاق والتحصيل.</p>
            </div>
        </div>
        <a href="/ERP/treasury/cheques/create" class="btn-chq"><i class="ph-bold ph-plus"></i> إضافة / تسجيل شيك جديد</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-files"></i></div><div class="kpi-info"><h4>إجمالي الشيكات</h4><p><?= number_format($stats->total_cheques ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-clock"></i></div><div class="kpi-info"><h4 style="color:#d97706;">شيكات معلقة (برسم التحصيل)</h4><p><?= number_format((float)($stats->pending_amount ?? 0), 2) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;">شيكات محصلة ومقبولة</h4><p><?= number_format((float)($stats->collected_amount ?? 0), 2) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-warning-circle"></i></div><div class="kpi-info"><h4 style="color:#dc2626;">شيكات مرتجعة ومرفوضة</h4><p><?= number_format((float)($stats->bounced_amount ?? 0), 2) ?></p></div></div>
    </div>

    <form action="/ERP/treasury/cheques" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="ابحث برقم الشيك، اسم البنك، الساحب/المستفيد..." value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="type" class="form-control" style="flex:1; min-width:140px;">
            <option value="">-- كل الأنواع --</option>
            <option value="received" <?= ($typeFilter==='received')?'selected':'' ?>>وارد (استلام)</option>
            <option value="issued" <?= ($typeFilter==='issued')?'selected':'' ?>>صادر (دفع)</option>
        </select>
        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value="">-- كل الحالات --</option>
            <option value="pending" <?= ($statusFilter==='pending')?'selected':'' ?>>برسم التحصيل / معلق</option>
            <option value="collected" <?= ($statusFilter==='collected')?'selected':'' ?>>محصل</option>
            <option value="bounced" <?= ($statusFilter==='bounced')?'selected':'' ?>>مرتد / مرفوض</option>
            <option value="cancelled" <?= ($statusFilter==='cancelled')?'selected':'' ?>>ملغى</option>
        </select>
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($fromDate ?? '') ?>" title="تاريخ الاستحقاق من">
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($toDate ?? '') ?>" title="تاريخ الاستحقاق إلى">
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> فلترة</button>
    </form>

    <div class="table-card">
        <table class="chq-table">
            <thead>
                <tr>
                    <th style="width: 14%;">رقم الشيك</th>
                    <th style="width: 10%;">النوع</th>
                    <th style="width: 16%;">البنك المسحوب عليه</th>
                    <th style="width: 20%;">المستفيد / الساحب</th>
                    <th style="width: 12%;">تاريخ الاستحقاق</th>
                    <th style="width: 14%;">المبلغ</th>
                    <th style="width: 12%; text-align: center;">الحالة</th>
                    <th style="width: 10%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cheques)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد شيكات أو أوراق مالية مسجلة.</td></tr>
                <?php else: foreach ($cheques as $c): 
                    $st = $statusMap[$c->status] ?? $statusMap['pending'];
                    $isReceived = $c->type === 'received';
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-chq-dark); font-size: 0.95rem;">
                            <a href="/ERP/treasury/cheques/<?= $c->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($c->cheque_number) ?></a>
                        </td>
                        <td>
                            <span class="badge-status" style="<?= $isReceived ? 'background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;' : 'background:#fef2f2; color:#dc2626; border:1px solid #fecaca;' ?>">
                                <?= $isReceived ? 'شيك وارد' : 'شيك صادر' ?>
                            </span>
                        </td>
                        <td style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($c->bank_name) ?></td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($c->payee_payer_name) ?></div>
                            <div style="font-size: 0.75rem; color: var(--c-text-muted);"><?= htmlspecialchars($c->account_name ?? '') ?></div>
                        </td>
                        <td style="font-family: monospace; font-weight: 800; color: <?= strtotime($c->due_date) < time() && $c->status === 'pending' ? '#dc2626' : '#0f172a' ?>;">
                            <?= htmlspecialchars($c->due_date) ?>
                        </td>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-chq-dark); font-size: 1rem;"><?= number_format((float)$c->amount, 2) ?></td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/treasury/cheques/<?= $c->id ?>" class="action-btn" title="عرض وسند الشيك"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/treasury/cheques/<?= $c->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/treasury/cheques/<?= $c->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف سجل الشيك؟');">
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