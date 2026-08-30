<?php
// Path: app/Modules/Admin/Http/Controllers/BranchController.php

namespace App\Modules\Admin\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class BranchController extends Controller
{
    private string $basePath;
    private ?PDO $db = null;

    public function __construct()
    {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            if ($this->db) {
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        }
    }

    private function resolveId($id = null): ?int
    {
        if (!empty($id) && is_numeric($id)) return (int)$id;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/admin/branches/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        // توجيه الروابط الداخلية لضمان عدم حدوث شاشة بيضاء عند استدعاء create أو store
        if (preg_match('#/admin/branches/create#', $uri)) return $this->create($request, $response);
        if (preg_match('#/admin/branches/store#', $uri)) return $this->store($request, $response);
        if (preg_match('#/admin/branches/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/admin/branches/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/admin/branches/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/admin/branches/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $branches = [];
        $totalPages = 1;
        $stats = (object)[
            'total' => 0,
            'active' => 0,
            'inactive' => 0
        ];

        if ($this->db) {
            try {
                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total,
                        SUM(IF(is_active = 1, 1, 0)) as active,
                        SUM(IF(is_active = 0, 1, 0)) as inactive
                    FROM sys_branches
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

                $where = ["1=1"];

                if ($search !== '') {
                    $where[] = "(b.code LIKE :s1 OR b.name_ar LIKE :s2 OR c.name_ar LIKE :s3 OR b.city LIKE :s4)";
                }

                if ($statusFilter !== '') {
                    $where[] = "b.is_active = :status_val";
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM sys_branches b 
                    LEFT JOIN sys_companies c ON b.company_id = c.id 
                    $whereSql
                ");

                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $countStmt->bindValue(':s1', $searchVal);
                    $countStmt->bindValue(':s2', $searchVal);
                    $countStmt->bindValue(':s3', $searchVal);
                    $countStmt->bindValue(':s4', $searchVal);
                }
                if ($statusFilter !== '') $countStmt->bindValue(':status_val', (int)$statusFilter, PDO::PARAM_INT);

                $countStmt->execute();
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT b.*, c.name_ar as company_name 
                    FROM sys_branches b 
                    LEFT JOIN sys_companies c ON b.company_id = c.id 
                    $whereSql
                    ORDER BY b.id DESC 
                    LIMIT :limit OFFSET :offset
                ");

                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $stmt->bindValue(':s1', $searchVal);
                    $stmt->bindValue(':s2', $searchVal);
                    $stmt->bindValue(':s3', $searchVal);
                    $stmt->bindValue(':s4', $searchVal);
                }
                if ($statusFilter !== '') $stmt->bindValue(':status_val', (int)$statusFilter, PDO::PARAM_INT);
                
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

                $stmt->execute();
                $branches = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            } catch (Throwable $e) {
                error_log("Branches Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/admin/branches/index.php', [
            'branches' => $branches,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'statusFilter' => $statusFilter
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        $companies = [];
        $autoCode = 'BRN-' . str_pad((string)(($this->getBranchCount()) + 1), 3, '0', STR_PAD_LEFT);

        if ($this->db) {
            try {
                $companies = $this->db->query("SELECT id, name_ar FROM sys_companies WHERE is_active = 1 ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/admin/branches/create.php', [
            'branch' => null,
            'companies' => $companies,
            'autoCode' => $autoCode
        ], $response);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            if (empty($data['company_id']) || empty($data['name_ar'])) {
                throw new Exception("يرجى اختيار الشركة وإدخال اسم الفرع.");
            }

            $stmt = $this->db->prepare("
                INSERT INTO sys_branches (company_id, code, name_ar, city, is_active)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                (int)$data['company_id'],
                trim($data['code']),
                trim($data['name_ar']),
                trim($data['city'] ?? ''),
                isset($data['is_active']) ? (int)$data['is_active'] : 1
            ]);

            $_SESSION['flash_msg'] = "تم إنشاء الفرع بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء الإنشاء: " . $e->getMessage();
            return new RedirectResponse('/ERP/admin/branches/create');
        }

        return new RedirectResponse('/ERP/admin/branches');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $branch = null;
        $companies = [];

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("SELECT * FROM sys_branches WHERE id = ?");
            $stmt->execute([$id]);
            $branch = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$branch) throw new Exception("الفرع غير موجود.");

            $companies = $this->db->query("SELECT id, name_ar FROM sys_companies ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/admin/branches');
        }

        return $this->renderView('/resources/views/admin/branches/create.php', [
            'branch' => $branch,
            'companies' => $companies,
            'autoCode' => $branch->code
        ], $response);
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("
                UPDATE sys_branches 
                SET company_id = ?, name_ar = ?, city = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                (int)$data['company_id'],
                trim($data['name_ar']),
                trim($data['city'] ?? ''),
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $id
            ]);

            $_SESSION['flash_msg'] = "تم تحديث بيانات الفرع بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء التحديث: " . $e->getMessage();
            return new RedirectResponse("/ERP/admin/branches/{$id}/edit");
        }

        return new RedirectResponse('/ERP/admin/branches');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM sys_branches WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف الفرع نهائياً.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "لا يمكن حذف الفرع لاحتمالية ارتباطه ببيانات أخرى.";
        }

        return new RedirectResponse('/ERP/admin/branches');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $branch = null;

        try {
            if ($this->db) {
                $stmt = $this->db->prepare("
                    SELECT b.*, c.name_ar as company_name, c.tax_number 
                    FROM sys_branches b 
                    LEFT JOIN sys_companies c ON b.company_id = c.id 
                    WHERE b.id = ?
                ");
                $stmt->execute([$id]);
                $branch = $stmt->fetch(PDO::FETCH_OBJ);
            }
        } catch (Throwable $e) {}

        if (!$branch) {
            $_SESSION['flash_err'] = "الفرع المطلوب غير موجود في النظام.";
            return new RedirectResponse('/ERP/admin/branches');
        }

        return $this->renderView('/resources/views/admin/branches/show.php', [
            'branch' => $branch
        ], $response);
    }

    private function getBranchCount(): int
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM sys_branches")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function renderView(string $viewPath, array $data, Response $response): Response
    {
        extract($data);
        $fullPath = $this->basePath . $viewPath;

        if (!file_exists($fullPath)) {
            die("View File Missing: " . htmlspecialchars($fullPath));
        }

        try {
            ob_start();
            include $fullPath;
            $content = ob_get_clean();

            ob_start();
            include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');
        } catch (Throwable $e) {
            ob_end_clean();
            die("View Render Error: " . htmlspecialchars($e->getMessage()));
        }
    }
}