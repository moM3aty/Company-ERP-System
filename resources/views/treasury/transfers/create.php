<?php
// Path: resources/views/treasury/transfers/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$isEdit = isset($transfer) && $transfer !== null && !empty($transfer->id);
$actionUrl = $isEdit ? "/ERP/treasury/transfers/" . (int)$transfer->id . "/update" : "/ERP/treasury/transfers/store";

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title_new' => 'إجراء تحويل نقدي جديد', 'title_edit' => 'تعديل أمر التحويل الداخلي',
        'desc' => 'تحويل وتغذية السيولة بين الخزائن والحسابات المصرفية.', 'panel_flow' => 'مسار التحويل النقدي',
        'from_acc' => 'من حساب / خزينة (المصدر - الخصم)', 'choose_from' => '-- اختر الحساب المصدر --',
        'to_acc' => 'إلى حساب / خزينة (الوجهة - الإضافة)', 'choose_to' => '-- اختر الحساب المستلم --',
        'panel_details' => 'القيمة والبيانات المرجعية', 'num' => 'رقم أمر التحويل',
        'date' => 'تاريخ التحويل', 'amount' => "المبلغ المحول (بـ $currency)",
        'ref_no' => 'الرقم المرجعي / رقم الإيصال / إذن الإيداع', 'ref_ph' => 'مثال: Bank-Ref-99812',
        'description' => 'البيان والسبب التفصيلي للتحويل', 'desc_ph' => 'تغذية الخزينة الفرعية / إيداع بنكي...',
        'cancel' => 'إلغاء وتراجع', 'save' => 'تأكيد وإصدار التحويل', 'update' => 'تحديث البيانات', 'active_scope' => 'الفرع النشط:'
    ],
    'en' => [
        'title_new' => 'Create New Transfer', 'title_edit' => 'Edit Internal Transfer',
        'desc' => 'Transfer and fund liquidity between safes and banks.', 'panel_flow' => 'Transfer Path',
        'from_acc' => 'From Account (Source - Deduct)', 'choose_from' => '-- Select Source Account --',
        'to_acc' => 'To Account (Destination - Add)', 'choose_to' => '-- Select Destination Account --',
        'panel_details' => 'Amount & References', 'num' => 'Transfer No.',
        'date' => 'Transfer Date', 'amount' => "Transfer Amount (in $currency)",
        'ref_no' => 'Reference / Deposit No.', 'ref_ph' => 'e.g. Bank-Ref-99812',
        'description' => 'Description & Reason', 'desc_ph' => 'Funding sub-safe / bank deposit...',
        'cancel' => 'Cancel', 'save' => 'Confirm & Issue Transfer', 'update' => 'Update Data', 'active_scope' => 'Active Branch:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-trf: #4f46e5; 
        --c-trf-dark: #4338ca; 
        --c-trf-light: #e0e7ff;
        --c-border: #cbd5e1; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text); margin-bottom: 20px; }

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
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--c-trf); background: #ffffff; box-shadow: 0 0 0 4px var(--c-trf-light); }

    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-trf), var(--c-trf-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/transfers" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-trf);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-trf); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-arrows-left-right"></i> <?= $t['panel_flow'] ?></h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label" style="color:#dc2626;"><?= $t['from_acc'] ?> <span style="color:red">*</span></label>
                    <select name="from_account_id" class="form-control" required style="border-color:#fca5a5;">
                        <option value=""><?= $t['choose_from'] ?></option>
                        <?php foreach($treasuryAccounts ?? [] as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && ($transfer->from_account_id ?? 0) == $acc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$acc->code) ?> - <?= htmlspecialchars((string)$acc->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="input-label" style="color:#059669;"><?= $t['to_acc'] ?> <span style="color:red">*</span></label>
                    <select name="to_account_id" class="form-control" required style="border-color:#6ee7b7;">
                        <option value=""><?= $t['choose_to'] ?></option>
                        <?php foreach($treasuryAccounts ?? [] as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && ($transfer->to_account_id ?? 0) == $acc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$acc->code) ?> - <?= htmlspecialchars((string)$acc->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-receipt"></i> <?= $t['panel_details'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['num'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="transfer_number" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-trf-dark);" value="<?= $isEdit ? htmlspecialchars((string)($transfer->transfer_number ?? '')) : htmlspecialchars((string)($autoNumber ?? '')) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="transfer_date" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($transfer->transfer_date ?? '')) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['amount'] ?> <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" style="font-family:monospace; font-weight:900; color:var(--c-trf-dark); font-size:1.2rem;" value="<?= $isEdit ? htmlspecialchars((string)($transfer->amount ?? '')) : '' ?>" placeholder="0.00" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['ref_no'] ?></label>
                    <input type="text" name="reference_no" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($transfer->reference_no ?? '')) : '' ?>" placeholder="<?= $t['ref_ph'] ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['description'] ?></label>
                    <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($transfer->description ?? '')) : '' ?>" placeholder="<?= $t['desc_ph'] ?>">
                </div>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/treasury/transfers" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>