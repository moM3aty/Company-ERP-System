<?php
// Path: resources/views/crm/leads/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$t = [
    'ar' => [
        'title' => 'العملاء المحتملين (Leads)',
        'desc' => 'إدارة وتتبع الفرص البيعية والعملاء المحتملين قبل تحويلهم لعملاء فعليين.',
        'add_btn' => 'إضافة عميل محتمل',
        'search' => 'البحث بالاسم، الشركة، أو الهاتف...',
        'col_company' => 'الشركة / العميل',
        'col_contact' => 'بيانات التواصل',
        'col_status' => 'الحالة',
        'col_source' => 'المصدر / التقييم',
        'col_followup' => 'المتابعة القادمة',
        'col_actions' => 'إجراءات',
        'empty_title' => 'لا يوجد عملاء محتملين',
        'empty_desc' => 'ابدأ بإضافة أول فرصة بيعية لك.',
        'convert_title' => 'تأكيد تحويل العميل',
        'convert_confirm' => 'هل أنت متأكد من تحويل هذه الفرصة إلى عميل فعلي؟',
        'convert_btn' => 'تأكيد التحويل',
        'cancel_btn' => 'تراجع وإلغاء',
        'delete_confirm' => 'هل أنت متأكد من حذف هذه الفرصة نهائياً؟',
    ],
    'en' => [
        'title' => 'Leads',
        'desc' => 'Manage and track sales opportunities before converting them to active customers.',
        'add_btn' => 'Add New Lead',
        'search' => 'Search by name, company, or phone...',
        'col_company' => 'Company / Lead',
        'col_contact' => 'Contact Info',
        'col_status' => 'Status',
        'col_source' => 'Source / Score',
        'col_followup' => 'Next Follow-up',
        'col_actions' => 'Actions',
        'empty_title' => 'No Leads Found',
        'empty_desc' => 'Start by adding your first sales opportunity.',
        'convert_title' => 'Confirm Customer Conversion',
        'convert_confirm' => 'Are you sure you want to convert this lead to an active customer?',
        'convert_btn' => 'Confirm Conversion',
        'cancel_btn' => 'Cancel',
        'delete_confirm' => 'Are you sure you want to delete this lead?',
    ]
][$isRtl ? 'ar' : 'en'];

function getStatusBadge($status) {
    $styles = [
        'new' => 'background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd;',
        'contacted' => 'background: #fef3c7; color: #d97706; border: 1px solid #fde68a;',
        'qualified' => 'background: #f3e8ff; color: #9333ea; border: 1px solid #d8b4fe;',
        'lost' => 'background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;',
        'converted' => 'background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;',
    ];
    $s = strtolower($status);
    $style = $styles[$s] ?? $styles['new'];
    return "<span style='padding: 4px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; {$style}'>{$status}</span>";
}
?>

