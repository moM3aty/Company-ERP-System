<?php
// Path: resources/views/hr/contracts/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$statusMap = [
    'active' => ['label' => 'عقد ساري', 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
    'expired' => ['label' => 'عقد منتهي', 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-clock-countdown'],
    'terminated' => ['label' => 'عقد مفسوخ', 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-x-circle'],
];
?>

<style>
    :root {
        --c-hcont: #d97706;
        --c-hcont-dark: #b45309;
        --c-hcont-light: #fef3c7;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .hcont-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .hcont-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .hcont-title-box { display: flex; align-items: center; gap: 16px; }
    .hcont-icon { width: 48px; height: 48px; background: var(--c-hcont-light); color: var(--c-hcont); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(217, 119, 6, 0.15); }
    .hcont-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-hcont { background: linear-gradient(135deg, var(--c-hcont), var(--c-hcont-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-hcont-light); color: var(--c-hcont); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .hcont-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .hcont-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .hcont-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-hcont-light); color: var(--c-hcont); border-color: #fde68a; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-hcont); color: #ffffff; border-color: var(--c-hcont); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
</style>

<div class="hcont-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="hcont-header">
        <div class="hcont-title-box">
            <div class="hcont-icon"><i class="ph-duotone ph-file-signature"></i></div>
            <div>
                <h2 class="hcont-title">عقود الموظفين (Employment Contracts)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);">إدارة وتتبع عقود العمل وتفاصيل الرواتب والبدلات.</p>
            </div>
        </div>
        <a href="/ERP/hr/contracts/create" class="btn-hcont"><i class="ph-bold ph-plus"></i> إبرام عقد جديد</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-files"></i></div><div class="kpi-info"><h4>إجمالي العقود</h4><p><?= number_format($stats->total_contracts ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;">عقود سارية</h4><p><?= number_format($stats->active_contracts ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-clock-countdown"></i></div><div class="kpi-info"><h4 style="color:#d97706;">عقود منتهية</h4><p><?= number_format($stats->expired_contracts ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-x-circle"></i></div><div class="kpi-info"><h4 style="color:#dc2626;">عقود مفسوخة</h4><p><?= number_format($stats->terminated_contracts ?? 0) ?></p></div></div>
    </div>

    <form action="/ERP/hr/contracts" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:220px;" placeholder="ابحث برقم العقد، اسم الموظف، كود الموظف..." value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value="">-- كل الحالات --</option>
            <option value="active" <?= ($statusFilter==='active')?'selected':'' ?>>ساري</option>
            <option value="expired" <?= ($statusFilter==='expired')?'selected':'' ?>>منتهي</option>
            <option value="terminated" <?= ($statusFilter==='terminated')?'selected':'' ?>>مفسوخ</option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> فلترة</button>
    </form>

    <div class="table-card">
        <table class="hcont-table">
            <thead>
                <tr>
                    <th style="width: 12%;">رقم العقد</th>
                    <th style="width: 25%;">اسم الموظف</th>
                    <th style="width: 20%;">الراتب والبدلات</th>
                    <th style="width: 18%;">تاريخ العقد</th>
                    <th style="width: 10%; text-align: center;">الحالة</th>
                    <th style="width: 15%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contracts)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد عقود مسجلة بمواصفات البحث.</td></tr>
                <?php else: foreach ($contracts as $c): 
                    $st = $statusMap[$c->status] ?? $statusMap['active'];
                    $totalSal = (float)$c->basic_salary + (float)$c->housing_allowance + (float)$c->transport_allowance;
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-hcont-dark); font-size: 0.95rem;">
                            <a href="/ERP/hr/contracts/<?= $c->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($c->contract_code) ?></a>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($c->employee_name ?: 'مجهول') ?></div>
                            <div style="font-size: 0.78rem; color: var(--c-text-muted); font-family:monospace;"><?= htmlspecialchars($c->emp_code) ?></div>
                        </td>
                        <td>
                            <div style="font-family: monospace; font-weight: 900; color: #059669;">Total: <?= number_format($totalSal, 2) ?></div>
                            <div style="font-size: 0.75rem; color: #64748b;">(أساسي: <?= number_format((float)$c->basic_salary) ?>)</div>
                        </td>
                        <td style="font-family: monospace; font-size: 0.85rem; font-weight: 700;">
                            <div><span style="color:#64748b;">بدء:</span> <?= htmlspecialchars($c->start_date) ?></div>
                            <div><span style="color:#64748b;">انتهاء:</span> <?= htmlspecialchars($c->end_date ?: 'غير محدد') ?></div>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/hr/contracts/<?= $c->id ?>" class="action-btn" title="عرض العقد"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/hr/contracts/<?= $c->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/hr/contracts/<?= $c->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا العقد؟');">
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