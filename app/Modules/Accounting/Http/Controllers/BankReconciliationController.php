<?php
// Path: app/Modules/Accounting/Http/Controllers/BankReconciliationController.php

namespace App\Modules\Accounting\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use Core\Security\Auth;
use PDO;
use Exception;
use Throwable;

class BankReconciliationController extends Controller
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
        if (preg_match('#/bank-reconciliation/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    private function generateRecNumber(): string
    {
        $stmt = $this->db->query("SELECT reconciliation_number FROM bank_reconciliations ORDER BY id DESC LIMIT 1");
        $last = $stmt->fetchColumn();
        if (!$last) return 'BR-' . date('ym') . '0001';
        $num = (int)substr($last, 7) + 1;
        return 'BR-' . date('ym') . str_pad((string)$num, 4, '0', STR_PAD_LEFT);
    }

    public function index(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_reconciliation_view');

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/bank-reconciliation/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/bank-reconciliation/(\d+)/finalize#', $uri, $m)) return $this->finalize($request, $response, (int)$m[1]);
        if (preg_match('#/bank-reconciliation/(\d+)/match#', $uri, $m)) return $this->matchItems($request, $response, (int)$m[1]);
        if (preg_match('#/bank-reconciliation/(\d+)/auto-match#', $uri, $m)) return $this->autoMatch($request, $response, (int)$m[1]);
        if (preg_match('#/bank-reconciliation/(\d+)/add-fee#', $uri, $m)) return $this->addFee($request, $response, (int)$m[1]);
        if (preg_match('#/bank-reconciliation/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $accountId = trim($_GET['account_id'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $where = ["1=1"];
            $params = [];

            if ($search !== '') {
                $where[] = "(br.reconciliation_number LIKE ? OR br.notes LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like]);
            }
            if ($accountId !== '') {
                $where[] = "br.account_id = ?";
                $params[] = (int)$accountId;
            }
            if ($statusFilter !== '') {
                $where[] = "br.status = ?";
                $params[] = $statusFilter;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM bank_reconciliations br $whereSql");
            $countStmt->execute($params);
            $totalPages = max(1, ceil($countStmt->fetchColumn() / $limit));

            $stmt = $this->db->prepare("
                SELECT br.*, a.code as acc_code, a.name_ar as acc_name
                FROM bank_reconciliations br
                JOIN accounts a ON br.account_id = a.id
                $whereSql
                ORDER BY br.statement_date DESC, br.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $reconciliations = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $bankAccounts = $this->db->query("SELECT id, code, name_ar FROM accounts WHERE is_active = 1 AND is_parent = 0 AND (name_ar LIKE '%بنك%' OR name_ar LIKE '%Bank%' OR code LIKE '1102%') ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total_recs,
                    SUM(IF(status='reconciled', 1, 0)) as reconciled_count,
                    SUM(IF(status='draft', 1, 0)) as draft_count,
                    SUM(statement_balance) as total_statement_val
                FROM bank_reconciliations
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $reconciliations = []; $bankAccounts = [];
            $stats = (object)['total_recs'=>0, 'reconciled_count'=>0, 'draft_count'=>0, 'total_statement_val'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;
        ob_start(); include $this->basePath . '/resources/views/accounting/bank_reconciliation/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_reconciliation_create');

        $bankAccounts = $this->db->query("SELECT id, code, name_ar, current_balance FROM accounts WHERE is_active = 1 AND is_parent = 0 ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);

        ob_start(); include $this->basePath . '/resources/views/accounting/bank_reconciliation/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_reconciliation_create');

        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $recNum = $this->generateRecNumber();
            $accountId = (int)($data['account_id'] ?? 0);
            $stmtDate = $data['statement_date'] ?? date('Y-m-d');
            $stmtBal = (float)($data['statement_balance'] ?? 0);

            $accStmt = $this->db->prepare("SELECT current_balance FROM accounts WHERE id = ?");
            $accStmt->execute([$accountId]);
            $bookBal = (float)$accStmt->fetchColumn();

            $diff = $stmtBal - $bookBal;

            $stmt = $this->db->prepare("
                INSERT INTO bank_reconciliations 
                (reconciliation_number, account_id, statement_date, statement_balance, book_balance, difference, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, 'draft', ?)
            ");
            $stmt->execute([$recNum, $accountId, $stmtDate, $stmtBal, $bookBal, $diff, trim($data['notes'] ?? '')]);
            $recId = $this->db->lastInsertId();

            $_SESSION['flash_msg'] = __('تم إنشاء مذكرة التسوية البنكية برقم ', 'Bank reconciliation created with number: ') . $recNum;
            return new RedirectResponse("/ERP/accounting/bank-reconciliation/{$recId}");
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/bank-reconciliation/create');
        }
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_reconciliation_view');

        $id = $this->resolveId($id);

        $stmt = $this->db->prepare("
            SELECT br.*, a.code as acc_code, a.name_ar as acc_name 
            FROM bank_reconciliations br 
            JOIN accounts a ON br.account_id = a.id 
            WHERE br.id = ?
        ");
        $stmt->execute([$id]);
        $rec = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$rec) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = __('مذكرة التسوية البنكية غير موجودة.', 'Bank reconciliation not found.');
            return new RedirectResponse('/ERP/accounting/bank-reconciliation');
        }

        // الحركات المرحّلة الخاصة بالحساب البنكي
        $txStmt = $this->db->prepare("
            SELECT ji.*, je.entry_number, je.entry_date, je.description as entry_desc,
                   COALESCE(bri.is_cleared, 0) as is_cleared
            FROM journal_entry_items ji
            JOIN journal_entries je ON ji.journal_entry_id = je.id
            LEFT JOIN bank_reconciliation_items bri ON bri.journal_entry_item_id = ji.id AND bri.reconciliation_id = ?
            WHERE ji.account_id = ? AND je.status = 'posted' AND je.entry_date <= ?
            ORDER BY je.entry_date ASC, ji.id ASC
        ");
        $txStmt->execute([$id, $rec->account_id, $rec->statement_date]);
        $transactions = $txStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

        // حسابات العمولات البنكية لاستخدامها في المودال المباشر
        $expenseAccounts = $this->db->query("SELECT id, code, name_ar FROM accounts WHERE is_active = 1 AND is_parent = 0 AND type = 'expense' ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);

        ob_start(); include $this->basePath . '/resources/views/accounting/bank_reconciliation/show.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function matchItems(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_reconciliation_process');

        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $this->db->beginTransaction();

            $clearedItemIds = $data['cleared_items'] ?? [];

            $this->db->prepare("DELETE FROM bank_reconciliation_items WHERE reconciliation_id = ?")->execute([$id]);

            $clearedDebits = 0; $clearedCredits = 0;

            if (!empty($clearedItemIds)) {
                $insStmt = $this->db->prepare("INSERT INTO bank_reconciliation_items (reconciliation_id, journal_entry_item_id, is_cleared) VALUES (?, ?, 1)");
                $amtStmt = $this->db->prepare("SELECT debit, credit FROM journal_entry_items WHERE id = ?");

                foreach ($clearedItemIds as $itemId) {
                    $insStmt->execute([$id, (int)$itemId]);
                    $amtStmt->execute([(int)$itemId]);
                    $row = $amtStmt->fetch(PDO::FETCH_OBJ);
                    if ($row) {
                        $clearedDebits += (float)$row->debit;
                        $clearedCredits += (float)$row->credit;
                    }
                }
            }

            // حساب الإيداعات والسحوبات غير المطابقة (المعلقة)
            $rec = $this->db->query("SELECT * FROM bank_reconciliations WHERE id = $id")->fetch(PDO::FETCH_OBJ);

            $unmatchedStmt = $this->db->prepare("
                SELECT SUM(ji.debit) as un_debit, SUM(ji.credit) as un_credit
                FROM journal_entry_items ji
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                LEFT JOIN bank_reconciliation_items bri ON bri.journal_entry_item_id = ji.id AND bri.reconciliation_id = ?
                WHERE ji.account_id = ? AND je.status = 'posted' AND je.entry_date <= ? AND (bri.is_cleared IS NULL OR bri.is_cleared = 0)
            ");
            $unmatchedStmt->execute([$id, $rec->account_id, $rec->statement_date]);
            $unmatched = $unmatchedStmt->fetch(PDO::FETCH_OBJ);

            $outstandingDeposits = (float)($unmatched->un_debit ?? 0);
            $outstandingPayments = (float)($unmatched->un_credit ?? 0);

            // المعادلة المحاسبية المزدوجة:
            $adjustedBankBalance = $rec->statement_balance + $outstandingDeposits - $outstandingPayments;
            $adjustedBookBalance = $rec->book_balance;

            $difference = $adjustedBankBalance - $adjustedBookBalance;

            $updStmt = $this->db->prepare("
                UPDATE bank_reconciliations 
                SET cleared_debits=?, cleared_credits=?, outstanding_deposits=?, outstanding_payments=?,
                    adjusted_bank_balance=?, adjusted_book_balance=?, difference=?
                WHERE id=?
            ");
            $updStmt->execute([
                $clearedDebits, $clearedCredits, $outstandingDeposits, $outstandingPayments,
                $adjustedBankBalance, $adjustedBookBalance, $difference, $id
            ]);

            $this->db->commit();
            $_SESSION['flash_msg'] = __('تم تحديث المطابقة وإعادة حساب كشف التسوية بنجاح.', 'Reconciliation updated successfully.');
        } catch (Throwable $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse("/ERP/accounting/bank-reconciliation/{$id}");
    }

    public function autoMatch(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_reconciliation_process');

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $rec = $this->db->query("SELECT * FROM bank_reconciliations WHERE id = $id")->fetch(PDO::FETCH_OBJ);
            if (!$rec || $rec->status !== 'draft') throw new Exception(__('لا يمكن مطابقة مذكرة مغلقة.', 'Cannot match a closed reconciliation.'));

            // مطابقة آلية لجميع الحركات المسجلة بالدفاتر
            $txs = $this->db->query("
                SELECT ji.id FROM journal_entry_items ji
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                WHERE ji.account_id = {$rec->account_id} AND je.status = 'posted' AND je.entry_date <= '{$rec->statement_date}'
            ")->fetchAll(PDO::FETCH_COLUMN);

            $_POST['cleared_items'] = $txs;
            return $this->matchItems($request, $response, $id);
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/accounting/bank-reconciliation/{$id}");
        }
    }

    public function addFee(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_reconciliation_process');

        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $this->db->beginTransaction();

            $rec = $this->db->query("SELECT * FROM bank_reconciliations WHERE id = $id")->fetch(PDO::FETCH_OBJ);
            $amount = (float)($data['fee_amount'] ?? 0);
            $expAccountId = (int)($data['expense_account_id'] ?? 0);

            if ($amount <= 0 || !$expAccountId) throw new Exception(__('بيانات المصروف البنكي غير صالحة.', 'Invalid bank fee data.'));

            // 1. توليد قيد مصروفات بنكية تلقائي
            $entryNum = 'BNK-FEE-' . date('ymd') . '-' . rand(10, 99);
            $desc = $data['fee_description'] ?: "مصاريف وعمولات بنكية - كشف تسوية {$rec->reconciliation_number}";

            $jeStmt = $this->db->prepare("INSERT INTO journal_entries (entry_number, entry_date, description, total_amount, status) VALUES (?, ?, ?, ?, 'posted')");
            $jeStmt->execute([$entryNum, $rec->statement_date, $desc, $amount]);
            $jeId = $this->db->lastInsertId();

            // مدين: حساب المصروف البنكي
            $iStmt = $this->db->prepare("INSERT INTO journal_entry_items (journal_entry_id, account_id, description, debit, credit) VALUES (?, ?, ?, ?, ?)");
            $iStmt->execute([$jeId, $expAccountId, $desc, $amount, 0]);

            // دائن: الحساب البنكي
            $iStmt->execute([$jeId, $rec->account_id, $desc, 0, $amount]);
            $bankItemId = $this->db->lastInsertId();

            // خصم الرصيد الدفتري للحساب البنكي
            $this->db->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?")->execute([$amount, $rec->account_id]);

            // إضافة الحركة مباشرة كحركة مطابقة
            $this->db->prepare("INSERT INTO bank_reconciliation_items (reconciliation_id, journal_entry_item_id, is_cleared) VALUES (?, ?, 1)")->execute([$id, $bankItemId]);

            // تحديث رصيد الدفاتر بالمذكرة
            $this->db->prepare("UPDATE bank_reconciliations SET book_balance = book_balance - ? WHERE id = ?")->execute([$amount, $id]);

            $this->db->commit();
            $_SESSION['flash_msg'] = __('تم إثبات القيد الآلي للمصروف البنكي بمبلغ ', 'Bank fee auto-entry posted with amount: ') . number_format($amount, 2) . " " . __('ومطابقته بنجاح.', 'and reconciled successfully.');
        } catch (Throwable $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse("/ERP/accounting/bank-reconciliation/{$id}");
    }

    public function finalize(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_reconciliation_process');

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $rec = $this->db->query("SELECT difference FROM bank_reconciliations WHERE id = $id")->fetch(PDO::FETCH_OBJ);
            if (abs((float)$rec->difference) > 0.01) {
                throw new Exception(__('لا يمكن اعتماد التسوية البنكية بوجود فرق مالي غير صفري. يرجى المراجعة.', 'Cannot finalize with non-zero difference. Please review.'));
            }

            $this->db->prepare("UPDATE bank_reconciliations SET status = 'reconciled' WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = __('تم اعتماد وإغلاق مذكرة التسوية البنكية بنجاح.', 'Bank reconciliation finalized successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse("/ERP/accounting/bank-reconciliation/{$id}");
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_reconciliation_delete');

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $rec = $this->db->query("SELECT status FROM bank_reconciliations WHERE id = $id")->fetch(PDO::FETCH_OBJ);
            if ($rec && $rec->status === 'reconciled') {
                throw new Exception(__('لا يمكن حذف تسوية بنكية معتمدة ومغلقة.', 'Cannot delete a finalized reconciliation.'));
            }

            $this->db->prepare("DELETE FROM bank_reconciliations WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = __('تم حذف مذكرة التسوية بنجاح.', 'Reconciliation deleted successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse('/ERP/accounting/bank-reconciliation');
    }
}