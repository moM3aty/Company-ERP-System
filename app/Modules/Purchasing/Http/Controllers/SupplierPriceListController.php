<?php
// Path: app/Modules/Purchasing/Http/Controllers/SupplierPriceListController.php

namespace App\Modules\Purchasing\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;

class SupplierPriceListController extends Controller
{
    private string $basePath;
    private PDO $db;

    public function __construct()
    {
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
                $whereClause = "WHERE l.list_number LIKE ? OR l.title LIKE ? OR s.name_ar LIKE ? OR s.name_en LIKE ?";
                $like = "%{$search}%";
                $params = array_fill(0, 4, $like);
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
            $lists = $stmt->fetchAll(PDO::FETCH_OBJ);
            if ($lists === false) $lists = [];

            $today = date('Y-m-d');
            foreach ($lists as $l) {
                if ($l->status == 'active' && $l->valid_to < $today) {
                    $this->db->query("UPDATE supplier_price_lists SET status = 'expired' WHERE id = {$l->id}");
                    $l->status = 'expired';
                }
            }
        } catch (Exception $e) {
            $lists = [];
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/purchasing/price_lists/index.php';
        if ($dbError) echo "<div style='margin:20px; padding:20px; background:#fef2f2; color:#b91c1c; border-radius:8px;'><strong>DB Error:</strong> $dbError</div>";
        if (file_exists($viewPath)) include $viewPath;
        
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $priceList = null; 
        $items = [];
        $suppliers = [];
        $products = [];

        // فصل جلب الموردين عن المنتجات لتجنب انهيار الاثنين معاً
        try {
            $suppliers = $this->db->query("SELECT id, name_ar, name_en, code FROM suppliers WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {}

        try {
            // محاولة جلب المنتجات (إذا كان الجدول موجوداً)
            $products = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {}

        ob_start(); include $this->basePath . '/resources/views/purchasing/price_lists/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            $this->db->beginTransaction();

            $companyId = $_SESSION['company_id'] ?? 1;
            $listNum = !empty($data['list_number']) ? trim($data['list_number']) : 'SPL-' . date('ym') . rand(10, 99);

            $stmt = $this->db->prepare("
                INSERT INTO supplier_price_lists 
                (company_id, supplier_id, list_number, title, valid_from, valid_to, currency, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, $data['supplier_id'], $listNum, $data['title'],
                $data['valid_from'], $data['valid_to'], $data['currency'] ?? 'EGP',
                $data['status'] ?? 'active', $data['notes'] ?? null
            ]);
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
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/price-lists/create');
        }

        return new RedirectResponse('/ERP/purchasing/price-lists');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $suppliers = [];
        $products = [];
        $items = [];

        try {
            $stmt = $this->db->prepare("SELECT * FROM supplier_price_lists WHERE id = ?");
            $stmt->execute([$id]);
            $priceList = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$priceList) throw new Exception("القائمة غير موجودة.");

            $stmtItems = $this->db->prepare("SELECT * FROM supplier_price_list_items WHERE price_list_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/price-lists');
        }

        try { $suppliers = $this->db->query("SELECT id, name_ar, name_en, code FROM suppliers WHERE is_active = 1")->fetchAll(PDO::FETCH_OBJ); } catch (Exception $e) {}
        try { $products = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products")->fetchAll(PDO::FETCH_OBJ); } catch (Exception $e) {}

        ob_start(); include $this->basePath . '/resources/views/purchasing/price_lists/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $data = $request->getParsedBody();
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE supplier_price_lists 
                SET supplier_id=?, title=?, valid_from=?, valid_to=?, currency=?, status=?, notes=?
                WHERE id=?
            ");
            $stmt->execute([
                $data['supplier_id'], $data['title'], $data['valid_from'], $data['valid_to'],
                $data['currency'] ?? 'EGP', $data['status'] ?? 'active', $data['notes'] ?? null, $id
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
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/purchasing/price-lists/{$id}/edit");
        }

        return new RedirectResponse('/ERP/purchasing/price-lists');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        try {
            $stmt = $this->db->prepare("
                SELECT l.*, COALESCE(s.name_ar, s.name_en) as supplier_name, s.code as supplier_code, s.phone as supplier_phone
                FROM supplier_price_lists l
                LEFT JOIN suppliers s ON l.supplier_id = s.id
                WHERE l.id = ?
            ");
            $stmt->execute([$id]);
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

        } catch (Exception $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
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
        try {
            $this->db->prepare("DELETE FROM supplier_price_lists WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف قائمة الأسعار بنجاح.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "خطأ أثناء الحذف.";
        }
        return new RedirectResponse('/ERP/purchasing/price-lists');
    }
}