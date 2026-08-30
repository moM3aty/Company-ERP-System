<?php
// Path: resources/views/accounting/journals/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($entry) && $entry !== null && !empty($entry->id);
$actionUrl = $isEdit ? "/ERP/accounting/journal-entries/{$entry->id}/update" : "/ERP/accounting/journal-entries/store";
?>

<style>
    :root { 
        --c-je: #4f46e5; 
        --c-je-dark: #3730a3; 
        --c-je-light: #eef2ff;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 1200px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-je); font-size: 1.4rem; padding: 8px; background: var(--c-je-light); border-radius: 8px; }
    
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-3 { grid-template-columns: 1fr; } }
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.88rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 14px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .items-table th { padding: 12px 14px; background: #f8fafc; color: var(--c-muted); font-weight: 800; font-size: 0.78rem; text-transform: uppercase; border-bottom: 2px solid var(--c-border); text-align: start; }
    .items-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    
    .btn-add-row { background: var(--c-je-light); color: var(--c-je); border: 1px dashed var(--c-je); padding: 10px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; margin-top: 16px; }
    .btn-remove-row { color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; width: 34px; height: 34px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }

    .balance-bar { display: flex; justify-content: space-between; align-items: center; background: #1e293b; color: white; padding: 16px 24px; border-radius: 14px; margin-top: 24px; }
    .balance-status { font-weight: 800; padding: 6px 14px; border-radius: 8px; font-size: 0.9rem; }
    .status-balanced { background: #10b981; color: white; }
    .status-unbalanced { background: #ef4444; color: white; }

    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 24px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-je), var(--c-je-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/accounting/journal-entries" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل قيد اليومية' : 'إنشاء قيد يومية جديد' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">أدخل أطراف القيد، مراكز التكلفة، والبيانات المالية مع التأكد من التوازن.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST" id="journalForm">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-info"></i> ترويسة القيد المالي</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">تاريخ القيد <span style="color:red">*</span></label>
                    <input type="date" name="entry_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($entry->entry_date ?? '') : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">رقم المرجع / المستند</label>
                    <input type="text" name="reference_number" class="form-control" value="<?= $isEdit ? htmlspecialchars($entry->reference_number ?? '') : '' ?>" placeholder="مثال: فاتورة #1024">
                </div>
                <div class="form-group">
                    <label class="input-label">شرح القيد العام <span style="color:red">*</span></label>
                    <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars($entry->description ?? '') : '' ?>" placeholder="بيان مختصر لسبب القيد..." required>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-arrows-down-up"></i> أطراف القيد ومراكز التكلفة</h3>
            
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width: 28%;">الحساب المالي <span style="color:red">*</span></th>
                        <th style="width: 22%;">مركز التكلفة</th>
                        <th style="width: 24%;">البيان التفصيلي</th>
                        <th style="width: 11%; text-align: center;">مدين (+)</th>
                        <th style="width: 11%; text-align: center;">دائن (-)</th>
                        <th style="width: 4%;"></th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if ($isEdit && !empty($items)): ?>
                        <?php foreach($items as $item): ?>
                            <tr>
                                <td>
                                    <select name="account_id[]" class="form-control account-select" required>
                                        <option value="">-- اختر الحساب --</option>
                                        <?php foreach($accounts as $acc): ?>
                                            <option value="<?= $acc->id ?>" <?= $acc->id == $item->account_id ? 'selected' : '' ?>><?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="cost_center_id[]" class="form-control">
                                        <option value="">-- بدون مركز تكلفة --</option>
                                        <?php foreach($costCenters as $cc): ?>
                                            <option value="<?= $cc->id ?>" <?= $cc->id == ($item->cost_center_id ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($cc->code) ?> - <?= htmlspecialchars($cc->name_ar) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="text" name="item_description[]" class="form-control" value="<?= htmlspecialchars($item->description ?? '') ?>" placeholder="شرح السطر..."></td>
                                <td><input type="number" step="0.01" min="0" name="debit[]" class="form-control debit-input" style="text-align:center; font-family:monospace; font-weight:bold; color:#059669;" value="<?= $item->debit ?>"></td>
                                <td><input type="number" step="0.01" min="0" name="credit[]" class="form-control credit-input" style="text-align:center; font-family:monospace; font-weight:bold; color:#dc2626;" value="<?= $item->credit ?>"></td>
                                <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="removeRow(this)"><i class="ph-bold ph-trash"></i></button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php for($i=0; $i<2; $i++): ?>
                            <tr>
                                <td>
                                    <select name="account_id[]" class="form-control account-select" required>
                                        <option value="">-- اختر الحساب --</option>
                                        <?php foreach($accounts as $acc): ?>
                                            <option value="<?= $acc->id ?>"><?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="cost_center_id[]" class="form-control">
                                        <option value="">-- بدون مركز تكلفة --</option>
                                        <?php foreach($costCenters as $cc): ?>
                                            <option value="<?= $cc->id ?>"><?= htmlspecialchars($cc->code) ?> - <?= htmlspecialchars($cc->name_ar) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="text" name="item_description[]" class="form-control" placeholder="شرح السطر..."></td>
                                <td><input type="number" step="0.01" min="0" name="debit[]" class="form-control debit-input" style="text-align:center; font-family:monospace; font-weight:bold; color:#059669;" value="0.00"></td>
                                <td><input type="number" step="0.01" min="0" name="credit[]" class="form-control credit-input" style="text-align:center; font-family:monospace; font-weight:bold; color:#dc2626;" value="0.00"></td>
                                <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="removeRow(this)"><i class="ph-bold ph-trash"></i></button></td>
                            </tr>
                        <?php endfor; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <button type="button" class="btn-add-row" onclick="addRow()"><i class="ph-bold ph-plus"></i> إضافة طرف جديد بالقيد</button>

            <div class="balance-bar">
                <div style="display:flex; gap:30px;">
                    <div>إجمالي المدين: <span id="totalDebit" style="font-family:monospace; font-size:1.2rem; font-weight:900; color:#34d399;">0.00</span></div>
                    <div>إجمالي الدائن: <span id="totalCredit" style="font-family:monospace; font-size:1.2rem; font-weight:900; color:#f87171;">0.00</span></div>
                    <div>الفرق: <span id="totalDiff" style="font-family:monospace; font-size:1.2rem; font-weight:900; color:#fbbf24;">0.00</span></div>
                </div>
                <div id="balanceStatus" class="balance-status status-unbalanced">غير متزن</div>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/accounting/journal-entries" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit" id="submitBtn"><i class="ph-bold ph-floppy-disk"></i> حفظ القيد كمسودة</button>
        </div>
    </form>
</div>

<script>
function calculateTotals() {
    let debits = document.querySelectorAll('.debit-input');
    let credits = document.querySelectorAll('.credit-input');
    let sumDebit = 0, sumCredit = 0;

    debits.forEach(i => sumDebit += parseFloat(i.value) || 0);
    credits.forEach(i => sumCredit += parseFloat(i.value) || 0);

    document.getElementById('totalDebit').innerText = sumDebit.toFixed(2);
    document.getElementById('totalCredit').innerText = sumCredit.toFixed(2);
    
    let diff = Math.abs(sumDebit - sumCredit);
    document.getElementById('totalDiff').innerText = diff.toFixed(2);

    let statusBox = document.getElementById('balanceStatus');
    let submitBtn = document.getElementById('submitBtn');

    if (sumDebit > 0 && diff < 0.01) {
        statusBox.innerText = 'متزن ✓';
        statusBox.className = 'balance-status status-balanced';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
    } else {
        statusBox.innerText = 'غير متزن ✕';
        statusBox.className = 'balance-status status-unbalanced';
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';
    }
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
    calculateTotals();
}

function removeRow(btn) {
    let tbody = document.getElementById('tableBody');
    if (tbody.rows.length > 2) {
        btn.closest('tr').remove();
        calculateTotals();
    } else {
        alert('يجب أن يحتوي القيد على طرفين على الأقل.');
    }
}

function attachListeners() {
    document.querySelectorAll('.debit-input, .credit-input').forEach(input => {
        input.oninput = function() {
            let tr = this.closest('tr');
            if (this.classList.contains('debit-input') && parseFloat(this.value) > 0) {
                tr.querySelector('.credit-input').value = '0.00';
            } else if (this.classList.contains('credit-input') && parseFloat(this.value) > 0) {
                tr.querySelector('.debit-input').value = '0.00';
            }
            calculateTotals();
        };
    });
}

document.addEventListener('DOMContentLoaded', function() {
    attachListeners();
    calculateTotals();
});
</script>