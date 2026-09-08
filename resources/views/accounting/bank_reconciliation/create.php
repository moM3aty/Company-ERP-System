<?php
// Path: resources/views/accounting/bank_reconciliation/create.php

try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
    $currency = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');

    $flashMsg = $_SESSION['flash_msg'] ?? null;
    $flashErr = $_SESSION['flash_err'] ?? null;
    unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

    $t = [
        'ar' => [
            'back' => 'رجوع للقائمة', 'title' => 'إنشاء مذكرة تسوية بنكية جديدة', 'acc' => 'الحساب البنكي', 'acc_ph' => '-- اختر الحساب البنكي --',
            'branch' => 'الفرع المخصص', 'branch_ph' => '-- عام (المركز الرئيسي) --', 'date' => 'تاريخ كشف الحساب',
            'bal' => 'رصيد نهاية الفترة بكشف البنك', 'notes' => 'ملاحظات وبيان التسوية', 'notes_ph' => 'أدخل أي ملاحظات حول كشف الحساب أو الفترة...',
            'btn_submit' => 'بدء ورشة التسوية والمطابقة'
        ],
        'en' => [
            'back' => 'Back to List', 'title' => 'Create New Bank Reconciliation', 'acc' => 'Bank Account', 'acc_ph' => '-- Select Account --',
            'branch' => 'Assigned Branch', 'branch_ph' => '-- General (HQ) --', 'date' => 'Statement Date',
            'bal' => 'Statement Ending Balance', 'notes' => 'Notes & Description', 'notes_ph' => 'Enter notes about this statement...',
            'btn_submit' => 'Start Reconciliation Process'
        ]
    ][$isRtl ? 'ar' : 'en'];
?>

<style>
    :root { 
        --c-br: #059669; --c-br-dark: #047857; --c-br-light: #ecfdf5;
        --c-border: #cbd5e1; --c-text-dark: #0f172a; --c-text-muted: #64748b;
    }
    .form-wrapper { max-width: 800px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .form-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .form-title { font-size: 1.4rem; font-weight: 900; color: var(--c-text-dark); margin: 0 0 20px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; }
    .form-group { margin-bottom: 20px; }
    .input-label { font-size: 0.9rem; font-weight: 800; color: var(--c-text-dark); margin-bottom: 6px; display: block; }
    .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--c-br); background: #ffffff; }
    .btn-submit { background: linear-gradient(135deg, var(--c-br), var(--c-br-dark)); color: #ffffff; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; font-size: 1rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <a href="/ERP/accounting/bank-reconciliation" style="text-decoration:none; color:var(--c-text-muted); font-weight:800; display:inline-flex; align-items:center; gap:6px;"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i> <?= $t['back'] ?></a>
    </div>

    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="form-card">
        <h2 class="form-title"><i class="ph-duotone ph-bank" style="color:var(--c-br);"></i> <?= $t['title'] ?></h2>
        
        <form action="/ERP/accounting/bank-reconciliation/store" method="POST">
            <div class="form-group">
                <label class="input-label"><?= $t['acc'] ?> <span style="color:red">*</span></label>
                <select name="account_id" class="form-control" required>
                    <option value=""><?= $t['acc_ph'] ?></option>
                    <?php if(!empty($bankAccounts)): foreach($bankAccounts as $acc): 
                        $aName = $isRtl ? ($acc->name_ar ?? '') : ($acc->name_en ?: ($acc->name_ar ?? ''));
                    ?>
                        <option value="<?= $acc->id ?? 0 ?>"><?= htmlspecialchars((string)($acc->code ?? '')) ?> - <?= htmlspecialchars($aName) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>

            <?php if(!empty($branches)): ?>
            <div class="form-group">
                <label class="input-label"><?= $t['branch'] ?></label>
                <select name="branch_id" class="form-control">
                    <option value="0"><?= $t['branch_ph'] ?></option>
                    <?php foreach($branches as $b): 
                        $bName = $isRtl ? ($b->name_ar ?? '') : ($b->name_en ?: ($b->name_ar ?? ''));
                    ?>
                        <option value="<?= $b->id ?? 0 ?>"><?= htmlspecialchars($bName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="input-label"><?= $t['date'] ?> <span style="color:red">*</span></label>
                    <input type="date" name="statement_date" class="form-control" value="<?= date('Y-m-d') ?>" required style="font-family:monospace;">
                </div>

                <div class="form-group">
                    <label class="input-label"><?= $t['bal'] ?> (<?= $currency ?>) <span style="color:red">*</span></label>
                    <input type="number" step="0.01" name="statement_balance" class="form-control" placeholder="0.00" required style="font-family:monospace; font-weight:900; font-size:1.1rem; color:#2563eb;">
                </div>
            </div>

            <div class="form-group">
                <label class="input-label"><?= $t['notes'] ?></label>
                <textarea name="notes" class="form-control" rows="3" placeholder="<?= $t['notes_ph'] ?>"></textarea>
            </div>

            <div style="text-align:end; margin-top:24px;">
                <button type="submit" class="btn-submit"><i class="ph-bold ph-plus"></i> <?= $t['btn_submit'] ?></button>
            </div>
        </form>
    </div>
</div>

<?php 
} catch (Throwable $e) {
    echo "<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 View Error (create.php)</h3>" . htmlspecialchars($e->getMessage()) . "<br>Line: " . $e->getLine() . "</div>";
}
?>