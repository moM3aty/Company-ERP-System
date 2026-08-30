<?php
// Path: resources/views/components/table.php

/**
 * @var array $headers ['column_key' => 'Column Label']
 * @var array $rows Array of objects or associative arrays
 * @var array $actions Array of available actions (edit, delete, view)
 */

$headers = $headers ?? [];
$rows = $rows ?? [];
$actions = $actions ?? [];
?>
<div class="erp-table-wrapper" style="background: var(--color-surface); border-radius: var(--radius-md); border: 1px solid var(--color-border); overflow: hidden; box-shadow: var(--shadow-sm);">
    
    <!-- Table Toolbar -->
    <div class="erp-table-toolbar d-flex justify-between align-center" style="padding: var(--spacing-md); border-bottom: 1px solid var(--color-border); background: var(--color-background);">
        <div class="d-flex gap-sm align-center">
            <div class="erp-search-input d-flex align-center" style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-sm); padding: 4px 8px;">
                <i class="ph ph-magnifying-glass text-muted"></i>
                <input type="text" placeholder="<?= __('common.search') ?>..." style="border: none; outline: none; background: transparent; padding-inline-start: 8px; font-size: 0.875rem;">
            </div>
            <button class="erp-btn-icon" style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 6px; border-radius: var(--radius-sm); cursor: pointer; color: var(--color-text-muted);"><i class="ph ph-funnel"></i></button>
        </div>
        <div class="d-flex gap-sm align-center">
            <button class="erp-btn-icon" style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 6px; border-radius: var(--radius-sm); cursor: pointer; color: var(--color-text-muted);"><i class="ph ph-export"></i> <?= __('common.export') ?></button>
            <button class="erp-btn-icon" style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 6px; border-radius: var(--radius-sm); cursor: pointer; color: var(--color-text-muted);"><i class="ph ph-gear"></i></button>
        </div>
    </div>

    <!-- The Table -->
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: start;">
            <thead>
                <tr>
                    <th style="padding: 12px 16px; border-bottom: 1px solid var(--color-border); background: var(--color-background); width: 40px;">
                        <input type="checkbox" style="accent-color: var(--color-primary-500);">
                    </th>
                    <?php foreach ($headers as $key => $label): ?>
                        <th style="padding: 12px 16px; border-bottom: 1px solid var(--color-border); background: var(--color-background); color: var(--color-text-muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?= htmlspecialchars($label) ?>
                        </th>
                    <?php endforeach; ?>
                    <?php if (!empty($actions)): ?>
                        <th style="padding: 12px 16px; border-bottom: 1px solid var(--color-border); background: var(--color-background); text-align: end;"></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="<?= count($headers) + 2 ?>" style="padding: 40px; text-align: center; color: var(--color-text-muted);">
                            <i class="ph ph-folder-open" style="font-size: 3rem; margin-bottom: 8px; opacity: 0.5;"></i>
                            <p><?= __('common.no_data_found') ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr style="border-bottom: 1px solid var(--color-border); transition: background var(--transition-fast);" onmouseover="this.style.background='var(--color-background)';" onmouseout="this.style.background='transparent';">
                            <td style="padding: 12px 16px;">
                                <input type="checkbox" style="accent-color: var(--color-primary-500);">
                            </td>
                            <?php foreach ($headers as $key => $label): ?>
                                <td style="padding: 12px 16px; font-size: 0.875rem; color: var(--color-text-main);">
                                    <?= isset($row[$key]) ? $row[$key] : '-' ?>
                                </td>
                            <?php endforeach; ?>
                            <?php if (!empty($actions)): ?>
                                <td style="padding: 12px 16px; text-align: end;">
                                    <div class="d-flex gap-sm justify-end">
                                        <?php if (in_array('view', $actions)): ?>
                                            <a href="#" style="color: var(--color-primary-500); text-decoration: none;"><i class="ph ph-eye"></i></a>
                                        <?php endif; ?>
                                        <?php if (in_array('edit', $actions)): ?>
                                            <a href="#" style="color: var(--color-accent); text-decoration: none;"><i class="ph ph-pencil-simple"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="erp-pagination d-flex justify-between align-center" style="padding: 12px 16px; border-top: 1px solid var(--color-border); background: var(--color-surface); font-size: 0.75rem; color: var(--color-text-muted);">
        <div><?= __('common.showing') ?> 1 <?= __('common.to') ?> 10 <?= __('common.of') ?> 42 <?= __('common.results') ?></div>
        <div class="d-flex gap-sm">
            <button style="border: 1px solid var(--color-border); background: var(--color-surface); padding: 4px 8px; border-radius: var(--radius-sm); cursor: pointer;"><?= __('common.prev') ?></button>
            <button style="border: 1px solid var(--color-border); background: var(--color-surface); padding: 4px 8px; border-radius: var(--radius-sm); cursor: pointer;"><?= __('common.next') ?></button>
        </div>
    </div>
</div>