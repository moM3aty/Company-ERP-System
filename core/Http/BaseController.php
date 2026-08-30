<?php
// Path: core/Http/BaseController.php
namespace Core\Http;

use Core\Security\Auth;

class BaseController {
    
    protected $company_id;
    protected $branch_id;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // حماية افتراضية لأي Controller يمتد من هذا الكلاس
        if (!Auth::check()) {
            header("Location: /ERP/login");
            exit;
        }

        // تثبيت الشركة والفرع لجميع العمليات
        $this->company_id = Auth::companyId();
        $this->branch_id = Auth::branchId();
    }

    // دالة مساعدة لتمرير البيانات للـ Views مع الترجمة
    protected function view(string $path, array $data = []) {
        extract($data);
        $isRtl = isRtl();
        $dir = $isRtl ? 'rtl' : 'ltr';
        
        global $basePath;
        require $basePath . '/resources/views/' . $path . '.php';
    }
}