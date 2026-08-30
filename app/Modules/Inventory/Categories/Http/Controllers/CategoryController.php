<?php
// Path: app/Modules/Inventory/Categories/Http/Controllers/CategoryController.php

namespace App\Modules\Inventory\Categories\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class CategoryController extends Controller
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
                $whereClause = "WHERE name_ar LIKE ? OR name_en LIKE ? OR description LIKE ?";
                $like = "%{$search}%";
                $params = [$like, $like, $like];
            }

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM product_categories $whereClause");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT c.*, 
                       (SELECT COUNT(id) FROM products WHERE category_id = c.id) as products_count
                FROM product_categories c
                $whereClause
                ORDER BY c.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $categories = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            $categories = [];
            $totalPages = 1;
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/inventory/categories/index.php';
        if ($dbError) echo "<div style='margin:20px; padding:20px; background:#fef2f2; color:#b91c1c; border-radius:8px;'><strong>DB Error:</strong> $dbError</div>";
        if (file_exists($viewPath)) include $viewPath;
        else echo "<div style='margin:20px; padding:20px; background:#fee2e2; color:#dc2626;'>ملف الواجهة مفقود: $viewPath</div>";
        
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $category = null;
        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/inventory/categories/create.php';
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

            $stmt = $this->db->prepare("
                INSERT INTO product_categories (company_id, name_ar, name_en, description)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, 
                $data['name_ar'], 
                $data['name_en'] ?? null, 
                $data['description'] ?? null
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تمت إضافة الفئة بنجاح." : "Category added successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/inventory/categories/create');
        }

        return new RedirectResponse('/ERP/inventory/categories');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM product_categories WHERE id = ?");
            $stmt->execute([$id]);
            $category = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$category) throw new Exception("الفئة غير موجودة.");

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/inventory/categories');
        }

        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/inventory/categories/create.php';
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
                UPDATE product_categories 
                SET name_ar=?, name_en=?, description=?
                WHERE id=?
            ");
            $stmt->execute([
                $data['name_ar'], 
                $data['name_en'] ?? null, 
                $data['description'] ?? null, 
                $id
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات الفئة بنجاح." : "Category updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/inventory/categories/{$id}/edit");
        }

        return new RedirectResponse('/ERP/inventory/categories');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        try {
            $this->db->prepare("DELETE FROM product_categories WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف الفئة بنجاح.";
        } catch (Throwable $e) {
            // سيحدث هذا الخطأ إذا كان هناك أصناف مرتبطة بهذه الفئة ولديها قيود (Foreign Key Constraint)
            $_SESSION['flash_err'] = "لا يمكن الحذف. توجد أصناف مسجلة تحت هذه الفئة.";
        }
        return new RedirectResponse('/ERP/inventory/categories');
    }
}