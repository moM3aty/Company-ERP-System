<?php
// Path: app/Modules/Inventory/Warehouses/Http/Controllers/WarehouseController.php

namespace App\Modules\Inventory\Warehouses\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class WarehouseController extends Controller
{
    private string $basePath;
    private PDO $db;

    public function __construct()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 5);
        
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
                $whereClause = "WHERE code LIKE ? OR name_ar LIKE ? OR name_en LIKE ? OR location LIKE ? OR manager_name LIKE ?";
                $like = "%{$search}%";
                $params = array_fill(0, 5, $like);
            }

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM warehouses $whereClause");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT * FROM warehouses
                $whereClause
                ORDER BY id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $warehouses = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            // إحصائيات المستودعات
            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total, 
                    SUM(IF(is_active=1, 1, 0)) as active, 
                    SUM(IF(is_active=0, 1, 0)) as inactive 
                FROM warehouses
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $warehouses = [];
            $stats = (object)['total'=>0, 'active'=>0, 'inactive'=>0];
            $totalPages = 1;
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/inventory/warehouses/index.php';
        if ($dbError) echo "<div style='margin:20px; padding:20px; background:#fef2f2; color:#b91c1c; border-radius:8px;'><strong>DB Error:</strong> $dbError</div>";
        if (file_exists($viewPath)) include $viewPath;
        else echo "<div style='margin:20px; padding:20px; background:#fee2e2; color:#dc2626;'>ملف الواجهة مفقود: $viewPath</div>";
        
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $warehouse = null;
        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/inventory/warehouses/create.php';
        if (file_exists($viewPath)) include $viewPath;
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            $companyId = $_SESSION['company_id'] ?? 1;
            $code = !empty($data['code']) ? trim($data['code']) : 'WH-' . rand(100, 999);

            $stmt = $this->db->prepare("
                INSERT INTO warehouses (company_id, code, name_ar, name_en, location, manager_name, phone, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, 
                $code, 
                $data['name_ar'], 
                $data['name_en'] ?? null, 
                $data['location'] ?? null,
                $data['manager_name'] ?? null,
                $data['phone'] ?? null,
                isset($data['is_active']) ? 1 : 0
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم إنشاء المستودع بنجاح." : "Warehouse created successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/inventory/warehouses/create');
        }

        return new RedirectResponse('/ERP/inventory/warehouses');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM warehouses WHERE id = ?");
            $stmt->execute([$id]);
            $warehouse = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$warehouse) throw new Exception("المستودع غير موجود.");

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/inventory/warehouses');
        }

        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/inventory/warehouses/create.php';
        if (file_exists($viewPath)) include $viewPath;
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            $stmt = $this->db->prepare("
                UPDATE warehouses 
                SET code=?, name_ar=?, name_en=?, location=?, manager_name=?, phone=?, is_active=?
                WHERE id=?
            ");
            $stmt->execute([
                $data['code'], 
                $data['name_ar'], 
                $data['name_en'] ?? null, 
                $data['location'] ?? null,
                $data['manager_name'] ?? null,
                $data['phone'] ?? null,
                isset($data['is_active']) ? 1 : 0, 
                $id
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات المستودع بنجاح." : "Warehouse updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/inventory/warehouses/{$id}/edit");
        }

        return new RedirectResponse('/ERP/inventory/warehouses');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        try {
            $this->db->prepare("DELETE FROM warehouses WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف المستودع بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف. قد يكون المستودع مرتبطاً بحركات مخزنية أو أرصدة فعلية.";
        }
        return new RedirectResponse('/ERP/inventory/warehouses');
    }
}