<?php
// Path: app/Modules/Sales/Http/Controllers/QuotationController.php

namespace App\Modules\Sales\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;

class QuotationController extends Controller
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
        $dbError   = null;

        try {
            $sql = "
                SELECT q.*, c.name_en as customer_name, c.name_ar as customer_name_ar 
                FROM sales_quotations q 
                LEFT JOIN customers c ON q.customer_id = c.id 
                WHERE q.company_id = ?
            ";
            $params = [$companyId];

            if ($branchId) {
                $sql .= " AND q.branch_id = ?";
                $params[] = $branchId;
            }

            $sql .= " ORDER BY q.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $quotations = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            foreach ($quotations as $q) {
                $q->total_amount_converted = convert_amount($q->total_amount ?? 0);
                $q->subtotal_converted     = convert_amount($q->subtotal ?? 0);
                $q->tax_amount_converted   = convert_amount($q->tax_amount ?? 0);
            }

        } catch (Exception $e) {
            $quotations = [];
            $dbError = $e->getMessage();
        }

        ob_start();
        $viewPath = $this->basePath . '/resources/views/sales/quotations/index.php';
        if (file_exists($viewPath)) {
            if ($dbError) echo "<div style='background:#fef2f2; color:#b91c1c; padding:20px; border-radius:8px;'><strong>DB Error:</strong> $dbError</div>";
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

        $quotation = null; 
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

        ob_start(); include $this->basePath . '/resources/views/sales/quotations/create.php'; $content = ob_get_clean();
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

            $quoteNumber = !empty($data['quote_number']) ? $data['quote_number'] : 'QT-' . date('Ym') . rand(1000, 9999);
            
            $inputSubtotal = 0;
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $qty   = (float)($item['quantity'] ?? 0);
                    $price = (float)($item['unit_price'] ?? 0);
                    $inputSubtotal += ($qty * $price);
                }
            }

            $baseSubtotal = convert_to_base($inputSubtotal);
            $baseTax      = $baseSubtotal * 0.15;
            $baseTotal    = $baseSubtotal + $baseTax;

            $stmt = $this->db->prepare("
                INSERT INTO sales_quotations (company_id, branch_id, customer_id, quote_number, issue_date, expiry_date, status, subtotal, tax_amount, total_amount, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, $branchId, $data['customer_id'], $quoteNumber, $data['issue_date'], $data['expiry_date'], 
                $data['status'] ?? 'draft', $baseSubtotal, $baseTax, $baseTotal, $data['notes'] ?? null
            ]);
            
            $quoteId = $this->db->lastInsertId();

            if (!empty($data['items']) && is_array($data['items'])) {
                $lineStmt = $this->db->prepare("INSERT INTO sales_quotation_lines (quotation_id, product_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($data['items'] as $item) {
                    $qty   = (float)($item['quantity'] ?? 0);
                    $priceInput = (float)($item['unit_price'] ?? 0);
                    if ($qty <= 0) continue;
                    
                    $baseUnitPrice = convert_to_base($priceInput);
                    $baseLineTotal = $qty * $baseUnitPrice;

                    $lineStmt->execute([
                        $quoteId, 
                        empty($item['product_id']) ? null : $item['product_id'], 
                        $item['description'] ?? 'Item', 
                        $qty, $baseUnitPrice, $baseLineTotal
                    ]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = "تم إنشاء عرض السعر رقم $quoteNumber بنجاح!";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/sales/quotations/create');
        }

        return new RedirectResponse('/ERP/sales/quotations');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "SELECT * FROM sales_quotations WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $quotation = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$quotation) throw new Exception("عرض السعر غير موجود لهذا الفرع.");

            $quotation->subtotal_converted   = convert_amount($quotation->subtotal ?? 0);
            $quotation->tax_amount_converted = convert_amount($quotation->tax_amount ?? 0);
            $quotation->total_amount_converted = convert_amount($quotation->total_amount ?? 0);

            $lineStmt = $this->db->prepare("SELECT * FROM sales_quotation_lines WHERE quotation_id = ?");
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
            return new RedirectResponse('/ERP/sales/quotations');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/quotations/create.php'; $content = ob_get_clean();
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
                    $qty   = (float)($item['quantity'] ?? 0);
                    $price = (float)($item['unit_price'] ?? 0);
                    $inputSubtotal += ($qty * $price);
                }
            }

            $baseSubtotal = convert_to_base($inputSubtotal);
            $baseTax      = $baseSubtotal * 0.15;
            $baseTotal    = $baseSubtotal + $baseTax;

            $sql = "UPDATE sales_quotations SET customer_id=?, issue_date=?, expiry_date=?, status=?, subtotal=?, tax_amount=?, total_amount=?, notes=? WHERE id=? AND company_id=?";
            $params = [
                $data['customer_id'], $data['issue_date'], $data['expiry_date'], 
                $data['status'] ?? 'draft', $baseSubtotal, $baseTax, $baseTotal, $data['notes'] ?? null, $id, $companyId
            ];

            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $delStmt = $this->db->prepare("DELETE FROM sales_quotation_lines WHERE quotation_id = ?");
            $delStmt->execute([$id]);

            if (!empty($data['items']) && is_array($data['items'])) {
                $lineStmt = $this->db->prepare("INSERT INTO sales_quotation_lines (quotation_id, product_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($data['items'] as $item) {
                    $qty   = (float)($item['quantity'] ?? 0);
                    $priceInput = (float)($item['unit_price'] ?? 0);
                    if ($qty <= 0) continue;

                    $baseUnitPrice = convert_to_base($priceInput);
                    $baseLineTotal = $qty * $baseUnitPrice;

                    $lineStmt->execute([$id, empty($item['product_id']) ? null : $item['product_id'], $item['description'] ?? 'Item', $qty, $baseUnitPrice, $baseLineTotal]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = "تم تحديث عرض السعر بنجاح!";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/sales/quotations/{$id}/edit");
        }
        return new RedirectResponse('/ERP/sales/quotations');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "SELECT * FROM sales_quotations WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $quotation = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$quotation) throw new Exception("عرض السعر غير موجود.");

            $quotation->subtotal_converted   = convert_amount($quotation->subtotal ?? 0);
            $quotation->tax_amount_converted = convert_amount($quotation->tax_amount ?? 0);
            $quotation->total_amount_converted = convert_amount($quotation->total_amount ?? 0);

            $custStmt = $this->db->prepare("SELECT * FROM customers WHERE id = ? AND company_id = ?");
            $custStmt->execute([$quotation->customer_id, $companyId]);
            $customer = $custStmt->fetch(PDO::FETCH_OBJ);

            $lineStmt = $this->db->prepare("SELECT * FROM sales_quotation_lines WHERE quotation_id = ?");
            $lineStmt->execute([$id]);
            $lines = $lineStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            foreach ($lines as $line) {
                $line->unit_price_converted = convert_amount($line->unit_price ?? 0);
                $line->total_converted      = convert_amount($line->total ?? 0);
            }

        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/quotations');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/quotations/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "DELETE FROM sales_quotations WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $_SESSION['flash_msg'] = "تم حذف عرض السعر بنجاح.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف لوجود عمليات مرتبطة به.";
        }
        return new RedirectResponse('/ERP/sales/quotations');
    }
}