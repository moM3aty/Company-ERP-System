<?php
//app/Modules/Sales/Http/Controllers/SalesReceiptController.php

namespace App\Modules\Sales\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;

class SalesReceiptController extends Controller
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
                FROM sales_receipts r
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
            $receipts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Exception $e) {
            $receipts = [];
            $dbError = $e->getMessage();
        }

        ob_start(); include $this->basePath . '/resources/views/sales/receipts/index.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();
        $receipt = null;

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
            
            $invSql = "
                SELECT id, customer_id, invoice_number, total_amount, 
                       COALESCE(paid_amount, 0) as paid_amount, 
                       (total_amount - COALESCE(paid_amount, 0)) as due_amount 
                FROM sales_invoices 
                WHERE LOWER(status) NOT IN ('paid', 'cancelled') 
                  AND (total_amount - COALESCE(paid_amount, 0)) > 0
                  AND company_id = ?
            ";
            $invParams = [$companyId];
            if ($branchId) {
                $invSql .= " AND branch_id = ?";
                $invParams[] = $branchId;
            }
            $invSql .= " ORDER BY id DESC";

            $invStmt = $this->db->prepare($invSql);
            $invStmt->execute($invParams);
            $invoices = $invStmt->fetchAll(PDO::FETCH_OBJ);

            // تحويل المستحقات لعرضها بالعملة الحالية
            foreach ($invoices as $inv) {
                $inv->due_amount = convert_amount((float)$inv->due_amount);
                $inv->total_amount = convert_amount((float)$inv->total_amount);
            }

        } catch (Exception $e) {
            $_SESSION['flash_err'] = "خطأ في جلب البيانات: " . $e->getMessage();
            $customers = []; $invoices = [];
        }

        ob_start(); include $this->basePath . '/resources/views/sales/receipts/create.php'; $content = ob_get_clean();
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

            $receiptNum = !empty($data['receipt_number']) ? $data['receipt_number'] : 'RCT-' . date('Ym') . rand(1000, 9999);
            $inputAmount = (float)($data['amount'] ?? 0);
            $invoiceId = !empty($data['invoice_id']) ? $data['invoice_id'] : null;

            if ($inputAmount <= 0) throw new Exception($isAr ? "يرجى إدخال مبلغ صحيح للسند." : "Invalid amount.");

            $baseAmount = convert_to_base($inputAmount);

            $stmt = $this->db->prepare("
                INSERT INTO sales_receipts (company_id, branch_id, receipt_number, customer_id, invoice_id, amount, payment_method, receipt_date, reference_no, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, $branchId, $receiptNum, $data['customer_id'], $invoiceId, $baseAmount,
                $data['payment_method'] ?? 'cash', $data['receipt_date'], $data['reference_no'] ?? null, $data['notes'] ?? null
            ]);

            if ($invoiceId) {
                $payStmt = $this->db->prepare("INSERT INTO sales_invoice_payments (invoice_id, amount, payment_method, payment_date, notes) VALUES (?, ?, ?, ?, ?)");
                $payStmt->execute([$invoiceId, $baseAmount, $data['payment_method'] ?? 'cash', $data['receipt_date'], "سند قبض رقم: {$receiptNum}"]);

                $sumStmt = $this->db->prepare("SELECT SUM(amount) FROM sales_invoice_payments WHERE invoice_id = ?");
                $sumStmt->execute([$invoiceId]);
                $totalPaid = (float)$sumStmt->fetchColumn();

                $invStmt = $this->db->prepare("SELECT total_amount FROM sales_invoices WHERE id = ?");
                $invStmt->execute([$invoiceId]);
                $grandTotal = (float)$invStmt->fetchColumn();

                $newStatus = ($totalPaid >= ($grandTotal - 0.01)) ? 'paid' : 'partially_paid';

                $upStmt = $this->db->prepare("UPDATE sales_invoices SET paid_amount = ?, status = ? WHERE id = ?");
                $upStmt->execute([$totalPaid, $newStatus, $invoiceId]);
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم إصدار سند القبض بنجاح!" : "Receipt issued successfully!";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/sales/receipts/create');
        }

        return new RedirectResponse('/ERP/sales/receipts');
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
                FROM sales_receipts r
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
            $receipt = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$receipt) throw new Exception("سند القبض غير موجود.");

            // تحويل للعملة الحالية
            $receipt->amount = convert_amount($receipt->amount);

        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/receipts');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/receipts/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        try {
            $stmt = $this->db->prepare("DELETE FROM sales_receipts WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف سند القبض بنجاح.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "لا يمكن حذف السند لربطه بفرع مالي أخر.";
        }
        return new RedirectResponse('/ERP/sales/receipts');
    }
}