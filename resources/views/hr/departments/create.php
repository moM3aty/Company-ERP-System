<?php
// Path: resources/views/hr/departments/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($department) && $department !== null && !empty($department->id);
$actionUrl = $isEdit ? "/ERP/hr/departments/{$department->id}/update" : "/ERP/hr/departments/store";
?>

<style>
    :root { 
        --c-dept: #0d9488; 
        --c-dept-dark: #0f766e; 
        --c-dept-light: #ccfbf1;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
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
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-dept), var(--c-dept-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/departments" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل بيانات الإدارة' : 'إضافة إدارة / قسم جديد' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">تحديد الكود والهيكل الشجري للإدارة والمدير المسؤول.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-sitemap"></i> التعريف بالإدارة والقطاع</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">كود الإدارة <span style="color:red">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-dept-dark);" value="<?= $isEdit ? htmlspecialchars($department->code) : htmlspecialchars($autoCode) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="input-label">اسم الإدارة (عربي) <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($department->name_ar) : '' ?>" placeholder="مثال: إدارة الشؤون المالية والتحصيل..." required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">اسم الإدارة (إنجليزي)</label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($department->name_en ?? '') : '' ?>" placeholder="e.g. Finance & Accounting Department">
                </div>
                <div class="form-group">
                    <label class="input-label">الإدارة العليا التابع لها (القطاع)</label>
                    <select name="parent_id" class="form-control">
                        <option value="">-- إدارة رئيسية مستقلة --</option>
                        <?php foreach($parentDepts as $p): ?>
                            <option value="<?= $p->id ?>" <?= ($isEdit && $department->parent_id == $p->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p->code) ?> - <?= htmlspecialchars($p->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">مدير الإدارة / المسؤول</label>
                    <input type="text" name="manager_name" class="form-control" value="<?= $isEdit ? htmlspecialchars($department->manager_name ?? '') : '' ?>" placeholder="اسم مدير الإدارة...">
                </div>
                <div class="form-group">
                    <label class="input-label">حالة الإدارة <span style="color:red">*</span></label>
                    <select name="status" class="form-control" required>
                        <option value="active" <?= (!$isEdit || $department->status === 'active') ? 'selected' : '' ?>>نشط ومفعل (Active)</option>
                        <option value="inactive" <?= ($isEdit && $department->status === 'inactive') ? 'selected' : '' ?>>غير نشط (Inactive)</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">الوصف والمهام الوظيفية للإدارة</label>
                <textarea name="description" class="form-control" rows="3" placeholder="توصيف المهام والاختصاصات..."><?= $isEdit ? htmlspecialchars($department->description ?? '') : '' ?></textarea>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/hr/departments" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث بيانات الإدارة' : 'حفظ وتسجيل الإدارة' ?></button>
        </div>
    </form>
</div>