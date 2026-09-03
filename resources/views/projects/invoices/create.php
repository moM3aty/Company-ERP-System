<?php
// Path: resources/views/projects/invoices/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();
$isEdit = isset($invoice) && $invoice !== null && !empty($invoice->id);
$actionUrl = $isEdit ? "/ERP/projects/invoices/{$invoice->id}/update" : "/ERP/projects/invoices/store";

$t = [
    'ar' => [
        'title_new' => 'إنشاء وإصدار مستخلص / فاتورة', 'title_edit' => 'تعديل المستخلص / الفاتورة', 'desc' => 'تحرير مطالبات الإنجاز والتفاصيل المالية للمشاريع.',
        'sec_basic' => 'البيانات الأساسية والمشروع', 'inv_num' => 'رقم المستخلص/الفاتورة', 'inv_type' => 'نوع المستخلص',
        'tp_adv' => 'دفعة مقدمة', 'tp_prog' => 'مستخلص جاري', 'tp_final' => 'مستخلص ختامي', 'tp_ret' => 'إفراج عن محتجزات',
        'project' => 'المشروع', 'select_proj' => '-- اختر المشروع --', 'customer' => 'العميل المالك للمشروع', 'select_cust' => '-- تعيين تلقائي من المشروع --',
        'inv_date' => 'تاريخ الإصدار', 'due_date' => 'تاريخ الاستحقاق', 'p_start' => 'بداية فترة المستخلص', 'p_end' => 'نهاية فترة المستخلص',
        'sec_fin' => 'الحسابات والقيم المالية', 'gross' => 'إجمالي الأعمال المنجزة (Gross)', 'deduct' => 'الخصومات والمحتجزات', 'tax' => 'ضريبة القيمة المضافة (VAT)',
        'net' => 'صافي المطالبة المستحقة (Net Claim)', 'status' => 'حالة المستخلص',
        'st_draft' => 'مسودة', 'st_sub' => 'مقدم للاعتماد', 'st_app' => 'معتمد للصرف', 'st_part' => 'مدفوع جزئياً', 'st_paid' => 'مسدد بالكامل', 'st_rej' => 'مرفوض',
        'paid_amt' => 'المبلغ المسدد حتى الآن', 'desc_label' => 'وصف ونطاق المستخلص', 'notes_label' => 'شروط وملاحظات السداد',
        'cancel' => 'إلغاء وتراجع', 'save' => 'حفظ وإصدار المستخلص', 'update' => 'تحديث المستخلص'
    ],
    'en' => [
        'title_new' => 'Create Claim / Invoice', 'title_edit' => 'Edit Claim / Invoice', 'desc' => 'Edit progress claims and financial details for projects.',
        'sec_basic' => 'Basic Details & Project', 'inv_num' => 'Claim/Invoice Number', 'inv_type' => 'Claim Type',
        'tp_adv' => 'Advance Payment', 'tp_prog' => 'Progress Claim', 'tp_final' => 'Final Claim', 'tp_ret' => 'Retention Release',
        'project' => 'Project', 'select_proj' => '-- Select Project --', 'customer' => 'Project Client/Owner', 'select_cust' => '-- Auto-assigned from project --',
        'inv_date' => 'Issue Date', 'due_date' => 'Due Date', 'p_start' => 'Period Start', 'p_end' => 'Period End',
        'sec_fin' => 'Calculations & Financials', 'gross' => 'Gross Amount', 'deduct' => 'Deductions & Retentions', 'tax' => 'VAT Amount',
        'net' => 'Net Claim Amount', 'status' => 'Status',
        'st_draft' => 'Draft', 'st_sub' => 'Submitted', 'st_app' => 'Approved', 'st_part' => 'Partially Paid', 'st_paid' => 'Fully Paid', 'st_rej' => 'Rejected',
        'paid_amt' => 'Amount Paid So Far', 'desc_label' => 'Scope & Description', 'notes_label' => 'Payment Terms & Notes',
        'cancel' => 'Cancel', 'save' => 'Save & Issue Claim', 'update' => 'Update Claim'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { --c-pi: #059669; --c-pi-dark: #047857; --c-pi-light: #ecfdf5; --c-border: #e2e8f0; --c-text: #0f172a; --c-bg: #f8fafc; }
    .form-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #64748b; font-size: 1.2rem; transition: 0.2s;}
    .back-btn:hover { background: var(--c-pi-light); color: var(--c-pi); border-color: #a7f3d0; }

    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; border-top: 4px solid var(--c-pi); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; display: flex; align-items: center; gap: 8px;}
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; background: var(--c-bg); font-weight: 600; font-family:inherit; transition: 0.2s;}
    .form-control:focus { border-color: var(--c-pi); box-shadow: 0 0 0 4px var(--c-pi-light); outline: none; background: #fff;}
    
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn-submit { background: linear-gradient(135deg, var(--c-pi), var(--c-pi-dark)); color: white; border: none; padding: 12px 28px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: #475569; padding: 12px 28px; border-radius: 10px; font-weight: 800; text-decoration: none; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/invoices" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
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
            <h3 class="section-title"><i class="ph-duotone ph-file-text" style="color:var(--c-pi);"></i> <?= $t['sec_basic'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['inv_num'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="invoice_number" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-pi-dark);" value="<?= $isEdit ? htmlspecialchars($invoice->invoice_number) : htmlspecialchars($autoCode) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['inv_type'] ?> <span style="color:red">*</span></label>
                    <select name="invoice_type" class="form-control" required>
                        <?php $tp = $isEdit ? $invoice->invoice_type : 'progress_claim'; ?>
                        <option value="progress_claim" <?= $tp === 'progress_claim' ? 'selected' : '' ?>><?= $t['tp_prog'] ?></option>
                        <option value="advance_payment" <?= $tp === 'advance_payment' ? 'selected' : '' ?>><?= $t['tp_adv'] ?></option>
                        <option value="final_claim" <?= $tp === 'final_claim' ? 'selected' : '' ?>><?= $t['tp_final'] ?></option>
                        <option value="retention_release" <?= $tp === 'retention_release' ? 'selected' : '' ?>><?= $t['tp_ret'] ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['project'] ?> <span style="color:red">*</span></label>
                    <select name="project_id" class="form-control" required>
                        <option value=""><?= $t['select_proj'] ?></option>
                        <?php foreach($projects as $p): ?>
                            <option value="<?= $p->id ?>" <?= ($isEdit && $invoice->project_id == $p->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p->code) ?> - <?= htmlspecialchars($p->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['customer'] ?></label>
                    <select name="customer_id" class="form-control">
                        <option value=""><?= $t['select_cust'] ?></option>
                        <?php foreach($customers as $c): ?>
                            <option value="<?= $c->id ?>" <?= ($isEdit && $invoice->customer_id == $c->id) ? 'selected' : '' ?>><?= htmlspecialchars($c->name_ar) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['inv_date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="invoice_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($invoice->invoice_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['due_date'] ?></label>
                    <input type="date" name="due_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($invoice->due_date ?? '') : date('Y-m-d', strtotime('+15 days')) ?>">
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['p_start'] ?></label>
                    <input type="date" name="period_start" class="form-control" value="<?= $isEdit ? htmlspecialchars($invoice->period_start ?? '') : date('Y-m-01') ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['p_end'] ?></label>
                    <input type="date" name="period_end" class="form-control" value="<?= $isEdit ? htmlspecialchars($invoice->period_end ?? '') : date('Y-m-t') ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-calculator" style="color:var(--c-pi);"></i> <?= $t['sec_fin'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['gross'] ?> <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0" name="total_amount" id="total_amount" class="form-control" style="font-family:monospace; font-weight:900; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($invoice->total_amount) : '0.00' ?>" oninput="calculateNet()" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['deduct'] ?></label>
                    <input type="number" step="0.01" min="0" name="deductions_amount" id="deductions_amount" class="form-control" style="font-family:monospace; font-weight:900; color:#dc2626; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($invoice->deductions_amount) : '0.00' ?>" oninput="calculateNet()">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['tax'] ?></label>
                    <input type="number" step="0.01" min="0" name="tax_amount" id="tax_amount" class="form-control" style="font-family:monospace; font-weight:900; color:#0284c7; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($invoice->tax_amount) : '0.00' ?>" oninput="calculateNet()">
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px; background:#f0fdf4; padding:20px; border-radius:12px; border:1px solid #a7f3d0;">
                <div class="form-group">
                    <label class="input-label" style="color:var(--c-pi-dark); font-size:1rem;"><?= $t['net'] ?> (<?= $currency ?>)</label>
                    <input type="text" id="net_amount_display" class="form-control" style="font-family:monospace; font-weight:900; color:var(--c-pi-dark); font-size:1.4rem;" value="<?= $isEdit ? number_format((float)$invoice->net_amount, 2, '.', '') : '0.00' ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label class="input-label"><?= $t['status'] ?> <span style="color:red">*</span></label>
                    <select name="status" class="form-control" style="font-weight: 800;" required>
                        <?php $st = $isEdit ? $invoice->status : 'submitted'; ?>
                        <option value="draft" <?= $st === 'draft' ? 'selected' : '' ?>><?= $t['st_draft'] ?></option>
                        <option value="submitted" <?= $st === 'submitted' ? 'selected' : '' ?>><?= $t['st_sub'] ?></option>
                        <option value="approved" <?= $st === 'approved' ? 'selected' : '' ?>><?= $t['st_app'] ?></option>
                        <option value="partially_paid" <?= $st === 'partially_paid' ? 'selected' : '' ?>><?= $t['st_part'] ?></option>
                        <option value="paid" <?= $st === 'paid' ? 'selected' : '' ?>><?= $t['st_paid'] ?></option>
                        <option value="rejected" <?= $st === 'rejected' ? 'selected' : '' ?>><?= $t['st_rej'] ?></option>
                    </select>
                </div>
            </div>

            <?php if($isEdit): ?>
                <div class="form-group" style="margin-top:16px;">
                    <label class="input-label"><?= $t['paid_amt'] ?> (<?= $currency ?>)</label>
                    <input type="number" step="0.01" min="0" name="paid_amount" class="form-control" style="font-family:monospace; font-weight:900; color:#059669;" value="<?= htmlspecialchars($invoice->paid_amount) ?>">
                </div>
            <?php endif; ?>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['desc_label'] ?></label>
                    <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars($invoice->description ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['notes_label'] ?></label>
                    <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($invoice->notes ?? '') : '' ?>">
                </div>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/projects/invoices" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>

<script>
function calculateNet() {
    let gross = parseFloat(document.getElementById('total_amount').value) || 0;
    let deductions = parseFloat(document.getElementById('deductions_amount').value) || 0;
    let tax = parseFloat(document.getElementById('tax_amount').value) || 0;
    
    let net = Math.max(0, (gross - deductions) + tax);
    document.getElementById('net_amount_display').value = net.toFixed(2);
}
</script>