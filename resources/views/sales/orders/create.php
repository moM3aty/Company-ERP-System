<?php
// Path: resources/views/sales/orders/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isRtl();
$currency = current_currency();

$isEdit = isset($order) && $order !== null;
$actionUrl = $isEdit ? "/ERP/sales/orders/{$order->id}/update" : "/ERP/sales/orders/store";

$t = [
    'ar' => [
        'title' => $isEdit ? 'تعديل أمر البيع' : 'إنشاء أمر بيع جديد',
        'master_info' => 'البيانات الأساسية',
        'customer' => 'العميل',
        'select_customer' => '-- اختر العميل --',
        'order_date' => 'تاريخ الطلب',
        'exp_date' => 'تاريخ التسليم المتوقع',
        'status' => 'حالة الطلب',
        'status_draft' => 'مسودة (Draft)',
        'status_confirmed' => 'مؤكد (Confirmed)',
        'status_processing' => 'قيد التجهيز (Processing)',
        'status_shipped' => 'تم الشحن (Shipped)',
        'status_delivered' => 'تم التسليم (Delivered)',
        'status_cancelled' => 'ملغي (Cancelled)',
        'notes' => 'ملاحظات / تعليمات الشحن',
        'notes_ph' => 'تعليمات إضافية للتسليم...',
        'lines_title' => 'تفاصيل المنتجات المطلوبة',
        'col_prod' => 'المنتج / الوصف',
        'col_qty' => 'الكمية',
        'col_price' => 'سعر الوحدة',
        'col_total' => 'الإجمالي',
        'col_delete' => 'حذف',
        'add_line' => 'إضافة سطر جديد',
        'custom_prod' => '-- منتج مخصص --',
        'desc_ph' => 'وصف المنتج أو الخدمة...',
        'subtotal' => 'المجموع الفرعي:',
        'tax' => 'الضريبة (15%):',
        'grand_total' => 'الإجمالي النهائي:',
        'cancel' => 'إلغاء',
        'save' => $isEdit ? 'حفظ التعديلات' : 'تأكيد أمر البيع'
    ],
    'en' => [
        'title' => $isEdit ? 'Edit Sales Order' : 'Create Sales Order',
        'master_info' => 'Basic Information',
        'customer' => 'Customer',
        'select_customer' => '-- Select Customer --',
        'order_date' => 'Order Date',
        'exp_date' => 'Expected Delivery',
        'status' => 'Order Status',
        'status_draft' => 'Draft',
        'status_confirmed' => 'Confirmed',
        'status_processing' => 'Processing',
        'status_shipped' => 'Shipped',
        'status_delivered' => 'Delivered',
        'status_cancelled' => 'Cancelled',
        'notes' => 'Notes / Shipping Instructions',
        'notes_ph' => 'Additional delivery instructions...',
        'lines_title' => 'Order Line Items',
        'col_prod' => 'Product / Description',
        'col_qty' => 'Quantity',
        'col_price' => 'Unit Price',
        'col_total' => 'Total',
        'col_delete' => 'Delete',
        'add_line' => 'Add New Line',
        'custom_prod' => '-- Custom Product --',
        'desc_ph' => 'Product or service description...',
        'subtotal' => 'Subtotal:',
        'tax' => 'Tax (15%):',
        'grand_total' => 'Grand Total:',
        'cancel' => 'Cancel',
        'save' => $isEdit ? 'Save Changes' : 'Confirm Sales Order'
    ]
][$isRtl ? 'ar' : 'en'];

$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_err']);
?>

