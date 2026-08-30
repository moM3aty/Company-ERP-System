<?php
// Path: app/Modules/Sales/Http/Controllers/SalesOrderController.php

namespace App\Modules\Sales\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;

class SalesOrderController extends Controller
{
    private string $basePath;
    private PDO $db;

    public function __construct()
    {
        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    public function index(Request $request, Response $response): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "SELECT * FROM sales_orders WHERE company_id = ?";
            $params = [$companyId];

            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }
            $sql .= " ORDER BY id DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
            
            if (!empty($orders)) {
                $custStmt = $this->db->prepare("SELECT name_en, name_ar FROM customers WHERE id = ?");
                foreach ($orders as $order) {
                    $custStmt->execute([$order->customer_id]);
                    $customer = $custStmt->fetch(PDO::FETCH_OBJ);
                    $order->customer_name = $customer ? ($customer->name_en ?: $customer->name_ar) : 'Unknown';
                    $order->customer_name_ar = $customer ? ($customer->name_ar ?: $customer->name_en) : 'غير معروف';
                }
            }
        } catch (Exception $e) { 
            $orders = []; 
        }

        ob_start(); include $this->basePath . '/resources/views/sales/orders/index.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();
        $order = null; 
        $lines = [];

        try {
            $custSql = "SELECT id, name_en, name_ar FROM customers WHERE is_active = 1 AND company_id = ?";
            $custParams = [$companyId];
            if ($branchId) {
                $custSql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $custParams[] = $branchId;
            }
            $custStmt = $this->db->prepare($custSql);
            $custStmt->execute($custParams);
            $customers = $custStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $prodSql = "SELECT id, sku, COALESCE(name_ar, name_en) as name, sale_price FROM inv_products WHERE is_active = 1 AND company_id = ?";
            $prodParams = [$companyId];
            if ($branchId) {
                $prodSql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $prodParams[] = $branchId;
            }
            $prodStmt = $this->db->prepare($prodSql);
            $prodStmt->execute($prodParams);
            $products = $prodStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            foreach ($products as $p) {
                $p->sale_price = convert_amount($p->sale_price ?? 0);
            }
        } catch (Exception $e) { 
            $customers = []; $products = []; 
        }

        ob_start(); include $this->basePath . '/resources/views/sales/orders/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $isAr = isRtl();
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $this->db->beginTransaction();
            
            $orderNo = !empty($data['order_no']) ? trim($data['order_no']) : 'SO-' . date('Ym') . rand(1000, 9999);
            
            $inputSubtotal = 0;
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $qty = (float)($item['quantity'] ?? 0);
                    $price = (float)($item['unit_price'] ?? 0);
                    $inputSubtotal += ($qty * $price);
                }
            }

            $baseSubtotal = convert_to_base($inputSubtotal);
            $baseTax = $baseSubtotal * 0.15;
            $baseTotal = $baseSubtotal + $baseTax;

            $stmt = $this->db->prepare("
                INSERT INTO sales_orders (company_id, branch_id, order_no, customer_id, order_date, expected_date, subtotal, tax_total, grand_total, status, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, $branchId, $orderNo, $data['customer_id'], $data['order_date'], $data['expected_delivery_date'] ?? null, 
                $baseSubtotal, $baseTax, $baseTotal, $data['status'] ?? 'draft', $data['notes'] ?? null
            ]);
            
            $orderId = $this->db->lastInsertId();

            if (!empty($data['items']) && is_array($data['items'])) {
                $lineStmt = $this->db->prepare("INSERT INTO sales_order_lines (order_id, product_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($data['items'] as $item) {
                    $qty = (float)($item['quantity'] ?? 0); 
                    $priceInput = (float)($item['unit_price'] ?? 0);
                    if ($qty <= 0) continue;

                    $baseUnitPrice = convert_to_base($priceInput);
                    $baseLineTotal = $qty * $baseUnitPrice;

                    $lineStmt->execute([
                        $orderId, empty($item['product_id']) ? null : $item['product_id'], 
                        $item['description'] ?? 'Item', $qty, $baseUnitPrice, $baseLineTotal
                    ]);
                }
            }
            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم إنشاء أمر البيع بنجاح!" : "Sales Order created!";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = "Error: " . $e->getMessage();
            return new RedirectResponse('/ERP/sales/orders/create');
        }
        return new RedirectResponse('/ERP/sales/orders');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "SELECT * FROM sales_orders WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $order = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$order) throw new Exception("الأمر غير موجود.");

            $lineStmt = $this->db->prepare("SELECT * FROM sales_order_lines WHERE order_id = ?");
            $lineStmt->execute([$id]);
            $lines = $lineStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            foreach ($lines as $line) {
                $line->unit_price = convert_amount($line->unit_price ?? 0);
                $line->total      = convert_amount($line->total ?? 0);
            }

            $custSql = "SELECT id, name_en, name_ar FROM customers WHERE is_active = 1 AND company_id = ?";
            $custParams = [$companyId];
            if ($branchId) {
                $custSql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $custParams[] = $branchId;
            }
            $custStmt = $this->db->prepare($custSql);
            $custStmt->execute($custParams);
            $customers = $custStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $prodSql = "SELECT id, sku, COALESCE(name_ar, name_en) as name, sale_price FROM inv_products WHERE is_active = 1 AND company_id = ?";
            $prodParams = [$companyId];
            if ($branchId) {
                $prodSql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $prodParams[] = $branchId;
            }
            $prodStmt = $this->db->prepare($prodSql);
            $prodStmt->execute($prodParams);
            $products = $prodStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            foreach ($products as $p) {
                $p->sale_price = convert_amount($p->sale_price ?? 0);
            }
        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/orders');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/orders/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $data = $request->getParsedBody();
        $isAr = isRtl();
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $this->db->beginTransaction();

            $inputSubtotal = 0;
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $qty = (float)($item['quantity'] ?? 0);
                    $price = (float)($item['unit_price'] ?? 0);
                    $inputSubtotal += ($qty * $price);
                }
            }
            $baseSubtotal = convert_to_base($inputSubtotal);
            $baseTax = $baseSubtotal * 0.15;
            $baseTotal = $baseSubtotal + $baseTax;

            $sql = "UPDATE sales_orders SET customer_id=?, order_date=?, expected_date=?, subtotal=?, tax_total=?, grand_total=?, status=?, notes=? WHERE id=? AND company_id=?";
            $params = [
                $data['customer_id'], $data['order_date'], $data['expected_delivery_date'] ?? null, 
                $baseSubtotal, $baseTax, $baseTotal, $data['status'] ?? 'draft', $data['notes'] ?? null, $id, $companyId
            ];

            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $this->db->prepare("DELETE FROM sales_order_lines WHERE order_id = ?")->execute([$id]);

            if (!empty($data['items']) && is_array($data['items'])) {
                $lineStmt = $this->db->prepare("INSERT INTO sales_order_lines (order_id, product_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($data['items'] as $item) {
                    $qty = (float)($item['quantity'] ?? 0); $priceInput = (float)($item['unit_price'] ?? 0);
                    if ($qty <= 0) continue;

                    $basePrice = convert_to_base($priceInput);
                    $lineStmt->execute([$id, empty($item['product_id']) ? null : $item['product_id'], $item['description'] ?? 'Item', $qty, $basePrice, ($qty * $basePrice)]);
                }
            }
            
            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم تحديث أمر البيع بنجاح!" : "Sales Order updated!";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = "Error: " . $e->getMessage();
            return new RedirectResponse("/ERP/sales/orders/{$id}/edit");
        }
        return new RedirectResponse('/ERP/sales/orders');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "SELECT * FROM sales_orders WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $order = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$order) throw new Exception("Order not found.");

            $custStmt = $this->db->prepare("SELECT name_en, name_ar, phone, email, tax_number FROM customers WHERE id = ? AND company_id = ?");
            $custStmt->execute([$order->customer_id, $companyId]);
            $customer = $custStmt->fetch(PDO::FETCH_OBJ);

            $lineStmt = $this->db->prepare("SELECT * FROM sales_order_lines WHERE order_id = ?");
            $lineStmt->execute([$id]);
            $lines = $lineStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/orders');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/orders/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $this->db->beginTransaction();
            $sql = "DELETE FROM sales_orders WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $this->db->commit();
            $_SESSION['flash_msg'] = "تم الحذف بنجاح.";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = "لا يمكن الحذف.";
        }
        return new RedirectResponse('/ERP/sales/orders');
    }
}