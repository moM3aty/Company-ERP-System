<?php
// Path: app/Modules/Projects/Http/Controllers/ProjectDashboardController.php

namespace App\Modules\Projects\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Throwable;

class ProjectDashboardController extends Controller
{
    private string $basePath;
    private ?PDO $db = null;

    public function __construct()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
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
        if ($request->input('export') === 'excel') {
            return $this->exportExcel($response);
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
            'status_labels'       => ['التخطيط', 'قيد التنفيذ', 'متوقف مؤقتاً', 'مكتمل', 'ملغى'],
            'status_counts'       => [0, 0, 0, 0, 0],
            'monthly_labels'      => [],
            'monthly_claims'      => [],
            'monthly_costs'       => [],
            'cost_cat_labels'     => ['مواد وتوريدات', 'عمالة وأجور', 'معدات وآليات', 'مقاولين فرعيين', 'مصروفات إدارية'],
            'cost_cat_values'     => [0, 0, 0, 0, 0]
        ];

        $recentProjects = [];
        $recentInvoices = [];

        for ($i = 5; $i >= 0; $i--) {
            $mLabel = date('M Y', strtotime("-$i months"));
            $charts['monthly_labels'][] = $mLabel;
            $charts['monthly_claims'][$i] = 0.00;
            $charts['monthly_costs'][$i] = 0.00;
        }

