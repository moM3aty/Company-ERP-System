<?php
// Path: resources/views/settings/users/form.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($user) && $user !== null;
$actionUrl = $isEdit ? "/ERP/settings/users/{$user->id}/update" : "/ERP/settings/users/store";
?>

<style>
    .user-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-title { font-size: 1.8rem; font-weight: 800; color: #0f172a; margin: 0 0 24px 0; display: flex; align-items: center; gap: 12px; }
    .back-btn { width: 36px; height: 36px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .panel-title { font-size: 1.1rem; color: #0f172a; font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 8px;}
    
    .input-label { font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px; display: block; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.9rem; outline: none; background: #f8fafc; transition: 0.2s; }
    .form-control:focus { border-color: #0284c7; box-shadow: 0 0 0 3px #e0f2fe; background: #ffffff;}
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn { padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; font-family: inherit; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none;}
    .btn-submit { background: linear-gradient(135deg, #0284c7, #0369a1); color: white; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25); }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35); }
</style>

<div class="user-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <h2 class="form-title">
        <a href="/ERP/settings/users" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <?= $isEdit ? ($isRtl ? 'تعديل بيانات الحساب' : 'Edit User Profile') : ($isRtl ? 'إنشاء حساب جديد' : 'New User Account') ?>
    </h2>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST" autocomplete="off">
        
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-user text-sky-600"></i> <?= $isRtl ? 'بيانات الاعتماد والدخول' : 'User Credentials' ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $isRtl ? 'اسم المستخدم' : 'Username' ?> <span style="color:red">*</span></label>
                    <input type="text" name="username" class="form-control" value="<?= $isEdit ? htmlspecialchars($user->username) : '' ?>" required autocomplete="off">
                </div>
                <div>
                    <label class="input-label"><?= $isRtl ? 'البريد الإلكتروني' : 'Email' ?> <span style="color:red">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= $isEdit ? htmlspecialchars($user->email) : '' ?>" required autocomplete="off">
                </div>
                <div style="grid-column: span 2;">
                    <label class="input-label">
                        <?= $isRtl ? 'كلمة المرور' : 'Password' ?> 
                        <?= $isEdit ? '<span style="color:#64748b; font-weight:500;">(تترك فارغة بدون تغيير)</span>' : '<span style="color:red">*</span>' ?>
                    </label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" <?= $isEdit ? '' : 'required' ?> autocomplete="new-password">
                </div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-shield-check text-sky-600"></i> <?= $isRtl ? 'الصلاحيات وتفضيلات الحساب' : 'Role & Settings' ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $isRtl ? 'الدور والصلاحية' : 'Role' ?> <span style="color:red">*</span></label>
                    <select name="role_id" class="form-control" required>
                        <option value="">-- <?= $isRtl ? 'اختر الدور' : 'Select Role' ?> --</option>
                        <?php foreach($roles ?? [] as $r): ?>
                            <option value="<?= $r->id ?>" <?= ($isEdit && $user->role_id == $r->id) ? 'selected' : '' ?>><?= htmlspecialchars($r->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="input-label"><?= $isRtl ? 'لغة الواجهة' : 'Default Language' ?></label>
                    <select name="language" class="form-control">
                        <option value="ar" <?= ($isEdit && $user->language === 'ar') ? 'selected' : '' ?>>العربية (Arabic)</option>
                        <option value="en" <?= ($isEdit && $user->language === 'en') ? 'selected' : '' ?>>English</option>
                    </select>
                </div>
                <div style="grid-column: span 2;">
                    <label class="input-label"><?= $isRtl ? 'حالة الحساب' : 'Status' ?></label>
                    <select name="status" class="form-control">
                        <option value="active" <?= ($isEdit && $user->status === 'active') ? 'selected' : '' ?>><?= $isRtl ? 'نشط (مسموح بالدخول)' : 'Active' ?></option>
                        <option value="suspended" <?= ($isEdit && $user->status === 'suspended') ? 'selected' : '' ?>><?= $isRtl ? 'موقوف (ممنوع من الدخول)' : 'Suspended' ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/settings/users" class="btn" style="background: #ffffff; border: 1px solid #cbd5e1; color: #475569;"><?= $isRtl ? 'إلغاء' : 'Cancel' ?></a>
            <button type="submit" class="btn btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? ($isRtl ? 'حفظ التعديلات' : 'Save Changes') : ($isRtl ? 'حفظ وإنشاء الحساب' : 'Create Account') ?></button>
        </div>
    </form>
</div>