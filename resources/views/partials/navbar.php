<?php
// Path: resources/views/partials/navbar.php

if (session_status() === PHP_SESSION_NONE) session_start();

global $app;
$db = $app && $app->has(PDO::class) ? $app->get(PDO::class) : null;

/* =======================================================================
   1. TRANSLATION & LOCALE ENGINE
   ======================================================================= */
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'])) {
    $_SESSION['locale'] = $_GET['lang'];
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (strpos($currentPath, '/ERP') !== 0) $currentPath = '/ERP' . $currentPath;
    
    $queryParams = $_GET; unset($queryParams['lang']);
    header("Location: " . $currentPath . ($queryParams ? '?' . http_build_query($queryParams) : ''));
    exit;
}

$currentLocale = $_SESSION['locale'] ?? 'ar';
$isAr = $currentLocale === 'ar';

/* =======================================================================
   2. UNIFIED ENTITY SWITCHER ENGINE (شجرة الشركات والفروع)
   ======================================================================= */
if (isset($_GET['select_entity']) && $db) {
    $type = $_GET['type'] ?? 'company';
    $id = (int)$_GET['select_entity'];

    try {
        if ($type === 'company') {
            $stmt = $db->prepare("SELECT * FROM sys_companies WHERE id = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$id]);
            $comp = $stmt->fetch(PDO::FETCH_OBJ);
            
            if ($comp) {
                $_SESSION['company_id'] = $comp->id;
                $_SESSION['company_name'] = $comp->name_ar;
                $_SESSION['active_company_name'] = $isAr ? $comp->name_ar : ($comp->name_en ?? $comp->name_ar);
                $_SESSION['branch_id'] = 0; 
                $_SESSION['active_branch_name'] = $isAr ? 'كل الفروع' : 'All Branches';
                
                // تحديث العملة فقط إذا لم يقم المستخدم باختيار عملة يدوياً
                if (empty($_SESSION['manual_currency'])) {
                    $compCurr = !empty($comp->currency) ? $comp->currency : 'EGP';
                    $_SESSION['currency'] = $compCurr;
                    $_SESSION['user_currency'] = $compCurr;
                    $_SESSION['company_currency'] = $compCurr;
                }
            }
        } elseif ($type === 'branch') {
            $compId = (int)($_GET['comp_id'] ?? 0);
            $stmtC = $db->prepare("SELECT * FROM sys_companies WHERE id = ? AND is_active = 1 LIMIT 1");
            $stmtC->execute([$compId]);
            $comp = $stmtC->fetch(PDO::FETCH_OBJ);

            $stmtB = $db->prepare("SELECT * FROM sys_branches WHERE id = ? AND is_active = 1 LIMIT 1");
            $stmtB->execute([$id]);
            $branch = $stmtB->fetch(PDO::FETCH_OBJ);

            if ($comp && $branch) {
                $_SESSION['company_id'] = $compId;
                $_SESSION['company_name'] = $comp->name_ar;
                $_SESSION['active_company_name'] = $isAr ? $comp->name_ar : ($comp->name_en ?? $comp->name_ar);
                $_SESSION['branch_id'] = $branch->id;
                $_SESSION['active_branch_name'] = $isAr ? $branch->name_ar : ($branch->name_en ?? $branch->name_ar);
                
                // تحديث العملة فقط إذا لم يقم المستخدم باختيار عملة يدوياً
                if (empty($_SESSION['manual_currency'])) {
                    $compCurr = !empty($comp->currency) ? $comp->currency : 'EGP';
                    $_SESSION['currency'] = $compCurr;
                    $_SESSION['user_currency'] = $compCurr;
                    $_SESSION['company_currency'] = $compCurr;
                }
            }
        }
    } catch (Throwable $e) {}

    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (strpos($currentPath, '/ERP') !== 0) $currentPath = '/ERP' . $currentPath;
    $queryParams = $_GET; unset($queryParams['select_entity'], $queryParams['type'], $queryParams['comp_id']);
    header("Location: " . $currentPath . ($queryParams ? '?' . http_build_query($queryParams) : ''));
    exit;
}

/* =======================================================================
   3. CURRENCY SWITCHER ENGINE (ميزة التثبيت اليدوي)
   ======================================================================= */
$availableCurrencies = [
    'SAR' => ['symbol' => 'ر.س', 'name_ar' => 'ريال سعودي', 'name_en' => 'Saudi Riyal'],
    'EGP' => ['symbol' => 'ج.م', 'name_ar' => 'جنيه مصري', 'name_en' => 'Egyptian Pound'],
    'USD' => ['symbol' => '$',   'name_ar' => 'دولار أمريكي', 'name_en' => 'US Dollar'],
    'EUR' => ['symbol' => '€',   'name_ar' => 'يورو', 'name_en' => 'Euro'],
];

if (isset($_GET['currency']) && array_key_exists($_GET['currency'], $availableCurrencies)) {
    $selectedCurr = $_GET['currency'];
    
    $_SESSION['currency'] = $selectedCurr;
    $_SESSION['user_currency'] = $selectedCurr;
    
    // تفعيل علم "الاختيار اليدوي" لمنع مسح العملة عند تغيير الشركة
    $_SESSION['manual_currency'] = true; 

    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (strpos($currentPath, '/ERP') !== 0) $currentPath = '/ERP' . $currentPath;
    $queryParams = $_GET; unset($queryParams['currency']);
    header("Location: " . $currentPath . ($queryParams ? '?' . http_build_query($queryParams) : ''));
    exit;
}

$currentCurrency = $_SESSION['currency'] ?? $_SESSION['user_currency'] ?? $_SESSION['company_currency'] ?? 'EGP';

if (!array_key_exists($currentCurrency, $availableCurrencies)) {
    $currentCurrency = 'EGP';
}

/* =======================================================================
   4. DYNAMIC USER & SESSION DATA
   ======================================================================= */
use Core\Auth\AuthManager;
if (class_exists('\Core\Auth\AuthManager') && $db) {
    if (AuthManager::user() === null) AuthManager::init($db);
}
$user = class_exists('\Core\Auth\AuthManager') ? AuthManager::user() : null;

$userName = $user['name'] ?? $user['username'] ?? $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'System Admin';
$userEmail = $user['email'] ?? $_SESSION['user_email'] ?? 'admin@nourtrust.com';
$userRole = $user['role_name'] ?? $_SESSION['user_role'] ?? 'Administrator';

$words = explode(' ', trim($userName));
$initials = mb_substr($words[0], 0, 1, 'UTF-8') . (isset($words[1]) ? mb_substr($words[1], 0, 1, 'UTF-8') : '');
$initials = mb_strtoupper($initials, 'UTF-8');

$nextLocale = $isAr ? 'en' : 'ar';
$nextLocaleLabel = $isAr ? 'English' : 'عربي';

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($currentPath, '/ERP') !== 0) $currentPath = '/ERP' . $currentPath;
$queryParams = $_GET; $queryParams['lang'] = $nextLocale;
$switchLangUrl = $currentPath . '?' . http_build_query($queryParams);

