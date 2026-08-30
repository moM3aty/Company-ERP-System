<?php
// Path: resources/views/components/badge.php

/**
 * @var string $text
 * @var string $status (success, warning, danger, info, neutral)
 */

$status = $status ?? 'neutral';

$bg = 'var(--color-background)';
$color = 'var(--color-text-muted)';
$border = 'var(--color-border)';

switch ($status) {
    case 'success':
        $bg = 'var(--color-success-bg)';
        $color = 'var(--color-success-text)';
        $border = 'var(--color-success-border)';
        break;
    case 'warning':
        $bg = 'var(--color-warning-bg)';
        $color = 'var(--color-warning-text)';
        $border = 'var(--color-warning-border)';
        break;
    case 'danger':
        $bg = 'var(--color-danger-bg)';
        $color = 'var(--color-danger-text)';
        $border = 'var(--color-danger-border)';
        break;
    case 'info':
        $bg = 'var(--color-info-bg)';
        $color = 'var(--color-info-text)';
        $border = 'var(--color-info-border)';
        break;
}
?>
<span class="erp-badge" style="display: inline-flex; align-items: center; padding: 2px 8px; background: <?= $bg ?>; color: <?= $color ?>; border: 1px solid <?= $border ?>; border-radius: 999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
    <?= htmlspecialchars($text) ?>
</span>