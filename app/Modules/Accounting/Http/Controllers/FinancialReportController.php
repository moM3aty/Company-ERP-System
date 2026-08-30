<?php
// Path: app/Modules/Accounting/Http/Controllers/FinancialReportController.php

namespace App\Modules\Accounting\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Security\Auth;
use PDO;
use Exception;
use Throwable;

class FinancialReportController extends Controller
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

    private function renderView(string $viewPath, array $data, Response $response): Response
    {
        extract($data);
        header('Content-Type: text/html; charset=utf-8');
        ob_start(); include $this->basePath . '/resources/views/accounting/reports/' . $viewPath . '.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    // 1. الميزانية العمومية المتقدمة (Categorized Balance Sheet)
    public function balanceSheet(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_reports_view');

        $asOfDate = trim($_GET['as_of_date'] ?? date('Y-m-d'));
        $companyId = current_company() ?? 1;

        try {
            $stmt = $this->db->prepare("
                SELECT a.id, a.code, a.name_ar, a.type,
                       COALESCE(SUM(ji.debit), 0) as total_debit, 
                       COALESCE(SUM(ji.credit), 0) as total_credit
                FROM accounts a
                LEFT JOIN journal_entry_items ji ON a.id = ji.account_id
                LEFT JOIN journal_entries je ON ji.journal_entry_id = je.id AND je.status = 'posted' AND je.entry_date <= ? AND je.company_id = ?
                WHERE a.is_parent = 0 AND a.company_id = ?
                GROUP BY a.id, a.code, a.name_ar, a.type
                ORDER BY a.code ASC
            ");
            $stmt->execute([$asOfDate, $companyId, $companyId]);
            $accounts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $currAssets = []; $nonCurrAssets = [];
            $currLiabilities = []; $nonCurrLiabilities = [];
            $equity = [];

            $totCurrAssets = 0; $totNonCurrAssets = 0;
            $totCurrLiab = 0; $totNonCurrLiab = 0; $totEquity = 0;
            $totRev = 0; $totExp = 0;

            foreach ($accounts as $acc) {
                $deb = (float)$acc->total_debit;
                $crd = (float)$acc->total_credit;

                if ($acc->type === 'asset') {
                    $bal = $deb - $crd;
                    if ($bal != 0) {
                        $acc->balance = $bal;
                        if (str_starts_with($acc->code, '11')) {
                            $currAssets[] = $acc; $totCurrAssets += $bal;
                        } else {
                            $nonCurrAssets[] = $acc; $totNonCurrAssets += $bal;
                        }
                    }
                } elseif ($acc->type === 'liability') {
                    $bal = $crd - $deb;
                    if ($bal != 0) {
                        $acc->balance = $bal;
                        if (str_starts_with($acc->code, '21')) {
                            $currLiabilities[] = $acc; $totCurrLiab += $bal;
                        } else {
                            $nonCurrLiabilities[] = $acc; $totNonCurrLiab += $bal;
                        }
                    }
                } elseif ($acc->type === 'equity') {
                    $bal = $crd - $deb;
                    if ($bal != 0) { $acc->balance = $bal; $equity[] = $acc; $totEquity += $bal; }
                } elseif ($acc->type === 'revenue') {
                    $totRev += ($crd - $deb);
                } elseif ($acc->type === 'expense') {
                    $totExp += ($deb - $crd);
                }
            }

            $currentNetProfit = $totRev - $totExp;
            $grandTotalAssets = $totCurrAssets + $totNonCurrAssets;
            $grandTotalLiabAndEquity = $totCurrLiab + $totNonCurrLiab + $totEquity + $currentNetProfit;
            $workingCapital = $totCurrAssets - $totCurrLiab;

        } catch (Throwable $e) {
            $currAssets = []; $nonCurrAssets = []; $currLiabilities = []; $nonCurrLiabilities = []; $equity = [];
            $grandTotalAssets = 0; $grandTotalLiabAndEquity = 0; $currentNetProfit = 0; $workingCapital = 0;
            $totCurrAssets = 0; $totNonCurrAssets = 0; $totCurrLiab = 0; $totNonCurrLiab = 0; $totEquity = 0;
        }

        return $this->renderView('balance_sheet', compact(
            'asOfDate', 'currAssets', 'nonCurrAssets', 'currLiabilities', 'nonCurrLiabilities', 'equity',
            'totCurrAssets', 'totNonCurrAssets', 'totCurrLiab', 'totNonCurrLiab', 'totEquity',
            'grandTotalAssets', 'grandTotalLiabAndEquity', 'currentNetProfit', 'workingCapital'
        ), $response);
    }

    // 2. دفتر الأستاذ العام التفاعلي (Interactive General Ledger)
    public function ledger(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_reports_view');

        $accountId = trim($_GET['account_id'] ?? '');
        $startDate = trim($_GET['start_date'] ?? date('Y-01-01'));
        $endDate = trim($_GET['end_date'] ?? date('Y-m-d'));
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;
        $companyId = current_company() ?? 1;

        try {
            $accounts = $this->db->query("SELECT id, code, name_ar FROM accounts WHERE is_parent = 0 AND company_id = {$companyId} ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);
            
            $selectedAccount = null;
            $items = []; $openingBalance = 0; $totalPages = 1; $totalDebit = 0; $totalCredit = 0;

            if ($accountId !== '') {
                $accStmt = $this->db->prepare("SELECT * FROM accounts WHERE id = ? AND company_id = ?");
                $accStmt->execute([(int)$accountId, $companyId]);
                $selectedAccount = $accStmt->fetch(PDO::FETCH_OBJ);

                if($selectedAccount){
                    // حساب رصيد بداية الفترة
                    $opStmt = $this->db->prepare("
                        SELECT COALESCE(SUM(ji.debit), 0) - COALESCE(SUM(ji.credit), 0) as op_bal
                        FROM journal_entry_items ji
                        JOIN journal_entries je ON ji.journal_entry_id = je.id
                        WHERE ji.account_id = ? AND je.status = 'posted' AND je.entry_date < ? AND je.company_id = ?
                    ");
                    $opStmt->execute([(int)$accountId, $startDate, $companyId]);
                    $openingBalance = (float)($opStmt->fetchColumn() ?: 0);

                    $where = ["ji.account_id = ?", "je.status = 'posted'", "je.company_id = ?", "je.entry_date BETWEEN ? AND ?"];
                    $params = [(int)$accountId, $companyId, $startDate, $endDate];

                    if ($search !== '') {
                        $where[] = "(je.entry_number LIKE ? OR ji.description LIKE ? OR je.description LIKE ?)";
                        $like = "%{$search}%";
                        $params = array_merge($params, [$like, $like, $like]);
                    }

                    $whereSql = "WHERE " . implode(" AND ", $where);

                    $countStmt = $this->db->prepare("SELECT COUNT(*) FROM journal_entry_items ji JOIN journal_entries je ON ji.journal_entry_id = je.id $whereSql");
                    $countStmt->execute($params);
                    $totalPages = max(1, ceil($countStmt->fetchColumn() / $limit));

                    $stmt = $this->db->prepare("
                        SELECT ji.*, je.entry_number, je.entry_date, je.description as entry_desc
                        FROM journal_entry_items ji
                        JOIN journal_entries je ON ji.journal_entry_id = je.id
                        $whereSql ORDER BY je.entry_date ASC, ji.id ASC LIMIT $limit OFFSET $offset
                    ");
                    $stmt->execute($params);
                    $items = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                    $totStmt = $this->db->prepare("
                        SELECT SUM(ji.debit) as t_debit, SUM(ji.credit) as t_credit
                        FROM journal_entry_items ji JOIN journal_entries je ON ji.journal_entry_id = je.id $whereSql
                    ");
                    $totStmt->execute($params);
                    $totRow = $totStmt->fetch(PDO::FETCH_OBJ);
                    $totalDebit = (float)($totRow->t_debit ?? 0);
                    $totalCredit = (float)($totRow->t_credit ?? 0);
                }
            }

        } catch (Throwable $e) {
            $accounts = []; $selectedAccount = null; $items = []; $openingBalance = 0; $totalPages = 1; $totalDebit = 0; $totalCredit = 0;
        }

        $currentPage = $page;
        return $this->renderView('ledger', compact(
            'accounts', 'accountId', 'selectedAccount', 'startDate', 'endDate', 'search', 
            'items', 'openingBalance', 'currentPage', 'totalPages', 'totalDebit', 'totalCredit'
        ), $response);
    }

    // 3. ميزان المراجعة المنضبط (Balanced Trial Balance)
    public function trialBalance(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_reports_view');

        $startDate = trim($_GET['start_date'] ?? date('Y-01-01'));
        $endDate = trim($_GET['end_date'] ?? date('Y-m-d'));
        $search = trim($_GET['search'] ?? '');
        $companyId = current_company() ?? 1;

        try {
            $where = ["a.is_parent = 0", "a.company_id = ?"];
            $params = [$companyId, $startDate, $startDate, $endDate, $startDate, $endDate];

            if ($search !== '') {
                $where[] = "(a.code LIKE ? OR a.name_ar LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $stmt = $this->db->prepare("
                SELECT 
                    a.id, a.code, a.name_ar, a.type,
                    COALESCE(SUM(CASE WHEN je.entry_date < ? THEN (ji.debit - ji.credit) ELSE 0 END), 0) as op_balance,
                    COALESCE(SUM(CASE WHEN je.entry_date BETWEEN ? AND ? THEN ji.debit ELSE 0 END), 0) as period_debit,
                    COALESCE(SUM(CASE WHEN je.entry_date BETWEEN ? AND ? THEN ji.credit ELSE 0 END), 0) as period_credit
                FROM accounts a
                LEFT JOIN journal_entry_items ji ON a.id = ji.account_id
                LEFT JOIN journal_entries je ON ji.journal_entry_id = je.id AND je.status = 'posted' AND je.company_id = a.company_id
                $whereSql
                GROUP BY a.id, a.code, a.name_ar, a.type
                HAVING op_balance != 0 OR period_debit != 0 OR period_credit != 0
                ORDER BY a.code ASC
            ");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $totOpDeb = 0; $totOpCrd = 0; $totPerDeb = 0; $totPerCrd = 0; $totEndDeb = 0; $totEndCrd = 0;

            foreach ($rows as $r) {
                $op = (float)$r->op_balance;
                $pDeb = (float)$r->period_debit;
                $pCrd = (float)$r->period_credit;
                $end = $op + $pDeb - $pCrd;

                $r->op_debit = $op > 0 ? $op : 0;
                $r->op_credit = $op < 0 ? abs($op) : 0;
                $r->end_debit = $end > 0 ? $end : 0;
                $r->end_credit = $end < 0 ? abs($end) : 0;

                $totOpDeb += $r->op_debit; $totOpCrd += $r->op_credit;
                $totPerDeb += $pDeb; $totPerCrd += $pCrd;
                $totEndDeb += $r->end_debit; $totEndCrd += $r->end_credit;
            }

            $isBalanced = (abs($totEndDeb - $totEndCrd) < 0.01);

        } catch (Throwable $e) {
            $rows = []; $totOpDeb = 0; $totOpCrd = 0; $totPerDeb = 0; $totPerCrd = 0; $totEndDeb = 0; $totEndCrd = 0; $isBalanced = true;
        }

        return $this->renderView('trial_balance', compact(
            'startDate', 'endDate', 'search', 'rows', 
            'totOpDeb', 'totOpCrd', 'totPerDeb', 'totPerCrd', 'totEndDeb', 'totEndCrd', 'isBalanced'
        ), $response);
    }

    // 4. تقرير الإقرار الضريبي الشامل (Detailed VAT Return)
    public function vatReturn(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_reports_view');

        $startDate = trim($_GET['start_date'] ?? date('Y-01-01'));
        $endDate = trim($_GET['end_date'] ?? date('Y-m-d'));
        $companyId = current_company() ?? 1;

        try {
            $stmt = $this->db->prepare("
                SELECT ji.*, je.entry_number, je.entry_date, je.description as entry_desc, 
                       t.tax_rate, t.tax_type, t.code as tax_code, t.name_ar as tax_name
                FROM journal_entry_items ji
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                JOIN taxes t ON ji.account_id = t.account_id
                WHERE je.status = 'posted' AND je.company_id = ? AND je.entry_date BETWEEN ? AND ?
                ORDER BY je.entry_date ASC
            ");
            $stmt->execute([$companyId, $startDate, $endDate]);
            $vatItems = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $salesTaxItems = []; $purchaseTaxItems = []; $whtItems = [];
            $totalOutputVat = 0; $totalInputVat = 0; $totalWht = 0;

            foreach ($vatItems as $item) {
                if ($item->tax_type === 'wht') {
                    $whtItems[] = $item;
                    $totalWht += ((float)$item->credit - (float)$item->debit);
                } elseif ((float)$item->credit > 0) {
                    $salesTaxItems[] = $item;
                    $totalOutputVat += (float)$item->credit;
                } else {
                    $purchaseTaxItems[] = $item;
                    $totalInputVat += (float)$item->debit;
                }
            }

            $netVatPayable = $totalOutputVat - $totalInputVat;

        } catch (Throwable $e) {
            $vatItems = []; $salesTaxItems = []; $purchaseTaxItems = []; $whtItems = [];
            $totalOutputVat = 0; $totalInputVat = 0; $totalWht = 0; $netVatPayable = 0;
        }

        return $this->renderView('vat_return', compact(
            'startDate', 'endDate', 'vatItems', 'salesTaxItems', 'purchaseTaxItems', 'whtItems',
            'totalOutputVat', 'totalInputVat', 'totalWht', 'netVatPayable'
        ), $response);
    }

    // 5. قائمة التدفقات النقدية المصنفة (Categorized Cash Flow)
    public function cashFlow(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_reports_view');

        $startDate = trim($_GET['start_date'] ?? date('Y-01-01'));
        $endDate = trim($_GET['end_date'] ?? date('Y-m-d'));
        $companyId = current_company() ?? 1;

        try {
            $stmt = $this->db->prepare("
                SELECT ji.*, je.entry_number, je.entry_date, je.description as entry_desc, 
                       a.name_ar as acc_name, a.code as acc_code
                FROM journal_entry_items ji
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                JOIN accounts a ON ji.account_id = a.id
                WHERE je.status = 'posted' AND je.company_id = ? AND je.entry_date BETWEEN ? AND ?
                  AND (a.code LIKE '1101%' OR a.code LIKE '1102%' OR a.name_ar LIKE '%صندوق%' OR a.name_ar LIKE '%بنك%')
                ORDER BY je.entry_date ASC
            ");
            $stmt->execute([$companyId, $startDate, $endDate]);
            $cashItems = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $operatingItems = []; $investingItems = []; $financingItems = [];
            $totOpIn = 0; $totOpOut = 0; $totInvIn = 0; $totInvOut = 0; $totFinIn = 0; $totFinOut = 0;

            foreach ($cashItems as $item) {
                $deb = (float)$item->debit;
                $crd = (float)$item->credit;
                $desc = $item->description ?: $item->entry_desc;

                if (mb_strpos($desc, 'أصول') !== false || mb_strpos($desc, 'معدات') !== false || mb_strpos($desc, 'سيارات') !== false) {
                    $investingItems[] = $item;
                    $totInvIn += $deb; $totInvOut += $crd;
                } elseif (mb_strpos($desc, 'رأس المال') !== false || mb_strpos($desc, 'قرض') !== false || mb_strpos($desc, 'سحب') !== false) {
                    $financingItems[] = $item;
                    $totFinIn += $deb; $totFinOut += $crd;
                } else {
                    $operatingItems[] = $item;
                    $totOpIn += $deb; $totOpOut += $crd;
                }
            }

            $netOperating = $totOpIn - $totOpOut;
            $netInvesting = $totInvIn - $totInvOut;
            $netFinancing = $totFinIn - $totFinOut;
            $netCashChange = $netOperating + $netInvesting + $netFinancing;

        } catch (Throwable $e) {
            $cashItems = []; $operatingItems = []; $investingItems = []; $financingItems = [];
            $netOperating = 0; $netInvesting = 0; $netFinancing = 0; $netCashChange = 0;
            $totOpIn = 0; $totOpOut = 0; $totInvIn = 0; $totInvOut = 0; $totFinIn = 0; $totFinOut = 0;
        }

        return $this->renderView('cash_flow', compact(
            'startDate', 'endDate', 'cashItems', 'operatingItems', 'investingItems', 'financingItems',
            'netOperating', 'netInvesting', 'netFinancing', 'netCashChange',
            'totOpIn', 'totOpOut', 'totInvIn', 'totInvOut', 'totFinIn', 'totFinOut'
        ), $response);
    }
}