<?php
// Path: resources/views/components/modal.php

/**
 * @var string $id
 * @var string $title
 * @var string $slot
 * @var string|null $footer
 * @var string $size (sm, md, lg, xl)
 */
$size = $size ?? 'md';
$maxWidth = '500px';

switch ($size) {
    case 'sm': $maxWidth = '300px'; break;
    case 'lg': $maxWidth = '800px'; break;
    case 'xl': $maxWidth = '1140px'; break;
}
?>
<div id="<?= htmlspecialchars($id) ?>" class="erp-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); z-index: 1000; backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: var(--spacing-md); opacity: 0; transition: opacity var(--transition-fast);">
    <div class="erp-modal" style="background: var(--color-surface); width: 100%; max-width: <?= $maxWidth ?>; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); display: flex; flex-direction: column; max-height: 90vh; transform: scale(0.95); transition: transform var(--transition-fast);">
        
        <!-- Header -->
        <div style="padding: var(--spacing-md) var(--spacing-lg); border-bottom: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: var(--color-primary-900);"><?= htmlspecialchars($title) ?></h3>
            <button type="button" onclick="closeModal('<?= htmlspecialchars($id) ?>')" style="background: transparent; border: none; color: var(--color-text-muted); cursor: pointer; padding: 4px; border-radius: var(--radius-sm); transition: background var(--transition-fast);" onmouseover="this.style.background='var(--color-background)'" onmouseout="this.style.background='transparent'">
                <i class="ph ph-x" style="font-size: 1.25rem;"></i>
            </button>
        </div>

        <!-- Body -->
        <div style="padding: var(--spacing-lg); overflow-y: auto;">
            <?= $slot ?>
        </div>

        <!-- Footer -->
        <?php if (isset($footer)): ?>
            <div style="padding: var(--spacing-md) var(--spacing-lg); border-top: 1px solid var(--color-border); background: var(--color-background); border-bottom-left-radius: var(--radius-lg); border-bottom-right-radius: var(--radius-lg);">
                <?= $footer ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
    function openModal(id) {
        const overlay = document.getElementById(id);
        const modal = overlay.querySelector('.erp-modal');
        overlay.style.display = 'flex';
        // Force reflow
        void overlay.offsetWidth;
        overlay.style.opacity = '1';
        modal.style.transform = 'scale(1)';
    }

    function closeModal(id) {
        const overlay = document.getElementById(id);
        const modal = overlay.querySelector('.erp-modal');
        overlay.style.opacity = '0';
        modal.style.transform = 'scale(0.95)';
        setTimeout(() => {
            overlay.style.display = 'none';
        }, 150); // matches transition time
    }

    // Close on outside click
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('erp-modal-overlay')) {
            closeModal(event.target.id);
        }
    });
</script>