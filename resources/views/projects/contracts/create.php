<?php
// Path: resources/views/projects/contracts/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($contract) && $contract !== null && !empty($contract->id);
$actionUrl = $isEdit ? "/ERP/projects/contracts/{$contract->id}/update" : "/ERP/projects/contracts/store";
?>

<style>
    :root { 
        --c-pcontract: #be123c; 
        --c-pcontract-dark: #9f1239; 
        --c-pcontract-light: #fff1f2;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-pcontract); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-pcontract); font-size: 1.4rem; padding: 8px; background: var(--c-pcontract-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-pcontract), var(--c-pcontract-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/contracts" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل بيانات العقد' : 'تسجيل وإصدار عقد مشروع جديد' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">تحرير شروط العقد، القيم المالية، نسب ضمان حسن التنفيذ والتواريخ.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-file-search"></i> البيانات التعريفية للعقد</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">رقم العقد <span style="color:red">*</span></label>
                    <input type="text" name="contract_number" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-pcontract-dark);" value="<?= $isEdit ? htmlspecialchars($contract->contract_number) : htmlspecialchars($autoCode) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label class="input-label">نوع العقد <span style="color:red">*</span></label>
                    <select name="contract_type" class="form-control" required>
                        <option value="owner_contract" <?= (!$isEdit || $contract->contract_type === 'owner_contract') ? 'selected' : '' ?>>عقد المالك الرئيسي (Owner Contract)</option>
                        <option value="subcontractor_contract" <?= ($isEdit && $contract->contract_type === 'subcontractor_contract') ? 'selected' : '' ?>>عقد مقاول فرعي (Subcontractor)</option>
                        <option value="consultant_contract" <?= ($isEdit && $contract->contract_type === 'consultant_contract') ? 'selected' : '' ?>>عقد استشاري (Consultant)</option>
                        <option value="supply_contract" <?= ($isEdit && $contract->contract_type === 'supply_contract') ? 'selected' : '' ?>>عقد توريد مواد (Supply Contract)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">المشروع التابع له <span style="color:red">*</span></label>
                    <select name="project_id" class="form-control" required>
                        <option value="">-- اختر المشروع --</option>
                        <?php foreach($projects as $p): ?>
                            <option value="<?= $p->id ?>" <?= ($isEdit && $contract->project_id == $p->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p->code) ?> - <?= htmlspecialchars($p->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">عنوان العقد (عربي) <span style="color:red">*</span></label>
                    <input type="text" name="title_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->title_ar) : '' ?>" placeholder="مثال: عقد تنفيذ أعمال التشطيبات والتكييف..." required>
                </div>
                <div class="form-group">
                    <label class="input-label">عنوان العقد (إنجليزي)</label>
                    <input type="text" name="title_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->title_en ?? '') : '' ?>" placeholder="e.g. Main Construction & Finishing Agreement">
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">العميل / مالك العقد</label>
                <select name="customer_id" class="form-control">
                    <option value="">-- تعيين تلقائي من العميل المسجل بالمشروع --</option>
                    <?php foreach($customers as $c): ?>
                        <option value="<?= $c->id ?>" <?= ($isEdit && $contract->customer_id == $c->id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c->name_ar) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-currency-circle-dollar"></i> القيمة المادية وضمانات التنفيذ</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">قيمة العقد الكلية <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0" name="contract_value" class="form-control" style="font-family:monospace; font-weight:900; color:var(--c-pcontract-dark); font-size:1.15rem;" value="<?= $isEdit ? htmlspecialchars($contract->contract_value) : '0.00' ?>" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label class="input-label">قيمة الدفعة المقدمة (Advance Payment)</label>
                    <input type="number" step="0.01" min="0" name="advance_payment_amount" class="form-control" style="font-family:monospace; font-weight:800; color:#059669; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($contract->advance_payment_amount) : '0.00' ?>" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="input-label">نسبة استقطاع ضمان حسن التنفيذ (%)</label>
                    <input type="number" step="0.1" min="0" max="100" name="retention_percent" class="form-control" style="font-family:monospace; font-weight:800; color:#d97706; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($contract->retention_percent) : '5.0' ?>" placeholder="5.0">
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">تاريخ توقيع العقد</label>
                    <input type="date" name="sign_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->sign_date ?? '') : date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ بدء سريان العقد <span style="color:red">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->start_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ تسليم وانتهاء العقد</label>
                    <input type="date" name="end_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->end_date ?? '') : date('Y-m-d', strtotime('+1 year')) ?>">
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">حالة العقد <span style="color:red">*</span></label>
                <select name="status" class="form-control" required>
                    <option value="active" <?= (!$isEdit || $contract->status === 'active') ? 'selected' : '' ?>>ساري وساري التنفيذ (Active)</option>
                    <option value="draft" <?= ($isEdit && $contract->status === 'draft') ? 'selected' : '' ?>>مسودة (Draft)</option>
                    <option value="under_renewal" <?= ($isEdit && $contract->status === 'under_renewal') ? 'selected' : '' ?>>قيد التجديد (Under Renewal)</option>
                    <option value="completed" <?= ($isEdit && $contract->status === 'completed') ? 'selected' : '' ?>>مكتمل ومغلق (Completed)</option>
                    <option value="suspended" <?= ($isEdit && $contract->status === 'suspended') ? 'selected' : '' ?>>موقف مؤقتاً (Suspended)</option>
                    <option value="terminated" <?= ($isEdit && $contract->status === 'terminated') ? 'selected' : '' ?>>مفسوخ / ملغى (Terminated)</option>
                </select>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">الشروط والبنود الالتزامية للعقد</label>
                <textarea name="terms_and_conditions" class="form-control" rows="3" placeholder="البنود الجزائية، شروط الصرف، الغرامات التأخيرية..."><?= $isEdit ? htmlspecialchars($contract->terms_and_conditions ?? '') : '' ?></textarea>
            </div>

            <div class="form-group" style="margin-top:16px;">
                <label class="input-label">ملاحظات إضافية</label>
                <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->notes ?? '') : '' ?>" placeholder="ملاحظات الحفظ والتسليم...">
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/projects/contracts" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث العقد' : 'حفظ وتسجيل العقد' ?></button>
        </div>
    </form>
</div>