<style>
    .crm-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .crm-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
    .crm-title-box { display: flex; align-items: center; gap: 14px; }
    .crm-title-icon { width: 48px; height: 48px; background: #e0f2fe; color: #0284c7; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(2, 132, 199, 0.1); }
    .crm-title { margin: 0; color: #0f172a; font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px; }
    .crm-desc { margin: 4px 0 0 0; font-size: 0.9rem; color: #64748b; font-weight: 500; }
    
    .btn-primary { background: linear-gradient(135deg, #0284c7, #0369a1); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; text-decoration: none; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25); transition: all 0.2s ease; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35); }
    
    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; margin-top: 20px;}
    .crm-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .crm-table th { padding: 16px 24px; background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem; border-bottom: 2px solid #e2e8f0; }
    .crm-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; transition: background 0.2s; }
    .crm-table tr:hover td { background: #f8fafc; }
    
    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; font-size: 1.1rem; }
    .action-btn:hover { background: #e0f2fe; border-color: #bae6fd; color: #0284c7; }
    .action-btn.delete:hover { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
    .action-btn.convert { color: #10b981; border-color: #a7f3d0; background: #ecfdf5;}
    .action-btn.convert:hover { background: #10b981; color: #fff;}
    
    .empty-state { text-align: center; padding: 80px 20px; }
    .empty-icon { width: 90px; height: 90px; background: #e0f2fe; color: #0284c7; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 3rem; margin-bottom: 20px; }

    /* Custom Conversion Modal */
    .custom-modal-overlay { position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 9999; }
    .custom-modal-overlay.active { display: flex; }
    .custom-modal-card { background: #ffffff; border-radius: 20px; width: 90%; max-width: 440px; padding: 32px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); animation: modalPop 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    @keyframes modalPop { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
    .modal-icon-box { width: 72px; height: 72px; background: #ecfdf5; color: #10b981; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2.2rem; margin-bottom: 16px; border: 4px solid #d1fae5; }
    .modal-title-text { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0 0 8px 0; }
    .modal-desc-text { color: #64748b; font-size: 0.95rem; font-weight: 500; margin: 0 0 24px 0; line-height: 1.5; }
    .modal-actions-row { display: flex; gap: 12px; justify-content: center; }
    .modal-btn-confirm { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 700; font-family: inherit; cursor: pointer; transition: 0.2s; flex: 1; }
    .modal-btn-confirm:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
    .modal-btn-cancel { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 10px 24px; border-radius: 10px; font-weight: 700; font-family: inherit; cursor: pointer; transition: 0.2s; flex: 1; }
    .modal-btn-cancel:hover { background: #e2e8f0; color: #0f172a; }
</style>

<div class="crm-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="crm-header">
        <div class="crm-title-box">
            <div class="crm-title-icon"><i class="ph-duotone ph-magnet"></i></div>
            <div>
                <h2 class="crm-title"><?= $t['title'] ?></h2>
                <p class="crm-desc"><?= $t['desc'] ?></p>
            </div>
        </div>
        <a href="/ERP/crm/leads/create" class="btn-primary"><i class="ph-bold ph-plus"></i> <?= $t['add_btn'] ?></a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th style="width: 20%;"><?= $t['col_company'] ?></th>
                        <th style="width: 25%;"><?= $t['col_contact'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_status'] ?></th>
                        <th style="width: 15%; text-align: center;"><?= $t['col_source'] ?></th>
                        <th style="width: 15%;"><?= $t['col_followup'] ?></th>
                        <th style="width: 10%; text-align: center;"><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leads)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="ph-duotone ph-users"></i></div>
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.4rem; font-weight: 800;"><?= $t['empty_title'] ?></h3>
                                    <p style="color: #64748b; margin: 0;"><?= $t['empty_desc'] ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leads as $l): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800; color: #0f172a; font-size: 1rem;"><?= htmlspecialchars($l->company_name) ?></div>
                                    <div style="color: #64748b; font-size: 0.85rem;"><i class="ph-fill ph-user text-muted"></i> <?= htmlspecialchars($l->contact_person) ?></div>
                                </td>
                                <td>
                                    <?php if($l->phone): ?><div style="font-weight: 600; color: #334155;"><i class="ph-fill ph-phone-call" style="color: #0284c7;"></i> <?= htmlspecialchars($l->phone) ?></div><?php endif; ?>
                                    <?php if($l->email): ?><div style="font-size: 0.85rem; color: #64748b; margin-top: 4px;"><i class="ph-fill ph-envelope" style="color: #94a3b8;"></i> <?= htmlspecialchars($l->email) ?></div><?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?= getStatusBadge($l->status) ?>
                                </td>
                                <td style="text-align: center;">
                                    <div style="font-weight: 700; color: #475569;"><?= htmlspecialchars($l->source) ?></div>
                                    <div style="font-size: 0.8rem; color: #f59e0b; font-weight: 800; margin-top: 4px;"><i class="ph-fill ph-star"></i> <?= $l->score ?> Pts</div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: <?= (strtotime($l->next_follow_up) < time() && $l->status != 'converted') ? '#dc2626' : '#059669' ?>;">
                                        <i class="ph-fill ph-calendar-check"></i> <?= htmlspecialchars($l->next_follow_up ?? 'N/A') ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 4px;">By: <?= htmlspecialchars($l->owner_name) ?></div>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <?php if($l->status !== 'converted'): ?>
                                        <form action="/ERP/crm/leads/<?= $l->id ?>/convert" method="POST" style="display:inline;" onsubmit="return handleConvertSubmit(event, this);">
                                            <button type="submit" class="action-btn convert" title="تحويل لعميل فعلي"><i class="ph-bold ph-arrows-left-right"></i></button>
                                        </form>
                                        <a href="/ERP/crm/leads/<?= $l->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                                    <?php endif; ?>
                                    <form action="/ERP/crm/leads/<?= $l->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('<?= $t['delete_confirm'] ?>');">
                                        <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Custom Conversion Modal Container -->
<div class="custom-modal-overlay" id="convertModalOverlay">
    <div class="custom-modal-card">
        <div class="modal-icon-box">
            <i class="ph-bold ph-user-check"></i>
        </div>
        <h3 class="modal-title-text"><?= $t['convert_title'] ?></h3>
        <p class="modal-desc-text"><?= $t['convert_confirm'] ?></p>
        <div class="modal-actions-row">
            <button type="button" class="modal-btn-confirm" id="modalConfirmBtn"><i class="ph-bold ph-check"></i> <?= $t['convert_btn'] ?></button>
            <button type="button" class="modal-btn-cancel" id="modalCancelBtn"><?= $t['cancel_btn'] ?></button>
        </div>
    </div>
</div>

<script>
let pendingForm = null;

function handleConvertSubmit(e, form) {
    e.preventDefault();
    e.stopPropagation();
    pendingForm = form;
    
    document.getElementById('convertModalOverlay').classList.add('active');
    return false;
}

document.addEventListener("DOMContentLoaded", function() {
    const modalOverlay = document.getElementById('convertModalOverlay');
    const confirmBtn   = document.getElementById('modalConfirmBtn');
    const cancelBtn    = document.getElementById('modalCancelBtn');

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (pendingForm) {
                modalOverlay.classList.remove('active');
                pendingForm.submit();
            }
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            modalOverlay.classList.remove('active');
            pendingForm = null;
        });
    }

    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                modalOverlay.classList.remove('active');
                pendingForm = null;
            }
        });
    }
});
</script>