<?php
// Path: resources/views/components/button.php

/**
 * @var string $text
 * @var string $type (submit, button)
 * @var string $variant (primary, secondary, danger, outline)
 * @var string|null $icon (Phosphor icon class, e.g., 'ph-plus')
 * @var string|null $class
 */

$type = $type ?? 'button';
$variant = $variant ?? 'primary';
$icon = $icon ?? null;
$class = $class ?? '';

// Enterprise Styling Logic
$bg = 'var(--color-primary-500)';
$color = '#ffffff';
$border = 'none';

if ($variant === 'secondary') {
    $bg = 'var(--color-surface)';
    $color = 'var(--color-text-main)';
    $border = '1px solid var(--color-border)';
} elseif ($variant === 'danger') {
    $bg = 'var(--color-danger-bg)';
    $color = 'var(--color-danger-text)';
    $border = '1px solid var(--color-danger-border)';
}

?>
<button 
    type="<?= htmlspecialchars($type) ?>" 
    class="erp-btn <?= htmlspecialchars($class) ?>"
    style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 8px 16px; background: <?= $bg ?>; color: <?= $color ?>; border: <?= $border ?>; border-radius: var(--radius-sm); font-family: inherit; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all var(--transition-fast); box-shadow: var(--shadow-sm);"
    onmouseover="this.style.transform='translateY(-1px)';"
    onmouseout="this.style.transform='none';"
>
    <?php if ($icon): ?>
        <i class="ph <?= htmlspecialchars($icon) ?>" style="font-size: 1.125rem;"></i>
    <?php endif; ?>
    <?= htmlspecialchars($text) ?>
</button>