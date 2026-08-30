<?php
// Path: app/Modules/HR/Http/Controllers/RecruitmentController.php

namespace App\Modules\HR\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class RecruitmentController extends Controller
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
        if (preg_match('#/hr/recruitment/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

   public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/hr/recruitment/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/hr/recruitment/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/hr/recruitment/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/hr/recruitment/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $applicants = [];
        $stats = (object)[
            'total_applicants' => 0,
            'interviewed_count' => 0,
            'offered_count' => 0,
            'hired_count' => 0
        ];
        $totalPages = 1;

        if ($this->db) {
            try {
                $where = ["1=1"];

                if ($search !== '') {
                    $where[] = "(r.applicant_code LIKE :s1 OR r.candidate_name LIKE :s2 OR r.email LIKE :s3 OR r.phone LIKE :s4)";
                }

                if ($statusFilter !== '') {
                    $where[] = "r.status = :status_val";
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM hr_recruitment r $whereSql");
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
                    SELECT r.*, d.name_ar as dept_name, dg.title_ar as desig_name
                    FROM hr_recruitment r
                    LEFT JOIN hr_departments d ON r.department_id = d.id
                    LEFT JOIN hr_designations dg ON r.designation_id = dg.id
                    $whereSql
                    ORDER BY r.id DESC 
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
                $applicants = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_applicants,
                        SUM(IF(status = 'interviewed', 1, 0)) as interviewed_count,
                        SUM(IF(status = 'offered', 1, 0)) as offered_count,
                        SUM(IF(status = 'hired', 1, 0)) as hired_count
                    FROM hr_recruitment
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                error_log("HR Recruitment Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/hr/recruitment/index.php', [
            'applicants' => $applicants,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'statusFilter' => $statusFilter
        ], $response);
    }
    
    public function create(Request $request, Response $response): Response
    {
        $departments = [];
        $designations = [];
        $autoCode = 'CAN-' . date('Y') . '-' . str_pad((string)(($this->getApplicantCount()) + 1), 3, '0', STR_PAD_LEFT);

        if ($this->db) {
            try {
                $departments = $this->db->query("SELECT id, code, name_ar FROM hr_departments WHERE status = 'active' ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                $designations = $this->db->query("SELECT id, code, title_ar FROM hr_designations WHERE status = 'active' ORDER BY title_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/hr/recruitment/create.php', [
            'applicant' => null,
            'departments' => $departments,
            'designations' => $designations,
            'autoCode' => $autoCode
        ], $response);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            if (empty($data['applicant_code']) || empty($data['candidate_name'])) {
                throw new Exception("يرجى تعبئة الحقول الأساسية للمتقدم.");
            }

            $stmt = $this->db->prepare("
                INSERT INTO hr_recruitment 
                (applicant_code, candidate_name, email, phone, department_id, designation_id, experience_years, expected_salary, status, interview_date, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($data['applicant_code']),
                trim($data['candidate_name']),
                trim($data['email'] ?? ''),
                trim($data['phone'] ?? ''),
                !empty($data['department_id']) ? (int)$data['department_id'] : null,
                !empty($data['designation_id']) ? (int)$data['designation_id'] : null,
                !empty($data['experience_years']) ? (int)$data['experience_years'] : 0,
                !empty($data['expected_salary']) ? (float)$data['expected_salary'] : 0.00,
                $data['status'] ?? 'applied',
                !empty($data['interview_date']) ? $data['interview_date'] : null,
                trim($data['notes'] ?? ''),
                $_SESSION['user_id'] ?? 1
            ]);

            $_SESSION['flash_msg'] = "تم تسجيل طلب التوظيف للمتقدم بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/recruitment/create');
        }

        return new RedirectResponse('/ERP/hr/recruitment');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $applicant = null;
        $departments = [];
        $designations = [];

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("SELECT * FROM hr_recruitment WHERE id = ?");
            $stmt->execute([$id]);
            $applicant = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$applicant) throw new Exception("بيانات المتقدم غير موجودة.");

            $departments = $this->db->query("SELECT id, code, name_ar FROM hr_departments WHERE status = 'active' ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $designations = $this->db->query("SELECT id, code, title_ar FROM hr_designations WHERE status = 'active' ORDER BY title_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/recruitment');
        }

        return $this->renderView('/resources/views/hr/recruitment/create.php', [
            'applicant' => $applicant,
            'departments' => $departments,
            'designations' => $designations,
            'autoCode' => $applicant->applicant_code
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
                UPDATE hr_recruitment 
                SET candidate_name = ?, email = ?, phone = ?, department_id = ?, designation_id = ?, experience_years = ?, expected_salary = ?, status = ?, interview_date = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($data['candidate_name']),
                trim($data['email'] ?? ''),
                trim($data['phone'] ?? ''),
                !empty($data['department_id']) ? (int)$data['department_id'] : null,
                !empty($data['designation_id']) ? (int)$data['designation_id'] : null,
                !empty($data['experience_years']) ? (int)$data['experience_years'] : 0,
                !empty($data['expected_salary']) ? (float)$data['expected_salary'] : 0.00,
                $data['status'] ?? 'applied',
                !empty($data['interview_date']) ? $data['interview_date'] : null,
                trim($data['notes'] ?? ''),
                $id
            ]);

            $_SESSION['flash_msg'] = "تم تحديث بيانات المتقدم بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/hr/recruitment/{$id}/edit");
        }

        return new RedirectResponse('/ERP/hr/recruitment');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM hr_recruitment WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف طلب المتقدم بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/hr/recruitment');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $applicant = null;

        if ($this->db) {
            $stmt = $this->db->prepare("
                SELECT r.*, d.name_ar as dept_name, dg.title_ar as desig_name
                FROM hr_recruitment r
                LEFT JOIN hr_departments d ON r.department_id = d.id
                LEFT JOIN hr_designations dg ON r.designation_id = dg.id
                WHERE r.id = ?
            ");
            $stmt->execute([$id]);
            $applicant = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$applicant) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "طلب التوظيف غير موجود.";
            return new RedirectResponse('/ERP/hr/recruitment');
        }

        return $this->renderView('/resources/views/hr/recruitment/show.php', [
            'applicant' => $applicant
        ], $response);
    }

    private function getApplicantCount(): int
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM hr_recruitment")->fetchColumn();
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