        if ($this->db) {
            try {
                // أ. المؤشرات الرئيسية
                $kpis['total_projects'] = (int)$this->db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
                $kpis['active_projects'] = (int)$this->db->query("SELECT COUNT(*) FROM projects WHERE status = 'in_progress'")->fetchColumn();
                $kpis['total_spent'] = (float)$this->db->query("SELECT COALESCE(SUM(spent_amount), 0) FROM projects")->fetchColumn();

                // احتساب إجمالي العقود بدقة من جدول العقود أو المشاريع
                try {
                    $contractVal = (float)$this->db->query("SELECT COALESCE(SUM(contract_value), 0) FROM project_contracts WHERE status != 'terminated'")->fetchColumn();
                    if ($contractVal <= 0) {
                        $contractVal = (float)$this->db->query("SELECT COALESCE(SUM(contract_value), 0) FROM projects")->fetchColumn();
                    }
                    $kpis['total_contract_val'] = $contractVal;
                } catch (Throwable $e) {
                    $kpis['total_contract_val'] = (float)$this->db->query("SELECT COALESCE(SUM(contract_value), 0) FROM projects")->fetchColumn();
                }

                try {
                    $kpis['total_claims'] = (float)$this->db->query("SELECT COALESCE(SUM(net_amount), 0) FROM project_invoices WHERE status != 'rejected'")->fetchColumn();
                    $kpis['total_paid_claims'] = (float)$this->db->query("SELECT COALESCE(SUM(paid_amount), 0) FROM project_invoices")->fetchColumn();
                } catch (Throwable $e) {}

                try {
                    $kpis['active_milestones'] = (int)$this->db->query("SELECT COUNT(*) FROM project_milestones WHERE status IN ('in_progress','under_review')")->fetchColumn();
                } catch (Throwable $e) {}

                // ب. أعلى 5 مشاريع
                $topProjects = $this->db->query("SELECT name_ar, estimated_budget, spent_amount FROM projects ORDER BY contract_value DESC LIMIT 5")->fetchAll(PDO::FETCH_OBJ);
                foreach ($topProjects as $p) {
                    $charts['budget_proj_names'][] = $p->name_ar;
                    $charts['budget_values'][] = (float)$p->estimated_budget;
                    $charts['spent_values'][] = (float)$p->spent_amount;
                }

                // ج. حالات المشاريع
                $statusData = $this->db->query("SELECT status, COUNT(*) as count FROM projects GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
                $charts['status_counts'] = [
                    (int)($statusData['planning'] ?? 0),
                    (int)($statusData['in_progress'] ?? 0),
                    (int)($statusData['on_hold'] ?? 0),
                    (int)($statusData['completed'] ?? 0),
                    (int)($statusData['cancelled'] ?? 0)
                ];

                // د. أداء 6 أشهر
                $claimsMonthlyArr = [];
                $costsMonthlyArr = [];

                for ($i = 5; $i >= 0; $i--) {
                    $mStart = date('Y-m-01', strtotime("-$i months"));
                    $mEnd   = date('Y-m-t', strtotime("-$i months"));

                    try {
                        $claimsVal = (float)$this->db->query("SELECT COALESCE(SUM(net_amount), 0) FROM project_invoices WHERE invoice_date BETWEEN '{$mStart}' AND '{$mEnd}' AND status != 'rejected'")->fetchColumn();
                        $claimsMonthlyArr[] = $claimsVal;
                    } catch (Throwable $e) { $claimsMonthlyArr[] = 0.00; }

                    try {
                        $costsVal = (float)$this->db->query("SELECT COALESCE(SUM(amount), 0) FROM project_costs WHERE cost_date BETWEEN '{$mStart}' AND '{$mEnd}'")->fetchColumn();
                        $costsMonthlyArr[] = $costsVal;
                    } catch (Throwable $e) { $costsMonthlyArr[] = 0.00; }
                }
                $charts['monthly_claims'] = $claimsMonthlyArr;
                $charts['monthly_costs'] = $costsMonthlyArr;

                // هـ. تبويبات التكلفة
                try {
                    $catData = $this->db->query("SELECT cost_category, SUM(amount) as total FROM project_costs GROUP BY cost_category")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
                    $charts['cost_cat_values'] = [
                        (float)($catData['materials'] ?? 0),
                        (float)($catData['labor'] ?? 0),
                        (float)($catData['equipment'] ?? 0),
                        (float)($catData['subcontractor'] ?? 0),
                        (float)($catData['overhead'] ?? 0)
                    ];
                } catch (Throwable $e) {}

                // و. أحدث السجلات
                $recentProjects = $this->db->query("SELECT id, code, name_ar, contract_value, progress_percent, status FROM projects ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_OBJ) ?: [];

            } catch (Throwable $e) {
                error_log("Project Dashboard Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/projects/dashboard/index.php', [
            'kpis'           => $kpis,
            'charts'         => $charts,
            'recentProjects' => $recentProjects,
            'recentInvoices' => $recentInvoices
        ], $response);
    }

    private function exportExcel(Response $response): Response
    {
        $filename = "Projects_Executive_Summary_" . date('Y-m-d_H-i') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");

        fputcsv($output, ['كود المشروع', 'اسم المشروع', 'قيمة العقد', 'الميزانية التقديرية', 'المنصرف الفعلي', 'نسبة الإنجاز %', 'الحالة']);

        if ($this->db) {
            try {
                $projects = $this->db->query("SELECT code, name_ar, contract_value, estimated_budget, spent_amount, progress_percent, status FROM projects ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($projects as $p) {
                    fputcsv($output, [
                        $p['code'], $p['name_ar'],
                        number_format((float)$p['contract_value'], 2),
                        number_format((float)$p['estimated_budget'], 2),
                        number_format((float)$p['spent_amount'], 2),
                        $p['progress_percent'] . '%',
                        $p['status']
                    ]);
                }
            } catch (Throwable $e) {}
        }

        fclose($output);
        exit;
    }

    private function renderView(string $viewPath, array $data, Response $response): Response
    {
        extract($data);
        $fullPath = $this->basePath . $viewPath;

        if (!file_exists($fullPath)) {
            die("<div style='padding:30px; background:#fff; color:#dc2626;'>Missing: " . htmlspecialchars($fullPath) . "</div>");
        }

        try {
            ob_start();
            include $fullPath;
            $content = ob_get_clean();

            ob_start();
            include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');
        } catch (Throwable $e) {
            ob_end_clean();
            die("<div style='padding:30px; background:#fff; color:#dc2626;'>View Error: " . htmlspecialchars($e->getMessage()) . "</div>");
        }
    }
}