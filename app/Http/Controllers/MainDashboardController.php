<?php
// Path: app/Http/Controllers/MainDashboardController.php

namespace App\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use PDO;
use Throwable;

class MainDashboardController extends Controller
{
    private $db;
    private $basePath;

    public function __construct()
    {
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        global $app, $basePath;
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            if ($this->db) {
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        }
        $this->basePath = isset($basePath) ? $basePath : dirname(__DIR__, 3);
    }

    private function getCompanyId()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (function_exists('current_company_id')) {
            return (int)current_company_id();
        }
        return (int)($_SESSION['company_id'] ?? 1);
    }

    private function getActiveBranchId()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (function_exists('current_branch')) {
            return (int)current_branch();
        }
        return (int)($_SESSION['branch_id'] ?? 0);
    }

    private function hasColumn($table, $column)
    {
        if (!$this->db) return false;
        try {
            $this->db->query("SELECT {$column} FROM {$table} LIMIT 1");
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function buildBranchCond($tableAlias, $branchId, $tableName)
    {
        if ($branchId <= 0) return "";
        if (!$this->hasColumn($tableName, 'branch_id')) return "";
        $col = $tableAlias ? "{$tableAlias}.branch_id" : "branch_id";
        return " AND ({$col} = {$branchId} OR {$col} = 0 OR {$col} IS NULL)";
    }

    public function index(Request $request, Response $response)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header("Location: /ERP/login");
            exit;
        }

        $companyId = $this->getCompanyId();
        $branchId  = $this->getActiveBranchId();
        $currency  = function_exists('current_currency') ? current_currency() : ($_SESSION['currency'] ?? 'EGP');
        $locale    = $_SESSION['locale'] ?? 'ar';
        $isAr      = function_exists('isRtl') ? isRtl() : ($locale === 'ar');

        $convert = function($amt) {
            return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt;
        };

        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-t'));

        // إعداد شروط الفروع والمؤسسات بمرونة
        $bCond   = $this->buildBranchCond('', $branchId, 'sales_invoices');
        $bJeCond = $this->buildBranchCond('je', $branchId, 'journal_entries');

        // الاختصارات السريعة
        $shortcuts = [
            ['title_ar' => 'فاتورة بيع', 'title_en' => 'New Invoice', 'icon' => 'ph-receipt', 'link' => '/ERP/sales/invoices/create', 'color' => '#10b981'],
            ['title_ar' => 'أمر شراء', 'title_en' => 'Purchase Order', 'icon' => 'ph-shopping-cart', 'link' => '/ERP/purchases/orders/create', 'color' => '#f59e0b'],
            ['title_ar' => 'سند قبض', 'title_en' => 'Receipt Voucher', 'icon' => 'ph-money', 'link' => '/ERP/treasury/receipts/create', 'color' => '#0284c7'],
            ['title_ar' => 'تسجيل عميل', 'title_en' => 'New Customer', 'icon' => 'ph-users', 'link' => '/ERP/sales/customers/create', 'color' => '#6366f1'],
            ['title_ar' => 'مشروع جديد', 'title_en' => 'New Project', 'icon' => 'ph-kanban', 'link' => '/ERP/projects/create', 'color' => '#ec4899'],
            ['title_ar' => 'إذن صرف مخزني', 'title_en' => 'Stock Issue', 'icon' => 'ph-package', 'link' => '/ERP/inventory/delivery-notes/create', 'color' => '#8b5cf6']
        ];

        // مصفوفات البيانات الشاملة للمؤشرات
        $kpis = [
            // المالية المباشرة
            'total_sales' => 0.0, 'total_purchases' => 0.0, 'expenses' => 0.0, 'net_profit' => 0.0,
            'cash_balance' => 0.0, 'receivables' => 0.0, 'payables' => 0.0, 'inventory_value' => 0.0,
            'fixed_assets_val' => 0.0, 'allocated_budgets' => 0.0,
            
            // العمليات والأقسام
            'quotations_count' => 0, 'sales_orders_count' => 0, 'sales_returns_total' => 0.0,
            'purchase_requests_count' => 0, 'active_projects_count' => 0, 'projects_contract_val' => 0.0,
            'total_leads_count' => 0, 'converted_leads_count' => 0,
            
            // الموارد البشرية والخزينة
            'active_employees' => 0, 'pending_leaves' => 0, 'payroll_monthly_net' => 0.0,
            'pending_cheques_val' => 0.0, 'petty_cash_remaining' => 0.0
        ];

        $charts = [
            'labels' => [], 'sales' => [], 'purchases' => [], 'expenses' => [],
            'cash_in' => [], 'cash_out' => [], 'expense_categories' => [],
            'leads_status' => ['new' => 0, 'contacted' => 0, 'converted' => 0]
        ];

        $alerts = [
            'overdue_invoices' => [],
            'expiring_docs' => [],
            'low_stock_products' => []
        ];

        $tables = [
            'top_products' => [],
            'active_projects' => [],
            'recent_transactions' => []
        ];

        if ($this->db) {
            try {
                // 1. المؤشرات المالية الرئيسية (Financial KPIs)
                $condSI  = $this->buildBranchCond('si', $branchId, 'sales_invoices');
                $condPI  = $this->buildBranchCond('pi', $branchId, 'purchase_invoices');
                $condCust= $this->buildBranchCond('c', $branchId, 'customers');
                $condSup = $this->buildBranchCond('s', $branchId, 'suppliers');
                $condAcc = $this->buildBranchCond('a', $branchId, 'accounts');
                $condPrj = $this->buildBranchCond('p', $branchId, 'projects');
                $condSR  = $this->buildBranchCond('sr', $branchId, 'sales_returns');
                $condPR  = $this->buildBranchCond('pr', $branchId, 'purchase_requests');

                try { 
                    $kpis['total_sales'] = (float)$this->db->query("SELECT COALESCE(SUM(si.total_amount), 0) FROM sales_invoices si WHERE si.company_id = {$companyId} {$condSI} AND si.issue_date BETWEEN '{$startDate}' AND '{$endDate}' AND si.status != 'cancelled'")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $kpis['total_purchases'] = (float)$this->db->query("SELECT COALESCE(SUM(pi.total_amount), 0) FROM purchase_invoices pi WHERE pi.company_id = {$companyId} {$condPI} AND pi.invoice_date BETWEEN '{$startDate}' AND '{$endDate}' AND pi.status != 'cancelled'")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $kpis['expenses'] = (float)$this->db->query("SELECT COALESCE(SUM(jel.debit - jel.credit), 0) FROM journal_entry_items jel JOIN accounts a ON jel.account_id = a.id JOIN journal_entries je ON jel.journal_entry_id = je.id WHERE a.type = 'expense' AND je.status = 'posted' AND je.company_id = {$companyId} {$bJeCond} AND je.entry_date BETWEEN '{$startDate}' AND '{$endDate}'")->fetchColumn(); 
                } catch(Throwable $e){}
                
                $kpis['net_profit'] = $kpis['total_sales'] - $kpis['expenses'];

                try { 
                    $kpis['cash_balance'] = (float)$this->db->query("SELECT COALESCE(SUM(a.current_balance), 0) FROM accounts a WHERE a.company_id = {$companyId} {$condAcc} AND a.type = 'asset' AND (a.code LIKE '111%' OR a.code LIKE '112%' OR a.name_ar LIKE '%خزينة%' OR a.name_ar LIKE '%بنك%') AND a.is_active = 1")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $kpis['receivables'] = (float)$this->db->query("SELECT COALESCE(SUM(c.balance), 0) FROM customers c WHERE c.company_id = {$companyId} {$condCust}")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $kpis['payables'] = (float)$this->db->query("SELECT COALESCE(SUM(s.balance), 0) FROM suppliers s WHERE s.company_id = {$companyId} {$condSup}")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $prodTable = $this->hasColumn('products', 'id') ? 'products' : 'inv_products';
                    $priceCol  = $this->hasColumn($prodTable, 'selling_price') ? 'selling_price' : ($this->hasColumn($prodTable, 'sale_price') ? 'sale_price' : 'purchase_price');
                    $condProd  = $this->buildBranchCond('p', $branchId, $prodTable);
                    $kpis['inventory_value'] = (float)$this->db->query("SELECT COALESCE(SUM(p.{$priceCol}), 0) FROM {$prodTable} p WHERE p.company_id = {$companyId} {$condProd} AND p.is_active = 1")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $condFA = $this->buildBranchCond('fa', $branchId, 'fixed_assets');
                    $kpis['fixed_assets_val'] = (float)$this->db->query("SELECT COALESCE(SUM(fa.book_value), 0) FROM fixed_assets fa WHERE fa.company_id = {$companyId} {$condFA} AND fa.status = 'active'")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $condB = $this->buildBranchCond('b', $branchId, 'budgets');
                    $kpis['allocated_budgets'] = (float)$this->db->query("SELECT COALESCE(SUM(b.total_allocated), 0) FROM budgets b WHERE b.company_id = {$companyId} {$condB} AND b.status = 'approved'")->fetchColumn(); 
                } catch(Throwable $e){}

                // 2. المؤشرات التشغيلية والقطاعية (Operations & Departmental KPIs)
                try { 
                    $condSQ = $this->buildBranchCond('sq', $branchId, 'sales_quotations');
                    $kpis['quotations_count'] = (int)$this->db->query("SELECT COUNT(*) FROM sales_quotations sq WHERE sq.company_id = {$companyId} {$condSQ} AND sq.status IN ('sent', 'draft', 'accepted')")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $condSO = $this->buildBranchCond('so', $branchId, 'sales_orders');
                    $kpis['sales_orders_count'] = (int)$this->db->query("SELECT COUNT(*) FROM sales_orders so WHERE so.company_id = {$companyId} {$condSO} AND so.status IN ('confirmed', 'processing', 'shipped')")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $kpis['sales_returns_total'] = (float)$this->db->query("SELECT COALESCE(SUM(sr.total_amount), 0) FROM sales_returns sr WHERE sr.company_id = {$companyId} {$condSR} AND sr.status != 'cancelled'")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $kpis['purchase_requests_count'] = (int)$this->db->query("SELECT COUNT(*) FROM purchase_requests pr WHERE pr.company_id = {$companyId} {$condPR} AND pr.status = 'pending'")->fetchColumn(); 
                } catch(Throwable $e){}

                // CRM & Projects & HR & Treasury
                try { 
                    $condLead = $this->buildBranchCond('l', $branchId, 'crm_leads');
                    $kpis['total_leads_count'] = (int)$this->db->query("SELECT COUNT(*) FROM crm_leads l WHERE l.company_id = {$companyId} {$condLead}")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $condLead = $this->buildBranchCond('l', $branchId, 'crm_leads');
                    $kpis['converted_leads_count'] = (int)$this->db->query("SELECT COUNT(*) FROM crm_leads l WHERE l.company_id = {$companyId} {$condLead} AND l.status = 'converted'")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $kpis['active_projects_count'] = (int)$this->db->query("SELECT COUNT(*) FROM projects p WHERE p.company_id = {$companyId} {$condPrj} AND p.status = 'in_progress'")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $kpis['projects_contract_val'] = (float)$this->db->query("SELECT COALESCE(SUM(p.contract_value), 0) FROM projects p WHERE p.company_id = {$companyId} {$condPrj} AND p.status = 'in_progress'")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $condEmp = $this->buildBranchCond('e', $branchId, 'hr_employees');
                    $kpis['active_employees'] = (int)$this->db->query("SELECT COUNT(*) FROM hr_employees e WHERE e.status = 'active' {$condEmp}")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $condLev = $this->buildBranchCond('hl', $branchId, 'hr_leaves');
                    $kpis['pending_leaves'] = (int)$this->db->query("SELECT COUNT(*) FROM hr_leaves hl WHERE hl.status = 'pending' {$condLev}")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $kpis['payroll_monthly_net'] = (float)$this->db->query("SELECT COALESCE(SUM(net_pay), 0) FROM hr_payroll WHERE status IN ('processed', 'paid') ORDER BY id DESC LIMIT 1")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $condChq = $this->buildBranchCond('tc', $branchId, 'treasury_cheques');
                    $kpis['pending_cheques_val'] = (float)$this->db->query("SELECT COALESCE(SUM(tc.amount), 0) FROM treasury_cheques tc WHERE tc.company_id = {$companyId} {$condChq} AND tc.status = 'pending'")->fetchColumn(); 
                } catch(Throwable $e){}

                try { 
                    $condPC = $this->buildBranchCond('tpc', $branchId, 'treasury_petty_cash');
                    $kpis['petty_cash_remaining'] = (float)$this->db->query("SELECT COALESCE(SUM(tpc.remaining_amount), 0) FROM treasury_petty_cash tpc WHERE tpc.company_id = {$companyId} {$condPC} AND tpc.status != 'closed'")->fetchColumn(); 
                } catch(Throwable $e){}

                // 3. الرسم البياني للـ 6 أشهر الماضية
                for ($i = 5; $i >= 0; $i--) {
                    $monthStr = date('Y-m', strtotime("-$i months"));
                    $charts['labels'][] = date('M Y', strtotime("-$i months"));

                    try { 
                        $charts['sales'][] = (float)$this->db->query("SELECT COALESCE(SUM(si.total_amount), 0) FROM sales_invoices si WHERE si.company_id = {$companyId} {$condSI} AND DATE_FORMAT(si.issue_date, '%Y-%m') = '{$monthStr}' AND si.status != 'cancelled'")->fetchColumn(); 
                    } catch(Throwable $e) { $charts['sales'][] = 0.0; }

                    try { 
                        $charts['purchases'][] = (float)$this->db->query("SELECT COALESCE(SUM(pi.total_amount), 0) FROM purchase_invoices pi WHERE pi.company_id = {$companyId} {$condPI} AND DATE_FORMAT(pi.invoice_date, '%Y-%m') = '{$monthStr}' AND pi.status != 'cancelled'")->fetchColumn(); 
                    } catch(Throwable $e) { $charts['purchases'][] = 0.0; }

                    try { 
                        $charts['expenses'][] = (float)$this->db->query("SELECT COALESCE(SUM(jel.debit - jel.credit), 0) FROM journal_entry_items jel JOIN accounts a ON jel.account_id = a.id JOIN journal_entries je ON jel.journal_entry_id = je.id WHERE a.type = 'expense' AND je.company_id = {$companyId} {$bJeCond} AND DATE_FORMAT(je.entry_date, '%Y-%m') = '{$monthStr}'")->fetchColumn(); 
                    } catch(Throwable $e) { $charts['expenses'][] = 0.0; }

                    try { 
                        $condRec = $this->buildBranchCond('tr', $branchId, 'treasury_receipts');
                        $charts['cash_in'][] = (float)$this->db->query("SELECT COALESCE(SUM(tr.amount), 0) FROM treasury_receipts tr WHERE tr.company_id = {$companyId} {$condRec} AND DATE_FORMAT(tr.receipt_date, '%Y-%m') = '{$monthStr}'")->fetchColumn(); 
                    } catch(Throwable $e) { $charts['cash_in'][] = 0.0; }

                    try { 
                        $condPay = $this->buildBranchCond('tp', $branchId, 'treasury_payments');
                        $charts['cash_out'][] = (float)$this->db->query("SELECT COALESCE(SUM(tp.amount), 0) FROM treasury_payments tp WHERE tp.company_id = {$companyId} {$condPay} AND DATE_FORMAT(tp.payment_date, '%Y-%m') = '{$monthStr}'")->fetchColumn(); 
                    } catch(Throwable $e) { $charts['cash_out'][] = 0.0; }
                }

                // توزيع المصروفات حسب الحسابات
                try {
                    $expenseGroups = $this->db->query("SELECT a.name_en, a.name_ar, COALESCE(SUM(jel.debit - jel.credit), 0) as total FROM journal_entry_items jel JOIN accounts a ON jel.account_id = a.id JOIN journal_entries je ON jel.journal_entry_id = je.id WHERE a.type = 'expense' AND je.company_id = {$companyId} {$bJeCond} AND je.entry_date BETWEEN '{$startDate}' AND '{$endDate}' GROUP BY a.id ORDER BY total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($expenseGroups as $eg) { 
                        if ($eg['total'] > 0) {
                            $catName = $isAr ? ($eg['name_ar'] ?: $eg['name_en']) : ($eg['name_en'] ?: $eg['name_ar']);
                            $charts['expense_categories'][$catName] = (float)$eg['total']; 
                        }
                    }
                } catch(Throwable $e){}

                if (empty($charts['expense_categories'])) { 
                    $charts['expense_categories'] = [$isAr ? 'مصروفات عمومية' : 'General Expenses' => 1.0]; 
                } 

                // CRM Leads Breakdown
                try {
                    $condLead = $this->buildBranchCond('l', $branchId, 'crm_leads');
                    $charts['leads_status']['new']       = (int)$this->db->query("SELECT COUNT(*) FROM crm_leads l WHERE l.company_id = {$companyId} {$condLead} AND l.status = 'new'")->fetchColumn();
                    $charts['leads_status']['contacted'] = (int)$this->db->query("SELECT COUNT(*) FROM crm_leads l WHERE l.company_id = {$companyId} {$condLead} AND l.status = 'contacted'")->fetchColumn();
                    $charts['leads_status']['converted'] = (int)$this->db->query("SELECT COUNT(*) FROM crm_leads l WHERE l.company_id = {$companyId} {$condLead} AND l.status = 'converted'")->fetchColumn();
                } catch(Throwable $e){}

                // 4. التنبيهات والمخاطر (Alerts & Risks)
                try { 
                    $alerts['overdue_invoices'] = $this->db->query("SELECT si.invoice_number, c.name_ar, c.name_en, (si.total_amount - si.paid_amount) as remaining, DATEDIFF(CURDATE(), si.due_date) as delay_days FROM sales_invoices si JOIN customers c ON si.customer_id = c.id WHERE si.company_id = {$companyId} {$condSI} AND si.due_date < CURDATE() AND si.status IN ('unpaid', 'partially_paid') ORDER BY delay_days DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC) ?: []; 
                } catch(Throwable $e){}
                
                try {
                    $condDoc = $this->buildBranchCond('e', $branchId, 'hr_employees');
                    $alerts['expiring_docs'] = $this->db->query("SELECT d.title_ar, e.name_ar, e.name_en, d.expiry_date, DATEDIFF(d.expiry_date, CURDATE()) as days_left FROM hr_documents d JOIN hr_employees e ON d.employee_id = e.id WHERE d.expiry_date IS NOT NULL AND d.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) {$condDoc} ORDER BY d.expiry_date ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch(Throwable $e){}

                try {
                    $prodTable = $this->hasColumn('products', 'id') ? 'products' : 'inv_products';
                    $prodName  = $this->hasColumn($prodTable, 'name_ar') ? 'name_ar' : 'name_en';
                    $prodCode  = $this->hasColumn($prodTable, 'item_code') ? 'item_code' : 'sku';
                    $prodPrice = $this->hasColumn($prodTable, 'selling_price') ? 'selling_price' : 'sale_price';
                    $condProd  = $this->buildBranchCond('p', $branchId, $prodTable);
                    
                    $alerts['low_stock_products'] = $this->db->query("SELECT p.{$prodCode} as sku, p.{$prodName} as name_ar, p.{$prodName} as name_en, p.{$prodPrice} as sale_price, p.reorder_level FROM {$prodTable} p WHERE p.company_id = {$companyId} {$condProd} AND p.is_active = 1 AND p.reorder_level > 0 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch(Throwable $e){}

                // 5. الجداول التفصيلية (Operational Tables)
                try { 
                    $tables['top_products'] = $this->db->query("SELECT p.name_ar, p.name_en, SUM(sil.quantity) as qty, SUM(sil.total) as revenue FROM sales_invoice_lines sil JOIN sales_invoices si ON sil.invoice_id = si.id LEFT JOIN products p ON sil.product_id = p.id WHERE si.company_id = {$companyId} {$condSI} AND si.status != 'cancelled' GROUP BY sil.product_id ORDER BY revenue DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC) ?: []; 
                } catch(Throwable $e){}
                
                try {
                    $tables['active_projects'] = $this->db->query("SELECT p.code, p.name_ar, p.name_en, p.contract_value, p.spent_amount, p.progress_percent FROM projects p WHERE p.company_id = {$companyId} {$condPrj} AND p.status = 'in_progress' ORDER BY p.id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch(Throwable $e){}

                try { 
                    $tables['recent_transactions'] = $this->db->query("SELECT je.id, je.entry_date, je.entry_number, je.description as details, je.total_amount as amount FROM journal_entries je WHERE je.company_id = {$companyId} {$bJeCond} ORDER BY je.id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC) ?: []; 
                } catch(Throwable $e){}

            } catch (Throwable $e) {
                error_log("Dashboard Master Fetch Error: " . $e->getMessage());
            }
        }

        // تطبيق تحويل العملة الديناميكي على جميع القيم المالية
        $monetaryKeys = [
            'total_sales', 'total_purchases', 'expenses', 'net_profit',
            'cash_balance', 'receivables', 'payables', 'inventory_value',
            'fixed_assets_val', 'allocated_budgets', 'sales_returns_total',
            'projects_contract_val', 'payroll_monthly_net', 'pending_cheques_val', 'petty_cash_remaining'
        ];

        foreach ($monetaryKeys as $kKey) {
            $kpis[$kKey] = $convert($kpis[$kKey]);
        }

        foreach ($charts['sales'] as &$val) $val = $convert($val);
        foreach ($charts['purchases'] as &$val) $val = $convert($val);
        foreach ($charts['expenses'] as &$val) $val = $convert($val);
        foreach ($charts['cash_in'] as &$val) $val = $convert($val);
        foreach ($charts['cash_out'] as &$val) $val = $convert($val);
        foreach ($charts['expense_categories'] as &$val) $val = $convert($val);

        foreach ($alerts['overdue_invoices'] as &$inv) {
            $inv['remaining'] = $convert($inv['remaining'] ?? 0);
        }
        foreach ($tables['top_products'] as &$prod) {
            $prod['revenue'] = $convert($prod['revenue'] ?? 0);
        }
        foreach ($tables['active_projects'] as &$prj) {
            $prj['contract_value'] = $convert($prj['contract_value'] ?? 0);
            $prj['spent_amount'] = $convert($prj['spent_amount'] ?? 0);
        }
        foreach ($tables['recent_transactions'] as &$trx) {
            $trx['amount'] = $convert($trx['amount'] ?? 0);
        }

        ob_start();
        $viewPath = $this->basePath . '/resources/views/dashboard/index.php';
        if (file_exists($viewPath)) {
            extract([
                'shortcuts' => $shortcuts, 'kpis' => $kpis, 'charts' => $charts,
                'alerts' => $alerts, 'tables' => $tables,
                'applied_filters' => ['start_date' => $startDate, 'end_date' => $endDate],
                'currency' => $currency, 'locale' => $locale, 'isAr' => $isAr
            ]);
            include $viewPath;
        } else {
            echo "Dashboard View missing.";
        }
        $content = ob_get_clean();

        ob_start();
        $layoutPath = $this->basePath . '/resources/views/layouts/app.php';
        if (file_exists($layoutPath)) { 
            include $layoutPath; 
        } else { 
            echo $content; 
        }
        
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}