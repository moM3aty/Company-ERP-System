<?php
// Path: resources/views/accounting/taxes/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($tax) && $tax !== null && !empty($tax->id);
$actionUrl = $isEdit ? "/ERP/accounting/taxes/{$tax->id}/update" : "/ERP/accounting/taxes/store";

$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_err']);
?>

<style>
    :root { 
        --c-tax: #0284c7; 
        --c-tax-dark: #0369a1; 
        --c-tax-light: #e0f2fe;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-tax); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-tax); font-size: 1.4rem; padding: 8px; background: var(--c-tax-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    @media(max-width:768px) { .grid-2 { grid-template-columns: 1fr; } }
    .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 14px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--c-tax); background: #ffffff; }

    .toggle-row { display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid var(--c-border); border-radius: 12px; background: #f8fafc; margin-top: 10px; }
    .toggle-info h4 { margin: 0 0 4px 0; font-size: 0.95rem; font-weight: 800; color: var(--c-text); }
    .toggle-info p { margin: 0; font-size: 0.8rem; color: var(--c-muted); font-weight: 600; }
    
    .switch { position: relative; display: inline-block; width: 50px; height: 28px; flex-shrink: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: var(--c-tax); }
    input:checked + .slider:before { transform: translateX(22px); }
    [dir="rtl"] input:checked + .slider:before { transform: translateX(-22px); left: auto; right: 4px; }

    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-tax), var(--c-tax-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.2); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/accounting/taxes" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل كود الضريبة' : 'إضافة ضريبة / رسم جديد' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">إعداد النسب وتوجيه الضريبة للحساب المختص بدليل الحسابات.</p>
            </div>
        </div>
    </div>

    <?php if($flashErr): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca; display:flex; align-items:center; gap:8px;">
            <i class="ph-fill ph-warning-circle" style="font-size:1.2rem;"></i> <?= htmlspecialchars($flashErr) ?>
        </div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-receipt"></i> البيانات الأساسية</h3>
            
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">كود الضريبة <span style="color:red">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-tax-dark);" value="<?= $isEdit ? htmlspecialchars($tax->code ?? '') : '' ?>" placeholder="مثال: VAT-14" required>
                </div>
                <div class="form-group">
                    <label class="input-label">نوع الضريبة</label>
                    <select name="tax_type" class="form-control">
                        <?php $tType = $tax->tax_type ?? 'vat'; ?>
                        <option value="vat" <?= $tType === 'vat' ? 'selected' : '' ?>>ضريبة القيمة المضافة (VAT)</option>
                        <option value="wht" <?= $tType === 'wht' ? 'selected' : '' ?>>ضريبة خصم وإضافة (WHT)</option>
                        <option value="sales" <?= $tType === 'sales' ? 'selected' : '' ?>>ضريبة مبيعات</option>
                        <option value="other" <?= $tType === 'other' ? 'selected' : '' ?>>أخرى / رسوم دولة</option>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top: 20px;">
                <div class="form-group">
                    <label class="input-label">اسم الضريبة (عربي) <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($tax->name_ar ?? '') : '' ?>" placeholder="مثال: قيمة مضافة 14%" required>
                </div>
                <div class="form-group">
                    <label class="input-label">الاسم (إنجليزي)</label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($tax->name_en ?? '') : '' ?>" placeholder="e.g. Standard VAT 14%">
                </div>
            </div>

            <div class="grid-2" style="margin-top: 20px;">
                <div class="form-group">
                    <label class="input-label">النسبة المئوية (%) <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0" name="tax_rate" class="form-control" style="font-family:monospace; font-size:1.2rem; font-weight:900; color:var(--c-tax);" value="<?= $isEdit ? (float)($tax->tax_rate ?? 0) : '14.00' ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">الحساب المالي المرتبط (GL Account) <span style="color:red">*</span></label>
                    <select name="account_id" class="form-control" required>
                        <option value="">-- اختر الحساب من الدليل --</option>
                        <?php foreach($accounts as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && ($tax->account_id ?? '') == $acc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label class="input-label">ملاحظات والتوجيه المحاسبي</label>
                <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($tax->notes ?? '') : '' ?>" placeholder="شرح لاستخدام الضريبة...">
            </div>

            <div class="toggle-row">
                <div class="toggle-info">
                    <h4>تفعيل الكود الضريبي</h4>
                    <p>السماح باستخدام هذه الضريبة في الفواتير وإدخالات القيود اليومية.</p>
                </div>
                <label class="switch">
                    <input type="checkbox" name="is_active" value="1" <?= (!$isEdit || !empty($tax->is_active)) ? 'checked' : '' ?>>
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/accounting/taxes" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث كود الضريبة' : 'حفظ وتسجيل الضريبة' ?></button>
        </div>
    </form>
</div>