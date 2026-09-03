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
        ini_set('display_errors', 0);
        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    public function index(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        $dbError = null;
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $whereClause = "WHERE s.company_id = ?";
            $params = [$companyId];

            if ($branchId > 0) {
                $whereClause .= " AND (s.branch_id = ? OR s.branch_id IS NULL OR s.branch_id = 0)";
                $params[] = $branchId;
            }

            if ($search !== '') {
                $whereClause .= " AND (s.name_ar LIKE ? OR s.name_en LIKE ? OR s.code LIKE ? OR s.phone LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like, $like]);
            }

            // حساب عدد الموردين المبحوث عنهم
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM suppliers s $whereClause");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $poBranchCond = $branchId > 0 ? " AND branch_id = $branchId" : "";

            // جلب الموردين مع إجماليات الفواتير والمدفوعات والمرتجعات كما بالقديم
            $stmt = $this->db->prepare("
                SELECT s.id, s.code, COALESCE(s.name_ar, s.name_en) as supplier_name, s.name_ar, s.name_en, s.phone, s.tax_number, s.is_active,
                       COALESCE((SELECT SUM(total_amount) FROM purchase_invoices WHERE supplier_id = s.id AND company_id = $companyId $poBranchCond AND status != 'cancelled'), 0) as total_invoiced,
                       COALESCE((SELECT SUM(paid_amount) FROM purchase_invoices WHERE supplier_id = s.id AND company_id = $companyId $poBranchCond AND status != 'cancelled'), 0) as total_paid,
                       COALESCE((SELECT SUM(total_amount) FROM purchase_returns WHERE supplier_id = s.id AND company_id = $companyId $poBranchCond AND status != 'cancelled'), 0) as total_returned
                FROM suppliers s
                $whereClause
                ORDER BY s.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $statements = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            // حساب الإحصائيات العامة للموردين المبحوث عنهم مع تحويل العملة
            $totalBalanceSum = 0;
            $totalInvoicedSum = 0;
            foreach ($statements as $st) {
                $st->total_invoiced = convert_amount((float)$st->total_invoiced);
                $st->total_paid     = convert_amount((float)$st->total_paid);
                $st->total_returned = convert_amount((float)$st->total_returned);

                $st->net_balance    = $st->total_invoiced - ($st->total_paid + $st->total_returned);
                $totalBalanceSum   += $st->net_balance;
                $totalInvoicedSum  += $st->total_invoiced;
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
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;
        $isAr      = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            $bCond = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";

            // جلب بيانات المورد
            $stmtSup = $this->db->prepare("SELECT * FROM suppliers WHERE id = ? AND company_id = ? $bCond");
            $stmtSup->execute([$id, $companyId]);
            $supplier = $stmtSup->fetch(PDO::FETCH_OBJ);
            if (!$supplier) throw new Exception("المورد غير موجود.");

            $fromDate = $_GET['from_date'] ?? date('Y-01-01');
            $toDate   = $_GET['to_date']   ?? date('Y-12-31');

            $poBranchCond = $branchId > 0 ? " AND branch_id = $branchId" : "";

            // 1. جلب الفواتير المستحقة (Credit / دائن / له)
            $invTitle = $isAr ? 'فاتورة مشتريات' : 'Purchase Invoice';
            $stmtInv = $this->db->prepare("
                SELECT 'invoice' as tx_type, invoice_number as ref_no, invoice_date as tx_date,
                       '$invTitle' as tx_title, total_amount as credit, paid_amount as debit, notes
                FROM purchase_invoices 
                WHERE supplier_id = ? AND company_id = ? $poBranchCond AND status != 'cancelled' AND invoice_date BETWEEN ? AND ?
            ");
            $stmtInv->execute([$id, $companyId, $fromDate, $toDate]);
            $invoices = $stmtInv->fetchAll(PDO::FETCH_OBJ) ?: [];

            // 2. جلب المرتجعات / إشعارات الخصم (Debit / مدين / عليه)
            $retTitle = $isAr ? 'مرتجع مشتريات / إشعار خصم' : 'Purchase Return (Debit Note)';
            $stmtRet = $this->db->prepare("
                SELECT 'return' as tx_type, return_number as ref_no, return_date as tx_date,
                       '$retTitle' as tx_title, 0.00 as credit, total_amount as debit, reason as notes
                FROM purchase_returns 
                WHERE supplier_id = ? AND company_id = ? $poBranchCond AND status != 'cancelled' AND return_date BETWEEN ? AND ?
            ");
            $stmtRet->execute([$id, $companyId, $fromDate, $toDate]);
            $returns = $stmtRet->fetchAll(PDO::FETCH_OBJ) ?: [];

            // 3. دمج العمليات وترتيبها زمنياً
            $ledger = array_merge($invoices, $returns);
            usort($ledger, function($a, $b) {
                return strtotime($a->tx_date) <=> strtotime($b->tx_date);
            });

            // 4. حساب الرصيد التراكمي وتحويل العملات
            $runningBalance = 0;
            $totalCredit = 0;
            $totalDebit = 0;
            foreach ($ledger as $tx) {
                $tx->credit = convert_amount((float)$tx->credit);
                $tx->debit  = convert_amount((float)$tx->debit);

                $runningBalance += ($tx->credit - $tx->debit);
                $tx->running_balance = $runningBalance;
                $totalCredit += $tx->credit;
                $totalDebit  += $tx->debit;
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