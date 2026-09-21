<?php
// Path: resources/views/treasury/accounts/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$isEdit = isset($account) && $account !== null && !empty($account->id);
$actionUrl = $isEdit ? "/ERP/treasury/accounts/" . (int)$account->id . "/update" : "/ERP/treasury/accounts/store";

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title_new' => 'إضافة خزينة / حساب بنكي جديد', 'title_edit' => 'تعديل بيانات الحساب / الخزينة',
        'desc' => 'تعريف بيانات الحساب المالي في الدليل وإعداد الرصيد.', 'panel_basic' => 'البيانات الأساسية',
        'code' => 'كود الحساب المالي', 'name_ar' => 'اسم الحساب / الخزينة (عربي)', 'name_en' => 'اسم الحساب (إنجليزي)',
        'bal' => "الرصيد الافتتاحي / الحالي (بـ $currency)", 'panel_status' => 'الحالة والخصائص',
        'active' => 'حالة الحساب (نشط)', 'active_desc' => 'السماح بإجراء حركات قبض وصرف نقدية على هذا الحساب في النظام.',
        'cancel' => 'إلغاء وتراجع', 'save' => 'حفظ الحساب', 'update' => 'تحديث البيانات', 'active_scope' => 'الفرع النشط:'
    ],
    'en' => [
        'title_new' => 'Add New Safe / Bank Account', 'title_edit' => 'Edit Account / Safe Details',
        'desc' => 'Define financial account data in directory and set initial balance.', 'panel_basic' => 'Basic Information',
        'code' => 'Account Code', 'name_ar' => 'Account Name (Arabic)', 'name_en' => 'Account Name (English)',
        'bal' => "Opening / Current Balance (in $currency)", 'panel_status' => 'Status & Properties',
        'active' => 'Account Status (Active)', 'active_desc' => 'Allow cash receipt and payment transactions on this account.',
        'cancel' => 'Cancel', 'save' => 'Save Account', 'update' => 'Update Data', 'active_scope' => 'Active Branch:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-acc: #0891b2; 
        --c-acc-dark: #0e7490; 
        --c-acc-light: #ecfeff;
        --c-border: #cbd5e1; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text); margin-bottom: 20px; }

    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-acc); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-acc); font-size: 1.4rem; padding: 8px; background: var(--c-acc-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    @media(max-width:768px) { .grid-2 { grid-template-columns: 1fr; } }
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 14px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--c-acc); background: #ffffff; box-shadow: 0 0 0 4px var(--c-acc-light); }

    .toggle-row { display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid var(--c-border); border-radius: 12px; background: #f8fafc; }
    .toggle-info h4 { margin: 0 0 4px 0; font-size: 0.95rem; font-weight: 800; color: var(--c-text); }
    .toggle-info p { margin: 0; font-size: 0.8rem; color: var(--c-muted); font-weight: 600; }
    
    .switch { position: relative; display: inline-block; width: 50px; height: 28px; flex-shrink: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: var(--c-acc); }
    input:checked + .slider:before { transform: translateX(22px); }
    [dir="rtl"] input:checked + .slider:before { transform: translateX(-22px); left: auto; right: 4px; }

    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-acc), var(--c-acc-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/accounts" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-acc);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-acc); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-vault"></i> <?= $t['panel_basic'] ?></h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label"><?= $t['code'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-acc-dark);" value="<?= $isEdit ? htmlspecialchars((string)($account->code ?? '')) : htmlspecialchars($autoCode) ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['name_ar'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($account->name_ar ?? '')) : '' ?>" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top: 20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['name_en'] ?></label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($account->name_en ?? '')) : '' ?>" dir="ltr">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['bal'] ?></label>
                    <input type="number" step="0.01" name="current_balance" class="form-control" style="font-family:monospace; font-weight:900; color:#059669; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars((string)($account->current_balance ?? '0.00')) : '0.00' ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-sliders"></i> <?= $t['panel_status'] ?></h3>
            <div class="toggle-row">
                <div class="toggle-info">
                    <h4><?= $t['active'] ?></h4>
                    <p><?= $t['active_desc'] ?></p>
                </div>
                <label class="switch">
                    <input type="checkbox" name="is_active" value="1" <?= (!$isEdit || !empty($account->is_active)) ? 'checked' : '' ?>>
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/treasury/accounts" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>