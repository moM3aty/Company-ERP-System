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
        if (preg_match('#/journal-entries/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    private function hasBranchesSupport(): bool 
    {
        if (!$this->db) return false;
        try {
            $this->db->query("SELECT branch_id FROM journal_entries LIMIT 1");
            $this->db->query("SELECT id FROM branches LIMIT 1");
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function getTenantCondition(string $alias = '', string $table = 'journal_entries'): string 
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

    private function generateEntryNumber(): string
    {
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        try {
            $stmt = $this->db->prepare("SELECT entry_number FROM journal_entries WHERE company_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$companyId]);
            $last = $stmt->fetchColumn();
            if (!$last) return 'JE-' . date('ym') . '0001';
            $num = (int)substr($last, 7) + 1;
            return 'JE-' . date('ym') . str_pad((string)$num, 4, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            return 'JE-' . date('ym') . rand(1000, 9999);
        }
    }

    public function index(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_journals_view');
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/journal-entries/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/journal-entries/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/journal-entries/(\d+)/post#', $uri, $m)) return $this->post($request, $response, (int)$m[1]);
        if (preg_match('#/journal-entries/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $branchFilter = trim($_GET['branch_id'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $tenantCond = $this->getTenantCondition('je', 'journal_entries');
        $hasBranch = $this->hasBranchesSupport();
        
        $convert = function($amt) {
            return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt;
        };

        $entries = []; $branches = []; $dbErrors = [];
        $stats = (object)['total_entries'=>0, 'drafts'=>0, 'posted'=>0, 'total_value'=>0];
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
                    $where[] = "(je.entry_number LIKE ? OR je.description LIKE ? OR je.reference_number LIKE ?)";
                    $like = "%{$search}%";
                    $params = array_merge($params, [$like, $like, $like]);
                }
                if ($statusFilter !== '') {
                    $where[] = "je.status = ?";
                    $params[] = $statusFilter;
                }
                if ($hasBranch && $branchFilter !== '') {
                    $where[] = "je.branch_id = ?";
                    $params[] = (int)$branchFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM journal_entries je $whereSql");
                $countStmt->execute($params);
                $totalPages = max(1, ceil($countStmt->fetchColumn() / $limit));

                $branchSelect = $hasBranch ? ", b.name_ar as branch_name, b.name_en as branch_name_en" : "";
                $branchJoin   = $hasBranch ? "LEFT JOIN branches b ON je.branch_id = b.id" : "";

                $stmt = $this->db->prepare("
                    SELECT je.* $branchSelect
                    FROM journal_entries je
                    $branchJoin
                    $whereSql 
                    ORDER BY je.entry_date DESC, je.id DESC LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $entries = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                foreach($entries as $e) {
                    $e->total_amount = $convert($e->total_amount);
                }

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_entries,
                        SUM(IF(status='draft', 1, 0)) as drafts,
                        SUM(IF(status='posted', 1, 0)) as posted,
                        SUM(total_amount) as total_value
                    FROM journal_entries je WHERE $tenantCond
                ")->fetch(PDO::FETCH_OBJ);

                if ($statsData) {
                    $stats = $statsData;
                    $stats->total_value = $convert($stats->total_value ?? 0);
                }

            } catch (Throwable $e) {
                $dbErrors[] = $e->getMessage();
            }
        }

        $currentPage = $page;
        return $this->renderView('/resources/views/accounting/journals/index.php', compact(
            'entries', 'branches', 'stats', 'totalPages', 'currentPage', 'search', 'statusFilter', 'branchFilter', 'dbErrors'
        ), $response);
    }

    public function create(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_journals_create');
        }

        $entry = null; $items = []; $branches = []; $accounts = []; $costCenters = [];
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

        return $this->renderView('/resources/views/accounting/journals/create.php', compact('entry', 'items', 'branches', 'accounts', 'costCenters'), $response);
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_journals_edit');
        }

        $id = $this->resolveId($id);
        $hasBranch = $this->hasBranchesSupport();
        $entry = null; $items = []; $branches = []; $accounts = []; $costCenters = [];

        try {
            if (!$id || !$this->db) throw new Exception("معرف القيد غير صالح.");
            $companyId = (int)($_SESSION['company_id'] ?? 1);

            $stmt = $this->db->prepare("SELECT * FROM journal_entries WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $entry = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$entry) throw new Exception("القيد المالي غير موجود.");
            if ($entry->status !== 'draft') throw new Exception("لا يمكن تعديل قيد مرحّل.");

            if ($hasBranch) {
                $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
            }
            $accounts = $this->db->query("SELECT id, code, name_ar, name_en FROM accounts WHERE is_active = 1 AND is_parent = 0 AND company_id = $companyId ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $costCenters = $this->db->query("SELECT id, code, name_ar, name_en FROM cost_centers WHERE is_active = 1 AND is_parent = 0 AND company_id = $companyId ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

            $items = $this->db->query("SELECT * FROM journal_entry_items WHERE journal_entry_id = $id")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/journal-entries');
        }

        return $this->renderView('/resources/views/accounting/journals/create.php', compact('entry', 'items', 'branches', 'accounts', 'costCenters'), $response);
    }

    public function store(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_journals_create');
        }

        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $sessionBranch = (int)($_SESSION['branch_id'] ?? 0);
        $branchId = ($sessionBranch > 0) ? $sessionBranch : (!empty($data['branch_id']) ? (int)$data['branch_id'] : 0);

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");
            $this->db->beginTransaction();

            $entryNum = $this->generateEntryNumber();
            $date = $data['entry_date'] ?? date('Y-m-d');
            $desc = $data['description'] ?? 'قيد تسوية';
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
                throw new Exception("القيد غير متزن! إجمالي المدين يجب أن يساوي الدائن.");
            }
            if ($totalDebit <= 0) throw new Exception("يجب إدخال قيم مالية أكبر من الصفر.");

            try {
                $stmt = $this->db->prepare("INSERT INTO journal_entries (company_id, branch_id, entry_number, entry_date, reference_number, description, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'draft')");
                $stmt->execute([$companyId, $branchId, $entryNum, $date, $ref, $desc, $totalDebit]);
            } catch (\PDOException $ex) {
                $stmt = $this->db->prepare("INSERT INTO journal_entries (company_id, entry_number, entry_date, reference_number, description, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, 'draft')");
                $stmt->execute([$companyId, $entryNum, $date, $ref, $desc, $totalDebit]);
            }
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
            $_SESSION['flash_msg'] = "تم حفظ القيد كمسودة برقم " . $entryNum;
        } catch (Throwable $e) {
            if ($this->db && $this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/journal-entries/create');
        }
        return new RedirectResponse('/ERP/accounting/journal-entries');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_journals_edit');
        }

        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $sessionBranch = (int)($_SESSION['branch_id'] ?? 0);
        $branchId = ($sessionBranch > 0) ? $sessionBranch : (!empty($data['branch_id']) ? (int)$data['branch_id'] : 0);

        try {
            if (!$id || !$this->db) throw new Exception("معرف القيد غير صالح.");
            $stmt = $this->db->prepare("SELECT status FROM journal_entries WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $entry = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$entry) throw new Exception("القيد غير موجود.");
            if ($entry->status !== 'draft') throw new Exception("لا يمكن تعديل قيد مُرحّل.");

            $this->db->beginTransaction();

            $totalDebit = 0; $totalCredit = 0;
            $accountIds = $data['account_id'] ?? [];
            $costCenterIds = $data['cost_center_id'] ?? [];

            foreach ($accountIds as $k => $accId) {
                if(empty($accId)) continue;
                $totalDebit += (float)($data['debit'][$k] ?? 0);
                $totalCredit += (float)($data['credit'][$k] ?? 0);
            }

            if (round($totalDebit, 2) !== round($totalCredit, 2)) throw new Exception("القيد غير متزن!");

            try {
                $updateStmt = $this->db->prepare("UPDATE journal_entries SET branch_id=?, entry_date=?, reference_number=?, description=?, total_amount=? WHERE id=? AND company_id=?");
                $updateStmt->execute([$branchId, $data['entry_date'], $data['reference_number'], $data['description'], $totalDebit, $id, $companyId]);
            } catch (\PDOException $ex) {
                $updateStmt = $this->db->prepare("UPDATE journal_entries SET entry_date=?, reference_number=?, description=?, total_amount=? WHERE id=? AND company_id=?");
                $updateStmt->execute([$data['entry_date'], $data['reference_number'], $data['description'], $totalDebit, $id, $companyId]);
            }

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
            $_SESSION['flash_msg'] = "تم تعديل القيد بنجاح.";
        } catch (Throwable $e) {
            if ($this->db && $this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/accounting/journal-entries/{$id}/edit");
        }
        return new RedirectResponse('/ERP/accounting/journal-entries');
    }

    public function post(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_journals_process');
        }

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = (int)($_SESSION['company_id'] ?? 1);

        try {
            if (!$this->db) throw new Exception("Database error.");
            $stmt = $this->db->prepare("SELECT status FROM journal_entries WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $entry = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$entry || $entry->status !== 'draft') throw new Exception("القيد غير صالح للترحيل.");

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
            $_SESSION['flash_msg'] = "تم ترحيل القيد بنجاح.";
        } catch (Throwable $e) {
            if ($this->db && $this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse('/ERP/accounting/journal-entries');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_journals_delete');
        }

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = (int)($_SESSION['company_id'] ?? 1);

        try {
            if (!$this->db) throw new Exception("Database error.");
            $stmt = $this->db->prepare("SELECT status FROM journal_entries WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $entry = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$entry || $entry->status !== 'draft') throw new Exception("لا يمكن حذف قيد مرحّل.");

            $this->db->prepare("DELETE FROM journal_entries WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف القيد بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse('/ERP/accounting/journal-entries');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_journals_view');
        }

        $id = $this->resolveId($id);
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $hasBranch = $this->hasBranchesSupport();
        
        $convert = function($amt) {
            return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt;
        };

        try {
            if (!$this->db) throw new Exception("Database error.");

            $branchSelect = $hasBranch ? ", b.name_ar as branch_name, b.name_en as branch_name_en" : "";
            $branchJoin   = $hasBranch ? "LEFT JOIN branches b ON je.branch_id = b.id" : "";

            $stmt = $this->db->prepare("
                SELECT je.* $branchSelect
                FROM journal_entries je
                $branchJoin
                WHERE je.id = ? AND je.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $entry = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$entry) throw new Exception("القيد غير موجود.");

            $entry->total_amount = $convert($entry->total_amount);

            $items = $this->db->query("
                SELECT ji.*, a.code as acc_code, a.name_ar as acc_name, a.name_en as acc_name_en, cc.name_ar as cc_name 
                FROM journal_entry_items ji 
                JOIN accounts a ON ji.account_id = a.id 
                LEFT JOIN cost_centers cc ON ji.cost_center_id = cc.id
                WHERE ji.journal_entry_id = $id
            ")->fetchAll(PDO::FETCH_OBJ) ?: [];

            foreach ($items as $it) {
                $it->debit = $convert($it->debit);
                $it->credit = $convert($it->credit);
            }

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/journal-entries');
        }

        return $this->renderView('/resources/views/accounting/journals/show.php', compact('entry', 'items'), $response);
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