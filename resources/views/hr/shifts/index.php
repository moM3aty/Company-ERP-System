<?php
// Path: resources/views/hr/shifts/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$statusMap = [
    'active' => ['label' => 'وردية مفعلة', 'color' => '#16a34a', 'bg' => '#dcfce7', 'icon' => 'ph-check-circle'],
    'inactive' => ['label' => 'وردية متوقفة', 'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'ph-minus-circle'],
];
?>

<style>
    :root {
        --c-shift: #16a34a;
        --c-shift-dark: #15803d;
        --c-shift-light: #dcfce7;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .shift-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .shift-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .shift-title-box { display: flex; align-items: center; gap: 16px; }
    .shift-icon { width: 48px; height: 48px; background: var(--c-shift-light); color: var(--c-shift); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(22, 163, 74, 0.15); }
    .shift-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-shift { background: linear-gradient(135deg, var(--c-shift), var(--c-shift-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25); }

    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:768px){ .kpi-row { grid-template-columns: 1fr; } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-shift-light); color: var(--c-shift); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .shift-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .shift-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .shift-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-shift-light); color: var(--c-shift); border-color: #bbf7d0; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-shift); color: #ffffff; border-color: var(--c-shift); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
</style>

<div class="shift-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="shift-header">
        <div class="shift-title-box">
            <div class="shift-icon"><i class="ph-duotone ph-clock-user"></i></div>
            <div>
                <h2 class="shift-title">الورديات ومواعيد العمل (Shifts & Schedules)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);">إعداد فترات العمل اليومية، أوقات الدخول والخروج وفترات السماح.</p>
            </div>
        </div>
        <a href="/ERP/hr/shifts/create" class="btn-shift"><i class="ph-bold ph-plus"></i> إضافة وردية جديدة</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-list-numbers"></i></div><div class="kpi-info"><h4>إجمالي الورديات</h4><p><?= number_format($stats->total_shifts ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#dcfce7; color:#16a34a;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#16a34a;">ورديات نشطة</h4><p><?= number_format($stats->active_shifts ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#f1f5f9; color:#64748b;"><i class="ph-duotone ph-minus-circle"></i></div><div class="kpi-info"><h4 style="color:#64748b;">ورديات متوقفة</h4><p><?= number_format($stats->inactive_shifts ?? 0) ?></p></div></div>
    </div>

    <form action="/ERP/hr/shifts" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:220px;" placeholder="ابحث بكود أو اسم الوردية..." value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value="">-- كل الحالات --</option>
            <option value="active" <?= ($statusFilter==='active')?'selected':'' ?>>وردية مفعلة</option>
            <option value="inactive" <?= ($statusFilter==='inactive')?'selected':'' ?>>متوقفة</option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> فلترة</button>
    </form>

    <div class="table-card">
        <table class="shift-table">
            <thead>
                <tr>
                    <th style="width: 15%;">كود الوردية</th>
                    <th style="width: 25%;">المسمى / الاسم</th>
                    <th style="width: 15%; text-align: center;">وقت الدخول</th>
                    <th style="width: 15%; text-align: center;">وقت الانصراف</th>
                    <th style="width: 10%; text-align: center;">فترة السماح</th>
                    <th style="width: 10%; text-align: center;">الحالة</th>
                    <th style="width: 10%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($shifts)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد ورديات عمل مسجلة بمواصفات البحث.</td></tr>
                <?php else: foreach ($shifts as $s): 
                    $st = $statusMap[$s->status] ?? $statusMap['active'];
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-shift-dark); font-size: 0.95rem;">
                            <?= htmlspecialchars($s->code) ?>
                        </td>
                        <td style="font-weight: 800; color: var(--c-text-dark);">
                            <?= htmlspecialchars($s->name_ar) ?>
                        </td>
                        <td style="text-align: center; font-family: monospace; font-weight: bold; color: #059669;">
                            <?= date('h:i A', strtotime($s->start_time)) ?>
                        </td>
                        <td style="text-align: center; font-family: monospace; font-weight: bold; color: #dc2626;">
                            <?= date('h:i A', strtotime($s->end_time)) ?>
                        </td>
                        <td style="text-align: center; font-family: monospace; font-weight: 800; color: #d97706;">
                            <?= $s->grace_period_mins ?> دقيقة
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/hr/shifts/<?= $s->id ?>" class="action-btn" title="عرض الوردية"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/hr/shifts/<?= $s->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/hr/shifts/<?= $s->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذه الوردية؟');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>