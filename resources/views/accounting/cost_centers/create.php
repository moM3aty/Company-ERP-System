<?php
// Path: resources/views/accounting/cost_centers/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($center) && $center !== null && !empty($center->id);
$actionUrl = $isEdit ? "/ERP/accounting/cost-centers/{$center->id}/update" : "/ERP/accounting/cost-centers/store";
?>

<style>
    :root { 
        --c-cc: #7c3aed; 
        --c-cc-dark: #6d28d9; 
        --c-cc-light: #f5f3ff;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-cc); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-cc); font-size: 1.4rem; padding: 8px; background: var(--c-cc-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    @media(max-width:768px) { .grid-2 { grid-template-columns: 1fr; } }
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 14px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .toggle-row { display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid var(--c-border); border-radius: 12px; background: #f8fafc; }
    .toggle-info h4 { margin: 0 0 4px 0; font-size: 0.95rem; font-weight: 800; color: var(--c-text); }
    .toggle-info p { margin: 0; font-size: 0.8rem; color: var(--c-muted); font-weight: 600; }
    
    .switch { position: relative; display: inline-block; width: 50px; height: 28px; flex-shrink: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: var(--c-cc); }
    input:checked + .slider:before { transform: translateX(22px); }
    [dir="rtl"] input:checked + .slider:before { transform: translateX(-22px); left: auto; right: 4px; }

    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-cc), var(--c-cc-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/accounting/cost-centers" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل مركز التكلفة' : 'إضافة مركز تكلفة جديد' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">تعريف بيانات ووظيفة مركز التكلفة والموازنة المخصصة له.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-info"></i> البيانات التعريفية</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">رمز الكود المالي <span style="color:red">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-cc-dark);" value="<?= $isEdit ? htmlspecialchars($center->code ?? '') : '' ?>" placeholder="مثال: CC-101" required>
                </div>
                <div class="form-group">
                    <label class="input-label">يندرج تحت مركز رئيسي (Parent)</label>
                    <select name="parent_id" class="form-control">
                        <option value="">-- مركز تكلفة رئيسي مستقل --</option>
                        <?php foreach($parentCenters ?? [] as $pc): ?>
                            <option value="<?= $pc->id ?>" <?= ($isEdit && ($center->parent_id ?? '') == $pc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pc->code) ?> - <?= htmlspecialchars($pc->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top: 20px;">
                <div class="form-group">
                    <label class="input-label">اسم مركز التكلفة (عربي) <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($center->name_ar ?? '') : '' ?>" placeholder="مثال: فرع القاهرة / مشروع العاصمة" required>
                </div>
                <div class="form-group">
                    <label class="input-label">اسم مركز التكلفة (إنجليزي)</label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($center->name_en ?? '') : '' ?>" placeholder="e.g. Cairo Branch / Capital Project">
                </div>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label class="input-label">الموازنة التقديرية المخصصة (Budget Target)</label>
                <input type="number" step="0.01" min="0" name="budget_amount" class="form-control" style="font-family:monospace; font-weight:800; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($center->budget_amount ?? '0.00') : '0.00' ?>" placeholder="0.00">
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-sliders"></i> خصائص وإعدادات المركز</h3>
            <div class="grid-2">
                <div class="toggle-row">
                    <div class="toggle-info">
                        <h4>مركز تجميعي (رئيسي)</h4>
                        <p>تفعيل هذا الخيار يعني أن هذا المركز يحتوي على مراكز فرعية تحته لتجميع التكاليف.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="is_parent" value="1" <?= ($isEdit && !empty($center->is_parent)) ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="toggle-row">
                    <div class="toggle-info">
                        <h4>حالة المركز (نشط)</h4>
                        <p>السماح بربط التكاليف والفواتير بمركز التكلفة هذا في النظام.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="is_active" value="1" <?= (!$isEdit || !empty($center->is_active)) ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/accounting/cost-centers" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث البيانات' : 'حفظ مركز التكلفة' ?></button>
        </div>
    </form>
</div>