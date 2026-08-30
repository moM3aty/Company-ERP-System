<?php
// Path: resources/views/hr/contracts/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($contract) && $contract !== null && !empty($contract->id);
$actionUrl = $isEdit ? "/ERP/hr/contracts/{$contract->id}/update" : "/ERP/hr/contracts/store";
?>

<style>
    :root { 
        --c-hcont: #d97706; 
        --c-hcont-dark: #b45309; 
        --c-hcont-light: #fef3c7;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-hcont); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-hcont); font-size: 1.4rem; padding: 8px; background: var(--c-hcont-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-hcont), var(--c-hcont-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/contracts" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل وثيقة العقد' : 'إبرام عقد موظف جديد' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">تحديد الرواتب، البدلات، وتواريخ سريان العقد.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-file-signature"></i> البيانات الأساسية للعقد</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">رقم وثيقة العقد <span style="color:red">*</span></label>
                    <input type="text" name="contract_code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-hcont-dark);" value="<?= $isEdit ? htmlspecialchars($contract->contract_code) : htmlspecialchars($autoCode) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label class="input-label">اسم الموظف المعني <span style="color:red">*</span></label>
                    <select name="employee_id" class="form-control" required>
                        <option value="">-- اختر الموظف --</option>
                        <?php foreach($employees as $emp): ?>
                            <option value="<?= $emp->id ?>" <?= ($isEdit && $contract->employee_id == $emp->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp->emp_code) ?> - <?= htmlspecialchars($emp->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">تاريخ بداية العقد <span style="color:red">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->start_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ انتهاء العقد</label>
                    <input type="date" name="end_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->end_date ?? '') : date('Y-m-d', strtotime('+1 year')) ?>">
                </div>
                <div class="form-group">
                    <label class="input-label">حالة العقد <span style="color:red">*</span></label>
                    <select name="status" class="form-control" required>
                        <option value="active" <?= (!$isEdit || $contract->status === 'active') ? 'selected' : '' ?>>ساري المفعول (Active)</option>
                        <option value="expired" <?= ($isEdit && $contract->status === 'expired') ? 'selected' : '' ?>>منتهي (Expired)</option>
                        <option value="terminated" <?= ($isEdit && $contract->status === 'terminated') ? 'selected' : '' ?>>مفسوخ (Terminated)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-coins"></i> الرواتب والبدلات الأساسية</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">الراتب الأساسي (Basic) <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0" name="basic_salary" class="form-control" style="font-family:monospace; font-weight:900; color:#059669; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($contract->basic_salary) : '0.00' ?>" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label class="input-label">بدل السكن (Housing)</label>
                    <input type="number" step="0.01" min="0" name="housing_allowance" class="form-control" style="font-family:monospace; font-weight:800; color:#0284c7; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($contract->housing_allowance) : '0.00' ?>" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="input-label">بدل النقل/مواصلات (Transport)</label>
                    <input type="number" step="0.01" min="0" name="transport_allowance" class="form-control" style="font-family:monospace; font-weight:800; color:#d97706; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($contract->transport_allowance) : '0.00' ?>" placeholder="0.00">
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">ملاحظات وشروط خاصة بالعقد</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="أية بدلات أخرى، شروط جزائية، أو التزامات..."><?= $isEdit ? htmlspecialchars($contract->notes ?? '') : '' ?></textarea>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/hr/contracts" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث بيانات العقد' : 'اعتماد وحفظ العقد' ?></button>
        </div>
    </form>
</div>