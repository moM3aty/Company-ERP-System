<?php
// Path: resources/views/purchasing/price_lists/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';
$currency = current_currency();

$t = [
    'ar' => [
        'title' => 'تفاصيل قائمة الأسعار',
        'print' => 'طباعة معتمدة',
        'edit' => 'تعديل',
        'brand_title' => 'قائمة أسعار الموردين المعتمدة (Supplier Price List)',
        'subject' => 'العنوان / الموضوع',
        'supplier' => 'المورد',
        'validity' => 'فترة سريان الأسعار',
        'from' => 'من:',
        'to' => 'إلى:',
        'approved_currency' => 'العملة المعتمدة',
        'items_title' => 'أصناف القائمة (Products & Pricing):',
        'col_prod' => 'كود واسم الصنف',
        'col_moq' => 'MOQ',
        'col_discount' => 'خصم %',
        'col_price' => 'سعر الوحدة',
        'no_items' => 'لا توجد أصناف في هذه القائمة.',
        'notes' => 'ملاحظات وشروط القائمة:',
        'sig_prep' => 'إعداد المشتريات',
        'sig_mgr' => 'اعتماد مدير المشتريات',
        'unregistered' => 'غير متوفر'
    ],
    'en' => [
        'title' => 'Price List Details',
        'print' => 'Print Official Catalog',
        'edit' => 'Edit',
        'brand_title' => 'Approved Supplier Price List Catalog',
        'subject' => 'Subject / Title',
        'supplier' => 'Supplier',
        'validity' => 'Pricing Validity Period',
        'from' => 'From:',
        'to' => 'To:',
        'approved_currency' => 'Approved Currency',
        'items_title' => 'Products & Pricing Catalog:',
        'col_prod' => 'Product Code & Name',
        'col_moq' => 'MOQ',
        'col_discount' => 'Discount %',
        'col_price' => 'Unit Price',
        'no_items' => 'No products registered in this list.',
        'notes' => 'Catalog Terms & Notes:',
        'sig_prep' => 'Prepared By',
        'sig_mgr' => 'Purchasing Manager',
        'unregistered' => 'N/A'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    .pl-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .back-btn { width: 38px; height: 38px; border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: #e0e7ff; color: #4f46e5; border-color: #c7d2fe; }
    
    .btn-act { padding: 9px 18px; border-radius: 8px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    .btn-dark { background: #0f172a; color: #ffffff; }
    .btn-dark:hover { background: #1e293b; }

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); }
    .c-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 24px; }
    .c-brand { font-size: 1.6rem; font-weight: 900; color: #0f172a; margin: 0; }
    
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 28px; border: 1px solid #f1f5f9; }
    .info-box h5 { margin: 0 0 6px 0; color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
    .info-box p { margin: 0; color: #0f172a; font-weight: 800; font-size: 1rem; }

    .table-print { width: 100%; border-collapse: collapse; }
    .table-print th { background: #0f172a; color: #ffffff; padding: 10px 12px; font-size: 0.8rem; text-align: start; }
    .table-print td { padding: 12px 10px; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; color: #1e293b; font-weight: 600; }

    .print-signatures { display: none; }

    /* ========================================= */
    /* إعدادات الطباعة الشاملة والحجب الإجباري */
    /* ========================================= */
    @media print {
        @page { size: A4 portrait; margin: 15mm; }
        
        /* 1. حجب جميع عناصر الصفحة خارج كارت الطباعة */
        body * {
            visibility: hidden !important;
        }
        
        /* 2. إظهار ورقة القائمة فقط */
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

        .print-signatures { display: flex !important; justify-content: space-between !important; margin-top: 50px !important; padding-top: 15px !important; border-top: 2px dashed #0f172a !important; page-break-inside: avoid !important; }
        .sig-box { text-align: center !important; flex: 1 !important; font-weight: 800 !important; }
        .sig-line { border-top: 1px dashed #0f172a !important; width: 60% !important; margin: 30px auto 0 auto !important; }
    }
</style>

<div class="pl-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="/ERP/purchasing/price-lists" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <h3 style="margin:0; font-weight: 800; color: #0f172a;"><?= $t['title'] ?></h3>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/ERP/purchasing/price-lists/<?= $priceList->id ?>/edit" class="btn-act" style="background:#e0e7ff; color:#4f46e5; border:1px solid #c7d2fe;"><i class="ph-bold ph-pencil-simple"></i> <?= $t['edit'] ?></a>
            <button onclick="safePrint()" class="btn-act btn-dark"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="print-canvas">
        <div class="c-header">
            <div>
                <h1 class="c-brand">Nour Trust ERP</h1>
                <p style="margin:4px 0 0 0; color:#64748b; font-weight:600;"><?= $t['brand_title'] ?></p>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <div style="font-family: monospace; font-size: 1.4rem; font-weight: 900; color: #4f46e5;"><?= htmlspecialchars($priceList->list_number) ?></div>
                <div style="margin-top:4px; font-weight:800; color:#059669; text-transform: uppercase;"><?= strtoupper($priceList->status) ?></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5><?= $t['subject'] ?></h5>
                <p><?= htmlspecialchars($priceList->title) ?></p>
            </div>
            <div class="info-box">
                <h5><?= $t['supplier'] ?></h5>
                <p><i class="ph-fill ph-buildings text-slate-400"></i> <?= htmlspecialchars($priceList->supplier_name ?? '---') ?></p>
            </div>
            <div class="info-box">
                <h5><?= $t['validity'] ?></h5>
                <p style="color: #059669; font-size:0.9rem;"><?= $t['from'] ?> <?= $priceList->valid_from ?> <span style="color:#dc2626; margin:0 8px;"><?= $t['to'] ?> <?= $priceList->valid_to ?></span></p>
            </div>
            <div class="info-box">
                <h5><?= $t['approved_currency'] ?></h5>
                <p style="font-family: monospace; font-size:1.1rem; color:#4f46e5;"><?= htmlspecialchars($currency) ?></p>
            </div>
        </div>

        <h4 style="margin: 0 0 16px 0; color: #0f172a; font-weight: 800; font-size: 1.1rem;"><?= $t['items_title'] ?></h4>
        
        <table class="table-print">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 45%;"><?= $t['col_prod'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_moq'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_discount'] ?></th>
                    <th style="width: 20%; text-align: end;"><?= $t['col_price'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($items)): foreach($items as $index => $item): ?>
                    <tr>
                        <td style="text-align: center; color: #94a3b8;"><?= $index + 1 ?></td>
                        <td>
                            <div style="color: #0f172a; font-weight: 800;"><?= htmlspecialchars($item->product_name ?? $t['unregistered']) ?></div>
                            <div style="color: #64748b; font-family: monospace; font-size: 0.8rem;"><?= htmlspecialchars($item->product_code ?? '') ?></div>
                        </td>
                        <td style="text-align: center; font-family: monospace; color:#475569;"><?= number_format($item->min_order_qty, 2) ?></td>
                        <td style="text-align: center; font-family: monospace; color:#d97706;"><?= number_format($item->discount_percent, 2) ?>%</td>
                        <td style="text-align: end; font-family: monospace; font-weight: 900; color:#4f46e5; font-size: 1.05rem;"><?= number_format($item->unit_price, 2) ?> <?= $currency ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 20px;"><?= $t['no_items'] ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if(!empty($priceList->notes)): ?>
            <div style="margin-top: 24px; padding: 16px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
                <strong style="color: #4f46e5; font-size: 0.85rem;"><?= $t['notes'] ?></strong><br>
                <span style="color: #334155; font-weight: 600; font-size: 0.9rem;"><?= nl2br(htmlspecialchars($priceList->notes)) ?></span>
            </div>
        <?php endif; ?>

        <div class="print-signatures">
            <div class="sig-box">
                <div><?= $t['sig_prep'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $t['sig_mgr'] ?></div>
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