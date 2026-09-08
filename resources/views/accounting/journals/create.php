<?php
// Path: resources/views/accounting/journals/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$isEdit = isset($entry) && $entry !== null && !empty($entry->id);
$actionUrl = $isEdit ? "/ERP/accounting/journal-entries/{$entry->id}/update" : "/ERP/accounting/journal-entries/store";

$t = [
    'ar' => [
        'title_new' => 'إنشاء قيد يومية جديد', 'title_edit' => 'تعديل قيد مسودة', 'desc' => 'أدخل أطراف القيد، التوجيه المحاسبي لمراكز التكلفة والفروع.',
        'sec_basic' => 'ترويسة القيد المالي', 'date' => 'تاريخ القيد', 'ref' => 'رقم المرجع / المستند', 'desc_general' => 'شرح القيد العام',
        'branch' => 'الفرع المخصص', 'general_branch' => '-- قيد عام (المركز الرئيسي) --',
        'sec_lines' => 'أطراف القيد والتوجيه المحاسبي', 'col_acc' => 'الحساب المالي', 'col_cc' => 'مركز التكلفة', 'col_desc' => 'البيان التفصيلي للسطر', 'col_dr' => 'مدين (+)', 'col_cr' => 'دائن (-)',
        'add_row' => 'إضافة طرف جديد بالقيد', 'tot_dr' => 'إجمالي المدين', 'tot_cr' => 'إجمالي الدائن', 'diff' => 'الفرق',
        'st_balanced' => 'متزن ✓', 'st_unbalanced' => 'غير متزن ✕',
        'cancel' => 'تراجع وإلغاء', 'save' => 'حفظ كمسودة', 'update' => 'تحديث المسودة'
    ],
    'en' => [
        'title_new' => 'Create Journal Entry', 'title_edit' => 'Edit Draft Entry', 'desc' => 'Enter entry lines, cost center allocations, and branch.',
        'sec_basic' => 'Entry Header', 'date' => 'Entry Date', 'ref' => 'Reference No.', 'desc_general' => 'General Description',
        'branch' => 'Assigned Branch', 'general_branch' => '-- General Entry (HQ) --',
        'sec_lines' => 'Entry Lines & Allocation', 'col_acc' => 'Account', 'col_cc' => 'Cost Center', 'col_desc' => 'Line Description', 'col_dr' => 'Debit (+)', 'col_cr' => 'Credit (-)',
        'add_row' => 'Add New Line', 'tot_dr' => 'Total Debit', 'tot_cr' => 'Total Credit', 'diff' => 'Difference',
        'st_balanced' => 'Balanced ✓', 'st_unbalanced' => 'Unbalanced ✕',
        'cancel' => 'Cancel', 'save' => 'Save Draft', 'update' => 'Update Draft'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --brand-primary: #4f46e5; --brand-primary-dark: #3730a3; --brand-primary-light: #e0e7ff; 
        --surface: #ffffff; --surface-hover: #f8fafc; --bg-body: #f1f5f9;
        --text-main: #0f172a; --text-muted: #64748b; --border-color: #e2e8f0;
        --radius-lg: 24px; --radius-md: 16px;
        --shadow-soft: 0 10px 30px -10px rgba(15, 23, 42, 0.08);
        --shadow-hover: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
    }
    .form-wrapper { max-width: 1200px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: var(--bg-body); }
    
    .glass-header { position: sticky; top: 0; z-index: 50; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); padding: 24px 30px; margin-bottom: 30px; border-bottom: 1px solid rgba(226, 232, 240, 0.6); display: flex; align-items: center; justify-content: space-between; }
    .back-btn { width: 48px; height: 48px; border-radius: 14px; background: var(--surface); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--text-muted); font-size: 1.3rem; transition: 0.3s; box-shadow: var(--shadow-soft);}
    .back-btn:hover { background: var(--brand-primary-light); color: var(--brand-primary); border-color: #a5b4fc; transform: translateX(<?= $isRtl ? '4px' : '-4px' ?>); }

    .form-section { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 32px; margin: 0 30px 30px 30px; border-top: 5px solid var(--brand-primary); box-shadow: var(--shadow-soft); transition:0.3s;}
    .form-section:hover { box-shadow: var(--shadow-hover); }
    .section-title { font-size: 1.15rem; font-weight: 900; color: var(--text-main); margin: 0 0 24px 0; border-bottom: 2px dashed var(--border-color); padding-bottom: 14px; display: flex; align-items: center; gap: 10px;}
    .section-title i { color: var(--brand-primary); font-size: 1.5rem; padding: 8px; background: var(--brand-primary-light); border-radius: 10px; }
    
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
    .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
    @media(max-width:900px) { .grid-4 { grid-template-columns: repeat(2, 1fr); } }
    @media(max-width:768px) { .grid-3, .grid-4 { grid-template-columns: 1fr; } }
    
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: var(--text-muted); }
    .form-control { width: 100%; padding: 14px 18px; border: 1px solid #cbd5e1; border-radius: 12px; background: var(--surface-hover); font-weight: 700; color: var(--text-main); font-family:inherit; transition: 0.3s; box-sizing: border-box;}
    .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15); outline: none; background: var(--surface);}
    
    .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .items-table th { padding: 14px 16px; background: var(--surface-hover); color: var(--text-muted); font-weight: 900; font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid var(--border-color); text-align: start; }
    .items-table td { padding: 10px; border-bottom: 1px dashed var(--border-color); vertical-align: middle; }
    
    .btn-add-row { background: var(--brand-primary-light); color: var(--brand-primary); border: 2px dashed var(--brand-primary); padding: 12px 24px; border-radius: 12px; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; margin-top: 16px; transition:0.3s; font-size:0.95rem;}
    .btn-add-row:hover { background: var(--brand-primary); color: #fff; }
    .btn-remove-row { color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; width: 40px; height: 40px; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size:1.2rem; transition:0.2s;}
    .btn-remove-row:hover { background: #dc2626; color: #fff; }

    .balance-bar { display: flex; justify-content: space-between; align-items: center; background: #0f172a; color: white; padding: 20px 30px; border-radius: var(--radius-md); margin-top: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.15);}
    .balance-status { font-weight: 900; padding: 8px 16px; border-radius: 10px; font-size: 1rem; border:2px solid transparent;}
    .status-balanced { background: rgba(16, 185, 129, 0.2); color: #34d399; border-color: #10b981; }
    .status-unbalanced { background: rgba(239, 68, 68, 0.2); color: #f87171; border-color: #ef4444; }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(16px); border-top: 1px solid var(--border-color); padding: 18px 30px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; box-shadow: 0 -10px 30px rgba(0,0,0,0.05);}
    .btn-submit { background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 900; font-size: 1rem; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 8px 20px rgba(79, 70, 229, 0.25); transition: 0.3s; }
    .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(79, 70, 229, 0.35); }
    .btn-cancel { background: var(--surface); border: 1px solid #cbd5e1; color: #475569; padding: 14px 32px; border-radius: 12px; font-weight: 900; font-size: 1rem; text-decoration: none; transition: 0.3s;}
    .btn-cancel:hover { background: var(--surface-hover); color: var(--text-main); border-color: #94a3b8; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="glass-header">
        <div style="display:flex; align-items:center; gap:18px;">
            <a href="/ERP/accounting/journal-entries" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--text-main); font-weight:900;"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--text-muted); font-size:0.95rem; font-weight:700;"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="margin: 0 30px 24px 30px; background: #fff1f2; color: #e11d48; padding: 18px 20px; border-radius: 16px; border: 1px solid #fecdd3; font-weight:800; display:flex; align-items:center; gap:12px; box-shadow:var(--shadow-soft);"><i class="ph-bold ph-warning-circle" style="font-size:1.6rem;"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST" id="journalForm">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-fill ph-info"></i> <?= $t['sec_basic'] ?></h3>
            <div class="grid-4">
                <div class="form-group">
                    <label class="input-label"><?= $t['date'] ?> <span style="color:var(--brand-danger);">*</span></label>
                    <input type="date" name="entry_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($entry->entry_date ?? '') : date('Y-m-d') ?>" required>
                </div>
                
                <?php if(!empty($branches)): ?>
                <div class="form-group">
                    <label class="input-label"><?= $t['branch'] ?></label>
                    <select name="branch_id" class="form-control">
                        <option value="0"><?= $t['general_branch'] ?></option>
                        <?php foreach($branches as $b): 
                            $bName = $isRtl ? $b->name_ar : ($b->name_en ?: $b->name_ar);
                        ?>
                            <option value="<?= $b->id ?>" <?= ($isEdit && ($entry->branch_id ?? 0) == $b->id) ? 'selected' : '' ?>><?= htmlspecialchars($bName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="input-label"><?= $t['ref'] ?></label>
                    <input type="text" name="reference_number" class="form-control" style="font-family:monospace; color:var(--brand-primary);" value="<?= $isEdit ? htmlspecialchars($entry->reference_number ?? '') : '' ?>" placeholder="INV-001">
                </div>
                <div class="form-group" style="grid-column: span <?= empty($branches) ? 2 : 1 ?>;">
                    <label class="input-label"><?= $t['desc_general'] ?> <span style="color:var(--brand-danger);">*</span></label>
                    <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars($entry->description ?? '') : '' ?>" required>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-fill ph-arrows-down-up"></i> <?= $t['sec_lines'] ?></h3>
            
            <div style="overflow-x:auto;">
                <table class="items-table" id="itemsTable">
                    <thead>
                        <tr>
                            <th style="width: 25%;"><?= $t['col_acc'] ?> <span style="color:red">*</span></th>
                            <th style="width: 20%;"><?= $t['col_cc'] ?></th>
                            <th style="width: 25%;"><?= $t['col_desc'] ?></th>
                            <th style="width: 12%; text-align: center;"><?= $t['col_dr'] ?></th>
                            <th style="width: 12%; text-align: center;"><?= $t['col_cr'] ?></th>
                            <th style="width: 6%;"></th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php if ($isEdit && !empty($items)): ?>
                            <?php foreach($items as $item): ?>
                                <tr>
                                    <td>
                                        <select name="account_id[]" class="form-control" required>
                                            <option value="">-- Select --</option>
                                            <?php foreach($accounts as $acc): 
                                                $aName = $isRtl ? $acc->name_ar : ($acc->name_en ?: $acc->name_ar);
                                            ?>
                                                <option value="<?= $acc->id ?>" <?= $acc->id == $item->account_id ? 'selected' : '' ?>><?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($aName) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="cost_center_id[]" class="form-control">
                                            <option value="">-- None --</option>
                                            <?php foreach($costCenters as $cc): 
                                                $cName = $isRtl ? $cc->name_ar : ($cc->name_en ?: $cc->name_ar);
                                            ?>
                                                <option value="<?= $cc->id ?>" <?= $cc->id == ($item->cost_center_id ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($cc->code) ?> - <?= htmlspecialchars($cName) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="item_description[]" class="form-control" value="<?= htmlspecialchars($item->description ?? '') ?>"></td>
                                    <td><input type="number" step="0.01" min="0" name="debit[]" class="form-control debit-input" style="text-align:center; font-family:monospace; font-weight:900; color:#059669;" value="<?= (float)$item->debit ?>"></td>
                                    <td><input type="number" step="0.01" min="0" name="credit[]" class="form-control credit-input" style="text-align:center; font-family:monospace; font-weight:900; color:#e11d48;" value="<?= (float)$item->credit ?>"></td>
                                    <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="removeRow(this)"><i class="ph-bold ph-trash"></i></button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php for($i=0; $i<2; $i++): ?>
                                <tr>
                                    <td>
                                        <select name="account_id[]" class="form-control" required>
                                            <option value="">-- Select --</option>
                                            <?php foreach($accounts as $acc): 
                                                $aName = $isRtl ? $acc->name_ar : ($acc->name_en ?: $acc->name_ar);
                                            ?>
                                                <option value="<?= $acc->id ?>"><?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($aName) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="cost_center_id[]" class="form-control">
                                            <option value="">-- None --</option>
                                            <?php foreach($costCenters as $cc): 
                                                $cName = $isRtl ? $cc->name_ar : ($cc->name_en ?: $cc->name_ar);
                                            ?>
                                                <option value="<?= $cc->id ?>"><?= htmlspecialchars($cc->code) ?> - <?= htmlspecialchars($cName) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="item_description[]" class="form-control"></td>
                                    <td><input type="number" step="0.01" min="0" name="debit[]" class="form-control debit-input" style="text-align:center; font-family:monospace; font-weight:900; color:#059669;" value="0.00"></td>
                                    <td><input type="number" step="0.01" min="0" name="credit[]" class="form-control credit-input" style="text-align:center; font-family:monospace; font-weight:900; color:#e11d48;" value="0.00"></td>
                                    <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="removeRow(this)"><i class="ph-bold ph-trash"></i></button></td>
                                </tr>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <button type="button" class="btn-add-row" onclick="addRow()"><i class="ph-bold ph-plus"></i> <?= $t['add_row'] ?></button>

            <div class="balance-bar">
                <div style="display:flex; flex-wrap:wrap; gap:30px;">
                    <div><span style="color:#94a3b8; font-size:0.85rem; display:block; margin-bottom:4px;"><?= $t['tot_dr'] ?></span><span id="totalDebit" style="font-family:monospace; font-size:1.5rem; font-weight:900; color:#34d399;">0.00</span></div>
                    <div><span style="color:#94a3b8; font-size:0.85rem; display:block; margin-bottom:4px;"><?= $t['tot_cr'] ?></span><span id="totalCredit" style="font-family:monospace; font-size:1.5rem; font-weight:900; color:#fb7185;">0.00</span></div>
                    <div><span style="color:#94a3b8; font-size:0.85rem; display:block; margin-bottom:4px;"><?= $t['diff'] ?></span><span id="totalDiff" style="font-family:monospace; font-size:1.5rem; font-weight:900; color:#fbbf24;">0.00</span></div>
                </div>
                <div id="balanceStatus" class="balance-status status-unbalanced"><?= $t['st_unbalanced'] ?></div>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/accounting/journal-entries" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit" id="submitBtn"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
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
        statusBox.innerText = '<?= $t['st_balanced'] ?>';
        statusBox.className = 'balance-status status-balanced';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
    } else {
        statusBox.innerText = '<?= $t['st_unbalanced'] ?>';
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
    // إعادة تهيئة Select2 إذا كنت تستخدمها، أو إعادة التعيين العادي
    newRow.querySelectorAll('select').forEach(s => {
        s.selectedIndex = 0;
    });
    
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
        alert('<?= $isRtl ? "يجب أن يحتوي القيد على طرفين على الأقل." : "An entry must have at least two lines." ?>');
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