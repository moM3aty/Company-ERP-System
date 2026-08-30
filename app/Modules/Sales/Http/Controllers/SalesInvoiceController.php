<?php
// Path: app/Modules/Sales/Http/Controllers/SalesInvoiceController.php

namespace App\Modules\Sales\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;

class SalesInvoiceController extends Controller
{
    private string $basePath;
    private PDO $db;

    public function __construct()
    {
        global $basePath, $app;
        if (isset($basePath) && !empty($basePath)) {
            $this->basePath = $basePath;
        } else {
            $dir = __DIR__;
            while (!file_exists($dir . '/resources') && strlen($dir) > 3) { $dir = dirname($dir); }
            $this->basePath = $dir;
        }

        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    public function index(Request $request, Response $response): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();
        $dbError = null;

        try {
            $sql = "
                SELECT i.*, c.name_en as customer_name, c.name_ar as customer_name_ar 
                FROM sales_invoices i 
                LEFT JOIN customers c ON i.customer_id = c.id 
                WHERE i.company_id = ?
            ";
            $params = [$companyId];

            if ($branchId) {
                $sql .= " AND i.branch_id = ?";
                $params[] = $branchId;
            }
            $sql .= " ORDER BY i.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $invoices = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Exception $e) {
            $invoices = [];
            $dbError = $e->getMessage();
        }

        ob_start();
        $viewPath = $this->basePath . '/resources/views/sales/invoices/index.php';
        if (file_exists($viewPath)) {
            if ($dbError) echo "<div style='background:#fef2f2; color:#b91c1c; padding:20px; border-radius:8px;'><strong>DB Error:</strong> " . htmlspecialchars($dbError) . "</div>";
            include $viewPath;
        }
        $content = ob_get_clean();

        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();
        $invoice = null; 
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
            $customers = $custStmt->fetchAll(PDO::FETCH_OBJ);

            $prodSql = "SELECT id, sku, COALESCE(name_ar, name_en) as name, sale_price FROM inv_products WHERE is_active = 1 AND company_id = ?";
            $prodParams = [$companyId];
            if ($branchId) {
                $prodSql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $prodParams[] = $branchId;
            }
            $prodStmt = $this->db->prepare($prodSql);
            $prodStmt->execute($prodParams);
            $products = $prodStmt->fetchAll(PDO::FETCH_OBJ);

            foreach($products as $p) {
                $p->sale_price = convert_amount($p->sale_price ?? 0);
            }
        } catch (Exception $e) {
            $customers = []; $products = [];
        }

        ob_start(); include $this->basePath . '/resources/views/sales/invoices/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $this->db->beginTransaction();

            $invNumber = !empty($data['invoice_number']) ? $data['invoice_number'] : 'INV-' . date('Ym') . rand(1000, 9999);
            
