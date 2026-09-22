<?php
// Path: resources/views/hr/attendance/log.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$targetDate = !empty($targetDate) ? $targetDate : ($_GET['date'] ?? date('Y-m-d'));
$employees = $employees ?? [];
$attendanceMap = $attendanceMap ?? [];

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';

$activeBranchName = $_SESSION['active_branch_name'] ?? ($isRtl ? 'كل الفروع' : 'All Branches');

$t = [
    'ar' => [
        'title' => 'شيت التحضير الجماعي (Bulk Attendance)',
        'desc' => 'تسجيل الدوام لجميع الموظفين دفعة واحدة لليوم المحدد.',
        'print' => 'طباعة شيت اليوم', 'save' => 'حفظ السجل الشامل',
        'date_lbl' => 'تاريخ السجل (اليوم المراد تحضيره):',
        'mark_all_in' => 'تحضير الكل (حاضر)', 'mark_all_out' => 'غياب الكل',
        'col_hash' => 'م', 'col_emp' => 'اسم الموظف والكود', 'col_status' => 'الحالة (Status)',
        'col_in' => 'الدخول (In)', 'col_out' => 'الانصراف (Out)', 'col_notes' => 'توقيع / ملاحظات',
        'empty' => 'لا يوجد موظفين نشطين لعمل سجل حضور لهم.',
        'status_present' => 'حاضر', 'status_late' => 'متأخر', 'status_half' => 'نصف يوم',
        'status_absent' => 'غائب', 'status_leave' => 'في إجازة', 'notes_ph' => 'أعطال / توقيع...',
        'active_scope' => 'الفرع النشط:'
    ],
    'en' => [
        'title' => 'Bulk Attendance Sheet',
        'desc' => 'Register daily attendance for all active employees at once.',
        'print' => 'Print Daily Sheet', 'save' => 'Save Bulk Record',
        'date_lbl' => 'Record Date (Day to Track):',
        'mark_all_in' => 'Mark All Present', 'mark_all_out' => 'Mark All Absent',
        'col_hash' => '#', 'col_emp' => 'Employee & Code', 'col_status' => 'Status',
        'col_in' => 'Time In', 'col_out' => 'Time Out', 'col_notes' => 'Signature / Notes',
        'empty' => 'No active employees found to create a record.',
        'status_present' => 'Present', 'status_late' => 'Late', 'status_half' => 'Half Day',
        'status_absent' => 'Absent', 'status_leave' => 'On Leave', 'notes_ph' => 'Issues / Signature...',
        'active_scope' => 'Active Branch:'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-att: #0284c7; 
        --c-att-dark: #0369a1; 
        --c-att-light: #e0f2fe;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 1200px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); flex-wrap: wrap; gap: 16px; }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .branch-scope-badge { display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--c-text); margin-bottom: 20px; }

    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

    .top-controls { display: flex; justify-content: space-between; align-items: flex-end; background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #cbd5e1; margin-bottom: 20px; flex-wrap: wrap; gap: 16px; }
    
    .form-control { padding: 10px 14px; border: 1px solid var(--c-border); border-radius: 8px; font-family: inherit; font-size: 0.9rem; background: #fff; }
    .form-control-sm { padding: 6px 10px; border: 1px solid var(--c-border); border-radius: 6px; font-size: 0.85rem; width: 100%; box-sizing: border-box; }

    .bulk-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .bulk-table th { background: var(--c-att-light); color: var(--c-att-dark); padding: 12px; font-weight: 800; text-align: start; border-bottom: 2px solid #bae6fd; }
    .bulk-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .bulk-table tr:hover { background: #f8fafc; }

    .btn-submit { background: linear-gradient(135deg, var(--c-att), var(--c-att-dark)); color: white; border: none; padding: 12px 28px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 1rem; transition: 0.2s; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3); }
    
    .btn-print { background: #0f172a; color: white; border: none; padding: 12px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 1rem; transition: 0.2s; }
    .btn-print:hover { background: #1e293b; transform: translateY(-2px); }
    
    .btn-action-quick { background: #10b981; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; }
    .btn-action-quick:hover { opacity: 0.9; }

    .print-header { display: none; }

    /* إخفاء عناصر DataTables المحقونة في صفحة السجل فقط لمنع التشوه */
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate { display: none !important; }

    /* إعدادات الطباعة الاحترافية A4 Landscape */
    @media print {
        @page { size: A4 landscape; margin: 12mm; }
        
        body { background: #ffffff !important; margin: 0; padding: 0; color: #000000 !important; }
        
        .nt-sidebar, .top-header, .form-header, .top-controls, button, .action-bar, .branch-scope-badge { 
            display: none !important; 
        }
        
        .form-wrapper { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .form-section { box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; }
        
        /* ترويسة الطباعة الرسمية */
        .print-header { 
            display: flex !important; 
            justify-content: space-between; 
            align-items: flex-end; 
            border-bottom: 3px double #000000; 
            padding-bottom: 12px; 
            margin-bottom: 24px; 
        }
        .print-header h2 { margin: 0 0 5px 0; font-size: 1.6rem; font-weight: 900; color: #000000; }
        .print-header p { margin: 0; font-size: 1rem; font-weight: bold; color: #333333; }
        .print-header .print-date { font-family: monospace; font-size: 1.3rem; font-weight: bold; color: #000000; margin-top: 5px; }

        /* تجريد الحقول من الإطارات في الطباعة لتبدو كنص عادي */
        input[type="time"], input[type="text"], select { 
            border: none !important; 
            background: transparent !important; 
            color: #000000 !important; 
            -webkit-appearance: none !important; 
            -moz-appearance: none !important;
            appearance: none !important; 
            font-weight: bold !important; 
            padding: 0 !important;
            width: 100% !important;
            text-align: <?= $isRtl ? 'right' : 'left' ?> !important;
            text-align-last: <?= $isRtl ? 'right' : 'left' ?> !important;
            box-shadow: none !important;
        }

        /* إخفاء السهم الخاص بالقائمة المنسدلة في بعض المتصفحات عند الطباعة */
        select::-ms-expand { display: none !important; }
        
        /* تنسيق الجدول الاحترافي للطباعة */
        .bulk-table { border: 2px solid #000000 !important; border-collapse: collapse !important; width: 100% !important; }
        .bulk-table th { 
            background-color: #f1f5f9 !important; 
            color: #000000 !important; 
            border: 1px solid #000000 !important; 
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important;
            padding: 10px !important;
            font-size: 0.95rem !important;
        }
        .bulk-table td { 
            border: 1px solid #000000 !important; 
            padding: 8px 10px !important; 
            font-size: 0.9rem !important;
            color: #000000 !important;
        }

        /* ضمان بقاء الهيدر في الصفحات الجديدة */
        .bulk-table thead { display: table-header-group !important; }
        .bulk-table tr { page-break-inside: avoid !important; }
    }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/attendance" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;"><?= $t['title'] ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;"><?= $t['desc'] ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="button" onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
            <button type="button" onclick="document.getElementById('bulkForm').submit();" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $t['save'] ?></button>
        </div>
    </div>

    <div>
        <div class="branch-scope-badge">
            <i class="ph-bold ph-storefront" style="color:var(--c-att);"></i>
            <span><?= $t['active_scope'] ?></span>
            <span style="color:var(--c-att); font-weight:900;"><?= htmlspecialchars($activeBranchName) ?></span>
        </div>
    </div>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fecaca; font-weight:bold;"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <div class="form-section">
        
        <!-- Print Header -->
        <div class="print-header">
            <div>
                <h2><?= htmlspecialchars((string)$cName) ?></h2>
                <p><?= $t['title'] ?> - <?= htmlspecialchars($activeBranchName) ?></p>
            </div>
            <div style="text-align:<?= $isRtl ? 'left' : 'right' ?>;">
                <p style="margin-bottom:5px; font-size: 0.9rem;"><?= $t['date_lbl'] ?></p>
                <div class="print-date"><?= htmlspecialchars((string)$targetDate) ?></div>
            </div>
        </div>

        <div class="top-controls">
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-weight: 800; color: #334155; font-size: 0.9rem;"><?= $t['date_lbl'] ?></label>
                <form action="" method="GET" id="dateForm" style="display:flex; gap:10px;">
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars((string)$targetDate) ?>" onchange="document.getElementById('dateForm').submit();" style="font-weight:bold; color:var(--c-att-dark);">
                    <button type="submit" style="display:none;"></button>
                </form>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn-action-quick" onclick="markAll('present')"><i class="ph-bold ph-check-square"></i> <?= $t['mark_all_in'] ?></button>
                <button type="button" class="btn-action-quick" style="background:#dc2626;" onclick="markAll('absent')"><i class="ph-bold ph-x-square"></i> <?= $t['mark_all_out'] ?></button>
            </div>
        </div>

        <form action="/ERP/hr/attendance/bulk-store" method="POST" id="bulkForm">
            <input type="hidden" name="date" value="<?= htmlspecialchars((string)$targetDate) ?>">
            
            <div style="overflow-x: auto;">
                <table class="bulk-table no-datatable">
                    <thead>
                        <tr>
                            <th style="width: 5%;"><?= $t['col_hash'] ?></th>
                            <th style="width: 25%;"><?= $t['col_emp'] ?></th>
                            <th style="width: 15%;"><?= $t['col_status'] ?></th>
                            <th style="width: 15%;"><?= $t['col_in'] ?></th>
                            <th style="width: 15%;"><?= $t['col_out'] ?></th>
                            <th style="width: 25%;"><?= $t['col_notes'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr><td colspan="6" style="text-align: center; padding: 40px; font-weight: bold; color: #94a3b8;"><?= $t['empty'] ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($employees as $index => $emp): 
                                $empName = $isRtl ? ($emp->name_ar ?? '') : ($emp->name_en ?: ($emp->name_ar ?? ''));
                                $att = $attendanceMap[$emp->id] ?? null;
                                $status = $att ? $att->status : 'present';
                                $checkIn = $att ? $att->check_in : '08:00';
                                $checkOut = $att ? $att->check_out : '16:00';
                                $notes = $att ? $att->notes : '';
                            ?>
                            <tr>
                                <td style="font-weight:bold;"><?= $index + 1 ?></td>
                                <td>
                                    <div style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars((string)$empName) ?></div>
                                    <div style="font-size: 0.75rem; color: #64748b; font-family: monospace;"><?= htmlspecialchars((string)$emp->emp_code) ?></div>
                                    <?php if($att): ?>
                                        <input type="hidden" name="attendance[<?= $emp->id ?>][record_id]" value="<?= $att->id ?>">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <select name="attendance[<?= $emp->id ?>][status]" class="form-control-sm status-select" onchange="toggleTimes(this, <?= $emp->id ?>)">
                                        <option value="present" <?= $status == 'present' ? 'selected' : '' ?>><?= $t['status_present'] ?></option>
                                        <option value="late" <?= $status == 'late' ? 'selected' : '' ?>><?= $t['status_late'] ?></option>
                                        <option value="half_day" <?= $status == 'half_day' ? 'selected' : '' ?>><?= $t['status_half'] ?></option>
                                        <option value="absent" <?= $status == 'absent' ? 'selected' : '' ?>><?= $t['status_absent'] ?></option>
                                        <option value="on_leave" <?= $status == 'on_leave' ? 'selected' : '' ?>><?= $t['status_leave'] ?></option>
                                    </select>
                                </td>
                                <td>
                                    <input type="time" name="attendance[<?= $emp->id ?>][check_in]" id="in_<?= $emp->id ?>" class="form-control-sm time-in" value="<?= htmlspecialchars((string)$checkIn) ?>" <?= in_array($status, ['absent', 'on_leave']) ? 'readonly style="opacity:0.5;"' : '' ?>>
                                </td>
                                <td>
                                    <input type="time" name="attendance[<?= $emp->id ?>][check_out]" id="out_<?= $emp->id ?>" class="form-control-sm time-out" value="<?= htmlspecialchars((string)$checkOut) ?>" <?= in_array($status, ['absent', 'on_leave']) ? 'readonly style="opacity:0.5;"' : '' ?>>
                                </td>
                                <td>
                                    <input type="text" name="attendance[<?= $emp->id ?>][notes]" class="form-control-sm" value="<?= htmlspecialchars((string)$notes) ?>" placeholder="<?= $t['notes_ph'] ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleTimes(selectElement, empId) {
        let inInput = document.getElementById('in_' + empId);
        let outInput = document.getElementById('out_' + empId);
        
        if (selectElement.value === 'absent' || selectElement.value === 'on_leave') {
            inInput.value = '';
            outInput.value = '';
            inInput.readOnly = true;
            outInput.readOnly = true;
            inInput.style.opacity = '0.5';
            outInput.style.opacity = '0.5';
        } else {
            if (!inInput.value) inInput.value = '08:00';
            if (!outInput.value) outInput.value = '16:00';
            inInput.readOnly = false;
            outInput.readOnly = false;
            inInput.style.opacity = '1';
            outInput.style.opacity = '1';
        }
    }

    function markAll(statusVal) {
        let selects = document.querySelectorAll('.status-select');
        selects.forEach(select => {
            select.value = statusVal;
            select.dispatchEvent(new Event('change'));
        });
    }

    function purgeControls() {
        document.querySelectorAll('.dataTables_wrapper, .dataTables_filter, .dataTables_length, .dataTables_info, .dataTables_paginate').forEach(el => el.remove());
    }

    function safePrint() {
        purgeControls();
        window.print();
    }
    
    document.addEventListener("DOMContentLoaded", purgeControls);
    window.addEventListener("beforeprint", purgeControls);
</script>