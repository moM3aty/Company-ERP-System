<?php
// Path: resources/views/purchasing/returns/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();

$isEdit = isset($returnOrder) && $returnOrder !== null;
$actionUrl = $isEdit ? "/ERP/purchasing/returns/{$returnOrder->id}/update" : "/ERP/purchasing/returns/store";

$t = [
    'ar' => [
        'title_new' => 'إصدار مرتجع مشتريات جديد (Debit Note)',
        'title_edit' => 'تعديل مرتجع المشتريات',
        'basic_info' => 'بيانات المرتجع والمورد',
        'supplier' => 'المورد',
        'select_supplier' => '-- اختر المورد --',
        'original_inv' => 'فاتورة الشراء الأصلية (اختياري)',
        'direct_return' => '-- مرتجع مباشر / بدون فاتورة --',
        'ret_num' => 'رقم المرتجع (تلقائي)',
        'ret_placeholder' => 'تلقائي',
        'ret_date' => 'تاريخ الإرجاع',
        'reason' => 'سبب الإرجاع الرئيسي',
        'reason_placeholder' => 'مثال: أصناف معيبة، عدم المطابقة للمواصفات...',
        'status' => 'حالة المرتجع',
        'status_completed' => 'تم الإرجاع والخصم (Completed)',
        'status_approved' => 'موافق عليه (Approved)',
        'status_draft' => 'مسودة (Draft)',
        'notes' => 'ملاحظات إضافية',
        'notes_placeholder' => 'أي تفاصيل خاصة بالشحن أو التسليم لأمين المخزن...',
        'items_title' => 'الأصناف المرجعة',
        'add_item' => 'إضافة صنف',
        'col_prod' => 'الصنف (الكود والاسم)',
        'col_desc' => 'الوصف',
        'col_qty' => 'الكمية المرجعة',
        'col_price' => 'سعر الوحدة',
        'col_total' => 'الإجمالي',
        'col_actions' => 'إزالة',
        'subtotal' => 'إجمالي الأصناف المرجعة:',
        'vat_refund' => 'ضريبة القيمة المضافة المستردة:',
        'total_debit' => 'إجمالي إشعار الخصم:',
        'unregistered' => '-- غير مسجل / يدوي --',
        'cancel' => 'إلغاء وتراجع',
        'save' => 'حفظ وإصدار إذن المرتجع',
        'update' => 'تحديث المرتجع'
    ],
    'en' => [
        'title_new' => 'Issue New Purchase Return (Debit Note)',
        'title_edit' => 'Edit Purchase Return',
        'basic_info' => 'Return & Vendor Info',
        'supplier' => 'Supplier',
        'select_supplier' => '-- Select Supplier --',
        'original_inv' => 'Original Purchase Invoice (Optional)',
        'direct_return' => '-- Direct Return / No Invoice --',
        'ret_num' => 'Return Number (Auto)',
        'ret_placeholder' => 'Auto',
        'ret_date' => 'Return Date',
        'reason' => 'Main Return Reason',
        'reason_placeholder' => 'e.g. Defective items, wrong specifications...',
        'status' => 'Return Status',
        'status_completed' => 'Completed & Debited',
        'status_approved' => 'Approved',
        'status_draft' => 'Draft',
        'notes' => 'Additional Notes',
        'notes_placeholder' => 'Any remarks for warehouse keeper or shipping...',
        'items_title' => 'Returned Line Items',
        'add_item' => 'Add Item',
        'col_prod' => 'Product (Code & Name)',
        'col_desc' => 'Description',
        'col_qty' => 'Returned Qty',
        'col_price' => 'Unit Price',
        'col_total' => 'Total',
        'col_actions' => 'Remove',
        'subtotal' => 'Returned Items Subtotal:',
        'vat_refund' => 'Refunded VAT:',
        'total_debit' => 'Total Debit Note Amount:',
        'unregistered' => '-- Unregistered / Manual --',
        'cancel' => 'Cancel',
        'save' => 'Save & Issue Return',
        'update' => 'Update Return'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-red: #dc2626;
        --c-red-dark: #b91c1c;
        --c-red-light: #fef2f2;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
        --c-bg: #f8fafc;
    }

    .form-wrapper { max-width: 1000px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .back-btn { width: 42px; height: 42px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; color: var(--c-text-muted); text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-red-light); color: var(--c-red); border-color: #fca5a5; }
    .form-title { font-size: 1.6rem; font-weight: 800; color: var(--c-text-dark); margin: 0; }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 24px; border-top: 4px solid var(--c-red); }
    .panel-title { font-size: 1.1rem; color: var(--c-text-dark); font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 10px;}
    
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: var(--c-bg); transition: 0.2s; color: var(--c-text-dark); }
    .form-control:focus { border-color: var(--c-red); box-shadow: 0 0 0 4px var(--c-red-light); background: #ffffff;}
    .input-label { font-size: 0.85rem; font-weight: 800; color: var(--c-text-muted); margin-bottom: 8px; display: block; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }

    .items-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 16px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
    .items-table th { padding: 14px 12px; background: #f8fafc; color: var(--c-text-muted); font-size: 0.8rem; font-weight: 800; text-transform: uppercase; text-align: start; border-bottom: 1px solid #e2e8f0;}
    .items-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; background: #ffffff; }
    
    .btn-remove-row { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
    .btn-add-row { background: var(--c-red-light); color: var(--c-red); border: 1px solid #fca5a5; padding: 8px 16px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    
    .totals-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #cbd5e1; margin-top: 20px; width: 350px; float: <?= $isRtl ? 'left' : 'right' ?>; }
    .totals-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-weight: 700; color: #475569; font-size: 0.95rem; }
    .totals-row.grand { border-top: 2px dashed #cbd5e1; padding-top: 10px; font-size: 1.2rem; color: var(--c-red); font-weight: 900; }
    
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; clear: both;}
    .btn-submit { background: linear-gradient(135deg, var(--c-red), var(--c-red-dark)); color: white; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: var(--c-text-muted); padding: 12px 28px; border-radius: 12px; font-weight: 800; text-decoration: none; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <a href="/ERP/purchasing/returns" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <h2 class="form-title"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-info" style="color:var(--c-red);"></i> <?= $t['basic_info'] ?></h3>
            <div class="grid-3">
                <div>
                    <label class="input-label"><?= $t['supplier'] ?> <span style="color:red">*</span></label>
                    <select name="supplier_id" class="form-control" required>
                        <option value=""><?= $t['select_supplier'] ?></option>
                        <?php foreach($suppliers ?? [] as $s): ?>
                            <option value="<?= $s->id ?>" <?= ($isEdit && $returnOrder->supplier_id == $s->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s->name_ar) ?> <?= !empty($s->code) ? "({$s->code})" : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="input-label"><?= $t['original_inv'] ?></label>
                    <select name="invoice_id" class="form-control">
                        <option value=""><?= $t['direct_return'] ?></option>
                        <?php foreach($invoices ?? [] as $inv): ?>
                            <option value="<?= $inv->id ?>" <?= ($isEdit && $returnOrder->invoice_id == $inv->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($inv->invoice_number) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="input-label"><?= $t['ret_num'] ?></label>
                    <input type="text" name="return_number" class="form-control" style="font-family:monospace; color:var(--c-red); font-weight:bold;" value="<?= $isEdit ? htmlspecialchars($returnOrder->return_number) : '' ?>" <?= $isEdit ? 'readonly' : 'placeholder="'.$t['ret_placeholder'].'"' ?>>
                </div>
            </div>

            <div class="grid-3" style="margin-top: 24px;">
                <div>
                    <label class="input-label"><?= $t['ret_date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="return_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($returnOrder->return_date) : date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['reason'] ?></label>
                    <input type="text" name="reason" class="form-control" value="<?= $isEdit ? htmlspecialchars($returnOrder->reason ?? '') : '' ?>" placeholder="<?= $t['reason_placeholder'] ?>">
                </div>
                <div>
                    <label class="input-label"><?= $t['status'] ?></label>
                    <select name="status" class="form-control" style="font-weight: 800;">
                        <?php $st = $isEdit ? $returnOrder->status : 'completed'; ?>
                        <option value="completed" <?= $st=='completed' ? 'selected' : '' ?>><?= $t['status_completed'] ?></option>
                        <option value="approved" <?= $st=='approved' ? 'selected' : '' ?>><?= $t['status_approved'] ?></option>
                        <option value="draft" <?= $st=='draft' ? 'selected' : '' ?>><?= $t['status_draft'] ?></option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 24px;">
                <label class="input-label"><?= $t['notes'] ?></label>
                <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($returnOrder->notes ?? '') : '' ?>" placeholder="<?= $t['notes_placeholder'] ?>">
            </div>
        </div>

        <div class="panel-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h3 class="panel-title" style="margin:0; border:none; padding:0;"><i class="ph-duotone ph-list-numbers" style="color:var(--c-red);"></i> <?= $t['items_title'] ?></h3>
                <button type="button" onclick="addRow()" class="btn-add-row"><i class="ph-bold ph-plus"></i> <?= $t['add_item'] ?></button>
            </div>
            
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width: 28%;"><?= $t['col_prod'] ?></th>
                        <th style="width: 27%;"><?= $t['col_desc'] ?> <span style="color:red">*</span></th>
                        <th style="width: 12%; text-align: center;"><?= $t['col_qty'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_price'] ?></th>
                        <th style="width: 12%; text-align: center;"><?= $t['col_total'] ?></th>
                        <th style="width: 6%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <?php if($isEdit && !empty($items)): foreach($items as $item): ?>
                        <tr>
                            <td>
                                <select name="product_id[]" class="form-control">
                                    <option value=""><?= $t['unregistered'] ?></option>
                                    <?php foreach($products ?? [] as $p): ?>
                                        <option value="<?= $p->id ?>" <?= $item->product_id == $p->id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p->code) ?><?= !empty($p->name) ? ' - ' . htmlspecialchars($p->name) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="description[]" class="form-control" value="<?= htmlspecialchars($item->description) ?>" required></td>
                            <td><input type="number" step="0.01" name="quantity[]" class="form-control row-qty" style="text-align:center; font-weight:700;" value="<?= $item->quantity ?>" required oninput="calcTotals()"></td>
                            <td><input type="number" step="0.01" name="unit_price[]" class="form-control row-price" style="text-align:center; font-family:monospace;" value="<?= $item->unit_price ?>" required oninput="calcTotals()"></td>
                            <td><input type="text" class="form-control row-total" style="text-align:center; font-family:monospace; background:#f1f5f9; color:var(--c-red); font-weight:bold;" value="<?= $item->total_price ?>" readonly></td>
                            <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove(); calcTotals();"><i class="ph-bold ph-trash"></i></button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <div style="display:flex; justify-content:flex-end; margin-top: 24px;">
                <div class="totals-box">
                    <div class="totals-row">
                        <span><?= $t['subtotal'] ?></span>
                        <span id="txtSubtotal">0.00</span>
                    </div>
                    <div class="totals-row">
                        <span><?= $t['vat_refund'] ?></span>
                        <span><input type="number" step="0.01" name="tax_amount" id="inpTax" class="form-control" style="width: 100px; padding:4px; text-align:end;" value="<?= $isEdit ? $returnOrder->tax_amount : '0.00' ?>" oninput="calcTotals()"></span>
                    </div>
                    <div class="totals-row grand">
                        <span><?= $t['total_debit'] ?></span>
                        <div><span id="txtGrandTotal">0.00</span> <span style="font-size:0.8rem; color:#64748b;"><?= $currency ?></span></div>
                    </div>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/purchasing/returns" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>

<template id="rowTemplate">
    <tr>
        <td>
            <select name="product_id[]" class="form-control">
                <option value=""><?= $t['unregistered'] ?></option>
                <?php foreach($products ?? [] as $p): ?>
                    <option value="<?= $p->id ?>">
                        <?= htmlspecialchars($p->code) ?><?= !empty($p->name) ? ' - ' . htmlspecialchars($p->name) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="text" name="description[]" class="form-control" required></td>
        <td><input type="number" step="0.01" name="quantity[]" class="form-control row-qty" style="text-align:center; font-weight:700;" value="1.00" required oninput="calcTotals()"></td>
        <td><input type="number" step="0.01" name="unit_price[]" class="form-control row-price" style="text-align:center; font-family:monospace;" value="0.00" required oninput="calcTotals()"></td>
        <td><input type="text" class="form-control row-total" style="text-align:center; font-family:monospace; background:#f1f5f9; color:var(--c-red); font-weight:bold;" value="0.00" readonly></td>
        <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove(); calcTotals();"><i class="ph-bold ph-trash"></i></button></td>
    </tr>
</template>

<script>
function addRow() {
    const template = document.getElementById('rowTemplate');
    const clone = template.content.cloneNode(true);
    document.getElementById('itemsBody').appendChild(clone);
}
<?php if(!$isEdit || empty($items)): ?> document.addEventListener('DOMContentLoaded', addRow); <?php endif; ?>

function calcTotals() {
    let subtotal = 0;
    const rows = document.querySelectorAll('#itemsBody tr');
    rows.forEach(row => {
        const qty   = parseFloat(row.querySelector('.row-qty').value) || 0;
        const price = parseFloat(row.querySelector('.row-price').value) || 0;
        const lineTotal = qty * price;
        row.querySelector('.row-total').value = lineTotal.toFixed(2);
        subtotal += lineTotal;
    });

    document.getElementById('txtSubtotal').innerText = subtotal.toFixed(2);
    const tax = parseFloat(document.getElementById('inpTax').value) || 0;
    const grandTotal = subtotal + tax;
    document.getElementById('txtGrandTotal').innerText = grandTotal.toFixed(2);
}

document.addEventListener('DOMContentLoaded', calcTotals);
</script>