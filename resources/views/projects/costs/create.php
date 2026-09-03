<?php
// Path: resources/views/projects/costs/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();
$isEdit = isset($cost) && $cost !== null && !empty($cost->id);
$actionUrl = $isEdit ? "/ERP/projects/costs/{$cost->id}/update" : "/ERP/projects/costs/store";

$t = [
    'ar' => [
        'title_new' => 'تسجيل مصروف موقع جديد', 'title_edit' => 'تعديل سند مصروف الموقع', 'desc' => 'ربط النفقات والمواد ببطاقة المشروع وتحديث التكاليف الفعلية.',
        'sec_basic' => 'بيانات المصروف والمشروع', 'voucher' => 'رقم السند/المصروف', 'project' => 'المشروع', 'select_proj' => '-- اختر المشروع --',
        'category' => 'تبويب التكلفة', 'cat_mat' => 'مواد وتوريدات', 'cat_lab' => 'عمالة وأجور', 'cat_eq' => 'معدات وآليات', 'cat_sub' => 'مقاولين فرعيين', 'cat_over' => 'مصروفات إدارية', 'cat_oth' => 'مصروفات أخرى',
        'amt' => 'قيمة المصروف (المبلغ)', 'date' => 'تاريخ الصرف', 'ref' => 'الرقم المرجعي / الإيصال',
        'sec_supp' => 'المورد والتسوية المالية', 'supplier' => 'المورد / المقاول الفرعي', 'select_sup' => '-- شراء مباشر / غير محدد --',
        'account' => 'الحساب المالي المصدر / الصندوق', 'select_acc' => '-- صندوق/خزينة الموقع --',
        'status' => 'حالة السداد والتحصيل', 'st_paid' => 'مسدد بالكامل', 'st_part' => 'مسدد جزئياً', 'st_unpaid' => 'غير مسدد / آجل',
        'desc_label' => 'البيان والوصف التفصيلي', 'notes_label' => 'ملاحظات إضافية',
        'cancel' => 'إلغاء وتراجع', 'save' => 'حفظ وتسجيل المصروف', 'update' => 'تحديث المصروف'
    ],
    'en' => [
        'title_new' => 'Log New Site Expense', 'title_edit' => 'Edit Site Expense Voucher', 'desc' => 'Link expenses to a project and update actual site costs.',
        'sec_basic' => 'Expense & Project Details', 'voucher' => 'Voucher Number', 'project' => 'Project', 'select_proj' => '-- Select Project --',
        'category' => 'Cost Category', 'cat_mat' => 'Materials', 'cat_lab' => 'Labor', 'cat_eq' => 'Equipment', 'cat_sub' => 'Subcontractors', 'cat_over' => 'Overhead', 'cat_oth' => 'Other',
        'amt' => 'Expense Amount', 'date' => 'Cost Date', 'ref' => 'Reference No. / Receipt',
        'sec_supp' => 'Supplier & Financial Settlement', 'supplier' => 'Supplier / Subcontractor', 'select_sup' => '-- Direct Purchase --',
        'account' => 'Source Financial Account', 'select_acc' => '-- Site Petty Cash --',
        'status' => 'Payment Status', 'st_paid' => 'Fully Paid', 'st_part' => 'Partially Paid', 'st_unpaid' => 'Unpaid / Credit',
        'desc_label' => 'Description / Details', 'notes_label' => 'Additional Notes',
        'cancel' => 'Cancel', 'save' => 'Save Expense', 'update' => 'Update Expense'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { --c-pcost: #ea580c; --c-pcost-dark: #c2410c; --c-pcost-light: #ffedd5; --c-border: #e2e8f0; --c-text: #0f172a; --c-bg: #f8fafc; }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #64748b; font-size: 1.2rem; transition: 0.2s;}
    .back-btn:hover { background: var(--c-pcost-light); color: var(--c-pcost); border-color: #ffedd5; }

    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; border-top: 4px solid var(--c-pcost); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; display: flex; align-items: center; gap: 8px;}
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; background: var(--c-bg); font-weight: 600; font-family:inherit; transition: 0.2s;}
    .form-control:focus { border-color: var(--c-pcost); box-shadow: 0 0 0 4px var(--c-pcost-light); outline: none; background: #fff;}
    
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn-submit { background: linear-gradient(135deg, var(--c-pcost), var(--c-pcost-dark)); color: white; border: none; padding: 12px 28px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: #475569; padding: 12px 28px; border-radius: 10px; font-weight: 800; text-decoration: none; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/costs" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
                <p style="margin:4px 0 0 0; color:#64748b; font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-currency-dollar" style="color:var(--c-pcost);"></i> <?= $t['sec_basic'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['voucher'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="voucher_number" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-pcost-dark);" value="<?= $isEdit ? htmlspecialchars($cost->voucher_number) : htmlspecialchars($autoCode) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['project'] ?> <span style="color:red">*</span></label>
                    <select name="project_id" class="form-control" required>
                        <option value=""><?= $t['select_proj'] ?></option>
                        <?php foreach($projects as $p): ?>
                            <option value="<?= $p->id ?>" <?= ($isEdit && $cost->project_id == $p->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p->code) ?> - <?= htmlspecialchars($p->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['category'] ?> <span style="color:red">*</span></label>
                    <select name="cost_category" class="form-control" required>
                        <?php $ct = $isEdit ? $cost->cost_category : 'materials'; ?>
                        <option value="materials" <?= $ct === 'materials' ? 'selected' : '' ?>><?= $t['cat_mat'] ?></option>
                        <option value="labor" <?= $ct === 'labor' ? 'selected' : '' ?>><?= $t['cat_lab'] ?></option>
                        <option value="equipment" <?= $ct === 'equipment' ? 'selected' : '' ?>><?= $t['cat_eq'] ?></option>
                        <option value="subcontractor" <?= $ct === 'subcontractor' ? 'selected' : '' ?>><?= $t['cat_sub'] ?></option>
                        <option value="overhead" <?= $ct === 'overhead' ? 'selected' : '' ?>><?= $t['cat_over'] ?></option>
                        <option value="other" <?= $ct === 'other' ? 'selected' : '' ?>><?= $t['cat_oth'] ?></option>
                    </select>
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['amt'] ?> (<?= $currency ?>) <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" style="font-family:monospace; font-weight:900; color:#dc2626; font-size:1.2rem;" value="<?= $isEdit ? htmlspecialchars($cost->amount) : '' ?>" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="cost_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($cost->cost_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['ref'] ?></label>
                    <input type="text" name="reference_no" class="form-control" style="font-family:monospace;" value="<?= $isEdit ? htmlspecialchars($cost->reference_no ?? '') : '' ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-buildings" style="color:var(--c-pcost);"></i> <?= $t['sec_supp'] ?></h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label"><?= $t['supplier'] ?></label>
                    <select name="supplier_id" class="form-control">
                        <option value=""><?= $t['select_sup'] ?></option>
                        <?php foreach($suppliers as $s): ?>
                            <option value="<?= $s->id ?>" <?= ($isEdit && $cost->supplier_id == $s->id) ? 'selected' : '' ?>><?= htmlspecialchars($s->name_ar) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['account'] ?></label>
                    <select name="account_id" class="form-control">
                        <option value=""><?= $t['select_acc'] ?></option>
                        <?php foreach($accounts as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && $cost->account_id == $acc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label"><?= $t['status'] ?> <span style="color:red">*</span></label>
                <select name="payment_status" class="form-control" style="font-weight:800;" required>
                    <?php $ps = $isEdit ? $cost->payment_status : 'paid'; ?>
                    <option value="paid" <?= $ps === 'paid' ? 'selected' : '' ?>><?= $t['st_paid'] ?></option>
                    <option value="partially_paid" <?= $ps === 'partially_paid' ? 'selected' : '' ?>><?= $t['st_part'] ?></option>
                    <option value="unpaid" <?= $ps === 'unpaid' ? 'selected' : '' ?>><?= $t['st_unpaid'] ?></option>
                </select>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['desc_label'] ?></label>
                    <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars($cost->description ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['notes_label'] ?></label>
                    <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($cost->notes ?? '') : '' ?>">
                </div>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/projects/costs" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>