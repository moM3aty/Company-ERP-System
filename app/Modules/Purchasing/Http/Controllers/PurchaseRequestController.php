<?php
// Path: app/Modules/Purchasing/Http/Controllers/PurchaseRequestController.php

namespace App\Modules\Purchasing\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class PurchaseRequestController extends Controller
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
            $whereClause = "WHERE company_id = ?";
            $params = [$companyId];

            if ($branchId > 0) {
                $whereClause .= " AND (branch_id = ? OR branch_id IS NULL OR branch_id = 0)";
                $params[] = $branchId;
            }

            if ($search !== '') {
                $whereClause .= " AND (pr_number LIKE ? OR department LIKE ? OR requested_by LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like]);
            }

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM purchase_requests $whereClause");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT *, (SELECT COUNT(id) FROM purchase_request_items WHERE pr_id = purchase_requests.id) as items_count
                FROM purchase_requests $whereClause
                ORDER BY id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $requests = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            // تحويل القيمة التقديرية حسب العملة المحددة
            foreach ($requests as $r) {
                $r->total_estimated_value = convert_amount($r->total_estimated_value);
            }

        } catch (Throwable $e) {
            $requests = [];
            $totalPages = 1;
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/purchasing/requisitions/index.php';
        if (file_exists($viewPath)) include $viewPath;
        
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        $pr = null; $items = []; $products = [];
        try {
            $products = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products WHERE company_id = $companyId ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); include $this->basePath . '/resources/views/purchasing/requisitions/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        try {
            $this->db->beginTransaction();

            $prNum = !empty($data['pr_number']) ? trim($data['pr_number']) : 'PR-' . date('ymd') . '-' . rand(10, 99);

            $totalEstimated = 0;
            if (!empty($data['description']) && is_array($data['description'])) {
                foreach ($data['description'] as $idx => $desc) {
                    $qty = (float)($data['quantity'][$idx] ?? 1);
                    $price = (float)($data['estimated_price'][$idx] ?? 0);
                    $totalEstimated += ($qty * $price);
                }
            }

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO purchase_requests (company_id, branch_id, pr_number, request_date, required_date, department, requested_by, status, total_estimated_value, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $branchId, $prNum, $data['request_date'], $data['required_date'],
                    $data['department'] ?? null, $data['requested_by'] ?? null,
                    $data['status'] ?? 'pending', $totalEstimated, $data['notes'] ?? null
                ]);
            } catch (\PDOException $ex) {
                // احتياطي في حال عدم وجود عمود branch_id في الهيكل القديم
                $stmt = $this->db->prepare("
                    INSERT INTO purchase_requests (company_id, pr_number, request_date, required_date, department, requested_by, status, total_estimated_value, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $prNum, $data['request_date'], $data['required_date'],
                    $data['department'] ?? null, $data['requested_by'] ?? null,
                    $data['status'] ?? 'pending', $totalEstimated, $data['notes'] ?? null
                ]);
            }
            $prId = $this->db->lastInsertId();

            if (!empty($data['description']) && is_array($data['description'])) {
                $stmtItem = $this->db->prepare("INSERT INTO purchase_request_items (pr_id, product_id, description, quantity, estimated_price) VALUES (?, ?, ?, ?, ?)");
                foreach ($data['description'] as $idx => $desc) {
                    if (empty($desc)) continue;
                    $prodId = !empty($data['product_id'][$idx]) ? $data['product_id'][$idx] : null;
                    $stmtItem->execute([$prId, $prodId, $desc, (float)($data['quantity'][$idx] ?? 1), (float)($data['estimated_price'][$idx] ?? 0)]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم حفظ طلب الشراء بنجاح." : "PR saved successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/requisitions/create');
        }

        return new RedirectResponse('/ERP/purchasing/requisitions');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $stmt = $this->db->prepare("SELECT * FROM purchase_requests WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $requestData = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$requestData) throw new Exception("طلب الشراء غير موجود.");

            $stmtItems = $this->db->prepare("SELECT i.*, p.item_code as product_code FROM purchase_request_items i LEFT JOIN products p ON i.product_id = p.id WHERE i.pr_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            // تحويل القيم المالية بحسب العملة الحالية
            $requestData->total_estimated_value = convert_amount($requestData->total_estimated_value);
            foreach ($items as $item) {
                $item->estimated_price = convert_amount($item->estimated_price);
            }

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/requisitions');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/requisitions/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $stmt = $this->db->prepare("SELECT * FROM purchase_requests WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $pr = $stmt->fetch(PDO::FETCH_OBJ); 
            if (!$pr) throw new Exception("طلب الشراء غير موجود.");

            $stmtItems = $this->db->prepare("SELECT * FROM purchase_request_items WHERE pr_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $products = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products WHERE company_id = $companyId ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/requisitions');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/requisitions/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $data = $request->getParsedBody();
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $this->db->beginTransaction();

            $totalEstimated = 0;
            if (!empty($data['description']) && is_array($data['description'])) {
                foreach ($data['description'] as $idx => $desc) {
                    $qty = (float)($data['quantity'][$idx] ?? 1);
                    $price = (float)($data['estimated_price'][$idx] ?? 0);
                    $totalEstimated += ($qty * $price);
                }
            }

            $stmt = $this->db->prepare("UPDATE purchase_requests SET request_date=?, required_date=?, department=?, requested_by=?, status=?, total_estimated_value=?, notes=? WHERE id=? AND company_id=?");
            $stmt->execute([$data['request_date'], $data['required_date'], $data['department'] ?? null, $data['requested_by'] ?? null, $data['status'] ?? 'pending', $totalEstimated, $data['notes'] ?? null, $id, $companyId]);

            $this->db->prepare("DELETE FROM purchase_request_items WHERE pr_id = ?")->execute([$id]);

            if (!empty($data['description']) && is_array($data['description'])) {
                $stmtItem = $this->db->prepare("INSERT INTO purchase_request_items (pr_id, product_id, description, quantity, estimated_price) VALUES (?, ?, ?, ?, ?)");
                foreach ($data['description'] as $idx => $desc) {
                    if (empty($desc)) continue;
                    $prodId = !empty($data['product_id'][$idx]) ? $data['product_id'][$idx] : null;
                    $stmtItem->execute([$id, $prodId, $desc, (float)($data['quantity'][$idx] ?? 1), (float)($data['estimated_price'][$idx] ?? 0)]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم تحديث طلب الشراء بنجاح." : "PR updated successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/purchasing/requisitions/{$id}/edit");
        }

        return new RedirectResponse('/ERP/purchasing/requisitions');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $this->db->prepare("DELETE FROM purchase_requests WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف طلب الشراء.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف لارتباطه بعمليات أخرى.";
        }
        return new RedirectResponse('/ERP/purchasing/requisitions');
    }
}