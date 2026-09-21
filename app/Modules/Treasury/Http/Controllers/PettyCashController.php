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
    private $basePath;
    private $db;

    public function __construct()
    {
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = isset($basePath) ? $basePath : dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            if ($this->db) {
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        }
    }

    private function resolveId($id = null)
    {
        if (!empty($id) && is_numeric($id)) return (int)$id;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/petty-cash/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    private function getCompanyId()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return (int)($_SESSION['company_id'] ?? 1);
    }

    private function getActiveBranchId()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return (int)($_SESSION['branch_id'] ?? 0);
    }

    private function hasBranchColumn($table)
    {
        if (!$this->db) return false;
        try {
            $this->db->query("SELECT branch_id FROM {$table} LIMIT 1");
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function buildBranchCond($tableAlias, $branchId, $tableName)
    {
        if ($branchId <= 0) return "";
        if (!$this->hasBranchColumn($tableName)) return "";
        $col = $tableAlias ? "{$tableAlias}.branch_id" : "branch_id";
        return " AND ({$col} = {$branchId} OR {$col} = 0 OR {$col} IS NULL)";
    }

    public function index(Request $request, Response $response)
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
        $limit = 15;
        $offset = ($page - 1) * $limit;

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $condPC = $this->buildBranchCond('pc', $branchId, 'treasury_petty_cash');

            $where = ["pc.company_id = ?"];
            $params = [$companyId];

            if ($search !== '') {
                $where[] = "(pc.code LIKE ? OR pc.employee_name LIKE ? OR pc.description LIKE ? OR a.name_ar LIKE ?)";
                $like = "%{$search}%";
                array_push($params, $like, $like, $like, $like);
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
                $whereSql $condPC
            ");
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();
            $totalPages = max(1, ceil($totalCount / $limit));

            $joinBranch = $this->hasBranchColumn('treasury_petty_cash') ? "LEFT JOIN sys_branches br ON pc.branch_id = br.id" : "";
            $colBranch = $this->hasBranchColumn('treasury_petty_cash') ? "br.name_ar as branch_name" : "'' as branch_name";

            $stmt = $this->db->prepare("
                SELECT pc.*, a.name_ar as account_name, a.code as account_code, {$colBranch}
                FROM treasury_petty_cash pc
                LEFT JOIN accounts a ON pc.treasury_account_id = a.id
                {$joinBranch}
                $whereSql $condPC
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
                FROM treasury_petty_cash pc
                WHERE pc.company_id = $companyId $condPC
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

    public function create(Request $request, Response $response)
    {
        $pettyCash = null; 
        $treasuryAccounts = [];

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();
            $condAcc = $this->buildBranchCond('a', $branchId, 'accounts');

            $treasuryAccounts = $this->db->query("
                SELECT a.id, a.code, a.name_ar 
                FROM accounts a 
                WHERE a.company_id = {$companyId} AND a.type = 'asset' AND (a.code LIKE '111%' OR a.name_ar LIKE '%خزينة%' OR a.name_ar LIKE '%بنك%' OR a.name_ar LIKE '%صندوق%') {$condAcc} 
                ORDER BY a.code ASC
            ")->fetchAll(PDO::FETCH_OBJ) ?: [];
            
            $nextSeq = (int)$this->db->query("SELECT COUNT(*) FROM treasury_petty_cash WHERE company_id = {$companyId}")->fetchColumn() + 1;
            $autoCode = 'PC-' . date('Y') . '-' . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);

        } catch (Throwable $e) {
            $autoCode = 'PC-' . date('Y') . '-00001';
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/petty_cash/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response)
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            if (empty($data['code']) || empty($data['employee_name']) || empty($data['treasury_account_id']) || empty($data['amount'])) {
                throw new Exception($isAr ? "يرجى تعبئة كافة الحقول الأساسية لإصدار العهدة." : "Please fill required custody fields.");
            }

            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();
            $amount = (float)$data['amount'];

            $hasBranch = $this->hasBranchColumn('treasury_petty_cash');
            $branchCol = $hasBranch ? ", branch_id" : "";
            $branchVal = $hasBranch ? ", ?" : "";

            $stmt = $this->db->prepare("
                INSERT INTO treasury_petty_cash 
                (company_id, code, employee_name, treasury_account_id, amount, spent_amount, remaining_amount, issue_date, status, description, created_by {$branchCol})
                VALUES (?, ?, ?, ?, ?, 0.00, ?, ?, 'active', ?, ? {$branchVal})
            ");

            $params = [
                $companyId,
                trim($data['code']),
                trim($data['employee_name']),
                (int)$data['treasury_account_id'],
                $amount,
                $amount,
                $data['issue_date'] ?? date('Y-m-d'),
                trim($data['description'] ?? ''),
                $_SESSION['user_id'] ?? 1
            ];

            if ($hasBranch) {
                $params[] = $branchId;
            }

            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم تسليم وإصدار العُهدة المالية بنجاح." : "Petty cash issued successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/petty-cash/create");
            exit;
        }

        header("Location: /ERP/treasury/petty-cash");
        exit;
    }

    public function edit(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $companyId = $this->getCompanyId();
            $branchId = $this->getActiveBranchId();

            $stmt = $this->db->prepare("SELECT * FROM treasury_petty_cash WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $pettyCash = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$pettyCash) throw new Exception("بيانات العُهدة المالية غير موجودة.");

            $condAcc = $this->buildBranchCond('a', $branchId, 'accounts');
            $treasuryAccounts = $this->db->query("SELECT id, code, name_ar FROM accounts a WHERE company_id = $companyId AND type = 'asset' $condAcc ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $autoCode = $pettyCash->code;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/petty-cash");
            exit;
        }

        ob_start(); include $this->basePath . '/resources/views/treasury/petty_cash/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            $companyId = $this->getCompanyId();
            $amount = (float)$data['amount'];
            $spent = (float)($data['spent_amount'] ?? 0.00);
            $remaining = max(0, $amount - $spent);
            $status = $remaining == 0 ? 'closed' : ($spent > 0 ? 'partially_settled' : 'active');

            $stmt = $this->db->prepare("
                UPDATE treasury_petty_cash 
                SET employee_name = ?, treasury_account_id = ?, amount = ?, spent_amount = ?, remaining_amount = ?, issue_date = ?, status = ?, description = ?
                WHERE id = ? AND company_id = ?
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
                $id,
                $companyId
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات العُهدة بنجاح." : "Petty cash updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/petty-cash/{$id}/edit");
            exit;
        }

        header("Location: /ERP/treasury/petty-cash");
        exit;
    }

    public function settle(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';

        try {
            $companyId = $this->getCompanyId();

            $stmt = $this->db->prepare("SELECT * FROM treasury_petty_cash WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $pc = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$pc) throw new Exception($isAr ? "العُهدة غير موجودة." : "Custody not found.");

            $addSpent = (float)($data['settle_amount'] ?? 0.00);
            if ($addSpent <= 0) throw new Exception($isAr ? "يرجى إدخال مبلغ تسوية صحيح." : "Please enter a valid settlement amount.");

            $newSpent = (float)$pc->spent_amount + $addSpent;
            if ($newSpent > (float)$pc->amount) {
                throw new Exception($isAr ? "مبلغ التسوية أكبر من القيمة المتبقية في العهدة." : "Settlement amount exceeds remaining budget.");
            }

            $newRemaining = (float)$pc->amount - $newSpent;
            $newStatus = $newRemaining == 0 ? 'closed' : 'partially_settled';

            $upStmt = $this->db->prepare("
                UPDATE treasury_petty_cash 
                SET spent_amount = ?, remaining_amount = ?, status = ?
                WHERE id = ? AND company_id = ?
            ");
            $upStmt->execute([$newSpent, $newRemaining, $newStatus, $id, $companyId]);

            $_SESSION['flash_msg'] = $isAr ? "تم تسجيل تصفية المنصرف وتخفيض ميزانية العُهدة بنجاح." : "Settlement recorded successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/treasury/petty-cash/{$id}");
        exit;
    }

    public function delete(Request $request, Response $response, $id = null)
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = $this->getCompanyId();

        try {
            $this->db->prepare("DELETE FROM treasury_petty_cash WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = "تم حذف سجل العُهدة المالية بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        header("Location: /ERP/treasury/petty-cash");
        exit;
    }

    public function show(Request $request, Response $response, $id = null)
    {
        try {
            $id = $this->resolveId($id);
            if (!$id) throw new Exception("المعرف غير متاح.");

            $companyId = $this->getCompanyId();

            $stmt = $this->db->prepare("
                SELECT pc.*, a.name_ar as account_name, a.code as account_code
                FROM treasury_petty_cash pc
                LEFT JOIN accounts a ON pc.treasury_account_id = a.id
                WHERE pc.id = ? AND pc.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $pettyCash = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$pettyCash) throw new Exception("سجل العُهدة المالي غير موجود.");

            ob_start(); include $this->basePath . '/resources/views/treasury/petty_cash/show.php';
            $content = ob_get_clean();
            
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            header("Location: /ERP/treasury/petty-cash");
            exit;
        }
    }
}