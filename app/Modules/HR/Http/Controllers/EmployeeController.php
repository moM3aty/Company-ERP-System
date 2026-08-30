<?php
// Path: app/Modules/HR/Http/Controllers/EmployeeController.php

namespace App\Modules\HR\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class EmployeeController extends Controller
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
        if (preg_match('#/hr/employees/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/hr/employees/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/hr/employees/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/hr/employees/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/hr/employees/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $deptFilter = trim($_GET['department_id'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $employees = [];
        $departments = [];
        $stats = (object)[
            'total_emps' => 0,
            'active_emps' => 0,
            'on_leave_emps' => 0,
            'probation_emps' => 0
        ];
        $totalPages = 1;

        if ($this->db) {
            try {
                $departments = $this->db->query("SELECT id, code, name_ar FROM hr_departments WHERE status = 'active' ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

                $where = ["1=1"];
                $params = [];

                if ($search !== '') {
                    $where[] = "(e.emp_code LIKE ? OR e.name_ar LIKE ? OR e.name_en LIKE ? OR e.national_id LIKE ? OR e.phone LIKE ? OR e.email LIKE ?)";
                    $like = "%{$search}%";
                    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
                }

                if ($deptFilter !== '') {
                    $where[] = "e.department_id = ?";
                    $params[] = (int)$deptFilter;
                }

                if ($statusFilter !== '') {
                    $where[] = "e.status = ?";
                    $params[] = $statusFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM hr_employees e $whereSql");
                $countStmt->execute($params);
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT e.*, d.name_ar as department_name_ar, dg.title_ar as designation_title_ar
                    FROM hr_employees e
                    LEFT JOIN hr_departments d ON e.department_id = d.id
                    LEFT JOIN hr_designations dg ON e.designation_id = dg.id
                    $whereSql
                    ORDER BY e.id DESC 
                    LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $employees = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_emps,
                        SUM(IF(status = 'active', 1, 0)) as active_emps,
                        SUM(IF(status = 'on_leave', 1, 0)) as on_leave_emps,
                        SUM(IF(employment_type = 'probation', 1, 0)) as probation_emps
                    FROM hr_employees
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                error_log("HR Employees Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/hr/employees/index.php', [
            'employees' => $employees,
            'departments' => $departments,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'deptFilter' => $deptFilter,
            'statusFilter' => $statusFilter
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        $employee = null;
        $departments = [];
        $designations = [];
        $autoCode = 'EMP-' . str_pad((string)(($this->getEmpCount()) + 1), 4, '0', STR_PAD_LEFT);

        if ($this->db) {
            try {
                $departments = $this->db->query("SELECT id, code, name_ar FROM hr_departments WHERE status = 'active' ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                $designations = $this->db->query("SELECT id, code, title_ar FROM hr_designations WHERE status = 'active' ORDER BY title_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/hr/employees/create.php', [
            'employee' => $employee,
            'departments' => $departments,
            'designations' => $designations,
            'autoCode' => $autoCode
        ], $response);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            if (empty($data['emp_code']) || empty($data['name_ar']) || empty($data['joining_date'])) {
                throw new Exception("يرجى تعبئة الحقول الأساسية (الكود، الاسم بالعربية، وتاريخ المباشرة).");
            }

            $stmt = $this->db->prepare("
                INSERT INTO hr_employees 
                (emp_code, name_ar, name_en, national_id, passport_no, gender, dob, email, phone, department_id, designation_id, joining_date, employment_type, basic_salary, status, address, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($data['emp_code']),
                trim($data['name_ar']),
                trim($data['name_en'] ?? ''),
                trim($data['national_id'] ?? ''),
                trim($data['passport_no'] ?? ''),
                $data['gender'] ?? 'male',
                !empty($data['dob']) ? $data['dob'] : null,
                trim($data['email'] ?? ''),
                trim($data['phone'] ?? ''),
                !empty($data['department_id']) ? (int)$data['department_id'] : null,
                !empty($data['designation_id']) ? (int)$data['designation_id'] : null,
                $data['joining_date'],
                $data['employment_type'] ?? 'full_time',
                !empty($data['basic_salary']) ? (float)$data['basic_salary'] : 0.00,
                $data['status'] ?? 'active',
                trim($data['address'] ?? ''),
                $_SESSION['user_id'] ?? 1
            ]);

            $_SESSION['flash_msg'] = "تم إضافة وتسجيل الموظف بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/employees/create');
        }

        return new RedirectResponse('/ERP/hr/employees');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $employee = null;
        $departments = [];
        $designations = [];

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("SELECT * FROM hr_employees WHERE id = ?");
            $stmt->execute([$id]);
            $employee = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$employee) throw new Exception("بيانات الموظف غير موجودة.");

            $departments = $this->db->query("SELECT id, code, name_ar FROM hr_departments WHERE status = 'active' ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $designations = $this->db->query("SELECT id, code, title_ar FROM hr_designations WHERE status = 'active' ORDER BY title_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/employees');
        }

        return $this->renderView('/resources/views/hr/employees/create.php', [
            'employee' => $employee,
            'departments' => $departments,
            'designations' => $designations,
            'autoCode' => $employee->emp_code
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
                UPDATE hr_employees 
                SET name_ar = ?, name_en = ?, national_id = ?, passport_no = ?, gender = ?, dob = ?, email = ?, phone = ?, department_id = ?, designation_id = ?, joining_date = ?, employment_type = ?, basic_salary = ?, status = ?, address = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($data['name_ar']),
                trim($data['name_en'] ?? ''),
                trim($data['national_id'] ?? ''),
                trim($data['passport_no'] ?? ''),
                $data['gender'] ?? 'male',
                !empty($data['dob']) ? $data['dob'] : null,
                trim($data['email'] ?? ''),
                trim($data['phone'] ?? ''),
                !empty($data['department_id']) ? (int)$data['department_id'] : null,
                !empty($data['designation_id']) ? (int)$data['designation_id'] : null,
                $data['joining_date'],
                $data['employment_type'] ?? 'full_time',
                !empty($data['basic_salary']) ? (float)$data['basic_salary'] : 0.00,
                $data['status'] ?? 'active',
                trim($data['address'] ?? ''),
                $id
            ]);

            $_SESSION['flash_msg'] = "تم تحديث بيانات الموظف بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/hr/employees/{$id}/edit");
        }

        return new RedirectResponse('/ERP/hr/employees');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM hr_employees WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف سجل الموظف بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/hr/employees');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $employee = null;

        if ($this->db) {
            $stmt = $this->db->prepare("
                SELECT e.*, d.name_ar as department_name_ar, dg.title_ar as designation_title_ar
                FROM hr_employees e
                LEFT JOIN hr_departments d ON e.department_id = d.id
                LEFT JOIN hr_designations dg ON e.designation_id = dg.id
                WHERE e.id = ?
            ");
            $stmt->execute([$id]);
            $employee = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$employee) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "سجل الموظف غير موجود.";
            return new RedirectResponse('/ERP/hr/employees');
        }

        return $this->renderView('/resources/views/hr/employees/show.php', [
            'employee' => $employee
        ], $response);
    }

    private function getEmpCount(): int
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM hr_employees")->fetchColumn();
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