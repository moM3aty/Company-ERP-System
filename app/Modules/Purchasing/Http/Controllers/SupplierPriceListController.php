<?php
// Path: app/Modules/Purchasing/Http/Controllers/SupplierPriceListController.php

namespace App\Modules\Purchasing\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class SupplierPriceListController extends Controller
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
            $whereClause = "WHERE l.company_id = ?";
            $params = [$companyId];

            if ($branchId > 0) {
                $whereClause .= " AND (l.branch_id = ? OR l.branch_id IS NULL OR l.branch_id = 0)";
                $params[] = $branchId;
            }

            if ($search !== '') {
                $whereClause .= " AND (l.list_number LIKE ? OR l.title LIKE ? OR s.name_ar LIKE ? OR s.name_en LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like, $like]);
            }

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM supplier_price_lists l LEFT JOIN suppliers s ON l.supplier_id = s.id $whereClause");
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalItems / $limit));

            $stmt = $this->db->prepare("
                SELECT l.*, COALESCE(s.name_ar, s.name_en) as supplier_name,
                       (SELECT COUNT(id) FROM supplier_price_list_items WHERE price_list_id = l.id) as items_count
                FROM supplier_price_lists l
                LEFT JOIN suppliers s ON l.supplier_id = s.id
                $whereClause
                ORDER BY l.id DESC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $lists = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            // تحديث تلقائي للحالة إذا انتهت تاريخ الصلاحية
            $today = date('Y-m-d');
            foreach ($lists as $l) {
                if ($l->status == 'active' && $l->valid_to < $today) {
                    $this->db->query("UPDATE supplier_price_lists SET status = 'expired' WHERE id = {$l->id}");
                    $l->status = 'expired';
                }
            }
        } catch (Throwable $e) {
            $lists = [];
            $totalPages = 1;
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/purchasing/price_lists/index.php';
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

        $priceList = null; 
        $items = [];
        $suppliers = [];
        $products = [];

        try {
            $suppliers = $this->db->query("SELECT id, COALESCE(name_ar, name_en) as name_ar, name_en, code FROM suppliers WHERE company_id = $companyId AND is_active = 1 $bCond ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products WHERE company_id = $companyId ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {}

        ob_start(); include $this->basePath . '/resources/views/purchasing/price_lists/create.php'; $content = ob_get_clean();
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

            $listNum = !empty($data['list_number']) ? trim($data['list_number']) : 'SPL-' . date('ym') . rand(10, 99);

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO supplier_price_lists 
                    (company_id, branch_id, supplier_id, list_number, title, valid_from, valid_to, currency, status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $branchId, $data['supplier_id'], $listNum, $data['title'],
                    $data['valid_from'], $data['valid_to'], $data['currency'] ?? current_currency(),
                    $data['status'] ?? 'active', $data['notes'] ?? null
                ]);
            } catch (\PDOException $ex) {
                // احتياطي في حال عدم وجود العمود branch_id داخل الهيكل القديم
                $stmt = $this->db->prepare("
                    INSERT INTO supplier_price_lists 
                    (company_id, supplier_id, list_number, title, valid_from, valid_to, currency, status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $companyId, $data['supplier_id'], $listNum, $data['title'],
                    $data['valid_from'], $data['valid_to'], $data['currency'] ?? current_currency(),
                    $data['status'] ?? 'active', $data['notes'] ?? null
                ]);
            }
            $listId = $this->db->lastInsertId();

            if (!empty($data['product_id']) && is_array($data['product_id'])) {
                $stmtItem = $this->db->prepare("INSERT INTO supplier_price_list_items (price_list_id, product_id, unit_price, min_order_qty, discount_percent) VALUES (?, ?, ?, ?, ?)");
                foreach ($data['product_id'] as $idx => $prodId) {
                    if (empty($prodId)) continue;
                    $stmtItem->execute([
                        $listId, $prodId,
                        (float)($data['unit_price'][$idx] ?? 0),
                        (float)($data['min_order_qty'][$idx] ?? 1),
                        (float)($data['discount_percent'][$idx] ?? 0)
                    ]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم حفظ قائمة الأسعار بنجاح." : "Price list saved successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/price-lists/create');
        }

        return new RedirectResponse('/ERP/purchasing/price-lists');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;
        $branchId  = $_SESSION['branch_id'] ?? 0;
        $bCond     = $branchId > 0 ? " AND (branch_id = $branchId OR branch_id IS NULL OR branch_id = 0)" : "";

        $suppliers = []; $products = []; $items = [];

        try {
            $stmt = $this->db->prepare("SELECT * FROM supplier_price_lists WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $priceList = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$priceList) throw new Exception("القائمة غير موجودة.");

            $stmtItems = $this->db->prepare("SELECT * FROM supplier_price_list_items WHERE price_list_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $suppliers = $this->db->query("SELECT id, COALESCE(name_ar, name_en) as name_ar, name_en, code FROM suppliers WHERE company_id = $companyId AND is_active = 1 $bCond")->fetchAll(PDO::FETCH_OBJ);
            $products = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products WHERE company_id = $companyId")->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/price-lists');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/price_lists/create.php'; $content = ob_get_clean();
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

            $stmt = $this->db->prepare("
                UPDATE supplier_price_lists 
                SET supplier_id=?, title=?, valid_from=?, valid_to=?, currency=?, status=?, notes=?
                WHERE id=? AND company_id=?
            ");
            $stmt->execute([
                $data['supplier_id'], $data['title'], $data['valid_from'], $data['valid_to'],
                $data['currency'] ?? current_currency(), $data['status'] ?? 'active', $data['notes'] ?? null, $id, $companyId
            ]);

            $this->db->prepare("DELETE FROM supplier_price_list_items WHERE price_list_id = ?")->execute([$id]);

            if (!empty($data['product_id']) && is_array($data['product_id'])) {
                $stmtItem = $this->db->prepare("INSERT INTO supplier_price_list_items (price_list_id, product_id, unit_price, min_order_qty, discount_percent) VALUES (?, ?, ?, ?, ?)");
                foreach ($data['product_id'] as $idx => $prodId) {
                    if (empty($prodId)) continue;
                    $stmtItem->execute([
                        $id, $prodId,
                        (float)($data['unit_price'][$idx] ?? 0),
                        (float)($data['min_order_qty'][$idx] ?? 1),
                        (float)($data['discount_percent'][$idx] ?? 0)
                    ]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم تحديث قائمة الأسعار بنجاح." : "Price list updated successfully.";
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/purchasing/price-lists/{$id}/edit");
        }

        return new RedirectResponse('/ERP/purchasing/price-lists');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $stmt = $this->db->prepare("
                SELECT l.*, COALESCE(s.name_ar, s.name_en) as supplier_name, s.code as supplier_code, s.phone as supplier_phone
                FROM supplier_price_lists l
                LEFT JOIN suppliers s ON l.supplier_id = s.id
                WHERE l.id = ? AND l.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $priceList = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$priceList) throw new Exception("القائمة غير موجودة.");

            $stmtItems = $this->db->prepare("
                SELECT i.*, COALESCE(p.name_ar, p.name_en) as product_name, p.item_code as product_code
                FROM supplier_price_list_items i
                LEFT JOIN products p ON i.product_id = p.id
                WHERE i.price_list_id = ?
            ");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            // تحويل أسعار الأصناف حسب العملة النشطة
            foreach ($items as $item) {
                $item->unit_price = convert_amount($item->unit_price);
            }

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/price-lists');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/price_lists/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $_SESSION['company_id'] ?? 1;

        try {
            $this->db->prepare("DELETE FROM supplier_price_lists WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف قائمة الأسعار بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء الحذف.";
        }
        return new RedirectResponse('/ERP/purchasing/price-lists');
    }
}