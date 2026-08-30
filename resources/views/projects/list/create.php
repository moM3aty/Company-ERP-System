<?php
// Path: resources/views/projects/list/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($project) && $project !== null && !empty($project->id);
$actionUrl = $isEdit ? "/ERP/projects/list/{$project->id}/update" : "/ERP/projects/list/store";
?>

<style>
    :root { 
        --c-prj: #0284c7; 
        --c-prj-dark: #0369a1; 
        --c-prj-light: #e0f2fe;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-prj); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-prj); font-size: 1.4rem; padding: 8px; background: var(--c-prj-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-prj), var(--c-prj-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/list" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل بيانات المشروع' : 'تعريف وإضافة مشروع جديد' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">إدخال تفاصيل العقد والعميل والميزانية المقدرة للمشروع.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-buildings"></i> البيانات الأساسية للمشروع</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">كود المشروع <span style="color:red">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-prj-dark);" value="<?= $isEdit ? htmlspecialchars($project->code) : htmlspecialchars($autoCode) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="input-label">اسم المشروع (عربي) <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($project->name_ar) : '' ?>" placeholder="مثال: مشروع إنشائي / تطوير البرمجيات..." required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">اسم المشروع (إنجليزي)</label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($project->name_en ?? '') : '' ?>" placeholder="e.g. Residential Tower Construction">
                </div>
                <div class="form-group">
                    <label class="input-label">العميل المالك للمشروع</label>
                    <select name="customer_id" class="form-control">
                        <option value="">-- اختر العميل --</option>
                        <?php foreach($customers as $c): ?>
                            <option value="<?= $c->id ?>" <?= ($isEdit && $project->customer_id == $c->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-currency-circle-dollar"></i> القيمة المالية والجدول الزمني</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">قيمة العقد الكلية</label>
                    <input type="number" step="0.01" min="0" name="contract_value" class="form-control" style="font-family:monospace; font-weight:900; color:var(--c-prj-dark); font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($project->contract_value) : '0.00' ?>" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="input-label">الميزانية المقدرة للتكاليف</label>
                    <input type="number" step="0.01" min="0" name="estimated_budget" class="form-control" style="font-family:monospace; font-weight:900; color:#475569; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($project->estimated_budget) : '0.00' ?>" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="input-label">نسبة الإنجاز الحالية (%)</label>
                    <input type="number" step="0.1" min="0" max="100" name="progress_percent" class="form-control" style="font-family:monospace; font-weight:900; color:#059669; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($project->progress_percent) : '0' ?>" placeholder="0">
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">تاريخ بدء المشروع <span style="color:red">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($project->start_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ التسليم المتوقع</label>
                    <input type="date" name="end_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($project->end_date ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label">حالة المشروع <span style="color:red">*</span></label>
                    <select name="status" class="form-control" required>
                        <option value="planning" <?= ($isEdit && $project->status === 'planning') ? 'selected' : '' ?>>تخطيط وتجهيز (Planning)</option>
                        <option value="in_progress" <?= (!$isEdit || $project->status === 'in_progress') ? 'selected' : '' ?>>قيد التنفيذ (In Progress)</option>
                        <option value="on_hold" <?= ($isEdit && $project->status === 'on_hold') ? 'selected' : '' ?>>موقف مؤقتاً (On Hold)</option>
                        <option value="completed" <?= ($isEdit && $project->status === 'completed') ? 'selected' : '' ?>>مكتمل (Completed)</option>
                        <option value="cancelled" <?= ($isEdit && $project->status === 'cancelled') ? 'selected' : '' ?>>ملغى (Cancelled)</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">نطاق العمل والوصف التفصيلي</label>
                <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars($project->description ?? '') : '' ?>" placeholder="ملاحظات وشروط تنفيذ المشروع...">
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/projects/list" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث بيانات المشروع' : 'حفظ وتسجيل المشروع' ?></button>
        </div>
    </form>
</div>