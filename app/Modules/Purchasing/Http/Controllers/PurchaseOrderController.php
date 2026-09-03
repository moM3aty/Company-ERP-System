<?php
// Path: app/Modules/Purchasing/Http/Controllers/PurchaseOrderController.php

namespace App\Modules\Purchasing\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class PurchaseOrderController extends Controller
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
            $whereClause = "WHERE po.company_id = ?";
            $params = [$companyId];

            if ($branchId > 0) {
                $whereClause .= " AND (po.branch_id = ? OR po.branch_id IS NULL OR po.branch_id = 0)";
                $params[] = $branchId;
            }

            if ($search !== '') {
                $whereClause .= " AND (po.po_number LIKE ? OR s.name_ar LIKE ? OR s.name_en LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like]);
            }

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id $whereClause");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT po.*, COALESCE(s.name_ar, s.name_en) as supplier_name,
                       (SELECT COUNT(id) FROM purchase_order_items WHERE po_id = po.id) as items_count
                FROM purchase_orders po
                LEFT JOIN suppliers s ON po.supplier_id = s.id
                $whereClause
                ORDER BY po.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            // تطبيق تحويل العملة على إجمالي القيمة
            foreach ($orders as $o) {
                $o->total_amount = convert_amount($o->total_amount);
            }

        } catch (Throwable $e) {
            $orders = [];
            $totalPages = 1;
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/purchasing/orders/index.php';
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

        $order = null; $items = []; $suppliers = []; $products = [];
        try {
            $suppliers = $this->db->query("SELECT id, COALESCE(name_ar, name_en) as name_ar, name_en, code FROM suppliers WHERE company_id = $companyId AND is_active = 1 $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
            $products  = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products WHERE company_id = $companyId ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); include $this->basePath . '/resources/views/purchasing/orders/create.php'; $content = ob_get_clean();
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
            if (empty($data['supplier_id'])) throw new Exception($isAr ? "يرجى اختيار المورد." : "Supplier is required.");
            $this->db->beginTransaction();

            $poNum = !empty($data['po_number']) ? trim($data['po_number']) : 'PO-' . date('ymd') . '-' . rand(100, 999);

            $subtotal = 0;
            if (!empty($data['description']) && is_array($data['description'])) {
                foreach ($data['description'] as $idx => $desc) {
                    $qty = (float)($data['quantity'][$idx] ?? 1);
                    $price = (float)($data['unit_price'][$idx] ?? 0);
                    $subtotal += ($qty * $price);
                }
            }
            $discount = (float)($data['discount_amount'] ?? 0);
            $tax = (float)($data['tax_amount'] ?? 0);
            $totalAmount = ($subtotal - $discount) + $tax;

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO purchase_orders (company_id, branch_id, supplier_id, po_number, order_date, delivery_date, status, subtotal, discount_amount, tax_amount, total_amount, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $branchId, $data['supplier_id'], $poNum, $data['order_date'], 
                    $data['delivery_date'], $data['status'] ?? 'draft', $subtotal, $discount, $tax, $totalAmount, $data['notes'] ?? null
                ]);
            } catch (\PDOException $ex) {
                // احتياطي في حال عدم وجود عمود branch_id
                $stmt = $this->db->prepare("
                    INSERT INTO purchase_orders (company_id, supplier_id, po_number, order_date, delivery_date, status, subtotal, discount_amount, tax_amount, total_amount, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $data['supplier_id'], $poNum, $data['order_date'], 
                    $data['delivery_date'], $data['status'] ?? 'draft', $subtotal, $discount, $tax, $totalAmount, $data['notes'] ?? null
                ]);
            }
            $poId = $this->db->lastInsertId();

            if (!empty($data['description']) && is_array($data['description'])) {
                $stmtItem = $this->db->prepare("INSERT INTO purchase_order_items (po_id, product_id, description, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($data['description'] as $idx => $desc) {
                    if (empty($desc)) continue;
                    $prodId = !empty($data['product_id'][$idx]) ? $data['product_id'][$idx] : null;
                    $qty = (float)($data['quantity'][$idx] ?? 1);
                    $price = (float)($data['unit_price'][$idx] ?? 0);
                    $stmtItem->execute([$poId, $prodId, $desc, $qty, $price, ($qty * $price)]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم إنشاء أمر الشراء بنجاح." : "Purchase Order created successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/orders/create');
        }

        return new RedirectResponse('/ERP/purchasing/orders');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;
        $bCond     = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";

        try {
            $stmt = $this->db->prepare("SELECT * FROM purchase_orders WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $order = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$order) throw new Exception("أمر الشراء غير موجود.");

            $stmtItems = $this->db->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $suppliers = $this->db->query("SELECT id, COALESCE(name_ar, name_en) as name_ar, name_en, code FROM suppliers WHERE company_id = $companyId AND is_active = 1 $bCond")->fetchAll(PDO::FETCH_OBJ);
            $products  = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products WHERE company_id = $companyId")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/orders');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/orders/create.php'; $content = ob_get_clean();
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

            $subtotal = 0;
            if (!empty($data['description']) && is_array($data['description'])) {
                foreach ($data['description'] as $idx => $desc) {
                    $qty = (float)($data['quantity'][$idx] ?? 1);
                    $price = (float)($data['unit_price'][$idx] ?? 0);
                    $subtotal += ($qty * $price);
                }
            }
            $discount = (float)($data['discount_amount'] ?? 0);
            $tax = (float)($data['tax_amount'] ?? 0);
            $totalAmount = ($subtotal - $discount) + $tax;

            $stmt = $this->db->prepare("
                UPDATE purchase_orders 
                SET supplier_id=?, order_date=?, delivery_date=?, status=?, subtotal=?, discount_amount=?, tax_amount=?, total_amount=?, notes=?
                WHERE id=? AND company_id=?
            ");
            $stmt->execute([
                $data['supplier_id'], $data['order_date'], $data['delivery_date'], $data['status'] ?? 'draft',
                $subtotal, $discount, $tax, $totalAmount, $data['notes'] ?? null, $id, $companyId
            ]);

            $this->db->prepare("DELETE FROM purchase_order_items WHERE po_id = ?")->execute([$id]);

            if (!empty($data['description']) && is_array($data['description'])) {
                $stmtItem = $this->db->prepare("INSERT INTO purchase_order_items (po_id, product_id, description, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($data['description'] as $idx => $desc) {
                    if (empty($desc)) continue;
                    $prodId = !empty($data['product_id'][$idx]) ? $data['product_id'][$idx] : null;
                    $qty = (float)($data['quantity'][$idx] ?? 1);
                    $price = (float)($data['unit_price'][$idx] ?? 0);
                    $stmtItem->execute([$id, $prodId, $desc, $qty, $price, ($qty * $price)]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم تحديث أمر الشراء بنجاح." : "PO updated successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/purchasing/orders/{$id}/edit");
        }

        return new RedirectResponse('/ERP/purchasing/orders');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $stmt = $this->db->prepare("
                SELECT po.*, 
                       COALESCE(s.name_ar, s.name_en) as supplier_name, s.code as supplier_code, s.tax_number as supplier_tax, s.phone as supplier_phone, s.address as supplier_address
                FROM purchase_orders po
                LEFT JOIN suppliers s ON po.supplier_id = s.id
                WHERE po.id = ? AND po.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $order = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$order) throw new Exception("أمر الشراء غير موجود.");

            $stmtItems = $this->db->prepare("SELECT i.*, p.item_code as product_code FROM purchase_order_items i LEFT JOIN products p ON i.product_id = p.id WHERE i.po_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            // تحويل القيم المالية بناءً على العملة الحالية
            $order->subtotal = convert_amount($order->subtotal);
            $order->discount_amount = convert_amount($order->discount_amount);
            $order->tax_amount = convert_amount($order->tax_amount);
            $order->total_amount = convert_amount($order->total_amount);

            foreach ($items as $item) {
                $item->unit_price = convert_amount($item->unit_price);
                $item->total_price = convert_amount($item->total_price);
            }

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/orders');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/orders/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $this->db->prepare("DELETE FROM purchase_orders WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف أمر الشراء.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف لارتباطه بعمليات أخرى.";
        }
        return new RedirectResponse('/ERP/purchasing/orders');
    }
}