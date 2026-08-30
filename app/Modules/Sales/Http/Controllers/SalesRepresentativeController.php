<?php
// Path: app/Modules/Sales/Http/Controllers/SalesRepresentativeController.php

namespace App\Modules\Sales\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;

class SalesRepresentativeController extends Controller
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
        $dbError   = null;

        try {
            $sql = "SELECT * FROM sales_representatives WHERE company_id = ?";
            $params = [$companyId];

            if ($branchId) {
                $sql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $params[] = $branchId;
            }
            $sql .= " ORDER BY id DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $representatives = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            foreach ($representatives as $r) {
                $r->target_amount = convert_amount($r->target_amount ?? 0);
            }
        } catch (Exception $e) {
            $representatives = [];
            $dbError = $e->getMessage();
        }

        ob_start(); include $this->basePath . '/resources/views/sales/representatives/index.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $representative = null;
        ob_start(); include $this->basePath . '/resources/views/sales/representatives/create.php'; $content = ob_get_clean();
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
            $code  = !empty($data['code']) ? trim($data['code']) : 'REP-' . rand(100, 999);
            $email = !empty($data['email']) ? trim($data['email']) : null;
            $phone = !empty($data['phone']) ? trim($data['phone']) : null;

            if ($email !== null || $phone !== null) {
                $sqlCheck = "SELECT id FROM sales_representatives WHERE ((email = ? AND email IS NOT NULL) OR (phone = ? AND phone IS NOT NULL)) AND company_id = ?";
                $paramsCheck = [$email, $phone, $companyId];
                if ($branchId) {
                    $sqlCheck .= " AND (branch_id = ? OR branch_id IS NULL)";
                    $paramsCheck[] = $branchId;
                }
                $sqlCheck .= " LIMIT 1";

                $stmt = $this->db->prepare($sqlCheck);
                $stmt->execute($paramsCheck);
                if ($stmt->fetch()) {
                    throw new Exception($isAr ? "البريد الإلكتروني أو الهاتف مسجل لمندوب آخر." : "Email or Phone already exists.");
                }
            }

            $targetBase = convert_to_base((float)($data['target_amount'] ?? 0));

            $stmt = $this->db->prepare("
                INSERT INTO sales_representatives (company_id, branch_id, code, name_ar, name_en, email, phone, commission_rate, target_amount, is_active, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, $branchId, $code,
                $data['name_ar'], $data['name_en'] ?? $data['name_ar'],
                $email, $phone,
                empty($data['commission_rate']) ? 0 : (float)$data['commission_rate'],
                $targetBase,
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $data['notes'] ?? null
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم إضافـة مندوب المبيعات بنجاح!" : "Sales Representative created successfully!";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/sales/representatives/create');
        }

        return new RedirectResponse('/ERP/sales/representatives');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "SELECT * FROM sales_representatives WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $representative = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$representative) throw new Exception("مندوب المبيعات غير موجود.");

            $representative->target_amount = convert_amount($representative->target_amount ?? 0);
        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/representatives');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/representatives/create.php'; $content = ob_get_clean();
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
                $sqlCheck = "SELECT id FROM sales_representatives WHERE ((email = ? AND email IS NOT NULL) OR (phone = ? AND phone IS NOT NULL)) AND id != ? AND company_id = ?";
                $paramsCheck = [$email, $phone, $id, $companyId];
                if ($branchId) {
                    $sqlCheck .= " AND (branch_id = ? OR branch_id IS NULL)";
                    $paramsCheck[] = $branchId;
                }
                $sqlCheck .= " LIMIT 1";

                $stmt = $this->db->prepare($sqlCheck);
                $stmt->execute($paramsCheck);
                if ($stmt->fetch()) {
                    throw new Exception($isAr ? "البريد الإلكتروني أو الهاتف مسجل لمندوب آخر." : "Email or Phone already registered.");
                }
            }

            $targetBase = convert_to_base((float)($data['target_amount'] ?? 0));

            $sql = "
                UPDATE sales_representatives 
                SET name_ar=?, name_en=?, email=?, phone=?, commission_rate=?, target_amount=?, is_active=?, notes=?
                WHERE id=? AND company_id=?
            ";
            $params = [
                $data['name_ar'], $data['name_en'] ?? $data['name_ar'],
                $email, $phone,
                empty($data['commission_rate']) ? 0 : (float)$data['commission_rate'],
                $targetBase, isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $data['notes'] ?? null, $id, $companyId
            ];

            if ($branchId) {
                $sql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات المندوب بنجاح." : "Sales Representative updated successfully.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/sales/representatives/{$id}/edit");
        }

        return new RedirectResponse('/ERP/sales/representatives');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "SELECT * FROM sales_representatives WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $representative = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$representative) throw new Exception("مندوب المبيعات غير موجود.");

            $representative->target_amount = convert_amount($representative->target_amount ?? 0);

            $stats = (object)['total_sales' => 0, 'invoices_count' => 0, 'earned_commission' => 0];
            try {
                $stSql = "
                    SELECT COUNT(id) as invoices_count, COALESCE(SUM(total_amount), 0) as total_sales 
                    FROM sales_invoices 
                    WHERE status != 'cancelled' AND sales_rep_id = ? AND company_id = ?
                ";
                $stParams = [$id, $companyId];
                if ($branchId) {
                    $stSql .= " AND branch_id = ?";
                    $stParams[] = $branchId;
                }

                $stStmt = $this->db->prepare($stSql);
                $stStmt->execute($stParams);
                $fetched = $stStmt->fetch(PDO::FETCH_OBJ);

                if ($fetched) {
                    $stats->invoices_count = $fetched->invoices_count ?? 0;
                    $stats->total_sales = convert_amount((float)$fetched->total_sales);
                    $stats->earned_commission = ($stats->total_sales * ($representative->commission_rate / 100));
                }
            } catch (Exception $ex) {}

        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/representatives');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/representatives/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "DELETE FROM sales_representatives WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $_SESSION['flash_msg'] = "تم حذف مندوب المبيعات بنجاح.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف لوجود فواتير أو عمليات مرتبطة بالمندوب.";
        }
        return new RedirectResponse('/ERP/sales/representatives');
    }
}