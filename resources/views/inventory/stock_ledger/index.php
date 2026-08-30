<?php
// Path: resources/views/inventory/stock_ledger/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

function getReferenceBadge($type) {
    $map = [
        'purchase'        => ['bg' => '#eff6ff', 'color' => '#2563eb', 'label' => 'مشتريات واردة'],
        'sale'            => ['bg' => '#f8fafc', 'color' => '#475569', 'label' => 'مبيعات منصرفة'],
        'transfer_in'     => ['bg' => '#f0fdf4', 'color' => '#16a34a', 'label' => 'تحويل وارد'],
        'transfer_out'    => ['bg' => '#fff7ed', 'color' => '#c2410c', 'label' => 'تحويل منصرف'],
        'sales_return'    => ['bg' => '#f0fdf4', 'color' => '#15803d', 'label' => 'مرتجع مبيعات'],
        'purchase_return' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => 'مرتجع مشتريات'],
        'adjustment_add'  => ['bg' => '#f0fdf4', 'color' => '#059669', 'label' => 'تسوية إضافة'],
        'adjustment_sub'  => ['bg' => '#fef2f2', 'color' => '#b91c1c', 'label' => 'تسوية خصم']
    ];
    $s = $map[$type] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $type];
    return "<span class='badge-status' style='background:{$s['bg']}; color:{$s['color']}; padding:4px 10px; border-radius:6px; font-weight:800; font-size:0.78rem; border:1px solid currentColor;'>{$s['label']}</span>";
}
?>

