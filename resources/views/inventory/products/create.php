<?php
// Path: resources/views/inventory/products/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($product) && $product !== null;
$actionUrl = $isEdit ? "/ERP/inventory/products/{$product->id}/update" : "/ERP/inventory/products/store";
?>

<style>
    :root {
        --c-amber: #f59e0b;
        --c-amber-dark: #d97706;
        --c-amber-light: #fef3c7;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
        --c-bg: #f8fafc;
    }

    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .back-btn { width: 42px; height: 42px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; color: var(--c-text-muted); text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-amber-light); color: var(--c-amber); border-color: #fcd34d; }
    .form-title { font-size: 1.6rem; font-weight: 800; color: var(--c-text-dark); margin: 0; }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 24px; border-top: 4px solid var(--c-amber); }
    .panel-title { font-size: 1.1rem; color: var(--c-text-dark); font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 10px;}
    
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: var(--c-bg); transition: 0.2s; color: var(--c-text-dark); }
    .form-control:focus { border-color: var(--c-amber); box-shadow: 0 0 0 4px var(--c-amber-light); background: #ffffff;}
    .input-label { font-size: 0.85rem; font-weight: 800; color: var(--c-text-muted); margin-bottom: 8px; display: block; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }

    /* Custom Checkbox Toggle */
    .toggle-wrapper { display: flex; align-items: center; gap: 12px; margin-top: 10px; }
    .toggle-checkbox { display: none; }
    .toggle-label { position: relative; width: 50px; height: 26px; background: #cbd5e1; border-radius: 30px; cursor: pointer; transition: 0.3s; }
    .toggle-label::after { content: ''; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; background: #fff; border-radius: 50%; transition: 0.3s; }
    .toggle-checkbox:checked + .toggle-label { background: #059669; }
    .toggle-checkbox:checked + .toggle-label::after { left: calc(100% - 3px); transform: translateX(-100%); }
    .toggle-text { font-weight: 800; color: #0f172a; }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn-submit { background: linear-gradient(135deg, var(--c-amber), var(--c-amber-dark)); color: white; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: var(--c-text-muted); padding: 12px 28px; border-radius: 12px; font-weight: 800; text-decoration: none; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <a href="/ERP/inventory/products" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <h2 class="form-title"><?= $isEdit ? 'تعديل بيانات الصنف' : 'إضافة صنف جديد لدليل المنتجات' ?></h2>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-package" style="color:var(--c-amber);"></i> البيانات التعريفية للصنف</h3>
            <div class="grid-2">
                <div>
                    <label class="input-label">اسم الصنف (بالعربية) <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($product->name_ar) : '' ?>" required>
                </div>
                <div>
                    <label class="input-label">اسم الصنف (بالإنجليزية)</label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($product->name_en ?? '') : '' ?>" dir="ltr">
                </div>
            </div>

            <div class="grid-3" style="margin-top: 24px;">
                <div>
                    <label class="input-label">كود الصنف المستودعي (تلقائي إن تُرِك)</label>
                    <input type="text" name="item_code" class="form-control" style="font-family:monospace; color:var(--c-amber-dark); font-weight:bold;" value="<?= $isEdit ? htmlspecialchars($product->item_code) : '' ?>" <?= $isEdit ? 'readonly' : 'placeholder="ITM-xxxxx"' ?>>
                </div>
                <div>
                    <label class="input-label">رقم الباركود (الماسح الضوئي)</label>
                    <input type="text" name="barcode" class="form-control" style="font-family:monospace;" value="<?= $isEdit ? htmlspecialchars($product->barcode ?? '') : '' ?>">
                </div>
                <div>
                    <label class="input-label">فئة الصنف</label>
                    <select name="category_id" class="form-control">
                        <option value="">-- بدون فئة --</option>
                        <?php foreach($categories ?? [] as $cat): ?>
                            <option value="<?= $cat->id ?>" <?= ($isEdit && $product->category_id == $cat->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div style="margin-top: 24px;">
                <label class="input-label">وصف وتفاصيل الصنف</label>
                <textarea name="description" class="form-control" rows="3"><?= $isEdit ? htmlspecialchars($product->description ?? '') : '' ?></textarea>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-currency-circle-dollar" style="color:var(--c-amber);"></i> بيانات التسعير والتخزين</h3>
            <div class="grid-3">
                <div>
                    <label class="input-label">وحدة القياس <span style="color:red">*</span></label>
                    <input type="text" name="unit" class="form-control" value="<?= $isEdit ? htmlspecialchars($product->unit) : 'قطعة' ?>" required>
                </div>
                <div>
                    <label class="input-label">سعر الشراء (التكلفة الافتراضية)</label>
                    <input type="number" step="0.01" name="purchase_price" class="form-control" style="font-family:monospace; font-weight:bold;" value="<?= $isEdit ? $product->purchase_price : '0.00' ?>">
                </div>
                <div>
                    <label class="input-label">سعر البيع الافتراضي</label>
                    <input type="number" step="0.01" name="selling_price" class="form-control" style="font-family:monospace; font-weight:bold; color:#0f172a;" value="<?= $isEdit ? $product->selling_price : '0.00' ?>">
                </div>
            </div>

            <div class="grid-2" style="margin-top: 24px; align-items: end;">
                <div>
                    <label class="input-label">حد إعادة الطلب (Reorder Level)</label>
                    <input type="number" step="0.01" name="reorder_level" class="form-control" value="<?= $isEdit ? $product->reorder_level : '0.00' ?>" placeholder="الكمية التي ينبهك النظام عندها">
                </div>
                <div>
                    <div class="toggle-wrapper">
                        <?php $isActive = $isEdit ? $product->is_active : 1; ?>
                        <input type="checkbox" id="isActive" name="is_active" class="toggle-checkbox" value="1" <?= $isActive ? 'checked' : '' ?>>
                        <label for="isActive" class="toggle-label"></label>
                        <span class="toggle-text">الصنف نشط ومتاح للحركات</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/inventory/products" class="btn-cancel">إلغاء وتراجع</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث بيانات الصنف' : 'حفظ وإضافة الصنف' ?></button>
        </div>
    </form>
</div>