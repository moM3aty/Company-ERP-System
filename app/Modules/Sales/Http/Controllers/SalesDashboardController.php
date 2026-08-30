<?php
// Path: app/Modules/Sales/Http/Controllers/SalesDashboardController.php

namespace App\Modules\Sales\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use PDO;
use Exception;

class SalesDashboardController extends Controller
{
    private string $basePath;
    private PDO $db;

    public function __construct()
    {
        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    public function index(Request $request, Response $response): Response
    {
        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            return $this->exportMultiSheetExcelReport($response);
        }

        $companyId = current_company_id();
        $branchId  = current_branch();

        $kpis = [
            'total_invoiced'     => 0.00,
            'total_collected'    => 0.00,
            'unpaid_balance'     => 0.00,
            'total_returns'      => 0.00,
            'collection_rate'    => 0,
            'open_quotes_val'    => 0.00,
            'active_contracts'   => 0,
            'active_customers'   => 0
        ];

        $charts = [
            'labels'              => [],
            'sales_trend'         => [],
            'collected_trend'     => [],
            'status_distribution' => [0, 0, 0, 0],
            'rep_names'           => [],
            'rep_sales'           => [],
            'quotes_pipeline'     => [0, 0, 0, 0]
        ];

        $recentInvoices = [];
        $topProducts    = [];
        $topReps        = [];

        $trendMapSales     = [];
        $trendMapCollected = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthKey   = date('Y-m', strtotime("-$i months"));
            $monthLabel = date('M Y', strtotime("-$i months"));
            $charts['labels'][] = $monthLabel;
            $trendMapSales[$monthKey]     = 0.00;
            $trendMapCollected[$monthKey] = 0.00;
        }

