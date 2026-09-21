<?php
// Path: resources/views/treasury/receipts/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$isEdit = isset($receipt) && $receipt !== null && !empty($receipt->id);
$actionUrl = $isEdit ? "/ERP/treasury/receipts/" . (int)$receipt->id . "/update" : "/ERP/treasury/receipts/store";

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title_new' => 'إنشاء سند قبض جديد', 'title_edit' => 'تعديل سند القبض',
        'desc' => 'تحرير وإصدار إيصال استلام نقدية أو تحويل وارد.', 'panel_basic' => 'البيانات الأساسية للسند',
        'num' => 'رقم السند', 'date' => 'تاريخ الاستلام', 'amount' => "المبلغ المقبوض (بـ $currency)",
        'account' => 'الصندوق / الحساب البنكي المستلم', 'choose_acc' => '-- اختر الحساب المستلم --',
        'method' => 'طريقة الدفع والتحصيل', 'cash' => 'نقدي (Cash)', 'bank_transfer' => 'تحويل بنكي مباشر',
        'cheque' => 'شيك مسحوب', 'pos' => 'شبكة / مدى / POS', 'panel_payer' => 'جهة الدفع والبيان',
        'cust' => 'اختيار العميل (إن وجد)', 'general_cust' => '-- عميل عام / غير مسجل --',
        'payer_name' => 'اسم الدافع / المسلم صراحة', 'payer_ph' => 'اسم الشخص أو الجهة المسلمة للمبلغ',
        'ref_no' => 'الرقم المرجعي / رقم الشيك / الإيصال', 'ref_ph' => 'مثال: CHQ-99081 أو Ref-102',
        'details' => 'البيان والوصف التفصيلي', 'details_ph' => 'سبب واستحقاق قبض هذا المبلغ...',
        'cancel' => 'إلغاء وتراجع', 'save' => 'حفظ وإصدار السند', 'update' => 'تحديث السند', 'active_scope' => 'الفرع النشط:'
    ],
    'en' => [
        'title_new' => 'Create Receipt Voucher', 'title_edit' => 'Edit Receipt Voucher',
        'desc' => 'Issue a cash receipt or record incoming transfers.', 'panel_basic' => 'Voucher Basic Data',
        'num' => 'Voucher No.', 'date' => 'Receipt Date', 'amount' => "Received Amount (in $currency)",
        'account' => 'Receiving Account / Safe', 'choose_acc' => '-- Select Receiving Account --',
        'method' => 'Payment Method', 'cash' => 'Cash', 'bank_transfer' => 'Direct Bank Transfer',
        'cheque' => 'Received Cheque', 'pos' => 'POS / Cards', 'panel_payer' => 'Payer Info & Details',
        'cust' => 'Select Customer (Optional)', 'general_cust' => '-- General Customer --',
        'payer_name' => 'Payer Name (Explicit)', 'payer_ph' => 'Name of entity/person paying',
        'ref_no' => 'Reference / Cheque No.', 'ref_ph' => 'e.g. CHQ-99081 or Ref-102',
        'details' => 'Description & Details', 'details_ph' => 'Reason for receiving this amount...',
        'cancel' => 'Cancel', 'save' => 'Save & Issue Voucher', 'update' => 'Update Voucher', 'active_scope' => 'Active Branch:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-rec: #0d9488; 
        --c-rec-dark: #0f766e; 
        --c-rec-light: #f0fdf4;
        --c-border: #cbd5e1; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text); margin-bottom: 20px; }

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
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--c-rec); background: #ffffff; box-shadow: 0 0 0 4px var(--c-rec-light); }

    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-rec), var(--c-rec-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/receipts" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-rec);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-rec); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-receipt"></i> <?= $t['panel_basic'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['num'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="voucher_number" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-rec-dark);" value="<?= $isEdit ? htmlspecialchars((string)($receipt->voucher_number ?? '')) : htmlspecialchars((string)($autoNumber ?? '')) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="receipt_date" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($receipt->receipt_date ?? '')) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['amount'] ?> <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" style="font-family:monospace; font-weight:900; color:#059669; font-size:1.2rem;" value="<?= $isEdit ? htmlspecialchars((string)($receipt->amount ?? '')) : '' ?>" placeholder="0.00" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['account'] ?> <span style="color:red">*</span></label>
                    <select name="treasury_account_id" class="form-control" required>
                        <option value=""><?= $t['choose_acc'] ?></option>
                        <?php foreach($treasuryAccounts ?? [] as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && ($receipt->treasury_account_id ?? 0) == $acc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$acc->code) ?> - <?= htmlspecialchars((string)$acc->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['method'] ?> <span style="color:red">*</span></label>
                    <select name="payment_method" class="form-control" required>
                        <?php $pm = $isEdit ? ($receipt->payment_method ?? 'cash') : 'cash'; ?>
                        <option value="cash" <?= $pm === 'cash' ? 'selected' : '' ?>><?= $t['cash'] ?></option>
                        <option value="bank_transfer" <?= $pm === 'bank_transfer' ? 'selected' : '' ?>><?= $t['bank_transfer'] ?></option>
                        <option value="cheque" <?= $pm === 'cheque' ? 'selected' : '' ?>><?= $t['cheque'] ?></option>
                        <option value="pos" <?= $pm === 'pos' ? 'selected' : '' ?>><?= $t['pos'] ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-user"></i> <?= $t['panel_payer'] ?></h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label"><?= $t['cust'] ?></label>
                    <select name="customer_id" class="form-control">
                        <option value=""><?= $t['general_cust'] ?></option>
                        <?php foreach($customers ?? [] as $c): ?>
                            <option value="<?= $c->id ?>" <?= ($isEdit && ($receipt->customer_id ?? 0) == $c->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$c->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['payer_name'] ?></label>
                    <input type="text" name="payer_name" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($receipt->payer_name ?? '')) : '' ?>" placeholder="<?= $t['payer_ph'] ?>">
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['ref_no'] ?></label>
                    <input type="text" name="reference_no" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($receipt->reference_no ?? '')) : '' ?>" placeholder="<?= $t['ref_ph'] ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['details'] ?></label>
                    <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($receipt->description ?? '')) : '' ?>" placeholder="<?= $t['details_ph'] ?>">
                </div>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/treasury/receipts" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>