/* =======================================================================
   5. DYNAMIC TREE (COMPANIES & BRANCHES) & NOTIFICATIONS
   ======================================================================= */
$activeCompanyName = $_SESSION['active_company_name'] ?? ($_SESSION['company_name'] ?? 'NOUR TRUST');
$activeBranchName = $_SESSION['active_branch_name'] ?? ($isAr ? 'كل الفروع' : 'All Branches');
$entityTree = [];
$notifications = [];
$unreadNotificationsCount = 0;

if ($db) {
    try {
        $stmtComps = $db->query("SELECT * FROM sys_companies WHERE is_active = 1 ORDER BY id ASC");
        $companies = $stmtComps ? ($stmtComps->fetchAll(PDO::FETCH_OBJ) ?: []) : [];

        foreach ($companies as $cmp) {
            $stmtBr = $db->prepare("SELECT * FROM sys_branches WHERE company_id = ? AND is_active = 1 ORDER BY id ASC");
            $stmtBr->execute([$cmp->id]);
            $cmp->branches = $stmtBr->fetchAll(PDO::FETCH_OBJ) ?: [];
            $entityTree[] = $cmp;
        }

        if (!isset($_SESSION['company_id']) && !empty($entityTree)) {
            $_SESSION['company_id'] = $entityTree[0]->id;
            $_SESSION['company_name'] = $entityTree[0]->name_ar;
            $_SESSION['active_company_name'] = $isAr ? $entityTree[0]->name_ar : ($entityTree[0]->name_en ?? $entityTree[0]->name_ar);
            $_SESSION['branch_id'] = 0;
            $_SESSION['active_branch_name'] = $isAr ? 'كل الفروع' : 'All Branches';
            $activeCompanyName = $_SESSION['active_company_name'];
            $activeBranchName = $_SESSION['active_branch_name'];
        }

        $today = date('Y-m-d');
        $stmtNotif = $db->query("
            (SELECT 'عقد عمل' as type, c.contract_code as code, e.name_ar as title, c.end_date as date, '/ERP/hr/contracts' as link 
             FROM hr_employee_contracts c JOIN hr_employees e ON c.employee_id = e.id 
             WHERE c.status = 'active' AND c.end_date BETWEEN '{$today}' AND DATE_ADD('{$today}', INTERVAL 30 DAY))
            UNION ALL
            (SELECT 'وثيقة' as type, d.document_code as code, e.name_ar as title, d.expiry_date as date, '/ERP/hr/documents' as link 
             FROM hr_documents d JOIN hr_employees e ON d.employee_id = e.id 
             WHERE d.status = 'active' AND d.expiry_date BETWEEN '{$today}' AND DATE_ADD('{$today}', INTERVAL 30 DAY))
            ORDER BY date ASC LIMIT 5
        ");
        $notifications = $stmtNotif ? ($stmtNotif->fetchAll(PDO::FETCH_OBJ) ?: []) : [];
        $unreadNotificationsCount = count($notifications);
    } catch (Throwable $e) { }
}

$t = [
    'ar' => [
        'search' => 'البحث السريع (Ctrl+K)...', 'logout' => 'تسجيل الخروج', 'profile' => 'الملف الشخصي والإعدادات',
        'notifications' => 'التنبيهات الإدارية', 'no_notif' => 'لا توجد تنبيهات عاجلة حالياً', 'view_all_notif' => 'عرض كافة التنبيهات',
        'active_entity' => 'نطاق العمل الحالي', 'switch_entity' => 'الشركات والفروع',
        'currency' => 'العملة', 'current_currency' => 'العملة الحالية', 'all_branches' => 'كل الفروع',
        'reset_currency' => 'إعادة لعملة الشركة'
    ],
    'en' => [
        'search' => 'Quick Search (Ctrl+K)...', 'logout' => 'Logout', 'profile' => 'Profile & Settings',
        'notifications' => 'System Notifications', 'no_notif' => 'No urgent notifications', 'view_all_notif' => 'View All Notifications',
        'active_entity' => 'Active Workspace', 'switch_entity' => 'Companies & Branches',
        'currency' => 'Currency', 'current_currency' => 'Current Currency', 'all_branches' => 'All Branches',
        'reset_currency' => 'Reset to Default'
    ]
][$currentLocale];
?>

<style>
    .nt-navbar { display: flex; justify-content: space-between; align-items: center; padding: 10px 24px; background-color: var(--color-surface, #ffffff); border-bottom: 1px solid var(--color-border, #e2e8f0); width: 100%; box-sizing: border-box; position: sticky; top: 0; z-index: 1040; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .nav-group { display: flex; align-items: center; gap: 12px; }
    .nav-icon-btn { width: 38px; height: 38px; border-radius: 8px; border: 1px solid var(--color-border-hover, #cbd5e1); background: var(--color-surface, #ffffff); color: var(--color-text-main, #0f172a); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; text-decoration: none; position: relative; }
    .nav-icon-btn:hover { background: #f8fafc; border-color: #0284c7; color: #0284c7; }
    .nav-search-box { display: flex; align-items: center; background: var(--color-background, #f8fafc); border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 8px; width: 250px; transition: 0.2s; }
    .nav-search-box:focus-within { background: #ffffff; border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15); }
    .nav-search-input { border: none; background: transparent; outline: none; width: 100%; font-family: inherit; font-size: 0.85rem; color: var(--color-text-main, #0f172a); padding: 0 8px; }
    .nav-search-shortcut { font-size: 0.68rem; font-weight: 800; color: #64748b; background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
    .nav-lang-btn { display: flex; align-items: center; gap: 6px; padding: 0 12px; height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; background: var(--color-surface, #ffffff); color: var(--color-text-main, #0f172a); font-weight: 700; font-size: 0.82rem; text-decoration: none; transition: 0.2s; cursor: pointer; }
    .nav-lang-btn:hover { background: #f8fafc; border-color: #0284c7; color: #0284c7; }
    .nav-divider { width: 1px; height: 24px; background: #e2e8f0; margin: 0 2px; }

    .nav-entity-btn { display: flex; align-items: center; gap: 10px; padding: 4px 12px; height: 42px; border-radius: 8px; cursor: pointer; transition: 0.2s; border: 1px solid #cbd5e1; background: #f8fafc; }
    .nav-entity-btn:hover { border-color: #0284c7; background: #ffffff; }

    .nav-badge { position: absolute; top: -4px; right: -4px; background: #ef4444; color: white; font-size: 0.65rem; font-weight: 800; min-width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border-radius: 50%; border: 2px solid #ffffff; }

    .nt-dropdown-menu { display: none; position: absolute; top: calc(100% + 8px); <?= $isAr ? 'left: 0;' : 'right: 0;' ?> background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); min-width: 280px; padding: 0; z-index: 1050; animation: dropFade 0.2s ease; max-height: 450px; overflow-y: auto;}
    @keyframes dropFade { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
    
    .nt-dropdown-header { padding: 16px 16px 12px; border-bottom: 1px solid #f1f5f9; position: sticky; top: 0; background: #ffffff; z-index: 2; }
    .nt-dropdown-item-comp { display: flex; align-items: center; gap: 8px; padding: 12px 16px; text-decoration: none; border-bottom: 1px solid #e2e8f0; transition: 0.2s; }
    .nt-dropdown-item-comp:hover { background: #f1f5f9; }
    .nt-dropdown-item-br { display: flex; align-items: center; gap: 8px; padding: 10px 16px; padding-inline-start: 40px; text-decoration: none; border-bottom: 1px solid #f8fafc; transition: 0.2s; }
    .nt-dropdown-item-br:hover { background: #f8fafc; color: #059669 !important; }

    @media (max-width: 1200px) { .nav-search-box { width: 180px; } }
    @media (max-width: 992px) {
        .nav-search-box { display: none; }
        .nav-entity-btn .entity-text { display: none; }
    }
    @media (max-width: 768px) {
        .nt-navbar { padding: 10px 12px; }
        .nav-group { gap: 6px; }
        .nav-lang-btn span.lang-text { display: none; }
        .nav-lang-btn { padding: 0; width: 38px; justify-content: center; }
        .nav-divider { display: none; }
        .nt-dropdown-menu { position: fixed; top: 60px; left: 10px !important; right: 10px !important; width: calc(100vw - 20px); min-width: auto; max-height: 80vh; overflow-y: auto; }
    }
</style>

<header class="nt-navbar" dir="<?= $isAr ? 'rtl' : 'ltr' ?>">
    <div class="nav-group">
        <button type="button" class="nav-icon-btn mobile-sidebar-toggle" data-toggle="sidebar">
            <i class="ph-bold ph-list" style="font-size: 1.3rem;"></i>
        </button>
        <form action="/ERP/search" method="GET" style="margin: 0;">
            <div class="nav-search-box">
                <div class="nav-search-shortcut">Ctrl+K</div>
                <input type="text" name="q" id="navSearchInput" placeholder="<?= $t['search'] ?>" class="nav-search-input">
                <i class="ph-bold ph-magnifying-glass" style="font-size: 1rem; color: #94a3b8;"></i>
            </div>
        </form>
    </div>

    <div class="nav-group">
        <!-- القائمة المنسدلة للشركات والفروع -->
        <div style="position: relative;" id="entityDropdownContainer">
            <div class="nav-entity-btn" onclick="toggleNavbarDropdown('entityDropdownMenu')">
                <div style="width: 32px; height: 32px; background: #e0f2fe; color: #0284c7; border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="ph-bold ph-buildings" style="font-size: 1.1rem;"></i>
                </div>
                <div class="entity-text" style="display: flex; flex-direction: column; line-height: 1.2;">
                    <span style="font-size: 0.75rem; font-weight: 800; color: #0f172a; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($activeCompanyName) ?></span>
                    <span style="font-size: 0.65rem; color: #64748b; font-weight: 700; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($activeBranchName) ?></span>
                </div>
                <i class="ph-bold ph-caret-down" style="font-size: 0.75rem; color: #94a3b8; margin-inline-start: 4px;"></i>
            </div>
            
            <div id="entityDropdownMenu" class="nt-dropdown-menu">
                <div class="nt-dropdown-header">
                    <div style="font-size: 0.75rem; color: #64748b; font-weight: 800; text-transform: uppercase;"><?= $t['switch_entity'] ?></div>
                </div>
                
                <?php foreach($entityTree as $cmp): ?>
                    <?php 
                        $isCmpActive = (($_SESSION['company_id']??0) == $cmp->id && empty($_SESSION['branch_id'])); 
                        $cmpParams = $_GET; 
                        $cmpParams['select_entity'] = $cmp->id; 
                        $cmpParams['type'] = 'company'; 
                        unset($cmpParams['comp_id']);
                        $cmpUrl = $currentPath . '?' . http_build_query($cmpParams);
                        $cmpDisplayName = $isAr ? $cmp->name_ar : (!empty($cmp->name_en) ? $cmp->name_en : $cmp->name_ar);
                    ?>
                    <a href="<?= htmlspecialchars($cmpUrl) ?>" class="nt-dropdown-item-comp" style="background: <?= $isCmpActive ? '#eff6ff' : '#f8fafc' ?>; color: <?= $isCmpActive ? '#1d4ed8' : '#0f172a' ?>;">
                        <i class="ph-bold ph-buildings" style="font-size: 1.2rem;"></i>
                        <div style="display: flex; flex-direction: column; flex: 1;">
                            <span style="font-weight: 800; font-size: 0.85rem;"><?= htmlspecialchars($cmpDisplayName) ?></span>
                            <span style="font-size: 0.65rem; color: <?= $isCmpActive ? '#3b82f6' : '#94a3b8' ?>; font-weight: 700;"><?= $t['all_branches'] ?></span>
                        </div>
                        <?php if($isCmpActive): ?><i class="ph-bold ph-check-circle" style="color: #2563eb;"></i><?php endif; ?>
                    </a>
                    
                    <?php foreach($cmp->branches as $br): ?>
                        <?php 
                            $isBrActive = (($_SESSION['company_id']??0) == $cmp->id && ($_SESSION['branch_id']??0) == $br->id); 
                            $brParams = $_GET; 
                            $brParams['select_entity'] = $br->id; 
                            $brParams['type'] = 'branch'; 
                            $brParams['comp_id'] = $cmp->id;
                            $brUrl = $currentPath . '?' . http_build_query($brParams);
                            $brDisplayName = $isAr ? $br->name_ar : (!empty($br->name_en) ? $br->name_en : $br->name_ar);
                        ?>
                        <a href="<?= htmlspecialchars($brUrl) ?>" class="nt-dropdown-item-br" style="background: <?= $isBrActive ? '#ecfdf5' : '#ffffff' ?>; color: <?= $isBrActive ? '#059669' : '#475569' ?>;">
                            <i class="ph-duotone ph-storefront" style="font-size: 1.1rem; color: <?= $isBrActive ? '#10b981' : '#94a3b8' ?>;"></i>
                            <span style="font-weight: <?= $isBrActive ? '800' : '600' ?>; font-size: 0.8rem; flex: 1;"><?= htmlspecialchars($brDisplayName) ?></span>
                            <?php if($isBrActive): ?><i class="ph-bold ph-check" style="color: #059669; font-size: 0.8rem;"></i><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="nav-divider"></div>

        <!-- القائمة المنسدلة لاختيار العملة -->
        <div style="position: relative;" id="currencyDropdownContainer">
            <div class="nav-lang-btn" onclick="toggleNavbarDropdown('currencyDropdownMenu')" title="<?= $t['currency'] ?>">
                <span class="lang-text" style="font-family: monospace; font-weight: 900; color: #1e40af;"><?= htmlspecialchars($currentCurrency) ?></span> 
                <i class="ph-bold ph-coins" style="font-size: 1.1rem; color: #64748b;"></i>
            </div>
            <div id="currencyDropdownMenu" class="nt-dropdown-menu" style="min-width: 220px; padding: 8px;">
                <div class="nt-dropdown-header" style="padding: 4px 8px 12px; position: static;">
                    <div style="font-size: 0.75rem; color: #64748b; font-weight: 800; text-transform: uppercase;"><?= $t['current_currency'] ?></div>
                    <div style="font-weight: 900; color: #0f172a; font-size: 0.95rem; margin-top: 2px; display: flex; justify-content: space-between; align-items: center;">
                        <span><?= htmlspecialchars($availableCurrencies[$currentCurrency]['name_'.$currentLocale]) ?> (<?= $currentCurrency ?>)</span>
                    </div>
                </div>
                
                <?php foreach($availableCurrencies as $code => $cData): ?>
                    <?php 
                        $currParams = $_GET; 
                        $currParams['currency'] = $code; 
                        $currUrl = $currentPath . '?' . http_build_query($currParams);
                        $isCurrActive = ($code === $currentCurrency);
                    ?>
                    <a href="<?= htmlspecialchars($currUrl) ?>" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 0.85rem; background: <?= $isCurrActive ? '#eff6ff' : 'transparent' ?>; color: <?= $isCurrActive ? '#1d4ed8' : '#1e293b' ?>;">
                        <div style="width: 28px; text-align: center; font-weight: 900; color: <?= $isCurrActive ? '#1d4ed8' : '#64748b' ?>;"><?= $cData['symbol'] ?></div>
                        <span style="flex: 1;"><?= htmlspecialchars($cData['name_'.$currentLocale]) ?> <small style="color:#94a3b8;">(<?= $code ?>)</small></span>
                        <?php if($isCurrActive): ?><i class="ph-bold ph-check" style="color: #1d4ed8; font-size: 0.85rem;"></i><?php endif; ?>
                    </a>
                <?php endforeach; ?>
                
                <?php if(!empty($_SESSION['manual_currency'])): ?>
                    <div style="border-top: 1px solid #e2e8f0; margin-top: 6px; padding-top: 6px;">
                        <a href="?currency=reset" style="display: block; text-align: center; color: #dc2626; font-size: 0.8rem; font-weight: 700; text-decoration: none; padding: 6px;"><i class="ph-bold ph-arrow-counter-clockwise"></i> <?= $t['reset_currency'] ?></a>
                    </div>
                    <?php 
                        if(isset($_GET['currency']) && $_GET['currency'] === 'reset') {
                            unset($_SESSION['manual_currency']);
                            $_SESSION['currency'] = $_SESSION['company_currency'] ?? 'EGP';
                            $_SESSION['user_currency'] = $_SESSION['currency'];
                            $qParams = $_GET; unset($qParams['currency']);
                            header("Location: " . $currentPath . ($qParams ? '?' . http_build_query($qParams) : ''));
                            exit;
                        }
                    ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- قائمة التنبيهات الإدارية -->
        <div class="nav-notify-wrap" style="position: relative;" id="notifDropdownContainer">
            <button class="nav-icon-btn" onclick="toggleNavbarDropdown('notifDropdownMenu')" title="<?= $t['notifications'] ?>">
                <i class="ph-bold ph-bell" style="font-size: 1.2rem;"></i>
                <?php if($unreadNotificationsCount > 0): ?><span class="nav-badge"><?= $unreadNotificationsCount ?></span><?php endif; ?>
            </button>
            <div id="notifDropdownMenu" class="nt-dropdown-menu" style="padding: 8px;">
                <div class="nt-dropdown-header" style="display: flex; justify-content: space-between; align-items: center; padding: 4px 8px 12px; position: static;">
                    <span style="font-weight: 900; color: #0f172a; font-size: 0.95rem;"><?= $t['notifications'] ?></span>
                    <?php if($unreadNotificationsCount > 0): ?><span style="background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 99px; font-size: 0.7rem; font-weight: 800;"><?= $unreadNotificationsCount ?> عاجل</span><?php endif; ?>
                </div>
                <div style="max-height: 280px; overflow-y: auto;">
                    <?php if(empty($notifications)): ?>
                        <div style="padding: 24px; text-align: center; color: #94a3b8; font-size: 0.85rem; font-weight: 700;"><i class="ph-duotone ph-check-circle" style="font-size: 2rem; color: #10b981; display: block; margin-bottom: 6px;"></i><?= $t['no_notif'] ?></div>
                    <?php else: foreach($notifications as $notif): ?>
                        <a href="<?= $notif->link ?>" style="display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; text-decoration: none; border-bottom: 1px solid #f8fafc; border-radius: 8px;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;"><i class="ph-bold ph-warning-diamond" style="font-size: 1rem;"></i></div>
                            <div style="flex: 1; overflow: hidden;"><div style="font-weight: 800; font-size: 0.82rem; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($notif->title) ?></div><div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">استحقاق <?= htmlspecialchars($notif->type) ?> بتاريخ <span style="font-family: monospace; font-weight: bold; color: #dc2626;"><?= htmlspecialchars($notif->date) ?></span></div></div>
                        </a>
                    <?php endforeach; endif; ?>
                </div>
                <a href="/ERP/workspace/approvals" style="display: block; text-align: center; padding: 10px; margin-top: 8px; background: #f8fafc; border-radius: 8px; color: #0284c7; font-weight: 800; font-size: 0.8rem; text-decoration: none;"><?= $t['view_all_notif'] ?> <i class="ph-bold <?= $isAr ? 'ph-arrow-left' : 'ph-arrow-right' ?>"></i></a>
            </div>
        </div>

        <!-- زر تغيير اللغة -->
        <a href="<?= htmlspecialchars($switchLangUrl) ?>" class="nav-lang-btn" title="تغيير اللغة / Change Language">
            <span class="lang-text"><?= $nextLocaleLabel ?></span> <i class="ph-bold ph-translate" style="font-size: 1.1rem; color: #64748b;"></i>
        </a>

        <!-- قائمة الملف الشخصي -->
        <div style="position: relative;" id="profileDropdownContainer">
            <div onclick="toggleNavbarDropdown('profileDropdownMenu')" style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 2px;">
                <div style="width: 38px; height: 38px; background: linear-gradient(135deg, #0284c7, #0369a1); color: #ffffff; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-weight: 900; font-size: 0.95rem; box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25);"><?= htmlspecialchars($initials) ?></div>
            </div>
            <div id="profileDropdownMenu" class="nt-dropdown-menu" style="padding: 8px;">
                <div class="nt-dropdown-header" style="padding: 4px 8px 12px; position: static;">
                    <div style="font-weight: 900; color: #0f172a; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($userName) ?></div>
                    <div style="font-size: 0.75rem; color: #64748b; font-family: monospace; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($userEmail) ?></div>
                    <div style="margin-top: 6px;"><span style="background: #e0f2fe; color: #0284c7; padding: 2px 8px; border-radius: 4px; font-weight: 800; font-size: 0.7rem; border: 1px solid #bae6fd;"><?= htmlspecialchars($userRole) ?></span></div>
                </div>
                <a href="/ERP/settings/users" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 0.85rem; color: #1e293b;"><i class="ph-duotone ph-user-circle" style="font-size: 1.2rem; color: #0284c7;"></i><span><?= $t['profile'] ?></span></a>
                <a href="/ERP/admin/settings" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 0.85rem; color: #1e293b;"><i class="ph-duotone ph-gear" style="font-size: 1.2rem; color: #64748b;"></i><span><?= $isAr ? 'إعدادات النظام العامة' : 'Global Settings' ?></span></a>
                <div style="height: 1px; background: #f1f5f9; margin: 6px 0;"></div>
                <a href="/ERP/logout" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 0.85rem; color: #dc2626;"><i class="ph-duotone ph-sign-out" style="font-size: 1.2rem;"></i><span><?= $t['logout'] ?></span></a>
            </div>
        </div>
    </div>
</header>

<script>
function toggleNavbarDropdown(menuId) {
    const targetMenu = document.getElementById(menuId);
    if (!targetMenu) return;
    const isVisible = targetMenu.style.display === 'block';
    document.querySelectorAll('.nt-dropdown-menu').forEach(menu => menu.style.display = 'none');
    if (!isVisible) targetMenu.style.display = 'block';
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('#entityDropdownContainer') && 
        !e.target.closest('#currencyDropdownContainer') && 
        !e.target.closest('#notifDropdownContainer') && 
        !e.target.closest('#profileDropdownContainer')) {
        document.querySelectorAll('.nt-dropdown-menu').forEach(menu => menu.style.display = 'none');
    }
});

document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        const searchInput = document.getElementById('navSearchInput');
        if (searchInput) { searchInput.focus(); searchInput.select(); }
    }
});
</script>