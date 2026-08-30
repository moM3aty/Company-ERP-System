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
        if (preg_match('#/hr/payroll/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

  public function index(Request $request, Response $response): Response
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
                $where = ["1=1"];

                if ($search !== '') {
                    $where[] = "(payroll_code LIKE :s1 OR month LIKE :s2)";
                }

                if ($statusFilter !== '') {
                    $where[] = "status = :status_val";
                }

                if ($yearFilter !== '') {
                    $where[] = "year = :year_val";
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM hr_payroll $whereSql");
                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $countStmt->bindValue(':s1', $searchVal);
                    $countStmt->bindValue(':s2', $searchVal);
                }
                if ($statusFilter !== '') $countStmt->bindValue(':status_val', $statusFilter);
                if ($yearFilter !== '') $countStmt->bindValue(':year_val', (int)$yearFilter, PDO::PARAM_INT);

                $countStmt->execute();
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT * FROM hr_payroll
                    $whereSql
                    ORDER BY id DESC 
                    LIMIT :limit OFFSET :offset
                ");

                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $stmt->bindValue(':s1', $searchVal);
                    $stmt->bindValue(':s2', $searchVal);
                }
                if ($statusFilter !== '') $stmt->bindValue(':status_val', $statusFilter);
                if ($yearFilter !== '') $stmt->bindValue(':year_val', (int)$yearFilter, PDO::PARAM_INT);
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

                $stmt->execute();
                $payrolls = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_runs,
                        COALESCE(SUM(net_pay), 0) as total_net_paid,
                        SUM(IF(status = 'draft', 1, 0)) as draft_runs,
                        SUM(IF(status = 'paid', 1, 0)) as paid_runs
                    FROM hr_payroll
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                error_log("HR Payroll Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/hr/payroll/index.php', [
            'payrolls' => $payrolls,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'yearFilter' => $yearFilter
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        $autoCode = 'PAY-' . date('Ym') . '-' . str_pad((string)(($this->getPayrollCount()) + 1), 3, '0', STR_PAD_LEFT);

        return $this->renderView('/resources/views/hr/payroll/create.php', [
            'payroll' => null,
            'autoCode' => $autoCode
        ], $response);
    }

    public function process(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            if (empty($data['payroll_code']) || empty($data['month']) || empty($data['year'])) {
                throw new Exception("يرجى تعبئة كود المسير والشهر والسنة.");
            }

            $stmtEmp = $this->db->query("
                SELECT e.id, e.basic_salary, 
                       COALESCE(c.housing_allowance, 0) as housing, 
                       COALESCE(c.transport_allowance, 0) as transport
                FROM hr_employees e
                LEFT JOIN hr_employee_contracts c ON e.id = c.employee_id AND c.status = 'active'
                WHERE e.status = 'active'
            ");
            $activeEmps = $stmtEmp->fetchAll(PDO::FETCH_OBJ) ?: [];

            if (empty($activeEmps)) {
                throw new Exception("لا يوجد موظفين نشطين لإصدار مسير الرواتب لهم.");
            }

            $totalBasic = 0.00;
            $totalAllowances = 0.00;
            $totalDeductions = !empty($data['total_deductions']) ? (float)$data['total_deductions'] : 0.00;

            foreach ($activeEmps as $emp) {
                $totalBasic += (float)$emp->basic_salary;
                $totalAllowances += ((float)$emp->housing + (float)$emp->transport);
            }

            $netPay = max(0, ($totalBasic + $totalAllowances) - $totalDeductions);

            $stmt = $this->db->prepare("
                INSERT INTO hr_payroll 
                (payroll_code, month, year, total_basic, total_allowances, total_deductions, net_pay, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($data['payroll_code']),
                trim($data['month']),
                (int)$data['year'],
                $totalBasic,
                $totalAllowances,
                $totalDeductions,
                $netPay,
                $data['status'] ?? 'processed',
                $_SESSION['user_id'] ?? 1
            ]);

            $_SESSION['flash_msg'] = "تم توليد واحتساب مسير الرواتب بنجاح لشهر {$data['month']} {$data['year']}.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/payroll/create');
        }

        return new RedirectResponse('/ERP/hr/payroll');
    }

    public function edit(Request $request, Response $response, $id = null): Response
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
            return new RedirectResponse('/ERP/hr/payroll');
        }

        return $this->renderView('/resources/views/hr/payroll/create.php', [
            'payroll' => $payroll,
            'autoCode' => $payroll->payroll_code
        ], $response);
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

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

            $_SESSION['flash_msg'] = "تم تحديث بيانات مسير الرواتب بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/hr/payroll/{$id}/edit");
        }

        return new RedirectResponse('/ERP/hr/payroll');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM hr_payroll WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف مسير الرواتب بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/hr/payroll');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $payroll = null;
        $employeeDetails = [];

        if ($this->db) {
            $stmt = $this->db->prepare("SELECT * FROM hr_payroll WHERE id = ?");
            $stmt->execute([$id]);
            $payroll = $stmt->fetch(PDO::FETCH_OBJ);

            if ($payroll) {
                $stmtEmp = $this->db->query("
                    SELECT e.emp_code, e.name_ar, e.basic_salary,
                           COALESCE(c.housing_allowance, 0) as housing,
                           COALESCE(c.transport_allowance, 0) as transport,
                           d.name_ar as dept_name
                    FROM hr_employees e
                    LEFT JOIN hr_employee_contracts c ON e.id = c.employee_id AND c.status = 'active'
                    LEFT JOIN hr_departments d ON e.department_id = d.id
                    WHERE e.status = 'active'
                    ORDER BY e.name_ar ASC
                ");
                $employeeDetails = $stmtEmp->fetchAll(PDO::FETCH_OBJ) ?: [];
            }
        }

        if (!$payroll) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "مسير الرواتب غير موجود.";
            return new RedirectResponse('/ERP/hr/payroll');
        }

        return $this->renderView('/resources/views/hr/payroll/show.php', [
            'payroll' => $payroll,
            'employeeDetails' => $employeeDetails
        ], $response);
    }

    private function getPayrollCount(): int
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM hr_payroll")->fetchColumn();
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