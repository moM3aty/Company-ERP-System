<?php
// Path: app/Modules/Treasury/Http/Controllers/InternalTransferController.php

namespace App\Modules\Treasury\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class InternalTransferController extends Controller
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
        if (preg_match('#/transfers/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/transfers/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/transfers/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/transfers/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/transfers/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $fromDate = trim($_GET['start_date'] ?? '');
        $toDate = trim($_GET['end_date'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15; // 15 عنصر في الصفحة
        $offset = ($page - 1) * $limit;

        try {
            $where = ["1=1"];
            $params = [];

            if ($search !== '') {
                $where[] = "(t.transfer_number LIKE ? OR t.description LIKE ? OR t.reference_no LIKE ? OR fa.name_ar LIKE ? OR ta.name_ar LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like, $like, $like]);
            }

            if ($fromDate !== '') {
                $where[] = "t.transfer_date >= ?";
                $params[] = $fromDate;
            }

            if ($toDate !== '') {
                $where[] = "t.transfer_date <= ?";
                $params[] = $toDate;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM treasury_transfers t
                LEFT JOIN accounts fa ON t.from_account_id = fa.id
                LEFT JOIN accounts ta ON t.to_account_id = ta.id
                $whereSql
            ");
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCount / $limit));

            $stmt = $this->db->prepare("
                SELECT t.*, 
                       fa.name_ar as from_account_name, fa.code as from_account_code,
                       ta.name_ar as to_account_name, ta.code as to_account_code
                FROM treasury_transfers t
                LEFT JOIN accounts fa ON t.from_account_id = fa.id
                LEFT JOIN accounts ta ON t.to_account_id = ta.id
                $whereSql
                ORDER BY t.transfer_date DESC, t.id DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $transfers = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total_transfers,
                    COALESCE(SUM(amount), 0) as total_amount
                FROM treasury_transfers
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $transfers = [];
            $stats = (object)['total_transfers'=>0, 'total_amount'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;
        ob_start(); include $this->basePath . '/resources/views/treasury/transfers/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $transfer = null; 
        $treasuryAccounts = [];

        try {
            $treasuryAccounts = $this->db->query("
                SELECT id, code, name_ar 
                FROM accounts 
                WHERE type = 'asset' AND (code LIKE '111%' OR name_ar LIKE '%خزينة%' OR name_ar LIKE '%بنك%' OR name_ar LIKE '%صندوق%') 
                ORDER BY code ASC
            ")->fetchAll(PDO::FETCH_OBJ);
            
            $nextSeq = (int)$this->db->query("SELECT COUNT(*) FROM treasury_transfers")->fetchColumn() + 1;
            $autoNumber = 'TRF-' . date('Y') . '-' . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);

        } catch (Throwable $e) {
            $autoNumber = 'TRF-' . date('Y') . '-00001';
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/transfers/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (empty($data['transfer_number']) || empty($data['transfer_date']) || empty($data['from_account_id']) || empty($data['to_account_id']) || empty($data['amount'])) {
                throw new Exception("يرجى تعبئة الحقول الأساسية للتحويل.");
            }

            if ((int)$data['from_account_id'] === (int)$data['to_account_id']) {
                throw new Exception("لا يمكن تحويل الأموال إلى نفس الحساب/الخزينة المصدر.");
            }

            $stmt = $this->db->prepare("
                INSERT INTO treasury_transfers 
                (transfer_number, transfer_date, from_account_id, to_account_id, amount, reference_no, description, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($data['transfer_number']),
                $data['transfer_date'],
                (int)$data['from_account_id'],
                (int)$data['to_account_id'],
                (float)$data['amount'],
                trim($data['reference_no'] ?? ''),
                trim($data['description'] ?? ''),
                $_SESSION['user_id'] ?? 1
            ]);

            $_SESSION['flash_msg'] = "تم إنشاء أمر التحويل الداخلي بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/treasury/transfers/create');
        }

        return new RedirectResponse('/ERP/treasury/transfers');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $stmt = $this->db->prepare("SELECT * FROM treasury_transfers WHERE id = ?");
            $stmt->execute([$id]);
            $transfer = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$transfer) throw new Exception("أمر التحويل الداخلي غير موجود.");

            $treasuryAccounts = $this->db->query("SELECT id, code, name_ar FROM accounts WHERE type = 'asset' ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);
            $autoNumber = $transfer->transfer_number;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/treasury/transfers');
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/transfers/create.php';
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
            if ((int)$data['from_account_id'] === (int)$data['to_account_id']) {
                throw new Exception("لا يمكن تحويل الأموال إلى نفس الحساب/الخزينة المصدر.");
            }

            $stmt = $this->db->prepare("
                UPDATE treasury_transfers 
                SET transfer_date = ?, from_account_id = ?, to_account_id = ?, amount = ?, reference_no = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['transfer_date'],
                (int)$data['from_account_id'],
                (int)$data['to_account_id'],
                (float)$data['amount'],
                trim($data['reference_no'] ?? ''),
                trim($data['description'] ?? ''),
                $id
            ]);

            $_SESSION['flash_msg'] = "تم تحديث بيانات أمر التحويل بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/treasury/transfers/{$id}/edit");
        }

        return new RedirectResponse('/ERP/treasury/transfers');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $this->db->prepare("DELETE FROM treasury_transfers WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف أمر التحويل الداخلي بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/treasury/transfers');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);

        $stmt = $this->db->prepare("
            SELECT t.*, 
                   fa.name_ar as from_account_name, fa.code as from_account_code,
                   ta.name_ar as to_account_name, ta.code as to_account_code
            FROM treasury_transfers t
            LEFT JOIN accounts fa ON t.from_account_id = fa.id
            LEFT JOIN accounts ta ON t.to_account_id = ta.id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        $transfer = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$transfer) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "أمر التحويل الداخلي غير موجود.";
            return new RedirectResponse('/ERP/treasury/transfers');
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/transfers/show.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}