<?php
// Path: routes/web.php

use Core\Routing\Router;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use Core\Security\Auth;

/** @var Router $router */
global $basePath;

// ==========================================
// UNDER CONSTRUCTION HELPER
// ==========================================
$renderUnderConstruction = function(string $title, string $icon = 'ph-wrench') use ($basePath) {
    $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar'; 
    $pageTitle = __($title, $title);
    
    ob_start();
    echo "<div style='padding: 80px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; font-family: " . ($isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif") . ";' dir='" . ($isRtl ? "rtl" : "ltr") . "'>";
    echo "<div style='display: flex; align-items: center; justify-content: center; width: 100px; height: 100px; background: #eff6ff; border-radius: 50%; margin-bottom: 24px; color: #2563eb; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.1);'>";
    echo "<i class='ph-duotone {$icon}' style='font-size: 3.5rem;'></i></div>";
    echo "<h2 style='color: #0f172a; font-size: 1.8rem; margin-bottom: 12px; font-weight: 800;'>{$pageTitle}</h2>";
    echo "<p style='color: #64748b; font-size: 1rem; max-width: 500px; margin: 0 auto; line-height: 1.6;'>" . 
         __('هذه الشاشة قيد التطوير والبرمجة حالياً وسيتم تفعيلها قريباً.', 'This module is currently under active development and will be available soon.') . 
         "</p>";
    echo "<a href='/ERP/dashboard' class='erp-btn' style='margin-top: 32px; background: #2563eb; color: white; padding: 10px 24px; border-radius: 8px; text-decoration: none; font-weight: 800;'><i class='ph-bold ph-arrow-" . ($isRtl ? "right" : "left") . "'></i> " . __('العودة للوحة القيادة الرئيسية', 'Back to Dashboard') . "</a>";
    echo "</div>";
    $content = ob_get_clean();

    ob_start(); include $basePath . '/resources/views/layouts/app.php';
    return (new Response())->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');
};

// ==========================================
// CORE & AUTH ROUTES
// ==========================================
$router->get('/login', function (Request $request, Response $response) use ($basePath) { 
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    if (isset($_SESSION['user_id'])) {
        header("Location: /ERP/dashboard");
        exit;
    }

    // تنظيف البافر وإجبار السيرفر على إرسال HTML
    while (ob_get_level()) { ob_end_clean(); }
    header_remove('Content-Type');
    header('Content-Type: text/html; charset=UTF-8');

    // استدعاء ملفات التصميم اللي صلحناها
    ob_start(); 
    include $basePath . '/resources/views/auth/login.php'; 
    $content = ob_get_clean(); 

    include $basePath . '/resources/views/layouts/auth.php'; 
    
    exit; 
});
$router->post('/login', function (Request $request, Response $response) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    global $app;
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $db = $app ? $app->get(\PDO::class) : null;
    if (!$db) {
        die('Database Connection Error');
    }

    // جلب بيانات المستخدم
    $stmt = $db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active' LIMIT 1");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(\PDO::FETCH_OBJ);

    if ($user && password_verify($password, $user->password_hash)) {
        // --- 1. البيانات الأساسية للـ Auth والـ Navbar ---
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['user_name'] = $user->name ?? $user->username;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['locale'] = $user->language ?? 'ar';

        // --- 2. بيانات الشركة والفروع والعملة ---
        $_SESSION['company_id'] = $user->company_id ?? 1;
        $_SESSION['branch_id'] = $user->branch_id ?? null;
        
        if (!empty($_SESSION['company_id'])) {
            try {
                $compStmt = $db->prepare("SELECT * FROM sys_companies WHERE id = ? LIMIT 1");
                $compStmt->execute([$_SESSION['company_id']]);
                $comp = $compStmt->fetch(\PDO::FETCH_OBJ);
                if ($comp) {
                    $_SESSION['company_name'] = $comp->name_ar;
                    $_SESSION['company_name_ar'] = $comp->name_ar;
                    $_SESSION['company_name_en'] = $comp->name_en ?? $comp->name_ar;
                    $_SESSION['company_currency'] = !empty($comp->currency) ? $comp->currency : 'EGP';
                }
            } catch (\Throwable $e) {}
        }
        
        // --- 3. بيانات الرول (الصلاحيات) ---
        $_SESSION['role_id'] = $user->role_id;
        $_SESSION['user_role_id'] = $user->role_id;
        
        $_SESSION['permissions'] = [];
        $_SESSION['user_permissions'] = [];

        if ($user->role_id) {
            $roleStmt = $db->prepare("SELECT name FROM roles WHERE id = ?");
            $roleStmt->execute([$user->role_id]);
            $_SESSION['user_role'] = $roleStmt->fetchColumn() ?: 'Administrator';

            $permStmt = $db->prepare("SELECT permission_key FROM role_permissions WHERE role_id = ?");
            $permStmt->execute([$user->role_id]);
            $perms = $permStmt->fetchAll(\PDO::FETCH_COLUMN);
            
            $_SESSION['permissions'] = $perms;
            $_SESSION['user_permissions'] = $perms;
        }

        if ($user->role_id == 1) {
            $_SESSION['is_super_admin'] = true;
        }

        return new RedirectResponse('/ERP/dashboard');
    }

    $_SESSION['flash_err'] = ($_SESSION['locale'] ?? 'ar') === 'ar' ? 'اسم المستخدم أو كلمة المرور غير صحيحة.' : 'Invalid credentials.';
    return new RedirectResponse('/ERP/login');
});

$router->get('/logout', function () { 
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    
    return new RedirectResponse('/ERP/login'); 
});

$router->get('/', function () { 
    return new RedirectResponse('/ERP/login'); 
});

// ==========================================
// DYNAMIC SWITCHERS (COMPANY, BRANCH, CURRENCY & LANGUAGE)
// ==========================================
$router->get('/admin/companies/{id}/switch', [\App\Modules\Admin\Http\Controllers\CompanyController::class, 'switchCompany']);
$router->get('/lang/{lang}/switch', [\App\Modules\Admin\Http\Controllers\CompanyController::class, 'switchLanguage']);
$router->get('/currency/{code}/switch', [\App\Modules\Admin\Http\Controllers\CompanyController::class, 'switchCurrency']);

// ==========================================
// MAIN DASHBOARDS
// ==========================================
$router->get('/dashboard', [\App\Http\Controllers\MainDashboardController::class, 'index']);
$router->get('/sales/dashboard', [\App\Modules\Sales\Http\Controllers\SalesDashboardController::class, 'index']);
$router->get('/purchasing/dashboard', [\App\Modules\Purchasing\Http\Controllers\PurchasingDashboardController::class, 'index']);
$router->get('/purchasing/dashboard/export', [\App\Modules\Purchasing\Http\Controllers\PurchasingDashboardController::class, 'exportReport']);
$router->get('/accounting/dashboard', [\App\Modules\Accounting\Http\Controllers\AccountingDashboardController::class, 'index']);
$router->get('/treasury/dashboard', [\App\Modules\Treasury\Http\Controllers\TreasuryDashboardController::class, 'index']);
$router->get('/hr/dashboard', [\App\Modules\HR\Http\Controllers\HrDashboardController::class, 'index']);

// =========================================================================
// مساحة العمل والموافقات (Workspace & Approvals)
// =========================================================================
$router->get('/workspace/approvals', [\App\Modules\Workspace\Http\Controllers\ApprovalController::class, 'index']);
$router->post('/workspace/approvals/process', [\App\Modules\Workspace\Http\Controllers\ApprovalController::class, 'process']);

// ==========================================
// CRM & SALES MODULE
// ==========================================
// Leads
$router->get('/crm/leads', [\App\Modules\CRM\Http\Controllers\LeadController::class, 'index']);
$router->get('/crm/leads/create', [\App\Modules\CRM\Http\Controllers\LeadController::class, 'create']);
$router->post('/crm/leads/store', [\App\Modules\CRM\Http\Controllers\LeadController::class, 'store']);
$router->get('/crm/leads/{id}/edit', [\App\Modules\CRM\Http\Controllers\LeadController::class, 'edit']);
$router->post('/crm/leads/{id}/update', [\App\Modules\CRM\Http\Controllers\LeadController::class, 'update']);
$router->post('/crm/leads/{id}/delete', [\App\Modules\CRM\Http\Controllers\LeadController::class, 'delete']);
$router->post('/crm/leads/{id}/convert', [\App\Modules\CRM\Http\Controllers\LeadController::class, 'convert']);

// Customers
$router->get('/sales/customers', [\App\Modules\Sales\Http\Controllers\CustomerController::class, 'index']);
$router->get('/sales/customers/create', [\App\Modules\Sales\Http\Controllers\CustomerController::class, 'create']);
$router->post('/sales/customers/store', [\App\Modules\Sales\Http\Controllers\CustomerController::class, 'store']);
$router->get('/sales/customers/{id}', [\App\Modules\Sales\Http\Controllers\CustomerController::class, 'show']);
$router->get('/sales/customers/{id}/edit', [\App\Modules\Sales\Http\Controllers\CustomerController::class, 'edit']);
$router->post('/sales/customers/{id}/update', [\App\Modules\Sales\Http\Controllers\CustomerController::class, 'update']);
$router->post('/sales/customers/{id}/delete', [\App\Modules\Sales\Http\Controllers\CustomerController::class, 'delete']);

