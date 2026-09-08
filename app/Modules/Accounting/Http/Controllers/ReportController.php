<?php
// Path: app/Modules/Accounting/Http/Controllers/ReportController.php

namespace App\Modules\Accounting\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Security\Auth;
use PDO;
use Throwable;

class ReportController extends Controller
{
    private string $basePath;
    private ?PDO $db = null;

    public function __construct()
    {
        ini_set('display_errors', '1');
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

    private function getCompanyId(): int
    {
        if (function_exists('current_company_id')) return current_company_id();
        if (function_exists('current_company')) {
            $cid = current_company();
            if ($cid) return (int)$cid;
        }
        if (session_status() === PHP_SESSION_NONE) session_start();
        return (int)($_SESSION['company_id'] ?? 1);
    }

    private function getBranches(int $companyId): array
    {
        if (!$this->db) return [];
        try {
            $branches = $this->db->query("SELECT id, name_ar, name_ar as name_en FROM sys_branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ);
            if (!empty($branches)) return $branches;
            return $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    private function catchError(Throwable $e, Response $response): Response
    {
        while (ob_get_level() > 0) ob_end_clean();
        $html = "<div style='padding:30px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:12px; margin:20px;' dir='ltr'>
            <h2>🚨 خطأ تقني في استعلام التقرير:</h2>
            <strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>
            <strong>File:</strong> " . $e->getFile() . "<br>
            <strong>Line:</strong> " . $e->getLine() . "
        </div>";
        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    // 1. دفتر الأستاذ العام
    public function ledger(Request $request, Response $response): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_reports_view');

            $companyId = $this->getCompanyId();
            $accountId = (int)$request->input('account_id', 0);
            $branchId  = (int)$request->input('branch_id', 0);
            $startDate = $request->input('start_date', date('Y-01-01'));
            $endDate   = $request->input('end_date', date('Y-12-31'));
            $search    = trim((string)$request->input('search', ''));

            $accounts = []; $branches = $this->getBranches($companyId);
            $transactions = []; $selectedAccount = null;
            $openingBalance = 0.0; $totalDebit = 0.0; $totalCredit = 0.0;

            if ($this->db) {
                $accounts = $this->db->query("SELECT id, code, name_ar, name_en FROM accounts WHERE company_id = $companyId AND is_control_account = 0 ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

                if ($accountId > 0) {
                    $currStmt = $this->db->prepare("SELECT id, code, name_ar, name_en, opening_balance FROM accounts WHERE id = ?");
                    $currStmt->execute([$accountId]);
                    $selectedAccount = $currStmt->fetch(PDO::FETCH_OBJ);

                    if ($selectedAccount) {
                        $branchCond = $branchId > 0 ? " AND je.branch_id = $branchId" : "";

                        $opStmt = $this->db->prepare("
                            SELECT COALESCE(SUM(ji.debit),0) as dr, COALESCE(SUM(ji.credit),0) as cr
                            FROM journal_entry_items ji 
                            JOIN journal_entries je ON ji.journal_entry_id = je.id 
                            JOIN accounts a ON ji.account_id = a.id
                            WHERE ji.account_id = ? AND je.status = 'posted' AND a.company_id = ? AND je.entry_date < ? $branchCond
                        ");
                        $opStmt->execute([$accountId, $companyId, $startDate]);
                        $opData = $opStmt->fetch(PDO::FETCH_OBJ);

                        $openingBalance = (float)($selectedAccount->opening_balance ?? 0) + (float)($opData->dr ?? 0) - (float)($opData->cr ?? 0);

                        $where = ["ji.account_id = ?", "je.status = 'posted'", "a.company_id = ?", "je.entry_date BETWEEN ? AND ?"];
                        $params = [$accountId, $companyId, $startDate, $endDate];

                        if ($branchId > 0) { $where[] = "je.branch_id = ?"; $params[] = $branchId; }
                        if ($search !== '') {
                            $where[] = "(je.entry_number LIKE ? OR ji.description LIKE ?)";
                            $params[] = "%$search%"; $params[] = "%$search%";
                        }

                        $whereSql = "WHERE " . implode(" AND ", $where);
                        $stmt = $this->db->prepare("
                            SELECT je.entry_date as date, je.reference as ref, je.entry_number, je.id as journal_entry_id, ji.description as line_desc, ji.debit, ji.credit
                            FROM journal_entry_items ji 
                            JOIN journal_entries je ON ji.journal_entry_id = je.id
                            JOIN accounts a ON ji.account_id = a.id
                            $whereSql ORDER BY je.entry_date ASC, je.id ASC
                        ");
                        $stmt->execute($params);
                        $rawLines = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                        $runningBalance = $openingBalance;
                        foreach ($rawLines as $line) {
                            $totalDebit += (float)$line->debit;
                            $totalCredit += (float)$line->credit;
                            $runningBalance += (float)$line->debit - (float)$line->credit;
                            $line->balance = $runningBalance;
                            $transactions[] = $line;
                        }
                    }
                }
            }

            ob_start(); include $this->basePath . '/resources/views/accounting/reports/ledger.php'; $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');

        } catch (Throwable $e) { return $this->catchError($e, $response); }
    }

    // 2. ميزان المراجعة
    public function trialBalance(Request $request, Response $response): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_reports_view');

            $companyId = $this->getCompanyId();
            $startDate = $request->input('start_date', date('Y-01-01'));
            $endDate   = $request->input('end_date', date('Y-12-31'));
            $branchId  = (int)$request->input('branch_id', 0);

            $tbData = []; $branches = $this->getBranches($companyId);

            if ($this->db) {
                $branchCond = $branchId > 0 ? " AND je.branch_id = $branchId" : "";
                
                $stmt = $this->db->prepare("
                    SELECT a.code, a.name_en, a.name_ar, a.opening_balance,
                           COALESCE(SUM(CASE WHEN je.entry_date < ? THEN ji.debit ELSE 0 END), 0) as op_dr,
                           COALESCE(SUM(CASE WHEN je.entry_date < ? THEN ji.credit ELSE 0 END), 0) as op_cr,
                           COALESCE(SUM(CASE WHEN je.entry_date BETWEEN ? AND ? THEN ji.debit ELSE 0 END), 0) as dr,
                           COALESCE(SUM(CASE WHEN je.entry_date BETWEEN ? AND ? THEN ji.credit ELSE 0 END), 0) as cr
                    FROM accounts a
                    LEFT JOIN journal_entry_items ji ON a.id = ji.account_id
                    LEFT JOIN journal_entries je ON ji.journal_entry_id = je.id AND je.status = 'posted' $branchCond
                    WHERE a.is_control_account = 0 AND a.company_id = ?
                    GROUP BY a.id, a.code, a.name_en, a.name_ar, a.opening_balance
                    HAVING dr > 0 OR cr > 0 OR op_dr > 0 OR op_cr > 0 OR a.opening_balance != 0
                    ORDER BY a.code ASC
                ");
                
                $stmt->execute([$startDate, $startDate, $startDate, $endDate, $startDate, $endDate, $companyId]);
                $tbData = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                foreach ($tbData as $row) {
                    if ($row->opening_balance > 0) $row->op_dr += $row->opening_balance;
                    else $row->op_cr += abs($row->opening_balance);

                    $netEnd = ($row->op_dr - $row->op_cr) + ($row->dr - $row->cr);
                    $row->close_dr = $netEnd > 0 ? $netEnd : 0;
                    $row->close_cr = $netEnd < 0 ? abs($netEnd) : 0;
                }
            }

            ob_start(); include $this->basePath . '/resources/views/accounting/reports/trial_balance.php'; $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');

        } catch (Throwable $e) { return $this->catchError($e, $response); }
    }

    // 3. الميزانية العمومية
    public function balanceSheet(Request $request, Response $response): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_reports_view');

            $companyId = $this->getCompanyId();
            $asOfDate  = $request->input('as_of_date', date('Y-m-d'));
            $branchId  = (int)$request->input('branch_id', 0);

            $currAssets = []; $nonCurrAssets = []; $currLiabilities = []; $nonCurrLiabilities = []; $equity = [];
            $totCurrAssets = 0.0; $totNonCurrAssets = 0.0; $totCurrLiab = 0.0; $totNonCurrLiab = 0.0; $totEquity = 0.0; $currentNetProfit = 0.0;
            $branches = $this->getBranches($companyId);

            if ($this->db) {
                $branchCond = $branchId > 0 ? " AND je.branch_id = $branchId" : "";
                $stmt = $this->db->prepare("
                    SELECT a.code, a.name_ar, a.name_en, a.type, a.opening_balance, COALESCE(SUM(ji.debit - ji.credit), 0) as period_net
                    FROM accounts a
                    LEFT JOIN journal_entry_items ji ON a.id = ji.account_id
                    LEFT JOIN journal_entries je ON ji.journal_entry_id = je.id AND je.status = 'posted' AND je.entry_date <= ? $branchCond
                    WHERE a.company_id = ? AND a.is_control_account = 0
                    GROUP BY a.id, a.code, a.name_ar, a.name_en, a.type, a.opening_balance
                ");
                $stmt->execute([$asOfDate, $companyId]);
                $accounts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                foreach ($accounts as $a) {
                    $bal = (float)$a->opening_balance + (float)$a->period_net;
                    $a->balance = abs($bal);
                    if ($a->type === 'asset') {
                        if (str_starts_with($a->code, '11')) { $currAssets[] = $a; $totCurrAssets += $bal; }
                        else { $nonCurrAssets[] = $a; $totNonCurrAssets += $bal; }
                    } elseif ($a->type === 'liability') {
                        if (str_starts_with($a->code, '21')) { $currLiabilities[] = $a; $totCurrLiab += abs($bal); }
                        else { $nonCurrLiabilities[] = $a; $totNonCurrLiab += abs($bal); }
                    } elseif ($a->type === 'equity') {
                        $equity[] = $a; $totEquity += abs($bal);
                    } elseif (in_array($a->type, ['revenue', 'expense'])) {
                        $currentNetProfit += ($a->type === 'revenue') ? abs($bal) : -$bal;
                    }
                }
            }

            $grandTotalAssets = $totCurrAssets + $totNonCurrAssets;
            $grandTotalLiabAndEquity = $totCurrLiab + $totNonCurrLiab + $totEquity + $currentNetProfit;
            $workingCapital = $totCurrAssets - $totCurrLiab;

            ob_start(); include $this->basePath . '/resources/views/accounting/reports/balance_sheet.php'; $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');

        } catch (Throwable $e) { return $this->catchError($e, $response); }
    }

    // 4. قائمة الدخل
    public function incomeStatement(Request $request, Response $response): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_reports_view');

            $companyId = $this->getCompanyId();
            $startDate = $request->input('start_date', date('Y-01-01'));
            $endDate   = $request->input('end_date', date('Y-12-31'));
            $branchId  = (int)$request->input('branch_id', 0);
            $costCenterId = (int)$request->input('cost_center_id', 0);

            $revenues = []; $expenses = []; $totalRevenue = 0.0; $totalExpense = 0.0;
            $branches = $this->getBranches($companyId);
            $costCenters = [];
            try { $costCenters = $this->db->query("SELECT id, code, name_ar FROM cost_centers WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: []; } catch (Throwable $e) {}

            if ($this->db) {
                $conds = $branchId > 0 ? " AND je.branch_id = $branchId" : "";
                if ($costCenterId > 0) $conds .= " AND ji.cost_center_id = $costCenterId";

                $stmt = $this->db->prepare("
                    SELECT a.code, a.name_ar, a.name_en, a.type, 
                           SUM(ji.credit - ji.debit) as rev_net, 
                           SUM(ji.debit - ji.credit) as exp_net
                    FROM accounts a 
                    JOIN journal_entry_items ji ON a.id = ji.account_id
                    JOIN journal_entries je ON ji.journal_entry_id = je.id AND je.status = 'posted' AND je.entry_date BETWEEN ? AND ? $conds
                    WHERE a.company_id = ? AND a.type IN ('revenue', 'expense')
                    GROUP BY a.id, a.code, a.name_ar, a.name_en, a.type ORDER BY a.code ASC
                ");
                $stmt->execute([$startDate, $endDate, $companyId]);
                $items = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                foreach ($items as $it) {
                    if ($it->type === 'revenue') {
                        $it->balance = (float)$it->rev_net;
                        $totalRevenue += $it->balance;
                        $revenues[] = $it;
                    } else {
                        $it->balance = (float)$it->exp_net;
                        $totalExpense += $it->balance;
                        $expenses[] = $it;
                    }
                }
            }

            $netIncome = $totalRevenue - $totalExpense;
            $netMargin = $totalRevenue > 0 ? round(($netIncome / $totalRevenue) * 100, 2) : 0;

            ob_start(); include $this->basePath . '/resources/views/accounting/reports/income_statement.php'; $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');

        } catch (Throwable $e) { return $this->catchError($e, $response); }
    }

  // 5. قائمة التدفقات النقدية
    public function cashFlow(Request $request, Response $response): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_reports_view');

            $companyId = $this->getCompanyId();
            $startDate = $request->input('start_date', date('Y-01-01'));
            $endDate   = $request->input('end_date', date('Y-12-31'));
            $branchId  = (int)$request->input('branch_id', 0); // إضافة متغير الفرع

            $operatingItems = []; $investingItems = []; $financingItems = [];
            $netOperating = 0.0; $netInvesting = 0.0; $netFinancing = 0.0;
            
            $branches = $this->getBranches($companyId); // جلب الفروع

            if ($this->db) {
                $branchCond = $branchId > 0 ? " AND je.branch_id = $branchId" : ""; // شرط الفرع

                // تم تغيير journal_entry_lines إلى journal_entry_items
                // وتم تغيير a.code LIKE '110%' إلى '111%' للبنوك و '112%' للخزينة لتطابق قاعدة بياناتك
                $stmt = $this->db->prepare("
                    SELECT ji.debit, ji.credit, je.entry_number, je.entry_date, a.name_ar as acc_name, ji.description as line_desc, je.id as journal_entry_id
                    FROM journal_entry_items ji 
                    JOIN journal_entries je ON ji.journal_entry_id = je.id 
                    JOIN accounts a ON ji.account_id = a.id
                    WHERE a.company_id = ? AND je.status = 'posted' AND je.entry_date BETWEEN ? AND ? 
                    AND (a.code LIKE '111%' OR a.code LIKE '112%') $branchCond
                    ORDER BY je.entry_date ASC
                ");
                $stmt->execute([$companyId, $startDate, $endDate]);
                $items = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                foreach ($items as $it) {
                    $net = (float)$it->debit - (float)$it->credit;
                    $desc = $it->line_desc ?? '';
                    if (mb_strpos($desc, 'أصول') !== false || mb_strpos($desc, 'شراء') !== false) {
                        $investingItems[] = $it;
                        $netInvesting += $net;
                    } elseif (mb_strpos($desc, 'رأس مال') !== false || mb_strpos($desc, 'قرض') !== false) {
                        $financingItems[] = $it;
                        $netFinancing += $net;
                    } else {
                        $operatingItems[] = $it;
                        $netOperating += $net;
                    }
                }
            }

            $netCashChange = $netOperating + $netInvesting + $netFinancing;

            ob_start(); include $this->basePath . '/resources/views/accounting/reports/cash_flow.php'; $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');

        } catch (Throwable $e) { return $this->catchError($e, $response); }
    }

    // 6. الإقرار الضريبي
    public function vatReturn(Request $request, Response $response): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_reports_view');

            $companyId = $this->getCompanyId();
            $startDate = $request->input('start_date', date('Y-01-01'));
            $endDate   = $request->input('end_date', date('Y-12-31'));
            $branchId  = (int)$request->input('branch_id', 0); // إضافة متغير الفرع

            $vatItems = []; $totalOutputVat = 0.0; $totalInputVat = 0.0; $totalWht = 0.0;
            $branches = $this->getBranches($companyId); // جلب الفروع

            if ($this->db) {
                try {
                    $branchCond = $branchId > 0 ? " AND je.branch_id = $branchId" : ""; // شرط الفرع

                    // تم تغيير journal_entry_lines إلى journal_entry_items
                    $stmt = $this->db->prepare("
                        SELECT ji.debit, ji.credit, je.entry_number, je.entry_date, ji.description as line_desc, je.id as journal_entry_id,
                               t.name_ar as tax_code, t.rate as tax_rate, t.tax_type
                        FROM journal_entry_items ji 
                        JOIN journal_entries je ON ji.journal_entry_id = je.id 
                        JOIN taxes t ON ji.account_id = t.account_id 
                        JOIN accounts a ON ji.account_id = a.id
                        WHERE a.company_id = ? AND je.status = 'posted' AND je.entry_date BETWEEN ? AND ? $branchCond
                        ORDER BY je.entry_date ASC
                    ");
                    $stmt->execute([$companyId, $startDate, $endDate]);
                    $vatItems = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                    foreach ($vatItems as $v) {
                        if ($v->tax_type === 'vat') {
                            $totalOutputVat += (float)$v->credit;
                            $totalInputVat += (float)$v->debit;
                        } elseif ($v->tax_type === 'withholding') {
                            $totalWht += ((float)$v->credit - (float)$v->debit);
                        }
                    }
                } catch (Throwable $e) {}
            }

            $netVatPayable = ($totalOutputVat - $totalInputVat) - $totalWht;

            ob_start(); include $this->basePath . '/resources/views/accounting/reports/vat_return.php'; $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');

        } catch (Throwable $e) { return $this->catchError($e, $response); }
    }
}