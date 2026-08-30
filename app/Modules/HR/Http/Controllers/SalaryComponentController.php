<?php
// Path: app/Modules/HR/Http/Controllers/SalaryComponentController.php

namespace App\Modules\HR\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class SalaryComponentController extends Controller
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
        if (preg_match('#/hr/salary-components/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

  public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/hr/salary-components/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/hr/salary-components/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/hr/salary-components/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/hr/salary-components/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $typeFilter = trim($_GET['type'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $components = [];
        $stats = (object)[
            'total_items' => 0,
            'total_allowances' => 0.00,
            'total_deductions' => 0.00,
            'fixed_count' => 0
        ];
        $totalPages = 1;

        if ($this->db) {
            try {
                $where = ["1=1"];

                if ($search !== '') {
                    $where[] = "(c.name_ar LIKE :s1 OR e.name_ar LIKE :s2 OR e.emp_code LIKE :s3)";
                }

                if ($typeFilter !== '') {
                    $where[] = "c.type = :type_val";
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM hr_salary_components c
                    LEFT JOIN hr_employees e ON c.employee_id = e.id
                    $whereSql
                ");
                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $countStmt->bindValue(':s1', $searchVal);
                    $countStmt->bindValue(':s2', $searchVal);
                    $countStmt->bindValue(':s3', $searchVal);
                }
                if ($typeFilter !== '') $countStmt->bindValue(':type_val', $typeFilter);

                $countStmt->execute();
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT c.*, e.name_ar as employee_name, e.emp_code, d.name_ar as dept_name
                    FROM hr_salary_components c
                    LEFT JOIN hr_employees e ON c.employee_id = e.id
                    LEFT JOIN hr_departments d ON e.department_id = d.id
                    $whereSql
                    ORDER BY c.id DESC 
                    LIMIT :limit OFFSET :offset
                ");

                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $stmt->bindValue(':s1', $searchVal);
                    $stmt->bindValue(':s2', $searchVal);
                    $stmt->bindValue(':s3', $searchVal);
                }
                if ($typeFilter !== '') $stmt->bindValue(':type_val', $typeFilter);
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

                $stmt->execute();
                $components = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_items,
                        COALESCE(SUM(IF(type = 'allowance', amount, 0)), 0) as total_allowances,
                        COALESCE(SUM(IF(type = 'deduction', amount, 0)), 0) as total_deductions,
                        SUM(IF(is_fixed = 1, 1, 0)) as fixed_count
                    FROM hr_salary_components
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                error_log("HR Salary Components Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/hr/salary_components/index.php', [
            'components' => $components,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'typeFilter' => $typeFilter
        ], $response);
    }
    public function create(Request $request, Response $response): Response
    {
        $employees = [];

        if ($this->db) {
            try {
                $employees = $this->db->query("SELECT id, emp_code, name_ar FROM hr_employees WHERE status = 'active' ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/hr/salary_components/create.php', [
            'component' => null,
            'employees' => $employees
        ], $response);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            if (empty($data['employee_id']) || empty($data['name_ar']) || empty($data['type'])) {
                throw new Exception("يرجى تعبئة الحقول الأساسية لرمز الراتب.");
            }

            $stmt = $this->db->prepare("
                INSERT INTO hr_salary_components (employee_id, type, name_ar, amount, is_fixed, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                (int)$data['employee_id'],
                $data['type'],
                trim($data['name_ar']),
                !empty($data['amount']) ? (float)$data['amount'] : 0.00,
                isset($data['is_fixed']) ? (int)$data['is_fixed'] : 1,
                $_SESSION['user_id'] ?? 1
            ]);

            $_SESSION['flash_msg'] = "تم إضافة مفرد الراتب/البدل بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/salary-components/create');
        }

        return new RedirectResponse('/ERP/hr/salary-components');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $component = null;
        $employees = [];

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("SELECT * FROM hr_salary_components WHERE id = ?");
            $stmt->execute([$id]);
            $component = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$component) throw new Exception("مفرد الراتب غير موجود.");

            $employees = $this->db->query("SELECT id, emp_code, name_ar FROM hr_employees ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/salary-components');
        }

        return $this->renderView('/resources/views/hr/salary_components/create.php', [
            'component' => $component,
            'employees' => $employees
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
                UPDATE hr_salary_components 
                SET employee_id = ?, type = ?, name_ar = ?, amount = ?, is_fixed = ?
                WHERE id = ?
            ");
            $stmt->execute([
                (int)$data['employee_id'],
                $data['type'],
                trim($data['name_ar']),
                !empty($data['amount']) ? (float)$data['amount'] : 0.00,
                isset($data['is_fixed']) ? (int)$data['is_fixed'] : 1,
                $id
            ]);

            $_SESSION['flash_msg'] = "تم تحديث مفرد الراتب بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/hr/salary-components/{$id}/edit");
        }

        return new RedirectResponse('/ERP/hr/salary-components');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM hr_salary_components WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف المفرد بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/hr/salary-components');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $component = null;

        if ($this->db) {
            $stmt = $this->db->prepare("
                SELECT c.*, e.name_ar as employee_name, e.emp_code, d.name_ar as dept_name
                FROM hr_salary_components c
                LEFT JOIN hr_employees e ON c.employee_id = e.id
                LEFT JOIN hr_departments d ON e.department_id = d.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $component = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$component) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "سجل مفرد الراتب غير موجود.";
            return new RedirectResponse('/ERP/hr/salary-components');
        }

        return $this->renderView('/resources/views/hr/salary_components/show.php', [
            'component' => $component
        ], $response);
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