// Quotations
$router->get('/sales/quotations', [\App\Modules\Sales\Http\Controllers\QuotationController::class, 'index']);
$router->get('/sales/quotations/create', [\App\Modules\Sales\Http\Controllers\QuotationController::class, 'create']);
$router->post('/sales/quotations/store', [\App\Modules\Sales\Http\Controllers\QuotationController::class, 'store']);
$router->get('/sales/quotations/{id}', [\App\Modules\Sales\Http\Controllers\QuotationController::class, 'show']);
$router->get('/sales/quotations/{id}/edit', [\App\Modules\Sales\Http\Controllers\QuotationController::class, 'edit']);
$router->post('/sales/quotations/{id}/update', [\App\Modules\Sales\Http\Controllers\QuotationController::class, 'update']);
$router->post('/sales/quotations/{id}/delete', [\App\Modules\Sales\Http\Controllers\QuotationController::class, 'delete']);

// Sales Orders
$router->get('/sales/orders', [\App\Modules\Sales\Http\Controllers\SalesOrderController::class, 'index']);
$router->get('/sales/orders/create', [\App\Modules\Sales\Http\Controllers\SalesOrderController::class, 'create']);
$router->post('/sales/orders/store', [\App\Modules\Sales\Http\Controllers\SalesOrderController::class, 'store']);
$router->get('/sales/orders/{id}', [\App\Modules\Sales\Http\Controllers\SalesOrderController::class, 'show']);
$router->get('/sales/orders/{id}/edit', [\App\Modules\Sales\Http\Controllers\SalesOrderController::class, 'edit']);
$router->post('/sales/orders/{id}/update', [\App\Modules\Sales\Http\Controllers\SalesOrderController::class, 'update']);
$router->post('/sales/orders/{id}/delete', [\App\Modules\Sales\Http\Controllers\SalesOrderController::class, 'delete']);

// Sales Invoices
$router->get('/sales/invoices', [\App\Modules\Sales\Http\Controllers\SalesInvoiceController::class, 'index']);
$router->get('/sales/invoices/create', [\App\Modules\Sales\Http\Controllers\SalesInvoiceController::class, 'create']);
$router->post('/sales/invoices/store', [\App\Modules\Sales\Http\Controllers\SalesInvoiceController::class, 'store']);
$router->get('/sales/invoices/{id}', [\App\Modules\Sales\Http\Controllers\SalesInvoiceController::class, 'show']);
$router->get('/sales/invoices/{id}/edit', [\App\Modules\Sales\Http\Controllers\SalesInvoiceController::class, 'edit']);
$router->post('/sales/invoices/{id}/update', [\App\Modules\Sales\Http\Controllers\SalesInvoiceController::class, 'update']);
$router->post('/sales/invoices/{id}/pay', [\App\Modules\Sales\Http\Controllers\SalesInvoiceController::class, 'pay']);
$router->post('/sales/invoices/{id}/delete', [\App\Modules\Sales\Http\Controllers\SalesInvoiceController::class, 'delete']);

// Sales Receipts
$router->get('/sales/receipts', [\App\Modules\Sales\Http\Controllers\SalesReceiptController::class, 'index']);
$router->get('/sales/receipts/create', [\App\Modules\Sales\Http\Controllers\SalesReceiptController::class, 'create']);
$router->post('/sales/receipts/store', [\App\Modules\Sales\Http\Controllers\SalesReceiptController::class, 'store']);
$router->get('/sales/receipts/{id}', [\App\Modules\Sales\Http\Controllers\SalesReceiptController::class, 'show']);
$router->get('/sales/receipts/{id}/edit', [\App\Modules\Sales\Http\Controllers\SalesReceiptController::class, 'edit']);
$router->post('/sales/receipts/{id}/update', [\App\Modules\Sales\Http\Controllers\SalesReceiptController::class, 'update']);
$router->post('/sales/receipts/{id}/delete', [\App\Modules\Sales\Http\Controllers\SalesReceiptController::class, 'delete']);

// Sales Returns
$router->get('/sales/returns', [\App\Modules\Sales\Http\Controllers\SalesReturnController::class, 'index']);
$router->get('/sales/returns/create', [\App\Modules\Sales\Http\Controllers\SalesReturnController::class, 'create']);
$router->post('/sales/returns/store', [\App\Modules\Sales\Http\Controllers\SalesReturnController::class, 'store']);
$router->get('/sales/returns/{id}', [\App\Modules\Sales\Http\Controllers\SalesReturnController::class, 'show']);
$router->get('/sales/returns/{id}/edit', [\App\Modules\Sales\Http\Controllers\SalesReturnController::class, 'edit']);
$router->post('/sales/returns/{id}/update', [\App\Modules\Sales\Http\Controllers\SalesReturnController::class, 'update']);
$router->post('/sales/returns/{id}/delete', [\App\Modules\Sales\Http\Controllers\SalesReturnController::class, 'delete']);

// Price Lists
$router->get('/sales/price-lists', [\App\Modules\Sales\Http\Controllers\PriceListController::class, 'index']);
$router->get('/sales/price-lists/create', [\App\Modules\Sales\Http\Controllers\PriceListController::class, 'create']);
$router->post('/sales/price-lists/store', [\App\Modules\Sales\Http\Controllers\PriceListController::class, 'store']);
$router->get('/sales/price-lists/{id}', [\App\Modules\Sales\Http\Controllers\PriceListController::class, 'show']);
$router->get('/sales/price-lists/{id}/edit', [\App\Modules\Sales\Http\Controllers\PriceListController::class, 'edit']);
$router->post('/sales/price-lists/{id}/update', [\App\Modules\Sales\Http\Controllers\PriceListController::class, 'update']);
$router->post('/sales/price-lists/{id}/delete', [\App\Modules\Sales\Http\Controllers\PriceListController::class, 'delete']);

// Sales Representatives
$router->get('/sales/representatives', [\App\Modules\Sales\Http\Controllers\SalesRepresentativeController::class, 'index']);
$router->get('/sales/representatives/create', [\App\Modules\Sales\Http\Controllers\SalesRepresentativeController::class, 'create']);
$router->post('/sales/representatives/store', [\App\Modules\Sales\Http\Controllers\SalesRepresentativeController::class, 'store']);
$router->get('/sales/representatives/{id}', [\App\Modules\Sales\Http\Controllers\SalesRepresentativeController::class, 'show']);
$router->get('/sales/representatives/{id}/edit', [\App\Modules\Sales\Http\Controllers\SalesRepresentativeController::class, 'edit']);
$router->post('/sales/representatives/{id}/update', [\App\Modules\Sales\Http\Controllers\SalesRepresentativeController::class, 'update']);
$router->post('/sales/representatives/{id}/delete', [\App\Modules\Sales\Http\Controllers\SalesRepresentativeController::class, 'delete']);

// Customer Statements
$router->get('/sales/statements', [\App\Modules\Sales\Http\Controllers\CustomerStatementController::class, 'index']);
$router->get('/sales/statements/{customerId}', [\App\Modules\Sales\Http\Controllers\CustomerStatementController::class, 'show']);

// Sales Contracts
$router->get('/sales/contracts', [\App\Modules\Sales\Http\Controllers\SalesContractController::class, 'index']);
$router->get('/sales/contracts/create', [\App\Modules\Sales\Http\Controllers\SalesContractController::class, 'create']);
$router->post('/sales/contracts/store', [\App\Modules\Sales\Http\Controllers\SalesContractController::class, 'store']);
$router->get('/sales/contracts/{id}', [\App\Modules\Sales\Http\Controllers\SalesContractController::class, 'show']);
$router->get('/sales/contracts/{id}/edit', [\App\Modules\Sales\Http\Controllers\SalesContractController::class, 'edit']);
$router->post('/sales/contracts/{id}/update', [\App\Modules\Sales\Http\Controllers\SalesContractController::class, 'update']);
$router->post('/sales/contracts/{id}/delete', [\App\Modules\Sales\Http\Controllers\SalesContractController::class, 'delete']);

// ==========================================
// PURCHASING MODULE
// ==========================================
// Suppliers
$router->get('/purchasing/suppliers', [\App\Modules\Purchasing\Http\Controllers\SupplierController::class, 'index']);
$router->get('/purchasing/suppliers/create', [\App\Modules\Purchasing\Http\Controllers\SupplierController::class, 'create']);
$router->post('/purchasing/suppliers/store', [\App\Modules\Purchasing\Http\Controllers\SupplierController::class, 'store']);
$router->get('/purchasing/suppliers/{id}/edit', [\App\Modules\Purchasing\Http\Controllers\SupplierController::class, 'edit']);
$router->post('/purchasing/suppliers/{id}/update', [\App\Modules\Purchasing\Http\Controllers\SupplierController::class, 'update']);
$router->post('/purchasing/suppliers/{id}/delete', [\App\Modules\Purchasing\Http\Controllers\SupplierController::class, 'delete']);
$router->get('/purchasing/suppliers/{id}', [\App\Modules\Purchasing\Http\Controllers\SupplierController::class, 'show']);

