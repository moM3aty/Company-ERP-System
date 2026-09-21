<?php
// Path: app/Modules/Treasury/Http/Controllers/ReceiptController.php

namespace App\Modules\Treasury\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class ReceiptController extends Controller
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
        if (preg_match('#/receipts/(\d+)#', $uri, $matches)) return (int)$matches[1];
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
        if (preg_match('#/receipts/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/receipts/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/receipts/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/receipts/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $methodFilter = trim($_GET['payment_method'] ?? '');
        $fromDate = trim($_GET['start_date'] ?? '');
        $toDate = trim($_GET['end_date'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condTR = $this->buildBranchCond('r', $branchId, 'treasury_receipts');

            $where = ["r.company_id = ?"];
            $params = [$companyId];

            if ($search !== '') {
                $where[] = "(r.voucher_number LIKE ? OR r.payer_name LIKE ? OR r.description LIKE ? OR r.reference_no LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like, $like);
            }

            if ($methodFilter !== '') {
                $where[] = "r.payment_method = ?";
                $params[] = $methodFilter;
            }

            if ($fromDate !== '') {
                $where[] = "r.receipt_date >= ?";
                $params[] = $fromDate;
            }

            if ($toDate !== '') {
                $where[] = "r.receipt_date <= ?";
                $params[] = $toDate;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM treasury_receipts r $whereSql $condTR");
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCount / $limit));

            $joinBranch = $this->hasBranchColumn('treasury_receipts') ? "LEFT JOIN sys_branches br ON r.branch_id = br.id" : "";
            $colBranch = $this->hasBranchColumn('treasury_receipts') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT r.*, 
                       a.name_ar as account_name, a.code as account_code,
                       c.name_ar as customer_name,
                       {$colBranch}
                FROM treasury_receipts r
                LEFT JOIN accounts a ON r.treasury_account_id = a.id
                LEFT JOIN customers c ON r.customer_id = c.id
                {$joinBranch}
                $whereSql $condTR
                ORDER BY r.receipt_date DESC, r.id DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $receipts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total_vouchers,
                    COALESCE(SUM(amount), 0) as total_amount,
                    COALESCE(SUM(IF(payment_method = 'cash', amount, 0)), 0) as total_cash,
                    COALESCE(SUM(IF(payment_method = 'bank_transfer', amount, 0)), 0) as total_bank,
                    COALESCE(SUM(IF(payment_method = 'cheque', amount, 0)), 0) as total_cheque
                FROM treasury_receipts r
                WHERE r.company_id = $companyId $condTR
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $receipts = [];
            $stats = (object)['total_vouchers'=>0, 'total_amount'=>0, 'total_cash'=>0, 'total_bank'=>0, 'total_cheque'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;
        ob_start(); include $this->basePath . '/resources/views/treasury/receipts/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response)
    {
        $receipt = null; 
        $treasuryAccounts = [];
        $customers = [];

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();
            $condAcc = $this->buildBranchCond('a', $branchId, 'accounts');
            $condCust = $this->buildBranchCond('c', $branchId, 'customers');

            $treasuryAccounts = $this->db->query("
                SELECT a.id, a.code, a.name_ar 
                FROM accounts a
                WHERE a.company_id = {$companyId} AND a.type = 'asset' AND (a.code LIKE '111%' OR a.name_ar LIKE '%خزينة%' OR a.name_ar LIKE '%بنك%') {$condAcc}
                ORDER BY a.code ASC
            ")->fetchAll(PDO::FETCH_OBJ) ?: [];

            $customers = $this->db->query("SELECT c.id, c.name_ar, c.phone FROM customers c WHERE c.company_id = {$companyId} {$condCust} ORDER BY c.name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            
            $nextSeq = (int)$this->db->query("SELECT COUNT(*) FROM treasury_receipts WHERE company_id = {$companyId}")->fetchColumn() + 1;
            $autoNumber = 'RV-' . date('Y') . '-' . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);

        } catch (Throwable $e) {
            $autoNumber = 'RV-' . date('Y') . '-00001';
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/receipts/create.php';
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
            if (empty($data['voucher_number']) || empty($data['receipt_date']) || empty($data['treasury_account_id']) || empty($data['amount'])) {
                throw new Exception($isAr ? "يرجى تعبئة الحقول الأساسية للسند." : "Please fill required voucher fields.");
            }

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $hasBranch = $this->hasBranchColumn('treasury_receipts');
            $branchCol = $hasBranch ? ", branch_id" : "";
            $branchVal = $hasBranch ? ", ?" : "";

            $stmt = $this->db->prepare("
                INSERT INTO treasury_receipts 
                (company_id, voucher_number, receipt_date, treasury_account_id, customer_id, payer_name, amount, payment_method, reference_no, description, created_by {$branchCol})
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ? {$branchVal})
            ");

            $params = [
                $companyId,
                trim($data['voucher_number']),
                $data['receipt_date'],
                (int)$data['treasury_account_id'],
                !empty($data['customer_id']) ? (int)$data['customer_id'] : null,
                trim($data['payer_name'] ?? ''),
                (float)$data['amount'],
                $data['payment_method'] ?? 'cash',
                trim($data['reference_no'] ?? ''),
                trim($data['description'] ?? ''),
                $_SESSION['user_id'] ?? 1
            ];

            if ($hasBranch) {
                $params[] = $branchId;
            }

            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم إنشاء سند القبض بنجاح." : "Receipt voucher created successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/receipts/create");
            exit;
        }

        header("Location: /ERP/treasury/receipts");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $stmt = $this->db->prepare("SELECT * FROM treasury_receipts WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $receipt = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$receipt) throw new Exception("سند القبض غير موجود.");

            $condAcc = $this->buildBranchCond('a', $branchId, 'accounts');
            $condCust = $this->buildBranchCond('c', $branchId, 'customers');

            $treasuryAccounts = $this->db->query("SELECT id, code, name_ar FROM accounts a WHERE company_id = $companyId AND type = 'asset' $condAcc ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $customers = $this->db->query("SELECT id, name_ar FROM customers c WHERE company_id = $companyId $condCust ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $autoNumber = $receipt->voucher_number;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/receipts");
            exit;
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/receipts/create.php';
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
                UPDATE treasury_receipts 
                SET receipt_date = ?, treasury_account_id = ?, customer_id = ?, payer_name = ?, amount = ?, payment_method = ?, reference_no = ?, description = ?
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([
                $data['receipt_date'],
                (int)$data['treasury_account_id'],
                !empty($data['customer_id']) ? (int)$data['customer_id'] : null,
                trim($data['payer_name'] ?? ''),
                (float)$data['amount'],
                $data['payment_method'] ?? 'cash',
                trim($data['reference_no'] ?? ''),
                trim($data['description'] ?? ''),
                $id,
                $companyId
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات سند القبض بنجاح." : "Receipt voucher updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/receipts/{$id}/edit");
            exit;
        }

        header("Location: /ERP/treasury/receipts");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $this->getCompanyId();

        try {
            $this->db->prepare("DELETE FROM treasury_receipts WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف سند القبض بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/treasury/receipts");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        try {
            $id = $this->resolveId($id);
            if (!$id) throw new Exception("المعرف غير متاح.");

            $companyId = $this->getCompanyId();

            $stmt = $this->db->prepare("
                SELECT r.*, 
                       a.name_ar as account_name, a.code as account_code,
                       c.name_ar as customer_name
                FROM treasury_receipts r
                LEFT JOIN accounts a ON r.treasury_account_id = a.id
                LEFT JOIN customers c ON r.customer_id = c.id
                WHERE r.id = ? AND r.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $receipt = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$receipt) throw new Exception("سند القبض غير موجود.");

            ob_start(); include $this->basePath . '/resources/views/treasury/receipts/show.php';
            $content = ob_get_clean();
            
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/receipts");
            exit;
        }
    }
}