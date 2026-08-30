<?php
// Path: app/Modules/Purchasing/Http/Controllers/GoodsReceiptController.php

namespace App\Modules\Purchasing\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class GoodsReceiptController extends Controller
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

    public function index(Request $request, Response $response): Response
    {
        $dbError = null;
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $whereClause = "";
            $params = [];
            if ($search !== '') {
                $whereClause = "WHERE gr.receipt_number LIKE ? OR gr.delivery_note_number LIKE ? OR s.name_ar LIKE ? OR po.po_number LIKE ?";
                $like = "%{$search}%";
                $params = [$like, $like, $like, $like];
            }

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM goods_receipts gr 
                LEFT JOIN suppliers s ON gr.supplier_id = s.id 
                LEFT JOIN purchase_orders po ON gr.po_id = po.id 
                $whereClause
            ");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT gr.*, s.name_ar as supplier_name, po.po_number,
                       (SELECT COUNT(id) FROM goods_receipt_items WHERE receipt_id = gr.id) as items_count
                FROM goods_receipts gr
                LEFT JOIN suppliers s ON gr.supplier_id = s.id
                LEFT JOIN purchase_orders po ON gr.po_id = po.id
                $whereClause
                ORDER BY gr.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $receipts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            $receipts = [];
            $totalPages = 1;
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/purchasing/receipts/index.php';
        if ($dbError) echo "<div style='margin:20px; padding:20px; background:#fef2f2; color:#b91c1c; border-radius:8px;'><strong>DB Error:</strong> $dbError</div>";
        if (file_exists($viewPath)) include $viewPath;
        else echo "<div style='margin:20px; padding:20px; background:#fee2e2; color:#dc2626;'>ملف الواجهة مفقود: $viewPath</div>";
        
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $receipt = null; $items = []; $suppliers = []; $products = []; $orders = [];
        try {
            $suppliers = $this->db->query("SELECT id, name_ar, name_en, code FROM suppliers WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
            $orders = $this->db->query("SELECT id, po_number FROM purchase_orders ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/purchasing/receipts/create.php';
        if (file_exists($viewPath)) include $viewPath;
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
            if (empty($data['supplier_id'])) {
                throw new Exception($isAr ? "يرجى اختيار المورد." : "Supplier is required.");
            }

            $this->db->beginTransaction();

            $companyId = $_SESSION['company_id'] ?? 1;
            $grnNum = !empty($data['receipt_number']) ? trim($data['receipt_number']) : 'GRN-' . date('ymd') . '-' . rand(100, 999);
            $poId = !empty($data['po_id']) ? $data['po_id'] : null;

            $stmt = $this->db->prepare("
                INSERT INTO goods_receipts (company_id, po_id, supplier_id, receipt_number, delivery_note_number, receipt_date, received_by, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, $poId, $data['supplier_id'], $grnNum,
                $data['delivery_note_number'] ?? null, $data['receipt_date'],
                $data['received_by'] ?? null, $data['status'] ?? 'accepted', $data['notes'] ?? null
            ]);
            $receiptId = $this->db->lastInsertId();

            if (!empty($data['description']) && is_array($data['description'])) {
                $stmtItem = $this->db->prepare("
                    INSERT INTO goods_receipt_items (receipt_id, product_id, description, quantity_received, quantity_accepted, quantity_rejected, unit_price, total_price)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                foreach ($data['description'] as $idx => $desc) {
                    if (empty($desc)) continue;
                    $prodId = !empty($data['product_id'][$idx]) ? $data['product_id'][$idx] : null;
                    $qtyRec = (float)($data['quantity_received'][$idx] ?? 1);
                    $qtyAcc = (float)($data['quantity_accepted'][$idx] ?? $qtyRec);
                    $qtyRej = (float)($data['quantity_rejected'][$idx] ?? 0);
                    $price = (float)($data['unit_price'][$idx] ?? 0);

                    $stmtItem->execute([
                        $receiptId, $prodId, $desc, $qtyRec, $qtyAcc, $qtyRej, $price, ($qtyAcc * $price)
                    ]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم إنشاء إذن استلام البضائع بنجاح." : "Goods Receipt created successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/receipts/create');
        }

        return new RedirectResponse('/ERP/purchasing/receipts');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM goods_receipts WHERE id = ?");
            $stmt->execute([$id]);
            $receipt = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$receipt) throw new Exception("إذن الاستلام غير موجود.");

            $stmtItems = $this->db->prepare("SELECT * FROM goods_receipt_items WHERE receipt_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $suppliers = $this->db->query("SELECT id, name_ar, name_en, code FROM suppliers WHERE is_active = 1")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products")->fetchAll(PDO::FETCH_OBJ);
            $orders = $this->db->query("SELECT id, po_number FROM purchase_orders")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/receipts');
        }

        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/purchasing/receipts/create.php';
        if (file_exists($viewPath)) include $viewPath;
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            $this->db->beginTransaction();

            $poId = !empty($data['po_id']) ? $data['po_id'] : null;

            $stmt = $this->db->prepare("
                UPDATE goods_receipts 
                SET po_id=?, supplier_id=?, delivery_note_number=?, receipt_date=?, received_by=?, status=?, notes=?
                WHERE id=?
            ");
            $stmt->execute([
                $poId, $data['supplier_id'], $data['delivery_note_number'] ?? null,
                $data['receipt_date'], $data['received_by'] ?? null,
                $data['status'] ?? 'accepted', $data['notes'] ?? null, $id
            ]);

            $this->db->prepare("DELETE FROM goods_receipt_items WHERE receipt_id = ?")->execute([$id]);

            if (!empty($data['description']) && is_array($data['description'])) {
                $stmtItem = $this->db->prepare("
                    INSERT INTO goods_receipt_items (receipt_id, product_id, description, quantity_received, quantity_accepted, quantity_rejected, unit_price, total_price)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                foreach ($data['description'] as $idx => $desc) {
                    if (empty($desc)) continue;
                    $prodId = !empty($data['product_id'][$idx]) ? $data['product_id'][$idx] : null;
                    $qtyRec = (float)($data['quantity_received'][$idx] ?? 1);
                    $qtyAcc = (float)($data['quantity_accepted'][$idx] ?? $qtyRec);
                    $qtyRej = (float)($data['quantity_rejected'][$idx] ?? 0);
                    $price = (float)($data['unit_price'][$idx] ?? 0);

                    $stmtItem->execute([
                        $id, $prodId, $desc, $qtyRec, $qtyAcc, $qtyRej, $price, ($qtyAcc * $price)
                    ]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم تحديث إذن الاستلام بنجاح." : "Goods Receipt updated successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/purchasing/receipts/{$id}/edit");
        }

        return new RedirectResponse('/ERP/purchasing/receipts');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        try {
            $stmt = $this->db->prepare("
                SELECT gr.*, s.name_ar as supplier_name, s.code as supplier_code, s.phone as supplier_phone, po.po_number
                FROM goods_receipts gr
                LEFT JOIN suppliers s ON gr.supplier_id = s.id
                LEFT JOIN purchase_orders po ON gr.po_id = po.id
                WHERE gr.id = ?
            ");
            $stmt->execute([$id]);
            $receipt = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$receipt) throw new Exception("إذن الاستلام غير موجود.");

            $stmtItems = $this->db->prepare("
                SELECT i.*, p.item_code as product_code 
                FROM goods_receipt_items i 
                LEFT JOIN products p ON i.product_id = p.id 
                WHERE i.receipt_id = ?
            ");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/receipts');
        }

        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/purchasing/receipts/show.php';
        if (file_exists($viewPath)) include $viewPath;
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        try {
            $this->db->prepare("DELETE FROM goods_receipts WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف إذن الاستلام بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء عملية الحذف.";
        }
        return new RedirectResponse('/ERP/purchasing/receipts');
    }
}