<?php
// Path: app/Modules/Purchasing/Http/Controllers/PurchasingDashboardController.php

namespace App\Modules\Purchasing\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use PDO;
use Exception;
use Throwable;

class PurchasingDashboardController extends Controller
{
    private string $basePath;
    private PDO $db;

    public function __construct()
    {
        ini_set('display_errors', 0);
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
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;
        
        // شروط الفروع
        $bCond     = $branchId > 0 ? " AND branch_id = $branchId " : "";
        $bCondPo   = $branchId > 0 ? " AND po.branch_id = $branchId " : "";
        $bCondInv  = $branchId > 0 ? " AND inv.branch_id = $branchId " : "";

        $kpis = [
            'monthly_spend' => 0.0, 'open_pos' => 0, 'pending_prs' => 0, 
            'suppliers_count' => 0, 'pending_receipts' => 0, 'unpaid_invoices' => 0.0
        ];

        $charts = [
            'spend_labels' => [], 'spend_data' => [],
            'status_labels' => [], 'status_data' => [],
            'top_sup_labels' => [], 'top_sup_data' => [],
            'top_prod_labels' => [], 'top_prod_data' => []
        ];

        $recentOrders = [];
        $recentInvoices = [];

        try {
            // جلب الـ KPIs
            $kpis['suppliers_count'] = (int)$this->db->query("SELECT COUNT(*) FROM suppliers WHERE company_id = $companyId AND is_active = 1")->fetchColumn();
            $kpis['pending_prs'] = (int)$this->db->query("SELECT COUNT(*) FROM purchase_requests WHERE company_id = $companyId $bCond AND status = 'pending'")->fetchColumn();
            $kpis['open_pos'] = (int)$this->db->query("SELECT COUNT(*) FROM purchase_orders WHERE company_id = $companyId $bCond AND status IN ('sent', 'partially_received')")->fetchColumn();
            $kpis['pending_receipts'] = (int)$this->db->query("SELECT COUNT(*) FROM goods_receipts WHERE company_id = $companyId $bCond AND status = 'inspected'")->fetchColumn();
            
            // مبالغ تحتاج تحويل عملة
            $kpis['unpaid_invoices'] = (float)$this->db->query("SELECT COALESCE(SUM(total_amount - paid_amount), 0) FROM purchase_invoices WHERE company_id = $companyId $bCond AND status != 'paid' AND status != 'cancelled'")->fetchColumn();
            $kpis['monthly_spend'] = (float)$this->db->query("SELECT COALESCE(SUM(total_amount), 0) FROM purchase_orders WHERE company_id = $companyId $bCond AND MONTH(order_date) = MONTH(CURRENT_DATE()) AND YEAR(order_date) = YEAR(CURRENT_DATE()) AND status != 'cancelled'")->fetchColumn();

            // Chart 1: Spend Trend (6 Months)
            for ($i = 5; $i >= 0; $i--) {
                $charts['spend_labels'][] = date('M Y', strtotime("-$i months"));
                $charts['spend_data'][] = 0.0;
            }
            $spendData = $this->db->query("
                SELECT DATE_FORMAT(order_date, '%b %Y') as month_label, SUM(total_amount) as total
                FROM purchase_orders WHERE company_id = $companyId $bCond AND order_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH) AND status != 'cancelled'
                GROUP BY DATE_FORMAT(order_date, '%b %Y'), YEAR(order_date), MONTH(order_date) ORDER BY YEAR(order_date), MONTH(order_date)
            ")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($spendData as $row) {
                $idx = array_search($row['month_label'], $charts['spend_labels']);
                if ($idx !== false) $charts['spend_data'][$idx] = (float)$row['total'];
            }

            // Chart 2: PO Status Breakdown
            $statusData = $this->db->query("SELECT status, COUNT(*) as count FROM purchase_orders WHERE company_id = $companyId $bCond GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($statusData as $row) {
                $charts['status_labels'][] = strtoupper($row['status']);
                $charts['status_data'][] = (int)$row['count'];
            }

            // Chart 3: Top 5 Suppliers
            $topSupData = $this->db->query("
                SELECT COALESCE(s.name_ar, s.name_en, 'Unknown') as sup_name, SUM(po.total_amount) as total
                FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id
                WHERE po.company_id = $companyId $bCondPo AND po.status != 'cancelled' GROUP BY po.supplier_id ORDER BY total DESC LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($topSupData as $row) {
                $charts['top_sup_labels'][] = $row['sup_name'];
                $charts['top_sup_data'][] = (float)$row['total'];
            }

            // Chart 4: Top 5 Products
            $topProdData = $this->db->query("
                SELECT COALESCE(p.name_ar, p.name_en, poi.description) as prod_name, SUM(poi.total_price) as total
                FROM purchase_order_items poi 
                LEFT JOIN purchase_orders po ON poi.po_id = po.id
                LEFT JOIN products p ON poi.product_id = p.id
                WHERE po.company_id = $companyId $bCondPo
                GROUP BY prod_name ORDER BY total DESC LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($topProdData as $row) {
                $charts['top_prod_labels'][] = mb_substr($row['prod_name'], 0, 20);
                $charts['top_prod_data'][] = (float)$row['total'];
            }

            // Tables
            $recentOrders = $this->db->query("SELECT po.id, po.po_number, po.order_date, po.total_amount, po.status, COALESCE(s.name_ar, s.name_en) as supplier_name FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id WHERE po.company_id = $companyId $bCondPo ORDER BY po.id DESC LIMIT 4")->fetchAll(PDO::FETCH_OBJ);
            $recentInvoices = $this->db->query("SELECT inv.id, inv.invoice_number, inv.due_date, (inv.total_amount - inv.paid_amount) as balance, inv.status, COALESCE(s.name_ar, s.name_en) as supplier_name FROM purchase_invoices inv LEFT JOIN suppliers s ON inv.supplier_id = s.id WHERE inv.company_id = $companyId $bCondInv ORDER BY inv.id DESC LIMIT 4")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            // تجاهل صامت في حالة عدم وجود الجداول بعد
        }

        // ========================================================
        // تحويل العملات بالكامل داخل الكنترولر قبل إرسالها للـ View
        // ========================================================
        $kpis['monthly_spend']   = convert_amount($kpis['monthly_spend']);
        $kpis['unpaid_invoices'] = convert_amount($kpis['unpaid_invoices']);

        foreach ($charts['spend_data'] as &$val) $val = convert_amount($val);
        foreach ($charts['top_sup_data'] as &$val) $val = convert_amount($val);
        foreach ($charts['top_prod_data'] as &$val) $val = convert_amount($val);

        if (!empty($recentOrders)) {
            foreach ($recentOrders as $po) { $po->total_amount = convert_amount($po->total_amount); }
        }
        if (!empty($recentInvoices)) {
            foreach ($recentInvoices as $inv) { $inv->balance = convert_amount($inv->balance); }
        }

        ob_start(); 
        include $this->basePath . '/resources/views/purchasing/dashboard/index.php';
        $content = ob_get_clean();

        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function exportReport(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;
        $bCond     = $branchId > 0 ? " AND branch_id = $branchId " : "";
        
        $type = $_GET['type'] ?? 'suppliers';
        $filename = "Export_{$type}_" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        try {
            if ($type === 'suppliers') {
                fputcsv($output, ['الكود', 'اسم المورد', 'الهاتف', 'الرقم الضريبي', 'الحالة']);
                $data = $this->db->query("SELECT code, COALESCE(name_ar, name_en), phone, tax_number, IF(is_active=1, 'Active', 'Inactive') FROM suppliers WHERE company_id = $companyId")->fetchAll(PDO::FETCH_NUM);
                foreach ($data as $row) fputcsv($output, $row);
            } 
            elseif ($type === 'pos') {
                fputcsv($output, ['رقم الأمر', 'تاريخ الإصدار', 'المورد', 'الإجمالي', 'الحالة']);
                $data = $this->db->query("SELECT po.po_number, po.order_date, COALESCE(s.name_ar, s.name_en, '---'), po.total_amount, po.status FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id WHERE po.company_id = $companyId $bCond")->fetchAll(PDO::FETCH_NUM);
                foreach ($data as $row) fputcsv($output, $row);
            }
            elseif ($type === 'invoices') {
                fputcsv($output, ['رقم الفاتورة', 'المورد', 'تاريخ الاستحقاق', 'الإجمالي', 'المدفوع', 'الحالة']);
                $data = $this->db->query("SELECT inv.invoice_number, COALESCE(s.name_ar, s.name_en, '---'), inv.due_date, inv.total_amount, inv.paid_amount, inv.status FROM purchase_invoices inv LEFT JOIN suppliers s ON inv.supplier_id = s.id WHERE inv.company_id = $companyId $bCond")->fetchAll(PDO::FETCH_NUM);
                foreach ($data as $row) fputcsv($output, $row);
            }
        } catch (Throwable $e) {
            fputcsv($output, ['Error generating report']);
        }

        fclose($output);
        exit();
    }
}