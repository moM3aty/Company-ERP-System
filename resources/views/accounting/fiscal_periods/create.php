<?php
// Path: resources/views/accounting/fiscal_periods/create.php

try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
    $isEdit = isset($period) && $period !== null && !empty($period->id);
    $actionUrl = "/ERP/accounting/fiscal-periods/store";

    $flashMsg = $_SESSION['flash_msg'] ?? null;
    $flashErr = $_SESSION['flash_err'] ?? null;
    unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

    $t = [
        'ar' => [
            'back' => 'رجوع للقائمة', 'title_new' => 'إضافة فترة مالية جديدة', 'title_edit' => 'تعديل بيانات الفترة المالية',
            'name_ar' => 'اسم الفترة (عربي)', 'name_en' => 'اسم الفترة (إنجليزي)',
            'branch' => 'الفرع المخصص', 'branch_ph' => '-- عام (المركز الرئيسي) --',
            'start' => 'تاريخ البداية', 'end' => 'تاريخ النهاية',
            'status' => 'حالة الفترة', 'st_open' => 'مفتوحة (تستقبل قيود)', 'st_closed' => 'مغلقة (لا تستقبل قيود)',
            'notes' => 'ملاحظات', 'btn_save' => 'حفظ البيانات'
        ],
        'en' => [
            'back' => 'Back to List', 'title_new' => 'Create New Fiscal Period', 'title_edit' => 'Edit Fiscal Period',
            'name_ar' => 'Period Name (AR)', 'name_en' => 'Period Name (EN)',
            'branch' => 'Assigned Branch', 'branch_ph' => '-- General (HQ) --',
            'start' => 'Start Date', 'end' => 'End Date',
            'status' => 'Period Status', 'st_open' => 'Open (Accepts entries)', 'st_closed' => 'Closed (No new entries)',
            'notes' => 'Notes', 'btn_save' => 'Save Period'
        ]
    ][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-fp: #6366f1; --c-fp-dark: #4338ca; --c-fp-light: #e0e7ff;
        --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b;
    }
    .form-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .form-title { font-size: 1.4rem; font-weight: 900; color: var(--c-text-dark); margin: 0 0 20px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; }
    .form-group { margin-bottom: 20px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 6px; display: block; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--c-fp); background: #ffffff; }
    .btn-submit { background: linear-gradient(135deg, var(--c-fp), var(--c-fp-dark)); color: #ffffff; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; font-size: 1rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <a href="/ERP/accounting/fiscal-periods" style="text-decoration:none; color:var(--c-text-muted); font-weight:800; display:inline-flex; align-items:center; gap:6px;"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i> <?= $t['back'] ?></a>
    </div>

    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="form-card">
        <h2 class="form-title"><i class="ph-duotone ph-calendar-plus" style="color:var(--c-fp);"></i> <?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
        
        <form action="<?= $actionUrl ?>" method="POST">
            <?php if($isEdit): ?> <input type="hidden" name="id" value="<?= htmlspecialchars((string)($period->id ?? '')) ?>"> <?php endif; ?>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['name_ar'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($period->name_ar ?? '')) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['name_en'] ?></label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($period->name_en ?? '')) : '' ?>">
                </div>
            </div>

            <?php if(!empty($branches)): ?>
            <div class="form-group">
                <label class="input-label"><?= $t['branch'] ?></label>
                <select name="branch_id" class="form-control">
                    <option value="0"><?= $t['branch_ph'] ?></option>
                    <?php foreach($branches as $b): 
                        $bName = $isRtl ? ($b->name_ar ?? '') : ($b->name_en ?: ($b->name_ar ?? ''));
                    ?>
                        <option value="<?= $b->id ?? 0 ?>" <?= ($isEdit && ($period->branch_id ?? 0) == ($b->id ?? 0)) ? 'selected' : '' ?>><?= htmlspecialchars($bName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['start'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($period->start_date ?? '')) : date('Y-01-01') ?>" required style="font-family:monospace;">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['end'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="end_date" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($period->end_date ?? '')) : date('Y-12-31') ?>" required style="font-family:monospace;">
                </div>
            </div>

            <div class="form-group">
                <label class="input-label"><?= $t['status'] ?> <span style="color:red">*</span></label>
                <select name="status" class="form-control" required>
                    <option value="open" <?= ($isEdit && ($period->status ?? '') === 'open') ? 'selected' : '' ?>><?= $t['st_open'] ?></option>
                    <option value="closed" <?= ($isEdit && ($period->status ?? '') === 'closed') ? 'selected' : '' ?>><?= $t['st_closed'] ?></option>
                </select>
            </div>

            <div class="form-group">
                <label class="input-label"><?= $t['notes'] ?></label>
                <textarea name="notes" class="form-control" rows="3"><?= $isEdit ? htmlspecialchars((string)($period->notes ?? '')) : '' ?></textarea>
            </div>

            <div style="text-align:end; margin-top:24px;">
                <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $t['btn_save'] ?></button>
            </div>
        </form>
    </div>
</div>

<?php 
} catch (Throwable $e) {
    echo "<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 View Error (create.php)</h3>" . htmlspecialchars($e->getMessage()) . "</div>";
}
?>