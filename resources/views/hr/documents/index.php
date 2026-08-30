<?php
// Path: resources/views/hr/documents/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

$statusMap = [
    'active' => ['label' => 'سارية', 'color' => '#059669', 'bg' => '#ecfdf5', 'icon' => 'ph-check-circle'],
    'pending_renewal' => ['label' => 'قيد التجديد', 'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ph-clock-countdown'],
    'expired' => ['label' => 'منتهية', 'color' => '#dc2626', 'bg' => '#fef2f2', 'icon' => 'ph-warning-circle'],
];

$typeMap = [
    'passport' => 'جواز سفر',
    'national_id' => 'هوية / إقامة',
    'contract' => 'عقد عمل',
    'certificate' => 'شهادة علمية',
    'visa' => 'تأشيرة / إذن عمل',
    'other' => 'وثيقة أخرى'
];
?>

<style>
    :root {
        --c-doc: #0f766e;
        --c-doc-dark: #115e59;
        --c-doc-light: #ccfbf1;
        --c-border: #cbd5e1;
        --c-text-dark: #0f172a;
        --c-text-muted: #64748b;
    }

    .doc-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    .doc-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
    .doc-title-box { display: flex; align-items: center; gap: 16px; }
    .doc-icon { width: 48px; height: 48px; background: var(--c-doc-light); color: var(--c-doc); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(15, 118, 110, 0.15); }
    .doc-title { margin: 0; color: var(--c-text-dark); font-size: 1.6rem; font-weight: 900; }
    .btn-doc { background: linear-gradient(135deg, var(--c-doc), var(--c-doc-dark)); color: #ffffff !important; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(15, 118, 110, 0.25); }

    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width:1024px){ .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    .kpi-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; background: var(--c-doc-light); color: var(--c-doc); }
    .kpi-info h4 { margin: 0 0 2px 0; font-size: 0.75rem; color: var(--c-text-muted); font-weight: 800; }
    .kpi-info p { margin: 0; font-size: 1.2rem; font-weight: 900; font-family: monospace; color: var(--c-text-dark); }

    .search-bar { background: #ffffff; border: 1px solid var(--c-border); border-radius: 14px; padding: 12px; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
    .form-control { border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; }
    .btn-search { background: var(--c-text-dark); color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 800; cursor: pointer; }

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
    .doc-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .doc-table th { padding: 16px 20px; background: #f8fafc; color: var(--c-text-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; }
    .doc-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--c-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; }
    .action-btn:hover { background: var(--c-doc-light); color: var(--c-doc); border-color: #99f6e4; }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
    .page-link { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--c-border); background: #ffffff; color: var(--c-text-muted); text-decoration: none; font-weight: 800; }
    .page-link.active { background: var(--c-doc); color: #ffffff; border-color: var(--c-doc); }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
</style>

<div class="doc-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="doc-header">
        <div class="doc-title-box">
            <div class="doc-icon"><i class="ph-duotone ph-folder-user"></i></div>
            <div>
                <h2 class="doc-title">أرشيف ووثائق الموظفين (Employee Documents)</h2>
                <p style="margin:4px 0 0 0; color:var(--c-text-muted);">حفظ وأرشفة الجوازات، الهويات، العقود، والشهادات داخل مجلدات الموظفين.</p>
            </div>
        </div>
        <a href="/ERP/hr/documents/create" class="btn-doc"><i class="ph-bold ph-upload-simple"></i> رفع وثيقة جديدة</a>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="kpi-row">
        <div class="kpi-card"><div class="kpi-icon"><i class="ph-duotone ph-files"></i></div><div class="kpi-info"><h4>إجمالي الوثائق</h4><p><?= number_format($stats->total_docs ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#ecfdf5; color:#059669;"><i class="ph-duotone ph-check-circle"></i></div><div class="kpi-info"><h4 style="color:#059669;">وثائق سارية</h4><p><?= number_format($stats->active_docs ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="ph-duotone ph-clock-countdown"></i></div><div class="kpi-info"><h4 style="color:#d97706;">قيد التجديد</h4><p><?= number_format($stats->pending_docs ?? 0) ?></p></div></div>
        <div class="kpi-card"><div class="kpi-icon" style="background:#fef2f2; color:#dc2626;"><i class="ph-duotone ph-warning-circle"></i></div><div class="kpi-info"><h4 style="color:#dc2626;">وثائق منتهية</h4><p><?= number_format($stats->expired_docs ?? 0) ?></p></div></div>
    </div>

    <form action="/ERP/hr/documents" method="GET" class="search-bar">
        <input type="text" name="search" class="form-control" style="flex:2; min-width:180px;" placeholder="ابحث بكود الوثيقة، العنوان، اسم الموظف..." value="<?= htmlspecialchars((string)$search) ?>">
        
        <select name="employee_id" class="form-control" style="flex:1.5; min-width:160px;">
            <option value="">-- تصفية بمجلد الموظف --</option>
            <?php foreach($employees as $emp): ?>
                <option value="<?= $emp->id ?>" <?= ($employeeFilter == $emp->id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$emp->emp_code) ?> - <?= htmlspecialchars((string)$emp->name_ar) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="document_type" class="form-control" style="flex:1; min-width:130px;">
            <option value="">-- نوع الوثيقة --</option>
            <option value="passport" <?= ($typeFilter==='passport')?'selected':'' ?>>جواز سفر</option>
            <option value="national_id" <?= ($typeFilter==='national_id')?'selected':'' ?>>هوية / إقامة</option>
            <option value="contract" <?= ($typeFilter==='contract')?'selected':'' ?>>عقد عمل</option>
            <option value="certificate" <?= ($typeFilter==='certificate')?'selected':'' ?>>شهادة علمية</option>
            <option value="visa" <?= ($typeFilter==='visa')?'selected':'' ?>>تأشيرة / إذن عمل</option>
            <option value="other" <?= ($typeFilter==='other')?'selected':'' ?>>أخرى</option>
        </select>

        <select name="status" class="form-control" style="flex:1; min-width:120px;">
            <option value="">-- الحالة --</option>
            <option value="active" <?= ($statusFilter==='active')?'selected':'' ?>>سارية</option>
            <option value="pending_renewal" <?= ($statusFilter==='pending_renewal')?'selected':'' ?>>قيد التجديد</option>
            <option value="expired" <?= ($statusFilter==='expired')?'selected':'' ?>>منتهية</option>
        </select>

        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> بحث</button>
    </form>

    <div class="table-card">
        <table class="doc-table">
            <thead>
                <tr>
                    <th style="width: 12%;">كود الوثيقة</th>
                    <th style="width: 25%;">عنوان الوثيقة والنوع</th>
                    <th style="width: 20%;">مجلد الموظف والإدارة</th>
                    <th style="width: 18%;">تاريخ الإصدار / الانتهاء</th>
                    <th style="width: 10%; text-align: center;">الحالة</th>
                    <th style="width: 15%; text-align: center;">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documents)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 700;">لا توجد وثائق مؤرشفة بمواصفات البحث.</td></tr>
                <?php else: foreach ($documents as $doc): 
                    $st = $statusMap[$doc->status] ?? $statusMap['active'];
                    $fileUrl = !empty($doc->file_path) ? '/ERP' . $doc->file_path : null;
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 900; color: var(--c-doc-dark); font-size: 0.95rem;">
                            <a href="/ERP/hr/documents/<?= $doc->id ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars((string)$doc->document_code) ?></a>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--c-text-dark);"><?= htmlspecialchars((string)$doc->title_ar) ?></div>
                            <span style="font-size: 0.75rem; background:#f1f5f9; padding:2px 8px; border-radius:4px; font-weight:700; color:#475569;">
                                <?= $typeMap[$doc->document_type] ?? $doc->document_type ?>
                            </span>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: #334155; display:flex; align-items:center; gap:4px;">
                                <i class="ph-bold ph-folder" style="color:var(--c-doc);"></i> <?= htmlspecialchars((string)($doc->employee_name ?: 'عام')) ?>
                            </div>
                            <div style="font-size: 0.75rem; color: #64748b; font-family:monospace;"><?= htmlspecialchars((string)$doc->emp_code) ?> - <?= htmlspecialchars((string)($doc->dept_name ?: 'عام')) ?></div>
                        </td>
                        <td style="font-family: monospace; font-size:0.85rem; font-weight:700;">
                            <div><span style="color:#64748b;">إصدار:</span> <?= htmlspecialchars((string)($doc->issue_date ?: '---')) ?></div>
                            <div><span style="color:#64748b;">انتهاء:</span> <span style="color:<?= $doc->status === 'expired' ? '#dc2626' : 'inherit' ?>;"><?= htmlspecialchars((string)($doc->expiry_date ?: 'غير منتهي')) ?></span></div>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-status" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>;">
                                <i class="ph-bold <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="/ERP/hr/documents/<?= $doc->id ?>" class="action-btn" title="عرض التفاصيل"><i class="ph-bold ph-eye"></i></a>
                            <?php if($fileUrl): ?>
                                <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" class="action-btn" title="معاينة الملف المرفوع"><i class="ph-bold ph-file-arrow-down"></i></a>
                            <?php endif; ?>
                            <a href="/ERP/hr/documents/<?= $doc->id ?>/edit" class="action-btn" title="تعديل"><i class="ph-bold ph-pencil-simple"></i></a>
                            <form action="/ERP/hr/documents/<?= $doc->id ?>/delete" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذه الوثيقة؟');">
                                <button type="submit" class="action-btn delete" title="حذف"><i class="ph-bold ph-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&document_type=<?= urlencode($typeFilter) ?>&employee_id=<?= urlencode($employeeFilter) ?>" class="page-link <?= $i == ($currentPage ?? 1) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>