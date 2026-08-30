<?php
// Path: resources/views/components/pagination.php

/**
 * @var int $currentPage
 * @var int $totalPages
 * @var int $totalItems
 * @var int $itemsPerPage
 */

$currentPage = $currentPage ?? 1;
$totalPages = $totalPages ?? 1;
$totalItems = $totalItems ?? 0;
$itemsPerPage = $itemsPerPage ?? 10;

$startItem = (($currentPage - 1) * $itemsPerPage) + 1;
$endItem = min($currentPage * $itemsPerPage, $totalItems);
if ($totalItems == 0) {
    $startItem = 0;
    $endItem = 0;
}
?>
<div class="erp-pagination d-flex justify-between align-center" style="padding: 12px 16px; border-top: 1px solid var(--color-border); background: var(--color-surface); font-size: 0.75rem; color: var(--color-text-muted);">
    <div>
        <?= __('common.showing') ?? 'Showing' ?> 
        <span class="font-semibold" style="color: var(--color-text-main);"><?= $startItem ?></span> 
        <?= __('common.to') ?? 'to' ?> 
        <span class="font-semibold" style="color: var(--color-text-main);"><?= $endItem ?></span> 
        <?= __('common.of') ?? 'of' ?> 
        <span class="font-semibold" style="color: var(--color-text-main);"><?= $totalItems ?></span> 
        <?= __('common.results') ?? 'results' ?>
    </div>
    
    <div class="d-flex gap-xs">
        <a href="?page=<?= max(1, $currentPage - 1) ?>" class="erp-btn-icon" style="text-decoration: none; <?= $currentPage <= 1 ? 'pointer-events: none; opacity: 0.5;' : '' ?>">
            <i class="ph ph-caret-left"></i>
        </a>
        
        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
            <a href="?page=<?= $i ?>" class="erp-btn-icon" style="text-decoration: none; <?= $i === $currentPage ? 'background: var(--color-primary-500); color: white; border-color: var(--color-primary-500);' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <a href="?page=<?= min($totalPages, $currentPage + 1) ?>" class="erp-btn-icon" style="text-decoration: none; <?= $currentPage >= $totalPages ? 'pointer-events: none; opacity: 0.5;' : '' ?>">
            <i class="ph ph-caret-right"></i>
        </a>
    </div>
</div>