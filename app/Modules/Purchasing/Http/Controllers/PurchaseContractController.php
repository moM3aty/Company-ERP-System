<?php
// Path: app/Modules/Purchasing/Http/Controllers/PurchaseContractController.php

namespace App\Modules\Purchasing\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;

class PurchaseContractController extends Controller
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
                $whereClause = "WHERE c.contract_number LIKE ? OR c.title LIKE ? OR s.name_ar LIKE ? OR s.name_en LIKE ?";
                $like = "%{$search}%";
                $params = array_fill(0, 4, $like);
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
            $contracts = $stmt->fetchAll(PDO::FETCH_OBJ);
            if ($contracts === false) $contracts = [];

            $today = date('Y-m-d');
            foreach ($contracts as $c) {
                if ($c->status == 'active' && $c->end_date < $today) {
                    $this->db->query("UPDATE purchase_contracts SET status = 'expired' WHERE id = {$c->id}");
                    $c->status = 'expired';
                }
            }
        } catch (Exception $e) {
            $contracts = [];
            $dbError = $e->getMessage();
        }

        $currentPage = $page;

        ob_start();
        $viewPath = $this->basePath . '/resources/views/purchasing/contracts/index.php';
        if ($dbError) echo "<div style='margin:20px; padding:20px; background:#fef2f2; color:#b91c1c; border-radius:8px;'><strong>DB Error:</strong> $dbError</div>";
        if (file_exists($viewPath)) include $viewPath;
        
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
    public function create(Request $request, Response $response): Response
    {
        $contract = null;
        try {
            $suppliers = $this->db->query("SELECT id, name_ar, name_en, code FROM suppliers WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
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

        try {
            $companyId = $_SESSION['company_id'] ?? 1;
            $contractNum = !empty($data['contract_number']) ? trim($data['contract_number']) : 'PCNT-' . date('ym') . rand(100, 999);

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

            $_SESSION['flash_msg'] = $isAr ? "تم إبرام عقد المشتريات بنجاح." : "Purchase contract created successfully.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse('/ERP/purchasing/contracts/create');
        }

        return new RedirectResponse('/ERP/purchasing/contracts');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM purchase_contracts WHERE id = ?");
            $stmt->execute([$id]);
            $contract = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$contract) throw new Exception("العقد غير موجود.");

            $suppliers = $this->db->query("SELECT id, name_ar, name_en, code FROM suppliers WHERE is_active = 1")->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
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

        try {
            $stmt = $this->db->prepare("
                UPDATE purchase_contracts 
                SET supplier_id=?, title=?, start_date=?, end_date=?, total_value=?, status=?, terms_conditions=?, notes=?
                WHERE id=?
            ");
            $stmt->execute([
                $data['supplier_id'], $data['title'], $data['start_date'], $data['end_date'],
                empty($data['total_value']) ? 0 : (float)$data['total_value'],
                $data['status'] ?? 'active',
                $data['terms_conditions'] ?? null,
                $data['notes'] ?? null,
                $id
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات العقد بنجاح." : "Contract updated successfully.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "خطأ: " . $e->getMessage();
            return new RedirectResponse("/ERP/purchasing/contracts/{$id}/edit");
        }

        return new RedirectResponse('/ERP/purchasing/contracts');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       COALESCE(s.name_ar, s.name_en) as supplier_name, s.code as supplier_code, s.phone as supplier_phone, s.tax_number as supplier_tax, s.address as supplier_address
                FROM purchase_contracts c
                LEFT JOIN suppliers s ON c.supplier_id = s.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $contract = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$contract) throw new Exception("العقد غير موجود.");

        } catch (Exception $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
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
        try {
            $this->db->prepare("DELETE FROM purchase_contracts WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف العقد بنجاح.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف.";
        }
        return new RedirectResponse('/ERP/purchasing/contracts');
    }
}