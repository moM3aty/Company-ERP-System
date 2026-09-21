<?php
// Path: app/Modules/Treasury/Http/Controllers/InternalTransferController.php

namespace App\Modules\Treasury\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class InternalTransferController extends Controller
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
        if (preg_match('#/transfers/(\d+)#', $uri, $matches)) return (int)$matches[1];
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
        if (preg_match('#/transfers/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/transfers/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/transfers/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/transfers/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $fromDate = trim($_GET['start_date'] ?? '');
        $toDate = trim($_GET['end_date'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condTR = $this->buildBranchCond('t', $branchId, 'treasury_transfers');

            $where = ["t.company_id = ?"];
            $params = [$companyId];

            if ($search !== '') {
                $where[] = "(t.transfer_number LIKE ? OR t.description LIKE ? OR t.reference_no LIKE ? OR fa.name_ar LIKE ? OR ta.name_ar LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like, $like, $like);
            }

            if ($fromDate !== '') {
                $where[] = "t.transfer_date >= ?";
                $params[] = $fromDate;
            }

            if ($toDate !== '') {
                $where[] = "t.transfer_date <= ?";
                $params[] = $toDate;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM treasury_transfers t
                LEFT JOIN accounts fa ON t.from_account_id = fa.id
                LEFT JOIN accounts ta ON t.to_account_id = ta.id
                $whereSql $condTR
            ");
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCount / $limit));

            $stmt = $this->db->prepare("
                SELECT t.*, 
                       fa.name_ar as from_account_name, fa.code as from_account_code,
                       ta.name_ar as to_account_name, ta.code as to_account_code
                FROM treasury_transfers t
                LEFT JOIN accounts fa ON t.from_account_id = fa.id
                LEFT JOIN accounts ta ON t.to_account_id = ta.id
                $whereSql $condTR
                ORDER BY t.transfer_date DESC, t.id DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $transfers = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total_transfers,
                    COALESCE(SUM(amount), 0) as total_amount
                FROM treasury_transfers t
                WHERE t.company_id = $companyId $condTR
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $transfers = [];
            $stats = (object)['total_transfers'=>0, 'total_amount'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;
        ob_start(); include $this->basePath . '/resources/views/treasury/transfers/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response)
    {
        $transfer = null; 
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
            
            $nextSeq = (int)$this->db->query("SELECT COUNT(*) FROM treasury_transfers WHERE company_id = {$companyId}")->fetchColumn() + 1;
            $autoNumber = 'TRF-' . date('Y') . '-' . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);

        } catch (Throwable $e) {
            $autoNumber = 'TRF-' . date('Y') . '-00001';
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/transfers/create.php';
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
            if (empty($data['transfer_number']) || empty($data['transfer_date']) || empty($data['from_account_id']) || empty($data['to_account_id']) || empty($data['amount'])) {
                throw new Exception($isAr ? "يرجى تعبئة الحقول الأساسية للتحويل." : "Please fill required transfer fields.");
            }

            if ((int)$data['from_account_id'] === (int)$data['to_account_id']) {
                throw new Exception($isAr ? "لا يمكن تحويل الأموال إلى نفس الحساب/الخزينة المصدر." : "Source and destination accounts cannot be the same.");
            }

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $hasBranch = $this->hasBranchColumn('treasury_transfers');
            $branchCol = $hasBranch ? ", branch_id" : "";
            $branchVal = $hasBranch ? ", ?" : "";

            $stmt = $this->db->prepare("
                INSERT INTO treasury_transfers 
                (company_id, transfer_number, transfer_date, from_account_id, to_account_id, amount, reference_no, description, created_by {$branchCol})
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ? {$branchVal})
            ");

            $params = [
                $companyId,
                trim($data['transfer_number']),
                $data['transfer_date'],
                (int)$data['from_account_id'],
                (int)$data['to_account_id'],
                (float)$data['amount'],
                trim($data['reference_no'] ?? ''),
                trim($data['description'] ?? ''),
                $_SESSION['user_id'] ?? 1
            ];

            if ($hasBranch) {
                $params[] = $branchId;
            }

            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم إنشاء أمر التحويل الداخلي بنجاح." : "Internal transfer created successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/transfers/create");
            exit;
        }

        header("Location: /ERP/treasury/transfers");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $stmt = $this->db->prepare("SELECT * FROM treasury_transfers WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $transfer = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$transfer) throw new Exception("أمر التحويل الداخلي غير موجود.");

            $condAcc = $this->buildBranchCond('a', $branchId, 'accounts');
            $treasuryAccounts = $this->db->query("SELECT id, code, name_ar FROM accounts a WHERE company_id = $companyId AND type = 'asset' $condAcc ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $autoNumber = $transfer->transfer_number;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/transfers");
            exit;
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/transfers/create.php';
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
            if ((int)$data['from_account_id'] === (int)$data['to_account_id']) {
                throw new Exception($isAr ? "لا يمكن تحويل الأموال إلى نفس الحساب/الخزينة المصدر." : "Source and destination accounts cannot be the same.");
            }

            $companyId = $this->getCompanyId();

            $stmt = $this->db->prepare("
                UPDATE treasury_transfers 
                SET transfer_date = ?, from_account_id = ?, to_account_id = ?, amount = ?, reference_no = ?, description = ?
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([
                $data['transfer_date'],
                (int)$data['from_account_id'],
                (int)$data['to_account_id'],
                (float)$data['amount'],
                trim($data['reference_no'] ?? ''),
                trim($data['description'] ?? ''),
                $id,
                $companyId
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات أمر التحويل بنجاح." : "Internal transfer updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/transfers/{$id}/edit");
            exit;
        }

        header("Location: /ERP/treasury/transfers");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $this->getCompanyId();

        try {
            $this->db->prepare("DELETE FROM treasury_transfers WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف أمر التحويل الداخلي بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/treasury/transfers");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        try {
            $id = $this->resolveId($id);
            if (!$id) throw new Exception("المعرف غير متاح.");

            $companyId = $this->getCompanyId();

            $stmt = $this->db->prepare("
                SELECT t.*, 
                       fa.name_ar as from_account_name, fa.code as from_account_code,
                       ta.name_ar as to_account_name, ta.code as to_account_code
                FROM treasury_transfers t
                LEFT JOIN accounts fa ON t.from_account_id = fa.id
                LEFT JOIN accounts ta ON t.to_account_id = ta.id
                WHERE t.id = ? AND t.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $transfer = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$transfer) throw new Exception("أمر التحويل الداخلي غير موجود.");

            ob_start(); include $this->basePath . '/resources/views/treasury/transfers/show.php';
            $content = ob_get_clean();
            
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/transfers");
            exit;
        }
    }
}