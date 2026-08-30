<?php
// Path: app/Modules/Settings/Http/Controllers/UserController.php

namespace App\Modules\Settings\Http\Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use Core\Security\Auth;
use PDO;

class UserController {

    private function getDb(): PDO {
        global $app;
        if (!$app) {
            die('Database Connection Container Error');
        }
        return $app->get(PDO::class);
    }

    public function index(Request $request, Response $response) {
        Auth::requirePermission('settings.users.view');
        global $basePath;

        $db = $this->getDb();
        $stmt = $db->query("
            SELECT u.*, r.name as role_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            ORDER BY u.id DESC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_OBJ);

        ob_start();
        include $basePath . '/resources/views/settings/users/index.php';
        $content = ob_get_clean();

        ob_start();
        include $basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response) {
        Auth::requirePermission('settings.users.create');
        global $basePath;

        $db = $this->getDb();
        $roles = $db->query("SELECT id, name FROM roles WHERE is_active = 1")->fetchAll(PDO::FETCH_OBJ);
        $user = null;

        ob_start();
        include $basePath . '/resources/views/settings/users/form.php';
        $content = ob_get_clean();

        ob_start();
        include $basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store() {
        Auth::requirePermission('settings.users.create');
        if (session_status() === PHP_SESSION_NONE) session_start();

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = $_POST['role_id'] ?? null;
        $status = $_POST['status'] ?? 'active';
        $language = $_POST['language'] ?? 'ar';

        if (empty($username) || empty($email) || empty($password) || empty($roleId)) {
            $_SESSION['flash_err'] = 'يرجى استكمال كافة الحقول المطلوبة.';
            return new RedirectResponse('/ERP/settings/users/create');
        }

        $db = $this->getDb();
        
        $check = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        if ($check->rowCount() > 0) {
            $_SESSION['flash_err'] = 'اسم المستخدم أو البريد الإلكتروني مستخدم مسبقاً.';
            return new RedirectResponse('/ERP/settings/users/create');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (company_id, role_id, username, email, password_hash, language, status) VALUES (1, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$roleId, $username, $email, $hash, $language, $status]);

        $_SESSION['flash_msg'] = 'تم إنشاء حساب المستخدم بنجاح.';
        return new RedirectResponse('/ERP/settings/users');
    }

    public function edit(Request $request, Response $response, $id) {
        Auth::requirePermission('settings.users.edit');
        global $basePath;

        $db = $this->getDb();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$user) {
            return new RedirectResponse('/ERP/settings/users');
        }

        $roles = $db->query("SELECT id, name FROM roles WHERE is_active = 1")->fetchAll(PDO::FETCH_OBJ);

        ob_start();
        include $basePath . '/resources/views/settings/users/form.php';
        $content = ob_get_clean();

        ob_start();
        include $basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id) {
        Auth::requirePermission('settings.users.edit');
        if (session_status() === PHP_SESSION_NONE) session_start();

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = $_POST['role_id'] ?? null;
        $status = $_POST['status'] ?? 'active';
        $language = $_POST['language'] ?? 'ar';

        $db = $this->getDb();

        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET role_id = ?, username = ?, email = ?, password_hash = ?, language = ?, status = ? WHERE id = ?");
            $stmt->execute([$roleId, $username, $email, $hash, $language, $status, $id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET role_id = ?, username = ?, email = ?, language = ?, status = ? WHERE id = ?");
            $stmt->execute([$roleId, $username, $email, $language, $status, $id]);
        }

        $_SESSION['flash_msg'] = 'تم تحديث حساب المستخدم بنجاح.';
        return new RedirectResponse('/ERP/settings/users');
    }

    public function delete(Request $request, Response $response, $id) {
        Auth::requirePermission('settings.users.delete');
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($id == 1) {
            $_SESSION['flash_err'] = 'لا يمكن حذف الحساب الرئيسي للنظام.';
            return new RedirectResponse('/ERP/settings/users');
        }

        $db = $this->getDb();
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['flash_msg'] = 'تم حذف المستخدم بنجاح.';
        return new RedirectResponse('/ERP/settings/users');
    }
}