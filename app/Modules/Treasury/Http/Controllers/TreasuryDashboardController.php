<?php
// Path: app/Modules/Treasury/Http/Controllers/TreasuryDashboardController.php

namespace App\Modules\Treasury\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Throwable;

class TreasuryDashboardController extends Controller
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

    public function index(Request $request, Response $response): Response
    {
        if ($request->input('export') === 'excel') {
            return $this->exportExcel($response);
        }

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
            'methods_labels'   => ['نقدي', 'تحويل بنكي', 'شيك', 'شبكة / POS'],
            'methods_values'   => [0, 0, 0, 0],
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
            // أ. أرقام المؤشرات الرئيسية
            $kpis['cash_balance'] = (float) $this->db->query("
                SELECT COALESCE(SUM(current_balance), 0) 
                FROM accounts 
                WHERE type = 'asset' AND (code LIKE '1111%' OR name_ar LIKE '%خزينة%' OR name_ar LIKE '%صندوق%')
            ")->fetchColumn();

            $kpis['bank_balance'] = (float) $this->db->query("
                SELECT COALESCE(SUM(current_balance), 0) 
                FROM accounts 
                WHERE type = 'asset' AND (code LIKE '1112%' OR name_ar LIKE '%بنك%' OR name_en LIKE '%Bank%')
            ")->fetchColumn();

            $today = date('Y-m-d');
            $kpis['today_inflow'] = (float) $this->db->query("
                SELECT COALESCE(SUM(amount), 0) FROM treasury_receipts WHERE receipt_date = '{$today}'
            ")->fetchColumn();

            $kpis['today_outflow'] = (float) $this->db->query("
                SELECT COALESCE(SUM(amount), 0) FROM treasury_payments WHERE payment_date = '{$today}'
            ")->fetchColumn();

            try {
                $kpis['pending_cheques'] = (float) $this->db->query("SELECT COALESCE(SUM(amount), 0) FROM treasury_cheques WHERE status = 'pending'")->fetchColumn();
            } catch (Throwable $e) {}

            try {
                $kpis['active_custody'] = (float) $this->db->query("SELECT COALESCE(SUM(remaining_amount), 0) FROM treasury_petty_cash WHERE status != 'closed'")->fetchColumn();
            } catch (Throwable $e) {}

            // ب. Chart 1: التدفقات لآخر 7 أيام
            $sevenDaysAgo = date('Y-m-d', strtotime('-6 days'));
            $receiptsDaily = $this->db->query("SELECT receipt_date as tx_date, SUM(amount) as total FROM treasury_receipts WHERE receipt_date >= '{$sevenDaysAgo}' GROUP BY receipt_date")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
            $paymentsDaily = $this->db->query("SELECT payment_date as tx_date, SUM(amount) as total FROM treasury_payments WHERE payment_date >= '{$sevenDaysAgo}' GROUP BY payment_date")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

            foreach ($charts['inflow'] as $d => $val) {
                if (isset($receiptsDaily[$d])) $charts['inflow'][$d] = (float)$receiptsDaily[$d];
                if (isset($paymentsDaily[$d])) $charts['outflow'][$d] = (float)$paymentsDaily[$d];
            }
            $charts['inflow'] = array_values($charts['inflow']);
            $charts['outflow'] = array_values($charts['outflow']);

            // ج. Chart 2: وزن أرصدة الحسابات الرئيسية
            $accountsDist = $this->db->query("
                SELECT name_ar, current_balance 
                FROM accounts 
                WHERE type = 'asset' AND (code LIKE '111%' OR name_ar LIKE '%خزينة%' OR name_ar LIKE '%بنك%') 
                ORDER BY current_balance DESC LIMIT 5
            ")->fetchAll(PDO::FETCH_OBJ);

            foreach ($accountsDist as $acc) {
                $charts['accounts_labels'][] = $acc->name_ar;
                $charts['accounts_balances'][] = (float)$acc->current_balance;
            }

            // د. Chart 3: توزيع طرق الدفع والتحصيل (Payment Methods)
            $methodsData = $this->db->query("
                SELECT payment_method, SUM(amount) as total 
                FROM treasury_receipts 
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

                $inVal = (float)$this->db->query("SELECT COALESCE(SUM(amount), 0) FROM treasury_receipts WHERE receipt_date BETWEEN '{$mStart}' AND '{$mEnd}'")->fetchColumn();
                $outVal = (float)$this->db->query("SELECT COALESCE(SUM(amount), 0) FROM treasury_payments WHERE payment_date BETWEEN '{$mStart}' AND '{$mEnd}'")->fetchColumn();

                $charts['monthly_inflow'][] = $inVal;
                $charts['monthly_outflow'][] = $outVal;
            }

            // و. أحدث 6 حركات نقدية
            $recentTransactions = $this->db->query("
                (SELECT id, voucher_number as code, receipt_date as tx_date, 'قبض' as type, payer_name as party, amount, 'in' as direction 
                 FROM treasury_receipts ORDER BY id DESC LIMIT 3)
                UNION ALL
                (SELECT id, voucher_number as code, payment_date as tx_date, 'صرف' as type, payee_name as party, amount, 'out' as direction 
                 FROM treasury_payments ORDER BY id DESC LIMIT 3)
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

        fputcsv($output, ['كود الحساب', 'اسم الخزينة / البنك', 'الرصيد الحالي']);

        try {
            $accounts = $this->db->query("
                SELECT code, name_ar, current_balance 
                FROM accounts 
                WHERE type = 'asset' AND (code LIKE '111%' OR name_ar LIKE '%خزينة%' OR name_ar LIKE '%بنك%') 
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