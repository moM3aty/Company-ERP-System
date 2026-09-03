<?php
// Path: resources/views/projects/invoices/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = current_currency();

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$t = [
    'ar' => [
        'print' => 'طباعة المستخلص الرسمي', 'edit' => 'تعديل', 'back' => 'العودة',
        'subtitle' => 'إدارة العقود والمستخلصات الإنشائية',
        'proj_site' => 'المشروع / الموقع:', 'client' => 'العميل المالك:', 'period' => 'فترة المستخلص:',
        'from' => 'من:', 'to' => 'إلى:', 'status' => 'حالة المستخلص:',
        'gross' => 'إجمالي الأعمال المنجزة حتى تاريخه (Gross Amount):', 'deduct' => 'الاستقطاعات والتأخيرات / دفعة مقدمة (Deductions):',
        'tax' => 'ضريبة القيمة المضافة (VAT):', 'net' => 'صافي قيمة الشهادة والمطالبة (Net Progress Claim):',
        'paid' => 'المسدد للآن:', 'rem' => 'المتبقي غير المسدد:',
        'desc' => 'وصف الأعمال:', 'notes' => 'ملاحظات وشروط الدفع:',
        'sig_eng' => 'المهندس الاستشاري المشرف', 'sig_mgr' => 'مدير المشروع / المقاول', 'sig_owner' => 'اعتماد مالك المشروع',
        'st_draft' => 'مسودة', 'st_sub' => 'مقدم للاعتماد الفني', 'st_app' => 'معتمد وجاهز للصرف', 'st_part' => 'مدفوع جزئياً', 'st_paid' => 'مسدد بالكامل', 'st_rej' => 'مرفوض',
        'tp_adv' => 'شهادة دفعة مقدمة (Advance Payment Claim)', 'tp_prog' => 'شهادة مستخلص جاري (Progress Payment Claim)', 'tp_final' => 'شهادة مستخلص ختامي (Final Payment Claim)', 'tp_ret' => 'شهادة إفراج عن محتجزات (Retention Release)',
        'update_st' => 'تحديث حالة واعتمادات المستخلص', 'new_st' => 'الحالة الجديدة', 'paid_inp' => 'المبلغ المسدد إجمالاً', 'btn_update' => 'تحديث الحالة الآن'
    ],
    'en' => [
        'print' => 'Print Official Claim', 'edit' => 'Edit', 'back' => 'Back',
        'subtitle' => 'Construction Contracts & Claims Management',
        'proj_site' => 'Project / Site:', 'client' => 'Client / Owner:', 'period' => 'Claim Period:',
        'from' => 'From:', 'to' => 'To:', 'status' => 'Claim Status:',
        'gross' => 'Total Work Done to Date (Gross Amount):', 'deduct' => 'Deductions & Advance Payment Recovery:',
        'tax' => 'Value Added Tax (VAT):', 'net' => 'Net Progress Claim Amount:',
        'paid' => 'Paid So Far:', 'rem' => 'Remaining Unpaid:',
        'desc' => 'Scope of Work:', 'notes' => 'Payment Terms & Notes:',
        'sig_eng' => 'Consultant Engineer', 'sig_mgr' => 'Project Manager / Contractor', 'sig_owner' => 'Owner Approval',
        'st_draft' => 'Draft', 'st_sub' => 'Submitted for Tech Approval', 'st_app' => 'Approved for Payment', 'st_part' => 'Partially Paid', 'st_paid' => 'Fully Paid', 'st_rej' => 'Rejected',
        'tp_adv' => 'Advance Payment Claim', 'tp_prog' => 'Progress Payment Claim', 'tp_final' => 'Final Payment Claim', 'tp_ret' => 'Retention Release Certificate',
        'update_st' => 'Update Claim Status & Approvals', 'new_st' => 'New Status', 'paid_inp' => 'Total Amount Paid', 'btn_update' => 'Update Status Now'
    ]
][$isAr ? 'ar' : 'en'];

