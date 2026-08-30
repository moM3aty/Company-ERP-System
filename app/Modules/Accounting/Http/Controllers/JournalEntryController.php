<?php
// Path: app/Modules/Accounting/Http/Controllers/JournalEntryController.php

namespace App\Modules\Accounting\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use Core\Security\Auth;
use PDO;
use Exception;
use Throwable;

class JournalEntryController extends Controller
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
        if (preg_match('#/journal-entries/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    private function generateEntryNumber(): string
    {
        $companyId = current_company() ?? 1;
        $stmt = $this->db->prepare("SELECT entry_number FROM journal_entries WHERE company_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$companyId]);
        $last = $stmt->fetchColumn();
        if (!$last) return 'JE-' . date('ym') . '0001';
        $num = (int)substr($last, 7) + 1;
        return 'JE-' . date('ym') . str_pad((string)$num, 4, '0', STR_PAD_LEFT);
    }

    public function index(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_journals_view');

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        if (preg_match('#/journal-entries/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/journal-entries/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/journal-entries/(\d+)/post#', $uri, $m)) return $this->post($request, $response, (int)$m[1]);
        if (preg_match('#/journal-entries/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/journal-entries/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;
        $companyId = current_company() ?? 1;

        try {
            $where = ["company_id = ?"];
            $params = [$companyId];

            if ($search !== '') {
                $where[] = "(entry_number LIKE ? OR description LIKE ? OR reference_number LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like]);
            }
            if ($statusFilter !== '') {
                $where[] = "status = ?";
                $params[] = $statusFilter;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM journal_entries $whereSql");
            $countStmt->execute($params);
            $totalPages = max(1, ceil($countStmt->fetchColumn() / $limit));

            $stmt = $this->db->prepare("SELECT * FROM journal_entries $whereSql ORDER BY entry_date DESC, id DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            $entries = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $statsStmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_entries,
                    SUM(IF(status='draft', 1, 0)) as drafts,
                    SUM(IF(status='posted', 1, 0)) as posted,
                    SUM(total_amount) as total_value
                FROM journal_entries WHERE company_id = ?
            ");
            $statsStmt->execute([$companyId]);
            $stats = $statsStmt->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $entries = [];
            $stats = (object)['total_entries'=>0, 'drafts'=>0, 'posted'=>0, 'total_value'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;
        ob_start(); include $this->basePath . '/resources/views/accounting/journals/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_journals_create');

        $entry = null; $items = [];
        $companyId = current_company() ?? 1;

        $accounts = $this->db->prepare("SELECT id, code, name_ar FROM accounts WHERE is_active = 1 AND is_parent = 0 AND company_id = ? ORDER BY code ASC");
        $accounts->execute([$companyId]);
        $accounts = $accounts->fetchAll(PDO::FETCH_OBJ);

        $costCenters = $this->db->prepare("SELECT id, code, name_ar FROM cost_centers WHERE is_active = 1 AND is_parent = 0 AND company_id = ? ORDER BY code ASC");
        $costCenters->execute([$companyId]);
        $costCenters = $costCenters->fetchAll(PDO::FETCH_OBJ);

        ob_start(); include $this->basePath . '/resources/views/accounting/journals/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_journals_create');

        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        try {
            $this->db->beginTransaction();

            $entryNum = $this->generateEntryNumber();
            $date = $data['entry_date'] ?? date('Y-m-d');
            $desc = $data['description'] ?? __('قيد تسوية', 'Journal Entry');
            $ref = $data['reference_number'] ?? null;

            $accountIds = $data['account_id'] ?? [];
            $costCenterIds = $data['cost_center_id'] ?? [];
            $debits = $data['debit'] ?? [];
            $credits = $data['credit'] ?? [];
            $itemDescs = $data['item_description'] ?? [];

            $totalDebit = 0; $totalCredit = 0;
            
            foreach ($accountIds as $k => $accId) {
                if (empty($accId)) continue;
                $totalDebit += (float)($debits[$k] ?? 0);
                $totalCredit += (float)($credits[$k] ?? 0);
            }

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new Exception(__('القيد غير متزن! إجمالي المدين يجب أن يساوي إجمالي الدائن.', 'Unbalanced Entry! Debit must equal Credit.'));
            }
            if ($totalDebit <= 0) throw new Exception(__('يجب إدخال قيم مالية أكبر من الصفر.', 'Amounts must be greater than zero.'));

            $stmt = $this->db->prepare("INSERT INTO journal_entries (company_id, entry_number, entry_date, reference_number, description, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, 'draft')");
            $stmt->execute([$companyId, $entryNum, $date, $ref, $desc, $totalDebit]);
            $entryId = $this->db->lastInsertId();

            $iStmt = $this->db->prepare("INSERT INTO journal_entry_items (journal_entry_id, account_id, cost_center_id, description, debit, credit) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($accountIds as $k => $accId) {
                if (empty($accId)) continue;
                $d = (float)($debits[$k] ?? 0);
                $c = (float)($credits[$k] ?? 0);
                if ($d == 0 && $c == 0) continue;

                $ccId = !empty($costCenterIds[$k]) ? (int)$costCenterIds[$k] : null;
                $iStmt->execute([$entryId, $accId, $ccId, $itemDescs[$k] ?? '', $d, $c]);
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = __('تم حفظ القيد كمسودة برقم ', 'Entry saved as draft with Number: ') . $entryNum;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/journal-entries/create');
        }
        return new RedirectResponse('/ERP/accounting/journal-entries');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_journals_edit');

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM journal_entries WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $entry = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$entry) throw new Exception(__('القيد غير موجود.', 'Journal Entry not found.'));
            if ($entry->status !== 'draft') throw new Exception(__('لا يمكن تعديل قيد مُرحّل. يجب عمل قيد عكسي بدلاً من ذلك.', 'Cannot edit a posted entry. Create a reverse entry instead.'));

            $items = $this->db->query("SELECT * FROM journal_entry_items WHERE journal_entry_id = $id")->fetchAll(PDO::FETCH_OBJ);
            
            $accounts = $this->db->prepare("SELECT id, code, name_ar FROM accounts WHERE is_active = 1 AND is_parent = 0 AND company_id = ? ORDER BY code ASC");
            $accounts->execute([$companyId]);
            $accounts = $accounts->fetchAll(PDO::FETCH_OBJ);

            $costCenters = $this->db->prepare("SELECT id, code, name_ar FROM cost_centers WHERE is_active = 1 AND is_parent = 0 AND company_id = ? ORDER BY code ASC");
            $costCenters->execute([$companyId]);
            $costCenters = $costCenters->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/journal-entries');
        }

        ob_start(); include $this->basePath . '/resources/views/accounting/journals/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_journals_edit');

        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        try {
            $stmt = $this->db->prepare("SELECT status FROM journal_entries WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $entry = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$entry) throw new Exception(__('القيد غير موجود.', 'Entry not found.'));
            if ($entry->status !== 'draft') throw new Exception(__('لا يمكن تعديل قيد مُرحّل.', 'Cannot edit a posted entry.'));

            $this->db->beginTransaction();

            $totalDebit = 0; $totalCredit = 0;
            $accountIds = $data['account_id'] ?? [];
            $costCenterIds = $data['cost_center_id'] ?? [];

            foreach ($accountIds as $k => $accId) {
                if(empty($accId)) continue;
                $totalDebit += (float)($data['debit'][$k] ?? 0);
                $totalCredit += (float)($data['credit'][$k] ?? 0);
            }

            if (round($totalDebit, 2) !== round($totalCredit, 2)) throw new Exception(__('القيد غير متزن!', 'Unbalanced Entry!'));

            $updateStmt = $this->db->prepare("UPDATE journal_entries SET entry_date=?, reference_number=?, description=?, total_amount=? WHERE id=? AND company_id=?");
            $updateStmt->execute([$data['entry_date'], $data['reference_number'], $data['description'], $totalDebit, $id, $companyId]);

            $this->db->prepare("DELETE FROM journal_entry_items WHERE journal_entry_id=?")->execute([$id]);
            $iStmt = $this->db->prepare("INSERT INTO journal_entry_items (journal_entry_id, account_id, cost_center_id, description, debit, credit) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($accountIds as $k => $accId) {
                if (empty($accId)) continue;
                $d = (float)($data['debit'][$k] ?? 0); $c = (float)($data['credit'][$k] ?? 0);
                if ($d == 0 && $c == 0) continue;

                $ccId = !empty($costCenterIds[$k]) ? (int)$costCenterIds[$k] : null;
                $iStmt->execute([$id, $accId, $ccId, $data['item_description'][$k] ?? '', $d, $c]);
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = __('تم تعديل القيد بنجاح.', 'Journal Entry updated successfully.');
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/accounting/journal-entries/{$id}/edit");
        }
        return new RedirectResponse('/ERP/accounting/journal-entries');
    }

    public function post(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_journals_process');

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        try {
            $stmt = $this->db->prepare("SELECT status FROM journal_entries WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $entry = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$entry || $entry->status !== 'draft') throw new Exception(__('القيد غير صالح للترحيل.', 'Entry is not valid for posting.'));

            $this->db->beginTransaction();

            $items = $this->db->query("SELECT account_id, debit, credit FROM journal_entry_items WHERE journal_entry_id = $id")->fetchAll(PDO::FETCH_OBJ);
            $accStmt = $this->db->prepare("SELECT type FROM accounts WHERE id = ? AND company_id = ?");
            $updStmt = $this->db->prepare("UPDATE accounts SET current_balance = current_balance + ? WHERE id = ? AND company_id = ?");

            foreach ($items as $item) {
                $accStmt->execute([$item->account_id, $companyId]);
                $type = $accStmt->fetchColumn();
                
                $amount = 0;
                if (in_array($type, ['asset', 'expense'])) {
                    $amount = $item->debit - $item->credit;
                } else {
                    $amount = $item->credit - $item->debit;
                }
                
                $updStmt->execute([$amount, $item->account_id, $companyId]);
            }

            $this->db->prepare("UPDATE journal_entries SET status = 'posted' WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            
            $this->db->commit();
            $_SESSION['flash_msg'] = __('تم ترحيل القيد والتأثير على الحسابات بنجاح.', 'Entry posted successfully.');
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse('/ERP/accounting/journal-entries');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_journals_delete');

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        try {
            $stmt = $this->db->prepare("SELECT status FROM journal_entries WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $entry = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$entry || $entry->status !== 'draft') throw new Exception(__('لا يمكن حذف قيد مرحّل.', 'Cannot delete a posted entry.'));

            $this->db->prepare("DELETE FROM journal_entries WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = __('تم حذف القيد بنجاح.', 'Entry deleted successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse('/ERP/accounting/journal-entries');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_journals_view');

        $id = $this->resolveId($id);
        $companyId = current_company() ?? 1;
        
        $stmt = $this->db->prepare("SELECT * FROM journal_entries WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, $companyId]);
        $entry = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$entry) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = __('القيد غير موجود.', 'Entry not found.');
            return new RedirectResponse('/ERP/accounting/journal-entries');
        }

        $items = $this->db->query("
            SELECT ji.*, a.code as acc_code, a.name_ar as acc_name, cc.name_ar as cc_name 
            FROM journal_entry_items ji 
            JOIN accounts a ON ji.account_id = a.id 
            LEFT JOIN cost_centers cc ON ji.cost_center_id = cc.id
            WHERE ji.journal_entry_id = $id
        ")->fetchAll(PDO::FETCH_OBJ);

        ob_start(); include $this->basePath . '/resources/views/accounting/journals/show.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}