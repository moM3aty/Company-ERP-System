<?php
// Path: app/Modules/Sales/Http/Controllers/PriceListController.php

namespace App\Modules\Sales\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;

class PriceListController extends Controller
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
        $companyId = current_company_id();
        $branchId  = current_branch();
        $dbError   = null;

        try {
            $sql = "
                SELECT pl.*, 
                       (SELECT COUNT(id) FROM sales_price_list_items WHERE price_list_id = pl.id) as items_count 
                FROM sales_price_lists pl 
                WHERE pl.company_id = ?
            ";
            $params = [$companyId];

            if ($branchId) {
                $sql .= " AND pl.branch_id = ?";
                $params[] = $branchId;
            }
            $sql .= " ORDER BY pl.id DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $priceLists = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (Exception $e) {
            $priceLists = [];
            $dbError = $e->getMessage();
        }

        ob_start(); include $this->basePath . '/resources/views/sales/price_lists/index.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();
        $priceList = null; 
        $lines = [];

        try {
            $sql = "SELECT id, sku, COALESCE(name_ar, name_en) as name, sale_price FROM inv_products WHERE is_active = 1 AND company_id = ?";
            $params = [$companyId];
            if ($branchId) {
                $sql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $params[] = $branchId;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            // تحويل السعر للعملة المختارة حالياً في النظام
            foreach ($products as $p) {
                $p->sale_price = convert_amount($p->sale_price ?? 0);
            }
        } catch (Exception $e) {
            $products = [];
        }

        ob_start(); include $this->basePath . '/resources/views/sales/price_lists/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $isAr = isRtl();
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $this->db->beginTransaction();

            $code = !empty($data['code']) ? trim($data['code']) : 'PL-' . rand(100, 999);
            $currency = !empty($data['currency']) ? trim($data['currency']) : current_currency();

            $stmt = $this->db->prepare("
                INSERT INTO sales_price_lists (company_id, branch_id, code, name_ar, name_en, currency, is_active, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, $branchId, $code, $data['name_ar'], $data['name_en'] ?? $data['name_ar'], 
                $currency, isset($data['is_active']) ? (int)$data['is_active'] : 1, $data['notes'] ?? null
            ]);

            $listId = $this->db->lastInsertId();

            if (!empty($data['items']) && is_array($data['items'])) {
                $itemStmt = $this->db->prepare("INSERT INTO sales_price_list_items (price_list_id, product_id, min_quantity, price, discount_percentage) VALUES (?, ?, ?, ?, ?)");
                foreach ($data['items'] as $item) {
                    if (empty($item['product_id'])) continue;
                    
                    // تحويل السعر المدخل بعملة الجلسة الحالية إلى العملة الأساسية للقاعدة
                    $basePrice = convert_to_base((float)($item['price'] ?? 0));

                    $itemStmt->execute([
                        $listId, $item['product_id'], empty($item['min_quantity']) ? 1 : (float)$item['min_quantity'],
                        $basePrice, (float)($item['discount_percentage'] ?? 0)
                    ]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم إنشاء قائمة الأسعار بنجاح!" : "Price list created successfully!";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/sales/price-lists/create');
        }

        return new RedirectResponse('/ERP/sales/price-lists');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "SELECT * FROM sales_price_lists WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $priceList = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$priceList) throw new Exception("قائمة الأسعار غير موجودة.");

            $lineStmt = $this->db->prepare("SELECT * FROM sales_price_list_items WHERE price_list_id = ?");
            $lineStmt->execute([$id]);
            $lines = $lineStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            // تحويل الأسعار المخزنة لعملة الجلسة الحالية
            foreach ($lines as $line) {
                $line->price = convert_amount($line->price ?? 0);
            }

            $prodSql = "SELECT id, sku, COALESCE(name_ar, name_en) as name, sale_price FROM inv_products WHERE is_active = 1 AND company_id = ?";
            $prodParams = [$companyId];
            if ($branchId) {
                $prodSql .= " AND (branch_id = ? OR branch_id IS NULL)";
                $prodParams[] = $branchId;
            }
            $prodStmt = $this->db->prepare($prodSql);
            $prodStmt->execute($prodParams);
            $products = $prodStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            foreach ($products as $p) {
                $p->sale_price = convert_amount($p->sale_price ?? 0);
            }
        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/price-lists');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/price_lists/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $data = $request->getParsedBody();
        $isAr = isRtl();
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $this->db->beginTransaction();

            $currency = !empty($data['currency']) ? trim($data['currency']) : current_currency();

            $sql = "UPDATE sales_price_lists SET name_ar=?, name_en=?, currency=?, is_active=?, notes=? WHERE id=? AND company_id=?";
            $params = [
                $data['name_ar'], $data['name_en'] ?? $data['name_ar'], $currency, 
                isset($data['is_active']) ? (int)$data['is_active'] : 1, $data['notes'] ?? null, $id, $companyId
            ];

            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $this->db->prepare("DELETE FROM sales_price_list_items WHERE price_list_id = ?")->execute([$id]);

            if (!empty($data['items']) && is_array($data['items'])) {
                $itemStmt = $this->db->prepare("INSERT INTO sales_price_list_items (price_list_id, product_id, min_quantity, price, discount_percentage) VALUES (?, ?, ?, ?, ?)");
                foreach ($data['items'] as $item) {
                    if (empty($item['product_id'])) continue;
                    
                    // تحويل السعر المعدل من عملة الجلسة للعملة الأساسية
                    $basePrice = convert_to_base((float)($item['price'] ?? 0));

                    $itemStmt->execute([
                        $id, $item['product_id'], empty($item['min_quantity']) ? 1 : (float)$item['min_quantity'],
                        $basePrice, (float)($item['discount_percentage'] ?? 0)
                    ]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم تحديث قائمة الأسعار بنجاح." : "Price list updated successfully.";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/sales/price-lists/{$id}/edit");
        }

        return new RedirectResponse('/ERP/sales/price-lists');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "SELECT * FROM sales_price_lists WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $priceList = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$priceList) throw new Exception("قائمة الأسعار غير موجودة.");

            $lineStmt = $this->db->prepare("
                SELECT pli.*, p.sku, COALESCE(p.name_ar, p.name_en) as product_name
                FROM sales_price_list_items pli
                LEFT JOIN inv_products p ON pli.product_id = p.id
                WHERE pli.price_list_id = ?
            ");
            $lineStmt->execute([$id]);
            $lines = $lineStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            // تحويل وعرض القيمة بناءً على عملة النظام المحددة حالياً
            foreach ($lines as $line) {
                $line->price = convert_amount($line->price ?? 0);
            }

        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/sales/price-lists');
        }

        ob_start(); include $this->basePath . '/resources/views/sales/price_lists/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "DELETE FROM sales_price_lists WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $_SESSION['flash_msg'] = "تم حذف قائمة الأسعار بنجاح.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف لوجود عملاء مربوطين بهذه القائمة.";
        }
        return new RedirectResponse('/ERP/sales/price-lists');
    }
}