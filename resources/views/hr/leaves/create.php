<?php
// Path: resources/views/hr/leaves/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
?>

<style>
    :root { 
        --c-leave: #7c3aed; 
        --c-leave-dark: #6d28d9; 
        --c-leave-light: #f3e8ff;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-leave); border-radius: 0 16px 16px 0; }
    [dir="ltr"] .form-section::before { right: auto; left: 0; border-radius: 16px 0 0 16px; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-leave); font-size: 1.4rem; padding: 8px; background: var(--c-leave-light); border-radius: 8px; }
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
    @media(max-width:768px) { .grid-2 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 32px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-leave), var(--c-leave-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/hr/leaves" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;">تقديم طلب إجازة جديد</h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">إدخال تفاصيل الإجازة، المدة، والسبب للاعتماد.</p>
            </div>
        </div>
    </div>

    <form action="/ERP/hr/leaves/store" method="POST">
        
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-airplane-takeoff"></i> تفاصيل طلب الإجازة</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">الموظف صاحب الطلب <span style="color:red">*</span></label>
                    <select name="employee_id" class="form-control" required>
                        <option value="">-- اختر الموظف --</option>
                        <?php foreach($employees as $emp): ?>
                            <option value="<?= $emp->id ?>">
                                <?= htmlspecialchars($emp->emp_code) ?> - <?= htmlspecialchars($emp->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">نوع الإجازة <span style="color:red">*</span></label>
                    <select name="leave_type" class="form-control" required>
                        <option value="annual">إجازة سنوية (Annual)</option>
                        <option value="sick">إجازة مرضية (Sick)</option>
                        <option value="unpaid">بدون راتب (Unpaid)</option>
                        <option value="maternity">إجازة وضع/أمومة (Maternity)</option>
                        <option value="other">إجازة أخرى (Other)</option>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div class="form-group">
                    <label class="input-label">تاريخ بدء الإجازة <span style="color:red">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ نهاية الإجازة <span style="color:red">*</span></label>
                    <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">سبب الإجازة والتفاصيل</label>
                <textarea name="reason" class="form-control" rows="3" placeholder="توضيح أسباب الطلب إن وجد..."></textarea>
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/hr/leaves" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-paper-plane-tilt"></i> إرسال الطلب للاعتماد</button>
        </div>
    </form>
</div>