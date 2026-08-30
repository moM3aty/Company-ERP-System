<?php
// Path: resources/views/treasury/cheques/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($cheque) && $cheque !== null && !empty($cheque->id);
$actionUrl = $isEdit ? "/ERP/treasury/cheques/{$cheque->id}/update" : "/ERP/treasury/cheques/store";
?>

<style>
    :root { 
        --c-chq: #c026d3; 
        --c-chq-dark: #a21caf; 
        --c-chq-light: #fdf4ff;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-chq); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-chq); font-size: 1.4rem; padding: 8px; background: var(--c-chq-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-chq), var(--c-chq-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/cheques" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل بيانات الشيك' : 'تسجيل ورقة مالية / شيك جديد' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">إدخال بيانات الشيك والبنك وتواريخ الاستحقاق.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-checks"></i> البيانات المباشرة للشيك</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">نوع الشيك <span style="color:red">*</span></label>
                    <select name="type" class="form-control" required>
                        <option value="received" <?= ($isEdit && $cheque->type === 'received') ? 'selected' : '' ?>>شيك وارد (استلام من عميل)</option>
                        <option value="issued" <?= ($isEdit && $cheque->type === 'issued') ? 'selected' : '' ?>>شيك صادر (دفع لمورد)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">رقم الشيك الورقي <span style="color:red">*</span></label>
                    <input type="text" name="cheque_number" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-chq-dark);" value="<?= $isEdit ? htmlspecialchars($cheque->cheque_number) : '' ?>" placeholder="مثال: CHQ-88019" required>
                </div>
                <div class="form-group">
                    <label class="input-label">قيمة الشيك <span style="color:red">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" style="font-family:monospace; font-weight:900; color:var(--c-chq-dark); font-size:1.2rem;" value="<?= $isEdit ? htmlspecialchars($cheque->amount) : '' ?>" placeholder="0.00" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">اسم البنك المسحوب عليه <span style="color:red">*</span></label>
                    <input type="text" name="bank_name" class="form-control" value="<?= $isEdit ? htmlspecialchars($cheque->bank_name) : '' ?>" placeholder="مثال: البنك الأهلي / بنك مصر / الراجحي..." required>
                </div>
                <div class="form-group">
                    <label class="input-label">الحساب / الخزينة المرتبطة <span style="color:red">*</span></label>
                    <select name="treasury_account_id" class="form-control" required>
                        <option value="">-- اختر الحساب المالي --</option>
                        <?php foreach($treasuryAccounts as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && $cheque->treasury_account_id == $acc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-calendar"></i> الأطراف والتواريخ</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">اسم المستفيد / الساحب <span style="color:red">*</span></label>
                    <input type="text" name="payee_payer_name" class="form-control" value="<?= $isEdit ? htmlspecialchars($cheque->payee_payer_name) : '' ?>" placeholder="اسم الشخص أو الجهة المدونة بالشيك" required>
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ تحرير الشيك <span style="color:red">*</span></label>
                    <input type="date" name="issue_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($cheque->issue_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ الاستحقاق للصرف <span style="color:red">*</span></label>
                    <input type="date" name="due_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($cheque->due_date) : date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">ملاحظات وشروط الشيك</label>
                <input type="text" name="notes" class="form-control" value="<?= $isEdit ? htmlspecialchars($cheque->notes) : '' ?>" placeholder="ملاحظات تفصيلية أو رقم العقد المربوط...">
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/treasury/cheques" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث بيانات الشيك' : 'حفظ وتسجيل الشيك' ?></button>
        </div>
    </form>
</div>
