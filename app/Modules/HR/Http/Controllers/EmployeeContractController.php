<?php
// Path: app/Modules/HR/Http/Controllers/EmployeeContractController.php

namespace App\Modules\HR\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class EmployeeContractController extends Controller
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
        if (preg_match('#/hr/contracts/(\d+)#', $uri, $matches)) return (int)$matches[1];
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
        if (preg_match('#/hr/contracts/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/hr/contracts/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/hr/contracts/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/hr/contracts/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $contracts = [];
        $stats = (object)[
            'total_contracts' => 0,
            'active_contracts' => 0,
            'expired_contracts' => 0,
            'terminated_contracts' => 0
        ];
        $totalPages = 1;

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condCont = $this->buildBranchCond('c', $branchId, 'hr_employee_contracts');

            $where = ["(c.company_id = {$companyId} OR c.company_id IS NULL OR c.company_id = 0)"];
            $params = [];

            if ($search !== '') {
                $where[] = "(c.contract_code LIKE ? OR e.name_ar LIKE ? OR e.name_en LIKE ? OR e.emp_code LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like, $like);
            }

            if ($statusFilter !== '') {
                $where[] = "c.status = ?";
                $params[] = $statusFilter;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) FROM hr_employee_contracts c
                LEFT JOIN hr_employees e ON c.employee_id = e.id
                $whereSql $condCont
            ");
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCount / $limit));

            $joinBranch = $this->hasColumn('hr_employee_contracts', 'branch_id') ? "LEFT JOIN sys_branches br ON c.branch_id = br.id" : "";
            $colBranch  = $this->hasColumn('hr_employee_contracts', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT c.*, e.name_ar as employee_name, e.name_en as employee_name_en, e.emp_code, {$colBranch}
                FROM hr_employee_contracts c
                LEFT JOIN hr_employees e ON c.employee_id = e.id
                {$joinBranch}
                $whereSql $condCont
                ORDER BY c.id DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $contracts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $statsData = $this->db->query("
                SELECT 
                    COUNT(*) as total_contracts,
                    SUM(IF(c.status = 'active', 1, 0)) as active_contracts,
                    SUM(IF(c.status = 'expired', 1, 0)) as expired_contracts,
                    SUM(IF(c.status = 'terminated', 1, 0)) as terminated_contracts
                FROM hr_employee_contracts c
                WHERE (c.company_id = {$companyId} OR c.company_id IS NULL OR c.company_id = 0) {$condCont}
            ")->fetch(PDO::FETCH_OBJ);
            if ($statsData) $stats = $statsData;

        } catch (Throwable $e) {
            error_log("HR Contracts Index Error: " . $e->getMessage());
            $contracts = [];
            $totalPages = 1;
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/hr/contracts/index.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response)
    {
        $contract = null;
        $employees = [];
        $autoCode = 'CNT-' . str_pad((string)(($this->getContractCount()) + 1), 4, '0', STR_PAD_LEFT);

        if ($this->db) {
            try {
                $companyId = $this->getCompanyId();
                $branchId = $this->getActiveBranchId();
                $condEmp = $this->buildBranchCond('e', $branchId, 'hr_employees');

                $employees = $this->db->query("SELECT e.id, e.emp_code, e.name_ar, e.name_en FROM hr_employees e WHERE (e.company_id = {$companyId} OR e.company_id IS NULL OR e.company_id = 0) AND e.status = 'active' {$condEmp} ORDER BY e.name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/contracts/create.php';
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
            if (!$this->db) throw new Exception($isAr ? "اتصال قاعدة البيانات غير متوفر." : "Database connection lost.");

            if (empty($data['contract_code']) || empty($data['employee_id']) || empty($data['start_date'])) {
                throw new Exception($isAr ? "يرجى تعبئة الحقول الأساسية للعقد." : "Contract code, employee, and start date are required.");
            }

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $hasBranch = $this->hasColumn('hr_employee_contracts', 'branch_id');
            $hasCompany = $this->hasColumn('hr_employee_contracts', 'company_id');

            $extraCols = "";
            $extraVals = "";
            $params = [
                trim($data['contract_code']),
                (int)$data['employee_id'],
                $data['start_date'],
                !empty($data['end_date']) ? $data['end_date'] : null,
                !empty($data['basic_salary']) ? (float)$data['basic_salary'] : 0.00,
                !empty($data['housing_allowance']) ? (float)$data['housing_allowance'] : 0.00,
                !empty($data['transport_allowance']) ? (float)$data['transport_allowance'] : 0.00,
                $data['status'] ?? 'active',
                trim($data['notes'] ?? ''),
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
                INSERT INTO hr_employee_contracts 
                (contract_code, employee_id, start_date, end_date, basic_salary, housing_allowance, transport_allowance, status, notes, created_by {$extraCols})
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ? {$extraVals})
            ");
            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم إضافة العقد بنجاح." : "Contract added successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/contracts/create");
            exit;
        }

        header("Location: /ERP/hr/contracts");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $contract = null;
        $employees = [];

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $stmt = $this->db->prepare("SELECT * FROM hr_employee_contracts WHERE id = ?");
            $stmt->execute([$id]);
            $contract = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$contract) throw new Exception("بيانات العقد غير موجودة.");

            $condEmp = $this->buildBranchCond('e', $branchId, 'hr_employees');
            $employees = $this->db->query("SELECT e.id, e.emp_code, e.name_ar, e.name_en FROM hr_employees e WHERE (e.company_id = {$companyId} OR e.company_id IS NULL OR e.company_id = 0) {$condEmp} ORDER BY e.name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

            $autoCode = $contract->contract_code;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/contracts");
            exit;
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/contracts/create.php';
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
                UPDATE hr_employee_contracts 
                SET employee_id = ?, start_date = ?, end_date = ?, basic_salary = ?, housing_allowance = ?, transport_allowance = ?, status = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                (int)$data['employee_id'],
                $data['start_date'],
                !empty($data['end_date']) ? $data['end_date'] : null,
                !empty($data['basic_salary']) ? (float)$data['basic_salary'] : 0.00,
                !empty($data['housing_allowance']) ? (float)$data['housing_allowance'] : 0.00,
                !empty($data['transport_allowance']) ? (float)$data['transport_allowance'] : 0.00,
                $data['status'] ?? 'active',
                trim($data['notes'] ?? ''),
                $id
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات العقد بنجاح." : "Contract updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/contracts/{$id}/edit");
            exit;
        }

        header("Location: /ERP/hr/contracts");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if ($this->db && $id) {
                $this->db->prepare("DELETE FROM hr_employee_contracts WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = $isAr ? "تم حذف العقد بنجاح." : "Contract deleted successfully.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/hr/contracts");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        $contract = null;

        if ($this->db && $id) {
            $joinBranch = $this->hasColumn('hr_employee_contracts', 'branch_id') ? "LEFT JOIN sys_branches br ON c.branch_id = br.id" : "";
            $colBranch  = $this->hasColumn('hr_employee_contracts', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT c.*, e.name_ar as employee_name, e.name_en as employee_name_en, e.emp_code, e.national_id, 
                       d.name_ar as dept_name, d.name_en as dept_name_en, 
                       dg.title_ar as desig_name, dg.title_en as desig_name_en, {$colBranch}
                FROM hr_employee_contracts c
                LEFT JOIN hr_employees e ON c.employee_id = e.id
                LEFT JOIN hr_departments d ON e.department_id = d.id
                LEFT JOIN hr_designations dg ON e.designation_id = dg.id
                {$joinBranch}
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $contract = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$contract) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "العقد غير موجود.";
            header("Location: /ERP/hr/contracts");
            exit;
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/contracts/show.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    private function getContractCount()
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM hr_employee_contracts")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}