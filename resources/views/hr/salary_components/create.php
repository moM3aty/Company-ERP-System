<?php
// Path: resources/views/hr/salary_components/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($component) && $component !== null && !empty($component->id);
$actionUrl = $isEdit ? "/ERP/hr/salary-components/{$component->id}/update" : "/ERP/hr/salary-components/store";
?>

<style>
    :root { 
        --c-comp: #4338ca; 
        --c-comp-dark: #312e81; 
        --c-comp-light: #e0e7ff;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-comp); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-comp); font-size: 1.4rem; padding: 8px; background: var(--c-comp-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    @media(max-width:768px) { .grid-2 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-comp), var(--c-comp-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/salary-components" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل مفرد الراتب' : 'إضافة بدل أو استقطاع جديد' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">ربط الموظف بمفرد راتب مخصص ومبلغه المحدد.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-sliders-horizontal"></i> تفاصيل المرفق/المفرد</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">الموظف المعني <span style="color:red">*</span></label>
                    <select name="employee_id" class="form-control" required>
                        <option value="">-- اختر الموظف --</option>
                        <?php foreach($employees as $emp): ?>
                            <option value="<?= $emp->id ?>" <?= ($isEdit && $component->employee_id == $emp->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp->emp_code) ?> - <?= htmlspecialchars($emp->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">نوع المفرد <span style="color:red">*</span></label>
                    <select name="type" class="form-control" required>
                        <option value="allowance" <?= (!$isEdit || $component->type === 'allowance') ? 'selected' : '' ?>>بدل / إضافة (Allowance)</option>
                        <option value="deduction" <?= ($isEdit && $component->type === 'deduction') ? 'selected' : '' ?>>استقطاع / خصم (Deduction)</option>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">اسم البدل / المفرد <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($component->name_ar) : '' ?>" placeholder="مثال: بدل طبيعة عمل / خصم تأخيرات..." required>
                </div>
                <div class="form-group">
                    <label class="input-label">المبلغ الشهري <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0" name="amount" class="form-control" style="font-family:monospace; font-weight:900;" value="<?= $isEdit ? htmlspecialchars($component->amount) : '0.00' ?>" placeholder="0.00" required>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">خاصية المفرد <span style="color:red">*</span></label>
                <select name="is_fixed" class="form-control" required>
                    <option value="1" <?= (!$isEdit || $component->is_fixed == 1) ? 'selected' : '' ?>>عنصر ثابت شهرياً (Fixed Allowance/Deduction)</option>
                    <option value="0" <?= ($isEdit && $component->is_fixed == 0) ? 'selected' : '' ?>>عنصر متغير / مؤقت (Variable)</option>
                </select>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/hr/salary-components" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث المفرد' : 'حفظ وتسجيل المفرد' ?></button>
        </div>
    </form>
</div>