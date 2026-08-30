<?php
// Path: app/Modules/Sales/Http/Controllers/InvoiceController.php

namespace App\Modules\Sales\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use App\Modules\Accounting\Services\AccountingEngine;
use PDO;

class InvoiceController extends Controller
{
    private string $basePath;
    private PDO $db;
    private AccountingEngine $accountingEngine;

    /* STREAMING_CHUNK: Initializing Sales Controller... */
    public function __construct() 
    { 
        global $basePath, $app; 
        $this->basePath = $basePath ?? dirname(__DIR__, 4); 
        $this->db = $app->get(PDO::class);
        // حقن المحرك المحاسبي
        $this->accountingEngine = new AccountingEngine($this->db);
    }

    /* STREAMING_CHUNK: Sales Invoices List... */
    public function index(Request $request, Response $response): Response
    {
        $pageTitle = 'Sales Invoices';
        
        ob_start();
        include $this->basePath . '/resources/views/sales/invoices/index.php';
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    /* STREAMING_CHUNK: Auto-Generate Invoice & Journal Entry... */
    public function storeDummy(Request $request, Response $response): Response
    {
        // محاكاة إنشاء فاتورة مبيعات جديدة من قبل موظف المبيعات
        $invoiceNo = 'INV-' . date('Y') . '-' . rand(1000, 9999);
        $netAmount = rand(5000, 20000); // مبلغ عشوائي
        $taxAmount = $netAmount * 0.15; // ضريبة 15%

        // هنا السحر! موديول المبيعات بيكلم موديول الحسابات بصمت في الخلفية
        try {
            $journalId = $this->accountingEngine->recordSalesInvoice($invoiceNo, $netAmount, $taxAmount);
            
            // إرسال رسالة نجاح في الـ Session (يمكن تطبيقها لاحقاً)
            $_SESSION['flash_msg'] = "Invoice $invoiceNo created and Journal Entry #$journalId posted successfully!";
        } catch (\Exception $e) {
            $_SESSION['flash_err'] = "Failed to post entry: " . $e->getMessage();
        }

        return new RedirectResponse('/ERP/sales/invoices');
    }
}