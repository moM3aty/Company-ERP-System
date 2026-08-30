<?php
// Path: resources/views/admin/backups/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);
?>

<!-- تحميل مكتبة SweetAlert2 القياسية -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/ERP/assets/js/app-alerts.js"></script>

<style>
    /* المتغيرات المتكيفة مع المظهر المضيء والداكن (Dark Mode Support) */
    :root {
        --bk-primary: #059669;
        --bk-dark: #047857;
        --bk-light: #ecfdf5;
        --bk-border: #a7f3d0;
        --bk-card-bg: #ffffff;
        --bk-text: #0f172a;
        --bk-muted: #64748b;
        --bk-table-hover: #f0fdf4;
    }

    body.dark-mode, [data-theme="dark"] {
        --bk-primary: #10b981;
        --bk-dark: #059669;
        --bk-light: rgba(16, 185, 129, 0.12);
        --bk-border: #064e3b;
        --bk-card-bg: #1e293b;
        --bk-text: #f8fafc;
        --bk-muted: #94a3b8;
        --bk-table-hover: rgba(16, 185, 129, 0.08);
    }

    .bk-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .bk-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid var(--bk-border); padding-bottom: 16px; flex-wrap: wrap; gap: 16px; }
    .bk-title-box { display: flex; align-items: center; gap: 16px; }
    .bk-icon { width: 52px; height: 50px; background: var(--bk-light); color: var(--bk-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.15); border: 1px solid var(--bk-border); }
    .bk-title { margin: 0; color: var(--bk-text); font-size: 1.6rem; font-weight: 900; }
    
    .btn-create { background: linear-gradient(135deg, var(--bk-primary), var(--bk-dark)); color: #ffffff !important; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25); transition: 0.2s; cursor: pointer; }
    .btn-create:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(5, 150, 105, 0.35); }

    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width: 768px){ .stats-grid { grid-template-columns: 1fr; } }
    .stat-card { background: var(--bk-card-bg); border: 1px solid var(--bk-border); border-radius: 14px; padding: 18px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border-left: 4px solid var(--bk-primary); }
    .stat-val { font-size: 1.6rem; font-weight: 900; color: var(--bk-text); font-family: monospace; }
    .stat-lbl { font-size: 0.85rem; font-weight: 700; color: var(--bk-muted); }

    .search-bar { background: var(--bk-card-bg); border: 1px solid var(--bk-border); border-radius: 12px; padding: 16px; display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; align-items: center; }
    .form-control { border: 1px solid var(--bk-border); border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: var(--bk-card-bg); color: var(--bk-text); outline: none; }
    .form-control:focus { border-color: var(--bk-primary); box-shadow: 0 0 0 3px var(--bk-light); }
    .btn-search { background: var(--bk-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 8px;}

    .table-card { background: var(--bk-card-bg); border: 1px solid var(--bk-border); border-radius: 14px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .bk-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .bk-table th { padding: 16px 20px; background: var(--bk-light); color: var(--bk-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid var(--bk-border); font-size: 0.75rem; white-space: nowrap; }
    .bk-table td { padding: 14px 20px; border-bottom: 1px solid var(--bk-border); color: var(--bk-text); vertical-align: middle; }
    .bk-table tr:hover td { background: var(--bk-table-hover); }

    .action-btn { width: 36px; height: 36px; border-radius: 8px; border: 1px solid var(--bk-border); background: var(--bk-card-bg); color: var(--bk-primary); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin: 0 2px; transition: 0.2s;}
    .action-btn:hover { background: var(--bk-light); border-color: var(--bk-primary); }
    .action-btn.delete { color: #ef4444; }
    .action-btn.delete:hover { background: rgba(239, 68, 68, 0.12); color: #dc2626; border-color: #fca5a5; }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; font-family: monospace; display: inline-flex; align-items: center; gap: 4px; }

    .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 24px; }
    .page-link { padding: 8px 14px; border: 1px solid var(--bk-border); background: var(--bk-card-bg); color: var(--bk-text); border-radius: 8px; text-decoration: none; font-weight: 700; transition: 0.2s; }
    .page-link:hover { background: var(--bk-light); border-color: var(--bk-primary); color: var(--bk-primary); }
    .page-link.active { background: var(--bk-primary); color: #fff; border-color: var(--bk-primary); pointer-events: none; }
</style>

<div class="bk-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="bk-header">
        <div class="bk-title-box">
            <div class="bk-icon"><i class="ph-duotone ph-database"></i></div>
            <div>
                <h2 class="bk-title">النسخ الاحتياطي والصيانة (Backups & Recovery)</h2>
                <p style="margin:4px 0 0 0; color:var(--bk-muted);">توليد نسخ احتياطية لقواعد البيانات ومراقبة سلامة الملفات.</p>
            </div>
        </div>
        
        <!-- نموذج الإنشاء المربوط مع SweetAlert2 الاحترافي -->
        <form id="createBackupForm" action="/ERP/admin/backups/create" method="POST">
            <button type="button" onclick="triggerCreateBackup()" class="btn-create">
                <i class="ph-bold ph-hard-drives"></i> إنشاء نسخة احتياطية الآن
            </button>
        </form>
    </div>

    <?php if($flashMsg): ?>
        <script>document.addEventListener('DOMContentLoaded', () => ERPAlerts.toastSuccess("<?= htmlspecialchars($flashMsg) ?>"));</script>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="stats-grid">
        <div class="stat-card">
            <div style="background:var(--bk-light); color:var(--bk-primary); width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="ph-fill ph-database"></i></div>
            <div><div class="stat-val"><?= number_format($stats->total ?? 0) ?></div><div class="stat-lbl">إجمالي النسخ الاحتياطية</div></div>
        </div>
        <div class="stat-card" style="border-left-color: #0284c7;">
            <div style="background:rgba(2, 132, 199, 0.12); color:#0284c7; width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="ph-fill ph-check-circle"></i></div>
            <div><div class="stat-val"><?= number_format($stats->completed ?? 0) ?></div><div class="stat-lbl">نسخ مكتملة وجاهزة</div></div>
        </div>
        <div class="stat-card" style="border-left-color: #8b5cf6;">
            <div style="background:rgba(139, 92, 246, 0.12); color:#8b5cf6; width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="ph-fill ph-clock"></i></div>
            <div><div class="stat-val" style="font-size:1.1rem; font-family:inherit;"><?= htmlspecialchars($stats->latest) ?></div><div class="stat-lbl">أحدث تاريخ توليد</div></div>
        </div>
    </div>

    <!-- Search & Filters -->
    <form action="/ERP/admin/backups" method="GET" class="search-bar">
        <div style="flex: 2; min-width: 250px;">
            <input type="text" name="search" class="form-control" style="width: 100%;" placeholder="بحث باسم الملف أو القائم بالتوليد..." value="<?= htmlspecialchars($search ?? '') ?>">
        </div>
        <div style="flex: 1; min-width: 150px;">
            <select name="status" class="form-control" style="width: 100%;">
                <option value="">-- كل الحالات --</option>
                <option value="completed" <?= ($statusFilter === 'completed') ? 'selected' : '' ?>>مكتملة (Completed)</option>
                <option value="failed" <?= ($statusFilter === 'failed') ? 'selected' : '' ?>>فاشلة (Failed)</option>
            </select>
        </div>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> تصفية</button>
    </form>

    <!-- Data Table -->
    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="bk-table">
                <thead>
                    <tr>
                        <th style="width: 28%;">اسم ملف النسخة</th>
                        <th style="width: 12%;">حجم الملف</th>
                        <th style="width: 12%;">الجداول</th>
                        <th style="width: 18%;">بواسطة</th>
                        <th style="width: 15%;">التاريخ والوقت</th>
                        <th style="width: 8%; text-align: center;">الحالة</th>
                        <th style="width: 7%; text-align: center;">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($backups)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--bk-muted); font-weight: 700;">لا توجد نسخ احتياطية مطابقة للبحث.</td></tr>
                    <?php else: foreach ($backups as $b): ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: 900; color: var(--bk-primary); font-size: 0.95rem;">
                                <i class="ph-duotone ph-file-sql"></i> <?= htmlspecialchars($b->filename) ?>
                            </td>
                            <td style="font-family: monospace; font-weight: 700; color: #0284c7;"><?= htmlspecialchars($b->file_size) ?></td>
                            <td style="font-weight: 800;"><span style="background:var(--bk-light); padding:2px 8px; border-radius:6px; font-size:0.8rem;"><?= (int)($b->tables_count ?? 0) ?> جدول</span></td>
                            <td style="font-weight: 700;"><?= htmlspecialchars($b->created_by ?: 'النظام') ?></td>
                            <td style="font-family: monospace; font-weight: 600; color: var(--bk-muted); font-size: 0.85rem;"><?= date('Y-m-d H:i', strtotime($b->created_at ?? 'now')) ?></td>
                            <td style="text-align: center;">
                                <?php if(($b->status ?? 'completed') === 'completed'): ?>
                                    <span class="badge-status" style="background:rgba(16, 185, 129, 0.15); color:#10b981; border:1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> مكتمل</span>
                                <?php else: ?>
                                    <span class="badge-status" style="background:rgba(239, 68, 68, 0.15); color:#ef4444; border:1px solid #fecaca;"><i class="ph-fill ph-x-circle"></i> فاشل</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="/ERP/admin/backups/<?= $b->id ?>" class="action-btn" title="عرض التفاصيل"><i class="ph-bold ph-eye"></i></a>
                                <a href="/ERP/admin/backups/<?= $b->id ?>/download" class="action-btn" style="color:var(--bk-primary);" title="تحميل الملف"><i class="ph-bold ph-download-simple"></i></a>
                                
                                <form id="deleteForm_<?= $b->id ?>" action="/ERP/admin/backups/<?= $b->id ?>/delete" method="POST" style="display:inline;">
                                    <button type="button" onclick="triggerDeleteBackup(<?= $b->id ?>)" class="action-btn delete" title="حذف الملف"><i class="ph-bold ph-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" 
                   class="page-link <?= ($i === $currentPage) ? 'active' : '' ?>">
                   <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

</div>

<!-- ربط الأحداث مع SweetAlert2 -->
<script>
    const ERPAlerts = {
    // 1. تنبيه الحذف التحذيري (Danger)
    confirmDelete: function(title, text, callback) {
        Swal.fire({
            title: title || 'هل أنت متأكد من عملية الحذف؟',
            text: text || 'لن تتمكن من استعادة هذه البيانات بعد الحذف!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="ph-bold ph-trash"></i> نعم، تأكيد الحذف',
            cancelButtonText: 'تراجع وإلغاء',
            reverseButtons: true,
            customClass: {
                popup: 'erp-swal-popup',
                title: 'erp-swal-title'
            }
        }).then((result) => {
            if (result.isConfirmed && typeof callback === 'function') {
                callback();
            }
        });
    },

    // 2. تنبيه الإجراءات الإيجابية (توليد نسخة احتياطية، حفظ، معالجة)
    confirmAction: function(options, callback) {
        Swal.fire({
            title: options.title || 'تأكيد الإجراء',
            text: options.text || 'هل ترغب في الاستمرار؟',
            icon: options.icon || 'question',
            showCancelButton: true,
            confirmButtonColor: options.confirmColor || '#059669',
            cancelButtonColor: '#64748b',
            confirmButtonText: options.confirmText || 'تأكيد',
            cancelButtonText: 'إلغاء',
            reverseButtons: true,
            customClass: {
                popup: 'erp-swal-popup'
            }
        }).then((result) => {
            if (result.isConfirmed && typeof callback === 'function') {
                callback();
            }
        });
    },

    // 3. إشعار النجاح الفوري (Toast Success)
    toastSuccess: function(msg) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: msg,
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true
        });
    }
};

function triggerCreateBackup() {
    ERPAlerts.confirmAction({
        title: 'إنشاء نسخة احتياطية جديدة',
        text: 'هل تأكدت من رغبتك في توليد واستخراج نسخة كاملة من قاعدة البيانات الآن؟',
        icon: 'info',
        confirmColor: '#059669',
        confirmText: '<i class="ph-bold ph-hard-drives"></i> بدء الإنشاء'
    }, function() {
        document.getElementById('createBackupForm').submit();
    });
}

function triggerDeleteBackup(id) {
    ERPAlerts.confirmDelete(
        'حذف ملف النسخة الاحتياطية',
        'هل أنت متأكد من حذف هذا الملف نهائياً من السيرفر؟ لن يمكنك استرجاعه!',
        function() {
            document.getElementById('deleteForm_' + id).submit();
        }
    );
}
</script>