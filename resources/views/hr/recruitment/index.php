<?php
// Path: resources/views/hr/recruitment/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$statusMap = [
    'applied' => ['label' => 'طلب جديد', 'color' => '#0284c7', 'bg' => '#e0f2fe', 'icon' => 'ph-file-text'],
    'interviewed' => ['label' => 'تمت المقابلة', 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-chats-circle'],
    'offered' => ['label' => 'عرض وظيفي', 'color' => '#8b5cf6', 'bg' => '#f5f3ff', 'icon' => 'ph-handshake'],
    'hired' => ['label' => 'تم التوظيف', 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
    'rejected' => ['label' => 'مرفوض', 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-x-circle'],
];
?>

<style>
    :root {
        --c-rec: #db2777;
        --c-rec-dark: #be185d;
        --c-rec-light: #fce7f3;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .rec-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .rec-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .rec-title-box { display: flex; align-items: center; gap: 16px; }
    .rec-icon { width: 48px; height: 48px; background: var(--c-rec-light); color: var(--c-rec); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(219, 39, 119, 0.15); }
    .rec-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-rec { background: linear-gradient(135deg, var(--c-rec), var(--c-rec-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(219, 39, 119, 0.25); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-rec-light); color: var(--c-rec); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .rec-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .rec-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .rec-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-rec-light); color: var(--c-rec); border-color: #fbcfe8; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-rec); color: #ffffff; border-color: var(--c-rec); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
</style>

<div class="rec-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="rec-header">
        <div class="rec-title-box">
            <div class="rec-icon"><i class="ph-duotone ph-user-plus"></i></div>
            <div>
                <h2 class="rec-title">إدارة التوظيف والمتقدمين (Recruitment & Applicants)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);">سجل طلبات التوظيف، مقابلات العمل والترشيحات الوظيفية.</p>
            </div>
        </div>
        <a href="/ERP/hr/recruitment/create" class="btn-rec"><i class="ph-bold ph-plus"></i> إضافة طلب متقدم جديد</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-users-three"></i></div><div class="kpi-info"><h4>إجمالي المتقدمين</h4><p><?= number_format($stats->total_applicants ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-chats-circle"></i></div><div class="kpi-info"><h4 style="color:#d97706;">تمت المقابلة</h4><p><?= number_format($stats->interviewed_count ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#f5f3ff; color:#8b5cf6;"><i class="ph-duotone ph-handshake"></i></div><div class="kpi-info"><h4 style="color:#8b5cf6;">عروض وظيفية</h4><p><?= number_format($stats->offered_count ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;">تم التوظيف</h4><p><?= number_format($stats->hired_count ?? 0) ?></p></div></div>
    </div>

    <form action="/ERP/hr/recruitment" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:200px;" placeholder="ابحث برقم الطلب، اسم المرشح، الجوال، البريد..." value="<?= htmlspecialchars($search ?? '') ?>">
        
        <select name="status" class="form-control" style="flex:1; min-width:140px;">
            <option value="">-- كل الحالات --</option>
            <option value="applied" <?= ($statusFilter==='applied')?'selected':'' ?>>طلب جديد</option>
            <option value="interviewed" <?= ($statusFilter==='interviewed')?'selected':'' ?>>تمت المقابلة</option>
            <option value="offered" <?= ($statusFilter==='offered')?'selected':'' ?>>عرض وظيفي</option>
            <option value="hired" <?= ($statusFilter==='hired')?'selected':'' ?>>تم التوظيف</option>
            <option value="rejected" <?= ($statusFilter==='rejected')?'selected':'' ?>>مرفوض</option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> فلترة</button>
    </form>

    <div class="table-card">
        <table class="rec-table">
            <thead>
                <tr>
                    <th style="width: 12%;">كود الطلب</th>
                    <th style="width: 25%;">اسم المرشح للتعديل</th>
                    <th style="width: 20%;">الوظيفة / الإدارة</th>
                    <th style="width: 12%;">الخبرة / الراتب المتوقع</th>
                    <th style="width: 13%;">تاريخ المقابلة</th>
                    <th style="width: 8%; text-align: center;">الحالة</th>
                    <th style="width: 10%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applicants)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا يوجد طلبات توظيف مسجلة.</td></tr>
                <?php else: foreach ($applicants as $app): 
                    $st = $statusMap[$app->status] ?? $statusMap['applied'];
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-rec-dark); font-size: 0.95rem;">
                            <a href="/ERP/hr/recruitment/<?= $app->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($app->applicant_code) ?></a>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($app->candidate_name) ?></div>
                            <div style="font-size: 0.75rem; color: #64748b; font-family: monospace;"><?= htmlspecialchars($app->phone ?: $app->email) ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: #334155;"><?= htmlspecialchars($app->desig_name ?: 'عام') ?></div>
                            <div style="font-size: 0.75rem; color: #64748b;"><?= htmlspecialchars($app->dept_name ?: 'عام') ?></div>
                        </td>
                        <td style="font-family: monospace; font-size:0.85rem; font-weight:700;">
                            <div><?= (int)$app->experience_years ?> سنوات</div>
                            <div style="color:#059669; font-weight:bold;"><?= number_format((float)$app->expected_salary, 2) ?></div>
                        </td>
                        <td style="font-family: monospace; font-size:0.85rem; font-weight:700; color:#0284c7;">
                            <?= htmlspecialchars($app->interview_date ?: 'غير محدد') ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/hr/recruitment/<?= $app->id ?>" class="action-btn" title="عرض التفاصيل"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/hr/recruitment/<?= $app->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/hr/recruitment/<?= $app->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا الطلب؟');">
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