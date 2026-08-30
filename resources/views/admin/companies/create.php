<?php
// Path: resources/views/admin/companies/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($company) && $company !== null && !empty($company->id);
$actionUrl = $isEdit ? "/ERP/admin/companies/{$company->id}/update" : "/ERP/admin/companies/store";
?>

<style>
    :root { 
        --tc-primary: #0d9488;
        --tc-dark: #0f766e;
        --tc-light: #f0fdfa;
        --tc-border: #ccfbf1;
        --tc-text: #1e293b;
        --tc-muted: #64748b;
    }

    .form-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0; }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--tc-muted); font-size: 1.2rem; transition: 0.2s;}
    .back-btn:hover { background: var(--tc-light); color: var(--tc-primary); border-color: var(--tc-primary); }
    
    .form-section { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--tc-primary); }
    [dir="ltr"] .form-section::before { right: auto; left: 0; }

    .section-title { font-size: 1.15rem; font-weight: 800; color: var(--tc-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    @media(max-width:768px) { .grid-2 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: var(--tc-text); }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; outline:none; transition:0.2s; }
    .form-control:focus { border-color: var(--tc-primary); box-shadow: 0 0 0 3px var(--tc-light); background: #ffffff;}
    
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn-cancel { padding: 12px 28px; border: 1px solid #cbd5e1; border-radius: 10px; text-decoration: none; color: var(--tc-muted); font-weight: 800; background: #fff; transition: 0.2s; }
    .btn-cancel:hover { background: #f8fafc; color: var(--tc-text); }
    .btn-submit { background: linear-gradient(135deg, var(--tc-primary), var(--tc-dark)); color: white; border: none; padding: 12px 32px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 1rem; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25); transition: 0.2s; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(13, 148, 136, 0.35); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/admin/companies" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--tc-text); font-weight:900;"><?= $isEdit ? 'تعديل السجل التجاري' : 'إضافة شركة جديدة' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--tc-muted); font-size:0.9rem;">إدخال الهوية التجارية والبيانات الضريبية للشركة.</p>
            </div>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST" autocomplete="off">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-identification-card" style="color:var(--tc-primary);"></i> البيانات الرئيسية للشركة</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">كود التمييز <span style="color:red">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; color:var(--tc-primary); background:#e2e8f0; cursor:not-allowed;" value="<?= $isEdit ? htmlspecialchars($company->code) : htmlspecialchars($autoCode) ?>" required readonly tabindex="-1">
                </div>
                <div class="form-group">
                    <label class="input-label">الاسم التجاري <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($company->name_ar) : '' ?>" required placeholder="مثال: شركة نور للحلول التقنية">
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">الرقم الضريبي (VAT)</label>
                    <input type="text" name="tax_number" class="form-control" style="font-family:monospace;" value="<?= $isEdit ? htmlspecialchars($company->tax_number ?? '') : '' ?>" placeholder="3000XXXXXXXXX">
                </div>
                <div class="form-group">
                    <label class="input-label">السجل التجاري (CR Number)</label>
                    <input type="text" name="cr_number" class="form-control" style="font-family:monospace;" value="<?= $isEdit ? htmlspecialchars($company->cr_number ?? '') : '' ?>" placeholder="1010XXXXXX">
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">حالة التفعيل والعمل <span style="color:red">*</span></label>
                <select name="is_active" class="form-control" required>
                    <option value="1" <?= (!$isEdit || $company->is_active == 1) ? 'selected' : '' ?>>شركة نشطة وتعمل (Active)</option>
                    <option value="0" <?= ($isEdit && $company->is_active == 0) ? 'selected' : '' ?>>شركة موقوفة مؤقتاً (Inactive)</option>
                </select>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/admin/companies" class="btn-cancel">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-check-circle"></i> <?= $isEdit ? 'حفظ التعديلات' : 'إنشاء وحفظ الشركة' ?></button>
        </div>
    </form>
</div>