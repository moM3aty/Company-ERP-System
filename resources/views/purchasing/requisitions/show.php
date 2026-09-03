<?php
// Path: resources/views/purchasing/requisitions/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';
$currency = current_currency();

function getPrStatusLabel($status, $isAr) {
    $map = [
        'draft' => ['color' => '#64748b', 'label' => $isAr ? 'مسودة' : 'Draft'],
        'pending' => ['color' => '#d97706', 'label' => $isAr ? 'قيد الاعتماد' : 'Pending'],
        'approved' => ['color' => '#0d9488', 'label' => $isAr ? 'معتمد' : 'Approved'],
        'rejected' => ['color' => '#dc2626', 'label' => $isAr ? 'مرفوض' : 'Rejected'],
        'completed' => ['color' => '#2563eb', 'label' => $isAr ? 'تم الشراء' : 'Completed']
    ];
    return $map[$status] ?? $map['draft'];
}

$statusData = getPrStatusLabel($requestData->status, $isAr);

$t = [
    'ar' => [
        'title' => 'تفاصيل طلب الشراء (PR)',
        'print' => 'طباعة الوثيقة',
        'edit' => 'تعديل',
        'brand_title' => 'طلب شراء داخلي (Purchase Requisition)',
        'dept' => 'الإدارة الطالبة',
        'requested_by' => 'مقدم الطلب',
        'request_date' => 'تاريخ الطلب',
        'required_date' => 'تاريخ الاحتياج',
        'items_title' => 'بيان بالأصناف والمواصفات المطلوبة:',
        'col_code' => 'كود الصنف',
        'col_desc' => 'الوصف الفني والمواصفات',
        'col_qty' => 'الكمية المطلوبة',
        'col_price' => 'السعر التقديري',
        'no_items' => 'لا توجد أصناف في هذا الطلب.',
        'notes' => 'مبرر الشراء / ملاحظات:',
        'total_est' => 'إجمالي التكلفة التقديرية للطلب:',
        'sig_requester' => 'مقدم الطلب',
        'sig_dept_head' => 'مدير الإدارة الطالبة',
        'sig_purchasing' => 'إدارة المشتريات',
        'sig_approval' => 'الاعتماد المالي / الإدارة العليا',
        'unregistered' => 'عام / يدوي'
    ],
    'en' => [
        'title' => 'Purchase Requisition Details (PR)',
        'print' => 'Print Document',
        'edit' => 'Edit',
        'brand_title' => 'Internal Purchase Requisition',
        'dept' => 'Requesting Department',
        'requested_by' => 'Requested By',
        'request_date' => 'Request Date',
        'required_date' => 'Required Date',
        'items_title' => 'Requested Items & Specifications:',
        'col_code' => 'Item Code',
        'col_desc' => 'Technical Description & Specs',
        'col_qty' => 'Quantity Required',
        'col_price' => 'Est. Unit Price',
        'no_items' => 'No items found in this requisition.',
        'notes' => 'Justification / Notes:',
        'total_est' => 'Total Estimated Cost:',
        'sig_requester' => 'Requested By',
        'sig_dept_head' => 'Department Head',
        'sig_purchasing' => 'Purchasing Dept.',
        'sig_approval' => 'Financial / Executive Approval',
        'unregistered' => 'Manual Item'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-teal: #0d9488;
        --c-teal-light: #ccfbf1;
    }

    .pr-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-teal-light); color: var(--c-teal); border-color: #99f6e4; }
    
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; }
    .btn-dark { background: #0f172a; color: #ffffff; }
    .btn-dark:hover { background: #1e293b; }
    .btn-edit { background: #ccfbf1; color: #0f766e; border: 1px solid #99f6e4; }

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); border-top: 6px solid var(--c-teal); }
    .c-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 24px; }
    .c-brand { font-size: 1.6rem; font-weight: 900; color: #0f172a; margin: 0; }
    
    .info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 28px; border: 1px solid #f1f5f9; }
    @media(max-width: 768px) { .info-grid { grid-template-columns: 1fr 1fr; } }
    .info-box h5 { margin: 0 0 6px 0; color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
    .info-box p { margin: 0; color: #0f172a; font-weight: 800; font-size: 0.95rem; }

    .table-print { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .table-print th { background: #0f172a; color: #ffffff; padding: 12px; font-size: 0.8rem; text-align: start; }
    .table-print td { padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; color: #1e293b; font-weight: 600; }

    .total-box { display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 16px 24px; border-radius: 12px; border: 1px dashed #cbd5e1; }
    .total-box h4 { margin: 0; color: #475569; font-size: 1.1rem; font-weight: 800; }
    .total-box .val { font-size: 1.6rem; font-weight: 900; color: var(--c-teal); font-family: monospace; }

    .print-signatures { display: none; }

    /* ========================================= */
    /* إعدادات الطباعة الشاملة والحجب الإجباري */
    /* ========================================= */
    @media print {
        @page { size: A4 portrait; margin: 15mm; }
        
        /* 1. حجب جميع عناصر الصفحة خارج كارت التقييم */
        body * {
            visibility: hidden !important;
        }
        
        /* 2. إظهار ورقة الطلب فقط */
        .print-canvas, .print-canvas * {
            visibility: visible !important;
        }
        
        .print-canvas {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
        }

        /* 3. الإخفاء الجذري لكل مكونات البحث، الترقيم، و DataTables */
        .table-pagination-nav,
        .table-pagination-nav *,
        .print-canvas .table-pagination-nav,
        .dataTables_info, .dataTables_paginate, .pagination {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            height: 0 !important;
            width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* 4. إخفاء عمود الترقيم (#) من الجدول */
        .table-print th:first-child, .table-print td:first-child { 
            display: none !important; 
        }

        .info-grid { border: 1px solid #0f172a !important; background: transparent !important; page-break-inside: avoid !important; }
        .table-print th { background: #e2e8f0 !important; color: #000 !important; border: 1px solid #000 !important; -webkit-print-color-adjust: exact !important; }
        .table-print td { border: 1px solid #000 !important; color: #000 !important; }
        .total-box { border: 1px solid #000 !important; background: transparent !important; }

        .print-signatures { display: flex !important; justify-content: space-between !important; gap: 15px !important; margin-top: 60px !important; page-break-inside: avoid !important; }
        .sig-box { text-align: center !important; flex: 1 !important; font-weight: 800 !important; font-size: 0.85rem !important; color: #000 !important; }
        .sig-line { border-top: 1px dashed #0f172a !important; width: 80% !important; margin: 35px auto 0 auto !important; }
    }
</style>

<div class="pr-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="/ERP/purchasing/requisitions" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <h3 style="margin:0; font-weight: 800; color: #0f172a;"><?= $t['title'] ?></h3>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/ERP/purchasing/requisitions/<?= $requestData->id ?>/edit" class="btn-act btn-edit"><i class="ph-bold ph-pencil-simple"></i> <?= $t['edit'] ?></a>
            <button onclick="safePrint()" class="btn-act btn-dark"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="print-canvas">
        <div class="c-header">
            <div>
                <h1 class="c-brand">Nour Trust ERP</h1>
                <p style="margin:4px 0 0 0; color:#64748b; font-weight:700;"><?= $t['brand_title'] ?></p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <div style="font-family: monospace; font-size: 1.5rem; font-weight: 900; color: var(--c-teal);"><?= htmlspecialchars($requestData->pr_number) ?></div>
                <div style="margin-top:4px; font-weight:800; color: <?= $statusData['color'] ?>; text-transform: uppercase;"><?= $statusData['label'] ?></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5><?= $t['dept'] ?></h5>
                <p><?= htmlspecialchars($requestData->department ?? '---') ?></p>
            </div>
            <div class="info-box">
                <h5><?= $t['requested_by'] ?></h5>
                <p><?= htmlspecialchars($requestData->requested_by ?? '---') ?></p>
            </div>
            <div class="info-box">
                <h5><?= $t['request_date'] ?></h5>
                <p style="font-family: monospace;"><?= $requestData->request_date ?></p>
            </div>
            <div class="info-box">
                <h5><?= $t['required_date'] ?></h5>
                <p style="color: #dc2626; font-family: monospace;"><?= $requestData->required_date ?></p>
            </div>
        </div>

        <h4 style="margin: 0 0 12px 0; color: #0f172a; font-weight: 900; font-size: 1.1rem;"><?= $t['items_title'] ?></h4>
        
        <table class="table-print">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 20%;"><?= $t['col_code'] ?></th>
                    <th style="width: 40%;"><?= $t['col_desc'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_qty'] ?></th>
                    <th style="width: 20%; text-align: end;"><?= $t['col_price'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($items)): foreach($items as $index => $item): ?>
                    <tr>
                        <td style="text-align: center; color: #94a3b8; font-weight: 800;"><?= $index + 1 ?></td>
                        <td style="font-family: monospace; color: #475569;"><?= htmlspecialchars($item->product_code ?? $t['unregistered']) ?></td>
                        <td style="color: #0f172a; font-weight: 700;"><?= htmlspecialchars($item->description) ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 800; color: #0f172a;"><?= number_format($item->quantity, 2) ?></td>
                        <td style="text-align: end; font-family: monospace; font-weight: 900; color: var(--c-teal);"><?= number_format($item->estimated_price, 2) ?> <?= $currency ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 20px;"><?= $t['no_items'] ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if(!empty($requestData->notes)): ?>
            <div style="margin-bottom: 24px; color: #334155; font-size: 0.9rem; font-weight: 600;">
                <strong style="color: #475569;"><?= $t['notes'] ?></strong> <?= nl2br(htmlspecialchars($requestData->notes)) ?>
            </div>
        <?php endif; ?>

        <div class="total-box">
            <h4><?= $t['total_est'] ?></h4>
            <div class="val"><?= number_format($requestData->total_estimated_value, 2) ?> <span style="font-size:0.9rem; color:#64748b;"><?= $currency ?></span></div>
        </div>

        <div class="print-signatures">
            <div class="sig-box">
                <div><?= $t['sig_requester'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $t['sig_dept_head'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $t['sig_purchasing'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $t['sig_approval'] ?></div>
                <div class="sig-line"></div>
            </div>
        </div>
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