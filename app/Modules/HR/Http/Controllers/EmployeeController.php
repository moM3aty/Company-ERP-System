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
    private $basePath;
    private $db;

    public function __construct()
    {
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = isset($basePath) ? $basePath : dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            if ($this->db) {
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        }
    }

    private function resolveId($id = null)
    {
        if (!empty($id) && is_numeric($id)) return (int)$id;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/hr/employees/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    private function getCompanyId()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return (int)($_SESSION['company_id'] ?? 1);
    }

    private function getActiveBranchId()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return (int)($_SESSION['branch_id'] ?? 0);
    }

    private function hasColumn($table, $column)
    {
        if (!$this->db) return false;
        try {
            $this->db->query("SELECT {$column} FROM {$table} LIMIT 1");
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function buildBranchCond($tableAlias, $branchId, $tableName)
    {
        if ($branchId <= 0) return "";
        if (!$this->hasColumn($tableName, 'branch_id')) return "";
        $col = $tableAlias ? "{$tableAlias}.branch_id" : "branch_id";
        return " AND ({$col} = {$branchId} OR {$col} = 0 OR {$col} IS NULL)";
    }

    public function index(Request $request, Response $response)
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

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condEmp  = $this->buildBranchCond('e', $branchId, 'hr_employees');
            $condDept = $this->buildBranchCond('d', $branchId, 'hr_departments');

            $departments = $this->db->query("SELECT id, code, name_ar, name_en FROM hr_departments d WHERE (d.company_id = {$companyId} OR d.company_id IS NULL OR d.company_id = 0) {$condDept} AND status = 'active' ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

            $where = ["(e.company_id = {$companyId} OR e.company_id IS NULL OR e.company_id = 0)"];
            $params = [];

            if ($search !== '') {
                $where[] = "(e.emp_code LIKE ? OR e.name_ar LIKE ? OR e.name_en LIKE ? OR e.national_id LIKE ? OR e.phone LIKE ? OR e.email LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like, $like, $like, $like);
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

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM hr_employees e $whereSql $condEmp");
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCount / $limit));

            $joinBranch = $this->hasColumn('hr_employees', 'branch_id') ? "LEFT JOIN sys_branches br ON e.branch_id = br.id" : "";
            $colBranch  = $this->hasColumn('hr_employees', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT e.*, d.name_ar as department_name_ar, d.name_en as department_name_en, 
                       dg.title_ar as designation_title_ar, dg.title_en as designation_title_en, {$colBranch}
                FROM hr_employees e
                LEFT JOIN hr_departments d ON e.department_id = d.id
                LEFT JOIN hr_designations dg ON e.designation_id = dg.id
                {$joinBranch}
                $whereSql $condEmp
                ORDER BY e.id DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $employees = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $statsData = $this->db->query("
                SELECT 
                    COUNT(*) as total_emps,
                    SUM(IF(e.status = 'active', 1, 0)) as active_emps,
                    SUM(IF(e.status = 'on_leave', 1, 0)) as on_leave_emps,
                    SUM(IF(e.employment_type = 'probation', 1, 0)) as probation_emps
                FROM hr_employees e
                WHERE (e.company_id = {$companyId} OR e.company_id IS NULL OR e.company_id = 0) {$condEmp}
            ")->fetch(PDO::FETCH_OBJ);
            if ($statsData) $stats = $statsData;

        } catch (Throwable $e) {
            error_log("HR Employees Index Error: " . $e->getMessage());
            $employees = [];
            $totalPages = 1;
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/hr/employees/index.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response)
    {
        $employee = null;
        $departments = [];
        $designations = [];
        $autoCode = 'EMP-' . str_pad((string)(($this->getEmpCount()) + 1), 4, '0', STR_PAD_LEFT);

        if ($this->db) {
            try {
                $companyId = $this->getCompanyId();
                $branchId = $this->getActiveBranchId();
                $condDept = $this->buildBranchCond('d', $branchId, 'hr_departments');
                $condDesig = $this->buildBranchCond('dg', $branchId, 'hr_designations');

                $departments = $this->db->query("SELECT id, code, name_ar, name_en FROM hr_departments d WHERE (d.company_id = {$companyId} OR d.company_id IS NULL OR d.company_id = 0) AND status = 'active' {$condDept} ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                $designations = $this->db->query("SELECT id, code, title_ar, title_en FROM hr_designations dg WHERE (dg.company_id = {$companyId} OR dg.company_id IS NULL OR dg.company_id = 0) AND status = 'active' {$condDesig} ORDER BY title_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/employees/create.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response)
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if (!$this->db) throw new Exception($isAr ? "اتصال قاعدة البيانات غير متوفر." : "Database connection error.");

            if (empty($data['emp_code']) || empty($data['name_ar']) || empty($data['joining_date'])) {
                throw new Exception($isAr ? "يرجى تعبئة الحقول الأساسية (الكود، الاسم بالعربية، وتاريخ المباشرة)." : "Code, Arabic Name, and Joining Date are required.");
            }

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $hasBranch = $this->hasColumn('hr_employees', 'branch_id');
            $hasCompany = $this->hasColumn('hr_employees', 'company_id');

            $extraCols = "";
            $extraVals = "";
            $params = [
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
            ];

            if ($hasCompany) {
                $extraCols .= ", company_id";
                $extraVals .= ", ?";
                $params[] = $companyId;
            }

            if ($hasBranch) {
                $extraCols .= ", branch_id";
                $extraVals .= ", ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare("
                INSERT INTO hr_employees 
                (emp_code, name_ar, name_en, national_id, passport_no, gender, dob, email, phone, department_id, designation_id, joining_date, employment_type, basic_salary, status, address, created_by {$extraCols})
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? {$extraVals})
            ");
            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم إضافة وتسجيل الموظف بنجاح." : "Employee added successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/employees/create");
            exit;
        }

        header("Location: /ERP/hr/employees");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $employee = null;
        $departments = [];
        $designations = [];

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $stmt = $this->db->prepare("SELECT * FROM hr_employees WHERE id = ?");
            $stmt->execute([$id]);
            $employee = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$employee) throw new Exception("بيانات الموظف غير موجودة.");

            $condDept  = $this->buildBranchCond('d', $branchId, 'hr_departments');
            $condDesig = $this->buildBranchCond('dg', $branchId, 'hr_designations');

            $departments = $this->db->query("SELECT id, code, name_ar, name_en FROM hr_departments d WHERE (d.company_id = {$companyId} OR d.company_id IS NULL OR d.company_id = 0) AND status = 'active' {$condDept} ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $designations = $this->db->query("SELECT id, code, title_ar, title_en FROM hr_designations dg WHERE (dg.company_id = {$companyId} OR dg.company_id IS NULL OR dg.company_id = 0) AND status = 'active' {$condDesig} ORDER BY title_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

            $autoCode = $employee->emp_code;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/employees");
            exit;
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/employees/create.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

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

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات الموظف بنجاح." : "Employee updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/employees/{$id}/edit");
            exit;
        }

        header("Location: /ERP/hr/employees");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if ($this->db && $id) {
                $this->db->prepare("DELETE FROM hr_employees WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = $isAr ? "تم حذف سجل الموظف بنجاح." : "Employee record deleted successfully.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/hr/employees");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        $employee = null;

        if ($this->db && $id) {
            $joinBranch = $this->hasColumn('hr_employees', 'branch_id') ? "LEFT JOIN sys_branches br ON e.branch_id = br.id" : "";
            $colBranch  = $this->hasColumn('hr_employees', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT e.*, d.name_ar as department_name_ar, d.name_en as department_name_en, 
                       dg.title_ar as designation_title_ar, dg.title_en as designation_title_en, {$colBranch}
                FROM hr_employees e
                LEFT JOIN hr_departments d ON e.department_id = d.id
                LEFT JOIN hr_designations dg ON e.designation_id = dg.id
                {$joinBranch}
                WHERE e.id = ?
            ");
            $stmt->execute([$id]);
            $employee = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$employee) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "سجل الموظف غير موجود.";
            header("Location: /ERP/hr/employees");
            exit;
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/employees/show.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    private function getEmpCount()
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM hr_employees")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}