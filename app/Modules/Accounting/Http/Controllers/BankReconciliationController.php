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
        if (preg_match('#/bank-reconciliation/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    private function generateRecNumber(): string
    {
        if (!$this->db) return 'BR-' . date('ym') . '0001';
        try {
            $stmt = $this->db->query("SELECT reconciliation_number FROM bank_reconciliations ORDER BY id DESC LIMIT 1");
            $last = $stmt ? $stmt->fetchColumn() : null;
            if (!$last) return 'BR-' . date('ym') . '0001';
            $num = (int)substr($last, 7) + 1;
            return 'BR-' . date('ym') . str_pad((string)$num, 4, '0', STR_PAD_LEFT);
        } catch (Throwable $e) { return 'BR-' . date('ym') . '0001'; }
    }

    private function hasBranchesSupport(): bool 
    {
        if (!$this->db) return false;
        try {
            $this->db->query("SELECT branch_id FROM bank_reconciliations LIMIT 1");
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
                Auth::enforce('accounting_reconciliation_view');
            }

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
            $branchFilter = trim($_GET['branch_id'] ?? '');
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = 15;
            $offset = ($page - 1) * $limit;

            $tenantCond = $this->getTenantCondition('br');
            $hasBranch = $this->hasBranchesSupport();
            
            // تحويل العملات
            $convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

            $reconciliations = []; $bankAccounts = []; $branches = [];
            $stats = (object)['total_recs'=>0, 'reconciled_count'=>0, 'draft_count'=>0, 'total_statement_val'=>0];
            $totalPages = 1;

            if ($this->db) {
                $companyId = (int)($_SESSION['company_id'] ?? 1);
                if ($hasBranch) {
                    $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
                }

                $where = [$tenantCond];
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
                if ($hasBranch && $branchFilter !== '') {
                    $where[] = "br.branch_id = ?";
                    $params[] = (int)$branchFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM bank_reconciliations br $whereSql");
                $countStmt->execute($params);
                $totalPages = max(1, ceil($countStmt->fetchColumn() / $limit));

                $branchSelect = $hasBranch ? ", b.name_ar as branch_name, b.name_en as branch_name_en" : "";
                $branchJoin   = $hasBranch ? "LEFT JOIN branches b ON br.branch_id = b.id" : "";

                $stmt = $this->db->prepare("
                    SELECT br.*, a.code as acc_code, a.name_ar as acc_name, a.name_en as acc_name_en $branchSelect
                    FROM bank_reconciliations br
                    JOIN accounts a ON br.account_id = a.id
                    $branchJoin
                    $whereSql
                    ORDER BY br.statement_date DESC, br.id DESC LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $reconciliations = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                // تطبيق التحويل على الأرصدة
                foreach ($reconciliations as $r) {
                    $r->statement_balance = $convert($r->statement_balance);
                    $r->book_balance = $convert($r->book_balance);
                }

                $bankAccounts = $this->db->query("SELECT id, code, name_ar, name_en FROM accounts WHERE is_active = 1 AND is_parent = 0 AND company_id = $companyId ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_recs,
                        SUM(IF(status='reconciled', 1, 0)) as reconciled_count,
                        SUM(IF(status='draft', 1, 0)) as draft_count,
                        SUM(statement_balance) as total_statement_val
                    FROM bank_reconciliations br WHERE $tenantCond
                ")->fetch(PDO::FETCH_OBJ);

                if ($statsData) {
                    $stats = $statsData;
                }
            }

            $currentPage = $page;
            
            ob_start(); include $this->basePath . '/resources/views/accounting/bank_reconciliation/index.php';
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
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_reconciliation_create');

            if (session_status() === PHP_SESSION_NONE) session_start();
            $companyId = (int)($_SESSION['company_id'] ?? 1);
            $bankAccounts = []; $branches = [];
            $hasBranch = $this->hasBranchesSupport();

            if ($this->db) {
                if ($hasBranch) {
                    $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
                }
                $bankAccounts = $this->db->query("SELECT id, code, name_ar, name_en, current_balance FROM accounts WHERE is_active = 1 AND is_parent = 0 AND company_id = $companyId ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            }

            ob_start(); include $this->basePath . '/resources/views/accounting/bank_reconciliation/create.php';
            $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
        } catch (Throwable $e) {
            die("<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 Controller Error (create)</h3>" . $e->getMessage() . "<br>Line: " . $e->getLine() . "</div>");
        }
    }

    public function store(Request $request, Response $response): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_reconciliation_create');

            $data = $_POST;
            if (session_status() === PHP_SESSION_NONE) session_start();
            $companyId = (int)($_SESSION['company_id'] ?? 1);
            $sessionBranch = (int)($_SESSION['branch_id'] ?? 0);
            $branchId = ($sessionBranch > 0) ? $sessionBranch : (!empty($data['branch_id']) ? (int)$data['branch_id'] : 0);

            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $recNum = $this->generateRecNumber();
            $accountId = (int)($data['account_id'] ?? 0);
            $stmtDate = $data['statement_date'] ?? date('Y-m-d');
            $stmtBal = (float)($data['statement_balance'] ?? 0);

            $accStmt = $this->db->prepare("SELECT current_balance FROM accounts WHERE id = ?");
            $accStmt->execute([$accountId]);
            $bookBal = (float)$accStmt->fetchColumn();

            $diff = $stmtBal - $bookBal;

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO bank_reconciliations 
                    (company_id, branch_id, reconciliation_number, account_id, statement_date, statement_balance, book_balance, difference, status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)
                ");
                $stmt->execute([$companyId, $branchId, $recNum, $accountId, $stmtDate, $stmtBal, $bookBal, $diff, trim($data['notes'] ?? '')]);
            } catch (\PDOException $ex) {
                $stmt = $this->db->prepare("
                    INSERT INTO bank_reconciliations 
                    (company_id, reconciliation_number, account_id, statement_date, statement_balance, book_balance, difference, status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', ?)
                ");
                $stmt->execute([$companyId, $recNum, $accountId, $stmtDate, $stmtBal, $bookBal, $diff, trim($data['notes'] ?? '')]);
            }

            $recId = $this->db->lastInsertId();
            $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
            $_SESSION['flash_msg'] = $isRtl ? "تم إنشاء مذكرة التسوية برقم " . $recNum : "Reconciliation created: " . $recNum;
            return new RedirectResponse("/ERP/accounting/bank-reconciliation/{$recId}");

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/bank-reconciliation/create');
        }
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_reconciliation_view');

            $id = $this->resolveId($id);
            if (session_status() === PHP_SESSION_NONE) session_start();
            $companyId = (int)($_SESSION['company_id'] ?? 1);
            $hasBranch = $this->hasBranchesSupport();
            $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
            
            // دالة التحويل
            $convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

            if (!$this->db) throw new Exception("اتصال قاعدة البيانات مفقود.");

            $branchSelect = $hasBranch ? ", b.name_ar as branch_name, b.name_en as branch_name_en" : "";
            $branchJoin   = $hasBranch ? "LEFT JOIN branches b ON br.branch_id = b.id" : "";

            $stmt = $this->db->prepare("
                SELECT br.*, a.code as acc_code, a.name_ar as acc_name, a.name_en as acc_name_en $branchSelect
                FROM bank_reconciliations br 
                JOIN accounts a ON br.account_id = a.id 
                $branchJoin
                WHERE br.id = ? AND br.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $rec = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$rec) {
                $_SESSION['flash_err'] = $isRtl ? 'مذكرة التسوية البنكية غير موجودة.' : 'Reconciliation not found.';
                return new RedirectResponse('/ERP/accounting/bank-reconciliation');
            }

            // تطبيق التحويل على بيانات التسوية
            $rec->statement_balance = $convert($rec->statement_balance ?? 0);
            $rec->book_balance = $convert($rec->book_balance ?? 0);
            $rec->outstanding_deposits = $convert($rec->outstanding_deposits ?? 0);
            $rec->outstanding_payments = $convert($rec->outstanding_payments ?? 0);
            $rec->adjusted_bank_balance = $convert($rec->adjusted_bank_balance ?? 0);
            $rec->adjusted_book_balance = $convert($rec->adjusted_book_balance ?? 0);
            $rec->difference = $convert($rec->difference ?? 0);

            $txStmt = $this->db->prepare("
                SELECT ji.*, je.entry_number, je.entry_date, je.description as entry_desc,
                       COALESCE(bri.is_cleared, 0) as is_cleared
                FROM journal_entry_items ji
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                LEFT JOIN bank_reconciliation_items bri ON bri.journal_entry_item_id = ji.id AND bri.reconciliation_id = ?
                WHERE ji.account_id = ? AND je.status = 'posted' AND je.entry_date <= ? AND je.company_id = ?
                ORDER BY je.entry_date ASC, ji.id ASC
            ");
            $txStmt->execute([$id, $rec->account_id, $rec->statement_date, $companyId]);
            $transactions = $txStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            // تطبيق التحويل على القيود
            foreach ($transactions as $tx) {
                $tx->debit = $convert($tx->debit ?? 0);
                $tx->credit = $convert($tx->credit ?? 0);
            }

            $expenseAccounts = $this->db->query("SELECT id, code, name_ar, name_en FROM accounts WHERE is_active = 1 AND is_parent = 0 AND type = 'expense' AND company_id = $companyId ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

            ob_start(); include $this->basePath . '/resources/views/accounting/bank_reconciliation/show.php';
            $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');

        } catch (Throwable $e) {
            die("<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 Controller Error (show)</h3>" . $e->getMessage() . "<br>Line: " . $e->getLine() . "</div>");
        }
    }

    public function matchItems(Request $request, Response $response, $id = null): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_reconciliation_process');

            $id = $this->resolveId($id);
            $data = $_POST;
            if (session_status() === PHP_SESSION_NONE) session_start();
            $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

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
            $_SESSION['flash_msg'] = $isRtl ? 'تم تحديث المطابقة وإعادة حساب كشف التسوية بنجاح.' : 'Reconciliation matched successfully.';
            return new RedirectResponse("/ERP/accounting/bank-reconciliation/{$id}");

        } catch (Throwable $e) {
            if ($this->db && $this->db->inTransaction()) $this->db->rollBack();
            die("<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 Controller Error (match)</h3>" . $e->getMessage() . "<br>Line: " . $e->getLine() . "</div>");
        }
    }

    public function autoMatch(Request $request, Response $response, $id = null): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_reconciliation_process');

            $id = $this->resolveId($id);
            if (session_status() === PHP_SESSION_NONE) session_start();
            $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

            $rec = $this->db->query("SELECT * FROM bank_reconciliations WHERE id = $id")->fetch(PDO::FETCH_OBJ);
            if (!$rec || $rec->status !== 'draft') throw new Exception($isRtl ? 'لا يمكن مطابقة مذكرة مغلقة.' : 'Cannot match a closed reconciliation.');

            $txs = $this->db->query("
                SELECT ji.id FROM journal_entry_items ji
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                WHERE ji.account_id = {$rec->account_id} AND je.status = 'posted' AND je.entry_date <= '{$rec->statement_date}'
            ")->fetchAll(PDO::FETCH_COLUMN);

            $_POST['cleared_items'] = $txs;
            return $this->matchItems($request, $response, $id);

        } catch (Throwable $e) {
            die("<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 Controller Error (autoMatch)</h3>" . $e->getMessage() . "<br>Line: " . $e->getLine() . "</div>");
        }
    }

    public function addFee(Request $request, Response $response, $id = null): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_reconciliation_process');

            $id = $this->resolveId($id);
            $data = $_POST;
            if (session_status() === PHP_SESSION_NONE) session_start();
            $companyId = (int)($_SESSION['company_id'] ?? 1);
            $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

            $this->db->beginTransaction();

            $rec = $this->db->query("SELECT * FROM bank_reconciliations WHERE id = $id")->fetch(PDO::FETCH_OBJ);
            $amount = (float)($data['fee_amount'] ?? 0);
            $expAccountId = (int)($data['expense_account_id'] ?? 0);

            if ($amount <= 0 || !$expAccountId) throw new Exception($isRtl ? 'بيانات المصروف البنكي غير صالحة.' : 'Invalid bank fee data.');

            $entryNum = 'BNK-FEE-' . date('ymd') . '-' . rand(10, 99);
            $desc = $data['fee_description'] ?: ($isRtl ? "مصاريف وعمولات بنكية - كشف تسوية {$rec->reconciliation_number}" : "Bank Fees - Recon {$rec->reconciliation_number}");

            try {
                $jeStmt = $this->db->prepare("INSERT INTO journal_entries (company_id, branch_id, entry_number, entry_date, description, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, 'posted')");
                $jeStmt->execute([$companyId, $rec->branch_id ?? 0, $entryNum, $rec->statement_date, $desc, $amount]);
            } catch (\PDOException $e) {
                $jeStmt = $this->db->prepare("INSERT INTO journal_entries (company_id, entry_number, entry_date, description, total_amount, status) VALUES (?, ?, ?, ?, 'posted')");
                $jeStmt->execute([$companyId, $entryNum, $rec->statement_date, $desc, $amount]);
            }
            
            $jeId = $this->db->lastInsertId();

            $iStmt = $this->db->prepare("INSERT INTO journal_entry_items (journal_entry_id, account_id, description, debit, credit) VALUES (?, ?, ?, ?, ?)");
            $iStmt->execute([$jeId, $expAccountId, $desc, $amount, 0]);
            $iStmt->execute([$jeId, $rec->account_id, $desc, 0, $amount]);
            $bankItemId = $this->db->lastInsertId();

            $this->db->prepare("UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?")->execute([$amount, $rec->account_id]);
            $this->db->prepare("INSERT INTO bank_reconciliation_items (reconciliation_id, journal_entry_item_id, is_cleared) VALUES (?, ?, 1)")->execute([$id, $bankItemId]);
            $this->db->prepare("UPDATE bank_reconciliations SET book_balance = book_balance - ? WHERE id = ?")->execute([$amount, $id]);

            $this->db->commit();
            $_SESSION['flash_msg'] = $isRtl ? 'تم إثبات القيد الآلي للمصروف البنكي بنجاح.' : 'Bank fee auto-entry posted successfully.';
            return new RedirectResponse("/ERP/accounting/bank-reconciliation/{$id}");

        } catch (Throwable $e) {
            if ($this->db && $this->db->inTransaction()) $this->db->rollBack();
            die("<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 Controller Error (addFee)</h3>" . $e->getMessage() . "<br>Line: " . $e->getLine() . "</div>");
        }
    }

    public function finalize(Request $request, Response $response, $id = null): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_reconciliation_process');

            $id = $this->resolveId($id);
            if (session_status() === PHP_SESSION_NONE) session_start();
            $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

            $rec = $this->db->query("SELECT difference FROM bank_reconciliations WHERE id = $id")->fetch(PDO::FETCH_OBJ);
            if (abs((float)$rec->difference) > 0.01) {
                throw new Exception($isRtl ? 'لا يمكن اعتماد التسوية البنكية بوجود فرق مالي غير صفري.' : 'Cannot finalize with non-zero difference.');
            }

            $this->db->prepare("UPDATE bank_reconciliations SET status = 'reconciled' WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = $isRtl ? 'تم اعتماد وإغلاق مذكرة التسوية البنكية بنجاح.' : 'Reconciliation finalized successfully.';
            return new RedirectResponse("/ERP/accounting/bank-reconciliation/{$id}");

        } catch (Throwable $e) {
            die("<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 Controller Error (finalize)</h3>" . $e->getMessage() . "<br>Line: " . $e->getLine() . "</div>");
        }
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_reconciliation_delete');

            $id = $this->resolveId($id);
            if (session_status() === PHP_SESSION_NONE) session_start();
            $isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';

            $rec = $this->db->query("SELECT status FROM bank_reconciliations WHERE id = $id")->fetch(PDO::FETCH_OBJ);
            if ($rec && $rec->status === 'reconciled') {
                throw new Exception($isRtl ? 'لا يمكن حذف تسوية بنكية معتمدة ومغلقة.' : 'Cannot delete a finalized reconciliation.');
            }

            $this->db->prepare("DELETE FROM bank_reconciliations WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = $isRtl ? 'تم حذف مذكرة التسوية بنجاح.' : 'Reconciliation deleted successfully.';
            return new RedirectResponse('/ERP/accounting/bank-reconciliation');

        } catch (Throwable $e) {
            die("<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 Controller Error (delete)</h3>" . $e->getMessage() . "<br>Line: " . $e->getLine() . "</div>");
        }
    }
}