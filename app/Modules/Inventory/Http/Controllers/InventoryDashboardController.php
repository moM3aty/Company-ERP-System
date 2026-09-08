<?php
// Path: app/Modules/Inventory/Http/Controllers/InventoryDashboardController.php

namespace App\Modules\Inventory\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use PDO;
use Throwable;

class InventoryDashboardController extends Controller
{
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

    // جلب الفرع من الـ Session الخاصة بالـ Navbar حصرياً
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

    // الفلترة الصارمة جداً (بدون استثناءات)
    private function buildBranchCond($tableAlias, $branchId, $tableName)
    {
        if ($branchId <= 0) return ""; // إذا كان 0 (كل الفروع) لا تضع شرطاً
        if (!$this->hasBranchColumn($tableName)) return "";
        $col = $tableAlias ? "{$tableAlias}.branch_id" : "branch_id";
        return " AND {$col} = {$branchId}";
    }

    private function getSafeValue($sql, $params = [], $default = 0)
    {
        if (!$this->db) return $default;
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $val = $stmt->fetchColumn();
            return $val !== false ? $val : $default;
        } catch (Throwable $e) {
            return $default;
        }
    }

    public function index(Request $request, Response $response)
    {
        try {
            if (session_status() === PHP_SESSION_NONE) session_start();
            
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId(); // فرع الـ Navbar

            $kpis = [
                'stock_value'       => 0,
                'total_products'    => 0,
                'active_warehouses' => 0,
                'low_stock_alerts'  => 0,
                'total_movements'   => 0,
                'pending_transfers' => 0
            ];

            $chartData = [
                'movements_in'  => 0,
                'movements_out' => 0,
                'ops_breakdown' => [
                    'transfers'   => 0,
                    'deliveries'  => 0,
                    'returns'     => 0,
                    'adjustments' => 0
                ]
            ];

            $recentMovements = [];
            $recentDeliveries = [];

            if ($this->db) {
                // تطبيق شروط الفرع الصارمة
                $condP  = $this->buildBranchCond('p', $branchId, 'products');
                $condW  = $this->buildBranchCond('w', $branchId, 'warehouses');
                $condSM = $this->buildBranchCond('sm', $branchId, 'stock_movements');
                $condST = $this->buildBranchCond('st', $branchId, 'stock_transfers');
                $condDN = $this->buildBranchCond('dn', $branchId, 'delivery_notes');
                $condSR = $this->buildBranchCond('sr', $branchId, 'stock_returns');
                $condSA = $this->buildBranchCond('sa', $branchId, 'stock_adjustments');

                $kpis['total_products']    = (int)$this->getSafeValue("SELECT COUNT(id) FROM products p WHERE company_id = ? AND is_active = 1 $condP", [$companyId]);
                $kpis['active_warehouses'] = (int)$this->getSafeValue("SELECT COUNT(id) FROM warehouses w WHERE company_id = ? AND is_active = 1 $condW", [$companyId]);
                $kpis['total_movements']   = (int)$this->getSafeValue("SELECT COUNT(id) FROM stock_movements sm WHERE company_id = ? $condSM", [$companyId]);
                $kpis['pending_transfers'] = (int)$this->getSafeValue("SELECT COUNT(id) FROM stock_transfers st WHERE company_id = ? AND (status = 'in_transit' OR status = 'draft') $condST", [$companyId]);
                $kpis['stock_value']       = (float)$this->getSafeValue("SELECT COALESCE(SUM(purchase_price), 0) FROM products p WHERE company_id = ? $condP", [$companyId]);

                $chartData['movements_in']  = (float)$this->getSafeValue("SELECT COALESCE(SUM(quantity), 0) FROM stock_movements sm WHERE company_id = ? AND movement_type = 'in' $condSM", [$companyId]);
                $chartData['movements_out'] = (float)$this->getSafeValue("SELECT COALESCE(SUM(quantity), 0) FROM stock_movements sm WHERE company_id = ? AND movement_type = 'out' $condSM", [$companyId]);

                $chartData['ops_breakdown']['transfers']   = (int)$this->getSafeValue("SELECT COUNT(id) FROM stock_transfers st WHERE company_id = ? $condST", [$companyId]);
                $chartData['ops_breakdown']['deliveries']  = (int)$this->getSafeValue("SELECT COUNT(id) FROM delivery_notes dn WHERE company_id = ? $condDN", [$companyId]);
                $chartData['ops_breakdown']['returns']     = (int)$this->getSafeValue("SELECT COUNT(id) FROM stock_returns sr WHERE company_id = ? $condSR", [$companyId]);
                $chartData['ops_breakdown']['adjustments'] = (int)$this->getSafeValue("SELECT COUNT(id) FROM stock_adjustments sa WHERE company_id = ? $condSA", [$companyId]);

                try {
                    $smCond = str_replace("branch_id", "sm.branch_id", $condSM);
                    $stmtMovements = $this->db->prepare("
                        SELECT sm.*, p.name_ar as product_name, p.item_code as product_code, 
                               w.name_ar as warehouse_name, b.name_ar as branch_name
                        FROM stock_movements sm
                        LEFT JOIN products p ON sm.product_id = p.id
                        LEFT JOIN warehouses w ON sm.warehouse_id = w.id
                        LEFT JOIN sys_branches b ON sm.branch_id = b.id
                        WHERE sm.company_id = ? $smCond
                        ORDER BY sm.id DESC LIMIT 5
                    ");
                    $stmtMovements->execute([$companyId]);
                    $recentMovements = $stmtMovements->fetchAll(PDO::FETCH_OBJ);
                } catch (Throwable $e) {}

                try {
                    $dnCond = str_replace("branch_id", "dn.branch_id", $condDN);
                    $stmtDeliveries = $this->db->prepare("
                        SELECT dn.*, w.name_ar as warehouse_name, b.name_ar as branch_name
                        FROM delivery_notes dn
                        LEFT JOIN warehouses w ON dn.warehouse_id = w.id
                        LEFT JOIN sys_branches b ON dn.branch_id = b.id
                        WHERE dn.company_id = ? $dnCond
                        ORDER BY dn.id DESC LIMIT 5
                    ");
                    $stmtDeliveries->execute([$companyId]);
                    $recentDeliveries = $stmtDeliveries->fetchAll(PDO::FETCH_OBJ);
                } catch (Throwable $e) {}
            }

            ob_start();
            include $this->basePath . '/resources/views/inventory/dashboard/index.php';
            $content = ob_get_clean();

            ob_start();
            include $this->basePath . '/resources/views/layouts/app.php';
            $html = ob_get_clean();

            return $response->setContent($html)->setHeader('Content-Type', 'text/html; charset=UTF-8');

        } catch (Throwable $e) {
            die("<div style='padding:20px; background:#fef2f2; color:#dc2626;' dir='ltr'><h2>🚨 Dashboard Error:</h2>" . $e->getMessage() . " <br>Line: " . $e->getLine() . "</div>");
        }
    }
}