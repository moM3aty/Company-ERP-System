<?php
// Path: app/Modules/Workspace\Http\Controllers\ApprovalController.php

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
            if ($this->db) {
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        }
    }

    public function index(Request $request, Response $response): Response
    {
        $search = trim($_GET['search'] ?? '');
        $typeFilter = trim($_GET['type'] ?? '');
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;

        $allApprovals = [];
        $stats = (object)['total' => 0, 'hr' => 0, 'financial' => 0];

        if ($this->db) {
            try {
                // 1. تعريف مصادر البيانات (الموديولات التي تحتاج موافقات)
                // يمكنك إضافة أي جدول مستقبلاً هنا بكل سهولة
                $sources = [
                    [
                        'module' => 'hr_leaves',
                        'type_name' => 'طلب إجازة',
                        'category' => 'hr',
                        'query' => "SELECT id, 'hr_leaves' as source_module, 'طلب إجازة' as type_name, 'hr' as category, 
                                    leave_type as details, created_at, status 
                                    FROM hr_leaves WHERE status = 'pending'"
                    ],
                    [
                        'module' => 'hr_appraisals',
                        'type_name' => 'تقييم أداء',
                        'category' => 'hr',
                        'query' => "SELECT id, 'hr_appraisals' as source_module, 'تقييم أداء' as type_name, 'hr' as category, 
                                    rating_grade as details, created_at, status 
                                    FROM hr_appraisals WHERE status = 'pending'"
                    ],
                    /* مثال للمشتريات (فك التعليق عند اكتمال جداول المشتريات)
                    [
                        'module' => 'pur_orders',
                        'type_name' => 'أمر شراء',
                        'category' => 'financial',
                        'query' => "SELECT id, 'pur_orders' as source_module, 'أمر شراء' as type_name, 'financial' as category, 
                                    CONCAT('إجمالي: ', total_amount) as details, created_at, status 
                                    FROM pur_orders WHERE status = 'pending'"
                    ]
                    */
                ];

                // 2. تجميع البيانات ديناميكياً (Aggregating)
                foreach ($sources as $src) {
                    if ($typeFilter !== '' && $typeFilter !== $src['category']) continue;
                    
                    try {
                        // استخدام try-catch داخلي لتجنب توقف الشاشة إذا كان أحد الجداول غير موجود بعد
                        $stmt = $this->db->query($src['query']);
                        $results = $stmt->fetchAll(PDO::FETCH_OBJ);
                        foreach ($results as $row) {
                            $allApprovals[] = $row;
                        }
                    } catch (Throwable $e) {
                        // تجاهل الجداول غير الموجودة بصمت
                    }
                }

                // 3. البحث في المصفوفة المجمعة (PHP Filter)
                if ($search !== '') {
                    $allApprovals = array_filter($allApprovals, function($item) use ($search) {
                        return str_contains(strtolower($item->type_name), strtolower($search)) || 
                               str_contains(strtolower($item->details ?? ''), strtolower($search));
                    });
                }

                // 4. ترتيب تنازلي حسب التاريخ (أحدث الطلبات أولاً)
                usort($allApprovals, function($a, $b) {
                    return strtotime($b->created_at) <=> strtotime($a->created_at);
                });

                // 5. حساب الإحصائيات
                $stats->total = count($allApprovals);
                foreach ($allApprovals as $item) {
                    if ($item->category === 'hr') $stats->hr++;
                    if ($item->category === 'financial') $stats->financial++;
                }

                // 6. التقسيم لصفحات (Pagination Slice)
                $totalPages = max(1, ceil($stats->total / $limit));
                $offset = ($page - 1) * $limit;
                $pagedApprovals = array_slice($allApprovals, $offset, $limit);

            } catch (Throwable $e) {
                error_log("Approvals Engine Error: " . $e->getMessage());
                $pagedApprovals = [];
                $totalPages = 1;
            }
        }

        return $this->renderView('/resources/views/workspace/approvals/index.php', [
            'approvals' => $pagedApprovals,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'typeFilter' => $typeFilter
        ], $response);
    }

    public function process(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $data = $_POST;

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");
            
            $id = (int)($data['request_id'] ?? 0);
            $module = $data['source_module'] ?? '';
            $action = $data['action_type'] ?? ''; // 'approve' or 'reject'

            if (!$id || !$module || !in_array($action, ['approve', 'reject'])) {
                throw new Exception("بيانات الطلب غير صالحة.");
            }

            // تحديد الحالة الجديدة بناءً على الإجراء
            $newStatus = ($action === 'approve') ? 'approved' : 'rejected';

            // حماية بسيطة لتجنب حقن SQL في اسم الجدول (يجب أن يكون ضمن الجداول المعتمدة)
            $allowedModules = ['hr_leaves', 'hr_appraisals', 'pur_orders', 'pur_requisitions', 'sales_quotations'];
            if (!in_array($module, $allowedModules)) {
                throw new Exception("الجدول المستهدف غير مصرح به.");
            }

            // تنفيذ التحديث الديناميكي
            $stmt = $this->db->prepare("UPDATE {$module} SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $id]);

            $_SESSION['flash_msg'] = ($action === 'approve') ? "تم اعتماد الطلب بنجاح." : "تم رفض الطلب.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ في المعالجة: " . $e->getMessage();
        }

        return new RedirectResponse('/ERP/workspace/approvals');
    }

    private function renderView(string $viewPath, array $data, Response $response): Response
    {
        extract($data);
        $fullPath = $this->basePath . $viewPath;

        if (!file_exists($fullPath)) {
            die("View File Missing: " . htmlspecialchars($fullPath));
        }

        try {
            ob_start();
            include $fullPath;
            $content = ob_get_clean();

            ob_start();
            include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');
        } catch (Throwable $e) {
            while (ob_get_level() > 0) ob_end_clean();
            die("View Render Error: " . htmlspecialchars($e->getMessage()));
        }
    }
}