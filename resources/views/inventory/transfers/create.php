<?php
// Path: resources/views/inventory/transfers/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($transfer) && $transfer !== null && !empty($transfer->id);
$actionUrl = $isEdit ? "/ERP/inventory/stock/transfers/{$transfer->id}/update" : "/ERP/inventory/stock/transfers/store";
?>

<style>
    :root { --c-amber: #f59e0b; --c-amber-dark: #d97706; --c-border: #cbd5e1; --c-bg: #f8fafc; }
    .form-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .back-btn { width: 42px; height: 42px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #64748b; }
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; margin-bottom: 24px; border-top: 4px solid var(--c-amber); }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: var(--c-bg); color: #0f172a; }
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
        <a href="/ERP/inventory/stock/transfers" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
        <h2 style="margin:0; font-size:1.6rem; color:#0f172a; font-weight:800;"><?= $isEdit ? 'تعديل أمر التحويل' : 'إصدار أمر تحويل جديد' ?></h2>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST" id="transferForm">
        <div class="panel-card">
            <h3 style="margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; font-size:1.1rem; font-weight:800; color:#0f172a;"><i class="ph-duotone ph-map-pin" style="color:var(--c-amber);"></i> مسار التحويل</h3>
            <div class="grid-2">
                <div>
                    <label class="input-label">من مستودع (المصدر) <span style="color:red">*</span></label>
                    <select name="from_warehouse_id" id="from_wh" class="form-control" required>
                        <option value="">-- اختر مستودع الصرف --</option>
                        <?php foreach($warehouses ?? [] as $w): ?>
                            <option value="<?= $w->id ?>" <?= ($isEdit && $transfer->from_warehouse_id == $w->id) ? 'selected' : '' ?>><?= htmlspecialchars($w->name_ar) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="input-label">إلى مستودع (الوجهة) <span style="color:red">*</span></label>
                    <select name="to_warehouse_id" id="to_wh" class="form-control" required>
                        <option value="">-- اختر مستودع الاستلام --</option>
                        <?php foreach($warehouses ?? [] as $w): ?>
                            <option value="<?= $w->id ?>" <?= ($isEdit && $transfer->to_warehouse_id == $w->id) ? 'selected' : '' ?>><?= htmlspecialchars($w->name_ar) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-3" style="margin-top: 24px;">
                <div>
                    <label class="input-label">رقم أمر التحويل</label>
                    <input type="text" name="transfer_number" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-amber-dark);" value="<?= $isEdit ? htmlspecialchars($transfer->transfer_number) : '' ?>" readonly placeholder="تلقائي">
                </div>
                <div>
                    <label class="input-label">تاريخ التحويل <span style="color:red">*</span></label>
                    <input type="date" name="transfer_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($transfer->transfer_date) : date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="input-label">حالة التحويل</label>
                    <select name="status" class="form-control" style="font-weight: 800;">
                        <?php $st = $isEdit ? $transfer->status : 'draft'; ?>
                        <option value="draft" <?= $st=='draft' ? 'selected' : '' ?>>مسودة (لم يتم النقل)</option>
                        <option value="in_transit" <?= $st=='in_transit' ? 'selected' : '' ?>>قيد النقل (في الطريق)</option>
                        <option value="completed" <?= $st=='completed' ? 'selected' : '' ?>>مستلم ومكتمل (نهائي)</option>
                    </select>
                </div>
            </div>
            
            <div style="margin-top: 24px;">
                <label class="input-label">ملاحظات التحويل</label>
                <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($transfer->notes ?? '') : '' ?>" placeholder="بوليصة الشحن، اسم السائق...">
            </div>
        </div>

        <div class="panel-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h3 style="margin:0; font-size:1.1rem; font-weight:800; color:#0f172a;"><i class="ph-duotone ph-list-numbers" style="color:var(--c-amber);"></i> الأصناف المحولة</h3>
                <button type="button" onclick="addRow()" class="btn-add-row"><i class="ph-bold ph-plus"></i> إضافة صنف</button>
            </div>
            
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width: 70%;">الصنف (الكود والاسم) <span style="color:red">*</span></th>
                        <th style="width: 20%; text-align: center;">الكمية <span style="color:red">*</span></th>
                        <th style="width: 10%; text-align: center;">إزالة</th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <?php if($isEdit && !empty($items)): foreach($items as $item): ?>
                        <tr>
                            <td>
                                <select name="product_id[]" class="form-control" required>
                                    <option value="">-- اختر الصنف --</option>
                                    <?php foreach($products ?? [] as $p): ?>
                                        <option value="<?= $p->id ?>" <?= $item->product_id == $p->id ? 'selected' : '' ?>><?= htmlspecialchars($p->code) ?> - <?= htmlspecialchars($p->name_ar) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" step="0.01" name="quantity[]" class="form-control" style="text-align:center; font-weight:900; font-family:monospace; font-size:1.1rem; color:var(--c-amber-dark);" value="<?= $item->quantity ?>" required></td>
                            <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove();"><i class="ph-bold ph-trash"></i></button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/inventory/stock/transfers" class="btn-cancel">إلغاء وتراجع</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث البيانات' : 'حفظ وإصدار التحويل' ?></button>
        </div>
    </form>
</div>

<template id="rowTemplate">
    <tr>
        <td>
            <select name="product_id[]" class="form-control" required>
                <option value="">-- اختر الصنف --</option>
                <?php foreach($products ?? [] as $p): ?>
                    <option value="<?= $p->id ?>"><?= htmlspecialchars($p->code) ?> - <?= htmlspecialchars($p->name_ar) ?></option>
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

document.getElementById('transferForm').addEventListener('submit', function(e) {
    const fromWh = document.getElementById('from_wh').value;
    const toWh = document.getElementById('to_wh').value;
    if(fromWh && toWh && fromWh === toWh) {
        e.preventDefault(); 
        alert("خطأ: لا يمكن اختيار نفس المستودع للمصدر والوجهة!");
    }
});
</script>