<?php
// Path: app/Modules/Projects/Http/Controllers/ProjectDashboardController.php

namespace App\Modules\Projects\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use PDO;
use Throwable;

class ProjectDashboardController extends Controller
{
    private string $basePath;
    private ?PDO $db = null;

    public function __construct()
    {
        ini_set('display_errors', 0);
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
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $branchId  = (int)($_SESSION['branch_id'] ?? 0);

        // الشرط الرئيسي المربوط بجدول المشاريع (p)
        $pCond = "p.company_id = $companyId";
        if ($branchId > 0) {
            $pCond .= " AND p.branch_id = $branchId";
        }

        if ($request->input('export') === 'excel') {
            return $this->exportExcel($response, $pCond);
        }

        $kpis = [
            'total_projects'      => 0,
            'active_projects'     => 0,
            'total_contract_val'  => 0.00,
            'total_spent'         => 0.00,
            'total_claims'        => 0.00,
            'total_paid_claims'   => 0.00,
            'active_milestones'   => 0,
        ];

        $charts = [
            'budget_proj_names'   => [],
            'budget_values'       => [],
            'spent_values'        => [],
            'status_keys'         => ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled'],
            'status_counts'       => [0, 0, 0, 0, 0],
            'monthly_labels'      => [],
            'monthly_claims'      => [],
            'monthly_costs'       => [],
            'cost_cat_keys'       => ['materials', 'labor', 'equipment', 'subcontractor', 'overhead'],
            'cost_cat_values'     => [0, 0, 0, 0, 0]
        ];

        $recentProjects = [];
        $dbErrors = [];

        // تجهيز مسميات الشهور
        for ($i = 5; $i >= 0; $i--) {
            $mLabel = date('M Y', strtotime("-$i months"));
            $charts['monthly_labels'][] = $mLabel;
            $charts['monthly_claims'][$i] = 0.00;
            $charts['monthly_costs'][$i] = 0.00;
        }

        if ($this->db) {
            // 1. المؤشرات الرئيسية للمشاريع
            try {
                $kpis['total_projects'] = (int)$this->db->query("SELECT COUNT(p.id) FROM projects p WHERE $pCond")->fetchColumn();
                $kpis['active_projects'] = (int)$this->db->query("SELECT COUNT(p.id) FROM projects p WHERE p.status = 'in_progress' AND $pCond")->fetchColumn();
                $kpis['total_spent'] = convert_amount((float)$this->db->query("SELECT COALESCE(SUM(p.spent_amount), 0) FROM projects p WHERE $pCond")->fetchColumn());
            } catch (Throwable $e) { $dbErrors[] = "Projects KPIs: " . $e->getMessage(); }

            // 2. إجمالي العقود (JOIN)
            try {
                $contractVal = (float)$this->db->query("SELECT COALESCE(SUM(c.contract_value), 0) FROM project_contracts c JOIN projects p ON c.project_id = p.id WHERE c.status != 'terminated' AND $pCond")->fetchColumn();
                if ($contractVal <= 0) {
                    $contractVal = (float)$this->db->query("SELECT COALESCE(SUM(p.contract_value), 0) FROM projects p WHERE $pCond")->fetchColumn();
                }
                $kpis['total_contract_val'] = convert_amount($contractVal);
            } catch (Throwable $e) {
                try {
                    $kpis['total_contract_val'] = convert_amount((float)$this->db->query("SELECT COALESCE(SUM(p.contract_value), 0) FROM projects p WHERE $pCond")->fetchColumn());
                } catch (Throwable $e2) { $dbErrors[] = "Contracts KPI: " . $e2->getMessage(); }
            }

            // 3. المستخلصات والمطالبات (JOIN)
            try {
                $kpis['total_claims'] = convert_amount((float)$this->db->query("SELECT COALESCE(SUM(i.net_amount), 0) FROM project_invoices i JOIN projects p ON i.project_id = p.id WHERE i.status != 'rejected' AND $pCond")->fetchColumn());
                $kpis['total_paid_claims'] = convert_amount((float)$this->db->query("SELECT COALESCE(SUM(i.paid_amount), 0) FROM project_invoices i JOIN projects p ON i.project_id = p.id WHERE $pCond")->fetchColumn());
            } catch (Throwable $e) { $dbErrors[] = "Invoices KPI: " . $e->getMessage(); }

            // 4. المراحل النشطة (JOIN)
            try {
                $kpis['active_milestones'] = (int)$this->db->query("SELECT COUNT(m.id) FROM project_milestones m JOIN projects p ON m.project_id = p.id WHERE m.status IN ('in_progress','under_review') AND $pCond")->fetchColumn();
            } catch (Throwable $e) { $dbErrors[] = "Milestones KPI: " . $e->getMessage(); }

            // 5. أعلى 5 مشاريع للمخطط البياني
            try {
                $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
                $nameCol = $isAr ? "COALESCE(p.name_ar, p.name_en)" : "COALESCE(p.name_en, p.name_ar)";
                $topProjects = $this->db->query("SELECT $nameCol as proj_name, p.estimated_budget, p.spent_amount FROM projects p WHERE $pCond ORDER BY p.contract_value DESC LIMIT 5")->fetchAll(PDO::FETCH_OBJ);
                foreach ($topProjects as $proj) {
                    $charts['budget_proj_names'][] = $proj->proj_name;
                    $charts['budget_values'][] = convert_amount((float)$proj->estimated_budget);
                    $charts['spent_values'][] = convert_amount((float)$proj->spent_amount);
                }
            } catch (Throwable $e) { $dbErrors[] = "Top Projects Chart: " . $e->getMessage(); }

            // 6. حالات المشاريع الدائري
            try {
                $statusData = $this->db->query("SELECT p.status, COUNT(p.id) as count FROM projects p WHERE $pCond GROUP BY p.status")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
                $charts['status_counts'] = [
                    (int)($statusData['planning'] ?? 0),
                    (int)($statusData['in_progress'] ?? 0),
                    (int)($statusData['on_hold'] ?? 0),
                    (int)($statusData['completed'] ?? 0),
                    (int)($statusData['cancelled'] ?? 0)
                ];
            } catch (Throwable $e) { $dbErrors[] = "Status Chart: " . $e->getMessage(); }

            // 7. أداء 6 أشهر (JOIN)
            for ($i = 5; $i >= 0; $i--) {
                $mStart = date('Y-m-01', strtotime("-$i months"));
                $mEnd   = date('Y-m-t', strtotime("-$i months"));

                try {
                    $claimsVal = (float)$this->db->query("SELECT COALESCE(SUM(i.net_amount), 0) FROM project_invoices i JOIN projects p ON i.project_id = p.id WHERE i.invoice_date BETWEEN '{$mStart}' AND '{$mEnd}' AND i.status != 'rejected' AND $pCond")->fetchColumn();
                    $charts['monthly_claims'][$i] = convert_amount($claimsVal);
                } catch (Throwable $e) {}

                try {
                    $costsVal = (float)$this->db->query("SELECT COALESCE(SUM(c.amount), 0) FROM project_costs c JOIN projects p ON c.project_id = p.id WHERE c.cost_date BETWEEN '{$mStart}' AND '{$mEnd}' AND $pCond")->fetchColumn();
                    $charts['monthly_costs'][$i] = convert_amount($costsVal);
                } catch (Throwable $e) {}
            }

            // 8. تصنيفات التكاليف (JOIN)
            try {
                $catData = $this->db->query("SELECT c.cost_category, SUM(c.amount) as total FROM project_costs c JOIN projects p ON c.project_id = p.id WHERE $pCond GROUP BY c.cost_category")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
                $charts['cost_cat_values'] = [
                    convert_amount((float)($catData['materials'] ?? 0)),
                    convert_amount((float)($catData['labor'] ?? 0)),
                    convert_amount((float)($catData['equipment'] ?? 0)),
                    convert_amount((float)($catData['subcontractor'] ?? 0)),
                    convert_amount((float)($catData['overhead'] ?? 0))
                ];
            } catch (Throwable $e) { $dbErrors[] = "Cost Categories Chart: " . $e->getMessage(); }

            // 9. أحدث المشاريع للجدول السفلي
            try {
                $recentProjects = $this->db->query("SELECT p.id, p.code, p.name_ar, p.name_en, p.contract_value, p.progress_percent, p.status FROM projects p WHERE $pCond ORDER BY p.id DESC LIMIT 5")->fetchAll(PDO::FETCH_OBJ) ?: [];
                foreach($recentProjects as $rp) {
                    $rp->contract_value = convert_amount($rp->contract_value);
                }
            } catch (Throwable $e) { $dbErrors[] = "Recent Projects Table: " . $e->getMessage(); }
        }

        ob_start();
        $viewPath = $this->basePath . '/resources/views/projects/dashboard/index.php';
        if (file_exists($viewPath)) include $viewPath;
        $content = ob_get_clean();

        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    private function exportExcel(Response $response, string $pCond): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        $filename = "Projects_Executive_Summary_" . date('Y-m-d_H-i') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");

        $headers = $isAr ? 
            ['كود المشروع', 'اسم المشروع', 'قيمة العقد', 'الميزانية التقديرية', 'المنصرف الفعلي', 'نسبة الإنجاز %', 'الحالة'] :
            ['Project Code', 'Project Name', 'Contract Value', 'Estimated Budget', 'Actual Spent', 'Progress %', 'Status'];
        
        fputcsv($output, $headers);

        if ($this->db) {
            try {
                $nameCol = $isAr ? "COALESCE(p.name_ar, p.name_en)" : "COALESCE(p.name_en, p.name_ar)";
                $projects = $this->db->query("SELECT p.code, $nameCol as proj_name, p.contract_value, p.estimated_budget, p.spent_amount, p.progress_percent, p.status FROM projects p WHERE $pCond ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($projects as $p) {
                    fputcsv($output, [
                        $p['code'], 
                        $p['proj_name'],
                        number_format(convert_amount((float)$p['contract_value']), 2, '.', ''),
                        number_format(convert_amount((float)$p['estimated_budget']), 2, '.', ''),
                        number_format(convert_amount((float)$p['spent_amount']), 2, '.', ''),
                        $p['progress_percent'] . '%',
                        $p['status']
                    ]);
                }
            } catch (Throwable $e) {}
        }

        fclose($output);
        exit;
    }
}