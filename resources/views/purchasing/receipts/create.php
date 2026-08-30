<?php
// Path: resources/views/purchasing/receipts/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($receipt) && $receipt !== null;
$actionUrl = $isEdit ? "/ERP/purchasing/receipts/{$receipt->id}/update" : "/ERP/purchasing/receipts/store";
?>

<style>
    :root {
        --c-emerald: #059669;
        --c-emerald-dark: #047857;
        --c-emerald-light: #d1fae5;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
        --c-bg: #f8fafc;
    }

    .form-wrapper { max-width: 1050px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .back-btn { width: 42px; height: 42px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; color: var(--c-text-muted); text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-emerald-light); color: var(--c-emerald); border-color: #a7f3d0; }
    .form-title { font-size: 1.6rem; font-weight: 800; color: var(--c-text-dark); margin: 0; }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 24px; border-top: 4px solid var(--c-emerald); }
    .panel-title { font-size: 1.1rem; color: var(--c-text-dark); font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 10px;}
    
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: var(--c-bg); transition: 0.2s; color: var(--c-text-dark); }
    .form-control:focus { border-color: var(--c-emerald); box-shadow: 0 0 0 4px var(--c-emerald-light); background: #ffffff;}
    .input-label { font-size: 0.85rem; font-weight: 800; color: var(--c-text-muted); margin-bottom: 8px; display: block; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }

    .items-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 16px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
    .items-table th { padding: 14px 10px; background: #f8fafc; color: var(--c-text-muted); font-size: 0.78rem; font-weight: 800; text-transform: uppercase; text-align: start; border-bottom: 1px solid #e2e8f0;}
    .items-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; background: #ffffff; }
    
    .btn-remove-row { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
    .btn-add-row { background: var(--c-emerald-light); color: var(--c-emerald); border: 1px solid #a7f3d0; padding: 8px 16px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn-submit { background: linear-gradient(135deg, var(--c-emerald), var(--c-emerald-dark)); color: white; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: var(--c-text-muted); padding: 12px 28px; border-radius: 12px; font-weight: 800; text-decoration: none; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <a href="/ERP/purchasing/receipts" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <h2 class="form-title"><?= $isEdit ? 'تعديل إذن استلام بضائع' : 'إصدار إذن استلام بضائع جديد (GRN)' ?></h2>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-info" style="color:var(--c-emerald);"></i> البيانات الأساسية للإذن</h3>
            <div class="grid-3">
                <div>
                    <label class="input-label">المورد <span style="color:red">*</span></label>
                    <select name="supplier_id" class="form-control" required>
                        <option value="">-- اختر المورد --</option>
                        <?php foreach($suppliers ?? [] as $s): ?>
                            <option value="<?= $s->id ?>" <?= ($isEdit && $receipt->supplier_id == $s->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s->name_ar) ?> <?= !empty($s->code) ? "({$s->code})" : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="input-label">ربط بأمر شراء (اختياري)</label>
                    <select name="po_id" class="form-control">
                        <option value="">-- مباشر / بدون أمر شراء --</option>
                        <?php foreach($orders ?? [] as $po): ?>
                            <option value="<?= $po->id ?>" <?= ($isEdit && $receipt->po_id == $po->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($po->po_number) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="input-label">رقم إذن الاستلام (GRN Number)</label>
                    <input type="text" name="receipt_number" class="form-control" style="font-family:monospace; color:var(--c-emerald); font-weight:bold;" value="<?= $isEdit ? htmlspecialchars($receipt->receipt_number) : '' ?>" <?= $isEdit ? 'readonly' : 'placeholder="تلقائي"' ?>>
                </div>
            </div>

            <div class="grid-3" style="margin-top: 24px;">
                <div>
                    <label class="input-label">رقم إذن التسليم / البوليصة من المورد</label>
                    <input type="text" name="delivery_note_number" class="form-control" value="<?= $isEdit ? htmlspecialchars($receipt->delivery_note_number ?? '') : '' ?>" placeholder="مثال: DN-88492">
                </div>
                <div>
                    <label class="input-label">تاريخ الاستلام <span style="color:red">*</span></label>
                    <input type="date" name="receipt_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($receipt->receipt_date) : date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="input-label">مستلم البضائع بالمخزن</label>
                    <input type="text" name="received_by" class="form-control" value="<?= $isEdit ? htmlspecialchars($receipt->received_by ?? '') : '' ?>" placeholder="اسم أمين المخزن">
                </div>
            </div>

            <div class="grid-2" style="margin-top: 24px;">
                <div>
                    <label class="input-label">حالة الفحص والاستلام</label>
                    <select name="status" class="form-control" style="font-weight: 800;">
                        <?php $st = $isEdit ? $receipt->status : 'accepted'; ?>
                        <option value="inspected" <?= $st=='inspected' ? 'selected' : '' ?>>قيد الفحص</option>
                        <option value="accepted" <?= $st=='accepted' ? 'selected' : '' ?>>مقبول ومستلم بالمخزن</option>
                        <option value="rejected" <?= $st=='rejected' ? 'selected' : '' ?>>مرفوض بالكامل</option>
                        <option value="draft" <?= $st=='draft' ? 'selected' : '' ?>>مسودة</option>
                    </select>
                </div>
                <div>
                    <label class="input-label">ملاحظات الفحص والاستلام</label>
                    <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($receipt->notes ?? '') : '' ?>" placeholder="أي تلفيات أو ملاحظات على حالة الشحنة...">
                </div>
            </div>
        </div>

        <div class="panel-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h3 class="panel-title" style="margin:0; border:none; padding:0;"><i class="ph-duotone ph-list-numbers" style="color:var(--c-emerald);"></i> أصناف الشحنة المستلمة</h3>
                <button type="button" onclick="addRow()" class="btn-add-row"><i class="ph-bold ph-plus"></i> إضافة صنف</button>
            </div>
            
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width: 25%;">الصنف (الكود والاسم)</th>
                        <th style="width: 25%;">الوصف <span style="color:red">*</span></th>
                        <th style="width: 11%; text-align: center;">الكمية المستلمة</th>
                        <th style="width: 11%; text-align: center;">الكمية المقبولة</th>
                        <th style="width: 11%; text-align: center;">الكمية المرفوضة</th>
                        <th style="width: 12%; text-align: center;">سعر الوحدة</th>
                        <th style="width: 5%; text-align: center;">إزالة</th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <?php if($isEdit && !empty($items)): foreach($items as $item): ?>
                        <tr>
                            <td>
                                <select name="product_id[]" class="form-control">
                                    <option value="">-- غير مسجل --</option>
                                    <?php foreach($products ?? [] as $p): ?>
                                        <option value="<?= $p->id ?>" <?= $item->product_id == $p->id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p->code) ?><?= !empty($p->name) ? ' - ' . htmlspecialchars($p->name) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="description[]" class="form-control" value="<?= htmlspecialchars($item->description) ?>" required></td>
                            <td><input type="number" step="0.01" name="quantity_received[]" class="form-control" style="text-align:center; font-weight:700;" value="<?= $item->quantity_received ?>" required></td>
                            <td><input type="number" step="0.01" name="quantity_accepted[]" class="form-control" style="text-align:center; font-weight:700; color:#059669;" value="<?= $item->quantity_accepted ?>" required></td>
                            <td><input type="number" step="0.01" name="quantity_rejected[]" class="form-control" style="text-align:center; font-weight:700; color:#dc2626;" value="<?= $item->quantity_rejected ?>"></td>
                            <td><input type="number" step="0.01" name="unit_price[]" class="form-control" style="text-align:center; font-family:monospace;" value="<?= $item->unit_price ?>"></td>
                            <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove()"><i class="ph-bold ph-trash"></i></button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/purchasing/receipts" class="btn-cancel">إلغاء وتراجع</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث الإذن' : 'حفظ وإصدار إذن الاستلام' ?></button>
        </div>
    </form>
</div>

<template id="rowTemplate">
    <tr>
        <td>
            <select name="product_id[]" class="form-control">
                <option value="">-- غير مسجل --</option>
                <?php foreach($products ?? [] as $p): ?>
                    <option value="<?= $p->id ?>">
                        <?= htmlspecialchars($p->code) ?><?= !empty($p->name) ? ' - ' . htmlspecialchars($p->name) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="text" name="description[]" class="form-control" placeholder="الوصف..." required></td>
        <td><input type="number" step="0.01" name="quantity_received[]" class="form-control" style="text-align:center; font-weight:700;" value="1.00" required></td>
        <td><input type="number" step="0.01" name="quantity_accepted[]" class="form-control" style="text-align:center; font-weight:700; color:#059669;" value="1.00" required></td>
        <td><input type="number" step="0.01" name="quantity_rejected[]" class="form-control" style="text-align:center; font-weight:700; color:#dc2626;" value="0.00"></td>
        <td><input type="number" step="0.01" name="unit_price[]" class="form-control" style="text-align:center; font-family:monospace;" placeholder="0.00"></td>
        <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove()"><i class="ph-bold ph-trash"></i></button></td>
    </tr>
</template>

<script>
function addRow() {
    const template = document.getElementById('rowTemplate');
    const clone = template.content.cloneNode(true);
    document.getElementById('itemsBody').appendChild(clone);
}
<?php if(!$isEdit || empty($items)): ?> document.addEventListener('DOMContentLoaded', addRow); <?php endif; ?>
</script>