<style>
    :root {
        --c-brand: #2563eb;
        --c-brand-dark: #1d4ed8;
        --c-brand-light: #eff6ff;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .mod-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .mod-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .mod-title-box { display: flex; align-items: center; gap: 16px; }
    .mod-icon { width: 48px; height: 48px; background: var(--c-brand-light); color: var(--c-brand); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.12); }
    .mod-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 800; }
    
    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .kpi-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: var(--c-brand-light); color: var(--c-brand); }
    .kpi-info h4 { margin: 0 0 4px 0; font-size: 0.8rem; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; }
    .kpi-info p { margin: 0; font-size: 1.4rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .filter-card { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 18px; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .filter-grid { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 12px; align-items: end; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--c-border); border-radius: 8px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; color: var(--c-text-dark); }
    .form-control:focus { border-color: var(--c-brand); outline: none; background: #fff; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; height: 42px; display: inline-flex; align-items: center; gap: 6px; }
    .btn-clear { background: #f1f5f9; color: var(--c-text-muted); border: 1px solid var(--c-border); padding: 10px 16px; border-radius: 8px; font-weight: 800; text-decoration: none; height: 42px; display: inline-flex; align-items: center; justify-content: center; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .mod-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .mod-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .mod-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .mod-table tr:hover td { background: #f8fafc; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-brand); color: #ffffff; border-color: var(--c-brand); }
    
    /* إعدادات الطباعة الاحترافية */
    @media print {
        @page {
            size: A4 landscape;
            margin: 12mm 10mm;
        }

        body {
            background: #ffffff !important;
            color: #000000 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .nt-sidebar, header, nav, footer, .filter-card, .pagination, .btn-search, .btn-clear, .mod-header button, .mod-icon {
            display: none !important;
        }

        .mod-wrapper {
            padding: 0 !important;
            margin: 0 !important;
        }

        .mod-header {
            border-bottom: 2px solid #0f172a !important;
            padding-bottom: 12px !important;
            margin-bottom: 20px !important;
        }

        .mod-title {
            font-size: 1.8rem !important;
            color: #0f172a !important;
        }

        .kpi-row {
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 12px !important;
            margin-bottom: 20px !important;
        }

        .kpi-card {
            border: 1px solid #cbd5e1 !important;
            box-shadow: none !important;
            padding: 10px 14px !important;
            background: #f8fafc !important;
        }

        .kpi-info h4 {
            color: #475569 !important;
            font-size: 0.75rem !important;
        }

        .kpi-info p {
            color: #0f172a !important;
            font-size: 1.2rem !important;
        }

        .table-card {
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }

        .mod-table {
            width: 100% !important;
            border-collapse: collapse !important;
            border: 1px solid #000000 !important;
        }

        .mod-table th {
            background: #e2e8f0 !important;
            color: #000000 !important;
            border: 1px solid #000000 !important;
            font-size: 0.8rem !important;
            padding: 10px !important;
        }

        .mod-table td {
            border: 1px solid #cbd5e1 !important;
            color: #000000 !important;
            font-size: 0.85rem !important;
            padding: 8px 10px !important;
        }

        .mod-table tr {
            page-break-inside: avoid !important;
        }

        .badge-status {
            border: 1px solid #000000 !important;
            background: #ffffff !important;
            color: #000000 !important;
        }

        .qty-badge {
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
        }
    }
</style>

<div class="mod-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="mod-header">
        <div class="mod-title-box">
            <div class="mod-icon"><i class="ph-duotone ph-list-dashes"></i></div>
            <div>
                <h2 class="mod-title">كارت الحساب المخزني (Stock Ledger)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted); font-weight:500;">سجل الحركات المخزنية التفصيلي للأصناف والوارد والمنصرف بكل مستودع.</p>
            </div>
        </div>
        <button onclick="window.print()" class="btn-search" style="background:#0f172a;"><i class="ph-bold ph-printer"></i> طباعة الكارت</button>
    </div>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="ph-duotone ph-arrows-down-up"></i></div>
            <div class="kpi-info"><h4>إجمالي الحركات المسجلة</h4><p><?= number_format($stats->total_ops ?? 0) ?></p></div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #16a34a;">
            <div class="kpi-icon" style="background:#f0fdf4; color:#16a34a;"><i class="ph-duotone ph-arrow-down-right"></i></div>
            <div class="kpi-info"><h4 style="color:#16a34a;">إجمالي الوارد (+)</h4><p><?= number_format($stats->total_in ?? 0, 2) ?></p></div>
        </div>
        <div class="kpi-card" style="border-bottom: 3px solid #dc2626;">
            <div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-arrow-up-right"></i></div>
            <div class="kpi-info"><h4 style="color:#dc2626;">إجمالي المنصرف (-)</h4><p><?= number_format($stats->total_out ?? 0, 2) ?></p></div>
        </div>
    </div>

    <div class="filter-card">
        <form action="/ERP/inventory/stock/ledger" method="GET" class="filter-grid">
            <div>
                <label style="font-size:0.8rem; font-weight:800; color:var(--c-text-muted); margin-bottom:4px; display:block;">البحث السريع</label>
                <input type="text" name="search" class="form-control" placeholder="ابحث برقم الإذن، كود الصنف، أو الاسم..." value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
            <div>
                <label style="font-size:0.8rem; font-weight:800; color:var(--c-text-muted); margin-bottom:4px; display:block;">الصنف المحدد</label>
                <select name="product_id" class="form-control">
                    <option value="">-- كل الأصناف --</option>
                    <?php foreach($products as $p): ?>
                        <option value="<?= $p->id ?>" <?= ($productId == $p->id) ? 'selected' : '' ?>><?= htmlspecialchars($p->item_code) ?> - <?= htmlspecialchars($p->name_ar) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-size:0.8rem; font-weight:800; color:var(--c-text-muted); margin-bottom:4px; display:block;">المستودع</label>
                <select name="warehouse_id" class="form-control">
                    <option value="">-- كل المستودعات --</option>
                    <?php foreach($warehouses as $w): ?>
                        <option value="<?= $w->id ?>" <?= ($warehouseId == $w->id) ? 'selected' : '' ?>><?= htmlspecialchars($w->name_ar) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; gap:6px;">
                <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> فلترة</button>
                <?php if(!empty($search) || $productId || $warehouseId): ?>
                    <a href="/ERP/inventory/stock/ledger" class="btn-clear"><i class="ph-bold ph-x"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="table-card">
        <table class="mod-table">
            <thead>
                <tr>
                    <th style="width: 15%;">تاريخ الحركة</th>
                    <th style="width: 25%;">الصنف</th>
                    <th style="width: 15%;">المستودع</th>
                    <th style="width: 15%;">نوع العملية</th>
                    <th style="width: 15%;">المرجع</th>
                    <th style="width: 15%; text-align: center;">الكمية (وارد / منصرف)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد حركات مخزنية مسجلة تطابق بحثك.</td></tr>
                <?php else: foreach ($movements as $m): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: #334155; font-family: monospace;"><i class="ph-bold ph-clock text-slate-400"></i> <?= $m->created_at ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars($m->product_name) ?></div>
                            <div style="font-family: monospace; color: var(--c-brand); font-weight: 800; font-size: 0.8rem; margin-top: 2px;"><?= htmlspecialchars($m->product_code) ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #334155;"><i class="ph-fill ph-warehouse text-slate-400"></i> <?= htmlspecialchars($m->warehouse_name) ?></div>
                        </td>
                        <td><?= getReferenceBadge($m->reference_type) ?></td>
                        <td>
                            <div style="font-weight: 900; font-family: monospace; color: #0f172a;"><?= htmlspecialchars($m->reference_number ?? '---') ?></div>
                        </td>
                        <td style="text-align: center;">
                            <?php if($m->movement_type === 'in'): ?>
                                <span class="qty-badge" style="font-family: monospace; font-weight: 900; color: #16a34a; font-size: 1.05rem; background: #f0fdf4; padding: 4px 12px; border-radius: 6px; border: 1px solid #bbf7d0;">
                                    +<?= number_format($m->quantity, 2) ?> <?= htmlspecialchars($m->unit ?? '') ?>
                                </span>
                            <?php else: ?>
                                <span class="qty-badge" style="font-family: monospace; font-weight: 900; color: #dc2626; font-size: 1.05rem; background: #fef2f2; padding: 4px 12px; border-radius: 6px; border: 1px solid #fecaca;">
                                    -<?= number_format($m->quantity, 2) ?> <?= htmlspecialchars($m->unit ?? '') ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>&product_id=<?= $productId ?>&warehouse_id=<?= $warehouseId ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>