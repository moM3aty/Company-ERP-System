<?php
// Path: resources/views/accounting/bank_reconciliation/show.php

try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
    $currency = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');

    $flashMsg = $_SESSION['flash_msg'] ?? null;
    $flashErr = $_SESSION['flash_err'] ?? null;
    unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

    global $companyName, $companyLogo;
    $cName = $companyName ?? 'NOUR TRUST';
    $cLogo = $companyLogo ?? '/assets/img/default-logo.png';

    // القاموس المترجم
    $t = [
        'ar' => [
            'back' => 'رجوع للقائمة', 'title' => 'مذكرة تسوية:', 'date_label' => 'تاريخ الكشف:',
            'btn_auto' => 'مطابقة آليّة كاملة', 'btn_fee' => 'إضافة مصروف بنكي مباشر', 'btn_finalize' => 'اعتماد التسوية نهائياً', 'btn_print' => 'طباعة التقرير',
            'print_header' => 'قسم المحاسبة والرقابة المالية', 'print_title' => 'مذكرة تسوية ومطابقة الحساب البنكي', 'print_date' => 'تاريخ الطباعة:',
            'tbl1_title' => 'جدول تسوية الحساب المالي (المعدل)', 'tbl1_st' => 'حالة التسوية:',
            'st_comp' => 'معتمدة ✓', 'st_draft' => 'مسودة قيد المطابقة ✎',
            'bank_side' => 'رصيد البنك (حسب كشف الحساب)', 'bank_end' => 'رصيد نهاية الفترة بكشف البنك:',
            'bank_add' => '(+) يضاف: إيداعات بالطريق (غير مرحلة بالكشف):', 'bank_sub' => '(-) يخصم: شيكات وسحوبات معلقة (لم تصرف بعد):', 'bank_adj' => '= رصيد البنك المعدل:',
            'book_side' => 'رصيد الدفاتر العامة (الميزانية)', 'book_curr' => 'الرصيد الحالي بدليل الحسابات (GL Balance):', 'book_adj' => '= رصيد الدفاتر الفعلي:',
            'diff_label' => 'فرق التسوية المحاسبية (Difference):', 'diff_ok' => '(متزنة 100% جاهزة للاعتماد) ✓', 'diff_bad' => '(غير متزنة - حدد الحركات المطابقة بالكشف) ✕',
            'tbl2_title' => 'حركات الحساب البنكي (ضع علامة ✓ أمام كل حركة ظهرت بنجاح في كشف البنك)', 'btn_save_draft' => 'حفظ المطابقة بالدفاتر',
            'col_match' => 'مطابق', 'col_ref' => 'رقم القيد', 'col_date' => 'التاريخ', 'col_desc' => 'البيان / الشرح', 'col_dr' => 'إيداع / مدين (+)', 'col_cr' => 'سحب / دائن (-)',
            'empty_tx' => 'لا توجد حركات مالية مرحلة على هذا الحساب البنكي حتى تاريخ الكشف.',
            'modal_fee_title' => 'إثبات مصروف / عمولة بنكية مباشرة', 'modal_fee_desc' => 'توليد قيد إثبات تلقائي للمصاريف التي ظهرت بكشف البنك ولم تسجل بالدفاتر.',
            'fee_acc' => 'حساب المصروف المديون', 'fee_acc_ph' => '-- اختر حساب العمولات / المصاريف --', 'fee_amt' => 'المبلغ', 'fee_desc' => 'بيان المصروف', 'fee_desc_ph' => 'مثال: مصاريف كشف حساب / عمولة تحويل',
            'btn_cancel' => 'إلغاء', 'btn_save_fee' => 'حفظ وإثبات بالقيد',
            'modal_fin_title' => 'تأكيد اعتماد التسوية البنكية', 'modal_fin_desc' => 'هل أنت متأكد من اعتماد وإغلاق هذه المذكرة؟ تشترط قواعد المحاسبة أن يكون الفرق المالي صفر تماماً (0.00).',
            'btn_fin_cancel' => 'تراجع وإلغاء', 'btn_fin_confirm' => 'تأكيد الاعتماد المالي',
            'confirm_del' => 'هل أنت متأكد من حذف مذكرة التسوية؟', 'confirm_fin' => 'هل أنت متأكد من اعتماد التسوية؟'
        ],
        'en' => [
            'back' => 'Back to List', 'title' => 'Reconciliation:', 'date_label' => 'Stmt Date:',
            'btn_auto' => 'Auto-Match All', 'btn_fee' => 'Add Direct Bank Fee', 'btn_finalize' => 'Finalize Reconciliation', 'btn_print' => 'Print Report',
            'print_header' => 'Accounting & Financial Control Dept', 'print_title' => 'Bank Reconciliation Statement', 'print_date' => 'Print Date:',
            'tbl1_title' => 'Adjusted Financial Reconciliation', 'tbl1_st' => 'Status:',
            'st_comp' => 'Reconciled ✓', 'st_draft' => 'Draft (Pending) ✎',
            'bank_side' => 'Bank Balance (Per Statement)', 'bank_end' => 'Ending Statement Balance:',
            'bank_add' => '(+) Add: Deposits in Transit (Not on Stmt):', 'bank_sub' => '(-) Less: Outstanding Checks (Uncleared):', 'bank_adj' => '= Adjusted Bank Balance:',
            'book_side' => 'General Ledger Balance (Books)', 'book_curr' => 'Current GL Balance:', 'book_adj' => '= Actual Book Balance:',
            'diff_label' => 'Accounting Difference:', 'diff_ok' => '(100% Balanced, Ready to Finalize) ✓', 'diff_bad' => '(Unbalanced - Check cleared items) ✕',
            'tbl2_title' => 'Bank Account Transactions (Check ✓ items appearing on bank statement)', 'btn_save_draft' => 'Save Match Progress',
            'col_match' => 'Cleared', 'col_ref' => 'Entry No.', 'col_date' => 'Date', 'col_desc' => 'Description / Details', 'col_dr' => 'Deposit / Dr (+)', 'col_cr' => 'Withdrawal / Cr (-)',
            'empty_tx' => 'No posted financial transactions on this account up to statement date.',
            'modal_fee_title' => 'Record Direct Bank Fee / Commission', 'modal_fee_desc' => 'Auto-generate a journal entry for fees appearing on statement but not in books.',
            'fee_acc' => 'Debit Expense Account', 'fee_acc_ph' => '-- Select Fee/Expense Account --', 'fee_amt' => 'Amount', 'fee_desc' => 'Fee Description', 'fee_desc_ph' => 'e.g. Account Maintenance Fee',
            'btn_cancel' => 'Cancel', 'btn_save_fee' => 'Save & Post Entry',
            'modal_fin_title' => 'Confirm Bank Reconciliation', 'modal_fin_desc' => 'Are you sure you want to finalize and lock this memo? Accounting rules require a strict 0.00 difference.',
            'btn_fin_cancel' => 'Go Back', 'btn_fin_confirm' => 'Confirm & Finalize',
            'confirm_del' => 'Are you sure you want to delete this memo?', 'confirm_fin' => 'Are you sure you want to finalize?'
        ]
    ][$isRtl ? 'ar' : 'en'];

    $stmtBalance = isset($rec->statement_balance) ? (float)str_replace(',','',(string)$rec->statement_balance) : 0.00;
    $bookBalance = isset($rec->book_balance) ? (float)str_replace(',','',(string)$rec->book_balance) : 0.00;
    $outDeposits = isset($rec->outstanding_deposits) ? (float)str_replace(',','',(string)$rec->outstanding_deposits) : 0.00;
    $outPayments = isset($rec->outstanding_payments) ? (float)str_replace(',','',(string)$rec->outstanding_payments) : 0.00;

    $adjBank = $stmtBalance + $outDeposits - $outPayments;
    $diff = $adjBank - $bookBalance;

    $accNameAr = $rec->acc_name ?? '';
    $accNameEn = $rec->acc_name_en ?? '';
    $aName = $isRtl ? $accNameAr : ($accNameEn !== '' ? $accNameEn : $accNameAr);
    
    $recNum = $rec->reconciliation_number ?? '';
    $accCode = $rec->acc_code ?? '';
    $stmtDate = $rec->statement_date ?? '';
    $status = $rec->status ?? 'draft';
    $notes = $rec->notes ?? '';
    $recId = $rec->id ?? 0;
