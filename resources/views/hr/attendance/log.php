<?php
// Path: resources/views/hr/attendance/log.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

// حماية المتغيرات من القيمة null لتفادي خطأ PHP 8.1+
$targetDate = !empty($targetDate) ? $targetDate : ($_GET['date'] ?? date('Y-m-d'));
$employees = $employees ?? [];
$attendanceMap = $attendanceMap ?? [];

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
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
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

    .top-controls { display: flex; justify-content: space-between; align-items: flex-end; background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #cbd5e1; margin-bottom: 20px; flex-wrap: wrap; gap: 16px; }
    
    .form-control { padding: 10px 14px; border: 1px solid var(--c-border); border-radius: 8px; font-family: inherit; font-size: 0.9rem; background: #fff; }
    .form-control-sm { padding: 6px 10px; border: 1px solid var(--c-border); border-radius: 6px; font-size: 0.85rem; width: 100%; }

    .bulk-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .bulk-table th { background: var(--c-att-light); color: var(--c-att-dark); padding: 12px; font-weight: 800; text-align: start; border-bottom: 2px solid #bae6fd; }
    .bulk-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .bulk-table tr:hover { background: #f8fafc; }

    .btn-submit { background: linear-gradient(135deg, var(--c-att), var(--c-att-dark)); color: white; border: none; padding: 12px 28px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 1rem; }
    .btn-print { background: #0f172a; color: white; border: none; padding: 12px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 1rem; text-decoration: none; }
    .btn-action-quick { background: #10b981; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px; }

    /* Print Header hidden on screens */
    .print-header { display: none; }

    /* إعدادات الطباعة الاحترافية */
    @media print {
        .nt-sidebar, .top-header, .form-header, .top-controls, button, .action-bar { display: none !important; }
        body { background: #fff !important; margin: 0; padding: 0; }
        .form-wrapper { max-width: 100% !important; padding: 0 !important; }
        .form-section { box-shadow: none !important; border: none !important; padding: 0 !important; }
        
        .print-header { display: flex !important; justify-content: space-between; align-items: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 20px; }
        .print-header h2 { margin: 0 0 5px 0; font-size: 1.5rem; color: #000; }
        .print-header p { margin: 0; font-size: 0.9rem; font-weight: bold; color: #333; }
        .print-header .print-date { font-family: monospace; font-size: 1.2rem; font-weight: bold; }

        input[type="time"], input[type="text"], select { 
            border: none !important; 
            background: transparent !important; 
            color: #000 !important; 
            -webkit-appearance: none; 
            appearance: none; 
            font-weight: bold !important; 
            padding: 0 !important;
            width: auto !important;
        }
        
        .bulk-table { border: 1px solid #000 !important; }
        .bulk-table th { background: #f1f5f9 !important; color: #000 !important; border: 1px solid #000 !important; -webkit-print-color-adjust: exact; }
        .bulk-table td { border: 1px solid #000 !important; }
        
        @page { size: A4 portrait; margin: 15mm; }
    }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/attendance" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;">شيت التحضير الجماعي (Bulk Attendance)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">تسجيل الدوام لجميع الموظفين دفعة واحدة لليوم المحدد.</p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="button" onclick="window.print()" class="btn-print"><i class="ph-bold ph-printer"></i> طباعة شيت اليوم</button>
            <button type="button" onclick="document.getElementById('bulkForm').submit();" class="btn-submit"><i class="ph-bold ph-floppy-disk"></i> حفظ السجل</button>
        </div>
    </div>

    <div class="form-section">
        
        <!-- ترويسة الطباعة -->
        <div class="print-header">
            <div>
                <h2><?= htmlspecialchars($cName) ?></h2>
                <p>إدارة الموارد البشرية - شيت الحضور والانصراف اليومي المجمع</p>
            </div>
            <div style="text-align:end;">
                <p style="margin-bottom:5px;">تاريخ السجل (Date)</p>
                <div class="print-date"><?= htmlspecialchars((string)$targetDate) ?></div>
            </div>
        </div>

        <!-- شريط التحكم والتاريخ -->
        <div class="top-controls">
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-weight: 800; color: #334155; font-size: 0.9rem;">تاريخ السجل (اليوم المراد تحضيره):</label>
                <form action="" method="GET" id="dateForm" style="display:flex; gap:10px;">
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars((string)$targetDate) ?>" onchange="document.getElementById('dateForm').submit();" style="font-weight:bold; color:var(--c-att-dark);">
                    <button type="submit" style="display:none;"></button>
                </form>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn-action-quick" onclick="markAll('present')"><i class="ph-bold ph-check-square"></i> تحضير الكل (حاضر)</button>
                <button type="button" class="btn-action-quick" style="background:#dc2626;" onclick="markAll('absent')"><i class="ph-bold ph-x-square"></i> غياب الكل</button>
            </div>
        </div>

        <form action="/ERP/hr/attendance/bulk-store" method="POST" id="bulkForm">
            <input type="hidden" name="date" value="<?= htmlspecialchars((string)$targetDate) ?>">
            
            <div style="overflow-x: auto;">
                <table class="bulk-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">م</th>
                            <th style="width: 25%;">اسم الموظف والكود</th>
                            <th style="width: 15%;">الحالة (Status)</th>
                            <th style="width: 15%;">الدخول (In)</th>
                            <th style="width: 15%;">الانصراف (Out)</th>
                            <th style="width: 25%;">توقيع / ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr><td colspan="6" style="text-align: center; padding: 40px; font-weight: bold; color: #94a3b8;">لا يوجد موظفين نشطين لعمل سجل حضور لهم.</td></tr>
                        <?php else: ?>
                            <?php foreach ($employees as $index => $emp): 
                                $att = $attendanceMap[$emp->id] ?? null;
                                $status = $att ? $att->status : 'present';
                                $checkIn = $att ? $att->check_in : '08:00';
                                $checkOut = $att ? $att->check_out : '16:00';
                                $notes = $att ? $att->notes : '';
                            ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <div style="font-weight: 800; color: #0f172a;"><?= htmlspecialchars($emp->name_ar) ?></div>
                                    <div style="font-size: 0.75rem; color: #64748b; font-family: monospace;"><?= htmlspecialchars($emp->emp_code) ?></div>
                                    <?php if($att): ?>
                                        <input type="hidden" name="attendance[<?= $emp->id ?>][record_id]" value="<?= $att->id ?>">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <select name="attendance[<?= $emp->id ?>][status]" class="form-control-sm status-select" onchange="toggleTimes(this, <?= $emp->id ?>)">
                                        <option value="present" <?= $status == 'present' ? 'selected' : '' ?>>حاضر</option>
                                        <option value="late" <?= $status == 'late' ? 'selected' : '' ?>>متأخر</option>
                                        <option value="half_day" <?= $status == 'half_day' ? 'selected' : '' ?>>نصف يوم</option>
                                        <option value="absent" <?= $status == 'absent' ? 'selected' : '' ?>>غائب</option>
                                        <option value="on_leave" <?= $status == 'on_leave' ? 'selected' : '' ?>>في إجازة</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="time" name="attendance[<?= $emp->id ?>][check_in]" id="in_<?= $emp->id ?>" class="form-control-sm time-in" value="<?= htmlspecialchars((string)$checkIn) ?>" <?= in_array($status, ['absent', 'on_leave']) ? 'readonly style="opacity:0.5;"' : '' ?>>
                                </td>
                                <td>
                                    <input type="time" name="attendance[<?= $emp->id ?>][check_out]" id="out_<?= $emp->id ?>" class="form-control-sm time-out" value="<?= htmlspecialchars((string)$checkOut) ?>" <?= in_array($status, ['absent', 'on_leave']) ? 'readonly style="opacity:0.5;"' : '' ?>>
                                </td>
                                <td>
                                    <input type="text" name="attendance[<?= $emp->id ?>][notes]" class="form-control-sm" value="<?= htmlspecialchars((string)$notes) ?>" placeholder="أعطال / توقيع...">
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
</script>