// Supplier Evaluations
$router->get('/purchasing/supplier-evaluations', [\App\Modules\Purchasing\Http\Controllers\SupplierEvaluationController::class, 'index']);
$router->get('/purchasing/supplier-evaluations/create', [\App\Modules\Purchasing\Http\Controllers\SupplierEvaluationController::class, 'create']);
$router->post('/purchasing/supplier-evaluations/store', [\App\Modules\Purchasing\Http\Controllers\SupplierEvaluationController::class, 'store']);
$router->get('/purchasing/supplier-evaluations/{id}', [\App\Modules\Purchasing\Http\Controllers\SupplierEvaluationController::class, 'show']);
$router->get('/purchasing/supplier-evaluations/{id}/edit', [\App\Modules\Purchasing\Http\Controllers\SupplierEvaluationController::class, 'edit']);
$router->post('/purchasing/supplier-evaluations/{id}/update', [\App\Modules\Purchasing\Http\Controllers\SupplierEvaluationController::class, 'update']);
$router->post('/purchasing/supplier-evaluations/{id}/delete', [\App\Modules\Purchasing\Http\Controllers\SupplierEvaluationController::class, 'delete']);

// Supplier Catalogs & Price Lists
$router->get('/purchasing/price-lists', [\App\Modules\Purchasing\Http\Controllers\SupplierPriceListController::class, 'index']);
$router->get('/purchasing/price-lists/create', [\App\Modules\Purchasing\Http\Controllers\SupplierPriceListController::class, 'create']);
$router->post('/purchasing/price-lists/store', [\App\Modules\Purchasing\Http\Controllers\SupplierPriceListController::class, 'store']);
$router->get('/purchasing/price-lists/{id}', [\App\Modules\Purchasing\Http\Controllers\SupplierPriceListController::class, 'show']);
$router->get('/purchasing/price-lists/{id}/edit', [\App\Modules\Purchasing\Http\Controllers\SupplierPriceListController::class, 'edit']);
$router->post('/purchasing/price-lists/{id}/update', [\App\Modules\Purchasing\Http\Controllers\SupplierPriceListController::class, 'update']);
$router->post('/purchasing/price-lists/{id}/delete', [\App\Modules\Purchasing\Http\Controllers\SupplierPriceListController::class, 'delete']);

// Purchase Contracts
$router->get('/purchasing/contracts', [\App\Modules\Purchasing\Http\Controllers\PurchaseContractController::class, 'index']);
$router->get('/purchasing/contracts/create', [\App\Modules\Purchasing\Http\Controllers\PurchaseContractController::class, 'create']);
$router->post('/purchasing/contracts/store', [\App\Modules\Purchasing\Http\Controllers\PurchaseContractController::class, 'store']);
$router->get('/purchasing/contracts/{id}', [\App\Modules\Purchasing\Http\Controllers\PurchaseContractController::class, 'show']);
$router->get('/purchasing/contracts/{id}/edit', [\App\Modules\Purchasing\Http\Controllers\PurchaseContractController::class, 'edit']);
$router->post('/purchasing/contracts/{id}/update', [\App\Modules\Purchasing\Http\Controllers\PurchaseContractController::class, 'update']);
$router->post('/purchasing/contracts/{id}/delete', [\App\Modules\Purchasing\Http\Controllers\PurchaseContractController::class, 'delete']);

// Purchase Requests 
$router->get('/purchasing/requisitions', [\App\Modules\Purchasing\Http\Controllers\PurchaseRequestController::class, 'index']);
$router->get('/purchasing/requisitions/create', [\App\Modules\Purchasing\Http\Controllers\PurchaseRequestController::class, 'create']);
$router->post('/purchasing/requisitions/store', [\App\Modules\Purchasing\Http\Controllers\PurchaseRequestController::class, 'store']);
$router->get('/purchasing/requisitions/{id}', [\App\Modules\Purchasing\Http\Controllers\PurchaseRequestController::class, 'show']);
$router->get('/purchasing/requisitions/{id}/edit', [\App\Modules\Purchasing\Http\Controllers\PurchaseRequestController::class, 'edit']);
$router->post('/purchasing/requisitions/{id}/update', [\App\Modules\Purchasing\Http\Controllers\PurchaseRequestController::class, 'update']);
$router->post('/purchasing/requisitions/{id}/delete', [\App\Modules\Purchasing\Http\Controllers\PurchaseRequestController::class, 'delete']);

// RFQs
$router->get('/purchasing/rfq', [\App\Modules\Purchasing\Http\Controllers\RfqController::class, 'index']);
$router->get('/purchasing/rfq/create', [\App\Modules\Purchasing\Http\Controllers\RfqController::class, 'create']);
$router->post('/purchasing/rfq/store', [\App\Modules\Purchasing\Http\Controllers\RfqController::class, 'store']);
$router->get('/purchasing/rfq/{id}', [\App\Modules\Purchasing\Http\Controllers\RfqController::class, 'show']);
$router->get('/purchasing/rfq/{id}/edit', [\App\Modules\Purchasing\Http\Controllers\RfqController::class, 'edit']);
$router->post('/purchasing/rfq/{id}/update', [\App\Modules\Purchasing\Http\Controllers\RfqController::class, 'update']);
$router->post('/purchasing/rfq/{id}/delete', [\App\Modules\Purchasing\Http\Controllers\RfqController::class, 'delete']);

// Goods Receipts
$router->get('/purchasing/receipts', [\App\Modules\Purchasing\Http\Controllers\GoodsReceiptController::class, 'index']);
$router->get('/purchasing/receipts/create', [\App\Modules\Purchasing\Http\Controllers\GoodsReceiptController::class, 'create']);
$router->post('/purchasing/receipts/store', [\App\Modules\Purchasing\Http\Controllers\GoodsReceiptController::class, 'store']);
$router->get('/purchasing/receipts/{id}/edit', [\App\Modules\Purchasing\Http\Controllers\GoodsReceiptController::class, 'edit']);
$router->post('/purchasing/receipts/{id}/update', [\App\Modules\Purchasing\Http\Controllers\GoodsReceiptController::class, 'update']);
$router->post('/purchasing/receipts/{id}/delete', [\App\Modules\Purchasing\Http\Controllers\GoodsReceiptController::class, 'delete']);
$router->get('/purchasing/receipts/{id}', [\App\Modules\Purchasing\Http\Controllers\GoodsReceiptController::class, 'show']);

// Purchase Orders
$router->get('/purchasing/orders', [\App\Modules\Purchasing\Http\Controllers\PurchaseOrderController::class, 'index']);
$router->get('/purchasing/orders/create', [\App\Modules\Purchasing\Http\Controllers\PurchaseOrderController::class, 'create']);
$router->post('/purchasing/orders/store', [\App\Modules\Purchasing\Http\Controllers\PurchaseOrderController::class, 'store']);
$router->get('/purchasing/orders/{id}/edit', [\App\Modules\Purchasing\Http\Controllers\PurchaseOrderController::class, 'edit']);
$router->post('/purchasing/orders/{id}/update', [\App\Modules\Purchasing\Http\Controllers\PurchaseOrderController::class, 'update']);
$router->post('/purchasing/orders/{id}/delete', [\App\Modules\Purchasing\Http\Controllers\PurchaseOrderController::class, 'delete']);
$router->get('/purchasing/orders/{id}', [\App\Modules\Purchasing\Http\Controllers\PurchaseOrderController::class, 'show']);

// Landed Costs
$router->get('/purchasing/landed-costs', [\App\Modules\Purchasing\Http\Controllers\LandedCostController::class, 'index']);
$router->get('/purchasing/landed-costs/create', [\App\Modules\Purchasing\Http\Controllers\LandedCostController::class, 'create']);
$router->post('/purchasing/landed-costs/store', [\App\Modules\Purchasing\Http\Controllers\LandedCostController::class, 'store']);
$router->get('/purchasing/landed-costs/{id}/edit', [\App\Modules\Purchasing\Http\Controllers\LandedCostController::class, 'edit']);
$router->post('/purchasing/landed-costs/{id}/update', [\App\Modules\Purchasing\Http\Controllers\LandedCostController::class, 'update']);
$router->post('/purchasing/landed-costs/{id}/delete', [\App\Modules\Purchasing\Http\Controllers\LandedCostController::class, 'delete']);
$router->get('/purchasing/landed-costs/{id}', [\App\Modules\Purchasing\Http\Controllers\LandedCostController::class, 'show']);

// Purchase Invoices
$router->get('/purchasing/invoices', [\App\Modules\Purchasing\Http\Controllers\PurchaseInvoiceController::class, 'index']);
$router->get('/purchasing/invoices/create', [\App\Modules\Purchasing\Http\Controllers\PurchaseInvoiceController::class, 'create']);
$router->post('/purchasing/invoices/store', [\App\Modules\Purchasing\Http\Controllers\PurchaseInvoiceController::class, 'store']);
$router->get('/purchasing/invoices/{id}/edit', [\App\Modules\Purchasing\Http\Controllers\PurchaseInvoiceController::class, 'edit']);
$router->post('/purchasing/invoices/{id}/update', [\App\Modules\Purchasing\Http\Controllers\PurchaseInvoiceController::class, 'update']);
$router->post('/purchasing/invoices/{id}/delete', [\App\Modules\Purchasing\Http\Controllers\PurchaseInvoiceController::class, 'delete']);
$router->get('/purchasing/invoices/{id}', [\App\Modules\Purchasing\Http\Controllers\PurchaseInvoiceController::class, 'show']);

