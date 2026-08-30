<?php
// Path: resources/views/sales/contracts/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isRtl();
$currency = current_currency();

$isEdit = isset($contract) && $contract !== null;
$actionUrl = $isEdit ? "/ERP/sales/contracts/{$contract->id}/update" : "/ERP/sales/contracts/store";
?>

<style>
    .form-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-title { font-size: 1.8rem; font-weight: 800; color: #0f172a; margin: 0 0 24px 0; display: flex; align-items: center; gap: 12px; }
    .back-btn { width: 36px; height: 36px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .panel-title { font-size: 1.1rem; color: #0f172a; font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 8px;}
    
    .input-label { font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px; display: block; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.9rem; outline: none; background: #f8fafc; transition: 0.2s; }
    .form-control:focus { border-color: #16a34a; box-shadow: 0 0 0 3px #dcfce7; background: #ffffff;}
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn { padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; font-family: inherit; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none;}
    .btn-submit { background: linear-gradient(135deg, #16a34a, #15803d); color: white; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25); }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(22, 163, 74, 0.35); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <h2 class="form-title">
        <a href="/ERP/sales/contracts" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <?= $isEdit ? ($isRtl ? 'تعديل عقد مبيعات' : 'Edit Sales Contract') : ($isRtl ? 'إبرام عقد مبيعات جديد' : 'New Sales Contract') ?>
        <?php if($isEdit): ?>
            <span style="margin-inline-start: auto; color: #16a34a; font-family: monospace; font-size: 1.1rem;"><?= htmlspecialchars($contract->contract_number) ?></span>
        <?php endif; ?>
    </h2>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-file-text text-green-600"></i> <?= $isRtl ? 'بيانات الاتفاقية والطرف الثاني' : 'Agreement & Client Details' ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $isRtl ? 'عنوان العقد / الموضوع' : 'Contract Title / Subject' ?> <span style="color:red">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->title) : '' ?>" placeholder="<?= $isRtl ? 'عقد صيانة سنوية، توريد بضائع...' : 'Annual maintenance, supplying...' ?>" required>
                </div>

                <div>
                    <label class="input-label"><?= $isRtl ? 'العميل (الطرف الثاني)' : 'Customer (Second Party)' ?> <span style="color:red">*</span></label>
                    <select name="customer_id" class="form-control" required>
                        <option value="">-- <?= $isRtl ? 'اختر العميل' : 'Select Customer' ?> --</option>
                        <?php foreach($customers ?? [] as $c): ?>
                            <option value="<?= $c->id ?>" <?= ($isEdit && $contract->customer_id == $c->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($isRtl ? ($c->name_ar ?? $c->name_en) : ($c->name_en ?? $c->name_ar)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="grid-column: span 2;">
                    <label class="input-label"><?= $isRtl ? 'رقم العقد (تلقائي إن تُرك فارغاً)' : 'Contract Number (Auto if empty)' ?></label>
                    <input type="text" name="contract_number" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->contract_number) : '' ?>" <?= $isEdit ? 'readonly' : '' ?>>
                </div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-calendar text-green-600"></i> <?= $isRtl ? 'فترة السريان والمالية' : 'Validity & Financials' ?></h3>
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $isRtl ? 'تاريخ بداية العقد' : 'Start Date' ?> <span style="color:red">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->start_date) : date('Y-m-d') ?>" required>
                </div>

                <div>
                    <label class="input-label"><?= $isRtl ? 'تاريخ انتهاء العقد' : 'End Date' ?> <span style="color:red">*</span></label>
                    <input type="date" name="end_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->end_date) : date('Y-m-d', strtotime('+1 year')) ?>" required>
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div>
                    <label class="input-label"><?= $isRtl ? 'القيمة الإجمالية للعقد' : 'Total Contract Value' ?> (<?= htmlspecialchars($currency) ?>) <span style="color:red">*</span></label>
                    <input type="number" step="0.01" name="total_value" class="form-control" value="<?= $isEdit ? htmlspecialchars($contract->total_value) : '0.00' ?>" required style="font-weight:800; font-family:monospace; color:#16a34a;">
                </div>

                <div>
                    <label class="input-label"><?= $isRtl ? 'دورية الفوترة / التحصيل' : 'Billing Frequency' ?></label>
                    <select name="billing_frequency" class="form-control">
                        <?php $bf = $isEdit ? $contract->billing_frequency : 'monthly'; ?>
                        <option value="one_time" <?= $bf === 'one_time' ? 'selected' : '' ?>><?= $isRtl ? 'دفعة واحدة' : 'One Time' ?></option>
                        <option value="monthly" <?= $bf === 'monthly' ? 'selected' : '' ?>><?= $isRtl ? 'شهرياً' : 'Monthly' ?></option>
                        <option value="quarterly" <?= $bf === 'quarterly' ? 'selected' : '' ?>><?= $isRtl ? 'ربع سنوي' : 'Quarterly' ?></option>
                        <option value="semi_annually" <?= $bf === 'semi_annually' ? 'selected' : '' ?>><?= $isRtl ? 'نصف سنوي' : 'Semi-Annually' ?></option>
                        <option value="annually" <?= $bf === 'annually' ? 'selected' : '' ?>><?= $isRtl ? 'سنوياً' : 'Annually' ?></option>
                    </select>
                </div>

                <div>
                    <label class="input-label"><?= $isRtl ? 'حالة العقد' : 'Status' ?></label>
                    <select name="status" class="form-control">
                        <?php $st = $isEdit ? $contract->status : 'draft'; ?>
                        <option value="draft" <?= $st === 'draft' ? 'selected' : '' ?>><?= $isRtl ? 'مسودة' : 'Draft' ?></option>
                        <option value="active" <?= $st === 'active' ? 'selected' : '' ?>><?= $isRtl ? 'ساري' : 'Active' ?></option>
                        <option value="expired" <?= $st === 'expired' ? 'selected' : '' ?>><?= $isRtl ? 'منتهي' : 'Expired' ?></option>
                        <option value="terminated" <?= $st === 'terminated' ? 'selected' : '' ?>><?= $isRtl ? 'مفسوخ' : 'Terminated' ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-article text-green-600"></i> <?= $isRtl ? 'الشروط والأحكام الخاصة' : 'Terms & Conditions' ?></h3>
            <div>
                <textarea name="terms_conditions" class="form-control" rows="5" placeholder="<?= $isRtl ? 'أدخل الشروط والأحكام، الالتزامات والتعهدات المتبادلة...' : 'Enter terms, conditions, and obligations...' ?>"><?= $isEdit ? htmlspecialchars($contract->terms_conditions ?? '') : '' ?></textarea>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/sales/contracts" class="btn" style="background: #ffffff; border: 1px solid #cbd5e1; color: #475569;"><?= $isRtl ? 'إلغاء' : 'Cancel' ?></a>
            <button type="submit" class="btn btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? ($isRtl ? 'حفظ التعديلات' : 'Save Changes') : ($isRtl ? 'إبرام العقد' : 'Create Contract') ?></button>
        </div>
    </form>
</div>