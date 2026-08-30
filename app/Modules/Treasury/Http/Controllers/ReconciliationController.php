<?php
// Path: app/Modules/Treasury/Http/Controllers/ReconciliationController.php

namespace App\Modules\Treasury\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;

class ReconciliationController extends Controller
{
    private string $basePath;

    public function __construct()
    {
        global $basePath;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
    }

    public function index(Request $request, Response $response): Response
    {
        $pageTitle = 'Bank Reconciliation';

        ob_start();
        include $this->basePath . '/resources/views/treasury/reconciliation/index.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        $html = ob_get_clean();

        return $response->setContent($html)->setHeader('Content-Type', 'text/html');
    }

    public function match(Request $request, Response $response): Response
    {
        return $this->json(['status' => 'success', 'message' => 'Transactions matched successfully.']);
    }
}