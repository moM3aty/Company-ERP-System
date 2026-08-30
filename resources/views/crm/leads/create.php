<?php
// Path: resources/views/crm/leads/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$currentLocale = $_SESSION['locale'] ?? 'ar';
$isRtl = $currentLocale === 'ar';

$isEdit = isset($lead) && $lead !== null;
$actionUrl = $isEdit ? "/ERP/crm/leads/{$lead->id}/update" : "/ERP/crm/leads/store";

$t = [
    'ar' => [
        'title' => $isEdit ? 'تعديل بيانات العميل المحتمل' : 'إضافة عميل محتمل جديد',
        'master_info' => 'البيانات الأساسية',
        'comp_name' => 'اسم الشركة / المؤسسة',
        'contact' => 'اسم مسؤول التواصل',
        'phone' => 'رقم الهاتف',
        'email' => 'البريد الإلكتروني',
        'sales_info' => 'بيانات المبيعات والمتابعة',
        'source' => 'مصدر العميل',
        'status' => 'حالة الفرصة',
        'followup' => 'تاريخ المتابعة القادمة',
        'owner' => 'الموظف المسؤول',
        'select_owner' => '-- اختر الموظف --',
        'cancel' => 'إلغاء',
        'save' => $isEdit ? 'حفظ التعديلات' : 'تسجيل العميل المحتمل'
    ],
    'en' => [
        'title' => $isEdit ? 'Edit Lead' : 'Add New Lead',
        'master_info' => 'Basic Information',
        'comp_name' => 'Company Name',
        'contact' => 'Contact Person',
        'phone' => 'Phone Number',
        'email' => 'Email Address',
        'sales_info' => 'Sales & Follow-up Info',
        'source' => 'Lead Source',
        'status' => 'Lead Status',
        'followup' => 'Next Follow-up Date',
        'owner' => 'Assigned To (Owner)',
        'select_owner' => '-- Select User --',
        'cancel' => 'Cancel',
        'save' => $isEdit ? 'Save Changes' : 'Save Lead'
    ]
][$currentLocale];

$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_err']);
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
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 16px;}
    @media(max-width: 768px){ .grid-2 { grid-template-columns: 1fr; } }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; box-shadow: 0 -4px 10px rgba(0,0,0,0.03); }
    .btn { padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; font-family: inherit; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none;}
    .btn-submit { background: linear-gradient(135deg, #0284c7, #0369a1); color: white; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25); }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <h2 class="form-title">
        <a href="/ERP/crm/leads" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <?= $t['title'] ?>
    </h2>

    <?php if($flashErr): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-warning-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashErr) ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-identification-card text-sky-600"></i> <?= $t['master_info'] ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $t['comp_name'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="company_name" class="form-control" value="<?= $isEdit ? htmlspecialchars($lead->company_name) : '' ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['contact'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="contact_person" class="form-control" value="<?= $isEdit ? htmlspecialchars($lead->contact_person) : '' ?>" required>
                </div>
            </div>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $t['phone'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="phone" class="form-control" value="<?= $isEdit ? htmlspecialchars($lead->phone ?? '') : '' ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['email'] ?></label>
                    <input type="email" name="email" class="form-control" value="<?= $isEdit ? htmlspecialchars($lead->email ?? '') : '' ?>">
                </div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-target text-sky-600"></i> <?= $t['sales_info'] ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $t['source'] ?></label>
                    <select name="source" class="form-control">
                        <?php $src = $isEdit ? $lead->source : 'Website'; ?>
                        <option value="Website" <?= $src == 'Website' ? 'selected' : '' ?>>الموقع الإلكتروني (Website)</option>
                        <option value="Referral" <?= $src == 'Referral' ? 'selected' : '' ?>>ترشيح / وساطة (Referral)</option>
                        <option value="Social Media" <?= $src == 'Social Media' ? 'selected' : '' ?>>وسائل التواصل (Social Media)</option>
                        <option value="Cold Call" <?= $src == 'Cold Call' ? 'selected' : '' ?>>اتصال مباشر (Cold Call)</option>
                        <option value="Other" <?= $src == 'Other' ? 'selected' : '' ?>>أخرى (Other)</option>
                    </select>
                </div>
                <div>
                    <label class="input-label"><?= $t['status'] ?></label>
                    <select name="status" class="form-control">
                        <?php $st = $isEdit ? $lead->status : 'new'; ?>
                        <option value="new" <?= $st == 'new' ? 'selected' : '' ?>>جديد (New)</option>
                        <option value="contacted" <?= $st == 'contacted' ? 'selected' : '' ?>>تم التواصل (Contacted)</option>
                        <option value="qualified" <?= $st == 'qualified' ? 'selected' : '' ?>>مؤهل (Qualified)</option>
                        <option value="lost" <?= $st == 'lost' ? 'selected' : '' ?>>مرفوض / مفقود (Lost)</option>
                    </select>
                </div>
            </div>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $t['followup'] ?></label>
                    <input type="date" name="next_follow_up" class="form-control" value="<?= $isEdit ? htmlspecialchars($lead->next_follow_up ?? '') : date('Y-m-d', strtotime('+1 day')) ?>">
                </div>
                <div>
                    <label class="input-label"><?= $t['owner'] ?></label>
                    <select name="owner_id" class="form-control">
                        <option value=""><?= $t['select_owner'] ?></option>
                        <?php foreach($users ?? [] as $u): ?>
                            <option value="<?= $u->id ?>" <?= ($isEdit && $lead->owner_id == $u->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Sticky Footer -->
        <div class="sticky-footer">
            <a href="/ERP/crm/leads" class="btn" style="background: #ffffff; border: 1px solid #cbd5e1; color: #475569; text-decoration: none;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $t['save'] ?></button>
        </div>
    </form>
</div>