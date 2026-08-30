<?php
// Path: resources/views/components/card.php

/**
 * @var string|null $title
 * @var string $slot (The content inside the card)
 * @var string|null $headerActions (HTML for buttons inside header)
 * @var string|null $class (Additional CSS classes)
 */

$class = $class ?? '';
?>
<div class="erp-card <?= htmlspecialchars($class) ?>" style="background: var(--color-surface); border-radius: var(--radius-md); border: 1px solid var(--color-border); box-shadow: var(--shadow-sm); overflow: hidden;">
    
    <?php if (isset($title)): ?>
        <div class="erp-card-header d-flex justify-between align-center" style="padding: var(--spacing-md) var(--spacing-lg); border-bottom: 1px solid var(--color-border); background: var(--color-background);">
            <h3 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: var(--color-primary-900);">
                <?= htmlspecialchars($title) ?>
            </h3>
            
            <?php if (isset($headerActions)): ?>
                <div class="erp-card-actions d-flex gap-sm">
                    <?= $headerActions ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="erp-card-body" style="padding: var(--spacing-lg);">
        <?= $slot ?>
    </div>
</div>