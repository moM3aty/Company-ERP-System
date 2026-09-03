<?php
// Path: app/Modules/Projects/Http/Controllers/ProjectContractController.php

namespace App\Modules\Projects\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class ProjectContractController extends Controller
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
        if (preg_match('#/projects/contracts/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/projects/contracts/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/projects/contracts/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/projects/contracts/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/projects/contracts/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $projectId = trim($_GET['project_id'] ?? '');
        $typeFilter = trim($_GET['contract_type'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $contracts = [];
        $projects = [];
        $stats = (object)[
            'total_contracts' => 0,
            'total_value' => 0,
            'active_count' => 0,
            'avg_retention' => 0
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
                    $where[] = "(c.contract_number LIKE ? OR c.title_ar LIKE ? OR c.title_en LIKE ? OR p.name_ar LIKE ? OR cust.name_ar LIKE ?)";
                    $like = "%{$search}%";
                    $params = array_merge($params, [$like, $like, $like, $like, $like]);
                }

                if ($projectId !== '') {
                    $where[] = "c.project_id = ?";
                    $params[] = (int)$projectId;
                }

                if ($typeFilter !== '') {
                    $where[] = "c.contract_type = ?";
                    $params[] = $typeFilter;
                }

                if ($statusFilter !== '') {
                    $where[] = "c.status = ?";
                    $params[] = $statusFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM project_contracts c
                    LEFT JOIN projects p ON c.project_id = p.id
                    LEFT JOIN customers cust ON c.customer_id = cust.id
                    $whereSql
                ");
                $countStmt->execute($params);
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT c.*, COALESCE(p.name_ar, p.name_en) as project_name, p.code as project_code, cust.name_ar as customer_name
                    FROM project_contracts c
                    LEFT JOIN projects p ON c.project_id = p.id
                    LEFT JOIN customers cust ON c.customer_id = cust.id
                    $whereSql
                    ORDER BY c.id DESC 
                    LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $contracts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                // Convert Currency
                foreach ($contracts as $c) {
                    $c->contract_value = convert_amount((float)$c->contract_value);
                }

                $statsStmt = $this->db->prepare("
                    SELECT 
                        COUNT(*) as total_contracts,
                        COALESCE(SUM(contract_value), 0) as total_value,
                        SUM(IF(status = 'active', 1, 0)) as active_count,
                        COALESCE(AVG(retention_percent), 0) as avg_retention
                    FROM project_contracts c
                    WHERE c.company_id = ? " . ($branchId > 0 ? " AND (c.branch_id = $branchId OR c.branch_id IS NULL OR c.branch_id = 0)" : "") . "
                ");
                $statsStmt->execute([$companyId]);
                $statsData = $statsStmt->fetch(PDO::FETCH_OBJ);
                if ($statsData) {
                    $stats = $statsData;
                    $stats->total_value = convert_amount((float)$stats->total_value);
                }

            } catch (Throwable $e) {
                error_log("Project Contracts Index Error: " . $e->getMessage());
            }
        }

        $currentPage = $page;

        return $this->renderView('/resources/views/projects/contracts/index.php', [
            'contracts' => $contracts,
            'projects' => $projects,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'search' => $search,
            'projectId' => $projectId,
            'typeFilter' => $typeFilter,
            'statusFilter' => $statusFilter
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        $contract = null; 
        $projects = [];
        $customers = [];
        $autoCode = 'CON-PRJ-' . date('Y') . '-0001';

        if ($this->db) {
            try {
                $bCond = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";
                $projects = $this->db->query("SELECT id, code, COALESCE(name_ar, name_en) as name_ar, customer_id, contract_value FROM projects WHERE company_id = $companyId $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                $customers = $this->db->query("SELECT id, name_ar FROM customers WHERE company_id = $companyId ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                
                $nextSeq = (int)$this->db->query("SELECT COUNT(*) FROM project_contracts WHERE company_id = $companyId")->fetchColumn() + 1;
                $autoCode = 'CON-PRJ-' . date('Y') . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/projects/contracts/create.php', [
            'contract' => $contract,
            'projects' => $projects,
            'customers' => $customers,
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

            if (empty($data['contract_number']) || empty($data['title_ar']) || empty($data['project_id']) || empty($data['start_date'])) {
                throw new Exception($isAr ? "يرجى تعبئة الحقول الأساسية للعقد." : "Please fill required fields.");
            }

            $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : null;
            if (!$customerId) {
                $pStmt = $this->db->prepare("SELECT customer_id FROM projects WHERE id = ? AND company_id = ?");
                $pStmt->execute([(int)$data['project_id'], $companyId]);
                $customerId = $pStmt->fetchColumn() ?: null;
            }

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO project_contracts 
                    (company_id, branch_id, contract_number, project_id, customer_id, title_ar, title_en, contract_type, contract_value, advance_payment_amount, retention_percent, start_date, end_date, sign_date, status, terms_and_conditions, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $branchId,
                    trim($data['contract_number']), (int)$data['project_id'], $customerId,
                    trim($data['title_ar']), trim($data['title_en'] ?? ''),
                    $data['contract_type'] ?? 'owner_contract',
                    !empty($data['contract_value']) ? (float)$data['contract_value'] : 0.00,
                    !empty($data['advance_payment_amount']) ? (float)$data['advance_payment_amount'] : 0.00,
                    !empty($data['retention_percent']) ? (float)$data['retention_percent'] : 0.00,
                    $data['start_date'], !empty($data['end_date']) ? $data['end_date'] : null,
                    !empty($data['sign_date']) ? $data['sign_date'] : null,
                    $data['status'] ?? 'active', trim($data['terms_and_conditions'] ?? ''),
                    trim($data['notes'] ?? ''), $_SESSION['user_id'] ?? 1
                ]);
            } catch (\PDOException $ex) {
                $stmt = $this->db->prepare("
                    INSERT INTO project_contracts 
                    (contract_number, project_id, customer_id, title_ar, title_en, contract_type, contract_value, advance_payment_amount, retention_percent, start_date, end_date, sign_date, status, terms_and_conditions, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    trim($data['contract_number']), (int)$data['project_id'], $customerId,
                    trim($data['title_ar']), trim($data['title_en'] ?? ''),
                    $data['contract_type'] ?? 'owner_contract',
                    !empty($data['contract_value']) ? (float)$data['contract_value'] : 0.00,
                    !empty($data['advance_payment_amount']) ? (float)$data['advance_payment_amount'] : 0.00,
                    !empty($data['retention_percent']) ? (float)$data['retention_percent'] : 0.00,
                    $data['start_date'], !empty($data['end_date']) ? $data['end_date'] : null,
                    !empty($data['sign_date']) ? $data['sign_date'] : null,
                    $data['status'] ?? 'active', trim($data['terms_and_conditions'] ?? ''),
                    trim($data['notes'] ?? ''), $_SESSION['user_id'] ?? 1
                ]);
            }

            $_SESSION['flash_msg'] = $isAr ? "تم تسجيل وإنشاء عقد المشروع بنجاح." : "Project contract created successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/projects/contracts/create');
        }

        return new RedirectResponse('/ERP/projects/contracts');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        $contract = null;
        $projects = [];
        $customers = [];
        $autoCode = '';

        try {
            if (!$this->db) throw new Exception("Database connection unavailable.");

            $stmt = $this->db->prepare("SELECT * FROM project_contracts WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $contract = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$contract) throw new Exception("بيانات العقد غير موجودة.");

            $bCond = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";
            $projects = $this->db->query("SELECT id, code, COALESCE(name_ar, name_en) as name_ar FROM projects WHERE company_id = $companyId $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $customers = $this->db->query("SELECT id, name_ar FROM customers WHERE company_id = $companyId ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $autoCode = $contract->contract_number;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/projects/contracts');
        }

        return $this->renderView('/resources/views/projects/contracts/create.php', [
            'contract' => $contract,
            'projects' => $projects,
            'customers' => $customers,
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
            if (!$this->db) throw new Exception("Database connection unavailable.");

            $stmt = $this->db->prepare("
                UPDATE project_contracts 
                SET project_id = ?, customer_id = ?, title_ar = ?, title_en = ?, contract_type = ?, contract_value = ?, advance_payment_amount = ?, retention_percent = ?, start_date = ?, end_date = ?, sign_date = ?, status = ?, terms_and_conditions = ?, notes = ?
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([
                (int)$data['project_id'], !empty($data['customer_id']) ? (int)$data['customer_id'] : null,
                trim($data['title_ar']), trim($data['title_en'] ?? ''),
                $data['contract_type'] ?? 'owner_contract',
                !empty($data['contract_value']) ? (float)$data['contract_value'] : 0.00,
                !empty($data['advance_payment_amount']) ? (float)$data['advance_payment_amount'] : 0.00,
                !empty($data['retention_percent']) ? (float)$data['retention_percent'] : 0.00,
                $data['start_date'], !empty($data['end_date']) ? $data['end_date'] : null,
                !empty($data['sign_date']) ? $data['sign_date'] : null,
                $data['status'] ?? 'active', trim($data['terms_and_conditions'] ?? ''),
                trim($data['notes'] ?? ''), $id, $companyId
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات العقد بنجاح." : "Contract updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/projects/contracts/{$id}/edit");
        }

        return new RedirectResponse('/ERP/projects/contracts');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM project_contracts WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
                $_SESSION['flash_msg'] = "تم حذف العقد بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء الحذف.";
        }

        return new RedirectResponse('/ERP/projects/contracts');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        
        $contract = null;

        if ($this->db) {
            $stmt = $this->db->prepare("
                SELECT c.*, COALESCE(p.name_ar, p.name_en) as project_name, p.code as project_code, p.contract_value as project_total_val, cust.name_ar as customer_name
                FROM project_contracts c
                LEFT JOIN projects p ON c.project_id = p.id
                LEFT JOIN customers cust ON c.customer_id = cust.id
                WHERE c.id = ? AND c.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $contract = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$contract) {
            $_SESSION['flash_err'] = "سجل عقد المشروع غير موجود.";
            return new RedirectResponse('/ERP/projects/contracts');
        }

        $contract->contract_value = convert_amount((float)$contract->contract_value);
        $contract->advance_payment_amount = convert_amount((float)$contract->advance_payment_amount);

        return $this->renderView('/resources/views/projects/contracts/show.php', [
            'contract' => $contract
        ], $response);
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