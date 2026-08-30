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

    public function cashBook(Request $request, Response $response): Response
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
            // 1. جلب كافة الحسابات والخزائن النقدية والبنكية
            $accStmt = $this->db->query("
                SELECT id, code, name_ar 
                FROM accounts 
                WHERE type = 'asset' AND (code LIKE '111%' OR name_ar LIKE '%خزينة%' OR name_ar LIKE '%بنك%' OR name_ar LIKE '%صندوق%') 
                ORDER BY code ASC
            ");
            $treasuryAccounts = $accStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            if (!$accountId && !empty($treasuryAccounts)) {
                $accountId = (int)$treasuryAccounts[0]->id;
            }

            if ($accountId) {
                // 2. جلب الحساب المالي المختار
                $selStmt = $this->db->prepare("SELECT * FROM accounts WHERE id = ?");
                $selStmt->execute([$accountId]);
                $selectedAccount = $selStmt->fetch(PDO::FETCH_OBJ) ?: null;

                // 3. احتساب الرصيد الافتتاحي التراكمي قبل start_date
                $inBefore = (float)$this->fetchSum("SELECT COALESCE(SUM(amount), 0) FROM treasury_receipts WHERE treasury_account_id = ? AND receipt_date < ?", [$accountId, $startDate]);
                $outBefore = (float)$this->fetchSum("SELECT COALESCE(SUM(amount), 0) FROM treasury_payments WHERE treasury_account_id = ? AND payment_date < ?", [$accountId, $startDate]);
                $trfInBefore = (float)$this->fetchSum("SELECT COALESCE(SUM(amount), 0) FROM treasury_transfers WHERE to_account_id = ? AND transfer_date < ?", [$accountId, $startDate]);
                $trfOutBefore = (float)$this->fetchSum("SELECT COALESCE(SUM(amount), 0) FROM treasury_transfers WHERE from_account_id = ? AND transfer_date < ?", [$accountId, $startDate]);

                $openingBalance = ($inBefore + $trfInBefore) - ($outBefore + $trfOutBefore);

                // 4. تجميع حركة الصندوق بجدول موحد للعمليات خلال الفترة
                $unionSql = "
                    SELECT id, receipt_date as tx_date, voucher_number as ref_no, 'قبض' as tx_type, COALESCE(payer_name, 'عام') as party, amount as in_amount, 0.00 as out_amount, description
                    FROM treasury_receipts 
                    WHERE treasury_account_id = {$accountId} AND receipt_date BETWEEN '{$startDate}' AND '{$endDate}'
                    
                    UNION ALL
                    
                    SELECT id, payment_date as tx_date, voucher_number as ref_no, 'صرف' as tx_type, COALESCE(payee_name, 'عام') as party, 0.00 as in_amount, amount as out_amount, description
                    FROM treasury_payments 
                    WHERE treasury_account_id = {$accountId} AND payment_date BETWEEN '{$startDate}' AND '{$endDate}'
                    
                    UNION ALL
                    
                    SELECT id, transfer_date as tx_date, transfer_number as ref_no, 'تحويل وارد' as tx_type, 'تحويل داخلي' as party, amount as in_amount, 0.00 as out_amount, description
                    FROM treasury_transfers 
                    WHERE to_account_id = {$accountId} AND transfer_date BETWEEN '{$startDate}' AND '{$endDate}'
                    
                    UNION ALL
                    
                    SELECT id, transfer_date as tx_date, transfer_number as ref_no, 'تحويل صادر' as tx_type, 'تحويل داخلي' as party, 0.00 as in_amount, amount as out_amount, description
                    FROM treasury_transfers 
                    WHERE from_account_id = {$accountId} AND transfer_date BETWEEN '{$startDate}' AND '{$endDate}'
                ";

                $whereSearch = "";
                if ($search !== '') {
                    $quoted = $this->db->quote("%{$search}%");
                    $whereSearch = "WHERE ref_no LIKE {$quoted} OR party LIKE {$quoted} OR description LIKE {$quoted}";
                }

                // حساب الإجماليات
                $totalsObj = $this->db->query("SELECT COALESCE(SUM(in_amount), 0) as total_in, COALESCE(SUM(out_amount), 0) as total_out FROM ({$unionSql}) as all_tx {$whereSearch}")->fetch(PDO::FETCH_OBJ);
                $totalIn = (float)($totalsObj->total_in ?? 0);
                $totalOut = (float)($totalsObj->total_out ?? 0);
                $closingBalance = $openingBalance + $totalIn - $totalOut;

                // عدد السجلات للترقيم
                $totalCount = (int)$this->db->query("SELECT COUNT(*) FROM ({$unionSql}) as all_tx {$whereSearch}")->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                // استخراج السجلات بحد أقصى 15 عنصر لكل صفحة
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

        // التقاط أي استثناء بداخل العرض لمنع حجب الصفحة
        try {
            ob_start();
            include $this->basePath . '/resources/views/treasury/reports/cash_book.php';
            $content = ob_get_clean();

            ob_start();
            include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');
        } catch (Throwable $e) {
            ob_end_clean();
            $errorHtml = "<div style='padding:30px; background:#fff; color:#dc2626; font-family:monospace; direction:ltr;'>
                            <h3>Fatal View Render Error:</h3>
                            <p><b>Message:</b> " . htmlspecialchars($e->getMessage()) . "</p>
                            <p><b>File:</b> " . htmlspecialchars($e->getFile()) . "</p>
                            <p><b>Line:</b> " . $e->getLine() . "</p>
                          </div>";
            return $response->setContent($errorHtml)->setHeader('Content-Type', 'text/html; charset=UTF-8');
        }
    }

    private function fetchSum(string $sql, array $params = []): float
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