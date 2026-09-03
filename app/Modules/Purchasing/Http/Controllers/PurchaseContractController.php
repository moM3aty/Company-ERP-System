<?php
// Path: app/Modules/Purchasing/Http/Controllers/PurchaseContractController.php

namespace App\Modules\Purchasing\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class PurchaseContractController extends Controller
{
    private string $basePath;
    private PDO $db;

    public function __construct()
    {
        ini_set('display_errors', 0);
        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    public function index(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        $dbError = null;
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $whereClause = "WHERE c.company_id = ?";
            $params = [$companyId];

            if ($branchId > 0) {
                $whereClause .= " AND (c.branch_id = ? OR c.branch_id IS NULL OR c.branch_id = 0)";
                $params[] = $branchId;
            }

            if ($search !== '') {
                $whereClause .= " AND (c.contract_number LIKE ? OR c.title LIKE ? OR s.name_ar LIKE ? OR s.name_en LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like, $like]);
            }

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM purchase_contracts c LEFT JOIN suppliers s ON c.supplier_id = s.id $whereClause");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT c.*, COALESCE(s.name_ar, s.name_en) as supplier_name, s.code as supplier_code
                FROM purchase_contracts c
                LEFT JOIN suppliers s ON c.supplier_id = s.id
                $whereClause
                ORDER BY c.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $contracts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $today = date('Y-m-d');
            foreach ($contracts as $c) {
                // تحديث التلقائي للعقود المنتهية
                if ($c->status == 'active' && $c->end_date < $today) {
                    $this->db->query("UPDATE purchase_contracts SET status = 'expired' WHERE id = {$c->id}");
                    $c->status = 'expired';
                }
                // تحويل القيم المالية بحسب العملة الحالية
                $c->total_value = convert_amount($c->total_value);
            }
        } catch (Throwable $e) {
            $contracts = [];
            $totalPages = 1;
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/purchasing/contracts/index.php';
        if (file_exists($viewPath)) include $viewPath;
        
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;
        $bCond     = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";

        $contract = null;
        try {
            $suppliers = $this->db->query("SELECT id, COALESCE(name_ar, name_en) as name_ar, name_en, code FROM suppliers WHERE company_id = $companyId AND is_active = 1 $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            $suppliers = [];
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/contracts/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        try {
            $contractNum = !empty($data['contract_number']) ? trim($data['contract_number']) : 'PCNT-' . date('ym') . rand(100, 999);

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO purchase_contracts 
                    (company_id, branch_id, supplier_id, contract_number, title, start_date, end_date, total_value, status, terms_conditions, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $branchId, $data['supplier_id'], $contractNum, $data['title'],
                    $data['start_date'], $data['end_date'],
                    empty($data['total_value']) ? 0 : (float)$data['total_value'],
                    $data['status'] ?? 'active',
                    $data['terms_conditions'] ?? null,
                    $data['notes'] ?? null
                ]);
            } catch (\PDOException $ex) {
                // احتياطي في حال عدم وجود العمود branch_id في قاعدة البيانات
                $stmt = $this->db->prepare("
                    INSERT INTO purchase_contracts 
                    (company_id, supplier_id, contract_number, title, start_date, end_date, total_value, status, terms_conditions, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $data['supplier_id'], $contractNum, $data['title'],
                    $data['start_date'], $data['end_date'],
                    empty($data['total_value']) ? 0 : (float)$data['total_value'],
                    $data['status'] ?? 'active',
                    $data['terms_conditions'] ?? null,
                    $data['notes'] ?? null
                ]);
            }

            $_SESSION['flash_msg'] = $isAr ? "تم إبرام عقد المشتريات بنجاح." : "Purchase contract created successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/contracts/create');
        }

        return new RedirectResponse('/ERP/purchasing/contracts');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;
        $bCond     = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";

        try {
            $stmt = $this->db->prepare("SELECT * FROM purchase_contracts WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $contract = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$contract) throw new Exception("العقد غير موجود.");

            $suppliers = $this->db->query("SELECT id, COALESCE(name_ar, name_en) as name_ar, name_en, code FROM suppliers WHERE company_id = $companyId AND is_active = 1 $bCond")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/contracts');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/contracts/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $data = $request->getParsedBody();
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $stmt = $this->db->prepare("
                UPDATE purchase_contracts 
                SET supplier_id=?, title=?, start_date=?, end_date=?, total_value=?, status=?, terms_conditions=?, notes=?
                WHERE id=? AND company_id=?
            ");
            $stmt->execute([
                $data['supplier_id'], $data['title'], $data['start_date'], $data['end_date'],
                empty($data['total_value']) ? 0 : (float)$data['total_value'],
                $data['status'] ?? 'active',
                $data['terms_conditions'] ?? null,
                $data['notes'] ?? null,
                $id, $companyId
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات العقد بنجاح." : "Contract updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/purchasing/contracts/{$id}/edit");
        }

        return new RedirectResponse('/ERP/purchasing/contracts');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       COALESCE(s.name_ar, s.name_en) as supplier_name, s.code as supplier_code, s.phone as supplier_phone, s.tax_number as supplier_tax, s.address as supplier_address
                FROM purchase_contracts c
                LEFT JOIN suppliers s ON c.supplier_id = s.id
                WHERE c.id = ? AND c.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $contract = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$contract) throw new Exception("العقد غير موجود.");

            // تحويل القيمة التقديرية حسب العملة الحالية
            $contract->total_value = convert_amount($contract->total_value);

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/contracts');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/contracts/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $this->db->prepare("DELETE FROM purchase_contracts WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف العقد بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف لارتباطه بعمليات أخرى.";
        }
        return new RedirectResponse('/ERP/purchasing/contracts');
    }
}