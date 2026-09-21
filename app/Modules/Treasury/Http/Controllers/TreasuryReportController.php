<?php
// Path: app/Modules/Treasury/Http/Controllers/TreasuryReportController.php

namespace App\Modules\Treasury\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class TreasuryReportController extends Controller
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

    public function cashBook(Request $request, Response $response)
    {
        $accountId = isset($_GET['account_id']) && is_numeric($_GET['account_id']) ? (int)$_GET['account_id'] : null;
        $startDate = !empty($_GET['start_date']) ? trim($_GET['start_date']) : date('Y-m-01');
        $endDate = !empty($_GET['end_date']) ? trim($_GET['end_date']) : date('Y-m-t');
        $search = trim($_GET['search'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $treasuryAccounts = [];
        $selectedAccount = null;
        $movements = [];
        $openingBalance = 0.00;
        $totalIn = 0.00;
        $totalOut = 0.00;
        $closingBalance = 0.00;
        $totalPages = 1;

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condAcc = $this->buildBranchCond('a', $branchId, 'accounts');
            $condRec = $this->buildBranchCond('', $branchId, 'treasury_receipts');
            $condPay = $this->buildBranchCond('', $branchId, 'treasury_payments');
            $condTrf = $this->buildBranchCond('', $branchId, 'treasury_transfers');

            // 1. جلب كافة الخزائن والحسابات المتاحة للفرع
            $joinBranch = $this->hasBranchColumn('accounts') ? "LEFT JOIN sys_branches br ON a.branch_id = br.id" : "";
            $colBranch = $this->hasBranchColumn('accounts') ? "br.name_ar as branch_name" : "'' as branch_name";

            $accStmt = $this->db->query("
                SELECT a.id, a.code, a.name_ar, a.name_en, {$colBranch}
                FROM accounts a
                {$joinBranch}
                WHERE a.company_id = {$companyId} AND a.type = 'asset' AND (a.code LIKE '111%' OR a.name_ar LIKE '%خزينة%' OR a.name_ar LIKE '%بنك%' OR a.name_ar LIKE '%صندوق%') {$condAcc}
                ORDER BY a.code ASC
            ");
            $treasuryAccounts = $accStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            if (!$accountId && !empty($treasuryAccounts)) {
                $accountId = (int)$treasuryAccounts[0]->id;
            }

            if ($accountId) {
                // 2. جلب الحساب المالي المختار
                $selStmt = $this->db->prepare("SELECT * FROM accounts WHERE id = ? AND company_id = ?");
                $selStmt->execute([$accountId, $companyId]);
                $selectedAccount = $selStmt->fetch(PDO::FETCH_OBJ) ?: null;

                // 3. احتساب الرصيد الافتتاحي التراكمي قبل تاريخ البداية
                $inBefore = (float)$this->fetchSum("SELECT COALESCE(SUM(amount), 0) FROM treasury_receipts WHERE treasury_account_id = ? AND receipt_date < ? AND company_id = ? {$condRec}", [$accountId, $startDate, $companyId]);
                $outBefore = (float)$this->fetchSum("SELECT COALESCE(SUM(amount), 0) FROM treasury_payments WHERE treasury_account_id = ? AND payment_date < ? AND company_id = ? {$condPay}", [$accountId, $startDate, $companyId]);
                $trfInBefore = (float)$this->fetchSum("SELECT COALESCE(SUM(amount), 0) FROM treasury_transfers WHERE to_account_id = ? AND transfer_date < ? AND company_id = ? {$condTrf}", [$accountId, $startDate, $companyId]);
                $trfOutBefore = (float)$this->fetchSum("SELECT COALESCE(SUM(amount), 0) FROM treasury_transfers WHERE from_account_id = ? AND transfer_date < ? AND company_id = ? {$condTrf}", [$accountId, $startDate, $companyId]);

                $openingBalance = ($inBefore + $trfInBefore) - ($outBefore + $trfOutBefore);

                // 4. استعلام موحد للعمليات خلال الفترة
                $unionSql = "
                    SELECT id, receipt_date as tx_date, voucher_number as ref_no, 'قبض' as tx_type, COALESCE(payer_name, 'عام') as party, amount as in_amount, 0.00 as out_amount, description
                    FROM treasury_receipts 
                    WHERE treasury_account_id = {$accountId} AND company_id = {$companyId} AND receipt_date BETWEEN '{$startDate}' AND '{$endDate}' {$condRec}
                    
                    UNION ALL
                    
                    SELECT id, payment_date as tx_date, voucher_number as ref_no, 'صرف' as tx_type, COALESCE(payee_name, 'عام') as party, 0.00 as in_amount, amount as out_amount, description
                    FROM treasury_payments 
                    WHERE treasury_account_id = {$accountId} AND company_id = {$companyId} AND payment_date BETWEEN '{$startDate}' AND '{$endDate}' {$condPay}
                    
                    UNION ALL
                    
                    SELECT id, transfer_date as tx_date, transfer_number as ref_no, 'تحويل وارد' as tx_type, 'تحويل داخلي' as party, amount as in_amount, 0.00 as out_amount, description
                    FROM treasury_transfers 
                    WHERE to_account_id = {$accountId} AND company_id = {$companyId} AND transfer_date BETWEEN '{$startDate}' AND '{$endDate}' {$condTrf}
                    
                    UNION ALL
                    
                    SELECT id, transfer_date as tx_date, transfer_number as ref_no, 'تحويل صادر' as tx_type, 'تحويل داخلي' as party, 0.00 as in_amount, amount as out_amount, description
                    FROM treasury_transfers 
                    WHERE from_account_id = {$accountId} AND company_id = {$companyId} AND transfer_date BETWEEN '{$startDate}' AND '{$endDate}' {$condTrf}
                ";

                $whereSearch = "";
                if ($search !== '') {
                    $quoted = $this->db->quote("%{$search}%");
                    $whereSearch = "WHERE ref_no LIKE {$quoted} OR party LIKE {$quoted} OR description LIKE {$quoted}";
                }

                // حساب الإجماليات والترقيم
                $totalsObj = $this->db->query("SELECT COALESCE(SUM(in_amount), 0) as total_in, COALESCE(SUM(out_amount), 0) as total_out FROM ({$unionSql}) as all_tx {$whereSearch}")->fetch(PDO::FETCH_OBJ);
                $totalIn = (float)($totalsObj->total_in ?? 0);
                $totalOut = (float)($totalsObj->total_out ?? 0);
                $closingBalance = $openingBalance + $totalIn - $totalOut;

                $totalCount = (int)$this->db->query("SELECT COUNT(*) FROM ({$unionSql}) as all_tx {$whereSearch}")->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->query("
                    SELECT * FROM ({$unionSql}) as all_tx 
                    {$whereSearch}
                    ORDER BY tx_date ASC, id ASC
                    LIMIT {$limit} OFFSET {$offset}
                ");
                $movements = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
            }
        } catch (Throwable $e) {
            error_log("CashBook Controller Error: " . $e->getMessage());
        }

        $currentPage = $page;

        try {
            ob_start();
            include $this->basePath . '/resources/views/treasury/reports/cash_book.php';
            $content = ob_get_clean();

            ob_start();
            include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');
        } catch (Throwable $e) {
            ob_end_clean();
            die("<div style='padding:30px; background:#fff; color:#dc2626; font-family:monospace;' dir='ltr'><h3>Fatal View Render Error:</h3>" . htmlspecialchars($e->getMessage()) . "</div>");
        }
    }

    private function fetchSum($sql, $params = [])
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (float)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0.00;
        }
    }
}