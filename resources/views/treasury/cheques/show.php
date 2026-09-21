<?php
// Path: resources/views/treasury/cheques/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

if (!isset($cheque) || !$cheque) {
    header("Location: /ERP/treasury/cheques");
    exit;
}

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$chequeNumber = htmlspecialchars((string)($cheque->cheque_number ?? '---'));
$dueDate      = htmlspecialchars((string)($cheque->due_date ?? date('Y-m-d')));
$issueDate    = htmlspecialchars((string)($cheque->issue_date ?? date('Y-m-d')));
$bankName     = htmlspecialchars((string)($cheque->bank_name ?? '---'));
$payeeName    = htmlspecialchars((string)($cheque->payee_payer_name ?? '---'));
$accountName  = htmlspecialchars((string)($cheque->account_name ?? ($isAr ? 'عام' : 'General')));
$accountCode  = htmlspecialchars((string)($cheque->account_code ?? '---'));
$notes        = htmlspecialchars((string)($cheque->notes ?? ''));
$amount       = (float)($cheque->amount ?? 0);
$type         = (string)($cheque->type ?? 'received');
$status       = (string)($cheque->status ?? 'pending');

$t = [
    'ar' => [
        'print' => 'طباعة كشف الشيك',
        'edit' => 'تعديل',
        'title_recv' => 'شيك وارد (RECEIVED CHEQUE)',
        'title_iss' => 'شيك صادر (ISSUED CHEQUE)',
        'sub' => 'إدارة الخزانة والمالية',
        'cheque_no' => 'شيك رقم:',
        'due_date_lbl' => 'تاريخ الاستحقاق:',
        'amt_label' => 'مبلغ وقيمة الشيك / Cheque Amount',
        'status_lbl' => 'حالة الورقة:',
        'bank_lbl' => 'البنك المسحوب عليه:',
        'payee_lbl' => 'اسم المستفيد / الساحب:',
        'acc_lbl' => 'الحساب / الخزينة المربوطة:',
        'dates_lbl' => 'تاريخ التحرير والاستحقاق:',
        'issue' => 'تحرير:', 'due' => 'استحقاق:',
        'notes_lbl' => 'ملاحظات تفصيلية:',
        'no_notes' => 'لا توجد ملاحظات إضافية',
        'sig_payee' => 'المستلم / الساحب',
        'sig_cashier' => 'أمين صندوق الشيكات',
        'sig_manager' => 'اعتماد الحسابات',
        'update_title' => 'تحديث وتغيير حالة الشيك ورسم التحصيل',
        'choose_status' => 'اختر الحالة الجديدة للشيك',
        'btn_update' => 'تحديث الحالة الآن',
        'status_pending' => 'برسم التحصيل / معلق',
        'status_collected' => 'محصل بالمصرف / مقبول',
        'status_bounced' => 'مرتد / مرفوض بدون رصيد',
        'status_cancelled' => 'ملغى'
    ],
    'en' => [
        'print' => 'Print Cheque Voucher',
        'edit' => 'Edit',
        'title_recv' => 'RECEIVED CHEQUE VOUCHER',
        'title_iss' => 'ISSUED CHEQUE VOUCHER',
        'sub' => 'Treasury & Finance Department',
        'cheque_no' => 'Cheque No:',
        'due_date_lbl' => 'Due Date:',
        'amt_label' => 'Cheque Amount',
        'status_lbl' => 'Status:',
        'bank_lbl' => 'Drawee Bank Name:',
        'payee_lbl' => 'Payee / Payer Name:',
        'acc_lbl' => 'Linked Account / Safe:',
        'dates_lbl' => 'Issue & Due Dates:',
        'issue' => 'Issue:', 'due' => 'Due:',
        'notes_lbl' => 'Detailed Notes:',
        'no_notes' => 'No additional notes',
        'sig_payee' => 'Recipient / Payer',
        'sig_cashier' => 'Cheques Cashier',
        'sig_manager' => 'Accounts Approval',
        'update_title' => 'Update Cheque Status & Clearing',
        'choose_status' => 'Select New Cheque Status',
        'btn_update' => 'Update Status Now',
        'status_pending' => 'Pending (Under Collection)',
        'status_collected' => 'Collected / Cleared',
        'status_bounced' => 'Bounced / Rejected',
        'status_cancelled' => 'Cancelled'
    ]
][$isAr ? 'ar' : 'en'];

