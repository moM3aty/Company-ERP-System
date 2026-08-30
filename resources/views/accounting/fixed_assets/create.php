<?php
// Path: resources/views/accounting/fixed_assets/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($asset) && $asset !== null && !empty($asset->id);
$actionUrl = $isEdit ? "/ERP/accounting/fixed-assets/{$asset->id}/update" : "/ERP/accounting/fixed-assets/store";
?>

<style>
    :root { 
        --c-fa: #0d9488; 
        --c-fa-dark: #0f766e; 
        --c-fa-light: #ccfbf1;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-fa); }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-fa); font-size: 1.4rem; padding: 8px; background: var(--c-fa-light); border-radius: 8px; }
    
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-3 { grid-template-columns: 1fr; } }
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.88rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 14px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-fa), var(--c-fa-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/accounting/fixed-assets" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل بيانات أصل ثابت' : 'تعريف أصل ثابت جديد' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">إدخال التكلفة التاريخية، العمر الإنتاجي، وحسابات الربط الدفتري.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-armchair"></i> البيانات العامة للأصل</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">كود الأصل <span style="color:red">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-fa-dark);" value="<?= $isEdit ? htmlspecialchars($asset->code ?? '') : '' ?>" placeholder="مثال: AST-1001" required>
                </div>
                <div class="form-group">
                    <label class="input-label">اسم الأصل (عربي) <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($asset->name_ar ?? '') : '' ?>" placeholder="مثال: خط إنتاج التعبئة رقم 1" required>
                </div>
                <div class="form-group">
                    <label class="input-label">تصنيف الأصل</label>
                    <select name="category" class="form-control">
                        <?php $cat = $asset->category ?? ''; ?>
                        <option value="general" <?= $cat==='general'?'selected':'' ?>>أصل عام (General)</option>
                        <option value="buildings" <?= $cat==='buildings'?'selected':'' ?>>مباني وعقارات</option>
                        <option value="vehicles" <?= $cat==='vehicles'?'selected':'' ?>>سيارات ووسائل نقل</option>
                        <option value="equipment" <?= $cat==='equipment'?'selected':'' ?>>آلات ומعدات</option>
                        <option value="furniture" <?= $cat==='furniture'?'selected':'' ?>>أثاث وتجهيزات</option>
                        <option value="it_hardware" <?= $cat==='it_hardware'?'selected':'' ?>>أجهزة حاسب وآليات</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-calculator"></i> بيانات الشراء وحساب الإهلاك</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">تاريخ الشراء / الإقتناء <span style="color:red">*</span></label>
                    <input type="date" name="purchase_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($asset->purchase_date ?? '') : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">تكلفة الشراء التاريخية <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0" name="purchase_cost" class="form-control" style="font-family:monospace; font-weight:800; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($asset->purchase_cost ?? '0.00') : '0.00' ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">القيمة التخريدية (الخردة)</label>
                    <input type="number" step="0.01" min="0" name="salvage_value" class="form-control" style="font-family:monospace; font-weight:800;" value="<?= $isEdit ? htmlspecialchars($asset->salvage_value ?? '0.00') : '0.00' ?>">
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">العمر الإنتاجي (بالسنوات) <span style="color:red">*</span></label>
                    <input type="number" name="useful_life_years" class="form-control" value="<?= $isEdit ? htmlspecialchars($asset->useful_life_years ?? '5') : '5' ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">طريقة الإهلاك</label>
                    <select name="depreciation_method" class="form-control">
                        <option value="straight_line">طريقة القسط الثابت (Straight Line)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">مركز التكلفة المربوط</label>
                    <select name="cost_center_id" class="form-control">
                        <option value="">-- بدون مركز تكلفة --</option>
                        <?php foreach($costCenters as $cc): ?>
                            <option value="<?= $cc->id ?>" <?= ($isEdit && ($asset->cost_center_id ?? '') == $cc->id) ? 'selected' : '' ?>><?= htmlspecialchars($cc->code) ?> - <?= htmlspecialchars($cc->name_ar) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-tree-structure"></i> شجرة الحسابات المرتبطة (الربط الدفتري)</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">حساب الأصل في الميزانية <span style="color:red">*</span></label>
                    <select name="asset_account_id" class="form-control" required>
                        <option value="">-- اختر حساب الأصل --</option>
                        <?php foreach($accounts as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && ($asset->asset_account_id ?? '') == $acc->id) ? 'selected' : '' ?>><?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">حساب مجمع الإهلاك (دائن) <span style="color:red">*</span></label>
                    <select name="acc_dep_account_id" class="form-control" required>
                        <option value="">-- اختر حساب مجمع الإهلاك --</option>
                        <?php foreach($accounts as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && ($asset->acc_dep_account_id ?? '') == $acc->id) ? 'selected' : '' ?>><?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">حساب مصروف الإهلاك (مدين) <span style="color:red">*</span></label>
                    <select name="dep_expense_account_id" class="form-control" required>
                        <option value="">-- اختر حساب مصروف الإهلاك --</option>
                        <?php foreach($accounts as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && ($asset->dep_expense_account_id ?? '') == $acc->id) ? 'selected' : '' ?>><?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/accounting/fixed-assets" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث الأصل' : 'حفظ وتسجيل الأصل' ?></button>
        </div>
    </form>
</div>