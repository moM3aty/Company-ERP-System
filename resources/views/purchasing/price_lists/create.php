<?php
// Path: resources/views/purchasing/price_lists/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($priceList) && $priceList !== null;
$actionUrl = $isEdit ? "/ERP/purchasing/price-lists/{$priceList->id}/update" : "/ERP/purchasing/price-lists/store";
?>

<style>
    :root {
        --c-primary: #4f46e5;    /* Indigo 600 */
        --c-primary-dark: #3730a3;
        --c-primary-light: #e0e7ff;
        --c-primary-soft: #ede9fe;
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

    /* Items Table with new Indigo Theme */
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
        <h2 class="form-title"><?= $isEdit ? 'تعديل قائمة الأسعار' : 'إضافة قائمة أسعار جديدة' ?></h2>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-info panel-icon"></i> البيانات الأساسية</h3>
            <div class="grid-2">
                <div>
                    <label class="input-label">عنوان القائمة <span style="color:red">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= $isEdit ? htmlspecialchars($priceList->title) : '' ?>" placeholder="مثال: أسعار التوريد صيف 2026" required>
                </div>
                <div>
                    <label class="input-label">المورد <span style="color:red">*</span></label>
                    <select name="supplier_id" class="form-control" required>
                        <?php if(empty($suppliers)): ?>
                            <option value="">لا يوجد موردين مسجلين</option>
                        <?php else: ?>
                            <option value="">-- اختر المورد --</option>
                            <?php foreach($suppliers as $s): ?>
                                <option value="<?= $s->id ?>" <?= ($isEdit && $priceList->supplier_id == $s->id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s->name_ar ?? $s->name_en) ?> <?= !empty($s->code) ? "({$s->code})" : "" ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="grid-3" style="margin-top: 24px;">
                <div>
                    <label class="input-label">صالح من تاريخ <span style="color:red">*</span></label>
                    <input type="date" name="valid_from" class="form-control" value="<?= $isEdit ? htmlspecialchars($priceList->valid_from) : date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="input-label">صالح حتى تاريخ <span style="color:red">*</span></label>
                    <input type="date" name="valid_to" class="form-control" value="<?= $isEdit ? htmlspecialchars($priceList->valid_to) : date('Y-m-d', strtotime('+1 year')) ?>" required>
                </div>
                <div>
                    <label class="input-label">العملة والحالة</label>
                    <div style="display: flex; gap:12px;">
                        <input type="text" name="currency" class="form-control" value="<?= $isEdit ? htmlspecialchars($priceList->currency) : 'EGP' ?>" style="width: 40%; text-align: center; font-weight: 800; color: var(--c-primary);">
                        <select name="status" class="form-control" style="width: 60%; font-weight: 700;">
                            <?php $st = $isEdit ? $priceList->status : 'active'; ?>
                            <option value="active" <?= $st=='active'?'selected':'' ?>>نشط</option>
                            <option value="draft" <?= $st=='draft'?'selected':'' ?>>مسودة</option>
                            <option value="expired" <?= $st=='expired'?'selected':'' ?>>منتهي</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 24px;">
                <label class="input-label">ملاحظات وشروط القائمة</label>
                <input type="text" name="notes" class="form-control" placeholder="أي شروط إضافية تخص هذه الأسعار..." value="<?= $isEdit ? htmlspecialchars($priceList->notes ?? '') : '' ?>">
            </div>
        </div>

        <div class="panel-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h3 class="panel-title" style="margin:0; border:none; padding:0;"><i class="ph-duotone ph-list-numbers panel-icon"></i> أصناف وتسعير القائمة</h3>
                <button type="button" onclick="addRow()" class="btn-add-row"><i class="ph-bold ph-plus"></i> إضافة صنف</button>
            </div>
            
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width: 45%;">الصنف (Product)</th>
                        <th style="width: 18%;">سعر الوحدة</th>
                        <th style="width: 15%; text-align: center;">أقل كمية (MOQ)</th>
                        <th style="width: 15%; text-align: center;">خصم إضافي %</th>
                        <th style="width: 7%; text-align: center;">إزالة</th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <?php if($isEdit && !empty($items)): foreach($items as $idx => $item): ?>
                        <tr>
                            <td>
                                <select name="product_id[]" class="form-control" required>
                                    <option value="">-- اختر الصنف --</option>
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
                    <?php endforeach; else: ?>
                        <!-- Empty row handled by JS -->
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/purchasing/price-lists" class="btn-cancel">إلغاء وتراجع</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث القائمة' : 'اعتماد وحفظ القائمة' ?></button>
        </div>
    </form>
</div>

<!-- JS Template for New Rows -->
<template id="rowTemplate">
    <tr>
        <td>
            <select name="product_id[]" class="form-control" required>
                <?php if(empty($products)): ?>
                    <option value="">الرجاء إضافة أصناف في المخازن أولاً</option>
                <?php else: ?>
                    <option value="">-- اختر الصنف --</option>
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

// Add an initial row if creating new
<?php if(!$isEdit): ?> document.addEventListener('DOMContentLoaded', addRow); <?php endif; ?>
</script>