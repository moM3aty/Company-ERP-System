<?php
// Path: resources/views/projects/milestones/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();
$isEdit = isset($milestone) && $milestone !== null && !empty($milestone->id);
$actionUrl = $isEdit ? "/ERP/projects/milestones/{$milestone->id}/update" : "/ERP/projects/milestones/store";

$t = [
    'ar' => [
        'title_new' => 'تعيين مرحلة / مهمة جديدة', 'title_edit' => 'تعديل بيانات المرحلة / المهمة', 'desc' => 'ربط المهمة بالمشروع وتعيين المهندس والتواريخ والتقديرات التكلفية.',
        'sec_basic' => 'التعريف بالمرحلة والمشروع', 'code' => 'كود المرحلة', 'project' => 'المشروع التابع له', 'select_proj' => '-- اختر المشروع --',
        'title_ar' => 'عنوان المرحلة/المهمة (عربي)', 'title_en' => 'عنوان المرحلة/المهمة (إنجليزي)', 'assigned' => 'المهندس / المسؤول الفني', 'assigned_ph' => 'اسم المهندس المشرف...',
        'sec_time' => 'الجدول الزمني والتكاليف', 'start' => 'تاريخ بدء التنفيذ', 'due' => 'تاريخ الاستحقاق المتوقع', 'comp' => 'تاريخ الإنجاز الفعلي',
        'prog' => 'نسبة الإنجاز الفعلية (%)', 'est_cost' => 'التكلفة التقديرية', 'act_cost' => 'التكلفة الفعلية المنصرفة',
        'status' => 'حالة المرحلة', 'priority' => 'درجة الأولوية', 'desc_label' => 'تفاصيل والمواصفات الفنية', 'desc_ph' => 'ملاحظات المواصفات وشروط استلام المهندس المشرف...',
        'cancel' => 'إلغاء وتراجع', 'save' => 'حفظ وتسجيل المهمة', 'update' => 'تحديث بيانات المرحلة',
        'st_pending' => 'قيد الانتظار', 'st_in_progress' => 'قيد التنفيذ', 'st_under_review' => 'قيد المراجعة', 'st_completed' => 'مكتملة ومستلمة', 'st_delayed' => 'متأخرة', 'st_cancelled' => 'ملغاة',
        'pr_low' => 'منخفضة', 'pr_medium' => 'متوسطة', 'pr_high' => 'عالية', 'pr_urgent' => 'طائفة / عاجلة جداً'
    ],
    'en' => [
        'title_new' => 'Assign New Milestone / Task', 'title_edit' => 'Edit Milestone Data', 'desc' => 'Link task to a project, assign engineer, set dates and budgets.',
        'sec_basic' => 'Milestone & Project Definition', 'code' => 'Milestone Code', 'project' => 'Linked Project', 'select_proj' => '-- Select Project --',
        'title_ar' => 'Task Title (AR)', 'title_en' => 'Task Title (EN)', 'assigned' => 'Assigned Engineer / Tech', 'assigned_ph' => 'Supervisor engineer name...',
        'sec_time' => 'Timeline & Costs', 'start' => 'Execution Start Date', 'due' => 'Expected Due Date', 'comp' => 'Actual Completion Date',
        'prog' => 'Actual Progress (%)', 'est_cost' => 'Estimated Cost', 'act_cost' => 'Actual Spent Cost',
        'status' => 'Milestone Status', 'priority' => 'Priority Level', 'desc_label' => 'Technical Specs & Details', 'desc_ph' => 'Notes on specs and handover terms...',
        'cancel' => 'Cancel', 'save' => 'Save Task', 'update' => 'Update Milestone Data',
        'st_pending' => 'Pending', 'st_in_progress' => 'In Progress', 'st_under_review' => 'Under Review', 'st_completed' => 'Completed', 'st_delayed' => 'Delayed', 'st_cancelled' => 'Cancelled',
        'pr_low' => 'Low', 'pr_medium' => 'Medium', 'pr_high' => 'High', 'pr_urgent' => 'Urgent'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { --c-ms: #8b5cf6; --c-ms-dark: #7c3aed; --c-ms-light: #f5f3ff; --c-border: #e2e8f0; --c-text: #0f172a; --c-bg: #f8fafc; }
    .form-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #64748b; font-size: 1.2rem; transition: 0.2s;}
    .back-btn:hover { background: var(--c-ms-light); color: var(--c-ms); border-color: #ddd6fe; }

    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; border-top: 4px solid var(--c-ms); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; display: flex; align-items: center; gap: 8px;}
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; background: var(--c-bg); font-weight: 600; font-family:inherit; transition: 0.2s;}
    .form-control:focus { border-color: var(--c-ms); box-shadow: 0 0 0 4px var(--c-ms-light); outline: none; background: #fff;}
    
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn-submit { background: linear-gradient(135deg, var(--c-ms), var(--c-ms-dark)); color: white; border: none; padding: 12px 28px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: #475569; padding: 12px 28px; border-radius: 10px; font-weight: 800; text-decoration: none; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/milestones" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
                <p style="margin:4px 0 0 0; color:#64748b; font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-flag-banner" style="color:var(--c-ms);"></i> <?= $t['sec_basic'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['code'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="milestone_code" class="form-control" style="font-family:monospace; color:var(--c-ms-dark); font-weight:900;" value="<?= $isEdit ? htmlspecialchars($milestone->milestone_code) : htmlspecialchars($autoCode) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="input-label"><?= $t['project'] ?> <span style="color:red">*</span></label>
                    <select name="project_id" class="form-control" required>
                        <option value=""><?= $t['select_proj'] ?></option>
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
                    <label class="input-label"><?= $t['title_ar'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="title_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($milestone->title_ar) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['title_en'] ?></label>
                    <input type="text" name="title_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($milestone->title_en ?? '') : '' ?>">
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label"><?= $t['assigned'] ?></label>
                <input type="text" name="assigned_to" class="form-control" value="<?= $isEdit ? htmlspecialchars($milestone->assigned_to ?? '') : '' ?>" placeholder="<?= $t['assigned_ph'] ?>">
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-calendar" style="color:var(--c-ms);"></i> <?= $t['sec_time'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['start'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($milestone->start_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['due'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="due_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($milestone->due_date) : date('Y-m-d', strtotime('+30 days')) ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['comp'] ?></label>
                    <input type="date" name="completion_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($milestone->completion_date ?? '') : '' ?>">
                </div>
            </div>

            <div class="grid-3" style="margin-top:24px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['prog'] ?></label>
                    <input type="number" step="0.1" min="0" max="100" name="progress_percent" class="form-control" style="font-family:monospace; font-weight:900; color:var(--c-ms-dark); font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($milestone->progress_percent) : '0' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['est_cost'] ?> (<?= $currency ?>)</label>
                    <input type="number" step="0.01" min="0" name="estimated_cost" class="form-control" style="font-family:monospace; font-weight:900; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($milestone->estimated_cost) : '0.00' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['act_cost'] ?> (<?= $currency ?>)</label>
                    <input type="number" step="0.01" min="0" name="actual_cost" class="form-control" style="font-family:monospace; font-weight:900; color:#dc2626; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($milestone->actual_cost) : '0.00' ?>">
                </div>
            </div>

            <div class="grid-2" style="margin-top:24px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['status'] ?> <span style="color:red">*</span></label>
                    <select name="status" class="form-control" style="font-weight:800;" required>
                        <?php $st = $isEdit ? $milestone->status : 'pending'; ?>
                        <option value="pending" <?= $st === 'pending' ? 'selected' : '' ?>><?= $t['st_pending'] ?></option>
                        <option value="in_progress" <?= $st === 'in_progress' ? 'selected' : '' ?>><?= $t['st_in_progress'] ?></option>
                        <option value="under_review" <?= $st === 'under_review' ? 'selected' : '' ?>><?= $t['st_under_review'] ?></option>
                        <option value="completed" <?= $st === 'completed' ? 'selected' : '' ?>><?= $t['st_completed'] ?></option>
                        <option value="delayed" <?= $st === 'delayed' ? 'selected' : '' ?>><?= $t['st_delayed'] ?></option>
                        <option value="cancelled" <?= $st === 'cancelled' ? 'selected' : '' ?>><?= $t['st_cancelled'] ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['priority'] ?> <span style="color:red">*</span></label>
                    <select name="priority" class="form-control" style="font-weight:800;" required>
                        <?php $pr = $isEdit ? $milestone->priority : 'medium'; ?>
                        <option value="low" <?= $pr === 'low' ? 'selected' : '' ?>><?= $t['pr_low'] ?></option>
                        <option value="medium" <?= $pr === 'medium' ? 'selected' : '' ?>><?= $t['pr_medium'] ?></option>
                        <option value="high" <?= $pr === 'high' ? 'selected' : '' ?>><?= $t['pr_high'] ?></option>
                        <option value="urgent" <?= $pr === 'urgent' ? 'selected' : '' ?>><?= $t['pr_urgent'] ?></option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top:24px;">
                <label class="input-label"><?= $t['desc_label'] ?></label>
                <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars($milestone->description ?? '') : '' ?>" placeholder="<?= $t['desc_ph'] ?>">
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/projects/milestones" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>