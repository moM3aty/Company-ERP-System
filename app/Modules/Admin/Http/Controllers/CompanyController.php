<?php
// Path: app/Modules/Admin/Http/Controllers/CompanyController.php

namespace App\Modules\Admin\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class CompanyController extends Controller
{
    private string $basePath;
    private ?PDO $db = null;

    public function __construct()
    {
        ini_set('display_errors', 1);
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
        if (preg_match('#/admin/companies/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/admin/companies/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/admin/companies/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/admin/companies/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/admin/companies/(\d+)/switch#', $uri, $m)) return $this->switchCompany($request, $response, (int)$m[1]);
        if (preg_match('#/admin/companies/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $companies = [];
        $totalPages = 1;
        $stats = (object)[
            'total' => 0,
            'active' => 0,
            'inactive' => 0
        ];

        if ($this->db) {
            try {
                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total,
                        SUM(IF(is_active = 1, 1, 0)) as active,
                        SUM(IF(is_active = 0, 1, 0)) as inactive
                    FROM sys_companies
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

                $where = ["1=1"];

                if ($search !== '') {
                    $where[] = "(code LIKE :s1 OR name_ar LIKE :s2 OR tax_number LIKE :s3 OR cr_number LIKE :s4)";
                }

                if ($statusFilter !== '') {
                    $where[] = "is_active = :status_val";
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM sys_companies $whereSql");
                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $countStmt->bindValue(':s1', $searchVal);
                    $countStmt->bindValue(':s2', $searchVal);
                    $countStmt->bindValue(':s3', $searchVal);
                    $countStmt->bindValue(':s4', $searchVal);
                }
                if ($statusFilter !== '') $countStmt->bindValue(':status_val', (int)$statusFilter, PDO::PARAM_INT);

                $countStmt->execute();
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT * FROM sys_companies
                    $whereSql
                    ORDER BY id DESC 
                    LIMIT :limit OFFSET :offset
                ");

                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $stmt->bindValue(':s1', $searchVal);
                    $stmt->bindValue(':s2', $searchVal);
                    $stmt->bindValue(':s3', $searchVal);
                    $stmt->bindValue(':s4', $searchVal);
                }
                if ($statusFilter !== '') $stmt->bindValue(':status_val', (int)$statusFilter, PDO::PARAM_INT);
                
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

                $stmt->execute();
                $companies = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            } catch (Throwable $e) {
                error_log("Companies Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/admin/companies/index.php', [
            'companies' => $companies,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'statusFilter' => $statusFilter
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        $autoCode = 'CMP-' . str_pad((string)(($this->getCompanyCount()) + 1), 3, '0', STR_PAD_LEFT);

        return $this->renderView('/resources/views/admin/companies/create.php', [
            'company' => null,
            'autoCode' => $autoCode
        ], $response);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            if (empty($data['code']) || empty($data['name_ar'])) {
                throw new Exception("يرجى تعبئة اسم وكود الشركة.");
            }

            $stmt = $this->db->prepare("
                INSERT INTO sys_companies (code, name_ar, tax_number, cr_number, is_active)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($data['code']),
                trim($data['name_ar']),
                trim($data['tax_number'] ?? ''),
                trim($data['cr_number'] ?? ''),
                isset($data['is_active']) ? (int)$data['is_active'] : 1
            ]);

            $_SESSION['flash_msg'] = "تم إنشاء بيانات الشركة بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء الإنشاء: " . $e->getMessage();
            return new RedirectResponse('/ERP/admin/companies/create');
        }

        return new RedirectResponse('/ERP/admin/companies');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $company = null;

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("SELECT * FROM sys_companies WHERE id = ?");
            $stmt->execute([$id]);
            $company = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$company) throw new Exception("الشركة غير موجودة.");

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/admin/companies');
        }

        return $this->renderView('/resources/views/admin/companies/create.php', [
            'company' => $company,
            'autoCode' => $company->code
        ], $response);
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("
                UPDATE sys_companies 
                SET name_ar = ?, tax_number = ?, cr_number = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                trim($data['name_ar']),
                trim($data['tax_number'] ?? ''),
                trim($data['cr_number'] ?? ''),
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $id
            ]);

            if (isset($_SESSION['company_id']) && $_SESSION['company_id'] == $id) {
                $_SESSION['company_name'] = trim($data['name_ar']);
                $_SESSION['company_name_ar'] = trim($data['name_ar']);
            }

