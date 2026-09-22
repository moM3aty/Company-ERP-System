<?php
// Path: app/Modules/HR/Http/Controllers/ShiftController.php

namespace App\Modules\HR\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class ShiftController extends Controller
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
        if (preg_match('#/hr/shifts/(\d+)#', $uri, $matches)) return (int)$matches[1];
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
        if (preg_match('#/hr/shifts/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/hr/shifts/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/hr/shifts/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/hr/shifts/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $shifts = [];
        $stats = (object)[
            'total_shifts' => 0,
            'active_shifts' => 0,
            'inactive_shifts' => 0
        ];
        $totalPages = 1;

        if ($this->db) {
            try {
                $companyId = $this->getCompanyId();
                $branchId = $this->getActiveBranchId();

                $condShift = $this->buildBranchCond('s', $branchId, 'hr_shifts');

                $where = ["(s.company_id = {$companyId} OR s.company_id IS NULL OR s.company_id = 0)"];
                $params = [];

                if ($search !== '') {
                    $where[] = "(s.code LIKE ? OR s.name_ar LIKE ?)";
                    $like = "%{$search}%";
                    array_push($params, $like, $like);
                }

                if ($statusFilter !== '') {
                    $where[] = "s.status = ?";
                    $params[] = $statusFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM hr_shifts s $whereSql $condShift");
                $countStmt->execute($params);
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $joinBranch = $this->hasColumn('hr_shifts', 'branch_id') ? "LEFT JOIN sys_branches br ON s.branch_id = br.id" : "";
                $colBranch  = $this->hasColumn('hr_shifts', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

                $stmt = $this->db->prepare("
                    SELECT s.*, {$colBranch} 
                    FROM hr_shifts s
                    {$joinBranch}
                    $whereSql $condShift
                    ORDER BY s.id DESC 
                    LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $shifts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_shifts,
                        SUM(IF(s.status = 'active', 1, 0)) as active_shifts,
                        SUM(IF(s.status = 'inactive', 1, 0)) as inactive_shifts
                    FROM hr_shifts s
                    WHERE (s.company_id = {$companyId} OR s.company_id IS NULL OR s.company_id = 0) {$condShift}
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                error_log("HR Shifts Index Error: " . $e->getMessage());
            }
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/hr/shifts/index.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response)
    {
        $shift = null;
        $autoCode = 'SHF-' . str_pad((string)(($this->getShiftCount()) + 1), 3, '0', STR_PAD_LEFT);

        ob_start();
        include $this->basePath . '/resources/views/hr/shifts/create.php';
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

            if (empty($data['code']) || empty($data['name_ar']) || empty($data['start_time']) || empty($data['end_time'])) {
                throw new Exception($isAr ? "يرجى تعبئة الحقول الأساسية لوردية العمل." : "Please fill in the required fields.");
            }

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $hasBranch = $this->hasColumn('hr_shifts', 'branch_id');
            $hasCompany = $this->hasColumn('hr_shifts', 'company_id');

            $extraCols = "";
            $extraVals = "";
            $params = [
                trim($data['code']),
                trim($data['name_ar']),
                $data['start_time'],
                $data['end_time'],
                !empty($data['grace_period_mins']) ? (int)$data['grace_period_mins'] : 0,
                $data['status'] ?? 'active',
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
                INSERT INTO hr_shifts (code, name_ar, start_time, end_time, grace_period_mins, status, created_by {$extraCols})
                VALUES (?, ?, ?, ?, ?, ?, ? {$extraVals})
            ");
            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم إضافة وردية العمل بنجاح." : "Shift added successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/shifts/create");
            exit;
        }

        header("Location: /ERP/hr/shifts");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $shift = null;

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("SELECT * FROM hr_shifts WHERE id = ?");
            $stmt->execute([$id]);
            $shift = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$shift) throw new Exception("بيانات الوردية غير موجودة.");

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/shifts");
            exit;
        }

        $autoCode = $shift->code;

        ob_start();
        include $this->basePath . '/resources/views/hr/shifts/create.php';
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
                UPDATE hr_shifts 
                SET name_ar = ?, start_time = ?, end_time = ?, grace_period_mins = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($data['name_ar']),
                $data['start_time'],
                $data['end_time'],
                !empty($data['grace_period_mins']) ? (int)$data['grace_period_mins'] : 0,
                $data['status'] ?? 'active',
                $id
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات الوردية بنجاح." : "Shift updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/shifts/{$id}/edit");
            exit;
        }

        header("Location: /ERP/hr/shifts");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if ($this->db && $id) {
                $this->db->prepare("DELETE FROM hr_shifts WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = $isAr ? "تم حذف الوردية بنجاح." : "Shift deleted successfully.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/hr/shifts");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        $shift = null;

        if ($this->db && $id) {
            $joinBranch = $this->hasColumn('hr_shifts', 'branch_id') ? "LEFT JOIN sys_branches br ON s.branch_id = br.id" : "";
            $colBranch  = $this->hasColumn('hr_shifts', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT s.*, {$colBranch}
                FROM hr_shifts s
                {$joinBranch}
                WHERE s.id = ?
            ");
            $stmt->execute([$id]);
            $shift = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$shift) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "الوردية غير موجودة.";
            header("Location: /ERP/hr/shifts");
            exit;
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/shifts/show.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    private function getShiftCount()
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM hr_shifts")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}