<?php
// Path: resources/views/hr/employees/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$isEdit = isset($employee) && $employee !== null && !empty($employee->id);
$actionUrl = $isEdit ? "/ERP/hr/employees/" . (int)$employee->id . "/update" : "/ERP/hr/employees/store";

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title_new' => 'تسجيل موظف جديد', 'title_edit' => 'تعديل بيانات الموظف',
        'desc' => 'إدخال البيانات الشخصية والوظيفية والمالية للكادر.', 'panel_personal' => 'البيانات الشخصية والتعريفية',
        'code' => 'كود الموظف', 'name_ar' => 'الاسم بالكامل (عربي)', 'name_en' => 'الاسم بالكامل (إنجليزي)',
        'national_id' => 'رقم الهوية الوطنية / الإقامة', 'passport' => 'رقم جواز السفر',
        'gender' => 'الجنس', 'male' => 'ذكر (Male)', 'female' => 'أنثى (Female)', 'dob' => 'تاريخ الميلاد',
        'phone' => 'رقم الجوال / الهاتف', 'email' => 'البريد الإلكتروني الرسمي',
        'panel_job' => 'البيانات الوظيفية والراتب', 'dept' => 'الإدارة التابع لها', 'dept_null' => '-- اختر الإدارة --',
        'desig' => 'المسمى الوظيفي', 'desig_null' => '-- اختر المسمى --',
        'join_date' => 'تاريخ المباشرة', 'emp_type' => 'نوع التوظيف',
        'type_full_time' => 'دوام كامل (Full-Time)', 'type_part_time' => 'دوام جزئي (Part-Time)',
        'type_contract' => 'عقد محدد (Contract)', 'type_probation' => 'تحت التجربة (Probation)',
        'salary' => "الراتب الأساسي الشهري (بـ $currency)", 'status' => 'حالة الموظف',
        'status_active' => 'على رأس العمل (Active)', 'status_on_leave' => 'في إجازة (On Leave)',
        'status_resigned' => 'مستقيل (Resigned)', 'status_terminated' => 'منهي خدماته (Terminated)',
        'address' => 'عنوان السكن الإقامي', 'cancel' => 'إلغاء وتراجع', 'save' => 'حفظ وتسجيل الموظف',
        'update' => 'تحديث بيانات الموظف', 'active_scope' => 'الفرع النشط:'
    ],
    'en' => [
        'title_new' => 'Register New Employee', 'title_edit' => 'Edit Employee Details',
        'desc' => 'Enter personal, job position, and base salary information.', 'panel_personal' => 'Personal Identification Data',
        'code' => 'Employee Code', 'name_ar' => 'Full Name (Arabic)', 'name_en' => 'Full Name (English)',
        'national_id' => 'National ID / Iqama No.', 'passport' => 'Passport Number',
        'gender' => 'Gender', 'male' => 'Male', 'female' => 'Female', 'dob' => 'Date of Birth',
        'phone' => 'Mobile / Phone Number', 'email' => 'Official Email Address',
        'panel_job' => 'Position & Financial Details', 'dept' => 'Department', 'dept_null' => '-- Select Department --',
        'desig' => 'Job Title / Designation', 'desig_null' => '-- Select Position --',
        'join_date' => 'Joining Date', 'emp_type' => 'Employment Type',
        'type_full_time' => 'Full-Time', 'type_part_time' => 'Part-Time',
        'type_contract' => 'Contract', 'type_probation' => 'Probation',
        'salary' => "Monthly Base Salary (in $currency)", 'status' => 'Employee Status',
        'status_active' => 'Active', 'status_on_leave' => 'On Leave',
        'status_resigned' => 'Resigned', 'status_terminated' => 'Terminated',
        'address' => 'Residential Address', 'cancel' => 'Cancel', 'save' => 'Save Employee',
        'update' => 'Update Employee Data', 'active_scope' => 'Active Branch:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-emp: #2563eb; 
        --c-emp-dark: #1d4ed8; 
        --c-emp-light: #dbeafe;
        --c-border: #cbd5e1; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text); margin-bottom: 20px; }

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
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--c-emp); background: #ffffff; box-shadow: 0 0 0 4px var(--c-emp-light); }

    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-emp), var(--c-emp-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/employees" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-emp);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-emp); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-user"></i> <?= $t['panel_personal'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['code'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="emp_code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-emp-dark);" value="<?= $isEdit ? htmlspecialchars((string)($employee->emp_code ?? '')) : htmlspecialchars((string)($autoCode ?? '')) ?>" required <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="input-label"><?= $t['name_ar'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="name_ar" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($employee->name_ar ?? '')) : '' ?>" required>
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['name_en'] ?></label>
                    <input type="text" name="name_en" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($employee->name_en ?? '')) : '' ?>" dir="ltr">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['national_id'] ?></label>
                    <input type="text" name="national_id" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($employee->national_id ?? '')) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['passport'] ?></label>
                    <input type="text" name="passport_no" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($employee->passport_no ?? '')) : '' ?>">
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['gender'] ?></label>
                    <select name="gender" class="form-control">
                        <?php $g = $isEdit ? ($employee->gender ?? 'male') : 'male'; ?>
                        <option value="male" <?= $g === 'male' ? 'selected' : '' ?>><?= $t['male'] ?></option>
                        <option value="female" <?= $g === 'female' ? 'selected' : '' ?>><?= $t['female'] ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['dob'] ?></label>
                    <input type="date" name="dob" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($employee->dob ?? '')) : '' ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['phone'] ?></label>
                    <input type="text" name="phone" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($employee->phone ?? '')) : '' ?>">
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label"><?= $t['email'] ?></label>
                <input type="email" name="email" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($employee->email ?? '')) : '' ?>" dir="ltr">
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-briefcase"></i> <?= $t['panel_job'] ?></h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label"><?= $t['dept'] ?></label>
                    <select name="department_id" class="form-control">
                        <option value=""><?= $t['dept_null'] ?></option>
                        <?php foreach($departments ?? [] as $dept): 
                            $deptName = $isRtl ? ($dept->name_ar ?? '') : ($dept->name_en ?: ($dept->name_ar ?? ''));
                        ?>
                            <option value="<?= $dept->id ?>" <?= ($isEdit && ($employee->department_id ?? 0) == $dept->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$dept->code) ?> - <?= htmlspecialchars((string)$deptName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['desig'] ?></label>
                    <select name="designation_id" class="form-control">
                        <option value=""><?= $t['desig_null'] ?></option>
                        <?php foreach($designations ?? [] as $dsg): 
                            $dsgTitle = $isRtl ? ($dsg->title_ar ?? '') : ($dsg->title_en ?: ($dsg->title_ar ?? ''));
                        ?>
                            <option value="<?= $dsg->id ?>" <?= ($isEdit && ($employee->designation_id ?? 0) == $dsg->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$dsg->code) ?> - <?= htmlspecialchars((string)$dsgTitle) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-3" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['join_date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="joining_date" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($employee->joining_date ?? '')) : date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['emp_type'] ?></label>
                    <select name="employment_type" class="form-control">
                        <?php $et = $isEdit ? ($employee->employment_type ?? 'full_time') : 'full_time'; ?>
                        <option value="full_time" <?= $et === 'full_time' ? 'selected' : '' ?>><?= $t['type_full_time'] ?></option>
                        <option value="part_time" <?= $et === 'part_time' ? 'selected' : '' ?>><?= $t['type_part_time'] ?></option>
                        <option value="contract" <?= $et === 'contract' ? 'selected' : '' ?>><?= $t['type_contract'] ?></option>
                        <option value="probation" <?= $et === 'probation' ? 'selected' : '' ?>><?= $t['type_probation'] ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['salary'] ?></label>
                    <input type="number" step="0.01" min="0" name="basic_salary" class="form-control" style="font-family:monospace; font-weight:900; color:#059669; font-size:1.1rem;" value="<?= $isEdit ? htmlspecialchars((string)($employee->basic_salary ?? '0.00')) : '0.00' ?>">
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label"><?= $t['status'] ?> <span style="color:red">*</span></label>
                <select name="status" class="form-control" required>
                    <?php $st = $isEdit ? ($employee->status ?? 'active') : 'active'; ?>
                    <option value="active" <?= $st === 'active' ? 'selected' : '' ?>><?= $t['status_active'] ?></option>
                    <option value="on_leave" <?= $st === 'on_leave' ? 'selected' : '' ?>><?= $t['status_on_leave'] ?></option>
                    <option value="resigned" <?= $st === 'resigned' ? 'selected' : '' ?>><?= $t['status_resigned'] ?></option>
                    <option value="terminated" <?= $st === 'terminated' ? 'selected' : '' ?>><?= $t['status_terminated'] ?></option>
                </select>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label"><?= $t['address'] ?></label>
                <input type="text" name="address" class="form-control" value="<?= $isEdit ? htmlspecialchars((string)($employee->address ?? '')) : '' ?>">
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/hr/employees" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>