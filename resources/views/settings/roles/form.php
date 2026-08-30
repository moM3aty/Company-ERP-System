<?php
// Path: resources/views/settings/roles/form.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($role) && $role !== null;
$actionUrl = $isEdit ? "/ERP/settings/roles/{$role->id}/update" : "/ERP/settings/roles/store";

$isSystemRole = $isEdit ? ($role->is_system ?? 0) : 0;
$rolePermissions = $rolePermissions ?? [];

function checkPerm($key, $rolePermissions, $isSystemRole) {
    if ($isSystemRole) return 'checked disabled';
    return in_array($key, $rolePermissions) ? 'checked' : '';
}
?>

<style>
    .role-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-title { font-size: 1.8rem; font-weight: 800; color: #0f172a; margin: 0 0 24px 0; display: flex; align-items: center; gap: 12px; }
    .back-btn { width: 36px; height: 36px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .panel-title { font-size: 1.1rem; color: #0f172a; font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 8px;}
    
    .input-label { font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px; display: block; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.9rem; outline: none; background: #f8fafc; transition: 0.2s; }
    .form-control:focus { border-color: #4338ca; box-shadow: 0 0 0 3px #e0e7ff; background: #ffffff;}
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }

    .matrix-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; margin-top: 12px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
    .matrix-table th { padding: 12px; background: #0f172a; color: #ffffff; font-weight: 700; font-size: 0.8rem; text-align: center; }
    .matrix-table th:first-child { text-align: start; }
    .matrix-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; border-left: 1px solid #f1f5f9; text-align: center; vertical-align: middle; }
    .matrix-table td:first-child { text-align: start; font-weight: 700; color: #0f172a; background: #f8fafc; width: 40%; }
    
    .group-header { background: #e0e7ff !important; color: #4338ca !important; font-weight: 800 !important; font-size: 0.82rem; text-transform: uppercase; }
    .perm-check { width: 18px; height: 18px; accent-color: #4338ca; cursor: pointer; }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn { padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; font-family: inherit; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none;}
    .btn-submit { background: linear-gradient(135deg, #4338ca, #3730a3); color: white; box-shadow: 0 4px 12px rgba(67, 56, 202, 0.25); }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(67, 56, 202, 0.35); }
</style>

<div class="role-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <h2 class="form-title">
        <a href="/ERP/settings/roles" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <?= $isEdit ? ($isRtl ? 'تعديل الدور الصلاحيات' : 'Edit Role & Permissions') : ($isRtl ? 'إنشاء مصفوفة صلاحيات جديدة' : 'New Role Matrix') ?>
    </h2>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-identification-card text-indigo-600"></i> <?= $isRtl ? 'بيانات الدور القيادي' : 'Role Information' ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $isRtl ? 'اسم الدور الوظيفي' : 'Role Name' ?> <span style="color:red">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= $isEdit ? htmlspecialchars($role->name) : '' ?>" required <?= $isSystemRole ? 'readonly' : '' ?>>
                </div>
                <div>
                    <label class="input-label"><?= $isRtl ? 'حالة التفعيل' : 'Status' ?></label>
                    <select name="is_active" class="form-control" <?= $isSystemRole ? 'disabled' : '' ?>>
                        <option value="1" <?= ($isEdit && $role->is_active == 1) ? 'selected' : '' ?>><?= $isRtl ? 'نشط' : 'Active' ?></option>
                        <option value="0" <?= ($isEdit && $role->is_active == 0) ? 'selected' : '' ?>><?= $isRtl ? 'معطل' : 'Inactive' ?></option>
                    </select>
                </div>
                <div style="grid-column: span 2;">
                    <label class="input-label"><?= $isRtl ? 'الوصف التوضيحي' : 'Description' ?></label>
                    <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars($role->description ?? '') : '' ?>" <?= $isSystemRole ? 'readonly' : '' ?>>
                </div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-shield-check text-indigo-600"></i> <?= $isRtl ? 'مصفوفة التراخيص والتصاريح' : 'Permissions Matrix' ?></h3>
            
            <table class="matrix-table">
                <thead>
                    <tr>
                        <th><?= $isRtl ? 'الموديول / النظام' : 'Module' ?></th>
                        <th><?= $isRtl ? 'عرض (View)' : 'View' ?></th>
                        <th><?= $isRtl ? 'إضافة (Create)' : 'Create' ?></th>
                        <th><?= $isRtl ? 'تعديل (Edit)' : 'Edit' ?></th>
                        <th><?= $isRtl ? 'حذف (Delete)' : 'Delete' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($matrix as $groupName => $modules): ?>
                        <tr>
                            <td colspan="5" class="group-header"><i class="ph-fill ph-folder"></i> <?= $groupName ?></td>
                        </tr>
                        <?php foreach($modules as $modKey => $modLabel): ?>
                            <tr>
                                <td><?= $modLabel ?></td>
                                <td><input type="checkbox" name="permissions[]" value="<?= $modKey ?>.view" class="perm-check" <?= checkPerm($modKey . '.view', $rolePermissions, $isSystemRole) ?>></td>
                                <td><input type="checkbox" name="permissions[]" value="<?= $modKey ?>.create" class="perm-check" <?= checkPerm($modKey . '.create', $rolePermissions, $isSystemRole) ?>></td>
                                <td><input type="checkbox" name="permissions[]" value="<?= $modKey ?>.edit" class="perm-check" <?= checkPerm($modKey . '.edit', $rolePermissions, $isSystemRole) ?>></td>
                                <td><input type="checkbox" name="permissions[]" value="<?= $modKey ?>.delete" class="perm-check" <?= checkPerm($modKey . '.delete', $rolePermissions, $isSystemRole) ?>></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/settings/roles" class="btn" style="background: #ffffff; border: 1px solid #cbd5e1; color: #475569;"><?= $isRtl ? 'إلغاء' : 'Cancel' ?></a>
            <?php if(!$isSystemRole): ?>
                <button type="submit" class="btn btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? ($isRtl ? 'حفظ التعديلات' : 'Save Changes') : ($isRtl ? 'حفظ الصلاحيات' : 'Save Matrix') ?></button>
            <?php endif; ?>
        </div>
    </form>
</div>