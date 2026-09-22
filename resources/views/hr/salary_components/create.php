<?php
// Path: resources/views/hr/salary_components/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$isEdit = isset($component) && $component !== null && !empty($component->id);
$actionUrl = $isEdit ? "/ERP/hr/salary-components/" . (int)$component->id . "/update" : "/ERP/hr/salary-components/store";

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title_new' => 'إضافة بدل أو استقطاع جديد', 'title_edit' => 'تعديل مفرد الراتب',
        'desc' => 'ربط الموظف بمفرد راتب مخصص ومبلغه المحدد.', 'panel_basic' => 'تفاصيل المرفق/المفرد',
        'emp' => 'الموظف المعني', 'emp_null' => '-- اختر الموظف --',
        'type' => 'نوع المفرد', 'type_allowance' => 'بدل / إضافة (Allowance)', 'type_deduction' => 'استقطاع / خصم (Deduction)',
        'name' => 'اسم البدل / المفرد', 'name_ph' => 'مثال: بدل طبيعة عمل / خصم تأخيرات...',
        'amount' => "المبلغ الشهري (بـ $currency)", 'fixed' => 'خاصية المفرد',
        'fixed_yes' => 'عنصر ثابت شهرياً (Fixed Allowance/Deduction)', 'fixed_no' => 'عنصر متغير / مؤقت (Variable)',
        'cancel' => 'إلغاء وتراجع', 'save' => 'حفظ وتسجيل المفرد', 'update' => 'تحديث المفرد', 'active_scope' => 'الفرع النشط:'
    ],
    'en' => [
        'title_new' => 'Add New Component / Allowance', 'title_edit' => 'Edit Salary Component',
        'desc' => 'Assign specific allowances or deductions to employees.', 'panel_basic' => 'Salary Component Details',
        'emp' => 'Employee Name', 'emp_null' => '-- Select Employee --',
        'type' => 'Component Type', 'type_allowance' => 'Allowance / Addition', 'type_deduction' => 'Deduction / Cut',
        'name' => 'Component Name', 'name_ph' => 'e.g. Field Allowance / Late Deduction...',
        'amount' => "Monthly Amount (in $currency)", 'fixed' => 'Property',
        'fixed_yes' => 'Fixed Monthly Element', 'fixed_no' => 'Variable Element',
        'cancel' => 'Cancel', 'save' => 'Save Component', 'update' => 'Update Component', 'active_scope' => 'Active Branch:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-comp: #4338ca; 
        --c-comp-dark: #312e81; 
        --c-comp-light: #e0e7ff;
        --c-border: #cbd5e1; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text); margin-bottom: 20px; }

    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-comp); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-comp); font-size: 1.4rem; padding: 8px; background: var(--c-comp-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    @media(max-width:768px) { .grid-2 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--c-comp); background: #ffffff; box-shadow: 0 0 0 4px var(--c-comp-light); }

    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-comp), var(--c-comp-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/salary-components" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-comp);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-comp); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-sliders-horizontal"></i> <?= $t['panel_basic'] ?></h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label"><?= $t['emp'] ?> <span style="color:red">*</span></label>
                    <select name="employee_id" class="form-control" required>
                        <option value=""><?= $t['emp_null'] ?></option>
                        <?php foreach($employees ?? [] as $emp): 
                            $empName = $isRtl ? ($emp->name_ar ?? '') : ($emp->name_en ?: ($emp->name_ar ?? ''));
                        ?>
                            <option value="<?= $emp->id ?>" <?= ($isEdit && ($component->employee_id ?? 0) == $emp->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$emp->emp_code) ?> - <?= htmlspecialchars((string)$empName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['type'] ?> <span style="color:red">*</span></label>
                    <select name="type" class="form-control" required>
                        <?php $tp = $isEdit ? ($component->type ?? 'allowance') : 'allowance'; ?>
                        <option value="allowance" <?= $tp === 'allowance' ? 'selected' : '' ?>><?= $t['type_allowance'] ?></option>
                        <option value="deduction" <?= $tp === 'deduction' ? 'selected' : '' ?>><?= $t['type_deduction'] ?></option>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['name'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($component->name_ar ?? '')) : '' ?>" placeholder="<?= $t['name_ph'] ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['amount'] ?> <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0" name="amount" class="form-control" style="font-family:monospace; font-weight:900;" value="<?= $isEdit ? htmlspecialchars((string)($component->amount ?? '0.00')) : '0.00' ?>" placeholder="0.00" required>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label"><?= $t['fixed'] ?> <span style="color:red">*</span></label>
                <select name="is_fixed" class="form-control" required>
                    <?php $fx = $isEdit ? ($component->is_fixed ?? 1) : 1; ?>
                    <option value="1" <?= $fx == 1 ? 'selected' : '' ?>><?= $t['fixed_yes'] ?></option>
                    <option value="0" <?= $fx == 0 ? 'selected' : '' ?>><?= $t['fixed_no'] ?></option>
                </select>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/hr/salary-components" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>