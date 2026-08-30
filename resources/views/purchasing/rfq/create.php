<?php
// Path: resources/views/purchasing/rfq/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($rfq) && $rfq !== null;
$actionUrl = $isEdit ? "/ERP/purchasing/rfq/{$rfq->id}/update" : "/ERP/purchasing/rfq/store";
?>

<style>
    :root {
        --c-orange: #ea580c;
        --c-orange-dark: #c2410c;
        --c-orange-light: #ffedd5;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
        --c-bg: #f8fafc;
    }

    .form-wrapper { max-width: 1000px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .back-btn { width: 42px; height: 42px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; color: var(--c-text-muted); text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-orange-light); color: var(--c-orange); border-color: #fdba74; }
    .form-title { font-size: 1.6rem; font-weight: 800; color: var(--c-text-dark); margin: 0; }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 24px; border-top: 4px solid var(--c-orange); }
    .panel-title { font-size: 1.1rem; color: var(--c-text-dark); font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 10px;}
    
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: var(--c-bg); transition: 0.2s; color: var(--c-text-dark); }
    .form-control:focus { border-color: var(--c-orange); box-shadow: 0 0 0 4px var(--c-orange-light); background: #ffffff;}
    .input-label { font-size: 0.85rem; font-weight: 800; color: var(--c-text-muted); margin-bottom: 8px; display: block; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }

    /* Suppliers Checkboxes */
    .sup-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; max-height: 200px; overflow-y: auto; }
    .sup-item { display: flex; align-items: center; gap: 10px; background: #fff; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 700; color: #334155; font-size: 0.85rem; cursor: pointer; transition: 0.2s;}
    .sup-item:hover { border-color: var(--c-orange); background: var(--c-orange-light); }
    .sup-item input { width: 16px; height: 16px; accent-color: var(--c-orange); cursor: pointer; }

    /* Items Table */
    .items-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 16px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
    .items-table th { padding: 14px 12px; background: #f8fafc; color: var(--c-text-muted); font-size: 0.8rem; font-weight: 800; text-transform: uppercase; text-align: start; border-bottom: 1px solid #e2e8f0;}
    .items-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; background: #ffffff; }
    
    .btn-remove-row { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
    .btn-add-row { background: var(--c-orange-light); color: var(--c-orange); border: 1px solid #fdba74; padding: 8px 16px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn-submit { background: linear-gradient(135deg, var(--c-orange), var(--c-orange-dark)); color: white; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: var(--c-text-muted); padding: 12px 28px; border-radius: 12px; font-weight: 800; text-decoration: none; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <a href="/ERP/purchasing/rfq" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <h2 class="form-title"><?= $isEdit ? 'تعديل طلب التسعير' : 'إنشاء طلب تسعير (RFQ)' ?></h2>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-info" style="color:var(--c-orange);"></i> البيانات الأساسية</h3>
            <div class="grid-2">
                <div style="grid-column: span 2;">
                    <label class="input-label">موضوع الطلب / العنوان <span style="color:red">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= $isEdit ? htmlspecialchars($rfq->title) : '' ?>" placeholder="مثال: طلب تسعير أجهزة كمبيوتر للإدارة" required>
                </div>
            </div>
            <div class="grid-3" style="margin-top: 24px;">
                <div>
                    <label class="input-label">تاريخ إنشاء الطلب</label>
                    <input type="date" name="request_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($rfq->request_date) : date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="input-label">الموعد النهائي لاستلام العروض (Deadline) <span style="color:red">*</span></label>
                    <input type="date" name="deadline_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($rfq->deadline_date) : date('Y-m-d', strtotime('+7 days')) ?>" required>
                </div>
                <div>
                    <label class="input-label">رقم الـ RFQ (يولد تلقائياً)</label>
                    <input type="text" name="rfq_number" class="form-control" style="font-family:monospace; color:var(--c-orange); font-weight:bold;" value="<?= $isEdit ? htmlspecialchars($rfq->rfq_number) : '' ?>" <?= $isEdit ? 'readonly' : 'placeholder="تلقائي"' ?>>
                </div>
            </div>
            
            <div class="grid-2" style="margin-top: 24px;">
                <div>
                    <label class="input-label">حالة الطلب</label>
                    <select name="status" class="form-control" style="font-weight: 800;">
                        <?php $st = $isEdit ? $rfq->status : 'draft'; ?>
                        <option value="draft" <?= $st=='draft' ? 'selected' : '' ?>>مسودة (Draft)</option>
                        <option value="published" <?= $st=='published' ? 'selected' : '' ?>>بانتظار العروض (Published)</option>
                        <option value="closed" <?= $st=='closed' ? 'selected' : '' ?>>مغلق (Closed)</option>
                        <option value="awarded" <?= $st=='awarded' ? 'selected' : '' ?>>تم الترسية (Awarded)</option>
                    </select>
                </div>
                <div>
                    <label class="input-label">ملاحظات للموردين</label>
                    <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($rfq->notes ?? '') : '' ?>" placeholder="شروط أو تفاصيل إضافية للأسعار...">
                </div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-buildings" style="color:var(--c-orange);"></i> تحديد الموردين المدعوين لتقديم الأسعار</h3>
            <div class="sup-grid">
                <?php if(empty($suppliers)): ?>
                    <div style="grid-column:span 3; color:red; font-weight:bold;">الرجاء إضافة موردين نشطين أولاً!</div>
                <?php else: foreach($suppliers as $s): ?>
                    <label class="sup-item">
                        <input type="checkbox" name="suppliers[]" value="<?= $s->id ?>" <?= ($isEdit && in_array($s->id, $selectedSuppliers)) ? 'checked' : '' ?>> 
                        <?= htmlspecialchars($s->name_ar) ?> <span style="color:#94a3b8; font-size:0.75rem; font-family:monospace;">(<?= $s->code ?>)</span>
                    </label>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="panel-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h3 class="panel-title" style="margin:0; border:none; padding:0;"><i class="ph-duotone ph-list-numbers" style="color:var(--c-orange);"></i> الأصناف المطلوب تسعيرها</h3>
                <button type="button" onclick="addRow()" class="btn-add-row"><i class="ph-bold ph-plus"></i> إضافة صنف</button>
            </div>
            
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width: 25%;">كود الصنف (اختياري)</th>
                        <th style="width: 50%;">الوصف / المواصفات المطلوبة <span style="color:red">*</span></th>
                        <th style="width: 15%; text-align: center;">الكمية</th>
                        <th style="width: 10%; text-align: center;">إزالة</th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <?php if($isEdit && !empty($items)): foreach($items as $item): ?>
                        <tr>
                            <td>
                                <select name="product_id[]" class="form-control">
                                    <option value="">-- غير مسجل / وصف يدوي --</option>
                                    <?php foreach($products ?? [] as $p): ?>
                                        <option value="<?= $p->id ?>" <?= $item->product_id == $p->id ? 'selected' : '' ?>><?= htmlspecialchars($p->code) ?> - <?= htmlspecialchars($p->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="description[]" class="form-control" value="<?= htmlspecialchars($item->description) ?>" required></td>
                            <td><input type="number" step="0.01" name="quantity[]" class="form-control" style="text-align:center; font-weight:700;" value="<?= $item->quantity ?>" required></td>
                            <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove()"><i class="ph-bold ph-trash"></i></button></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <!-- سيتم إضافة الصف عبر الجافاسكريبت -->
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/purchasing/rfq" class="btn-cancel">إلغاء وتراجع</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-paper-plane-tilt"></i> <?= $isEdit ? 'تحديث الطلب' : 'حفظ ونشر طلب التسعير' ?></button>
        </div>
    </form>
</div>

<template id="rowTemplate">
    <tr>
        <td>
            <select name="product_id[]" class="form-control">
                <option value="">-- غير مسجل / وصف يدوي --</option>
                <?php foreach($products ?? [] as $p): ?>
                    <option value="<?= $p->id ?>"><?= htmlspecialchars($p->code) ?> - <?= htmlspecialchars($p->name) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="text" name="description[]" class="form-control" placeholder="اكتب المواصفات بدقة للمورد..." required></td>
        <td><input type="number" step="0.01" name="quantity[]" class="form-control" style="text-align:center; font-weight:700;" value="1.00" required></td>
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