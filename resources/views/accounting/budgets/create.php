<?php
// Path: resources/views/accounting/budgets/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$isEdit = isset($budget) && $budget !== null && !empty($budget->id);
$actionUrl = $isEdit ? "/ERP/accounting/budgets/{$budget->id}/update" : "/ERP/accounting/budgets/store";

$t = [
    'ar' => [
        'title_new' => 'إضافة موازنة جديدة', 'title_edit' => 'تعديل بيانات الموازنة', 'desc' => 'تحديد الاعتماد المالي للسنة وربطه بالحسابات أو مراكز التكلفة.',
        'sec_basic' => 'البيانات الأساسية للموازنة', 'name_ar' => 'اسم الموازنة (عربي)', 'name_en' => 'اسم الموازنة (إنجليزي)',
        'year' => 'السنة المالية', 'branch' => 'الفرع المخصص', 'general_branch' => '-- موازنة عامة (كل الفروع) --',
        'amt' => 'المبلغ المعتمد', 'sec_link' => 'الربط المحاسبي (التتبع الدفتري)',
        'acc' => 'الحساب المالي المرتبط (اختياري)', 'acc_null' => '-- بدون ربط حساب --',
        'cc' => 'مركز التكلفة المرتبط (اختياري)', 'cc_null' => '-- بدون ربط بمركز تكلفة --',
        'sec_settings' => 'الإعدادات التشغيلية', 'is_active' => 'موازنة نشطة', 'is_active_desc' => 'تفعيلها لبدء تتبع واحتساب المصروفات الفعلية.',
        'cancel' => 'إلغاء وتراجع', 'save' => 'اعتماد الموازنة', 'update' => 'تحديث الموازنة'
    ],
    'en' => [
        'title_new' => 'Add New Budget', 'title_edit' => 'Edit Budget Data', 'desc' => 'Set financial allocation for the year and link to accounts/cost centers.',
        'sec_basic' => 'Basic Budget Information', 'name_ar' => 'Budget Name (AR)', 'name_en' => 'Budget Name (EN)',
        'year' => 'Fiscal Year', 'branch' => 'Assigned Branch', 'general_branch' => '-- General (All Branches) --',
        'amt' => 'Allocated Amount', 'sec_link' => 'Accounting Link (Tracking)',
        'acc' => 'Linked GL Account (Optional)', 'acc_null' => '-- No Account Link --',
        'cc' => 'Linked Cost Center (Optional)', 'cc_null' => '-- No Cost Center Link --',
        'sec_settings' => 'Operational Settings', 'is_active' => 'Active Budget', 'is_active_desc' => 'Enable to start tracking actual expenses against it.',
        'cancel' => 'Cancel', 'save' => 'Allocate Budget', 'update' => 'Update Budget'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --brand-primary: #d97706; --brand-primary-dark: #b45309; --brand-primary-light: #fef3c7; 
        --surface: #ffffff; --surface-hover: #f8fafc; --bg-body: #f1f5f9;
        --text-main: #0f172a; --text-muted: #64748b; --border-color: #e2e8f0;
        --radius-lg: 24px; --radius-md: 16px;
        --shadow-soft: 0 10px 30px -10px rgba(15, 23, 42, 0.08);
        --shadow-hover: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
    }
    .form-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: var(--bg-body); }
    
    .glass-header { position: sticky; top: 0; z-index: 50; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); padding: 24px 30px; margin-bottom: 30px; border-bottom: 1px solid rgba(226, 232, 240, 0.6); display: flex; align-items: center; justify-content: space-between; }
    .back-btn { width: 48px; height: 48px; border-radius: 14px; background: var(--surface); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--text-muted); font-size: 1.3rem; transition: 0.3s; box-shadow: var(--shadow-soft);}
    .back-btn:hover { background: var(--brand-primary-light); color: var(--brand-primary); border-color: #fcd34d; transform: translateX(<?= $isRtl ? '4px' : '-4px' ?>); }

    .form-section { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 32px; margin: 0 30px 30px 30px; border-top: 5px solid var(--brand-primary); box-shadow: var(--shadow-soft); transition:0.3s;}
    .form-section:hover { box-shadow: var(--shadow-hover); }
    .section-title { font-size: 1.15rem; font-weight: 900; color: var(--text-main); margin: 0 0 24px 0; border-bottom: 2px dashed var(--border-color); padding-bottom: 14px; display: flex; align-items: center; gap: 10px;}
    .section-title i { color: var(--brand-primary); font-size: 1.5rem; padding: 8px; background: var(--brand-primary-light); border-radius: 10px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    @media(max-width:768px) { .grid-2 { grid-template-columns: 1fr; } }
    
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: var(--text-muted); }
    .form-control { width: 100%; padding: 14px 18px; border: 1px solid #cbd5e1; border-radius: 12px; background: var(--surface-hover); font-weight: 700; color: var(--text-main); font-family:inherit; transition: 0.3s; box-sizing: border-box;}
    .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.15); outline: none; background: var(--surface);}
    
    .toggle-card { display: flex; justify-content: space-between; align-items: center; padding: 20px; border: 1px solid var(--border-color); border-radius: 14px; background: var(--surface-hover); transition: 0.3s; }
    .toggle-card:hover { border-color: #cbd5e1; background: var(--surface); box-shadow: var(--shadow-soft); }
    .toggle-info h4 { margin: 0 0 6px 0; font-size: 1rem; font-weight: 900; color: var(--text-main); }
    .toggle-info p { margin: 0; font-size: 0.85rem; color: var(--text-muted); font-weight: 600; line-height: 1.5; }
    
    .switch { position: relative; display: inline-block; width: 54px; height: 30px; flex-shrink: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
    input:checked + .slider { background-color: var(--brand-primary); }
    input:checked + .slider:before { transform: translateX(24px); }
    [dir="rtl"] input:checked + .slider:before { transform: translateX(-24px); left: auto; right: 4px; }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(16px); border-top: 1px solid var(--border-color); padding: 18px 30px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; box-shadow: 0 -10px 30px rgba(0,0,0,0.05);}
    .btn-submit { background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 900; font-size: 1rem; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 8px 20px rgba(217, 119, 6, 0.25); transition: 0.3s; }
    .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(217, 119, 6, 0.35); }
    .btn-cancel { background: var(--surface); border: 1px solid #cbd5e1; color: #475569; padding: 14px 32px; border-radius: 12px; font-weight: 900; font-size: 1rem; text-decoration: none; transition: 0.3s;}
    .btn-cancel:hover { background: var(--surface-hover); color: var(--text-main); border-color: #94a3b8; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="glass-header">
        <div style="display:flex; align-items:center; gap:18px;">
            <a href="/ERP/accounting/budgets" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--text-main); font-weight:900;"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--text-muted); font-size:0.95rem; font-weight:700;"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="margin: 0 30px 24px 30px; background: #fff1f2; color: #e11d48; padding: 18px 20px; border-radius: 16px; border: 1px solid #fecdd3; font-weight:800; display:flex; align-items:center; gap:12px; box-shadow:var(--shadow-soft);"><i class="ph-bold ph-warning-circle" style="font-size:1.6rem;"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-fill ph-chart-polar"></i> <?= $t['sec_basic'] ?></h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label"><?= $t['name_ar'] ?> <span style="color:var(--brand-primary);">*</span></label>
                    <input type="text" name="name_ar" class="form-control" style="font-size:1.1rem; color:var(--brand-primary-dark);" value="<?= $isEdit ? htmlspecialchars($budget->name_ar ?? '') : '' ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['name_en'] ?></label>
                    <input type="text" name="name_en" class="form-control" style="font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($budget->name_en ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['year'] ?> <span style="color:var(--brand-primary);">*</span></label>
                    <input type="number" name="fiscal_year" class="form-control" style="font-family:monospace; font-size:1.2rem;" value="<?= $isEdit ? htmlspecialchars($budget->fiscal_year ?? date('Y')) : date('Y') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['amt'] ?> (<?= $currency ?>) <span style="color:var(--brand-primary);">*</span></label>
                    <input type="number" step="0.01" min="0" name="total_amount" class="form-control" style="font-family:monospace; font-weight:900; font-size:1.2rem; color:#059669;" value="<?= $isEdit ? htmlspecialchars($budget->total_amount ?? '0.00') : '0.00' ?>" required>
                </div>
                <?php if(!empty($branches)): ?>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="input-label"><?= $t['branch'] ?></label>
                    <select name="branch_id" class="form-control">
                        <option value="0"><?= $t['general_branch'] ?></option>
                        <?php foreach($branches as $b): 
                            $bName = $isRtl ? $b->name_ar : ($b->name_en ?: $b->name_ar);
                        ?>
                            <option value="<?= $b->id ?>" <?= ($isEdit && ($budget->branch_id ?? 0) == $b->id) ? 'selected' : '' ?>><?= htmlspecialchars($bName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-fill ph-link"></i> <?= $t['sec_link'] ?></h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label"><?= $t['acc'] ?></label>
                    <select name="account_id" class="form-control">
                        <option value=""><?= $t['acc_null'] ?></option>
                        <?php foreach($accounts as $acc): 
                            $aName = $isRtl ? $acc->name_ar : ($acc->name_en ?: $acc->name_ar);
                        ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && ($budget->account_id ?? '') == $acc->id) ? 'selected' : '' ?>><?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($aName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['cc'] ?></label>
                    <select name="cost_center_id" class="form-control">
                        <option value=""><?= $t['cc_null'] ?></option>
                        <?php foreach($costCenters as $cc): 
                            $cName = $isRtl ? $cc->name_ar : ($cc->name_en ?: $cc->name_ar);
                        ?>
                            <option value="<?= $cc->id ?>" <?= ($isEdit && ($budget->cost_center_id ?? '') == $cc->id) ? 'selected' : '' ?>><?= htmlspecialchars($cc->code) ?> - <?= htmlspecialchars($cName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-fill ph-sliders-horizontal"></i> <?= $t['sec_settings'] ?></h3>
            <div class="toggle-card">
                <div class="toggle-info">
                    <h4><?= $t['is_active'] ?></h4>
                    <p><?= $t['is_active_desc'] ?></p>
                </div>
                <label class="switch">
                    <input type="checkbox" name="is_active" value="1" <?= (!$isEdit || !empty($budget->is_active)) ? 'checked' : '' ?>>
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/accounting/budgets" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>