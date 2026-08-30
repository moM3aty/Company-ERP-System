<?php
// Path: app/Modules/Inventory/Http/Controllers/TransferController.php

namespace App\Modules\Inventory\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class TransferController extends Controller
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
        if (preg_match('#/transfers/(\d+)#', $uri, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/transfers/(\d+)/edit#', $uri, $m)) {
            return $this->edit($request, $response, (int)$m[1]);
        }
        if (preg_match('#/transfers/(\d+)/update#', $uri, $m)) {
            return $this->update($request, $response, (int)$m[1]);
        }
        if (preg_match('#/transfers/(\d+)/delete#', $uri, $m)) {
            return $this->delete($request, $response, (int)$m[1]);
        }
        if (preg_match('#/transfers/(\d+)$#', $uri, $m)) {
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
                $whereClause = "WHERE st.transfer_number LIKE ? OR w1.name_ar LIKE ? OR w2.name_ar LIKE ?";
                $like = "%{$search}%";
                $params = [$like, $like, $like];
            }

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM stock_transfers st
                LEFT JOIN warehouses w1 ON st.from_warehouse_id = w1.id
                LEFT JOIN warehouses w2 ON st.to_warehouse_id = w2.id
                $whereClause
            ");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT st.*, 
                       COALESCE(w1.name_ar, '---') as from_wh_name, 
                       COALESCE(w2.name_ar, '---') as to_wh_name,
                       (SELECT COUNT(id) FROM stock_transfer_items WHERE transfer_id = st.id) as items_count
                FROM stock_transfers st
                LEFT JOIN warehouses w1 ON st.from_warehouse_id = w1.id
                LEFT JOIN warehouses w2 ON st.to_warehouse_id = w2.id
                $whereClause
                ORDER BY st.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $transfers = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total, 
                    SUM(IF(status='in_transit', 1, 0)) as in_transit, 
                    SUM(IF(status='completed', 1, 0)) as completed 
                FROM stock_transfers
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $transfers = [];
            $stats = (object)['total'=>0, 'in_transit'=>0, 'completed'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/inventory/transfers/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $transfer = null; $items = []; $warehouses = []; $products = [];
        try {
            $warehouses = $this->db->query("SELECT id, name_ar, code FROM warehouses WHERE is_active = 1 ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, name_ar FROM products WHERE is_active = 1 ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); 
        include $this->basePath . '/resources/views/inventory/transfers/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (empty($data['from_warehouse_id']) || empty($data['to_warehouse_id'])) {
                throw new Exception("يجب تحديد مستودع المصدر ومستودع الوجهة.");
            }
            if ($data['from_warehouse_id'] == $data['to_warehouse_id']) {
                throw new Exception("لا يمكن التحويل لنفس المستودع!");
            }

            $this->db->beginTransaction();

            $companyId = $_SESSION['company_id'] ?? 1;
            $transferNum = !empty($data['transfer_number']) ? trim($data['transfer_number']) : 'TRN-' . date('ymd') . rand(10, 99);
            $status = $data['status'] ?? 'draft';

            $stmt = $this->db->prepare("
                INSERT INTO stock_transfers (company_id, transfer_number, from_warehouse_id, to_warehouse_id, transfer_date, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, $transferNum, $data['from_warehouse_id'], $data['to_warehouse_id'],
                $data['transfer_date'], $status, $data['notes'] ?? null
            ]);
            $transferId = $this->db->lastInsertId();

            $stmtItem = $this->db->prepare("INSERT INTO stock_transfer_items (transfer_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmtMov = $this->db->prepare("INSERT INTO stock_movements (company_id, product_id, warehouse_id, movement_type, reference_type, reference_number, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");

            if (!empty($data['product_id']) && is_array($data['product_id'])) {
                foreach ($data['product_id'] as $idx => $prodId) {
                    if (empty($prodId)) continue;
                    $qty = (float)($data['quantity'][$idx] ?? 1);
                    if ($qty <= 0) continue;
                    
                    $stmtItem->execute([$transferId, $prodId, $qty]);

                    if ($status === 'completed') {
                        $stmtMov->execute([$companyId, $prodId, $data['from_warehouse_id'], 'out', 'transfer_out', $transferNum, $qty]);
                        $stmtMov->execute([$companyId, $prodId, $data['to_warehouse_id'], 'in', 'transfer_in', $transferNum, $qty]);
                    }
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = "تم إنشاء أمر التحويل بنجاح.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/inventory/stock/transfers/create');
        }

        return new RedirectResponse('/ERP/inventory/stock/transfers');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        
        try {
            if (!$id) throw new Exception("معرف أمر التحويل غير متاح.");

            $stmt = $this->db->prepare("SELECT * FROM stock_transfers WHERE id = ?");
            $stmt->execute([$id]);
            $transfer = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$transfer) throw new Exception("أمر التحويل غير موجود بقاعدة البيانات.");
            if ($transfer->status === 'completed') {
                throw new Exception("لا يمكن تعديل أمر تحويل مكتمل وتم استلامه.");
            }

            $stmtItems = $this->db->prepare("SELECT * FROM stock_transfer_items WHERE transfer_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $warehouses = $this->db->query("SELECT id, name_ar, code FROM warehouses WHERE is_active = 1 ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, name_ar FROM products WHERE is_active = 1 ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/inventory/stock/transfers');
        }

        ob_start(); 
        include $this->basePath . '/resources/views/inventory/transfers/create.php';
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
            if (!$id) throw new Exception("معرف أمر التحويل مفقود.");

            $this->db->beginTransaction();
            $companyId = $_SESSION['company_id'] ?? 1;

            $stmtNum = $this->db->prepare("SELECT transfer_number FROM stock_transfers WHERE id = ?");
            $stmtNum->execute([$id]);
            $transferNum = $stmtNum->fetchColumn();

            $status = $data['status'] ?? 'draft';

            $stmt = $this->db->prepare("
                UPDATE stock_transfers 
                SET from_warehouse_id=?, to_warehouse_id=?, transfer_date=?, status=?, notes=?
                WHERE id=? AND status != 'completed'
            ");
            $stmt->execute([
                $data['from_warehouse_id'], $data['to_warehouse_id'],
                $data['transfer_date'], $status, $data['notes'] ?? null, $id
            ]);

            $this->db->prepare("DELETE FROM stock_transfer_items WHERE transfer_id = ?")->execute([$id]);

            $stmtItem = $this->db->prepare("INSERT INTO stock_transfer_items (transfer_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmtMov = $this->db->prepare("INSERT INTO stock_movements (company_id, product_id, warehouse_id, movement_type, reference_type, reference_number, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");

            if (!empty($data['product_id']) && is_array($data['product_id'])) {
                foreach ($data['product_id'] as $idx => $prodId) {
                    if (empty($prodId)) continue;
                    $qty = (float)($data['quantity'][$idx] ?? 1);
                    if ($qty <= 0) continue;
                    
                    $stmtItem->execute([$id, $prodId, $qty]);

                    if ($status === 'completed') {
                        $stmtMov->execute([$companyId, $prodId, $data['from_warehouse_id'], 'out', 'transfer_out', $transferNum, $qty]);
                        $stmtMov->execute([$companyId, $prodId, $data['to_warehouse_id'], 'in', 'transfer_in', $transferNum, $qty]);
                    }
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = "تم تحديث أمر التحويل بنجاح.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/inventory/stock/transfers/{$id}/edit");
        }

        return new RedirectResponse('/ERP/inventory/stock/transfers');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        try {
            if (!$id) throw new Exception("المعرف غير صالح.");

            $stmt = $this->db->prepare("SELECT status FROM stock_transfers WHERE id = ?");
            $stmt->execute([$id]);
            $status = $stmt->fetchColumn();

            if ($status === 'completed') {
                throw new Exception("لا يمكن حذف أمر تحويل مكتمل.");
            }

            $this->db->prepare("DELETE FROM stock_transfers WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف أمر التحويل بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse('/ERP/inventory/stock/transfers');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        
        try {
            if (!$id) throw new Exception("المعرف غير متاح.");

            $stmt = $this->db->prepare("
                SELECT st.*, 
                       COALESCE(w1.name_ar, '---') as from_wh_name, COALESCE(w1.code, '---') as from_wh_code,
                       COALESCE(w2.name_ar, '---') as to_wh_name, COALESCE(w2.code, '---') as to_wh_code
                FROM stock_transfers st
                LEFT JOIN warehouses w1 ON st.from_warehouse_id = w1.id
                LEFT JOIN warehouses w2 ON st.to_warehouse_id = w2.id
                WHERE st.id = ?
            ");
            $stmt->execute([$id]);
            $transfer = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$transfer) throw new Exception("أمر التحويل غير موجود.");

            $stmtItems = $this->db->prepare("
                SELECT i.*, COALESCE(p.name_ar, 'صنف غير معروف') as product_name, COALESCE(p.item_code, '---') as product_code, COALESCE(p.unit, 'قطعة') as unit
                FROM stock_transfer_items i 
                LEFT JOIN products p ON i.product_id = p.id 
                WHERE i.transfer_id = ?
            ");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/inventory/stock/transfers');
        }

        ob_start(); 
        include $this->basePath . '/resources/views/inventory/transfers/show.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}