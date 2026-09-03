<?php
// Path: app/Modules/Projects/Http/Controllers/ProjectController.php

namespace App\Modules\Projects\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class ProjectController extends Controller
{
    private string $basePath;
    private ?PDO $db = null;

    public function __construct()
    {
        ini_set('display_errors', 0);
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
        if (preg_match('#/projects/list/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/projects/list/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/projects/list/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/projects/list/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/projects/list/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $fromDate = trim($_GET['start_date'] ?? '');
        $toDate = trim($_GET['end_date'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $projects = [];
        $stats = (object)['total_projects'=>0, 'total_value'=>0, 'total_spent'=>0, 'in_progress_count'=>0, 'completed_count'=>0];
        $totalPages = 1;

        if ($this->db) {
            try {
                $where = ["p.company_id = ?"];
                $params = [$companyId];

                if ($branchId > 0) {
                    $where[] = "(p.branch_id = ? OR p.branch_id IS NULL OR p.branch_id = 0)";
                    $params[] = $branchId;
                }

                if ($search !== '') {
                    $where[] = "(p.code LIKE ? OR p.name_ar LIKE ? OR p.name_en LIKE ? OR c.name_ar LIKE ?)";
                    $like = "%{$search}%";
                    $params = array_merge($params, [$like, $like, $like, $like]);
                }

                if ($statusFilter !== '') {
                    $where[] = "p.status = ?";
                    $params[] = $statusFilter;
                }

                if ($fromDate !== '') {
                    $where[] = "p.start_date >= ?";
                    $params[] = $fromDate;
                }

                if ($toDate !== '') {
                    $where[] = "p.start_date <= ?";
                    $params[] = $toDate;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM projects p
                    LEFT JOIN customers c ON p.customer_id = c.id
                    $whereSql
                ");
                $countStmt->execute($params);
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT p.*, c.name_ar as customer_name
                    FROM projects p
                    LEFT JOIN customers c ON p.customer_id = c.id
                    $whereSql
                    ORDER BY p.id DESC 
                    LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $projects = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                // تحويل القيم المالية
                foreach ($projects as $proj) {
                    $proj->contract_value = convert_amount($proj->contract_value);
                }

                $bCondStat = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";
                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_projects,
                        COALESCE(SUM(contract_value), 0) as total_value,
                        COALESCE(SUM(spent_amount), 0) as total_spent,
                        SUM(IF(status = 'in_progress', 1, 0)) as in_progress_count,
                        SUM(IF(status = 'completed', 1, 0)) as completed_count
                    FROM projects
                    WHERE company_id = $companyId $bCondStat
                ")->fetch(PDO::FETCH_OBJ);
                
                if ($statsData) {
                    $stats = $statsData;
                    $stats->total_value = convert_amount($stats->total_value);
                    $stats->total_spent = convert_amount($stats->total_spent);
                }

            } catch (Throwable $e) {
                error_log("Projects Index Error: " . $e->getMessage());
            }
        }

        $currentPage = $page;

        return $this->renderView('/resources/views/projects/list/index.php', [
            'projects' => $projects,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'fromDate' => $fromDate,
            'toDate' => $toDate
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        $project = null; 
        $customers = [];
        $autoCode = 'PRJ-' . date('Y') . '-0001';

        if ($this->db) {
            try {
                $customers = $this->db->query("SELECT id, name_ar FROM customers WHERE company_id = $companyId ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                $nextSeq = (int)$this->db->query("SELECT COUNT(*) FROM projects WHERE company_id = $companyId")->fetchColumn() + 1;
                $autoCode = 'PRJ-' . date('Y') . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/projects/list/create.php', [
            'project' => $project,
            'customers' => $customers,
            'autoCode' => $autoCode
        ], $response);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        try {
            if (!$this->db) throw new Exception("Database connection unavailable.");

            if (empty($data['code']) || empty($data['name_ar']) || empty($data['start_date'])) {
                throw new Exception($isAr ? "يرجى تعبئة كافة الحقول الأساسية للمشروع." : "Please fill required fields.");
            }

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO projects 
                    (company_id, branch_id, code, name_ar, name_en, customer_id, contract_value, estimated_budget, progress_percent, start_date, end_date, status, description, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $branchId,
                    trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''),
                    !empty($data['customer_id']) ? (int)$data['customer_id'] : null,
                    !empty($data['contract_value']) ? (float)$data['contract_value'] : 0.00,
                    !empty($data['estimated_budget']) ? (float)$data['estimated_budget'] : 0.00,
                    !empty($data['progress_percent']) ? (float)$data['progress_percent'] : 0.00,
                    $data['start_date'], !empty($data['end_date']) ? $data['end_date'] : null,
                    $data['status'] ?? 'in_progress', trim($data['description'] ?? ''), $_SESSION['user_id'] ?? 1
                ]);
            } catch (\PDOException $ex) {
                // الفولباك في حال كانت الأعمدة غير موجودة تماماً (للاحتياط)
                $stmt = $this->db->prepare("
                    INSERT INTO projects 
                    (code, name_ar, name_en, customer_id, contract_value, estimated_budget, progress_percent, start_date, end_date, status, description, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''),
                    !empty($data['customer_id']) ? (int)$data['customer_id'] : null,
                    !empty($data['contract_value']) ? (float)$data['contract_value'] : 0.00,
                    !empty($data['estimated_budget']) ? (float)$data['estimated_budget'] : 0.00,
                    !empty($data['progress_percent']) ? (float)$data['progress_percent'] : 0.00,
                    $data['start_date'], !empty($data['end_date']) ? $data['end_date'] : null,
                    $data['status'] ?? 'in_progress', trim($data['description'] ?? ''), $_SESSION['user_id'] ?? 1
                ]);
            }

            $_SESSION['flash_msg'] = $isAr ? "تم إنشاء وتسجيل المشروع بنجاح." : "Project created successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/projects/list/create');
        }

        return new RedirectResponse('/ERP/projects/list');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        $project = null; $customers = []; $autoCode = '';

        try {
            if (!$this->db) throw new Exception("Database connection unavailable.");

            $stmt = $this->db->prepare("SELECT * FROM projects WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $project = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$project) throw new Exception("المشروع غير موجود.");

            $customers = $this->db->query("SELECT id, name_ar FROM customers WHERE company_id = $companyId ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $autoCode = $project->code;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/projects/list');
        }

        return $this->renderView('/resources/views/projects/list/create.php', [
            'project' => $project,
            'customers' => $customers,
            'autoCode' => $autoCode
        ], $response);
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            if (!$this->db) throw new Exception("Database error.");

            $stmt = $this->db->prepare("
                UPDATE projects 
                SET name_ar=?, name_en=?, customer_id=?, contract_value=?, estimated_budget=?, progress_percent=?, start_date=?, end_date=?, status=?, description=?
                WHERE id=? AND company_id=?
            ");
            $stmt->execute([
                trim($data['name_ar']), trim($data['name_en'] ?? ''),
                !empty($data['customer_id']) ? (int)$data['customer_id'] : null,
                !empty($data['contract_value']) ? (float)$data['contract_value'] : 0.00,
                !empty($data['estimated_budget']) ? (float)$data['estimated_budget'] : 0.00,
                !empty($data['progress_percent']) ? (float)$data['progress_percent'] : 0.00,
                $data['start_date'], !empty($data['end_date']) ? $data['end_date'] : null,
                $data['status'] ?? 'in_progress', trim($data['description'] ?? ''),
                $id, $companyId
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات المشروع بنجاح." : "Project updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/projects/list/{$id}/edit");
        }

        return new RedirectResponse('/ERP/projects/list');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM projects WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
                $_SESSION['flash_msg'] = "تم حذف المشروع بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "لا يمكن حذف المشروع لوجود عمليات مالية أو عقود مرتبطة به.";
        }

        return new RedirectResponse('/ERP/projects/list');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;
        
        $project = null;
        if ($this->db) {
            $bCond = $branchId > 0 ? " AND (p.branch_id = $branchId OR p.branch_id IS NULL OR p.branch_id = 0)" : "";
            $stmt = $this->db->prepare("
                SELECT p.*, c.name_ar as customer_name
                FROM projects p
                LEFT JOIN customers c ON p.customer_id = c.id
                WHERE p.id = ? AND p.company_id = ? $bCond
            ");
            $stmt->execute([$id, $companyId]);
            $project = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$project) {
            $_SESSION['flash_err'] = "سجل المشروع غير موجود أو لا تملك صلاحية الوصول إليه.";
            return new RedirectResponse('/ERP/projects/list');
        }

        // تحويل القيم
        $project->contract_value = convert_amount($project->contract_value);
        $project->estimated_budget = convert_amount($project->estimated_budget);
        $project->spent_amount = convert_amount($project->spent_amount);

        return $this->renderView('/resources/views/projects/list/show.php', [
            'project' => $project
        ], $response);
    }

    private function renderView(string $viewPath, array $data, Response $response): Response
    {
        extract($data);
        $fullPath = $this->basePath . $viewPath;

        if (!file_exists($fullPath)) {
            die("<div style='padding:30px; background:#fff; color:#dc2626;'>View Missing: " . htmlspecialchars($fullPath) . "</div>");
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
            die("<div style='padding:30px; background:#fff; color:#dc2626;'>View Error: " . htmlspecialchars($e->getMessage()) . "</div>");
        }
    }
}