<?php
// Path: resources/views/inventory/warehouses/show.php
/** @var object $warehouse */
?>
<style>
    .nt-tabs { display: flex; gap: 8px; border-bottom: 1px solid var(--color-border); margin-bottom: var(--spacing-lg); overflow-x: auto; }
    .nt-tab { padding: 12px 20px; color: var(--color-text-muted); font-weight: 600; font-size: 0.875rem; cursor: pointer; border-bottom: 3px solid transparent; transition: 0.2s; white-space: nowrap; }
    .nt-tab.active { color: var(--color-primary-900); border-bottom-color: var(--color-primary-500); background: var(--color-surface); }
    .nt-tab-content { display: none; }
    .nt-tab-content.active { display: block; animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="d-flex justify-between align-center margin-b-md">
    <div>
        <h2 style="margin: 0; color: var(--color-primary-900); display: flex; align-items: center; gap: 8px;">
            <i class="ph-duotone ph-warehouse"></i> <?= htmlspecialchars($warehouse->name) ?>
            <span style="font-family: monospace; font-size: 0.8rem; background: var(--color-background); border: 1px solid var(--color-border); padding: 2px 8px; border-radius: 4px;"><?= $warehouse->code ?></span>
        </h2>
        <p class="text-muted" style="margin: 4px 0 0 0; font-size: 0.85rem;"><i class="ph ph-map-pin"></i> <?= htmlspecialchars($warehouse->location) ?> | Manager: <strong><?= $warehouse->manager ?></strong></p>
    </div>
    <div class="d-flex gap-sm">
        <button class="erp-btn" style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 8px 16px; border-radius: var(--radius-sm); font-weight: 500;"><i class="ph ph-barcode"></i> Scan Barcode</button>
        <button class="erp-btn" style="background: var(--color-primary-500); color: white; border: none; padding: 8px 16px; border-radius: var(--radius-sm); font-weight: 600;"><i class="ph ph-arrows-left-right"></i> New Transfer</button>
    </div>
</div>

<div class="erp-card" style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); padding: var(--spacing-lg);">
    
    <div class="nt-tabs">
        <div class="nt-tab active" onclick="openTab(event, 'tab-overview')">Overview</div>
        <div class="nt-tab" onclick="openTab(event, 'tab-stock')">Stock Availability</div>
        <div class="nt-tab" onclick="openTab(event, 'tab-locations')">Bin Locations</div>
        <div class="nt-tab" onclick="openTab(event, 'tab-transfers')">Transfers</div>
        <div class="nt-tab" onclick="openTab(event, 'tab-movements')">Movements</div>
        <div class="nt-tab" onclick="openTab(event, 'tab-employees')">Employees</div>
    </div>

    <!-- 1. Overview -->
    <div id="tab-overview" class="nt-tab-content active">
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--spacing-md); margin-bottom: var(--spacing-lg);">
            <div style="padding: 16px; background: var(--color-background); border: 1px solid var(--color-border); border-radius: var(--radius-md); border-top: 3px solid var(--color-primary-500);">
                <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Total Stock Value</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--color-primary-900);">4,500,000 <span style="font-size: 0.8rem;">SAR</span></div>
            </div>
            <div style="padding: 16px; background: var(--color-background); border: 1px solid var(--color-border); border-radius: var(--radius-md); border-top: 3px solid var(--color-info-text);">
                <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Items in Stock</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--color-info-text);">12,450</div>
            </div>
            <div style="padding: 16px; background: var(--color-background); border: 1px solid var(--color-border); border-radius: var(--radius-md); border-top: 3px solid var(--color-warning-text);">
                <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Pending Transfers</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--color-warning-text);">8</div>
            </div>
            <div style="padding: 16px; background: var(--color-background); border: 1px solid var(--color-border); border-radius: var(--radius-md); border-top: 3px solid var(--color-danger-text);">
                <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Capacity Utilization</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--color-danger-text);"><?= $warehouse->capacity ?></div>
            </div>
        </div>
    </div>

    <!-- 2. Stock -->
    <div id="tab-stock" class="nt-tab-content">
        <p class="text-muted">Displays real-time quantities (On Hand, Reserved, Available) specifically for this warehouse.</p>
    </div>

    <!-- 3. Transfers -->
    <div id="tab-transfers" class="nt-tab-content">
        <p class="text-muted">Lists all IN and OUT transfer orders related to this facility.</p>
    </div>

    <div id="tab-locations" class="nt-tab-content"><p class="text-muted">Aisles, Racks, and Bin locations mapping.</p></div>
    <div id="tab-movements" class="nt-tab-content"><p class="text-muted">Detailed Ledger of every single stock movement in this warehouse.</p></div>
    <div id="tab-employees" class="nt-tab-content"><p class="text-muted">Workers, Forklift operators, and Pickers assigned here.</p></div>
</div>

<script>
function openTab(evt, tabId) {
    document.querySelectorAll(".nt-tab-content").forEach(el => el.classList.remove("active"));
    document.querySelectorAll(".nt-tab").forEach(el => el.classList.remove("active"));
    document.getElementById(tabId).classList.add("active");
    evt.currentTarget.classList.add("active");
}
</script>