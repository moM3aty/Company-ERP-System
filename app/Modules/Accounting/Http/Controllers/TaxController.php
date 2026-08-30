<?php
// Path: app/Modules/Accounting/Http/Controllers/TaxController.php

namespace App\Modules\Accounting\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use Core\Security\Auth;
use PDO;
use Exception;
use Throwable;

class TaxController extends Controller
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
        if (preg_match('#/taxes/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_taxes_view');

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/taxes/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/taxes/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/taxes/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/taxes/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $typeFilter = trim($_GET['type'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;
        $companyId = current_company() ?? 1;

        try {
            $where = ["t.company_id = ?"];
            $params = [$companyId];

            if ($search !== '') {
                $where[] = "(t.code LIKE ? OR t.name_ar LIKE ? OR t.name_en LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like]);
            }
            if ($statusFilter !== '') {
                $where[] = "t.is_active = ?";
                $params[] = ($statusFilter === 'active') ? 1 : 0;
            }
            if ($typeFilter !== '') {
                $where[] = "t.tax_type = ?";
                $params[] = $typeFilter;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM taxes t $whereSql");
            $countStmt->execute($params);
            $totalPages = max(1, ceil($countStmt->fetchColumn() / $limit));

            $stmt = $this->db->prepare("
                SELECT t.*, a.code as acc_code, a.name_ar as acc_name 
                FROM taxes t
                LEFT JOIN accounts a ON t.account_id = a.id
                $whereSql ORDER BY t.code ASC LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $taxes = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $statsStmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_taxes,
                    SUM(IF(is_active=1, 1, 0)) as active_taxes,
                    SUM(IF(tax_type='vat', 1, 0)) as vat_taxes
                FROM taxes WHERE company_id = ?
            ");
            $statsStmt->execute([$companyId]);
            $stats = $statsStmt->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $taxes = [];
            $stats = (object)['total_taxes'=>0, 'active_taxes'=>0, 'vat_taxes'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;
        header('Content-Type: text/html; charset=utf-8');
        ob_start(); include $this->basePath . '/resources/views/accounting/taxes/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_taxes_create');

        $tax = null;
        $companyId = current_company() ?? 1;
        $accountsStmt = $this->db->prepare("SELECT id, code, name_ar FROM accounts WHERE is_active = 1 AND is_parent = 0 AND company_id = ? ORDER BY code ASC");
        $accountsStmt->execute([$companyId]);
        $accounts = $accountsStmt->fetchAll(PDO::FETCH_OBJ);

        header('Content-Type: text/html; charset=utf-8');
        ob_start(); include $this->basePath . '/resources/views/accounting/taxes/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_taxes_create');

        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        try {
            if (empty($data['code']) || empty($data['name_ar']) || empty($data['account_id'])) {
                throw new Exception(__('يرجى تعبئة الحقول الإلزامية.', 'Please fill required fields.'));
            }

            $isActive = isset($data['is_active']) ? 1 : 0;
            $taxRate = (float)($data['tax_rate'] ?? 0);

            $stmt = $this->db->prepare("
                INSERT INTO taxes (company_id, code, name_ar, name_en, tax_rate, tax_type, account_id, is_active, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId,
                trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''),
                $taxRate, $data['tax_type'] ?? 'vat', (int)$data['account_id'], $isActive, trim($data['notes'] ?? '')
            ]);

            $_SESSION['flash_msg'] = __('تم إنشاء كود الضريبة بنجاح.', 'Tax created successfully.');
            return new RedirectResponse('/ERP/accounting/taxes');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/taxes/create');
        }
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_taxes_view');

        $id = $this->resolveId($id);
        $companyId = current_company() ?? 1;

        $stmt = $this->db->prepare("
            SELECT t.*, a.code as acc_code, a.name_ar as acc_name, a.current_balance 
            FROM taxes t 
            LEFT JOIN accounts a ON t.account_id = a.id 
            WHERE t.id = ? AND t.company_id = ?
        ");
        $stmt->execute([$id, $companyId]);
        $tax = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$tax) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = __('الضريبة غير موجودة.', 'Tax not found.');
            return new RedirectResponse('/ERP/accounting/taxes');
        }

        $txStmt = $this->db->prepare("
            SELECT ji.*, je.entry_number, je.entry_date, je.description as entry_desc
            FROM journal_entry_items ji
            JOIN journal_entries je ON ji.journal_entry_id = je.id
            WHERE ji.account_id = ? AND je.status = 'posted' AND je.company_id = ?
            ORDER BY je.entry_date DESC LIMIT 50
        ");
        $txStmt->execute([$tax->account_id, $companyId]);
        $transactions = $txStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

        header('Content-Type: text/html; charset=utf-8');
        ob_start(); include $this->basePath . '/resources/views/accounting/taxes/show.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_taxes_edit');

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        try {
            $stmt = $this->db->prepare("SELECT * FROM taxes WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $tax = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$tax) throw new Exception(__('الضريبة غير موجودة.', 'Tax not found.'));

            $accountsStmt = $this->db->prepare("SELECT id, code, name_ar FROM accounts WHERE is_active = 1 AND is_parent = 0 AND company_id = ? ORDER BY code ASC");
            $accountsStmt->execute([$companyId]);
            $accounts = $accountsStmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/taxes');
        }

        header('Content-Type: text/html; charset=utf-8');
        ob_start(); include $this->basePath . '/resources/views/accounting/taxes/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_taxes_edit');

        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        try {
            $isActive = isset($data['is_active']) ? 1 : 0;
            $taxRate = (float)($data['tax_rate'] ?? 0);

            $stmt = $this->db->prepare("
                UPDATE taxes 
                SET code=?, name_ar=?, name_en=?, tax_rate=?, tax_type=?, account_id=?, is_active=?, notes=? 
                WHERE id=? AND company_id=?
            ");
            $stmt->execute([
                trim($data['code']), trim($data['name_ar']), trim($data['name_en'] ?? ''),
                $taxRate, $data['tax_type'] ?? 'vat', (int)$data['account_id'], $isActive, trim($data['notes'] ?? ''), 
                $id, $companyId
            ]);

            $_SESSION['flash_msg'] = __('تم تحديث بيانات الضريبة بنجاح.', 'Tax updated successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/accounting/taxes/{$id}/edit");
        }
        return new RedirectResponse('/ERP/accounting/taxes');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_taxes_delete');

        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        try {
            $this->db->prepare("DELETE FROM taxes WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = __('تم حذف كود الضريبة بنجاح.', 'Tax deleted successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = __('تعذر الحذف لارتباط الضريبة بعمليات مالية.', 'Cannot delete as it is linked to financial operations.');
        }
        return new RedirectResponse('/ERP/accounting/taxes');
    }
}