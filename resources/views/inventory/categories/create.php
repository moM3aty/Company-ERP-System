<?php
// Path: resources/views/inventory/categories/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($category) && $category !== null;
$actionUrl = $isEdit ? "/ERP/inventory/categories/" . (int)$category->id . "/update" : "/ERP/inventory/categories/store";

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title_new' => 'إضافة فئة أصناف جديدة',
        'title_edit' => 'تعديل فئة الأصناف',
        'panel_title' => 'بيانات فئة الأصناف',
        'name_ar' => 'اسم الفئة (بالعربية)',
        'name_en' => 'اسم الفئة (بالإنجليزية)',
        'desc' => 'الوصف التفصيلي',
        'desc_ph' => 'تفاصيل إضافية حول أنواع المنتجات التي تندرج تحت هذه الفئة...',
        'cancel' => 'إلغاء وتراجع',
        'save' => 'حفظ وإضافة الفئة',
        'update' => 'تحديث البيانات',
        'active_scope' => 'الفرع النشط:'
    ],
    'en' => [
        'title_new' => 'Add New Product Category',
        'title_edit' => 'Edit Product Category',
        'panel_title' => 'Category Information',
        'name_ar' => 'Category Name (Arabic)',
        'name_en' => 'Category Name (English)',
        'desc' => 'Description',
        'desc_ph' => 'Additional details about product types under this category...',
        'cancel' => 'Cancel',
        'save' => 'Save Category',
        'update' => 'Update Category',
        'active_scope' => 'Active Branch:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-amber: #f59e0b;
        --c-amber-dark: #d97706;
        --c-amber-light: #fef3c7;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #475569;
        --c-bg: #f8fafc;
    }

    .form-wrapper { max-width: 700px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .back-btn { width: 42px; height: 42px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; color: var(--c-text-muted); text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-amber-light); color: var(--c-amber); border-color: #fcd34d; }
    .form-title { font-size: 1.6rem; font-weight: 800; color: var(--c-text-dark); margin: 0; }
    
    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 20px; }

    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 24px; border-top: 4px solid var(--c-amber); }
    .panel-title { font-size: 1.1rem; color: var(--c-text-dark); font-weight: 800; margin: 0 0 20px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 10px;}
    
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: var(--c-bg); transition: 0.2s; color: var(--c-text-dark); box-sizing: border-box; }
    .form-control:focus { border-color: var(--c-amber); box-shadow: 0 0 0 4px var(--c-amber-light); background: #ffffff; outline: none; }
    .input-label { font-size: 0.85rem; font-weight: 800; color: var(--c-text-muted); margin-bottom: 8px; display: block; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn-submit { background: linear-gradient(135deg, var(--c-amber), var(--c-amber-dark)); color: white; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25); }
    .btn-cancel { background: #ffffff; border: 1px solid var(--c-border); color: var(--c-text-muted); padding: 12px 28px; border-radius: 12px; font-weight: 800; text-decoration: none; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <a href="/ERP/inventory/categories" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <h2 class="form-title"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-amber);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-amber); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-folders" style="color:var(--c-amber);"></i> <?= $t['panel_title'] ?></h3>
            
            <div class="grid-2" style="margin-bottom: 24px;">
                <div>
                    <label class="input-label"><?= $t['name_ar'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= htmlspecialchars((string)($category->name_ar ?? '')) ?>" placeholder="مثال: أجهزة إلكترونية" required>
                </div>
                <div>
                    <label class="input-label"><?= $t['name_en'] ?></label>
                    <input type="text" name="name_en" class="form-control" value="<?= htmlspecialchars((string)($category->name_en ?? '')) ?>" placeholder="e.g. Electronics" dir="ltr">
                </div>
            </div>

            <div>
                <label class="input-label"><?= $t['desc'] ?></label>
                <textarea name="description" class="form-control" rows="4" placeholder="<?= $t['desc_ph'] ?>"><?= htmlspecialchars((string)($category->description ?? '')) ?></textarea>
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/inventory/categories" class="btn-cancel"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>