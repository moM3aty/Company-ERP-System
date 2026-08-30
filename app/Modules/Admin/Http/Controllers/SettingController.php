<?php
// Path: app/Modules/Admin/Http/Controllers/SettingController.php

namespace App\Modules\Admin\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class SettingController extends Controller
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

    public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        if (preg_match('#/admin/settings/(create|\d+)#', $uri)) {
            return new RedirectResponse('/ERP/admin/settings');
        }

        $settings = [];
        if ($this->db) {
            try {
                $rows = $this->db->query("SELECT setting_key, setting_value FROM sys_settings")->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $row) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            } catch (Throwable $e) {
                error_log("Settings Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/admin/settings/index.php', [
            'settings' => $settings
        ], $response);
    }

    public function update(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = $this->basePath . '/public/uploads/branding/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $ext = pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
                $fileName = 'logo_' . time() . '.' . strtolower($ext);
                if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $uploadDir . $fileName)) {
                    $data['company_logo'] = '/uploads/branding/' . $fileName;
                }
            }

            // قائمة شاملة بكل متغيرات الـ ERP
            $allowedKeys = [
                // 1. الشركة
                'company_name', 'company_email', 'company_phone', 'company_address', 'company_website', 'industry_type', 'manager_name', 'company_logo',
                // 2. الضرائب والقانونية
                'tax_number', 'cr_number', 'tax_rate', 'tax_office', 'invoice_terms',
                // 3. المالية والتشغيل
                'default_currency', 'timezone', 'date_format', 'fiscal_year_start', 'inventory_costing', 'invoice_prefix', 'payment_terms',
                // 4. المراسلات SMTP
                'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'mail_from_address', 'mail_from_name',
                // 5. الأمان والسياسات
                'session_timeout', 'password_complexity', 'max_login_attempts', 'two_factor_auth',
                // 6. الموارد البشرية
                'hr_working_hours', 'hr_grace_period', 'hr_overtime_rate', 'hr_weekend_days',
                // 7. النظام
                'system_language', 'items_per_page', 'maintenance_mode'
            ];

            $stmt = $this->db->prepare("
                INSERT INTO sys_settings (setting_key, setting_value) 
                VALUES (:key, :val) 
                ON DUPLICATE KEY UPDATE setting_value = :val
            ");

            foreach ($allowedKeys as $key) {
                if (array_key_exists($key, $data)) {
                    $stmt->execute([
                        ':key' => $key,
                        ':val' => trim((string)$data[$key])
                    ]);
                }
            }

            $_SESSION['flash_msg'] = "تم تحديث جميع إعدادات وثوابت النظام الشاملة بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء الحفظ: " . $e->getMessage();
        }

        return new RedirectResponse('/ERP/admin/settings');
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