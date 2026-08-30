<?php
// Path: app/Modules/HR/Http/Controllers/DepartmentController.php

namespace App\Modules\HR\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class DepartmentController extends Controller
{
    private string $basePath;
    private ?PDO $db = null;

    public function __construct()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
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
        if (preg_match('#/hr/departments/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/hr/departments/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/hr/departments/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/hr/departments/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/hr/departments/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $departments = [];
        $stats = (object)[
            'total_depts' => 0,
            'active_depts' => 0,
            'parent_depts' => 0,
            'sub_depts' => 0
        ];
        $totalPages = 1;

        if ($this->db) {
            try {
                $where = ["1=1"];
                $params = [];

                if ($search !== '') {
                    $where[] = "(d.code LIKE ? OR d.name_ar LIKE ? OR d.name_en LIKE ? OR d.manager_name LIKE ?)";
                    $like = "%{$search}%";
                    $params = array_merge($params, [$like, $like, $like, $like]);
                }

                if ($statusFilter !== '') {
                    $where[] = "d.status = ?";
                    $params[] = $statusFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM hr_departments d $whereSql");
                $countStmt->execute($params);
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT d.*, p.name_ar as parent_name_ar
                    FROM hr_departments d
                    LEFT JOIN hr_departments p ON d.parent_id = p.id
                    $whereSql
                    ORDER BY d.id DESC 
                    LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $departments = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_depts,
                        SUM(IF(status = 'active', 1, 0)) as active_depts,
                        SUM(IF(parent_id IS NULL OR parent_id = 0, 1, 0)) as parent_depts,
                        SUM(IF(parent_id IS NOT NULL AND parent_id > 0, 1, 0)) as sub_depts
                    FROM hr_departments
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                error_log("HR Departments Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/hr/departments/index.php', [
            'departments' => $departments,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'statusFilter' => $statusFilter
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        $department = null;
        $parentDepts = [];
        $autoCode = 'DEP-' . str_pad((string)(($this->getDeptCount()) + 1), 3, '0', STR_PAD_LEFT);

        if ($this->db) {
            try {
                $parentDepts = $this->db->query("SELECT id, code, name_ar FROM hr_departments WHERE status = 'active' ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/hr/departments/create.php', [
            'department' => $department,
            'parentDepts' => $parentDepts,
            'autoCode' => $autoCode
        ], $response);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            if (empty($data['code']) || empty($data['name_ar'])) {
                throw new Exception("يرجى تعبئة كود الإدارة واسم الإدارة بالعربية.");
            }

            $stmt = $this->db->prepare("
                INSERT INTO hr_departments (code, name_ar, name_en, parent_id, manager_name, status, description, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($data['code']),
                trim($data['name_ar']),
                trim($data['name_en'] ?? ''),
                !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
                trim($data['manager_name'] ?? ''),
                $data['status'] ?? 'active',
                trim($data['description'] ?? ''),
                $_SESSION['user_id'] ?? 1
            ]);

            $_SESSION['flash_msg'] = "تم إنشاء الإدارة/القسم بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/departments/create');
        }

        return new RedirectResponse('/ERP/hr/departments');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $department = null;
        $parentDepts = [];

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("SELECT * FROM hr_departments WHERE id = ?");
            $stmt->execute([$id]);
            $department = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$department) throw new Exception("بيانات الإدارة غير موجودة.");

            $parentDepts = $this->db->query("SELECT id, code, name_ar FROM hr_departments WHERE id != {$id} AND status = 'active' ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/departments');
        }

        return $this->renderView('/resources/views/hr/departments/create.php', [
            'department' => $department,
            'parentDepts' => $parentDepts,
            'autoCode' => $department->code
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
                UPDATE hr_departments 
                SET name_ar = ?, name_en = ?, parent_id = ?, manager_name = ?, status = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($data['name_ar']),
                trim($data['name_en'] ?? ''),
                !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
                trim($data['manager_name'] ?? ''),
                $data['status'] ?? 'active',
                trim($data['description'] ?? ''),
                $id
            ]);

            $_SESSION['flash_msg'] = "تم تحديث بيانات الإدارة بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/hr/departments/{$id}/edit");
        }

        return new RedirectResponse('/ERP/hr/departments');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM hr_departments WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف الإدارة/القسم بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/hr/departments');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $department = null;

        if ($this->db) {
            $stmt = $this->db->prepare("
                SELECT d.*, p.name_ar as parent_name_ar
                FROM hr_departments d
                LEFT JOIN hr_departments p ON d.parent_id = p.id
                WHERE d.id = ?
            ");
            $stmt->execute([$id]);
            $department = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$department) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "سجل الإدارة غير موجود.";
            return new RedirectResponse('/ERP/hr/departments');
        }

        return $this->renderView('/resources/views/hr/departments/show.php', [
            'department' => $department
        ], $response);
    }

    private function getDeptCount(): int
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM hr_departments")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function renderView(string $viewPath, array $data, Response $response): Response
    {
        extract($data);
        $fullPath = $this->basePath . $viewPath;

        if (!file_exists($fullPath)) {
            die("<div style='padding:30px; background:#fff; color:#dc2626;'>View File Missing: " . htmlspecialchars($fullPath) . "</div>");
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
            die("<div style='padding:30px; background:#fff; color:#dc2626;'>View Render Error: " . htmlspecialchars($e->getMessage()) . "</div>");
        }
    }
}