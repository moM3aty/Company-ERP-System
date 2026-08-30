<?php
// Path: resources/views/accounting/accounts/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($account) && $account !== null && !empty($account->id);
$actionUrl = $isEdit ? "/ERP/accounting/chart-of-accounts/{$account->id}/update" : "/ERP/accounting/chart-of-accounts/store";
?>

<style>
    :root { 
        --c-acc: #059669; 
        --c-acc-dark: #047857; 
        --c-acc-light: #ecfdf5;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    /* Header */
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .header-title-box { display: flex; align-items: center; gap: 16px; }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .back-btn:hover { border-color: var(--c-acc); color: var(--c-acc); transform: translateX(<?= $isRtl ? '4px' : '-4px' ?>); }
    
    /* Sections */
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-acc); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }
    
    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-acc); font-size: 1.4rem; padding: 8px; background: var(--c-acc-light); border-radius: 8px; }
    
    /* Inputs */
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    @media(max-width:768px) { .grid-2 { grid-template-columns: 1fr; } }
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; display: flex; justify-content: space-between; }
    .form-control { width: 100%; padding: 14px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; transition: 0.2s; color: var(--c-text); font-weight: 600; }
    .form-control:focus { outline: none; border-color: var(--c-acc); background: #ffffff; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); }
    
    /* Toggle Switch */
    .toggle-row { display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid var(--c-border); border-radius: 12px; background: #f8fafc; }
    .toggle-info h4 { margin: 0 0 4px 0; font-size: 0.95rem; font-weight: 800; color: var(--c-text); }
    .toggle-info p { margin: 0; font-size: 0.8rem; color: var(--c-muted); font-weight: 600; }
    
    .switch { position: relative; display: inline-block; width: 50px; height: 28px; flex-shrink: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
    input:checked + .slider { background-color: var(--c-acc); }
    input:checked + .slider:before { transform: translateX(22px); }
    [dir="rtl"] input:checked + .slider:before { transform: translateX(-22px); left: auto; right: 4px; }

    /* Buttons */
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-cancel { padding: 14px 28px; border: 1px solid var(--c-border); border-radius: 12px; text-decoration: none; color: #475569; font-weight: 800; background: #ffffff; transition: 0.2s; }
    .btn-cancel:hover { background: #f1f5f9; }
    .btn-submit { background: linear-gradient(135deg, var(--c-acc), var(--c-acc-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; font-size: 1rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2); transition: 0.2s; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(5, 150, 105, 0.3); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div class="header-title-box">
            <a href="/ERP/accounting/chart-of-accounts" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل بيانات الحساب' : 'إنشاء حساب مالي جديد' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem; font-weight:600;">يرجى إدخال البيانات المطلوبة لتعريف الحساب في الدليل.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <!-- القسم الأول: البيانات الأساسية -->
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-info"></i> البيانات الأساسية للحساب</h3>
            
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">رمز الحساب (الكود) <span style="color:red">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; font-size:1.1rem; color:var(--c-acc-dark);" value="<?= $isEdit ? htmlspecialchars($account->code ?? '') : '' ?>" placeholder="مثال: 10101" required>
                </div>
                <div class="form-group">
                    <label class="input-label">طبيعة الحساب (النوع) <span style="color:red">*</span></label>
                    <select name="type" class="form-control" required>
                        <?php $t = $isEdit ? ($account->type ?? '') : ''; ?>
                        <option value="" disabled <?= !$isEdit ? 'selected' : '' ?>>-- اختر نوع الحساب --</option>
                        <option value="asset" <?= $t==='asset'?'selected':'' ?>>أصول (Assets) - مدين بطبيعته</option>
                        <option value="liability" <?= $t==='liability'?'selected':'' ?>>التزامات (Liabilities) - دائن بطبيعته</option>
                        <option value="equity" <?= $t==='equity'?'selected':'' ?>>حقوق ملكية (Equity) - دائن بطبيعته</option>
                        <option value="revenue" <?= $t==='revenue'?'selected':'' ?>>إيرادات (Revenues) - دائن بطبيعته</option>
                        <option value="expense" <?= $t==='expense'?'selected':'' ?>>مصروفات (Expenses) - مدين بطبيعته</option>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top: 24px;">
                <div class="form-group">
                    <label class="input-label">اسم الحساب (باللغة العربية) <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($account->name_ar ?? '') : '' ?>" placeholder="مثال: الصندوق الرئيسي" required>
                </div>
                <div class="form-group">
                    <label class="input-label">اسم الحساب (باللغة الإنجليزية) <span>اختياري</span></label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($account->name_en ?? '') : '' ?>" placeholder="e.g. Main Cash Account">
                </div>
            </div>
        </div>

        <!-- القسم الثاني: الهيكلة والرصيد الافتتاحي -->
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-tree-structure"></i> الهيكلة المالية والرصيد</h3>
            
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">الحساب الرئيسي (الأب)</label>
                    <select name="parent_id" class="form-control">
                        <option value="">-- حساب رئيسي مستقل (لا يتبع لحساب آخر) --</option>
                        <?php foreach($parentAccounts ?? [] as $pa): ?>
                            <option value="<?= $pa->id ?>" <?= ($isEdit && ($account->parent_id ?? '') == $pa->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pa->code) ?> - <?= htmlspecialchars($pa->name_ar) ?> (<?= htmlspecialchars($pa->type) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">الرصيد الافتتاحي</label>
                    <input type="number" step="0.01" name="opening_balance" class="form-control" style="font-family:monospace; font-weight:900; font-size:1.1rem;" value="<?= $isEdit ? ($account->opening_balance ?? '0.00') : '0.00' ?>" <?= $isEdit ? 'readonly title="لا يمكن تعديل الرصيد الافتتاحي بعد إنشاء الحساب"' : '' ?>>
                </div>
            </div>
        </div>

        <!-- القسم الثالث: الإعدادات والخيارات -->
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-sliders"></i> خصائص الحساب</h3>
            
            <div class="grid-2">
                <!-- Toggle 1 -->
                <div class="toggle-row">
                    <div class="toggle-info">
                        <h4>حساب تجميعي (رئيسي)</h4>
                        <p>تفعيل هذا الخيار يمنع تسجيل قيود يومية مباشرة على الحساب، ويجعله مخصصاً لتجميع أرصدة الحسابات الفرعية.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="is_parent" value="1" <?= ($isEdit && !empty($account->is_parent)) ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                
                <!-- Toggle 2 -->
                <div class="toggle-row">
                    <div class="toggle-info">
                        <h4>حالة الحساب (نشط)</h4>
                        <p>السماح باستخدام هذا الحساب في العمليات المالية وإصدار الفواتير والقيود.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="is_active" value="1" <?= (!$isEdit || !empty($account->is_active)) ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/accounting/chart-of-accounts" class="btn-cancel">إلغاء وتراجع</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث وحفظ التعديلات' : 'اعتماد وإنشاء الحساب' ?></button>
        </div>
    </form>
</div>