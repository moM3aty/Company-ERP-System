<?php
// Path: resources/views/treasury/petty_cash/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$isEdit = isset($pettyCash) && $pettyCash !== null && !empty($pettyCash->id);
$actionUrl = $isEdit ? "/ERP/treasury/petty-cash/" . (int)$pettyCash->id . "/update" : "/ERP/treasury/petty-cash/store";

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title_new' => 'تسليم عُهدة مالية جديدة', 'title_edit' => 'تعديل بيانات العُهدة المالية',
        'desc' => 'تعريف ميزانية العُهدة واسم الموظف المسؤول والحساب المصدر.', 'panel_basic' => 'البيانات الأساسية للعهدة',
        'code' => 'كود العُهدة', 'date' => 'تاريخ التسليم', 'amount' => "ميزانية العُهدة المسلمة (بـ $currency)",
        'employee' => 'اسم الموظف المسؤول عن العُهدة', 'emp_ph' => 'اسم الموظف المسلم له العهدة...',
        'account' => 'الصندوق / البنك المصدر للعهدة', 'choose_acc' => '-- اختر الحساب المصدر --',
        'spent' => 'المبلغ المنصرف / المصفى حالياً', 'remaining' => 'المبلغ المتبقي المحسوب آلياً',
        'purpose' => 'الغرض والبيان التفصيلي من العُهدة', 'purpose_ph' => 'مثال: عهدة نثريات المكتب / مصاريف صيانة وسفريات...',
        'cancel' => 'إلغاء وتراجع', 'save' => 'تسليم وإصدار العُهدة', 'update' => 'تحديث بيانات العُهدة', 'active_scope' => 'الفرع النشط:'
    ],
    'en' => [
        'title_new' => 'Issue New Petty Cash', 'title_edit' => 'Edit Petty Cash Data',
        'desc' => 'Define budget, responsible employee, and funding account.', 'panel_basic' => 'Basic Custody Data',
        'code' => 'Custody Code', 'date' => 'Issue Date', 'amount' => "Amount Issued (in $currency)",
        'employee' => 'Responsible Employee', 'emp_ph' => 'Name of employee receiving custody...',
        'account' => 'Source Account/Safe', 'choose_acc' => '-- Select Source Account --',
        'spent' => 'Settled/Spent Amount', 'remaining' => 'Auto-calculated Remaining Amount',
        'purpose' => 'Purpose & Description', 'purpose_ph' => 'Example: Office supplies / travel expenses...',
        'cancel' => 'Cancel', 'save' => 'Issue & Save Custody', 'update' => 'Update Custody Data', 'active_scope' => 'Active Branch:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-pc: #d97706; 
        --c-pc-dark: #b45309; 
        --c-pc-light: #fef3c7;
        --c-border: #cbd5e1; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text); margin-bottom: 20px; }

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
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--c-pc); background: #ffffff; box-shadow: 0 0 0 4px var(--c-pc-light); }

    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-pc), var(--c-pc-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/petty-cash" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-pc);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-pc); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-briefcase"></i> <?= $t['panel_basic'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['code'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-pc-dark);" value="<?= $isEdit ? htmlspecialchars((string)($pettyCash->code ?? '')) : htmlspecialchars((string)($autoCode ?? '')) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="issue_date" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($pettyCash->issue_date ?? '')) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['amount'] ?> <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" style="font-family:monospace; font-weight:900; color:var(--c-pc-dark); font-size:1.2rem;" value="<?= $isEdit ? htmlspecialchars((string)($pettyCash->amount ?? '')) : '' ?>" placeholder="0.00" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['employee'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="employee_name" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($pettyCash->employee_name ?? '')) : '' ?>" placeholder="<?= $t['emp_ph'] ?>" required>
                </div>

                <div class="form-group">
                    <label class="input-label"><?= $t['account'] ?> <span style="color:red">*</span></label>
                    <select name="treasury_account_id" class="form-control" required>
                        <option value=""><?= $t['choose_acc'] ?></option>
                        <?php foreach($treasuryAccounts ?? [] as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && ($pettyCash->treasury_account_id ?? 0) == $acc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$acc->code) ?> - <?= htmlspecialchars((string)$acc->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if($isEdit): ?>
                <div class="grid-2" style="margin-top:20px; background:#fff1f2; padding:16px; border-radius:12px; border:1px solid #fecdd3;">
                    <div class="form-group">
                        <label class="input-label" style="color:#dc2626;"><?= $t['spent'] ?></label>
                        <input type="number" step="0.01" min="0" name="spent_amount" class="form-control" style="font-family:monospace; font-weight:800; color:#dc2626;" value="<?= htmlspecialchars((string)($pettyCash->spent_amount ?? '0.00')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="input-label" style="color:#059669;"><?= $t['remaining'] ?></label>
                        <input type="text" class="form-control" style="font-family:monospace; font-weight:800; color:#059669;" value="<?= number_format((float)($pettyCash->remaining_amount ?? 0), 2) ?>" readonly>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label"><?= $t['purpose'] ?></label>
                <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($pettyCash->description ?? '')) : '' ?>" placeholder="<?= $t['purpose_ph'] ?>">
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/treasury/petty-cash" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>