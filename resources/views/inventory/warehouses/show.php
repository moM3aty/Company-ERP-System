<?php
// Path: resources/views/inventory/warehouses/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
$dir = $isAr ? 'rtl' : 'ltr';

$currency = function_exists('current_currency') ? current_currency() : 'EGP';
$convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

if (!isset($warehouse) || !$warehouse) {
    header("Location: /ERP/inventory/warehouses");
    exit;
}

$whId = (int)($warehouse->id ?? 0);
$whNameAr = (string)($warehouse->name_ar ?? '');
$whNameEn = (string)($warehouse->name_en ?? '');
$whName = $isAr ? $whNameAr : ($whNameEn ?: $whNameAr);
$whCode = (string)($warehouse->code ?? '---');
$whLocation = (string)($warehouse->location ?? '---');
$whManager = (string)($warehouse->manager_name ?? ($isAr ? 'غير محدد' : 'Not assigned'));
$whPhone = (string)($warehouse->phone ?? '---');
$whActive = !empty($warehouse->is_active);
$createdAt = (string)($warehouse->created_at ?? '---');

$itemsCount = (int)($warehouse->stats->items_count ?? 0);
$totalValue = (float)($warehouse->stats->total_value ?? 0);

$t = [
    'ar' => [
        'print' => 'طباعة البطاقة', 'active' => 'مستودع نشط', 'inactive' => 'مستودع موقوف',
        'info' => 'بيانات التواصل والموقع', 'manager' => 'أمين المخزن / المسؤول:',
        'phone' => 'رقم هاتف المستودع:', 'location' => 'العنوان / الموقع:',
        'stats' => 'الإحصائيات السريعة (قيمة المخزون)', 'items' => 'عدد الأصناف المرتبطة:', 'value' => 'إجمالي القيمة التقديرية (شراء):',
        'created' => 'تاريخ تسجيل المستودع:'
    ],
    'en' => [
        'print' => 'Print Card', 'active' => 'Active Warehouse', 'inactive' => 'Inactive Warehouse',
        'info' => 'Contact & Location Info', 'manager' => 'Storekeeper / Manager:',
        'phone' => 'Phone Number:', 'location' => 'Address / Location:',
        'stats' => 'Quick Stats (Inventory Value)', 'items' => 'Linked Products Count:', 'value' => 'Estimated Total Value (Cost):',
        'created' => 'Registration Date:'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    :root { --c-amber: #f59e0b; }
    .wh-wrapper { max-width: 850px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .btn-act { padding: 10px 20px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: #0f172a; color: #ffffff; transition:0.2s;}
    .btn-act:hover {background: #1e293b;}
    .back-btn { width: 40px; height: 40px; border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #475569; text-decoration: none; transition:0.2s;}
    .back-btn:hover { background: #fef3c7; color: var(--c-amber); border-color: #fcd34d; }

    .print-canvas { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05); border-top: 8px solid var(--c-amber); }
    .c-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 24px; }
    
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px; }
    .info-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; }
    .info-box h5 { margin: 0 0 14px 0; color: #64748b; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px; }
    .info-box p { margin: 8px 0; color: #0f172a; font-weight: 700; font-size: 0.95rem; }

    @media print {
        @page { size: A4 portrait; margin: 12mm; }
        body { background: #fff !important; }
        .nt-sidebar, header, nav, footer, .top-bar { display: none !important; }
        .wh-wrapper { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .print-canvas { border: none !important; box-shadow: none !important; padding: 0 !important; border-top: none !important; }
        .info-box { border: 1px solid #000 !important; background: transparent !important; }
    }
</style>

<div class="wh-wrapper" dir="<?= $dir ?>">
    <div class="top-bar">
        <a href="/ERP/inventory/warehouses" class="back-btn"><i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i></a>
        <div style="display:flex; gap:10px;">
            <a href="/ERP/inventory/warehouses/<?= $whId ?>/edit" class="btn-act" style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1;"><i class="ph-bold ph-pencil-simple"></i> تعديل</a>
            <button onclick="window.print()" class="btn-act"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
        </div>
    </div>

    <div class="print-canvas">
        <div class="c-header">
            <div>
                <h1 style="margin:0; font-size:1.8rem; font-weight:900; color:#0f172a;"><i class="ph-fill ph-warehouse" style="color:var(--c-amber);"></i> <?= htmlspecialchars($whName) ?></h1>
                <?php if($isAr && !empty($whNameEn)): ?><p style="margin:4px 0 0 0; color:#64748b; font-weight:700; font-family:sans-serif;" dir="ltr"><?= htmlspecialchars($whNameEn) ?></p><?php endif; ?>
            </div>
            <div style="text-align: <?= $isAr ? 'left' : 'right' ?>;">
                <div style="font-family: monospace; font-size: 1.8rem; font-weight: 900; color: var(--c-amber);"><i class="ph-bold ph-hash"></i> <?= htmlspecialchars($whCode) ?></div>
                <div style="margin-top:4px; font-weight:800; color: <?= $whActive ? '#059669' : '#dc2626' ?>; text-transform: uppercase;">
                    <?= $whActive ? $t['active'] : $t['inactive'] ?>
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h5><?= $t['info'] ?></h5>
                <p><span style="color:#64748b;"><?= $t['manager'] ?></span> <strong style="color:#d97706;"><?= htmlspecialchars($whManager) ?></strong></p>
                <p><span style="color:#64748b;"><?= $t['phone'] ?></span> <span style="font-family:monospace;"><?= htmlspecialchars($whPhone) ?></span></p>
                <p><span style="color:#64748b;"><?= $t['location'] ?></span> <?= htmlspecialchars($whLocation) ?></p>
            </div>
            
            <div class="info-box" style="background: #eff6ff; border-color: #bfdbfe;">
                <h5 style="color:#1e40af; border-bottom-color:#93c5fd;"><?= $t['stats'] ?></h5>
                <p><span style="color:#3b82f6;"><?= $t['items'] ?></span> <strong style="font-family:monospace; font-size:1.2rem; color:#1d4ed8;"><?= $itemsCount ?></strong></p>
                <div style="margin-top: 16px;">
                    <span style="color:#3b82f6; font-size:0.85rem; font-weight:800;"><?= $t['value'] ?></span>
                    <div style="font-family:monospace; font-size:1.8rem; font-weight:900; color:#1e40af;">
                        <?= number_format($convert($totalValue), 2) ?> <span style="font-size:1rem; color:#64748b;"><?= htmlspecialchars($currency) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 20px; padding-top: 10px; border-top: 1px dashed #cbd5e1; font-size: 0.85rem; color: #94a3b8; font-weight:700;">
            <?= $t['created'] ?> <span style="font-family:monospace;"><?= htmlspecialchars($createdAt) ?></span>
        </div>
    </div>
</div>