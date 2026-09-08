<?php
// Path: resources/views/accounting/fixed_assets/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$isEdit = isset($asset) && $asset !== null && !empty($asset->id);
$actionUrl = $isEdit ? "/ERP/accounting/fixed-assets/{$asset->id}/update" : "/ERP/accounting/fixed-assets/store";

$t = [
    'ar' => [
        'title_new' => 'تعريف أصل ثابت جديد', 'title_edit' => 'تعديل بيانات الأصل', 'desc' => 'إدخال التكلفة التاريخية، العمر الإنتاجي، وحسابات الربط الدفتري.',
        'sec_basic' => 'البيانات العامة للأصل', 'code' => 'كود الأصل', 'name_ar' => 'اسم الأصل (عربي)', 'name_en' => 'اسم الأصل (إنجليزي)',
        'cat' => 'تصنيف الأصل', 'cat_buildings' => 'مباني وعقارات', 'cat_vehicles' => 'سيارات ووسائل نقل', 'cat_equipment' => 'آلات ومعدات', 'cat_furniture' => 'أثاث وتجهيزات', 'cat_it' => 'أجهزة حاسب وآليات', 'cat_general' => 'أصل عام',
        'branch' => 'الفرع / الموقع', 'general_branch' => '-- عام (الشركة) --',
        'sec_dep' => 'بيانات الشراء وحساب الإهلاك', 'date' => 'تاريخ الشراء', 'cost' => 'التكلفة التاريخية', 'salvage' => 'القيمة التخريدية (الخردة)',
        'life' => 'العمر الإنتاجي (سنوات)', 'method' => 'طريقة الإهلاك', 'method_sl' => 'قسط ثابت (Straight Line)',
        'cc' => 'مركز التكلفة المربوط', 'cc_null' => '-- بدون مركز تكلفة --',
        'sec_acc' => 'شجرة الحسابات المرتبطة (الربط الدفتري)', 'acc_ast' => 'حساب الأصل في الميزانية', 'acc_dep_acc' => 'حساب مجمع الإهلاك (دائن)', 'acc_dep_exp' => 'حساب مصروف الإهلاك (مدين)', 'acc_null' => '-- اختر الحساب --',
        'cancel' => 'إلغاء وتراجع', 'save' => 'تسجيل الأصل', 'update' => 'تحديث الأصل'
    ],
    'en' => [
        'title_new' => 'Register New Asset', 'title_edit' => 'Edit Asset Data', 'desc' => 'Enter historical cost, useful life, and GL linking accounts.',
        'sec_basic' => 'General Information', 'code' => 'Asset Code', 'name_ar' => 'Asset Name (AR)', 'name_en' => 'Asset Name (EN)',
        'cat' => 'Asset Category', 'cat_buildings' => 'Buildings & Real Estate', 'cat_vehicles' => 'Vehicles', 'cat_equipment' => 'Machinery & Equipment', 'cat_furniture' => 'Furniture & Fixtures', 'cat_it' => 'IT Hardware', 'cat_general' => 'General Asset',
        'branch' => 'Branch / Location', 'general_branch' => '-- General (Company) --',
        'sec_dep' => 'Purchase & Depreciation Info', 'date' => 'Purchase Date', 'cost' => 'Historical Cost', 'salvage' => 'Salvage Value',
        'life' => 'Useful Life (Years)', 'method' => 'Depreciation Method', 'method_sl' => 'Straight Line',
        'cc' => 'Linked Cost Center', 'cc_null' => '-- No Cost Center --',
        'sec_acc' => 'GL Accounts Linking', 'acc_ast' => 'Asset Account (Balance Sheet)', 'acc_dep_acc' => 'Accumulated Depr. Account (Cr)', 'acc_dep_exp' => 'Depreciation Exp. Account (Dr)', 'acc_null' => '-- Select Account --',
        'cancel' => 'Cancel', 'save' => 'Register Asset', 'update' => 'Update Asset'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --brand-primary: #0d9488; --brand-primary-dark: #0f766e; --brand-primary-light: #ccfbf1; 
        --surface: #ffffff; --surface-hover: #f8fafc; --bg-body: #f1f5f9;
        --text-main: #0f172a; --text-muted: #64748b; --border-color: #e2e8f0;
        --radius-lg: 24px; --radius-md: 16px;
        --shadow-soft: 0 10px 30px -10px rgba(15, 23, 42, 0.08);
        --shadow-hover: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
    }
    .form-wrapper { max-width: 1000px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: var(--bg-body); }
    
    .glass-header { position: sticky; top: 0; z-index: 50; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); padding: 24px 30px; margin-bottom: 30px; border-bottom: 1px solid rgba(226, 232, 240, 0.6); display: flex; align-items: center; justify-content: space-between; }
    .back-btn { width: 48px; height: 48px; border-radius: 14px; background: var(--surface); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--text-muted); font-size: 1.3rem; transition: 0.3s; box-shadow: var(--shadow-soft);}
    .back-btn:hover { background: var(--brand-primary-light); color: var(--brand-primary); border-color: #5eead4; transform: translateX(<?= $isRtl ? '4px' : '-4px' ?>); }

    .form-section { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 32px; margin: 0 30px 30px 30px; border-top: 5px solid var(--brand-primary); box-shadow: var(--shadow-soft); transition:0.3s;}
    .form-section:hover { box-shadow: var(--shadow-hover); }
    .section-title { font-size: 1.15rem; font-weight: 900; color: var(--text-main); margin: 0 0 24px 0; border-bottom: 2px dashed var(--border-color); padding-bottom: 14px; display: flex; align-items: center; gap: 10px;}
    .section-title i { color: var(--brand-primary); font-size: 1.5rem; padding: 8px; background: var(--brand-primary-light); border-radius: 10px; }
    
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
    @media(max-width:768px) { .grid-3 { grid-template-columns: 1fr; } }
    
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: var(--text-muted); }
    .form-control { width: 100%; padding: 14px 18px; border: 1px solid #cbd5e1; border-radius: 12px; background: var(--surface-hover); font-weight: 700; color: var(--text-main); font-family:inherit; transition: 0.3s; box-sizing: border-box;}
    .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.15); outline: none; background: var(--surface);}
    
    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(16px); border-top: 1px solid var(--border-color); padding: 18px 30px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; box-shadow: 0 -10px 30px rgba(0,0,0,0.05);}
    .btn-submit { background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 900; font-size: 1rem; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 8px 20px rgba(13, 148, 136, 0.25); transition: 0.3s; }
    .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(13, 148, 136, 0.35); }
    .btn-cancel { background: var(--surface); border: 1px solid #cbd5e1; color: #475569; padding: 14px 32px; border-radius: 12px; font-weight: 900; font-size: 1rem; text-decoration: none; transition: 0.3s;}
    .btn-cancel:hover { background: var(--surface-hover); color: var(--text-main); border-color: #94a3b8; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="glass-header">
        <div style="display:flex; align-items:center; gap:18px;">
            <a href="/ERP/accounting/fixed-assets" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
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
            <h3 class="section-title"><i class="ph-fill ph-armchair"></i> <?= $t['sec_basic'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['code'] ?> <span style="color:var(--brand-danger);">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; font-size:1.2rem; color:var(--brand-primary-dark);" value="<?= $isEdit ? htmlspecialchars($asset->code ?? '') : '' ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['name_ar'] ?> <span style="color:var(--brand-danger);">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($asset->name_ar ?? '') : '' ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['name_en'] ?></label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($asset->name_en ?? '') : '' ?>">
                </div>
            </div>

            <div class="grid-3" style="margin-top: 24px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['cat'] ?></label>
                    <select name="category" class="form-control">
                        <?php $cat = $asset->category ?? ''; ?>
                        <option value="general" <?= $cat==='general'?'selected':'' ?>><?= $t['cat_general'] ?></option>
                        <option value="buildings" <?= $cat==='buildings'?'selected':'' ?>><?= $t['cat_buildings'] ?></option>
                        <option value="vehicles" <?= $cat==='vehicles'?'selected':'' ?>><?= $t['cat_vehicles'] ?></option>
                        <option value="equipment" <?= $cat==='equipment'?'selected':'' ?>><?= $t['cat_equipment'] ?></option>
                        <option value="furniture" <?= $cat==='furniture'?'selected':'' ?>><?= $t['cat_furniture'] ?></option>
                        <option value="it_hardware" <?= $cat==='it_hardware'?'selected':'' ?>><?= $t['cat_it'] ?></option>
                    </select>
                </div>
                <?php if(!empty($branches)): ?>
                <div class="form-group">
                    <label class="input-label"><?= $t['branch'] ?></label>
                    <select name="branch_id" class="form-control">
                        <option value="0"><?= $t['general_branch'] ?></option>
                        <?php foreach($branches as $b): 
                            $bName = $isRtl ? $b->name_ar : ($b->name_en ?: $b->name_ar);
                        ?>
                            <option value="<?= $b->id ?>" <?= ($isEdit && ($asset->branch_id ?? 0) == $b->id) ? 'selected' : '' ?>><?= htmlspecialchars($bName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-fill ph-calculator"></i> <?= $t['sec_dep'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['date'] ?> <span style="color:var(--brand-danger);">*</span></label>
                    <input type="date" name="purchase_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($asset->purchase_date ?? '') : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['cost'] ?> (<?= $currency ?>) <span style="color:var(--brand-danger);">*</span></label>
                    <input type="number" step="0.01" min="0" name="purchase_cost" class="form-control" style="font-family:monospace; font-weight:900; font-size:1.15rem;" value="<?= $isEdit ? htmlspecialchars($asset->purchase_cost ?? '0.00') : '0.00' ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['salvage'] ?> (<?= $currency ?>)</label>
                    <input type="number" step="0.01" min="0" name="salvage_value" class="form-control" style="font-family:monospace; font-weight:900; font-size:1.15rem;" value="<?= $isEdit ? htmlspecialchars($asset->salvage_value ?? '0.00') : '0.00' ?>">
                </div>
            </div>

            <div class="grid-3" style="margin-top:24px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['life'] ?> <span style="color:var(--brand-danger);">*</span></label>
                    <input type="number" name="useful_life_years" class="form-control" value="<?= $isEdit ? htmlspecialchars($asset->useful_life_years ?? '5') : '5' ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['method'] ?></label>
                    <select name="depreciation_method" class="form-control">
                        <option value="straight_line"><?= $t['method_sl'] ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['cc'] ?></label>
                    <select name="cost_center_id" class="form-control">
                        <option value=""><?= $t['cc_null'] ?></option>
                        <?php foreach($costCenters as $cc): 
                            $cName = $isRtl ? $cc->name_ar : ($cc->name_en ?: $cc->name_ar);
                        ?>
                            <option value="<?= $cc->id ?>" <?= ($isEdit && ($asset->cost_center_id ?? '') == $cc->id) ? 'selected' : '' ?>><?= htmlspecialchars($cc->code) ?> - <?= htmlspecialchars($cName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-fill ph-tree-structure"></i> <?= $t['sec_acc'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['acc_ast'] ?> <span style="color:var(--brand-danger);">*</span></label>
                    <select name="asset_account_id" class="form-control" required>
                        <option value=""><?= $t['acc_null'] ?></option>
                        <?php foreach($accounts as $acc): 
                            $aName = $isRtl ? $acc->name_ar : ($acc->name_en ?: $acc->name_ar);
                        ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && ($asset->asset_account_id ?? '') == $acc->id) ? 'selected' : '' ?>><?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($aName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['acc_dep_acc'] ?> <span style="color:var(--brand-danger);">*</span></label>
                    <select name="acc_dep_account_id" class="form-control" required>
                        <option value=""><?= $t['acc_null'] ?></option>
                        <?php foreach($accounts as $acc): 
                            $aName = $isRtl ? $acc->name_ar : ($acc->name_en ?: $acc->name_ar);
                        ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && ($asset->acc_dep_account_id ?? '') == $acc->id) ? 'selected' : '' ?>><?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($aName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['acc_dep_exp'] ?> <span style="color:var(--brand-danger);">*</span></label>
                    <select name="dep_expense_account_id" class="form-control" required>
                        <option value=""><?= $t['acc_null'] ?></option>
                        <?php foreach($accounts as $acc): 
                            $aName = $isRtl ? $acc->name_ar : ($acc->name_en ?: $acc->name_ar);
                        ?>
                            <option value="<?= $acc->id ?>" <?= ($isEdit && ($asset->dep_expense_account_id ?? '') == $acc->id) ? 'selected' : '' ?>><?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($aName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/accounting/fixed-assets" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>