// Purchase Returns
$router->get('/purchasing/returns', [\App\Modules\Purchasing\Http\Controllers\PurchaseReturnController::class, 'index']);
$router->get('/purchasing/returns/create', [\App\Modules\Purchasing\Http\Controllers\PurchaseReturnController::class, 'create']);
$router->post('/purchasing/returns/store', [\App\Modules\Purchasing\Http\Controllers\PurchaseReturnController::class, 'store']);
$router->get('/purchasing/returns/{id}/edit', [\App\Modules\Purchasing\Http\Controllers\PurchaseReturnController::class, 'edit']);
$router->post('/purchasing/returns/{id}/update', [\App\Modules\Purchasing\Http\Controllers\PurchaseReturnController::class, 'update']);
$router->post('/purchasing/returns/{id}/delete', [\App\Modules\Purchasing\Http\Controllers\PurchaseReturnController::class, 'delete']);
$router->get('/purchasing/returns/{id}', [\App\Modules\Purchasing\Http\Controllers\PurchaseReturnController::class, 'show']);

// Supplier Statements
$router->get('/purchasing/statements', [\App\Modules\Purchasing\Http\Controllers\SupplierStatementController::class, 'index']);
$router->get('/purchasing/statements/{id}', [\App\Modules\Purchasing\Http\Controllers\SupplierStatementController::class, 'show']);

// ==========================================
// INVENTORY MODULE
// ==========================================
$router->get('/inventory/dashboard', [\App\Modules\Inventory\Http\Controllers\InventoryDashboardController::class, 'index']);

// Products
$router->get('/inventory/products', [\App\Modules\Inventory\Products\Http\Controllers\ProductController::class, 'index']);
$router->get('/inventory/products/create', [\App\Modules\Inventory\Products\Http\Controllers\ProductController::class, 'create']);
$router->post('/inventory/products/store', [\App\Modules\Inventory\Products\Http\Controllers\ProductController::class, 'store']);
$router->get('/inventory/products/{id}', [\App\Modules\Inventory\Products\Http\Controllers\ProductController::class, 'show']);
$router->get('/inventory/products/{id}/edit', [\App\Modules\Inventory\Products\Http\Controllers\ProductController::class, 'edit']);
$router->post('/inventory/products/{id}/update', [\App\Modules\Inventory\Products\Http\Controllers\ProductController::class, 'update']);
$router->post('/inventory/products/{id}/delete', [\App\Modules\Inventory\Products\Http\Controllers\ProductController::class, 'delete']);

// Categories
$router->get('/inventory/categories', [\App\Modules\Inventory\Categories\Http\Controllers\CategoryController::class, 'index']);
$router->get('/inventory/categories/create', [\App\Modules\Inventory\Categories\Http\Controllers\CategoryController::class, 'create']);
$router->post('/inventory/categories/store', [\App\Modules\Inventory\Categories\Http\Controllers\CategoryController::class, 'store']);
$router->get('/inventory/categories/{id}/edit', [\App\Modules\Inventory\Categories\Http\Controllers\CategoryController::class, 'edit']);
$router->post('/inventory/categories/{id}/update', [\App\Modules\Inventory\Categories\Http\Controllers\CategoryController::class, 'update']);
$router->post('/inventory/categories/{id}/delete', [\App\Modules\Inventory\Categories\Http\Controllers\CategoryController::class, 'delete']);

// Warehouses
$router->get('/inventory/warehouses', [\App\Modules\Inventory\Warehouses\Http\Controllers\WarehouseController::class, 'index']);
$router->get('/inventory/warehouses/create', [\App\Modules\Inventory\Warehouses\Http\Controllers\WarehouseController::class, 'create']);
$router->post('/inventory/warehouses/store', [\App\Modules\Inventory\Warehouses\Http\Controllers\WarehouseController::class, 'store']);
$router->get('/inventory/warehouses/{id}/edit', [\App\Modules\Inventory\Warehouses\Http\Controllers\WarehouseController::class, 'edit']);
$router->post('/inventory/warehouses/{id}/update', [\App\Modules\Inventory\Warehouses\Http\Controllers\WarehouseController::class, 'update']);
$router->post('/inventory/warehouses/{id}/delete', [\App\Modules\Inventory\Warehouses\Http\Controllers\WarehouseController::class, 'delete']);

// Stock Transfers
$router->get('/inventory/stock/transfers', [\App\Modules\Inventory\Http\Controllers\TransferController::class, 'index']);
$router->get('/inventory/stock/transfers/create', [\App\Modules\Inventory\Http\Controllers\TransferController::class, 'create']);
$router->post('/inventory/stock/transfers/store', [\App\Modules\Inventory\Http\Controllers\TransferController::class, 'store']);
$router->get('/inventory/stock/transfers/{id}/edit', [\App\Modules\Inventory\Http\Controllers\TransferController::class, 'edit']);
$router->post('/inventory/stock/transfers/{id}/update', [\App\Modules\Inventory\Http\Controllers\TransferController::class, 'update']);
$router->post('/inventory/stock/transfers/{id}/delete', [\App\Modules\Inventory\Http\Controllers\TransferController::class, 'delete']);
$router->get('/inventory/stock/transfers/{id}', [\App\Modules\Inventory\Http\Controllers\TransferController::class, 'show']);

// Delivery Notes
$router->get('/inventory/delivery-notes', [\App\Modules\Inventory\Http\Controllers\DeliveryNoteController::class, 'index']);
$router->get('/inventory/delivery-notes/create', [\App\Modules\Inventory\Http\Controllers\DeliveryNoteController::class, 'create']);
$router->post('/inventory/delivery-notes/store', [\App\Modules\Inventory\Http\Controllers\DeliveryNoteController::class, 'store']);
$router->get('/inventory/delivery-notes/{id}/edit', [\App\Modules\Inventory\Http\Controllers\DeliveryNoteController::class, 'edit']);
$router->post('/inventory/delivery-notes/{id}/update', [\App\Modules\Inventory\Http\Controllers\DeliveryNoteController::class, 'update']);
$router->post('/inventory/delivery-notes/{id}/delete', [\App\Modules\Inventory\Http\Controllers\DeliveryNoteController::class, 'delete']);
$router->get('/inventory/delivery-notes/{id}', [\App\Modules\Inventory\Http\Controllers\DeliveryNoteController::class, 'show']);

// Stock Returns
$router->get('/inventory/returns', [\App\Modules\Inventory\Http\Controllers\ReturnController::class, 'index']);
$router->get('/inventory/returns/create', [\App\Modules\Inventory\Http\Controllers\ReturnController::class, 'create']);
$router->post('/inventory/returns/store', [\App\Modules\Inventory\Http\Controllers\ReturnController::class, 'store']);
$router->get('/inventory/returns/{id}/edit', [\App\Modules\Inventory\Http\Controllers\ReturnController::class, 'edit']);
$router->post('/inventory/returns/{id}/update', [\App\Modules\Inventory\Http\Controllers\ReturnController::class, 'update']);
$router->post('/inventory/returns/{id}/delete', [\App\Modules\Inventory\Http\Controllers\ReturnController::class, 'delete']);
$router->get('/inventory/returns/{id}', [\App\Modules\Inventory\Http\Controllers\ReturnController::class, 'show']);

// Stock Adjustments Routes
$router->get('/inventory/adjustments', [\App\Modules\Inventory\Http\Controllers\AdjustmentController::class, 'index']);
$router->get('/inventory/adjustments/create', [\App\Modules\Inventory\Http\Controllers\AdjustmentController::class, 'create']);
$router->post('/inventory/adjustments/store', [\App\Modules\Inventory\Http\Controllers\AdjustmentController::class, 'store']);
$router->get('/inventory/adjustments/{id}/edit', [\App\Modules\Inventory\Http\Controllers\AdjustmentController::class, 'edit']);
$router->post('/inventory/adjustments/{id}/update', [\App\Modules\Inventory\Http\Controllers\AdjustmentController::class, 'update']);
$router->post('/inventory/adjustments/{id}/delete', [\App\Modules\Inventory\Http\Controllers\AdjustmentController::class, 'delete']);
$router->get('/inventory/adjustments/{id}', [\App\Modules\Inventory\Http\Controllers\AdjustmentController::class, 'show']);

// Stock Ledger
$router->get('/inventory/stock/ledger', [\App\Modules\Inventory\Http\Controllers\StockLedgerController::class, 'index']);

// ==========================================
// ACCOUNTING MODULE ROUTES
// ==========================================
// Chart of Accounts
$router->get('/accounting/chart-of-accounts', [\App\Modules\Accounting\Http\Controllers\AccountController::class, 'index']);
$router->get('/accounting/chart-of-accounts/create', [\App\Modules\Accounting\Http\Controllers\AccountController::class, 'create']);
$router->post('/accounting/chart-of-accounts/store', [\App\Modules\Accounting\Http\Controllers\AccountController::class, 'store']);
$router->get('/accounting/chart-of-accounts/{id}', [\App\Modules\Accounting\Http\Controllers\AccountController::class, 'show']);
$router->get('/accounting/chart-of-accounts/{id}/edit', [\App\Modules\Accounting\Http\Controllers\AccountController::class, 'edit']);
$router->post('/accounting/chart-of-accounts/{id}/update', [\App\Modules\Accounting\Http\Controllers\AccountController::class, 'update']);
$router->post('/accounting/chart-of-accounts/{id}/delete', [\App\Modules\Accounting\Http\Controllers\AccountController::class, 'delete']);

