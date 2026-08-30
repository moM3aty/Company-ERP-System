<?php
// Path: app/Modules/Accounting/Http/Controllers/ReportController.php

namespace App\Modules\Accounting\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Security\Auth;
use PDO;

class ReportController extends Controller
{
    private string $basePath;
    private PDO $db;

    public function __construct()
    {
        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
        }
    }

    public function ledger(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_reports_view');

        $pageTitle = __('دفتر الأستاذ العام', 'General Ledger');
        $transactions = [];
        $companyId = current_company() ?? 1;

        try {
            $accountId = $request->input('account_id', 1); 
            
            $stmt = $this->db->prepare("
                SELECT je.entry_date as date, je.reference as ref, jel.description as desc, 
                       jel.debit, jel.credit
                FROM journal_entry_lines jel
                JOIN journal_entries je ON jel.journal_entry_id = je.id
                JOIN accounts a ON jel.account_id = a.id
                WHERE jel.account_id = :acc_id 
                  AND je.status = 'posted' 
                  AND a.company_id = :company_id
                ORDER BY je.entry_date ASC, je.id ASC
            ");
            $stmt->execute(['acc_id' => $accountId, 'company_id' => $companyId]);
            $rawLines = $stmt->fetchAll(PDO::FETCH_OBJ);

            $runningBalance = 0; 
            
            foreach ($rawLines as $line) {
                $runningBalance += (float)$line->debit - (float)$line->credit;
                $line->balance = $runningBalance;
                $transactions[] = $line;
            }

        } catch (\PDOException $e) {
            // Ignore if empty
        }

        ob_start();
        include $this->basePath . '/resources/views/accounting/reports/ledger.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        $html = ob_get_clean();

        return $response->setContent($html)->setHeader('Content-Type', 'text/html');
    }

    public function trialBalance(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_reports_view');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $pageTitle = __('ميزان المراجعة', 'Trial Balance');
        $tbData = [];
        $companyId = current_company() ?? 1;

        try {
            $stmt = $this->db->prepare("
                SELECT a.code, a.name_en as name, a.name_ar as name_ar, 
                       'detail' as type,
                       0 as open_dr, 0 as open_cr, 
                       SUM(jel.debit) as dr, SUM(jel.credit) as cr,
                       SUM(jel.debit) as close_dr, SUM(jel.credit) as close_cr
                FROM accounts a
                LEFT JOIN journal_entry_lines jel ON a.id = jel.account_id
                LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id AND je.status = 'posted'
                WHERE a.is_control_account = 0 AND a.company_id = ?
                GROUP BY a.id, a.code, a.name_en, a.name_ar
                HAVING dr > 0 OR cr > 0
                ORDER BY a.code ASC
            ");
            $stmt->execute([$companyId]);
            $tbData = $stmt->fetchAll(PDO::FETCH_OBJ);

        } catch (\PDOException $e) {
            // Ignore
        }

        ob_start();
        $viewPath = $this->basePath . '/resources/views/accounting/reports/trial_balance.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo "<div style='padding:40px; text-align:center; color:red; font-weight:bold;'>" . __('ملف العرض غير موجود: ', 'View not found: ') . "{$viewPath}</div>";
        }
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        $html = ob_get_clean();

        return $response->setContent($html)->setHeader('Content-Type', 'text/html');
    }
}