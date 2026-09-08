<?php
// Path: app/Modules/Accounting/Http/Controllers/CostCenterController.php

namespace App\Modules\Accounting\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use Core\Security\Auth;
use PDO;
use Exception;
use Throwable;

class CostCenterController extends Controller
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
        if (preg_match('#/cost-centers/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    private function hasBranchesSupport(): bool 
    {
        if (!$this->db) return false;
        try {
            $this->db->query("SELECT branch_id FROM cost_centers LIMIT 1");
            $this->db->query("SELECT id FROM branches LIMIT 1");
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function getTenantCondition(string $alias = '', string $table = 'cost_centers'): string 
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
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_cost_centers_view');
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/cost-centers/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/cost-centers/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/cost-centers/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/cost-centers/(\d+)/report#', $uri, $m)) return $this->report($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $branchFilter = trim($_GET['branch_id'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $tenantCond = $this->getTenantCondition('c', 'cost_centers');
        $hasBranch = $this->hasBranchesSupport();
        
        $convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

        $centers = []; $branches = []; $dbErrors = [];
        $stats = (object)['total_centers'=>0, 'parent_centers'=>0, 'sub_centers'=>0, 'active_centers'=>0];
        $totalPages = 1;

        if ($this->db) {
            try {
                $companyId = (int)($_SESSION['company_id'] ?? 1);
                
                if ($hasBranch) {
                    $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
                }

                $where = [$tenantCond];
                $params = [];

                if ($search !== '') {
                    $where[] = "(c.code LIKE ? OR c.name_ar LIKE ? OR c.name_en LIKE ?)";
                    $like = "%{$search}%";
                    $params = array_merge($params, [$like, $like, $like]);
                }
                if ($statusFilter !== '') {
                    $where[] = "c.is_active = ?";
                    $params[] = ($statusFilter === 'active') ? 1 : 0;
                }
                if ($hasBranch && $branchFilter !== '') {
                    $where[] = "c.branch_id = ?";
                    $params[] = (int)$branchFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM cost_centers c $whereSql");
                $countStmt->execute($params);
                $totalPages = max(1, ceil($countStmt->fetchColumn() / $limit));

                $branchSelect = $hasBranch ? ", b.name_ar as branch_name, b.name_en as branch_name_en" : "";
                $branchJoin   = $hasBranch ? "LEFT JOIN branches b ON c.branch_id = b.id" : "";

                $stmt = $this->db->prepare("
                    SELECT c.*, p.name_ar as parent_name, p.code as parent_code $branchSelect
                    FROM cost_centers c
                    LEFT JOIN cost_centers p ON c.parent_id = p.id
                    $branchJoin
                    $whereSql
                    ORDER BY c.code ASC LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $centers = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                foreach ($centers as $cen) {
                    $cen->budget_amount = $convert($cen->budget_amount);
                }

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_centers,
                        SUM(IF(is_parent = 1, 1, 0)) as parent_centers,
                        SUM(IF(is_parent = 0, 1, 0)) as sub_centers,
                        SUM(IF(is_active = 1, 1, 0)) as active_centers
                    FROM cost_centers c WHERE $tenantCond
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                $dbErrors[] = $e->getMessage();
            }
        }

        $currentPage = $page;
        return $this->renderView('/resources/views/accounting/cost_centers/index.php', compact(
            'centers', 'branches', 'stats', 'totalPages', 'currentPage', 'search', 'statusFilter', 'branchFilter', 'dbErrors'
        ), $response);
    }

    public function create(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_cost_centers_create');
        }

        $center = null; $parentCenters = []; $branches = [];
        $tenantCond = $this->getTenantCondition('c', 'cost_centers');
        $hasBranch = $this->hasBranchesSupport();

        if ($this->db) {
            try {
                $companyId = (int)($_SESSION['company_id'] ?? 1);
                if ($hasBranch) {
                    $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
                }
                $parentCenters = $this->db->query("
                    SELECT id, code, name_ar, name_en 
                    FROM cost_centers c 
                    WHERE is_parent = 1 AND $tenantCond 
                    ORDER BY code ASC
                ")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/accounting/cost_centers/create.php', compact('center', 'parentCenters', 'branches'), $response);
    }

    public function store(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_cost_centers_create');
        }

        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $sessionBranch = (int)($_SESSION['branch_id'] ?? 0);
        $branchId = ($sessionBranch > 0) ? $sessionBranch : (!empty($data['branch_id']) ? (int)$data['branch_id'] : 0);

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");
            if (empty($data['code']) || empty($data['name_ar'])) throw new Exception("يرجى تعبئة الحقول الإلزامية.");

            $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
            $budget = !empty($data['budget_amount']) ? (float)$data['budget_amount'] : 0.00;
            $isParent = isset($data['is_parent']) ? 1 : 0;
            $isActive = isset($data['is_active']) ? 1 : 0;

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO cost_centers (company_id, branch_id, code, name_ar, name_en, budget_amount, parent_id, is_parent, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $branchId, trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''), 
                    $budget, $parentId, $isParent, $isActive
                ]);
            } catch (\PDOException $ex) {
                // Fallback for missing branch_id
                $stmt = $this->db->prepare("
                    INSERT INTO cost_centers (company_id, code, name_ar, name_en, budget_amount, parent_id, is_parent, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''), 
                    $budget, $parentId, $isParent, $isActive
                ]);
            }

            $_SESSION['flash_msg'] = "تم إنشاء مركز التكلفة بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/cost-centers/create');
        }
        return new RedirectResponse('/ERP/accounting/cost-centers');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_cost_centers_edit');
        }

        $id = $this->resolveId($id);
        $tenantCond = $this->getTenantCondition('c', 'cost_centers');
        $hasBranch = $this->hasBranchesSupport();
        
        $center = null; $parentCenters = []; $branches = [];

        try {
            if (!$id || !$this->db) throw new Exception("معرف المركز غير صالح.");

            $companyId = (int)($_SESSION['company_id'] ?? 1);
            if ($hasBranch) {
                $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
            }

            $stmt = $this->db->prepare("SELECT * FROM cost_centers WHERE id = ?");
            $stmt->execute([$id]);
            $center = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$center) throw new Exception("مركز التكلفة غير موجود.");

            $parentCenters = $this->db->query("
                SELECT id, code, name_ar, name_en 
                FROM cost_centers c 
                WHERE is_parent = 1 AND id != {$id} AND $tenantCond 
                ORDER BY code ASC
            ")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/cost-centers');
        }

        return $this->renderView('/resources/views/accounting/cost_centers/create.php', compact('center', 'parentCenters', 'branches'), $response);
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_cost_centers_edit');
        }

        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $sessionBranch = (int)($_SESSION['branch_id'] ?? 0);
        $branchId = ($sessionBranch > 0) ? $sessionBranch : (!empty($data['branch_id']) ? (int)$data['branch_id'] : 0);

        try {
            $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
            $budget = !empty($data['budget_amount']) ? (float)$data['budget_amount'] : 0.00;
            $isParent = isset($data['is_parent']) ? 1 : 0;
            $isActive = isset($data['is_active']) ? 1 : 0;

            try {
                $stmt = $this->db->prepare("
                    UPDATE cost_centers 
                    SET branch_id = ?, code = ?, name_ar = ?, name_en = ?, budget_amount = ?, parent_id = ?, is_parent = ?, is_active = ?
                    WHERE id = ? AND company_id = ?
                ");
                $stmt->execute([
                    $branchId, trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''), $budget,
                    $parentId, $isParent, $isActive, $id, $companyId
                ]);
            } catch (\PDOException $ex) {
                $stmt = $this->db->prepare("
                    UPDATE cost_centers 
                    SET code = ?, name_ar = ?, name_en = ?, budget_amount = ?, parent_id = ?, is_parent = ?, is_active = ?
                    WHERE id = ? AND company_id = ?
                ");
                $stmt->execute([
                    trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''), $budget,
                    $parentId, $isParent, $isActive, $id, $companyId
                ]);
            }

            $_SESSION['flash_msg'] = "تم تحديث بيانات مركز التكلفة بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/accounting/cost-centers/{$id}/edit");
        }
        return new RedirectResponse('/ERP/accounting/cost-centers');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_cost_centers_delete');
        }

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = (int)($_SESSION['company_id'] ?? 1);

        try {
            $childCheck = $this->db->prepare("SELECT COUNT(*) FROM cost_centers WHERE parent_id = ? AND company_id = ?");
            $childCheck->execute([$id, $companyId]);
            if ($childCheck->fetchColumn() > 0) {
                throw new Exception("لا يمكن حذف مركز تكلفة رئيسي تتبع له مراكز فرعية.");
            }

            $entriesCheck = $this->db->prepare("SELECT COUNT(*) FROM journal_entry_items WHERE cost_center_id = ?");
            $entriesCheck->execute([$id]);
            if ($entriesCheck->fetchColumn() > 0) {
                throw new Exception("لا يمكن حذف المركز لوجود قيود مالية وحركات مسجلة عليه.");
            }

            $this->db->prepare("DELETE FROM cost_centers WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف مركز التكلفة بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse('/ERP/accounting/cost-centers');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_cost_centers_view');
        }

        $id = $this->resolveId($id);
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $hasBranch = $this->hasBranchesSupport();
        
        $convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

        try {
            $branchSelect = $hasBranch ? ", b.name_ar as branch_name, b.name_en as branch_name_en" : "";
            $branchJoin   = $hasBranch ? "LEFT JOIN branches b ON c.branch_id = b.id" : "";

            $stmt = $this->db->prepare("
                SELECT c.*, p.name_ar as parent_name, p.code as parent_code $branchSelect
                FROM cost_centers c
                LEFT JOIN cost_centers p ON c.parent_id = p.id
                $branchJoin
                WHERE c.id = ? AND c.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $center = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$center) throw new Exception("مركز التكلفة غير موجود.");

            $center->budget_amount = $convert($center->budget_amount);

            $subCenters = $this->db->query("SELECT * FROM cost_centers WHERE parent_id = {$id} ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/cost-centers');
        }

        return $this->renderView('/resources/views/accounting/cost_centers/show.php', compact('center', 'subCenters'), $response);
    }

    public function report(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_cost_centers_report');
        }

        $id = $this->resolveId($id);
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

        try {
            $stmt = $this->db->prepare("SELECT * FROM cost_centers WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $center = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$center) throw new Exception("مركز التكلفة غير موجود.");

            $pnlStmt = $this->db->prepare("
                SELECT 
                    a.type,
                    COALESCE(SUM(ji.debit), 0) as total_debit,
                    COALESCE(SUM(ji.credit), 0) as total_credit
                FROM journal_entry_items ji
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                JOIN accounts a ON ji.account_id = a.id
                WHERE ji.cost_center_id = ? AND je.status = 'posted' AND je.company_id = ?
                GROUP BY a.type
            ");
            $pnlStmt->execute([$id, $companyId]);
            $pnlData = $pnlStmt->fetchAll(PDO::FETCH_OBJ);

            $totalRevenues = 0; $totalExpenses = 0;

            foreach ($pnlData as $row) {
                if ($row->type === 'revenue') {
                    $totalRevenues += ($row->total_credit - $row->total_debit);
                } elseif ($row->type === 'expense') {
                    $totalExpenses += ($row->total_debit - $row->total_credit);
                }
            }

            $netProfit = $totalRevenues - $totalExpenses;

            $accStmt = $this->db->prepare("
                SELECT 
                    a.code, a.name_ar, a.name_en, a.type,
                    SUM(ji.debit) as total_debit,
                    SUM(ji.credit) as total_credit
                FROM journal_entry_items ji
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                JOIN accounts a ON ji.account_id = a.id
                WHERE ji.cost_center_id = ? AND je.status = 'posted' AND je.company_id = ?
                GROUP BY a.id, a.code, a.name_ar, a.name_en, a.type
                ORDER BY a.code ASC
            ");
            $accStmt->execute([$id, $companyId]);
            $accountBreakdown = $accStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $txStmt = $this->db->prepare("
                SELECT ji.*, je.entry_number, je.entry_date, je.description as entry_desc, a.name_ar as account_name, a.name_en as account_name_en
                FROM journal_entry_items ji
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                JOIN accounts a ON ji.account_id = a.id
                WHERE ji.cost_center_id = ? AND je.status = 'posted' AND je.company_id = ?
                ORDER BY je.entry_date DESC LIMIT 30
            ");
            $txStmt->execute([$id, $companyId]);
            $transactions = $txStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            // Apply conversions
            $totalRevenues = $convert($totalRevenues);
            $totalExpenses = $convert($totalExpenses);
            $netProfit = $convert($netProfit);
            $center->budget_amount = $convert($center->budget_amount);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/cost-centers');
        }

        return $this->renderView('/resources/views/accounting/cost_centers/report.php', compact(
            'center', 'totalRevenues', 'totalExpenses', 'netProfit', 'accountBreakdown', 'transactions'
        ), $response);
    }

    private function renderView(string $viewPath, array $data, Response $response): Response
    {
        extract($data);
        $fullPath = $this->basePath . $viewPath;

        if (!file_exists($fullPath)) {
            die("<div style='padding:30px; background:#fef2f2; color:#dc2626; font-family:monospace;'><h3>View File Missing:</h3>" . htmlspecialchars($fullPath) . "</div>");
        }

        try {
            ob_start(); include $fullPath; $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php'; $finalHtml = ob_get_clean();
            return $response->setContent($finalHtml)->setHeader('Content-Type', 'text/html; charset=UTF-8');
        } catch (Throwable $e) {
            ob_end_clean();
            die("<div style='padding:30px; background:#fef2f2; color:#dc2626; font-family:monospace;'><h3>Render Error:</h3>" . htmlspecialchars($e->getMessage()) . "</div>");
        }
    }
}