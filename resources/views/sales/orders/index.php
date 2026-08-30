<?php
// Path: resources/views/sales/orders/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isRtl();
$currency = current_currency();

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'أوامر البيع (Sales Orders)', 'desc' => 'إدارة أوامر البيع المؤكدة للعملاء، متابعة التسليم والفوترة.',
        'add_btn' => 'إنشاء أمر بيع جديد', 'search' => 'البحث برقم الأمر أو اسم العميل...',
        'col_order' => 'رقم الأمر', 'col_customer' => 'العميل', 'col_date' => 'تاريخ الطلب والتسليم',
        'col_amount' => 'الإجمالي', 'col_status' => 'الحالة', 'col_actions' => 'إجراءات',
        'empty_title' => 'لا توجد أوامر بيع', 'empty_desc' => 'ابدأ بإنشاء أول أمر بيع وتأكيده للعملاء.',
    ],
    'en' => [
        'title' => 'Sales Orders', 'desc' => 'Manage confirmed customer orders, track fulfillment and invoicing.',
        'add_btn' => 'New Sales Order', 'search' => 'Search by order number or customer...',
        'col_order' => 'Order No.', 'col_customer' => 'Customer', 'col_date' => 'Order & Delivery Dates',
        'col_amount' => 'Total Amount', 'col_status' => 'Status', 'col_actions' => 'Actions',
        'empty_title' => 'No Sales Orders Found', 'empty_desc' => 'Start by creating your first sales order.',
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    .crm-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .crm-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
    .crm-title-box { display: flex; align-items: center; gap: 14px; }
    .crm-title-icon { width: 48px; height: 48px; background: #ecfdf5; color: #059669; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.1); }
    .crm-title { margin: 0; color: #0f172a; font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px; }
    .crm-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: #64748b; font-weight: 500; }
    
    .btn-primary { background: linear-gradient(135deg, #10b981, #059669); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; text-decoration: none; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); transition: all 0.2s ease; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35); }
    
    .crm-toolbar { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; display: flex; gap: 16px; align-items: center; flex-wrap: wrap; box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: 24px; }
    .search-box { flex-grow: 1; position: relative; min-width: 250px; }
    .search-box i { position: absolute; top: 50%; transform: translateY(-50%); <?= $isRtl ? 'right: 14px;' : 'left: 14px;' ?> color: #94a3b8; font-size: 1.2rem; }
    .search-input { width: 100%; padding: 10px 14px; padding-<?= $isRtl ? 'right' : 'left' ?>: 40px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 0.9rem; font-weight: 500; outline: none; background: #f8fafc; color: #0f172a; transition: 0.2s; }
    .search-input:focus { background: #ffffff; border-color: #34d399; box-shadow: 0 0 0 3px #d1fae5; }
    
    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
    .crm-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .crm-table th { padding: 16px 24px; background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem; border-bottom: 2px solid #e2e8f0; }
    .crm-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; transition: background 0.2s; }
    .crm-table tr:hover td { background: #f8fafc; }
    
    .badge { padding: 4px 12px; border-radius: 999px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; display: inline-flex; align-items: center; justify-content: center;}
    .bg-draft { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .bg-confirmed { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
    .bg-processing { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
    .bg-shipped { background: #f3e8ff; color: #9333ea; border: 1px solid #d8b4fe; }
    .bg-delivered { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .bg-cancelled { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; font-size: 1.1rem; margin: 0 2px; }
    .action-btn:hover { background: #ecfdf5; border-color: #a7f3d0; color: #059669; }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
    
    .empty-state { text-align: center; padding: 80px 20px; }
    .empty-icon { width: 90px; height: 90px; background: #ecfdf5; color: #10b981; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 3rem; margin-bottom: 20px; box-shadow: 0 0 0 10px rgba(16, 185, 129, 0.05); }
</style>

<div class="crm-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="crm-header">
        <div class="crm-title-box">
            <div class="crm-title-icon"><i class="ph-duotone ph-shopping-cart"></i></div>
            <div>
                <h2 class="crm-title"><?= $t['title'] ?></h2>
                <p class="crm-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/sales/orders/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-check-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca; display: flex; align-items: center; gap: 12px;"><i class="ph-fill ph-warning-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="crm-toolbar">
        <div class="search-box">
            <i class="ph-bold ph-magnifying-glass"></i>
            <input type="text" class="search-input" placeholder="<?= $t['search'] ?>">
        </div>
    </div>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th style="width: 15%;"><?= $t['col_order'] ?></th>
                        <th style="width: 25%;"><?= $t['col_customer'] ?></th>
                        <th style="width: 20%;"><?= $t['col_date'] ?></th>
                        <th style="text-align: end; width: 15%;"><?= $t['col_amount'] ?> (<?= htmlspecialchars($currency) ?>)</th>
                        <th style="text-align: center; width: 15%;"><?= $t['col_status'] ?></th>
                        <th style="text-align: center; width: 10%;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="ph-duotone ph-shopping-bag"></i></div>
                                    <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 1.4rem; font-weight: 800;"><?= $t['empty_title'] ?></h3>
                                    <p style="color: #64748b; margin: 0; font-size: 1rem; font-weight: 500;"><?= $t['empty_desc'] ?></p>
                                    <br>
                                    <a href="/ERP/sales/orders/create" class="btn-primary" style="margin-top: 16px;"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td style="font-weight: 800; font-family: monospace; color: #059669;"><?= htmlspecialchars($o->order_no) ?></td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a; font-size: 0.95rem;"><i class="ph-fill ph-user-circle text-muted"></i> <?= htmlspecialchars($o->customer_name) ?></div>
                                </td>
                                <td>
                                    <div style="color: #475569; font-weight: 600;"><i class="ph-fill ph-calendar-blank text-muted"></i> Order: <?= htmlspecialchars($o->order_date) ?></div>
                                    <div style="color: #64748b; font-size: 0.8rem; margin-top: 4px;"><i class="ph-fill ph-truck text-muted"></i> ETA: <?= htmlspecialchars($o->expected_date ?? 'TBD') ?></div>
                                </td>
                                <td style="text-align: end; font-weight: 800; font-family: monospace; color: #0f172a; font-size: 1.05rem;">
                                    <?= number_format(convert_amount($o->grand_total ?? 0), 2) ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge bg-<?= strtolower($o->status) ?>"><?= htmlspecialchars($o->status) ?></span>
                                </td>
                               <td style="text-align: center; white-space: nowrap;">
                                    <a href="/ERP/sales/orders/<?= $o->id ?>" class="action-btn" title="رؤية التفاصيل"><i class="ph-bold ph-eye"></i></a>
                                    <a href="/ERP/sales/orders/<?= $o->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                    <form action="/ERP/sales/orders/<?= $o->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $isRtl ? "هل أنت متأكد من الحذف؟" : "Confirm Delete?" ?>');">
                                        <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>