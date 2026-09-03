<?php
// Path: app/Modules/Purchasing/Http/Controllers/SupplierController.php

namespace App\Modules\Purchasing\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class SupplierController extends Controller
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
            // فلترة حسب الشركة والفرع (الموردين المخصصين للفرع + الموردين العموميين للشركة)
            $whereClause = "WHERE s.company_id = ?";
            $params = [$companyId];

            if ($branchId > 0) {
                $whereClause .= " AND (s.branch_id = ? OR s.branch_id IS NULL OR s.branch_id = 0)";
                $params[] = $branchId;
            }

            if ($search !== '') {
                $whereClause .= " AND (s.name_ar LIKE ? OR s.name_en LIKE ? OR s.code LIKE ? OR s.phone LIKE ? OR s.tax_number LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like, $like, $like]);
            }

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM suppliers s $whereClause");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            // ربط حساب أوامر الشراء بالفرع النشط
            $poBranchCond = $branchId > 0 ? " AND branch_id = $branchId" : "";

            $stmt = $this->db->prepare("
                SELECT s.*,
                       (SELECT COUNT(id) FROM purchase_orders WHERE supplier_id = s.id $poBranchCond) as orders_count
                FROM suppliers s
                $whereClause
                ORDER BY s.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $suppliers = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            // تحويل الحدود الائتمانية بحسب العملة المحددة
            foreach ($suppliers as $s) {
                $s->credit_limit = convert_amount($s->credit_limit);
            }

        } catch (Throwable $e) {
            $suppliers = [];
            $totalPages = 1;
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/purchasing/suppliers/index.php';
        if (file_exists($viewPath)) include $viewPath;
        
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $supplier = null;
        ob_start(); include $this->basePath . '/resources/views/purchasing/suppliers/create.php'; $content = ob_get_clean();
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
            $code = !empty($data['code']) ? trim($data['code']) : 'SUP-' . rand(1000, 9999);

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO suppliers (company_id, branch_id, code, name_ar, name_en, email, phone, tax_number, address, credit_limit, is_active, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $branchId, $code,
                    $data['name_ar'],
                    $data['name_en'] ?? $data['name_ar'],
                    $data['email'] ?? null,
                    $data['phone'] ?? null,
                    $data['tax_number'] ?? null,
                    $data['address'] ?? null,
                    empty($data['credit_limit']) ? 0 : (float)$data['credit_limit'],
                    isset($data['is_active']) ? (int)$data['is_active'] : 1,
                    $data['notes'] ?? null
                ]);
            } catch (\PDOException $ex) {
                // احتياطي في حال عدم وجود العمود branch_id داخل الجدول في قاعدة البيانات
                $stmt = $this->db->prepare("
                    INSERT INTO suppliers (company_id, code, name_ar, name_en, email, phone, tax_number, address, credit_limit, is_active, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $code,
                    $data['name_ar'],
                    $data['name_en'] ?? $data['name_ar'],
                    $data['email'] ?? null,
                    $data['phone'] ?? null,
                    $data['tax_number'] ?? null,
                    $data['address'] ?? null,
                    empty($data['credit_limit']) ? 0 : (float)$data['credit_limit'],
                    isset($data['is_active']) ? (int)$data['is_active'] : 1,
                    $data['notes'] ?? null
                ]);
            }

            $_SESSION['flash_msg'] = $isAr ? "تم تسجيل المورد بنجاح!" : "Supplier created successfully!";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/suppliers/create');
        }

        return new RedirectResponse('/ERP/purchasing/suppliers');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        try {
            $bCond = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0) " : "";
            $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = ? AND company_id = ? $bCond");
            $stmt->execute([$id, $companyId]);
            $supplier = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$supplier) throw new Exception("المورد غير موجود أو ليس لديك صلاحية عليه.");
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/suppliers');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/suppliers/create.php'; $content = ob_get_clean();
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
            $stmt = $this->db->prepare("
                UPDATE suppliers 
                SET name_ar=?, name_en=?, email=?, phone=?, tax_number=?, address=?, credit_limit=?, is_active=?, notes=?
                WHERE id=? AND company_id=?
            ");
            $stmt->execute([
                $data['name_ar'],
                $data['name_en'] ?? $data['name_ar'],
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['tax_number'] ?? null,
                $data['address'] ?? null,
                empty($data['credit_limit']) ? 0 : (float)$data['credit_limit'],
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $data['notes'] ?? null,
                $id,
                $companyId
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات المورد بنجاح." : "Supplier updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/purchasing/suppliers/{$id}/edit");
        }

        return new RedirectResponse('/ERP/purchasing/suppliers');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        try {
            $bCond = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0) " : "";
            $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = ? AND company_id = ? $bCond");
            $stmt->execute([$id, $companyId]);
            $supplier = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$supplier) throw new Exception("المورد غير موجود.");

            $stats = (object)['orders_count' => 0, 'total_invoiced' => 0.0];
            try {
                $poBranchCond = $branchId > 0 ? " AND branch_id = $branchId" : "";
                $stStmt = $this->db->prepare("SELECT COUNT(id) as cnt, COALESCE(SUM(total_amount), 0) as total FROM purchase_orders WHERE supplier_id = ? $poBranchCond");
                $stStmt->execute([$id]);
                $res = $stStmt->fetch(PDO::FETCH_OBJ);
                $stats->orders_count = $res->cnt ?? 0;
                $stats->total_invoiced = convert_amount($res->total ?? 0);
            } catch (Throwable $ex) {}

            $supplier->credit_limit = convert_amount($supplier->credit_limit);

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/suppliers');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/suppliers/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $stmt = $this->db->prepare("DELETE FROM suppliers WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف المورد بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف لوجود أوامر شراء أو عمليات مرتبطة بالمورد.";
        }
        return new RedirectResponse('/ERP/purchasing/suppliers');
    }
}