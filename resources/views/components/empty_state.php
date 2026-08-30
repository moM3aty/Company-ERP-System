<?php
// Path: resources/views/components/empty_state.php

/**
 * @var string $icon e.g., 'ph-folder-open'
 * @var string $title
 * @var string $description
 * @var string|null $actionHtml Optional button HTML
 */
$icon = $icon ?? 'ph-package';
$title = $title ?? __('common.no_data_found');
?>
<div class="erp-empty-state" style="text-align: center; padding: 60px 20px; background: var(--color-surface); border: 1px dashed var(--color-border); border-radius: var(--radius-md);">
    <i class="ph-duotone <?= htmlspecialchars($icon) ?>" style="font-size: 4rem; color: var(--color-text-muted); opacity: 0.4; margin-bottom: 16px;"></i>
    <h3 style="margin: 0 0 8px 0; color: var(--color-primary-900); font-size: 1.125rem; font-weight: 600;">
        <?= htmlspecialchars($title) ?>
    </h3>
    <?php if (isset($description)): ?>
        <p style="margin: 0 0 24px 0; color: var(--color-text-muted); font-size: 0.875rem; max-width: 400px; margin-inline: auto;">
            <?= htmlspecialchars($description) ?>
        </p>
    <?php endif; ?>
    
    <?php if (isset($actionHtml)): ?>
        <?= $actionHtml ?>
    <?php endif; ?>
</div>