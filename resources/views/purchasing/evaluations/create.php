<?php
// Path: resources/views/purchasing/evaluations/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($evaluation) && $evaluation !== null;
$actionUrl = $isEdit ? "/ERP/purchasing/supplier-evaluations/{$evaluation->id}/update" : "/ERP/purchasing/supplier-evaluations/store";
?>

<style>
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; }
    .form-title { font-size: 1.6rem; font-weight: 800; color: #0f172a; margin: 0; }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .panel-title { font-size: 1.1rem; color: #0f172a; font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 10px;}
    
    .input-label { font-size: 0.85rem; font-weight: 800; color: #475569; margin-bottom: 8px; display: block; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-family: inherit; font-size: 0.95rem; outline: none; background: #f8fafc; }
    .form-control:focus { border-color: #db2777; box-shadow: 0 0 0 4px #fce7f3; background: #ffffff;}
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    
    /* Criteria Score Range Card */
    .criteria-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }
    .criteria-info h5 { margin: 0 0 4px 0; color: #0f172a; font-size: 0.95rem; font-weight: 800; }
    .criteria-info p { margin: 0; color: #64748b; font-size: 0.8rem; }
    .criteria-input { width: 100px; text-align: center; font-weight: 900; font-family: monospace; font-size: 1.1rem; color: #db2777; }

    .score-preview-box { background: #0f172a; color: #ffffff; padding: 20px; border-radius: 16px; text-align: center; margin-top: 20px; }
    .score-preview-val { font-size: 2.2rem; font-weight: 900; font-family: monospace; color: #34d399; }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn { padding: 12px 28px; border-radius: 12px; font-weight: 800; font-size: 0.95rem; font-family: inherit; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none;}
    .btn-submit { background: linear-gradient(135deg, #db2777, #be185d); color: white; box-shadow: 0 4px 12px rgba(219, 39, 119, 0.25); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <a href="/ERP/purchasing/supplier-evaluations" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <h2 class="form-title"><?= $isEdit ? 'تعديل تقييم المورد' : 'إجراء تقييم مورد جديد' ?></h2>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-buildings text-pink-600"></i> بيانات المورد وتقييم الفترة</h3>
            <div class="grid-2">
                <div>
                    <label class="input-label">اختر المورد <span style="color:red">*</span></label>
                    <select name="supplier_id" class="form-control" required>
                        <option value="">-- اختر المورد --</option>
                        <?php foreach($suppliers ?? [] as $s): ?>
                            <option value="<?= $s->id ?>" <?= ($isEdit && $evaluation->supplier_id == $s->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($isRtl ? ($s->name_ar ?? $s->name_en) : $s->name_en) ?> (<?= htmlspecialchars($s->code) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="input-label">تاريخ التقييم <span style="color:red">*</span></label>
                    <input type="date" name="evaluation_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($evaluation->evaluation_date) : date('Y-m-d') ?>" required>
                </div>

                <div>
                    <label class="input-label">الفترة المغطاة بالتقييم</label>
                    <input type="text" name="period_covered" class="form-control" placeholder="مثال: الربع الأول 2026 / النصف السنوي" value="<?= $isEdit ? htmlspecialchars($evaluation->period_covered ?? '') : '' ?>">
                </div>

                <div>
                    <label class="input-label">اسم المقَيّم / المسؤول</label>
                    <input type="text" name="evaluator_name" class="form-control" placeholder="اسم مسؤول المشتريات" value="<?= $isEdit ? htmlspecialchars($evaluation->evaluator_name ?? '') : '' ?>">
                </div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-star text-pink-600"></i> معايير التقييم الأساسية (من 100%)</h3>

            <div class="criteria-box">
                <div class="criteria-info">
                    <h5>1. الالتزام بمواعيد التوريد (Delivery Time)</h5>
                    <p>مدى الدقة في تسليم البضائع والطلبيات في التواريخ المتفق عليها دون تأخير.</p>
                </div>
                <input type="number" name="delivery_score" id="del_score" class="form-control criteria-input" value="<?= $isEdit ? htmlspecialchars($evaluation->delivery_score) : '85' ?>" min="0" max="100" oninput="calcOverall()">
            </div>

            <div class="criteria-box">
                <div class="criteria-info">
                    <h5>2. جودة المنتجات والمواصفات (Quality & Specs)</h5>
                    <p>مطابقة المواد الموردة للمواصفات المطلوبة وقلة نسبة المرتجعات أو التالف.</p>
                </div>
                <input type="number" name="quality_score" id="qual_score" class="form-control criteria-input" value="<?= $isEdit ? htmlspecialchars($evaluation->quality_score) : '90' ?>" min="0" max="100" oninput="calcOverall()">
            </div>

            <div class="criteria-box">
                <div class="criteria-info">
                    <h5>3. تنافسية الأسعار والتسهيلات (Price & Terms)</h5>
                    <p>تناسب الأسعار مع السوق وتقديم التسهيلات المالية وشروط السداد المريحة.</p>
                </div>
                <input type="number" name="price_score" id="prc_score" class="form-control criteria-input" value="<?= $isEdit ? htmlspecialchars($evaluation->price_score) : '80' ?>" min="0" max="100" oninput="calcOverall()">
            </div>

            <div class="criteria-box">
                <div class="criteria-info">
                    <h5>4. خدمة ودعم ما بعد التوريد (Service & Support)</h5>
                    <p>سرعة التجاوب مع الاستفسارات، معالجة المشكلات واستبدال الأصناف بشكل سريع.</p>
                </div>
                <input type="number" name="service_score" id="srv_score" class="form-control criteria-input" value="<?= $isEdit ? htmlspecialchars($evaluation->service_score) : '85' ?>" min="0" max="100" oninput="calcOverall()">
            </div>

            <div class="score-preview-box">
                <div style="font-size:0.85rem; font-weight:700; opacity:0.8;">النتيجة التراكمية المحتسبة تلقائياً</div>
                <div class="score-preview-val" id="preview_val">85.00 %</div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-article text-pink-600"></i> التوصيات والقرار</h3>
            <div>
                <textarea name="recommendation" class="form-control" rows="3" placeholder="توصيات لجنة المشتريات (مثال: الاستمرار في التعامل، تجديد العقد، أو وضع المورد تحت الملاحظة)..."><?= $isEdit ? htmlspecialchars($evaluation->recommendation ?? '') : '' ?></textarea>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/purchasing/supplier-evaluations" class="btn" style="background: #ffffff; border: 1px solid #cbd5e1; color: #475569;">إلغاء</a>
            <button type="submit" class="btn btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث التقييم' : 'اعتماد التقييم' ?></button>
        </div>
    </form>
</div>

<script>
function calcOverall() {
    let d = parseFloat(document.getElementById('del_score').value) || 0;
    let q = parseFloat(document.getElementById('qual_score').value) || 0;
    let p = parseFloat(document.getElementById('prc_score').value) || 0;
    let s = parseFloat(document.getElementById('srv_score').value) || 0;

    let avg = ((d + q + p + s) / 4).toFixed(2);
    document.getElementById('preview_val').innerText = avg + ' %';
}
document.addEventListener('DOMContentLoaded', calcOverall);
</script>