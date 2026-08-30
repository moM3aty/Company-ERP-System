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
        if (preg_match('#/projects/milestones/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
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
                $projects = $this->db->query("SELECT id, code, name_ar FROM projects ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ) ?: [];

                $where = ["1=1"];
                $params = [];

                if ($search !== '') {
                    $where[] = "(m.milestone_code LIKE ? OR m.title_ar LIKE ? OR m.assigned_to LIKE ? OR p.name_ar LIKE ?)";
                    $like = "%{$search}%";
                    $params = array_merge($params, [$like, $like, $like, $like]);
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
                    SELECT m.*, p.name_ar as project_name, p.code as project_code
                    FROM project_milestones m
                    LEFT JOIN projects p ON m.project_id = p.id
                    $whereSql
                    ORDER BY m.due_date ASC, m.id DESC 
                    LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $milestones = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_milestones,
                        SUM(IF(status = 'in_progress', 1, 0)) as in_progress_count,
                        SUM(IF(status = 'completed', 1, 0)) as completed_count,
                        SUM(IF(status = 'delayed' OR (due_date < CURRENT_DATE() AND status NOT IN ('completed','cancelled')), 1, 0)) as delayed_count,
                        COALESCE(SUM(estimated_cost), 0) as total_estimated_cost
                    FROM project_milestones
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) {
                    $stats = $statsData;
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
        $milestone = null; 
        $projects = [];
        $autoCode = 'MS-' . date('Y') . '-0001';

        if ($this->db) {
            try {
                $projects = $this->db->query("SELECT id, code, name_ar FROM projects ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                $nextSeq = (int)$this->db->query("SELECT COUNT(*) FROM project_milestones")->fetchColumn() + 1;
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

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            if (empty($data['milestone_code']) || empty($data['title_ar']) || empty($data['project_id']) || empty($data['start_date']) || empty($data['due_date'])) {
                throw new Exception("يرجى تعبئة كافة الحقول الأساسية للمرحلة/المهمة.");
            }

            $stmt = $this->db->prepare("
                INSERT INTO project_milestones 
                (project_id, milestone_code, title_ar, title_en, assigned_to, start_date, due_date, completion_date, progress_percent, estimated_cost, actual_cost, status, priority, description, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                (int)$data['project_id'],
                trim($data['milestone_code']),
                trim($data['title_ar']),
                trim($data['title_en'] ?? ''),
                trim($data['assigned_to'] ?? ''),
                $data['start_date'],
                $data['due_date'],
                !empty($data['completion_date']) ? $data['completion_date'] : null,
                !empty($data['progress_percent']) ? (float)$data['progress_percent'] : 0.00,
                !empty($data['estimated_cost']) ? (float)$data['estimated_cost'] : 0.00,
                !empty($data['actual_cost']) ? (float)$data['actual_cost'] : 0.00,
                $data['status'] ?? 'pending',
                $data['priority'] ?? 'medium',
                trim($data['description'] ?? ''),
                $_SESSION['user_id'] ?? 1
            ]);

            $_SESSION['flash_msg'] = "تم إنشاء وتسجيل المرحلة/المهمة بنجاح.";
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

        $milestone = null;
        $projects = [];
        $autoCode = '';

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("SELECT * FROM project_milestones WHERE id = ?");
            $stmt->execute([$id]);
            $milestone = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$milestone) throw new Exception("بيانات المرحلة غير موجودة.");

            $projects = $this->db->query("SELECT id, code, name_ar FROM projects ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ) ?: [];
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

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("
                UPDATE project_milestones 
                SET project_id = ?, title_ar = ?, title_en = ?, assigned_to = ?, start_date = ?, due_date = ?, completion_date = ?, progress_percent = ?, estimated_cost = ?, actual_cost = ?, status = ?, priority = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([
                (int)$data['project_id'],
                trim($data['title_ar']),
                trim($data['title_en'] ?? ''),
                trim($data['assigned_to'] ?? ''),
                $data['start_date'],
                $data['due_date'],
                !empty($data['completion_date']) ? $data['completion_date'] : null,
                !empty($data['progress_percent']) ? (float)$data['progress_percent'] : 0.00,
                !empty($data['estimated_cost']) ? (float)$data['estimated_cost'] : 0.00,
                !empty($data['actual_cost']) ? (float)$data['actual_cost'] : 0.00,
                $data['status'] ?? 'pending',
                $data['priority'] ?? 'medium',
                trim($data['description'] ?? ''),
                $id
            ]);

            $_SESSION['flash_msg'] = "تم تحديث بيانات المرحلة/المهمة بنجاح.";
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

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM project_milestones WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف المرحلة/المهمة بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/projects/milestones');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $milestone = null;

        if ($this->db) {
            $stmt = $this->db->prepare("
                SELECT m.*, p.name_ar as project_name, p.code as project_code
                FROM project_milestones m
                LEFT JOIN projects p ON m.project_id = p.id
                WHERE m.id = ?
            ");
            $stmt->execute([$id]);
            $milestone = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$milestone) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "سجل المرحلة غير موجود.";
            return new RedirectResponse('/ERP/projects/milestones');
        }

        return $this->renderView('/resources/views/projects/milestones/show.php', [
            'milestone' => $milestone
        ], $response);
    }

    private function renderView(string $viewPath, array $data, Response $response): Response
    {
        extract($data);
        $fullPath = $this->basePath . $viewPath;

        if (!file_exists($fullPath)) {
            die("<div style='padding:30px; background:#fff; color:#dc2626; font-family:monospace; direction:ltr;'><h3>View File Missing:</h3>" . htmlspecialchars($fullPath) . "</div>");
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
            die("<div style='padding:30px; background:#fff; color:#dc2626; font-family:monospace; direction:ltr;'>
                    <h3>Milestones View Error:</h3>
                    <p><b>Message:</b> " . htmlspecialchars($e->getMessage()) . "</p>
                    <p><b>File:</b> " . htmlspecialchars($e->getFile()) . "</p>
                    <p><b>Line:</b> " . $e->getLine() . "</p>
                 </div>");
        }
    }
}