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
        if (preg_match('#/hr/leaves/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

   public function index(Request $request, Response $response): Response
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
                $where = ["1=1"];

                if ($search !== '') {
                    $where[] = "(e.emp_code LIKE :s1 OR e.name_ar LIKE :s2)";
                }

                if ($statusFilter !== '') {
                    $where[] = "l.status = :status_val";
                }

                if ($typeFilter !== '') {
                    $where[] = "l.leave_type = :type_val";
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM hr_leaves l
                    LEFT JOIN hr_employees e ON l.employee_id = e.id
                    $whereSql
                ");
                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $countStmt->bindValue(':s1', $searchVal);
                    $countStmt->bindValue(':s2', $searchVal);
                }
                if ($statusFilter !== '') $countStmt->bindValue(':status_val', $statusFilter);
                if ($typeFilter !== '') $countStmt->bindValue(':type_val', $typeFilter);

                $countStmt->execute();
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT l.*, e.name_ar as employee_name, e.emp_code, d.name_ar as dept_name
                    FROM hr_leaves l
                    LEFT JOIN hr_employees e ON l.employee_id = e.id
                    LEFT JOIN hr_departments d ON e.department_id = d.id
                    $whereSql
                    ORDER BY l.id DESC 
                    LIMIT :limit OFFSET :offset
                ");

                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $stmt->bindValue(':s1', $searchVal);
                    $stmt->bindValue(':s2', $searchVal);
                }
                if ($statusFilter !== '') $stmt->bindValue(':status_val', $statusFilter);
                if ($typeFilter !== '') $stmt->bindValue(':type_val', $typeFilter);
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

                $stmt->execute();
                $leaves = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_leaves,
                        SUM(IF(status = 'pending', 1, 0)) as pending_leaves,
                        SUM(IF(status = 'approved', 1, 0)) as approved_leaves,
                        SUM(IF(status = 'rejected', 1, 0)) as rejected_leaves
                    FROM hr_leaves
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                error_log("HR Leaves Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/hr/leaves/index.php', [
            'leaves' => $leaves,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'typeFilter' => $typeFilter
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        $employees = [];

        if ($this->db) {
            try {
                $employees = $this->db->query("SELECT id, emp_code, name_ar FROM hr_employees WHERE status = 'active' ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/hr/leaves/create.php', [
            'employees' => $employees
        ], $response);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            if (empty($data['employee_id']) || empty($data['start_date']) || empty($data['end_date']) || empty($data['leave_type'])) {
                throw new Exception("يرجى تعبئة كافة بيانات طلب الإجازة.");
            }

            $startDate = strtotime($data['start_date']);
            $endDate = strtotime($data['end_date']);

            if ($endDate < $startDate) {
                throw new Exception("تاريخ نهاية الإجازة لا يمكن أن يكون قبل تاريخ البداية.");
            }

            $daysCount = round(($endDate - $startDate) / 86400) + 1;

            $stmt = $this->db->prepare("
                INSERT INTO hr_leaves (employee_id, leave_type, start_date, end_date, days_count, reason, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)
            ");
            $stmt->execute([
                (int)$data['employee_id'],
                $data['leave_type'],
                $data['start_date'],
                $data['end_date'],
                $daysCount,
                trim($data['reason'] ?? ''),
                $_SESSION['user_id'] ?? 1
            ]);

            $_SESSION['flash_msg'] = "تم تقديم طلب الإجازة بنجاح وهو قيد المراجعة.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/leaves/create');
        }

        return new RedirectResponse('/ERP/hr/leaves');
    }

    public function approve(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $stmt = $this->db->prepare("UPDATE hr_leaves SET status = 'approved', approved_by = ? WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'] ?? 1, $id]);
                $_SESSION['flash_msg'] = "تم اعتماد وإقرار طلب الإجازة بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/hr/leaves');
    }

    public function reject(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $stmt = $this->db->prepare("UPDATE hr_leaves SET status = 'rejected', approved_by = ? WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'] ?? 1, $id]);
                $_SESSION['flash_msg'] = "تم رفض طلب الإجازة.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/hr/leaves');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM hr_leaves WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف طلب الإجازة بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/hr/leaves');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $leave = null;

        if ($this->db) {
            $stmt = $this->db->prepare("
                SELECT l.*, e.name_ar as employee_name, e.emp_code, d.name_ar as dept_name, dg.title_ar as desig_name
                FROM hr_leaves l
                LEFT JOIN hr_employees e ON l.employee_id = e.id
                LEFT JOIN hr_departments d ON e.department_id = d.id
                LEFT JOIN hr_designations dg ON e.designation_id = dg.id
                WHERE l.id = ?
            ");
            $stmt->execute([$id]);
            $leave = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$leave) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "طلب الإجازة غير موجود.";
            return new RedirectResponse('/ERP/hr/leaves');
        }

        return $this->renderView('/resources/views/hr/leaves/show.php', [
            'leave' => $leave
        ], $response);
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