<?php
// Path: app/Modules/HR/Http/Controllers/DesignationController.php

namespace App\Modules\HR\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class DesignationController extends Controller
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
        if (preg_match('#/hr/designations/(\d+)#', $uri, $matches)) return (int)$matches[1];
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
        if (preg_match('#/hr/designations/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/hr/designations/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/hr/designations/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/hr/designations/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $deptFilter = trim($_GET['department_id'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $designations = [];
        $departments = [];
        $stats = (object)[
            'total_designations' => 0,
            'active_designations' => 0,
            'inactive_designations' => 0,
            'linked_depts' => 0,
            'grades_count' => 0
        ];
        $totalPages = 1;

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condD = $this->buildBranchCond('d', $branchId, 'hr_designations');
            $condDep = $this->buildBranchCond('dep', $branchId, 'hr_departments');

            $departments = $this->db->query("SELECT id, code, name_ar, name_en FROM hr_departments dep WHERE (dep.company_id = {$companyId} OR dep.company_id IS NULL OR dep.company_id = 0) {$condDep} AND status = 'active' ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

            $where = ["(d.company_id = {$companyId} OR d.company_id IS NULL OR d.company_id = 0)"];
            $params = [];

            if ($search !== '') {
                $where[] = "(d.code LIKE ? OR d.title_ar LIKE ? OR d.title_en LIKE ? OR d.pay_grade LIKE ? OR dep.name_ar LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like, $like, $like);
            }

            if ($deptFilter !== '') {
                $where[] = "d.department_id = ?";
                $params[] = (int)$deptFilter;
            }

            if ($statusFilter !== '') {
                $where[] = "d.status = ?";
                $params[] = $statusFilter;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM hr_designations d
                LEFT JOIN hr_departments dep ON d.department_id = dep.id
                $whereSql $condD
            ");
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCount / $limit));

            $joinBranch = $this->hasColumn('hr_designations', 'branch_id') ? "LEFT JOIN sys_branches br ON d.branch_id = br.id" : "";
            $colBranch = $this->hasColumn('hr_designations', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT d.*, dep.name_ar as department_name_ar, dep.name_en as department_name_en, {$colBranch}
                FROM hr_designations d
                LEFT JOIN hr_departments dep ON d.department_id = dep.id
                {$joinBranch}
                $whereSql $condD
                ORDER BY d.id DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $designations = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $statsData = $this->db->query("
                SELECT 
                    COUNT(*) as total_designations,
                    SUM(IF(d.status = 'active', 1, 0)) as active_designations,
                    SUM(IF(d.status = 'inactive', 1, 0)) as inactive_designations,
                    COUNT(DISTINCT d.department_id) as linked_depts,
                    COUNT(DISTINCT NULLIF(d.pay_grade, '')) as grades_count
                FROM hr_designations d
                WHERE (d.company_id = {$companyId} OR d.company_id IS NULL OR d.company_id = 0) {$condD}
            ")->fetch(PDO::FETCH_OBJ);
            if ($statsData) $stats = $statsData;

        } catch (Throwable $e) {
            error_log("HR Designations Index Error: " . $e->getMessage());
            $designations = [];
            $totalPages = 1;
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/hr/designations/index.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response)
    {
        $designation = null;
        $departments = [];
        $autoCode = 'DSG-' . str_pad((string)(($this->getDesignationCount()) + 1), 3, '0', STR_PAD_LEFT);

        if ($this->db) {
            try {
                $companyId = $this->getCompanyId();
                $branchId = $this->getActiveBranchId();
                $condDep = $this->buildBranchCond('dep', $branchId, 'hr_departments');

                $departments = $this->db->query("SELECT id, code, name_ar, name_en FROM hr_departments dep WHERE (dep.company_id = {$companyId} OR dep.company_id IS NULL OR dep.company_id = 0) AND status = 'active' {$condDep} ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/designations/create.php';
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

            if (empty($data['code']) || empty($data['title_ar'])) {
                throw new Exception($isAr ? "يرجى تعبئة الحقول الأساسية للمسمى الوظيفي." : "Designation code and Arabic title are required.");
            }

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $hasBranch = $this->hasColumn('hr_designations', 'branch_id');
            $hasCompany = $this->hasColumn('hr_designations', 'company_id');

            $extraCols = "";
            $extraVals = "";
            $params = [
                trim($data['code']),
                trim($data['title_ar']),
                trim($data['title_en'] ?? ''),
                !empty($data['department_id']) ? (int)$data['department_id'] : null,
                trim($data['pay_grade'] ?? ''),
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
                INSERT INTO hr_designations (code, title_ar, title_en, department_id, pay_grade, status, description, created_by {$extraCols})
                VALUES (?, ?, ?, ?, ?, ?, ?, ? {$extraVals})
            ");
            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم إضافة المسمى الوظيفي بنجاح." : "Designation added successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/designations/create");
            exit;
        }

        header("Location: /ERP/hr/designations");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $designation = null;
        $departments = [];

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $stmt = $this->db->prepare("SELECT * FROM hr_designations WHERE id = ?");
            $stmt->execute([$id]);
            $designation = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$designation) throw new Exception("بيانات المسمى الوظيفي غير موجودة.");

            $condDep = $this->buildBranchCond('dep', $branchId, 'hr_departments');
            $departments = $this->db->query("SELECT id, code, name_ar, name_en FROM hr_departments dep WHERE (dep.company_id = {$companyId} OR dep.company_id IS NULL OR dep.company_id = 0) AND status = 'active' {$condDep} ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

            $autoCode = $designation->code;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/designations");
            exit;
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/designations/create.php';
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
                UPDATE hr_designations 
                SET title_ar = ?, title_en = ?, department_id = ?, pay_grade = ?, status = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($data['title_ar']),
                trim($data['title_en'] ?? ''),
                !empty($data['department_id']) ? (int)$data['department_id'] : null,
                trim($data['pay_grade'] ?? ''),
                $data['status'] ?? 'active',
                trim($data['description'] ?? ''),
                $id
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث المسمى الوظيفي بنجاح." : "Designation updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/hr/designations/{$id}/edit");
            exit;
        }

        header("Location: /ERP/hr/designations");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if ($this->db && $id) {
                // التأكد من عدم ارتباط المسمى بموظفين نشطين
                $empCheck = $this->db->prepare("SELECT COUNT(*) FROM hr_employees WHERE designation_id = ?");
                $empCheck->execute([$id]);
                if ($empCheck->fetchColumn() > 0) {
                    throw new Exception($isAr ? "لا يمكن حذف المسمى الوظيفي لوجود موظفين مرتبطيين به." : "Cannot delete designation assigned to employees.");
                }

                $this->db->prepare("DELETE FROM hr_designations WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = $isAr ? "تم حذف المسمى الوظيفي بنجاح." : "Designation deleted successfully.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/hr/designations");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        $designation = null;

        if ($this->db && $id) {
            $joinBranch = $this->hasColumn('hr_designations', 'branch_id') ? "LEFT JOIN sys_branches br ON d.branch_id = br.id" : "";
            $colBranch = $this->hasColumn('hr_designations', 'branch_id') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT d.*, dep.name_ar as department_name_ar, dep.name_en as department_name_en, {$colBranch}
                FROM hr_designations d
                LEFT JOIN hr_departments dep ON d.department_id = dep.id
                {$joinBranch}
                WHERE d.id = ?
            ");
            $stmt->execute([$id]);
            $designation = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$designation) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "المسمى الوظيفي غير موجود.";
            header("Location: /ERP/hr/designations");
            exit;
        }

        ob_start();
        include $this->basePath . '/resources/views/hr/designations/show.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    private function getDesignationCount()
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM hr_designations")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}