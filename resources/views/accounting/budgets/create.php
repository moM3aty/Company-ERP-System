<?php
// Path: resources/views/accounting/budgets/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($budget) && $budget !== null && !empty($budget->id);
$actionUrl = $isEdit ? "/ERP/accounting/budgets/{$budget->id}/update" : "/ERP/accounting/budgets/store";
?>

<style>
    :root { 
        --c-bg: #e11d48; 
        --c-bg-dark: #be123c; 
        --c-bg-light: #ffe4e6;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 1050px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-bg); font-size: 1.4rem; padding: 8px; background: var(--c-bg-light); border-radius: 8px; }
    
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-3 { grid-template-columns: 1fr; } }
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.88rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 14px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .items-table th { padding: 12px 14px; background: #f8fafc; color: var(--c-muted); font-weight: 800; font-size: 0.78rem; text-transform: uppercase; border-bottom: 2px solid var(--c-border); text-align: start; }
    .items-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    
    .btn-add-row { background: var(--c-bg-light); color: var(--c-bg); border: 1px dashed var(--c-bg); padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; margin-top: 16px; }
    .btn-remove-row { color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; width: 34px; height: 34px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }

    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 24px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-bg), var(--c-bg-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/accounting/budgets" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل الموازنة التقديرية' : 'إنشاء موازنة تقديرية جديدة' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">تحديد الاعتمادات الموازنية لكل حساب مالى للفترة المحددة.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-info"></i> ترويسة الموازنة</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">رمز الكود <span style="color:red">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-bg-dark);" value="<?= $isEdit ? htmlspecialchars($budget->code ?? '') : '' ?>" placeholder="مثال: BUD-2026" required>
                </div>
                <div class="form-group">
                    <label class="input-label">اسم / عنوان الموازنة <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($budget->name_ar ?? '') : '' ?>" placeholder="مثال: الموازنة التشغيلية للعام 2026" required>
                </div>
                <div class="form-group">
                    <label class="input-label">السنة المالية <span style="color:red">*</span></label>
                    <input type="number" name="fiscal_year" class="form-control" value="<?= $isEdit ? htmlspecialchars($budget->fiscal_year ?? date('Y')) : date('Y') ?>" required>
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">تاريخ البداية <span style="color:red">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($budget->start_date ?? '') : date('Y-01-01') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ النهاية <span style="color:red">*</span></label>
                    <input type="date" name="end_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($budget->end_date ?? '') : date('Y-12-31') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">حالة الموازنة</label>
                    <select name="status" class="form-control">
                        <?php $st = $budget->status ?? 'draft'; ?>
                        <option value="draft" <?= $st==='draft'?'selected':'' ?>>مسودة (Draft)</option>
                        <option value="approved" <?= $st==='approved'?'selected':'' ?>>معتمدة (Approved)</option>
                        <option value="closed" <?= $st==='closed'?'selected':'' ?>>مغلقة (Closed)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-list-numbers"></i> بنود الاعتمادات الموازنية بالحسابات</h3>
            
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width: 45%;">الحساب المالي (مصروف / إيراد) <span style="color:red">*</span></th>
                        <th style="width: 25%;">المبلغ المستهدف المخصص <span style="color:red">*</span></th>
                        <th style="width: 25%;">ملاحظات</th>
                        <th style="width: 5%;"></th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if ($isEdit && !empty($items)): ?>
                        <?php foreach($items as $item): ?>
                            <tr>
                                <td>
                                    <select name="account_id[]" class="form-control" required>
                                        <option value="">-- اختر الحساب --</option>
                                        <?php foreach($accounts as $acc): ?>
                                            <option value="<?= $acc->id ?>" <?= $acc->id == $item->account_id ? 'selected' : '' ?>><?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" min="0" name="allocated_amount[]" class="form-control amount-input" style="text-align:center; font-family:monospace; font-weight:bold; color:var(--c-bg-dark);" value="<?= $item->allocated_amount ?>" required></td>
                                <td><input type="text" name="item_notes[]" class="form-control" value="<?= htmlspecialchars($item->notes ?? '') ?>" placeholder="ملاحظة..."></td>
                                <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="removeRow(this)"><i class="ph-bold ph-trash"></i></button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php for($i=0; $i<3; $i++): ?>
                            <tr>
                                <td>
                                    <select name="account_id[]" class="form-control" required>
                                        <option value="">-- اختر الحساب --</option>
                                        <?php foreach($accounts as $acc): ?>
                                            <option value="<?= $acc->id ?>"><?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" min="0" name="allocated_amount[]" class="form-control amount-input" style="text-align:center; font-family:monospace; font-weight:bold; color:var(--c-bg-dark);" value="0.00" required></td>
                                <td><input type="text" name="item_notes[]" class="form-control" placeholder="ملاحظة..."></td>
                                <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="removeRow(this)"><i class="ph-bold ph-trash"></i></button></td>
                            </tr>
                        <?php endfor; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <button type="button" class="btn-add-row" onclick="addRow()"><i class="ph-bold ph-plus"></i> إضافة بند موازنة جديد</button>
            <div style="margin-top:20px; text-align:end; font-size:1.1rem; font-weight:900;">
                إجمالي الموازنة المخصصة: <span id="grandTotal" style="font-family:monospace; color:var(--c-bg-dark);">0.00</span> EGP
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/accounting/budgets" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> حفظ الموازنة التقديرية</button>
        </div>
    </form>
</div>

<script>
function calculateGrandTotal() {
    let amounts = document.querySelectorAll('.amount-input');
    let sum = 0;
    amounts.forEach(a => sum += parseFloat(a.value) || 0);
    document.getElementById('grandTotal').innerText = sum.toFixed(2);
}

function addRow() {
    let tbody = document.getElementById('tableBody');
    let firstRow = tbody.rows[0];
    let newRow = firstRow.cloneNode(true);
    
    newRow.querySelectorAll('input').forEach(i => {
        if(i.type === 'number') i.value = '0.00';
        else i.value = '';
    });
    newRow.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    
    tbody.appendChild(newRow);
    attachListeners();
    calculateGrandTotal();
}

function removeRow(btn) {
    let tbody = document.getElementById('tableBody');
    if (tbody.rows.length > 1) {
        btn.closest('tr').remove();
        calculateGrandTotal();
    }
}

function attachListeners() {
    document.querySelectorAll('.amount-input').forEach(input => {
        input.oninput = calculateGrandTotal;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    attachListeners();
    calculateGrandTotal();
});
</script>