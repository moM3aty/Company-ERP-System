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
        if (preg_match('#/hr/shifts/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

   public function index(Request $request, Response $response): Response
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
                $where = ["1=1"];

                if ($search !== '') {
                    $where[] = "(code LIKE :s1 OR name_ar LIKE :s2)";
                }

                if ($statusFilter !== '') {
                    $where[] = "status = :status_val";
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM hr_shifts $whereSql");
                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $countStmt->bindValue(':s1', $searchVal);
                    $countStmt->bindValue(':s2', $searchVal);
                }
                if ($statusFilter !== '') $countStmt->bindValue(':status_val', $statusFilter);
                
                $countStmt->execute();
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT * FROM hr_shifts
                    $whereSql
                    ORDER BY id DESC 
                    LIMIT :limit OFFSET :offset
                ");

                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $stmt->bindValue(':s1', $searchVal);
                    $stmt->bindValue(':s2', $searchVal);
                }
                if ($statusFilter !== '') $stmt->bindValue(':status_val', $statusFilter);
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

                $stmt->execute();
                $shifts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_shifts,
                        SUM(IF(status = 'active', 1, 0)) as active_shifts,
                        SUM(IF(status = 'inactive', 1, 0)) as inactive_shifts
                    FROM hr_shifts
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                error_log("HR Shifts Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/hr/shifts/index.php', [
            'shifts' => $shifts,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'statusFilter' => $statusFilter
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        $shift = null;
        $autoCode = 'SHF-' . str_pad((string)(($this->getShiftCount()) + 1), 3, '0', STR_PAD_LEFT);

        return $this->renderView('/resources/views/hr/shifts/create.php', [
            'shift' => $shift,
            'autoCode' => $autoCode
        ], $response);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            if (empty($data['code']) || empty($data['name_ar']) || empty($data['start_time']) || empty($data['end_time'])) {
                throw new Exception("يرجى تعبئة الحقول الأساسية لوردية العمل.");
            }

            $stmt = $this->db->prepare("
                INSERT INTO hr_shifts (code, name_ar, start_time, end_time, grace_period_mins, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($data['code']),
                trim($data['name_ar']),
                $data['start_time'],
                $data['end_time'],
                !empty($data['grace_period_mins']) ? (int)$data['grace_period_mins'] : 0,
                $data['status'] ?? 'active',
                $_SESSION['user_id'] ?? 1
            ]);

            $_SESSION['flash_msg'] = "تم إضافة وردية العمل بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/shifts/create');
        }

        return new RedirectResponse('/ERP/hr/shifts');
    }

    public function edit(Request $request, Response $response, $id = null): Response
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
            return new RedirectResponse('/ERP/hr/shifts');
        }

        return $this->renderView('/resources/views/hr/shifts/create.php', [
            'shift' => $shift,
            'autoCode' => $shift->code
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

            $_SESSION['flash_msg'] = "تم تحديث بيانات الوردية بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/hr/shifts/{$id}/edit");
        }

        return new RedirectResponse('/ERP/hr/shifts');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM hr_shifts WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف الوردية بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/hr/shifts');
    }

    private function getShiftCount(): int
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM hr_shifts")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
// أضف هذه الدالة داخل ShiftController
public function show(Request $request, Response $response, $id = null): Response
{
    $id = $this->resolveId($id);
    $shift = null;

    if ($this->db) {
        $stmt = $this->db->prepare("SELECT * FROM hr_shifts WHERE id = ?");
        $stmt->execute([$id]);
        $shift = $stmt->fetch(PDO::FETCH_OBJ);
    }

    if (!$shift) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash_err'] = "الوردية غير موجودة.";
        return new RedirectResponse('/ERP/hr/shifts');
    }

    return $this->renderView('/resources/views/hr/shifts/show.php', [
        'shift' => $shift
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