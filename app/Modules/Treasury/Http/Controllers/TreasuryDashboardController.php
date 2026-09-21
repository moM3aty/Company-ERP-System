<?php
// Path: app/Modules/Treasury/Http/Controllers/TreasuryDashboardController.php

namespace App\Modules\Treasury\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use PDO;
use Throwable;

class TreasuryDashboardController extends Controller
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

    public function index(Request $request, Response $response): Response
    {
        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            return $this->exportExcel($response);
        }

        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        $kpis = [
            'cash_balance'   => 0.00,
            'bank_balance'   => 0.00,
            'today_inflow'   => 0.00,
            'today_outflow'  => 0.00,
            'pending_cheques'=> 0.00,
            'active_custody' => 0.00,
        ];

        $charts = [
            'labels'           => [],
            'inflow'           => [],
            'outflow'          => [],
            'accounts_labels'  => [],
            'accounts_balances'=> [],
            'methods_values'   => [0, 0, 0, 0], // cash, bank_transfer, cheque, pos
            'monthly_labels'   => [],
            'monthly_inflow'   => [],
            'monthly_outflow'  => []
        ];

        $recentTransactions = [];

        // 1. تجهيز أخر 7 أيام للرسم الخطى
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $charts['labels'][] = date('M d', strtotime($date));
            $charts['inflow'][$date] = 0.00;
            $charts['outflow'][$date] = 0.00;
        }

        // 2. تجهيز آخر 6 أشهر للرسم البياني الشهري
        for ($i = 5; $i >= 0; $i--) {
            $mStart = date('Y-m-01', strtotime("-$i months"));
            $mEnd   = date('Y-m-t', strtotime("-$i months"));
            $mLabel = date('M Y', strtotime("-$i months"));

            $charts['monthly_labels'][] = $mLabel;
            $charts['monthly_inflow'][$i] = 0.00;
            $charts['monthly_outflow'][$i] = 0.00;
        }

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condAcc = $this->buildBranchCond('a', $branchId, 'accounts');
            $condRec = $this->buildBranchCond('tr', $branchId, 'treasury_receipts');
            $condPay = $this->buildBranchCond('tp', $branchId, 'treasury_payments');
            $condChq = $this->buildBranchCond('tc', $branchId, 'treasury_cheques');
            $condPC  = $this->buildBranchCond('tpc', $branchId, 'treasury_petty_cash');

            // أ. أرقام المؤشرات الرئيسية
            $kpis['cash_balance'] = (float) $this->db->query("
                SELECT COALESCE(SUM(current_balance), 0) 
                FROM accounts a
                WHERE type = 'asset' AND (code LIKE '1111%' OR name_ar LIKE '%خزينة%' OR name_ar LIKE '%صندوق%') AND company_id = $companyId $condAcc
            ")->fetchColumn();

            $kpis['bank_balance'] = (float) $this->db->query("
                SELECT COALESCE(SUM(current_balance), 0) 
                FROM accounts a
                WHERE type = 'asset' AND (code LIKE '1112%' OR name_ar LIKE '%بنك%' OR name_en LIKE '%Bank%') AND company_id = $companyId $condAcc
            ")->fetchColumn();

            $today = date('Y-m-d');
            $kpis['today_inflow'] = (float) $this->db->query("
                SELECT COALESCE(SUM(amount), 0) FROM treasury_receipts tr WHERE receipt_date = '{$today}' AND company_id = $companyId $condRec
            ")->fetchColumn();

            $kpis['today_outflow'] = (float) $this->db->query("
                SELECT COALESCE(SUM(amount), 0) FROM treasury_payments tp WHERE payment_date = '{$today}' AND company_id = $companyId $condPay
            ")->fetchColumn();

            try {
                $kpis['pending_cheques'] = (float) $this->db->query("SELECT COALESCE(SUM(amount), 0) FROM treasury_cheques tc WHERE status = 'pending' AND company_id = $companyId $condChq")->fetchColumn();
            } catch (Throwable $e) {}

            try {
                $kpis['active_custody'] = (float) $this->db->query("SELECT COALESCE(SUM(remaining_amount), 0) FROM treasury_petty_cash tpc WHERE status != 'closed' AND company_id = $companyId $condPC")->fetchColumn();
            } catch (Throwable $e) {}

            // ب. Chart 1: التدفقات لآخر 7 أيام
            $sevenDaysAgo = date('Y-m-d', strtotime('-6 days'));
            $receiptsDaily = $this->db->query("SELECT receipt_date as tx_date, SUM(amount) as total FROM treasury_receipts tr WHERE receipt_date >= '{$sevenDaysAgo}' AND company_id = $companyId $condRec GROUP BY receipt_date")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
            $paymentsDaily = $this->db->query("SELECT payment_date as tx_date, SUM(amount) as total FROM treasury_payments tp WHERE payment_date >= '{$sevenDaysAgo}' AND company_id = $companyId $condPay GROUP BY payment_date")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

            foreach ($charts['inflow'] as $d => $val) {
                if (isset($receiptsDaily[$d])) $charts['inflow'][$d] = (float)$receiptsDaily[$d];
                if (isset($paymentsDaily[$d])) $charts['outflow'][$d] = (float)$paymentsDaily[$d];
            }
            $charts['inflow'] = array_values($charts['inflow']);
            $charts['outflow'] = array_values($charts['outflow']);

            // ج. Chart 2: وزن أرصدة الحسابات الرئيسية
            $accountsDist = $this->db->query("
                SELECT name_ar, name_en, current_balance 
                FROM accounts a
                WHERE type = 'asset' AND (code LIKE '111%' OR name_ar LIKE '%خزينة%' OR name_ar LIKE '%بنك%') AND company_id = $companyId $condAcc
                ORDER BY current_balance DESC LIMIT 5
            ")->fetchAll(PDO::FETCH_OBJ);

            if (empty($accountsDist)) {
                // إضافة داتا وهمية (Placeholder) لتجنب اختفاء الرسم البياني الدائري
                $charts['accounts_labels'][] = $isAr ? 'لا يوجد رصيد مسجل' : 'No balance recorded';
                $charts['accounts_balances'][] = 0.0001; 
            } else {
                foreach ($accountsDist as $acc) {
                    $charts['accounts_labels'][] = $isAr ? $acc->name_ar : ($acc->name_en ?: $acc->name_ar);
                    $charts['accounts_balances'][] = (float)$acc->current_balance;
                }
            }

            // د. Chart 3: توزيع طرق الدفع والتحصيل
            $methodsData = $this->db->query("
                SELECT payment_method, SUM(amount) as total 
                FROM treasury_receipts tr
                WHERE company_id = $companyId $condRec
                GROUP BY payment_method
            ")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

            $charts['methods_values'] = [
                (float)($methodsData['cash'] ?? 0),
                (float)($methodsData['bank_transfer'] ?? 0),
                (float)($methodsData['cheque'] ?? 0),
                (float)($methodsData['pos'] ?? 0)
            ];

            // هـ. Chart 4: المقارنة الشهرية لآخر 6 أشهر
            for ($i = 5; $i >= 0; $i--) {
                $mStart = date('Y-m-01', strtotime("-$i months"));
                $mEnd   = date('Y-m-t', strtotime("-$i months"));

                $inVal = (float)$this->db->query("SELECT COALESCE(SUM(amount), 0) FROM treasury_receipts tr WHERE receipt_date BETWEEN '{$mStart}' AND '{$mEnd}' AND company_id = $companyId $condRec")->fetchColumn();
                $outVal = (float)$this->db->query("SELECT COALESCE(SUM(amount), 0) FROM treasury_payments tp WHERE payment_date BETWEEN '{$mStart}' AND '{$mEnd}' AND company_id = $companyId $condPay")->fetchColumn();

                $charts['monthly_inflow'][] = $inVal;
                $charts['monthly_outflow'][] = $outVal;
            }

            // و. أحدث 6 حركات نقدية
            $joinBranchR = $this->hasBranchColumn('treasury_receipts') ? "LEFT JOIN sys_branches br ON tr.branch_id = br.id" : "";
            $joinBranchP = $this->hasBranchColumn('treasury_payments') ? "LEFT JOIN sys_branches bp ON tp.branch_id = bp.id" : "";
            $colBranchR = $this->hasBranchColumn('treasury_receipts') ? "br.name_ar as branch_name" : "'' as branch_name";
            $colBranchP = $this->hasBranchColumn('treasury_payments') ? "bp.name_ar as branch_name" : "'' as branch_name";

            $recentTransactions = $this->db->query("
                (SELECT tr.id, tr.voucher_number as code, tr.receipt_date as tx_date, 'in' as direction, tr.payer_name as party, tr.amount, {$colBranchR} 
                 FROM treasury_receipts tr {$joinBranchR} 
                 WHERE tr.company_id = {$companyId} {$condRec} 
                 ORDER BY tr.id DESC LIMIT 3)
                UNION ALL
                (SELECT tp.id, tp.voucher_number as code, tp.payment_date as tx_date, 'out' as direction, tp.payee_name as party, tp.amount, {$colBranchP} 
                 FROM treasury_payments tp {$joinBranchP} 
                 WHERE tp.company_id = {$companyId} {$condPay} 
                 ORDER BY tp.id DESC LIMIT 3)
                ORDER BY tx_date DESC LIMIT 6
            ")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            error_log("Treasury Dashboard Fetch Error: " . $e->getMessage());
        }

        ob_start();
        include $this->basePath . '/resources/views/treasury/dashboard/index.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';

        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    private function exportExcel(Response $response): Response
    {
        $filename = "Treasury_Executive_" . date('Y-m-d_H-i') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Account Code', 'Account Name', 'Current Balance']);

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();
            $condAcc = $this->buildBranchCond('a', $branchId, 'accounts');

            $accounts = $this->db->query("
                SELECT code, name_ar, current_balance 
                FROM accounts a
                WHERE type = 'asset' AND (code LIKE '111%' OR name_ar LIKE '%خزينة%' OR name_ar LIKE '%بنك%') AND company_id = $companyId $condAcc
                ORDER BY code ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($accounts as $row) {
                fputcsv($output, [$row['code'], $row['name_ar'], number_format((float)$row['current_balance'], 2)]);
            }
        } catch (Throwable $e) {}

        fclose($output);
        exit;
    }
}