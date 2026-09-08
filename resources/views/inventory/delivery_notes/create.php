<?php
// Path: resources/views/inventory/delivery_notes/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($note) && $note !== null && !empty($note->id);
$actionUrl = $isEdit ? "/ERP/inventory/delivery-notes/" . (int)$note->id . "/update" : "/ERP/inventory/delivery-notes/store";

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title_new' => 'إصدار إذن تسليم بضاعة جديد', 'title_edit' => 'تعديل إذن التسليم',
        'panel_basic' => 'البيانات الأساسية للشحنة', 'customer' => 'العميل / الجهة المستلمة',
        'cust_ph' => 'اسم الشركة أو العميل...', 'warehouse' => 'المستودع المصدر',
        'choose_wh' => '-- اختر مستودع الصرف --', 'num' => 'رقم إذن التسليم',
        'date' => 'تاريخ التسليم', 'status' => 'حالة الإذن', 'status_draft' => 'مسودة (تجهيز)',
        'status_delivered' => 'تم التسليم للعميل (نهائي)', 'notes' => 'ملاحظات والتفاصيل',
        'notes_ph' => 'ملاحظات الشحن، العنوان، السائق...', 'panel_items' => 'الأصناف المسلمة',
        'add_item' => 'إضافة صنف', 'col_item' => 'الصنف (الكود والاسم)', 'choose_item' => '-- اختر الصنف --',
        'col_qty' => 'الكمية المسلمة', 'col_remove' => 'إزالة', 'cancel' => 'إلغاء وتراجع',
        'save' => 'حفظ وإصدار الإذن', 'update' => 'تحديث البيانات', 'active_scope' => 'الفرع النشط:'
    ],
    'en' => [
        'title_new' => 'Issue New Delivery Note', 'title_edit' => 'Edit Delivery Note',
        'panel_basic' => 'Shipment Basic Info', 'customer' => 'Customer / Recipient',
        'cust_ph' => 'Customer or company name...', 'warehouse' => 'Source Warehouse',
        'choose_wh' => '-- Select Source Warehouse --', 'num' => 'Delivery Note No.',
        'date' => 'Delivery Date', 'status' => 'Status', 'status_draft' => 'Draft (Preparing)',
        'status_delivered' => 'Delivered (Final)', 'notes' => 'Notes & Details',
        'notes_ph' => 'Shipping notes, address, driver...', 'panel_items' => 'Delivered Items',
        'add_item' => 'Add Item', 'col_item' => 'Item (Code & Name)', 'choose_item' => '-- Select Item --',
        'col_qty' => 'Delivered Qty', 'col_remove' => 'Remove', 'cancel' => 'Cancel',
        'save' => 'Save & Issue Note', 'update' => 'Update Data', 'active_scope' => 'Active Branch:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { --c-amber: #f59e0b; --c-amber-dark: #d97706; --c-border: #cbd5e1; --c-bg: #f8fafc; }
    .form-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .back-btn { width: 42px; height: 42px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #64748b; }
    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: #0f172a; margin-bottom: 20px; }
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; margin-bottom: 24px; border-top: 4px solid var(--c-amber); }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: var(--c-bg); color: #0f172a; box-sizing: border-box; }
    .input-label { font-size: 0.85rem; font-weight: 800; margin-bottom: 8px; display: block; color: #475569; }
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
    .items-table { width: 100%; border-collapse: collapse; margin-top: 16px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
    .items-table th { padding: 14px; background: #f8fafc; font-size: 0.8rem; text-align: start; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 800; }
    .items-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; background: #fff; }
    .btn-remove-row { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; width: 36px; height: 36px; cursor: pointer; }
    .btn-add-row { background: #fef3c7; color: #d97706; border: 1px solid #fcd34d; padding: 8px 16px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; border-top: 1px solid #e2e8f0; z-index: 100;}
    .btn-submit { background: linear-gradient(135deg, var(--c-amber), var(--c-amber-dark)); color: white; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 800; cursor: pointer; }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); padding: 12px 28px; border-radius: 12px; font-weight: 800; text-decoration: none; color: #475569; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <a href="/ERP/inventory/delivery-notes" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <h2 style="margin:0; font-size:1.6rem; color:#0f172a; font-weight:800;"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-amber);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-amber); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST" id="deliveryForm">
        <div class="panel-card">
            <h3 style="margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; font-size:1.1rem; font-weight:800; color:#0f172a;"><i class="ph-duotone ph-truck" style="color:var(--c-amber);"></i> <?= $t['panel_basic'] ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $t['customer'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="customer_name" class="form-control" value="<?= htmlspecialchars((string)($note->customer_name ?? '')) ?>" placeholder="<?= $t['cust_ph'] ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['warehouse'] ?> <span style="color:red">*</span></label>
                    <select name="warehouse_id" class="form-control" required>
                        <option value=""><?= $t['choose_wh'] ?></option>
                        <?php foreach($warehouses ?? [] as $w): $wName = $isRtl ? ($w->name_ar ?? '') : ($w->name_en ?: ($w->name_ar ?? '')); ?>
                            <option value="<?= $w->id ?>" <?= ($isEdit && ($note->warehouse_id ?? 0) == $w->id) ? 'selected' : '' ?>><?= htmlspecialchars($wName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-3" style="margin-top: 24px;">
                <div>
                    <label class="input-label"><?= $t['num'] ?></label>
                    <input type="text" name="delivery_number" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-amber-dark);" value="<?= $isEdit ? htmlspecialchars((string)($note->delivery_number ?? '')) : '' ?>" readonly placeholder="Auto">
                </div>
                <div>
                    <label class="input-label"><?= $t['date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="delivery_date" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($note->delivery_date ?? '')) : date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['status'] ?></label>
                    <select name="status" class="form-control" style="font-weight: 800;">
                        <?php $st = $isEdit ? ($note->status ?? 'draft') : 'draft'; ?>
                        <option value="draft" <?= $st=='draft' ? 'selected' : '' ?>><?= $t['status_draft'] ?></option>
                        <option value="delivered" <?= $st=='delivered' ? 'selected' : '' ?>><?= $t['status_delivered'] ?></option>
                    </select>
                </div>
            </div>
            
            <div style="margin-top: 24px;">
                <label class="input-label"><?= $t['notes'] ?></label>
                <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($note->notes ?? '')) : '' ?>" placeholder="<?= $t['notes_ph'] ?>">
            </div>
        </div>

        <div class="panel-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h3 style="margin:0; font-size:1.1rem; font-weight:800; color:#0f172a;"><i class="ph-duotone ph-list-checks" style="color:var(--c-amber);"></i> <?= $t['panel_items'] ?></h3>
                <button type="button" onclick="addRow()" class="btn-add-row"><i class="ph-bold ph-plus"></i> <?= $t['add_item'] ?></button>
            </div>
            
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width: 70%;"><?= $t['col_item'] ?> <span style="color:red">*</span></th>
                        <th style="width: 20%; text-align: center;"><?= $t['col_qty'] ?> <span style="color:red">*</span></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_remove'] ?></th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <?php if($isEdit && !empty($items)): foreach($items as $item): ?>
                        <tr>
                            <td>
                                <select name="product_id[]" class="form-control" required>
                                    <option value=""><?= $t['choose_item'] ?></option>
                                    <?php foreach($products ?? [] as $p): $pName = $isRtl ? ($p->name_ar ?? '') : ($p->name_en ?: ($p->name_ar ?? '')); ?>
                                        <option value="<?= $p->id ?>" <?= ($item->product_id ?? 0) == $p->id ? 'selected' : '' ?>><?= htmlspecialchars($p->code) ?> - <?= htmlspecialchars($pName) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" step="0.01" name="quantity[]" class="form-control" style="text-align:center; font-weight:900; font-family:monospace; font-size:1.1rem; color:var(--c-amber-dark);" value="<?= (float)($item->quantity ?? 1) ?>" required></td>
                            <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove();"><i class="ph-bold ph-trash"></i></button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/inventory/delivery-notes" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>

<template id="rowTemplate">
    <tr>
        <td>
            <select name="product_id[]" class="form-control" required>
                <option value=""><?= $t['choose_item'] ?></option>
                <?php foreach($products ?? [] as $p): $pName = $isRtl ? ($p->name_ar ?? '') : ($p->name_en ?: ($p->name_ar ?? '')); ?>
                    <option value="<?= $p->id ?>"><?= htmlspecialchars($p->code) ?> - <?= htmlspecialchars($pName) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" step="0.01" name="quantity[]" class="form-control" style="text-align:center; font-weight:900; font-family:monospace; font-size:1.1rem; color:var(--c-amber-dark);" value="1.00" required></td>
        <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove();"><i class="ph-bold ph-trash"></i></button></td>
    </tr>
</template>

<script>
function addRow() {
    const template = document.getElementById('rowTemplate');
    const clone = template.content.cloneNode(true);
    document.getElementById('itemsBody').appendChild(clone);
}

<?php if(!$isEdit || empty($items)): ?> 
document.addEventListener('DOMContentLoaded', addRow); 
<?php endif; ?>
</script>