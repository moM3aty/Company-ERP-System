<?php
// Path: resources/views/inventory/adjustments/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

if (!isset($adj) || !$adj) {
    header("Location: /ERP/inventory/adjustments");
    exit;
}

$adjId         = (int)($adj->id ?? 0);
$adjNumber     = htmlspecialchars((string)($adj->adjustment_number ?? '---'));
$adjDate       = htmlspecialchars((string)($adj->adjustment_date ?? date('Y-m-d')));
$warehouseName = htmlspecialchars((string)($adj->warehouse_name ?? '---'));
$warehouseCode = htmlspecialchars((string)($adj->warehouse_code ?? '---'));
$reason        = htmlspecialchars((string)($adj->reason ?? '---'));
$notes         = htmlspecialchars((string)($adj->notes ?? ''));
$status        = (string)($adj->status ?? 'draft');
$type          = (string)($adj->adjustment_type ?? 'addition');

$statusBadges = [
    'draft'    => ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $isAr ? 'مسودة' : 'Draft'],
    'approved' => ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => $isAr ? 'معتمد ومرحل' : 'Approved'],
    'cancelled' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => $isAr ? 'ملغى' : 'Cancelled']
];
$currentBadge = $statusBadges[$status] ?? $statusBadges['draft'];

$t = [
    'ar' => [
        'print' => 'طباعة إذن التسوية',
        'sub_add' => 'إذن تسوية مخزنية - إضافة (فائض جرد)',
        'sub_sub' => 'إذن تسوية مخزنية - خصم (عجز / تلف)',
        'date_label' => 'التاريخ:',
        'wh_title' => 'المستودع المعني',
        'reason_title' => 'سبب التسوية الرئيسي',
        'notes_title' => 'الملاحظات التفصيلية:',
        'col_code' => 'كود الصنف', 'col_name' => 'اسم ووصف الصنف',
        'col_unit' => 'الوحدة', 'col_qty' => 'الكمية المعدلة',
        'no_items' => 'لا توجد أصناف مسجلة لهذا الإذن.',
        'tot_qty' => 'إجمالي الكميات (Total Quantity):',
        'sig_store' => 'منسق / أمين المخزن',
        'sig_audit' => 'رئيس لجنة الجرد',
        'sig_manager' => 'مدير إدارة المخازن / الاعتماد',
        'sig_sub' => 'الاسم والتوقيع والختم'
    ],
    'en' => [
        'print' => 'Print Adjustment Note',
        'sub_add' => 'Stock Adjustment Note - Addition (Surplus)',
        'sub_sub' => 'Stock Adjustment Note - Deduction (Deficit)',
        'date_label' => 'Date:',
        'wh_title' => 'Warehouse',
        'reason_title' => 'Primary Reason',
        'notes_title' => 'Detailed Notes:',
        'col_code' => 'Item Code', 'col_name' => 'Item Name & Description',
        'col_unit' => 'Unit', 'col_qty' => 'Adjusted Qty',
        'no_items' => 'No items registered for this adjustment note.',
        'tot_qty' => 'Total Quantity:',
        'sig_store' => 'Storekeeper / Coordinator',
        'sig_audit' => 'Audit Committee Head',
        'sig_manager' => 'Warehouse Manager / Approval',
        'sig_sub' => 'Name, Signature & Stamp'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-amber: #f59e0b;
        --c-amber-dark: #d97706;
        --c-amber-light: #fef3c7;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .doc-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: #0f172a; color: #ffffff; transition: 0.2s; }
    .btn-act:hover { background: #1e293b; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-amber-light); color: var(--c-amber-dark); border-color: #fcd34d; }

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); border-top: 8px solid var(--c-amber); }
    .c-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; }
    
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 24px 0; }
    .info-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; }
    .info-card h5 { margin: 0 0 10px 0; color: var(--c-text-muted); font-size: 0.8rem; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 6px; }
    .info-card p { margin: 4px 0; color: #0f172a; font-weight: 700; font-size: 0.95rem; }

    .table-print { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .table-print th { background: #0f172a; color: #ffffff; padding: 12px 16px; font-size: 0.85rem; text-align: start; border: 1px solid #0f172a; }
    .table-print td { padding: 12px 16px; border: 1px solid #cbd5e1; font-size: 0.95rem; color: #1e293b; font-weight: 600; vertical-align: middle; }

    .signatures-grid { margin-top: 60px; display: flex; justify-content: space-between; text-align: center; font-weight: 800; font-size: 0.9rem; color: #0f172a; }
    .sig-box { flex: 1; padding: 0 10px; }
    .sig-line { border-top: 1px dashed #cbd5e1; width: 80%; margin: 40px auto 0 auto; }

    /* إخفاء عناصر التصفح والبحث المضافة تلقائياً */
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
        .print-canvas, .print-canvas * { visibility: visible !important; }
        .print-canvas { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; border: none !important; box-shadow: none !important; padding: 0 !important; }
        .info-card { border: 1px solid #000 !important; background: transparent !important; }
        .table-print th { background: #e2e8f0 !important; color: #000 !important; border-color: #000 !important; -webkit-print-color-adjust: exact !important; }
        .table-print td { border-color: #000 !important; }
        .dataTables_wrapper, .table-pagination-nav { display: none !important; visibility: hidden !important; }
    }
</style>

<div class="doc-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <a href="/ERP/inventory/adjustments" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <button onclick="safePrint()" class="btn-act"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
    </div>

    <div class="print-canvas">
        <div class="c-header">
            <div>
                <h1 style="margin:0; font-size:1.8rem; font-weight:900; color:#0f172a;">Nour Trust ERP</h1>
                <p style="margin:4px 0 0 0; color:#64748b; font-weight:700;">
                    <?= $type === 'addition' ? $t['sub_add'] : $t['sub_sub'] ?>
                </p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <div style="font-family: monospace; font-size: 1.8rem; font-weight: 900; color: var(--c-amber);"><i class="ph-bold ph-hash"></i> <?= $adjNumber ?></div>
                <div style="margin-top:6px; font-weight:800; color: #0f172a;"><?= $t['date_label'] ?> <?= $adjDate ?></div>
                <div style="margin-top:6px;">
                    <span style="background:<?= $currentBadge['bg'] ?>; color:<?= $currentBadge['color'] ?>; padding:4px 12px; border-radius:8px; font-weight:800; font-size:0.8rem; border:1px solid currentColor;">
                        <?= $currentBadge['label'] ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <h5><?= $t['wh_title'] ?></h5>
                <p style="font-size:1.1rem; color:var(--c-text-dark);"><?= $warehouseName ?> (<span style="font-family:monospace;"><?= $warehouseCode ?></span>)</p>
            </div>
            <div class="info-card">
                <h5><?= $t['reason_title'] ?></h5>
                <p><?= $reason ?></p>
            </div>
        </div>

        <?php if(!empty($notes)): ?>
            <div style="margin-bottom: 24px; padding: 12px 16px; border-right: 4px solid var(--c-amber); background: #fef3c7; color: #b45309; font-weight: 700; border-radius: 6px;">
                <strong style="color: #92400e;"><?= $t['notes_title'] ?></strong> <?= $notes ?>
            </div>
        <?php endif; ?>

        <table class="table-print">
            <thead>
                <tr>
                    <th style="width: 6%; text-align: center;">#</th>
                    <th style="width: 22%;"><?= $t['col_code'] ?></th>
                    <th style="width: 44%;"><?= $t['col_name'] ?></th>
                    <th style="width: 10%; text-align: center;"><?= $t['col_unit'] ?></th>
                    <th style="width: 18%; text-align: center;"><?= $t['col_qty'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $totalQty = 0; if(!empty($items)): foreach($items as $index => $item): 
                    $pQty = (float)($item->quantity ?? 0);
                    $totalQty += $pQty;
                    $pName = $isAr ? ($item->product_name ?? '') : ($item->product_name ?? '');
                ?>
                    <tr>
                        <td style="text-align: center; color: #64748b; font-weight: 800;"><?= $index + 1 ?></td>
                        <td style="font-family: monospace; font-weight: 800; color: #334155;"><?= htmlspecialchars((string)($item->product_code ?? '---')) ?></td>
                        <td><?= htmlspecialchars((string)$pName) ?></td>
                        <td style="text-align: center;"><?= htmlspecialchars((string)($item->unit ?? 'قطعة')) ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 900; font-size: 1.1rem; color: #0f172a; background: #f8fafc;"><?= number_format($pQty, 2) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px; color: #94a3b8;"><?= $t['no_items'] ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: end; font-weight: 900; background: #f1f5f9;"><?= $t['tot_qty'] ?></td>
                    <td style="text-align: center; font-weight: 900; font-family: monospace; font-size: 1.2rem; color: var(--c-amber-dark); background: #f1f5f9;"><?= number_format($totalQty, 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="signatures-grid">
            <div class="sig-box">
                <div><?= $t['sig_store'] ?></div>
                <div style="color: #64748b; font-size: 0.75rem; margin-top: 4px;"><?= $t['sig_sub'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $t['sig_audit'] ?></div>
                <div style="color: #64748b; font-size: 0.75rem; margin-top: 4px;"><?= $t['sig_sub'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $t['sig_manager'] ?></div>
                <div style="color: #64748b; font-size: 0.75rem; margin-top: 4px;"><?= $t['sig_sub'] ?></div>
                <div class="sig-line"></div>
            </div>
        </div>
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