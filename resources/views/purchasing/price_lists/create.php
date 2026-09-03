<?php
// Path: resources/views/purchasing/price_lists/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();
$isEdit = isset($priceList) && $priceList !== null;
$actionUrl = $isEdit ? "/ERP/purchasing/price-lists/{$priceList->id}/update" : "/ERP/purchasing/price-lists/store";

$t = [
    'ar' => [
        'title_new' => 'إضافة قائمة أسعار جديدة',
        'title_edit' => 'تعديل قائمة الأسعار',
        'basic_info' => 'البيانات الأساسية',
        'title_label' => 'عنوان القائمة',
        'title_placeholder' => 'مثال: أسعار التوريد صيف 2026',
        'supplier' => 'المورد',
        'select_supplier' => '-- اختر المورد --',
        'no_suppliers' => 'لا يوجد موردين مسجلين',
        'valid_from' => 'صالح من تاريخ',
        'valid_to' => 'صالح حتى تاريخ',
        'currency_status' => 'العملة والحالة',
        'status_active' => 'نشط',
        'status_draft' => 'مسودة',
        'status_expired' => 'منتهي',
        'notes' => 'ملاحظات وشروط القائمة',
        'notes_placeholder' => 'أي شروط إضافية تخص هذه الأسعار...',
        'items_pricing' => 'أصناف وتسعير القائمة',
        'add_item' => 'إضافة صنف',
        'col_prod' => 'الصنف (Product)',
        'col_price' => 'سعر الوحدة',
        'col_moq' => 'أقل كمية (MOQ)',
        'col_discount' => 'خصم إضافي %',
        'col_remove' => 'إزالة',
        'select_product' => '-- اختر الصنف --',
        'no_products' => 'الرجاء إضافة أصناف في المخازن أولاً',
        'cancel' => 'إلغاء وتراجع',
        'save' => 'اعتماد وحفظ القائمة',
        'update' => 'تحديث القائمة'
    ],
    'en' => [
        'title_new' => 'Add New Price List',
        'title_edit' => 'Edit Price List',
        'basic_info' => 'Basic Information',
        'title_label' => 'List Title',
        'title_placeholder' => 'e.g. Summer 2026 Supply Pricing',
        'supplier' => 'Supplier',
        'select_supplier' => '-- Select Supplier --',
        'no_suppliers' => 'No registered suppliers',
        'valid_from' => 'Valid From',
        'valid_to' => 'Valid To',
        'currency_status' => 'Currency & Status',
        'status_active' => 'Active',
        'status_draft' => 'Draft',
        'status_expired' => 'Expired',
        'notes' => 'Notes & Catalog Terms',
        'notes_placeholder' => 'Any additional terms for this price list...',
        'items_pricing' => 'Catalog Products & Pricing',
        'add_item' => 'Add Item',
        'col_prod' => 'Product',
        'col_price' => 'Unit Price',
        'col_moq' => 'Min Order Qty (MOQ)',
        'col_discount' => 'Extra Discount %',
        'col_remove' => 'Remove',
        'select_product' => '-- Select Product --',
        'no_products' => 'Please add inventory products first',
        'cancel' => 'Cancel',
        'save' => 'Approve & Save List',
        'update' => 'Update Price List'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-primary: #4f46e5;
        --c-primary-dark: #3730a3;
        --c-primary-light: #e0e7ff;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
        --c-border: #cbd5e1;
        --c-bg: #f8fafc;
    }

    .form-wrapper { max-width: 1000px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .back-btn { width: 42px; height: 42px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; color: var(--c-text-muted); text-decoration: none; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: 0.2s; }
    .back-btn:hover { background: var(--c-primary-light); color: var(--c-primary); border-color: #a5b4fc; }
    .form-title { font-size: 1.6rem; font-weight: 800; color: var(--c-text-dark); margin: 0; }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .panel-title { font-size: 1.1rem; color: var(--c-text-dark); font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 10px;}
    .panel-icon { color: var(--c-primary); font-size: 1.3rem; }
    
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: var(--c-bg); transition: 0.2s; color: var(--c-text-dark); }
    .form-control:focus { border-color: var(--c-primary); box-shadow: 0 0 0 4px var(--c-primary-light); background: #ffffff;}
    .input-label { font-size: 0.85rem; font-weight: 800; color: var(--c-text-muted); margin-bottom: 8px; display: block; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }

    .items-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 16px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
    .items-table th { padding: 14px 12px; background: #f8fafc; color: var(--c-text-muted); font-size: 0.8rem; font-weight: 800; text-transform: uppercase; text-align: start; border-bottom: 1px solid #e2e8f0;}
    .items-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; background: #ffffff; }
    
    .btn-remove-row { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; }
    .btn-remove-row:hover { background: #fee2e2; color: #b91c1c; }
    
    .btn-add-row { background: var(--c-primary-light); color: var(--c-primary); border: 1px solid #a5b4fc; padding: 8px 16px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
    .btn-add-row:hover { background: var(--c-primary); color: #ffffff; }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn-submit { background: linear-gradient(135deg, var(--c-primary), var(--c-primary-dark)); color: white; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 800; font-size: 0.95rem; cursor: pointer; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25); transition: 0.2s; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: var(--c-text-muted); padding: 12px 28px; border-radius: 12px; font-weight: 800; font-size: 0.95rem; text-decoration: none; transition: 0.2s; }
    .btn-cancel:hover { background: #f8fafc; color: var(--c-text-dark); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <a href="/ERP/purchasing/price-lists" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <h2 class="form-title"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-info panel-icon"></i> <?= $t['basic_info'] ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $t['title_label'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= $isEdit ? htmlspecialchars($priceList->title) : '' ?>" placeholder="<?= $t['title_placeholder'] ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['supplier'] ?> <span style="color:red">*</span></label>
                    <select name="supplier_id" class="form-control" required>
                        <?php if(empty($suppliers)): ?>
                            <option value=""><?= $t['no_suppliers'] ?></option>
                        <?php else: ?>
                            <option value=""><?= $t['select_supplier'] ?></option>
                            <?php foreach($suppliers as $s): ?>
                                <option value="<?= $s->id ?>" <?= ($isEdit && $priceList->supplier_id == $s->id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s->name_ar) ?> <?= !empty($s->code) ? "({$s->code})" : "" ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="grid-3" style="margin-top: 24px;">
                <div>
                    <label class="input-label"><?= $t['valid_from'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="valid_from" class="form-control" value="<?= $isEdit ? htmlspecialchars($priceList->valid_from) : date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['valid_to'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="valid_to" class="form-control" value="<?= $isEdit ? htmlspecialchars($priceList->valid_to) : date('Y-m-d', strtotime('+1 year')) ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['currency_status'] ?></label>
                    <div style="display: flex; gap:12px;">
                        <input type="text" name="currency" class="form-control" value="<?= $isEdit ? htmlspecialchars($priceList->currency) : $currency ?>" style="width: 40%; text-align: center; font-weight: 800; color: var(--c-primary);">
                        <select name="status" class="form-control" style="width: 60%; font-weight: 700;">
                            <?php $st = $isEdit ? $priceList->status : 'active'; ?>
                            <option value="active" <?= $st=='active'?'selected':'' ?>><?= $t['status_active'] ?></option>
                            <option value="draft" <?= $st=='draft'?'selected':'' ?>><?= $t['status_draft'] ?></option>
                            <option value="expired" <?= $st=='expired'?'selected':'' ?>><?= $t['status_expired'] ?></option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 24px;">
                <label class="input-label"><?= $t['notes'] ?></label>
                <input type="text" name="notes" class="form-control" placeholder="<?= $t['notes_placeholder'] ?>" value="<?= $isEdit ? htmlspecialchars($priceList->notes ?? '') : '' ?>">
            </div>
        </div>

        <div class="panel-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h3 class="panel-title" style="margin:0; border:none; padding:0;"><i class="ph-duotone ph-list-numbers panel-icon"></i> <?= $t['items_pricing'] ?></h3>
                <button type="button" onclick="addRow()" class="btn-add-row"><i class="ph-bold ph-plus"></i> <?= $t['add_item'] ?></button>
            </div>
            
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width: 45%;"><?= $t['col_prod'] ?></th>
                        <th style="width: 18%;"><?= $t['col_price'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_moq'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_discount'] ?></th>
                        <th style="width: 7%; text-align: center;"><?= $t['col_remove'] ?></th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <?php if($isEdit && !empty($items)): foreach($items as $idx => $item): ?>
                        <tr>
                            <td>
                                <select name="product_id[]" class="form-control" required>
                                    <option value=""><?= $t['select_product'] ?></option>
                                    <?php foreach($products as $p): ?>
                                        <option value="<?= $p->id ?>" <?= $item->product_id == $p->id ? 'selected' : '' ?>><?= htmlspecialchars($p->name) ?> (<?= htmlspecialchars($p->code ?? '') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" step="0.01" name="unit_price[]" class="form-control" style="font-family:monospace; font-weight:800; color:var(--c-primary);" value="<?= $item->unit_price ?>" required></td>
                            <td><input type="number" step="0.01" name="min_order_qty[]" class="form-control" style="text-align:center; font-weight:700;" value="<?= $item->min_order_qty ?>"></td>
                            <td><input type="number" step="0.01" name="discount_percent[]" class="form-control" style="text-align:center; font-weight:700;" value="<?= $item->discount_percent ?>"></td>
                            <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove()"><i class="ph-bold ph-trash"></i></button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/purchasing/price-lists" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>

<template id="rowTemplate">
    <tr>
        <td>
            <select name="product_id[]" class="form-control" required>
                <?php if(empty($products)): ?>
                    <option value=""><?= $t['no_products'] ?></option>
                <?php else: ?>
                    <option value=""><?= $t['select_product'] ?></option>
                    <?php foreach($products as $p): ?>
                        <option value="<?= $p->id ?>"><?= htmlspecialchars($p->name) ?> <?= !empty($p->code) ? "({$p->code})" : "" ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </td>
        <td><input type="number" step="0.01" name="unit_price[]" class="form-control" style="font-family:monospace; font-weight:800; color:var(--c-primary);" placeholder="0.00" required></td>
        <td><input type="number" step="0.01" name="min_order_qty[]" class="form-control" style="text-align:center; font-weight:700;" value="1.00"></td>
        <td><input type="number" step="0.01" name="discount_percent[]" class="form-control" style="text-align:center; font-weight:700;" value="0.00"></td>
        <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove()"><i class="ph-bold ph-trash"></i></button></td>
    </tr>
</template>

<script>
function addRow() {
    const template = document.getElementById('rowTemplate');
    const clone = template.content.cloneNode(true);
    document.getElementById('itemsBody').appendChild(clone);
}
<?php if(!$isEdit): ?> document.addEventListener('DOMContentLoaded', addRow); <?php endif; ?>
</script>