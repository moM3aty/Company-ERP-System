<?php
// Path: app/Modules/Treasury/Http/Controllers/PaymentVoucherController.php

namespace App\Modules\Treasury\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class PaymentVoucherController extends Controller
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
        if (preg_match('#/payments/(\d+)#', $uri, $matches)) return (int)$matches[1];
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
        if (preg_match('#/payments/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/payments/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/payments/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/payments/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

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

            $condTP = $this->buildBranchCond('p', $branchId, 'treasury_payments');

            $where = ["p.company_id = ?"];
            $params = [$companyId];

            if ($search !== '') {
                $where[] = "(p.voucher_number LIKE ? OR p.payee_name LIKE ? OR p.description LIKE ? OR p.reference_no LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like, $like);
            }

            if ($methodFilter !== '') {
                $where[] = "p.payment_method = ?";
                $params[] = $methodFilter;
            }

            if ($fromDate !== '') {
                $where[] = "p.payment_date >= ?";
                $params[] = $fromDate;
            }

            if ($toDate !== '') {
                $where[] = "p.payment_date <= ?";
                $params[] = $toDate;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM treasury_payments p $whereSql $condTP");
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCount / $limit));

            $joinBranch = $this->hasBranchColumn('treasury_payments') ? "LEFT JOIN sys_branches br ON p.branch_id = br.id" : "";
            $colBranch = $this->hasBranchColumn('treasury_payments') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT p.*, 
                       a.name_ar as account_name, a.code as account_code,
                       s.name_ar as supplier_name,
                       {$colBranch}
                FROM treasury_payments p
                LEFT JOIN accounts a ON p.treasury_account_id = a.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                {$joinBranch}
                $whereSql $condTP
                ORDER BY p.payment_date DESC, p.id DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $payments = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total_vouchers,
                    COALESCE(SUM(amount), 0) as total_amount,
                    COALESCE(SUM(IF(payment_method = 'cash', amount, 0)), 0) as total_cash,
                    COALESCE(SUM(IF(payment_method = 'bank_transfer', amount, 0)), 0) as total_bank,
                    COALESCE(SUM(IF(payment_method = 'cheque', amount, 0)), 0) as total_cheque
                FROM treasury_payments p
                WHERE p.company_id = $companyId $condTP
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $payments = [];
            $stats = (object)['total_vouchers'=>0, 'total_amount'=>0, 'total_cash'=>0, 'total_bank'=>0, 'total_cheque'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;
        ob_start(); include $this->basePath . '/resources/views/treasury/payments/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response)
    {
        $payment = null; 
        $treasuryAccounts = [];
        $suppliers = [];

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();
            $condAcc = $this->buildBranchCond('a', $branchId, 'accounts');
            $condSup = $this->buildBranchCond('s', $branchId, 'suppliers');

            $treasuryAccounts = $this->db->query("
                SELECT a.id, a.code, a.name_ar 
                FROM accounts a
                WHERE a.company_id = {$companyId} AND a.type = 'asset' AND (a.code LIKE '111%' OR a.name_ar LIKE '%خزينة%' OR a.name_ar LIKE '%بنك%' OR a.name_ar LIKE '%صندوق%') {$condAcc}
                ORDER BY a.code ASC
            ")->fetchAll(PDO::FETCH_OBJ) ?: [];

            $suppliers = $this->db->query("SELECT s.id, s.name_ar, s.phone FROM suppliers s WHERE s.company_id = {$companyId} {$condSup} ORDER BY s.name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            
            $nextSeq = (int)$this->db->query("SELECT COUNT(*) FROM treasury_payments WHERE company_id = {$companyId}")->fetchColumn() + 1;
            $autoNumber = 'PV-' . date('Y') . '-' . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);

        } catch (Throwable $e) {
            $autoNumber = 'PV-' . date('Y') . '-00001';
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/payments/create.php';
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
            if (empty($data['voucher_number']) || empty($data['payment_date']) || empty($data['treasury_account_id']) || empty($data['amount'])) {
                throw new Exception($isAr ? "يرجى تعبئة الحقول الأساسية للسند." : "Please fill required voucher fields.");
            }

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $hasBranch = $this->hasBranchColumn('treasury_payments');
            $branchCol = $hasBranch ? ", branch_id" : "";
            $branchVal = $hasBranch ? ", ?" : "";

            $stmt = $this->db->prepare("
                INSERT INTO treasury_payments 
                (company_id, voucher_number, payment_date, treasury_account_id, supplier_id, payee_name, amount, payment_method, reference_no, description, created_by {$branchCol})
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ? {$branchVal})
            ");

            $params = [
                $companyId,
                trim($data['voucher_number']),
                $data['payment_date'],
                (int)$data['treasury_account_id'],
                !empty($data['supplier_id']) ? (int)$data['supplier_id'] : null,
                trim($data['payee_name'] ?? ''),
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

            $_SESSION['flash_msg'] = $isAr ? "تم إنشاء سند الصرف بنجاح." : "Payment voucher created successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/payments/create");
            exit;
        }

        header("Location: /ERP/treasury/payments");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $stmt = $this->db->prepare("SELECT * FROM treasury_payments WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $payment = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$payment) throw new Exception("سند الصرف غير موجود.");

            $condAcc = $this->buildBranchCond('a', $branchId, 'accounts');
            $condSup = $this->buildBranchCond('s', $branchId, 'suppliers');

            $treasuryAccounts = $this->db->query("SELECT id, code, name_ar FROM accounts a WHERE company_id = $companyId AND type = 'asset' $condAcc ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $suppliers = $this->db->query("SELECT id, name_ar FROM suppliers s WHERE company_id = $companyId $condSup ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $autoNumber = $payment->voucher_number;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/payments");
            exit;
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/payments/create.php';
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
                UPDATE treasury_payments 
                SET payment_date = ?, treasury_account_id = ?, supplier_id = ?, payee_name = ?, amount = ?, payment_method = ?, reference_no = ?, description = ?
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([
                $data['payment_date'],
                (int)$data['treasury_account_id'],
                !empty($data['supplier_id']) ? (int)$data['supplier_id'] : null,
                trim($data['payee_name'] ?? ''),
                (float)$data['amount'],
                $data['payment_method'] ?? 'cash',
                trim($data['reference_no'] ?? ''),
                trim($data['description'] ?? ''),
                $id,
                $companyId
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات سند الصرف بنجاح." : "Payment voucher updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/payments/{$id}/edit");
            exit;
        }

        header("Location: /ERP/treasury/payments");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $this->getCompanyId();

        try {
            $this->db->prepare("DELETE FROM treasury_payments WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف سند الصرف بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/treasury/payments");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        try {
            $id = $this->resolveId($id);
            if (!$id) throw new Exception("المعرف غير متاح.");

            $companyId = $this->getCompanyId();

            $stmt = $this->db->prepare("
                SELECT p.*, 
                       a.name_ar as account_name, a.code as account_code,
                       s.name_ar as supplier_name
                FROM treasury_payments p
                LEFT JOIN accounts a ON p.treasury_account_id = a.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                WHERE p.id = ? AND p.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $payment = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$payment) throw new Exception("سند الصرف غير موجود.");

            ob_start(); include $this->basePath . '/resources/views/treasury/payments/show.php';
            $content = ob_get_clean();
            
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/payments");
            exit;
        }
    }
}