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
        if (preg_match('#/hr/salary-components/(\d+)#', $uri, $matches)) return (int)$matches[1];
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
                $companyId = $this->getCompanyId();
                $branchId = $this->getActiveBranchId();

                $condComp = $this->buildBranchCond('c', $branchId, 'hr_salary_components');

                $where = ["(c.company_id = {$companyId} OR c.company_id IS NULL OR c.company_id = 0)"];
                $params = [];

                if ($search !== '') {
                    $where[] = "(c.name_ar LIKE ? OR e.name_ar LIKE ? OR e.name_en LIKE ? OR e.emp_code LIKE ?)";
                    $like = "%{$search}%";
                    array_push($params, $like, $like, $like, $like);
                }

                if ($typeFilter !== '') {
                    $where[] = "c.type = ?";
                    $params[] = $typeFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM hr_salary_components c
                    LEFT JOIN hr_employees e ON c.employee_id = e.id
                    $whereSql $condComp
                ");
                $countStmt->execute($params);
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $joinBranch = $this->hasColumn('hr_salary_components', 'branch_id') ? "LEFT JOIN sys_branches br ON c.branch_id = br.id" : "";
                $colBranch  = $this->hasColumn('hr_salary_components', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

                $stmt = $this->db->prepare("
                    SELECT c.*, e.name_ar as employee_name, e.name_en as employee_name_en, e.emp_code, d.name_ar as dept_name, d.name_en as dept_name_en, {$colBranch}
                    FROM hr_salary_components c
                    LEFT JOIN hr_employees e ON c.employee_id = e.id
                    LEFT JOIN hr_departments d ON e.department_id = d.id
                    {$joinBranch}
                    $whereSql $condComp
                    ORDER BY c.id DESC 
                    LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $components = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_items,
                        COALESCE(SUM(IF(c.type = 'allowance', c.amount, 0)), 0) as total_allowances,
                        COALESCE(SUM(IF(c.type = 'deduction', c.amount, 0)), 0) as total_deductions,
                        SUM(IF(c.is_fixed = 1, 1, 0)) as fixed_count
                    FROM hr_salary_components c
                    WHERE (c.company_id = {$companyId} OR c.company_id IS NULL OR c.company_id = 0) {$condComp}
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                error_log("HR Salary Components Index Error: " . $e->getMessage());
                $components = [];
                $totalPages = 1;
            }
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/hr/salary_components/index.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response)
    {
        $employees = [];

        if ($this->db) {
            try {
                $companyId = $this->getCompanyId();
                $branchId = $this->getActiveBranchId();
                $condEmp = $this->buildBranchCond('e', $branchId, 'hr_employees');

                $employees = $this->db->query("SELECT e.id, e.emp_code, e.name_ar, e.name_en FROM hr_employees e WHERE (e.company_id = {$companyId} OR e.company_id IS NULL OR e.company_id = 0) AND status = 'active' {$condEmp} ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/salary_components/create.php';
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

            if (empty($data['employee_id']) || empty($data['name_ar']) || empty($data['type'])) {
                throw new Exception($isAr ? "يرجى تعبئة الحقول الأساسية لمفرد الراتب." : "Employee, Name, and Type are required.");
            }

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $hasBranch = $this->hasColumn('hr_salary_components', 'branch_id');
            $hasCompany = $this->hasColumn('hr_salary_components', 'company_id');

            $extraCols = "";
            $extraVals = "";
            $params = [
                (int)$data['employee_id'],
                $data['type'],
                trim($data['name_ar']),
                !empty($data['amount']) ? (float)$data['amount'] : 0.00,
                isset($data['is_fixed']) ? (int)$data['is_fixed'] : 1,
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
                INSERT INTO hr_salary_components (employee_id, type, name_ar, amount, is_fixed, created_by {$extraCols})
                VALUES (?, ?, ?, ?, ?, ? {$extraVals})
            ");
            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم إضافة مفرد الراتب/البدل بنجاح." : "Salary component added successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/salary-components/create");
            exit;
        }

        header("Location: /ERP/hr/salary-components");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $component = null;
        $employees = [];

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $stmt = $this->db->prepare("SELECT * FROM hr_salary_components WHERE id = ?");
            $stmt->execute([$id]);
            $component = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$component) throw new Exception("مفرد الراتب غير موجود.");

            $condEmp = $this->buildBranchCond('e', $branchId, 'hr_employees');
            $employees = $this->db->query("SELECT e.id, e.emp_code, e.name_ar, e.name_en FROM hr_employees e WHERE (e.company_id = {$companyId} OR e.company_id IS NULL OR e.company_id = 0) {$condEmp} ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/salary-components");
            exit;
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/salary_components/create.php';
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

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث مفرد الراتب بنجاح." : "Salary component updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/salary-components/{$id}/edit");
            exit;
        }

        header("Location: /ERP/hr/salary-components");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if ($this->db && $id) {
                $this->db->prepare("DELETE FROM hr_salary_components WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = $isAr ? "تم حذف المفرد بنجاح." : "Component deleted successfully.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/hr/salary-components");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        $component = null;

        if ($this->db && $id) {
            $joinBranch = $this->hasColumn('hr_salary_components', 'branch_id') ? "LEFT JOIN sys_branches br ON c.branch_id = br.id" : "";
            $colBranch  = $this->hasColumn('hr_salary_components', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT c.*, e.name_ar as employee_name, e.name_en as employee_name_en, e.emp_code, d.name_ar as dept_name, d.name_en as dept_name_en, {$colBranch}
                FROM hr_salary_components c
                LEFT JOIN hr_employees e ON c.employee_id = e.id
                LEFT JOIN hr_departments d ON e.department_id = d.id
                {$joinBranch}
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $component = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$component) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "سجل مفرد الراتب غير موجود.";
            header("Location: /ERP/hr/salary-components");
            exit;
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/salary_components/show.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}