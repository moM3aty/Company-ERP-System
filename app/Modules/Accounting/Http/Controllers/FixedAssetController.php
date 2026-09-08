<?php
// Path: app/Modules/Accounting/Http/Controllers/FixedAssetController.php

namespace App\Modules\Accounting\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use Core\Security\Auth;
use PDO;
use Exception;
use Throwable;

class FixedAssetController extends Controller
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
        if (preg_match('#/fixed-assets/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    private function hasBranchesSupport(): bool 
    {
        if (!$this->db) return false;
        try {
            $this->db->query("SELECT branch_id FROM fixed_assets LIMIT 1");
            $this->db->query("SELECT id FROM branches LIMIT 1");
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function getTenantCondition(string $alias = '', string $table = 'fixed_assets'): string 
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
            Auth::enforce('accounting_fixed_assets_view');
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/fixed-assets/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/fixed-assets/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/fixed-assets/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/fixed-assets/(\d+)/depreciate#', $uri, $m)) return $this->depreciate($request, $response, (int)$m[1]);
        
        $search = trim($_GET['search'] ?? '');
        $categoryFilter = trim($_GET['category'] ?? '');
        $branchFilter = trim($_GET['branch_id'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $tenantCond = $this->getTenantCondition('fa', 'fixed_assets');
        $hasBranch = $this->hasBranchesSupport();
        
        $convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

        $assets = []; $branches = []; $dbErrors = [];
        $stats = (object)['total_assets'=>0, 'total_cost'=>0, 'total_depreciation'=>0, 'total_book_value'=>0];
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
                    $where[] = "(fa.code LIKE ? OR fa.name_ar LIKE ? OR fa.name_en LIKE ?)";
                    $like = "%{$search}%";
                    $params = array_merge($params, [$like, $like, $like]);
                }
                if ($categoryFilter !== '') {
                    $where[] = "fa.category = ?";
                    $params[] = $categoryFilter;
                }
                if ($hasBranch && $branchFilter !== '') {
                    $where[] = "fa.branch_id = ?";
                    $params[] = (int)$branchFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM fixed_assets fa $whereSql");
                $countStmt->execute($params);
                $totalPages = max(1, ceil($countStmt->fetchColumn() / $limit));

                $branchSelect = $hasBranch ? ", b.name_ar as branch_name, b.name_en as branch_name_en" : "";
                $branchJoin   = $hasBranch ? "LEFT JOIN branches b ON fa.branch_id = b.id" : "";

                $stmt = $this->db->prepare("
                    SELECT fa.* $branchSelect
                    FROM fixed_assets fa
                    $branchJoin
                    $whereSql 
                    ORDER BY fa.code ASC LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $assets = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                foreach ($assets as $a) {
                    $a->purchase_cost = $convert($a->purchase_cost);
                    $a->accumulated_depreciation = $convert($a->accumulated_depreciation);
                    $a->book_value = $convert($a->book_value);
                }

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_assets,
                        SUM(purchase_cost) as total_cost,
                        SUM(accumulated_depreciation) as total_depreciation,
                        SUM(book_value) as total_book_value
                    FROM fixed_assets fa WHERE $tenantCond
                ")->fetch(PDO::FETCH_OBJ);

                if ($statsData) {
                    $stats = $statsData;
                    $stats->total_cost = $convert($stats->total_cost ?? 0);
                    $stats->total_depreciation = $convert($stats->total_depreciation ?? 0);
                    $stats->total_book_value = $convert($stats->total_book_value ?? 0);
                }

            } catch (Throwable $e) {
                $dbErrors[] = $e->getMessage();
            }
        }

        $currentPage = $page;
        return $this->renderView('/resources/views/accounting/fixed_assets/index.php', compact(
            'assets', 'branches', 'stats', 'totalPages', 'currentPage', 'search', 'categoryFilter', 'branchFilter', 'dbErrors'
        ), $response);
    }

    public function create(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_fixed_assets_create');
        }

        $asset = null; $accounts = []; $costCenters = []; $branches = [];
        $hasBranch = $this->hasBranchesSupport();

        if ($this->db) {
            try {
                $companyId = (int)($_SESSION['company_id'] ?? 1);
                if ($hasBranch) {
                    $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
                }
                $accounts = $this->db->query("SELECT id, code, name_ar, name_en FROM accounts WHERE is_active = 1 AND is_parent = 0 AND company_id = $companyId ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                $costCenters = $this->db->query("SELECT id, code, name_ar, name_en FROM cost_centers WHERE is_active = 1 AND is_parent = 0 AND company_id = $companyId ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/accounting/fixed_assets/create.php', compact('asset', 'accounts', 'costCenters', 'branches'), $response);
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_fixed_assets_edit');
        }

        $id = $this->resolveId($id);
        $hasBranch = $this->hasBranchesSupport();
        
        $asset = null; $accounts = []; $costCenters = []; $branches = [];

        try {
            if (!$id || !$this->db) throw new Exception("معرف الأصل غير صالح.");
            $companyId = (int)($_SESSION['company_id'] ?? 1);

            $stmt = $this->db->prepare("SELECT * FROM fixed_assets WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $asset = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$asset) throw new Exception("الأصل غير موجود.");

            if ($hasBranch) {
                $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
            }
            $accounts = $this->db->query("SELECT id, code, name_ar, name_en FROM accounts WHERE is_active = 1 AND is_parent = 0 AND company_id = $companyId ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $costCenters = $this->db->query("SELECT id, code, name_ar, name_en FROM cost_centers WHERE is_active = 1 AND is_parent = 0 AND company_id = $companyId ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/fixed-assets');
        }

        return $this->renderView('/resources/views/accounting/fixed_assets/create.php', compact('asset', 'accounts', 'costCenters', 'branches'), $response);
    }

    public function store(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_fixed_assets_create');
        }

        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $sessionBranch = (int)($_SESSION['branch_id'] ?? 0);
        $branchId = ($sessionBranch > 0) ? $sessionBranch : (!empty($data['branch_id']) ? (int)$data['branch_id'] : 0);

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");
            
            $cost = (float)($data['purchase_cost'] ?? 0);
            $salvage = (float)($data['salvage_value'] ?? 0);
            $bookValue = $cost;

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO fixed_assets 
                    (company_id, branch_id, code, name_ar, name_en, category, purchase_date, purchase_cost, salvage_value, useful_life_years, depreciation_method, accumulated_depreciation, book_value, asset_account_id, dep_expense_account_id, acc_dep_account_id, cost_center_id, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, ?, ?, ?, ?, ?, 'active')
                ");
                $stmt->execute([
                    $companyId, $branchId, trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''),
                    $data['category'] ?? 'general', $data['purchase_date'], $cost, $salvage,
                    (int)($data['useful_life_years'] ?? 5), $data['depreciation_method'] ?? 'straight_line',
                    $bookValue,
                    !empty($data['asset_account_id']) ? (int)$data['asset_account_id'] : null,
                    !empty($data['dep_expense_account_id']) ? (int)$data['dep_expense_account_id'] : null,
                    !empty($data['acc_dep_account_id']) ? (int)$data['acc_dep_account_id'] : null,
                    !empty($data['cost_center_id']) ? (int)$data['cost_center_id'] : null,
                ]);
            } catch (\PDOException $ex) {
                // Fallback if branch_id is missing
                $stmt = $this->db->prepare("
                    INSERT INTO fixed_assets 
                    (company_id, code, name_ar, name_en, category, purchase_date, purchase_cost, salvage_value, useful_life_years, depreciation_method, accumulated_depreciation, book_value, asset_account_id, dep_expense_account_id, acc_dep_account_id, cost_center_id, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, ?, ?, ?, ?, ?, 'active')
                ");
                $stmt->execute([
                    $companyId, trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''),
                    $data['category'] ?? 'general', $data['purchase_date'], $cost, $salvage,
                    (int)($data['useful_life_years'] ?? 5), $data['depreciation_method'] ?? 'straight_line',
                    $bookValue,
                    !empty($data['asset_account_id']) ? (int)$data['asset_account_id'] : null,
                    !empty($data['dep_expense_account_id']) ? (int)$data['dep_expense_account_id'] : null,
                    !empty($data['acc_dep_account_id']) ? (int)$data['acc_dep_account_id'] : null,
                    !empty($data['cost_center_id']) ? (int)$data['cost_center_id'] : null,
                ]);
            }

            $_SESSION['flash_msg'] = "تم إدراج الأصل الثابت بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/fixed-assets/create');
        }
        return new RedirectResponse('/ERP/accounting/fixed-assets');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_fixed_assets_edit');
        }

        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $sessionBranch = (int)($_SESSION['branch_id'] ?? 0);
        $branchId = ($sessionBranch > 0) ? $sessionBranch : (!empty($data['branch_id']) ? (int)$data['branch_id'] : 0);

        try {
            if (!$this->db) throw new Exception("Database error.");
            
            $cost = (float)($data['purchase_cost'] ?? 0);
            $salvage = (float)($data['salvage_value'] ?? 0);

            $currentAsset = $this->db->query("SELECT accumulated_depreciation FROM fixed_assets WHERE id = $id")->fetch(PDO::FETCH_OBJ);
            $accDep = (float)($currentAsset->accumulated_depreciation ?? 0);
            $bookValue = max(0, $cost - $accDep);

            try {
                $stmt = $this->db->prepare("
                    UPDATE fixed_assets SET
                    branch_id=?, code=?, name_ar=?, name_en=?, category=?, purchase_date=?, purchase_cost=?, salvage_value=?,
                    useful_life_years=?, depreciation_method=?, book_value=?, asset_account_id=?,
                    dep_expense_account_id=?, acc_dep_account_id=?, cost_center_id=?
                    WHERE id=? AND company_id=?
                ");
                $stmt->execute([
                    $branchId, trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''),
                    $data['category'] ?? 'general', $data['purchase_date'], $cost, $salvage,
                    (int)($data['useful_life_years'] ?? 5), $data['depreciation_method'] ?? 'straight_line',
                    $bookValue,
                    !empty($data['asset_account_id']) ? (int)$data['asset_account_id'] : null,
                    !empty($data['dep_expense_account_id']) ? (int)$data['dep_expense_account_id'] : null,
                    !empty($data['acc_dep_account_id']) ? (int)$data['acc_dep_account_id'] : null,
                    !empty($data['cost_center_id']) ? (int)$data['cost_center_id'] : null,
                    $id, $companyId
                ]);
            } catch (\PDOException $ex) {
                $stmt = $this->db->prepare("
                    UPDATE fixed_assets SET
                    code=?, name_ar=?, name_en=?, category=?, purchase_date=?, purchase_cost=?, salvage_value=?,
                    useful_life_years=?, depreciation_method=?, book_value=?, asset_account_id=?,
                    dep_expense_account_id=?, acc_dep_account_id=?, cost_center_id=?
                    WHERE id=? AND company_id=?
                ");
                $stmt->execute([
                    trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''),
                    $data['category'] ?? 'general', $data['purchase_date'], $cost, $salvage,
                    (int)($data['useful_life_years'] ?? 5), $data['depreciation_method'] ?? 'straight_line',
                    $bookValue,
                    !empty($data['asset_account_id']) ? (int)$data['asset_account_id'] : null,
                    !empty($data['dep_expense_account_id']) ? (int)$data['dep_expense_account_id'] : null,
                    !empty($data['acc_dep_account_id']) ? (int)$data['acc_dep_account_id'] : null,
                    !empty($data['cost_center_id']) ? (int)$data['cost_center_id'] : null,
                    $id, $companyId
                ]);
            }

            $_SESSION['flash_msg'] = "تم تحديث بيانات الأصل بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/accounting/fixed-assets/{$id}/edit");
        }
        return new RedirectResponse('/ERP/accounting/fixed-assets');
    }

    public function depreciate(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_fixed_assets_process');
        }

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = (int)($_SESSION['company_id'] ?? 1);

        try {
            if (!$this->db) throw new Exception("Database error.");
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT * FROM fixed_assets WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $asset = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$asset || $asset->status !== 'active') throw new Exception("الأصل غير جاهز للإهلاك.");
            if (empty($asset->dep_expense_account_id) || empty($asset->acc_dep_account_id)) {
                throw new Exception("يرجى ربط حساب مجمع الإهلاك وحساب مصروف الإهلاك بالأصل أولاً.");
            }

            $depreciableAmount = $asset->purchase_cost - $asset->salvage_value;
            $annualDepreciation = $depreciableAmount / max(1, $asset->useful_life_years);
            $monthlyDepreciation = round($annualDepreciation / 12, 2);

            if ($asset->book_value <= $asset->salvage_value) {
                throw new Exception("وصل الأصل إلى قيمته التخريدية، لا يمكن حساب إهلاك إضافي.");
            }

            $depAmount = min($monthlyDepreciation, $asset->book_value - $asset->salvage_value);

            $entryNum = 'DEP-' . date('ymd') . '-' . rand(100, 999);
            $entryDesc = "قيد إهلاك شهري للأصل: {$asset->name_ar} ({$asset->code})";

            try {
                $jeStmt = $this->db->prepare("INSERT INTO journal_entries (company_id, branch_id, entry_number, entry_date, description, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, 'posted')");
                $jeStmt->execute([$companyId, $asset->branch_id ?? 0, $entryNum, date('Y-m-d'), $entryDesc, $depAmount]);
            } catch (\PDOException $e) {
                $jeStmt = $this->db->prepare("INSERT INTO journal_entries (company_id, entry_number, entry_date, description, total_amount, status) VALUES (?, ?, ?, ?, ?, 'posted')");
                $jeStmt->execute([$companyId, $entryNum, date('Y-m-d'), $entryDesc, $depAmount]);
            }
            $jeId = $this->db->lastInsertId();

            $itemStmt = $this->db->prepare("INSERT INTO journal_entry_items (journal_entry_id, account_id, cost_center_id, description, debit, credit) VALUES (?, ?, ?, ?, ?, ?)");
            $itemStmt->execute([$jeId, $asset->dep_expense_account_id, $asset->cost_center_id, "مصروف إهلاك - {$asset->name_ar}", $depAmount, 0]);
            $itemStmt->execute([$jeId, $asset->acc_dep_account_id, $asset->cost_center_id, "مجمع إهلاك - {$asset->name_ar}", 0, $depAmount]);

            $this->db->prepare("INSERT INTO asset_depreciations (asset_id, journal_entry_id, depreciation_date, amount, notes) VALUES (?, ?, ?, ?, ?)")
                ->execute([$id, $jeId, date('Y-m-d'), $depAmount, "إهلاك شهري آلي"]);

            $newAccDep = $asset->accumulated_depreciation + $depAmount;
            $newBookValue = $asset->purchase_cost - $newAccDep;
            $newStatus = ($newBookValue <= $asset->salvage_value) ? 'fully_depreciated' : 'active';

            $this->db->prepare("UPDATE fixed_assets SET accumulated_depreciation=?, book_value=?, status=? WHERE id=?")
                ->execute([$newAccDep, $newBookValue, $newStatus, $id]);

            $this->db->commit();
            
            // Note: the message amount should be converted for display, but here we just show the numeric success, 
            // the view will convert it properly. Or we just show a generic success.
            $_SESSION['flash_msg'] = "تم إثبات إهلاك الأصل وتوليد القيد بنجاح.";
        } catch (Throwable $e) {
            if ($this->db && $this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse("/ERP/accounting/fixed-assets/{$id}");
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_fixed_assets_view');
        }

        $id = $this->resolveId($id);
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $hasBranch = $this->hasBranchesSupport();
        
        $convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

        try {
            $branchSelect = $hasBranch ? ", b.name_ar as branch_name, b.name_en as branch_name_en" : "";
            $branchJoin   = $hasBranch ? "LEFT JOIN branches b ON fa.branch_id = b.id" : "";

            $stmt = $this->db->prepare("
                SELECT fa.*, 
                       a1.name_ar as asset_account_name, a1.code as asset_account_code, a1.name_en as asset_account_name_en,
                       a2.name_ar as exp_account_name, a2.name_en as exp_account_name_en,
                       a3.name_ar as acc_account_name, a3.name_en as acc_account_name_en,
                       cc.name_ar as cost_center_name, cc.name_en as cost_center_name_en
                       $branchSelect
                FROM fixed_assets fa
                LEFT JOIN accounts a1 ON fa.asset_account_id = a1.id
                LEFT JOIN accounts a2 ON fa.dep_expense_account_id = a2.id
                LEFT JOIN accounts a3 ON fa.acc_dep_account_id = a3.id
                LEFT JOIN cost_centers cc ON fa.cost_center_id = cc.id
                $branchJoin
                WHERE fa.id = ? AND fa.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $asset = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$asset) throw new Exception("الأصل الثابت غير موجود.");

            $asset->purchase_cost = $convert($asset->purchase_cost);
            $asset->salvage_value = $convert($asset->salvage_value);
            $asset->accumulated_depreciation = $convert($asset->accumulated_depreciation);
            $asset->book_value = $convert($asset->book_value);

            $depreciations = $this->db->query("
                SELECT ad.*, je.entry_number 
                FROM asset_depreciations ad 
                LEFT JOIN journal_entries je ON ad.journal_entry_id = je.id 
                WHERE ad.asset_id = {$id} 
                ORDER BY ad.depreciation_date DESC
            ")->fetchAll(PDO::FETCH_OBJ) ?: [];

            foreach($depreciations as $d) {
                $d->amount = $convert($d->amount);
            }

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/fixed-assets');
        }

        return $this->renderView('/resources/views/accounting/fixed_assets/show.php', compact('asset', 'depreciations'), $response);
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