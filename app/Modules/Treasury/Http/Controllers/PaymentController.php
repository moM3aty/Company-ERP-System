<?php
// Path: app/Modules/Treasury/Http/Controllers/PaymentController.php

namespace App\Modules\Treasury\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use App\Modules\Accounting\Application\AccountingEngine;
use PDO;
use Exception;

class PaymentController extends Controller
{
    private PDO $db;
    private string $basePath;

    public function __construct()
    {
        global $app, $basePath;
        $this->db = $app->get(PDO::class);
        $this->basePath = $basePath ?? $_SERVER['DOCUMENT_ROOT'] . '/ERP';
    }

    /* STREAMING_CHUNK: Fetching Payments... */
    public function index(Request $request, Response $response): Response
    {
        $pageTitle = 'Payment Vouchers';
        try {
            $payments = $this->db->query("
                SELECT p.*, s.name as supplier_name, t.name as treasury_name, je.entry_no as je_ref 
                FROM trs_payment_vouchers p 
                LEFT JOIN suppliers s ON p.supplier_id = s.id 
                LEFT JOIN treasury_accounts t ON p.treasury_account_id = t.id
                LEFT JOIN journal_entries je ON p.journal_entry_id = je.id
                ORDER BY p.id DESC
            ")->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) { $payments = []; }

        ob_start(); include $this->basePath . '/resources/views/treasury/payments/index.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    /* STREAMING_CHUNK: Creating Payment... */
    public function create(Request $request, Response $response): Response
    {
        $pageTitle = 'Create Payment Voucher';
        try {
            $suppliers = $this->db->query("SELECT id, name FROM suppliers WHERE status = 'active'")->fetchAll(PDO::FETCH_OBJ);
            $treasuryAccounts = $this->db->query("SELECT id, name FROM treasury_accounts WHERE is_active = 1")->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) { $suppliers = []; $treasuryAccounts = []; }

        ob_start(); include $this->basePath . '/resources/views/treasury/payments/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    /* STREAMING_CHUNK: Process Payment & Auto-Accounting... */
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        try {
            $this->db->beginTransaction();

            $voucherNo = 'PV-' . date('Ym') . rand(100, 999);
            $amount = (float) $data['amount'];
            $treasuryId = (int) $data['treasury_account_id'];

            // 1. جلب حساب الـ GL المرتبط بالخزنة/البنك
            $treasuryStmt = $this->db->prepare("SELECT gl_account_id, name FROM treasury_accounts WHERE id = ?");
            $treasuryStmt->execute([$treasuryId]);
            $treasuryAcc = $treasuryStmt->fetch(PDO::FETCH_OBJ);

            if (!$treasuryAcc || !$treasuryAcc->gl_account_id) {
                throw new Exception("Selected Treasury Account is not linked to Accounting (GL).");
            }

            // 2. حفظ سند الصرف
            $stmt = $this->db->prepare("
                INSERT INTO trs_payment_vouchers (voucher_number, treasury_account_id, supplier_id, payee_name, amount, payment_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'posted')
            ");
            
            $payeeName = $data['payee_name'] ?? 'External Supplier/Expense';
            $stmt->execute([
                $voucherNo, $treasuryId, $data['supplier_id'] ?: null, $payeeName, $amount, date('Y-m-d')
            ]);
            $paymentId = $this->db->lastInsertId();

            // 3. المحرك المالي: حـ/ الموردين (مدين)، حـ/ الخزينة (دائن)
            $accounting = new AccountingEngine($this->db);
            $apAccount = $accounting->getSystemAccount(1, 'accounts_payable'); // حساب الموردين الافتراضي من الإعدادات

            $lines = [
                ['account_id' => $apAccount, 'debit' => $amount, 'credit' => 0, 'description' => "Payment $voucherNo - Supplier Settlement"],
                ['account_id' => $treasuryAcc->gl_account_id, 'debit' => 0, 'credit' => $amount, 'description' => "Payment $voucherNo - from {$treasuryAcc->name}"]
            ];

            $jeId = $accounting->generateAutomatedEntry(1, 'Payment Voucher', $paymentId, date('Y-m-d'), "Supplier Payment Issued ($voucherNo)", $lines);

            // 4. ربط القيد بالسند
            $this->db->prepare("UPDATE trs_payment_vouchers SET journal_entry_id = ? WHERE id = ?")->execute([$jeId, $paymentId]);

            $this->db->commit();
            if(session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_msg'] = "Payment Voucher $voucherNo posted successfully to Accounting!";

        } catch (Exception $e) {
            $this->db->rollBack();
            if(session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/treasury/payments');
    }
}