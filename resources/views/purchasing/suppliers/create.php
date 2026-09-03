<?php
// Path: resources/views/purchasing/suppliers/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();
$isEdit = isset($supplier) && $supplier !== null;
$actionUrl = $isEdit ? "/ERP/purchasing/suppliers/{$supplier->id}/update" : "/ERP/purchasing/suppliers/store";

$t = [
    'ar' => [
        'title_new' => 'إضافة مورد جديد',
        'title_edit' => 'تحديث بيانات المورد',
        'basic_info' => 'بيانات الشركة والمورد',
        'name_ar' => 'اسم المورد (عربي)',
        'name_en' => 'اسم المورد (إنجليزي)',
        'code' => 'كود المورد (تلقائي إن تُرك فارغاً)',
        'contact_info' => 'معلومات التواصل والضريبة',
        'phone' => 'رقم الهاتف',
        'email' => 'البريد الإلكتروني',
        'tax_number' => 'الرقم الضريبي (VAT No)',
        'address' => 'عنوان المورد',
        'credit_status' => 'الائتمان والحالة',
        'credit_limit' => 'الحد الائتماني المسموح',
        'status' => 'حالة حساب المورد',
        'status_active' => 'نشط (Active)',
        'status_inactive' => 'موقوف (Inactive)',
        'notes' => 'ملاحظات داخلية',
        'cancel' => 'إلغاء وتراجع',
        'save' => 'تسجيل المورد',
        'update' => 'حفظ التعديلات'
    ],
    'en' => [
        'title_new' => 'Add New Supplier',
        'title_edit' => 'Edit Supplier Profile',
        'basic_info' => 'Company & Supplier Info',
        'name_ar' => 'Supplier Name (Arabic)',
        'name_en' => 'Supplier Name (English)',
        'code' => 'Supplier Code (Auto if empty)',
        'contact_info' => 'Contact & Tax Info',
        'phone' => 'Phone Number',
        'email' => 'Email Address',
        'tax_number' => 'VAT / Tax Number',
        'address' => 'Supplier Address',
        'credit_status' => 'Credit & Status',
        'credit_limit' => 'Allowed Credit Limit',
        'status' => 'Supplier Account Status',
        'status_active' => 'Active',
        'status_inactive' => 'Inactive',
        'notes' => 'Internal Notes',
        'cancel' => 'Cancel',
        'save' => 'Save Supplier',
        'update' => 'Update Supplier'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; gap: 16px; margin-bottom: 32px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: 0.2s; }
    .back-btn:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
    .form-title { font-size: 1.6rem; font-weight: 800; color: #0f172a; margin: 0; }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .panel-title { font-size: 1.1rem; color: #0f172a; font-weight: 800; margin: 0 0 24px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 10px;}
    
    .input-label { font-size: 0.85rem; font-weight: 800; color: #475569; margin-bottom: 8px; display: block; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-family: inherit; font-size: 0.95rem; outline: none; background: #f8fafc; transition: 0.2s; }
    .form-control:focus { border-color: #db2777; box-shadow: 0 0 0 4px #fce7f3; background: #ffffff;}
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn { padding: 12px 28px; border-radius: 12px; font-weight: 800; font-size: 0.95rem; font-family: inherit; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none;}
    .btn-submit { background: linear-gradient(135deg, #db2777, #be185d); color: white; box-shadow: 0 4px 12px rgba(219, 39, 119, 0.25); }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(219, 39, 119, 0.35); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <a href="/ERP/purchasing/suppliers" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <h2 class="form-title">
            <?= $isEdit ? $t['title_edit'] : $t['title_new'] ?>
        </h2>
        <?php if($isEdit): ?>
            <span style="margin-inline-start: auto; color: #db2777; font-family: monospace; font-weight: 800; font-size: 1.1rem; background: #fdf2f8; padding: 4px 12px; border-radius: 8px; border: 1px solid #fbcfe8;"><?= htmlspecialchars($supplier->code) ?></span>
        <?php endif; ?>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-buildings text-pink-600"></i> <?= $t['basic_info'] ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $t['name_ar'] ?> <span style="color:#ef4444">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($supplier->name_ar) : '' ?>" required>
                </div>

                <div>
                    <label class="input-label"><?= $t['name_en'] ?></label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($supplier->name_en ?? '') : '' ?>">
                </div>

                <div style="grid-column: span 2;">
                    <label class="input-label"><?= $t['code'] ?></label>
                    <input type="text" name="code" class="form-control" value="<?= $isEdit ? htmlspecialchars($supplier->code) : '' ?>" <?= $isEdit ? 'readonly' : '' ?>>
                </div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-address-book text-pink-600"></i> <?= $t['contact_info'] ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $t['phone'] ?></label>
                    <input type="text" name="phone" class="form-control" value="<?= $isEdit ? htmlspecialchars($supplier->phone ?? '') : '' ?>">
                </div>

                <div>
                    <label class="input-label"><?= $t['email'] ?></label>
                    <input type="email" name="email" class="form-control" value="<?= $isEdit ? htmlspecialchars($supplier->email ?? '') : '' ?>">
                </div>
                
                <div style="grid-column: span 2;">
                    <label class="input-label"><?= $t['tax_number'] ?></label>
                    <input type="text" name="tax_number" class="form-control" style="font-family: monospace; font-weight: 800;" value="<?= $isEdit ? htmlspecialchars($supplier->tax_number ?? '') : '' ?>">
                </div>

                <div style="grid-column: span 2;">
                    <label class="input-label"><?= $t['address'] ?></label>
                    <input type="text" name="address" class="form-control" value="<?= $isEdit ? htmlspecialchars($supplier->address ?? '') : '' ?>">
                </div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-shield-check text-pink-600"></i> <?= $t['credit_status'] ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $t['credit_limit'] ?> (<?= $currency ?>)</label>
                    <input type="number" step="0.01" name="credit_limit" class="form-control" value="<?= $isEdit ? htmlspecialchars($supplier->credit_limit) : '0.00' ?>" style="font-weight:800; font-family:monospace; color:#db2777;">
                </div>

                <div>
                    <label class="input-label"><?= $t['status'] ?></label>
                    <select name="is_active" class="form-control">
                        <option value="1" <?= ($isEdit && $supplier->is_active == 1) ? 'selected' : '' ?>><?= $t['status_active'] ?></option>
                        <option value="0" <?= ($isEdit && $supplier->is_active == 0) ? 'selected' : '' ?>><?= $t['status_inactive'] ?></option>
                    </select>
                </div>

                <div style="grid-column: span 2;">
                    <label class="input-label"><?= $t['notes'] ?></label>
                    <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($supplier->notes ?? '') : '' ?>">
                </div>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/purchasing/suppliers" class="btn" style="background: #ffffff; border: 1px solid #cbd5e1; color: #475569;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>