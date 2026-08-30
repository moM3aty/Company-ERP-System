<?php
// Path: resources/views/accounting/bank_reconciliation/create.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

// توليد رقم تسوية افتراضي للعرض
$previewRecNum = 'BR-' . date('ym') . str_pad((string)rand(1, 99), 4, '0', STR_PAD_LEFT);
?>

<style>
    :root { 
        --c-br: #059669; 
        --c-br-dark: #047857; 
        --c-br-light: #ecfdf5;
        --c-border: #e2e8f0; 
        --c-text: #0f172a;
        --c-muted: #64748b;
    }
    .form-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .form-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--c-border); }
    .back-btn { width: 44px; height: 44px; border-radius: 12px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-muted); font-size: 1.2rem; }
    
    .form-section { background: #ffffff; border: 1px solid var(--c-border); border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; }
    .form-section::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--c-br); }
    [dir="ltr"] .form-section::before { right: auto; left: 0; }

    .section-title { font-size: 1.1rem; font-weight: 800; color: var(--c-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    .section-title i { color: var(--c-br); font-size: 1.4rem; padding: 8px; background: var(--c-br-light); border-radius: 8px; }
    
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    @media(max-width:768px) { .grid-3, .grid-2 { grid-template-columns: 1fr; } }
    
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .input-label { font-size: 0.88rem; font-weight: 800; color: #475569; }
    .form-control { width: 100%; padding: 12px 14px; border: 1px solid var(--c-border); border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #f8fafc; font-weight: 600; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--c-br); background: #ffffff; }

    .kpi-preview-card { background: var(--c-br-light); border: 1px solid #a7f3d0; border-radius: 14px; padding: 20px; margin-top: 16px; display: flex; justify-content: space-between; align-items: center; }

    .action-bar { display: flex; justify-content: flex-end; gap: 16px; margin-top: 24px; }
    .btn-submit { background: linear-gradient(135deg, var(--c-br), var(--c-br-dark)); color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2); }
</style>

<div class="form-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="form-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/accounting/bank-reconciliation" class="back-btn"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text); font-weight:900;">بدء مذكرة تسوية بنكية جديدة</h2>
                <p style="margin:4px 0 0 0; color:var(--c-muted); font-size:0.9rem;">تحديد الحساب البنكي، الفترة المالية، ورصيد نهاية الفترة بكشف البنك للمطابقة.</p>
            </div>
        </div>
    </div>

    <form action="/ERP/accounting/bank-reconciliation/store" method="POST">
        
        <!-- القسم الأول: بيانات المستند والترویسة -->
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-file-text"></i> بيانات المذكرة والمستند المالي</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label class="input-label">رقم التسوية (تلقائي)</label>
                    <input type="text" class="form-control" style="font-family:monospace; font-weight:bold; color:var(--c-br-dark);" value="<?= $previewRecNum ?>" readonly>
                </div>
                <div class="form-group">
                    <label class="input-label">تاريخ كشف الحساب البنكي <span style="color:red">*</span></label>
                    <input type="date" name="statement_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="input-label">رقم مرجع كشف البنك (Statement Ref)</label>
                    <input type="text" name="statement_reference" class="form-control" placeholder="مثال: STMT-2026-08">
                </div>
            </div>
        </div>

        <!-- القسم الثاني: الحساب البنكي والأرصدة -->
        <div class="form-section">
            <h3 class="section-title"><i class="ph-duotone ph-bank"></i> إعدادات الحساب البنكي والأرصدة</h3>
            
            <div class="grid-2">
                <div class="form-group">
                    <label class="input-label">اختر الحساب البنكي بدليل الحسابات <span style="color:red">*</span></label>
                    <select name="account_id" id="accountSelect" class="form-control" onchange="updateGLBalance()" required>
                        <option value="" data-balance="0.00">-- اختر الحساب البنكي --</option>
                        <?php foreach($bankAccounts as $acc): ?>
                            <option value="<?= $acc->id ?>" data-balance="<?= $acc->current_balance ?>">
                                <?= htmlspecialchars($acc->code) ?> - <?= htmlspecialchars($acc->name_ar) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="input-label">الرصيد الختامي في كشف البنك <span style="color:red">*</span></label>
                    <input type="number" step="0.01" name="statement_balance" id="stmtBalanceInput" class="form-control" style="font-family:monospace; font-weight:800; font-size:1.1rem; color:#2563eb;" placeholder="0.00" required oninput="updateGLBalance()">
                </div>
            </div>

            <!-- بطاقة المعاينة الفورية للرصيد الدفتري والفرق -->
            <div class="kpi-preview-card">
                <div>
                    <h5 style="margin:0 0 4px 0; color:var(--c-br-dark); font-weight:800; font-size:0.85rem;">الرصيد الدفتري الحالي (General Ledger Balance)</h5>
                    <p style="margin:0; font-size:1.4rem; font-weight:900; font-family:monospace; color:#0f172a;" id="glBalanceTxt">0.00 EGP</p>
                </div>
                <div style="text-align:end;">
                    <h5 style="margin:0 0 4px 0; color:#dc2626; font-weight:800; font-size:0.85rem;">الفرق المالي المبدئي</h5>
                    <p style="margin:0; font-size:1.4rem; font-weight:900; font-family:monospace; color:#dc2626;" id="diffPreviewTxt">0.00 EGP</p>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="input-label">ملاحظات وقيد التسوية</label>
                <input type="text" name="notes" class="form-control" placeholder="أدخل أي ملاحظات إضافية بخصوص التسوية البنكية...">
            </div>
        </div>

        <div class="action-bar">
            <a href="/ERP/accounting/bank-reconciliation" style="padding:14px 28px; border:1px solid var(--c-border); border-radius:12px; text-decoration:none; color:#475569; font-weight:800; background:#fff;">إلغاء</a>
            <button type="submit" class="btn-submit"><i class="ph-bold ph-arrow-left"></i> الحفظ والمتابعة لورشة المطابقة</button>
        </div>
    </form>
</div>

<script>
function updateGLBalance() {
    let select = document.getElementById('accountSelect');
    let selectedOption = select.options[select.selectedIndex];
    let glBal = parseFloat(selectedOption.dataset.balance) || 0;
    
    let stmtBalInput = parseFloat(document.getElementById('stmtBalanceInput').value) || 0;
    let diff = stmtBalInput - glBal;

    document.getElementById('glBalanceTxt').innerText = glBal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' EGP';
    document.getElementById('diffPreviewTxt').innerText = diff.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' EGP';
}
</script>