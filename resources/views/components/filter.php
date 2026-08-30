<?php
// Path: resources/views/components/filter.php

/**
 * @var array $filters Array of filter definitions [['name' => '', 'label' => '', 'type' => 'text|date|select', 'options' => []]]
 */
$filters = $filters ?? [];
?>
<div class="erp-filter-panel" style="background: var(--color-surface); padding: var(--spacing-md); border-radius: var(--radius-md); border: 1px solid var(--color-border); margin-bottom: var(--spacing-lg); box-shadow: var(--shadow-sm);">
    <form action="" method="GET" class="d-flex align-center" style="flex-wrap: wrap; gap: var(--spacing-md);">
        
        <?php foreach ($filters as $filter): ?>
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; margin-bottom: 4px; font-size: 0.75rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase;">
                    <?= htmlspecialchars($filter['label']) ?>
                </label>
                
                <?php if ($filter['type'] === 'select'): ?>
                    <select name="<?= htmlspecialchars($filter['name']) ?>" style="width: 100%; padding: 8px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); background: var(--color-background); font-family: inherit; font-size: 0.875rem; outline: none;">
                        <option value=""><?= __('common.all') ?></option>
                        <?php foreach ($filter['options'] ?? [] as $val => $text): ?>
                            <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($text) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="<?= htmlspecialchars($filter['type'] ?? 'text') ?>" name="<?= htmlspecialchars($filter['name']) ?>" style="width: 100%; padding: 8px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); background: var(--color-background); font-family: inherit; font-size: 0.875rem; outline: none;">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <div style="display: flex; align-items: flex-end; padding-bottom: 2px;">
            <button type="submit" class="erp-btn" style="background: var(--color-primary-500); color: white; border: none; padding: 8px 16px; border-radius: var(--radius-sm); cursor: pointer; font-weight: 500;"><i class="ph ph-funnel"></i> Apply</button>
        </div>
    </form>
</div>