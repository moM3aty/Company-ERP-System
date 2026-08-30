<?php
// Path: resources/views/hr/organization/index.php
?>
<style>
    .org-container { padding: var(--spacing-xl); background: var(--color-background); border-radius: var(--radius-md); border: 1px solid var(--color-border); overflow-x: auto; min-height: 600px; }
    
    .org-tree ul { padding-top: 20px; position: relative; transition: all 0.5s; display: flex; justify-content: center; }
    .org-tree li { float: left; text-align: center; list-style-type: none; position: relative; padding: 20px 5px 0 5px; transition: all 0.5s; }
    
    /* Lines connecting nodes */
    .org-tree li::before, .org-tree li::after { content: ''; position: absolute; top: 0; right: 50%; border-top: 2px solid var(--color-primary-500); width: 50%; height: 20px; }
    .org-tree li::after { right: auto; left: 50%; border-left: 2px solid var(--color-primary-500); }
    .org-tree li:only-child::after, .org-tree li:only-child::before { display: none; }
    .org-tree li:only-child { padding-top: 0; }
    .org-tree li:first-child::before, .org-tree li:last-child::after { border: 0 none; }
    .org-tree li:last-child::before { border-right: 2px solid var(--color-primary-500); border-radius: 0 5px 0 0; }
    .org-tree li:first-child::after { border-radius: 5px 0 0 0; }
    .org-tree ul ul::before { content: ''; position: absolute; top: 0; left: 50%; border-left: 2px solid var(--color-primary-500); width: 0; height: 20px; }

    /* The Node Card */
    .org-node { background: var(--color-surface); border: 2px solid var(--color-border); padding: 12px 24px; text-decoration: none; color: var(--color-text-main); font-family: inherit; font-size: 0.85rem; display: inline-block; border-radius: var(--radius-md); transition: 0.3s; box-shadow: var(--shadow-sm); cursor: grab; position: relative; min-width: 160px; }
    .org-node:hover, .org-node.drag-over { background: var(--color-primary-100); border-color: var(--color-primary-500); box-shadow: var(--shadow-md); transform: translateY(-3px); }
    .org-node.dragging { opacity: 0.5; transform: scale(0.95); }
    
    .org-node-title { font-weight: 700; color: var(--color-primary-900); font-size: 1rem; margin-bottom: 4px; }
    .org-node-subtitle { color: var(--color-text-muted); font-size: 0.75rem; margin-bottom: 8px; }
    .org-node-badge { background: var(--color-background); border: 1px solid var(--color-border); padding: 2px 8px; border-radius: 12px; font-weight: 700; font-size: 0.7rem; color: var(--color-text-main); }
    
    .org-company { border-color: var(--color-primary-900); border-top-width: 4px; }
    .org-division { border-color: var(--color-accent); border-top-width: 4px; }
    .org-dept { border-color: var(--color-success-text); border-top-width: 4px; }
</style>

<div class="d-flex justify-between align-center margin-b-md">
    <div>
        <h2 style="margin: 0; color: var(--color-primary-900);">Interactive Organization Chart</h2>
        <p class="text-muted" style="margin: 4px 0 0 0; font-size: 0.85rem;">Drag and drop departments or teams to restructure the hierarchy.</p>
    </div>
    <div class="d-flex gap-sm">
        <input type="text" placeholder="Search employees/departments..." style="padding: 8px 12px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-size: 0.875rem;">
        <button class="erp-btn" style="background: var(--color-primary-500); color: white; border: none; padding: 8px 16px; border-radius: var(--radius-sm); font-weight: 600;"><i class="ph ph-floppy-disk"></i> Save Structure</button>
    </div>
</div>

<div class="org-container">
    <div class="org-tree">
        <ul>
            <li>
                <div class="org-node org-company" draggable="true" data-id="1">
                    <div class="org-node-title">Nour Trust HQ</div>
                    <div class="org-node-subtitle"><i class="ph ph-buildings"></i> Main Company</div>
                    <div class="org-node-badge"><i class="ph-fill ph-users"></i> 185 Employees</div>
                </div>
                <ul>
                    <!-- Division 1 -->
                    <li>
                        <div class="org-node org-division" draggable="true" data-id="2">
                            <div class="org-node-title">Operations Div</div>
                            <div class="org-node-subtitle"><i class="ph ph-briefcase"></i> Khaled Youssef</div>
                            <div class="org-node-badge">45 Employees</div>
                        </div>
                        <ul class="drop-zone" data-parent="2">
                            <li>
                                <div class="org-node org-dept" draggable="true" data-id="4">
                                    <div class="org-node-title">Logistics Team</div>
                                    <div class="org-node-subtitle">Ali Hassan</div>
                                </div>
                            </li>
                            <li>
                                <div class="org-node org-dept" draggable="true" data-id="5">
                                    <div class="org-node-title">Procurement</div>
                                    <div class="org-node-subtitle">Mona Ali</div>
                                </div>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Division 2 -->
                    <li>
                        <div class="org-node org-division" draggable="true" data-id="3">
                            <div class="org-node-title">Commercial Div</div>
                            <div class="org-node-subtitle"><i class="ph ph-briefcase"></i> Sara Ahmed</div>
                            <div class="org-node-badge">120 Employees</div>
                        </div>
                        <ul class="drop-zone" data-parent="3">
                            <li>
                                <div class="org-node org-dept" draggable="true" data-id="6">
                                    <div class="org-node-title">B2B Sales</div>
                                    <div class="org-node-subtitle">Omar Zaid</div>
                                </div>
                            </li>
                        </ul>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</div>

<script>
    // HTML5 Drag and Drop Logic for Org Chart
    const nodes = document.querySelectorAll('.org-node');
    const dropZones = document.querySelectorAll('li'); // Any LI can be a drop target for restructuring

    let draggedNode = null;

    nodes.forEach(node => {
        node.addEventListener('dragstart', function(e) {
            draggedNode = this.parentNode; // We drag the whole LI containing the node and its children
            setTimeout(() => this.classList.add('dragging'), 0);
        });

        node.addEventListener('dragend', function() {
            this.classList.remove('dragging');
            nodes.forEach(n => n.classList.remove('drag-over'));
        });

        node.addEventListener('dragenter', function(e) {
            e.preventDefault();
            if(this.parentNode !== draggedNode && !draggedNode.contains(this)) {
                this.classList.add('drag-over');
            }
        });

        node.addEventListener('dragleave', function() {
            this.classList.remove('drag-over');
        });

        node.addEventListener('dragover', function(e) {
            e.preventDefault(); // Necessary to allow dropping
        });

        node.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            
            // Cannot drop inside itself or its own children
            if (this.parentNode === draggedNode || draggedNode.contains(this)) return;

            // Logic: Append the dragged LI (with all its children) as a child of the target LI's UL
            let targetUl = this.parentNode.querySelector('ul');
            if (!targetUl) {
                targetUl = document.createElement('ul');
                this.parentNode.appendChild(targetUl);
            }
            targetUl.appendChild(draggedNode);
            
            // Clean up empty ULs
            document.querySelectorAll('.org-tree ul').forEach(ul => {
                if (ul.children.length === 0) ul.remove();
            });
        });
    });
</script>