<?php
// Path: resources/views/hr/payroll/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$isEdit = isset($payroll) && $payroll !== null && !empty($payroll->id);
$actionUrl = $isEdit ? "/ERP/hr/payroll/" . (int)$payroll->id . "/update" : "/ERP/hr/payroll/process";

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$months = [
    'January' => $isRtl ? 'يناير (January)' : 'January',
    'February' => $isRtl ? 'فبراير (February)' : 'February',
    'March' => $isRtl ? 'مارس (March)' : 'March',
    'April' => $isRtl ? 'أبريل (April)' : 'April',
    'May' => $isRtl ? 'مايو (May)' : 'May',
    'June' => $isRtl ? 'يونيو (June)' : 'June',
    'July' => $isRtl ? 'يوليو (July)' : 'July',
    'August' => $isRtl ? 'أغسطس (August)' : 'August',
    'September' => $isRtl ? 'سبتمبر (September)' : 'September',
    'October' => $isRtl ? 'أكتوبر (October)' : 'October',
    'November' => $isRtl ? 'نوفمبر (November)' : 'November',
    'December' => $isRtl ? 'ديسمبر (December)' : 'December'
];

$t = [
    'ar' => [
        'title_new' => 'توليد واحتساب مسير رواتب', 'title_edit' => 'تعديل بيانات مسير الرواتب',
        'desc' => 'تعديل قيم المبالغ، الخصومات وحالة صرف المسير للفرع الحالي.', 'panel_basic' => 'البيانات الأساسية للمسير',
        'code' => 'كود المسير', 'month' => 'عن شهر', 'year' => 'عن سنة',
        'status' => 'حالة المسير', 'status_processed' => 'معتمد ومحتسب (Processed)',
        'status_draft' => 'مسودة (Draft)', 'status_paid' => 'تم الصرف بالكامل (Paid)',
        'panel_numbers' => 'أرقام وتوازنات المسير المالية',
        'basic' => "إجمالي الرواتب الأساسية (بـ $currency)", 'allow' => "إجمالي البدلات (بـ $currency)",
        'deduct' => "إجمالي الاستقطاعات والخصومات (بـ $currency)",
        'cancel' => 'إلغاء وتراجع', 'save' => 'بدء الاحتساب الآلي وتوليد المسير', 'update' => 'تحديث بيانات المسير',
        'active_scope' => 'الفرع المستهدف:'
    ],
    'en' => [
        'title_new' => 'Generate Payroll Run', 'title_edit' => 'Edit Payroll Details',
        'desc' => 'Adjust amounts, deductions, and payment status for the current branch.', 'panel_basic' => 'Basic Payroll Data',
        'code' => 'Payroll Code', 'month' => 'For Month', 'year' => 'For Year',
        'status' => 'Payroll Status', 'status_processed' => 'Processed & Approved',
        'status_draft' => 'Draft', 'status_paid' => 'Fully Paid',
        'panel_numbers' => 'Financial Payroll Figures',
        'basic' => "Total Basic Salaries (in $currency)", 'allow' => "Total Allowances (in $currency)",
        'deduct' => "Total Deductions (in $currency)",
        'cancel' => 'Cancel', 'save' => 'Auto-Calculate & Generate', 'update' => 'Update Payroll',
        'active_scope' => 'Target Branch:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-pay: #be123c; 
        --c-pay-dark: #9f1239; 
        --c-pay-light: #ffe4e6;
        --c-border: #cbd5e1; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text); margin-bottom: 20px; }

    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-pay); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-pay); font-size: 1.4rem; padding: 8px; background: var(--c-pay-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--c-pay); background: #ffffff; box-shadow: 0 0 0 4px var(--c-pay-light); }

    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-pay), var(--c-pay-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/payroll" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $isEdit ? $t['title_edit'] : $t['title_new'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-pay);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-pay); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-calculator"></i> <?= $t['panel_basic'] ?></h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label"><?= $t['code'] ?> <span style="color:red">*</span></label>
                    <input type="text" name="payroll_code" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-pay-dark);" value="<?= $isEdit ? htmlspecialchars((string)($payroll->payroll_code ?? '')) : htmlspecialchars((string)($autoCode ?? '')) ?>" required readonly>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['month'] ?> <span style="color:red">*</span></label>
                    <select name="month" class="form-control" required>
                        <?php foreach($months as $mEng => $mName): ?>
                            <option value="<?= $mEng ?>" <?= (($isEdit ? ($payroll->month ?? '') : date('F')) === $mEng) ? 'selected' : '' ?>><?= $mName ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['year'] ?> <span style="color:red">*</span></label>
                    <input type="number" name="year" class="form-control" style="font-family:monospace;" value="<?= $isEdit ? htmlspecialchars((string)($payroll->year ?? date('Y'))) : date('Y') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['status'] ?> <span style="color:red">*</span></label>
                    <select name="status" class="form-control" required>
                        <?php $st = $isEdit ? ($payroll->status ?? 'processed') : 'processed'; ?>
                        <option value="processed" <?= $st === 'processed' ? 'selected' : '' ?>><?= $t['status_processed'] ?></option>
                        <option value="draft" <?= $st === 'draft' ? 'selected' : '' ?>><?= $t['status_draft'] ?></option>
                        <option value="paid" <?= $st === 'paid' ? 'selected' : '' ?>><?= $t['status_paid'] ?></option>
                    </select>
                </div>
            </div>
        </div>

        <?php if($isEdit): ?>
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-coins"></i> <?= $t['panel_numbers'] ?></h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label"><?= $t['basic'] ?></label>
                    <input type="number" step="0.01" min="0" name="total_basic" class="form-control" style="font-family:monospace; font-weight:bold;" value="<?= htmlspecialchars((string)($payroll->total_basic ?? '0.00')) ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['allow'] ?></label>
                    <input type="number" step="0.01" min="0" name="total_allowances" class="form-control" style="font-family:monospace; font-weight:bold; color:#0284c7;" value="<?= htmlspecialchars((string)($payroll->total_allowances ?? '0.00')) ?>">
                </div>
                <div class="form-group">
                    <label class="input-label"><?= $t['deduct'] ?></label>
                    <input type="number" step="0.01" min="0" name="total_deductions" class="form-control" style="font-family:monospace; font-weight:bold; color:#dc2626;" value="<?= htmlspecialchars((string)($payroll->total_deductions ?? '0.00')) ?>">
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="action-bar">
            <a href="/ERP/hr/payroll" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $isEdit ? $t['update'] : $t['save'] ?></button>
        </div>
    </form>
</div>