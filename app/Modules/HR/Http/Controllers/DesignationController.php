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
        if (preg_match('#/hr/designations/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

   public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/hr/designations/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/hr/designations/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/hr/designations/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/hr/designations/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $designations = [];
        $stats = (object)[
            'total_designations' => 0,
            'active_designations' => 0,
            'inactive_designations' => 0
        ];
        $totalPages = 1;

        if ($this->db) {
            try {
                $where = ["1=1"];

                if ($search !== '') {
                    $where[] = "(d.code LIKE :s1 OR d.title_ar LIKE :s2 OR dep.name_ar LIKE :s3)";
                }

                if ($statusFilter !== '') {
                    $where[] = "d.status = :status_val";
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM hr_designations d
                    LEFT JOIN hr_departments dep ON d.department_id = dep.id
                    $whereSql
                ");
                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $countStmt->bindValue(':s1', $searchVal);
                    $countStmt->bindValue(':s2', $searchVal);
                    $countStmt->bindValue(':s3', $searchVal);
                }
                if ($statusFilter !== '') $countStmt->bindValue(':status_val', $statusFilter);

                $countStmt->execute();
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT d.*, dep.name_ar as department_name_ar
                    FROM hr_designations d
                    LEFT JOIN hr_departments dep ON d.department_id = dep.id
                    $whereSql
                    ORDER BY d.id DESC 
                    LIMIT :limit OFFSET :offset
                ");

                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $stmt->bindValue(':s1', $searchVal);
                    $stmt->bindValue(':s2', $searchVal);
                    $stmt->bindValue(':s3', $searchVal);
                }
                if ($statusFilter !== '') $stmt->bindValue(':status_val', $statusFilter);
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

                $stmt->execute();
                $designations = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_designations,
                        SUM(IF(status = 'active', 1, 0)) as active_designations,
                        SUM(IF(status = 'inactive', 1, 0)) as inactive_designations
                    FROM hr_designations
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                error_log("HR Designations Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/hr/designations/index.php', [
            'designations' => $designations,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'statusFilter' => $statusFilter
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        $autoCode = 'DSG-' . str_pad((string)(($this->getDesignationCount()) + 1), 3, '0', STR_PAD_LEFT);

        return $this->renderView('/resources/views/hr/designations/create.php', [
            'designation' => null,
            'autoCode' => $autoCode
        ], $response);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            if (empty($data['code']) || empty($data['title_ar'])) {
                throw new Exception("يرجى تعبئة الحقول الأساسية للمسمى الوظيفي.");
            }

            $stmt = $this->db->prepare("
                INSERT INTO hr_designations (code, title_ar, status, created_by)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($data['code']),
                trim($data['title_ar']),
                $data['status'] ?? 'active',
                $_SESSION['user_id'] ?? 1
            ]);

            $_SESSION['flash_msg'] = "تم إضافة المسمى الوظيفي بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/designations/create');
        }

        return new RedirectResponse('/ERP/hr/designations');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $designation = null;

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("SELECT * FROM hr_designations WHERE id = ?");
            $stmt->execute([$id]);
            $designation = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$designation) throw new Exception("بيانات المسمى الوظيفي غير موجودة.");

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/designations');
        }

        return $this->renderView('/resources/views/hr/designations/create.php', [
            'designation' => $designation,
            'autoCode' => $designation->code
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
                UPDATE hr_designations 
                SET title_ar = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($data['title_ar']),
                $data['status'] ?? 'active',
                $id
            ]);

            $_SESSION['flash_msg'] = "تم تحديث المسمى الوظيفي بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/hr/designations/{$id}/edit");
        }

        return new RedirectResponse('/ERP/hr/designations');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM hr_designations WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف المسمى الوظيفي بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/hr/designations');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $designation = null;

        if ($this->db) {
            $stmt = $this->db->prepare("SELECT * FROM hr_designations WHERE id = ?");
            $stmt->execute([$id]);
            $designation = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$designation) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "المسمى الوظيفي غير موجود.";
            return new RedirectResponse('/ERP/hr/designations');
        }

        return $this->renderView('/resources/views/hr/designations/show.php', [
            'designation' => $designation
        ], $response);
    }

    private function getDesignationCount(): int
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM hr_designations")->fetchColumn();
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