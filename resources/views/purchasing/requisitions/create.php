<?php
// Path: resources/views/purchasing/requisitions/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();

$isEdit = isset($pr) && $pr !== null;
$actionUrl = $isEdit ? "/ERP/purchasing/requisitions/{$pr->id}/update" : "/ERP/purchasing/requisitions/store";

$t = [
    'ar' => [
        'title_new' => 'إنشاء طلب شراء جديد (PR)',
        'title_edit' => 'تعديل طلب الشراء',
        'basic_info' => 'بيانات الطلب الأساسية',
        'dept' => 'الإدارة الطالبة',
        'dept_placeholder' => 'مثال: إدارة تقنية المعلومات',
        'requested_by' => 'اسم مقدم الطلب',
        'requested_by_placeholder' => 'اسم الموظف المسؤول',
        'request_date' => 'تاريخ الطلب',
        'required_date' => 'تاريخ الاحتياج (الاستحقاق)',
        'status' => 'حالة الطلب',
        'status_draft' => 'مسودة',
        'status_pending' => 'قيد الاعتماد',
        'status_approved' => 'معتمد',
        'status_rejected' => 'مرفوض',
        'status_completed' => 'تم الشراء',
        'pr_number' => 'رقم الطلب (PR Number)',
        'pr_placeholder' => 'يولد تلقائياً',
        'notes' => 'مبرر الشراء / ملاحظات',
        'notes_placeholder' => 'اكتب سبب الاحتياج لهذه الأصناف...',
        'items_title' => 'الأصناف المطلوبة',
        'add_item' => 'إضافة صنف',
        'col_code' => 'كود الصنف (اختياري)',
        'col_desc' => 'الوصف / المواصفات الفنية',
        'col_qty' => 'الكمية',
        'col_price' => 'السعر التقديري للوحدة',
        'col_actions' => 'إزالة',
        'unregistered' => '-- غير مسجل / إدخال يدوي --',
        'cancel' => 'إلغاء وتراجع',
        'save' => 'إرسال الطلب للاعتماد',
        'update' => 'تحديث الطلب'
    ],
    'en' => [
        'title_new' => 'Create Purchase Requisition (PR)',
        'title_edit' => 'Edit Purchase Requisition',
        'basic_info' => 'Basic Requisition Details',
        'dept' => 'Requesting Department',
        'dept_placeholder' => 'e.g. IT Department',
        'requested_by' => 'Requester Name',
        'requested_by_placeholder' => 'Employee Name',
        'request_date' => 'Request Date',
        'required_date' => 'Required Date',
        'status' => 'Status',
        'status_draft' => 'Draft',
        'status_pending' => 'Pending Approval',
        'status_approved' => 'Approved',
        'status_rejected' => 'Rejected',
        'status_completed' => 'Completed',
        'pr_number' => 'PR Number',
        'pr_placeholder' => 'Auto generated',
        'notes' => 'Justification / Notes',
        'notes_placeholder' => 'State the business reason for this purchase...',
        'items_title' => 'Requested Items',
        'add_item' => 'Add Item',
        'col_code' => 'Product Code (Optional)',
        'col_desc' => 'Technical Specs & Description',
        'col_qty' => 'Quantity',
        'col_price' => 'Estimated Unit Price',
        'col_actions' => 'Remove',
        'unregistered' => '-- Manual / Custom Item --',
        'cancel' => 'Cancel',
        'save' => 'Submit for Approval',
        'update' => 'Update Requisition'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-teal: #0d9488;
        --c-teal-dark: #0f766e;
        --c-teal-light: #ccfbf1;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
        --c-bg: #f8fafc;
    }

    .form-wrapper { max-width: 1000px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .back-btn { width: 42px; height: 42px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; color: var(--c-text-muted); text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-teal-light); color: var(--c-teal); border-color: #99f6e4; }
    .form-title { font-size: 1.6rem; font-weight: 800; color: var(--c-text-dark); margin: 0; }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 24px; border-top: 4px solid var(--c-teal); }
    .panel-title { font-size: 1.1rem; color: var(--c-text-dark); font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 10px;}
    
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: var(--c-bg); transition: 0.2s; color: var(--c-text-dark); }
    .form-control:focus { border-color: var(--c-teal); box-shadow: 0 0 0 4px var(--c-teal-light); background: #ffffff;}
    .input-label { font-size: 0.85rem; font-weight: 800; color: var(--c-text-muted); margin-bottom: 8px; display: block; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }

    .items-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 16px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
    .items-table th { padding: 14px 12px; background: #f8fafc; color: var(--c-text-muted); font-size: 0.8rem; font-weight: 800; text-transform: uppercase; text-align: start; border-bottom: 1px solid #e2e8f0;}
    .items-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; background: #ffffff; }
    
    .btn-remove-row { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; }
    .btn-remove-row:hover { background: #fee2e2; }
    .btn-add-row { background: var(--c-teal-light); color: var(--c-teal); border: 1px solid #99f6e4; padding: 8px 16px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn-submit { background: linear-gradient(135deg, var(--c-teal), var(--c-teal-dark)); color: white; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: var(--c-text-muted); padding: 12px 28px; border-radius: 12px; font-weight: 800; text-decoration: none; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <a href="/ERP/purchasing/requisitions" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <h2 class="form-title"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-info" style="color:var(--c-teal);"></i> <?= $t['basic_info'] ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $t['dept'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="department" class="form-control" value="<?= $isEdit ? htmlspecialchars($pr->department) : '' ?>" placeholder="<?= $t['dept_placeholder'] ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['requested_by'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="requested_by" class="form-control" value="<?= $isEdit ? htmlspecialchars($pr->requested_by) : '' ?>" placeholder="<?= $t['requested_by_placeholder'] ?>" required>
                </div>
            </div>
            <div class="grid-3" style="margin-top: 24px;">
                <div>
                    <label class="input-label"><?= $t['request_date'] ?></label>
                    <input type="date" name="request_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($pr->request_date) : date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['required_date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="required_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($pr->required_date) : '' ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['status'] ?></label>
                    <select name="status" class="form-control" style="font-weight: 800;">
                        <?php $st = $isEdit ? $pr->status : 'pending'; ?>
                        <option value="draft" <?= $st=='draft' ? 'selected' : '' ?>><?= $t['status_draft'] ?></option>
                        <option value="pending" <?= $st=='pending' ? 'selected' : '' ?>><?= $t['status_pending'] ?></option>
                        <option value="approved" <?= $st=='approved' ? 'selected' : '' ?>><?= $t['status_approved'] ?></option>
                        <option value="rejected" <?= $st=='rejected' ? 'selected' : '' ?>><?= $t['status_rejected'] ?></option>
                        <option value="completed" <?= $st=='completed' ? 'selected' : '' ?>><?= $t['status_completed'] ?></option>
                    </select>
                </div>
            </div>
            
            <div class="grid-2" style="margin-top: 24px;">
                <div>
                    <label class="input-label"><?= $t['pr_number'] ?></label>
                    <input type="text" name="pr_number" class="form-control" style="font-family:monospace; color:var(--c-teal); font-weight:bold;" value="<?= $isEdit ? htmlspecialchars($pr->pr_number) : '' ?>" <?= $isEdit ? 'readonly' : 'placeholder="'.$t['pr_placeholder'].'"' ?>>
                </div>
                <div>
                    <label class="input-label"><?= $t['notes'] ?></label>
                    <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($pr->notes ?? '') : '' ?>" placeholder="<?= $t['notes_placeholder'] ?>">
                </div>
            </div>
        </div>

        <div class="panel-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h3 class="panel-title" style="margin:0; border:none; padding:0;"><i class="ph-duotone ph-list-numbers" style="color:var(--c-teal);"></i> <?= $t['items_title'] ?></h3>
                <button type="button" onclick="addRow()" class="btn-add-row"><i class="ph-bold ph-plus"></i> <?= $t['add_item'] ?></button>
            </div>
            
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width: 25%;"><?= $t['col_code'] ?></th>
                        <th style="width: 35%;"><?= $t['col_desc'] ?> <span style="color:red">*</span></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_qty'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_price'] ?> (<?= $currency ?>)</th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <?php if($isEdit && !empty($items)): foreach($items as $item): ?>
                        <tr>
                            <td>
                                <select name="product_id[]" class="form-control">
                                    <option value=""><?= $t['unregistered'] ?></option>
                                    <?php foreach($products ?? [] as $p): ?>
                                        <option value="<?= $p->id ?>" <?= $item->product_id == $p->id ? 'selected' : '' ?>><?= htmlspecialchars($p->code) ?> - <?= htmlspecialchars($p->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="description[]" class="form-control" value="<?= htmlspecialchars($item->description) ?>" required></td>
                            <td><input type="number" step="0.01" name="quantity[]" class="form-control" style="text-align:center; font-weight:700;" value="<?= $item->quantity ?>" required></td>
                            <td><input type="number" step="0.01" name="estimated_price[]" class="form-control" style="text-align:center; font-family:monospace;" value="<?= $item->estimated_price ?>"></td>
                            <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove()"><i class="ph-bold ph-trash"></i></button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/purchasing/requisitions" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-paper-plane-tilt"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>

<template id="rowTemplate">
    <tr>
        <td>
            <select name="product_id[]" class="form-control">
                <option value=""><?= $t['unregistered'] ?></option>
                <?php foreach($products ?? [] as $p): ?>
                    <option value="<?= $p->id ?>"><?= htmlspecialchars($p->code) ?> - <?= htmlspecialchars($p->name) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="text" name="description[]" class="form-control" required></td>
        <td><input type="number" step="0.01" name="quantity[]" class="form-control" style="text-align:center; font-weight:700;" value="1.00" required></td>
        <td><input type="number" step="0.01" name="estimated_price[]" class="form-control" style="text-align:center; font-family:monospace;" placeholder="0.00"></td>
        <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove()"><i class="ph-bold ph-trash"></i></button></td>
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