        try {
            $invSql = "
                SELECT 
                    COALESCE(SUM(total_amount), 0) as total_invoiced,
                    COALESCE(SUM(paid_amount), 0) as total_collected,
                    COALESCE(SUM(total_amount - paid_amount), 0) as unpaid_balance
                FROM sales_invoices 
                WHERE status != 'cancelled' AND company_id = ?
            ";
            $invParams = [$companyId];
            if ($branchId) {
                $invSql .= " AND branch_id = ?";
                $invParams[] = $branchId;
            }

            $invStmt = $this->db->prepare($invSql);
            $invStmt->execute($invParams);
            $invData = $invStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($invData) {
                $kpis['total_invoiced']  = convert_amount((float)$invData['total_invoiced']);
                $kpis['total_collected'] = convert_amount((float)$invData['total_collected']);
                $kpis['unpaid_balance']  = convert_amount((float)$invData['unpaid_balance']);
                $kpis['collection_rate'] = $kpis['total_invoiced'] > 0 
                    ? round(($kpis['total_collected'] / $kpis['total_invoiced']) * 100, 1) : 0;
            }

            $retSql = "SELECT COALESCE(SUM(total_amount), 0) FROM sales_returns WHERE status != 'cancelled' AND company_id = ?";
            $retParams = [$companyId];
            if ($branchId) {
                $retSql .= " AND branch_id = ?";
                $retParams[] = $branchId;
            }
            $returnsStmt = $this->db->prepare($retSql);
            $returnsStmt->execute($retParams);
            $kpis['total_returns'] = convert_amount((float)$returnsStmt->fetchColumn());

            $custSql = "SELECT COUNT(id) FROM customers WHERE is_active = 1 AND company_id = ?";
            $custParams = [$companyId];
            if ($branchId) {
                $custSql .= " AND branch_id = ?";
                $custParams[] = $branchId;
            }
            $custStmt = $this->db->prepare($custSql);
            $custStmt->execute($custParams);
            $kpis['active_customers'] = (int)$custStmt->fetchColumn();

            $quotesSql = "SELECT COALESCE(SUM(total_amount), 0) as val FROM sales_quotations WHERE status IN ('draft', 'sent') AND company_id = ?";
            $quotesParams = [$companyId];
            if ($branchId) {
                $quotesSql .= " AND branch_id = ?";
                $quotesParams[] = $branchId;
            }
            $quotesStmt = $this->db->prepare($quotesSql);
            $quotesStmt->execute($quotesParams);
            $quotesData = $quotesStmt->fetch(PDO::FETCH_ASSOC);
            if ($quotesData) {
                $kpis['open_quotes_val'] = convert_amount((float)$quotesData['val']);
            }

            try {
                $cntSql = "SELECT COUNT(id) FROM sales_contracts WHERE status = 'active' AND company_id = ?";
                $cntParams = [$companyId];
                if ($branchId) {
                    $cntSql .= " AND branch_id = ?";
                    $cntParams[] = $branchId;
                }
                $contractStmt = $this->db->prepare($cntSql);
                $contractStmt->execute($cntParams);
                $kpis['active_contracts'] = (int)$contractStmt->fetchColumn();
            } catch (Exception $e) {}

            $sixMonthsAgo = date('Y-m-01', strtotime('-5 months'));
            
            $salesTrendSql = "
                SELECT DATE_FORMAT(created_at, '%Y-%m') as month_key, SUM(total_amount) as total 
                FROM sales_invoices 
                WHERE status != 'cancelled' AND created_at >= ? AND company_id = ? 
            ";
            $salesTrendParams = [$sixMonthsAgo, $companyId];
            if ($branchId) {
                $salesTrendSql .= " AND branch_id = ?";
                $salesTrendParams[] = $branchId;
            }
            $salesTrendSql .= " GROUP BY month_key";

            $salesTrendQuery = $this->db->prepare($salesTrendSql);
            $salesTrendQuery->execute($salesTrendParams);
            foreach ($salesTrendQuery->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (isset($trendMapSales[$row['month_key']])) {
                    $trendMapSales[$row['month_key']] = convert_amount((float)$row['total']);
                }
            }
            $charts['sales_trend'] = array_values($trendMapSales);

            $collTrendSql = "
                SELECT DATE_FORMAT(created_at, '%Y-%m') as month_key, SUM(paid_amount) as total 
                FROM sales_invoices 
                WHERE status != 'cancelled' AND created_at >= ? AND company_id = ? 
            ";
            $collTrendParams = [$sixMonthsAgo, $companyId];
            if ($branchId) {
                $collTrendSql .= " AND branch_id = ?";
                $collTrendParams[] = $branchId;
            }
            $collTrendSql .= " GROUP BY month_key";

            $collTrendQuery = $this->db->prepare($collTrendSql);
            $collTrendQuery->execute($collTrendParams);
            foreach ($collTrendQuery->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (isset($trendMapCollected[$row['month_key']])) {
                    $trendMapCollected[$row['month_key']] = convert_amount((float)$row['total']);
                }
            }
            $charts['collected_trend'] = array_values($trendMapCollected);

            $statusSql = "SELECT status, COUNT(id) as cnt FROM sales_invoices WHERE company_id = ?";
            $statusParams = [$companyId];
            if ($branchId) {
                $statusSql .= " AND branch_id = ?";
                $statusParams[] = $branchId;
            }
            $statusSql .= " GROUP BY status";

            $statusStmt = $this->db->prepare($statusSql);
            $statusStmt->execute($statusParams);
            $statusData = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
            $statusMapObj = ['unpaid' => 0, 'partially_paid' => 0, 'paid' => 0, 'draft' => 0];
            foreach ($statusData as $row) {
                $stKey = strtolower($row['status']);
                if (isset($statusMapObj[$stKey])) $statusMapObj[$stKey] = (int)$row['cnt'];
            }
            $charts['status_distribution'] = array_values($statusMapObj);

            try {
                $topRepsSql = "
                    SELECT COALESCE(r.name_ar, r.name_en) as rep_name, r.commission_rate,
                           COALESCE(SUM(i.total_amount), 0) as total_sales
                    FROM sales_representatives r
                    LEFT JOIN sales_invoices i ON i.status != 'cancelled' AND i.company_id = r.company_id AND i.sales_rep_id = r.id
                    WHERE r.company_id = ?
                ";
                $topRepsParams = [$companyId];
                if ($branchId) {
                    $topRepsSql .= " AND r.branch_id = ?";
                    $topRepsParams[] = $branchId;
                }
                $topRepsSql .= " GROUP BY r.id ORDER BY total_sales DESC LIMIT 5";

                $topRepsQuery = $this->db->prepare($topRepsSql);
                $topRepsQuery->execute($topRepsParams);
                $topRepsData = $topRepsQuery->fetchAll(PDO::FETCH_OBJ);
                
                $topReps = $topRepsData;
                foreach ($topRepsData as $rep) {
                    $charts['rep_names'][] = $rep->rep_name;
                    $convertedVal = convert_amount((float)$rep->total_sales);
                    $charts['rep_sales'][] = $convertedVal;
                    $rep->total_sales = $convertedVal; 
                }
            } catch (Exception $e) {}

            try {
                $qSql = "SELECT status, COUNT(id) as cnt FROM sales_quotations WHERE company_id = ?";
                $qParams = [$companyId];
                if ($branchId) {
                    $qSql .= " AND branch_id = ?";
                    $qParams[] = $branchId;
                }
                $qSql .= " GROUP BY status";

                $qStmt = $this->db->prepare($qSql);
                $qStmt->execute($qParams);
                $qData = $qStmt->fetchAll(PDO::FETCH_ASSOC);
                $qMap = ['draft' => 0, 'sent' => 0, 'accepted' => 0, 'rejected' => 0];
                foreach ($qData as $qRow) {
                    $qKey = strtolower($qRow['status']);
                    if (isset($qMap[$qKey])) $qMap[$qKey] = (int)$qRow['cnt'];
                }
                $charts['quotes_pipeline'] = array_values($qMap);
            } catch (Exception $e) {}

            $recentSql = "
                SELECT i.id, i.invoice_number, COALESCE(c.name_ar, c.name_en) as customer_name, i.total_amount, i.paid_amount, i.status, i.issue_date 
                FROM sales_invoices i 
                LEFT JOIN customers c ON i.customer_id = c.id 
                WHERE i.company_id = ?
            ";
            $recentParams = [$companyId];
            if ($branchId) {
                $recentSql .= " AND i.branch_id = ?";
                $recentParams[] = $branchId;
            }
            $recentSql .= " ORDER BY i.id DESC LIMIT 5";

            $recentInvoicesStmt = $this->db->prepare($recentSql);
            $recentInvoicesStmt->execute($recentParams);
            $recentInvoices = $recentInvoicesStmt->fetchAll(PDO::FETCH_OBJ);

            foreach($recentInvoices as $inv) {
                $inv->total_amount = convert_amount((float)$inv->total_amount);
                $inv->paid_amount  = convert_amount((float)$inv->paid_amount);
            }

            $prodSql = "
                SELECT l.description, SUM(l.quantity) as total_qty, SUM(l.total) as total_revenue 
                FROM sales_invoice_lines l
                JOIN sales_invoices i ON l.invoice_id = i.id
                WHERE i.company_id = ?
            ";
            $prodParams = [$companyId];
            if ($branchId) {
                $prodSql .= " AND i.branch_id = ?";
                $prodParams[] = $branchId;
            }
            $prodSql .= " GROUP BY l.description ORDER BY total_revenue DESC LIMIT 5";

            $topProductsStmt = $this->db->prepare($prodSql);
            $topProductsStmt->execute($prodParams);
            $topProducts = $topProductsStmt->fetchAll(PDO::FETCH_OBJ);

            foreach($topProducts as $p) {
                $p->total_revenue = convert_amount((float)$p->total_revenue);
            }

        } catch (Exception $e) {
            error_log("Dashboard Error: " . $e->getMessage());
        }

