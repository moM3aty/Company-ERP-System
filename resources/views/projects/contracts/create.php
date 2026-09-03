<?php
// Path: resources/views/projects/contracts/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();
$isEdit = isset($contract) && $contract !== null && !empty($contract->id);
$actionUrl = $isEdit ? "/ERP/projects/contracts/{$contract->id}/update" : "/ERP/projects/contracts/store";

$t = [
    'ar' => [
        'title_new' => 'تسجيل وإصدار عقد مشروع جديد', 'title_edit' => 'تعديل بيانات العقد', 'desc' => 'تحرير شروط العقد، القيم المالية، نسب ضمان حسن التنفيذ والتواريخ.',
        'sec_basic' => 'البيانات التعريفية للعقد', 'contract_num' => 'رقم العقد', 'contract_type' => 'نوع العقد',
        'tp_owner' => 'عقد المالك الرئيسي', 'tp_sub' => 'عقد مقاول فرعي', 'tp_cons' => 'عقد استشاري', 'tp_supp' => 'عقد توريد مواد',
        'project' => 'المشروع التابع له', 'select_proj' => '-- اختر المشروع --', 'title_ar' => 'عنوان العقد (عربي)', 'title_en' => 'عنوان العقد (إنجليزي)',
        'customer' => 'العميل / مالك العقد', 'select_cust' => '-- تعيين تلقائي من العميل المسجل بالمشروع --',
        'sec_fin' => 'القيمة المادية وضمانات التنفيذ', 'val' => 'قيمة العقد الكلية', 'adv_pay' => 'قيمة الدفعة المقدمة (Advance Payment)', 'retention' => 'نسبة استقطاع ضمان حسن التنفيذ (%)',
        'sign_date' => 'تاريخ توقيع العقد', 'start_date' => 'تاريخ بدء سريان العقد', 'end_date' => 'تاريخ تسليم وانتهاء العقد',
        'status' => 'حالة العقد', 'st_active' => 'ساري (Active)', 'st_draft' => 'مسودة (Draft)', 'st_renewal' => 'قيد التجديد', 'st_completed' => 'مكتمل (Completed)', 'st_suspended' => 'موقف (Suspended)', 'st_terminated' => 'مفسوخ (Terminated)',
        'terms' => 'الشروط والبنود الالتزامية', 'terms_ph' => 'البنود الجزائية، شروط الصرف...', 'notes' => 'ملاحظات إضافية',
        'cancel' => 'إلغاء وتراجع', 'save' => 'حفظ وتسجيل العقد', 'update' => 'تحديث العقد'
    ],
    'en' => [
        'title_new' => 'Issue New Project Contract', 'title_edit' => 'Edit Contract Data', 'desc' => 'Edit contract terms, financials, retentions, and dates.',
        'sec_basic' => 'Contract Identity & Info', 'contract_num' => 'Contract Number', 'contract_type' => 'Contract Type',
        'tp_owner' => 'Main Owner Contract', 'tp_sub' => 'Subcontractor Contract', 'tp_cons' => 'Consultant Contract', 'tp_supp' => 'Supply Contract',
        'project' => 'Linked Project', 'select_proj' => '-- Select Project --', 'title_ar' => 'Contract Title (AR)', 'title_en' => 'Contract Title (EN)',
        'customer' => 'Client / Owner', 'select_cust' => '-- Auto-assigned from project --',
        'sec_fin' => 'Financials & Guarantees', 'val' => 'Total Contract Value', 'adv_pay' => 'Advance Payment Amount', 'retention' => 'Retention Guarantee (%)',
        'sign_date' => 'Signing Date', 'start_date' => 'Start Date', 'end_date' => 'End / Delivery Date',
        'status' => 'Contract Status', 'st_active' => 'Active', 'st_draft' => 'Draft', 'st_renewal' => 'Under Renewal', 'st_completed' => 'Completed', 'st_suspended' => 'Suspended', 'st_terminated' => 'Terminated',
        'terms' => 'Terms & Conditions', 'terms_ph' => 'Penalties, payment terms...', 'notes' => 'Additional Notes',
        'cancel' => 'Cancel', 'save' => 'Save Contract', 'update' => 'Update Contract'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { --c-pcontract: #be123c; --c-pcontract-dark: #9f1239; --c-pcontract-light: #fff1f2; --c-border: #e2e8f0; --c-text: #0f172a; --c-bg: #f8fafc; }
    .form-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #64748b; font-size: 1.2rem; transition: 0.2s;}
    .back-btn:hover { background: var(--c-pcontract-light); color: var(--c-pcontract); border-color: #fecdd3; }

    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; border-top: 4px solid var(--c-pcontract); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; display: flex; align-items: center; gap: 8px;}
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; background: var(--c-bg); font-weight: 600; font-family:inherit; transition: 0.2s;}
    .form-control:focus { border-color: var(--c-pcontract); box-shadow: 0 0 0 4px var(--c-pcontract-light); outline: none; background: #fff;}
    
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn-submit { background: linear-gradient(135deg, var(--c-pcontract), var(--c-pcontract-dark)); color: white; border: none; padding: 12px 28px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(190, 18, 60, 0.25); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: #475569; padding: 12px 28px; border-radius: 10px; font-weight: 800; text-decoration: none; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/contracts" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
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
            <h3 class="section-title"><i class="ph-duotone ph-file-search" style="color:var(--c-pcontract);"></i> <?= $t['sec_basic'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['contract_num'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="contract_number" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-pcontract-dark);" value="<?= $isEdit ? htmlspecialchars($contract->contract_number) : htmlspecialchars($autoCode) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['contract_type'] ?> <span style="color:red">*</span></label>
                    <select name="contract_type" class="form-control" required>
                        <?php $ctype = $isEdit ? $contract->contract_type : 'owner_contract'; ?>
                        <option value="owner_contract" <?= $ctype === 'owner_contract' ? 'selected' : '' ?>><?= $t['tp_owner'] ?></option>
                        <option value="subcontractor_contract" <?= $ctype === 'subcontractor_contract' ? 'selected' : '' ?>><?= $t['tp_sub'] ?></option>
                        <option value="consultant_contract" <?= $ctype === 'consultant_contract' ? 'selected' : '' ?>><?= $t['tp_cons'] ?></option>
                        <option value="supply_contract" <?= $ctype === 'supply_contract' ? 'selected' : '' ?>><?= $t['tp_supp'] ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['project'] ?> <span style="color:red">*</span></label>
                    <select name="project_id" class="form-control" required>
                        <option value=""><?= $t['select_proj'] ?></option>
                        <?php foreach($projects as $p): ?>
                            <option value="<?= $p->id ?>" <?= ($isEdit && $contract->project_id == $p->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p->code) ?> - <?= htmlspecialchars($p->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['title_ar'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="title_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->title_ar) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['title_en'] ?></label>
                    <input type="text" name="title_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->title_en ?? '') : '' ?>">
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label"><?= $t['customer'] ?></label>
                <select name="customer_id" class="form-control">
                    <option value=""><?= $t['select_cust'] ?></option>
                    <?php foreach($customers as $c): ?>
                        <option value="<?= $c->id ?>" <?= ($isEdit && $contract->customer_id == $c->id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c->name_ar) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-currency-circle-dollar" style="color:var(--c-pcontract);"></i> <?= $t['sec_fin'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['val'] ?> (<?= $currency ?>) <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0" name="contract_value" class="form-control" style="font-family:monospace; font-weight:900; color:var(--c-pcontract-dark); font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($contract->contract_value) : '0.00' ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['adv_pay'] ?> (<?= $currency ?>)</label>
                    <input type="number" step="0.01" min="0" name="advance_payment_amount" class="form-control" style="font-family:monospace; font-weight:900; color:#059669; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($contract->advance_payment_amount) : '0.00' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['retention'] ?></label>
                    <input type="number" step="0.1" min="0" max="100" name="retention_percent" class="form-control" style="font-family:monospace; font-weight:900; color:#d97706; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($contract->retention_percent) : '0.0' ?>">
                </div>
            </div>

            <div class="grid-3" style="margin-top:24px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['sign_date'] ?></label>
                    <input type="date" name="sign_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->sign_date ?? '') : date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['start_date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->start_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['end_date'] ?></label>
                    <input type="date" name="end_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->end_date ?? '') : date('Y-m-d', strtotime('+1 year')) ?>">
                </div>
            </div>

            <div class="form-group" style="margin-top:24px;">
                <label class="input-label"><?= $t['status'] ?> <span style="color:red">*</span></label>
                <select name="status" class="form-control" style="font-weight:800;" required>
                    <?php $st = $isEdit ? $contract->status : 'active'; ?>
                    <option value="active" <?= $st === 'active' ? 'selected' : '' ?>><?= $t['st_active'] ?></option>
                    <option value="draft" <?= $st === 'draft' ? 'selected' : '' ?>><?= $t['st_draft'] ?></option>
                    <option value="under_renewal" <?= $st === 'under_renewal' ? 'selected' : '' ?>><?= $t['st_renewal'] ?></option>
                    <option value="completed" <?= $st === 'completed' ? 'selected' : '' ?>><?= $t['st_completed'] ?></option>
                    <option value="suspended" <?= $st === 'suspended' ? 'selected' : '' ?>><?= $t['st_suspended'] ?></option>
                    <option value="terminated" <?= $st === 'terminated' ? 'selected' : '' ?>><?= $t['st_terminated'] ?></option>
                </select>
            </div>

            <div class="form-group" style="margin-top:24px;">
                <label class="input-label"><?= $t['terms'] ?></label>
                <textarea name="terms_and_conditions" class="form-control" rows="3" placeholder="<?= $t['terms_ph'] ?>"><?= $isEdit ? htmlspecialchars($contract->terms_and_conditions ?? '') : '' ?></textarea>
            </div>

            <div class="form-group" style="margin-top:16px;">
                <label class="input-label"><?= $t['notes'] ?></label>
                <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->notes ?? '') : '' ?>">
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/projects/contracts" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>