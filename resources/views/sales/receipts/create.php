<?php
// Path: resources/views/sales/receipts/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isRtl();
$currency = current_currency();

$actionUrl = "/ERP/sales/receipts/store";

$t = [
    'ar' => [
        'title' => 'إصدار سند قبض جديد',
        'master_info' => 'البيانات الأساسية للسند',
        'customer' => 'العميل',
        'select_customer' => '-- اختر العميل --',
        'invoice_link' => 'ربط بفاتورة مبيعات (اختياري)',
        'general_receipt' => '-- سند عام (بدون فاتورة محددة) --',
        'due_label' => 'المتبقي:',
        'amount' => 'المبلغ المقبوض',
        'date' => 'تاريخ التحصيل',
        'payment_method' => 'طريقة الدفع',
        'method_cash' => 'نقداً (كاش)',
        'method_bank' => 'تحويل بنكي',
        'method_cheque' => 'شيك بنكي',
        'ref_no' => 'رقم المرجع / الشيك / التحويل',
        'ref_ph' => 'رقم الإيصال، الشيك أو المرجع...',
        'notes' => 'ملاحظات / البيان',
        'notes_ph' => 'البيان الذي سيظهر في سند القبض...',
        'cancel' => 'إلغاء',
        'save' => 'حفظ وسداد السند'
    ],
    'en' => [
        'title' => 'Issue Sales Receipt',
        'master_info' => 'Receipt Details',
        'customer' => 'Customer',
        'select_customer' => '-- Select Customer --',
        'invoice_link' => 'Link to Sales Invoice (Optional)',
        'general_receipt' => '-- General Receipt (No Invoice) --',
        'due_label' => 'Due:',
        'amount' => 'Received Amount',
        'date' => 'Receipt Date',
        'payment_method' => 'Payment Method',
        'method_cash' => 'Cash',
        'method_bank' => 'Bank Transfer',
        'method_cheque' => 'Cheque',
        'ref_no' => 'Reference / Cheque No.',
        'ref_ph' => 'Receipt, cheque or ref number...',
        'notes' => 'Notes / Description',
        'notes_ph' => 'Description to be printed on receipt...',
        'cancel' => 'Cancel',
        'save' => 'Save & Record Receipt'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    .form-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 80px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-title { font-size: 1.8rem; font-weight: 800; color: #0f172a; margin: 0 0 24px 0; display: flex; align-items: center; gap: 12px; }
    .back-btn { width: 36px; height: 36px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    
    .panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .panel-title { font-size: 1.1rem; color: #0f172a; font-weight: 800; margin: 0 0 16px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 8px;}
    
    .input-label { font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px; display: block; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.9rem; outline: none; background: #f8fafc; transition: 0.2s; }
    .form-control:focus { border-color: #10b981; box-shadow: 0 0 0 3px #d1fae5; background: #ffffff;}
    
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    @media(max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn { padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; font-family: inherit; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; border: none; text-decoration: none;}
    .btn-submit { background: linear-gradient(135deg, #10b981, #059669); color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <h2 class="form-title">
        <a href="/ERP/sales/receipts" class="back-btn"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <?= $t['title'] ?>
    </h2>

    <?php if(isset($_SESSION['flash_err'])): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-bold ph-warning-circle"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <form action="<?= $actionUrl ?>" method="POST">
        
        <div class="panel-card">
            <h3 class="panel-title"><i class="ph-duotone ph-money text-emerald-600"></i> <?= $t['master_info'] ?></h3>
            
            <div class="grid-2">
                <div>
                    <label class="input-label"><?= $t['customer'] ?> <span style="color:red">*</span></label>
                    <select name="customer_id" id="customerSelect" class="form-control" required onchange="filterInvoicesByCustomer(this.value)">
                        <option value=""><?= $t['select_customer'] ?></option>
                        <?php foreach($customers ?? [] as $c): ?>
                            <option value="<?= $c->id ?>"><?= htmlspecialchars($isRtl ? ($c->name_ar ?? $c->name_en) : ($c->name_en ?? $c->name_ar)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="input-label"><?= $t['invoice_link'] ?></label>
                    <select name="invoice_id" id="invoiceSelect" class="form-control" onchange="setInvoiceAmount(this)">
                        <option value=""><?= $t['general_receipt'] ?></option>
                        <?php foreach($invoices ?? [] as $inv): ?>
                            <option value="<?= $inv->id ?>" data-customer="<?= $inv->customer_id ?>" data-due="<?= $inv->due_amount ?>">
                                [<?= htmlspecialchars($inv->invoice_number) ?>] - <?= $t['due_label'] ?> <?= number_format($inv->due_amount, 2) ?> <?= htmlspecialchars($currency) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="margin-top: 20px;">
                <div>
                    <label class="input-label"><?= $t['amount'] ?> (<?= htmlspecialchars($currency) ?>) <span style="color:red">*</span></label>
                    <input type="number" step="0.01" name="amount" id="amountInput" class="form-control" style="font-weight: 800; font-family: monospace; font-size: 1.1rem; color: #059669;" placeholder="0.00" required>
                </div>

                <div>
                    <label class="input-label"><?= $t['date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="receipt_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="grid-2" style="margin-top: 20px;">
                <div>
                    <label class="input-label"><?= $t['payment_method'] ?> <span style="color:red">*</span></label>
                    <select name="payment_method" class="form-control" required>
                        <option value="cash"><?= $t['method_cash'] ?></option>
                        <option value="bank_transfer"><?= $t['method_bank'] ?></option>
                        <option value="cheque"><?= $t['method_cheque'] ?></option>
                    </select>
                </div>

                <div>
                    <label class="input-label"><?= $t['ref_no'] ?></label>
                    <input type="text" name="reference_no" class="form-control" placeholder="<?= $t['ref_ph'] ?>">
                </div>
            </div>

            <div style="margin-top: 20px;">
                <label class="input-label"><?= $t['notes'] ?></label>
                <input type="text" name="notes" class="form-control" placeholder="<?= $t['notes_ph'] ?>">
            </div>
        </div>

        <div class="sticky-footer">
            <a href="/ERP/sales/receipts" class="btn" style="background: #ffffff; border: 1px solid #cbd5e1; color: #475569;"><?= $t['cancel'] ?></a>
            <button type="submit" class="btn btn-submit"><i class="ph-bold ph-floppy-disk"></i> <?= $t['save'] ?></button>
        </div>
    </form>
</div>

<script>
    function filterInvoicesByCustomer(custId) {
        const invSelect = document.getElementById('invoiceSelect');
        const options = invSelect.querySelectorAll('option');

        invSelect.value = "";
        options.forEach(opt => {
            if (!opt.value) return;
            if (opt.getAttribute('data-customer') === custId) {
                opt.style.display = "";
            } else {
                opt.style.display = "none";
            }
        });
    }

    function setInvoiceAmount(select) {
        const selectedOpt = select.options[select.selectedIndex];
        const due = selectedOpt.getAttribute('data-due');
        if (due) {
            document.getElementById('amountInput').value = parseFloat(due).toFixed(2);
        }
    }
</script>