        ob_start(); include $this->basePath . '/resources/views/sales/dashboard/index.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    private function exportMultiSheetExcelReport(Response $response): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();
        $currency  = current_currency();
        $fileName  = "Executive_Sales_Report_" . date('Y-m-d') . ".xls";

        try {
            $invSql = "
                SELECT i.invoice_number, COALESCE(c.name_ar, c.name_en) as customer_name, c.tax_number,
                       i.issue_date, i.subtotal, i.tax_amount, i.total_amount, i.paid_amount, 
                       (i.total_amount - i.paid_amount) as due_amount, i.status
                FROM sales_invoices i LEFT JOIN customers c ON i.customer_id = c.id 
                WHERE i.company_id = ?
            ";
            $invParams = [$companyId];
            if ($branchId) {
                $invSql .= " AND i.branch_id = ?";
                $invParams[] = $branchId;
            }
            $invSql .= " ORDER BY i.id DESC";

            $invStmt = $this->db->prepare($invSql);
            $invStmt->execute($invParams);
            $invoices = $invStmt->fetchAll(PDO::FETCH_OBJ);

            $recSql = "
                SELECT r.receipt_number, COALESCE(c.name_ar, c.name_en) as customer_name, r.receipt_date, r.amount, r.payment_method
                FROM sales_receipts r LEFT JOIN customers c ON r.customer_id = c.id 
                WHERE r.company_id = ?
            ";
            $recParams = [$companyId];
            if ($branchId) {
                $recSql .= " AND r.branch_id = ?";
                $recParams[] = $branchId;
            }
            $recSql .= " ORDER BY r.id DESC";

            $recStmt = $this->db->prepare($recSql);
            $recStmt->execute($recParams);
            $receipts = $recStmt->fetchAll(PDO::FETCH_OBJ);

            $prodSql = "
                SELECT l.description, SUM(l.quantity) as total_qty, SUM(l.total) as total_revenue 
                FROM sales_invoice_lines l
                JOIN sales_invoices i ON l.invoice_id = i.id
                WHERE i.company_id = ?
            ";
            $prodParams = [$companyId];
            if ($branchId) {
                $prodSql .= " AND i.branch_id = ?";
                $prodParams[] = $branchId;
            }
            $prodSql .= " GROUP BY l.description ORDER BY total_revenue DESC";

            $prodStmt = $this->db->prepare($prodSql);
            $prodStmt->execute($prodParams);
            $products = $prodStmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            $invoices = []; $receipts = []; $products = [];
        }

