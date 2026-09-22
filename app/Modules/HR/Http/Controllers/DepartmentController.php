<?php
// Path: app/Modules/HR/Http/Controllers/DepartmentController.php

namespace App\Modules\HR\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class DepartmentController extends Controller
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
        if (preg_match('#/hr/departments/(\d+)#', $uri, $matches)) return (int)$matches[1];
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
        if (preg_match('#/hr/departments/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/hr/departments/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/hr/departments/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/hr/departments/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $departments = [];
        $stats = (object)[
            'total_depts' => 0,
            'active_depts' => 0,
            'parent_depts' => 0,
            'sub_depts' => 0
        ];
        $totalPages = 1;

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condDept = $this->buildBranchCond('d', $branchId, 'hr_departments');

            $where = ["(d.company_id = {$companyId} OR d.company_id IS NULL OR d.company_id = 0)"];
            $params = [];

            if ($search !== '') {
                $where[] = "(d.code LIKE ? OR d.name_ar LIKE ? OR d.name_en LIKE ? OR d.manager_name LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like, $like);
            }

            if ($statusFilter !== '') {
                $where[] = "d.status = ?";
                $params[] = $statusFilter;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM hr_departments d $whereSql $condDept");
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCount / $limit));

            $joinBranch = $this->hasColumn('hr_departments', 'branch_id') ? "LEFT JOIN sys_branches br ON d.branch_id = br.id" : "";
            $colBranch = $this->hasColumn('hr_departments', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT d.*, p.name_ar as parent_name_ar, p.name_en as parent_name_en, {$colBranch}
                FROM hr_departments d
                LEFT JOIN hr_departments p ON d.parent_id = p.id
                {$joinBranch}
                $whereSql $condDept
                ORDER BY d.id DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $departments = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $statsData = $this->db->query("
                SELECT 
                    COUNT(*) as total_depts,
                    SUM(IF(d.status = 'active', 1, 0)) as active_depts,
                    SUM(IF(d.parent_id IS NULL OR d.parent_id = 0, 1, 0)) as parent_depts,
                    SUM(IF(d.parent_id IS NOT NULL AND d.parent_id > 0, 1, 0)) as sub_depts
                FROM hr_departments d
                WHERE (d.company_id = {$companyId} OR d.company_id IS NULL OR d.company_id = 0) {$condDept}
            ")->fetch(PDO::FETCH_OBJ);
            if ($statsData) $stats = $statsData;

        } catch (Throwable $e) {
            error_log("HR Departments Index Error: " . $e->getMessage());
            $departments = [];
            $totalPages = 1;
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/hr/departments/index.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response)
    {
        $department = null;
        $parentDepts = [];
        $autoCode = 'DEP-' . str_pad((string)(($this->getDeptCount()) + 1), 3, '0', STR_PAD_LEFT);

        if ($this->db) {
            try {
                $companyId = $this->getCompanyId();
                $branchId = $this->getActiveBranchId();
                $condDept = $this->buildBranchCond('d', $branchId, 'hr_departments');

                $parentDepts = $this->db->query("SELECT id, code, name_ar, name_en FROM hr_departments d WHERE (d.company_id = {$companyId} OR d.company_id IS NULL OR d.company_id = 0) AND d.status = 'active' {$condDept} ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/departments/create.php';
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

            if (empty($data['code']) || empty($data['name_ar'])) {
                throw new Exception($isAr ? "يرجى تعبئة كود الإدارة واسم الإدارة بالعربية." : "Code and Arabic Name are required.");
            }

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $hasBranch = $this->hasColumn('hr_departments', 'branch_id');
            $hasCompany = $this->hasColumn('hr_departments', 'company_id');

            $extraCols = "";
            $extraVals = "";
            $params = [
                trim($data['code']),
                trim($data['name_ar']),
                trim($data['name_en'] ?? ''),
                !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
                trim($data['manager_name'] ?? ''),
                $data['status'] ?? 'active',
                trim($data['description'] ?? ''),
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
                INSERT INTO hr_departments (code, name_ar, name_en, parent_id, manager_name, status, description, created_by {$extraCols})
                VALUES (?, ?, ?, ?, ?, ?, ?, ? {$extraVals})
            ");
            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم إنشاء الإدارة/القسم بنجاح." : "Department created successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/departments/create");
            exit;
        }

        header("Location: /ERP/hr/departments");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $department = null;
        $parentDepts = [];

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $stmt = $this->db->prepare("SELECT * FROM hr_departments WHERE id = ?");
            $stmt->execute([$id]);
            $department = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$department) throw new Exception("بيانات الإدارة غير موجودة.");

            $condDept = $this->buildBranchCond('d', $branchId, 'hr_departments');
            $parentDepts = $this->db->query("SELECT id, code, name_ar, name_en FROM hr_departments d WHERE id != {$id} AND (d.company_id = {$companyId} OR d.company_id IS NULL OR d.company_id = 0) AND status = 'active' {$condDept} ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

            $autoCode = $department->code;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/departments");
            exit;
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/departments/create.php';
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
                UPDATE hr_departments 
                SET name_ar = ?, name_en = ?, parent_id = ?, manager_name = ?, status = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($data['name_ar']),
                trim($data['name_en'] ?? ''),
                !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
                trim($data['manager_name'] ?? ''),
                $data['status'] ?? 'active',
                trim($data['description'] ?? ''),
                $id
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات الإدارة بنجاح." : "Department updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/departments/{$id}/edit");
            exit;
        }

        header("Location: /ERP/hr/departments");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if ($this->db && $id) {
                // التأكد من عدم وجود أطفال (أقسام فرعية)
                $childCheck = $this->db->prepare("SELECT COUNT(*) FROM hr_departments WHERE parent_id = ?");
                $childCheck->execute([$id]);
                if ($childCheck->fetchColumn() > 0) {
                    throw new Exception($isAr ? "لا يمكن حذف الإدارة لوجود أقسام فرعية تابعة لها." : "Cannot delete department with sub-departments.");
                }

                $this->db->prepare("DELETE FROM hr_departments WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = $isAr ? "تم حذف الإدارة/القسم بنجاح." : "Department deleted successfully.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/hr/departments");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        $department = null;

        if ($this->db && $id) {
            $joinBranch = $this->hasColumn('hr_departments', 'branch_id') ? "LEFT JOIN sys_branches br ON d.branch_id = br.id" : "";
            $colBranch = $this->hasColumn('hr_departments', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT d.*, p.name_ar as parent_name_ar, p.name_en as parent_name_en, {$colBranch}
                FROM hr_departments d
                LEFT JOIN hr_departments p ON d.parent_id = p.id
                {$joinBranch}
                WHERE d.id = ?
            ");
            $stmt->execute([$id]);
            $department = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$department) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "سجل الإدارة غير موجود.";
            header("Location: /ERP/hr/departments");
            exit;
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/departments/show.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    private function getDeptCount()
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM hr_departments")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}