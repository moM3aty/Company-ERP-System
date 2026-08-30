<?php
// Path: resources/views/components/tabs.php

/**
 * @var array $tabs ['tab_id' => 'Tab Label']
 * @var string $activeTab The ID of the default active tab
 * @var array $slots Associative array of HTML content for each tab ['tab_id' => '<html>...']
 */
?>
<div class="erp-tabs-container">
    <div class="erp-tabs-header d-flex gap-md" style="border-bottom: 1px solid var(--color-border); margin-bottom: var(--spacing-lg); overflow-x: auto;">
        <?php foreach ($tabs as $id => $label): ?>
            <button 
                class="erp-tab-btn <?= $id === $activeTab ? 'active' : '' ?>" 
                data-target="<?= htmlspecialchars($id) ?>"
                style="background: none; border: none; padding: 12px 16px; font-size: 0.875rem; font-weight: 600; cursor: pointer; color: <?= $id === $activeTab ? 'var(--color-primary-500)' : 'var(--color-text-muted)' ?>; border-bottom: 2px solid <?= $id === $activeTab ? 'var(--color-primary-500)' : 'transparent' ?>; transition: all var(--transition-fast); white-space: nowrap;"
                onclick="switchTab(this, '<?= htmlspecialchars($id) ?>')"
            >
                <?= htmlspecialchars($label) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="erp-tabs-content">
        <?php foreach ($slots as $id => $content): ?>
            <div id="tab-<?= htmlspecialchars($id) ?>" class="erp-tab-pane" style="display: <?= $id === $activeTab ? 'block' : 'none' ?>;">
                <?= $content ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function switchTab(btn, tabId) {
        // Reset all buttons
        const container = btn.closest('.erp-tabs-container');
        container.querySelectorAll('.erp-tab-btn').forEach(b => {
            b.classList.remove('active');
            b.style.color = 'var(--color-text-muted)';
            b.style.borderBottomColor = 'transparent';
        });
        
        // Hide all panes
        container.querySelectorAll('.erp-tab-pane').forEach(p => p.style.display = 'none');
        
        // Activate selected
        btn.classList.add('active');
        btn.style.color = 'var(--color-primary-500)';
        btn.style.borderBottomColor = 'var(--color-primary-500)';
        container.querySelector('#tab-' + tabId).style.display = 'block';
    }
</script>