<?php
// Path: resources/views/sales/price_lists/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isRtl();
$currency = current_currency(); // استخدام عملة الجلسة الموحدة

$isEdit = isset($priceList) && $priceList !== null;
$actionUrl = $isEdit ? "/ERP/sales/price-lists/{$priceList->id}/update" : "/ERP/sales/price-lists/store";

$existingLines = $lines ?? [];

$t = [
    'ar' => [
        'title' => $isEdit ? 'تعديل قائمة الأسعار' : 'إنشاء قائمة أسعار جديدة',
        'master_info' => 'البيانات الأساسية للشريحة',
        'name_ar' => 'اسم القائمة (عربي)',
        'name_en' => 'اسم القائمة (إنجليزي)',
        'code' => 'الكود (تلقائي إن تُرك فارغاً)',
        'currency' => 'عملة التسعير للقائمة',
        'status' => 'حالة القائمة',
        'status_active' => 'مفعلة (Active)',
        'status_inactive' => 'معطلة (Inactive)',
        'notes' => 'ملاحظات وشروط القائمة',
        'lines_title' => 'تحديد أسعار الأصناف والخصومات',
        'col_prod' => 'الصنف / المنتج',
        'col_min_qty' => 'الحد الأدنى للكمية',
        'col_price' => 'السعر الخاص',
        'col_discount' => 'نسبة الخصم %',
        'col_delete' => 'حذف',
        'add_line' => 'إضافة صنف للشريحة',
        'select_prod' => '-- اختر المنتج --',
        'cancel' => 'إلغاء',
        'save' => $isEdit ? 'حفظ التعديلات' : 'حفظ قائمة الأسعار'
    ],
    'en' => [
        'title' => $isEdit ? 'Edit Price List' : 'New Price List',
        'master_info' => 'Price List Basic Info',
        'name_ar' => 'List Name (Arabic)',
        'name_en' => 'List Name (English)',
        'code' => 'Code (Auto if empty)',
        'currency' => 'Pricing Currency',
        'status' => 'Status',
        'status_active' => 'Active',
        'status_inactive' => 'Inactive',
        'notes' => 'Notes & Terms',
        'lines_title' => 'Items Pricing & Discounts',
        'col_prod' => 'Product / Item',
        'col_min_qty' => 'Min Quantity',
        'col_price' => 'Special Price',
        'col_discount' => 'Discount %',
        'col_delete' => 'Delete',
        'add_line' => 'Add Item to Tier',
        'select_prod' => '-- Select Product --',
        'cancel' => 'Cancel',
        'save' => $isEdit ? 'Save Changes' : 'Save Price List'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    .form-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-title { font-size: 1.8rem; font-weight: 800; color: #0f172a; margin: 0 0 24px 0; display: flex; align-items: center; gap: 12px; }
    .back-btn { width: 36px; height: 36px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .panel-title { font-size: 1.1rem; color: #0f172a; font-weight: 800; margin: 0 0 16px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 8px;}
    
    .input-label { font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px; display: block; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.9rem; outline: none; background: #f8fafc; transition: 0.2s; }
    .form-control:focus { border-color: #4338ca; box-shadow: 0 0 0 3px #e0e7ff; background: #ffffff;}
    
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width: 768px){ .grid-3 { grid-template-columns: 1fr; } }
    
    .lines-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .lines-table th { padding: 12px; text-align: start; color: #64748b; font-weight: 700; background: #f8fafc; border-bottom: 1px solid #cbd5e1; }
    .lines-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn { padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; font-family: inherit; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none;}
    .btn-submit { background: linear-gradient(135deg, #4338ca, #3730a3); color: white; box-shadow: 0 4px 12px rgba(67, 56, 202, 0.25); }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(67, 56, 202, 0.35); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <h2 class="form-title">
        <a href="/ERP/sales/price-lists" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <?= $t['title'] ?>
        <?php if($isEdit): ?>
            <span style="margin-inline-start: auto; color: #4338ca; font-family: monospace; font-size: 1.2rem;"># <?= htmlspecialchars($priceList->code) ?></span>
        <?php endif; ?>
    </h2>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST" id="priceListForm">
        
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-info text-indigo-600"></i> <?= $t['master_info'] ?></h3>
            <div class="grid-3">
                <div>
                    <label class="input-label"><?= $t['name_ar'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($priceList->name_ar) : '' ?>" required>
                </div>

                <div>
                    <label class="input-label"><?= $t['name_en'] ?></label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($priceList->name_en ?? '') : '' ?>">
                </div>

                <div>
                    <label class="input-label"><?= $t['code'] ?></label>
                    <input type="text" name="code" class="form-control" value="<?= $isEdit ? htmlspecialchars($priceList->code) : '' ?>" <?= $isEdit ? 'readonly' : '' ?>>
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div>
                    <label class="input-label"><?= $t['currency'] ?> <span style="color:red">*</span></label>
                    <!-- يتم حفظ العملة الأصلية للقائمة، ولكن المستخدم يعرض القيم بعملته -->
                    <select name="currency" id="currencySelect" class="form-control" required>
                        <?php $curr = $isEdit ? $priceList->currency : $currency; ?>
                        <option value="EGP" <?= $curr === 'EGP' ? 'selected' : '' ?>>EGP</option>
                        <option value="SAR" <?= $curr === 'SAR' ? 'selected' : '' ?>>SAR</option>
                        <option value="USD" <?= $curr === 'USD' ? 'selected' : '' ?>>USD</option>
                        <option value="EUR" <?= $curr === 'EUR' ? 'selected' : '' ?>>EUR</option>
                    </select>
                </div>

                <div>
                    <label class="input-label"><?= $t['status'] ?></label>
                    <select name="is_active" class="form-control">
                        <option value="1" <?= ($isEdit && $priceList->is_active == 1) ? 'selected' : '' ?>><?= $t['status_active'] ?></option>
                        <option value="0" <?= ($isEdit && $priceList->is_active == 0) ? 'selected' : '' ?>><?= $t['status_inactive'] ?></option>
                    </select>
                </div>

                <div>
                    <label class="input-label"><?= $t['notes'] ?></label>
                    <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($priceList->notes ?? '') : '' ?>">
                </div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-list-numbers text-indigo-600"></i> <?= $t['lines_title'] ?></h3>
            <div style="overflow-x: auto;">
                <table class="lines-table" id="linesTable">
                    <thead>
                        <tr>
                            <th style="width: 40%;"><?= $t['col_prod'] ?></th>
                            <th style="width: 18%;"><?= $t['col_min_qty'] ?></th>
                            <!-- عرض رمز العملة الموحد بجوار السعر -->
                            <th style="width: 22%;"><?= $t['col_price'] ?> (<?= htmlspecialchars($currency) ?>)</th>
                            <th style="width: 12%;"><?= $t['col_discount'] ?></th>
                            <th style="width: 8%; text-align: center;"><?= $t['col_delete'] ?></th>
                        </tr>
                    </thead>
                    <tbody id="linesBody">
                        <!-- Lines via JS -->
                    </tbody>
                </table>
            </div>
            
            <button type="button" onclick="addLine()" style="margin-top: 16px; background: transparent; border: 1px dashed #4338ca; color: #4338ca; padding: 10px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px;"><i class="ph-bold ph-plus"></i> <?= $t['add_line'] ?></button>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/sales/price-lists" class="btn" style="background: #ffffff; border: 1px solid #cbd5e1; color: #475569;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $t['save'] ?></button>
        </div>
    </form>
</div>

<script>
    const products = <?= json_encode($products ?? []) ?>;
    const existingLines = <?= json_encode($existingLines) ?>;
    const txtSelectProd = "<?= $t['select_prod'] ?>";
    let lineIdx = 0;

    function addLine(productId = '', minQty = 1, price = 0, discount = 0) {
        let pOptions = `<option value="">${txtSelectProd}</option>`;
        products.forEach(p => {
            const isSelected = (p.id == productId) ? 'selected' : '';
            // السعر القادم من الداتا بيز تم تحويله للعملة المحددة عبر الكونترولر
            pOptions += `<option value="${p.id}" data-price="${p.sale_price}" ${isSelected}>[${p.sku || 'N/A'}] ${p.name} (${parseFloat(p.sale_price).toFixed(2)})</option>`;
        });

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <select name="items[${lineIdx}][product_id]" class="form-control" onchange="setOriginalPrice(this, ${lineIdx})" required>${pOptions}</select>
            </td>
            <td><input type="number" name="items[${lineIdx}][min_quantity]" class="form-control" value="${minQty}" min="1" step="0.01" required></td>
            <td><input type="number" name="items[${lineIdx}][price]" id="price_${lineIdx}" class="form-control" value="${parseFloat(price).toFixed(2)}" step="0.01" required style="font-weight: 800; font-family: monospace; color: #4338ca;"></td>
            <td><input type="number" name="items[${lineIdx}][discount_percentage]" class="form-control" value="${discount}" min="0" max="100" step="0.01"></td>
            <td style="text-align: center;"><button type="button" onclick="this.closest('tr').remove();" style="background: none; border: none; color: #ef4444; font-size: 1.2rem; cursor: pointer;"><i class="ph-bold ph-trash"></i></button></td>
        `;
        document.getElementById('linesBody').appendChild(tr);
        lineIdx++;
    }

    function setOriginalPrice(select, idx) {
        const defaultPrice = select.options[select.selectedIndex].getAttribute('data-price');
        if(defaultPrice && document.getElementById(`price_${idx}`).value == 0) {
            document.getElementById(`price_${idx}`).value = parseFloat(defaultPrice).toFixed(2);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (existingLines && existingLines.length > 0) {
            existingLines.forEach(line => {
                addLine(line.product_id, line.min_quantity, line.price, line.discount_percentage);
            });
        } else {
            addLine('', 1, 0, 0);
        }
    });
</script>