// Journal Entries
$router->get('/accounting/journal-entries', [\App\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'index']);
$router->get('/accounting/journal-entries/create', [\App\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'create']);
$router->post('/accounting/journal-entries/store', [\App\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'store']);
$router->get('/accounting/journal-entries/{id}', [\App\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'show']);
$router->get('/accounting/journal-entries/{id}/edit', [\App\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'edit']);
$router->post('/accounting/journal-entries/{id}/update', [\App\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'update']);
$router->post('/accounting/journal-entries/{id}/post', [\App\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'post']);
$router->post('/accounting/journal-entries/{id}/delete', [\App\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'delete']);

// Cost Centers
$router->get('/accounting/cost-centers', [\App\Modules\Accounting\Http\Controllers\CostCenterController::class, 'index']);
$router->get('/accounting/cost-centers/create', [\App\Modules\Accounting\Http\Controllers\CostCenterController::class, 'create']);
$router->post('/accounting/cost-centers/store', [\App\Modules\Accounting\Http\Controllers\CostCenterController::class, 'store']);
$router->get('/accounting/cost-centers/{id}', [\App\Modules\Accounting\Http\Controllers\CostCenterController::class, 'show']);
$router->get('/accounting/cost-centers/{id}/edit', [\App\Modules\Accounting\Http\Controllers\CostCenterController::class, 'edit']);
$router->post('/accounting/cost-centers/{id}/update', [\App\Modules\Accounting\Http\Controllers\CostCenterController::class, 'update']);
$router->post('/accounting/cost-centers/{id}/delete', [\App\Modules\Accounting\Http\Controllers\CostCenterController::class, 'delete']);
$router->get('/accounting/cost-centers/{id}/report', [\App\Modules\Accounting\Http\Controllers\CostCenterController::class, 'report']);

// Fixed Assets Routes
$router->get('/accounting/fixed-assets', [\App\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'index']);
$router->get('/accounting/fixed-assets/create', [\App\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'create']);
$router->post('/accounting/fixed-assets/store', [\App\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'store']);
$router->get('/accounting/fixed-assets/{id}', [\App\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'show']);
$router->get('/accounting/fixed-assets/{id}/edit', [\App\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'edit']);
$router->post('/accounting/fixed-assets/{id}/update', [\App\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'update']);
$router->post('/accounting/fixed-assets/{id}/delete', [\App\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'delete']);
$router->post('/accounting/fixed-assets/{id}/depreciate', [\App\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'depreciate']);

// Budgets & Financial Planning Routes
$router->get('/accounting/budgets', [\App\Modules\Accounting\Http\Controllers\BudgetController::class, 'index']);
$router->get('/accounting/budgets/create', [\App\Modules\Accounting\Http\Controllers\BudgetController::class, 'create']);
$router->post('/accounting/budgets/store', [\App\Modules\Accounting\Http\Controllers\BudgetController::class, 'store']);
$router->get('/accounting/budgets/{id}', [\App\Modules\Accounting\Http\Controllers\BudgetController::class, 'show']);
$router->get('/accounting/budgets/{id}/edit', [\App\Modules\Accounting\Http\Controllers\BudgetController::class, 'edit']);
$router->post('/accounting/budgets/{id}/update', [\App\Modules\Accounting\Http\Controllers\BudgetController::class, 'update']);
$router->post('/accounting/budgets/{id}/delete', [\App\Modules\Accounting\Http\Controllers\BudgetController::class, 'delete']);

// Bank Reconciliation Routes
$router->get('/accounting/bank-reconciliation', [\App\Modules\Accounting\Http\Controllers\BankReconciliationController::class, 'index']);
$router->get('/accounting/bank-reconciliation/create', [\App\Modules\Accounting\Http\Controllers\BankReconciliationController::class, 'create']);
$router->post('/accounting/bank-reconciliation/store', [\App\Modules\Accounting\Http\Controllers\BankReconciliationController::class, 'store']);
$router->get('/accounting/bank-reconciliation/{id}', [\App\Modules\Accounting\Http\Controllers\BankReconciliationController::class, 'show']);
$router->get('/accounting/bank-reconciliation/{id}/edit', [\App\Modules\Accounting\Http\Controllers\BankReconciliationController::class, 'edit']);
$router->post('/accounting/bank-reconciliation/{id}/update', [\App\Modules\Accounting\Http\Controllers\BankReconciliationController::class, 'update']);
$router->post('/accounting/bank-reconciliation/{id}/match', [\App\Modules\Accounting\Http\Controllers\BankReconciliationController::class, 'matchItems']);
$router->post('/accounting/bank-reconciliation/{id}/finalize', [\App\Modules\Accounting\Http\Controllers\BankReconciliationController::class, 'finalize']);
$router->post('/accounting/bank-reconciliation/{id}/delete', [\App\Modules\Accounting\Http\Controllers\BankReconciliationController::class, 'delete']);
$router->post('/accounting/bank-reconciliation/{id}/auto-match', [\App\Modules\Accounting\Http\Controllers\BankReconciliationController::class, 'autoMatch']);
$router->post('/accounting/bank-reconciliation/{id}/add-fee', [\App\Modules\Accounting\Http\Controllers\BankReconciliationController::class, 'addFee']);

// Fiscal Periods Extended Routes
$router->get('/accounting/fiscal-periods', [\App\Modules\Accounting\Http\Controllers\FiscalPeriodController::class, 'index']);
$router->get('/accounting/fiscal-periods/create', [\App\Modules\Accounting\Http\Controllers\FiscalPeriodController::class, 'create']);
$router->post('/accounting/fiscal-periods/store', [\App\Modules\Accounting\Http\Controllers\FiscalPeriodController::class, 'store']);
$router->get('/accounting/fiscal-periods/{id}', [\App\Modules\Accounting\Http\Controllers\FiscalPeriodController::class, 'show']);
$router->get('/accounting/fiscal-periods/{id}/edit', [\App\Modules\Accounting\Http\Controllers\FiscalPeriodController::class, 'edit']);
$router->post('/accounting/fiscal-periods/{id}/update', [\App\Modules\Accounting\Http\Controllers\FiscalPeriodController::class, 'update']);
$router->post('/accounting/fiscal-periods/sub-period/{subId}/toggle', [\App\Modules\Accounting\Http\Controllers\FiscalPeriodController::class, 'toggleSubPeriod']);
$router->post('/accounting/fiscal-periods/{id}/close', [\App\Modules\Accounting\Http\Controllers\FiscalPeriodController::class, 'closePeriod']);
$router->post('/accounting/fiscal-periods/{id}/close-year', [\App\Modules\Accounting\Http\Controllers\FiscalPeriodController::class, 'closeYear']);
$router->post('/accounting/fiscal-periods/{id}/delete', [\App\Modules\Accounting\Http\Controllers\FiscalPeriodController::class, 'delete']);

// Taxes & Duties Routes
$router->get('/accounting/taxes', [\App\Modules\Accounting\Http\Controllers\TaxController::class, 'index']);
$router->get('/accounting/taxes/create', [\App\Modules\Accounting\Http\Controllers\TaxController::class, 'create']);
$router->post('/accounting/taxes/store', [\App\Modules\Accounting\Http\Controllers\TaxController::class, 'store']);
$router->get('/accounting/taxes/{id}', [\App\Modules\Accounting\Http\Controllers\TaxController::class, 'show']);
$router->get('/accounting/taxes/{id}/edit', [\App\Modules\Accounting\Http\Controllers\TaxController::class, 'edit']);
$router->post('/accounting/taxes/{id}/update', [\App\Modules\Accounting\Http\Controllers\TaxController::class, 'update']);
$router->post('/accounting/taxes/{id}/delete', [\App\Modules\Accounting\Http\Controllers\TaxController::class, 'delete']);

// Financial Statements & Executive Reports
$router->get('/accounting/reports/balance-sheet', [\App\Modules\Accounting\Http\Controllers\FinancialReportController::class, 'balanceSheet']);
$router->get('/accounting/reports/cash-flow', [\App\Modules\Accounting\Http\Controllers\FinancialReportController::class, 'cashFlow']);
$router->get('/accounting/reports/vat-return', [\App\Modules\Accounting\Http\Controllers\FinancialReportController::class, 'vatReturn']);
$router->get('/accounting/reports/ledger', [\App\Modules\Accounting\Http\Controllers\FinancialReportController::class, 'ledger']);
$router->get('/accounting/reports/trial-balance', [\App\Modules\Accounting\Http\Controllers\FinancialReportController::class, 'trialBalance']);
$router->get('/accounting/reports/income-statement', [\App\Modules\Accounting\Http\Controllers\IncomeStatementController::class, 'index']);

// ==========================================
// TREASURY & BANKING MODULE
// ==========================================
// Dashboard & Accounts
$router->get('/treasury/accounts', [\App\Modules\Treasury\Http\Controllers\TreasuryAccountController::class, 'index']);
$router->get('/treasury/accounts/create', [\App\Modules\Treasury\Http\Controllers\TreasuryAccountController::class, 'create']);
$router->post('/treasury/accounts/store', [\App\Modules\Treasury\Http\Controllers\TreasuryAccountController::class, 'store']);
$router->get('/treasury/accounts/{id}/edit', [\App\Modules\Treasury\Http\Controllers\TreasuryAccountController::class, 'edit']);
$router->post('/treasury/accounts/{id}/update', [\App\Modules\Treasury\Http\Controllers\TreasuryAccountController::class, 'update']);
$router->post('/treasury/accounts/{id}/delete', [\App\Modules\Treasury\Http\Controllers\TreasuryAccountController::class, 'delete']);
$router->get('/treasury/accounts/{id}', [\App\Modules\Treasury\Http\Controllers\TreasuryAccountController::class, 'show']);

