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
    private $basePath;
    private $db;

    public function __construct()
    {
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = isset($basePath) ? $basePath : dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            if ($this->db) {
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        }
    }

    private function getCompanyId()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return (int)($_SESSION['company_id'] ?? 1);
    }

    private function getActiveBranchId()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
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
        $today = date('Y-m-d');
        $companyId = $this->getCompanyId();
        $branchId = $this->getActiveBranchId();
        
        $kpis = [
            'total_employees' => 0, 'present_today' => 0, 'absent_today' => 0,
            'on_leave_today' => 0, 'contracts_expiring' => 0, 'docs_expiring' => 0,
            'latest_payroll_net' => 0.00, 'active_applicants' => 0, 'avg_appraisal' => 0.00
        ];

        $charts = [
            'dept_labels' => [], 'dept_data' => [],
            'att_status_labels' => ['حاضر/متأخر', 'غائب', 'إجازة'], 'att_status_data' => [0, 0, 0],
            'recruitment_labels' => ['جديد', 'مقابلة', 'عرض', 'توظيف', 'مرفوض'], 'recruitment_data' => [0, 0, 0, 0, 0],
            'appraisal_labels' => ['ممتاز', 'جيد جداً', 'جيد', 'مقبول', 'تحسين'], 'appraisal_data' => [0, 0, 0, 0, 0],
            'contract_status_labels' => ['ساري', 'منتهي', 'مفسوخ'], 'contract_status_data' => [0, 0, 0],
            'leave_type_labels' => ['سنوية', 'مرضية', 'بدون راتب', 'وضع', 'أخرى'], 'leave_type_data' => [0, 0, 0, 0, 0]
        ];

        $expiringAlerts = [];

        if ($this->db) {
            try {
                $cEmp  = $this->buildBranchCond('e', $branchId, 'hr_employees');
                $cAtt  = $this->buildBranchCond('a', $branchId, 'hr_attendance');
                $cCont = $this->buildBranchCond('c', $branchId, 'hr_employee_contracts');
                $cDoc  = $this->buildBranchCond('d', $branchId, 'hr_documents');
                $cPay  = $this->buildBranchCond('p', $branchId, 'hr_payroll');
                $cRec  = $this->buildBranchCond('r', $branchId, 'hr_recruitment');
                $cAppr = $this->buildBranchCond('ap', $branchId, 'hr_appraisals');
                $cLev  = $this->buildBranchCond('l', $branchId, 'hr_leaves');

                // KPIs
                $kpis['total_employees'] = (int) $this->db->query("SELECT COUNT(*) FROM hr_employees e WHERE e.status = 'active' AND e.company_id = {$companyId} {$cEmp}")->fetchColumn();
                
                $attData = $this->db->query("
                    SELECT 
                        SUM(IF(a.status IN ('present', 'late', 'half_day'), 1, 0)) as present_cnt,
                        SUM(IF(a.status = 'absent', 1, 0)) as absent_cnt,
                        SUM(IF(a.status = 'on_leave', 1, 0)) as leave_cnt
                    FROM hr_attendance a 
                    WHERE a.date = '{$today}' AND a.company_id = {$companyId} {$cAtt}
                ")->fetch(PDO::FETCH_OBJ);

                if ($attData) {
                    $kpis['present_today']  = (int)$attData->present_cnt;
                    $kpis['absent_today']   = (int)$attData->absent_cnt;
                    $kpis['on_leave_today'] = (int)$attData->leave_cnt;
                    $charts['att_status_data'] = [(int)$attData->present_cnt, (int)$attData->absent_cnt, (int)$attData->leave_cnt];
                }

                $kpis['contracts_expiring'] = (int) $this->db->query("SELECT COUNT(*) FROM hr_employee_contracts c WHERE c.status = 'active' AND c.company_id = {$companyId} AND c.end_date BETWEEN '{$today}' AND DATE_ADD('{$today}', INTERVAL 30 DAY) {$cCont}")->fetchColumn();
                $kpis['docs_expiring'] = (int) $this->db->query("SELECT COUNT(*) FROM hr_documents d WHERE d.status = 'active' AND d.company_id = {$companyId} AND d.expiry_date BETWEEN '{$today}' AND DATE_ADD('{$today}', INTERVAL 30 DAY) {$cDoc}")->fetchColumn();

                $kpis['latest_payroll_net'] = (float) $this->db->query("SELECT net_pay FROM hr_payroll p WHERE p.company_id = {$companyId} {$cPay} ORDER BY p.id DESC LIMIT 1")->fetchColumn();
                $kpis['active_applicants'] = (int) $this->db->query("SELECT COUNT(*) FROM hr_recruitment r WHERE r.company_id = {$companyId} {$cRec} AND r.status IN ('applied', 'interviewed', 'offered')")->fetchColumn();
                $kpis['avg_appraisal'] = (float) $this->db->query("SELECT COALESCE(AVG(score), 0) FROM hr_appraisals ap WHERE ap.company_id = {$companyId} {$cAppr} AND ap.status = 'approved'")->fetchColumn();

                // Chart 1: الأقسام
                $depts = $this->db->query("
                    SELECT d.name_ar as dept_name, d.name_en as dept_name_en, COUNT(e.id) as emp_count 
                    FROM hr_departments d 
                    LEFT JOIN hr_employees e ON d.id = e.department_id AND e.status = 'active' AND e.company_id = {$companyId} {$cEmp}
                    WHERE d.company_id = {$companyId}
                    GROUP BY d.id HAVING emp_count > 0
                ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
                foreach($depts as $d) {
                    $charts['dept_labels'][] = $isAr ? $d['dept_name'] : ($d['dept_name_en'] ?: $d['dept_name']);
                    $charts['dept_data'][] = (int)$d['emp_count'];
                }

                // Chart 3: التوظيف
                $recData = $this->db->query("
                    SELECT 
                        SUM(IF(r.status = 'applied', 1, 0)) as app_cnt,
                        SUM(IF(r.status = 'interviewed', 1, 0)) as int_cnt,
                        SUM(IF(r.status = 'offered', 1, 0)) as off_cnt,
                        SUM(IF(r.status = 'hired', 1, 0)) as hir_cnt,
                        SUM(IF(r.status = 'rejected', 1, 0)) as rej_cnt
                    FROM hr_recruitment r WHERE r.company_id = {$companyId} {$cRec}
                ")->fetch(PDO::FETCH_OBJ);
                if ($recData) {
                    $charts['recruitment_data'] = [(int)$recData->app_cnt, (int)$recData->int_cnt, (int)$recData->off_cnt, (int)$recData->hir_cnt, (int)$recData->rej_cnt];
                }

                // Chart 4: التقييمات
                $apprData = $this->db->query("
                    SELECT 
                        SUM(IF(ap.rating_grade = 'Excellent', 1, 0)) as exc_cnt,
                        SUM(IF(ap.rating_grade = 'Very Good', 1, 0)) as vg_cnt,
                        SUM(IF(ap.rating_grade = 'Good', 1, 0)) as g_cnt,
                        SUM(IF(ap.rating_grade = 'Acceptable', 1, 0)) as acc_cnt,
                        SUM(IF(ap.rating_grade = 'Needs Improvement', 1, 0)) as ni_cnt
                    FROM hr_appraisals ap WHERE ap.company_id = {$companyId} {$cAppr}
                ")->fetch(PDO::FETCH_OBJ);
                if ($apprData) {
                    $charts['appraisal_data'] = [(int)$apprData->exc_cnt, (int)$apprData->vg_cnt, (int)$apprData->g_cnt, (int)$apprData->acc_cnt, (int)$apprData->ni_cnt];
                }

                // Chart 5: العقود
                $cntData = $this->db->query("
                    SELECT 
                        SUM(IF(c.status = 'active', 1, 0)) as act_cnt,
                        SUM(IF(c.status = 'expired', 1, 0)) as exp_cnt,
                        SUM(IF(c.status = 'terminated', 1, 0)) as trm_cnt
                    FROM hr_employee_contracts c WHERE c.company_id = {$companyId} {$cCont}
                ")->fetch(PDO::FETCH_OBJ);
                if ($cntData) {
                    $charts['contract_status_data'] = [(int)$cntData->act_cnt, (int)$cntData->exp_cnt, (int)$cntData->trm_cnt];
                }

                // Chart 6: الإجازات
                $leaveData = $this->db->query("
                    SELECT 
                        SUM(IF(l.leave_type = 'annual', 1, 0)) as ann_cnt,
                        SUM(IF(l.leave_type = 'sick', 1, 0)) as sck_cnt,
                        SUM(IF(l.leave_type = 'unpaid', 1, 0)) as unp_cnt,
                        SUM(IF(l.leave_type = 'maternity', 1, 0)) as mat_cnt,
                        SUM(IF(l.leave_type = 'other', 1, 0)) as oth_cnt
                    FROM hr_leaves l WHERE l.company_id = {$companyId} {$cLev}
                ")->fetch(PDO::FETCH_OBJ);
                if ($leaveData) {
                    $charts['leave_type_data'] = [(int)$leaveData->ann_cnt, (int)$leaveData->sck_cnt, (int)$leaveData->unp_cnt, (int)$leaveData->mat_cnt, (int)$leaveData->oth_cnt];
                }

                // التنبيهات العاجلة
                $expiringAlerts = $this->db->query("
                    (SELECT 'عقد عمل' as type_ar, 'Contract' as type_en, c.contract_code as code, e.name_ar as emp_name, e.name_en as emp_name_en, c.end_date as exp_date 
                     FROM hr_employee_contracts c 
                     JOIN hr_employees e ON c.employee_id = e.id 
                     WHERE c.company_id = {$companyId} {$cCont} AND c.status = 'active' AND c.end_date BETWEEN '{$today}' AND DATE_ADD('{$today}', INTERVAL 30 DAY))
                    UNION ALL
                    (SELECT 'وثيقة' as type_ar, 'Document' as type_en, d.document_code as code, e.name_ar as emp_name, e.name_en as emp_name_en, d.expiry_date as exp_date 
                     FROM hr_documents d 
                     JOIN hr_employees e ON d.employee_id = e.id 
                     WHERE d.company_id = {$companyId} {$cDoc} AND d.status = 'active' AND d.expiry_date BETWEEN '{$today}' AND DATE_ADD('{$today}', INTERVAL 30 DAY))
                    ORDER BY exp_date ASC LIMIT 8
                ")->fetchAll(PDO::FETCH_OBJ) ?: [];

            } catch (Throwable $e) {
                error_log("HR Dashboard Error: " . $e->getMessage());
            }
        }

        ob_start();
        $viewPath = $this->basePath . '/resources/views/hr/dashboard/index.php';
        if (file_exists($viewPath)) {
            extract([
                'kpis' => $kpis, 'charts' => $charts, 'expiringAlerts' => $expiringAlerts
            ]);
            include $viewPath;
        }
        $content = ob_get_clean();

        ob_start();
        $layoutPath = $this->basePath . '/resources/views/layouts/app.php';
        if (file_exists($layoutPath)) { include $layoutPath; } else { echo $content; }
        
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}