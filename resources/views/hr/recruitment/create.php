<?php
// Path: resources/views/hr/recruitment/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($applicant) && $applicant !== null && !empty($applicant->id);
$actionUrl = $isEdit ? "/ERP/hr/recruitment/{$applicant->id}/update" : "/ERP/hr/recruitment/store";
?>

<style>
    :root { 
        --c-rec: #db2777; 
        --c-rec-dark: #be185d; 
        --c-rec-light: #fce7f3;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-rec); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-rec); font-size: 1.4rem; padding: 8px; background: var(--c-rec-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-rec), var(--c-rec-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/recruitment" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل بيانات المتقدم' : 'تسجيل طلب توظيف جديد' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">إدخال بيانات المرشح، الوظيفة المستهدفة والموعد.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-user-plus"></i> البيانات الشخصية للمرشح</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">كود الطلب <span style="color:red">*</span></label>
                    <input type="text" name="applicant_code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-rec-dark);" value="<?= $isEdit ? htmlspecialchars($applicant->applicant_code) : htmlspecialchars($autoCode) ?>" required readonly>
                </div>
                <div class="form-group">
                    <label class="input-label">اسم المرشح بالكامل <span style="color:red">*</span></label>
                    <input type="text" name="candidate_name" class="form-control" value="<?= $isEdit ? htmlspecialchars($applicant->candidate_name) : '' ?>" placeholder="الاسم الثلاثي أو الرباعي..." required>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control" value="<?= $isEdit ? htmlspecialchars($applicant->email ?? '') : '' ?>" placeholder="candidate@email.com">
                </div>
                <div class="form-group">
                    <label class="input-label">رقم الجوال / الهاتف</label>
                    <input type="text" name="phone" class="form-control" value="<?= $isEdit ? htmlspecialchars($applicant->phone ?? '') : '' ?>" placeholder="05XXXXXXXX">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-briefcase"></i> التفاصيل الوظيفية والمقابلة</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">الإدارة المستهدفة</label>
                    <select name="department_id" class="form-control">
                        <option value="">-- اختر الإدارة --</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?= $dept->id ?>" <?= ($isEdit && $applicant->department_id == $dept->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept->code) ?> - <?= htmlspecialchars($dept->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">المسمى الوظيفي المستهدف</label>
                    <select name="designation_id" class="form-control">
                        <option value="">-- اختر المسمى --</option>
                        <?php foreach($designations as $dsg): ?>
                            <option value="<?= $dsg->id ?>" <?= ($isEdit && $applicant->designation_id == $dsg->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dsg->code) ?> - <?= htmlspecialchars($dsg->title_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">سنوات الخبرة</label>
                    <input type="number" min="0" name="experience_years" class="form-control" style="font-family:monospace;" value="<?= $isEdit ? htmlspecialchars($applicant->experience_years) : '0' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label">الراتب المتوقع</label>
                    <input type="number" step="0.01" min="0" name="expected_salary" class="form-control" style="font-family:monospace; font-weight:bold; color:#059669;" value="<?= $isEdit ? htmlspecialchars($applicant->expected_salary) : '0.00' ?>" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ المقابلة المخطط</label>
                    <input type="date" name="interview_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($applicant->interview_date ?? '') : '' ?>">
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">حالة الطلب <span style="color:red">*</span></label>
                <select name="status" class="form-control" required>
                    <option value="applied" <?= (!$isEdit || $applicant->status === 'applied') ? 'selected' : '' ?>>طلب جديد (Applied)</option>
                    <option value="interviewed" <?= ($isEdit && $applicant->status === 'interviewed') ? 'selected' : '' ?>>تمت المقابلة (Interviewed)</option>
                    <option value="offered" <?= ($isEdit && $applicant->status === 'offered') ? 'selected' : '' ?>>عرض وظيفي (Job Offered)</option>
                    <option value="hired" <?= ($isEdit && $applicant->status === 'hired') ? 'selected' : '' ?>>تم التوظيف (Hired)</option>
                    <option value="rejected" <?= ($isEdit && $applicant->status === 'rejected') ? 'selected' : '' ?>>مرفوض (Rejected)</option>
                </select>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">ملاحظات وتقييم المقابلة</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="ملخص المهارات، نتائج المقابلة الشخصية..."><?= $isEdit ? htmlspecialchars($applicant->notes ?? '') : '' ?></textarea>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/hr/recruitment" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث الطلب' : 'حفظ وتسجيل الطلب' ?></button>
        </div>
    </form>
</div>