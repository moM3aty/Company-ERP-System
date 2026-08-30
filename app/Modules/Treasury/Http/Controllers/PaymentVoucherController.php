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
        if (preg_match('#/payments/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
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
        $limit = 15; // 15 عنصر في الصفحة
        $offset = ($page - 1) * $limit;

        try {
            $where = ["1=1"];
            $params = [];

            if ($search !== '') {
                $where[] = "(p.voucher_number LIKE ? OR p.payee_name LIKE ? OR p.description LIKE ? OR p.reference_no LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like, $like]);
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

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM treasury_payments p $whereSql");
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCount / $limit));

            $stmt = $this->db->prepare("
                SELECT p.*, 
                       a.name_ar as account_name, a.code as account_code,
                       s.name_ar as supplier_name
                FROM treasury_payments p
                LEFT JOIN accounts a ON p.treasury_account_id = a.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                $whereSql
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
                FROM treasury_payments
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

    public function create(Request $request, Response $response): Response
    {
        $payment = null; 
        $treasuryAccounts = [];
        $suppliers = [];

        try {
            $treasuryAccounts = $this->db->query("
                SELECT id, code, name_ar 
                FROM accounts 
                WHERE type = 'asset' AND (code LIKE '111%' OR name_ar LIKE '%خزينة%' OR name_ar LIKE '%بنك%' OR name_ar LIKE '%صندوق%') 
                ORDER BY code ASC
            ")->fetchAll(PDO::FETCH_OBJ);

            $suppliers = $this->db->query("SELECT id, name_ar, phone FROM suppliers ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
            
            $nextSeq = (int)$this->db->query("SELECT COUNT(*) FROM treasury_payments")->fetchColumn() + 1;
            $autoNumber = 'PV-' . date('Y') . '-' . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);

        } catch (Throwable $e) {
            $autoNumber = 'PV-' . date('Y') . '-00001';
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/payments/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (empty($data['voucher_number']) || empty($data['payment_date']) || empty($data['treasury_account_id']) || empty($data['amount'])) {
                throw new Exception("يرجى تعبئة الحقول الأساسية للسند.");
            }

            $stmt = $this->db->prepare("
                INSERT INTO treasury_payments 
                (voucher_number, payment_date, treasury_account_id, supplier_id, payee_name, amount, payment_method, reference_no, description, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
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
            ]);

            $_SESSION['flash_msg'] = "تم إنشاء سند الصرف بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/treasury/payments/create');
        }

        return new RedirectResponse('/ERP/treasury/payments');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $stmt = $this->db->prepare("SELECT * FROM treasury_payments WHERE id = ?");
            $stmt->execute([$id]);
            $payment = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$payment) throw new Exception("سند الصرف غير موجود.");

            $treasuryAccounts = $this->db->query("SELECT id, code, name_ar FROM accounts WHERE type = 'asset' ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);
            $suppliers = $this->db->query("SELECT id, name_ar FROM suppliers ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ);
            $autoNumber = $payment->voucher_number;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/treasury/payments');
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/payments/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $stmt = $this->db->prepare("
                UPDATE treasury_payments 
                SET payment_date = ?, treasury_account_id = ?, supplier_id = ?, payee_name = ?, amount = ?, payment_method = ?, reference_no = ?, description = ?
                WHERE id = ?
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
                $id
            ]);

            $_SESSION['flash_msg'] = "تم تحديث بيانات سند الصرف بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/treasury/payments/{$id}/edit");
        }

        return new RedirectResponse('/ERP/treasury/payments');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $this->db->prepare("DELETE FROM treasury_payments WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف سند الصرف بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/treasury/payments');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);

        $stmt = $this->db->prepare("
            SELECT p.*, 
                   a.name_ar as account_name, a.code as account_code,
                   s.name_ar as supplier_name
            FROM treasury_payments p
            LEFT JOIN accounts a ON p.treasury_account_id = a.id
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $payment = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$payment) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "سند الصرف غير موجود.";
            return new RedirectResponse('/ERP/treasury/payments');
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/payments/show.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}