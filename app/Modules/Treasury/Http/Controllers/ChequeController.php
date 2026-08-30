<?php
// Path: app/Modules/Treasury/Http/Controllers/ChequeController.php

namespace App\Modules\Treasury\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class ChequeController extends Controller
{
    private string $basePath;
    private PDO $db;

    public function __construct()
    {
        ini_set('display_errors', 0);
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    private function resolveId($id = null): ?int
    {
        if (!empty($id) && is_numeric($id)) return (int)$id;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/cheques/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/cheques/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/cheques/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/cheques/(\d+)/status#', $uri, $m)) return $this->updateStatus($request, $response, (int)$m[1]);
        if (preg_match('#/cheques/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/cheques/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $typeFilter = trim($_GET['type'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $fromDate = trim($_GET['start_date'] ?? '');
        $toDate = trim($_GET['end_date'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15; // 15 عنصر في الصفحة
        $offset = ($page - 1) * $limit;

        try {
            $where = ["1=1"];
            $params = [];

            if ($search !== '') {
                $where[] = "(c.cheque_number LIKE ? OR c.bank_name LIKE ? OR c.payee_payer_name LIKE ? OR c.notes LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like, $like]);
            }

            if ($typeFilter !== '') {
                $where[] = "c.type = ?";
                $params[] = $typeFilter;
            }

            if ($statusFilter !== '') {
                $where[] = "c.status = ?";
                $params[] = $statusFilter;
            }

            if ($fromDate !== '') {
                $where[] = "c.due_date >= ?";
                $params[] = $fromDate;
            }

            if ($toDate !== '') {
                $where[] = "c.due_date <= ?";
                $params[] = $toDate;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM treasury_cheques c
                LEFT JOIN accounts a ON c.treasury_account_id = a.id
                $whereSql
            ");
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCount / $limit));

            $stmt = $this->db->prepare("
                SELECT c.*, a.name_ar as account_name, a.code as account_code
                FROM treasury_cheques c
                LEFT JOIN accounts a ON c.treasury_account_id = a.id
                $whereSql
                ORDER BY c.due_date ASC, c.id DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $cheques = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total_cheques,
                    COALESCE(SUM(amount), 0) as total_amount,
                    COALESCE(SUM(IF(status = 'pending', amount, 0)), 0) as pending_amount,
                    COALESCE(SUM(IF(status = 'collected', amount, 0)), 0) as collected_amount,
                    COALESCE(SUM(IF(status = 'bounced', amount, 0)), 0) as bounced_amount
                FROM treasury_cheques
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $cheques = [];
            $stats = (object)['total_cheques'=>0, 'total_amount'=>0, 'pending_amount'=>0, 'collected_amount'=>0, 'bounced_amount'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;
        ob_start(); include $this->basePath . '/resources/views/treasury/cheques/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $cheque = null; 
        $treasuryAccounts = [];

        try {
            $treasuryAccounts = $this->db->query("
                SELECT id, code, name_ar 
                FROM accounts 
                WHERE type = 'asset' AND (code LIKE '111%' OR name_ar LIKE '%خزينة%' OR name_ar LIKE '%بنك%' OR name_ar LIKE '%صندوق%') 
                ORDER BY code ASC
            ")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {}

        ob_start(); include $this->basePath . '/resources/views/treasury/cheques/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (empty($data['cheque_number']) || empty($data['bank_name']) || empty($data['due_date']) || empty($data['amount']) || empty($data['treasury_account_id'])) {
                throw new Exception("يرجى تعبئة كافة الحقول الأساسية للشيك.");
            }

            $stmt = $this->db->prepare("
                INSERT INTO treasury_cheques 
                (cheque_number, type, bank_name, treasury_account_id, payee_payer_name, amount, issue_date, due_date, status, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)
            ");
            $stmt->execute([
                trim($data['cheque_number']),
                $data['type'] ?? 'received',
                trim($data['bank_name']),
                (int)$data['treasury_account_id'],
                trim($data['payee_payer_name'] ?? ''),
                (float)$data['amount'],
                $data['issue_date'] ?? date('Y-m-d'),
                $data['due_date'],
                trim($data['notes'] ?? ''),
                $_SESSION['user_id'] ?? 1
            ]);

            $_SESSION['flash_msg'] = "تم تسجيل الشيك الورقي بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/treasury/cheques/create');
        }

        return new RedirectResponse('/ERP/treasury/cheques');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $stmt = $this->db->prepare("SELECT * FROM treasury_cheques WHERE id = ?");
            $stmt->execute([$id]);
            $cheque = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$cheque) throw new Exception("الشيك غير موجود.");

            $treasuryAccounts = $this->db->query("SELECT id, code, name_ar FROM accounts WHERE type = 'asset' ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/treasury/cheques');
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/cheques/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $stmt = $this->db->prepare("
                UPDATE treasury_cheques 
                SET cheque_number = ?, type = ?, bank_name = ?, treasury_account_id = ?, payee_payer_name = ?, amount = ?, issue_date = ?, due_date = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($data['cheque_number']),
                $data['type'] ?? 'received',
                trim($data['bank_name']),
                (int)$data['treasury_account_id'],
                trim($data['payee_payer_name'] ?? ''),
                (float)$data['amount'],
                $data['issue_date'],
                $data['due_date'],
                trim($data['notes'] ?? ''),
                $id
            ]);

            $_SESSION['flash_msg'] = "تم تحديث بيانات الشيك بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/treasury/cheques/{$id}/edit");
        }

        return new RedirectResponse('/ERP/treasury/cheques');
    }

    public function updateStatus(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $newStatus = $data['status'] ?? 'pending';
            $validStatuses = ['pending', 'collected', 'bounced', 'cancelled'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new Exception("حالة الشيك غير صالحة.");
            }

            $stmt = $this->db->prepare("UPDATE treasury_cheques SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $id]);

            $_SESSION['flash_msg'] = "تم تغيير حالة الشيك بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse("/ERP/treasury/cheques/{$id}");
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $this->db->prepare("DELETE FROM treasury_cheques WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف سجل الشيك بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/treasury/cheques');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);

        $stmt = $this->db->prepare("
            SELECT c.*, a.name_ar as account_name, a.code as account_code
            FROM treasury_cheques c
            LEFT JOIN accounts a ON c.treasury_account_id = a.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $cheque = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$cheque) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "سجل الشيك غير موجود.";
            return new RedirectResponse('/ERP/treasury/cheques');
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/cheques/show.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}