<?php
// Path: resources/views/sales/representatives/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isRtl();
$currency = current_currency();

$isEdit = isset($representative) && $representative !== null;
$actionUrl = $isEdit ? "/ERP/sales/representatives/{$representative->id}/update" : "/ERP/sales/representatives/store";

$t = [
    'ar' => [
        'title' => $isEdit ? 'تعديل بيانات المندوب' : 'إضافة مندوب مبيعات جديد',
        'basic_info' => 'البيانات الأساسية للمندوب',
        'name_ar' => 'الاسم (عربي)',
        'name_en' => 'الاسم (إنجليزي)',
        'code' => 'الكود (تلقائي إن تُرك فارغاً)',
        'contact_info' => 'التواصل والمعلومات الشخصية',
        'phone' => 'رقم الهاتف',
        'email' => 'البريد الإلكتروني',
        'targets_info' => 'العمولات والمستهدف المالي (Targets)',
        'commission_rate' => 'نسبة العمولة %',
        'target_amount' => 'المستهدف الشهري (Target)',
        'status' => 'حالة الحساب',
        'status_active' => 'نشط (Active)',
        'status_inactive' => 'موقوف (Inactive)',
        'notes' => 'ملاحظات إضافية',
        'cancel' => 'إلغاء',
        'save' => $isEdit ? 'حفظ التعديلات' : 'حفظ بيانات المندوب'
    ],
    'en' => [
        'title' => $isEdit ? 'Edit Sales Rep' : 'Add New Sales Rep',
        'basic_info' => 'Basic Information',
        'name_ar' => 'Name (Arabic)',
        'name_en' => 'Name (English)',
        'code' => 'Code (Auto-generated if empty)',
        'contact_info' => 'Contact & Personal Details',
        'phone' => 'Phone Number',
        'email' => 'Email Address',
        'targets_info' => 'Commissions & Monthly Targets',
        'commission_rate' => 'Commission Rate %',
        'target_amount' => 'Monthly Target',
        'status' => 'Account Status',
        'status_active' => 'Active',
        'status_inactive' => 'Inactive',
        'notes' => 'Additional Notes',
        'cancel' => 'Cancel',
        'save' => $isEdit ? 'Save Changes' : 'Save Representative'
    ]
][$isRtl ? 'ar' : 'en'];

$targetVal = $isEdit ? ($representative->target_amount ?? 0) : '0.00';
?>

<style>
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-title { font-size: 1.8rem; font-weight: 800; color: #0f172a; margin: 0 0 24px 0; display: flex; align-items: center; gap: 12px; }
    .back-btn { width: 36px; height: 36px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: 0.2s; }
    .back-btn:hover { background: #f8fafc; color: #0f172a; }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .panel-title { font-size: 1.1rem; color: #0f172a; font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 8px;}
    
    .input-label { font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px; display: block; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.9rem; outline: none; background: #f8fafc; transition: 0.2s; }
    .form-control:focus { border-color: #0284c7; box-shadow: 0 0 0 3px #e0f2fe; background: #ffffff;}
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    @media(max-width: 768px){ .grid-2 { grid-template-columns: 1fr; } }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; box-shadow: 0 -4px 10px rgba(0,0,0,0.03); }
    .btn { padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; font-family: inherit; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none;}
    .btn-submit { background: linear-gradient(135deg, #0284c7, #0369a1); color: white; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25); }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <h2 class="form-title">
        <a href="/ERP/sales/representatives" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <?= $t['title'] ?>
        <?php if($isEdit): ?>
            <span style="margin-inline-start: auto; color: #0284c7; font-family: monospace; font-size: 1.1rem;"><?= htmlspecialchars($representative->code) ?></span>
        <?php endif; ?>
    </h2>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-identification-card text-sky-600"></i> <?= $t['basic_info'] ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $t['name_ar'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($representative->name_ar) : '' ?>" required>
                </div>

                <div>
                    <label class="input-label"><?= $t['name_en'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($representative->name_en ?? '') : '' ?>" required>
                </div>

                <div style="grid-column: span 2;">
                    <label class="input-label"><?= $t['code'] ?></label>
                    <input type="text" name="code" class="form-control" value="<?= $isEdit ? htmlspecialchars($representative->code) : '' ?>" <?= $isEdit ? 'readonly' : '' ?>>
                </div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-address-book text-sky-600"></i> <?= $t['contact_info'] ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $t['phone'] ?></label>
                    <input type="text" name="phone" class="form-control" value="<?= $isEdit ? htmlspecialchars($representative->phone ?? '') : '' ?>">
                </div>

                <div>
                    <label class="input-label"><?= $t['email'] ?></label>
                    <input type="email" name="email" class="form-control" value="<?= $isEdit ? htmlspecialchars($representative->email ?? '') : '' ?>">
                </div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-percent text-sky-600"></i> <?= $t['targets_info'] ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $t['commission_rate'] ?> <span style="color:red">*</span></label>
                    <input type="number" step="0.1" name="commission_rate" class="form-control" value="<?= $isEdit ? htmlspecialchars($representative->commission_rate) : '0.0' ?>" required style="font-weight:800; font-family:monospace; color:#0284c7;">
                </div>

                <div>
                    <label class="input-label"><?= $t['target_amount'] ?> (<?= htmlspecialchars($currency) ?>)</label>
                    <input type="number" step="0.01" name="target_amount" class="form-control" value="<?= htmlspecialchars($targetVal) ?>" style="font-weight:800; font-family:monospace;">
                </div>

                <div>
                    <label class="input-label"><?= $t['status'] ?></label>
                    <select name="is_active" class="form-control">
                        <option value="1" <?= ($isEdit && $representative->is_active == 1) ? 'selected' : '' ?>><?= $t['status_active'] ?></option>
                        <option value="0" <?= ($isEdit && $representative->is_active == 0) ? 'selected' : '' ?>><?= $t['status_inactive'] ?></option>
                    </select>
                </div>

                <div>
                    <label class="input-label"><?= $t['notes'] ?></label>
                    <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($representative->notes ?? '') : '' ?>">
                </div>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/sales/representatives" class="btn" style="background: #ffffff; border: 1px solid #cbd5e1; color: #475569; text-decoration: none;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $t['save'] ?></button>
        </div>
    </form>
</div>