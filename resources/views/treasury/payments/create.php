<?php
// Path: resources/views/treasury/payments/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($payment) && $payment !== null && !empty($payment->id);
$actionUrl = $isEdit ? "/ERP/treasury/payments/{$payment->id}/update" : "/ERP/treasury/payments/store";
?>

<style>
    :root { 
        --c-pay: #e11d48; 
        --c-pay-dark: #be123c; 
        --c-pay-light: #fff1f2;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
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
            <a href="/ERP/treasury/payments" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? __('تعديل سند الصرف', 'Edit Payment Voucher') : __('إنشاء سند صرف جديد', 'Create Payment Voucher') ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;"><?= __('تحرير وإصدار إذن صرف نقدية أو تحويل صادرة من الخزينة.', 'Issue a cash payment or outgoing transfer voucher.') ?></p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-receipt"></i> <?= __('البيانات الأساسية للسند', 'Voucher Basic Data') ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= __('رقم السند', 'Voucher No') ?> <span style="color:red">*</span></label>
                    <input type="text" name="voucher_number" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-pay-dark);" value="<?= $isEdit ? htmlspecialchars($payment->voucher_number) : htmlspecialchars($autoNumber) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= __('تاريخ الصرف', 'Payment Date') ?> <span style="color:red">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($payment->payment_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= __('المبلغ المصروف', 'Payment Amount') ?> <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" style="font-family:monospace; font-weight:900; color:#e11d48; font-size:1.2rem;" value="<?= $isEdit ? htmlspecialchars($payment->amount) : '' ?>" placeholder="0.00" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= __('الصندوق / الحساب البنكي الصادر منه', 'Outgoing Account / Safe') ?> <span style="color:red">*</span></label>
                    <select name="treasury_account_id" class="form-control" required>
                        <option value=""><?= __('-- اختر الحساب الصادر --', '-- Select Outgoing Account --') ?></option>
                        <?php foreach($treasuryAccounts as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && $payment->treasury_account_id == $acc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= __('طريقة الدفع والصرف', 'Payment Method') ?> <span style="color:red">*</span></label>
                    <select name="payment_method" class="form-control" required>
                        <option value="cash" <?= ($isEdit && $payment->payment_method === 'cash') ? 'selected' : '' ?>><?= __('نقدي (Cash)', 'Cash') ?></option>
                        <option value="bank_transfer" <?= ($isEdit && $payment->payment_method === 'bank_transfer') ? 'selected' : '' ?>><?= __('تحويل بنكي مباشر', 'Direct Bank Transfer') ?></option>
                        <option value="cheque" <?= ($isEdit && $payment->payment_method === 'cheque') ? 'selected' : '' ?>><?= __('شيك صادر', 'Issued Cheque') ?></option>
                        <option value="pos" <?= ($isEdit && $payment->payment_method === 'pos') ? 'selected' : '' ?>><?= __('بطاقة / شبكة / POS', 'POS / Card') ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-user"></i> <?= __('المستفيد والبيان', 'Payee & Description') ?></h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label"><?= __('اختيار المورد (إن وجد)', 'Select Supplier (Optional)') ?></label>
                    <select name="supplier_id" class="form-control">
                        <option value=""><?= __('-- مورد عام / غير مسجل --', '-- General / Unregistered Supplier --') ?></option>
                        <?php foreach($suppliers as $s): ?>
                            <option value="<?= $s->id ?>" <?= ($isEdit && $payment->supplier_id == $s->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= __('اسم المستفيد صراحة (اصرفوا إلى)', 'Payee Name (Explicit)') ?></label>
                    <input type="text" name="payee_name" class="form-control" value="<?= $isEdit ? htmlspecialchars($payment->payee_name) : '' ?>" placeholder="<?= __('اسم الشخص أو الشركة المستلمة للمبلغ', 'Name of the person or company receiving the amount') ?>">
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= __('الرقم المرجعي / رقم الشيك / الحوالة', 'Reference / Cheque No') ?></label>
                    <input type="text" name="reference_no" class="form-control" value="<?= $isEdit ? htmlspecialchars($payment->reference_no) : '' ?>" placeholder="<?= __('مثال: CHQ-50021 أو Trf-881', 'Example: CHQ-50021') ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= __('البيان وسبب الصرف', 'Description & Reason') ?></label>
                    <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars($payment->description) : '' ?>" placeholder="<?= __('سبب واستحقاق صرف هذا المبلغ...', 'Reason for this payment...') ?>">
                </div>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/treasury/payments" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;"><?= __('إلغاء', 'Cancel') ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? __('تحديث السند', 'Update Voucher') : __('حفظ وإصدار السند', 'Save & Issue Voucher') ?></button>
        </div>
    </form>
</div>