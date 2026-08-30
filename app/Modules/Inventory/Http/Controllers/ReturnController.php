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
        if (preg_match('#/returns/(\d+)#', $uri, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    public function index(Request $request, Response $response): Response
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
            $whereClause = "";
            $params = [];
            if ($search !== '') {
                $whereClause = "WHERE sr.return_number LIKE ? OR sr.party_name LIKE ? OR w.name_ar LIKE ?";
                $like = "%{$search}%";
                $params = [$like, $like, $like];
            }

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM stock_returns sr
                LEFT JOIN warehouses w ON sr.warehouse_id = w.id
                $whereClause
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
                $whereClause
                ORDER BY sr.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $returns = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total, 
                    SUM(IF(return_type='sales_return', 1, 0)) as sales_ret, 
                    SUM(IF(return_type='purchase_return', 1, 0)) as purchase_ret 
                FROM stock_returns
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

    public function create(Request $request, Response $response): Response
    {
        $ret = null; $items = []; $warehouses = []; $products = [];
        try {
            $warehouses = $this->db->query("SELECT id, name_ar, code FROM warehouses WHERE is_active = 1 ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, name_ar FROM products WHERE is_active = 1 ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); 
        include $this->basePath . '/resources/views/inventory/returns/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (empty($data['warehouse_id']) || empty($data['party_name'])) throw new Exception("بيانات ناقصة.");

            $this->db->beginTransaction();
            $companyId = $_SESSION['company_id'] ?? 1;
            $retNum = !empty($data['return_number']) ? trim($data['return_number']) : 'RET-' . date('ymd') . rand(10, 99);
            $status = $data['status'] ?? 'draft';
            $retType = $data['return_type'] ?? 'sales_return';

            $stmt = $this->db->prepare("INSERT INTO stock_returns (company_id, return_number, return_type, warehouse_id, party_name, return_date, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$companyId, $retNum, $retType, $data['warehouse_id'], trim($data['party_name']), $data['return_date'], $status, $data['notes'] ?? null]);
            $returnId = $this->db->lastInsertId();

            $stmtItem = $this->db->prepare("INSERT INTO stock_return_items (return_id, product_id, quantity, reason) VALUES (?, ?, ?, ?)");
            $stmtMov = $this->db->prepare("INSERT INTO stock_movements (company_id, product_id, warehouse_id, movement_type, reference_type, reference_number, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");

            if (!empty($data['product_id']) && is_array($data['product_id'])) {
                foreach ($data['product_id'] as $idx => $prodId) {
                    if (empty($prodId)) continue;
                    $qty = (float)($data['quantity'][$idx] ?? 1);
                    if ($qty <= 0) continue;
                    $reason = trim($data['reason'][$idx] ?? '');
                    
                    $stmtItem->execute([$returnId, $prodId, $qty, $reason]);

                    if ($status === 'approved') {
                        $movType = ($retType === 'sales_return') ? 'in' : 'out';
                        $stmtMov->execute([$companyId, $prodId, $data['warehouse_id'], $movType, $retType, $retNum, $qty]);
                    }
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = "تم إنشاء إذن المرتجع بنجاح.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/inventory/returns/create');
        }

        return new RedirectResponse('/ERP/inventory/returns');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        
        try {
            if (!$id) throw new Exception("معرف المرتجع غير متاح.");

            $stmt = $this->db->prepare("SELECT * FROM stock_returns WHERE id = ?");
            $stmt->execute([$id]);
            $ret = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$ret) throw new Exception("إذن المرتجع غير موجود.");
            if ($ret->status === 'approved') {
                throw new Exception("لا يمكن تعديل إذن مرتجع تم اعتماده وتسويته مخزنياً.");
            }

            $stmtItems = $this->db->prepare("SELECT * FROM stock_return_items WHERE return_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $warehouses = $this->db->query("SELECT id, name_ar, code FROM warehouses WHERE is_active = 1 ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, name_ar FROM products WHERE is_active = 1 ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/inventory/returns');
        }

        ob_start(); 
        include $this->basePath . '/resources/views/inventory/returns/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        try {
            $this->db->beginTransaction();
            $companyId = $_SESSION['company_id'] ?? 1;

            $stmtNum = $this->db->prepare("SELECT return_number FROM stock_returns WHERE id = ?");
            $stmtNum->execute([$id]);
            $retNum = $stmtNum->fetchColumn();

            $status = $data['status'] ?? 'draft';
            $retType = $data['return_type'] ?? 'sales_return';

            $stmt = $this->db->prepare("UPDATE stock_returns SET return_type=?, warehouse_id=?, party_name=?, return_date=?, status=?, notes=? WHERE id=? AND status != 'approved'");
            $stmt->execute([$retType, $data['warehouse_id'], trim($data['party_name']), $data['return_date'], $status, $data['notes'] ?? null, $id]);

            $this->db->prepare("DELETE FROM stock_return_items WHERE return_id = ?")->execute([$id]);

            $stmtItem = $this->db->prepare("INSERT INTO stock_return_items (return_id, product_id, quantity, reason) VALUES (?, ?, ?, ?)");
            $stmtMov = $this->db->prepare("INSERT INTO stock_movements (company_id, product_id, warehouse_id, movement_type, reference_type, reference_number, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");

            if (!empty($data['product_id']) && is_array($data['product_id'])) {
                foreach ($data['product_id'] as $idx => $prodId) {
                    if (empty($prodId)) continue;
                    $qty = (float)($data['quantity'][$idx] ?? 1);
                    if ($qty <= 0) continue;
                    $reason = trim($data['reason'][$idx] ?? '');
                    
                    $stmtItem->execute([$id, $prodId, $qty, $reason]);

                    if ($status === 'approved') {
                        $movType = ($retType === 'sales_return') ? 'in' : 'out';
                        $stmtMov->execute([$companyId, $prodId, $data['warehouse_id'], $movType, $retType, $retNum, $qty]);
                    }
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = "تم تحديث إذن المرتجع بنجاح.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/inventory/returns/{$id}/edit");
        }

        return new RedirectResponse('/ERP/inventory/returns');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        try {
            if (!$id) throw new Exception("المعرف غير صالح.");

            $stmt = $this->db->prepare("SELECT status FROM stock_returns WHERE id = ?");
            $stmt->execute([$id]);
            $status = $stmt->fetchColumn();

            if ($status === 'approved') {
                throw new Exception("لا يمكن حذف إذن مرتجع معتمد ومرحل مخزنياً.");
            }

            $this->db->prepare("DELETE FROM stock_returns WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف إذن المرتجع بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse('/ERP/inventory/returns');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        
        try {
            if (!$id) throw new Exception("المعرف غير متاح.");

            $stmt = $this->db->prepare("
                SELECT sr.*, 
                       COALESCE(w.name_ar, '---') as warehouse_name, COALESCE(w.code, '---') as warehouse_code
                FROM stock_returns sr
                LEFT JOIN warehouses w ON sr.warehouse_id = w.id
                WHERE sr.id = ?
            ");
            $stmt->execute([$id]);
            $ret = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$ret) throw new Exception("إذن المرتجع غير موجود.");

            $stmtItems = $this->db->prepare("
                SELECT i.*, COALESCE(p.name_ar, 'صنف غير معروف') as product_name, COALESCE(p.item_code, '---') as product_code, COALESCE(p.unit, 'قطعة') as unit
                FROM stock_return_items i 
                LEFT JOIN products p ON i.product_id = p.id 
                WHERE i.return_id = ?
            ");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/inventory/returns');
        }

        ob_start(); 
        include $this->basePath . '/resources/views/inventory/returns/show.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}