            $inputSubtotal = 0;
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $qty = (float)($item['quantity'] ?? 0);
                    $price = (float)($item['unit_price'] ?? 0);
                    $inputSubtotal += ($qty * $price);
                }
            }
            
            $baseSubtotal = convert_to_base($inputSubtotal);
            $taxAmount = $baseSubtotal * 0.15; 
            $totalAmount = $baseSubtotal + $taxAmount;

            $stmt = $this->db->prepare("
                INSERT INTO sales_invoices (company_id, branch_id, customer_id, invoice_number, issue_date, due_date, status, subtotal, tax_amount, total_amount, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, $branchId, $data['customer_id'], $invNumber, $data['issue_date'], $data['due_date'], 
                $data['status'] ?? 'unpaid', $baseSubtotal, $taxAmount, $totalAmount, $data['notes'] ?? null
            ]);
            
            $invoiceId = $this->db->lastInsertId();

            if (!empty($data['items']) && is_array($data['items'])) {
                $lineStmt = $this->db->prepare("INSERT INTO sales_invoice_lines (invoice_id, product_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($data['items'] as $item) {
                    $qty = (float)($item['quantity'] ?? 0);
                    $priceInput = (float)($item['unit_price'] ?? 0);
                    if ($qty <= 0) continue;
                    
                    $basePrice = convert_to_base($priceInput);
                    $lineStmt->execute([
                        $invoiceId, 
                        empty($item['product_id']) ? null : $item['product_id'], 
                        $item['description'] ?? 'Item', 
                        $qty, $basePrice, ($qty * $basePrice)
                    ]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = "تم إصدار الفاتورة رقم $invNumber بنجاح!";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/sales/invoices/create');
        }

        return new RedirectResponse('/ERP/sales/invoices');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "SELECT * FROM sales_invoices WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $invoice = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$invoice) throw new Exception("الفاتورة غير موجودة.");

            $lineStmt = $this->db->prepare("SELECT * FROM sales_invoice_lines WHERE invoice_id = ?");
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
            $customers = $this->db->prepare($custSql);
            $customers->execute($custParams);
            $customers = $customers->fetchAll(PDO::FETCH_OBJ) ?: [];

            $prodSql = "SELECT id, sku, COALESCE(name_ar, name_en) as name, sale_price FROM inv_products WHERE is_active = 1 AND company_id = ?";
            $prodParams = [$companyId];
            if ($branchId) {
                $prodSql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $prodParams[] = $branchId;
            }
            $products = $this->db->prepare($prodSql);
            $products->execute($prodParams);
            $products = $products->fetchAll(PDO::FETCH_OBJ) ?: [];

            foreach ($products as $p) {
                $p->sale_price = convert_amount($p->sale_price ?? 0);
            }

        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/invoices');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/invoices/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $data = $request->getParsedBody();
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
            $taxAmount = $baseSubtotal * 0.15;
            $totalAmount = $baseSubtotal + $taxAmount;

            $sql = "UPDATE sales_invoices SET customer_id=?, issue_date=?, due_date=?, status=?, subtotal=?, tax_amount=?, total_amount=?, notes=? WHERE id=? AND company_id=?";
            $params = [
                $data['customer_id'], $data['issue_date'], $data['due_date'], 
                $data['status'] ?? 'unpaid', $baseSubtotal, $taxAmount, $totalAmount, $data['notes'] ?? null, $id, $companyId
            ];

            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $delStmt = $this->db->prepare("DELETE FROM sales_invoice_lines WHERE invoice_id = ?");
            $delStmt->execute([$id]);

            if (!empty($data['items']) && is_array($data['items'])) {
                $lineStmt = $this->db->prepare("INSERT INTO sales_invoice_lines (invoice_id, product_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($data['items'] as $item) {
                    $qty = (float)($item['quantity'] ?? 0);
                    $priceInput = (float)($item['unit_price'] ?? 0);
                    if ($qty <= 0) continue;
                    
                    $basePrice = convert_to_base($priceInput);
                    $lineStmt->execute([$id, empty($item['product_id']) ? null : $item['product_id'], $item['description'] ?? 'Item', $qty, $basePrice, ($qty * $basePrice)]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = "تم تحديث الفاتورة بنجاح!";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/sales/invoices/{$id}/edit");
        }
        return new RedirectResponse('/ERP/sales/invoices');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "SELECT * FROM sales_invoices WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $invoice = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$invoice) throw new Exception("الفاتورة غير موجودة.");

            // تحويلات للعرض
            $invoice->total_amount = convert_amount($invoice->total_amount ?? 0);
            $invoice->paid_amount  = convert_amount($invoice->paid_amount ?? 0);
            $invoice->subtotal     = convert_amount($invoice->subtotal ?? 0);
            $invoice->tax_amount   = convert_amount($invoice->tax_amount ?? 0);

            $custStmt = $this->db->prepare("SELECT * FROM customers WHERE id = ? AND company_id = ?");
            $custStmt->execute([$invoice->customer_id, $companyId]);
            $customer = $custStmt->fetch(PDO::FETCH_OBJ);

            $lineStmt = $this->db->prepare("SELECT * FROM sales_invoice_lines WHERE invoice_id = ?");
            $lineStmt->execute([$id]);
            $lines = $lineStmt->fetchAll(PDO::FETCH_OBJ);

            foreach ($lines as $line) {
                $line->unit_price = convert_amount($line->unit_price ?? 0);
                $line->total      = convert_amount($line->total ?? 0);
            }

            $payStmt = $this->db->prepare("SELECT * FROM sales_invoice_payments WHERE invoice_id = ? ORDER BY id DESC");
            $payStmt->execute([$id]);
            $payments = $payStmt->fetchAll(PDO::FETCH_OBJ);

            foreach ($payments as $pay) {
                $pay->amount = convert_amount($pay->amount ?? 0);
            }

        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/invoices');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/invoices/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function pay(Request $request, Response $response, $id = null): Response
    {
        $data = $request->getParsedBody();
        $isAr = isRtl();
        $companyId = current_company_id();

        try {
            $this->db->beginTransaction();

            $inputAmount = (float)($data['amount'] ?? 0);
            if ($inputAmount <= 0) {
                throw new Exception($isAr ? "يرجى إدخال مبلغ دفع صحيح أكبر من الصفر." : "Invalid payment amount.");
            }

            $baseAmount = convert_to_base($inputAmount);

            $payStmt = $this->db->prepare("
                INSERT INTO sales_invoice_payments (invoice_id, amount, payment_method, payment_date, notes) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $payStmt->execute([
                $id, 
                $baseAmount, 
                $data['payment_method'] ?? 'cash', 
                $data['payment_date'] ?? date('Y-m-d'), 
                $data['payment_notes'] ?? null
            ]);

            $sumStmt = $this->db->prepare("SELECT SUM(amount) FROM sales_invoice_payments WHERE invoice_id = ?");
            $sumStmt->execute([$id]);
            $totalPaid = (float)$sumStmt->fetchColumn();

            $invStmt = $this->db->prepare("SELECT total_amount FROM sales_invoices WHERE id = ? AND company_id = ?");
            $invStmt->execute([$id, $companyId]);
            $grandTotal = (float)$invStmt->fetchColumn();

            $newStatus = 'unpaid';
            if ($totalPaid >= ($grandTotal - 0.01)) {
                $newStatus = 'paid';
            } elseif ($totalPaid > 0) {
                $newStatus = 'partially_paid';
            }

            $updateStmt = $this->db->prepare("UPDATE sales_invoices SET paid_amount = ?, status = ? WHERE id = ? AND company_id = ?");
            $updateStmt->execute([$totalPaid, $newStatus, $id, $companyId]);

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم تسجيل الدفعة بمبلغ " . number_format($inputAmount, 2) . " وتحديث المتبقي بنجاح!" : "Payment recorded successfully!";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse("/ERP/sales/invoices/{$id}");
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "DELETE FROM sales_invoices WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $_SESSION['flash_msg'] = "تم حذف الفاتورة بنجاح.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "لا يمكن حذف الفاتورة لوجود حركات مالية مرتبطة بها.";
        }
        return new RedirectResponse('/ERP/sales/invoices');
    }
}