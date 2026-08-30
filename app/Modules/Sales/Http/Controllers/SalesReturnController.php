<?php
//app/Modules/Sales/Http/Controllers/SalesReturnController.php
namespace App\Modules\Sales\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;

class SalesReturnController extends Controller
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
        $dbError = null;

        try {
            $sql = "
                SELECT r.*, 
                       COALESCE(c.name_ar, c.name_en) as customer_name,
                       i.invoice_number
                FROM sales_returns r
                LEFT JOIN customers c ON r.customer_id = c.id
                LEFT JOIN sales_invoices i ON r.invoice_id = i.id
                WHERE r.company_id = ?
            ";
            $params = [$companyId];

            if ($branchId) {
                $sql .= " AND r.branch_id = ?";
                $params[] = $branchId;
            }
            $sql .= " ORDER BY r.id DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $returns = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Exception $e) {
            $returns = [];
            $dbError = $e->getMessage();
        }

        ob_start(); include $this->basePath . '/resources/views/sales/returns/index.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();
        $return = null; $lines = [];

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

            $invStmt = $this->db->prepare("SELECT id, customer_id, invoice_number, total_amount FROM sales_invoices WHERE company_id = ?");
            $invStmt->execute([$companyId]);
            $invoices = $invStmt->fetchAll(PDO::FETCH_OBJ);

            $prodStmt = $this->db->prepare("SELECT id, sku, COALESCE(name_ar, name_en) as name, sale_price FROM inv_products WHERE is_active = 1 AND company_id = ?");
            $prodStmt->execute([$companyId]);
            $products = $prodStmt->fetchAll(PDO::FETCH_OBJ);

            foreach($products as $p) {
                $p->sale_price = convert_amount($p->sale_price);
            }

        } catch (Exception $e) {
            $customers = []; $invoices = []; $products = [];
        }

        ob_start(); include $this->basePath . '/resources/views/sales/returns/create.php'; $content = ob_get_clean();
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

            $returnNum = !empty($data['return_number']) ? $data['return_number'] : 'SRN-' . date('Ym') . rand(1000, 9999);
            $invoiceId = !empty($data['invoice_id']) ? $data['invoice_id'] : null;

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
                INSERT INTO sales_returns (company_id, branch_id, customer_id, invoice_id, return_number, return_date, status, subtotal, tax_amount, total_amount, reason)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, $branchId, $data['customer_id'], $invoiceId, $returnNum, $data['return_date'],
                $data['status'] ?? 'draft', $baseSubtotal, $baseTax, $baseTotal, $data['reason'] ?? null
            ]);

            $returnId = $this->db->lastInsertId();

            if (!empty($data['items']) && is_array($data['items'])) {
                $lineStmt = $this->db->prepare("INSERT INTO sales_return_lines (return_id, product_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($data['items'] as $item) {
                    $qty = (float)($item['quantity'] ?? 0);
                    $priceInput = (float)($item['unit_price'] ?? 0);
                    if ($qty <= 0) continue;

                    $basePrice = convert_to_base($priceInput);

                    $lineStmt->execute([
                        $returnId, empty($item['product_id']) ? null : $item['product_id'],
                        $item['description'] ?? 'Returned Item', $qty, $basePrice, ($qty * $basePrice)
                    ]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم إشعار مرتجع المبيعات بنجاح!" : "Sales return created successfully!";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/sales/returns/create');
        }

        return new RedirectResponse('/ERP/sales/returns');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "SELECT * FROM sales_returns WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $return = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$return) throw new Exception("مرتجع المبيعات غير موجود.");

            $lineStmt = $this->db->prepare("SELECT * FROM sales_return_lines WHERE return_id = ?");
            $lineStmt->execute([$id]);
            $lines = $lineStmt->fetchAll(PDO::FETCH_OBJ);

            foreach ($lines as $line) {
                $line->unit_price = convert_amount($line->unit_price);
                $line->total      = convert_amount($line->total);
            }

            $customers = $this->db->query("SELECT id, name_en, name_ar FROM customers WHERE is_active = 1 AND company_id = $companyId")->fetchAll(PDO::FETCH_OBJ);
            $invoices  = $this->db->query("SELECT id, customer_id, invoice_number, total_amount FROM sales_invoices WHERE company_id = $companyId")->fetchAll(PDO::FETCH_OBJ);
            $products  = $this->db->query("SELECT id, sku, COALESCE(name_ar, name_en) as name, sale_price FROM inv_products WHERE is_active = 1 AND company_id = $companyId")->fetchAll(PDO::FETCH_OBJ);
            
            foreach($products as $p) $p->sale_price = convert_amount($p->sale_price);

        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/returns');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/returns/create.php'; $content = ob_get_clean();
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

            $sql = "UPDATE sales_returns SET customer_id=?, invoice_id=?, return_date=?, status=?, subtotal=?, tax_amount=?, total_amount=?, reason=? WHERE id=? AND company_id=?";
            $params = [
                $data['customer_id'], empty($data['invoice_id']) ? null : $data['invoice_id'], $data['return_date'],
                $data['status'] ?? 'draft', $baseSubtotal, $baseTax, $baseTotal, $data['reason'] ?? null, $id, $companyId
            ];

            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $this->db->prepare("DELETE FROM sales_return_lines WHERE return_id = ?")->execute([$id]);

            if (!empty($data['items']) && is_array($data['items'])) {
                $lineStmt = $this->db->prepare("INSERT INTO sales_return_lines (return_id, product_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($data['items'] as $item) {
                    $qty = (float)($item['quantity'] ?? 0);
                    $priceInput = (float)($item['unit_price'] ?? 0);
                    if ($qty <= 0) continue;

                    $basePrice = convert_to_base($priceInput);
                    $lineStmt->execute([$id, empty($item['product_id']) ? null : $item['product_id'], $item['description'] ?? 'Returned Item', $qty, $basePrice, ($qty * $basePrice)]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم تحديث مرتجع المبيعات بنجاح." : "Sales return updated successfully.";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/sales/returns/{$id}/edit");
        }

        return new RedirectResponse('/ERP/sales/returns');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "
                SELECT r.*, 
                       COALESCE(c.name_ar, c.name_en) as customer_name, c.phone as customer_phone, c.address as customer_address, c.tax_number as customer_tax,
                       i.invoice_number 
                FROM sales_returns r
                LEFT JOIN customers c ON r.customer_id = c.id
                LEFT JOIN sales_invoices i ON r.invoice_id = i.id
                WHERE r.id = ? AND r.company_id = ?
            ";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND r.branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $return = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$return) throw new Exception("مرتجع المبيعات غير موجود.");

            $return->subtotal = convert_amount($return->subtotal);
            $return->tax_amount = convert_amount($return->tax_amount);
            $return->total_amount = convert_amount($return->total_amount);

            $lineStmt = $this->db->prepare("SELECT * FROM sales_return_lines WHERE return_id = ?");
            $lineStmt->execute([$id]);
            $lines = $lineStmt->fetchAll(PDO::FETCH_OBJ);

            foreach ($lines as $line) {
                $line->unit_price = convert_amount($line->unit_price);
                $line->total      = convert_amount($line->total);
            }

        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/returns');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/returns/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        try {
            $stmt = $this->db->prepare("DELETE FROM sales_returns WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف إشعار المرتجع بنجاح.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف لوجود قيود مالية مرتبطة بهذه الحركة.";
        }
        return new RedirectResponse('/ERP/sales/returns');
    }
}