        $output = '<?xml version="1.0" encoding="UTF-8"?>';
        $output .= '
        <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
         xmlns:o="urn:schemas-microsoft-com:office:office"
         xmlns:x="urn:schemas-microsoft-com:office:excel"
         xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
         xmlns:html="http://www.w3.org/TR/REC-html40">
        ';

        // Sheet 1: Invoices
        $output .= '<Worksheet ss:Name="فواتير المبيعات"><Table>';
        $output .= '<Row>
            <Cell><Data ss:Type="String">رقم الفاتورة</Data></Cell>
            <Cell><Data ss:Type="String">العميل</Data></Cell>
            <Cell><Data ss:Type="String">التاريخ</Data></Cell>
            <Cell><Data ss:Type="String">الإجمالي (' . $currency . ')</Data></Cell>
            <Cell><Data ss:Type="String">المحصل (' . $currency . ')</Data></Cell>
            <Cell><Data ss:Type="String">المتبقي (' . $currency . ')</Data></Cell>
            <Cell><Data ss:Type="String">الحالة</Data></Cell>
        </Row>';
        foreach ($invoices as $inv) {
            $output .= '<Row>';
            $output .= '<Cell><Data ss:Type="String">' . htmlspecialchars($inv->invoice_number) . '</Data></Cell>';
            $output .= '<Cell><Data ss:Type="String">' . htmlspecialchars($inv->customer_name ?? 'عام') . '</Data></Cell>';
            $output .= '<Cell><Data ss:Type="String">' . $inv->issue_date . '</Data></Cell>';
            $output .= '<Cell><Data ss:Type="Number">' . convert_amount((float)$inv->total_amount) . '</Data></Cell>';
            $output .= '<Cell><Data ss:Type="Number">' . convert_amount((float)$inv->paid_amount) . '</Data></Cell>';
            $output .= '<Cell><Data ss:Type="Number">' . convert_amount((float)$inv->due_amount) . '</Data></Cell>';
            $output .= '<Cell><Data ss:Type="String">' . strtoupper($inv->status) . '</Data></Cell>';
            $output .= '</Row>';
        }
        $output .= '</Table></Worksheet>';

        // Sheet 2: Receipts
        $output .= '<Worksheet ss:Name="سندات القبض"><Table>';
        $output .= '<Row>
            <Cell><Data ss:Type="String">رقم السند</Data></Cell>
            <Cell><Data ss:Type="String">العميل</Data></Cell>
            <Cell><Data ss:Type="String">التاريخ</Data></Cell>
            <Cell><Data ss:Type="String">المبلغ (' . $currency . ')</Data></Cell>
            <Cell><Data ss:Type="String">طريقة الدفع</Data></Cell>
        </Row>';
        foreach ($receipts as $rct) {
            $output .= '<Row>';
            $output .= '<Cell><Data ss:Type="String">' . htmlspecialchars($rct->receipt_number) . '</Data></Cell>';
            $output .= '<Cell><Data ss:Type="String">' . htmlspecialchars($rct->customer_name ?? 'عام') . '</Data></Cell>';
            $output .= '<Cell><Data ss:Type="String">' . $rct->receipt_date . '</Data></Cell>';
            $output .= '<Cell><Data ss:Type="Number">' . convert_amount((float)$rct->amount) . '</Data></Cell>';
            $output .= '<Cell><Data ss:Type="String">' . strtoupper($rct->payment_method) . '</Data></Cell>';
            $output .= '</Row>';
        }
        $output .= '</Table></Worksheet>';

        // Sheet 3: Top Products
        $output .= '<Worksheet ss:Name="أداء الأصناف"><Table>';
        $output .= '<Row>
            <Cell><Data ss:Type="String">وصف الصنف</Data></Cell>
            <Cell><Data ss:Type="String">الكمية المباعة</Data></Cell>
            <Cell><Data ss:Type="String">إجمالي الإيراد (' . $currency . ')</Data></Cell>
        </Row>';
        foreach ($products as $p) {
            $output .= '<Row>';
            $output .= '<Cell><Data ss:Type="String">' . htmlspecialchars($p->description) . '</Data></Cell>';
            $output .= '<Cell><Data ss:Type="Number">' . $p->total_qty . '</Data></Cell>';
            $output .= '<Cell><Data ss:Type="Number">' . convert_amount((float)$p->total_revenue) . '</Data></Cell>';
            $output .= '</Row>';
        }
        $output .= '</Table></Worksheet>';

        $output .= '</Workbook>';

        return $response->setContent($output)
            ->setHeader('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->setHeader('Content-Disposition', "attachment; filename=\"{$fileName}\"");
    }
}