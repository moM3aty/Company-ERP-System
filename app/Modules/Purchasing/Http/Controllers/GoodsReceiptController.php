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
        ini_set('display_errors', 0);
        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    public function index(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        $dbError = null;
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $whereClause = "WHERE gr.company_id = ?";
            $params = [$companyId];

            if ($branchId > 0) {
                $whereClause .= " AND (gr.branch_id = ? OR gr.branch_id IS NULL OR gr.branch_id = 0)";
                $params[] = $branchId;
            }

            if ($search !== '') {
                $whereClause .= " AND (gr.receipt_number LIKE ? OR gr.delivery_note_number LIKE ? OR s.name_ar LIKE ? OR s.name_en LIKE ? OR po.po_number LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like, $like, $like]);
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
                SELECT gr.*, COALESCE(s.name_ar, s.name_en) as supplier_name, po.po_number,
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
        if (file_exists($viewPath)) include $viewPath;
        
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;
        $bCond     = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";

        $receipt = null; $items = []; $suppliers = []; $products = []; $orders = [];
        try {
            $suppliers = $this->db->query("SELECT id, COALESCE(name_ar, name_en) as name_ar, name_en, code FROM suppliers WHERE company_id = $companyId AND is_active = 1 $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
            $products  = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products WHERE company_id = $companyId ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
            $orders    = $this->db->query("SELECT id, po_number FROM purchase_orders WHERE company_id = $companyId $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); include $this->basePath . '/resources/views/purchasing/receipts/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        try {
            if (empty($data['supplier_id'])) {
                throw new Exception($isAr ? "يرجى اختيار المورد." : "Supplier is required.");
            }

            $this->db->beginTransaction();

            $grnNum = !empty($data['receipt_number']) ? trim($data['receipt_number']) : 'GRN-' . date('ymd') . '-' . rand(100, 999);
            $poId   = !empty($data['po_id']) ? $data['po_id'] : null;

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO goods_receipts (company_id, branch_id, po_id, supplier_id, receipt_number, delivery_note_number, receipt_date, received_by, status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $branchId, $poId, $data['supplier_id'], $grnNum,
                    $data['delivery_note_number'] ?? null, $data['receipt_date'],
                    $data['received_by'] ?? null, $data['status'] ?? 'accepted', $data['notes'] ?? null
                ]);
            } catch (\PDOException $ex) {
                // احتياطي في حال عدم وجود عمود branch_id
                $stmt = $this->db->prepare("
                    INSERT INTO goods_receipts (company_id, po_id, supplier_id, receipt_number, delivery_note_number, receipt_date, received_by, status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $poId, $data['supplier_id'], $grnNum,
                    $data['delivery_note_number'] ?? null, $data['receipt_date'],
                    $data['received_by'] ?? null, $data['status'] ?? 'accepted', $data['notes'] ?? null
                ]);
            }
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
                    $price  = (float)($data['unit_price'][$idx] ?? 0);

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
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;
        $bCond     = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";

        try {
            $stmt = $this->db->prepare("SELECT * FROM goods_receipts WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $receipt = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$receipt) throw new Exception("إذن الاستلام غير موجود.");

            $stmtItems = $this->db->prepare("SELECT * FROM goods_receipt_items WHERE receipt_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $suppliers = $this->db->query("SELECT id, COALESCE(name_ar, name_en) as name_ar, name_en, code FROM suppliers WHERE company_id = $companyId AND is_active = 1 $bCond")->fetchAll(PDO::FETCH_OBJ);
            $products  = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products WHERE company_id = $companyId")->fetchAll(PDO::FETCH_OBJ);
            $orders    = $this->db->query("SELECT id, po_number FROM purchase_orders WHERE company_id = $companyId $bCond")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/receipts');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/receipts/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $this->db->beginTransaction();

            $poId = !empty($data['po_id']) ? $data['po_id'] : null;

            $stmt = $this->db->prepare("
                UPDATE goods_receipts 
                SET po_id=?, supplier_id=?, delivery_note_number=?, receipt_date=?, received_by=?, status=?, notes=?
                WHERE id=? AND company_id=?
            ");
            $stmt->execute([
                $poId, $data['supplier_id'], $data['delivery_note_number'] ?? null,
                $data['receipt_date'], $data['received_by'] ?? null,
                $data['status'] ?? 'accepted', $data['notes'] ?? null, $id, $companyId
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
                    $price  = (float)($data['unit_price'][$idx] ?? 0);

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
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $stmt = $this->db->prepare("
                SELECT gr.*, COALESCE(s.name_ar, s.name_en) as supplier_name, s.code as supplier_code, s.phone as supplier_phone, po.po_number
                FROM goods_receipts gr
                LEFT JOIN suppliers s ON gr.supplier_id = s.id
                LEFT JOIN purchase_orders po ON gr.po_id = po.id
                WHERE gr.id = ? AND gr.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
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

            // تحويل قيم الوحدات إن وجدت
            foreach ($items as $item) {
                $item->unit_price = convert_amount($item->unit_price);
                $item->total_price = convert_amount($item->total_price);
            }

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/receipts');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/receipts/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $this->db->prepare("DELETE FROM goods_receipts WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف إذن الاستلام بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء عملية الحذف.";
        }
        return new RedirectResponse('/ERP/purchasing/receipts');
    }
}