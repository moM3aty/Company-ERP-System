<?php
// Path: resources/views/accounting/cost_centers/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$isEdit = isset($center) && $center !== null && !empty($center->id);
$actionUrl = $isEdit ? "/ERP/accounting/cost-centers/{$center->id}/update" : "/ERP/accounting/cost-centers/store";

$t = [
    'ar' => [
        'title_new' => 'إضافة مركز تكلفة جديد', 'title_edit' => 'تعديل مركز التكلفة', 'desc' => 'تعريف بيانات ووظيفة مركز التكلفة والموازنة المخصصة له.',
        'sec_basic' => 'البيانات التعريفية', 'code' => 'رمز الكود المالي', 'parent' => 'المركز الرئيسي (Parent)', 'parent_null' => '-- مركز رئيسي مستقل --',
        'name_ar' => 'اسم المركز (عربي)', 'name_en' => 'اسم المركز (إنجليزي)',
        'branch' => 'الفرع المخصص', 'general_branch' => '-- مركز عام (كل الفروع) --',
        'budget' => 'الموازنة التقديرية المخصصة',
        'sec_settings' => 'خصائص وإعدادات المركز', 'is_parent' => 'مركز تجميعي (رئيسي)', 'is_parent_desc' => 'تفعيل هذا الخيار يعني احتواء المركز لفرعيات أخرى.',
        'is_active' => 'حالة المركز (نشط)', 'is_active_desc' => 'السماح بربط التكاليف والفواتير بالمركز.',
        'cancel' => 'إلغاء وتراجع', 'save' => 'حفظ مركز التكلفة', 'update' => 'تحديث البيانات'
    ],
    'en' => [
        'title_new' => 'Add New Cost Center', 'title_edit' => 'Edit Cost Center', 'desc' => 'Configure cost center details and budget allocations.',
        'sec_basic' => 'Basic Information', 'code' => 'Cost Center Code', 'parent' => 'Parent Center', 'parent_null' => '-- Independent Root Center --',
        'name_ar' => 'Name (AR)', 'name_en' => 'Name (EN)',
        'branch' => 'Assigned Branch', 'general_branch' => '-- General Center (All Branches) --',
        'budget' => 'Allocated Budget',
        'sec_settings' => 'Settings & Properties', 'is_parent' => 'Parent (Control) Center', 'is_parent_desc' => 'Enabling this makes it a container for sub-centers.',
        'is_active' => 'Active Status', 'is_active_desc' => 'Allow linking transactions to this center.',
        'cancel' => 'Cancel', 'save' => 'Save Cost Center', 'update' => 'Update Data'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --brand-primary: #7c3aed; --brand-primary-dark: #6d28d9; --brand-primary-light: #f5f3ff; 
        --surface: #ffffff; --surface-hover: #f8fafc; --bg-body: #f1f5f9;
        --text-main: #0f172a; --text-muted: #64748b; --border-color: #e2e8f0;
        --radius-lg: 24px; --radius-md: 16px;
        --shadow-soft: 0 10px 30px -10px rgba(15, 23, 42, 0.08);
        --shadow-hover: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
    }
    .form-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; background: var(--bg-body); }
    
    .glass-header { position: sticky; top: 0; z-index: 50; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); padding: 24px 30px; margin-bottom: 30px; border-bottom: 1px solid rgba(226, 232, 240, 0.6); display: flex; align-items: center; justify-content: space-between; }
    .back-btn { width: 48px; height: 48px; border-radius: 14px; background: var(--surface); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--text-muted); font-size: 1.3rem; transition: 0.3s; box-shadow: var(--shadow-soft);}
    .back-btn:hover { background: var(--brand-primary-light); color: var(--brand-primary); border-color: #ddd6fe; transform: translateX(<?= $isRtl ? '4px' : '-4px' ?>); }

    .form-section { background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 32px; margin: 0 30px 30px 30px; border-top: 5px solid var(--brand-primary); box-shadow: var(--shadow-soft); transition:0.3s;}
    .form-section:hover { box-shadow: var(--shadow-hover); }
    .section-title { font-size: 1.15rem; font-weight: 900; color: var(--text-main); margin: 0 0 24px 0; border-bottom: 2px dashed var(--border-color); padding-bottom: 14px; display: flex; align-items: center; gap: 10px;}
    .section-title i { color: var(--brand-primary); font-size: 1.5rem; padding: 8px; background: var(--brand-primary-light); border-radius: 10px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    @media(max-width:768px) { .grid-2 { grid-template-columns: 1fr; } }
    
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: var(--text-muted); }
    .form-control { width: 100%; padding: 14px 18px; border: 1px solid #cbd5e1; border-radius: 12px; background: var(--surface-hover); font-weight: 700; color: var(--text-main); font-family:inherit; transition: 0.3s; box-sizing: border-box;}
    .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.15); outline: none; background: var(--surface);}
    
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
    .btn-submit { background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 900; font-size: 1rem; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 8px 20px rgba(124, 58, 237, 0.25); transition: 0.3s; }
    .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(124, 58, 237, 0.35); }
    .btn-cancel { background: var(--surface); border: 1px solid #cbd5e1; color: #475569; padding: 14px 32px; border-radius: 12px; font-weight: 900; font-size: 1rem; text-decoration: none; transition: 0.3s;}
    .btn-cancel:hover { background: var(--surface-hover); color: var(--text-main); border-color: #94a3b8; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="glass-header">
        <div style="display:flex; align-items:center; gap:18px;">
            <a href="/ERP/accounting/cost-centers" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
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
            <h3 class="section-title"><i class="ph-fill ph-info"></i> <?= $t['sec_basic'] ?></h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label"><?= $t['code'] ?> <span style="color:var(--brand-primary);">*</span></label>
                    <input type="text" name="code" class="form-control" style="font-family:monospace; font-size:1.2rem; color:var(--brand-primary-dark);" value="<?= $isEdit ? htmlspecialchars($center->code ?? '') : '' ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['parent'] ?></label>
                    <select name="parent_id" class="form-control">
                        <option value=""><?= $t['parent_null'] ?></option>
                        <?php foreach($parentCenters ?? [] as $pc): 
                            $pcNameEn = property_exists($pc, 'name_en') && !empty($pc->name_en) ? $pc->name_en : ($pc->name_ar ?? '');
                            $pcName = $isRtl ? ($pc->name_ar ?? '') : $pcNameEn;
                        ?>
                            <option value="<?= $pc->id ?>" <?= ($isEdit && ($center->parent_id ?? '') == $pc->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pc->code ?? '') ?> - <?= htmlspecialchars($pcName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top: 24px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['name_ar'] ?> <span style="color:var(--brand-primary);">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($center->name_ar ?? '') : '' ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['name_en'] ?></label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($center->name_en ?? '') : '' ?>">
                </div>
            </div>

            <div class="grid-2" style="margin-top: 24px;">
                <?php if(!empty($branches)): ?>
                <div class="form-group">
                    <label class="input-label"><?= $t['branch'] ?></label>
                    <select name="branch_id" class="form-control">
                        <option value="0"><?= $t['general_branch'] ?></option>
                        <?php foreach($branches as $b): 
                            $bName = $isRtl ? $b->name_ar : ($b->name_en ?: $b->name_ar);
                        ?>
                            <option value="<?= $b->id ?>" <?= ($isEdit && ($center->branch_id ?? 0) == $b->id) ? 'selected' : '' ?>><?= htmlspecialchars($bName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-group" style="<?= empty($branches) ? 'grid-column: span 2;' : '' ?>">
                    <label class="input-label"><?= $t['budget'] ?> (<?= $currency ?>)</label>
                    <input type="number" step="0.01" min="0" name="budget_amount" class="form-control" style="font-family:monospace; font-weight:900; font-size:1.15rem;" value="<?= $isEdit ? ($center->budget_amount ?? '0.00') : '0.00' ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-fill ph-sliders-horizontal"></i> <?= $t['sec_settings'] ?></h3>
            <div class="grid-2">
                <div class="toggle-card">
                    <div class="toggle-info">
                        <h4><?= $t['is_parent'] ?></h4>
                        <p><?= $t['is_parent_desc'] ?></p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="is_parent" value="1" <?= ($isEdit && !empty($center->is_parent)) ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                
                <div class="toggle-card">
                    <div class="toggle-info">
                        <h4><?= $t['is_active'] ?></h4>
                        <p><?= $t['is_active_desc'] ?></p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="is_active" value="1" <?= (!$isEdit || !empty($center->is_active)) ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/accounting/cost-centers" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>