<?php
// Path: resources/views/hr/departments/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($department) && $department !== null && !empty($department->id);
$actionUrl = $isEdit ? "/ERP/hr/departments/" . (int)$department->id . "/update" : "/ERP/hr/departments/store";

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title_new' => 'إضافة إدارة / قسم جديد', 'title_edit' => 'تعديل بيانات الإدارة',
        'desc' => 'تحديد الكود والهيكل الشجري للإدارة والمدير المسؤول.', 'panel_basic' => 'التعريف بالإدارة والقطاع',
        'code' => 'كود الإدارة', 'name_ar' => 'اسم الإدارة (عربي)', 'name_en' => 'اسم الإدارة (إنجليزي)',
        'parent' => 'الإدارة العليا التابع لها (القطاع)', 'parent_null' => '-- إدارة رئيسية مستقلة --',
        'manager' => 'مدير الإدارة / المسؤول', 'manager_ph' => 'اسم مدير الإدارة...',
        'status' => 'حالة الإدارة', 'status_active' => 'نشط ومفعل (Active)', 'status_inactive' => 'غير نشط (Inactive)',
        'description' => 'الوصف والمهام الوظيفية للإدارة', 'desc_ph' => 'توصيف المهام والاختصاصات...',
        'cancel' => 'إلغاء وتراجع', 'save' => 'حفظ وتسجيل الإدارة', 'update' => 'تحديث بيانات الإدارة', 'active_scope' => 'الفرع النشط:'
    ],
    'en' => [
        'title_new' => 'Add New Department / Division', 'title_edit' => 'Edit Department Details',
        'desc' => 'Define code, organizational hierarchy, and department manager.', 'panel_basic' => 'Department Identification & Sector',
        'code' => 'Department Code', 'name_ar' => 'Department Name (Arabic)', 'name_en' => 'Department Name (English)',
        'parent' => 'Parent Division / Department', 'parent_null' => '-- Independent Main Division --',
        'manager' => 'Department Manager', 'manager_ph' => 'Name of Department Manager...',
        'status' => 'Department Status', 'status_active' => 'Active', 'status_inactive' => 'Inactive',
        'description' => 'Description & Responsibilities', 'desc_ph' => 'Department scope of work and tasks...',
        'cancel' => 'Cancel', 'save' => 'Save Department', 'update' => 'Update Department', 'active_scope' => 'Active Branch:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-dept: #0d9488; 
        --c-dept-dark: #0f766e; 
        --c-dept-light: #ccfbf1;
        --c-border: #cbd5e1; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text); margin-bottom: 20px; }

    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-dept); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-dept); font-size: 1.4rem; padding: 8px; background: var(--c-dept-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--c-dept); background: #ffffff; box-shadow: 0 0 0 4px var(--c-dept-light); }

    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-dept), var(--c-dept-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/departments" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-dept);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-dept); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-sitemap"></i> <?= $t['panel_basic'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['code'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-dept-dark);" value="<?= $isEdit ? htmlspecialchars((string)($department->code ?? '')) : htmlspecialchars((string)($autoCode ?? '')) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="input-label"><?= $t['name_ar'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($department->name_ar ?? '')) : '' ?>" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['name_en'] ?></label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($department->name_en ?? '')) : '' ?>" dir="ltr">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['parent'] ?></label>
                    <select name="parent_id" class="form-control">
                        <option value=""><?= $t['parent_null'] ?></option>
                        <?php foreach($parentDepts ?? [] as $p): 
                            $pName = $isRtl ? ($p->name_ar ?? '') : ($p->name_en ?: ($p->name_ar ?? ''));
                        ?>
                            <option value="<?= $p->id ?>" <?= ($isEdit && ($department->parent_id ?? 0) == $p->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$p->code) ?> - <?= htmlspecialchars((string)$pName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['manager'] ?></label>
                    <input type="text" name="manager_name" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($department->manager_name ?? '')) : '' ?>" placeholder="<?= $t['manager_ph'] ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['status'] ?> <span style="color:red">*</span></label>
                    <select name="status" class="form-control" required>
                        <?php $st = $isEdit ? ($department->status ?? 'active') : 'active'; ?>
                        <option value="active" <?= $st === 'active' ? 'selected' : '' ?>><?= $t['status_active'] ?></option>
                        <option value="inactive" <?= $st === 'inactive' ? 'selected' : '' ?>><?= $t['status_inactive'] ?></option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label"><?= $t['description'] ?></label>
                <textarea name="description" class="form-control" rows="3" placeholder="<?= $t['desc_ph'] ?>"><?= $isEdit ? htmlspecialchars((string)($department->description ?? '')) : '' ?></textarea>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/hr/departments" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>