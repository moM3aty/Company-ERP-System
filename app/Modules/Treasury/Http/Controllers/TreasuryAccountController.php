<?php
// Path: app/Modules/Treasury/Http/Controllers/TreasuryAccountController.php

namespace App\Modules\Treasury\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class TreasuryAccountController extends Controller
{
    private $basePath;
    private $db;

    public function __construct()
    {
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = isset($basePath) ? $basePath : dirname(__DIR__, 4);
        
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            if ($this->db) {
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        }
    }

    private function resolveId($id = null)
    {
        if (!empty($id) && is_numeric($id)) return (int)$id;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/accounts/(\d+)#', $uri, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    private function getCompanyId()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return (int)($_SESSION['company_id'] ?? 1);
    }

    private function getActiveBranchId()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return (int)($_SESSION['branch_id'] ?? 0);
    }

    private function hasBranchColumn($table)
    {
        if (!$this->db) return false;
        try {
            $this->db->query("SELECT branch_id FROM {$table} LIMIT 1");
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function buildBranchCond($tableAlias, $branchId, $tableName)
    {
        if ($branchId <= 0) return "";
        if (!$this->hasBranchColumn($tableName)) return "";
        $col = $tableAlias ? "{$tableAlias}.branch_id" : "branch_id";
        return " AND ({$col} = {$branchId} OR {$col} = 0 OR {$col} IS NULL)";
    }

    public function index(Request $request, Response $response)
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/accounts/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/accounts/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/accounts/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/accounts/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condAcc = $this->buildBranchCond('a', $branchId, 'accounts');

            $where = [
                "a.company_id = ?",
                "a.type = 'asset'",
                "(a.code LIKE '111%' OR a.code LIKE '112%' OR a.name_ar LIKE '%خزينة%' OR a.name_ar LIKE '%بنك%' OR a.name_ar LIKE '%صندوق%')"
            ];
            $params = [$companyId];

            if ($search !== '') {
                $where[] = "(a.code LIKE ? OR a.name_ar LIKE ? OR a.name_en LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like);
            }

            if ($statusFilter !== '') {
                $where[] = "a.is_active = ?";
                $params[] = ($statusFilter === 'active') ? 1 : 0;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM accounts a $whereSql $condAcc");
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCount / $limit));

            $stmt = $this->db->prepare("
                SELECT a.*
                FROM accounts a
                $whereSql $condAcc
                ORDER BY a.code ASC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $accounts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total_accounts,
                    COALESCE(SUM(current_balance), 0) as total_balance,
                    COALESCE(SUM(IF(name_ar LIKE '%بنك%' OR code LIKE '1112%', current_balance, 0)), 0) as bank_balance,
                    COALESCE(SUM(IF(name_ar LIKE '%خزينة%' OR name_ar LIKE '%صندوق%' OR code LIKE '1111%', current_balance, 0)), 0) as cash_balance
                FROM accounts a
                WHERE a.company_id = $companyId AND a.type = 'asset' AND (a.code LIKE '111%' OR a.name_ar LIKE '%خزينة%' OR a.name_ar LIKE '%بنك%' OR a.name_ar LIKE '%صندوق%') $condAcc
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $accounts = [];
            $stats = (object)['total_accounts'=>0, 'total_balance'=>0, 'bank_balance'=>0, 'cash_balance'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/treasury/accounts/index.php';
        $content = ob_get_clean();
        
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response)
    {
        $account = null;
        try {
            $companyId = $this->getCompanyId();
            $nextSeq = (int)$this->db->query("SELECT COUNT(*) FROM accounts WHERE company_id = $companyId AND code LIKE '111%'")->fetchColumn() + 1;
            $autoCode = '111' . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            $autoCode = '111001';
        }

        ob_start(); 
        include $this->basePath . '/resources/views/treasury/accounts/create.php';
        $content = ob_get_clean();
        
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response)
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if (empty($data['code']) || empty($data['name_ar'])) {
                throw new Exception($isAr ? "يرجى تعبئة الحقول الإلزامية." : "Required fields missing.");
            }

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $hasBranch = $this->hasBranchColumn('accounts');
            $branchCol = $hasBranch ? ", branch_id" : "";
            $branchVal = $hasBranch ? ", ?" : "";

            $stmt = $this->db->prepare("
                INSERT INTO accounts (company_id, code, name_ar, name_en, type, current_balance, is_active, is_parent {$branchCol})
                VALUES (?, ?, ?, ?, 'asset', ?, ?, 0 {$branchVal})
            ");

            $params = [
                $companyId,
                trim($data['code']),
                trim($data['name_ar']),
                trim($data['name_en'] ?? ''),
                !empty($data['current_balance']) ? (float)$data['current_balance'] : 0.00,
                isset($data['is_active']) ? 1 : 0
            ];

            if ($hasBranch) {
                $params[] = $branchId;
            }

            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم إنشاء الحساب / الخزينة بنجاح." : "Treasury account created successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            header("Location: /ERP/treasury/accounts/create");
            exit;
        }

        header("Location: /ERP/treasury/accounts");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        
        try {
            if (!$id) throw new Exception("المعرف غير صالح.");

            $companyId = $this->getCompanyId();
            $stmt = $this->db->prepare("SELECT * FROM accounts WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $account = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$account) throw new Exception("الحساب غير موجود.");
            $autoCode = $account->code;

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/accounts");
            exit;
        }

        ob_start(); 
        include $this->basePath . '/resources/views/treasury/accounts/create.php';
        $content = ob_get_clean();
        
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if (!$id) throw new Exception("معرف الحساب مفقود.");

            $companyId = $this->getCompanyId();

            $stmt = $this->db->prepare("
                UPDATE accounts 
                SET code = ?, name_ar = ?, name_en = ?, current_balance = ?, is_active = ?
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([
                trim($data['code']),
                trim($data['name_ar']),
                trim($data['name_en'] ?? ''),
                !empty($data['current_balance']) ? (float)$data['current_balance'] : 0.00,
                isset($data['is_active']) ? 1 : 0,
                $id,
                $companyId
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات الحساب بنجاح." : "Account updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            header("Location: /ERP/treasury/accounts/{$id}/edit");
            exit;
        }

        header("Location: /ERP/treasury/accounts");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $this->getCompanyId();

        try {
            if (!$id) throw new Exception("المعرف غير صالح.");

            $trxCheck = $this->db->prepare("SELECT COUNT(*) FROM journal_entry_items WHERE account_id = ?");
            $trxCheck->execute([$id]);
            if ($trxCheck->fetchColumn() > 0) {
                throw new Exception("لا يمكن حذف حساب مرتبطة به حركات مالية في القيود اليومية.");
            }

            $this->db->prepare("DELETE FROM accounts WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف الحساب بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/treasury/accounts");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        try {
            $id = $this->resolveId($id);
            if (!$id) throw new Exception("معرف الحساب غير متاح.");

            $companyId = $this->getCompanyId();

            $stmt = $this->db->prepare("SELECT * FROM accounts WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $account = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$account) throw new Exception("الحساب غير موجود.");

            $transactions = [];
            try {
                $txStmt = $this->db->prepare("
                    SELECT ji.*, je.entry_number, je.entry_date, je.description as entry_desc
                    FROM journal_entry_items ji
                    JOIN journal_entries je ON ji.journal_entry_id = je.id
                    WHERE ji.account_id = ? AND je.status = 'posted'
                    ORDER BY je.entry_date DESC, je.id DESC LIMIT 30
                ");
                $txStmt->execute([$id]);
                $transactions = $txStmt->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {
                $transactions = [];
            }

            ob_start(); 
            include $this->basePath . '/resources/views/treasury/accounts/show.php';
            $content = ob_get_clean();
            
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/accounts");
            exit;
        }
    }
}