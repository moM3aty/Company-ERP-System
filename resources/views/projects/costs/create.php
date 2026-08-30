<?php
// Path: resources/views/projects/costs/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($cost) && $cost !== null && !empty($cost->id);
$actionUrl = $isEdit ? "/ERP/projects/costs/{$cost->id}/update" : "/ERP/projects/costs/store";
?>

<style>
    :root { 
        --c-pcost: #ea580c; 
        --c-pcost-dark: #c2410c; 
        --c-pcost-light: #ffedd5;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-pcost); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-pcost); font-size: 1.4rem; padding: 8px; background: var(--c-pcost-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-pcost), var(--c-pcost-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/costs" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل سند مصروف الموقع' : 'تسجيل مصروف موقع جديد' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">ربط النفقات والمواد ببطاقة المشروع وتحديث التكاليف الفعلية.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-currency-dollar"></i> بيانات المصروف والمشروع</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">رقم السند/المصروف <span style="color:red">*</span></label>
                    <input type="text" name="voucher_number" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-pcost-dark);" value="<?= $isEdit ? htmlspecialchars($cost->voucher_number) : htmlspecialchars($autoCode) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label class="input-label">المشروع <span style="color:red">*</span></label>
                    <select name="project_id" class="form-control" required>
                        <option value="">-- اختر المشروع --</option>
                        <?php foreach($projects as $p): ?>
                            <option value="<?= $p->id ?>" <?= ($isEdit && $cost->project_id == $p->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p->code) ?> - <?= htmlspecialchars($p->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">تبويب التكلفة <span style="color:red">*</span></label>
                    <select name="cost_category" class="form-control" required>
                        <option value="materials" <?= (!$isEdit || $cost->cost_category === 'materials') ? 'selected' : '' ?>>مواد وتوريدات (Materials)</option>
                        <option value="labor" <?= ($isEdit && $cost->cost_category === 'labor') ? 'selected' : '' ?>>عمالة وأجور (Labor)</option>
                        <option value="equipment" <?= ($isEdit && $cost->cost_category === 'equipment') ? 'selected' : '' ?>>معدات وآليات (Equipment)</option>
                        <option value="subcontractor" <?= ($isEdit && $cost->cost_category === 'subcontractor') ? 'selected' : '' ?>>مقاولين فرعيين (Subcontractor)</option>
                        <option value="overhead" <?= ($isEdit && $cost->cost_category === 'overhead') ? 'selected' : '' ?>>مصروفات إدارية (Overhead)</option>
                        <option value="other" <?= ($isEdit && $cost->cost_category === 'other') ? 'selected' : '' ?>>مصروفات أخرى (Other)</option>
                    </select>
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">قيمة المصروف (المبلغ) <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" style="font-family:monospace; font-weight:900; color:#dc2626; font-size:1.2rem;" value="<?= $isEdit ? htmlspecialchars($cost->amount) : '' ?>" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ الصرف <span style="color:red">*</span></label>
                    <input type="date" name="cost_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($cost->cost_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">الرقم المرجعي / رقم الفاتورة/الإيصال</label>
                    <input type="text" name="reference_no" class="form-control" value="<?= $isEdit ? htmlspecialchars($cost->reference_no ?? '') : '' ?>" placeholder="مثال: Inv-9921 / Chq-881">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-buildings"></i> المورد والتسوية المالية</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">المورد / المقاول الفرعي (إن وجد)</label>
                    <select name="supplier_id" class="form-control">
                        <option value="">-- شراء/شخص مباشر --</option>
                        <?php foreach($suppliers as $s): ?>
                            <option value="<?= $s->id ?>" <?= ($isEdit && $cost->supplier_id == $s->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">الحساب المالي المصدر / المصروفات</label>
                    <select name="account_id" class="form-control">
                        <option value="">-- صندوق/خزينة الموقع --</option>
                        <?php foreach($accounts as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && $cost->account_id == $acc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">حالة السداد والتحصيل <span style="color:red">*</span></label>
                <select name="payment_status" class="form-control" required>
                    <option value="paid" <?= (!$isEdit || $cost->payment_status === 'paid') ? 'selected' : '' ?>>مسدد بالكامل (Paid)</option>
                    <option value="partially_paid" <?= ($isEdit && $cost->payment_status === 'partially_paid') ? 'selected' : '' ?>>مسدد جزئياً (Partially Paid)</option>
                    <option value="unpaid" <?= ($isEdit && $cost->payment_status === 'unpaid') ? 'selected' : '' ?>>غير مسدد / آجل (Unpaid)</option>
                </select>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">البيان والوصف التفصيلي</label>
                    <input type="text" name="description" class="form-control" value="<?= $isEdit ? htmlspecialchars($cost->description ?? '') : '' ?>" placeholder="مثال: شراء طن حديد تسليح / صيانة معدة الحفر...">
                </div>
                <div class="form-group">
                    <label class="input-label">ملاحظات إضافية</label>
                    <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($cost->notes ?? '') : '' ?>" placeholder="اسم المستلم / شروط الاستلام...">
                </div>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/projects/costs" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث المصروف' : 'حفظ وتسجيل المصروف' ?></button>
        </div>
    </form>
</div>