<?php
// Path: resources/views/projects/invoices/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($invoice) && $invoice !== null && !empty($invoice->id);
$actionUrl = $isEdit ? "/ERP/projects/invoices/{$invoice->id}/update" : "/ERP/projects/invoices/store";
?>

<style>
    :root { 
        --c-pi: #059669; 
        --c-pi-dark: #047857; 
        --c-pi-light: #ecfdf5;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-pi); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-pi); font-size: 1.4rem; padding: 8px; background: var(--c-pi-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-pi), var(--c-pi-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/invoices" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل المستخلص / الفاتورة' : 'إنشاء وإصدار مستخلص / فاتورة جديدة' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">تحرير مطالبات الإنجاز الجارية والختامية والتفاصيل المالية.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-file-text"></i> البيانات التعريفية والمشروع</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">رقم المستخلص/الفاتورة <span style="color:red">*</span></label>
                    <input type="text" name="invoice_number" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-pi-dark);" value="<?= $isEdit ? htmlspecialchars($invoice->invoice_number) : htmlspecialchars($autoCode) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label class="input-label">نوع المستخلص <span style="color:red">*</span></label>
                    <select name="invoice_type" class="form-control" required>
                        <option value="progress_claim" <?= (!$isEdit || $invoice->invoice_type === 'progress_claim') ? 'selected' : '' ?>>مستخلص جاري (Progress Claim)</option>
                        <option value="advance_payment" <?= ($isEdit && $invoice->invoice_type === 'advance_payment') ? 'selected' : '' ?>>دفعة مقدمة (Advance Payment)</option>
                        <option value="final_claim" <?= ($isEdit && $invoice->invoice_type === 'final_claim') ? 'selected' : '' ?>>مستخلص ختامي (Final Claim)</option>
                        <option value="retention_release" <?= ($isEdit && $invoice->invoice_type === 'retention_release') ? 'selected' : '' ?>>إفراج عن محتجزات (Retention Release)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">المشروع <span style="color:red">*</span></label>
                    <select name="project_id" class="form-control" required>
                        <option value="">-- اختر المشروع --</option>
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
                    <label class="input-label">العميل المالك للمشروع</label>
                    <select name="customer_id" class="form-control">
                        <option value="">-- تعيين تلقائي من المشروع --</option>
                        <?php foreach($customers as $c): ?>
                            <option value="<?= $c->id ?>" <?= ($isEdit && $invoice->customer_id == $c->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ الإصدار <span style="color:red">*</span></label>
                    <input type="date" name="invoice_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($invoice->invoice_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ الاستحقاق للدفع</label>
                    <input type="date" name="due_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($invoice->due_date ?? '') : date('Y-m-d', strtotime('+15 days')) ?>">
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">بداية فترة المستخلص</label>
                    <input type="date" name="period_start" class="form-control" value="<?= $isEdit ? htmlspecialchars($invoice->period_start ?? '') : date('Y-m-01') ?>">
                </div>
                <div class="form-group">
                    <label class="input-label">نهاية فترة المستخلص</label>
                    <input type="date" name="period_end" class="form-control" value="<?= $isEdit ? htmlspecialchars($invoice->period_end ?? '') : date('Y-m-t') ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-calculator"></i> الحسابات والقيم المالية</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">إجمالي الأعمال المنجزة (Gross) <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0" name="total_amount" id="total_amount" class="form-control" style="font-family:monospace; font-weight:900; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($invoice->total_amount) : '0.00' ?>" oninput="calculateNet()" required>
                </div>
                <div class="form-group">
                    <label class="input-label">الخصومات / تأمين أعمال / دفعة مقدمة</label>
                    <input type="number" step="0.01" min="0" name="deductions_amount" id="deductions_amount" class="form-control" style="font-family:monospace; font-weight:900; color:#dc2626; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($invoice->deductions_amount) : '0.00' ?>" oninput="calculateNet()">
                </div>
                <div class="form-group">
                    <label class="input-label">قيمة ضريبة القيمة المضافة (VAT)</label>
                    <input type="number" step="0.01" min="0" name="tax_amount" id="tax_amount" class="form-control" style="font-family:monospace; font-weight:900; color:#0284c7; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($invoice->tax_amount) : '0.00' ?>" oninput="calculateNet()">
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px; background:#f0fdf4; padding:20px; border-radius:12px; border:1px solid #a7f3d0;">
                <div class="form-group">
                    <label class="input-label" style="color:var(--c-pi-dark); font-size:1rem;">صافي المطالبة المستحقة (Net Claim)</label>
                    <input type="text" id="net_amount_display" class="form-control" style="font-family:monospace; font-weight:900; color:var(--c-pi-dark); font-size:1.4rem;" value="<?= $isEdit ? number_format((float)$invoice->net_amount, 2) : '0.00' ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label class="input-label">حالة المستخلص <span style="color:red">*</span></label>
                    <select name="status" class="form-control" required>
                        <option value="draft" <?= ($isEdit && $invoice->status === 'draft') ? 'selected' : '' ?>>مسودة (Draft)</option>
                        <option value="submitted" <?= (!$isEdit || $invoice->status === 'submitted') ? 'selected' : '' ?>>مقدم للاعتماد (Submitted)</option>
                        <option value="approved" <?= ($isEdit && $invoice->status === 'approved') ? 'selected' : '' ?>>معتمد للصرف (Approved)</option>
                        <option value="partially_paid" <?= ($isEdit && $invoice->status === 'partially_paid') ? 'selected' : '' ?>>مدفوع جزئياً (Partially Paid)</option>
                        <option value="paid" <?= ($isEdit && $invoice->status === 'paid') ? 'selected' : '' ?>>مسدد بالكامل (Paid)</option>
                        <option value="rejected" <?= ($isEdit && $invoice->status === 'rejected') ? 'selected' : '' ?>>مرفوض (Rejected)</option>
                    </select>
                </div>
            </div>

            <?php if($isEdit): ?>
                <div class="form-group" style="margin-top:16px;">
                    <label class="input-label">المبلغ المسدد حتى الآن</label>
                    <input type="number" step="0.01" min="0" name="paid_amount" class="form-control" style="font-family:monospace; font-weight:900; color:#059669;" value="<?= htmlspecialchars($invoice->paid_amount) ?>">
                </div>
            <?php endif; ?>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">وصف ونطاق المستخلص</label>
                    <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars($invoice->description ?? '') : '' ?>" placeholder="عن أعمال الصبة الخرسانية / الهيكل الانشائي...">
                </div>
                <div class="form-group">
                    <label class="input-label">شروط وملاحظات السداد</label>
                    <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($invoice->notes ?? '') : '' ?>" placeholder="رقم الحساب البنكي للتحويل / شروط الدفع...">
                </div>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/projects/invoices" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث المستخلص' : 'حفظ وإصدار المستخلص' ?></button>
        </div>
    </form>
</div>

<script>
function calculateNet() {
    let gross = parseFloat(document.getElementById('total_amount').value) || 0;
    let deductions = parseFloat(document.getElementById('deductions_amount').value) || 0;
    let tax = parseFloat(document.getElementById('tax_amount').value) || 0;
    
    let net = Math.max(0, (gross - deductions) + tax);
    document.getElementById('net_amount_display').value = net.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>