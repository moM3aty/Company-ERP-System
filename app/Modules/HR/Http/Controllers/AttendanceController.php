<?php
// Path: app/Modules/HR/Http/Controllers/AttendanceController.php

namespace App\Modules\HR\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class AttendanceController extends Controller
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
        if (preg_match('#/hr/attendance/(\d+)#', $uri, $matches)) return (int)$matches[1];
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
        if (preg_match('#/hr/attendance/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/hr/attendance/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $dateFilter = trim($_GET['date'] ?? date('Y-m-d'));
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $attendances = [];
        $stats = (object)[
            'present_today' => 0,
            'absent_today' => 0,
            'late_today' => 0,
            'on_leave_today' => 0
        ];
        $totalPages = 1;

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condAtt = $this->buildBranchCond('a', $branchId, 'hr_attendance');

            $where = ["(a.company_id = {$companyId} OR a.company_id IS NULL OR a.company_id = 0)"];
            $params = [];

            if ($search !== '') {
                $where[] = "(e.emp_code LIKE ? OR e.name_ar LIKE ? OR e.name_en LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like);
            }

            if ($dateFilter !== '') {
                $where[] = "a.date = ?";
                $params[] = $dateFilter;
            }

            if ($statusFilter !== '') {
                $where[] = "a.status = ?";
                $params[] = $statusFilter;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $statsDate = $dateFilter !== '' ? $dateFilter : date('Y-m-d');
            $statsData = $this->db->query("
                SELECT 
                    SUM(IF(status = 'present', 1, 0)) as present_today,
                    SUM(IF(status = 'absent', 1, 0)) as absent_today,
                    SUM(IF(status = 'late', 1, 0)) as late_today,
                    SUM(IF(status = 'on_leave', 1, 0)) as on_leave_today
                FROM hr_attendance a
                WHERE a.date = '{$statsDate}' AND (a.company_id = {$companyId} OR a.company_id IS NULL OR a.company_id = 0) {$condAtt}
            ")->fetch(PDO::FETCH_OBJ);
            if ($statsData) $stats = $statsData;

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) FROM hr_attendance a
                LEFT JOIN hr_employees e ON a.employee_id = e.id
                $whereSql $condAtt
            ");
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCount / $limit));

            $joinBranch = $this->hasColumn('hr_attendance', 'branch_id') ? "LEFT JOIN sys_branches br ON a.branch_id = br.id" : "";
            $colBranch  = $this->hasColumn('hr_attendance', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT a.*, e.name_ar as employee_name, e.name_en as employee_name_en, e.emp_code, {$colBranch}
                FROM hr_attendance a
                LEFT JOIN hr_employees e ON a.employee_id = e.id
                {$joinBranch}
                $whereSql $condAtt
                ORDER BY a.date DESC, a.check_in DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $attendances = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            error_log("HR Attendance Index Error: " . $e->getMessage());
            $attendances = [];
            $totalPages = 1;
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/hr/attendance/index.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function log(Request $request, Response $response)
    {
        $targetDate = trim($_GET['date'] ?? date('Y-m-d'));
        $employees = [];
        $attendanceMap = [];

        if ($this->db) {
            try {
                $companyId = $this->getCompanyId();
                $branchId = $this->getActiveBranchId();

                $condEmp = $this->buildBranchCond('e', $branchId, 'hr_employees');
                $condAtt = $this->buildBranchCond('a', $branchId, 'hr_attendance');

                // جلب الموظفين النشطين حسب الفرع والشركة
                $employees = $this->db->query("SELECT id, emp_code, name_ar, name_en FROM hr_employees e WHERE (e.company_id = {$companyId} OR e.company_id IS NULL OR e.company_id = 0) AND status = 'active' {$condEmp} ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                
                // جلب سجلات الحضور الموجودة مسبقاً لهذا اليوم لنفس الفرع والشركة
                $existingAtt = $this->db->prepare("SELECT a.* FROM hr_attendance a WHERE a.date = ? AND (a.company_id = ? OR a.company_id IS NULL OR a.company_id = 0) {$condAtt}");
                $existingAtt->execute([$targetDate, $companyId]);
                $records = $existingAtt->fetchAll(PDO::FETCH_OBJ) ?: [];
                
                foreach ($records as $rec) {
                    $attendanceMap[$rec->employee_id] = $rec;
                }
            } catch (Throwable $e) {
                error_log("Attendance Log Error: " . $e->getMessage());
            }
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/attendance/log.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function storeBulk(Request $request, Response $response)
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if (!$this->db) throw new Exception($isAr ? "اتصال قاعدة البيانات غير متوفر." : "Database connection lost.");

            $targetDate = $data['date'] ?? date('Y-m-d');
            $attendances = $data['attendance'] ?? [];
            $userId = $_SESSION['user_id'] ?? 1;

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $hasBranch = $this->hasColumn('hr_attendance', 'branch_id');
            $hasCompany = $this->hasColumn('hr_attendance', 'company_id');

            $extraCols = "";
            $extraVals = "";
            
            if ($hasCompany) {
                $extraCols .= ", company_id";
                $extraVals .= ", {$companyId}";
            }
            if ($hasBranch) {
                $extraCols .= ", branch_id";
                $extraVals .= ", {$branchId}";
            }

            $this->db->beginTransaction();

            $insertStmt = $this->db->prepare("
                INSERT INTO hr_attendance (employee_id, date, check_in, check_out, status, work_hours, notes, created_by {$extraCols})
                VALUES (?, ?, ?, ?, ?, ?, ?, ? {$extraVals})
            ");

            $updateStmt = $this->db->prepare("
                UPDATE hr_attendance 
                SET check_in = ?, check_out = ?, status = ?, work_hours = ?, notes = ?
                WHERE id = ?
            ");

            foreach ($attendances as $empId => $att) {
                $workHours = 0.00;
                $checkIn = !empty($att['check_in']) ? $att['check_in'] : null;
                $checkOut = !empty($att['check_out']) ? $att['check_out'] : null;

                if ($checkIn && $checkOut) {
                    $inTime = strtotime($checkIn);
                    $outTime = strtotime($checkOut);
                    $workHours = round(max(0, ($outTime - $inTime) / 3600), 2);
                }

                $status = $att['status'] ?? 'present';
                $notes = trim($att['notes'] ?? '');

                if (!empty($att['record_id'])) {
                    $updateStmt->execute([$checkIn, $checkOut, $status, $workHours, $notes, (int)$att['record_id']]);
                } else {
                    $insertStmt->execute([(int)$empId, $targetDate, $checkIn, $checkOut, $status, $workHours, $notes, $userId]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم حفظ السجل الجماعي للحضور والانصراف بنجاح ليوم {$targetDate}." : "Bulk attendance saved successfully for {$targetDate}.";

        } catch (Throwable $e) {
            if ($this->db && $this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/attendance/log");
            exit;
        }

        header("Location: /ERP/hr/attendance");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if ($this->db && $id) {
                $this->db->prepare("DELETE FROM hr_attendance WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = $isAr ? "تم حذف سجل الحضور بنجاح." : "Attendance record deleted successfully.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/hr/attendance");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        $attendance = null;

        if ($this->db && $id) {
            $joinBranch = $this->hasColumn('hr_attendance', 'branch_id') ? "LEFT JOIN sys_branches br ON a.branch_id = br.id" : "";
            $colBranch  = $this->hasColumn('hr_attendance', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT a.*, e.name_ar as employee_name, e.name_en as employee_name_en, e.emp_code, {$colBranch}
                FROM hr_attendance a
                LEFT JOIN hr_employees e ON a.employee_id = e.id
                {$joinBranch}
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $attendance = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$attendance) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "سجل الحضور غير موجود.";
            header("Location: /ERP/hr/attendance");
            exit;
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/attendance/show.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}