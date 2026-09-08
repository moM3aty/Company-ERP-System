<?php
// Path: app/Modules/Inventory/Products/Http/Controllers/ProductController.php

namespace App\Modules\Inventory\Products\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Throwable;

class ProductController extends Controller
{
    private $basePath;
    private $db;

    public function __construct()
    {
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = isset($basePath) ? $basePath : dirname(__DIR__, 5);
        
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            if ($this->db) {
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        }
    }

    private function resolveId($id = null)
    {
        if (!empty($id) && is_numeric($id)) return (int)$id;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/products/(\d+)#', $uri, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    private function getCompanyId()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return (int)($_SESSION['company_id'] ?? 1);
    }

    private function getActiveBranchId()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return (int)($_SESSION['branch_id'] ?? 0);
    }

    private function hasBranchColumn($table)
    {
        if (!$this->db) return false;
        try {
            $this->db->query("SELECT branch_id FROM {$table} LIMIT 1");
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function buildBranchCond($tableAlias, $branchId, $tableName)
    {
        if ($branchId <= 0) return "";
        if (!$this->hasBranchColumn($tableName)) return "";
        $col = $tableAlias ? "{$tableAlias}.branch_id" : "branch_id";
        return " AND ({$col} = {$branchId} OR {$col} = 0 OR {$col} IS NULL)";
    }

    public function index(Request $request, Response $response)
    {
        $dbError = null;
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condP = $this->buildBranchCond('p', $branchId, 'products');
            
            $whereClause = "WHERE p.company_id = ?";
            $params = [$companyId];

            if ($search !== '') {
                $whereClause .= " AND (p.item_code LIKE ? OR p.barcode LIKE ? OR p.name_ar LIKE ? OR p.name_en LIKE ? OR c.name_ar LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like, $like, $like);
            }

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM products p 
                LEFT JOIN product_categories c ON p.category_id = c.id 
                $whereClause $condP
            ");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT p.*, COALESCE(c.name_ar, c.name_en) as category_name
                FROM products p
                LEFT JOIN product_categories c ON p.category_id = c.id
                $whereClause $condP
                ORDER BY p.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total, 
                    SUM(IF(is_active=1, 1, 0)) as active, 
                    SUM(IF(is_active=0, 1, 0)) as inactive 
                FROM products p WHERE company_id = $companyId $condP
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $products = [];
            $stats = (object)['total'=>0, 'active'=>0, 'inactive'=>0];
            $totalPages = 1;
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/inventory/products/index.php';
        if ($dbError) echo "<div style='margin:20px; padding:20px; background:#fef2f2; color:#b91c1c; border-radius:8px;'><strong>DB Error:</strong> $dbError</div>";
        if (file_exists($viewPath)) include $viewPath;
        
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response)
    {
        $product = null; $categories = [];
        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();
            $condC = $this->buildBranchCond('', $branchId, 'product_categories');

            $categories = $this->db->query("SELECT id, name_ar, name_en FROM product_categories WHERE company_id = $companyId $condC ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/inventory/products/create.php';
        if (file_exists($viewPath)) include $viewPath;
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response)
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();
            $itemCode = !empty($data['item_code']) ? trim($data['item_code']) : 'ITM-' . rand(10000, 99999);
            
            $hasBranch = $this->hasBranchColumn('products');
            $branchCol = $hasBranch ? ", branch_id" : "";
            $branchVal = $hasBranch ? ", ?" : "";

            $stmt = $this->db->prepare("
                INSERT INTO products (company_id, category_id, item_code, barcode, name_ar, name_en, description, unit, purchase_price, selling_price, reorder_level, is_active {$branchCol})
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? {$branchVal})
            ");
            
            $params = [
                $companyId, 
                !empty($data['category_id']) ? $data['category_id'] : null,
                $itemCode,
                $data['barcode'] ?? null,
                $data['name_ar'],
                $data['name_en'] ?? null,
                $data['description'] ?? null,
                $data['unit'] ?? 'قطعة',
                (float)($data['purchase_price'] ?? 0),
                (float)($data['selling_price'] ?? 0),
                (float)($data['reorder_level'] ?? 0),
                isset($data['is_active']) ? 1 : 0
            ];

            if ($hasBranch) {
                $params[] = $branchId;
            }

            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تمت إضافة الصنف بنجاح." : "Product added successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/inventory/products/create');
        }

        return new RedirectResponse('/ERP/inventory/products');
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        try {
            if (!$id) throw new \Exception("معرف الصنف غير صالح.");
            
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();
            $condC = $this->buildBranchCond('', $branchId, 'product_categories');

            $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$product) throw new \Exception("الصنف غير موجود.");

            $categories = $this->db->query("SELECT id, name_ar, name_en FROM product_categories WHERE company_id = $companyId $condC ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/inventory/products");
            exit;
        }

        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/inventory/products/create.php';
        if (file_exists($viewPath)) include $viewPath;
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if (!$id) throw new \Exception("معرف الصنف غير متاح.");

            $stmt = $this->db->prepare("
                UPDATE products 
                SET category_id=?, item_code=?, barcode=?, name_ar=?, name_en=?, description=?, unit=?, purchase_price=?, selling_price=?, reorder_level=?, is_active=?
                WHERE id=?
            ");
            $stmt->execute([
                !empty($data['category_id']) ? $data['category_id'] : null,
                $data['item_code'],
                $data['barcode'] ?? null,
                $data['name_ar'],
                $data['name_en'] ?? null,
                $data['description'] ?? null,
                $data['unit'] ?? 'قطعة',
                (float)($data['purchase_price'] ?? 0),
                (float)($data['selling_price'] ?? 0),
                (float)($data['reorder_level'] ?? 0),
                isset($data['is_active']) ? 1 : 0,
                $id
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات الصنف بنجاح." : "Product updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            header("Location: /ERP/inventory/products/{$id}/edit");
            exit;
        }

        header("Location: /ERP/inventory/products");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        try {
            if (!$id) throw new \Exception("معرف الصنف غير متاح.");

            $stmt = $this->db->prepare("
                SELECT p.*, COALESCE(c.name_ar, c.name_en) as category_name
                FROM products p
                LEFT JOIN product_categories c ON p.category_id = c.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$product) throw new \Exception("الصنف غير موجود.");

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/inventory/products");
            exit;
        }

        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/inventory/products/show.php';
        if (file_exists($viewPath)) include $viewPath;
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        try {
            if (!$id) throw new \Exception("معرف غير صالح.");

            $this->db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف الصنف بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف (قد يكون الصنف مرتبطاً بحركات مخزنية أو فواتير).";
        }
        header("Location: /ERP/inventory/products");
        exit;
    }
}