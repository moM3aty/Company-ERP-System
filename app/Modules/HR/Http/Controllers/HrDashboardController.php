<?php
// Path: app/Modules/HR/Http/Controllers/HrDashboardController.php

namespace App\Modules\HR\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use PDO;
use Throwable;

class HrDashboardController extends Controller
{
    private string $basePath;
    private ?PDO $db = null;

    public function __construct()
    {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
        }
    }

    public function index(Request $request, Response $response): Response
    {
        $today = date('Y-m-d');
        
        $kpis = [
            'total_employees' => 0,
            'present_today' => 0,
            'absent_today' => 0,
            'on_leave_today' => 0,
            'contracts_expiring' => 0,
            'docs_expiring' => 0,
            'latest_payroll_net' => 0.00,
            'active_applicants' => 0,
            'avg_appraisal' => 0.00
        ];

        $charts = [
            'dept_labels' => [],
            'dept_data' => [],
            'att_status_labels' => ['حاضر', 'متأخر', 'غائب', 'إجازة'],
            'att_status_data' => [0, 0, 0, 0],
            'recruitment_labels' => ['جديد', 'مقابلة', 'عرض', 'توظيف', 'مرفوض'],
            'recruitment_data' => [0, 0, 0, 0, 0],
            'appraisal_labels' => ['ممتاز', 'جيد جداً', 'جيد', 'مقبول', 'تحسين'],
            'appraisal_data' => [0, 0, 0, 0, 0],
            'contract_status_labels' => ['ساري', 'منتهي', 'مفسوخ'],
            'contract_status_data' => [0, 0, 0],
            'leave_type_labels' => ['سنوية', 'مرضية', 'بدون راتب', 'وضع', 'أخرى'],
            'leave_type_data' => [0, 0, 0, 0, 0]
        ];

        $expiringAlerts = [];

        if ($this->db) {
            try {
                // KPIs
                $kpis['total_employees'] = (int) $this->db->query("SELECT COUNT(*) FROM hr_employees WHERE status = 'active'")->fetchColumn();
                
                $attData = $this->db->query("
                    SELECT 
                        SUM(IF(status = 'present', 1, 0)) as present_cnt,
                        SUM(IF(status = 'late', 1, 0)) as late_cnt,
                        SUM(IF(status = 'absent', 1, 0)) as absent_cnt,
                        SUM(IF(status = 'on_leave', 1, 0)) as leave_cnt
                    FROM hr_attendance WHERE date = '{$today}'
                ")->fetch(PDO::FETCH_OBJ);

                if ($attData) {
                    $kpis['present_today'] = (int)($attData->present_cnt + $attData->late_cnt);
                    $kpis['absent_today'] = (int)$attData->absent_cnt;
                    $kpis['on_leave_today'] = (int)$attData->leave_cnt;
                    $charts['att_status_data'] = [(int)$attData->present_cnt, (int)$attData->late_cnt, (int)$attData->absent_cnt, (int)$attData->leave_cnt];
                }

                $kpis['contracts_expiring'] = (int) $this->db->query("SELECT COUNT(*) FROM hr_employee_contracts WHERE status = 'active' AND end_date BETWEEN '{$today}' AND DATE_ADD('{$today}', INTERVAL 30 DAY)")->fetchColumn();
                $kpis['docs_expiring'] = (int) $this->db->query("SELECT COUNT(*) FROM hr_documents WHERE status = 'active' AND expiry_date BETWEEN '{$today}' AND DATE_ADD('{$today}', INTERVAL 30 DAY)")->fetchColumn();

                $kpis['latest_payroll_net'] = (float) $this->db->query("SELECT net_pay FROM hr_payroll ORDER BY id DESC LIMIT 1")->fetchColumn();
                $kpis['active_applicants'] = (int) $this->db->query("SELECT COUNT(*) FROM hr_recruitment WHERE status IN ('applied', 'interviewed', 'offered')")->fetchColumn();
                $kpis['avg_appraisal'] = (float) $this->db->query("SELECT COALESCE(AVG(score), 0) FROM hr_appraisals WHERE status = 'approved'")->fetchColumn();

                // Chart 1: الأقسام
                $depts = $this->db->query("
                    SELECT d.name_ar as dept_name, COUNT(e.id) as emp_count 
                    FROM hr_departments d 
                    LEFT JOIN hr_employees e ON d.id = e.department_id AND e.status = 'active'
                    GROUP BY d.id HAVING emp_count > 0
                ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

                foreach($depts as $d) {
                    $charts['dept_labels'][] = $d['dept_name'];
                    $charts['dept_data'][] = (int)$d['emp_count'];
                }

                // Chart 3: التوظيف
                $recData = $this->db->query("
                    SELECT 
                        SUM(IF(status = 'applied', 1, 0)) as app_cnt,
                        SUM(IF(status = 'interviewed', 1, 0)) as int_cnt,
                        SUM(IF(status = 'offered', 1, 0)) as off_cnt,
                        SUM(IF(status = 'hired', 1, 0)) as hir_cnt,
                        SUM(IF(status = 'rejected', 1, 0)) as rej_cnt
                    FROM hr_recruitment
                ")->fetch(PDO::FETCH_OBJ);

                if ($recData) {
                    $charts['recruitment_data'] = [(int)$recData->app_cnt, (int)$recData->int_cnt, (int)$recData->off_cnt, (int)$recData->hir_cnt, (int)$recData->rej_cnt];
                }

                // Chart 4: التقييمات
                $apprData = $this->db->query("
                    SELECT 
                        SUM(IF(rating_grade = 'Excellent', 1, 0)) as exc_cnt,
                        SUM(IF(rating_grade = 'Very Good', 1, 0)) as vg_cnt,
                        SUM(IF(rating_grade = 'Good', 1, 0)) as g_cnt,
                        SUM(IF(rating_grade = 'Acceptable', 1, 0)) as acc_cnt,
                        SUM(IF(rating_grade = 'Needs Improvement', 1, 0)) as ni_cnt
                    FROM hr_appraisals
                ")->fetch(PDO::FETCH_OBJ);

                if ($apprData) {
                    $charts['appraisal_data'] = [(int)$apprData->exc_cnt, (int)$apprData->vg_cnt, (int)$apprData->g_cnt, (int)$apprData->acc_cnt, (int)$apprData->ni_cnt];
                }

                // Chart 5: العقود
                $cntData = $this->db->query("
                    SELECT 
                        SUM(IF(status = 'active', 1, 0)) as act_cnt,
                        SUM(IF(status = 'expired', 1, 0)) as exp_cnt,
                        SUM(IF(status = 'terminated', 1, 0)) as trm_cnt
                    FROM hr_employee_contracts
                ")->fetch(PDO::FETCH_OBJ);

                if ($cntData) {
                    $charts['contract_status_data'] = [(int)$cntData->act_cnt, (int)$cntData->exp_cnt, (int)$cntData->trm_cnt];
                }

                // Chart 6: الإجازات
                $leaveData = $this->db->query("
                    SELECT 
                        SUM(IF(leave_type = 'annual', 1, 0)) as ann_cnt,
                        SUM(IF(leave_type = 'sick', 1, 0)) as sck_cnt,
                        SUM(IF(leave_type = 'unpaid', 1, 0)) as unp_cnt,
                        SUM(IF(leave_type = 'maternity', 1, 0)) as mat_cnt,
                        SUM(IF(leave_type = 'other', 1, 0)) as oth_cnt
                    FROM hr_leaves
                ")->fetch(PDO::FETCH_OBJ);

                if ($leaveData) {
                    $charts['leave_type_data'] = [(int)$leaveData->ann_cnt, (int)$leaveData->sck_cnt, (int)$leaveData->unp_cnt, (int)$leaveData->mat_cnt, (int)$leaveData->oth_cnt];
                }

                // التنبيهات
                $expiringAlerts = $this->db->query("
                    (SELECT 'عقد عمل' as type, c.contract_code as code, e.name_ar as emp_name, c.end_date as exp_date 
                     FROM hr_employee_contracts c 
                     JOIN hr_employees e ON c.employee_id = e.id 
                     WHERE c.status = 'active' AND c.end_date BETWEEN '{$today}' AND DATE_ADD('{$today}', INTERVAL 30 DAY))
                    UNION ALL
                    (SELECT 'وثيقة/مستند' as type, d.document_code as code, e.name_ar as emp_name, d.expiry_date as exp_date 
                     FROM hr_documents d 
                     JOIN hr_employees e ON d.employee_id = e.id 
                     WHERE d.status = 'active' AND d.expiry_date BETWEEN '{$today}' AND DATE_ADD('{$today}', INTERVAL 30 DAY))
                    ORDER BY exp_date ASC LIMIT 8
                ")->fetchAll(PDO::FETCH_OBJ) ?: [];

            } catch (Throwable $e) {
                error_log("HR Dashboard Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/hr/dashboard/index.php', [
            'kpis' => $kpis,
            'charts' => $charts,
            'expiringAlerts' => $expiringAlerts
        ], $response);
    }

    private function renderView(string $viewPath, array $data, Response $response): Response
    {
        extract($data);
        $fullPath = $this->basePath . $viewPath;

        try {
            ob_start();
            include $fullPath;
            $content = ob_get_clean();

            ob_start();
            include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');
        } catch (Throwable $e) {
            ob_end_clean();
            die("Dashboard Render Error: " . $e->getMessage());
        }
    }
}