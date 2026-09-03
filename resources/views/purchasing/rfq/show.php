<?php
// Path: resources/views/purchasing/rfq/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';
$currency = current_currency();

$t = [
    'ar' => [
        'title' => 'طلب عرض سعر (RFQ)',
        'print' => 'طباعة الوثيقة (للموردين)',
        'edit' => 'تعديل',
        'brand_title' => 'طلب تسعير بضائع (Request for Quotation)',
        'status_label' => 'الحالة:',
        'issuing_dept' => 'الجهة الطالبة',
        'company_name' => 'مؤسسة نور الثقة (إدارة المشتريات)',
        'issue_date' => 'تاريخ الإصدار',
        'deadline_date' => 'آخر موعد لتلقي العروض (Deadline)',
        'invited_suppliers' => 'الموردون المدعوون للتسعير (يُخفى عند الطباعة):',
        'no_suppliers' => 'لم يتم تحديد موردين.',
        'items_title' => 'الأصناف المطلوبة وتسعيرها من قبلكم:',
        'col_code' => 'كود الصنف',
        'col_desc' => 'الوصف الفني والمواصفات (Technical Specs)',
        'col_qty' => 'الكمية',
        'col_price' => 'السعر المعروض (Unit Price)',
        'price_placeholder' => 'يُترك للمورد',
        'no_items' => 'لا توجد أصناف مطلوبة.',
        'notes' => 'ملاحظات وشروط إضافية:',
        'sig_purchasing' => 'إدارة المشتريات (توقيع وختم)',
        'sig_supplier' => 'توقيع وختم المورد (المقر بالموافقة على التسعير)',
        'unregistered' => '---'
    ],
    'en' => [
        'title' => 'Request for Quotation (RFQ)',
        'print' => 'Print Document (For Bidders)',
        'edit' => 'Edit',
        'brand_title' => 'Request for Quotation Document (RFQ)',
        'status_label' => 'Status:',
        'issuing_dept' => 'Issuing Department',
        'company_name' => 'Nour Trust Corp (Purchasing Dept.)',
        'issue_date' => 'Issue Date',
        'deadline_date' => 'Bidding Deadline Date',
        'invited_suppliers' => 'Invited Suppliers (Hidden from Print):',
        'no_suppliers' => 'No suppliers specified.',
        'items_title' => 'Requested Items for Bidding:',
        'col_code' => 'Item Code',
        'col_desc' => 'Technical Description & Specifications',
        'col_qty' => 'Quantity',
        'col_price' => 'Quoted Unit Price',
        'price_placeholder' => 'To be filled by Bidder',
        'no_items' => 'No items requested.',
        'notes' => 'Instructions & Notes:',
        'sig_purchasing' => 'Purchasing Dept. Stamp',
        'sig_supplier' => 'Supplier Stamp & Approval',
        'unregistered' => 'N/A'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root {
        --c-orange: #ea580c;
        --c-orange-light: #ffedd5;
    }

    .rfq-wrapper { max-width: 900px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition: 0.2s; }
    .back-btn:hover { background: var(--c-orange-light); color: var(--c-orange); border-color: #fdba74; }
    
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; }
    .btn-dark { background: #0f172a; color: #ffffff; }
    .btn-dark:hover { background: #1e293b; }
    .btn-edit { background: #ffedd5; color: #ea580c; border: 1px solid #fdba74; }

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); border-top: 6px solid var(--c-orange); }
    .c-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 24px; }
    .c-brand { font-size: 1.6rem; font-weight: 900; color: #0f172a; margin: 0; }
    
    .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 28px; border: 1px solid #f1f5f9; }
    .info-box h5 { margin: 0 0 6px 0; color: #64748b; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
    .info-box p { margin: 0; color: #0f172a; font-weight: 800; font-size: 0.95rem; }

    .table-print { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .table-print th { background: #0f172a; color: #ffffff; padding: 12px; font-size: 0.8rem; text-align: start; }
    .table-print td { padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; color: #1e293b; font-weight: 600; }

    .sup-list { background: #ffffff; border: 1px dashed #cbd5e1; padding: 16px; border-radius: 10px; margin-bottom: 20px;}
    .sup-tag { display: inline-block; background: #f1f5f9; border: 1px solid #e2e8f0; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; margin: 4px; color: #334155; }

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

        /* 3. الإخفاء الجذري لكل مكونات البحث، الترقيم، وقائمة الموردين المنافسين عند الطباعة للمورد */
        .table-pagination-nav,
        .table-pagination-nav *,
        .sup-list,
        .sup-list *,
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

        .print-signatures { display: flex !important; justify-content: space-between !important; margin-top: 60px !important; page-break-inside: avoid !important; }
        .sig-box { text-align: center !important; flex: 1 !important; font-weight: 800 !important; font-size: 0.85rem !important; color: #000 !important; }
        .sig-line { border-top: 1px dashed #0f172a !important; width: 80% !important; margin: 35px auto 0 auto !important; }
    }
</style>

<div class="rfq-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="/ERP/purchasing/rfq" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
            <h3 style="margin:0; font-weight: 800; color: #0f172a;"><?= $t['title'] ?></h3>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/ERP/purchasing/rfq/<?= $rfq->id ?>/edit" class="btn-act btn-edit"><i class="ph-bold ph-pencil-simple"></i> <?= $t['edit'] ?></a>
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
                <div style="font-family: monospace; font-size: 1.5rem; font-weight: 900; color: var(--c-orange);"><?= htmlspecialchars($rfq->rfq_number) ?></div>
                <div style="margin-top:4px; font-weight:800; color: #0f172a; text-transform: uppercase;"><?= $t['status_label'] ?> <?= strtoupper($rfq->status) ?></div>
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <h4 style="margin:0; color:#0f172a; font-size:1.3rem; font-weight:900;"><?= htmlspecialchars($rfq->title) ?></h4>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5><?= $t['issuing_dept'] ?></h5>
                <p><?= $t['company_name'] ?></p>
            </div>
            <div class="info-box">
                <h5><?= $t['issue_date'] ?></h5>
                <p style="font-family: monospace;"><?= $rfq->request_date ?></p>
            </div>
            <div class="info-box">
                <h5><?= $t['deadline_date'] ?></h5>
                <p style="color: #dc2626; font-family: monospace;"><?= $rfq->deadline_date ?></p>
            </div>
        </div>

        <div class="sup-list">
            <h5 style="margin:0 0 10px 0; color:#ea580c; font-size:0.85rem; font-weight:800;"><i class="ph-bold ph-buildings"></i> <?= $t['invited_suppliers'] ?></h5>
            <?php if(!empty($invitedSuppliers)): foreach($invitedSuppliers as $sup): ?>
                <span class="sup-tag"><?= htmlspecialchars($sup->name) ?></span>
            <?php endforeach; else: ?>
                <span style="color:#94a3b8; font-size:0.85rem;"><?= $t['no_suppliers'] ?></span>
            <?php endif; ?>
        </div>

        <h4 style="margin: 0 0 12px 0; color: #0f172a; font-weight: 900; font-size: 1.1rem;"><?= $t['items_title'] ?></h4>
        
        <table class="table-print">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 20%;"><?= $t['col_code'] ?></th>
                    <th style="width: 45%;"><?= $t['col_desc'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_qty'] ?></th>
                    <th style="width: 15%; text-align: center;"><?= $t['col_price'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($items)): foreach($items as $index => $item): ?>
                    <tr>
                        <td style="text-align: center; color: #94a3b8; font-weight: 800;"><?= $index + 1 ?></td>
                        <td style="font-family: monospace; color: #475569;"><?= htmlspecialchars($item->product_code ?? $t['unregistered']) ?></td>
                        <td style="color: #0f172a; font-weight: 700;"><?= htmlspecialchars($item->description) ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 800; color: #0f172a;"><?= number_format($item->quantity, 2) ?></td>
                        <td style="text-align: center; color: #cbd5e1; border-left: 2px dashed #e2e8f0; background: #f8fafc; font-size:0.85rem;"><?= $t['price_placeholder'] ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 20px;"><?= $t['no_items'] ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if(!empty($rfq->notes)): ?>
            <div style="margin-bottom: 24px; color: #334155; font-size: 0.9rem; font-weight: 600;">
                <strong style="color: #ea580c;"><?= $t['notes'] ?></strong> <br><?= nl2br(htmlspecialchars($rfq->notes)) ?>
            </div>
        <?php endif; ?>

        <div class="print-signatures">
            <div class="sig-box">
                <div><?= $t['sig_purchasing'] ?></div>
                <div class="sig-line"></div>
            </div>
            <div class="sig-box">
                <div><?= $t['sig_supplier'] ?></div>
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