<?php
// Path: resources/views/hr/employees/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$isEdit = isset($employee) && $employee !== null && !empty($employee->id);
$actionUrl = $isEdit ? "/ERP/hr/employees/{$employee->id}/update" : "/ERP/hr/employees/store";
?>

<style>
    :root { 
        --c-emp: #2563eb; 
        --c-emp-dark: #1d4ed8; 
        --c-emp-light: #dbeafe;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-emp); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-emp); font-size: 1.4rem; padding: 8px; background: var(--c-emp-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-emp), var(--c-emp-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/employees" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? 'تعديل بيانات الموظف' : 'تسجيل موظف جديد' ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">إدخال البيانات الشخصية والوظيفية والمالية للكادر.</p>
            </div>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-user"></i> البيانات الشخصية والتعريفية</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">كود الموظف <span style="color:red">*</span></label>
                    <input type="text" name="emp_code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-emp-dark);" value="<?= $isEdit ? htmlspecialchars($employee->emp_code) : htmlspecialchars($autoCode) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="input-label">الاسم بالكامل (عربي) <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars($employee->name_ar) : '' ?>" placeholder="الاسم الثلاثي أو الرباعي..." required>
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">الاسم بالكامل (إنجليزي)</label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars($employee->name_en ?? '') : '' ?>" placeholder="Full Name in English">
                </div>
                <div class="form-group">
                    <label class="input-label">رقم الهوية الوطنية / الإقامة</label>
                    <input type="text" name="national_id" class="form-control" value="<?= $isEdit ? htmlspecialchars($employee->national_id ?? '') : '' ?>" placeholder="10 أرقام...">
                </div>
                <div class="form-group">
                    <label class="input-label">رقم جواز السفر</label>
                    <input type="text" name="passport_no" class="form-control" value="<?= $isEdit ? htmlspecialchars($employee->passport_no ?? '') : '' ?>">
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">الجنس</label>
                    <select name="gender" class="form-control">
                        <option value="male" <?= (!$isEdit || $employee->gender === 'male') ? 'selected' : '' ?>>ذكر (Male)</option>
                        <option value="female" <?= ($isEdit && $employee->gender === 'female') ? 'selected' : '' ?>>أنثى (Female)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ الميلاد</label>
                    <input type="date" name="dob" class="form-control" value="<?= $isEdit ? htmlspecialchars($employee->dob ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label">رقم الجوال / الهاتف</label>
                    <input type="text" name="phone" class="form-control" value="<?= $isEdit ? htmlspecialchars($employee->phone ?? '') : '' ?>" placeholder="05XXXXXXXX">
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">البريد الإلكتروني الرسمي</label>
                <input type="email" name="email" class="form-control" value="<?= $isEdit ? htmlspecialchars($employee->email ?? '') : '' ?>" placeholder="employee@company.com">
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-briefcase"></i> البيانات الوظيفية والراتب</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">الإدارة التابع لها</label>
                    <select name="department_id" class="form-control">
                        <option value="">-- اختر الإدارة --</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?= $dept->id ?>" <?= ($isEdit && $employee->department_id == $dept->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept->code) ?> - <?= htmlspecialchars($dept->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">المسمى الوظيفي</label>
                    <select name="designation_id" class="form-control">
                        <option value="">-- اختر المسمى --</option>
                        <?php foreach($designations as $dsg): ?>
                            <option value="<?= $dsg->id ?>" <?= ($isEdit && $employee->designation_id == $dsg->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dsg->code) ?> - <?= htmlspecialchars($dsg->title_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">تاريخ المباشرة <span style="color:red">*</span></label>
                    <input type="date" name="joining_date" class="form-control" value="<?= $isEdit ? htmlspecialchars($employee->joining_date) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">نوع التوظيف</label>
                    <select name="employment_type" class="form-control">
                        <option value="full_time" <?= (!$isEdit || $employee->employment_type === 'full_time') ? 'selected' : '' ?>>دوام كامل (Full-Time)</option>
                        <option value="part_time" <?= ($isEdit && $employee->employment_type === 'part_time') ? 'selected' : '' ?>>دوام جزئي (Part-Time)</option>
                        <option value="contract" <?= ($isEdit && $employee->employment_type === 'contract') ? 'selected' : '' ?>>عقد محدد (Contract)</option>
                        <option value="probation" <?= ($isEdit && $employee->employment_type === 'probation') ? 'selected' : '' ?>>تحت التجربة (Probation)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">الراتب الأساسي الشهري</label>
                    <input type="number" step="0.01" min="0" name="basic_salary" class="form-control" style="font-family:monospace; font-weight:900; color:#059669; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars($employee->basic_salary) : '0.00' ?>" placeholder="0.00">
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">حالة الموظف <span style="color:red">*</span></label>
                <select name="status" class="form-control" required>
                    <option value="active" <?= (!$isEdit || $employee->status === 'active') ? 'selected' : '' ?>>على رأس العمل (Active)</option>
                    <option value="on_leave" <?= ($isEdit && $employee->status === 'on_leave') ? 'selected' : '' ?>>في إجازة (On Leave)</option>
                    <option value="resigned" <?= ($isEdit && $employee->status === 'resigned') ? 'selected' : '' ?>>مستقيل (Resigned)</option>
                    <option value="terminated" <?= ($isEdit && $employee->status === 'terminated') ? 'selected' : '' ?>>منهي خدماته (Terminated)</option>
                </select>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">عنوان السكن الإقامي</label>
                <input type="text" name="address" class="form-control" value="<?= $isEdit ? htmlspecialchars($employee->address ?? '') : '' ?>" placeholder="المدينة، الشارع...">
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/hr/employees" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? 'تحديث بيانات الموظف' : 'حفظ وتسجيل الموظف' ?></button>
        </div>
    </form>
</div>