?>

<style>
    :root { 
        --c-br: #059669; --c-br-dark: #047857; --c-br-light: #ecfdf5;
        --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b; 
    }
    
    .br-show-wrapper { max-width: 1200px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .print-only-header { display: none; }

    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { height: 42px; padding: 0 16px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 0.9rem; font-weight: 800; cursor: pointer; gap: 6px; }
    
    .btn-finalize { background: linear-gradient(135deg, var(--c-br), var(--c-br-dark)); color: #fff; border: none; padding: 11px 22px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2); }
    .btn-save-draft { background: var(--c-text-dark); color: #ffffff; border: none; padding: 11px 20px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }

    .recon-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; overflow: hidden; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
    .recon-header { background: #1e293b; color: #fff; padding: 16px 20px; font-weight: 900; font-size: 1.05rem; display: flex; justify-content: space-between; align-items: center; }
    
    .recon-grid { display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid #e2e8f0; }
    @media(max-width:850px){ .recon-grid { grid-template-columns: 1fr; } }
    
    .recon-col { padding: 20px; }
    .recon-col:first-child { border-inline-end: 1px solid #e2e8f0; background: #fafafa; }
    
    .recon-line { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px dashed #e2e8f0; font-size: 0.92rem; }
    .recon-line.total { border-bottom: none; border-top: 2px solid #0f172a; font-weight: 900; font-size: 1.05rem; padding-top: 14px; margin-top: 6px; }

    .table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .tx-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    .tx-table th { padding: 14px 16px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; border-bottom: 2px solid #e2e8f0; text-align: start; font-size: 0.75rem; }
    .tx-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .checkbox-custom { width: 20px; height: 20px; accent-color: var(--c-br); cursor: pointer; }

    .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 9999; }
    .modal-card { background: #ffffff; border-radius: 20px; width: 100%; max-width: 460px; padding: 32px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); animation: modalIn 0.2s ease-out; }
    @keyframes modalIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .modal-icon-circle { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; background: var(--c-br-light); color: var(--c-br); }
    .modal-actions-row { display: flex; gap: 12px; margin-top: 24px; justify-content: center; }
    .btn-modal-cancel { flex: 1; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; font-weight: 800; cursor: pointer; }
    .btn-modal-confirm { flex: 1; padding: 12px; border-radius: 10px; border: none; background: var(--c-br); color: #ffffff; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }

    .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--c-border); border-radius: 8px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; margin-top: 6px; box-sizing: border-box; }

    @media print {
        @page { size: A4 portrait; margin: 12mm 15mm; }
        body { background: #ffffff !important; color: #000000 !important; font-size: 10pt; }
        .header-bar, .btn-save-draft, .modal-overlay, button, form button { display: none !important; }
        .br-show-wrapper { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .print-only-header { display: flex !important; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--c-br); padding-bottom: 12px; margin-bottom: 20px; }
        .recon-card { border: 1px solid #000000 !important; box-shadow: none !important; margin-bottom: 20px; page-break-inside: avoid; }
        .recon-header { background: #0f172a !important; color: #ffffff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;}
        .recon-col:first-child { border-inline-end: 1px solid #000000 !important; background: #f8fafc !important; -webkit-print-color-adjust: exact; }
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; page-break-inside: auto; }
        .tx-table { border-collapse: collapse !important; width: 100% !important; }
        .tx-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; }
        .tx-table td { border: 1px solid #cbd5e1 !important; padding: 6px 8px !important; }
    }
</style>

<div class="br-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <div class="print-only-header">
        <div style="display:flex; align-items:center; gap:15px;">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars((string)$cLogo) ?>" alt="Logo" style="max-height:50px;">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:2.5rem; color:var(--c-br);"></i>
            <?php endif; ?>
            <div>
                <h2 style="margin:0; font-size:1.4rem; color:#000; font-weight:900;"><?= htmlspecialchars((string)$cName) ?></h2>
                <span style="font-size:0.85rem; color:#475569; font-weight:bold;"><?= $t['print_header'] ?></span>
            </div>
        </div>
        <div style="text-align:end; font-size:0.85rem; font-weight:bold; color:#0f172a;">
            <?= $t['print_title'] ?><br>
            <?= $t['print_date'] ?> <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/accounting/bank-reconciliation" class="btn-action" style="width:44px; padding:0;"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= $t['title'] ?> <span style="font-family:monospace; color:var(--c-br);"><?= htmlspecialchars((string)$recNum) ?></span></h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-size:0.9rem;"><?= htmlspecialchars((string)$accCode) ?> - <?= htmlspecialchars((string)$aName) ?> | <?= $t['date_label'] ?> <?= htmlspecialchars((string)$stmtDate) ?></p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <?php if($status === 'draft'): ?>
                <form action="/ERP/accounting/bank-reconciliation/<?= $recId ?>/auto-match" method="POST" style="display:inline;">
                    <button type="submit" class="btn-action"><i class="ph-bold ph-lightning" style="color:#d97706;"></i> <?= $t['btn_auto'] ?></button>
                </form>
                <button type="button" class="btn-action" onclick="openFeeModal()"><i class="ph-bold ph-plus-circle" style="color:var(--c-br);"></i> <?= $t['btn_fee'] ?></button>
                <button type="button" class="btn-finalize" onclick="openFinalizeModal()"><i class="ph-bold ph-check-circle"></i> <?= $t['btn_finalize'] ?></button>
            <?php endif; ?>
            <button onclick="window.print()" class="btn-action" title="طباعة"><i class="ph-bold ph-printer"></i> <?= $t['btn_print'] ?></button>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="recon-card">
        <div class="recon-header">
            <span><i class="ph-bold ph-scales"></i> <?= $t['tbl1_title'] ?></span>
            <span style="font-size:0.85rem; font-family:monospace; background:rgba(255,255,255,0.15); padding:4px 10px; border-radius:6px;">
                <?= $t['tbl1_st'] ?> <?= $status === 'reconciled' ? $t['st_comp'] : $t['st_draft'] ?>
            </span>
        </div>
        
        <div class="recon-grid">
            <div class="recon-col">
                <h4 style="margin:0 0 14px 0; color:#2563eb; font-weight:900;"><i class="ph-bold ph-bank"></i> <?= $t['bank_side'] ?></h4>
                <div class="recon-line">
                    <span><?= $t['bank_end'] ?></span>
                    <span style="font-family:monospace; font-weight:800;" id="txtStmtBal"><?= number_format($stmtBalance, 2) ?> <?= $currency ?></span>
                </div>
                <div class="recon-line" style="color:#059669;">
                    <span><?= $t['bank_add'] ?></span>
                    <span style="font-family:monospace; font-weight:800;" id="txtOutDep">+ <?= number_format($outDeposits, 2) ?> <?= $currency ?></span>
                </div>
                <div class="recon-line" style="color:#dc2626;">
                    <span><?= $t['bank_sub'] ?></span>
                    <span style="font-family:monospace; font-weight:800;" id="txtOutPay">- <?= number_format($outPayments, 2) ?> <?= $currency ?></span>
                </div>
                <div class="recon-line total" style="color:#2563eb;">
                    <span><?= $t['bank_adj'] ?></span>
                    <span style="font-family:monospace;" id="txtAdjBank"><?= number_format($adjBank, 2) ?> <?= $currency ?></span>
                </div>
            </div>

            <div class="recon-col">
                <h4 style="margin:0 0 14px 0; color:#0f172a; font-weight:900;"><i class="ph-bold ph-notebook"></i> <?= $t['book_side'] ?></h4>
                <div class="recon-line">
                    <span><?= $t['book_curr'] ?></span>
                    <span style="font-family:monospace; font-weight:800;"><?= number_format($bookBalance, 2) ?> <?= $currency ?></span>
                </div>
                <div class="recon-line" style="color:#64748b;">
                    <span>تعديلات وعمولات غير مسجلة بالدفاتر:</span>
                    <span style="font-family:monospace; font-weight:800;">0.00</span>
                </div>
                <div class="recon-line total" style="color:#0f172a;">
                    <span><?= $t['book_adj'] ?></span>
                    <span style="font-family:monospace;"><?= number_format($bookBalance, 2) ?> <?= $currency ?></span>
                </div>
            </div>
        </div>

        <div id="diffBanner" style="padding:16px 20px; text-align:center; font-weight:900; font-size:1.1rem; background: <?= abs($diff) < 0.01 ? '#ecfdf5' : '#fef2f2' ?>; color: <?= abs($diff) < 0.01 ? '#059669' : '#dc2626' ?>; border-top:1px solid #e2e8f0;">
            <?= $t['diff_label'] ?> <span id="txtDiffVal" style="font-family:monospace; font-size:1.3rem;"><?= number_format($diff, 2) ?></span> <?= $currency ?>
            <?php if(abs($diff) < 0.01): ?>
                <span style="font-size:0.85rem; font-weight:bold; margin-inline-start:10px;"><?= $t['diff_ok'] ?></span>
            <?php else: ?>
                <span style="font-size:0.85rem; font-weight:bold; margin-inline-start:10px;"><?= $t['diff_bad'] ?></span>
            <?php endif; ?>
        </div>
    </div>

    <form action="/ERP/accounting/bank-reconciliation/<?= $recId ?>/match" method="POST" id="matchForm">
        <div class="table-card">
            <div style="padding:16px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:800; color:var(--c-text-dark); display:flex; justify-content:space-between; align-items:center;">
                <span><i class="ph-bold ph-list-checks" style="color:var(--c-br);"></i> <?= $t['tbl2_title'] ?></span>
                <?php if($status === 'draft'): ?>
                    <button type="submit" class="btn-save-draft"><i class="ph-bold ph-floppy-disk"></i> <?= $t['btn_save_draft'] ?></button>
                <?php endif; ?>
            </div>
            
            <table class="tx-table">
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;"><?= $t['col_match'] ?></th>
                        <th style="width: 15%;"><?= $t['col_ref'] ?></th>
                        <th style="width: 12%;"><?= $t['col_date'] ?></th>
                        <th style="width: 38%;"><?= $t['col_desc'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_dr'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_cr'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($transactions)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:35px; color:#94a3b8; font-weight:700;"><?= $t['empty_tx'] ?></td></tr>
                    <?php else: foreach($transactions as $tx): 
                        $dr = isset($tx->debit) ? (float)$tx->debit : 0.00;
                        $cr = isset($tx->credit) ? (float)$tx->credit : 0.00;
                        $isCleared = !empty($tx->is_cleared);
                    ?>
                        <tr style="<?= $isCleared ? 'background:#f0fdf4;' : '' ?>" class="tx-row">
                            <td style="text-align:center;">
                                <input type="checkbox" name="cleared_items[]" value="<?= htmlspecialchars((string)($tx->id ?? '')) ?>" 
                                       data-debit="<?= $dr ?>" data-credit="<?= $cr ?>"
                                       class="checkbox-custom tx-checkbox" 
                                       <?= $isCleared ? 'checked' : '' ?> 
                                       <?= $status === 'reconciled' ? 'disabled' : '' ?>
                                       onchange="recalculateLiveTotals()">
                            </td>
                            <td><a href="/ERP/accounting/journal-entries/<?= htmlspecialchars((string)($tx->journal_entry_id ?? '')) ?>" style="font-family:monospace; font-weight:900; color:var(--c-br); text-decoration:none;">#<?= htmlspecialchars((string)($tx->entry_number ?? '')) ?></a></td>
                            <td style="font-family:monospace; font-weight:700; color:#0f172a;"><?= htmlspecialchars((string)($tx->entry_date ?? '')) ?></td>
                            <td style="font-weight:700; color:var(--c-text-dark);"><?= htmlspecialchars((string)($tx->description ?: ($tx->entry_desc ?? ''))) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format($dr, 2) ?></td>
                            <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format($cr, 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<!-- Modal 1: مصروفات بنكية -->
<div id="feeModal" class="modal-overlay">
    <div class="modal-card" style="text-align:start; max-width:500px;">
        <h3 style="margin:0 0 6px 0; font-size:1.2rem; font-weight:900; color:var(--c-text-dark);"><i class="ph-bold ph-plus-circle" style="color:var(--c-br);"></i> <?= $t['modal_fee_title'] ?></h3>
        <p style="margin:0 0 16px 0; color:var(--c-text-muted); font-size:0.85rem;"><?= $t['modal_fee_desc'] ?></p>
        
        <form action="/ERP/accounting/bank-reconciliation/<?= $recId ?>/add-fee" method="POST">
            <div style="margin-bottom:14px;">
                <label style="font-size:0.85rem; font-weight:800;"><?= $t['fee_acc'] ?> <span style="color:red">*</span></label>
                <select name="expense_account_id" class="form-control" required>
                    <option value=""><?= $t['fee_acc_ph'] ?></option>
                    <?php if(!empty($expenseAccounts)): foreach($expenseAccounts as $ea): 
                        $eaName = $isRtl ? ($ea->name_ar ?? '') : ($ea->name_en ?: ($ea->name_ar ?? ''));
                    ?>
                        <option value="<?= htmlspecialchars((string)($ea->id ?? '')) ?>"><?= htmlspecialchars((string)($ea->code ?? '')) ?> - <?= htmlspecialchars($eaName) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            
            <div style="margin-bottom:14px;">
                <label style="font-size:0.85rem; font-weight:800;"><?= $t['fee_amt'] ?> (<?= $currency ?>) <span style="color:red">*</span></label>
                <input type="number" step="0.01" min="0.01" name="fee_amount" class="form-control" placeholder="0.00" required style="font-family:monospace; font-weight:bold;">
            </div>

            <div style="margin-bottom:14px;">
                <label style="font-size:0.85rem; font-weight:800;"><?= $t['fee_desc'] ?></label>
                <input type="text" name="fee_description" class="form-control" placeholder="<?= $t['fee_desc_ph'] ?>">
            </div>

            <div class="modal-actions-row">
                <button type="button" onclick="closeFeeModal()" class="btn-modal-cancel"><?= $t['btn_cancel'] ?></button>
                <button type="submit" class="btn-modal-confirm"><i class="ph-bold ph-check"></i> <?= $t['btn_save_fee'] ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: اعتماد التسوية -->
<div id="finalizeModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-icon-circle">
            <i class="ph-bold ph-check-circle" style="font-size:2.2rem; color:var(--c-br);"></i>
        </div>
        <h3 style="margin: 0 0 8px 0; font-size: 1.3rem; font-weight: 900; color: var(--c-text-dark);"><?= $t['modal_fin_title'] ?></h3>
        <p style="margin: 0; color: var(--c-text-muted); font-size: 0.9rem; line-height: 1.5; font-weight: 600;">
            <?= $t['modal_fin_desc'] ?>
        </p>
        
        <div class="modal-actions-row">
            <button type="button" onclick="closeFinalizeModal()" class="btn-modal-cancel"><?= $t['btn_fin_cancel'] ?></button>
            <form action="/ERP/accounting/bank-reconciliation/<?= $recId ?>/finalize" method="POST" style="flex: 1;">
                <button type="submit" class="btn-modal-confirm" style="width: 100%;" onclick="return confirm('<?= $t['confirm_fin'] ?>');">
                    <i class="ph-bold ph-check"></i> <?= $t['btn_fin_confirm'] ?>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
const statementBalance = <?= $stmtBalance ?>;
const bookBalance = <?= $bookBalance ?>;
const currSymbol = ' <?= $currency ?>';

function recalculateLiveTotals() {
    let checkboxes = document.querySelectorAll('.tx-checkbox');
    let outDeposits = 0;
    let outPayments = 0;

    checkboxes.forEach(cb => {
        let row = cb.closest('tr');
        let debit = parseFloat(cb.dataset.debit) || 0;
        let credit = parseFloat(cb.dataset.credit) || 0;

        if (cb.checked) {
            row.style.background = '#f0fdf4';
        } else {
            row.style.background = 'transparent';
            outDeposits += debit;
            outPayments += credit;
        }
    });

    let adjBank = statementBalance + outDeposits - outPayments;
    let diff = adjBank - bookBalance;

    document.getElementById('txtOutDep').innerText = '+ ' + outDeposits.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + currSymbol;
    document.getElementById('txtOutPay').innerText = '- ' + outPayments.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + currSymbol;
    document.getElementById('txtAdjBank').innerText = adjBank.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + currSymbol;
    document.getElementById('txtDiffVal').innerText = diff.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    let banner = document.getElementById('diffBanner');
    if (Math.abs(diff) < 0.01) {
        banner.style.background = '#ecfdf5';
        banner.style.color = '#059669';
    } else {
        banner.style.background = '#fef2f2';
        banner.style.color = '#dc2626';
    }
}

function openFeeModal() { document.getElementById('feeModal').style.display = 'flex'; }
function closeFeeModal() { document.getElementById('feeModal').style.display = 'none'; }
function openFinalizeModal() { document.getElementById('finalizeModal').style.display = 'flex'; }
function closeFinalizeModal() { document.getElementById('finalizeModal').style.display = 'none'; }
</script>

<?php 
} catch (Throwable $e) {
    echo "<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 View Error (show.php)</h3>" . htmlspecialchars($e->getMessage()) . "<br>Line: " . $e->getLine() . "</div>";
}
?>