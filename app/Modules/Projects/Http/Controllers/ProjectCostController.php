<?php
// Path: app/Modules/Projects/Http/Controllers/ProjectCostController.php

namespace App\Modules\Projects\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class ProjectCostController extends Controller
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

    private function resolveId($id = null): ?int
    {
        if (!empty($id) && is_numeric($id)) return (int)$id;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/projects/costs/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/projects/costs/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/projects/costs/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/projects/costs/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/projects/costs/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $projectId = trim($_GET['project_id'] ?? '');
        $categoryFilter = trim($_GET['cost_category'] ?? '');
        $statusFilter = trim($_GET['payment_status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $costs = [];
        $projects = [];
        $stats = (object)[
            'total_vouchers' => 0,
            'total_amount' => 0,
            'materials_cost' => 0,
            'labor_cost' => 0,
            'equipment_cost' => 0
        ];
        $totalPages = 1;

        if ($this->db) {
            try {
                $bCond = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";
                $projects = $this->db->query("SELECT id, code, COALESCE(name_ar, name_en) as name_ar FROM projects WHERE company_id = $companyId $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ) ?: [];

                $where = ["c.company_id = ?"];
                $params = [$companyId];

                if ($branchId > 0) {
                    $where[] = "(c.branch_id = ? OR c.branch_id IS NULL OR c.branch_id = 0)";
                    $params[] = $branchId;
                }

                if ($search !== '') {
                    $where[] = "(c.voucher_number LIKE ? OR c.description LIKE ? OR c.reference_no LIKE ? OR p.name_ar LIKE ? OR s.name_ar LIKE ?)";
                    $like = "%{$search}%";
                    $params = array_merge($params, [$like, $like, $like, $like, $like]);
                }

                if ($projectId !== '') {
                    $where[] = "c.project_id = ?";
                    $params[] = (int)$projectId;
                }

                if ($categoryFilter !== '') {
                    $where[] = "c.cost_category = ?";
                    $params[] = $categoryFilter;
                }

                if ($statusFilter !== '') {
                    $where[] = "c.payment_status = ?";
                    $params[] = $statusFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM project_costs c
                    LEFT JOIN projects p ON c.project_id = p.id
                    LEFT JOIN suppliers s ON c.supplier_id = s.id
                    $whereSql
                ");
                $countStmt->execute($params);
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT c.*, COALESCE(p.name_ar, p.name_en) as project_name, p.code as project_code, s.name_ar as supplier_name, a.name_ar as account_name
                    FROM project_costs c
                    LEFT JOIN projects p ON c.project_id = p.id
                    LEFT JOIN suppliers s ON c.supplier_id = s.id
                    LEFT JOIN accounts a ON c.account_id = a.id
                    $whereSql
                    ORDER BY c.cost_date DESC, c.id DESC 
                    LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $costs = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                foreach ($costs as $c) {
                    $c->amount = convert_amount($c->amount);
                }

                $statsStmt = $this->db->prepare("
                    SELECT 
                        COUNT(*) as total_vouchers,
                        COALESCE(SUM(amount), 0) as total_amount,
                        COALESCE(SUM(IF(cost_category = 'materials', amount, 0)), 0) as materials_cost,
                        COALESCE(SUM(IF(cost_category = 'labor', amount, 0)), 0) as labor_cost,
                        COALESCE(SUM(IF(cost_category IN ('equipment','subcontractor'), amount, 0)), 0) as equipment_cost
                    FROM project_costs c
                    WHERE c.company_id = ? " . ($branchId > 0 ? " AND (c.branch_id = $branchId OR c.branch_id IS NULL OR c.branch_id = 0)" : "") . "
                ");
                $statsStmt->execute([$companyId]);
                $statsData = $statsStmt->fetch(PDO::FETCH_OBJ);
                if ($statsData) {
                    $stats = $statsData;
                    $stats->total_amount = convert_amount($stats->total_amount);
                    $stats->materials_cost = convert_amount($stats->materials_cost);
                    $stats->labor_cost = convert_amount($stats->labor_cost);
                    $stats->equipment_cost = convert_amount($stats->equipment_cost);
                }

            } catch (Throwable $e) {
                error_log("Project Costs Index Error: " . $e->getMessage());
            }
        }

        $currentPage = $page;

        return $this->renderView('/resources/views/projects/costs/index.php', [
            'costs' => $costs,
            'projects' => $projects,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'search' => $search,
            'projectId' => $projectId,
            'categoryFilter' => $categoryFilter,
            'statusFilter' => $statusFilter
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        $cost = null; 
        $projects = [];
        $suppliers = [];
        $accounts = [];
        $autoCode = 'COST-PRJ-' . date('Y') . '-0001';

        if ($this->db) {
            try {
                $bCond = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";
                $projects = $this->db->query("SELECT id, code, COALESCE(name_ar, name_en) as name_ar FROM projects WHERE company_id = $companyId $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                $suppliers = $this->db->query("SELECT id, name_ar FROM suppliers WHERE company_id = $companyId ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                $accounts = $this->db->query("SELECT id, code, name_ar FROM accounts WHERE company_id = $companyId AND type IN ('expense','asset') ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                
                $nextSeq = (int)$this->db->query("SELECT COUNT(*) FROM project_costs WHERE company_id = $companyId")->fetchColumn() + 1;
                $autoCode = 'COST-PRJ-' . date('Y') . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/projects/costs/create.php', [
            'cost' => $cost,
            'projects' => $projects,
            'suppliers' => $suppliers,
            'accounts' => $accounts,
            'autoCode' => $autoCode
        ], $response);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        try {
            if (!$this->db) throw new Exception("Database connection unavailable.");

            if (empty($data['voucher_number']) || empty($data['project_id']) || empty($data['cost_date']) || empty($data['amount'])) {
                throw new Exception($isAr ? "يرجى تعبئة الحقول الأساسية למصروف الموقع." : "Please fill required fields.");
            }

            $projectId = (int)$data['project_id'];
            $amount = (float)$data['amount'];

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO project_costs 
                    (company_id, branch_id, voucher_number, project_id, cost_category, cost_date, amount, supplier_id, account_id, reference_no, payment_status, description, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $branchId,
                    trim($data['voucher_number']),
                    $projectId,
                    $data['cost_category'] ?? 'materials',
                    $data['cost_date'],
                    $amount,
                    !empty($data['supplier_id']) ? (int)$data['supplier_id'] : null,
                    !empty($data['account_id']) ? (int)$data['account_id'] : null,
                    trim($data['reference_no'] ?? ''),
                    $data['payment_status'] ?? 'paid',
                    trim($data['description'] ?? ''),
                    trim($data['notes'] ?? ''),
                    $_SESSION['user_id'] ?? 1
                ]);
            } catch (\PDOException $ex) {
                // Fallback
                $stmt = $this->db->prepare("
                    INSERT INTO project_costs 
                    (voucher_number, project_id, cost_category, cost_date, amount, supplier_id, account_id, reference_no, payment_status, description, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    trim($data['voucher_number']), $projectId, $data['cost_category'] ?? 'materials',
                    $data['cost_date'], $amount, !empty($data['supplier_id']) ? (int)$data['supplier_id'] : null,
                    !empty($data['account_id']) ? (int)$data['account_id'] : null, trim($data['reference_no'] ?? ''),
                    $data['payment_status'] ?? 'paid', trim($data['description'] ?? ''), trim($data['notes'] ?? ''), $_SESSION['user_id'] ?? 1
                ]);
            }

            $this->recalculateProjectSpentAmount($projectId);

            $_SESSION['flash_msg'] = $isAr ? "تم تسجيل مصروف الموقع بنجاح." : "Site cost logged successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/projects/costs/create');
        }

        return new RedirectResponse('/ERP/projects/costs');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        $cost = null;
        $projects = [];
        $suppliers = [];
        $accounts = [];
        $autoCode = '';

        try {
            if (!$this->db) throw new Exception("Database connection unavailable.");

            $stmt = $this->db->prepare("SELECT * FROM project_costs WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $cost = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$cost) throw new Exception("سجل المصروف غير موجود.");

            $bCond = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";
            $projects = $this->db->query("SELECT id, code, COALESCE(name_ar, name_en) as name_ar FROM projects WHERE company_id = $companyId $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $suppliers = $this->db->query("SELECT id, name_ar FROM suppliers WHERE company_id = $companyId ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $accounts = $this->db->query("SELECT id, code, name_ar FROM accounts WHERE company_id = $companyId AND type IN ('expense','asset') ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $autoCode = $cost->voucher_number;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/projects/costs');
        }

        return $this->renderView('/resources/views/projects/costs/create.php', [
            'cost' => $cost,
            'projects' => $projects,
            'suppliers' => $suppliers,
            'accounts' => $accounts,
            'autoCode' => $autoCode
        ], $response);
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            if (!$this->db) throw new Exception("Database error.");

            $projectId = (int)$data['project_id'];

            $stmt = $this->db->prepare("
                UPDATE project_costs 
                SET project_id = ?, cost_category = ?, cost_date = ?, amount = ?, supplier_id = ?, account_id = ?, reference_no = ?, payment_status = ?, description = ?, notes = ?
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([
                $projectId,
                $data['cost_category'] ?? 'materials',
                $data['cost_date'],
                (float)$data['amount'],
                !empty($data['supplier_id']) ? (int)$data['supplier_id'] : null,
                !empty($data['account_id']) ? (int)$data['account_id'] : null,
                trim($data['reference_no'] ?? ''),
                $data['payment_status'] ?? 'paid',
                trim($data['description'] ?? ''),
                trim($data['notes'] ?? ''),
                $id, $companyId
            ]);

            $this->recalculateProjectSpentAmount($projectId);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات مصروف الموقع." : "Cost updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/projects/costs/{$id}/edit");
        }

        return new RedirectResponse('/ERP/projects/costs');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            if ($this->db) {
                $pStmt = $this->db->prepare("SELECT project_id FROM project_costs WHERE id = ? AND company_id = ?");
                $pStmt->execute([$id, $companyId]);
                $projId = $pStmt->fetchColumn();

                $this->db->prepare("DELETE FROM project_costs WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
                
                if ($projId) {
                    $this->recalculateProjectSpentAmount((int)$projId);
                }

                $_SESSION['flash_msg'] = "تم حذف مصروف الموقع بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء الحذف.";
        }

        return new RedirectResponse('/ERP/projects/costs');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        
        $cost = null;
        if ($this->db) {
            $stmt = $this->db->prepare("
                SELECT c.*, COALESCE(p.name_ar, p.name_en) as project_name, p.code as project_code, s.name_ar as supplier_name, a.name_ar as account_name
                FROM project_costs c
                LEFT JOIN projects p ON c.project_id = p.id
                LEFT JOIN suppliers s ON c.supplier_id = s.id
                LEFT JOIN accounts a ON c.account_id = a.id
                WHERE c.id = ? AND c.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $cost = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$cost) {
            $_SESSION['flash_err'] = "سجل مصروف الموقع غير موجود.";
            return new RedirectResponse('/ERP/projects/costs');
        }

        // تطبيق تحويل العملات للعرض
        $cost->amount = convert_amount($cost->amount);

        return $this->renderView('/resources/views/projects/costs/show.php', [
            'cost' => $cost
        ], $response);
    }

    private function recalculateProjectSpentAmount(int $projectId): void
    {
        if (!$this->db) return;
        try {
            $totalSpent = (float)$this->db->query("SELECT COALESCE(SUM(amount), 0) FROM project_costs WHERE project_id = {$projectId}")->fetchColumn();
            $stmt = $this->db->prepare("UPDATE projects SET spent_amount = ? WHERE id = ?");
            $stmt->execute([$totalSpent, $projectId]);
        } catch (Throwable $e) {
            error_log("Recalculate Spent Error: " . $e->getMessage());
        }
    }

    private function renderView(string $viewPath, array $data, Response $response): Response
    {
        extract($data);
        $fullPath = $this->basePath . $viewPath;

        if (!file_exists($fullPath)) {
            die("<div style='padding:30px; background:#fff; color:#dc2626;'>View Missing: " . htmlspecialchars($fullPath) . "</div>");
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