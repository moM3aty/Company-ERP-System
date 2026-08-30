<?php
// Path: app/Modules/HR/Http/Controllers/AppraisalController.php

namespace App\Modules\HR\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class AppraisalController extends Controller
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
        if (preg_match('#/hr/appraisals/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

  public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/hr/appraisals/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/hr/appraisals/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/hr/appraisals/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/hr/appraisals/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $appraisals = [];
        $stats = (object)[
            'total_appraisals' => 0,
            'avg_score' => 0.00,
            'approved_count' => 0,
            'draft_count' => 0
        ];
        $totalPages = 1;

        if ($this->db) {
            try {
                $where = ["1=1"];

                if ($search !== '') {
                    $where[] = "(a.appraisal_code LIKE :s1 OR e.name_ar LIKE :s2 OR e.emp_code LIKE :s3 OR a.evaluator_name LIKE :s4)";
                }

                if ($statusFilter !== '') {
                    $where[] = "a.status = :status_val";
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM hr_appraisals a
                    LEFT JOIN hr_employees e ON a.employee_id = e.id
                    $whereSql
                ");
                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $countStmt->bindValue(':s1', $searchVal);
                    $countStmt->bindValue(':s2', $searchVal);
                    $countStmt->bindValue(':s3', $searchVal);
                    $countStmt->bindValue(':s4', $searchVal);
                }
                if ($statusFilter !== '') $countStmt->bindValue(':status_val', $statusFilter);

                $countStmt->execute();
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT a.*, e.name_ar as employee_name, e.emp_code, d.name_ar as dept_name
                    FROM hr_appraisals a
                    LEFT JOIN hr_employees e ON a.employee_id = e.id
                    LEFT JOIN hr_departments d ON e.department_id = d.id
                    $whereSql
                    ORDER BY a.id DESC 
                    LIMIT :limit OFFSET :offset
                ");

                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $stmt->bindValue(':s1', $searchVal);
                    $stmt->bindValue(':s2', $searchVal);
                    $stmt->bindValue(':s3', $searchVal);
                    $stmt->bindValue(':s4', $searchVal);
                }
                if ($statusFilter !== '') $stmt->bindValue(':status_val', $statusFilter);
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

                $stmt->execute();
                $appraisals = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_appraisals,
                        COALESCE(AVG(score), 0) as avg_score,
                        SUM(IF(status = 'approved', 1, 0)) as approved_count,
                        SUM(IF(status = 'draft', 1, 0)) as draft_count
                    FROM hr_appraisals
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                error_log("HR Appraisals Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/hr/appraisals/index.php', [
            'appraisals' => $appraisals,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'statusFilter' => $statusFilter
        ], $response);
    }
    public function create(Request $request, Response $response): Response
    {
        $employees = [];
        $autoCode = 'APR-' . date('Y') . '-' . str_pad((string)(($this->getAppraisalCount()) + 1), 3, '0', STR_PAD_LEFT);

        if ($this->db) {
            try {
                $employees = $this->db->query("SELECT id, emp_code, name_ar FROM hr_employees WHERE status = 'active' ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/hr/appraisals/create.php', [
            'appraisal' => null,
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

            if (empty($data['appraisal_code']) || empty($data['employee_id']) || empty($data['appraisal_date'])) {
                throw new Exception("يرجى تعبئة الحقول الأساسية للتقييم.");
            }

            $score = !empty($data['score']) ? (float)$data['score'] : 0.00;
            
            // تحديد الدرجة التلقائية بناءً على النسبة
            $ratingGrade = 'Good';
            if ($score >= 90) $ratingGrade = 'Excellent';
            elseif ($score >= 75) $ratingGrade = 'Very Good';
            elseif ($score >= 60) $ratingGrade = 'Good';
            elseif ($score >= 50) $ratingGrade = 'Acceptable';
            else $ratingGrade = 'Needs Improvement';

            $stmt = $this->db->prepare("
                INSERT INTO hr_appraisals (appraisal_code, employee_id, evaluator_name, appraisal_period, appraisal_date, score, rating_grade, status, remarks, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($data['appraisal_code']),
                (int)$data['employee_id'],
                trim($data['evaluator_name'] ?? ''),
                trim($data['appraisal_period'] ?? 'Annual ' . date('Y')),
                $data['appraisal_date'],
                $score,
                $ratingGrade,
                $data['status'] ?? 'draft',
                trim($data['remarks'] ?? ''),
                $_SESSION['user_id'] ?? 1
            ]);

            $_SESSION['flash_msg'] = "تم إنشاء تقييم الأداء بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/appraisals/create');
        }

        return new RedirectResponse('/ERP/hr/appraisals');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $appraisal = null;
        $employees = [];

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("SELECT * FROM hr_appraisals WHERE id = ?");
            $stmt->execute([$id]);
            $appraisal = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$appraisal) throw new Exception("بيانات تقييم الأداء غير موجودة.");

            $employees = $this->db->query("SELECT id, emp_code, name_ar FROM hr_employees ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/appraisals');
        }

        return $this->renderView('/resources/views/hr/appraisals/create.php', [
            'appraisal' => $appraisal,
            'employees' => $employees,
            'autoCode' => $appraisal->appraisal_code
        ], $response);
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $score = !empty($data['score']) ? (float)$data['score'] : 0.00;
            
            $ratingGrade = 'Good';
            if ($score >= 90) $ratingGrade = 'Excellent';
            elseif ($score >= 75) $ratingGrade = 'Very Good';
            elseif ($score >= 60) $ratingGrade = 'Good';
            elseif ($score >= 50) $ratingGrade = 'Acceptable';
            else $ratingGrade = 'Needs Improvement';

            $stmt = $this->db->prepare("
                UPDATE hr_appraisals 
                SET employee_id = ?, evaluator_name = ?, appraisal_period = ?, appraisal_date = ?, score = ?, rating_grade = ?, status = ?, remarks = ?
                WHERE id = ?
            ");
            $stmt->execute([
                (int)$data['employee_id'],
                trim($data['evaluator_name'] ?? ''),
                trim($data['appraisal_period'] ?? ''),
                $data['appraisal_date'],
                $score,
                $ratingGrade,
                $data['status'] ?? 'draft',
                trim($data['remarks'] ?? ''),
                $id
            ]);

            $_SESSION['flash_msg'] = "تم تحديث تقييم الأداء بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/hr/appraisals/{$id}/edit");
        }

        return new RedirectResponse('/ERP/hr/appraisals');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM hr_appraisals WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف التقييم بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/hr/appraisals');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $appraisal = null;

        if ($this->db) {
            $stmt = $this->db->prepare("
                SELECT a.*, e.name_ar as employee_name, e.emp_code, d.name_ar as dept_name, dg.title_ar as desig_name
                FROM hr_appraisals a
                LEFT JOIN hr_employees e ON a.employee_id = e.id
                LEFT JOIN hr_departments d ON e.department_id = d.id
                LEFT JOIN hr_designations dg ON e.designation_id = dg.id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $appraisal = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$appraisal) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "سجل تقييم الأداء غير موجود.";
            return new RedirectResponse('/ERP/hr/appraisals');
        }

        return $this->renderView('/resources/views/hr/appraisals/show.php', [
            'appraisal' => $appraisal
        ], $response);
    }

    private function getAppraisalCount(): int
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM hr_appraisals")->fetchColumn();
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