$statusMap = [
    'draft' => ['label' => $t['st_draft'], 'color' => '#64748b', 'bg' => '#f1f5f9'],
    'submitted' => ['label' => $t['st_sub'], 'color' => '#d97706', 'bg' => '#fef3c7'],
    'approved' => ['label' => $t['st_app'], 'color' => '#0284c7', 'bg' => '#e0f2fe'],
    'partially_paid' => ['label' => $t['st_part'], 'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
    'paid' => ['label' => $t['st_paid'], 'color' => '#059669', 'bg' => '#ecfdf5'],
    'rejected' => ['label' => $t['st_rej'], 'color' => '#dc2626', 'bg' => '#fef2f2'],
];

$typeMap = [
    'advance_payment' => $t['tp_adv'],
    'progress_claim' => $t['tp_prog'],
    'final_claim' => $t['tp_final'],
    'retention_release' => $t['tp_ret'],
];

$st = $statusMap[$invoice->status] ?? $statusMap['draft'];
$net = (float)$invoice->net_amount;
$paid = (float)$invoice->paid_amount;
$rem = max(0, $net - $paid);
?>

<style>
    :root { --c-pi: #059669; --c-pi-dark: #047857; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .pi-show-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition: 0.2s;}
    .btn-action:hover { background: #ecfdf5; color: var(--c-pi); border-color: #a7f3d0; }
    .btn-print { background: var(--c-pi-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25); transition: 0.2s;}
    .btn-print:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(5, 150, 105, 0.35); }

    .voucher-card { background: #ffffff; border: 2px solid var(--c-pi); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }
    .voucher-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px double var(--c-pi); padding-bottom: 20px; margin-bottom: 24px; }
    
    .voucher-title-badge { background: var(--c-pi); color: #fff; padding: 6px 20px; border-radius: 8px; font-size: 1.1rem; font-weight: 900; letter-spacing: 1px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 160px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    .calculation-box { background: #f0fdf4; border: 1px solid #a7f3d0; border-radius: 12px; padding: 20px; margin: 24px 0; }
    .calc-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #dcfce7; font-size: 0.95rem; font-weight: 700; }
    .calc-row.total { border-bottom: none; font-size: 1.25rem; font-weight: 900; color: var(--c-pi-dark); padding-top: 12px; }

    .signatures-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 44px; text-align: center; }
    .sig-box h6 { margin: 0 0 40px 0; font-size: 0.85rem; color: var(--c-text-muted); font-weight: 800; }
    .sig-line { border-bottom: 1px solid #94a3b8; width: 80%; margin: 0 auto; }

    @media print {
        .nt-sidebar, .top-header, .header-bar, .status-action-card, header, aside { display: none !important; }
        body { background: #fff !important; }
        .pi-show-wrapper { max-width: 100% !important; padding: 0 !important; }
        .voucher-card { border: 2px solid #000 !important; box-shadow: none !important; page-break-inside: avoid; }
        .calculation-box { border: 1px solid #000 !important; background: transparent !important; }
        .calc-row { border-bottom: 1px dashed #000 !important; }
        .calc-row.total { border-bottom: none !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>

<div class="pi-show-wrapper" dir="<?= $isAr ? 'rtl' : 'ltr' ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/projects/invoices" class="btn-action" title="<?= $t['back'] ?>"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"># <?= htmlspecialchars($invoice->invoice_number) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;"><?= htmlspecialchars($invoice->invoice_date) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
            <a href="/ERP/projects/invoices/<?= $invoice->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="<?= $t['edit'] ?>"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <div class="voucher-card">
        <div class="voucher-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-buildings" style="font-size:3rem; color:var(--c-pi);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= $t['subtitle'] ?></span>
                </div>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <div class="voucher-title-badge"><?= $typeMap[$invoice->invoice_type] ?? $t['tp_prog'] ?></div>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-pi-dark); font-size:1.1rem;"># <?= htmlspecialchars($invoice->invoice_number) ?></div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label"><?= $t['proj_site'] ?></span>
                <span class="info-val"><?= htmlspecialchars($invoice->project_name ?? '---') ?> (<?= htmlspecialchars($invoice->project_code ?? '') ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['client'] ?></span>
                <span class="info-val"><?= htmlspecialchars($invoice->customer_name ?? '---') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['period'] ?></span>
                <span class="info-val" style="font-family:monospace;"><?= $t['from'] ?> <?= htmlspecialchars($invoice->period_start ?: '---') ?> <?= $t['to'] ?> <?= htmlspecialchars($invoice->period_end ?: '---') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['status'] ?></span>
                <span class="info-val"><span style="padding:2px 10px; border-radius:4px; font-size:0.85rem; background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;"><?= $st['label'] ?></span></span>
            </div>
        </div>

        <div class="calculation-box">
            <div class="calc-row">
                <span><?= $t['gross'] ?></span>
                <span style="font-family:monospace;"><?= number_format((float)$invoice->total_amount, 2) ?></span>
            </div>
            <div class="calc-row" style="color:#dc2626;">
                <span><?= $t['deduct'] ?></span>
                <span style="font-family:monospace;">- <?= number_format((float)$invoice->deductions_amount, 2) ?></span>
            </div>
            <div class="calc-row" style="color:#0284c7;">
                <span><?= $t['tax'] ?></span>
                <span style="font-family:monospace;">+ <?= number_format((float)$invoice->tax_amount, 2) ?></span>
            </div>
            <div class="calc-row total">
                <span><?= $t['net'] ?></span>
                <span style="font-family:monospace;"><?= number_format($net, 2) ?> <span style="font-size:0.8rem; color:#64748b;"><?= $currency ?></span></span>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; background:#f8fafc; padding:12px 16px; border-radius:8px; border:1px solid #e2e8f0; font-size:0.9rem;">
            <div><b><?= $t['paid'] ?></b> <span style="font-family:monospace; color:#059669; font-weight:bold;"><?= number_format($paid, 2) ?></span></div>
            <div><b><?= $t['rem'] ?></b> <span style="font-family:monospace; color:<?= $rem > 0 ? '#dc2626' : '#64748b' ?>; font-weight:bold;"><?= number_format($rem, 2) ?></span></div>
        </div>

        <?php if(!empty($invoice->description) || !empty($invoice->notes)): ?>
            <div style="margin-top:20px; padding-top:16px; border-top:1px solid #f1f5f9;">
                <?php if(!empty($invoice->description)): ?>
                    <p style="margin:0 0 6px 0; font-size:0.85rem; color:#475569;"><b><?= $t['desc'] ?></b> <?= htmlspecialchars($invoice->description) ?></p>
                <?php endif; ?>
                <?php if(!empty($invoice->notes)): ?>
                    <p style="margin:0; font-size:0.85rem; color:#64748b;"><b><?= $t['notes'] ?></b> <?= htmlspecialchars($invoice->notes) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6><?= $t['sig_eng'] ?></h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= $t['sig_mgr'] ?></h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= $t['sig_owner'] ?></h6>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>

    <!-- Status Update Form (Hidden on print) -->
    <div class="status-action-card" style="background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:24px;">
        <h3 style="margin:0 0 16px 0; font-size:1.1rem; font-weight:800; color:var(--c-text-dark); display:flex; align-items:center; gap:8px;">
            <i class="ph-bold ph-sliders-horizontal" style="color:var(--c-pi);"></i> <?= $t['update_st'] ?>
        </h3>
        <form action="/ERP/projects/invoices/<?= $invoice->id ?>/status" method="POST" style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap;">
            <div style="flex:1; min-width:200px;">
                <label style="display:block; font-size:0.85rem; font-weight:800; color:#475569; margin-bottom:6px;"><?= $t['new_st'] ?></label>
                <select name="status" style="width:100%; padding:10px 14px; border:1px solid #cbd5e1; border-radius:8px; font-weight:bold; font-family:inherit; background:#f8fafc;">
                    <option value="draft" <?= $invoice->status === 'draft' ? 'selected' : '' ?>><?= $t['st_draft'] ?></option>
                    <option value="submitted" <?= $invoice->status === 'submitted' ? 'selected' : '' ?>><?= $t['st_sub'] ?></option>
                    <option value="approved" <?= $invoice->status === 'approved' ? 'selected' : '' ?>><?= $t['st_app'] ?></option>
                    <option value="partially_paid" <?= $invoice->status === 'partially_paid' ? 'selected' : '' ?>><?= $t['st_part'] ?></option>
                    <option value="paid" <?= $invoice->status === 'paid' ? 'selected' : '' ?>><?= $t['st_paid'] ?></option>
                    <option value="rejected" <?= $invoice->status === 'rejected' ? 'selected' : '' ?>><?= $t['st_rej'] ?></option>
                </select>
            </div>
            <div style="flex:1; min-width:200px;">
                <label style="display:block; font-size:0.85rem; font-weight:800; color:#475569; margin-bottom:6px;"><?= $t['paid_inp'] ?> (<?= $currency ?>)</label>
                <input type="number" step="0.01" min="0" name="paid_amount" style="width:100%; padding:10px 14px; border:1px solid #cbd5e1; border-radius:8px; font-family:monospace; font-weight:bold; background:#f8fafc;" value="<?= htmlspecialchars($invoice->paid_amount) ?>">
            </div>
            <button type="submit" style="background:var(--c-pi-dark); color:#fff; border:none; padding:12px 24px; border-radius:8px; font-weight:800; cursor:pointer;"><i class="ph-bold ph-check"></i> <?= $t['btn_update'] ?></button>
        </form>
    </div>
</div>

<script>
function purgeControls() {
    const selectors = ['.table-pagination-nav', '.dataTables_info', '.dataTables_paginate', '.pagination'];
    selectors.forEach(s => {
        document.querySelectorAll(s).forEach(el => el.remove());
    });
}

function safePrint() {
    purgeControls();
    window.print();
}

document.addEventListener("DOMContentLoaded", purgeControls);
window.addEventListener("beforeprint", purgeControls);
</script>