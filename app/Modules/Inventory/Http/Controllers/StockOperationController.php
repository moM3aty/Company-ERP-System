<?php
// Path: app/Modules/Inventory/Http/Controllers/StockOperationController.php

namespace App\Modules\Inventory\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;

class StockOperationController extends Controller
{
    private string $basePath;

    public function __construct() { global $basePath; $this->basePath = $basePath ?? dirname(__DIR__, 4); }

    // 37. Stock
    public function stock(Request $request, Response $response): Response { return $this->renderView('inventory/operations/stock', 'Stock Availability'); }

    // 39. Stock Transfers
    public function transfers(Request $request, Response $response): Response
    {
        $pageTitle = 'Stock Transfers';
        $transfers = [
            (object)['ref' => 'TRF-2026-001', 'source' => 'HQ Main Warehouse', 'dest' => 'Jeddah Logistics Hub', 'date' => '2026-08-19', 'qty' => 450, 'status' => 'In Transit', 'approval' => 'Approved'],
            (object)['ref' => 'TRF-2026-002', 'source' => 'Jeddah Logistics Hub', 'dest' => 'Showroom A', 'date' => '2026-08-18', 'qty' => 12, 'status' => 'Completed', 'approval' => 'Approved'],
        ];

        ob_start(); include $this->basePath . '/resources/views/inventory/operations/transfers.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php'; $html = ob_get_clean();

        return $response->setContent($html)->setHeader('Content-Type', 'text/html');
    }

    // 40. Stock Adjustments
    public function adjustments(Request $request, Response $response): Response { return $this->renderView('inventory/operations/adjustments', 'Stock Adjustments'); }
    
    // 43. Stock Taking
    public function stockTaking(Request $request, Response $response): Response { return $this->renderView('inventory/operations/stock_taking', 'Stock Taking (Inventory Count)'); }

    private function renderView(string $viewPath, string $pageTitle): Response
    {
        ob_start(); include $this->basePath . "/resources/views/{$viewPath}.php"; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php'; $html = ob_get_clean();
        return (new Response())->setContent($html)->setHeader('Content-Type', 'text/html');
    }
}