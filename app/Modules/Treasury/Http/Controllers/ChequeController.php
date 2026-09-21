<?php
// Path: app/Modules/Treasury/Http/Controllers/ChequeController.php

namespace App\Modules\Treasury\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class ChequeController extends Controller
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
        if (preg_match('#/cheques/(\d+)#', $uri, $matches)) return (int)$matches[1];
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
        if (preg_match('#/cheques/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/cheques/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/cheques/(\d+)/status#', $uri, $m)) return $this->updateStatus($request, $response, (int)$m[1]);
        if (preg_match('#/cheques/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/cheques/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $typeFilter = trim($_GET['type'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $fromDate = trim($_GET['start_date'] ?? '');
        $toDate = trim($_GET['end_date'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condChq = $this->buildBranchCond('c', $branchId, 'treasury_cheques');

            $where = ["c.company_id = ?"];
            $params = [$companyId];

            if ($search !== '') {
                $where[] = "(c.cheque_number LIKE ? OR c.bank_name LIKE ? OR c.payee_payer_name LIKE ? OR c.notes LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like, $like);
            }

            if ($typeFilter !== '') {
                $where[] = "c.type = ?";
                $params[] = $typeFilter;
            }

            if ($statusFilter !== '') {
                $where[] = "c.status = ?";
                $params[] = $statusFilter;
            }

            if ($fromDate !== '') {
                $where[] = "c.due_date >= ?";
                $params[] = $fromDate;
            }

            if ($toDate !== '') {
                $where[] = "c.due_date <= ?";
                $params[] = $toDate;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM treasury_cheques c
                LEFT JOIN accounts a ON c.treasury_account_id = a.id
                $whereSql $condChq
            ");
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCount / $limit));

            $joinBranch = $this->hasBranchColumn('treasury_cheques') ? "LEFT JOIN sys_branches br ON c.branch_id = br.id" : "";
            $colBranch = $this->hasBranchColumn('treasury_cheques') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT c.*, a.name_ar as account_name, a.code as account_code, {$colBranch}
                FROM treasury_cheques c
                LEFT JOIN accounts a ON c.treasury_account_id = a.id
                {$joinBranch}
                $whereSql $condChq
                ORDER BY c.due_date ASC, c.id DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $cheques = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total_cheques,
                    COALESCE(SUM(amount), 0) as total_amount,
                    COALESCE(SUM(IF(status = 'pending', amount, 0)), 0) as pending_amount,
                    COALESCE(SUM(IF(status = 'collected', amount, 0)), 0) as collected_amount,
                    COALESCE(SUM(IF(status = 'bounced', amount, 0)), 0) as bounced_amount
                FROM treasury_cheques c
                WHERE c.company_id = $companyId $condChq
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $cheques = [];
            $stats = (object)['total_cheques'=>0, 'total_amount'=>0, 'pending_amount'=>0, 'collected_amount'=>0, 'bounced_amount'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;
        ob_start(); include $this->basePath . '/resources/views/treasury/cheques/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response)
    {
        $cheque = null; 
        $treasuryAccounts = [];

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();
            $condAcc = $this->buildBranchCond('a', $branchId, 'accounts');

            $treasuryAccounts = $this->db->query("
                SELECT a.id, a.code, a.name_ar 
                FROM accounts a 
                WHERE a.company_id = {$companyId} AND a.type = 'asset' AND (a.code LIKE '111%' OR a.name_ar LIKE '%خزينة%' OR a.name_ar LIKE '%بنك%' OR a.name_ar LIKE '%صندوق%') {$condAcc} 
                ORDER BY a.code ASC
            ")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {}

        ob_start(); include $this->basePath . '/resources/views/treasury/cheques/create.php';
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
            if (empty($data['cheque_number']) || empty($data['bank_name']) || empty($data['due_date']) || empty($data['amount']) || empty($data['treasury_account_id'])) {
                throw new Exception($isAr ? "يرجى تعبئة كافة الحقول الأساسية للشيك." : "Please fill required cheque fields.");
            }

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $hasBranch = $this->hasBranchColumn('treasury_cheques');
            $branchCol = $hasBranch ? ", branch_id" : "";
            $branchVal = $hasBranch ? ", ?" : "";

            $stmt = $this->db->prepare("
                INSERT INTO treasury_cheques 
                (company_id, cheque_number, type, bank_name, treasury_account_id, payee_payer_name, amount, issue_date, due_date, status, notes, created_by {$branchCol})
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ? {$branchVal})
            ");

            $params = [
                $companyId,
                trim($data['cheque_number']),
                $data['type'] ?? 'received',
                trim($data['bank_name']),
                (int)$data['treasury_account_id'],
                trim($data['payee_payer_name'] ?? ''),
                (float)$data['amount'],
                $data['issue_date'] ?? date('Y-m-d'),
                $data['due_date'],
                trim($data['notes'] ?? ''),
                $_SESSION['user_id'] ?? 1
            ];

            if ($hasBranch) {
                $params[] = $branchId;
            }

            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم تسجيل الشيك الورقي بنجاح." : "Cheque registered successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/cheques/create");
            exit;
        }

        header("Location: /ERP/treasury/cheques");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $stmt = $this->db->prepare("SELECT * FROM treasury_cheques WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $cheque = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$cheque) throw new Exception("الشيك غير موجود.");

            $condAcc = $this->buildBranchCond('a', $branchId, 'accounts');
            $treasuryAccounts = $this->db->query("SELECT id, code, name_ar FROM accounts a WHERE company_id = $companyId AND type = 'asset' $condAcc ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/cheques");
            exit;
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/cheques/create.php';
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
            $companyId = $this->getCompanyId();

            $stmt = $this->db->prepare("
                UPDATE treasury_cheques 
                SET cheque_number = ?, type = ?, bank_name = ?, treasury_account_id = ?, payee_payer_name = ?, amount = ?, issue_date = ?, due_date = ?, notes = ?
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([
                trim($data['cheque_number']),
                $data['type'] ?? 'received',
                trim($data['bank_name']),
                (int)$data['treasury_account_id'],
                trim($data['payee_payer_name'] ?? ''),
                (float)$data['amount'],
                $data['issue_date'],
                $data['due_date'],
                trim($data['notes'] ?? ''),
                $id,
                $companyId
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات الشيك بنجاح." : "Cheque updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/cheques/{$id}/edit");
            exit;
        }

        header("Location: /ERP/treasury/cheques");
        exit;
    }

    public function updateStatus(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            $companyId = $this->getCompanyId();
            $newStatus = $data['status'] ?? 'pending';
            $validStatuses = ['pending', 'collected', 'bounced', 'cancelled'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new Exception($isAr ? "حالة الشيك غير صالحة." : "Invalid cheque status.");
            }

            $stmt = $this->db->prepare("UPDATE treasury_cheques SET status = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$newStatus, $id, $companyId]);

            $_SESSION['flash_msg'] = $isAr ? "تم تغيير حالة الشيك بنجاح." : "Cheque status updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/treasury/cheques/{$id}");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $this->getCompanyId();

        try {
            $this->db->prepare("DELETE FROM treasury_cheques WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف سجل الشيك بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/treasury/cheques");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        try {
            $companyId = $this->getCompanyId();

            $stmt = $this->db->prepare("
                SELECT c.*, a.name_ar as account_name, a.code as account_code
                FROM treasury_cheques c
                LEFT JOIN accounts a ON c.treasury_account_id = a.id
                WHERE c.id = ? AND c.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $cheque = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$cheque) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['flash_err'] = "سجل الشيك غير موجود.";
                header("Location: /ERP/treasury/cheques");
                exit;
            }

            ob_start(); include $this->basePath . '/resources/views/treasury/cheques/show.php';
            $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/cheques");
            exit;
        }
    }
}