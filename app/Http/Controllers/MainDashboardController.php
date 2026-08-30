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
    private PDO $db;
    private string $basePath;

    public function __construct()
    {
        global $app, $basePath;
        $this->db = $app->get(PDO::class);
        $this->basePath = $basePath ?? dirname(__DIR__, 3);
    }

    public function index(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header("Location: /ERP/login");
            exit;
        }

        $companyId = current_company_id();
        $branchId  = current_branch();
        $currency  = current_currency();
        $locale    = $_SESSION['locale'] ?? 'ar';
        $isAr      = isRtl();
        
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-t'));

        // إعداد شروط الفروع وقاعدة البيانات
        $bCond   = $branchId ? " AND branch_id = $branchId " : "";
        $bJeCond = $branchId ? " AND je.branch_id = $branchId " : "";

        // الاختصارات السريعة
        $shortcuts = [
            ['title_ar' => 'فاتورة بيع', 'title_en' => 'New Invoice', 'icon' => 'ph-receipt', 'link' => '/ERP/sales/invoices/create', 'color' => '#10b981'],
            ['title_ar' => 'أمر شراء', 'title_en' => 'Purchase Order', 'icon' => 'ph-shopping-cart', 'link' => '/ERP/purchases/orders/create', 'color' => '#f59e0b'],
            ['title_ar' => 'سند قبض', 'title_en' => 'Receipt Voucher', 'icon' => 'ph-money', 'link' => '/ERP/sales/receipts/create', 'color' => '#0284c7'],
            ['title_ar' => 'تسجيل عميل', 'title_en' => 'New Customer', 'icon' => 'ph-users', 'link' => '/ERP/sales/customers/create', 'color' => '#6366f1'],
            ['title_ar' => 'مشروع جديد', 'title_en' => 'New Project', 'icon' => 'ph-kanban', 'link' => '/ERP/projects/create', 'color' => '#ec4899'],
            ['title_ar' => 'إذن صرف مخزني', 'title_en' => 'Stock Issue', 'icon' => 'ph-package', 'link' => '/ERP/inventory/delivery-notes/create', 'color' => '#8b5cf6']
        ];

        // مصفوفات البيانات الشاملة
        $kpis = [
            // المالية المباشرة
            'total_sales' => 0, 'total_purchases' => 0, 'expenses' => 0, 'net_profit' => 0,
            'cash_balance' => 0, 'receivables' => 0, 'payables' => 0, 'inventory_value' => 0,
            'fixed_assets_val' => 0, 'allocated_budgets' => 0,
            
            // العمليات والأقسام
            'quotations_count' => 0, 'sales_orders_count' => 0, 'sales_returns_total' => 0,
            'purchase_requests_count' => 0, 'active_projects_count' => 0, 'projects_contract_val' => 0,
            'total_leads_count' => 0, 'converted_leads_count' => 0,
            
            // الموارد البشرية والخزينة
            'active_employees' => 0, 'pending_leaves' => 0, 'payroll_monthly_net' => 0,
            'pending_cheques_val' => 0, 'petty_cash_remaining' => 0
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

        try {
            // 1. المؤشرات المالية الرئيسية (Financial KPIs)
            try { $kpis['total_sales'] = (float)$this->db->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales_invoices WHERE company_id = $companyId $bCond AND issue_date BETWEEN '$startDate' AND '$endDate' AND status != 'cancelled'")->fetchColumn(); } catch(Throwable $e){}
            try { $kpis['total_purchases'] = (float)$this->db->query("SELECT COALESCE(SUM(total_amount), 0) FROM purchase_invoices WHERE company_id = $companyId $bCond AND invoice_date BETWEEN '$startDate' AND '$endDate' AND status != 'cancelled'")->fetchColumn(); } catch(Throwable $e){}
            try { $kpis['expenses'] = (float)$this->db->query("SELECT COALESCE(SUM(jel.debit - jel.credit), 0) FROM journal_entry_items jel JOIN accounts a ON jel.account_id = a.id JOIN journal_entries je ON jel.journal_entry_id = je.id WHERE a.type = 'expense' AND je.status = 'posted' AND je.company_id = $companyId $bJeCond AND je.entry_date BETWEEN '$startDate' AND '$endDate'")->fetchColumn(); } catch(Throwable $e){}
            
            $kpis['net_profit'] = $kpis['total_sales'] - $kpis['expenses'];

            try { $kpis['cash_balance'] = (float)$this->db->query("SELECT COALESCE(SUM(current_balance), 0) FROM accounts WHERE company_id = $companyId AND type = 'asset' AND (code LIKE '111%' OR code LIKE '112%') AND is_active = 1")->fetchColumn(); } catch(Throwable $e){}
            try { $kpis['receivables'] = (float)$this->db->query("SELECT COALESCE(SUM(balance), 0) FROM customers WHERE company_id = $companyId $bCond")->fetchColumn(); } catch(Throwable $e){}
            try { $kpis['payables'] = (float)$this->db->query("SELECT COALESCE(SUM(balance), 0) FROM suppliers WHERE company_id = $companyId $bCond")->fetchColumn(); } catch(Throwable $e){}
            try { $kpis['inventory_value'] = (float)$this->db->query("SELECT COALESCE(SUM(p.sale_price), 0) FROM inv_products p WHERE p.company_id = $companyId AND p.is_active = 1")->fetchColumn(); } catch(Throwable $e){}
            try { $kpis['fixed_assets_val'] = (float)$this->db->query("SELECT COALESCE(SUM(book_value), 0) FROM fixed_assets WHERE company_id = $companyId AND status = 'active'")->fetchColumn(); } catch(Throwable $e){}
            try { $kpis['allocated_budgets'] = (float)$this->db->query("SELECT COALESCE(SUM(total_allocated), 0) FROM budgets WHERE company_id = $companyId AND status = 'approved'")->fetchColumn(); } catch(Throwable $e){}

            // 2. المؤشرات التشغيلية والقطاعية (Operations & Departmental KPIs)
            try { $kpis['quotations_count'] = (int)$this->db->query("SELECT COUNT(*) FROM sales_quotations WHERE company_id = $companyId $bCond AND status IN ('sent', 'draft')")->fetchColumn(); } catch(Throwable $e){}
            try { $kpis['sales_orders_count'] = (int)$this->db->query("SELECT COUNT(*) FROM sales_orders WHERE company_id = $companyId $bCond AND status IN ('confirmed', 'processing')")->fetchColumn(); } catch(Throwable $e){}
            try { $kpis['sales_returns_total'] = (float)$this->db->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales_returns WHERE company_id = $companyId $bCond AND status != 'cancelled'")->fetchColumn(); } catch(Throwable $e){}
            try { $kpis['purchase_requests_count'] = (int)$this->db->query("SELECT COUNT(*) FROM purchase_requests WHERE company_id = $companyId $bCond AND status = 'pending'")->fetchColumn(); } catch(Throwable $e){}
            
            // CRM & Projects & HR
            try { $kpis['total_leads_count'] = (int)$this->db->query("SELECT COUNT(*) FROM crm_leads WHERE company_id = $companyId")->fetchColumn(); } catch(Throwable $e){}
            try { $kpis['converted_leads_count'] = (int)$this->db->query("SELECT COUNT(*) FROM crm_leads WHERE company_id = $companyId AND status = 'converted'")->fetchColumn(); } catch(Throwable $e){}
            try { $kpis['active_projects_count'] = (int)$this->db->query("SELECT COUNT(*) FROM projects WHERE status = 'in_progress'")->fetchColumn(); } catch(Throwable $e){}
            try { $kpis['projects_contract_val'] = (float)$this->db->query("SELECT COALESCE(SUM(contract_value), 0) FROM projects WHERE status = 'in_progress'")->fetchColumn(); } catch(Throwable $e){}
            
            try { $kpis['active_employees'] = (int)$this->db->query("SELECT COUNT(*) FROM hr_employees WHERE status = 'active'")->fetchColumn(); } catch(Throwable $e){}
            try { $kpis['pending_leaves'] = (int)$this->db->query("SELECT COUNT(*) FROM hr_leaves WHERE status = 'pending'")->fetchColumn(); } catch(Throwable $e){}
            try { $kpis['payroll_monthly_net'] = (float)$this->db->query("SELECT COALESCE(SUM(net_pay), 0) FROM hr_payroll WHERE status IN ('processed', 'paid') ORDER BY id DESC LIMIT 1")->fetchColumn(); } catch(Throwable $e){}
            
            try { $kpis['pending_cheques_val'] = (float)$this->db->query("SELECT COALESCE(SUM(amount), 0) FROM treasury_cheques WHERE status = 'pending'")->fetchColumn(); } catch(Throwable $e){}
            try { $kpis['petty_cash_remaining'] = (float)$this->db->query("SELECT COALESCE(SUM(remaining_amount), 0) FROM treasury_petty_cash WHERE status = 'active'")->fetchColumn(); } catch(Throwable $e){}

            // 3. الرسم البياني للـ 6 أشهر الماضية
            for ($i = 5; $i >= 0; $i--) {
                $monthStr = date('Y-m', strtotime("-$i months"));
                $charts['labels'][] = date('M Y', strtotime("-$i months"));

                try { $charts['sales'][] = (float)$this->db->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales_invoices WHERE company_id = $companyId $bCond AND DATE_FORMAT(issue_date, '%Y-%m') = '$monthStr' AND status != 'cancelled'")->fetchColumn(); } catch(Throwable $e) { $charts['sales'][] = 0; }
                try { $charts['purchases'][] = (float)$this->db->query("SELECT COALESCE(SUM(total_amount), 0) FROM purchase_invoices WHERE company_id = $companyId $bCond AND DATE_FORMAT(invoice_date, '%Y-%m') = '$monthStr' AND status != 'cancelled'")->fetchColumn(); } catch(Throwable $e) { $charts['purchases'][] = 0; }
                try { $charts['expenses'][] = (float)$this->db->query("SELECT COALESCE(SUM(jel.debit - jel.credit), 0) FROM journal_entry_items jel JOIN accounts a ON jel.account_id = a.id JOIN journal_entries je ON jel.journal_entry_id = je.id WHERE a.type = 'expense' AND je.company_id = $companyId $bJeCond AND DATE_FORMAT(je.entry_date, '%Y-%m') = '$monthStr'")->fetchColumn(); } catch(Throwable $e) { $charts['expenses'][] = 0; }
                try { $charts['cash_in'][] = (float)$this->db->query("SELECT COALESCE(SUM(amount), 0) FROM sales_receipts WHERE company_id = $companyId $bCond AND DATE_FORMAT(receipt_date, '%Y-%m') = '$monthStr'")->fetchColumn(); } catch(Throwable $e) { $charts['cash_in'][] = 0; }
                try { $charts['cash_out'][] = (float)$this->db->query("SELECT COALESCE(SUM(amount), 0) FROM treasury_payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = '$monthStr'")->fetchColumn(); } catch(Throwable $e) { $charts['cash_out'][] = 0; }
            }

            // توزيع المصروفات حسب الحسابات
            try {
                $expenseGroups = $this->db->query("SELECT a.name_en, a.name_ar, COALESCE(SUM(jel.debit - jel.credit), 0) as total FROM journal_entry_items jel JOIN accounts a ON jel.account_id = a.id JOIN journal_entries je ON jel.journal_entry_id = je.id WHERE a.type = 'expense' AND je.company_id = $companyId $bJeCond AND je.entry_date BETWEEN '$startDate' AND '$endDate' GROUP BY a.id ORDER BY total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($expenseGroups as $eg) { 
                    if($eg['total'] > 0) $charts['expense_categories'][$isAr ? $eg['name_ar'] : $eg['name_en']] = (float)$eg['total']; 
                }
            } catch(Throwable $e){}
            if(empty($charts['expense_categories'])) { $charts['expense_categories'] = [$isAr ? 'مصروفات عمومية' : 'General Expenses' => 1.0]; } 

            // CRM Leads Breakdown
            try {
                $charts['leads_status']['new'] = (int)$this->db->query("SELECT COUNT(*) FROM crm_leads WHERE company_id = $companyId AND status = 'new'")->fetchColumn();
                $charts['leads_status']['contacted'] = (int)$this->db->query("SELECT COUNT(*) FROM crm_leads WHERE company_id = $companyId AND status = 'contacted'")->fetchColumn();
                $charts['leads_status']['converted'] = (int)$this->db->query("SELECT COUNT(*) FROM crm_leads WHERE company_id = $companyId AND status = 'converted'")->fetchColumn();
            } catch(Throwable $e){}

            // 4. التنبيهات والمخاطر (Alerts & Risks)
            try { 
                $alerts['overdue_invoices'] = $this->db->query("SELECT si.invoice_number, c.name_ar, c.name_en, (si.total_amount - si.paid_amount) as remaining, DATEDIFF(CURDATE(), si.due_date) as delay_days FROM sales_invoices si JOIN customers c ON si.customer_id = c.id WHERE si.company_id = $companyId $bCond AND si.due_date < CURDATE() AND si.status IN ('unpaid', 'partially_paid') ORDER BY delay_days DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC); 
            } catch(Throwable $e){}
            
            try {
                $alerts['expiring_docs'] = $this->db->query("SELECT d.title_ar, e.name_ar, e.name_en, d.expiry_date, DATEDIFF(d.expiry_date, CURDATE()) as days_left FROM hr_documents d JOIN hr_employees e ON d.employee_id = e.id WHERE d.expiry_date IS NOT NULL AND d.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY d.expiry_date ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            } catch(Throwable $e){}

            try {
                $alerts['low_stock_products'] = $this->db->query("SELECT sku, name_ar, name_en, sale_price, reorder_level FROM inv_products WHERE company_id = $companyId AND is_active = 1 AND reorder_level > 0 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            } catch(Throwable $e){}

            // 5. الجداول التفصيلية (Operational Tables)
            try { 
                $tables['top_products'] = $this->db->query("SELECT p.name_ar, p.name_en, SUM(sil.quantity) as qty, SUM(sil.total) as revenue FROM sales_invoice_lines sil JOIN sales_invoices si ON sil.invoice_id = si.id JOIN inv_products p ON sil.product_id = p.id WHERE si.company_id = $companyId $bCond AND si.status != 'cancelled' GROUP BY p.id ORDER BY revenue DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC); 
            } catch(Throwable $e){}
            
            try {
                $tables['active_projects'] = $this->db->query("SELECT code, name_ar, name_en, contract_value, spent_amount, progress_percent FROM projects WHERE status = 'in_progress' ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            } catch(Throwable $e){}

            try { 
                $tables['recent_transactions'] = $this->db->query("SELECT id, entry_date, entry_number, description as details, total_amount as amount FROM journal_entries WHERE company_id = $companyId $bJeCond ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC); 
            } catch(Throwable $e){}

        } catch (Throwable $e) {
            error_log("Dashboard Master Fetch Error: " . $e->getMessage());
        }

        // تطبيق تحويل العملة الديناميكي على جميع القيم المالية
        $monetaryKeys = [
            'total_sales', 'total_purchases', 'expenses', 'net_profit',
            'cash_balance', 'receivables', 'payables', 'inventory_value',
            'fixed_assets_val', 'allocated_budgets', 'sales_returns_total',
            'projects_contract_val', 'payroll_monthly_net', 'pending_cheques_val', 'petty_cash_remaining'
        ];

        foreach ($monetaryKeys as $kKey) {
            $kpis[$kKey] = convert_amount($kpis[$kKey]);
        }

        foreach ($charts['sales'] as &$val) $val = convert_amount($val);
        foreach ($charts['purchases'] as &$val) $val = convert_amount($val);
        foreach ($charts['expenses'] as &$val) $val = convert_amount($val);
        foreach ($charts['cash_in'] as &$val) $val = convert_amount($val);
        foreach ($charts['cash_out'] as &$val) $val = convert_amount($val);
        foreach ($charts['expense_categories'] as &$val) $val = convert_amount($val);

        foreach ($alerts['overdue_invoices'] as &$inv) $inv['remaining'] = convert_amount($inv['remaining']);
        foreach ($tables['top_products'] as &$prod) $prod['revenue'] = convert_amount($prod['revenue']);
        foreach ($tables['active_projects'] as &$prj) {
            $prj['contract_value'] = convert_amount($prj['contract_value']);
            $prj['spent_amount'] = convert_amount($prj['spent_amount']);
        }
        foreach ($tables['recent_transactions'] as &$trx) $trx['amount'] = convert_amount($trx['amount']);

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
        if (file_exists($layoutPath)) { include $layoutPath; } else { echo $content; }
        
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}