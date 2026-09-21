<?php
// Path: resources/views/treasury/cheques/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$isEdit = isset($cheque) && $cheque !== null && !empty($cheque->id);
$actionUrl = $isEdit ? "/ERP/treasury/cheques/" . (int)$cheque->id . "/update" : "/ERP/treasury/cheques/store";

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title_new' => 'تسجيل ورقة مالية / شيك جديد', 'title_edit' => 'تعديل بيانات الشيك',
        'desc' => 'إدخال بيانات الشيك والبنك وتواريخ الاستحقاق.', 'panel_basic' => 'البيانات المباشرة للشيك',
        'type' => 'نوع الشيك', 'type_recv' => 'شيك وارد (استلام من عميل)', 'type_iss' => 'شيك صادر (دفع لمورد)',
        'num' => 'رقم الشيك الورقي', 'num_ph' => 'مثال: CHQ-88019', 'amount' => "قيمة الشيك (بـ $currency)",
        'bank' => 'اسم البنك المسحوب عليه', 'bank_ph' => 'مثال: البنك الأهلي / بنك مصر / الراجحي...',
        'account' => 'الحساب / الخزينة المرتبطة', 'choose_acc' => '-- اختر الحساب المالي --',
        'panel_dates' => 'الأطراف والتواريخ', 'payee' => 'اسم المستفيد / الساحب', 'payee_ph' => 'اسم الشخص أو الجهة المدونة بالشيك',
        'issue_date' => 'تاريخ تحرير الشيك', 'due_date' => 'تاريخ الاستحقاق للصرف',
        'notes' => 'ملاحظات وشروط الشيك', 'notes_ph' => 'ملاحظات تفصيلية أو رقم العقد المربوط...',
        'cancel' => 'إلغاء وتراجع', 'save' => 'حفظ وتسجيل الشيك', 'update' => 'تحديث بيانات الشيك', 'active_scope' => 'الفرع النشط:'
    ],
    'en' => [
        'title_new' => 'Register New Cheque', 'title_edit' => 'Edit Cheque Data',
        'desc' => 'Enter cheque details, bank, and due dates.', 'panel_basic' => 'Cheque Basic Details',
        'type' => 'Cheque Type', 'type_recv' => 'Received (From Customer)', 'type_iss' => 'Issued (To Supplier)',
        'num' => 'Cheque Number', 'num_ph' => 'e.g. CHQ-88019', 'amount' => "Cheque Amount (in $currency)",
        'bank' => 'Drawee Bank Name', 'bank_ph' => 'e.g. NCB, Al Rajhi, Banque Misr...',
        'account' => 'Linked Account / Safe', 'choose_acc' => '-- Select Financial Account --',
        'panel_dates' => 'Parties & Dates', 'payee' => 'Payee / Payer Name', 'payee_ph' => 'Name written on the cheque',
        'issue_date' => 'Issue Date', 'due_date' => 'Due Date',
        'notes' => 'Notes & Conditions', 'notes_ph' => 'Detailed notes or contract link...',
        'cancel' => 'Cancel', 'save' => 'Register Cheque', 'update' => 'Update Cheque', 'active_scope' => 'Active Branch:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-chq: #c026d3; 
        --c-chq-dark: #a21caf; 
        --c-chq-light: #fdf4ff;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text); margin-bottom: 20px; }

    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-chq); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-chq); font-size: 1.4rem; padding: 8px; background: var(--c-chq-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--c-chq); background: #ffffff; box-shadow: 0 0 0 4px var(--c-chq-light); }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-chq), var(--c-chq-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/cheques" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-chq);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-chq); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-checks"></i> <?= $t['panel_basic'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['type'] ?> <span style="color:red">*</span></label>
                    <select name="type" class="form-control" required>
                        <?php $ct = $isEdit ? ($cheque->type ?? 'received') : 'received'; ?>
                        <option value="received" <?= $ct === 'received' ? 'selected' : '' ?>><?= $t['type_recv'] ?></option>
                        <option value="issued" <?= $ct === 'issued' ? 'selected' : '' ?>><?= $t['type_iss'] ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['num'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="cheque_number" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-chq-dark);" value="<?= $isEdit ? htmlspecialchars((string)($cheque->cheque_number ?? '')) : '' ?>" placeholder="<?= $t['num_ph'] ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['amount'] ?> <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" style="font-family:monospace; font-weight:900; color:var(--c-chq-dark); font-size:1.2rem;" value="<?= $isEdit ? htmlspecialchars((string)($cheque->amount ?? '')) : '' ?>" placeholder="0.00" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['bank'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="bank_name" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($cheque->bank_name ?? '')) : '' ?>" placeholder="<?= $t['bank_ph'] ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['account'] ?> <span style="color:red">*</span></label>
                    <select name="treasury_account_id" class="form-control" required>
                        <option value=""><?= $t['choose_acc'] ?></option>
                        <?php foreach($treasuryAccounts ?? [] as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && ($cheque->treasury_account_id ?? 0) == $acc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$acc->code) ?> - <?= htmlspecialchars((string)$acc->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-calendar"></i> <?= $t['panel_dates'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['payee'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="payee_payer_name" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($cheque->payee_payer_name ?? '')) : '' ?>" placeholder="<?= $t['payee_ph'] ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['issue_date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="issue_date" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($cheque->issue_date ?? '')) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['due_date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="due_date" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($cheque->due_date ?? '')) : date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label"><?= $t['notes'] ?></label>
                <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($cheque->notes ?? '')) : '' ?>" placeholder="<?= $t['notes_ph'] ?>">
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/treasury/cheques" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>