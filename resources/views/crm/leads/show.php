<?php
// Path: resources/views/crm/leads/show.php
/** @var object $lead */
?>
<div class="d-flex justify-between align-center margin-b-md">
    <div>
        <h2 style="margin: 0; color: var(--color-primary-900); display: flex; align-items: center; gap: 8px;">
            <i class="ph-duotone ph-target text-danger"></i> Lead: <?= htmlspecialchars($lead->company_name) ?>
        </h2>
        <p class="text-muted" style="margin: 4px 0 0 0; font-size: 0.85rem;">Contact: <?= htmlspecialchars($lead->contact_person) ?> | Owner: <strong><?= $lead->owner ?></strong></p>
    </div>
    <div class="d-flex gap-sm">
        <a href="<?= url('/api/crm/leads') ?>" class="erp-btn" style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 8px 16px; border-radius: var(--radius-sm); text-decoration: none; color: var(--color-text-main); font-weight: 500;"><i class="ph ph-arrow-left"></i> Back</a>
        
        <?php if($lead->status !== 'converted'): ?>
            <form action="<?= url('/api/crm/leads/' . $lead->id . '/convert') ?>" method="POST" style="margin: 0;">
                <button type="submit" class="erp-btn" style="background: var(--color-success-text); color: white; border: none; padding: 8px 16px; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; transition: 0.2s;" onmouseover="this.style.opacity='0.9'"><i class="ph ph-check-circle"></i> Convert to Customer</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'converted'): ?>
    <div style="background: var(--color-success-bg); color: var(--color-success-text); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 16px; font-weight: bold; border: 1px solid var(--color-success-border);">
        <i class="ph-fill ph-check-circle"></i> Lead successfully converted into a Customer Record!
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--spacing-xl);">
    
    <!-- Left: Activity & Follow-Ups -->
    <div class="erp-card" style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); padding: var(--spacing-lg);">
        <div class="d-flex justify-between align-center margin-b-md" style="border-bottom: 1px solid var(--color-border); padding-bottom: 8px;">
            <h4 style="margin: 0; color: var(--color-primary-900);"><i class="ph-fill ph-clock-counter-clockwise"></i> Activities & Follow-Ups</h4>
            <button class="erp-btn" style="background: none; border: none; color: var(--color-primary-500); font-weight: 600; font-size: 0.85rem; cursor: pointer;"><i class="ph ph-plus"></i> Log Activity</button>
        </div>
        
        <div style="padding: 16px; border-left: 3px solid var(--color-primary-500); margin-left: 8px; margin-bottom: 16px; background: var(--color-background); border-radius: 0 var(--radius-sm) var(--radius-sm) 0;">
            <div style="font-size: 0.75rem; color: var(--color-text-muted); font-family: monospace;">2026-08-19 10:00 AM</div>
            <div style="font-weight: 700; color: var(--color-primary-900); margin-top: 4px;"><i class="ph-fill ph-envelope-simple text-primary"></i> Sent Proposal via Email</div>
            <div style="font-size: 0.85rem; color: var(--color-text-main); margin-top: 4px;">Quoted for 5 Enterprise Servers. Waiting for response.</div>
        </div>
        
        <div style="padding: 16px; border-left: 3px solid var(--color-border); margin-left: 8px;">
            <div style="font-size: 0.75rem; color: var(--color-text-muted); font-family: monospace;">2026-08-15 14:30 PM</div>
            <div style="font-weight: 700; color: var(--color-primary-900); margin-top: 4px;"><i class="ph-fill ph-phone-call text-muted"></i> Initial Phone Call</div>
            <div style="font-size: 0.85rem; color: var(--color-text-main); margin-top: 4px;">Client is interested in expanding their datacenter. Scheduled a demo.</div>
        </div>
    </div>

    <!-- Right: Lead Details & Score -->
    <div style="display: flex; flex-direction: column; gap: var(--spacing-lg);">
        <div class="erp-card" style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); padding: var(--spacing-lg); text-align: center;">
            <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Lead Score</div>
            <div style="font-size: 3rem; font-weight: 800; color: var(--color-success-text); margin: 8px 0;"><?= $lead->score ?></div>
            <div style="font-size: 0.85rem; color: var(--color-success-text); background: var(--color-success-bg); border: 1px solid var(--color-success-border); padding: 4px 12px; border-radius: 99px; display: inline-block; font-weight: 700;"><i class="ph-fill ph-fire"></i> Hot Opportunity</div>
        </div>

        <div class="erp-card" style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); padding: var(--spacing-lg);">
            <h4 style="margin: 0 0 16px 0; color: var(--color-primary-900); border-bottom: 1px solid var(--color-border); padding-bottom: 8px;"><i class="ph-fill ph-address-book"></i> Contact Information</h4>
            <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.875rem;">
                <div class="d-flex justify-between"><span class="text-muted">Email:</span> <a href="mailto:<?= $lead->email ?>" style="font-weight: 600; color: var(--color-primary-500);"><?= $lead->email ?></a></div>
                <div class="d-flex justify-between"><span class="text-muted">Phone:</span> <span class="font-semibold"><?= $lead->phone ?></span></div>
                <div class="d-flex justify-between"><span class="text-muted">Source:</span> <span class="badge" style="background: var(--color-background); border: 1px solid var(--color-border); padding: 2px 6px; border-radius: 4px;"><?= $lead->source ?></span></div>
                <div class="d-flex justify-between"><span class="text-muted">Status:</span> <span class="font-semibold text-warning"><?= $lead->status ?></span></div>
            </div>
        </div>
    </div>
</div>