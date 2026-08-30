<?php
// Path: resources/views/components/drawer.php

/**
 * @var string $id
 * @var string $title
 * @var string $slot
 * @var string|null $footer
 * @var string $direction (right, left) - default is right, adjusts based on RTL automatically if CSS is set correctly, but we provide option.
 */
$direction = $direction ?? 'right';
?>
<div id="<?= htmlspecialchars($id) ?>" class="erp-drawer-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); z-index: 200; backdrop-filter: blur(2px);">
    <div class="erp-drawer" style="position: absolute; top: 0; <?= $direction === 'right' ? 'right: -400px;' : 'left: -400px;' ?> width: 400px; max-width: 100%; height: 100vh; background: var(--color-surface); box-shadow: var(--shadow-lg); display: flex; flex-direction: column; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
        
        <div class="erp-drawer-header d-flex justify-between align-center" style="padding: var(--spacing-md) var(--spacing-lg); border-bottom: 1px solid var(--color-border);">
            <h3 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: var(--color-primary-900);"><?= htmlspecialchars($title) ?></h3>
            <button onclick="closeDrawer('<?= htmlspecialchars($id) ?>')" style="background: transparent; border: none; cursor: pointer; color: var(--color-text-muted); padding: 4px; border-radius: var(--radius-sm); transition: background var(--transition-fast);" onmouseover="this.style.background='var(--color-background)';">
                <i class="ph ph-x" style="font-size: 1.25rem;"></i>
            </button>
        </div>

        <div class="erp-drawer-body" style="padding: var(--spacing-lg); flex-grow: 1; overflow-y: auto;">
            <?= $slot ?>
        </div>

        <?php if (isset($footer)): ?>
            <div class="erp-drawer-footer" style="padding: var(--spacing-md) var(--spacing-lg); border-top: 1px solid var(--color-border); background: var(--color-background);">
                <?= $footer ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
    function openDrawer(id) {
        const overlay = document.getElementById(id);
        overlay.style.display = 'block';
        // Force reflow
        void overlay.offsetWidth;
        const drawer = overlay.querySelector('.erp-drawer');
        if (drawer.style.right !== undefined && drawer.style.right !== '') {
            drawer.style.right = '0';
        } else {
            drawer.style.left = '0';
        }
    }

    function closeDrawer(id) {
        const overlay = document.getElementById(id);
        const drawer = overlay.querySelector('.erp-drawer');
        if (drawer.style.right !== undefined && drawer.style.right !== '') {
            drawer.style.right = '-400px';
        } else {
            drawer.style.left = '-400px';
        }
        setTimeout(() => { overlay.style.display = 'none'; }, 300);
    }
</script>