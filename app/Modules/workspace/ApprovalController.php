<?php
// Path: app/Modules/Workspace/Http/Controllers/ApprovalController.php

namespace App\Modules\Workspace\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class ApprovalController extends Controller
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
        $search = trim($_GET['search'] ?? '');
        $typeFilter = trim($_GET['type'] ?? '');
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;

        $allApprovals = [];
        $stats = (object)['total' => 0, 'hr' => 0, 'financial' => 0];

        $companyId = $this->getCompanyId();
        $branchId = $this->getActiveBranchId();

        if ($this->db) {
            try {
                $hasSysBranches = $this->hasColumn('sys_branches', 'id');

                // 1. مصادر الطلبات المعلقة المتاحة في النظام مع جلب الفرع والقيمة المالية
                $sources = [
                    [
                        'table' => 'hr_leaves',
                        'module' => 'hr_leaves',
                        'category' => 'hr',
                        'query' => "SELECT l.id, 'hr_leaves' as source_module, 'طلب إجازة' as type_name_ar, 'Leave Request' as type_name_en, 'hr' as category, 
                                           CONCAT('نوع الإجازة: ', l.leave_type, ' (', l.days_count, ' يوم)') as details, 0.00 as amount, l.created_at, l.status, 
                                           " . ($this->hasColumn('hr_leaves', 'branch_id') && $hasSysBranches ? "COALESCE(br.name_ar, '')" : "''") . " as branch_name
                                    FROM hr_leaves l 
                                    " . ($this->hasColumn('hr_leaves', 'branch_id') && $hasSysBranches ? "LEFT JOIN sys_branches br ON l.branch_id = br.id" : "") . "
                                    WHERE l.status IN ('pending', 'draft', 'submitted') AND (l.company_id = {$companyId} OR l.company_id IS NULL OR l.company_id = 0)" . $this->buildBranchCond('l', $branchId, 'hr_leaves')
                    ],
                    [
                        'table' => 'hr_appraisals',
                        'module' => 'hr_appraisals',
                        'category' => 'hr',
                        'query' => "SELECT a.id, 'hr_appraisals' as source_module, 'تقييم أداء' as type_name_ar, 'Performance Appraisal' as type_name_en, 'hr' as category, 
                                           CONCAT('كود: ', a.appraisal_code, ' - التقدير: ', a.rating_grade) as details, 0.00 as amount, a.created_at, a.status, 
                                           " . ($this->hasColumn('hr_appraisals', 'branch_id') && $hasSysBranches ? "COALESCE(br.name_ar, '')" : "''") . " as branch_name
                                    FROM hr_appraisals a 
                                    " . ($this->hasColumn('hr_appraisals', 'branch_id') && $hasSysBranches ? "LEFT JOIN sys_branches br ON a.branch_id = br.id" : "") . "
                                    WHERE a.status IN ('pending', 'submitted') AND (a.company_id = {$companyId} OR a.company_id IS NULL OR a.company_id = 0)" . $this->buildBranchCond('a', $branchId, 'hr_appraisals')
                    ],
                    [
                        'table' => 'purchase_requests',
                        'module' => 'purchase_requests',
                        'category' => 'financial',
                        'query' => "SELECT pr.id, 'purchase_requests' as source_module, 'طلب شراء' as type_name_ar, 'Purchase Request' as type_name_en, 'financial' as category, 
                                           CONCAT('رقم الطلب: ', pr.pr_number, ' - القسم: ', COALESCE(pr.department, 'عام')) as details, pr.total_estimated_value as amount, pr.created_at, pr.status, 
                                           " . ($this->hasColumn('purchase_requests', 'branch_id') && $hasSysBranches ? "COALESCE(br.name_ar, '')" : "''") . " as branch_name
                                    FROM purchase_requests pr 
                                    " . ($this->hasColumn('purchase_requests', 'branch_id') && $hasSysBranches ? "LEFT JOIN sys_branches br ON pr.branch_id = br.id" : "") . "
                                    WHERE pr.status IN ('pending', 'draft') AND (pr.company_id = {$companyId} OR pr.company_id IS NULL OR pr.company_id = 0)" . $this->buildBranchCond('pr', $branchId, 'purchase_requests')
                    ],
                    [
                        'table' => 'purchase_orders',
                        'module' => 'purchase_orders',
                        'category' => 'financial',
                        'query' => "SELECT po.id, 'purchase_orders' as source_module, 'أمر شراء' as type_name_ar, 'Purchase Order' as type_name_en, 'financial' as category, 
                                           CONCAT('رقم الأمر: ', po.po_number, ' - المورد: ', COALESCE(s.name_ar, 'عام')) as details, po.total_amount as amount, po.created_at, po.status, 
                                           " . ($this->hasColumn('purchase_orders', 'branch_id') && $hasSysBranches ? "COALESCE(br.name_ar, '')" : "''") . " as branch_name
                                    FROM purchase_orders po 
                                    LEFT JOIN suppliers s ON po.supplier_id = s.id
                                    " . ($this->hasColumn('purchase_orders', 'branch_id') && $hasSysBranches ? "LEFT JOIN sys_branches br ON po.branch_id = br.id" : "") . "
                                    WHERE po.status IN ('draft', 'sent', 'pending') AND (po.company_id = {$companyId} OR po.company_id IS NULL OR po.company_id = 0)" . $this->buildBranchCond('po', $branchId, 'purchase_orders')
                    ]
                ];

                // 2. تجميع البيانات
                foreach ($sources as $src) {
                    if ($typeFilter !== '' && $typeFilter !== $src['category']) continue;
                    
                    try {
                        $stmt = $this->db->query($src['query']);
                        $results = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
                        foreach ($results as $row) {
                            $allApprovals[] = $row;
                        }
                    } catch (Throwable $e) {
                        // كتم أخطاء الجداول غير المكتملة دون حجب الصفحة
                    }
                }

                // 3. الفلترة بالتكست
                if ($search !== '') {
                    $allApprovals = array_filter($allApprovals, function($item) use ($search) {
                        $nameAr = $item->type_name_ar ?? '';
                        $nameEn = $item->type_name_en ?? '';
                        $details = $item->details ?? '';
                        return (mb_strpos(mb_strtolower($nameAr), mb_strtolower($search)) !== false) ||
                               (mb_strpos(mb_strtolower($nameEn), mb_strtolower($search)) !== false) ||
                               (mb_strpos(mb_strtolower($details), mb_strtolower($search)) !== false);
                    });
                    $allApprovals = array_values($allApprovals);
                }

                // 4. الفرز التنازلي التراكمي
                usort($allApprovals, function($a, $b) {
                    $timeA = !empty($a->created_at) ? strtotime($a->created_at) : 0;
                    $timeB = !empty($b->created_at) ? strtotime($b->created_at) : 0;
                    return $timeB <=> $timeA;
                });

                // 5. احتساب الإحصائيات
                $stats->total = count($allApprovals);
                foreach ($allApprovals as $item) {
                    if (($item->category ?? '') === 'hr') $stats->hr++;
                    if (($item->category ?? '') === 'financial') $stats->financial++;
                }

                // 6. الترقيم الحصين وتجزئة البيانات للصفحة الحالية
                $totalPages = max(1, (int)ceil($stats->total / $limit));
                if ($page > $totalPages) {
                    $page = 1;
                }
                $offset = ($page - 1) * $limit;
                $pagedApprovals = array_slice($allApprovals, $offset, $limit);

            } catch (Throwable $e) {
                error_log("Approvals Engine Error: " . $e->getMessage());
                $pagedApprovals = [];
                $totalPages = 1;
            }
        } else {
            $pagedApprovals = [];
            $totalPages = 1;
        }

        $currentPage = $page;

        ob_start();
        include $this->basePath . '/resources/views/workspace/approvals/index.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function process(Request $request, Response $response)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $data = $_POST;
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if (!$this->db) throw new Exception($isAr ? "اتصال قاعدة البيانات غير متوفر." : "Database connection not available.");
            
            $id = (int)($data['request_id'] ?? 0);
            $module = trim($data['source_module'] ?? '');
            $action = trim($data['action_type'] ?? '');

            if (!$id || !$module || !in_array($action, ['approve', 'reject'])) {
                throw new Exception($isAr ? "بيانات الطلب غير صالحة." : "Invalid request parameters.");
            }

            $allowedModules = ['hr_leaves', 'hr_appraisals', 'purchase_requests', 'purchase_orders', 'pur_orders', 'sales_quotations'];
            if (!in_array($module, $allowedModules)) {
                throw new Exception($isAr ? "الجدول المستهدف غير مصرح به." : "Module not authorized.");
            }

            $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
            if ($module === 'purchase_orders') {
                $newStatus = ($action === 'approve') ? 'confirmed' : 'cancelled';
            }

            $stmt = $this->db->prepare("UPDATE {$module} SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $id]);

            $_SESSION['flash_msg'] = ($action === 'approve') 
                ? ($isAr ? "تم اعتماد الطلب بنجاح." : "Request approved successfully.") 
                : ($isAr ? "تم رفض الطلب." : "Request rejected.");
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = ($isAr ? "خطأ في المعالجة: " : "Processing Error: ") . $e->getMessage();
        }

        header("Location: /ERP/workspace/approvals");
        exit;
    }
}