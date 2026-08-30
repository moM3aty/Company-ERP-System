<?php
// Path: resources/views/components/input.php

/**
 * @var string $name
 * @var string $label
 * @var string $type (text, number, email, date, etc.)
 * @var string|null $value
 * @var string|null $placeholder
 * @var bool $required
 * @var string|null $error
 */

$type = $type ?? 'text';
$value = $value ?? '';
$placeholder = $placeholder ?? '';
$required = $required ?? false;
$error = $error ?? null;
?>
<div class="erp-form-group" style="margin-bottom: var(--spacing-md);">
    <label for="<?= htmlspecialchars($name) ?>" style="display: block; margin-bottom: 4px; font-weight: 500; color: var(--color-text-main); font-size: 0.875rem;">
        <?= htmlspecialchars($label) ?>
        <?php if ($required): ?>
            <span style="color: var(--color-danger-text);">*</span>
        <?php endif; ?>
    </label>
    
    <input 
        type="<?= htmlspecialchars($type) ?>" 
        id="<?= htmlspecialchars($name) ?>" 
        name="<?= htmlspecialchars($name) ?>" 
        value="<?= htmlspecialchars($value) ?>" 
        placeholder="<?= htmlspecialchars($placeholder) ?>"
        <?= $required ? 'required' : '' ?>
        style="width: 100%; padding: 8px 12px; border: 1px solid <?= $error ? 'var(--color-danger-border)' : 'var(--color-border)' ?>; border-radius: var(--radius-sm); font-family: inherit; font-size: 0.875rem; background: var(--color-surface); color: var(--color-text-main); transition: border-color var(--transition-fast), box-shadow var(--transition-fast); outline: none;"
        onfocus="this.style.borderColor='var(--color-primary-500)'; this.style.boxShadow='0 0 0 3px var(--color-primary-100)';"
        onblur="this.style.borderColor='<?= $error ? 'var(--color-danger-border)' : 'var(--color-border)' ?>'; this.style.boxShadow='none';"
    >
    
    <?php if ($error): ?>
        <div class="erp-error-text" style="color: var(--color-danger-text); font-size: 0.75rem; margin-top: 4px; display: flex; align-items: center; gap: 4px;">
            <i class="ph ph-warning-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
</div>