<?php
// Path: app/Modules/Treasury/Http/Controllers/PettyCashController.php

namespace App\Modules\Treasury\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class PettyCashController extends Controller
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
        if (preg_match('#/petty-cash/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/petty-cash/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/petty-cash/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/petty-cash/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/petty-cash/(\d+)/settle#', $uri, $m)) return $this->settle($request, $response, (int)$m[1]);
        if (preg_match('#/petty-cash/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
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
                $where[] = "(pc.code LIKE ? OR pc.employee_name LIKE ? OR pc.description LIKE ? OR a.name_ar LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like, $like]);
            }

            if ($statusFilter !== '') {
                $where[] = "pc.status = ?";
                $params[] = $statusFilter;
            }

            if ($fromDate !== '') {
                $where[] = "pc.issue_date >= ?";
                $params[] = $fromDate;
            }

            if ($toDate !== '') {
                $where[] = "pc.issue_date <= ?";
                $params[] = $toDate;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM treasury_petty_cash pc
                LEFT JOIN accounts a ON pc.treasury_account_id = a.id
                $whereSql
            ");
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCount / $limit));

            $stmt = $this->db->prepare("
                SELECT pc.*, a.name_ar as account_name, a.code as account_code
                FROM treasury_petty_cash pc
                LEFT JOIN accounts a ON pc.treasury_account_id = a.id
                $whereSql
                ORDER BY pc.issue_date DESC, pc.id DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $pettyCashList = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $stats = $this->db->query("
                SELECT 
                    COUNT(*) as total_custodies,
                    COALESCE(SUM(amount), 0) as total_amount,
                    COALESCE(SUM(spent_amount), 0) as total_spent,
                    COALESCE(SUM(remaining_amount), 0) as total_remaining,
                    SUM(IF(status = 'active', 1, 0)) as active_count
                FROM treasury_petty_cash
            ")->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $pettyCashList = [];
            $stats = (object)['total_custodies'=>0, 'total_amount'=>0, 'total_spent'=>0, 'total_remaining'=>0, 'active_count'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;
        ob_start(); include $this->basePath . '/resources/views/treasury/petty_cash/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $pettyCash = null; 
        $treasuryAccounts = [];

        try {
            $treasuryAccounts = $this->db->query("
                SELECT id, code, name_ar 
                FROM accounts 
                WHERE type = 'asset' AND (code LIKE '111%' OR name_ar LIKE '%خزينة%' OR name_ar LIKE '%بنك%' OR name_ar LIKE '%صندوق%') 
                ORDER BY code ASC
            ")->fetchAll(PDO::FETCH_OBJ);
            
            $nextSeq = (int)$this->db->query("SELECT COUNT(*) FROM treasury_petty_cash")->fetchColumn() + 1;
            $autoCode = 'PC-' . date('Y') . '-' . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);

        } catch (Throwable $e) {
            $autoCode = 'PC-' . date('Y') . '-00001';
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/petty_cash/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (empty($data['code']) || empty($data['employee_name']) || empty($data['treasury_account_id']) || empty($data['amount'])) {
                throw new Exception("يرجى تعبئة كافة الحقول الأساسية لإصدار العهدة.");
            }

            $amount = (float)$data['amount'];

            $stmt = $this->db->prepare("
                INSERT INTO treasury_petty_cash 
                (code, employee_name, treasury_account_id, amount, spent_amount, remaining_amount, issue_date, status, description, created_by)
                VALUES (?, ?, ?, ?, 0.00, ?, ?, 'active', ?, ?)
            ");
            $stmt->execute([
                trim($data['code']),
                trim($data['employee_name']),
                (int)$data['treasury_account_id'],
                $amount,
                $amount,
                $data['issue_date'] ?? date('Y-m-d'),
                trim($data['description'] ?? ''),
                $_SESSION['user_id'] ?? 1
            ]);

            $_SESSION['flash_msg'] = "تم تسليم وإصدار العُهدة المالية بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/treasury/petty-cash/create');
        }

        return new RedirectResponse('/ERP/treasury/petty-cash');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $stmt = $this->db->prepare("SELECT * FROM treasury_petty_cash WHERE id = ?");
            $stmt->execute([$id]);
            $pettyCash = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$pettyCash) throw new Exception("بيانات العُهدة المالية غير موجودة.");

            $treasuryAccounts = $this->db->query("SELECT id, code, name_ar FROM accounts WHERE type = 'asset' ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ);
            $autoCode = $pettyCash->code;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/treasury/petty-cash');
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/petty_cash/create.php';
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
            $amount = (float)$data['amount'];
            $spent = (float)($data['spent_amount'] ?? 0.00);
            $remaining = max(0, $amount - $spent);
            $status = $remaining == 0 ? 'closed' : ($spent > 0 ? 'partially_settled' : 'active');

            $stmt = $this->db->prepare("
                UPDATE treasury_petty_cash 
                SET employee_name = ?, treasury_account_id = ?, amount = ?, spent_amount = ?, remaining_amount = ?, issue_date = ?, status = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($data['employee_name']),
                (int)$data['treasury_account_id'],
                $amount,
                $spent,
                $remaining,
                $data['issue_date'],
                $status,
                trim($data['description'] ?? ''),
                $id
            ]);

            $_SESSION['flash_msg'] = "تم تحديث بيانات العُهدة بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/treasury/petty-cash/{$id}/edit");
        }

        return new RedirectResponse('/ERP/treasury/petty-cash');
    }

    public function settle(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $stmt = $this->db->prepare("SELECT * FROM treasury_petty_cash WHERE id = ?");
            $stmt->execute([$id]);
            $pc = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$pc) throw new Exception("العُهدة غير موجودة.");

            $addSpent = (float)($data['settle_amount'] ?? 0.00);
            if ($addSpent <= 0) throw new Exception("يرجى إدخال مبلغ تسوية صحيح.");

            $newSpent = (float)$pc->spent_amount + $addSpent;
            if ($newSpent > (float)$pc->amount) {
                throw new Exception("مبلغ التسوية أكبر من القيمة المتبقية في العهدة.");
            }

            $newRemaining = (float)$pc->amount - $newSpent;
            $newStatus = $newRemaining == 0 ? 'closed' : 'partially_settled';

            $upStmt = $this->db->prepare("
                UPDATE treasury_petty_cash 
                SET spent_amount = ?, remaining_amount = ?, status = ?
                WHERE id = ?
            ");
            $upStmt->execute([$newSpent, $newRemaining, $newStatus, $id]);

            $_SESSION['flash_msg'] = "تم تسجيل تسوية المباشرة وتخفيض ميزانية العُهدة بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse("/ERP/treasury/petty-cash/{$id}");
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $this->db->prepare("DELETE FROM treasury_petty_cash WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = "تم حذف سجل العُهدة المالية بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/treasury/petty-cash');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);

        $stmt = $this->db->prepare("
            SELECT pc.*, a.name_ar as account_name, a.code as account_code
            FROM treasury_petty_cash pc
            LEFT JOIN accounts a ON pc.treasury_account_id = a.id
            WHERE pc.id = ?
        ");
        $stmt->execute([$id]);
        $pettyCash = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$pettyCash) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "سجل العُهدة المالي غير موجود.";
            return new RedirectResponse('/ERP/treasury/petty-cash');
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/petty_cash/show.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}