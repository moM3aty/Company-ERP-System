<?php
// Path: app/Modules/HR/Http/Controllers/PayrollController.php

namespace App\Modules\HR\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class PayrollController extends Controller
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
        if (preg_match('#/hr/payroll/(\d+)#', $uri, $matches)) return (int)$matches[1];
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
        if (preg_match('#/hr/payroll/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/hr/payroll/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/hr/payroll/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/hr/payroll/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $yearFilter = trim($_GET['year'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $payrolls = [];
        $stats = (object)[
            'total_runs' => 0,
            'total_net_paid' => 0.00,
            'draft_runs' => 0,
            'paid_runs' => 0
        ];
        $totalPages = 1;

        if ($this->db) {
            try {
                $companyId = $this->getCompanyId();
                $branchId = $this->getActiveBranchId();

                $condPay = $this->buildBranchCond('p', $branchId, 'hr_payroll');

                $where = ["(p.company_id = {$companyId} OR p.company_id IS NULL OR p.company_id = 0)"];
                $params = [];

                if ($search !== '') {
                    $where[] = "(p.payroll_code LIKE ? OR p.month LIKE ?)";
                    $like = "%{$search}%";
                    array_push($params, $like, $like);
                }

                if ($statusFilter !== '') {
                    $where[] = "p.status = ?";
                    $params[] = $statusFilter;
                }

                if ($yearFilter !== '') {
                    $where[] = "p.year = ?";
                    $params[] = (int)$yearFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM hr_payroll p $whereSql $condPay");
                $countStmt->execute($params);
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $joinBranch = $this->hasColumn('hr_payroll', 'branch_id') ? "LEFT JOIN sys_branches br ON p.branch_id = br.id" : "";
                $colBranch  = $this->hasColumn('hr_payroll', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

                $stmt = $this->db->prepare("
                    SELECT p.*, {$colBranch}
                    FROM hr_payroll p
                    {$joinBranch}
                    $whereSql $condPay
                    ORDER BY p.id DESC 
                    LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $payrolls = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_runs,
                        COALESCE(SUM(IF(p.status = 'paid', p.net_pay, 0)), 0) as total_net_paid,
                        SUM(IF(p.status = 'draft', 1, 0)) as draft_runs,
                        SUM(IF(p.status = 'paid', 1, 0)) as paid_runs
                    FROM hr_payroll p
                    WHERE (p.company_id = {$companyId} OR p.company_id IS NULL OR p.company_id = 0) {$condPay}
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                error_log("HR Payroll Index Error: " . $e->getMessage());
            }
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/hr/payroll/index.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response)
    {
        $autoCode = 'PAY-' . date('Ym') . '-' . str_pad((string)(($this->getPayrollCount()) + 1), 3, '0', STR_PAD_LEFT);

        ob_start();
        include $this->basePath . '/resources/views/hr/payroll/create.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function process(Request $request, Response $response)
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if (!$this->db) throw new Exception($isAr ? "اتصال قاعدة البيانات غير متوفر." : "Database connection lost.");

            if (empty($data['payroll_code']) || empty($data['month']) || empty($data['year'])) {
                throw new Exception($isAr ? "يرجى تعبئة كود المسير والشهر والسنة." : "Payroll code, month, and year are required.");
            }

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condEmp = $this->buildBranchCond('e', $branchId, 'hr_employees');

            $stmtEmp = $this->db->query("
                SELECT e.id, e.basic_salary, 
                       COALESCE(c.housing_allowance, 0) as housing, 
                       COALESCE(c.transport_allowance, 0) as transport
                FROM hr_employees e
                LEFT JOIN hr_employee_contracts c ON e.id = c.employee_id AND c.status = 'active'
                WHERE e.status = 'active' AND (e.company_id = {$companyId} OR e.company_id IS NULL OR e.company_id = 0) {$condEmp}
            ");
            $activeEmps = $stmtEmp->fetchAll(PDO::FETCH_OBJ) ?: [];

            if (empty($activeEmps)) {
                throw new Exception($isAr ? "لا يوجد موظفين نشطين في هذا الفرع لإصدار مسير الرواتب لهم." : "No active employees found in this branch to generate payroll.");
            }

            $totalBasic = 0.00;
            $totalAllowances = 0.00;
            $totalDeductions = !empty($data['total_deductions']) ? (float)$data['total_deductions'] : 0.00;

            foreach ($activeEmps as $emp) {
                $totalBasic += (float)$emp->basic_salary;
                $totalAllowances += ((float)$emp->housing + (float)$emp->transport);
            }

            $netPay = max(0, ($totalBasic + $totalAllowances) - $totalDeductions);

            $hasBranch = $this->hasColumn('hr_payroll', 'branch_id');
            $hasCompany = $this->hasColumn('hr_payroll', 'company_id');

            $extraCols = "";
            $extraVals = "";
            $params = [
                trim($data['payroll_code']),
                trim($data['month']),
                (int)$data['year'],
                $totalBasic,
                $totalAllowances,
                $totalDeductions,
                $netPay,
                $data['status'] ?? 'processed',
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
                INSERT INTO hr_payroll 
                (payroll_code, month, year, total_basic, total_allowances, total_deductions, net_pay, status, created_by {$extraCols})
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ? {$extraVals})
            ");
            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم توليد واحتساب مسير الرواتب بنجاح لشهر {$data['month']} {$data['year']}." : "Payroll processed successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/payroll/create");
            exit;
        }

        header("Location: /ERP/hr/payroll");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $payroll = null;

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("SELECT * FROM hr_payroll WHERE id = ?");
            $stmt->execute([$id]);
            $payroll = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$payroll) throw new Exception("مسير الرواتب غير موجود.");

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/payroll");
            exit;
        }

        $autoCode = $payroll->payroll_code;

        ob_start();
        include $this->basePath . '/resources/views/hr/payroll/create.php';
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

            $totalBasic = !empty($data['total_basic']) ? (float)$data['total_basic'] : 0.00;
            $totalAllowances = !empty($data['total_allowances']) ? (float)$data['total_allowances'] : 0.00;
            $totalDeductions = !empty($data['total_deductions']) ? (float)$data['total_deductions'] : 0.00;
            $netPay = max(0, ($totalBasic + $totalAllowances) - $totalDeductions);

            $stmt = $this->db->prepare("
                UPDATE hr_payroll 
                SET month = ?, year = ?, total_basic = ?, total_allowances = ?, total_deductions = ?, net_pay = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($data['month']),
                (int)$data['year'],
                $totalBasic,
                $totalAllowances,
                $totalDeductions,
                $netPay,
                $data['status'] ?? 'processed',
                $id
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات مسير الرواتب بنجاح." : "Payroll updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/payroll/{$id}/edit");
            exit;
        }

        header("Location: /ERP/hr/payroll");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if ($this->db && $id) {
                $this->db->prepare("DELETE FROM hr_payroll WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = $isAr ? "تم حذف مسير الرواتب بنجاح." : "Payroll deleted successfully.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/hr/payroll");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        $payroll = null;
        $employeeDetails = [];

        if ($this->db && $id) {
            $companyId = $this->getCompanyId();
            
            $joinBranch = $this->hasColumn('hr_payroll', 'branch_id') ? "LEFT JOIN sys_branches br ON p.branch_id = br.id" : "";
            $colBranch  = $this->hasColumn('hr_payroll', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("SELECT p.*, {$colBranch} FROM hr_payroll p {$joinBranch} WHERE p.id = ?");
            $stmt->execute([$id]);
            $payroll = $stmt->fetch(PDO::FETCH_OBJ);

            if ($payroll) {
                // جلب الموظفين لنفس فرع المسير إن وجد
                $payrollBranchCond = "";
                if (isset($payroll->branch_id) && $payroll->branch_id > 0) {
                    $payrollBranchCond = " AND e.branch_id = " . (int)$payroll->branch_id;
                }

                $stmtEmp = $this->db->query("
                    SELECT e.emp_code, e.name_ar, e.name_en, e.basic_salary,
                           COALESCE(c.housing_allowance, 0) as housing,
                           COALESCE(c.transport_allowance, 0) as transport,
                           d.name_ar as dept_name, d.name_en as dept_name_en
                    FROM hr_employees e
                    LEFT JOIN hr_employee_contracts c ON e.id = c.employee_id AND c.status = 'active'
                    LEFT JOIN hr_departments d ON e.department_id = d.id
                    WHERE e.status = 'active' AND (e.company_id = {$companyId} OR e.company_id IS NULL OR e.company_id = 0) {$payrollBranchCond}
                    ORDER BY e.name_ar ASC
                ");
                $employeeDetails = $stmtEmp->fetchAll(PDO::FETCH_OBJ) ?: [];
            }
        }

        if (!$payroll) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "مسير الرواتب غير موجود.";
            header("Location: /ERP/hr/payroll");
            exit;
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/payroll/show.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    private function getPayrollCount()
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM hr_payroll")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}