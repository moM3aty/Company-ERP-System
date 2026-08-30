<?php
// Path: resources/views/hr/documents/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($document) && $document !== null && !empty($document->id);
$actionUrl = $isEdit ? "/ERP/hr/documents/{$document->id}/update" : "/ERP/hr/documents/store";
?>

<style>
    :root { 
        --c-doc: #0f766e; 
        --c-doc-dark: #115e59; 
        --c-doc-light: #ccfbf1;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-doc); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-doc); font-size: 1.4rem; padding: 8px; background: var(--c-doc-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-doc), var(--c-doc-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/documents" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل بيانات الوثيقة' : 'أرشفة ورفع وثيقة جديدة' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">إدخال المستندات، تواريخ الصلاحية، وربطها بالموظف.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST" enctype="multipart/form-data">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-folder-user"></i> البيانات الأساسية للمستند</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">كود الوثيقة <span style="color:red">*</span></label>
                    <input type="text" name="document_code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-doc-dark);" value="<?= $isEdit ? htmlspecialchars((string)$document->document_code) : htmlspecialchars((string)$autoCode) ?>" required readonly>
                </div>
                <div class="form-group">
                    <label class="input-label">الموظف المعني <span style="color:red">*</span></label>
                    <select name="employee_id" class="form-control" required>
                        <option value="">-- اختر الموظف --</option>
                        <?php foreach($employees as $emp): ?>
                            <option value="<?= $emp->id ?>" <?= ($isEdit && $document->employee_id == $emp->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$emp->emp_code) ?> - <?= htmlspecialchars((string)$emp->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">عنوان / مسمى الوثيقة <span style="color:red">*</span></label>
                    <input type="text" name="title_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)$document->title_ar) : '' ?>" placeholder="مثال: جواز سفر الموظف / شهادة التخرج..." required>
                </div>
                <div class="form-group">
                    <label class="input-label">نوع الوثيقة <span style="color:red">*</span></label>
                    <select name="document_type" class="form-control" required>
                        <option value="passport" <?= ($isEdit && $document->document_type === 'passport') ? 'selected' : '' ?>>جواز سفر (Passport)</option>
                        <option value="national_id" <?= ($isEdit && $document->document_type === 'national_id') ? 'selected' : '' ?>>هوية / إقامة (National ID / Residency)</option>
                        <option value="contract" <?= ($isEdit && $document->document_type === 'contract') ? 'selected' : '' ?>>عقد عمل (Employment Contract)</option>
                        <option value="certificate" <?= ($isEdit && $document->document_type === 'certificate') ? 'selected' : '' ?>>شهادة علمية / خبرة (Certificate)</option>
                        <option value="visa" <?= ($isEdit && $document->document_type === 'visa') ? 'selected' : '' ?>>تأشيرة / إذن عمل (Visa)</option>
                        <option value="other" <?= ($isEdit && $document->document_type === 'other') ? 'selected' : '' ?>>وثيقة أخرى (Other)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-calendar"></i> الصلاحية والملف المرفق</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">تاريخ الإصدار</label>
                    <input type="date" name="issue_date" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($document->issue_date ?? '')) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ الانتهاء</label>
                    <input type="date" name="expiry_date" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($document->expiry_date ?? '')) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label">حالة الصلاحية <span style="color:red">*</span></label>
                    <select name="status" class="form-control" required>
                        <option value="active" <?= (!$isEdit || $document->status === 'active') ? 'selected' : '' ?>>سارية المفعول (Active)</option>
                        <option value="pending_renewal" <?= ($isEdit && $document->status === 'pending_renewal') ? 'selected' : '' ?>>قيد التجديد (Pending Renewal)</option>
                        <option value="expired" <?= ($isEdit && $document->status === 'expired') ? 'selected' : '' ?>>منتهية الصلاحية (Expired)</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">رفع ملف المرفق (PDF, PNG, JPG)</label>
                <input type="file" name="file" class="form-control" accept=".pdf,.png,.jpg,.jpeg">
                <?php if($isEdit && !empty($document->file_path)): ?>
                    <div style="margin-top:6px; font-size:0.85rem;"><a href="/ERP<?= htmlspecialchars((string)$document->file_path) ?>" target="_blank" style="color:var(--c-doc); font-weight:bold;"><i class="ph-bold ph-paperclip"></i> معاينة الملف الحالي المرفوع</a></div>
                <?php endif; ?>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">ملاحظات وشروط إضافية</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="ملاحظات الأرشفة..."><?= $isEdit ? htmlspecialchars((string)($document->notes ?? '')) : '' ?></textarea>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/hr/documents" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث الوثيقة' : 'حفظ وأرشفة الوثيقة' ?></button>
        </div>
    </form>
</div>