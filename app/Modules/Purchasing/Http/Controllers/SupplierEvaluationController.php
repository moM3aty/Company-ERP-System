<?php
// Path: app/Modules/Purchasing/Http/Controllers/SupplierEvaluationController.php

namespace App\Modules\Purchasing\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;

class SupplierEvaluationController extends Controller
{
    private string $basePath;
    private PDO $db;

    public function __construct()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    public function index(Request $request, Response $response): Response
    {
        $dbError = null;
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $whereClause = "";
            $params = [];
            if ($search !== '') {
                $whereClause = "WHERE se.eval_number LIKE ? OR s.name_ar LIKE ? OR s.name_en LIKE ? OR se.evaluator_name LIKE ?";
                $like = "%{$search}%";
                $params = [$like, $like, $like, $like];
            }

            // 1. حساب الإحصائيات مع الفلترة عبر SQL مباشرة لتوفير الأداء
            $statsStmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_evals, 
                    COALESCE(AVG(se.overall_score), 0) as avg_score,
                    SUM(CASE WHEN se.grade IN ('A+', 'A') THEN 1 ELSE 0 END) as excellent_suppliers
                FROM supplier_evaluations se
                LEFT JOIN suppliers s ON se.supplier_id = s.id
                $whereClause
            ");
            $statsStmt->execute($params);
            $statsData = $statsStmt->fetch(PDO::FETCH_OBJ);

            $stats = (object)[
                'total_evals' => (int)($statsData->total_evals ?? 0),
                'avg_score' => round((float)($statsData->avg_score ?? 0), 1),
                'excellent_suppliers' => (int)($statsData->excellent_suppliers ?? 0)
            ];

            $totalItems = $stats->total_evals;
            $totalPages = max(1, ceil($totalItems / $limit));

            // 2. جلب سجلات الصفحة الحالية فقط
            $stmt = $this->db->prepare("
                SELECT se.*, COALESCE(s.name_ar, s.name_en) as supplier_name, s.code as supplier_code
                FROM supplier_evaluations se
                LEFT JOIN suppliers s ON se.supplier_id = s.id
                $whereClause
                ORDER BY se.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $evaluations = $stmt->fetchAll(PDO::FETCH_OBJ);
            if ($evaluations === false) $evaluations = [];

        } catch (Exception $e) {
            $evaluations = [];
            $stats = (object)['total_evals' => 0, 'avg_score' => 0, 'excellent_suppliers' => 0];
            $totalPages = 1;
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/purchasing/evaluations/index.php';
        if ($dbError) echo "<div style='margin:20px; padding:20px; background:#fef2f2; color:#b91c1c; border-radius:8px;'><strong>DB Error:</strong> $dbError</div>";
        if (file_exists($viewPath)) include $viewPath;

        $content = ob_get_clean();

        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $evaluation = null;
        try {
            $suppliers = $this->db->query("SELECT id, code, name_ar, name_en FROM suppliers WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            $suppliers = [];
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/evaluations/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            $companyId = $_SESSION['company_id'] ?? 1;
            $evalNum = !empty($data['eval_number']) ? trim($data['eval_number']) : 'EVL-' . date('Ym') . rand(100, 999);

            $delScore = min(100, max(0, (float)($data['delivery_score'] ?? 0)));
            $qualScore = min(100, max(0, (float)($data['quality_score'] ?? 0)));
            $prcScore = min(100, max(0, (float)($data['price_score'] ?? 0)));
            $srvScore = min(100, max(0, (float)($data['service_score'] ?? 0)));

            $overall = round(($delScore + $qualScore + $prcScore + $srvScore) / 4, 2);
            $grade = $this->calculateGrade($overall);

            $stmt = $this->db->prepare("
                INSERT INTO supplier_evaluations 
                (company_id, supplier_id, eval_number, evaluation_date, evaluator_name, period_covered, delivery_score, quality_score, price_score, service_score, overall_score, grade, status, recommendation)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, $data['supplier_id'], $evalNum,
                $data['evaluation_date'] ?? date('Y-m-d'),
                $data['evaluator_name'] ?? null,
                $data['period_covered'] ?? null,
                $delScore, $qualScore, $prcScore, $srvScore,
                $overall, $grade, $data['status'] ?? 'approved',
                $data['recommendation'] ?? null
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم حفظ تقييم المورد بنجاح بنسبة $overall% ($grade)" : "Evaluation saved successfully!";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/supplier-evaluations/create');
        }

        return new RedirectResponse('/ERP/purchasing/supplier-evaluations');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM supplier_evaluations WHERE id = ?");
            $stmt->execute([$id]);
            $evaluation = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$evaluation) throw new Exception("التقييم غير موجود.");

            $suppliers = $this->db->query("SELECT id, code, name_ar, name_en FROM suppliers WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/supplier-evaluations');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/evaluations/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $data = $request->getParsedBody();
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            $delScore = min(100, max(0, (float)($data['delivery_score'] ?? 0)));
            $qualScore = min(100, max(0, (float)($data['quality_score'] ?? 0)));
            $prcScore = min(100, max(0, (float)($data['price_score'] ?? 0)));
            $srvScore = min(100, max(0, (float)($data['service_score'] ?? 0)));

            $overall = round(($delScore + $qualScore + $prcScore + $srvScore) / 4, 2);
            $grade = $this->calculateGrade($overall);

            $stmt = $this->db->prepare("
                UPDATE supplier_evaluations 
                SET supplier_id=?, evaluation_date=?, evaluator_name=?, period_covered=?, delivery_score=?, quality_score=?, price_score=?, service_score=?, overall_score=?, grade=?, status=?, recommendation=?
                WHERE id=?
            ");
            $stmt->execute([
                $data['supplier_id'], $data['evaluation_date'], $data['evaluator_name'] ?? null,
                $data['period_covered'] ?? null, $delScore, $qualScore, $prcScore, $srvScore,
                $overall, $grade, $data['status'] ?? 'approved', $data['recommendation'] ?? null, $id
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث التقييم بنجاح." : "Evaluation updated successfully.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/purchasing/supplier-evaluations/{$id}/edit");
        }

        return new RedirectResponse('/ERP/purchasing/supplier-evaluations');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        try {
            $stmt = $this->db->prepare("
                SELECT se.*, 
                       COALESCE(s.name_ar, s.name_en) as supplier_name, 
                       s.code as supplier_code, 
                       s.phone as supplier_phone, 
                       s.tax_number as supplier_tax
                FROM supplier_evaluations se
                LEFT JOIN suppliers s ON se.supplier_id = s.id
                WHERE se.id = ?
            ");
            $stmt->execute([$id]);
            $evaluation = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$evaluation) throw new Exception("التقييم غير موجود.");

        } catch (Exception $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/supplier-evaluations');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/evaluations/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        try {
            $stmt = $this->db->prepare("DELETE FROM supplier_evaluations WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف التقييم بنجاح.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "خطأ أثناء عملية الحذف.";
        }
        return new RedirectResponse('/ERP/purchasing/supplier-evaluations');
    }

    private function calculateGrade(float $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        return 'D';
    }
}