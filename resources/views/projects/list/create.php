<?php
// Path: resources/views/projects/list/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();
$isEdit = isset($project) && $project !== null && !empty($project->id);
$actionUrl = $isEdit ? "/ERP/projects/list/{$project->id}/update" : "/ERP/projects/list/store";

$t = [
    'ar' => [
        'title_new' => 'تعريف مشروع جديد', 'title_edit' => 'تعديل بيانات المشروع', 'desc' => 'إدخال تفاصيل العقد والعميل والميزانية المقدرة للمشروع.',
        'sec_basic' => 'البيانات الأساسية للمشروع', 'code' => 'كود المشروع', 'name_ar' => 'اسم المشروع (عربي)', 'name_en' => 'اسم المشروع (إنجليزي)', 'customer' => 'العميل المالك', 'select_cust' => '-- اختر العميل --',
        'sec_fin' => 'القيمة المالية والجدول الزمني', 'val' => 'قيمة العقد', 'budget' => 'الميزانية المقدرة', 'prog' => 'نسبة الإنجاز (%)',
        'start' => 'تاريخ البداية', 'end' => 'تاريخ التسليم', 'status' => 'حالة المشروع',
        'desc_label' => 'الوصف ونطاق العمل', 'cancel' => 'إلغاء وتراجع', 'save' => 'حفظ وتسجيل المشروع', 'update' => 'تحديث بيانات المشروع',
        'st_planning' => 'تخطيط وتجهيز (Planning)', 'st_in_progress' => 'قيد التنفيذ (In Progress)', 'st_on_hold' => 'موقف مؤقتاً (On Hold)', 'st_completed' => 'مكتمل (Completed)', 'st_cancelled' => 'ملغى (Cancelled)'
    ],
    'en' => [
        'title_new' => 'Define New Project', 'title_edit' => 'Edit Project Data', 'desc' => 'Enter project contract details, client, and estimated budget.',
        'sec_basic' => 'Basic Project Data', 'code' => 'Project Code', 'name_ar' => 'Project Name (AR)', 'name_en' => 'Project Name (EN)', 'customer' => 'Client / Owner', 'select_cust' => '-- Select Client --',
        'sec_fin' => 'Financials & Timeline', 'val' => 'Contract Value', 'budget' => 'Estimated Budget', 'prog' => 'Progress (%)',
        'start' => 'Start Date', 'end' => 'Expected End Date', 'status' => 'Project Status',
        'desc_label' => 'Scope & Description', 'cancel' => 'Cancel', 'save' => 'Save Project', 'update' => 'Update Project Data',
        'st_planning' => 'Planning', 'st_in_progress' => 'In Progress', 'st_on_hold' => 'On Hold', 'st_completed' => 'Completed', 'st_cancelled' => 'Cancelled'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { --c-prj: #0284c7; --c-prj-dark: #0369a1; --c-prj-light: #e0f2fe; --c-border: #cbd5e1; --c-text: #0f172a; --c-bg: #f8fafc; }
    .form-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #64748b; font-size: 1.2rem; transition: 0.2s;}
    .back-btn:hover { background: var(--c-prj-light); color: var(--c-prj); border-color: #bae6fd; }

    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; border-top: 4px solid var(--c-prj); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; display: flex; align-items: center; gap: 8px;}
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; background: var(--c-bg); font-weight: 600; font-family:inherit; transition: 0.2s;}
    .form-control:focus { border-color: var(--c-prj); box-shadow: 0 0 0 4px var(--c-prj-light); outline: none; background: #fff;}
    
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn-submit { background: linear-gradient(135deg, var(--c-prj), var(--c-prj-dark)); color: white; border: none; padding: 12px 28px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: #475569; padding: 12px 28px; border-radius: 10px; font-weight: 800; text-decoration: none; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/list" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
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
            <h3 class="section-title"><i class="ph-duotone ph-buildings" style="color:var(--c-prj);"></i> <?= $t['sec_basic'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['code'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; color:var(--c-prj-dark); font-weight:900;" value="<?= $isEdit ? htmlspecialchars($project->code) : htmlspecialchars($autoCode) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="input-label"><?= $t['name_ar'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($project->name_ar) : '' ?>" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['name_en'] ?></label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($project->name_en ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['customer'] ?></label>
                    <select name="customer_id" class="form-control">
                        <option value=""><?= $t['select_cust'] ?></option>
                        <?php foreach($customers as $c): ?>
                            <option value="<?= $c->id ?>" <?= ($isEdit && $project->customer_id == $c->id) ? 'selected' : '' ?>><?= htmlspecialchars($c->name_ar) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-currency-circle-dollar" style="color:var(--c-prj);"></i> <?= $t['sec_fin'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['val'] ?> (<?= $currency ?>)</label>
                    <input type="number" step="0.01" name="contract_value" class="form-control" style="font-family:monospace; font-weight:900; font-size:1.1rem; color:var(--c-prj-dark);" value="<?= $isEdit ? htmlspecialchars($project->contract_value) : '0.00' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['budget'] ?> (<?= $currency ?>)</label>
                    <input type="number" step="0.01" name="estimated_budget" class="form-control" style="font-family:monospace; font-weight:900; color:#475569; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($project->estimated_budget) : '0.00' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['prog'] ?></label>
                    <input type="number" step="0.1" name="progress_percent" class="form-control" style="font-family:monospace; font-weight:900; color:#059669; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($project->progress_percent) : '0' ?>">
                </div>
            </div>

            <div class="grid-3" style="margin-top:24px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['start'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($project->start_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['end'] ?></label>
                    <input type="date" name="end_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($project->end_date ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['status'] ?> <span style="color:red">*</span></label>
                    <select name="status" class="form-control" style="font-weight:800;" required>
                        <?php $st = $isEdit ? $project->status : 'in_progress'; ?>
                        <option value="planning" <?= $st === 'planning' ? 'selected' : '' ?>><?= $t['st_planning'] ?></option>
                        <option value="in_progress" <?= $st === 'in_progress' ? 'selected' : '' ?>><?= $t['st_in_progress'] ?></option>
                        <option value="on_hold" <?= $st === 'on_hold' ? 'selected' : '' ?>><?= $t['st_on_hold'] ?></option>
                        <option value="completed" <?= $st === 'completed' ? 'selected' : '' ?>><?= $t['st_completed'] ?></option>
                        <option value="cancelled" <?= $st === 'cancelled' ? 'selected' : '' ?>><?= $t['st_cancelled'] ?></option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top:24px;">
                <label class="input-label"><?= $t['desc_label'] ?></label>
                <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars($project->description ?? '') : '' ?>">
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/projects/list" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>