$statusMap = [
    'pending'   => ['label' => $t['status_pending'], 'color' => '#d97706', 'bg' => '#fef3c7'],
    'collected' => ['label' => $t['status_collected'], 'color' => '#059669', 'bg' => '#ecfdf5'],
    'bounced'   => ['label' => $t['status_bounced'], 'color' => '#dc2626', 'bg' => '#fef2f2'],
    'cancelled' => ['label' => $t['status_cancelled'], 'color' => '#64748b', 'bg' => '#f1f5f9'],
];
$st = $statusMap[$status] ?? $statusMap['pending'];
?>

<style>
    :root { --c-chq: #c026d3; --c-chq-dark: #a21caf; --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; }
    .cheque-show-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { width: 44px; height: 44px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 1.2rem; transition:0.2s;}
    .btn-action:hover { background: #fdf4ff; color: var(--c-chq-dark); border-color: #f5d0fe; }
    .btn-print { background: var(--c-chq-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition:0.2s;}
    .btn-print:hover { background: #701a75; }

    .voucher-card { background: #ffffff; border: 2px solid var(--c-chq); border-radius: 16px; padding: 36px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); position: relative; margin-bottom: 24px; }
    .voucher-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px double var(--c-chq); padding-bottom: 20px; margin-bottom: 24px; }
    
    .voucher-title-badge { background: var(--c-chq); color: #fff; padding: 6px 20px; border-radius: 8px; font-size: 1.1rem; font-weight: 900; letter-spacing: 1px; }

    .info-row { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 0.95rem; }
    .info-label { font-weight: 800; color: var(--c-text-muted); min-width: 150px; }
    .info-val { font-weight: 800; color: var(--c-text-dark); flex: 1; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; }

    .amount-box { background: #fdf4ff; border: 2px solid #c026d3; border-radius: 12px; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; margin: 24px 0; }
    .amount-val { font-size: 2rem; font-weight: 900; color: #a21caf; font-family: monospace; }

    .status-action-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

    .signatures-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 48px; text-align: center; }
    .sig-box h6 { margin: 0 0 40px 0; font-size: 0.85rem; color: var(--c-text-muted); font-weight: 800; }
    .sig-line { border-bottom: 1px dashed #94a3b8; width: 80%; margin: 0 auto; }

    /* إخفاء عناصر التحكم المحقونة تلقائياً */
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate,
    .table-pagination-nav, .pagination {
        display: none !important;
    }

    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        body * { visibility: hidden !important; }
        .cheque-show-wrapper, .cheque-show-wrapper * { visibility: visible !important; }
        .cheque-show-wrapper { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; }
        .header-bar, .status-action-card { display: none !important; }
        .voucher-card { border: 2px solid #000 !important; box-shadow: none !important; padding: 20px !important; }
        .amount-box { border-color: #000 !important; background: transparent !important; }
        .amount-val { color: #000 !important; }
        .voucher-title-badge { background: #000 !important; color: #fff !important; -webkit-print-color-adjust: exact !important; }
        .dataTables_wrapper, .table-pagination-nav { display: none !important; visibility: hidden !important; }
    }
</style>

<div class="cheque-show-wrapper" dir="<?= $dir ?>">
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/treasury/cheques" class="btn-action"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= $t['cheque_no'] ?> <?= $chequeNumber ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;"><?= $t['due_date_lbl'] ?> <?= $dueDate ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="safePrint()" class="btn-print"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
            <a href="/ERP/treasury/cheques/<?= (int)$cheque->id ?>/edit" class="btn-action" style="background:#0f172a; color:#fff;" title="<?= $t['edit'] ?>"><i class="ph-bold ph-pencil"></i></a>
        </div>
    </div>

    <div class="print-canvas voucher-card">
        <div class="voucher-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                    <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:60px;">
                <?php else: ?>
                    <i class="ph-fill ph-buildings" style="font-size:3rem; color:var(--c-chq);"></i>
                <?php endif; ?>
                <div>
                    <h2 style="margin:0; font-size:1.4rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                    <span style="font-size:0.8rem; color:var(--c-text-muted); font-weight:700;"><?= $t['sub'] ?></span>
                </div>
            </div>
            <div style="text-align:<?= $isAr ? 'left' : 'right' ?>;">
                <div class="voucher-title-badge"><?= $type === 'received' ? $t['title_recv'] : $t['title_iss'] ?></div>
                <div style="margin-top:8px; font-family:monospace; font-weight:900; color:var(--c-chq-dark); font-size:1.1rem;"># <?= $chequeNumber ?></div>
            </div>
        </div>

        <div class="amount-box">
            <div>
                <span style="font-size:0.8rem; font-weight:800; color:#a21caf; text-transform:uppercase;"><?= $t['amt_label'] ?></span>
                <div style="font-size:0.9rem; font-weight:700; color:#86198f; margin-top:2px;">
                    <?= $t['status_lbl'] ?> <span style="color:<?= $st['color'] ?>; font-weight:900;"><?= $st['label'] ?></span>
                </div>
            </div>
            <div class="amount-val"><?= number_format($convert($amount), 2) ?> <span style="font-size:1rem;"><?= $currency ?></span></div>
        </div>

        <div style="margin-top:20px;">
            <div class="info-row">
                <span class="info-label"><?= $t['bank_lbl'] ?></span>
                <span class="info-val"><?= $bankName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['payee_lbl'] ?></span>
                <span class="info-val"><?= $payeeName ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['acc_lbl'] ?></span>
                <span class="info-val"><?= $accountName ?> (<?= $accountCode ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['dates_lbl'] ?></span>
                <span class="info-val"><?= $t['issue'] ?> <?= $issueDate ?> | <?= $t['due'] ?> <?= $dueDate ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t['notes_lbl'] ?></span>
                <span class="info-val"><?= !empty($notes) ? $notes : $t['no_notes'] ?></span>
            </div>
        </div>

        <div class="signatures-grid">
            <div class="sig-box">
                <h6><?= $t['sig_payee'] ?></h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= $t['sig_cashier'] ?></h6>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <h6><?= $t['sig_manager'] ?></h6>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>

    <div class="status-action-card">
        <h3 style="margin:0 0 16px 0; font-size:1.1rem; font-weight:800; color:var(--c-text-dark); display:flex; align-items:center; gap:8px;">
            <i class="ph-bold ph-sliders-horizontal" style="color:var(--c-chq);"></i> <?= $t['update_title'] ?>
        </h3>
        <form action="/ERP/treasury/cheques/<?= (int)$cheque->id ?>/status" method="POST" style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap;">
            <div style="flex:1; min-width:200px;">
                <label style="display:block; font-size:0.85rem; font-weight:800; color:#475569; margin-bottom:6px;"><?= $t['choose_status'] ?></label>
                <select name="status" class="form-control" style="width:100%; padding:10px 14px; border:1px solid #cbd5e1; border-radius:8px; font-weight:bold; box-sizing:border-box;">
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>><?= $t['status_pending'] ?></option>
                    <option value="collected" <?= $status === 'collected' ? 'selected' : '' ?>><?= $t['status_collected'] ?></option>
                    <option value="bounced" <?= $status === 'bounced' ? 'selected' : '' ?>><?= $t['status_bounced'] ?></option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>><?= $t['status_cancelled'] ?></option>
                </select>
            </div>
            <button type="submit" style="background:var(--c-chq-dark); color:#fff; border:none; padding:12px 24px; border-radius:8px; font-weight:800; cursor:pointer;"><i class="ph-bold ph-check"></i> <?= $t['btn_update'] ?></button>
        </form>
    </div>
</div>

<script>
function purgeControls() {
    const selectors = [
        '.table-pagination-nav', 
        '.dataTables_info', 
        '.dataTables_paginate', 
        '.pagination',
        '.dataTables_filter',
        '.dataTables_length'
    ];
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