<?php
// Path: resources/views/hr/appraisals/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($appraisal) && $appraisal !== null && !empty($appraisal->id);
$actionUrl = $isEdit ? "/ERP/hr/appraisals/{$appraisal->id}/update" : "/ERP/hr/appraisals/store";
?>

<style>
    :root { 
        --c-appr: #ea580c; 
        --c-appr-dark: #c2410c; 
        --c-appr-light: #ffedd5;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-appr); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-appr); font-size: 1.4rem; padding: 8px; background: var(--c-appr-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-appr), var(--c-appr-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/appraisals" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل تقييم الأداء' : 'إجراء تقييم أداء جديد' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">إدخال نتائج التقييم، النسبة المئوية والتوصيات.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-chart-line-up"></i> البيانات الأساسية للتقييم</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">كود التقييم <span style="color:red">*</span></label>
                    <input type="text" name="appraisal_code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-appr-dark);" value="<?= $isEdit ? htmlspecialchars($appraisal->appraisal_code) : htmlspecialchars($autoCode) ?>" required readonly>
                </div>
                <div class="form-group">
                    <label class="input-label">الموظف الخاضع للتقييم <span style="color:red">*</span></label>
                    <select name="employee_id" class="form-control" required>
                        <option value="">-- اختر الموظف --</option>
                        <?php foreach($employees as $emp): ?>
                            <option value="<?= $emp->id ?>" <?= ($isEdit && $appraisal->employee_id == $emp->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp->emp_code) ?> - <?= htmlspecialchars($emp->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">فترة التقييم <span style="color:red">*</span></label>
                    <input type="text" name="appraisal_period" class="form-control" value="<?= $isEdit ? htmlspecialchars($appraisal->appraisal_period) : 'Annual ' . date('Y') ?>" placeholder="مثال: Q1 2026 / السنوي 2025" required>
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ إجراء التقييم <span style="color:red">*</span></label>
                    <input type="date" name="appraisal_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($appraisal->appraisal_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">المُقَيِّم المسؤول</label>
                    <input type="text" name="evaluator_name" class="form-control" value="<?= $isEdit ? htmlspecialchars($appraisal->evaluator_name ?? '') : '' ?>" placeholder="اسم المدير / رئيس القسم...">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-star"></i> التقدير والملاحظات</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">النتيجة النهائية (%) <span style="color:red">*</span></label>
                    <input type="number" step="0.1" min="0" max="100" name="score" class="form-control" style="font-family:monospace; font-weight:900; color:#059669; font-size:1.2rem;" value="<?= $isEdit ? htmlspecialchars($appraisal->score) : '85.0' ?>" placeholder="0 - 100" required>
                </div>
                <div class="form-group">
                    <label class="input-label">حالة التقييم <span style="color:red">*</span></label>
                    <select name="status" class="form-control" required>
                        <option value="draft" <?= (!$isEdit || $appraisal->status === 'draft') ? 'selected' : '' ?>>مسودة (Draft)</option>
                        <option value="submitted" <?= ($isEdit && $appraisal->status === 'submitted') ? 'selected' : '' ?>>مقدم للمراجعة (Submitted)</option>
                        <option value="approved" <?= ($isEdit && $appraisal->status === 'approved') ? 'selected' : '' ?>>معتمد نهائياً (Approved)</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">التوصيات وملاحظات الإدارة</label>
                <textarea name="remarks" class="form-control" rows="3" placeholder="نقاط القوة، نقاط التحسين، الترقية أو العلاوة المقترحة..."><?= $isEdit ? htmlspecialchars($appraisal->remarks ?? '') : '' ?></textarea>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/hr/appraisals" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث التقييم' : 'اعتماد وحفظ التقييم' ?></button>
        </div>
    </form>
</div>