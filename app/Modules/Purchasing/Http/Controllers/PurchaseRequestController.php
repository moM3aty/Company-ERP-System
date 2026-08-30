<?php
namespace App\Modules\Purchasing\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;

class PurchaseRequestController extends Controller
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
            $whereClause = "WHERE pr_number LIKE ? OR department LIKE ? OR requested_by LIKE ?";
            $like = "%{$search}%";
            $params = [$like, $like, $like];
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM purchase_requests $whereClause");
        $countStmt->execute($params);
        $totalItems = $countStmt->fetchColumn();
        $totalPages = max(1, ceil($totalItems / $limit));

        $stmt = $this->db->prepare("
            SELECT *, (SELECT COUNT(id) FROM purchase_request_items WHERE pr_id = purchase_requests.id) as items_count
            FROM purchase_requests $whereClause
            ORDER BY id DESC LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $requests = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

    } catch (Exception $e) {
        $requests = [];
        $dbError = $e->getMessage();
    }

    $currentPage = $page;

    ob_start();
    $viewPath = $this->basePath . '/resources/views/purchasing/requisitions/index.php';
    if ($dbError) echo "<div style='margin:20px; padding:20px; background:#fef2f2; color:#b91c1c; border-radius:8px;'><strong>DB Error:</strong> $dbError</div>";
    if (file_exists($viewPath)) include $viewPath;
    
    $content = ob_get_clean();
    ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
    return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
}

    public function create(Request $request, Response $response): Response
    {
        $pr = null; $items = []; $products = [];
        try {
            $products = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {}

        ob_start(); include $this->basePath . '/resources/views/purchasing/requisitions/create.php'; $content = ob_get_clean();
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
            $prNum = !empty($data['pr_number']) ? trim($data['pr_number']) : 'PR-' . date('ymd') . '-' . rand(10, 99);

            $totalEstimated = 0;
            if (!empty($data['description']) && is_array($data['description'])) {
                foreach ($data['description'] as $idx => $desc) {
                    $qty = (float)($data['quantity'][$idx] ?? 1);
                    $price = (float)($data['estimated_price'][$idx] ?? 0);
                    $totalEstimated += ($qty * $price);
                }
            }

            $stmt = $this->db->prepare("
                INSERT INTO purchase_requests (company_id, pr_number, request_date, required_date, department, requested_by, status, total_estimated_value, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, $prNum, $data['request_date'], $data['required_date'],
                $data['department'] ?? null, $data['requested_by'] ?? null,
                $data['status'] ?? 'pending', $totalEstimated, $data['notes'] ?? null
            ]);
            $prId = $this->db->lastInsertId();

            if (!empty($data['description']) && is_array($data['description'])) {
                $stmtItem = $this->db->prepare("INSERT INTO purchase_request_items (pr_id, product_id, description, quantity, estimated_price) VALUES (?, ?, ?, ?, ?)");
                foreach ($data['description'] as $idx => $desc) {
                    if (empty($desc)) continue;
                    $prodId = !empty($data['product_id'][$idx]) ? $data['product_id'][$idx] : null;
                    $stmtItem->execute([$prId, $prodId, $desc, (float)($data['quantity'][$idx] ?? 1), (float)($data['estimated_price'][$idx] ?? 0)]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم حفظ طلب الشراء بنجاح." : "PR saved successfully.";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/requisitions/create');
        }

        return new RedirectResponse('/ERP/purchasing/requisitions');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM purchase_requests WHERE id = ?");
            $stmt->execute([$id]);
            $requestData = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$requestData) throw new Exception("طلب الشراء غير موجود.");

            $stmtItems = $this->db->prepare("SELECT i.*, p.item_code as product_code FROM purchase_request_items i LEFT JOIN products p ON i.product_id = p.id WHERE i.pr_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

        } catch (Exception $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/requisitions');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/requisitions/show.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM purchase_requests WHERE id = ?");
            $stmt->execute([$id]);
            $pr = $stmt->fetch(PDO::FETCH_OBJ); 
            if (!$pr) throw new Exception("طلب الشراء غير موجود.");

            $stmtItems = $this->db->prepare("SELECT * FROM purchase_request_items WHERE pr_id = ?");
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll(PDO::FETCH_OBJ);

            $products = $this->db->query("SELECT id, item_code as code, COALESCE(name_ar, name_en) as name FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/requisitions');
        }

        ob_start(); include $this->basePath . '/resources/views/purchasing/requisitions/create.php'; $content = ob_get_clean();
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

            $totalEstimated = 0;
            if (!empty($data['description']) && is_array($data['description'])) {
                foreach ($data['description'] as $idx => $desc) {
                    $qty = (float)($data['quantity'][$idx] ?? 1);
                    $price = (float)($data['estimated_price'][$idx] ?? 0);
                    $totalEstimated += ($qty * $price);
                }
            }

            $stmt = $this->db->prepare("UPDATE purchase_requests SET request_date=?, required_date=?, department=?, requested_by=?, status=?, total_estimated_value=?, notes=? WHERE id=?");
            $stmt->execute([$data['request_date'], $data['required_date'], $data['department'] ?? null, $data['requested_by'] ?? null, $data['status'] ?? 'pending', $totalEstimated, $data['notes'] ?? null, $id]);

            $this->db->prepare("DELETE FROM purchase_request_items WHERE pr_id = ?")->execute([$id]);

            if (!empty($data['description']) && is_array($data['description'])) {
                $stmtItem = $this->db->prepare("INSERT INTO purchase_request_items (pr_id, product_id, description, quantity, estimated_price) VALUES (?, ?, ?, ?, ?)");
                foreach ($data['description'] as $idx => $desc) {
                    if (empty($desc)) continue;
                    $prodId = !empty($data['product_id'][$idx]) ? $data['product_id'][$idx] : null;
                    $stmtItem->execute([$id, $prodId, $desc, (float)($data['quantity'][$idx] ?? 1), (float)($data['estimated_price'][$idx] ?? 0)]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم تحديث طلب الشراء بنجاح." : "PR updated successfully.";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/purchasing/requisitions/{$id}/edit");
        }

        return new RedirectResponse('/ERP/purchasing/requisitions');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        try {
            $this->db->prepare("DELETE FROM purchase_requests WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف طلب الشراء.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف.";
        }
        return new RedirectResponse('/ERP/purchasing/requisitions');
    }
}