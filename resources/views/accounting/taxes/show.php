<?php
// Path: resources/views/accounting/taxes/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$currency = 'EGP';

global $companyName, $companyLogo;
$cName = $companyName ?? 'NOUR TRUST';
$cLogo = $companyLogo ?? '/assets/img/default-logo.png';

$taxBal = (float)($tax->current_balance ?? 0);

function getTaxTypeLabelView($type) {
    $map = [
        'vat' => 'قيمة مضافة (VAT)',
        'wht' => 'خصم وإضافة (WHT)',
        'sales' => 'ضريبة مبيعات',
        'other' => 'أخرى'
    ];
    return $map[$type] ?? $type;
}
?>

<style>
    :root { 
        --c-tax: #0284c7; 
        --c-tax-dark: #0369a1; 
        --c-tax-light: #e0f2fe;
        --c-border: #cbd5e1; 
        --c-text-dark: #0f172a; 
        --c-text-muted: #64748b; 
    }
    
    .tax-show-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 50px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .print-only-header { display: none; }

    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .btn-action { height: 42px; padding: 0 16px; border-radius: 10px; background: #ffffff; border: 1px solid var(--c-border); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: var(--c-text-muted); font-size: 0.9rem; font-weight: 800; cursor: pointer; gap: 6px; transition: all 0.2s; }
    .btn-action:hover { background: var(--c-tax-light); color: var(--c-tax); border-color: #bae6fd; }
    .btn-print { background: var(--c-text-dark); color: #ffffff; border: none; padding: 0 16px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; }

    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:900px){ .kpi-row { grid-template-columns: 1fr; } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-card h4 { margin: 0 0 6px 0; font-size: 0.78rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-card p { margin: 0; font-size: 1.6rem; font-weight: 900; font-family: monospace; }

    .info-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; border-top: 4px solid var(--c-tax); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
    @media(max-width:768px){ .grid-4 { grid-template-columns: repeat(2, 1fr); } }
    .info-item h5 { margin: 0 0 6px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .info-item p { margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--c-text-dark); }

    .table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .tx-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    .tx-table th { padding: 14px 16px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; border-bottom: 2px solid #e2e8f0; text-align: start; font-size: 0.75rem; }
    .tx-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    /* ========================================================= */
    /* قواعد تنسيق الطباعة الأكاديمية والمؤسسية (A4 Print Fixes)   */
    /* ========================================================= */
    @media print {
        @page { size: A4 portrait; margin: 12mm 15mm; }
        body { background: #ffffff !important; color: #000000 !important; font-size: 10pt; font-family: 'Cairo', 'Inter', sans-serif !important; }
        .nt-sidebar, .top-header, .header-bar, header, aside { display: none !important; }
        
        .tax-show-wrapper { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .print-only-header { display: flex !important; align-items: center; justify-content: space-between; border-bottom: 2px solid var(--c-tax); padding-bottom: 12px; margin-bottom: 20px; }
        
        .kpi-row { grid-template-columns: repeat(3, 1fr) !important; gap: 10px !important; margin-bottom: 20px !important; }
        .kpi-card { border: 1px solid #000000 !important; box-shadow: none !important; padding: 12px !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; page-break-inside: avoid; }
        
        .info-card { border: 1px solid #000000 !important; box-shadow: none !important; padding: 15px !important; page-break-inside: avoid; }
        
        .table-card { border: 1px solid #000000 !important; box-shadow: none !important; page-break-inside: auto; }
        .tx-table { border-collapse: collapse !important; width: 100% !important; }
        .tx-table th { background: #f1f5f9 !important; color: #000000 !important; border: 1px solid #000000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .tx-table td { border: 1px solid #cbd5e1 !important; padding: 8px 10px !important; }
    }
</style>

<div class="tax-show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <!-- الترويسة الرسمية للطباعة -->
    <div class="print-only-header">
        <div style="display:flex; align-items:center; gap:15px;">
            <?php if($cLogo !== '/assets/img/default-logo.png'): ?>
                <img src="<?= htmlspecialchars($cLogo) ?>" alt="Logo" style="max-height:50px;">
            <?php else: ?>
                <i class="ph-fill ph-buildings" style="font-size:2.5rem; color:var(--c-tax);"></i>
            <?php endif; ?>
            <div>
                <h2 style="margin:0; font-size:1.4rem; color:#000; font-weight:900;"><?= htmlspecialchars($cName) ?></h2>
                <span style="font-size:0.85rem; color:#475569; font-weight:bold;">تقرير الموقف الضريبي للحسابات</span>
            </div>
        </div>
        <div style="text-align:end; font-size:0.85rem; font-weight:bold; color:#0f172a;">
            كشف حساب ضريبة وتوجيهها<br>
            تاريخ الطباعة: <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <!-- شريط الإجراءات -->
    <div class="header-bar">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="/ERP/accounting/taxes" class="btn-action" style="width:44px; padding:0;"><i class="ph-bold ph-arrow-right"></i></a>
            <div>
                <h2 style="margin:0; font-size:1.6rem; color:var(--c-text-dark); font-weight:900;"><?= htmlspecialchars($tax->name_ar) ?></h2>
                <p style="margin:4px 0 0 0; color:var(--c-tax-dark); font-weight:800; font-family:monospace;"><?= htmlspecialchars($tax->code) ?> | <?= (float)$tax->tax_rate ?>%</p>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="window.print()" class="btn-print" title="طباعة"><i class="ph-bold ph-printer"></i> طباعة التقرير</button>
            <a href="/ERP/accounting/taxes/<?= $tax->id ?>/edit" class="btn-action"><i class="ph-bold ph-pencil"></i> تعديل الضريبة</a>
        </div>
    </div>

    <!-- مؤشرات الأداء الضريبي -->
    <div class="kpi-row">
        <div class="kpi-card" style="border-bottom: 3px solid var(--c-tax);">
            <h4 style="color:var(--c-tax-dark);">النسبة الضريبية المطبقة</h4>
            <p style="color:var(--c-tax-dark);"><?= (float)$tax->tax_rate ?> <span style="font-size:1rem; font-weight:bold;">%</span></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #0f172a;">
            <h4>إجمالي الحركات المرتبطة</h4>
            <p><?= number_format(count($transactions)) ?></p>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid <?= $taxBal >= 0 ? '#16a34a' : '#dc2626' ?>; background: <?= $taxBal >= 0 ? '#f0fdf4' : '#fef2f2' ?>;">
            <h4 style="color:<?= $taxBal >= 0 ? '#16a34a' : '#dc2626' ?>;">صافي رصيد حساب الضريبة الحالي</h4>
            <p style="color:<?= $taxBal >= 0 ? '#16a34a' : '#dc2626' ?>;"><?= number_format($taxBal, 2) ?> <span style="font-size:0.8rem; font-weight:normal;"><?= $currency ?></span></p>
        </div>
    </div>

    <!-- بطاقة المعلومات -->
    <div class="info-card">
        <div class="grid-4">
            <div class="info-item">
                <h5>كود الضريبة (Identifier)</h5>
                <p style="font-family:monospace; color:var(--c-tax-dark);"><?= htmlspecialchars($tax->code) ?></p>
            </div>
            <div class="info-item">
                <h5>نوع المعاملة</h5>
                <p><?= getTaxTypeLabelView($tax->tax_type) ?></p>
            </div>
            <div class="info-item">
                <h5>رقم الحساب المرتبط (GL)</h5>
                <p style="font-family:monospace; color:#2563eb;"><?= htmlspecialchars($tax->acc_code) ?></p>
            </div>
            <div class="info-item">
                <h5>اسم الحساب بالدفاتر</h5>
                <p><?= htmlspecialchars($tax->acc_name) ?></p>
            </div>
        </div>
    </div>

    <!-- كشف حركات الحساب الضريبي -->
    <div class="table-card">
        <div style="padding:16px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:800; color:var(--c-text-dark); display:flex; justify-content:space-between; align-items:center;">
            <span><i class="ph-bold ph-list-dashes" style="color:var(--c-tax);"></i> سجل القيود المؤثرة على حساب الضريبة (<span dir="ltr"><?= htmlspecialchars($tax->acc_code) ?></span>)</span>
        </div>
        
        <table class="tx-table">
            <thead>
                <tr>
                    <th style="width: 15%;">رقم القيد</th>
                    <th style="width: 15%;">تاريخ القيد</th>
                    <th style="width: 40%;">بيان الحركة (الإقرار)</th>
                    <th style="width: 15%; text-align: center;">مدين (مسترد)</th>
                    <th style="width: 15%; text-align: center;">دائن (مستحق)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($transactions)): ?>
                    <tr><td colspan="5" style="text-align:center; padding:35px; color:#94a3b8; font-weight:700;">لا توجد حركات مالية مسجلة على حساب هذه الضريبة حتى الآن.</td></tr>
                <?php else: foreach($transactions as $tx): ?>
                    <tr>
                        <td><a href="/ERP/accounting/journal-entries/<?= $tx->journal_entry_id ?>" style="font-family:monospace; font-weight:900; color:var(--c-tax-dark); text-decoration:none;">#<?= htmlspecialchars($tx->entry_number) ?></a></td>
                        <td style="font-family:monospace; font-weight:700; color:#0f172a;"><?= htmlspecialchars($tx->entry_date) ?></td>
                        <td style="font-weight:700; color:var(--c-text-dark);"><?= htmlspecialchars($tx->description ?: $tx->entry_desc) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#059669;"><?= number_format((float)$tx->debit, 2) ?></td>
                        <td style="text-align:center; font-family:monospace; font-weight:900; color:#dc2626;"><?= number_format((float)$tx->credit, 2) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>