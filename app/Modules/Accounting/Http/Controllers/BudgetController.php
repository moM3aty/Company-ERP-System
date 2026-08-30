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
        if (preg_match('#/budgets/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_budgets_view');

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/budgets/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/budgets/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/budgets/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/budgets/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $yearFilter = trim($_GET['year'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $where = ["1=1"];
            $params = [];

            if ($search !== '') {
                $where[] = "(code LIKE ? OR name_ar LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like]);
            }
            if ($yearFilter !== '') {
                $where[] = "fiscal_year = ?";
                $params[] = (int)$yearFilter;
            }
            if ($statusFilter !== '') {
                $where[] = "status = ?";
                $params[] = $statusFilter;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM budgets $whereSql");
            $countStmt->execute($params);
            $totalPages = max(1, ceil($countStmt->fetchColumn() / $limit));

            $stmt = $this->db->prepare("SELECT * FROM budgets $whereSql ORDER BY fiscal_year DESC, code ASC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            $budgets = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total_budgets,
                    SUM(IF(status='approved', 1, 0)) as approved_budgets,
                    SUM(total_allocated) as total_allocated_amount
                FROM budgets
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $budgets = [];
            $stats = (object)['total_budgets'=>0, 'approved_budgets'=>0, 'total_allocated_amount'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;
        ob_start(); include $this->basePath . '/resources/views/accounting/budgets/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_budgets_create');

        $budget = null; $items = [];
        $accounts = $this->db->query("SELECT id, code, name_ar, type FROM accounts WHERE is_active = 1 AND is_parent = 0 AND type IN ('expense','revenue') ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);

        ob_start(); include $this->basePath . '/resources/views/accounting/budgets/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_budgets_create');

        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $this->db->beginTransaction();

            $accountIds = $data['account_id'] ?? [];
            $amounts = $data['allocated_amount'] ?? [];
            $notes = $data['item_notes'] ?? [];

            $totalAllocated = 0;
            foreach ($accountIds as $k => $accId) {
                if (empty($accId)) continue;
                $totalAllocated += (float)($amounts[$k] ?? 0);
            }

            $stmt = $this->db->prepare("
                INSERT INTO budgets (name_ar, code, fiscal_year, start_date, end_date, status, total_allocated, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($data['name_ar']), trim($data['code']), (int)$data['fiscal_year'],
                $data['start_date'], $data['end_date'], $data['status'] ?? 'draft',
                $totalAllocated, trim($data['notes'] ?? '')
            ]);
            $budgetId = $this->db->lastInsertId();

            $iStmt = $this->db->prepare("INSERT INTO budget_items (budget_id, account_id, allocated_amount, notes) VALUES (?, ?, ?, ?)");
            foreach ($accountIds as $k => $accId) {
                if (empty($accId)) continue;
                $amt = (float)($amounts[$k] ?? 0);
                if ($amt <= 0) continue;
                $iStmt->execute([$budgetId, $accId, $amt, $notes[$k] ?? '']);
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = __('تم حفظ الموازنة التقديرية بنجاح.', 'Budget saved successfully.');
        } catch (Throwable $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/budgets/create');
        }
        return new RedirectResponse('/ERP/accounting/budgets');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_budgets_edit');

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $stmt = $this->db->prepare("SELECT * FROM budgets WHERE id = ?");
            $stmt->execute([$id]);
            $budget = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$budget) throw new Exception(__('الموازنة غير موجودة.', 'Budget not found.'));

            $items = $this->db->query("SELECT * FROM budget_items WHERE budget_id = {$id}")->fetchAll(PDO::FETCH_OBJ);
            $accounts = $this->db->query("SELECT id, code, name_ar, type FROM accounts WHERE is_active = 1 AND is_parent = 0 AND type IN ('expense','revenue') ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/budgets');
        }

        ob_start(); include $this->basePath . '/resources/views/accounting/budgets/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_budgets_edit');

        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $this->db->beginTransaction();

            $accountIds = $data['account_id'] ?? [];
            $amounts = $data['allocated_amount'] ?? [];
            $notes = $data['item_notes'] ?? [];

            $totalAllocated = 0;
            foreach ($accountIds as $k => $accId) {
                if (empty($accId)) continue;
                $totalAllocated += (float)($amounts[$k] ?? 0);
            }

            $stmt = $this->db->prepare("
                UPDATE budgets SET name_ar=?, code=?, fiscal_year=?, start_date=?, end_date=?, status=?, total_allocated=?, notes=?
                WHERE id=?
            ");
            $stmt->execute([
                trim($data['name_ar']), trim($data['code']), (int)$data['fiscal_year'],
                $data['start_date'], $data['end_date'], $data['status'] ?? 'draft',
                $totalAllocated, trim($data['notes'] ?? ''), $id
            ]);

            $this->db->prepare("DELETE FROM budget_items WHERE budget_id=?")->execute([$id]);
            $iStmt = $this->db->prepare("INSERT INTO budget_items (budget_id, account_id, allocated_amount, notes) VALUES (?, ?, ?, ?)");
            
            foreach ($accountIds as $k => $accId) {
                if (empty($accId)) continue;
                $amt = (float)($amounts[$k] ?? 0);
                if ($amt <= 0) continue;
                $iStmt->execute([$id, $accId, $amt, $notes[$k] ?? '']);
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = __('تم تحديث بيانات الموازنة التقديرية بنجاح.', 'Budget updated successfully.');
        } catch (Throwable $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/accounting/budgets/{$id}/edit");
        }
        return new RedirectResponse('/ERP/accounting/budgets');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_budgets_view');

        $id = $this->resolveId($id);

        $stmt = $this->db->prepare("SELECT * FROM budgets WHERE id = ?");
        $stmt->execute([$id]);
        $budget = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$budget) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = __('الموازنة غير موجودة.', 'Budget not found.');
            return new RedirectResponse('/ERP/accounting/budgets');
        }

        // تحليل الانحرافات بين الموازنة التقديرية والمنصرف الفعلي من قيود اليومية
        $itemsStmt = $this->db->prepare("
            SELECT bi.*, a.code as acc_code, a.name_ar as acc_name, a.type as acc_type,
                   COALESCE(SUM(CASE WHEN a.type = 'expense' THEN (ji.debit - ji.credit) ELSE (ji.credit - ji.debit) END), 0) as actual_amount
            FROM budget_items bi
            JOIN accounts a ON bi.account_id = a.id
            LEFT JOIN journal_entry_items ji ON bi.account_id = ji.account_id
            LEFT JOIN journal_entries je ON ji.journal_entry_id = je.id 
                 AND je.status = 'posted' 
                 AND je.entry_date BETWEEN ? AND ?
            WHERE bi.budget_id = ?
            GROUP BY bi.id, bi.budget_id, bi.account_id, bi.allocated_amount, bi.notes, a.code, a.name_ar, a.type
            ORDER BY a.code ASC
        ");
        $itemsStmt->execute([$budget->start_date, $budget->end_date, $id]);
        $analysisItems = $itemsStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

        ob_start(); include $this->basePath . '/resources/views/accounting/budgets/show.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_budgets_delete');

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $this->db->prepare("DELETE FROM budgets WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = __('تم حذف الموازنة بنجاح.', 'Budget deleted successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse('/ERP/accounting/budgets');
    }
}