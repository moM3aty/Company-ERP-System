<?php
// Path: resources/views/purchasing/requisitions/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

// فحص هل الشاشة في وضع التعديل أم الإضافة
$isEdit = isset($pr) && $pr !== null;
$actionUrl = $isEdit ? "/ERP/purchasing/requisitions/{$pr->id}/update" : "/ERP/purchasing/requisitions/store";
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

    /* Items Table */
    .items-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 16px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
    .items-table th { padding: 14px 12px; background: #f8fafc; color: var(--c-text-muted); font-size: 0.8rem; font-weight: 800; text-transform: uppercase; text-align: start; border-bottom: 1px solid #e2e8f0;}
    .items-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; background: #ffffff; }
    
    .btn-remove-row { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 8px; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; }
    .btn-add-row { background: var(--c-teal-light); color: var(--c-teal); border: 1px solid #99f6e4; padding: 8px 16px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn-submit { background: linear-gradient(135deg, var(--c-teal), var(--c-teal-dark)); color: white; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: var(--c-text-muted); padding: 12px 28px; border-radius: 12px; font-weight: 800; text-decoration: none; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <a href="/ERP/purchasing/requisitions" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <h2 class="form-title"><?= $isEdit ? 'تعديل طلب الشراء' : 'إنشاء طلب شراء (PR)' ?></h2>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-info" style="color:var(--c-teal);"></i> بيانات الطلب الأساسية</h3>
            <div class="grid-2">
                <div>
                    <label class="input-label">الإدارة الطالبة <span style="color:red">*</span></label>
                    <input type="text" name="department" class="form-control" value="<?= $isEdit ? htmlspecialchars($pr->department) : '' ?>" placeholder="مثال: إدارة تقنية المعلومات" required>
                </div>
                <div>
                    <label class="input-label">اسم مقدم الطلب <span style="color:red">*</span></label>
                    <input type="text" name="requested_by" class="form-control" value="<?= $isEdit ? htmlspecialchars($pr->requested_by) : '' ?>" placeholder="اسم الموظف" required>
                </div>
            </div>
            <div class="grid-3" style="margin-top: 24px;">
                <div>
                    <label class="input-label">تاريخ الطلب</label>
                    <input type="date" name="request_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($pr->request_date) : date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="input-label">تاريخ الاحتياج (الاستحقاق) <span style="color:red">*</span></label>
                    <input type="date" name="required_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($pr->required_date) : '' ?>" required>
                </div>
                <div>
                    <label class="input-label">حالة الطلب</label>
                    <select name="status" class="form-control" style="font-weight: 800;">
                        <?php $st = $isEdit ? $pr->status : 'pending'; ?>
                        <option value="draft" <?= $st=='draft' ? 'selected' : '' ?>>مسودة</option>
                        <option value="pending" <?= $st=='pending' ? 'selected' : '' ?>>قيد الاعتماد</option>
                        <option value="approved" <?= $st=='approved' ? 'selected' : '' ?>>معتمد</option>
                        <option value="rejected" <?= $st=='rejected' ? 'selected' : '' ?>>مرفوض</option>
                        <option value="completed" <?= $st=='completed' ? 'selected' : '' ?>>تم الشراء</option>
                    </select>
                </div>
            </div>
            
            <div class="grid-2" style="margin-top: 24px;">
                <div>
                    <label class="input-label">رقم الطلب (PR Number)</label>
                    <input type="text" name="pr_number" class="form-control" style="font-family:monospace; color:var(--c-teal); font-weight:bold;" value="<?= $isEdit ? htmlspecialchars($pr->pr_number) : '' ?>" <?= $isEdit ? 'readonly' : 'placeholder="يولد تلقائياً"' ?>>
                </div>
                <div>
                    <label class="input-label">مبرر الشراء / ملاحظات</label>
                    <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($pr->notes ?? '') : '' ?>" placeholder="اكتب سبب الاحتياج لهذه الأصناف...">
                </div>
            </div>
        </div>

        <div class="panel-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h3 class="panel-title" style="margin:0; border:none; padding:0;"><i class="ph-duotone ph-list-numbers" style="color:var(--c-teal);"></i> الأصناف المطلوبة</h3>
                <button type="button" onclick="addRow()" class="btn-add-row"><i class="ph-bold ph-plus"></i> إضافة صنف</button>
            </div>
            
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width: 25%;">كود الصنف (اختياري)</th>
                        <th style="width: 35%;">الوصف / المواصفات الفنية <span style="color:red">*</span></th>
                        <th style="width: 15%; text-align: center;">الكمية</th>
                        <th style="width: 15%; text-align: center;">السعر التقديري</th>
                        <th style="width: 10%; text-align: center;">إزالة</th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <?php if($isEdit && !empty($items)): foreach($items as $item): ?>
                        <tr>
                            <td>
                                <select name="product_id[]" class="form-control">
                                    <option value="">-- غير مسجل / يدوي --</option>
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
                    <?php endforeach; else: ?>
                        <!-- سيتم إضافة الصف عبر الجافاسكريبت إذا كان جديداً -->
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/purchasing/requisitions" class="btn-cancel">إلغاء وتراجع</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-paper-plane-tilt"></i> <?= $isEdit ? 'تحديث الطلب' : 'إرسال الطلب للاعتماد' ?></button>
        </div>
    </form>
</div>

<!-- Template for JavaScript -->
<template id="rowTemplate">
    <tr>
        <td>
            <select name="product_id[]" class="form-control">
                <option value="">-- غير مسجل / يدوي --</option>
                <?php foreach($products ?? [] as $p): ?>
                    <option value="<?= $p->id ?>"><?= htmlspecialchars($p->code) ?> - <?= htmlspecialchars($p->name) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="text" name="description[]" class="form-control" placeholder="اكتب الوصف التفصيلي للصنف..." required></td>
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
// إضافة صف فارغ مبدئياً إذا كنا في وضع الإضافة وليس التعديل
<?php if(!$isEdit || empty($items)): ?>
document.addEventListener('DOMContentLoaded', addRow);
<?php endif; ?>
</script>