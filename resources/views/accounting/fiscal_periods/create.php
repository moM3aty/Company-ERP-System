<?php
// Path: resources/views/accounting/fiscal_periods/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($period) && $period !== null && !empty($period->id);
$actionUrl = $isEdit ? "/ERP/accounting/fiscal-periods/{$period->id}/update" : "/ERP/accounting/fiscal-periods/store";

$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_err']);
?>

<style>
    :root { 
        --c-fp: #d97706; 
        --c-fp-dark: #b45309; 
        --c-fp-light: #fef3c7;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 750px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-fp); }
    [dir="ltr"] .form-section::before { right: auto; left: 0; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-fp); font-size: 1.4rem; padding: 8px; background: var(--c-fp-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    @media(max-width:768px) { .grid-2 { grid-template-columns: 1fr; } }
    .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 14px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; }

    .toggle-box { background: var(--c-fp-light); border: 1px solid #fde68a; border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 14px; margin-top: 10px; }

    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-fp), var(--c-fp-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.2); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/accounting/fiscal-periods" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل بيانات الفترة المالية' : 'إضافة سنة / فترة مالية جديدة' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">تحديد النطاق الزمني والمسمى الخاص بالفترة المحاسبية.</p>
            </div>
        </div>
    </div>

    <?php if($flashErr): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca; display:flex; align-items:center; gap:8px;">
            <i class="ph-fill ph-warning-circle" style="font-size:1.2rem;"></i> <?= htmlspecialchars($flashErr) ?>
        </div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-calendar-plus"></i> إعدادات الفترة الزمنية</h3>
            
            <div class="form-group">
                <label class="input-label">اسم / عنوان الفترة المالية <span style="color:red">*</span></label>
                <input type="text" name="period_name" class="form-control" style="font-weight:bold; color:var(--c-fp-dark);" value="<?= $isEdit ? htmlspecialchars($period->period_name ?? '') : 'السنة المالية ' . date('Y') ?>" required>
            </div>

            <div class="grid-2" style="margin-top: 20px;">
                <div class="form-group">
                    <label class="input-label">تاريخ بداية السنة <span style="color:red">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($period->start_date ?? '') : date('Y-01-01') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ نهاية السنة <span style="color:red">*</span></label>
                    <input type="date" name="end_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($period->end_date ?? '') : date('Y-12-31') ?>" required>
                </div>
            </div>

            <?php if(!$isEdit): ?>
                <div class="toggle-box">
                    <input type="checkbox" name="auto_generate_months" value="1" id="chkAutoMonths" checked style="width:20px; height:20px; accent-color:var(--c-fp);">
                    <label for="chkAutoMonths" style="font-weight:800; color:var(--c-fp-dark); cursor:pointer;">
                        توليد 12 شهراً مالياً فرعياً تلقائياً تحت هذه السنة للتحكم بالقفل الشهري.
                    </label>
                </div>
            <?php endif; ?>

            <div class="form-group" style="margin-top: 20px;">
                <label class="input-label">ملاحظات داخلية</label>
                <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($period->notes ?? '') : '' ?>" placeholder="ملاحظات حول إقفال الأرصدة أو الميزانية...">
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/accounting/fiscal-periods" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'حفظ التعديلات' : 'اعتماد وإنشاء السنة' ?></button>
        </div>
    </form>
</div>