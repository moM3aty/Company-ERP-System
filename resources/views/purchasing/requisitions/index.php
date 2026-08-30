<?php
// Path: resources/views/purchasing/requisitions/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'طلبات الشراء (PR)', 'desc' => 'إدارة طلبات الشراء الداخلية للأقسام، الاعتمادات، والميزانيات التقديرية.',
        'add_btn' => 'إنشاء طلب شراء', 'col_num' => 'رقم الطلب', 'col_dept' => 'الإدارة / الطالب',
        'col_dates' => 'تاريخ الطلب / الاستحقاق', 'col_val' => 'القيمة التقديرية',
        'col_status' => 'حالة الاعتماد', 'col_actions' => 'إجراءات', 'empty' => 'لا توجد طلبات شراء تطابق بحثك.'
    ]
][$isRtl ? 'ar' : 'ar'];

function getPrStatusBadge($status) {
    $map = [
        'draft' => ['color' => '#64748b', 'bg' => '#f1f5f9', 'border' => '#cbd5e1', 'label' => 'مسودة'],
        'pending' => ['color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fde68a', 'label' => 'قيد الاعتماد'],
        'approved' => ['color' => '#0d9488', 'bg' => '#ccfbf1', 'border' => '#99f6e4', 'label' => 'معتمد'],
        'rejected' => ['color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#fecaca', 'label' => 'مرفوض'],
        'completed' => ['color' => '#2563eb', 'bg' => '#eff6ff', 'border' => '#bfdbfe', 'label' => 'تم الشراء']
    ];
    $s = $map[$status] ?? $map['draft'];
    return "<span style='background:{$s['bg']}; color:{$s['color']}; border:1px solid {$s['border']}; padding: 4px 12px; border-radius: 8px; font-weight: 800; font-size: 0.75rem;'>{$s['label']}</span>";
}

$pendingCount = 0; $approvedCount = 0;
if (!empty($requests)) {
    foreach ($requests as $r) {
        if ($r->status === 'pending') $pendingCount++;
        if ($r->status === 'approved') $approvedCount++;
    }
}
?>

<style>
    :root {
        --c-teal: #0d9488;
        --c-teal-dark: #0f766e;
        --c-teal-light: #ccfbf1;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
    }

    .mod-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .mod-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .mod-title-box { display: flex; align-items: center; gap: 16px; }
    .mod-icon { width: 48px; height: 48px; background: var(--c-teal-light); color: var(--c-teal); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(13, 148, 136, 0.15); }
    .mod-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 800; }
    .mod-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: var(--c-text-muted); font-weight: 500; }
    
    .btn-primary { background: linear-gradient(135deg, var(--c-teal), var(--c-teal-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25); transition: 0.2s; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(13, 148, 136, 0.35); }

    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: var(--c-teal-light); color: var(--c-teal); }
    .kpi-info h4 { margin: 0 0 4px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .kpi-info p { margin: 0; font-size: 1.4rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; }
    .mod-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .mod-table th { padding: 16px 24px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .mod-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .mod-table tr:hover td { background: #f8fafc; }
    
    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; transition: 0.2s; }
    .action-btn:hover { background: var(--c-teal-light); border-color: #99f6e4; color: var(--c-teal); }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
</style>

<div class="mod-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="mod-header">
        <div class="mod-title-box">
            <div class="mod-icon"><i class="ph-duotone ph-clipboard-text"></i></div>
            <div>
                <h2 class="mod-title"><?= $t['title'] ?></h2>
                <p class="mod-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/purchasing/requisitions/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ccfbf1; color: #0f766e; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #99f6e4;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="ph-duotone ph-files"></i></div>
            <div class="kpi-info"><h4>إجمالي الطلبات</h4><p><?= count($requests ?? []) ?></p></div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #d97706;">
            <div class="kpi-icon" style="background:#fffbeb; color:#d97706;"><i class="ph-duotone ph-clock"></i></div>
            <div class="kpi-info"><h4 style="color:#d97706;">في انتظار الاعتماد</h4><p><?= $pendingCount ?></p></div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid var(--c-teal);">
            <div class="kpi-icon"><i class="ph-duotone ph-check-circle"></i></div>
            <div class="kpi-info"><h4 style="color:var(--c-teal);">طلبات معتمدة</h4><p><?= $approvedCount ?></p></div>
        </div>
    </div>

    <!-- Search Form -->
    <form action="/ERP/purchasing/requisitions" method="GET" style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:12px; display:flex; gap:10px; margin-bottom:24px;">
        <input type="text" name="search" style="flex:1; border:1px solid #cbd5e1; border-radius:8px; padding:10px 16px; font-family:inherit; background:#f8fafc;" placeholder="ابحث برقم الـ PR، الإدارة، أو مقدم الطلب..." value="<?= htmlspecialchars($search ?? '') ?>">
        <button type="submit" style="background:#0f172a; color:#fff; border:none; padding:10px 24px; border-radius:8px; font-weight:800; cursor:pointer;"><i class="ph-bold ph-magnifying-glass"></i> بحث</button>
        <?php if(!empty($search)): ?>
            <a href="/ERP/purchasing/requisitions" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:10px 20px; border-radius:8px; font-weight:800; text-decoration:none;">إلغاء</a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="mod-table">
            <thead>
                <tr>
                    <th style="width: 15%;"><?= $t['col_num'] ?></th>
                    <th style="width: 25%;"><?= $t['col_dept'] ?></th>
                    <th style="width: 20%;"><?= $t['col_dates'] ?></th>
                    <th style="width: 15%; text-align: end;"><?= $t['col_val'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_status'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;"><?= $t['empty'] ?></td></tr>
                <?php else: foreach ($requests as $r): ?>
                    <tr>
                        <td style="font-weight: 900; color: var(--c-teal); font-family: monospace; font-size: 1rem;"><i class="ph-bold ph-hash"></i> <?= htmlspecialchars($r->pr_number) ?></td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($r->department ?? '---') ?></div>
                            <div style="color: var(--c-text-muted); font-size: 0.8rem; margin-top:2px;"><i class="ph-fill ph-user text-slate-400"></i> <?= htmlspecialchars($r->requested_by ?? '---') ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #334155; font-size: 0.85rem;"><i class="ph-bold ph-calendar-blank"></i> <?= $r->request_date ?></div>
                            <div style="font-weight: 600; color: #dc2626; font-size: 0.85rem; margin-top:2px;"><i class="ph-bold ph-warning-circle"></i> <?= $r->required_date ?></div>
                        </td>
                        <td style="text-align: end; font-family: monospace; font-weight: 900; color: var(--c-text-dark); font-size: 1.05rem;">
                            <?= number_format($r->total_estimated_value, 2) ?>
                        </td>
                        <td style="text-align: center;">
                            <?= getPrStatusBadge($r->status) ?>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/purchasing/requisitions/<?= $r->id ?>" class="action-btn" title="عرض"><i class="ph-bold ph-eye"></i></a>
                            <a href="/ERP/purchasing/requisitions/<?= $r->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/purchasing/requisitions/<?= $r->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('تأكيد الحذف؟');">
                                <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div style="display:flex; justify-content:center; align-items:center; gap:8px; margin-top:24px;">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>" 
                   style="width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; border:1px solid #cbd5e1; text-decoration:none; font-weight:800; <?= $i == ($currentPage ?? 1) ? 'background:var(--c-teal); color:#fff; border-color:var(--c-teal);' : 'background:#fff; color:#475569;' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>