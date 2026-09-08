<?php
// Path: app/Modules/Accounting/Http/Controllers/AccountController.php

namespace App\Modules\Accounting\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use Core\Security\Auth;
use PDO;
use Exception;
use Throwable;

class AccountController extends Controller
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
        if (preg_match('#/chart-of-accounts/(\d+)#', $uri, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    // دالة ديناميكية تفحص وجود الأعمدة قبل بناء الشروط
    private function getTenantCondition(string $alias = '', string $table = 'accounts'): string 
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $branchId  = (int)($_SESSION['branch_id'] ?? 0);
        
        $prefix = $alias ? $alias . '.' : '';
        $cond = "1=1"; // افتراضي إذا لم يوجد عمود شركة
        
        if ($this->db) {
            try {
                $this->db->query("SELECT company_id FROM {$table} LIMIT 1");
                $cond = "({$prefix}company_id = {$companyId} OR {$prefix}company_id IS NULL OR {$prefix}company_id = 0)";
            } catch(Throwable $e) {}

            if ($branchId > 0) {
                try {
                    $this->db->query("SELECT branch_id FROM {$table} LIMIT 1");
                    $cond .= " AND ({$prefix}branch_id = {$branchId} OR {$prefix}branch_id IS NULL OR {$prefix}branch_id = 0)";
                } catch (Throwable $e) {}
            }
        }
        return $cond;
    }

    private function hasBranchesSupport(): bool 
    {
        if (!$this->db) return false;
        try {
            $this->db->query("SELECT branch_id FROM accounts LIMIT 1");
            $this->db->query("SELECT id FROM branches LIMIT 1");
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function index(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_accounts_view');
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/chart-of-accounts/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/chart-of-accounts/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/chart-of-accounts/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $typeFilter = trim($_GET['type'] ?? '');
        $branchFilter = trim($_GET['branch_id'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $tenantCond = $this->getTenantCondition('a', 'accounts');
        $hasBranch = $this->hasBranchesSupport();
        
        $convert = function($amt) {
            return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt;
        };

        $accounts = [];
        $branches = [];
        $dbErrors = [];
        $stats = (object)['total_accounts'=>0, 'assets_count'=>0, 'liabilities_count'=>0, 'equity_count'=>0, 'revenue_count'=>0, 'expense_count'=>0];
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
                    $where[] = "(a.code LIKE ? OR a.name_ar LIKE ? OR a.name_en LIKE ?)";
                    $like = "%{$search}%";
                    $params = array_merge($params, [$like, $like, $like]);
                }

                if ($typeFilter !== '') {
                    $where[] = "a.type = ?";
                    $params[] = $typeFilter;
                }

                if ($hasBranch && $branchFilter !== '') {
                    $where[] = "a.branch_id = ?";
                    $params[] = (int)$branchFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM accounts a $whereSql");
                $countStmt->execute($params);
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                // بناء الاستعلام بمرونة حسب وجود الفروع
                $branchSelect = $hasBranch ? ", b.name_ar as branch_name, b.name_en as branch_name_en" : "";
                $branchJoin   = $hasBranch ? "LEFT JOIN branches b ON a.branch_id = b.id" : "";

                $stmt = $this->db->prepare("
                    SELECT a.*, p.name_ar as parent_name, p.code as parent_code $branchSelect
                    FROM accounts a
                    LEFT JOIN accounts p ON a.parent_id = p.id
                    $branchJoin
                    $whereSql
                    ORDER BY a.code ASC LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $accounts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                foreach ($accounts as $acc) {
                    $acc->current_balance = $convert($acc->current_balance ?? 0);
                }

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_accounts,
                        SUM(IF(type='asset', 1, 0)) as assets_count,
                        SUM(IF(type='liability', 1, 0)) as liabilities_count,
                        SUM(IF(type='equity', 1, 0)) as equity_count,
                        SUM(IF(type='revenue', 1, 0)) as revenue_count,
                        SUM(IF(type='expense', 1, 0)) as expense_count
                    FROM accounts a WHERE $tenantCond
                ")->fetch(PDO::FETCH_OBJ);

                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                $dbErrors[] = $e->getMessage();
            }
        }

        $currentPage = $page;

        return $this->renderView('/resources/views/accounting/accounts/index.php', compact(
            'accounts', 'branches', 'stats', 'totalPages', 'currentPage', 'search', 'typeFilter', 'branchFilter', 'dbErrors'
        ), $response);
    }

    public function create(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_accounts_create');
        }

        $account = null; 
        $parentAccounts = [];
        $branches = [];
        $tenantCond = $this->getTenantCondition('a', 'accounts');
        $hasBranch = $this->hasBranchesSupport();

        if ($this->db) {
            try {
                $companyId = (int)($_SESSION['company_id'] ?? 1);
                if ($hasBranch) {
                    $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
                }

                $parentAccounts = $this->db->query("
                    SELECT id, code, name_ar, name_en, type 
                    FROM accounts a
                    WHERE (parent_id IS NULL OR is_parent = 1) AND $tenantCond
                    ORDER BY code ASC
                ")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/accounting/accounts/create.php', compact('account', 'parentAccounts', 'branches'), $response);
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_accounts_edit');
        }
        $id = $this->resolveId($id);
        $tenantCond = $this->getTenantCondition('a', 'accounts');
        $hasBranch = $this->hasBranchesSupport();
        
        $account = null; $parentAccounts = []; $branches = [];

        try {
            if (!$id || !$this->db) throw new Exception("معرف الحساب غير صالح.");

            $companyId = (int)($_SESSION['company_id'] ?? 1);
            if ($hasBranch) {
                $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
            }

            $stmt = $this->db->prepare("SELECT * FROM accounts WHERE id = ?");
            $stmt->execute([$id]);
            $account = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$account) throw new Exception("الحساب المالي غير موجود.");

            $parentAccounts = $this->db->query("
                SELECT id, code, name_ar, name_en, type 
                FROM accounts a
                WHERE (parent_id IS NULL OR is_parent = 1) AND id != {$id} AND $tenantCond
                ORDER BY code ASC
            ")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/chart-of-accounts');
        }

        return $this->renderView('/resources/views/accounting/accounts/create.php', compact('account', 'parentAccounts', 'branches'), $response);
    }

    public function store(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_accounts_create');
        }

        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $companyId = $_SESSION['company_id'] ?? 1;
        $sessionBranch = (int)($_SESSION['branch_id'] ?? 0);
        $branchId = ($sessionBranch > 0) ? $sessionBranch : (!empty($data['branch_id']) ? (int)$data['branch_id'] : 0);

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");
            if (empty($data['code']) || empty($data['name_ar']) || empty($data['type'])) {
                throw new Exception("يرجى تعبئة الحقول الأساسية.");
            }

            $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
            $level = 1;

            if ($parentId) {
                $pStmt = $this->db->prepare("SELECT account_level FROM accounts WHERE id = ?");
                $pStmt->execute([$parentId]);
                $level = ((int)$pStmt->fetchColumn()) + 1;
            }

            $openingBalance = (float)($data['opening_balance'] ?? 0);
            $isParent = isset($data['is_parent']) ? 1 : 0;
            $isActive = isset($data['is_active']) ? 1 : 0;

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO accounts (company_id, branch_id, code, name_ar, name_en, type, parent_id, account_level, is_parent, opening_balance, current_balance, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $branchId, trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''),
                    $data['type'], $parentId, $level, $isParent, $openingBalance, $openingBalance, $isActive
                ]);
            } catch (\PDOException $ex) {
                // الفولباك الآمن لو عمود الفرع مش موجود
                $stmt = $this->db->prepare("
                    INSERT INTO accounts (company_id, code, name_ar, name_en, type, parent_id, account_level, is_parent, opening_balance, current_balance, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''),
                    $data['type'], $parentId, $level, $isParent, $openingBalance, $openingBalance, $isActive
                ]);
            }

            $_SESSION['flash_msg'] = "تم إضافة الحساب المالي بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/chart-of-accounts/create');
        }

        return new RedirectResponse('/ERP/accounting/chart-of-accounts');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_accounts_edit');
        }

        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $sessionBranch = (int)($_SESSION['branch_id'] ?? 0);
        $branchId = ($sessionBranch > 0) ? $sessionBranch : (!empty($data['branch_id']) ? (int)$data['branch_id'] : 0);

        try {
            if (!$id || !$this->db) throw new Exception("معرف الحساب غير صالح.");

            $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
            $level = 1;

            if ($parentId) {
                $pStmt = $this->db->prepare("SELECT account_level FROM accounts WHERE id = ?");
                $pStmt->execute([$parentId]);
                $level = ((int)$pStmt->fetchColumn()) + 1;
            }

            $isParent = isset($data['is_parent']) ? 1 : 0;
            $isActive = isset($data['is_active']) ? 1 : 0;

            try {
                $stmt = $this->db->prepare("
                    UPDATE accounts 
                    SET branch_id=?, code=?, name_ar=?, name_en=?, type=?, parent_id=?, account_level=?, is_parent=?, is_active=?
                    WHERE id=?
                ");
                $stmt->execute([
                    $branchId, trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''),
                    $data['type'], $parentId, $level, $isParent, $isActive, $id
                ]);
            } catch (\PDOException $ex) {
                $stmt = $this->db->prepare("
                    UPDATE accounts 
                    SET code=?, name_ar=?, name_en=?, type=?, parent_id=?, account_level=?, is_parent=?, is_active=?
                    WHERE id=?
                ");
                $stmt->execute([
                    trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''),
                    $data['type'], $parentId, $level, $isParent, $isActive, $id
                ]);
            }

            $_SESSION['flash_msg'] = "تم تحديث الحساب المالي بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/accounting/chart-of-accounts/{$id}/edit");
        }

        return new RedirectResponse('/ERP/accounting/chart-of-accounts');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_accounts_delete');
        }

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $hasChildren = $this->db->prepare("SELECT COUNT(*) FROM accounts WHERE parent_id = ?");
                $hasChildren->execute([$id]);
                if ($hasChildren->fetchColumn() > 0) {
                    throw new Exception("تعذر الحذف: لا يمكن حذف حساب رئيسي تتبع له حسابات فرعية.");
                }

                $hasEntries = $this->db->prepare("SELECT COUNT(*) FROM journal_entry_items WHERE account_id = ?");
                $hasEntries->execute([$id]);
                if ($hasEntries->fetchColumn() > 0) {
                    throw new Exception("لا يمكن حذف هذا الحساب لوجود حركات مالية عليه. قم بتعطيله بدلاً من ذلك.");
                }

                $this->db->prepare("DELETE FROM accounts WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف الحساب بنجاح لعدم ارتباطه بأي قيود.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/accounting/chart-of-accounts');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_accounts_view');
        }

        $id = $this->resolveId($id);
        $convert = function($amt) {
            return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt;
        };
        
        $account = null; $movements = [];
        $totals = (object)['total_debit' => 0, 'total_credit' => 0];

        try {
            if (!$id || !$this->db) throw new Exception("معرف الحساب غير صالح.");

            $hasBranch = $this->hasBranchesSupport();
            $branchSelect = $hasBranch ? ", b.name_ar as branch_name, b.name_en as branch_name_en" : "";
            $branchJoin   = $hasBranch ? "LEFT JOIN branches b ON a.branch_id = b.id" : "";

            $stmt = $this->db->prepare("
                SELECT a.*, p.name_ar as parent_name, p.code as parent_code $branchSelect
                FROM accounts a
                LEFT JOIN accounts p ON a.parent_id = p.id
                $branchJoin
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $account = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$account) throw new Exception("الحساب المالي غير موجود.");

            $account->opening_balance = $convert($account->opening_balance ?? 0);
            $account->current_balance = $convert($account->current_balance ?? 0);

            try {
                $tenantCond = $this->getTenantCondition('je', 'journal_entries');

                $mStmt = $this->db->prepare("
                    SELECT ji.*, je.entry_number, je.entry_date, je.description as entry_desc, je.status
                    FROM journal_entry_items ji
                    JOIN journal_entries je ON ji.journal_entry_id = je.id
                    WHERE ji.account_id = ? AND je.status = 'posted' AND $tenantCond
                    ORDER BY je.entry_date DESC, je.id DESC LIMIT 100
                ");
                $mStmt->execute([$id]);
                $movements = $mStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                foreach ($movements as $m) {
                    $m->debit = $convert($m->debit);
                    $m->credit = $convert($m->credit);
                }

                $tStmt = $this->db->prepare("
                    SELECT COALESCE(SUM(ji.debit), 0) as total_debit, COALESCE(SUM(ji.credit), 0) as total_credit
                    FROM journal_entry_items ji
                    JOIN journal_entries je ON ji.journal_entry_id = je.id
                    WHERE ji.account_id = ? AND je.status = 'posted' AND $tenantCond
                ");
                $tStmt->execute([$id]);
                $totalsData = $tStmt->fetch(PDO::FETCH_OBJ);
                
                if ($totalsData) {
                    $totals->total_debit = $convert($totalsData->total_debit);
                    $totals->total_credit = $convert($totalsData->total_credit);
                }

            } catch (Throwable $e) {}

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/chart-of-accounts');
        }

        return $this->renderView('/resources/views/accounting/accounts/show.php', compact(
            'account', 'movements', 'totals'
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
            ob_start();
            include $fullPath;
            $content = ob_get_clean();

            ob_start();
            include $this->basePath . '/resources/views/layouts/app.php';
            $finalHtml = ob_get_clean();
            return $response->setContent($finalHtml)->setHeader('Content-Type', 'text/html; charset=UTF-8');
        } catch (Throwable $e) {
            ob_end_clean();
            die("<div style='padding:30px; background:#fef2f2; color:#dc2626; font-family:monospace;'><h3>Render Error:</h3>" . htmlspecialchars($e->getMessage()) . "</div>");
        }
    }
}