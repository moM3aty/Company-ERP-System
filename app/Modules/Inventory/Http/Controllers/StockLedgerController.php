<?php
// Path: app/Modules/Inventory/Http/Controllers/StockLedgerController.php

namespace App\Modules\Inventory\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use PDO;
use Throwable;

class StockLedgerController extends Controller
{
    // إزالة التنميط (Typed Properties) للتوافق التام مع PHP 7.2
    private $basePath;
    private $db;

    public function __construct()
    {
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = isset($basePath) ? $basePath : dirname(__DIR__, 4);
        
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            if ($this->db) {
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        }
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
        $search = trim($_GET['search'] ?? '');
        $productId = !empty($_GET['product_id']) ? (int)$_GET['product_id'] : null;
        $warehouseId = !empty($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : null;
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condSM = $this->buildBranchCond('sm', $branchId, 'stock_movements');
            $condP  = $this->buildBranchCond('p', $branchId, 'products');
            $condW  = $this->buildBranchCond('w', $branchId, 'warehouses');

            $where = ["sm.company_id = ?"];
            $params = [$companyId];

            if ($search !== '') {
                $where[] = "(p.name_ar LIKE ? OR p.name_en LIKE ? OR p.item_code LIKE ? OR sm.reference_number LIKE ? OR w.name_ar LIKE ? OR w.name_en LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like, $like, $like, $like);
            }

            if ($productId) {
                $where[] = "sm.product_id = ?";
                $params[] = $productId;
            }

            if ($warehouseId) {
                $where[] = "sm.warehouse_id = ?";
                $params[] = $warehouseId;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM stock_movements sm
                LEFT JOIN products p ON sm.product_id = p.id
                LEFT JOIN warehouses w ON sm.warehouse_id = w.id
                $whereSql $condSM
            ");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT sm.*, 
                       COALESCE(p.name_ar, '---') as product_name_ar,
                       COALESCE(p.name_en, '---') as product_name_en,
                       p.item_code as product_code, p.unit,
                       COALESCE(w.name_ar, '---') as warehouse_name_ar,
                       COALESCE(w.name_en, '---') as warehouse_name_en,
                       w.code as warehouse_code
                FROM stock_movements sm
                LEFT JOIN products p ON sm.product_id = p.id
                LEFT JOIN warehouses w ON sm.warehouse_id = w.id
                $whereSql $condSM
                ORDER BY sm.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $movements = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total_ops, 
                    SUM(IF(movement_type='in', quantity, 0)) as total_in, 
                    SUM(IF(movement_type='out', quantity, 0)) as total_out 
                FROM stock_movements sm WHERE sm.company_id = $companyId $condSM
            ")->fetch(PDO::FETCH_OBJ);

            $products = $this->db->query("SELECT id, name_ar, name_en, item_code FROM products p WHERE company_id = $companyId AND is_active = 1 $condP ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
            $warehouses = $this->db->query("SELECT id, name_ar, name_en, code FROM warehouses w WHERE company_id = $companyId AND is_active = 1 $condW ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $movements = [];
            $products = [];
            $warehouses = [];
            $stats = (object)['total_ops'=>0, 'total_in'=>0, 'total_out'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/inventory/stock_ledger/index.php';
        $content = ob_get_clean();
        
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}