<?php
// Path: app/Modules/Purchasing/Http/Controllers/SupplierStatementController.php

namespace App\Modules\Purchasing\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class SupplierStatementController extends Controller
{
    private string $basePath;
    private PDO $db;

    public function __construct()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    public function index(Request $request, Response $response): Response
    {
        $dbError = null;
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $whereClause = "";
            $params = [];
            if ($search !== '') {
                $whereClause = "WHERE s.name_ar LIKE ? OR s.name_en LIKE ? OR s.code LIKE ? OR s.phone LIKE ?";
                $like = "%{$search}%";
                $params = [$like, $like, $like, $like];
            }

            // حساب عدد الموردين المبحوث عنهم
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM suppliers s $whereClause");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            // جلب الموردين مع إجماليات الفواتير والمدفوعات والمرتجعات
            $stmt = $this->db->prepare("
                SELECT s.id, s.code, COALESCE(s.name_ar, s.name_en) as supplier_name, s.phone, s.tax_number, s.is_active,
                       COALESCE((SELECT SUM(total_amount) FROM purchase_invoices WHERE supplier_id = s.id AND status != 'cancelled'), 0) as total_invoiced,
                       COALESCE((SELECT SUM(paid_amount) FROM purchase_invoices WHERE supplier_id = s.id AND status != 'cancelled'), 0) as total_paid,
                       COALESCE((SELECT SUM(total_amount) FROM purchase_returns WHERE supplier_id = s.id AND status != 'cancelled'), 0) as total_returned
                FROM suppliers s
                $whereClause
                ORDER BY s.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $statements = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            // حساب الإحصائيات العامة للموردين المبحوث عنهم
            $totalBalanceSum = 0;
            $totalInvoicedSum = 0;
            foreach ($statements as $st) {
                $st->net_balance = $st->total_invoiced - ($st->total_paid + $st->total_returned);
                $totalBalanceSum += $st->net_balance;
                $totalInvoicedSum += $st->total_invoiced;
            }

        } catch (Throwable $e) {
            $statements = [];
            $totalPages = 1;
            $totalBalanceSum = 0;
            $totalInvoicedSum = 0;
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/purchasing/statements/index.php';
        if ($dbError) echo "<div style='margin:20px; padding:20px; background:#fef2f2; color:#b91c1c; border-radius:8px;'><strong>DB Error:</strong> $dbError</div>";
        if (file_exists($viewPath)) include $viewPath;
        else echo "<div style='margin:20px; padding:20px; background:#fee2e2; color:#dc2626;'>ملف الواجهة مفقود: $viewPath</div>";
        
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        try {
            // جلب بيانات المورد
            $stmtSup = $this->db->prepare("SELECT * FROM suppliers WHERE id = ?");
            $stmtSup->execute([$id]);
            $supplier = $stmtSup->fetch(PDO::FETCH_OBJ);
            if (!$supplier) throw new Exception("المورد غير موجود.");

            $fromDate = $_GET['from_date'] ?? date('Y-01-01');
            $toDate = $_GET['to_date'] ?? date('Y-12-31');

            // 1. جلب الفواتير المستحقة (Credit / دائن / له)
            $stmtInv = $this->db->prepare("
                SELECT 'invoice' as tx_type, invoice_number as ref_no, invoice_date as tx_date,
                       'فاتورة مشتريات' as tx_title, total_amount as credit, 0.00 as debit, notes
                FROM purchase_invoices 
                WHERE supplier_id = ? AND status != 'cancelled' AND invoice_date BETWEEN ? AND ?
            ");
            $stmtInv->execute([$id, $fromDate, $toDate]);
            $invoices = $stmtInv->fetchAll(PDO::FETCH_OBJ) ?: [];

            // 2. جلب المرتجعات / إشعارات الخصم (Debit / مدين / عليه)
            $stmtRet = $this->db->prepare("
                SELECT 'return' as tx_type, return_number as ref_no, return_date as tx_date,
                       'مرتجع مشتريات / إشعار خصم' as tx_title, 0.00 as credit, total_amount as debit, reason as notes
                FROM purchase_returns 
                WHERE supplier_id = ? AND status != 'cancelled' AND return_date BETWEEN ? AND ?
            ");
            $stmtRet->execute([$id, $fromDate, $toDate]);
            $returns = $stmtRet->fetchAll(PDO::FETCH_OBJ) ?: [];

            // 3. دمج العمليات وترتيبها زمنياً
            $ledger = array_merge($invoices, $returns);
            usort($ledger, function($a, $b) {
                return strtotime($a->tx_date) <=> strtotime($b->tx_date);
            });

            // 4. حساب الرصيد التراكمي خطوة بخطوة
            $runningBalance = 0;
            $totalCredit = 0;
            $totalDebit = 0;
            foreach ($ledger as $tx) {
                $runningBalance += ($tx->credit - $tx->debit);
                $tx->running_balance = $runningBalance;
                $totalCredit += $tx->credit;
                $totalDebit += $tx->debit;
            }

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/statements');
        }

        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/purchasing/statements/show.php';
        if (file_exists($viewPath)) include $viewPath;
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}