<?php
// Path: app/Modules/Sales/Http/Controllers/SalesContractController.php

namespace App\Modules\Sales\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;

class SalesContractController extends Controller
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
                SELECT sc.*, COALESCE(c.name_ar, c.name_en) as customer_name
                FROM sales_contracts sc
                LEFT JOIN customers c ON sc.customer_id = c.id
                WHERE sc.company_id = ?
            ";
            $params = [$companyId];

            if ($branchId) {
                $sql .= " AND sc.branch_id = ?";
                $params[] = $branchId;
            }
            $sql .= " ORDER BY sc.id DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $contracts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            foreach ($contracts as $c) {
                $c->total_value = convert_amount($c->total_value);
            }
        } catch (Exception $e) {
            $contracts = [];
            $dbError = $e->getMessage();
        }

        ob_start(); include $this->basePath . '/resources/views/sales/contracts/index.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();
        $contract = null;

        try {
            $sql = "SELECT id, name_en, name_ar FROM customers WHERE is_active = 1 AND company_id = ?";
            $params = [$companyId];
            if ($branchId) {
                $sql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $params[] = $branchId;
            }
            $sql .= " ORDER BY id DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $customers = $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            $customers = [];
        }

        ob_start(); include $this->basePath . '/resources/views/sales/contracts/create.php'; $content = ob_get_clean();
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
            $contractNum = !empty($data['contract_number']) ? trim($data['contract_number']) : 'CNT-' . date('Ym') . rand(1000, 9999);
            $baseValue = convert_to_base((float)($data['total_value'] ?? 0));

            $stmt = $this->db->prepare("
                INSERT INTO sales_contracts (company_id, branch_id, customer_id, contract_number, title, start_date, end_date, billing_frequency, total_value, status, terms_conditions)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, $branchId, $data['customer_id'], $contractNum, $data['title'],
                $data['start_date'], $data['end_date'], $data['billing_frequency'] ?? 'monthly',
                $baseValue, $data['status'] ?? 'draft', $data['terms_conditions'] ?? null
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم إبرام عقد المبيعات رقم $contractNum بنجاح!" : "Sales Contract created successfully!";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/sales/contracts/create');
        }

        return new RedirectResponse('/ERP/sales/contracts');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "SELECT * FROM sales_contracts WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $contract = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$contract) throw new Exception("العقد غير موجود.");

            $contract->total_value = convert_amount($contract->total_value);

            $custSql = "SELECT id, name_en, name_ar FROM customers WHERE is_active = 1 AND company_id = ?";
            $custParams = [$companyId];
            if ($branchId) {
                $custSql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $custParams[] = $branchId;
            }
            $custSql .= " ORDER BY id DESC";

            $stmtCust = $this->db->prepare($custSql);
            $stmtCust->execute($custParams);
            $customers = $stmtCust->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/contracts');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/contracts/create.php'; $content = ob_get_clean();
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
            $baseValue = convert_to_base((float)($data['total_value'] ?? 0));

            $sql = "
                UPDATE sales_contracts 
                SET customer_id=?, title=?, start_date=?, end_date=?, billing_frequency=?, total_value=?, status=?, terms_conditions=?
                WHERE id=? AND company_id=?
            ";
            $params = [
                $data['customer_id'], $data['title'], $data['start_date'], $data['end_date'],
                $data['billing_frequency'] ?? 'monthly', $baseValue,
                $data['status'] ?? 'draft', $data['terms_conditions'] ?? null, $id, $companyId
            ];

            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث العقد بنجاح." : "Contract updated successfully.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/sales/contracts/{$id}/edit");
        }

        return new RedirectResponse('/ERP/sales/contracts');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "
                SELECT sc.*, 
                       COALESCE(c.name_ar, c.name_en) as customer_name, c.phone as customer_phone, c.address as customer_address, c.tax_number as customer_tax
                FROM sales_contracts sc
                LEFT JOIN customers c ON sc.customer_id = c.id
                WHERE sc.id = ? AND sc.company_id = ?
            ";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND sc.branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $contract = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$contract) throw new Exception("العقد غير موجود.");

            $contract->total_value = convert_amount($contract->total_value);

        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/contracts');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/contracts/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "DELETE FROM sales_contracts WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $_SESSION['flash_msg'] = "تم حذف العقد بنجاح.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف لوجود عمليات مرتبطة بهذا العقد.";
        }
        return new RedirectResponse('/ERP/sales/contracts');
    }
}