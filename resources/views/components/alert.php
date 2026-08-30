<?php
// Path: resources/views/components/alert.php

/**
 * @var string $type (success, error, warning, info)
 * @var string $message
 * @var bool $dismissible
 */

$type = $type ?? 'info';
$dismissible = $dismissible ?? true;

$bg = 'var(--color-info-bg)';
$color = 'var(--color-info-text)';
$border = 'var(--color-info-border)';
$icon = 'ph-info';

switch ($type) {
    case 'success':
        $bg = 'var(--color-success-bg)';
        $color = 'var(--color-success-text)';
        $border = 'var(--color-success-border)';
        $icon = 'ph-check-circle';
        break;
    case 'error':
        $bg = 'var(--color-danger-bg)';
        $color = 'var(--color-danger-text)';
        $border = 'var(--color-danger-border)';
        $icon = 'ph-warning-circle';
        break;
    case 'warning':
        $bg = 'var(--color-warning-bg)';
        $color = 'var(--color-warning-text)';
        $border = 'var(--color-warning-border)';
        $icon = 'ph-warning';
        break;
}
?>
<div class="erp-alert" style="background: <?= $bg ?>; color: <?= $color ?>; border: 1px solid <?= $border ?>; padding: 12px 16px; border-radius: var(--radius-sm); display: flex; align-items: flex-start; gap: 12px; margin-bottom: var(--spacing-md); position: relative;">
    <i class="ph <?= $icon ?>" style="font-size: 1.25rem; margin-top: 2px;"></i>
    <div style="flex-grow: 1; font-size: 0.875rem; font-weight: 500; line-height: 1.5;">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php if ($dismissible): ?>
        <button onclick="this.parentElement.style.display='none'" style="background: transparent; border: none; color: <?= $color ?>; cursor: pointer; padding: 0; opacity: 0.7; transition: opacity var(--transition-fast);" onmouseover="this.style.opacity='1';">
            <i class="ph ph-x" style="font-size: 1rem;"></i>
        </button>
    <?php endif; ?>
</div>