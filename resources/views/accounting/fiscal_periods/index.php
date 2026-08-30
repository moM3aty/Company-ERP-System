<?php
// Path: resources/views/accounting/fiscal_periods/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

function getPeriodStatusBadge($status) {
    if ($status === 'open') return "<span style='background:#f0fdf4; color:#16a34a; padding:6px 14px; border-radius:8px; font-weight:800; font-size:0.78rem; border:1px solid currentColor; display:inline-block; white-space:nowrap;'>مفتوحة (Open)</span>";
    return "<span style='background:#fef2f2; color:#dc2626; padding:6px 14px; border-radius:8px; font-weight:800; font-size:0.78rem; border:1px solid currentColor; display:inline-block; white-space:nowrap;'>مغلقة (Closed)</span>";
}
?>

<style>
    :root {
        --c-fp: #d97706; 
        --c-fp-dark: #b45309;
        --c-fp-light: #fef3c7;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .fp-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .fp-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .fp-title-box { display: flex; align-items: center; gap: 16px; }
    .fp-icon { width: 50px; height: 50px; background: var(--c-fp-light); color: var(--c-fp); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.15); flex-shrink: 0; }
    .fp-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-fp { background: linear-gradient(135deg, var(--c-fp), var(--c-fp-dark)); color: #ffffff !important; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.2); white-space: nowrap; }

    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:900px){ .kpi-row { grid-template-columns: 1fr; } }
    .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-info h4 { margin: 0 0 6px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.4rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }
    .kpi-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }

    .search-bar { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px; display: flex; gap: 12px; align-items: center; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .form-control { border: 1px solid var(--c-border); border-radius: 10px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; color: var(--c-text-dark); font-weight: 600; outline: none; transition: all 0.2s; }
    .form-control:focus { border-color: var(--c-fp); background: #ffffff; box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.1); }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 11px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .fp-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .fp-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.78rem; text-align: start; }
    .fp-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; text-align: start; }

    .action-btn { width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; transition: all 0.2s; }
    .action-btn:hover { background: var(--c-fp-light); color: var(--c-fp); border-color: #fde68a; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-fp); color: #ffffff; border-color: var(--c-fp); }

    .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 9999; }
    .modal-card { background: #ffffff; border-radius: 20px; width: 100%; max-width: 440px; padding: 32px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); animation: modalIn 0.2s ease-out; }
    @keyframes modalIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .modal-icon-circle { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; background: var(--c-fp-light); color: var(--c-fp-dark); }
    .modal-actions-row { display: flex; gap: 12px; margin-top: 24px; justify-content: center; }
    .btn-modal-cancel { flex: 1; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; font-weight: 800; cursor: pointer; }
    .btn-modal-confirm { flex: 1; padding: 12px; border-radius: 10px; border: none; background: var(--c-fp); color: #ffffff; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
</style>

<div class="fp-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="fp-header">
        <div class="fp-title-box">
            <div class="fp-icon"><i class="ph-duotone ph-calendar-check"></i></div>
            <div>
                <h2 class="fp-title">السنوات والفترات المالية (Fiscal Periods)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;">إدارة السنوات المالية وإغلاق الفترات لضمان سلامة الدفاتر المحاسبية.</p>
            </div>
        </div>
        <a href="/ERP/accounting/fiscal-periods/create" class="btn-fp"><i class="ph-bold ph-plus"></i> إضافة فترة مالية جديدة</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0; display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-check-circle" style="font-size:1.2rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca; display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-warning-circle" style="font-size:1.2rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-info">
                <h4>إجمالي الفترات المسجلة</h4>
                <p><?= number_format($stats->total_periods ?? 0) ?></p>
            </div>
            <div class="kpi-icon" style="background:#f1f5f9; color:#475569;"><i class="ph-duotone ph-calendar"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4 style="color:#16a34a;">فترات مفتوحة ونشطة</h4>
                <p style="color:#16a34a;"><?= number_format($stats->open_periods ?? 0) ?></p>
            </div>
            <div class="kpi-icon" style="background:#f0fdf4; color:#16a34a;"><i class="ph-duotone ph-door-open"></i></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-info">
                <h4 style="color:#dc2626;">فترات مغلقة وتاريخية</h4>
                <p style="color:#dc2626;"><?= number_format($stats->closed_periods ?? 0) ?></p>
            </div>
            <div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-lock-key"></i></div>
        </div>
    </div>

    <form action="/ERP/accounting/fiscal-periods" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2;" placeholder="ابحث باسم السنة أو الملاحظات..." value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="status" class="form-control" style="flex:1;">
            <option value="">-- جميع الحالات --</option>
            <option value="open" <?= ($statusFilter==='open')?'selected':'' ?>>مفتوحة (Open)</option>
            <option value="closed" <?= ($statusFilter==='closed')?'selected':'' ?>>مغلقة (Closed)</option>
        </select>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> بحث</button>
    </form>

    <div class="table-card">
        <table class="fp-table">
            <thead>
                <tr>
                    <th style="width: 25%;">اسم السنة / الفترة المالية</th>
                    <th style="width: 22%; text-align: center;">النطاق الزمني</th>
                    <th style="width: 23%;">ملاحظات</th>
                    <th style="width: 15%; text-align: center;">الحالة</th>
                    <th style="width: 15%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($periods)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد فترات مالية مسجلة.</td></tr>
                <?php else: foreach ($periods as $p): ?>
                    <tr>
                        <td style="font-weight: 900; color: var(--c-text-dark); font-size: 1.05rem;">
                            <a href="/ERP/accounting/fiscal-periods/<?= $p->id ?>" style="color:var(--c-text-dark); text-decoration:none;">
                                <?= htmlspecialchars($p->period_name) ?>
                            </a>
                        </td>
                        <td style="text-align: center; font-size: 0.85rem; font-family: monospace; color: #64748b; white-space: nowrap;">
                            <span dir="ltr" style="display:inline-block; unicode-bidi: isolate; font-weight:800; color:var(--c-fp-dark);"><?= htmlspecialchars($p->start_date) ?></span>
                            <span style="margin: 0 4px; font-weight: bold; color: #cbd5e1;">إلى</span>
                            <span dir="ltr" style="display:inline-block; unicode-bidi: isolate; font-weight:800; color:var(--c-fp-dark);"><?= htmlspecialchars($p->end_date) ?></span>
                        </td>
                        <td style="color: #64748b; font-size: 0.85rem;"><?= htmlspecialchars($p->notes ?: '---') ?></td>
                        <td style="text-align: center;">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:4px;">
                                <?= getPeriodStatusBadge($p->status) ?>
                                <?php if($p->status === 'closed' && $p->closed_at): ?>
                                    <span style="font-size:0.72rem; color:#94a3b8; font-weight:700; font-family:monospace;">أغلقت: <?= date('Y-m-d', strtotime($p->closed_at)) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <div style="display: flex; justify-content: center; gap: 6px;">
                                <a href="/ERP/accounting/fiscal-periods/<?= $p->id ?>" class="action-btn" title="مركز التحكم وتدقيق السنة"><i class="ph-bold ph-eye"></i></a>
                                <?php if($p->status === 'open'): ?>
                                    <button type="button" class="action-btn" title="إغلاق الفترة المالية" style="color:var(--c-fp);" onclick="openCloseModal('<?= $p->id ?>', '<?= htmlspecialchars($p->period_name) ?>')">
                                        <i class="ph-bold ph-lock-key"></i>
                                    </button>
                                    <a href="/ERP/accounting/fiscal-periods/<?= $p->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                    <form action="/ERP/accounting/fiscal-periods/<?= $p->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذه الفترة؟');">
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>&status=<?= urlencode($statusFilter ?? '') ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<div id="closePeriodModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-icon-circle">
            <i class="ph-bold ph-lock-key" style="font-size:2.2rem;"></i>
        </div>
        <h3 style="margin: 0 0 8px 0; font-size: 1.3rem; font-weight: 900; color: var(--c-text-dark);">تأكيد إغلاق الفترة المالية</h3>
        <p style="margin: 0; color: var(--c-text-muted); font-size: 0.9rem; line-height: 1.5; font-weight: 600;" id="closeModalDesc">
            هل أنت متأكد من الإغلاق؟ لن تتمكن من إضافة قيود جديدة في هذه التواريخ بعد إغلاقها.
        </p>
        
        <div class="modal-actions-row">
            <button type="button" onclick="closeCloseModal()" class="btn-modal-cancel">تراجع وإلغاء</button>
            <form id="closePeriodForm" action="" method="POST" style="flex: 1;">
                <button type="submit" class="btn-modal-confirm" style="width: 100%;">
                    <i class="ph-bold ph-check"></i> تأكيد الإغلاق
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openCloseModal(id, name) {
    document.getElementById('closePeriodForm').action = '/ERP/accounting/fiscal-periods/' + id + '/close';
    document.getElementById('closeModalDesc').innerText = 'هل أنت متأكد من إغلاق (' + name + ')؟ لن يُسمح لك بإضافة أي قيود مالية في هذه التواريخ بعد القفل.';
    document.getElementById('closePeriodModal').style.display = 'flex';
}
function closeCloseModal() {
    document.getElementById('closePeriodModal').style.display = 'none';
}
</script>