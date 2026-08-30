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
        if (preg_match('#/cost-centers/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_cost_centers_view');

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/cost-centers/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/cost-centers/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/cost-centers/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/cost-centers/(\d+)/report#', $uri, $m)) return $this->report($request, $response, (int)$m[1]);
        if (preg_match('#/cost-centers/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            // Multi-Tenancy: Filter by active company
            $companyId = current_company() ?? 1;
            $where = ["c.company_id = ?"];
            $params = [$companyId];

            if ($search !== '') {
                $where[] = "(c.code LIKE ? OR c.name_ar LIKE ? OR c.name_en LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like]);
            }
            if ($statusFilter !== '') {
                $where[] = "c.is_active = ?";
                $params[] = ($statusFilter === 'active') ? 1 : 0;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM cost_centers c $whereSql");
            $countStmt->execute($params);
            $totalPages = max(1, ceil($countStmt->fetchColumn() / $limit));

            $stmt = $this->db->prepare("
                SELECT c.*, p.name_ar as parent_name, p.code as parent_code
                FROM cost_centers c
                LEFT JOIN cost_centers p ON c.parent_id = p.id
                $whereSql
                ORDER BY c.code ASC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $centers = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $statsStmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_centers,
                    SUM(IF(is_parent = 1, 1, 0)) as parent_centers,
                    SUM(IF(is_parent = 0, 1, 0)) as sub_centers,
                    SUM(IF(is_active = 1, 1, 0)) as active_centers
                FROM cost_centers WHERE company_id = ?
            ");
            $statsStmt->execute([$companyId]);
            $stats = $statsStmt->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $centers = [];
            $stats = (object)['total_centers'=>0, 'parent_centers'=>0, 'sub_centers'=>0, 'active_centers'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;
        ob_start(); include $this->basePath . '/resources/views/accounting/cost_centers/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_cost_centers_create');

        $center = null; $parentCenters = [];
        try {
            $companyId = current_company() ?? 1;
            $parentCenters = $this->db->query("SELECT id, code, name_ar FROM cost_centers WHERE is_parent = 1 AND company_id = {$companyId} ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); include $this->basePath . '/resources/views/accounting/cost_centers/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_cost_centers_create');

        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (empty($data['code']) || empty($data['name_ar'])) {
                throw new Exception(__('يرجى تعبئة الحقول الإلزامية.', 'Please fill the required fields.'));
            }

            $companyId = current_company() ?? 1;
            $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
            $budget = !empty($data['budget_amount']) ? (float)$data['budget_amount'] : 0.00;
            $isParent = isset($data['is_parent']) ? 1 : 0;
            $isActive = isset($data['is_active']) ? 1 : 0;

            $stmt = $this->db->prepare("
                INSERT INTO cost_centers (company_id, code, name_ar, name_en, budget_amount, parent_id, is_parent, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''), $budget,
                $parentId, $isParent, $isActive
            ]);

            $_SESSION['flash_msg'] = __('تم إنشاء مركز التكلفة بنجاح.', 'Cost center created successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/cost-centers/create');
        }
        return new RedirectResponse('/ERP/accounting/cost-centers');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_cost_centers_edit');

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $companyId = current_company() ?? 1;
            $stmt = $this->db->prepare("SELECT * FROM cost_centers WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $center = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$center) throw new Exception(__('مركز التكلفة غير موجود.', 'Cost center not found.'));

            $parentCenters = $this->db->query("SELECT id, code, name_ar FROM cost_centers WHERE is_parent = 1 AND id != {$id} AND company_id = {$companyId} ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/cost-centers');
        }

        ob_start(); include $this->basePath . '/resources/views/accounting/cost_centers/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_cost_centers_edit');

        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
            $budget = !empty($data['budget_amount']) ? (float)$data['budget_amount'] : 0.00;
            $isParent = isset($data['is_parent']) ? 1 : 0;
            $isActive = isset($data['is_active']) ? 1 : 0;
            $companyId = current_company() ?? 1;

            $stmt = $this->db->prepare("
                UPDATE cost_centers 
                SET code = ?, name_ar = ?, name_en = ?, budget_amount = ?, parent_id = ?, is_parent = ?, is_active = ?
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([
                trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''), $budget,
                $parentId, $isParent, $isActive, $id, $companyId
            ]);

            $_SESSION['flash_msg'] = __('تم تحديث بيانات مركز التكلفة بنجاح.', 'Cost center updated successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/accounting/cost-centers/{$id}/edit");
        }
        return new RedirectResponse('/ERP/accounting/cost-centers');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_cost_centers_delete');

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $companyId = current_company() ?? 1;
            $childCheck = $this->db->prepare("SELECT COUNT(*) FROM cost_centers WHERE parent_id = ? AND company_id = ?");
            $childCheck->execute([$id, $companyId]);
            if ($childCheck->fetchColumn() > 0) {
                throw new Exception(__('لا يمكن حذف مركز تكلفة رئيسي تتبع له مراكز فرعية.', 'Cannot delete parent center with sub-centers.'));
            }

            // التأكد من عدم وجود قيود يومية
            $entriesCheck = $this->db->prepare("SELECT COUNT(*) FROM journal_entry_items WHERE cost_center_id = ?");
            $entriesCheck->execute([$id]);
            if ($entriesCheck->fetchColumn() > 0) {
                throw new Exception(__('لا يمكن حذف المركز لوجود قيود مالية وحركات مسجلة عليه.', 'Cannot delete cost center that has journal entries.'));
            }

            $this->db->prepare("DELETE FROM cost_centers WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = __('تم حذف مركز التكلفة بنجاح.', 'Cost center deleted successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse('/ERP/accounting/cost-centers');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_cost_centers_view');

        $id = $this->resolveId($id);

        $stmt = $this->db->prepare("
            SELECT c.*, p.name_ar as parent_name, p.code as parent_code
            FROM cost_centers c
            LEFT JOIN cost_centers p ON c.parent_id = p.id
            WHERE c.id = ? AND c.company_id = ?
        ");
        $stmt->execute([$id, current_company() ?? 1]);
        $center = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$center) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = __('مركز التكلفة غير موجود.', 'Cost center not found.');
            return new RedirectResponse('/ERP/accounting/cost-centers');
        }

        $subCenters = $this->db->query("SELECT * FROM cost_centers WHERE parent_id = {$id} ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);

        ob_start(); include $this->basePath . '/resources/views/accounting/cost_centers/show.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function report(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_cost_centers_report');

        $id = $this->resolveId($id);
        $companyId = current_company() ?? 1;

        try {
            $stmt = $this->db->prepare("SELECT * FROM cost_centers WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $center = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$center) throw new Exception(__('مركز التكلفة غير موجود.', 'Cost center not found.'));

            // Profit & Loss specific to Cost Center
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

            $totalRevenues = 0;
            $totalExpenses = 0;

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
                    a.code, a.name_ar, a.type,
                    SUM(ji.debit) as total_debit,
                    SUM(ji.credit) as total_credit
                FROM journal_entry_items ji
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                JOIN accounts a ON ji.account_id = a.id
                WHERE ji.cost_center_id = ? AND je.status = 'posted' AND je.company_id = ?
                GROUP BY a.id, a.code, a.name_ar, a.type
                ORDER BY a.code ASC
            ");
            $accStmt->execute([$id, $companyId]);
            $accountBreakdown = $accStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $txStmt = $this->db->prepare("
                SELECT ji.*, je.entry_number, je.entry_date, je.description as entry_desc, a.name_ar as account_name
                FROM journal_entry_items ji
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                JOIN accounts a ON ji.account_id = a.id
                WHERE ji.cost_center_id = ? AND je.status = 'posted' AND je.company_id = ?
                ORDER BY je.entry_date DESC LIMIT 30
            ");
            $txStmt->execute([$id, $companyId]);
            $transactions = $txStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/cost-centers');
        }

        ob_start();
        include $this->basePath . '/resources/views/accounting/cost_centers/report.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}