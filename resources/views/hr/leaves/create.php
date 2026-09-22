<?php
// Path: resources/views/hr/leaves/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title_new' => 'تقديم طلب إجازة جديد',
        'desc' => 'إدخال تفاصيل الإجازة، المدة، والسبب للاعتماد.', 'panel_basic' => 'تفاصيل طلب الإجازة',
        'emp' => 'الموظف صاحب الطلب', 'emp_null' => '-- اختر الموظف --',
        'type' => 'نوع الإجازة', 'start' => 'تاريخ بدء الإجازة', 'end' => 'تاريخ نهاية الإجازة',
        'reason' => 'سبب الإجازة والتفاصيل', 'reason_ph' => 'توضيح أسباب الطلب إن وجد...',
        'type_annual' => 'إجازة سنوية (Annual)', 'type_sick' => 'إجازة مرضية (Sick)',
        'type_unpaid' => 'بدون راتب (Unpaid)', 'type_maternity' => 'إجازة وضع/أمومة (Maternity)',
        'type_other' => 'إجازة أخرى (Other)',
        'cancel' => 'إلغاء وتراجع', 'send' => 'إرسال الطلب للاعتماد', 'active_scope' => 'الفرع النشط:'
    ],
    'en' => [
        'title_new' => 'New Leave Request',
        'desc' => 'Submit leave details, period, and reasons for approval.', 'panel_basic' => 'Leave Request Details',
        'emp' => 'Applicant Employee', 'emp_null' => '-- Select Employee --',
        'type' => 'Leave Type', 'start' => 'Leave Start Date', 'end' => 'Leave End Date',
        'reason' => 'Reason & Additional Notes', 'reason_ph' => 'Explain leave reason if any...',
        'type_annual' => 'Annual Leave', 'type_sick' => 'Sick Leave',
        'type_unpaid' => 'Unpaid Leave', 'type_maternity' => 'Maternity Leave',
        'type_other' => 'Other Leave',
        'cancel' => 'Cancel', 'send' => 'Submit Request for Approval', 'active_scope' => 'Active Branch:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-leave: #7c3aed; 
        --c-leave-dark: #6d28d9; 
        --c-leave-light: #f3e8ff;
        --c-border: #cbd5e1; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text); margin-bottom: 20px; }

    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-leave); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-leave); font-size: 1.4rem; padding: 8px; background: var(--c-leave-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    @media(max-width:768px) { .grid-2 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--c-leave); background: #ffffff; box-shadow: 0 0 0 4px var(--c-leave-light); }

    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-leave), var(--c-leave-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/leaves" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $t['title_new'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-leave);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-leave); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="/ERP/hr/leaves/store" method="POST">
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-airplane-takeoff"></i> <?= $t['panel_basic'] ?></h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label"><?= $t['emp'] ?> <span style="color:red">*</span></label>
                    <select name="employee_id" class="form-control" required>
                        <option value=""><?= $t['emp_null'] ?></option>
                        <?php foreach($employees ?? [] as $emp): 
                            $empName = $isRtl ? ($emp->name_ar ?? '') : ($emp->name_en ?: ($emp->name_ar ?? ''));
                        ?>
                            <option value="<?= $emp->id ?>">
                                <?= htmlspecialchars((string)$emp->emp_code) ?> - <?= htmlspecialchars((string)$empName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['type'] ?> <span style="color:red">*</span></label>
                    <select name="leave_type" class="form-control" required>
                        <option value="annual"><?= $t['type_annual'] ?></option>
                        <option value="sick"><?= $t['type_sick'] ?></option>
                        <option value="unpaid"><?= $t['type_unpaid'] ?></option>
                        <option value="maternity"><?= $t['type_maternity'] ?></option>
                        <option value="other"><?= $t['type_other'] ?></option>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['start'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['end'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label"><?= $t['reason'] ?></label>
                <textarea name="reason" class="form-control" rows="3" placeholder="<?= $t['reason_ph'] ?>"></textarea>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/hr/leaves" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-paper-plane-tilt"></i> <?= $t['send'] ?></button>
        </div>
    </form>
</div>