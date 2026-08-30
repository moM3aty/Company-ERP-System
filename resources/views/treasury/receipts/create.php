<?php
// Path: resources/views/treasury/receipts/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($receipt) && $receipt !== null && !empty($receipt->id);
$actionUrl = $isEdit ? "/ERP/treasury/receipts/{$receipt->id}/update" : "/ERP/treasury/receipts/store";
?>
<style>
    :root { 
        --c-rec: #0d9488; 
        --c-rec-dark: #0f766e; 
        --c-rec-light: #f0fdf4;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-rec); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-rec); font-size: 1.4rem; padding: 8px; background: var(--c-rec-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-rec), var(--c-rec-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/receipts" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? __('تعديل سند القبض', 'Edit Receipt Voucher') : __('إنشاء سند قبض جديد', 'Create Receipt Voucher') ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;"><?= __('تحرير وإصدار إيصال استلام نقدية أو تحويل وارد.', 'Issue a cash receipt or record incoming transfers.') ?></p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-receipt"></i> <?= __('البيانات الأساسية للسند', 'Voucher Basic Data') ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= __('رقم السند', 'Voucher No') ?> <span style="color:red">*</span></label>
                    <input type="text" name="voucher_number" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-rec-dark);" value="<?= $isEdit ? htmlspecialchars($receipt->voucher_number) : htmlspecialchars($autoNumber) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= __('تاريخ الاستلام', 'Receipt Date') ?> <span style="color:red">*</span></label>
                    <input type="date" name="receipt_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($receipt->receipt_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= __('المبلغ المقبوض', 'Received Amount') ?> <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" style="font-family:monospace; font-weight:900; color:#059669; font-size:1.2rem;" value="<?= $isEdit ? htmlspecialchars($receipt->amount) : '' ?>" placeholder="0.00" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= __('الصندوق / الحساب البنكي المستلم', 'Receiving Account / Safe') ?> <span style="color:red">*</span></label>
                    <select name="treasury_account_id" class="form-control" required>
                        <option value=""><?= __('-- اختر الحساب المستلم --', '-- Select Receiving Account --') ?></option>
                        <?php foreach($treasuryAccounts as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && $receipt->treasury_account_id == $acc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= __('طريقة الدفع والتحصيل', 'Payment Method') ?> <span style="color:red">*</span></label>
                    <select name="payment_method" class="form-control" required>
                        <option value="cash" <?= ($isEdit && $receipt->payment_method === 'cash') ? 'selected' : '' ?>><?= __('نقدي (Cash)', 'Cash') ?></option>
                        <option value="bank_transfer" <?= ($isEdit && $receipt->payment_method === 'bank_transfer') ? 'selected' : '' ?>><?= __('تحويل بنكي مباشر', 'Direct Bank Transfer') ?></option>
                        <option value="cheque" <?= ($isEdit && $receipt->payment_method === 'cheque') ? 'selected' : '' ?>><?= __('شيك مسحوب', 'Received Cheque') ?></option>
                        <option value="pos" <?= ($isEdit && $receipt->payment_method === 'pos') ? 'selected' : '' ?>><?= __('شبكة / مدى / POS', 'POS / Card') ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-user"></i> <?= __('جهة الدفع والبيان', 'Payer Information & Description') ?></h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label"><?= __('اختيار العميل (إن وجد)', 'Select Customer (Optional)') ?></label>
                    <select name="customer_id" class="form-control">
                        <option value=""><?= __('-- عميل عام / غير مسجل --', '-- General / Unregistered Customer --') ?></option>
                        <?php foreach($customers as $c): ?>
                            <option value="<?= $c->id ?>" <?= ($isEdit && $receipt->customer_id == $c->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= __('اسم الدافع / المسلم صراحة', 'Payer Name (Explicit)') ?></label>
                    <input type="text" name="payer_name" class="form-control" value="<?= $isEdit ? htmlspecialchars($receipt->payer_name) : '' ?>" placeholder="<?= __('اسم الشخص أو الجهة المسلمة للمبلغ', 'Name of the person or entity paying') ?>">
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= __('الرقم المرجعي / رقم الشيك / الإيصال', 'Reference / Cheque No') ?></label>
                    <input type="text" name="reference_no" class="form-control" value="<?= $isEdit ? htmlspecialchars($receipt->reference_no) : '' ?>" placeholder="<?= __('مثال: CHQ-99081 أو Ref-102', 'Example: CHQ-99081') ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= __('البيان والوصف التفصيلي', 'Description & Details') ?></label>
                    <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars($receipt->description) : '' ?>" placeholder="<?= __('سبب واستحقاق قبض هذا المبلغ...', 'Reason for receiving this amount...') ?>">
                </div>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/treasury/receipts" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;"><?= __('إلغاء', 'Cancel') ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? __('تحديث السند', 'Update Voucher') : __('حفظ وإصدار السند', 'Save & Issue Voucher') ?></button>
        </div>
    </form>
</div>