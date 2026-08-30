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
                $whereClause = "WHERE lc.reference_number LIKE ? OR po.po_number LIKE ? OR s.name_ar LIKE ?";
                $like = "%{$search}%";
                $params = [$like, $like, $like];
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
                SELECT lc.*, po.po_number, s.name_ar as supplier_name,
                       (SELECT COUNT(id) FROM landed_cost_items WHERE landed_cost_id = lc.id) as items_count
                FROM landed_costs lc
                LEFT JOIN purchase_orders po ON lc.po_id = po.id
                LEFT JOIN suppliers s ON lc.supplier_id = s.id
                $whereClause
                ORDER BY lc.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $landedCosts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            $landedCosts = [];
            $totalPages = 1;
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/purchasing/landed_costs/index.php';
        if ($dbError) echo "<div style='margin:20px; padding:20px; background:#fef2f2; color:#b91c1c; border-radius:8px;'><strong>DB Error:</strong> $dbError</div>";
        if (file_exists($viewPath)) include $viewPath;
        else echo "<div style='margin:20px; padding:20px; background:#fee2e2; color:#dc2626;'>ملف الواجهة مفقود: $viewPath</div>";
        
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $landedCost = null; $items = []; $orders = []; $suppliers = [];
        try {
            $orders = $this->db->query("SELECT id, po_number FROM purchase_orders ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
            $suppliers = $this->db->query("SELECT id, code, name_ar, name_en FROM suppliers WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/purchasing/landed_costs/create.php';
        if (file_exists($viewPath)) include $viewPath;
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            $this->db->beginTransaction();

            $companyId = $_SESSION['company_id'] ?? 1;
            $refNum = !empty($data['reference_number']) ? trim($data['reference_number']) : 'LC-' . date('ymd') . '-' . rand(100, 999);
            $poId = !empty($data['po_id']) ? $data['po_id'] : null;
            $supplierId = !empty($data['supplier_id']) ? $data['supplier_id'] : null;

            $totalAmount = 0;
            if (!empty($data['amount']) && is_array($data['amount'])) {
                foreach ($data['amount'] as $amt) {
                    $totalAmount += (float)$amt;
                }
            }

            $stmt = $this->db->prepare("
                INSERT INTO landed_costs (company_id, reference_number, po_id, supplier_id, cost_date, allocation_method, total_amount, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, $refNum, $poId, $supplierId,
                $data['cost_date'] ?? date('Y-m-d'),
                $data['allocation_method'] ?? 'by_value',
                $totalAmount, $data['status'] ?? 'draft', $data['notes'] ?? null
            ]);
            $landedCostId = $this->db->lastInsertId();

            if (!empty($data['cost_type']) && is_array($data['cost_type'])) {
                $stmtItem = $this->db->prepare("
                    INSERT INTO landed_cost_items (landed_cost_id, cost_type, description, amount)
                    VALUES (?, ?, ?, ?)
                ");
                foreach ($data['cost_type'] as $idx => $type) {
                    if (empty($type)) continue;
                    $amt = (float)($data['amount'][$idx] ?? 0);
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
        try {
            $stmt = $this->db->prepare("SELECT * FROM landed_costs WHERE id = ?");
            $stmt->execute([$id]);
            $landedCost = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$landedCost) throw new Exception("بيان التكلفة غير موجود.");

            $stmtItems = $this->db->prepare("SELECT * FROM landed_cost_items WHERE landed_cost_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $orders = $this->db->query("SELECT id, po_number FROM purchase_orders ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
            $suppliers = $this->db->query("SELECT id, code, name_ar, name_en FROM suppliers WHERE is_active = 1")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/landed-costs');
        }

        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/purchasing/landed_costs/create.php';
        if (file_exists($viewPath)) include $viewPath;
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            $this->db->beginTransaction();

            $poId = !empty($data['po_id']) ? $data['po_id'] : null;
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
                WHERE id=?
            ");
            $stmt->execute([
                $poId, $supplierId, $data['cost_date'], $data['allocation_method'] ?? 'by_value',
                $totalAmount, $data['status'] ?? 'draft', $data['notes'] ?? null, $id
            ]);

            $this->db->prepare("DELETE FROM landed_cost_items WHERE landed_cost_id = ?")->execute([$id]);

            if (!empty($data['cost_type']) && is_array($data['cost_type'])) {
                $stmtItem = $this->db->prepare("
                    INSERT INTO landed_cost_items (landed_cost_id, cost_type, description, amount)
                    VALUES (?, ?, ?, ?)
                ");
                foreach ($data['cost_type'] as $idx => $type) {
                    if (empty($type)) continue;
                    $amt = (float)($data['amount'][$idx] ?? 0);
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
        try {
            $stmt = $this->db->prepare("
                SELECT lc.*, po.po_number, s.name_ar as supplier_name, s.code as supplier_code
                FROM landed_costs lc
                LEFT JOIN purchase_orders po ON lc.po_id = po.id
                LEFT JOIN suppliers s ON lc.supplier_id = s.id
                WHERE lc.id = ?
            ");
            $stmt->execute([$id]);
            $landedCost = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$landedCost) throw new Exception("بيان التكلفة غير موجود.");

            $stmtItems = $this->db->prepare("SELECT * FROM landed_cost_items WHERE landed_cost_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/landed-costs');
        }

        ob_start(); 
        $viewPath = $this->basePath . '/resources/views/purchasing/landed_costs/show.php';
        if (file_exists($viewPath)) include $viewPath;
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        try {
            $this->db->prepare("DELETE FROM landed_costs WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف بيان التكلفة الإضافية بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء عملية الحذف.";
        }
        return new RedirectResponse('/ERP/purchasing/landed-costs');
    }
}