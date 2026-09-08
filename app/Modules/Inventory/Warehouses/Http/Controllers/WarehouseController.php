<?php
// Path: app/Modules/Inventory/Warehouses/Http/Controllers/WarehouseController.php

namespace App\Modules\Inventory\Warehouses\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Throwable;

class WarehouseController extends Controller
{
    private $basePath;
    private $db;

    public function __construct()
    {
        ini_set('display_errors', '1');
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
        if (preg_match('#/warehouses/(\d+)#', $uri, $matches)) {
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
        // موجه الـ URI التلقائي لضمان فتح الصفحة حتى بدون الـ Router
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/warehouses/(\d+)/edit#', $uri, $m)) {
            return $this->edit($request, $response, (int)$m[1]);
        }
        if (preg_match('#/warehouses/(\d+)/update#', $uri, $m)) {
            return $this->update($request, $response, (int)$m[1]);
        }
        if (preg_match('#/warehouses/(\d+)/delete#', $uri, $m)) {
            return $this->delete($request, $response, (int)$m[1]);
        }
        if (preg_match('#/warehouses/(\d+)$#', $uri, $m)) {
            return $this->show($request, $response, (int)$m[1]);
        }

        $dbError = null;
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $cond = $this->buildBranchCond('w', $branchId, 'warehouses');

            $whereClause = "WHERE w.company_id = ?";
            $params = [$companyId];

            if ($search !== '') {
                $whereClause .= " AND (w.code LIKE ? OR w.name_ar LIKE ? OR w.name_en LIKE ? OR w.location LIKE ? OR w.manager_name LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like, $like, $like);
            }

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM warehouses w $whereClause $cond");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT w.* FROM warehouses w
                $whereClause $cond
                ORDER BY w.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $warehouses = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total, 
                    SUM(IF(is_active=1, 1, 0)) as active, 
                    SUM(IF(is_active=0, 1, 0)) as inactive 
                FROM warehouses w WHERE company_id = $companyId $cond
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $warehouses = [];
            $stats = (object)['total'=>0, 'active'=>0, 'inactive'=>0];
            $totalPages = 1;
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/inventory/warehouses/index.php';
        if ($dbError) echo "<div style='margin:20px; padding:20px; background:#fef2f2; color:#b91c1c; border-radius:8px;'><strong>DB Error:</strong> " . htmlspecialchars($dbError) . "</div>";
        if (file_exists($viewPath)) include $viewPath;
        
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response)
    {
        $warehouse = null;
        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/inventory/warehouses/create.php';
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
            $code = !empty($data['code']) ? trim($data['code']) : 'WH-' . rand(1000, 9999);

            $hasBranch = $this->hasBranchColumn('warehouses');
            $branchCol = $hasBranch ? ", branch_id" : "";
            $branchVal = $hasBranch ? ", ?" : "";

            $stmt = $this->db->prepare("
                INSERT INTO warehouses (company_id, code, name_ar, name_en, location, manager_name, phone, is_active {$branchCol})
                VALUES (?, ?, ?, ?, ?, ?, ?, ? {$branchVal})
            ");

            $params = [
                $companyId, 
                $code, 
                $data['name_ar'], 
                $data['name_en'] ?? null, 
                $data['location'] ?? null,
                $data['manager_name'] ?? null,
                $data['phone'] ?? null,
                isset($data['is_active']) ? 1 : 0
            ];

            if ($hasBranch) {
                $params[] = $branchId;
            }

            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم إنشاء المستودع بنجاح." : "Warehouse created successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            header("Location: /ERP/inventory/warehouses/create");
            exit;
        }

        header("Location: /ERP/inventory/warehouses");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        try {
            if (!$id) throw new \Exception("معرف المستودع غير صالح.");

            $companyId = $this->getCompanyId();
            $stmt = $this->db->prepare("SELECT * FROM warehouses WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $warehouse = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$warehouse) throw new \Exception("المستودع غير موجود.");

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/inventory/warehouses");
            exit;
        }

        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/inventory/warehouses/create.php';
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
            if (!$id) throw new \Exception("معرف المستودع غير متاح.");
            
            $companyId = $this->getCompanyId();
            $stmt = $this->db->prepare("
                UPDATE warehouses 
                SET code=?, name_ar=?, name_en=?, location=?, manager_name=?, phone=?, is_active=?
                WHERE id=? AND company_id=?
            ");
            $stmt->execute([
                $data['code'], 
                $data['name_ar'], 
                $data['name_en'] ?? null, 
                $data['location'] ?? null,
                $data['manager_name'] ?? null,
                $data['phone'] ?? null,
                isset($data['is_active']) ? 1 : 0, 
                $id,
                $companyId
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات المستودع بنجاح." : "Warehouse updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            header("Location: /ERP/inventory/warehouses/{$id}/edit");
            exit;
        }

        header("Location: /ERP/inventory/warehouses");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $this->getCompanyId();

        try {
            if (!$id) throw new \Exception("معرف غير صالح.");

            $this->db->prepare("DELETE FROM warehouses WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف المستودع بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف. المستودع مرتبط بحركات مخزنية.";
        }
        
        header("Location: /ERP/inventory/warehouses");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        try {
            $id = $this->resolveId($id);
            if (!$id) throw new \Exception("معرف المستودع غير متاح.");

            $companyId = $this->getCompanyId();
            $stmt = $this->db->prepare("SELECT * FROM warehouses WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $warehouse = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$warehouse) throw new \Exception("المستودع غير موجود.");

            // حساب الإحصائيات من حركات المخزون بمرونة
            $totalValue = 0.0;
            $itemsCount = 0;
            try {
                $stmtValue = $this->db->prepare("
                    SELECT COALESCE(SUM(sm.quantity * COALESCE(p.purchase_price, 0)), 0) as total_value, 
                           COUNT(DISTINCT sm.product_id) as items_count 
                    FROM stock_movements sm 
                    LEFT JOIN products p ON sm.product_id = p.id 
                    WHERE sm.warehouse_id = ?
                ");
                $stmtValue->execute([$id]);
                $res = $stmtValue->fetch(PDO::FETCH_OBJ);
                if ($res) {
                    $totalValue = (float)($res->total_value ?? 0);
                    $itemsCount = (int)($res->items_count ?? 0);
                }
            } catch (Throwable $e) {
                $totalValue = 0.0;
                $itemsCount = 0;
            }

            $warehouse->stats = (object)[
                'total_value' => $totalValue,
                'items_count' => $itemsCount
            ];

            ob_start(); 
            $viewPath = $this->basePath . '/resources/views/inventory/warehouses/show.php';
            if (!file_exists($viewPath)) throw new \Exception("ملف الواجهة مفقود: " . $viewPath);
            include $viewPath;
            $content = ob_get_clean();
            
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/inventory/warehouses");
            exit;
        }
    }
}