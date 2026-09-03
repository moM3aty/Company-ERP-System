<?php
// Path: resources/views/purchasing/contracts/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();
$isEdit = isset($contract) && $contract !== null;
$actionUrl = $isEdit ? "/ERP/purchasing/contracts/{$contract->id}/update" : "/ERP/purchasing/contracts/store";

$t = [
    'ar' => [
        'title_new' => 'إبرام عقد مشتريات جديد',
        'title_edit' => 'تعديل بيانات العقد',
        'panel_basic' => 'بيانات الاتفاقية والمورد',
        'subject' => 'عنوان العقد / الموضوع',
        'subject_placeholder' => 'مثال: عقد توريد مواد خام سنوي',
        'supplier' => 'المورد (الطرف الثاني)',
        'choose_supplier' => '-- اختر المورد --',
        'contract_no' => 'رقم العقد (يولد تلقائياً إن تُرك فارغاً)',
        'panel_validity' => 'الصلاحية والمالية',
        'start_date' => 'تاريخ بداية العقد',
        'end_date' => 'تاريخ انتهاء العقد',
        'total_value' => 'القيمة الإجمالية التقديرية للعقد',
        'status' => 'حالة العقد',
        'status_draft' => 'مسودة (Draft)',
        'status_active' => 'ساري (Active)',
        'status_expired' => 'منتهي (Expired)',
        'status_terminated' => 'مفسوخ (Terminated)',
        'panel_terms' => 'الشروط والأحكام',
        'terms_label' => 'الشروط والأحكام المتفق عليها (Terms & Conditions)',
        'terms_placeholder' => 'اكتب الشروط والأحكام الخاصة بالتوريد والدفع هنا...',
        'notes_label' => 'ملاحظات داخلية (لا تظهر في الطباعة)',
        'cancel' => 'إلغاء وتراجع',
        'save' => 'اعتماد وحفظ العقد',
        'update' => 'تحديث العقد'
    ],
    'en' => [
        'title_new' => 'New Purchase Contract Agreement',
        'title_edit' => 'Edit Purchase Contract',
        'panel_basic' => 'Agreement & Supplier Details',
        'subject' => 'Contract Title / Subject',
        'subject_placeholder' => 'e.g. Annual Raw Materials Supply Agreement',
        'supplier' => 'Supplier (Second Party)',
        'choose_supplier' => '-- Select Supplier --',
        'contract_no' => 'Contract No (Auto generated if empty)',
        'panel_validity' => 'Validity & Financials',
        'start_date' => 'Contract Start Date',
        'end_date' => 'Contract End Date',
        'total_value' => 'Estimated Total Contract Value',
        'status' => 'Contract Status',
        'status_draft' => 'Draft',
        'status_active' => 'Active',
        'status_expired' => 'Expired',
        'status_terminated' => 'Terminated',
        'panel_terms' => 'Terms & Conditions',
        'terms_label' => 'Agreed Terms & Conditions',
        'terms_placeholder' => 'Write supply, payment, and delivery terms here...',
        'notes_label' => 'Internal Notes (Hidden from print)',
        'cancel' => 'Cancel',
        'save' => 'Approve & Save Contract',
        'update' => 'Update Contract'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-primary: #7c3aed;
        --c-primary-dark: #6d28d9;
        --c-primary-light: #ede9fe;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
        --c-bg: #f8fafc;
    }

    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .back-btn { width: 42px; height: 42px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; color: var(--c-text-muted); text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-primary-light); color: var(--c-primary); border-color: #c4b5fd; }
    .form-title { font-size: 1.6rem; font-weight: 800; color: var(--c-text-dark); margin: 0; }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 24px; border-top: 4px solid var(--c-primary); }
    .panel-title { font-size: 1.1rem; color: var(--c-text-dark); font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 10px;}
    
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: var(--c-bg); transition: 0.2s; color: var(--c-text-dark); }
    .form-control:focus { border-color: var(--c-primary); box-shadow: 0 0 0 4px var(--c-primary-light); background: #ffffff;}
    .input-label { font-size: 0.85rem; font-weight: 800; color: var(--c-text-muted); margin-bottom: 8px; display: block; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    
    .btn-submit { background: linear-gradient(135deg, var(--c-primary), var(--c-primary-dark)); color: white; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 800; font-size: 0.95rem; cursor: pointer; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25); transition: 0.2s; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(124, 58, 237, 0.35); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: var(--c-text-muted); padding: 12px 28px; border-radius: 12px; font-weight: 800; font-size: 0.95rem; text-decoration: none; transition: 0.2s; }
    .btn-cancel:hover { background: #f8fafc; color: var(--c-text-dark); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <a href="/ERP/purchasing/contracts" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <h2 class="form-title"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-article" style="color:var(--c-primary);"></i> <?= $t['panel_basic'] ?></h3>
            <div class="grid-2">
                <div style="grid-column: span 2;">
                    <label class="input-label"><?= $t['subject'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->title) : '' ?>" placeholder="<?= $t['subject_placeholder'] ?>" required>
                </div>

                <div>
                    <label class="input-label"><?= $t['supplier'] ?> <span style="color:red">*</span></label>
                    <select name="supplier_id" class="form-control" required>
                        <option value=""><?= $t['choose_supplier'] ?></option>
                        <?php foreach($suppliers ?? [] as $s): ?>
                            <option value="<?= $s->id ?>" <?= ($isEdit && $contract->supplier_id == $s->id) ? 'selected' : '' ?>><?= htmlspecialchars($s->name_ar) ?> (<?= $s->code ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="input-label"><?= $t['contract_no'] ?></label>
                    <input type="text" name="contract_number" class="form-control" style="font-family:monospace; color:var(--c-primary); font-weight:bold;" value="<?= $isEdit ? htmlspecialchars($contract->contract_number) : '' ?>" <?= $isEdit ? 'readonly' : '' ?>>
                </div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-calendar" style="color:var(--c-primary);"></i> <?= $t['panel_validity'] ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $t['start_date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->start_date) : date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['end_date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="end_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->end_date) : date('Y-m-d', strtotime('+1 year')) ?>" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['total_value'] ?> (<?= $currency ?>)</label>
                    <input type="number" step="0.01" name="total_value" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-primary);" value="<?= $isEdit ? htmlspecialchars($contract->total_value) : '0.00' ?>">
                </div>
                <div>
                    <label class="input-label"><?= $t['status'] ?></label>
                    <select name="status" class="form-control">
                        <?php $st = $isEdit ? $contract->status : 'draft'; ?>
                        <option value="draft" <?= $st=='draft'?'selected':'' ?>><?= $t['status_draft'] ?></option>
                        <option value="active" <?= $st=='active'?'selected':'' ?>><?= $t['status_active'] ?></option>
                        <option value="expired" <?= $st=='expired'?'selected':'' ?>><?= $t['status_expired'] ?></option>
                        <option value="terminated" <?= $st=='terminated'?'selected':'' ?>><?= $t['status_terminated'] ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-scales" style="color:var(--c-primary);"></i> <?= $t['panel_terms'] ?></h3>
            <div style="margin-bottom: 20px;">
                <label class="input-label"><?= $t['terms_label'] ?></label>
                <textarea name="terms_conditions" class="form-control" rows="6" placeholder="<?= $t['terms_placeholder'] ?>"><?= $isEdit ? htmlspecialchars($contract->terms_conditions ?? '') : '' ?></textarea>
            </div>
            <div>
                <label class="input-label"><?= $t['notes_label'] ?></label>
                <textarea name="notes" class="form-control" rows="2"><?= $isEdit ? htmlspecialchars($contract->notes ?? '') : '' ?></textarea>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/purchasing/contracts" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>