            $_SESSION['flash_msg'] = "تم تحديث بيانات الشركة بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء التحديث: " . $e->getMessage();
            return new RedirectResponse("/ERP/admin/companies/{$id}/edit");
        }

        return new RedirectResponse('/ERP/admin/companies');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                if ($id == 1) {
                    throw new Exception("لا يمكن حذف الشركة الرئيسية للنظام.");
                }

                $this->db->prepare("DELETE FROM sys_companies WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف الشركة بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/admin/companies');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $company = null;
        $branches = [];

        try {
            if ($this->db) {
                $stmt = $this->db->prepare("SELECT * FROM sys_companies WHERE id = ?");
                $stmt->execute([$id]);
                $company = $stmt->fetch(PDO::FETCH_OBJ);

                if ($company) {
                    $stmtBranches = $this->db->prepare("SELECT * FROM sys_branches WHERE company_id = ? ORDER BY id DESC");
                    $stmtBranches->execute([$id]);
                    $branches = $stmtBranches->fetchAll(PDO::FETCH_OBJ) ?: [];
                }
            }
        } catch (Throwable $e) {}

        if (!$company) {
            $_SESSION['flash_err'] = "الشركة المطلوبة غير موجودة في النظام.";
            return new RedirectResponse('/ERP/admin/companies');
        }

        return $this->renderView('/resources/views/admin/companies/show.php', [
            'company' => $company,
            'branches' => $branches
        ], $response);
    }

    /**
     * دالة التبديل التفاعلي بين الشركات وتحديد أول فرع وعملة الشركة تلقائياً
     */
    public function switchCompany(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($this->db && $id) {
            try {
                $stmt = $this->db->prepare("SELECT * FROM sys_companies WHERE id = ? AND is_active = 1 LIMIT 1");
                $stmt->execute([$id]);
                $company = $stmt->fetch(PDO::FETCH_OBJ);

                if ($company) {
                    $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
                    $_SESSION['company_id'] = $company->id;
                    $_SESSION['company_name'] = $company->name_ar;
                    $_SESSION['company_name_ar'] = $company->name_ar;
                    $_SESSION['company_name_en'] = $company->name_en ?? $company->name_ar;
                    $_SESSION['active_company_name'] = $isAr ? $company->name_ar : ($company->name_en ?? $company->name_ar);

                    $curr = !empty($company->currency) ? $company->currency : 'EGP';
                    $_SESSION['company_currency'] = $curr;
                    $_SESSION['user_currency']    = $curr;
                    $_SESSION['currency']         = $curr;

                    // جلب وتعيين أول فرع تابع للشركة لمنع ظهور كافة الفروع
                    $branchStmt = $this->db->prepare("SELECT id, name_ar, name_en FROM sys_branches WHERE company_id = ? AND is_active = 1 ORDER BY id ASC LIMIT 1");
                    $branchStmt->execute([$company->id]);
                    $firstBranch = $branchStmt->fetch(PDO::FETCH_OBJ);

                    if ($firstBranch) {
                        $_SESSION['branch_id'] = (int)$firstBranch->id;
                        $_SESSION['active_branch_name'] = $isAr ? $firstBranch->name_ar : ($firstBranch->name_en ?? $firstBranch->name_ar);
                    } else {
                        $_SESSION['branch_id'] = 0;
                        $_SESSION['active_branch_name'] = $isAr ? 'كل الفروع' : 'All Branches';
                    }

                    $msg = $isAr ? 'تم التبديل بنجاح إلى شركة: ' : 'Switched successfully to: ';
                    $_SESSION['flash_msg'] = $msg . ($isAr ? $company->name_ar : ($company->name_en ?? $company->name_ar));
                } else {
                    $_SESSION['flash_err'] = ($_SESSION['locale'] ?? 'ar') === 'ar' ? 'الشركة غير متاحة أو موقوفة.' : 'Selected company is inactive.';
                }
            } catch (Throwable $e) {
                $_SESSION['flash_err'] = $e->getMessage();
            }
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/ERP/dashboard';
        return new RedirectResponse($referer);
    }

    /**
     * دالة التبديل التفاعلي للعملة
     */
    public function switchCurrency(Request $request, Response $response, $code = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!$code) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (preg_match('#/currency/([A-Za-z]{3})/switch#', $uri, $m)) {
                $code = $m[1];
            }
        }

        $code = strtoupper(trim($code ?? 'EGP'));
        $allowedCurrencies = ['EGP', 'SAR', 'USD', 'EUR'];

        if (in_array($code, $allowedCurrencies)) {
            $_SESSION['currency']         = $code;
            $_SESSION['user_currency']    = $code;
            $_SESSION['company_currency'] = $code;

            $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
            $_SESSION['flash_msg'] = $isAr 
                ? "تم تغيير العملة الحالية إلى: {$code}" 
                : "Currency changed successfully to: {$code}";
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/ERP/dashboard';
        return new RedirectResponse($referer);
    }

    /**
     * دالة تبديل اللغة التفاعلية
     */
    public function switchLanguage(Request $request, Response $response, $lang = null): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!$lang) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (preg_match('#/lang/(ar|en)/switch#', $uri, $m)) {
                $lang = $m[1];
            }
        }

        $lang = strtolower($lang ?? 'ar');
        if (in_array($lang, ['ar', 'en'])) {
            $_SESSION['locale'] = $lang;
            if (isset($_SESSION['user_id']) && $this->db) {
                try {
                    $stmt = $this->db->prepare("UPDATE users SET language = ? WHERE id = ?");
                    $stmt->execute([$lang, $_SESSION['user_id']]);
                } catch (Throwable $e) {}
            }
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/ERP/dashboard';
        return new RedirectResponse($referer);
    }

    private function getCompanyCount(): int
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM sys_companies")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function renderView(string $viewPath, array $data, Response $response): Response
    {
        extract($data);
        $fullPath = $this->basePath . $viewPath;

        if (!file_exists($fullPath)) {
            die("View File Missing: " . htmlspecialchars($fullPath));
        }

        try {
            ob_start();
            include $fullPath;
            $content = ob_get_clean();

            ob_start();
            include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');
        } catch (Throwable $e) {
            ob_end_clean();
            die("View Render Error: " . htmlspecialchars($e->getMessage()));
        }
    }
}