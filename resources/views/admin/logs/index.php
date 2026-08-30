<?php
// Path: resources/views/admin/logs/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);
?>

<style>
    /* 
      Crimson Ruby Theme 
      اللون الأساسي: Crimson/Ruby #e11d48 
    */
    :root {
        --al-primary: #e11d48;
        --al-dark: #be123c;
        --al-light: #fff1f2;
        --al-border: #fecdd3;
        --al-text: #1e293b;
        --al-muted: #64748b;
    }

    .al-wrapper { padding-bottom: 40px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .al-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; flex-wrap: wrap; gap: 16px; }
    .al-title-box { display: flex; align-items: center; gap: 16px; }
    .al-icon { width: 50px; height: 50px; background: var(--al-light); color: var(--al-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(225, 29, 72, 0.15); border: 1px solid var(--al-border); }
    .al-title { margin: 0; color: var(--al-text); font-size: 1.6rem; font-weight: 900; }
    
    .btn-clear { background: #ffffff; color: var(--al-dark) !important; border: 1px solid var(--al-border); padding: 10px 20px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; }
    .btn-clear:hover { background: var(--al-light); border-color: var(--al-primary); }

    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width: 768px){ .stats-grid { grid-template-columns: 1fr; } }
    .stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border-left: 4px solid var(--al-primary); }
    .stat-val { font-size: 1.6rem; font-weight: 900; color: var(--al-text); font-family: monospace; }
    .stat-lbl { font-size: 0.85rem; font-weight: 700; color: var(--al-muted); }

    .search-bar { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; align-items: center; }
    .form-control { border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; outline: none; }
    .form-control:focus { border-color: var(--al-primary); box-shadow: 0 0 0 3px var(--al-light); }
    .btn-search { background: var(--al-dark); color: #ffffff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 8px;}

    .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .al-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: start; }
    .al-table th { padding: 16px 20px; background: #f8fafc; color: var(--al-muted); font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; white-space: nowrap; }
    .al-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: var(--al-text); vertical-align: middle; }
    .al-table tr:hover td { background: var(--al-light); }

    .action-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; color: var(--al-primary); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; transition: 0.2s;}
    .action-btn:hover { background: var(--al-light); border-color: var(--al-border); }

    .badge-act { padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.75rem; font-family: monospace; display: inline-flex; align-items: center; gap: 4px; }

    .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 24px; }
    .page-link { padding: 8px 14px; border: 1px solid #cbd5e1; background: #fff; color: var(--al-text); border-radius: 8px; text-decoration: none; font-weight: 700; transition: 0.2s; }
    .page-link:hover { background: var(--al-light); border-color: var(--al-primary); color: var(--al-primary); }
    .page-link.active { background: var(--al-primary); color: #fff; border-color: var(--al-primary); pointer-events: none; }
</style>

<div class="al-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="al-header">
        <div class="al-title-box">
            <div class="al-icon"><i class="ph-duotone ph-shield-warning"></i></div>
            <div>
                <h2 class="al-title">سجل الأمان والتغيرات (Audit Trail Log)</h2>
                <p style="margin:4px 0 0 0; color:var(--al-muted);">تتبع كافة حركات المستخدمين والتغييرات على بيانات النظام.</p>
            </div>
        </div>
        <form action="/ERP/admin/logs/clear" method="POST" onsubmit="return confirm('هل تريد تنظيف ومسح سجلات التتبع القديمة (أكثر من 90 يوماً)؟');">
            <button type="submit" class="btn-clear"><i class="ph-bold ph-broom"></i> تنظيف السجلات القديمة</button>
        </form>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <!-- KPIs -->
    <div class="stats-grid">
        <div class="stat-card">
            <div style="background:var(--al-light); color:var(--al-primary); width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="ph-fill ph-shield-check"></i></div>
            <div><div class="stat-val"><?= number_format($stats->total ?? 0) ?></div><div class="stat-lbl">إجمالي الحركات الموثقة</div></div>
        </div>
        <div class="stat-card" style="border-left-color: #0284c7;">
            <div style="background:#e0f2fe; color:#0284c7; width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="ph-fill ph-clock-clockwise"></i></div>
            <div><div class="stat-val"><?= number_format($stats->today ?? 0) ?></div><div class="stat-lbl">حركات اليوم الحالية</div></div>
        </div>
        <div class="stat-card" style="border-left-color: #be123c;">
            <div style="background:#fef2f2; color:#be123c; width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="ph-fill ph-warning-octagon"></i></div>
            <div><div class="stat-val"><?= number_format($stats->critical ?? 0) ?></div><div class="stat-lbl">حذف وحركات حساسة</div></div>
        </div>
    </div>

    <!-- Search & Filters -->
    <form action="/ERP/admin/logs" method="GET" class="search-bar">
        <div style="flex: 2; min-width: 220px;">
            <input type="text" name="search" class="form-control" style="width: 100%;" placeholder="بحث بالمستخدم، الأكشن، النص أو عنوان IP..." value="<?= htmlspecialchars($search ?? '') ?>">
        </div>
        <div style="flex: 1; min-width: 140px;">
            <select name="module" class="form-control" style="width: 100%;">
                <option value="">-- كل الموديولات --</option>
                <option value="Sales" <?= ($moduleFilter === 'Sales') ? 'selected' : '' ?>>المبيعات (Sales)</option>
                <option value="Purchasing" <?= ($moduleFilter === 'Purchasing') ? 'selected' : '' ?>>المشتريات (Purchasing)</option>
                <option value="Inventory" <?= ($moduleFilter === 'Inventory') ? 'selected' : '' ?>>المخازن (Inventory)</option>
                <option value="Accounting" <?= ($moduleFilter === 'Accounting') ? 'selected' : '' ?>>الحسابات (Accounting)</option>
                <option value="HR" <?= ($moduleFilter === 'HR') ? 'selected' : '' ?>>الموارد البشرية (HR)</option>
                <option value="Settings" <?= ($moduleFilter === 'Settings') ? 'selected' : '' ?>>الإعدادات (Settings)</option>
                <option value="Admin" <?= ($moduleFilter === 'Admin') ? 'selected' : '' ?>>الإدارة (Admin)</option>
            </select>
        </div>
        <div style="flex: 1; min-width: 140px;">
            <select name="action_type" class="form-control" style="width: 100%;">
                <option value="">-- كل الأكشنز --</option>
                <option value="CREATE" <?= ($actionFilter === 'CREATE') ? 'selected' : '' ?>>إضافة (CREATE)</option>
                <option value="UPDATE" <?= ($actionFilter === 'UPDATE') ? 'selected' : '' ?>>تعديل (UPDATE)</option>
                <option value="DELETE" <?= ($actionFilter === 'DELETE') ? 'selected' : '' ?>>حذف (DELETE)</option>
                <option value="LOGIN" <?= ($actionFilter === 'LOGIN') ? 'selected' : '' ?>>دخول (LOGIN)</option>
            </select>
        </div>
        <button type="submit" class="btn-search"><i class="ph-bold ph-magnifying-glass"></i> تصفية</button>
        <?php if(!empty($search) || !empty($moduleFilter) || !empty($actionFilter)): ?>
            <a href="/ERP/admin/logs" style="color:var(--al-muted); font-weight:800; font-size:0.85rem; text-decoration:none; margin-right:10px;">إعادة ضبط</a>
        <?php endif; ?>
    </form>

    <!-- Data Table -->
    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="al-table">
                <thead>
                    <tr>
                        <th style="width: 18%;">المستخدم</th>
                        <th style="width: 12%;">الموديول</th>
                        <th style="width: 15%;">نوع الأكشن</th>
                        <th style="width: 30%;">موجز التفاصيل</th>
                        <th style="width: 13%;">عنوان IP</th>
                        <th style="width: 12%;">التاريخ والوقت</th>
                        <th style="width: 5%; text-align: center;">عرض</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--al-muted); font-weight: 700;">لا توجد سجلات حركات مطابقة للبحث.</td></tr>
                    <?php else: foreach ($logs as $l): ?>
                        <tr>
                            <td style="font-weight: 800; color: var(--al-text);"><i class="ph-duotone ph-user-circle" style="color:var(--al-primary);"></i> <?= htmlspecialchars($l->user_name ?: 'النظام / مجهول') ?></td>
                            <td><span style="background:#f1f5f9; color:#475569; padding:3px 8px; border-radius:6px; font-weight:800; font-size:0.75rem;"><?= htmlspecialchars($l->module ?: 'General') ?></span></td>
                            <td>
                                <?php 
                                    $act = strtoupper($l->action ?? 'LOG');
                                    $bg = '#e0f2fe'; $color = '#0284c7';
                                    if(str_contains($act, 'CREATE') || str_contains($act, 'STORE')) { $bg = '#d1fae5'; $color = '#059669'; }
                                    elseif(str_contains($act, 'UPDATE')) { $bg = '#fef3c7'; $color = '#d97706'; }
                                    elseif(str_contains($act, 'DELETE') || str_contains($act, 'CLEAR')) { $bg = '#fee2e2'; $color = '#dc2626'; }
                                ?>
                                <span class="badge-act" style="background:<?= $bg ?>; color:<?= $color ?>;"><?= htmlspecialchars($act) ?></span>
                            </td>
                            <td style="color: var(--al-muted); font-size: 0.85rem;">
                                <?php 
                                    $det = $l->details ?? '';
                                    echo htmlspecialchars(mb_strlen($det) > 50 ? mb_substr($det, 0, 50) . '...' : ($det ?: '---'));
                                ?>
                            </td>
                            <td style="font-family: monospace; font-weight: 700; color: #475569;"><?= htmlspecialchars($l->ip_address ?: '127.0.0.1') ?></td>
                            <td style="font-family: monospace; font-weight: 600; color: var(--al-muted); font-size: 0.8rem;"><?= date('Y-m-d H:i', strtotime($l->created_at ?? 'now')) ?></td>
                            <td style="text-align: center;">
                                <a href="/ERP/admin/logs/<?= $l->id ?>" class="action-btn" title="عرض التفاصيل بالكامل"><i class="ph-bold ph-eye"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination (15 items per page) -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&module=<?= urlencode($moduleFilter) ?>&action_type=<?= urlencode($actionFilter) ?>" 
                   class="page-link <?= ($i === $currentPage) ? 'active' : '' ?>">
                   <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

</div>