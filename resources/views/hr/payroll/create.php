<?php
// Path: resources/views/hr/payroll/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($payroll) && $payroll !== null && !empty($payroll->id);
$actionUrl = $isEdit ? "/ERP/hr/payroll/{$payroll->id}/update" : "/ERP/hr/payroll/process";

$months = [
    'January' => 'يناير (January)', 'February' => 'فبراير (February)', 'March' => 'مارس (March)',
    'April' => 'أبريل (April)', 'May' => 'مايو (May)', 'June' => 'يونيو (June)',
    'July' => 'يوليو (July)', 'August' => 'أغسطس (August)', 'September' => 'سبتمبر (September)',
    'October' => 'أكتوبر (October)', 'November' => 'نوفمبر (November)', 'December' => 'ديسمبر (December)'
];
?>

<style>
    :root { 
        --c-pay: #be123c; 
        --c-pay-dark: #9f1239; 
        --c-pay-light: #ffe4e6;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-pay); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-pay); font-size: 1.4rem; padding: 8px; background: var(--c-pay-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-pay), var(--c-pay-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/payroll" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل بيانات مسير الرواتب' : 'توليد واحتساب مسير رواتب' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">تعديل قيم المبالغ، الخصومات وحالة صرف المسير.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-calculator"></i> البيانات الأساسية للمسير</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">كود المسير <span style="color:red">*</span></label>
                    <input type="text" name="payroll_code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-pay-dark);" value="<?= $isEdit ? htmlspecialchars($payroll->payroll_code) : htmlspecialchars($autoCode) ?>" required readonly>
                </div>
                <div class="form-group">
                    <label class="input-label">عن شهر <span style="color:red">*</span></label>
                    <select name="month" class="form-control" required>
                        <?php foreach($months as $mEng => $mAr): ?>
                            <option value="<?= $mEng ?>" <?= (($isEdit ? $payroll->month : date('F')) === $mEng) ? 'selected' : '' ?>><?= $mAr ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">عن سنة <span style="color:red">*</span></label>
                    <input type="number" name="year" class="form-control" style="font-family:monospace;" value="<?= $isEdit ? htmlspecialchars($payroll->year) : date('Y') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">حالة المسير <span style="color:red">*</span></label>
                    <select name="status" class="form-control" required>
                        <option value="processed" <?= ($isEdit && $payroll->status === 'processed') ? 'selected' : '' ?>>معتمد ومحتسب (Processed)</option>
                        <option value="draft" <?= ($isEdit && $payroll->status === 'draft') ? 'selected' : '' ?>>مسودة (Draft)</option>
                        <option value="paid" <?= ($isEdit && $payroll->status === 'paid') ? 'selected' : '' ?>>تم الصرف بالكامل (Paid)</option>
                    </select>
                </div>
            </div>
        </div>

        <?php if($isEdit): ?>
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-coins"></i> أرقام وتوازنات المسير الممالية</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">إجمالي الرواتب الأساسية</label>
                    <input type="number" step="0.01" min="0" name="total_basic" class="form-control" style="font-family:monospace; font-weight:bold;" value="<?= htmlspecialchars($payroll->total_basic) ?>">
                </div>
                <div class="form-group">
                    <label class="input-label">إجمالي البدلات</label>
                    <input type="number" step="0.01" min="0" name="total_allowances" class="form-control" style="font-family:monospace; font-weight:bold; color:#0284c7;" value="<?= htmlspecialchars($payroll->total_allowances) ?>">
                </div>
                <div class="form-group">
                    <label class="input-label">إجمالي الاستقطاعات والخصومات</label>
                    <input type="number" step="0.01" min="0" name="total_deductions" class="form-control" style="font-family:monospace; font-weight:bold; color:#dc2626;" value="<?= htmlspecialchars($payroll->total_deductions) ?>">
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="action-bar">
            <a href="/ERP/hr/payroll" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث بيانات المسير' : 'بدء الاحتساب الآلي وتوليد المسير' ?></button>
        </div>
    </form>
</div>