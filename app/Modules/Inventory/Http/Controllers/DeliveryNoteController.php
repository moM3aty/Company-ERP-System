<?php
// Path: app/Modules/Inventory/Http/Controllers/DeliveryNoteController.php

namespace App\Modules\Inventory\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class DeliveryNoteController extends Controller
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
        if (preg_match('#/delivery-notes/(\d+)#', $uri, $matches)) {
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

    public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/delivery-notes/(\d+)/edit#', $uri, $m)) {
            return $this->edit($request, $response, (int)$m[1]);
        }
        if (preg_match('#/delivery-notes/(\d+)/update#', $uri, $m)) {
            return $this->update($request, $response, (int)$m[1]);
        }
        if (preg_match('#/delivery-notes/(\d+)/delete#', $uri, $m)) {
            return $this->delete($request, $response, (int)$m[1]);
        }
        if (preg_match('#/delivery-notes/(\d+)$#', $uri, $m)) {
            return $this->show($request, $response, (int)$m[1]);
        }

        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condDN = $this->buildBranchCond('dn', $branchId, 'delivery_notes');

            $whereClause = "WHERE dn.company_id = ?";
            $params = [$companyId];

            if ($search !== '') {
                $whereClause .= " AND (dn.delivery_number LIKE ? OR dn.customer_name LIKE ? OR w.name_ar LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like);
            }

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM delivery_notes dn
                LEFT JOIN warehouses w ON dn.warehouse_id = w.id
                $whereClause $condDN
            ");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT dn.*, 
                       COALESCE(w.name_ar, '---') as warehouse_name,
                       (SELECT COUNT(id) FROM delivery_note_items WHERE delivery_note_id = dn.id) as items_count
                FROM delivery_notes dn
                LEFT JOIN warehouses w ON dn.warehouse_id = w.id
                $whereClause $condDN
                ORDER BY dn.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $notes = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total, 
                    SUM(IF(status='delivered', 1, 0)) as delivered, 
                    SUM(IF(status='draft', 1, 0)) as draft 
                FROM delivery_notes dn WHERE company_id = $companyId $condDN
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $notes = [];
            $stats = (object)['total'=>0, 'delivered'=>0, 'draft'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/inventory/delivery_notes/index.php';
        $content = ob_get_clean();
        
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $note = null; $items = []; $warehouses = []; $products = [];
        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condW = $this->buildBranchCond('', $branchId, 'warehouses');
            $condP = $this->buildBranchCond('', $branchId, 'products');

            $warehouses = $this->db->query("SELECT id, name_ar, code FROM warehouses WHERE company_id = $companyId AND is_active = 1 $condW ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, name_ar FROM products WHERE company_id = $companyId AND is_active = 1 $condP ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); 
        include $this->basePath . '/resources/views/inventory/delivery_notes/create.php';
        $content = ob_get_clean();
        
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if (empty($data['warehouse_id']) || empty($data['customer_name'])) {
                throw new Exception($isAr ? "يجب تحديد المستودع واسم العميل." : "Warehouse and Customer name are required.");
            }

            $this->db->beginTransaction();

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();
            $delNum = !empty($data['delivery_number']) ? trim($data['delivery_number']) : 'DN-' . date('ymd') . rand(10, 99);
            $status = $data['status'] ?? 'draft';

            $hasBranchDN = $this->hasBranchColumn('delivery_notes');
            $branchCol = $hasBranchDN ? ", branch_id" : "";
            $branchVal = $hasBranchDN ? ", ?" : "";

            $stmt = $this->db->prepare("
                INSERT INTO delivery_notes (company_id, delivery_number, warehouse_id, customer_name, delivery_date, status, notes {$branchCol})
                VALUES (?, ?, ?, ?, ?, ?, ? {$branchVal})
            ");
            
            $params = [
                $companyId, $delNum, $data['warehouse_id'], trim($data['customer_name']),
                $data['delivery_date'], $status, $data['notes'] ?? null
            ];
            if ($hasBranchDN) $params[] = $branchId;

            $stmt->execute($params);
            $noteId = $this->db->lastInsertId();

            $stmtItem = $this->db->prepare("INSERT INTO delivery_note_items (delivery_note_id, product_id, quantity) VALUES (?, ?, ?)");

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

                    $stmtItem->execute([$noteId, $prodId, $qty]);

                    if ($status === 'delivered') {
                        $pMov = [$companyId, $prodId, $data['warehouse_id'], 'out', 'sale', $delNum, $qty];
                        if ($hasBranchSM) $pMov[] = $branchId;
                        $stmtMov->execute($pMov);
                    }
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم إنشاء إذن التسليم بنجاح." : "Delivery note created successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            header("Location: /ERP/inventory/delivery-notes/create");
            exit;
        }

        header("Location: /ERP/inventory/delivery-notes");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);

        try {
            if (!$id) throw new Exception("معرف إذن التسليم غير متاح.");

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $stmt = $this->db->prepare("SELECT * FROM delivery_notes WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $note = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$note) throw new Exception("إذن التسليم غير موجود بقاعدة البيانات.");
            if ($note->status === 'delivered') {
                throw new Exception("لا يمكن تعديل إذن تسليم مكتمل ومسلم بالفعل.");
            }

            $stmtItems = $this->db->prepare("SELECT * FROM delivery_note_items WHERE delivery_note_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $condW = $this->buildBranchCond('', $branchId, 'warehouses');
            $condP = $this->buildBranchCond('', $branchId, 'products');

            $warehouses = $this->db->query("SELECT id, name_ar, code FROM warehouses WHERE company_id = $companyId AND is_active = 1 $condW ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, name_ar FROM products WHERE company_id = $companyId AND is_active = 1 $condP ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/inventory/delivery-notes");
            exit;
        }

        ob_start(); 
        include $this->basePath . '/resources/views/inventory/delivery_notes/create.php';
        $content = ob_get_clean();
        
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if (!$id) throw new Exception("معرف إذن التسليم مفقود.");

            $this->db->beginTransaction();
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $stmtNum = $this->db->prepare("SELECT delivery_number FROM delivery_notes WHERE id = ?");
            $stmtNum->execute([$id]);
            $delNum = $stmtNum->fetchColumn();

            $status = $data['status'] ?? 'draft';

            $stmt = $this->db->prepare("
                UPDATE delivery_notes 
                SET warehouse_id=?, customer_name=?, delivery_date=?, status=?, notes=? 
                WHERE id=? AND company_id=? AND status != 'delivered'
            ");
            $stmt->execute([
                $data['warehouse_id'], trim($data['customer_name']), 
                $data['delivery_date'], $status, $data['notes'] ?? null, $id, $companyId
            ]);

            $this->db->prepare("DELETE FROM delivery_note_items WHERE delivery_note_id = ?")->execute([$id]);

            $stmtItem = $this->db->prepare("INSERT INTO delivery_note_items (delivery_note_id, product_id, quantity) VALUES (?, ?, ?)");

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

                    $stmtItem->execute([$id, $prodId, $qty]);

                    if ($status === 'delivered') {
                        $pMov = [$companyId, $prodId, $data['warehouse_id'], 'out', 'sale', $delNum, $qty];
                        if ($hasBranchSM) $pMov[] = $branchId;
                        $stmtMov->execute($pMov);
                    }
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم تحديث إذن التسليم بنجاح." : "Delivery note updated successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            header("Location: /ERP/inventory/delivery-notes/{$id}/edit");
            exit;
        }

        header("Location: /ERP/inventory/delivery-notes");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $this->getCompanyId();

        try {
            if (!$id) throw new Exception("المعرف غير صالح.");

            $stmt = $this->db->prepare("SELECT status FROM delivery_notes WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $status = $stmt->fetchColumn();

            if ($status === 'delivered') {
                throw new Exception("لا يمكن حذف إذن تسليم تم اعتماده وتسليمه للعميل.");
            }

            $this->db->prepare("DELETE FROM delivery_notes WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف إذن التسليم بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        
        header("Location: /ERP/inventory/delivery-notes");
        exit;
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);

        try {
            if (!$id) throw new Exception("المعرف غير متاح.");

            $companyId = $this->getCompanyId();

            $stmt = $this->db->prepare("
                SELECT dn.*, 
                       COALESCE(w.name_ar, '---') as warehouse_name, COALESCE(w.code, '---') as warehouse_code, w.location as warehouse_loc
                FROM delivery_notes dn
                LEFT JOIN warehouses w ON dn.warehouse_id = w.id
                WHERE dn.id = ? AND dn.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $note = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$note) throw new Exception("إذن التسليم غير موجود.");

            $stmtItems = $this->db->prepare("
                SELECT i.*, COALESCE(p.name_ar, 'صنف غير معروف') as product_name, COALESCE(p.item_code, '---') as product_code, COALESCE(p.unit, 'قطعة') as unit
                FROM delivery_note_items i 
                LEFT JOIN products p ON i.product_id = p.id 
                WHERE i.delivery_note_id = ?
            ");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ) ?: [];

            ob_start(); 
            include $this->basePath . '/resources/views/inventory/delivery_notes/show.php';
            $content = ob_get_clean();
            
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/inventory/delivery-notes");
            exit;
        }
    }
}