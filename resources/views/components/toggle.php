<?php
// Path: resources/views/components/toggle.php

/**
 * @var string $name
 * @var string $label
 * @var bool $checked
 * @var string|null $description
 */

$checked = $checked ?? false;
?>
<div class="erp-form-group d-flex align-center justify-between" style="margin-bottom: var(--spacing-md); padding: 12px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); background: var(--color-surface);">
    <div>
        <label for="<?= htmlspecialchars($name) ?>" style="display: block; font-weight: 500; color: var(--color-text-main); font-size: 0.875rem; cursor: pointer;">
            <?= htmlspecialchars($label) ?>
        </label>
        <?php if (isset($description)): ?>
            <span style="display: block; font-size: 0.75rem; color: var(--color-text-muted); margin-top: 2px;"><?= htmlspecialchars($description) ?></span>
        <?php endif; ?>
    </div>
    
    <label style="position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0;">
        <input type="checkbox" id="<?= htmlspecialchars($name) ?>" name="<?= htmlspecialchars($name) ?>" <?= $checked ? 'checked' : '' ?> style="opacity: 0; width: 0; height: 0;" onchange="this.nextElementSibling.style.background = this.checked ? 'var(--color-primary-500)' : 'var(--color-border)'; this.nextElementSibling.querySelector('span').style.transform = this.checked ? 'translateX(20px)' : 'translateX(0)';">
        <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: <?= $checked ? 'var(--color-primary-500)' : 'var(--color-border)' ?>; transition: .3s; border-radius: 24px;">
            <span style="position: absolute; content: ''; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); transform: <?= $checked ? 'translateX(20px)' : 'translateX(0)' ?>;"></span>
        </span>
    </label>
</div>