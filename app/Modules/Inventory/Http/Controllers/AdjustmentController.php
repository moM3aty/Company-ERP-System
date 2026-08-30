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
    private string $basePath;
    private PDO $db;

    public function __construct()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    private function resolveId($id = null): ?int
    {
        if (!empty($id) && is_numeric($id)) return (int)$id;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/adjustments/(\d+)#', $uri, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    public function index(Request $request, Response $response): Response
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
            $whereClause = "";
            $params = [];
            if ($search !== '') {
                $whereClause = "WHERE sa.adjustment_number LIKE ? OR sa.reason LIKE ? OR w.name_ar LIKE ?";
                $like = "%{$search}%";
                $params = [$like, $like, $like];
            }

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM stock_adjustments sa
                LEFT JOIN warehouses w ON sa.warehouse_id = w.id
                $whereClause
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
                $whereClause
                ORDER BY sa.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $adjustments = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total, 
                    SUM(IF(adjustment_type='addition', 1, 0)) as addition_count, 
                    SUM(IF(adjustment_type='subtraction', 1, 0)) as subtraction_count 
                FROM stock_adjustments
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

    public function create(Request $request, Response $response): Response
    {
        $adj = null; $items = []; $warehouses = []; $products = [];
        try {
            $warehouses = $this->db->query("SELECT id, name_ar, code FROM warehouses WHERE is_active = 1 ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, name_ar, purchase_price FROM products WHERE is_active = 1 ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); 
        include $this->basePath . '/resources/views/inventory/adjustments/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

  public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (empty($data['warehouse_id'])) throw new Exception("يجب تحديد المستودع.");

            $this->db->beginTransaction();
            $companyId = $_SESSION['company_id'] ?? 1;
            $adjNum = !empty($data['adjustment_number']) ? trim($data['adjustment_number']) : 'ADJ-' . date('ymd') . rand(10, 99);
            $status = $data['status'] ?? 'draft';
            $adjType = $data['adjustment_type'] ?? 'addition';

            $stmt = $this->db->prepare("INSERT INTO stock_adjustments (company_id, adjustment_number, warehouse_id, adjustment_type, adjustment_date, status, reason, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$companyId, $adjNum, $data['warehouse_id'], $adjType, $data['adjustment_date'], $status, $data['reason'] ?? null, $data['notes'] ?? null]);
            $adjId = $this->db->lastInsertId();

            $stmtItem = $this->db->prepare("INSERT INTO stock_adjustment_items (adjustment_id, product_id, quantity, unit_cost) VALUES (?, ?, ?, ?)");
            $stmtMov = $this->db->prepare("INSERT INTO stock_movements (company_id, product_id, warehouse_id, movement_type, reference_type, reference_number, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");

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
                        $stmtMov->execute([$companyId, $prodId, $data['warehouse_id'], $movType, $refType, $adjNum, $qty]);
                    }
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = "تم إنشاء إذن التسوية بنجاح.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/inventory/adjustments/create');
        }

        return new RedirectResponse('/ERP/inventory/adjustments');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        try {
            $this->db->beginTransaction();
            $companyId = $_SESSION['company_id'] ?? 1;

            $stmtNum = $this->db->prepare("SELECT adjustment_number FROM stock_adjustments WHERE id = ?");
            $stmtNum->execute([$id]);
            $adjNum = $stmtNum->fetchColumn();

            $status = $data['status'] ?? 'draft';
            $adjType = $data['adjustment_type'] ?? 'addition';

            $stmt = $this->db->prepare("UPDATE stock_adjustments SET warehouse_id=?, adjustment_type=?, adjustment_date=?, status=?, reason=?, notes=? WHERE id=? AND status != 'approved'");
            $stmt->execute([$data['warehouse_id'], $adjType, $data['adjustment_date'], $status, $data['reason'] ?? null, $data['notes'] ?? null, $id]);

            $this->db->prepare("DELETE FROM stock_adjustment_items WHERE adjustment_id = ?")->execute([$id]);

            $stmtItem = $this->db->prepare("INSERT INTO stock_adjustment_items (adjustment_id, product_id, quantity, unit_cost) VALUES (?, ?, ?, ?)");
            $stmtMov = $this->db->prepare("INSERT INTO stock_movements (company_id, product_id, warehouse_id, movement_type, reference_type, reference_number, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");

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
                        $stmtMov->execute([$companyId, $prodId, $data['warehouse_id'], $movType, $refType, $adjNum, $qty]);
                    }
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = "تم تحديث أمر التسوية بنجاح.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/inventory/adjustments/{$id}/edit");
        }

        return new RedirectResponse('/ERP/inventory/adjustments');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        
        try {
            if (!$id) throw new Exception("معرف التسوية غير متاح.");

            $stmt = $this->db->prepare("SELECT * FROM stock_adjustments WHERE id = ?");
            $stmt->execute([$id]);
            $adj = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$adj) throw new Exception("إذن التسوية غير موجود بقاعدة البيانات.");
            if ($adj->status === 'approved') {
                throw new Exception("لا يمكن تعديل تسوية مخزنية معتمدة ومرحلة.");
            }

            $stmtItems = $this->db->prepare("SELECT * FROM stock_adjustment_items WHERE adjustment_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $warehouses = $this->db->query("SELECT id, name_ar, code FROM warehouses WHERE is_active = 1 ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, name_ar, purchase_price FROM products WHERE is_active = 1 ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/inventory/adjustments');
        }

        ob_start(); 
        include $this->basePath . '/resources/views/inventory/adjustments/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

   

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        try {
            if (!$id) throw new Exception("المعرف غير صالح.");

            $stmt = $this->db->prepare("SELECT status FROM stock_adjustments WHERE id = ?");
            $stmt->execute([$id]);
            $status = $stmt->fetchColumn();

            if ($status === 'approved') {
                throw new Exception("لا يمكن حذف تسوية مخزنية تمت الموافقة عليها واعتمادها.");
            }

            $this->db->prepare("DELETE FROM stock_adjustments WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف إذن التسوية بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse('/ERP/inventory/adjustments');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        
        try {
            if (!$id) throw new Exception("المعرف غير متاح.");

            $stmt = $this->db->prepare("
                SELECT sa.*, 
                       COALESCE(w.name_ar, '---') as warehouse_name, COALESCE(w.code, '---') as warehouse_code
                FROM stock_adjustments sa
                LEFT JOIN warehouses w ON sa.warehouse_id = w.id
                WHERE sa.id = ?
            ");
            $stmt->execute([$id]);
            $adj = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$adj) throw new Exception("إذن التسوية غير موجود.");

            $stmtItems = $this->db->prepare("
                SELECT i.*, COALESCE(p.name_ar, 'صنف غير معروف') as product_name, COALESCE(p.item_code, '---') as product_code, COALESCE(p.unit, 'قطعة') as unit
                FROM stock_adjustment_items i 
                LEFT JOIN products p ON i.product_id = p.id 
                WHERE i.adjustment_id = ?
            ");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/inventory/adjustments');
        }

        ob_start(); 
        include $this->basePath . '/resources/views/inventory/adjustments/show.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}