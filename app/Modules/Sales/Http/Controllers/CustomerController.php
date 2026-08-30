<?php
// Path: app/Modules/Sales/Http/Controllers/CustomerController.php

namespace App\Modules\Sales\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;

class CustomerController extends Controller
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
            $sql = "SELECT * FROM customers WHERE company_id = ?";
            $params = [$companyId];

            if ($branchId) {
                $sql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $params[] = $branchId;
            }
            $sql .= " ORDER BY id DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $customers = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            foreach ($customers as $c) {
                $c->credit_limit_converted = convert_amount($c->credit_limit ?? 0);
            }
        } catch (Exception $e) { $customers = []; }

        ob_start(); include $this->basePath . '/resources/views/sales/customers/index.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $customer = null;
        ob_start(); include $this->basePath . '/resources/views/sales/customers/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $isAr = isRtl();

        try {
            $companyId = current_company_id();
            $branchId  = current_branch();

            $code  = !empty($data['code']) ? trim($data['code']) : 'CUST-' . date('Ym') . rand(100, 999);
            $email = !empty($data['email']) ? trim($data['email']) : null;
            $phone = !empty($data['phone']) ? trim($data['phone']) : null;

            if ($email !== null || $phone !== null) {
                $sqlCheck = "SELECT id FROM customers WHERE ((email = ? AND email IS NOT NULL) OR (phone = ? AND phone IS NOT NULL)) AND company_id = ?";
                $paramsCheck = [$email, $phone, $companyId];
                if ($branchId) {
                    $sqlCheck .= " AND (branch_id = ? OR branch_id IS NULL)";
                    $paramsCheck[] = $branchId;
                }
                $sqlCheck .= " LIMIT 1";

                $stmt = $this->db->prepare($sqlCheck);
                $stmt->execute($paramsCheck);
                if ($stmt->fetch()) {
                    throw new Exception($isAr ? "البريد الإلكتروني أو رقم الهاتف مسجل لعميل آخر." : "Email or Phone already exists.");
                }
            }

            $inputCreditLimit = empty($data['credit_limit']) ? 0 : (float)$data['credit_limit'];
            $baseCreditLimit  = convert_to_base($inputCreditLimit);

            $stmt = $this->db->prepare("INSERT INTO customers (company_id, branch_id, code, name_en, name_ar, email, phone, tax_number, address, credit_limit, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$companyId, $branchId, $code, !empty($data['name_en']) ? $data['name_en'] : $data['name_ar'], !empty($data['name_ar']) ? $data['name_ar'] : $data['name_en'], $email, $phone, $data['tax_number'] ?? null, $data['address'] ?? null, $baseCreditLimit, isset($data['is_active']) ? (int)$data['is_active'] : 1]);

            $_SESSION['flash_msg'] = $isAr ? "تم تسجيل العميل بنجاح!" : "Customer added successfully!";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/customers/create');
        }
        return new RedirectResponse('/ERP/sales/customers');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "SELECT * FROM customers WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $customer = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$customer) throw new Exception("العميل غير موجود.");

            $customer->credit_limit_converted = convert_amount($customer->credit_limit ?? 0);
        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/customers');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/customers/create.php'; $content = ob_get_clean();
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
            $email = !empty($data['email']) ? trim($data['email']) : null;
            $phone = !empty($data['phone']) ? trim($data['phone']) : null;

            if ($email !== null || $phone !== null) {
                $stmt = $this->db->prepare("SELECT id FROM customers WHERE ((email = ? AND email IS NOT NULL) OR (phone = ? AND phone IS NOT NULL)) AND id != ? AND company_id = ? LIMIT 1");
                $stmt->execute([$email, $phone, $id, $companyId]);
                if ($stmt->fetch()) {
                    throw new Exception($isAr ? "البريد الإلكتروني أو الهاتف مسجل لعميل آخر." : "Email or phone registered to another customer.");
                }
            }

            $inputCreditLimit = empty($data['credit_limit']) ? 0 : (float)$data['credit_limit'];
            $baseCreditLimit  = convert_to_base($inputCreditLimit);

            $sql = "UPDATE customers SET name_en=?, name_ar=?, email=?, phone=?, tax_number=?, address=?, credit_limit=?, is_active=? WHERE id=? AND company_id=?";
            $params = [$data['name_en'], $data['name_ar'], $email, $phone, $data['tax_number'] ?? null, $data['address'] ?? null, $baseCreditLimit, isset($data['is_active']) ? (int)$data['is_active'] : 1, $id, $companyId];

            if ($branchId) {
                $sql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات العميل بنجاح." : "Customer updated successfully.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/sales/customers/{$id}/edit");
        }
        return new RedirectResponse('/ERP/sales/customers');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "SELECT * FROM customers WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $customer = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$customer) throw new Exception("العميل غير موجود.");

            $customer->credit_limit_converted = convert_amount($customer->credit_limit ?? 0);

            $stats = (object)['orders_count' => 0, 'total_spent' => 0, 'total_spent_converted' => 0];
            try {
                $sqlStats = "SELECT COUNT(id) as orders_count, SUM(total_amount) as total_spent FROM sales_invoices WHERE customer_id = ? AND status != 'cancelled' AND company_id = ?";
                $paramsStats = [$id, $companyId];
                if ($branchId) {
                    $sqlStats .= " AND branch_id = ?";
                    $paramsStats[] = $branchId;
                }

                $statsStmt = $this->db->prepare($sqlStats);
                $statsStmt->execute($paramsStats);
                $fetchedStats = $statsStmt->fetch(PDO::FETCH_OBJ);
                if ($fetchedStats) {
                    $stats->orders_count = $fetchedStats->orders_count ?? 0;
                    $stats->total_spent_converted = convert_amount($fetchedStats->total_spent ?? 0);
                }
            } catch (Exception $ex) {}
        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/customers');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/customers/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "DELETE FROM customers WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $_SESSION['flash_msg'] = "تم حذف العميل بنجاح.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "لا يمكن حذف هذا العميل لوجود حركات مالية مسجلة باسمه.";
        }
        return new RedirectResponse('/ERP/sales/customers');
    }
}