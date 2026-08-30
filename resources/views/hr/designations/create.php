<?php
// Path: resources/views/hr/designations/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($designation) && $designation !== null && !empty($designation->id);
$actionUrl = $isEdit ? "/ERP/hr/designations/{$designation->id}/update" : "/ERP/hr/designations/store";
?>

<style>
    :root { 
        --c-desig: #c026d3; 
        --c-desig-dark: #a21caf; 
        --c-desig-light: #fae8ff;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
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
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-desig), var(--c-desig-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/designations" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل المسمى الوظيفي' : 'إضافة مسمى وظيفي جديد' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">تحديد الكود، العنوان، الإدارة التابع لها والدرجة المالية.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-identification-card"></i> البيانات الرئيسية للمسمى الوظيفي</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">كود المسمى <span style="color:red">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-desig-dark);" value="<?= $isEdit ? htmlspecialchars($designation->code) : htmlspecialchars($autoCode) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="input-label">المسمى الوظيفي (عربي) <span style="color:red">*</span></label>
                    <input type="text" name="title_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($designation->title_ar) : '' ?>" placeholder="مثال: مدير الموارد البشرية / مهندس برمجيات أول..." required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">المسمى الوظيفي (إنجليزي)</label>
                    <input type="text" name="title_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($designation->title_en ?? '') : '' ?>" placeholder="e.g. HR Manager / Senior Software Engineer">
                </div>
                <div class="form-group">
                    <label class="input-label">الإدارة التابع لها</label>
                    <select name="department_id" class="form-control">
                        <option value="">-- عمومي / كل الإدارات --</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?= $dept->id ?>" <?= ($isEdit && $designation->department_id == $dept->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept->code) ?> - <?= htmlspecialchars($dept->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">الدرجة المالية (Pay Grade)</label>
                    <input type="text" name="pay_grade" class="form-control" value="<?= $isEdit ? htmlspecialchars($designation->pay_grade ?? '') : '' ?>" placeholder="مثال: Grade A1 / المستوى الأول...">
                </div>
                <div class="form-group">
                    <label class="input-label">الحالة <span style="color:red">*</span></label>
                    <select name="status" class="form-control" required>
                        <option value="active" <?= (!$isEdit || $designation->status === 'active') ? 'selected' : '' ?>>نشط ومفعل (Active)</option>
                        <option value="inactive" <?= ($isEdit && $designation->status === 'inactive') ? 'selected' : '' ?>>غير نشط (Inactive)</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">الوصف الوظيفي والمسؤوليات الرئيسية</label>
                <textarea name="description" class="form-control" rows="3" placeholder="ملخص الوصف الوظيفي والشروط..."><?= $isEdit ? htmlspecialchars($designation->description ?? '') : '' ?></textarea>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/hr/designations" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث المسمى الوظيفي' : 'حفظ وتسجيل المسمى' ?></button>
        </div>
    </form>
</div>