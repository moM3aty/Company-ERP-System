<?php
// Path: app/Modules/Accounting/Http/Controllers/BudgetController.php

namespace App\Modules\Accounting\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use Core\Security\Auth;
use PDO;
use Exception;
use Throwable;

class BudgetController extends Controller
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
        if (preg_match('#/budgets/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    private function hasBranchesSupport(): bool 
    {
        if (!$this->db) return false;
        try {
            $this->db->query("SELECT branch_id FROM budgets LIMIT 1");
            $this->db->query("SELECT id FROM branches LIMIT 1");
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function getTenantCondition(string $alias = '', string $table = 'budgets'): string 
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
            Auth::enforce('accounting_budgets_view');
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/budgets/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/budgets/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/budgets/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $branchFilter = trim($_GET['branch_id'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $tenantCond = $this->getTenantCondition('bg', 'budgets');
        $hasBranch = $this->hasBranchesSupport();
        
        $convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

        $budgets = []; $branches = []; $dbErrors = [];
        $stats = (object)['total_budgets'=>0, 'active_budgets'=>0, 'total_allocated'=>0];
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
                    $where[] = "(bg.name_ar LIKE ? OR bg.name_en LIKE ? OR bg.fiscal_year LIKE ?)";
                    $like = "%{$search}%";
                    $params = array_merge($params, [$like, $like, $like]);
                }
                if ($statusFilter !== '') {
                    $where[] = "bg.is_active = ?";
                    $params[] = ($statusFilter === 'active') ? 1 : 0;
                }
                if ($hasBranch && $branchFilter !== '') {
                    $where[] = "bg.branch_id = ?";
                    $params[] = (int)$branchFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM budgets bg $whereSql");
                $countStmt->execute($params);
                $totalPages = max(1, ceil($countStmt->fetchColumn() / $limit));

                $branchSelect = $hasBranch ? ", b.name_ar as branch_name, b.name_en as branch_name_en" : "";
                $branchJoin   = $hasBranch ? "LEFT JOIN branches b ON bg.branch_id = b.id" : "";

                $stmt = $this->db->prepare("
                    SELECT bg.*, a.name_ar as account_name, cc.name_ar as cost_center_name $branchSelect
                    FROM budgets bg
                    LEFT JOIN accounts a ON bg.account_id = a.id
                    LEFT JOIN cost_centers cc ON bg.cost_center_id = cc.id
                    $branchJoin
                    $whereSql 
                    ORDER BY bg.fiscal_year DESC, bg.id DESC LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $budgets = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                foreach ($budgets as $b) {
                    $b->total_amount = $convert($b->total_amount);
                }

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_budgets,
                        SUM(IF(is_active = 1, 1, 0)) as active_budgets,
                        SUM(total_amount) as total_allocated
                    FROM budgets bg WHERE $tenantCond
                ")->fetch(PDO::FETCH_OBJ);

                if ($statsData) {
                    $stats = $statsData;
                    $stats->total_allocated = $convert($stats->total_allocated ?? 0);
                }

            } catch (Throwable $e) {
                $dbErrors[] = $e->getMessage();
            }
        }

        $currentPage = $page;
        return $this->renderView('/resources/views/accounting/budgets/index.php', compact(
            'budgets', 'branches', 'stats', 'totalPages', 'currentPage', 'search', 'statusFilter', 'branchFilter', 'dbErrors'
        ), $response);
    }

    public function create(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_budgets_create');
        }

        $budget = null; $accounts = []; $costCenters = []; $branches = [];
        $hasBranch = $this->hasBranchesSupport();

        if ($this->db) {
            try {
                $companyId = (int)($_SESSION['company_id'] ?? 1);
                if ($hasBranch) {
                    $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
                }
                $accounts = $this->db->query("SELECT id, code, name_ar, name_en FROM accounts WHERE is_active = 1 AND is_parent = 0 AND type = 'expense' AND company_id = $companyId ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                $costCenters = $this->db->query("SELECT id, code, name_ar, name_en FROM cost_centers WHERE is_active = 1 AND is_parent = 0 AND company_id = $companyId ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/accounting/budgets/create.php', compact('budget', 'accounts', 'costCenters', 'branches'), $response);
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_budgets_edit');
        }

        $id = $this->resolveId($id);
        $hasBranch = $this->hasBranchesSupport();
        
        $budget = null; $accounts = []; $costCenters = []; $branches = [];

        try {
            if (!$id || !$this->db) throw new Exception("معرف الموازنة غير صالح.");
            $companyId = (int)($_SESSION['company_id'] ?? 1);

            $stmt = $this->db->prepare("SELECT * FROM budgets WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $budget = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$budget) throw new Exception("الموازنة غير موجودة.");

            if ($hasBranch) {
                $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
            }
            $accounts = $this->db->query("SELECT id, code, name_ar, name_en FROM accounts WHERE is_active = 1 AND is_parent = 0 AND type = 'expense' AND company_id = $companyId ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $costCenters = $this->db->query("SELECT id, code, name_ar, name_en FROM cost_centers WHERE is_active = 1 AND is_parent = 0 AND company_id = $companyId ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/budgets');
        }

        return $this->renderView('/resources/views/accounting/budgets/create.php', compact('budget', 'accounts', 'costCenters', 'branches'), $response);
    }

    public function store(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_budgets_create');
        }

        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $sessionBranch = (int)($_SESSION['branch_id'] ?? 0);
        $branchId = ($sessionBranch > 0) ? $sessionBranch : (!empty($data['branch_id']) ? (int)$data['branch_id'] : 0);

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");
            
            $amount = (float)($data['total_amount'] ?? 0);
            $isActive = isset($data['is_active']) ? 1 : 0;
            $accountId = !empty($data['account_id']) ? (int)$data['account_id'] : null;
            $costCenterId = !empty($data['cost_center_id']) ? (int)$data['cost_center_id'] : null;

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO budgets (company_id, branch_id, name_ar, name_en, fiscal_year, total_amount, account_id, cost_center_id, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $branchId, trim($data['name_ar']), trim($data['name_en'] ?? ''),
                    trim($data['fiscal_year'] ?? date('Y')), $amount, $accountId, $costCenterId, $isActive
                ]);
            } catch (\PDOException $ex) {
                // Fallback if branch_id is missing
                $stmt = $this->db->prepare("
                    INSERT INTO budgets (company_id, name_ar, name_en, fiscal_year, total_amount, account_id, cost_center_id, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, trim($data['name_ar']), trim($data['name_en'] ?? ''),
                    trim($data['fiscal_year'] ?? date('Y')), $amount, $accountId, $costCenterId, $isActive
                ]);
            }

            $_SESSION['flash_msg'] = "تم إدراج الموازنة التقديرية بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/budgets/create');
        }
        return new RedirectResponse('/ERP/accounting/budgets');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_budgets_edit');
        }

        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $sessionBranch = (int)($_SESSION['branch_id'] ?? 0);
        $branchId = ($sessionBranch > 0) ? $sessionBranch : (!empty($data['branch_id']) ? (int)$data['branch_id'] : 0);

        try {
            if (!$this->db) throw new Exception("Database error.");
            
            $amount = (float)($data['total_amount'] ?? 0);
            $isActive = isset($data['is_active']) ? 1 : 0;
            $accountId = !empty($data['account_id']) ? (int)$data['account_id'] : null;
            $costCenterId = !empty($data['cost_center_id']) ? (int)$data['cost_center_id'] : null;

            try {
                $stmt = $this->db->prepare("
                    UPDATE budgets SET
                    branch_id=?, name_ar=?, name_en=?, fiscal_year=?, total_amount=?, account_id=?, cost_center_id=?, is_active=?
                    WHERE id=? AND company_id=?
                ");
                $stmt->execute([
                    $branchId, trim($data['name_ar']), trim($data['name_en'] ?? ''),
                    trim($data['fiscal_year'] ?? date('Y')), $amount, $accountId, $costCenterId, $isActive, $id, $companyId
                ]);
            } catch (\PDOException $ex) {
                $stmt = $this->db->prepare("
                    UPDATE budgets SET
                    name_ar=?, name_en=?, fiscal_year=?, total_amount=?, account_id=?, cost_center_id=?, is_active=?
                    WHERE id=? AND company_id=?
                ");
                $stmt->execute([
                    trim($data['name_ar']), trim($data['name_en'] ?? ''),
                    trim($data['fiscal_year'] ?? date('Y')), $amount, $accountId, $costCenterId, $isActive, $id, $companyId
                ]);
            }

            $_SESSION['flash_msg'] = "تم تحديث بيانات الموازنة بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/accounting/budgets/{$id}/edit");
        }
        return new RedirectResponse('/ERP/accounting/budgets');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_budgets_delete');
        }

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = (int)($_SESSION['company_id'] ?? 1);

        try {
            if (!$this->db) throw new Exception("Database error.");
            $this->db->prepare("DELETE FROM budgets WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف الموازنة بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse('/ERP/accounting/budgets');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_budgets_view');
        }

        $id = $this->resolveId($id);
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $hasBranch = $this->hasBranchesSupport();
        
        $convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

        try {
            $branchSelect = $hasBranch ? ", b.name_ar as branch_name, b.name_en as branch_name_en" : "";
            $branchJoin   = $hasBranch ? "LEFT JOIN branches b ON bg.branch_id = b.id" : "";

            $stmt = $this->db->prepare("
                SELECT bg.*, a.name_ar as acc_name, a.code as acc_code, cc.name_ar as cc_name, cc.code as cc_code $branchSelect
                FROM budgets bg
                LEFT JOIN accounts a ON bg.account_id = a.id
                LEFT JOIN cost_centers cc ON bg.cost_center_id = cc.id
                $branchJoin
                WHERE bg.id = ? AND bg.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $budget = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$budget) throw new Exception("الموازنة غير موجودة.");

            // احتساب المصروف الفعلي من القيود المرحلة (Posted)
            $actualSpent = 0;
            $transactions = [];

            if ($budget->account_id || $budget->cost_center_id) {
                $conds = [];
                if ($budget->account_id) $conds[] = "ji.account_id = " . (int)$budget->account_id;
                if ($budget->cost_center_id) $conds[] = "ji.cost_center_id = " . (int)$budget->cost_center_id;
                
                $matchCond = implode(" OR ", $conds);
                // فلترة السنة المالية
                $yearCond = "YEAR(je.entry_date) = " . (int)$budget->fiscal_year;

                // جلب القيود
                $txStmt = $this->db->prepare("
                    SELECT ji.*, je.entry_number, je.entry_date, je.description as entry_desc
                    FROM journal_entry_items ji
                    JOIN journal_entries je ON ji.journal_entry_id = je.id
                    WHERE ($matchCond) AND je.status = 'posted' AND je.company_id = ? AND $yearCond
                    ORDER BY je.entry_date DESC
                ");
                $txStmt->execute([$companyId]);
                $transactions = $txStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                foreach ($transactions as $tx) {
                    $tx->debit = $convert($tx->debit);
                    $tx->credit = $convert($tx->credit);
                    // باعتبار الحساب مصروف، المصروف الفعلي = المدين - الدائن
                    $actualSpent += ($tx->debit - $tx->credit);
                }
            }

            $budget->total_amount = $convert($budget->total_amount);
            
            $remaining = $budget->total_amount - $actualSpent;
            $percent = $budget->total_amount > 0 ? min(100, round(($actualSpent / $budget->total_amount) * 100, 1)) : 0;

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/budgets');
        }

        return $this->renderView('/resources/views/accounting/budgets/show.php', compact('budget', 'actualSpent', 'remaining', 'percent', 'transactions'), $response);
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