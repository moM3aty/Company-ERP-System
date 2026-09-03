<?php
// Path: app/Modules/Purchasing/Http/Controllers/LandedCostController.php

namespace App\Modules\Purchasing\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class LandedCostController extends Controller
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
            $whereClause = "WHERE lc.company_id = ?";
            $params = [$companyId];

            if ($branchId > 0) {
                $whereClause .= " AND (lc.branch_id = ? OR lc.branch_id IS NULL OR lc.branch_id = 0)";
                $params[] = $branchId;
            }

            if ($search !== '') {
                $whereClause .= " AND (lc.reference_number LIKE ? OR po.po_number LIKE ? OR s.name_ar LIKE ? OR s.name_en LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like, $like]);
            }

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM landed_costs lc 
                LEFT JOIN purchase_orders po ON lc.po_id = po.id 
                LEFT JOIN suppliers s ON lc.supplier_id = s.id 
                $whereClause
            ");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT lc.*, po.po_number, COALESCE(s.name_ar, s.name_en) as supplier_name,
                       (SELECT COUNT(id) FROM landed_cost_items WHERE landed_cost_id = lc.id) as items_count
                FROM landed_costs lc
                LEFT JOIN purchase_orders po ON lc.po_id = po.id
                LEFT JOIN suppliers s ON lc.supplier_id = s.id
                $whereClause
                ORDER BY lc.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $landedCosts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            // تحويل مبالغ التكاليف الإضافية حسب العملة الحالية
            foreach ($landedCosts as $lc) {
                $lc->total_amount = convert_amount($lc->total_amount);
            }

        } catch (Throwable $e) {
            $landedCosts = [];
            $totalPages = 1;
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/purchasing/landed_costs/index.php';
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

        $landedCost = null; $items = []; $orders = []; $suppliers = [];
        try {
            $orders    = $this->db->query("SELECT id, po_number FROM purchase_orders WHERE company_id = $companyId $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
            $suppliers = $this->db->query("SELECT id, code, COALESCE(name_ar, name_en) as name_ar, name_en FROM suppliers WHERE company_id = $companyId AND is_active = 1 $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); include $this->basePath . '/resources/views/purchasing/landed_costs/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;

        try {
            $this->db->beginTransaction();

            $refNum     = !empty($data['reference_number']) ? trim($data['reference_number']) : 'LC-' . date('ymd') . '-' . rand(100, 999);
            $poId       = !empty($data['po_id']) ? $data['po_id'] : null;
            $supplierId = !empty($data['supplier_id']) ? $data['supplier_id'] : null;

            $totalAmount = 0;
            if (!empty($data['amount']) && is_array($data['amount'])) {
                foreach ($data['amount'] as $amt) {
                    $totalAmount += (float)$amt;
                }
            }

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO landed_costs (company_id, branch_id, reference_number, po_id, supplier_id, cost_date, allocation_method, total_amount, status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $branchId, $refNum, $poId, $supplierId,
                    $data['cost_date'] ?? date('Y-m-d'),
                    $data['allocation_method'] ?? 'by_value',
                    $totalAmount, $data['status'] ?? 'posted', $data['notes'] ?? null
                ]);
            } catch (\PDOException $ex) {
                // احتياطي في حال عدم وجود عمود branch_id
                $stmt = $this->db->prepare("
                    INSERT INTO landed_costs (company_id, reference_number, po_id, supplier_id, cost_date, allocation_method, total_amount, status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $refNum, $poId, $supplierId,
                    $data['cost_date'] ?? date('Y-m-d'),
                    $data['allocation_method'] ?? 'by_value',
                    $totalAmount, $data['status'] ?? 'posted', $data['notes'] ?? null
                ]);
            }
            $landedCostId = $this->db->lastInsertId();

            if (!empty($data['cost_type']) && is_array($data['cost_type'])) {
                $stmtItem = $this->db->prepare("
                    INSERT INTO landed_cost_items (landed_cost_id, cost_type, description, amount)
                    VALUES (?, ?, ?, ?)
                ");
                foreach ($data['cost_type'] as $idx => $type) {
                    if (empty($type)) continue;
                    $amt  = (float)($data['amount'][$idx] ?? 0);
                    $desc = $data['description'][$idx] ?? null;
                    $stmtItem->execute([$landedCostId, $type, $desc, $amt]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم تسجيل وزيادة تكاليف الشحنة بنجاح." : "Landed Cost posted successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/landed-costs/create');
        }

        return new RedirectResponse('/ERP/purchasing/landed-costs');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;
        $bCond     = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";

        try {
            $stmt = $this->db->prepare("SELECT * FROM landed_costs WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $landedCost = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$landedCost) throw new Exception("بيان التكلفة غير موجود.");

            $stmtItems = $this->db->prepare("SELECT * FROM landed_cost_items WHERE landed_cost_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $orders    = $this->db->query("SELECT id, po_number FROM purchase_orders WHERE company_id = $companyId $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
            $suppliers = $this->db->query("SELECT id, code, COALESCE(name_ar, name_en) as name_ar, name_en FROM suppliers WHERE company_id = $companyId AND is_active = 1 $bCond")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/landed-costs');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/landed_costs/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $this->db->beginTransaction();

            $poId       = !empty($data['po_id']) ? $data['po_id'] : null;
            $supplierId = !empty($data['supplier_id']) ? $data['supplier_id'] : null;

            $totalAmount = 0;
            if (!empty($data['amount']) && is_array($data['amount'])) {
                foreach ($data['amount'] as $amt) {
                    $totalAmount += (float)$amt;
                }
            }

            $stmt = $this->db->prepare("
                UPDATE landed_costs 
                SET po_id=?, supplier_id=?, cost_date=?, allocation_method=?, total_amount=?, status=?, notes=?
                WHERE id=? AND company_id=?
            ");
            $stmt->execute([
                $poId, $supplierId, $data['cost_date'], $data['allocation_method'] ?? 'by_value',
                $totalAmount, $data['status'] ?? 'posted', $data['notes'] ?? null, $id, $companyId
            ]);

            $this->db->prepare("DELETE FROM landed_cost_items WHERE landed_cost_id = ?")->execute([$id]);

            if (!empty($data['cost_type']) && is_array($data['cost_type'])) {
                $stmtItem = $this->db->prepare("
                    INSERT INTO landed_cost_items (landed_cost_id, cost_type, description, amount)
                    VALUES (?, ?, ?, ?)
                ");
                foreach ($data['cost_type'] as $idx => $type) {
                    if (empty($type)) continue;
                    $amt  = (float)($data['amount'][$idx] ?? 0);
                    $desc = $data['description'][$idx] ?? null;
                    $stmtItem->execute([$id, $type, $desc, $amt]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم تحديث التكاليف الإضافية بنجاح." : "Landed Cost updated successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/purchasing/landed-costs/{$id}/edit");
        }

        return new RedirectResponse('/ERP/purchasing/landed-costs');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $stmt = $this->db->prepare("
                SELECT lc.*, po.po_number, COALESCE(s.name_ar, s.name_en) as supplier_name, s.code as supplier_code
                FROM landed_costs lc
                LEFT JOIN purchase_orders po ON lc.po_id = po.id
                LEFT JOIN suppliers s ON lc.supplier_id = s.id
                WHERE lc.id = ? AND lc.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $landedCost = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$landedCost) throw new Exception("بيان التكلفة غير موجود.");

            $stmtItems = $this->db->prepare("SELECT * FROM landed_cost_items WHERE landed_cost_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            // تحويل المبالغ
            $landedCost->total_amount = convert_amount($landedCost->total_amount);
            foreach ($items as $item) {
                $item->amount = convert_amount($item->amount);
            }

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/landed-costs');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/landed_costs/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $this->db->prepare("DELETE FROM landed_costs WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف بيان التكلفة الإضافية بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء عملية الحذف.";
        }
        return new RedirectResponse('/ERP/purchasing/landed-costs');
    }
}