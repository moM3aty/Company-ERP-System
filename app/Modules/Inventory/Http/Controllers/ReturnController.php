<?php
// Path: app/Modules/Inventory/Http/Controllers/ReturnController.php

namespace App\Modules\Inventory\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class ReturnController extends Controller
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
        if (preg_match('#/returns/(\d+)#', $uri, $matches)) {
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
        if (preg_match('#/returns/(\d+)/edit#', $uri, $m)) {
            return $this->edit($request, $response, (int)$m[1]);
        }
        if (preg_match('#/returns/(\d+)/update#', $uri, $m)) {
            return $this->update($request, $response, (int)$m[1]);
        }
        if (preg_match('#/returns/(\d+)/delete#', $uri, $m)) {
            return $this->delete($request, $response, (int)$m[1]);
        }
        if (preg_match('#/returns/(\d+)$#', $uri, $m)) {
            return $this->show($request, $response, (int)$m[1]);
        }

        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condSR = $this->buildBranchCond('sr', $branchId, 'stock_returns');

            $whereClause = "WHERE sr.company_id = ?";
            $params = [$companyId];

            if ($search !== '') {
                $whereClause .= " AND (sr.return_number LIKE ? OR sr.party_name LIKE ? OR w.name_ar LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like);
            }

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM stock_returns sr
                LEFT JOIN warehouses w ON sr.warehouse_id = w.id
                $whereClause $condSR
            ");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT sr.*, 
                       COALESCE(w.name_ar, '---') as warehouse_name,
                       (SELECT COUNT(id) FROM stock_return_items WHERE return_id = sr.id) as items_count
                FROM stock_returns sr
                LEFT JOIN warehouses w ON sr.warehouse_id = w.id
                $whereClause $condSR
                ORDER BY sr.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $returns = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total, 
                    SUM(IF(return_type='sales_return', 1, 0)) as sales_ret, 
                    SUM(IF(return_type='purchase_return', 1, 0)) as purchase_ret 
                FROM stock_returns sr WHERE company_id = $companyId $condSR
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $returns = [];
            $stats = (object)['total'=>0, 'sales_ret'=>0, 'purchase_ret'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/inventory/returns/index.php';
        $content = ob_get_clean();
        
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response)
    {
        $ret = null; $items = []; $warehouses = []; $products = [];
        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condW = $this->buildBranchCond('', $branchId, 'warehouses');
            $condP = $this->buildBranchCond('', $branchId, 'products');

            $warehouses = $this->db->query("SELECT id, name_ar, code FROM warehouses WHERE company_id = $companyId AND is_active = 1 $condW ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, name_ar FROM products WHERE company_id = $companyId AND is_active = 1 $condP ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); 
        include $this->basePath . '/resources/views/inventory/returns/create.php';
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
            if (empty($data['warehouse_id']) || empty($data['party_name'])) {
                throw new Exception($isAr ? "بيانات ناقصة. الرجاء تعبئة الحقول المطلوبة." : "Missing required fields.");
            }

            $this->db->beginTransaction();
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();
            $retNum = !empty($data['return_number']) ? trim($data['return_number']) : 'RET-' . date('ymd') . rand(10, 99);
            $status = $data['status'] ?? 'draft';
            $retType = $data['return_type'] ?? 'sales_return';

            $hasBranchSR = $this->hasBranchColumn('stock_returns');
            $branchCol = $hasBranchSR ? ", branch_id" : "";
            $branchVal = $hasBranchSR ? ", ?" : "";

            $stmt = $this->db->prepare("
                INSERT INTO stock_returns (company_id, return_number, return_type, warehouse_id, party_name, return_date, status, notes {$branchCol}) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ? {$branchVal})
            ");
            
            $params = [
                $companyId, $retNum, $retType, $data['warehouse_id'], 
                trim($data['party_name']), $data['return_date'], $status, $data['notes'] ?? null
            ];
            if ($hasBranchSR) $params[] = $branchId;

            $stmt->execute($params);
            $returnId = $this->db->lastInsertId();

            $stmtItem = $this->db->prepare("INSERT INTO stock_return_items (return_id, product_id, quantity, reason) VALUES (?, ?, ?, ?)");
            
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
                    if ($qty <= 0) continue;
                    $reason = trim($data['reason'][$idx] ?? '');
                    
                    $stmtItem->execute([$returnId, $prodId, $qty, $reason]);

                    if ($status === 'approved') {
                        $movType = ($retType === 'sales_return') ? 'in' : 'out';
                        $pMov = [$companyId, $prodId, $data['warehouse_id'], $movType, $retType, $retNum, $qty];
                        if ($hasBranchSM) $pMov[] = $branchId;
                        $stmtMov->execute($pMov);
                    }
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم إنشاء إذن المرتجع بنجاح." : "Stock return created successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            header("Location: /ERP/inventory/returns/create");
            exit;
        }

        header("Location: /ERP/inventory/returns");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        
        try {
            if (!$id) throw new Exception("معرف المرتجع غير متاح.");

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $stmt = $this->db->prepare("SELECT * FROM stock_returns WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $ret = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$ret) throw new Exception("إذن المرتجع غير موجود.");
            if ($ret->status === 'approved') {
                throw new Exception("لا يمكن تعديل إذن مرتجع تم اعتماده وتسويته مخزنياً.");
            }

            $stmtItems = $this->db->prepare("SELECT * FROM stock_return_items WHERE return_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $condW = $this->buildBranchCond('', $branchId, 'warehouses');
            $condP = $this->buildBranchCond('', $branchId, 'products');

            $warehouses = $this->db->query("SELECT id, name_ar, code FROM warehouses WHERE company_id = $companyId AND is_active = 1 $condW ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, name_ar FROM products WHERE company_id = $companyId AND is_active = 1 $condP ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/inventory/returns");
            exit;
        }

        ob_start(); 
        include $this->basePath . '/resources/views/inventory/returns/create.php';
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
            if (!$id) throw new Exception("معرف المرتجع غير متاح.");

            $this->db->beginTransaction();
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $stmtNum = $this->db->prepare("SELECT return_number FROM stock_returns WHERE id = ?");
            $stmtNum->execute([$id]);
            $retNum = $stmtNum->fetchColumn();

            $status = $data['status'] ?? 'draft';
            $retType = $data['return_type'] ?? 'sales_return';

            $stmt = $this->db->prepare("
                UPDATE stock_returns 
                SET return_type=?, warehouse_id=?, party_name=?, return_date=?, status=?, notes=? 
                WHERE id=? AND company_id=? AND status != 'approved'
            ");
            $stmt->execute([$retType, $data['warehouse_id'], trim($data['party_name']), $data['return_date'], $status, $data['notes'] ?? null, $id, $companyId]);

            $this->db->prepare("DELETE FROM stock_return_items WHERE return_id = ?")->execute([$id]);

            $stmtItem = $this->db->prepare("INSERT INTO stock_return_items (return_id, product_id, quantity, reason) VALUES (?, ?, ?, ?)");
            
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
                    if ($qty <= 0) continue;
                    $reason = trim($data['reason'][$idx] ?? '');
                    
                    $stmtItem->execute([$id, $prodId, $qty, $reason]);

                    if ($status === 'approved') {
                        $movType = ($retType === 'sales_return') ? 'in' : 'out';
                        $pMov = [$companyId, $prodId, $data['warehouse_id'], $movType, $retType, $retNum, $qty];
                        if ($hasBranchSM) $pMov[] = $branchId;
                        $stmtMov->execute($pMov);
                    }
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم تحديث إذن المرتجع بنجاح." : "Stock return updated successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            header("Location: /ERP/inventory/returns/{$id}/edit");
            exit;
        }

        header("Location: /ERP/inventory/returns");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $this->getCompanyId();

        try {
            if (!$id) throw new Exception("المعرف غير صالح.");

            $stmt = $this->db->prepare("SELECT status FROM stock_returns WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $status = $stmt->fetchColumn();

            if ($status === 'approved') {
                throw new Exception("لا يمكن حذف إذن مرتجع معتمد ومرحل مخزنياً.");
            }

            $this->db->prepare("DELETE FROM stock_returns WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف إذن المرتجع بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        
        header("Location: /ERP/inventory/returns");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        try {
            $id = $this->resolveId($id);
            if (!$id) throw new Exception("المعرف غير متاح.");

            $companyId = $this->getCompanyId();

            $stmt = $this->db->prepare("
                SELECT sr.*, 
                       COALESCE(w.name_ar, '---') as warehouse_name, COALESCE(w.code, '---') as warehouse_code
                FROM stock_returns sr
                LEFT JOIN warehouses w ON sr.warehouse_id = w.id
                WHERE sr.id = ? AND sr.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $ret = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$ret) throw new Exception("إذن المرتجع غير موجود.");

            $stmtItems = $this->db->prepare("
                SELECT i.*, COALESCE(p.name_ar, 'صنف غير معروف') as product_name, COALESCE(p.item_code, '---') as product_code, COALESCE(p.unit, 'قطعة') as unit
                FROM stock_return_items i 
                LEFT JOIN products p ON i.product_id = p.id 
                WHERE i.return_id = ?
            ");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ) ?: [];

            ob_start(); 
            include $this->basePath . '/resources/views/inventory/returns/show.php';
            $content = ob_get_clean();
            
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/inventory/returns");
            exit;
        }
    }
}