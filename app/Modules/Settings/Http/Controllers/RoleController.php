<?php
// Path: app/Modules/Settings/Http/Controllers/RoleController.php

namespace App\Modules\Settings\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use Core\Security\Auth;
use PDO;
use Exception;
use Throwable;

class RoleController extends Controller
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
        if (preg_match('#/settings/roles/(\d+)#', $uri, $m)) return (int)$m[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) {
            Auth::requirePermission('settings.roles.view');
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/settings/roles/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/settings/roles/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/settings/roles/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);

        $roles = [];
        if ($this->db) {
            try {
                $stmt = $this->db->query("
                    SELECT r.*, 
                    (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) as users_count 
                    FROM roles r 
                    ORDER BY r.id ASC
                ");
                $roles = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {
                error_log("Roles Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/settings/roles/index.php', [
            'roles' => $roles
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) {
            Auth::requirePermission('settings.roles.create');
        }

        return $this->renderView('/resources/views/settings/roles/form.php', [
            'role' => null,
            'rolePermissions' => [],
            'matrix' => $this->getPermissionMatrix()
        ], $response);
    }

    public function store(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) {
            Auth::requirePermission('settings.roles.create');
        }
        if (session_status() === PHP_SESSION_NONE) session_start();

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $permissions = $_POST['permissions'] ?? [];

        if (empty($name)) {
            $_SESSION['flash_err'] = 'اسم الدور مطلوب.';
            return new RedirectResponse('/ERP/settings/roles/create');
        }

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO roles (company_id, name, description, is_active, is_system) VALUES (1, ?, ?, ?, 0)");
            $stmt->execute([$name, $description, $isActive]);
            $roleId = $this->db->lastInsertId();

            if (!empty($permissions)) {
                $pStmt = $this->db->prepare("INSERT INTO role_permissions (role_id, permission_key) VALUES (?, ?)");
                foreach ($permissions as $permKey) {
                    $pStmt->execute([$roleId, $permKey]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = 'تم حفظ الدور الوظيفي والمصفوفة بنجاح.';
            return new RedirectResponse('/ERP/settings/roles');
        } catch (Throwable $e) {
            if ($this->db && $this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = 'حدث خطأ أثناء الحفظ: ' . $e->getMessage();
            return new RedirectResponse('/ERP/settings/roles/create');
        }
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) {
            Auth::requirePermission('settings.roles.edit');
        }

        $id = $this->resolveId($id);
        $role = null;
        $rolePermissions = [];

        if ($this->db && $id) {
            try {
                $stmt = $this->db->prepare("SELECT * FROM roles WHERE id = ?");
                $stmt->execute([$id]);
                $role = $stmt->fetch(PDO::FETCH_OBJ);

                if ($role) {
                    $pStmt = $this->db->prepare("SELECT permission_key FROM role_permissions WHERE role_id = ?");
                    $pStmt->execute([$id]);
                    $rolePermissions = $pStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                }
            } catch (Throwable $e) {}
        }

        if (!$role) {
            return new RedirectResponse('/ERP/settings/roles');
        }

        return $this->renderView('/resources/views/settings/roles/form.php', [
            'role' => $role,
            'rolePermissions' => $rolePermissions,
            'matrix' => $this->getPermissionMatrix()
        ], $response);
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) {
            Auth::requirePermission('settings.roles.edit');
        }
        if (session_status() === PHP_SESSION_NONE) session_start();

        $id = $this->resolveId($id);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $permissions = $_POST['permissions'] ?? [];

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE roles SET name = ?, description = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $description, $isActive, $id]);

            $delStmt = $this->db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $delStmt->execute([$id]);

            if (!empty($permissions)) {
                $pStmt = $this->db->prepare("INSERT INTO role_permissions (role_id, permission_key) VALUES (?, ?)");
                foreach ($permissions as $permKey) {
                    $pStmt->execute([$id, $permKey]);
                }
            }

            $this->db->commit();

            if (class_exists('\Core\Security\Auth') && ($_SESSION['user_role_id'] ?? null) == $id) {
                Auth::loadUserPermissions($id);
            }

            $_SESSION['flash_msg'] = 'تم تحديث مصفوفة الصلاحيات بنجاح.';
            return new RedirectResponse('/ERP/settings/roles');
        } catch (Throwable $e) {
            if ($this->db && $this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = 'خطأ أثناء التحديث: ' . $e->getMessage();
            return new RedirectResponse("/ERP/settings/roles/{$id}/edit");
        }
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) {
            Auth::requirePermission('settings.roles.delete');
        }
        if (session_status() === PHP_SESSION_NONE) session_start();

        $id = $this->resolveId($id);
        if ($this->db && $id) {
            try {
                $stmt = $this->db->prepare("DELETE FROM roles WHERE id = ? AND is_system = 0");
                $stmt->execute([$id]);

                if ($stmt->rowCount() > 0) {
                    $this->db->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$id]);
                    $_SESSION['flash_msg'] = 'تم حذف الدور بنجاح.';
                } else {
                    $_SESSION['flash_err'] = 'لا يمكن حذف أدوار النظام الأساسية.';
                }
            } catch (Throwable $e) {
                $_SESSION['flash_err'] = $e->getMessage();
            }
        }

        return new RedirectResponse('/ERP/settings/roles');
    }

    private function getPermissionMatrix(): array
    {
        return [
            'المبيعات وعلاقات العملاء' => [
                'sales.customers' => 'سجل العملاء',
                'sales.quotations' => 'عروض الأسعار',
                'sales.orders' => 'أوامر البيع',
                'sales.invoices' => 'فواتير المبيعات',
                'sales.contracts' => 'عقود المبيعات'
            ],
            'المشتريات والموردين' => [
                'purchasing.suppliers' => 'سجل الموردين',
                'purchasing.requisitions' => 'طلبات الشراء',
                'purchasing.orders' => 'أوامر الشراء',
                'purchasing.invoices' => 'فواتير المشتريات'
            ],
            'المخازن والمستودعات' => [
                'inventory.products' => 'الأصناف والمنتجات',
                'inventory.warehouses' => 'المستودعات',
                'inventory.transfers' => 'التحويلات المخزنية'
            ],
            'الخزانة والبنوك' => [
                'treasury.accounts' => 'الحسابات والصناديق',
                'treasury.receipts' => 'سندات القبض',
                'treasury.payments' => 'سندات الصرف'
            ],
            'الموارد البشرية' => [
                'hr.employees' => 'دليل الموظفين',
                'hr.contracts' => 'العقود',
                'hr.attendance' => 'الحضور والانصراف',
                'hr.payroll' => 'مسيرات الرواتب'
            ],
            'الإعدادات والنظام' => [
                'settings.roles' => 'الأدوار والصلاحيات',
                'settings.users' => 'إدارة المستخدمين',
                'settings.general' => 'إعدادات النظام العامة'
            ]
        ];
    }

    private function renderView(string $viewPath, array $data, Response $response): Response
    {
        extract($data);
        $fullPath = $this->basePath . $viewPath;

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