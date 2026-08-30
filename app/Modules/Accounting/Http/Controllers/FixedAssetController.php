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
    private PDO $db;

    public function __construct()
    {
        ini_set('display_errors', 0);
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    private function resolveId($id = null): ?int
    {
        if (!empty($id) && is_numeric($id)) return (int)$id;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/fixed-assets/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fixed_assets_view');

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/fixed-assets/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/fixed-assets/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/fixed-assets/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/fixed-assets/(\d+)/depreciate#', $uri, $m)) return $this->depreciate($request, $response, (int)$m[1]);
        if (preg_match('#/fixed-assets/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $categoryFilter = trim($_GET['category'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $companyId = current_company() ?? 1;

        try {
            $where = ["company_id = ?"];
            $params = [$companyId];

            if ($search !== '') {
                $where[] = "(code LIKE ? OR name_ar LIKE ? OR name_en LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like]);
            }
            if ($categoryFilter !== '') {
                $where[] = "category = ?";
                $params[] = $categoryFilter;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM fixed_assets $whereSql");
            $countStmt->execute($params);
            $totalPages = max(1, ceil($countStmt->fetchColumn() / $limit));

            $stmt = $this->db->prepare("SELECT * FROM fixed_assets $whereSql ORDER BY code ASC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            $assets = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $statsStmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_assets,
                    SUM(purchase_cost) as total_cost,
                    SUM(accumulated_depreciation) as total_depreciation,
                    SUM(book_value) as total_book_value
                FROM fixed_assets WHERE company_id = ?
            ");
            $statsStmt->execute([$companyId]);
            $stats = $statsStmt->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $assets = [];
            $stats = (object)['total_assets'=>0, 'total_cost'=>0, 'total_depreciation'=>0, 'total_book_value'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;
        ob_start(); include $this->basePath . '/resources/views/accounting/fixed_assets/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fixed_assets_create');

        $companyId = current_company() ?? 1;
        $asset = null;
        $accounts = $this->db->query("SELECT id, code, name_ar FROM accounts WHERE is_active = 1 AND is_parent = 0 AND company_id = {$companyId} ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);
        $costCenters = $this->db->query("SELECT id, code, name_ar FROM cost_centers WHERE is_active = 1 AND is_parent = 0 AND company_id = {$companyId} ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);

        ob_start(); include $this->basePath . '/resources/views/accounting/fixed_assets/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fixed_assets_create');

        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;
        $branchId = current_branch() ?? null;

        try {
            $cost = (float)($data['purchase_cost'] ?? 0);
            $salvage = (float)($data['salvage_value'] ?? 0);
            $bookValue = $cost;

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

            $_SESSION['flash_msg'] = __('تم إدراج الأصل الثابت بنجاح.', 'Fixed asset registered successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/fixed-assets/create');
        }
        return new RedirectResponse('/ERP/accounting/fixed-assets');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fixed_assets_edit');

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        try {
            $stmt = $this->db->prepare("SELECT * FROM fixed_assets WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $asset = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$asset) throw new Exception(__('الأصل غير موجود.', 'Asset not found.'));

            $accounts = $this->db->query("SELECT id, code, name_ar FROM accounts WHERE is_active = 1 AND is_parent = 0 AND company_id = {$companyId} ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);
            $costCenters = $this->db->query("SELECT id, code, name_ar FROM cost_centers WHERE is_active = 1 AND is_parent = 0 AND company_id = {$companyId} ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/fixed-assets');
        }

        ob_start(); include $this->basePath . '/resources/views/accounting/fixed_assets/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fixed_assets_edit');

        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $cost = (float)($data['purchase_cost'] ?? 0);
            $salvage = (float)($data['salvage_value'] ?? 0);

            $currentAsset = $this->db->query("SELECT accumulated_depreciation FROM fixed_assets WHERE id = $id")->fetch(PDO::FETCH_OBJ);
            $accDep = (float)($currentAsset->accumulated_depreciation ?? 0);
            $bookValue = max(0, $cost - $accDep);

            $stmt = $this->db->prepare("
                UPDATE fixed_assets SET
                code=?, name_ar=?, name_en=?, category=?, purchase_date=?, purchase_cost=?, salvage_value=?,
                useful_life_years=?, depreciation_method=?, book_value=?, asset_account_id=?,
                dep_expense_account_id=?, acc_dep_account_id=?, cost_center_id=?
                WHERE id=?
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
                $id
            ]);

            $_SESSION['flash_msg'] = __('تم تحديث بيانات الأصل بنجاح.', 'Asset updated successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/accounting/fixed-assets/{$id}/edit");
        }
        return new RedirectResponse('/ERP/accounting/fixed-assets');
    }

    public function depreciate(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fixed_assets_process');

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT * FROM fixed_assets WHERE id = ?");
            $stmt->execute([$id]);
            $asset = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$asset || $asset->status !== 'active') throw new Exception(__('الأصل غير جاهز للإهلاك.', 'Asset not active for depreciation.'));
            if (empty($asset->dep_expense_account_id) || empty($asset->acc_dep_account_id)) {
                throw new Exception(__('يرجى ربط حساب مجمع الإهلاك وحساب مصروف الإهلاك بالأصل أولاً.', 'Please link depreciation accounts.'));
            }

            // حساب الإهلاك السنوي طريقة القسط الثابت
            $depreciableAmount = $asset->purchase_cost - $asset->salvage_value;
            $annualDepreciation = $depreciableAmount / max(1, $asset->useful_life_years);
            $monthlyDepreciation = round($annualDepreciation / 12, 2);

            if ($asset->book_value <= $asset->salvage_value) {
                throw new Exception(__('وصل الأصل إلى قيمته التخريدية، لا يمكن حساب إهلاك إضافي.', 'Asset fully depreciated.'));
            }

            $depAmount = min($monthlyDepreciation, $asset->book_value - $asset->salvage_value);

            // 1. توليد قيد يومية آلي للإهلاك
            $entryNum = 'DEP-' . date('ymd') . '-' . rand(100, 999);
            $entryDesc = "قيد إهلاك شهري للأصل: {$asset->name_ar} ({$asset->code})";

            $jeStmt = $this->db->prepare("INSERT INTO journal_entries (company_id, entry_number, entry_date, description, total_amount, status) VALUES (?, ?, ?, ?, ?, 'posted')");
            $jeStmt->execute([$companyId, $entryNum, date('Y-m-d'), $entryDesc, $depAmount]);
            $jeId = $this->db->lastInsertId();

            // طرف مدين: مصروف الإهلاك
            $itemStmt = $this->db->prepare("INSERT INTO journal_entry_items (journal_entry_id, account_id, cost_center_id, description, debit, credit) VALUES (?, ?, ?, ?, ?, ?)");
            $itemStmt->execute([$jeId, $asset->dep_expense_account_id, $asset->cost_center_id, "مصروف إهلاك - {$asset->name_ar}", $depAmount, 0]);

            // طرف دائن: مجمع الإهلاك
            $itemStmt->execute([$jeId, $asset->acc_dep_account_id, $asset->cost_center_id, "مجمع إهلاك - {$asset->name_ar}", 0, $depAmount]);

            // 2. تسجيل الحركة وترحيل الأرصدة
            $this->db->prepare("INSERT INTO asset_depreciations (asset_id, journal_entry_id, depreciation_date, amount, notes) VALUES (?, ?, ?, ?, ?)")
                ->execute([$id, $jeId, date('Y-m-d'), $depAmount, "إهلاك شهري آلي"]);

            $newAccDep = $asset->accumulated_depreciation + $depAmount;
            $newBookValue = $asset->purchase_cost - $newAccDep;
            $newStatus = ($newBookValue <= $asset->salvage_value) ? 'fully_depreciated' : 'active';

            $this->db->prepare("UPDATE fixed_assets SET accumulated_depreciation=?, book_value=?, status=? WHERE id=?")
                ->execute([$newAccDep, $newBookValue, $newStatus, $id]);

            $this->db->commit();
            $_SESSION['flash_msg'] = __('تم إثبات إهلاك الأصل بقيمة ', 'Depreciation posted: ') . number_format($depAmount, 2) . " " . __('وتوليد القيد بنجاح.', 'successfully.');
        } catch (Throwable $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse("/ERP/accounting/fixed-assets/{$id}");
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fixed_assets_view');

        $id = $this->resolveId($id);

        $stmt = $this->db->prepare("
            SELECT fa.*, 
                   a1.name_ar as asset_account_name, a1.code as asset_account_code,
                   a2.name_ar as exp_account_name,
                   a3.name_ar as acc_account_name,
                   cc.name_ar as cost_center_name
            FROM fixed_assets fa
            LEFT JOIN accounts a1 ON fa.asset_account_id = a1.id
            LEFT JOIN accounts a2 ON fa.dep_expense_account_id = a2.id
            LEFT JOIN accounts a3 ON fa.acc_dep_account_id = a3.id
            LEFT JOIN cost_centers cc ON fa.cost_center_id = cc.id
            WHERE fa.id = ?
        ");
        $stmt->execute([$id]);
        $asset = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$asset) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = __('الأصل الثابت غير موجود.', 'Asset not found.');
            return new RedirectResponse('/ERP/accounting/fixed-assets');
        }

        $depreciations = $this->db->query("
            SELECT ad.*, je.entry_number 
            FROM asset_depreciations ad 
            LEFT JOIN journal_entries je ON ad.journal_entry_id = je.id 
            WHERE ad.asset_id = {$id} 
            ORDER BY ad.depreciation_date DESC
        ")->fetchAll(PDO::FETCH_OBJ);

        ob_start(); include $this->basePath . '/resources/views/accounting/fixed_assets/show.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fixed_assets_delete');

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $this->db->prepare("DELETE FROM fixed_assets WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = __('تم حذف الأصل الثابت بنجاح.', 'Asset deleted successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse('/ERP/accounting/fixed-assets');
    }
}