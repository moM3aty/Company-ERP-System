<?php
// Path: app/Modules/Projects/Http/Controllers/MilestoneController.php

namespace App\Modules\Projects\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class MilestoneController extends Controller
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
        if (preg_match('#/projects/milestones/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/projects/milestones/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/projects/milestones/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/projects/milestones/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/projects/milestones/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $projectId = trim($_GET['project_id'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $priorityFilter = trim($_GET['priority'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $milestones = [];
        $projects = [];
        $stats = (object)[
            'total_milestones' => 0,
            'in_progress_count' => 0,
            'completed_count' => 0,
            'delayed_count' => 0,
            'total_estimated_cost' => 0
        ];
        $totalPages = 1;

        if ($this->db) {
            try {
                $bCond = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";
                $projects = $this->db->query("SELECT id, code, COALESCE(name_ar, name_en) as name_ar FROM projects WHERE company_id = $companyId $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ) ?: [];

                $where = ["m.company_id = ?"];
                $params = [$companyId];

                if ($branchId > 0) {
                    $where[] = "(m.branch_id = ? OR m.branch_id IS NULL OR m.branch_id = 0)";
                    $params[] = $branchId;
                }

                if ($search !== '') {
                    $where[] = "(m.milestone_code LIKE ? OR m.title_ar LIKE ? OR m.title_en LIKE ? OR m.assigned_to LIKE ? OR p.name_ar LIKE ?)";
                    $like = "%{$search}%";
                    $params = array_merge($params, [$like, $like, $like, $like, $like]);
                }

                if ($projectId !== '') {
                    $where[] = "m.project_id = ?";
                    $params[] = (int)$projectId;
                }

                if ($statusFilter !== '') {
                    $where[] = "m.status = ?";
                    $params[] = $statusFilter;
                }

                if ($priorityFilter !== '') {
                    $where[] = "m.priority = ?";
                    $params[] = $priorityFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM project_milestones m
                    LEFT JOIN projects p ON m.project_id = p.id
                    $whereSql
                ");
                $countStmt->execute($params);
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT m.*, COALESCE(p.name_ar, p.name_en) as project_name, p.code as project_code
                    FROM project_milestones m
                    LEFT JOIN projects p ON m.project_id = p.id
                    $whereSql
                    ORDER BY m.due_date ASC, m.id DESC 
                    LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $milestones = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                // تحويل التكاليف التقديرية والفعلية
                foreach ($milestones as $m) {
                    $m->estimated_cost = convert_amount($m->estimated_cost);
                    $m->actual_cost = convert_amount($m->actual_cost);
                }

                $statsStmt = $this->db->prepare("
                    SELECT 
                        COUNT(*) as total_milestones,
                        SUM(IF(status = 'in_progress', 1, 0)) as in_progress_count,
                        SUM(IF(status = 'completed', 1, 0)) as completed_count,
                        SUM(IF(status = 'delayed' OR (due_date < CURRENT_DATE() AND status NOT IN ('completed','cancelled')), 1, 0)) as delayed_count,
                        COALESCE(SUM(estimated_cost), 0) as total_estimated_cost
                    FROM project_milestones m
                    WHERE m.company_id = ? " . ($branchId > 0 ? " AND (m.branch_id = $branchId OR m.branch_id IS NULL OR m.branch_id = 0)" : "") . "
                ");
                $statsStmt->execute([$companyId]);
                $statsData = $statsStmt->fetch(PDO::FETCH_OBJ);
                if ($statsData) {
                    $stats = $statsData;
                    $stats->total_estimated_cost = convert_amount($stats->total_estimated_cost);
                }

            } catch (Throwable $e) {
                error_log("Milestones Index Error: " . $e->getMessage());
            }
        }

        $currentPage = $page;

        return $this->renderView('/resources/views/projects/milestones/index.php', [
            'milestones' => $milestones,
            'projects' => $projects,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'search' => $search,
            'projectId' => $projectId,
            'statusFilter' => $statusFilter,
            'priorityFilter' => $priorityFilter
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        $milestone = null; 
        $projects = [];
        $autoCode = 'MS-' . date('Y') . '-0001';

        if ($this->db) {
            try {
                $bCond = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";
                $projects = $this->db->query("SELECT id, code, COALESCE(name_ar, name_en) as name_ar FROM projects WHERE company_id = $companyId $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                $nextSeq = (int)$this->db->query("SELECT COUNT(*) FROM project_milestones WHERE company_id = $companyId")->fetchColumn() + 1;
                $autoCode = 'MS-' . date('Y') . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/projects/milestones/create.php', [
            'milestone' => $milestone,
            'projects' => $projects,
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

            if (empty($data['milestone_code']) || empty($data['title_ar']) || empty($data['project_id']) || empty($data['start_date']) || empty($data['due_date'])) {
                throw new Exception($isAr ? "يرجى تعبئة كافة الحقول الأساسية للمرحلة." : "Please fill all required fields.");
            }

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO project_milestones 
                    (company_id, branch_id, project_id, milestone_code, title_ar, title_en, assigned_to, start_date, due_date, completion_date, progress_percent, estimated_cost, actual_cost, status, priority, description, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $branchId,
                    (int)$data['project_id'], trim($data['milestone_code']),
                    trim($data['title_ar']), trim($data['title_en'] ?? ''),
                    trim($data['assigned_to'] ?? ''), $data['start_date'], $data['due_date'],
                    !empty($data['completion_date']) ? $data['completion_date'] : null,
                    !empty($data['progress_percent']) ? (float)$data['progress_percent'] : 0.00,
                    !empty($data['estimated_cost']) ? (float)$data['estimated_cost'] : 0.00,
                    !empty($data['actual_cost']) ? (float)$data['actual_cost'] : 0.00,
                    $data['status'] ?? 'pending', $data['priority'] ?? 'medium',
                    trim($data['description'] ?? ''), $_SESSION['user_id'] ?? 1
                ]);
            } catch (\PDOException $ex) {
                // Fallback
                $stmt = $this->db->prepare("
                    INSERT INTO project_milestones 
                    (project_id, milestone_code, title_ar, title_en, assigned_to, start_date, due_date, completion_date, progress_percent, estimated_cost, actual_cost, status, priority, description, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    (int)$data['project_id'], trim($data['milestone_code']),
                    trim($data['title_ar']), trim($data['title_en'] ?? ''),
                    trim($data['assigned_to'] ?? ''), $data['start_date'], $data['due_date'],
                    !empty($data['completion_date']) ? $data['completion_date'] : null,
                    !empty($data['progress_percent']) ? (float)$data['progress_percent'] : 0.00,
                    !empty($data['estimated_cost']) ? (float)$data['estimated_cost'] : 0.00,
                    !empty($data['actual_cost']) ? (float)$data['actual_cost'] : 0.00,
                    $data['status'] ?? 'pending', $data['priority'] ?? 'medium',
                    trim($data['description'] ?? ''), $_SESSION['user_id'] ?? 1
                ]);
            }

            $_SESSION['flash_msg'] = $isAr ? "تم تسجيل المرحلة/المهمة بنجاح." : "Milestone created successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/projects/milestones/create');
        }

        return new RedirectResponse('/ERP/projects/milestones');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        $milestone = null;
        $projects = [];
        $autoCode = '';

        try {
            if (!$this->db) throw new Exception("Database connection unavailable.");

            $stmt = $this->db->prepare("SELECT * FROM project_milestones WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $milestone = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$milestone) throw new Exception("بيانات المرحلة غير موجودة.");

            $bCond = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";
            $projects = $this->db->query("SELECT id, code, COALESCE(name_ar, name_en) as name_ar FROM projects WHERE company_id = $companyId $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $autoCode = $milestone->milestone_code;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/projects/milestones');
        }

        return $this->renderView('/resources/views/projects/milestones/create.php', [
            'milestone' => $milestone,
            'projects' => $projects,
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
            if (!$this->db) throw new Exception("Database connection unavailable.");

            $stmt = $this->db->prepare("
                UPDATE project_milestones 
                SET project_id = ?, title_ar = ?, title_en = ?, assigned_to = ?, start_date = ?, due_date = ?, completion_date = ?, progress_percent = ?, estimated_cost = ?, actual_cost = ?, status = ?, priority = ?, description = ?
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([
                (int)$data['project_id'], trim($data['title_ar']), trim($data['title_en'] ?? ''),
                trim($data['assigned_to'] ?? ''), $data['start_date'], $data['due_date'],
                !empty($data['completion_date']) ? $data['completion_date'] : null,
                !empty($data['progress_percent']) ? (float)$data['progress_percent'] : 0.00,
                !empty($data['estimated_cost']) ? (float)$data['estimated_cost'] : 0.00,
                !empty($data['actual_cost']) ? (float)$data['actual_cost'] : 0.00,
                $data['status'] ?? 'pending', $data['priority'] ?? 'medium',
                trim($data['description'] ?? ''), $id, $companyId
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث المهمة بنجاح." : "Milestone updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/projects/milestones/{$id}/edit");
        }

        return new RedirectResponse('/ERP/projects/milestones');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM project_milestones WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
                $_SESSION['flash_msg'] = "تم حذف المرحلة/المهمة بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء عملية الحذف.";
        }

        return new RedirectResponse('/ERP/projects/milestones');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        $milestone = null;

        if ($this->db) {
            $stmt = $this->db->prepare("
                SELECT m.*, COALESCE(p.name_ar, p.name_en) as project_name, p.code as project_code
                FROM project_milestones m
                LEFT JOIN projects p ON m.project_id = p.id
                WHERE m.id = ? AND m.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $milestone = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$milestone) {
            $_SESSION['flash_err'] = "سجل المرحلة غير موجود.";
            return new RedirectResponse('/ERP/projects/milestones');
        }

        // تحويل المبالغ
        $milestone->estimated_cost = convert_amount($milestone->estimated_cost);
        $milestone->actual_cost = convert_amount($milestone->actual_cost);

        return $this->renderView('/resources/views/projects/milestones/show.php', [
            'milestone' => $milestone
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