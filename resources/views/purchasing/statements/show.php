<?php
// Path: resources/views/purchasing/statements/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';
?>

<style>
    :root { --c-sky: #0284c7; }
    .stmt-wrapper { max-width: 950px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: #0f172a; color: #ffffff; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none;}

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); border-top: 8px solid var(--c-sky); }
    .c-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 24px; }
    .c-brand { font-size: 1.8rem; font-weight: 900; color: #0f172a; margin: 0; }
    
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px; }
    .info-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; }
    .info-box h5 { margin: 0 0 10px 0; color: #64748b; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 6px; }
    .info-box p { margin: 4px 0; color: #0f172a; font-weight: 700; font-size: 0.95rem; }

    .date-filter-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 20px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }

    .table-print { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .table-print th { background: #0f172a; color: #ffffff; padding: 12px; font-size: 0.85rem; text-align: start; border: 1px solid #0f172a; }
    .table-print td { padding: 12px; border: 1px solid #cbd5e1; font-size: 0.9rem; color: #1e293b; font-weight: 600; }

    .totals-area { width: 350px; float: <?= $isAr ? 'left' : 'right' ?>; border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden; }
    .totals-row { display: flex; justify-content: space-between; padding: 10px 16px; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 700; }
    .totals-row.grand { background: #f8fafc; color: var(--c-sky); font-size: 1.2rem; font-weight: 900; border-bottom: none; }

    @media print {
        @page { size: A4 portrait; margin: 12mm; }
        body { background: #fff !important; }
        .nt-sidebar, header, nav, footer, .top-bar, .date-filter-box { display: none !important; }
        .stmt-wrapper { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .print-canvas { border: none !important; box-shadow: none !important; padding: 0 !important; border-top: none !important; }
        .info-box { border: 1px solid #000 !important; background: transparent !important; }
        .table-print th { background: #e2e8f0 !important; color: #000 !important; -webkit-print-color-adjust: exact !important; }
    }
</style>

<div class="stmt-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <a href="/ERP/purchasing/statements" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <button onclick="window.print()" class="btn-act"><i class="ph-bold ph-printer"></i> طباعة كشف الحساب</button>
    </div>

    <!-- Date Range Filter Form -->
    <form action="" method="GET" class="date-filter-box">
        <span style="font-weight:800; color:#0f172a;"><i class="ph-bold ph-calendar"></i> الفترة المالية:</span>
        <input type="date" name="from_date" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px;" value="<?= htmlspecialchars($fromDate) ?>">
        <span>إلى</span>
        <input type="date" name="to_date" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px;" value="<?= htmlspecialchars($toDate) ?>">
        <button type="submit" style="background:#0284c7; color:#fff; border:none; padding:8px 16px; border-radius:8px; font-weight:800; cursor:pointer;">تحديث الكشف</button>
    </form>

    <div class="print-canvas">
        <div class="c-header">
            <div>
                <h1 class="c-brand">Nour Trust ERP</h1>
                <p style="margin:4px 0 0 0; color:#64748b; font-weight:700;">كشف حساب مورد تفصيلي (Statement of Account)</p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <div style="font-family: monospace; font-size: 1.5rem; font-weight: 900; color: var(--c-sky);"><?= htmlspecialchars($supplier->code) ?></div>
                <div style="margin-top:4px; font-weight:800; color: #0f172a;">التاريخ: <?= date('Y-m-d') ?></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5>بيانات المورد</h5>
                <p style="font-size: 1.1rem; color: var(--c-sky);"><i class="ph-fill ph-buildings"></i> <?= htmlspecialchars($supplier->name_ar) ?></p>
                <p><span style="color:#64748b;">الرقم الضريبي:</span> <?= htmlspecialchars($supplier->tax_number ?? 'غير مسجل') ?></p>
                <p><span style="color:#64748b;">الهاتف:</span> <span style="font-family:monospace;"><?= htmlspecialchars($supplier->phone ?? '---') ?></span></p>
            </div>
            <div class="info-box">
                <h5>ملخص فترة الكشف</h5>
                <p><span style="color:#64748b;">من تاريخ:</span> <span style="font-family:monospace;"><?= $fromDate ?></span></p>
                <p><span style="color:#64748b;">إلى تاريخ:</span> <span style="font-family:monospace;"><?= $toDate ?></span></p>
                <p><span style="color:#64748b;">إجمالي عدد العمليات:</span> <strong><?= count($ledger) ?> عملية</strong></p>
            </div>
        </div>

        <table class="table-print">
            <thead>
                <tr>
                    <th style="width: 12%;">التاريخ</th>
                    <th style="width: 25%;">نوع العملية</th>
                    <th style="width: 18%;">رقم المرجع</th>
                    <th style="width: 15%; text-align: end;">دائن / له (+)</th>
                    <th style="width: 15%; text-align: end;">مدين / عليه (-)</th>
                    <th style="width: 15%; text-align: end;">الرصيد التراكمي</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($ledger)): ?>
                    <tr><td colspan="6" style="text-align:center; padding: 30px; color:#94a3b8;">لا توجد حركة حساب لهذا المورد خلال الفترة المحددة.</td></tr>
                <?php else: foreach($ledger as $tx): ?>
                    <tr>
                        <td style="font-family: monospace; font-size:0.85rem;"><?= $tx->tx_date ?></td>
                        <td><?= htmlspecialchars($tx->tx_title) ?></td>
                        <td style="font-family: monospace; font-weight:800; color:var(--c-sky);"><?= htmlspecialchars($tx->ref_no) ?></td>
                        <td style="text-align: end; font-family: monospace; font-weight:800; color:#0f172a;"><?= $tx->credit > 0 ? number_format($tx->credit, 2) : '-' ?></td>
                        <td style="text-align: end; font-family: monospace; font-weight:800; color:#059669;"><?= $tx->debit > 0 ? number_format($tx->debit, 2) : '-' ?></td>
                        <td style="text-align: end; font-family: monospace; font-weight: 900; color: <?= $tx->running_balance > 0 ? '#dc2626' : '#059669' ?>; background:#f8fafc;">
                            <?= number_format($tx->running_balance, 2) ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <div>
            <div class="totals-area">
                <div class="totals-row">
                    <span>إجمالي المشتريات (له):</span>
                    <span style="font-family: monospace;"><?= number_format($totalCredit, 2) ?></span>
                </div>
                <div class="totals-row" style="color:#059669;">
                    <span>إجمالي المدفوع والمرتجع (عليه):</span>
                    <span style="font-family: monospace;"><?= number_format($totalDebit, 2) ?></span>
                </div>
                <div class="totals-row grand">
                    <span>صافي الرصيد المتبقي:</span>
                    <span style="font-family: monospace;"><?= number_format($runningBalance, 2) ?> EGP</span>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>

        <div style="margin-top: 60px; display: flex; justify-content: space-between; text-align: center; font-weight: 800; font-size: 0.9rem; color: #0f172a;">
            <div style="flex: 1;">
                <div>إعداد إدارة الحسابات</div>
                <div style="border-top: 1px dashed #cbd5e1; width: 60%; margin: 40px auto 0 auto;"></div>
            </div>
            <div style="flex: 1;">
                <div>تأكيد ومطابقة المورد (توقيع وختم)</div>
                <div style="border-top: 1px dashed #cbd5e1; width: 60%; margin: 40px auto 0 auto;"></div>
            </div>
        </div>
    </div>
</div>