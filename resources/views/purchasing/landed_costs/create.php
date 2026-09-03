<?php
// Path: resources/views/purchasing/landed_costs/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();

$isEdit = isset($landedCost) && $landedCost !== null;
$actionUrl = $isEdit ? "/ERP/purchasing/landed-costs/{$landedCost->id}/update" : "/ERP/purchasing/landed-costs/store";

$t = [
    'ar' => [
        'title_new' => 'تسجيل وتوزيع تكاليف إضافية (Landed Cost)',
        'title_edit' => 'تعديل توزيع التكاليف الإضافية',
        'basic_info' => 'البيانات الأساسية للشحنة والتوزيع',
        'linked_po' => 'أمر الشراء المرتبط (PO)',
        'select_po' => '-- اختر أمر الشراء --',
        'supplier' => 'مورد الشحنة (اختياري)',
        'select_supplier' => '-- عام / غير محدد --',
        'ref_no' => 'رقم القيد (Reference Number)',
        'ref_placeholder' => 'تلقائي',
        'cost_date' => 'تاريخ التكلفة',
        'alloc_method' => 'معيار التوزيع على الأصناف',
        'alloc_by_value' => 'نسبة وتناسب حسب قيمة الصنف (By Value)',
        'alloc_by_qty' => 'توزيع بالتساوي حسب كمية الصنف (By Quantity)',
        'status' => 'حالة القيد',
        'status_posted' => 'مرحل ومضاف للتكلفة',
        'status_allocated' => 'موزع مبدئياً',
        'status_draft' => 'مسودة',
        'notes' => 'ملاحظات والتفاصيل المالية',
        'notes_placeholder' => 'تفاصيل بوليصة الشحن، المخلص الجمركي، إلخ...',
        'items_title' => 'بنود ومصاريف الاستيراد الإضافية',
        'add_item' => 'إضافة بند مصاريف',
        'col_cost_type' => 'نوع المصروف',
        'col_desc' => 'التفاصيل / البيان',
        'col_amount' => 'المبلغ الإجمالي',
        'col_actions' => 'إزالة',
        'type_freight' => 'شحن دولي / بري (Freight)',
        'type_customs' => 'رسوم جمركية (Customs)',
        'type_insurance' => 'تأمين شحنات (Insurance)',
        'type_handling' => 'تفريغ وتعتيق (Handling)',
        'type_broker' => 'عمولة مخلص جمركي',
        'type_other' => 'مصاريف إضافية أخرى',
        'total_landed' => 'إجمالي التكاليف المضافة:',
        'desc_placeholder' => 'اكتب البيان...',
        'cancel' => 'إلغاء وتراجع',
        'save' => 'حفظ وتوزيع التكاليف',
        'update' => 'تحديث القيد'
    ],
    'en' => [
        'title_new' => 'Record & Allocate Landed Costs',
        'title_edit' => 'Edit Landed Cost Allocation',
        'basic_info' => 'Shipment & Allocation Info',
        'linked_po' => 'Linked Purchase Order (PO)',
        'select_po' => '-- Select Purchase Order --',
        'supplier' => 'Shipment Vendor (Optional)',
        'select_supplier' => '-- General / Multiple --',
        'ref_no' => 'Reference Number',
        'ref_placeholder' => 'Auto',
        'cost_date' => 'Cost Date',
        'alloc_method' => 'Allocation Method Across Items',
        'alloc_by_value' => 'Proportional by Item Value (By Value)',
        'alloc_by_qty' => 'Equal / By Item Quantity (By Quantity)',
        'status' => 'Entry Status',
        'status_posted' => 'Posted & Cost Adjusted',
        'status_allocated' => 'Allocated (Draft)',
        'status_draft' => 'Draft',
        'notes' => 'Notes & Financial Details',
        'notes_placeholder' => 'Waybill details, customs broker info, etc...',
        'items_title' => 'Additional Import Expense Line Items',
        'add_item' => 'Add Cost Item',
        'col_cost_type' => 'Cost Type',
        'col_desc' => 'Details / Description',
        'col_amount' => 'Total Amount',
        'col_actions' => 'Remove',
        'type_freight' => 'Freight Charges',
        'type_customs' => 'Customs Duties',
        'type_insurance' => 'Shipment Insurance',
        'type_handling' => 'Handling & Port Fees',
        'type_broker' => 'Customs Broker Fee',
        'type_other' => 'Other Extra Costs',
        'total_landed' => 'Total Additional Costs:',
        'desc_placeholder' => 'Enter details...',
        'cancel' => 'Cancel',
        'save' => 'Save & Allocate Costs',
        'update' => 'Update Allocation'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-indigo: #4338ca;
        --c-indigo-dark: #3730a3;
        --c-indigo-light: #e0e7ff;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
        --c-bg: #f8fafc;
    }

    .form-wrapper { max-width: 1000px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .back-btn { width: 42px; height: 42px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; color: var(--c-text-muted); text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-indigo-light); color: var(--c-indigo); border-color: #c7d2fe; }
    .form-title { font-size: 1.6rem; font-weight: 800; color: var(--c-text-dark); margin: 0; }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 24px; border-top: 4px solid var(--c-indigo); }
    .panel-title { font-size: 1.1rem; color: var(--c-text-dark); font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 10px;}
    
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: var(--c-bg); transition: 0.2s; color: var(--c-text-dark); }
    .form-control:focus { border-color: var(--c-indigo); box-shadow: 0 0 0 4px var(--c-indigo-light); background: #ffffff;}
    .input-label { font-size: 0.85rem; font-weight: 800; color: var(--c-text-muted); margin-bottom: 8px; display: block; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }

    .items-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 16px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
    .items-table th { padding: 14px 12px; background: #f8fafc; color: var(--c-text-muted); font-size: 0.8rem; font-weight: 800; text-transform: uppercase; text-align: start; border-bottom: 1px solid #e2e8f0;}
    .items-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; background: #ffffff; }
    
    .btn-remove-row { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
    .btn-add-row { background: var(--c-indigo-light); color: var(--c-indigo); border: 1px solid #c7d2fe; padding: 8px 16px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    
    .totals-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #cbd5e1; margin-top: 20px; width: 350px; float: <?= $isRtl ? 'left' : 'right' ?>; }
    .totals-row { display: flex; justify-content: space-between; align-items: center; font-size: 1.2rem; color: var(--c-indigo); font-weight: 900; }
    
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn-submit { background: linear-gradient(135deg, var(--c-indigo), var(--c-indigo-dark)); color: white; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(67, 56, 202, 0.25); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: var(--c-text-muted); padding: 12px 28px; border-radius: 12px; font-weight: 800; text-decoration: none; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <a href="/ERP/purchasing/landed-costs" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <h2 class="form-title"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-info" style="color:var(--c-indigo);"></i> <?= $t['basic_info'] ?></h3>
            <div class="grid-3">
                <div>
                    <label class="input-label"><?= $t['linked_po'] ?></label>
                    <select name="po_id" class="form-control">
                        <option value=""><?= $t['select_po'] ?></option>
                        <?php foreach($orders ?? [] as $po): ?>
                            <option value="<?= $po->id ?>" <?= ($isEdit && $landedCost->po_id == $po->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($po->po_number) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="input-label"><?= $t['supplier'] ?></label>
                    <select name="supplier_id" class="form-control">
                        <option value=""><?= $t['select_supplier'] ?></option>
                        <?php foreach($suppliers ?? [] as $s): ?>
                            <option value="<?= $s->id ?>" <?= ($isEdit && $landedCost->supplier_id == $s->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s->name_ar) ?> <?= !empty($s->code) ? "({$s->code})" : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="input-label"><?= $t['ref_no'] ?></label>
                    <input type="text" name="reference_number" class="form-control" style="font-family:monospace; color:var(--c-indigo); font-weight:bold;" value="<?= $isEdit ? htmlspecialchars($landedCost->reference_number) : '' ?>" <?= $isEdit ? 'readonly' : 'placeholder="'.$t['ref_placeholder'].'"' ?>>
                </div>
            </div>

            <div class="grid-3" style="margin-top: 24px;">
                <div>
                    <label class="input-label"><?= $t['cost_date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="cost_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($landedCost->cost_date) : date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['alloc_method'] ?> <span style="color:red">*</span></label>
                    <select name="allocation_method" class="form-control" style="font-weight:800;">
                        <?php $am = $isEdit ? $landedCost->allocation_method : 'by_value'; ?>
                        <option value="by_value" <?= $am=='by_value' ? 'selected' : '' ?>><?= $t['alloc_by_value'] ?></option>
                        <option value="by_quantity" <?= $am=='by_quantity' ? 'selected' : '' ?>><?= $t['alloc_by_qty'] ?></option>
                    </select>
                </div>
                <div>
                    <label class="input-label"><?= $t['status'] ?></label>
                    <select name="status" class="form-control" style="font-weight: 800;">
                        <?php $st = $isEdit ? $landedCost->status : 'posted'; ?>
                        <option value="posted" <?= $st=='posted' ? 'selected' : '' ?>><?= $t['status_posted'] ?></option>
                        <option value="allocated" <?= $st=='allocated' ? 'selected' : '' ?>><?= $t['status_allocated'] ?></option>
                        <option value="draft" <?= $st=='draft' ? 'selected' : '' ?>><?= $t['status_draft'] ?></option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 24px;">
                <label class="input-label"><?= $t['notes'] ?></label>
                <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($landedCost->notes ?? '') : '' ?>" placeholder="<?= $t['notes_placeholder'] ?>">
            </div>
        </div>

        <div class="panel-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h3 class="panel-title" style="margin:0; border:none; padding:0;"><i class="ph-duotone ph-list-plus" style="color:var(--c-indigo);"></i> <?= $t['items_title'] ?></h3>
                <button type="button" onclick="addRow()" class="btn-add-row"><i class="ph-bold ph-plus"></i> <?= $t['add_item'] ?></button>
            </div>
            
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width: 30%;"><?= $t['col_cost_type'] ?> <span style="color:red">*</span></th>
                        <th style="width: 45%;"><?= $t['col_desc'] ?></th>
                        <th style="width: 20%; text-align: center;"><?= $t['col_amount'] ?> (<?= $currency ?>)</th>
                        <th style="width: 5%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <?php if($isEdit && !empty($items)): foreach($items as $item): ?>
                        <tr>
                            <td>
                                <select name="cost_type[]" class="form-control" required>
                                    <option value="شحن دولي / بري" <?= $item->cost_type == 'شحن دولي / بري' ? 'selected' : '' ?>><?= $t['type_freight'] ?></option>
                                    <option value="رسوم جمركية" <?= $item->cost_type == 'رسوم جمركية' ? 'selected' : '' ?>><?= $t['type_customs'] ?></option>
                                    <option value="تأمين شحنات" <?= $item->cost_type == 'تأمين شحنات' ? 'selected' : '' ?>><?= $t['type_insurance'] ?></option>
                                    <option value="تفريغ وتعتيق" <?= $item->cost_type == 'تفريغ وتعتيق' ? 'selected' : '' ?>><?= $t['type_handling'] ?></option>
                                    <option value="عمولة مخلص جمركي" <?= $item->cost_type == 'عمولة مخلص جمركي' ? 'selected' : '' ?>><?= $t['type_broker'] ?></option>
                                    <option value="مصاريف أخرى" <?= $item->cost_type == 'مصاريف أخرى' ? 'selected' : '' ?>><?= $t['type_other'] ?></option>
                                </select>
                            </td>
                            <td><input type="text" name="description[]" class="form-control" value="<?= htmlspecialchars($item->description ?? '') ?>" placeholder="<?= $t['desc_placeholder'] ?>"></td>
                            <td><input type="number" step="0.01" name="amount[]" class="form-control row-amt" style="text-align:center; font-family:monospace; font-weight:bold;" value="<?= $item->amount ?>" required oninput="calcTotal()"></td>
                            <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove(); calcTotal();"><i class="ph-bold ph-trash"></i></button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <div class="totals-box">
                <div class="totals-row">
                    <span><?= $t['total_landed'] ?></span>
                    <div><span id="txtGrandTotal">0.00</span> <span style="font-size:0.8rem; color:#64748b;"><?= $currency ?></span></div>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/purchasing/landed-costs" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>

<template id="rowTemplate">
    <tr>
        <td>
            <select name="cost_type[]" class="form-control" required>
                <option value="شحن دولي / بري"><?= $t['type_freight'] ?></option>
                <option value="رسوم جمركية"><?= $t['type_customs'] ?></option>
                <option value="تأمين شحنات"><?= $t['type_insurance'] ?></option>
                <option value="تفريغ وتعتيق"><?= $t['type_handling'] ?></option>
                <option value="عمولة مخلص جمركي"><?= $t['type_broker'] ?></option>
                <option value="مصاريف أخرى"><?= $t['type_other'] ?></option>
            </select>
        </td>
        <td><input type="text" name="description[]" class="form-control" placeholder="<?= $t['desc_placeholder'] ?>"></td>
        <td><input type="number" step="0.01" name="amount[]" class="form-control row-amt" style="text-align:center; font-family:monospace; font-weight:bold;" value="0.00" required oninput="calcTotal()"></td>
        <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove(); calcTotal();"><i class="ph-bold ph-trash"></i></button></td>
    </tr>
</template>

<script>
function addRow() {
    const template = document.getElementById('rowTemplate');
    const clone = template.content.cloneNode(true);
    document.getElementById('itemsBody').appendChild(clone);
}
<?php if(!$isEdit || empty($items)): ?> document.addEventListener('DOMContentLoaded', addRow); <?php endif; ?>

function calcTotal() {
    let total = 0;
    document.querySelectorAll('.row-amt').forEach(inp => {
        total += parseFloat(inp.value) || 0;
    });
    document.getElementById('txtGrandTotal').innerText = total.toFixed(2);
}

document.addEventListener('DOMContentLoaded', calcTotal);
</script>