<?php
// Path: app/Modules/HR/Http/Controllers/LeaveController.php

namespace App\Modules\HR\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class LeaveController extends Controller
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
        if (preg_match('#/hr/leaves/(\d+)#', $uri, $matches)) return (int)$matches[1];
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
        if (preg_match('#/hr/leaves/(\d+)/approve#', $uri, $m)) return $this->approve($request, $response, (int)$m[1]);
        if (preg_match('#/hr/leaves/(\d+)/reject#', $uri, $m)) return $this->reject($request, $response, (int)$m[1]);
        if (preg_match('#/hr/leaves/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/hr/leaves/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $typeFilter = trim($_GET['leave_type'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $leaves = [];
        $stats = (object)[
            'total_leaves' => 0,
            'pending_leaves' => 0,
            'approved_leaves' => 0,
            'rejected_leaves' => 0
        ];
        $totalPages = 1;

        if ($this->db) {
            try {
                $companyId = $this->getCompanyId();
                $branchId = $this->getActiveBranchId();

                $condLeave = $this->buildBranchCond('l', $branchId, 'hr_leaves');

                $where = ["(l.company_id = {$companyId} OR l.company_id IS NULL OR l.company_id = 0)"];
                $params = [];

                if ($search !== '') {
                    $where[] = "(e.emp_code LIKE ? OR e.name_ar LIKE ? OR e.name_en LIKE ?)";
                    $like = "%{$search}%";
                    array_push($params, $like, $like, $like);
                }

                if ($statusFilter !== '') {
                    $where[] = "l.status = ?";
                    $params[] = $statusFilter;
                }

                if ($typeFilter !== '') {
                    $where[] = "l.leave_type = ?";
                    $params[] = $typeFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM hr_leaves l
                    LEFT JOIN hr_employees e ON l.employee_id = e.id
                    $whereSql $condLeave
                ");
                $countStmt->execute($params);
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $joinBranch = $this->hasColumn('hr_leaves', 'branch_id') ? "LEFT JOIN sys_branches br ON l.branch_id = br.id" : "";
                $colBranch  = $this->hasColumn('hr_leaves', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

                $stmt = $this->db->prepare("
                    SELECT l.*, e.name_ar as employee_name, e.name_en as employee_name_en, e.emp_code, d.name_ar as dept_name, d.name_en as dept_name_en, {$colBranch}
                    FROM hr_leaves l
                    LEFT JOIN hr_employees e ON l.employee_id = e.id
                    LEFT JOIN hr_departments d ON e.department_id = d.id
                    {$joinBranch}
                    $whereSql $condLeave
                    ORDER BY l.id DESC 
                    LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $leaves = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_leaves,
                        SUM(IF(l.status = 'pending', 1, 0)) as pending_leaves,
                        SUM(IF(l.status = 'approved', 1, 0)) as approved_leaves,
                        SUM(IF(l.status = 'rejected', 1, 0)) as rejected_leaves
                    FROM hr_leaves l
                    WHERE (l.company_id = {$companyId} OR l.company_id IS NULL OR l.company_id = 0) {$condLeave}
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                error_log("HR Leaves Index Error: " . $e->getMessage());
                $leaves = [];
                $totalPages = 1;
            }
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/hr/leaves/index.php';
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

                $employees = $this->db->query("SELECT e.id, e.emp_code, e.name_ar, e.name_en FROM hr_employees e WHERE (e.company_id = {$companyId} OR e.company_id IS NULL OR e.company_id = 0) AND e.status = 'active' {$condEmp} ORDER BY e.name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/leaves/create.php';
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

            if (empty($data['employee_id']) || empty($data['start_date']) || empty($data['end_date']) || empty($data['leave_type'])) {
                throw new Exception($isAr ? "يرجى تعبئة كافة بيانات طلب الإجازة." : "All leave request details are required.");
            }

            $startDate = strtotime($data['start_date']);
            $endDate = strtotime($data['end_date']);

            if ($endDate < $startDate) {
                throw new Exception($isAr ? "تاريخ نهاية الإجازة لا يمكن أن يكون قبل تاريخ البداية." : "Leave end date cannot be earlier than start date.");
            }

            $daysCount = round(($endDate - $startDate) / 86400) + 1;

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $hasBranch = $this->hasColumn('hr_leaves', 'branch_id');
            $hasCompany = $this->hasColumn('hr_leaves', 'company_id');

            $extraCols = "";
            $extraVals = "";
            $params = [
                (int)$data['employee_id'],
                $data['leave_type'],
                $data['start_date'],
                $data['end_date'],
                $daysCount,
                trim($data['reason'] ?? ''),
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
                INSERT INTO hr_leaves (employee_id, leave_type, start_date, end_date, days_count, reason, status, created_by {$extraCols})
                VALUES (?, ?, ?, ?, ?, ?, 'pending', ? {$extraVals})
            ");
            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم تقديم طلب الإجازة بنجاح وهو قيد المراجعة." : "Leave request submitted successfully and is pending approval.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/leaves/create");
            exit;
        }

        header("Location: /ERP/hr/leaves");
        exit;
    }

    public function approve(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if ($this->db && $id) {
                $stmt = $this->db->prepare("UPDATE hr_leaves SET status = 'approved', approved_by = ? WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'] ?? 1, $id]);
                $_SESSION['flash_msg'] = $isAr ? "تم اعتماد وإقرار طلب الإجازة بنجاح." : "Leave request approved successfully.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/hr/leaves");
        exit;
    }

    public function reject(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if ($this->db && $id) {
                $stmt = $this->db->prepare("UPDATE hr_leaves SET status = 'rejected', approved_by = ? WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'] ?? 1, $id]);
                $_SESSION['flash_msg'] = $isAr ? "تم رفض طلب الإجازة." : "Leave request rejected.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/hr/leaves");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if ($this->db && $id) {
                $this->db->prepare("DELETE FROM hr_leaves WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = $isAr ? "تم حذف طلب الإجازة بنجاح." : "Leave request deleted successfully.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/hr/leaves");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        $leave = null;

        if ($this->db && $id) {
            $joinBranch = $this->hasColumn('hr_leaves', 'branch_id') ? "LEFT JOIN sys_branches br ON l.branch_id = br.id" : "";
            $colBranch  = $this->hasColumn('hr_leaves', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT l.*, e.name_ar as employee_name, e.name_en as employee_name_en, e.emp_code, 
                       d.name_ar as dept_name, d.name_en as dept_name_en, 
                       dg.title_ar as desig_name, dg.title_en as desig_name_en, {$colBranch}
                FROM hr_leaves l
                LEFT JOIN hr_employees e ON l.employee_id = e.id
                LEFT JOIN hr_departments d ON e.department_id = d.id
                LEFT JOIN hr_designations dg ON e.designation_id = dg.id
                {$joinBranch}
                WHERE l.id = ?
            ");
            $stmt->execute([$id]);
            $leave = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$leave) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "طلب الإجازة غير موجود.";
            header("Location: /ERP/hr/leaves");
            exit;
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/leaves/show.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}