// Receipt Vouchers
$router->get('/treasury/receipts', [\App\Modules\Treasury\Http\Controllers\ReceiptController::class, 'index']);
$router->get('/treasury/receipts/create', [\App\Modules\Treasury\Http\Controllers\ReceiptController::class, 'create']);
$router->post('/treasury/receipts/store', [\App\Modules\Treasury\Http\Controllers\ReceiptController::class, 'store']);
$router->get('/treasury/receipts/{id}/edit', [\App\Modules\Treasury\Http\Controllers\ReceiptController::class, 'edit']);
$router->post('/treasury/receipts/{id}/update', [\App\Modules\Treasury\Http\Controllers\ReceiptController::class, 'update']);
$router->post('/treasury/receipts/{id}/delete', [\App\Modules\Treasury\Http\Controllers\ReceiptController::class, 'delete']);
$router->get('/treasury/receipts/{id}', [\App\Modules\Treasury\Http\Controllers\ReceiptController::class, 'show']);

// Payment Vouchers
$router->get('/treasury/payments', [\App\Modules\Treasury\Http\Controllers\PaymentVoucherController::class, 'index']);
$router->get('/treasury/payments/create', [\App\Modules\Treasury\Http\Controllers\PaymentVoucherController::class, 'create']);
$router->post('/treasury/payments/store', [\App\Modules\Treasury\Http\Controllers\PaymentVoucherController::class, 'store']);
$router->get('/treasury/payments/{id}/edit', [\App\Modules\Treasury\Http\Controllers\PaymentVoucherController::class, 'edit']);
$router->post('/treasury/payments/{id}/update', [\App\Modules\Treasury\Http\Controllers\PaymentVoucherController::class, 'update']);
$router->post('/treasury/payments/{id}/delete', [\App\Modules\Treasury\Http\Controllers\PaymentVoucherController::class, 'delete']);
$router->get('/treasury/payments/{id}', [\App\Modules\Treasury\Http\Controllers\PaymentVoucherController::class, 'show']);

// Internal Transfers
$router->get('/treasury/transfers', [\App\Modules\Treasury\Http\Controllers\InternalTransferController::class, 'index']);
$router->get('/treasury/transfers/create', [\App\Modules\Treasury\Http\Controllers\InternalTransferController::class, 'create']);
$router->post('/treasury/transfers/store', [\App\Modules\Treasury\Http\Controllers\InternalTransferController::class, 'store']);
$router->get('/treasury/transfers/{id}/edit', [\App\Modules\Treasury\Http\Controllers\InternalTransferController::class, 'edit']);
$router->post('/treasury/transfers/{id}/update', [\App\Modules\Treasury\Http\Controllers\InternalTransferController::class, 'update']);
$router->post('/treasury/transfers/{id}/delete', [\App\Modules\Treasury\Http\Controllers\InternalTransferController::class, 'delete']);
$router->get('/treasury/transfers/{id}', [\App\Modules\Treasury\Http\Controllers\InternalTransferController::class, 'show']);

// Petty Cash & Custody
$router->get('/treasury/petty-cash', [\App\Modules\Treasury\Http\Controllers\PettyCashController::class, 'index']);
$router->get('/treasury/petty-cash/create', [\App\Modules\Treasury\Http\Controllers\PettyCashController::class, 'create']);
$router->post('/treasury/petty-cash/store', [\App\Modules\Treasury\Http\Controllers\PettyCashController::class, 'store']);
$router->get('/treasury/petty-cash/{id}/edit', [\App\Modules\Treasury\Http\Controllers\PettyCashController::class, 'edit']);
$router->post('/treasury/petty-cash/{id}/update', [\App\Modules\Treasury\Http\Controllers\PettyCashController::class, 'update']);
$router->post('/treasury/petty-cash/{id}/settle', [\App\Modules\Treasury\Http\Controllers\PettyCashController::class, 'settle']);
$router->post('/treasury/petty-cash/{id}/delete', [\App\Modules\Treasury\Http\Controllers\PettyCashController::class, 'delete']);
$router->get('/treasury/petty-cash/{id}', [\App\Modules\Treasury\Http\Controllers\PettyCashController::class, 'show']);

// Cheque Management
$router->get('/treasury/cheques', [\App\Modules\Treasury\Http\Controllers\ChequeController::class, 'index']);
$router->get('/treasury/cheques/create', [\App\Modules\Treasury\Http\Controllers\ChequeController::class, 'create']);
$router->post('/treasury/cheques/store', [\App\Modules\Treasury\Http\Controllers\ChequeController::class, 'store']);
$router->get('/treasury/cheques/{id}/edit', [\App\Modules\Treasury\Http\Controllers\ChequeController::class, 'edit']);
$router->post('/treasury/cheques/{id}/update', [\App\Modules\Treasury\Http\Controllers\ChequeController::class, 'update']);
$router->post('/treasury/cheques/{id}/status', [\App\Modules\Treasury\Http\Controllers\ChequeController::class, 'updateStatus']);
$router->post('/treasury/cheques/{id}/delete', [\App\Modules\Treasury\Http\Controllers\ChequeController::class, 'delete']);
$router->get('/treasury/cheques/{id}', [\App\Modules\Treasury\Http\Controllers\ChequeController::class, 'show']);

// Treasury Reports
$router->get('/treasury/reports/cash-book', [\App\Modules\Treasury\Http\Controllers\TreasuryReportController::class, 'cashBook']);

// ==========================================
// HUMAN RESOURCES (HR) MODULE
// ==========================================

// Departments & Org Structure
$router->get('/hr/departments', [\App\Modules\HR\Http\Controllers\DepartmentController::class, 'index']);
$router->get('/hr/departments/create', [\App\Modules\HR\Http\Controllers\DepartmentController::class, 'create']);
$router->post('/hr/departments/store', [\App\Modules\HR\Http\Controllers\DepartmentController::class, 'store']);
$router->get('/hr/departments/{id}', [\App\Modules\HR\Http\Controllers\DepartmentController::class, 'show']); 
$router->get('/hr/departments/{id}/edit', [\App\Modules\HR\Http\Controllers\DepartmentController::class, 'edit']);
$router->post('/hr/departments/{id}/update', [\App\Modules\HR\Http\Controllers\DepartmentController::class, 'update']);
$router->post('/hr/departments/{id}/delete', [\App\Modules\HR\Http\Controllers\DepartmentController::class, 'delete']);

// Designations & Job Titles
$router->get('/hr/designations', [\App\Modules\HR\Http\Controllers\DesignationController::class, 'index']);
$router->get('/hr/designations/create', [\App\Modules\HR\Http\Controllers\DesignationController::class, 'create']);
$router->post('/hr/designations/store', [\App\Modules\HR\Http\Controllers\DesignationController::class, 'store']);
$router->get('/hr/designations/{id}', [\App\Modules\HR\Http\Controllers\DesignationController::class, 'show']); 
$router->get('/hr/designations/{id}/edit', [\App\Modules\HR\Http\Controllers\DesignationController::class, 'edit']);
$router->post('/hr/designations/{id}/update', [\App\Modules\HR\Http\Controllers\DesignationController::class, 'update']);
$router->post('/hr/designations/{id}/delete', [\App\Modules\HR\Http\Controllers\DesignationController::class, 'delete']);

// Employees Directory
$router->get('/hr/employees', [\App\Modules\HR\Http\Controllers\EmployeeController::class, 'index']);
$router->get('/hr/employees/create', [\App\Modules\HR\Http\Controllers\EmployeeController::class, 'create']);
$router->post('/hr/employees/store', [\App\Modules\HR\Http\Controllers\EmployeeController::class, 'store']);
$router->get('/hr/employees/{id}', [\App\Modules\HR\Http\Controllers\EmployeeController::class, 'show']);
$router->get('/hr/employees/{id}/edit', [\App\Modules\HR\Http\Controllers\EmployeeController::class, 'edit']);
$router->post('/hr/employees/{id}/update', [\App\Modules\HR\Http\Controllers\EmployeeController::class, 'update']);
$router->post('/hr/employees/{id}/delete', [\App\Modules\HR\Http\Controllers\EmployeeController::class, 'delete']);

// Employment Contracts
$router->get('/hr/contracts', [\App\Modules\HR\Http\Controllers\EmployeeContractController::class, 'index']);
$router->get('/hr/contracts/create', [\App\Modules\HR\Http\Controllers\EmployeeContractController::class, 'create']);
$router->post('/hr/contracts/store', [\App\Modules\HR\Http\Controllers\EmployeeContractController::class, 'store']);
$router->get('/hr/contracts/{id}', [\App\Modules\HR\Http\Controllers\EmployeeContractController::class, 'show']);
$router->get('/hr/contracts/{id}/edit', [\App\Modules\HR\Http\Controllers\EmployeeContractController::class, 'edit']);
$router->post('/hr/contracts/{id}/update', [\App\Modules\HR\Http\Controllers\EmployeeContractController::class, 'update']);
$router->post('/hr/contracts/{id}/delete', [\App\Modules\HR\Http\Controllers\EmployeeContractController::class, 'delete']);

