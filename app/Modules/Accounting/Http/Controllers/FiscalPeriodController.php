<?php
// Path: app/Modules/Accounting/Http/Controllers/FiscalPeriodController.php

namespace App\Modules\Accounting\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use Core\Security\Auth;
use PDO;
use Exception;
use Throwable;

class FiscalPeriodController extends Controller
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
        if (preg_match('#/fiscal-periods/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    private function hasBranchesSupport(): bool 
    {
        if (!$this->db) return false;
        try {
            $this->db->query("SELECT branch_id FROM fiscal_periods LIMIT 1");
            return true;
        } catch (Throwable $e) { return false; }
    }

    private function getTenantCondition(string $alias = ''): string 
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $branchId  = (int)($_SESSION['branch_id'] ?? 0);
        
        $prefix = $alias ? $alias . '.' : '';
        $cond = "({$prefix}company_id = {$companyId} OR {$prefix}company_id IS NULL OR {$prefix}company_id = 0)";
        
        if ($this->hasBranchesSupport() && $branchId > 0) {
            $cond .= " AND ({$prefix}branch_id = {$branchId} OR {$prefix}branch_id IS NULL OR {$prefix}branch_id = 0)";
        }
        return $cond;
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
                Auth::enforce('accounting_periods_view');
            }

            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (preg_match('#/fiscal-periods/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
            if (preg_match('#/fiscal-periods/(\d+)/close#', $uri, $m)) return $this->closePeriod($request, $response, (int)$m[1]);
            if (preg_match('#/fiscal-periods/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
            if (preg_match('#/fiscal-periods/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

            $search = trim($_GET['search'] ?? '');
            $statusFilter = trim($_GET['status'] ?? '');
            $branchFilter = trim($_GET['branch_id'] ?? '');
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = 15;
            $offset = ($page - 1) * $limit;

            $tenantCond = $this->getTenantCondition('fp');
            $hasBranch = $this->hasBranchesSupport();
            
            $periods = []; $branches = [];
            $stats = (object)['total'=>0, 'open'=>0, 'closed'=>0];
            $totalPages = 1;

            if ($this->db) {
                $companyId = (int)($_SESSION['company_id'] ?? 1);
                if ($hasBranch) {
                    $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
                }

                $where = [$tenantCond];
                $params = [];

                if ($search !== '') {
                    $where[] = "(fp.name_ar LIKE ? OR fp.name_en LIKE ?)";
                    $like = "%{$search}%";
                    $params = array_merge($params, [$like, $like]);
                }
                if ($statusFilter !== '') {
                    $where[] = "fp.status = ?";
                    $params[] = $statusFilter;
                }
                if ($hasBranch && $branchFilter !== '') {
                    $where[] = "fp.branch_id = ?";
                    $params[] = (int)$branchFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM fiscal_periods fp $whereSql");
                $countStmt->execute($params);
                $totalPages = max(1, ceil($countStmt->fetchColumn() / $limit));

                $branchSelect = $hasBranch ? ", b.name_ar as branch_name, b.name_en as branch_name_en" : "";
                $branchJoin   = $hasBranch ? "LEFT JOIN branches b ON fp.branch_id = b.id" : "";

                $stmt = $this->db->prepare("
                    SELECT fp.* $branchSelect
                    FROM fiscal_periods fp
                    $branchJoin
                    $whereSql
                    ORDER BY fp.start_date DESC, fp.id DESC LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $periods = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total,
                        SUM(IF(status='open', 1, 0)) as open_periods,
                        SUM(IF(status='closed', 1, 0)) as closed_periods
                    FROM fiscal_periods fp WHERE $tenantCond
                ")->fetch(PDO::FETCH_OBJ);

                if ($statsData) {
                    $stats->total = $statsData->total;
                    $stats->open = $statsData->open_periods;
                    $stats->closed = $statsData->closed_periods;
                }
            }

            $currentPage = $page;
            
            ob_start(); include $this->basePath . '/resources/views/accounting/fiscal_periods/index.php';
            $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');

        } catch (Throwable $e) {
            die("<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 Controller Error (index)</h3>" . $e->getMessage() . "<br>Line: " . $e->getLine() . "</div>");
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_periods_create');

            if (session_status() === PHP_SESSION_NONE) session_start();
            $companyId = (int)($_SESSION['company_id'] ?? 1);
            $branches = []; $period = null;
            $hasBranch = $this->hasBranchesSupport();

            if ($this->db && $hasBranch) {
                $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
            }

            ob_start(); include $this->basePath . '/resources/views/accounting/fiscal_periods/create.php';
            $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
        } catch (Throwable $e) {
            die("<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 Controller Error (create)</h3>" . $e->getMessage() . "</div>");
        }
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_periods_edit');

            $id = $this->resolveId($id);
            if (session_status() === PHP_SESSION_NONE) session_start();
            $companyId = (int)($_SESSION['company_id'] ?? 1);
            $branches = [];
            $hasBranch = $this->hasBranchesSupport();

            if (!$this->db) throw new Exception("Database error.");

            $stmt = $this->db->prepare("SELECT * FROM fiscal_periods WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $period = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$period) {
                $_SESSION['flash_err'] = 'الفترة المالية غير موجودة.';
                return new RedirectResponse('/ERP/accounting/fiscal-periods');
            }

            if ($hasBranch) {
                $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
            }

            ob_start(); include $this->basePath . '/resources/views/accounting/fiscal_periods/create.php';
            $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
        } catch (Throwable $e) {
            die("<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 Controller Error (edit)</h3>" . $e->getMessage() . "</div>");
        }
    }

    public function store(Request $request, Response $response): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_periods_create');

            $data = $_POST;
            if (session_status() === PHP_SESSION_NONE) session_start();
            $companyId = (int)($_SESSION['company_id'] ?? 1);
            $sessionBranch = (int)($_SESSION['branch_id'] ?? 0);
            $branchId = ($sessionBranch > 0) ? $sessionBranch : (!empty($data['branch_id']) ? (int)$data['branch_id'] : 0);

            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $periodId = !empty($data['id']) ? (int)$data['id'] : null;
            $nameAr = trim($data['name_ar'] ?? '');
            $nameEn = trim($data['name_en'] ?? '');
            $startDate = $data['start_date'] ?? date('Y-m-d');
            $endDate = $data['end_date'] ?? date('Y-m-d');
            $notes = trim($data['notes'] ?? '');
            $status = $data['status'] ?? 'open';

            if ($startDate > $endDate) throw new Exception('تاريخ البداية يجب أن يكون قبل أو يساوي تاريخ النهاية.');

            if ($periodId) {
                $stmt = $this->db->prepare("UPDATE fiscal_periods SET branch_id=?, name_ar=?, name_en=?, start_date=?, end_date=?, status=?, notes=? WHERE id=? AND company_id=?");
                $stmt->execute([$branchId, $nameAr, $nameEn, $startDate, $endDate, $status, $notes, $periodId, $companyId]);
                $_SESSION['flash_msg'] = "تم تحديث الفترة المالية بنجاح.";
            } else {
                $stmt = $this->db->prepare("INSERT INTO fiscal_periods (company_id, branch_id, name_ar, name_en, start_date, end_date, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$companyId, $branchId, $nameAr, $nameEn, $startDate, $endDate, $status, $notes]);
                $_SESSION['flash_msg'] = "تم إنشاء الفترة المالية بنجاح.";
            }

            return new RedirectResponse("/ERP/accounting/fiscal-periods");

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/fiscal-periods/create');
        }
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_periods_view');

            $id = $this->resolveId($id);
            if (session_status() === PHP_SESSION_NONE) session_start();
            $companyId = (int)($_SESSION['company_id'] ?? 1);
            $hasBranch = $this->hasBranchesSupport();
            
            $convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

            if (!$this->db) throw new Exception("اتصال قاعدة البيانات مفقود.");

            $branchSelect = $hasBranch ? ", b.name_ar as branch_name, b.name_en as branch_name_en" : "";
            $branchJoin   = $hasBranch ? "LEFT JOIN branches b ON fp.branch_id = b.id" : "";

            $stmt = $this->db->prepare("
                SELECT fp.* $branchSelect
                FROM fiscal_periods fp 
                $branchJoin
                WHERE fp.id = ? AND fp.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $period = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$period) {
                $_SESSION['flash_err'] = 'الفترة المالية غير موجودة.';
                return new RedirectResponse('/ERP/accounting/fiscal-periods');
            }

            // إحصائيات القيود خلال هذه الفترة
            $txStmt = $this->db->prepare("
                SELECT COUNT(id) as total_entries, SUM(total_amount) as total_volume
                FROM journal_entries
                WHERE company_id = ? AND entry_date BETWEEN ? AND ? AND status = 'posted'
            ");
            $txStmt->execute([$companyId, $period->start_date, $period->end_date]);
            $stats = $txStmt->fetch(PDO::FETCH_OBJ);
            
            $stats->total_volume = $convert($stats->total_volume ?? 0);

            // جلب أحدث 10 قيود كعينة للمراجعة
            $entriesStmt = $this->db->prepare("
                SELECT id, entry_number, entry_date, description, total_amount
                FROM journal_entries
                WHERE company_id = ? AND entry_date BETWEEN ? AND ? AND status = 'posted'
                ORDER BY entry_date DESC, id DESC LIMIT 10
            ");
            $entriesStmt->execute([$companyId, $period->start_date, $period->end_date]);
            $latestEntries = $entriesStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            foreach ($latestEntries as $je) {
                $je->total_amount = $convert($je->total_amount);
            }

            ob_start(); include $this->basePath . '/resources/views/accounting/fiscal_periods/show.php';
            $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');

        } catch (Throwable $e) {
            die("<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 Controller Error (show)</h3>" . $e->getMessage() . "</div>");
        }
    }

    public function closePeriod(Request $request, Response $response, $id = null): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_periods_edit');

            $id = $this->resolveId($id);
            if (session_status() === PHP_SESSION_NONE) session_start();
            $companyId = (int)($_SESSION['company_id'] ?? 1);

            if (!$this->db) throw new Exception("Database error.");

            $this->db->prepare("UPDATE fiscal_periods SET status = 'closed' WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = 'تم إغلاق الفترة المالية بنجاح. لا يمكن إضافة قيود جديدة في هذا النطاق.';
            
            return new RedirectResponse("/ERP/accounting/fiscal-periods/{$id}");

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/accounting/fiscal-periods/{$id}");
        }
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_periods_delete');

            $id = $this->resolveId($id);
            if (session_status() === PHP_SESSION_NONE) session_start();
            $companyId = (int)($_SESSION['company_id'] ?? 1);

            $rec = $this->db->query("SELECT status FROM fiscal_periods WHERE id = $id AND company_id = $companyId")->fetch(PDO::FETCH_OBJ);
            if ($rec && $rec->status === 'closed') {
                throw new Exception('لا يمكن حذف فترة مالية مغلقة.');
            }

            $this->db->prepare("DELETE FROM fiscal_periods WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = 'تم حذف الفترة المالية بنجاح.';
            return new RedirectResponse('/ERP/accounting/fiscal-periods');

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/fiscal-periods');
        }
    }
}