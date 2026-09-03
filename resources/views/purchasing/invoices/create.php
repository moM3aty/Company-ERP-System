<?php
// Path: resources/views/purchasing/invoices/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();
$isEdit = isset($invoice) && $invoice !== null;
$actionUrl = $isEdit ? "/ERP/purchasing/invoices/{$invoice->id}/update" : "/ERP/purchasing/invoices/store";

$t = [
    'ar' => [
        'title_new' => 'إصدار فاتورة شراء جديدة (Bill)',
        'title_edit' => 'تعديل فاتورة الشراء',
        'basic_info' => 'البيانات الأساسية للفاتورة',
        'supplier' => 'المورد',
        'select_supplier' => '-- اختر المورد --',
        'po_link' => 'ربط بأمر شراء (PO)',
        'no_po' => '-- مباشر / بدون أمر شراء --',
        'inv_num' => 'رقم الفاتورة بالنظام (تلقائي)',
        'sup_inv_num' => 'رقم فاتورة المورد (Supplier Bill No.)',
        'inv_date' => 'تاريخ الفاتورة',
        'due_date' => 'تاريخ الاستحقاق (Due Date)',
        'status' => 'حالة الدفع للفاتورة',
        'status_unpaid' => 'غير مدفوعة (Unpaid)',
        'status_partially_paid' => 'مدفوعة جزئياً (Partially Paid)',
        'status_paid' => 'مدفوعة بالكامل (Paid)',
        'status_draft' => 'مسودة (Draft)',
        'status_cancelled' => 'ملغاة (Cancelled)',
        'notes' => 'ملاحظات وشروط الفاتورة',
        'items_pricing' => 'بنود الفاتورة والأسعار',
        'add_item' => 'إضافة صنف',
        'col_prod' => 'الصنف (الكود والاسم)',
        'col_desc' => 'الوصف التفصيلي',
        'col_qty' => 'الكمية',
        'col_price' => 'سعر الوحدة',
        'col_total' => 'الإجمالي',
        'subtotal' => 'الإجمالي الفرعي:',
        'discount' => 'الخصم التجاري:',
        'tax' => 'ضريبة القيمة المضافة:',
        'grand_total' => 'إجمالي الفاتورة المطلوب:',
        'paid_amount' => 'المبلغ المدفوع حتى الآن:',
        'cancel' => 'إلغاء وتراجع',
        'save' => 'حفظ وإصدار الفاتورة',
        'update' => 'تحديث الفاتورة',
        'unregistered' => '-- غير مسجل / إدخال يدوي --',
        'remove' => 'إزالة'
    ],
    'en' => [
        'title_new' => 'Create New Purchase Invoice (Bill)',
        'title_edit' => 'Edit Purchase Invoice',
        'basic_info' => 'Basic Invoice Information',
        'supplier' => 'Supplier',
        'select_supplier' => '-- Select Supplier --',
        'po_link' => 'Link to Purchase Order (PO)',
        'no_po' => '-- Direct / No PO --',
        'inv_num' => 'System Invoice No (Auto)',
        'sup_inv_num' => 'Supplier Bill No.',
        'inv_date' => 'Invoice Date',
        'due_date' => 'Due Date',
        'status' => 'Payment Status',
        'status_unpaid' => 'Unpaid',
        'status_partially_paid' => 'Partially Paid',
        'status_paid' => 'Fully Paid',
        'status_draft' => 'Draft',
        'status_cancelled' => 'Cancelled',
        'notes' => 'Invoice Notes & Terms',
        'items_pricing' => 'Invoice Items & Pricing',
        'add_item' => 'Add Item',
        'col_prod' => 'Product (Code & Name)',
        'col_desc' => 'Description',
        'col_qty' => 'Quantity',
        'col_price' => 'Unit Price',
        'col_total' => 'Total',
        'subtotal' => 'Subtotal:',
        'discount' => 'Commercial Discount:',
        'tax' => 'VAT / Tax:',
        'grand_total' => 'Grand Total Due:',
        'paid_amount' => 'Amount Paid So Far:',
        'cancel' => 'Cancel & Return',
        'save' => 'Save & Issue Invoice',
        'update' => 'Update Invoice',
        'unregistered' => '-- Unregistered / Manual --',
        'remove' => 'Remove'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-rose: #e11d48;
        --c-rose-dark: #be123c;
        --c-rose-light: #ffe4e6;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
        --c-bg: #f8fafc;
    }

    .form-wrapper { max-width: 1050px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .back-btn { width: 42px; height: 42px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; color: var(--c-text-muted); text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-rose-light); color: var(--c-rose); border-color: #fecdd3; }
    .form-title { font-size: 1.6rem; font-weight: 800; color: var(--c-text-dark); margin: 0; }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 24px; border-top: 4px solid var(--c-rose); }
    .panel-title { font-size: 1.1rem; color: var(--c-text-dark); font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 10px;}
    
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: var(--c-bg); transition: 0.2s; color: var(--c-text-dark); }
    .form-control:focus { border-color: var(--c-rose); box-shadow: 0 0 0 4px var(--c-rose-light); background: #ffffff;}
    .input-label { font-size: 0.85rem; font-weight: 800; color: var(--c-text-muted); margin-bottom: 8px; display: block; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }

    .items-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 16px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
    .items-table th { padding: 14px 12px; background: #f8fafc; color: var(--c-text-muted); font-size: 0.8rem; font-weight: 800; text-transform: uppercase; text-align: start; border-bottom: 1px solid #e2e8f0;}
    .items-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; background: #ffffff; }
    
    .btn-remove-row { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; }
    .btn-remove-row:hover { background: #fee2e2; color: #b91c1c; }
    .btn-add-row { background: var(--c-rose-light); color: var(--c-rose); border: 1px solid #fecdd3; padding: 8px 16px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s;}
    .btn-add-row:hover { background: var(--c-rose); color: #fff; }
    
    .totals-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #cbd5e1; margin-top: 20px; width: 400px; float: <?= $isRtl ? 'left' : 'right' ?>; }
    .totals-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-weight: 700; color: #475569; font-size: 0.95rem; }
    .totals-row.grand { border-top: 2px dashed #cbd5e1; padding-top: 10px; font-size: 1.2rem; color: var(--c-rose); font-weight: 900; }
    
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; clear: both;}
    .btn-submit { background: linear-gradient(135deg, var(--c-rose), var(--c-rose-dark)); color: white; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25); transition: 0.2s;}
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(225, 29, 72, 0.35); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: var(--c-text-muted); padding: 12px 28px; border-radius: 12px; font-weight: 800; text-decoration: none; transition: 0.2s; }
    .btn-cancel:hover { background: #f1f5f9; color: var(--c-text-dark); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <a href="/ERP/purchasing/invoices" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <h2 class="form-title"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-info" style="color:var(--c-rose);"></i> <?= $t['basic_info'] ?></h3>
            <div class="grid-3">
                <div>
                    <label class="input-label"><?= $t['supplier'] ?> <span style="color:red">*</span></label>
                    <select name="supplier_id" class="form-control" required>
                        <option value=""><?= $t['select_supplier'] ?></option>
                        <?php foreach($suppliers ?? [] as $s): ?>
                            <option value="<?= $s->id ?>" <?= ($isEdit && $invoice->supplier_id == $s->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s->name_ar) ?> <?= !empty($s->code) ? "({$s->code})" : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="input-label"><?= $t['po_link'] ?></label>
                    <select name="po_id" class="form-control">
                        <option value=""><?= $t['no_po'] ?></option>
                        <?php foreach($orders ?? [] as $po): ?>
                            <option value="<?= $po->id ?>" <?= ($isEdit && $invoice->po_id == $po->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($po->po_number) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="input-label"><?= $t['inv_num'] ?></label>
                    <input type="text" name="invoice_number" class="form-control" style="font-family:monospace; color:var(--c-rose); font-weight:bold;" value="<?= $isEdit ? htmlspecialchars($invoice->invoice_number) : '' ?>" <?= $isEdit ? 'readonly' : 'placeholder="AUTO"' ?>>
                </div>
            </div>

            <div class="grid-3" style="margin-top: 24px;">
                <div>
                    <label class="input-label"><?= $t['sup_inv_num'] ?></label>
                    <input type="text" name="supplier_invoice_number" class="form-control" value="<?= $isEdit ? htmlspecialchars($invoice->supplier_invoice_number ?? '') : '' ?>" placeholder="e.g. BILL-2026-901">
                </div>
                <div>
                    <label class="input-label"><?= $t['inv_date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="invoice_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($invoice->invoice_date) : date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['due_date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="due_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($invoice->due_date) : date('Y-m-d', strtotime('+30 days')) ?>" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top: 24px;">
                <div>
                    <label class="input-label"><?= $t['status'] ?></label>
                    <select name="status" class="form-control" style="font-weight: 800;">
                        <?php $st = $isEdit ? $invoice->status : 'unpaid'; ?>
                        <option value="unpaid" <?= $st=='unpaid' ? 'selected' : '' ?>><?= $t['status_unpaid'] ?></option>
                        <option value="partially_paid" <?= $st=='partially_paid' ? 'selected' : '' ?>><?= $t['status_partially_paid'] ?></option>
                        <option value="paid" <?= $st=='paid' ? 'selected' : '' ?>><?= $t['status_paid'] ?></option>
                        <option value="draft" <?= $st=='draft' ? 'selected' : '' ?>><?= $t['status_draft'] ?></option>
                        <option value="cancelled" <?= $st=='cancelled' ? 'selected' : '' ?>><?= $t['status_cancelled'] ?></option>
                    </select>
                </div>
                <div>
                    <label class="input-label"><?= $t['notes'] ?></label>
                    <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($invoice->notes ?? '') : '' ?>">
                </div>
            </div>
        </div>

        <div class="panel-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h3 class="panel-title" style="margin:0; border:none; padding:0;"><i class="ph-duotone ph-list-numbers" style="color:var(--c-rose);"></i> <?= $t['items_pricing'] ?></h3>
                <button type="button" onclick="addRow()" class="btn-add-row"><i class="ph-bold ph-plus"></i> <?= $t['add_item'] ?></button>
            </div>
            
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width: 25%;"><?= $t['col_prod'] ?></th>
                        <th style="width: 30%;"><?= $t['col_desc'] ?> <span style="color:red">*</span></th>
                        <th style="width: 12%; text-align: center;"><?= $t['col_qty'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_price'] ?></th>
                        <th style="width: 12%; text-align: center;"><?= $t['col_total'] ?></th>
                        <th style="width: 6%; text-align: center;"><i class="ph-bold ph-trash"></i></th>
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
                            <td><input type="text" class="form-control row-total" style="text-align:center; font-family:monospace; background:#f1f5f9; color:var(--c-rose); font-weight:bold;" value="<?= $item->total_price ?>" readonly></td>
                            <td style="text-align:center;"><button type="button" class="btn-remove-row" title="<?= $t['remove'] ?>" onclick="this.closest('tr').remove(); calcTotals();"><i class="ph-bold ph-x"></i></button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <div style="display:flex; justify-content:<?= $isRtl ? 'flex-end' : 'flex-end' ?>; margin-top: 24px;">
                <div class="totals-box">
                    <div class="totals-row">
                        <span><?= $t['subtotal'] ?></span>
                        <span id="txtSubtotal">0.00</span>
                    </div>
                    <div class="totals-row">
                        <span><?= $t['discount'] ?></span>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="number" step="0.01" name="discount_amount" id="inpDiscount" class="form-control" style="width: 120px; padding:6px; text-align:end;" value="<?= $isEdit ? $invoice->discount_amount : '0.00' ?>" oninput="calcTotals()">
                            <span style="font-size: 0.8rem; color:var(--c-text-muted);"><?= $currency ?></span>
                        </div>
                    </div>
                    <div class="totals-row">
                        <span><?= $t['tax'] ?></span>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="number" step="0.01" name="tax_amount" id="inpTax" class="form-control" style="width: 120px; padding:6px; text-align:end;" value="<?= $isEdit ? $invoice->tax_amount : '0.00' ?>" oninput="calcTotals()">
                            <span style="font-size: 0.8rem; color:var(--c-text-muted);"><?= $currency ?></span>
                        </div>
                    </div>
                    <div class="totals-row grand">
                        <span><?= $t['grand_total'] ?></span>
                        <div><span id="txtGrandTotal">0.00</span> <span style="font-size: 0.8rem; color:var(--c-text-muted);"><?= $currency ?></span></div>
                    </div>
                    <div class="totals-row" style="margin-top:10px; padding-top:10px; border-top:1px solid #e2e8f0;">
                        <span><?= $t['paid_amount'] ?></span>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="number" step="0.01" name="paid_amount" class="form-control" style="width: 120px; padding:6px; text-align:end; font-weight:bold; color:#059669;" value="<?= $isEdit ? $invoice->paid_amount : '0.00' ?>">
                            <span style="font-size: 0.8rem; color:var(--c-text-muted);"><?= $currency ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/purchasing/invoices" class="btn-cancel"><?= $t['cancel'] ?></a>
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
        <td><input type="text" class="form-control row-total" style="text-align:center; font-family:monospace; background:#f1f5f9; color:var(--c-rose); font-weight:bold;" value="0.00" readonly></td>
        <td style="text-align:center;"><button type="button" class="btn-remove-row" title="<?= $t['remove'] ?>" onclick="this.closest('tr').remove(); calcTotals();"><i class="ph-bold ph-x"></i></button></td>
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
        const qty = parseFloat(row.querySelector('.row-qty').value) || 0;
        const price = parseFloat(row.querySelector('.row-price').value) || 0;
        const lineTotal = qty * price;
        row.querySelector('.row-total').value = lineTotal.toFixed(2);
        subtotal += lineTotal;
    });

    document.getElementById('txtSubtotal').innerText = subtotal.toFixed(2);
    
    const discount = parseFloat(document.getElementById('inpDiscount').value) || 0;
    const tax = parseFloat(document.getElementById('inpTax').value) || 0;
    
    const grandTotal = (subtotal - discount) + tax;
    document.getElementById('txtGrandTotal').innerText = grandTotal.toFixed(2);
}

document.addEventListener('DOMContentLoaded', calcTotals);
</script>