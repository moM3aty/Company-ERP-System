<?php
// Path: resources/views/components/dropdown.php

/**
 * @var string $id
 * @var string $trigger HTML content for the button/element triggering the dropdown
 * @var array $items Array of items ['label' => '', 'icon' => '', 'link' => '', 'color' => '']
 * @var string $align (left, right)
 */
$align = $align ?? 'right';
?>
<div class="erp-dropdown" style="position: relative; display: inline-block;">
    <!-- Trigger -->
    <div onclick="toggleDropdown('<?= htmlspecialchars($id) ?>')" style="cursor: pointer;">
        <?= $trigger ?>
    </div>

    <!-- Dropdown Menu -->
    <div id="<?= htmlspecialchars($id) ?>" class="erp-dropdown-menu" style="display: none; position: absolute; top: calc(100% + 4px); <?= $align === 'right' ? 'right: 0;' : 'left: 0;' ?> min-width: 180px; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); box-shadow: var(--shadow-md); z-index: 100; padding: 4px; animation: dropdownFadeIn var(--transition-fast);">
        <ul style="list-style: none; margin: 0; padding: 0;">
            <?php foreach ($items as $item): ?>
                <?php if ($item === 'divider'): ?>
                    <li style="border-top: 1px solid var(--color-border); margin: 4px 0;"></li>
                <?php else: ?>
                    <li>
                        <a href="<?= htmlspecialchars($item['link'] ?? '#') ?>" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; text-decoration: none; color: <?= htmlspecialchars($item['color'] ?? 'var(--color-text-main)') ?>; font-size: 0.875rem; border-radius: var(--radius-sm); transition: background var(--transition-fast);" onmouseover="this.style.background='var(--color-background)';" onmouseout="this.style.background='transparent';">
                            <?php if (isset($item['icon'])): ?>
                                <i class="ph <?= htmlspecialchars($item['icon']) ?>" style="font-size: 1rem;"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($item['label']) ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<script>
    function toggleDropdown(id) {
        const menu = document.getElementById(id);
        const isVisible = menu.style.display === 'block';
        
        // Close all other dropdowns first
        document.querySelectorAll('.erp-dropdown-menu').forEach(el => el.style.display = 'none');
        
        if (!isVisible) {
            menu.style.display = 'block';
        }
    }

    // Close when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.erp-dropdown')) {
            document.querySelectorAll('.erp-dropdown-menu').forEach(el => el.style.display = 'none');
        }
    });
</script>

<style>
    @keyframes dropdownFadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>