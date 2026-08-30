<?php
// Path: resources/views/sales/customers/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isRtl();
$currency = current_currency();

$isEdit = isset($customer) && $customer !== null;
$actionUrl = $isEdit ? "/ERP/sales/customers/{$customer->id}/update" : "/ERP/sales/customers/store";

$t = [
    'ar' => [
        'title' => $isEdit ? 'تعديل بيانات العميل' : 'إضافة عميل جديد',
        'basic' => 'البيانات الأساسية', 'contact' => 'التواصل والعنوان', 'finance' => 'البيانات المالية',
        'code' => 'كود العميل (تلقائي إن تُرك فارغاً)', 'name_ar' => 'الاسم (عربي)', 'name_en' => 'الاسم (إنجليزي)',
        'phone' => 'رقم الهاتف', 'email' => 'البريد الإلكتروني', 'address' => 'العنوان التفصيلي',
        'tax_no' => 'الرقم الضريبي (VAT)', 'credit_limit' => 'الحد الائتماني المسموح (' . htmlspecialchars($currency) . ')', 'is_active' => 'حالة الحساب',
        'active' => 'نشط', 'inactive' => 'موقوف', 'cancel' => 'إلغاء', 'save' => 'حفظ البيانات'
    ],
    'en' => [
        'title' => $isEdit ? 'Edit Customer' : 'Add New Customer',
        'basic' => 'Basic Details', 'contact' => 'Contact & Address', 'finance' => 'Financial Details',
        'code' => 'Customer Code (Auto-generated if empty)', 'name_ar' => 'Name (Arabic)', 'name_en' => 'Name (English)',
        'phone' => 'Phone Number', 'email' => 'Email Address', 'address' => 'Detailed Address',
        'tax_no' => 'Tax / VAT Number', 'credit_limit' => 'Credit Limit (' . htmlspecialchars($currency) . ')', 'is_active' => 'Account Status',
        'active' => 'Active', 'inactive' => 'Inactive', 'cancel' => 'Cancel', 'save' => 'Save Customer'
    ]
][$isRtl ? 'ar' : 'en'];

// الحد الائتماني محول ومجهز من الكونترولر في حالة التعديل
$creditLimitVal = $isEdit ? ($customer->credit_limit_converted ?? 0) : '0.00';
?>

<style>
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-title { font-size: 1.8rem; font-weight: 800; color: #0f172a; margin: 0 0 24px 0; display: flex; align-items: center; gap: 12px; }
    .back-btn { width: 36px; height: 36px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; margin-bottom: 24px; }
    .panel-title { font-size: 1.1rem; color: #0f172a; font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 8px;}
    .input-label { font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px; display: block; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.9rem; outline: none; background: #f8fafc; transition: 0.2s; }
    .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px #dbeafe; background: #ffffff;}
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 16px;}
    @media(max-width: 768px){ .grid-2 { grid-template-columns: 1fr; } }
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn-submit { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <h2 class="form-title">
        <a href="/ERP/sales/customers" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a> 
        <?= $t['title'] ?>
        <?php if($isEdit): ?>
            <span style="margin-inline-start: auto; color: #2563eb; font-family: monospace; font-size: 1.1rem;"><?= htmlspecialchars($customer->code) ?></span>
        <?php endif; ?>
    </h2>
    
    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-identification-card text-blue-600"></i> <?= $t['basic'] ?></h3>
            <div class="grid-2">
                <div><label class="input-label"><?= $t['name_ar'] ?> <span style="color:red">*</span></label><input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($customer->name_ar ?? '') : '' ?>" required></div>
                <div><label class="input-label"><?= $t['name_en'] ?> <span style="color:red">*</span></label><input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($customer->name_en ?? '') : '' ?>" required></div>
                <div style="grid-column: span 2;"><label class="input-label"><?= $t['code'] ?></label><input type="text" name="code" class="form-control" value="<?= $isEdit ? htmlspecialchars($customer->code ?? '') : '' ?>" <?= $isEdit ? 'readonly' : '' ?>></div>
            </div>
        </div>
        
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-address-book text-blue-600"></i> <?= $t['contact'] ?></h3>
            <div class="grid-2">
                <div><label class="input-label"><?= $t['phone'] ?></label><input type="text" name="phone" class="form-control" value="<?= $isEdit ? htmlspecialchars($customer->phone ?? '') : '' ?>"></div>
                <div><label class="input-label"><?= $t['email'] ?></label><input type="email" name="email" class="form-control" value="<?= $isEdit ? htmlspecialchars($customer->email ?? '') : '' ?>"></div>
                <div style="grid-column: span 2;"><label class="input-label"><?= $t['address'] ?></label><input type="text" name="address" class="form-control" value="<?= $isEdit ? htmlspecialchars($customer->address ?? '') : '' ?>"></div>
            </div>
        </div>
        
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-bank text-blue-600"></i> <?= $t['finance'] ?></h3>
            <div class="grid-2">
                <div><label class="input-label"><?= $t['tax_no'] ?></label><input type="text" name="tax_number" class="form-control" value="<?= $isEdit ? htmlspecialchars($customer->tax_number ?? '') : '' ?>"></div>
                <div><label class="input-label"><?= $t['credit_limit'] ?></label><input type="number" name="credit_limit" class="form-control" step="0.01" value="<?= htmlspecialchars($creditLimitVal) ?>"></div>
                <div>
                    <label class="input-label"><?= $t['is_active'] ?></label>
                    <select name="is_active" class="form-control">
                        <option value="1" <?= ($isEdit && $customer->is_active == 1) ? 'selected' : '' ?>><?= $t['active'] ?></option>
                        <option value="0" <?= ($isEdit && $customer->is_active == 0) ? 'selected' : '' ?>><?= $t['inactive'] ?></option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="sticky-footer">
            <a href="/ERP/sales/customers" style="padding: 10px 24px; border-radius: 8px; font-weight: 700; color: #475569; text-decoration: none; border: 1px solid #cbd5e1;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $t['save'] ?></button>
        </div>
    </form>
</div>