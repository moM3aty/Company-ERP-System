<?php
// Path: resources/views/components/file-upload.php

/**
 * @var string $name
 * @var string $label
 * @var string|null $accept (e.g., 'image/*, .pdf')
 * @var bool $multiple
 * @var string|null $error
 */

$accept = $accept ?? '*/*';
$multiple = $multiple ?? false;
$error = $error ?? null;
?>
<div class="erp-form-group" style="margin-bottom: var(--spacing-md);">
    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--color-text-main); font-size: 0.875rem;">
        <?= htmlspecialchars($label) ?>
    </label>
    
    <div class="erp-file-dropzone" style="position: relative; border: 2px dashed <?= $error ? 'var(--color-danger-border)' : 'var(--color-border)' ?>; border-radius: var(--radius-md); padding: var(--spacing-xl); text-align: center; background: var(--color-background); transition: all var(--transition-fast); cursor: pointer;" onmouseover="this.style.borderColor='var(--color-primary-500)'; this.style.background='rgba(37, 99, 235, 0.02)';" onmouseout="this.style.borderColor='<?= $error ? 'var(--color-danger-border)' : 'var(--color-border)' ?>'; this.style.background='var(--color-background)';">
        
        <input type="file" name="<?= htmlspecialchars($name) ?><?= $multiple ? '[]' : '' ?>" id="<?= htmlspecialchars($name) ?>" accept="<?= htmlspecialchars($accept) ?>" <?= $multiple ? 'multiple' : '' ?> style="position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 10;" onchange="updateFileName(this, '<?= htmlspecialchars($name) ?>_filename')">
        
        <div style="pointer-events: none;">
            <i class="ph-duotone ph-cloud-arrow-up" style="font-size: 2.5rem; color: var(--color-text-muted); margin-bottom: 8px;"></i>
            <p style="margin: 0; font-size: 0.875rem; color: var(--color-text-main); font-weight: 500;">Click or drag files here to upload</p>
            <p style="margin: 4px 0 0 0; font-size: 0.75rem; color: var(--color-text-muted);" id="<?= htmlspecialchars($name) ?>_filename">Max file size: 10MB</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="erp-error-text" style="color: var(--color-danger-text); font-size: 0.75rem; margin-top: 4px; display: flex; align-items: center; gap: 4px;">
            <i class="ph ph-warning-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function updateFileName(input, textElementId) {
        const textElement = document.getElementById(textElementId);
        if (input.files && input.files.length > 1) {
            textElement.textContent = input.files.length + ' files selected';
            textElement.style.color = 'var(--color-primary-700)';
        } else if (input.files && input.files.length === 1) {
            textElement.textContent = input.files[0].name;
            textElement.style.color = 'var(--color-primary-700)';
        } else {
            textElement.textContent = 'Max file size: 10MB';
            textElement.style.color = 'var(--color-text-muted)';
        }
    }
</script>