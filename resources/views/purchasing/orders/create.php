<?php
// Path: resources/views/purchasing/orders/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();

$isEdit = isset($order) && $order !== null;
$actionUrl = $isEdit ? "/ERP/purchasing/orders/{$order->id}/update" : "/ERP/purchasing/orders/store";

$t = [
    'ar' => [
        'title_new' => 'إصدار أمر شراء جديد (PO)',
        'title_edit' => 'تعديل أمر الشراء',
        'basic_info' => 'بيانات أمر الشراء الأساسية',
        'supplier' => 'المورد',
        'select_supplier' => '-- اختر المورد --',
        'po_number' => 'رقم الـ PO (يولد تلقائياً إن تُرك فارغاً)',
        'order_date' => 'تاريخ الإصدار',
        'delivery_date' => 'تاريخ التوريد المتوقع',
        'status' => 'حالة الأمر',
        'status_draft' => 'مسودة',
        'status_sent' => 'مُرسل للمورد',
        'status_partially_received' => 'مستلم جزئياً',
        'status_completed' => 'مكتمل (تم الاستلام)',
        'status_cancelled' => 'ملغي',
        'items_pricing' => 'الأصناف والتسعير',
        'add_item' => 'إضافة صنف',
        'col_prod' => 'الصنف (الكود والاسم)',
        'col_desc' => 'الوصف',
        'col_qty' => 'الكمية',
        'col_price' => 'سعر الوحدة',
        'col_total' => 'الإجمالي',
        'col_remove' => 'إزالة',
        'notes_label' => 'ملاحظات للمورد وشروط الدفع',
        'subtotal' => 'الإجمالي الفرعي (Subtotal):',
        'discount' => 'الخصم (Discount):',
        'tax' => 'الضريبة المضافة (Tax):',
        'grand_total' => 'الصافي المطلوب (Total):',
        'unregistered' => '-- غير مسجل / يدوي --',
        'cancel' => 'إلغاء وتراجع',
        'save' => 'إصدار أمر الشراء',
        'update' => 'تحديث الأمر'
    ],
    'en' => [
        'title_new' => 'Issue New Purchase Order (PO)',
        'title_edit' => 'Edit Purchase Order',
        'basic_info' => 'Purchase Order Basic Info',
        'supplier' => 'Supplier',
        'select_supplier' => '-- Select Supplier --',
        'po_number' => 'PO Number (Auto generated if empty)',
        'order_date' => 'Issue Date',
        'delivery_date' => 'Expected Delivery Date',
        'status' => 'Order Status',
        'status_draft' => 'Draft',
        'status_sent' => 'Sent to Vendor',
        'status_partially_received' => 'Partially Received',
        'status_completed' => 'Completed',
        'status_cancelled' => 'Cancelled',
        'items_pricing' => 'Items & Pricing',
        'add_item' => 'Add Item',
        'col_prod' => 'Product (Code & Name)',
        'col_desc' => 'Description',
        'col_qty' => 'Quantity',
        'col_price' => 'Unit Price',
        'col_total' => 'Total',
        'col_remove' => 'Remove',
        'notes_label' => 'Vendor Notes & Payment Terms',
        'subtotal' => 'Subtotal:',
        'discount' => 'Discount:',
        'tax' => 'Tax:',
        'grand_total' => 'Total Due:',
        'unregistered' => '-- Unregistered / Manual --',
        'cancel' => 'Cancel',
        'save' => 'Issue Purchase Order',
        'update' => 'Update Order'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-blue: #2563eb;
        --c-blue-dark: #1d4ed8;
        --c-blue-light: #dbeafe;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
        --c-bg: #f8fafc;
    }

    .form-wrapper { max-width: 1000px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .back-btn { width: 42px; height: 42px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; color: var(--c-text-muted); text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-blue-light); color: var(--c-blue); border-color: #bfdbfe; }
    .form-title { font-size: 1.6rem; font-weight: 800; color: var(--c-text-dark); margin: 0; }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 24px; border-top: 4px solid var(--c-blue); }
    .panel-title { font-size: 1.1rem; color: var(--c-text-dark); font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 10px;}
    
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: var(--c-bg); transition: 0.2s; color: var(--c-text-dark); }
    .form-control:focus { border-color: var(--c-blue); box-shadow: 0 0 0 4px var(--c-blue-light); background: #ffffff;}
    .input-label { font-size: 0.85rem; font-weight: 800; color: var(--c-text-muted); margin-bottom: 8px; display: block; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }

    .items-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 16px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
    .items-table th { padding: 14px 12px; background: #f8fafc; color: var(--c-text-muted); font-size: 0.8rem; font-weight: 800; text-transform: uppercase; text-align: start; border-bottom: 1px solid #e2e8f0;}
    .items-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; background: #ffffff; }
    
    .btn-remove-row { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; }
    .btn-add-row { background: var(--c-blue-light); color: var(--c-blue); border: 1px solid #bfdbfe; padding: 8px 16px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    
    .totals-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #cbd5e1; margin-top: 20px; width: 350px; float: <?= $isRtl ? 'left' : 'right' ?>; }
    .totals-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-weight: 700; color: #475569; font-size: 0.95rem; }
    .totals-row.grand { border-top: 2px dashed #cbd5e1; padding-top: 10px; font-size: 1.2rem; color: var(--c-blue); font-weight: 900; }
    
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; clear: both;}
    .btn-submit { background: linear-gradient(135deg, var(--c-blue), var(--c-blue-dark)); color: white; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: var(--c-text-muted); padding: 12px 28px; border-radius: 12px; font-weight: 800; text-decoration: none; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <a href="/ERP/purchasing/orders" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <h2 class="form-title"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-info" style="color:var(--c-blue);"></i> <?= $t['basic_info'] ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $t['supplier'] ?> <span style="color:red">*</span></label>
                    <select name="supplier_id" class="form-control" required>
                        <option value=""><?= $t['select_supplier'] ?></option>
                        <?php foreach($suppliers ?? [] as $s): ?>
                            <option value="<?= $s->id ?>" <?= ($isEdit && $order->supplier_id == $s->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s->name_ar) ?> <?= !empty($s->code) ? "({$s->code})" : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="input-label"><?= $t['po_number'] ?></label>
                    <input type="text" name="po_number" class="form-control" style="font-family:monospace; color:var(--c-blue); font-weight:bold;" value="<?= $isEdit ? htmlspecialchars($order->po_number) : '' ?>" <?= $isEdit ? 'readonly' : '' ?>>
                </div>
            </div>
            <div class="grid-3" style="margin-top: 24px;">
                <div>
                    <label class="input-label"><?= $t['order_date'] ?></label>
                    <input type="date" name="order_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($order->order_date) : date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['delivery_date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="delivery_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($order->delivery_date) : '' ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['status'] ?></label>
                    <select name="status" class="form-control" style="font-weight: 800;">
                        <?php $st = $isEdit ? $order->status : 'draft'; ?>
                        <option value="draft" <?= $st=='draft' ? 'selected' : '' ?>><?= $t['status_draft'] ?></option>
                        <option value="sent" <?= $st=='sent' ? 'selected' : '' ?>><?= $t['status_sent'] ?></option>
                        <option value="partially_received" <?= $st=='partially_received' ? 'selected' : '' ?>><?= $t['status_partially_received'] ?></option>
                        <option value="completed" <?= $st=='completed' ? 'selected' : '' ?>><?= $t['status_completed'] ?></option>
                        <option value="cancelled" <?= $st=='cancelled' ? 'selected' : '' ?>><?= $t['status_cancelled'] ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="panel-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h3 class="panel-title" style="margin:0; border:none; padding:0;"><i class="ph-duotone ph-list-numbers" style="color:var(--c-blue);"></i> <?= $t['items_pricing'] ?></h3>
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
                        <th style="width: 6%; text-align: center;"><?= $t['col_remove'] ?></th>
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
                            <td><input type="text" class="form-control row-total" style="text-align:center; font-family:monospace; background:#f1f5f9; color:var(--c-blue); font-weight:bold;" value="<?= $item->total_price ?>" readonly></td>
                            <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove(); calcTotals();"><i class="ph-bold ph-trash"></i></button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <div style="display:flex; justify-content:space-between; margin-top: 24px; flex-wrap:wrap; gap:16px;">
                <div style="flex: 1; min-width:280px;">
                    <label class="input-label"><?= $t['notes_label'] ?></label>
                    <textarea name="notes" class="form-control" rows="5"><?= $isEdit ? htmlspecialchars($order->notes ?? '') : '' ?></textarea>
                </div>
                
                <div class="totals-box">
                    <div class="totals-row">
                        <span><?= $t['subtotal'] ?></span>
                        <span id="txtSubtotal">0.00</span>
                    </div>
                    <div class="totals-row">
                        <span><?= $t['discount'] ?></span>
                        <span><input type="number" step="0.01" name="discount_amount" id="inpDiscount" class="form-control" style="width: 110px; padding:4px; text-align:end;" value="<?= $isEdit ? $order->discount_amount : '0.00' ?>" oninput="calcTotals()"></span>
                    </div>
                    <div class="totals-row">
                        <span><?= $t['tax'] ?></span>
                        <span><input type="number" step="0.01" name="tax_amount" id="inpTax" class="form-control" style="width: 110px; padding:4px; text-align:end;" value="<?= $isEdit ? $order->tax_amount : '0.00' ?>" oninput="calcTotals()"></span>
                    </div>
                    <div class="totals-row grand">
                        <span><?= $t['grand_total'] ?></span>
                        <div><span id="txtGrandTotal">0.00</span> <span style="font-size:0.8rem; color:#64748b;"><?= $currency ?></span></div>
                    </div>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/purchasing/orders" class="btn-cancel"><?= $t['cancel'] ?></a>
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
        <td><input type="text" class="form-control row-total" style="text-align:center; font-family:monospace; background:#f1f5f9; color:var(--c-blue); font-weight:bold;" value="0.00" readonly></td>
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