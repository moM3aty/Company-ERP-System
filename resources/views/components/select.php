<?php
// Path: resources/views/components/select.php

/**
 * @var string $name
 * @var string $label
 * @var array $options Associative array [value => text]
 * @var string|null $selected
 * @var bool $required
 * @var string|null $error
 */

$selected = $selected ?? null;
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
    
    <select 
        id="<?= htmlspecialchars($name) ?>" 
        name="<?= htmlspecialchars($name) ?>" 
        <?= $required ? 'required' : '' ?>
        style="width: 100%; padding: 8px 12px; border: 1px solid <?= $error ? 'var(--color-danger-border)' : 'var(--color-border)' ?>; border-radius: var(--radius-sm); font-family: inherit; font-size: 0.875rem; background: var(--color-surface); color: var(--color-text-main); transition: border-color var(--transition-fast), box-shadow var(--transition-fast); outline: none; appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2364748b%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 12px top 50%; background-size: 10px auto;"
        onfocus="this.style.borderColor='var(--color-primary-500)'; this.style.boxShadow='0 0 0 3px var(--color-primary-100)';"
        onblur="this.style.borderColor='<?= $error ? 'var(--color-danger-border)' : 'var(--color-border)' ?>'; this.style.boxShadow='none';"
    >
        <option value="" disabled <?= is_null($selected) ? 'selected' : '' ?>><?= __('common.select_option') ?></option>
        <?php foreach ($options as $val => $text): ?>
            <option value="<?= htmlspecialchars($val) ?>" <?= $selected == $val ? 'selected' : '' ?>>
                <?= htmlspecialchars($text) ?>
            </option>
        <?php endforeach; ?>
    </select>
    
    <?php if ($error): ?>
        <div class="erp-error-text" style="color: var(--color-danger-text); font-size: 0.75rem; margin-top: 4px; display: flex; align-items: center; gap: 4px;">
            <i class="ph ph-warning-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
</div>