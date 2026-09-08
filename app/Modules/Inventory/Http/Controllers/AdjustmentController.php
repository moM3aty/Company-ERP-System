<?php
// Path: app/Modules/Inventory/Http/Controllers/AdjustmentController.php

namespace App\Modules\Inventory\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class AdjustmentController extends Controller
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

    private function resolveId($id = null)
    {
        if (!empty($id) && is_numeric($id)) return (int)$id;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/adjustments/(\d+)#', $uri, $matches)) {
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
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        if (preg_match('#/adjustments/(\d+)/edit#', $uri, $m)) {
            return $this->edit($request, $response, (int)$m[1]);
        }
        if (preg_match('#/adjustments/(\d+)/update#', $uri, $m)) {
            return $this->update($request, $response, (int)$m[1]);
        }
        if (preg_match('#/adjustments/(\d+)/delete#', $uri, $m)) {
            return $this->delete($request, $response, (int)$m[1]);
        }
        if (preg_match('#/adjustments/(\d+)$#', $uri, $m)) {
            return $this->show($request, $response, (int)$m[1]);
        }

        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condSA = $this->buildBranchCond('sa', $branchId, 'stock_adjustments');

            $whereClause = "WHERE sa.company_id = ?";
            $params = [$companyId];

            if ($search !== '') {
                $whereClause .= " AND (sa.adjustment_number LIKE ? OR sa.reason LIKE ? OR w.name_ar LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like);
            }

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM stock_adjustments sa
                LEFT JOIN warehouses w ON sa.warehouse_id = w.id
                $whereClause $condSA
            ");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT sa.*, 
                       COALESCE(w.name_ar, '---') as warehouse_name,
                       (SELECT COUNT(id) FROM stock_adjustment_items WHERE adjustment_id = sa.id) as items_count
                FROM stock_adjustments sa
                LEFT JOIN warehouses w ON sa.warehouse_id = w.id
                $whereClause $condSA
                ORDER BY sa.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $adjustments = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total, 
                    SUM(IF(adjustment_type='addition', 1, 0)) as addition_count, 
                    SUM(IF(adjustment_type='subtraction', 1, 0)) as subtraction_count 
                FROM stock_adjustments sa WHERE company_id = $companyId $condSA
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $adjustments = [];
            $stats = (object)['total'=>0, 'addition_count'=>0, 'subtraction_count'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/inventory/adjustments/index.php';
        $content = ob_get_clean();
        
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response)
    {
        $adj = null; $items = []; $warehouses = []; $products = [];
        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condW = $this->buildBranchCond('', $branchId, 'warehouses');
            $condP = $this->buildBranchCond('', $branchId, 'products');

            $warehouses = $this->db->query("SELECT id, name_ar, code FROM warehouses WHERE company_id = $companyId AND is_active = 1 $condW ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, name_ar, purchase_price FROM products WHERE company_id = $companyId AND is_active = 1 $condP ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); 
        include $this->basePath . '/resources/views/inventory/adjustments/create.php';
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
            if (empty($data['warehouse_id'])) {
                throw new Exception($isAr ? "يجب تحديد المستودع." : "Warehouse is required.");
            }

            $this->db->beginTransaction();

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();
            $adjNum = !empty($data['adjustment_number']) ? trim($data['adjustment_number']) : 'ADJ-' . date('ymd') . rand(10, 99);
            $status = $data['status'] ?? 'draft';
            $adjType = $data['adjustment_type'] ?? 'addition';

            $hasBranchSA = $this->hasBranchColumn('stock_adjustments');
            $branchCol = $hasBranchSA ? ", branch_id" : "";
            $branchVal = $hasBranchSA ? ", ?" : "";

            $stmt = $this->db->prepare("
                INSERT INTO stock_adjustments (company_id, adjustment_number, warehouse_id, adjustment_type, adjustment_date, status, reason, notes {$branchCol}) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ? {$branchVal})
            ");
            
            $params = [
                $companyId, $adjNum, $data['warehouse_id'], $adjType, 
                $data['adjustment_date'], $status, $data['reason'] ?? null, $data['notes'] ?? null
            ];
            if ($hasBranchSA) $params[] = $branchId;

            $stmt->execute($params);
            $adjId = $this->db->lastInsertId();

            $stmtItem = $this->db->prepare("INSERT INTO stock_adjustment_items (adjustment_id, product_id, quantity, unit_cost) VALUES (?, ?, ?, ?)");
            
            $hasBranchSM = $this->hasBranchColumn('stock_movements');
            $branchColSM = $hasBranchSM ? ", branch_id" : "";
            $branchValSM = $hasBranchSM ? ", ?" : "";

            $stmtMov = $this->db->prepare("
                INSERT INTO stock_movements (company_id, product_id, warehouse_id, movement_type, reference_type, reference_number, quantity {$branchColSM}) 
                VALUES (?, ?, ?, ?, ?, ?, ? {$branchValSM})
            ");

            if (!empty($data['product_id']) && is_array($data['product_id'])) {
                foreach ($data['product_id'] as $idx => $prodId) {
                    if (empty($prodId)) continue;
                    $qty = (float)($data['quantity'][$idx] ?? 1);
                    $cost = (float)($data['unit_cost'][$idx] ?? 0);
                    if ($qty <= 0) continue;
                    
                    $stmtItem->execute([$adjId, $prodId, $qty, $cost]);

                    if ($status === 'approved') {
                        $movType = ($adjType === 'addition') ? 'in' : 'out';
                        $refType = ($adjType === 'addition') ? 'adjustment_add' : 'adjustment_sub';
                        
                        $pMov = [$companyId, $prodId, $data['warehouse_id'], $movType, $refType, $adjNum, $qty];
                        if ($hasBranchSM) $pMov[] = $branchId;
                        $stmtMov->execute($pMov);
                    }
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم إنشاء إذن التسوية بنجاح." : "Stock adjustment created successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            header("Location: /ERP/inventory/adjustments/create");
            exit;
        }

        header("Location: /ERP/inventory/adjustments");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        
        try {
            if (!$id) throw new Exception("معرف التسوية غير متاح.");

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $stmt = $this->db->prepare("SELECT * FROM stock_adjustments WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $adj = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$adj) throw new Exception("إذن التسوية غير موجود بقاعدة البيانات.");
            if ($adj->status === 'approved') {
                throw new Exception("لا يمكن تعديل تسوية مخزنية معتمدة ومرحلة.");
            }

            $stmtItems = $this->db->prepare("SELECT * FROM stock_adjustment_items WHERE adjustment_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $condW = $this->buildBranchCond('', $branchId, 'warehouses');
            $condP = $this->buildBranchCond('', $branchId, 'products');

            $warehouses = $this->db->query("SELECT id, name_ar, code FROM warehouses WHERE company_id = $companyId AND is_active = 1 $condW ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, name_ar, purchase_price FROM products WHERE company_id = $companyId AND is_active = 1 $condP ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/inventory/adjustments");
            exit;
        }

        ob_start(); 
        include $this->basePath . '/resources/views/inventory/adjustments/create.php';
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
            if (!$id) throw new Exception("معرف التسوية غير متاح.");

            $this->db->beginTransaction();
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $stmtNum = $this->db->prepare("SELECT adjustment_number FROM stock_adjustments WHERE id = ?");
            $stmtNum->execute([$id]);
            $adjNum = $stmtNum->fetchColumn();

            $status = $data['status'] ?? 'draft';
            $adjType = $data['adjustment_type'] ?? 'addition';

            $stmt = $this->db->prepare("
                UPDATE stock_adjustments 
                SET warehouse_id=?, adjustment_type=?, adjustment_date=?, status=?, reason=?, notes=? 
                WHERE id=? AND company_id=? AND status != 'approved'
            ");
            $stmt->execute([
                $data['warehouse_id'], $adjType, $data['adjustment_date'], 
                $status, $data['reason'] ?? null, $data['notes'] ?? null, $id, $companyId
            ]);

            $this->db->prepare("DELETE FROM stock_adjustment_items WHERE adjustment_id = ?")->execute([$id]);

            $stmtItem = $this->db->prepare("INSERT INTO stock_adjustment_items (adjustment_id, product_id, quantity, unit_cost) VALUES (?, ?, ?, ?)");
            
            $hasBranchSM = $this->hasBranchColumn('stock_movements');
            $branchColSM = $hasBranchSM ? ", branch_id" : "";
            $branchValSM = $hasBranchSM ? ", ?" : "";

            $stmtMov = $this->db->prepare("
                INSERT INTO stock_movements (company_id, product_id, warehouse_id, movement_type, reference_type, reference_number, quantity {$branchColSM}) 
                VALUES (?, ?, ?, ?, ?, ?, ? {$branchValSM})
            ");

            if (!empty($data['product_id']) && is_array($data['product_id'])) {
                foreach ($data['product_id'] as $idx => $prodId) {
                    if (empty($prodId)) continue;
                    $qty = (float)($data['quantity'][$idx] ?? 1);
                    $cost = (float)($data['unit_cost'][$idx] ?? 0);
                    if ($qty <= 0) continue;
                    
                    $stmtItem->execute([$id, $prodId, $qty, $cost]);

                    if ($status === 'approved') {
                        $movType = ($adjType === 'addition') ? 'in' : 'out';
                        $refType = ($adjType === 'addition') ? 'adjustment_add' : 'adjustment_sub';
                        
                        $pMov = [$companyId, $prodId, $data['warehouse_id'], $movType, $refType, $adjNum, $qty];
                        if ($hasBranchSM) $pMov[] = $branchId;
                        $stmtMov->execute($pMov);
                    }
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم تحديث أمر التسوية بنجاح." : "Stock adjustment updated successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            header("Location: /ERP/inventory/adjustments/{$id}/edit");
            exit;
        }

        header("Location: /ERP/inventory/adjustments");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $this->getCompanyId();

        try {
            if (!$id) throw new Exception("المعرف غير صالح.");

            $stmt = $this->db->prepare("SELECT status FROM stock_adjustments WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $status = $stmt->fetchColumn();

            if ($status === 'approved') {
                throw new Exception("لا يمكن حذف تسوية مخزنية تمت الموافقة عليها واعتمادها.");
            }

            $this->db->prepare("DELETE FROM stock_adjustments WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف إذن التسوية بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        
        header("Location: /ERP/inventory/adjustments");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        try {
            $id = $this->resolveId($id);
            if (!$id) throw new Exception("المعرف غير متاح.");

            $companyId = $this->getCompanyId();

            $stmt = $this->db->prepare("
                SELECT sa.*, 
                       COALESCE(w.name_ar, '---') as warehouse_name, COALESCE(w.code, '---') as warehouse_code
                FROM stock_adjustments sa
                LEFT JOIN warehouses w ON sa.warehouse_id = w.id
                WHERE sa.id = ? AND sa.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $adj = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$adj) throw new Exception("إذن التسوية غير موجود.");

            $stmtItems = $this->db->prepare("
                SELECT i.*, COALESCE(p.name_ar, 'صنف غير معروف') as product_name, COALESCE(p.item_code, '---') as product_code, COALESCE(p.unit, 'قطعة') as unit
                FROM stock_adjustment_items i 
                LEFT JOIN products p ON i.product_id = p.id 
                WHERE i.adjustment_id = ?
            ");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ) ?: [];

            ob_start(); 
            include $this->basePath . '/resources/views/inventory/adjustments/show.php';
            $content = ob_get_clean();
            
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/inventory/adjustments");
            exit;
        }
    }
}