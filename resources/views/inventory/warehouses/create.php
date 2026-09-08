<?php
// Path: resources/views/inventory/warehouses/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($warehouse) && $warehouse !== null;
$actionUrl = $isEdit ? "/ERP/inventory/warehouses/" . (int)$warehouse->id . "/update" : "/ERP/inventory/warehouses/store";

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title_new' => 'إضافة مستودع / مخزن جديد',
        'title_edit' => 'تعديل بيانات المستودع',
        'panel_basic' => 'البيانات الأساسية',
        'code' => 'كود المستودع (تلقائي إن تُرِك)',
        'name_ar' => 'اسم المستودع (بالعربية)',
        'name_en' => 'اسم المستودع (بالإنجليزية)',
        'location' => 'موقع المستودع / العنوان',
        'panel_contact' => 'بيانات المسؤول',
        'manager' => 'اسم أمين المخزن / المسؤول',
        'phone' => 'رقم الهاتف',
        'active' => 'المستودع نشط',
        'cancel' => 'إلغاء وتراجع',
        'save' => 'حفظ المستودع',
        'update' => 'تحديث البيانات',
        'active_scope' => 'الفرع النشط:'
    ],
    'en' => [
        'title_new' => 'Add New Warehouse',
        'title_edit' => 'Edit Warehouse Details',
        'panel_basic' => 'Basic Information',
        'code' => 'Warehouse Code (Auto if empty)',
        'name_ar' => 'Warehouse Name (Arabic)',
        'name_en' => 'Warehouse Name (English)',
        'location' => 'Location / Address',
        'panel_contact' => 'Manager Details',
        'manager' => 'Storekeeper / Manager Name',
        'phone' => 'Phone Number',
        'active' => 'Warehouse is Active',
        'cancel' => 'Cancel',
        'save' => 'Save Warehouse',
        'update' => 'Update Data',
        'active_scope' => 'Active Branch:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-amber: #f59e0b; --c-amber-dark: #d97706; --c-amber-light: #fef3c7;
        --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #475569; --c-bg: #f8fafc;
    }

    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .back-btn { width: 42px; height: 42px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; color: var(--c-text-muted); text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-amber-light); color: var(--c-amber); border-color: #fcd34d; }
    .form-title { font-size: 1.6rem; font-weight: 800; color: var(--c-text-dark); margin: 0; }
    
    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 20px; }

    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 24px; border-top: 4px solid var(--c-amber); }
    .panel-title { font-size: 1.1rem; color: var(--c-text-dark); font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 10px;}
    
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: var(--c-bg); transition: 0.2s; color: var(--c-text-dark); box-sizing: border-box;}
    .form-control:focus { border-color: var(--c-amber); box-shadow: 0 0 0 4px var(--c-amber-light); background: #ffffff; outline:none;}
    .input-label { font-size: 0.85rem; font-weight: 800; color: var(--c-text-muted); margin-bottom: 8px; display: block; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }

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
        <a href="/ERP/inventory/warehouses" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <h2 class="form-title"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-amber);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-amber); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-warehouse" style="color:var(--c-amber);"></i> <?= $t['panel_basic'] ?></h3>
            <div class="grid-3">
                <div>
                    <label class="input-label"><?= $t['code'] ?></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; color:var(--c-amber-dark); font-weight:bold;" value="<?= $isEdit ? htmlspecialchars((string)($warehouse->code ?? '')) : '' ?>" <?= $isEdit ? 'readonly' : 'placeholder="WH-xxx"' ?>>
                </div>
                <div style="grid-column: span 2;">
                    <label class="input-label"><?= $t['name_ar'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= htmlspecialchars((string)($isEdit ? ($warehouse->name_ar ?? '') : '')) ?>" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top: 24px;">
                <div>
                    <label class="input-label"><?= $t['name_en'] ?></label>
                    <input type="text" name="name_en" class="form-control" value="<?= htmlspecialchars((string)($isEdit ? ($warehouse->name_en ?? '') : '')) ?>" dir="ltr">
                </div>
                <div>
                    <label class="input-label"><?= $t['location'] ?></label>
                    <input type="text" name="location" class="form-control" value="<?= htmlspecialchars((string)($isEdit ? ($warehouse->location ?? '') : '')) ?>">
                </div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-identification-card" style="color:var(--c-amber);"></i> <?= $t['panel_contact'] ?></h3>
            <div class="grid-3" style="align-items: center;">
                <div>
                    <label class="input-label"><?= $t['manager'] ?></label>
                    <input type="text" name="manager_name" class="form-control" value="<?= htmlspecialchars((string)($isEdit ? ($warehouse->manager_name ?? '') : '')) ?>">
                </div>
                <div>
                    <label class="input-label"><?= $t['phone'] ?></label>
                    <input type="text" name="phone" class="form-control" style="font-family:monospace;" value="<?= htmlspecialchars((string)($isEdit ? ($warehouse->phone ?? '') : '')) ?>">
                </div>
                <div style="padding-top: 20px;">
                    <div class="toggle-wrapper">
                        <?php $isActive = $isEdit ? (!empty($warehouse->is_active)) : 1; ?>
                        <input type="checkbox" id="isActive" name="is_active" class="toggle-checkbox" value="1" <?= $isActive ? 'checked' : '' ?>>
                        <label for="isActive" class="toggle-label"></label>
                        <span class="toggle-text"><?= $t['active'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/inventory/warehouses" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>