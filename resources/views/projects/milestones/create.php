<?php
// Path: resources/views/projects/milestones/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($milestone) && $milestone !== null && !empty($milestone->id);
$actionUrl = $isEdit ? "/ERP/projects/milestones/{$milestone->id}/update" : "/ERP/projects/milestones/store";
?>

<style>
    :root { 
        --c-ms: #8b5cf6; 
        --c-ms-dark: #7c3aed; 
        --c-ms-light: #f5f3ff;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-ms); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-ms); font-size: 1.4rem; padding: 8px; background: var(--c-ms-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-ms), var(--c-ms-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/milestones" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل بيانات المرحلة / المهمة' : 'تسليم وتعيين مرحلة / مهمة جديدة' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">ربط المهمة بالمشروع وتعيين المهندس والتواريخ والتقديرات التكلفية.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-flag-banner"></i> التعريف بالمرحلة والمشروع</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">كود المرحلة <span style="color:red">*</span></label>
                    <input type="text" name="milestone_code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-ms-dark);" value="<?= $isEdit ? htmlspecialchars($milestone->milestone_code) : htmlspecialchars($autoCode) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="input-label">المشروع التابع له <span style="color:red">*</span></label>
                    <select name="project_id" class="form-control" required>
                        <option value="">-- اختر المشروع --</option>
                        <?php foreach($projects as $p): ?>
                            <option value="<?= $p->id ?>" <?= ($isEdit && $milestone->project_id == $p->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p->code) ?> - <?= htmlspecialchars($p->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">عنوان المرحلة/المهمة (عربي) <span style="color:red">*</span></label>
                    <input type="text" name="title_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($milestone->title_ar) : '' ?>" placeholder="مثال: مرحلة حفر وتجهيز القواعد والأساسات..." required>
                </div>
                <div class="form-group">
                    <label class="input-label">عنوان المرحلة/المهمة (إنجليزي)</label>
                    <input type="text" name="title_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($milestone->title_en ?? '') : '' ?>" placeholder="e.g. Foundation & Excavation Stage">
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">المهندس / المسؤول الفني عن التنفيذ</label>
                <input type="text" name="assigned_to" class="form-control" value="<?= $isEdit ? htmlspecialchars($milestone->assigned_to ?? '') : '' ?>" placeholder="اسم المهندس المشرف أو المقاول الفرعي...">
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-calendar"></i> الجدول الزمني والتكاليف</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">تاريخ بدء التنفيذ <span style="color:red">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($milestone->start_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ الاستحقاق المتوقع <span style="color:red">*</span></label>
                    <input type="date" name="due_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($milestone->due_date) : date('Y-m-d', strtotime('+30 days')) ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ الإنجاز الفعلي</label>
                    <input type="date" name="completion_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($milestone->completion_date ?? '') : '' ?>">
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">نسبة الإنجاز الفعلية (%)</label>
                    <input type="number" step="0.1" min="0" max="100" name="progress_percent" class="form-control" style="font-family:monospace; font-weight:900; color:var(--c-ms-dark); font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($milestone->progress_percent) : '0' ?>" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="input-label">التكلفة التقديرية للمرحلة</label>
                    <input type="number" step="0.01" min="0" name="estimated_cost" class="form-control" style="font-family:monospace; font-weight:900; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($milestone->estimated_cost) : '0.00' ?>" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="input-label">التكلفة الفعلية المنصرفة</label>
                    <input type="number" step="0.01" min="0" name="actual_cost" class="form-control" style="font-family:monospace; font-weight:900; color:#dc2626; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($milestone->actual_cost) : '0.00' ?>" placeholder="0.00">
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">حالة المرحلة <span style="color:red">*</span></label>
                    <select name="status" class="form-control" required>
                        <option value="pending" <?= ($isEdit && $milestone->status === 'pending') ? 'selected' : '' ?>>قيد الانتظار (Pending)</option>
                        <option value="in_progress" <?= (!$isEdit || $milestone->status === 'in_progress') ? 'selected' : '' ?>>قيد التنفيذ (In Progress)</option>
                        <option value="under_review" <?= ($isEdit && $milestone->status === 'under_review') ? 'selected' : '' ?>>قيد المراجعة والاستلام (Under Review)</option>
                        <option value="completed" <?= ($isEdit && $milestone->status === 'completed') ? 'selected' : '' ?>>مكتملة ومستلمة (Completed)</option>
                        <option value="delayed" <?= ($isEdit && $milestone->status === 'delayed') ? 'selected' : '' ?>>متأخرة (Delayed)</option>
                        <option value="cancelled" <?= ($isEdit && $milestone->status === 'cancelled') ? 'selected' : '' ?>>ملغاة (Cancelled)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">درجة الأولوية <span style="color:red">*</span></label>
                    <select name="priority" class="form-control" required>
                        <option value="low" <?= ($isEdit && $milestone->priority === 'low') ? 'selected' : '' ?>>منخفضة (Low)</option>
                        <option value="medium" <?= (!$isEdit || $milestone->priority === 'medium') ? 'selected' : '' ?>>متوسطة (Medium)</option>
                        <option value="high" <?= ($isEdit && $milestone->priority === 'high') ? 'selected' : '' ?>>عالية (High)</option>
                        <option value="urgent" <?= ($isEdit && $milestone->priority === 'urgent') ? 'selected' : '' ?>>طائفة / عاجلة جداً (Urgent)</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">تفاصيل والمواصفات الفنية للمرحلة</label>
                <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars($milestone->description ?? '') : '' ?>" placeholder="ملاحظات المواصفات وشروط استلام المهندس المشرف...">
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/projects/milestones" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث بيانات المرحلة' : 'حفظ وتسجيل المهمة' ?></button>
        </div>
    </form>
</div>