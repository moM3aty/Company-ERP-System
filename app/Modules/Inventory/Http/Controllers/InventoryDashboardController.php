<?php
// Path: app/Modules/Inventory/Http/Controllers/InventoryDashboardController.php

namespace App\Modules\Inventory\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use PDO;
use Throwable;

class InventoryDashboardController extends Controller
{
    private string $basePath;
    private PDO $db;

    public function __construct()
    {
        // إخفاء الأخطاء البرمجية عن واجهة المستخدم للحفاظ على شكل النظام
        ini_set('display_errors', 0);
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    // دالة درع الحماية: تنفذ الاستعلام، وإذا فشل تعيد قيمة افتراضية دون تحطيم الصفحة
    private function getSafeValue(string $sql, $default = 0)
    {
        try {
            $stmt = $this->db->query($sql);
            if ($stmt) {
                $val = $stmt->fetchColumn();
                return $val !== false ? $val : $default;
            }
        } catch (Throwable $e) {
            // يتم كتم الخطأ عمداً لكي تستمر باقي اللوحة في العمل
        }
        return $default;
    }

    public function index(Request $request, Response $response): Response
    {
        $kpis = [
            'stock_value'       => 0,
            'total_products'    => 0,
            'active_warehouses' => 0,
            'low_stock_alerts'  => 0,
            'total_movements'   => 0,
            'pending_transfers' => 0
        ];

        $chartData = [
            'movements_in'  => 0,
            'movements_out' => 0,
            'ops_breakdown' => [
                'transfers'   => 0,
                'deliveries'  => 0,
                'returns'     => 0,
                'adjustments' => 0
            ]
        ];

        $recentMovements = [];
        $recentDeliveries = [];

        // 1. المؤشرات الرئيسية المعزولة
        $kpis['total_products']    = (int)$this->getSafeValue("SELECT COUNT(id) FROM products WHERE is_active = 1");
        $kpis['active_warehouses'] = (int)$this->getSafeValue("SELECT COUNT(id) FROM warehouses WHERE is_active = 1");
        $kpis['total_movements']   = (int)$this->getSafeValue("SELECT COUNT(id) FROM stock_movements");
        $kpis['pending_transfers'] = (int)$this->getSafeValue("SELECT COUNT(id) FROM stock_transfers WHERE status = 'in_transit' OR status = 'draft'");
        
        // قد لا تكون هذه الأعمدة موجودة في جدول المنتجات، لذا الدالة ستحميها
        $kpis['stock_value']      = (float)$this->getSafeValue("SELECT COALESCE(SUM(quantity * purchase_price), 0) FROM products");
        $kpis['low_stock_alerts'] = (int)$this->getSafeValue("SELECT COUNT(id) FROM products WHERE is_active = 1 AND quantity <= 5");

        // 2. الرسوم البيانية المعزولة
        $chartData['movements_in']  = (float)$this->getSafeValue("SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE movement_type = 'in'");
        $chartData['movements_out'] = (float)$this->getSafeValue("SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE movement_type = 'out'");

        $chartData['ops_breakdown']['transfers']   = (int)$this->getSafeValue("SELECT COUNT(id) FROM stock_transfers");
        $chartData['ops_breakdown']['deliveries']  = (int)$this->getSafeValue("SELECT COUNT(id) FROM delivery_notes");
        $chartData['ops_breakdown']['returns']     = (int)$this->getSafeValue("SELECT COUNT(id) FROM stock_returns");
        $chartData['ops_breakdown']['adjustments'] = (int)$this->getSafeValue("SELECT COUNT(id) FROM stock_adjustments");

        // 3. أحدث الحركات (معزولة بـ Try/Catch منفصل)
        try {
            $recentMovements = $this->db->query("
                SELECT sm.*, p.name_ar as product_name, p.item_code as product_code, w.name_ar as warehouse_name
                FROM stock_movements sm
                LEFT JOIN products p ON sm.product_id = p.id
                LEFT JOIN warehouses w ON sm.warehouse_id = w.id
                ORDER BY sm.id DESC LIMIT 5
            ")->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (Throwable $e) {}

        // 4. أحدث التسليمات (معزولة بـ Try/Catch منفصل)
        try {
            $recentDeliveries = $this->db->query("
                SELECT dn.*, w.name_ar as warehouse_name
                FROM delivery_notes dn
                LEFT JOIN warehouses w ON dn.warehouse_id = w.id
                ORDER BY dn.id DESC LIMIT 5
            ")->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (Throwable $e) {}

        ob_start();
        include $this->basePath . '/resources/views/inventory/dashboard/index.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}