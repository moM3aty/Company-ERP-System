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
    private ?PDO $db = null;

    public function __construct()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            if ($this->db) {
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        }
    }

    private function resolveId($id = null): ?int
    {
        if (!empty($id) && is_numeric($id)) return (int)$id;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/taxes/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    private function hasBranchesSupport(): bool 
    {
        if (!$this->db) return false;
        try {
            $this->db->query("SELECT branch_id FROM taxes LIMIT 1");
            return true;
        } catch (Throwable $e) { return false; }
    }

    private function getTenantCondition(string $alias = ''): string 
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = (int)($_SESSION['company_id'] ?? 1);
        $branchId  = (int)($_SESSION['branch_id'] ?? 0);
        
        $prefix = $alias ? $alias . '.' : '';
        $cond = "({$prefix}company_id = {$companyId} OR {$prefix}company_id IS NULL OR {$prefix}company_id = 0)";
        
        if ($this->hasBranchesSupport() && $branchId > 0) {
            $cond .= " AND ({$prefix}branch_id = {$branchId} OR {$prefix}branch_id IS NULL OR {$prefix}branch_id = 0)";
        }
        return $cond;
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_taxes_view');

            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (preg_match('#/taxes/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
            if (preg_match('#/taxes/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
            if (preg_match('#/taxes/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

            $search = trim($_GET['search'] ?? '');
            $typeFilter = trim($_GET['tax_type'] ?? '');
            $branchFilter = trim($_GET['branch_id'] ?? '');
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = 15;
            $offset = ($page - 1) * $limit;

            $tenantCond = $this->getTenantCondition('t');
            $hasBranch = $this->hasBranchesSupport();
            
            $taxes = []; $branches = [];
            $stats = (object)['total'=>0, 'vat_count'=>0, 'withholding_count'=>0];
            $totalPages = 1;

            if ($this->db) {
                $companyId = (int)($_SESSION['company_id'] ?? 1);
                if ($hasBranch) {
                    $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
                }

                $where = [$tenantCond];
                $params = [];

                if ($search !== '') {
                    $where[] = "(t.name_ar LIKE ? OR t.name_en LIKE ?)";
                    $like = "%{$search}%";
                    $params = array_merge($params, [$like, $like]);
                }
                if ($typeFilter !== '') {
                    $where[] = "t.tax_type = ?";
                    $params[] = $typeFilter;
                }
                if ($hasBranch && $branchFilter !== '') {
                    $where[] = "t.branch_id = ?";
                    $params[] = (int)$branchFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM taxes t $whereSql");
                $countStmt->execute($params);
                $totalPages = max(1, ceil($countStmt->fetchColumn() / $limit));

                $branchSelect = $hasBranch ? ", b.name_ar as branch_name, b.name_en as branch_name_en" : "";
                $branchJoin   = $hasBranch ? "LEFT JOIN branches b ON t.branch_id = b.id" : "";

                $stmt = $this->db->prepare("
                    SELECT t.*, a.code as acc_code, a.name_ar as acc_name, a.name_en as acc_name_en $branchSelect
                    FROM taxes t
                    LEFT JOIN accounts a ON t.account_id = a.id
                    $branchJoin
                    $whereSql
                    ORDER BY t.tax_type ASC, t.id DESC LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $taxes = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total,
                        SUM(IF(tax_type='vat', 1, 0)) as vat_count,
                        SUM(IF(tax_type='withholding', 1, 0)) as withholding_count
                    FROM taxes t WHERE $tenantCond AND is_active = 1
                ")->fetch(PDO::FETCH_OBJ);

                if ($statsData) {
                    $stats->total = $statsData->total;
                    $stats->vat_count = $statsData->vat_count;
                    $stats->withholding_count = $statsData->withholding_count;
                }
            }

            $currentPage = $page;
            
            ob_start(); include $this->basePath . '/resources/views/accounting/taxes/index.php';
            $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');

        } catch (Throwable $e) {
            die("<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 Controller Error (index)</h3>" . $e->getMessage() . "</div>");
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_taxes_create');

            if (session_status() === PHP_SESSION_NONE) session_start();
            $companyId = (int)($_SESSION['company_id'] ?? 1);
            $branches = []; $accounts = []; $tax = null;
            $hasBranch = $this->hasBranchesSupport();

            if ($this->db) {
                if ($hasBranch) {
                    $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
                }
                $accounts = $this->db->query("SELECT id, code, name_ar, name_en FROM accounts WHERE company_id = $companyId AND is_active = 1 AND is_parent = 0 AND (type = 'liability' OR type = 'asset') ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            }

            ob_start(); include $this->basePath . '/resources/views/accounting/taxes/create.php';
            $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
        } catch (Throwable $e) {
            die("<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 Controller Error (create)</h3>" . $e->getMessage() . "</div>");
        }
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_taxes_edit');

            $id = $this->resolveId($id);
            if (session_status() === PHP_SESSION_NONE) session_start();
            $companyId = (int)($_SESSION['company_id'] ?? 1);
            $branches = []; $accounts = [];
            $hasBranch = $this->hasBranchesSupport();

            if (!$this->db) throw new Exception("Database error.");

            $stmt = $this->db->prepare("SELECT * FROM taxes WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $tax = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$tax) {
                $_SESSION['flash_err'] = 'الضريبة المطلوبة غير موجودة.';
                return new RedirectResponse('/ERP/accounting/taxes');
            }

            if ($hasBranch) {
                $branches = $this->db->query("SELECT id, name_ar, name_en FROM branches WHERE company_id = $companyId AND is_active = 1")->fetchAll(PDO::FETCH_OBJ) ?: [];
            }
            $accounts = $this->db->query("SELECT id, code, name_ar, name_en FROM accounts WHERE company_id = $companyId AND is_active = 1 AND is_parent = 0 ORDER BY code ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

            ob_start(); include $this->basePath . '/resources/views/accounting/taxes/create.php';
            $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
        } catch (Throwable $e) {
            die("<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 Controller Error (edit)</h3>" . $e->getMessage() . "</div>");
        }
    }

    public function store(Request $request, Response $response): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_taxes_create');

            $data = $_POST;
            if (session_status() === PHP_SESSION_NONE) session_start();
            $companyId = (int)($_SESSION['company_id'] ?? 1);
            $sessionBranch = (int)($_SESSION['branch_id'] ?? 0);
            $branchId = ($sessionBranch > 0) ? $sessionBranch : (!empty($data['branch_id']) ? (int)$data['branch_id'] : 0);

            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $taxId = !empty($data['id']) ? (int)$data['id'] : null;
            $nameAr = trim($data['name_ar'] ?? '');
            $nameEn = trim($data['name_en'] ?? '');
            $rate = (float)($data['rate'] ?? 0);
            $taxType = $data['tax_type'] ?? 'vat';
            $accountId = (int)($data['account_id'] ?? 0);
            $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
            $notes = trim($data['notes'] ?? '');

            if (!$accountId) throw new Exception('يجب ربط الضريبة بحساب في الدليل المحاسبي.');

            if ($taxId) {
                $stmt = $this->db->prepare("UPDATE taxes SET branch_id=?, name_ar=?, name_en=?, rate=?, tax_type=?, account_id=?, is_active=?, notes=? WHERE id=? AND company_id=?");
                $stmt->execute([$branchId, $nameAr, $nameEn, $rate, $taxType, $accountId, $isActive, $notes, $taxId, $companyId]);
                $_SESSION['flash_msg'] = "تم تحديث بيانات الضريبة بنجاح.";
            } else {
                $stmt = $this->db->prepare("INSERT INTO taxes (company_id, branch_id, name_ar, name_en, rate, tax_type, account_id, is_active, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$companyId, $branchId, $nameAr, $nameEn, $rate, $taxType, $accountId, $isActive, $notes]);
                $_SESSION['flash_msg'] = "تم إضافة الضريبة بنجاح.";
            }

            return new RedirectResponse("/ERP/accounting/taxes");

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/taxes/create');
        }
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_taxes_view');

            $id = $this->resolveId($id);
            if (session_status() === PHP_SESSION_NONE) session_start();
            $companyId = (int)($_SESSION['company_id'] ?? 1);
            $hasBranch = $this->hasBranchesSupport();
            
            $convert = function($amt) { return function_exists('convert_amount') ? convert_amount((float)$amt) : (float)$amt; };

            if (!$this->db) throw new Exception("اتصال قاعدة البيانات مفقود.");

            $branchSelect = $hasBranch ? ", b.name_ar as branch_name, b.name_en as branch_name_en" : "";
            $branchJoin   = $hasBranch ? "LEFT JOIN branches b ON t.branch_id = b.id" : "";

            $stmt = $this->db->prepare("
                SELECT t.*, a.code as acc_code, a.name_ar as acc_name, a.name_en as acc_name_en $branchSelect
                FROM taxes t 
                LEFT JOIN accounts a ON t.account_id = a.id
                $branchJoin
                WHERE t.id = ? AND t.company_id = ?
            ");
            $stmt->execute([$id, $companyId]);
            $tax = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$tax) {
                $_SESSION['flash_err'] = 'الضريبة غير موجودة.';
                return new RedirectResponse('/ERP/accounting/taxes');
            }

            $txStmt = $this->db->prepare("
                SELECT ji.*, je.entry_number, je.entry_date, je.description as entry_desc
                FROM journal_entry_items ji
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                WHERE ji.account_id = ? AND je.company_id = ? AND je.status = 'posted'
                ORDER BY je.entry_date DESC, je.id DESC LIMIT 50
            ");
            $txStmt->execute([$tax->account_id, $companyId]);
            $transactions = $txStmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $totalDebit = 0; $totalCredit = 0;
            foreach ($transactions as $tx) {
                $tx->debit = $convert($tx->debit);
                $tx->credit = $convert($tx->credit);
                $totalDebit += $tx->debit;
                $totalCredit += $tx->credit;
            }

            ob_start(); include $this->basePath . '/resources/views/accounting/taxes/show.php';
            $content = ob_get_clean();
            ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');

        } catch (Throwable $e) {
            die("<div style='padding:20px; background:#fef2f2; color:#dc2626; font-family:monospace; border:2px solid #fecaca; border-radius:10px; margin:20px;' dir='ltr'><h3>🚨 Controller Error (show)</h3>" . $e->getMessage() . "</div>");
        }
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        try {
            if (class_exists('\Core\Security\Auth') && method_exists('\Core\Security\Auth', 'enforce')) Auth::enforce('accounting_taxes_delete');

            $id = $this->resolveId($id);
            if (session_status() === PHP_SESSION_NONE) session_start();
            $companyId = (int)($_SESSION['company_id'] ?? 1);

            $this->db->prepare("DELETE FROM taxes WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = 'تم حذف الضريبة بنجاح.';
            return new RedirectResponse('/ERP/accounting/taxes');

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/taxes');
        }
    }
}