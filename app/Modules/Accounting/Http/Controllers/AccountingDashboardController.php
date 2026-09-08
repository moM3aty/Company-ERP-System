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
    private ?PDO $db = null;

    public function __construct() 
    { 
        ini_set('display_errors', 0);
        error_reporting(E_ALL);

        global $basePath, $app; 
        $this->basePath = $basePath ?? dirname(__DIR__, 4); 
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            if ($this->db) {
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        }
    }

    public function index(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // التحقق من الصلاحيات إن وجدت
        if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) {
            Auth::enforce('accounting_dashboard_view');
        }

        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $branchId  = (int)($_SESSION['branch_id'] ?? 0);
        $dbErrors = [];

        // التأكد من وجود عمود branch_id لتفادي الأخطاء
        $hasBranch = false;
        if ($this->db) {
            try {
                $this->db->query("SELECT branch_id FROM journal_entries LIMIT 1");
                $hasBranch = true;
            } catch (Throwable $e) {}
        }

        $tenantCond = "je.company_id = $companyId";
        if ($branchId > 0 && $hasBranch) {
            $tenantCond .= " AND je.branch_id = $branchId";
        }

        if ($request->input('export') === 'excel') {
            return $this->exportExcel($response, $tenantCond);
        }

        // Fallback آمن لدالة تحويل العملات
        $convert = function($amt) {
            return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt;
        };

        $kpis = [
            'revenue' => 0.0, 'expenses' => 0.0, 'net_profit' => 0.0, 'net_margin' => 0.0,
            'drafts' => 0, 'total_assets' => 0.0, 'total_liabilities' => 0.0, 'total_equity' => 0.0,
            'cash_balance' => 0.0, 'working_capital' => 0.0, 'pending_recons' => 0
        ];

        $charts = [
            'labels' => [], 'revenue' => [], 'expense' => [], 'profit_trend' => [],
            'cash_inflow' => [], 'cash_outflow' => [],
            'expense_categories' => ['labels' => [], 'data' => []],
            'tax_breakdown' => ['output_vat' => 0.0, 'input_vat' => 0.0, 'wht' => 0.0]
        ];

        for ($i = 5; $i >= 0; $i--) {
            $charts['labels'][] = date('M Y', strtotime("-$i months"));
            $charts['revenue'][] = 0.0;
            $charts['expense'][] = 0.0;
            $charts['profit_trend'][] = 0.0;
            $charts['cash_inflow'][] = 0.0;
            $charts['cash_outflow'][] = 0.0;
        }

        $recentEntries = [];

        if ($this->db) {
            try {
                // 1. الإيرادات والمصروفات
                $revStmt = $this->db->query("
                    SELECT COALESCE(SUM(ji.credit - ji.debit), 0) 
                    FROM journal_entry_items ji 
                    JOIN accounts a ON ji.account_id = a.id 
                    JOIN journal_entries je ON ji.journal_entry_id = je.id 
                    WHERE a.type = 'revenue' AND je.status = 'posted' AND $tenantCond
                ");
                $kpis['revenue'] = $convert($revStmt->fetchColumn());

                $expStmt = $this->db->query("
                    SELECT COALESCE(SUM(ji.debit - ji.credit), 0) 
                    FROM journal_entry_items ji 
                    JOIN accounts a ON ji.account_id = a.id 
                    JOIN journal_entries je ON ji.journal_entry_id = je.id 
                    WHERE a.type = 'expense' AND je.status = 'posted' AND $tenantCond
                ");
                $kpis['expenses'] = $convert($expStmt->fetchColumn());

                $kpis['net_profit'] = $kpis['revenue'] - $kpis['expenses'];
                $kpis['net_margin'] = $kpis['revenue'] > 0 ? round(($kpis['net_profit'] / $kpis['revenue']) * 100, 1) : 0;
                
                $kpis['drafts'] = (int)$this->db->query("SELECT COUNT(*) FROM journal_entries WHERE status = 'draft' AND company_id = $companyId")->fetchColumn();
                
                try {
                    $kpis['pending_recons'] = (int)$this->db->query("SELECT COUNT(*) FROM bank_reconciliations WHERE status = 'draft'")->fetchColumn();
                } catch (Throwable $e) {}

                $kpis['total_assets'] = $convert($this->db->query("SELECT COALESCE(SUM(current_balance), 0) FROM accounts WHERE type = 'asset' AND company_id = $companyId")->fetchColumn());
                $kpis['total_liabilities'] = $convert($this->db->query("SELECT COALESCE(SUM(current_balance), 0) FROM accounts WHERE type = 'liability' AND company_id = $companyId")->fetchColumn());
                $kpis['total_equity'] = $convert($this->db->query("SELECT COALESCE(SUM(current_balance), 0) FROM accounts WHERE type = 'equity' AND company_id = $companyId")->fetchColumn());
                
                $currAssets = $convert($this->db->query("SELECT COALESCE(SUM(current_balance), 0) FROM accounts WHERE type = 'asset' AND code LIKE '11%' AND company_id = $companyId")->fetchColumn());
                $currLiab = $convert($this->db->query("SELECT COALESCE(SUM(current_balance), 0) FROM accounts WHERE type = 'liability' AND code LIKE '21%' AND company_id = $companyId")->fetchColumn());
                $kpis['working_capital'] = $currAssets - $currLiab;

                $kpis['cash_balance'] = $convert($this->db->query("SELECT COALESCE(SUM(current_balance), 0) FROM accounts WHERE type = 'asset' AND (code LIKE '1101%' OR code LIKE '1102%' OR name_ar LIKE '%بنك%' OR name_ar LIKE '%صندوق%') AND company_id = $companyId")->fetchColumn());

                // 2. حركة الإيرادات والمصروفات الشهرية
                $chartQuery = $this->db->query("
                    SELECT 
                        DATE_FORMAT(je.entry_date, '%b %Y') as month_label,
                        a.type,
                        SUM(CASE WHEN a.type = 'revenue' THEN (ji.credit - ji.debit) ELSE (ji.debit - ji.credit) END) as total
                    FROM journal_entry_items ji 
                    JOIN journal_entries je ON ji.journal_entry_id = je.id 
                    JOIN accounts a ON ji.account_id = a.id
                    WHERE a.type IN ('revenue', 'expense') AND je.status = 'posted' AND je.entry_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH) AND $tenantCond
                    GROUP BY DATE_FORMAT(je.entry_date, '%b %Y'), a.type
                ")->fetchAll(PDO::FETCH_ASSOC);

                foreach ($chartQuery as $row) {
                    $idx = array_search($row['month_label'], $charts['labels']);
                    if ($idx !== false) {
                        if ($row['type'] === 'revenue') $charts['revenue'][$idx] = $convert($row['total']);
                        else $charts['expense'][$idx] = $convert($row['total']);
                    }
                }

                for ($j = 0; $j < count($charts['labels']); $j++) {
                    $charts['profit_trend'][$j] = $charts['revenue'][$j] - $charts['expense'][$j];
                }

                // 3. التدفق النقدي شهرياً
                $cashTrendQuery = $this->db->query("
                    SELECT 
                        DATE_FORMAT(je.entry_date, '%b %Y') as month_label,
                        SUM(ji.debit) as inflow,
                        SUM(ji.credit) as outflow
                    FROM journal_entry_items ji 
                    JOIN journal_entries je ON ji.journal_entry_id = je.id 
                    JOIN accounts a ON ji.account_id = a.id
                    WHERE (a.code LIKE '1101%' OR a.code LIKE '1102%' OR a.name_ar LIKE '%بنك%' OR a.name_ar LIKE '%صندوق%')
                      AND je.status = 'posted' AND je.entry_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH) AND $tenantCond
                    GROUP BY DATE_FORMAT(je.entry_date, '%b %Y')
                ")->fetchAll(PDO::FETCH_ASSOC);

                foreach ($cashTrendQuery as $row) {
                    $idx = array_search($row['month_label'], $charts['labels']);
                    if ($idx !== false) {
                        $charts['cash_inflow'][$idx] = $convert($row['inflow']);
                        $charts['cash_outflow'][$idx] = $convert($row['outflow']);
                    }
                }

                // 4. تصنيفات المصروفات
                $expCatQuery = $this->db->query("
                    SELECT a.name_ar, a.name_en, SUM(ji.debit - ji.credit) as total
                    FROM journal_entry_items ji
                    JOIN accounts a ON ji.account_id = a.id
                    JOIN journal_entries je ON ji.journal_entry_id = je.id
                    WHERE a.type = 'expense' AND je.status = 'posted' AND $tenantCond
                    GROUP BY a.id, a.name_ar, a.name_en
                    HAVING total > 0
                    ORDER BY total DESC LIMIT 5
                ")->fetchAll(PDO::FETCH_ASSOC);

                $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
                foreach ($expCatQuery as $cat) {
                    $name = $isAr ? ($cat['name_ar'] ?: $cat['name_en']) : ($cat['name_en'] ?: $cat['name_ar']);
                    $charts['expense_categories']['labels'][] = $name;
                    $charts['expense_categories']['data'][] = $convert($cat['total']);
                }

                // 5. الهيكل الضريبي
                try {
                    $taxQuery = $this->db->query("
                        SELECT 
                            t.tax_type,
                            SUM(ji.debit) as total_debit,
                            SUM(ji.credit) as total_credit
                        FROM journal_entry_items ji
                        JOIN taxes t ON ji.account_id = t.account_id
                        JOIN journal_entries je ON ji.journal_entry_id = je.id
                        WHERE je.status = 'posted' AND $tenantCond
                        GROUP BY t.tax_type
                    ")->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($taxQuery as $tRow) {
                        if ($tRow['tax_type'] === 'wht') {
                            $val = ((float)$tRow['total_credit'] - (float)$tRow['total_debit']);
                            $charts['tax_breakdown']['wht'] += $convert($val);
                        } else {
                            $charts['tax_breakdown']['output_vat'] += $convert($tRow['total_credit']);
                            $charts['tax_breakdown']['input_vat'] += $convert($tRow['total_debit']);
                        }
                    }
                } catch (Throwable $e) {}

                // 6. أحدث القيود
                $recentEntries = $this->db->query("
                    SELECT id, entry_number, entry_date, description, total_amount, status 
                    FROM journal_entries je WHERE $tenantCond ORDER BY id DESC LIMIT 6
                ")->fetchAll(PDO::FETCH_OBJ) ?: [];

                foreach ($recentEntries as $re) {
                    $re->total_amount = $convert($re->total_amount);
                }

            } catch (Throwable $e) {
                $dbErrors[] = "SQL Error: " . $e->getMessage();
            }
        } else {
            $dbErrors[] = "Database connection is not available.";
        }

        // =========================================================================
        // إصلاح المشكلة هنا: استخدام طريقة الـ Include التقليدية للحفاظ على المتغيرات
        // لكي يتعرف ملف الـ app.php عليها ويعرض الـ Navbar والـ Sidebar بشكل صحيح
        // =========================================================================
        ob_start();
        $viewPath = $this->basePath . '/resources/views/accounting/dashboard/index.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo "Dashboard view not found!";
        }
        $content = ob_get_clean();

        ob_start(); 
        include $this->basePath . '/resources/views/layouts/app.php';
        $finalHtml = ob_get_clean();

        return $response->setContent($finalHtml)->setHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    private function exportExcel(Response $response, string $tenantCond): Response
    {
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        $filename = "Journal_Entries_Export_" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");

        $headers = $isAr ? 
            ['رقم القيد', 'التاريخ', 'البيان', 'المبلغ', 'الحالة'] :
            ['Entry No', 'Date', 'Description', 'Amount', 'Status'];
        
        fputcsv($output, $headers);

        if ($this->db) {
            try {
                $entries = $this->db->query("SELECT entry_number, entry_date, description, total_amount, status FROM journal_entries je WHERE $tenantCond ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
                
                $convert = function($amt) {
                    return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt;
                };

                foreach ($entries as $e) {
                    fputcsv($output, [
                        $e['entry_number'], 
                        $e['entry_date'],
                        $e['description'],
                        number_format($convert($e['total_amount']), 2, '.', ''),
                        $e['status']
                    ]);
                }
            } catch (Throwable $e) {}
        }

        fclose($output);
        exit;
    }
}