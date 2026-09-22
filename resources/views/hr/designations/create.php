<?php
// Path: resources/views/hr/designations/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($designation) && $designation !== null && !empty($designation->id);
$actionUrl = $isEdit ? "/ERP/hr/designations/" . (int)$designation->id . "/update" : "/ERP/hr/designations/store";

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title_new' => 'إضافة مسمى وظيفي جديد', 'title_edit' => 'تعديل المسمى الوظيفي',
        'desc' => 'تحديد الكود، العنوان، الإدارة التابع لها والدرجة المالية.', 'panel_basic' => 'البيانات الرئيسية للمسمى الوظيفي',
        'code' => 'كود المسمى', 'title_ar' => 'المسمى الوظيفي (عربي)', 'title_en' => 'المسمى الوظيفي (إنجليزي)',
        'dept' => 'الإدارة التابع لها', 'dept_null' => '-- عمومي / كل الإدارات --',
        'pay_grade' => 'الدرجة المالية (Pay Grade)', 'pay_grade_ph' => 'مثال: Grade A1 / المستوى الأول...',
        'status' => 'الحالة', 'status_active' => 'نشط ومفعل (Active)', 'status_inactive' => 'غير نشط (Inactive)',
        'description' => 'الوصف الوظيفي والمسؤوليات الرئيسية', 'desc_ph' => 'ملخص الوصف الوظيفي والشروط...',
        'cancel' => 'إلغاء وتراجع', 'save' => 'حفظ وتسجيل المسمى', 'update' => 'تحديث المسمى الوظيفي', 'active_scope' => 'الفرع النشط:'
    ],
    'en' => [
        'title_new' => 'New Job Designation', 'title_edit' => 'Edit Designation Details',
        'desc' => 'Define designation code, title, department link, and pay grade.', 'panel_basic' => 'Main Designation Data',
        'code' => 'Designation Code', 'title_ar' => 'Job Title (Arabic)', 'title_en' => 'Job Title (English)',
        'dept' => 'Assigned Department', 'dept_null' => '-- General / All Departments --',
        'pay_grade' => 'Pay Grade', 'pay_grade_ph' => 'e.g. Grade A1 / Level 1...',
        'status' => 'Status', 'status_active' => 'Active', 'status_inactive' => 'Inactive',
        'description' => 'Job Description & Responsibilities', 'desc_ph' => 'Summary of key responsibilities and prerequisites...',
        'cancel' => 'Cancel', 'save' => 'Save Designation', 'update' => 'Update Designation', 'active_scope' => 'Active Branch:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-desig: #c026d3; 
        --c-desig-dark: #a21caf; 
        --c-desig-light: #fae8ff;
        --c-border: #cbd5e1; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text); margin-bottom: 20px; }

    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-desig); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-desig); font-size: 1.4rem; padding: 8px; background: var(--c-desig-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--c-desig); background: #ffffff; box-shadow: 0 0 0 4px var(--c-desig-light); }

    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-desig), var(--c-desig-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/designations" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-desig);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-desig); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-identification-card"></i> <?= $t['panel_basic'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['code'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-desig-dark);" value="<?= $isEdit ? htmlspecialchars((string)($designation->code ?? '')) : htmlspecialchars((string)($autoCode ?? '')) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="input-label"><?= $t['title_ar'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="title_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($designation->title_ar ?? '')) : '' ?>" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['title_en'] ?></label>
                    <input type="text" name="title_en" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($designation->title_en ?? '')) : '' ?>" dir="ltr">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['dept'] ?></label>
                    <select name="department_id" class="form-control">
                        <option value=""><?= $t['dept_null'] ?></option>
                        <?php foreach($departments ?? [] as $dept): 
                            $deptName = $isRtl ? ($dept->name_ar ?? '') : ($dept->name_en ?: ($dept->name_ar ?? ''));
                        ?>
                            <option value="<?= $dept->id ?>" <?= ($isEdit && ($designation->department_id ?? 0) == $dept->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$dept->code) ?> - <?= htmlspecialchars((string)$deptName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['pay_grade'] ?></label>
                    <input type="text" name="pay_grade" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($designation->pay_grade ?? '')) : '' ?>" placeholder="<?= $t['pay_grade_ph'] ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['status'] ?> <span style="color:red">*</span></label>
                    <select name="status" class="form-control" required>
                        <?php $st = $isEdit ? ($designation->status ?? 'active') : 'active'; ?>
                        <option value="active" <?= $st === 'active' ? 'selected' : '' ?>><?= $t['status_active'] ?></option>
                        <option value="inactive" <?= $st === 'inactive' ? 'selected' : '' ?>><?= $t['status_inactive'] ?></option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label"><?= $t['description'] ?></label>
                <textarea name="description" class="form-control" rows="3" placeholder="<?= $t['desc_ph'] ?>"><?= $isEdit ? htmlspecialchars((string)($designation->description ?? '')) : '' ?></textarea>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/hr/designations" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>