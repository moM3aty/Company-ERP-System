<?php
// Path: resources/views/hr/shifts/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($shift) && $shift !== null && !empty($shift->id);
$actionUrl = $isEdit ? "/ERP/hr/shifts/{$shift->id}/update" : "/ERP/hr/shifts/store";
?>

<style>
    :root { 
        --c-shift: #16a34a; 
        --c-shift-dark: #15803d; 
        --c-shift-light: #dcfce7;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-shift); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-shift); font-size: 1.4rem; padding: 8px; background: var(--c-shift-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    @media(max-width:768px) { .grid-2 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-shift), var(--c-shift-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/shifts" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل وردية العمل' : 'إضافة وردية عمل جديدة' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">تحديد مواقيت الدوام وفترات السماح للموظفين.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-clock-user"></i> تفاصيل ومواعيد الوردية</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">كود الوردية <span style="color:red">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-shift-dark);" value="<?= $isEdit ? htmlspecialchars($shift->code) : htmlspecialchars($autoCode) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label class="input-label">المسمى / الاسم (عربي) <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($shift->name_ar) : '' ?>" placeholder="مثال: الوردية الصباحية..." required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px; padding:20px; background:#f0fdf4; border:1px solid #dcfce7; border-radius:12px;">
                <div class="form-group">
                    <label class="input-label" style="color:#059669;"><i class="ph-bold ph-sun"></i> وقت بداية الدوام (Start Time) <span style="color:red">*</span></label>
                    <input type="time" name="start_time" class="form-control" style="font-family:monospace; font-size:1.2rem; color:#059669;" value="<?= $isEdit ? htmlspecialchars($shift->start_time) : '08:00' ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label" style="color:#dc2626;"><i class="ph-bold ph-moon"></i> وقت نهاية الدوام (End Time) <span style="color:red">*</span></label>
                    <input type="time" name="end_time" class="form-control" style="font-family:monospace; font-size:1.2rem; color:#dc2626;" value="<?= $isEdit ? htmlspecialchars($shift->end_time) : '16:00' ?>" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">فترة السماح للتأخير (بالدقائق)</label>
                    <input type="number" min="0" name="grace_period_mins" class="form-control" style="font-family:monospace; font-weight:bold; color:#d97706;" value="<?= $isEdit ? htmlspecialchars($shift->grace_period_mins) : '15' ?>" placeholder="15">
                </div>
                <div class="form-group">
                    <label class="input-label">حالة الوردية <span style="color:red">*</span></label>
                    <select name="status" class="form-control" required>
                        <option value="active" <?= (!$isEdit || $shift->status === 'active') ? 'selected' : '' ?>>مفعلة (Active)</option>
                        <option value="inactive" <?= ($isEdit && $shift->status === 'inactive') ? 'selected' : '' ?>>متوقفة (Inactive)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/hr/shifts" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث الوردية' : 'حفظ وردية العمل' ?></button>
        </div>
    </form>
</div>