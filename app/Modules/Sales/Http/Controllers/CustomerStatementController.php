<?php
//app/Modules/Sales/Http/Controllers/CustomerStatementController.php
namespace App\Modules\Sales\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;

class CustomerStatementController extends Controller
{
    private string $basePath;
    private PDO $db;

    public function __construct()
    {
        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    public function index(Request $request, Response $response): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();
        $dbError   = null;

        try {
            $branchInvFilter  = $branchId ? " AND branch_id = :branch1" : "";
            $branchRecFilter  = $branchId ? " AND branch_id = :branch2" : "";
            $branchRetFilter  = $branchId ? " AND branch_id = :branch3" : "";
            $branchCustFilter = $branchId ? " AND c.branch_id = :branch4" : "";

            $sql = "
                SELECT c.*,
                       COALESCE((SELECT SUM(total_amount) FROM sales_invoices WHERE customer_id = c.id AND status != 'cancelled' AND company_id = :comp1 {$branchInvFilter}), 0) as total_invoiced,
                       COALESCE((SELECT SUM(amount) FROM sales_receipts WHERE customer_id = c.id AND company_id = :comp2 {$branchRecFilter}), 0) as total_paid,
                       COALESCE((SELECT SUM(total_amount) FROM sales_returns WHERE customer_id = c.id AND status != 'cancelled' AND company_id = :comp3 {$branchRetFilter}), 0) as total_returned
                FROM customers c
                WHERE c.company_id = :comp4 {$branchCustFilter}
                ORDER BY c.id DESC
            ";

            $params = [
                'comp1' => $companyId,
                'comp2' => $companyId,
                'comp3' => $companyId,
                'comp4' => $companyId
            ];

            if ($branchId) {
                $params['branch1'] = $branchId;
                $params['branch2'] = $branchId;
                $params['branch3'] = $branchId;
                $params['branch4'] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $customers = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $summary = (object)[
                'total_receivables' => 0,
                'total_collected'   => 0,
                'active_accounts'   => count($customers)
            ];

            foreach ($customers as $c) {
                // Converting values
                $c->total_invoiced = convert_amount($c->total_invoiced);
                $c->total_paid = convert_amount($c->total_paid);
                $c->total_returned = convert_amount($c->total_returned);

                $netBalance = $c->total_invoiced - ($c->total_paid + $c->total_returned);
                $c->current_balance = $netBalance;
                
                $summary->total_receivables += max(0, $netBalance);
                $summary->total_collected += $c->total_paid;
            }

        } catch (Exception $e) {
            $customers = []; 
            $summary = (object)['total_receivables' => 0, 'total_collected' => 0, 'active_accounts' => 0];
            $dbError = $e->getMessage();
        }

        ob_start(); include $this->basePath . '/resources/views/sales/statements/index.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function show(Request $request, Response $response, $customerId = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $startDate = !empty($_GET['start_date']) ? trim($_GET['start_date']) : null;
            $endDate   = !empty($_GET['end_date']) ? trim($_GET['end_date']) : null;

            $custSql = "SELECT * FROM customers WHERE id = ? AND company_id = ?";
            $custParams = [$customerId, $companyId];
            if ($branchId) {
                $custSql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $custParams[] = $branchId;
            }
            $stmt = $this->db->prepare($custSql);
            $stmt->execute($custParams);
            $customer = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$customer) throw new Exception("العميل غير موجود.");

            $openingBalance = 0;
            if ($startDate) {
                $invOpenSql = "SELECT COALESCE(SUM(total_amount), 0) FROM sales_invoices WHERE customer_id = ? AND issue_date < ? AND status != 'cancelled' AND company_id = ?";
                $invOpenParams = [$customerId, $startDate, $companyId];
                if ($branchId) {
                    $invOpenSql .= " AND branch_id = ?";
                    $invOpenParams[] = $branchId;
                }
                $invOpen = $this->db->prepare($invOpenSql);
                $invOpen->execute($invOpenParams);

                $recOpenSql = "SELECT COALESCE(SUM(amount), 0) FROM sales_receipts WHERE customer_id = ? AND receipt_date < ? AND company_id = ?";
                $recOpenParams = [$customerId, $startDate, $companyId];
                if ($branchId) {
                    $recOpenSql .= " AND branch_id = ?";
                    $recOpenParams[] = $branchId;
                }
                $recOpen = $this->db->prepare($recOpenSql);
                $recOpen->execute($recOpenParams);

                $retOpenSql = "SELECT COALESCE(SUM(total_amount), 0) FROM sales_returns WHERE customer_id = ? AND return_date < ? AND status != 'cancelled' AND company_id = ?";
                $retOpenParams = [$customerId, $startDate, $companyId];
                if ($branchId) {
                    $retOpenSql .= " AND branch_id = ?";
                    $retOpenParams[] = $branchId;
                }
                $retOpen = $this->db->prepare($retOpenSql);
                $retOpen->execute($retOpenParams);

                $openingBalanceBase = $invOpen->fetchColumn() - ($recOpen->fetchColumn() + $retOpen->fetchColumn());
                $openingBalance = convert_amount($openingBalanceBase);
            }

            $branchInvClause = $branchId ? " AND branch_id = :branch1" : "";
            $branchRecClause = $branchId ? " AND branch_id = :branch2" : "";
            $branchRetClause = $branchId ? " AND branch_id = :branch3" : "";

            $sql = "
                SELECT 
                    CONVERT('invoice' USING utf8mb4) as tx_type, 
                    id, 
                    CONVERT(invoice_number USING utf8mb4) as doc_number, 
                    issue_date as tx_date, 
                    total_amount as debit, 
                    0.00 as credit, 
                    CONVERT(COALESCE(notes, '') USING utf8mb4) as notes, 
                    created_at
                FROM sales_invoices 
                WHERE customer_id = :cid1 AND status != 'cancelled' AND company_id = :comp1 {$branchInvClause}
                " . ($startDate ? " AND issue_date >= :sdate1" : "") . "
                " . ($endDate ? " AND issue_date <= :edate1" : "") . "

                UNION ALL

                SELECT 
                    CONVERT('receipt' USING utf8mb4) as tx_type, 
                    id, 
                    CONVERT(receipt_number USING utf8mb4) as doc_number, 
                    receipt_date as tx_date, 
                    0.00 as debit, 
                    amount as credit, 
                    CONVERT(COALESCE(notes, '') USING utf8mb4) as notes, 
                    created_at
                FROM sales_receipts 
                WHERE customer_id = :cid2 AND company_id = :comp2 {$branchRecClause}
                " . ($startDate ? " AND receipt_date >= :sdate2" : "") . "
                " . ($endDate ? " AND receipt_date <= :edate2" : "") . "

                UNION ALL

                SELECT 
                    CONVERT('return' USING utf8mb4) as tx_type, 
                    id, 
                    CONVERT(return_number USING utf8mb4) as doc_number, 
                    return_date as tx_date, 
                    0.00 as debit, 
                    total_amount as credit, 
                    CONVERT(COALESCE(reason, '') USING utf8mb4) as notes, 
                    created_at
                FROM sales_returns 
                WHERE customer_id = :cid3 AND status != 'cancelled' AND company_id = :comp3 {$branchRetClause}
                " . ($startDate ? " AND return_date >= :sdate3" : "") . "
                " . ($endDate ? " AND return_date <= :edate3" : "") . "

                ORDER BY tx_date ASC, created_at ASC
            ";

            $stmtTx = $this->db->prepare($sql);
            $params = [
                'cid1' => $customerId, 'comp1' => $companyId,
                'cid2' => $customerId, 'comp2' => $companyId,
                'cid3' => $customerId, 'comp3' => $companyId
            ];

            if ($branchId) {
                $params['branch1'] = $branchId;
                $params['branch2'] = $branchId;
                $params['branch3'] = $branchId;
            }

            if ($startDate) { $params['sdate1'] = $startDate; $params['sdate2'] = $startDate; $params['sdate3'] = $startDate; }
            if ($endDate)   { $params['edate1'] = $endDate;   $params['edate2'] = $endDate;   $params['edate3'] = $endDate; }

            $stmtTx->execute($params);
            $rawTransactions = $stmtTx->fetchAll(PDO::FETCH_OBJ);

            $runningBalance = $openingBalance;
            $totalDebit   = 0;
            $totalCredit  = 0;
            $transactions = [];

            foreach ($rawTransactions as $tx) {
                $debit  = convert_amount((float)$tx->debit);
                $credit = convert_amount((float)$tx->credit);
                
                $totalDebit   += $debit;
                $totalCredit  += $credit;
                $runningBalance += ($debit - $credit);

                $tx->debit = $debit;
                $tx->credit = $credit;
                $tx->running_balance = $runningBalance;
                $transactions[] = $tx;
            }

            $closingBalance = $runningBalance;

        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/statements');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/statements/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}