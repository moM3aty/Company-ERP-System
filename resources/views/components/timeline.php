<?php
// Path: resources/views/components/timeline.php

/**
 * @var array $events [ ['date' => '...', 'title' => '...', 'description' => '...', 'icon' => '...', 'color' => '...'] ]
 */
$events = $events ?? [];
?>
<div class="erp-timeline" style="position: relative; padding-inline-start: var(--spacing-lg);">
    <!-- Vertical Line -->
    <div style="position: absolute; top: 0; bottom: 0; left: 11px; width: 2px; background: var(--color-border);"></div>
    
    <?php foreach ($events as $event): ?>
        <div class="erp-timeline-item" style="position: relative; margin-bottom: var(--spacing-lg);">
            <!-- Icon -->
            <div style="position: absolute; left: calc(var(--spacing-lg) * -1); top: 0; width: 24px; height: 24px; border-radius: 50%; background: <?= htmlspecialchars($event['color'] ?? 'var(--color-surface)') ?>; border: 2px solid var(--color-surface); box-shadow: 0 0 0 2px <?= htmlspecialchars($event['color'] ?? 'var(--color-border)') ?>; display: flex; align-items: center; justify-content: center; color: white;">
                <i class="ph <?= htmlspecialchars($event['icon'] ?? 'ph-circle') ?>" style="font-size: 0.875rem;"></i>
            </div>
            
            <!-- Content -->
            <div style="padding-inline-start: var(--spacing-md);">
                <div class="d-flex justify-between align-center" style="margin-bottom: 4px;">
                    <h5 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: var(--color-text-main);"><?= htmlspecialchars($event['title']) ?></h5>
                    <span style="font-size: 0.75rem; color: var(--color-text-muted);"><?= htmlspecialchars($event['date']) ?></span>
                </div>
                <?php if (!empty($event['description'])): ?>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--color-text-muted); line-height: 1.4;"><?= htmlspecialchars($event['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>