// Attendance Management
$router->get('/hr/attendance', [\App\Modules\HR\Http\Controllers\AttendanceController::class, 'index']);
$router->get('/hr/attendance/log', [\App\Modules\HR\Http\Controllers\AttendanceController::class, 'log']);
$router->post('/hr/attendance/bulk-store', [\App\Modules\HR\Http\Controllers\AttendanceController::class, 'storeBulk']);
$router->get('/hr/attendance/{id}', [\App\Modules\HR\Http\Controllers\AttendanceController::class, 'show']);
$router->post('/hr/attendance/store', [\App\Modules\HR\Http\Controllers\AttendanceController::class, 'store']);

// Shifts & Schedules
$router->get('/hr/shifts', [\App\Modules\HR\Http\Controllers\ShiftController::class, 'index']);
$router->get('/hr/shifts/create', [\App\Modules\HR\Http\Controllers\ShiftController::class, 'create']);
$router->post('/hr/shifts/store', [\App\Modules\HR\Http\Controllers\ShiftController::class, 'store']);
$router->get('/hr/shifts/{id}', [\App\Modules\HR\Http\Controllers\ShiftController::class, 'show']);
$router->get('/hr/shifts/{id}/edit', [\App\Modules\HR\Http\Controllers\ShiftController::class, 'edit']);
$router->post('/hr/shifts/{id}/update', [\App\Modules\HR\Http\Controllers\ShiftController::class, 'update']);
$router->post('/hr/shifts/{id}/delete', [\App\Modules\HR\Http\Controllers\ShiftController::class, 'delete']);

// Leaves & Requests
$router->get('/hr/leaves', [\App\Modules\HR\Http\Controllers\LeaveController::class, 'index']);
$router->get('/hr/leaves/create', [\App\Modules\HR\Http\Controllers\LeaveController::class, 'create']);
$router->post('/hr/leaves/store', [\App\Modules\HR\Http\Controllers\LeaveController::class, 'store']);
$router->get('/hr/leaves/{id}', [\App\Modules\HR\Http\Controllers\LeaveController::class, 'show']);
$router->post('/hr/leaves/{id}/approve', [\App\Modules\HR\Http\Controllers\LeaveController::class, 'approve']);
$router->post('/hr/leaves/{id}/reject', [\App\Modules\HR\Http\Controllers\LeaveController::class, 'reject']);
$router->post('/hr/leaves/{id}/delete', [\App\Modules\HR\Http\Controllers\LeaveController::class, 'delete']);

// Payroll & Salary Processing
$router->get('/hr/payroll', [\App\Modules\HR\Http\Controllers\PayrollController::class, 'index']);
$router->get('/hr/payroll/create', [\App\Modules\HR\Http\Controllers\PayrollController::class, 'create']);
$router->post('/hr/payroll/process', [\App\Modules\HR\Http\Controllers\PayrollController::class, 'process']);
$router->get('/hr/payroll/{id}', [\App\Modules\HR\Http\Controllers\PayrollController::class, 'show']);
$router->get('/hr/payroll/{id}/edit', [\App\Modules\HR\Http\Controllers\PayrollController::class, 'edit']);
$router->post('/hr/payroll/{id}/update', [\App\Modules\HR\Http\Controllers\PayrollController::class, 'update']);
$router->post('/hr/payroll/{id}/delete', [\App\Modules\HR\Http\Controllers\PayrollController::class, 'delete']);

// Salary Components & Structure
$router->get('/hr/salary-components', [\App\Modules\HR\Http\Controllers\SalaryComponentController::class, 'index']);
$router->get('/hr/salary-components/create', [\App\Modules\HR\Http\Controllers\SalaryComponentController::class, 'create']);
$router->post('/hr/salary-components/store', [\App\Modules\HR\Http\Controllers\SalaryComponentController::class, 'store']);
$router->get('/hr/salary-components/{id}', [\App\Modules\HR\Http\Controllers\SalaryComponentController::class, 'show']);
$router->get('/hr/salary-components/{id}/edit', [\App\Modules\HR\Http\Controllers\SalaryComponentController::class, 'edit']);
$router->post('/hr/salary-components/{id}/update', [\App\Modules\HR\Http\Controllers\SalaryComponentController::class, 'update']);
$router->post('/hr/salary-components/{id}/delete', [\App\Modules\HR\Http\Controllers\SalaryComponentController::class, 'delete']);

// Performance Appraisals
$router->get('/hr/appraisals', [\App\Modules\HR\Http\Controllers\AppraisalController::class, 'index']);
$router->get('/hr/appraisals/create', [\App\Modules\HR\Http\Controllers\AppraisalController::class, 'create']);
$router->post('/hr/appraisals/store', [\App\Modules\HR\Http\Controllers\AppraisalController::class, 'store']);
$router->get('/hr/appraisals/{id}', [\App\Modules\HR\Http\Controllers\AppraisalController::class, 'show']);
$router->get('/hr/appraisals/{id}/edit', [\App\Modules\HR\Http\Controllers\AppraisalController::class, 'edit']);
$router->post('/hr/appraisals/{id}/update', [\App\Modules\HR\Http\Controllers\AppraisalController::class, 'update']);
$router->post('/hr/appraisals/{id}/delete', [\App\Modules\HR\Http\Controllers\AppraisalController::class, 'delete']);

// Recruitment & Applicants
$router->get('/hr/recruitment', [\App\Modules\HR\Http\Controllers\RecruitmentController::class, 'index']);
$router->get('/hr/recruitment/create', [\App\Modules\HR\Http\Controllers\RecruitmentController::class, 'create']);
$router->post('/hr/recruitment/store', [\App\Modules\HR\Http\Controllers\RecruitmentController::class, 'store']);
$router->get('/hr/recruitment/{id}', [\App\Modules\HR\Http\Controllers\RecruitmentController::class, 'show']);
$router->get('/hr/recruitment/{id}/edit', [\App\Modules\HR\Http\Controllers\RecruitmentController::class, 'edit']);
$router->post('/hr/recruitment/{id}/update', [\App\Modules\HR\Http\Controllers\RecruitmentController::class, 'update']);
$router->post('/hr/recruitment/{id}/delete', [\App\Modules\HR\Http\Controllers\RecruitmentController::class, 'delete']);

// Employee Documents Management
$router->get('/hr/documents', [\App\Modules\HR\Http\Controllers\DocumentController::class, 'index']);
$router->get('/hr/documents/create', [\App\Modules\HR\Http\Controllers\DocumentController::class, 'create']);
$router->post('/hr/documents/store', [\App\Modules\HR\Http\Controllers\DocumentController::class, 'store']);
$router->get('/hr/documents/{id}', [\App\Modules\HR\Http\Controllers\DocumentController::class, 'show']);
$router->get('/hr/documents/{id}/edit', [\App\Modules\HR\Http\Controllers\DocumentController::class, 'edit']);
$router->post('/hr/documents/{id}/update', [\App\Modules\HR\Http\Controllers\DocumentController::class, 'update']);
$router->post('/hr/documents/{id}/delete', [\App\Modules\HR\Http\Controllers\DocumentController::class, 'delete']);

// ==========================================
// SETTINGS & ROLES / USERS MODULE
// ==========================================
// Roles & Permissions
$router->get('/settings/roles', [\App\Modules\Settings\Http\Controllers\RoleController::class, 'index']);
$router->get('/settings/roles/create', [\App\Modules\Settings\Http\Controllers\RoleController::class, 'create']);
$router->post('/settings/roles/store', [\App\Modules\Settings\Http\Controllers\RoleController::class, 'store']);
$router->get('/settings/roles/{id}/edit', [\App\Modules\Settings\Http\Controllers\RoleController::class, 'edit']);
$router->post('/settings/roles/{id}/update', [\App\Modules\Settings\Http\Controllers\RoleController::class, 'update']);
$router->post('/settings/roles/{id}/delete', [\App\Modules\Settings\Http\Controllers\RoleController::class, 'delete']);

// Users Management
$router->get('/settings/users', [\App\Modules\Settings\Http\Controllers\UserController::class, 'index']);
$router->get('/settings/users/create', [\App\Modules\Settings\Http\Controllers\UserController::class, 'create']);
$router->post('/settings/users/store', [\App\Modules\Settings\Http\Controllers\UserController::class, 'store']);
$router->get('/settings/users/{id}/edit', [\App\Modules\Settings\Http\Controllers\UserController::class, 'edit']);
$router->post('/settings/users/{id}/update', [\App\Modules\Settings\Http\Controllers\UserController::class, 'update']);
$router->post('/settings/users/{id}/delete', [\App\Modules\Settings\Http\Controllers\UserController::class, 'delete']);

// ==========================================
// PROJECTS & CONTRACTING MODULE
// ==========================================

$router->get('/projects/dashboard', [\App\Modules\Projects\Http\Controllers\ProjectDashboardController::class, 'index']);

