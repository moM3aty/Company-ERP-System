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
                $whereClause = "WHERE po.po_number LIKE ? OR s.name_ar LIKE ? OR s.name_en LIKE ?";
                $like = "%{$search}%";
                $params = [$like, $like, $like];
            }

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id $whereClause");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT po.*, s.name_ar as supplier_name,
                       (SELECT COUNT(id) FROM purchase_order_items WHERE po_id = po.id) as items_count
                FROM purchase_orders po
                LEFT JOIN suppliers s ON po.supplier_id = s.id
                $whereClause
                ORDER BY po.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            $orders = [];
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/purchasing/orders/index.php';
        if ($dbError) echo "<div style='margin:20px; padding:20px; background:#fef2f2; color:#b91c1c; border-radius:8px;'><strong>DB Error:</strong> $dbError</div>";
        if (file_exists($viewPath)) include $viewPath;
        else echo "<div style='margin:20px; padding:20px; background:#fee2e2; color:#dc2626;'>ملف الواجهة مفقود: $viewPath</div>";
        
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $order = null; $items = []; $suppliers = []; $products = [];
        try {
            $suppliers = $this->db->query("SELECT id, name_ar, name_en, code FROM suppliers WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/purchasing/orders/create.php';
        if (file_exists($viewPath)) include $viewPath;
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST; // استخدام $_POST بدلاً من getParsedBody()
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if (empty($data['supplier_id'])) {
                throw new Exception($isAr ? "يرجى اختيار المورد." : "Supplier is required.");
            }

            $this->db->beginTransaction();

            $companyId = $_SESSION['company_id'] ?? 1;
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

            $stmt = $this->db->prepare("
                INSERT INTO purchase_orders (company_id, supplier_id, po_number, order_date, delivery_date, status, subtotal, discount_amount, tax_amount, total_amount, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, $data['supplier_id'], $poNum, $data['order_date'], $data['delivery_date'],
                $data['status'] ?? 'draft', $subtotal, $discount, $tax, $totalAmount, $data['notes'] ?? null
            ]);
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
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/orders/create');
        }

        return new RedirectResponse('/ERP/purchasing/orders');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM purchase_orders WHERE id = ?");
            $stmt->execute([$id]);
            $order = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$order) throw new Exception("أمر الشراء غير موجود.");

            $stmtItems = $this->db->prepare("SELECT * FROM purchase_order_items WHERE po_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $suppliers = $this->db->query("SELECT id, name_ar, name_en, code FROM suppliers WHERE is_active = 1")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/orders');
        }

        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/purchasing/orders/create.php';
        if(file_exists($viewPath)) include $viewPath;
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $data = $_POST; // استخدام $_POST المباشرة
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

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
                WHERE id=?
            ");
            $stmt->execute([
                $data['supplier_id'], $data['order_date'], $data['delivery_date'], $data['status'] ?? 'draft',
                $subtotal, $discount, $tax, $totalAmount, $data['notes'] ?? null, $id
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
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/purchasing/orders/{$id}/edit");
        }

        return new RedirectResponse('/ERP/purchasing/orders');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        try {
            $stmt = $this->db->prepare("
                SELECT po.*, s.name_ar as supplier_name, s.code as supplier_code, s.tax_number as supplier_tax, s.phone as supplier_phone, s.address as supplier_address
                FROM purchase_orders po
                LEFT JOIN suppliers s ON po.supplier_id = s.id
                WHERE po.id = ?
            ");
            $stmt->execute([$id]);
            $order = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$order) throw new Exception("أمر الشراء غير موجود.");

            $stmtItems = $this->db->prepare("SELECT i.*, p.item_code as product_code FROM purchase_order_items i LEFT JOIN products p ON i.product_id = p.id WHERE i.po_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/orders');
        }

        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/purchasing/orders/show.php';
        if(file_exists($viewPath)) include $viewPath;
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        try {
            $this->db->prepare("DELETE FROM purchase_orders WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف أمر الشراء.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف.";
        }
        return new RedirectResponse('/ERP/purchasing/orders');
    }
}