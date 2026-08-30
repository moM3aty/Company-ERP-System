<?php
// Path: resources/views/accounting/journals/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

function getEntryStatusBadge($status) {
    $map = [
        'draft'     => ['bg' => '#fef3c7', 'color' => '#d97706', 'label' => 'مسودة (Draft)'],
        'posted'    => ['bg' => '#e0e7ff', 'color' => '#4338ca', 'label' => 'مرحّل (Posted)'],
        'cancelled' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => 'ملغى (Cancelled)']
    ];
    $s = $map[$status] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $status];
    return "<span style='background:{$s['bg']}; color:{$s['color']}; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.75rem; border:1px solid currentColor;'>{$s['label']}</span>";
}
?>

<style>
    :root {
        --c-je: #4f46e5;
        --c-je-dark: #3730a3;
        --c-je-light: #eef2ff;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .je-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .je-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .je-title-box { display: flex; align-items: center; gap: 16px; }
    .je-icon { width: 48px; height: 48px; background: var(--c-je-light); color: var(--c-je); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.12); }
    .je-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-je { background: linear-gradient(135deg, var(--c-je), var(--c-je-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-je-light); color: var(--c-je); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .je-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .je-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .je-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-je-light); color: var(--c-je); border-color: #c7d2fe; }
    .action-btn.post:hover { background: #e0e7ff; color: #4338ca; border-color: #a5b4fc; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-je); color: #ffffff; border-color: var(--c-je); }

    /* Custom Confirm Modal */
    .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 9999; }
    .modal-card { background: #ffffff; border-radius: 20px; width: 100%; max-width: 420px; padding: 32px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); animation: modalIn 0.2s ease-out; }
    @keyframes modalIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .modal-icon-circle { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; }
    .modal-actions-row { display: flex; gap: 12px; margin-top: 24px; justify-content: center; }
    .btn-modal-cancel { flex: 1; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; font-weight: 800; cursor: pointer; }
    .btn-modal-confirm { flex: 1; padding: 12px; border-radius: 10px; border: none; color: #ffffff; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
</style>

<div class="je-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="je-header">
        <div class="je-title-box">
            <div class="je-icon"><i class="ph-duotone ph-notebook"></i></div>
            <div>
                <h2 class="je-title">قيود اليومية العامة (Journal Entries)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);">السجل المحاسبي المركزي لجميع الحركات والعمليات المالية.</p>
            </div>
        </div>
        <a href="/ERP/accounting/journal-entries/create" class="btn-je"><i class="ph-bold ph-plus"></i> إنشاء قيد جديد</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-files"></i></div><div class="kpi-info"><h4>إجمالي القيود</h4><p><?= number_format($stats->total_entries ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-pencil-line"></i></div><div class="kpi-info"><h4 style="color:#d97706;">المسودات (Drafts)</h4><p><?= number_format($stats->drafts ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#e0e7ff; color:#4338ca;"><i class="ph-duotone ph-check-square-offset"></i></div><div class="kpi-info"><h4 style="color:#4338ca;">المرحلة (Posted)</h4><p><?= number_format($stats->posted ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#f0fdf4; color:#16a34a;"><i class="ph-duotone ph-currency-circle-dollar"></i></div><div class="kpi-info"><h4 style="color:#16a34a;">إجمالي المبالغ</h4><p><?= number_format((float)($stats->total_value ?? 0), 2) ?></p></div></div>
    </div>

    <form action="/ERP/accounting/journal-entries" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2;" placeholder="ابحث برقم القيد، البيان، أو رقم المرجع..." value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="status" class="form-control" style="flex:1;">
            <option value="">-- جميع الحالات --</option>
            <option value="draft" <?= ($statusFilter==='draft')?'selected':'' ?>>مسودة (Draft)</option>
            <option value="posted" <?= ($statusFilter==='posted')?'selected':'' ?>>مرحّل (Posted)</option>
        </select>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> بحث</button>
    </form>

    <div class="table-card">
        <table class="je-table">
            <thead>
                <tr>
                    <th style="width: 15%;">رقم القيد</th>
                    <th style="width: 12%;">التاريخ</th>
                    <th style="width: 35%;">البيان / الوصف</th>
                    <th style="width: 13%;">رقم المرجع</th>
                    <th style="width: 12%; text-align: center;">إجمالي القيد</th>
                    <th style="width: 10%; text-align: center;">الحالة</th>
                    <th style="width: 13%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد قيود يومية تطابق بحثك.</td></tr>
                <?php else: foreach ($entries as $e): ?>
                    <tr>
                        <td>
                            <a href="/ERP/accounting/journal-entries/<?= $e->id ?>" style="font-weight: 900; color: var(--c-je); font-family: monospace; font-size: 1rem; text-decoration:none;">
                                <?= htmlspecialchars($e->entry_number ?? '') ?>
                            </a>
                        </td>
                        <td style="font-family: monospace; font-weight: 700; color: var(--c-text-dark);"><?= htmlspecialchars($e->entry_date ?? '') ?></td>
                        <td style="font-weight: 700; color: var(--c-text-dark);"><?= htmlspecialchars($e->description ?? '---') ?></td>
                        <td><span style="font-size:0.85rem; color:#64748b; font-family:monospace;"><?= htmlspecialchars($e->reference_number ?? '---') ?></span></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 900; color: #0f172a;"><?= number_format((float)($e->total_amount ?? 0), 2) ?></td>
                        <td style="text-align: center;"><?= getEntryStatusBadge($e->status ?? '') ?></td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/accounting/journal-entries/<?= $e->id ?>" class="action-btn" title="عرض السند"><i class="ph-bold ph-eye"></i></a>
                            
                            <?php if(($e->status ?? '') === 'draft'): ?>
                                <a href="/ERP/accounting/journal-entries/<?= $e->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                
                                <button type="button" class="action-btn post" title="ترحيل القيد" onclick="triggerPostModal('/ERP/accounting/journal-entries/<?= $e->id ?>/post')">
                                    <i class="ph-bold ph-check-square-offset"></i>
                                </button>
                                
                                <button type="button" class="action-btn delete" title="حذف" onclick="triggerDeleteModal('/ERP/accounting/journal-entries/<?= $e->id ?>/delete')">
                                    <i class="ph-bold ph-trash"></i>
                                </button>
                            <?php endif; ?>
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

<!-- Dynamic Action Modal -->
<div id="actionModal" class="modal-overlay">
    <div class="modal-card">
        <div id="modalIcon" class="modal-icon-circle"></div>
        <h3 id="modalTitle" style="margin: 0 0 8px 0; font-size: 1.3rem; font-weight: 900; color: var(--c-text-dark);"></h3>
        <p id="modalMsg" style="margin: 0; color: var(--c-text-muted); font-size: 0.9rem; line-height: 1.5; font-weight: 600;"></p>
        
        <div class="modal-actions-row">
            <button type="button" onclick="closeActionModal()" class="btn-modal-cancel">تراجع وإلغاء</button>
            <form id="actionForm" method="POST" style="flex: 1;">
                <button type="submit" id="modalBtnSubmit" class="btn-modal-confirm" style="width: 100%;"></button>
            </form>
        </div>
    </div>
</div>

<script>
function triggerPostModal(actionUrl) {
    document.getElementById('actionForm').action = actionUrl;
    document.getElementById('modalTitle').innerText = 'تأكيد ترحيل القيد';
    document.getElementById('modalMsg').innerText = 'هل أنت متأكد من ترحيل هذا القيد إلى الحسابات؟ لن يمكنك تعديله بعد الترحيل.';
    
    let icon = document.getElementById('modalIcon');
    icon.innerHTML = '<i class="ph-bold ph-check-circle" style="font-size:2rem; color:#4f46e5;"></i>';
    icon.style.background = '#eef2ff';
    
    let btn = document.getElementById('modalBtnSubmit');
    btn.innerHTML = '<i class="ph-bold ph-check"></i> تأكيد الترحيل';
    btn.style.background = '#4f46e5';
    
    document.getElementById('actionModal').style.display = 'flex';
}

function triggerDeleteModal(actionUrl) {
    document.getElementById('actionForm').action = actionUrl;
    document.getElementById('modalTitle').innerText = 'تحذير أمني!';
    document.getElementById('modalMsg').innerText = 'هل أنت متأكد من حذف هذا القيد تماماً؟ لا يمكن التراجع عن هذه الخطوة.';
    
    let icon = document.getElementById('modalIcon');
    icon.innerHTML = '<i class="ph-bold ph-warning-circle" style="font-size:2rem; color:#dc2626;"></i>';
    icon.style.background = '#fef2f2';
    
    let btn = document.getElementById('modalBtnSubmit');
    btn.innerHTML = '<i class="ph-bold ph-trash"></i> تأكيد الحذف';
    btn.style.background = '#dc2626';
    
    document.getElementById('actionModal').style.display = 'flex';
}

function closeActionModal() {
    document.getElementById('actionModal').style.display = 'none';
}
</script>