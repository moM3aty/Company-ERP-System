<?php
// Path: resources/views/accounting/taxes/create.php

try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
    $isEdit = isset($tax) && $tax !== null && !empty($tax->id);
    $actionUrl = "/ERP/accounting/taxes/store";

    $flashMsg = $_SESSION['flash_msg'] ?? null;
    $flashErr = $_SESSION['flash_err'] ?? null;
    unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

    $t = [
        'ar' => [
            'back' => 'رجوع للقائمة', 'title_new' => 'إضافة ضريبة جديدة', 'title_edit' => 'تعديل بيانات الضريبة',
            'name_ar' => 'اسم الضريبة (عربي)', 'name_en' => 'اسم الضريبة (إنجليزي)',
            'branch' => 'الفرع المخصص', 'branch_ph' => '-- عام (المركز الرئيسي) --',
            'rate' => 'نسبة الضريبة (%)', 'type' => 'نوع الضريبة',
            'type_vat' => 'قيمة مضافة (VAT)', 'type_wh' => 'خصم من المنبع (Withholding)', 'type_other' => 'أخرى (Other)',
            'account' => 'الحساب المحاسبي المرتبط بالدليل', 'acc_ph' => '-- اختر الحساب (أصول/خصوم) --',
            'status' => 'الحالة', 'st_active' => 'نشط', 'st_inactive' => 'معطل',
            'notes' => 'ملاحظات', 'btn_save' => 'حفظ البيانات'
        ],
        'en' => [
            'back' => 'Back to List', 'title_new' => 'Create New Tax', 'title_edit' => 'Edit Tax',
            'name_ar' => 'Tax Name (AR)', 'name_en' => 'Tax Name (EN)',
            'branch' => 'Assigned Branch', 'branch_ph' => '-- General (HQ) --',
            'rate' => 'Tax Rate (%)', 'type' => 'Tax Type',
            'type_vat' => 'VAT', 'type_wh' => 'Withholding', 'type_other' => 'Other',
            'account' => 'Linked GL Account', 'acc_ph' => '-- Select Account (Liability/Asset) --',
            'status' => 'Status', 'st_active' => 'Active', 'st_inactive' => 'Inactive',
            'notes' => 'Notes', 'btn_save' => 'Save Tax'
        ]
    ][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-tx: #0d9488; --c-tx-dark: #0f766e; --c-tx-light: #ccfbf1;
        --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b;
    }
    .form-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>;}
    .form-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .form-title { font-size: 1.4rem; font-weight: 900; color: var(--c-text-dark); margin: 0 0 20px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; }
    .form-group { margin-bottom: 20px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 6px; display: block; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; transition:0.3s;}
    .form-control:focus { outline: none; border-color: var(--c-tx); background: #ffffff; }
    .btn-submit { background: linear-gradient(135deg, var(--c-tx), var(--c-tx-dark)); color: #ffffff; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; font-size: 1rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition:0.3s;}
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(13, 148, 136, 0.35); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <a href="/ERP/accounting/taxes" style="text-decoration:none; color:var(--c-text-muted); font-weight:800; display:inline-flex; align-items:center; gap:6px;"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i> <?= $t['back'] ?></a>
    </div>

    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="form-card">
        <h2 class="form-title"><i class="ph-duotone ph-receipt" style="color:var(--c-tx);"></i> <?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
        
        <form action="<?= $actionUrl ?>" method="POST">
            <?php if($isEdit): ?> <input type="hidden" name="id" value="<?= htmlspecialchars((string)($tax->id ?? '')) ?>"> <?php endif; ?>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['name_ar'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($tax->name_ar ?? '')) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['name_en'] ?></label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($tax->name_en ?? '')) : '' ?>">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['rate'] ?> <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0" max="100" name="rate" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($tax->rate ?? '0')) : '0' ?>" required style="font-family:monospace; font-size:1.1rem; color:#ea580c;">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['type'] ?> <span style="color:red">*</span></label>
                    <select name="tax_type" class="form-control" required>
                        <option value="vat" <?= ($isEdit && ($tax->tax_type ?? '') === 'vat') ? 'selected' : '' ?>><?= $t['type_vat'] ?></option>
                        <option value="withholding" <?= ($isEdit && ($tax->tax_type ?? '') === 'withholding') ? 'selected' : '' ?>><?= $t['type_wh'] ?></option>
                        <option value="other" <?= ($isEdit && ($tax->tax_type ?? '') === 'other') ? 'selected' : '' ?>><?= $t['type_other'] ?></option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="input-label"><?= $t['account'] ?> <span style="color:red">*</span></label>
                <select name="account_id" class="form-control" required>
                    <option value=""><?= $t['acc_ph'] ?></option>
                    <?php if(!empty($accounts)): foreach($accounts as $acc): 
                        $aName = $isRtl ? ($acc->name_ar ?? '') : ($acc->name_en ?: ($acc->name_ar ?? ''));
                    ?>
                        <option value="<?= $acc->id ?? 0 ?>" <?= ($isEdit && ($tax->account_id ?? 0) == ($acc->id ?? 0)) ? 'selected' : '' ?>><?= htmlspecialchars((string)($acc->code ?? '')) ?> - <?= htmlspecialchars($aName) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>

            <?php if(!empty($branches)): ?>
            <div class="form-group">
                <label class="input-label"><?= $t['branch'] ?></label>
                <select name="branch_id" class="form-control">
                    <option value="0"><?= $t['branch_ph'] ?></option>
                    <?php foreach($branches as $b): 
                        $bName = $isRtl ? ($b->name_ar ?? '') : ($b->name_en ?: ($b->name_ar ?? ''));
                    ?>
                        <option value="<?= $b->id ?? 0 ?>" <?= ($isEdit && ($tax->branch_id ?? 0) == ($b->id ?? 0)) ? 'selected' : '' ?>><?= htmlspecialchars($bName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="input-label"><?= $t['status'] ?> <span style="color:red">*</span></label>
                <select name="is_active" class="form-control" required>
                    <option value="1" <?= ($isEdit && !empty($tax->is_active)) ? 'selected' : '' ?>><?= $t['st_active'] ?></option>
                    <option value="0" <?= ($isEdit && empty($tax->is_active)) ? 'selected' : '' ?>><?= $t['st_inactive'] ?></option>
                </select>
            </div>

            <div class="form-group">
                <label class="input-label"><?= $t['notes'] ?></label>
                <textarea name="notes" class="form-control" rows="3"><?= $isEdit ? htmlspecialchars((string)($tax->notes ?? '')) : '' ?></textarea>
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