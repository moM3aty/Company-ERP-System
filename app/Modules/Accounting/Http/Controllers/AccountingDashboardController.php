<?php
// Path: app/Modules/Accounting/Http/Controllers/AccountingDashboardController.php

namespace App\Modules\Accounting\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Security\Auth;
use PDO;
use Throwable;

class AccountingDashboardController extends Controller
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
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_dashboard_view');

        $kpis = [
            'revenue' => 0.0,
            'expenses' => 0.0,
            'net_profit' => 0.0,
            'net_margin' => 0.0,
            'drafts' => 0,
            'total_assets' => 0.0,
            'total_liabilities' => 0.0,
            'total_equity' => 0.0,
            'cash_balance' => 0.0,
            'working_capital' => 0.0,
            'pending_recons' => 0
        ];

        $recentEntries = [];
        $charts = [
            'labels' => [],
            'revenue' => [],
            'expense' => [],
            'profit_trend' => [],
            'cash_inflow' => [],
            'cash_outflow' => [],
            'expense_categories' => ['labels' => [], 'data' => []],
            'tax_breakdown' => ['output_vat' => 0.0, 'input_vat' => 0.0, 'wht' => 0.0]
        ];

        // إعداد تسميات آخر 6 شهور
        for ($i = 5; $i >= 0; $i--) {
            $monthLbl = date('M Y', strtotime("-$i months"));
            $charts['labels'][] = $monthLbl;
            $charts['revenue'][] = 0.0;
            $charts['expense'][] = 0.0;
            $charts['profit_trend'][] = 0.0;
            $charts['cash_inflow'][] = 0.0;
            $charts['cash_outflow'][] = 0.0;
        }

        try {
            // دعم تعدد الشركات (فقط إن كان النظام يطبق Multi-Tenancy الكاملة)
            // هنا نكتفي بصلاحيات العرض

            // 1. حساب المؤشرات الرئيسية (KPIs)
            $revStmt = $this->db->query("
                SELECT COALESCE(SUM(ji.credit - ji.debit), 0) 
                FROM journal_entry_items ji 
                JOIN accounts a ON ji.account_id = a.id 
                JOIN journal_entries je ON ji.journal_entry_id = je.id 
                WHERE a.type = 'revenue' AND je.status = 'posted'
            ");
            $kpis['revenue'] = (float)$revStmt->fetchColumn();

            $expStmt = $this->db->query("
                SELECT COALESCE(SUM(ji.debit - ji.credit), 0) 
                FROM journal_entry_items ji 
                JOIN accounts a ON ji.account_id = a.id 
                JOIN journal_entries je ON ji.journal_entry_id = je.id 
                WHERE a.type = 'expense' AND je.status = 'posted'
            ");
            $kpis['expenses'] = (float)$expStmt->fetchColumn();

            $kpis['net_profit'] = $kpis['revenue'] - $kpis['expenses'];
            $kpis['net_margin'] = $kpis['revenue'] > 0 ? round(($kpis['net_profit'] / $kpis['revenue']) * 100, 1) : 0;
            
            $kpis['drafts'] = (int)$this->db->query("SELECT COUNT(*) FROM journal_entries WHERE status = 'draft'")->fetchColumn();
            
            $recCountStmt = $this->db->query("SELECT COUNT(*) FROM bank_reconciliations WHERE status = 'draft'");
            $kpis['pending_recons'] = $recCountStmt ? (int)$recCountStmt->fetchColumn() : 0;

            // الأصول والخصوم والملكية
            $kpis['total_assets'] = (float)$this->db->query("SELECT COALESCE(SUM(current_balance), 0) FROM accounts WHERE type = 'asset'")->fetchColumn();
            $kpis['total_liabilities'] = (float)$this->db->query("SELECT COALESCE(SUM(current_balance), 0) FROM accounts WHERE type = 'liability'")->fetchColumn();
            $kpis['total_equity'] = (float)$this->db->query("SELECT COALESCE(SUM(current_balance), 0) FROM accounts WHERE type = 'equity'")->fetchColumn();
            
            $currAssets = (float)$this->db->query("SELECT COALESCE(SUM(current_balance), 0) FROM accounts WHERE type = 'asset' AND code LIKE '11%'")->fetchColumn();
            $currLiab = (float)$this->db->query("SELECT COALESCE(SUM(current_balance), 0) FROM accounts WHERE type = 'liability' AND code LIKE '21%'")->fetchColumn();
            $kpis['working_capital'] = $currAssets - $currLiab;

            $kpis['cash_balance'] = (float)$this->db->query("SELECT COALESCE(SUM(current_balance), 0) FROM accounts WHERE type = 'asset' AND (code LIKE '1101%' OR code LIKE '1102%' OR name_ar LIKE '%بنك%' OR name_ar LIKE '%صندوق%')")->fetchColumn();

            // 2. حركة الإيرادات والمصروفات الشهرية
            $chartQuery = $this->db->query("
                SELECT 
                    DATE_FORMAT(je.entry_date, '%b %Y') as month_label,
                    a.type,
                    SUM(CASE WHEN a.type = 'revenue' THEN (ji.credit - ji.debit) ELSE (ji.debit - ji.credit) END) as total
                FROM journal_entry_items ji 
                JOIN journal_entries je ON ji.journal_entry_id = je.id 
                JOIN accounts a ON ji.account_id = a.id
                WHERE a.type IN ('revenue', 'expense') AND je.status = 'posted' AND je.entry_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(je.entry_date, '%b %Y'), a.type
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($chartQuery as $row) {
                $idx = array_search($row['month_label'], $charts['labels']);
                if ($idx !== false) {
                    if ($row['type'] === 'revenue') $charts['revenue'][$idx] = (float)$row['total'];
                    else $charts['expense'][$idx] = (float)$row['total'];
                }
            }

            // حساب اتجاه صافي الربح بعد معالجة المصفوفة بدون أخطاء تكرار
            for ($j = 0; $j < count($charts['labels']); $j++) {
                $charts['profit_trend'][$j] = $charts['revenue'][$j] - $charts['expense'][$j];
            }

            // 3. تحليل التدفق النقدي شهرياً
            $cashTrendQuery = $this->db->query("
                SELECT 
                    DATE_FORMAT(je.entry_date, '%b %Y') as month_label,
                    SUM(ji.debit) as inflow,
                    SUM(ji.credit) as outflow
                FROM journal_entry_items ji 
                JOIN journal_entries je ON ji.journal_entry_id = je.id 
                JOIN accounts a ON ji.account_id = a.id
                WHERE (a.code LIKE '1101%' OR a.code LIKE '1102%' OR a.name_ar LIKE '%بنك%' OR a.name_ar LIKE '%صندوق%')
                  AND je.status = 'posted' AND je.entry_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(je.entry_date, '%b %Y')
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($cashTrendQuery as $row) {
                $idx = array_search($row['month_label'], $charts['labels']);
                if ($idx !== false) {
                    $charts['cash_inflow'][$idx] = (float)$row['inflow'];
                    $charts['cash_outflow'][$idx] = (float)$row['outflow'];
                }
            }

            // 4. أكبر 5 بنود للمصروفات
            $expCatQuery = $this->db->query("
                SELECT a.name_ar, SUM(ji.debit - ji.credit) as total
                FROM journal_entry_items ji
                JOIN accounts a ON ji.account_id = a.id
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                WHERE a.type = 'expense' AND je.status = 'posted'
                GROUP BY a.id, a.name_ar
                HAVING total > 0
                ORDER BY total DESC LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($expCatQuery as $cat) {
                $charts['expense_categories']['labels'][] = $cat['name_ar'];
                $charts['expense_categories']['data'][] = (float)$cat['total'];
            }

            // 5. الهيكل الضريبي
            $taxQuery = $this->db->query("
                SELECT 
                    t.tax_type,
                    SUM(ji.debit) as total_debit,
                    SUM(ji.credit) as total_credit
                FROM journal_entry_items ji
                JOIN taxes t ON ji.account_id = t.account_id
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                WHERE je.status = 'posted'
                GROUP BY t.tax_type
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($taxQuery as $tRow) {
                if ($tRow['tax_type'] === 'wht') {
                    $charts['tax_breakdown']['wht'] += ((float)$tRow['total_credit'] - (float)$tRow['total_debit']);
                } else {
                    $charts['tax_breakdown']['output_vat'] += (float)$tRow['total_credit'];
                    $charts['tax_breakdown']['input_vat'] += (float)$tRow['total_debit'];
                }
            }

            // 6. أحدث القيود المسجلة
            $recentEntries = $this->db->query("
                SELECT id, entry_number, entry_date, description, total_amount, status 
                FROM journal_entries ORDER BY id DESC LIMIT 6
            ")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {}

        header('Content-Type: text/html; charset=utf-8');
        ob_start(); include $this->basePath . '/resources/views/accounting/dashboard/index.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}