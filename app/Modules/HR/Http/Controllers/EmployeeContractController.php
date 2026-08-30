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
        if (preg_match('#/hr/contracts/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

   public function index(Request $request, Response $response): Response
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

        if ($this->db) {
            try {
                $where = ["1=1"];

                if ($search !== '') {
                    $where[] = "(c.contract_code LIKE :s1 OR e.name_ar LIKE :s2 OR e.emp_code LIKE :s3)";
                }

                if ($statusFilter !== '') {
                    $where[] = "c.status = :status_val";
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM hr_employee_contracts c
                    LEFT JOIN hr_employees e ON c.employee_id = e.id
                    $whereSql
                ");
                
                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $countStmt->bindValue(':s1', $searchVal);
                    $countStmt->bindValue(':s2', $searchVal);
                    $countStmt->bindValue(':s3', $searchVal);
                }
                if ($statusFilter !== '') $countStmt->bindValue(':status_val', $statusFilter);
                
                $countStmt->execute();
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT c.*, e.name_ar as employee_name, e.emp_code
                    FROM hr_employee_contracts c
                    LEFT JOIN hr_employees e ON c.employee_id = e.id
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
                if ($statusFilter !== '') $stmt->bindValue(':status_val', $statusFilter);
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

                $stmt->execute();
                $contracts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_contracts,
                        SUM(IF(status = 'active', 1, 0)) as active_contracts,
                        SUM(IF(status = 'expired', 1, 0)) as expired_contracts,
                        SUM(IF(status = 'terminated', 1, 0)) as terminated_contracts
                    FROM hr_employee_contracts
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                error_log("HR Contracts Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/hr/contracts/index.php', [
            'contracts' => $contracts,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'statusFilter' => $statusFilter
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        $contract = null;
        $employees = [];
        $autoCode = 'CNT-' . str_pad((string)(($this->getContractCount()) + 1), 4, '0', STR_PAD_LEFT);

        if ($this->db) {
            try {
                $employees = $this->db->query("SELECT id, emp_code, name_ar FROM hr_employees WHERE status = 'active' ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/hr/contracts/create.php', [
            'contract' => $contract,
            'employees' => $employees,
            'autoCode' => $autoCode
        ], $response);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            if (empty($data['contract_code']) || empty($data['employee_id']) || empty($data['start_date'])) {
                throw new Exception("يرجى تعبئة الحقول الأساسية للعقد.");
            }

            $stmt = $this->db->prepare("
                INSERT INTO hr_employee_contracts 
                (contract_code, employee_id, start_date, end_date, basic_salary, housing_allowance, transport_allowance, status, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
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
            ]);

            $_SESSION['flash_msg'] = "تم إضافة العقد بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/contracts/create');
        }

        return new RedirectResponse('/ERP/hr/contracts');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $contract = null;
        $employees = [];

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("SELECT * FROM hr_employee_contracts WHERE id = ?");
            $stmt->execute([$id]);
            $contract = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$contract) throw new Exception("بيانات العقد غير موجودة.");

            $employees = $this->db->query("SELECT id, emp_code, name_ar FROM hr_employees ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/contracts');
        }

        return $this->renderView('/resources/views/hr/contracts/create.php', [
            'contract' => $contract,
            'employees' => $employees,
            'autoCode' => $contract->contract_code
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

            $_SESSION['flash_msg'] = "تم تحديث بيانات العقد بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/hr/contracts/{$id}/edit");
        }

        return new RedirectResponse('/ERP/hr/contracts');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM hr_employee_contracts WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف العقد بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/hr/contracts');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $contract = null;

        if ($this->db) {
            $stmt = $this->db->prepare("
                SELECT c.*, e.name_ar as employee_name, e.emp_code, e.national_id, d.name_ar as dept_name, dg.title_ar as desig_name
                FROM hr_employee_contracts c
                LEFT JOIN hr_employees e ON c.employee_id = e.id
                LEFT JOIN hr_departments d ON e.department_id = d.id
                LEFT JOIN hr_designations dg ON e.designation_id = dg.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $contract = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$contract) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "العقد غير موجود.";
            return new RedirectResponse('/ERP/hr/contracts');
        }

        return $this->renderView('/resources/views/hr/contracts/show.php', [
            'contract' => $contract
        ], $response);
    }

    private function getContractCount(): int
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM hr_employee_contracts")->fetchColumn();
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