<?php
// Path: resources/views/treasury/transfers/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($transfer) && $transfer !== null && !empty($transfer->id);
$actionUrl = $isEdit ? "/ERP/treasury/transfers/{$transfer->id}/update" : "/ERP/treasury/transfers/store";
?>

<style>
    :root { 
        --c-trf: #4f46e5; 
        --c-trf-dark: #4338ca; 
        --c-trf-light: #e0e7ff;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-trf); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-trf); font-size: 1.4rem; padding: 8px; background: var(--c-trf-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-trf), var(--c-trf-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/transfers" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? __('تعديل أمر التحويل الداخلي', 'Edit Internal Transfer') : __('إجراء تحويل نقدي جديد', 'Create New Transfer') ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;"><?= __('تحويل وتغذية السيولة بين الخزائن والحسابات المصرفية.', 'Transfer and fund liquidity between safes and banks.') ?></p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-arrows-left-right"></i> <?= __('مسار التحويل النقدي', 'Transfer Path') ?></h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label" style="color:#dc2626;"><?= __('من حساب / خزينة (المصدر - الخصم)', 'From Account (Source - Deduct)') ?> <span style="color:red">*</span></label>
                    <select name="from_account_id" class="form-control" required style="border-color:#fca5a5;">
                        <option value=""><?= __('-- اختر الحساب المصدر --', '-- Select Source Account --') ?></option>
                        <?php foreach($treasuryAccounts as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && $transfer->from_account_id == $acc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="input-label" style="color:#059669;"><?= __('إلى حساب / خزينة (الوجهة - الإضافة)', 'To Account (Destination - Add)') ?> <span style="color:red">*</span></label>
                    <select name="to_account_id" class="form-control" required style="border-color:#6ee7b7;">
                        <option value=""><?= __('-- اختر الحساب المستلم --', '-- Select Destination Account --') ?></option>
                        <?php foreach($treasuryAccounts as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && $transfer->to_account_id == $acc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-receipt"></i> <?= __('القيمة والبيانات المرجعية', 'Amount & References') ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= __('رقم أمر التحويل', 'Transfer No') ?> <span style="color:red">*</span></label>
                    <input type="text" name="transfer_number" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-trf-dark);" value="<?= $isEdit ? htmlspecialchars($transfer->transfer_number) : htmlspecialchars($autoNumber) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= __('تاريخ التحويل', 'Transfer Date') ?> <span style="color:red">*</span></label>
                    <input type="date" name="transfer_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($transfer->transfer_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= __('المبلغ المحول', 'Transfer Amount') ?> <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" style="font-family:monospace; font-weight:900; color:var(--c-trf-dark); font-size:1.2rem;" value="<?= $isEdit ? htmlspecialchars($transfer->amount) : '' ?>" placeholder="0.00" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= __('الرقم المرجعي / رقم الإيصال / إذن الإيداع', 'Reference / Deposit No') ?></label>
                    <input type="text" name="reference_no" class="form-control" value="<?= $isEdit ? htmlspecialchars($transfer->reference_no) : '' ?>" placeholder="<?= __('مثال: Bank-Ref-99812', 'Example: Bank-Ref-99812') ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= __('البيان والسبب التفصيلي للتحويل', 'Description & Reason') ?></label>
                    <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars($transfer->description) : '' ?>" placeholder="<?= __('تغذية الخزينة الفرعية / إيداع بنكي...', 'Funding sub-safe / bank deposit...') ?>">
                </div>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/treasury/transfers" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;"><?= __('إلغاء', 'Cancel') ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? __('تحديث البيانات', 'Update Data') : __('تأكيد وإصدار التحويل', 'Confirm & Issue Transfer') ?></button>
        </div>
    </form>
</div>