<?php
// Path: resources/views/treasury/petty_cash/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($pettyCash) && $pettyCash !== null && !empty($pettyCash->id);
$actionUrl = $isEdit ? "/ERP/treasury/petty-cash/{$pettyCash->id}/update" : "/ERP/treasury/petty-cash/store";
?>

<style>
    :root { 
        --c-pc: #d97706; 
        --c-pc-dark: #b45309; 
        --c-pc-light: #fef3c7;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-pc); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-pc); font-size: 1.4rem; padding: 8px; background: var(--c-pc-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-pc), var(--c-pc-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/petty-cash" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? __('تعديل بيانات العُهدة المالية', 'Edit Petty Cash Data') : __('تسليم عُهدة مالية جديدة', 'Issue New Petty Cash') ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;"><?= __('تعريف ميزانية العُهدة واسم الموظف المسؤول والحساب المصدر.', 'Define budget, responsible employee, and funding account.') ?></p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-briefcase"></i> <?= __('البيانات الأساسية للعهدة', 'Basic Custody Data') ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= __('كود العُهدة', 'Custody Code') ?> <span style="color:red">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-pc-dark);" value="<?= $isEdit ? htmlspecialchars($pettyCash->code) : htmlspecialchars($autoCode) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= __('تاريخ التسليم', 'Issue Date') ?> <span style="color:red">*</span></label>
                    <input type="date" name="issue_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($pettyCash->issue_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= __('ميزانية العُهدة المسلمة', 'Amount Issued') ?> <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" style="font-family:monospace; font-weight:900; color:var(--c-pc-dark); font-size:1.2rem;" value="<?= $isEdit ? htmlspecialchars($pettyCash->amount) : '' ?>" placeholder="0.00" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= __('اسم الموظف المسؤول عن العُهدة', 'Responsible Employee') ?> <span style="color:red">*</span></label>
                    <input type="text" name="employee_name" class="form-control" value="<?= $isEdit ? htmlspecialchars($pettyCash->employee_name) : '' ?>" placeholder="<?= __('اسم الموظف المسلم له العهدة...', 'Name of employee receiving custody...') ?>" required>
                </div>

                <div class="form-group">
                    <label class="input-label"><?= __('الصندوق / البنك المصدر للعهدة', 'Source Account/Safe') ?> <span style="color:red">*</span></label>
                    <select name="treasury_account_id" class="form-control" required>
                        <option value=""><?= __('-- اختر الحساب المصدر --', '-- Select Source Account --') ?></option>
                        <?php foreach($treasuryAccounts as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && $pettyCash->treasury_account_id == $acc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if($isEdit): ?>
                <div class="grid-2" style="margin-top:20px; background:#fff1f2; padding:16px; border-radius:12px; border:1px solid #fecdd3;">
                    <div class="form-group">
                        <label class="input-label" style="color:#dc2626;"><?= __('المبلغ المنصرف / المصفى حالياً', 'Settled/Spent Amount') ?></label>
                        <input type="number" step="0.01" min="0" name="spent_amount" class="form-control" style="font-family:monospace; font-weight:800; color:#dc2626;" value="<?= htmlspecialchars($pettyCash->spent_amount) ?>">
                    </div>
                    <div class="form-group">
                        <label class="input-label" style="color:#059669;"><?= __('المبلغ المتبقي المحسوب آلياً', 'Auto-calculated Remaining Amount') ?></label>
                        <input type="text" class="form-control" style="font-family:monospace; font-weight:800; color:#059669;" value="<?= number_format((float)$pettyCash->remaining_amount, 2) ?>" readonly>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label"><?= __('الغرض والبيان التفصيلي من العُهدة', 'Purpose & Description') ?></label>
                <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars($pettyCash->description) : '' ?>" placeholder="<?= __('مثال: عهدة نثريات المكتب / مصاريف صيانة وسفريات...', 'Example: Office supplies / travel expenses...') ?>">
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/treasury/petty-cash" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;"><?= __('إلغاء', 'Cancel') ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? __('تحديث بيانات العُهدة', 'Update Custody Data') : __('تسليم وإصدار العُهدة', 'Issue & Save Custody') ?></button>
        </div>
    </form>
</div>