$router->get('/projects/list', [\App\Modules\Projects\Http\Controllers\ProjectController::class, 'index']);
$router->get('/projects/list/create', [\App\Modules\Projects\Http\Controllers\ProjectController::class, 'create']);
$router->post('/projects/list/store', [\App\Modules\Projects\Http\Controllers\ProjectController::class, 'store']);
$router->get('/projects/list/{id}', [\App\Modules\Projects\Http\Controllers\ProjectController::class, 'show']);
$router->get('/projects/list/{id}/edit', [\App\Modules\Projects\Http\Controllers\ProjectController::class, 'edit']);
$router->post('/projects/list/{id}/update', [\App\Modules\Projects\Http\Controllers\ProjectController::class, 'update']);
$router->post('/projects/list/{id}/delete', [\App\Modules\Projects\Http\Controllers\ProjectController::class, 'delete']);

$router->get('/projects/milestones', [\App\Modules\Projects\Http\Controllers\MilestoneController::class, 'index']);
$router->get('/projects/milestones/create', [\App\Modules\Projects\Http\Controllers\MilestoneController::class, 'create']);
$router->post('/projects/milestones/store', [\App\Modules\Projects\Http\Controllers\MilestoneController::class, 'store']);
$router->get('/projects/milestones/{id}', [\App\Modules\Projects\Http\Controllers\MilestoneController::class, 'show']);
$router->get('/projects/milestones/{id}/edit', [\App\Modules\Projects\Http\Controllers\MilestoneController::class, 'edit']);
$router->post('/projects/milestones/{id}/update', [\App\Modules\Projects\Http\Controllers\MilestoneController::class, 'update']);
$router->post('/projects/milestones/{id}/delete', [\App\Modules\Projects\Http\Controllers\MilestoneController::class, 'delete']);

$router->get('/projects/invoices', [\App\Modules\Projects\Http\Controllers\ProjectInvoiceController::class, 'index']);
$router->get('/projects/invoices/create', [\App\Modules\Projects\Http\Controllers\ProjectInvoiceController::class, 'create']);
$router->post('/projects/invoices/store', [\App\Modules\Projects\Http\Controllers\ProjectInvoiceController::class, 'store']);
$router->get('/projects/invoices/{id}', [\App\Modules\Projects\Http\Controllers\ProjectInvoiceController::class, 'show']);
$router->get('/projects/invoices/{id}/edit', [\App\Modules\Projects\Http\Controllers\ProjectInvoiceController::class, 'edit']);
$router->post('/projects/invoices/{id}/update', [\App\Modules\Projects\Http\Controllers\ProjectInvoiceController::class, 'update']);
$router->post('/projects/invoices/{id}/status', [\App\Modules\Projects\Http\Controllers\ProjectInvoiceController::class, 'updateStatus']);
$router->post('/projects/invoices/{id}/delete', [\App\Modules\Projects\Http\Controllers\ProjectInvoiceController::class, 'delete']);

$router->get('/projects/costs', [\App\Modules\Projects\Http\Controllers\ProjectCostController::class, 'index']);
$router->get('/projects/costs/create', [\App\Modules\Projects\Http\Controllers\ProjectCostController::class, 'create']);
$router->post('/projects/costs/store', [\App\Modules\Projects\Http\Controllers\ProjectCostController::class, 'store']);
$router->get('/projects/costs/{id}', [\App\Modules\Projects\Http\Controllers\ProjectCostController::class, 'show']);
$router->get('/projects/costs/{id}/edit', [\App\Modules\Projects\Http\Controllers\ProjectCostController::class, 'edit']);
$router->post('/projects/costs/{id}/update', [\App\Modules\Projects\Http\Controllers\ProjectCostController::class, 'update']);
$router->post('/projects/costs/{id}/delete', [\App\Modules\Projects\Http\Controllers\ProjectCostController::class, 'delete']);

$router->get('/projects/contracts', [\App\Modules\Projects\Http\Controllers\ProjectContractController::class, 'index']);
$router->get('/projects/contracts/create', [\App\Modules\Projects\Http\Controllers\ProjectContractController::class, 'create']);
$router->post('/projects/contracts/store', [\App\Modules\Projects\Http\Controllers\ProjectContractController::class, 'store']);
$router->get('/projects/contracts/{id}', [\App\Modules\Projects\Http\Controllers\ProjectContractController::class, 'show']);
$router->get('/projects/contracts/{id}/edit', [\App\Modules\Projects\Http\Controllers\ProjectContractController::class, 'edit']);
$router->post('/projects/contracts/{id}/update', [\App\Modules\Projects\Http\Controllers\ProjectContractController::class, 'update']);
$router->post('/projects/contracts/{id}/delete', [\App\Modules\Projects\Http\Controllers\ProjectContractController::class, 'delete']);

// =========================================================================
// موديول إدارة النظام والإعدادات (Administration & System Settings)
// =========================================================================

// 1. الشركات (Companies)
$router->get('/admin/companies', [\App\Modules\Admin\Http\Controllers\CompanyController::class, 'index']);
$router->get('/admin/companies/create', [\App\Modules\Admin\Http\Controllers\CompanyController::class, 'create']);
$router->post('/admin/companies/store', [\App\Modules\Admin\Http\Controllers\CompanyController::class, 'store']);
$router->get('/admin/companies/{id}', [\App\Modules\Admin\Http\Controllers\CompanyController::class, 'show']);
$router->get('/admin/companies/{id}/edit', [\App\Modules\Admin\Http\Controllers\CompanyController::class, 'edit']);
$router->post('/admin/companies/{id}/update', [\App\Modules\Admin\Http\Controllers\CompanyController::class, 'update']);
$router->post('/admin/companies/{id}/delete', [\App\Modules\Admin\Http\Controllers\CompanyController::class, 'delete']);

// 2. الفروع (Branches)
$router->get('/admin/branches', [\App\Modules\Admin\Http\Controllers\BranchController::class, 'index']);
$router->get('/admin/branches/create', [\App\Modules\Admin\Http\Controllers\BranchController::class, 'create']);
$router->post('/admin/branches/store', [\App\Modules\Admin\Http\Controllers\BranchController::class, 'store']);
$router->get('/admin/branches/{id}', [\App\Modules\Admin\Http\Controllers\BranchController::class, 'show']);
$router->get('/admin/branches/{id}/edit', [\App\Modules\Admin\Http\Controllers\BranchController::class, 'edit']);
$router->post('/admin/branches/{id}/update', [\App\Modules\Admin\Http\Controllers\BranchController::class, 'update']);
$router->post('/admin/branches/{id}/delete', [\App\Modules\Admin\Http\Controllers\BranchController::class, 'delete']);

// =========================================================================
// موديول الإعدادات والصلاحيات (Settings & Access Control)
// =========================================================================

// 1. الأدوار والصلاحيات (Roles)
$router->get('/settings/roles', [\App\Modules\Settings\Http\Controllers\RoleController::class, 'index']);
$router->get('/settings/roles/create', [\App\Modules\Settings\Http\Controllers\RoleController::class, 'create']);
$router->post('/settings/roles/store', [\App\Modules\Settings\Http\Controllers\RoleController::class, 'store']);
$router->get('/settings/roles/{id}', [\App\Modules\Settings\Http\Controllers\RoleController::class, 'index']);
$router->get('/settings/roles/{id}/edit', [\App\Modules\Settings\Http\Controllers\RoleController::class, 'edit']);
$router->post('/settings/roles/{id}/update', [\App\Modules\Settings\Http\Controllers\RoleController::class, 'update']);
$router->post('/settings/roles/{id}/delete', [\App\Modules\Settings\Http\Controllers\RoleController::class, 'delete']);

// 2. حسابات المستخدمين (Users)
$router->get('/settings/users', [\App\Modules\Settings\Http\Controllers\UserController::class, 'index']);
$router->get('/settings/users/create', [\App\Modules\Settings\Http\Controllers\UserController::class, 'create']);
$router->post('/settings/users/store', [\App\Modules\Settings\Http\Controllers\UserController::class, 'store']);
$router->get('/settings/users/{id}', [\App\Modules\Settings\Http\Controllers\UserController::class, 'index']);
$router->get('/settings/users/{id}/edit', [\App\Modules\Settings\Http\Controllers\UserController::class, 'edit']);
$router->post('/settings/users/{id}/update', [\App\Modules\Settings\Http\Controllers\UserController::class, 'update']);
$router->post('/settings/users/{id}/delete', [\App\Modules\Settings\Http\Controllers\UserController::class, 'delete']);

// 5. الإعدادات العامة للنظام (General Settings)
$router->get('/admin/settings', [\App\Modules\Admin\Http\Controllers\SettingController::class, 'index']);
$router->post('/admin/settings/update', [\App\Modules\Admin\Http\Controllers\SettingController::class, 'update']);

// 6. سجل الحركات والأمان (Audit / Activity Logs)
$router->get('/admin/logs', [\App\Modules\Admin\Http\Controllers\AuditLogController::class, 'index']);
$router->get('/admin/logs/{id}', [\App\Modules\Admin\Http\Controllers\AuditLogController::class, 'show']);

// 7. النسخ الاحتياطي والصيانة (Backups & System Maintenance)
$router->get('/admin/backups', [\App\Modules\Admin\Http\Controllers\BackupController::class, 'index']);
$router->post('/admin/backups/create', [\App\Modules\Admin\Http\Controllers\BackupController::class, 'create']);
$router->get('/admin/backups/{id}/download', [\App\Modules\Admin\Http\Controllers\BackupController::class, 'download']);
$router->post('/admin/backups/{id}/delete', [\App\Modules\Admin\Http\Controllers\BackupController::class, 'delete']);