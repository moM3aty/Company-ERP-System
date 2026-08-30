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
    private PDO $db;

    public function __construct()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
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
        if (preg_match('#/chart-of-accounts/(\d+)#', $uri, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_accounts_view');

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        if (preg_match('#/chart-of-accounts/(\d+)/edit#', $uri, $m)) {
            return $this->edit($request, $response, (int)$m[1]);
        }
        if (preg_match('#/chart-of-accounts/(\d+)/update#', $uri, $m)) {
            return $this->update($request, $response, (int)$m[1]);
        }
        if (preg_match('#/chart-of-accounts/(\d+)/delete#', $uri, $m)) {
            return $this->delete($request, $response, (int)$m[1]);
        }

        $search = trim($_GET['search'] ?? '');
        $typeFilter = trim($_GET['type'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $where = ["1=1"];
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

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM accounts a $whereSql");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT a.*, p.name_ar as parent_name, p.code as parent_code
                FROM accounts a
                LEFT JOIN accounts p ON a.parent_id = p.id
                $whereSql
                ORDER BY a.code ASC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $accounts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total_accounts,
                    SUM(IF(type='asset', 1, 0)) as assets_count,
                    SUM(IF(type='liability', 1, 0)) as liabilities_count,
                    SUM(IF(type='equity', 1, 0)) as equity_count,
                    SUM(IF(type='revenue', 1, 0)) as revenue_count,
                    SUM(IF(type='expense', 1, 0)) as expense_count
                FROM accounts
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $accounts = [];
            $stats = (object)['total_accounts'=>0, 'assets_count'=>0, 'liabilities_count'=>0, 'equity_count'=>0, 'revenue_count'=>0, 'expense_count'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/accounting/accounts/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

   public function create(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_accounts_create');

        $account = null; $parentAccounts = [];
        try {
            $parentAccounts = $this->db->query("
                SELECT id, code, name_ar, type 
                FROM accounts 
                WHERE parent_id IS NULL OR is_parent = 1 
                ORDER BY code ASC
            ")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); 
        include $this->basePath . '/resources/views/accounting/accounts/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_accounts_edit');
        
        $id = $this->resolveId($id);
        
        try {
            if (!$id) throw new Exception(__('معرف الحساب غير صالح.', 'Invalid account ID.'));

            $stmt = $this->db->prepare("SELECT * FROM accounts WHERE id = ?");
            $stmt->execute([$id]);
            $account = $stmt->fetch(PDO::FETCH_OBJ);
            
            if (!$account) throw new Exception(__('الحساب غير موجود.', 'Account not found.'));

            $parentAccounts = $this->db->query("
                SELECT id, code, name_ar, type 
                FROM accounts 
                WHERE (parent_id IS NULL OR is_parent = 1) AND id != {$id} 
                ORDER BY code ASC
            ")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/chart-of-accounts');
        }

        ob_start(); 
        include $this->basePath . '/resources/views/accounting/accounts/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_accounts_create');

        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (empty($data['code']) || empty($data['name_ar']) || empty($data['type'])) {
                throw new Exception(__('يرجى ملء جميع الحقول الإلزامية.', 'Please fill all required fields.'));
            }

            $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
            $level = 1;

            if ($parentId) {
                $pStmt = $this->db->prepare("SELECT account_level FROM accounts WHERE id = ?");
                $pStmt->execute([$parentId]);
                $level = ((int)$pStmt->fetchColumn()) + 1;
            }

            $stmt = $this->db->prepare("
                INSERT INTO accounts (company_id, code, name_ar, name_en, type, parent_id, account_level, is_parent, opening_balance, current_balance, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $companyId = $_SESSION['company_id'] ?? current_company() ?? 1;
            $openingBalance = (float)($data['opening_balance'] ?? 0);
            $isParent = isset($data['is_parent']) ? 1 : 0;
            $isActive = isset($data['is_active']) ? 1 : 0;

            $stmt->execute([
                $companyId, trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''),
                $data['type'], $parentId, $level, $isParent, $openingBalance, $openingBalance, $isActive
            ]);

            $_SESSION['flash_msg'] = __('تم إضافة الحساب المالي بنجاح.', 'Account added successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = __('خطأ: ', 'Error: ') . $e->getMessage();
            return new RedirectResponse('/ERP/accounting/chart-of-accounts/create');
        }

        return new RedirectResponse('/ERP/accounting/chart-of-accounts');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_accounts_view');

        $id = $this->resolveId($id);
        
        try {
            if (!$id) throw new Exception(__('معرف الحساب غير صالح.', 'Invalid account ID.'));

            // 1. جلب بيانات الحساب المالي والحساب الأب
            $stmt = $this->db->prepare("
                SELECT a.*, p.name_ar as parent_name, p.code as parent_code
                FROM accounts a
                LEFT JOIN accounts p ON a.parent_id = p.id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $account = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$account) throw new Exception(__('الحساب المالي غير موجود.', 'Account not found.'));

            // 2. جلب حركة الحساب التفصيلية من قيود اليومية (إن وجدت)
            $movements = [];
            $totals = (object)['total_debit' => 0, 'total_credit' => 0];

            try {
                $mStmt = $this->db->prepare("
                    SELECT ji.*, je.entry_number, je.entry_date, je.description as entry_desc, je.status
                    FROM journal_entry_items ji
                    JOIN journal_entries je ON ji.journal_entry_id = je.id
                    WHERE ji.account_id = ? AND je.status = 'posted'
                    ORDER BY je.entry_date DESC, je.id DESC LIMIT 50
                ");
                $mStmt->execute([$id]);
                $movements = $mStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $tStmt = $this->db->prepare("
                    SELECT COALESCE(SUM(ji.debit), 0) as total_debit, COALESCE(SUM(ji.credit), 0) as total_credit
                    FROM journal_entry_items ji
                    JOIN journal_entries je ON ji.journal_entry_id = je.id
                    WHERE ji.account_id = ? AND je.status = 'posted'
                ");
                $tStmt->execute([$id]);
                $totals = $tStmt->fetch(PDO::FETCH_OBJ);
            } catch (Throwable $e) {}

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/chart-of-accounts');
        }

        ob_start();
        include $this->basePath . '/resources/views/accounting/accounts/show.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_accounts_edit');

        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        try {
            if (!$id) throw new Exception(__('معرف الحساب غير صالح.', 'Invalid account ID.'));

            $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
            $level = 1;

            if ($parentId) {
                $pStmt = $this->db->prepare("SELECT account_level FROM accounts WHERE id = ?");
                $pStmt->execute([$parentId]);
                $level = ((int)$pStmt->fetchColumn()) + 1;
            }

            $stmt = $this->db->prepare("
                UPDATE accounts 
                SET code=?, name_ar=?, name_en=?, type=?, parent_id=?, account_level=?, is_parent=?, is_active=?
                WHERE id=?
            ");

            $isParent = isset($data['is_parent']) ? 1 : 0;
            $isActive = isset($data['is_active']) ? 1 : 0;

            $stmt->execute([
                trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''),
                $data['type'], $parentId, $level, $isParent, $isActive, $id
            ]);

            $_SESSION['flash_msg'] = __('تم تحديث الحساب المالي بنجاح.', 'Account updated successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = __('خطأ: ', 'Error: ') . $e->getMessage();
            return new RedirectResponse("/ERP/accounting/chart-of-accounts/{$id}/edit");
        }

        return new RedirectResponse('/ERP/accounting/chart-of-accounts');
    }

  public function delete(Request $request, Response $response, $id = null): Response
{
    if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_accounts_delete');

    $id = $this->resolveId($id);
    if (session_status() === PHP_SESSION_NONE) session_start();

    try {
        // 1. الفحص: هل الحساب رئيسي وتتبع له حسابات فرعية؟
        $hasChildren = $this->db->prepare("SELECT COUNT(*) FROM accounts WHERE parent_id = ?");
        $hasChildren->execute([$id]);
        if ($hasChildren->fetchColumn() > 0) {
            throw new Exception(__('تعذر الحذف: لا يمكن حذف حساب رئيسي تتبع له حسابات فرعية بجدول الحسابات.', 'Cannot delete a parent account that has sub-accounts.'));
        }

        // 2. الفحص: هل توجد قيود وحركات مالية مرتبطة بهذا الحساب؟
        $hasEntries = $this->db->prepare("SELECT COUNT(*) FROM journal_entry_items WHERE account_id = ?");
        $hasEntries->execute([$id]);
        if ($hasEntries->fetchColumn() > 0) {
            throw new Exception(__('حماية الدفاتر المالية: لا يمكن حذف هذا الحساب لوجود قيود وحركات مالية مرحّلة عليه. يُفضل تعديل الحساب وجعله (غير نشط) بدلاً من الحذف.', 'Cannot delete this account because it has posted journal entries. Disable it instead.'));
        }

        // 3. التنفيذ آمن في حال عدم وجود أي حركات
        $stmt = $this->db->prepare("DELETE FROM accounts WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['flash_msg'] = __('تم حذف الحساب بنجاح لعدم ارتباطه بأي قيود.', 'Account deleted successfully.');
    } catch (Throwable $e) {
        $_SESSION['flash_err'] = $e->getMessage();
    }

    return new RedirectResponse('/ERP/accounting/chart-of-accounts');
}
}