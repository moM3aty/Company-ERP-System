<?php
// Path: app/Modules/Purchasing/Http/Controllers/SupplierController.php

namespace App\Modules\Purchasing\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;

class SupplierController extends Controller
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
        $dbError = null;
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $whereClause = "";
            $params = [];
            if ($search !== '') {
                $whereClause = "WHERE name_ar LIKE ? OR name_en LIKE ? OR code LIKE ? OR phone LIKE ? OR tax_number LIKE ?";
                $like = "%{$search}%";
                $params = array_fill(0, 5, $like);
            }

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM suppliers $whereClause");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("SELECT * FROM suppliers $whereClause ORDER BY id DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            $suppliers = $stmt->fetchAll(PDO::FETCH_OBJ);
            if ($suppliers === false) $suppliers = [];
            
        } catch (Exception $e) {
            $suppliers = [];
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/purchasing/suppliers/index.php';
        if ($dbError) echo "<div style='margin:20px; padding:20px; background:#fef2f2; color:#b91c1c; border-radius:8px;'><strong>DB Error:</strong> $dbError</div>";
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

        try {
            $companyId = $_SESSION['company_id'] ?? 1;
            $code = !empty($data['code']) ? trim($data['code']) : 'SUP-' . rand(1000, 9999);

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

            $_SESSION['flash_msg'] = $isAr ? "تم تسجيل المورد بنجاح!" : "Supplier created successfully!";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/suppliers/create');
        }

        return new RedirectResponse('/ERP/purchasing/suppliers');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            $supplier = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$supplier) throw new Exception("المورد غير موجود.");
        } catch (Exception $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
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

        try {
            $stmt = $this->db->prepare("
                UPDATE suppliers 
                SET name_ar=?, name_en=?, email=?, phone=?, tax_number=?, address=?, credit_limit=?, is_active=?, notes=?
                WHERE id=?
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
                $id
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات المورد بنجاح." : "Supplier updated successfully.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/purchasing/suppliers/{$id}/edit");
        }

        return new RedirectResponse('/ERP/purchasing/suppliers');
    }

public function show(Request $request, Response $response, $id = null): Response
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            $supplier = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$supplier) throw new Exception("المورد غير موجود.");

            // الإحصائيات (أوامر الشراء المرتبطة بالمورد)
            $stats = (object)['orders_count' => 0];
            try {
                $stStmt = $this->db->prepare("SELECT COUNT(id) FROM purchase_orders WHERE supplier_id = ?");
                $stStmt->execute([$id]);
                $stats->orders_count = $stStmt->fetchColumn() ?: 0;
            } catch (Exception $ex) {}

        } catch (Exception $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
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
        try {
            $stmt = $this->db->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف المورد بنجاح.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف لوجود أوامر شراء أو عمليات مرتبطة بالمورد.";
        }
        return new RedirectResponse('/ERP/purchasing/suppliers');
    }
}