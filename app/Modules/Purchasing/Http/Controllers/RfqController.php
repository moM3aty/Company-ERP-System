<?php
// Path: app/Modules/Purchasing/Http/Controllers/RfqController.php

namespace App\Modules\Purchasing\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class RfqController extends Controller
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
            $whereClause = "WHERE r.company_id = ?";
            $params = [$companyId];

            if ($branchId > 0) {
                $whereClause .= " AND (r.branch_id = ? OR r.branch_id IS NULL OR r.branch_id = 0)";
                $params[] = $branchId;
            }

            if ($search !== '') {
                $whereClause .= " AND (r.rfq_number LIKE ? OR r.title LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like]);
            }

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM rfqs r $whereClause");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT r.*, 
                       (SELECT COUNT(id) FROM rfq_items WHERE rfq_id = r.id) as items_count,
                       (SELECT COUNT(id) FROM rfq_suppliers WHERE rfq_id = r.id) as suppliers_count
                FROM rfqs r
                $whereClause
                ORDER BY r.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $rfqs = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $today = date('Y-m-d');
            foreach ($rfqs as $r) {
                if ($r->status === 'published' && $r->deadline_date < $today) {
                    $this->db->query("UPDATE rfqs SET status = 'closed' WHERE id = {$r->id}");
                    $r->status = 'closed';
                }
            }
        } catch (Throwable $e) {
            $rfqs = [];
            $totalPages = 1;
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/purchasing/rfq/index.php';
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

        $rfq = null; $items = []; $selectedSuppliers = []; $suppliers = []; $products = [];
        try {
            $suppliers = $this->db->query("SELECT id, COALESCE(name_ar, name_en) as name_ar, name_en, code FROM suppliers WHERE company_id = $companyId AND is_active = 1 $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
            $products  = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products WHERE company_id = $companyId ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); include $this->basePath . '/resources/views/purchasing/rfq/create.php'; $content = ob_get_clean();
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
            $this->db->beginTransaction();

            $rfqNum = !empty($data['rfq_number']) ? trim($data['rfq_number']) : 'RFQ-' . date('ymd') . '-' . rand(10, 99);

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO rfqs (company_id, branch_id, rfq_number, title, request_date, deadline_date, status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $branchId, $rfqNum, $data['title'], $data['request_date'], $data['deadline_date'],
                    $data['status'] ?? 'draft', $data['notes'] ?? null
                ]);
            } catch (\PDOException $ex) {
                // احتياطي في حال عدم وجود عمود branch_id
                $stmt = $this->db->prepare("
                    INSERT INTO rfqs (company_id, rfq_number, title, request_date, deadline_date, status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $rfqNum, $data['title'], $data['request_date'], $data['deadline_date'],
                    $data['status'] ?? 'draft', $data['notes'] ?? null
                ]);
            }
            $rfqId = $this->db->lastInsertId();

            if (!empty($data['suppliers']) && is_array($data['suppliers'])) {
                $stmtSup = $this->db->prepare("INSERT INTO rfq_suppliers (rfq_id, supplier_id) VALUES (?, ?)");
                foreach ($data['suppliers'] as $supId) {
                    $stmtSup->execute([$rfqId, $supId]);
                }
            }

            if (!empty($data['description']) && is_array($data['description'])) {
                $stmtItem = $this->db->prepare("INSERT INTO rfq_items (rfq_id, product_id, description, quantity) VALUES (?, ?, ?, ?)");
                foreach ($data['description'] as $idx => $desc) {
                    if (empty($desc)) continue;
                    $prodId = !empty($data['product_id'][$idx]) ? $data['product_id'][$idx] : null;
                    $stmtItem->execute([
                        $rfqId, $prodId, $desc, (float)($data['quantity'][$idx] ?? 1)
                    ]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم حفظ طلب عروض الأسعار بنجاح." : "RFQ saved successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/rfq/create');
        }

        return new RedirectResponse('/ERP/purchasing/rfq');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;
        $bCond     = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";

        try {
            $stmt = $this->db->prepare("SELECT * FROM rfqs WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $rfq = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$rfq) throw new Exception("طلب التسعير غير موجود.");

            $stmtItems = $this->db->prepare("SELECT * FROM rfq_items WHERE rfq_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $stmtSup = $this->db->prepare("SELECT supplier_id FROM rfq_suppliers WHERE rfq_id = ?");
            $stmtSup->execute([$id]);
            $selectedSuppliers = $stmtSup->fetchAll(PDO::FETCH_COLUMN);

            $suppliers = $this->db->query("SELECT id, COALESCE(name_ar, name_en) as name_ar, name_en, code FROM suppliers WHERE company_id = $companyId AND is_active = 1 $bCond")->fetchAll(PDO::FETCH_OBJ);
            $products  = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products WHERE company_id = $companyId")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/rfq');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/rfq/create.php'; $content = ob_get_clean();
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
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE rfqs SET title=?, request_date=?, deadline_date=?, status=?, notes=? WHERE id=? AND company_id=?");
            $stmt->execute([$data['title'], $data['request_date'], $data['deadline_date'], $data['status'] ?? 'draft', $data['notes'] ?? null, $id, $companyId]);

            $this->db->prepare("DELETE FROM rfq_suppliers WHERE rfq_id = ?")->execute([$id]);
            if (!empty($data['suppliers']) && is_array($data['suppliers'])) {
                $stmtSup = $this->db->prepare("INSERT INTO rfq_suppliers (rfq_id, supplier_id) VALUES (?, ?)");
                foreach ($data['suppliers'] as $supId) { $stmtSup->execute([$id, $supId]); }
            }

            $this->db->prepare("DELETE FROM rfq_items WHERE rfq_id = ?")->execute([$id]);
            if (!empty($data['description']) && is_array($data['description'])) {
                $stmtItem = $this->db->prepare("INSERT INTO rfq_items (rfq_id, product_id, description, quantity) VALUES (?, ?, ?, ?)");
                foreach ($data['description'] as $idx => $desc) {
                    if (empty($desc)) continue;
                    $prodId = !empty($data['product_id'][$idx]) ? $data['product_id'][$idx] : null;
                    $stmtItem->execute([$id, $prodId, $desc, (float)($data['quantity'][$idx] ?? 1)]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم تحديث طلب التسعير بنجاح." : "RFQ updated successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/purchasing/rfq/{$id}/edit");
        }

        return new RedirectResponse('/ERP/purchasing/rfq');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $stmt = $this->db->prepare("SELECT * FROM rfqs WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $rfq = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$rfq) throw new Exception("طلب التسعير غير موجود.");

            $stmtItems = $this->db->prepare("SELECT i.*, p.item_code as product_code FROM rfq_items i LEFT JOIN products p ON i.product_id = p.id WHERE i.rfq_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $stmtSup = $this->db->prepare("SELECT s.code, COALESCE(s.name_ar, s.name_en) as name, s.email, s.phone FROM rfq_suppliers rs JOIN suppliers s ON rs.supplier_id = s.id WHERE rs.rfq_id = ?");
            $stmtSup->execute([$id]);
            $invitedSuppliers = $stmtSup->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/rfq');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/rfq/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $this->db->prepare("DELETE FROM rfqs WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف طلب التسعير بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء عملية الحذف.";
        }
        return new RedirectResponse('/ERP/purchasing/rfq');
    }
}