<style>
    .form-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-title { font-size: 1.8rem; font-weight: 800; color: #0f172a; margin: 0 0 24px 0; display: flex; align-items: center; gap: 12px; }
    .back-btn { width: 36px; height: 36px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: 0.2s; }
    .back-btn:hover { background: #f8fafc; color: #0f172a; }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .panel-title { font-size: 1.1rem; color: #0f172a; font-weight: 800; margin: 0 0 16px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 8px;}
    
    .input-label { font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px; display: block; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.9rem; outline: none; background: #f8fafc; transition: 0.2s; }
    .form-control:focus { border-color: #34d399; box-shadow: 0 0 0 3px #d1fae5; background: #ffffff;}
    
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width: 768px){ .grid-3 { grid-template-columns: 1fr; } }
    
    .lines-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .lines-table th { padding: 12px; text-align: start; color: #64748b; font-weight: 700; background: #f8fafc; border-bottom: 1px solid #cbd5e1; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px;}
    .lines-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
    
    .total-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; width: 300px; margin-top: 24px; <?= $isRtl ? 'margin-right: auto;' : 'margin-left: auto;' ?> }
    @media(max-width: 768px){ .total-box { width: 100%; } }
    .total-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-weight: 600; color: #475569; }
    .grand-total { border-top: 2px solid #cbd5e1; padding-top: 8px; margin-top: 8px; font-size: 1.2rem; font-weight: 900; color: #0f172a; }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; box-shadow: 0 -4px 10px rgba(0,0,0,0.03); }
    .btn { padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; font-family: inherit; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none;}
    .btn-submit { background: linear-gradient(135deg, #10b981, #059669); color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <h2 class="form-title">
        <a href="/ERP/sales/orders" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <?= $t['title'] ?> <?= $isEdit ? " (#{$order->order_no})" : '' ?>
    </h2>

    <?php if($flashErr): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-warning-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashErr) ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST" id="orderForm">
        
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-info text-emerald-600"></i> <?= $t['master_info'] ?></h3>
            <div class="grid-3">
                <div>
                    <label class="input-label"><?= $t['customer'] ?> <span style="color:red">*</span></label>
                    <select name="customer_id" class="form-control" required>
                        <option value=""><?= $t['select_customer'] ?></option>
                        <?php foreach($customers ?? [] as $c): ?>
                            <option value="<?= $c->id ?>" <?= ($isEdit && $order->customer_id == $c->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($isRtl ? ($c->name_ar ?? $c->name_en) : ($c->name_en ?? $c->name_ar)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="input-label"><?= $t['order_date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="order_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($order->order_date) : date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['exp_date'] ?></label>
                    <input type="date" name="expected_delivery_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($order->expected_date ?? '') : date('Y-m-d', strtotime('+3 days')) ?>">
                </div>
            </div>
            <div class="grid-3" style="margin-top: 20px;">
                <div>
                    <label class="input-label"><?= $t['status'] ?></label>
                    <select name="status" class="form-control">
                        <?php $currStatus = $isEdit ? $order->status : 'confirmed'; ?>
                        <option value="draft" <?= $currStatus == 'draft' ? 'selected' : '' ?>><?= $t['status_draft'] ?></option>
                        <option value="confirmed" <?= $currStatus == 'confirmed' ? 'selected' : '' ?>><?= $t['status_confirmed'] ?></option>
                        <option value="processing" <?= $currStatus == 'processing' ? 'selected' : '' ?>><?= $t['status_processing'] ?></option>
                        <option value="shipped" <?= $currStatus == 'shipped' ? 'selected' : '' ?>><?= $t['status_shipped'] ?></option>
                        <option value="delivered" <?= $currStatus == 'delivered' ? 'selected' : '' ?>><?= $t['status_delivered'] ?></option>
                        <option value="cancelled" <?= $currStatus == 'cancelled' ? 'selected' : '' ?>><?= $t['status_cancelled'] ?></option>
                    </select>
                </div>
                <div style="grid-column: span 2;">
                    <label class="input-label"><?= $t['notes'] ?></label>
                    <input type="text" name="notes" class="form-control" placeholder="<?= $t['notes_ph'] ?>" value="<?= $isEdit ? htmlspecialchars($order->notes ?? '') : '' ?>">
                </div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-list-numbers text-emerald-600"></i> <?= $t['lines_title'] ?></h3>
            <div style="overflow-x: auto;">
                <table class="lines-table" id="linesTable">
                    <thead>
                        <tr>
                            <th style="width: 35%;"><?= $t['col_prod'] ?></th>
                            <th style="width: 15%;"><?= $t['col_qty'] ?></th>
                            <th style="width: 20%;"><?= $t['col_price'] ?> (<?= htmlspecialchars($currency) ?>)</th>
                            <th style="width: 20%;"><?= $t['col_total'] ?> (<?= htmlspecialchars($currency) ?>)</th>
                            <th style="width: 10%; text-align: center;"><?= $t['col_delete'] ?></th>
                        </tr>
                    </thead>
                    <tbody id="linesBody">
                        <!-- Lines will be added here via JS -->
                    </tbody>
                </table>
            </div>
            
            <button type="button" onclick="addLine()" style="margin-top: 16px; background: transparent; border: 1px dashed #10b981; color: #059669; padding: 10px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: 0.2s;" onmouseover="this.style.background='#ecfdf5';" onmouseout="this.style.background='transparent';"><i class="ph-bold ph-plus"></i> <?= $t['add_line'] ?></button>

            <div class="total-box">
                <div class="total-row"><span><?= $t['subtotal'] ?></span> <span><span id="subtotalLabel">0.00</span> <?= htmlspecialchars($currency) ?></span></div>
                <div class="total-row"><span><?= $t['tax'] ?></span> <span><span id="taxLabel">0.00</span> <?= htmlspecialchars($currency) ?></span></div>
                <div class="total-row grand-total"><span><?= $t['grand_total'] ?></span> <span style="color: #059669;"><span id="totalLabel">0.00</span> <?= htmlspecialchars($currency) ?></span></div>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/sales/orders" class="btn" style="background: #ffffff; border: 1px solid #cbd5e1; color: #475569; text-decoration: none;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $t['save'] ?></button>
        </div>
    </form>
</div>

<script>
    const products = <?= json_encode($products ?? []) ?>;
    const existingLines = <?= json_encode($lines ?? []) ?>;
    const txtCustomProd = "<?= $t['custom_prod'] ?>";
    const txtDescPh = "<?= $t['desc_ph'] ?>";
    let lineIdx = 0;

    function addLine(lineData = null) {
        let pOptions = `<option value="">${txtCustomProd}</option>`;
        products.forEach(p => {
            const selected = (lineData && lineData.product_id == p.id) ? 'selected' : '';
            pOptions += `<option value="${p.id}" data-price="${p.sale_price}" ${selected}>${p.name}</option>`;
        });

        const desc = lineData ? lineData.description : '';
        const qty = lineData ? parseFloat(lineData.quantity) : 1;
        const price = lineData ? parseFloat(lineData.unit_price).toFixed(2) : '0.00';
        const total = lineData ? parseFloat(lineData.total).toFixed(2) : '0.00';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <select name="items[${lineIdx}][product_id]" class="form-control" onchange="setPrice(this, ${lineIdx})" style="margin-bottom:6px;">${pOptions}</select>
                <input type="text" name="items[${lineIdx}][description]" class="form-control" placeholder="${txtDescPh}" value="${desc.replace(/"/g, '&quot;')}" required>
            </td>
            <td><input type="number" name="items[${lineIdx}][quantity]" id="qty_${lineIdx}" class="form-control" value="${qty}" min="0.01" step="0.01" oninput="calcLine(${lineIdx})"></td>
            <td><input type="number" name="items[${lineIdx}][unit_price]" id="price_${lineIdx}" class="form-control" value="${price}" step="0.01" oninput="calcLine(${lineIdx})"></td>
            <td><input type="text" id="total_${lineIdx}" class="form-control" value="${total}" readonly style="background: transparent; border: none; font-weight: 800; font-family: monospace; color: #0f172a; outline: none; box-shadow: none;"></td>
            <td style="text-align: center;"><button type="button" onclick="this.closest('tr').remove(); calcGrand();" style="background: none; border: none; color: #ef4444; font-size: 1.2rem; cursor: pointer; padding: 4px; border-radius: 4px; transition: 0.2s;" onmouseover="this.style.background='#fef2f2';" onmouseout="this.style.background='transparent';"><i class="ph-bold ph-trash"></i></button></td>
        `;
        document.getElementById('linesBody').appendChild(tr);
        lineIdx++;
    }

    function setPrice(select, idx) {
        const price = select.options[select.selectedIndex].getAttribute('data-price');
        if(price) {
            document.getElementById(`price_${idx}`).value = parseFloat(price).toFixed(2);
            calcLine(idx);
        }
    }

    function calcLine(idx) {
        const qty = parseFloat(document.getElementById(`qty_${idx}`).value) || 0;
        const price = parseFloat(document.getElementById(`price_${idx}`).value) || 0;
        document.getElementById(`total_${idx}`).value = (qty * price).toFixed(2);
        calcGrand();
    }

    function calcGrand() {
        let subtotal = 0;
        document.querySelectorAll('input[id^="total_"]').forEach(el => subtotal += parseFloat(el.value) || 0);
        let tax = subtotal * 0.15;
        let total = subtotal + tax;

        document.getElementById('subtotalLabel').innerText = subtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('taxLabel').innerText = tax.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('totalLabel').innerText = total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    document.addEventListener('DOMContentLoaded', () => {
        if(existingLines.length > 0) {
            existingLines.forEach(line => addLine(line));
            calcGrand();
        } else {
            addLine();
        }
    });
</script>