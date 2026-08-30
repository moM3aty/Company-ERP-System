<?php
// Path: app/Modules/Accounting/Http/Controllers/IncomeStatementController.php

namespace App\Modules\Accounting\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Security\Auth;
use PDO;
use Exception;
use Throwable;

class IncomeStatementController extends Controller
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
        if (class_exists('\Core\Security\Auth')) {
            Auth::enforce('accounting_reports_view');
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $startDate = trim($_GET['start_date'] ?? date('Y-01-01'));
        $endDate = trim($_GET['end_date'] ?? date('Y-m-d'));
        $costCenterId = trim($_GET['cost_center_id'] ?? '');
        $companyId = current_company() ?? 1;

        try {
            $params = [$companyId, $startDate, $endDate];
            $ccCondition = "";
            
            if ($costCenterId !== '') {
                $ccCondition = " AND ji.cost_center_id = ? ";
                $params[] = (int)$costCenterId;
            }

            $stmt = $this->db->prepare("
                SELECT 
                    a.id, a.code, a.name_ar, a.type,
                    SUM(ji.debit) as total_debit,
                    SUM(ji.credit) as total_credit
                FROM accounts a
                JOIN journal_entry_items ji ON a.id = ji.account_id
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                WHERE a.company_id = ? 
                  AND a.type IN ('revenue', 'expense')
                  AND je.status = 'posted'
                  AND je.entry_date BETWEEN ? AND ?
                  {$ccCondition}
                GROUP BY a.id, a.code, a.name_ar, a.type
                ORDER BY a.code ASC
            ");
            $stmt->execute($params);
            $accountsData = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $revenues = [];
            $expenses = [];
            $totalRevenue = 0;
            $totalExpense = 0;

            foreach ($accountsData as $row) {
                if ($row->type === 'revenue') {
                    $balance = (float)$row->total_credit - (float)$row->total_debit;
                    if ($balance != 0) {
                        $row->balance = $balance;
                        $revenues[] = $row;
                        $totalRevenue += $balance;
                    }
                } elseif ($row->type === 'expense') {
                    $balance = (float)$row->total_debit - (float)$row->total_credit;
                    if ($balance != 0) {
                        $row->balance = $balance;
                        $expenses[] = $row;
                        $totalExpense += $balance;
                    }
                }
            }

            $netIncome = $totalRevenue - $totalExpense;
            $netMargin = $totalRevenue > 0 ? round(($netIncome / $totalRevenue) * 100, 2) : ($netIncome < 0 ? -100 : 0);

            $costCenters = $this->db->query("SELECT id, code, name_ar FROM cost_centers WHERE is_active = 1 AND company_id = {$companyId} ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $revenues = []; $expenses = [];
            $totalRevenue = 0; $totalExpense = 0; $netIncome = 0; $netMargin = 0;
            $costCenters = [];
            $_SESSION['flash_err'] = __('خطأ في استخراج التقرير: ', 'Report extraction error: ') . $e->getMessage();
        }

        header('Content-Type: text/html; charset=utf-8');
        ob_start(); include $this->basePath . '/resources/views/accounting/reports/income_statement.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}