<?php
// Path: app/Modules/Treasury/Http/Controllers/TreasuryAccountController.php

namespace App\Modules\Treasury\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class TreasuryAccountController extends Controller
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
        if (preg_match('#/treasury/accounts/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/accounts/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/accounts/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/accounts/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/accounts/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $typeFilter = trim($_GET['type'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15; // 15 عنصر في الصفحة
        $offset = ($page - 1) * $limit;

        try {
            $where = ["a.type = 'asset' AND (a.code LIKE '111%' OR a.code LIKE '112%' OR a.name_ar LIKE '%خزينة%' OR a.name_ar LIKE '%بنك%' OR a.name_ar LIKE '%صندوق%')"];
            $params = [];

            if ($search !== '') {
                $where[] = "(a.code LIKE ? OR a.name_ar LIKE ? OR a.name_en LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like]);
            }

            if ($statusFilter !== '') {
                $where[] = "a.is_active = ?";
                $params[] = ($statusFilter === 'active') ? 1 : 0;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM accounts a $whereSql");
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCount / $limit));

            $stmt = $this->db->prepare("
                SELECT a.*
                FROM accounts a
                $whereSql
                ORDER BY a.code ASC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $accounts = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total_accounts,
                    COALESCE(SUM(current_balance), 0) as total_balance,
                    COALESCE(SUM(IF(name_ar LIKE '%بنك%' OR code LIKE '1112%', current_balance, 0)), 0) as bank_balance,
                    COALESCE(SUM(IF(name_ar LIKE '%خزينة%' OR name_ar LIKE '%صندوق%' OR code LIKE '1111%', current_balance, 0)), 0) as cash_balance
                FROM accounts
                WHERE type = 'asset' AND (code LIKE '111%' OR name_ar LIKE '%خزينة%' OR name_ar LIKE '%بنك%' OR name_ar LIKE '%صندوق%')
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $accounts = [];
            $stats = (object)['total_accounts'=>0, 'total_balance'=>0, 'bank_balance'=>0, 'cash_balance'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;
        ob_start(); include $this->basePath . '/resources/views/treasury/accounts/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $account = null;
        try {
            $nextSeq = (int)$this->db->query("SELECT COUNT(*) FROM accounts WHERE code LIKE '111%'")->fetchColumn() + 1;
            $autoCode = '111' . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            $autoCode = '111001';
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/accounts/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (empty($data['code']) || empty($data['name_ar'])) {
                throw new Exception("يرجى تعبئة الحقول الإلزامية.");
            }

            $stmt = $this->db->prepare("
                INSERT INTO accounts (code, name_ar, name_en, type, current_balance, is_active, is_parent)
                VALUES (?, ?, ?, 'asset', ?, ?, 0)
            ");
            $stmt->execute([
                trim($data['code']),
                trim($data['name_ar']),
                trim($data['name_en'] ?? ''),
                !empty($data['current_balance']) ? (float)$data['current_balance'] : 0.00,
                isset($data['is_active']) ? 1 : 0
            ]);

            $_SESSION['flash_msg'] = "تم إنشاء الحساب / الخزينة بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/treasury/accounts/create');
        }

        return new RedirectResponse('/ERP/treasury/accounts');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $stmt = $this->db->prepare("SELECT * FROM accounts WHERE id = ?");
            $stmt->execute([$id]);
            $account = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$account) throw new Exception("الحساب غير موجود.");
            $autoCode = $account->code;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/treasury/accounts');
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/accounts/create.php';
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
                UPDATE accounts 
                SET code = ?, name_ar = ?, name_en = ?, current_balance = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($data['code']),
                trim($data['name_ar']),
                trim($data['name_en'] ?? ''),
                !empty($data['current_balance']) ? (float)$data['current_balance'] : 0.00,
                isset($data['is_active']) ? 1 : 0,
                $id
            ]);

            $_SESSION['flash_msg'] = "تم تحديث بيانات الحساب بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/treasury/accounts/{$id}/edit");
        }

        return new RedirectResponse('/ERP/treasury/accounts');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $trxCheck = $this->db->prepare("SELECT COUNT(*) FROM journal_entry_items WHERE account_id = ?");
            $trxCheck->execute([$id]);
            if ($trxCheck->fetchColumn() > 0) {
                throw new Exception("لا يمكن حذف حساب مرتبطة به حركات مالية في القيد اليومي.");
            }

            $this->db->prepare("DELETE FROM accounts WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف الحساب بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/treasury/accounts');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);

        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE id = ?");
        $stmt->execute([$id]);
        $account = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$account) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "الحساب غير موجود.";
            return new RedirectResponse('/ERP/treasury/accounts');
        }

        try {
            $txStmt = $this->db->prepare("
                SELECT ji.*, je.entry_number, je.entry_date, je.description as entry_desc
                FROM journal_entry_items ji
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                WHERE ji.account_id = ? AND je.status = 'posted'
                ORDER BY je.entry_date DESC, je.id DESC LIMIT 30
            ");
            $txStmt->execute([$id]);
            $transactions = $txStmt->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (Throwable $e) {
            $transactions = [];
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/accounts/show.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}