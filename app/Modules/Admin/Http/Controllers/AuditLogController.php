<?php
// Path: app/Modules/Admin/Http/Controllers/AuditLogController.php

namespace App\Modules\Admin\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use Core\Security\Auth;
use PDO;
use Exception;
use Throwable;

class AuditLogController extends Controller
{
    private string $basePath;
    private ?PDO $db = null;

    public function __construct()
    {
        ini_set('display_errors', 0);
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
        if (preg_match('#/admin/logs/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('admin_audit_logs_view');

        $uri = $_SERVER['REQUEST_URI'] ?? '';

        if (preg_match('#/admin/logs/clear#', $uri)) return $this->clearOldLogs($request, $response);
        if (preg_match('#/admin/logs/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $moduleFilter = trim($_GET['module'] ?? '');
        $actionFilter = trim($_GET['action_type'] ?? '');
        $companyId = current_company() ?? 1;

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $logs = [];
        $totalPages = 1;
        $stats = (object)[
            'total' => 0,
            'today' => 0,
            'critical' => 0
        ];

        if ($this->db) {
            try {
                $todayDate = date('Y-m-d');
                
                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total,
                        SUM(IF(DATE(created_at) = '{$todayDate}', 1, 0)) as today,
                        SUM(IF(action LIKE '%DELETE%' OR action LIKE '%TRUNCATE%' OR action LIKE '%PURGE%', 1, 0)) as critical
                    FROM sys_audit_logs
                    WHERE company_id = {$companyId}
                ")->fetch(PDO::FETCH_OBJ);
                
                if ($statsData) $stats = $statsData;

                $where = ["company_id = :company_id"];

                if ($search !== '') {
                    $where[] = "(user_name LIKE :s1 OR action LIKE :s2 OR details LIKE :s3 OR ip_address LIKE :s4)";
                }
                if ($moduleFilter !== '') {
                    $where[] = "module = :mod_val";
                }
                if ($actionFilter !== '') {
                    $where[] = "action LIKE :act_val";
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM sys_audit_logs $whereSql");
                $countStmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);

                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $countStmt->bindValue(':s1', $searchVal);
                    $countStmt->bindValue(':s2', $searchVal);
                    $countStmt->bindValue(':s3', $searchVal);
                    $countStmt->bindValue(':s4', $searchVal);
                }
                if ($moduleFilter !== '') $countStmt->bindValue(':mod_val', $moduleFilter);
                if ($actionFilter !== '') $countStmt->bindValue(':act_val', "%{$actionFilter}%");

                $countStmt->execute();
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT * FROM sys_audit_logs
                    $whereSql
                    ORDER BY id DESC 
                    LIMIT :limit OFFSET :offset
                ");

                $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $stmt->bindValue(':s1', $searchVal);
                    $stmt->bindValue(':s2', $searchVal);
                    $stmt->bindValue(':s3', $searchVal);
                    $stmt->bindValue(':s4', $searchVal);
                }
                if ($moduleFilter !== '') $stmt->bindValue(':mod_val', $moduleFilter);
                if ($actionFilter !== '') $stmt->bindValue(':act_val', "%{$actionFilter}%");
                
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

                $stmt->execute();
                $logs = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            } catch (Throwable $e) {
                error_log("Audit Log Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/admin/logs/index.php', [
            'logs' => $logs,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'moduleFilter' => $moduleFilter,
            'actionFilter' => $actionFilter
        ], $response);
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('admin_audit_logs_view');

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        $log = null;

        try {
            if ($this->db) {
                $stmt = $this->db->prepare("SELECT * FROM sys_audit_logs WHERE id = ? AND company_id = ?");
                $stmt->execute([$id, $companyId]);
                $log = $stmt->fetch(PDO::FETCH_OBJ);
            }
        } catch (Throwable $e) {}

        if (!$log) {
            $_SESSION['flash_err'] = __('سجل الحركة المطلوب غير موجود أو تم مسحه.', 'The requested log entry is not found or has been purged.');
            return new RedirectResponse('/ERP/admin/logs');
        }

        return $this->renderView('/resources/views/admin/logs/show.php', [
            'log' => $log
        ], $response);
    }

    public function clearOldLogs(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('admin_audit_logs_delete');
        if (session_status() === PHP_SESSION_NONE) session_start();

        $companyId = current_company() ?? 1;

        try {
            if ($this->db) {
                $stmt = $this->db->prepare("DELETE FROM sys_audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY) AND company_id = ?");
                $stmt->execute([$companyId]);
                $deletedCount = $stmt->rowCount();

                $_SESSION['flash_msg'] = __('تم أرشفة وتنظيف عدد', 'Archived and purged ') . " ({$deletedCount}) " . __('سجل قديم يتجاوز 90 يوماً.', 'old logs exceeding 90 days.');
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = __('خطأ أثناء تنظيف السجلات: ', 'Error purging logs: ') . $e->getMessage();
        }

        return new RedirectResponse('/ERP/admin/logs');
    }

    private function renderView(string $viewPath, array $data, Response $response): Response
    {
        extract($data);
        $fullPath = $this->basePath . $viewPath;

        if (!file_exists($fullPath)) {
            die("View File Missing: " . htmlspecialchars($fullPath));
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
            die("View Render Error: " . htmlspecialchars($e->getMessage()));
        }
    }
}